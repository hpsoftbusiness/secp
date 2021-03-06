<?php

declare(strict_types=1);

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Entity\Types\LoggableEntityInterface;
use App\Entity\User;
use App\Utils\ORM\ClassUtil;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Exception;
use StdClass;
use App\Utils\ORM\EntityLogAnnotationReader;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractWebTestCase extends WebTestCase
{
    public const HTTP_GET = 'GET';
    public const HTTP_POST = 'POST';
    public const HTTP_PUT = 'PUT';
    public const HTTP_PATCH = 'PATCH';
    public const HTTP_DELETE = 'DELETE';

    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_LD_JSON = 'application/ld+json';
    public const CONTENT_TYPE_XML = 'application/xml';

    /**
     * @var ReferenceRepository
     */
    protected static $staticFixtures = null;

    /**
     * @var ClassMetadata
     */
    protected static $staticMetadata = null;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ReferenceRepository
     */
    protected $fixtures;

    /**
     * @var null|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var null|Security
     */
    protected $security;

    /**
     * @var null|Request
     */
    protected $lastActionRequest;

    /**
     *
     * @throws ToolsException
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        /* @var $entityManager EntityManager */

        if (self::$staticMetadata === null) {
            self::$staticMetadata = $entityManager->getMetadataFactory()->getAllMetadata();

            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropDatabase();
            if (!empty(self::$staticMetadata)) {
                $schemaTool->createSchema(self::$staticMetadata);
            }
        }

        if (self::$staticFixtures === null) {
            $fixtures = new Fixtures();
            self::$staticFixtures = $fixtures->getFixtures();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->fixtures = self::$staticFixtures;
    }

    protected function getEntityFromReference($referenceName): ?object
    {
        if (!$this->fixtures->hasReference($referenceName)) {
            return null;
        }

        $reference = $this->fixtures->getReference($referenceName);

        return $this->entityManager->getRepository(get_class($reference))->find($reference->getId());
    }

    /**
     * @param string $method
     * @param string $route
     * @param string $payload
     * @param array $parameters
     * @param int $expectedStatus
     * @param string $userReference
     * @param string $contentTypeAccept
     * @param bool $anonymousRequest - ignore userReference and make an anonymous request
     *
     * @return null|Response
     * @throws NotFoundReferencedUserException
     */
    protected function getActionResponse(
        $method = self::HTTP_GET,
        $route = '/',
        $payload = null,
        $parameters = [],
        $expectedStatus = 200,
        $userReference = UserFixtures::REF_USER_ADMIN,
        $contentTypeAccept = self::CONTENT_TYPE_LD_JSON,
        $anonymousRequest = false
    ): ?Response {
        $client = $this->makeAuthenticatedClient($userReference);
        if ($anonymousRequest) {
            $client = $this->makeClient(false);
        }

        $client->request(
            $method,
            $route,
            $parameters,
            [],
            [
                'HTTP_Accept' => $contentTypeAccept,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => $this->createJWTTokenByReference($userReference),
                'HTTP_X_ANONYMOUS_REQUEST' => $anonymousRequest,
            ],
            $payload
        );

        $this->lastActionRequest = $client->getRequest();

        $this->assertJsonResponse($client->getResponse(), $expectedStatus);

        return $client->getResponse();
    }

    /**
     * Create JWT Token from user reference.
     *
     * @param string $userRef
     *
     * @return string
     * @throws InvalidArgumentException when userRef entity is not UserInterface object
     */
    protected function createJWTTokenByReference(string $userRef): string
    {
        $entity = $this->getEntityFromReference($userRef);
        if (!$entity instanceof UserInterface) {
            throw new InvalidArgumentException('Instance of UserInterface expected.');
        }

        return self::$container->get(JWTTokenManagerInterface::class)->create($entity);
    }

    /**
     * @param string $referenceName
     * @return Client
     * @throws NotFoundReferencedUserException
     */
    protected function makeAuthenticatedClient(string $referenceName): Client
    {
        if (!$this->fixtures->hasReference($referenceName)) {
            throw new NotFoundReferencedUserException();
        }

        $this->loginAs($this->fixtures->getReference($referenceName), 'login');
        return $this->makeClient();
    }

    /**
     * @param Response $response
     * @param int $expectedStatusCode
     */
    protected function assertJsonResponse($response, $expectedStatusCode = 200): void
    {
        $this->assertEquals(
            $expectedStatusCode,
            $response->getStatusCode(),
            $response->getContent()
        );

        if (json_decode($response->getContent(), false) !== null) {
            $this->assertTrue(
                $response->headers->contains('Content-Type', 'application/json') ||
                $response->headers->contains('Content-Type', 'application/json; charset=utf-8') ||
                $response->headers->contains('Content-Type', 'application/ld+json; charset=utf-8')
            );
        }
    }

    /**
     * @param array $theArray
     * @param string $keyName
     * @param mixed $value
     */
    protected function assertArrayContainsSameKeyWithValue($theArray, $keyName, $value): void
    {
        foreach ($theArray as $arrayItem) {
            if (!array_key_exists($keyName, $arrayItem)) {
                $this->assertTrue(
                    false,
                    sprintf('Array not contains given key: [%s]', $keyName)
                );
            }

            if ($arrayItem[$keyName] == $value) {
                $this->assertTrue(true);
                return;
            }
        }

        $this->assertTrue(
            false,
            sprintf('Array not contains given value: [%s => %s]', $keyName, $value)
        );
    }

    /**
     * @param object $listObject
     * @param string $attributeName
     * @param mixed $value
     */
    protected function assertListContainsSameObjectWithValue($listObject, $attributeName, $value): void
    {
        foreach ($listObject as $item) {
            $objectVars = get_class_vars(get_class($item));
            $objectMethods = get_class_methods(get_class($item));

            if (array_key_exists($attributeName, $objectVars)) {
                if ($item->$attributeName === $value) {
                    $this->assertTrue(true);
                    return;
                }
            } elseif (in_array($attributeName, $objectMethods, true)) {
                if ($item->$attributeName() === $value) {
                    $this->assertTrue(true);
                    return;
                }
            } else {
                $this->assertTrue(
                    false,
                    sprintf('Object not contains given attribute: [%s]', $attributeName)
                );
            }
        }

        $this->assertTrue(
            false,
            sprintf('List not contains object with given value: [%s => %s]', $attributeName, $value)
        );
    }

    /**
     * Set $this tokenStorage and Security.
     *
     * @param User $user
     * @param array $roles
     * @param string $providerKey
     *
     * @return void
     */
    protected function loginAsUser(User $user, array $roles = [], string $providerKey = 'secure_area'): void
    {
        if (!empty($roles)) {
            $user->setRoles($roles);
        }

        $security = self::$container->get('security.helper');
        $this->security = $security;

        try {
            $authenticationManager = self::$container->get('security.authentication.manager');
            $token = new PostAuthenticationGuardToken($user, $providerKey, $roles);

            $authenticatedToken = $authenticationManager->authenticate($token);
            $tokenStorage = self::$container->get('security.token_storage');
            $tokenStorage->setToken($authenticatedToken);

            self::$container
                ->get('event_dispatcher')
                ->dispatch(
                    new AuthenticationEvent($authenticatedToken),
                    AuthenticationEvents::AUTHENTICATION_SUCCESS
                );

            $this->tokenStorage = $tokenStorage;
        } catch (Exception $exception) {
        }

        $this->assertNotNull($this->security, 'Unable to set Security (loginAsUser)');
        $this->assertNotNull($this->tokenStorage, 'Unable to set Token Storage (loginAsUser)');
    }

    /**
     * Returns current logged user.
     *
     * @return User|null
     */
    protected function userMe(): ?User
    {
        return $this
            ->tokenStorage
            ->getToken()
            ->getUser()
        ;
    }

    /**
     * Assert object's all logs by fetch them via api.
     * It calls assertLog method in loop.
     *
     * @param string $apiUrl
     * @param Proxy|LoggableEntityInterface $beforeChangeObject
     *
     * @return void
     * @throws AnnotationException
     * @throws NotFoundReferencedUserException
     * @throws ReflectionException
     */
    protected function assertApiLogsSaving(string $apiUrl, $beforeChangeObject): void
    {
        $response = $this->getActionResponse(Request::METHOD_GET, $apiUrl);

        $logsJson = json_decode($response->getContent(), false);
        $this->assertNotNull($logsJson);

        $logsArray = $logsJson->{'hydra:member'};
        foreach ($logsArray as $log) {
            $this->assertLog($log, $beforeChangeObject);
        }
    }

    /**
     * Checks the correctness of single log, relative
     * to the object before the change.
     *
     * @param StdClass $log
     * @param $beforeChangeEntity
     *
     * @return void
     * @throws AnnotationException
     * @throws ReflectionException
     */
    protected function assertLog(StdClass $log, $beforeChangeEntity): void
    {
        $className = get_class($beforeChangeEntity);
        if ($beforeChangeEntity instanceof Proxy) {
            $className = ClassUtil::getRealClass(get_class($beforeChangeEntity));
        }
        $propertiesToLog = EntityLogAnnotationReader::getPropertiesToLog($className);
        $afterChangeEntity = $this
            ->entityManager
            ->getRepository($className)
            ->findOneById($beforeChangeEntity->getId())
        ;

        $triggerElement = $log->triggerElement;
        $logMessageFormat = $propertiesToLog[$triggerElement];
        $getter = 'get' . ucfirst($triggerElement);
        $oldValue = $beforeChangeEntity->$getter();
        $newValue = $afterChangeEntity->$getter();

        if (!$newValue || !$oldValue) {
            return;
        }

        $this->assertEquals(
            sprintf($logMessageFormat['message'], $oldValue, $newValue),
            $log->notice
        );
    }
}

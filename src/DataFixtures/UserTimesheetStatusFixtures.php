<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserTimesheetStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class UserTimesheetStatusFixtures
 */
class UserTimesheetStatusFixtures extends Fixture
{
    /**
     * @var string
     */
    public const REF_STATUS_OWNER_EDIT = 'TIMESHEET-STATUS-OWNER-EDIT';

    /**
     * @var string
     */
    public const REF_STATUS_OWNER_ACCEPT = 'TIMESHEET-STATUS-OWNER-ACCEPT';

    /**
     * @var string
     */
    public const REF_STATUS_MANAGER_ACCEPT = 'TIMESHEET-STATUS-MANAGER-ACCEPT';

    /**
     * @var string
     */
    public const REF_STATUS_HR_ACCEPT = 'TIMESHEET-STATUS-HR-ACCEPT';


    /**
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $statuses = [
            self::REF_STATUS_OWNER_EDIT => [
                'title' => 'Edytowana przez pracownika',
                'rules' => [
                    'ROLE_USER' => [
                        self::REF_STATUS_OWNER_ACCEPT,
                    ],
                    'ROLE_HR' => [
                        self::REF_STATUS_OWNER_ACCEPT,
                        self::REF_STATUS_MANAGER_ACCEPT,
                        self::REF_STATUS_HR_ACCEPT,
                    ],
                ]
            ],
            self::REF_STATUS_OWNER_ACCEPT => [
                'title' => 'Zatwierdzona przez pracownika',
                'rules' => [
                    'ROLE_DEPARTMENT_MANAGER' => [
                        self::REF_STATUS_OWNER_EDIT,
                        self::REF_STATUS_MANAGER_ACCEPT,
                    ],
                    'ROLE_HR' => [
                        self::REF_STATUS_OWNER_EDIT,
                        self::REF_STATUS_MANAGER_ACCEPT,
                        self::REF_STATUS_HR_ACCEPT
                    ],
                ]
            ],
            self::REF_STATUS_MANAGER_ACCEPT => [
                'title' => 'Zatwierdzona przez przełożonego',
                'rules' => [
                    'ROLE_HR' => [
                        self::REF_STATUS_OWNER_EDIT,
                        self::REF_STATUS_OWNER_ACCEPT,
                        self::REF_STATUS_MANAGER_ACCEPT,
                        self::REF_STATUS_HR_ACCEPT,
                    ],
                ],
            ],
            self::REF_STATUS_HR_ACCEPT => [
                'title' => 'Zatwierdzona przez HR',
                'rules' => [
                    'ROLE_HR' => [
                        self::REF_STATUS_OWNER_EDIT,
                        self::REF_STATUS_OWNER_ACCEPT,
                        self::REF_STATUS_MANAGER_ACCEPT,
                    ],
                ],
            ]
        ];

        foreach ($statuses as $key => $value) {
            $userTimesheetStatus = new UserTimesheetStatus();
            $userTimesheetStatus
                ->setId($key)
                ->setName($value['title'])
                ->setRules(json_encode($value['rules']))
            ;

            $manager->persist($userTimesheetStatus);

            $this->setReference($key, $userTimesheetStatus);
        }

        $manager->flush();
    }
}

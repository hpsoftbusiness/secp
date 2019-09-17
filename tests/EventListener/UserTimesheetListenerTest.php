<?php

namespace App\Tests\EventSubscriber;

use App\Entity\UserTimesheet;
use App\Entity\UserTimesheetLog;
use App\Tests\AbstractWebTestCase;

class UserTimesheetListenerTest extends AbstractWebTestCase
{
    private const SAMPLE_STATUS_BEFORE = 0;
    private const SAMPLE_STATUS_AFTER = 3;
    private const SAMPLE_ID = 1;

    /**
     * @test
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function firePreUpdateOnUserTimesheetTest(): void
    {
        $userTimesheet = $this->entityManager
            ->getRepository(UserTimesheet::class)
            ->findOneBy(
                array('id' => self::SAMPLE_ID)
            );

        $status = $userTimesheet->getStatus();
        $userTimesheet->setStatus(self::SAMPLE_STATUS_AFTER);
        $this->entityManager->flush();

        $statusChanged = $userTimesheet->getStatus();
        $userTimesheetLog = $this->entityManager
            ->getRepository(UserTimesheetLog::class)
            ->findOneBy(
                [],
                ['id' => 'desc']
            );
        $notice = $userTimesheetLog->getNotice();
        $this->assertStringContainsString('Zmieniono status z: ' . self::SAMPLE_STATUS_BEFORE .' na: ' .
            self::SAMPLE_STATUS_AFTER, $notice);
        $this->assertNotEquals($status, $statusChanged);
    }
}

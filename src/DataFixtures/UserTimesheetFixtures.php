<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AbsenceType;
use App\Entity\PresenceType;
use App\Entity\User;
use App\Entity\UserTimesheet;
use App\Entity\UserTimesheetDay;
use App\Entity\UserTimesheetStatus;
use App\Entity\UserWorkSchedule;
use App\Entity\UserWorkScheduleDay;
use App\Utils\DateTimeHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;

/**
 * Class UserTimesheetFixtures
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserTimesheetFixtures extends Fixture implements DependentFixtureInterface
{
    public const REF_USER_TIMESHEET_ADMIN_EDIT = 'user_timesheet_admin_edit';
    public const REF_USER_TIMESHEET_MANAGER_HR = 'user_timesheet_manager_hr';
    public const REF_USER_TIMESHEET_MANAGER_EDIT = 'user_timesheet_manager_edit';
    public const REF_USER_TIMESHEET_USER_HR = 'user_timesheet_user_hr';
    public const REF_USER_TIMESHEET_USER_EDIT = 'user_timesheet_user_edit';
    public const REF_USER_FILLED_DAYS_TIMESHEET_USER_EDIT = 'user_timesheet_filled_days_user_edit';

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return array(
            AbsenceTypeFixtures::class,
            PresenceTypeFixtures::class,
            UserFixtures::class,
            DayDefinitionFixtures::class,
            WorkScheduleProfileFixtures::class,
            UserWorkScheduleFixtures::class,
            UserWorkScheduleStatusFixtures::class,
            UserTimesheetStatusFixtures::class
        );
    }
    /**
     * @param ObjectManager $manager
     *
     * @return void
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_FILLED_DAYS_TIMESHEET_USER_EDIT,
            $this->getReference(UserFixtures::REF_USER_USER),
            date('Y-m'),
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_OWNER_EDIT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_ADMIN_HR),
            DateTimeHelper::getMonthDaysForCurrentYear((int) date('m'))
        );

        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_TIMESHEET_ADMIN_EDIT,
            $this->getReference(UserFixtures::REF_USER_ADMIN),
            '2019-06',
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_OWNER_EDIT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_ADMIN_HR),
            ['2019-06-03', '2019-06-04', '2019-06-05', '2019-06-06', '2019-06-07']
        );

        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_TIMESHEET_MANAGER_HR,
            $this->getReference('user_manager'),
            '2019-05',
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_HR_ACCEPT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_MANAGER_HR),
            ['2019-05-27', '2019-05-28', '2019-05-29', '2019-05-30', '2019-05-31']
        );

        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_TIMESHEET_MANAGER_EDIT,
            $this->getReference(UserFixtures::REF_USER_MANAGER),
            '2019-06',
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_OWNER_EDIT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_MANAGER_HR),
            ['2019-06-03', '2019-06-04', '2019-06-05', '2019-06-06', '2019-06-07']
        );

        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_TIMESHEET_USER_HR,
            $this->getReference(UserFixtures::REF_USER_USER),
            '2019-05',
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_HR_ACCEPT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_USER_HR),
            ['2019-05-27', '2019-05-28', '2019-05-29', '2019-05-30', '2019-05-31']
        );

        $this->makeUserTimesheetSets(
            $manager,
            self::REF_USER_TIMESHEET_USER_EDIT,
            $this->getReference(UserFixtures::REF_USER_USER),
            '2019-06',
            $this->getReference(UserTimesheetStatusFixtures::REF_STATUS_OWNER_EDIT),
            $this->getReference(UserWorkScheduleFixtures::REF_FIXED_USER_WORK_SCHEDULE_USER_HR),
            ['2019-06-03', '2019-06-04', '2019-06-05', '2019-06-06', '2019-06-07']
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $referenceName
     * @param User $owner
     * @param string $period
     * @param UserTimesheetStatus $status
     * @param UserWorkSchedule $userWorkSchedule
     * @param array $workingDays
     *
     * @return void
     *
     * @throws Exception
     */
    private function makeUserTimesheetSets(
        ObjectManager $manager,
        string $referenceName,
        User $owner,
        string $period,
        UserTimesheetStatus $status,
        UserWorkSchedule $userWorkSchedule,
        array $workingDays
    ): void {
        $userTimesheet = $this->makeUserTimesheet(
            $manager,
            $referenceName,
            $owner,
            $period,
            $status
        );

        foreach ($workingDays as $workingDay) {
            $userWorkScheduleDay = $manager->getRepository(UserWorkScheduleDay::class)
                ->findOneBy(
                    [
                        'userWorkSchedule' => $userWorkSchedule->getId(),
                        'dayDefinition' => $workingDay
                    ]
                );

            if ($userWorkScheduleDay !== null) {
                $presenceType = $this->getReference(
                    'presence_type_' . random_int(0, PresenceTypeFixtures::FIXTURES_RECORD_COUNT - 1)
                );
                $absenceType = null;
                $dayStartTime = '08:30';
                $dayEndTime = '16:30';
                $workingTime = 8.00;

                if ($presenceType->getShortName() === 'N') {
                    $absenceType = $this->getReference(
                        'absence_type_' . random_int(0, AbsenceTypeFixtures::FIXTURES_RECORD_COUNT - 1)
                    );
                    $dayStartTime = null;
                    $dayEndTime = null;
                    $workingTime = 0.00;
                }

                $this->makeUserWorkScheduleDay(
                    $manager,
                    $userTimesheet,
                    $userWorkScheduleDay,
                    $dayStartTime,
                    $dayEndTime,
                    $workingTime,
                    $presenceType,
                    $absenceType
                );
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $referenceName
     * @param User $owner
     * @param string $period
     * @param UserTimesheetStatus $status
     *
     * @return UserTimesheet
     */
    private function makeUserTimesheet(
        ObjectManager $manager,
        string $referenceName,
        User $owner,
        string $period,
        UserTimesheetStatus $status
    ): UserTimesheet {
        $userTimesheet = new UserTimesheet();
        $userTimesheet->setOwner($owner)
            ->setPeriod($period)
            ->setStatus($status);

        $manager->persist($userTimesheet);
        $this->addReference($referenceName, $userTimesheet);
        return $userTimesheet;
    }

    /**
     * @param ObjectManager $manager
     * @param UserTimesheet $userTimesheet
     * @param UserWorkScheduleDay $userWorkScheduleDay
     * @param string|null $dayStartTime
     * @param string|null $dayEndTime
     * @param float $workingTime
     * @param PresenceType $presenceType
     * @param AbsenceType $absenceType
     *
     * @return UserTimesheetDay
     */
    private function makeUserWorkScheduleDay(
        ObjectManager $manager,
        UserTimesheet $userTimesheet,
        UserWorkScheduleDay $userWorkScheduleDay,
        ?string $dayStartTime,
        ?string $dayEndTime,
        float $workingTime,
        PresenceType $presenceType,
        ?AbsenceType $absenceType
    ): UserTimesheetDay {
        $userTimesheetDay = new UserTimesheetDay();
        $userTimesheetDay->setUserTimesheet($userTimesheet)
            ->setUserWorkScheduleDay($userWorkScheduleDay)
            ->setDayStartTime($dayStartTime)
            ->setDayEndTime($dayEndTime)
            ->setWorkingTime($workingTime)
            ->setPresenceType($presenceType)
            ->setAbsenceType($absenceType);

        $userTimesheet->addUserTimesheetDay($userTimesheetDay);
        $manager->persist($userTimesheetDay);
        return $userTimesheetDay;
    }
}

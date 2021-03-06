<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Annotations\AnnotatedLogEntity;
use App\Entity\Types\LoggableEntityInterface;
use App\Entity\Utils\UserAware;
use App\Traits\LoggableEntityTrait;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use App\Validator\ValueExists;

/**
 * @ORM\Table(
 *     name="`user_work_schedules`",
 *     indexes={
 *          @ORM\Index(name="idx_user_work_schedules_status", columns={"status_id"}),
 *          @ORM\Index(name="idx_user_work_schedules_from_date", columns={"from_date"}),
 *          @ORM\Index(name="idx_user_work_schedules_to_date", columns={"to_date"}),
 *          @ORM\Index(name="idx_user_work_schedules_owner_id", columns={"owner_id"}),
 *          @ORM\Index(name="idx_user_work_schedules_work_schedule_profile_id", columns={"work_schedule_profile_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserWorkScheduleRepository")
 * @UserAware(userFieldName="owner_id")
 * @ApiResource(
 *      itemOperations={
 *          "get"={
 *              "normalization_context"={
 *                  "groups"={
 *                      "get"
 *                  }
 *              }
 *          },
 *          "put"={
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              },
 *              "denormalization_context"={
 *                  "groups"={"put"}
 *              }
 *          },
 *      },
 *      collectionOperations={
 *          "get"={
 *              "normalization_context"={
 *                  "groups"={
 *                      "get",
 *                      "UserWorkSchedule-get-user-with-section-department"
 *                  }
 *              }
 *          },
 *          "post"={
 *              "denormalization_context"={
 *                  "groups"={"post"}
 *              },
 *              "normalization_context"={
 *                  "groups"={"get"}
 *              }
 *          }
 *      },
 *      normalizationContext={
 *          "groups"={
 *              "get"
 *          }
 *      }
 * )
 *
 * @ApiFilter(
 *      SearchFilter::class,
 *      properties={
 *          "workScheduleProfile.id": "exact",
 *          "owner.firstName": "istart",
 *          "owner.lastName": "istart",
 *          "owner.department.id": "exact",
 *          "owner.section.id": "exact",
 *          "status.id": "exact"
 *      }
 * )
 *
 * @ApiFilter(
 *      DateFilter::class,
 *      properties={
 *          "fromDate",
 *          "toDate"
 *      }
 * )
 * @AnnotatedLogEntity(UserWorkScheduleLog::class)
 */
class UserWorkSchedule implements LoggableEntityInterface
{
    use LoggableEntityTrait;

    /**
     * @var string
     */
    public const STATUS_HR_ACCEPT = 'WORK-SCHEDULE-STATUS-HR-ACCEPT';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"get"})
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\Type(type="DateTimeInterface")
     * @Groups({"get", "post"})
     */
    private $fromDate;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\Type(type="DateTimeInterface")
     * @Groups({"get", "post"})
     */
    private $toDate;

    /**
     * @Assert\NotBlank()
     * @ValueExists(entity="App\Entity\UserWorkScheduleStatus", searchField="id")
     * @ORM\ManyToOne(targetEntity="App\Entity\UserWorkScheduleStatus", fetch="EAGER")
     * @Groups({"get", "post", "put"})
     * @AnnotatedLogEntity(options={
     *      "message": "Zmiana statusu z %s na %s"
     * })

     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"get", "post"})
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\WorkScheduleProfile")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"get", "post"})
     * @AnnotatedLogEntity(options={
     *      "message": "Zmiana profilu z %s na %s"
     * })
     */
    private $workScheduleProfile;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserWorkScheduleDay", mappedBy="userWorkSchedule", orphanRemoval=true)
     */
    private $userWorkScheduleDays;

    /**
     * UserWorkSchedule constructor.
     */
    public function __construct()
    {
        $this->userWorkScheduleDays = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getFromDate(): ?DateTimeInterface
    {
        return $this->fromDate;
    }

    /**
     * @param DateTimeInterface $fromDate
     *
     * @return UserWorkSchedule
     */
    public function setFromDate(DateTimeInterface $fromDate): UserWorkSchedule
    {
        $this->fromDate = $fromDate;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getToDate(): ?DateTimeInterface
    {
        return $this->toDate;
    }

    /**
     * @param DateTimeInterface $toDate
     *
     * @return UserWorkSchedule
     */
    public function setToDate(DateTimeInterface $toDate): UserWorkSchedule
    {
        $this->toDate = $toDate;

        return $this;
    }

    /**
     * @return UserWorkScheduleStatus|null
     */
    public function getStatus(): ?UserWorkScheduleStatus
    {
        return $this->status;
    }

    /**
     * @param UserWorkScheduleStatus $status
     *
     * @return UserWorkSchedule
     */
    public function setStatus(UserWorkScheduleStatus $status): UserWorkSchedule
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     *
     * @return UserWorkSchedule
     */
    public function setOwner(?User $owner): UserWorkSchedule
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return WorkScheduleProfile|null
     */
    public function getWorkScheduleProfile(): ?WorkScheduleProfile
    {
        return $this->workScheduleProfile;
    }

    /**
     * @param WorkScheduleProfile|null $workScheduleProfile
     *
     * @return UserWorkSchedule
     */
    public function setWorkScheduleProfile(?WorkScheduleProfile $workScheduleProfile): UserWorkSchedule
    {
        $this->workScheduleProfile = $workScheduleProfile;

        return $this;
    }

    /**
     * @return Collection|UserWorkScheduleDay[]
     */
    public function getUserWorkScheduleDays(): Collection
    {
        return $this->userWorkScheduleDays;
    }

    /**
     * @param UserWorkScheduleDay $userWorkScheduleDay
     *
     * @return UserWorkSchedule
     */
    public function addUserWorkScheduleDay(UserWorkScheduleDay $userWorkScheduleDay): UserWorkSchedule
    {
        if (!$this->userWorkScheduleDays->contains($userWorkScheduleDay)) {
            $this->userWorkScheduleDays[] = $userWorkScheduleDay;
            $userWorkScheduleDay->setUserWorkSchedule($this);
        }

        return $this;
    }

    /**
     * @param UserWorkScheduleDay $userWorkScheduleDay
     *
     * @return UserWorkSchedule
     */
    public function removeUserWorkScheduleDay(UserWorkScheduleDay $userWorkScheduleDay): UserWorkSchedule
    {
        if ($this->userWorkScheduleDays->contains($userWorkScheduleDay)) {
            $this->userWorkScheduleDays->removeElement($userWorkScheduleDay);
            // set the owning side to null (unless already changed)
            if ($userWorkScheduleDay->getUserWorkSchedule() === $this) {
                $userWorkScheduleDay->setUserWorkSchedule(null);
            }
        }

        return $this;
    }
}

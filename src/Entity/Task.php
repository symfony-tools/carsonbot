<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A "Task" is a scheduled job.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table]
class Task
{
    public const ACTION_CLOSE_STALE = 1;
    public const ACTION_CLOSE_DRAFT = 2;
    public const ACTION_INFORM_CLOSE_STALE = 3;

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private $updatedAt;

    public function __construct(
        #[ORM\Column(type: 'string')]
        private readonly string $repositoryFullName,
        #[ORM\Column(type: 'integer')]
        private readonly int $number,
        #[ORM\Column(type: 'integer')]
        private readonly int $action,
        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $verifyAfter)
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRepositoryFullName(): string
    {
        return $this->repositoryFullName;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getAction(): int
    {
        return $this->action;
    }

    public function getVerifyAfter(): \DateTimeImmutable
    {
        return $this->verifyAfter;
    }

    public function setVerifyAfter(\DateTimeImmutable $verifyAfter): void
    {
        $this->verifyAfter = $verifyAfter;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateUpdatedAt()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

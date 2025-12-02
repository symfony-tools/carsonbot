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
    public const int ACTION_CLOSE_STALE = 1;
    public const int ACTION_CLOSE_DRAFT = 2;
    public const int ACTION_INFORM_CLOSE_STALE = 3;

    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column]
    private string $repositoryFullName;

    #[ORM\Column]
    private int $number;

    #[ORM\Column]
    private int $action;

    #[ORM\Column]
    private \DateTimeImmutable $verifyAfter;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $repositoryFullName,
        int $number,
        int $action,
        \DateTimeImmutable $verifyAfter,
    ) {
        $this->repositoryFullName = $repositoryFullName;
        $this->number = $number;
        $this->action = $action;
        $this->verifyAfter = $verifyAfter;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
    public function updateUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A "Task" is a scheduled job.
 *
 * @ORM\Table
 *
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 *
 * @ORM\HasLifecycleCallbacks()
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Task
{
    public const ACTION_CLOSE_STALE = 1;
    public const ACTION_CLOSE_DRAFT = 2;
    public const ACTION_INFORM_CLOSE_STALE = 3;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $repositoryFullName;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $number;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $action;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private $verifyAfter;

    public function __construct(string $repositoryFullName, int $number, int $action, \DateTimeImmutable $verifyAfter)
    {
        $this->repositoryFullName = $repositoryFullName;
        $this->number = $number;
        $this->action = $action;
        $this->verifyAfter = $verifyAfter;
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

    /**
     * @ORM\PrePersist
     *
     * @ORM\PreUpdate
     */
    public function updateUpdatedAt()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

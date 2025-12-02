<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function getTasksToVerify(int $limit): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.verifyAfter <= :now')
            ->orderBy('t.updatedAt', 'ASC') // Yes, sort this the wrong way.
            ->setMaxResults($limit)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    final public function persist(Task $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    final public function remove(Task $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    final public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}

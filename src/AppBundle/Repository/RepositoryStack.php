<?php

namespace AppBundle\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class RepositoryStack
{
    /**
     * @var Repository[]
     */
    private $repositories = array();

    public function push(Repository $repository)
    {
        $this->repositories[] = $repository;
    }

    /**
     * @return Repository
     *
     * @throws RuntimeException if the stack is empty.
     */
    public function pop()
    {
        if (empty($this->repositories)) {
            throw new \RuntimeException('The RepositoryStack is empty.');
        }

        return array_pop($this->repositories);
    }

    /**
     * @return Repository
     *
     * @throws RuntimeException when there is no current repository.
     */
    public function getCurrentRepository()
    {
        $repository = end($this->repositories);
        if (false === $repository) {
            throw new \RuntimeException('No current repository.');
        }

        return $repository;
    }
}

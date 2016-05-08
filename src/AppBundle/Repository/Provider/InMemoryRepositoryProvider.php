<?php

namespace AppBundle\Repository\Provider;

use AppBundle\Repository\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class InMemoryRepositoryProvider implements RepositoryProviderInterface
{
    private $repositories = array();

    public function __construct(array $repositories)
    {
        foreach ($repositories as $data) {
            $repository = new Repository($data['vendor'], $data['name']);
            if (isset($data['secret'])) {
                $repository->setSecret($data['secret']);
            }

            $this->addRepository($repository);
        }
    }

    /**
     * @param string $vendor
     * @param string $name
     *
     * @return Repository
     *
     * @throws \RuntimeException if the repository is not registered.
     */
    public function getRepository($vendor, $name)
    {
        if (!isset($this->repositories[$fullName = strtolower($vendor.'/'.$name)])) {
            throw new \RuntimeException(sprintf('Repository "%s" not registered', $fullName));
        }

        return $this->repositories[$fullName];
    }

    public function addRepository(Repository $repository)
    {
        $this->repositories[strtolower($repository->getVendor().'/'.$repository->getName())] = $repository;
    }
}

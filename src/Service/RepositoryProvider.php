<?php

namespace App\Service;

use App\Model\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class RepositoryProvider
{
    private $repositories = [];

    public function __construct(array $repositories)
    {
        foreach ($repositories as $repositoryFullName => $repositoryData) {
            if (1 !== substr_count($repositoryFullName, '/')) {
                throw new \InvalidArgumentException(sprintf('The repository name %s is invalid: it must be the form "username/repo_name"', $repositoryFullName));
            }

            list($vendorName, $repositoryName) = explode('/', $repositoryFullName);

            $this->addRepository(new Repository(
                $vendorName,
                $repositoryName,
                $repositoryData['secret'] ?? null
            ));
        }
    }

    public function getRepository(string $repositoryName): ?Repository
    {
        $repository = strtolower($repositoryName);

        return $this->repositories[$repository] ?? null;
    }

    /**
     * @return Repository[]
     */
    public function getAllRepositories(): array
    {
        return array_values($this->repositories);
    }

    private function addRepository(Repository $repository)
    {
        $this->repositories[strtolower($repository->getVendor().'/'.$repository->getName())] = $repository;
    }
}

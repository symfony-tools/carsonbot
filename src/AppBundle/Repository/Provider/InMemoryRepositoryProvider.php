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
        foreach ($repositories as $repositoryFullName => $repositoryData) {
            if (1 !== substr_count($repositoryFullName, '/')) {
                throw new \InvalidArgumentException(sprintf('The repository name %s is invalid: it must be the form "username/repo_name"', $repositoryFullName));
            }

            list($vendorName, $repositoryName) = explode('/', $repositoryFullName);

            if (!isset($repositoryData['subscribers'])) {
                throw new \InvalidArgumentException(sprintf('The repository %s is missing a subscribers key!', $repositoryFullName));
            }

            if (empty($repositoryData['subscribers'])) {
                throw new \InvalidArgumentException(sprintf('The repository "%s" has no subscribers configured.', $repositoryFullName));
            }

            $secret = isset($repositoryData['secret']) ? $repositoryData['secret'] : null;

            $this->addRepository(new Repository(
                $vendorName,
                $repositoryName,
                $repositoryData['subscribers'],
                $secret
            ));
        }
    }

    public function getRepository($repositoryName)
    {
        return isset($this->repositories[$repositoryName]) ? $this->repositories[$repositoryName] : null;
    }

    private function addRepository(Repository $repository)
    {
        $this->repositories[strtolower($repository->getVendor().'/'.$repository->getName())] = $repository;
    }
}

<?php

namespace AppBundle\Repository\Provider;

use AppBundle\Repository\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
interface RepositoryProviderInterface
{
    /**
     * @param string $repositoryName e.g. symfony/symfony-docs
     *
     * @return Repository
     */
    public function getRepository($repositoryName);

    /**
     * @return Repository[]
     */
    public function getAllRepositories();
}

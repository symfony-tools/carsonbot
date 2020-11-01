<?php

namespace App\Repository\Provider;

use App\Repository\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
interface RepositoryProviderInterface
{
    /**
     * @param string $repositoryName e.g. symfony/symfony-docs
     *
     * @return Repository|null
     */
    public function getRepository($repositoryName);

    /**
     * @return Repository[]
     */
    public function getAllRepositories();
}

<?php

namespace AppBundle\Repository\Provider;

use AppBundle\Repository\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
interface RepositoryProviderInterface
{
    /**
     * @param string $vendor
     * @param string $name
     *
     * @return Repository
     */
    public function getRepository($vendor, $name);
}

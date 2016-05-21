<?php

namespace AppBundle\Repository;

/**
 * @author Ener-Getick <egetick@gmail.com>
 */
class Repository
{
    /**
     * @var string
     */
    private $vendor;

    /**
     * @var string
     */
    private $name;

    /**
     * An array of subscriber service ids
     *
     * @var array
     */
    private $subscribers;

    public function __construct($vendor, $name, array $subscribers)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->subscribers = $subscribers;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param string $vendor
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getSubscribers()
    {
        return $this->subscribers;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        // todo - implement setting a secret
    }

    public function getMaintainers()
    {
        // todo - implement this
        return [];
    }
}

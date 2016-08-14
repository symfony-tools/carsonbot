<?php

namespace AppBundle\Repository;

use AppBundle\Issues\GitHub\GitHubStatusApi;

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
     * An array of subscriber service ids.
     *
     * @var array
     */
    private $subscribers;

    /**
     * The webhook secret used by GitHub.
     *
     * @var string
     */
    private $secret;

    public function __construct($vendor, $name, array $subscribers, $secret)
    {
        $this->vendor = $vendor;
        $this->name = $name;
        $this->subscribers = $subscribers;
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        return $this->secret;
    }

    public function getNeedsReviewUrl()
    {
        return sprintf(
            'https://github.com/%s/%s/labels/%s',
            $this->getVendor(),
            $this->getName(),
            rawurlencode(GitHubStatusApi::getNeedsReviewLabel())
        );
    }

    public function getFullName()
    {
        return sprintf('%s/%s', $this->getVendor(), $this->getName());
    }
}

<?php

namespace AppBundle\Event;

use AppBundle\Repository\Repository;
use Symfony\Component\EventDispatcher\Event;

/**
 * GitHubEvent.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
class GitHubEvent extends Event
{
    protected $responseData = array();

    private $data;
    private $repository;
    private $maintainers;

    public function __construct(array $data, Repository $repository, array $maintainers = array())
    {
        $this->data = $data;
        $this->repository = $repository;
        $this->maintainers = $maintainers;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    public function getMaintainers()
    {
        return $this->maintainers;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }

    public function setResponseData(array $responseData)
    {
        foreach ($responseData as $k => $v) {
            $this->responseData[$k] = $v;
        }
    }
}

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

    public function __construct(array $data, Repository $repository)
    {
        $this->data = $data;
        $this->repository = $repository;
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

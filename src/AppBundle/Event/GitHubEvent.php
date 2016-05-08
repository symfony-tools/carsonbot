<?php

namespace AppBundle\Event;

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

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
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

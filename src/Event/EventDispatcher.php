<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcher
{
    /**
     * @var array<string, EventDispatcherInterface>
     */
    protected array $dispatchers = [];

    public function addDispatcher(string $repositoryName, EventDispatcherInterface $dispatcher): void
    {
        $this->dispatchers[$repositoryName] = $dispatcher;
    }

    public function dispatch(GitHubEvent $event, string $eventName): void
    {
        $name = $event->getRepository()->getFullName();

        if (isset($this->dispatchers[$name])) {
            $this->dispatchers[$name]->dispatch($event, $eventName);
        }
    }
}

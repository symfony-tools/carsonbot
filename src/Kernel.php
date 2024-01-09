<?php

namespace App;

use App\Event\EventDispatcher;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function process(ContainerBuilder $container)
    {
        /** @var array $repositories */
        $repositories = $container->getParameter('repositories');
        $dispatcherCollection = $container->getDefinition(EventDispatcher::class);

        foreach ($repositories as $name => $repository) {
            $ed = new Definition(SymfonyEventDispatcher::class);
            foreach ($repository['subscribers'] as $subscriber) {
                $ed->addMethodCall('addSubscriber', [new Reference($subscriber)]);
            }
            $dispatcherId = 'event_dispatcher.github.'.$name;
            $container->setDefinition($dispatcherId, $ed);
            $dispatcherCollection->addMethodCall('addDispatcher', [$name, new Reference($dispatcherId)]);
        }
    }
}

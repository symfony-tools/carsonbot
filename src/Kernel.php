<?php

namespace App;

use App\Event\EventDispatcher;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        if (file_exists(\dirname(__DIR__).'/config/services.yaml')) {
            $container->import('../config/{services}.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        } else {
            $path = \dirname(__DIR__).'/config/services.php';
            (require $path)($container->withPath($path), $this);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (file_exists(\dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import('../config/{routes}.yaml');
        } else {
            $path = \dirname(__DIR__).'/config/routes.php';
            (require $path)($routes->withPath($path), $this);
        }
    }

    public function process(ContainerBuilder $container)
    {
        $dispatcherCollection = $container->getDefinition(EventDispatcher::class);
        foreach ($container->getParameter('repositories') as $name => $repository) {
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

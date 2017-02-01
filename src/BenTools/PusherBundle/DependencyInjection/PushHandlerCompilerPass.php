<?php

namespace BenTools\PusherBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PushHandlerCompilerPass implements CompilerPassInterface {

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container) {
        $definition = $container->findDefinition('bentools.pusher');
        $taggedServices = $container->findTaggedServiceIds('bentools.pusher.handler');

        foreach ($taggedServices AS $id => $tags) {
            $definition->addMethodCall('registerHandler', [new Reference($id)]);
        }
    }
}
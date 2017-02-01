<?php

namespace BenTools\PusherBundle;

use BenTools\PusherBundle\DependencyInjection\PushHandlerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BenToolsPusherBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container) {
        parent::build($container);
        $container->addCompilerPass(new PushHandlerCompilerPass());
    }
}

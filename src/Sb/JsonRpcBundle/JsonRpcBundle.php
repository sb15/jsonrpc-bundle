<?php

namespace Sb\JsonRpcBundle;

use Sb\JsonRpcBundle\DependencyInjection\Compiler\JsonRpcExposablePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonRpcBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new JsonRpcExposablePass());
    }
}
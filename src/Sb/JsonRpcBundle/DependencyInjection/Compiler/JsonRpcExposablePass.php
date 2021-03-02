<?php
namespace Sb\JsonRpcBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class JsonRpcExposablePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('sb_json_rpc.jsonrpccontroller');
        $services = $container->findTaggedServiceIds('sb_json_rpc.exposable');
        foreach ($services as $service => $attributes) {
            $definition->addMethodCall('addService', [new Reference($service)]);
        }
    }
}
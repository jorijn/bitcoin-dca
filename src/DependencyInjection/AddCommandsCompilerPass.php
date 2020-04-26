<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddCommandsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('bl3p-dca.cli');
        if (empty($taggedServices)) {
            return;
        }

        uasort($taggedServices, static function (array $serviceA, array $serviceB) {
            return ($serviceB[0]['priority'] ?? 0) <=> ($serviceA[0]['priority'] ?? 0);
        });

        $applicationDefinition = $container->findDefinition('dca.application');
        foreach ($taggedServices as $id => $tags) {
            $applicationDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}

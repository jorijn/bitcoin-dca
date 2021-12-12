<?php

declare(strict_types=1);

/*
 * This file is part of the Bitcoin-DCA package.
 *
 * (c) Jorijn Schrijvershof <jorijn@jorijn.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jorijn\Bitcoin\Dca\Factory;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;

class ContainerFactory
{
    public static function createContainer(): ContainerInterface
    {
        $projectDirectory = \dirname(__DIR__, 2);
        $containerCache = $projectDirectory.\DIRECTORY_SEPARATOR.'var'.\DIRECTORY_SEPARATOR.'cache'.\DIRECTORY_SEPARATOR.'container.php';
        $containerConfigCache = new ConfigCache($containerCache, isset($_SERVER['DEBUG']) && (bool) $_SERVER['DEBUG']);

        if (!$containerConfigCache->isFresh()) {
            $containerBuilder = new ContainerBuilder();
            $containerBuilder->addCompilerPass(new AddConsoleCommandPass());
            $containerBuilder->addCompilerPass(new RegisterListenersPass());
            $containerBuilder->addCompilerPass(new SerializerPass());
            $containerBuilder->setParameter('application.path', $projectDirectory);

            // load the DI config
            $loader = new YamlFileLoader($containerBuilder, new FileLocator($projectDirectory.\DIRECTORY_SEPARATOR.'config'));
            $loader->load('services.yaml');

            try {
                $versionFile = $projectDirectory.\DIRECTORY_SEPARATOR.'version.json';
                if (file_exists($versionFile)) {
                    $version = json_decode(file_get_contents($versionFile), true, 512, JSON_THROW_ON_ERROR);
                    if (isset($version['version'])) {
                        $containerBuilder->setParameter('application_version', $version['version']);
                    }
                }
            } catch (\JsonException) {
                // this should not happen because the JSON would be corrupt, but since
                // version information is optional lets not make a big deal of it.
            }

            $containerBuilder->compile();

            // write the compiled container to file
            $dumper = new PhpDumper($containerBuilder);
            $containerConfigCache->write(
                $dumper->dump(['class' => 'BitcoinDcaContainer']),
                $containerBuilder->getResources()
            );
        }

        require_once $containerCache;

        return new \BitcoinDcaContainer();
    }
}

<?php

namespace Arachne\Twig\DI;

use Arachne\ServiceCollections\DI\ServiceCollectionsExtension;
use Arachne\Twig\RuntimeLoader;
use Nette\DI\CompilerExtension;
use Nette\Utils\AssertionException;
use Twig_Environment;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Yep\TracyTwigExtensions\BarDumpExtension;
use Yep\TracyTwigExtensions\DumpExtension;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class TwigExtension extends CompilerExtension
{
    /**
     * Twig extensions with this tag are registered to the Twig_Environment service.
     */
    const TAG_EXTENSION = 'arachne.twig.extension';

    /**
     * Twig loaders with this tag are added to the Twig_Loader_Chain service.
     */
    const TAG_LOADER = 'arachne.twig.loader';

    /**
     * Twig runtimes with this tag are registered to the Twig_Environment service.
     */
    const TAG_RUNTIME = 'arachne.twig.runtime';

    /**
     * @var array
     */
    public $defaults = [
        'options' => [
            'strict_variables' => true,
        ],
        'paths' => [],
        'dumpOptions' => [],
    ];

    public function __construct($tempDir, $debugMode = false)
    {
        $this->defaults['options']['cache'] = $tempDir;
        $this->defaults['options']['debug'] = $debugMode;
    }

    /**
     * @param string[] $paths
     * @param string   $namespace
     */
    public function addPaths(array $paths, $namespace = null)
    {
        $builder = $this->getContainerBuilder();
        $serviceName = $this->prefix('loader.fileSystem');

        if (!$builder->hasDefinition($serviceName)) {
            $builder->addDefinition($serviceName)
                ->setClass(Twig_Loader_Filesystem::class)
                ->addTag(self::TAG_LOADER)
                ->setAutowired(false);
        }

        $loader = $builder->getDefinition($serviceName);
        foreach ($paths as $path) {
            if ($namespace) {
                $loader->addSetup('?->addPath(?, ?)', ['@self', $path, $namespace]);
            } else {
                $loader->addSetup('?->addPath(?)', ['@self', $path]);
            }
        }
    }

    public function loadConfiguration()
    {
        $this->validateConfig($this->defaults);

        /* @var $serviceCollectionsExtension ServiceCollectionsExtension */
        $serviceCollectionsExtension = $this->getExtension(ServiceCollectionsExtension::class);

        $runtimeResolver = $serviceCollectionsExtension->getCollection(
            ServiceCollectionsExtension::TYPE_RESOLVER,
            self::TAG_RUNTIME
        );

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('runtimeLoader'))
            ->setClass(RuntimeLoader::class)
            ->setArguments(
                [
                    'resolver' => '@'.$runtimeResolver,
                ]
            );

        $builder->addDefinition($this->prefix('environment'))
            ->setClass(Twig_Environment::class)
            ->setArguments(
                [
                    'options' => $this->config['options'],
                ]
            )
            ->addSetup('addRuntimeLoader', [$this->prefix('@runtimeLoader')]);

        $builder->addDefinition($this->prefix('loader'))
            ->setClass(Twig_Loader_Chain::class);

        if (class_exists(DumpExtension::class)) {
            $builder->addDefinition($this->prefix('extension.tracy.dump'))
                ->setClass(DumpExtension::class)
                ->setArguments(
                    [
                        'options' => $this->config['dumpOptions'],
                    ]
                )
                ->addTag(self::TAG_EXTENSION)
                ->setAutowired(false);
        }

        if (class_exists(BarDumpExtension::class)) {
            $builder->addDefinition($this->prefix('extension.tracy.barDump'))
                ->setClass(BarDumpExtension::class)
                ->setArguments(
                    [
                        'options' => $this->config['dumpOptions'],
                    ]
                )
                ->addTag(self::TAG_EXTENSION)
                ->setAutowired(false);
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->config['paths'] as $namespace => $paths) {
            $this->addPaths((array) $paths, is_string($namespace) ? $namespace : null);
        }

        $builder->getDefinition($this->prefix('loader'))
            ->setArguments(
                [
                    'loaders' => array_map(
                        function ($service) {
                            return '@'.$service;
                        },
                        array_keys($builder->findByTag(self::TAG_LOADER))
                    ),
                ]
            );

        $environment = $builder->getDefinition($this->prefix('environment'));
        foreach ($builder->findByTag(self::TAG_EXTENSION) as $service => $attributes) {
            $environment->addSetup('addExtension', ['@'.$service]);
        }
    }

    /**
     * @param string $class
     *
     * @return CompilerExtension
     */
    private function getExtension($class)
    {
        $extensions = $this->compiler->getExtensions($class);

        if (!$extensions) {
            throw new AssertionException(
                sprintf('Extension "%s" requires "%s" to be installed.', get_class($this), $class)
            );
        }

        return reset($extensions);
    }
}

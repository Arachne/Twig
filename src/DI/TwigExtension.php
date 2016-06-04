<?php

/**
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Arachne\Twig\DI;

use Nette\DI\CompilerExtension;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class TwigExtension extends CompilerExtension
{
    /**
     * Twig Extensions with this tag are registered to the Twig_Environment service.
     */
    const TAG_EXTENSION = 'arachne.twig.extension';

    /**
     * Twig loaders with this tag are added to the Twig_Loader_Chain service.
     */
    const TAG_LOADER = 'arachne.twig.loader';

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
     * @param string $namespace
     */
    public function addPaths(array $paths, $namespace = null)
    {
        $builder = $this->getContainerBuilder();
        $serviceName = $this->prefix('loader.fileSystem');

        if (!$builder->hasDefinition($serviceName)) {
            $builder->addDefinition($serviceName)
                ->setClass('Twig_Loader_Filesystem')
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
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('environment'))
            ->setClass('Twig_Environment')
            ->setArguments([
                'options' => $this->config['options'],
            ]);

        $builder->addDefinition($this->prefix('loader'))
            ->setClass('Twig_Loader_Chain');

        if (class_exists('Yep\TracyTwigExtensions\DumpExtension')) {
            $builder->addDefinition($this->prefix('extension.tracy.dump'))
                ->setClass('Yep\TracyTwigExtensions\DumpExtension')
                ->setArguments([
                    'options' => $this->config['dumpOptions'],
                ])
                ->addTag(self::TAG_EXTENSION)
                ->setAutowired(false);
        }

        if (class_exists('Yep\TracyTwigExtensions\BarDumpExtension')) {
            $builder->addDefinition($this->prefix('extension.tracy.barDump'))
                ->setClass('Yep\TracyTwigExtensions\BarDumpExtension')
                ->setArguments([
                    'options' => $this->config['dumpOptions'],
                ])
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
            ->setArguments([
                'loaders' => array_map(function ($service) {
                    return '@' . $service;
                }, array_keys($builder->findByTag(self::TAG_LOADER))),
            ]);

        $environment = $builder->getDefinition($this->prefix('environment'));
        foreach ($builder->findByTag(self::TAG_EXTENSION) as $service => $attributes) {
            $environment->addSetup('?->addExtension(?)', ['@self', '@' . $service]);
        }
    }
}

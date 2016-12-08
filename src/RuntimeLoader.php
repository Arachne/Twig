<?php

/*
 * This file is part of the Arachne
 *
 * Copyright (c) Jáchym Toušek (enumag@gmail.com)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Arachne\Twig;

use Twig_RuntimeLoaderInterface;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class RuntimeLoader implements Twig_RuntimeLoaderInterface
{
    /**
     * @var callable
     */
    private $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function load($class)
    {
        return call_user_func($this->resolver, $class);
    }
}

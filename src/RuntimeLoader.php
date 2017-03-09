<?php

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

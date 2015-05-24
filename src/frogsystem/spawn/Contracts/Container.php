<?php
namespace Frogsystem\Spawn\Contracts;

use Interop\Container\ContainerInterface;

/**
 * Describes the interface of a container that exposes methods to read and write its entries
 * and implements delegated lookup.
 */
interface Container extends ContainerInterface
{
    /**
     * Binds an abstract to a value in the container.
     *
     * @param string $abstract The abstract to be bound.
     * @param mixed $value Value of the abstract.
     *
     * @param $value
     */
    public function set($abstract, $value);

    /**
     * Delegates to dependency lookup to another container.
     * @param ContainerInterface $container The delegated container.
     */
    public function delegate(ContainerInterface $container);
}

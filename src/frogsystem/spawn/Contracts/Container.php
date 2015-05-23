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
     * Sets an entry of the container by its identifier.
     *
     * @param string $id Identifier of the entry to be set.
     * @param mixed $value Value of the entry.
     *
     * @param $value
     */
    public function set($id, $value);

    /**
     * Delegates to dependency lookup to another container.
     * @param ContainerInterface $container The delegated container.
     */
    public function delegate(ContainerInterface $container);
}

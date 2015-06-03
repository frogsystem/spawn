<?php
namespace Frogsystem\Spawn\Contracts;

use Interop\Container\ContainerInterface;

/**
 * Interface ApplicationInterface
 * @package Frogsystem\Spawn\Contracts
 */
interface ApplicationInterface extends RunnableInterface, ContainerInterface
{
    /**
     * Load the application kernel.
     * @param KernelInterface $kernel
     * @return mixed
     */
    public function load(KernelInterface $kernel);

    /**
     * Connect a Pluggable to the application.
     * @param PluggableInterface $pluggable
     * @return mixed
     */
    public function connect(PluggableInterface $pluggable);
}

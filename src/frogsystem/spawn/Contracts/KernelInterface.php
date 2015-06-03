<?php
namespace Frogsystem\Spawn\Contracts;

/**
 * Interface Kernel
 * @package Frogsystem\Metamorphosis\Contracts
 */
interface KernelInterface
{
    /**
     * Get a list of all Kernel Pluggables
     * @return array
     */
    public function getPluggables();


    /**
     * Boot an application from instance or class name. Will return the booted application.
     * @param ApplicationInterface|string $app
     * @return ApplicationInterface
     */
    public function boot($app);
}

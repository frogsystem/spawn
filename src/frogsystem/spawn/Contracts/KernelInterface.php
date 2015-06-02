<?php
namespace Frogsystem\Spawn\Contracts;

/**
 * Interface Kernel
 * @package Frogsystem\Metamorphosis\Contracts
 */
interface KernelInterface
{
    /**
     * Get a list of all Kernel ServiceProviders
     * @return array
     */
    public function getServiceProviders();

    /**
     * Get a list of all Kernel Pluggables
     * @return array
     */
    public function getPluggables();


    /**
     * Boot the given Application with this Kernel.
     * @param ApplicationInterface $app
     * @return mixed
     */
    public function boot(ApplicationInterface $app);
}

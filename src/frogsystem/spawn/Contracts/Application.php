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
     * Boot a Kernel into the application.
     * @param KernelInterface $kernel
     * @return mixed
     */
    public function boot(KernelInterface $kernel);

    /**
     * Connect a Pluggable to the application.
     * @param PluggableInterface $pluggable
     * @return mixed
     */
    public function connect(PluggableInterface $pluggable);

    /**
     * Register a ServiceProvider with the application.
     * @param ServiceProviderInterface $provider
     * @return mixed
     */
    public function register(ServiceProviderInterface $provider);
}

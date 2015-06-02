<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Contracts\ApplicationInterface;
use Frogsystem\Spawn\Contracts\KernelInterface;
use Frogsystem\Spawn\Contracts\PluggableInterface;
use Frogsystem\Spawn\Contracts\ServiceProviderInterface;

/**
 * Class Application
 * @package Frogsystem\Spawn
 */
abstract class Application extends Container implements ApplicationInterface
{
    /**
     * @var array
     */
    protected $pluggables = [];

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @param KernelInterface $kernel
     * @return mixed
     */
    public function load(KernelInterface $kernel)
    {
        // Register ServiceProviders
        foreach ($kernel->getServiceProviders() as $provider) {
            $this->register($this->make($provider));
        }

        // Connect Pluggables
        foreach ($kernel->getPluggables() as $pluggable) {
            $this->connect($this->make($pluggable));
        }
    }

    /**
     * @param PluggableInterface $pluggable
     * @return mixed|void
     */
    public function connect(PluggableInterface $pluggable)
    {
        // Plug the pluggable in
        $this->pluggables[] = $pluggable;
        $pluggable->plugin();
    }

    /**
     * @param ServiceProviderInterface $provider
     * @return mixed|void
     */
    public function register(ServiceProviderInterface $provider)
    {
        // Plug the pluggable in
        $this->providers[] = $provider;
        $provider->register();
    }


    /**
     * @return mixed
     */
    abstract public function run();
}

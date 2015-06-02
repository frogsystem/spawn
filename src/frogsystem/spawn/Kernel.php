<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Contracts\ApplicationInterface;
use Frogsystem\Spawn\Contracts\KernelInterface;

/**
 * Class Kernel
 * @package Frogsystem\Spawn
 */
class Kernel implements KernelInterface
{
    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $pluggables = [];

    /**
     * Get a list of all Kernel ServiceProviders
     * @return array
     */
    public function getServiceProviders()
    {
        return $this->providers;
    }

    /**
     * Get a list of all Kernel Pluggables
     * @return array
     */
    public function getPluggables()
    {
        return $this->pluggables;
    }

    /**
     * Boot the given application with this kernel.
     * @param ApplicationInterface $app
     * @return mixed
     */
    public function boot(ApplicationInterface $app)
    {
        // Load the kernel into the application
        $app->load($this);
    }
}

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
    protected $pluggables = [];

    /**
     * Get a list of all Kernel Pluggables
     * @return array
     */
    public function getPluggables()
    {
        return $this->pluggables;
    }

    /**
     * Boot an application from instance or class name. Will return the booted application.
     * @param ApplicationInterface|string $app
     * @return ApplicationInterface
     */
    public function boot($app)
    {
        // build application
        if (is_string($app)) {
            $app = new $app;
        }

        // Load the kernel into the application
        $app->load($this);
        return $app;
    }
}

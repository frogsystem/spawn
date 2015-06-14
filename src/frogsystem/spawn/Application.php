<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Contracts\ApplicationInterface;
use Frogsystem\Spawn\Contracts\KernelInterface;
use Frogsystem\Spawn\Contracts\PluggableInterface;
use Frogsystem\Spawn\Contracts\RunnableInterface;

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
     * @param KernelInterface $kernel
     * @return mixed
     */
    public function load(KernelInterface $kernel)
    {
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
     * @return mixed
     */
    public function run() {
        // run pluggables
        foreach ($this->pluggables as $pluggable) {
            if ($pluggable instanceof RunnableInterface) {
                $pluggable->run();
            }
        }
    }
}
<?php
namespace Frogsystem\Spawn\Contracts;

/**
 * Interface PluggableInterface
 * @package Frogsystem\Spawn\Contracts
 */
interface PluggableInterface
{

    /**
     * Executed whenever a pluggable gets plugged in.
     * @return mixed
     */
    public function plugin();

    /**
     * Executed whenever a pluggable gets unplugged.
     * @return mixed
     */
    public function unplug();
}

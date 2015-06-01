<?php
namespace Frogsystem\Spawn\Contracts;

/**
 * Class ServiceProvider
 * @package Frogsystem\Spawn\Contracts
 */
interface ServiceProviderInterface
{

    /**
     * Called when the ServiceProvider is registered within the Application.
     */
    public function register();
}

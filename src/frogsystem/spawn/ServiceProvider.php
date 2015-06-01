<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Contracts\ServiceProviderInterface;
use Interop\Container\ContainerInterface;

/**
 * Class ServiceProvider
 * @package Frogsystem\Metamorphosis\Providers
 */
abstract class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @var Container The app container.
     */
    protected $app;

    /**
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     *
     */
    abstract public function register();
}

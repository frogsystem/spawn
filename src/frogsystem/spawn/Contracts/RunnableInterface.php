<?php
namespace Frogsystem\Spawn\Contracts;

/**
 * Interface RunnableInterface
 * @package Frogsystem\Spawn\Contract
 */
interface RunnableInterface {

    /**
     * Main method to run the application.
     * @return mixed
     */
    public function run();
}

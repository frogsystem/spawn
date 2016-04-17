<?php
namespace Frogsystem\Spawn\Exceptions;

use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;

/**
 * Class ContainerException
 * Base interface representing a generic exception in a container.
 * @package Frogsystem\Spawn\Exceptions
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}

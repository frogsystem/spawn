<?php
namespace Frogsystem\Spawn\Exceptions;

use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * Class NotFoundException
 * No entry was found in the container.
 * @package Frogsystem\Spawn\Exceptions
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}

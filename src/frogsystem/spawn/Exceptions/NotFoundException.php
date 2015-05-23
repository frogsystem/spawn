<?php
namespace Frogsystem\Spawn\Exceptions;

use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * No entry was found in the container.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}

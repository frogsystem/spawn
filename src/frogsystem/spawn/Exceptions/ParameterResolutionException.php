<?php
namespace Frogsystem\Spawn\Exceptions;

use Exception;

/**
 * Class ParameterResolutionException
 * Exception thrown when a container failed to resolve a parameter for dependency injection.
 * @package Frogsystem\Spawn\Exceptions
 */
class ParameterResolutionException extends ContainerException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link http://php.net/manual/en/exception.construct.php
     * @param \ReflectionFunctionAbstract $reflection The reflected method or function.
     * @param \ReflectionParameter $parameter The paramter failed to resolve.
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     * @since 5.1.0
     */
    public function __construct(\ReflectionFunctionAbstract $reflection, \ReflectionParameter $parameter, $message = null, $code = 0, Exception $previous = null)
    {
        // Default Exception message
        if (is_null($message)) {
            $message = 'Unable to resolve parameter `%1$s` for function/method `%2$s`.';
        }
        parent::__construct(
            sprintf($message, $this->getReflectionParameterName($parameter), $this->getReflectionFunctionName($reflection)),
            $code,
            $previous
        );
    }

    /**
     * Helper method to get the type of a reflection paramter
     * @param \ReflectionParameter $parameter
     * @return NULL|\ReflectionType|string
     */
    protected function getReflectionParameterName(\ReflectionParameter $parameter)
    {
        // parameter is a class
        if ($class = $parameter->getClass()) {
            return $class->getName() . ' \$' . $parameter->getName();
        }

        return $parameter->getName();
    }

    /**
     * Helper method to retrieve the name of a ReflectionFunctionAbstract
     * @param \ReflectionFunctionAbstract $reflection
     * @return string
     */
    protected function getReflectionFunctionName(\ReflectionFunctionAbstract $reflection)
    {
        // Class method
        if ($reflection instanceof \ReflectionMethod) {
            return $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName();
        }
        return $reflection->getName();
    }
}

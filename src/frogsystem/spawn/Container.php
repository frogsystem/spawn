<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Exceptions\NotFoundException;
use Interop\Container\ContainerInterface;

/**
 * Class Container
 * @package frogsystem\spawn
 */
class Container implements ContainerInterface, \ArrayAccess
{

    /**
     * Library of the entries.
     * @var array
     */
    protected $container = [];

    /**
     * Internal entries library.
     * @var array
     */
    protected $internals = [];

    /**
     * The container for delegated lookup.
     * @var ContainerInterface
     */
    protected $delegate;


    /**
     * Creates a Container.
     * @param ContainerInterface $container The delegated container.
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->delegate = $this;
        if ($container) {
            $this->delegate = $container;
        }

        // set default container instance
        $this->set(ContainerInterface::class, $this);
    }

    /**
     * Delegates to dependency lookup to another container.
     * @param ContainerInterface $container The delegated container.
     */
    public function delegate(ContainerInterface $container)
    {
        $this->delegate = $container;
    }

    /**
     * Shorthand for a factory.
     * @param string $concrete
     * @param array $args
     * @return Callable
     */
    public function factory($concrete, array $args = [])
    {
        return function () use ($concrete, $args) {
            return $this->make($concrete, $args);
        };
    }

    /**
     * Shorthand to invoke the callable just once (when needed) and return its result afterwards.
     * @param callable $callable
     * @param array $args
     * @return callable
     */
    public function once(callable $callable, array $args = [])
    {
        return function () use ($callable, $args) {
            static $result;
            if (!$result) {
                $result = $this->invoke($callable, $args);
            }
            return $result;
        };
    }

    /**
     * Shorthand to create a singleton like instance with additional arguments.
     * @param string $concrete
     * @param array $args
     * @return callable
     */
    public function one($concrete, array $args = [])
    {
        return $this->once($this->factory($concrete, $args));
    }

    /**
     * Protect a value from being executed as callable on retrieving.
     * @param $callable
     * @return Callable
     */
    public function protect($callable)
    {
        return function () use ($callable) {
            return $callable;
        };
    }

    /**
     * Binds the abstract to a value.
     * @param string $abstract
     * @param mixed $value
     */
    public function set($abstract, $value)
    {
        $this->container[$abstract] = $value;
    }

    /**
     * Invokes the registered entry for an abstract and returns the result.
     *
     * @param string $abstract The abstract to store in the container.
     * @param array $args Array of arguments passed to a possible callable
     * @return mixed No entry was found for this identifier.
     * @throws NotFoundException
     */
    public function get($abstract, $args = [])
    {
        // element in container
        if ($this->has($abstract)) {
            // retrieve the entry
            return $this->retrieve($this->container, $abstract, $args);
        }

        throw new Exceptions\NotFoundException("Abstract '{$abstract}' not found.");
    }

    /**
     * Helper method to retrieve an existing entry from a given library array.
     * You have to find sure, that the entry exists in the library.
     * @param $from
     * @param string $abstract The abstract to get.
     * @param array $args
     * @return mixed The entry.
     * @internal param $ &array $from The specified library.
     */
    private function retrieve(&$from, $abstract, $args = [])
    {
        // get the entry
        $entry = $from[$abstract];

        // Closures will be invoked with DI and the result returned
        if (is_object($entry) && ($entry instanceof \Closure)) {
            return $this->invoke($entry, $args);
        }

        // return the unchanged value
        return $entry;
    }

    /**
     * Returns true if an entry for the abstract exists.
     * False otherwise.
     * @param string $abstract The abstract to be looked up.
     * @return boolean
     */
    public function has($abstract)
    {
        return is_string($abstract) && isset($this->container[$abstract]);
    }


    /**
     * Make a new instance of a concrete using Dependency Injection.
     * @param $concrete
     * @param array $args
     * @return mixed
     * @throws Exceptions\InvalidArgumentException
     */
    public function make($concrete, array $args = [])
    {
        // build only from strings
        if (!is_object($concrete) && !is_scalar($concrete) && !is_null($concrete)) {
            throw new Exceptions\InvalidArgumentException();
        }

        // get reflection and parameters
        $reflection = new \ReflectionClass($concrete);
        $constructor = $reflection->getConstructor();

        // Return new instance
        $arguments = $constructor ? $this->inject($constructor, $args) : [];
        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Invoke the given Closure with DI.
     * @param callable $callable
     * @param array $args
     * @return mixed
     * @throws Exceptions\ContainerException
     */
    public function invoke(Callable $callable, array $args = [])
    {
        // object/class method
        if (is_string($callable) && false !== strpos($callable, '::')) {
            $callable = explode('::', $callable);
        }
        if (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
            $arguments = $this->inject($reflection, $args);
            return $reflection->invokeArgs($callable[0], $arguments);
        }

        // closures, functions and any other callable
        $reflection = new \ReflectionFunction($callable);
        $arguments = $this->inject($reflection, $args);
        return call_user_func_array($callable, $arguments); // closures will loose scope if invoked by reflection
    }

    /**
     * Performs the actual injection of dependencies from a reflection
     * @param \ReflectionFunctionAbstract $reflection
     * @param array $args
     * @return array The list of reflected arguments.
     * @throws Exceptions\ContainerException
     */
    protected function inject(\ReflectionFunctionAbstract $reflection, array $args = [])
    {
        // get parameters
        $parameters = $reflection->getParameters();

        // Build argument list
        $arguments = [];
        foreach ($parameters as $parameter) {
            // Get class
            $class = $parameter->getClass();

            // From argument array (class or parameter name)
            $key = $class && isset($args[$class->name]) ? $class->name : $parameter->name;
            if (array_key_exists($key, $args)) {
                $arguments[] = $args[$key];
                unset($args[$key]);
                continue;
            }

            // Delegated Lookup
            if ($class && $this->delegate->has($class->name)) {
                $arguments[] = $this->delegate->get($class->name);
                continue;
            }

            // Real class
            if ($class && class_exists($class->name)) {
                $arguments[] = $this->make($class->name);
                continue;
            }

            // Skip optional parameter with default value
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            // Couldn't resolve the dependency
            throw new Exceptions\ContainerException(
                "Unable to resolve parameter `{$this->getReflectionParameterName($parameter)}` for function/method `{$this->getReflectionFunctionName($reflection)}`."
            );
        }

        return $arguments;
    }

    /**
     * Helper method to get the type of a reflection paramter
     * @param \ReflectionParameter $parameter
     * @return NULL|\ReflectionType|string
     */
    private function getReflectionParameterName(\ReflectionParameter $parameter)
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
    private function getReflectionFunctionName(\ReflectionFunctionAbstract $reflection)
    {
        // Class method
        if ($reflection instanceof \ReflectionMethod) {
            return $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName();
        }
        return $reflection->getName();
    }

    /**
     * Sets an abstract via ArrayAccess.
     * @param $abstract
     * @param $value
     */
    public function offsetSet($abstract, $value)
    {
        $this->set($abstract, $value);
    }

    /**
     * Check existence of an abstract via ArrayAccess.
     * @param $abstract
     * @return bool
     */
    public function offsetExists($abstract)
    {
        return $this->has($abstract);
    }

    /**
     * Unset an entry via array interface.
     * @param $abstract
     */
    public function offsetUnset($abstract)
    {
        unset($this->container[$abstract]);
    }

    /**
     * Gets an abstract via ArrayAccess.
     * @param $abstract
     * @return null
     */
    public function offsetGet($abstract)
    {
        if (!$this->has($abstract)) {
            return null;
        }
        return $this->get($abstract);
    }


    /**
     * Sets an internal entry as property to the given value.
     * @param string $internal Identifier of the entry.
     * @param mixed $value The Value of the entry.
     */
    public function __set($internal, $value)
    {
        $this->internals[$internal] = $value;
    }

    /**
     * Returns the given entry via property.
     * @param string $internal Identifier of the entry.
     * @return mixed The entry.
     * @throws Exceptions\NotFoundException
     */
    public function __get($internal)
    {
        // element in container
        if (isset($this->$internal)) {
            // retrieve the entry
            return $this->retrieve($this->internals, $internal);
        }

        throw new Exceptions\NotFoundException("Internal '{$internal}' not found.");
    }

    /**
     * Returns whether an internal exists or not.
     * @param $internal
     * @return bool
     */
    public function __isset($internal)
    {
        return is_string($internal) && isset($this->internals[$internal]);
    }

    /**
     * Unset an internal via property.
     * @param $internal
     */
    public function __unset($internal)
    {
        unset($this->internals[$internal]);
    }
}

<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Exceptions\NotFoundException;
use Interop\Container\ContainerInterface;

/**
 * Class Container
 * @package frogsystem\spawn
 */
class Container implements Contracts\Container, \ArrayAccess
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
    }

    /**
     * Delegates to dependency lookup to another container.
     * @param ContainerInterface $container The delegated container.
     */
    public function delegate(ContainerInterface $container) {
        $this->delegate = $container;
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
     * Shorthand to invoke the callable just once (when needed) and return its result afterwards.
     * @param callable $value
     * @return callable
     */
    public function once(callable $value)
    {
        return function () use ($value) {
            static $result;
            if (!$result) {
                $result = $value();
            }
            return $result;
        };
    }

    /**
     * Shorthand to create a singleton like instance with additional arguments.
     * @param string $value
     * @param array $args
     * @return callable
     */
    public function one($value, array $args = [])
    {
        return $this->once(function() use ($value, $args) {
            return $this->make($value, $args);
        });
    }

    /**
     * Shorthand for a factory.
     * @param string $value
     * @param array $args
     * @return Callable
     */
    public function factory($value, array $args = [])
    {
        $factory = function () use ($value, $args) {
            return $this->make($value, $args);
        };
        return $factory;
    }

    /**
     * Protect a value from being executed as callable on retrieving.
     * @param $value
     * @return Callable
     */
    public function protect($value)
    {
        return function () use ($value) {
            return $value;
        };
    }

    /**
     * Invokes the registered entry for an abstract and returns the result.
     * @throws Exceptions\NotFoundException  No entry was found for this identifier.
     * @throws Exceptions\ContainerException Error while retrieving the entry.
     * @param string $abstract The abstract to store in the container.
     * @return mixed The entry.
     */
    public function get($abstract) {
        // element in container
        if ($this->has($abstract)) {
            // retrieve the entry
            return $this->retrieve($abstract, $this->container);
        }

        // look for an internal
        if (isset($this->$abstract)) {
            return $this->$abstract;
        }

        throw new Exceptions\NotFoundException("Abstract '{$abstract}' not found.");
    }

    /**
     * Helper method to retrieve an existing entry from a given library array.
     * You have to make sure, that the entry exists in the library.
     * @param string $abstract The abstract to get.
     * @param &array $from The specified library.
     * @return mixed  The entry.
     */
    protected function retrieve($abstract, &$from)
    {
        // get the entry
        $entry = &$from[$abstract];

        // Closures will be invoked with DI and the result returned
        if (is_object($entry) && ($entry instanceof \Closure)) {
            return $this->invoke($entry);
        }

        // return the unchanged value
        return $this->container[$abstract];
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
     * Build a new instance of a concrete using Dependency Injection.
     * @param $concrete
     * @param array $args
     * @return mixed
     * @throws Exceptions\ContainerException
     */
    public function build ($concrete, array $args = [])
    {
        // build only from strings
        if (!is_string($concrete)) {
            throw new Exceptions\ContainerException("Unable to find concrete {(string) $concrete}");
        }

        // get reflection and parameters
        $reflection = new \ReflectionClass($concrete);
        $constructor = $reflection->getConstructor();

        // Return new instance
        $arguments = $constructor ? $this->inject($constructor, $args) : [];
        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Make a instance of any abstract using the container or build a new object from a concrete if possible.
     * @param string $abstract
     * @param array $args
     * @return mixed
     */
    public function make($abstract, array $args = [])
    {
        // try to get abstract from container
        try {
            return $this->get($abstract);

        // Return new instance
        } catch (NotFoundException $e) {
            return $this->build($abstract, $args);
        }
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

        // closures, functions and other callables
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
        foreach ($parameters as $param) {
            // DI
            $class = $param->getClass();
            if ($class && $this->delegate->has($class->name)) {
                $arguments[] = $this->delegate->get($class->name);
                continue;
            }

            // class exists
            if ($class && class_exists($class->name)) {
                $arguments[] = $this->make($class->name);
                continue;
            }

            // from argument list
            if (array_key_exists($param->name, $args)) {
                $arguments[] = $args[$param->name];
                unset($args[$param->name]);
                continue;
            } else if (!empty($args)) {
                $arguments[] = array_shift($args);
                continue;
            }

            // optional parameter with default value
            if ($param->isDefaultValueAvailable()) {
                $arguments[] =  $param->getDefaultValue();
                continue;
            }

            // Couldn't resolve the dependency
            throw new Exceptions\ContainerException("Unable to resolve parameter '{$param->name}'.");
        }

        return $arguments;
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
     * @param string $internal    Identifier of the entry.
     * @param mixed  $value The Value of the entry.
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
            return $this->retrieve($internal, $this->internals);
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

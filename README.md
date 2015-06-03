[![Latest Stable Version](https://poser.pugx.org/frogsystem/spawn/v/stable)](https://packagist.org/packages/frogsystem/spawn) [![Total Downloads](https://poser.pugx.org/frogsystem/spawn/downloads)](https://packagist.org/packages/frogsystem/spawn) [![Latest Unstable Version](https://poser.pugx.org/frogsystem/spawn/v/unstable)](https://packagist.org/packages/frogsystem/spawn) [![License](https://poser.pugx.org/frogsystem/spawn/license)](https://packagist.org/packages/frogsystem/spawn)
[![Codacy Badge](https://www.codacy.com/project/badge/915b4386f900427e8b9e428b9d576e30)](https://www.codacy.com/app/mail_6/spawn)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a64ecd12-c01d-446f-b60e-3b4b5dc55f3e/mini.png)](https://insight.sensiolabs.com/projects/a64ecd12-c01d-446f-b60e-3b4b5dc55f3e)

# Spawn
Spawn is a simple and lightweight implementation of an IoC application container and fully compatible with the [container-interop](https://github.com/container-interop/container-interop) standard.

# Installation
You can install this package through Composer:
```
composer require frogsystem/spawn
```
The packages follows the Semantic Versioning specification, and there will be full backward compatibility between minor versions.

# Usage
Boot your container by creating a new instance:
```php
$app = new Container();
````
Spawn will take care of itself; you will always get the same instance as long as you use Dependency Injection and the provided factory methods.

## Get an entry
Retrieve an entry from the container with the standardized `get` method; use array access for your convenience:
```php
print $app->get('MyEntry'); // will print whatever value 'MyEntry' has
print $app['MyEntry']; // will do the same
```
However, if the entry is set to a callable, the callable will be invoked and its result returned instead. You will find use of this behavior to achieve different goals.
```php
$app->set('MyEntry', function() {
    return 'Called!'
});
print $app->get('MyEntry'); // will print 'Called!'
```

## Set an entry
To register an entry with the container, use the provided `set` method or array access:
```php
$app->set('MyEntry', $value);
$app['MyEntry'] = $value;
```

### Factory (Implementation)
By design, the purpose of the container is to provide you with implementations for abstracts. To do so, you'll have to bind the abstract to a factory closure:
```php
$app['ACME\MyContract'] = function() use ($app) {
    return $app->find('MyImplementation');
};
```
There is a shorthand for this and other common use cases:
```php
$app['ACME\MyContract'] = $app->factory('MyImplementation'); // shorthand for the statement above (roughly)
```

### Assignment (Instance)
Binding a specific instance to an abstract can be done by normal assignment:
```php
$app['ACME\MyContract'] = new MyImplementation();
```

### Once (deferred execution)
If you want to defer execution of the callable to the time when it is actually requested (e.g. because its expensive but not always used), use `once`:
```php
$app['ACME\MyContract'] = $app->once(function() {
  return very_expensive_call(); // will be executed once when 'ACME\MyContract' is requested; returns its result afterwards
});
```
It will store the result and any further request on `ACME\MyContract` will return the stored result instead of invoking the callable.

### One (Singleton)
This allows us to register implementations that behave more or less like singletons:
```php
$app['ACME\MyContract'] = $app->one('ACME\MyClass'); // instantiated on first request; returns the same object every time
```

### Protect a Callable
In case you want to store a closure or an other callable in the container, you can protect them from being invoked while retrieving:
```php
$app['MyCallable'] = $app->protect(function() {
    print 'Called!';
});
$printer = $app->get('MyCallable'); // will do nothing
$printer(); // will print 'Called!'
```

### FactoryFactory
Putting all this together, you might easily create a so called `FactoryFactory` - a factory that provides you with a specific factory whenever you need one:
```php
$app['UserFactory'] = $this->protect(function($username) use ($app) {
    $user = $app->one('User')->getByName($username);
    return $user;
});
$userFactory = $app->get('UserFactory');
print $userFactory('Alice')->getName(); // will print 'Alice'
print $userFactory('Bob')->getName(); // will print 'Bob'
```

## Check for an entry
Use the `has` method to check whether an entry exists or not:
```php
$app->has('MyEntry'); // true or false
```

## Internals
You must only use the container to define your abstracts. They are meant to be shared with other containers and an implementation may be replaced by a different one during runtime. However, you will have cases where your code depends on a specific instance. Those internals are hold separately from the rest of the container and therefore have to be set as properties:
```php
$app->config = $app->find('MyConfig');
```
Using the magic setter will provide you with the same API as set out above. You may also define an internal explicit as class property, but a callable __will not__ be invoked on retrieval if set this way.

Anyway, reading internals is possible through properties, the `get` method and array access:
```php
print $app->version;
print $app->get('version');
print $app['version']; // all three will print the same string
```

To use a value as internal as well as normal container entry, simply chain the assignments:
```php
$app->config = $app['ConfigContract'] = $this->factory('MyConfig');
```

## Dependency Injection
Spawn provides you with two methods to create new instances using Dependency Injection. Use `find` to get an implementation or previously stored instance for an abstract. The container will try to resolve any dependencies:
```php
class MyImplementation {
    __construct(OtherClass $other);
}
$app['MyClass'] = $this->factory('MyImplementation');
$app->find('MyClass');
```

Although you will normally use `find` to retrieve your instances, you may use `make` to create an object from a concrete class:
```php
class MyClass {
    __construct(OtherClass $other);
}
$app->make('MyClass');
```
In fact, `find` will simply return the result of `get` if there is an entry and the result of `make` otherwise. The magic happens when you set a container entry to closure. In the example above, the `factory` closure is fetched and invoked. By recursively calling `find` it will return a new instance of `MyImplementation`. Internally `make` is called this time, which takes care of resolving the dependencies.

### Constructor arguments
You may pass additional constructor arguments in an array as second parameter to `find` and `make`. Parameter names will be matched against array keys, but they will only be used if a dependency cannot be met else:
```php
class MyClass {
    __construct(OtherClass $other, $id);
}
$app->find('MyClass', ['id' => 42]);
```


## Delegate lookup
Delegate lookup was introduced by the Container Interoperability standard. By default a request via `get` or `has` methods is performed within the container. However, **if the fetched entry has dependencies, instead of performing the dependency lookup in the container, the lookup is performed on the delegate container**. 

Dependency lookup in Spawn will always be performed on the delegate container. But by default the delegate container is set to itself.
Set the delegate container via constructor argument or use the use the `delegate` method:
```php
$app = new Container($delegateContainer);
$app->delegate($delegateContainer);
```

Delegate lookup enables sharing of entries across containers and allows to make up a **delegation queue**. See **Design principles** to learn how to utilize this feature properly. 

# Design principles
- Implements container-interop
- Implements delegate lookup
- Enforce users to mainly add abstracts to their container
- Add entries only through one single `set` interface; other features are implemented by closures
- Enforce users to heavily use the delegate lookup feature and the delegation queue

# Outlook
- Add an application interface (Runnable)
- Add an interface to connect containers, thus creating an easy to use module system
- Add other elementary features for IoC applications (yet to define...) 

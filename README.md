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
The packages follows the SemVer specification, and there will be full backward compatibility between minor versions.

# Usage
Start your container by making a new instance:
```php
$app = new Container();
````
It will take care of itself, so you will always get the same instance as long as you use Dependency Injection and the provided factory methods.

## Get entries
Retrieve an entry from the container with the standardized `get` method. Use array access for your convenience.
```php
print $app->get('MyEntry'); // will print whatever value 'MyEntry' has
print $app['MyEntry']; // will do the same
```
However, if the entry's value is a callable, it will be invoked and the result will be returned instead. You will use this behavior to achieve different goals.
```php
$app->set('MyEntry', function() {
    return 'Called!'
});
print $app->get('MyEntry'); // will print 'Called!'
```

## Set entries
To register an entry with the container, use the provided `set` method or array access:
```php
$app->set('MyEntry', $value);
$app['MyEntry'] = $value;
```

### Implementation (Factory)
By design, the main purpose of the container is to provide you with implementations for abstracts. To do so, you'll have to set the abstract to a factory closure:
```php
$app['ACME\MyContract'] = function() use ($app) {
    return $app->make('MyImplementation');
};
```
But there are shorthands for the most common use cases:
```php
$app['ACME\MyContract'] = $app->factory('MyImplementation'); // shorthand for the statement above
```

### Instance
Binding a concrete instance to an abstract should be done by normal assignment:
```php
$app['ACME\MyContract'] = new MyImplementation();
```

### Deferred execution
If you want to defer the execution of a callable to the first time the abstract is requested (e.g. because an expensive script is only triggered under certain circumstances), use `once`:
```php
$app['ACME\MyContract'] = $app->once(function() {
  return very_expensive_call(); // will only be executed when 'ACME\MyContract' is actually requested
});
```
Any further request on `ACME\MyContract` will now return the stored result.

### Singleton like
This allows us to register Singleton like implementations:
```php
$app['ACME\MyContract'] = $app->one('ACME\MyClass'); // will be instantiated on the first request and returns the same object every time
```

### Protect callable
In case you want to store a closure or an other callable in the container, you can protect them from being invoked while retrieving:
```php
$app['MyCallable'] = $app->protect(function() {
    print 'Called!';
});
$printer = $app->get('MyCallable'); // will do nothing
$printer(); // will print 'Called!'
```

### Create a FactoryFactory
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
$app->has('MyEntry'); // true || false
```

## Internals
You must only use the container to define your abstracts. They are meant to be shared with other containers and an implementation may be replaced by a different one during runtime. However, there are use cases where your code depends on specific instances. Those internals are hold separately from the rest of the container and therefore have to be set as properties:
```php
$app->config = $app->make('MyConfig');
```
Using the magic setter will provide you with the exact same options as set out above. You may also define an internal explicit as class property, but a callable __will not__ be invoked on retrieval if set this way.

Anyway, access to internals is available through `get` method and properties:
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
Spawn provides two methods to create new instances using Dependency Injection. Use `build` to create an object from a concrete class. The container will try to resolve any dependencies. 
```php
class MyClass {
    __construct(OtherClass $other);
}
$app->build('MyClass');
```

To retrieve a value from the container if possible, use `make`: 
```php
$app['MyContract'] = $this->factory('MyClass);
$app->make('MyContract');
```
In fact, `make` will simply return the result of `get` if there is an entry and the result of `build` otherwise. The magic happens when you set a container entry to closure. In the example above, the `factory` closure will be fetched as entry and invoked. It will return a new instance of `MyClass` - by recursively calling `make`. As there is no entry for `MyClass`, `build` will take care of it and inject any dependencies.

### Additional arguments
You may pass additional constructor arguments in an array as second parameter. Parameter names will be match against array keys, but they'll only be used if a dependency cannot be met:
```php
class MyClass {
    __construct(OtherClass $other, $id);
}
$app->make('MyClass', $args); // $args = ['id' => 42]
```


## Delegate lookup
Delegate lookup is a feature introduced by the Container Interoperability standard. By default a simple request via `get` or `has` methods is performed within the container. However, **if the fetched entry has dependencies, instead of performing the dependency lookup in the container, the lookup is performed on the delegate container**. 

Dependency lookup in Spawn will always be performed on the delegate container. But by default the delegate container is set to itself.
Set the delegate container via constructor argument or use the use the `delegate` method:
```php
$app = new Container($delegateContainer);
$app->delegate($delegateContainer);
```

It allows us to share entries across containers and build up a **delegation queue**. See **Design principles** to learn how to utilize the feature properly. 

# Design principles
- Implements container-interop
- Implements delegate lookup
- Enforce users to mainly add abstracts to their container
- Add entries only through one single `set` interface; other features are implemented by closures.
- Enforce users to heavily use the delegate lookup feature and the delegation queue

# Outlook
- Add an application interface (Runnable)
- Add an interface to connect two containers, thus creating an easy to use module system
- Add other elementary features for IoC applications (yet to define...) 

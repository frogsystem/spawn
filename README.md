# Spawn

[![Latest Stable Version](https://poser.pugx.org/frogsystem/spawn/v/stable)](https://packagist.org/packages/frogsystem/spawn)
[![Total Downloads](https://poser.pugx.org/frogsystem/spawn/downloads)](https://packagist.org/packages/frogsystem/spawn)
[![Latest Unstable Version](https://poser.pugx.org/frogsystem/spawn/v/unstable)](https://packagist.org/packages/frogsystem/spawn)
[![License](https://poser.pugx.org/frogsystem/spawn/license)](https://packagist.org/packages/frogsystem/spawn)
[![Build Status](https://travis-ci.org/frogsystem/spawn.svg?branch=master)](https://travis-ci.org/frogsystem/spawn)
[![Codacy Badge](https://api.codacy.com/project/badge/coverage/915b4386f900427e8b9e428b9d576e30)](https://www.codacy.com/app/mrgrain/spawn)
[![Codacy Badge](https://api.codacy.com/project/badge/grade/915b4386f900427e8b9e428b9d576e30)](https://www.codacy.com/app/mrgrain/spawn)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a64ecd12-c01d-446f-b60e-3b4b5dc55f3e/mini.png)](https://insight.sensiolabs.com/projects/a64ecd12-c01d-446f-b60e-3b4b5dc55f3e)

## Spawn
Spawn is a simple and lightweight implementation of an IoC application container and fully compatible with the [container-interop](https://github.com/container-interop/container-interop) standard.

## Installation
You can install this package through Composer:
```
composer require frogsystem/spawn
```
The packages follows the Semantic Versioning specification, and there will be full backward compatibility between minor versions.

## Documentation
Boot your container by creating a new instance:
```php
$app = new Container();
````
Spawn will take care of itself; you will always get the same instance as long as you use Dependency Injection and the provided factory methods.

### Get an entry
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

### Set an entry
To register an entry with the container, use the provided `set` method or array access:
```php
$app->set('MyEntry', $value);
$app['MyEntry'] = $value;
```

#### Factory (Implementation)
By design, the purpose of the container is to provide you with implementations for abstracts. To do so, you'll have to bind the abstract to a factory closure:
```php
$app['ACME\MyContract'] = function() use ($app) {
    return $app->make('MyImplementation');
};
```
There is a shorthand for this and other common use cases:
```php
$app['ACME\MyContract'] = $app->factory('MyImplementation'); // shorthand for the statement above (roughly)
```

#### Assignment (Instance)
Binding a specific instance to an abstract can be done by normal assignment:
```php
$app['ACME\MyContract'] = new MyImplementation();
```

#### Once (deferred execution)
If you want to defer execution of the callable to the time when it is actually requested (e.g. because its expensive but not always used), use `once`:
```php
$app['ACME\MyContract'] = $app->once(function() {
  return very_expensive_call(); // will be executed once when 'ACME\MyContract' is requested; returns its result afterwards
});
```
It will store the result and any further request on `ACME\MyContract` will return the stored result instead of invoking the callable.

#### One (Singleton)
This allows us to register implementations that behave more or less like singletons:
```php
$app['ACME\MyContract'] = $app->one('ACME\MyClass'); // instantiated on first request; returns the same object every time
```

#### Protect a Callable
In case you want to store a closure or an other callable in the container, you can protect them from being invoked while retrieving:
```php
$app['MyCallable'] = $app->protect(function() {
    print 'Called!';
});
$printer = $app->get('MyCallable'); // will do nothing
$printer(); // will print 'Called!'
```

#### FactoryFactory
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

### Check for an entry
Use the `has` method to check whether an entry exists or not:
```php
$app->has('MyEntry'); // true or false
```

### Internals
You must only use the container to define your abstracts. They are meant to be shared with other containers and an implementation may be replaced by a different one during runtime. However, you will have cases where your code depends on a specific instance. Those internals are hold separately from the rest of the container and therefore have to be set as properties:
```php
$app->config = $app->make('MyConfig');
```
Using the magic setter will provide you with the same API as set out above. You may also define an internal explicit as class property, but a callable __will not__ be invoked on retrieval if set this way.

Get your internals through properties as well:
```php
print $app->version;
```

To set a value for both, an internal and a normal container entry, simply chain the assignments:
```php
$app->config = $app['ConfigContract'] = $this->factory('MyConfig');
```

### Dependency Injection
Spawn provides you with two methods for using Dependency Injection and the Inversion of Control pattern. Use `make` to create new instances of abstracts; and use `invoke` to execute callables with filled-in dependencies. Both methods will using Dependency Injection to resolve their arguments. This means, if the invoked callable or class constructor has **any** parameters, those methods will use the container to find a suitable implementation and inject it in the argument list.

Additional any value retrieved from the container via `get` or `ArrayAccess` which is a callable, will be invoked using the very same `invoke` method. Thus they will also have their dependencies injected.

Use `make` to create an object from a concrete class:
```php
class MyClass {
    __construct(OtherClass $other);
}
$app->make('MyClass');
```

When calling `invoke` with a callable as argument, Spawn will try resolve any arguments:
```php
class MyObject {
    function print() {
        print 'Found!!'
    }
}
$callable = function(MyObject $object) {
    $object->print();
}
$app->invoke($callable); // will print 'Found!'
```

#### Additional arguments
You may also pass additional arguments in an array to these methods. It allows you to override dependency lookup on a per case basis. During the argument selection, entries will first be looked up in the array, matching the parameters class and name against array keys.
```php
class MyClass {
    __construct(OtherClass $other, $id);
    function do($name) {
        print $name;
    }
}
$object = $app->make('MyClass', ['id' => 42]); // $id will be 42, $other will be resolved through the container
$app->invoke([$object, 'do'], ['name' => 'Spawn']); // will print 'Spawn'
```

As mentioned above, `get` will also invoke a callable before returning it. Thus you may pass additional arguments to this method, as well.

### Delegate lookup
Delegate lookup is a featured introduced by the Container Interoperability standard. A request to the container is performed within the container. But **if the fetched entry has dependencies, instead of performing the dependency lookup in the container, the lookup is performed on the delegate container**. In other words: Whenever dependency injection happens, dependencies will be resolved through the delegate container.

Dependency lookup in Spawn is always delegated. By default the container delegates the lookup to itself.
Set a different delegate container via constructor argument or use the use the `delegate` method:
```php
$app = new Container($delegateContainer);
$app->delegate($delegateContainer);
```

Delegate lookup enables sharing of entries across containers and allows to build up a **delegation queue**. See **Design principles** to learn how to utilize this feature properly. 

## Design principles
- Implements container-interop
- Implements delegate lookup
- Separates storage of 'public' abstracts and internals
- Adding entries always via the same single method; all other features are implemented using closures
- Enforce users to use the delegate lookup feature and the delegation queue

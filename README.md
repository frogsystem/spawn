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

```php
$app = new Container();
````

## Set and retrieve entries
To register an entry with the container, use the provided `set` method:
```php
$app->set('MyEntry', $value);
```

Retrieve an entry with the standardized `get` method:
```php
print $app->get('MyEntry'); // will print whatever $value is
```
However, if the entry's value is a callable, it will be invoked and the result will be returned instead:
```php
$app->set('MyEntry', function() {
    return 'Called!'
});
print $app->get('MyEntry'); // will print 'Called!'
```

For your convenience you may also use array access to set and retrieve entries:
```php
$app['MyEntry'] = $value;
print $app['MyEntry']; // will print whatever $value is
```


### Implementations (Factory)
By design, the main purpose of the container is to provide you with implementations for abstracts. To do so, you'll have to create a callable factory for the abstract:
```php
$app['ACME\MyContract'] = function() use ($app) {
    return $app->make('MyImplementation');
};
```
But there are shorthands for the most common use cases:
```php
$app['ACME\MyContract'] = $app->factory('MyImplementation'); // shorthand for the statement above
```

### Instances
Adding a specific instance to an abstract, should be done by a normal assignment:
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

### Protect callables
In case you want to store a callable in the container, you can protect them from being invoked while retrieving:
```php
$app['MyCallable'] = $app->protect(function() {
    print 'Called!';
});
$printer = $app->get('MyCallable'); // will do nothing
$printer(); // will print 'Called!'
```

### Register a FactoryFactory
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
Using the magic setter will provide you with the exact same options as set out above. You may also define the internals explicit as class properties, but a callable __will not__ be invoked on retrieval if set that way.

Anyway, access to internals is available through `get` method and properties:
```php
print $app->version;
print $app->get('version');
print $app['version']; // all three will print the same string
```

## Dependency Injection


## Delegate lookup
```php
$app = new Container($delegateContainer);
$app->delegate($delegateContainer);
```

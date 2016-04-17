<?php
namespace Frogsystem\Spawn;

use Frogsystem\Spawn\Exceptions\InvalidArgumentException;
use Frogsystem\Spawn\Exceptions\ParameterResolutionException;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use PHPUnit_Framework_TestCase;
use RecursiveIteratorIterator;

/**
 * Class ContainerTest
 * @package Frogsystem\Spawn
 */
class ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $app;

    public function setUp()
    {
        $this->app = new Container();
    }

    public function testOnce()
    {
        // Arrange & Assert
        $object = new \stdClass();
        $mock = $this->getMock('stdClass', array('callback'));
        $mock->expects($this->once())
            ->method('callback')
            ->will($this->returnValue($object));

        // Act
        $this->app['OnceTest'] = $this->app->once(function () use ($mock) {
            return call_user_func(array($mock, 'callback'));
        });

        // Act && Assert
        for ($i = 0; $i <= 5; $i++) {
            $result = $this->app->get('OnceTest');
            $this->assertSame($object, $result);
        }
    }

    public function testOne()
    {
        // Arrange & Assert
        $mock = $this->getMock('\stdClass', array('callback'));
        $mock->expects($this->never())
            ->method('callback');

        // Act
        $this->app['OneTest'] = $this->app->one('\stdClass');
        $first = $this->app->get('OneTest');

        // Assert
        $this->assertInstanceOf('\stdClass', $first);
        for ($i = 0; $i <= 5; $i++) {
            $result = $this->app->get('OneTest');
            $this->assertSame($first, $result);
        }
    }

    public function testFactory()
    {
        // Act
        $this->app['FactoryTest'] = $this->app->factory('\stdClass');

        // Act && Assert
        $last = $this->app->get('FactoryTest');
        for ($i = 0; $i <= 5; $i++) {
            $current = $this->app->get('FactoryTest');
            $this->assertNotSame($last, $current);
            $last = $current;
        }
    }

    public function testProtect()
    {
        // Arrange & Assert
        $mock = $this->getMock('\stdClass', array('callback'));
        $mock->expects($this->never())
            ->method('callback');

        // Act
        $this->app['ProtectTest'] = $this->app->protect(array($mock, 'callback'));
        $result = $this->app->get('ProtectTest');

        // Assert
        $this->assertSame(array($mock, 'callback'), $result);
    }

    public function testDelegateConstructor()
    {
        // Arrange
        $item = new \stdClass();
        $delegate = $this->getMock(Container::class, array('has', 'get'));
        $delegate->method('has')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn($this->returnValue(true));
        $delegate->expects($this->once())
            ->method('get')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn($item);

        // Act
        $this->app = new Container($delegate);
        $this->app['something'] = function (\stdClass $object) {
            return $object;
        };
        $result = $this->app->get('something');

        // Assert
        $this->assertSame($item, $result);
    }

    public function testSetDelegate()
    {
        // Arrange
        $item = new \stdClass();
        $delegate = $this->getMock(Container::class, array('has', 'get'));
        $delegate->method('has')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn(true);
        $delegate->expects($this->once())
            ->method('get')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn($item);
        $this->app['something'] = function (\stdClass $object) {
            return $object;
        };

        // Act
        $before = $this->app->get('something');
        $this->app->delegate($delegate);
        $after = $this->app->get('something');

        // Assert
        $this->assertNotSame($item, $before);
        $this->assertSame($item, $after);
    }

    public function testNotFound()
    {
        // Arrange
        $container = $this->getMock(Container::class, array('has'));
        $container->expects($this->once())
            ->method('has')
            ->with($this->equalTo('whatever'))
            ->willReturn(false);

        // Expect
        $this->expectException(NotFoundException::class);

        // Act
        $container->get('whatever');
    }

    public function testRetrieveScalar()
    {
        // Arrange and expect
        $container = $this->getMock(Container::class, array('invoke'));
        $container->expects($this->never())
            ->method('invoke');

        $scalar = 'test string';
        $this->app['scalar'] = $scalar;

        // Act
        $result = $this->app->get('scalar');

        // Assert
        $this->assertSame($scalar, $result);
    }

    public function testFailToMakeFromArray()
    {
        // Arrange
        $object = array();

        // Expect
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->app->make($object);
    }

    public static function staticInvocationHelper()
    {
        return 'staticMethodResult';
    }

    public function testInvokeStaticClassMethod()
    {
        // Act
        $result = $this->app->invoke('\\Frogsystem\\Spawn\\ContainerTest::staticInvocationHelper');

        // Assert
        $this->assertSame('staticMethodResult', $result);
    }

    public function testInvokeArrayCallable()
    {
        // Arrange and expect
        $callable = $this->getMock(\stdClass::class, array('anything'));
        $callable->expects($this->once())->method('anything');

        // Act
        $this->app->invoke([$callable, 'anything']);
    }

    public function testInvokeClosure()
    {
        // Arrange and expect
        $value = new \stdClass();
        $callable = function () use ($value) {
            return $value;
        };

        // Act
        $result = $this->app->invoke($callable);

        // Assert
        $this->assertSame($value, $result);
    }

    public function testInvokeWithNamedArguments()
    {
        // Arrange and expect
        $frog = "Greenfrog";
        $callable = function ($frog) {
            return $frog;
        };

        // Act
        $result = $this->app->invoke($callable, ['frog' => $frog]);

        // Assert
        $this->assertSame($frog, $result);
    }

    public function testInvokeWithTypedArguments()
    {
        // Arrange and expect
        $name = 'Greenfrog';
        $frog = $this->getMock(Container::class, array('gut'));
        $frog->method('gut')
            ->willReturn(strrev($name));
        $frogHunter = $this->getMock(\stdClass::class, array('hunt'));
        $frogHunter->expects($this->once())
            ->method('hunt')
            ->with($this->equalTo($frog))
            ->willReturn($frog->gut());
        $callable = function (\stdClass $hunter, Container $frog) {
            return $hunter->hunt($frog);
        };

        // Act
        $result = $this->app->invoke($callable, [Container::class => $frog, \stdClass::class => $frogHunter]);

        // Assert
        $this->assertSame(strrev($name), $result);
    }

    public function testInvokeUseDefaultValues()
    {
        // Arrange and expect
        $callable = function ($frog = 'toad') {
            return $frog;
        };

        // Act
        $result = $this->app->invoke($callable);

        // Assert
        $this->assertSame('toad', $result);
    }

    public function testInvokeResolutionFailed()
    {
        // Arrange
        $callable = function ($frog) {
            return $frog;
        };

        // Expect
        $this->expectException(ParameterResolutionException::class);

        // Act
        $this->app->invoke($callable);
    }

    public function testInvokeResolutionFailedTypedArgument()
    {
        // Expect
        $this->expectException(ParameterResolutionException::class);

        // Act
        $this->app->make(RecursiveIteratorIterator::class);
    }
}

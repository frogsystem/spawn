<?php
namespace Frogsystem\Spawn;

use PHPUnit_Framework_TestCase;

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
        $this->app['OnceTest'] = $this->app->once(function() use ($mock) {
            return call_user_func(array($mock, 'callback'));
        });

        // Act && Assert
        for ($i=0; $i<=5; $i++) {
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
        for ($i=0; $i<=5; $i++) {
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
        for ($i=0; $i<=5; $i++) {
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
}

<?php
namespace Frogsystem\Spawn;

use Interop\Container\Exception\NotFoundException;
use PHPUnit_Framework_TestCase;

/**
 * Class ContainerInternalsTest
 * @package Frogsystem\Spawn
 */
class ContainerInternalsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Container
     */
    protected $app;

    public function setUp()
    {
        $this->app = new Container();
    }

    public function testInternalsIsset()
    {
        // Arrange
        $item = new \stdClass();

        // Act
        $this->app->internal = $item;

        // Assert
        $this->assertTrue(isset($this->app->internal));
    }

    public function testInternalsSet()
    {
        // Arrange
        $item = new \stdClass();

        // Act
        $this->app->internal = $item;

        // Assert
        $this->assertSame($this->app->internal, $item);
    }

    public function testInternalsUnset()
    {
        // Arrange
        $item = new \stdClass();
        $this->app->internal = $item;

        // Act
        unset($this->app->internal);

        // Assert
        $this->assertFalse(isset($this->app->internal));
    }

    public function testInternalsGet()
    {
        // Arrange and expect
        $item = new \stdClass();
        $container = $this->getMock(Container::class, array('has', 'get'));
        $container->method('has')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(\stdClass::class))
            ->willReturn($item);

        // Act
        $result = $container[\stdClass::class];

        // Assert
        $this->assertSame($item, $result);
    }

    public function testInternalsGetUnset()
    {
        // Arrange
        unset($this->app->internal);

        // Expect
        $this->expectException(NotFoundException::class);

        // Act
        $this->app->internal;
    }

}

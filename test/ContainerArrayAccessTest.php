<?php
namespace Frogsystem\Spawn;

use Interop\Container\Exception\NotFoundException;
use PHPUnit_Framework_TestCase;

/**
 * Class ContainerArrayAccessTest
 * @package Frogsystem\Spawn
 */
class ContainerArrayAccessTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $app;

    public function setUp()
    {
        $this->app = new Container();
    }

    public function testOffsetSet()
    {
        // Arrange
        $container = $this->getMock(Container::class, array('set'));
        $container->expects($this->once())
            ->method('set');

        // Act
        $container['something'] = array();
    }

    public function testOffsetExists()
    {
        // Arrange
        $container = $this->getMock(Container::class, array('has'));
        $container->expects($this->once())
            ->method('has')
            ->with($this->equalTo('something'))
            ->willReturn(true);

        // Act & Assert
        $this->assertTrue(isset($container['something']));
    }

    public function testOffsetUnset()
    {
        // Arrange
        $this->app['something'] = array();

        // Act
        unset($this->app['something']);

        // Assert
        $this->assertFalse(isset($this->app['something']));
    }

    public function testOffsetGet()
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

    public function testOffsetGetReturnsNull()
    {
        // Arrange and expect
        $container = $this->getMock(Container::class, array('has', 'get'));
        $container->method('has')
            ->willReturn(false);
        $container->expects($this->never())
            ->method('get');

        // Act & Assert
        $this->assertNull($container['non_existing']);
    }
}

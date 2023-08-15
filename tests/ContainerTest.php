<?php

use PHPUnit\Framework\TestCase;
use Swidly\Core\Container;

class ContainerTest extends TestCase
{
    public function testSetAndGet()
    {
        $container = new Container();
        $container->set('key', 'value');
        $this->assertEquals('value', $container->get('key'));
    }

    public function testHas()
    {
        $container = new Container();
        $container->set('key', 'value');
        $this->assertTrue($container->has('key'));
        $this->assertFalse($container->has('nonexistent_key'));
    }

    public function testRemove()
    {
        $container = new Container();
        $container->set('key', 'value');
        $container->remove('key');
        $this->assertFalse($container->has('key'));
    }

    public function testClear()
    {
        $container = new Container();
        $container->set('key1', 'value1');
        $container->set('key2', 'value2');
        $container->clear();
        $this->assertFalse($container->has('key1'));
        $this->assertFalse($container->has('key2'));
    }

    public function testMagicMethods()
    {
        $container = new Container();
        $container->key = 'value';
        $this->assertEquals('value', $container->key);
        $this->assertTrue(isset($container->key));
        unset($container->key);
        $this->assertFalse(isset($container->key));
    }

    public function testCall()
    {
        $container = new Container();
        $container->set('callback', function ($arg) {
            return $arg;
        });
        $this->assertEquals('value', $container->callback('value'));
    }

    public function testToString()
    {
        $container = new Container();
        $container->set('key', 'value');
        $this->assertEquals('{"key":"value"}', (string) $container);
    }

    public function testDebugInfo()
    {
        $container = new Container();
        $container->set('key', 'value');
        $this->assertEquals(['key' => 'value'], $container->__debugInfo());
    }
}
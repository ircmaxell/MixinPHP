<?php

/*
 * MixinPHP
 */

namespace tests\MixinPHP;

use MixinPHP\Mixin;

/**
 * MixinTest tests Mixin
 */
class MixinObjectTest extends \PHPUnit_Framework_TestCase
{

    public function testExample()
    {
        $array = new \SplFixedArray(2);

        // First, let's create the dynamic array
        $dynamicArray = Mixin::create($array);

        // now, let's change the behavior of offsetSet to automatically resize...

        $dynamicArray->offsetSet = \Closure::bind(function($index, $value) {
                            if ($index >= $this->getSize()) {
                                $this->setSize($index + 1);
                            }
                            return $this->parent->offsetSet($index, $value);
                        }, $dynamicArray);

        for ($i = 0; $i < 100; $i++) {
            $dynamicArray[$i] = $i;
        }

        // To prove the original was modified:

        $this->assertEquals(100, $array->count());

        // Actually, let's make count() portable in a closure:
        $count = $dynamicArray->count;

        $dynamicArray[100] = 100;

        $this->assertEquals(101, $count());

        try {
            $array[101] = 101;
            $this->fail('original SplFixedArray must throw an exception');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\RuntimeException', $e);
            $this->assertEquals('Index invalid or out of range', $e->getMessage());
        }
    }

}
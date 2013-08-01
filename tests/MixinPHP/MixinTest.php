<?php

/*
 * MixinPHP
 */

namespace tests\MixinPHP;

use MixinPHP\Mixin;

/**
 * MixinTest tests Mixin
 */
class MixinTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateMixin()
    {
        $this->expectOutputString('array(3) {
  [0] =>
  int(1)
  [1] =>
  int(2)
  [2] =>
  int(3)
}
');
        $foo = Mixin::create('tests\fixtures\Foo');
        $bar = $foo->bar;
        $this->assertTrue($bar(1, 2, 3));

        return $foo;
    }

    /**
     * @depends testCreateMixin
     */
    public function testOverrideMethod($foo)
    {
        $this->expectOutputString('array(3) {
  [0] =>
  int(2)
  [1] =>
  int(4)
  [2] =>
  int(6)
}
');

        $foo->bar = function() {
                    return Mixin::callParent('bar', array_map(function($a) {
                                                return 2 * $a;
                                            }, func_get_args()));
                };

        $this->assertTrue($foo->bar(1, 2, 3));

        return $foo;
    }

    /**
     * @depends testOverrideMethod
     */
    public function testMixWith($foo)
    {
        $this->expectOutputString('array(3) {
  [0] =>
  int(3)
  [1] =>
  int(6)
  [2] =>
  int(9)
}
');

        $foo->mixWith('tests\fixtures\Baz');
        $this->assertTrue($foo->bar(1, 2, 3));
    }

}
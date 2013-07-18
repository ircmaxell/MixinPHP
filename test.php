<?php

require_once 'vendor/autoload.php';

class Foo {
    public function bar() {
        var_dump(func_get_args());
        return true;
    }
    public function baz($a, $b, Stdclass $c) {
    }
}

$foo = MixinPHP\Mixin::create('Foo');


$bar = $foo->bar;

var_dump($bar(1, 2, 3));

$foo->bar = function() {
    return MixinPHP\Mixin::callParent('bar', array_map(function($a) { return 2 * $a; }, func_get_args()));
};

var_dump($foo->bar(1, 2, 3));

class Baz {
    public function bar() {
        $args = func_get_args();
        $args = array_map(function($a) { return 3 * $a; }, $args);
        return MixinPHP\Mixin::callParent('bar', $args);
    }
}

$foo->mixWith('Baz');
var_dump($foo->bar(1, 2, 3));

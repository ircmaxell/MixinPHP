<?php

/*
 * MixinPHP
 */

namespace tests\fixtures;

class Baz
{

    public function bar()
    {
        $args = func_get_args();
        $args = array_map(function($a) {
                    return 3 * $a;
                }, $args);
        return \MixinPHP\Mixin::callParent('bar', $args);
    }

}
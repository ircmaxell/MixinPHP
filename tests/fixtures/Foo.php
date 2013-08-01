<?php

/*
 * MixinPHP
 */

namespace tests\fixtures;

class Foo
{

    public function bar()
    {
        var_dump(func_get_args());
        return true;
    }

    public function baz($a, $b, \stdClass $c)
    {
        
    }

}
An experiment at making dynamic mixins for PHP

Usage:
======

Basic usage decorates an existing object:

    use \MixinPHP\Mixin;
  
Use the `create` static method to create a "decorated" object:

    $array = Mixin::create(new SplFixedArray(10));

Now, we can interact with that decorated array just like the original object.

Note that decorated classes contain **all typing information** from the wrapped class!!!

    $array instanceof SplFixedArray; // true

But we can also do some cool stuff:

Dynamic Methods:
================

Let's take a copy of `$array->count()` to hold onto (creating dynamic callbacks):

    $count = $array->count;

    $count(); // 10

Let's set the count to return something silly:

    $array->count = function() {
        return 2 * $this->parent->count();
    };

    $count(); // 20


Mixins
======

Let's mix in another class!!!

    class Debug {
        protected $count = 0;
        public function wasCalled() {
            $this->count++;
        }
        public function getCallCount() {
            return $this->count;
        }
    }

Nice and simple. Let's mix that with our array:

    $array->mixWith('Debug');

Now, we can call those methods on it!

    $array->wasCalled();

    var_dump($array->getCallCount()); // 2

Decorated classes throw away all typing information from the mixed class:

    $array instanceof Debug; // false

Other
=====

There's a bunch of other functionality. Play with it. Try it out. Give it a shot. Most of all, break it!!!

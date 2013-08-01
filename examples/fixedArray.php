<?php

require_once '../vendor/autoload.php';

use MixinPHP\Mixin;

$array = new SplFixedArray(2);

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

var_dump($array->count()); // 100

// Actually, let's make count() portable in a closure:

$count = $dynamicArray->count;

$dynamicArray[100] = 100;

var_dump($count()); // 101

try {
    $array[101] = 101;
} catch (\Exception $e) {
    // This proves that the original behavior is still there!
    echo "Caught!\n";
}

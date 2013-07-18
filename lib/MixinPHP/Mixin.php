<?php

namespace MixinPHP;

class Mixin {

    protected static $parentStack = array();

    protected static $mixinClasses = array();

    public static function create($object) {
        if (!is_object($object)) {
            if (class_exists($object)) {
                $reflector = new \ReflectionClass($object);
                $args = func_get_args();
                array_shift($args);
                $object = $reflector->newInstanceArgs($args);
            } else {
                throw new \InvalidArgumentException('Expecting object or class, neither provided');
            }
        }
        $class = get_class($object);
        $newClass = static::createMixinClass($class);
        return new $newClass($object);
    }

    public static function pushParent($parent) {
        static::$parentStack[] = $parent;
    }

    public static function popParent($parent) {
        if ($parent !== end(static::$parentStack)) {
            throw new \LogicException('Stack mismatch');
        }
        array_pop(static::$parentStack);
    }

    public static function getParent() {
        return end(static::$parentStack);
    }

    public static function callParent($method, array $args = array()) {
        if (static::$parentStack) {
            return call_user_func_array(array(end(static::$parentStack), $method), $args);
        }
        throw new \LogicException('Non-callable parent');
    }

    public static function mix(Mixable $object, $child) {
        $child = static::create($child);
        $reflector = new \ReflectionObject($child);
        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!static::isMagic($method->getName())) {
                $name = $method->getName();
                // Happy fun copy time!!!
                $object->$name = $child->$name;
            }
        }
        return $object;
    }

    public static function createMixinClass($class) {
        $lowerClass = strtolower($class);
        if (isset(static::$mixinClasses[$lowerClass])) {
            return static::$mixinClasses[$lowerClass];
        } 
        if ($class instanceof Mixable) {
            // No need to decorate the class!!!
            return $class;
        }
        $mixedClass = $class . '_MIXED';
        if (!class_exists($mixedClass)) {
            // Attempt Autoloading
            $definition = static::createMixinClassDefinition($class);
            eval($definition);
        }
        static::$mixinClasses[$lowerClass] = $mixedClass;
        return $mixedClass;
    }

    public static function createMixinClassDefinition($class) {
        $baseClass = $class;
        $ns = '';
        if (strpos($class, '\\') !== false) {
            $parts = explode('\\', ltrim($class, '\\'));
            $baseClass = array_pop($parts);
            $ns = implode('\\', $parts);
        }
        $declaration = 'namespace ' . $ns . ' { class ' . $baseClass . '_MIXED extends ' . $class . ' implements \MixinPHP\Mixable {';
        $reflector = new \ReflectionClass($class);
        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $declaration .= static::parseMethodDeclaration($method);
        }
        $declaration .= static::getDefaultClassBody($class);
        return $declaration . '} }';
    }

    protected static function isMagic($name) {
        return in_array(strtolower($name), array('__get', '__set', '__isset', '__unset', '__construct', 'mixwith'));
    }

    protected static function parseMethodDeclaration(\ReflectionMethod $method) {
        if (static::isMagic($method->getName())) {
            return '';
        } elseif ($method->getModifiers() & \ReflectionMethod::IS_FINAL) {
            return '';
        } elseif ($method->isStatic()) {
            return '';
        }
        $output = implode(' ', \Reflection::getModifierNames($method->getModifiers()));
        $output .= ' function ';
        if ($method->returnsReference()) {
            $output .= '&';
        }
        $output .= $method->getName() . '(';
        $separator = '';
        foreach ($method->getParameters() as $param) {
            $output .= $separator . static::parseParameterDeclaration($param);
            $separator = ', ';
        }
        $output .= ') {';
        $output .= static::getMethodProxyBody($method->getName());
        return $output . '} ';
    }

    protected static function parseParameterDeclaration(\ReflectionParameter $param) {
        $output = '';
        if ($param->isArray()) {
            $output .= 'array ';
        } elseif (is_callable(array($param, 'isCallable')) && $param->isCallable()) {
            $output .= 'callable ';
        } elseif ($param->getClass()) {
            $output .= $param->getClass()->getName() . ' ';
        }
        if ($param->isPassedByReference()) {
            $output .= '&';
        }
        $output .= '$' . $param->getName();
        if ($param->isDefaultValueAvailable()) {
            $output .= ' = ' . $param->getDefaultValue();
        }
        return $output;
    }

    protected static function getMethodProxyBody($name) {
        $lname = strtolower($name);
        return ' $args = func_get_args();
                 if (!isset($this->__methods__["' . $lname . '"])) {
                     return call_user_func_array(array($this->__parent__, "'.$name.'"), $args);
                 }
                 $cb = $this->__methods__["' . $lname . '"];
                 \MixinPHP\Mixin::pushParent($this->__parent__);
                 $return = call_user_func_array($cb, $args); 
                 \MixinPHP\Mixin::popParent($this->__parent__); 
                 return $return; ';
    }

    protected static function getDefaultClassBody($class) {
        return ' private $__methods__ = array();
                 private $__parent__;
                 public function __construct(' . $class . ' $object) {
                     $this->__parent__ = $object;
                 }
                 public function mixWith($object) {
                     return \MixinPHP\Mixin::mix($this, $object);
                 }
                 public function __get($name) {
                     $lname = strtolower($name);
                     if ($name === "parent") {
                         return $this->__parent__;
                     } elseif (isset($this->__methods__[$lname])) {
                         return $this->__methods__[$lname];
                     } elseif (isset($this->__parent__->$name)) {
                         return $this->__parent__->$name;
                     }
                     return Closure::bind(function() use ($name) {
                         return call_user_func_array(array($this, $name), func_get_args());
                     }, $this);
                 }
                 public function __set($name, $value) {
                     $this->__methods__[strtolower($name)] = $value;
                 }
                 public function __isset($name) {
                     return isset($this->__methods__[strtolower($name)]);
                 }
                 public function __unset($name) {
                     unset($this->__methods__[strtolower($name)]);
                 }
                 ';
    }

}
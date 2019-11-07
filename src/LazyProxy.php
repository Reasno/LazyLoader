<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Reasno\LazyLoader;

use Hyperf\Utils\ApplicationContext;

abstract class LazyProxy
{
    /**
     * Real Object.
     * @var null|mixed
     */
    private $instance;

    public static function __callStatic(string $method, array $arguments)
    {
        $className = static::getProxyAccessor();
        return call_user_func([$className, $method], ...$arguments);
    }

    public function __call(string $method, array $arguments)
    {
        $className = static::getProxyAccessor();
        $obj = $this->getInstance($className);
        return call_user_func([$obj, $method], ...$arguments);
    }

    public function __get($name)
    {
        $className = static::getProxyAccessor();
        return $this->getInstance($className)->{$name};
    }

    public function __set($name, $value)
    {
        $className = static::getProxyAccessor();
        $this->getInstance($className)->{$name} = $value;
    }

    public function __isset($name)
    {
        $className = static::getProxyAccessor();
        return isset($this->getInstance($className)->{$name});
    }

    public function __unset($name)
    {
        $className = static::getProxyAccessor();
        unset($this->getInstance($className)->{$name});
    }

    /**
     * Get the instance of the class this LazyProxy is proxying.
     * If the instance does not already exist then it is initialised.
     * @param mixed $className
     * @return object An instance of the class this LazyProxy is proxying
     */
    public function getInstance($className)
    {
        if ($this->instance === null) {
            $this->instance = ApplicationContext::getContainer()->get($className);
        }
        return $this->instance;
    }

    /**
     * Proxy accessor.
     */
    abstract public static function getProxyAccessor(): string;
}

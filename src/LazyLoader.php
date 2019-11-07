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

use Hyperf\Utils\Coroutine\Locker as CoLocker;

class LazyLoader
{
    /**
     * Indicates if a loader has been registered.
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * The namespace for all lazy loaders.
     *
     * @var string
     */
    protected static $proxyNamespace = 'Lazy\\';

    /**
     * The singleton instance of the loader.
     *
     * @var \Reasno\LazyLoader\LazyLoader
     */
    protected static $instance;

    private function __construct()
    {
    }

    /**
     * Get or create the singleton alias loader instance.
     *
     * @return \Reasno\LazyLoader\LazyLoader
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Load a class alias if it is registered.
     *
     * @return null|bool
     */
    public function load(string $alias)
    {
        if (static::$proxyNamespace && strpos($alias, static::$proxyNamespace) === 0) {
            $this->loadProxy($alias);
            return true;
        }
    }

    /**
     * Register the loader on the auto-loader stack.
     */
    public function register(): void
    {
        if (! $this->registered) {
            $this->prependToLoaderStack();
            $this->registered = true;
        }
    }

    /**
     * Set the real-time proxy namespace.
     */
    public static function setProxyNamespace(string $namespace)
    {
        static::$proxyNamespace = rtrim($namespace, '\\') . '\\';
    }

    /**
     * Load a real-time facade for the given alias.
     */
    protected function loadProxy(string $alias)
    {
        require_once $this->ensureProxyExists($alias);
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
     */
    protected function ensureProxyExists(string $alias): string
    {
        $dir = BASE_PATH . '/runtime/container/proxy/';
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = str_replace('\\', '_', $dir . $alias . '.lazy.php');
        $key = md5($path);

        // If the proxy file does not exist, then try to acquire the coroutine lock.
        if (! file_exists($path) && CoLocker::lock($key)) {
            $targetPath = $path . '.' . uniqid();
            $code = $this->formatProxyStub(
                $alias,
                file_get_contents(__DIR__ . '/Stub/proxy.stub')
            );
            file_put_contents($targetPath, $code);
            rename($targetPath, $path);
            CoLocker::unlock($key);
        }

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
     *
     * @return string
     */
    protected function formatProxyStub(string $alias, string $stub)
    {
        $replacements = [
            str_replace('/', '\\', dirname(str_replace('\\', '/', $alias))),
            class_basename($alias),
            substr($alias, strlen(static::$proxyNamespace)),
        ];
        return str_replace(
            ['DummyNamespace', 'DummyClass', 'DummyTarget'],
            $replacements,
            $stub
        );
    }

    /**
     * Prepend the load method to the auto-loader stack.
     */
    protected function prependToLoaderStack(): void
    {
        spl_autoload_register([$this, 'load'], true, true);
    }
}

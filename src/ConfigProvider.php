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

use Reasno\LazyLoader\Listener\BootApplicationListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                BootApplicationListener::class => 10,
            ],
        ];
    }
}

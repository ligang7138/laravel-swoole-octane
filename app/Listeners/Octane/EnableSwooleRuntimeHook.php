<?php

namespace App\Listeners\Octane;

use Laravel\Octane\Events\WorkerStarting;

class EnableSwooleRuntimeHook
{
    public function handle(WorkerStarting $event): void
    {
        if (extension_loaded('swoole')) {
            \Swoole\Runtime::enableCoroutine(
                SWOOLE_HOOK_TCP | SWOOLE_HOOK_STDIO | SWOOLE_HOOK_SLEEP
            );
        }
    }
}

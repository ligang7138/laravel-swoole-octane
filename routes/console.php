<?php
/*
 * @Author: ligang ligang.bj@acewill.cn
 * @Date: 2026-03-15 00:54:00
 * @LastEditors: ligang ligang.bj@acewill.cn
 * @LastEditTime: 2026-05-29 03:39:34
 * @FilePath: /sxw/routes/console.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 定时任务
Schedule::command('orders:auto-audit')->everyMinute();
Schedule::command('bidding:sync-status')->everyMinute();
Schedule::command('goods:auto-down')->dailyAt('00:03');
Schedule::command('receivable:generate')->dailyAt('00:02');
Schedule::command('jiagewang:sync')->everyTenMinutes();
Schedule::command('canteen:calc-purchase')->everyThirtyMinutes();

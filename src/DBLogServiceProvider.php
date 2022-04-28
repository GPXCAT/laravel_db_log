<?php

namespace Gpxcat\LaravelDbLog;

use DB;
use Illuminate\Support\ServiceProvider;
use Log;

class DBLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //紀錄ORM語法到log
        DB::listen(function ($query) {
            $addSlashes = str_replace('?', "'?'", $query->sql);
            $sqlStr = vsprintf(str_replace('?', '%s', $addSlashes), $query->bindings);
            $queryTime = $query->time / 1000;

            // 因為自定的CustomizeFormatter Log會去呼叫Auth，而這個階段取用Session會失敗
            // 所以改成若是CLI直接叫Log，若不是CLI，則等到view的階段才叫
            $debug_backtrace = debug_backtrace();
            foreach ($debug_backtrace as $line_info) {
                if (!isset($line_info['file'])) {continue;}
                $line_info['file'] = str_replace(base_path(), '.', $line_info['file']);
                if (strpos($line_info['file'], './vendor/') === false) {
                    break;
                }
            }
            $logLine = [
                ($queryTime < 2.0 ? 'SQL' : 'SQL!!SLOW!!'),
                'QueryTime: ' . number_format($queryTime, 5),
                'Statement: ' . $sqlStr . ';',
                'File: ' . $line_info['file'] . ':' . $line_info['line'],
            ];
            if (strpos(php_sapi_name(), 'cli') !== false) {
                Log::debug(implode(' - ', $logLine));
            } else {
                view()->composer('*', function ($view) use ($logLine) {
                    Log::debug(implode(' - ', $logLine));
                });
            }
        });
    }
}

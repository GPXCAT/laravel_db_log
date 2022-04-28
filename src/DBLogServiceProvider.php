<?php

namespace Gpxcat\LaravelDbLog;

use DB;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;
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
        if (filter_var(env('DB_LOG', 0), FILTER_VALIDATE_BOOLEAN)) {
            DB::listen(function ($query) {
                try {
                    $addSlashes = str_replace('?', "'?'", $query->sql);
                    $sqlStr = vsprintf(str_replace('?', '%s', $addSlashes), $query->bindings);
                } catch (\Throwable $th) {
                    $sqlStr = $query->sql . "; with Bindings:" . json_encode($query->bindings);
                }
                $queryTime = $query->time / 1000;

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
                    Log::info(implode(' - ', $logLine));
                } else {
                    Event::listen(Authenticated::class, function () use ($logLine) {
                        Log::info(implode(' - ', $logLine));
                    });
                }
            });
        }
    }
}

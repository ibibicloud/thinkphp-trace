<?php

declare (strict_types = 1);

namespace think\trace;

use Closure;
use think\App;
use think\Config;
use think\event\LogWrite;
use think\Request;
use think\Response;
use think\response\Redirect;

/**
 * 页面Trace中间件
 */
class TraceDebug
{

    /**
     * Trace日志
     * @var array
     */
    protected $log = [];

    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    /** @var App */
    protected $app;

    public function __construct(App $app, Config $config)
    {
        $this->app    = $app;
        $this->config = $config->get('trace');
    }

    /**
     * 页面Trace调试
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return void
     */
    public function handle($request, Closure $next)
    {
        $debug = $this->app->isDebug();

        // 注册日志监听
        if ($debug) {
            $this->log = [];
            $this->app->event->listen(LogWrite::class, function ($event) {
                if (empty($this->config['channel']) || $this->config['channel'] == $event->channel) {
                    $this->log = array_merge_recursive($this->log, $event->log);
                }
            });
        }

        $response = $next($request);

        // Trace调试注入
        if ($debug) {
            $data = $response->getContent();
            $this->traceDebug($response, $data);
            $response->content($data);
        }

        return $response;
    }

    public function traceDebug(Response $response, &$content)
    {
        $config = $this->config;
        $type   = $config['type'] ?? 'Html';

        unset($config['type']);

        $trace = App::factory($type, '\\think\\trace\\', $config);

        if ($response instanceof Redirect) {
            //TODO 记录
        } else {
            $log    = $this->app->log->getLog($config['channel'] ?? '');
            $log    = array_merge_recursive($this->log, $log);
            $output = $trace->output($this->app, $response, $log);
            if (is_string($output)) {
                // trace调试信息注入
                $pos = strripos($content, '</body>');
                if (false !== $pos) {
                    $content = substr($content, 0, $pos) . $output . substr($content, $pos);
                } else {
                    $content = $content . $output;
                }
            }
        }
    }
}

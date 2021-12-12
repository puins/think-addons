<?php
declare(strict_types=1);

namespace think\middleware;

use think\App;

/**
 * 插件中间件
 *
 * @author JuMeng <hnjumneg@gmail.com>
 */
class Addons
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app  = $app;
    }

    /**
     * 插件中间件
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        hook('addon_middleware', $request);

        return $next($request);
    }
}
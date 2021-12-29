<?php
declare (strict_types = 1);

namespace think\service;

use think\facade\Config;
use think\facade\Route;
use think\route\dispatch\Addons;
use think\route\dispatch\Bind;
use think\Service;

class AddonsService extends Service
{
    use BaseAddons;
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        // 加载系统语言包
        $this->app->lang->load($this->app->getRootPath() . '/vendor/puins/think-addons/src/lang/zh-cn.php');

        $this->app->bind([
            'addons' => AddonsService::class,
        ]);
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        $addonsPath = $this->app->addons->getAddonsPath();

        // 如果插件目录不存在则创建
        if (!is_dir($addonsPath)) {
            @mkdir($addonsPath, 0755, true);
            @chown($addonsPath, 'www');
        }

        //所有配置
        $config = [];
        //所有二级绑定
        $bind = [];
        //所有伪静态
        $rules = [];
        foreach (scandir($addonsPath) as $v) {
            if ($v == '.' || $v == '..') {
                continue;
            }

            //基本信息文件
            $infoFile = $addonsPath . $v . '/info.ini';
            //路由配置文件
            $configFile = $addonsPath . $v . DIRECTORY_SEPARATOR . 'route.php';
            $info = parse_ini_file($infoFile, true, INI_SCANNER_TYPED) ?: [];
            if ($info['state']) {
                if (is_file($configFile)) {
                    $config_arr[$v] = include $configFile;
                    $config = array_merge($config, $config_arr);
                }
            }
        }

        //域名绑定及伪静态配置信息
        foreach ($config as $key => $val) {
            $bind[$key] = $val['domain_bind'];
            if ($val['rewrite']) {
                $rules = array_merge($rules, $val['route']);
            }
        }
        //子域名
        $subDomain = $this->app->request->subDomain();

        if (!$this->app->runningInConsole()) {
            //即二级域名绑定又伪静态
            if (array_search($subDomain, $bind) && $config[$bind[$subDomain]]['rewrite'] && count($config[$bind[$subDomain]]['route'])) {
                $rules = $config[$bind[$subDomain]]['route'];
                Route::domain($subDomain, function () use ($rules, $bind, $subDomain) {
                    // 动态注册域名的路由规则
                    foreach ($rules as $k => $rule) {
                        list($addon, $controller, $action) = explode('/', $rule);

                        app('http')->name($addon);
                        $this->loadAddon($addon);

                        Route::rule($k, Addons::class)
                            ->name($k)
                            ->completeMatch(true)
                            ->append([
                                'addon' => $addon,
                                'controller' => $controller,
                                'action' => $action,
                            ]);
                    }
                });
            }

            //二级域名绑定，不伪静态
            if (array_search($subDomain, $bind) && !$config[$bind[$subDomain]]['rewrite']) {
                Route::domain($subDomain, function () use ($bind, $subDomain) {
                    app('http')->name($bind[$subDomain]);
                    $this->loadAddon($bind[$subDomain]);
                    // 动态注册域名的路由规则
                    Route::rule('<controller?>/<action?>', Addons::class);
                });
            }

            //伪静态，不二级域名绑定
            if (count($rules) && !array_search($subDomain, $bind)) {
                foreach ($rules as $key => $rule) {
                    if (!$rule) {
                        continue;
                    }

                    list($addon, $controller, $action) = explode('/', $rule);

                    app('http')->name($addon);
                    $this->loadAddon($addon);

                    Route::rule($key, Addons::class)
                        ->name($key)
                        ->completeMatch(true)
                        ->append([
                            'addon' => $addon,
                            'controller' => $controller,
                            'action' => $action,
                        ]);
                }
            }

            //即不二级绑定也不伪静态
            if (!count($rules) && !array_search($subDomain, $bind)) {
                $path = $this->app->request->url();
                $map = $this->app->config->get('app.app_map', []);
                $deny = $this->app->config->get('app.deny_app_list', []);
                if (!empty($path)) {
                    $path = ltrim($path, '/');
                    $name = current(explode('/', $path));
                    if (strpos($name, '.')) {
                        $name = strstr($name, '.', true);
                    }

                    $appPath = app()->getBasePath() . $name . DIRECTORY_SEPARATOR;

                    if (false == array_search($name, $map) && !is_dir($appPath)) {
                        app('http')->name($name);
                        $this->loadAddon($name);
                        $this->registerRoutes(function (Route $route) {
                            $route::rule('<addon>/<controller?>/<action?>', Addons::class);
                        });
                    }
                }
            }
        }

        $this->commands(['addon' => 'think\console\command\Addon']);

    }

}

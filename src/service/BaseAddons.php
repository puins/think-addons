<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\service;

use think\facade\Config;

/**
 * Addons基础类
 */
trait BaseAddons
{
    /**
     * 获取插件基础目录
     * @access public
     * @return string
     */
    public function getAddonsPath(): string
    {
        return $this->app->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
    }

    /**
     * 加载插件文件
     * @param string $addonName 应用名
     * @return void
     */
    public function loadAddon(string $addonName): void
    {
        $addonPath = $this->getAddonsPath() . $addonName . DIRECTORY_SEPARATOR;

        //重新定义视图目录

        Config::set(['view_path' => $addonPath . 'view' . DIRECTORY_SEPARATOR], 'view');

        if (is_file($addonPath . 'common.php')) {
            include_once $addonPath . 'common.php';
        }

        $files = [];

        $files = array_merge($files, glob($addonPath . 'config' . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));

        foreach ($files as $file) {
            $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }

        if (is_file($addonPath . 'event.php')) {
            $this->app->loadEvent(include $addonPath . 'event.php');
        }

        if (is_file($addonPath . 'middleware.php')) {
            $this->app->middleware->import(include $addonPath . 'middleware.php', 'app');
        }
        //加载插件语言包
        $langFiles = [];

        $langFiles = array_merge($langFiles, glob($addonPath . 'lang' . DIRECTORY_SEPARATOR . $this->app->lang->defaultLangSet() . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));
        foreach ($langFiles as $langFile) {
            $this->app->lang->load($langFile);
        }
    }

    /**
     * 获取插件实例路由信息
     *
     * @param string $addonName
     * @return array
     */
    public function routeInfo(string $addonName)
    {
        $routeFile = $this->getAddonsPath() . $addonName . DIRECTORY_SEPARATOR . 'route.php';
        if (is_file($routeFile)) {
            $route = include $routeFile;
            return $route;
        } else {
            return [];
        }
    }
}

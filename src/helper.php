<?php
declare (strict_types = 1);
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jumeng <hnjumeng@163.com>
// +----------------------------------------------------------------------

if (!function_exists('addons_path')) {
    /**
     * 获取addons根目录
     *
     * @param string $path
     * @return string
     */
    function addons_path($path = '')
    {
        return app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . ($path ? ltrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $path);
    }
}

if (!function_exists('get_info')) {
    /**
     * 获取插件基本信息
     *
     * @param string $name
     * @return string
     */
    function get_info($name)
    {
        $info_file = addons_path() . $name . DIRECTORY_SEPARATOR . 'info.ini';
        if (!is_file($info_file)) {
            return false;
        }

        $info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];

        return $info;
    }
}

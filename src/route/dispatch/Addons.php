<?php
declare (strict_types = 1);

namespace think\route\dispatch;

use think\App;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;
use think\facade\Config;
use think\facade\Route;
use think\helper\Str;
use think\Request;
use think\route\Rule;

class Addons extends Url
{
    public function init(App $app)
    {
        parent::init($app);

        $result = $this->dispatch;

        if (is_string($result)) {
            $result = explode('/', $result);
        }

        // 获取控制器名
        $controller = strip_tags($result[0] ?: $this->rule->config('default_controller'));

        if (strpos($controller, '.')) {
            $pos = strrpos($controller, '.');
            $this->controller = substr($controller, 0, $pos) . '.' . Str::studly(substr($controller, $pos + 1));
        } else {
            $this->controller = Str::studly($controller);
        }

        // 获取操作名
        $this->actionName = strip_tags($result[1] ?: $this->rule->config('default_action'));

        // 设置当前请求的控制器、操作
        $this->request
            ->setController($this->controller)
            ->setAction($this->actionName);

    }

    /**
     * 解析URL地址，看该方法的主要目的是解析出控制器和动作
     * @access protected
     * @param  string $url URL app\route\Addons 没有意义了
     * @return array
     */
    protected function parseUrl(string $url): array
    {
        $pathinfo = $this->buildPathinfo();

        $this->request->setPathinfo($pathinfo);

        $depr = $this->rule->config('pathinfo_depr');

        $url = $pathinfo;

        $path = $this->rule->parseUrlPath($url);

        if (empty($path)) {
            return [null, null];
        }

        // 解析应用
        $name = !empty($path) ? Str::lower(array_shift($path)) : null;

        // 解析控制器
        $controller = !empty($path) ? array_shift($path) : null;

        if ($controller && !preg_match('/^[A-Za-z0-9][\w|\.]*$/', $controller)) {
            throw new HttpException(404, 'controller not exists:' . $controller);
        }

        // 解析操作
        $actionName = !empty($path) ? array_shift($path) : null;
        $var = [];

        // 解析额外参数
        if ($path) {
            preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                $var[$match[1]] = strip_tags($match[2]);
            }, implode('|', $path));
        }

        $panDomain = $this->request->panDomain();
        if ($panDomain && $key = array_search('*', $var)) {
            // 泛域名赋值
            $var[$key] = $panDomain;
        }

        // 设置当前请求的参数
        $this->param = $var;

        // 设置root
        $this->request->setRoot('/' . $name);

        // 封装路由
        $route = [$controller, $actionName];

        return $route;
    }

    protected function buildPathinfo()
    {
        $pathinfo_depr = $this->rule->config('pathinfo_depr');

        $vars = $this->rule->getVars();

        //判断是否是二级绑定
        $route = app('addons')->routeInfo(app('http')->getName());

        if (count($route) && $route['rewrite']) {
            $options = $this->rule->getOption();

            $pathinfo = $options['append']['addon'] . '/' . $options['append']['controller'] . '/' . $options['append']['action'];

            foreach ($vars as $key => $value) {
                $pathinfo .= $pathinfo_depr . $key . $pathinfo_depr . $value;
            }
        } else {
            if (empty($route['domain_bind'])) {
                $index = 0;
                $pathinfo = '';
                foreach ($vars as $key => $path) {
                    if ($index < 3) {
                        $pathinfo .= $pathinfo_depr . $path;
                        $index++;
                    } else {
                        $pathinfo .= $pathinfo_depr . $key . $pathinfo_depr . $path;
                        $index++;
                    }
                }
            } else {
                // halt($vars);
                $index = 0;
                $pathinfo = '';
                foreach ($vars as $key => $path) {
                    if ($index < 2) {
                        $pathinfo .= $pathinfo_depr . $path;
                        $index++;
                    } else {
                        $pathinfo .= $pathinfo_depr . $key . $pathinfo_depr . $path;
                        $index++;
                    }
                }
                $pathinfo = app('http')->getName() . $pathinfo;
            }
        }

        $pathinfo = trim($pathinfo, '/');

        return $pathinfo;
    }

    public function controller(string $name)
    {
        $suffix = $this->rule->config('controller_suffix') ? 'Controller' : '';

        $controllerLayer = $this->rule->config('controller_layer') ?: 'controller';
        $emptyController = $this->rule->config('empty_controller') ?: 'Error';

        $class = $this->parseClass($controllerLayer, $name . $suffix);

        if (class_exists($class)) {
            return $this->app->make($class, [], true);
        } elseif ($emptyController && class_exists($emptyClass = $this->parseClass($controllerLayer, $emptyController . $suffix))) {
            return $this->app->make($emptyClass, [], true);
        }

        throw new ClassNotFoundException('class not exists:' . $class, $class);
    }

    /**
     * 解析应用类的类名
     * @access public
     * @param string $layer 层名 controller model ...
     * @param string $name  类名
     * @return string
     */
    public function parseClass(string $layer, string $name): string
    {

        $name = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = Str::studly(array_pop($array));
        $path = $array ? implode('\\', $array) . '\\' : '';

        return 'addons' . '\\' . $this->app->http->getName() . '\\' . $layer . '\\' . $path . $class;

    }
}

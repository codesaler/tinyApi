<?php

// ==================================================
//  settings
// ==================================================

// start time
define('TIME_START', microtime(true));

// paths
define('PATH_ROOT', dirname(__DIR__));
define('PATH_CONFIG', PATH_ROOT . '/config');

// timezone
date_default_timezone_set('PRC');

// disable session
ini_set('session.auto_start', 0);
session_save_path('/dev/null');

// json header
header('Content-Type:application/json;charset=UTF-8');

// composer autoload
require PATH_ROOT . '/vendor/autoload.php';

// ==================================================
//  global functions
// ==================================================

/**
 * _config
 *
 * @param string $file
 * @param string $key
 * @return mixed
 */
function _config(string $file, string $key = null)
{
    static $configs = [];
    if (empty($configs)) {
        $file = sprintf('%s/%s.php', PATH_CONFIG, $file);
        if (!file_exists($file)) {
            throw new \RuntimeException("config file '{$file}' not found");
        }
        $configs = require $file;
    }
    if (is_null($key)) {
        return $configs;
    } else {
        if (strpos($key, '.') > 0) {
            $exp = explode('.', $key);
            $tmp = $configs;
            foreach ($exp as $v) {
                if (!isset($tmp[$v])) {
                    throw new \RuntimeException("config '{$key}' not found in file '{$file}'");
                }
                $tmp = $tmp[$v];
            }
            return $tmp;
        } else {
            if (!isset($configs[$key])) {
                throw new \RuntimeException("config '{$key}' not found in file '{$file}'");
            }
            return $configs[$key];
        }
    }
}

/**
 * _medoo
 *
 * @return \Medoo\Medoo
 */
function _medoo(): \Medoo\Medoo
{
    static $medoo;
    if (empty($medoo)) {
        $medoo = new \Medoo\Medoo(_config('config', 'medoo'));
    }
    return $medoo;
}

/**
 * _predis
 *
 * @return \Predis\Client
 */
function _predis(): \Predis\Client
{
    static $predis;
    if (empty($predis)) {
        $predis = new \Predis\Client(_config('config', 'predis'));
    }
    return $predis;
}

/**
 * _monolog
 *
 * @param string|null $subDir
 * @return \Monolog\Logger
 * @throws Exception
 */
function _monolog(string $subDir = null): \Monolog\Logger
{
    static $loggers = [];
    $file = sprintf(
        '%s%s/%s.log',
        _config('config', 'monolog.dir'),
        is_null($subDir) ? '' : '/' . rtrim(ltrim($subDir, '/'), '/'),
        date('Ymd')
    );
    if (!isset($loggers[$file])) {
        $logger = new \Monolog\Logger(_config('config', 'monolog.name'));
        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler($file, _config('config', 'monolog.level'))
        );
        $loggers[$file] = $logger;
    }
    return $loggers[$file];
}

/**
 * _model
 *
 * @param string $modelName
 * @return \tiny\api\model\AbstractModel
 */
function _model(string $modelName): \tiny\api\model\AbstractModel
{
    static $models = [];
    $modelClass = sprintf('\\tiny\\api\\model\\%sModel', ucfirst($modelName));
    if (!isset($models[$modelName])) {
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("model '{$modelClass}' not found");
        }
        $models[$modelClass] = new $modelClass();
    }
    return $models[$modelClass];
}

/**
 * _aes
 *
 * @return \phpseclib\Crypt\AES
 */
function _aes(): \phpseclib\Crypt\AES
{
    static $aes;
    if (empty($aes)) {
        $aes = new \phpseclib\Crypt\AES();
        $aes->setKey(_config('config', 'aes.key'));
        $aes->setIV(_config('config', 'aes.iv'));
    }
    return $aes;
}

// ==================================================
//  error & exception handler
// ==================================================

// error level
ini_set('display_errors', 1);
error_reporting(E_ALL);

// exception handler
set_exception_handler(function (\Throwable $ex) {
    exit(json_encode([
        'code' => $ex->getCode() ?: 99999,
        'msg' => 'exception: ' . $ex->getMessage()
    ]));
});

// error handler
set_error_handler(function (int $errno , string $errstr) {
    throw new \RuntimeException("error: {$errstr}", $errno);
});

// ==================================================
//  routing
// ==================================================

// closure
call_user_func(function () {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $controllerClass = '\\tiny\\api\\controller\\IndexController';
    $actionName = 'index';
    if (!empty($url['path']) && $url['path'] !== '/') {
        $path = explode('/', ltrim($url['path'], '/'));
        $controllerClass = sprintf('\\tiny\\api\\controller\\%sController', ucfirst($path[0]));
        if (isset($path[1])) {
            $actionName = lcfirst($path[1]);
        }
    }
    if (!class_exists($controllerClass)) {
        throw new \RuntimeException(
            "controller class '{$controllerClass}' not defined"
        );
    }
    if (!method_exists($controllerClass, $actionName)) {
        throw new \RuntimeException(
            "action '{$actionName}' not defined in class '{$controllerClass}'"
        );
    }

//    $annotation = (new \ReflectionMethod($controllerClass, $actionName))->getDocComment();
//    preg_match('/\@method ([a-z]+)/', $annotation, $matches);
//    if (!$matches) {
//        throw new \RuntimeException(
//            "action '{$controllerClass}::{$actionName}' method annotation not defined"
//        );
//    }
//    if (strtolower($matches[1]) !== strtolower($_SERVER['REQUEST_METHOD'])) {
//        throw new \RuntimeException(
//            "action '{$controllerClass}::{$actionName}' method annotation not matched"
//        );
//    }

    // request
    $input = file_get_contents('php://input');
    $request = [];
    if (!empty($input)) {
        $request = json_decode($input, true);
        if (!$request) {
            throw new \RuntimeException('request not json');
        }
    }

    // dispatch
    $controller = new $controllerClass($request);
    $response = json_encode([
        'code' => 0,
        'timestamp' => time(),
        'cost' => microtime(true) - TIME_START,
        'data' => call_user_func([$controller, $actionName])
    ]);
    exit($response);
});
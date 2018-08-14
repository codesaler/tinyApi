<?php

// ==================================================
//  settings
// ==================================================

// paths
define('PATH_ROOT', dirname(__DIR__));
define('PATH_CONFIG', PATH_ROOT . '/config');
define('PATH_CACHE', PATH_ROOT . '/cache');
define('PATH_LOG', PATH_ROOT . '/log');

// namespaces
define('NAMESPACE_CONTROLLER', '\\accnt\\api\\controller');
define('NAMESPACE_MODEL', '\\accnt\\api\\model');

// default exception code
define('DEFAULT_EXCEPTION_CODE', 99999);

// timezone
date_default_timezone_set('PRC');

// disable session
ini_set('session.auto_start', 0);
session_save_path('/dev/null');

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
 * _success
 *
 * @param array $data
 */
function _success(array $data): void
{
    header('Content-Type:application/json;charset=UTF-8');
    exit(json_encode([
        'code' => 0,
        'data' => $data
    ]));
}

/**
 * _error
 *
 * @param int $code
 * @param string $msg
 */
function _error(int $code, string $msg): void
{
    header('Content-Type:application/json;charset=UTF-8');
    exit(json_encode([
        'code' => $code,
        'msg' => $msg
    ]));
}

/**
 * _log
 */
function _log(): void
{

}

/**
 * _model
 */
function _model(): \accnt\api\model\Model
{

}

// ==================================================
//  error & exception handler
// ==================================================

// error level
ini_set('display_errors', 1);
error_reporting(E_ALL);

// error handler
set_error_handler(function (int $errno , string $errstr) {
    \accnt\api\util\Json::error(
        $errno,
        "error: {$errstr}"
    );
});

// exception handler
set_exception_handler(function (\Throwable $ex) {
    \accnt\api\util\Json::error(
        $ex->getCode() ?: DEFAULT_EXCEPTION_CODE,
        'exception: ' . $ex->getMessage()
    );
});

// ==================================================
//  routing
// ==================================================

// closure
call_user_func(function () {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $controllerClass = '\\accnt\\api\\controller\\IndexController';
    $actionName = 'index';
    if (!empty($url['path']) && $url['path'] !== '/') {
        $path = explode('/', ltrim($url['path'], '/'));
        $controllerClass = sprintf('\\accnt\\api\\controller\\%sController', ucfirst($path[0]));
        if (isset($path[1])) {
            $actionName = lcfirst($path[1]);
        }
    }
    if (!class_exists($controllerClass)) {
        throw new \RuntimeException(
            "controller class '{$controllerClass}' not defined",
            \accnt\api\util\Code::CONTROLLER_CLASS_NOT_DEFINED
        );
    }
    if (!method_exists($controllerClass, $actionName)) {
        throw new \RuntimeException(
            "action '{$actionName}' not defined in class '{$controllerClass}'",
            \accnt\api\util\Code::ACTION_NOT_DEFINED
        );
    }

//    $annotation = (new \ReflectionMethod($controllerClass, $actionName))->getDocComment();
//    preg_match('/\@method ([a-z]+)/', $annotation, $matches);
//    if (!$matches) {
//        throw new \RuntimeException(
//            "action '{$controllerClass}::{$actionName}' method annotation not defined",
//            \accnt\api\util\Code::ACTION_METHOD_ANNOTATION_NOT_DEFINED
//        );
//    }
//    if (strtolower($matches[1]) !== strtolower($_SERVER['REQUEST_METHOD'])) {
//        throw new \RuntimeException(
//            "action '{$controllerClass}::{$actionName}' method annotation not matched",
//            \accnt\api\util\Code::ACTION_METHOD_ANNOTATION_NOT_MATCHED
//        );
//    }

    // request
    $input = file_get_contents('php://input');
    $request = [];
    if (!empty($input)) {
        $request = json_decode($input, true);
        if (!$request) {
            throw new \RuntimeException('request not json', \accnt\api\util\Code::REQUEST_NOT_JSON);
        }
    }

    // dispatch
    $controller = new $controllerClass($request);
    \accnt\api\util\Json::success(
        call_user_func([$controller, $actionName])
    );
});
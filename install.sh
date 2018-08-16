#!/bin/bash

# create composer.json
# --------------------------------------------------
echo -e "\033[34mcreate composer.json...\033[0m"
cat>"composer.json"<<"EOF"
{
    "name": "LemonLone/tinyApi",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=7.2",
        "catfan/medoo": "~1.5",
        "predis/predis": "~1.1",
        "monolog/monolog": "~1.23",
        "phpseclib/phpseclib": "~2.0"
    },
    "autoload": {
        "psr-4": {
            "tiny\\api\\": ""
        }
    },
    "minimum-stability": "stable",
    "repositories": {
        "packagist": {
            "type": "composer",
            "url": "https://packagist.phpcomposer.com"
        }
    }
}
EOF
echo "composer.json created"

# download composer.phar
# --------------------------------------------------
echo -e "\033[34mdownload composer.phar...\033[0m"
`wget https://getcomposer.org/download/1.7.1/composer.phar`

# install composer
# --------------------------------------------------
echo -e "\033[34minstall composer...\033[0m"
if [ ! -f "composer.phar" ]; then
    echo -e "\033[31m'composer.phar' not found\033[0m"
    exit
fi
PHP="php"
while [ -n "$1" ]; do
    case "$1" in
        --php=*)
            PHP=${1##*=}
            break;;
    esac
    shift
done
if [ "$PHP" != "php" ]; then
    if [ ! -x "$PHP" ]; then
        echo -e "\033[31m'$PHP' not executable\033[0m"
        exit
    fi
fi
`$PHP composer.phar install`

# create dirs & files
# --------------------------------------------------
echo -e "\033[34mcreate dirs & files...\033[0m"

# config/
# config/config.php
`mkdir config`
echo "config/"
cat>"config/config.php"<<"EOF"
<?php

return [
    'medoo' => [
        'database_type' => 'mysql',
        'database_name' => 'dbname',
        'server' => '127.0.0.1',
        'username' => 'root',
        'password' => ''
    ],
    'predis' => [
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379
    ],
    'monolog' => [
        'name' => 'name',
        'dir' => '/tmp/',
        'level' => \Monolog\Logger::INFO
    ],
    'aes' => [
        'enable' => false,
        'key' => '18b1db0370a0d612be59e851944c470b',
        'iv' => '55eaa49877495b8e6b6fd831d42f8e96'
    ],
    'verify' => [
        'enable' => false,
        'interval' => 5,
        'key' => 'test',
        'secret' => '192c3896faeb6d14e2208ee3eb96f38c'
    ]
];
EOF
echo "config/config.php"

# controller/
# controller/AbstractController.php
# controller/IndexController.php
`mkdir controller`
echo "controller/"
cat>"controller/AbstractController.php"<<"EOF"
<?php

namespace tiny\api\controller;

/**
 * Class AbstractController
 *
 * @author LemonLone <lemonlone.com>
 */
abstract class AbstractController
{
    /**
     * @var array
     */
    private $request = [];

    /**
     * AbstractController constructor.
     *
     * @param array $request
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }

    /**
     * param
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed|null
     */
    protected function param(string $key, $defaultValue = null)
    {
        return $this->request[$key] ?: $defaultValue;
    }
}
EOF
echo "controller/AbstractController.php"
cat>"controller/IndexController.php"<<"EOF"
<?php

namespace tiny\api\controller;

/**
 * Class IndexController
 *
 * @author LemonLone <lemonlone.com>
 */
class IndexController
{
    /**
     * index
     *
     * @return array
     */
    public function index(): array
    {
        return [
            'version' => _model('demo')->getVersion()
        ];
    }
}
EOF
echo "controller/IndexController.php"

# model/
# model/AbstractModel.php
# model/DemoModel.php
`mkdir model`
echo "model/"
cat>"model/AbstractModel.php"<<"EOF"
<?php

namespace tiny\api\model;

/**
 * Class AbstractModel
 *
 * @method getVersion(): string
 *
 * @author LemonLone <lemonlone.com>
 */
abstract class AbstractModel
{

}
EOF
echo "model/AbstractModel.php"
cat>"model/DemoModel.php"<<"EOF"
<?php

namespace tiny\api\model;

/**
 * Class DemoModel
 *
 * @author LemonLone <lemonlone.com>
 */
class DemoModel extends AbstractModel
{
    /**
     * getVersion
     *
     * @return string
     */
    public function getVersion(): string
    {
        return 'v1.0.0';
    }
}
EOF
echo "model/DemoModel.php"

# public/
# public/index.php
`mkdir public`
echo "public/"
cat>"public/index.php"<<"EOF"
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
//  error & exception handler
// ==================================================

// error level
ini_set('display_errors', 1);
error_reporting(E_ALL);

// exception handler
set_exception_handler(function (\Throwable $ex) {
    exit(json_encode([
        'code' => $ex->getCode() ?: 99999,
        'msg' => $ex->getMessage()
    ]));
});

// error handler
set_error_handler(function (int $errno , string $errstr) {
    throw new \RuntimeException("error: {$errstr}", $errno);
});

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

// ==================================================
//  routing
// ==================================================

// closure
call_user_func(function () {
    $url = parse_url($_SERVER['REQUEST_URI']);

    // timestamp hash check
    if (_config('config', 'verify.enable')) {
        if (
            !isset($_SERVER['HTTP_VERIFY_TIMESTAMP'])
            || !isset($_SERVER['HTTP_VERIFY_KEY'])
            || !isset($_SERVER['HTTP_VERIFY_HASH'])
        ) {
            throw new \RuntimeException('verify params not found');
        }
        if ($_SERVER['HTTP_VERIFY_TIMESTAMP'] > time()) {
            throw new \RuntimeException('verify timestamp invalid');
        }
        if (time() - $_SERVER['HTTP_VERIFY_TIMESTAMP'] > _config('config', 'verify.interval')) {
            throw new \RuntimeException('verify timestamp too old');
        }
        if ($_SERVER['HTTP_VERIFY_KEY'] !== _config('config', 'verify.key')) {
            throw new \RuntimeException('verify key not matched');
        }
        $verifyString = _config('config', 'verify.key')
            . $url['path']
            . $_SERVER['HTTP_VERIFY_TIMESTAMP']
            . _config('config', 'verify.secret');
        if (md5($verifyString) !== $_SERVER['HTTP_VERIFY_HASH']) {
            throw new \RuntimeException('verify hash not matched');
        }
    }

    // controller & action
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
    if (_config('config', 'aes.enable')) {
        $input = _aes()->decrypt(base64_decode($input));
        if (!$input) {
            throw new \RuntimeException('request aes decrypt failed');
        }
    }
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
    if (_config('config', 'aes.enable')) {
        $response = base64_encode(_aes()->encrypt($response));
    }
    exit($response);
});
EOF
echo "public/index.php"

# finish
echo -e "\033[32mhave fun!\033[0m"
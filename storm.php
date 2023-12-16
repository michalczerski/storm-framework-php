<?php

namespace storm;

use MongoDB\Exception\Exception;

class STORM { public static $instance;}

function app($appDirectory) {
    STORM::$instance = new APP($appDirectory);
    return STORM::$instance;
}

function err($assert, $code, $message) {
    if ($assert) return;
    throw new \Exception($message, $code);
}

function configError($assert, $code, $message) {
    if ($assert) return;
    echo "<h1>$code</h1>$message";
    die;
}

function cleanExplode($delimiter, $string): array {
    $partsUnclean = explode($delimiter, $string);
    $parts = [];
    foreach ($partsUnclean as $part) {
        if ($part != "")
            $parts[] = $part;
    }
    return $parts;
}


function aliasPath($templatePath) {
    $appDirectory = STORM::$instance->directory;
    $aliases = STORM::$instance->aliases;
    if (str_starts_with($templatePath, '@')) {
        $firstSeparator = strpos($templatePath, "/");
        if ($firstSeparator) {
            $alias = substr($templatePath, 0, $firstSeparator);
            $path = substr($templatePath, $firstSeparator);
        } else {
            $alias = $templatePath;
            $path = '';
        }

        err(array_key_exists($alias, $aliases), 500, "Alias [$alias] doesn't exist" );

        $templatePath = $appDirectory . "/" . $aliases[$alias] . $path;
    }

    return $templatePath;
}

function import($file) : void {
    $cwd = STORM::$instance->cwd;
    $file = $file . ".php";
    $file = aliasPath($file);
    err(file_exists($file), 500, "IMPORT file failed [$file] doesn't exists");
    require_once($file);
}

function page($templateFileName, $data = []) {
    $appDirectory = STORM::$instance->directory;
    $aliases = STORM::$instance->aliases;

    $templateFileName = aliasPath($templateFileName);
    $templateFileName = $templateFileName . '.php';
    if (!file_exists($templateFileName)) {
        echo "<h1>500</h1> VIEW doesn't exist </br> $templateFileName "; die;
    }

    extract($data, EXTR_OVERWRITE, 'wddx');

    include($templateFileName);
}

function view($name) {

}

class View {
    public $directory;

    public function directory($directory) {
        $this->directory = $directory;
    }
}

class Request {
    public $method;

    function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    function isPost(): bool { return $this->method == 'POST'; }
}

class RouteParameters {
    public $file;
    public $action = "index";
    public array $parameters = [];
}

class ExecutionRoute {
    private $route;
    private $execution;
    public $parameters;

    //TODO zmienić nazwe bo nic to nie mowi po czasie kompletnie
    function __construct($route, $execution, $routeParameters) {
        $this->route = $route;
        $this->execution = $execution;
        $this->parameters = $routeParameters;
    }

    function isCallable() {
        return is_callable($this->execution);
    }

    function callback($request) {
        $function = $this->execution;
        $function($request);
    }

    function getExecutionFilePath() {
        $filePath = $this->execution;
        if ($this->parameters->file) {
            $filePath = str_replace("[file]", $this->parameters->file, $filePath);
        }

        return $filePath . ".php";
    }
 }

class App {
    public $env;
    public $cwd;
    public $directory;
    public $settings;
    public $aliases = [];
    public $filters = [];
    public $routes = [];
    public $view;

    function __construct($directory) {
        $this->directory = $directory;
        $this->env = getenv("APP_ENV");
        $this->cwd = getcwd();
        $this->view = new View();
    }
    public function filter($fun) : void {}

    function directories($aliases): void {
        $this->aliases = array_merge($this->aliases, $aliases);
        $this->aliases['@'] = $this->directory;
    }
    
    public function route($key, $value): void { $this->routes[$key] = $value; }

    public function settings($filePath) {
        $filePath = $this->directory . '/' . $filePath;
        configError(file_exists($filePath), 500, "Settings [$filePath] doesn't exist");
        $this->settings = json_decode($filePath);
    }

    public function run(): void
    {
        try {
            $requestUri = $this->getRequestUri();
            $route = $this->findRoute($requestUri);

            err($route != null, 404, "Route doesn't exist");

            $this->handleRoute($route);
        }
        catch(Exception $e) {
            echo "CATCHED"; die;
        }
    }

    //returns request uri relative to application file
    //path/on/hdd/index.php/product/first /returns product/first
    private function getRequestUri() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $endOfFirstSegment = strpos($requestUri, "/", 1);
        if ($endOfFirstSegment) {
            $getcwd = str_replace("\\", "/", $this->cwd);
            $search = substr($requestUri, 0, $endOfFirstSegment);
            $lastOccuranceOf = strripos($getcwd, $search);
            $common = substr($getcwd, $lastOccuranceOf);
            $requestUri = str_replace($common, "", $requestUri);
            $requestUri = $requestUri == "" ?  "/": $requestUri;
        }

        return $requestUri;
    }


    //TODO BEZ count == count (powyzej cisnac porownac strynga)
    private function parseRoute($routeParts, $requestPaths) {
        $routeParameters = new RouteParameters();
        if (count($routeParts) == 0 && count($requestPaths) == 0) {
            return $routeParameters;
        }
        else if (count($routeParts) == count($requestPaths)) {
            foreach($routeParts as $index => $part) {
                if (str_starts_with($part, "[") && str_ends_with($part, "]")) {
                    $parameter = str_replace(["[", "]"], "", $part);
                    if ($parameter == "file") {
                        $routeParameters->file = $requestPaths[$index];
                    }
                    if ($parameter == "action") {
                        $routeParameters->action = $requestPaths[$index];
                    }
                }
                else if (str_starts_with($part, "{") && str_ends_with($part, "}")) {
                    $parameter = str_replace(["{", "}"], "", $part);
                    $routeParameters->parameters[$parameter] = $requestPaths[$index];
                }
                else if ($part != $requestPaths[$index]) {
                    return null;
                }
            }
        }
        else {
            return null;
        }

        return $routeParameters;
    }

    private function findRoute($requestUri) {
        $requestPath = cleanExplode("/", $requestUri);
        foreach($this->routes as $route => $destination) {
            $routePart = cleanExplode("/", $route);
            $parameters = $this->parseRoute($routePart, $requestPath);
            if ($parameters) {
                return new ExecutionRoute($route, $destination, $parameters);
            }
        }
        return null;
    }

    private function handleRoute(ExecutionRoute $route): void
    {
        $request = new Request();

        if ($route->isCallable()) {
            $route->callback($request);
        } else {
            $file = $this->directory . '/' . $route->getExecutionFilePath();
            if (!is_file($file)) { echo "500 - ENDPOINT doesn't exist"; die; }
            ob_start();
            include $file;
            ob_get_clean();

            $function = $request->method . $route->parameters->action;
            if (!function_exists($function)) { echo "500 - ACTION doesn't exist'"; die; }
            $function($request);
        }
    }
}
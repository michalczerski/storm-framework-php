<?php

namespace storm;


class STORM { public static $instance;}

function app($appDirectory) {
    STORM::$instance = new APP($appDirectory);
    return STORM::$instance;
}

function exp($assert, $code, $message) {
    if ($assert) return;
    throw new \Exception($message, $code);
}

function err($assert, $code, $message) {
    if ($assert) return;
    echo "<h1>$code</h1>$message";
    die;
}

function safeArrayValue($key, $array) {
    return array_key_exists($key, $array) ? $_SERVER["CONTENT_TYPE"] : null;
}

function import($file) : void {
    $cwd = STORM::$instance->cwd;
    $file = aliasPath($file);
    if (str_ends_with($file, "/*")) {
        $dir = str_replace("/*", "", $file);
        $files = scandir($dir);
        foreach($files as $file) {
            if (str_ends_with($file, ".php")) {
                require_once($dir . "/" . $file);
            }
        }
    } else {
        $file = $file . ".php";
        exp(file_exists($file), 500, "IMPORT file failed [$file] doesn't exists");
        require_once($file);
    }
}

function view($templateFileName, $data = []) {
    STORM::$instance->view->view($templateFileName, $data);
}

class View {
    private $_layouts  = [];

    function layouts($templates) {
        $this->_layouts = $templates;
    }

    function view($templateFileName, $data = []) {
        $env = STORM::$instance->env;
        $appDirectory = STORM::$instance->directory;
        $cacheDirectory = "$appDirectory/.cache/";
        $aliases = STORM::$instance->aliases;

        $templateFileName = aliasPath($templateFileName);
        $templateFileName = $templateFileName . '.php';

        $cachedTemplateFileName = md5($templateFileName) . '.php';
        $cachedTemplateFilePath = $appDirectory . "/.cache/$cachedTemplateFileName";
        if ($env == 'development' || !file_exists($cachedTemplateFilePath)) {
            exp(file_exists($templateFileName), 500, "VIEW doesn't exist $templateFileName");
            if (!is_dir($cacheDirectory)) mkdir($cacheDirectory);

            log("compiling template [$templateFileName] \n");

            $content = file_get_contents($templateFileName);
            $content = $this->compile($content);

            $content = $this->putInLayout($content);

            file_put_contents($cachedTemplateFilePath, $content);
        }

        extract($data, EXTR_OVERWRITE, 'wddx');
        include $cachedTemplateFilePath;
    }

    private function putInLayout($content) {
        preg_match('/{% ?(layout) ?\'?(.*?)\'? ?%}/i', $content, $matches);
        if (!count($matches)) return $content;
        exp(array_key_exists($matches[2], $this->_layouts), 500, "Layout [$matches[2]] doesn't exist");
        $content = str_replace($matches[0], '', $content);

        $layoutFilePath = $this->_layouts[$matches[2]];
        $layoutFilePath = aliasPath($layoutFilePath);
        exp(file_exists($layoutFilePath), 500, "Layout [$layoutFilePath] doesn't exist");

        $layoutContent = file_get_contents($layoutFilePath);
        $layoutContent = $this->compile($layoutContent);
        $content = preg_replace('/{% ?(\$content) ?%}/i', $content, $layoutContent);

        return $content;
    }

    private function compile($content) {
        $content = preg_replace('~\{{\s*(.+?)\s*\}}~is',
            '<?php if(isset($1)) echo $1 ?>', $content);

        return $content;
    }
}

class Di implements \ArrayAccess {
    private array $container = [];

    public function offsetGet($key): mixed {
        if (!$this->offsetExists($key)) {
            throw new \Exception("DI element [$key] doesn't exist in container");
        }
        return $this->container[$key];
    }

    public function offsetSet($key, $value): void {
        $this->container[$key] = $value;
    }

    public function offsetExists($key): bool {
        return array_key_exists($key, $this->container);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->container[$offset]);
    }

    public function __get(string $name) {
        return $this->offsetGet($name);
    }
}

class Request {
    public $method;
    public $user;
    public $getParameters = [];
    public $postParameters = [];
    public $routeParameters = [];
    public $parameters = [];
    public $body;
    public $uri;

    function __construct($requestUri, $routeParameters) {
        $this->getParameters = $_GET;
        $this->postParameters = $_POST;
        $this->routeParameters = $routeParameters;
        $this->parameters = array_merge($_GET, $_POST);
        $this->uri = $requestUri;
        $this->user = array_key_exists('user', $_COOKIE) ? $_COOKIE['user'] : null;
        $this->method = $_SERVER['REQUEST_METHOD'];

        if (safeArrayValue("CONTENT_TYPE", $_SERVER) == "application/json") {
            $data = file_get_contents('php://input');
            $this->body = json_decode($data);
        }

        unset($_GET);
        unset($_POST);
    }

    function hasCookie() {
        return array_key_exists('sid', $_COOKIE);
    }

    function getCookie($key): string {
        return $_COOKIE[$key];
    }

    function isPost(): bool {
        return $this->method == 'POST';
    }
}

class Response {
    public function redirect($url) {
        if (str_starts_with($url, "http")) {
            header("Location: $url");
        } else {
            echo "<!DOCTYPE html><html><body>
                    <script type=\"text/javascript\">document.location.href=\"$url\"</script>
                    </body>
                    </html>";
        }
        die;
    }

    public function setCookie($name, $value) {
        setcookie($name, $value);
    }

    public function removeCookie($name) {
        unset($_COOKIE[$name]);
        setcookie($name, '', -1);
    }
}

//TODO zredukowac cwd
class App {
    public $start;
    public $env;
    public $cwd;
    public $directory;
    public $settings;
    public $aliases = [];
    public $hooks = [];
    public $routes = [];
    public $view;
    public $di;

    function __construct($directory) {
        $this->start = microtime(true);
        $this->hooks =  ['before' => [], 'after' => []];
        $this->directory = $directory;
        $this->env = getenv("APP_ENV");
        $this->cwd = getcwd();
        $this->view = new View();
        $this->di = new Di();
    }
    public function hook($executionStage, $fun) : void {
        if (!in_array($executionStage, ['before', 'after'])) {
            throw new \Exception("Unrecognized execution type of filter $executionStage");
        }

        $this->hooks[$executionStage][] = $fun;
    }

    function directories($aliases): void {
        $this->aliases = array_merge($this->aliases, $aliases);
        $this->aliases['@'] = $this->directory;
    }
    
    public function route($key, $value): void { $this->routes[$key] = $value; }

    public function settings($filePath) {
        $filePath = $this->directory . '/' . $filePath;
        err(file_exists($filePath), 500, "Settings [$filePath] doesn't exist");
        $json = file_get_contents($filePath);
        $this->di['settings'] = $this->settings = json_decode($json);
    }

    public function run(): void {
        function log($message) {
            $stream = fopen('php://stderr', 'w');
            fwrite($stream, $message);
        }

        try {
            $requestUri = $this->getRequestUri();
            $queryString = $this->getQueryString();

            $route = $this->findRoute($requestUri);

            $request = new Request($requestUri, $route->parameters);
            $response = new Response();

            exp($route != null, 404, "Route doesn't exist");

            log("[request: $requestUri] [query: $queryString] [route: $route->pattern] \n");

            foreach($this->hooks['before'] as $hook) {
                $hook($request, $response, $this->di);
            }

            $executable = $this->getRouteExecutable($route, $request);
            $result = $executable($request, $response, $this->di);

            $elapsed = round(microtime(true) - $this->start, 3);
            log("time elapsed $elapsed \n");

            echo $result;
        }
        catch(Exception $e) {
            echo "CATCHED"; die;
        }
    }

    //returns request uri relative to application file
    //path/on/hdd/index.php/product/first /returns product/first
    private function getRequestUri() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $pos = strpos($_SERVER['REQUEST_URI'], "?");
        if ($pos) {
            $requestUri = substr($requestUri, 0, $pos);
        }

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

    private function getQueryString() {
        return array_key_exists('QUERY_STRING', $_SERVER) ? $_SERVER['QUERY_STRING'] : "";
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

    private function getRouteExecutable(ExecutionRoute $route, $request) {
        if ($route->isCallable()) {
            return $route->callback;
        } else {
            $file = $this->directory . '/' . $route->getExecutionFilePath();
            if (!is_file($file)) { echo "500 - ROUTE ENDPOINT [$file] doesn't exist"; die; }
            ob_start();
            include $file;
            ob_get_clean();

            $function = $request->method . $route->parameters->action;
            if (!function_exists($function)) { echo "500 - ACTION doesn't exist'"; die; }
            return $function;
        }
    }
}
class RouteParameters {
    public $file;
    public $action = "index";
    public array $parameters = [];
}

class ExecutionRoute {
    private $execution;
    public $pattern;

    public $parameters;

    function __construct($pattern, $execution, $routeParameters) {
        $this->pattern = $pattern;
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

function getFromDi($key) {
    return STORM::$instance->di[$key];
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

        exp(array_key_exists($alias, $aliases), 500, "Alias [$alias] doesn't exist" );

        $templatePath = $appDirectory . "/" . $aliases[$alias] . $path;
    }

    return $templatePath;
}
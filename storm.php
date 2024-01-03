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

    private $helperFilePath;

    function layouts($templates) {
        $this->_layouts = $templates;
    }

    function helpers($helperFilePath) {
        $this->helperFilePath = aliasPath($helperFilePath);
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

        if ($this->helperFilePath) {
            $expMessage = "HELPER [$this->helperFilePath] doesn't exist";
            exp(file_exists($this->helperFilePath), 500, $expMessage);
            include $this->helperFilePath;
        }

        include $cachedTemplateFilePath;
    }

    private function putInLayout($content) {
        preg_match('/@layout\s(.*?)\s/i', $content, $matches);
        if (!count($matches)) return $content;
        exp(array_key_exists($matches[1], $this->_layouts), 500, "Layout [$matches[1]] doesn't exist");
        $content = str_replace($matches[0], '', $content);

        $layoutFilePath = $this->_layouts[$matches[1]];
        $layoutFilePath = aliasPath($layoutFilePath);
        exp(file_exists($layoutFilePath), 500, "Layout [$layoutFilePath] doesn't exist");

        $layoutContent = file_get_contents($layoutFilePath);
        $layoutContent = $this->compile($layoutContent);
        $content = preg_replace('/@template/i', $content, $layoutContent);

        return $content;
    }

    private function compile($content) {
        $filterCompilationFunction = 'storm\_storm_preg_replace_function_filtering';
        $i18nCompilationFunction = 'storm\_storm_preg_replace_function_i18n';

        $content = preg_replace_callback('/{{\s*(.+?)\|\s*(.+?)}}/i',
            $filterCompilationFunction, $content);
        $content = preg_replace_callback('/{{\s*_(.+?)}}/i', $i18nCompilationFunction, $content);

        $content = preg_replace('/{{\s*\$(.+?)\s*\}}/i', '<?php echo $$1 ?>', $content);
        $content = preg_replace('/{{\s*(.+?)\s*\}}/i', '<?php echo storm\\\$1 ?>', $content);

        $content = preg_replace('/@if\s*\((.*)\)/i', '<?php if($1): ?>', $content);
        $content = preg_replace('/@else/i', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/i', '<?php endif; ?>', $content);

        $content = preg_replace('/@foreach\s*\((.*)\)/i', '<?php foreach($1): ?>', $content);
        $content = preg_replace('/@endforeach/i', '<?php endforeach; ?>', $content);

        return $content;
    }
}
function _storm_preg_replace_function_i18n($matches) {
    $arg = $matches[1];
    $arg = trim($arg);

    if (!str_starts_with($arg, '$')) {
        $arg = '"' . $arg . '"';
    }

    return "<?php echo storm\_($arg) ?>";
}

function _storm_preg_replace_function_filtering($matches) {
    $filterNodes = cleanExplode(' ', trim($matches[2]), 2);
    $filterName = $filterNodes[0];
    $filterArguments = $matches[1];
    if (count($filterNodes) > 1) {
        for($i = 1; $i < count($filterNodes); $i++) {
            $arg = $filterNodes[$i];
            if (is_numeric($arg))
                $filterArguments .= ",$arg";
            else
                $filterArguments .= ",'$arg'";
        }
    }

    return "<?php echo storm\\$filterName ($filterArguments) ?>";
}

class I18n {
    public $local = 'us-US';
    public $dateFormat = "Y-m-d";
    public $dateTimeFormat = "Y-m-d H:i";
    public $currency = "USD";
    public $translations = [];

    public function load($filePath) {
        $path = aliasPath($filePath);
        exp(file_exists($path), 500, "TRANSLATION file [$path] doesn't exist");
        $this->translations = json_decode(file_get_contents($path), true);

        foreach(['dateFormat', 'dateTimeFormat', 'currency', 'local'] as $key) {
            if (array_key_exists($key, $this->translations)) {
                $this->$key = $this->translations[$key];
            }
        }
    }

    public function translate($phrase){
        if (array_key_exists($phrase, $this->translations)){
            return $this->translations[$phrase];
        }

        return $phrase;
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

class RequestParameters implements \ArrayAccess {
    public $parameters = [];

    function __construct(...$parameterArrays) {
        foreach ($parameterArrays as $array)
            $this->parameters = array_merge($this->parameters, $array);
    }

    public function offsetGet($key): mixed {
        if (!$this->offsetExists($key)) {
            throw new \Exception("PARAMETER [$key] doesn't exist");
        }
        return $this->parameters[$key];
    }

    public function offsetSet($key, $value): void {
        $this->parameters[$key] = $value;
    }

    public function offsetExists($key): bool {
        return array_key_exists($key, $this->parameters);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->parameters[$offset]);
    }

    public function exists(...$keys) {
        foreach($keys as $key) {
            if (!$this->exist($key)){
                return false;
            }
        }

        return true;
    }
    public function exist($key) {
        return $this->offsetExists($key);
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
        $this->parameters = new RequestParameters($_GET, $_POST, $routeParameters->parameters);
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

    function has($name) {

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
    public $i18n;

    function __construct($directory) {
        $this->start = microtime(true);
        $this->hooks =  ['before' => [], 'after' => []];
        $this->directory = $directory;
        $this->env = getenv("APP_ENV");
        $this->cwd = getcwd();
        $this->view = new View();
        $this->di = new Di();
        $this->di['i18n'] = $this->i18n = new I18n();
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

    private function getRouteExecutable(ExecutionRoute $route) {
        if ($route->isCallable()) {
            return $route->callback;
        } else {
            $file = $this->directory . '/' . $route->getExecutionFilePath();
            if (!is_file($file)) { echo "500 - ROUTE ENDPOINT [$file] doesn't exist"; die; }
            ob_start();
            include $file;
            ob_get_clean();

            $function = $route->parameters->action;
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

function __($key) {
    return STORM::$instance->di[$key];
}

function _($phrase) {
    $i18n = STORM::$instance->i18n;
    return $i18n->translate($phrase);
}

function date($date, $format = null) {
    if (!$date) return '';
    if (!is_object($date)) {
        $date = new \DateTime($date);
    }
    if ($format == null) {
        $i18n = __('i18n');
        $format = $i18n->dateFormat;
    }
    return $date->format($format);
}

function datetime($date, $format = null) {
    if (!$date) return '';
    if (!is_object($date)) {
        $date = new \DateTime($date);
    }
    if ($format == null) {
        $i18n = __('i18n');
        $format = $i18n->dateTimeFormat;
    }
    return $date->format($format);
}

function money($value, $currency = null) {
    $i18n = __('i18n');
    if (!$currency)
        $currency = $i18n->currency;
    $fmt = numfmt_create($i18n->local, \NumberFormatter::CURRENCY );
    return numfmt_format_currency($fmt, $value, $currency);
}

function url($path, $args) {
    if (count($args)) {
        $path = $path . "?" . http_build_query($args);
    }

    return $path;
}

function cleanExplode($delimiter, $string, $limit = PHP_INT_MAX): array {
    $partsUnclean = explode($delimiter, $string, $limit);
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
    if (str_starts_with($templatePath, "@/")) {
        return str_replace("@", $appDirectory, $templatePath);
    }
    else if (str_starts_with($templatePath, '@')) {
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


<?php

namespace storm;

function app() {  return new App(); }

function cleanExplode($delimiter, $string) {
    $partsUnclean = explode($delimiter, $string);
    $parts = [];
    foreach ($partsUnclean as $part) {
        if ($part != "") 
            $parts[] = $part;
    }
    return $parts;
}

class ExecutionRoute {
    public $key = "";
    public $parameters = [];
    public $path;
    public $action = "index";
    public $method;
    function __construct($key) { $this->key = $key; }
 };

class App {
    private $filters = [];
    private $routes = [];

    function filter($fun) {}
    
    function route($key, $value) { $this->routes[$key] = $value; }
    
    function run() {     
        $requestUri = $this->getRequestUri();
        $route = $this->findRoute($requestUri);
        
        echo "key: $route->key </br>";
        echo "path: $route->path </br>"; 
        echo "action: $route->action </br>";
        print_r($route->parameters);
    }

    //returns request uri relative to application file
    //path/on/hdd/index.php/product/first /returns product/first
    private function getRequestUri() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $endOfFirstSegment = strpos($requestUri, "/", 1);
        if ($endOfFirstSegment) {
            $getcwd = str_replace("\\", "/", getcwd());
            $search = substr($requestUri, 0, $endOfFirstSegment);
            $lastOccuranceOf = strripos($getcwd, $search);
            $common = substr($getcwd, $lastOccuranceOf);
            $requestUri = str_replace($common, "", $requestUri);
            $requestUri = $requestUri == "" ?  "/": $requestUri;
        }

        return $requestUri;
    }

    private function findRoute($requestUri) {
        foreach($this->routes as $key => $value) {
            if ($key == $requestUri) {
                return new ExecutionRoute($key);
            }
        }
        
        $requestUriPath = cleanExplode("/", $requestUri);
        foreach($this->routes as $key => $value) {
            $routePart = cleanExplode("/", $key);
            
            if (count($routePart) == count($requestUriPath)) {
                $executionRouter = new ExecutionRoute($key);
                foreach($routePart as $index => $part) {
                    if (str_starts_with($part, "[") && str_ends_with($part, "]")) {
                        $parameter = str_replace(["[", "]"], "", $part);
                        if ($parameter == "file") {
                            $executionRouter->path .= "/" . $requestUriPath[$index];
                        }
                        if ($parameter == "action") {
                            $executionRouter->action = $requestUriPath[$index];
                        }
                    }
                    else if (str_starts_with($part, "{") && str_ends_with($part, "}")) {
                        $parameter = str_replace(["{", "}"], "", $part);
                        $executionRouter->parameters[$parameter] = $requestUriPath[$index];
                    }
                    else if ($part == $requestUriPath[$index]) {
                        $executionRouter->path .= "/" . $requestUriPath[$index];
                    }
                    else {
                        break;
                    }

                    if (count($routePart) == $index + 1)
                        return $executionRouter;
                }
            }
        }

        return null;
    }
}
?>
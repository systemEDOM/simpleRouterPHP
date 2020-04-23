<?php

namespace EDOM\SimpleRouterPHP;

class Router
{

    private $routes = array();
    private $namespace;

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $matchTypes = [
        'i'  => '[0-9]++',
        's'  => '[0-9A-Za-z]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    public function __construct($namespace = null)
    {
        if ($namespace != null) {
            $this->namespace = $namespace;
        }
    }

    /**
     * Add a route to the array where mantain all the system routes
     *
     * @param [string] $uri
     * @param [string] $method
     * @param [string or Closure] $action
     * @return void
     */
    private function addRoute($uri, $method, $action)
    {
        $uri = '/' . trim($uri, '/');
        $complete_uri = array("uri" => $uri, "method" => $method, "action" => ($action != null) ? $action : null);
        $this->routes[] = $complete_uri;
    }

    /**
     * Add a route with method GET
     *
     * @param [string] $uri
     * @param [string or Closure] $action
     * @return void
     */
    public function get($uri, $action = null)
    {
        $this->addRoute($uri, "GET", $action);
    }

    /**
     * Add a route with method POST
     *
     * @param [string] $uri
     * @param [string or Closure] $action
     * @return void
     */
    public function post($uri, $action = null)
    {
        $this->addRoute($uri, "POST", $action);
    }

    /**
     * Add a route with method PUT
     *
     * @param [string] $uri
     * @param [string or Closure] $action
     * @return void
     */
    public function put($uri, $action = null)
    {
        $this->addRoute($uri, "PUT", $action);
    }

    /**
     * Add a route with method DELETE
     *
     * @param [string] $uri
     * @param [string or Closure] $action
     * @return void
     */
    public function delete($uri, $action = null)
    {
        $this->addRoute($uri, "DELETE", $action);
    }

    /**
     * Find match of the current url in browser and the routes saved before in the array
     *
     * @return void
     */
    public function match()
    {
        $isMatch = false;
        $currUri = $this->getUri();

        foreach ($this->routes as $key => $route) {
            $regex = $this->compileRoute($route["uri"]);
            $match = preg_match($regex, $currUri, $params) === 1;
            $currMethod = isset($_POST['_method']) ? strtoupper($_POST['_method']) : $_SERVER['REQUEST_METHOD'];

            if ($match && $currMethod == $route["method"]) {
                $this->workOnPutAndDeleteMethods($currMethod);
                if (sizeof($params) > 0) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }
                
                $isMatch = true;
                $action = $route["action"];
                $this->runAction($action, $params);
                break;
            }
        }

        if (!$isMatch) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
    }

    /**
     * Check if the current method is PUT or DELETE and convert every variable to POST
     *
     * @param [string] $currMethod
     * @return void
     */
    private function workOnPutAndDeleteMethods($currMethod)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
            parse_str(file_get_contents("php://input"), $_PUT);
            foreach ($_PUT as $key => $value) {
                unset($_PUT[$key]);
                $_PUT[str_replace('amp;', '', $key)] = $value;
            }
            $_POST = array_merge($_POST, $_PUT);
            $_REQUEST = array_merge($_REQUEST, $_PUT);
        }
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param $route
     * @return string
     */
    protected function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }

    /**
     * Execute the clouse if there is or create an instance of controller an execute the method
     *
     * @param [string or Closure] $action
     * @return void
     */
    private function runAction($action, $params = null)
    {
        if ($action instanceof \Closure) {
            call_user_func_array($action, $params);
        } else {
            $resource = explode('@', $action);
            $class = $this->namespace . $resource[0];
            $obj = new $class;
            call_user_func_array(array($obj, "$resource[1]"), $params);
        }
    }


    /**
     * This method return the base url if the project is inside a subfolder return it otherwise return "/"
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        return $pathInfo['dirname'] . "/";
    }


    /**
     * This method return the url base or route without the subfolder if exists
     *
     * @return string
     */
    private function getUri()
    {
        $path_info = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "/";
        $path_info = substr($path_info, strlen($this->getBaseUrl()) - 1);
        $path_info = rtrim($path_info, "/");
        return $path_info;
    }
}

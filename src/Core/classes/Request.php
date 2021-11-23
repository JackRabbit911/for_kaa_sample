<?php
namespace WN\Core;

use WN\Core\Core;
// use WN\Core\Request;
use WN\Core\Response;
use WN\Core\Route;
// use WN\Core\Exception;
use WN\Core\Helper\HTTP;
//use Core\Cache;
//use Core\I18n;
use WN\Core\Helper\Arr;
use WN\Core\Request\Accept;
//use Core\Request\Headers;

Class Request
{
    // public $accept;
//    public $headers;

    // public $cache_lifetime = 0;

    public $controller;
    
    /**
     * Instance of the main Request
     * 
     * @var Request object
     */
    protected static $_initial;
    
    /**
     * Instance of the current Request
     * 
     * @var Request object
     */
    protected static $_current;
    
    
    public $_uri;
    public $_url;
    protected $_query = array();
    protected $_post; // = array();   
    protected $_method = 'GET';
    protected $_params = array();   
    // protected $_client_ip = NULL;    
    protected $_requested_with = NULL;
    
    
    
    /**********************************************/
    /**
     * @var  string  trusted proxy server IPs
     */
    // protected static $trusted_proxies = array('127.0.0.1', 'localhost', 'localhost.localdomain');
    
    protected $_referrer = '';
    
    // protected $_user_agent = '';
    
    protected static $_headers;

    // protected $_is_initial = FALSE;

    public static function factory($uri = NULL)
    {
        return new static($uri);
    }

    public function __construct($uri = NULL)
    {
        // var_dump($uri); exit;
        if($uri === NULL) 
        {            
            $this->_uri = HTTP::detect_uri();
        }
        else $this->_uri = $uri;

        if(static::$_initial === NULL) static::$_initial = & $this;
        
        static::$_headers = apache_request_headers();

        
    }
    
    public static function headers($key = NULL)
    {
        if($key === NULL) return static::$_headers;
        else
        {
            $headers = array_change_key_case(static::$_headers);
            return Arr::get($headers, strtolower($key));
        }
    }

    public function __toString()
    {
        return (string) $this->execute();
    }
 
    public function uri()
    {
        if(!$this->_uri) $this->_uri = HTTP::detect_uri();
        return '/'.ltrim($this->_uri, '/');
    }
    
    public function url()
    {
        if(!$this->_url) $this->_url = HTTP::url();
        return $this->_url;
    }
    
    public function execute()
    {
        
        static::$_current = & $this;
        
        if(empty($this->_params))
        {
            $this->_params = Route::get_params($this);
        }

        // die('lala');

            $controller = $this->_params['controller'] ?? FALSE;

            // var_dump($controller); exit;

            $controller = (class_exists($controller)) ? $controller : FALSE;

            $action =  Arr::get($this->_params, 'action', 'index');
            $action = (empty($action)) ? 'index' : $action;
            $action = (method_exists($controller, $action)) ? $action : FALSE;

            // $modules = Autoload::modules(Autoload::$root_folder);
            // var_dump($modules, Autoload::$class_paths); exit;

            if(!$controller)
            {
                throw new Exception\WnException('Controller ":controller" in route ":uri" not found',
                        [':controller'=>$this->_params['controller'], ':uri'=>Route::$current], 404);
            }

            if(!$action)
            {
                throw new Exception\WnException('Action ":action" in controller ":controller" not found, route ":route"',
                        [':controller'=>$controller, ':action'=>$this->_params['action'], ':route'=>Route::$current], 404);
            }

            $this->controller = new $controller($this, $action);

            // $this->response = &$controller->response;

            return $this->controller->execute();
    }

    public function params($params = NULL, $default = NULL)
    {
        if($params === NULL) return $this->_params;
        elseif(is_array($params))
        {
            $this->_params = array_merge($this->_params, $params);
            return $this;
        }       
        elseif(is_string ($params))
        {
            return Arr::get($this->_params, $params, $default);
        }
        else throw new Exception\WnException('Invalid argument in function "params()"');
    }
    
    public function method($method = NULL)
    {
        if ($method === NULL)
        {
            if (isset($_SERVER['REQUEST_METHOD']))
            {
                // Use the server request method
                $this->_method = $_SERVER['REQUEST_METHOD'];
            }
                // Act as a getter
            return $this->_method;
        }

        // Act as a setter
        $this->_method = strtoupper($method);

        return $this;
    }
    
    public function query($key=NULL)
    {
        if(empty($this->_query)) parse_str(parse_url($this->_uri, PHP_URL_QUERY), $this->_query);
        if($key === NULL) return $this->_query;
        else return Arr::get($this->_query, $key);
    }

    public function path()
    {
        return parse_url($this->_uri, PHP_URL_PATH);
    }
    
    public function post($post = NULL)
    {
        if(empty($this->_post)) 
            $this->_post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        if($post === NULL)
        {
            return $this->_post;
        } 
        elseif(is_array($post))
        {
            $this->method('post');
            $this->_post = $post;
            return $this;
        }
        else return Arr::get($this->_post, $post);
    }
    
    /**
     * Gets and sets the requested with property, which should
     * be relative to the x-requested-with pseudo header.
     *
     * @param   string    $requested_with Requested with value
     * @return  mixed
     */
    public function requested_with($requested_with = NULL)
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
        {
            // Typically used to denote AJAX requests
            $this->_requested_with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
        }

        if ($requested_with === NULL)
        {
                // Act as a getter
                return $this->_requested_with;
        }

        // Act as a setter
        $this->_requested_with = strtolower($requested_with);

        return $this;
    }

    /**
     * Returns whether this is an ajax request (as used by JS frameworks)
     *
     * @return  boolean
     */
    public function is_ajax()
    {
        return ($this->requested_with() === 'xmlhttprequest');
    }


    /**
     * Returns the first request encountered by this framework. This will should
     * only be set once during the first [Request::factory] invocation.
     *
     *     // Get the first request
     *     $request = Request::initial();
     *
     *     // Test whether the current request is the first request
     *     if (Request::initial() === Request::current())
     *          // Do something useful
     *
     * @return  Request
     * @since   3.1.0
     */
    public static function initial()
    {
        return static::$_initial;
    }

    /**
     * Returns whether this request is the initial request Kohana received.
     * Can be used to test for sub requests.
     *
     *     if ( ! $request->is_initial())
     *         // This is a sub request
     *
     * @return  boolean
     */
    public function is_initial()
    {
        return ($this === static::$_initial);
    }

    public static function current()
    {
        return static::$_current;
    }

    public function protocol()
    {
        return filter_input(INPUT_SERVER, 'REQUEST_SCHEME', FILTER_SANITIZE_URL);
    }

    public function secure()
    {

    }

    public function domain()
    {
        return filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
    }

    public function route()
    {
        return Route::current();
    }

    public function client_ip()
    {
        return HTTP::client_ip();
    }
    
    public function user_agent()
    {
        return HTTP::user_agent();
    }
    
    public function referer($referer = null)
    {
        // return $_SERVER['HTTP_REFERER'] ?? 'huy';
        // return HTTP::referer($referer);

        if($referer === null)
        {
            if(empty($this->_referrer))
            {
                return HTTP::referer();
                // if(isset($_SERVER['HTTP_REFERER']))
                //     $this->_referrer = $_SERVER['HTTP_REFERER'];
                // else $this->_referrer = BASEDIR;
            }
            
            return $this->_referrer;
        }
        else
        {
            $this->_referrer = $referer;
            return $this;
        }
    }
}
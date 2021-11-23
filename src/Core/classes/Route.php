<?php
namespace WN\Core;

use WN\Core\Helper\{Arr, URL};
use WN\Core\Autoload;

Class Route
{
    
    // Matches a URI group and captures the contents
    const REGEX_GROUP   = '\(((?:(?>[^()]+)|(?R))*)\)';

    // Defines the pattern of a <segment>
    const REGEX_KEY     = '<([a-zA-Z0-9_]++)>';

    // What can be part of a <segment> value
    const REGEX_SEGMENT = '[^/.,;?\n]++';

    // What must be escaped in the route regex
    const REGEX_ESCAPE  = '[.\\+*?[^\\]${}=!|]';
    
    public static $current;
    
    protected static $_routes = [];

    public $params = [];

    public $request_uri;
    
    protected $_filters = [];

    protected $_defaults = ['controller' => 'index', 'action'=>'index', /*'namespace' => 'WN\App'*/];

    protected $_regex = [];

    protected $_route_regex;
    protected $_closure = [];
    protected $_required_method = NULL;

    protected $_directory = '\Controller\\';

    public $_uri = '';
    public $_name = '';
    protected $_bottom = 0;

    /**
     * Creates a new route. Sets the URI and regular expressions for keys.
     * Routes should always be created with [Route::set] or they will not
     * be properly stored.
     *
     *     $route = new Route($uri, $regex);
     *
     * The $uri parameter should be a string for basic regex matching.
     *
     *
     * @param   string  $uri    route URI pattern
     * @param   array   $regex  key patterns
     * @return  void
     * @uses    Core\Route::compile
     */
    public function __construct($uri, $regex = null)
    {
            $this->_uri = $uri;
            

           if ( ! empty($regex))
           {
                $this->_regex = $regex;
           }

            // Store the compiled regex locally
//            $this->_route_regex = self::compile($uri, $regex);
    }
    
    public static function set($name, $uri = NULL, $regex = NULL)
    {
        // var_dump($uri);

        if(array_key_exists($name, static::$_routes))
        {
            // die(static::$_routes[$name]->_uri);
            // var_dump(static::$_routes[$name]);
            // exit;
            return static::$_routes[$name];
        }
        else
        {
            $route = new static($uri, $regex);
            $route->_name = $name;
            return static::$_routes[$name] = $route;
        }
    }
    
    public static function all()
    {
        uasort(static::$_routes, function($a, $b) {
            return $a->_bottom <=> $b->_bottom;
        });

        return static::$_routes;
    }
    
    /**
     * Process a request to find a matching route.
     *
     * @param   $request Request.
     * @param   array   $routes  Route.
     * @throws 404.
     * @return  array
     */
    public static function get_params(Request $request, $routes = NULL)
    {           
            // Load routes
            $routes = (empty($routes)) ? static::all() : $routes;

            // var_dump($routes); exit;

            foreach ($routes as $name => $route)
            {
                if ($route->matches($request))
                {
                    static::$current = $name;
                    return $route->params;
                }
            }

            // exit;
            // if(Core::$errors)
            throw new Exception\WnException('The requested route not match', null, 404);
    }
    
    public function compile()
    {
        // var_dump($this->_regex); exit;
        // The URI should be considered literal except for keys and optional parts
        // Escape everything preg_quote would escape except for : ( ) < >
        $expression = preg_replace('#'.self::REGEX_ESCAPE.'#', '\\\\$0', $this->_uri);

        if (strpos($expression, '(') !== FALSE)
        {
                // Make optional parts of the URI non-capturing and optional
                $expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
        }

        // Insert default regex for keys
        $expression = str_replace(array('<', '>'), array('(?P<', '>'.self::REGEX_SEGMENT.')'), $expression);

        if (!empty($this->_regex))
        {
                $search = $replace = array();
                foreach ($this->_regex as $key => $value)
                {
                    $value = strtolower($value);
                    $search[]  = "<$key>".self::REGEX_SEGMENT;
                    $replace[] = "<$key>$value";
                }

                // Replace the default regex with the user-specified regex
                $expression = str_replace($search, $replace, $expression);
        }

        return '#^'.$expression.'$#uD';
    }
    
    public function regex(array $regex = NULL)
    {
        if($regex === NULL)
            return $this->_regex;
        
        $this->_regex += $regex;
        return $this;
    }
    
    
    /**
     * Provides default values for keys when they are not present. The default
     * action will always be "index" unless it is overloaded here.
     *
     *     $route->defaults(array(
     *         'controller' => 'welcome',
     *         'action'     => 'index'
     *     ));
     *
     * If no parameter is passed, this method will act as a getter.
     *
     * @param   array   $defaults   key values
     * @return  $this or array
     */
    public function defaults(array $defaults = NULL)
    {
            if ($defaults === NULL)
            {
                    return $this->_defaults;
            }

            // if(isset($defaults['controller']))
            // {

            // }

            $this->_defaults = array_merge($this->_defaults, $defaults);

            return $this;
    }
    
    /**
     * Filters to be run before route parameters are returned:
     *
     *     $route->filter(
     *         function(Route $route, $params, Request $request)
     *         {
     *             if ($request->method() !== HTTP_Request::POST)
     *             {
     *                 return FALSE; // This route only matches POST requests
     *             }
     *             if ($params AND $params['controller'] === 'welcome')
     *             {
     *                 $params['controller'] = 'home';
     *             }
     *
     *             return $params;
     *         }
     *     );
     *
     * To prevent a route from matching, return `FALSE`. To replace the route
     * parameters, return an array.
     *
     * [!!] Default parameters are added before filters are called!
     *
     * @throws  Exception
     * @param   array   $filter   callback string, array, or closure
     * @return  $this
     */
    public function filter($filter)
    {
        if ( is_callable($filter))
        {
            $this->_filters[] = $filter;
        }
        elseif(is_array($filter))
        {
            $this->_regex += $filter;
        }
        else
        {
            throw new Exception\WnException('Invalid argument to filter: array or callback specified');
        }

        return $this;
    }
    
    public function method()
    {
        $this->_required_method = array_map('strtoupper', func_get_args());
        return $this;
    }
    
//     public function subdomain($subdomain)
//     {
// //        if(!is_array($subdomain)) $subdomain = [$subdomain];
        
//         if(Arr::is_assoc($subdomain))
//         {
// //            $this->_subdomain = array_keys($subdomain);
// //            $this->_namespace = array_values($subdomain);
//             $this->_namespace = Arr::get($subdomain, self::$subdomain);
//         }
//         return $this;
//     }

    public function matches(Request & $request)
    {
        if($this->_defaults)
        {
            foreach ($this->_defaults as $key => $value)
            {
                $this->params[$key] = $value;
            }
        }

        // Get the URI from the Request
        $this->request_uri = trim($request->uri(), '/');

        

        $_route_regex = $this->compile();

        
        
        if(!empty($_route_regex))
        {
            // var_dump($_route_regex, $this->request_uri);
            // exit;

            if ( ! preg_match($_route_regex, $this->request_uri, $matches))
                    return FALSE;

                   

            foreach ($matches as $key => $value)
            {
                    if (is_int($key))
                    {
                            // Skip all unnamed keys
                            continue;
                    }

                    // Set the value for all matched keys
                    $this->params[$key] = $value;
            }
        }

        // var_dump($this->params);
            
        if($this->_required_method && is_array($this->_required_method))
        {
            if($key = array_search('AJAX', $this->_required_method))
            {
                unset($this->_required_method[$key]);
                if($request->is_ajax() === FALSE) return FALSE; 
            }
            if(!in_array($request->method(), $this->_required_method)) return FALSE;
        }
           
        if(!empty($this->params['directory']))
        {
            $this->_directory .= ucfirst($this->params['directory']).'\\';
        }

        // var_dump($this->params['controller']);

        if(!class_exists($this->params['controller']))
        {
            foreach(Autoload::$class_paths AS $path => $namespace)
            {
                if(class_exists(($controller = $namespace.$this->_directory.ucfirst($this->params['controller']))))
                {
                    $this->params['controller'] = $controller;
                    break;
                }
            }
        }

        // var_dump($this->params['controller'], Autoload::$class_paths);

        // if(isset($this->params['class_controller'])) var_dump($this->params['class_controller']);

        if($this->_filters)
        {
            $params = [];

            foreach ($this->_filters as $callback)
            {
                // Execute the filter giving it the route, params, and request
                // Filter has aborted the match
                
                $return = call_user_func($callback, $this->params);
                if($return === false) return false;
                elseif(is_array($return)) $params = array_replace($this->params, $return);
            }

            $this->params = array_replace($this->params, $params);
        }

        return TRUE;
    }
    
    public static function get($name)
    {
        if ( ! isset(Route::$_routes[$name]))
        {
            throw new Exception\WnException('The requested route does not exist: :route',
                    array(':route' => $name));
        }

        return Route::$_routes[$name];
    }
    
    public static function current()
    {
        return static::get(static::$current);
    }
    
	/**
	 * Generates a URI for the current route based on the parameters given.
	 *
	 *     // Using the "default" route: "users/profile/10"
	 *     $route->uri(array(
	 *         'controller' => 'users',
	 *         'action'     => 'profile',
	 *         'id'         => '10'
	 *     ));
	 *
	 * @param   array   $params URI parameters
	 * @return  string
	 * @throws  Core\Exception\WN_Exception
	 * @uses    Core\Route::REGEX_GROUP
	 * @uses    Core\Route::REGEX_KEY
	 */
	public function uri(array $params = NULL)
	{
        if(isset($params['query']))
        {
            $query = $params['query'];
            unset($params['query']);
        }
        else $query = NULL;

        // var_dump($params);

		if ($params)
		{
            if(isset($params['controller'])) 
                $params['controller'] = strtolower(basename(str_replace('\\', '/', $params['controller'])));

            if(isset($params['lang']))
                $params['lang'] = trim($params['lang']).'/';

			// @issue #4079 rawurlencode parameters
			$params = array_map('rawurlencode', $params);
			// decode slashes back, see Apache docs about AllowEncodedSlashes and AcceptPathInfo
			$params = str_replace(array('%2F', '%5C'), array('/', '\\'), $params);
        }
        
		$defaults = $this->_defaults;

        // if(isset($defaults['controller'])) $defaults['controller'] = basename(str_replace('\\', '/', $defaults['controller']));

        // var_dump($defaults['controller']);

		/**
		 * Recursively compiles a portion of a URI specification by replacing
		 * the specified parameters and any optional parameters that are needed.
		 *
		 * @param   string  $portion    Part of the URI specification
		 * @param   boolean $required   Whether or not parameters are required (initially)
		 * @return  array   Tuple of the compiled portion and whether or not it contained specified parameters
		 */
		$compile = function ($portion, $required) use (&$compile, $defaults, $params)
		{
            
			$missing = array();

			$pattern = '#(?:'.Route::REGEX_KEY.'|'.Route::REGEX_GROUP.')#';
			$result = preg_replace_callback($pattern, function ($matches) use (&$compile, $defaults, &$missing, $params, &$required)
			{
				if ($matches[0][0] === '<')
				{
					// Parameter, unwrapped
					$param = $matches[1];

					if (isset($params[$param]))
					{
						// This portion is required when a specified
						// parameter does not match the default
						$required = ($required OR ! isset($defaults[$param]) OR $params[$param] !== $defaults[$param]);

						// Add specified parameter to this result
						return $params[$param];
					}

					// Add default parameter to this result
					if (isset($defaults[$param]))
						return $defaults[$param];

					// This portion is missing a parameter
					$missing[] = $param;
				}
				else
				{
					// Group, unwrapped
					$result = $compile($matches[2], FALSE);

					if ($result[1])
					{
						// This portion is required when it contains a group
						// that is required
						$required = TRUE;

						// Add required groups to this result
						return $result[0];
					}

					// Do not add optional groups to this result
				}
			}, $portion);

			if ($required AND $missing)
			{
                return;

                            throw new Exception\WnException(
                                    'Required route parameter not passed: :param',
                                    array(':param' => reset($missing))
                            );
			}

			return array($result, $required);
		};

        list($uri) = $compile($this->_uri, TRUE);

        // var_dump($this->_uri);

		// Trim all extra slashes from the URI
		$uri = preg_replace('#//+#', '/', trim($uri, '/'));

//		if ($this->is_external())
//		{
//			// Need to add the host to the URI
//			$host = $this->_defaults['host'];
//
//			if (strpos($host, '://') === FALSE)
//			{
//				// Use the default defined protocol
//				$host = Route::$default_protocol.$host;
//			}
//
//			// Clean up the host and prepend it to the URI
//			$uri = rtrim($host, '/').'/'.$uri;
//		}

        if($query)
        {
            if(is_array($query)) $query = '?'.http_build_query($query);
            elseif(is_string($query) && !empty($query)) $query = '?'.ltrim($query, '?');
        }

		return '/'.$uri.$query;
	}

	public function param_exists($param)
    {
        return (strpos($this->_uri, $param)) ? true : false;
    }

    public function top()
    {
        $this->_bottom = -1;
        return $this;
    }

    public function bottom()
    {
        $this->_bottom = 1;
        return $this;
    }
    
}
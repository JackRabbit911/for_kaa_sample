<?php
namespace WN\Core;

//use Core\Helper\Arr;

use WN\Core\Exception\WnException;
use WN\Core\Core;

class View
{
    public static $path = 'views/';
    // public static $default_path = 'views/';
    public static $_global_data = array();
    public static $css = [];
    public static $js = [];
    public $_file;
    public $_data = array();
    
    
    
    public static function factory($file, $data = NULL)
    {  
        return new static($file, $data);
    }
    
    public static function set_global($key, $value = NULL)
    {
        if (is_array($key) OR $key instanceof \Traversable)
        {
            foreach ($key as $name => $value)
            {
                static::$_global_data[$name] = $value;
            }
        }
        else
        {
            static::$_global_data[$key] = $value;
        }
    }
    
    public function __construct($file, array $data = NULL)
    {
        $this->_file = $file;
        
        if ($data !== NULL)
        {
            // Add the values to the current data
            $this->_data = $data + $this->_data;           
        }
        
        
    }
    
    /**
     * Magic method, searches for the given variable and returns its value.
     * Local variables will be returned before global variables.
     *
     *     $value = $view->foo;
     *
     * [!!] If the variable has not yet been set, an exception will be thrown.
     *
     * @param   string  $key    variable name
     * @return  mixed
     * @throws  WN_Exception
     */
    public function & __get($key)
    {
        if (array_key_exists($key, $this->_data))
        {
            return $this->_data[$key];
        }
        elseif (array_key_exists($key, static::$_global_data))
        {
            return static::$_global_data[$key];
        }
        else
        {
            throw new WnException('View variable is not set: :var',[':var' => $key]);
        }
    }
    
    /**
     * Magic method, calls [View::set] with the same parameters.
     *
     *     $view->foo = 'something';
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    
     /**
     * Magic method, determines if a variable is set.
     *
     *     isset($view->foo);
     *
     * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
     *
     * @param   string  $key    variable name
     * @return  boolean
     */
    public function __isset($key)
    {
        return (isset($this->_data[$key]) OR isset(static::$_global_data[$key]));
    }
    
    
    /**
     * Magic method, unsets a given variable.
     *
     *     unset($view->foo);
     *
     * @param   string  $key    variable name
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->_data[$key], static::$_global_data[$key]);
    }
 
    public function __toString()
    { 
        return (string) $this->render();   
    }
    
    /**
     * Assigns a variable by name. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This value can be accessed as $foo within the view
     *     $view->set('foo', 'my value');
     *
     * You can also use an array or Traversable object to set several values at once:
     *
     *     // Create the values $food and $beverage in the view
     *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
     *
     * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
     * i.e. the object's standard properties will not be available in the view context.
     *
     * @param   string|array|Traversable  $key    variable name or an array of variables
     * @param   mixed                     $value  value
     * @return  $this
     */
    public function set($key, $value = NULL)
    {
        if (is_array($key) OR $key instanceof \Traversable)
        {
            foreach ($key as $name => $value)
            {
                $this->_data[$name] = $value;
            }
        }
        else
        {
            $this->_data[$key] = $value;
        }

        return $this;
    }
    
    /**
     * Assigns a value by reference. The benefit of binding is that values can
     * be altered without re-setting them. It is also possible to bind variables
     * before they have values. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This reference can be accessed as $ref within the view
     *     $view->bind('ref', $bar);
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  referenced variable
     * @return  $this
     */
    public function bind($key, & $value)
    {
            $this->_data[$key] = & $value;

            return $this;
    }

    protected function _find_file()
    {
        if(!is_array($this->_file)) $this->_file = [$this->_file];

        foreach($this->_file AS $f)
        {
            $filename = $f;

            if(!is_file($f))
            {
                $filename = $f = static::$path.$f;

                if(!is_file("$f.php"))
                    $f = Core::find_file($f);
                else $f .= '.php';
            }

            if(is_file($f))
            {
                $filename = $f;
                break;
            }
            
        }

        if(is_file($filename)) return $filename;
        else trigger_error("View file: '".$filename."' not found");
    }

    public function render($data = null)
    {
        // $filename = $this->_file;

        // if(!is_file($this->_file))
        // {
        //     $filename = $this->_file = static::$path.$this->_file;

        //     if(!is_file("$this->_file.php"))
        //         $this->_file = Core::find_file($this->_file);
        //     else $this->_file .= '.php';
        // }

        // // var_dump($this->)

        // if($this->_file === FALSE) trigger_error("View file: '".$filename."' not found");

        $this->_file = $this->_find_file();

        if($data) $this->_data = array_merge($this->_data, $data);
      
        unset($data);
        

        // Import the view variables to local namespace
        extract($this->_data, EXTR_SKIP);

        if (static::$_global_data)
        {
            // Import the global view variables to local namespace
            extract(static::$_global_data, EXTR_SKIP | EXTR_REFS);
        }
               
        ob_start();
        include $this->_file;
        return ob_get_clean();
    }

    public function get($key = NULL)
    {
        if($key !== NULL)
        {
            return $this->__get($key);
        }
        else
        {
            return array_merge_recursive(static::$_global_data, $this->_data);
        }
    }
    
    public function css()
    {
        $args = func_get_arg(0);
        if(!is_array($args)) $args = func_get_args();
        
        foreach($args AS $str)
        {
            if(!in_array($str, static::$css))
            {
                static::$css[] = $str;
            }
        }
        return $this;
    }
    
    public function js()
    {
        $args = func_get_arg(0);
        if(!is_array($args)) $args = func_get_args();

        if(in_array(FALSE, $args))
        {
            foreach($args AS $str)
            {
                if($str === FALSE) break;
                else
                {
                    if(in_array($str, static::$js))
                    {
                        $key = array_search($str, static::$js);
                        unset(static::$js[$key]);
                    }
                }

            }
        }
        else
        {
            foreach($args AS $str)
            {
                if(!in_array($str, static::$js))
                {
                    static::$js[] = $str;
                }
            }
        }

        return $this;
    }
}

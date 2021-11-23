<?php

namespace WN\Page;

use WN\Core\{Core, Request, View};
use WN\Core\Exception\WnException;

class TplWrap extends View
{
    const PHP = 'php';
    const TWIG = 'twig';

    public static $engine = self::TWIG;
    public static $twig_file_extension = 'twig';
    public static $path = 'views/';

    public function render($data = null)
    {
        if(static::$engine === self::PHP)
        {
            parent::$path = static::$path;
            return parent::render($data);
        }
        else
        {
            return $this->_twig_render($data);
        }        
    }

    protected function _twig_render($data = null)
    {
        if(!$data) $data = [];

        static $twig;
        if(!$twig) $twig = $this->_twig();

        if(static::$_global_data)
        {
            foreach(static::$_global_data AS $key => &$value)
                $twig->addGlobal($key, $value);
        }
        $tpl = $twig->load($this->_file.'.'.static::$twig_file_extension);

        $this->_data = array_merge($this->_data, $data);
        
        return $tpl->render($this->_data);
    }

    protected function _twig()
    {
        $env = (Core::$errors)
            ? ['debug' => true, 'strict_variables' => true]
            : [
                // 'cache' => 'src/App/tpl/compilation_cache',
            ];

        $paths = array_filter(array_map(function($v){
            if(is_dir(($dir = $v.'/'.static::$path))) return $dir;
        }, Core::paths()));

        $loader = new \Twig\Loader\FilesystemLoader($paths);
        $twig = new \Twig\Environment($loader, $env);

        $_gettext = new \Twig\TwigFunction('__', function ($text) {
            return __($text);
        });

        $_request = new \Twig\TwigFunction('_request', function ($url = null) {
            return _request($url);
        });

        $_call = new \Twig\TwigFunction('_call', function () {
            $args = func_get_args();
            $action = array_shift($args);
            if(is_array($action)) list($class, $func) = $action;
            else list($class, $func) = explode('::', $action);
            return call_user_func_array([new $class, $func], $args);
        });

        $_url = new \Twig\TwigFunction('_url', function ($route, $params = null) {
            return _url($route, $params);
        });

        $_set_classes = new \Twig\TwigFunction('_setClasses', function ($valid, $invalid) {
            \WN\Core\Validation\Response::set_classes($valid, $invalid);
            return null;
        });

        $_css = new \Twig\TwigFunction('_css', function () {
            $result = '';

            foreach(static::$css AS $str)
                $result .= '<link rel="stylesheet" href="'.$str.'">'.PHP_EOL."\t\t";

            return rtrim($result).PHP_EOL;
        });

        $_js = new \Twig\TwigFunction('_js', function () {
            $result = '';

            foreach(static::$js AS $str)
                $result .= '<script src="'.$str.'"></script>'.PHP_EOL."\t\t";

            return rtrim($result).PHP_EOL;
        });

        $twig->addFunction($_gettext);
        $twig->addFunction($_request);
        $twig->addFunction($_call);
        $twig->addFunction($_url);
        $twig->addFunction($_set_classes);
        $twig->addFunction($_css);
        $twig->addFunction($_js);

        return $twig;
    }

    // public static function hidden_input_action($file)
    // {
    //     include_once 'src/Core/vendor/simplehtmldom/simple_html_dom.php';
    //     $html_content = file_get_html($file);

    //     foreach($html_content->find('input[type="hidden"]') AS $element)
    //     {
    //         $name = $element->getAttribute("name");
    //         if($name === 'url' || $name == 'handler')
    //         {
    //             $value = $element->getAttribute("value");
    //             break;
    //         }
    //     }
    //     unset($html_content);

    //     if($name === 'url')
    //     {
    //         $pattern = '/^[a-zA-Z0-9\~\/?=&+]+$/';
    //         if(preg_match($pattern, $value) === 1)
    //             return Request::factory($value)->execute();
    //         else throw new WnException('Hidden input value is incorrect');
    //     }
    //     elseif($name === 'handler')
    //     {
    //         $pattern = '/^[a-zA-Z0-9\\\\(::)\[\]\(\),\s]+$/';
    //         if(preg_match($pattern, $value) === 1)
    //         {
    //             $args = preg_split('/[:\(\),\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    //             $class = array_shift($args);
    //             $func = array_shift($args);
    //             $object = new $class;
    //             return call_user_func_array([$object, $func], $args);
    //         }
    //         else throw new WnException('Hidden input value is incorrect');           
    //     }
    //     else throw new WnException('Hidden input "url" or "handler" not found');
    // }
}
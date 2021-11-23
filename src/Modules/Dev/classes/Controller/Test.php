<?php

namespace WN\Dev\Controller;

use WN\Core\{Controller, View, Core, Autoload};
use WN\Core\Helper\{HTML, Dir, Upload};

class Test extends Controller\Template
{
    public $template = 'src/Modules/Dev/views/template.php';

    protected static $dir = 'dev/test';

    protected $filepath;
    protected $active;

    protected function _before()
    {
        parent::_before();
        // Upload::$default_directory = 'public/tmp';
        // Dir::clean(Upload::$default_directory, null, 30);
        $this->template->header = null;
        $this->template->footer = null;
        $this->template->js('/media/js/test_form_submit.js');
        // $this->template->js('media/js/detect_resolution.js');
    }

    protected function _after()
    {
        if($this->request->is_ajax()) echo $this->result;
        else echo $this->template->render();    
    }

    public function index()
    {
        // die('qq');
        // if($this->request->is_ajax()) return;
        // var_dump(Core::paths(), Core::find_file('/media/img/200x200.svg'));
        // echo HTML::image('media/img/200x200.svg');
        // exit;

        // Dir::clean('public/test_upload'); 
        
        

        $modules = [];

        foreach(Autoload::modules(Autoload::$root_folder) as $module_path)
        {
            $pattern = ['/^(.*?'.Autoload::$root_folder.'[\\\\|\/])/i', '/'.Autoload::$modules_folder.'[\\\\|\/]/i'];
            $module_alias = preg_replace($pattern, '', $module_path);

            if(!empty($files = $this->_files($module_path.'/'.static::$dir, $module_alias)))
                $modules[$module_alias] = $files;
        }

        // var_dump($this->active);

        if($this->filepath && is_file($this->filepath))
        {
            $code = htmlspecialchars(file_get_contents($this->filepath), ENT_QUOTES);
            ob_start();
            include $this->filepath;
            $this->result = ob_get_contents();
            ob_get_clean();
        }           
        else $code = $this->result = null;

       $description = null;

       $content = View::factory('src/Modules/Dev/views/content.php');
       $content->sidebar = View::factory('src/Modules/Dev/views/sidebar.php');
       $content->sidebar->modules = $modules;
       $content->sidebar->active = $this->active;
       $content->code = $code;
       $content->result = $this->result;
       $content->description = $description;

       $this->template->content = $content;

       
    }

    protected function _files($module_path, $module_alias)
    {
        $result = [];

        // if(stripos($module_path, static::$dir) !== false) $dir = $module_path.'/'.static::$dir;
        // else $dir = $module_path;

        if(is_dir($module_path))
        {
            foreach(glob($module_path.'/*') as $path)
            {
                if(is_dir($path))
                {
                    $result = array_merge($result, $this->_files($path, $module_alias));
                    // var_dump($result, $path);
                }
                else
                {
                    // echo $path.'<br>';
                    // $title = str_replace(['src/', Autoload::$modules_folder.'/', 'Modules/', static::$dir.'/', '.php'], '', $path);

                    $pattern = [
                        '/^(.*?'.Autoload::$root_folder.'[\\\\|\/])/i',
                        '/'.Autoload::$modules_folder.'[\\\\|\/]/i',
                        // '/'.static::$dir.'[\\\\|\/]/i',
                        '/\.php/',
                    ];

                    $title = str_replace(static::$dir.'/', '', preg_replace($pattern, '', $path));

                    $uri = '/~test/'.strtolower($title);
                    // $result[$title]['path'] = $file; // HTML::anchor($uri, $title);
                    $result[ucwords($title, '/')] = $uri;

                

                    if($this->request->path() === ltrim($uri, '/'))
                    {
                        $this->filepath = $path;
                        $this->active['module'] = $module_alias;
                        $this->active['item'] = $title;
                    }
                }
            }
        }
        
        return $result;
    }
}
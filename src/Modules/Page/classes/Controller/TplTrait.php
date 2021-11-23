<?php
namespace WN\Page\Controller;

use WN\Core\{Session, Config};
use WN\Page\TplWrap;

trait TplTrait
{
    public static $tpl_name = 'default';

    protected function _template()
    {
        // $session = Session::instance();
        // if($session->tpl)
        // {
        //     $tpl_name = $session->tpl['name'] ?? null;
        //     $tpl_theme = $session->tpl['theme'] ?? null;
        // }

        $config = Config::instance();

        if(!isset($tpl_name))
            list($tpl_name, $tpl_theme) = $config->get('template');

        if(!$tpl_name) $tpl_name = static::$tpl_name;

        $config_dir = $this->path .= $tpl_name;

        // $tpl_path = $this->path;
        $this->path .= '/views/';

        parent::_before();


        Config::chdir($config_dir);
        $config_data = $config->get('config');
        Config::chdir();

        $this->wrapper = $config_data['wrapper'] ?? 'layout';

        if(isset($config_data['engine']))
                TplWrap::$engine = $config_data['engine'];

        if($config_data)
        {
            TplWrap::$css += $config_data['css'] ?? [];
            TplWrap::$js += $config_data['js'] ?? [];
        }

        TplWrap::$css = array_unique(TplWrap::$css);
        TplWrap::$js = array_unique(TplWrap::$js);
    }

    // protected function _tpl_settings($config, $config_dir)
    // {
    //     Config::chdir($config_dir);
    //     $config_data = $config->get('config');
    //     Config::chdir();

    //     $this->wrapper = $config_data['wrapper'] ?? 'layout';

    //     if(isset($config_data['engine']))
    //             TplWrap::$engine = $config_data['engine'];

    //     if($config_data)
    //     {
    //         TplWrap::$css += $config_data['css'] ?? [];
    //         TplWrap::$js += $config_data['js'] ?? [];
    //     }
    //     // else
    //     // {
    //     //     if(isset($tpl_theme))
    //     //     {
    //     //         $css_path = $tpl_path.'/css/'.$tpl_theme;
    //     //         $js_path = $tpl_path.'j/s/'.$tpl_theme;
    //     //     }
    //     //     else
    //     //     {
    //     //         $css_path = $tpl_path.'/css';
    //     //         $js_path = $tpl_path.'/js';
    //     //     }

    //     //     foreach(glob("$css_path/*.css") AS $file)
    //     //         TplWrap::$css[] = "/tpl/$tpl_name/css/".basename($file);


    //     //     foreach(glob("$js_path/*.js") AS $file)
    //     //         TplWrap::$js[] = "/tpl/$tpl_name/js/".basename($file);
    //     // }

    //     TplWrap::$css = array_unique(TplWrap::$css);
    //     TplWrap::$js = array_unique(TplWrap::$js);
    // }
}
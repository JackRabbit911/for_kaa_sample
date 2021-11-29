<?php

namespace WN\Core\Controller;

use WN\Core\Controller\Controller;
use WN\Core\Exception\Logger;
use WN\Core\View;
use WN\Core\Helper\URL;

class Errorlog extends Controller
{
    public function index()
    {
        $result = [];

        foreach(Logger::get() as $num => $str)
        {
            $hash = substr($str, 0, 32);
            $msg = substr($str,strlen($hash)+2); 
            $result[$num]['hash'] = $hash;
            $result[$num]['msg'] = $msg;
        }

        $content = View::factory('errors/log', ['errs' => $result])->render();
        echo View::factory('errors/wrapper', ['content' => $content])->render();
    }

    public function get($hash)
    {
        $error = Logger::get($hash);
        $content = View::factory('errors/error', $error)->render();

        if($this->request->is_ajax()) echo $content;
        else
            echo View::factory('errors/wrapper', ['content' => $content])->render();
    }

    public function delete($hash = null)
    {
        Logger::delete($hash);

        header('Location: /'.URL::segment(0));
        exit;
    }
}

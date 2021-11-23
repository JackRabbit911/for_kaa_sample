<?php

namespace WN\Page;

use WN\Core\Request;
use WN\Core\Helper\HTTP;

class MenuItem
{
    public $title;
    public $uri;

    public static function factory($title, $uri)
    {
        return new static([$title, $uri]);
    }

    public function __construct(array $data = null)
    {
        if($data)
        {
            $this->title = $data[0];
            $this->uri = $data[1];
        }
    }

    public function active($class = 'active')
    {
        $request = Request::initial();
        $uri = $request->uri();

        if($request->params('lang'))
            $uri = str_replace('/'.$request->params('lang'), '', $uri);

        if($uri === $this->uri) return " $class";
        else return null;
    }

    public function url($route = 'page', $params = null)
    {
        if(!$params) $params = ['page' => $this->uri];
        return _url($route, $params);
    }
}
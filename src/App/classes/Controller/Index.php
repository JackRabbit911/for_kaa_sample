<?php

namespace WN\App\Controller;

use WN\App\Controller\Base;
use WN\Core\View;

class Index extends Base
{
    public function index()
    {
        $this->template->title = "Homepage";
        $this->template->content = View::factory('home')->render();
    }

    public function foo($a = 'qqq', $b = null)
    {
        $this->template->content = $b; // $this->request->params('params');
    }
}

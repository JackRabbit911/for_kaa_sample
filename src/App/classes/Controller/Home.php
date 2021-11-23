<?php

namespace WN\App\Controller;

use WN\App\Controller\Base;
use WN\Core\View;

class Home extends Base
{
    public function index()
    {
        $this->template->title = "Homepage";
        $this->template->content = View::factory('home')->render();
    }
}

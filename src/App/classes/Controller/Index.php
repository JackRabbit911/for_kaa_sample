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

    // public function _remap(...$a)
    // {
    //     $this->template->content = join(', ', $a);
    // }
}

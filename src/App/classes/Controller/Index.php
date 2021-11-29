<?php

namespace WN\App\Controller;

use WN\App\Controller\Base;
use WN\Core\{View, Route, Core};
use WN\DB\DB;

class Index extends Base
{
    public function index()
    {
        $this->template->title = "Homepage";
        $this->template->content = View::factory('home')->render();
    }

    public function _remap(...$a)
    {
        // 1/0;
        // echo ['qq'];

        // $f = function(int $a)
        // {
        //     return $a*2;
        // };

        // $f();

        $this->template->content = 'hruhru';
    }
}

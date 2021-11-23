<?php

namespace WN\Page\Controller;

use WN\Core\{Session, Config};
use WN\Page\TplWrap;
use WN\Page\Controller\TplTrait;

class Template extends Page
{
    use TplTrait;

    public static $tpl_name = 'default';
    public $wrapper; // = 'wrap';
    public $path = 'tpl/';

    protected function _before()
    {
        $this->_template();
    }

    public function ajax_json()
    {

        parent::ajax_json();
    }
}
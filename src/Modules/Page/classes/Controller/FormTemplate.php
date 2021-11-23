<?php

namespace WN\Page\Controller;

use WN\Page\Controller\TplTrait;

class FormTemplate extends Form
{
    use TplTrait;

    public static $tpl_name = 'default';
    public $path = 'tpl/';
    public $template = 'form/layout';
    public $folder = 'form/';

    protected function _before()
    {
        $this->_template();

        // var_dump($this->view);
    }
}
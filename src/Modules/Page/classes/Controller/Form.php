<?php
namespace WN\Page\Controller;

// use WN\Core\Controller\Controller;
use WN\Core\{Request, Validation, Message, I18n};
use WN\Core\Helper\HTTP;
use WN\User\{User, Controller};
use WN\Page\{Page, TplWrap};

// use const WN\User\ROLE_USER;

class Form extends Controller\User
{
    public $template = 'wrap';
    public $tpl = 'WN\Page\TplWrap';

    protected $status = false;

    protected function _before()
    {
        parent::_before();

        TplWrap::$path = 'tpl/default/views/';
        TplWrap::$engine = TplWrap::TWIG;

        $lang = I18n::object();
        $this->page = Page::factory('/');

        $this->page->title = $this->request->params('action');

        if(isset($this->path)) TplWrap::$path = $this->path;

        TplWrap::set_global('lang', $lang);
        TplWrap::set_global('user', $this->user);
        TplWrap::set_global('page', $this->page);
    }
}
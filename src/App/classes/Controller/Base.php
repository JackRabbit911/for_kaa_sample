<?php

namespace WN\App\Controller;

use WN\Core\Controller\Controller;
use WN\Core\{Validation, View};
use WN\Core\Helper\HTTP;
use WN\User\User;

abstract class Base extends Controller
{
    protected $user;
    protected $session;
    protected $tpl_name = 'template';

    protected function _before()
    {
        $this->template = View::factory($this->tpl_name);
        $this->user = User::auth();
        $this->session = $this->user::$session;
        View::set_global('user', $this->user);
    }

    protected function _after()
    {
        $this->session->save();
        echo $this->template->render();
    }
}
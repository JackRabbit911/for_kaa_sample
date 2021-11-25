<?php

namespace WN\App\Controller;

use WN\App\Controller\Base;
use WN\Core\{Validation, View};
use WN\Core\Helper\HTTP;
// use WN\User\User as ClassUser;

class User extends Base
{
    private static $redirected = false;

    protected function _before()
    {      
        parent::_before();
        $this->validation = new Validation();
    }

    protected function _after()
    {
        if(static::$redirected) return;
        parent::_after();
    }

    public function login()
    {
        if($this->user->id)
        {
            HTTP::redirect(HTTP::referer());
            static::$redirected = true;
            return;
        }

        $this->template->title = "Form LogIn";

        $this->validation->rule('email', 'required|email');
        $this->validation->rule('password', 'required|password');

        if($this->validation->check($_POST))
        {
            $user = $this->user::login($_POST['email'], $_POST['password']);

            if($user->id)
            {
                $this->session->save();
                HTTP::redirect(HTTP::referer());
                static::$redirected = true;
                return;
            }
            else
            {
                $this->validation->response['email']->status = false;
                $this->validation->response['password']->status = false;
                $this->validation->response['password']->msg = 'Неверная пара email/пароль';
            }
        }
       
        $this->template->content = View::factory('form_login')
            ->render($this->validation->response);
    }

    public function logout()
    {
        $this->user->log_out();
        HTTP::redirect(HTTP::referer());
        static::$redirected = true;
    }

    public function foo($a = null)
    {
        $this->template->content = $a;
    }
}

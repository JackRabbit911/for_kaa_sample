<?php

namespace WN\User;

use WN\Core\{Validation, Message, Request, Helper};
use WN\Page\TplWrap;

class Handler
{
    public $form_controller; // = 'WN\Page\Controller\FormTemplate';

    public $session;
    public $validation;

    public function __construct()
    {
        $this->user = User::auth();
        $this->session = $this->user::$session;
        $this->validation = new Validation();

        $this->request = Request::current();

        // AttemptCounter::$db = $this->user::$model::$db;
    }

    public function login($view = null)
    {
        $this->validation->rule('userdata', 'required|email_or_phone');
        $this->validation->rule('password', 'required|password');
        $this->validation->rule('short', 'boolean');

        // var_dump($this->request->referer(), Helper\HTTP::detect_uri(), $this->request->params('controller') === $this->form_controller, $this->request->method());

        if(!$this->session->referer && $this->request->method() === 'POST')
        {
            if($this->request->params('controller') === $this->form_controller)
                $referer = $this->request->referer();
            else $referer = '/'.Helper\HTTP::detect_uri();

            $this->session->referer = $referer;
        }

        if($this->validation->check($_POST))
        {
            
                               
            if(isset($_POST['short'])) $is_long = false;
            else $is_long = true;

            $user = User::login($_POST['userdata'], $_POST['password'], $is_long);

            // return $user; exit;

            if($user->id)
            {
                $url = $this->session->referer;
                unset($this->session->referer);
                $this->session->save();
                // var_dump($url);
                if(Request::current()->is_ajax())
                    return json_encode(["action"=>"redirect", 'uri'=> $url]);
                else return header("Location: $url");
            }
            else
            {
                $message = new Message('validation', 'Invalid data');

                $this->validation->response['userdata']->status = false;
                $this->validation->response['userdata']->value = null;
                $this->validation->response['userdata']->msg = false;

                $this->validation->response['password']->status = false;
                $this->validation->response['password']->value = null;
                $this->validation->response['password']->msg = $message->get(__FUNCTION__);       
            }
        }

        // if(!empty($_POST))
            $this->session->save();

        if(!$view) $view = __FUNCTION__;

        return TplWrap::factory($view, $this->validation->response)->render();
        // else return $this->validation->response;
    }

    public function restore($view = null)
    {
        if(isset($_POST['combine']))
            $this->validation->rule('combine', 'required|email_or_phone');
        elseif(isset($_POST['email']))
            $this->validation->rule('email', 'required|email');
        elseif(isset($_POST['phone']))
            $this->validation->rule('phone', 'required|phone');

        if($this->validation->check($_POST))
        {
            // if(Helper\Validation::email($this->validation->response['combine']->value))
            //     $this->_restore_by_email_success($_POST['combine']);
            // else
            //     $this->_restore_by_phone_success($_POST['combine']);
        }
        else
        {
            if(isset($_POST['combine']))
            {
                if(Helper\Validation::email($_POST['combine'])) $name = 'email';
                else $name = __('phone');

                $this->validation->response['combine']->vars[':name'] = $name;
            }
            
            if(!$view) $view = 'restore_by_email';
            return TplWrap::factory($view, $this->validation->response)->render();
        }
    }

    protected function _generate_code($length = 6)
    {       
        return Helper\Text::random('distinct', $length);
    }
}
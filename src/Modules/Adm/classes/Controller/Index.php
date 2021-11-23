<?php

namespace WN\Adm\Controller;

use WN\Core\Controller\Controller;
use WN\Core\{Core, I18n, View, Validation};
use WN\User\User;

class Index extends Controller
{
    public $template = 'layout.php';
    protected $user;
    protected $session;
    protected $validation;

    protected function _before()
    {
        View::$path = ADMPATH.'views/';
        // $this->tpl = View::factory($this->template);
        $this->user = User::auth();
        $this->session = &$this->user::$session;
        $this->validation = new Validation();
    }

    protected function _after()
    {
        $this->session->save();
        
        if($this->request->is_ajax() || !$this->request->is_initial())
        {
            if(isset($_GET['target']))
                echo json_encode([$_GET['target'] => $this->main]);
            else echo $this->form;
        }
        else
        {
            $template = View::factory($this->template);
            $template->user = $this->user;
            $template->main = $this->main;
            echo $template->render();
        }
    }

    public function index()
    {
        $this->main = '<h1 class="display-1">Admin panel content</h1>';
    }

}
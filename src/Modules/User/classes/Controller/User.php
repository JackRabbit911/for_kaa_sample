<?php
namespace WN\User\Controller;

use WN\Core\Controller\Controller;
use WN\Core\{Request, Validation, Message, Mailer, I18n, Route};
use WN\Core\Helper\{HTTP, Text, Validation as Valid, Date, URL, HTML};
use WN\User\User as ClassUser;

class User extends Controller
{
    public $template = 'src/Modules/User/views/template.php';
    public $tpl = 'WN\Core\View';
    public $folder = '';

    public $register_rules = [
        'nickname'  => 'username',
        'firstname' => 'alpha_space_utf8',
        'lastname'  => 'alpha_space_utf8',
        'nickfirstlast' => 'required_one_of(nickname, firstname, lastname)',
        'email'     => 'email|WN\User\ModelUser::unique(email)',
        'phone'     => 'phone|WN\User\ModelUser::unique(phone)',
        'dob'       => 'valid_date',
        'sex'       => 'integer',
        'emailphone' => 'required_one_of(email, phone)',
        'password'  => 'required|password|min_length(6)',
        'confirm'   => 'required|confirm',
        'agree'     => 'required|boolean',
    ];

    protected $status = false;

    protected function _before()
    {
        parent::_before();

        $this->uri = $_SERVER['REQUEST_URI'];
        $this->user = ClassUser::auth();
        $this->_logout();
        $this->session = $this->user::$session;
        $this->validation = new Validation();

        if($this->request->params('view') !== null)
            $this->view = $this->request->params('view');
        else $this->view = $this->folder.$this->request->params('action');
    }

    protected function _after()
    {
        $this->session->save();

        if($this->status === true) return;

        if($this->request->is_ajax() || !$this->request->is_initial())
        {
            if(isset($_GET['target']))
                echo json_encode([$_GET['target'] => $this->form]);
            else echo $this->form;
        }
        else
        {
            $template = $this->tpl::factory($this->template);
            $template->title = $this->request->params('action');
            $template->main = $this->form;
            echo $template->render();
        }
    }

    public function index() {}

    public function login()
    {
        if($this->user->role() >= ROLE_USER)
            $this->status = true;
           
        if($this->status === true) return;

        if($this->request->method() === 'GET' && !$this->session->referer)
        {
            $referer = ($this->request->is_initial()) ? $this->request->referer() : $this->request->initial()->url();
            $this->session->referer = $referer;
        }

        $this->validation->rule('userdata', 'required|email_or_phone');
        $this->validation->rule('password', 'required|password');
        $this->validation->rule('short', 'boolean');

        if($this->validation->check($_POST))
        {                               
            if(isset($_POST['short'])) $is_long = false;
            else $is_long = true;

            $user = ClassUser::login($_POST['userdata'], $_POST['password'], $is_long);

            if($user->id)
            {
                $url = $this->session->referer ?? '/';
                unset($this->session->referer);
                $this->status = true;

                if($this->request->is_ajax())
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

        if(!isset($this->validation->response['disabled'])) $this->validation->response['disabled'] = null;

        $this->form = $this->tpl::factory($this->view, $this->validation->response)
            ->set('restore_link', _url('form', ['action'=>'restore', 'view'=>'form/restore_modal']))
            ->set('register_link', _url('form', ['action'=>'register', 'view'=>'form/register_modal']))
            ->set('action', $this->uri)->set('view', $this->view)->render();
    }

    public function restore()
    {
        switch (ClassUser::$confirm)
        {
            case ClassUser::CONFIRM_EMAIL:
                $this->validation->rule('email', 'required|email');
                $mode = 'email'; break;
            case ClassUser::CONFIRM_PHONE:
                $this->validation->rule('phone', 'required|phone');
                $mode = 'phone'; break;
            case ClassUser::CONFIRM_COMBINE:
                $this->validation->rule('combine', 'required|email_or_phone');
                $mode = 'combine'; break;           
        }

        $this->_restore($mode);
    }

    public function confirm()
    {
        $disabled = '';
        $this->validation->rule('confirm', 'required|alpha_num');
        
        if($this->validation->check($_POST))
        {
            if($_POST['confirm'] === $this->session->code)
            {
                $view = ($this->request->params('view')) 
                    ? preg_replace('/confirm/', 'password', $this->request->params('view')) 
                    : $this->folder.'password';

                $code = md5('№ыха%бфм!нйж~'.$this->session->code);
                $this->session->code = $code;
                $this->session->save();

                $url = _url('form', ['action'=>'password', 'view'=>$view, 'query'=>"code=$code"]);
                header("Location: $url");
                exit;
            }
            else
            {
                $this->validation->response['confirm']->reset()
                ->check([
                    'status' => false, 
                    'code' => 'pincode', 
                ]);

                $msg = '';
                $disabled = ' disabled';
            }
        }
        else
        {
            $message = new Message('user');
            $msg = $message->get('reset_password_by_'.$this->session->mode, [':'.$this->session->mode => $this->session->value]);
        }

        $this->form = $this->tpl::factory($this->view, $this->validation->response)
            ->set('action', $this->uri)
            ->set('msg', $msg)->set('disabled', $disabled)->render();
    }

    public function password()
    {
        $this->validation->rule('password', 'required|password|min_length(6)');
        $this->validation->rule('confirm', 'required|confirm');

        $disabled = '';
        $code = $_GET['code'] ?? false;

        $denied = function()
        {
            $this->validation->response['password']->reset()
                ->check(['status' => false]);

            $this->validation->response['confirm']->reset()
                ->check(['status' => false, 'code' => 'pincode']);

            $this->session->destroy();

            return ' disabled';
        };

        if($this->validation->check($_POST))
        {
            if($code === $this->session->code)
            {
                $this->user::$model->set(['id'=>$this->session->user, 'password'=>$_POST['password']]);
                unset($this->session->code, $this->session->mode, $this->session->value, $this->session->user);

                $view = ($this->request->params('view')) 
                ? preg_replace('/password/', 'success', $this->request->params('view')) 
                : $this->folder.'success';
                
                $this->form = $this->tpl::factory($view)
                        ->set('msg' , __('The password was changed successfully'))
                        ->set('login_link', _url('form', ['action'=>'login', 'view'=>'form/login_modal']))
                        ->render();
                return;
            }
            else $disabled = $denied();
        }
        elseif($code !== $this->session->code) $disabled = $denied();
 
        $this->form = $this->tpl::factory($this->view, $this->validation->response)
                        ->set('action', $this->uri)
                        ->set('disabled', $disabled)->render();
    }

    public function register()
    {
        $this->validation->rules($this->register_rules);

        if($this->validation->check($_POST))
        {
            $code = $this->_generate_code();

            unset($_POST['confirm']);
            unset($_POST['agree']);

            if(!empty($_POST['phone'])) $mode = 'phone';
            else $mode = 'email';

            $this->session->mode = $mode;
            $this->session->value = $_POST[$mode];
            $this->session->code = $code;
            $this->session->userdata = $_POST;
            $this->session->save();

            $subject = __('Confirmation of registration');
            call_user_func([$this, 'send_email'], $_POST, $subject, $code);

            $view = ($this->request->params('view')) 
                ? preg_replace('/register/', 'confirm', $this->request->params('view')) 
                : $this->folder.'confirm';

            $url = _url('form', ['action'=>'register_confirm', 'view'=>$view]);

            header("Location: $url");
            exit;
        }
        else
            $this->form = $this->tpl::factory($this->view, $this->validation->response)
                            ->set('mode', 'register')->set('action', $this->uri)->render();
    }

    public function register_confirm()
    {
        $disabled = '';
        $message = new Message('user');
        $this->validation->rule('confirm', 'required|alpha_num');
        
        if($this->validation->check($_POST))
        {
            if($_POST['confirm'] === $this->session->code)
            {
                $view = ($this->request->params('view')) 
                    ? preg_replace('/confirm/', 'success', $this->request->params('view')) 
                    : $this->folder.'success';

                $user = new ClassUser;
                $user->data = (array) $this->session->userdata;
                $user->register = time();
                $user->save();

                $this->session->destroy();

                $this->form = $this->tpl::factory($view)
                    ->set('msg' , __('You are registered successfully'))
                    ->set('login_link', _url('form', ['action'=>'login', 'view'=>'form/login_modal']))
                    ->render();
                return;
            }
            else
            {
                $this->validation->response['confirm']->reset()
                ->check([
                    'status' => false, 
                    'code' => 'pincode1',
                ]);

                $msg = $message->get('confirm_register_by_'.$this->session->mode, [':'.$this->session->mode => $this->session->value]);
            }
        }
        else $msg = $message->get('confirm_register_by_'.$this->session->mode, [':'.$this->session->mode => $this->session->value]);

        $this->form = $this->tpl::factory($this->view, $this->validation->response)
            ->set('msg', $msg)->set('disabled', $disabled)
            ->set('action', $this->uri)->render();
    }

    public function private()
    {
        if($this->user->role() == ROLE_GUEST)
        {
            if($this->request->method() === 'GET' || empty($_POST))
                header("Location: ".$this->request->referer());
                // throw new WnException('Page not found', null, 404);
            else
            {
                $this->session->post = $_POST;
                $this->session->referer = $this->request->uri();
                $this->session->save();
                header("Location: /~form/login");                
            }
            exit;
        }

        $rules = $this->register_rules;
        unset($rules['agree']);
        $rules['password'] = 'password|min_length(6)';
        $rules['confirm'] = 'confirm';

        $this->validation->rules($rules);

        if($this->validation->check($_POST))
        {
            unset($_POST['confirm']);

            if(!empty($this->user->id))
            {
                $_POST['id'] = $this->user->id;

                $this->user->data = $_POST;
                $this->user->save();
                $msg = __('Data changed successfully');
            }           
        }
        else $msg = null;

        if(!$this->validation->response['dob']->value)
            $this->validation->response['dob']->value = $this->user->dob();
       
        $this->validation->set_values($this->session->post);
        $this->validation->set_values($this->user);

        unset($this->session->post);
        unset($this->session->referer);
        $this->session->save();

        $this->form = $this->tpl::factory($this->view, $this->validation->response)
                ->set('action', $this->uri)->set('mode', 'private')->set('msg', $msg)->render();
    }

    protected function _restore($mode)
    {
        if($this->validation->check($_POST))
        {
            $value = $_POST[$mode];

            if($mode === 'email' || ($mode !== 'phone' && Valid::email($value)))
                    $name = 'email';
            else $name = 'phone';

            $userdata = $this->user::$model->get_userdata($value);
            if($userdata)
            {
                $code = $this->_generate_code();
                $this->session->user = $userdata['id'];       
                $this->session->mode = $name;
                $this->session->code = $code;
                $this->session->value = $value;
                $this->session->save();

                $subject = __('Password restore');
                call_user_func([$this, 'send_'.$name], $userdata, $subject, $code);

                $view = ($this->request->params('view')) 
                    ? preg_replace('/restore/', 'confirm', $this->request->params('view')) 
                    : $this->folder.'confirm';
                
                $url = _url('form', ['action'=>'confirm', 'view'=>$view]);
                header("Location: $url");
                exit;
            }
            else
            {
                $this->validation->response[$mode]->reset()
                ->check([
                    'status' => false, 
                    'code' => 'isset', 
                    'vars' => [':name' => __($name)],
                    'value' => $_POST[$mode]
                ]);
            }
        }
 
        $this->form = $this->tpl::factory($this->view, $this->validation->response)
                        ->set('mode', $mode)->set('action', $this->uri)->render();
    }

    protected function _logout()
    {
        if(isset($_GET['user']) && $_GET['user'] === 'logout')
        {
            $this->user->log_out();
            HTTP::redirect(HTTP::referer());
            $this->status = true;
        }
    }

    protected function _generate_code($length = 6)
    {
        return Text::random('distinct', $length);
    }

    protected function send_email($usd, $subject, $code)
    {
        $mailer = new Mailer();
        $mailer->to($usd['email'])->subject($subject)->body($code)->send();
    }

    protected function send_phone($usd, $subject, $code)
    {
        $mailer = new Mailer();
        $mailer->to($usd['email'])->subject($subject)->body($code)->send();
    }
}

<?php
namespace WN\User\Controller;

// use WN\Core\Controller\Controller;
use WN\Core\{Controller, Session, Validation, Core, View, Request, Mailer, I18n, Message};
use WN\Core\Helper\{HTTP, HTML, Date, Validation as Valid, Text, URL, Cookie};
use WN\User\{User, AttemptCounter};
use WN\Core\Exception\Handler;
use WN\DB\DB;

class Index extends Controller\Template
{
    public $template = 'src/Modules/User/views/template.php';

    public $attempts = 3;

    protected $wait;

    protected $register_callback;
    protected $confirm_callback;

    protected $validation;

    protected $redirect = false;

    protected function _before()
    {
        // I18n::lang('en');
        // Core::$errors = false;
        parent::_before();
        $this->template->title = $this->request->params('action');     
        $this->template->js('media/js/test_form_submit.js');
       
        $this->user = User::auth();
        $this->session = $this->user::$session;

        var_dump($this->session->cookie, $_COOKIE);
        // $this->session = Session::instance();

        // $this->session->count = 0;

        // if(!$this->session->cookie && $this->request->uri() != '~user/cookie') HTTP::redirect('/~user/cookie');

        // if(!$this->session->referer) // && HTTP::referer() != HTTP::url())
        // {
        //     if($this->request->is_initial() === false)
        //         $this->session->referer = $this->request->initial()->url();
        //     else $this->session->referer = HTTP::referer();
        // }

        // if($this->user->role() > ROLE_GUEST)
        // {
        //     // var_dump($this->user);
        //     // exit;     
        //     HTTP::redirect(HTTP::referer($this->session->referer));
        //     $this->redirect = true;
        // }

        $this->validation = new Validation();
    }

    protected function _check_cookie()
    {
        if(empty($_COOKIE) && !isset($_GET['cookie']))
        {
            $uri = HTTP::detect_uri().URL::query(['cookie'=>1]);
            Cookie::set('WNSID', 1);
            HTTP::redirect($uri);
        }
        else
        {
            
        }
    }

    protected function _after()
    {
        $this->session->save();

        if($this->redirect) return;

        if($this->request->is_ajax() || $this->request->is_initial() == false)
            echo $this->template->form;
        else parent::_after();
    }

    public function index()
    {
        if($this->session->count) var_dump($this->session->count);
        $this->template->form = 'Controller\User';
    }

    public function login()
    {
        // if(!$this->session->id)
        // {
        //     $this->template->form = 'Что-то пошло не так';
        //     return;
        // }
        // $this->session = Session::instance();
        $this->session->referer = HTTP::referer();
        // $validation = new Validation();
        $this->validation->rule('userdata', 'required|regexp(/^[+-_\w\s@.(),!"]*$/u)');
        $this->validation->rule('password', 'required|password');
        // $this->validation->rule('password', [[$this->user::$model, 'pair'], 'userdata', 'short', ':validation']);
        $this->validation->rule('password', [[$this, '_counter'], 3, 60]);
        $this->validation->rule('password', [[$this, '_pair'], ':validation']);
        $this->validation->rule('short', 'boolean');

        // $this->validation->rule('userdata', null);
        // $this->validation->rule('password', null);
        // $this->validation->rule('short', null);


        if($this->validation->check($_POST))
        {
            $referer = $this->session->referer;
            // unset($this->session->referer);

            if(!$referer) $this->template->form = 'Что-то пошло не так';
            else
            {
                User::log_in($this->user);

                $this->session->save();

                // var_dump($this->session);
                // exit;

                HTTP::redirect(HTTP::referer($referer));
                $this->redirect = true;
            }
        }
        else
        {
            $referer = $this->session->referer;
            // unset($this->session->referer);

            // var_dump($referer);

            // var_dump($this->session);

            if(!$referer) $this->template->form = 'Что-то пошло не так';    
            elseif(!isset($this->template->form)) $this->template->form = 
                View::factory('sign_in', $this->validation->response)->render();
        }

        $this->session->save();
    }

    public function logout()
    {
        $this->user->log_out();
        HTTP::redirect(HTTP::referer());
        $this->redirect = true;
    }

    public function cookie()
    {
        if($this->session->cookie)
        {
            HTTP::redirect(HTTP::referer());
            $this->redirect = true;
        }
        else $this->template->form = 'Enable cookie, please! '.$this->request->uri();
    }

    public function restore()
    {
        switch (User::$confirm)
        {
            case User::CONFIRM_EMAIL:
                $this->_restore_by_email();
                break;
            case User::CONFIRM_PHONE:
                $this->_restore_by_phone();
                break;
            case User::CONFIRM_EMAIL|User::CONFIRM_PHONE:
                $this->_restore_by_both();
                break;
            case User::CONFIRM_COMBINE:
                $this->_restore_by_combine();
                break;
        }
    }

    public function restore_by_phone()
    {
        $code = $this->session->code;
        $phone = $this->session->phone;

        if(!$code)
        {
            // Core::$errors = false;
            // throw new WnException(null, null, 404);

            echo Handler::http_response(404);
            exit;
        }

        $this->validation->rule('pincode', 'required');
        $this->validation->rule('pincode', [$this, 'check_code']);

        if($this->validation->check($_POST))
        {
            $this->template->form = 'good!';
        }
        else
        {
            $msg = 'На указанный номер телефона: '.$phone.' отправлено <br> сообщение с кодом, введите его в поле ниже';
            $class = 'primary';
            $form = View::factory('phone_code', $this->validation->response);

            

            $data = [
                'class' => $class,
                'msg'   => $msg,
                'form'  => $form,
            ];
    
            $view = View::factory('alert', $data);
            $view->js('media/js/pincode.js');
    
            $this->template->form = $view->render();
        }
        
        // unset($this->session->code);
        // unset($this->session->phone);
        $this->session->save();       
    }

    public function _counter($value, $count, $wait)
    {
        if($this->session->count === null)
            $this->session->count = $count - 1;
        elseif($this->session->count > 0)
        {
            $this->session->count--;

            if($this->session->count == 0)
            {
                return $this->_wait($wait); 
                // return false;
            }
        }
        else
        {
            return $this->_wait($wait);
            // return false;
        }

        if(!$this->session->cookie || $this->session->empty)
        {
            $this->template->form = 'Error';
            return false;
        }
        else return true;
    }

    protected function _wait($wait)
    {
        $t = time();
        if(!$this->session->wait) 
            $this->session->wait = $t;
        $wt = $wait - ($t - $this->session->wait);

        if($wt > 0)
        {
            $this->template->form = 'Wait, please '.Date::sec2str($wt);
            return false;
        }
        else
        {
            // unset($this->session->count);
            // unset($this->session->wait);
            $this->session->destroy();
            return true;
        }
    }

    public function _pair($password, &$validation)
    {
        $user = $this->user::$model->log_in(['password' => $password, 'userdata' => $validation->response['userdata']->value]);

        if($user)
        {
            // User::log_in($user);
            $this->user = $user;
            return true;
        }
        else
        {
            $validation->response['userdata']->status = false;
            $validation->response['userdata']->value = null;
            $validation->response['userdata']->msg = null;

            // if($this->session->count)
            // {
            //     $validation->response['password']->msg = $validation->response['password']->msg().'<br> Осталось '.$this->session->count;
            // }
            // else $validation->response['password']->msg = $validation->response['password']->msg().'<br> Осталось не знаю';

            // $validation->response['password']->status = false;
            // $validation->response['password']->msg = $this->session->count;
            // var_dump($validation->response['password']);

            if($this->session->count)
            {
                $message = new Message('validation', 'Invalid data');
                $msg = $message->get(__FUNCTION__);
                $msg .= '<br>'.$message->get('_counter', [':count' => I18n::plural($this->session->count, 'attempt')]);
                // $msg .= '<br>'.I18n::plural($this->session->count, 'attempt');
                return $msg;
            }
            else return false;

            
                
            // return $msg->get('_pair').' Осталось '.$this->session->count;
        }
    }

    public function check_code($value)
    {
        $code = $this->session->code;
        // $phone = $this->session->phone;

        if($code == $value) return true;
        else
        {
            return 'Код не принят';
            // if(AttemptCounter::check('phone', $phone))
            // {
            //     var_dump($validation->response);
            //     return 'Код не принят. Осталось '.I18n::plural(AttemptCounter::$count, 'attempt');
            // }
            // else return 'Количество попыток исчерпано.<br>Повторная попытка возможна<br>через '.Date::sec2str(AttemptCounter::$wait);
        }       
    }

    public function password()
    {
        $code = $this->request->params('code');

        if($this->session->id == $code) echo 'Good!';
        else echo 'HUY!';
        exit;
    }

    public function register()
    {
        $data = [
            'nickname' => 'Mrs. Marple',
            'lastname' => null,
            'firstname' => 'Оля',
            // $user->password = '2128506';
            'email' => 'okkrina@rambler.ru',
            // $user->phone = null;
            'dob' => Date::timestamp('01.06.1969'),
            'sex' => 0,
            ];
            // $user->register = time();
    
        $url = User::register($data, $this->register_callback);
        if($url) echo HTML::anchor($url);
        else echo 'Пользователь с такими данными уже зарегистрирован<br>', HTML::anchor(HTTP::referer(), 'Назад');
    }

    public function confirm()
    {
        $code = $this->request->params('code');
        $success = User::confirm($code, $this->confirm_callback);

        if($success === false) echo 'Пользователь с такими данными уже зарегистрирован';
    }

    protected function _restore_by_email($check = false)
    {
        $success = function(){
            $code = $this->_generate_code('email');
            $link = HTML::anchor(HTTP::url('/~user/password').'/'.$code, null, ['target'=>'_blank']);
            $this->session->code = $code;
            $this->session->save();

            $mailer = new Mailer();
            $mailer->to($_POST['email'])->body($link);
            $mailer->send();

            $msg = 'На указанный email: '.$_POST['email'].' отправлено письмо <br> со ссылкой на восстановление пароля<br>';
            // $form = HTML::anchor(HTTP::url('/~user/password').'/'.$this->session->id, null, ['target'=>'_blank']);
            $form = null;



            $data = [
                'class' => 'primary',
                'msg'   => $msg,
                'form'  => $form,
            ];

            $this->template->form = View::factory('alert', $data)->render();
        };

        if($check === true) $success();
        else
        {
            $this->validation->rule('email', 'required|email');
            $this->validation->rule('email', [[$this->user::$model, 'isset'], 'email']);

            if($this->validation->check($_POST))
            {
                $success();
            }
            else $this->template->form = View::factory('form_restore_by_both', $this->validation->response)->set('mode', 'email')->render();
        }
    }

    protected function _restore_by_phone($check = false)
    {
        $this->validation->rule('phone', 'required|phone');
        $this->validation->rule('phone', [[$this->user::$model, 'isset'], 'phone']);

        if($this->validation->check($_POST))
        {
            $code = $this->_generate_code('phone');
        
            $this->session->code = $code;
            $this->session->phone = $_POST['phone'];
            $this->session->save();

            $phone = $this->user::$model::filter_santize_phone($_POST['phone']);
            $userdata = $this->user::$model->get('phone', $phone);

            $mailer = new Mailer();
            $mailer->to($userdata['email'])->body($code);
            $mailer->send();

            HTTP::redirect('/~user/restore_by_phone');
            $this->redirect = true;
        }
        else $this->template->form = View::factory('form_restore_by_both', $this->validation->response)->set('mode', 'phone')->render();
    }

    protected function _restore_by_combine()
    {
        $this->validation->rule('combine', 'required|regexp(/^[+-_\w\s@.()!"]+$/u)');
        $this->validation->rule('combine', [$this->user::$model, 'isset'], 'email', 'phone');

        if($this->validation->check($_POST))
        {
            if(Valid::email($this->validation->response['combine']->value))
                $this->_restore_by_email(true);
            else
            {
                HTTP::redirect('/~user/restore_by_phone');
                $this->redirect = true;
            }
        }
        else $this->template->form = View::factory('form_restore_by_combine', $this->validation->response)->render();
    }

    protected function _restore_by_both()
    {
        $this->validation->rule('email', 'email');
        $this->validation->rule('email', [[$this->user::$model, 'isset'], 'email']);
        $this->validation->rule('phone', 'phone|required_one_of(:validation, email)');
        $this->validation->rule('phone', [[$this->user::$model, 'isset'], 'phone']);

        if($this->validation->check($_POST))
        {
            if(!empty($this->validation->response['email']->value))
            {
                $this->_restore_by_email(true);
            }
            elseif(!empty($this->validation->response['phone']->value))
            {
                HTTP::redirect('/~user/restore_by_phone');
                $this->redirect = true;
            }
        }
        else $this->template->form = View::factory('form_restore_by_both', $this->validation->response)->set('mode', 'both')->render();
    }

    protected function _generate_code($mode)
    {
        if($mode === 'email') return bin2hex(random_bytes(6));
        else return Text::random('numeric', 4);
    }
}
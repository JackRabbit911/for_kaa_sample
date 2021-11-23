<?php
namespace WN\User\Controller;

// use WN\Core\Controller\Controller;
use WN\Core\{Controller, Session, Validation, Core, View, Request, Mailer, I18n, Message};
use WN\Core\Helper\{HTTP, HTML, Date, Validation as Valid, Text, URL, Cookie};
use WN\User\{User, AttemptCounter as Counter, AttemptCounter, ModelUser};
use WN\Core\Exception\Handler;
use WN\Core\Exception\WnException;
use WN\DB\DB;

class UserController extends Controller\Template
{
    public $template = 'src/Modules/User/views/template.php';

    public $attempts = 3;

    // protected $count;
    public $wait = 60;

    public $register_rules = [
        'nickname'  => 'username',
        'firstname' => 'alpha_space_utf8',
        'lastname'  => 'required_one_of(nickname, firstname)|alpha_space_utf8',
        'email'     => 'email|WN\User\ModelUser::unique(email)',
        'phone'     => 'required_one_of(email)|phone|WN\User\ModelUser::unique(phone)',
        'dob'       => 'valid_date',
        'sex'       => 'integer',
        'password'  => 'required|password|min_length(6)',
        'confirm'   => 'required|confirm',
        'agree'     => 'required|boolean',
    ];

    public $private_rules = [
        'nickname'  => 'username',
        'firstname' => 'alpha_space_utf8',
        'lastname'  => 'alpha_space_utf8',
        'email'     => 'email|WN\User\ModelUser::unique(email)',
        'phone'     => 'phone',
        'dob'       => 'valid_date',
        'sex'       => 'integer',
        'password'  => 'password|min_length(6)',
        'confirm'   => 'confirm',
    ];

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
        $this->template->js('/media/js/user_form_submit.js');
       
        $this->user = User::auth();
        $this->session = $this->user::$session;

        AttemptCounter::$db = $this->user::$model::$db;

        if(!$this->session->referer) // && HTTP::referer() != HTTP::url())
        {
            if($this->request->is_initial() === false)
                $this->session->referer = $this->request->initial()->url();
            else $this->session->referer = HTTP::referer();
        }

        if($this->user->role() > ROLE_GUEST && $this->request->params('action') != 'private')
        {
            HTTP::redirect(HTTP::referer($this->session->referer));
            $this->redirect = true;
        }

        $this->validation = new Validation();
    }

    protected function _after()
    {
        $this->session->save();

        if($this->redirect) return;

        if($this->request->is_ajax() || $this->request->is_initial() == false)
            echo $this->template->form;
        else parent::_after();
    }

    // public function index()
    // {
    //     // if(isset($this->template->form)) return;
    //     // $this->_out('Controller\User');
    // }

    public function login()
    {

        if(isset($this->template->form)) return;
       
        AttemptCounter::create_table();
        AttemptCounter::delete_by_time($this->wait);
       
        $this->validation->rule('userdata', 'required|email_or_phone');
        $this->validation->rule('password', 'required|password');
        $this->validation->rule('short', 'boolean');

        if($this->validation->check($_POST))
        {
            $userdata = $this->_log_in();

            if($userdata)
            {
                if(isset($_POST['short'])) $is_long = false;
                else $is_long = true;

                $referer = $this->session->referer;
                unset($this->session->referer);

                User::log_in($userdata, $is_long);

                // if($this->request->is_ajax())
                // {
                //     $this->redirect = true;
                //     return json_encode(['action' => 'reload']);
                // }
                // else
                {
                HTTP::redirect(HTTP::referer($referer));
                $this->redirect = true;
                }
            }
        }

        $this->session->save();

        if(!isset($this->validation->response['disabled'])) $this->validation->response['disabled'] = null;

        $this->template->form = View::factory('sign_in', $this->validation->response)->render();
    }

    protected function _log_in()
    {
        list($count, $wait) = AttemptCounter::count($_POST['userdata'], $this->attempts, $this->wait);

        $message = new Message('validation', 'Invalid data');

        if(!$wait)
        {
            $userdata = $this->user::$model->get_userdata($_POST['userdata'], $_POST['password']);

            if($userdata)
            {
                AttemptCounter::delete($this->session->id, $userdata);
                return $userdata;
            }
            else
            {
                $this->validation->response['userdata']->status = false;
                $this->validation->response['userdata']->value = null;
                $this->validation->response['userdata']->msg = false;

                $this->validation->response['password']->status = false;
                $this->validation->response['password']->value = null;

                if($count === null)
                    $this->validation->response['password']->msg = $message->get(__FUNCTION__);
                elseif($count > 0)
                {
                    $msg = $message->get(__FUNCTION__);
                    $msg .= '<br>'.$message->get('count', [':count' => I18n::plural($count, 'attempt')]);
                    $this->validation->response['password']->msg = $msg;
                }
                else
                {
                    $this->validation->response['disabled'] = ' disabled';
                    $msg = $message->get('wait', [':time' => Date::sec2str($this->wait)]);
                    $this->validation->response['password']->msg = $msg;
                }

                return false;
            }            
        }
        else
        {
            $this->validation->response['userdata']->status = false;
            $this->validation->response['userdata']->value = null;
            $this->validation->response['userdata']->msg = false;

            $this->validation->response['password']->status = false;
            $this->validation->response['password']->value = null;

            $this->validation->response['disabled'] = ' disabled';
            $msg = $message->get('wait', [':time' => Date::sec2str($wait)]);
            $this->validation->response['password']->msg = $msg;

            return false;
        }
    }

    public function logout()
    {
        $this->user->log_out();
        HTTP::redirect(HTTP::referer());
        $this->redirect = true;
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
        $phone = $this->session->usd;

        if(!$code)
        {
            echo Handler::http_response(404);
            exit;
        }

        $this->validation->rule('pincode', 'required');

        if($this->validation->check($_POST))
        {
            list($count, $wait) = AttemptCounter::count($phone, $this->attempts, $this->wait);

            $message = new Message('validation', 'Invalid data');

            if(!$wait)
            {
                if($_POST['pincode'] == $code)
                {
                    AttemptCounter::delete($this->session->id, $phone);

                    $code = $this->_generate_code();
                    $this->session->code = $code;

                    HTTP::redirect("password/$code");
                    $this->redirect = true;
                }
                else
                {
                    $this->validation->response['pincode']->status = false;
                    $this->validation->response['pincode']->value = null;

                    if($count === null)
                        $this->validation->response['pincode']->msg = $message->get('pincode');
                    elseif($count > 0)
                    {
                        $msg = $message->get('pincode');
                        $msg .= '<br>'.$message->get('count', [':count' => I18n::plural($count, 'attempt')]);
                        $this->validation->response['pincode']->msg = $msg;
                    }
                    else
                    {
                        $this->validation->response['disabled'] = ' disabled';
                        $msg = $message->get('wait', [':time' => Date::sec2str($this->wait)]);
                        $this->validation->response['pincode']->msg = $msg;
                    }
                }            
            }
            else
            {
                $this->validation->response['pincode']->status = false;
                $this->validation->response['pincode']->value = null;

                $this->validation->response['disabled'] = ' disabled';
                $msg = $message->get('wait', [':time' => Date::sec2str($wait)]);
                $this->validation->response['pincode']->msg = $msg;
            }
        }

        $this->session->save();

        if(!isset($this->validation->response['disabled'])) $this->validation->response['disabled'] = null;

        $msg_user = new Message('user');

        $msg = $msg_user->get('reset_password_by_phone', [':phone' => $phone]);
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

    public function password()
    {
        $code = $this->request->params('code');

        if($this->session->code == $code)
        {
            AttemptCounter::delete($this->session->id, $this->session->usd);
            $this->_reset_password();
        }
        else
        {
            echo Handler::http_response(404);
            exit;
        }
    }

    public function register()
    {
        if(($code = $this->request->params('code')))
            $this->_confirm($code);
        else
        {
            $this->validation->rules($this->register_rules);

            if($this->validation->check($_POST))
            {
                $code = $this->_generate_code('email');
                $link = HTML::anchor("/~user/register/$code", null, null, true);

                unset($_POST['confirm']);
                unset($_POST['agree']);

                $_POST['dob'] = Date::timestamp($_POST['dob']);
                if(empty($_POST['phone'])) $_POST['phone'] = null;

                $this->session->code = $code;
                $this->session->userdata = $_POST;
                $this->session->save();

                $subject = __('Confirmation of registration');

                $mailer = new Mailer();
                $mailer->to($_POST['email'])->subject($subject)->body($link)->send();

                $message = new Message('user');
                $msg = $message->get('confirm_register_by_email', [':email' => $_POST['email']]);

                $data = [
                    'class' => 'primary',
                    'msg'   =>  $msg,
                    'form'  => null,
                ];
    
                $this->template->form = View::factory('alert', $data)->render();
            }
            else
                $this->template->form = View::factory('register', $this->validation->response)->render();
        }
    }

    public function private()
    {
        if($this->user->role() == ROLE_GUEST)
        {
            if($this->request->method() === 'GET' || empty($_POST))
                throw new WnException('Page not fiund', null, 404);
                // return Handler::http_response(404);
            else
            {
                $this->session->post = $_POST;
                $this->session->referer = '/~user/private';
                $this->session->save();
                HTTP::redirect('/~user/login');
                $this->redirect = true;
                return;
            }
        }

        $this->validation->rules($this->private_rules);
        
        if($this->validation->check($_POST))
        {
            unset($_POST['confirm']);

            if(!empty($this->user->id))
            {
                $_POST['id'] = $this->user->id;
                $this->user::$model->set($_POST);

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

        $this->template->form = View::factory('private', $this->validation->response)
                                    ->set('msg', $msg)->render();
    }

    protected function _reset_password()
    {
        $this->validation->rule('password', 'required|password|min_length(6)');
        $this->validation->rule('confirm', 'required|confirm');

        if($this->validation->check($_POST))
        {
            $this->user::$model->set(['id'=>$this->session->user, 'password'=>$_POST['password']]);
            unset($this->session->user);
            unset($this->session->code);
            unset($this->session->usd);

            $this->session->save();

            $data = [
                'class' => 'success',
                'msg' => __('The password was changed successfull').'<br>'.HTML::anchor($this->session->referer, __('Return'), null, true),
                'form' => null,
            ];
            $this->template->form = View::factory('alert', $data);
        }
        else
            $this->template->form = View::factory('reset_password', $this->validation->response)->render();
    }

    protected function _confirm($code)
    {
        $session_code = $this->session->code;
        $userdata = $this->session->userdata;

        $this->session->destroy();

        if($code == $session_code)
        {
            $this->user::$model->set($userdata);
            
            HTTP::redirect('/~user/login');
            $this->redirect = true;
        }
        else
        {
            $data = [
                'class' => 'warning',
                'msg'   => __('Link is out of date'),
                'form'  => null,
            ];

            $this->template->form = View::factory('alert', $data)->render();
        }
    }

    protected function _restore_by_email()
    {
        $this->validation->rule('email', 'required|email');

        if($this->validation->check($_POST))
            $this->_restore_by_email_success($_POST['email']);
        else
            $this->template->form = View::factory('form_restore_by_both', $this->validation->response)
                                        ->set('mode', 'email')->render();
    }

    protected function _restore_by_email_success($email)
    {
        $userdata = $this->user::$model->get_userdata($email);

        if($userdata)
        {
            $code = $this->_generate_code('email');
            $link = HTML::anchor("/~user/password/$code", null, null, true);
            $this->session->user = $userdata['id'];
            $this->session->usd = $email;
            $this->session->code = $code;
            $this->session->save();

            $subject = __('Password reset');

            $mailer = new Mailer();
            $mailer->to($email)->subject($subject)->body($link)->send();

            $message = new Message('user');
            $msg = $message->get('reset_password_msg', [':email' => $email]);

            $data = [
                'class' => 'primary',
                'msg'   => $msg,
                'form'  => null,
            ];

            $this->template->form = View::factory('alert', $data)->render();
        }
        else
        {
            $this->validation->response['email']->reset()->check(false, ['code' => 'isset']);

            $this->template->form = View::factory('form_restore_by_both', $this->validation->response)
                                        ->set('mode', 'email')->render();
        }
    }

    protected function _restore_by_phone()
    {
        $this->validation->rule('phone', 'required|phone');

        if($this->validation->check($_POST)) $this->_restore_by_phone_success($_POST['phone']);
        else $this->template->form = View::factory('form_restore_by_both', $this->validation->response)->set('mode', 'phone')->render();
    }

    protected function _restore_by_phone_success($phone)
    {
        $code = $this->_generate_code('phone');
        $phone = $this->user::$model::filter_santize_phone($phone);

        $userdata = $this->user::$model->get_userdata($phone);

        if($userdata)
        {
            $this->session->user = $userdata['id'];
            $this->session->code = $code;
            $this->session->usd = $phone;
            $this->session->save();

            $mailer = new Mailer();
            $mailer->to($userdata['email'])->body($code);
            $mailer->send();

            HTTP::redirect('/~user/restore_by_phone');
            $this->redirect = true;
        }
        else
        {
            $this->validation->response['phone']->reset()->check(false, ['code' => 'isset']);
            $this->template->form = View::factory('form_restore_by_both', $this->validation->response)
                                        ->set('mode', 'phone')->render();
        }        
    }

    protected function _restore_by_combine()
    {
        $this->validation->rule('combine', 'required|email_or_phone');

        if($this->validation->check($_POST))
        {
            if(Valid::email($this->validation->response['combine']->value))
                $this->_restore_by_email_success($_POST['combine']);
            else
                $this->_restore_by_phone_success($_POST['combine']);
        }
        else
        {
            if(isset($_POST['combine']))
            {
                if(Valid::email($_POST['combine'])) $name = 'email';
                else $name = __('phone');

                $this->validation->response['combine']->vars[':name'] = $name;
            }
            
            $this->template->form = View::factory('form_restore_by_combine', $this->validation->response)->render();
        }
    }

    protected function _restore_by_both()
    {
        $this->validation->rule('email', 'email');
        $this->validation->rule('phone', 'required_one_of(email)|phone');

        if($this->validation->check($_POST))
        {
            if(!empty($_POST['email']))
                $this->_restore_by_email_success($_POST['email']);
            elseif(!empty($_POST['phone']))
                $this->_restore_by_phone_success($_POST['phone']);
        }
        else $this->template->form = View::factory('form_restore_by_both', $this->validation->response)->set('mode', 'both')->render();
    }

    protected function _generate_code($mode = 'email')
    {
        if($mode === 'email') return bin2hex(random_bytes(12));
        else return Text::random('numeric', 4);
    }
}
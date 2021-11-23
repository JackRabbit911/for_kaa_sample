<?php
namespace WN\Page\Controller;

use WN\Core\Controller\Controller;
use WN\Core\{I18n, Request, Exception\WnException, Exception\Handler, Session};
use WN\Core\Helper\{Arr, HTTP};
use WN\Page\{Page as ClassPage, TplWrap, Lib};
use WN\User\User;

class Page extends Controller
{
    public $wrapper = 'wrap';
    public $ajax_default = ['main' => 'main', '#lang-choice' => 'lang_choice'];
    public $path = 'views/default/';

    protected function _before()
    {
        $this->user = User::auth();
        $this->session = $this->user::$session;

        $this->_logout();

        $url = $this->request->params('page');
        if(!$url) $url = '/';

        // var_dump($url);

        $lang = I18n::object('block');

        $this->page = ClassPage::factory($url);

        if(!$this->page) $this->_404($url);
        elseif(!$this->page->access()) $this->_http_error(403);
        elseif($this->page->status < ClassPage::PUBLISHED) $this->_http_error(404);

        TplWrap::$engine = TplWrap::PHP;
        if(isset($this->path)) TplWrap::$path = $this->path;

        TplWrap::set_global('lang', $lang);
        TplWrap::set_global('user', $this->user);
        TplWrap::set_global('page', $this->page);

        TplWrap::set_global('SUBDOMAIN', SUBDOMAIN);
    }

    protected function _after()
    {
        $this->session->save();
        parent::_after();
    }

    public function Index()
    {
        // var_dump($this->page);      
        $this->tpl = TplWrap::factory($this->wrapper);
        // var_dump($this->tpl->render());
        $this->tpl->main = TplWrap::factory('main')->render();
        $this->response->body($this->tpl->render());  
    }

    public function ajax_json()
    {
        

        // if(isset($_GET['block']))
        // {
            if(isset($_GET['block']) && is_array($_GET['block']))
                foreach($_GET['block'] AS $key => $value)
                    $data[$key] = TplWrap::factory($value)->render();
            else
                foreach($this->ajax_default AS $key => $value)
                    $data[$key] = TplWrap::factory($value)->render();
        // }

        $data['title'] = $this->page->title();
        $data['keywords'] = $this->page->keywords;
        $data['description'] = $this->page->description;

        $this->response->headers('content-type', 'application/json');
        $this->response->body(json_encode($data));        
    }

    public function ajax_html()
    {
        $view = $this->request->query('view') ?? 'main';
        return TplWrap::factory($view)->render();
    }

    public function sub_request()
    {
        $view = $this->request->query('view') ?? 'main';
        return TplWrap::factory($view)->render();
    }

    public function ajax_post()
    {
        $url = $_POST['url'] ?? false;

        if(!$url) throw new WnException('Hidden input "url" not found');
        else
        {
            $pattern = '/^[a-zA-Z0-9\~\/_?=&+]+$/';
            if(preg_match($pattern, $url) === 1)
                $response = Request::factory($url)->execute();
            else throw new WnException('Hidden input value is incorrect');

            if($this->_isJson($response)) $this->response->headers('content-type', 'application/json');
            return $response;
        }
    }

    public function request()
    {
        if(isset($_GET['request']))
        {
            $target = $_GET['request']['target'] ?? 'main';
            $url = $_GET['request']['url'];

            $this->session->referer = $this->request->referer();
            $this->session->save();

            ob_start();
            echo Request::factory($url)->execute();
            $html = ob_get_clean();

            return ($this->_isJson($html)) ? $html : json_encode([$target => $html]);
        } 
        else throw new WnException('Key "request" not found in query string');
    }

    protected function _logout()
    {
        if(isset($_GET['user']) && $_GET['user'] === 'logout')
        {
            $this->user->log_out();
            HTTP::redirect(HTTP::referer());
            $this->redirect = true;
        }
    }
    
    protected function _404($url)
    {
        throw new WnException('Page not found, url: :url', [':url' => $url], 404);
        // Handler::http_response(404);
    }

    protected function _http_error($code)
    {
        Handler::http_response($code);
    }

    protected function _isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
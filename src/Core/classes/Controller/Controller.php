<?php
namespace WN\Core\Controller;

/**
 * The main Controller class. Executing into the Request.
 * 
 * @author JackRabbit
 */

use WN\Core\Core;
// use WN\Core\View;
use WN\Core\Request;
use WN\Core\Response;
// use Core\Model\Model;

abstract Class Controller //implements \Core\Controller\ContollerInterface
{
    /**
     * instance of the Request class
     * 
     * @var Request
     */
    protected $request;
    
    /**
     * instance of the Response class
     * 
     * @var Response
     */
    public $response;
 
    protected $action;
    
    protected $max_age = 60;
    
    /**
     * Set Request and Response variables
     * Set "global" variables
     * Find the current module and path to them
     * 
     * @param Request $request
     */
    public function __construct(Request $request, $action = 'index')
    {
        $this->request = $request;
        
        $this->response = new Response($request);

        // a valid action name must not have a prefix "_"
        $this->action = ltrim($action, '_');
    }
    
    /**
     * Execute the Controller
     * first - execute before() method
     * second - execute main action method of the child controller
     * third - execute after() method
     * 
     * @param string $action
     */
    public function execute()
    {
        $params = $this->_params();
        $this->response->body(call_user_func_array([$this, '_before'], $params), false);
        $this->response->body(call_user_func_array([$this, $this->action], $params), false);
        $this->response->body(call_user_func_array([$this, '_after'], $params), true);
        return $this->response->body();
    }
    
    /**
     * Method execute before action
     */
    protected function _before(){}
    
    /**
     * Method execute after action
     */
    protected function _after(){}

    protected function _params()
    {
        if($this->request->params('any'))
            return explode('/', $this->request->params('any'));
        else return [];
    }
}

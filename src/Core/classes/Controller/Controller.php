<?php
namespace WN\Core\Controller;

/**
 * The main Controller class. Executing into the Request.
 * 
 * @author JackRabbit
 */
use WN\Core\Exception\WnException;
use WN\Core\{Request, Response};

abstract Class Controller
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
 
    protected $_action;    
    protected $max_age = 60;
    
    /**
     * Set Request and Response variables
     * Set "global" variables
     * Find the current module and path to them
     * 
     * @param Request $request
     * @uses Response
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        
        $this->response = new Response($request);

        foreach($this->request->params() as $key => $value)
            $this->$key = $value;
    }
    
    /**
     * Execute the Controller
     * first - execute before() method
     * second - execute main action or _remap method of the child controller
     * third - execute after() method
     * 
     * @param string $action
     * @uses WnException
     * @return string
     */
    protected function _execute() : string
    {
        // prepare       
        $params = $this->_params();

        // first dtep
        $this->_before(...$params);

        // second step
        if(method_exists($this, '_remap') && is_callable([$this, '_remap'])) //for fans CodeIgniter..
            call_user_func_array([$this, '_remap'], $params);
        else
        {
            if(!method_exists($this, $this->action))
                throw new WnException('Action ":action" does not exists in ":controller"',
                    [
                        ':action' => $this->action,
                        ':controller' => $this->controller,
                    ], 404);
            else
            {
                $reflection = new \ReflectionMethod($this, $this->action);
                if(!$reflection->isPublic() || $reflection->isAbstract())
                    throw new WnException('Action :action is not public',
                        [':action' => $this->request->params('action')], 404);
                else
                    call_user_func_array([$this, $this->action], $params);
            }           
        }

        // third step
        $this->_after(...$params);

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

    private function _params()
    {
        if(isset($this->any))
            return explode('/', $this->any);
        else return [];
    }
}

<?php
namespace Wn\Core\Controller;

use WN\Core\Controller\Controller;
//use Core\Core;
// use Core\Request;
use WN\Core\View;

/**
 * Description of Template
 *
 * @author JackRabbit
 */
abstract Class Template extends Controller
{
    /**
     * name of Template and than object of View class templare
     * 
     * @var string|object
     */
    public $template = 'template';

    protected function _before()
    {
        $this->template = View::factory($this->template);
        parent::_before();            
    }
    
    protected function _after()
    {
        parent::_after();
        echo $this->template->render();       
    }
}
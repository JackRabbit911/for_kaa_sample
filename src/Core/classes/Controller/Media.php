<?php
namespace WN\Core\Controller;

// use WN\Core\Controller\Controller;
use WN\Core\Core;

class Media extends Controller
{
    /**
     * @var numeric
     */
    public $max_age = NULL;
    
    /**
     * @var string
     */
    public $etag = NULL;
    
    /**
     * @var string
     */
    public $dir = 'media/';

    public function index()
    {
        // $file = Core::find_file($this->dir.$this->request->params('file'));
        // $this->_cache_policy($file);
        $this->response->file($this->dir.$this->request->params('file'), $this->max_age, $this->etag);
        // echo $this->dir.$this->request->params('file');
        // var_dump($file); exit;
    }
    
    /**
     * Custom client - cache policy
     * 
     * @param string $file
     * @param string $ext
     */
    private function _cache_policy($file)
    {
        return;
        
        if(!$file) return;
        
        if(stripos($file, 'vendor') !== FALSE)
        {
            return;
        }        
        else 
        {
            $this->max_age = 30;
            $this->etag = md5_file($file);
        }
    }
}
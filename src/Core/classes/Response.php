<?php
namespace WN\Core;

/**
 * Description of Response
 *
 * @author JackRabbit
 */
use WN\Core\Helper\{HTTP, File};
use WN\Core\Exception\Handler;

class Response
{    
    public $body = '';
    public $server_cache_lifetime = 0;
    protected $headers;

    public function __construct($request)
    {
       $this->request = $request;
    }

    public function file($name, $lifetime = null, $etag = null)
    {
        if ($file = Core::find_file($name))
        {
            if(!$this->cache_control($lifetime, $etag))
            {
                // Send the file content as the response
                
                // $this->headers('content-type', File::mime_by_ext(pathinfo($file, PATHINFO_EXTENSION)));
                $this->headers('content-type', File::mime($file));
                header('Content-length: '.filesize($file));
                header('Accept-Ranges: bytes');
                header('Content-Disposition: inline');
                header('Content-Transfer-Encoding: binary');           
                // $this->body(file_get_contents($file));
                readfile($file);
            }
        }
        else
        {
            Handler::http_response(404);
        }
    }
    
    /**
     * Checks the browser cache to see the response needs to be returned,
     * execution will halt and a 304 Not Modified will be sent if the
     * browser cache is up to date.
     *
     * @param  integer   $lifetime  max age
     * @param  string    $etag      Resource ETag
     * @return boolean
     */
    public function cache_control($lifetime = NULL, $etag = NULL)
    {
        // return false;
        if($this->request->params('query')) return FALSE;
        
        if($lifetime)
            $this->headers('cache-control', 'max-age='.$lifetime);

        if($etag)
        { 
            // if($this->headers('cache-control'))
            //     $this->headers('cache-control', $this->response->headers('cache-control').', must-revalidate');
            // else
            //     $this->headers('cache-control', 'must-revalidate');

            $this->headers('etag', $etag);
            
            if ($this->request->headers('if-none-match') AND (string) $this->request->headers('if-none-match') === $etag)
            {
                // No need to send data again
                $this->status(304);
                return TRUE;
            }
            else return FALSE;
        }
    }

    public function status($code, $message = NULL)
    {
        HTTP::status($code, $message);
        return $this;
    }
    
    public function headers($key = null, $value = null)
    {
        $headers = HTTP::response_headers($key, $value);

        return (!$value && is_string($key)) ? $headers : $this;
    }

    public function body(string $body = NULL, $echo = false)
    {
        if($body === NULL) return $this->body;
        elseif($echo === TRUE)
        {
            echo $body;
            return $this;
        }
        else 
        {
            $this->body = $body;
            return $this->body;
        }
    }

    public function __toString()
    {
        return $this->body;
    }
}
<?php
namespace WN\UpFile\Controller;

use WN\Core\Controller\Controller;
use WN\UpFile\UpFile;

class Index extends Controller
{
    public $lifetime;

    public function index()
    {
        $file = new UpFile($this->request->params('id'));
        // var_dump($file); exit;
        $etag = $this->_etag($file->filename);
        $this->response->file($file->filename, $this->lifetime, $etag);
    }

    protected function _etag($filename)
    {
        return null;
        // return md5_file($filename);
    }
}
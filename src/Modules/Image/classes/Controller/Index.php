<?php

namespace WN\Image\Controller;

use WN\Core\Controller\Controller;
use WN\Image\Image;
use WN\Image\ModelEntimg AS Model;
use WN\Core\Pattern\Settings;
use WN\Core\Model\DB\{Table, PDO};
use WN\Core\Helper\{HTML, File, URL, UTF8, HTTP};
use WN\Core\{Session, Route, Im, Core, View};
use WN\Core\Exception\Handler;

class Index extends Controller
{
    // use Settings;

    public $lifetime = null;

    public $width;
    public $height;

    protected function _before()
    {
        // var_dump(Image::$settings); exit;

        // Image::$settings = [
    
        //     'model' => [
        //         'driver' => 'sqlite.test',
        //         'dir' => '/public/test',
        //     ],
        // ];

        // Image::$settings = 'test_img';

        if($this->request->params('action') == 'plh') return;
        parent::_before();

        $this->image = new Image($this->request->params('id'));

        // var_dump(is_file($this->image->filename)); exit;

        if($this->image->filename)
        {
            $etag = null; // = md5_file($this->image->filename);

            $this->response->headers('content-type',  [$this->image->mime]);
            // header('Content-length: '.filesize(realpath($this->image->filename)));
            header('Accept-Ranges: bytes');
            header('Content-Disposition: inline');
            header('Content-Transfer-Encoding: binary');

            $this->response->cache_control($this->lifetime, $etag);
        }

        if(!empty(Image::$stranger['enable']) && Image::$stranger['enable'] === true && $this->_is_stranger())
        {
            call_user_func(Image::$stranger['callback'], $this->image);
            exit;
        }
    }

    public function original()
    {
        if(is_file($this->image->filename))
        {
            if($this->request->params('size'))
            {
                $this->_parse_size();
                $this->image->im()->resize($this->width, $this->height);
                $this->response->body($this->image->im());
            }
            else
                $this->response->body(file_get_contents($this->image->filename));
        }
        else $this->_no_image(404);
    }

    public function image()
    {
        $this->_parse_size();

        if(is_file($this->image->filename))
        {
               if($this->request->params('size'))
                    $this->image->im()->resize($this->width, $this->height);

                call_user_func(Image::$watermark['callback'], $this->image);
                
                $this->response->body($this->image->im());
        }
        else 
        {
            $this->_no_image(404, 'no image', $this->width, $this->height);
        }       
    }

    public function thumb()
    {
        $this->_parse_size();

        $width = ($this->width || $this->height) ? $this->width : Image::$thumbnail['width'];
        $height = ($this->height || $this->width) ? $this->height : Image::$thumbnail['height'];


        if(($content = $this->image->thumbnail($width, $height)) !== false)
            $this->response->body($content);
        elseif(is_file($this->image->filename))
            $this->response->body($this->image->im()->resize($width, $height)->render());
        else $this->_no_image(404, 'no image', $width, $height);
    }

    public function square()
    {
        if(is_file($this->image->filename))
        {
            if(($size = $this->request->params('size')))
            {
                $this->image->im()->resize($size, $size, Im::INVERSE);
            }
            else
            {
                list($width, $height) = getimagesize($this->image->filename);
                $size = ($width >= $height) ? $height : $width;
            }
                       
            $this->image->im()->crop($size, $size);

            call_user_func(Image::$watermark['callback'], $this->image);
            $this->response->body($this->image->im());
        }
        else
        {
            if(!($size = $this->request->params('size'))) $size = 150;
            $this->_no_image(404, 'no image', $size, $size);
        }
    }

    public function crop()
    {
        if($this->request->params('size'))
        {
            $this->_parse_size();
            $width = $this->width;
            $height = $this->height;
        }
        else
        {
            $width = Image::$thumbnail['width'] ?? 150;
            $height = Image::$thumbnail['height'] ?? 150;
        }

        if(is_file($this->image->filename))
        {            
            $im = $this->image->im();
            $im->resize($width, $height, Im::PRECISE);
            if(!$width) $width = $im->width;
            if(!$height) $height = $im->height;
            $im->crop($width, $height);

            // call_user_func(Image::$watermark['callback'], $this->image);
            $this->response->body($im);
        }
        else
        {
            $this->_no_image(404, 'no image', $width, $height);
        }
    }

    public function avatar()
    {
        if($this->request->params('size'))
        {
            $this->_parse_size();
            $width = $this->width;
            $height = $this->height;
        }
        else
        {
            $width = Image::$avatar['width'] ?? 100;
            $height = Image::$avatar['height'] ?? 100;
        }

        if(is_file($this->image->filename))
        {       
            $im = $this->image->im();
            $im->resize($width, $height, Im::PRECISE);
            if(!$width) $width = $im->width;
            if(!$height) $height = $im->height;
            $im->crop($width, $height);

            $this->response->body($im);
        }
        else
        {
            $plh = Image::$avatar['plh'] ?? 'no image';
            $this->_no_image(200, $plh, $width, $height);
        }
    }

    public function plh()
    {
        $this->_parse_size();
        // $this->response->status(404);
        $this->response->headers('Content-Type', ['image/svg+xml']);
        $plh = View::factory('plh')
            ->set('width', $this->width)
            ->set('height', $this->height)
            ->set('bgcolor', $this->request->params('bgcolor'))
            ->set('color', $this->request->params('color'))
            ->set('text', $this->request->query('text'));
        
        $this->response->body($plh);
    }

    protected function _parse_size()
    {
        if(!$this->request->params('size')) return;

        $empty_string_to_null = function($value){
            return (empty($value)) ? null : $value;
        };

        $arr = explode('x', $this->request->params('size'));
        list($this->width, $this->height) = array_map($empty_string_to_null, $arr);
    }

    public static function _watermark(Image $image, $settings = null)
    {
        $settings = ($settings) ? (object) $settings : (object) Image::$watermark;

        if(isset($settings->enable) && $settings->enable === true)
        {
            if(!property_exists($settings, 'filepath'))
                $settings->filepath = 'media/img/wm.png';

            if(($wmpath = Core::find_file($settings->filepath)))
            {
                $wmim = Im::factory($wmpath);

                if(isset($settings->direction))
                    $wmim->rotate($settings->direction);

                if(!property_exists($settings, 'size'))
                    $settings->size = 50;

                $divisor = 100/$settings->size;

                $wmim->resize($image->im()->width/$divisor, $image->im()->height/$divisor);
                
                if(!property_exists($settings, 'offset_x'))
                    $settings->offset_x = true;

                if(!property_exists($settings, 'offset_y'))
                    $settings->offset_y = true;

                if(!property_exists($settings, 'opacity'))
                    $settings->opacity = 50;

                $image->im()->watermark($wmim, $settings->offset_x, $settings->offset_y, $settings->opacity);
            }
        }
    }

    protected function _no_image($status = 404, $text = 'no image', $width = null, $height = null)
    {
        $this->response->status($status);

        if(($file = Core::find_file("media/img/$text")))
        {
            // $mime = image_type_to_mime_type(exif_imagetype($file));
            $ext = (pathinfo($file, PATHINFO_EXTENSION));
            $this->response->headers('Content-Type', File::mime_by_ext($ext));
            // $this->response->headers('Content-Type', ['image/svg+xml']);
            if($ext === 'svg') $plh = file_get_contents($file);
            else $plh = Im::factory($file)->resize($width, $height);
            // $this->response->body(file_get_contents($file));
            // readfile($file);
            // $this->response->body($im);
        }
        else
        {
            $this->response->headers('Content-Type', ['image/svg+xml']);
            $plh = View::factory('plh');
            $plh->set('width', $width);
            $plh->set('height', $height);
            $plh->set('text', $text);
            // $this->response->body($plh);
        }
        
        $this->response->body($plh);
    }

    protected function _is_stranger()
    {
        $host = HTTP::scheme().'://'.HTTP::domain();
        if(!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $host) !== 0) return true;
        else return false;
    }

    protected function _to_stranger(Image $image)
    {
        if(is_file($image->filename))
        {
            // $this->response->body(file_get_contents($this->image->filename), true);
            $watermark = [
            'enable'    => true,
            // 'callback'  => [__CLASS__, '_watermark'],
            // 'filepath'    => 'media/img/wm.png',
            'direction' => 45,
            'size'      => 100,
            'offset_x'  => null,
            'offset_y'  => null,
            ];
            
            $watermark = array_replace(Image::$watermark, $watermark);

            $size = Image::$stranger['size'] ?? Image::$thumbnail['height'];

            $this->image->im()->resize($size, $size);
            call_user_func($watermark['callback'], $this->image, $watermark);
            // $this->_watermark($this->image->im(), $watermark);
            $this->response->body($this->image->im(), true);
        }
        else $this->_no_image(404, 'no image', Image::$thumbnail['width'], Image::$thumbnail['height']);
    }
}
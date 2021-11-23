<?php
namespace WN\Image;

use WN\UpFile\UpFile;
use WN\Core\{Im, Route, View, Core};
use WN\Core\Helper\{UTF8, File, URL, Upload, HTML};
use WN\Core\Pattern\Entity;

class Image extends UpFile
{
    public static $watermark = [
        'enable'    => true,
        'callback'  => ['WN\Image\Controller\Index', '_watermark'],
        // 'filepath'    => 'media/img/wm.png',
        'direction' => -90,
        // 'size'      => 50,
        // 'offset_x'  => true,
        // 'offset_y'  => true,
    ];

    public static $thumbnail = [
        'width'     => null,
        'height'    => 150,
        'filesize'  => 102400,
    ];

    public static $avatar = [
        'width'     => 100,
        'height'    => 100,
        'plh'       => 'no_avatar.svg',
    ];

    public static $stranger = [
        'enable'    => true,
        'callback'  => ['WN\Image\Controller\Index', '_to_stranger'],
        'size'      => 250,
    ];

    protected $im;

    public function alt($text = null)
    {
        if($text)
        {
            $this->data['alt'] = $text;
            return $this;
        }
        else
        {
            if(!empty($this->data['alt'])) return $this->data['alt'];
            elseif(isset($this->data['orig_name'])) return pathinfo($this->data['orig_name'], PATHINFO_FILENAME);
            else return null;
        }
    }

    public function im()
    {
        if(!$this->im) $this->im = Im::factory($this->filename);
        return $this->im;
    }

    public function thumbnail_name()
    {
        $basename = basename($this->filename);
        $path = dirname($this->filename);
        return "$path/thumb_$basename";
    }

    public function thumbnail($width = null, $height = null)
    {
        $filename = $this->thumbnail_name();

        if(!is_file($filename)) return false;

        list($w, $h) = getimagesize($filename);

        if(($width || $height) && (($width && $w < $width) || ($height && $h < $height)))
            return $this->im()->resize($width, $height)->render();
        elseif(($width || $height) && (($width && $w > $width) || ($height && $h > $height)))
            return Im::factory($filename)->resize($width, $height)->render();
        else 
            return file_get_contents($filename);
    }

    public function create_thumbnail($width = null, $height = null)
    {
        if(!$width) $width = static::$thumbnail['width'];
        if(!$height) $height = static::$thumbnail['height'];

        list($w, $h) = getimagesize($this->filename);

        if($width >= $w || $height >= $h) return;
        if(!empty(static::$thumbnail['filesize']) && filesize($this->filename) < static::$thumbnail['filesize']) return;

        $filename = $this->thumbnail_name();

        $this->im()->resize($width, $height)->save($filename);

        return $this;
    }

    public function src($action = 'original', $width = null, $height = null)
    {
        if(in_array($action, ['image', 'thumb', 'avatar', 'crop'])) 
        {
            $route_name = 'image';
            $size = ($width === null && $height === null) ? '' 
            : $width.'x'.$height;
        }
        elseif($action === 'base64')
        {
            if(!is_file($this->filename))
            {
                $plh = View::factory('plh');
                $plh->set('width', $width);
                $plh->set('height', $height);
                $plh->set('text', 'no image');
                return 'data:image/svg+xml;base64,' . base64_encode($plh->render());
            }
          
            if(!$height) $height = $width;
            if($width || $height)
                $this->im()->resize($width, $height);
                
                // call_user_func(Image::$watermark['callback'], $this);

            return 'data:'.$this->mime.';base64,'.base64_encode($this->im()->render());
        }
        else
        {
            $route_name = 'image';
            // $route_name = $action;
            $size = $width;
        }

        $id = $this->id ?? 0;
        $file = $this->data['orig_name'] ?? 'no_image.svg';
        
        $route = Route::get($route_name);
        $uri = $route->uri([
            'action'=> $action,
            'id'    => $id,
            'file'  => static::name2ascii($file),
            'size'  => $size,
            ]);

        return $uri;
    }

    public function html() //array $attr = null, $type = 'original', $width = null, $height = null)
    {
        $args = func_get_args();

        if(isset($args[0]) && is_string($args[0]))
            array_unshift($args, []);

        $attr = $args[0] ?? [];
        $type = $args[1] ?? 'original';
        $width = $args[2] ?? null;
        $height = $args[3] ?? null;

        if(!isset($attr['alt'])) $attr['alt'] = $this->alt();

        $src = $this->src($type, $width, $height);
        return HTML::image($src, $attr);
    }

    public function __sleep()
    {
        return array('id');
    }

    public function __wakeup()
    {
        $this->__construct($this->id);
    }
}
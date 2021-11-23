<?php
namespace WN\Core\Session;

use WN\Core\Session;

class File// implements Sessionable
{
    public static $path = APPPATH.'data/session/';
    public static $mask = '{,.}*';

    public static function find($id)
    {
        $mask = '?'.$id.'*';
        // echo $mask;
        foreach(glob(static::$path.$mask) AS $file)
        {
            if(substr($file, -1) === '.') continue;
            if(is_file($file))
            {
                $is_long = (bool) basename($file)[0];
                $lifetime = ($is_long) ? Session::$long : Session::$short;

                if($lifetime > (time() - filemtime($file)))
                {
                    $data['is_long'] = $is_long;
                    $data['data'] = unserialize(file_get_contents($file));
                    return $data;
                }
                else unlink($file);
            }            
        }

        return false;
    }

    public static function online($lifetime)
    {
        $users = $guests = 0;
        foreach(glob(static::$path.static::$mask, GLOB_BRACE) AS $file)
        {
            if(substr($file, -1) === '.') continue;
            elseif($lifetime !== null && $lifetime < (time() - filemtime($file))) continue;
            else
            {
                $file = pathinfo($file, PATHINFO_FILENAME);
                $user = substr($file, 33);
                if($user) $users++;
                else $guests++;
            }
        }

        return ['all' => $users+$guests, 'users' => $users, 'guests' => $guests];
    }

    public static function gd($lifetime, $is_long = false)
    {
        if($is_long === true) $mask = '*';
        else $mask = '0*';

        $count = 0;

        foreach(glob(static::$path.$mask, GLOB_BRACE) AS $p)
        {
            if(substr($p, -1) === '.') continue;
            elseif(is_file($p) && ($lifetime === null || ($lifetime < (time() - filemtime($p)))))
            {
                unlink($p);
                $count++;
            }
        }

        return $count;
    }

    public static function get_last($user_id)
    {
        $mask = str_repeat('?', 33).$user_id;

        $arr = glob(static::$path.$mask, GLOB_BRACE | GLOB_NOSORT);

        if(empty($arr)) return false;

        $cmp = function($a, $b)
        {
            $at = filemtime($a);
            $bt = filemtime($b);

            return $bt <=> $at;
        };

        usort($arr, $cmp);

        $file = array_shift($arr);
        $filename = basename($file);

        $res['id'] = substr($filename, 1, 32); //substr($file, 1, 32);
        $res['last_activity'] = filemtime($file);
        $res['is_long'] = $filename[0];
        $res['user_id'] = substr($filename, 33);
        $res['data'] = unserialize(file_get_contents($file));

        return $res;
    }

    public static function save($data, $old_sid = null)
    {
        $user_id = $data['user_id'] ?? '';
        $is_long = $data['is_long'];
        $sid = $data['id'];
        $content = $data['data'];

        $file = static::$path.$is_long.$sid.(string)$user_id;

        if($old_sid)
        {
            $mask = static::$path.'?'.$old_sid.(string)$user_id;
            $dir = glob($mask);
            $old_file = $dir[0] ?? false;
            // echo ' ', $old_file;
            if(is_file($old_file)) rename($old_file, $file);
        }

        // if(is_file($file))
        //     $old_content = unserialize(file_get_contents($file));
        // else $old_content = [];

        // $content = array_replace_recursive($old_content, $data['data']);

        // file_put_contents($file, serialize($content));

        file_put_contents($file, serialize($data['data']));

        $lifetime = ($data['is_long']) ? Session::$long : Session::$short;
        if($lifetime > 0) touch($file);
    }

    public static function delete($sid, $user_id, $mode = 0)
    {
        // $files[] = static::$path.'0'.$old_sid.(string)$user_id;
        // $files[] = static::$path.'1'.$old_sid.(string)$user_id;
        $files[] = static::$path.'0'.$sid.(string)$user_id;
        $files[] = static::$path.'1'.$sid.(string)$user_id;

        if($mode = 1)
        {
            $mask = str_repeat('?', 33).$user_id;
            foreach(glob(static::$path.$mask, GLOB_BRACE) AS $file)
            {
                if(substr($file, -1) === '.') continue;
                unlink($file);
            }
        }
        elseif($mode = 2)
        {
            $mask = str_repeat('?', 33).$user_id;
            foreach(glob(static::$path.$mask, GLOB_BRACE) AS $file)
            {
                if(substr($file, -1) === '.') continue;
                if(!in_array($file, $files)) unlink($file);
            }
        }
        else
        {
            foreach($files AS $file)
            {
                if(is_file($file)) unlink($file);
            }
        }
    }
}
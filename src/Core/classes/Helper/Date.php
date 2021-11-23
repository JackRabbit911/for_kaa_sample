<?php

namespace WN\Core\Helper;

use WN\Core\I18n;

class Date
{
    public static function timestamp(string $date = null, string $format = null)
    {
        if(!$date) return time();
        elseif(is_numeric($date)) return $date;

        if(!$format) $format = I18n::l10n('date');
        // var_dump($format); exit;
        $d = date_create_from_format($format.' h', $date.' 12');
        // var_dump($d); exit;
        return $d->getTimestamp();
    }

    public static function format(int $timestamp = null, string $format = null)
    {
        if(!$format) $format = I18n::l10n('date');
        if(!$timestamp) $timestamp = time();
        return date($format, $timestamp);
    }

    public static function interval($timestamp1, $timestamp2 = null, $format = '%y')
    {
        if(!$timestamp2) $timestamp2 = time();
        $t1 = date_create()->setTimestamp($timestamp1);
        $t2 = date_create()->setTimestamp($timestamp2);
        return $t2->diff($t1)->format($format);
    }

    public static function sec2array($secs)
    {
        $res = array();
        
        if(($d = floor($secs / 86400))) $res['days'] = (int) $d;
        $secs = $secs % 86400;
        
        if(($h = floor($secs / 3600))) $res['hours'] = (int) $h;
        $secs = $secs % 3600;
    
        if(($m = floor($secs / 60))) $res['minutes'] = (int) $m;
        if(($s = $secs % 60)) $res['seconds'] = (int) $s;
    
        return $res;
    }

    public static function sec2str($secs, $words = null)
    {
        $array = static::sec2array($secs);

        foreach($array AS $key => &$value)
            $value =I18n::plural($value, Inflector::singular($key));

        return implode(', ', $array);
    }
}
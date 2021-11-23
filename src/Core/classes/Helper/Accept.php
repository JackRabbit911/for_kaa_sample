<?php

namespace WN\Core\Helper;

// use WN\Core\Helper\Arr;
/**
 * Description of Headers
 *
 * @author JackRabbit
 */
class Accept
{
    const DEFAULT_QUALITY = 1;
    
    // protected static $_headers;
    protected static $_accept_language;
    
    // public function __construct(array $headers = NULL)
    // {
    //     if($headers === NULL)
    //         self::$_headers = apache_request_headers();
    //     else  self::$_headers = $headers;
    // }
    
    public static function headers($key = NULL)
    {
        return HTTP::request_headers($key);
        // if(!self::$_headers) self::$_headers = apache_request_headers();
        // if($key === NULL) return self::$_headers;
        // else return Arr::get(self::$_headers, $key);
    }
    
    /**
     * Parses an Accept(-*) header and detects the quality
     *
     * @param   array   $parts  accept header parts
     * @return  array
     * @since   3.2.0
     */
    public static function quality(array $parts)
    {
        $parsed = array();

        // Resource light iteration
        $parts_keys = array_keys($parts);
        foreach ($parts_keys as $key)
        {
            $value = trim(str_replace(array("\r", "\n"), '', $parts[$key]));

            $pattern = '~\b(\;\s*+)?q\s*+=\s*+([.0-9]+)~';

            // If there is no quality directive, return default
            if ( ! preg_match($pattern, $value, $quality))
            {
                $parsed[$value] = (float) self::DEFAULT_QUALITY;
            }
            else
            {
                $quality = $quality[2];

                if ($quality[0] === '.')
                {
                        $quality = '0'.$quality;
                }

                // Remove the quality value from the string and apply quality
                $parsed[trim(preg_replace($pattern, '', $value, 1), '; ')] = (float) $quality;
            }
        }

        return $parsed;
    }
    
     /**
     * Parses the `Accept-Language:` HTTP header and returns an array containing
     * the languages and associated quality.
     *
     * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
     * @param   string  $language   charset string to parse
     * @return  array
     * @since   3.2.0
     */
    public static function parse_language_header($language = NULL)
    {
        if ($language === NULL)
        {
            return array('*' => array('*' => (float) self::DEFAULT_QUALITY));
        }

        $language = strtolower($language);

        $language = self::quality(explode(',', (string) $language));

        $parsed_language = array();

        $keys = array_keys($language);
        foreach ($keys as $key)
        {
            // Extract the parts
            $parts = explode('-', $key, 2);

            // Invalid content type- bail
            if ( ! isset($parts[1]))
            {
                    $parsed_language[$parts[0]]['*'] = $language[$key];
            }
            else
            {
                    // Set the parsed output
                    $parsed_language[$parts[0]][$parts[1]] = $language[$key];
                    $parsed_language[$parts[0]]['*'] = $language[$key];
            }
        }

        return $parsed_language;
    }
    
    /**
     * Returns the quality of `$language` supplied, optionally ignoring
     * wildcards if `$explicit` is set to a non-`FALSE` value. If the quality
     * is not found, `0.0` is returned.
     *
     *     // Accept-Language: en-us, en-gb; q=.7, en; q=.5
     *     $lang = $header->accepts_language_at_quality('en-gb');
     *     // $lang = (float) 0.7
     *
     *     $lang2 = $header->accepts_language_at_quality('en-au');
     *     // $lang2 = (float) 0.5
     *
     *     $lang3 = $header->accepts_language_at_quality('en-au', TRUE);
     *     // $lang3 = (float) 0.0
     *
     * @param   string  $language   language to interrogate
     * @param   boolean $explicit   explicit interrogation, `TRUE` ignores wildcards
     * @return  float
     * @since   3.2.0
     */
    public static function language_at_quality($language, $explicit = FALSE)
    {
        if(empty(static::$_accept_language))
            static::$_accept_language = self::parse_language_header(self::headers('Accept-Language'));

        // Normalize the language
        $language_parts = explode('-', strtolower($language), 2);

        if (isset(static::$_accept_language[$language_parts[0]]))
        {
            if (isset($language_parts[1]))
            {
                if (isset(static::$_accept_language[$language_parts[0]][$language_parts[1]]))
                {
                    return static::$_accept_language[$language_parts[0]][$language_parts[1]];
                }
                elseif ($explicit === FALSE AND isset(static::$_accept_language[$language_parts[0]]['*']))
                {
                    return static::$_accept_language[$language_parts[0]]['*'];
                }
            }
            elseif (isset(static::$_accept_language[$language_parts[0]]['*']))
            {
                return static::$_accept_language[$language_parts[0]]['*'];
            }
        }

        if ($explicit === FALSE AND isset(static::$_accept_language['*']))
        {
            return static::$_accept_language['*'];
        }

        return (float) 0;
    }
    
    /**
     * Returns the preferred language from the supplied array `$languages` based
     * on the `Accept-Language` header directive.
     *
     *      // Accept-Language: en-us, en-gb; q=.7, en; q=.5
     *      $lang = $header->preferred_language(array(
     *          'en-gb', 'en-au', 'fr', 'es'
     *      )); // $lang = 'en-gb'
     *
     * @param   array   $languages
     * @param   boolean $explicit
     * @return  mixed
     * @since   3.2.0
     */
    public static function preferred_language(array $languages, $explicit = FALSE)
    {
        $ceiling = 0;
        $preferred = FALSE;

        foreach ($languages as $language)
        {
                $quality = self::language_at_quality($language, $explicit);

                if ($quality > $ceiling)
                {
                        $ceiling = $quality;
                        $preferred = $language;
                }
        }

        return $preferred;
    }
    
    /**
     * Returns quality or the preferred language depends $arg
     * 
     * @param boolean|string|array $arg
     * @param boolean $explicit
     * @return string|float
     */
    public static function language($arg = NULL, $is_locale = FALSE)
    {
        if($arg === NULL)
        {
            $h = "en;q=0.7,en-US;q=0.8,ru-RU,ru;q=0.9";
            // if(empty(static::$_accept_language))
                static::$_accept_language = self::parse_language_header($h);//(self::headers('Accept-Language'));           
            
            $quality = 0; $res = array();
            foreach(static::$_accept_language AS $lang=>$locale)
            {
                // var_dump($locale);
                // echo '<br>';
                if($quality <= max($locale))
                {
                    $quality  = max($locale);
                    $res[$lang] = $quality;
                    // $loc[key($locale).' ';
                }
            }
            // return $res;          
            $arr_lang = (!empty($res)) ? array_keys($res, max($res)) : array('*'=>1);
            $arr_locale = (!empty($locale)) ? array_keys($locale, max($locale)) : ['*'=>1];
            
            $preferred_lang = $arr_lang[0] ?? '*';
            $preferred_locale = $arr_locale[0] ?? '*';

            return ($is_locale) ? [$preferred_lang, $preferred_locale] : $preferred_lang;
        }
        elseif(is_array($arg) && !empty($arg))
        {
            return self::preferred_language($arg);
        }
        else
        {
            return self::language_at_quality($arg);
        }
        
    }
    
    /**
     * Returns array of allows encodings or TRUE/FALSE depends $arg
     * 
     * @param boolean|string $arg
     * @return array|float
     */
    public static function encoding($arg = NULL)
    {
        $header = self::headers('Accept-Encoding');        
        $array = self::quality(explode(',', $header));        
        return ($arg) ? Arr::get($array, $arg, 0) : $array;
    }
    
    public static function type($arg = NULL)
    {
        $header = self::headers('Accept');    
        $array = self::quality(explode(',', $header));
        return ($arg) ? Arr::get($array, $arg, 0) : $array;
    }
    
    public static function charset($arg = NULL)
    {
        $header = self::headers('Accept-Charset');    
        $array = self::quality(explode(',', $header));
        return ($arg) ? Arr::get($array, $arg, 0) : $array;
    }
}

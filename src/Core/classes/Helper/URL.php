<?php

/**
 * URL helper class.
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
namespace WN\Core\Helper;

use WN\Core\Core;
use WN\Core\Request;
use WN\Core\Helper\HTTP;

class URL {

	/**
	 * Gets the base URL to the application.
	 * To specify a protocol, provide the protocol as a string or request object.
	 * If a protocol is used, a complete URL will be generated using the
	 * `$_SERVER['HTTP_HOST']` variable.
	 *
	 *     // Absolute URL path with no host or protocol
	 *     echo URL::base();
	 *
	 *     // Absolute URL path with host, https protocol and index.php if set
	 *     echo URL::base('https', TRUE);
	 *
	 *     // Absolute URL path with host and protocol from $request
	 *     echo URL::base($request);
	 *
	 * @param   mixed    $protocol Protocol string, [Request], or boolean
	 * @param   boolean  $index    Add index file to URL?
	 * @return  string
	 * @uses    Kohana::$index_file
	 * @uses    Request::protocol()
	 */
	public static function base($protocol = NULL, $index = FALSE)
	{
		// Start with the configured base URL
		$base_url = BASEDIR;

		// if ($protocol === TRUE)
		// {
		// 	// Use the initial request to get the protocol
		// 	$protocol = Request::initial();
		// }

		// if ($protocol instanceof Request)
		// {
		// 	if ( ! $protocol->secure())
		// 	{
		// 		// Use the current protocol
		// 		list($protocol) = explode('/', strtolower($protocol->protocol()));
		// 	}
		// 	else
		// 	{
		// 		$protocol = 'https';
		// 	}
		// }

		if($protocol === true) $protocol = HTTP::scheme(); //(isset($_SERVER['HTTPS'])) ? strtolower($_SERVER['HTTPS']) : 'http';

		if ( ! $protocol)
		{
			// Use the configured default protocol
			$protocol = parse_url($base_url, PHP_URL_SCHEME);
		}

		if ($index === TRUE AND ! empty(Core::$index_file))
		{
			// Add the index file to the URL
			$base_url .= Core::$index_file.'/';
		}

		if (is_string($protocol))
		{
			if ($port = parse_url($base_url, PHP_URL_PORT))
			{
				// Found a port, make it usable for the URL
				$port = ':'.$port;
			}

			// if ($domain = parse_url($base_url, PHP_URL_HOST))
			// {
			// 	// Remove everything but the path from the URL
			// 	$base_url = parse_url($base_url, PHP_URL_PATH);
			// }
			// else
			// {
			// 	// Attempt to use HTTP_HOST and fallback to SERVER_NAME
			// 	$domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			// }

			// Add the protocol and domain to the base URL
			$base_url = $protocol.'://'.HTTP::domain().$port.$base_url;
		}

		return $base_url;
	}

	/**
	 * Fetches an absolute site URL based on a URI segment.
	 *
	 *     echo URL::site('foo/bar');
	 *
	 * @param   string  $uri        Site URI to convert
	 * @param   mixed   $protocol   Protocol string or [Request] class to use protocol from
	 * @param   boolean $index		Include the index_page in the URL
	 * @return  string
	 * @uses    URL::base
	 */
	public static function site($uri = '', $protocol = NULL, $index = FALSE)
	{
		// Chop off possible scheme, host, port, user and pass parts
		$path = preg_replace('~^[-a-z0-9+.]++://[^/]++/?~', '', trim($uri, '/'));

		if ( ! UTF8::is_ascii($path))
		{
			// Encode all non-ASCII characters, as per RFC 1738
			$path = preg_replace_callback('~([^/]+)~', 'WN\Core\Helper\URL::_rawurlencode_callback', $path);
		}

		// Concat the URL
		return URL::base($protocol, $index).$path;
	}

	/**
	 * Callback used for encoding all non-ASCII characters, as per RFC 1738
	 * Used by URL::site()
	 *
	 * @param  array $matches  Array of matches from preg_replace_callback()
	 * @return string          Encoded string
	 */
	protected static function _rawurlencode_callback($matches)
	{
		return rawurlencode($matches[0]);
	}

	/**
	 * Merges the current GET parameters with an array of new or overloaded
	 * parameters and returns the resulting query string.
	 *
	 *     // Returns "?sort=title&limit=10" combined with any existing GET values
	 *     $query = URL::query(array('sort' => 'title', 'limit' => 10));
	 *
	 * Typically you would use this when you are sorting query results,
	 * or something similar.
	 *
	 * [!!] Parameters with a NULL value are left out.
	 *
	 * @param   array    $params   Array of GET parameters
	 * @param   boolean  $use_get  Include current request GET parameters
	 * @return  string
	 */
	public static function query(array $params = NULL, $use_get = TRUE)
	{
		if ($use_get)
		{
			if ($params === NULL)
			{
				// Use only the current parameters
				$params = $_GET;
			}
			else
			{
				// Merge the current and new parameters
				$params = Arr::merge($_GET, $params);
			}
		}

		if (empty($params))
		{
			// No query parameters
			return '';
		}

		// Note: http_build_query returns an empty string for a params array with only NULL values
		$query = http_build_query($params);

		// Don't prepend '?' to an empty string
		return ($query === '') ? '' : ('?'.$query);
	}

	/**
	 * Convert a phrase to a URL-safe title.
	 *
	 *     echo URL::title('My Blog Post'); // "my-blog-post"
	 *
	 * @param   string   $title       Phrase to convert
	 * @param   string   $separator   Word separator (any single character)
	 * @param   boolean  $ascii_only  Transliterate to ASCII?
	 * @return  string
	 * @uses    UTF8::transliterate_to_ascii
	 */
	public static function title($title, $separator = '-', $ascii_only = FALSE)
	{
		if ($ascii_only === TRUE)
		{
			// Transliterate non-ASCII characters
			$title = UTF8::transliterate_to_ascii($title);

			// Remove all characters that are not the separator, a-z, 0-9, or whitespace
			$title = preg_replace('![^'.preg_quote($separator).'a-z0-9\s]+!', '', strtolower($title));
		}
		else
		{
			// Remove all characters that are not the separator, letters, numbers, or whitespace
			$title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
		}

		// Replace all separator characters and whitespace by a single separator
		$title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

		// Trim separators from the beginning and end
		return trim($title, $separator);
	}
        
	public static function ns2url($str, $delimeter = '-')
	{
		return strtolower(str_replace('\\', $delimeter, $str));
	}
	
	public static function url2ns($str, $delimeter = '-')
	{
		return str_replace($delimeter, '\\', ucwords($str, $delimeter));
	}

	public static function segment($key = NULL, $uri = NULL)
	{
		if(!$uri)
			$uri = trim(HTTP::detect_path(), '/');

		$segments = explode('/', $uri);
		return ($key !== NULL) ? Arr::get($segments, $key) : $segments;
	}

	public static function remove_query($url, array $keys = null)
	{
		if(strpos($url, '?') !== false)
			list($uri, $query_string) = explode('?', $url);
		else return $url;

		if($keys === null || $query_string === '') return $uri;

		parse_str($query_string, $query_array);

		foreach($keys AS $key)
		{
			if(array_key_exists($key, $query_array))
				unset($query_array[$key]);
		}
		
		return rtrim($uri.'?'.http_build_query($query_array), '?');
		
	}

	public static function output_reset_rewrite_var($key)
	{
		if(array_key_exists($key, $_GET))
		{
			unset($_GET[$key]);
			output_reset_rewrite_vars();

			foreach($_GET as $key => $value)
			{
				output_add_rewrite_var($key, $value);
			}
		}
	}
}

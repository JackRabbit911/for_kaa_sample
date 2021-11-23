<?php
/**
 * Upload helper class for working with uploaded files and [Validation].
 *
 *     $array = Validation::factory($_FILES);
 *
 * [!!] Remember to define your form with "enctype=multipart/form-data" or file
 * uploading will not work!
 *
 * The following configuration properties can be set:
 *
 * - [Upload::$remove_spaces]
 * - [Upload::$default_directory]
 *
 * @package    WN
 * @category   Helpers
 * @author     WN Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
namespace WN\Core\Helper;

use WN\Core\Exception\{WnException, Debug};
use WN\Core\Validation\Response;

class Upload
{

	/**
	 * @var  boolean  remove spaces in uploaded files
	 */
	public static $remove_spaces = TRUE;

	/**
	 * @var  string  default upload directory
	 */
	public static $default_directory = 'src/App/upload';

	public static $upload_multiple_auto = false;

	/**
	 * Save an uploaded file to a new location. If no filename is provided,
	 * the original filename will be used, with a unique prefix added.
	 *
	 * This method should be used after validating the $_FILES array:
	 *
	 *     if ($array->check())
	 *     {
	 *         // Upload is valid, save it
	 *         Upload::save($array['file']);
	 *     }
	 *
	 * @param   array   $file       uploaded file data
	 * @param   string  $filename   new filename
	 * @param   string  $directory  new directory
	 * @param   integer $chmod      chmod mask
	 * @return  string  on success, full path to new file
	 * @return  FALSE   on failure
	 */
	public static function save(array $file, $filename = NULL, $directory = NULL, $chmod = 0644)
	{
		// var_dump($file); exit;

		if ( ! isset($file['tmp_name']) OR ! is_uploaded_file($file['tmp_name']))
		{
			// Ignore corrupted uploads
			return FALSE;
		}

		if ($filename === NULL)
		{
			// Use the default filename, with a timestamp pre-pended
			$filename = uniqid().'_'.$file['name'];
		}

		if (Upload::$remove_spaces === TRUE)
		{
			// Remove spaces from the filename
			$filename = preg_replace('/\s+/u', '_', $filename);
		}

		if ($directory === NULL)
		{
			// Use the pre-configured upload directory
			$directory = Upload::$default_directory;
		}
		elseif($directory[0] !== '/') $directory = Upload::$default_directory.'/'.$directory;
		// else $directory = $_SERVER['DOCUMENT_ROOT'].$directory;

		$directory = ltrim($directory, '/');

		if(!is_dir($directory)) mkdir($directory, 0644, true);
		elseif(!is_writable(realpath($directory))) chmod($directory, 0644);

		// Make the filename into a complete path
		$filename = $directory.'/'.$filename;

		if (move_uploaded_file($file['tmp_name'], $filename))
		{
			if ($chmod !== FALSE)
			{
				// Set permissions on filename
				chmod($filename, $chmod);
			}

			// Return new file path
			return $filename;
		}

		return FALSE;
	}

	/**
	 * Tests if upload data is valid, even if no file was uploaded. If you
	 * _do_ require a file to be uploaded, add the [Upload::not_empty] rule
	 * before this rule.
	 *
	 *     $array->rule('file', 'Upload::valid')
	 *
	 * @param   array   $file   $_FILES item
	 * @return  bool
	 */
	public static function valid($file)
	{
		return (isset($file['error'])
			AND isset($file['name'])
			AND isset($file['type'])
			AND isset($file['tmp_name'])
			AND isset($file['size']));
	}

	public static function valid_multiple($file)
	{
		return (isset($file['error']) && is_array($file['error'])
			AND isset($file['name']) && is_array($file['name'])
			AND isset($file['type']) && is_array($file['type'])
			AND isset($file['tmp_name']) && is_array($file['tmp_name'])
			AND isset($file['size']) && is_array($file['size']));
	}

	public static function check(&$validation, $name, $file)
	{
		if(static::valid_multiple($file)) return static::check_upload_multiple($validation, $name, $file);

		if(!isset($validation->rules[$name])) $validation->rules[$name] = [];

		$args = [];

		array_unshift($validation->rules[$name], [[__CLASS__, 'internal']]);

		foreach($validation->rules[$name] AS $args)
		{
			$func = array_shift($args);
			array_unshift($args, $file);

			if(!is_callable($func)) $func = [Upload::class, $func];
			elseif(is_string($func)) $func = (strpos($func, '::') !== false) ? explode('::', $func) : $func;

			if(($check = call_user_func_array($func, $args)) !== true) break;
		}

		$args = $args + [':name' => $name];

		$validation->response[$name] = new Response($func, $args);
		$validation->response[$name]->check($check, ['value' => $file]);
		
		foreach($file as $key => $val)
			$validation->response[$name]->vars[':value_'.$key] = $val;

		return ($check === true) ? true : false;
	}

	public static function check_upload_multiple(&$validation, $name, $value)
    {
		$msg = $files = '';

        foreach($value['error'] AS $i => $error)
        {

            $f = ['name' => $value['name'][$i],
                'type'  => $value['type'][$i],
                'tmp_name' => $value['tmp_name'][$i],
                'error' => $error,
                'size'  => $value['size'][$i],
            ];

            $check[$i] = static::check($validation, $name, $f);

            if($check[$i] === true)
            {
                if(Upload::$upload_multiple_auto)
                    $validation->uploaded_files[] = Upload::save($f);                            
                else $validation->files_to_upload[] = $f;

				$files .= $f['name'].', ';
            }
            else
            {
				$msg .= $validation->response[$name]->msg().PHP_EOL;

                unset($_FILES[$name]['name'][$i], 
                    $_FILES[$name]['type'][$i], 
                    $_FILES[$name]['tmp_name'][$i], 
                    $_FILES[$name]['error'][$i], 
                    $_FILES[$name]['size'][$i]);

                $check[$i] = false;    
            }
        }

		$validation->response[$name]->value = rtrim($files, ', ');
		$validation->response[$name]->msg = rtrim($msg, PHP_EOL);
		$validation->response[$name]->code = null;
		$validation->response[$name]->vars = [];
		$validation->response[$name]->message = null;
		$validation->response[$name]->value = rtrim($files, ', ');

        return !in_array(false, $check);
    }

	public static function internal($file)
	{
		switch($file['error'])
		{
			case 0: return true;
			case 1:
				$size = ini_get('upload_max_filesize');
				return ['vars' => [':size' => $size], 'code' => 'internal_err_1', 'status' => false];
				// return ['vars' => [':size' => $size], 'code' => 'internal_err_1'];
				// return 'The uploaded file '.$file['name'].' exceeds the upload_max_filesize directive: '.$size;
			case 2: return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case 3: return 'The uploaded file was only partially uploaded';
			case 4: return true;
			case 6: return 'Missing a temporary folder';
			case 7: return 'Failed to write file to disk';
			case 8: return 'A PHP extension stopped the file upload';
			default: return true;
		}
	}

	public static function required(array $file)
	{
		return static::not_empty($file);
	}

	/**
	 * Tests if a successful upload has been made.
	 *
	 *     $array->rule('file', 'Upload::not_empty');
	 *
	 * @param   array   $file   $_FILES item
	 * @return  bool
	 */
	public static function not_empty(array $file)
	{
		return (isset($file['error'])
			AND isset($file['tmp_name'])
			AND $file['error'] === UPLOAD_ERR_OK
			AND is_uploaded_file($file['tmp_name']))
			? true : 'file_required';
	}

	/**
	 * Test if an uploaded file is an allowed file type, by extension.
	 *
	 *     $array->rule('file', 'Upload::type', array(':value', array('jpg', 'png', 'gif')));
	 *
	 * @param   array   $file       $_FILES item
	 * @param   array   $allowed    allowed file extensions
	 * @return  bool
	 */
	public static function type(array $file, array $allowed)
	{
		if ($file['error'] !== UPLOAD_ERR_OK)
			return TRUE;

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

		return in_array($ext, $allowed);
	}

	/**
	 * Validation rule to test if an uploaded file is allowed by file size.
	 * File sizes are defined as: SB, where S is the size (1, 8.5, 300, etc.)
	 * and B is the byte unit (K, MiB, GB, etc.). All valid byte units are
	 * defined in Num::$byte_units
	 *
	 *     $array->rule('file', 'Upload::size', array(':value', '1M'))
	 *     $array->rule('file', 'Upload::size', array(':value', '2.5KiB'))
	 *
	 * @param   array   $file   $_FILES item
	 * @param   string  $size   maximum file size allowed
	 * @return  bool
	 */
	public static function size(array $file, $size)
	{
		if ($file['error'] === UPLOAD_ERR_INI_SIZE)
		{
			// Upload is larger than PHP allowed size (upload_max_filesize)
			// return FALSE;
		}

		if ($file['error'] !== UPLOAD_ERR_OK)
		{
			// The upload failed, no size to check
			return TRUE;
		}

		// Convert the provided size to bytes for comparison
		$size = Num::bytes($size);

		// Test that the file is under or equal to the max size
		return ($file['size'] <= $size);
	}

	/**
	 * Validation rule to test if an upload is an image and, optionally, is the correct size.
	 *
	 *     // The "image" file must be an image
	 *     $array->rule('image', 'Upload::image')
	 *
	 *     // The "photo" file has a maximum size of 640x480 pixels
	 *     $array->rule('photo', 'Upload::image', array(':value', 640, 480));
	 *
	 *     // The "image" file must be exactly 100x100 pixels
	 *     $array->rule('image', 'Upload::image', array(':value', 100, 100, TRUE));
	 *
	 *
	 * @param   array   $file       $_FILES item
	 * @param   integer $max_width  maximum width of image
	 * @param   integer $max_height maximum height of image
	 * @param   boolean $exact      match width and height exactly?
	 * @return  boolean
	 */
	public static function image(array $file, $max_width = NULL, $max_height = NULL, $exact = FALSE)
	{
		if(Upload::not_empty($file) === true)
		{
			try
			{
				// Get the width and height from the uploaded image
				list($width, $height) = getimagesize($file['tmp_name']);
			}
			catch (\ErrorException $e)
			{
				// Ignore read errors
			}

			if (empty($width) OR empty($height))
			{
				// Cannot get image size, cannot validate
				return 'image_invalid';
			}

			if ( ! $max_width)
			{
				// No limit, use the image width
				$max_width = $width;
			}

			if ( ! $max_height)
			{
				// No limit, use the image height
				$max_height = $height;
			}

			if ($exact)
			{
				// Check if dimensions match exactly
				return ($width == $max_width AND $height == $max_height) ? true : 'image_size_exact';
			}
			else
			{
				// Check if size is within maximum dimensions
				return ($width <= $max_width AND $height <= $max_height) ? true : 'image_size';
			}
		}

		return true; //"file wasn't uploaded!";
	}

}

<?php

namespace WN\Core\Helper;

/**
 * Inflector helper class. Inflection is changing the form of a word based on
 * the context it is used in. For example, changing a word into a plural form.
 *
 * [!!] Inflection is only tested with English, and is will not work with other languages.
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Inflector {

	/**
	 * @var  array  cached inflections
	 */
	protected static $cache = array();

	/**
	 * @var  array  uncountable words
	 */
	protected static $uncountable = [
		'access',
		'advice',
		'aircraft',
		'art',
		'baggage',
		'bison',
		'dances',
		'deer',
		'equipment',
		'fish',
		'fuel',
		'furniture',
		'heat',
		'honey',
		'homework',
		'impatience',
		'information',
		'knowledge',
		'luggage',
		'media',
		'money',
		'moose',
		'music',
		'news',
		'patience',
		'progress',
		'pollution',
		'research',
		'rice',
		'salmon',
		'sand',
		'series',
		'sheep',
		'sms',
		'spam',
		'species',
		'staff',
		'swine',
		'toothpaste',
		'traffic',
		'understanding',
		'water',
		'weather',
		'work',
	];

	/**
	 * @var  array  irregular words
	 */
	protected static $irregular = [
		'appendix'    => 'appendices',
		'cactus'      => 'cacti',
		'calf'        => 'calves',
		'child'       => 'children',
		'crisis'      => 'crises',
		'criterion'   => 'criteria',
		'curriculum'  => 'curricula',
		'diagnosis'   => 'diagnoses',
		'elf'         => 'elves',
		'ellipsis'    => 'ellipses',
		'foot'        => 'feet',
		'goose'       => 'geese',
		'hero'        => 'heroes',
		'hoof'        => 'hooves',
		'hypothesis'  => 'hypotheses',
		'is'          => 'are',
		'knife'       => 'knives',
		'leaf'        => 'leaves',
		'life'        => 'lives',
		'loaf'        => 'loaves',
		'man'         => 'men',
		'mouse'       => 'mice',
		'nucleus'     => 'nuclei',
		'oasis'       => 'oases',
		'octopus'     => 'octopi',
		'ox'          => 'oxen',
		'paralysis'   => 'paralyses',
		'parenthesis' => 'parentheses',
		'person'      => 'people',
		'phenomenon'  => 'phenomena',
		'potato'      => 'potatoes',
		'quiz'        => 'quizzes',
		'radius'      => 'radii',
		'scarf'       => 'scarves',
		'stimulus'    => 'stimuli',
		'syllabus'    => 'syllabi',
		'synthesis'   => 'syntheses',
		'thief'       => 'thieves',
		'tooth'       => 'teeth',
		'was'         => 'were',
		'wharf'       => 'wharves',
		'wife'        => 'wives',
		'woman'       => 'women',
		'release'     => 'releases',
	];

	/**
	 * Checks if a word is defined as uncountable. An uncountable word has a
	 * single form. For instance, one "fish" and many "fish", not "fishes".
	 *
	 *     static::uncountable('fish'); // TRUE
	 *     static::uncountable('cat');  // FALSE
	 *
	 * If you find a word is being pluralized improperly, it has probably not
	 * been defined as uncountable in `config/inflector.php`. If this is the
	 * case, please report [an issue](http://dev.kohanaphp.com/projects/kohana3/issues).
	 *
	 * @param   string  $str    word to check
	 * @return  boolean
	 */
	public static function uncountable($str)
	{
		if (static::$uncountable === NULL)
		{
			// // Cache uncountables
			// static::$uncountable = Kohana::$config->load('inflector')->uncountable;

			// Make uncountables mirrored
			static::$uncountable = array_combine(static::$uncountable, static::$uncountable);
		}

		return isset(static::$uncountable[strtolower($str)]);
	}

	/**
	 * Makes a plural word singular.
	 *
	 *     echo static::singular('cats'); // "cat"
	 *     echo static::singular('fish'); // "fish", uncountable
	 *
	 * You can also provide the count to make inflection more intelligent.
	 * In this case, it will only return the singular value if the count is
	 * greater than one and not zero.
	 *
	 *     echo Inflector::singular('cats', 2); // "cats"
	 *
	 * [!!] Special inflections are defined in `config/inflector.php`.
	 *
	 * @param   string  $str    word to make singular
	 * @param   integer $count  count of thing
	 * @return  string
	 * @uses    Inflector::uncountable
	 */
	public static function singular($str, $count = NULL)
	{
		// $count should always be a float
		$count = ($count === NULL) ? 1.0 : (float) $count;

		// Do nothing when $count is not 1
		if ($count != 1)
			return $str;

		// Remove garbage
		$str = strtolower(trim($str));

		// Cache key name
		$key = 'singular_'.$str.$count;

		if (isset(static::$cache[$key]))
			return static::$cache[$key];

		if (static::uncountable($str))
			return static::$cache[$key] = $str;

		// if (empty(static::$irregular))
		// {
		// 	// Cache irregular words
		// 	static::$irregular = Kohana::$config->load('inflector')->irregular;
		// }

		if ($irregular = array_search($str, static::$irregular))
		{
			$str = $irregular;
		}
		elseif (preg_match('/us$/', $str))
		{
			// http://en.wikipedia.org/wiki/Plural_form_of_words_ending_in_-us
			// Already singular, do nothing
		}
		elseif (preg_match('/[sxz]es$/', $str) OR preg_match('/[^aeioudgkprt]hes$/', $str))
		{
			// Remove "es"
			$str = substr($str, 0, -2);
		}
		elseif (preg_match('/[^aeiou]ies$/', $str))
		{
			// Replace "ies" with "y"
			$str = substr($str, 0, -3).'y';
		}
		elseif (substr($str, -1) === 's' AND substr($str, -2) !== 'ss')
		{
			// Remove singular "s"
			$str = substr($str, 0, -1);
		}

		return static::$cache[$key] = $str;
	}

	/**
	 * Makes a singular word plural.
	 *
	 *     echo Inflector::plural('fish'); // "fish", uncountable
	 *     echo Inflector::plural('cat');  // "cats"
	 *
	 * You can also provide the count to make inflection more intelligent.
	 * In this case, it will only return the plural value if the count is
	 * not one.
	 *
	 *     echo Inflector::singular('cats', 3); // "cats"
	 *
	 * [!!] Special inflections are defined in `config/inflector.php`.
	 *
	 * @param   string  $str    word to pluralize
	 * @param   integer $count  count of thing
	 * @return  string
	 * @uses    Inflector::uncountable
	 */
	public static function plural($str, $count = NULL)
	{
		// $count should always be a float
		$count = ($count === NULL) ? 0.0 : (float) $count;

		// Do nothing with singular
		if ($count == 1)
			return $str;

		// Remove garbage
		$str = trim($str);

		// Cache key name
		$key = 'plural_'.$str.$count;

		// Check uppercase
		$is_uppercase = ctype_upper($str);

		if (isset(static::$cache[$key]))
			return static::$cache[$key];

		if (static::uncountable($str))
			return static::$cache[$key] = $str;

		// if (empty(static::$irregular))
		// {
		// 	// Cache irregular words
		// 	static::$irregular = Kohana::$config->load('inflector')->irregular;
		// }

		if (isset(static::$irregular[$str]))
		{
			$str = static::$irregular[$str];
		}
		elseif (in_array($str, static::$irregular))
		{
			// Do nothing
		}
		elseif (preg_match('/[sxz]$/', $str) OR preg_match('/[^aeioudgkprt]h$/', $str))
		{
			$str .= 'es';
		}
		elseif (preg_match('/[^aeiou]y$/', $str))
		{
			// Change "y" to "ies"
			$str = substr_replace($str, 'ies', -1);
		}
		else
		{
			$str .= 's';
		}

		// Convert to uppercase if necessary
		if ($is_uppercase)
		{
			$str = strtoupper($str);
		}

		// Set the cache and return
		return static::$cache[$key] = $str;
	}

	/**
	 * Makes a phrase camel case. Spaces and underscores will be removed.
	 *
	 *     $str = Inflector::camelize('mother cat');     // "motherCat"
	 *     $str = Inflector::camelize('kittens in bed'); // "kittensInBed"
	 *
	 * @param   string  $str    phrase to camelize
	 * @return  string
	 */
	public static function camelize($str)
	{
		$str = 'x'.strtolower(trim($str));
		$str = ucwords(preg_replace('/[\s_]+/', ' ', $str));

		return substr(str_replace(' ', '', $str), 1);
	}

	/**
	 * Converts a camel case phrase into a spaced phrase.
	 *
	 *     $str = Inflector::decamelize('houseCat');    // "house cat"
	 *     $str = Inflector::decamelize('kingAllyCat'); // "king ally cat"
	 *
	 * @param   string  $str    phrase to camelize
	 * @param   string  $sep    word separator
	 * @return  string
	 */
	public static function decamelize($str, $sep = ' ')
	{
		return strtolower(preg_replace('/([a-z])([A-Z])/', '$1'.$sep.'$2', trim($str)));
	}

	/**
	 * Makes a phrase underscored instead of spaced.
	 *
	 *     $str = Inflector::underscore('five cats'); // "five_cats";
	 *
	 * @param   string  $str    phrase to underscore
	 * @return  string
	 */
	public static function underscore($str)
	{
		return preg_replace('/\s+/', '_', trim($str));
	}

	/**
	 * Makes an underscored or dashed phrase human-readable.
	 *
	 *     $str = Inflector::humanize('kittens-are-cats'); // "kittens are cats"
	 *     $str = Inflector::humanize('dogs_as_well');     // "dogs as well"
	 *
	 * @param   string  $str    phrase to make human-readable
	 * @return  string
	 */
	public static function humanize($str)
	{
		return preg_replace('/[_-]+/', ' ', trim($str));
	}

}

<?php

namespace Anstech;

use Fuel\Core\Config;
use Fuel\Core\Lang;
use Fuel\Core\Str;
use Fuel\Core\Uri;

/**
 * Breadcrumb solution
 *
 * @version    0.2
 * @author     Daniel Polito - @dbpolito
 * @link       https://github.com/dbpolito/Fuel-Breadcrumb
 */
class Breadcrumb
{
    protected static $breadcrumb = [];

    protected static $auto_render = true;

    protected static $use_lang = false;

    protected static $lang_file = null;

    protected static $lang_prefix = null;

    protected static $home = [
        'name' => 'Home',
        'link' => '/',
    ];

    /**
     * Loads in the config and sets the variables
     *
     * @access  public
     *
     * @return  void
     */
    public function __construct()
    {
        // Load configuration
        if (! Config::load('breadcrumb', true)) {
            // Load default configuration
            Config::load('menu::breadcrumb', 'breadcrumb', true);
        }

        // Check if autorendering is enabled
        if (Config::get('breadcrumb.auto_render', static::$auto_render) === true) {
            static::initialise();
        }
    }

    /**
     * Initialise breadcrumb based on URI segments
     *
     * @access  protected
     *
     * @return  void
     */
    protected static function initialise()
    {
        $home = Config::get('breadcrumb.home', static::$home);
        $use_lang = Config::get('breadcrumb.use_lang', static::$use_lang);
        $ignored = Config::get('breadcrumb.ignore_segments', []);

        if (! in_array('home', $ignored)) {
            if ($use_lang === true) {
                static::set(static::translate($home['name']), $home['link']);
            } else {
                static::set($home['name'], $home['link']);
            }
        }

        $segments = Uri::segments();
        $link     = '';

        foreach ($segments as $segment) {
            if (preg_match('/^([0-9])+$/', $segment) > 0 or $segment === 'index') {
                continue;
            }

            $link .= '/' . $segment;

            if (! in_array($segment, $ignored)) {
                if ($use_lang === true) {
                    static::set(static::translate($segment), $link);
                } else {
                    static::set(Str::ucwords(str_replace('_', ' ', $segment)), $link);
                }
            }
        }
    }


    /**
     * Try to translate the desired string taking account of the config
     *
     * @param  string $string The string to be translate
     *
     * @return string
     */
    protected static function translate($string)
    {
        $lang_file = Config::get('breadcrumb.lang_file', static::$lang_file);
        $lang_prefix = Config::get('breadcrumb.lang_prefix', static::$lang_prefix);

        empty($lang_file) or Lang::load($lang_file, true);

        empty($lang_prefix) or $string = $lang_prefix . '.' . $string;
        empty($lang_file) or $string = $lang_file . '.' . $string;

        return Lang::get($string);
    }


    /**
     * Set an item on breadcrumb static property
     *
     * @param string    $title  Display Link
     * @param string    $link   Relative Link
     * @param integer   $index  Index to replace items
     *
     * @return void
     */
    public static function set($title, $link = '', $index = null, $overwrite = true)
    {
        // trim the title
        $title = trim($title);

        // if link is empty user the current
        $link = ($link === '') ? Uri::current() : $link;

        if (is_null($index)) {
            static::$breadcrumb[] = [
                'title' => $title,
                'link'  => $link,
            ];
        } else {
            if ($overwrite === true) {
                static::$breadcrumb[$index] = [
                    'title' => $title,
                    'link'  => $link,
                ];
            } else {
                static::append($title, $link, $index);
            }
        }
    }


    /**
     * Unset an item on breadcrumb static property
     *
     * @param integer $index
     *
     * @return void
     */
    public static function remove($index = null)
    {
        unset(static::$breadcrumb[$index]);
        static::sortArray(static::$breadcrumb);
    }


    /**
     * Count number of items on breadcrumb
     *
     * @return int
     */
    public static function count()
    {
        return count(static::$breadcrumb);
    }


    /**
     * Get the breadcrumb
     *
     * @return array
     */
    public static function breadcrumb()
    {
        return static::$breadcrumb;
    }


    public static function pop()
    {
        array_pop(static::$breadcrumb);
    }


    /**
     * Set an item on breadcrumb without overwrite the index
     *
     * @param string    $title  Display Link
     * @param string    $link   Relative Link
     * @param integer   $index  Index to replace items
     *
     * @return void
     */
    protected static function append($title, $link = '', $index = null)
    {
        $breadcrumb = [];

        if (is_null($index) or $index > count(static::$breadcrumb) - 1) {
            static::set($title, $link);
        }

        for ($i = 0, $total = count(static::$breadcrumb); $i < $total; $i++) {
            if ($i === $index) {
                $breadcrumb[] = [
                    'title' => $title,
                    'link'  => $link,
                ];
                $i--;
                $index = null;
            } else {
                $breadcrumb[] = static::$breadcrumb[$i];
            }
        }

        static::$breadcrumb = $breadcrumb;
    }


    /**
     * Sort the index of an array
     *
     * @return &$array Array to sort
     */
    protected static function sortArray(&$array)
    {
        if (is_array($array)) {
            $aux = [];
            foreach ($array as $value) {
                $aux[] = $value;
            }

            $array = $aux;
        }
    }
}

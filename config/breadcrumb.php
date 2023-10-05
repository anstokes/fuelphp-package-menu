<?php

/**
 * Breadcrumb solution
 *
 * @author     Daniel Polito - @dbpolito
 * @version    0.2
 * @link       https://github.com/dbpolito/Fuel-Breadcrumb
 */

return [

    /**
     * Auto Populate Breadcrumb based on routes
     */
    'auto_populate'   => true,
    'ignore_segments' => [],

    /**
     * If true the class will call ONLY ON AUTO POPULATING Lang::get() to each item
     * of breadcrumb and WILL NOT ucwords and replace underscores to spaces
     */
    'use_lang'        => false,
    'lang_file'       => null,
    'lang_prefix'     => null,

    /**
     * Home Link
     */
    'home'            => [
        'name' => 'Home',
        'link' => '/home',
    ],

    'display_always'  => false,

];

/* End of file breadcrumb.php */

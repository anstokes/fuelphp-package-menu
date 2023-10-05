<?php

namespace Anstech;

use Fuel\Core\Arr;
use Fuel\Core\Config;
use Fuel\Core\Module;
use Fuel\Core\Router;
use Parser\View;
use Parser\View_Mustache;

class Menu
{
    /*
    <ul class="navbar-nav tab-pane active" id="Main" role="tabpanel">
        <li class="menu-label mt-0 text-primary font-12 fw-semibold">M<span>ain</span><br><span class="font-10 text-secondary fw-normal">Unique Dashboard</span></li>
        <li class="nav-item">
            <a class="nav-link" href="#sidebarAnalytics" data-bs-toggle="collapse" role="button"
                aria-expanded="false" aria-controls="sidebarAnalytics">
                <i class="ti ti-stack menu-icon"></i>
                <span>Analytics</span>
            </a>
            <div class="collapse " id="sidebarAnalytics">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Dashboard</a>
                    </li><!--end nav-item-->
                    ...
                </ul><!--end nav-->
            </div><!--end sidebarAnalytics-->
        </li><!--end nav-item-->
    </ul>
    <ul class="navbar-nav tab-pane" id="Extra" role="tabpanel">
        ...
    </ul>
    */

    protected static $example_menu = [
        'tabs' => [
            [
                // 'active' => true,
                'id'    => 'main',
                'label' => 'Main',
                'menu'  => [
                    [
                        'label'       => 'Main',
                        'description' => 'Unique Dashboard',
                        // 'link' => '/main/dashboard',
                        'menu'        => [
                            'id'    => 'sidebarAnalytics',
                            'label' => 'Analytics',
                            // 'link' => '/main/dashboard/analytics',
                            'icon'  => ['class' => 'ti ti-stack menu-icon'],
                            'menu'  => [
                                [
                                    'label' => 'Dashboard',
                                    'link'  => 'index.html',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id'    => 'extra',
                'label' => 'Extra',
                'html'  => '
					<div class="update-msg text-center position-relative">
						<button type="button" class="btn-close position-absolute end-0 me-2" aria-label="Close"></button>
						<img src="/assets/img/speaker-light.png" alt="" class="" height="110">
						<h5 class="mt-0">ANStech</h5>
						<p class="mb-3">Proven experience of designing, developing and deploying robust / high-availability SaaS solutions.</p>
						<a href="https://anstech.co.uk" class="btn btn-outline-warning btn-sm">Visit our website</a>
					</div>',
            ],
        ],
    ];


    protected static function menuConfiguration($menu_name = null)
    {
        // Load base menu configuration
        $menu_configuration = Config::load('menu', true);

        // Scan modules to add menu options
        static::moduleMenuConfiguration($menu_configuration);

        // Inject menus, where required
        if (isset($menu_configuration['inject']) && ($inject_menus = $menu_configuration['inject'])) {
            unset($menu_configuration['inject']);
            static::injectMenus($menu_configuration, $inject_menus);
        }

        // var_dump($menuConfiguration); exit;

        // Return specific menu, if requested
        if ($menu_name) {
            // Check for requested menu
            if (isset($menu_configuration[$menu_name]) && ($menu = $menu_configuration[$menu_name])) {
                return $menu;
            }

            // Requested menu not found
            return false;
        }

        return $menu_configuration;
    }


    protected static function moduleMenuConfiguration(&$menu_configuration)
    {
        // Loop through module paths
        foreach (Config::get('module_paths') as $modules_path) {
            // Loop through modules
            $directory = new \DirectoryIterator($modules_path);
            foreach ($directory as $file_info) {
                if ($file_info->isDot()) {
                    continue;
                }

                $module = $file_info->getBasename();
                $module_path = $file_info->getPathname();

                // Check if module has menu configuration
                if (file_exists($module_path . DS . 'config' . DS . 'menu.php')) {
                    static::loadModuleMenu($module, $menu_configuration);
                }
            }
        }
    }


    protected static function loadModuleMenu($module, &$menu_configuration)
    {
        // Read menus from module
        Module::load($module);

        // Load module routing, in case there are routes in the menu
        Router::add(Config::load($module . '::routes', true));

        // Load the menu configuration
        if ($module_menus = Config::load($module . '::menu', true)) {
            if (isset($module_menus['inject'])) {
                if (! isset($menu_configuration['inject'])) {
                    $menu_configuration['inject'] = [];
                }

                // Merge the module menu with the existing menu
                $menu_configuration['inject'] = Arr::merge_assoc($menu_configuration['inject'], $module_menus['inject']);
            }
        }
    }


    protected static function injectMenus(&$menu_configuration, $inject_menus)
    {
        foreach ($menu_configuration as $index => $menu) {
            if (isset($menu['tabs']) && ($tabs = $menu['tabs'])) {
                $menu_configuration[$index]['tabs'] = static::injectMenus($tabs, $inject_menus);
            } else {
                if (isset($menu['menu']) && ($sub_menus = $menu['menu'])) {
                    $menu_configuration[$index]['menu'] = static::injectMenus($sub_menus, $inject_menus);
                }
            }

            // Check if this is where the menu needs injecting
            if (isset($menu['id']) && ($additional_menus = static::additionalMenus($menu['id'], $inject_menus))) {
                $menu_configuration[$index]['menu'] = Arr::merge_assoc($menu['menu'], $additional_menus);
            }
        }

        return $menu_configuration;
    }


    protected static function additionalMenus($id, $inject_menus)
    {
        $additional_menus = [];

        foreach ($inject_menus as $inject_menu) {
            if (isset($inject_menu['parent']) && ($inject_menu['parent'] === $id)) {
                // unset($injectMenu['parent']);
                $additional_menus[] = $inject_menu['menu'];
            }
        }

        return $additional_menus;
    }


    /**
     * Render/display a menu
     *
     * @param string $menu_name     The menu to display
     * @param string $template      The templated to use for rendering
     *
     * @return object|string
     */
    public static function menu($menu_name = 'nav', $template = 'menu')
    {
        // Load menu configuration
        if ($menu = static::menuConfiguration($menu_name)) {
            // Example menu, if menu not provided
            // $menu = static::$exampleMenu;

            // Parse the menu
            $parsed_menu = static::parseMenu($menu);
            // var_dump($parsedMenu); exit;

            return View::forge($template . '.mustache', $parsed_menu, false);
        }

        // TODO - improve
        return 'Menu not found';
    }


    /**
     * Parses menu
     *
     * @param array Array of menu options to be parsed
     *
     * @return array
     */
    protected static function parseMenu(&$menu)
    {
        if (isset($menu['tabs']) && ($tabs = $menu['tabs'])) {
            foreach ($tabs as $tab_index => $tab_menu) {
                $menu['tabs'][$tab_index] = static::parseMenu($tab_menu);
            }
        } else {
            // Add 'hasSubmenu' label
            if (isset($menu['menu']) && ($sub_menus = $menu['menu'])) {
                $menu['hasSubmenu'] = true;
                foreach ($sub_menus as $index => $sub_menu) {
                    $menu['menu'][$index] = static::parseMenu($sub_menu);
                }
            }

            if (isset($menu['label'])) {
                $menu['labelCharacter'] = substr($menu['label'], 0, 1);
                $menu['labelRemainder'] = substr($menu['label'], 1);
            }
        }

        return $menu;
    }
}

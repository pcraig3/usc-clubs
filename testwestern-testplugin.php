<?php
/**
 * A Test Plugin that will eventually connect to an API.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Testwestern_Testplugin
 * @author    Paul Craig <pcraig3@uwo.ca>
 * @license   GPL-2.0+
 * @link      http://testwestern.com
 * @copyright 2014 University Students' Council
 *
 * @wordpress-plugin
 * Plugin Name:       Testwestern_Testplugin
 * Plugin URI:        http://testwestern.com
 * Description:       Beams in some information from GitHub.  Possibly witchcraft.
 * Version:           1.3.0
 * Author:            Paul Craig
 * Author URI:        https://profiles.wordpress.org/pcraig3/
 * Text Domain:       testwestern-testplugin
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/pcraig3/testwestern-testplugin
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-testwestern-testplugin.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 * - rename Plugin_Name in `class-testwestern-testplugin.php`
 */
register_activation_hook( __FILE__, array( 'Testwestern_Testplugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Testwestern_Testplugin', 'deactivate' ) );

/*
 * - rename Plugin_Name in `class-testwestern-testplugin.php`
 */
add_action( 'plugins_loaded', array( 'Testwestern_Testplugin', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace Plugin_Name_Admin with the name of the class defined in
 *   `testwestern-testplugin-admin.php`
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 *
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/testwestern-testplugin-admin.php' );
	add_action( 'plugins_loaded', array( 'Plugin_Name_Admin', 'get_instance' ) );

}
 */

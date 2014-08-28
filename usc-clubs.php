<?php
/**
 * The USC Clubs plugin generates a list of clubs originally sourced from WesternLink,
 * as well as individual pages for each of the clubs.
 *
 *
 * @package   USC_Clubs
 * @author    Paul Craig <pcraig3@uwo.ca>
 * @license   GPL-2.0+
 * @link      http://westernusc.org
 * @copyright 2014 University Students' Council
 *
 * @wordpress-plugin
 * Plugin Name:       USC Clubs
 * Plugin URI:        http://testwestern.com/clubs-from-github/
 * Description:       Beams in some information from GitHub.  Possibly witchcraft.
 * Version:           1.1.1
 * Author:            Paul Craig
 * Author URI:        https://profiles.wordpress.org/pcraig3/
 * Text Domain:       usc-clubs
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/pcraig3/usc-clubs
 * GitHub Branch:     master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/WP_AJAX.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/class-usc-clubs.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 * - rename Plugin_Name in `class-usc-clubs.php`
 */
register_activation_hook( __FILE__, array( 'USC_Clubs', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'USC_Clubs', 'deactivate' ) );

/*
 * - rename Plugin_Name in `class-usc-clubs.php`
 */
add_action( 'plugins_loaded', array( 'USC_Clubs', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace Plugin_Name_Admin with the name of the class defined in
 *   `class-usc-clubs-admin.php`
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

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-usc-clubs-admin.php' );
	add_action( 'plugins_loaded', array( 'Plugin_Name_Admin', 'get_instance' ) );

}
 */

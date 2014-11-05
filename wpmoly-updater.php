<?php
/**
 * WPMovieLibrary-Updater
 *
 * WPMovieLibrary plugin to batch-update all your movies metadata.
 *
 * @package   WPMovieLibrary-Updater
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 CaerCam.org
 *
 * @wordpress-plugin
 * Plugin Name: WPMovieLibrary-Updater
 * Plugin URI:  https://github.com/wpmovielibrary/wpmovielibrary-updater
 * Description: WPMovieLibrary plugin to batch-update all your movies metadata.
 * Version:     1.0
 * Author:      Charlie MERLAND
 * Author URI:  http://www.caercam.org/
 * License:     GPL-3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * GitHub Plugin URI: https://github.com/wpmovielibrary/wpmovielibrary-updater
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPMOLY_UPDATER_NAME',                   'WPMovieLibrary-Updater' );
define( 'WPMOLY_UPDATER_VERSION',                '1.0' );
define( 'WPMOLY_UPDATER_SLUG',                   'wpmoly-updater' );
define( 'WPMOLY_UPDATER_URL',                    plugins_url( basename( __DIR__ ) ) );
define( 'WPMOLY_UPDATER_PATH',                   plugin_dir_path( __FILE__ ) );
define( 'WPMOLY_UPDATER_REQUIRED_PHP_VERSION',   '5.4' );
define( 'WPMOLY_UPDATER_REQUIRED_WP_VERSION',    '4.0' );


/**
 * Determine whether WPMOLY is active or not.
 *
 * @since    1.0
 *
 * @return   boolean
 */
if ( ! function_exists( 'is_wpmoly_active' ) ) :
	function is_wpmoly_active() {

		return defined( 'WPMOLY_VERSION' );
	}
endif;

/**
 * Checks if the system requirements are met
 * 
 * @since    1.0
 * 
 * @return   bool    True if system requirements are met, false if not
 */
function wpmoly_updater_requirements_met() {

	global $wp_version;

	if ( version_compare( PHP_VERSION, WPMOLY_UPDATER_REQUIRED_PHP_VERSION, '<' ) )
		return false;

	if ( version_compare( $wp_version, WPMOLY_UPDATER_REQUIRED_WP_VERSION, '<' ) )
		return false;

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0
 */
function wpmoly_updater_requirements_error() {
	global $wp_version;

	require_once WPMOLY_UPDATER_PATH . '/views/requirements-error.php';
}

/**
 * Prints an error that the system requirements weren't met.
 * 
 * @since    1.0
 */
function wpmoly_updater_l10n() {

	$domain = 'wpmovielibrary-updater';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, WPMOLY_UPDATER_PATH . 'languages/' . $domain . '-' . $locale . '.mo' );
	load_plugin_textdomain( $domain, FALSE, basename( __DIR__ ) . '/languages/' );
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the
 * plugin requirements are met. Otherwise older PHP installations could crash
 * when trying to parse it.
 */
if ( wpmoly_updater_requirements_met() ) {

	require_once( WPMOLY_UPDATER_PATH . 'includes/class-module.php' );
	require_once( WPMOLY_UPDATER_PATH . 'class-wpmoly-updater.php' );

	if ( class_exists( 'WPMovieLibrary_Updater' ) ) {
		$GLOBALS['wpmoly_updater'] = new WPMovieLibrary_Updater();
		register_activation_hook(   __FILE__, array( $GLOBALS['wpmoly_updater'], 'activate' ) );
		register_deactivation_hook( __FILE__, array( $GLOBALS['wpmoly_updater'], 'deactivate' ) );
	}
}
else {
	wpmoly_updater_requirements_error();
}

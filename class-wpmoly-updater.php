<?php
/**
 * WPMovieLibrary-Updater
 *
 * @package   WPMovieLibrary-Updater
 * @author    Charlie MERLAND <charlie@caercam.org>
 * @license   GPL-3.0
 * @link      http://www.caercam.org/
 * @copyright 2014 Charlie MERLAND
 */

if ( ! class_exists( 'WPMovieLibrary_Updater' ) ) :

	/**
	* Plugin class
	*
	* @package WPMovieLibrary-Updater
	* @author  Charlie MERLAND <charlie@caercam.org>
	*/
	class WPMovieLibrary_Updater extends WPMOLY_Updater_Module {

		/**
		 * Initialize the plugin by setting localization and loading public scripts
		 * and styles.
		 *
		 * @since     1.0
		 */
		public function __construct() {

			$this->init();
			$this->register_hook_callbacks();
		}

		/**
		 * Initializes variables
		 *
		 * @since    1.0
		 */
		public function init() {}

		/**
		 * Register callbacks for actions and filters
		 * 
		 * @since    1.0
		 */
		public function register_hook_callbacks() {

			add_action( 'plugins_loaded', 'wpmoly_updater_l10n' );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			//
			add_filter( 'wpmoly_filter_admin_menu', array( $this, 'updater_page_link' ) );
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Plugin  Activate/Deactivate
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Make sure WPMovieLibrary is active and compatible.
		 *
		 * @since    1.0
		 * 
		 * @return   boolean    Requirements met or not?
		 */
		private function wpmoly_updater_requirements_error() {

			$wpml_active  = is_wpmoly_active();
			$wpml_version = ( $wpml_active && version_compare( WPML_VERSION, WPMLTR_REQUIRED_WPML_VERSION, '>=' ) );

			if ( ! $wpml_active || ! $wpml_version )
				return false;

			return true;
		}

		/**
		 * Fired when the plugin is activated.
		 *
		 * @since    1.0
		 *
		 * @param    boolean    $network_wide    True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public function activate( $network_wide ) {

			global $wpdb;

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				if ( $network_wide ) {
					$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

					foreach ( $blogs as $blog ) {
						switch_to_blog( $blog );
						$this->single_activate( $network_wide );
					}

					restore_current_blog();
				} else {
					$this->single_activate( $network_wide );
				}
			} else {
				$this->single_activate( $network_wide );
			}

		}

		/**
		 * Fired when the plugin is deactivated.
		 * 
		 * When deactivatin/uninstalling WPML, adopt different behaviors depending
		 * on user options. Movies and Taxonomies can be kept as they are,
		 * converted to WordPress standars or removed. Default is conserve on
		 * deactivation, convert on uninstall.
		 *
		 * @since    1.0
		 */
		public function deactivate() {
		}

		/**
		 * Runs activation code on a new WPMS site when it's created
		 *
		 * @since    1.0
		 *
		 * @param    int    $blog_id
		 */
		public function activate_new_site( $blog_id ) {
			switch_to_blog( $blog_id );
			$this->single_activate( true );
			restore_current_blog();
		}

		/**
		 * Prepares a single blog to use the plugin
		 *
		 * @since    1.0
		 *
		 * @param    bool    $network_wide
		 */
		protected function single_activate( $network_wide ) {

			self::require_wpmoly_first();
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                     Scripts/Styles and Utils
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		/**
		 * Register and enqueue public-facing style sheet.
		 *
		 * @since    1.0
		 */
		public function admin_enqueue_scripts() {

			wp_enqueue_style( WPMOLY_SLUG . '-legacy', WPMOLY_URL . '/assets/css/admin/wpmoly-legacy.css', array(), WPMOLY_VERSION );

			wp_enqueue_script( WPMOLY_SLUG . '-meta-update', WPMOLY_UPDATER_URL . '/assets/js/admin/wpmoly-meta-updater.js', array( WPMOLY_SLUG . '-admin', 'jquery' ), WPMOLY_UPDATER_VERSION, true );
		}

		/**
		 * Make sure the plugin is load after WPMovieLibrary and not
		 * before, which would result in errors and missing files.
		 *
		 * @since    1.0
		 */
		public static function require_wpmoly_first() {

			$this_plugin_path = plugin_dir_path( __FILE__ );
			$this_plugin      = basename( $this_plugin_path ) . '/wpmoly-updater.php';
			$active_plugins   = get_option( 'active_plugins' );
			$this_plugin_key  = array_search( $this_plugin, $active_plugins );
			$wpml_plugin_key  = array_search( 'wpmovielibrary/wpmovielibrary.php', $active_plugins );

			if ( $this_plugin_key < $wpml_plugin_key ) {

				unset( $active_plugins[ $this_plugin_key ] );
				$active_plugins = array_merge(
					array_slice( $active_plugins, 0, $wpml_plugin_key ),
					array( $this_plugin ),
					array_slice( $active_plugins, $wpml_plugin_key )
				);

				update_option( 'active_plugins', $active_plugins );
			}
		}

		/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 *                            Plugin methods
		 * 
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

		public function updater_page_link( $admin_menu ) {

			if ( ! isset( $admin_menu['subpages'] ) )
				return $admin_menu;

			$pos  = array_search( 'importer', array_keys( $admin_menu['subpages'] ) );
			$menu = $admin_menu['subpages'];
			$menu = array_merge(
				array_slice( $menu, 0, $pos ),
				array( 'update_meta' => array(
					'page_title'  => __( 'Batch-update Metadata', 'wpmovielibrary' ),
					'menu_title'  => __( 'Update Metadata', 'wpmovielibrary' ),
					'capability'  => 'manage_options',
					'menu_slug'   => 'wpmovielibrary-update-meta',
					'function'    => array( $this, 'updater_page' ),
					'condition'   => null,
					'hide'        => false,
					'actions'     => array(),
					'scripts'     => array(),
					'styles'      => array()
				) ),
				array_slice( $menu, $pos )
			);

			$admin_menu['subpages'] = $menu;

			return $admin_menu;
		}

		public function updater_page() {

			$args = array(
				'post_type'      => 'movie',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => '_wpmoly_movie_tmdb_id',
				'meta_value'     => '',
				'meta_compate'   => '!='
			);
			$movies = new WP_Query( $args );

			echo self::render_admin_template( 'settings/updater.php', array( 'movies' => $movies->posts ), $require = 'always' );
		}

	}
endif;

<?php
/**
 * Plugin Name: WP Custom Export CSV
 * Description: Custom export data for WordPress
 * Author: Lafif Astahdziq
 * Author URI: https://lafif.me
 * Author Email: hello@lafif.me
 * Version: 1.0.0
 * Text Domain: wcec
 * Domain Path: /languages/ 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_Custom_Export_CSV' ) ) :

/**
 * Main WP_Custom_Export_CSV Class
 *
 * @class WP_Custom_Export_CSV
 * @version	1.0.0
 */
final class WP_Custom_Export_CSV {

	/**
	 * Text domain
	 * @var string
	 */
	const TEXT_DOMAIN = 'wcec';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Version of scripts.
	 *
	 * @var string A date in the format: YYYYMMDD
	 */
	const SCRIPT_VERSION = '00000000';

	/**
	 * Version of database schema.
	 *
	 * @var string A date in the format: YYYYMMDD
	 */
	const DB_VERSION = '00000000';

	/**
	 * Plugin capability
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * The absolute path to this plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	private $directory_path;

	/**
	 * The URL of this plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	private $directory_url;

	/**
	 * Store of registered objects.
	 */
	private $registry;

	/**
     * A placeholder to hold the file iterator so that directory traversal is only
     * performed once.
     */
	private $file_iterator;

	/**
	 * Main WP_Custom_Export_CSV Instance
	 *
	 * Ensures only one instance of WP_Custom_Export_CSV is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return WP_Custom_Export_CSV - Main instance
	 */
	public static function instance() {
		static $instance = false;

        if ( !$instance ) {
            $instance = new self();
        }

        return $instance;
	}

	/**
	 * WP_Custom_Export_CSV Constructor.
	 */
	public function __construct() {

		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		$this->define_constants();

		$this->includes();

		$this->init_hooks();
	}

	/**
	 * Define required constants that can be override on wp config
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'WCEC_LOG_DIR', $upload_dir['basedir'] . '/wcec-logs/' );
		$this->define( 'WCEC_LOG_DEPRECATED', true );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {

		/**
		 * DEPENDENCIES
		 */
		if(file_exists( __DIR__ . '/vendor/autoload.php') ){
			require __DIR__ . '/vendor/autoload.php';
		}

		spl_autoload_register( array( $this, 'autoloader' ) );

		include_once( $this->get_path( 'includes' ) . 'functions.php' );
		include_once( $this->get_path( 'includes' ) . 'class-wcec-registry.php' );
	}

	/**
	 * Hook into actions and filters
	 * @since  1.0.0
	 */
	private function init_hooks() {

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'start' ), 1 );
	}

	/**
	 * Run the startup sequence.
	 *
	 * This is only ever executed once.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function start(){

		/* If we've already started (i.e. run this function once before), do not pass go. */
		if ( did_action( 'wcec_start' ) || current_filter() == 'wcec_start') {
			return;
		}

		$this->registry();

		/**
		 * We do this on priority 20 so that any functionality that is loaded on init (such
		 * as addons) has a chance to run before the event.
		 */
		add_action( 'init', array( $this, 'do_wcec_actions' ), 20 );

		do_action( 'wcec_start', $this );
	}

	/**
	 * Setup main registry
	 * 
	 * @since  1.0.0
	 */
	public function registry(){

		if ( ! isset( $this->registry ) ) {
			$this->registry = new WCEC_Registry();

			$this->registry->register_object( WCEC_Scripts::instance() );
			$this->registry->register_object( WCEC_Exports::instance() );

			if ( $this->is_request( 'admin' ) ) {
				$this->registry->register_object( WCEC_Admin::instance() );
			}

			if ( $this->is_request( 'ajax' ) ) {
				$this->registry->register_object( WCEC_Ajax::instance() );
			}

			if ( $this->is_request( 'frontend' ) ) {

			}
		}

		return $this->registry;
	}

	/**
	 * If a wcec_action event is triggered, delegate the event using do_action.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function do_wcec_actions(){
		if ( isset( $_REQUEST['wcec_action'] ) ) {

			$action = $_REQUEST['wcec_action'];

			/**
			 * Handle WCEC action.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wcec_' . $action );
		}
	}

	/**
	 * Shortcut to get registered object
	 * @param  [type] $class_key [description]
	 * @return [type]            [description]
	 */
	public function get( $class_key ){
		return $this->registry()->get( $class_key );
	}

	/**
	 * Shortcut to register object
	 * @param  [type] $class_key [description]
	 * @return [type]            [description]
	 */
	public function register( $class_key ){
		return $this->registry()->register_object( $class_key );
	}

	/**
	 * Dynamically loads the class attempting to be instantiated elsewhere in the
	 * plugin by looking at the $class_name parameter being passed as an argument.
	 *
	 * @param  string $class_name The fully-qualified name of the class to load.
	 * @return boolean
	 */
	public function autoloader( $class_name ) {
		/* If the specified $class_name already exists, bail. */
		if ( class_exists( $class_name ) ) {
			return false;
		}

		/* If the specified $class_name does not include our namespace, duck out. */
		if ( false === strpos( $class_name, 'WCEC_' ) ) {
			return false;
		}

		$directory = new RecursiveDirectoryIterator( $this->get_path( 'includes' ) , RecursiveDirectoryIterator::SKIP_DOTS);
        
        if ( ! isset( $this->file_iterator ) ) {
            $this->file_iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
        }

        $filename = 'class-' . str_replace('_', '-', strtolower( $class_name ) )  . '.php';
        foreach ( $this->file_iterator as $file ) {
            if ( strtolower( $file->getFilename() ) === $filename ) {
                if ( $file->isReadable() ) {
                    include_once $file->getPathname();
                }
                return true;
                break;
            }
        }

		return false;
	}

	/**
	 * All install stuff
	 * @return [type] [description]
	 */
	public function activate( $network_wide = false ){

	}

	/**
	 * All uninstall stuff
	 * @return [type] [description]
	 */
	public function deactivate(){

	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 * string $type ajax, frontend or admin
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Returns various plugin paths or urls
	 * @param  string  $type          [description]
	 * @param  boolean $use_url 	  [description]
	 * @return [type]                 [description]
	 */
	public function get_path( $type = '', $use_url = false ) {

		$base = $use_url ? $this->directory_url : $this->directory_path;

		switch ( $type ) {
			case 'includes':
				$path = $base . 'includes/';
				break;

			case 'abstracts':
				$path = $base . 'includes/abstracts/';
				break;

			case 'admin':
				$path = $base . 'includes/admin/';
				break;

			case 'admin_view':
				$path = $base . 'includes/admin/views/';
				break;

			case 'public':
				$path = $base . 'includes/public/';
				break;

			case 'processors':
				$path = $base . 'includes/processors/';
				break;

			case 'dist':
				$path = $base . 'dist/';
				break;

			case 'templates':
				$path = $base . 'templates/';
				break;

			default:
				$path = $base;
				break;

		}//end switch

		return $path;
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
}

endif;

// boot up
WP_Custom_Export_CSV::instance();
<?php 
/**
 * WCEC_Admin Class.
 *
 * @class       WCEC_Admin
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCEC_Admin class.
 */
class WCEC_Admin {

	/**
     * Singleton method
     *
     * @return self
     */
	public static function instance(){
		static $instance = false;

		if( ! $instance ){
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Constructor
	 */
	public function __construct(){
		add_action( 'wcec_start', array( $this, 'includes' ) );
	}

	public function includes(){
		require_once( wcec()->get_path( 'admin' ) . 'functions.php' );
		wcec()->register( WCEC_Admin_Pages::instance() );
	}
}
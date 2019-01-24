<?php 
/**
 * WCEC_Ajax Class.
 *
 * @class       WCEC_Ajax
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WCEC_Ajax' ) ) :

/**
 * WCEC_Ajax class.
 */
class WCEC_Ajax {

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
		add_action( 'wp_ajax_wcec_export', array( $this, 'export_ajax_callback' ) );
	}

	public function export_ajax_callback(){

		check_ajax_referer( 'wcec-export', 'nonce' );

		$export_id = (isset($_POST['export_id'])) ? esc_attr( $_POST['export_id'] ) : false;
		$step = (isset($_POST['step'])) ? esc_attr( $_POST['step'] ) : 1;

		$response = call_user_func( array( wcec()->get( 'exports' ), 'do_process' ) , $export_id, $step);

		wp_send_json( $response );
	}
}

endif;
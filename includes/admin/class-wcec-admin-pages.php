<?php 
/**
 * WCEC_Admin_Pages Class.
 *
 * @class       WCEC_Admin_Pages
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCEC_Admin_Pages class.
 */
class WCEC_Admin_Pages {

	private $screens = array();

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
		add_action( 'admin_menu', array( $this, 'add_menu' ), 5 );
	}

	public function add_menu(){

		if( has_filter( 'wcec_custom_menu' ) ){

			$this->screens['wcec'] = apply_filters( 'wcec_custom_menu', null, $this );

		} else {

			$this->screens['wcec'] = add_management_page(
				__( 'Custom Export', 'wcec' ),
				__( 'Custom Export', 'wcec' ),
				WP_Custom_Export_CSV::CAPABILITY,
				'wcec',
				array( $this, 'export_page_callback' )
			);
		}

		
	}

	public function get_screen_id( $id = false ){
		
		if( $id ){
			return isset( $this->screens[ $id ] ) ? $this->screens[ $id ] : false;
		}

		return $this->screens;
	}

	public function export_page_callback(){
		wcec_admin_view( 'export/export', array(
			'exports' => wcec()->get( 'exports' )->get_registered_processors(),
		));
	}
}
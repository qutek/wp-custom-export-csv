<?php 
/**
 * WCEC_Exports Class.
 *
 * @class       WCEC_Exports
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCEC_Exports class.
 */
class WCEC_Exports {

	protected $registered_processors;

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

		add_action( 'wcec_start', array( $this, 'on_start' ), 100 );
		add_action( 'admin_init', array( $this, 'maybe_download_export' ) );
	}

	public function on_start(){
		$this->register_export_processors();
	}

	private function register_export_processors(){

		$processors = array(
			'sample-user' => include_once( wcec()->get_path( 'processors' ) . 'class-wcec-user-processor.php' )
		);

		$this->registered_processors = apply_filters( 'wcec_registered_processors', $processors );
	}

	public function get_registered_processors(){
		return $this->registered_processors;
	}

	public function do_process( $export_id, $step ){

		$response = array(
			'success' => false,
			'message' => __('Failed to process export.', 'wcec')
		);

		try {
			if( !isset($this->registered_processors[ $export_id ]) ){
				throw new Exception( __('Processor not registered', 'wcec') );
			}

			$processor = $this->registered_processors[ $export_id ];

			if( !$processor->is_writable() ){
				throw new Exception( __('File not writable.', 'wcec') );
			}

			$response = $processor->run( $step );

		} catch (Exception $e) {
			$response[ 'message' ] = $e->getMessage();
		}

		return $response;
	}

	public function maybe_download_export(){

		if( !isset($_GET[ 'wcec_action' ]) || $_GET[ 'wcec_action' ] !== 'download-export' )
			return;

		if( !isset($_GET[ 'export_id' ]) || !isset($_GET[ 'nonce' ]) )
			return;

		if( !wp_verify_nonce( $_GET[ 'nonce' ], 'wcec-download-export' ) ){
			wp_die( __('Can not verify nonce', 'wcec') );
		}

		$export_id = $_GET[ 'export_id' ];

		if( !isset($this->registered_processors[ $export_id ]) ){
			wp_die( __('Processor not registered', 'wcec') );
		}

		$this->registered_processors[ $export_id ]->export();

		exit();
	}
}
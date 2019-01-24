<?php 
/**
 * WCEC_Scripts Class.
 *
 * @class       WCEC_Scripts
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCEC_Scripts class.
 */
class WCEC_Scripts {

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
		add_action( 'admin_head', array( $this, 'print_script_data' ), 1 );

		add_action( 'init', array( $this, 'register_scripts' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10, 1 );
	}

	public function print_script_data(){

		$script_data = apply_filters( 'wcec_script_data', array(
			'ajax_url' => wcec()->ajax_url(),
			'export_nonce' => wp_create_nonce( 'wcec-export' )
		) );

		?>
		<script type="text/javascript">
			window.WCEC_Admin_Data = <?php echo wp_json_encode( $script_data ); ?>;
		</script>
		<?php
	}

	public function register_scripts(){
		wp_register_style( 'wcec-admin', wcec()->get_path('dist', true) . 'admin/css/admin.css', array(), WP_Custom_Export_CSV::VERSION );
		wp_register_script( 'wcec-admin', wcec()->get_path('dist', true) . 'admin/js/admin.js', array('jquery'), WP_Custom_Export_CSV::VERSION, true );

	}

	public function enqueue_admin_scripts($hook_suffix){

		if( wcec()->get( 'admin_pages' )->get_screen_id('wcec') == $hook_suffix ){
			wp_enqueue_style( 'wcec-admin' );
			wp_enqueue_script( 'wcec-admin' );
		}
	}
}
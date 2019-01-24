<?php
/**
 * WCEC_User_Processor Class.
 *
 * @class       WCEC_User_Processor
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WCEC_Processor Posts class.
 */
class WCEC_User_Processor extends WCEC_Processor {
	/**
	 * The individual batch's parameter for specifying the amount of results to return.
	 * should match the key on default_args
	 *
	 * in this case we are querying with WP_User_Query so the pagination args use `number`
	 *
	 * @var string
	 */
	public $per_batch_param = 'number';

	/**
	 * Default args for the query.
	 *
	 * @var array
	 */
	public $default_args = array(
		'number' => 10,
		'offset' => 0,
	);

	/**
	 * Constructor
	 */
	public function __construct(){

		$this->export_id = 'sample-user';
		$this->name = __('Sample User', 'mba');
		$this->filename   = 'wcec-' . $this->export_id . '-' . date( 'Y-m-d' ) . '.csv';

		parent::__construct();
	}

	/**
	 * Set the CSV columns
	 * @return array $cols All the columns
	 */
	public function get_csv_cols() {
		$cols = array(
			'id'   => __( 'ID',   'wcec' ),
			'display_name' => __( 'Display Name', 'wcec' )
		);
		return $cols;
	}

	public function get_csv_data( $user ){

		$this->add_message( sprintf( __( 'Write data for %s.', 'wcec' ), $user->display_name ), 'notice' );

		return array(
			'id' => $user->ID,
			'display_name' => $user->display_name,
		);
	}

	/**
	 * Get results function for the registered batch process.
	 *
	 * @return array \WP_User_query->get_results() result.
	 */
	public function batch_get_results() {
		$query = new WP_User_Query( $this->args );
		$total_users = $query->get_total();
		$this->set_total_num_results( $total_users );
		return $query->get_results();
	}
}

return new WCEC_User_Processor();

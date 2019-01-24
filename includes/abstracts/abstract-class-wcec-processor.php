<?php
/**
 * WCEC_Processor Class.
 *
 * @class       WCEC_Processor
 * @version		1.0.0
 * @author lafif <hello@lafif.me>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract bulk export class.
 */
abstract class WCEC_Processor {

	
	/**
	 * Name of the export.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The export id.
	 *
	 * @var string
	 */
	public $export_id;

	/**
	 * The name of the file the data is stored in
	 *
	 * @since 1.0.0
	 */
	protected $filename = 'wcec-export.csv';

	/**
	 * The file the data is stored in
	 *
	 * @since 1.0.0
	 */
	protected $file;

	/**
	 * Is the export file writable
	 *
	 * @since 1.0.0
	 */
	protected $is_writable = true;

	/**
	 * The individual batch's parameter for specifying the amount of results to return.
	 *
	 * Can / should be overwritten within the class that extends this abstract class.
	 *
	 * @var string
	 */
	public $per_batch_param = 'posts_per_page';

	/**
	 * Args for the batch query.
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * Default args for the query.
	 *
	 * Should be implemented on the classes that extend this class.
	 *
	 * @var array
	 */
	public $default_args = array(
		'number' => 10,
		'offset' => 0,
	);

	/**
	 * Current step this batch is on.
	 *
	 * @var int
	 */
	public $current_step = 0;

	/**
	 * Total number of results.
	 *
	 * @var int
	 */
	public $total_num_results;

	/**
	 * Holds difference between total from client and total from query, if one exists.
	 *
	 * @var int
	 */
	public $difference_in_result_totals = 0;

	/**
	 * Stores the list of errors.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $errors = array();

	/**
	 * Stores the list of notices.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $notices = array();

	/**
	 * Stores the list of success.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $success = array();

	/**
	 * Constructor
	 */
	public function __construct(){

		$upload_dir       = wp_upload_dir();
		$this->file       = trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}
	}

	/**
	 * Set the CSV columns
	 * @return array $cols All the columns
	 */
	abstract public function get_csv_cols();

	/**
	 * Set the CSV data
	 * @return array $cols All the columns
	 */
	abstract public function get_csv_data( $raw_data );

	/**
	 * Get results function for the registered batch export.
	 *
	 * @return array
	 */
	abstract public function batch_get_results();

	/**
	 * Main plugin method for querying data.
	 *
	 * We need to run the query twice for each step. The first query is run in order to properly set
	 * the total number of results retrieved from the *query*. This number is then compared to the original total
	 * from the *request*, and a new offset is calculated based on these values. Once the offset is calculated, we
	 * run the query again, this time actually pulling the results.
	 *
	 * @since 0.1
	 *
	 * @return mixed An array of data to be exported in bulk fashion.
	 */
	public function get_results() {
		$this->args = wp_parse_args( $this->args, $this->default_args );
		$this->batch_get_results();
		$this->calculate_offset();

		// Run query again, but this time with the new offset calculated.
		$results = $this->batch_get_results();
		return $results;
	}

	/**
	 * Set the total number of results
	 *
	 * Uses a number passed from the client to the server and compares it to the total objects
	 * pulled by the latest query. If the dataset is larger, we increase the total_num_results number.
	 * Otherwise, keep it at the original (to account for deletion / changes).
	 *
	 * @param int $total_from_query Total number of results from latest query.
	 */
	public function set_total_num_results( $total_from_query ) {
		// If this is past step 1, the client is passing back the total number of results.
		// This accounts for deletion / destructive actions to the data.
		$total_from_request = isset( $_POST['total_num_results'] ) ? absint( $_POST['total_num_results'] ) : 0; // Input var okay.

		// In all cases we want to ensure that we use the higher of the two results total (from client or query).
		// We go with the higher number because we want to lock the total number of steps calculated at it's highest total.
		// With a destructive action, that would be total from request. If addivitve action, it would be total from query.
		// In all other cases, these two numbers are equal, so either would work.
		if ( $total_from_query > $total_from_request ) {
			$this->total_num_results = (int) $total_from_query;
		} else {
			$this->total_num_results = (int) $total_from_request;
		}

		$this->record_change_if_totals_differ( $total_from_request, $total_from_query );
	}

	/**
	 * If the amount of total records has changed, the amount is recorded so that it can
	 * be applied to the offeset when it is calculated. This ensures that the offset takes into
	 * account if new objects have been added or removed from the query.
	 *
	 * @param  int $total_from_request    Total number of results passed up from client.
	 * @param  int $total_from_query      Total number of results retreived from query.
	 */
	public function record_change_if_totals_differ( $total_from_request, $total_from_query ) {
		if ( $total_from_query !== $total_from_request && $total_from_request > 0 ) {
			$this->difference_in_result_totals = $total_from_request - $total_from_query;
		}
	}

	/**
	 * Calculate the offset for the current query.
	 */
	public function calculate_offset() {
		if ( 1 !== $this->current_step ) {
			// Example: step 2: 1 * 10 = offset of 10, step 3: 2 * 10 = offset of 20.
			// The difference in result totals is used in case of additive or destructive actions.
			// if 5 posts were deleted in step 1 (20 - 15 = 5) then the offset should remain at 0 ( offset of 5 - 5) in step 2.
			$this->args['offset'] = ( ( $this->current_step - 1 ) * $this->args[ $this->per_batch_param ] ) - $this->difference_in_result_totals;
		}
	}

	/**
	 * Run this batch export (query for the data and export the results).
	 *
	 * @param int $current_step Current step.
	 */
	public function run( $current_step ) {
		$this->current_step = absint( $current_step );

		$results = $this->get_results();

		if ( empty( $results ) ) {

			$this->add_message( __( 'No results found.', 'wcec' ), 'notice' );

			return $this->format_ajax_details( array(
				'success' => true,
			) );
		}

		$this->add_message( sprintf( __( 'Found %d data.', 'wcec' ), count($results) ), 'success' );

		$this->export_results( $results );

		$per_page = get_option( 'posts_per_page' );
		if ( isset( $this->per_batch_param ) ) {
			$per_page = $this->args[ $this->per_batch_param ];
		}

		/**
		 * Filter the per_page number used to calculate total number of steps. You would get use
		 * out of this if you had a custom $wpdb query that didn't paginate in one of the default
		 * ways supported by the plugin.
		 *
		 * @param int $per_page The number of results per page.
		 */
		$per_page = apply_filters( 'wcec_export_' . $this->export_id . '_per_page', $per_page );

		$total_steps = ceil( $this->total_num_results / $per_page );

		if ( (int) $this->current_step === (int) $total_steps ) {

			// The difference here calcuates the gap between the original total and the most recent query.
			// In the case of a deletion export the final step will have a number exactly equal to the posts_per_page.
			// If 20 total, then the last step would have 4 for instance.
			// In all other cases, the difference would be the same as the total number of results (20 - 0 = 20).
			// The exception is a deletion export where a new object is added during the export.
			// In this case, then the final step would have less then the posts_per_page but never more (so <=).
			// We check this difference and compare it before saying that we are finished. If not, we run the last step over.
			$difference = $this->total_num_results - $this->difference_in_result_totals;
			if ( $difference <= $per_page || $difference === $this->total_num_results ) {

				$args = array(
					'wcec_action' => 'download-export',
					'export_id'      => $this->export_id,
					'nonce'      => wp_create_nonce( 'wcec-download-export' ),
				);

				$download_url = add_query_arg( $args, admin_url() );

				// finish
				// @todo | Download file
				return $this->format_ajax_details( array(
					'total_steps'   => $total_steps,
					'progress'      => 100,
					'download_url' 	=> $download_url,
				) );

			} else {
				$this->current_step = $this->current_step - 1;
			}
		}

		$progress = ( 0 === (int) $total_steps ) ? 100 : ( $this->current_step / $total_steps ) * 100;

		return $this->format_ajax_details( array(
			'total_steps'   => $total_steps,
			'progress'      => $progress,
		) );
	}

	/**
	 * Get details for Ajax requests.
	 *
	 * @param  array $details Array of details to send via Ajax.
	 */
	private function format_ajax_details( $details = array() ) {

		return wp_parse_args( $details, array(
			'success'           => empty( $this->errors ),
			'export'             => $this->name,
			'current_step'      => $this->current_step,
			'total_num_results' => $this->total_num_results,
			'messages' 			=> $this->get_all_messages(),
			// 'file' 				=> $this->file,
			// 'query_results' 		=> $results,
			// 'args' 				=> $this->args,
		) );
	}

	/**
	 * Add message
	 */
	public function add_message( $message, $type = 'success' ) {
		switch ($type) {
			case 'success':
				$this->success[] = $message;
				break;

			case 'notice':
				$this->notices[] = $message;
				break;

			case 'error':
				$this->errors[] = $message;
				break;
		}
	}

	private function get_all_messages(){
		return array(
			'success' 	=> $this->success,
			'notices' 	=> $this->notices,
			'errors' 	=> $this->errors,
		);
	}

	/**
	 * Loop over an array of results (posts, pages, etc) and run the write csv
	 *
	 * @param array $results Array of results from the query.
	 */
	public function export_results( $results ) {

		if( $this->current_step < 2 ) {
			// Make sure we start with a fresh file on step 1
			@unlink( $this->file );
			$this->print_csv_cols();
		}

		foreach ( $results as $result ) {

			try {
				$data = $this->get_csv_data( $result );
				$this->print_csv_rows( $data );

			} catch ( Exception $e ) {
				$this->add_message( $e->getMessage(), 'error' );
			}
		}
	}

	/**
	 * Output the CSV columns
	 * @return string
	 */
	public function print_csv_cols() {

		$col_data = '';
		$cols = $this->get_csv_cols();
		$i = 1;
		foreach( $cols as $col_id => $column ) {
			$col_data .= '"' . addslashes( $column ) . '"';
			$col_data .= $i == count( $cols ) ? '' : ',';
			$i++;
		}
		$col_data .= "\r\n";

		$this->stash_step_data( $col_data );

		return $col_data;

	}

	/**
	 * Print the CSV rows for the current step
	 *
	 * @return string|false
	 */
	public function print_csv_rows( $data ) {

		if( empty( $data ) ) {
			return false;
		}

		$cols     = $this->get_csv_cols();
		$row_data = '';

		$i = 1;
		foreach ( $data as $col_id => $column ) {
			// Make sure the column is valid
			if ( array_key_exists( $col_id, $cols ) ) {
				$row_data .= '"' . addslashes( preg_replace( "/\"/","'", $column ) ) . '"';
				$row_data .= $i == count( $cols ) ? '' : ',';
				$i++;
			}
		}
		$row_data .= "\r\n";

		$this->stash_step_data( $row_data );

		return $row_data;
	}

	/**
	 * Append data to export file
	 * 
	 * @param $data string The data to add to the file
	 * @return void
	 */
	protected function stash_step_data( $data = '' ) {

		$file = $this->get_file();
		$file .= $data;
		@file_put_contents( $this->file, $file );

		// If we have no rows after this step, mark it as an empty export
		$file_rows    = file( $this->file, FILE_SKIP_EMPTY_LINES);
		$default_cols = $this->get_csv_cols();
		$default_cols = empty( $default_cols ) ? 0 : 1;

		$this->is_empty = count( $file_rows ) == $default_cols ? true : false;

	}

	/**
	 * Perform the export
	 *
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		$file = $this->get_file();

		@unlink( $this->file );

		echo $file;

		exit();
	}

	/**
	 * Set the export headers
	 *
	 * @return void
	 */
	public function headers() {
		ignore_user_abort( true );

		set_time_limit( 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( "Expires: 0" );
	}

	public function can_export(){
		return true;
	}

	public function is_writable(){
		return (bool) $this->is_writable;
	}

	public function get_file(){

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	}

	public function get_filename(){
		return $this->filename;
	}
}

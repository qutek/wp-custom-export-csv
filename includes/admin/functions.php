<?php
/**
 * WWP Core Admin Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Load a view from the admin/views folder.
 *
 * If the view is not found, an Exception will be thrown.
 *
 * Example usage: wcec_admin_view('metaboxes/campaign-title');
 *
 * @since  1.0.0
 *
 * @param  string $view      The view to display.
 * @param  array  $view_args Optional. Arguments to pass through to the view itself.
 * @return boolean True if the view exists and was rendered. False otherwise.
 */
function wcec_admin_view( $view, $view_args = array() ) {

	$base_path = wcec()->get_path( 'admin_view' );

	if( isset($view_args[ 'base_path' ]) ){
		$base_path = $view_args[ 'base_path' ];
		unset($view_args[ 'base_path' ]);
	}

	if ( ! empty( $view_args ) && is_array( $view_args ) ) {
		extract( $view_args ); // @codingStandardsIgnoreLine
	}

	/**
	 * Filter the path to the view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path      The default path.
	 * @param string $view      The view.
	 * @param array  $view_args View args.
	 */
	$filename  = apply_filters( 'wcec_admin_view_path', $base_path . $view . '.php', $view, $view_args );

	if ( ! is_readable( $filename ) ) {
		wcec()->get('deprecated')->doing_it_wrong(
			__FUNCTION__,
			__( 'Passed view (' . $filename . ') not found or is not readable.', 'wcec' ),
			'1.0.0'
		);

		return false;
	}

	ob_start();

	include( $filename );

	ob_end_flush();

	return true;
}
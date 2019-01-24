<?php
/**
 * WWP Core Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wcec(){
	return WP_Custom_Export_CSV::instance();
}

/**
 * Shortcut the WCEC_Deprecated class, loading the file if required.
 *
 * @since  1.0.0
 *
 * @return WCEC_Deprecated
 */
function wcec_deprecated() {
	return wcec()->get( 'deprecated' );
}
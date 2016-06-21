<?php
/*
Plugin Name: WSU Transfer Credit Equivalencies
Version: 0.0.1
Description: A tool for searching transfer credit equivalencies.
Author: washingtonstateuniversity, philcable
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-Transfer-Equivalencies
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuwp-transfer-equivalencies.php';

add_action( 'after_setup_theme', 'WSUWP_Transfer_Equivalencies' );
/**
 * Start things up.
 *
 * @return \WSUWP_Transfer_Equivalencies
 */
function WSUWP_Transfer_Equivalencies() {
	return WSUWP_Transfer_Equivalencies::get_instance();
}

<?php
/*
Plugin Name: Web Font Icon Manager
Text Domain: web-font-icon-manager
Domain Path: /languages/
Plugin URI: 
Description: Add "data-icon" attribute into custom menu.
Version: 0.1
Author: Noah Kobayashi
Author URI: 
License: 
*/

define( 'WFIM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WFIM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Set text domain
load_plugin_textdomain( 'web-font-icon-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

include( WFIM_PLUGIN_DIR . 'includes/icon-manager.php' );
include( WFIM_PLUGIN_DIR . 'includes/font-file-manager.php' );
include( WFIM_PLUGIN_DIR . 'includes/option-manager.php' );

if ( is_admin() ) {
	$wfim['font_file_manager'] = new WFIM_Font_File_Manager();
	$wfim['option_manager'] = new WFIM_Option_Manager();

	$use_in = get_option( 'wfim_use_in' );
	if ( ! empty( $use_in['custom_menu'] ) ) {
		include( WFIM_PLUGIN_DIR . 'includes/custom-menu-admin.php' );
		new WFIM_Custom_Menu_Admin();
	}
	include( WFIM_PLUGIN_DIR . 'includes/taxonomy-admin.php' );
	new WFIM_Taxonomy_Admin();
} else {
	include( WFIM_PLUGIN_DIR . 'includes/front.php' );
	new WFIM_Front();
}


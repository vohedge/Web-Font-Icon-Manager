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

if ( is_admin() ) {
	// Set text domain
	load_plugin_textdomain( 'web-font-icon-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// Include class file
	include( WFIM_PLUGIN_DIR . 'includes/font-file-manager.php' );
	include( WFIM_PLUGIN_DIR . 'includes/custom-menu-admin.php' );
	include( WFIM_PLUGIN_DIR . 'includes/option-page.php' );

	// Make Instance
	$wfim['font_file_manager'] = new WFIM_Font_File_Manager();
	$wfim['custom_menu_admin'] = new WFIM_Custom_Menu_Admin();
	$wfim['opton_page'] = new WFIM_Option_Page();
}

?>

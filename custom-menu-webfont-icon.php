<?php
/*
Plugin Name: Custom Menu Webfont Icon
Plugin URI: 
Description: Add "data-icon" attribute into custom menu items.
Version: 0.1
Author: Noah Kobayashi
Author URI: 
License: 
*/

class CustomMenuWebfontIcon {
	function __construct() {
		add_action( 'admin_print_scripts-nav-menus.php', array( &$this, 'add_js' ) );
		add_action( 'wp_update_nav_menu_item', array( &$this, 'save' ), 10, 3 );
		add_action( 'admin_print_scripts', array( &$this, 'output' ) );
	}
	function add_js() {
		wp_enqueue_script( 'menu_extend_js', plugin_dir_url( __FILE__ ) . '/js/menu-extend.js', array( 'jquery' ), '0.1', true );
	}
	function save( $menu_id, $menu_item_db_id, $args ) {
		$data_icons = isset( $_POST['menu-item-data-icon'] ) ? $_POST['menu-item-data-icon'] : '';
		if ( ! empty( $data_icons[$menu_item_db_id] ) ) {
			update_post_meta( $menu_item_db_id, '_menu_item_data_icon', sanitize_key( $data_icons[$menu_item_db_id] ) );
		}
	}
	function output() {
		if ( isset( $_REQUEST['menu'] ) )
			$menu_id = $_REQUEST['menu']

		if ( ! isset( $menu_id ) )

		$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );

		$test = "";


?>
<script>
var menu_item_data_icons = {
}
</script>
<?php
	}
}
new CustomMenuWebfontIcon();
?>

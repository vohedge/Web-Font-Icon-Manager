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
		wp_enqueue_script( 'menu_extend_js', plugin_dir_url( __FILE__ ) . 'js/menu-extend.js', array( 'jquery' ), '0.1', true );
	}
	function save( $menu_id, $menu_item_db_id, $args ) {
		$data_icons = isset( $_POST['menu-item-data-icon'] ) ? $_POST['menu-item-data-icon'] : '';
		update_post_meta( $menu_item_db_id, '_menu_item_data_icon', sanitize_key( $data_icons[$menu_item_db_id] ) );
	}
	function output() {
		/* Refer to below
		 * http://core.trac.wordpress.org/browser/tags/3.4.2/wp-admin/nav-menus.php
		 */
		$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;
		$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );
		$recently_edited = (int) get_user_option( 'nav_menu_recently_edited' );
		if ( !$recently_edited && is_nav_menu( $nav_menu_selected_id ) ) {
			$recently_edited = $nav_menu_selected_id;
		} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && is_nav_menu( $recently_edited ) ) {
			$nav_menu_selected_id = $recently_edited;
		} elseif ( 0 == $nav_menu_selected_id && ! isset( $_REQUEST['menu'] ) && ! empty($nav_menus) ) {
			$nav_menu_selected_id = $nav_menus[0]->term_id;
		}

		$args = array( 'order' => 'ASC',
			'orderby' => 'menu_order',
			'post_type' => 'nav_menu_item',
			'post_status' => 'publish',
			'output' => ARRAY_A,
			'output_key' => 'menu_order',
			'nopaging' => true,
			'update_post_term_cache' => false
		);
		$items = wp_get_nav_menu_items( $nav_menu_selected_id, $args );

		$data_icons = array();
		foreach ( $items as $item ) {
			$data_icon = get_post_meta( $item->ID, '_menu_item_data_icon', true );
			if ( ! empty( $data_icon ) )
				$data_icons[] = "\t" . $item->ID . ':"' . esc_js( $data_icon ) . '"';
		}

		if ( ! empty( $data_icons ) ) {
?>
<script>
var menu_item_data_icons = { 
<?php echo implode( ",\n", $data_icons) . "\n"; ?>
}
</script>
<?php
		}
	}
}
new CustomMenuWebfontIcon();
?>

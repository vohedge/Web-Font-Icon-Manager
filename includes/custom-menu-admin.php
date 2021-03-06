<?php
class WFIM_Custom_Menu_Admin {
	function __construct() {
		add_action( 'wp_update_nav_menu_item', array( &$this, 'save' ), 10, 3 );
		add_action( 'admin_print_scripts-nav-menus.php', array( &$this, 'admin_print_scripts' ) );
		add_action( 'admin_print_styles-nav-menus.php', array( &$this, 'admin_print_styles' ) );
	}

	/**
	 * Add scripts and variables into head on "Menu" admin screen
	 * 
	 * @return void
	 */
	function admin_print_scripts() {
		wp_enqueue_script( 'wfim_custom_menu', WFIM_PLUGIN_URL . 'js/web-font-icon-manager-custom-menu.js', array( 'jquery' ), '0.1', true );
		$this->pass_the_meta_to_js();
		WFIM_Icon_Manager::pass_the_code_points_to_js();
		WFIM_Icon_Manager::add_icon_selector_js();
		$this->pass_nonce_value_to_js();
	}

	/**
	 * Add css into head on "Menu" admin screen
	 * 
	 * @return void
	 */
	function admin_print_styles() {
		WFIM_Icon_Manager::at_font_face( true );
		WFIM_Icon_Manager::add_icon_selector_styles();
	}

	/**
	 * Save "data-icon" as meta data with custom menu
	 *
	 * All the params come from hook 'wp_update_nav_menu_item'
	 * 
	 * @TODO Data check
	 *
	 * @param integer $menu_id
	 * @param integer $menu_item_db_id
	 * @param array $args
	 * @return void
	 */
	function save( $menu_id, $menu_item_db_id, $args ) {
		if ( ! $this->check_nonce() )
			return;

		$code_point = isset( $_POST['menu-item-data-icon'] ) ? $_POST['menu-item-data-icon'] : '';
		$font_name = isset( $_POST['menu-item-data-icon-class'] ) ? $_POST['menu-item-data-icon-class'] : '';
		update_post_meta( $menu_item_db_id, '_menu_item_data_icon', $code_point[$menu_item_db_id] );
		update_post_meta( $menu_item_db_id, '_menu_item_data_icon_class', $font_name[$menu_item_db_id] );
	}

	/**
	 * Pass the nonce value to javascript
	 *
	 * @return void
	 */
	function pass_nonce_value_to_js() {
?>
<script>
	var wfim_cm_nonce = '<?php echo wp_create_nonce( 'web-font-icon-manager-nonce' ); ?>';
</script>
<?php	
	}

	/** 
	 * Check referer before save meta data
	 *
	 * @return boolen If the referer is from correct admin screen return true
	 * @link http://codex.wordpress.org/Function_Reference/wp_create_nonce
	 */
	function check_nonce() {
		$nonce = $_REQUEST['web-font-icon-manager-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'web-font-icon-manager-nonce') )
			return false;

		return true;
	}

	/**
	 * Pass the meta data to javascript as global variable
	 *
	 * @return void
	 */
	function pass_the_meta_to_js() {
		$nav_menu_selected_id = $this->get_menu_id();
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
		if ( empty( $items ) )
			return;

		$data_icons = array();
		$font_names = array();
		foreach ( $items as $item ) {
			$data_icon = get_post_meta( $item->ID, '_menu_item_data_icon', true );
			$font_name = get_post_meta( $item->ID, '_menu_item_data_icon_class', true );
			if ( ! empty( $data_icon ) ) 
				$data_icons[] = "\t" . $item->ID . ':"' . esc_js( $data_icon ) . '"';
			if ( ! empty( $font_name ) ) 
				$font_names[] = "\t" . $item->ID . ':"' . esc_js( $font_name ) . '"';
		}

		if ( ! empty( $data_icons ) ) {
?>
<script>
var menu_item_data_icons = { 
<?php echo implode( ",\n", $data_icons ) . "\n"; ?>
}
var menu_item_data_icons_classes = { 
<?php echo implode( ",\n", $font_names ) . "\n"; ?>
}
</script>
<?php
		}
	}

	/**
	 * Get cureent menu id on "Menu" admin screen
	 *
	 * Copy from wordpress code
	 * http://core.trac.wordpress.org/browser/tags/3.4.2/wp-admin/nav-menus.php
	 *
	 * @return integer menu_id
	 * @link http://core.trac.wordpress.org/browser/tags/3.4.2/wp-admin/nav-menus.php
	 */
	function get_menu_id() {
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
		return $nav_menu_selected_id;
	}
}


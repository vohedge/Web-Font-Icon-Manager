<?php
include( WFIM_PLUGIN_DIR . 'includes/walker-classes.php' );

class WFIM_Front {
	function __construct() {
		add_action( 'wp_head', array( &$this, 'add_default_js' ) );
		add_action( 'wp_print_scripts', array( &$this, 'add_default_css' ) );
		add_filter( 'wp_nav_menu_args', array( &$this, 'change_default_menu_walker' ) );
		add_filter( 'wp_page_menu_args', array( &$this, 'fix_no_menu_warning' ) );
		add_filter( 'widget_categories_args', array( &$this, 'change_category_widget' ) );
	}

	/**
	 * Add default css
	 *
	 * @return void
	 */
	function add_default_css() {
		if ( get_option( 'wfim_default_css' ) != '1' )
			return;

		WFIM_Icon_Manager::at_font_face();
	}

	/**
	 * Add default javascript for IE6.7
	 * 
	 * @TODO
	 * @return void
	 */
	function add_default_js() {
?>
<!--[if lt IE 8]>
<script type="text/javascript" src="<?php echo WFIM_PLUGIN_URL . 'js/web-font-manager-ie67-fix.js'; ?>"></script>
<![endif]-->
<?php
	}

	/**
	 * Change default menu walker
	 *
	 * @return void
	 */
	function change_default_menu_walker( $args ) {
		$args = (object) $args;
		$args->walker = new WFIM_Walker_Nav_Menu_With_Icon();
		return $args;
	}

	/**
	 * Fix worning when no menu is set
	 *
	 * When no menu is set, wp_nav_men() calls wp_page_menu() with same arguments
	 * to show page list instead of custom menu.
	 * Then wp_page_menu() uses WFIM_Walker_Nav_Menu_With_Icon class.
	 * WFIM_Walker_Nav_Menu_With_Icon is created to show custom menu with icon.
	 * So worning is occured.
	 * This method fix the problem.
	 *
	 * @return array
	 */
	function fix_no_menu_warning( $args ) {
		if ( get_class( $args['walker'] ) == 'WFIM_Walker_Nav_Menu_With_Icon' )
			$args['walker'] = null;

		return $args;
	}

	/**
	 * Category widget customize
	 *
	 * @return void
	 */
	function change_category_widget( $cat_args ) {
		$cat_args['walker'] = new WFIM_Walker_Category;
		return $cat_args;
	}
}


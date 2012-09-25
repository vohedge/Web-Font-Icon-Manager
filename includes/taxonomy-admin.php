<?php
class WFIM_Taxonomy_Admin {
	function __construct() {
		add_action ( 'init', array( &$this, 'add_icon_fields') );
		add_action ( 'init', array( &$this, 'save') );
		add_action ( 'admin_print_scripts-edit-tags.php', array( &$this, 'admin_print_scripts' ) );
		add_action ( 'admin_print_styles-edit-tags.php', array( &$this, 'admin_print_styles' ) );
	}

	/**
	 * Add field all category/tag/taxonomy screen
	 *
	 * @return void
	 */
	function add_icon_fields() {
		// Get Taxonomies
		$args = array(
			'public'   => true,
			'_builtin' => false
		); 
		$taxonomies = get_taxonomies( $args );

		/**
		 * Add fields
		 */
		// Category
		add_action ( 'category_add_form_fields', array( &$this, 'add_icon_field') );
		add_action ( 'edit_category_form_fields', array( &$this, 'add_icon_field') );
		
		// Tag
		add_action ( 'add_tag_form_fields', array( &$this, 'add_icon_field') );
		if ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'post_tag' )
			add_action ( 'edit_tag_form_fields', array( &$this, 'add_icon_field') );

		// Link
		// add_action( @TODO );	
		// add_action ( 'edit_link_category_form_fields', array( &$this, 'add_icon_field') );

		// Custom Taxonomy
		foreach ( $taxonomies as $taxonomy ) {
			add_action ( $taxonomy . '_add_form_fields', array( &$this, 'add_icon_field') );
		}
		if ( isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === $taxonomy )
			add_action ( 'edit_tag_form_fields', array( &$this, 'add_icon_field') );
	}

	/**
	 * Add scripts and variables into head on category/tag/taxonomy admin screen
	 * 
	 * @return void
	 */
	function admin_print_scripts() {
		WFIM_Icon_Manager::pass_the_code_points_to_js();
		WFIM_Icon_Manager::add_icon_selector_js();
		wp_localize_script( 'wfim_icon_selector', 'wfim_cm_i18n', WFIM_Icon_Manager::js_i18n() );
	}

	/**
	 * Add css into head on category/tag/taxonomy admin screen
	 * 
	 * @return void
	 */
	function admin_print_styles() {
		WFIM_Icon_Manager::at_font_face();
		WFIM_Icon_Manager::add_icon_selector_styles();
	}

	/**
	 * Add icon field into category screen
	 *
	 * @return void
	 */
	function add_icon_field( $tag ) {
		// Get saved value
		$tag_id = isset( $tag->term_id ) ? $tag->term_id : '';
		if ( is_numeric( $tag_id ) ) {
			$code_point = get_post_meta( $tag_id, 'wfim_code_point', true );
			$font_name = get_post_meta( $tag_id, 'wfim_font_name', true );
		}
		$code_point = ! empty( $code_point ) ? $code_point : '';
		$font_name = ! empty( $font_name ) ? $font_name : '';

		// Get mode
		$mode = isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['tag_ID'] ) && is_numeric( $_GET['tag_ID'] ) ? 'edit' : '';
?>
<?php echo $mode == 'edit' ? '<tr class="form-field">' : '<div class="form-field">'; ?>
<?php echo $mode == 'edit' ? '<th>' : ''; ?>
<label for="wfim_code_point"><?php _e( 'Icon', 'web-font-icon-manager' ); ?></label>
<?php echo $mode == 'edit' ? '</th>' : ''; ?>
<?php echo $mode == 'edit' ? '<td class="field-data-icon">' : '<div class="field-data-icon">'; ?>
<input class="wfim_icon_select" value="アイコンを選択" type="button" style="width: auto" />
<input type="hidden" name="wfim_code_point" id="wfim_code_point" class="data_icon" value="<?php if ( isset ( $code_point ) ) echo esc_html( $code_point ) ?>" />
<input type="hidden" name="wfim_font_name" id="wfim_font_name" class="font_name" value="<?php if ( isset ( $font_name ) ) echo esc_html( $font_name ) ?>" />
<span class="icon_preview<?php if ( ! empty( $font_name ) ) echo ' icon-' . $font_name; ?>"><?php if ( ! empty( $code_point ) ) echo '&#' . $code_point . ';'; ?></span>
<?php wp_nonce_field( 'web-font-icon-save', 'web-font-icon-manager-nonce' ) ?>
<?php echo $mode == 'edit' ? '</td>' : '</div>'; ?>
<?php echo $mode == 'edit' ? '</tr>' : '</div>'; ?>
<?php
	}

	/**
	 * Save icon info as category/tag/taxonomy post meta
	 *
	 * @return void
	 */
	function save() {
		add_action ( 'edited_term', array( &$this, 'save_meata_data' ), 10, 1 );
		add_action ( 'create_term', array( &$this, 'save_meata_data' ), 10, 1 );
	}

	/**
	 * Save term meta data
	 *
	 * @return void
	 */
	function save_meata_data( $term_id ) {
		$nonce = $_REQUEST['web-font-icon-manager-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'web-font-icon-save') )
			return false;

		$code_point = isset( $_POST['wfim_code_point'] ) ? $_POST['wfim_code_point'] : '';
		$font_name = isset( $_POST['wfim_font_name'] ) ? $_POST['wfim_font_name'] : '';
		update_post_meta( $term_id, 'wfim_code_point', sanitize_key( $code_point ) );
		update_post_meta( $term_id, 'wfim_font_name', sanitize_key( $font_name ) );
	}
}


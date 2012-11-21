<?php
class WFIM_Option_Manager {
	private $font_ext2type;
	private $taxonomies;

	function __construct() {
		$this->font_ext2type = array( 'ttf', 'woff', 'svg', 'eot' );
		add_action( 'admin_menu', array( &$this, 'add_submenu') );
		add_action( 'admin_print_scripts-appearance_page_wfim_option', array( &$this, 'add_option_js' ) );
		add_action( 'admin_print_styles-appearance_page_wfim_option', array( &$this, 'add_option_css' ) );
		add_action( 'admin_init', array( &$this, 'save_options' ) );
	}

	/**
	 * Add javascipt to option page
	 *
	 * @return void
	 */
	public function add_option_js() {
		wp_enqueue_script( 'wfim_option_page', WFIM_PLUGIN_URL . 'js/web-font-icon-manager-option-page.js', array( 'jquery' ), '0.1', true );
	}

	/**
	 * Add css to option page
	 *
	 * @return void
	 */
	public function add_option_css() {
		WFIM_Icon_Manager::at_font_face( true );
		wp_enqueue_style( 'wfim_option_page', WFIM_PLUGIN_URL . 'css/web-font-icon-manager-option-page.css', '0.1', true );
	}

	/**
	 * Add sub menu into "Appearance"
	 *
	 * @return void
	 */
	public function add_submenu() {
		add_submenu_page( 'themes.php', __( 'Web Font Icon', 'web-font-icon-manager' ), __( 'Icon', 'web-font-icon-manager' ), 'update_themes', 'wfim_option', array( &$this, 'option_page' ) ); 
	}

	/**
	 * Show option page
	 *
	 * @return void
	 */
	public function option_page() {
		global $wfim;
		$options = $this->get_options();
		extract( $options );
		$taxonomies_and_posttypes = $this->get_post_type_and_taxonomies();
		$taxonomy_labels = $this->get_taxonomy_labels();
		$post_type_labels = $this->get_post_type_labels();

		$msg_output = '';
		$errors =  $wfim['font_file_manager']->errors;
		if ( ! empty( $errors ) && is_array( $errors ) ) {
			$msg_output .= '<div id="message" class="updated below-h2">';
			foreach( $errors as $error )
				$msg_output .= '<p>' . esc_html( $error ) . '</p>';
			$msg_output .= '</div>';
		}	
		$messages =  $wfim['font_file_manager']->messages;
		if ( ! empty( $messages ) && is_array( $messages ) ) {
			$msg_output .= '<div id="message" class="updated below-h2">';
			foreach( $messages as $message )
				$msg_output .= '<p>' . esc_html( $error ) . '</p>';
			$msg_output .= '</div>';
		}	
?>
<div class="wrap">
<div class="icon32" id="icon-themes"><br></div>
<h2><?php _e( 'Web Font Icon', 'web-font-icon-manager' ); ?></h2>

<?php echo $msg_output; ?>

<form action="" method="post" enctype="multipart/form-data">
<input type="hidden" name="wfim_option_page_action" value="font_upload" />
<?php wp_nonce_field( 'wfim_font_upload', 'wfim_option_page_nonce' ); ?>
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e( 'Add font File', 'web-font-icon-manager' ); ?></th>
<td>
<input type="file" name="font_file" />
<input type="submit" id="add_font_file" class="button" value="<?php _e( 'Upload', 'web-font-icon-manager' ); ?>" /><br />
<span><?php _e( 'Upload necessary files (.eot, .woff, .tff, .svg)', 'web-font-icon-manager' ); ?></span><br />
</td>
</tr>
</tbody>
</table>
</form>

<form action="" method="post">
<input type="hidden" name="wfim_option_page" value="option_page" />
<?php wp_nonce_field( 'wfim_option_page_update', 'wfim_option_page_nonce' ); ?>
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e( 'Select font', 'web-font-icon-manager' ); ?></th>
<td>
<?php WFIM_Font_File_Manager::font_list( $fonts ); ?>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e( 'Where to use', 'web-font-icon-manager' ); ?></th>
<td>
<ul>
<?php
		foreach ( $taxonomies_and_posttypes as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$checked = ! empty( $use_in['taxonomy'][$post_type][$taxonomy] ) ? ' checked="checked"' : '';
				echo '<li><input type="checkbox" name="wfim_use_in[taxonomy][' . $post_type . '][' . $taxonomy . ']" ' . $checked . ' /> ';
				echo $post_type_labels[$post_type] . ' > ' . $taxonomy_labels[$taxonomy] . "</li>\n";
			}
		}
?>
<li><input type="checkbox" name="wfim_use_in[custom_menu]" <?php echo ! empty( $use_in['custom_menu'] ) ? 'checked="checked"' : ''; ?>/> <?php _e( 'Appearance' ); ?> &gt; <?php _e( 'Menus' ); ?></li>
</ul>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e( 'Options', 'web-font-icon-manager' ); ?></th>
<td>
<ul>
<li><input type="checkbox" name="wfim_default_css" <?php echo $this->checked( $default_css ); ?>/> <?php _e( 'Use default css', 'web-font-icon-manager' ); ?></li>
<li><input type="checkbox" name="wfim_default_js" <?php echo $this->checked( $default_js ); ?>/> <?php _e( 'Use default js for IE6,7', 'web-font-icon-manager' ); ?></li>
</ul>
</td>
</tr>
</tbody>
</table>
<p class="submit"><input type="submit" value="<?php _e( 'Save', 'web-font-icon-manager' ); ?>" class="button-primary" id="submit" name="submit"></p>
</form>
</div><!-- .wrap -->
<?php	
	}

	/**
	 * Get taxonomies
	 * return both builtin and custom taxonomies.
	 * but only non hierarchical taxonomies.
	 *
	 * @return array
	 */
	private function get_taxonomies() {
		$builtin_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => true ), 'object', 'and' );
		$custom_taxonomies = get_taxonomies( array( '_builtin' => false ), 'object', 'and' );
		$taxonomies = array_merge( $builtin_taxonomies, $custom_taxonomies );
		// unset( $taxonomies['post_tag'] );
		unset( $taxonomies['post_format'] );
		return $this->taxonomies = $taxonomies;
	}

	/**
	 * Get post_types and taxonomies
	 * Now return non hierarchical taxonomies only.
	 *
	 * @return array
	 */
	public function get_post_type_and_taxonomies() {
		if ( empty( $this->taxonomies ) || is_array( $this->taxonomies ) ) 
			$this->get_taxonomies();

		// Taxonomy and Post types
		$taxonomies_and_posttypes = array();
		foreach ( $this->taxonomies as $taxonomy ) {
			/* if ( ! $taxonomy->hierarchical )
				continue; 
			 */

			$post_types = $taxonomy->object_type;
			foreach ( $post_types as $post_type )
				$taxonomies_and_posttypes[$post_type][] = $taxonomy->name;
		}
		return $taxonomies_and_posttypes;
	}

	/**
	 * Get taxonomy labels
	 *
	 * @return array
	 */
	private function get_taxonomy_labels() {
		if ( empty( $this->taxonomies ) || is_array( $this->taxonomies ) ) 
			$this->get_taxonomies();

		// Taxonomy label
		$taxonomy_labels = array();
		foreach ( $this->taxonomies as $taxonomy )
			$taxonomy_labels[$taxonomy->name] = $taxonomy->label;

		return $taxonomy_labels;
	}

	/**
	 * Get post type labels
	 *
	 * @return array
	 */
	private function get_post_type_labels() {
		$post_type_labels = array();
		$builtin_post_types = get_post_types( array( 'public' => true, '_builtin' => true ), 'object', 'and' );
		$custom_post_types = get_post_types( array( '_builtin' => false ), 'object', 'and' );
		$post_types = array_merge( $builtin_post_types, $custom_post_types );
		foreach ( $post_types as $post_type )
			$post_type_labels[$post_type->name] = $post_type->label;

		return $post_type_labels;
	}

	/**
	 * Save options
	 *
	 * @return void
	 */
	public function save_options() {
		if ( ! $this->pre_save_security_check() )
			return;

		$options = $this->post_data_prepare();
		extract( $options );
		update_option( 'wfim_default_css', $default_css );
		update_option( 'wfim_default_js', $default_js );
		update_option( 'wfim_fonts', $fonts );
		update_option( 'wfim_use_in', $use_in );
	}

	/**
	 * Pre save security check
	 *
	 * @return boolen 
	 */
	private function pre_save_security_check() {
		if ( ! isset( $_REQUEST['wfim_option_page'] ) )
			return false;

		if ( $_REQUEST['wfim_option_page'] != 'option_page' )
			return false;

		if ( ! isset( $_POST['wfim_option_page_nonce'] ) )
			return false;

		if ( ! wp_verify_nonce( $_POST['wfim_option_page_nonce'], 'wfim_option_page_update' ) )
			die( 'Error has occured' );

		return true;
	}

	/**
	 * Post data check
	 *
	 * @return array
	 */
	private function post_data_prepare() {
		$default_css = ! empty( $_POST['wfim_default_css'] ) ? 1 : 0;
		$default_js = ! empty( $_POST['wfim_default_js'] ) ? 1 : 0;

		// Fonts
		$fonts = ! empty( $_POST['wfim_fonts'] ) ? $_POST['wfim_fonts'] : '';
		if ( is_array( $fonts ) ) {
			$_fonts = array();
			foreach ( $fonts as $font )
				$_fonts[] = esc_html( $font );

			$fonts = array_filter( $_fonts );
		} else {
			$fonts = array( esc_html( $fonts ) );
		}

		// Use in
		$taxonomies_and_posttypes = $this->get_post_type_and_taxonomies();
		$use_in = ! empty( $_POST['wfim_use_in'] ) ? $_POST['wfim_use_in'] : '';
		foreach ( $taxonomies_and_posttypes as $post_type => $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $use_in['taxonomy'][$post_type][$taxonomy] ) )
					$_use_in['taxonomy'][$post_type][$taxonomy] = 1;
				else
					$_use_in['taxonomy'][$post_type][$taxonomy] = 0;
			}
		}
		if ( isset( $use_in['custom_menu'] ) )
			$_use_in['custom_menu'] = 1;
		else
			$_use_in['custom_menu'] = 0;

		$use_in = $_use_in;
		return compact( 'default_css', 'default_js', 'fonts', 'use_in' );
	}

	/**
	 * Get options
	 *
	 * @return array
	 */
	private function get_options() {
		$default_css = get_option( 'wfim_default_css' );
		$default_js = get_option( 'wfim_default_js' );
		$fonts = get_option( 'wfim_fonts' );
		$use_in = get_option( 'wfim_use_in' );
		return compact( 'default_css', 'default_js', 'fonts', 'use_in' );
	}

	/**
	 * Set checked=checked to checkbox
	 *
	 * @return string
	 */
	private function checked( $value ) {
		if ( isset( $value ) && ! empty( $value ) )
			return 'checked=checked ';

		return '';
	}

	/**
	 * Show value into text box
	 *
	 * @return void or string
	 */
	private function value( $value, $echo ) {
		if ( $value && $echo )
			echo esc_html( $value );

		elseif ( $value && ! $echo )
			return $value;
	}

	/**
	 * Get icons to active
	 *
	 * @return mixed
	 */
	static public function get_active_fonts() {
		$icons = get_option( 'wfim_fonts', true );
		if ( empty( $icons ) || ! is_array( $icons ) )
			$icons = false;

		return $icons;
	}
}


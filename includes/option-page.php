<?php
class WFIM_Option_Page {
	private $plugin_dir_url;
	private $font_ext2type;

	function __construct() {
		$this->plugin_dir_url = WFIM_PLUGIN_URL;
		$this->font_ext2type = array( 'ttf', 'woff', 'svg', 'eot' );
		add_filter( 'ext2type', array( &$this, 'add_ext2type' ) );
		add_filter( 'upload_mimes', array( &$this, 'add_minetype' ) );
		add_action( 'admin_menu', array( &$this, add_submenu ) );
		add_action( 'admin_print_scripts-appearance_page_wfim_option', array( &$this, 'add_option_js' ) );
		add_action( 'admin_print_styles-appearance_page_wfim_option', array( &$this, 'add_option_css' ) );
		add_action( 'init', array( &$this, 'save_options' ) );
	}

	/**
	 * Add javascipt to option page
	 *
	 * @return void
	 */
	function add_option_js() {
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script('media-upload');
		wp_enqueue_script( 'wfim_option_page', $this->plugin_dir_url . 'js/web-font-icon-manager-option-page.js', array( 'jquery' ), '0.1', true );
	}

	/**
	 * Add css to option page
	 *
	 * @return void
	 */
	function add_option_css() {
		wp_enqueue_style('thickbox');
		wp_enqueue_style( 'wfim_option_page', $this->plugin_dir_url . 'css/web-font-icon-manager-option-page.css', '0.1', true );
	}

	/**
	 * Add sub menu into "Appearance"
	 *
	 * @return void
	 */
	function add_submenu() {
		add_submenu_page( 'themes.php', __( 'Web Font Icon', 'web-font-icon-manager' ), __( 'Icon', 'web-font-icon-manager' ), 'update_themes', 'wfim_option', array( &$this, 'option_page' ) ); 
	}

	/**
	 * Show option page
	 *
	 * @return void
	 */
	function option_page() {
		$options = $this->get_options();
		extract( $options );

		$font_url_fields = '';
		$i = 0;
		foreach ( $font_urls as $font_url ) {
			if ( ! empty( $font_url ) ) {
				$font_url_fields .= "<li><input type=\"text\" name=\"wfim_font_urls[]\" size=\"80\" value=\"" . $this->value( $font_url, false ) . "\" />";
				$font_url_fields .= $i ? "<span class=\"remove\"> X </span>" : "";
				$font_url_fields .=	"</li>\n";
			}
			$i++;
		}

		$font_name = $this->get_font_info();
?>
<div class="wrap">
<div class="icon32" id="icon-themes"><br></div>
<h2><?php _e( 'Web Font Icon', 'web-font-icon-manager' ); ?></h2>

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
<span>*Upload Fonts*</span>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e( 'Options', 'web-font-icon-manager' ); ?></th>
<td>
<ul>
<li><input type="checkbox" name="wfim_default_css" <?php $this->checked( $default_css ); ?>/> <?php _e( 'Use default css', 'web-font-icon-manager' ); ?></li>
<li><input type="checkbox" name="wfim_default_js" <?php $this->checked( $default_js ); ?>/> <?php _e( 'Use default js for IE6,7', 'web-font-icon-manager' ); ?></li>
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
	 * Save options
	 *
	 * @return void
	 */
	function save_options() {
		if ( ! $this->pre_save_security_check() )
			return;

		$options = $this->post_data_prepare();
		extract( $options );
		update_option( 'wfim_default_css', $default_css );
		update_option( 'wfim_default_js', $default_js );
		update_option( 'wfim_font_urls', $font_urls );
		update_option( 'wfim_font_codes', $font_codes );
	}

	/**
	 * Pre save security check
	 *
	 * @return boolen 
	 */
	function pre_save_security_check() {
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
	function post_data_prepare() {
		$p = $_POST;
		$default_css = ! empty( $_POST['wfim_default_css'] ) ? 1 : 0;
		$default_js = ! empty( $_POST['wfim_default_js'] ) ? 1 : 0;
		$font_urls = ! empty( $_POST['wfim_font_urls'] ) ? $_POST['wfim_font_urls'] : '';
		$font_codes = ! empty( $_POST['wfim_font_codes'] ) ? $_POST['wfim_font_codes'] : '';

		if ( is_array( $font_urls ) ) {
			$_font_urls = array();
			foreach ( $font_urls as $font_url )
				$_font_urls[] = esc_url( $font_url );

			$font_url = array_filter( $_font_urls );
		} else {
			$font_urls = array( $font_urls );
		}

		if ( is_array( $font_codes ) ) {
			$_font_codes = array();
			foreach ( $font_codes as $font_code )
				$_font_codes[] = esc_attr( $font_code );

			$font_codes = array_filter( $_font_codes );
		} else {
			$font_codes = array( $font_codes );
		}

		return compact( 'default_css', 'default_js', 'font_urls', 'font_codes' );
	}

	/**
	 * Get options
	 *
	 * @return array
	 */
	function get_options() {
		$default_css = get_option( 'wfim_default_css' );
		$default_js = get_option( 'wfim_default_js' );
		$font_urls = get_option( 'wfim_font_urls' );
		$font_codes = get_option( 'wfim_font_codes' );
		return compact( 'default_css', 'default_js', 'font_urls', 'font_codes' );
	}

	/**
	 * Set checked=checked to checkbox
	 *
	 * @return void
	 */
	function checked( $value ) {
		if ( $value )
			echo 'checked=checked ';
	}

	/**
	 * Show value into text box
	 *
	 * @return void or string
	 */
	function value( $value, $echo ) {
		if ( $value && $echo )
			echo esc_html( $value );

		elseif ( $value && ! $echo )
			return $value;
	}

	/**
	 * Get font info
	 *
	 * @return array
	 */
	function get_font_info( $font_file = '' ) {
		$font_file = '/home/noah/workspace/my_projects/plugin_dev/wp-content/plugins/web-font-icon-manager/fonts/TinyIconFont.woff';

		if ( ! class_exists( 'Font' ) )
			return;

		if ( ! file_exists( $font_file ) )
			return;

		$font = Font::load( $font_file );
		if ( $font instanceof Font_TrueType_Collection )
			$font = $font->getFont(0);

		$font->parse();
		$records = $font->getData( 'name', 'records' );

		return $records[3];
	}

	/**
	 * Add extention of font files to wordpress
	 *
	 * @return void
	 */
	function add_ext2type( $ext2type ) {
		array_push( $ext2type['font'], $this->font_ext2type );
		return $ext2type;
	}

	/** 
	 * Add mine type of font files to wordpress
	 *
	 * @return void
	 */
	function add_minetype( $mimes ) {
		$mimes['ttf|woff|svg|eot'] = 'application/octet-stream';
		return( $mimes );
	}
}

?>

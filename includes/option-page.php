<?php
class WFIM_Option_Page {

	function __construct() {
		add_action( 'admin_menu', array( &$this, add_submenu ) );
	}

	/**
	 * Add sub menu into "Appearance"
	 *
	 * @return void
	 */
	function add_submenu() {
		add_submenu_page( 'themes.php', __( 'Web Font Icon', 'web-font-icon-manager' ), __( 'Icon', 'web-font-icon-manager' ), 'update_themes', 'test', array( &$this, 'option_page' ) ); 
	}

	/**
	 * Show option page
	 *
	 * @return void
	 */
	function option_page() {
?>
<div class="wrap">
<div class="icon32" id="icon-themes"><br></div>
<h2><?php _e( 'Web Font Icon', 'web-font-icon-manager' ); ?></h2>
<form action="" method="post">
<input type="hidden" name="wfim_option_page" value="option_page" />
<?php wp_nonce_field( 'wfim_option_page_update', 'wfim_option_page_nonce' ); ?>
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e( 'Font file url', 'web-font-icon-manager' ); ?></th>
<td><input type="text" name="wfim_font_url[]" size="80" /><br />
<span><?php _e( 'e.g.', 'web-font-icon-manager' ); ?> <?php echo get_stylesheet_directory_uri() . '/fonts/MyIconFont.woff'; ?></span>
</td>
</tr>
<tr valign="top">
</tr>
<tr valign="top">
</tr>
</tbody>
</table>
</form>
</div><!-- .wrap -->
<?php	
	}
}

?>

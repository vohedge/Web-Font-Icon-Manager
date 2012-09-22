<?php
include( WFIM_PLUGIN_DIR . 'lib/php-font-lib/classes/font.cls.php' );

class WFIM_Font_File_Manager {
	private $post_type;
	private $allow_ext;

	function __construct() {
		$this->post_type = 'wfim_fonts';
		$this->allow_ext = array( 'ttf', 'woff', 'svg', 'eot' );
		$this->upload_error = array();
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'init', array( &$this, 'save_font' ) );
	}

	/**
	 * Register font management post type
	 *
	 * @return void
	 */
	function register_post_type() {
		$args = array(
			'label' => 'fonts',
			'description' => 'Web Font Icon Manager plugin use this post type',
			'public' => false,
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array( 'title' )
		);
		register_post_type( $this->post_type, $args );
	}

	/**
	 * Pre save security check
	 *
	 * @return boolen 
	 */
	function save_font() {
		if ( ! isset( $_POST['wfim_option_page_action'] ) )
			return false;

		if ( $_POST['wfim_option_page_action'] != 'font_upload' )
			return false;

		if ( ! isset( $_POST['wfim_option_page_nonce'] ) )
			return false;

		if ( ! current_user_can( 'edit_theme_options' ) )
			return false;

		if ( ! wp_verify_nonce( $_POST['wfim_option_page_nonce'], 'wfim_font_upload' ) )
			die( 'Error has occured' );

		if ( ! isset( $_FILES['font_file'] ) )
			return false;
		else
			$file = $_FILES['font_file'];

		if ( ! empty( $file['error'] ) )
			return false;

		// Check filesize
		if ( ! filesize( $file['tmp_name'] ) > 0 )
			return $this->upload_error = __( 'This file is 0KB.' );

		// Check if uploaded
		if ( ! is_uploaded_file( $file['tmp_name'] ) )
			return $this->upload_error = __( 'Error has occured.' );

		// Check Extention
		$ext = ltrim(strrchr($file['name'], '.'), '.');
		if ( in_array( $this->allow_ext, $ext ) )
			return $this->upload_error = __( 'This extention is not allowed.' );

		// Move the file to the uploads dir 
		$uploads = wp_upload_dir();
		$filename = wp_unique_filename( $uploads['path'], $file['name'] );
		$new_file = $uploads['path'] . "/$filename";
		if ( false === @ move_uploaded_file( $file['tmp_name'], $new_file ) )
			return $this->upload_error = __( 'The upload file could not move uploads directory.' );

		// Set correct file permissions 
		$stat = stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0000666;
		@ chmod( $new_file, $perms );

		// Get URL
		$url = $uploads['url'] . "/$filename";
	
		$this->create_font_post( $new_file, $url );
	}

	/** 
	 * Create font record
	 *
	 * @return void
	 */
	function create_font_post( $file_path, $url ) {
		$font_name = $this->get_font_name( $file_path );
		echo $font_name;
	}

	/**
	 * Get font name
	 *
	 * @return string
	 */
	function get_font_name( $font_file_path ) {
		if ( ! class_exists( 'Font' ) )
			return false;

		if ( ! file_exists( $font_file_path ) )
			return false;
		
		$font = Font::load( $font_file_path );
		if ( $font instanceof Font_TrueType_Collection )
			$font = $font->getFont(0);

		$font->parse();
		$records = $font->getData( 'name', 'records' );

		return $records[3];
	}
}	
?>

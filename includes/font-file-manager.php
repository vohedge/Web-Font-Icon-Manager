<?php
include( WFIM_PLUGIN_DIR . 'lib/php-font-lib/classes/font.cls.php' );

class WFIM_Font_File_Manager {
	const post_type = 'wfim_fonts';
	const ext_list = 'ttf,woff,otf,svg,eot';
	const ext_list_has_codepoints = 'ttf,woff';

	function __construct() {
		$this->errors = array();
		$this->messages = array();
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'init', array( &$this, 'dispatcher' ) );
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
		register_post_type( self::post_type, $args );
	}

	/**
	 * Dsipacher
	 *
	 * @return void
	 */
	function dispatcher() {
		if ( ! isset( $_REQUEST['wfim_option_page_action'] ) )
			return false;

		switch ( $_REQUEST['wfim_option_page_action'] ) {
			case 'font_upload':
				$this->save_font();
				break;

			case 'font_delete':
				$this->delete_font();
				break;
		}
	}

	/**
	 * Save posted font file
	 *
	 * @return void 
	 */
	function save_font() {
		if( ! $this->pre_save_check() )
			return;

		$result = $this->save_file();
		if ( $result === false || ! is_array( $result ) )
			return;

		extract( $result );
		$pathinfo = pathinfo( $file_name );
		$font_name = $pathinfo['filename'];
		$ext = $pathinfo['extension'];
		// Code point test
		$code_points = ( $ext == 'ttf' || $ext == 'woff' || $ext == 'otf' ) ? $this->get_code_points_from_font( $file_path ) : '';
		$this->create_font_post( $font_name, $ext, $file_name, $file_path, $url, $code_points );
	}

	/**
	 * Pre save security check
	 *
	 * @return boolen 
	 */
	function pre_save_check() {
		if ( ! isset( $_POST['wfim_option_page_nonce'] ) )
			return false;

		if ( ! wp_verify_nonce( $_POST['wfim_option_page_nonce'], 'wfim_font_upload' ) )
			die( 'Error has occured' );

		if ( ! current_user_can( 'edit_theme_options' ) )
			return false;

		if ( ! isset( $_FILES['font_file'] ) )
			return false;

		if ( ! empty( $_FILES['font_file']['error'] ) )
			return false;

		// Check filesize
		if ( ! filesize( $_FILES['font_file']['tmp_name'] ) > 0 )
			return false;

		// Check if uploaded
		if ( ! is_uploaded_file( $_FILES['font_file']['tmp_name'] ) )
			return false;

		// Check Extention
		$ext = ltrim( strrchr( $_FILES['font_file']['name'], '.' ), '.' );
		$ext_approved = self::get_approved_ext_list();
		if ( ! in_array( $ext, $ext_approved ) )
			return false;

		// Check file type
		if ( ! self::check_font_file_type( $_FILES['font_file']['name'], $_FILES['font_file']['tmp_name'] ) )
			return false;

		return true;
	}

	/** 
	 * Check font file type
	 *
	 * @return boolen
	 */
	private function check_font_file_type( $file_name, $file_path ) {
		$ext = ltrim( strrchr( $file_name, '.' ), '.' );
		$font = Font::load( $file_path );
		if ( $font instanceof Font_TrueType && $ext == 'ttf' )
			return true;

		if ( $font instanceof Font_OpenType && $ext == 'otf' )
			return true;

		if ( $font instanceof Font_WOFF && $ext == 'woff' )
			return true;

		if ( $font instanceof Font_EOT && $ext == 'eot' )
			return true;

		if ( self::verify_svg_font( $file_path ) && $ext == 'svg' )
			return true;
	
		return false;
	}

	/**
	 * Verify svg font file
	 *
	 * Return true if the file is svg font file.
	 *
	 * @return boolen
	 */
	private function verify_svg_font( $file_path ) {
		$font = simplexml_load_file( $file_path );
		if ( ! isset( $font->defs->font->{'font-face'} ) )
			return false;

		if ( ! isset( $font->defs->font->glyph ) )
			return false;

		if ( ! isset( $font->defs->font->{'missing-glyph'} ) )
			return false;

		return true;
	}

	/**
	 * Parse svg font format
	 *
	 * This method was not used.
	 *
	 * @TODO How convert code point value
	 * @return boolen
	 */
	private function parse_svg_font_file( $file_path ) {
		$font = simplexml_load_file( $file_path );
		$font = json_decode( json_encode( $font ), true );
		$glyphs = $font['defs']['font']['glyph'];
		$code_points = array();
		foreach ( $glyphs as $glyph ) {
			if ( empty( $glyph['@attributes']['unicode'] ) )
				continue;

			$code_point = $glyph['@attributes']['unicode'];
			// $code_points[] = bin2hex( $code_point );
		}
	}

	/**
	 * Save posted file to font directory
	 *
	 * @return void 
	 */
	function save_file() {
		$file = $_FILES['font_file'];

		// Create font directory
		$uploads = wp_upload_dir();
		$uploads_dir = $uploads['basedir'] . '/wfim_icon_fonts/';
		if ( ! file_exists( $uploads_dir ) && ! is_dir( $uploads_dir ) ) {
			if ( ! mkdir( $uploads_dir, 0777, true ) )
				return $this->error( __( "Can't make fonts directory in /wp-content/uploads/", 'web-font-icon-manager' ) );
		}
		
		// Check same name file
		$file_name = $this->sanitize_file_name( $file['name'] );
		$file_name = str_replace( '-', '_', $file_name );
		$new_file = $uploads_dir . $file_name;
		if ( file_exists( $new_file ) )
			$this->info( __( "Same name file was overwritten.", 'web-font-icon-manager' ) );
		
		// Move the file to the uploads dir 
		if ( false === @ move_uploaded_file( $file['tmp_name'], $new_file ) )
			return $this->error ( __( 'The upload file could not move uploads directory.', 'web-font-icon-manager' ) );

		// Set correct file permissions 
		$stat = stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0000666;
		@ chmod( $new_file, $perms );

		// Get URL
		$url = $uploads['baseurl'] . "/wfim_icon_fonts/$file_name";

		// File Path
		$file_path = $new_file;
	
		return compact( 'file_name', 'file_path', 'url' );
	}

	/**
	 * Sanitize file name
	 *
	 * @param file_name string
	 * @return string
	 */
	function sanitize_file_name( $file_name ) {
		$info = pathinfo( $file_name );
		$ext = ! empty( $info['extension']) ? '.' . $info['extension'] : '';
		$file_name = str_replace( $ext, '', $file_name );
		$name_enc = rawurlencode( $file_name);
		$file_name = ($file_name == $name_enc) ? $file_name . $ext : md5( $file_name ) . $ext;
		return $file_name;
	}

	/** 
	 * Create font record
	 *
	 * @return void
	 */
	function create_font_post( $font_name, $file_type, $file_name, $file_path, $url, $code_points ) {
		// Check existing posts
		$args = array(
			'numberposts'     => -1,
			'offset'          => 0,
			'orderby'         => 'post_date',
			'order'           => 'DESC',
			'post_type'       => self::post_type,
			'post_status'     => 'publish',
			'meta_key'        => 'file_path',
			'meta_value'      => $file_path
		);
		$id = null;
		$_fonts = get_posts( $args ); 
		if ( ! empty( $_fonts ) && is_array( $_fonts ) ) {
			foreach ( $_fonts as $font )
				$id = $font->ID;
		}

		// Insert or update font post
		$post = array(
			'ID'			=> $id,
			'post_status'	=> 'publish',
			'post_title'	=> $font_name,
			'post_type'		=> self::post_type
		);
		if ( ! $id = wp_insert_post( $post ) )
			return $this->error( __( "Can't Create font infomation record.", 'web-font-icon-manager' ) );

		// Update font post meta
		update_post_meta( $id, 'file_path', $file_path );
		update_post_meta( $id, 'file_type', $file_type );
		update_post_meta( $id, 'url', $url );
		update_post_meta( $id, 'code_points', $code_points );
	}

	/**
	 * Get code points of font file
	 *
	 * @return mixid
	 */
	private function get_code_points_from_font( $font_file_path ) {
		if ( ! class_exists( 'Font' ) )
			return '';

		$font = Font::load( $font_file_path );
		if ( $font instanceof Font_TrueType_Collection )
			$font = $font->getFont(0);

		$cmap_f12 = '';
		$cmap_f4 = '';
		foreach( $font->getData("cmap", "subtables") as $_subtable ) {
			if ( $_subtable["platformID"] == 3 && $_subtable["platformSpecificID"] == 10 )
				$cmap_f12 = $_subtable;

			if ( $_subtable["platformID"] == 3 && $_subtable["platformSpecificID"] == 1 )
				$cmap_f4 = $_subtable;
		}

		$none_glyph_ids = array();
		$loca = $font->getData("loca");
		if ( ! empty( $loca ) && is_array( $loca ) ) {
			foreach( $loca as $index => $gid ) {
				if ( $index == 0 || ( isset( $loca[$index + 1] ) && $loca[$index + 1] - $gid == 0 ) )
					$none_glyph_ids[] = $index;
				$gid_prev = $gid;
			}
		}

		$glyphIndexArray = array();
		if ( ! empty( $cmap_f12['glyphIndexArray'] ) && is_array( $cmap_f12['glyphIndexArray'] ) )
			$glyphIndexArray += $cmap_f12['glyphIndexArray'];

		if ( ! empty( $cmap_f4['glyphIndexArray'] ) && is_array( $cmap_f4['glyphIndexArray'] ) )
			$glyphIndexArray += $cmap_f4['glyphIndexArray'];

		$code_points = array();
		foreach ( $glyphIndexArray as $code_point => $gid ) {
			if ( $gid != 0 && ! in_array( $gid, $none_glyph_ids ) )
				$code_points[] = $code_point;
		}

		return array_unique( $code_points );
	}

	private function delete_font() {
		// Check before delete action
		$id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

		if ( ! is_numeric( $id ) )
			return false;

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wfim_delete_font_' . $id ) )
			return $this->error( __( "Error has occured.", 'web-font-icon-manager' ) );

		// Delete font file
		$error = 0;
		$file_path = get_post_meta( $id, 'file_path', true );
		if ( file_exists( $file_path ) ) {
			if ( ! unlink( $file_path ) ) {
				$this->error( __( "Could not delete font file.", 'web-font-icon-manager' ) );
				$error++;
			}
		}
		
		// Delete font file post
		if ( ! wp_delete_post( $id, true ) ) {
			$this->error( __( "Could not delete font file record.", 'web-font-icon-manager' ) );
			$error++;
		}

		// Success message
		if ( $error == 0 )
			$this->info( __( "Font file removing complete.", 'web-font-icon-manager' ) );
	}

	/**
	 * Error hudle
	 *
	 * @return boolen false
	 */
	private function error( $err_message ) {
		array_push( $this->errors, $err_message );
		return false;
	}

	/**
	 * Error hudle
	 *
	 * @return boolen false
	 */
	private function info( $message ) {
		array_push( $this->messages, $message );
		return false;
	}

	/**
	 * Get font list
	 *
	 * @return array
	 */
	static public function font_list( $saved_fonts ) {
		if ( ! is_array( $saved_fonts ) )
			$saved_fonts = array();

		$fonts = self::get_fonts();
		if ( empty( $fonts ) || ! is_array( $fonts ) ) {
			_e( 'Please upload font file.', 'web-font-icon-manager' );
			return;
		}
		
		// Show font list
		$output = '';
		foreach ( $fonts as $font => $types ) {
			ksort( $types );
			$checked = '';
			if ( in_array( $font, $saved_fonts ) )
				$checked = ' checked="checked"';

			// Font name and type
			$output .= "<div id=\"font_list\"><div class=\"font_name\"><label><input type=\"checkbox\" name=\"wfim_fonts[]\" value=\"$font\" $checked/> $font</label>";
			foreach ( $types as $type => $args ) {
				$output .= "<span class=\"$type\">$type</span>";
			}
			$output .= "<a class=\"toggle\" href=\"\" class=\"show_font_list\">" . __( 'File List', 'web-font-icon-manager') . "</a></div>\n";

			// File List
			$output .= "<ul class=\"file_list\">\n";
			foreach ( $types as $type => $args ) {
				$output .= "<li>$font.$type";
				$output .= "<a href=\"" . wp_nonce_url( "?page=wfim_option&wfim_option_page_action=font_delete&post={$args['id']}", "wfim_delete_font_{$args['id']}" ) . "\" />";
				$output .= __( 'delete', 'web-font-icon-manager' ) . "</a>";
				$output .= "</li>\n";
			}
			$output .= "</ul>\n";

			// Preview
			$output .= "<ul class=\"preview icon-$font\">\n";
			foreach ( $types as $type => $args ) {
				if ( ( $type == 'ttf' || $type == 'woff' ) && isset( $args['code_points'] ) && is_array( $args['code_points'] ) ) {
					foreach( $args['code_points'] as $code_point ) {
						$output .= "<li>&#$code_point;</li>\n";
					}
					break;
				}
			}
			$output .= "</ul>\n</div>\n";
		}
		echo $output;
	}

	/**
	 * Get fonts info from db
	 *
	 * @return mixed
	 */
	static public function get_fonts() {
		$fonts = '';
		$args = array(
			'numberposts'     => -1,
			'offset'          => 0,
			'orderby'         => 'post_date',
			'order'           => 'DESC',
			'post_type'       => self::post_type,
			'post_status'     => 'publish'
		);
		$_fonts = get_posts( $args ); 
		if ( empty( $_fonts ) || ! is_array( $_fonts ) )
			return false;

		foreach ( $_fonts as $font ) {
			$file_type = get_post_meta( $font->ID, 'file_type', true ); 
			$url = get_post_meta( $font->ID, 'url', true ); 
			$code_points = get_post_meta( $font->ID, 'code_points', true ); 
			$fonts[$font->post_title][$file_type]['id'] = $font->ID;
			$fonts[$font->post_title][$file_type]['url'] = $url;
			$fonts[$font->post_title][$file_type]['code_points'] = $code_points;
		}

		if ( empty ( $font_name ) || ! in_array( $font_name, $fonts ) )
			return $fonts;

		return $fonts[$font_name];
	}

	/**
	 * Get a font info from db
	 *
	 * @return mixed
	 */
	static public function get_font( $font_name ) {
		$fonts = self::get_fonts();
		if ( empty( $fonts ) )
			return false;

		if ( empty ( $font_name ) || ! array_key_exists( $font_name, $fonts ) )
			return false;

		return $fonts[$font_name];
	}

	/**
	 * Get code points of specific font form db
	 *
	 * @param $font_name string
	 * @return mixed
	 */
	static public function get_code_points( $font_name ) {
		if ( empty( $font_name ) )
			return false;
		
		$font = self::get_font( $font_name );
		if ( empty( $font ) )
			return false;

		foreach ( array( 'ttf', 'woff' ) as $type ) {
			if ( isset( $font[$type] ) && isset( $font[$type]['code_points']) )
				return $font[$type]['code_points'];
		}

		return false;
	}

	/** 
	 * Get url of font file
	 *
	 * @param string $font_name 
	 * @return array
	 */
	static public function get_urls( $font_name ) {
		$font = self::get_font( $font_name );
		if ( empty( $font ) || ! is_array( $font ) )
			return false;
		
		$urls = array();
		$exts = self::get_approved_ext_list();
		foreach( $exts as $ext ) {
			if ( ! empty ( $font[$ext]['url'] ) )
				$urls[$ext] = $font[$ext]['url'];
		}

		return $urls;
	}

	/**
	 * Get approved ext list
	 *
	 * @return array
	 */
	static private function get_approved_ext_list() {
		return explode( ',', self::ext_list );
	}
}	
?>

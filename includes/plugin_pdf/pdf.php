<?php

require_once dirname( __FILE__ ) . '/class.ucm_pdf.php';

class module_pdf extends module_base
{
	public $links;
	public $pdf_library;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function __construct() {
		$this->module_name     = 'pdf';
		$this->module_position = 410;
		$this->version         = 2.137;
	}

	/**
	 * @return array
	 */
	public function third_party_libraries() {
		return array(
			'dompdf' => ucm_pdf::library(),
		);
	}

	/**
	 * Runs when this plugin's version is applied, on in-app updates and zip uploads alike.
	 * Updating never deletes files, so take out the old bundled dompdf here.
	 */
	function install_upgrade() {
		ucm_pdf::remove_bundled_dompdf();

		return parent::install_upgrade();
	}

	function init() {
		// 2.1 - added arialuni ttf file
		// 2.11 - more options for pdf generation
		// 2.12 - better unicode configuration
		// 2.121 - 2013-05-27 - space after slash fix
		// 2.122 - 2013-05-27 - jpeg image quality improvement
		// 2.123 - 2014-05-21 - faster/better pdf generation, READ BLOG FOR DETAILS!
		// 2.124 - 2014-05-23 - faster/better pdf generation, READ BLOG FOR DETAILS!
		// 2.125 - 2014-07-01 - cron job pdf fix
		// 2.126 - 2014-07-01 - better mpdf unicode support
		// 2.127 - 2014-08-18 - mpdf update
		// 2.128 - 2014-09-29 - pdf generation file path fix
		// 2.129 - 2015-01-27 - mpdf bug fix
		// 2.13 - 2015-01-31 - mpdf update
		// 2.131 - 2016-01-06 - mpdf ttf update
		// 2.132 - 2016-05-02 - support for online pdf rocket service
		// 2.133 - 2016-07-10 - php7 support
		// 2.134 - 2021-04-07 - php8 compatibility fix
		// 2.135 - 2021-09-24 - change to dompdf generation
		// 2.136 - 2026-10-17 - 4.0.0 relaunch: PDFs saved to disk, automatic mPDF installer removed, PHP 8.4
		// 2.137 - 2026-11-02 - dompdf 3 from Composer replaces the bundled dompdf 1.0.2; the old folder is removed on upgrade
		$this->links = array();
	}

	public function pre_menu() {
		// the link within Admin > Settings > Emails.
		if ( $this->can_i( 'edit', 'PDF Settings', 'Config' ) ) {
			$this->links[] = array(
				"name"                => "PDF",
				"p"                   => "pdf_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}
	}
}


function convert_html2pdf( $html, $pdf ) {

	$library = module_config::c( 'pdf_library' );
	if ( ! $library ) {
		$library = 'dompdf';
	}
	switch ( $library ) {
		case 'pdfrocket':
			$api_key = module_config::c( 'pdf_rocket_api_key', '' );
			if ( ! $api_key ) {
				die( 'Please enter an API key in Settings > PDF' );
			}
			$post_fields = array(
				'apikey'       => $api_key,
				'value'        => file_get_contents( $html ),
				'generator'    => 'UltimateClientManager',
				'PageSize'     => module_config::c( 'pdf_media_size', 'A4' ),
				'MarginLeft'   => module_config::c( 'pdf_media_left', '10' ),
				'MarginRight'  => module_config::c( 'pdf_media_right', '10' ),
				'MarginTop'    => module_config::c( 'pdf_media_top', '10' ),
				'MarginBottom' => module_config::c( 'pdf_media_bottom', '10' ),
			);
			$url         = 'http://api.html2pdfrocket.com/pdf';
			if ( ! function_exists( 'curl_init' ) ) {
				$postdata = http_build_query(
					$post_fields
				);
				$opts     = array(
					'http' =>
						array(
							'method'  => 'POST',
							'header'  => 'Content-type: application/x-www-form-urlencoded',
							'content' => $postdata
						)
				);
				$context  = stream_context_create( $opts );
				$result   = file_get_contents( $url, false, $context );
			} else {
				$ch = curl_init( $url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_HEADER, false );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 ); // fixes netregistr, may break others?
				$result = curl_exec( $ch );
			}
			file_put_contents( $pdf, $result );
			break;
		default:
			ini_set( 'error_reporting', E_ERROR );
			ini_set( "display_errors", true );
			if ( ! ucm_libraries::load( 'dompdf' ) ) {
				set_error( 'PDF generation is not available: the dompdf library is missing.' );

				return false;
			}
			set_time_limit( 120 );

			// Dompdf::stream() sends the PDF straight to the browser and writes nothing to
			// disk. Every caller of this function expects a file afterwards - to attach to
			// an email, or to serve with its own headers - so write the file out instead.
			$output = ucm_pdf::render( file_get_contents( $html ), module_config::c( 'pdf_media_size', 'A4' ) );
			if ( false === $output ) {
				return false;
			}
			file_put_contents( $pdf, $output );

			break;
	}

	return str_replace( _UCM_FOLDER, '', $pdf );
}

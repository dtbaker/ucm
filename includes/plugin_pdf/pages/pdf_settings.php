<?php

$options = array(
	'html2ps' => 'html2ps',
	'mpdf'    => 'mPDF',
	//'pdfrocket' => 'PDF Rocket Service',
);
if ( ! is_file( 'includes/plugin_pdf/html2ps/html2ps.config' ) ) {
	unset( $options['html2ps'] );
}
if ( ! is_dir( 'includes/plugin_pdf/mpdf/' ) || ! is_file( 'includes/plugin_pdf/mpdf/mpdf.php' ) ) {
	unset( $options['mpdf'] );
	if ( isset( $_REQUEST['auto_pdf_install'] ) ) {
		if ( is_writable( "includes/plugin_pdf/" ) ) {
			chdir( "includes/plugin_pdf/" );
			$ch = curl_init( "http://ultimateclientmanager.com/files/mpdf.zip" );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			$zip = curl_exec( $ch );
			if ( ! $zip ) {
				$zip = @file_get_contents( "http://ultimateclientmanager.com/files/mpdf.zip" );
			}
			if ( strlen( $zip ) > 100 ) {
				file_put_contents( 'mpdf.zip', $zip );
				if ( is_file( 'mpdf.zip' ) ) {

					/* from php.net. simply unzip function */
					function unzip_mpdf( $file, $debug = false, $die = true, $overwrite = false ) {

						if ( class_exists( 'ZipArchive' ) ) {
							$zip = new ZipArchive;
							$zip->open( $file );
							$zip->extractTo( './' );
							$zip->close();

							return true;
						}
						if ( ! function_exists( 'zip_open' ) ) {
							$result = 0;
							passthru( "unzip $file", $result );
							if ( ! $result ) {
								return true;
							} else {
								return false;
							}
						}
						$zip = zip_open( $file );
						if ( is_resource( $zip ) ) {
							while ( ( $zip_entry = zip_read( $zip ) ) !== false ) {
								if ( $debug ) {
									echo "Unpacking " . zip_entry_name( $zip_entry ) . "\n";
								}
								if ( strpos( zip_entry_name( $zip_entry ), DIRECTORY_SEPARATOR ) !== false ) {
									$last = strrpos( zip_entry_name( $zip_entry ), DIRECTORY_SEPARATOR );
									$dir  = substr( zip_entry_name( $zip_entry ), 0, $last );
									$file = substr( zip_entry_name( $zip_entry ), strrpos( zip_entry_name( $zip_entry ), DIRECTORY_SEPARATOR ) + 1 );
									if ( ! is_dir( $dir ) ) {
										if ( ! mkdir( $dir, 0755, true ) ) {
											if ( $die ) {
												die( "Unzipping Failed: Unable to create directory: $dir\n" );
											} else {
												return false;
											}
										}
									}
									if ( strlen( trim( $file ) ) > 0 ) {
										if ( ! file_exists( $dir . "/" . $file ) || $overwrite ) {
											$return = @file_put_contents( $dir . "/" . $file, zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) ) );
										} else {
											if ( $debug ) {
												echo "File already exists: " . $dir . "/" . $file . "\n";
											}
											$return = false;
										}
										if ( $return === false ) {
											if ( $die ) {
												die( "Unzipping Failed: Unable to write file: $dir/$file\n" );
											} else {
												continue;
											}
										}
									}
								} else {
									$file = zip_entry_name( $zip_entry );
									if ( ! file_exists( $file ) || $overwrite ) {
										$return = @file_put_contents( $file, zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) ) );
									} else {
										if ( $debug ) {
											echo "File already exists: " . $file . "\n";
										}
										$return = false;
									}
									if ( $return === false ) {
										if ( $die ) {
											die( "Unzipping Failed: Unable to write file: $file\n" );
										} else {
											continue;
										}
									}
								}
							}
						} else {
							if ( $die ) {
								die( "Unzipping Failed: Unable to open zip file: $file \n" );
							} else {
								return false;
							}
						}

						return true;
					}

					if ( unzip_mpdf( 'mpdf.zip', false, true ) ) {
						set_message( 'Success. mPDF installed. You can now print PDFs' );
						redirect_browser( str_replace( 'auto_pdf_install', '', $_SERVER['REQUEST_URI'] ) );
					} else {
						echo " Installation of mPDF failed. Sorry, please try the manual method below.";
					}

				}
			}

		}
	}
	?>
	<h3>Please install the mPDF library:</h3>
	Automatic Install (recommended):
	<ul>
		<li><a
				href="<?php echo htmlspecialchars( $_SERVER["REQUEST_URI"] ) . ( strpos( $_SERVER["REQUEST_URI"], '?' ) ? '&' : '?' ) . 'auto_pdf_install'; ?>">Click
				here to attempt an automatic installation</a> (recommended)
		</li>
	</ul>
	Manual Install:
	<ul>
		<li><a href="http://ultimateclientmanager.com/files/mpdf.zip">Click here</a> to download mpdf.zip</li>
		<li>Unzip this file to your desktop</li>
		<li>Upload the 'mpdf' folder to this UCM installation: includes/plugin_pdf/mpdf/</li>
		<li>Come back here, if this message is gone then it worked and you can now generate PDFs</li>
	</ul>
	<?php
}
$settings = array(
	array(
		'key'         => 'pdf_library',
		'default'     => 'mpdf',
		'type'        => 'select',
		'blank'       => false,
		'options'     => $options,
		'description' => 'Which PDF library to use',
		'help'        => 'Choose a different PDF library if you notice PDF generation is not working or is slow. mPDF is the better option but may not display some things correctly.',
	),
	array(
		'key'         => 'pdf_media_size',
		'default'     => 'A4',
		'type'        => 'text',
		'description' => 'PDF media size',
		'help'        => 'eg: A4 or Letter',
	),
	array(
		'key'         => 'pdf_media_left',
		'default'     => '10',
		'type'        => 'text',
		'description' => 'PDF left padding',
	),
	array(
		'key'         => 'pdf_media_right',
		'default'     => '10',
		'type'        => 'text',
		'description' => 'PDF right padding',
	),
	array(
		'key'         => 'pdf_media_top',
		'default'     => '10',
		'type'        => 'text',
		'description' => 'PDF top padding',
	),
	array(
		'key'         => 'pdf_media_bottom',
		'default'     => '10',
		'type'        => 'text',
		'description' => 'PDF bottom padding',
	),
);

if ( module_config::c( 'pdf_library', 'mpdf' ) == 'pdfrocket' ) {
	// please go here: http://www.html2pdfrocket.com/Account/Register
	// choose PHP
	$settings[] = array(
		'key'         => 'pdf_rocket_api_key',
		'default'     => '',
		'type'        => 'text',
		'description' => 'HTML 2 PDF Rocket API Key',
		'help'        => 'Please go to http://www.html2pdfrocket.com/Account/Register - enter your name, email address and choose "PHP" as the Language. You will be emailed an API key. Enter that API key here. You should be able to generate about 100 PDF files a month for free using this service. Any more and you will need to sign up for a paid account, see their website for details.'
	);
}

module_config::print_settings_form(
	array(
		'heading'  => array(
			'title' => 'PDF Settings',
			'type'  => 'h2',
			'main'  => true,
		),
		'settings' => $settings,
	)
);
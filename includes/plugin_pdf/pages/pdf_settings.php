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
	// The automatic mPDF installer was removed. It fetched a zip over plaintext HTTP from
	// a third-party host and extracted it into a PHP include path with no TLS, no
	// signature check and no path-traversal guard - remote code execution for anyone able
	// to MITM the connection or acquire the domain. Install mPDF via Composer or by hand.
	?>
	<h3>Please install the mPDF library:</h3>
	Manual Install:
	<ul>
		<li><a href="https://github.com/mpdf/mpdf/releases" target="_blank" rel="noopener noreferrer">Download
				mPDF</a> from its official release page</li>
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
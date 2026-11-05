<?php

// dompdf is installed with Composer and is the only generator now. The old html2ps and
// mPDF choices were removed with their bundled copies; an install still saved with one of
// them falls back to dompdf in convert_html2pdf().
$options = array(
	'dompdf' => 'dompdf',
	//'pdfrocket' => 'PDF Rocket Service',
);
if ( ! ucm_libraries::have( 'dompdf' ) ) {
	ucm_libraries::print_missing_notice( 'dompdf' );
}
$settings = array(
	array(
		'key'         => 'pdf_library',
		'default'     => 'dompdf',
		'type'        => 'select',
		'blank'       => false,
		'options'     => $options,
		'description' => 'Which PDF library to use',
		'help'        => 'dompdf is installed with Composer and generates every PDF.',
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

if ( module_config::c( 'pdf_library', 'dompdf' ) == 'pdfrocket' ) {
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

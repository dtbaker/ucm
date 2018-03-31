<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'Statistic Settings' ); ?>

<?php module_config::print_settings_form(
	array()
);

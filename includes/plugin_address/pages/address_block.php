<?php
$fields = array(
	"line_1"    => array( module_config::c( 'address_1', "Line 1" ), 30 ),
	"line_2"    => array( module_config::c( 'address_2', "Line 2" ), 30 ),
	"suburb"    => array( module_config::c( 'address_3', "Suburb" ), 30 ),
	"state"     => array( module_config::c( 'address_4', "State" ), 20 ),
	"region"    => array( module_config::c( 'address_5', "Region" ), 20 ),
	"post_code" => array( module_config::c( 'address_6', "Post Code" ), 10 ),
	"country"   => array( module_config::c( 'address_7', "Country" ), 20 ),
);

if ( isset( $display_pretty ) && $display_pretty ) {
	$fieldset_data      = array(
		'heading'  => array(
			'type'       => 'h3',
			'title'      => 'Address',
			'responsive' => array(
				'summary' => '',
			)
		),
		'elements' => array(),
	);
	$responsive_summary = array();
	foreach ( $fields as $key => $val ) {
		if ( ! empty( $val[0] ) || ! empty( $address[ $key ] ) ) {
			$fieldset_data['elements'][ $key ] = array(
				'title' => $val[0],
				'field' => array(
					'type'  => 'text',
					'name'  => 'address[' . $address_hash . '][' . $key . ']',
					'value' => isset( $address[ $key ] ) ? $address[ $key ] : '',
					'size'  => $val[1],
				)
			);
			if ( isset( $address[ $key ] ) && ! empty( $address[ $key ] ) ) {
				$responsive_summary[] = $address[ $key ];
			}
		}
	}
	if ( $responsive_summary ) {
		$fieldset_data['heading']['responsive']['summary'] = htmlspecialchars( implode( ', ', $responsive_summary ) );
	};
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );
} else {
	// oldschool table layout:

	$address_elements = array();
	foreach ( $fields as $key => $val ) {
		if ( ! empty( $val[0] ) || ! empty( $address[ $key ] ) ) {
			// quick added hack for 'region' to display as a drop down
			if ( $key == 'region_id' || $key == 'state_id' || $key == 'country_id' ) {
				ob_start();
				echo print_select_box( get_col_vals( 'address', $key ), 'address[' . $address_hash . '][' . $key . ']', ( isset( $address[ $key ] ) ? $address[ $key ] : '' ), '', false, false, true );
				$address_elements[ $key ] = array(
					'title'  => _l( $val[0] ),
					'fields' => array( ob_get_clean() ),
				);
			} else {
				$address_elements[ $key ] = array(
					'title' => _l( $val[0] ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'address[' . $address_hash . '][' . $key . ']',
						'value' => isset( $address[ $key ] ) ? $address[ $key ] : '',
						'size'  => $val[1],
					),
				);
			}
		}
	}
	echo module_form::generate_fieldset( array(
		'class'    => 'tableclass tableclass_form',
		'elements' => $address_elements,
	) );
}
?>
<input type="hidden" name="address[<?php echo $address_hash; ?>][address_id]" value="<?php echo $address_id; ?>">
<input type="hidden" name="address[<?php echo $address_hash; ?>][owner_id]" value="<?php echo $owner_id; ?>">
<input type="hidden" name="address[<?php echo $address_hash; ?>][owner_table]" value="<?php echo $owner_table; ?>">
<input type="hidden" name="address[<?php echo $address_hash; ?>][address_type]" value="<?php echo $address_type; ?>">
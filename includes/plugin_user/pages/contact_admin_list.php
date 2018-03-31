<?php


$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();

$use_master_key = module_user::get_contact_master_key();
if ( ! $use_master_key ) {
	throw new Exception( 'Sorry no Customer or Vendor selected' );
} else if ( isset( $_REQUEST[ $use_master_key ] ) ) {
	$search[ $use_master_key ] = $_REQUEST[ $use_master_key ];
}
switch ( $use_master_key ) {
	case 'customer_id':
		$contact_type            = 'Customer';
		$contact_type_permission = 'Customer';
		$contact_module_name     = 'customer';
		// is this a customer or a lead?
		$current_customer_type_id = module_customer::get_current_customer_type_id();
		if ( $current_customer_type_id > 0 ) {
			$customer_type = module_customer::get_customer_type( $current_customer_type_id );
			if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
				$contact_type_permission = $customer_type['type_name'];
			}
		}
		break;
	case 'vendor_id':
		$contact_type            = 'Vendor';
		$contact_type_permission = 'Vendor';
		$contact_module_name     = 'vendor';
		break;
	default:
		die( 'Unsupported type' );
}
$module->page_title = _l( $contact_type_permission . ' Contacts' );

if ( ! isset( $search[ $use_master_key ] ) || ! $search[ $use_master_key ] ) {
	// we are just showing a list of all customer contacts.
	$show_customer_details = true;
	// check they have permissions to view all customer contacts.
	if ( class_exists( 'module_security', false ) ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => $contact_type,
			'page_name' => 'All ' . $contact_type_permission . ' Contacts',
			'module'    => $contact_module_name,
			'feature'   => 'view',
		) );
	}
	//throw new Exception('Please create a user correctly');
} else {
	$show_customer_details = false;
}
$users = module_user::get_contacts( $search, true, false );

if ( class_exists( 'module_group', false ) ) {
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this customers?
		array(
			'fields' => array(
				'owner_id'    => 'user_id',
				'owner_table' => 'user',
				'name'        => 'name',
				'email'       => 'email'
			),
		)
	);
}

// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_user::can_i( 'view', 'Export ' . $contact_type_permission . ' Contacts' ) ) {
	if ( isset( $_REQUEST['import_export_go'] ) ) {
		$users = query_to_array( $users );
		foreach ( $users as $user_id => $user ) {
			$users[ $user_id ]['is_primary'] = $user['is_primary'] == $user['user_id'] ? _l( 'Yes' ) : _l( 'No' );
		}
	}

	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this Customer Contacts?
		array(
			'name'   => $contact_type_permission . ' Contact Export',
			'fields' => array(
				$contact_type_permission . ' Contact ID' => 'user_id',
				'First Name'                             => 'name',
				'Last Name'                              => 'last_name',
				$contact_type_permission . ' ID'         => $contact_module_name . '_id',
				$contact_type_permission . ' Name'       => $contact_module_name . '_name',
				'Primary Contact'                        => 'is_primary',
				'Phone'                                  => 'phone',
				'Email'                                  => 'email',
				'Fax'                                    => 'fax',
				'Mobile'                                 => 'mobile',
			),
			// do we look for extra fields?
			'extra'  => array(
				'owner_table' => 'user',
				'owner_id'    => 'user_id',
			),
			'group'  => array(
				array(
					'title'       => 'Contact Group',
					'owner_table' => 'user',
					'owner_id'    => 'user_id',
				)
			),
		)
	);
}
$heading = array(
	'main'   => true,
	'type'   => 'h2',
	'title'  => _l( ( $show_customer_details ? 'All ' : '' ) . $contact_type_permission . ' Contacts' ),
	'button' => array()
);
if ( isset( $search[ $use_master_key ] ) && $search[ $use_master_key ] && module_user::can_i( 'create', 'Contacts', $contact_type_permission ) ) {
	$heading['button'][] = array(
		'title' => 'Add New Contact',
		'url'   => module_user::link_generate( 'new', array( 'type'          => 'contact',
		                                                     $use_master_key => isset( $search[ $use_master_key ] ) ? $search[ $use_master_key ] : false
		) ),
		'type'  => 'add',
	);
}
print_heading( $heading );
?>


<form action="" method="<?php echo _DEFAULT_FORM_METHOD; ?>">
	<?php if ( $use_master_key && isset( $search[ $use_master_key ] ) ) { ?>
		<input type="hidden" name="<?php echo $use_master_key; ?>" value="<?php echo $search[ $use_master_key ]; ?>">
	<?php } ?>


	<?php $search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Contact Name, Email or Phone Number:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 30,
				)
			)
		)
	);
	echo module_form::search_bar( $search_bar );

	/** START TABLE LAYOUT **/
	$table_manager    = module_theme::new_table_manager();
	$columns          = array();
	$columns['name']  = array(
		'title'      => 'Name',
		'callback'   => function ( $user ) {
			echo module_user::link_open_contact( $user['user_id'], true, $user );
			if ( $user['is_primary'] == $user['user_id'] ) {
				echo ' *';
			}
		},
		'cell_class' => 'row_action',
	);
	$columns['phone'] = array(
		'title'    => 'Phone Number',
		'callback' => function ( $user ) {
			module_user::print_contact_summary( $user['user_id'], 'html', array( 'phone|mobile' ) );
		}
	);
	$columns['email'] = array(
		'title'    => 'Email Address',
		'callback' => function ( $user ) {
			module_user::print_contact_summary( $user['user_id'], 'html', array( 'email' ) );
		}
	);
	if ( $show_customer_details ) {
		$columns['customer'] = array(
			'title'    => $contact_type_permission,
			'callback' => function ( $user ) use ( $contact_module_name ) {
				switch ( $contact_module_name ) {
					case 'customer':
						echo module_customer::link_open( $user['customer_id'], true, $user );
						break;
					case 'vendor':
						echo module_vendor::link_open( $user['vendor_id'], true, $user );
						break;
				}
			}
		);
	}
	if ( class_exists( 'module_group', false ) && module_user::can_i( 'view', 'Contact Groups' ) ) {
		$columns['group'] = array(
			'title'    => 'Group',
			'callback' => function ( $user ) {
				$groups = module_group::get_groups_search( array(
					'owner_table' => 'user',
					'owner_id'    => $user['user_id'],
				) );
				$g      = array();
				foreach ( $groups as $group ) {
					$g[] = $group['name'];
				}
				echo implode( ', ', $g );
			}
		);
	}

	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return $row_data; //module_user::get_user($row_data['user_id']);
	};
	$table_manager->set_rows( $users );
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'user', function ( $user ) {
			module_extra::print_table_data( 'user', $user['user_id'] );
		} );
	}
	$table_manager->pagination = true;
	$table_manager->print_table();
	?>
</form>
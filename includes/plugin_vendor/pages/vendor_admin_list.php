<?php

$page_type        = 'Vendors';
$page_type_single = 'Vendor';

$search = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
if ( ! module_vendor::can_i( 'view', $page_type ) ) {
	redirect_browser( _BASE_HREF );
}
$module->page_title = _l( $page_type );


$vendors = module_vendor::get_vendors( $search, array( 'as_resource' => true ) );
// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) ) {
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this vendors?
		array(
			'fields' => array(
				'owner_id'    => 'vendor_id',
				'owner_table' => 'vendor',
				'title'       => $page_type_single . ' Groups',
				'name'        => 'vendor_name',
				'email'       => 'primary_user_email'
			),
		)
	);
}
if ( class_exists( 'module_table_sort', false ) ) {
	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'vendor_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'vendor_name'           => array(
					'field' => 'vendor_name',
					//'current' => 1, // 1 asc, 2 desc
				),
				'primary_contact_name'  => array(
					'field' => 'primary_user_name',
				),
				'primary_contact_email' => array(
					'field' => 'primary_user_email',
				),
				// special case for group sorting.
				'vendor_group'          => array(
					'group_sort'  => true,
					'owner_table' => 'vendor',
					'owner_id'    => 'vendor_id',
				),
			),
		)
	);
}
// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_vendor::can_i( 'view', 'Export ' . $page_type ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this vendors?
		array(
			'name'   => $page_type_single . ' Export',
			'fields' => array(
				$page_type_single . ' ID'    => 'vendor_id',
				$page_type_single . ' Name'  => 'vendor_name',
				'Credit'                     => 'credit',
				'Address Line 1'             => 'line_1',
				'Address Line 2'             => 'line_2',
				'Address Suburb'             => 'suburb',
				'Address Country'            => 'country',
				'Address State'              => 'state',
				'Address Region'             => 'region',
				'Address Post Code'          => 'post_code',
				'Primary Contact First Name' => 'primary_user_name',
				'Primary Contact Last Name'  => 'primary_user_last_name',
				'Primary Phone'              => 'primary_user_phone',
				'Primary Email'              => 'primary_user_email',
				'Primary Fax'                => 'primary_user_fax',
				'Primary Mobile'             => 'primary_user_mobile',
				'Primary Language'           => 'primary_user_language',
				'Invoice Prefix'             => 'default_invoice_prefix',
				'Tax Name'                   => 'default_tax_name',
				'Tax Rate'                   => 'default_tax',
			),
			// do we look for extra fields?
			'extra'  => array(
				array(
					'owner_table' => 'vendor',
					'owner_id'    => 'vendor_id',
				),
				array(
					'owner_table' => 'user',
					'owner_id'    => 'primary_user_id',
				),
			),
			'group'  => array(
				array(
					'title'       => $page_type_single . ' Group',
					'owner_table' => 'vendor',
					'owner_id'    => 'vendor_id',
				)
			),
		)
	);
}

$header_buttons = array();
if ( module_vendor::can_i( 'create', $page_type ) ) {
	$header_buttons[] = array(
		'url'   => module_vendor::link_open( 'new', false ),
		'title' => 'Create New ' . $page_type_single,
		'type'  => 'add',
	);
}
if ( class_exists( 'module_import_export', false ) && module_vendor::can_i( 'view', 'Import ' . $page_type ) ) {
	$header_buttons[] = array(
		'url'   => module_import_export::import_link(
			array(
				'callback'   => 'module_vendor::handle_import',
				'name'       => $page_type,
				'return_url' => $_SERVER['REQUEST_URI'],
				'group'      => 'vendor',
				'fields'     => array(
					$page_type_single . ' ID'    => 'vendor_id',
					$page_type_single . ' Name'  => 'vendor_name',
					'Credit'                     => 'credit',
					'Address Line 1'             => 'line_1',
					'Address Line 2'             => 'line_2',
					'Address Suburb'             => 'suburb',
					'Address Country'            => 'country',
					'Address State'              => 'state',
					'Address Region'             => 'region',
					'Address Post Code'          => 'post_code',
					'Primary Contact First Name' => 'primary_user_name',
					'Primary Contact Last Name'  => 'primary_user_last_name',
					'Primary Phone'              => 'primary_user_phone',
					'Primary Email'              => 'primary_user_email',
					'Primary Fax'                => 'primary_user_fax',
					'Primary Mobile'             => 'primary_user_mobile',
					'Primary Language'           => 'primary_user_language',
					'Invoice Prefix'             => 'default_invoice_prefix',
					'Tax Name'                   => 'default_tax_name',
					'Tax Rate'                   => 'default_tax',
					'Password'                   => 'password',
					'User Role Name'             => 'role',
				),
				// do we try to import extra fields?
				'extra'      => array(
					array(
						'owner_table' => 'vendor',
						'owner_id'    => 'vendor_id',
					),
					array(
						'owner_table' => 'user',
						'owner_id'    => 'primary_user_id',
					),
				),
			)
		),
		'title' => 'Import ' . $page_type,
		'type'  => 'add',
	);
}
if ( file_exists( 'includes/plugin_user/pages/contact_admin_list.php' ) && module_user::can_i( 'view', 'All ' . $page_type_single . ' Contacts', 'Vendor', 'vendor' ) ) {
	$header_buttons[] = array(
		'url'   => module_user::link_open_contact( false, false, array( 'vendor_id' => 0 ) ),
		'title' => 'View All Contacts',
	);
}

print_heading( array(
	'main'   => true,
	'type'   => 'h2',
	'title'  => $page_type,
	'button' => $header_buttons,
) );

?>

<div class="" style="padding: 20px; font-weight:bold;">Notice: This Vendors section will be merged into the core
	"Customers" area soon, so we can make better use of existing features (such as invoicing). Please go to Settings >
	Customers > Customer Types and create a new type called "Vendors" and create any new vendors there.
</div>


<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name'    => array(
				'title' => _l( 'Names, Phone or Email:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 15,
				)
			),
			'address' => array(
				'title' => _l( 'Address:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[address]',
					'value' => isset( $search['address'] ) ? $search['address'] : '',
					'size'  => 15,
				)
			),
		)
	);
	if ( class_exists( 'module_extra', false ) ) {
		$search_bar['extra_fields'] = 'vendor';
	}
	if ( class_exists( 'module_group', false ) && module_vendor::can_i( 'view', $page_type_single . ' Groups' ) ) {
		$search_bar['elements']['group_id'] = array(
			'title' => false,
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[group_id]',
				'value'            => isset( $search['group_id'] ) ? $search['group_id'] : '',
				'options'          => module_group::get_groups( 'vendor' ),
				'options_array_id' => 'name',
				'blank'            => _l( ' - Group - ' ),
			)
		);
	}
	if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
		$companys     = module_company::get_companys();
		$companys_rel = array();
		foreach ( $companys as $company ) {
			$companys_rel[ $company['company_id'] ] = $company['name'];
		}
		$search_bar['elements']['company'] = array(
			'title' => false,
			'field' => array(
				'type'    => 'select',
				'name'    => 'search[company_id]',
				'value'   => isset( $search['company_id'] ) ? $search['company_id'] : '',
				'options' => $companys_rel,
				'blank'   => _l( ' - Company - ' ),
			)
		);
	}
	echo module_form::search_bar( $search_bar );


	/** START TABLE LAYOUT **/
	$table_manager = module_theme::new_table_manager();
	$columns       = array();
	if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
		$columns['company_name'] = array(
			'title'    => 'Company',
			'callback' => function ( $vendor ) {
				if ( isset( $vendor['company_ids'] ) && is_array( $vendor['company_ids'] ) && count( $vendor['company_ids'] ) ) {
					foreach ( $vendor['company_ids'] as $company_id => $company_name ) { ?>
						<a
							href="<?php echo module_vendor::link_open( $vendor['vendor_id'], false ); ?>"><?php echo htmlspecialchars( $company_name ); ?></a>
					<?php }
				} else {
					_e( 'N/A' );
				}
			}
		);
	}
	$columns['vendor_name']           = array(
		'title'      => $page_type_single . ' Name',
		'callback'   => function ( $vendor ) {
			echo module_vendor::link_open( $vendor['vendor_id'], true, $vendor );
		},
		'cell_class' => 'row_action',
	);
	$columns['primary_contact_name']  = array(
		'title'    => 'Primary Contact',
		'callback' => function ( $vendor ) {
			if ( $vendor['primary_user_id'] ) {
				echo module_user::link_open_contact( $vendor['primary_user_id'], true );
			} else {
				echo '';
			}
		}
	);
	$columns['phone_number']          = array(
		'title'    => 'Phone Number',
		'callback' => function ( $vendor ) {
			if ( $vendor['primary_user_id'] ) {
				module_user::print_contact_summary( $vendor['primary_user_id'], 'html', array( 'phone|mobile' ) );
			} else {
				echo '';
			}
		}
	);
	$columns['primary_contact_email'] = array(
		'title'    => 'Email Address',
		'callback' => function ( $vendor ) {
			if ( $vendor['primary_user_id'] ) {
				module_user::print_contact_summary( $vendor['primary_user_id'], 'html', array( 'email' ) );
			} else {
				echo '';
			}
		}
	);
	$columns['address']               = array(
		'title'    => 'Address',
		'callback' => function ( $vendor ) {
			module_address::print_address( $vendor['vendor_id'], 'vendor', 'physical' );
		}
	);
	if ( class_exists( 'module_group', false ) && module_vendor::can_i( 'view', $page_type_single . ' Groups' ) ) {
		$columns['vendor_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $vendor ) {
				if ( isset( $vendor['group_sort_vendor'] ) ) {
					echo htmlspecialchars( $vendor['group_sort_vendor'] );
				} else {
					// find the groups for this vendor.
					$groups = module_group::get_groups_search( array(
						'owner_table' => 'vendor',
						'owner_id'    => $vendor['vendor_id'],
					) );
					$g      = array();
					foreach ( $groups as $group ) {
						$g[] = $group['name'];
					}
					echo htmlspecialchars( implode( ', ', $g ) );
				}
			}
		);
	}
	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_vendor::get_vendor( $row_data['vendor_id'] );
	};
	$table_manager->set_rows( $vendors );
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'vendor', function ( $vendor ) {
			module_extra::print_table_data( 'vendor', $vendor['vendor_id'] );
		} );
		$table_manager->display_extra( 'user', function ( $vendor ) {
			module_extra::print_table_data( 'user', $vendor['primary_user_id'] );
		} );
	}
	$table_manager->pagination = true;
	$table_manager->print_table();

	?>
</form>

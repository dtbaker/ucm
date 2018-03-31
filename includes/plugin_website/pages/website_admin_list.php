<?php

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['customer_id'] ) ) {
	$search['customer_id'] = $_REQUEST['customer_id'];
}
$websites = module_website::get_websites( $search );


// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) ) {
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this customers?
		array(
			'fields' => array(
				'owner_id'    => 'website_id',
				'owner_table' => 'website',
				'name'        => 'name',
				'email'       => ''
			),
		)
	);
}
if ( class_exists( 'module_table_sort', false ) ) {
	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'website_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'website_name'     => array(
					'field'   => 'name',
					'current' => 1, // 1 asc, 2 desc
				),
				'website_url'      => array(
					'field' => 'url',
				),
				'website_customer' => array(
					'field' => 'customer_name',
				),
				'website_status'   => array(
					'field' => 'status',
				),
				// special case for group sorting.
				'website_group'    => array(
					'group_sort'  => true,
					'owner_table' => 'website',
					'owner_id'    => 'website_id',
				),
			),
		)
	);
}
// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_website::can_i( 'view', 'Export ' . module_config::c( 'project_name_plural', 'Websites' ) ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this customers?
		array(
			'name'   => module_config::c( 'project_name_single', 'Website' ) . ' Export',
			'fields' => array(
				module_config::c( 'project_name_single', 'Website' ) . ' ID'     => 'website_id',
				'Customer Name'                                                  => 'customer_name',
				'Customer Contact First Name'                                    => 'customer_contact_fname',
				'Customer Contact Last Name'                                     => 'customer_contact_lname',
				'Customer Contact Email'                                         => 'customer_contact_email',
				module_config::c( 'project_name_single', 'Website' ) . ' Name'   => 'name',
				'URL'                                                            => 'url',
				module_config::c( 'project_name_single', 'Website' ) . ' Status' => 'status',
			),
			// do we look for extra fields?
			'extra'  => array(
				'owner_table' => 'website',
				'owner_id'    => 'website_id',
			),
		)
	);
}
$header_buttons = array();
if ( module_website::can_i( 'create', 'Websites' ) ) {
	$header_buttons[] = array(
		'url'   => module_website::link_open( 'new' ),
		'type'  => 'add',
		'title' => _l( 'Add New ' . module_config::c( 'project_name_single', 'Website' ) ),
	);
}
if ( class_exists( 'module_import_export', false ) && module_website::can_i( 'view', 'Import ' . module_config::c( 'project_name_plural', 'Websites' ) ) ) {
	$link             = module_import_export::import_link(
		array(
			'callback'         => 'module_website::handle_import',
			'callback_preview' => 'module_website::handle_import_row_debug',
			'name'             => module_config::c( 'project_name_plural', 'Websites' ),
			'return_url'       => $_SERVER['REQUEST_URI'],
			'group'            => 'website',
			'fields'           => array(
				module_config::c( 'project_name_single', 'Website' ) . ' ID'     => 'website_id',
				'Customer Name'                                                  => 'customer_name',
				'Customer Contact First Name'                                    => 'customer_contact_fname',
				'Customer Contact Last Name'                                     => 'customer_contact_lname',
				'Customer Contact Email'                                         => 'customer_contact_email',
				module_config::c( 'project_name_single', 'Website' ) . ' Name'   => 'name',
				'URL'                                                            => 'url',
				module_config::c( 'project_name_single', 'Website' ) . ' Status' => 'status',
				'Notes'                                                          => 'notes',
			),
			// extra args to pass to our website import handling function.
			'options'          => array(
				'duplicates' => array(
					'label'        => _l( 'Duplicates' ),
					'form_element' => array(
						'name'    => 'duplicates',
						'type'    => 'select',
						'blank'   => false,
						'value'   => 'ignore',
						'options' => array(
							'ignore'    => _l( 'Skip Duplicates' ),
							'overwrite' => _l( 'Overwrite/Update Duplicates' )
						),
					),
				),
			),
			// do we attempt to import extra fields?
			'extra'            => array(
				'owner_table' => 'website',
				'owner_id'    => 'website_id',
			),
		)
	);
	$header_buttons[] = array(
		'url'   => $link,
		'type'  => 'add',
		'title' => _l( "Import " . module_config::c( 'project_name_plural', 'Websites' ) ),
	);
}
print_heading( array(
	'type'   => 'h2',
	'main'   => true,
	'title'  => _l( 'Customer ' . module_config::c( 'project_name_plural', 'Websites' ) ),
	'button' => $header_buttons,
) )
?>


<form action="" method="post">


	<?php $search_bar = array(
		'elements' => array(
			'name'   => array(
				'title' => _l( 'Name/URL:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 30,
				)
			),
			'status' => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[status]',
					'value'   => isset( $search['status'] ) ? $search['status'] : '',
					'options' => module_website::get_statuses(),
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );


	/** START TABLE LAYOUT **/
	$table_manager           = module_theme::new_table_manager();
	$columns                 = array();
	$columns['website_name'] = array(
		'title'      => 'Name',
		'callback'   => function ( $website ) {
			echo module_website::link_open( $website['website_id'], true, $website );
		},
		'cell_class' => 'row_action',
	);
	if ( module_config::c( 'project_display_url', 1 ) ) {
		$columns['website_url'] = array(
			'title'    => 'URL',
			'callback' => function ( $website ) {
				if ( strlen( trim( $website['url'] ) ) > 0 ) { ?>
					<a href="<?php echo module_website::urlify( $website['url'] ); ?>"
					   target="_blank"><?php echo module_website::urlify( $website['url'] ); ?></a>
				<?php }
			},
		);
	}
	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
		$columns['website_customer'] = array(
			'title'    => 'Customer',
			'callback' => function ( $website ) {
				echo module_customer::link_open( $website['customer_id'], true );
			},
		);
	}
	$columns['website_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $website ) {
			echo htmlspecialchars( $website['status'] );
		},
	);
	if ( class_exists( 'module_group', false ) ) {
		$columns['website_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $website ) {
				if ( isset( $website['group_sort_website'] ) ) {
					echo htmlspecialchars( $website['group_sort_website'] );
				} else {
					// find the groups for this website.
					$groups = module_group::get_groups_search( array(
						'owner_table' => 'website',
						'owner_id'    => $website['website_id'],
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
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'website', function ( $website ) {
			module_extra::print_table_data( 'website', $website['website_id'] );
		} );
	}
	if ( class_exists( 'module_subscription', false ) ) {
		$table_manager->display_subscription( 'website', function ( $website ) {
			module_subscription::print_table_data( 'website', $website['website_id'] );
		} );
	}

	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_website::get_website( $row_data['website_id'] );
	};
	$table_manager->set_rows( $websites );
	$table_manager->pagination = true;
	$table_manager->print_table();
	?>
</form>
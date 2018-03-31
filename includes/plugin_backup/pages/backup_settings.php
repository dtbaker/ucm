<?php

if(!module_config::can_i('view','Settings') || !module_backup::can_i('view','Backups') || defined('_UCM_HIDE_BACKUPS')){
    redirect_browser(_BASE_HREF);
}

$module->page_title = 'Backups';

if (!extension_loaded('zip')){
	?>
	<p>Warning: Full backups will not work correctly because this hosting account does not have the <strong>ZIP</strong> PHP extension enabled. Please contact the hosting provider and ask them to enable the PHP ZipArchive class.</p>
	<?php
}

if(isset($_REQUEST['backup_id']) && $_REQUEST['backup_id']){

	$backup_id = (int)$_REQUEST['backup_id'];


	if($backup_id > 0){


		$backup = module_backup::get_backup($backup_id);
		if(!$backup || $backup['backup_id'] != $backup_id)redirect_browser(_BASE_HREF);
		if(!$backup['backup_file']){
			// start the ajax backup magic.

	        $backup_file_base = 'backup_'.date('Y-m-d').'_'.md5(_UCM_SECRET.time()).'';
	        update_insert('backup_id',$backup_id,'backup',array(
		        'backup_file' => $backup_file_base,
	        ));
			set_message('Backup created');

			?>

			<form action="" method="post">
				<input type="hidden" name="_process" value="save_backup">

				<script type="text/javascript">
					ucm.backup.file_list = [];
					ucm.backup.database_list = [];
				</script>
			<?php

			module_form::print_form_auth();

			$fieldset_data = array(
		        'heading' =>  array(
		            'title' => _l('Backup'),
		            'type' => 'h3',
		        ),
		        'elements' => array(
			        array(
				        'message' => _l('This page will refresh when the backup is completed. Please stay on this page and wait...'),
			        ),
			        array(
				        'message' => _l('Closing this page or refreshing this page will cause the backup to stop.'),
			        ),
			        array(
				        'message' => _l('Backup progress is shown below.'),
			        ),
			        array(
				        'title' => 'Database Backup',
				        'fields' => array(
					        '<ul id="database_backup"> </ul>'
				        )
			        ),
			        array(
				        'title' => 'Files Backup',
				        'fields' => array(
					        '<ul id="files_backup"> </ul>'
				        )
			        ),
		        )
		    );


		    echo module_form::generate_fieldset($fieldset_data);
		    unset($fieldset_data);

			$form_actions = array(
		        'class' => 'action_bar action_bar_center action_bar_single',
		        'elements' => array(
		            array(
		                'type' => 'button',
		                'name' => 'cancel',
		                'value' => _l('Cancel'),
		                'class' => 'submit_button',
		                'onclick' => "window.location.href='".module_backup::link_open(false)."';",
		            ),
		        ),
		    );
		    echo module_form::generate_form_actions($form_actions);

			?>
			</form>


			<script type="text/javascript">
				ucm.backup.file_list = [];
				ucm.backup.database_list = [];
				ucm.backup.backup_delay = <?php echo (int)module_config::c('backup_post_delay',1000);?>;
				<?php
				$source = dirname(__FILE__).'/../../../'; // UCM root.
		        $source = realpath($source);
				$source = rtrim($source,'/') . '/';
		        ?>
				ucm.backup.file_list.push({
					path: '/',
					name: '/ (root directory)',
					recurisive: 0,
					status: false
				});
	            <?php foreach (new DirectoryIterator($source) as $fileInfo) {
		        if($fileInfo->isDot() || !$fileInfo->isDir() || $fileInfo->getFilename() == 'temp' || $fileInfo->getFilename() == 'includes' || $fileInfo->getFilename() == '.hg' || $fileInfo->getFilename() == '.git') continue; ?>
					ucm.backup.file_list.push({
						path: '<?php echo $fileInfo->getFilename();?>/',
						name: '<?php echo $fileInfo->getFilename();?>/',
						recurisive: 1,
						status: false
					});
	            <?php } ?>
				ucm.backup.file_list.push({
					path: 'includes/',
					name: 'includes/ (plugin directory)',
					recurisive: 0,
					status: false
				});
                <?php
	            foreach (new DirectoryIterator($source.'includes/') as $fileInfo) {
		        if($fileInfo->isDot() || !$fileInfo->isDir()) continue; ?>
	                ucm.backup.file_list.push({
						path: 'includes/<?php echo $fileInfo->getFilename();?>/',
						name: 'includes/<?php echo $fileInfo->getFilename();?>/',
						recurisive: 1,
						status: false
					});
	            <?php }

	             $result = query( 'SHOW TABLES' );
			        $tables = array();
					while ( $row = mysqli_fetch_row( $result ) ) {
						if(!strlen(_DB_PREFIX) || strpos($row[0],_DB_PREFIX) === 0) {
							$tables[] = $row[0];
						}
					}
			        if(!count($tables)){
				        //_e('Error: could not find any database tables to backup.');
			        }else{
			            foreach($tables as $table){
							?>
			                ucm.backup.database_list.push({
								name: '<?php echo $table;?>',
								status: false
							});
			            <?php
			            }
			        }
	            ?>
				$(function(){
					ucm.backup.lang.pending = '<?php _e('Pending');?>';
					ucm.backup.lang.process = '<?php _e('Processing');?>';
					ucm.backup.lang.success = '<?php _e('Successfully backed up %s items','%s');?>';
					ucm.backup.lang.error = '<?php _e('Error');?>';
					ucm.backup.lang.retry = '<?php _e('Retrying...');?>';
					ucm.backup.backup_url = '<?php echo module_backup::link_external_backup($backup_id);?>';
					ucm.backup.backup_post_data = {backup_file:'<?php echo $backup_file_base;?>'};
					ucm.backup.init();
					ucm.backup.start_backup();
				});
			</script>

			<?php


		}else{

			if(isset($_GET['completed'])){
				// we've just automatically redirected from completing the backup.
				// save this backup date/time in the database so we can use it to generate backup reminders.
				module_config::save_config('backup_time',time());
			}


			$fieldset_data = array(
		        'heading' =>  array(
		            'title' => _l('Backup'),
		            'type' => 'h3',
		        ),
		        'elements' => array(

		        )
		    );
			$fieldset_data['elements'] = array(
				array(
					'message' => _l( 'Important: Please download this backup file and save it in a secure location. Once this backup has been downloaded to your computer please delete it from here.' ),
				),
				array(
					'message' => _l( 'After downloading, please unzip this backup on your computer and confirm all the files and database exist.' ),
				),
				array(
					'title' => 'Created Date',
					'fields' => array(
						print_date( isset( $backup['date_created'] ) ? $backup['date_created'] : time(), true ),
					)
				),
				array(
					'title'  => 'Backup Size',
					'fields' => array(
						function () use ( $backup ) {
							if ( isset( $backup['backup_file'] ) && strlen( $backup['backup_file'] ) && file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip' ) ) {
								echo module_file::format_bytes( filesize( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip' ) ) . ' ' . _l( 'files' );
								echo '<br/> ';
							}
							if ( isset( $backup['backup_file'] ) && strlen( $backup['backup_file'] ) && file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql' ) ) {
								echo module_file::format_bytes( filesize( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql' ) ) . ' ' . _l( 'database' );
								echo '<br/> ';
							}
							if ( isset( $backup['backup_file'] ) && strlen( $backup['backup_file'] ) && file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz' ) ) {
								echo module_file::format_bytes( filesize( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz' ) ) . ' ' . _l( 'database' );
								echo '<br/> ';
							}
						}
					)
				),
				array(
					'title'  => 'Download Backup',
					'fields' => array(
						function () use ( $backup_id, $backup ) {
							if ( !$backup_id ) {
								_e( 'Please click Create Backup button first' );
							} else if ( !$backup['backup_file'] || !strlen( $backup['backup_file'] ) ) {
								_e( 'Error: Backup File Not Found: %s', _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) );
							} else {
								if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql' ) ) {
									echo '<a href="' . _BASE_HREF . _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql">' . _l( 'Download Database SQL Backup (right click save as)' ) . '</a> <br/>';
								}
								if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz' ) ) {
									echo '<a href="' . _BASE_HREF . _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz">' . _l( 'Download Compressed Database SQL Backup (right click save as)' ) . '</a> <br/>';
								}
								if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip' ) ) {
									echo '<a href="' . _BASE_HREF . _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip">' . _l( 'Download Files ZIP Backup (right click save as)' ) . '</a> <br/>';
								}
							}
						}
					)
				)
			);

			?>

			<form action="" method="post">
				<input type="hidden" name="_process" value="save_backup">

			<?php

			module_form::print_form_auth();

		    echo module_form::generate_fieldset($fieldset_data);
		    unset($fieldset_data);

			$form_actions = array(
		        'class' => 'action_bar action_bar_center action_bar_single',
		        'elements' => array(
		            array(
		                'ignore' => !((int)$backup_id && module_backup::can_i('delete','Backups')),
		                'type' => 'delete_button',
		                'name' => 'butt_del',
		                'value' => _l('Delete Backup'),
		            ),
		            array(
		                'type' => 'button',
		                'name' => 'cancel',
		                'value' => _l('Cancel'),
		                'class' => 'submit_button',
		                'onclick' => "window.location.href='".module_backup::link_open(false)."';",
		            ),
		        ),
		    );
		    echo module_form::generate_form_actions($form_actions);

			?>
			</form>

			<?php

		}
	}else{
		$fieldset_data['elements'] = array(
			array(
                'message' => _l('Please check the hosting account has enough storage space for a full backup.'),
            ),
			array(
                'message' => _l('If the backup fails please contact support for assistance.'),
            ),
			array(
                'message' => _l('Please click the button below to start the backup process.'),
            ),
        );
		?>

		<form action="" method="post">
			<input type="hidden" name="_process" value="save_backup">

		<?php

		module_form::print_form_auth();

	    echo module_form::generate_fieldset($fieldset_data);
	    unset($fieldset_data);

		$form_actions = array(
	        'class' => 'action_bar action_bar_center action_bar_single',
	        'elements' => array(
	            array(
		            'ignore' => (int)$backup_id,
	                'type' => 'save_button',
	                'name' => 'butt_save',
	                'id' => 'butt_save',
	                'value' => _l('Create Backup'),
		            'onclick' => "$(this).val('"._l('Please wait...')."');",
	            ),
	            array(
	                'type' => 'button',
	                'name' => 'cancel',
	                'value' => _l('Cancel'),
	                'class' => 'submit_button',
	                'onclick' => "window.location.href='".module_backup::link_open(false)."';",
	            ),
	        ),
	    );
	    echo module_form::generate_form_actions($form_actions);

		?>
		</form>

		<?php
	}



}else {

	$header = array(
		'title'  => _l( 'Backups' ),
		'type'   => 'h2',
		'main'   => true,
		'button' => array(),
	);
	if ( module_backup::can_i( 'create', 'Backups' ) ) {
		$header['button'] = array(
			'url'   => module_backup::link_open( 'new' ),
			'title' => _l( 'Create New Backup' ),
			'type'  => 'add',
		);
	}
	print_heading( $header );

	$backups = module_backup::get_backups();
	
	
		
	$table_manager = module_theme::new_table_manager();
	$columns = array();
	$columns['backup_name'] = array(
	    'title' => 'Backup',
	    'callback' => function($backup){
	        echo '<a href="'.module_backup::link_open($backup['backup_id'],false).'">'._l('View Backup').'</a>';
	    },
	    'cell_class' => 'row_action',
	);
	$columns['backup_size'] = array(
	    'title' => 'Backup Size',
	    'callback' => function($backup){
			if(strlen($backup['backup_file']) && file_exists(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.zip')){
	            echo module_file::format_bytes(filesize(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.zip')) .' '._l('files');
				echo ' ';
	        }
			if(strlen($backup['backup_file']) && file_exists(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.sql')){
	            echo module_file::format_bytes(filesize(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.sql')) .' '._l('database');
				echo ' ';
	        }
			if(strlen($backup['backup_file']) && file_exists(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.sql.gz')){
	            echo module_file::format_bytes(filesize(_BACKUP_BASE_DIR.basename($backup['backup_file']).'.sql.gz')) .' '._l('database');
				echo ' ';
	        }
	    },
	);
	$columns['backup_date'] = array(
	    'title' => 'Date Created',
	    'callback' => function($backup){
			echo _l('%s by %s',print_date($backup['date_created']),module_user::link_open($backup['create_user_id'],true));
	    },
	);
	$table_manager->set_columns($columns);
	$table_manager->set_rows($backups);
	if(module_config::c('backup_time',0) > 0){
		$table_manager->blank_message = _l('The last backup was: %s',print_date(module_config::c('backup_time',0),true));
	}else{
		$table_manager->blank_message = _l('No backups yet. Please create one.');
	}

	$table_manager->pagination = false;
	$table_manager->print_table();

}
<?php


class ucm_upgrade_manager{

	/**
	 * @var array
	 * These are files we don't want to overwrite during the upgrade process.
	 */
	public $held_files = array();

	public function __construct(){

		if(is_file(_UCM_FOLDER.'upgrade_ignore.txt')){
		    foreach(file(_UCM_FOLDER.'upgrade_ignore.txt') as $held_file){
		        $this->held_files[]=trim($held_file);
		    }
		}
	}

	public function check_for_plugin_updates($plugin_name){

	}
	public function install_plugin_update_files($plugin_name){
		$result = array(
			'message' => '',
			'plugin_name' => $plugin_name,
		);
	    $errors = array();
	
		if(!strlen(trim(module_config::c('_installation_code')))){
	        $result['message'] = 'Please enter your license code before doing an upgrade.';
	    }else{
			/*$available_updates = module_config::check_for_upgrades($plugin_name);
		    if($available_updates && isset($available_updates['message']) && strlen(trim($available_updates['message']))>2){
		        $result['message'] = $available_updates['message'];
		    }else{*/
			    $completed_updates = array();
			    // we useto process multiple plugin updates at a time, not any more..
			    $upgrade_plugins = array($plugin_name); // hack so we can 'continue/break' out of loop below.
			    foreach($upgrade_plugins as $plugin_name){
				    $this_update = array(
					    'key' => $plugin_name,
				    );
			        /*$this_update = false;
			        foreach($available_updates['plugins'] as $available_update){
			            if($available_update['key'] == $plugin_name){
			                $this_update = $available_update;
			                break;
			            }
			        }*/
			        if($this_update && strlen(trim($this_update['key']))){
				        $result['message'] .= "Downloading files... <br/>\n";
				        if ( $update = module_config::download_update( $this_update['key'] ) ) {
							foreach($update['plugins'] as $available_update){
			                    if(
			                        $available_update['key'] != $this_update['key']
			                        //(!isset($available_update['linked_key']) && $available_update['key'] != $this_update['key']) ||
			                        //(isset($available_update['linked_key']) && $available_update['linked_key'] != $this_update['key'])
			                    ){
			                        // core update bug fix.
			                        continue;
			                    }
			                    // have we done this yet?
			                    if(isset($completed_updates[$available_update['key']]))continue;
			                    $completed_updates[$available_update['key']] = true;
	
			                    foreach($available_update['folders'] as $file){
				                    if(!is_dir(_UCM_FOLDER.$file)) {
					                    $result['message'] .= "Folder: $file <br/>\n";
					                    $result['message'] .= '<span class="small">';
					                    if ( is_dir( _UCM_FOLDER . $file ) ) {
						                    $result['message'] .= 'this folder exists, nothing will change.';
					                    } else {
						                    // check if writable
						                    if ( mkdir( _UCM_FOLDER . $file, 0777, true ) ) {
							                    $result['message'] .= 'this new folder has been <strong>created</strong>';
						                    } else {
							                    $error    = '<strong>WARNING:</strong> failed to create new folder: ' . $file;
							                    $errors[] = $error;
							                    $result['message'] .= $error;
						                    }
					                    }
					                    $result['message'] .= "</span><br/>";
				                    }
			                    }
								foreach($available_update['files'] as $file){
									$result['message'] .= "File: $file <br/>\n";
				                    $result['message'] .= '<span class="small">';
				                    if(in_array($file,$this->held_files)){
		                                $error = '<strong>Custom:</strong> file not upgraded: '.$file;
		                                $errors[] = $error;
	                                    $result['message'] .= $error;
		                            }else if(!isset($available_update['file_contents'][$file])){
		                                $error = '<strong>WARNING:</strong> failed to get file contents from server: '.$file;
		                                $errors[] = $error;
	                                    $result['message'] .= $error;
		                            }else if(!file_put_contents(_UCM_FOLDER.$file,base64_decode($available_update['file_contents'][$file]))){
		                                $error = '<strong>WARNING:</strong> failed to install the file: '.$file;
		                                $errors[] = $error;
	                                    $result['message'] .= $error;
		                            }else{
	                                    $result['message'] .= 'this file has been <strong>installed</strong> successfully';
		                            }
				                    $result['message'] .= "</span><br/>";
								}
			                }
			                //exit;
			            }else{
				            $error = "failed to download files ($plugin_name):";
				            $errors[] = $error;
			                $result['message'] .=  '<span class="error_text">'.$error.'</span><br/> ';
			            }
			            //$_REQUEST['run_upgrade'] = true; // so we do the DB update again down the bottom.
			        }else{
			            $error = "Failed to start update ($plugin_name):";
			            $errors[] = $error;
				        $result['message'] .= $error.'<br/>';
			        }
			    }
			/*}*/
		}
	
		$result['errors'] = $errors;
		if(!count($errors)) {
			$result['success'] = 1;
		}
		return $result;
	}
	public function complete_plugin_installation($plugin_name){
		global $plugins;
		$result = array(
			'message' => ''
		);
		$new_system_version = module_config::current_version();
	    $fail = false;
		if(isset($plugins[$plugin_name])){
			$result['message'] .= "Processing update: <span style='text-decoration:underline;'>".$plugin_name."</span> - Current Version: ".$plugins[$plugin_name]->get_plugin_version().".... ";
			ob_start();
			if($version = $plugins[$plugin_name]->install_upgrade()){
	            $result['message'] .= '<span class="success_text">all good</span>';
	            $new_system_version = max($version,$new_system_version);
				$plugins[$plugin_name]->init();
	            // lol typo - oh well.
	            $plugins[$plugin_name]->set_insatlled_plugin_version($version);
	        }else{
	            $fail = true;
	            $result['message'] .= '<span class="error_text">failed</span> ';
	        }
			$result['message'] .= ob_get_clean().'<br/>';
			$result['message'] .= '<br/>';
		    if($fail){
		        $result['message'] .= _('Some things failed. Please go back and try again.');
		    }else{
				$result['message'] .= '<strong>'._l('Success! Everything worked.').'</strong>';
		        module_config::set_system_version($new_system_version);
		        module_config::save_config('last_update',time());
		    }
			if(isset($_SESSION['_message']) && count($_SESSION['_message'])){
				$result['message'] .= '<br/>';
				$result['message'] .= implode('<br/>', $_SESSION['_message']);
				unset($_SESSION['_errors']);
			}
			if(isset($_SESSION['_errors']) && count($_SESSION['_errors'])){
				$result['message'] .= '<br/>';
				$result['message'] .= implode('<br/>', $_SESSION['_errors']);
				unset($_SESSION['_errors']);
			}
	    }else if($plugin_name == 'corefiles' || $plugin_name == 'database') {

		}else{
			$fail = true;
		}
		// hack to clear db field cache:
		module_cache::clear('db');
		if(!$fail){
			$result['success'] = 1;
		}
		return $result;
	}

}
<?php


function get_yes_no() {
	$data = array(
		1 => "Yes",
		0 => "No",
	);

	return $data;
}

function friendly_key( $data, $key ) {
	return isset( $data[ $key ] ) ? $data[ $key ] : false;
}

function print_select_box( $data, $id, $cur = '', $class = '', $blank = true, $array_id = false, $allow_new = false ) {
	// todo: call module_form::generate_form_element
	$sel = '<select name="' . $id . '" id="' . $id . '" class="' . $class . '"';
	if ( $allow_new ) {
		$sel .= ' onchange="dynamic_select_box(this);"';

	}
	$sel .= '>';
	if ( $blank ) {
		$foo = ucwords( str_replace( "_", " ", $id ) );
		$sel .= '<option value="">' . ( $blank === true ? _l( ' - Select - ' ) : $blank ) . '</option>';
	}
	$found_selected = false;
	$current_val    = 'Enter new value here';
	foreach ( $data as $key => $val ) {
		if ( is_array( $val ) ) {
			if ( ! $array_id ) {
				if ( isset( $val[ $id ] ) ) {
					$array_id = $id;
				} else {
					$array_id = key( $val );
				}
			}
			$printval = $val[ $array_id ];
		} else {
			$printval = $val;
		}
		if ( strlen( $printval ) == 0 ) {
			continue;
		}
		$sel .= '<option value="' . htmlspecialchars( $key ) . '"';
		// to handle 0 elements:
		if ( $cur !== false && ( $cur != '' ) && $key == $cur ) {
			$current_val    = $printval;
			$sel            .= ' selected';
			$found_selected = true;
		}
		$sel .= '>' . htmlspecialchars( $printval ) . '</option>';
	}
	if ( $cur && ! $found_selected ) {
		$sel .= '<option value="' . $cur . '" selected>' . htmlspecialchars( $cur ) . '</option>';
	}
	if ( $allow_new && get_display_mode() != 'mobile' ) {
		$sel .= '<option value="create_new_item">' . _l( ' - Create New - ' ) . '</option>';
		if ( is_array( $allow_new ) ) {
			$sel               .= '<option value="_manage_items"';
			$allow_new['hash'] = md5( serialize( $allow_new ) . _UCM_SECRET );
			$sel               .= ' data-items="' . htmlspecialchars( json_encode( $allow_new ), ENT_QUOTES, 'UTF-8' ) . '"';
			$sel               .= '>' . _l( ' - Manage Options - ' ) . '</option>';
		}
	}
	$sel .= '</select>';
	if ( $allow_new ) {
		//$sel .= '<input type="text" name="new_'.$id.'" style="display:none;" value="'.$current_val.'">';
	}

	return $sel;
}

function print_multi_select_box( $data, $id, $cur = array(), $blank = true, $array_id = false, $hidden = true ) {
	$width  = '89px';
	$height = '18px';
	ob_start();
	if ( $hidden ) {
		?>
		<!-- <div style="height:<?php echo $height; ?>; width:<?php echo $width; ?>; overflow: hidden; position: absolute; background: #FFFFFF; font-size:0.8em;" onmouseover="$(this).height('auto');$(this).width('auto');" onmouseout="$(this).height('<?php echo $height; ?>');$(this).width('<?php echo $width; ?>');"> -->
		<div onmouseover="$('div',this).show();" onmouseout="$('div',this).hide();">
		<?php
	}
	if ( $blank ) {
		echo( $blank === true ? _l( ' - Select - ' ) : $blank );
		echo "<br>\n";
	}
	if ( $hidden ) {
		echo '<div style="overflow-y: auto; width:220px; height:300px; position: absolute; background: #FFF; font-size:0.9em; display:none;">';
	}
	$x = 0;
	foreach ( $data as $key => $val ) {
		if ( is_array( $val ) ) {
			if ( ! $array_id ) {
				if ( isset( $val[ $id ] ) ) {
					$array_id = $id;
				} else {
					$array_id = key( $val );
				}
			}
			$printval = $val[ $array_id ];
		} else {
			$printval = $val;
		}
		if ( strlen( $printval ) == 0 ) {
			continue;
		}
		?>
		<input type="checkbox" name="<?php echo $id; ?>[]" value="<?php echo $key; ?>"
		       id="multi_<?php echo $x; ?>" <?php echo in_array( $key, $cur ) ? ' checked' : ''; ?>>
		<label for="multi_<?php echo $x; ?>"><?php echo htmlspecialchars( $printval ); ?></label> <br/>
		<?php
		$x ++;
	}
	if ( $hidden ) {
		echo '</div>';
		?>
		</div> &nbsp;
		<?php
	}

	return ob_get_clean();
}

// copied currency over to plugin_core

// copied dollar() over to plugin_core

function input_date( $date, $include_time = false ) {

	$time = false;
	if ( preg_match( '/^\d+$/', $date ) ) {
		$time = $date;
	}

	if (
		! $date ||
		( preg_match( '/[a-z]/i', $date ) && ! preg_match( '/^[\+-]\d/', $date ) )
	) {
		return '';
	}

	if ( $date == '0000-00-00' ) {
		return $date;
	}

	// takes a user input date and returns the mysql YYYY-MM-DD valid format.
	// 1 = DD/MM/YYYY
	// 2 = YYYY/MM/DD
	// 3 = MM/DD/YYYY
	// 4 = DD.MM.YYYY

	// could use sscanf below, but still wanted to run preg_match
	// so used implode(explode( instead... meh
	if ( ! $time ) {
		switch ( _DATE_INPUT ) {
			case 1:
				if ( preg_match( '#^\d?\d([-/])\d?\d\1\d{2,4}(.*)$#', $date, $matches ) ) {
					$time_bits = $matches[2];
					$date      = str_replace( $time_bits, '', $date );
					$date      = implode( "-", array_reverse( explode( $matches[1], $date ) ) );
					$date      .= $time_bits;
					if ( strtotime( $date ) ) {
						$date = date( 'Y-m-d' . ( ( $include_time ) ? ' H:i:s' : '' ), strtotime( $date ) );
						break;
					}
				}
			case 2:
				if ( preg_match( '#^\d{2,4}([-/])\d?\d\1\d?\d(.*)$#', $date, $matches ) ) {
					$time_bits = $matches[2];
					$date      = str_replace( $time_bits, '', $date );
					$date      = implode( "-", explode( $matches[1], $date ) );
					$date      .= $time_bits;
					if ( strtotime( $date ) ) {
						$date = date( 'Y-m-d' . ( ( $include_time ) ? ' H:i:s' : '' ), strtotime( $date ) );
						break;
					}
				}
			case 3:
				if ( preg_match( '#^\d?\d([-/])\d?\d\1\d{2,4}(.*)$#', $date, $matches ) ) {
					$time_bits = $matches[2];
					$date      = str_replace( $time_bits, '', $date );
					$date_bits = explode( $matches[1], $date );
					$date      = $date_bits[2] . '-' . $date_bits[0] . '-' . $date_bits[1];
					$date      .= $time_bits;
					if ( strtotime( $date ) ) {
						$date = date( 'Y-m-d' . ( ( $include_time ) ? ' H:i:s' : '' ), strtotime( $date ) );
						break;
					}
				}
			case 4:
				if ( preg_match( '#^\d?\d([-/\.])\d?\d\1\d{2,4}(.*)$#', $date, $matches ) ) {
					$time_bits = $matches[2];
					$date      = str_replace( $time_bits, '', $date );
					$date      = implode( "-", array_reverse( explode( $matches[1], $date ) ) );
					$date      .= $time_bits;
					if ( strtotime( $date ) ) {
						$date = date( 'Y-m-d' . ( ( $include_time ) ? ' H:i:s' : '' ), strtotime( $date ) );
						break;
					}
				}
			default:
				$date = date( 'Y-m-d' . ( ( $include_time ) ? ' H:i:s' : '' ), strtotime( $date ) );
		}
	}

	if ( $include_time ) {
		// if we're on todays date, and there is no time set, use nows time.
		if ( date( 'Y-m-d', strtotime( $date ) ) == date( 'Y-m-d' ) ) {
			if ( $date == date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d' ) ) ) ) {
				$date = date( 'Y-m-d H:i:s' );
			}
		}
	}

	return $date;
}

function print_date( $date, $include_time = false, $input_format = false ) {
	if ( ! $date || ( preg_match( '/[a-z]/i', $date ) && ! preg_match( '/^[\+-]\d/', $date ) ) ) {
		return '';
	}
	if ( strpos( $date, '0000-00-00' ) !== false ) {
		return '';
	}
	if ( strpos( $date, '1970-01-01' ) !== false ) {
		return '';
	}
	if ( is_numeric( $date ) ) {
		// we have a timestamp, simply spit this out
		$time = $date;
	} else {
		$time = strtotime( input_date( $date, $include_time ) );
	}
	if ( $input_format ) {
		switch ( _DATE_INPUT ) {
			case 1:
				$date = date( "d/m/Y", $time );
				break;
			case 2:
				$date = date( "Y/m/d", $time );
				break;
			case 3:
				$date = date( "m/d/Y", $time );
				break;
			case 4:
				$date = date( "d.m.Y", $time );
				break;
		}
	} else {
		$date = date( _DATE_FORMAT, $time );
	}
	if ( $include_time ) {
		$date .= ' ' . date( module_config::c( 'time_format', 'g:ia' ), $time );
	}

	return $date;
}

function print_error( $errors, $fatal = false ) {
	if ( ! is_array( $errors ) ) {
		$errors = array( $errors );
	}
	echo "Error!";
	print_r( $errors );
	if ( $fatal ) {
		exit;
	}
}


function handle_hook( $hook ) {
	global $plugins;
	// check each plugin if they want to handle this hook.

	module_debug::log( array(
		'title' => 'Running Hook: ' . $hook,
		'data'  => var_export( func_get_args(), true ),
	) );

	$argv = array();
	$tmp  = func_get_args();
	foreach ( $tmp as $key => $value ) {
		$argv[ $key ] = &$tmp[ $key ];
	} // hack for php5.3.2
	$return = array();
	if ( is_array( $plugins ) ) {
		foreach ( $plugins as $plugin_name => &$plugin ) {
			if ( is_callable( array( &$plugin, 'handle_hook' ) ) ) {
				module_debug::log( array(
					'title' => 'Running Hook: ' . $hook,
					'data'  => "Calling Plugin $plugin_name -> handle_hook() with: " . var_export( func_get_args(), true ),
				) );
				$this_return = call_user_func_array( array( &$plugin, 'handle_hook' ), $argv );
				if ( $this_return !== false && $this_return !== null ) {
					$return[] = $this_return;
				}
			}
			//$return[] = $plugin->handle_hook($hook,$calling_module);
		}
	}
	if ( count( $return ) == 0 ) {
		$return = false;
	}

	return $return;
}

global $hooks;
$hooks = array();
function hook_add( $hook_name, $callback ) {
	global $hooks;
	if ( ! isset( $hooks[ $hook_name ] ) ) {
		$hooks[ $hook_name ] = array();
	}
	$hooks[ $hook_name ][] = $callback;
}

function hook_remove( $hook_name, $callback ) {
	global $hooks;
	if ( isset( $hooks[ $hook_name ] ) ) {
		foreach ( $hooks[ $hook_name ] as $key => $existing_callback ) {
			if ( $existing_callback == $callback ) {
				unset( $hooks[ $hook_name ][ $key ] );
			}
		}
	}
}

function hook_handle_callback( $hook ) {
	// bad! look at how wordpress handles hooks.
	// have to pass results along the line to different hooks if there are multiples.
	global $hooks;
	if ( is_array( $hooks ) && isset( $hooks[ $hook ] ) && is_array( $hooks[ $hook ] ) ) {
		$argv = array();
		$tmp  = func_get_args();
		foreach ( $tmp as $key => $value ) {
			$argv[ $key ] = &$tmp[ $key ];
		} // hack for php5.3.2
		$return = array();
		foreach ( $hooks[ $hook ] as $hook_callback ) {
			module_debug::log( array(
				'title' => 'calling hook: ' . $hook,
				'data'  => 'callback: ' . $hook_callback . ' args: ' . var_export( $argv, true ),
			) );
			$this_return = call_user_func_array( $hook_callback, $argv );
			if ( $this_return !== false && $this_return !== null ) {
				module_debug::log( array(
					'title' => 'calling hook: ' . $hook . ' completed!',
					'data'  => 'with results! ',
				) );
				$return[] = $this_return;
			}
		}
		if ( count( $return ) == 0 ) {
			$return = false;
		}

		return $return;
	} else {
		return false;
	}
}


function process_alert( $check_date, $item, $alert_days_in_future = false ) {
	if ( $alert_days_in_future === false ) {
		$alert_days_in_future = module_config::c( 'alert_days_in_future', 5 );
	}
	$date = input_date( $check_date, true );
	if ( $check_date != 's2009-07-12' ) {
		//echo $date;
	}
	if ( ! strtotime( $date ) ) {
		$date = false;
	}
	/*if(preg_match('#^\d?\d/\d?\d/\d{2,4}$#',$check_date)){
		$date = implode("-",array_reverse(explode("/",$check_date)));
	}else if(preg_match('#^\d{2,4}-\d?\d-\d?\d$#',$check_date)){
		$date = $check_date;
	}*/

	$alert_res = false;

	if ( $date ) {
		// we have a date
		$secs        = date( "U" ) - date( "U", strtotime( $date ) );
		$days        = $secs / 86400;
		$alert_field = false;
		$warning     = false;
		if ( $secs > 0 ) {
			$days = floor( $days );
			if ( $days == 0 ) {
				$alert_field = " " . _l( 'today!' );
				$warning     = true;
			} else {
				$alert_field = " " . _l( '%s days ago', $days );
				$warning     = true;
			}
		} else {
			$days = abs( $days );
			$days = ceil( $days );
			if ( $days == 0 ) {
				$alert_field = " " . _l( 'today!' );
				$warning     = true;
			} else if ( $days < $alert_days_in_future ) {
				$alert_field = " " . _l( 'in %s days', $days );
			}
		}

		if ( $alert_field ) {
			$alert_res = array(
				"warning" => $warning,
				"alert"   => _l( $item ) . $alert_field,
				"item"    => _l( $item ),
				"days"    => $alert_field,
				"date"    => $date,
			);
		}
	}

	return $alert_res;
}

function set_message( $message ) {
	if ( ! isset( $_SESSION['_message'] ) ) {
		$_SESSION['_message'] = array();
	}
	$_SESSION['_message'][] = _l( $message );
}

function set_error( $message ) {
	if ( ! isset( $_SESSION['_errors'] ) ) {
		$_SESSION['_errors'] = array();
	}
	foreach ( $_SESSION['_errors'] as $existing_error ) {
		if ( $existing_error == _l( $message ) ) {
			return false;
		}
	}
	$_SESSION['_errors'][] = _l( $message );

	return true;
}

function print_header_message() {
	$return = false;
	if ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) {
		?>
		<div class="ui-widget" style="padding-top:10px;">
			<div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em;">
				<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
					<?php
					$x = 1;
					foreach ( $_SESSION['_message'] as $msg ) {
						if ( count( $_SESSION['_message'] ) > 1 ) {
							echo "<strong>#$x</strong> ";
							$x ++;
						}
						echo nl2br( ( $msg ) ) . "<br>";
					}
					?>
				</p>
			</div>
		</div>
		<?php
		$return = true;
	}
	if ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) {
		$x = 1;
		foreach ( $_SESSION['_errors'] as $msg ) {
			?>
			<div class="ui-widget" style="padding-top:10px;">
				<div class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
					<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
						<?php echo nl2br( $msg ) . "<br>"; ?>
					</p>
				</div>
			</div>
			<?php
		}
		$return = true;
	}
	$_SESSION['_message'] = array();
	$_SESSION['_errors']  = array();

	return $return;
}


function send_error( $message ) {
	//echo $message;exit;
	mail( _ERROR_EMAIL, "Admin System Notification (" . date( "Y-m-d H:i:s" ) . ")", $message . "\n\n" . var_export( $_REQUEST, true ) . "\n\n" . var_export( $_SERVER, true ) );
}


function _hr( $text ) {
	ob_start();
	$argv = func_get_args();
	call_user_func_array( '_h', $argv );

	return ob_get_clean();
}

function _h( $text ) {
	// is help enabled?
	if ( ! module_config::c( 'show_help', 1 ) || get_display_mode() == 'mobile' ) {
		return '';
	}
	$help_id = md5( $text );
	$argv    = func_get_args();
	?> <a href="#"
	      data-ajax-modal='{"type":"inline","content":"#help_<?php echo $help_id; ?>","buttons":"close","title":"<?php _e( 'Help' ); ?>"}'
	      class="" style="display:inline-block;"><i class="fa fa-question"></i></a>
	<div id="help_<?php echo $help_id; ?>" style="display:none;">
		<?php print call_user_func_array( '_l', $argv ); ?>
	</div>
	<?php
}

function _e( $text ) {
	$argv = func_get_args();
	print call_user_func_array( '_l', $argv );
}

// this is the makings of a labelling system.
function _l( $text ) {
	if ( is_callable( 'module_language::l' ) ) {
		$argv = func_get_args();

		return call_user_func_array( 'module_language::l', $argv );
	}
	// read in from the global label array
	//return 'L';
	global $labels;
	$argv = func_get_args();
	// see if the first one is a lang label
	if ( isset( $labels[ $text ] ) && trim( $labels[ $text ] ) ) {
		$argv[0] = $labels[ $text ];
	}
	// use this for building up the language array.
	// visit index.php?dump_lang=true to get a csv file of language vars.
	if ( _DEBUG_MODE ) {
		$foo       = debug_backtrace();
		$last_file = false;
		while ( $last = array_shift( $foo ) ) {
			if ( $last && isset( $last['file'] ) ) {
				$last_file = $last['file'];
				break;
			}
		}
		$last_file = str_replace( _UCM_FOLDER, '', $last_file );
		if ( ! $last_file ) {
			print_r( $foo );
			exit;
		}
		$_SESSION['ll'][ $last_file ][ $text ] = true;
	}
	$result = call_user_func_array( 'sprintf', $argv );
	if ( isset( $_SESSION['_edit_labels'] ) ) {
		// this idea didn't really work because we use
		// labels in hidden fields and in javascript.
		// this worked fine when the labels were just on the page, but not otherwise.
		return '{[{' . $result . '}]}';
		$edit_result = '<span style="" onclick="return false;">';
		$edit_result .= '<input type="text" style="font-size:10px; border:1px solid #FF0000;" size="' . strlen( $result ) . '" value="' . $result . '" onclick="return false;">';
		$edit_result .= '</span>';

		return $edit_result;
	} else {
		return $result;
	}

}

function forum_text( $original_text, $htmlspecialchars = true ) {
	$text = ( $htmlspecialchars ) ? htmlspecialchars( $original_text ) : $original_text;

	// convert links
	$text = " " . $text;
	//$text = preg_replace('#(((f|ht){1}tps?://)[-a-zA-Z0-9@:;%\_\+\.~\#\?&//=\[\]]+)#i', '<a href="\\1" target=_blank>\\1</a>', $text);
	//$text = preg_replace('#([[:space:]()[{}])(www.[-a-zA-Z0-9@:;%\_\+.~\#\?&//=]+)#i', '\\1<a href="http://\\2" target=_blank>\\2</a>', $text);
	//$text = preg_replace('#([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})#i', '<a href="mailto:\\1" target=_blank>\\1</a>', $text);

	//$text = preg_replace('#(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#i','<a href="\\1" target="_blank">\\1</a>',$text);

	$convertedtext = preg_replace( '#&lt;((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))&gt;#i', '\\1', $text );
	if ( strlen( $convertedtext ) ) {
		$text = $convertedtext;
	}
	/*if(preg_match_all('#((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#i',$text,$matches)){
        echo '<pre>';print_r($matches);echo '</pre>';
        foreach($matches[0] as $match){
            $match2 = $match;
            if(!preg_match('#^https?:#',$match2) && !preg_match('#^ftps?:#',$match2)){
                $match2 = 'http://'.$match2;
            }
            $text = preg_replace('#'.preg_quote($match,'#').'#','<a href="'.$match2.'" target="_blank">'.$match.'</a>',$text,1);
        }
    }*/
	$text = preg_replace( '#((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#i', '<a href="\\1" target="_blank">\\1</a>', $text );

	// replace any links that don't start with http
	if ( preg_match_all( '#<a href="([^"]+)"#', $text, $matches ) ) {
		//echo '<pre>';print_r($matches);echo '</pre>';
		foreach ( $matches[1] as $key => $match ) {
			if ( ! preg_match( '#^https?:#', $match ) && ! preg_match( '#^ftps?:#', $match ) ) {
				$match = 'http://' . $match;
			}
			$text = preg_replace( '#' . preg_quote( $matches[0][ $key ], '#' ) . '#', '<a href="' . $match . '"', $text, 1 );
		}
	}
	$text = ltrim( $text );
	// putin some word breaks on long links (todo: and long strings as well)
	if ( preg_match_all( '#(<a[^>]*>)([^<]+)</a>#imsU', $text, $matches ) ) {
		foreach ( $matches[2] as $key => $match ) {
			$new_url_bit = preg_replace( "#([^\s]{50})#", "$1<wbr>", $match );//wordwrap($match,50,'<wbr>',true);
			$text        = preg_replace( '#' . preg_quote( $matches[1][ $key ], '#' ) . preg_quote( $match, '#' ) . '#', $matches[1][ $key ] . $new_url_bit, $text, 1 );
		}
	}

	$print_text = '';
	$lines      = explode( "\n", $text );
	foreach ( $lines as $line_number => $line ) {
		$line = rtrim( $line );
		$line = preg_replace( "/\t/", "&nbsp;&nbsp;&nbsp;", $line );
		if ( preg_match( '/^(\s+)/', $line, $matches ) ) {
			$line = str_repeat( "&nbsp;", strlen( $matches[1] ) ) . ltrim( $line );
		}
		$print_text .= $line;
		if ( ( $line_number + 1 ) < count( $lines ) ) {
			$print_text .= "<br />\n";
		}
	}

	return $print_text;
}


function process_pagination( $rows, $per_page = 20, $page_number = 0, $table_id = 'table' ) {
	$data          = array();
	$data['rows']  = array();
	$data['links'] = '';
	if ( $per_page !== false && $per_page <= 0 ) {
		$per_page = 20;
	}

	hook_handle_callback( 'pagination_hook_init' );

	if ( isset( $GLOBALS['pagination_group_hack'] ) ) {
		module_group::run_pagination_hook( $rows );
	}
	if ( isset( $GLOBALS['pagination_import_export_hack'] ) ) {
		module_import_export::run_pagination_hook( $rows );
	}
	if ( class_exists( 'module_table_sort', false ) ) {
		module_table_sort::run_pagination_hook( $rows, $per_page );
	}

	$db_resource = false;
	if ( $rows instanceof mysqli_result ) {
		// have the db handle for the sql query
		$db_resource = $rows;
		unset( $rows );
		$total = mysqli_num_rows( $db_resource );

	} else if ( is_array( $rows ) ) {
		// we have the rows in an array.
		$total = count( $rows );
	} else {
		print_header_message();
		echo 'Pagination failed. Please try going to Settings > Update and click the Manual Update button. If that does not fix this error please report this bug.';
		exit;
	}


	// pagination hooks
	ob_start();
	if ( $total > 0 ) {
		// group hack addition
		hook_handle_callback( 'pagination_hook_display' );
		if ( isset( $GLOBALS['pagination_group_hack'] ) && module_group::groups_enabled() ) {
			module_group::display_pagination_hook();
		}
		if ( get_display_mode() != 'mobile' ) {
			// export hack addition
			if ( isset( $GLOBALS['pagination_import_export_hack'] ) ) {
				module_import_export::display_pagination_hook();
			}
			if ( class_exists( 'module_table_sort', false ) ) {
				module_table_sort::display_pagination_hook( $per_page );
			}
		}
	}
	$pagination_hooks = ob_get_clean();

	// default summary/links content
	ob_start();
	echo '<div class="pagination_summary"><p>';
	if ( $total > 0 ) {
		_e( 'Showing records %s to %s of %s', ( ( $page_number * $per_page ) + 1 ), $total, $total );
		echo $pagination_hooks;
	} else {
		_e( 'No results found' );
	}
	echo '</p></div>';
	$data['summary'] = ob_get_clean();
	ob_start();
	echo '<div class="pagination_links">';
	//echo "\n<p>";
	echo _l( 'Page %s of %s', 1, 1 );
	//echo '</p>';
	echo '</div>';
	$data['links']        = ob_get_clean();
	$data['page_numbers'] = 1;
	if ( $per_page === false || $total <= $per_page ) {

		if ( $db_resource ) {
			$rows = array();
			//if($per_page !== false && $total<=$per_page){
			// pull out all records.
			while ( $row = mysqli_fetch_assoc( $db_resource ) ) {
				$rows[] = $row;
			}
			if ( mysqli_num_rows( $db_resource ) > 0 ) {
				mysqli_data_seek( $db_resource, 0 );
			}
			//}
		}

		$data['rows'] = $rows;
	} else {

		if ( isset( $_REQUEST[ 'pg' . $table_id ] ) ) {
			$page_number = $_REQUEST[ 'pg' . $table_id ];
		}
		if ( $table_id && $table_id != 'table' && $total > $per_page ) {
			// we remember the last page number we were on.
			if ( ! isset( $_SESSION['_table_page_num'] ) ) {
				$_SESSION['_table_page_num'] = array();
			}
			if ( ! isset( $_SESSION['_table_page_num'][ $table_id ] ) ) {
				$_SESSION['_table_page_num'][ $table_id ] = array(
					'total_count' => 0,
					'page_number' => 0,
				);
			}
			$_SESSION['_table_page_num'][ $table_id ]['total_count'] = $total;

			if ( isset( $_REQUEST[ 'pg' . $table_id ] ) ) {
				$page_number = $_REQUEST[ 'pg' . $table_id ];
			} else if ( $_SESSION['_table_page_num'][ $table_id ]['total_count'] == $total ) {
				$page_number = $_SESSION['_table_page_num'][ $table_id ]['page_number'];
			}
			$_SESSION['_table_page_num'][ $table_id ]['page_number'] = $page_number;
			//echo $table_id.' '.$total . ' '.$per_page.' '.$page_number; print_r($_SESSION['_table_page_num']);
		}
		$page_number = min( ceil( $total / $per_page ) - 1, $page_number );


		// slice up the result into the number of rows requested.
		if ( $db_resource ) {
			// do the the mysql way:
			mysqli_data_seek( $db_resource, ( $page_number * $per_page ) );
			$x = 0;
			while ( $x < $per_page ) {
				$row_data = mysqli_fetch_assoc( $db_resource );
				if ( $row_data ) {
					$data['rows'] [] = $row_data;
				}
				$x ++;
			}
			unset( $row_data );
		} else {
			// the old array way.
			$data['rows'] = array_slice( $rows, ( $page_number * $per_page ), $per_page );
		}
		$data['summary'] = '';
		$data['links']   = '';
		$request_uri     = preg_replace( '/[&?]pg' . preg_quote( $table_id ) . '=\d+/', '', $_SERVER['REQUEST_URI'] );
		$request_uri     .= ( preg_match( '/\?/', $request_uri ) ) ? '&' : '?';
		$request_uri     = htmlspecialchars( $request_uri );
		if ( count( $data['rows'] ) ) {

			$page_count = ceil( $total / $per_page );
			// group into ranges with cute little .... around the numbers if there's too many.
			$rangestart = max( 0, $page_number - 5 );
			$rangeend   = min( $page_count - 1, $page_number + 5 );

			ob_start();
			echo '<div class="pagination_summary">';
			echo '<p>';
			_e( 'Showing records %s to %s of %s', ( ( $page_number * $per_page ) + 1 ), ( ( $page_number * $per_page ) + count( $data['rows'] ) ), $total );
			//echo 'Showing records ' . (($page_number*$per_page)+1) . ' to ' . (($page_number*$per_page)+count($data['rows'])) .' of ' . $total . '</p>';
			echo $pagination_hooks;
			echo '</p>';
			echo '</div>';
			$data['summary'] = ob_get_clean();
			ob_start();
			echo '<div class="pagination_links">';
			//echo "\n<p>";
			echo '<span>';
			if ( $page_number > 0 ) { ?>
				<a
					href="<?php echo $request_uri; ?>pg<?php echo $table_id; ?>=<?php echo $page_number - 1; ?>#t_<?php echo $table_id; ?>"
					rel="<?php echo $page_number - 1; ?>"><?php _e( '&laquo; Prev' ); ?></a> |
			<?php } else { ?>
				<?php _e( '&laquo; Prev' ); ?> |
			<?php }
			echo '</span>';
			if ( $rangestart > 0 ) {
				?> <span><a href="<?= $request_uri; ?>pg<?php echo $table_id; ?>=0#t_<?php echo $table_id; ?>" rel="0" class="">1</a></span> <?php
				if ( $rangestart > 1 ) {
					echo ' ... ';
				}
			}
			for ( $x = $rangestart; $x <= $rangeend; $x ++ ) {
				if ( $x == $page_number ) {
					?>
					<span><a href="<?= $request_uri; ?>pg<?php echo $table_id; ?>=<?= $x; ?>#t_<?php echo $table_id; ?>"
					         rel="<?= $x; ?>" class="current"><?= ( $x + 1 ); ?></a></span>
					<?php
				} else {
					?>
					<span><a href="<?= $request_uri; ?>pg<?php echo $table_id; ?>=<?= $x; ?>#t_<?php echo $table_id; ?>"
					         rel="<?= $x; ?>" class=""><?= ( $x + 1 ); ?></a></span>
					<?php
				}
			}
			if ( $rangeend < ( $page_count - 1 ) ) {
				if ( $rangeend < ( $page_count - 2 ) ) {
					echo ' ... ';
				}
				?> <span><a
						href="<?= $request_uri; ?>pg<?php echo $table_id; ?>=<?= ( $page_count - 1 ); ?>#t_<?php echo $table_id; ?>"
						rel="<?= ( $page_count - 1 ); ?>" class=""><?= ( $page_count ); ?></a></span> <?php
			}

			if ( $page_number < ( $page_count - 1 ) ) { ?>
				| <span><a
						href="<?php echo $request_uri; ?>pg<?php echo $table_id; ?>=<?php echo $page_number + 1; ?>#t_<?php echo $table_id; ?>"
						rel="<?php echo $page_number + 1; ?>"><?php _e( 'Next &raquo;' ); ?></a></span>
			<?php } else { ?>
				| <span><?php _e( 'Next &raquo;' ); ?></span>
			<?php }
			//echo '</p>';
			echo '</div>';
			?>
			<script type="text/javascript">
          $(function () {
              $('.pagination_links a').each(function () {
                  // make the links post the search bar on pagination.
                  $(this).click(function () {
                      // see if there's a search bar to post.
                      var search_form = false;
                      search_form = $('.search_form')[0];
                      $('.search_bar').each(function () {
                          var form = $(this).parents('form');
                          if (typeof form != 'undefined') {
                              search_form = form;
                          }
                      });
                      if (typeof search_form == 'object') {
                          $(search_form).append('<input type="hidden" name="pg<?php echo $table_id;?>" value="' + $(this).attr('rel') + '">');
                          search_form = search_form[0];
                          if (typeof search_form.submit == 'function') {
                              search_form.submit();
                          } else {
                              $('[name=submit]', search_form).click();
                          }
                          return false;
                      }
                  });
              });
          });
			</script>
			<?php
			$data['links'] = ob_get_clean();

			$data['page_numbers'] = $page_count;
		}
	}

	return $data;
}

function print_heading( $options ) {
	// we've moved this function to the theme module.
	// all future updates to this function will be done there, not in this main file.
	if ( is_callable( 'module_theme::print_heading' ) ) {
		module_theme::print_heading( $options );

		return;
	}
	if ( ! is_array( $options ) ) {
		$options = array(
			'type'  => 'h2',
			'title' => $options,
		);
	}
	$buttons = array();
	if ( isset( $options['button'] ) && is_array( $options['button'] ) && count( $options['button'] ) ) {
		$buttons = $options['button'];
		if ( isset( $buttons['url'] ) ) {
			$buttons = array( $buttons );
		}
	}
	?>
	<<?php echo $options['type']; ?>>
	<?php foreach ( $buttons as $button ) { ?>
		<span class="button">
			<a href="<?php echo $button['url']; ?>" class="uibutton"<?php
			if ( isset( $button['id'] ) ) {
				echo ' id="' . $button['id'] . '"';
			}
			if ( isset( $button['onclick'] ) ) {
				echo ' onclick="' . $button['onclick'] . '"';
			}
			if ( isset( $button['ajax-modal'] ) ) {
				echo " data-ajax-modal='" . json_encode( $button['ajax-modal'] ) . "'";
			}
			?>>
                <?php if ( isset( $button['type'] ) && $button['type'] == 'add' ) { ?> <img
	                src="<?php echo _BASE_HREF; ?>images/add.png" width="10" height="10" alt="add" border="0"/> <?php } ?>
				<span><?php echo _l( $button['title'] ); ?></span>
			</a>
		</span>
	<?php } ?>
	<?php if ( isset( $options['help'] ) ) { ?>
		<span class="button">
                <?php _h( $options['help'] ); ?>
            </span>
	<?php } ?>
	<span class="title">
			<?php echo _l( $options['title'] ); ?>
		</span>
	</<?php echo $options['type']; ?>>
	<?php
}


function redirect_browser( $url, $hard = false ) {
	hook_finish();
	$original_url = $url;
	if ( $hard ) {
		header( 'HTTP/1.1 301 Moved Permanently' );
	}
	if ( ! preg_match( '/^https?:/', $url ) && $url[0] != "/" && $url[0] != "?" ) {
		$url = _BASE_HREF . $url;
	}
	if ( false && _DEBUG_MODE ) {
		module_debug::$show_debug = true;
		module_debug::log( array(
			'title' => 'Redirecting',
			'file'  => 'includes/functions.php',
			'data'  => "to '$original_url'" . ( $url != $original_url ? " (converted to: $url)" : '' ) .
			           "<br/><br/>Please <a href='$url'>Click Here</a> to perform the redirect.",
		) );
		module_debug::print_heading();
	} else {

		/*$url .= (strpos($url,'?')===false ? '?' : '&') . "redirect".time();
        ?>
        <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($url);?>">
        <script type="text/javascript">window.location.href='<?php echo $url;?>';</script>
        Redirecting...
        <?php*/
		header( "Location: " . $url );
	}
	exit;
}

/**
 * Take a full link <a href="adsf">afsdf</a>
 * and turn it into a popup link.
 *
 * @param       $link
 * @param array $options
 *
 * @return string
 */
function popup_link( $link, $options = array() ) {
	$hash = substr( md5( $link . mt_rand( 3, 8 ) ), 4, 17 );
	module_debug::log( array(
		'title' => 'PopUp Link',
		'file'  => 'includes/functions.php',
		'data'  => "Converting $link into a popup link",
	) );
	$width  = ( isset( $options['width'] ) ) ? $options['width'] : 400;
	$height = ( isset( $options['height'] ) ) ? $options['height'] : 300;
	preg_match( '#href="([^"]*)"#', $link, $matches );
	$url = $matches[1];
	if ( ! preg_match( '/display_mode/', $url ) ) {
		$url .= ( strpos( $url, '?' ) ? '&' : '?' ) . 'display_mode=ajax';
	}
	//
	ob_start();
	if ( isset( $options['force'] ) && $options['force'] ) {
		$link = preg_replace( '#<a href#', '<a data-ajax-modal=\'{"buttons":"close","title":"' . ( isset( $options['title'] ) ? htmlspecialchars( $options['title'] ) : '' ) . '"}\' href', $link );
		echo $link;
	} else {
		echo $link;
		?>
		<a href="<?php echo $url; ?>"
		   data-ajax-modal='{"buttons":"close","title":"<?php echo( isset( $options['title'] ) ? htmlspecialchars( $options['title'] ) : '' ); ?>"}'>(<?php _e( 'popup' ); ?>
			)</a>
	<?php }

	return ob_get_clean();
}

function part_number( $number ) {
	return '#' . $number;
}


function ordinal( $n ) {
	//$l=substr($i,-1);return$i.($l>3||$l==0?'th':($l==3?'rd':($l==2?'nd':'st')));
	$n_last = $n % 100;
	if ( ( $n_last > 10 && $n_last < 14 ) || $n == 0 ) {
		return "{$n}th";
	}
	switch ( substr( $n, - 1 ) ) {
		case '1':
			return "{$n}st";
		case '2':
			return "{$n}nd";
		case '3':
			return "{$n}rd";
		default:
			return "{$n}th";
	}
}

function idnumber( $number ) {
	return str_pad( $number, 7, '0', STR_PAD_LEFT );
}


function hook_finish( $add = false ) {
	static $hooks = array();
	// run any hooks that have been registered for when execution has finished.
	if ( $add !== false ) {
		$hooks[ md5( serialize( $add ) ) ] = $add;
	} else {
		global $plugins;
		// running them
		foreach ( $hooks as $hook ) {
			call_user_func_array( array( $plugins[ $hook['plugin'] ], $hook['method'] ), $hook['args'] );
		}
	}
}


function is_installed() {
	if ( defined( '_DB_NAME' ) && _DB_NAME != '' ) {
		// check for config table.
		if ( db_connect() === false ) {
			return false; // db connection failed.
		}

		return module_base::db_table_exists( 'config' );
	}

	return false; //not installed
	/*if(!defined('_UCM_INSTALLED') || !_UCM_INSTALLED){
        return false;
    }else{
        return true;
    }*/
}

function h( $e ) {
	return htmlspecialchars( $e );
}


function getcred() {
	return module_security::getcred();
}

/*function getlevel(){
	return module_security::getlevel();
}*/

function _shl( $string, $highlight ) {
	$string    = htmlspecialchars( $string );
	$highlight = trim( $highlight );
	if ( ! $highlight ) {
		return $string;
	}

	return preg_replace( '/' . preg_quote( $highlight, '/' ) . '/i', '<span style="background-color:#FFFF66">$0</span>', $string );
}

function file_exists_ip( $filename ) {
	if ( function_exists( "get_include_path" ) ) {
		$include_path = get_include_path();
	} elseif ( false !== ( $ip = ini_get( "include_path" ) ) ) {
		$include_path = $ip;
	} else {
		return false;
	}

	if ( false !== strpos( $include_path, PATH_SEPARATOR ) ) {
		if ( false !== ( $temp = explode( PATH_SEPARATOR, $include_path ) ) && count( $temp ) > 0 ) {
			for ( $n = 0; $n < count( $temp ); $n ++ ) {
				if ( false !== @file_exists( $temp[ $n ] . $filename ) ) {
					return true;
				}
			}

			return false;
		} else {
			return false;
		}
	} elseif ( ! empty( $include_path ) ) {
		if ( false !== @file_exists( $include_path ) ) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function frndlyfilesize( $filesize ) {

	if ( is_numeric( $filesize ) ) {
		$decr   = 1024;
		$step   = 0;
		$prefix = array( 'Byte', 'KB', 'MB', 'GB', 'TB', 'PB' );

		while ( ( $filesize / $decr ) > 0.9 ) {
			$filesize = $filesize / $decr;
			$step ++;
		}

		return round( $filesize, 2 ) . ' ' . $prefix[ $step ];
	} else {
		return '0';
	}
}

function get_display_mode() {

	if ( isset( $_REQUEST['display_mode'] ) && $_REQUEST['display_mode'] != 'iframe' && $_REQUEST['display_mode'] != 'ajax' ) {
		$_SESSION['display_mode'] = $_REQUEST['display_mode'];
	}
	if ( isset( $_REQUEST['display_mode'] ) ) {
		return $_REQUEST['display_mode'];
	}
	if ( isset( $_SESSION['display_mode'] ) && $_SESSION['display_mode'] ) {
		return $_SESSION['display_mode'];
	}

	if ( class_exists( 'module_mobile', false ) ) {
		if ( module_mobile::is_mobile_browser() ) {
			return 'mobile';
		}
	}

	return 'normal';

}

function getFilesFromDir( $dir ) {

	$files = array();
	if ( $handle = opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file != "." && $file != ".." ) {
				if ( is_dir( $dir . '/' . $file ) ) {
					$dir2    = $dir . '/' . $file;
					$files[] = getFilesFromDir( $dir2 );
				} else {
					$files[] = str_replace( '//', '/', $dir . '/' . $file );
				}
			}
		}
		closedir( $handle );
	}

	return array_flat( $files );
}

function array_flat( $array ) {

	$tmp = array();

	foreach ( $array as $a ) {
		if ( is_array( $a ) ) {
			$tmp = array_merge( $tmp, array_flat( $a ) );
		} else {
			$tmp[] = $a;
		}
	}

	return $tmp;
}

function dtbaker_mime_type( $file_name, $file_path = false ) {
	if ( function_exists( 'finfo_open' ) && $file_path ) {
		if ( ! defined( 'FILEINFO_MIME_TYPE' ) ) {
			define( 'FILEINFO_MIME_TYPE', 16 );
		}
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $file_path );
	} else {
		if ( ! function_exists( 'mime_content_type' ) ) {

			function mime_content_type( $filename ) {

				$mime_types = array(

					'txt'  => 'text/plain',
					'htm'  => 'text/html',
					'html' => 'text/html',
					'php'  => 'text/html',
					'css'  => 'text/css',
					'js'   => 'application/javascript',
					'json' => 'application/json',
					'xml'  => 'application/xml',
					'swf'  => 'application/x-shockwave-flash',
					'flv'  => 'video/x-flv',

					// images
					'png'  => 'image/png',
					'jpe'  => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'jpg'  => 'image/jpeg',
					'gif'  => 'image/gif',
					'bmp'  => 'image/bmp',
					'ico'  => 'image/vnd.microsoft.icon',
					'tiff' => 'image/tiff',
					'tif'  => 'image/tiff',
					'svg'  => 'image/svg+xml',
					'svgz' => 'image/svg+xml',

					// archives
					'zip'  => 'application/zip',
					'rar'  => 'application/x-rar-compressed',
					'exe'  => 'application/x-msdownload',
					'msi'  => 'application/x-msdownload',
					'cab'  => 'application/vnd.ms-cab-compressed',

					// audio/video
					'mp3'  => 'audio/mpeg',
					'qt'   => 'video/quicktime',
					'mov'  => 'video/quicktime',

					// adobe
					'pdf'  => 'application/pdf',
					'psd'  => 'image/vnd.adobe.photoshop',
					'ai'   => 'application/postscript',
					'eps'  => 'application/postscript',
					'ps'   => 'application/postscript',

					// ms office
					'doc'  => 'application/msword',
					'rtf'  => 'application/rtf',
					'xls'  => 'application/vnd.ms-excel',
					'ppt'  => 'application/vnd.ms-powerpoint',

					// open office
					'odt'  => 'application/vnd.oasis.opendocument.text',
					'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
				);

				//$ext = strtolower(array_pop(explode('.',$filename))); fix from #005213
				$value = explode( ".", $filename );
				$ext   = strtolower( array_pop( $value ) );
				if ( array_key_exists( $ext, $mime_types ) ) {
					return $mime_types[ $ext ];
				} elseif ( function_exists( 'finfo_open' ) ) {
					$finfo    = finfo_open( FILEINFO_MIME );
					$mimetype = finfo_file( $finfo, $filename );
					finfo_close( $finfo );

					return $mimetype;
				} else {
					return 'application/octet-stream';
				}
			}
		}
		$mime = mime_content_type( $file_name );
	}

	return $mime;
}

@include_once( 'old.php' );
@include_once( 'pro.php' );
@include_once( 'dev.php' );

<?php


function full_link( $link, $secure = false ) {
	//return 'http' . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS']!='off')?'s':'').'://' . $_SERVER['HTTP_HOST'].ltrim($link,'/');
	$link = ltrim( $link, '/' );

	if ( ! _REWRITE_LINKS ) {
		// convert rewritten links into normal links.
		$base_url = _BASE_HREF; //module_config::c('system_base_dir');
		if ( ! $base_url ) {
			$base_url = '/';
		}
		$url_part = preg_replace( '#^' . preg_quote( $base_url, '#' ) . '#i', '', $link );
		$url_part = preg_replace( '#\?.*$#', '', $url_part );
		//echo $url_part."<br>";
		if ( $url_part ) {
			$new_url_part  = '';
			$parts         = explode( "/", $url_part );
			$module_number = 0;
			//print_r($parts);echo "<br>";
			$doing_external = false;
			foreach ( $parts as $part ) {
				if ( $part == 'index.php' ) {
					continue;
				}
				if ( $part == 'external' ) {
					$doing_external = true;
					continue;
				}
				$m = explode( ".", $part );
				//print_r($m);echo "<br>";
				if ( count( $m ) == 2 ) {
					if ( $doing_external ) {
						$new_url_part .= '' . $m[0] . '=' . $m[1] . '&';
					} else if ( strlen( $m[1] ) > 3 ) {
						$new_url_part .= 'm[' . $module_number . ']=' . $m[0] . '&';
						$new_url_part .= 'p[' . $module_number . ']=' . $m[1] . '&';
						$module_number ++;
					}
				}
			}
			if ( strlen( $new_url_part ) ) {
				if ( $doing_external ) {
					$new_url_part = 'ext.php?' . $new_url_part;
				} else {
					$new_url_part = '?' . $new_url_part;
				}
				if ( preg_match( '#\?(.*)$#', $link, $matches ) ) {
					$new_url_part .= '&' . $matches[1];
				}
				//echo $new_url_part."<br>";
				$link = $new_url_part;
			}
		}
	}
	//$url = module_config::c('system_base_href') .module_config::c('system_base_dir') . $link;
	$url = _UCM_HOST . _BASE_HREF . $link;
	if ( $secure ) {
		$url = preg_replace( '#^http:#', 'https:', $url );
	}

	// hack for ssl
	if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS'] != 'off' ) {
		$url = preg_replace( '#^https?#', 'https', $url );
	}

	// hack for iframe
	if ( get_display_mode() == 'iframe' && ! preg_match( '#display_mode#', $url ) ) {
		$url .= ( strpos( $url, '?' ) === false ? '?' : '&' ) . 'display_mode=iframe';;
	}

	return $url;
}


function link_generate( $link_options ) {
	$link      = $link_text = '';
	$full_link = false;
	$args      = array();
	$m         = 0;
	if ( ! _REWRITE_LINKS ) {
		$link .= ( ( strpos( $link, '?' ) !== false ) ? '&' : '?' );
	}
	if ( get_display_mode() == 'iframe' ) {
		$args['display_mode'] = 'iframe';
	}
	foreach ( $link_options as $link_option ) {

		if ( ! is_array( $link_option ) ) {
			continue;
		}

		if ( _REWRITE_LINKS ) {
			$link .= '/';
			$link .= ( isset( $link_option['module'] ) ) ? $link_option['module'] : '';
			$link .= '.';
			$link .= ( isset( $link_option['page'] ) ) ? $link_option['page'] : '';
		} else {
			$link .= 'm[' . $m . ']=';
			$link .= ( isset( $link_option['module'] ) ) ? $link_option['module'] : '';
			$link .= '&p[' . $m . ']=';
			$link .= ( isset( $link_option['page'] ) ) ? $link_option['page'] : '';
			$link .= '&';
		}
		if ( isset( $link_option['text'] ) ) {
			$link_text = $link_option['text'];
		}
		if ( isset( $link_option['full'] ) && $link_option['full'] ) {
			$full_link = true;
		}
		if ( isset( $link_option['arguments'] ) && is_array( $link_option['arguments'] ) ) {
			foreach ( $link_option['arguments'] as $key => $val ) {
				$args[ $key ] = $val;
			}
		}
		$m ++;
	}

	$link  = rtrim( $link, '&' );
	$link  = full_link( $link );
	$argcc = 0;
	foreach ( $args as $key => $val ) {
		if ( $val === false ) {
			continue;
		}
		$link .= ( ( strpos( $link, '?' ) !== false ) ? '&' : '?' ) . "$key=$val";
	}
	if ( $full_link ) {
		$link = '<a href="' . $link . '"';
		if ( isset( $link_options['class'] ) ) {
			$link .= ' class="' . $link_options['class'] . '"';
		}
		$link_text = ( $link_text ) ? $link_text : 'N/A';
		if ( ! isset( $link_options['raw'] ) ) {
			$link_text = htmlspecialchars( $link_text );
		}
		$link .= '>' . $link_text . '</a>';
	}

	return $link;
}


function generate_link( $page = '', $args = array(), $module = false, $include_parent = false, $allow_nesting = false, $load_modules = false, $load_pages = false ) {

	if ( ! $load_modules ) {
		$load_modules = ( isset( $_REQUEST['m'] ) ) ? ( is_array( $_REQUEST['m'] ) ? $_REQUEST['m'] : array( $_REQUEST['m'] ) ) : false;
	}
	if ( ! $load_pages ) {
		$load_pages = ( isset( $_REQUEST['p'] ) ) ? ( is_array( $_REQUEST['p'] ) ? $_REQUEST['p'] : array( $_REQUEST['p'] ) ) : false;
	}
	$link = full_link( '' );

	if ( $include_parent !== false ) {
		// we have to include any parent modules here.
		// at the moment we just grab the currently loaded
		// modules from the uri. Later we should hop up the plugin
		// heirarchy building links as we go.

		foreach ( $load_modules as $key => $val ) {
			if ( $key > $include_parent && ( $page && $module ) ) {
				unset( $load_modules[ $key ] );
				unset( $load_pages[ $key ] );
			}
		}
		if ( $page && $module ) {
			$x = count( $load_modules ); // so it doesn't keep growing at every []
			if ( $allow_nesting === false && in_array( $module, $load_modules ) ) {
				// reply newer definitions of older module with new settings.
				$x = array_search( $module, $load_modules );
			} else if ( $allow_nesting !== false ) {
				// we allow nesting of this module up to the defined number of times.
				// 1 will allow this module to exist once before the specified link.
				foreach ( array_keys( $load_modules, $module ) as $key => $val ) {
					if ( $key == $allow_nesting ) {
						$x = $key;
					}
					if ( $key >= $allow_nesting ) {
						// gone to far, remove this from teh loaded nested modules.
						unset( $load_modules[ $key ] );
						unset( $load_pages[ $key ] );
					}
				}

			}
			$load_modules[ $x ] = $module;
			$load_pages[ $x ]   = $page;

		}
		//if(!$args){
		foreach ( $_GET as $key => $val ) {
			if ( ! is_array( $val ) && ! isset( $args[ $key ] ) && $key != 'm' && $key != 'p' ) {
				// only include id's that are part of to-be-loaded modules
				foreach ( $load_modules as $load_module ) {
					if ( preg_match( '/' . preg_quote( $load_module, '/' ) . '/', $key ) ) {
						$args[ $key ] = $val;
					}
				}
				//$args[$key] = $val;
			}
		}
		//}
	} else {
		if ( $module ) {
			$load_modules = array( $module );
		}
		if ( $page ) {
			$load_pages = array( $page );
		}
	}
	foreach ( $load_modules as $load_module_key => $load_module ) {
		if ( ! isset( $load_pages[ $load_module_key ] ) ) {
			continue;
		}
		if ( _REWRITE_LINKS ) {
			$link .= $load_module . '.' . $load_pages[ $load_module_key ] . '/';
		} else {
			$link .= ( strpos( $link, '?' ) !== false ? '&' : '?' ) . 'm[' . $load_module_key . ']=' . $load_module . "&p[" . $load_module_key . "]=" . $load_pages[ $load_module_key ];
		}
		//

	}

	if ( is_array( $args ) ) {
		$argcc = 0;
		foreach ( $args as $key => $val ) {
			if ( $val === false ) {
				continue;
			}
			$link .= ( ( strpos( $link, '?' ) !== false ) ? '&' : '?' ) . "$key=$val";
		}
	} else {
		$link .= $args;
	}

	return $link;
}

function create_link( $label, $type, $href = '', $id = '' ) {
	ob_start();
	$class = 'uibutton';
	switch ( $type ) {
		case "reset":
			if ( ! $href ) {
				ob_end_clean();

				return '';
			}
			?>
			<input type="button" class="uibutton" name="resetbtn" value="<?php echo _l( $label ); ?>"
			       onclick="window.location.href='<?php echo $href; ?>';">
			<?php
			break;
		case "submit":
			?>
			<input type="submit" class="uibutton" name="submitbtn" value="<?php echo _l( $label ); ?>">
			<?php
			break;
		case 'return':
			if ( ! $href ) {
				ob_end_clean();

				return '';
			}
			?>
			<a href="<?php echo $href; ?>" class="<?php echo $class; ?>">
				<span>&laquo; <?php echo _l( $label ); ?></span>
			</a>
			<?php
			break;
		case "thickbox":
			$class .= ' thickbox';
		case 'link':
			if ( ! $href ) {
				ob_end_clean();

				return '';
			}
			?>
			<a href="<?php echo $href; ?>" class="<?php echo $class; ?>" id="<?php echo $id; ?>">
				<span><?php echo _l( $label ); ?></span>
			</a>
			<?php
			break;
		case "add":
		default:
			if ( ! $href ) {
				ob_end_clean();

				return '';
			}
			?>
			<a href="<?php echo $href; ?>" class="<?php echo $class; ?>" id="<?php echo $id; ?>">
				<img src="<?php echo _BASE_HREF; ?>images/add.png" width="10" height="10" alt="add" border="0"/>
				<span><?php echo _l( $label ); ?></span>
			</a>
		<?php
	}

	return ob_get_clean();
}


/*
function module_link($options){
    global $plugins;
    // generate this round of link:
    $options['link'] = '';
    if(isset($options['parent_options'])){
        // we can search here for any missing argument values.
    }
    if(isset($options['parent_link'])){
        // we re-run this module_link function to the parent module
        $parent_module = $options['parent_link']['module'];
        $parent_argument = $options['parent_link']['argument'];
        $parent_link = $plugins[$parent_module]->link_generate(false,array(
            'parent_options' => $options,
        ));
        $options['link'] = $parent_link . $options['link'];
    }
    return $options['link'];
}

function module_link_combine_parent($options,$parent_link){
    global $plugins;
    $parent_module = $parent_link['module'];
    $parent_argument = $parent_link['argument'];
    $argument_value = (isset($options['data'])&&isset($options['data'][$parent_argument])) ? $options['data'][$parent_argument] : false;
    // we call the parent module to get its link options,
    // then we combine them together and return
    $parent_options = $plugins[$parent_module]->link_options($argument_value);
    // combine together.
    foreach($parent_options as $key=>$val){
        if(!isset($options[$key])){
            $options[$key] = $val;
        }else{

        }
    }
    return $options;
}*/
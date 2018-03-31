<?php


class module_template extends module_base {

	public $values = array();
	public $tags = array();
	public $content = '';
	public $description = '';
	public $wysiwyg = false;

	public $template_id;
	public $template_key;

	private static $_templates;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->links           = array();
		$this->module_name     = "template";
		$this->module_position = 28;

		$this->version = 2.259;
		//2.259 - 2015-11-06 - javascript template improvement
		//2.258 - 2015-07-29 - delete template button
		//2.257 - 2015-04-23 - more template tags for emails
		//2.256 - 2015-01-09 - newline fix for template tags
		//2.255 - 2014-11-19 - improved template tag arithmetic
		//2.254 - 2014-11-19 - improved template tag arithmetic
		//2.253 - 2014-11-19 - improved template tag arithmetic
		//2.252 - 2014-09-28 - fix for showing available template tags under editor
		//2.251 - 2014-05-28 - more powerful if/else template tags
		//2.25 - 2014-05-25 - support for {CURRENCY:xxx} template tag
		//2.249 - 2014-05-20 - template defaults
		//2.248 - 2014-03-08 - date splitting in templates, eg: {DATE_FIELD-ymdYMDjlSWFn}
		//2.247 - 2014-02-10 - conditional logic improvement
		//2.246 - 2014-02-05 - new quote feature
		//2.245 - 2013-11-15 - working on new UI
		//2.244 - 2013-10-13 - basic conditional tags in templates {if:KEY}Hi {KEY}{endif:KEY}
		//2.243 - 2013-10-05 - settings page improvement
		//2.242 - 2013-10-02 - template creation fix
		//2.241 - 2013-09-12 - support for company specific templates
		//2.24 - 2013-09-02 - support for {l:Translate} language strings in templates
		//2.239 - 2013-08-30 - template speed improvement
		//2.238 - 2013-05-01 - template printing improvements

		//2.22 - wysiwyg edior error on creating new templates.
		//2.221 - perm fix
		//2.222 - sort by name instead of id
		//2.23 - editing templates from other settings pages in a popup
		//2.231 - new jquery version
		//2.232 - showing available template tags in template editor.
		//2.233 - speed improvements
		//2.234 - bug fix create new template
		//2.235 - support for basic arithmetic in template variables (+-) and dates (+1d-1m+2y)
		//2.236 - new 'external_template' template, used for all external layouts (jobs, tickets, invoices, etc..)
		//2.237 - css tweak

		// the link within Admin > Settings > templates.
		if ( module_security::has_feature_access( array(
			'name'        => 'Settings',
			'module'      => 'config',
			'category'    => 'Config',
			'view'        => 1,
			'description' => 'view',
		) ) ) {
			$this->links[] = array(
				"name"                => "Templates",
				"p"                   => "template",
				"icon"                => "icon.png",
				"args"                => array( 'template_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}


	}

	private static function _load_all_templates() {
		self::$_templates = array();


		if ( self::db_table_exists( 'template' ) ) {
			// load all templates into memory for quicker processing.
			foreach ( self::get_templates() as $template ) {
				// hook in here to load any custom company templates.
				if ( $template['template_id'] && class_exists( 'module_company', false ) && is_callable( 'module_company::template_get_company' ) ) {
					$custom_data = module_company::template_get_company( $template['template_id'], $template );
					if ( $custom_data ) {
						$template = $custom_data;
					}
				}
				if ( $template['wysiwyg'] && stripos( $template['content'], '<html' ) ) {
					if ( preg_match( '#<body>(.*)</body>#imsU', $template['content'], $matches ) ) {
						$template['content'] = $matches[1];
					}
				}
				self::$_templates[ $template['template_key'] ] = $template;
			}
		}
	}


	public static function link_generate( $template_id = false, $options = array(), $link_options = array() ) {

		$key = 'template_id';
		if ( $template_id === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}
		$bubble_to_module = false;
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'template';
		}
		$options['page'] = $template_id !== false ? 'template_edit' : 'template';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['template_id'] = $template_id;
		$options['module']                   = 'template';
		$data                                = self::get_template( $template_id );
		$options['data']                     = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['template_key'] ) || ! trim( $data['template_key'] ) ) ? 'N/A' : htmlspecialchars( $data['template_key'] );
		//if(isset($data['template_id']) && $data['template_id']>0){
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'template_id',
		);
		// }
		array_unshift( $link_options, $options );

		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $template_id, $full = false ) {
		return self::link_generate( $template_id, array( 'full' => $full ) );
	}

	public static function link_open_popup( $template_name ) {
		$template = self::get_template_by_key( $template_name );
		if ( ! $template || ! isset( $template->template_id ) || ! $template->template_id ) {
			return;
		}
		$template_id = $template->template_id;
		$url         = self::link_open( $template_id );
		if ( ! preg_match( '/display_mode/', $url ) ) {
			$url .= ( strpos( $url, '?' ) ? '&' : '?' ) . 'display_mode=ajax';
		}
		$url .= ( strpos( $url, '?' ) ? '&' : '?' ) . 'return=' . urlencode( $_SERVER['REQUEST_URI'] );
		?>
		<p>
			<a href="#"
			   onclick="$('#template_popup_<?php echo $template_id; ?>').dialog('open'); return false;"><?php _e( 'Edit template: %s', htmlspecialchars( $template->template_key ) ); ?></a>
			<em><?php echo htmlspecialchars( $template->description ); ?></em>
		</p>
		<div id="template_popup_<?php echo $template_id; ?>" title="">
			<div class="modal_inner"></div>
		</div>
		<script type="text/javascript">
        $(function () {
            $("#template_popup_<?php echo $template_id;?>").dialog({
                autoOpen: false,
                width: 700,
                height: 600,
                modal: true,
                buttons: {
                    /*Close: function() {
												$(this).dialog('close');
										}*/
                },
                open: function () {
                    var t = this;
                    $.ajax({
                        type: "GET",
                        url: '<?php echo $url;?>',
                        dataType: "html",
                        success: function (d) {
                            $('.modal_inner', t).html(d);
                            $('input[name=_redirect]', t).val(window.location.href);
                            init_interface();
                        }
                    });
                },
                close: function () {
                    $('.modal_inner', this).html('');
                }
            });
        });
		</script>
		<?php
	}

	public function process() {
		if ( 'save_template' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				die( 'No perms to edit Config > Settings' );
			}
			$this->_handle_save_template();
		}

	}

	function delete( $template_id ) {
		if ( self::can_i( 'delete', 'Templates' ) ) {
			$template_id = (int) $template_id;
			$sql         = "DELETE FROM " . _DB_PREFIX . "template WHERE template_id = '" . $template_id . "' LIMIT 1";
			$res         = query( $sql );
		}
	}

	public static function add_tags( $template_key, $tags_to_add ) {
		$template    = self::get_template_by_key( $template_key );
		$template_id = $template->template_id;
		if ( $template_id ) {
			if ( ! is_array( $template->tags ) ) {
				$template->tags = array();
			}
			foreach ( $tags_to_add as $key => $val ) {
				if ( ! is_array( $val ) ) {
					if ( strlen( $val ) > 30 ) {
						$val = substr( $val, 0, 30 ) . '...';
					}
					unset( $tags_to_add[ $key ] );
					$tags_to_add[ strtoupper( $key ) ] = $val;
				}
			}
			//            echo '<hr>';echo '<hr>';
			//            print_r($tags_to_add);echo '<hr>';
			//            print_r($template->tags);echo '<hr>';
			$new_tags = array_merge( $tags_to_add, $template->tags );
			//            print_r($new_tags);echo '<hr>';
			self::$_templates[ $template_key ]['tags'] = serialize( $new_tags );
			update_insert( 'template_id', $template_id, 'template', array( 'tags' => serialize( $new_tags ) ) );
		}
	}

	public static function init_template( $template_key, $content, $description, $type = 'text', $tags = array() ) {

		// todo - cache this, dont init on every page load
		if ( ! self::db_table_exists( 'template' ) ) {
			return;
		}
		if ( ! count( self::$_templates ) ) {
			self::_load_all_templates();
		}
		$template = false;

		if ( isset( self::$_templates[ $template_key ] ) ) {
			$template = self::$_templates[ $template_key ];
		} else {
			$template = get_single( "template", "template_key", $template_key );
		}
		if ( is_array( $type ) && ! $tags ) {
			$tags = $type;
			$type = '';
		}
		//$template=get_single('template','template_key',$template_key);
		if ( ! $template || ( ! $template['content'] && $content ) ) {
			$data = array(
				'template_key' => $template_key,
				'description'  => $description,
				'content'      => $content,
				'wysiwyg'      => 1,
				'tags'         => serialize( $tags ),
			);
			if ( $type == 'text' && stripos( $data['content'], '<br' ) === false && stripos( $data['content'], '<p>' ) === false && stripos( $data['content'], '<table' ) === false ) {
				$data['content'] = nl2br( $content );
			} else if ( $type == 'code' ) {
				$data['wysiwyg'] = 0;
			}
			update_insert( 'template_id', ( $template && $template['template_id'] ) ? $template['template_id'] : 'new', 'template', $data );
			self::$_templates[ $template_key ] = $data;
		} else {
			// add new tags if any are given.
			if ( $tags && isset( $template['tags'] ) && $template_key ) {
				// check these tags
				$new_tags      = false;
				$existing_tags = @unserialize( $template['tags'] );
				foreach ( $tags as $key => $val ) {
					if ( ! isset( $existing_tags[ strtoupper( $key ) ] ) && ! isset( $existing_tags[ $key ] ) ) {
						$new_tags = true;
					}
				}
				if ( $new_tags ) {
					self::add_tags( $template_key, $tags );
				}
				/*$existing_tags = @unserialize($template['tags']);
				$tags = array_merge($existing_tags,$tags);
				update_insert('template_id',$template['template_id'],'template',array(
						'tags'=>serialize($tags),
				));*/
			}
		}
	}

	public static function &get_template_by_key( $template_key ) {
		if ( ! count( self::$_templates ) ) {
			self::_load_all_templates();
		}
		$template = new self();
		if ( isset( self::$_templates[ $template_key ] ) ) {
			$data = self::$_templates[ $template_key ];
		} else if ( self::db_table_exists( 'template' ) ) {
			$data = get_single( "template", "template_key", $template_key );
		} else {
			$data = array();
		}
		if ( $data && isset( $data['template_id'] ) && $data['template_id'] && $data['template_key'] && $data['template_key'] == $template_key ) {
			// hook in here to load any custom company templates.
			if ( class_exists( 'module_company', false ) && is_callable( 'module_company::template_get_company' ) ) {
				$custom_data = module_company::template_get_company( $data['template_id'], $data );
				if ( $custom_data ) {
					$data = $custom_data;
				}
			}
		}
		foreach ( $data as $key => $val ) {
			if ( $key == 'tags' ) {
				$template->{$key} = @unserialize( $val );
			} else {
				$template->{$key} = $val;
			}
		}

		return $template;
	}

	public static function get_template( $template_id ) {
		if ( self::db_table_exists( 'template' ) ) {
			$data = get_single( "template", "template_id", $template_id );
			if ( $data && $data['template_id'] && $data['template_id'] == $template_id ) {
				// hook in here to load any custom company templates.
				if ( class_exists( 'module_company', false ) && is_callable( 'module_company::template_get_company' ) ) {
					$custom_data = module_company::template_get_company( $data['template_id'], $data );
					if ( $custom_data ) {
						$data = $custom_data;
					}
				}
			}

			return $data;
		} else {
			return array();
		}
	}

	public static function get_templates( $search = array() ) {
		if ( self::db_table_exists( 'template' ) ) {
			// useto be sorted by template_id
			return get_multiple( "template", $search, "template_id", "exact", "template_key ASC" );
		} else {
			return array();
		}
	}


	private function _handle_save_template() {
		// handle post back for save template template.
		$template_id = (int) $_REQUEST['template_id'];

		// delete.
		if ( isset( $_REQUEST['butt_del'] ) && self::can_i( 'delete', 'Templates' ) ) {
			$template_data = self::get_template( $template_id );
			if ( module_form::confirm_delete(
				'template_id',
				_l( "Really delete template: %s", $template_data['template_key'] ),
				self::link_open( $template_id )
			) ) {
				$this->delete( $template_id );
				// todo: delete company template as well if exists.
				set_message( "Template deleted successfully" );
				redirect_browser( self::link_open( false ) );
			}
		}

		$data          = $_POST;
		$already_saved = false;
		if ( (int) $template_id > 0 && class_exists( 'module_company', false ) ) {
			module_company::template_handle_save( $template_id, $data );
			// we have to redirect to a company specific version of this template
			// each company template must have a matching parent template id/key. cannot change keys in company unique config.
		}
		// write header/footer html based on uploaded images.
		// pass uploaded images to the file manager plugin.
		$template_id = update_insert( 'template_id', $template_id, 'template', $data );
		// redirect upon save.
		set_message( 'Template saved successfully!' );
		if ( isset( $_REQUEST['return'] ) && $_REQUEST['return'] ) {
			redirect_browser( $_REQUEST['return'] );
		}
		redirect_browser( $this->link_open( $template_id ) );
		exit;
	}

	public function assign_values( $values ) {
		if ( is_array( $values ) ) {
			foreach ( $values as $key => $val ) {
				if ( is_array( $val ) ) {
					continue;
				}
				$this->values[ strtolower( $key ) ] = $val;
			}
		}
	}

	public function replace_content() {
		$content = $this->content;
		$this->add_tags( $this->template_key, $this->values );
		// add todays date values
		if ( ! isset( $this->values['day'] ) ) {
			$this->values['day'] = date( 'd' );
		}
		if ( ! isset( $this->values['month'] ) ) {
			$this->values['month'] = date( 'm' );
		}
		if ( ! isset( $this->values['year'] ) ) {
			$this->values['year'] = date( 'y' );
		}
		if ( ! isset( $this->values['current_user'] ) && module_security::is_logged_in() ) {
			$this->values['current_user'] = module_security::get_loggedin_id();
			$user_details                 = module_user::get_replace_fields( module_security::get_loggedin_id() );
			foreach ( $user_details as $key => $val ) {
				$this->values[ 'current_' . $key . '' ] = $val;
			}
		}
		// basic conditional tags - eg: {if:name}Dear {name},{else:name}Hello,{endif:name}
		// todo - support nested if - pass to template module.
		$debug = false;
		if ( preg_match_all( '#\{if:([^\}=]+)([^\}]*)\}(.*){endif:\1\}.*#imsU', $content, $matches ) ) {
			foreach ( $matches[1] as $key => $template_tag ) {
				// does this first if have a conditional statement (eg: {if:STATUS=New}sdfasdf{else}asdfasdf{/if:STATUS}
				$bits = preg_split( '#{else[^\}]*}#', $matches[3][ $key ] );
				preg_match_all( '#\{(else)?(if:([^\}=]+)([^\}]*))?\}#ims', $matches[0][ $key ], $elseif_matches );
				$new_content = false;
				if ( $debug ) {
					echo $matches[0][ $key ] . "\n";
				}
				if ( $debug ) {
					print_r( $bits );
				}
				if ( $debug ) {
					print_r( $elseif_matches );
				}
				foreach ( $elseif_matches[0] as $elseif_key => $elseif_condition ) {
					$template_tag_value = isset( $this->values[ strtolower( $elseif_matches[3][ $elseif_key ] ) ] ) ? trim( $this->values[ strtolower( $elseif_matches[3][ $elseif_key ] ) ] ) : '';
					if ( ! strlen( trim( $elseif_matches[3][ $elseif_key ] ) ) ) {
						// we are at the final {else} statement, this means all other checks before have failed. we use this content.
						if ( $debug ) {
							echo "Reached final {else} statement, using this value\n";
						}
						$new_content = $bits[ $elseif_key ];
						break;
					} else {
						if ( $debug ) {
							echo "Checking $elseif_condition against " . $elseif_matches[3][ $elseif_key ] . " which has the value: " . $template_tag_value . "\n";
						}
						if ( strlen( $elseif_matches[4][ $elseif_key ] ) && $elseif_matches[4][ $elseif_key ][0] == '=' ) {
							// the if/elseif tag is checking a condition, rather than just an "does exist" check
							$check_matching_value = ltrim( trim( $elseif_matches[4][ $elseif_key ] ), '=' );
							if ( $debug ) {
								echo "Checking if it matches $check_matching_value \n";
							}
							if ( $check_matching_value == $template_tag_value ) {
								if ( $debug ) {
									echo "YES WE HAVE A MATCH \n";
								}
								// this first if statement matches this query! yes!
								// use its value in the final $new_content
								$new_content = $bits[ $elseif_key ];
								break;
							} else {
								if ( $debug ) {
									echo "No match this time \n";
								}
							}
						} else {
							// we're just checking if this value exists or not.
							if ( strlen( $template_tag_value ) > 0 && $template_tag_value != '0000-00-00' && $template_tag_value != _l( 'N/A' ) ) {
								// it's a match!
								$new_content = $bits[ $elseif_key ];
								break;
							} else {
								// no match, move onto next bit.
							}
						}
					}
				}

				if ( $debug ) {
					echo "Final content to use will be: \n" . $new_content;
				}

				$content = str_replace( $matches[0][ $key ], $new_content, $content );
			}
		}
		foreach ( $this->values as $key => $val ) {
			if ( is_array( $val ) ) {
				continue;
			}
			// if this isn't a html field we add newlines.
			if ( ! preg_match( '#<[^>]+>#', $val ) ) {
				// raw text. nl2br
				$val = nl2br( $val );
			}
			$content = str_replace( '{' . strtoupper( $key ) . '}', $val, $content );
			// we perform some basic arithmetic on some replace fields.
			if ( preg_match_all( '#\{(currency:)?' . preg_quote( strtoupper( $key ), '#' ) . '([*+-])([\d\.]+)\}#', $content, $matches ) ) {
				// pull the "number" portion out of this string for math processing.
				// string could look like this: "$150.10 USD"
				$mathval = $originalval = $val;
				if ( preg_match( '#([\d.,]+)#', $val, $mathvalmatches ) ) {
					$mathval = $originalval = $mathvalmatches[1];
				}
				foreach ( $matches[0] as $i => $v ) {
					$mathval = $originalval;
					if ( $matches[2][ $i ] == '-' ) {
						$mathval = $mathval - $matches[3][ $i ];
					} else if ( $matches[2][ $i ] == '+' ) {
						$mathval = $mathval + $matches[3][ $i ];
					} else if ( $matches[2][ $i ] == '*' ) {
						$mathval = $mathval * $matches[3][ $i ];
					}

					if ( strtolower( $matches[1][ $i ] ) == 'currency:' ) {
						$mathval = dollar( $mathval, true, isset( $this->values['currency_id'] ) ? $this->values['currency_id'] : false );
					}

					$newval = str_replace( $originalval, $mathval, $val );

					$content = str_replace( $v, $newval, $content );
				}
			}
			if ( preg_match_all( '#\{currency:(' . preg_quote( strtoupper( $key ), '#' ) . ')\}#', $content, $matches ) ) {
				foreach ( $matches[0] as $i => $v ) {
					$content = str_replace( $v, dollar( $val, true, isset( $this->values['currency_id'] ) ? $this->values['currency_id'] : false ), $content );
				}
			}
			// we perform some arithmetic on date fields.
			$matches = false;
			if ( stripos( $key, 'date' ) !== false && $val && strlen( $val ) > 6 && preg_match_all( '#' . preg_quote( '{' . strtoupper( $key ), '#' ) . '((?>[+-]\d+[ymd])*)\}#', $content, $matches ) ) {
				//$processed_date = (input_date($val)); $processed_date_timeo =
				$processed_date_time = strtotime( input_date( $val ) );
				foreach ( $matches[0] as $i => $v ) {
					if ( preg_match_all( '#([+-])(\d+)([ymd])#', $matches[1][ $i ], $date_math ) ) {
						foreach ( $date_math[1] as $di => $dv ) {
							$period = $date_math[3][ $di ];
							$period = ( $period == 'd' ? 'day' : ( $period == 'm' ? 'month' : ( $period == 'y' ? 'year' : 'days' ) ) );
							//echo $dv.$date_math[2][$di]." ".$period."\n";
							$processed_date_time = strtotime( $dv . $date_math[2][ $di ] . " " . $period, $processed_date_time );
						}
						$content = str_replace( $v, print_date( $processed_date_time ), $content );
						//echo "Processing date: $val - $processed_date (time: $processed_date_timeo / ".print_date($processed_date_timeo).") with result of: ".print_date($processed_date_time); exit;
					}
				}
			}
			// we perform some date splitting
			$matches = false;
			if ( stripos( $key, 'date' ) !== false && $val && strlen( $val ) > 6 && preg_match_all( '#' . preg_quote( '{' . strtoupper( $key ), '#' ) . '-([ymdYMDjlSWFn])\}#', $content, $matches ) ) {
				$processed_date_time = strtotime( input_date( $val ) );
				foreach ( $matches[0] as $i => $v ) {
					$content = str_replace( $v, date( $matches[1][ $i ], $processed_date_time ), $content );
				}
			}
			//$val = str_replace(array('\\', '$'), array('\\\\', '\$'), $val);
			//$content = preg_replace('/\{'.strtoupper(preg_quote($key,'/')).'\}/',$val,$content);
		}
		if ( preg_match_all( '#\{l:([^\}]+)\}#', $content, $matches ) ) {
			foreach ( $matches[1] as $key => $label ) {
				$content = str_replace( $matches[0][ $key ], _l( $label ), $content );
			}

		}

		return $content;
	}

	public function replace_description() {
		$content = $this->description;
		$this->add_tags( $this->template_key, $this->values );
		foreach ( $this->values as $key => $val ) {
			if ( is_array( $val ) ) {
				continue;
			}
			$content = str_replace( '{' . strtoupper( $key ) . '}', $val, $content );
		}

		return $content;
	}

	public function render( $type = 'html', $options = array() ) {
		ob_start();
		switch ( $type ) {
			case 'pretty_html':
				// header and footer so plain contnet can be rendered nicely.
				$display_mode = get_display_mode();
				// addition - woah! we pass this through to the template module for re-rending again:
				ob_start();
				?>
				<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
					<title>{PAGE_TITLE}</title>
					{FAVICON}
					{AUTOMATIC_STYLES}
					{AUTOMATIC_SCRIPTS}
					<style type="text/css">
						body {
							margin: 0;
						}
					</style>
				</head>
				<body>
				<div class="pretty_content_wrap">
					{CONTENT}
				</div>
				</body>
				</html>
				<?php
				/*$c = $this->replace_content();
										if(!$this->wysiwyg){
												//$c = nl2br($c);
										}
										echo $c;*/
				module_template::init_template( 'external_template', ob_get_clean(), 'Used when displaying the external content such as "External Jobs" and "External Invoices" and "External Tickets".', 'code', array(
					'CONTENT'           => 'The inner content',
					'PAGE_TITLE'        => 'in the <title> tag',
					'FAVICON'           => 'if the theme specifies a favicon it will be here',
					'AUTOMATIC_STYLES'  => 'system generated stylesheets',
					'AUTOMATIC_SCRIPTS' => 'system generated javascripts',
				) );

				ob_start();
				?>
				<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/desktop.css" type="text/css">
				<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/print.css" type="text/css" media="print">
				<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/styles.css" type="text/css">
				<?php module_config::print_css(); ?><?php
				$css = ob_get_clean();
				ob_start();
				module_config::print_js();
				$scripts = ob_get_clean();

				$external_template = self::get_template_by_key( 'external_template' );
				$external_template->assign_values( array(
					'CONTENT'           => $this->replace_content(),
					'PAGE_TITLE'        => $this->page_title ? $this->page_title : module_config::s( 'admin_system_name' ),
					'FAVICON'           => ( module_theme::get_config( 'theme_favicon', '' ) ) ? '<link rel="icon" href="' . htmlspecialchars( module_theme::get_config( 'theme_favicon', '' ) ) . '">' : '',
					'AUTOMATIC_STYLES'  => $css,
					'AUTOMATIC_SCRIPTS' => $scripts,
				) );
				echo $external_template->render( 'raw' );

				break;
			case 'html':
			default:
				$c = $this->replace_content();
				if ( $this->wysiwyg ) {
					//$c = nl2br($c);
				}
				echo $c;
				break;
		}

		return ob_get_clean();
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>template` (
		`template_id` int(11) NOT NULL auto_increment,
		`template_key` varchar(255) NOT NULL DEFAULT  '',
		`description` varchar(255) NOT NULL DEFAULT  '',
		`content` LONGTEXT NULL,
		`tags` TEXT NULL,
		`wysiwyg` CHAR( 1 ) NOT NULL DEFAULT  '1',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`template_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		<?php

		return ob_get_clean();
	}

}


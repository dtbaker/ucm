<?php
// $Header: /cvsroot/html2ps/utils_array.php,v 1.7 2006/09/07 18:38:16 Konstantin Exp $

function any_flag_set( &$flags ) {
	for ( $i = 0, $size = count( $flags ); $i < $size; $i ++ ) {
		if ( $flags[ $i ] ) {
			return true;
		};
	}

	return false;
}

function expand_to_with_flags( $size, $array, $flags ) {
	// if array have no elements - return immediately
	if ( count( $array ) == 0 ) {
		return $array;
	};

	// Never decrease exising values
	if ( array_sum( $array ) > $size ) {
		return $array;
	}

	// Subtract non-modifiable values from target value
	for ( $i = 0; $i < count( $array ); $i ++ ) {
		if ( ! $flags[ $i ] ) {
			$size -= $array[ $i ];
		};
	};

	// Check if there's any expandable columns
	$sum = 0;
	for ( $i = 0, $count = count( $flags ); $i < $count; $i ++ ) {
		if ( $flags[ $i ] ) {
			$sum += $array[ $i ];
		};
	}

	if ( $sum == 0 ) {
		// Note that this function is used in colpans-width calculation routine
		// If we executing this branch, then we've got a colspan over non-resizable columns
		// So, we decide to expand the very first column; note that 'Size' in this case
		// will contain the delta value for the width and we need to _add_ it to the first
		// column's width
		$array[0] += $size;

		return $array;
	}

	// Calculate scale koeff
	$koeff = $size / $sum;

	// Apply scale koeff
	for ( $i = 0, $count = count( $flags ); $i < $count; $i ++ ) {
		if ( $flags[ $i ] ) {
			$array[ $i ] *= $koeff;
		};
	}

	return $array;
}

function expand_to( $size, $array ) {
	// if array have no elements - return immediately
	if ( count( $array ) == 0 ) {
		return $array;
	};

	// If array contains only zero elements (or no elements at all) do not do anything
	if ( array_sum( $array ) == 0 ) {
		return $array;
	};

	// Never decrease exising values
	if ( array_sum( $array ) > $size ) {
		return $array;
	}

	// Calculate scale koeff
	$koeff = $size / array_sum( $array );

	// Apply scale koeff
	for ( $i = 0, $size = count( $array ); $i < $size; $i ++ ) {
		$array[ $i ] *= $koeff;
	}

	return $array;
}

// $Header: /cvsroot/html2ps/utils_graphic.php,v 1.9 2007/01/24 18:56:10 Konstantin Exp $

function do_image_open( $filename, &$type ) {
	// Gracefully process missing GD extension
	if ( ! extension_loaded( 'gd' ) ) {
		return null;
	};

	// Disable interlacing for the generated images, as we do not need progressive images
	// if PDF files (futhermore, FPDF does not support such images)
	$image = do_image_open_wrapped( $filename, $type );
	if ( ! is_resource( $image ) ) {
		return null;
	};

	if ( ! is_null( $image ) ) {
		imageinterlace( $image, 0 );
	};

	return $image;
}

function do_image_open_wrapped( $filename, &$type ) {
	// FIXME: it will definitely cause problems;
	global $g_config;
	if ( ! $g_config['renderimages'] ) {
		return null;
	};

	// get the information about the image
	if ( ! $data = @getimagesize( $filename ) ) {
		return null;
	};
	switch ( $data[2] ) {
		case 1: // GIF
			$type = 'image/png';
			// Handle lack of GIF support in older versions of PHP
			if ( function_exists( 'imagecreatefromgif' ) ) {
				return @imagecreatefromgif( $filename );
			} else {
				return null;
			};
		case 2: // JPG
			$type = 'image/jpeg';

			return @imagecreatefromjpeg( $filename );
		case 3: // PNG
			$type  = 'image/png';
			$image = imagecreatefrompng( $filename );
			//     imagealphablending($image, false);
			//     imagesavealpha($image, true);
			return $image;
		case 15: // WBMP
			$type = 'image/png';

			return @imagecreatefromwbmp( $filename );
	};

	return null;
}

;

// $Header: /cvsroot/html2ps/utils_url.php,v 1.9 2006/07/09 09:07:46 Konstantin Exp $

function guess_url( $path, $baseurl ) {
	// Check if path is absolute
	// 'Path' is starting with protocol identifier?
	if ( preg_match( "!^[a-zA-Z]+://.*!", $path ) ) {
		return $path;
	};

	$data = parse_url( $baseurl );

	$default_host = array(
		'http'  => 'localhost',
		'https' => 'localhost',
		'file'  => ''
	);

	$base_scheme = isset( $data['scheme'] ) ? $data['scheme'] : "http";
	$base_port   = isset( $data['port'] ) ? ":" . $data['port'] : "";
	$base_user   = isset( $data['user'] ) ? $data['user'] : "";
	$base_pass   = isset( $data['pass'] ) ? $data['pass'] : "";
	$base_host   = isset( $data['host'] ) ? $data['host'] : ( isset( $default_host[ $base_scheme ] ) ? $default_host[ $base_scheme ] : "" );
	$base_path   = isset( $data['path'] ) ? $data['path'] : "/";

	/**
	 * Workaround: Some PHP versions do remove the leading slash from the
	 * 'file://' URLs with empty host name, while some do not.
	 *
	 * An example of such URL is: file:///D:/path/dummy.html
	 * The path should be: /D:/path/dummy.html
	 *
	 * Here we check if the leading slash is present and
	 * add it if it is missing.
	 */
	if ( $base_scheme == "file" && PHP_OS == "WINNT" ) {
		if ( strlen( $base_path ) > 0 ) {
			if ( $base_path{0} != "/" ) {
				$base_path = "/" . $base_path;
			};
		};
	};

	$base_user_pass = "";
	if ( $base_user || $base_pass ) {
		$base_user_pass = sprintf( "%s:%s@", $base_user, $base_pass );
	}

	// 'Path' is starting at scheme?
	if ( substr( $path, 0, 2 ) == "//" ) {
		$guessed = $base_scheme . ':' . $path;

		return $guessed;
	}

	// 'Path' is starting at root?
	if ( substr( $path, 0, 1 ) == "/" ) {
		$guessed = $base_scheme . '://' . $base_user_pass . $base_host . $base_port . $path;

		return $guessed;
	};

	// 'Path' is relative from the current position
	if ( preg_match( "#^(/.*)/[^/]*$#", $base_path, $matches ) ) {
		$base_path_dir = $matches[1];
	} else {
		$base_path_dir = "";
	};
	$guessed = $base_scheme . '://' . $base_user_pass . $base_host . $base_port . $base_path_dir . '/' . $path;

	return $guessed;
}

;


// $Header: /cvsroot/html2ps/utils_text.php,v 1.2 2005/07/01 18:01:58 Konstantin Exp $

function squeeze( $string ) {
	return preg_replace( "![ \n\t]+!", " ", trim( $string ) );
}


// $Header: /cvsroot/html2ps/utils_units.php,v 1.22 2007/01/24 18:56:10 Konstantin Exp $

function round_units( $value ) {
	return round( $value, 2 );
}

function pt2pt( $pt ) {
	return $pt * $GLOBALS['g_pt_scale'];
}

function px2pt( $px ) {
	global $g_px_scale;

	return $px * $g_px_scale;
}

function mm2pt( $mm ) {
	return $mm * 2.834645669;
}

function units_mul( $value, $koeff ) {
	if ( preg_match( "/(pt|pc|px|mm|cm|em|ex)$/", $value ) ) {
		$units = substr( $value, strlen( $value ) - 2, 2 );
	} else {
		$units = "";
	};

	return sprintf( "%.2f%s",
		round( (double) $value * $koeff, 2 ),
		$units );
}

function punits2pt( $value, $font_size ) {
	$value = trim( $value );

	// Check if current value is percentage
	if ( substr( $value, strlen( $value ) - 1, 1 ) === "%" ) {
		return array( (float) $value, true );
	} else {
		return array( units2pt( $value, $font_size ), false );
	}
}

function em2pt( $value, $font_size ) {
	return $font_size * (double) $value * EM_KOEFF;
}

function ex2pt( $value, $font_size ) {
	return $font_size * (double) $value * EX_KOEFF;
}

function units2pt( $value, $font_size = null ) {
	$unit = Value::unit_from_string( $value );

	switch ( $unit ) {
		case UNIT_PT:
			return pt2pt( (double) $value );
		case UNIT_PX:
			return px2pt( (double) $value );
		case UNIT_MM:
			return pt2pt( mm2pt( (double) $value ) );
		case UNIT_CM:
			return pt2pt( mm2pt( (double) $value * 10 ) );
		case UNIT_EM:
			return em2pt( (double) $value, $font_size );
		case UNIT_EX:
			return ex2pt( (double) $value, $font_size );
		case UNIT_IN:
			return pt2pt( (double) $value * 72 ); // points used by CSS 2.1 are equal to 1/72nd of an inch.
		case UNIT_PC:
			return pt2pt( (double) $value * 12 ); // 1 pica equals to 12 points.
		default:
			global $g_config;
			if ( $g_config['mode'] === 'quirks' ) {
				return px2pt( (double) $value );
			} else {
				return 0;
			};
	};
}


// $Header: /cvsroot/html2ps/utils_number.php,v 1.2 2005/07/01 18:01:58 Konstantin Exp $

function arabic_to_roman( $num ) {
	$arabic = array( 1, 4, 5, 9, 10, 40, 50, 90, 100, 400, 500, 900, 1000 );
	$roman  = array( "I", "IV", "V", "IX", "X", "XL", "L", "XC", "C", "CD", "D", "CM", "M" );
	$i      = 12;
	$result = "";
	while ( $num ) {
		while ( $num >= $arabic[ $i ] ) {
			$num    -= $arabic[ $i ];
			$result .= $roman[ $i ];
		}
		$i --;
	}

	return $result;
}


require_once( HTML2PS_DIR . 'value.generic.php' );

class Color extends CSSValue {
	function Color( $rgb = array( 0, 0, 0 ), $transparent = true ) {
		// We need this 'max' hack, as somethimes we can get values below zero due
		// the rounding errors... it will cause PDFLIB to die with error message
		// that is not what we want
		$this->r = max( $rgb[0] / 255.0, 0 );
		$this->g = max( $rgb[1] / 255.0, 0 );
		$this->b = max( $rgb[2] / 255.0, 0 );

		$this->transparent = $transparent;
	}

	function apply( &$viewport ) {
		$viewport->setrgbcolor( $this->r, $this->g, $this->b );
	}

	function blend( $color, $alpha ) {
		$this->r += ( $color->r - $this->r ) * $alpha;
		$this->g += ( $color->g - $this->g ) * $alpha;
		$this->b += ( $color->b - $this->b ) * $alpha;
	}

	function &copy() {
		$color =& new Color();

		$color->r           = $this->r;
		$color->g           = $this->g;
		$color->b           = $this->b;
		$color->transparent = $this->transparent;

		return $color;
	}

	function equals( $rgb ) {
		return
			$this->r == $rgb->r &&
			$this->g == $rgb->g &&
			$this->b == $rgb->b;
	}

	function isTransparent() {
		return $this->transparent;
	}
}


// $Header: /cvsroot/html2ps/config.parse.php,v 1.7 2007/05/07 13:28:39 Konstantin Exp $

require_once( HTML2PS_DIR . 'font.resolver.class.php' );
//require_once(HTML2PS_DIR . 'treebuilder.class.php');
//require_once(HTML2PS_DIR . 'media.layout.inc.php');

// Get list of media types being used by script;
// It should be a list of two types:
// 1. Current CSS media type chose by user (defaults to 'screen')
// 2. 'all' media type
//
function config_get_allowed_media() {
	return array( $GLOBALS['g_config']['cssmedia'], 'all' );
}

function parse_encoding_override_node_config_file( $root, &$resolver ) {
	$child = $root->first_child();
	do {
		if ( $child->node_type() == XML_ELEMENT_NODE ) {
			switch ( $child->tagname() ) {
				case "normal":
					if ( $root->has_attribute( 'name' ) ) {
						$names = explode( ',', $root->get_attribute( 'name' ) );
						foreach ( $names as $name ) {
							$resolver->add_normal_encoding_override( $name,
								$child->get_attribute( 'normal' ),
								$child->get_attribute( 'italic' ),
								$child->get_attribute( 'oblique' ) );
						};
					};

					if ( $root->has_attribute( 'mask' ) ) {
						foreach ( $names as $name ) {
							$resolver->add_normal_encoding_override_mask( $root->get_attribute( 'mask' ),
								$child->get_attribute( 'normal' ),
								$child->get_attribute( 'italic' ),
								$child->get_attribute( 'oblique' ) );
						};
					};

					break;
				case "bold":
					if ( $root->has_attribute( 'name' ) ) {
						$names = explode( ',', $root->get_attribute( 'name' ) );
						foreach ( $names as $name ) {
							$resolver->add_bold_encoding_override( $name,
								$child->get_attribute( 'normal' ),
								$child->get_attribute( 'italic' ),
								$child->get_attribute( 'oblique' ) );
						};
					};

					if ( $root->has_attribute( 'mask' ) ) {
						foreach ( $names as $name ) {
							$resolver->add_bold_encoding_override_mask( $root->get_attribute( 'mask' ),
								$child->get_attribute( 'normal' ),
								$child->get_attribute( 'italic' ),
								$child->get_attribute( 'oblique' ) );
						};
					};

					break;
			};
		};
	} while ( $child = $child->next_sibling() );
}

function parse_metrics_node_config_file( $root, &$resolver ) {
	$resolver->add_afm_mapping( $root->get_attribute( 'typeface' ),
		$root->get_attribute( 'file' ) );
}

function parse_ttf_node_config_file( $root, &$resolver ) {
	switch ( FONT_EMBEDDING_MODE ) {
		case 'all':
			$embed_flag = true;
			break;
		case 'none':
			$embed_flag = false;
			break;
		case 'config':
			$embed_flag = (bool) $root->get_attribute( 'embed' );
			break;
	}

	$resolver->add_ttf_mapping( $root->get_attribute( 'typeface' ),
		$root->get_attribute( 'file' ),
		$embed_flag );
}

function parse_family_encoding_override_node_config_file( $family, $root, &$resolver ) {
	$child = $root->first_child();
	do {
		if ( $child->node_type() == XML_ELEMENT_NODE ) {
			switch ( $child->tagname() ) {
				case "normal":
					$names = explode( ",", $root->get_attribute( 'name' ) );
					foreach ( $names as $name ) {
						$resolver->add_family_normal_encoding_override( $family,
							$name,
							$child->get_attribute( 'normal' ),
							$child->get_attribute( 'italic' ),
							$child->get_attribute( 'oblique' ) );
					};
					break;
				case "bold":
					$names = explode( ",", $root->get_attribute( 'name' ) );
					foreach ( $names as $name ) {
						$resolver->add_family_bold_encoding_override( $family,
							$name,
							$child->get_attribute( 'normal' ),
							$child->get_attribute( 'italic' ),
							$child->get_attribute( 'oblique' ) );
					};
					break;
			};
		};
	} while ( $child = $child->next_sibling() );
}

function parse_fonts_family_node_config_file( $root, &$resolver ) {
	// Note: font family names are always converted to lower case to be non-case-sensitive
	$child = $root->first_child();
	do {
		if ( $child->node_type() == XML_ELEMENT_NODE ) {
			$font_family_name = strtolower( $root->get_attribute( 'name' ) );
			switch ( $child->tagname() ) {
				case "normal":
					$resolver->add_normal_family( $font_family_name,
						$child->get_attribute( 'normal' ),
						$child->get_attribute( 'italic' ),
						$child->get_attribute( 'oblique' ) );
					break;
				case "bold":
					$resolver->add_bold_family( $font_family_name,
						$child->get_attribute( 'normal' ),
						$child->get_attribute( 'italic' ),
						$child->get_attribute( 'oblique' ) );
					break;
				case "encoding-override":
					parse_family_encoding_override_node_config_file( $font_family_name, $child, $resolver );
					break;
			};
		};
	} while ( $child = $child->next_sibling() );
}

function parse_fonts_node_config_file( $root, &$resolver ) {
	$child = $root->first_child();
	do {
		if ( $child->node_type() == XML_ELEMENT_NODE ) {
			switch ( $child->tagname() ) {
				case "alias":
					$resolver->add_alias( strtolower( $child->get_attribute( 'alias' ) ), $child->get_attribute( 'family' ) );
					break;
				case "family":
					parse_fonts_family_node_config_file( $child, $resolver );
					break;
				case "encoding-override":
					parse_encoding_override_node_config_file( $child, $resolver );
					break;
				case "ttf":
					parse_ttf_node_config_file( $child, $resolver );
					break;
				case "metrics":
					parse_metrics_node_config_file( $child, $resolver );
					break;
			};
		};
	} while ( $child = $child->next_sibling() );
}

function parse_config_file( $filename ) {
	// Save old magic_quotes_runtime value and disable it
	$mq_runtime = function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime();
	function_exists('set_magic_quotes_runtime') && set_magic_quotes_runtime( 0 );

	$doc  = TreeBuilder::build( file_get_contents( $filename ) );
	$root = $doc->document_element();

	$child = $root->first_child();
	do {
		if ( $child->node_type() == XML_ELEMENT_NODE ) {
			switch ( $child->tagname() ) {
				case "fonts":
					global $g_font_resolver;
					parse_fonts_node_config_file( $child, $g_font_resolver );
					break;
				case "fonts-pdf":
					global $g_font_resolver_pdf;
					parse_fonts_node_config_file( $child, $g_font_resolver_pdf );
					break;
				case "media":
					add_predefined_media( $child->get_attribute( 'name' ),
						(float) $child->get_attribute( 'height' ),
						(float) $child->get_attribute( 'width' ) );
					break;
			};
		};
	} while ( $child = $child->next_sibling() );

	// Restore old magic_quotes_runtime values
	function_exists('set_magic_quotes_runtime') && set_magic_quotes_runtime( $mq_runtime );
}

class FlowContext {
	var $absolute_positioned;
	var $fixed_positioned;

	var $viewport;
	var $_floats;
	var $collapsed_margins;
	var $container_uid;

	function add_absolute_positioned( &$box ) {
		$this->absolute_positioned[] =& $box;
	}

	function add_fixed_positioned( &$box ) {
		$this->fixed_positioned[] =& $box;
	}

	function add_float( &$float ) {
		$this->_floats[0][] =& $float;
	}

	function container_uid() {
		return $this->container_uid[0];
	}

	function &current_floats() {
		return $this->_floats[0];
	}

	// Get the bottom edge coordinate of the bottommost float in
	// current formatting context
	//
	// @return null in case of no floats exists in current context
	// numeric coordinate value otherwise
	//
	function float_bottom() {
		$floats =& $this->current_floats();

		if ( count( $floats ) == 0 ) {
			return null;
		}

		$bottom = $floats[0]->get_bottom_margin();
		$size   = count( $floats );
		for ( $i = 1; $i < $size; $i ++ ) {
			$bottom = min( $bottom, $floats[ $i ]->get_bottom_margin() );
		};

		return $bottom;
	}

	// Calculates the leftmost x-coordinate not covered by floats in current context
	// at the given level (y-coordinate)
	//
	// @param $x starting X coordinate (no point to the left of this allowed)
	// @param $y Y coordinate we're searching at
	// @return the leftmost X coordinate value
	//
	function float_left_x( $x, $y ) {
		$floats =& $this->current_floats();

		$size = count( $floats );
		for ( $i = 0; $i < $size; $i ++ ) {
			$float =& $floats[ $i ];

			// Process only left-floating boxes
			if ( $float->get_css_property( CSS_FLOAT ) == FLOAT_LEFT ) {
				// Check if this float contains given Y-coordinate
				//
				// Note that top margin coordinate is inclusive but
				// bottom margin coordinate is exclusive! The cause is following:
				// - if we have several floats in one line, their top margin edge Y coordinates will be equal,
				//   so we must use agreater or equal sign to avod placing all floats at one X coordinate
				// - on the other side, if we place one float under the other, the top margin Y coordinate
				//   of bottom float will be equal to bottom margin Y coordinate of the top float and
				//   we should NOT offset tho bottom float in this case
				//

				if ( $float->get_top_margin() + EPSILON >= $y &&
				     $float->get_bottom_margin() < $y ) {
					$x = max( $x, $float->get_right_margin() );
				};
			};
		};

		return $x;
	}

	// Calculates position of left floating box (taking into account the possibility
	// of "wrapping" float to next line in case we have not enough space at current level (Y coordinate)
	//
	// @param $parent reference to a parent box
	// @param $width width of float being placed. Full width! so, extra horizontal space (padding, margins and borders) is added here too
	// @param $x [out] X coordinate of float upper-left corner
	// @param $y [in,out] Y coordinate of float upper-left corner
	//
	function float_left_xy( &$parent, $width, &$x, &$y ) {
		// Numbler of floats to clear; we need this because of the following example:
		// <div style="width: 150px; background-color: red; padding: 5px;">
		// <div style="float: left; background-color: green; height: 40px; width: 100px;">T</div>
		// <div style="float: left; background-color: yellow; height: 20px; width: 50px;">T</div>
		// <div style="float: left; background-color: cyan; height: 20px; width: 50px;">T</div>
		// in this case the third float will be rendered directly under the second, so only the
		// second float should be cleared

		$clear = 0;

		$floats =& $this->current_floats();

		// Prepare information about the float bottom coordinates
		$float_bottoms = array();
		$size          = count( $floats );
		for ( $i = 0; $i < $size; $i ++ ) {
			$float_bottoms[] = $floats[ $i ]->get_bottom_margin();
		};

		// Note that the sort function SHOULD NOT maintain key-value assotiations!
		rsort( $float_bottoms );

		do {
			$x = $this->float_left_x( $parent->get_left(), $y );

			// Check if current float will fit into the parent box
			// OR if there's no parent boxes with constrained width (it will expanded in this case anyway)

			// small value to hide the rounding errors
			$parent_wc = $parent->get_css_property( CSS_WIDTH );
			if ( $parent->get_right() + EPSILON >= $x + $width ||
			     $parent->mayBeExpanded() ) {

				// Will fit;
				// Check if current float will intersect the existing left-floating box
				//
				$x1 = $this->float_right_x( $parent->get_right(), $y );
				if ( $x1 + EPSILON > $x + $width ) {
					return;
				};

				return;
			};

			//      print("CLEAR<br/>");

			// No, float does not fit at current level, let's try to 'clear' some previous floats
			$clear ++;

			// Check if we've cleared all existing floats; the loop will be terminated in this case, of course,
			// but we can get a notice/warning message if we'll try to access the non-existing array element
			if ( $clear <= count( $floats ) ) {
				$y = min( $y, $float_bottoms[ $clear - 1 ] );
			};

		} while ( $clear <= count( $floats ) ); // We need to check if all floats have been cleared to avoid infinite loop

		// All floats are cleared; fall back to the leftmost X coordinate
		$x = $parent->get_left();
	}

	// Get the right edge coordinate of the rightmost float in
	// current formatting context
	//
	// @return null in case of no floats exists in current context
	// numeric coordinate value otherwise
	//
	function float_right() {
		$floats =& $this->current_floats();

		if ( count( $floats ) == 0 ) {
			return null;
		}

		$right = $floats[0]->get_right_margin();
		$size  = count( $floats );
		for ( $i = 1; $i < $size; $i ++ ) {
			$right = max( $right, $floats[ $i ]->get_right_margin() );
		};

		return $right;
	}

	// Calculates the rightmost x-coordinate not covered by floats in current context
	// at the given level (y-coordinate)
	//
	// @param $x starting X coordinate (no point to the right of this allowed)
	// @param $y Y coordinate we're searching at
	// @return the rightmost X coordinate value
	//
	function float_right_x( $x, $y ) {
		$floats =& $this->current_floats();

		$size = count( $floats );
		for ( $i = 0; $i < $size; $i ++ ) {
			$float =& $floats[ $i ];

			// Process only right-floating boxes
			if ( $float->get_css_property( CSS_FLOAT ) == FLOAT_RIGHT ) {
				// Check if this float contains given Y-coordinate
				//
				// Note that top margin coordinate is inclusive but
				// bottom margin coordinate is exclusive! The cause is following:
				// - if we have several floats in one line, their top margin edge Y coordinates will be equal,
				//   so we must use agreater or equal sign to avod placing all floats at one X coordinate
				// - on the other side, if we place one float under the other, the top margin Y coordinate
				//   of bottom float will be equal to bottom margin Y coordinate of the top float and
				//   we should NOT offset tho bottom float in this case
				//

				if ( $float->get_top_margin() + EPSILON >= $y &&
				     $float->get_bottom_margin() < $y ) {
					$x = min( $x, $float->get_left_margin() );
				};
			};
		};

		return $x;
	}

	// Calculates position of right floating box (taking into account the possibility
	// of "wrapping" float to next line in case we have not enough space at current level (Y coordinate)
	//
	// @param $parent reference to a parent box
	// @param $width width of float being placed. Full width! so, extra horizontal space (padding, margins and borders) is added here too
	// @param $x [out] X coordinate of float upper-right corner
	// @param $y [in,out] Y coordinate of float upper-right corner
	//
	function float_right_xy( &$parent, $width, &$x, &$y ) {
		// Numbler of floats to clear; we need this because of the following example:
		// <div style="width: 150px; background-color: red; padding: 5px;">
		// <div style="float: left; background-color: green; height: 40px; width: 100px;">T</div>
		// <div style="float: left; background-color: yellow; height: 20px; width: 50px;">T</div>
		// <div style="float: left; background-color: cyan; height: 20px; width: 50px;">T</div>
		// in this case the third float will be rendered directly under the second, so only the
		// second float should be cleared

		$clear = 0;

		$floats =& $this->current_floats();

		// Prepare information about the float bottom coordinates
		$float_bottoms = array();
		$size          = count( $floats );
		for ( $i = 0; $i < $size; $i ++ ) {
			$float_bottoms[] = $floats[ $i ]->get_bottom_margin();
		};

		// Note that the sort function SHOULD NOT maintain key-value assotiations!
		rsort( $float_bottoms );

		do {
			$x = $this->float_right_x( $parent->get_right(), $y );

			// Check if current float will fit into the parent box
			// OR if the parent box have width: auto (it will expanded in this case anyway)
			//
			if ( $parent->get_right() + EPSILON > $x ||
			     $parent->width == WIDTH_AUTO ) {

				// Will fit;
				// Check if current float will intersect the existing left-floating box
				//
				$x1 = $this->float_left_x( $parent->get_left(), $y );
				if ( $x1 - EPSILON < $x - $width ) {
					return;
				};
			};


			// No, float does not fit at current level, let's try to 'clear' some previous floats
			$clear ++;

			// Check if we've cleared all existing floats; the loop will be terminated in this case, of course,
			// but we can get a notice/warning message if we'll try to access the non-existing array element
			if ( $clear <= count( $floats ) ) {
				$y = min( $y, $float_bottoms[ $clear - 1 ] );
			};

		} while ( $clear <= count( $floats ) ); // We need to check if all floats have been cleared to avoid infinite loop

		// All floats are cleared; fall back to the rightmost X coordinate
		$x = $parent->get_right();
	}

	function FlowContext() {
		$this->absolute_positioned = array();
		$this->fixed_positioned    = array();

		$this->viewport          = array();
		$this->_floats           = array( array() );
		$this->collapsed_margins = array( 0 );
		$this->container_uid     = array( 1 );
	}

	function get_collapsed_margin() {
		return $this->collapsed_margins[0];
	}

	function &get_viewport() {
		return $this->viewport[0];
	}

	function pop() {
		$this->pop_collapsed_margin();
		$this->pop_floats();
	}

	function pop_collapsed_margin() {
		array_shift( $this->collapsed_margins );
	}

	function pop_container_uid() {
		array_shift( $this->container_uid );
	}

	function pop_floats() {
		array_shift( $this->_floats );
	}

	function push() {
		$this->push_collapsed_margin( 0 );
		$this->push_floats();
	}

	function push_collapsed_margin( $margin ) {
		array_unshift( $this->collapsed_margins, $margin );
	}

	function push_container_uid( $uid ) {
		array_unshift( $this->container_uid, $uid );
	}

	function push_floats() {
		array_unshift( $this->_floats, array() );
	}

	function push_viewport( &$box ) {
		array_unshift( $this->viewport, $box );
	}

	function &point_in_floats( $x, $y ) {
		// Scan the floating children list of the current container box
		$floats =& $this->current_floats();
		$size   = count( $floats );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( $floats[ $i ]->contains_point_margin( $x, $y ) ) {
				return $floats[ $i ];
			}
		}

		$dummy = null;

		return $dummy;
	}

	function pop_viewport() {
		array_shift( $this->viewport );
	}

	function sort_absolute_positioned_by_z_index() {
		usort( $this->absolute_positioned, "cmp_boxes_by_z_index" );
	}
}

function cmp_boxes_by_z_index( $a, $b ) {
	$a_z = $a->get_css_property( CSS_Z_INDEX );
	$b_z = $b->get_css_property( CSS_Z_INDEX );

	if ( $a_z == $b_z ) {
		return 0;
	}

	return ( $a_z < $b_z ) ? - 1 : 1;
}

class FlowViewport {
	var $left;
	var $top;
	var $width;
	var $height;

	function FlowViewport() {
		$this->left   = 0;
		$this->top    = 0;
		$this->width  = 0;
		$this->height = 0;
	}

	function &create( &$box ) {
		$viewport       = new FlowViewport;
		$viewport->left = $box->get_left_padding();
		$viewport->top  = $box->get_top_padding();

		$padding = $box->get_css_property( CSS_PADDING );

		$viewport->width  = $box->get_width() + $padding->left->value + $padding->right->value;
		$viewport->height = $box->get_height() + $padding->top->value + $padding->bottom->value;

		return $viewport;
	}

	function get_left() {
		return $this->left;
	}

	function get_top() {
		return $this->top;
	}

	function get_height() {
		return $this->height;
	}

	function get_width() {
		return $this->width;
	}
}

// $Header: /cvsroot/html2ps/output._interface.class.php,v 1.8 2007/01/09 20:13:48 Konstantin Exp $

class OutputDriver {
	function add_link( $x, $y, $w, $h, $target ) {
	}

	function add_local_link( $left, $top, $width, $height, $anchor ) {
	}

	function circle( $x, $y, $r ) {
	}

	function clip() {
	}

	function close() {
		die( "Unoverridden 'close' method called in " . get_class( $this ) );
	}

	function closepath() {
	}

	function content_type() {
		die( "Unoverridden 'content_type' method called in " . get_class( $this ) );
	}

	function dash( $x, $y ) {
	}

	function decoration( $underline, $overline, $strikeout ) {
	}

	function error_message() {
		die( "Unoverridden 'error_message' method called in " . get_class( $this ) );
	}

	function new_form( $name ) {
	}

	function field_multiline_text( $x, $y, $w, $h, $value, $field_name ) {
	}

	function field_text( $x, $y, $w, $h, $value, $field_name ) {
	}

	function field_password( $x, $y, $w, $h, $value, $field_name ) {
	}

	function field_pushbutton( $x, $y, $w, $h ) {
	}

	function field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
	}

	function field_pushbuttonreset( $x, $y, $w, $h ) {
	}

	function field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
	}

	function field_checkbox( $x, $y, $w, $h, $name, $value ) {
	}

	function field_radio( $x, $y, $w, $h, $groupname, $value, $checked ) {
	}

	function field_select( $x, $y, $w, $h, $name, $value, $options ) {
	}

	function fill() {
	}

	function font_ascender( $name, $encoding ) {
	}

	/**
	 * Note that positive value returned by this function
	 * means offset to the BOTTOM!
	 */
	function font_descender( $name, $encoding ) {
	}

	function get_bottom() {
	}

	function image( $image, $x, $y, $scale ) {
	}

	function image_scaled( $image, $x, $y, $scale_x, $scale_y ) {
	}

	function image_ry( $image, $x, $y, $height, $bottom, $ox, $oy, $scale ) {
	}

	function image_rx( $image, $x, $y, $width, $right, $ox, $oy, $scale ) {
	}

	function image_rx_ry( $image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale ) {
	}

	function lineto( $x, $y ) {
	}

	function moveto( $x, $y ) {
	}

	function next_page( $height ) {
	}

	function release() {
	}

	function restore() {
	}

	function save() {
	}

	// Note that there's no functions for setting bold/italic style of the fonts;
	// you must keep in mind that roman/bold/italic font variations are, in fact, different
	// fonts stored in different files and it is the business of the font resolver object to
	// find the appropriate font name. Here we just re-use it.
	//
	function setfont( $name, $encoding, $size ) {
	}

	//   function setfontcore($name, $size) {}

	function setlinewidth( $x ) {
	}

	function setrgbcolor( $r, $g, $b ) {
	}

	function set_watermark( $text ) {
	}

	function show_xy( $text, $x, $y ) {
	}

	function stringwidth( $string, $name, $encoding, $size ) {
	}

	function stroke() {
	}
}

// $Header: /cvsroot/html2ps/output._generic.class.php,v 1.17 2007/05/17 13:55:13 Konstantin Exp $

class OutputDriverGeneric extends OutputDriver {
	var $media;
	var $bottom;
	var $left;
	var $width;
	var $height;

	var $_watermark;

	// Offset (in device points) of the current page from the first page.
	// Can be treated as coordinate of the bottom page edge (as first page
	// will have zero Y value at its bottom).
	// Note that ir is PAGE edge coordinate, NOT PRINTABLE AREA! If you want to get
	// the position of the lowest pixel on the page which won't be cut-off, use
	// $offset+$bottom expression, as $bottom contains bottom white margin size
	var $offset;

	var $expected_pages;
	var $current_page;

	var $filename;

	// Properties

	var $debug_boxes;
	var $show_page_border;

	var $error_message;

	var $_footnote_area_height;
	var $_footnote_count;

	var $_page_height;

	var $_postponed;

	var $anchors;

	function OutputDriverGeneric() {
		// Properties setup
		$this->set_debug_boxes( false );
		$this->set_filename( $this->mk_filename() );
		$this->set_show_page_border( false );

		$this->setFootnoteAreaHeight( 0 );
		$this->setFootnoteCount( 0 );

		$this->_postponed = array();

		$this->anchors = array();
	}

	function postpone( &$box ) {
		$this->_postponed[] =& $box;
	}

	function show_postponed() {
		$size = count( $this->_postponed );
		for ( $i = 0; $i < $size; $i ++ ) {
			$box =& $this->_postponed[ $i ];

			$this->save();
			$box->_setupClip( $this );
			$box->show_postponed( $this, true );
			$this->restore();
		};
	}

	function show_postponed_in_absolute() {
		$size = count( $this->_postponed );
		for ( $i = 0; $i < $size; $i ++ ) {
			$box =& $this->_postponed[ $i ];

			if ( $box->hasAbsolutePositionedParent() ) {

				$this->save();
				$box->_setupClip( $this );
				$box->show_postponed( $this, true );
				$this->restore();
			};
		};
	}

	function show_postponed_in_fixed() {
		$size = count( $this->_postponed );
		for ( $i = 0; $i < $size; $i ++ ) {
			$box =& $this->_postponed[ $i ];

			if ( $box->hasFixedPositionedParent() ) {
				$this->save();
				$box->_setupClip( $this );
				$box->show_postponed( $this, true );
				$this->restore();
			};
		};
	}

	function next_page( $old_page_height ) {
		$this->setFootnoteAreaHeight( 0 );
		$this->setFootnoteCount( 0 );
		$this->setPageHeight( mm2pt( $this->media->real_height() ) );

		$this->_postponed = array();
		$this->current_page ++;
	}

	function setPageHeight( $value ) {
		$this->_page_height = $value;
	}

	function getPageHeight() {
		return $this->_page_height;
	}

	function getPageMaxHeight() {
		return round( mm2pt( $this->media->real_height() ), 2 );
	}

	function getPageWidth() {
		return round( mm2pt( $this->media->real_width() ), 2 );
	}

	function getPageLeft() {
		return round( mm2pt( $this->media->margins['left'] ), 2 );
	}

	function getPageTop() {
		return round( $this->offset + mm2pt( $this->media->height() - $this->media->margins['top'] ), 2 );
	}

	function getPageBottom() {
		return $this->getPageTop() - $this->getPageHeight();
	}

	function getFootnoteTop() {
		return round( $this->offset +
		              mm2pt( $this->media->margins['bottom'] ) +
		              $this->getFootnoteAreaHeight(),
			2 );
	}

	function getFootnoteAreaHeight() {
		return $this->_footnote_area_height;
	}

	function setFootnoteAreaHeight( $value ) {
		$this->_footnote_area_height = $value;
	}

	function setFootnoteCount( $value ) {
		$this->_footnote_count = $value;
	}

	function getFootnoteCount() {
		return $this->_footnote_count;
	}

	function error_message() {
		return $this->error_message;
	}

	/**
	 * Checks if a given box should be drawn on the current page.
	 * Basically, box should be drawn if its top or bottom edge is "inside" the page "viewport"
	 *
	 * @param GenericBox $box Box we're using for check
	 *
	 * @return boolean flag indicating of any part of this box should be placed on the current page
	 */
	function contains( &$box ) {
		return $this->willContain( $box, 0 );
	}

	function willContain( &$box, $footnote_height ) {
		/**
		 * These two types of boxes are not visual and
		 * may have incorrect position
		 */
		if ( is_a( $box, 'TableSectionBox' ) ) {
			return true;
		};
		if ( is_a( $box, 'TableRowBox' ) ) {
			return true;
		};

		$top    = round( $box->get_top(), 2 );
		$bottom = round( $box->get_bottom(), 2 );

		$vp_top    = $this->getPageTop();
		$vp_bottom = max( $this->getFootnoteTop() + $footnote_height,
			$this->getPageTop() - $this->getPageHeight() );

		return ( $top > $vp_bottom &&
		         $bottom <= $vp_top );
	}

	function draw_page_border() {
		$this->setlinewidth( 1 );
		$this->setrgbcolor( 0, 0, 0 );

		$this->moveto( $this->left, $this->bottom + $this->offset );
		$this->lineto( $this->left, $this->bottom + $this->height + $this->offset );
		$this->lineto( $this->left + $this->width, $this->bottom + $this->height + $this->offset );
		$this->lineto( $this->left + $this->width, $this->bottom + $this->offset );
		$this->closepath();
		$this->stroke();
	}

	function get_expected_pages() {
		return $this->expected_pages;
	}

	function mk_filename() {
		// Check if we can use tempnam to create files (so, we have PHP version
		// with fixed bug it this function behaviour and open_basedir/environment
		// variables are not maliciously set to move temporary files out of open_basedir
		// In general, we'll try to create these files in ./temp subdir of current
		// directory, but it can be overridden by environment vars both on Windows and
		// Linux
		$filename   = tempnam( WRITER_TEMPDIR, WRITER_FILE_PREFIX );
		$filehandle = @fopen( $filename, "wb" );

		// Now, if we have had any troubles, $filehandle will be
		if ( $filehandle === false ) {
			// Note: that we definitely need to unlink($filename); - tempnam just created it for us!
			// but we can't ;) because of open_basedir (or whatelse prevents us from opening it)

			// Fallback to some stupid algorithm of filename generation
			$tries = 0;
			do {
				$filename = WRITER_TEMPDIR . DIRECTORY_SEPARATOR . WRITER_FILE_PREFIX . md5( uniqid( rand(), true ) );
				// Note: "x"-mode prevents us from re-using existing files
				// But it require PHP 4.3.2 or later
				$filehandle = @fopen( $filename, "xb" );
				$tries ++;
			} while ( ! $filehandle && $tries < WRITER_RETRIES );
		};

		if ( ! $filehandle ) {
			die( WRITER_CANNOT_CREATE_FILE );
		};

		// Release this filehandle - we'll reopen it using some gzip wrappers
		// (if they are available)
		fclose( $filehandle );

		// Remove temporary file we've just created during testing
		unlink( $filename );

		return $filename;
	}

	function get_filename() {
		return $this->filename;
	}

	function &get_font_resolver() {
		global $g_font_resolver_pdf;

		return $g_font_resolver_pdf;
	}

	function is_debug_boxes() {
		return $this->debug_boxes;
	}

	function is_show_page_border() {
		return $this->show_page_border;
	}

	function rect( $x, $y, $w, $h ) {
		$this->moveto( $x, $y );
		$this->lineto( $x + $w, $y );
		$this->lineto( $x + $w, $y + $h );
		$this->lineto( $x, $y + $h );
		$this->closepath();
	}

	function set_debug_boxes( $debug ) {
		$this->debug_boxes = $debug;
	}

	function set_expected_pages( $num ) {
		$this->expected_pages = $num;
	}

	function set_filename( $filename ) {
		$this->filename = $filename;
	}

	function set_show_page_border( $show ) {
		$this->show_page_border = $show;
	}

	function setup_clip() {
		if ( ! $GLOBALS['g_config']['debugnoclip'] ) {
			$this->moveto( $this->left, $this->bottom + $this->height + $this->offset );
			$this->lineto( $this->left + $this->width, $this->bottom + $this->height + $this->offset );
			$this->lineto( $this->left + $this->width, $this->bottom + $this->height + $this->offset - $this->getPageHeight() );
			$this->lineto( $this->left, $this->bottom + $this->height + $this->offset - $this->getPageHeight() );
			$this->clip();
		};
	}

	function prepare() {
	}

	function reset( &$media ) {
		$this->update_media( $media );
		$this->_postponed = array();

		$this->offset         = 0;
		$this->offset_delta   = 0;
		$this->expected_pages = 0;
		$this->current_page   = 0;
	}

	function &get_media() {
		return $this->media;
	}

	function update_media( &$media ) {
		$this->media  =& $media;
		$this->width  = mm2pt( $media->width() - $media->margins['left'] - $media->margins['right'] );
		$this->height = mm2pt( $media->height() - $media->margins['top'] - $media->margins['bottom'] );
		$this->left   = mm2pt( $media->margins['left'] );
		$this->bottom = mm2pt( $media->margins['bottom'] );

		$this->setPageHeight( mm2pt( $media->real_height() ) );
	}
}

// $Header: /cvsroot/html2ps/output._generic.pdf.class.php,v 1.1 2005/12/13 18:24:45 Konstantin Exp $

class OutputDriverGenericPDF extends OutputDriverGeneric {
	var $pdf_version;

	function OutputDriverGenericPDF() {
		$this->OutputDriverGeneric();
		$this->set_pdf_version( "1.3" );
	}

	function content_type() {
		return ContentType::pdf();
	}

	function get_pdf_version() {
		return $this->pdf_version;
	}

	function reset( $media ) {
		OutputDriverGeneric::reset( $media );
	}

	function set_pdf_version( $version ) {
		$this->pdf_version = $version;
	}
}

// $Header: /cvsroot/html2ps/output._generic.ps.class.php,v 1.2 2007/05/07 13:12:07 Konstantin Exp $

class OutputDriverGenericPS extends OutputDriverGeneric {
	var $language_level;
	var $image_encoder;

	function content_type() {
		return ContentType::ps();
	}

	function &get_image_encoder() {
		return $this->image_encoder;
	}

	function get_language_level() {
		return $this->language_level;
	}

	function OutputDriverGenericPS( $image_encoder ) {
		$this->OutputDriverGeneric();

		$this->set_language_level( 2 );
		$this->set_image_encoder( $image_encoder );
	}

	function reset( &$media ) {
		OutputDriverGeneric::reset( $media );
	}

	function set_image_encoder( &$encoder ) {
		$this->image_encoder = $encoder;
	}

	function set_language_level( $version ) {
		$this->language_level = $version;
	}
}

// $Header: /cvsroot/html2ps/output.pdflib.old.class.php,v 1.2 2006/11/11 13:43:53 Konstantin Exp $

require_once( HTML2PS_DIR . 'output.pdflib.class.php' );

class OutputDriverPdflibOld extends OutputDriverPdflib {
	function field_multiline_text( $x, $y, $w, $h, $value, $name ) {
	}

	function field_text( $x, $y, $w, $h, $value, $name ) {
	}

	function field_password( $x, $y, $w, $h, $value, $name ) {
	}

	function field_pushbutton( $x, $y, $w, $h ) {
	}

	function field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
	}

	function field_pushbuttonreset( $x, $y, $w, $h ) {
	}

	function field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
	}

	function field_checkbox( $x, $y, $w, $h, $name, $value, $checked ) {
	}

	function field_radio( $x, $y, $w, $h, $groupname, $value, $checked ) {
	}

	function field_select( $x, $y, $w, $h, $name, $value, $options ) {
	}

	function new_form( $name ) {
	}
}

// $Header: /cvsroot/html2ps/output.pdflib.1.6.class.php,v 1.2 2006/11/11 13:43:53 Konstantin Exp $

require_once( HTML2PS_DIR . 'output.pdflib.class.php' );

class PDFLIBForm {
	var $_name;

	function PDFLIBForm( $name /*, $submit_action, $reset_action */ ) {
		$this->_name = $name;
	}

	function name() {
		return $this->_name;
	}
}

class OutputDriverPdflib16 extends OutputDriverPdflib {
	function field_multiline_text( $x, $y, $w, $h, $value, $name ) {
		$font = $this->_control_font();
		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $name ),
			"textfield",
			sprintf( "currentvalue {%s} defaultvalue {%s} font {%s} fontsize {auto} multiline {true}",
				$value,
				$value,
				$font ) );
	}

	function field_text( $x, $y, $w, $h, $value, $name ) {
		$font = $this->_control_font();
		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $name ),
			"textfield",
			sprintf( "currentvalue {%s} defaultvalue {%s} font {%s} fontsize {auto}",
				$value,
				$value,
				$font ) );
	}

	function field_password( $x, $y, $w, $h, $value, $name ) {
		$font = $this->_control_font();
		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $name ),
			"textfield",
			sprintf( "currentvalue {%s} font {%s} fontsize {auto} password {true}", $value, $font ) );
	}

	function field_pushbutton( $x, $y, $w, $h ) {
		$font = $this->_control_font();

		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( sprintf( "___Button%s", md5( time() . rand() ) ) ),
			"pushbutton",
			sprintf( "font {%s} fontsize {auto} caption {%s}",
				$font,
				" " ) );
	}

	function field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
		$font = $this->_control_font();

		$action = pdf_create_action( $this->pdf,
			"SubmitForm",
			sprintf( "exportmethod {html} url=%s", $actionURL ) );

		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $field_name ),
			"pushbutton",
			sprintf( "action {activate %s} font {%s} fontsize {auto} caption {%s}",
				$action,
				$font,
				" " ) );
	}

	function field_pushbuttonreset( $x, $y, $w, $h ) {
		$font = $this->_control_font();

		$action = pdf_create_action( $this->pdf,
			"ResetForm",
			sprintf( "" ) );

		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( sprintf( "___ResetButton%d", $action ) ),
			"pushbutton",
			sprintf( "action {activate %s} font {%s} fontsize {auto} caption {%s}",
				$action,
				$font,
				" " ) );
	}

	function field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
		$font = $this->_control_font();

		$action = pdf_create_action( $this->pdf,
			"SubmitForm",
			sprintf( "exportmethod {html} url=%s", $actionURL ) );

		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $field_name ),
			"pushbutton",
			sprintf( "action {activate %s} font {%s} fontsize {auto} caption {%s}",
				$action,
				$font,
				" " ) );
	}

	function field_checkbox( $x, $y, $w, $h, $name, $value, $checked ) {
		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $name ),
			"checkbox",
			sprintf( "buttonstyle {cross} currentvalue {%s} defaultvalue {%s} itemname {%s}",
				$checked ? $value : "Off",
				$checked ? $value : "Off",
				$value ) );
	}

	function field_radio( $x, $y, $w, $h, $groupname, $value, $checked ) {
		$fqgn = $this->_fqn( $groupname, true );

		if ( ! isset( $this->_radiogroups[ $fqgn ] ) ) {
			$this->_radiogroups[ $fqgn ] = pdf_create_fieldgroup( $this->pdf, $fqgn, "fieldtype=radiobutton" );
		};

		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			sprintf( "%s.%s", $fqgn, $value ),
			"radiobutton",
			sprintf( "buttonstyle {circle} currentvalue {%s} defaultvalue {%s} itemname {%s}",
				$checked ? $value : "Off",
				$checked ? $value : "Off",
				$value ) );
	}

	function field_select( $x, $y, $w, $h, $name, $value, $options ) {
		$items_str = "";
		$text_str  = "";
		foreach ( $options as $option ) {
			$items_str .= sprintf( "%s ", $option[0] );
			$text_str  .= sprintf( "%s ", $option[1] );
		};

		$font = $this->_control_font();
		pdf_create_field( $this->pdf,
			$x, $y, $x + $w, $y - $h,
			$this->_fqn( $name ),
			"combobox",
			sprintf( "currentvalue {%s} defaultvalue {%s} font {%s} fontsize {auto} itemnamelist {%s} itemtextlist {%s}",
				$value,
				$value,
				$font,
				$items_str,
				$text_str ) );
	}

	function new_form( $name ) {
		$this->_forms[] = new PDFLIBForm( $name );

		pdf_create_fieldgroup( $this->pdf, $name, "fieldtype=mixed" );
	}

	/* private routines */

	function _control_font() {
		return pdf_load_font( $this->pdf, "Helvetica", "winansi", "embedding=true subsetting=false" );
	}

	function _lastform() {
		if ( count( $this->_forms ) == 0 ) {
			/**
			 * Handle invalid HTML; if we've met an input control outside the form,
			 * generate a new form with random name
			 */

			$name = sprintf( "AnonymousFormObject_%u", md5( rand() . time() ) );

			$this->_forms[] = new PDFLIBForm( $name );
			pdf_create_fieldgroup( $this->pdf, $name, "fieldtype=mixed" );

			error_log( sprintf( "Anonymous form generated with name %s; check your HTML for validity",
				$name ) );
		};

		return $this->_forms[ count( $this->_forms ) - 1 ];
	}

	function _valid_name( $name ) {
		if ( empty( $name ) ) {
			return false;
		};

		return true;
	}

	function _fqn( $name, $allowexisting = false ) {
		if ( ! $this->_valid_name( $name ) ) {
			$name = uniqid( "AnonymousFormFieldObject_" );
			error_log( sprintf( "Anonymous field generated with name %s; check your HTML for validity",
				$name ) );
		};

		$lastform = $this->_lastform();
		$fqn      = sprintf( "%s.%s",
			$lastform->name(),
			$name );

		if ( array_search( $fqn, $this->_field_names ) === false ) {
			$this->_field_names[] = $fqn;
		} elseif ( ! $allowexisting ) {
			error_log( sprintf( "Interactive form '%s' already contains field named '%s'",
				$lastform->name(),
				$name ) );
			$fqn .= md5( rand() . time() );
		};

		return $fqn;
	}
}

// $Header: /cvsroot/html2ps/output.fpdf.class.php,v 1.27 2007/05/17 13:55:13 Konstantin Exp $

require_once( HTML2PS_DIR . 'pdf.fpdf.php' );
require_once( HTML2PS_DIR . 'pdf.fpdf.makefont.php' );

// require_once(HTML2PS_DIR.'fpdf/font/makefont/makefont.php');

class OutputDriverFPDF extends OutputDriverGenericPDF {
	var $pdf;
	var $locallinks;
	var $cx;
	var $cy;

	function OutputDriverFPDF() {
		$this->OutputDriverGenericPDF();
	}

	function add_link( $x, $y, $w, $h, $target ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_link_external( $x, $y, $w, $h, $target );
	}

	function add_local_link( $left, $top, $width, $height, $anchor ) {
		if ( ! isset( $this->locallinks[ $anchor->name ] ) ) {
			$x = 0;
			$y = $anchor->y;
			$this->_coords2pdf( $x, $y );

			$this->locallinks[ $anchor->name ] = $this->pdf->AddLink();
			$this->pdf->SetLink( $this->locallinks[ $anchor->name ],
				$y - 20,
				$anchor->page );
		};

		$x = $left;
		$y = $top - $this->offset;
		$this->_coords2pdf( $x, $y );

		$this->pdf->add_link_internal( $x,
			$y,
			$width,
			$height,
			$this->locallinks[ $anchor->name ] );
	}

	// UNfortunately, FPDF do not provide any coordinate-space transformation routines
	// so we need to reverse the Y-axis manually
	function _coords2pdf( &$x, &$y ) {
		$y = mm2pt( $this->media->height() ) - $y;
	}

	// Annotation coordinates are always interpreted in the default (untranslated!)
	// user space. (See PDF Reference 1.6 Section 8.4 p.575)
	function _coords2pdf_annotation( &$x, &$y ) {
		$y = $y - $this->offset;
		$this->_coords2pdf( $x, $y );
	}

	function decoration( $underline, $overline, $strikeout ) {
		// underline
		$this->pdf->SetDecoration( $underline, $overline, $strikeout );
	}

	function circle( $x, $y, $r ) {
		$this->pdf->circle( $x, $y, $r );
	}

	function clip() {
		$this->pdf->Clip();
	}

	function close() {
		$this->pdf->Output( $this->get_filename() );
	}

	function closepath() {
		$this->pdf->closepath();
	}

	function dash( $x, $y ) {
		$this->pdf->SetDash( ceil( $x ), ceil( $y ) );
	}

	function get_bottom() {
		return $this->bottom + $this->offset;
	}

	function field_multiline_text( $x, $y, $w, $h, $value, $field_name ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_multiline_text( $x, $y, $w, $h, $value, $field_name );
	}

	function field_text( $x, $y, $w, $h, $value, $field_name ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_text( $x, $y, $w, $h, $value, $field_name );
	}

	function field_password( $x, $y, $w, $h, $value, $field_name ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_password( $x, $y, $w, $h, $value, $field_name );
	}

	function field_pushbutton( $x, $y, $w, $h ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_pushbutton( $x, $y, $w, $h );
	}

	function field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL );
	}

	function field_pushbuttonreset( $x, $y, $w, $h ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_pushbuttonreset( $x, $y, $w, $h );
	}

	function field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL );
	}

	function field_checkbox( $x, $y, $w, $h, $name, $value, $checked ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_checkbox( $x, $y, $w, $h, $name, $value, $checked );
	}

	function field_radio( $x, $y, $w, $h, $groupname, $value, $checked ) {
		static $generated_group_index = 0;
		if ( is_null( $groupname ) ) {
			$generated_group_index ++;
			$groupname = "__generated_group_" . $generated_group_index;
		};

		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_radio( $x, $y, $w, $h, $groupname, $value, $checked );
	}

	function field_select( $x, $y, $w, $h, $name, $value, $options ) {
		$this->_coords2pdf_annotation( $x, $y );
		$this->pdf->add_field_select( $x, $y, $w, $h, $name, $value, $options );
	}

	function fill() {
		$this->pdf->Fill();
	}

	function findfont( $name, $encoding ) {
		// Todo: encodings handling
		return $name;
	}

	function font_ascender( $name, $encoding ) {
		return $this->pdf->GetFontAscender( $name, $encoding );
	}

	function font_descender( $name, $encoding ) {
		return $this->pdf->GetFontDescender( $name, $encoding );
	}

	function image( $image, $x, $y, $scale ) {
		$tmpname = $this->_mktempimage( $image );

		$this->_coords2pdf( $x, $y );
		$this->pdf->Image( $tmpname,
			$x,
			$y - $image->sy() * $scale,
			$image->sx() * $scale,
			$image->sy() * $scale );

		unlink( $tmpname );
	}

	function image_rx( $image, $x, $y, $width, $right, $ox, $oy, $scale ) {
		$tmpname = $this->_mktempimage( $image );

		// Fill part to the right
		$cx = $x;
		while ( $cx < $right ) {
			$tx = $cx;
			$ty = $y + px2pt( $image->sy() );
			$this->_coords2pdf( $tx, $ty );
			$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
			$cx += $width;
		};

		// Fill part to the left
		$cx = $x;
		while ( $cx + $width >= $x - $ox ) {
			$tx = $cx - $width;
			$ty = $y + px2pt( $image->sy() );
			$this->_coords2pdf( $tx, $ty );
			$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
			$cx -= $width;
		};

		unlink( $tmpname );
	}

	function image_rx_ry( $image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale ) {
		$tmpname = $this->_mktempimage( $image );

		// Fill bottom-right quadrant
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$cx = $x;
			while ( $cx < $right ) {
				$tx = $cx;
				$ty = $cy + $height;
				$this->_coords2pdf( $tx, $ty );

				$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
				$cx += $width;
			};
			$cy -= $height;
		}

		// Fill bottom-left quadrant
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$cx = $x;
			while ( $cx + $width > $x - $ox ) {
				$tx = $cx;
				$ty = $cy;
				$this->_coords2pdf( $tx, $ty );
				$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
				$cx -= $width;
			};
			$cy -= $height;
		}

		// Fill top-right quadrant
		$cy = $y;
		while ( $cy < $y + $oy ) {
			$cx = $x;
			while ( $cx < $right ) {
				$tx = $cx;
				$ty = $cy;
				$this->_coords2pdf( $tx, $ty );
				$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
				$cx += $width;
			};
			$cy += $height;
		}

		// Fill top-left quadrant
		$cy = $y;
		while ( $cy < $y + $oy ) {
			$cx = $x;
			while ( $cx + $width > $x - $ox ) {
				$tx = $cx;
				$ty = $cy;
				$this->_coords2pdf( $tx, $ty );
				$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
				$cx -= $width;
			};
			$cy += $height;
		}

		unlink( $tmpname );
	}


	function image_ry( $image, $x, $y, $height, $bottom, $ox, $oy, $scale ) {
		$tmpname = $this->_mktempimage( $image );

		// Fill part to the bottom
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$tx = $x;
			$ty = $cy + px2pt( $image->sy() );
			$this->_coords2pdf( $tx, $ty );
			$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
			$cy -= $height;
		};

		// Fill part to the top
		$cy = $y;
		while ( $cy - $height < $y + $oy ) {
			$tx = $x;
			$ty = $cy + px2pt( $image->sy() );
			$this->_coords2pdf( $tx, $ty );
			$this->pdf->Image( $tmpname, $tx, $ty, $image->sx() * $scale, $image->sy() * $scale, "png" );
			$cy += $height;
		};

		unlink( $tmpname );
	}

	function image_scaled( $image, $x, $y, $scale_x, $scale_y ) {
		$tmpname = $this->_mktempimage( $image );

		$this->_coords2pdf( $x, $y );
		$this->pdf->Image( $tmpname, $x, $y - $image->sy() * $scale_y, $image->sx() * $scale_x, $image->sy() * $scale_y, "png" );
		unlink( $tmpname );
	}

	function lineto( $x, $y ) {
		$this->_coords2pdf( $x, $y );
		$this->pdf->lineto( $x, $y );
	}

	function moveto( $x, $y ) {
		$this->_coords2pdf( $x, $y );
		$this->pdf->moveto( $x, $y );
	}

	function new_form( $name ) {
		$this->pdf->add_form( $name );
	}

	function next_page( $height ) {
		$this->pdf->AddPage( mm2pt( $this->media->width() ), mm2pt( $this->media->height() ) );

		// Calculate coordinate of the next page bottom edge
		$this->offset -= $height - $this->offset_delta;

		// Reset the "correction" offset to it normal value
		// Note: "correction" offset is an offset value required to avoid page breaking
		// in the middle of text boxes
		$this->offset_delta = 0;

		$this->pdf->Translate( 0, - $this->offset );

		parent::next_page( $height );
	}

	function reset( &$media ) {
		parent::reset( $media );

		$this->pdf =& new FPDF( 'P', 'pt', array( mm2pt( $media->width() ), mm2pt( $media->height() ) ) );

		if ( defined( 'DEBUG_MODE' ) ) {
			$this->pdf->SetCompression( false );
		} else {
			$this->pdf->SetCompression( true );
		};

		$this->cx = 0;
		$this->cy = 0;

		$this->locallinks = array();
	}

	function restore() {
		$this->pdf->Restore();
	}

	function save() {
		$this->pdf->Save();
	}

	function setfont( $name, $encoding, $size ) {
		$this->pdf->SetFont( $this->findfont( $name, $encoding ), $encoding, $size );

		return true;
	}

	function setlinewidth( $x ) {
		$this->pdf->SetLineWidth( $x );
	}

	// PDFLIB wrapper functions
	function setrgbcolor( $r, $g, $b ) {
		$this->pdf->SetDrawColor( $r * 255, $g * 255, $b * 255 );
		$this->pdf->SetFillColor( $r * 255, $g * 255, $b * 255 );
		$this->pdf->SetTextColor( $r * 255, $g * 255, $b * 255 );
	}

	function show_xy( $text, $x, $y ) {
		$this->_coords2pdf( $x, $y );

		$this->pdf->Text( $x, $y, $text );
	}

	function stroke() {
		$this->pdf->stroke();
	}

	function stringwidth( $string, $name, $encoding, $size ) {
		$this->setfont( $name, $encoding, $size );
		$width = $this->pdf->GetStringWidth( $string );

		return $width;
	}

	function _show_watermark( $watermark ) {
		$this->pdf->SetFont( "Helvetica", "iso-8859-1", 100 );

		$x = $this->left + $this->width / 2;
		$y = $this->bottom + $this->height / 2 - $this->offset;
		$this->_coords2pdf( $x, $y );

		$this->pdf->SetTextRendering( 1 );
		$this->pdf->SetDecoration( false, false, false );
		$this->pdf->Translate( $x, $y );
		$this->pdf->Rotate( 60 );

		$tx = - $this->pdf->GetStringWidth( $watermark ) / 2;
		$ty = - 50;
		$this->_coords2pdf( $tx, $ty );

		// By default, "watermark" is rendered in black color
		$this->setrgbcolor( 0, 0, 0 );

		$this->pdf->Text( $tx,
			$ty,
			$watermark );
	}

	function _mktempimage( $image ) {
		$tempnam = tempnam( WRITER_TEMPDIR, WRITER_FILE_PREFIX );

		switch ( $image->get_type() ) {
			case 'image/png':
				$filename = $tempnam . '.png';
				imagepng( $image->get_handle(), $filename );
				break;

			case 'image/jpeg':
			default:
				$filename = $tempnam . '.jpg';
				imagejpeg( $image->get_handle(), $filename, 100 );
				break;
		}

		unlink( $tempnam );

		return $filename;
	}
}

// $Header: /cvsroot/html2ps/output.fastps.class.php,v 1.18 2007/05/17 13:55:13 Konstantin Exp $

define( 'FASTPS_STATUS_DOCUMENT_INITIALIZED', 0 );
define( 'FASTPS_STATUS_OUTPUT_STARTED', 1 );
define( 'FASTPS_STATUS_OUTPUT_TERMINATED', 2 );

class OutputDriverFastPS extends OutputDriverGenericPS {
	var $found_fonts;
	var $used_encodings;
	var $font_factory;
	var $status;

	var $overline;
	var $underline;
	var $linethrough;

	function OutputDriverFastPS( &$image_encoder ) {
		$this->OutputDriverGenericPS( $image_encoder );
	}

	function add_link( $x, $y, $w, $h, $target ) {
		$this->write( sprintf( "[ /Rect [ %.2f %.2f %.2f %.2f ] /Action << /Subtype /URI /URI (%s) >> /Border [0 0 0] /Subtype /Link /ANN pdfmark\n",
			$x, $y, $x + $w, $y - $h, $this->_string( $target ) ) );
	}

	function add_local_link( $left, $top, $width, $height, $anchor ) {
		$this->write( sprintf( "[ /Rect [ %.2f %.2f %.2f %.2f ] /Page %d /View [ /XYZ null %.2f null ] /Border [0 0 0] /Subtype /Link /ANN pdfmark\n",
			$left, $top, $left + $width, $top - $height, $anchor->page, $anchor->y ) );
	}

	function circle( $x, $y, $r ) {
		$this->moveto( $x, $y );
		$this->write( sprintf( "%.2f %.2f %.2f 0 360 arc\n", $x, $y, $r ) );
	}

	function clip() {
		$this->write( "clip newpath\n" );
	}

	function close() {
		$this->_terminate_output();
		fclose( $this->data );
	}

	function closepath() {
		$this->write( "closepath\n" );
	}

	function dash( $x, $y ) {
		$this->write( sprintf( "[%.2f %.2f] 0 setdash\n", $x, $y ) );
	}

	function decoration( $underline, $overline, $linethrough ) {
		$this->underline   = $underline;
		$this->overline    = $overline;
		$this->linethrough = $linethrough;
	}

	function fill() {
		$this->write( "fill\n" );
	}

	function _findfont( $name, $encoding ) {
		$font =& $this->font_factory->get_type1( $name, $encoding );
		if ( is_null( $font ) ) {
			$this->error_message .= $this->font_factory->error_message();
			$dummy               = null;

			return $dummy;
		};

		if ( ! isset( $this->used_encodings[ $encoding ] ) ) {
			$this->used_encodings[ $encoding ] = true;

			$manager = ManagerEncoding::get();
			$this->_write_document_prolog( $manager->get_ps_encoding_vector( $encoding ) );
			$this->_write_document_prolog( "\n" );
		};

		$fontname = $font->name();
		if ( ! isset( $this->found_fonts[ $fontname ] ) ) {
			$this->found_fonts[ $fontname ] = true;

			$this->_write_document_prolog( "/$fontname /$name $encoding findfont-enc def\n" );
		};

		return $font;
	}

	// @return 'null' in case of error or ascender fraction of font-size
	//
	function font_ascender( $name, $encoding ) {
		$font = $this->_findfont( $name, $encoding );
		if ( is_null( $font ) ) {
			return null;
		};

		return $font->ascender() / 1000;
	}

	// @return 'null' in case of error or ascender fraction of font-size
	//
	function font_descender( $name, $encoding ) {
		$font = $this->_findfont( $name, $encoding );
		if ( is_null( $font ) ) {
			return null;
		};

		return - $font->descender() / 1000;
	}

	function get_bottom() {
		return $this->bottom + $this->offset;
	}

	function &get_font_resolver() {
		global $g_font_resolver;

		return $g_font_resolver;
	}

	function image( $image, $x, $y, $scale ) {
		$image_encoder = $this->get_image_encoder();
		$id            = $image_encoder->auto( $this, $image, $size_x, $size_y, $tcolor, $image, $mask );
		$init          = "image-" . $id . "-init";

		$this->moveto( $x, $y );
		$this->write( sprintf( "%.2f %.2f %s %s {%s} %d %d image-create image-show\n",
			$size_x * $scale,
			$size_y * $scale,
			( $mask !== "" ? $mask : "/null" ),
			$image,
			$init,
			$size_y,
			$size_x ) );
	}

	function image_scaled( $image, $x, $y, $scale_x, $scale_y ) {
		$image_encoder = $this->get_image_encoder();
		$id            = $image_encoder->auto( $this, $image, $size_x, $size_y, $tcolor, $image, $mask );
		$init          = "image-" . $id . "-init";

		$this->moveto( $x, $y );
		$this->write( sprintf( "%.2f %.2f %s %s {%s} %d %d image-create image-show\n",
			$size_x * $scale_x,
			$size_y * $scale_y,
			( $mask !== "" ? $mask : "/null" ),
			$image,
			$init,
			$size_y,
			$size_x ) );
	}

	function image_ry( $image, $x, $y, $height, $bottom, $ox, $oy, $scale ) {
		$image_encoder = $this->get_image_encoder();
		$id            = $image_encoder->auto( $this, $image, $size_x, $size_y, $tcolor, $image, $mask );
		$init          = "image-" . $id . "-init";

		$this->write( sprintf( "%.2f %.2f %.2f %.2f %.2f %.2f %.2f %s %s {%s} %d %d image-create image-show-repeat-y\n",
			$scale, $oy, $ox, $bottom, $height, $y, $x,
			( $mask !== "" ? $mask : "/null" ),
			$image,
			$init,
			$size_y,
			$size_x ) );
	}

	function image_rx( $image, $x, $y, $width, $right, $ox, $oy, $scale ) {
		$image_encoder = $this->get_image_encoder();
		$id            = $image_encoder->auto( $this, $image, $size_x, $size_y, $tcolor, $image, $mask );
		$init          = "image-" . $id . "-init";

		$this->write( sprintf( "%.2f %.2f %.2f %.2f %.2f %.2f %.2f %s %s {%s} %d %d image-create image-show-repeat-x\n",
			$scale, $oy, $ox, $right, $width, $y, $x,
			( $mask !== "" ? $mask : "/null" ),
			$image,
			$init,
			$size_y,
			$size_x ) );
	}

	function image_rx_ry( $image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale ) {
		$image_encoder = $this->get_image_encoder();
		$id            = $image_encoder->auto( $this, $image, $size_x, $size_y, $tcolor, $image, $mask );
		$init          = "image-" . $id . "-init";

		$this->write( sprintf( "%.2f %.2f %.2f %.2f %.2f %.2f %.2f  %.2f %.2f %s %s {%s} %d %d image-create image-show-repeat-xy\n",
			$scale, $oy, $ox, $bottom, $right, $height, $width, $y, $x,
			( $mask !== "" ? $mask : "/null" ),
			$image,
			$init,
			$size_y,
			$size_x ) );
	}

	function lineto( $x, $y ) {
		$data = sprintf( "%.2f %.2f lineto\n", $x, $y );
		$this->write( $data );
	}

	function moveto( $x, $y ) {
		$data = sprintf( "%.2f %.2f moveto\n", $x, $y );
		$this->write( $data );
	}

	function next_page( $height ) {
		if ( $this->current_page > 0 ) {
			$this->write( "showpage\n" );
		};

		$this->offset -= $height - $this->offset_delta;

		// Reset the "correction" offset to it normal value
		// Note: "correction" offset is an offset value required to avoid page breaking
		// in the middle of text boxes
		$this->offset_delta = 0;

		$this->write( sprintf( "%%%%Page: %d %d\n", $this->current_page + 1, $this->current_page + 1 ) );
		$this->write( "%%BeginPageSetup\n" );
		$this->write( sprintf( "initpage\n" ) );
		$this->write( sprintf( "0 %.2f translate\n", - $this->offset ) );
		$this->write( "0 0 0 setrgbcolor\n" );
		$this->write( "%%EndPageSetup\n" );

		parent::next_page( $height );
	}

	function reset( &$media ) {
		OutputDriverGenericPS::reset( $media );

		$this->media =& $media;
		$this->data  = fopen( $this->get_filename(), "wb" );

		// List of fonts names which already had generated findfond PS code
		$this->found_fonts = array();

		$this->used_encodings = array();

		$this->overline    = false;
		$this->underline   = false;
		$this->linethrough = false;

		// A font class factory
		$this->font_factory =& new FontFactory;

		$this->_document_body   = '';
		$this->_document_prolog = '';

		$this->status = FASTPS_STATUS_DOCUMENT_INITIALIZED;
	}

	function restore() {
		$this->write( "grestore\n" );
	}

	function save() {
		$this->write( "gsave\n" );
	}

	// @return true normally or null in case of error
	//
	function setfont( $name, $encoding, $size ) {
		$this->fontsize    = $size;
		$this->currentfont = $this->_findfont( $name, $encoding );

		if ( is_null( $this->currentfont ) ) {
			return null;
		};

		$this->write( sprintf( "%s %.2f scalefont setfont\n", $this->currentfont->name(), $size ) );

		return true;
	}

	function setlinewidth( $x ) {
		$data = sprintf( "%.2f setlinewidth\n", $x );
		$this->write( $data );
	}

	function setrgbcolor( $r, $g, $b ) {
		$data = sprintf( "%.2f %.2f %.2f setrgbcolor\n", $r, $g, $b );
		$this->write( $data );
	}

	function show_xy( $text, $x, $y ) {
		if ( trim( $text ) !== '' ) {
			$this->moveto( $x, $y );
			$this->write( "(" . $this->_string( $text ) . ") show\n" );
		};

		$width = Font::points( $this->fontsize, $this->currentfont->stringwidth( $text ) );
		if ( $this->overline ) {
			$this->_show_overline( $x, $y, $width, $this->fontsize );
		};
		if ( $this->underline ) {
			$this->_show_underline( $x, $y, $width, $this->fontsize );
		};
		if ( $this->linethrough ) {
			$this->_show_linethrough( $x, $y, $width, $this->fontsize );
		};
	}

	function stringwidth( $string, $name, $encoding, $size ) {
		$font =& $this->font_factory->get_type1( $name, $encoding );

		if ( is_null( $font ) ) {
			$this->error_message .= $this->font_factory->error_message();
			$dummy               = null;

			return $dummy;
		};

		return Font::points( $size, $font->stringwidth( $string ) );
	}

	function stroke() {
		$this->write( "stroke\n" );
	}

	function write( $string ) {
		if ( $this->status == FASTPS_STATUS_DOCUMENT_INITIALIZED ) {
			$this->_start_output();
		};

		$this->_document_body .= $string;
	}

	function _write_document_prolog( $string ) {
		$this->_document_prolog .= $string;
	}

	function _show_line( $x, $y, $width, $height, $up, $ut ) {
		$this->setlinewidth( $ut );
		$this->moveto( $x, $y + $up );
		$this->lineto( $x + $width, $y + $up );
		$this->stroke();
	}

	function _show_underline( $x, $y, $width, $height ) {
		$up = Font::points( $this->fontsize, $this->currentfont->underline_position() );
		$ut = Font::points( $this->fontsize, $this->currentfont->underline_thickness() );

		$this->_show_line( $x, $y, $width, $height, $up, $ut );
	}

	function _show_overline( $x, $y, $width, $height ) {
		$up = Font::points( $this->fontsize, $this->currentfont->overline_position() );
		$ut = Font::points( $this->fontsize, $this->currentfont->underline_thickness() );

		$this->_show_line( $x, $y, $width, $height, $up, $ut );
	}

	function _show_linethrough( $x, $y, $width, $height ) {
		$up = Font::points( $this->fontsize, $this->currentfont->linethrough_position() );
		$ut = Font::points( $this->fontsize, $this->currentfont->underline_thickness() );

		$this->_show_line( $x, $y, $width, $height, $up, $ut );
	}

	function _start_output() {
		$this->status = FASTPS_STATUS_OUTPUT_STARTED;
	}

	function _terminate_output() {
		/**
		 * Prepare the PS file header
		 * Note that %PS-Adobe-3.0 refers to DSC version, NOT language level
		 */
		$header = file_get_contents( HTML2PS_DIR . 'postscript/fastps.header.ps' );

		global $g_config;
		$header = preg_replace( "/##PS2PDF##/",
			( $g_config['ps2pdf'] && $g_config['transparency_workaround'] ) ? "/ps2pdf-transparency-hack true def" : "/ps2pdf-transparency-hack false def", $header );
		$header = preg_replace( "/##TRANSPARENCY##/", ( $g_config['transparency_workaround'] ) ? "/no-transparency-output true def" : "/no-transparency-output false def", $header );
		$header = preg_replace( "/##PAGES##/", $this->expected_pages, $header );

		$header = preg_replace( "/##BBOX##/", $this->media->to_bbox(), $header );
		$header = preg_replace( "/##MEDIA##/", $this->media->to_ps(), $header );

		$header = preg_replace( "/##PROLOG##/", $this->_document_prolog, $header );

		fwrite( $this->data, $header );
		fwrite( $this->data, "\n" );
		fwrite( $this->data, $this->_document_body );

		$footer = file_get_contents( HTML2PS_DIR . 'postscript/fastps.footer.ps' );
		fwrite( $this->data, $footer );
	}

	function _show_watermark() {
	}

	/**
	 * Protected output-specific methods
	 */

	/**
	 * Escapes special Postscript symbols '(',')' and '%' inside a text string
	 */
	function _string( $str ) {
		$str = str_replace( "\\", "\\\\", $str );
		$str = str_replace( array( "(", ")", "%" ), array( "\\(", "\\)", "\\%" ), $str );

		// Replace characters having 8-bit set with their octal representation
		for ( $i = 0; $i < strlen( $str ); $i ++ ) {
			if ( ord( $str{$i} ) > 127 ) {
				$str = substr_replace( $str, sprintf( "\\%o", ord( $str{$i} ) ), $i, 1 );
				$i   += 3;
			};
		};

		return $str;
	}
}


class OutputDriverFastPSLevel2 extends OutputDriverFastPS {
	function OutputDriverFastPSLevel2( &$image_encoder ) {
		$this->OutputDriverFastPS( $image_encoder );
	}

	function image( $image, $x, $y, $scale ) {
		$this->image_scaled( $image, $x, $y, $scale, $scale );
	}

	function image_scaled( $image, $x, $y, $scale_x, $scale_y ) {
		$image_encoder = $this->get_image_encoder();
		$lines         = $image_encoder->by_lines( $image, $size_x, $size_y );

		$offset = 0;
		foreach ( $lines as $line ) {
			$this->moveto( $x, $y - $offset * $scale_y );
			$this->write( sprintf( "gsave\n" ) );
			$this->write( sprintf( " << /ImageType 1 /Width %d /Height 1 /BitsPerComponent 8 /Decode [0 1 0 1 0 1] /ImageMatrix %s /DataSource %s >> image\n",
				$size_x,
				sprintf( "matrix currentpoint translate %.2f %.2f scale 0 %.2f translate",
					$scale_x, $scale_y,
					$size_y
				),
				"currentfile /ASCIIHexDecode filter" ) );
			$this->write( sprintf( "%s\n", $line ) );
			$this->write( sprintf( "grestore\n" ) );

			$offset ++;
		};
	}

	function image_ry( $image, $x, $y, $height, $bottom, $ox, $oy, $scale ) {
		// Fill part to the bottom
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$this->image( $image, $x, $cy, $scale );
			$cy -= $height;
		};

		// Fill part to the top
		$cy = $y;
		while ( $cy - $height < $y + $oy ) {
			$this->image( $image, $x, $cy, $scale );
			$cy += $height;
		};
	}

	function image_rx( $image, $x, $y, $width, $right, $ox, $oy, $scale ) {
		// Fill part to the right
		$cx = $x;
		while ( $cx < $right ) {
			$this->image( $image, $cx, $y, $scale );
			$cx += $width;
		};

		// Fill part to the left
		$cx = $x;
		while ( $cx + $width >= $x - $ox ) {
			$this->image( $image, $cx - $width, $y, $scale );
			$cx -= $width;
		};
	}

	function image_rx_ry( $image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale ) {
		// Fill bottom-right quadrant
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$cx = $x;
			while ( $cx < $right ) {
				$this->image( $image, $cx, $cy, $scale );
				$cx += $width;
			};
			$cy -= $height;
		}

		// Fill bottom-left quadrant
		$cy = $y;
		while ( $cy + $height > $bottom ) {
			$cx = $x;
			while ( $cx + $width > $x - $ox ) {
				$this->image( $image, $cx, $cy, $scale );
				$cx -= $width;
			};
			$cy -= $height;
		}

		// Fill top-right quadrant
		$cy = $y;
		while ( $cy < $y + $oy ) {
			$cx = $x;
			while ( $cx < $right ) {
				$this->image( $image, $cx, $cy, $scale );
				$cx += $width;
			};
			$cy += $height;
		}

		// Fill top-left quadrant
		$cy = $y;
		while ( $cy < $y + $oy ) {
			$cx = $x;
			while ( $cx + $width > $x - $ox ) {
				$this->image( $image, $cx, $cy, $scale );
				$cx -= $width;
			};
			$cy += $height;
		}
	}
}


// $Header: /cvsroot/html2ps/output.png.class.php,v 1.7 2007/05/07 13:12:07 Konstantin Exp $

require_once( HTML2PS_DIR . 'ot.class.php' );
require_once( HTML2PS_DIR . 'path.php' );
//require_once(HTML2PS_DIR . 'font_factory.class.php');

/**
 * TODO: of course, it is not 'real' affine transformation;
 * it is just a compatibility hack
 */
class AffineTransform {
	var $_y_offset;
	var $_x_scale;
	var $_y_scale;

	function AffineTransform( $y_offset, $x_scale, $y_scale ) {
		$this->_y_offset = $y_offset;
		$this->_x_scale  = $x_scale;
		$this->_y_scale  = $y_scale;
	}

	function apply( &$x, &$y ) {
		$x = floor( $x * $this->_x_scale );
		$y = floor( $this->_y_offset - $y * $this->_y_scale );
	}
}

class OutputDriverPNG extends OutputDriverGeneric {
	var $_image;

	var $_clipping;

	var $_media;
	var $_heightPixels;
	var $_widthPixels;
	var $_color;
	var $_font;
	var $_path;

	/**
	 * This variable  contains an  array of clipping  contexts. Clipping
	 * context describes the "active area" and "base" image (image which
	 * will take the changes drawn in clipped area).
	 *
	 * As GD does not support  clipping natively, when new clipping area
	 * is  defined,  we  create  new  image. When  clipping  context  is
	 * terminated (i.e. by establishing new clipping context, by call to
	 * 'restore' or by finishing the image output), only area bounded by
	 * clipping region  is copied  to the "base"  image. Note  that This
	 * will  increase the  memory  consumption, as  we'll  need to  keep
	 * several active images at once.
	 */
	var $_clip;

	function _restoreColor() {
		imagecolordeallocate( $this->_image, $this->_color[0] );
		array_shift( $this->_color );
	}

	function _restoreClip() {
		/**
		 * As clipping context images have the same size/scale, we may use
		 * the simplest/fastest image copying function
		 */
		$clip = $this->_clipping[0];
		imagecopy( $clip['image'],
			$this->_image,
			$clip['box']->ll->x,
			$clip['box']->ll->y,
			$clip['box']->ll->x,
			$clip['box']->ll->y,
			$clip['box']->getWidth(),
			$clip['box']->getHeight() );

		/**
		 * Now we should free image allocated for the clipping context to avoid memory leaks
		 */
		imagedestroy( $this->_image );
		$this->_image = $clip['image'];

		/**
		 * Remove clipping context from the stack
		 */
		array_shift( $this->_clipping );
	}

	function _saveColor( $rgb ) {
		$color = imagecolorallocate( $this->_image, $rgb[0], $rgb[1], $rgb[2] );
		array_unshift( $this->_color, array(
			'rgb'    => $rgb,
			'object' => $color
		) );
	}

	function _saveClip( $box ) {
		/**
		 * Initialize clipping  context record and add it  to the clipping
		 * stack
		 */
		$clip = array(
			'image' => $this->_image,
			'box'   => $box
		);
		array_unshift( $this->_clipping, $clip );

		/**
		 * Create a copy of current image for the clipping context
		 */
		$width        = imagesx( $clip['image'] );
		$height       = imagesy( $clip['image'] );
		$this->_image = imagecreatetruecolor( $width,
			$height );
		imagecopy( $this->_image,
			$clip['image'],
			0, 0,
			0, 0,
			$width, $height );
	}

	function _getCurrentColor() {
		return $this->_color[0]['object'];
	}

	function _setColor( $color ) {
		imagecolordeallocate( $this->_image, $this->_color[0]['object'] );
		$this->_color[0] = $color;
	}

	function _setFont( $typeface, $encoding, $size ) {
		global $g_font_resolver_pdf;
		$fontfile = $g_font_resolver_pdf->ttf_mappings[ $typeface ];

		$font     = $this->_font_factory->getTrueType( $typeface, $encoding );
		$ascender = $font->ascender() / 1000;

		$this->_font[0] = array(
			'font'     => $typeface,
			'encoding' => $encoding,
			'size'     => $size,
			'ascender' => $ascender
		);
	}

	function _getFont() {
		return $this->_font[0];
	}

	function _drawLine( $x1, $y1, $x2, $y2 ) {
		imageline( $this->_image, $x1, $y1, $x2, $y2, $this->_color[0]['object'] );
	}

	/**
	 * Note that "paper space" have Y coordinate axis directed to the bottom,
	 * while images have Y coordinate axis directory to the top
	 */
	function _fixCoords( &$x, &$y ) {
		$x = $this->_fixCoordX( $x );
		$y = $this->_fixCoordY( $y );
	}

	function _fixCoordX( $source_x ) {
		$x     = $source_x;
		$dummy = 0;
		$this->_transform->apply( $x, $dummy );

		return $x;
	}

	function _fixCoordY( $source_y ) {
		$y     = $source_y;
		$dummy = 0;
		$this->_transform->apply( $dummy, $y );

		return $y;
	}

	function _fixSizes( &$x, &$y ) {
		$x = $this->_fixSizeX( $x );
		$y = $this->_fixSizeY( $y );
	}

	function _fixSizeX( $x ) {
		static $scale = null;
		if ( is_null( $scale ) ) {
			$scale = $this->_widthPixels / mm2pt( $this->media->width() );
		};

		return ceil( $x * $scale );
	}

	function _fixSizeY( $y ) {
		static $scale = null;
		if ( is_null( $scale ) ) {
			$scale = $this->_heightPixels / mm2pt( $this->media->height() );
		};

		return ceil( $y * $scale );
	}

	function OutputDriverPNG() {
		$this->OutputDriverGeneric();

		$this->_color    = array();
		$this->_font     = array();
		$this->_path     = new Path;
		$this->_clipping = array();

		$this->_font_factory = new FontFactory();
	}

	function reset( &$media ) {
		parent::reset( $media );

		$this->update_media( $media );
	}

	function update_media( $media ) {
		parent::update_media( $media );

		/**
		 * Here we use a small hack; media height and width (in millimetres) match
		 * the size of screenshot (in pixels), so we take them as-is
		 */
		$this->_heightPixels = $media->height();
		$this->_widthPixels  = $media->width();

		$this->_image = imagecreatetruecolor( $this->_widthPixels,
			$this->_heightPixels );
		/**
		 * Render white background
		 */
		$white = imagecolorallocate( $this->_image, 255, 255, 255 );
		imagefill( $this->_image, 0, 0, $white );
		imagecolordeallocate( $this->_image, $white );

		$this->_color[0] = array(
			'rgb'    => array( 0, 0, 0 ),
			'object' => imagecolorallocate( $this->_image, 0, 0, 0 )
		);

		/**
		 * Setup initial clipping region
		 */
		$this->_clipping = array();
		$this->_saveClip( new Rectangle( new Point( 0,
			0 ),
			new Point( $this->_widthPixels - 1,
				$this->_heightPixels - 1 ) ) );

		$this->_transform = new AffineTransform( $this->_heightPixels,
			$this->_widthPixels / mm2pt( $this->media->width() ),
			$this->_heightPixels / mm2pt( $this->media->height() ) );
	}

	function add_link( $x, $y, $w, $h, $target ) { /* N/A */
	}

	function add_local_link( $left, $top, $width, $height, $anchor ) { /* N/A */
	}

	function circle( $x, $y, $r ) {
		$this->_path = new PathCircle();
		$this->_path->set_r( $r );
		$this->_path->set_x( $x );
		$this->_path->set_y( $y );
	}

	function clip() {
		/**
		 * Only  rectangular  clipping  areas  are  supported;  we'll  use
		 * bounding box of  current path for clipping. If  current path is
		 * an rectangle, bounding box will match the path itself.
		 */
		$box = $this->_path->getBbox();

		/**
		 * Convert bounding from media coordinates
		 * to output device coordinates
		 */
		$this->_fixCoords( $box->ll->x, $box->ll->y );
		$this->_fixCoords( $box->ur->x, $box->ur->y );
		$box->normalize();

		/**
		 * Add a clipping context information
		 */
		$this->_restoreClip();
		$this->_saveClip( $box );

		/**
		 * Reset path after clipping have been applied
		 */
		$this->_path = new Path;
	}

	function close() {
		/**
		 * A small hack; as clipping  context is save every time 'save' is
		 * called, we may deterine the number of graphic contexts saved by
		 * the size of clipping context stack
		 */
		while ( count( $this->_clipping ) > 0 ) {
			$this->restore();
		};

		imagepng( $this->_image, $this->get_filename() );
		imagedestroy( $this->_image );
	}

	function closepath() {
		$this->_path->close();
	}

	function content_type() {
		return ContentType::png();
	}

	function dash( $x, $y ) {
	}

	function decoration( $underline, $overline, $strikeout ) {
	}

	function error_message() {
		return "OutputDriverPNG: generic error";
	}

	function field_multiline_text( $x, $y, $w, $h, $value, $field_name ) { /* N/A */
	}

	function field_text( $x, $y, $w, $h, $value, $field_name ) { /* N/A */
	}

	function field_password( $x, $y, $w, $h, $value, $field_name ) { /* N/A */
	}

	function field_pushbutton( $x, $y, $w, $h ) { /* N/A */
	}

	function field_pushbuttonimage( $x, $y, $w, $h, $field_name, $value, $actionURL ) { /* N/A */
	}

	function field_pushbuttonreset( $x, $y, $w, $h ) { /* N/A */
	}

	function field_pushbuttonsubmit( $x, $y, $w, $h, $field_name, $value, $actionURL ) { /* N/A */
	}

	function field_checkbox( $x, $y, $w, $h, $name, $value ) { /* N/A */
	}

	function field_radio( $x, $y, $w, $h, $groupname, $value, $checked ) { /* N/A */
	}

	function field_select( $x, $y, $w, $h, $name, $value, $options ) { /* N/A */
	}

	function fill() {
		$this->_path->fill( $this->_transform, $this->_image, $this->_getCurrentColor() );
		$this->_path = new Path;
	}

	function font_ascender( $name, $encoding ) {
		$font = $this->_font_factory->getTrueType( $name, $encoding );

		return $font->ascender() / 1000;
	}

	function font_descender( $name, $encoding ) {
		$font = $this->_font_factory->getTrueType( $name, $encoding );

		return - $font->descender() / 1000;
	}

	function get_bottom() {
	}

	/**
	 * Image output always contains only one page
	 */
	function get_expected_pages() {
		return 1;
	}

	function image( $image, $x, $y, $scale ) {
		$this->image_scaled( $image, $x, $y, $scale, $scale );
	}

	function image_scaled( $image, $x, $y, $scale_x, $scale_y ) {
		$this->_fixCoords( $x, $y );

		$sx = $image->sx();
		$sy = $image->sy();

		/**
		 * Get image size in device coordinates
		 */
		$dx = $sx * $scale_x;
		$dy = $sy * $scale_y;
		$this->_fixSizes( $dx, $dy );

		imagecopyresampled( $this->_image, $image->get_handle(),
			$x, $y - $dy,
			0, 0,
			$dx, $dy,
			$sx, $sy );
	}

	function image_ry( $image, $x, $y, $height, $bottom, $ox, $oy, $scale ) {
		$base_y = floor( $this->_fixCoordY( $bottom ) );
		$this->_fixCoords( $x, $y );
		$dest_height = floor( $this->_fixSizeY( $height ) );
		$start_y     = $y - $dest_height;

		$sx = $image->sx();
		$sy = $image->sy();
		$dx = $this->_fixSizeX( $sx * $scale );
		$dy = $this->_fixSizeY( $sy * $scale );

		$cx = $x;
		$cy = $start_y - ceil( $this->_fixSizeY( $oy ) / $dest_height ) * $dest_height;
		while ( $cy < $base_y ) {
			imagecopyresampled( $this->_image, $image->get_handle(),
				$cx, $cy,
				0, 0,
				$dx, $dy,
				$sx, $sy );
			$cy += $dest_height;
		};
	}

	function image_rx( $image, $x, $y, $width, $right, $ox, $oy, $scale ) {
		$base_x = floor( $this->_fixCoordX( $right ) );
		$this->_fixCoords( $x, $y );
		$dest_width = floor( $this->_fixSizeX( $width ) );
		$start_x    = $x - $dest_width;

		$sx = $image->sx();
		$sy = $image->sy();
		$dx = $this->_fixSizeX( $sx * $scale );
		$dy = $this->_fixSizeY( $sy * $scale );

		$cx = $start_x - ceil( $this->_fixSizeX( $oy ) / $dest_width ) * $dest_width;

		$cy = $y - $dy;

		while ( $cx < $base_x ) {
			imagecopyresampled( $this->_image, $image->get_handle(),
				$cx, $cy,
				0, 0,
				$dx, $dy,
				$sx, $sy );
			$cx += $dest_width;
		};
	}

	function image_rx_ry( $image, $x, $y, $width, $height, $right, $bottom, $ox, $oy, $scale ) {
		$base_x = floor( $this->_fixCoordX( $right ) );
		$base_y = floor( $this->_fixCoordY( $bottom ) );
		$this->_fixCoords( $x, $y );
		$dest_width  = floor( $this->_fixSizeX( $width ) );
		$dest_height = floor( $this->_fixSizeY( $height ) );
		$start_x     = $x - $dest_width;
		$start_y     = $y - $dest_height;

		$sx = $image->sx();
		$sy = $image->sy();
		$dx = $this->_fixSizeX( $sx * $scale );
		$dy = $this->_fixSizeY( $sy * $scale );

		$cx = $start_x - ceil( $this->_fixSizeX( $ox ) / $dest_width ) * $dest_width;
		$cy = $start_y - ceil( $this->_fixSizeY( $oy ) / $dest_height ) * $dest_height;

		while ( $cy < $base_y ) {
			while ( $cx < $base_x ) {
				imagecopyresampled( $this->_image,
					$image->get_handle(),
					$cx, $cy,
					0, 0,
					$dx, $dy,
					$sx, $sy );
				$cx += $dest_width;
			};
			$cx = $start_x - ceil( $this->_fixSizeX( $ox ) / $dest_width ) * $dest_width;
			$cy += $dest_height;
		};
	}

	function lineto( $x, $y ) {
		$this->_path->addPoint( new Point( $x, $y ) );
	}

	function moveto( $x, $y ) {
		$this->_path->clear();
		$this->_path->addPoint( new Point( $x, $y ) );
	}

	function new_form( $name ) { /* N/A */
	}

	function next_page() { /* N/A */
	}

	function release() {
	}

	/**
	 * Note: _restoreClip  will change current image object,  so we must
	 * release all  image-dependent objects before  call to _restoreClip
	 * to ensure resources are released correctly
	 */
	function restore() {
		$this->_restoreColor();
		$this->_restoreClip();
	}

	/**
	 * Note:  _saveClip will  change current  image object,  so  we must
	 * create  all image-dependent  objects after  call to  _saveClip to
	 * ensure resources are created correctly
	 */
	function save() {
		$this->_saveClip( $this->_clipping[0]['box'] );
		$this->_saveColor( $this->_color[0]['rgb'] );
	}

	function setfont( $name, $encoding, $size ) {
		$this->_setFont( $name, $encoding, $size );

		return true;
	}

	function setlinewidth( $x ) {
		$dummy = 0;
		$this->_fixSizes( $x, $dummy );
		imagesetthickness( $this->_image, $x );
	}

	function setrgbcolor( $r, $g, $b ) {
		$color = array(
			'rgb'    => array( $r, $g, $b ),
			'object' => imagecolorallocate( $this->_image, $r * 255, $g * 255, $b * 255 )
		);
		$this->_setColor( $color );
	}

	function set_watermark( $text ) {
	}

	function show_xy( $text, $x, $y ) {
		$this->_fixCoords( $x, $y );

		$font      = $this->_getFont();
		$converter = Converter::create();

		global $g_font_resolver_pdf;
		$fontFile = $g_font_resolver_pdf->ttf_mappings[ $font['font'] ];

		$fontSize = $font['size'];

		$dummy = 0;
		$this->_fixSizes( $dummy, $fontSize );

		$utf8_string = $converter->to_utf8( $text, $font['encoding'] );

		imagefttext( $this->_image,
			$fontSize * $font['ascender'],
			0,
			$x,
			$y,
			$this->_getCurrentColor(),
			TTF_FONTS_REPOSITORY . $fontFile,
			$utf8_string );
	}

	/**
	 * Note: the koefficient is just a magic number; I'll need to examine the
	 * imagefttext behavior more closely
	 */
	function stringwidth( $string, $name, $encoding, $size ) {
		$font = $this->_font_factory->getTrueType( $name, $encoding );

		return Font::points( $size, $font->stringwidth( $string ) ) * 1.25;
	}

	function stroke() {
		$this->_path->stroke( $this->_transform, $this->_image, $this->_getCurrentColor() );
		$this->_path = new Path;
	}
}

// $Header: /cvsroot/html2ps/stubs.common.inc.php,v 1.5 2006/11/11 13:43:53 Konstantin Exp $

if ( ! function_exists( 'file_get_contents' ) ) {
	require_once( HTML2PS_DIR . 'stubs.file_get_contents.inc.php' );
}

if ( ! function_exists( 'file_put_contents' ) ) {
	require_once( HTML2PS_DIR . 'stubs.file_put_contents.inc.php' );
}

if ( ! function_exists( 'is_executable' ) ) {
	require_once( HTML2PS_DIR . 'stubs.is_executable.inc.php' );
}

if ( ! function_exists( 'memory_get_usage' ) ) {
	require_once( HTML2PS_DIR . 'stubs.memory_get_usage.inc.php' );
}

if ( ! function_exists( '_' ) ) {
	require_once( HTML2PS_DIR . 'stubs._.inc.php' );
}


// $Header: /cvsroot/html2ps/media.layout.inc.php,v 1.16 2007/05/07 12:15:53 Konstantin Exp $

$GLOBALS['g_predefined_media'] = array();
$GLOBALS['g_media']            = null;

// TODO: check for validity
function add_predefined_media( $name, $height, $width ) {
	global $g_predefined_media;
	$g_predefined_media[ $name ] = array( 'height' => $height, 'width' => $width );
}

class Media {
	var $margins;
	var $size;
	var $pixels;
	var $is_landscape;

	/**
	 * @param Array $size associative array with 'height' and 'width' keys (mm)
	 * @param Array $margins associative array with 'top', 'bottom', 'left' and 'right' keys (mm)
	 */
	function Media( $size, $margins ) {
		$this->size    = $size;
		$this->margins = $margins;
		$this->pixels  = 800;
	}

	function &copy() {
		$new_item         =& new Media( $this->size, $this->margins );
		$new_item->pixels = $this->pixels;

		return $new_item;
	}

	function doInherit() {
	}

	function get_width() {
		return $this->is_landscape ? $this->size['height'] : $this->size['width'];
	}

	function width() {
		return $this->get_width();
	}

	function get_height() {
		return $this->height();
	}

	function height() {
		return $this->is_landscape ? $this->size['width'] : $this->size['height'];
	}

	function real_width() {
		return $this->width() - $this->margins['left'] - $this->margins['right'];
	}

	function real_height() {
		return $this->height() - $this->margins['bottom'] - $this->margins['top'];
	}

	function set_height( $height ) {
		$this->size['height'] = $height;
	}

	function set_landscape( $state ) {
		$this->is_landscape = (bool) $state;
	}

	// TODO: validity checking
	function set_margins( $margins ) {
		$this->margins = $margins;
	}

	function set_pixels( $pixels ) {
		$this->pixels = $pixels;
	}

	function set_width( $width ) {
		$this->size['width'] = $width;
	}

	// TODO: validity checking
	function &predefined( $name ) {
		global $g_predefined_media;

		// Let's check if the chosen media defined
		if ( isset( $g_predefined_media[ $name ] ) ) {
			$media =& new Media( $g_predefined_media[ $name ], array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0
			) );
		} else {
			$media = null;
		};

		return $media;
	}

	/**
	 * Pixels per millimeter
	 */
	function PPM() {
		return $this->pixels / ( $this->size['width'] - $this->margins['left'] - $this->margins['right'] );
	}

	function to_bbox() {
		return '0 0 ' . ceil( mm2pt( $this->size['width'] ) ) . ' ' . ceil( mm2pt( $this->size['height'] ) );
	}

	function to_ps_landscape() {
		if ( ! $this->is_landscape ) {
			return "/initpage {} def";
		};

		return "/initpage {90 rotate 0 pageheight neg translate} def";
	}

	function to_ps() {
		return
			// Note that /pagewidth and /pageheight should contain page size on the "client"
			// coordinate system for correct rendering, so the will swap place in landscape mode,
			// while /width and height set in PageSize should have the real media values, because
			// actual coordinate system rotation/offset is done by the /initpage command without
			// actually ratating the media.
			"/pagewidth  {" . $this->width() . " mm} def\n" .
			"/pageheight {" . $this->height() . " mm} def\n" .
			"/lmargin    {{$this->margins['left']} mm} def\n" .
			"/rmargin    {{$this->margins['right']} mm} def\n" .
			"/tmargin    {{$this->margins['top']} mm} def\n" .
			"/bmargin    {{$this->margins['bottom']} mm} def\n" .
			"/px {pagewidth lmargin sub rmargin sub {$this->pixels} div mul} def\n" .
			"<< /PageSize [" . $this->size['width'] . " mm " . $this->size['height'] . " mm] >> setpagedevice\n" .
			$this->to_ps_landscape();
	}
}


// $Header: /cvsroot/html2ps/box.php,v 1.46 2007/05/06 18:49:29 Konstantin Exp $

// This variable is used to track the reccurrent framesets
// they can be produced by inaccurate or malicious HTML-coder
// or by some cookie- or referrer- based identification system
//
$GLOBALS['g_frame_level'] = 0;

// Called when frame node  is to be processed
function inc_frame_level() {
	global $g_frame_level;
	$g_frame_level ++;

	if ( $g_frame_level > MAX_FRAME_NESTING_LEVEL ) {
		trigger_error( 'Frame nesting too deep',
			E_USER_ERROR );
	};
}

// Called when frame (and all nested frames, of course) processing have been completed
//
function dec_frame_level() {
	global $g_frame_level;
	$g_frame_level --;
}

// Calculate 'display' CSS property according to CSS 2.1 paragraph 9.7
// "Relationships between 'display', 'position', and 'float'"
// (The last table in that paragraph)
//
// @return flag indication of current box need a block box wrapper
//
function _fix_display_position_float( &$css_state ) {
	// Specified value -> Computed value
	// inline-table -> table
	// inline, run-in, table-row-group, table-column, table-column-group, table-header-group,
	// table-footer-group, table-row, table-cell, table-caption, inline-block -> block
	// others-> same as specified

	$display = $css_state->get_property( CSS_DISPLAY );

	switch ( $display ) {
		case "inline-table":
			$css_state->set_property( CSS_DISPLAY, 'table' );

			return false;
		case "inline":
		case "run-in":
		case "table-row-group":
		case "table-column":
		case "table-column-group":
		case "table-header-group":
		case "table-footer-group":
		case "table-row":
		case "table-cell":
		case "table-caption":
		case "inline-block":
			// Note that as we're using some non-standard display values, we need to add them to translation table
			$css_state->set_property( CSS_DISPLAY, 'block' );

			return false;

		// There are display types that cannot be directly converted to block; in this case we need to create a "wrapper" floating
		// or positioned block box and put our real box into it.
		case "-button":
		case "-button-submit":
		case "-button-reset":
		case "-button-image":
		case "-checkbox":
		case "-iframe":
		case "-image":
		case "-legend":
		case "-password":
		case "-radio":
		case "-select":
		case "-text":
		case "-textarea":
			// No change
			return true;

		// Display values that are not affected by "float" property
		case "-frame":
		case "-frameset":
			// 'block' is assumed here
		default:
			// No change
			return false;
	}
}

function &create_pdf_box( &$root, &$pipeline ) {
	// we must to be sure that first element is object
	if ( is_object( $root ) ) {
		switch ( $root->node_type() ) {
			case XML_DOCUMENT_NODE:
				// TODO: some magic from traverse_dom_tree
				$box =& create_document_box( $root, $pipeline );

				return $box;
			case XML_ELEMENT_NODE:
				$box =& create_node_box( $root, $pipeline );

				return $box;
			case XML_TEXT_NODE:
				$box =& create_text_box( $root, $pipeline );

				return $box;
			default:
				die( "unsupported node type:" . $root->node_type() );
		}
	} else {
		die( "node object expected, none object resived (" . __FILE__ . ":" . __LINE__ . ")" );
	}
}

function &create_document_box( &$root, &$pipeline ) {
	return BlockBox::create( $root, $pipeline );
}

function &create_node_box( &$root, &$pipeline ) {
	// Determine CSS proerty value for current child
	$css_state =& $pipeline->get_current_css_state();
	$css_state->pushDefaultState();

	$default_css = $pipeline->get_default_css();
	$default_css->apply( $root, $css_state, $pipeline );

	// Store the default 'display' value; we'll need it later when checking for impossible tag/display combination
	$handler         =& CSS::get_handler( CSS_DISPLAY );
	$default_display = $handler->get( $css_state->getState() );

	// Initially generated boxes do not require block wrappers
	// Block wrappers are required in following cases:
	// - float property is specified for non-block box which cannot be directly converted to block box
	//   (a button, for example)
	// - display set to block for such box
	$need_block_wrapper = false;

	// TODO: some inheritance magic

	// Order is important. Items with most priority should be applied last
	// Tag attributes
	execute_attrs_before( $root, $pipeline );

	// CSS stylesheet
	$css =& $pipeline->get_current_css();
	$css->apply( $root, $css_state, $pipeline );

	// values from 'style' attribute
	if ( $root->has_attribute( "style" ) ) {
		parse_style_attr( $root, $css_state, $pipeline );
	};

	_fix_tag_display( $default_display, $css_state, $pipeline );

	execute_attrs_after_styles( $root, $pipeline );

	// CSS 2.1:
	// 9.7 Relationships between 'display', 'position', and 'float'
	// The three properties that affect box generation and layout 
	// 'display', 'position', and 'float'  interact as follows:
	// 1. If 'display' has the value 'none', then 'position' and 'float' do not apply.
	//    In this case, the element generates no box.
	$position_handler =& CSS::get_handler( CSS_POSITION );
	$float_handler    =& CSS::get_handler( CSS_FLOAT );

	// 2. Otherwise, if 'position' has the value 'absolute' or 'fixed', the box is absolutely positioned,
	//    the computed value of 'float' is 'none', and display is set according to the table below.
	//    The position of the box will be determined by the 'top', 'right', 'bottom' and 'left' properties and
	//    the box's containing block.
	$position = $css_state->get_property( CSS_POSITION );
	if ( $position === CSS_PROPERTY_INHERIT ) {
		$position = $css_state->getInheritedProperty( CSS_POSITION );
	};

	if ( $position === POSITION_ABSOLUTE ||
	     $position === POSITION_FIXED ) {
		$float_handler->replace( FLOAT_NONE, $css_state );
		$need_block_wrapper |= _fix_display_position_float( $css_state );
	};

	// 3. Otherwise, if 'float' has a value other than 'none', the box is floated and 'display' is set
	//    according to the table below.
	$float = $css_state->get_property( CSS_FLOAT );
	if ( $float != FLOAT_NONE ) {
		$need_block_wrapper |= _fix_display_position_float( $css_state );
	};

	// Process some special nodes, which should not get their 'display' values overwritten (unless
	// current display value is 'none'
	$current_display = $css_state->get_property( CSS_DISPLAY );

	if ( $current_display != 'none' ) {
		switch ( $root->tagname() ) {
			case 'body':
				$handler =& CSS::get_handler( CSS_DISPLAY );
				$handler->css( '-body', $pipeline );
				break;
			case 'br':
				$handler =& CSS::get_handler( CSS_DISPLAY );
				$handler->css( '-break', $pipeline );
				break;
			case 'img':
				$handler            =& CSS::get_handler( CSS_DISPLAY );
				$need_block_wrapper |= ( $handler->get( $css_state->getState() ) == 'block' );
				$handler->css( '-image', $pipeline );
				break;
		};
	};

	// 4. Otherwise, if the element is the root element, 'display' is set according to the table below.
	// 5. Otherwise, the remaining 'display' property values apply as specified. (see _fix_display_position_float)

	switch ( $css_state->get_property( CSS_DISPLAY ) ) {
		case 'block':
			$box =& BlockBox::create( $root, $pipeline );
			break;
		case '-break':
			$box =& BRBox::create( $pipeline );
			break;
		case '-body':
			$box =& BodyBox::create( $root, $pipeline );
			break;
		case '-button':
			$box =& ButtonBox::create( $root, $pipeline );
			break;
		case '-button-reset':
			$box =& ButtonResetBox::create( $root, $pipeline );
			break;
		case '-button-submit':
			$box =& ButtonSubmitBox::create( $root, $pipeline );
			break;
		case '-button-image':
			$box =& ButtonImageBox::create( $root, $pipeline );
			break;
		case '-checkbox':
			$box =& CheckBox::create( $root, $pipeline );
			break;
		case '-form':
			$box =& FormBox::create( $root, $pipeline );
			break;
		case '-frame':
			inc_frame_level();
			$box =& FrameBox::create( $root, $pipeline );
			dec_frame_level();
			break;
		case '-frameset':
			inc_frame_level();
			$box =& FramesetBox::create( $root, $pipeline );
			dec_frame_level();
			break;
		case '-iframe':
			inc_frame_level();
			$box =& IFrameBox::create( $root, $pipeline );
			dec_frame_level();
			break;
		case '-textarea':
			$box =& TextAreaInputBox::create( $root, $pipeline );
			break;
		case '-image':
			$box =& IMGBox::create( $root, $pipeline );
			break;
		case 'inline':
			$box =& InlineBox::create( $root, $pipeline );
			break;
		case 'inline-block':
			$box =& InlineBlockBox::create( $root, $pipeline );
			break;
		case '-legend':
			$box =& LegendBox::create( $root, $pipeline );
			break;
		case 'list-item':
			$box =& ListItemBox::create( $root, $pipeline );
			break;
		case 'none':
			$box =& NullBox::create();
			break;
		case '-radio':
			$box =& RadioBox::create( $root, $pipeline );
			break;
		case '-select':
			$box =& SelectBox::create( $root, $pipeline );
			break;
		case 'table':
			$box =& TableBox::create( $root, $pipeline );
			break;
		case 'table-cell':
			$box =& TableCellBox::create( $root, $pipeline );
			break;
		case 'table-row':
			$box =& TableRowBox::create( $root, $pipeline );
			break;
		case 'table-row-group':
		case 'table-header-group':
		case 'table-footer-group':
			$box =& TableSectionBox::create( $root, $pipeline );
			break;
		case '-text':
			$box =& TextInputBox::create( $root, $pipeline );
			break;
		case '-password':
			$box =& PasswordInputBox::create( $root, $pipeline );
			break;
		default:
			/**
			 * If 'display' value is invalid or unsupported, fall back to 'block' mode
			 */
			error_log( "Unsupported 'display' value: " . $css_state->get_property( CSS_DISPLAY ) );
			$box =& BlockBox::create( $root, $pipeline );
			break;
	}

	// Now check if pseudoelement should be created; in this case we'll use the "inline wrapper" box
	// containing both generated box and pseudoelements
	//
	$pseudoelements = $box->get_css_property( CSS_HTML2PS_PSEUDOELEMENTS );

	if ( $pseudoelements & CSS_HTML2PS_PSEUDOELEMENTS_BEFORE ) {
		// Check if :before preudoelement exists
		$before =& create_pdf_pseudoelement( $root, SELECTOR_PSEUDOELEMENT_BEFORE, $pipeline );
		if ( ! is_null( $before ) ) {
			$box->insert_child( 0, $before );
		};
	};

	if ( $pseudoelements & CSS_HTML2PS_PSEUDOELEMENTS_AFTER ) {
		// Check if :after pseudoelement exists
		$after =& create_pdf_pseudoelement( $root, SELECTOR_PSEUDOELEMENT_AFTER, $pipeline );
		if ( ! is_null( $after ) ) {
			$box->add_child( $after );
		};
	};

	// Check if this box needs a block wrapper (for example, floating button)
	// Note that to keep float/position information, we clear the CSS stack only
	// AFTER the wrapper box have been created; BUT we should clear the following CSS properties
	// to avoid the fake wrapper box actually affect the layout:
	// - margin
	// - border
	// - padding
	// - background
	//
	if ( $need_block_wrapper ) {
		/**
		 * Clear POSITION/FLOAT properties on wrapped boxes
		 */
		$box->setCSSProperty( CSS_POSITION, POSITION_STATIC );
		$box->setCSSProperty( CSS_POSITION, FLOAT_NONE );

		$wc = $box->get_css_property( CSS_WIDTH );

		// Note that if element width have been set as a percentage constraint and we're adding a block wrapper,
		// then we need to:
		// 1. set the same percentage width constraint to the wrapper element (will be done implicilty if we will not
		// modify the 'width' CSS handler stack
		// 2. set the wrapped element's width constraint to 100%, otherwise it will be narrower than expected
		if ( $wc->isFraction() ) {
			$box->setCSSProperty( CSS_WIDTH, new WCFraction( 1 ) );
		}

		$handler =& CSS::get_handler( CSS_MARGIN );
		$box->setCSSProperty( CSS_MARGIN, $handler->default_value() );

		/**
		 * Note:  default border does  not contain  any fontsize-dependent
		 * values, so we may safely use zero as a base font size
		 */
		$border_handler =& CSS::get_handler( CSS_BORDER );
		$value          = $border_handler->default_value();
		$value->units2pt( 0 );
		$box->setCSSProperty( CSS_BORDER, $value );

		$handler =& CSS::get_handler( CSS_PADDING );
		$box->setCSSProperty( CSS_PADDING, $handler->default_value() );

		$handler =& CSS::get_handler( CSS_BACKGROUND );
		$box->setCSSProperty( CSS_BACKGROUND, $handler->default_value() );

		// Create "clean" block box
		$wrapper =& new BlockBox();
		$wrapper->readCSS( $pipeline->get_current_css_state() );
		$wrapper->add_child( $box );

		// Remove CSS propery values from stack
		execute_attrs_after( $root, $pipeline );

		$css_state->popState();

		return $wrapper;
	} else {
		// Remove CSS propery values from stack
		execute_attrs_after( $root, $pipeline );
		$css_state->popState();

		$box->set_tagname( $root->tagname() );

		return $box;
	};
}

function &create_text_box( &$root, &$pipeline ) {
	// Determine CSS property value for current child
	$css_state =& $pipeline->get_current_css_state();
	$css_state->pushDefaultTextState();

	/**
	 * No text boxes generated by empty text nodes.
	 * Note that nodes containing spaces only are NOT empty, as they may
	 * correspond, for example, to whitespace between tags.
	 */
	if ( $root->content !== "" ) {
		$box =& InlineBox::create( $root, $pipeline );
	} else {
		$box = null;
	}

	// Remove CSS property values from stack
	$css_state->popState();

	return $box;
}

function &create_pdf_pseudoelement( $root, $pe_type, &$pipeline ) {
	// Store initial values to CSS stack
	$css_state =& $pipeline->get_current_css_state();
	$css_state->pushDefaultState();

	// Initially generated boxes do not require block wrappers
	// Block wrappers are required in following cases:
	// - float property is specified for non-block box which cannot be directly converted to block box
	//   (a button, for example)
	// - display set to block for such box
	$need_block_wrapper = false;

	$css =& $pipeline->get_current_css();
	$css->apply_pseudoelement( $pe_type, $root, $css_state, $pipeline );

	// Now, if no content found, just return
	//
	$content_obj = $css_state->get_property( CSS_CONTENT );
	if ( $content_obj === CSS_PROPERTY_INHERIT ) {
		$content_obj = $css_state->getInheritedProperty( CSS_CONTENT );
	};
	$content = $content_obj->render( $pipeline->get_counters() );

	if ( $content === '' ) {
		$css_state->popState();

		$dummy = null;

		return $dummy;
	};

	// CSS 2.1:
	// 9.7 Relationships between 'display', 'position', and 'float'
	// The three properties that affect box generation and layout 
	// 'display', 'position', and 'float'  interact as follows:
	// 1. If 'display' has the value 'none', then 'position' and 'float' do not apply.
	//    In this case, the element generates no box.

	// 2. Otherwise, if 'position' has the value 'absolute' or 'fixed', the box is absolutely positioned,
	//    the computed value of 'float' is 'none', and display is set according to the table below.
	//    The position of the box will be determined by the 'top', 'right', 'bottom' and 'left' properties and
	//    the box's containing block.
	$position_handler =& CSS::get_handler( CSS_POSITION );
	$float_handler    =& CSS::get_handler( CSS_FLOAT );

	$position = $position_handler->get( $css_state->getState() );
	if ( $position === CSS_PROPERTY_INHERIT ) {
		$position = $css_state->getInheritedProperty( CSS_POSITION );
	};

	if ( $position === POSITION_ABSOLUTE || $position === POSITION_FIXED ) {
		$float_handler->replace( FLOAT_NONE );
		$need_block_wrapper |= _fix_display_position_float( $css_state );
	};

	// 3. Otherwise, if 'float' has a value other than 'none', the box is floated and 'display' is set
	//    according to the table below.
	$float = $float_handler->get( $css_state->getState() );
	if ( $float != FLOAT_NONE ) {
		$need_block_wrapper |= _fix_display_position_float( $css_state );
	};

	// 4. Otherwise, if the element is the root element, 'display' is set according to the table below.
	// 5. Otherwise, the remaining 'display' property values apply as specified. (see _fix_display_position_float)

	// Note that pseudoelements may get only standard display values
	$display_handler =& CSS::get_handler( CSS_DISPLAY );
	$display         = $display_handler->get( $css_state->getState() );

	switch ( $display ) {
		case 'block':
			$box =& BlockBox::create_from_text( $content, $pipeline );
			break;
		case 'inline':
			$ws_handler =& CSS::get_handler( CSS_WHITE_SPACE );
			$box        =& InlineBox::create_from_text( $content,
				$ws_handler->get( $css_state->getState() ),
				$pipeline );
			break;
		default:
			die( 'Unsupported "display" value: ' . $display_handler->get( $css_state->getState() ) );
	}

	// Check if this box needs a block wrapper (for example, floating button)
	// Note that to keep float/position information, we clear the CSS stack only
	// AFTER the wrapper box have been created; BUT we should clear the following CSS properties
	// to avoid the fake wrapper box actually affect the layout:
	// - margin
	// - border
	// - padding
	// - background
	//
	if ( $need_block_wrapper ) {
		$handler =& CSS::get_handler( CSS_MARGIN );
		$handler->css( "0", $pipeline );

		pop_border();
		push_border( default_border() );

		pop_padding();
		push_padding( default_padding() );

		$handler =& CSS::get_handler( CSS_BACKGROUND );
		$handler->css( 'transparent', $pipeline );

		// Create "clean" block box
		$wrapper =& new BlockBox();
		$wrapper->readCSS( $pipeline->get_current_css_state() );
		$wrapper->add_child( $box );

		$css_state->popState();

		return $wrapper;
	} else {
		$css_state->popState();

		return $box;
	};
}

function is_inline( &$box ) {
	if ( is_a( $box, "TextBox" ) ) {
		return true;
	};

	$display = $box->get_css_property( CSS_DISPLAY );

	return
		$display === '-button' ||
		$display === '-button-reset' ||
		$display === '-button-submit' ||
		$display === '-button-image' ||
		$display === '-checkbox' ||
		$display === '-image' ||
		$display === 'inline' ||
		$display === 'inline-block' ||
		$display === 'none' ||
		$display === '-radio' ||
		$display === '-select' ||
		$display === '-text' ||
		$display === '-password';
}

function is_whitespace( &$box ) {
	return
		is_a( $box, "WhitespaceBox" ) ||
		is_a( $box, "NullBox" );
}

function is_container( &$box ) {
	return is_a( $box, "GenericContainerBox" ) &&
	       ! is_a( $box, "GenericInlineBox" ) ||
	       is_a( $box, "InlineBox" );
}

function is_span( &$box ) {
	return is_a( $box, "InlineBox" );
}

function is_table_cell( &$box ) {
	return is_a( $box, "TableCellBox" );
}


// $Header: /cvsroot/html2ps/box.generic.php,v 1.73 2007/05/06 18:49:29 Konstantin Exp $

require_once( HTML2PS_DIR . 'globals.php' );

class GenericBox {
	var $_cache;
	var $_css;
	var $_left;
	var $_top;
	var $_parent;
	var $baseline;
	var $default_baseline;
	var $_tagname;
	var $_id;

	var $_cached_base_font_size;

	function GenericBox() {
		$this->_cache                 = array();
		$this->_css                   = array();
		$this->_cached_base_font_size = null;

		$this->_left = 0;
		$this->_top  = 0;

		$this->_parent = null;

		$this->baseline         = 0;
		$this->default_baseline = 0;

		$this->set_tagname( null );

		/**
		 * Assign an unique box identifier
		 */
		$GLOBALS['g_box_uid'] ++;
		$this->uid = $GLOBALS['g_box_uid'];

		$this->_id = null;
	}

	function destroy() {
		unset( $this->_cache );
		unset( $this->_css );
		unset( $this->_left );
		unset( $this->_top );
		unset( $this->_parent );
		unset( $this->baseline );
		unset( $this->default_baseline );
	}

	/**
	 * see get_property for optimization description
	 */
	function setCSSProperty( $code, $value ) {
		static $cache = array();
		if ( ! isset( $cache[ $code ] ) ) {
			$cache[ $code ] =& CSS::get_handler( $code );
		};

		$cache[ $code ]->replace_array( $value, $this->_css );
	}

	/**
	 * Optimization: this function is called very often,
	 * so even a slight overhead for CSS::get_handler call
	 * accumulates in a significiant processing delay.
	 */
	function &get_css_property( $code ) {
		static $cache = array();
		if ( ! isset( $cache[ $code ] ) ) {
			$cache[ $code ] =& CSS::get_handler( $code );
		};

		$value =& $cache[ $code ]->get( $this->_css );

		return $value;
	}

	function get_tagname() {
		return $this->_tagname;
	}

	function set_tagname( $tagname ) {
		$this->_tagname = $tagname;
	}

	function get_content() {
		return '';
	}

	function show_postponed( &$driver ) {
		$this->show( $driver );
	}

	function copy_style( &$box ) {
		// TODO: object references
		$this->_css = $box->_css;
	}

	/**
	 * Optimization: _readCSSLength is usually called several times
	 * while initializing box object. $base_font_size cound be calculated
	 * only once and stored in a static variable.
	 */
	function _readCSSLengths( $state, $property_list ) {
		if ( is_null( $this->_cached_base_font_size ) ) {
			$font                         =& $this->get_css_property( CSS_FONT );
			$this->_cached_base_font_size = $font->size->getPoints();
		};

		foreach ( $property_list as $property ) {
			$value =& $state->get_property( $property );

			if ( $value === CSS_PROPERTY_INHERIT ) {
				$value =& $state->getInheritedProperty( $property );
			};

			if ( is_object( $value ) ) {
				$value =& $value->copy();
				$value->doInherit( $state );
				$value->units2pt( $this->_cached_base_font_size );
			};

			$this->setCSSProperty( $property, $value );
		}
	}

	function _readCSS( $state, $property_list ) {
		foreach ( $property_list as $property ) {
			$value = $state->get_property( $property );

			// Note that order is important; composite object-value could be inherited and
			// object itself could contain subvalues with 'inherit' value

			if ( $value === CSS_PROPERTY_INHERIT ) {
				$value = $state->getInheritedProperty( $property );
			};

			if ( is_object( $value ) ) {
				$value = $value->copy();
				$value->doInherit( $state );
			};

			$this->setCSSProperty( $property, $value );
		}
	}

	function readCSS( &$state ) {
		/**
		 * Determine font size to be used in this box (required for em/ex units)
		 */
		$value = $state->get_property( CSS_FONT );
		if ( $value === CSS_PROPERTY_INHERIT ) {
			$value = $state->getInheritedProperty( CSS_FONT );
		};
		$base_font_size = $state->getBaseFontSize();

		if ( is_object( $value ) ) {
			$value = $value->copy();
			$value->doInherit( $state );
			$value->units2pt( $base_font_size );
		};

		$this->setCSSProperty( CSS_FONT, $value );

		/**
		 * Continue working with other properties
		 */

		$this->_readCSS( $state,
			array(
				CSS_COLOR,
				CSS_DISPLAY,
				CSS_VISIBILITY
			) );

		$this->_readCSSLengths( $state,
			array( CSS_VERTICAL_ALIGN ) );

		// '-html2ps-link-destination'
		global $g_config;
		if ( $g_config["renderlinks"] ) {
			$this->_readCSS( $state,
				array( CSS_HTML2PS_LINK_DESTINATION ) );
		};

		// Save ID attribute value
		$id = $state->get_property( CSS_HTML2PS_LINK_DESTINATION );
		if ( ! is_null( $id ) ) {
			$this->set_id( $id );
		};
	}

	function set_id( $id ) {
		$this->_id = $id;

		if ( ! isset( $GLOBALS['__html_box_id_map'][ $id ] ) ) {
			$GLOBALS['__html_box_id_map'][ $id ] =& $this;
		};
	}

	function get_id() {
		return $this->_id;
	}

	function show( &$driver ) {
		// If debugging mode is on, draw the box outline
		global $g_config;
		if ( $g_config['debugbox'] ) {
			// Copy the border object of current box
			$driver->setlinewidth( 0.1 );
			$driver->setrgbcolor( 0, 0, 0 );
			$driver->rect( $this->get_left(), $this->get_top(), $this->get_width(), - $this->get_height() );
			$driver->stroke();
		}

		// Set current text color
		// Note that text color is used not only for text drawing (for example, list item markers
		// are drawn with text color)
		$color = $this->get_css_property( CSS_COLOR );
		$color->apply( $driver );
	}

	/**
	 * Render box having position: fixed or contained in such box
	 * (Default behaviour)
	 */
	function show_fixed( &$driver ) {
		return $this->show( $driver );
	}

	function pre_reflow_images() {
	}

	function set_top( $value ) {
		$this->_top = $value;
	}

	function set_left( $value ) {
		$this->_left = $value;
	}

	function offset( $dx, $dy ) {
		$this->_left += $dx;
		$this->_top  += $dy;
	}

	// Calculate the content upper-left corner position in curent flow
	function guess_corner( &$parent ) {
		$this->put_left( $parent->_current_x + $this->get_extra_left() );
		$this->put_top( $parent->_current_y - $this->get_extra_top() );
	}

	function put_left( $value ) {
		$this->_left = $value;
	}

	function put_top( $value ) {
		$this->_top = $value + $this->getBaselineOffset();
	}

	/**
	 * Get Y coordinate of the top content area edge
	 */
	function get_top() {
		return
			$this->_top -
			$this->getBaselineOffset();
	}

	function get_right() {
		return $this->get_left() + $this->get_width();
	}

	function get_left() {
		return $this->_left;
	}

	function get_bottom() {
		return $this->get_top() - $this->get_height();
	}

	function getBaselineOffset() {
		return $this->baseline - $this->default_baseline;
	}

	function &make_anchor( &$media, $link_destination, $page_heights ) {
		$page_index  = 0;
		$pages_count = count( $page_heights );
		$bottom      = mm2pt( $media->height() - $media->margins['top'] );
		do {
			$bottom -= $page_heights[ $page_index ];
			$page_index ++;
		} while ( $this->get_top() < $bottom && $page_index < $pages_count );

		/**
		 * Now let's calculate the coordinates on this particular page
		 *
		 * X coordinate calculation is pretty straightforward (and, actually, unused, as it would be
		 * a bad idea to scroll PDF horiaontally).
		 */
		$x = $this->get_left();

		/**
		 * Y coordinate should be calculated relatively to the bottom page edge
		 */
		$y = ( $this->get_top() - $bottom ) + ( mm2pt( $media->real_height() ) - $page_heights[ $page_index - 1 ] ) + mm2pt( $media->margins['bottom'] );

		$anchor =& new Anchor( $link_destination,
			$page_index,
			$x,
			$y );

		return $anchor;
	}

	function reflow_anchors( &$driver, &$anchors, $page_heights ) {
		if ( $this->is_null() ) {
			return;
		};

		$link_destination = $this->get_css_property( CSS_HTML2PS_LINK_DESTINATION );
		if ( ! is_null( $link_destination ) ) {
			$anchors[ $link_destination ] =& $this->make_anchor( $driver->media, $link_destination, $page_heights );
		};
	}

	function reflow( &$parent, &$context ) {
	}

	function reflow_inline() {
	}

	function out_of_flow() {
		return false;
	}

	function get_bottom_margin() {
		return $this->get_bottom();
	}

	function get_top_margin() {
		return $this->get_top();
	}

	function get_full_height() {
		return $this->get_height();
	}

	function get_width() {
		return $this->width;
	}

	function get_full_width() {
		return $this->width;
	}

	function get_height() {
		return $this->height;
	}

	function get_baseline() {
		return $this->baseline;
	}

	function is_container() {
		return false;
	}

	function isVisibleInFlow() {
		return true;
	}

	function reflow_text() {
		return true;
	}

	/**
	 * Note that linebox is started by any non-whitespace inline element; all whitespace elements before
	 * that moment should be ignored.
	 *
	 * @param boolean $linebox_started Flag indicating that a new line box have just started and it already contains
	 * some inline elements
	 * @param boolean $previous_whitespace Flag indicating that a previous inline element was an whitespace element.
	 */
	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		return;
	}

	function is_null() {
		return false;
	}

	function isCell() {
		return false;
	}

	function isTableRow() {
		return false;
	}

	function isTableSection() {
		return false;
	}

	// CSS 2.1:
	// 9.2.1 Block-level elements and block boxes
	// Block-level elements are those elements of the source document that are formatted visually as blocks
	// (e.g., paragraphs). Several values of the 'display' property make an element block-level:
	// 'block', 'list-item', 'compact' and 'run-in' (part of the time; see compact and run-in boxes), and 'table'.
	//
	function isBlockLevel() {
		return false;
	}

	function hasAbsolutePositionedParent() {
		if ( is_null( $this->parent ) ) {
			return false;
		};

		return
			$this->parent->get_css_property( CSS_POSITION ) == POSITION_ABSOLUTE ||
			$this->parent->hasAbsolutePositionedParent();
	}

	function hasFixedPositionedParent() {
		if ( is_null( $this->parent ) ) {
			return false;
		};

		return
			$this->parent->get_css_property( CSS_POSITION ) == POSITION_FIXED ||
			$this->parent->hasFixedPositionedParent();
	}

	/**
	 * Box can be expanded if it has no width constrains and
	 * all it parents has no width constraints
	 */
	function mayBeExpanded() {
		$wc = $this->get_css_property( CSS_WIDTH );
		if ( ! $wc->isNull() ) {
			return false;
		};

		if ( $this->get_css_property( CSS_FLOAT ) <> FLOAT_NONE ) {
			return true;
		};

		if ( $this->get_css_property( CSS_POSITION ) <> POSITION_STATIC &&
		     $this->get_css_property( CSS_POSITION ) <> POSITION_RELATIVE ) {
			return true;
		};

		if ( is_null( $this->parent ) ) {
			return true;
		};

		return $this->parent->mayBeExpanded();
	}

	function isLineBreak() {
		return false;
	}

	function get_min_width_natural( $context ) {
		return $this->get_min_width( $context );
	}

	function is_note_call() {
		return isset( $this->note_call );
	}

	/* DOM compatibility */
	function &get_parent_node() {
		return $this->parent;
	}
}

// $Header: /cvsroot/html2ps/box.generic.formatted.php,v 1.21 2007/02/18 09:55:10 Konstantin Exp $

require_once( HTML2PS_DIR . 'doc.anchor.class.php' );
require_once( HTML2PS_DIR . 'layout.vertical.php' );

class GenericFormattedBox extends GenericBox {
	var $uid;

	function _get_collapsable_top_margin_internal() {
		$positive_margin = 0;
		$negative_margin = 0;

		$current_box = $this;

		$border  = $current_box->get_css_property( CSS_BORDER );
		$padding = $current_box->get_css_property( CSS_PADDING );
		if ( $border->top->get_width() > 0 ||
		     $padding->top->value > 0 ) {
			return 0;
		};

		while ( ! is_null( $current_box ) &&
		        $current_box->isBlockLevel() ) {
			$margin  = $current_box->get_css_property( CSS_MARGIN );
			$border  = $current_box->get_css_property( CSS_BORDER );
			$padding = $current_box->get_css_property( CSS_PADDING );

			$top_margin = $margin->top->value;

			if ( $top_margin >= 0 ) {
				$positive_margin = max( $positive_margin, $top_margin );
			} else {
				$negative_margin = min( $negative_margin, $top_margin );
			};

			if ( $border->top->get_width() > 0 ||
			     $padding->top->value > 0 ) {
				$current_box = null;
			} else {
				$current_box = $current_box->get_first();
			};
		};

		return $positive_margin /*- $negative_margin*/
			;
	}

	function _get_collapsable_top_margin_external() {
		$positive_margin = 0;
		$negative_margin = 0;

		$current_box = $this;
		while ( ! is_null( $current_box ) &&
		        $current_box->isBlockLevel() ) {
			$margin  = $current_box->get_css_property( CSS_MARGIN );
			$border  = $current_box->get_css_property( CSS_BORDER );
			$padding = $current_box->get_css_property( CSS_PADDING );

			$top_margin = $margin->top->value;

			if ( $top_margin >= 0 ) {
				$positive_margin = max( $positive_margin, $top_margin );
			} else {
				$negative_margin = min( $negative_margin, $top_margin );
			};

			if ( $border->top->get_width() > 0 ||
			     $padding->top->value > 0 ) {
				$current_box = null;
			} else {
				$current_box = $current_box->get_first();
			};
		};

		return $positive_margin + $negative_margin;
	}

	function _get_collapsable_bottom_margin_external() {
		$positive_margin = 0;
		$negative_margin = 0;

		$current_box = $this;
		while ( ! is_null( $current_box ) &&
		        $current_box->isBlockLevel() ) {
			$margin  = $current_box->get_css_property( CSS_MARGIN );
			$border  = $current_box->get_css_property( CSS_BORDER );
			$padding = $current_box->get_css_property( CSS_PADDING );

			$bottom_margin = $margin->bottom->value;

			if ( $bottom_margin >= 0 ) {
				$positive_margin = max( $positive_margin, $bottom_margin );
			} else {
				$negative_margin = min( $negative_margin, $bottom_margin );
			};

			if ( $border->bottom->get_width() > 0 ||
			     $padding->bottom->value > 0 ) {
				$current_box = null;
			} else {
				$current_box = $current_box->get_last();
			};
		};

		return $positive_margin + $negative_margin;
	}

	function collapse_margin_bottom( &$parent, &$context ) {
		/**
		 * Now, if there's a parent for this box, we extend its height to fit current box.
		 * If parent generated new flow context (like table cell or floating box), its content
		 * area should include the current box bottom margin (bottom margin does not colllapse).
		 * See CSS 2.1 for more detailed explanations.
		 *
		 * @see FlowContext::container_uid()
		 *
		 * @link http://www.w3.org/TR/CSS21/visudet.html#Computing_widths_and_margins CSS 2.1 8.3.1 Calculating widths and margins
		 */
		$parent_border  = $parent->get_css_property( CSS_BORDER );
		$parent_padding = $parent->get_css_property( CSS_PADDING );

		/**
		 * The  bottom margin  of an  in-flow block-level  element  with a
		 * 'height'  of 'auto'  and 'min-height'  less than  the element's
		 * used height  and 'max-height'  greater than the  element's used
		 * height  is adjoining  to its  last in-flow  block-level child's
		 * bottom margin if the element has NO BOTTOM PADDING OR BORDER.
		 */

		$last    =& $parent->get_last();
		$is_last = ! is_null( $last ) && $this->uid == $last->uid;

		if ( ! is_null( $last ) &&
		     $is_last &&                                  // This element is a last in-flow block level element AND
		     $parent->uid != $context->container_uid() && // Parent element did not generate new flow context (like table-cell) AND
		     $parent_border->bottom->get_width() == 0 && // Parent have NO bottom border AND
		     $parent_padding->bottom->value == 0 ) {      // Parent have NO bottom padding AND
			$parent->extend_height( $this->get_bottom_border() );
		} else {
			// Otherwise (in particular, if this box is not last), bottom
			// margin of the current box will be contained inside the current box
			$parent->extend_height( $this->get_bottom_margin() );
		}

		$cm = $context->get_collapsed_margin();
		$context->pop_collapsed_margin();
		$context->pop_collapsed_margin();

		/**
		 * shift current parent 'watermark' to the current box margin edge;
		 * all content now will be drawn below this mark (with a small exception
		 * of elements having negative vertical margins, of course).
		 */
		if ( $is_last &&
		     ( $parent_border->bottom->get_width() > 0 ||
		       $parent_padding->bottom->value > 0 ) ) {
			$context->push_collapsed_margin( 0 );

			return $this->get_bottom_border() - $cm;
		} else {
			$collapsable = $this->_get_collapsable_bottom_margin_external();
			$context->push_collapsed_margin( $collapsable );

			return $this->get_bottom_border();
		};
	}

	function collapse_margin( &$parent, &$context ) {
		// Do margin collapsing

		// Margin collapsing is done as follows:
		// 1. If previous sibling was an inline element (so, parent line box was not empty),
		//    then no collapsing will take part
		// 2. If NO previous element exists at all, then collapse current box top margin
		//    with parent's collapsed top margin.
		// 2.1. If parent element was float, no collapsing should be
		// 3. If there's previous block element, collapse current box top margin
		//    with previous elemenent's collapsed bottom margin

		// Check if current parent line box contains inline elements only. In this case the only
		// margin will be current box margin

		if ( ! $parent->line_box_empty() ) {
			// Case (1). Previous element was inline element; no collapsing

			$parent->close_line( $context );

			$vmargin = $this->_get_collapsable_top_margin_external();
		} else {
			$parent_first = $this->parent->get_first();

			if ( is_null( $parent_first ) || // Unfortunately, we sometimes get null as a value of $parent_first; this should be checked
			     $parent_first->uid == $this->uid ) {
				// Case (2). No previous block element at all; Collapse with parent margins
				$collapsable = $this->_get_collapsable_top_margin_external();
				$collapsed   = $context->get_collapsed_margin();

				$vmargin = max( 0, $collapsable - $collapsed );

			} else {
				// Case (3). There's a previous block element

				$collapsable = $this->_get_collapsable_top_margin_external();
				$collapsed   = $context->get_collapsed_margin();

				// In this case, base position is a bottom border of the previous element
				// $vmargin - offset from a base position - should be at least $collapsed
				// (value of collapsed bottom margins from the previous element and its
				// children). If current element have $collapsable - collapsed top margin
				// (from itself and children too) greater that this value, we should
				// offset it further to the bottom

				$vmargin = max( $collapsable, $collapsed );
			};
		};

		// Determine the base Y coordinate of box margin edge
		$y = $parent->_current_y - $vmargin;

		$internal_margin = $this->_get_collapsable_top_margin_internal();
		$context->push_collapsed_margin( $internal_margin );

		return $y;
	}

	function GenericFormattedBox() {
		$this->GenericBox();

		// Layout data
		$this->baseline = 0;
		$this->parent   = null;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array(
				CSS_OVERFLOW,
				CSS_PAGE_BREAK_AFTER,
				CSS_PAGE_BREAK_BEFORE,
				CSS_PAGE_BREAK_INSIDE,
				CSS_ORPHANS,
				CSS_WIDOWS,
				CSS_POSITION,
				CSS_TEXT_ALIGN,
				CSS_WHITE_SPACE,
				CSS_CLEAR,
				CSS_CONTENT,
				CSS_HTML2PS_PSEUDOELEMENTS,
				CSS_FLOAT,
				CSS_Z_INDEX,
				CSS_HTML2PS_ALIGN,
				CSS_HTML2PS_NOWRAP,
				CSS_DIRECTION,
				CSS_PAGE
			) );

		$this->_readCSSLengths( $state,
			array(
				CSS_BACKGROUND,
				CSS_BORDER,
				CSS_BOTTOM,
				CSS_TOP,
				CSS_LEFT,
				CSS_RIGHT,
				CSS_MARGIN,
				CSS_PADDING,
				CSS_TEXT_INDENT,
				CSS_HTML2PS_COMPOSITE_WIDTH,
				CSS_HEIGHT,
				CSS_MIN_HEIGHT,
				CSS_MAX_HEIGHT,
				CSS_LETTER_SPACING
			) );

		/**
		 * CSS 2.1,  p 8.5.2:
		 *
		 * If an  element's border  color is not  specified with  a border
		 * property,  user agents  must  use the  value  of the  element's
		 * 'color' property as the computed value for the border color.
		 */
		$border =& $this->get_css_property( CSS_BORDER );
		$color  =& $this->get_css_property( CSS_COLOR );

		if ( $border->top->isDefaultColor() ) {
			$border->top->setColor( $color );
		};

		if ( $border->right->isDefaultColor() ) {
			$border->right->setColor( $color );
		};

		if ( $border->bottom->isDefaultColor() ) {
			$border->bottom->setColor( $color );
		};

		if ( $border->left->isDefaultColor() ) {
			$border->left->setColor( $color );
		};

		$this->setCSSProperty( CSS_BORDER, $border );

		$this->_height_constraint =& HCConstraint::create( $this );
		$this->height             = 0;

		// 'width'
		$wc          =& $this->get_css_property( CSS_WIDTH );
		$this->width = $wc->apply( 0, 0 );

		// 'PSEUDO-CSS' properties

		// '-localalign'
		switch ( $state->get_property( CSS_HTML2PS_LOCALALIGN ) ) {
			case LA_LEFT:
				break;
			case LA_RIGHT:
				$margin             =& $this->get_css_property( CSS_MARGIN );
				$margin->left->auto = true;
				$this->setCSSProperty( CSS_MARGIN, $margin );
				break;
			case LA_CENTER:
				$margin              =& $this->get_css_property( CSS_MARGIN );
				$margin->left->auto  = true;
				$margin->right->auto = true;
				$this->setCSSProperty( CSS_MARGIN, $margin );
				break;
		};
	}

	function _calc_percentage_margins( &$parent ) {
		$margin           = $this->get_css_property( CSS_MARGIN );
		$containing_block =& $this->_get_containing_block();
		$margin->calcPercentages( $containing_block['right'] - $containing_block['left'] );
		$this->setCSSProperty( CSS_MARGIN, $margin );
	}

	function _calc_percentage_padding( &$parent ) {
		$padding          = $this->get_css_property( CSS_PADDING );
		$containing_block =& $this->_get_containing_block();
		$padding->calcPercentages( $containing_block['right'] - $containing_block['left'] );
		$this->setCSSProperty( CSS_PADDING, $padding );
	}

	function apply_clear( $y, &$context ) {
		return LayoutVertical::apply_clear( $this, $y, $context );
	}


	/**
	 * CSS 2.1:
	 * 10.2 Content width: the 'width' property
	 * Values have the following meanings:
	 * <percentage> Specifies a percentage width. The percentage is calculated with respect to the width of the generated box's containing block.
	 *
	 * If the containing block's width depends on this element's width,
	 * then the resulting layout is undefined in CSS 2.1.
	 */
	function _calc_percentage_width( &$parent, &$context ) {
		$wc = $this->get_css_property( CSS_WIDTH );
		if ( $wc->isFraction() ) {
			$containing_block =& $this->_get_containing_block();

			// Calculate actual width
			$width = $wc->apply( $this->width, $containing_block['right'] - $containing_block['left'] );

			// Assign calculated width
			$this->put_width( $width );

			// Remove any width constraint
			$this->setCSSProperty( CSS_WIDTH, new WCConstant( $width ) );
		}
	}

	function _calc_auto_width_margins( &$parent ) {
		$float = $this->get_css_property( CSS_FLOAT );

		if ( $float !== FLOAT_NONE ) {
			$this->_calc_auto_width_margins_float( $parent );
		} else {
			$this->_calc_auto_width_margins_normal( $parent );
		}
	}

	// 'auto' margin value became 0, 'auto' width is 'shrink-to-fit'
	function _calc_auto_width_margins_float( &$parent ) {
		// If 'width' is set to 'auto' the used value is the "shrink-to-fit" width
		// TODO
		if ( false ) {
			// Calculation of the shrink-to-fit width is similar to calculating the
			// width of a table cell using the automatic table layout
			// algorithm. Roughly: calculate the preferred width by formatting the
			// content without breaking lines other than where explicit line breaks
			// occur, and also calculate the preferred minimum width, e.g., by trying
			// all possible line breaks. CSS 2.1 does not define the exact
			//  algorithm. Thirdly, find the available width: in this case, this is
			// the width of the containing block minus minus the used values of
			// 'margin-left', 'border-left-width', 'padding-left', 'padding-right',
			//  'border-right-width', 'margin-right', and the widths of any relevant
			// scroll bars.

			// Then the shrink-to-fit width is: min(max(preferred minimum width, available width), preferred width).

			// Store used value
		};

		// If 'margin-left', or 'margin-right' are computed as 'auto', their used value is '0'.
		$margin = $this->get_css_property( CSS_MARGIN );
		if ( $margin->left->auto ) {
			$margin->left->value = 0;
		}
		if ( $margin->right->auto ) {
			$margin->right->value = 0;
		}
		$this->setCSSProperty( CSS_MARGIN, $margin );

		$this->width = $this->get_width();
	}

	// 'margin-left' + 'border-left-width' + 'padding-left' + 'width' + 'padding-right' + 'border-right-width' + 'margin-right' = width of containing block
	function _calc_auto_width_margins_normal( &$parent ) {
		// get the containing block width
		$containing_block =& $this->_get_containing_block();
		$parent_width     = $containing_block['right'] - $containing_block['left'];

		// If 'width' is set to 'auto', any other 'auto' values become '0'  and 'width' follows from the resulting equality.

		// If both 'margin-left' and 'margin-right' are 'auto', their used values are equal.
		// This horizontally centers the element with respect to the edges of the containing block.

		$margin = $this->get_css_property( CSS_MARGIN );
		if ( $margin->left->auto && $margin->right->auto ) {
			$margin_value         = ( $parent_width - $this->get_full_width() ) / 2;
			$margin->left->value  = $margin_value;
			$margin->right->value = $margin_value;
		} else {
			// If there is exactly one value specified as 'auto', its used value follows from the equality.
			if ( $margin->left->auto ) {
				$margin->left->value = $parent_width - $this->get_full_width();
			} elseif ( $margin->right->auto ) {
				$margin->right->value = $parent_width - $this->get_full_width();
			};
		};
		$this->setCSSProperty( CSS_MARGIN, $margin );

		$this->width = $this->get_width();
	}

	function get_descender() {
		return 0;
	}

	function get_ascender() {
		return 0;
	}

	function _get_vert_extra() {
		return
			$this->get_extra_top() +
			$this->get_extra_bottom();
	}

	function _get_hor_extra() {
		return
			$this->get_extra_left() +
			$this->get_extra_right();
	}

	// Width:
	// 'get-min-width' stub
	function get_min_width( &$context ) {
		die( "OOPS! Unoverridden get_min_width called in class " . get_class( $this ) . " inside " . get_class( $this->parent ) );
	}

	function get_preferred_width( &$context ) {
		return $this->get_max_width( $context ) - $this->_get_hor_extra();
	}

	function get_preferred_minimum_width( &$context ) {
		return $this->get_min_width( $context );
	}

	// 'get-max-width' stub
	function get_max_width( &$context ) {
		die( "OOPS! Unoverridden get_max_width called in class " . get_class( $this ) . " inside " . get_class( $this->parent ) );
	}

	function get_max_width_natural( &$context ) {
		return $this->get_max_width( $context );
	}

	function get_full_width() {
		return $this->get_width() + $this->_get_hor_extra();
	}

	function put_full_width( $value ) {
		// Calculate value of additional horizontal space consumed by margins and padding
		$this->width = $value - $this->_get_hor_extra();
	}

	function &_get_containing_block() {
		$position = $this->get_css_property( CSS_POSITION );

		switch ( $position ) {
			case POSITION_ABSOLUTE:
				$containing_block =& $this->_get_containing_block_absolute();

				return $containing_block;
			case POSITION_FIXED:
				$containing_block =& $this->_get_containing_block_fixed();

				return $containing_block;
			case POSITION_STATIC:
			case POSITION_RELATIVE:
				$containing_block =& $this->_get_containing_block_static();

				return $containing_block;
			default:
				die( sprintf( 'Unexpected position enum value: %d', $position ) );
		};
	}

	function &_get_containing_block_fixed() {
		$media = $GLOBALS['g_media'];

		$containing_block           = array();
		$containing_block['left']   = mm2pt( $media->margins['left'] );
		$containing_block['right']  = mm2pt( $media->margins['left'] + $media->real_width() );
		$containing_block['top']    = mm2pt( $media->margins['bottom'] + $media->real_height() );
		$containing_block['bottom'] = mm2pt( $media->margins['bottom'] );

		return $containing_block;
	}

	// Get the position and size of containing block for current
	// ABSOLUTE POSITIONED element. It is assumed that this function
	// is called for ABSOLUTE positioned boxes ONLY
	//
	// @return associative array with 'top', 'bottom', 'right' and 'left'
	// indices in data space describing the position of containing block
	//
	function &_get_containing_block_absolute() {
		$parent =& $this->parent;

		// No containing block at all...
		// How could we get here?
		if ( is_null( $parent ) ) {
			trigger_error( "No containing block found for absolute-positioned element",
				E_USER_ERROR );
		};

		// CSS 2.1:
		// If the element has 'position: absolute', the containing block is established by the
		// nearest ancestor with a 'position' of 'absolute', 'relative' or 'fixed', in the following way:
		// - In the case that the ancestor is inline-level, the containing block depends on
		//   the 'direction' property of the ancestor:
		//   1. If the 'direction' is 'ltr', the top and left of the containing block are the top and left
		//      content edges of the first box generated by the ancestor, and the bottom and right are the
		//      bottom and right content edges of the last box of the ancestor.
		//   2. If the 'direction' is 'rtl', the top and right are the top and right edges of the first
		//      box generated by the ancestor, and the bottom and left are the bottom and left content
		//      edges of the last box of the ancestor.
		// - Otherwise, the containing block is formed by the padding edge of the ancestor.
		// TODO: inline-level ancestors
		while ( ( ! is_null( $parent->parent ) ) &&
		        ( $parent->get_css_property( CSS_POSITION ) === POSITION_STATIC ) ) {
			$parent =& $parent->parent;
		}

		// Note that initial containg block (containig BODY element) will be formed by BODY margin edge,
		// unlike other blocks which are formed by padding edges

		if ( $parent->parent ) {
			// Normal containing block
			$containing_block           = array();
			$containing_block['left']   = $parent->get_left_padding();
			$containing_block['right']  = $parent->get_right_padding();
			$containing_block['top']    = $parent->get_top_padding();
			$containing_block['bottom'] = $parent->get_bottom_padding();
		} else {
			// Initial containing block
			$containing_block           = array();
			$containing_block['left']   = $parent->get_left_margin();
			$containing_block['right']  = $parent->get_right_margin();
			$containing_block['top']    = $parent->get_top_margin();
			$containing_block['bottom'] = $parent->get_bottom_margin();
		};

		return $containing_block;
	}

	function &_get_containing_block_static() {
		$parent =& $this->parent;

		// No containing block at all...
		// How could we get here?

		if ( is_null( $parent ) ) {
			die( "No containing block found for static-positioned element" );
		};

		while ( ! is_null( $parent->parent ) &&
		        ! $parent->isBlockLevel() &&
		        ! $parent->isCell() ) {
			$parent =& $parent->parent;
		};

		// Note that initial containg block (containing BODY element)
		// will be formed by BODY margin edge,
		// unlike other blocks which are formed by content edges

		$containing_block           = array();
		$containing_block['left']   = $parent->get_left();
		$containing_block['right']  = $parent->get_right();
		$containing_block['top']    = $parent->get_top();
		$containing_block['bottom'] = $parent->get_bottom();

		return $containing_block;
	}

	// Height constraint
	function get_height_constraint() {
		return $this->_height_constraint;
	}

	function put_height_constraint( &$wc ) {
		$this->_height_constraint = $wc;
	}

	// Extends the box height to cover the given Y coordinate
	// If box height is already big enough, no changes will be made
	//
	// @param $y_coord Y coordinate should be covered by the box
	//
	function extend_height( $y_coord ) {
		$this->put_height( max( $this->get_height(), $this->get_top() - $y_coord ) );
	}

	function extend_width( $x_coord ) {
		$this->put_width( max( $this->get_width(), $x_coord - $this->get_left() ) );
	}

	function get_extra_bottom() {
		$border = $this->get_css_property( CSS_BORDER );

		return
			$this->get_margin_bottom() +
			$border->bottom->get_width() +
			$this->get_padding_bottom();
	}

	function get_extra_left() {
		$border = $this->get_css_property( CSS_BORDER );

		$left_border = $border->left;

		return
			$this->get_margin_left() +
			$left_border->get_width() +
			$this->get_padding_left();
	}

	function get_extra_right() {
		$border       = $this->get_css_property( CSS_BORDER );
		$right_border = $border->right;

		return
			$this->get_margin_right() +
			$right_border->get_width() +
			$this->get_padding_right();
	}

	function get_extra_top() {
		$border = $this->get_css_property( CSS_BORDER );

		return
			$this->get_margin_top() +
			$border->top->get_width() +
			$this->get_padding_top();
	}

	function get_extra_line_left() {
		return 0;
	}

	function get_extra_line_right() {
		return 0;
	}

	function get_margin_bottom() {
		$margin = $this->get_css_property( CSS_MARGIN );

		return $margin->bottom->value;
	}

	function get_margin_left() {
		$margin = $this->get_css_property( CSS_MARGIN );

		return $margin->left->value;
	}

	function get_margin_right() {
		$margin = $this->get_css_property( CSS_MARGIN );

		return $margin->right->value;
	}

	function get_margin_top() {
		$margin = $this->get_css_property( CSS_MARGIN );

		return $margin->top->value;
	}

	function get_padding_right() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $padding->right->value;
	}

	function get_padding_left() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $padding->left->value;
	}

	function get_padding_top() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $padding->top->value;
	}

	function get_border_top_width() {
		return $this->border->top->width;
	}

	function get_padding_bottom() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $padding->bottom->value;
	}

	function get_left_border() {
		$padding = $this->get_css_property( CSS_PADDING );
		$border  = $this->get_css_property( CSS_BORDER );

		return
			$this->get_left() -
			$padding->left->value -
			$border->left->get_width();
	}

	function get_right_border() {
		$padding = $this->get_css_property( CSS_PADDING );
		$border  = $this->get_css_property( CSS_BORDER );

		return
			$this->get_left() +
			$this->get_width() +
			$padding->right->value +
			$border->right->get_width();
	}

	function get_top_border() {
		$border = $this->get_css_property( CSS_BORDER );

		return
			$this->get_top_padding() +
			$border->top->get_width();
	}

	function get_bottom_border() {
		$border = $this->get_css_property( CSS_BORDER );

		return
			$this->get_bottom_padding() -
			$border->bottom->get_width();
	}

	function get_left_padding() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $this->get_left() - $padding->left->value;
	}

	function get_right_padding() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $this->get_left() + $this->get_width() + $padding->right->value;
	}

	function get_top_padding() {
		$padding = $this->get_css_property( CSS_PADDING );

		return
			$this->get_top() +
			$padding->top->value;
	}

	function get_bottom_padding() {
		$padding = $this->get_css_property( CSS_PADDING );

		return $this->get_bottom() - $padding->bottom->value;
	}

	function get_left_margin() {
		return
			$this->get_left() -
			$this->get_extra_left();
	}

	function get_right_margin() {
		return
			$this->get_right() +
			$this->get_extra_right();
	}

	function get_bottom_margin() {
		return
			$this->get_bottom() -
			$this->get_extra_bottom();
	}

	function get_top_margin() {
		$margin = $this->get_css_property( CSS_MARGIN );

		return
			$this->get_top_border() +
			$margin->top->value;
	}

	// Geometry
	function contains_point_margin( $x, $y ) {
		// Actually, we treat a small area around the float as "inside" float;
		// it will help us to prevent incorrectly positioning float due the rounding errors
		$eps = 0.1;

		return
			$this->get_left_margin() - $eps <= $x &&
			$this->get_right_margin() + $eps >= $x &&
			$this->get_top_margin() + $eps >= $y &&
			$this->get_bottom_margin() < $y;
	}

	function get_width() {
		$wc = $this->get_css_property( CSS_WIDTH );

		if ( $this->parent ) {
			return $wc->apply( $this->width, $this->parent->width );
		} else {
			return $wc->apply( $this->width, $this->width );
		}
	}

	// Unlike real/constrained width, or min/max width,
	// expandable width shows the size current box CAN be expanded;
	// it is pretty obvious that width-constrained boxes will never be expanded;
	// any other box can be expanded up to its parent _expandable_ width -
	// as parent can be expanded too.
	//
	function get_expandable_width() {
		$wc = $this->get_css_property( CSS_WIDTH );
		if ( $wc->isNull() && $this->parent ) {
			return $this->parent->get_expandable_width();
		} else {
			return $this->get_width();
		};
	}

	function put_width( $value ) {
		// TODO: constraints
		$this->width = $value;
	}

	function get_height() {
		if ( $this->_height_constraint->applicable( $this ) ) {
			return $this->_height_constraint->apply( $this->height, $this );
		} else {
			return $this->height;
		};
	}

	function get_height_padded() {
		return $this->get_height() + $this->get_padding_top() + $this->get_padding_bottom();
	}

	function put_height( $value ) {
		if ( $this->_height_constraint->applicable( $this ) ) {
			$this->height = $this->_height_constraint->apply( $value, $this );
		} else {
			$this->height = $value;
		};
	}

	function put_full_height( $value ) {
		$this->put_height( $value - $this->_get_vert_extra() );
	}

	// Returns total height of current element:
	// top padding + top margin + content + bottom padding + bottom margin + top border + bottom border
	function get_full_height() {
		return $this->get_height() +
		       $this->get_extra_top() +
		       $this->get_extra_bottom();
	}

	function get_real_full_height() {
		return $this->get_full_height();
	}

	function out_of_flow() {
		$position = $this->get_css_property( CSS_POSITION );
		$display  = $this->get_css_property( CSS_DISPLAY );

		return
			$position == POSITION_ABSOLUTE ||
			$position == POSITION_FIXED ||
			$display == 'none';
	}

	function moveto( $x, $y ) {
		$this->offset( $x - $this->get_left(), $y - $this->get_top() );
	}

	function show( &$viewport ) {
		$border     = $this->get_css_property( CSS_BORDER );
		$background = $this->get_css_property( CSS_BACKGROUND );

		// Draw border of the box
		$border->show( $viewport, $this );

		// Render background of the box
		$background->show( $viewport, $this );

		parent::show( $viewport );

		return true;
	}

	function show_fixed( &$viewport ) {
		return $this->show( $viewport );
	}

	function is_null() {
		return false;
	}

	function line_break_allowed() {
		$white_space = $this->get_css_property( CSS_WHITE_SPACE );
		$nowrap      = $this->get_css_property( CSS_HTML2PS_NOWRAP );

		return
			( $white_space === WHITESPACE_NORMAL ||
			  $white_space === WHITESPACE_PRE_WRAP ||
			  $white_space === WHITESPACE_PRE_LINE ) &&
			$nowrap === NOWRAP_NORMAL;
	}

	function get_left_background() {
		return $this->get_left_padding();
	}

	function get_right_background() {
		return $this->get_right_padding();
	}

	function get_top_background() {
		return $this->get_top_padding();
	}

	function get_bottom_background() {
		return $this->get_bottom_padding();
	}

	function isVisibleInFlow() {
		$visibility = $this->get_css_property( CSS_VISIBILITY );
		$position   = $this->get_css_property( CSS_POSITION );

		return
			$visibility === VISIBILITY_VISIBLE &&
			$position !== POSITION_FIXED;
	}

	function reflow_footnote( &$parent, &$context ) {
		$this->reflow_static( $parent, $context );
	}

	/**
	 * The  'top'  and 'bottom'  properties  move relatively  positioned
	 * element(s) up  or down without  changing their size.  'top' moves
	 * the boxes down,  and 'bottom' moves them up.  Since boxes are not
	 * split or stretched as a result of 'top' or 'bottom', the computed
	 * values  are always:  top =  -bottom.  If both  are 'auto',  their
	 * computed  values are  both  '0'. If  one  of them  is 'auto',  it
	 * becomes the negative of the other. If neither is 'auto', 'bottom'
	 * is ignored  (i.e., the computed  value of 'bottom' will  be minus
	 * the value of 'top').
	 */
	function offsetRelative() {
		/**
		 * Note  that  percentage   positioning  values  are  ignored  for
		 * relative positioning
		 */

		/**
		 * Check if 'top' value is percentage
		 */
		$top = $this->get_css_property( CSS_TOP );
		if ( $top->isNormal() ) {
			$top_value = $top->getPoints();
		} elseif ( $top->isPercentage() ) {
			$containing_block        = $this->_get_containing_block();
			$containing_block_height = $containing_block['top'] - $containing_block['bottom'];
			$top_value               = $containing_block_height * $top->getPercentage() / 100;
		} elseif ( $top->isAuto() ) {
			$top_value = null;
		}

		/**
		 * Check if 'bottom' value is percentage
		 */
		$bottom = $this->get_css_property( CSS_BOTTOM );
		if ( $bottom->isNormal() ) {
			$bottom_value = $bottom->getPoints();
		} elseif ( $bottom->isPercentage() ) {
			$containing_block        = $this->_get_containing_block();
			$containing_block_height = $containing_block['top'] - $containing_block['bottom'];
			$bottom_value            = $containing_block_height * $bottom->getPercentage() / 100;
		} elseif ( $bottom->isAuto() ) {
			$bottom_value = null;
		}

		/**
		 * Calculate vertical offset for relative positioned box
		 */
		if ( ! is_null( $top_value ) ) {
			$vertical_offset = - $top_value;
		} elseif ( ! is_null( $bottom_value ) ) {
			$vertical_offset = $bottom_value;
		} else {
			$vertical_offset = 0;
		};

		/**
		 * Check if 'left' value is percentage
		 */
		$left = $this->get_css_property( CSS_LEFT );
		if ( $left->isNormal() ) {
			$left_value = $left->getPoints();
		} elseif ( $left->isPercentage() ) {
			$containing_block       = $this->_get_containing_block();
			$containing_block_width = $containing_block['right'] - $containing_block['left'];
			$left_value             = $containing_block_width * $left->getPercentage() / 100;
		} elseif ( $left->isAuto() ) {
			$left_value = null;
		}

		/**
		 * Check if 'right' value is percentage
		 */
		$right = $this->get_css_property( CSS_RIGHT );
		if ( $right->isNormal() ) {
			$right_value = $right->getPoints();
		} elseif ( $right->isPercentage() ) {
			$containing_block       = $this->_get_containing_block();
			$containing_block_width = $containing_block['right'] - $containing_block['left'];
			$right_value            = $containing_block_width * $right->getPercentage() / 100;
		} elseif ( $right->isAuto() ) {
			$right_value = null;
		}

		/**
		 * Calculate vertical offset for relative positioned box
		 */
		if ( ! is_null( $left_value ) ) {
			$horizontal_offset = $left_value;
		} elseif ( ! is_null( $right_value ) ) {
			$horizontal_offset = - $right_value;
		} else {
			$horizontal_offset = 0;
		};

		$this->offset( $horizontal_offset,
			$vertical_offset );
	}
}

// $Header: /cvsroot/html2ps/box.container.php,v 1.68 2007/05/06 18:49:29 Konstantin Exp $

require_once( HTML2PS_DIR . 'strategy.width.min.php' );
require_once( HTML2PS_DIR . 'strategy.width.min.nowrap.php' );
require_once( HTML2PS_DIR . 'strategy.width.max.php' );
require_once( HTML2PS_DIR . 'strategy.width.max.natural.php' );

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * This file contains the abstract class describing the behavior of document element
 * containing some other document elements.
 */

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * The GenericContainerBox class is a common superclass for all document elements able
 * to contain other elements. This class does provide the line-box handling utilies and
 * some minor float related-functions.
 *
 */
class GenericContainerBox extends GenericFormattedBox {
	/**
	 * @var Array A list of contained elements (of type GenericFormattedBox)
	 * @access public
	 */
	var $content;

	var $_first_line;

	/**
	 * @var Array A list of child nodes in the current line box; changes dynamically
	 * during the reflow process.
	 * @access private
	 */
	var $_line;

	/**
	 * Sometimes floats may appear inside the line box, consider the following code,
	 * for example: "<div>text<div style='float:left'>float</div>word</div>". In
	 * this case, the floating DIV should be rendered below the "text word" line;
	 * thus, we need to keep a list of deferred floating elements and render them
	 * when current line box closes.
	 *
	 * @var Array A list of floats which should be flown after current line box ends;
	 * @access private
	 */
	var $_deferred_floats;

	/**
	 * @var float Current output X value inside the current element
	 * @access public
	 */
	var $_current_x;

	/**
	 * @var float Current output Y value inside the current element
	 * @access public
	 */
	var $_current_y;

	function destroy() {
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			$this->content[ $i ]->destroy();
		};
		unset( $this->content );

		parent::destroy();
	}

	/**
	 * Render current container box using the specified output method.
	 *
	 * @param OutputDriver $driver The output driver object
	 *
	 * @return Boolean flag indicating the success or 'null' value in case of critical rendering
	 * error
	 */
	function show( &$driver ) {
		GenericFormattedBox::show( $driver );

		$overflow = $this->get_css_property( CSS_OVERFLOW );

		/**
		 * Sometimes the content may overflow container boxes. This situation arise, for example,
		 * for relative-positioned child boxes, boxes having constrained height and in some
		 * other cases. If the container box does not have CSS 'overflow' property
		 * set to 'visible' value, the content should be visually clipped using container box
		 * padding area.
		 */
		if ( $overflow !== OVERFLOW_VISIBLE ) {
			$driver->save();
			$this->_setupClip( $driver );
		};

		/**
		 * Render child elements
		 */
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];

			/**
			 * We'll check the visibility property here
			 * Reason: all boxes (except the top-level one) are contained in some other box,
			 * so every box will pass this check. The alternative is to add this check into every
			 * box class show member.
			 *
			 * The only exception of absolute positioned block boxes which are drawn separately;
			 * their show method is called explicitly; the similar check should be performed there
			 */
			if ( $child->isVisibleInFlow() ) {
				/**
				 * To reduce the drawing overhead, we'll check if some part if current child element
				 * belongs to current output page. If not, there will be no reason to draw this
				 * child this time.
				 *
				 * @see OutputDriver::contains()
				 *
				 * @todo In rare cases the element content may be placed outside the element itself;
				 * in such situantion content may be visible on the page, while element is not.
				 * This situation should be resolved somehow.
				 */
				if ( $driver->contains( $child ) ) {
					if ( is_null( $child->show( $driver ) ) ) {
						return null;
					};
				};
			};
		}

		/**
		 * Restore previous clipping mode, if it have been modified for non-'overflow: visible'
		 * box.
		 */
		if ( $overflow !== OVERFLOW_VISIBLE ) {
			$driver->restore();
		};

		return true;
	}

	/**
	 * Render current fixed-positioned container box using the specified output method. Unlike
	 * the 'show' method, there's no check if current page viewport contains current element, as
	 * fixed-positioned may be drawn on the page margins, outside the viewport.
	 *
	 * @param OutputDriver $driver The output driver object
	 *
	 * @return Boolean flag indicating the success or 'null' value in case of critical rendering
	 * error
	 *
	 * @see GenericContainerBox::show()
	 *
	 * @todo the 'show' and 'show_fixed' method code are almost the same except the child element
	 * method called in the inner loop; also, no check is done if current viewport contains this element,
	 * thus sllowinf printing data on page margins, where no data should be printed normally
	 * I suppose some more generic method containing the common code should be made.
	 */
	function show_fixed( &$driver ) {
		GenericFormattedBox::show( $driver );

		$overflow = $this->get_css_property( CSS_OVERFLOW );

		/**
		 * Sometimes the content may overflow container boxes. This situation arise, for example,
		 * for relative-positioned child boxes, boxes having constrained height and in some
		 * other cases. If the container box does not have CSS 'overflow' property
		 * set to 'visible' value, the content should be visually clipped using container box
		 * padding area.
		 */
		if ( $overflow !== OVERFLOW_VISIBLE ) {
			// Save graphics state (of course, BEFORE the clipping area will be set)
			$driver->save();
			$this->_setupClip( $driver );
		};

		/**
		 * Render child elements
		 */
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			/**
			 * We'll check the visibility property here
			 * Reason: all boxes (except the top-level one) are contained in some other box,
			 * so every box will pass this check. The alternative is to add this check into every
			 * box class show member.
			 *
			 * The only exception of absolute positioned block boxes which are drawn separately;
			 * their show method is called explicitly; the similar check should be performed there
			 */
			$child =& $this->content[ $i ];
			if ( $child->get_css_property( CSS_VISIBILITY ) === VISIBILITY_VISIBLE ) {
				// Fixed-positioned blocks are displayed separately;
				// If we call them now, they will be drawn twice
				if ( $child->get_css_property( CSS_POSITION ) != POSITION_FIXED ) {
					if ( is_null( $child->show_fixed( $driver ) ) ) {
						return null;
					};
				};
			};
		}

		/**
		 * Restore previous clipping mode, if it have been modified for non-'overflow: visible'
		 * box.
		 */
		if ( $overflow !== OVERFLOW_VISIBLE ) {
			$driver->restore();
		};

		return true;
	}

	function _find( &$box ) {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( $this->content[ $i ]->uid == $box->uid ) {
				return $i;
			};
		}

		return null;
	}

	// Inserts new child box at the specified (zero-based) offset; 0 stands for first child
	//
	// @param $index index to insert child at
	// @param $box child to be inserted
	//
	function insert_child( $index, &$box ) {
		$box->parent =& $this;

		// Offset the content array
		for ( $i = count( $this->content ) - 1; $i >= $index; $i -- ) {
			$this->content[ $i + 1 ] =& $this->content[ $i ];
		};

		$this->content[ $index ] =& $box;
	}

	function insert_before( &$what, &$where ) {
		if ( $where ) {
			$index = $this->_find( $where );

			if ( is_null( $index ) ) {
				return null;
			};

			$this->insert_child( $index, $what );
		} else {
			// If 'where' is not specified, 'what' should become the last child
			$this->add_child( $what );
		};

		return $what;
	}

	function add_child( &$box ) {
		$this->append_child( $box );
	}

	function append_child( &$box ) {
		// In general, this function is called like following:
		// $box->add_child(create_pdf_box(...))
		// As create_pdf_box _may_ return null value (for example, for an empty text node),
		// we should process the case of $box == null here
		if ( $box ) {
			$box->parent     =& $this;
			$this->content[] =& $box;
		};
	}

	// Get first child of current box which actually will be drawn
	// on the page. So, whitespace and null boxes will be ignored
	//
	// See description of is_null for null box definition.
	// (not only NullBox is treated as null box)
	//
	// @return reference to the first visible child of current box
	function &get_first() {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( ! is_whitespace( $this->content[ $i ] ) &&
			     ! $this->content[ $i ]->is_null() ) {
				return $this->content[ $i ];
			};
		};

		// We use this construct to avoid notice messages in PHP 4.4 and PHP 5
		$dummy = null;

		return $dummy;
	}

	// Get first text or image child of current box which actually will be drawn
	// on the page.
	//
	// See description of is_null for null box definition.
	// (not only NullBox is treated as null box)
	//
	// @return reference to the first visible child of current box
	function &get_first_data() {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( ! is_whitespace( $this->content[ $i ] ) && ! $this->content[ $i ]->is_null() ) {
				if ( is_container( $this->content[ $i ] ) ) {
					$data =& $this->content[ $i ]->get_first_data();
					if ( ! is_null( $data ) ) {
						return $data;
					};
				} else {
					return $this->content[ $i ];
				};
			};
		};

		// We use this construct to avoid notice messages in PHP 4.4 and PHP 5
		$dummy = null;

		return $dummy;
	}

	// Get last child of current box which actually will be drawn
	// on the page. So, whitespace and null boxes will be ignored
	//
	// See description of is_null for null box definition.
	// (not only NullBox is treated as null box)
	//
	// @return reference to the last visible child of current box
	function &get_last() {
		for ( $i = count( $this->content ) - 1; $i >= 0; $i -- ) {
			if ( ! is_whitespace( $this->content[ $i ] ) && ! $this->content[ $i ]->is_null() ) {
				return $this->content[ $i ];
			};
		};

		// We use this construct to avoid notice messages in PHP 4.4 and PHP 5
		$dummy = null;

		return $dummy;
	}

	function offset_if_first( &$box, $dx, $dy ) {
		if ( $this->is_first( $box ) ) {
			// The top-level box (page box) should never be offset
			if ( $this->parent ) {
				if ( ! $this->parent->offset_if_first( $box, $dx, $dy ) ) {
					$this->offset( $dx, $dy );

					return true;
				};
			};
		};

		return false;
	}

	function offset( $dx, $dy ) {
		parent::offset( $dx, $dy );

		$this->_current_x += $dx;
		$this->_current_y += $dy;

		// Offset contents
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->content[ $i ]->offset( $dx, $dy );
		}
	}

	function GenericContainerBox() {
		$this->GenericFormattedBox();

		// By default, box does not have any content
		$this->content = array();

		// Initialize line box
		$this->_line = array();

		// Initialize floats-related stuff
		$this->_deferred_floats = array();

		$this->_additional_text_indent = 0;

		// Current-point
		$this->_current_x = 0;
		$this->_current_y = 0;

		// Initialize floating children array
		$this->_floats = array();
	}

	function add_deferred_float( &$float ) {
		$this->_deferred_floats[] =& $float;
	}

	/**
	 * Create the child nodes of current container object using the parsed HTML data
	 *
	 * @param mixed $root node corresponding to the current container object
	 */
	function create_content( &$root, &$pipeline ) {
		// Initialize content
		$child = $root->first_child();
		while ( $child ) {
			$box_child =& create_pdf_box( $child, $pipeline );
			$this->add_child( $box_child );
			$child = $child->next_sibling();
		};
	}

	// Content-handling functions

	function is_container() {
		return true;
	}

	function get_content() {
		return join( '', array_map( array( $this, 'get_content_callback' ), $this->content ) );
	}

	function get_content_callback( &$node ) {
		return $node->get_content();
	}

	// Get total height of this box content (including floats, if any)
	// Note that floats can be contained inside children, so we'll need to use
	// this function recusively
	function get_real_full_height() {
		$content_size = count( $this->content );

		$overflow = $this->get_css_property( CSS_OVERFLOW );

		// Treat items with overflow: hidden specifically,
		// as floats flown out of this boxes will not be visible
		if ( $overflow == OVERFLOW_HIDDEN ) {
			return $this->get_full_height();
		};

		// Check if this object is totally empty
		$first = $this->get_first();
		if ( is_null( $first ) ) {
			return 0;
		};

		// Initialize the vertical extent taken by content using the
		// very first child
		$max_top    = $first->get_top_margin();
		$min_bottom = $first->get_bottom_margin();

		for ( $i = 0; $i < $content_size; $i ++ ) {
			if ( ! $this->content[ $i ]->is_null() ) {
				// Check if top margin of current child is to the up
				// of vertical extent top margin
				$max_top = max( $max_top, $this->content[ $i ]->get_top_margin() );

				/**
				 * Check if current child bottom margin will extend
				 * the vertical space OR if it contains floats extending
				 * this, unless this child have overflow: hidden, because this
				 * will prevent additional content to be visible
				 */
				if ( ! $this->content[ $i ]->is_container() ) {
					$min_bottom = min( $min_bottom,
						$this->content[ $i ]->get_bottom_margin() );
				} else {
					$content_overflow = $this->content[ $i ]->get_css_property( CSS_OVERFLOW );

					if ( $content_overflow == OVERFLOW_HIDDEN ) {
						$min_bottom = min( $min_bottom,
							$this->content[ $i ]->get_bottom_margin() );
					} else {
						$min_bottom = min( $min_bottom,
							$this->content[ $i ]->get_bottom_margin(),
							$this->content[ $i ]->get_top_margin() -
							$this->content[ $i ]->get_real_full_height() );
					};
				};
			};
		}

		return max( 0, $max_top - $min_bottom ) + $this->_get_vert_extra();
	}

	// LINE-LENGTH RELATED FUNCTIONS

	function _line_length() {
		$sum  = 0;
		$size = count( $this->_line );

		for ( $i = 0; $i < $size; $i ++ ) {
			// Note that the line length should include the inline boxes margin/padding
			// as inline boxes are not directly included to the parent line box,
			// we'll need to check the parent of current line box element,
			// and, if it is an inline box, AND this element is last or first contained element
			// add correcponsing padding value
			$element =& $this->_line[ $i ];

			if ( isset( $element->wrapped ) && ! is_null( $element->wrapped ) ) {
				if ( $i == 0 ) {
					$sum += $element->get_full_width() - $element->getWrappedWidth();
				} else {
					$sum += $element->getWrappedWidthAndHyphen();
				};
			} else {
				$sum += $element->get_full_width();
			};

			if ( $element->parent ) {
				$first = $element->parent->get_first();
				$last  = $element->parent->get_last();

				if ( ! is_null( $first ) && $first->uid === $element->uid ) {
					$sum += $element->parent->get_extra_line_left();
				}

				if ( ! is_null( $last ) && $last->uid === $element->uid ) {
					$sum += $element->parent->get_extra_line_right();
				}
			};
		}

		if ( $this->_first_line ) {
			$ti  = $this->get_css_property( CSS_TEXT_INDENT );
			$sum += $ti->calculate( $this );
			$sum += $this->_additional_text_indent;
		};

		return $sum;
	}

	function _line_length_delta( &$context ) {
		return max( $this->get_available_width( $context ) - $this->_line_length(), 0 );
	}

	/**
	 * Get the last box in current line box
	 */
	function &last_in_line() {
		$size = count( $this->_line );
		if ( $size < 1 ) {
			$dummy = null;

			return $dummy;
		};

		return $this->_line[ $size - 1 ];
	}

	// WIDTH

	function get_min_width_natural( &$context ) {
		$content_size = count( $this->content );

		/**
		 * If box does not have any context, its minimal width is determined by extra horizontal space:
		 * padding, border width and margins
		 */
		if ( $content_size == 0 ) {
			$min_width = $this->_get_hor_extra();

			return $min_width;
		};

		/**
		 * If we're in 'nowrap' mode, minimal and maximal width will be equal
		 */
		$white_space   = $this->get_css_property( CSS_WHITE_SPACE );
		$pseudo_nowrap = $this->get_css_property( CSS_HTML2PS_NOWRAP );
		if ( $white_space == WHITESPACE_NOWRAP ||
		     $pseudo_nowrap == NOWRAP_NOWRAP ) {
			$min_width = $this->get_min_nowrap_width( $context );

			return $min_width;
		}

		/**
		 * We need to add text indent size to the width of the first item
		 */
		$start_index = 0;
		while ( $start_index < $content_size &&
		        $this->content[ $start_index ]->out_of_flow() ) {
			$start_index ++;
		};

		if ( $start_index < $content_size ) {
			$ti   = $this->get_css_property( CSS_TEXT_INDENT );
			$minw =
				$ti->calculate( $this ) +
				$this->content[ $start_index ]->get_min_width_natural( $context );
		} else {
			$minw = 0;
		};

		for ( $i = $start_index; $i < $content_size; $i ++ ) {
			$item =& $this->content[ $i ];
			if ( ! $item->out_of_flow() ) {
				$minw = max( $minw, $item->get_min_width( $context ) );
			};
		}

		/**
		 * Apply width constraint to min width. Return maximal value
		 */
		$wc               = $this->get_css_property( CSS_WIDTH );
		$containing_block =& $this->_get_containing_block();

		$min_width = $minw;

		return $min_width;
	}

	function get_min_width( &$context ) {
		$strategy = new StrategyWidthMin();

		return $strategy->apply( $this, $context );
	}

	function get_min_nowrap_width( &$context ) {
		$strategy = new StrategyWidthMinNowrap();

		return $strategy->apply( $this, $context );
	}

	// Note: <table width="100%" inside some block box cause this box to expand
	// $limit - maximal width which should not be exceeded; by default, there's no limit at all
	//
	function get_max_width_natural( &$context, $limit = 10E6 ) {
		$strategy = new StrategyWidthMaxNatural( $limit );

		return $strategy->apply( $this, $context );
	}

	function get_max_width( &$context, $limit = 10E6 ) {
		$strategy = new StrategyWidthMax( $limit );

		return $strategy->apply( $this, $context );
	}

	function close_line( &$context, $lastline = false ) {
		// Align line-box using 'text-align' property
		$size = count( $this->_line );

		if ( $size > 0 ) {
			$last_item =& $this->_line[ $size - 1 ];
			if ( is_whitespace( $last_item ) ) {
				$last_item->width  = 0;
				$last_item->height = 0;
			};
		};

		// Note that text-align should not be applied to the block boxes!
		// As block boxes will be alone in the line-box, we can check
		// if the very first box in the line is inline; if not - no justification should be made
		//
		if ( $size > 0 ) {
			if ( is_inline( $this->_line[0] ) ) {
				$cb = CSSTextAlign::value2pdf( $this->get_css_property( CSS_TEXT_ALIGN ) );
				$cb( $this, $context, $lastline );
			} else {
				// Nevertheless, CENTER tag and P/DIV with ALIGN attribute set should affect the
				// position of non-inline children.
				$cb = CSSPseudoAlign::value2pdf( $this->get_css_property( CSS_HTML2PS_ALIGN ) );
				$cb( $this, $context, $lastline );
			};
		};

		// Apply vertical align to all of the line content
		// first, we need to aling all baseline-aligned boxes to determine the basic line-box height, top and bottom edges
		// then, SUP and SUP positioned boxes (as they can extend the top and bottom edges, but not affected themselves)
		// then, MIDDLE, BOTTOM and TOP positioned boxes in the given order
		//
		$baselined = array();
		$baseline  = 0;
		$height    = 0;
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );

			if ( $vertical_align == VA_BASELINE ) {
				// Add current baseline-aligned item to the baseline
				//
				$baselined[] =& $this->_line[ $i ];

				$baseline = max( $baseline,
					$this->_line[ $i ]->default_baseline );
			};
		};

		$size_baselined = count( $baselined );
		for ( $i = 0; $i < $size_baselined; $i ++ ) {
			$baselined[ $i ]->baseline = $baseline;

			$height = max( $height,
				$baselined[ $i ]->get_full_height() + $baselined[ $i ]->getBaselineOffset(),
				$baselined[ $i ]->get_ascender() + $baselined[ $i ]->get_descender() );

		};

		// SUB vertical align
		//
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_SUB ) {
				$this->_line[ $i ]->baseline =
					$baseline + $this->_line[ $i ]->get_full_height() / 2;
			};
		}

		// SUPER vertical align
		//
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_SUPER ) {
				$this->_line[ $i ]->baseline = $this->_line[ $i ]->get_full_height() / 2;
			};
		}

		// MIDDLE vertical align
		//
		$middle = 0;
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_MIDDLE ) {
				$middle = max( $middle, $this->_line[ $i ]->get_full_height() / 2 );
			};
		};

		if ( $middle * 2 > $height ) {
			// Offset already aligned items
			//
			for ( $i = 0; $i < $size; $i ++ ) {
				$this->_line[ $i ]->baseline += ( $middle - $height / 2 );
			};
			$height = $middle * 2;
		};

		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_MIDDLE ) {
				$this->_line[ $i ]->baseline = $this->_line[ $i ]->default_baseline + ( $height / 2 - $this->_line[ $i ]->get_full_height() / 2 );
			};
		}

		// BOTTOM vertical align
		//
		$bottom = 0;
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_BOTTOM ) {
				$bottom = max( $bottom, $this->_line[ $i ]->get_full_height() );
			};
		};

		if ( $bottom > $height ) {
			// Offset already aligned items
			//
			for ( $i = 0; $i < $size; $i ++ ) {
				$this->_line[ $i ]->baseline += ( $bottom - $height );
			};
			$height = $bottom;
		};

		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_BOTTOM ) {
				$this->_line[ $i ]->baseline = $this->_line[ $i ]->default_baseline + $height - $this->_line[ $i ]->get_full_height();
			};
		}

		// TOP vertical align
		//
		$bottom = 0;
		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_TOP ) {
				$bottom = max( $bottom, $this->_line[ $i ]->get_full_height() );
			};
		};

		if ( $bottom > $height ) {
			$height = $bottom;
		};

		for ( $i = 0; $i < $size; $i ++ ) {
			$vertical_align = $this->_line[ $i ]->get_css_property( CSS_VERTICAL_ALIGN );
			if ( $vertical_align == VA_TOP ) {
				$this->_line[ $i ]->baseline = $this->_line[ $i ]->default_baseline;
			};
		}

		// Calculate the bottom Y coordinate of last line box
		//
		$line_bottom = $this->_current_y;
		foreach ( $this->_line AS $line_element ) {
			// This line is required; say, we have sequence of text and image inside the container,
			// AND image have greater baseline than text; in out case, text will be offset to the bottom
			// of the page and we lose the gap between text and container bottom edge, unless we'll re-extend
			// containier height

			// Note that we're using the colapsed margin value to get the Y coordinate to extend height to,
			// as bottom margin may be collapsed with parent

			$effective_bottom =
				$line_element->get_top() -
				$line_element->get_height() -
				$line_element->get_extra_bottom();

			$this->extend_height( $effective_bottom );
			$line_bottom = min( $effective_bottom, $line_bottom );
		}

		$this->extend_height( $line_bottom );

		// Clear the line box
		$this->_line = array();

		// Reset current X coordinate to the far left
		$this->_current_x = $this->get_left();

		// Extend Y coordinate
		$this->_current_y = $line_bottom;

		// Render the deferred floats
		for ( $i = 0, $size = count( $this->_deferred_floats ); $i < $size; $i ++ ) {
			$this->_deferred_floats[ $i ]->reflow_static_float( $this, $context );
		};
		// Clear deferred float list
		$this->_deferred_floats = array();

		// modify the current-x value, so that next inline box will not intersect any floating boxes
		$this->_current_x = $context->float_left_x( $this->_current_x, $this->_current_y );

		$this->_first_line = false;
	}

	function append_line( &$item ) {
		$this->_line[] =& $item;
	}

	// Line box should be treated as empty in following cases:
	// 1. It is really empty (so, it contains 0 boxes)
	// 2. It contains only whitespace boxes
	function line_box_empty() {
		$size = count( $this->_line );
		if ( $size == 0 ) {
			return true;
		}

		// Scan line box
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( ! is_whitespace( $this->_line[ $i ] ) &&
			     ! $this->_line[ $i ]->is_null() ) {
				return false;
			};
		}

		// No non-whitespace boxes were found
		return true;
	}

	function reflow_anchors( &$viewport, &$anchors, $page_heights ) {
		GenericFormattedBox::reflow_anchors( $viewport, $anchors, $page_heights );

		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->content[ $i ]->reflow_anchors( $viewport, $anchors, $page_heights );
		}
	}

	function fitFloats( &$context ) {
		$float_bottom = $context->float_bottom();
		if ( ! is_null( $float_bottom ) ) {
			$this->extend_height( $float_bottom );
		};

		$float_right = $context->float_right();
		if ( ! is_null( $float_right ) ) {
			$this->extend_width( $float_right );
		};
	}

	function reflow_content( &$context ) {
		$text_indent = $this->get_css_property( CSS_TEXT_INDENT );

		$this->close_line( $context );

		$this->_first_line = true;

		// If first child is inline - apply text-indent
		$first = $this->get_first();
		if ( ! is_null( $first ) ) {
			if ( is_inline( $first ) ) {
				$this->_current_x += $text_indent->calculate( $this );
				$this->_current_x += $this->_additional_text_indent;
			};
		};

		$this->height = 0;
		// Reset current Y value
		$this->_current_y = $this->get_top();

		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];
			$child->reflow( $this, $context );
		};

		$this->close_line( $context, true );
	}

	function reflow_inline() {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->content[ $i ]->reflow_inline();
		};
	}

	function reflow_text( &$viewport ) {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( is_null( $this->content[ $i ]->reflow_text( $viewport ) ) ) {
				return null;
			};
		}

		return true;
	}

	/**
	 * Position/size current box as floating one
	 */
	function reflow_static_float( &$parent, &$context ) {
		// Defer the float rendering till the next line box
		if ( ! $parent->line_box_empty() ) {
			$parent->add_deferred_float( $this );

			return;
		};

		// Calculate margin values if they have been set as a percentage
		$this->_calc_percentage_margins( $parent );
		$this->_calc_percentage_padding( $parent );

		// Calculate width value if it have been set as a percentage
		$this->_calc_percentage_width( $parent, $context );

		// Calculate margins and/or width is 'auto' values have been specified
		$this->_calc_auto_width_margins( $parent );

		// Determine the actual width of the floating box
		// Note that get_max_width returns both content and extra width
		$this->put_full_width( $this->get_max_width_natural( $context, $this->parent->get_width() ) );

		// We need to call this function before determining the horizontal coordinate
		// as after vertical offset the additional space to the left may apperar
		$y = $this->apply_clear( $parent->_current_y, $context );

		// determine the position of top-left floating box corner
		if ( $this->get_css_property( CSS_FLOAT ) === FLOAT_RIGHT ) {
			$context->float_right_xy( $parent, $this->get_full_width(), $x, $y );
			$x -= $this->get_full_width();
		} else {
			$context->float_left_xy( $parent, $this->get_full_width(), $x, $y );
		};

		// Note that $x and $y contain just a free space corner coordinate;
		// If our float has a margin/padding space, we'll need to offset ot a little;
		// Remember that float margins are never collapsed!
		$this->moveto( $x + $this->get_extra_left(), $y - $this->get_extra_top() );

		// Reflow contents.
		// Note that floating box creates a new float flow context for it children.

		$context->push_floats();

		// Floating box create a separate margin collapsing context
		$context->push_collapsed_margin( 0 );

		$this->reflow_content( $context );

		$context->pop_collapsed_margin();

		// Floats and boxes with overflow: hidden
		// should completely enclose its child floats
		$this->fitFloats( $context );

		// restore old float flow context
		$context->pop_floats();

		// Add this  box to the list of floats in current context
		$context->add_float( $this );

		// Now fix the value of _current_x for the parent box; it is required
		// in the following case:
		// <body><img align="left">some text
		// in such situation floating image is flown immediately, but it the close_line call have been made before,
		// so _current_x value of container box will be still equal to ots left content edge; by calling float_left_x again,
		// we'll force "some text" to be offset to the right
		$parent->_current_x = $context->float_left_x( $parent->_current_x, $parent->_current_y );
	}

	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		$previous_whitespace = false;
		$linebox_started     = false;

		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];

			$child->reflow_whitespace( $linebox_started, $previous_whitespace );
		};

		// remove the last whitespace in block box
		$this->remove_last_whitespace();

		// Non-inline box have terminated; we may be sure that line box will be closed
		// at this moment and new line box after this will be generated
		if ( ! is_inline( $this ) ) {
			$linebox_started = false;
		};

		return;
	}

	function remove_last_whitespace() {
		if ( count( $this->content ) == 0 ) {
			return;
		};

		$i    = count( $this->content ) - 1;
		$last = $this->content[ $i ];
		while ( $i >= 0 && is_whitespace( $this->content[ $i ] ) ) {
			$this->remove( $this->content[ $i ] );

			$i --;
			if ( $i >= 0 ) {
				$last = $this->content[ $i ];
			};
		};

		if ( $i >= 0 ) {
			if ( is_container( $this->content[ $i ] ) ) {
				$this->content[ $i ]->remove_last_whitespace();
			};
		};
	}

	function remove( &$box ) {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( $this->content[ $i ]->uid === $box->uid ) {
				$this->content[ $i ] = NullBox::create();
			};
		};

		return;
	}

	function is_first( &$box ) {
		$first =& $this->get_first();

		// Check if there's no first box at all
		//
		if ( is_null( $first ) ) {
			return false;
		};

		return $first->uid == $box->uid;
	}

	function is_null() {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( ! $this->content[ $i ]->is_null() ) {
				return false;
			};
		};

		return true;
	}

	// Calculate the available widths - e.g. content width minus space occupied by floats;
	// as floats may not fill the whole height of this box, this value depends on Y-coordinate.
	// We use current_Y in calculations
	//
	function get_available_width( &$context ) {
		$left_float_width  = $context->float_left_x( $this->get_left(), $this->_current_y ) - $this->get_left();
		$right_float_width = $this->get_right() - $context->float_right_x( $this->get_right(), $this->_current_y );

		return $this->get_width() - $left_float_width - $right_float_width;
	}

	function pre_reflow_images() {
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->content[ $i ]->pre_reflow_images();
		};
	}

	function _setupClip( &$driver ) {
		if ( ! is_null( $this->parent ) ) {
			$this->parent->_setupClip( $driver );
		};

		$overflow = $this->get_css_property( CSS_OVERFLOW );
		if ( $overflow !== OVERFLOW_VISIBLE && ! $GLOBALS['g_config']['debugnoclip'] ) {
			$driver->moveto( $this->get_left_border(), $this->get_top_border() );
			$driver->lineto( $this->get_right_border(), $this->get_top_border() );
			$driver->lineto( $this->get_right_border(), $this->get_bottom_border() );
			$driver->lineto( $this->get_left_border(), $this->get_bottom_border() );
			$driver->closepath();
			$driver->clip();
		};
	}

	/**
	 * DOMish functions
	 */
	function &get_element_by_id( $id ) {
		if ( isset( $GLOBALS['__html_box_id_map'] ) ) {
			return $GLOBALS['__html_box_id_map'][ $id ];
		} else {
			$dummy = null;

			return $dummy;
		};
	}

	/*
   *  this is just a fake at the moment
   */
	function get_body() {
		return $this;
	}

	function getChildNodes() {
		return $this->content;
	}
}


class GenericInlineBox extends GenericContainerBox {
	function GenericInlineBox() {
		$this->GenericContainerBox();
	}

	// @todo this code is duplicated in box.block.php
	//
	function reflow( &$parent, &$context ) {
		switch ( $this->get_css_property( CSS_POSITION ) ) {
			case POSITION_STATIC:
				return $this->reflow_static( $parent, $context );

			case POSITION_RELATIVE:
				/**
				 * CSS 2.1:
				 * Once a box has been laid out according to the normal flow or floated, it may be shifted relative
				 * to this position. This is called relative positioning. Offsetting a box (B1) in this way has no
				 * effect on the box (B2) that follows: B2 is given a position as if B1 were not offset and B2 is
				 * not re-positioned after B1's offset is applied. This implies that relative positioning may cause boxes
				 * to overlap. However, if relative positioning causes an 'overflow:auto' box to have overflow, the UA must
				 * allow the user to access this content, which, through the creation of scrollbars, may affect layout.
				 *
				 * @link http://www.w3.org/TR/CSS21/visuren.html#x28 CSS 2.1 Relative positioning
				 */

				$this->reflow_static( $parent, $context );
				$this->offsetRelative();

				return;
		}
	}

	// Checks if current inline box should cause a line break inside the parent box
	//
	// @param $parent reference to a parent box
	// @param $content flow context
	// @return true if line break occurred; false otherwise
	//
	function maybe_line_break( &$parent, &$context ) {
		if ( ! $parent->line_break_allowed() ) {
			return false;
		};

		// Calculate the x-coordinate of this box right edge
		$right_x = $this->get_full_width() + $parent->_current_x;

		$need_break = false;

		// Check for right-floating boxes
		// If upper-right corner of this inline box is inside of some float, wrap the line
		if ( $context->point_in_floats( $right_x, $parent->_current_y ) ) {
			$need_break = true;
		};

		// No floats; check if we had run out the right edge of container
		// TODO: nobr-before, nobr-after

		if ( ( $right_x > $parent->get_right() + EPSILON ) ) {
			// Now check if parent line box contains any other boxes;
			// if not, we should draw this box unless we have a floating box to the left

			$first = $parent->get_first();

			// FIXME: what's this? This condition is invariant!
			$text_indent   = $parent->get_css_property( CSS_TEXT_INDENT );
			$indent_offset = ( $first->uid == $this->uid || 1 ) ? $text_indent->calculate( $parent ) : 0;

			if ( $parent->_current_x > $parent->get_left() + $indent_offset + EPSILON ) {
				$need_break = true;
			};
		}

		// As close-line will not change the current-Y parent coordinate if no
		// items were in the line box, we need to offset this explicitly in this case
		//
		if ( $parent->line_box_empty() && $need_break ) {
			$parent->_current_y -= $this->get_height();
		};

		if ( $need_break ) {
			$parent->close_line( $context );

			// Check if parent inline boxes have left padding/margins and add them to current_x
			$element = $this->parent;
			while ( ! is_null( $element ) && is_a( $element, "GenericInlineBox" ) ) {
				$parent->_current_x += $element->get_extra_left();
				$element            = $element->parent;
			};
		};

		return $need_break;
	}

	function get_ascender() {
		$first =& $this->get_first();
		if ( is_null( $first ) ) {
			return 0;
		};

		return $first->get_ascender();
	}

	function get_baseline() {
		$first =& $this->get_first();
		if ( is_null( $first ) ) {
			return 0;
		};

		return $first->get_baseline();
	}

	function get_descender() {
		$first =& $this->get_first();
		if ( is_null( $first ) ) {
			return 0;
		};

		return $first->get_descender();
	}
}

// $Header: /cvsroot/html2ps/box.inline.php,v 1.53 2007/01/24 18:55:44 Konstantin Exp $

require_once( HTML2PS_DIR . 'encoding.inc.php' );

define( 'SYMBOL_SHY', code_to_utf8( 0xAD ) );
define( 'BROKEN_SYMBOL', chr( 0xC2 ) );

class LineBox {
	var $top;
	var $right;
	var $bottom;
	var $left;

	function LineBox() {
	}

	function &copy() {
		$box         =& new LineBox;
		$box->top    = $this->top;
		$box->right  = $this->right;
		$box->bottom = $this->bottom;
		$box->left   = $this->left;

		return $box;
	}

	function offset( $dx, $dy ) {
		$this->top    += $dy;
		$this->bottom += $dy;
		$this->left   += $dx;
		$this->right  += $dx;
	}

	function create( &$box ) {
		$lbox         = new LineBox;
		$lbox->top    = $box->get_top();
		$lbox->right  = $box->get_right();
		$lbox->bottom = $box->get_bottom();
		$lbox->left   = $box->get_left();

		// $lbox->bottom = $box->get_top() - $box->get_baseline() - $box->get_descender();
		// $lbox->top    = $box->get_top() - $box->get_baseline() + $box->get_ascender();
		return $lbox;
	}

	function extend( &$box ) {
		$base = $box->get_top() - $box->get_baseline();

		$this->top    = max( $this->top, $base + $box->get_ascender() );
		$this->right  = max( $this->right, $box->get_right() );
		$this->bottom = min( $this->bottom, $base - $box->get_descender() );

		// Left edge of the line box should never be modified
	}

	function fake_box( &$box ) {
		// Create the fake box object

		$fake_state = new CSSState( CSS::get() );
		$fake_state->pushState();

		$fake     = null;
		$fake_box = new BlockBox( $fake );
		$fake_box->readCSS( $fake_state );

		// Setup fake box size
		$fake_box->put_left( $this->left );
		$fake_box->put_width( $this->right - $this->left );
		$fake_box->put_top( $this->top - $box->baseline );
		$fake_box->put_height( $this->top - $this->bottom );

		// Setup padding value
		$fake_box->setCSSProperty( CSS_PADDING, $box->get_css_property( CSS_PADDING ) );

		// Setup fake box border and background
		$fake_box->setCSSProperty( CSS_BACKGROUND, $box->get_css_property( CSS_BACKGROUND ) );
		$fake_box->setCSSProperty( CSS_BORDER, $box->get_css_property( CSS_BORDER ) );

		return $fake_box;
	}
}

class InlineBox extends GenericInlineBox {
	var $_lines;

	function InlineBox() {
		// Call parent's constructor
		$this->GenericInlineBox();

		// Clear the list of line boxes inside this box
		$this->_lines = array();
	}

	function &create( &$root, &$pipeline ) {
		// Create contents of this inline box
		if ( $root->node_type() == XML_TEXT_NODE ) {
			$css_state =& $pipeline->get_current_css_state();
			$box       = InlineBox::create_from_text( $root->content,
				$css_state->get_property( CSS_WHITE_SPACE ),
				$pipeline );

			return $box;
		} else {
			$box =& new InlineBox();

			$css_state =& $pipeline->get_current_css_state();

			$box->readCSS( $css_state );

			// Initialize content
			$child = $root->first_child();
			while ( $child ) {
				$child_box =& create_pdf_box( $child, $pipeline );
				$box->add_child( $child_box );
				$child = $child->next_sibling();
			};

			// Add fake whitespace box with zero size for the anchor spans
			// We need this, as "reflow" functions will automatically remove empty inline boxes from the
			// document tree
			//
			if ( $box->is_null() ) {
				$css_state->pushState();
				$css_state->set_property( CSS_FONT_SIZE, Value::fromData( 0.01, UNIT_PT ) );

				$whitespace = WhitespaceBox::create( $pipeline );
				$whitespace->readCSS( $css_state );

				$box->add_child( $whitespace );

				$css_state->popState();
			};
		}

		return $box;
	}

	function &create_from_text( $text, $white_space, &$pipeline ) {
		$box =& new InlineBox();
		$box->readCSS( $pipeline->get_current_css_state() );

		// Apply/inherit text-related CSS properties
		$css_state =& $pipeline->get_current_css_state();
		$css_state->pushDefaultTextState();

		require_once( HTML2PS_DIR . 'inline.content.builder.factory.php' );
		$inline_content_builder =& InlineContentBuilderFactory::get( $white_space );
		$inline_content_builder->build( $box, $text, $pipeline );

		// Clear the CSS stack
		$css_state->popState();

		return $box;
	}

	function &get_line_box( $index ) {
		$line_box =& $this->_lines[ $index ];

		return $line_box;
	}

	function get_line_box_count() {
		return count( $this->_lines );
	}

	// Inherited from GenericFormattedBox

	function process_word( $raw_content, &$pipeline ) {
		if ( $raw_content === '' ) {
			return false;
		}

		$ptr      = 0;
		$word     = '';
		$hyphens  = array();
		$encoding = 'iso-8859-1';

		$manager_encoding =& ManagerEncoding::get();
		$text_box         =& TextBox::create_empty( $pipeline );

		$len = strlen( $raw_content );
		while ( $ptr < $len ) {
			$char = $manager_encoding->get_next_utf8_char( $raw_content, $ptr );

			// Check if current  char is a soft hyphen  character. It it is,
			// remove it from the word  (as it should not be drawn normally)
			// and store its location
			if ( $char == SYMBOL_SHY ) {
				$hyphens[] = strlen( $word );
			} else {
				$mapping = $manager_encoding->get_mapping( $char );

				/**
				 * If this character is not found in predefined encoding vectors,
				 * we'll use "Custom" encoding and add single-character TextBox
				 *
				 * @TODO: handle characters without known glyph names
				 */
				if ( is_null( $mapping ) ) {
					/**
					 * No mapping to default encoding vectors found for this character
					 */

					/**
					 * Add last word
					 */
					if ( $word !== '' ) {
						$text_box->add_subword( $word, $encoding, $hyphens );
					};

					/**
					 * Add current symbol
					 */
					$custom_char = $manager_encoding->add_custom_char( utf8_to_code( $char ) );
					$text_box->add_subword( $custom_char, $manager_encoding->get_current_custom_encoding_name(), $hyphens );

					$word = '';
				} else {
					if ( isset( $mapping[ $encoding ] ) ) {
						$word .= $mapping[ $encoding ];
					} else {
						// This condition prevents empty text boxes from appearing; say, if word starts with a national
						// character, an () - text box with no letters will be generated, in rare case causing a random line
						// wraps, if container is narrow
						if ( $word !== '' ) {
							$text_box->add_subword( $word, $encoding, $hyphens );
						};

						reset( $mapping );
						list( $encoding, $add ) = each( $mapping );

						$word    = $mapping[ $encoding ];
						$hyphens = array();
					};
				};
			};
		};

		if ( $word !== '' ) {
			$text_box->add_subword( $word, $encoding, $hyphens );
		};

		$this->add_child( $text_box );

		return true;
	}

	function show( &$driver ) {
		if ( $this->get_css_property( CSS_POSITION ) == POSITION_RELATIVE ) {
			// Postpone
			return true;
		};

		return $this->_show( $driver );
	}

	function show_postponed( &$driver ) {
		return $this->_show( $driver );
	}

	function _show( &$driver ) {
		// Show line boxes background and borders
		$size = $this->get_line_box_count();
		for ( $i = 0; $i < $size; $i ++ ) {
			$line_box = $this->get_line_box( $i );
			$fake_box = $line_box->fake_box( $this );

			$background = $this->get_css_property( CSS_BACKGROUND );
			$border     = $this->get_css_property( CSS_BORDER );

			$background->show( $driver, $fake_box );
			$border->show( $driver, $fake_box );
		};

		// Show content
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			if ( is_null( $this->content[ $i ]->show( $driver ) ) ) {
				return null;
			};
		}

		return true;
	}

	// Initialize next line box inside this inline
	//
	// Adds the next element to _lines array inside the current object and initializes it with the
	// $box parameters
	//
	// @param $box child box which will be first in this line box
	// @param $line_no number of line box
	//
	function init_line( &$box, &$line_no ) {
		$line_box                 = LineBox::create( $box );
		$this->_lines[ $line_no ] = $line_box;
	}

	// Extends the existing line box to include the given child
	// OR starts new line box, if current child is to the left of the box right edge
	// (which should not happen white the line box is filled)
	//
	// @param $box child box which will be first in this line box
	// @param $line_no number of line box
	//
	function extend_line( &$box, $line_no ) {
		if ( ! isset( $this->_lines[ $line_no ] ) ) {
			// New line box started
			$this->init_line( $box, $line_no );

			return $line_no;
		};

		// Check if this box starts a new line
		if ( $box->get_left() < $this->_lines[ $line_no ]->right ) {
			$line_no ++;
			$this->init_line( $box, $line_no );

			return $line_no;
		};

		$this->_lines[ $line_no ]->extend( $box );

		return $line_no;
	}

	function merge_line( &$box, $line_no ) {
		$start_line = 0;

		if ( $line_no > 0 && count( $box->_lines ) > 0 ) {
			if ( $this->_lines[ $line_no - 1 ]->right + EPSILON > $box->_lines[0]->left ) {
				$this->_lines[ $line_no - 1 ]->right  = max( $box->_lines[0]->right, $this->_lines[ $line_no - 1 ]->right );
				$this->_lines[ $line_no - 1 ]->top    = max( $box->_lines[0]->top, $this->_lines[ $line_no - 1 ]->top );
				$this->_lines[ $line_no - 1 ]->bottom = min( $box->_lines[0]->bottom, $this->_lines[ $line_no - 1 ]->bottom );
				$start_line                           = 1;
			};
		};

		$size = count( $box->_lines );
		for ( $i = $start_line; $i < $size; $i ++ ) {
			$this->_lines[] = $box->_lines[ $i ]->copy();
		};

		return count( $this->_lines );
	}

	function reflow_static( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// Note that inline boxes (actually SPANS)
		// are never added to the parent's line boxes

		// Move current box to the parent's current coordinates
		// Note that span box will start at the far left of the parent, NOT on its current X!
		// Also, note that inline box can have margins, padding and borders!

		$this->put_left( $parent->get_left() );
		$this->put_top( $parent->get_top() - $this->get_extra_top() );

		// first line of the SPAN will be offset to its parent current-x
		// PLUS the left padding of current span!
		$parent->_current_x += $this->get_extra_left();
		$this->_current_x   = $parent->_current_x;

		// Note that the same operation IS NOT applied to parent current-y!
		// The padding space is just extended to the top possibly OVERLAPPING the above boxes.

		$this->width = 0;

		// Reflow contents
		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];

			// Add current element into _parent_ line box and reflow it
			$child->reflow( $parent, $context );

			// In general, if inline box centained whitespace box only,
			// it could be removed during reflow function call;
			// let's check it and skip to next child
			//
			// if no children left AT ALL (so this box is empty), just exit

			// Track the real height of the inline box; it will be used by other functions
			// (say, functions calculating content height)

			$this->extend_height( $child->get_bottom_margin() );
		};

		// Apply right extra space value (padding + border + margin)
		$parent->_current_x += $this->get_extra_right();

		// Margins of inline boxes are not collapsed

		if ( $this->get_first_data() ) {
			$context->pop_collapsed_margin();
			$context->push_collapsed_margin( 0 );
		};
	}

	function reflow_inline() {
		$line_no = 0;

		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];
			$child->reflow_inline();

			if ( ! $child->is_null() ) {
				if ( is_a( $child, 'InlineBox' ) ) {
					$line_no = $this->merge_line( $child, $line_no );
				} else {
					$line_no = $this->extend_line( $child, $line_no );
				};
			};
		};
	}

	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		/**
		 * Anchors could have no content at all (like <a name="test"></a>).
		 * We should not remove such anchors, as this will break internal links
		 * in the document.
		 */
		$dest = $this->get_css_property( CSS_HTML2PS_LINK_DESTINATION );
		if ( ! is_null( $dest ) ) {
			return;
		};

		$size = count( $this->content );
		for ( $i = 0; $i < $size; $i ++ ) {
			$child =& $this->content[ $i ];
			$child->reflow_whitespace( $linebox_started, $previous_whitespace );
		};

		if ( $this->is_null() ) {
			$this->parent->remove( $this );
		};
	}

	function get_extra_line_left() {
		return $this->get_extra_left() + ( $this->parent ? $this->parent->get_extra_line_left() : 0 );
	}

	function get_extra_line_right() {
		return $this->get_extra_right() + ( $this->parent ? $this->parent->get_extra_line_right() : 0 );
	}

	/**
	 * As "nowrap" properties applied to block-level boxes only, we may use simplified version of
	 * 'get_min_width' here
	 */
	function get_min_width( &$context ) {
		if ( isset( $this->_cache[ CACHE_MIN_WIDTH ] ) ) {
			return $this->_cache[ CACHE_MIN_WIDTH ];
		}

		$content_size = count( $this->content );

		/**
		 * If box does not have any content, its minimal width is determined by extra horizontal space
		 */
		if ( $content_size == 0 ) {
			return $this->_get_hor_extra();
		};

		$minw = $this->content[0]->get_min_width( $context );

		for ( $i = 1; $i < $content_size; $i ++ ) {
			$item = $this->content[ $i ];
			if ( ! $item->out_of_flow() ) {
				$minw = max( $minw, $item->get_min_width( $context ) );
			};
		}

		// Apply width constraint to min width. Return maximal value
		$wc        = $this->get_css_property( CSS_WIDTH );
		$min_width = max( $minw, $wc->apply( $minw, $this->parent->get_width() ) ) + $this->_get_hor_extra();

		$this->_cache[ CACHE_MIN_WIDTH ] = $min_width;

		return $min_width;
	}

	// Restore default behaviour, as this class is a ContainerBox descendant
	function get_max_width_natural( &$context, $limit = 10E6 ) {
		return $this->get_max_width( $context, $limit );
	}

	function offset( $dx, $dy ) {
		$size = count( $this->_lines );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->_lines[ $i ]->offset( $dx, $dy );
		};
		GenericInlineBox::offset( $dx, $dy );
	}

	/**
	 * Deprecated
	 */
	function getLineBoxCount() {
		return $this->get_line_box_count();
	}

	function &getLineBox( $index ) {
		return $this->get_line_box( $index );
	}
}

;


// $Header: /cvsroot/html2ps/box.inline.control.php,v 1.7 2006/09/07 18:38:12 Konstantin Exp $

class InlineControlBox extends InlineBox {
	function InlineControlBox() {
		$this->InlineBox();
	}

	function get_min_width( &$context, $limit = 10E6 ) {
		return $this->get_max_width( $context, $limit );
	}

	function get_max_width( &$context, $limit = 10E6 ) {
		return
			GenericContainerBox::get_max_width( $context, $limit ) -
			$this->_get_hor_extra();
	}

	function line_break_allowed() {
		return false;
	}

	function reflow_static( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// Determine the box width
		$this->_calc_percentage_width( $parent, $context );
		$this->put_full_width( $this->get_min_width( $context, $parent->get_width() ) );
		$this->setCSSProperty( CSS_WIDTH, new WCNone() );

		// Check if we need a line break here
		$this->maybe_line_break( $parent, $context );

		// append to parent line box
		$parent->append_line( $this );

		// Determine coordinates of upper-left _margin_ corner
		$this->guess_corner( $parent );

		$this->reflow_content( $context );

		/**
		 * After text content have been reflown, we may determine the baseline of the control item itself;
		 *
		 * As there will be some extra whitespace on the top of the control box, we must add this whitespace
		 * to the calculated baseline value, so text before and after control item will be aligned
		 * with the text inside the box.
		 */
		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

		// center the text vertically inside the control
		$text  =& $this->content[0];
		$delta = ( $text->get_top() - $text->get_height() / 2 ) - ( $this->get_top() - $this->get_height() / 2 );
		$text->offset( 0, - $delta );

		// Offset parent current X coordinate
		$parent->_current_x += $this->get_full_width();

		// Extends parents height
		$parent->extend_height( $this->get_bottom_margin() );
	}

	function setup_content( $text, &$pipeline ) {
		/**
		 * Contents of the text box are somewhat similar to the inline box:
		 * a sequence of the text and whitespace boxes; we generate this sequence using
		 * the InlineBox, then copy contents of the created inline box to our button.
		 *
		 * @todo probably, create_from_text() function should be extracted to the common parent
		 * of inline boxes.
		 */
		$ibox = InlineBox::create_from_text( $text, WHITESPACE_PRE, $pipeline );

		if ( count( $ibox->content ) == 0 ) {
			$this->append_child( TextBox::create( ' ', 'iso-8859-1', $pipeline ) );
		} else {
			for ( $i = 0, $size = count( $ibox->content ); $i < $size; $i ++ ) {
				$this->append_child( $ibox->content[ $i ] );
			};
		};
	}

	function show( &$viewport ) {
		// Now set the baseline of a button box to align it vertically when flowing isude the
		// text line
		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

		return GenericContainerBox::show( $viewport );
	}
}


$GLOBALS['g_last_assigned_font_id'] = 0;

class Font {
	var $underline_position;
	var $underline_thickness;
	var $ascender;
	var $descender;
	var $char_widths;
	var $bbox;

	function ascender() {
		return $this->ascender;
	}

	function descender() {
		return $this->descender;
	}

	function error_message() {
		return $this->error_message;
	}

	function Font() {
	}

	function linethrough_position() {
		return $this->bbox[3] * 0.25;
	}

	function name() {
		return $this->name;
	}

	function overline_position() {
		return $this->bbox[3] * 0.8;
	}

	function points( $fontsize, $dimension ) {
		return $dimension * $fontsize / 1000;
	}

	function stringwidth( $string ) {
		$width = 0;

		$length = strlen( $string );
		for ( $i = 0; $i < $length; $i ++ ) {
			$width += $this->char_widths[ $string{$i} ];
		};

		return $width;
	}

	function underline_position() {
		return $this->underline_position;
	}

	function underline_thickness() {
		return $this->underline_thickness;
	}
}

class FontTrueType extends Font {
	function create( $fontfile, $encoding ) {
		$font = new FontTrueType();
		$font->_read( TTF_FONTS_REPOSITORY . $fontfile, $encoding );

		return $font;
	}

	/**
	 * TODO: cache results; replace makefont with this utility
	 */
	function _read( $file, $encoding ) {
		error_log( sprintf( "Parsing font file file %s for encoding %s", $file, $encoding ) );

		$font = new OpenTypeFile();
		$font->open( $file );
		$hhea     = $font->getTable( 'hhea' );
		$head     = $font->getTable( 'head' );
		$hmtx     = $font->getTable( 'hmtx' );
		$post     = $font->getTable( 'post' );
		$cmap     = $font->getTable( 'cmap' );
		$subtable = $cmap->findSubtable( OT_CMAP_PLATFORM_WINDOWS,
			OT_CMAP_PLATFORM_WINDOWS_UNICODE );

		/**
		 * Read character widths for selected encoding
		 */
		$widths  = array();
		$manager = ManagerEncoding::get();
		$map     = $manager->get_encoding_vector( $encoding );
		foreach ( $map as $code => $ucs2 ) {
			$glyphIndex = $subtable->lookup( $ucs2 );
			if ( ! is_null( $glyphIndex ) ) {
				$widths[ $code ] = floor( $hmtx->_hMetrics[ $glyphIndex ]['advanceWidth'] * 1000 / $head->_unitsPerEm );
			} else {
				$widths[ $code ] = DEFAULT_CHAR_WIDTH;
			};
		};

		// Fill unknown characters with the default char width
		for ( $i = 0; $i < 256; $i ++ ) {
			if ( ! isset( $widths[ chr( $i ) ] ) ) {
				$widths[ chr( $i ) ] = DEFAULT_CHAR_WIDTH;
			};
		};

		$this->ascender            = floor( $hhea->_ascender * 1000 / $head->_unitsPerEm );
		$this->descender           = floor( $hhea->_descender * 1000 / $head->_unitsPerEm );
		$this->bbox                = array(
			$head->_xMin * 1000 / $head->_unitsPerEm,
			$head->_yMin * 1000 / $head->_unitsPerEm,
			$head->_xMax * 1000 / $head->_unitsPerEm,
			$head->_yMax * 1000 / $head->_unitsPerEm
		);
		$this->underline_position  = floor( $post->_underlinePosition * 1000 / $head->_unitsPerEm );
		$this->underline_thickness = floor( $post->_underlineThickness * 1000 / $head->_unitsPerEm );
		$this->char_widths         = $widths;

		$font->close();
	}
}

// Note that ALL font dimensions are measured in 1/1000 of font size units;
//
class FontType1 extends Font {
	function &create( $typeface, $encoding, $font_resolver, &$error_message ) {
		$font = new FontType1();

		$font->underline_position  = 0;
		$font->underline_thickness = 0;
		$font->ascender;
		$font->descender;
		$font->char_widths = array();
		$font->bbox        = array();

		global $g_last_assigned_font_id;
		$g_last_assigned_font_id ++;

		$font->name = "font" . $g_last_assigned_font_id;

		// Get and load the metrics file
		$afm = $font_resolver->get_afm_mapping( $typeface );

		if ( ! $font->_parse_afm( $afm, $typeface, $encoding ) ) {
			$error_message = $font->error_message();
			$dummy         = null;

			return $dummy;
		};

		return $font;
	}

	// Parse the AFM metric file; keep only sized of glyphs present in the chosen encoding
	function _parse_afm( $afm, $typeface, $encoding ) {
		global $g_manager_encodings;
		$encoding_data = $g_manager_encodings->get_glyph_to_code_mapping( $encoding );

		$filename = TYPE1_FONTS_REPOSITORY . $afm . ".afm";

		$file = @fopen( $filename, 'r' );
		if ( ! $file ) {
			$_filename = $filename;
			$_typeface = $typeface;

			ob_start();
			include( HTML2PS_DIR . 'templates/error._missing_afm.tpl' );
			$this->error_message = ob_get_contents();
			ob_end_clean();

			error_log( sprintf( "Missing font metrics file: %s", $filename ) );

			return false;
		};

		while ( $line = fgets( $file ) ) {
			if ( preg_match( "/C\s-?\d+\s;\sWX\s(\d+)\s;\sN\s(\S+)\s;/", $line, $matches ) ) {
				$glyph_width = $matches[1];
				$glyph_name  = $matches[2];

				// This line is a character width definition
				if ( isset( $encoding_data[ $glyph_name ] ) ) {
					foreach ( $encoding_data[ $glyph_name ] as $c ) {
						$this->char_widths[ $c ] = $glyph_width;
					};
				};

			} elseif ( preg_match( "/UnderlinePosition ([\d-]+)/", $line, $matches ) ) {
				// This line is an underline position line
				$this->underline_position = $matches[1];

			} elseif ( preg_match( "/UnderlineThickness ([\d-]+)/", $line, $matches ) ) {
				// This line is an underline thickness line
				$this->underline_thickness = $matches[1];

			} elseif ( preg_match( "/Ascender ([\d-]+)/", $line, $matches ) ) {
				// This line is an ascender line
				$this->ascender = $matches[1];

			} elseif ( preg_match( "/Descender ([\d-]+)/", $line, $matches ) ) {
				// This line is an descender line
				$this->descender = $matches[1];

			} elseif ( preg_match( "/FontBBox ([\d-]+) ([\d-]+) ([\d-]+) ([\d-]+)/", $line, $matches ) ) {
				// This line is an font BBox line
				$this->bbox = array( $matches[1], $matches[2], $matches[3], $matches[4] );
			};
		};

		fclose( $file );

		// Fill unknown characters with the default char width
		for ( $i = 0; $i < 256; $i ++ ) {
			if ( ! isset( $this->char_widths[ chr( $i ) ] ) ) {
				$this->char_widths[ chr( $i ) ] = DEFAULT_CHAR_WIDTH;
			};
		};

		return true;
	}
}

class FontFactory {
	var $fonts;
	var $error_message;

	var $_trueType;

	function error_message() {
		return $this->error_message;
	}

	function FontFactory() {
		$this->fonts = array();
	}

	/**
	 * Note that typeface  is not a font file  name; example of typeface
	 * name  could  be  'Times-Roman'  or  'ArialUnicodeMS'.  Note  that
	 * typeface  names  are  for  internal  use only,  as  they  do  not
	 * correspond  to  any system  font  names/parameters; all  typeface
	 * names and their relateions to system fonts are defined in html2ps
	 * config
	 *
	 * @param $typeface String name of the font typeface
	 * @param $encoding String
	 *
	 */
	function &getTrueType( $typeface, $encoding ) {
		if ( ! isset( $this->fonts[ $typeface ][ $encoding ] ) ) {
			global $g_font_resolver_pdf;
			$fontfile = $g_font_resolver_pdf->ttf_mappings[ $typeface ];

			$font = FontTrueType::create( $fontfile, $encoding );
			if ( is_null( $font ) ) {
				$dummy = null;

				return $dummy;
			};

			$this->fonts[ $typeface ][ $encoding ] = $font;
		};

		return $this->fonts[ $typeface ][ $encoding ];
	}

	function &get_type1( $name, $encoding ) {
		if ( ! isset( $this->fonts[ $name ][ $encoding ] ) ) {
			global $g_font_resolver;

			$font =& FontType1::create( $name, $encoding, $g_font_resolver, $this->error_message );
			if ( is_null( $font ) ) {
				$dummy = null;

				return $dummy;
			};

			$this->fonts[ $name ][ $encoding ] = $font;
		};

		return $this->fonts[ $name ][ $encoding ];
	}
}


// $Header: /cvsroot/html2ps/box.br.php,v 1.31 2006/11/11 13:43:51 Konstantin Exp $

require_once( HTML2PS_DIR . 'layout.vertical.php' );

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * Class defined in this file handles the layout of "BR" HTML elements
 */

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * The BRBox class dessribed the behavior of the BR HTML elements
 *
 * @link http://www.w3.org/TR/html4/struct/text.html#edef-BR HTML 4.01 Forcing a line break: the BR element
 */
class BRBox extends GenericBox {
	/**
	 * Create new BR element
	 */
	function BRBox() {
		$this->GenericBox();
	}

	function apply_clear( $y, &$context ) {
		return LayoutVertical::apply_clear( $this, $y, $context );
	}

	function out_of_flow() {
		return true;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		/**
		 * We treat BR as a block box; as default value of 'display' property is not 'block', we should
		 * set it up manually.
		 */
		$this->setCSSProperty( CSS_DISPLAY, 'block' );

		$this->_readCSS( $state,
			array( CSS_CLEAR ) );

		$this->_readCSSLengths( $state,
			array(
				CSS_MARGIN,
				CSS_LINE_HEIGHT
			) );
	}

	/**
	 * Create new BR element
	 *
	 * @return BRBox new BR element object
	 */
	function &create( &$pipeline ) {
		$box =& new BRBox();
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	/**
	 * BR tags do not take any horizontal space, so if minimal width is zero.
	 *
	 * @param FlowContext $context The object containing auxiliary flow data; not used here/
	 *
	 * @return int should always return constant zero.
	 */
	function get_min_width( &$context ) {
		return 0;
	}

	/**
	 * BR tags do not take any horizontal space, so if maximal width is zero.
	 *
	 * @param FlowContext $context The object containing auxiliary flow data; not used here.
	 *
	 * @return int should always return constant zero.
	 */
	function get_max_width( &$context ) {
		return 0;
	}

	/**
	 * Layout current BR element. The reflow routine is somewhat similar to the block box reflow routine.
	 * As most CSS properties do not apply to BR elements, and BR element always have parent element,
	 * the routine is much simpler.
	 *
	 * @param GenericContainerBox $parent The document element which should be treated as the parent of current element
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 */
	function reflow( &$parent, &$context ) {
		parent::reflow( $parent, $context );

		/**
		 * Apply 'clear' property; the current Y coordinate can be modified as a result of 'clear'.
		 */
		$y = $this->apply_clear( $parent->_current_y, $context );

		/**
		 * Move current "box" to parent current coordinates. It is REQUIRED, in spite of the generated
		 * box itself have no dimensions and will never be drawn, as several other routines uses box coordinates.
		 */
		$this->put_left( $parent->_current_x );
		$this->put_top( $y );

		/**
		 * If we met a sequence of BR tags (like <BR><BR>), we'll have an only one item in the parent's
		 * line box - whitespace; in this case we'll need to additionally offset current y coordinate by the font size,
		 * as whitespace alone does not affect the Y-coordinate.
		 */
		if ( $parent->line_box_empty() ) {
			/**
			 * There's no elements in the parent line box at all (e.g in the following situation:
			 * <div><br/> .. some text here...</div>); thus, as we're initiating
			 * a new line, we need to offset current Y coordinate by the font-size value.
			 */

			// Note that _current_y should be modified before 'close_line' call, as it checks for
			// left-floating boxes, causing an issues if line bottom will be placed below
			// float while line top is above float bottom margin
			$font               = $this->get_css_property( CSS_FONT );
			$fs                 = $font->size;
			$parent->_current_y = min( $this->get_bottom(),
				$parent->_current_y - $font->line_height->apply( $fs->getPoints() ) );

			$parent->close_line( $context, true );
		} else {
			/**
			 * There's at least 1 non-whitespace element in the parent line box, we do not need to use whitespace
			 * height; the bottom of the line box is defined by the non-whitespace elements. Top of the new line
			 * should be equal to that value.
			 */
			$parent->close_line( $context, true );
		};

		/**
		 * We need to explicitly extend the parent's height, to make it contain the generated line,
		 * as we don't know if it have any children _after_ this BR box. If we will not do it,
		 * the following code will be rendred incorrectly:
		 * <div>...some text...<br/></div>
		 */
		$parent->extend_height( $parent->_current_y );
	}

	/**
	 * Render the BR element; as BR element is non-visual, we do nothing here.
	 *
	 * @param OutputDriver $driver Current output device driver object.
	 *
	 * @return boolean true in case the box was successfully rendered
	 */
	function show( &$driver ) {
		return true;
	}

	/**
	 * As BR element generated a line break, it means that a new line box will be started
	 * (thus, any whitespaces immediately following the BR tag should not be rendered).
	 * To indicate this, we reset the linebox_started flag to 'false' value.
	 *
	 * @param boolean $linebox_started Flag indicating that a new line box have just started and it already contains
	 * some inline elements
	 * @param boolean $previous_whitespace Flag indicating that a previous inline element was an whitespace element.
	 *
	 * @see GenericFormattedBox::reflow_whitespace()
	 */
	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		$linebox_started = false;
	}

	function get_height() {
		return 0;
	}

	function get_width() {
		return 0;
	}

	/**
	 * BRBox may be placed inside InlineBox (white-space: pre)
	 */
	function get_ascender() {
		return 0;
	}

	function get_descender() {
		return 0;
	}

	function isLineBreak() {
		return true;
	}
}

// $Header: /cvsroot/html2ps/box.block.php,v 1.56 2007/01/24 18:55:43 Konstantin Exp $

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * Class defined in this file handles the layout of block HTML elements
 */

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * The BlockBox class describes the layout and behavior of HTML element having
 * 'display: block' CSS property.
 *
 * @link http://www.w3.org/TR/CSS21/visuren.html#block-box CSS 2.1 Block-level elements and block boxes
 */
class BlockBox extends GenericContainerBox {
	/**
	 * Create empty block element
	 */
	function BlockBox() {
		$this->GenericContainerBox();
	}

	/**
	 * Create new block element and automatically fill in its contents using
	 * parsed HTML data
	 *
	 * @param mixed $root the HTML element corresponding to the element being created
	 *
	 * @return BlockBox new BlockBox object (with contents filled)
	 *
	 * @see GenericContainerBox::create_content()
	 */
	function &create( &$root, &$pipeline ) {
		$box = new BlockBox();
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	/**
	 * Create new block element and automatically initialize its contents
	 * with the given text string
	 *
	 * @param string $content The text string to be put inside the block box
	 *
	 * @return BlockBox new BlockBox object (with contents filled)
	 *
	 * @see InlineBox
	 * @see InlineBox::create_from_text()
	 */
	function &create_from_text( $content, &$pipeline ) {
		$box = new BlockBox();
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->add_child( InlineBox::create_from_text( $content,
			$box->get_css_property( CSS_WHITE_SPACE ),
			$pipeline ) );

		return $box;
	}

	/**
	 * Layout current block element
	 *
	 * @param GenericContainerBox $parent The document element which should be treated as the parent of current element
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 * @see InlineBlockBox::reflow
	 *
	 * @todo this 'reflow' skeleton is common for all element types; thus, we probably should move the generic 'reflow'
	 * definition to the GenericFormattedBox class, leaving only box-specific 'reflow_static' definitions in specific classes.
	 *
	 * @todo make relative positioning more CSS 2.1 compliant; currently, 'bottom' and 'right' CSS properties are ignored.
	 *
	 * @todo check whether percentage values should be really ignored during relative positioning
	 */
	function reflow( &$parent, &$context ) {
		switch ( $this->get_css_property( CSS_POSITION ) ) {
			case POSITION_STATIC:
				$this->reflow_static( $parent, $context );

				return;

			case POSITION_RELATIVE:
				/**
				 * CSS 2.1:
				 * Once a box has been laid out according to the normal flow or floated, it may be shifted relative
				 * to this position. This is called relative positioning. Offsetting a box (B1) in this way has no
				 * effect on the box (B2) that follows: B2 is given a position as if B1 were not offset and B2 is
				 * not re-positioned after B1's offset is applied. This implies that relative positioning may cause boxes
				 * to overlap. However, if relative positioning causes an 'overflow:auto' box to have overflow, the UA must
				 * allow the user to access this content, which, through the creation of scrollbars, may affect layout.
				 *
				 * @link http://www.w3.org/TR/CSS21/visuren.html#x28 CSS 2.1 Relative positioning
				 */
				$this->reflow_static( $parent, $context );
				$this->offsetRelative();

				return;

			case POSITION_ABSOLUTE:
				/**
				 * If this box is positioned absolutely, it is not laid out as usual box;
				 * The reference to this element is stored in the flow context for
				 * futher reference.
				 */
				$this->guess_corner( $parent );

				return;

			case POSITION_FIXED:
				/**
				 * If this box have 'position: fixed', it is not laid out as usual box;
				 * The reference to this element is stored in the flow context for
				 * futher reference.
				 */
				$this->guess_corner( $parent );

				return;
		};
	}

	/**
	 * Reflow absolutely positioned block box. Note that according to CSS 2.1
	 * the only types of boxes which could be absolutely positioned are
	 * 'block' and 'table'
	 *
	 * @param FlowContext $context A flow context object containing the additional layout data.
	 *
	 * @link http://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo CSS 2.1: Relationships between 'display', 'position', and 'float'
	 */
	function reflow_absolute( &$context ) {
		$parent_node =& $this->get_parent_node();
		parent::reflow( $parent_node, $context );

		$width_strategy =& new StrategyWidthAbsolutePositioned();
		$width_strategy->apply( $this, $context );

		$position_strategy =& new StrategyPositionAbsolute();
		$position_strategy->apply( $this );

		$this->reflow_content( $context );

		/**
		 * As absolute-positioned box generated new flow context, extend the height to fit all floats
		 */
		$this->fitFloats( $context );
	}

	/**
	 * Reflow fixed-positioned block box. Note that according to CSS 2.1
	 * the only types of boxes which could be absolutely positioned are
	 * 'block' and 'table'
	 *
	 * @param FlowContext $context A flow context object containing the additional layout data.
	 *
	 * @link http://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo CSS 2.1: Relationships between 'display', 'position', and 'float'
	 *
	 * @todo it seems that percentage-constrained fixed block width will be calculated incorrectly; we need
	 * to use containing block width instead of $this->get_width() when applying the width constraint
	 */
	function reflow_fixed( &$context ) {
		GenericFormattedBox::reflow( $this->parent, $context );

		/**
		 * As fixed-positioned elements are placed relatively to page (so that one element may be shown
		 * several times on different pages), we cannot calculate its position at the moment.
		 * The real position of the element is calculated when it is to be shown - once for each page.
		 *
		 * @see BlockBox::show_fixed()
		 */
		$this->put_left( 0 );
		$this->put_top( 0 );

		/**
		 * As sometimes left/right values may not be set, we need to use the "fit" width here.
		 * If box have a width constraint, 'get_max_width' will return constrained value;
		 * othersise, an intrictic width will be returned.
		 *
		 * @see GenericContainerBox::get_max_width()
		 */
		$this->put_full_width( $this->get_max_width( $context ) );

		/**
		 * Update the width, as it should be calculated based upon containing block width, not real parent.
		 * After this we should remove width constraints or we may encounter problem
		 * in future when we'll try to call get_..._width functions for this box
		 *
		 * @todo Update the family of get_..._width function so that they would apply constraint
		 * using the containing block width, not "real" parent width
		 */
		$containing_block =& $this->_get_containing_block();
		$wc               = $this->get_css_property( CSS_WIDTH );
		$this->put_full_width( $wc->apply( $this->get_width(),
			$containing_block['right'] - $containing_block['left'] ) );
		$this->setCSSProperty( CSS_WIDTH, new WCNone() );

		/**
		 * Layout element's children
		 */
		$this->reflow_content( $context );

		/**
		 * As fixed-positioned box generated new flow context, extend the height to fit all floats
		 */
		$this->fitFloats( $context );
	}

	/**
	 * Layout static-positioned block box.
	 *
	 * Note that static-positioned boxes may be floating boxes
	 *
	 * @param GenericContainerBox $parent The document element which should be treated as the parent of current element
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 */
	function reflow_static( &$parent, &$context ) {
		if ( $this->get_css_property( CSS_FLOAT ) === FLOAT_NONE ) {
			$this->reflow_static_normal( $parent, $context );
		} else {
			$this->reflow_static_float( $parent, $context );
		}
	}

	/**
	 * Layout normal (non-floating) static-positioned block box.
	 *
	 * @param GenericContainerBox $parent The document element which should be treated as the parent of current element
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 */
	function reflow_static_normal( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		if ( $parent ) {
			/**
			 * Child block will fill the whole content width of the parent block.
			 *
			 * 'margin-left' + 'border-left-width' + 'padding-left' + 'width' + 'padding-right' +
			 * 'border-right-width' + 'margin-right' = width of containing block
			 *
			 * See CSS 2.1 for more detailed explanation
			 *
			 * @link http://www.w3.org/TR/CSS21/visudet.html#blockwidth CSS 2.1. 10.3.3 Block-level, non-replaced elements in normal flow
			 */

			/**
			 * Calculate margin values if they have been set as a percentage; replace percentage-based values
			 * with fixed lengths.
			 */
			$this->_calc_percentage_margins( $parent );
			$this->_calc_percentage_padding( $parent );

			/**
			 * Calculate width value if it had been set as a percentage; replace percentage-based value
			 * with fixed value
			 */
			$this->put_full_width( $parent->get_width() );
			$this->_calc_percentage_width( $parent, $context );

			/**
			 * Calculate 'auto' values of width and margins. Unlike tables, DIV width is either constrained
			 * by some CSS rules or expanded to the parent width; thus, we can calculate 'auto' margin
			 * values immediately.
			 *
			 * @link http://www.w3.org/TR/CSS21/visudet.html#Computing_widths_and_margins CSS 2.1 Calculating widths and margins
			 */
			$this->_calc_auto_width_margins( $parent );

			/**
			 * Collapse top margin
			 *
			 * @see GenericFormattedBox::collapse_margin()
			 *
			 * @link http://www.w3.org/TR/CSS21/box.html#collapsing-margins CSS 2.1 Collapsing margins
			 */
			$y = $this->collapse_margin( $parent, $context );

			/**
			 * At this moment we have top parent/child collapsed margin at the top of context object
			 * margin stack
			 */

			/**
			 * Apply 'clear' property; the current Y coordinate can be modified as a result of 'clear'.
			 */
			$y = $this->apply_clear( $y, $context );

			/**
			 * Store calculated Y coordinate as current Y coordinate in the parent box
			 * No more content will be drawn abowe this mark; current box padding area will
			 * start below.
			 */
			$parent->_current_y = $y;

			/**
			 * Terminate current parent line-box (as current box is not inline)
			 */
			$parent->close_line( $context );

			/**
			 * Add current box to the parent's line-box; we will close the line box below
			 * after content will be reflown, so the line box will contain only current box.
			 */
			$parent->append_line( $this );

			/**
			 * Now, place the current box upper left content corner. Note that we should not
			 * use get_extra_top here, as _current_y value already culculated using the top margin value
			 * of the current box! The top content edge should be offset from that level only of padding and
			 * border width.
			 */
			$border  = $this->get_css_property( CSS_BORDER );
			$padding = $this->get_css_property( CSS_PADDING );

			$this->moveto( $parent->get_left() + $this->get_extra_left(),
				$parent->_current_y - $border->top->get_width() - $padding->top->value );
		}

		/**
		 * Reflow element's children
		 */
		$this->reflow_content( $context );

		if ( $this->get_css_property( CSS_OVERFLOW ) != OVERFLOW_VISIBLE ) {
			$this->fitFloats( $context );
		}

		/**
		 * After child elements have been reflown, we should the top collapsed margin stack value
		 * replaced by the value of last child bottom collapsed margin;
		 * if no children contained, then this value should be reset to 0.
		 *
		 * Note that invisible and
		 * whitespaces boxes would not affect the collapsed margin value, so we need to
		 * use 'get_first' function instead of just accessing the $content array.
		 *
		 * @see GenericContainerBox::get_first
		 */
		if ( ! is_null( $this->get_first() ) ) {
			$cm = 0;
		} else {
			$cm = $context->get_collapsed_margin();
		};

		/**
		 * Update the bottom  value, collapsing the latter value with
		 * current box bottom margin.
		 *
		 * Note that we need to remove TWO values from the margin stack:
		 * first - the value of collapsed bottom margin of the last child AND
		 * second - the value of collapsed top margin of current element.
		 */
		$margin = $this->get_css_property( CSS_MARGIN );

		if ( $parent ) {
			/**
			 * Terminate parent's line box (it contains the current box only)
			 */
			$parent->close_line( $context );

			$parent->_current_y = $this->collapse_margin_bottom( $parent, $context );
		};
	}

	function show( &$driver ) {
		if ( $this->get_css_property( CSS_FLOAT ) != FLOAT_NONE ||
		     $this->get_css_property( CSS_POSITION ) == POSITION_RELATIVE ) {
			// These boxes will be rendered separately
			return true;
		};

		return parent::show( $driver );
	}

	function show_postponed( &$driver ) {
		return parent::show( $driver );
	}

	/**
	 * Show fixed positioned block box using the specified output driver
	 *
	 * Note that 'show_fixed' is called to box _nested_ to the fixed-positioned boxes too!
	 * Thus, we need to check whether actual 'position' values is 'fixed' for this box
	 * and only in that case attempt to move box
	 *
	 * @param OutputDriver $driver The output device driver object
	 */
	function show_fixed( &$driver ) {
		$position = $this->get_css_property( CSS_POSITION );

		if ( $position == POSITION_FIXED ) {
			/**
			 * Calculate the distance between the top page edge and top box content edge
			 */
			$bottom = $this->get_css_property( CSS_BOTTOM );
			$top    = $this->get_css_property( CSS_TOP );

			if ( ! $top->isAuto() ) {
				if ( $top->isPercentage() ) {
					$vertical_offset = $driver->getPageMaxHeight() / 100 * $top->getPercentage();
				} else {
					$vertical_offset = $top->getPoints();
				};

			} elseif ( ! $bottom->isAuto() ) {
				if ( $bottom->isPercentage() ) {
					$vertical_offset = $driver->getPageMaxHeight() * ( 100 - $bottom->getPercentage() ) / 100 - $this->get_height();
				} else {
					$vertical_offset = $driver->getPageMaxHeight() - $bottom->getPoints() - $this->get_height();
				};

			} else {
				$vertical_offset = 0;
			};

			/**
			 * Calculate the distance between the right page edge and right box content edge
			 */
			$left  = $this->get_css_property( CSS_LEFT );
			$right = $this->get_css_property( CSS_RIGHT );

			if ( ! $left->isAuto() ) {
				if ( $left->isPercentage() ) {
					$horizontal_offset = $driver->getPageWidth() / 100 * $left->getPercentage();
				} else {
					$horizontal_offset = $left->getPoints();
				};

			} elseif ( ! $right->isAuto() ) {
				if ( $right->isPercentage() ) {
					$horizontal_offset = $driver->getPageWidth() * ( 100 - $right->getPercentage() ) / 100 - $this->get_width();
				} else {
					$horizontal_offset = $driver->getPageWidth() - $right->getPoints() - $this->get_width();
				};

			} else {
				$horizontal_offset = 0;
			};

			/**
			 * Offset current box to the required position on the current page (note that
			 * fixed-positioned element are placed relatively to the viewport - page in our case)
			 */
			$this->moveto( $driver->getPageLeft() + $horizontal_offset,
				$driver->getPageTop() - $vertical_offset );
		};

		/**
		 * After box have benn properly positioned, render it as usual.
		 */
		return GenericContainerBox::show_fixed( $driver );
	}

	function isBlockLevel() {
		return true;
	}
}


class BoxPage extends GenericContainerBox {
	function BoxPageMargin() {
		$this->GenericContainerBox();
	}

	function &create( &$pipeline, $rules ) {
		$box =& new BoxPage();

		$state =& $pipeline->get_current_css_state();
		$state->pushDefaultState();
		$rules->apply( $state );
		$box->readCSS( $state );
		$state->popState();

		return $box;
	}

	function get_bottom_background() {
		return $this->get_bottom_margin();
	}

	function get_left_background() {
		return $this->get_left_margin();
	}

	function get_right_background() {
		return $this->get_right_margin();
	}

	function get_top_background() {
		return $this->get_top_margin();
	}

	function reflow( &$media ) {
		$this->put_left( mm2pt( $media->margins['left'] ) );
		$this->put_top( mm2pt( $media->height() - $media->margins['top'] ) );
		$this->put_width( mm2pt( $media->real_width() ) );
		$this->put_height( mm2pt( $media->real_height() ) );
	}

	function show( &$driver ) {
		$this->offset( 0, $driver->offset );
		parent::show( $driver );
	}
}


/**
 * @abstract
 */
class BoxPageMargin extends GenericContainerBox {
	/**
	 * @param $at_rule CSSAtRuleMarginBox At-rule object describing margin box to be created
	 *
	 * @return Object Object of concrete BoxPageMargin descendant type
	 */
	function &create( &$pipeline, $at_rule ) {
		switch ( $at_rule->getSelector() ) {
			case CSS_MARGIN_BOX_SELECTOR_TOP:
				$box =& new BoxPageMarginTop( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_TOP_LEFT_CORNER:
				$box =& new BoxPageMarginTopLeftCorner( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_TOP_LEFT:
				$box =& new BoxPageMarginTopLeft( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_TOP_CENTER:
				$box =& new BoxPageMarginTopCenter( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT:
				$box =& new BoxPageMarginTopRight( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT_CORNER:
				$box =& new BoxPageMarginTopRightCorner( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM:
				$box =& new BoxPageMarginBottom( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT_CORNER:
				$box =& new BoxPageMarginBottomLeftCorner( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT:
				$box =& new BoxPageMarginBottomLeft( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM_CENTER:
				$box =& new BoxPageMarginBottomCenter( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT:
				$box =& new BoxPageMarginBottomRight( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT_CORNER:
				$box =& new BoxPageMarginBottomRightCorner( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_LEFT_TOP:
				$box =& new BoxPageMarginLeftTop( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE:
				$box =& new BoxPageMarginLeftMiddle( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM:
				$box =& new BoxPageMarginLeftBottom( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_RIGHT_TOP:
				$box =& new BoxPageMarginRightTop( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_RIGHT_MIDDLE:
				$box =& new BoxPageMarginRightMiddle( $pipeline, $at_rule );
				break;
			case CSS_MARGIN_BOX_SELECTOR_RIGHT_BOTTOM:
				$box =& new BoxPageMarginRightBottom( $pipeline, $at_rule );
				break;
			default:
				trigger_error( "Unknown selector type", E_USER_ERROR );
		};

		return $box;
	}

	function BoxPageMargin( &$pipeline, $at_rule ) {
		$state =& $pipeline->get_current_css_state();
		$state->pushDefaultState();

		$root = null;
		$at_rule->css->apply( $root, $state, $pipeline );

		$this->GenericContainerBox();
		$this->readCSS( $state );

		$state->pushDefaultstate();

		/**
		 * Check whether 'content' or '-html2ps-html-content' properties had been defined
		 * (if both properties are defined, -html2ps-html-content takes precedence)
		 */
		$raw_html_content =& $at_rule->get_css_property( CSS_HTML2PS_HTML_CONTENT );
		$html_content     = $raw_html_content->render( $pipeline->get_counters() );

		if ( $html_content !== '' ) {
			// We should wrap html_content in DIV tag,
			// as we treat only the very first box of the resulting DOM tree as margin box content

			$html_content = html2xhtml( "<div>" . $html_content . "</div>" );
			$tree         = TreeBuilder::build( $html_content );
			$tree_root    = traverse_dom_tree_pdf( $tree );
			$body_box     =& create_pdf_box( $tree_root, $pipeline );
			$box          =& $body_box->content[0];
		} else {
			$raw_content =& $at_rule->get_css_property( CSS_CONTENT );
			$content     = $raw_content->render( $pipeline->get_counters() );

			$box =& InlineBox::create_from_text( $content,
				WHITESPACE_PRE_LINE,
				$pipeline );
		}
		$this->add_child( $box );

		$state->popState();
		$state->popState();
	}

	function get_cell_baseline() {
		return 0;
	}

	function reflow( &$driver, &$media, $boxes ) {
		$context = new FlowContext;
		$this->_position( $media, $boxes, $context );

		$this->setCSSProperty( CSS_WIDTH, new WCConstant( $this->get_width() ) );
		$this->put_height_constraint( new HCConstraint( array( $this->height, false ),
			null,
			null ) );

		$this->reflow_content( $context );

		/**
		 * Apply vertical-align (behave like table cell)
		 */
		$va = CSSVerticalAlign::value2pdf( $this->get_css_property( CSS_VERTICAL_ALIGN ) );

		$va->apply_cell( $this, $this->get_full_height(), 0 );
	}

	function show( &$driver ) {
		$this->offset( 0, $driver->offset );
		$this->show_fixed( $driver );
	}

	function _calc_sizes( $full_width, $left, $center, $right ) {
		$context = new FlowContext;

		$left_width   = $left->get_max_width( $context );
		$center_width = $center->get_max_width( $context );
		$right_width  = $right->get_max_width( $context );

		$calculated_left_width   = 0;
		$calculated_center_width = 0;
		$calculated_right_width  = 0;

		if ( $center_width > 0 ) {
			$calculated_center_width = $full_width * $center_width / ( $center_width + 2 * max( $left_width, $right_width ) );
			$calculated_left_width   = ( $full_width - $calculated_center_width ) / 2;
			$calculated_right_width  = $calculated_left_width;
		} elseif ( $left_width == 0 && $right_width == 0 ) {
			$calculated_center_width = 0;
			$calculated_left_width   = 0;
			$calculated_right_width  = 0;
		} elseif ( $left_width == 0 ) {
			$calculated_center_width = 0;
			$calculated_left_width   = 0;
			$calculated_right_width  = $full_width;
		} elseif ( $right_width == 0 ) {
			$calculated_center_width = 0;
			$calculated_left_width   = $full_width;
			$calculated_right_width  = 0;
		} else {
			$calculated_center_width = 0;
			$calculated_left_width   = $full_width * $left_width / ( $left_width + $right_width );
			$calculated_right_width  = $full_width - $calculated_left_width;
		};

		return array(
			$calculated_left_width,
			$calculated_center_width,
			$calculated_right_width
		);
	}
}

class BoxPageMarginTop extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( mm2pt( $media->width() ) );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginTopLeftCorner extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( mm2pt( $media->margins['left'] ) );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginTopLeft extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( $left );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginTopCenter extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) + $left );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( $center );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginTopRight extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) + $left + $center );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( $right );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginTopRightCorner extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + mm2pt( $media->width() - $media->margins['right'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() ) );

		$this->put_full_width( mm2pt( $media->margins['right'] ) );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottomLeftCorner extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( mm2pt( $media->margins['left'] ) );
		$this->put_full_height( mm2pt( $media->margins['bottom'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottomLeft extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( $left );
		$this->put_full_height( mm2pt( $media->margins['bottom'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottomCenter extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) + $left );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( $center );
		$this->put_full_height( mm2pt( $media->margins['bottom'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottomRight extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_width() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_CENTER ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->margins['left'] ) + $left + $center );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( $right );
		$this->put_full_height( mm2pt( $media->margins['bottom'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottomRightCorner extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + mm2pt( $media->width() - $media->margins['right'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( mm2pt( $media->margins['right'] ) );
		$this->put_full_height( mm2pt( $media->margins['top'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginBottom extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->margins['bottom'] ) );

		$this->put_full_width( mm2pt( $media->width() ) );
		$this->put_full_height( mm2pt( $media->margins['bottom'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginLeftTop extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM ] );

		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) );

		$this->put_full_height( $left );
		$this->put_full_width( mm2pt( $media->margins['left'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginLeftMiddle extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM ] );
		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) - $left );

		$this->put_full_height( $center );
		$this->put_full_width( mm2pt( $media->margins['left'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginLeftBottom extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM ] );

		$this->put_left( $this->get_extra_left() + 0 );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) - $left - $center );

		$this->put_full_height( $right );
		$this->put_full_width( mm2pt( $media->margins['left'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginRightTop extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_RIGHT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_RIGHT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_RIGHT_BOTTOM ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->width() - $media->margins['right'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) );

		$this->put_full_height( $left );
		$this->put_full_width( mm2pt( $media->margins['right'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginRightMiddle extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->width() - $media->margins['right'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) - $left );

		$this->put_full_height( $center );
		$this->put_full_width( mm2pt( $media->margins['right'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}

class BoxPageMarginRightBottom extends BoxPageMargin {
	function _position( $media, $boxes, $context ) {
		list( $left, $center, $right ) = $this->_calc_sizes( mm2pt( $media->real_height() ),
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_TOP ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE ],
			$boxes[ CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM ] );

		$this->put_left( $this->get_extra_left() + mm2pt( $media->width() - $media->margins['right'] ) );
		$this->put_top( - $this->get_extra_top() + mm2pt( $media->height() - $media->margins['top'] ) - $left - $center );

		$this->put_full_height( $right );
		$this->put_full_width( mm2pt( $media->margins['right'] ) );

		$this->_current_x = $this->get_left();
		$this->_current_y = $this->get_top();
	}
}


class BodyBox extends BlockBox {
	function BodyBox() {
		$this->BlockBox();
	}

	function &create( &$root, &$pipeline ) {
		$box = new BodyBox();
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function get_bottom_background() {
		return $this->get_bottom_margin();
	}

	function get_left_background() {
		return $this->get_left_margin();
	}

	function get_right_background() {
		return $this->get_right_margin();
	}

	function get_top_background() {
		return $this->get_top_margin();
	}

	function reflow( &$parent, &$context ) {
		parent::reflow( $parent, $context );

		// Extend the body height to fit all contained floats
		$float_bottom = $context->float_bottom();
		if ( ! is_null( $float_bottom ) ) {
			$this->extend_height( $float_bottom );
		};
	}
}


// $Header: /cvsroot/html2ps/box.block.inline.php,v 1.21 2007/04/07 11:16:33 Konstantin Exp $

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * Describes document elements with 'display: inline-block'.
 *
 * @link http://www.w3.org/TR/CSS21/visuren.html#value-def-inline-block CSS 2.1 description of 'display: inline-block'
 */
class InlineBlockBox extends GenericContainerBox {
	/**
	 * Create new 'inline-block' element; add content from the parsed HTML tree automatically.
	 *
	 * @see InlineBlockBox::InlineBlockBox()
	 * @see GenericContainerBox::create_content()
	 */
	function &create( &$root, &$pipeline ) {
		$box = new InlineBlockBox();
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function InlineBlockBox() {
		$this->GenericContainerBox();
	}

	/**
	 * Layout current inline-block element
	 *
	 * @param GenericContainerBox $parent The document element which should be treated as the parent of current element
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 * @see BlockBox::reflow
	 *
	 * @todo this 'reflow' skeleton is common for all element types; thus, we probably should move the generic 'reflow'
	 * definition to the GenericFormattedBox class, leaving only box-specific 'reflow_static' definitions in specific classes.
	 *
	 * @todo make relative positioning more CSS 2.1 compliant; currently, 'bottom' and 'right' CSS properties are ignored.
	 *
	 * @todo check whether percentage values should be really ignored during relative positioning
	 */
	function reflow( &$parent, &$context ) {
		/**
		 * Note that we may not worry about 'position: absolute' and 'position: fixed',
		 * as, according to CSS 2.1 paragraph 9.7, these values of 'position'
		 * will cause 'display' value to change to either 'block' or 'table'. Thus,
		 * 'inline-block' boxes will never have 'position' value other than 'static' or 'relative'
		 *
		 * @link http://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo CSS 2.1: Relationships between 'display', 'position', and 'float'
		 */

		switch ( $this->get_css_property( CSS_POSITION ) ) {
			case POSITION_STATIC:
				return $this->reflow_static( $parent, $context );

			case POSITION_RELATIVE:
				/**
				 * CSS 2.1:
				 * Once a box has been laid out according to the normal flow or floated, it may be shifted relative
				 * to this position. This is called relative positioning. Offsetting a box (B1) in this way has no
				 * effect on the box (B2) that follows: B2 is given a position as if B1 were not offset and B2 is
				 * not re-positioned after B1's offset is applied. This implies that relative positioning may cause boxes
				 * to overlap. However, if relative positioning causes an 'overflow:auto' box to have overflow, the UA must
				 * allow the user to access this content, which, through the creation of scrollbars, may affect layout.
				 *
				 * @link http://www.w3.org/TR/CSS21/visuren.html#x28 CSS 2.1 Relative positioning
				 */

				$this->reflow_static( $parent, $context );
				$this->offsetRelative();

				return;
		}
	}

	/**
	 * Layout current 'inline-block' element assument it has 'position: static'
	 *
	 * @param GenericContainerBox $parent The document element which should
	 * be treated as the parent of current element
	 *
	 * @param FlowContext         $context The flow context containing the additional layout data
	 *
	 * @see FlowContext
	 * @see GenericContainerBox
	 *
	 * @todo re-check this layout routine; it seems that 'inline-block' boxes have
	 * their width calculated incorrectly
	 */
	function reflow_static( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		/**
		 * Calculate margin values if they have been set as a percentage
		 */
		$this->_calc_percentage_margins( $parent );
		$this->_calc_percentage_padding( $parent );

		/**
		 * Calculate width value if it had been set as a percentage
		 */
		$this->_calc_percentage_width( $parent, $context );

		/**
		 * Calculate 'auto' values of width and margins
		 */
		$this->_calc_auto_width_margins( $parent );

		/**
		 * add current box to the parent's line-box (alone)
		 */
		$parent->append_line( $this );

		/**
		 * Calculate position of the upper-left corner of the current box
		 */
		$this->guess_corner( $parent );

		/**
		 * By default, child block box will fill all available parent width;
		 * note that actual content width will be smaller because of non-zero padding, border and margins
		 */
		$this->put_full_width( $parent->get_width() );

		/**
		 * Layout element's children
		 */
		$this->reflow_content( $context );

		/**
		 * Calculate element's baseline, as it should be aligned inside the
		 * parent's line box vertically
		 */
		$font                   = $this->get_css_property( CSS_FONT );
		$this->default_baseline = $this->get_height() + $font->size->getPoints();

		/**
		 * Extend parent's height to fit current box
		 */
		$parent->extend_height( $this->get_bottom_margin() );

		/**
		 * Offset current x coordinate of parent box
		 */
		$parent->_current_x = $this->get_right_margin();
	}
}

// $Header: /cvsroot/html2ps/box.button.php,v 1.29 2007/01/24 18:55:43 Konstantin Exp $
/**
 * @package HTML2PS
 * @subpackage Document
 *
 * This file contains the class desribing layout and behavior of 'input type="button"'
 * elements
 */

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * The ButtonBox class desribes the HTML buttons layout. (Note that
 * button elements have 'display' CSS property set to HTML2PS-specific
 * '-button' value )
 *
 * @link http://www.w3.org/TR/html4/interact/forms.html#h-17.4 HTML 4.01 The INPUT element
 */
class ButtonBox extends InlineControlBox {
	function ButtonBox() {
		$this->InlineControlBox();
	}

	function get_max_width( &$context, $limit = 10E6 ) {
		return
			GenericContainerBox::get_max_width( $context, $limit );
	}

	/**
	 * Create a new button element from the DOM tree element
	 *
	 * @param DOMElement $root pointer to the DOM tree element corresponding to the button.
	 *
	 * @return ButtonBox new button element
	 */
	function &create( &$root, &$pipeline ) {
		/**
		 * Button text is defined by its 'value' attrubute;
		 * if this attribute is not specified, we should provide some
		 * appropriate defaults depending on the exact button type:
		 * reset, submit or generic button.
		 *
		 * Default button text values are specified in config file config.inc.php.
		 *
		 * @see config.inc.php
		 * @see DEFAULT_SUBMIT_TEXT
		 * @see DEFAULT_RESET_TEXT
		 * @see DEFAULT_BUTTON_TEXT
		 */
		if ( $root->has_attribute( "value" ) ) {
			$text = $root->get_attribute( "value" );
		} else {
			$text = DEFAULT_BUTTON_TEXT;
		};

		$box =& new ButtonBox();
		$box->readCSS( $pipeline->get_current_css_state() );

		/**
		 * If button width is not constrained, then we'll add some space around the button text
		 */
		$text = " " . $text . " ";

		$box->_setup( $text, $pipeline );

		return $box;
	}

	function _setup( $text, &$pipeline ) {
		$this->setup_content( $text, $pipeline );

		/**
		 * Button height includes vertical padding (e.g. the following two buttons
		 * <input type="button" value="test" style="padding: 10px; height: 50px;"/>
		 * <input type="button" value="test" style="padding: 0px; height: 30px;"/>
		 * are render by browsers with the same height!), so we'll need to adjust the
		 * height constraint, subtracting the vertical padding value from the constraint
		 * height value.
		 */
		$hc = $this->get_height_constraint();
		if ( ! is_null( $hc->constant ) ) {
			$hc->constant[0] -= $this->get_padding_top() + $this->get_padding_bottom();
		};
		$this->put_height_constraint( $hc );
	}

	/**
	 * Render the form field corresponding to this button
	 * (Will be overridden by subclasses; they may render more specific button types)
	 *
	 * @param OutputDriver $driver The output driver object
	 */
	function _render_field( &$driver ) {
		$driver->field_pushbutton( $this->get_left_padding(),
			$this->get_top_padding(),
			$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
			$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom() );
	}

	/**
	 * Render the button using the specified output driver
	 *
	 * @param OutputDriver $driver The output driver object
	 *
	 * @return boolean flag indicating an error (null value) or success (true)
	 */
	function show( &$driver ) {
		/**
		 * Set the baseline of a button box so that the button text will be aligned with
		 * the line box baseline
		 */
		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();


		/**
		 * Render the interactive button (if requested and possible)
		 */
		if ( $GLOBALS['g_config']['renderforms'] ) {
			$status = GenericContainerBox::show( $driver );
			$this->_render_field( $driver );
		} else {
			$status = GenericContainerBox::show( $driver );
		};

		return $status;
	}
}


/**
 * Handles INPUT type="submit" boxes generation.
 */
class ButtonSubmitBox extends ButtonBox {
	/**
	 * @var String URL to post the form to; may be null if this is not a 'submit' button
	 * @access private
	 */
	var $_action_url;

	/**
	 * Note: required for interative forms only
	 *
	 * @var String textual name of the input field
	 * @access private
	 */
	var $_field_name;

	/**
	 * Note: required for interactive forms only
	 *
	 * @var String button name to display
	 * @access private
	 */
	var $_value;

	/**
	 * Constructs new (possibly interactive) button box
	 *
	 * @param String $text text to display
	 * @param String $field field name (interactive forms)
	 * @param String $value field value (interactive forms)
	 */
	function ButtonSubmitBox( $field, $value, $action ) {
		$this->ButtonBox();
		$this->_action_url = $action;
		$this->_field_name = $field;
		$this->_value      = $value;
	}

	/**
	 * Create input box using DOM tree data
	 *
	 * @param Object   $root DOM tree node corresponding to the box being created
	 * @param Pipeline $pipeline reference to current pipeline object (unused)
	 *
	 * @return input box
	 */
	function &create( &$root, &$pipeline ) {
		/**
		 * If no "value" attribute is specified, display the default button text.
		 * Note the difference between displayed text and actual field value!
		 */
		if ( $root->has_attribute( "value" ) ) {
			$text = $root->get_attribute( "value" );
		} else {
			$text = DEFAULT_SUBMIT_TEXT;
		};

		$field = $root->get_attribute( 'name' );
		$value = $root->get_attribute( 'value' );

		$css_state =& $pipeline->get_current_css_state();
		$box       =& new ButtonSubmitBox( $field, $value, $css_state->get_property( CSS_HTML2PS_FORM_ACTION ) );
		$box->readCSS( $css_state );
		$box->_setup( $text, $pipeline );

		return $box;
	}

	/**
	 * Render interactive field using the driver-specific capabilities;
	 * button is rendered as a rectangle defined by margin and padding areas (note that unlike most other boxes,
	 * borders are _outside_ the box, so we may treat
	 *
	 * @param OutputDriver $driver reference to current output driver object
	 */
	function _render_field( &$driver ) {
		$driver->field_pushbuttonsubmit( $this->get_left_padding() - $this->get_margin_left(),
			$this->get_top_padding() + $this->get_margin_top(),
			$this->get_width() + $this->get_padding_left() + $this->get_padding_right() + $this->get_margin_left() + $this->get_margin_right(),
			$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom() + $this->get_margin_top() + $this->get_margin_bottom(),
			$this->_field_name,
			$this->_value,
			$this->_action_url );
	}
}


class ButtonResetBox extends ButtonBox {
	function ButtonResetBox( $text ) {
		$this->ButtonBox( $text );
	}

	function &create( &$root, &$pipeline ) {
		if ( $root->has_attribute( "value" ) ) {
			$text = $root->get_attribute( "value" );
		} else {
			$text = DEFAULT_RESET_TEXT;
		};

		$box =& new ButtonResetBox( $text );
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array( CSS_HTML2PS_FORM_ACTION ) );
	}

	function _render_field( &$driver ) {
		$driver->field_pushbuttonreset( $this->get_left_padding(),
			$this->get_top_padding(),
			$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
			$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom() );
	}
}


// $Header: /cvsroot/html2ps/box.checkbutton.php,v 1.21 2007/05/07 12:15:53 Konstantin Exp $

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * This file contains the class describing layot and behavior of <input type="checkbox">
 * elements
 */

/**
 * @package HTML2PS
 * @subpackage Document
 *
 * The CheckBox class desribes the layour of HTML checkboxes (they have HTML2PS-specific
 * '-checkbox' value of 'display' property)
 *
 * Checkboxes have fixed size, which can be configured using CHECKBOX_SIZE constant
 * in config.inc.php file. If "checked" attribute is present (whatever its value is),
 * a small cross is drawn inside the checkbox.
 *
 * @see CHECKBOX_SIZE
 *
 * @todo add "disabled" state
 */
class CheckBox extends GenericFormattedBox {
	/**
	 * @var Boolean Flag indicating whether the check mark should be drawn
	 * @access private
	 */
	var $_checked;

	/**
	 * @var String name of the corresponding form field
	 * @access private
	 */
	var $_name;

	/**
	 * Notes: leading and trailing spaces are removed; if value is not specified,
	 * checkbox is not rendered as ineractive control
	 *
	 * @var String value to be posted from ineractive form for this checkbox
	 * @access private
	 */
	var $_value;

	/**
	 * Create a new checkbutton element using DOM tree element to initialize
	 * it.
	 *
	 * @param DOMElement $root the DOM 'input' element
	 *
	 * @return CheckBox new checkbox element
	 *
	 * @see CheckBox::CheckBox()
	 */
	function &create( &$root, &$pipeline ) {
		$value = $root->get_attribute( 'value' );

		if ( trim( $value ) == "" ) {
			error_log( "Checkbox with empty 'value' attribute" );
			$value = sprintf( "___Value%s", md5( time() . rand() ) );
		};

		$box =& new CheckBox( $root->has_attribute( 'checked' ),
			$root->get_attribute( 'name' ),
			$value );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->setup_dimensions();

		return $box;
	}

	/**
	 * Create a new checkbox element with the given state
	 *
	 * @param $checked flag inidicating if this box should be checked
	 *
	 * @see CheckBox::create()
	 */
	function CheckBox( $checked, $name, $value ) {
		$this->GenericFormattedBox();

		$this->_checked = $checked;
		$this->_name    = trim( $name );
		$this->_value   = trim( $value );
	}

	/**
	 * Returns the width of the checkbox; not that max/min width does not
	 * make sense for the checkbuttons, as their width is always constant.
	 *
	 * @param FlowContext Context object describing current flow parameters (unused)
	 *
	 * @return int width of the checkbox
	 *
	 * @see CheckBox::get_max_width
	 */
	function get_min_width( &$context ) {
		return $this->width;
	}

	/**
	 * Returns the width of the checkbox; not that max/min width does not
	 * make sense for the checkbuttons, as their width is always constant.
	 *
	 * @param FlowContext Context object describing current flow parameters (unused)
	 *
	 * @return int width of the checkbox
	 *
	 * @see CheckBox::get_min_width
	 */
	function get_max_width( &$context ) {
		return $this->width;
	}

	/**
	 * Layout current checkbox element. Note that most CSS properties do not apply to the
	 * checkboxes; i.e. margin/padding values are ignored, checkboxes always aligned to
	 * to bottom of current line, etc.
	 *
	 * @param GenericContainerBox $parent
	 * @param FlowContext         $context Context object describing current flow parameters
	 *
	 * @return Boolean flag indicating the error/success state; 'null' value in case of critical error
	 */
	function reflow( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		/**
		 * Check box size is constant (defined in config.inc.php) and is never affected
		 * neither by CSS nor HTML. Call setup_dimensions once more to restore possible
		 * changes size
		 *
		 * @see CHECKBOX_SIZE
		 */
		$this->setup_dimensions();

		// set default baseline
		$this->baseline = $this->default_baseline;

		//     // Vertical-align
		//     $this->_apply_vertical_align($parent);

		/**
		 * append to parent line box
		 */
		$parent->append_line( $this );

		/**
		 * Determine coordinates of upper-left margin corner
		 */
		$this->guess_corner( $parent );

		/**
		 * Offset parent current X coordinate
		 */
		$parent->_current_x += $this->get_full_width();

		/**
		 * Extend parents height to fit the checkbox
		 */
		$parent->extend_height( $this->get_bottom_margin() );
	}

	/**
	 * Render the checkbox using the specified output driver
	 *
	 * @param OutputDriver $driver The output device driver object
	 */
	function show( &$driver ) {
		/**
		 * Get the coordinates of the check mark
		 */
		$x = ( $this->get_left() + $this->get_right() ) / 2;
		$y = ( $this->get_top() + $this->get_bottom() ) / 2;

		/**
		 * Calculate checkmark size; it looks nice when it takes
		 * 1/3 of the box size
		 */
		$size = $this->get_width() / 3;

		/**
		 * Draw the box
		 */
		$driver->setrgbcolor( 0, 0, 0 );
		$driver->setlinewidth( 0.25 );
		$driver->moveto( $x - $size, $y + $size );
		$driver->lineto( $x + $size, $y + $size );
		$driver->lineto( $x + $size, $y - $size );
		$driver->lineto( $x - $size, $y - $size );
		$driver->closepath();
		$driver->stroke();

		/**
		 * Render the interactive button (if requested and possible)
		 * Also, field should be rendered only if name is not empty
		 */
		global $g_config;
		if ( $g_config['renderforms'] && $this->_name != "" && $this->_value != "" ) {
			$driver->field_checkbox( $x - $size,
				$y + $size,
				2 * $size,
				2 * $size,
				$this->_name,
				$this->_value,
				$this->_checked );
		} else {
			/**
			 * Draw check mark if needed
			 */
			if ( $this->_checked ) {
				$check_size = $this->get_width() / 6;

				$driver->moveto( $x - $check_size, $y + $check_size );
				$driver->lineto( $x + $check_size, $y - $check_size );
				$driver->stroke();

				$driver->moveto( $x + $check_size, $y + $check_size );
				$driver->lineto( $x - $check_size, $y - $check_size );
				$driver->stroke();
			}
		};

		return true;
	}

	function setup_dimensions() {
		$this->default_baseline = units2pt( CHECKBOX_SIZE );
		$this->height           = units2pt( CHECKBOX_SIZE );
		$this->width            = units2pt( CHECKBOX_SIZE );
	}
}


class FormBox extends BlockBox {
	/**
	 * @var String form name; it will be used as a prefix for field names when submitting forms
	 * @access private
	 */
	var $_name;

	function show( &$driver ) {
		global $g_config;
		if ( $g_config['renderforms'] ) {
			$driver->new_form( $this->_name );
		};

		return parent::show( $driver );
	}

	function &create( &$root, &$pipeline ) {
		if ( $root->has_attribute( 'name' ) ) {
			$name = $root->get_attribute( 'name' );
		} elseif ( $root->has_attribute( 'id' ) ) {
			$name = $root->get_attribute( 'id' );
		} else {
			$name = "";
		};

		$box = new FormBox( $name );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function FormBox( $name ) {
		$this->BlockBox();

		$this->_name = $name;
	}
}


// $Header: /cvsroot/html2ps/box.frame.php,v 1.24 2007/02/18 09:55:10 Konstantin Exp $

class FrameBox extends GenericContainerBox {
	function &create( &$root, &$pipeline ) {
		$box =& new FrameBox( $root, $pipeline );
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	function reflow( &$parent, &$context ) {
		// If frame contains no boxes (for example, the src link is broken)
		// we just return - no further processing will be done
		if ( count( $this->content ) == 0 ) {
			return;
		};

		// First box contained in a frame should always fill all its height
		$this->content[0]->put_full_height( $this->get_height() );

		$hc = new HCConstraint( array( $this->get_height(), false ),
			array( $this->get_height(), false ),
			array( $this->get_height(), false ) );
		$this->content[0]->put_height_constraint( $hc );

		$context->push_collapsed_margin( 0 );
		$context->push_container_uid( $this->uid );

		$this->reflow_content( $context );

		$context->pop_collapsed_margin();
		$context->pop_container_uid();
	}

	/**
	 * Reflow absolutely positioned block box. Note that according to CSS 2.1
	 * the only types of boxes which could be absolutely positioned are
	 * 'block' and 'table'
	 *
	 * @param FlowContext $context A flow context object containing the additional layout data.
	 *
	 * @link http://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo CSS 2.1: Relationships between 'display', 'position', and 'float'
	 */
	function reflow_absolute( &$context ) {
		GenericFormattedBox::reflow( $this->parent, $context );

		$position_strategy =& new StrategyPositionAbsolute();
		$position_strategy->apply( $this );

		/**
		 * As sometimes left/right values may not be set, we need to use the "fit" width here.
		 * If box have a width constraint, 'get_max_width' will return constrained value;
		 * othersise, an intrictic width will be returned.
		 *
		 * Note that get_max_width returns width _including_ external space line margins, borders and padding;
		 * as we're setting the "internal" - content width, we must subtract "extra" space width from the
		 * value received
		 *
		 * @see GenericContainerBox::get_max_width()
		 */

		$this->put_width( $this->get_max_width( $context ) - $this->_get_hor_extra() );

		/**
		 * Update the width, as it should be calculated based upon containing block width, not real parent.
		 * After this we should remove width constraints or we may encounter problem
		 * in future when we'll try to call get_..._width functions for this box
		 *
		 * @todo Update the family of get_..._width function so that they would apply constraint
		 * using the containing block width, not "real" parent width
		 */
		$wc = $this->get_css_property( CSS_WIDTH );

		$containing_block =& $this->_get_containing_block();
		$this->put_width( $wc->apply( $this->get_width(),
			$containing_block['right'] - $containing_block['left'] ) );
		$this->setCSSProperty( CSS_WIDTH, new WCNone() );

		/**
		 * Layout element's children
		 */
		$this->reflow_content( $context );

		/**
		 * As absolute-positioned box generated new flow contexy, extend the height to fit all floats
		 */
		$this->fitFloats( $context );

		/**
		 * If element have been positioned using 'right' or 'bottom' property,
		 * we need to offset it, as we assumed it had zero width and height at
		 * the moment we placed it
		 */
		$right = $this->get_css_property( CSS_RIGHT );
		$left  = $this->get_css_property( CSS_LEFT );
		if ( $left->isAuto() && ! $right->isAuto() ) {
			$this->offset( - $this->get_width(), 0 );
		};

		$bottom = $this->get_css_property( CSS_BOTTOM );
		$top    = $this->get_css_property( CSS_TOP );
		if ( $top->isAuto() && ! $bottom->isAuto() ) {
			$this->offset( 0, $this->get_height() );
		};
	}

	function FrameBox( &$root, &$pipeline ) {
		$css_state =& $pipeline->get_current_css_state();

		// Inherit 'border' CSS value from parent (FRAMESET tag), if current FRAME
		// has no FRAMEBORDER attribute, and FRAMESET has one
		$parent = $root->parent();
		if ( ! $root->has_attribute( 'frameborder' ) &&
		     $parent->has_attribute( 'frameborder' ) ) {
			$parent_border = $css_state->get_propertyOnLevel( CSS_BORDER, CSS_PROPERTY_LEVEL_PARENT );
			$css_state->set_property( CSS_BORDER, $parent_border->copy() );
		}

		$this->GenericContainerBox( $root );

		// If NO src attribute specified, just return.
		if ( ! $root->has_attribute( 'src' ) ) {
			return;
		};

		// Determine the fullly qualified URL of the frame content
		$src  = $root->get_attribute( 'src' );
		$url  = $pipeline->guess_url( $src );
		$data = $pipeline->fetch( $url );

		/**
		 * If framed page could not be fetched return immediately
		 */
		if ( is_null( $data ) ) {
			return;
		};

		/**
		 * Render only iframes containing HTML only
		 *
		 * Note that content-type header may contain additional information after the ';' sign
		 */
		$content_type       = $data->get_additional_data( 'Content-Type' );
		$content_type_array = explode( ';', $content_type );
		if ( $content_type_array[0] != "text/html" ) {
			return;
		};

		$html = $data->get_content();

		// Remove control symbols if any
		$html      = preg_replace( '/[\x00-\x07]/', "", $html );
		$converter = Converter::create();
		$html      = $converter->to_utf8( $html, $data->detect_encoding() );
		$html      = html2xhtml( $html );
		$tree      = TreeBuilder::build( $html );

		// Save current stylesheet, as each frame may load its own stylesheets
		//
		$pipeline->push_css();
		$css =& $pipeline->get_current_css();
		$css->scan_styles( $tree, $pipeline );

		$frame_root = traverse_dom_tree_pdf( $tree );
		$box_child  =& create_pdf_box( $frame_root, $pipeline );
		$this->add_child( $box_child );

		// Restore old stylesheet
		//
		$pipeline->pop_css();

		$pipeline->pop_base_url();
	}

	/**
	 * Note that if both top and bottom are 'auto', box will use vertical coordinate
	 * calculated using guess_corder in 'reflow' method which could be used if this
	 * box had 'position: static'
	 */
	function _positionAbsoluteVertically( $containing_block ) {
		$bottom = $this->get_css_property( CSS_BOTTOM );
		$top    = $this->get_css_property( CSS_TOP );

		if ( ! $top->isAuto() ) {
			if ( $top->isPercentage() ) {
				$top_value = ( $containing_block['top'] - $containing_block['bottom'] ) / 100 * $top->getPercentage();
			} else {
				$top_value = $top->getPoints();
			};
			$this->put_top( $containing_block['top'] - $top_value - $this->get_extra_top() );
		} elseif ( ! $bottom->isAuto() ) {
			if ( $bottom->isPercentage() ) {
				$bottom_value = ( $containing_block['top'] - $containing_block['bottom'] ) / 100 * $bottom->getPercentage();
			} else {
				$bottom_value = $bottom->getPoints();
			};
			$this->put_top( $containing_block['bottom'] + $bottom_value + $this->get_extra_bottom() );
		};
	}

	/**
	 * Note that  if both  'left' and 'right'  are 'auto', box  will use
	 * horizontal coordinate  calculated using guess_corder  in 'reflow'
	 * method which could be used if this box had 'position: static'
	 */
	function _positionAbsoluteHorizontally( $containing_block ) {
		$left  = $this->get_css_property( CSS_LEFT );
		$right = $this->get_css_property( CSS_RIGHT );

		if ( ! $left->isAuto() ) {
			if ( $left->isPercentage() ) {
				$left_value = ( $containing_block['right'] - $containing_block['left'] ) / 100 * $left->getPercentage();
			} else {
				$left_value = $left->getPoints();
			};
			$this->put_left( $containing_block['left'] + $left_value + $this->get_extra_left() );
		} elseif ( ! $right->isAuto() ) {
			if ( $right->isPercentage() ) {
				$right_value = ( $containing_block['right'] - $containing_block['left'] ) / 100 * $right->getPercentage();
			} else {
				$right_value = $right->getPoints();
			};
			$this->put_left( $containing_block['right'] - $right_value - $this->get_extra_right() );
		};
	}
}

class FramesetBox extends GenericContainerBox {
	var $rows;
	var $cols;

	function &create( &$root, &$pipeline ) {
		$box =& new FramesetBox( $root, $pipeline );
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	function FramesetBox( &$root, $pipeline ) {
		$this->GenericContainerBox( $root );
		$this->create_content( $root, $pipeline );

		// Now determine the frame layout inside the frameset
		$this->rows = $root->has_attribute( 'rows' ) ? $root->get_attribute( 'rows' ) : "100%";
		$this->cols = $root->has_attribute( 'cols' ) ? $root->get_attribute( 'cols' ) : "100%";
	}

	function reflow( &$parent, &$context ) {
		$viewport =& $context->get_viewport();

		// Frameset always fill all available space in viewport
		$this->put_left( $viewport->get_left() + $this->get_extra_left() );
		$this->put_top( $viewport->get_top() - $this->get_extra_top() );

		$this->put_full_width( $viewport->get_width() );
		$this->setCSSProperty( CSS_WIDTH, new WCConstant( $viewport->get_width() ) );

		$this->put_full_height( $viewport->get_height() );
		$this->put_height_constraint( new WCConstant( $viewport->get_height() ) );

		// Parse layout-control values
		$rows = guess_lengths( $this->rows, $this->get_height() );
		$cols = guess_lengths( $this->cols, $this->get_width() );

		// Now reflow all frames in frameset
		$cur_col = 0;
		$cur_row = 0;
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			// Had we run out of cols/rows?
			if ( $cur_row >= count( $rows ) ) {
				// In valid HTML we never should get here, but someone can provide less frame cells
				// than frames. Extra frames will not be rendered at all
				return;
			}

			$frame =& $this->content[ $i ];

			/**
			 * Depending on the source HTML, FramesetBox may contain some non-frame boxes;
			 * we'll just ignore them
			 */
			if ( ! is_a( $frame, "FramesetBox" ) &&
			     ! is_a( $frame, "FrameBox" ) ) {
				continue;
			};

			// Guess frame size and position
			$frame->put_left( $this->get_left() + array_sum( array_slice( $cols, 0, $cur_col ) ) + $frame->get_extra_left() );
			$frame->put_top( $this->get_top() - array_sum( array_slice( $rows, 0, $cur_row ) ) - $frame->get_extra_top() );

			$frame->put_full_width( $cols[ $cur_col ] );
			$frame->setCSSProperty( CSS_WIDTH, new WCConstant( $frame->get_width() ) );

			$frame->put_full_height( $rows[ $cur_row ] );
			$frame->put_height_constraint( new WCConstant( $frame->get_height() ) );

			// Reflow frame contents
			$context->push_viewport( FlowViewport::create( $frame ) );
			$frame->reflow( $this, $context );
			$context->pop_viewport();

			// Move to the next frame position
			// Next columns
			$cur_col ++;
			if ( $cur_col >= count( $cols ) ) {
				// Next row
				$cur_col = 0;
				$cur_row ++;
			}
		}
	}
}


// $Header: /cvsroot/html2ps/box.iframe.php,v 1.14 2006/12/18 19:44:21 Konstantin Exp $

class IFrameBox extends InlineBlockBox {
	function &create( &$root, &$pipeline ) {
		$box =& new IFrameBox( $root, $pipeline );
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	// Note that IFRAME width is NOT determined by its content, thus we need to override 'get_min_width' and
	// 'get_max_width'; they should return the constrained frame width.
	function get_min_width( &$context ) {
		return $this->get_max_width( $context );
	}

	function get_max_width( &$context ) {
		return $this->get_width();
	}

	function IFrameBox( &$root, $pipeline ) {
		$this->InlineBlockBox();

		// If NO src attribute specified, just return.
		if ( ! $root->has_attribute( 'src' ) ||
		     trim( $root->get_attribute( 'src' ) ) == '' ) {
			return;
		};

		// Determine the fullly qualified URL of the frame content
		$src  = $root->get_attribute( 'src' );
		$url  = $pipeline->guess_url( $src );
		$data = $pipeline->fetch( $url );

		/**
		 * If framed page could not be fetched return immediately
		 */
		if ( is_null( $data ) ) {
			return;
		};

		/**
		 * Render only iframes containing HTML only
		 *
		 * Note that content-type header may contain additional information after the ';' sign
		 */
		$content_type       = $data->get_additional_data( 'Content-Type' );
		$content_type_array = explode( ';', $content_type );
		if ( $content_type_array[0] != "text/html" ) {
			return;
		};

		$html = $data->get_content();

		// Remove control symbols if any
		$html      = preg_replace( '/[\x00-\x07]/', "", $html );
		$converter = Converter::create();
		$html      = $converter->to_utf8( $html, $data->detect_encoding() );
		$html      = html2xhtml( $html );
		$tree      = TreeBuilder::build( $html );

		// Save current stylesheet, as each frame may load its own stylesheets
		//
		$pipeline->push_css();
		$css =& $pipeline->get_current_css();
		$css->scan_styles( $tree, $pipeline );

		$frame_root = traverse_dom_tree_pdf( $tree );
		$box_child  =& create_pdf_box( $frame_root, $pipeline );
		$this->add_child( $box_child );

		// Restore old stylesheet
		//
		$pipeline->pop_css();

		$pipeline->pop_base_url();
	}
}


// $Header: /cvsroot/html2ps/box.input.text.php,v 1.28 2007/01/03 19:39:29 Konstantin Exp $

/// define('SIZE_SPACE_KOEFF',1.65); (defined in tag.input.inc.php)

class TextInputBox extends InlineControlBox {
	/**
	 * @var String contains the default value of this text field
	 * @access private
	 */
	var $_value;

	function TextInputBox( $value, $name ) {
		$this->InlineControlBox();

		$this->_value      = $value;
		$this->_field_name = $name;
	}

	function &create( &$root, &$pipeline ) {
		// Text to be displayed
		if ( $root->has_attribute( 'value' ) ) {
			$text = trim( $root->get_attribute( 'value' ) );
		} else {
			$text = '';
		};

		/**
		 * Input field name
		 */
		$name = $root->get_attribute( 'name' );

		$box =& new TextInputBox( $root->get_attribute( "value" ), $name );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->setup_content( $text, $pipeline );

		return $box;
	}

	function get_height() {
		$normal_height = parent::get_height();

		$hc = $this->get_height_constraint();
		if ( $hc->is_null() ) {
			return $normal_height;
		} else {
			return $normal_height - $this->_get_vert_extra();
		};
	}

	function show( &$driver ) {
		// Now set the baseline of a button box to align it vertically when flowing isude the
		// text line

		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

		/**
		 * If we're rendering the interactive form, the field content should not be rendered
		 */
		global $g_config;
		if ( $g_config['renderforms'] ) {
			/**
			 * Render background/borders only
			 */
			$status = GenericFormattedBox::show( $driver );

			/**
			 * @todo encoding name?
			 * @todo font name?
			 * @todo check if font is embedded for PDFLIB
			 */
			$driver->field_text( $this->get_left_padding(),
				$this->get_top_padding(),
				$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
				$this->get_height(),
				$this->_value,
				$this->_field_name );
		} else {
			/**
			 * Render everything, including content
			 */
			$status = GenericContainerBox::show( $driver );
		}

		return $status;
	}
}

// $Header: /cvsroot/html2ps/box.input.textarea.php,v 1.5 2006/12/24 14:42:43 Konstantin Exp $

class TextAreaInputBox extends InlineBlockBox {
	var $_field_name;
	var $_value;

	function TextAreaInputBox( $value, $name ) {
		$this->InlineBlockBox();

		$this->set_value( $value );
		$this->_field_name = $name;
	}

	function &create( &$root, &$pipeline ) {
		$value = $root->get_content();
		$name  = $root->get_attribute( 'name' );

		$box = new TextAreaInputBox( $value, $name );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function get_height() {
		$normal_height = parent::get_height();

		return $normal_height - $this->_get_vert_extra();
	}

	function get_min_width( &$context ) {
		return $this->get_max_width( $context );
	}

	function get_max_width( &$context ) {
		return $this->get_width();
	}

	function get_value() {
		return $this->_value;
	}

	function get_width() {
		$normal_width = parent::get_width();

		return $normal_width - $this->_get_hor_extra();
	}

	function set_value( $value ) {
		$this->_value = $value;
	}

	function show( &$driver ) {
		/**
		 * If we're rendering the interactive form, the field content should not be rendered
		 */
		global $g_config;
		if ( $g_config['renderforms'] ) {
			$status = GenericFormattedBox::show( $driver );

			$driver->field_multiline_text( $this->get_left_padding(),
				$this->get_top_padding(),
				$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
				$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom(),
				$this->_value,
				$this->_field_name );
		} else {
			$status = GenericContainerBox::show( $driver );
		}

		return $status;
	}
}


// $Header: /cvsroot/html2ps/box.input.password.php,v 1.6 2006/10/06 20:10:52 Konstantin Exp $

class PasswordInputBox extends TextInputBox {
	function &create( &$root, &$pipeline ) {
		// Text to be displayed
		if ( $root->has_attribute( 'value' ) ) {
			$text = str_repeat( "*", strlen( $root->get_attribute( "value" ) ) );
		} else {
			$text = "";
		};

		/**
		 * Input field name
		 */
		$name = $root->get_attribute( 'name' );

		$box =& new PasswordInputBox( $text, $root->get_attribute( "value" ), $name );
		$box->readCSS( $pipeline->get_current_css_state() );

		$ibox = InlineBox::create_from_text( " ", WHITESPACE_PRE, $pipeline );
		for ( $i = 0, $size = count( $ibox->content ); $i < $size; $i ++ ) {
			$box->add_child( $ibox->content[ $i ] );
		};

		return $box;
	}

	function show( &$driver ) {
		// Now set the baseline of a button box to align it vertically when flowing isude the
		// text line
		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

		/**
		 * If we're rendering the interactive form, the field content should not be rendered
		 */
		global $g_config;
		if ( $g_config['renderforms'] ) {
			/**
			 * Render background/borders only
			 */
			$status = GenericFormattedBox::show( $driver );

			/**
			 * @todo encoding name?
			 * @todo font name?
			 * @todo check if font is embedded for PDFLIB
			 */
			$driver->field_password( $this->get_left_padding(),
				$this->get_top_padding(),
				$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
				$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom(),
				$this->_value,
				$this->_field_name );
		} else {
			/**
			 * Render everything, including content
			 */
			$status = GenericContainerBox::show( $driver );
		}

		return $status;
	}
}

// $Header: /cvsroot/html2ps/box.legend.php,v 1.14 2006/07/09 09:07:44 Konstantin Exp $

class LegendBox extends GenericContainerBox {
	function &create( &$root, &$pipeline ) {
		$box = new LegendBox( $root );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function LegendBox( &$root ) {
		// Call parent constructor
		$this->GenericContainerBox();

		$this->_current_x = 0;
		$this->_current_y = 0;
	}

	// Flow-control
	function reflow( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// Determine upper-left _content_ corner position of current box
		$this->put_left( $parent->get_left_padding() );
		$this->put_top( $parent->get_top_padding() );

		// Legends will not wrap
		$this->put_full_width( $this->get_max_width( $context ) );

		// Reflow contents
		$this->reflow_content( $context );

		// Adjust legend position
		$height = $this->get_full_height();
		$this->offset( units2pt( LEGEND_HORIZONTAL_OFFSET ) + $this->get_extra_left(),
			$height / 2 );
		// Adjust parent position
		$parent->offset( 0, - $height / 2 );
		// Adjust parent content position
		for ( $i = 0; $i < count( $parent->content ); $i ++ ) {
			if ( $parent->content[ $i ]->uid != $this->uid ) {
				$parent->content[ $i ]->offset( 0, - $height / 2 );
			}
		}
		$parent->_current_y -= $height / 2;

		$parent->extend_height( $this->get_bottom_margin() );
	}

	function show( &$driver ) {
		// draw generic box
		return GenericContainerBox::show( $driver );
	}
}

// $Header: /cvsroot/html2ps/box.list-item.php,v 1.34 2006/09/07 18:38:12 Konstantin Exp $

class ListItemBox extends BlockBox {
	var $size;

	function &create( &$root, &$pipeline ) {
		$box = new ListItemBox( $root, $pipeline );
		$box->readCSS( $pipeline->get_current_css_state() );

		/**
		 * Create text box containing item number
		 */
		$css_state =& $pipeline->get_current_css_state();
		$css_state->pushState();
		//    $css_state->set_property(CSS_COLOR, CSSColor::parse('transparent'));

		$list_style          = $css_state->get_property( CSS_LIST_STYLE );
		$box->str_number_box = TextBox::create( CSSListStyleType::format_number( $list_style->type,
				$css_state->get_property( CSS_HTML2PS_LIST_COUNTER ) ) . ". ",
			'iso-8859-1',
			$pipeline );
		$box->str_number_box->readCSS( $pipeline->get_current_css_state() );
		$box->str_number_box->baseline = $box->str_number_box->default_baseline;

		$css_state->popState();

		/**
		 * Create nested items
		 */
		$box->create_content( $root, $pipeline );

		return $box;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array( CSS_LIST_STYLE ) );

		// Pseudo-CSS properties
		// '-list-counter'

		// increase counter value
		$value = $state->get_property( CSS_HTML2PS_LIST_COUNTER ) + 1;
		$state->set_property( CSS_HTML2PS_LIST_COUNTER, $value );
		$state->set_property_on_level( CSS_HTML2PS_LIST_COUNTER, CSS_PROPERTY_LEVEL_PARENT, $value );

		// open the marker image if specified
		$list_style = $this->get_css_property( CSS_LIST_STYLE );

		if ( ! $list_style->image->is_default() ) {
			$this->marker_image = new ImgBox( $list_style->image->_image );
			$state->pushDefaultState();
			$this->marker_image->readCSS( $state );
			$state->popState();
			$this->marker_image->_setupSize();
		} else {
			$this->marker_image = null;
		};
	}

	function ListItemBox( &$root, &$pipeline ) {
		// Call parent constructor
		$this->BlockBox( $root );
	}

	function reflow( &$parent, &$context ) {
		$list_style = $this->get_css_property( CSS_LIST_STYLE );

		// If list-style-position is inside, we'll need to move marker box inside the
		// list-item box and offset all content by its size;
		if ( $list_style->position === LSP_INSIDE ) {
			// Add marker box width to text-indent value
			$this->_additional_text_indent = $this->get_marker_box_width();
		};

		// Procees with normal block box flow algorithm
		BlockBox::reflow( $parent, $context );
	}

	function reflow_text( &$driver ) {
		if ( is_null( $this->str_number_box->reflow_text( $driver ) ) ) {
			return null;
		};

		return GenericContainerBox::reflow_text( $driver );
	}

	function show( &$viewport ) {
		// draw generic block box
		if ( is_null( BlockBox::show( $viewport ) ) ) {
			return null;
		};

		// Draw marker
		/**
		 * Determine the marker box base X coordinate
		 * If possible, the marker box should be drawn immediately to the left of the first word in this
		 * box; this means that marker should be tied to the first text box, not to the left
		 * edge of the list block box
		 */
		$child = $this->get_first_data();
		if ( is_null( $child ) ) {
			$x = $this->get_left();

			$list_style = $this->get_css_property( CSS_LIST_STYLE );

			// If list-style-position is inside, we'll need to move marker box inside the
			// list-item box and offset all content by its size;
			if ( $list_style->position === LSP_INSIDE ) {
				$x += $this->get_marker_box_width();
			};
		} else {
			$x = $child->get_left();
		};

		// Determine the base Y coordinate of marker box
		$element = $this->get_first_data();

		if ( $element ) {
			$y = $element->get_top() - $element->default_baseline;
		} else {
			$y = $this->get_top();
		}

		if ( ! is_null( $this->marker_image ) ) {
			$this->mb_image( $viewport, $x, $y );
		} else {
			$list_style = $this->get_css_property( CSS_LIST_STYLE );

			switch ( $list_style->type ) {
				case LST_NONE:
					// No marker at all
					break;
				case LST_DISC:
					$this->mb_disc( $viewport, $x, $y );
					break;
				case LST_CIRCLE:
					$this->mb_circle( $viewport, $x, $y );
					break;
				case LST_SQUARE:
					$this->mb_square( $viewport, $x, $y );
					break;
				default:
					$this->mb_string( $viewport, $x, $y );
					break;
			}
		};

		return true;
	}

	function get_marker_box_width() {
		$list_style = $this->get_css_property( CSS_LIST_STYLE );

		switch ( $list_style->type ) {
			case LST_NONE:
				// no marker box will be rendered at all
				return 0;
			case LST_DISC:
			case LST_CIRCLE:
			case LST_SQUARE:
				//  simple graphic marker
				$font = $this->get_css_property( CSS_FONT );

				return $font->size->getPoints();
			default:
				// string marker. Return the width of the marker text
				return $this->str_number_box->get_full_width();
		};
	}

	function mb_string( &$viewport, $x, $y ) {
		$this->str_number_box->put_top( $y + $this->str_number_box->default_baseline );
		$this->str_number_box->put_left( $x - $this->str_number_box->get_full_width() );

		$this->str_number_box->show( $viewport );
	}

	function mb_disc( &$viewport, $x, $y ) {
		$color = $this->get_css_property( CSS_COLOR );
		$color->apply( $viewport );

		$font = $this->get_css_property( CSS_FONT );

		$viewport->circle( $x - $font->size->getPoints() * 0.5, $y + $font->size->getPoints() * 0.4 * HEIGHT_KOEFF, $font->size->getPoints() * BULLET_SIZE_KOEFF );
		$viewport->fill();
	}

	function mb_circle( &$viewport, $x, $y ) {
		$color = $this->get_css_property( CSS_COLOR );
		$color->apply( $viewport );

		$viewport->setlinewidth( 0.1 );

		$font = $this->get_css_property( CSS_FONT );
		$viewport->circle( $x - $font->size->getPoints() * 0.5, $y + $font->size->getPoints() * 0.4 * HEIGHT_KOEFF, $font->size->getPoints() * BULLET_SIZE_KOEFF );
		$viewport->stroke();
	}

	function mb_square( &$viewport, $x, $y ) {
		$color = $this->get_css_property( CSS_COLOR );
		$color->apply( $viewport );

		$font = $this->get_css_property( CSS_FONT );
		$viewport->rect( $x - $font->size->getPoints() * 0.512, $y + $font->size->getPoints() * 0.3 * HEIGHT_KOEFF, $font->size->getPoints() * 0.25, $font->size->getPoints() * 0.25 );
		$viewport->fill();
	}

	function mb_image( &$viewport, $x, $y ) {
		$font = $this->get_css_property( CSS_FONT );

		$imagebox =& $this->marker_image;
		$imagebox->moveto( $x - $font->size->getPoints() * 0.5 - $imagebox->get_width() / 2,
			$y + $font->size->getPoints() * 0.4 * HEIGHT_KOEFF + $imagebox->get_height() / 2 );
		$imagebox->show( $viewport );
	}

	function isBlockLevel() {
		return true;
	}
}


// $Header: /cvsroot/html2ps/box.null.php,v 1.18 2006/07/09 09:07:44 Konstantin Exp $

class NullBox extends GenericInlineBox {
	function get_min_width( &$context ) {
		return 0;
	}

	function get_max_width( &$context ) {
		return 0;
	}

	function get_height() {
		return 0;
	}

	function NullBox() {
		$this->GenericInlineBox();
	}

	function &create() {
		$box =& new NullBox;

		$css_state = new CSSState( CSS::get() );
		$css_state->pushState();
		$box->readCSS( $css_state );

		return $box;
	}

	function show( &$viewport ) {
		return true;
	}

	function reflow_static( &$parent, &$context ) {
		if ( ! $parent ) {
			$this->put_left( 0 );
			$this->put_top( 0 );

			return;
		};

		// Move current "box" to parent current coordinates. It is REQUIRED,
		// as some other routines uses box coordinates.
		$this->put_left( $parent->get_left() );
		$this->put_top( $parent->get_top() );
	}

	function is_null() {
		return true;
	}
}

// $Header: /cvsroot/html2ps/box.radiobutton.php,v 1.20 2006/11/11 13:43:51 Konstantin Exp $

require_once( HTML2PS_DIR . 'box.inline.simple.php' );

class RadioBox extends SimpleInlineBox {
	var $_checked;

	/**
	 * @var String name of radio button group
	 * @access private
	 */
	var $_group_name;

	/**
	 * @var String value to be posted as this radio button value
	 * @access private
	 */
	var $_value;

	function &create( &$root, &$pipeline ) {
		$checked = $root->has_attribute( 'checked' );

		$value = $root->get_attribute( 'value' );
		if ( trim( $value ) == "" ) {
			error_log( "Radiobutton with empty 'value' attribute" );
			$value = sprintf( "___Value%s", md5( time() . rand() ) );
		};

		$css_state = $pipeline->get_current_css_state();

		$box =& new RadioBox( $checked, $value,
			$css_state->get_property( CSS_HTML2PS_FORM_RADIOGROUP ) );
		$box->readCSS( $css_state );

		return $box;
	}

	function RadioBox( $checked, $value, $group_name ) {
		// Call parent constructor
		$this->GenericBox();

		// Check the box state
		$this->_checked = $checked;

		/**
		 * Store the form value for this radio button
		 */
		$this->_value = trim( $value );

		$this->_group_name = $group_name;

		// Setup box size:
		$this->default_baseline = units2pt( RADIOBUTTON_SIZE );
		$this->height           = units2pt( RADIOBUTTON_SIZE );
		$this->width            = units2pt( RADIOBUTTON_SIZE );

		$this->setCSSProperty( CSS_DISPLAY, '-radio' );
	}

	// Inherited from GenericFormattedBox
	function get_min_width( &$context ) {
		return $this->get_full_width( $context );
	}

	function get_max_width( &$context ) {
		return $this->get_full_width( $context );
	}

	function get_max_width_natural( &$context ) {
		return $this->get_full_width( $context );
	}

	function reflow( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// set default baseline
		$this->baseline = $this->default_baseline;

		// append to parent line box
		$parent->append_line( $this );

		// Determine coordinates of upper-left _margin_ corner
		$this->guess_corner( $parent );

		// Offset parent current X coordinate
		$parent->_current_x += $this->get_full_width();

		// Extends parents height
		$parent->extend_height( $this->get_bottom_margin() );
	}

	function show( &$driver ) {
		// Cet check center
		$x = ( $this->get_left() + $this->get_right() ) / 2;
		$y = ( $this->get_top() + $this->get_bottom() ) / 2;

		// Calculate checkbox size
		$size = $this->get_width() / 3;

		// Draw checkbox
		$driver->setlinewidth( 0.25 );
		$driver->circle( $x, $y, $size );
		$driver->stroke();

		/**
		 * Render the interactive button (if requested and possible)
		 * Also, if no value were specified, then this radio button should not be interactive
		 */
		global $g_config;
		if ( $g_config['renderforms'] && $this->_value != "" ) {
			$driver->field_radio( $x - $size,
				$y + $size,
				2 * $size,
				2 * $size,
				$this->_group_name,
				$this->_value,
				$this->_checked );
		} else {
			// Draw checkmark if needed
			if ( $this->_checked ) {
				$check_size = $this->get_width() / 6;

				$driver->circle( $x, $y, $check_size );
				$driver->fill();
			}
		};

		return true;
	}

	function get_ascender() {
		return $this->get_height();
	}

	function get_descender() {
		return 0;
	}
}

// $Header: /cvsroot/html2ps/box.select.php,v 1.24 2007/01/03 19:39:29 Konstantin Exp $

class SelectBox extends InlineControlBox {
	var $_name;
	var $_value;
	var $_options;

	function SelectBox( $name, $value, $options ) {
		// Call parent constructor
		$this->InlineBox();

		$this->_name    = $name;
		$this->_value   = $value;
		$this->_options = $options;
	}

	function &create( &$root, &$pipeline ) {
		$name = $root->get_attribute( 'name' );

		$value   = '';
		$options = array();

		// Get option list
		$child   = $root->first_child();
		$content = '';
		$size    = 0;
		while ( $child ) {
			if ( $child->node_type() == XML_ELEMENT_NODE ) {
				$size = max( $size, strlen( $child->get_content() ) );
				if ( empty( $content ) || $child->has_attribute( 'selected' ) ) {
					$content = preg_replace( '/\s/', ' ', $child->get_content() );
					$value   = trim( $child->get_content() );
				};

				if ( $child->has_attribute( 'value' ) ) {
					$options[] = array(
						$child->get_attribute( 'value' ),
						$child->get_content()
					);
				} else {
					$options[] = array(
						$child->get_content(),
						$child->get_content()
					);
				};
			};
			$child = $child->next_sibling();
		};
		$content = str_pad( $content, $size * SIZE_SPACE_KOEFF + SELECT_SPACE_PADDING, ' ' );

		$box =& new SelectBox( $name, $value, $options );
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->setup_content( $content, $pipeline );

		return $box;
	}

	function show( &$driver ) {
		global $g_config;
		if ( $g_config['renderforms'] ) {
			return $this->show_field( $driver );
		} else {
			return $this->show_rendered( $driver );
		};
	}

	function show_field( &$driver ) {
		if ( is_null( GenericFormattedBox::show( $driver ) ) ) {
			return null;
		};

		$driver->field_select( $this->get_left_padding(),
			$this->get_top_padding(),
			$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
			$this->get_height(),
			$this->_name,
			$this->_value,
			$this->_options );

		return true;
	}

	function show_rendered( &$driver ) {
		// Now set the baseline of a button box to align it vertically when flowing isude the
		// text line
		$this->default_baseline = $this->content[0]->baseline + $this->get_extra_top();
		$this->baseline         = $this->content[0]->baseline + $this->get_extra_top();

		if ( is_null( GenericContainerBox::show( $driver ) ) ) {
			return null;
		};

		$this->show_button( $driver );

		return true;
	}

	function show_button( &$driver ) {
		$padding       = $this->get_css_property( CSS_PADDING );
		$button_height = $this->get_height() + $padding->top->value + $padding->bottom->value;

		// Show arrow button box
		$driver->setrgbcolor( 0.93, 0.93, 0.93 );
		$driver->moveto( $this->get_right_padding(), $this->get_top_padding() );
		$driver->lineto( $this->get_right_padding() - $button_height, $this->get_top_padding() );
		$driver->lineto( $this->get_right_padding() - $button_height, $this->get_bottom_padding() );
		$driver->lineto( $this->get_right_padding(), $this->get_bottom_padding() );
		$driver->closepath();
		$driver->fill();

		// Show box boundary
		$driver->setrgbcolor( 0, 0, 0 );
		$driver->moveto( $this->get_right_padding(), $this->get_top_padding() );
		$driver->lineto( $this->get_right_padding() - $button_height, $this->get_top_padding() );
		$driver->lineto( $this->get_right_padding() - $button_height, $this->get_bottom_padding() );
		$driver->lineto( $this->get_right_padding(), $this->get_bottom_padding() );
		$driver->closepath();
		$driver->stroke();

		// Show arrow
		$driver->setrgbcolor( 0, 0, 0 );
		$driver->moveto( $this->get_right_padding() - SELECT_BUTTON_TRIANGLE_PADDING,
			$this->get_top_padding() - SELECT_BUTTON_TRIANGLE_PADDING );
		$driver->lineto( $this->get_right_padding() - $button_height + SELECT_BUTTON_TRIANGLE_PADDING,
			$this->get_top_padding() - SELECT_BUTTON_TRIANGLE_PADDING );
		$driver->lineto( $this->get_right_padding() - $button_height / 2, $this->get_bottom_padding() + SELECT_BUTTON_TRIANGLE_PADDING );
		$driver->closepath();
		$driver->fill();

		return true;
	}
}

// $Header: /cvsroot/html2ps/box.table.php,v 1.59 2007/04/01 12:11:24 Konstantin Exp $

class CellSpan {
	var $row;
	var $column;
	var $size;
}

/**
 * It is assumed that every row contains at least one cell
 */
class TableBox extends GenericContainerBox {
	var $cwc;
	var $_cached_min_widths;

	function TableBox() {
		$this->GenericContainerBox();

		// List of column width constraints
		$this->cwc = array();

		$this->_cached_min_widths = null;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array(
				CSS_BORDER_COLLAPSE,
				CSS_TABLE_LAYOUT
			) );

		$this->_readCSSLengths( $state,
			array(
				CSS_HTML2PS_CELLPADDING,
				CSS_HTML2PS_CELLSPACING
			) );
	}

	function &cell( $r, $c ) {
		return $this->content[ $r ]->content[ $c ];
	}

	function rows_count() {
		return count( $this->content );
	}

	// NOTE: assumes that rows are already normalized!
	function cols_count() {
		return count( $this->content[0]->content );
	}

	// FIXME: just a stub
	function append_line( &$e ) {
	}

	function &create( &$root, &$pipeline ) {
		$box =& new TableBox();
		$box->readCSS( $pipeline->get_current_css_state() );

		// This row should not inherit any table specific properties!
		// 'overflow' for example
		//
		$css_state =& $pipeline->get_current_css_state();
		$css_state->pushDefaultState();

		$row =& new TableRowBox( $root );
		$row->readCSS( $css_state );

		$box->add_child( $row );

		$css_state->popState();

		// Setup cellspacing / cellpadding values
		if ( $box->get_css_property( CSS_BORDER_COLLAPSE ) == BORDER_COLLAPSE ) {
			$handler =& CSS::get_handler( CSS_PADDING );
			$box->setCSSProperty( CSS_PADDING, $handler->default_value() );
		};

		// Set text-align to 'left'; all browsers I've ever seen prevent inheriting of
		// 'text-align' property by the tables.
		// Say, in the following example the text inside the table cell will be aligned left,
		// instead of inheriting 'center' value.
		//
		// <div style="text-align: center; background-color: green;">
		// <table width="100" bgcolor="red">
		// <tr><td>TEST
		// </table>
		// </div>
		$handler =& CSS::get_handler( CSS_TEXT_ALIGN );
		$handler->css( 'left', $pipeline );

		// Parse table contents
		$child     = $root->first_child();
		$col_index = 0;
		while ( $child ) {
			if ( $child->node_type() === XML_ELEMENT_NODE ) {
				if ( $child->tagname() === 'colgroup' ) {
					// COLGROUP tags do not generate boxes; they contain information on the columns
					//
					$col_index = $box->parse_colgroup_tag( $child, $col_index );
				} else {
					$child_box =& create_pdf_box( $child, $pipeline );
					$box->add_child( $child_box );
				};
			};

			$child = $child->next_sibling();
		};

		$box->normalize( $pipeline );
		$box->normalize_cwc();
		$box->normalize_rhc();
		$box->normalize_parent();

		return $box;
	}

	// Parse the data in COL node;
	// currently only 'width' attribute is parsed
	//
	// @param $root reference to a COL dom node
	// @param $index index of column corresponding to this node
	function parse_col( &$root, $index ) {
		if ( $root->has_attribute( 'width' ) ) {
			// The value if 'width' attrubute is "multi-length";
			// it means that it could be:
			// 1. absolute value (10)
			// 2. percentage value (10%)
			// 3. relative value (3* or just *)
			//

			// TODO: support for relative values

			$value = $root->get_attribute( 'width' );
			if ( is_percentage( $value ) ) {
				$this->cwc[ $index ] = new WCFraction( ( (int) $value ) / 100 );
			} else {
				$this->cwc[ $index ] = new WCConstant( px2pt( (int) $value ) );
			};
		};
	}

	// Traverse the COLGROUP node and save the column-specific information
	//
	// @param $root COLGROUP node
	// @param $start_index index of the first column in this column group
	// @return index of column after the last processed
	//
	function parse_colgroup_tag( &$root, $start_index ) {
		$index = $start_index;

		// COLGROUP may contain zero or more COLs
		//
		$child = $root->first_child();
		while ( $child ) {
			if ( $child->tagname() === 'col' ) {
				$this->parse_col( $child, $index );
				$index ++;
			};
			$child = $child->next_sibling();
		};

		return $index;
	}

	function normalize_parent() {
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$this->content[ $i ]->parent =& $this;

			for ( $j = 0; $j < count( $this->content[ $i ]->content ); $j ++ ) {
				$this->content[ $i ]->content[ $j ]->parent =& $this;

				// Set the column number for the cell to further reference
				$this->content[ $i ]->content[ $j ]->column = $j;

				// Set the column number for the cell to further reference
				$this->content[ $i ]->content[ $j ]->row = $i;
			}
		}
	}

	// Normalize row height constraints
	//
	// no return value
	//
	function normalize_rhc() {
		// Initialize the constraint array with the empty constraints
		$this->rhc = array();
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			$this->rhc[ $i ] = new HCConstraint( null, null, null );
		};

		// Scan all cells
		for ( $i = 0, $num_rows = count( $this->content ); $i < $num_rows; $i ++ ) {
			$row =& $this->content[ $i ];

			for ( $j = 0, $num_cells = count( $row->content ); $j < $num_cells; $j ++ ) {
				$cell = $row->content[ $j ];

				// Ignore cells with rowspans
				if ( $cell->rowspan > 1 ) {
					continue;
				}

				// Put current cell width constraint as a columns with constraint
				$this->rhc[ $i ] = merge_height_constraint( $this->rhc[ $i ], $cell->get_height_constraint() );

				// Now reset the cell width constraint; cell width should be affected by ceolumn constraint only
				$hc = new HCConstraint( null, null, null );
				$cell->put_height_constraint( $hc );
			};
		};
	}

	// Normalize column width constraints
	// Note that cwc array may be partially prefilled by a GOLGROUP/COL-generated constraints!
	//
	function normalize_cwc() {
		// Note we've called 'normalize' method prior to 'normalize_cwc',
		// so we already have all rows of equal length
		//
		for ( $i = 0, $num_cols = count( $this->content[0]->content ); $i < $num_cols; $i ++ ) {
			// Check if there's already COL-generated constraint for this column
			//
			if ( ! isset( $this->cwc[ $i ] ) ) {
				$this->cwc[ $i ] = new WCNone;
			};
		}

		// For each column (we should have table already normalized - so lengths of all rows are equal)
		for ( $i = 0, $num_cols = count( $this->content[0]->content ); $i < $num_cols; $i ++ ) {

			// For each row
			for ( $j = 0, $num_rows = count( $this->content ); $j < $num_rows; $j ++ ) {
				$cell =& $this->content[ $j ]->content[ $i ];

				// Ignore cells with colspans
				if ( $cell->colspan > 1 ) {
					continue;
				}

				// Put current cell width constraint as a columns with constraint
				$this->cwc[ $i ] = merge_width_constraint( $this->cwc[ $i ], $cell->get_css_property( CSS_WIDTH ) );

				// Now reset the cell width constraint; cell width should be affected by ceolumn constraint only
				$cell->setCSSProperty( CSS_WIDTH, new WCNone );
			}
		}

		// Now fix the overconstrained columns; first of all, sum of all percentage-constrained
		// columns should be less or equal than 100%. If sum is greater, the last column
		// percentage is reduced in order to get 100% as a result.
		$rest = 1;
		for ( $i = 0, $num_cols = count( $this->content[0]->content ); $i < $num_cols; $i ++ ) {
			// Get current CWC
			$wc =& $this->cwc[ $i ];

			if ( $wc->isFraction() ) {
				$wc->fraction = min( $rest, $wc->fraction );
				$rest         -= $wc->fraction;
			};
		};

		/**
		 * Now, let's process cells spanninig several columns.
		 */

		/**
		 * Let's check if there's any colspanning cells filling the whole table width and
		 * containing non-100% percentage constraint
		 */

		// For each row
		for ( $j = 0; $j < count( $this->content ); $j ++ ) {
			/**
			 * Check if the first cell in this row satisfies the above condition
			 */

			$cell =& $this->content[ $j ]->content[0];

			/**
			 * Note that there should be '>='; '==' is not enough, as sometimes cell is declared to span
			 * more columns than there are in the table
			 */
			$cell_wc = $cell->get_css_property( CSS_WIDTH );
			if ( ! $cell->is_fake() &&
			     $cell_wc->isFraction() &&
			     $cell->colspan >= count( $this->content[ $j ] ) ) {

				/**
				 * Clear the constraint; anyway, it should be replaced with 100% in this case, as
				 * this cell is the only cell in the row
				 */

				$wc = new WCNone;
				$cell->setCSSProperty( CSS_WIDTH, $wc );
			};
		};
	}

	/**
	 * Normalize table by adding fake cells for colspans and rowspans
	 * Also, if there is any empty rows (without cells), add at least one fake cell
	 */
	function normalize( &$pipeline ) {
		/**
		 * Fix empty rows by adding a fake cell
		 */
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$row =& $this->content[ $i ];
			if ( count( $row->content ) == 0 ) {
				$this->content[ $i ]->add_fake_cell_before( 0, $pipeline );
			};
		};

		/**
		 * first, normalize colspans
		 */
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$this->content[ $i ]->normalize( $pipeline );
		};

		/**
		 * second, normalize rowspans
		 *
		 * We should scan table column-by-column searching for row-spanned cells;
		 * consider the following example:
		 *
		 * <table>
		 * <tr>
		 * <td>A1</td>
		 * <td rowspan="3">B1</td>
		 * <td>C1</td>
		 * </tr>
		 *
		 * <tr>
		 * <td rowspan="2">A2</td>
		 * <td>C2</td>
		 * </tr>
		 *
		 * <tr>
		 * <td>C3</td>
		 * </tr>
		 * </table>
		 */

		$i_col = 0;
		do {
			$flag = false;
			for ( $i_row = 0; $i_row < count( $this->content ); $i_row ++ ) {
				$row =& $this->content[ $i_row ];
				if ( $i_col < count( $row->content ) ) {
					$flag = true;

					// Check if this rowspan runs off the last row
					$row->content[ $i_col ]->rowspan = min( $row->content[ $i_col ]->rowspan,
						count( $this->content ) - $i_row );

					if ( $row->content[ $i_col ]->rowspan > 1 ) {

						// Note that min($i_row + $row->content[$i_col]->rowspan, count($this->content)) is
						// required, as we cannot be sure that table actually contains the number
						// of rows used in rowspan
						//
						for ( $k = $i_row + 1; $k < min( $i_row + $row->content[ $i_col ]->rowspan, count( $this->content ) ); $k ++ ) {

							// Note that if rowspanned cell have a colspan, we should insert SEVERAL fake cells!
							//
							for ( $cs = 0; $cs < $row->content[ $i_col ]->colspan; $cs ++ ) {
								$this->content[ $k ]->add_fake_cell_before( $i_col, $pipeline );
							};
						};
					};
				};
			};

			$i_col ++;
		} while ( $flag );

		// third, make all rows equal in length by padding with fake-cells
		$length = 0;
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$length = max( $length, count( $this->content[ $i ]->content ) );
		}
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$row =& $this->content[ $i ];
			while ( $length > count( $row->content ) ) {
				$row->append_fake_cell( $pipeline );
			}
		}
	}

	// Overrides default 'add_child' in GenericFormattedBox
	function add_child( &$item ) {
		// Check if we're trying to add table cell to current table directly, without any table-rows
		if ( $item->isCell() ) {
			// Add cell to the last row
			$last_row =& $this->content[ count( $this->content ) - 1 ];
			$last_row->add_child( $item );

		} elseif ( $item->isTableRow() ) {
			// If previous row is empty, remove it (get rid of automatically generated table row in constructor)
			if ( count( $this->content ) > 0 ) {
				if ( count( $this->content[ count( $this->content ) - 1 ]->content ) == 0 ) {
					array_pop( $this->content );
				}
			};

			// Just add passed row
			$this->content[] =& $item;
		} elseif ( $item->isTableSection() ) {
			// Add table section rows to current table, then drop section box
			for ( $i = 0, $size = count( $item->content ); $i < $size; $i ++ ) {
				$this->add_child( $item->content[ $i ] );
			}
		};
	}

	// Table-specific functions

	// PREDICATES
	function is_constrained_column( $index ) {
		return ! is_a( $this->get_cwc( $index ), "wcnone" );
	}

	// ROWSPANS
	function table_have_rowspan( $x, $y ) {
		return $this->content[ $y ]->content[ $x ]->rowspan;
	}

	function table_fit_rowspans( $heights ) {
		$spans = $this->get_rowspans();

		// Scan all cells spanning several rows
		foreach ( $spans as $span ) {
			$cell =& $this->content[ $span->row ]->content[ $span->column ];

			// now check if cell height is less than sum of spanned rows heights
			$row_heights = array_slice( $heights, $span->row, $span->size );

			// Vertical-align current cell
			// calculate (approximate) row baseline
			$baseline = $this->content[ $span->row ]->get_row_baseline();

			// apply vertical-align
			$vertical_align = $cell->get_css_property( CSS_VERTICAL_ALIGN );

			$va_fun = CSSVerticalAlign::value2pdf( $vertical_align );
			$va_fun->apply_cell( $cell, array_sum( $row_heights ), $baseline );

			if ( array_sum( $row_heights ) > $cell->get_full_height() ) {
				// Make cell fill all available vertical space
				$cell->put_full_height( array_sum( $row_heights ) );
			};
		}
	}

	function get_rowspans() {
		$spans = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$spans = array_merge( $spans, $this->content[ $i ]->get_rowspans( $i ) );
		};

		return $spans;
	}

	// ROW-RELATED

	/**
	 * Calculate set of row heights
	 *
	 * At the moment (*), we have a sum of total content heights of percentage constraned rows in
	 * $ch variable, and a "free" (e.g. table height - sum of all non-percentage constrained heights) height
	 * in the $h variable. Obviously, percentage-constrained rows should be expanded to fill the free space
	 *
	 * On the other size, there should be a maximal value to expand them to; for example, if sum of
	 * percentage constraints is 33%, then all these rows should fill only 1/3 of the table height,
	 * whatever the content height of other rows is. In this case, other (non-constrained) rows
	 * should be expanded to fill space left.
	 *
	 * In the latter case, if there's no non-constrained rows, the additional space should be filled by
	 * "plain" rows without any constraints
	 *
	 * @param $minheight the minimal allowed height of the row; as we'll need to expand rows later
	 * and rows containing totally empty cells will have zero height
	 *
	 * @return array of row heights in media points
	 */
	function _row_heights( $minheight ) {
		$heights  = array();
		$cheights = array();
		$height   = $this->get_height();

		// Calculate "content" and "constrained" heights of table rows

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$heights[] = max( $minheight, $this->content[ $i ]->row_height() );

			// Apply row height constraint
			// we need to specify box which parent will serve as a base for height calculation;

			$hc         = $this->get_rhc( $i );
			$cheights[] = $hc->apply( $heights[ $i ], $this->content[ $i ], null );
		};

		// Collapse "constrained" heights of percentage-constrained rows, if they're
		// taking more that available space

		$flags = $this->get_non_percentage_constrained_height_flags();
		$h     = $height;
		$ch    = 0;
		for ( $i = 0; $i < count( $heights ); $i ++ ) {
			if ( $flags[ $i ] ) {
				$h -= $cheights[ $i ];
			} else {
				$ch += $cheights[ $i ];
			};
		};
		// (*) see note in the function description
		if ( $ch > 0 ) {
			$scale = $h / $ch;

			if ( $scale < 1 ) {
				for ( $i = 0; $i < count( $heights ); $i ++ ) {
					if ( ! $flags[ $i ] ) {
						$cheights[ $i ] *= $scale;
					};
				};
			};
		};

		// Expand non-constrained rows, if there's free space still

		$flags = $this->get_non_constrained_height_flags();
		$h     = $height;
		$ch    = 0;
		for ( $i = 0; $i < count( $cheights ); $i ++ ) {
			if ( ! $flags[ $i ] ) {
				$h -= $cheights[ $i ];
			} else {
				$ch += $cheights[ $i ];
			};
		};
		// (*) see note in the function description
		if ( $ch > 0 ) {
			$scale = $h / $ch;

			if ( $scale < 1 ) {
				for ( $i = 0; $i < count( $heights ); $i ++ ) {
					if ( $flags[ $i ] ) {
						$cheights[ $i ] *= $scale;
					};
				};
			};
		};

		// Expand percentage-constrained rows, if there's free space still

		$flags = $this->get_non_percentage_constrained_height_flags();
		$h     = $height;
		$ch    = 0;
		for ( $i = 0; $i < count( $cheights ); $i ++ ) {
			if ( $flags[ $i ] ) {
				$h -= $cheights[ $i ];
			} else {
				$ch += $cheights[ $i ];
			};
		};
		// (*) see note in the function description
		if ( $ch > 0 ) {
			$scale = $h / $ch;

			if ( $scale < 1 ) {
				for ( $i = 0; $i < count( $heights ); $i ++ ) {
					if ( ! $flags[ $i ] ) {
						$cheights[ $i ] *= $scale;
					};
				};
			};
		};

		// Get the actual row height
		for ( $i = 0; $i < count( $heights ); $i ++ ) {
			$heights[ $i ] = max( $heights[ $i ], $cheights[ $i ] );
		};

		return $heights;
	}

	function table_resize_rows( &$heights ) {
		$row_top = $this->get_top();

		$size = count( $heights );
		for ( $i = 0; $i < $size; $i ++ ) {
			$this->content[ $i ]->table_resize_row( $heights[ $i ], $row_top );
			$row_top -= $heights[ $i ];
		}

		// Set table height to sum of row heights
		$this->put_height( array_sum( $heights ) );
	}

	//   // Calculate given table row height
	//   //
	//   // @param  $index zero-based row index
	//   // @return value of row height (in media points)
	//   //
	//   function table_row_height($index) {
	//     // Select row
	//     $row =& $this->content[$index];

	//     // Calculate height of each cell contained in this row
	//     $height = 0;
	//     for ($i=0; $i<count($row->content); $i++) {
	//       if ($this->table_have_rowspan($i, $index) <= 1) {
	//         $height = max($height, $row->content[$i]->get_full_height());
	//       }
	//     }

	//     return $height;
	//   }

	//   function get_row_baseline($index) {
	//     // Get current row
	//     $row =& $this->content[$index];
	//     // Calculate maximal baseline for each cell contained
	//     $baseline = 0;
	//     for ($i = 0; $i < count($row->content); $i++) {
	//       // Cell baseline is the baseline of its first line box inside this cell
	//       if (count($row->content[$i]->content) > 0) {
	//         $baseline = max($baseline, $row->content[$i]->content[0]->baseline);
	//       };
	//     };
	//     return $baseline;
	//   }

	// Width constraints
	function get_cwc( $col ) {
		return $this->cwc[ $col ];
	}

	// Get height constraint for the given row
	//
	// @param $row number of row (zero-based)
	//
	// @return HCConstraint object
	//
	function get_rhc( $row ) {
		return $this->rhc[ $row ];
	}

	// Width calculation
	//
	// Note that if table have no width constraint AND some columns are percentage constrained,
	// then the width of the table can be determined based on the minimal column width;
	// e.g. if some column have minimal width of 10px and 10% width constraint,
	// then table will have minimal width of 100px. If there's several percentage-constrained columns,
	// then we choose from the generated values the maximal one
	//
	// Of course, all of the above can be applied ONLY to table without width constraint;
	// of theres any w.c. applied to the table, it will have greater than column constraints
	//
	// We must take constrained table width into account; if there's a width constraint,
	// then we must choose the maximal value between the constrained width and sum of minimal
	// columns widths - so, expanding the constrained width in case it is not enough to fit
	// the table contents
	//
	// @param $context referene to a flow context object
	// @return minimal box width (including the padding/margin/border width! NOT content width)
	//
	function get_min_width( &$context ) {
		$widths = $this->get_table_columns_min_widths( $context );
		$maxw   = $this->get_table_columns_max_widths( $context );

		// Expand some columns to fit colspanning cells
		$widths = $this->_table_apply_colspans( $widths, $context, 'get_min_width', $widths, $maxw );

		$width      = array_sum( $widths );
		$base_width = $width;

		$wc = $this->get_css_property( CSS_WIDTH );
		if ( ! $wc->isNull() ) {
			// Check if constrained table width should be expanded to fit the table contents
			//
			$width = max( $width, $wc->apply( 0, $this->parent->get_available_width( $context ) ) );
		} else {
			// Now check if there's any percentage column width constraints (note that
			// if we've get here, than the table width is not constrained). Calculate
			// the table width basing on these values and select the maximal value
			//
			for ( $i = 0; $i < $this->cols_count(); $i ++ ) {
				$cwc = $this->get_cwc( $i );

				$width = max( $width,
					min( $cwc->apply_inverse( $widths[ $i ], $base_width ),
						$this->parent->get_available_width( $context ) - $this->_get_hor_extra() ) );
			};
		};

		return $width + $this->_get_hor_extra();
	}

	function get_min_width_natural( &$context ) {
		return $this->get_min_width( $context );
	}

	function get_max_width( &$context ) {
		$wc = $this->get_css_property( CSS_WIDTH );

		if ( $wc->isConstant() ) {
			return $wc->apply( 0, $this->parent->get_available_width( $context ) );
		} else {
			$widths = $this->get_table_columns_max_widths( $context );
			$minwc  = $this->get_table_columns_min_widths( $context );

			$widths = $this->_table_apply_colspans( $widths, $context, 'get_max_width', $minwc, $widths );

			$width      = array_sum( $widths );
			$base_width = $width;

			// Now check if there's any percentage column width constraints (note that
			// if we've get here, than the table width is not constrained). Calculate
			// the table width based on these values and select the maximal value
			//
			for ( $i = 0; $i < $this->cols_count(); $i ++ ) {
				$cwc = $this->get_cwc( $i );

				$width = max( $width,
					min( $cwc->apply_inverse( $widths[ $i ], $base_width ),
						$this->parent->get_available_width( $context ) - $this->_get_hor_extra() ) );
			};

			return $width + $this->_get_hor_extra();
		}
	}

	function get_max_width_natural( &$context ) {
		return $this->get_max_width( $context );
	}

	function get_width() {
		$wc  = $this->get_css_property( CSS_WIDTH );
		$pwc = $this->parent->get_css_property( CSS_WIDTH );

		if ( ! $this->parent->isCell() ||
		     ! $pwc->isNull() ||
		     ! $wc->isFraction() ) {
			$width = $wc->apply( $this->width, $this->parent->width );
		} else {
			$width = $this->width;
		};

		// Note that table 'padding' property for is handled differently
		// by different browsers; for example, IE 6 ignores it completely,
		// while FF 1.5 subtracts horizontal padding value from constrained
		// table width. We emulate FF behavior here
		return $width -
		       $this->get_padding_left() -
		       $this->get_padding_right();
	}

	function table_column_widths( &$context ) {
		$table_layout = $this->get_css_property( CSS_TABLE_LAYOUT );
		switch ( $table_layout ) {
			case TABLE_LAYOUT_FIXED:
				//       require_once(HTML2PS_DIR.'strategy.table.layout.fixed.php');
				//       $strategy =& new StrategyTableLayoutFixed();
				//       break;
			case TABLE_LAYOUT_AUTO:
			default:
				require_once( HTML2PS_DIR . 'strategy.table.layout.auto.php' );
				$strategy =& new StrategyTableLayoutAuto();
				break;
		};

		return $strategy->apply( $this, $context );
	}

	// Extend some columns widths (if needed) to fit colspanned cell contents
	//
	function _table_apply_colspans( $widths, &$context, $width_fun, $minwc, $maxwc ) {
		$colspans = $this->get_colspans();

		foreach ( $colspans as $colspan ) {
			$cell = $this->content[ $colspan->row ]->content[ $colspan->column ];

			// apply colspans to the corresponsing colspanned-cell dimension
			//
			$cell_width = $cell->$width_fun( $context );

			// Apply cell constraint width, if any AND if table width is constrained
			// if table width is not constrained, we should not do this, as current value
			// of $table->get_width is maximal width (parent width), not the actual
			// width of the table
			$wc = $this->get_css_property( CSS_WIDTH );
			if ( ! $wc->isNull() ) {
				$cell_wc    = $cell->get_css_property( CSS_WIDTH );
				$cell_width = $cell_wc->apply( $cell_width, $this->get_width() );

				// On the other side, constrained with cannot be less than cell minimal width
				$cell_width = max( $cell_width, $cell->get_min_width( $context ) );
			};

			// now select the pre-calculated widths of columns covered by this cell
			// select the list of resizable columns covered by this cell
			$spanned_widths    = array();
			$spanned_resizable = array();

			for ( $i = $colspan->column; $i < $colspan->column + $colspan->size; $i ++ ) {
				$spanned_widths[]    = $widths[ $i ];
				$spanned_resizable[] = ( $minwc[ $i ] != $maxwc[ $i ] );
			}

			// Sometimes we may encounter the colspan over the empty columns (I mean ALL columns are empty); in this case
			// we need to make these columns reizable in order to fit colspanned cell contents
			//
			if ( array_sum( $spanned_widths ) == 0 ) {
				for ( $i = 0; $i < count( $spanned_widths ); $i ++ ) {
					$spanned_widths[ $i ]    = EPSILON;
					$spanned_resizable[ $i ] = true;
				};
			};

			// The same problem may arise when all colspanned columns are not resizable; in this case we'll force all
			// of them to be resized
			$any_resizable = false;
			for ( $i = 0; $i < count( $spanned_widths ); $i ++ ) {
				$any_resizable |= $spanned_resizable[ $i ];
			};
			if ( ! $any_resizable ) {
				for ( $i = 0; $i < count( $spanned_widths ); $i ++ ) {
					$spanned_resizable[ $i ] = true;
				};
			}

			// Expand resizable columns
			//
			$spanned_widths = expand_to_with_flags( $cell_width, $spanned_widths, $spanned_resizable );

			// Store modified widths
			array_splice( $widths, $colspan->column, $colspan->size, $spanned_widths );
		};

		return $widths;
	}

	function get_table_columns_max_widths( &$context ) {
		$widths = array();

		for ( $i = 0; $i < count( $this->content[0]->content ); $i ++ ) {
			$widths[] = 0;
		};

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			// Calculate column widths for a current row
			$roww = $this->content[ $i ]->get_table_columns_max_widths( $context );
			for ( $j = 0; $j < count( $roww ); $j ++ ) {
				//        $widths[$j] = max($roww[$j], isset($widths[$j]) ? $widths[$j] : 0);
				$widths[ $j ] = max( $roww[ $j ], $widths[ $j ] );
			}
		}

		// Use column width constraints - column should not be wider its constrained width
		for ( $i = 0; $i < count( $widths ); $i ++ ) {
			$cwc = $this->get_cwc( $i );

			// Newertheless, percentage constraints should not be applied IF table
			// does not have constrained width
			//
			if ( ! is_a( $cwc, "wcfraction" ) ) {
				$widths[ $i ] = $cwc->apply( $widths[ $i ], $this->get_width() );
			};
		}

		// TODO: colspans

		return $widths;
	}

	/**
	 * Optimization: calculated widths are cached
	 */
	function get_table_columns_min_widths( &$context ) {
		if ( ! is_null( $this->_cached_min_widths ) ) {
			return $this->_cached_min_widths;
		};

		$widths = array();

		for ( $i = 0; $i < count( $this->content[0]->content ); $i ++ ) {
			$widths[] = 0;
		};

		$content_size = count( $this->content );
		for ( $i = 0; $i < $content_size; $i ++ ) {
			// Calculate column widths for a current row
			$roww = $this->content[ $i ]->get_table_columns_min_widths( $context );

			$row_size = count( $roww );
			for ( $j = 0; $j < $row_size; $j ++ ) {
				$widths[ $j ] = max( $roww[ $j ], $widths[ $j ] );
			}
		}

		$this->_cached_min_widths = $widths;

		return $widths;
	}

	function get_colspans() {
		$colspans = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$colspans = array_merge( $colspans, $this->content[ $i ]->get_colspans( $i ) );
		};

		return $colspans;
	}

	function check_constrained_colspan( $col ) {
		for ( $i = 0; $i < $this->rows_count(); $i ++ ) {
			$cell    =& $this->cell( $i, $col );
			$cell_wc = $cell->get_css_property( CSS_WIDTH );

			if ( $cell->colspan > 1 &&
			     ! $cell_wc->isNull() ) {
				return true;
			};
		};

		return false;
	}

	// Tries to change minimal constrained width so that columns will fit into the given
	// table width
	//
	// Note that every width constraint have its own priority; first, the unconstrained columns are collapsed,
	// then - percentage constrained and after all - columns having fixed width
	//
	// @param $width table width
	// @param $minw array of unconstrained minimal widths
	// @param $minwc array of constrained minimal widths
	// @return list of normalized minimal constrained widths
	//
	function normalize_min_widths( $width, $minw, $minwc ) {
		// Check if sum of constrained widths is too big
		// Note that we compare sum of constrained width with the MAXIMAL value of table width and
		// sum of uncostrained minimal width; it will prevent from unneeded collapsing of table cells
		// if table content will expand its width anyway
		//
		$twidth = max( $width, array_sum( $minw ) );

		// compare with sum of minimal constrained widths
		//
		if ( array_sum( $minwc ) > $twidth ) {
			$delta = array_sum( $minwc ) - $twidth;

			// Calculate the amount of difference between minimal and constrained minimal width for each columns
			$diff = array();
			for ( $i = 0; $i < count( $minw ); $i ++ ) {
				// Do no modify width of columns taking part in constrained colspans
				if ( ! $this->check_constrained_colspan( $i ) ) {
					$diff[ $i ] = $minwc[ $i ] - $minw[ $i ];
				} else {
					$diff[ $i ] = 0;
				};
			}

			// If no difference is found, we can collapse no columns
			// otherwise scale some columns...
			$cwdelta = array_sum( $diff );

			if ( $cwdelta > 0 ) {
				for ( $i = 0; $i < count( $minw ); $i ++ ) {
					//          $minwc[$i] = max(0,- ($minwc[$i] - $minw[$i]) / $cwdelta * $delta + $minwc[$i]);
					$minwc[ $i ] = max( 0, - $diff[ $i ] / $cwdelta * $delta + $minwc[ $i ] );
				}
			}
		}

		return $minwc;
	}

	function table_have_colspan( $x, $y ) {
		return $this->content[ $y ]->content[ $x ]->colspan;
	}

	// Flow-control
	function reflow( &$parent, &$context ) {
		if ( $this->get_css_property( CSS_FLOAT ) === FLOAT_NONE ) {
			$status = $this->reflow_static_normal( $parent, $context );
		} else {
			$status = $this->reflow_static_float( $parent, $context );
		}

		return $status;
	}

	function reflow_absolute( &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// Calculate margin values if they have been set as a percentage
		$this->_calc_percentage_margins( $parent );

		// Calculate width value if it had been set as a percentage
		$this->_calc_percentage_width( $parent, $context );

		$wc = $this->get_css_property( CSS_WIDTH );
		if ( ! $wc->isNull() ) {
			$col_width = $this->get_table_columns_min_widths( $context );
			$maxw      = $this->get_table_columns_max_widths( $context );
			$col_width = $this->_table_apply_colspans( $col_width, $context, 'get_min_width', $col_width, $maxw );

			if ( array_sum( $col_width ) > $this->get_width() ) {
				$wc = new WCConstant( array_sum( $col_width ) );
			};
		};

		$position_strategy =& new StrategyPositionAbsolute();
		$position_strategy->apply( $this );

		$this->reflow_content( $context );
	}

	/**
	 * TODO: unlike block elements, table unconstrained width is determined
	 * with its content, so it may be smaller than parent available width!
	 */
	function reflow_static_normal( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		// Calculate margin values if they have been set as a percentage
		$this->_calc_percentage_margins( $parent );

		// Calculate width value if it had been set as a percentage
		$this->_calc_percentage_width( $parent, $context );

		$wc = $this->get_css_property( CSS_WIDTH );
		if ( ! $wc->isNull() ) {
			$col_width = $this->get_table_columns_min_widths( $context );
			$maxw      = $this->get_table_columns_max_widths( $context );
			$col_width = $this->_table_apply_colspans( $col_width, $context, 'get_min_width', $col_width, $maxw );

			if ( array_sum( $col_width ) > $this->get_width() ) {
				$wc = new WCConstant( array_sum( $col_width ) );
			};
		};

		// As table width can be deterimined by its contents, we may calculate auto values
		// only AFTER the contents have been reflown; thus, we'll offset the table
		// as a whole by a value of left margin AFTER the content reflow

		// Do margin collapsing
		$y = $this->collapse_margin( $parent, $context );

		// At this moment we have top parent/child collapsed margin at the top of context object
		// margin stack

		$y = $this->apply_clear( $y, $context );

		// Store calculated Y coordinate as current Y in the parent box
		$parent->_current_y = $y;

		// Terminate current parent line-box
		$parent->close_line( $context );

		// And add current box to the parent's line-box (alone)
		$parent->append_line( $this );

		// Determine upper-left _content_ corner position of current box
		// Also see note above regarding margins
		$border  = $this->get_css_property( CSS_BORDER );
		$padding = $this->get_css_property( CSS_PADDING );

		$this->put_left( $parent->_current_x +
		                 $border->left->get_width() +
		                 $padding->left->value );

		// Note that top margin already used above during maring collapsing
		$this->put_top( $parent->_current_y - $border->top->get_width() - $padding->top->value );

		/**
		 * By default, child block box will fill all available parent width;
		 * note that actual width will be smaller because of non-zero padding, border and margins
		 */
		$this->put_full_width( $parent->get_available_width( $context ) );

		// Reflow contents
		$this->reflow_content( $context );

		// Update the collapsed margin value with current box bottom margin
		$margin = $this->get_css_property( CSS_MARGIN );

		$context->pop_collapsed_margin();
		$context->pop_collapsed_margin();
		$context->push_collapsed_margin( $margin->bottom->value );

		// Calculate margins and/or width is 'auto' values have been specified
		$this->_calc_auto_width_margins( $parent );
		$this->offset( $margin->left->value, 0 );

		// Extend parent's height to fit current box
		$parent->extend_height( $this->get_bottom_margin() );
		// Terminate parent's line box
		$parent->close_line( $context );
	}

	// Get a list of boolean values indicating if table rows are height constrained
	//
	// @return array containing 'true' value at index I if I-th row is not height-constrained
	// and 'false' otherwise
	//
	function get_non_constrained_flags() {
		$flags = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$hc          = $this->get_rhc( $i );
			$flags[ $i ] =
				( is_null( $hc->constant ) ) &&
				( is_null( $hc->min ) ) &&
				( is_null( $hc->max ) );
		};

		return $flags;
	}

	// Get a list of boolean values indicating if table rows are height constrained using percentage values
	//
	// @return array containing 'true' value at index I if I-th row is not height-constrained
	// and 'false' otherwise
	//
	function get_non_percentage_constrained_height_flags() {
		$flags = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$hc          = $this->get_rhc( $i );
			$flags[ $i ] =
				( ! is_null( $hc->constant ) ? ! $hc->constant[1] : true ) &&
				( ! is_null( $hc->min ) ? ! $hc->min[1] : true ) &&
				( ! is_null( $hc->max ) ? ! $hc->max[1] : true );
		};

		return $flags;
	}

	function get_non_constrained_height_flags() {
		$flags = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$hc = $this->get_rhc( $i );

			$flags[ $i ] = $hc->is_null();
		};

		return $flags;
	}

	// Get a list of boolean values indicating if table columns are height constrained
	//
	// @return array containing 'true' value at index I if I-th columns is not width-constrained
	// and 'false' otherwise
	//
	function get_non_constrained_width_flags() {
		$flags = array();

		for ( $i = 0; $i < $this->cols_count(); $i ++ ) {
			$wc          = $this->get_cwc( $i );
			$flags[ $i ] = is_a( $wc, "wcnone" );
		};

		return $flags;
	}

	function get_non_constant_constrained_width_flags() {
		$flags = array();

		for ( $i = 0; $i < $this->cols_count(); $i ++ ) {
			$wc          = $this->get_cwc( $i );
			$flags[ $i ] = ! is_a( $wc, "WCConstant" );
		};

		return $flags;
	}

	function check_if_column_image_constrained( $col ) {
		for ( $i = 0; $i < $this->rows_count(); $i ++ ) {
			$cell =& $this->cell( $i, $col );
			for ( $j = 0; $j < count( $cell->content ); $j ++ ) {
				if ( ! $cell->content[ $j ]->is_null() &&
				     ! is_a( $cell->content[ $j ], "GenericImgBox" ) ) {
					return false;
				};
			};
		};

		return true;
	}

	function get_non_image_constrained_width_flags() {
		$flags = array();

		for ( $i = 0; $i < $this->cols_count(); $i ++ ) {
			$flags[ $i ] = ! $this->check_if_column_image_constrained( $i );
		};

		return $flags;
	}

	// Get a list of boolean values indicating if table rows are NOT constant constrained
	//
	// @return array containing 'true' value at index I if I-th row is height-constrained
	// and 'false' otherwise
	//
	function get_non_constant_constrained_flags() {
		$flags = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			$hc          = $this->get_rhc( $i );
			$flags[ $i ] = is_null( $hc->constant );
		};

		return $flags;
	}

	function reflow_content( &$context ) {
		// Reflow content

		// Reset current Y value
		//
		$this->_current_y = $this->get_top();

		// Determine the base table width
		// if width constraint exists, the actual table width will not be changed anyway
		//
		$this->put_width( min( $this->get_max_width( $context ), $this->get_width() ) );

		// Calculate widths of table columns
		$columns = $this->table_column_widths( $context );

		// Collapse table to minimum width (if width is not constrained)
		$real_width = array_sum( $columns );
		$this->put_width( $real_width );

		// If width is constrained, and is less than calculated, update the width constraint
		//
		//     if ($this->get_width() < $real_width) {
		//       // $this->put_width_constraint(new WCConstant($real_width));
		//     };

		// Flow cells horizontally in each table row
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			// Row flow started
			// Reset current X coordinate to the far left of the table
			$this->_current_x = $this->get_left();

			// Flow each cell in the row
			$span = 0;
			for ( $j = 0; $j < count( $this->content[ $i ]->content ); $j ++ ) {
				// Skip cells covered by colspans (fake cells, anyway)
				if ( $span == 0 ) {
					// Flow current cell
					// Any colspans here?
					$span = $this->table_have_colspan( $j, $i );

					// Get sum of width for the current cell (or several cells in colspan)
					// In most cases, $span == 1 here (just a single cell)
					$cw = array_sum( array_slice( $columns, $j, $span ) );

					// store calculated width of the current cell
					$cell =& $this->content[ $i ]->content[ $j ];
					$cell->put_full_width( $cw );
					$cell->setCSSProperty( CSS_WIDTH,
						new WCConstant( $cw -
						                $cell->_get_hor_extra() ) );

					// TODO: check for rowspans

					// Flow cell
					$this->content[ $i ]->content[ $j ]->reflow( $this, $context );

					// Offset current X value by the cell width
					$this->_current_x += $cw;
				};

				// Current cell have been processed or skipped
				$span = max( 0, $span - 1 );
			}

			// calculate row height and do vertical align
			//      $this->table_fit_row($i);

			// row height calculation offset current Y coordinate by the row height calculated
			//      $this->_current_y -= $this->table_row_height($i);
			$this->_current_y -= $this->content[ $i ]->row_height();
		}

		// Calculate (and possibly adjust height of table rows)
		$heights = $this->_row_heights( 0.1 );

		// adjust row heights to fit cells spanning several rows
		foreach ( $this->get_rowspans() as $rowspan ) {
			// Get height of the cell
			$cell_height = $this->content[ $rowspan->row ]->content[ $rowspan->column ]->get_full_height();

			// Get calculated height of the spanned-over rows
			$cell_row_heights = array_slice( $heights, $rowspan->row, $rowspan->size );

			// Get list of non-constrained columns
			$flags = array_slice( $this->get_non_constrained_flags(), $rowspan->row, $rowspan->size );

			// Expand row heights (only for non-constrained columns)
			$new_heights = expand_to_with_flags( $cell_height,
				$cell_row_heights,
				$flags );

			// Check if rows could not be expanded
			//      if (array_sum($new_heights) < $cell_height-1) {
			if ( array_sum( $new_heights ) < $cell_height - EPSILON ) {
				// Get list of non-constant-constrained columns
				$flags = array_slice( $this->get_non_constant_constrained_flags(), $rowspan->row, $rowspan->size );

				// use non-constant-constrained rows
				$new_heights = expand_to_with_flags( $cell_height,
					$cell_row_heights,
					$flags );
			};

			// Update the rows heights
			array_splice( $heights,
				$rowspan->row,
				$rowspan->size,
				$new_heights );
		}

		// Now expand rows to full table height
		$table_height = max( $this->get_height(), array_sum( $heights ) );

		// Get list of non-constrained columns
		$flags = $this->get_non_constrained_height_flags();

		// Expand row heights (only for non-constrained columns)
		$heights = expand_to_with_flags( $table_height,
			$heights,
			$flags );

		// Check if rows could not be expanded
		if ( array_sum( $heights ) < $table_height - EPSILON ) {
			// Get list of non-constant-constrained columns
			$flags = $this->get_non_constant_constrained_flags();

			// use non-constant-constrained rows
			$heights = expand_to_with_flags( $table_height,
				$heights,
				$flags );
		};

		// Now we calculated row heights, time to actually resize them
		$this->table_resize_rows( $heights );

		// Update size of cells spanning several rows
		$this->table_fit_rowspans( $heights );

		// Expand total table height, if needed
		$total_height = array_sum( $heights );
		if ( $total_height > $this->get_height() ) {
			$hc = new HCConstraint( array( $total_height, false ),
				array( $total_height, false ),
				array( $total_height, false ) );
			$this->put_height_constraint( $hc );
		};
	}

	function isBlockLevel() {
		return true;
	}
}

// $Header: /cvsroot/html2ps/box.table.cell.php,v 1.40 2007/01/24 18:55:45 Konstantin Exp $

class TableCellBox extends GenericContainerBox {
	var $colspan;
	var $rowspan;
	var $column;

	var $_suppress_first;
	var $_suppress_last;

	function TableCellBox() {
		// Call parent constructor
		$this->GenericContainerBox();

		$this->_suppress_first = false;
		$this->_suppress_last  = false;

		$this->colspan = 1;
		$this->rowspan = 1;

		// This value will be overwritten in table 'normalize_parent' method
		//
		$this->column = 0;
		$this->row    = 0;
	}

	function get_min_width( &$context ) {
		if ( isset( $this->_cache[ CACHE_MIN_WIDTH ] ) ) {
			return $this->_cache[ CACHE_MIN_WIDTH ];
		};

		$content_size = count( $this->content );

		/**
		 * If box does not have any context, its minimal width is determined by extra horizontal space:
		 * padding, border width and margins
		 */
		if ( $content_size == 0 ) {
			$min_width                       = $this->_get_hor_extra();
			$this->_cache[ CACHE_MIN_WIDTH ] = $min_width;

			return $min_width;
		};

		/**
		 * If we're in 'nowrap' mode, minimal and maximal width will be equal
		 */
		$white_space   = $this->get_css_property( CSS_WHITE_SPACE );
		$pseudo_nowrap = $this->get_css_property( CSS_HTML2PS_NOWRAP );
		if ( $white_space == WHITESPACE_NOWRAP ||
		     $pseudo_nowrap == NOWRAP_NOWRAP ) {
			$min_width                       = $this->get_min_nowrap_width( $context );
			$this->_cache[ CACHE_MIN_WIDTH ] = $min_width;

			return $min_width;
		}

		/**
		 * We need to add text indent size to the with of the first item
		 */
		$start_index = 0;
		while ( $start_index < $content_size &&
		        $this->content[ $start_index ]->out_of_flow() ) {
			$start_index ++;
		};

		if ( $start_index < $content_size ) {
			$ti   = $this->get_css_property( CSS_TEXT_INDENT );
			$minw =
				$ti->calculate( $this ) +
				$this->content[ $start_index ]->get_min_width( $context );
		} else {
			$minw = 0;
		};

		for ( $i = $start_index; $i < $content_size; $i ++ ) {
			$item =& $this->content[ $i ];
			if ( ! $item->out_of_flow() ) {
				$minw = max( $minw, $item->get_min_width_natural( $context ) );
			};
		}

		/**
		 * Apply width constraint to min width. Return maximal value
		 */
		$wc                              = $this->get_css_property( CSS_WIDTH );
		$min_width                       = max( $minw,
				$wc->apply( $minw, $this->parent->get_width() ) ) + $this->_get_hor_extra();
		$this->_cache[ CACHE_MIN_WIDTH ] = $min_width;

		return $min_width;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array( CSS_BORDER_COLLAPSE ) );

		$this->_readCSSLengths( $state,
			array(
				CSS_HTML2PS_CELLPADDING,
				CSS_HTML2PS_CELLSPACING,
				CSS_HTML2PS_TABLE_BORDER
			) );
	}

	function isCell() {
		return true;
	}

	function is_fake() {
		return false;
	}

	function &create( &$root, &$pipeline ) {
		$css_state = $pipeline->get_current_css_state();

		$box =& new TableCellBox();
		$box->readCSS( $css_state );

		// Use cellspacing / cellpadding values from the containing table
		$cellspacing = $box->get_css_property( CSS_HTML2PS_CELLSPACING );
		$cellpadding = $box->get_css_property( CSS_HTML2PS_CELLPADDING );

		// FIXME: I'll need to resolve that issue with COLLAPSING border model. Now borders
		// are rendered separated

		// if not border set explicitly, inherit value set via border attribute of TABLE tag
		$border_handler = CSS::get_handler( CSS_BORDER );
		if ( $border_handler->is_default( $box->get_css_property( CSS_BORDER ) ) ) {
			$table_border = $box->get_css_property( CSS_HTML2PS_TABLE_BORDER );
			$box->setCSSProperty( CSS_BORDER, $table_border );
		};

		$margin =& CSS::get_handler( CSS_MARGIN );
		$box->setCSSProperty( CSS_MARGIN, $margin->default_value() );

		$h_padding =& CSS::get_handler( CSS_PADDING );
		$padding   = $box->get_css_property( CSS_PADDING );

		if ( $h_padding->is_default( $padding ) ) {
			$padding->left->_units     = $cellpadding;
			$padding->left->auto       = false;
			$padding->left->percentage = null;

			$padding->right->_units     = $cellpadding;
			$padding->right->auto       = false;
			$padding->right->percentage = null;

			$padding->top->_units     = $cellpadding;
			$padding->top->auto       = false;
			$padding->top->percentage = null;

			$padding->bottom->_units     = $cellpadding;
			$padding->bottom->auto       = false;
			$padding->bottom->percentage = null;

			/**
			 * Note that cellpadding/cellspacing values never use font-size based units
			 * ('em' and 'ex'), so we may pass 0 as base_font_size parameter - it
			 * will not be used anyway
			 */
			$padding->units2pt( 0 );

			$box->setCSSProperty( CSS_PADDING, $padding );
		};

		if ( $box->get_css_property( CSS_BORDER_COLLAPSE ) != BORDER_COLLAPSE ) {
			$margin_value = $box->get_css_property( CSS_MARGIN );
			if ( $margin->is_default( $margin_value ) ) {
				$length = $cellspacing->copy();
				$length->scale( 0.5 );

				$margin_value->left->_units     = $length;
				$margin_value->left->auto       = false;
				$margin_value->left->percentage = null;

				$margin_value->right->_units     = $length;
				$margin_value->right->auto       = false;
				$margin_value->right->percentage = null;

				$margin_value->top->_units     = $length;
				$margin_value->top->auto       = false;
				$margin_value->top->percentage = null;

				$margin_value->bottom->_units     = $length;
				$margin_value->bottom->auto       = false;
				$margin_value->bottom->percentage = null;

				/**
				 * Note that cellpadding/cellspacing values never use font-size based units
				 * ('em' and 'ex'), so we may pass 0 as base_font_size parameter - it
				 * will not be used anyway
				 */
				$margin_value->units2pt( 0 );

				$box->setCSSProperty( CSS_MARGIN, $margin_value );
			}
		};

		// Save colspan and rowspan information
		$box->colspan = max( 1, (int) $root->get_attribute( 'colspan' ) );
		$box->rowspan = max( 1, (int) $root->get_attribute( 'rowspan' ) );

		// Create content

		// 'vertical-align' CSS value is not inherited from the table cells
		$css_state->pushState();

		$handler =& CSS::get_handler( CSS_VERTICAL_ALIGN );
		$handler->replace( $handler->default_value(),
			$css_state );

		$box->create_content( $root, $pipeline );

		global $g_config;
		if ( $g_config['mode'] == "quirks" ) {
			// QUIRKS MODE:
			// H1-H6 and P elements should have their top/bottom margin suppressed if they occur as the first/last table cell child
			// correspondingly; note that we cannot do it usung CSS rules, as there's no selectors for the last child.
			//
			$child = $root->first_child();
			if ( $child ) {
				while ( $child && $child->node_type() != XML_ELEMENT_NODE ) {
					$child = $child->next_sibling();
				};

				if ( $child ) {
					if ( array_search( strtolower( $child->tagname() ), array( "h1", "h2", "h3", "h4", "h5", "h6", "p" ) ) ) {
						$box->_suppress_first = true;
					}
				};
			};

			$child = $root->last_child();
			if ( $child ) {
				while ( $child && $child->node_type() != XML_ELEMENT_NODE ) {
					$child = $child->previous_sibling();
				};

				if ( $child ) {
					if ( array_search( strtolower( $child->tagname() ), array( "h1", "h2", "h3", "h4", "h5", "h6", "p" ) ) ) {
						$box->_suppress_last = true;
					}
				};
			};
		};

		// pop the default vertical-align value
		$css_state->popState();

		return $box;
	}

	// Inherited from GenericFormattedBox

	function get_cell_baseline() {
		$content = $this->get_first_data();
		if ( is_null( $content ) ) {
			return 0;
		}

		return $content->baseline;
	}

	// Flow-control
	function reflow( &$parent, &$context ) {
		GenericFormattedBox::reflow( $parent, $context );

		global $g_config;
		$size = count( $this->content );
		if ( $g_config['mode'] == "quirks" && $size > 0 ) {
			// QUIRKS MODE:
			// H1-H6 and P elements should have their top/bottom margin suppressed if they occur as the first/last table cell child
			// correspondingly; note that we cannot do it usung CSS rules, as there's no selectors for the last child.
			//

			$first =& $this->get_first();
			if ( ! is_null( $first ) && $this->_suppress_first && $first->isBlockLevel() ) {
				$first->margin->top->value      = 0;
				$first->margin->top->percentage = null;
			};

			$last =& $this->get_last();
			if ( ! is_null( $last ) && $this->_suppress_last && $last->isBlockLevel() ) {
				$last->margin->bottom->value      = 0;
				$last->margin->bottom->percentage = null;
			};
		};

		// Determine upper-left _content_ corner position of current box
		$this->put_left( $parent->_current_x + $this->get_extra_left() );

		// NOTE: Table cell margin is used as a cell-spacing value
		$border  = $this->get_css_property( CSS_BORDER );
		$padding = $this->get_css_property( CSS_PADDING );
		$this->put_top( $parent->_current_y -
		                $border->top->get_width() -
		                $padding->top->value );

		// CSS 2.1:
		// Floats, absolutely positioned elements, inline-blocks, table-cells, and elements with 'overflow' other than
		// 'visible' establish new block formatting contexts.
		$context->push();
		$context->push_container_uid( $this->uid );

		// Reflow cell content
		$this->reflow_content( $context );

		// Extend the table cell height to fit all contained floats
		//
		// Determine the bottom edge corrdinate of the bottommost float
		//
		$float_bottom = $context->float_bottom();

		if ( ! is_null( $float_bottom ) ) {
			$this->extend_height( $float_bottom );
		};

		// Restore old context
		$context->pop_container_uid();
		$context->pop();
	}
}


class FakeTableCellBox extends TableCellBox {
	var $colspan;
	var $rowspan;

	function create( &$pipeline ) {
		$box =& new FakeTableCellBox;

		$css_state =& $pipeline->get_current_css_state();
		$css_state->pushDefaultState();

		$box->readCSS( $css_state );

		$nullbox =& new NullBox;
		$nullbox->readCSS( $css_state );
		$box->add_child( $nullbox );

		$box->readCSS( $css_state );

		$css_state->popState();

		return $box;
	}

	function FakeTableCellBox() {
		// Required to reset any constraints initiated by CSS properties
		$this->colspan = 1;
		$this->rowspan = 1;
		$this->GenericContainerBox();

		$this->setCSSProperty( CSS_DISPLAY, 'table-cell' );
		$this->setCSSProperty( CSS_VERTICAL_ALIGN, VA_MIDDLE );
	}

	function show( &$viewport ) {
		return true;
	}

	function is_fake() {
		return true;
	}

	function get_width_constraint() {
		return new WCNone();
	}

	function get_height_constraint() {
		return new HCConstraint( null, null, null );
	}

	function get_height() {
		return 0;
	}

	function get_top_margin() {
		return 0;
	}

	function get_full_height() {
		return 0;
	}

	function get_max_width() {
		return 0;
	}

	function get_min_width() {
		return 0;
	}
}


// $Header: /cvsroot/html2ps/box.table.row.php,v 1.29 2007/01/24 18:55:45 Konstantin Exp $

class TableRowBox extends GenericContainerBox {
	var $rows;
	var $colspans;
	var $rowspans;

	function &create( &$root, &$pipeline ) {
		$box =& new TableRowBox();
		$box->readCSS( $pipeline->get_current_css_state() );

		$child = $root->first_child();
		while ( $child ) {
			$child_box =& create_pdf_box( $child, $pipeline );
			$box->add_child( $child_box );

			$child = $child->next_sibling();
		};

		return $box;
	}

	function add_child( &$item ) {
		if ( $item->isCell() ) {
			GenericContainerBox::add_child( $item );
		};
	}

	function TableRowBox() {
		// Call parent constructor
		$this->GenericContainerBox();
	}

	// Normalize colspans by adding fake cells after the "colspanned" cell
	// Say, if we've got the following row:
	// <tr><td colspan="3">1</td><td>2</td></tr>
	// we should get row containing four cells after normalization;
	// first contains "1"
	// second and third are completely empty
	// fourth contains "2"
	function normalize( &$pipeline ) {
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			for ( $j = 1; $j < $this->content[ $i ]->colspan; $j ++ ) {
				$this->add_fake_cell_after( $i, $pipeline );
				// Note that add_fake_cell_after will increase the length of current row by one cell,
				// so we must increase $size variable
				$size ++;
			};
		};
	}

	function add_fake_cell_after( $index, &$pipeline ) {
		array_splice( $this->content, $index + 1, 0, array( FakeTableCellBox::create( $pipeline ) ) );
	}

	function add_fake_cell_before( $index, &$pipeline ) {
		array_splice( $this->content, $index, 0, array( FakeTableCellBox::create( $pipeline ) ) );
	}

	function append_fake_cell( &$pipeline ) {
		$this->content[] = FakeTableCellBox::create( $pipeline );
	}

	// Table specific

	function table_resize_row( $height, $top ) {
		// Do cell vertical-align
		// Calculate row baseline

		$baseline = $this->get_row_baseline();

		// Process cells contained in current row
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			$cell =& $this->content[ $i ];

			// Offset cell if needed
			$cell->offset( 0,
				$top -
				$cell->get_top_margin() );

			// Vertical-align cell (do not apply to rowspans)
			if ( $cell->rowspan == 1 ) {
				$va     = $cell->get_css_property( CSS_VERTICAL_ALIGN );
				$va_fun = CSSVerticalAlign::value2pdf( $va );
				$va_fun->apply_cell( $cell, $height, $baseline );

				// Expand cell to full row height
				$cell->put_full_height( $height );
			}
		}
	}

	function get_row_baseline() {
		$baseline = 0;
		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			$cell = $this->content[ $i ];
			if ( $cell->rowspan == 1 ) {
				$baseline = max( $baseline, $cell->get_cell_baseline() );
			};
		}

		return $baseline;
	}

	function get_colspans( $row_index ) {
		$colspans = array();

		for ( $i = 0, $size = count( $this->content ); $i < $size; $i ++ ) {
			// Check if current colspan will run off the right table edge
			if ( $this->content[ $i ]->colspan > 1 ) {
				$colspan         = new CellSpan;
				$colspan->row    = $row_index;
				$colspan->column = $i;
				$colspan->size   = $this->content[ $i ]->colspan;

				$colspans[] = $colspan;
			}
		}

		return $colspans;
	}

	function get_rowspans( $row_index ) {
		$spans = array();

		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			if ( $this->content[ $i ]->rowspan > 1 ) {
				$rowspan         = new CellSpan;
				$rowspan->row    = $row_index;
				$rowspan->column = $i;
				$rowspan->size   = $this->content[ $i ]->rowspan;
				$spans[]         = $rowspan;
			}
		}

		return $spans;
	}

	// Column widths
	function get_table_columns_max_widths( &$context ) {
		$widths = array();
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			// For now, colspans are treated as zero-width; they affect
			// column widths only in parent *_fit function
			if ( $this->content[ $i ]->colspan > 1 ) {
				$widths[] = 0;
			} else {
				$widths[] = $this->content[ $i ]->get_max_width( $context );
			}
		}

		return $widths;
	}

	function get_table_columns_min_widths( &$context ) {
		$widths = array();
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			// For now, colspans are treated as zero-width; they affect
			// column widths only in parent *_fit function
			if ( $this->content[ $i ]->colspan > 1 ) {
				$widths[] = 0;
			} else {
				$widths[] = $this->content[ $i ]->get_min_width( $context );
			};
		}

		return $widths;
	}

	function row_height() {
		// Calculate height of each cell contained in this row
		$height = 0;
		for ( $i = 0; $i < count( $this->content ); $i ++ ) {
			if ( $this->content[ $i ]->rowspan <= 1 ) {
				$height = max( $height, $this->content[ $i ]->get_full_height() );
			}
		}

		return $height;
	}

	/**
	 * Note that we SHOULD owerride the show method inherited from GenericContainerBox,
	 * as it MAY draw row background in case it was set via CSS rules. As row box
	 * is a "fake" box and will never have reasonable size and/or position in the layout,
	 * we should prevent this
	 */
	function show( &$viewport ) {
		// draw content
		$size = count( $this->content );

		for ( $i = 0; $i < $size; $i ++ ) {
			/**
			 * We'll check the visibility property here
			 * Reason: all boxes (except the top-level one) are contained in some other box,
			 * so every box will pass this check. The alternative is to add this check into every
			 * box class show member.
			 *
			 * The only exception of absolute positioned block boxes which are drawn separately;
			 * their show method is called explicitly; the similar check should be performed there
			 */

			$cell       =& $this->content[ $i ];
			$visibility = $cell->get_css_property( CSS_VISIBILITY );

			if ( $visibility === VISIBILITY_VISIBLE ) {
				if ( is_null( $cell->show( $viewport ) ) ) {
					return null;
				};
			};
		}

		return true;
	}

	function isTableRow() {
		return true;
	}
}

// $Header: /cvsroot/html2ps/box.table.section.php,v 1.14 2006/10/28 12:24:16 Konstantin Exp $

class TableSectionBox extends GenericContainerBox {
	function &create( &$root, &$pipeline ) {
		$state =& $pipeline->get_current_css_state();
		$box   =& new TableSectionBox();
		$box->readCSS( $state );

		// Automatically create at least one table row
		$row = new TableRowBox();
		$row->readCSS( $state );
		$box->add_child( $row );

		// Parse table contents
		$child = $root->first_child();
		while ( $child ) {
			$child_box =& create_pdf_box( $child, $pipeline );
			$box->add_child( $child_box );
			$child = $child->next_sibling();
		};

		return $box;
	}

	function TableSectionBox() {
		$this->GenericContainerBox();
	}

	// Overrides default 'add_child' in GenericFormattedBox
	function add_child( &$item ) {
		// Check if we're trying to add table cell to current table directly, without any table-rows
		if ( $item->isCell() ) {
			// Add cell to the last row
			$last_row =& $this->content[ count( $this->content ) - 1 ];
			$last_row->add_child( $item );

		} elseif ( $item->isTableRow() ) {
			// If previous row is empty, remove it (get rid of automatically generated table row in constructor)
			if ( count( $this->content ) > 0 ) {
				if ( count( $this->content[ count( $this->content ) - 1 ]->content ) == 0 ) {
					array_pop( $this->content );
				}
			};

			// Just add passed row
			$this->content[] =& $item;
		};
	}

	function isTableSection() {
		return true;
	}
}

// $Header: /cvsroot/html2ps/box.text.php,v 1.56 2007/05/07 12:15:53 Konstantin Exp $

require_once( HTML2PS_DIR . 'box.inline.simple.php' );

// TODO: from my POV, it wll be better to pass the font- or CSS-controlling object to the constructor
// instead of using globally visible functions in 'show'.

class TextBox extends SimpleInlineBox {
	var $words;
	var $encodings;
	var $hyphens;
	var $_widths;
	var $_word_widths;
	var $_wrappable;
	var $wrapped;

	function TextBox() {
		$this->SimpleInlineBox();

		$this->words        = array();
		$this->encodings    = array();
		$this->hyphens      = array();
		$this->_word_widths = array();
		$this->_wrappable   = array();
		$this->wrapped      = null;
		$this->_widths      = array();

		$this->font_size = 0;
		$this->ascender  = 0;
		$this->descender = 0;
		$this->width     = 0;
		$this->height    = 0;
	}

	/**
	 * Check if given subword contains soft hyphens and calculate
	 */
	function _make_wrappable( &$driver, $base_width, $font_name, $font_size, $subword_index ) {
		$hyphens   = $this->hyphens[ $subword_index ];
		$wrappable = array();

		foreach ( $hyphens as $hyphen ) {
			$subword_wrappable_index = $hyphen;
			$subword_wrappable_width = $base_width + $driver->stringwidth( substr( $this->words[ $subword_index ], 0, $subword_wrappable_index ),
					$font_name,
					$this->encodings[ $subword_index ],
					$font_size );
			$subword_full_width      = $subword_wrappable_width + $driver->stringwidth( '-',
					$font_name,
					"iso-8859-1",
					$font_size );

			$wrappable[] = array( $subword_index, $subword_wrappable_index, $subword_wrappable_width, $subword_full_width );
		};

		return $wrappable;
	}

	function get_content() {
		return join( '', array_map( array( $this, 'get_content_callback' ), $this->words, $this->encodings ) );
	}

	function get_content_callback( $word, $encoding ) {
		$manager_encoding =& ManagerEncoding::get();

		return $manager_encoding->to_utf8( $word, $encoding );
	}

	function get_height() {
		return $this->height;
	}

	function put_height( $value ) {
		$this->height = $value;
	}

	// Apply 'line-height' CSS property; modifies the default_baseline value
	// (NOT baseline, as it is calculated - and is overwritten - in the close_line
	// method of container box
	//
	// Note that underline position (or 'descender' in terms of PDFLIB) -
	// so, simple that space of text box under the baseline - is scaled too
	// when 'line-height' is applied
	//
	function _apply_line_height() {
		$height = $this->get_height();
		$under  = $height - $this->default_baseline;

		$line_height = $this->get_css_property( CSS_LINE_HEIGHT );

		if ( $height > 0 ) {
			$scale = $line_height->apply( $this->ascender + $this->descender ) / ( $this->ascender + $this->descender );
		} else {
			$scale = 0;
		};

		// Calculate the height delta of the text box

		$delta = $height * ( $scale - 1 );
		$this->put_height( ( $this->ascender + $this->descender ) * $scale );
		$this->default_baseline = $this->default_baseline + $delta / 2;
	}

	function _get_font_name( &$viewport, $subword_index ) {
		if ( isset( $this->_cache[ CACHE_TYPEFACE ][ $subword_index ] ) ) {
			return $this->_cache[ CACHE_TYPEFACE ][ $subword_index ];
		};

		$font_resolver =& $viewport->get_font_resolver();

		$font = $this->get_css_property( CSS_FONT );

		$typeface = $font_resolver->get_typeface_name( $font->family,
			$font->weight,
			$font->style,
			$this->encodings[ $subword_index ] );

		$this->_cache[ CACHE_TYPEFACE ][ $subword_index ] = $typeface;

		return $typeface;
	}

	function add_subword( $raw_subword, $encoding, $hyphens ) {
		$text_transform = $this->get_css_property( CSS_TEXT_TRANSFORM );
		switch ( $text_transform ) {
			case CSS_TEXT_TRANSFORM_CAPITALIZE:
				$subword = ucwords( $raw_subword );
				break;
			case CSS_TEXT_TRANSFORM_UPPERCASE:
				$subword = strtoupper( $raw_subword );
				break;
			case CSS_TEXT_TRANSFORM_LOWERCASE:
				$subword = strtolower( $raw_subword );
				break;
			case CSS_TEXT_TRANSFORM_NONE:
				$subword = $raw_subword;
				break;
		}

		$this->words[]     = $subword;
		$this->encodings[] = $encoding;
		$this->hyphens[]   = $hyphens;
	}

	function &create( $text, $encoding, &$pipeline ) {
		$box =& TextBox::create_empty( $pipeline );
		$box->add_subword( $text, $encoding, array() );

		return $box;
	}

	function &create_empty( &$pipeline ) {
		$box       =& new TextBox();
		$css_state = $pipeline->get_current_css_state();

		$box->readCSS( $css_state );
		$css_state = $pipeline->get_current_css_state();

		return $box;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSSLengths( $state,
			array(
				CSS_TEXT_INDENT,
				CSS_LETTER_SPACING
			) );
	}

	// Inherited from GenericFormattedBox
	function get_descender() {
		return $this->descender;
	}

	function get_ascender() {
		return $this->ascender;
	}

	function get_baseline() {
		return $this->baseline;
	}

	function get_min_width_natural( &$context ) {
		return $this->get_full_width();
	}

	function get_min_width( &$context ) {
		return $this->get_full_width();
	}

	function get_max_width( &$context ) {
		return $this->get_full_width();
	}

	// Checks if current inline box should cause a line break inside the parent box
	//
	// @param $parent reference to a parent box
	// @param $content flow context
	// @return true if line break occurred; false otherwise
	//
	function maybe_line_break( &$parent, &$context ) {
		if ( ! $parent->line_break_allowed() ) {
			return false;
		};

		$last =& $parent->last_in_line();
		if ( $last ) {
			// Check  if last  box was  a note  call box.  Punctuation marks
			// after  a note-call  box should  not be  wrapped to  new line,
			// while "plain" words may be wrapped.
			if ( $last->is_note_call() && $this->is_punctuation() ) {
				return false;
			};
		};

		// Calculate the x-coordinate of this box right edge
		$right_x = $this->get_full_width() + $parent->_current_x;

		$need_break = false;

		// Check for right-floating boxes
		// If upper-right corner of this inline box is inside of some float, wrap the line
		$float = $context->point_in_floats( $right_x, $parent->_current_y );
		if ( $float ) {
			$need_break = true;
		};

		// No floats; check if we had run out the right edge of container
		// TODO: nobr-before, nobr-after
		if ( ( $right_x > $parent->get_right() + EPSILON ) ) {
			// Now check if parent line box contains any other boxes;
			// if not, we should draw this box unless we have a floating box to the left

			$first = $parent->get_first();

			$ti            = $this->get_css_property( CSS_TEXT_INDENT );
			$indent_offset = $ti->calculate( $parent );

			if ( $parent->_current_x > $parent->get_left() + $indent_offset + EPSILON ) {
				$need_break = true;
			};
		}

		// As close-line will not change the current-Y parent coordinate if no
		// items were in the line box, we need to offset this explicitly in this case
		//
		if ( $parent->line_box_empty() && $need_break ) {
			$parent->_current_y -= $this->get_height();
		};

		if ( $need_break ) {
			// Check if current box contains soft hyphens and use them, breaking word into parts
			$size = count( $this->_wrappable );
			if ( $size > 0 ) {
				$width_delta = $right_x - $parent->get_right();
				if ( ! is_null( $float ) ) {
					$width_delta = $right_x - $float->get_left_margin();
				};

				$this->_find_soft_hyphen( $parent, $width_delta );
			};

			$parent->close_line( $context );

			// Check if parent inline boxes have left padding/margins and add them to current_x
			$element = $this->parent;
			while ( ! is_null( $element ) && is_a( $element, "GenericInlineBox" ) ) {
				$parent->_current_x += $element->get_extra_left();
				$element            = $element->parent;
			};
		};

		return $need_break;
	}

	function _find_soft_hyphen( &$parent, $width_delta ) {
		/**
		 * Now we search for soft hyphen closest to the right margin
		 */
		$size = count( $this->_wrappable );
		for ( $i = $size - 1; $i >= 0; $i -- ) {
			$wrappable = $this->_wrappable[ $i ];
			if ( $this->get_width() - $wrappable[3] > $width_delta ) {
				$this->save_wrapped( $wrappable, $parent, $context );
				$parent->append_line( $this );

				return;
			};
		};
	}

	function save_wrapped( $wrappable, &$parent, &$context ) {
		$this->wrapped = array(
			$wrappable,
			$parent->_current_x + $this->get_extra_left(),
			$parent->_current_y - $this->get_extra_top()
		);
	}

	function reflow( &$parent, &$context ) {
		// Check if we need a line break here (possilble several times in a row, if we
		// have a long word and a floating box intersecting with this word
		//
		// To prevent infinite loop, we'll use a limit of 100 sequental line feeds
		$i = 0;

		do {
			$i ++;
		} while ( $this->maybe_line_break( $parent, $context ) && $i < 100 );

		// Determine the baseline position and height of the text-box using line-height CSS property
		$this->_apply_line_height();

		// set default baseline
		$this->baseline = $this->default_baseline;

		// append current box to parent line box
		$parent->append_line( $this );

		// Determine coordinates of upper-left _margin_ corner
		$this->guess_corner( $parent );

		// Offset parent current X coordinate
		if ( ! is_null( $this->wrapped ) ) {
			$parent->_current_x += $this->get_full_width() - $this->wrapped[0][2];
		} else {
			$parent->_current_x += $this->get_full_width();
		};

		// Extends parents height
		$parent->extend_height( $this->get_bottom() );

		// Update the value of current collapsed margin; pure text (non-span)
		// boxes always have zero margin

		$context->pop_collapsed_margin();
		$context->push_collapsed_margin( 0 );
	}

	function getWrappedWidthAndHyphen() {
		return $this->wrapped[0][3];
	}

	function getWrappedWidth() {
		return $this->wrapped[0][2];
	}

	function reflow_text( &$driver ) {
		$num_words = count( $this->words );

		/**
		 * Empty text box
		 */
		if ( $num_words == 0 ) {
			return true;
		};

		/**
		 * A simple assumption is made: fonts used for different encodings
		 * have equal ascender/descender values  (while they have the same
		 * typeface, style and weight).
		 */
		$font_name = $this->_get_font_name( $driver, 0 );

		/**
		 * Get font vertical metrics
		 */
		$ascender = $driver->font_ascender( $font_name, $this->encodings[0] );
		if ( is_null( $ascender ) ) {
			error_log( "TextBox::reflow_text: cannot get font ascender" );

			return null;
		};

		$descender = $driver->font_descender( $font_name, $this->encodings[0] );
		if ( is_null( $descender ) ) {
			error_log( "TextBox::reflow_text: cannot get font descender" );

			return null;
		};

		/**
		 * Setup box size
		 */
		$font      = $this->get_css_property( CSS_FONT_SIZE );
		$font_size = $font->getPoints();

		// Both ascender and descender should make $font_size
		// as it is not guaranteed that $ascender + $descender == 1,
		// we should normalize the result
		$koeff           = $font_size / ( $ascender + $descender );
		$this->ascender  = $ascender * $koeff;
		$this->descender = $descender * $koeff;

		$this->default_baseline = $this->ascender;
		$this->height           = $this->ascender + $this->descender;

		/**
		 * Determine box width
		 */
		if ( $font_size > 0 ) {
			$width = 0;

			for ( $i = 0; $i < $num_words; $i ++ ) {
				$font_name = $this->_get_font_name( $driver, $i );

				$current_width        = $driver->stringwidth( $this->words[ $i ],
					$font_name,
					$this->encodings[ $i ],
					$font_size );
				$this->_word_widths[] = $current_width;

				// Add information about soft hyphens
				$this->_wrappable = array_merge( $this->_wrappable, $this->_make_wrappable( $driver, $width, $font_name, $font_size, $i ) );

				$width += $current_width;
			};

			$this->width = $width;
		} else {
			$this->width = 0;
		};

		$letter_spacing = $this->get_css_property( CSS_LETTER_SPACING );

		if ( $letter_spacing->getPoints() != 0 ) {
			$this->_widths = array();

			for ( $i = 0; $i < $num_words; $i ++ ) {
				$num_chars = strlen( $this->words[ $i ] );

				for ( $j = 0; $j < $num_chars; $j ++ ) {
					$this->_widths[] = $driver->stringwidth( $this->words[ $i ]{$j},
						$font_name,
						$this->encodings[ $i ],
						$font_size );
				};

				$this->width += $letter_spacing->getPoints() * $num_chars;
			};
		};

		return true;
	}

	function show( &$driver ) {
		/**
		 * Check if font-size have been set to 0; in this case we should not draw this box at all
		 */
		$font_size = $this->get_css_property( CSS_FONT_SIZE );
		if ( $font_size->getPoints() == 0 ) {
			return true;
		}

		// Check if current text box will be cut-off by the page edge
		// Get Y coordinate of the top edge of the box
		$top = $this->get_top_margin();
		// Get Y coordinate of the bottom edge of the box
		$bottom = $this->get_bottom_margin();

		$top_inside    = $top >= $driver->getPageBottom() - EPSILON;
		$bottom_inside = $bottom >= $driver->getPageBottom() - EPSILON;

		if ( ! $top_inside && ! $bottom_inside ) {
			return true;
		}

		return $this->_showText( $driver );
	}

	function _showText( &$driver ) {
		if ( ! is_null( $this->wrapped ) ) {
			return $this->_showTextWrapped( $driver );
		} else {
			return $this->_showTextNormal( $driver );
		};
	}

	function _showTextWrapped( &$driver ) {
		// draw generic box
		parent::show( $driver );

		$font_size = $this->get_css_property( CSS_FONT_SIZE );

		$decoration = $this->get_css_property( CSS_TEXT_DECORATION );

		// draw text decoration
		$driver->decoration( $decoration['U'],
			$decoration['O'],
			$decoration['T'] );

		$letter_spacing = $this->get_css_property( CSS_LETTER_SPACING );

		// Output text with the selected font
		// note that we're using $default_baseline;
		// the alignment offset - the difference between baseline and default_baseline values
		// is taken into account inside the get_top/get_bottom functions
		//
		$current_char = 0;

		$left      = $this->wrapped[1];
		$top       = $this->get_top() - $this->default_baseline;
		$num_words = count( $this->words );

		/**
		 * First part of wrapped word (before hyphen)
		 */
		for ( $i = 0; $i < $this->wrapped[0][0]; $i ++ ) {
			// Activate font
			$status = $driver->setfont( $this->_get_font_name( $driver, $i ),
				$this->encodings[ $i ],
				$font_size->getPoints() );
			if ( is_null( $status ) ) {
				error_log( "TextBox::show: setfont call failed" );

				return null;
			};

			$driver->show_xy( $this->words[ $i ],
				$left,
				$this->wrapped[2] - $this->default_baseline );
			$left += $this->_word_widths[ $i ];
		};

		$index = $this->wrapped[0][0];

		$status = $driver->setfont( $this->_get_font_name( $driver, $index ),
			$this->encodings[ $index ],
			$font_size->getPoints() );
		if ( is_null( $status ) ) {
			error_log( "TextBox::show: setfont call failed" );

			return null;
		};

		$driver->show_xy( substr( $this->words[ $index ], 0, $this->wrapped[0][1] ) . "-",
			$left,
			$this->wrapped[2] - $this->default_baseline );

		/**
		 * Second part of wrapped word (after hyphen)
		 */

		$left = $this->get_left();
		$top  = $this->get_top();
		$driver->show_xy( substr( $this->words[ $index ], $this->wrapped[0][1] ),
			$left,
			$top - $this->default_baseline );

		$size = count( $this->words );
		for ( $i = $this->wrapped[0][0] + 1; $i < $size; $i ++ ) {
			// Activate font
			$status = $driver->setfont( $this->_get_font_name( $driver, $i ),
				$this->encodings[ $i ],
				$font_size->getPoints() );
			if ( is_null( $status ) ) {
				error_log( "TextBox::show: setfont call failed" );

				return null;
			};

			$driver->show_xy( $this->words[ $i ],
				$left,
				$top - $this->default_baseline );

			$left += $this->_word_widths[ $i ];
		};

		return true;
	}

	function _showTextNormal( &$driver ) {
		// draw generic box
		parent::show( $driver );

		$font_size = $this->get_css_property( CSS_FONT_SIZE );

		$decoration = $this->get_css_property( CSS_TEXT_DECORATION );

		// draw text decoration
		$driver->decoration( $decoration['U'],
			$decoration['O'],
			$decoration['T'] );

		$letter_spacing = $this->get_css_property( CSS_LETTER_SPACING );

		if ( $letter_spacing->getPoints() == 0 ) {
			// Output text with the selected font
			// note that we're using $default_baseline;
			// the alignment offset - the difference between baseline and default_baseline values
			// is taken into account inside the get_top/get_bottom functions
			//
			$size = count( $this->words );
			$left = $this->get_left();

			for ( $i = 0; $i < $size; $i ++ ) {
				// Activate font
				$status = $driver->setfont( $this->_get_font_name( $driver, $i ),
					$this->encodings[ $i ],
					$font_size->getPoints() );
				if ( is_null( $status ) ) {
					error_log( "TextBox::show: setfont call failed" );

					return null;
				};

				$driver->show_xy( $this->words[ $i ],
					$left,
					$this->get_top() - $this->default_baseline );

				$left += $this->_word_widths[ $i ];
			};
		} else {
			$current_char = 0;

			$left      = $this->get_left();
			$top       = $this->get_top() - $this->default_baseline;
			$num_words = count( $this->words );

			for ( $i = 0; $i < $num_words; $i ++ ) {
				$num_chars = strlen( $this->words[ $i ] );

				for ( $j = 0; $j < $num_chars; $j ++ ) {
					$status = $driver->setfont( $this->_get_font_name( $driver, $i ),
						$this->encodings[ $i ],
						$font_size->getPoints() );

					$driver->show_xy( $this->words[ $i ]{$j}, $left, $top );
					$left += $this->_widths[ $current_char ] + $letter_spacing->getPoints();
					$current_char ++;
				};
			};
		};

		return true;
	}

	function show_fixed( &$driver ) {
		$font_size = $this->get_css_property( CSS_FONT_SIZE );

		// Check if font-size have been set to 0; in this case we should not draw this box at all
		if ( $font_size->getPoints() == 0 ) {
			return true;
		}

		return $this->_showText( $driver );
	}

	function offset( $dx, $dy ) {
		parent::offset( $dx, $dy );

		// Note that horizonal offset should be called explicitly from text-align routines
		// otherwise wrapped part will be offset twice (as offset is called both for
		// wrapped and non-wrapped parts).
		if ( ! is_null( $this->wrapped ) ) {
			$this->offset_wrapped( $dx, $dy );
		};
	}

	function offset_wrapped( $dx, $dy ) {
		$this->wrapped[1] += $dx;
		$this->wrapped[2] += $dy;
	}

	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		$linebox_started     = true;
		$previous_whitespace = false;

		return;
	}

	function is_null() {
		return false;
	}
}

// $Header: /cvsroot/html2ps/box.text.string.php,v 1.5 2006/10/06 20:10:52 Konstantin Exp $

// TODO: from my POV, it wll be better to pass the font- or CSS-controlling object to the constructor
// instead of using globally visible functions in 'show'.

class TextBoxString extends TextBox {
	function &create( $text, $encoding ) {
		$box =& new TextBoxString( $text, $encoding );
		$box->readCSS( $pipeline->get_current_css_state() );

		return $box;
	}

	function TextBoxString( $word, $encoding ) {
		// Call parent constructor
		$this->TextBox();
		$this->add_subword( $word, $encoding, array() );
	}

	function get_extra_bottom() {
		return 0;
	}

	// "Pure" Text boxes never have margins/border/padding
	function get_extra_left() {
		return 0;
	}

	// "Pure" Text boxes never have margins/border/padding
	function get_extra_right() {
		return 0;
	}

	function get_extra_top() {
		return 0;
	}

	function get_full_width() {
		return $this->width;
	}

	function get_margin_top() {
		return 0;
	}

	function get_min_width( &$context ) {
		return $this->width;
	}

	function get_max_width( &$context ) {
		return $this->width;
	}

	// Note that we don't need to call complicated 'get_width' function inherited from GenericFormattedBox,
	// a TextBox never have width constraints nor children; its width is always defined by the string length
	function get_width() {
		return $this->width;
	}
}

class BoxTextFieldPageNo extends TextBoxString {
	function BoxTextFieldPageNo() {
		$this->TextBoxString( '', 'iso-8859-1' );
	}

	function from_box( &$box ) {
		$field = new BoxTextFieldPageNo;

		$field->copy_style( $box );

		$field->words     = array( '000' );
		$field->encodings = array( 'iso-8859-1' );
		$field->_left     = $box->_left;
		$field->_top      = $box->_top;
		$field->baseline  = $box->baseline;

		return $field;
	}

	function show( &$viewport ) {
		$font = $this->get_css_property( CSS_FONT );

		$this->words[0] = sprintf( '%d', $viewport->current_page );

		$field_width = $this->width;
		$field_left  = $this->_left;

		if ( $font->size->getPoints() > 0 ) {
			$value_width = $viewport->stringwidth( $this->words[0],
				$this->_get_font_name( $viewport, 0 ),
				$this->encodings[0],
				$font->size->getPoints() );
			if ( is_null( $value_width ) ) {
				return null;
			};
		} else {
			$value_width = 0;
		};
		$this->width = $value_width;
		$this->_left += ( $field_width - $value_width ) / 2;

		if ( is_null( TextBoxString::show( $viewport ) ) ) {
			return null;
		};

		$this->width = $field_width;
		$this->_left = $field_left;

		return true;
	}

	function show_fixed( &$driver ) {
		$font = $this->get_css_property( CSS_FONT );

		$this->words[0] = sprintf( '%d', $driver->current_page );

		$field_width = $this->width;
		$field_left  = $this->_left;

		if ( $font->size->getPoints() > 0 ) {
			$value_width = $driver->stringwidth( $this->words[0],
				$this->_get_font_name( $driver, 0 ),
				$this->encodings[0],
				$font->size->getPoints() );
			if ( is_null( $value_width ) ) {
				return null;
			};
		} else {
			$value_width = 0;
		};
		$this->width = $value_width;
		$this->_left += ( $field_width - $value_width ) / 2;

		if ( is_null( TextBoxString::show_fixed( $driver ) ) ) {
			return null;
		};

		$this->width = $field_width;
		$this->_left = $field_left;

		return true;
	}
}

/**
 * Handles the '##PAGES##' text field.
 *
 */
class BoxTextFieldPages extends TextBoxString {
	function BoxTextFieldPages() {
		$this->TextBoxString( "", "iso-8859-1" );
	}

	function from_box( &$box ) {
		$field = new BoxTextFieldPages;

		$field->copy_style( $box );

		$field->words     = array( "000" );
		$field->encodings = array( "iso-8859-1" );
		$field->_left     = $box->_left;
		$field->_top      = $box->_top;
		$field->baseline  = $box->baseline;

		return $field;
	}

	function show( &$viewport ) {
		$font = $this->get_css_property( CSS_FONT );

		$this->words[0] = sprintf( "%d", $viewport->expected_pages );

		$field_width = $this->width;
		$field_left  = $this->_left;

		if ( $font->size->getPoints() > 0 ) {
			$value_width = $viewport->stringwidth( $this->words[0],
				$this->_get_font_name( $viewport, 0 ),
				$this->encodings[0],
				$font->size->getPoints() );
			if ( is_null( $value_width ) ) {
				return null;
			};
		} else {
			$value_width = 0;
		};
		$this->width = $value_width;
		$this->_left += ( $field_width - $value_width ) / 2;

		if ( is_null( TextBoxString::show( $viewport ) ) ) {
			return null;
		};

		$this->width = $field_width;
		$this->_left = $field_left;

		return true;
	}

	function show_fixed( &$viewport ) {
		$font = $this->get_css_property( CSS_FONT );

		$this->words[0] = sprintf( "%d", $viewport->expected_pages );

		$field_width = $this->width;
		$field_left  = $this->_left;

		if ( $font->size->getPoints() > 0 ) {
			$value_width = $viewport->stringwidth( $this->words[0],
				$this->_get_font_name( $viewport, 0 ),
				$this->encodings[0],
				$font->size->getPoints() );
			if ( is_null( $value_width ) ) {
				return null;
			};
		} else {
			$value_width = 0;
		};
		$this->width = $value_width;
		$this->_left += ( $field_width - $value_width ) / 2;

		if ( is_null( TextBoxString::show_fixed( $viewport ) ) ) {
			return null;
		};

		$this->width = $field_width;
		$this->_left = $field_left;

		return true;
	}
}

// $Header: /cvsroot/html2ps/box.whitespace.php,v 1.33 2007/01/24 18:55:46 Konstantin Exp $

class WhitespaceBox extends TextBox {
	function &create( &$pipeline ) {
		$box =& new WhitespaceBox();
		$box->readCSS( $pipeline->get_current_css_state() );
		$box->add_subword( " ", 'iso-8859-1', array() );

		return $box;
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSSLengths( $state,
			array( CSS_WORD_SPACING ) );
	}

	function get_extra_bottom() {
		return 0;
	}

	// "Pure" Text boxes never have margins/border/padding
	function get_extra_left() {
		return 0;
	}

	// "Pure" Text boxes never have margins/border/padding
	function get_extra_right() {
		return 0;
	}

	function get_extra_top() {
		return 0;
	}

	function get_full_width() {
		return $this->width;
	}

	function get_margin_top() {
		return 0;
	}

	function get_min_width( &$context ) {
		return $this->width;
	}

	function get_max_width( &$context ) {
		return $this->width;
	}

	function WhitespaceBox() {
		// Call parent constructor
		$this->TextBox();
	}

	// (!) SIDE EFFECT: current whitespace box can be replaced by a null box during reflow.
	// callers of reflow should take this into account and possilby check for this
	// after reflow returns. This can be detected by UID change.
	//
	function reflow( &$parent, &$context ) {
		// Check if there are any boxes in parent's line box
		if ( $parent->line_box_empty() ) {
			// The very first whitespace in the line box should not affect neither height nor baseline of the line box;
			// because following boxes can be smaller that assumed whitespace height
			// Example: <br>[whitespace]<img height="2" width="2"><br>; whitespace can overextend this line

			$this->width  = 0;
			$this->height = 0;
		} elseif ( is_a( $parent->last_in_line(), "WhitespaceBox" ) ) {
			// Duplicate whitespace boxes should not offset further content and affect the line box length

			$this->width  = 0;
			$this->height = 0;
		} elseif ( $this->maybe_line_break( $parent, $context ) ) {
			$this->width  = 0;
			$this->height = 0;
		};

		parent::reflow( $parent, $context );
	}

	function reflow_text( &$driver ) {
		if ( is_null( parent::reflow_text( $driver ) ) ) {
			return null;
		};

		// Override widths
		$letter_spacing = $this->get_css_property( CSS_LETTER_SPACING );
		$word_spacing   = $this->get_css_property( CSS_WORD_SPACING );

		$this->width =
			$this->height * WHITESPACE_FONT_SIZE_FRACTION +
			$letter_spacing->getPoints() +
			$word_spacing->getPoints();

		return true;
	}

	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		if ( ! $linebox_started ||
		     ( $linebox_started && $previous_whitespace ) ) {

			$link_destination = $this->get_css_property( CSS_HTML2PS_LINK_DESTINATION );
			if ( is_null( $link_destination ) ) {
				$this->parent->remove( $this );

				return;
			};

			$this->font_height = 0.001;
			$this->height      = 0;
			$this->width       = 0;
		};

		$previous_whitespace = true;

		// Note that there can (in theory) several whitespaces in a row, so
		// we could not modify a flag until we met a real text box
	}
}

// $Header: /cvsroot/html2ps/box.img.php,v 1.50 2007/05/06 18:49:29 Konstantin Exp $

define( 'SCALE_NONE', 0 );
define( 'SCALE_WIDTH', 1 );
define( 'SCALE_HEIGHT', 2 );

class GenericImgBox extends GenericInlineBox {
	function GenericImgBox() {
		$this->GenericInlineBox();
	}

	function get_max_width_natural( &$context ) {
		return $this->get_full_width( $context );
	}

	function get_min_width( &$context ) {
		return $this->get_full_width();
	}

	function get_max_width( &$context ) {
		return $this->get_full_width();
	}

	function is_null() {
		return false;
	}

	function pre_reflow_images() {
		switch ( $this->scale ) {
			case SCALE_WIDTH:
				// Only 'width' attribute given
				$size =
					$this->src_width / $this->src_height *
					$this->get_width();

				$this->put_height( $size );

				// Update baseline according to constrained image height
				$this->default_baseline = $this->get_full_height();
				break;
			case SCALE_HEIGHT:
				// Only 'height' attribute given
				$size =
					$this->src_height / $this->src_width *
					$this->get_height();

				$this->put_width( $size );
				$this->setCSSProperty( CSS_WIDTH, new WCConstant( $size ) );

				$this->default_baseline = $this->get_full_height();
				break;
		};
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		// '-html2ps-link-target'
		global $g_config;
		if ( $g_config["renderlinks"] ) {
			$this->_readCSS( $state,
				array( CSS_HTML2PS_LINK_TARGET ) );
		};
	}

	function reflow_static( &$parent, &$context ) {
		$this->pre_reflow_images();

		GenericFormattedBox::reflow( $parent, $context );

		// Check if we need a line break here
		$this->maybe_line_break( $parent, $context );

		// set default baseline
		$this->baseline = $this->default_baseline;

		// append to parent line box
		$parent->append_line( $this );

		// Move box to the parent current point
		$this->guess_corner( $parent );

		// Move parent's X coordinate
		$parent->_current_x += $this->get_full_width();

		// Extend parent height
		$parent->extend_height( $this->get_bottom_margin() );
	}

	function _get_font_name( &$driver, $subword_index ) {
		if ( isset( $this->_cache[ CACHE_TYPEFACE ][ $subword_index ] ) ) {
			return $this->_cache[ CACHE_TYPEFACE ][ $subword_index ];
		};

		$font_resolver =& $driver->get_font_resolver();

		$font     = $this->get_css_property( CSS_FONT );
		$typeface = $font_resolver->get_typeface_name( $font->family,
			$font->weight,
			$font->style,
			'iso-8859-1' );

		$this->_cache[ CACHE_TYPEFACE ][ $subword_index ] = $typeface;

		return $typeface;
	}

	function reflow_text( &$driver ) {
		// In XHTML images are treated as a common inline elements; they are affected by line-height and font-size
		global $g_config;
		if ( $g_config['mode'] == 'xhtml' ) {
			/**
			 * A simple assumption is made: fonts used for different encodings
			 * have equal ascender/descender values  (while they have the same
			 * typeface, style and weight).
			 */
			$font_name = $this->_get_font_name( $driver, 0 );

			/**
			 * Get font vertical metrics
			 */
			$ascender = $driver->font_ascender( $font_name, 'iso-8859-1' );
			if ( is_null( $ascender ) ) {
				error_log( "ImgBox::reflow_text: cannot get font ascender" );

				return null;
			};

			$descender = $driver->font_descender( $font_name, 'iso-8859-1' );
			if ( is_null( $descender ) ) {
				error_log( "ImgBox::reflow_text: cannot get font descender" );

				return null;
			};

			/**
			 * Setup box size
			 */
			$font      = $this->get_css_property( CSS_FONT_SIZE );
			$font_size = $font->getPoints();

			$this->ascender  = $ascender * $font_size;
			$this->descender = $descender * $font_size;
		} else {
			$this->ascender  = $this->get_height();
			$this->descender = 0;
		};

		return true;
	}

	// Image boxes are regular inline boxes; whitespaces after images should be rendered
	//
	function reflow_whitespace( &$linebox_started, &$previous_whitespace ) {
		$linebox_started     = true;
		$previous_whitespace = false;

		return;
	}

	function show_fixed( &$driver ) {
		return $this->show( $driver );
	}
}

class BrokenImgBox extends GenericImgBox {
	var $alt;

	function BrokenImgBox( $width, $height, $alt ) {
		$this->scale    = SCALE_NONE;
		$this->encoding = DEFAULT_ENCODING;

		// Call parent constructor
		$this->GenericImgBox();

		$this->alt = $alt;
	}

	function show( &$driver ) {
		$driver->save();

		// draw generic box
		GenericFormattedBox::show( $driver );

		$driver->setlinewidth( 0.1 );
		$driver->moveto( $this->get_left(), $this->get_top() );
		$driver->lineto( $this->get_right(), $this->get_top() );
		$driver->lineto( $this->get_right(), $this->get_bottom() );
		$driver->lineto( $this->get_left(), $this->get_bottom() );
		$driver->closepath();
		$driver->stroke();

		if ( ! $GLOBALS['g_config']['debugnoclip'] ) {
			$driver->moveto( $this->get_left(), $this->get_top() );
			$driver->lineto( $this->get_right(), $this->get_top() );
			$driver->lineto( $this->get_right(), $this->get_bottom() );
			$driver->lineto( $this->get_left(), $this->get_bottom() );
			$driver->closepath();
			$driver->clip();
		};

		// Output text with the selected font
		$size = pt2pt( BROKEN_IMAGE_ALT_SIZE_PT );

		$status = $driver->setfont( "Times-Roman", "iso-8859-1", $size );
		if ( is_null( $status ) ) {
			return null;
		};

		$driver->show_xy( $this->alt,
			$this->get_left() + $this->width / 2 - $driver->stringwidth( $this->alt,
				"Times-Roman",
				"iso-8859-1",
				$size ) / 2,
			$this->get_top() - $this->height / 2 - $size / 2 );

		$driver->restore();

		$strategy =& new StrategyLinkRenderingNormal();
		$strategy->apply( $this, $driver );

		return true;
	}
}

class ImgBox extends GenericImgBox {
	var $image;
	var $type; // unused; should store the preferred image format (JPG / PNG)

	function ImgBox( $img ) {
		$this->encoding = DEFAULT_ENCODING;
		$this->scale    = SCALE_NONE;

		// Call parent constructor
		$this->GenericImgBox();

		// Store image for further processing
		$this->image = $img;
	}

	function &create( &$root, &$pipeline ) {
		// Open image referenced by HTML tag
		// Some crazy HTML writers add leading and trailing spaces to SRC attribute value - we need to remove them
		//
		$url_autofix = new AutofixUrl();
		$src         = $url_autofix->apply( trim( $root->get_attribute( "src" ) ) );

		$image_url = $pipeline->guess_url( $src );
		$src_img   = ImageFactory::get( $image_url, $pipeline );

		if ( is_null( $src_img ) ) {
			// image could not be opened, use ALT attribute

			if ( $root->has_attribute( 'width' ) ) {
				$width = px2pt( $root->get_attribute( 'width' ) );
			} else {
				$width = px2pt( BROKEN_IMAGE_DEFAULT_SIZE_PX );
			};

			if ( $root->has_attribute( 'height' ) ) {
				$height = px2pt( $root->get_attribute( 'height' ) );
			} else {
				$height = px2pt( BROKEN_IMAGE_DEFAULT_SIZE_PX );
			};

			$alt = $root->get_attribute( 'alt' );

			$box =& new BrokenImgBox( $width, $height, $alt );

			$box->readCSS( $pipeline->get_current_css_state() );

			$box->put_width( $width );
			$box->put_height( $height );

			$box->default_baseline = $box->get_full_height();

			$box->src_height = $box->get_height();
			$box->src_width  = $box->get_width();

			return $box;
		} else {
			$box =& new ImgBox( $src_img );
			$box->readCSS( $pipeline->get_current_css_state() );
			$box->_setupSize();

			return $box;
		}
	}

	function _setupSize() {
		$this->put_width( px2pt( $this->image->sx() ) );
		$this->put_height( px2pt( $this->image->sy() ) );
		$this->default_baseline = $this->get_full_height();

		$this->src_height = $this->image->sx();
		$this->src_width  = $this->image->sy();

		$wc = $this->get_css_property( CSS_WIDTH );
		$hc = $this->get_height_constraint();

		// Proportional scaling
		if ( $hc->is_null() && ! $wc->isNull() ) {
			$this->scale = SCALE_WIDTH;

			// Only 'width' attribute given
			$size =
				$this->src_width / $this->src_height *
				$this->get_width();

			$this->put_height( $size );

			// Update baseline according to constrained image height
			$this->default_baseline = $this->get_full_height();

		} elseif ( ! $hc->is_null() && $wc->isNull() ) {
			$this->scale = SCALE_HEIGHT;

			// Only 'height' attribute given
			$size =
				$this->src_height / $this->src_width *
				$this->get_height();

			$this->put_width( $size );
			$this->setCSSProperty( CSS_WIDTH, new WCConstant( $size ) );

			$this->default_baseline = $this->get_full_height();
		};
	}

	function show( &$driver ) {
		// draw generic box
		GenericFormattedBox::show( $driver );

		// Check if "designer" set the height or width of this image to zero; in this there will be no reason
		// in drawing the image at all
		//
		if ( $this->get_width() < EPSILON ||
		     $this->get_height() < EPSILON ) {
			return true;
		};

		$driver->image_scaled( $this->image,
			$this->get_left(), $this->get_bottom(),
			$this->get_width() / $this->image->sx(),
			$this->get_height() / $this->image->sy() );

		$strategy =& new StrategyLinkRenderingNormal();
		$strategy->apply( $this, $driver );

		return true;
	}
}


class ButtonBrokenImageBox extends BrokenImgBox {
	var $_field_name;
	var $_field_value;
	var $_action_url;

	function ButtonBrokenImageBox( $width, $height, $alt, $field, $value, $action_url ) {
		$this->BrokenImgBox( $width, $height, $alt );

		$this->_field_name  = $field;
		$this->_field_value = $value;
		$this->set_action_url( $action_url );
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array( CSS_HTML2PS_FORM_ACTION ) );
	}

	function set_action_url( $action_url ) {
		$this->_action_url = $action_url;
	}

	function show( &$driver ) {
		$status = parent::show( $driver );

		global $g_config;
		if ( $g_config['renderforms'] ) {
			$driver->field_pushbuttonimage( $this->get_left_padding(),
				$this->get_top_padding(),
				$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
				$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom(),
				$this->_field_name,
				$this->_field_value,
				$this->_action_url );
		};

		return $status;
	}
}

class ButtonImageBox extends ImgBox {
	var $_field_name;
	var $_field_value;
	var $_action_url;

	function ButtonImageBox( $img, $field, $value, $action_url ) {
		$this->ImgBox( $img );

		$this->_field_name  = $field;
		$this->_field_value = $value;
		$this->set_action_url( $action_url );
	}

	function readCSS( &$state ) {
		parent::readCSS( $state );

		$this->_readCSS( $state,
			array( CSS_HTML2PS_FORM_ACTION ) );
	}

	function set_action_url( $action_url ) {
		$this->_action_url = $action_url;
	}

	function show( &$driver ) {
		$status = parent::show( $driver );

		global $g_config;
		if ( $g_config['renderforms'] ) {
			$driver->field_pushbuttonimage( $this->get_left_padding(),
				$this->get_top_padding(),
				$this->get_width() + $this->get_padding_left() + $this->get_padding_right(),
				$this->get_height() + $this->get_padding_top() + $this->get_padding_bottom(),
				$this->_field_name,
				$this->_field_value,
				$this->_action_url );
		};

		return $status;
	}

	function &create( &$root, &$pipeline ) {
		$name  = $root->get_attribute( 'name' );
		$value = $root->get_attribute( 'value' );

		$url_autofix = new AutofixUrl();
		$src         = $url_autofix->apply( trim( $root->get_attribute( "src" ) ) );

		$src_img = ImageFactory::get( $pipeline->guess_url( $src ), $pipeline );
		if ( is_null( $src_img ) ) {
			error_log( sprintf( "Cannot open image at '%s'", $src ) );

			if ( $root->has_attribute( 'width' ) ) {
				$width = px2pt( $root->get_attribute( 'width' ) );
			} else {
				$width = px2pt( BROKEN_IMAGE_DEFAULT_SIZE_PX );
			};

			if ( $root->has_attribute( 'height' ) ) {
				$height = px2pt( $root->get_attribute( 'height' ) );
			} else {
				$height = px2pt( BROKEN_IMAGE_DEFAULT_SIZE_PX );
			};

			$alt = $root->get_attribute( 'alt' );

			$css_state =& $pipeline->get_current_css_state();
			$box       =& new ButtonBrokenImagebox( $width, $height, $alt, $name, $value,
				$css_state->get_property( CSS_HTML2PS_FORM_ACTION ) );
			$box->readCSS( $css_state );

			return $box;
		};

		$css_state =& $pipeline->get_current_css_state();
		$box       =& new ButtonImageBox( $src_img, $name, $value,
			$css_state->get_property( CSS_HTML2PS_FORM_ACTION ) );
		$box->readCSS( $css_state );
		$box->_setupSize();

		return $box;
	}
}


// $Header: /cvsroot/html2ps/box.utils.text-align.inc.php,v 1.13 2007/01/09 20:13:48 Konstantin Exp $

function ta_left( &$box, &$context, $lastline ) {
	// Do nothing; text is left-aligned by default
}

function ta_center( &$box, &$context, $lastline ) {
	$delta = $box->_line_length_delta( $context ) / 2;

	$size = count( $box->_line );
	for ( $i = 0; $i < $size; $i ++ ) {
		$box->_line[ $i ]->offset( $delta, 0 );
	};

	$first_box =& $box->_line[0];
	if ( isset( $first_box->wrapped ) && ! is_null( $first_box->wrapped ) ) {
		$first_box->offset_wrapped( - $delta, 0 );
	};
}

function ta_right( &$box, &$context, $lastline ) {
	$delta = $box->_line_length_delta( $context );

	$size = count( $box->_line );
	for ( $i = 0; $i < $size; $i ++ ) {
		$box->_line[ $i ]->offset( $delta, 0 );
	};

	$first_box =& $box->_line[0];
	if ( isset( $first_box->wrapped ) && ! is_null( $first_box->wrapped ) ) {
		$first_box->offset_wrapped( - $delta, 0 );
	};
}

function ta_justify( &$box, &$context, $lastline ) {
	// last line is never justified
	if ( $lastline ) {
		return;
	}

	// If line box contains less that two items, no justification can be done, just return
	if ( count( $box->_line ) < 2 ) {
		return;
	}

	// Calculate extra space to be filled by this line
	$delta = $box->_line_length_delta( $context );

	// note that if it is the very first line inside the container, 'text-indent' value
	// should not be taken into account while calculating delta value
	if ( count( $box->content ) > 0 ) {
		if ( $box->content[0]->uid === $box->_line[0]->uid ) {
			$delta -= $box->text_indent->calculate( $box );
		};
	};

	// if line takes less that MAX_JUSTIFY_FRACTION of available space, no justtification should be done
	if ( $delta > $box->_line_length() * MAX_JUSTIFY_FRACTION ) {
		return;
	};

	// Calculate offset for each whitespace box
	$whitespace_count = 0;
	$size             = count( $box->_line );

	// Why $size-1? Ignore whitespace box, if it is located at the very end of
	// line box

	// Also, ignore whitespace box at the very beginning of the line
	for ( $i = 1; $i < $size - 1; $i ++ ) {
		if ( is_a( $box->_line[ $i ], "WhitespaceBox" ) ) {
			$whitespace_count ++;
		};
	};

	if ( $whitespace_count > 0 ) {
		$offset = $delta / $whitespace_count;
	} else {
		$offset = 0;
	};

	// Offset all boxes in current line box
	$num_whitespaces = 0;
	$size            = count( $box->_line );
	for ( $i = 1; $i < $size; $i ++ ) {
		/*
     * Note that order is important: additional horizontal space
     * is added after the whitespace box; it is important, as
     * whitespace box (if it is the last box in the line) should not
     * run off the right edge of the container box
     */
		$box->_line[ $i ]->offset( $offset * $num_whitespaces, 0 );

		if ( is_a( $box->_line[ $i ], "WhitespaceBox" ) ) {
			$num_whitespaces ++;
		};
	};

	// The very first box is not offset in this case, so we don't need to
	// call offset_wrapped to compensate this.
}


require_once( HTML2PS_DIR . 'encoding.inc.php' );
require_once( HTML2PS_DIR . 'encoding.entities.inc.php' );
require_once( HTML2PS_DIR . 'encoding.glyphs.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-1.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-2.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-3.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-4.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-5.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-6.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-7.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-8.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-9.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-10.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-11.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-13.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-14.inc.php' );
require_once( HTML2PS_DIR . 'encoding.iso-8859-15.inc.php' );
require_once( HTML2PS_DIR . 'encoding.koi8-r.inc.php' );
require_once( HTML2PS_DIR . 'encoding.cp866.inc.php' );
require_once( HTML2PS_DIR . 'encoding.windows-1250.inc.php' );
require_once( HTML2PS_DIR . 'encoding.windows-1251.inc.php' );
require_once( HTML2PS_DIR . 'encoding.windows-1252.inc.php' );
require_once( HTML2PS_DIR . 'encoding.dingbats.inc.php' );
require_once( HTML2PS_DIR . 'encoding.symbol.inc.php' );

// TODO: this works for PS encoding names only
class ManagerEncoding {
	var $_encodings = array();

	/**
	 * Number of the current custom encoding vector
	 */
	var $_custom_vector_index = 0;

	var $_utf8_mapping;

	function ManagerEncoding() {
		$this->new_custom_encoding_vector();
	}

	/**
	 * Add  new  custom symbol  not  present  in  the existing  encoding
	 * vectors.
	 *
	 * Note:  encoding vector  this character  was placed  to  should be
	 * extracted via  get_current_custom_encoding_name immediately after
	 * add_custom_char call.
	 *
	 * @param  char[2]  $char UCS-2  character  (represented as  2-octet
	 * string)
	 *
	 * @return char index of this character in custom encoding vector
	 */
	function add_custom_char( $char ) {
		// Check if current  encoding vector is full; if  it is, we should
		// add a new one.
		if ( $this->is_custom_encoding_full() ) {
			$this->new_custom_encoding_vector();
		};

		// Get name of  the custom encoding where new  character should be
		// placed
		$vector_name = $this->get_current_custom_encoding_name();

		// Get (zero-based) index of this character in the encoding vector
		$index = count( $this->_encodings[ $vector_name ] );

		// Add new character to the custom encoding vector
		$this->_encodings[ $vector_name ][ chr( $index ) ] = $char;

		// Add new character to the UTF8 mapping table
		$this->_utf8_mapping[ code_to_utf8( $char ) ][ $vector_name ] = chr( $index );

		return chr( $index );
	}

	function generate_mapping( $mapping_file ) {
		global $g_utf8_converters;

		$this->_utf8_mapping = array();
		foreach ( array_keys( $g_utf8_converters ) as $encoding ) {
			$flipped = array_flip( $g_utf8_converters[ $encoding ][0] );
			foreach ( $flipped as $utf => $code ) {
				$this->_utf8_mapping[ code_to_utf8( $utf ) ][ $encoding ] = $code;
			};
		};

		$file = fopen( $mapping_file, 'w' );
		fwrite( $file, serialize( $this->_utf8_mapping ) );
		fclose( $file );
	}

	function &get() {
		global $g_manager_encodings;

		return $g_manager_encodings;
	}

	function get_canonized_encoding_name( $encoding ) {
		global $g_encoding_aliases;

		if ( isset( $g_encoding_aliases[ $encoding ] ) ) {
			return $g_encoding_aliases[ $encoding ];
		};

		return $encoding;
	}

	function get_current_custom_encoding_name() {
		return $this->get_custom_encoding_name( $this->get_custom_vector_index() );
	}

	function get_custom_encoding_name( $index ) {
		return sprintf( 'custom%d',
			$index );
	}

	function get_custom_vector_index() {
		return $this->_custom_vector_index;
	}

	function get_encoding_glyphs( $encoding ) {
		$vector = $this->get_encoding_vector( $encoding );
		if ( is_null( $vector ) ) {
			error_log( sprintf( "Cannot get encoding vector for encoding '%s'", $encoding ) );

			return null;
		};

		return $this->vector_to_glyphs( $vector );
	}

	/**
	 * Get  an encoding  vector  (array containing  256 elements;  every
	 * element is an ucs-2 encoded character)
	 *
	 * @param $encoding Encoding name
	 *
	 * @return Array encoding vector; null if this encoding is not known to the script
	 */
	function get_encoding_vector( $encoding ) {
		$encoding = $this->get_canonized_encoding_name( $encoding );

		global $g_utf8_converters;
		if ( isset( $g_utf8_converters[ $encoding ] ) ) {
			$vector = $g_utf8_converters[ $encoding ][0];
		} elseif ( isset( $this->_encodings[ $encoding ] ) ) {
			$vector = $this->_encodings[ $encoding ];
		} else {
			return null;
		};

		for ( $i = 0; $i <= 255; $i ++ ) {
			if ( ! isset( $vector[ chr( $i ) ] ) ) {
				$vector[ chr( $i ) ] = 0xFFFF;
			};
		};

		return $vector;
	}

	function get_glyph_to_code_mapping( $encoding ) {
		$vector = $this->get_encoding_vector( $encoding );

		$result = array();
		foreach ( $vector as $code => $uccode ) {
			if ( isset( $GLOBALS['g_unicode_glyphs'][ $uccode ] ) ) {
				$result[ $GLOBALS['g_unicode_glyphs'][ $uccode ] ][] = $code;
			};
		};

		return $result;
	}

	function get_mapping( $char ) {
		if ( ! isset( $this->_utf8_mapping ) ) {
			$this->load_mapping( CACHE_DIR . 'utf8.mappings.dat' );
		};

		if ( ! isset( $this->_utf8_mapping[ $char ] ) ) {
			return null;
		};

		return $this->_utf8_mapping[ $char ];
	}

	function get_next_utf8_char( $raw_content, &$ptr ) {
		if ( ( ord( $raw_content[ $ptr ] ) & 0xF0 ) == 0xF0 ) {
			$charlen = 4;
		} elseif ( ( ord( $raw_content[ $ptr ] ) & 0xE0 ) == 0xE0 ) {
			$charlen = 3;
		} elseif ( ( ord( $raw_content[ $ptr ] ) & 0xC0 ) == 0xC0 ) {
			$charlen = 2;
		} else {
			$charlen = 1;
		};

		$char = substr( $raw_content, $ptr, $charlen );
		$ptr  += $charlen;

		return $char;
	}

	function get_ps_encoding_vector( $encoding ) {
		$vector = $this->get_encoding_vector( $encoding );

		$result = "/" . $encoding . " [ \n";
		for ( $i = 0; $i < 256; $i ++ ) {
			if ( $i % 10 == 0 ) {
				$result .= "\n";
			};

			// ! Note the order of array checking; optimizing interpreters may break this
			if ( isset( $vector[ chr( $i ) ] ) && isset( $GLOBALS['g_unicode_glyphs'][ $vector[ chr( $i ) ] ] ) ) {
				$result .= " /" . $GLOBALS['g_unicode_glyphs'][ $vector[ chr( $i ) ] ];
			} else {
				$result .= " /.notdef";
			};
		};
		$result .= " ] readonly def";

		return $result;
	}

	function is_custom_encoding( $encoding ) {
		return preg_match( '/^custom\d+$/', $encoding );
	}

	function is_custom_encoding_full() {
		return count( $this->_encodings[ $this->get_current_custom_encoding_name() ] ) >= 256;
	}

	function load_mapping( $mapping_file ) {
		if ( ! is_readable( $mapping_file ) ) {
			$this->generate_mapping( $mapping_file );
		} else {
			$this->_utf8_mapping = unserialize( file_get_contents( $mapping_file ) );
		};
	}

	/**
	 * Create new custom  256-characters encoding vector.  Reserve first
	 * 32 symbols for system use.
	 *
	 * Custom encoding vectors have names 'customX' when X stand for the
	 * encoding index.
	 */
	function new_custom_encoding_vector() {
		$initial_vector = array();
		for ( $i = 0; $i <= 32; $i ++ ) {
			$initial_vector[ chr( $i ) ] = chr( $i );
		};
		$this->register_encoding( sprintf( 'custom%d',
			$this->next_custom_vector_index() ),
			$initial_vector );
	}

	/**
	 * Returns index for the next custom encoding
	 */
	function next_custom_vector_index() {
		return ++ $this->_custom_vector_index;
	}

	function register_encoding( $name, $vector ) {
		$this->_encodings[ $name ] = $vector;
	}

	function to_utf8( $word, $encoding ) {
		$vector = $this->get_encoding_vector( $encoding );

		$converted = '';
		for ( $i = 0, $size = strlen( $word ); $i < $size; $i ++ ) {
			$converted .= code_to_utf8( $vector[ $word{$i} ] );
		};

		return $converted;
	}

	function vector_to_glyphs( $vector ) {
		$result = array();

		foreach ( $vector as $code => $ucs2 ) {
			if ( isset( $GLOBALS['g_unicode_glyphs'][ $ucs2 ] ) ) {
				$result[ $code ] = $GLOBALS['g_unicode_glyphs'][ $ucs2 ];
			} elseif ( $ucs2 == 0xFFFF ) {
				$result[ $code ] = ".notdef";
			} else {
				// Use "Unicode and Glyph Names" mapping from Adobe
				// http://partners.adobe.com/public/developer/opentype/index_glyph.html
				$result[ $code ] = sprintf( "u%04X", $ucs2 );
			};
		};

		return $result;
	}
}

global $g_manager_encodings;
$g_manager_encodings = new ManagerEncoding;

// $Header: /cvsroot/html2ps/ps.unicode.inc.php,v 1.22 2007/01/24 18:56:10 Konstantin Exp $

// TODO: make encodings-related stuff more transparent
// function &find_vector_by_ps_name($psname) {
//   global $g_utf8_converters;

//   foreach ($g_utf8_converters as $key => $value) {
//     if ($value[1] == $psname) {
//       return $value[0];
//     };
//   };

//   return 0;
// };

$GLOBALS['g_encoding_aliases'] = array(
	'us-ascii' => 'iso-8859-1',
	'cp1250'   => 'windows-1250',
	'cp1251'   => 'windows-1251',
	'cp1252'   => 'windows-1252'
);

$GLOBALS['g_utf8_converters'] = array(
	'iso-8859-1'   => array( $GLOBALS['g_iso_8859_1'], "ISO-8859-1-Encoding" ),
	'iso-8859-2'   => array( $GLOBALS['g_iso_8859_2'], "ISO-8859-2-Encoding" ),
	'iso-8859-3'   => array( $GLOBALS['g_iso_8859_3'], "ISO-8859-3-Encoding" ),
	'iso-8859-4'   => array( $GLOBALS['g_iso_8859_4'], "ISO-8859-4-Encoding" ),
	'iso-8859-5'   => array( $GLOBALS['g_iso_8859_5'], "ISO-8859-5-Encoding" ),
	'iso-8859-6'   => array( $GLOBALS['g_iso_8859_6'], "ISO-8859-6-Encoding" ),
	'iso-8859-7'   => array( $GLOBALS['g_iso_8859_7'], "ISO-8859-7-Encoding" ),
	'iso-8859-8'   => array( $GLOBALS['g_iso_8859_8'], "ISO-8859-8-Encoding" ),
	'iso-8859-9'   => array( $GLOBALS['g_iso_8859_9'], "ISO-8859-9-Encoding" ),
	'iso-8859-10'  => array( $GLOBALS['g_iso_8859_10'], "ISO-8859-10-Encoding" ),
	'iso-8859-11'  => array( $GLOBALS['g_iso_8859_11'], "ISO-8859-11-Encoding" ),
	'iso-8859-13'  => array( $GLOBALS['g_iso_8859_13'], "ISO-8859-13-Encoding" ),
	'iso-8859-14'  => array( $GLOBALS['g_iso_8859_14'], "ISO-8859-14-Encoding" ),
	'iso-8859-15'  => array( $GLOBALS['g_iso_8859_15'], "ISO-8859-15-Encoding" ),
	'koi8-r'       => array( $GLOBALS['g_koi8_r'], "KOI8-R-Encoding" ),
	'cp866'        => array( $GLOBALS['g_cp866'], "CP-866" ),
	'windows-1250' => array( $GLOBALS['g_windows_1250'], "Windows-1250-Encoding" ),
	'windows-1251' => array( $GLOBALS['g_windows_1251'], "Windows-1251-Encoding" ),
	'windows-1252' => array( $GLOBALS['g_windows_1252'], "Windows-1252-Encoding" ),
	'symbol'       => array( $GLOBALS['g_symbol'], "Symbol-Encoding" ),
	'dingbats'     => array( $GLOBALS['g_dingbats'], "Dingbats-Encoding" )
);

// $Header: /cvsroot/html2ps/ps.utils.inc.php,v 1.10 2005/11/12 06:29:23 Konstantin Exp $

function trim_ps_comments( $data ) {
	$data = preg_replace( "/(?<!\\\\)%.*/", "", $data );

	return preg_replace( "/ +$/", "", $data );
}

function format_ps_color( $color ) {
	return sprintf( "%.3f %.3f %.3f", $color[0] / 255, $color[1] / 255, $color[2] / 255 );
}

// $Header: /cvsroot/html2ps/ps.whitespace.inc.php,v 1.6 2005/09/25 16:21:45 Konstantin Exp $


require_once( HTML2PS_DIR . 'ps.image.encoder.stream.inc.php' );

/**
 * Deprecated. Big. Slow. Causes /limitcheck Ghostcript error on big images. Use
 * another encoder.
 * @author Konstantin Bournayev
 * @version 1.0
 * @updated 24-���-2006 21:18:30
 */
class PSImageEncoderSimple extends PSImageEncoderStream {
	function PSImageEncoderSimple() {
	}

	function auto( $psdata, $src_img, &$size_x, &$size_y, &$tcolor, &$image, &$mask ) {
		if ( imagecolortransparent( $src_img ) == - 1 ) {
			$id     = $this->solid( $psdata,
				$src_img->get_handle(),
				$size_x,
				$size_y,
				$image->get_handle(),
				$mask );
			$tcolor = 0;

			return $id;
		} else {
			$id     = $this->transparent( $psdata,
				$src_img->get_handle(),
				$size_x,
				$size_y,
				$image->get_handle(),
				$mask );
			$tcolor = 1;

			return $id;
		};
	}

	function solid( $psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		$id = $this->generate_id();

		$size_x   = imagesx( $src_img );
		$size_y   = imagesy( $src_img );
		$dest_img = imagecreatetruecolor( $size_x, $size_y );

		imagecopymerge( $dest_img, $src_img, 0, 0, 0, 0, $size_x, $size_y, 100 );

		$ps_image_data = "";
		$ctr           = 1;
		$row           = 1;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Image pixel
				$rgb           = ImageColorAt( $dest_img, $x, $y );
				$r             = ( $rgb >> 16 ) & 0xFF;
				$g             = ( $rgb >> 8 ) & 0xFF;
				$b             = $rgb & 0xFF;
				$ps_image_data .= sprintf( "\\%03o\\%03o\\%03o", $r, $g, $b );

				// Write image rows
				$ctr ++;
				if ( $ctr > MAX_IMAGE_ROW_LEN || ( $x + 1 == $size_x ) ) {
					$row_next = ( $size_x - $x - 1 + $size_x * ( $size_y - $y - 1 ) == 0 ) ? 1 : $row + 1;
					$psdata->write( "/row-{$id}-{$row} { /image-{$id}-data { row-{$id}-{$row_next} } def ({$ps_image_data}) } def\n" );

					$ps_image_data = "";
					$ctr           = 1;
					$row           += 1;
				};
			};
		};

		if ( $ps_image_data ) {
			$psdata->write( "/row-{$id}-{$row}  { /image-{$id}-data { row-{$id}-1 } def ({$ps_image_data}) } def\n" );
		};

		$psdata->write( "/image-{$id}-data { row-{$id}-1 } def\n" );
		$psdata->write( "/image-{$id}-init { } def\n" );

		// return image and mask data references
		$image = "{image-{$id}-data}";
		$mask  = "";

		return $id;
	}

	function transparent( $psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		$id = $this->generate_id();

		$size_x      = imagesx( $src_img );
		$size_y      = imagesy( $src_img );
		$transparent = imagecolortransparent( $src_img );
		$dest_img    = imagecreatetruecolor( $size_x, $size_y );

		imagecopymerge( $dest_img, $src_img, 0, 0, 0, 0, $size_x, $size_y, 100 );

		$ps_image_data = "";
		$ps_mask_data  = 0xff;
		$ctr           = 1;
		$row           = 1;

		$handler          =& CSS::get_handler( CSS_BACKGROUND_COLOR );
		$background_color = $handler->get_visible_background_color();

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Image pixel
				$rgb = ImageColorAt( $dest_img, $x, $y );
				$r   = ( $rgb >> 16 ) & 0xFF;
				$g   = ( $rgb >> 8 ) & 0xFF;
				$b   = $rgb & 0xFF;

				// Mask pixel
				if ( ImageColorAt( $src_img, $x, $y ) == $transparent ) {
					$ps_mask_data = ( $ps_mask_data << 1 ) | 0x1;
					// Also, reset the image colors to the visible background to work correctly
					// while using 'transparency hack'
					$r = $background_color[0];
					$g = $background_color[1];
					$b = $background_color[2];
				} else {
					$ps_mask_data = ( $ps_mask_data << 1 ) | 0;
				};

				$ps_image_data .= sprintf( "\\%03o\\%03o\\%03o", $r, $g, $b );

				// Write mask and image rows
				$ctr ++;
				if ( $ctr > MAX_TRANSPARENT_IMAGE_ROW_LEN || ( $x + 1 == $size_x ) ) {
					while ( $ctr <= 8 ) {
						$ps_mask_data <<= 1;
						$ps_mask_data |= 1;
						$ctr ++;
					};

					$ps_mask_data_str = sprintf( "\\%03o", $ps_mask_data & 0xff );

					$row_next = ( $size_x - $x - 1 + $size_x * ( $size_y - $y - 1 ) == 0 ) ? 1 : $row + 1;

					$psdata->write( "/row-{$id}-{$row} { /image-{$id}-data { row-{$id}-{$row_next} } def ({$ps_image_data}) } def\n" );
					$psdata->write( "/mrow-{$id}-{$row} { /mask-{$id}-data { mrow-{$id}-{$row_next} } def ({$ps_mask_data_str}) } def\n" );

					$ps_image_data = "";
					$ps_mask_data  = 0xff;
					$ctr           = 1;
					$row           += 1;
				};
			};
		};

		if ( $ps_image_data ) {
			while ( $ctr <= 8 ) {
				$ps_mask_data <<= 1;
				$ps_mask_data |= 1;
				$ctr ++;
			};
			$ps_mask_data_str = sprintf( "\\%03o", $ps_mask_data & 0xFF );

			$psdata->write( "/row-{$id}-{$row} { /image-{$id}-data { row-{$id}-{$row_next} } def ({$ps_image_data}) } def\n" );
			$psdata->write( "/mrow-{$id}-{$row} { /mask-{$id}-data { mrow-{$id}-{$row_next} } def ({$ps_mask_data_str}) } def\n" );
		};

		$psdata->write( "/image-{$id}-data { row-{$id}-1 } def\n" );
		$psdata->write( "/mask-{$id}-data  { mrow-{$id}-1 } def\n" );
		$psdata->write( "/image-{$id}-init { } def\n" );

		// return image and mask data references
		$image = "{image-{$id}-data}";
		$mask  = "{mask-{$id}-data}";

		return $id;
	}

	function alpha( $psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		$id = $this->generate_id();

		$size_x = imagesx( $src_img );
		$size_y = imagesy( $src_img );

		$ps_image_data = "";
		$ps_mask_data  = 0xff;
		$ctr           = 1;
		$row           = 1;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Mask pixel
				$colors = imagecolorsforindex( $src_img, imagecolorat( $src_img, $x, $y ) );

				$a = $colors['alpha'];
				$r = $colors['red'];
				$g = $colors['green'];
				$b = $colors['blue'];

				$handler =& CSS::get_handler( CSS_BACKGROUND_COLOR );
				$bg      = $handler->get_visible_background_color();
				$r       = (int) ( $r + ( $bg[0] - $r ) * $a / 127 );
				$g       = (int) ( $g + ( $bg[1] - $g ) * $a / 127 );
				$b       = (int) ( $b + ( $bg[2] - $b ) * $a / 127 );

				$ps_image_data .= sprintf( "\\%03o\\%03o\\%03o", $r, $g, $b );

				// Write mask and image rows
				$ctr ++;
				if ( $ctr > MAX_IMAGE_ROW_LEN || ( $x + 1 == $size_x ) ) {
					$row_next = ( $size_x - $x - 1 + $size_x * ( $size_y - $y - 1 ) == 0 ) ? 1 : $row + 1;

					$psdata->write( "/row-{$id}-{$row} { /image-{$id}-data { row-{$id}-{$row_next} } def ({$ps_image_data}) } def\n" );

					$ps_image_data = "";
					$ctr           = 1;
					$row           += 1;
				};
			};
		};

		if ( $ps_image_data ) {
			$psdata->write( "/row-{$id}-{$row} { /image-{$id}-data { row-{$id}-{$row_next} } def ({$ps_image_data}) } def\n" );
		};

		$psdata->write( "/image-{$id}-data { row-{$id}-1 } def\n" );
		$psdata->write( "/image-{$id}-init { } def\n" );

		// return image and mask data references
		$image = "{image-{$id}-data}";
		$mask  = "";

		return $id;
	}

}


require_once( HTML2PS_DIR . 'ps.image.encoder.stream.inc.php' );

class PSL2ImageEncoderStream extends PSImageEncoderStream {
	function by_lines( $image, &$size_x, &$size_y ) {
		$lines = array();

		$size_x = imagesx( $image->get_handle() );
		$size_y = imagesy( $image->get_handle() );

		$dest_img = imagecreatetruecolor( $size_x, $size_y );
		imagecopymerge( $dest_img, $image->get_handle(), 0, 0, 0, 0, $size_x, $size_y, 100 );

		// initialize line length counter
		$ctr = 0;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			$line = "";

			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Save image pixel to the stream data
				$rgb  = ImageColorAt( $dest_img, $x, $y );
				$r    = ( $rgb >> 16 ) & 0xFF;
				$g    = ( $rgb >> 8 ) & 0xFF;
				$b    = $rgb & 0xFF;
				$line .= sprintf( "%02X%02X%02X", min( max( $r, 0 ), 255 ), min( max( $g, 0 ), 255 ), min( max( $b, 0 ), 255 ) );

				// Increate the line length counter; check if stream line needs to be terminated
				$ctr += 6;
				if ( $ctr > MAX_LINE_LENGTH ) {
					$line .= "\n";
					$ctr  = 0;
				}
			};

			$lines[] = $line;
		};

		return $lines;
	}
}


require_once( HTML2PS_DIR . 'ps.image.encoder.stream.inc.php' );

class PSL3ImageEncoderStream extends PSImageEncoderStream {
	function PSL3ImageEncoderStream() {
		$this->last_image_id = 0;
	}

	function auto( &$psdata, $src_img, &$size_x, &$size_y, &$tcolor, &$image, &$mask ) {
		if ( imagecolortransparent( $src_img->get_handle() ) == - 1 ) {
			$id     = $this->solid( $psdata, $src_img->get_handle(), $size_x, $size_y, $image->get_handle(), $mask );
			$tcolor = 0;

			return $id;
		} else {
			$id     = $this->transparent( $psdata, $src_img->get_handle(), $size_x, $size_y, $image->get_handle(), $mask );
			$tcolor = 1;

			return $id;
		};
	}

	// Encodes "solid" image without any transparent parts
	//
	// @param $psdata (in) Postscript file "writer" object
	// @param $src_img (in) PHP image resource
	// @param $size_x (out) size of image in pixels
	// @param $size_y (out) size of image in pixels
	// @returns identifier if encoded image to use in postscript file
	//
	function solid( &$psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		// Generate an unique image id
		$id = $this->generate_id();

		// Determine image size and create a truecolor copy of this image
		// (as we don't want to work with palette-based images manually)
		$size_x   = imagesx( $src_img );
		$size_y   = imagesy( $src_img );
		$dest_img = imagecreatetruecolor( $size_x, $size_y );
		imagecopymerge( $dest_img, $src_img, 0, 0, 0, 0, $size_x, $size_y, 100 );

		// write stread header to the postscript file
		$psdata->write( "/image-{$id}-init { image-{$id}-data 0 setfileposition } def\n" );
		$psdata->write( "/image-{$id}-data currentfile << /Filter /ASCIIHexDecode >> /ReusableStreamDecode filter\n" );

		// initialize line length counter
		$ctr = 0;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Save image pixel to the stream data
				$rgb = ImageColorAt( $dest_img, $x, $y );
				$r   = ( $rgb >> 16 ) & 0xFF;
				$g   = ( $rgb >> 8 ) & 0xFF;
				$b   = $rgb & 0xFF;
				$psdata->write( sprintf( "%02X%02X%02X", min( max( $r, 0 ), 255 ), min( max( $g, 0 ), 255 ), min( max( $b, 0 ), 255 ) ) );

				// Increate the line length counter; check if stream line needs to be terminated
				$ctr += 6;
				if ( $ctr > MAX_LINE_LENGTH ) {
					$psdata->write( "\n" );
					$ctr = 0;
				}
			};
		};

		// terminate the stream data
		$psdata->write( ">\ndef\n" );

		// return image and mask data references
		$image = "image-{$id}-data";
		$mask  = "";

		return $id;
	}

	// Encodes image containing 100% transparent color (1-bit alpha channel)
	//
	// @param $psdata (in) Postscript file "writer" object
	// @param $src_img (in) PHP image resource
	// @param $size_x (out) size of image in pixels
	// @param $size_y (out) size of image in pixels
	// @returns identifier if encoded image to use in postscript file
	//
	function transparent( &$psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		// Generate an unique image id
		$id = $this->generate_id();

		// Store transparent color for further reference
		$transparent = imagecolortransparent( $src_img );

		// Determine image size and create a truecolor copy of this image
		// (as we don't want to work with palette-based images manually)
		$size_x   = imagesx( $src_img );
		$size_y   = imagesy( $src_img );
		$dest_img = imagecreatetruecolor( $size_x, $size_y );
		imagecopymerge( $dest_img, $src_img, 0, 0, 0, 0, $size_x, $size_y, 100 );

		// write stread header to the postscript file
		$psdata->write( "/image-{$id}-init { image-{$id}-data 0 setfileposition mask-{$id}-data 0 setfileposition } def\n" );

		// Create IMAGE data stream
		$psdata->write( "/image-{$id}-data currentfile << /Filter /ASCIIHexDecode >> /ReusableStreamDecode filter\n" );

		// initialize line length counter
		$ctr = 0;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Save image pixel to the stream data
				$rgb = ImageColorAt( $dest_img, $x, $y );
				$r   = ( $rgb >> 16 ) & 0xFF;
				$g   = ( $rgb >> 8 ) & 0xFF;
				$b   = $rgb & 0xFF;

				$psdata->write( sprintf( "%02X%02X%02X", $r, $g, $b ) );

				// Increate the line length counter; check if stream line needs to be terminated
				$ctr += 6;
				if ( $ctr > MAX_LINE_LENGTH ) {
					$psdata->write( "\n" );
					$ctr = 0;
				}
			};
		};

		// terminate the stream data
		$psdata->write( ">\ndef\n" );

		// Create MASK data stream
		$psdata->write( "/mask-{$id}-data currentfile << /Filter /ASCIIHexDecode >> /ReusableStreamDecode filter\n" );

		// initialize line length counter
		$ctr = 0;

		// initialize mask bit counter
		$bit_ctr   = 0;
		$mask_data = 0xff;

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Check if this pixel should be transparent
				if ( ImageColorAt( $src_img, $x, $y ) == $transparent ) {
					$mask_data = ( $mask_data << 1 ) | 0x1;
				} else {
					$mask_data = ( $mask_data << 1 );
				};
				$bit_ctr ++;

				// If we've filled the whole byte,  write it into the mask data stream
				if ( $bit_ctr >= 8 || $x + 1 == $size_x ) {
					// Pad mask data, in case we have completed the image row
					while ( $bit_ctr < 8 ) {
						$mask_data = ( $mask_data << 1 ) | 0x01;
						$bit_ctr ++;
					};

					$psdata->write( sprintf( "%02X", $mask_data & 0xff ) );

					// Clear mask data after writing
					$mask_data = 0xff;
					$bit_ctr   = 0;

					// Increate the line length counter; check if stream line needs to be terminated
					$ctr += 1;
					if ( $ctr > MAX_LINE_LENGTH ) {
						$psdata->write( "\n" );
						$ctr = 0;
					}
				};
			};
		};

		// terminate the stream data
		// Write any incomplete mask byte to the mask data stream
		if ( $bit_ctr != 0 ) {
			while ( $bit_ctr < 8 ) {
				$mask_data <<= 1;
				$mask_data |= 1;
				$bit_ctr ++;
			}
			$psdata->write( sprintf( "%02X", $mask_data ) );
		};
		$psdata->write( ">\ndef\n" );

		// return image and mask data references
		$image = "image-{$id}-data";
		$mask  = "mask-{$id}-data";

		return $id;
	}

	function alpha( &$psdata, $src_img, &$size_x, &$size_y, &$image, &$mask ) {
		// Generate an unique image id
		$id = $this->generate_id();

		// Determine image size
		$size_x = imagesx( $src_img );
		$size_y = imagesy( $src_img );

		// write stread header to the postscript file
		$psdata->write( "/image-{$id}-init { image-{$id}-data 0 setfileposition } def\n" );
		$psdata->write( "/image-{$id}-data currentfile << /Filter /ASCIIHexDecode >> /ReusableStreamDecode filter\n" );

		// initialize line length counter
		$ctr = 0;

		// Save visible background color
		$handler =& CSS::get_handler( CSS_BACKGROUND_COLOR );
		$bg      = $handler->get_visible_background_color();

		for ( $y = 0; $y < $size_y; $y ++ ) {
			for ( $x = 0; $x < $size_x; $x ++ ) {
				// Check color/alpha of current pixels
				$colors = imagecolorsforindex( $src_img, imagecolorat( $src_img, $x, $y ) );

				$a = $colors['alpha'];
				$r = $colors['red'];
				$g = $colors['green'];
				$b = $colors['blue'];

				// Calculate approximate color
				$r = (int) ( $r + ( $bg[0] - $r ) * $a / 127 );
				$g = (int) ( $g + ( $bg[1] - $g ) * $a / 127 );
				$b = (int) ( $b + ( $bg[2] - $b ) * $a / 127 );

				// Save image pixel to the stream data
				$psdata->write( sprintf( "%02X%02X%02X", $r, $g, $b ) );

				// Increate the line length counter; check if stream line needs to be terminated
				$ctr += 6;
				if ( $ctr > MAX_LINE_LENGTH ) {
					$psdata->write( "\n" );
					$ctr = 0;
				}
			};
		};

		// terminate the stream data
		$psdata->write( ">\ndef\n" );

		// return image and mask data references
		$image = "image-{$id}-data";
		$mask  = "";

		return $id;
	}

}

// $Header: /cvsroot/html2ps/tag.body.inc.php,v 1.4 2005/07/01 18:01:58 Konstantin Exp $

// $Header: /cvsroot/html2ps/tag.font.inc.php,v 1.3 2005/06/28 15:56:09 Konstantin Exp $

// $Header: /cvsroot/html2ps/tag.frame.inc.php,v 1.19 2006/05/27 15:33:27 Konstantin Exp $

/**
 * Calculated  the actual  size of  frameset rows/columns  using value
 * specified in 'rows'  of 'cols' attribute. This value  is defined as
 * "MultiLength"; according to HTML 4.01 6.6:
 *
 * MultiLength:  The  value (  %MultiLength;  in  the  DTD) may  be  a
 * %Length; or a relative length. A relative length has the form "i*",
 * where  "i"  is an  integer.  When  allotting  space among  elements
 * competing for  that space, user  agents allot pixel  and percentage
 * lengths  first,  then divide  up  remaining  available space  among
 * relative lengths.  Each relative length  receives a portion  of the
 * available space  that is proportional to the  integer preceding the
 * "*". The  value "*" is  equivalent to "1*".  Thus, if 60  pixels of
 * space  are  available  after   the  user  agent  allots  pixel  and
 * percentage space,  and the competing  relative lengths are  1*, 2*,
 * and 3*, the 1* will be alloted 10 pixels, the 2* will be alloted 20
 * pixels, and the 3* will be alloted 30 pixels.
 *
 * @param $lengths_src String source Multilength value
 * @param $total Integer total space to be filled
 *
 * @return Array list of calculated lengths
 */
function guess_lengths( $lengths_src, $total ) {
	/**
	 * Initialization: the comma-separated list is exploded to the array
	 * of  distinct values,  list of  calculated lengths  is initialized
	 * with default (zero) values
	 */
	$lengths = explode( ",", $lengths_src );
	$values  = array();
	foreach ( $lengths as $length ) {
		$values[] = 0;
	};

	/**
	 * First pass: fixed-width sizes (%Length). There's two types of
	 * fixed widths: pixel widths and percentage widths
	 *
	 * According to HTML 4.01, 6.6:
	 *
	 * Length: The value  (%Length; in the DTD) may  be either a %Pixel;
	 * or  a   percentage  of  the  available   horizontal  or  vertical
	 * space. Thus, the value "50%" means half of the available space.
	 *
	 * Pixels:  The value  (%Pixels;  in  the DTD)  is  an integer  that
	 * represents  the   number  of   pixels  of  the   canvas  (screen,
	 * paper). Thus,  the value "50"  means fifty pixels.  For normative
	 * information  about  the definition  of  a  pixel, please  consult
	 * [CSS1].
	 */
	for ( $i = 0; $i < count( $lengths ); $i ++ ) {
		/**
		 * Remove leading/trailing spaces from current text value
		 */
		$length_src = trim( $lengths[ $i ] );

		if ( substr( $length_src, strlen( $length_src ) - 1, 1 ) == "%" ) {
			/**
			 * Percentage value
			 */
			$fraction     = substr( $length_src, 0, strlen( $length_src ) - 1 ) / 100;
			$values[ $i ] = $total * $fraction;

		} elseif ( substr( $length_src, strlen( $length_src ) - 1, 1 ) != "*" ) {
			/**
			 * Pixel value
			 */
			$values[ $i ] = px2pt( $length_src );
		};
	};

	// Second pass: relative-width columns
	$rest = $total - array_sum( $values );

	$parts = 0;
	foreach ( $lengths as $length_src ) {
		if ( substr( $length_src, strlen( $length_src ) - 1, 1 ) == "*" ) {
			$parts += max( 1, substr( $length_src, 0, strlen( $length ) - 1 ) );
		};
	};

	if ( $parts > 0 ) {
		$part_size = $rest / $parts;

		for ( $i = 0; $i < count( $lengths ); $i ++ ) {
			$length = $lengths[ $i ];

			if ( substr( $length, strlen( $length ) - 1, 1 ) == "*" ) {
				$values[ $i ] = $part_size * max( 1, substr( $length, 0, strlen( $length ) - 1 ) );
			};
		};
	};

	// Fix over/underconstrained framesets
	$width = array_sum( $values );

	if ( $width > 0 ) {
		$koeff = $total / $width;
		for ( $i = 0; $i < count( $values ); $i ++ ) {
			$values[ $i ] *= $koeff;
		};
	};

	return $values;
}


// $Header: /cvsroot/html2ps/tag.input.inc.php,v 1.21 2005/11/12 06:29:24 Konstantin Exp $

// define('DEFAULT_BUTTON_HORIZONTAL_PADDING','5 px');


// $Header: /cvsroot/html2ps/tag.img.inc.php,v 1.11 2005/11/12 06:29:24 Konstantin Exp $


// $Header: /cvsroot/html2ps/tag.select.inc.php,v 1.5 2005/09/25 16:21:45 Konstantin Exp $


// $Header: /cvsroot/html2ps/tag.span.inc.php,v 1.3 2005/08/21 08:24:35 Konstantin Exp $

// $Header: /cvsroot/html2ps/tag.table.inc.php,v 1.4 2005/09/25 16:21:45 Konstantin Exp $


// $Header: /cvsroot/html2ps/tag.td.inc.php,v 1.13 2005/09/25 16:21:45 Konstantin Exp $


// $Header: /cvsroot/html2ps/tag.utils.inc.php,v 1.3 2005/07/01 18:01:58 Konstantin Exp $

// $Header: /cvsroot/html2ps/tree.navigation.inc.php,v 1.13 2007/05/06 18:49:29 Konstantin Exp $

class TreeWalkerDepthFirst {
	var $_callback;

	function TreeWalkerDepthFirst( $callback ) {
		$this->_callback = $callback;
	}

	function run( &$node ) {
		call_user_func( $this->_callback, array( 'node' => &$node ) );
		$this->walk_element( $node );
	}

	function walk_element( &$node ) {
		if ( ! isset( $node->content ) ) {
			return;
		};

		for ( $i = 0, $size = count( $node->content ); $i < $size; $i ++ ) {
			$child =& $node->content[ $i ];
			$this->run( $child );
		};
	}
}

function &traverse_dom_tree_pdf( &$root ) {
	switch ( $root->node_type() ) {
		case XML_DOCUMENT_NODE:
			$child =& $root->first_child();
			while ( $child ) {
				$body =& traverse_dom_tree_pdf( $child );
				if ( $body ) {
					return $body;
				}
				$child =& $child->next_sibling();
			};

			$null = null;

			return $null;
		case XML_ELEMENT_NODE:
			if ( strtolower( $root->tagname() ) == "body" ) {
				return $root;
			}

			$child =& $root->first_child();
			while ( $child ) {
				$body =& traverse_dom_tree_pdf( $child );
				if ( $body ) {
					return $body;
				}
				$child =& $child->next_sibling();
			};

			$null = null;

			return $null;
		default:
			$null = null;

			return $null;
	}
}

;

function dump_tree( &$box, $level ) {
	print( str_repeat( " ", $level ) );
	if ( is_a( $box, 'TextBox' ) ) {
		print( get_class( $box ) . ":" . $box->uid . ":" . join( '/', $box->words ) . "\n" );
	} else {
		print( get_class( $box ) . ":" . $box->uid . "\n" );
	};

	if ( isset( $box->content ) ) {
		for ( $i = 0; $i < count( $box->content ); $i ++ ) {
			dump_tree( $box->content[ $i ], $level + 1 );
		};
	};
}

;


// $Header: /cvsroot/html2ps/html.attrs.inc.php,v 1.63 2007/03/15 18:37:32 Konstantin Exp $

global $g_tag_attrs;
$g_tag_attrs = array(
	/**
	 * Attribute handlers applicable to all tags
	 */
	'*'        => array(
		'id' => 'attr_id',
	),

	/**
	 * Tag-specific attribute handlers
	 */
	'a'        => array(
		'href' => 'attr_href',
		'name' => 'attr_name'
	),
	'body'     => array(
		'background'   => 'attr_background',
		'bgcolor'      => 'attr_bgcolor',
		'dir'          => 'attr_dir',
		'text'         => 'attr_body_text',
		'link'         => 'attr_body_link',
		'topmargin'    => 'attr_body_topmargin',
		'leftmargin'   => 'attr_body_leftmargin',
		'marginheight' => 'attr_body_marginheight',
		'marginwidth'  => 'attr_body_marginwidth'
	),
	'div'      => array(
		'align' => 'attr_align'
	),
	'font'     => array(
		'size'  => 'attr_font_size',
		'color' => 'attr_font_color',
		'face'  => 'attr_font_face'
	),
	'form'     => array(
		'action' => 'attr_form_action'
	),
	'frame'    => array(
		'frameborder'  => 'attr_frameborder',
		'marginwidth'  => 'attr_iframe_marginwidth',
		'marginheight' => 'attr_iframe_marginheight'
	),
	'frameset' => array(
		'frameborder' => 'attr_frameborder'
	),
	'h1'       => array(
		'align' => 'attr_align'
	),
	'h2'       => array(
		'align' => 'attr_align'
	),
	'h3'       => array(
		'align' => 'attr_align'
	),
	'h4'       => array(
		'align' => 'attr_align'
	),
	'h5'       => array(
		'align' => 'attr_align'
	),
	'h6'       => array(
		'align' => 'attr_align'
	),
	'hr'       => array(
		'align' => 'attr_self_align',
		'width' => 'attr_width',
		'color' => 'attr_hr_color'
	),
	'input'    => array(
		'name' => 'attr_input_name',
		'size' => 'attr_input_size'
	),
	'iframe'   => array(
		'frameborder'  => 'attr_frameborder',
		'marginwidth'  => 'attr_iframe_marginwidth',
		'marginheight' => 'attr_iframe_marginheight',
		'height'       => 'attr_height_required',
		'width'        => 'attr_width'
	),
	'img'      => array(
		'width'  => 'attr_width',
		'height' => 'attr_height_required',
		'border' => 'attr_border',
		'hspace' => 'attr_hspace',
		'vspace' => 'attr_vspace',
		'align'  => 'attr_img_align'
	),
	'marquee'  => array(
		'width'  => 'attr_width',
		'height' => 'attr_height_required'
	),
	'object'   => array(
		'width'  => 'attr_width',
		'height' => 'attr_height'
	),
	'ol'       => array(
		'start' => 'attr_start',
		'type'  => 'attr_ol_type'
	),
	'p'        => array(
		'align' => 'attr_align'
	),
	'table'    => array(
		'border'      => 'attr_table_border',
		'bordercolor' => 'attr_table_bordercolor',
		'align'       => 'attr_table_float_align',
		'bgcolor'     => 'attr_bgcolor',
		'width'       => 'attr_width',
		'background'  => 'attr_background',
		'height'      => 'attr_height',
		'cellspacing' => 'attr_cellspacing',
		'cellpadding' => 'attr_cellpadding',
		'rules'       => 'attr_table_rules' // NOTE that 'rules' should appear _after_ 'border' handler!
	),
	'td'       => array(
		'align'      => 'attr_align',
		'valign'     => 'attr_valign',
		'height'     => 'attr_height',
		'background' => 'attr_background',
		'bgcolor'    => 'attr_bgcolor',
		'nowrap'     => 'attr_nowrap',
		'width'      => 'attr_width'
	),
	'textarea' => array(
		'rows' => 'attr_textarea_rows',
		'cols' => 'attr_textarea_cols'
	),
	'th'       => array(
		'align'      => 'attr_align',
		'valign'     => 'attr_valign',
		'height'     => 'attr_height',
		'background' => 'attr_background',
		'bgcolor'    => 'attr_bgcolor',
		'nowrap'     => 'attr_nowrap',
		'width'      => 'attr_width'
	),
	'tr'       => array(
		'align'   => 'attr_align',
		'bgcolor' => 'attr_bgcolor',
		'valign'  => 'attr_row_valign',
		'height'  => 'attr_height'
	),
	'ul'       => array(
		'start' => 'attr_start',
		'type'  => 'attr_ul_type'
	)
);


function execute_attrs_before( $root, &$pipeline ) {
	execute_attrs( $root, '_before', $pipeline );
}

function execute_attrs_after( $root, &$pipeline ) {
	execute_attrs( $root, '_after', $pipeline );
}

function execute_attrs_after_styles( $root, &$pipeline ) {
	execute_attrs( $root, '_after_styles', $pipeline );
}

function execute_attrs( &$root, $suffix, &$pipeline ) {
	global $g_tag_attrs;

	foreach ( $g_tag_attrs['*'] as $attr => $fun ) {
		if ( $root->has_attribute( $attr ) ) {
			$fun = $fun . $suffix;
			$fun( $root, $pipeline );
		};
	};

	if ( array_key_exists( $root->tagname(), $g_tag_attrs ) ) {
		foreach ( $g_tag_attrs[ $root->tagname() ] as $attr => $fun ) {
			if ( $root->has_attribute( $attr ) ) {
				$fun = $fun . $suffix;
				$fun( $root, $pipeline );
			};
		};
	};
}

;

// ========= Handlers

// A NAME
function attr_name_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HTML2PS_LINK_DESTINATION );
	$handler->css( $root->get_attribute( 'name' ), $pipeline );
}

function attr_name_after_styles( &$root, &$pipeline ) {
}

;
function attr_name_after( &$root, &$pipeline ) {
}

;

// A ID
function attr_id_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HTML2PS_LINK_DESTINATION );
	$handler->css( $root->get_attribute( 'id' ), $pipeline );
}

function attr_id_after_styles( &$root, &$pipeline ) {
}

;
function attr_id_after( &$root, &$pipeline ) {
}

;


// A HREF
function attr_href_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HTML2PS_LINK_TARGET );
	$handler->css( $root->get_attribute( 'href' ), $pipeline );
}

function attr_href_after_styles( &$root, &$pipeline ) {
}

;
function attr_href_after( &$root, &$pipeline ) {
}

;

// IFRAME
function attr_frameborder_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();
	$handler   =& CSS::get_handler( CSS_BORDER );

	switch ( $root->get_attribute( 'frameborder' ) ) {
		case '1':
			$handler->css( 'inset black 1px', $pipeline );
			break;
		case '0':
			$handler->css( 'none', $pipeline );
			break;
	};
}

function attr_frameborder_after_styles( &$root, &$pipeline ) {
}

;
function attr_frameborder_after( &$root, &$pipeline ) {
}

;

function attr_iframe_marginheight_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_PADDING_TOP );
	$handler->css( (int) $root->get_attribute( 'marginheight' ) . 'px', $pipeline );
	$handler =& CSS::get_handler( CSS_PADDING_BOTTOM );
	$handler->css( (int) $root->get_attribute( 'marginheight' ) . 'px', $pipeline );
}

function attr_iframe_marginheight_after_styles( &$root, &$pipeline ) {
}

;
function attr_iframe_marginheight_after( &$root, &$pipeline ) {
}

;

function attr_iframe_marginwidth_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_PADDING_RIGHT );
	$handler->css( (int) $root->get_attribute( 'marginwidth' ) . 'px', $pipeline );
	$handler =& CSS::get_handler( CSS_PADDING_LEFT );
	$handler->css( (int) $root->get_attribute( 'marginwidth' ) . 'px', $pipeline );
}

function attr_iframe_marginwidth_after_styles( &$root, &$pipeline ) {
}

;
function attr_iframe_marginwidth_after( &$root, &$pipeline ) {
}

;


// BODY-specific
function attr_body_text_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_COLOR );
	$handler->css( $root->get_attribute( 'text' ), $pipeline );
}

function attr_body_text_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_text_after( &$root, &$pipeline ) {
}

;

function attr_body_link_before( &$root, &$pipeline ) {
	$color = $root->get_attribute( 'link' );

	// -1000 means priority modifier; so, any real CSS rule will have more priority than
	// this fake rule

	$collection = new CSSPropertyCollection();
	$collection->add_property( CSSPropertyDeclaration::create( CSS_COLOR, $color, $pipeline ) );
	$rule = array(
		array(
			SELECTOR_SEQUENCE,
			array(
				array( SELECTOR_TAG, 'a' ),
				array( SELECTOR_PSEUDOCLASS_LINK_LOW_PRIORITY )
			)
		),
		$collection,
		'',
		- 1000
	);

	$css =& $pipeline->get_current_css();
	$css->add_rule( $rule, $pipeline );
}

function attr_body_link_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_link_after( &$root, &$pipeline ) {
}

;

function attr_body_topmargin_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_MARGIN_TOP );
	$handler->css( (int) $root->get_attribute( 'topmargin' ) . 'px', $pipeline );
}

function attr_body_topmargin_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_topmargin_after( &$root, &$pipeline ) {
}

;

function attr_body_leftmargin_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_MARGIN_LEFT );
	$handler->css( (int) $root->get_attribute( 'leftmargin' ) . 'px', $pipeline );
}

function attr_body_leftmargin_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_leftmargin_after( &$root, &$pipeline ) {
}

;

function attr_body_marginheight_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();

	$h_top    =& CSS::get_handler( CSS_MARGIN_TOP );
	$h_bottom =& CSS::get_handler( CSS_MARGIN_BOTTOM );

	$top = $h_top->get( $css_state->getState() );

	$h_bottom->css( ( (int) $root->get_attribute( 'marginheight' ) - $top->value ) . 'px', $pipeline );
}

function attr_body_marginheight_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_marginheight_after( &$root, &$pipeline ) {
}

;

function attr_body_marginwidth_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();

	$h_left  =& CSS::get_handler( CSS_MARGIN_LEFT );
	$h_right =& CSS::get_handler( CSS_MARGIN_RIGHT );

	$left = $h_left->get( $css_state->getState() );

	$h_right->css( ( (int) $root->get_attribute( 'marginwidth' ) - $left->value ) . 'px', $pipeline );
}

function attr_body_marginwidth_after_styles( &$root, &$pipeline ) {
}

;
function attr_body_marginwidth_after( &$root, &$pipeline ) {
}

;

// === nowrap
function attr_nowrap_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();
	$css_state->set_property( CSS_HTML2PS_NOWRAP, NOWRAP_NOWRAP );
}

function attr_nowrap_after_styles( &$root, &$pipeline ) {
}

function attr_nowrap_after( &$root, &$pipeline ) {
}

// === hspace

function attr_hspace_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_PADDING_LEFT );
	$handler->css( (int) $root->get_attribute( 'hspace' ) . 'px', $pipeline );
	$handler =& CSS::get_handler( CSS_PADDING_RIGHT );
	$handler->css( (int) $root->get_attribute( 'hspace' ) . 'px', $pipeline );
}

function attr_hspace_after_styles( &$root, &$pipeline ) {
}

function attr_hspace_after( &$root, &$pipeline ) {
}

// === vspace

function attr_vspace_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_PADDING_TOP );
	$handler->css( (int) $root->get_attribute( 'vspace' ) . 'px', $pipeline );
	$handler =& CSS::get_handler( CSS_PADDING_BOTTOM );
	$handler->css( (int) $root->get_attribute( 'vspace' ) . 'px', $pipeline );
}

function attr_vspace_after_styles( &$root, &$pipeline ) {
}

function attr_vspace_after( &$root, &$pipeline ) {
}

// === background

function attr_background_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_BACKGROUND_IMAGE );
	$handler->css( 'url(' . $root->get_attribute( 'background' ) . ')', $pipeline );
}

function attr_background_after_styles( &$root, &$pipeline ) {
}

function attr_background_after( &$root, &$pipeline ) {
}

// === align

function attr_table_float_align_before( &$root, &$pipeline ) {
}

function attr_table_float_align_after_styles( &$root, &$pipeline ) {
	if ( $root->get_attribute( 'align' ) === 'center' ) {
		$margin_left =& CSS::get_handler( CSS_MARGIN_LEFT );
		$margin_left->css( 'auto', $pipeline );

		$margin_right =& CSS::get_handler( CSS_MARGIN_RIGHT );
		$margin_right->css( 'auto', $pipeline );
	} else {
		$float     =& CSS::get_handler( CSS_FLOAT );
		$css_state =& $pipeline->get_current_css_state();
		$float->replace( $float->parse( $root->get_attribute( 'align' ) ),
			$css_state );
	};
}

function attr_table_float_align_after( &$root, &$pipeline ) {
}

function attr_img_align_before( &$root, &$pipeline ) {
	if ( preg_match( '/left|right/', $root->get_attribute( 'align' ) ) ) {
		$float     =& CSS::get_handler( CSS_FLOAT );
		$css_state =& $pipeline->get_current_css_state();
		$float->replace( $float->parse( $root->get_attribute( 'align' ) ),
			$css_state );
	} else {
		$handler   =& CSS::get_handler( CSS_VERTICAL_ALIGN );
		$css_state =& $pipeline->get_current_css_state();
		$handler->replace( $handler->parse( $root->get_attribute( 'align' ) ),
			$css_state );
	};
}

function attr_img_align_after_styles( &$root, &$pipeline ) {
}

function attr_img_align_after( &$root, &$pipeline ) {
}

function attr_self_align_before( &$root, &$pipeline ) {
	$handler   =& CSS::get_handler( CSS_HTML2PS_LOCALALIGN );
	$css_state =& $pipeline->get_current_css_state();

	switch ( $root->get_attribute( 'align' ) ) {
		case 'left':
			$handler->replace( LA_LEFT,
				$css_state );
			break;
		case 'center':
			$handler->replace( LA_CENTER,
				$css_state );
			break;
		case 'right':
			$handler->replace( LA_RIGHT,
				$css_state );
			break;
		default:
			$handler->replace( LA_LEFT,
				$css_state );
			break;
	};
}

function attr_self_align_after_styles( &$root, &$pipeline ) {
}

function attr_self_align_after( &$root, &$pipeline ) {
}

// === bordercolor

function attr_table_bordercolor_before( &$root, &$pipeline ) {
	$color = parse_color_declaration( $root->get_attribute( 'bordercolor' ) );

	$css_state =& $pipeline->get_current_css_state();
	$border    =& $css_state->get_property( CSS_HTML2PS_TABLE_BORDER );
	$border    =& $border->copy();

	$border->left->color   = $color;
	$border->right->color  = $color;
	$border->top->color    = $color;
	$border->bottom->color = $color;

	//   $css_state->pushState();
	//   $css_state->set_property(CSS_HTML2PS_TABLE_BORDER, $border);

	//   $css_state->pushState();
	//   $css_state->set_property(CSS_BORDER, $border);
}

function attr_table_bordercolor_after_styles( &$root, &$pipeline ) {
	//   $css_state =& $pipeline->get_current_css_state();
	//   $css_state->popState();
}

function attr_table_bordercolor_after( &$root, &$pipeline ) {
	//   $css_state =& $pipeline->get_current_css_state();
	//   $css_state->popState();
}

// === border

function attr_border_before( &$root, &$pipeline ) {
	$width = (int) $root->get_attribute( 'border' );

	$css_state =& $pipeline->get_current_css_state();
	$border    =& $css_state->get_property( CSS_BORDER );
	$border    =& $border->copy();

	$border->left->width   = Value::fromData( $width, UNIT_PX );
	$border->right->width  = Value::fromData( $width, 'px' );
	$border->top->width    = Value::fromData( $width, 'px' );
	$border->bottom->width = Value::fromData( $width, 'px' );

	$border->left->style   = BS_SOLID;
	$border->right->style  = BS_SOLID;
	$border->top->style    = BS_SOLID;
	$border->bottom->style = BS_SOLID;

	$css_state->set_property( CSS_BORDER, $border );
}

function attr_border_after_styles( &$root, &$pipeline ) {
}

function attr_border_after( &$root, &$pipeline ) {
}

// === rules (table)

function attr_table_rules_before( &$root, &$pipeline ) {
	/**
	 * Handle 'rules' attribute
	 */
	$rules = $root->get_attribute( 'rules' );

	$css_state =& $pipeline->get_current_css_state();
	$border    = $css_state->get_property( CSS_HTML2PS_TABLE_BORDER );

	switch ( $rules ) {
		case 'none':
			$border->left->style   = BS_NONE;
			$border->right->style  = BS_NONE;
			$border->top->style    = BS_NONE;
			$border->bottom->style = BS_NONE;
			break;
		case 'groups':
			// Not supported
			break;
		case 'rows':
			$border->left->style  = BS_NONE;
			$border->right->style = BS_NONE;
			break;
		case 'cols':
			$border->top->style    = BS_NONE;
			$border->bottom->style = BS_NONE;
			break;
		case 'all':
			break;
	};

	$css_state->set_property( CSS_HTML2PS_TABLE_BORDER, $border );
}

function attr_table_rules_after_styles( &$root, &$pipeline ) {
}

function attr_table_rules_after( &$root, &$pipeline ) {
}

// === border (table)

function attr_table_border_before( &$root, &$pipeline ) {
	$width = (int) $root->get_attribute( 'border' );

	$css_state =& $pipeline->get_current_css_state();
	$border    =& $css_state->get_property( CSS_HTML2PS_TABLE_BORDER );
	$border    =& $border->copy();

	$border->left->width   = Value::fromData( $width, UNIT_PX );
	$border->right->width  = Value::fromData( $width, UNIT_PX );
	$border->top->width    = Value::fromData( $width, UNIT_PX );
	$border->bottom->width = Value::fromData( $width, UNIT_PX );

	$border->left->style   = BS_SOLID;
	$border->right->style  = BS_SOLID;
	$border->top->style    = BS_SOLID;
	$border->bottom->style = BS_SOLID;

	$css_state->set_property( CSS_BORDER, $border );

	$css_state->pushState();
	$border =& $border->copy();
	$css_state->set_property( CSS_HTML2PS_TABLE_BORDER, $border );
}

function attr_table_border_after_styles( &$root, &$pipeline ) {
}

function attr_table_border_after( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();
	$css_state->popState();
}

// === dir
function attr_dir_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_TEXT_ALIGN );
	switch ( strtolower( $root->get_attribute( 'dir' ) ) ) {
		case 'ltr':
			$handler->css( 'left', $pipeline );

			return;
		case 'rtl':
			$handler->css( 'right', $pipeline );

			return;
	};
}

function attr_dir_after_styles( &$root, &$pipeline ) {
}

function attr_dir_after( &$root, &$pipeline ) {
}

// === align
function attr_align_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_TEXT_ALIGN );
	$handler->css( $root->get_attribute( 'align' ), $pipeline );

	$handler =& CSS::get_handler( CSS_HTML2PS_ALIGN );
	$handler->css( $root->get_attribute( 'align' ), $pipeline );
}

function attr_align_after_styles( &$root, &$pipeline ) {
}

function attr_align_after( &$root, &$pipeline ) {
}

// valign
// 'valign' attribute value for table rows is inherited
function attr_row_valign_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_VERTICAL_ALIGN );
	$handler->css( $root->get_attribute( 'valign' ), $pipeline );
}

function attr_row_valign_after_styles( &$root, &$pipeline ) {
}

function attr_row_valign_after( &$root, &$pipeline ) {
}

// 'valign' attribute value for boxes other than table rows is not inherited
function attr_valign_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_VERTICAL_ALIGN );
	$handler->css( $root->get_attribute( 'valign' ),
		$pipeline );
}

function attr_valign_after_styles( &$root, &$pipeline ) {
}

function attr_valign_after( &$root, &$pipeline ) {
}

// bgcolor

function attr_bgcolor_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_BACKGROUND_COLOR );
	$handler->css( $root->get_attribute( 'bgcolor' ), $pipeline );
}

function attr_bgcolor_after_styles( &$root, &$pipeline ) {
}

function attr_bgcolor_after( &$root, &$pipeline ) {
}

// width

function attr_width_before( &$root, &$pipeline ) {
	$width =& CSS::get_handler( CSS_WIDTH );

	$value = $root->get_attribute( 'width' );
	if ( preg_match( '/^\d+$/', $value ) ) {
		$value .= 'px';
	};

	$width->css( $value, $pipeline );
}

function attr_width_after_styles( &$root, &$pipeline ) {
}

function attr_width_after( &$root, &$pipeline ) {
}

// height

// Difference between 'attr_height' and 'attr_height_required':
// attr_height sets the minimal box height so that is cal be expanded by it content;
// a good example is table rows and cells; on the other side, attr_height_required
// sets the fixed box height - it is useful for boxes which content height can be greater
// that box height - marquee or iframe, for example

function attr_height_required_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HEIGHT );

	$value = $root->get_attribute( 'height' );
	if ( preg_match( '/^\d+$/', $value ) ) {
		$value .= 'px';
	};
	$handler->css( $value, $pipeline );
}

function attr_height_required_after_styles( &$root, &$pipeline ) {
}

function attr_height_required_after( &$root, &$pipeline ) {
}

function attr_height_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_MIN_HEIGHT );

	$value = $root->get_attribute( 'height' );
	if ( preg_match( '/^\d+$/', $value ) ) {
		$value .= 'px';
	};
	$handler->css( $value, $pipeline );
}

function attr_height_after_styles( &$root, &$pipeline ) {
}

function attr_height_after( &$root, &$pipeline ) {
}

// FONT attributes
function attr_font_size_before( &$root, &$pipeline ) {
	$size = $root->get_attribute( 'size' );

	/**
	 * Check if attribute value is empty; no actions will be taken in this case
	 */
	if ( $size == '' ) {
		return;
	};

	if ( $size{0} == '-' ) {
		$koeff   = 1;
		$repeats = (int) substr( $size, 1 );
		for ( $i = 0; $i < $repeats; $i ++ ) {
			$koeff *= 1 / 1.2;
		};
		$newsize = sprintf( '%.2fem', round( $koeff, 2 ) );
	} else if ( $size{0} == '+' ) {
		$koeff   = 1;
		$repeats = (int) substr( $size, 1 );
		for ( $i = 0; $i < $repeats; $i ++ ) {
			$koeff *= 1.2;
		};
		$newsize = sprintf( '%.2fem', round( $koeff, 2 ) );
	} else {
		switch ( (int) $size ) {
			case 1:
				$newsize = BASE_FONT_SIZE_PT / 1.2 / 1.2;
				break;
			case 2:
				$newsize = BASE_FONT_SIZE_PT / 1.2;
				break;
			case 3:
				$newsize = BASE_FONT_SIZE_PT;
				break;
			case 4:
				$newsize = BASE_FONT_SIZE_PT * 1.2;
				break;
			case 5:
				$newsize = BASE_FONT_SIZE_PT * 1.2 * 1.2;
				break;
			case 6:
				$newsize = BASE_FONT_SIZE_PT * 1.2 * 1.2 * 1.2;
				break;
			case 7:
				$newsize = BASE_FONT_SIZE_PT * 1.2 * 1.2 * 1.2 * 1.2;
				break;
			default:
				$newsize = BASE_FONT_SIZE_PT;
				break;
		};
		$newsize = $newsize . 'pt';
	};

	$handler =& CSS::get_handler( CSS_FONT_SIZE );
	$handler->css( $newsize, $pipeline );
}

function attr_font_size_after_styles( &$root, &$pipeline ) {
}

function attr_font_size_after( &$root, &$pipeline ) {
}

function attr_font_color_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_COLOR );
	$handler->css( $root->get_attribute( 'color' ), $pipeline );
}

function attr_font_color_after_styles( &$root, &$pipeline ) {
}

function attr_font_color_after( &$root, &$pipeline ) {
}

function attr_font_face_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_FONT_FAMILY );
	$handler->css( $root->get_attribute( 'face' ), $pipeline );
}

function attr_font_face_after_styles( &$root, &$pipeline ) {
}

function attr_font_face_after( &$root, &$pipeline ) {
}

function attr_form_action_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HTML2PS_FORM_ACTION );
	if ( $root->has_attribute( 'action' ) ) {
		$handler->css( $pipeline->guess_url( $root->get_attribute( 'action' ) ), $pipeline );
	} else {
		$handler->css( null, $pipeline );
	};
}

function attr_form_action_after_styles( &$root, &$pipeline ) {
}

function attr_form_action_after( &$root, &$pipeline ) {
}

function attr_input_name_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HTML2PS_FORM_RADIOGROUP );
	if ( $root->has_attribute( 'name' ) ) {
		$handler->css( $root->get_attribute( 'name' ), $pipeline );
	};
}

function attr_input_name_after_styles( &$root, &$pipeline ) {
}

function attr_input_name_after( &$root, &$pipeline ) {
}

function attr_input_size_before( &$root, &$pipeline ) {
	// Check if current node has 'size' attribute
	if ( ! $root->has_attribute( 'size' ) ) {
		return;
	};
	$size = $root->get_attribute( 'size' );

	// Get the exact type of the input node, as 'size' has
	// different meanings for different input types
	$type = 'text';
	if ( $root->has_attribute( 'type' ) ) {
		$type = strtolower( $root->get_attribute( 'type' ) );
	};

	switch ( $type ) {
		case 'text':
		case 'password':
			$handler =& CSS::get_handler( CSS_WIDTH );
			$width   = sprintf( '%.2fem', INPUT_SIZE_BASE_EM + $size * INPUT_SIZE_EM_KOEFF );
			$handler->css( $width, $pipeline );
			break;
	};
}

;

function attr_input_size_after_styles( &$root, &$pipeline ) {
}

function attr_input_size_after( &$root, &$pipeline ) {
}

// TABLE

function attr_cellspacing_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();
	$handler   =& CSS::get_handler( CSS_HTML2PS_CELLSPACING );
	$handler->replace( Value::fromData( (int) $root->get_attribute( 'cellspacing' ), UNIT_PX ),
		$css_state );
}

function attr_cellspacing_after_styles( &$root, &$pipeline ) {
}

function attr_cellspacing_after( &$root, &$pipeline ) {
}

function attr_cellpadding_before( &$root, &$pipeline ) {
	$css_state =& $pipeline->get_current_css_state();
	$handler   =& CSS::get_handler( CSS_HTML2PS_CELLPADDING );
	$handler->replace( Value::fromData( (int) $root->get_attribute( 'cellpadding' ), UNIT_PX ),
		$css_state );
}

function attr_cellpadding_after_styles( &$root, &$pipeline ) {
}

function attr_cellpadding_after( &$root, &$pipeline ) {
}

// UL/OL 'start' attribute
function attr_start_before( &$root, &$pipeline ) {
	$handler   =& CSS::get_handler( CSS_HTML2PS_LIST_COUNTER );
	$css_state =& $pipeline->get_current_css_state();
	$handler->replace( (int) $root->get_attribute( 'start' ),
		$css_state );
}

function attr_start_after_styles( &$root, &$pipeline ) {
}

function attr_start_after( &$root, &$pipeline ) {
}

// UL 'type' attribute
//
// For  the UL  element, possible  values for  the type  attribute are
// disc, square, and circle. The default value depends on the level of
// nesting of the current list. These values are case-insensitive.
//
// How each value is presented  depends on the user agent. User agents
// should attempt to  present a "disc" as a  small filled-in circle, a
// "circle"  as a  small circle  outline, and  a "square"  as  a small
// square outline.
//
function attr_ul_type_before( &$root, &$pipeline ) {
	$type      = (string) $root->get_attribute( 'type' );
	$handler   =& CSS::get_handler( CSS_LIST_STYLE_TYPE );
	$css_state =& $pipeline->get_current_css_state();

	switch ( strtolower( $type ) ) {
		case 'disc':
			$handler->replace( LST_DISC, $css_state );
			break;
		case 'circle':
			$handler->replace( LST_CIRCLE, $css_state );
			break;
		case 'square':
			$handler->replace( LST_SQUARE, $css_state );
			break;
	};
}

function attr_ul_type_after_styles( &$root, &$pipeline ) {
}

function attr_ul_type_after( &$root, &$pipeline ) {
}

// OL 'type' attribute
//
// For the OL element, possible values for the type attribute are summarized in the table below (they are case-sensitive):
// Type 	Numbering style
// 1 	arabic numbers 	1, 2, 3, ...
// a 	lower alpha 	a, b, c, ...
// A 	upper alpha 	A, B, C, ...
// i 	lower roman 	i, ii, iii, ...
// I 	upper roman 	I, II, III, ...
//
function attr_ol_type_before( &$root, &$pipeline ) {
	$type      = (string) $root->get_attribute( 'type' );
	$handler   =& CSS::get_handler( CSS_LIST_STYLE_TYPE );
	$css_state =& $pipeline->get_current_css_state();

	switch ( $type ) {
		case '1':
			$handler->replace( LST_DECIMAL, $css_state );
			break;
		case 'a':
			$handler->replace( LST_LOWER_LATIN, $css_state );
			break;
		case 'A':
			$handler->replace( LST_UPPER_LATIN, $css_state );
			break;
		case 'i':
			$handler->replace( LST_LOWER_ROMAN, $css_state );
			break;
		case 'I':
			$handler->replace( LST_UPPER_ROMAN, $css_state );
			break;
	};
}

function attr_ol_type_after_styles( &$root, &$pipeline ) {
}

function attr_ol_type_after( &$root, &$pipeline ) {
}

// Textarea

function attr_textarea_rows_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_HEIGHT );
	$handler->css( sprintf( '%dem', (int) $root->get_attribute( 'rows' ) * 1.40 ), $pipeline );
}

function attr_textarea_rows_after_styles( &$root, &$pipeline ) {
}

function attr_textarea_rows_after( &$root, &$pipeline ) {
}

function attr_textarea_cols_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_WIDTH );
	$handler->css( sprintf( '%dem', (int) $root->get_attribute( 'cols' ) * 0.675 ), $pipeline );
}

function attr_textarea_cols_after_styles( &$root, &$pipeline ) {
}

function attr_textarea_cols_after( &$root, &$pipeline ) {
}

/**
 * HR-specific attributes
 */
function attr_hr_color_before( &$root, &$pipeline ) {
	$handler =& CSS::get_handler( CSS_BORDER_COLOR );
	$handler->css( $root->get_attribute( 'color' ), $pipeline );
}

function attr_hr_color_after_styles( &$root, &$pipeline ) {
}

function attr_hr_color_after( &$root, &$pipeline ) {
}


// $Header: /cvsroot/html2ps/xhtml.autoclose.inc.php,v 1.5 2005/07/28 17:04:33 Konstantin Exp $

function autoclose_tag( &$sample_html, $offset, $tags, $nested, $close ) {
	$tags = mk_open_tag_regexp( $tags );

	while ( preg_match( "#^(.*?)({$tags})#is", substr( $sample_html, $offset ), $matches ) ) {
		// convert tag name found to lower case
		$tag = strtolower( $matches[3] );
		// calculate position of the tag found
		$tag_start = $offset + strlen( $matches[1] );
		$tag_end   = $tag_start + strlen( $matches[2] );

		if ( $tag == $close ) {
			return $tag_end;
		};

		// REQ: PHP 4.0.5
		if ( isset( $nested[ $tag ] ) ) {
			$offset = $nested[ $tag ]( $sample_html, $tag_end );
		} else {
			$to_be_inserted = "<" . $close . ">";

			$sample_html = substr_replace( $sample_html, $to_be_inserted, $tag_start, 0 );

			return $tag_start + strlen( $to_be_inserted );
		};
	};

	return $offset;
}

// removes from current html string a piece from the current $offset to
// the beginning of next $tag; $tag should contain a '|'-separated list
// of opening or closing tags. This function is useful for cleaning up
// messy code containing trash between TD, TR and TABLE tags.
function skip_to( &$html, $offset, $tag ) {
	$prefix = substr( $html, 0, $offset );
	$suffix = substr( $html, $offset );

	if ( preg_match( "#^(.*?)<\s*({$tag})#is", $suffix, $matches ) ) {
		$suffix = substr( $suffix, strlen( $matches[1] ) );
	};

	$html = $prefix . $suffix;
}

function autoclose_tag_cleanup( &$sample_html, $offset, $tags_raw, $nested, $close ) {
	$tags = mk_open_tag_regexp( $tags_raw );
	skip_to( $sample_html, $offset, $tags_raw );

	while ( preg_match( "#^(.*?)({$tags})#is", substr( $sample_html, $offset ), $matches ) ) {
		// convert tag name found to lower case
		$tag = strtolower( $matches[3] );
		// calculate position of the tag found
		$tag_start = $offset + strlen( $matches[1] );
		$tag_end   = $tag_start + strlen( $matches[2] );

		if ( $tag == $close ) {
			return $tag_end;
		};

		// REQ: PHP 4.0.5
		if ( isset( $nested[ $tag ] ) ) {
			$offset = $nested[ $tag ]( $sample_html, $tag_end );
		} else {
			$to_be_inserted = "<" . $close . ">";

			$sample_html = substr_replace( $sample_html, $to_be_inserted, $tag_start, 0 );

			return $tag_start + strlen( $to_be_inserted );
		};

		skip_to( $sample_html, $offset, $tags_raw );
	};

	return $offset;
}


// $Header: /cvsroot/html2ps/xhtml.utils.inc.php,v 1.35 2007/03/15 18:37:36 Konstantin Exp $

function close_tag( $tag, $sample_html ) {
	return preg_replace( "!(<{$tag}(\s[^>]*[^/>])?)>!si", "\\1/>", $sample_html );
}

;

function make_attr_value( $attr, $html ) {
	return preg_replace( "#(<[^>]*\s){$attr}(\s|>|/>)#si", "\\1{$attr}=\"{$attr}\"\\2", $html );
}

;


function mk_open_tag_regexp( $tag ) {
	return "<\s*{$tag}(\s+[^>]*)?>";
}

;
function mk_close_tag_regexp( $tag ) {
	return "<\s*/\s*{$tag}\s*>";
}

;

function process_html( $html ) {
	$open  = mk_open_tag_regexp( "html" );
	$close = mk_close_tag_regexp( "html" );

	if ( ! preg_match( "#{$open}#is", $html ) ) {
		$html = "<html>" . $html;
	};

	/**
	 * Let's check if there's more than one <html> tags inside the page text
	 * If there are, remove everything except the first one and content between the first and second <html>
	 */
	while ( preg_match( "#{$open}(.*?){$open}#is", $html ) ) {
		$html = preg_replace( "#{$open}(.*?){$open}#is", "<html>\\2", $html );
	};

	if ( ! preg_match( "#{$close}#is", $html ) ) {
		$html = $html . "</html>";
	};

	// PHP 5.2.0 compatilibty issue
	// preg_replace may accidentally return NULL on large files not matching this
	// protect from twice processed
	$html = preg_replace( "#.*({$open})#is", "\\1", $html );

	// PHP 5.2.0 compatilibty issue
	// preg_replace may accidentally return NULL on large files not matching this

	// Cut off all data before and after 'html' tag; unless we'll do it,
	// the XML parser will die violently
	$html = preg_replace( "#^.*<html#is", "<html", $html );

	$html = preg_replace( "#</html\s*>.*$#is", "</html>", $html );

	if ( ! $html ) {
		trigger_error( 'pcre.pcre.backtrack_limit(' . ini_get( 'pcre.backtrack_limit' ) . ') and pcre.recursion_limit(' . ini_get( 'pcre.recursion_limit' ) . ') too low', E_USER_ERROR );
	}

	return $html;
}

function process_head( $html ) {
	$open  = mk_open_tag_regexp( "head" );
	$close = mk_close_tag_regexp( "head" );
	$ohtml = mk_open_tag_regexp( "html" );
	$chtml = mk_close_tag_regexp( "html" );
	$obody = mk_open_tag_regexp( "body" );

	if ( ! preg_match( "#{$open}#is", $html ) ) {
		$html = preg_replace( "#({$ohtml})(.*)({$obody})#is", "\\1<head>\\3</head>\\4", $html );
	} elseif ( ! preg_match( "#{$close}#is", $html ) ) {
		if ( preg_match( "#{$obody}#is", $html ) ) {
			$html = preg_replace( "#({$obody})#is", "</head>\\1", $html );
		} else {
			$html = preg_replace( "#({$chtml})#is", "</head>\\1", $html );
		};
	};

	return $html;
}

function process_body( $html ) {
	$open  = mk_open_tag_regexp( "body" );
	$close = mk_close_tag_regexp( "body" );
	$ohtml = mk_open_tag_regexp( "html" );
	$chtml = mk_close_tag_regexp( "html" );
	$chead = mk_close_tag_regexp( "head" );

	if ( ! preg_match( "#{$open}#is", $html ) ) {
		if ( preg_match( "#{$chead}#is", $html ) ) {
			$html = preg_replace( "#({$chead})#is", "\\1<body>", $html );
		} else {
			$html = preg_replace( "#({$ohtml})#is", "\\1<body>", $html );
		};
	};
	if ( ! preg_match( "#{$close}#is", $html ) ) {
		$html = preg_replace( "#({$chtml})#is", "</body>\\1", $html );
	};

	// Now check is there any data between </head> and <body>.
	$html = preg_replace( "#({$chead})(.+)({$open})#is", "\\1\\3\\2", $html );
	// Check if there's any data between </body> and </html>
	$html = preg_replace( "#({$close})(.+)({$chtml})#is", "\\2\\1\\3", $html );

	return $html;
}

// Hmmm. May be we'll just write SAX parser on PHP? ;-)
function fix_tags( $html ) {
	$result    = "";
	$tag_stack = array();

	// these corrections can simplify the regexp used to parse tags
	// remove whitespaces before '/' and between '/' and '>' in autoclosing tags
	$html = preg_replace( "#\s*/\s*>#is", "/>", $html );
	// remove whitespaces between '<', '/' and first tag letter in closing tags
	$html = preg_replace( "#<\s*/\s*#is", "</", $html );
	// remove whitespaces between '<' and first tag letter
	$html = preg_replace( "#<\s+#is", "<", $html );

	while ( preg_match( "#(.*?)(<([a-z\d]+)[^>]*/>|<([a-z\d]+)[^>]*(?<!/)>|</([a-z\d]+)[^>]*>)#is", $html, $matches ) ) {
		$result .= $matches[1];
		$html   = substr( $html, strlen( $matches[0] ) );

		// Closing tag
		if ( isset( $matches[5] ) ) {
			$tag = $matches[5];

			if ( $tag == $tag_stack[0] ) {
				// Matched the last opening tag (normal state)
				// Just pop opening tag from the stack
				array_shift( $tag_stack );
				$result .= $matches[2];
			} elseif ( array_search( $tag, $tag_stack ) ) {
				// We'll never should close 'table' tag such way, so let's check if any 'tables' found on the stack
				$no_critical_tags = ! array_search( 'table', $tag_stack );
				if ( ! $no_critical_tags ) {
					$no_critical_tags = ( array_search( 'table', $tag_stack ) >= array_search( $tag, $tag_stack ) );
				};

				if ( $no_critical_tags ) {
					// Corresponding opening tag exist on the stack (somewhere deep)
					// Note that we can forget about 0 value returned by array_search, becaus it is handled by previous 'if'

					// Insert a set of closing tags for all non-matching tags
					$i = 0;
					while ( $tag_stack[ $i ] != $tag ) {
						$result .= "</{$tag_stack[$i]}> ";
						$i ++;
					};

					// close current tag
					$result .= "</{$tag_stack[$i]}> ";
					// remove it from the stack
					array_splice( $tag_stack, $i, 1 );
					// if this tag is not "critical", reopen "run-off" tags
					$no_reopen_tags = array( "tr", "td", "table", "marquee", "body", "html" );
					if ( array_search( $tag, $no_reopen_tags ) === false ) {
						while ( $i > 0 ) {
							$i --;
							$result .= "<{$tag_stack[$i]}> ";
						};
					} else {
						array_splice( $tag_stack, 0, $i );
					};
				};
			} else {
				// No such tag found on the stack, just remove it (do nothing in out case, as we have to explicitly
				// add things to result
			};
		} elseif ( isset( $matches[4] ) ) {
			// Opening tag
			$tag = $matches[4];
			array_unshift( $tag_stack, $tag );
			$result .= $matches[2];
		} else {
			// Autoclosing tag; do nothing specific
			$result .= $matches[2];
		};
	};

	// Close all tags left
	while ( count( $tag_stack ) > 0 ) {
		$tag    = array_shift( $tag_stack );
		$result .= "</" . $tag . ">";
	}

	return $result;
}

/**
 * This function adds quotes to attribute values; it attribute values already have quotes, no changes are made
 */
function quote_attrs( $html ) {
	while ( preg_match( "!(<[^>]*)\s([^=>]+)=([^'\"\r\n >]+)([\r\n >])!si", $html, $matches ) ) {
		$html = preg_replace( "#(<[^>]*)\s([^=>]+)=([^'\"\r\n >]+)([\r\n >])#si", "\\1 \\2='\\3'\\4", $html );
	};

	return $html;
}

;

function escape_attr_value_entities( $html ) {
	$html = str_replace( "<", "&lt;", $html );
	$html = str_replace( ">", "&gt;", $html );

	// Replace all character references by their decimal codes
	process_character_references( $html );
	$html = escape_amp( $html );

	return $html;
}

/**
 * Updates attribute values: if there's any unescaped <, > or & symbols inside an attribute value,
 * replaces them with corresponding entity. Also note that & should not be escaped if it is already the part
 * of entity reference
 *
 * @param String $html source HTML code
 *
 * @return String updated HTML code
 */
function escape_attrs_entities( $html ) {
	$result = "";

	// Regular expression may be described as follows:
	// (<[^>]*) - something starting with < (i.e. tag name and, probably, some attribute name/values pairs
	// \s([^\s=>]+)= - space after "something", followed by attribute name (which may contain anything except spaces, = and > signs
	// (['\"])([^\3]*?)\3 - quoted attribute value; (@todo won't work with escaped quotes inside value, by the way).
	while ( preg_match( "#^(.*)(<[^>]*)\s([^\s=>]+)=(['\"])([^\\4]*?)\\4(.*)$#si", $html, $matches ) ) {
		$new_value = escape_attr_value_entities( $matches[5] );

		$result .= $matches[1] . $matches[2] . " " . $matches[3] . "=" . $matches[4] . $new_value . $matches[4];
		$html   = $matches[6];
	};

	return $result . $html;
}

;

function fix_attrs_spaces( &$html ) {
	while ( preg_match( "#(<[^>]*)\s([^\s=>]+)=\"([^\"]*?)\"([^\s])#si", $html ) ) {
		$html = preg_replace( "#(<[^>]*)\s([^\s=>]+)=\"([^\"]*?)\"([^\s])#si", "\\1 \\2=\"\\3\" \\4", $html );
	};

	while ( preg_match( "#(<[^>]*)\s([^\s=>]+)='([^']*?)'([^\s])#si", $html ) ) {
		$html = preg_replace( "#(<[^>]*)\s([^\s=>]+)='([^']*?)'([^\s])#si", "\\1 \\2='\\3' \\4", $html );
	};
}

function fix_attrs_tag( $tag ) {
	if ( preg_match( "#(<)(.*?)(/\s*>)#is", $tag, $matches ) ) {
		$prefix  = $matches[1];
		$suffix  = $matches[3];
		$content = $matches[2];
	} elseif ( preg_match( "#(<)(.*?)(>)#is", $tag, $matches ) ) {
		$prefix  = $matches[1];
		$suffix  = $matches[3];
		$content = $matches[2];
	} else {
		return;
	};

	if ( preg_match( "#^\s*(\w+)\s*(.*)\s*/\s*\$#is", $content, $matches ) ) {
		$tagname   = $matches[1];
		$raw_attrs = isset( $matches[2] ) ? $matches[2] : "";
	} elseif ( preg_match( "#^\s*(\w+)\s*(.*)\$#is", $content, $matches ) ) {
		$tagname   = $matches[1];
		$raw_attrs = isset( $matches[2] ) ? $matches[2] : "";
	} else {
		// A strange tag occurred; just remove everything
		$tagname   = "";
		$raw_attrs = "";
	};

	$attrs = array();
	while ( ! empty( $raw_attrs ) ) {
		if ( preg_match( "#^\s*(\w+?)\s*=\s*\"(.*?)\"(.*)$#is", $raw_attrs, $matches ) ) {
			$attr  = strtolower( $matches[1] );
			$value = $matches[2];

			if ( ! isset( $attrs[ $attr ] ) ) {
				$attrs[ $attr ] = $value;
			};

			$raw_attrs = $matches[3];
		} elseif ( preg_match( "#^\s*(\w+?)\s*=\s*'(.*?)'(.*)$#is", $raw_attrs, $matches ) ) {
			$attr  = strtolower( $matches[1] );
			$value = $matches[2];

			if ( ! isset( $attrs[ $attr ] ) ) {
				$attrs[ $attr ] = $value;
			};

			$raw_attrs = $matches[3];
		} elseif ( preg_match( "#^\s*(\w+?)=(\w+)(.*)$#is", $raw_attrs, $matches ) ) {
			$attr  = strtolower( $matches[1] );
			$value = $matches[2];

			if ( ! isset( $attrs[ $attr ] ) ) {
				$attrs[ $attr ] = $value;
			};

			$raw_attrs = $matches[3];
		} elseif ( preg_match( "#^\s*\S+\s+(.*)$#is", $raw_attrs, $matches ) ) {
			// Just a junk at the beginning; skip till the first space
			$raw_attrs = $matches[1];
		} else {
			$raw_attrs = "";
		};
	};

	$str = "";
	foreach ( $attrs as $key => $value ) {
		// In theory, if the garbage have been found inside the attrs section, we could get
		// and invalid attribute name here; just ignore them in this case
		if ( HTML2PS_XMLUtils::valid_attribute_name( $key ) ) {
			if ( strpos( $value, '"' ) !== false ) {
				$str .= " " . $key . "='" . $value . "'";
			} else {
				$str .= " " . $key . "=\"" . $value . "\"";
			};
		};
	};

	return $prefix . $tagname . $str . $suffix;
}

function fix_attrs( $html ) {
	$result = "";

	while ( preg_match( "#^(.*?)(<[^/].*?>)#is", $html, $matches ) ) {
		$result .= $matches[1] . fix_attrs_tag( $matches[2] );
		$html   = substr( $html, strlen( $matches[0] ) );
	};

	return $result . $html;
}

function fix_closing_tags( $html ) {
	return preg_replace( "#</\s*(\w+).*?>#", "</\\1>", $html );
}

function process_pagebreak_commands( &$html ) {
	$html = preg_replace( "#<\?page-break>|<!--NewPage-->#", "<pagebreak/>", $html );
}

function xhtml2xhtml( $html ) {
	process_pagebreak_commands( $html );
	// Remove STYLE tags for the same reason and store them in the temporary variable
	// later they will be added back to HEAD section
	$styles = process_style( $html );

	// Do HTML -> XML (XHTML) conversion
	// Convert HTML character references to their Unicode analogues
	process_character_references( $html );

	remove_comments( $html );

	// Convert all tags to lower case
	$html = lowercase_tags( $html );
	$html = lowercase_closing_tags( $html );

	// Remove SCRIPT tags
	$html = process_script( $html );

	$html = insert_styles( $html, $styles );

	return $html;
}

function html2xhtml( $html ) {
	process_pagebreak_commands( $html );

	// Remove SCRIPT tags from the page being processed, as script content may
	// mess the firther html-parsing utilities
	$html = process_script( $html );

	// Remove STYLE tags for the same reason and store them in the temporary variable
	// later they will be added back to HEAD section
	$styles = process_style( $html );

	// Convert HTML character references to their Unicode analogues
	process_character_references( $html );

	remove_comments( $html );

	fix_attrs_spaces( $html );
	$html = quote_attrs( $html );
	$html = escape_attrs_entities( $html );

	$html = lowercase_tags( $html );
	$html = lowercase_closing_tags( $html );

	$html = fix_closing_tags( $html );

	$html = close_tag( "area", $html );
	$html = close_tag( "base", $html );
	$html = close_tag( "basefont", $html );
	$html = close_tag( "br", $html );
	$html = close_tag( "col", $html );
	$html = close_tag( "embed", $html );
	$html = close_tag( "frame", $html );
	$html = close_tag( "hr", $html );
	$html = close_tag( "img", $html );
	$html = close_tag( "input", $html );
	$html = close_tag( "isindex", $html );
	$html = close_tag( "link", $html );
	$html = close_tag( "meta", $html );
	$html = close_tag( "param", $html );

	$html = make_attr_value( "checked", $html );
	$html = make_attr_value( "compact", $html );
	$html = make_attr_value( "declare", $html );
	$html = make_attr_value( "defer", $html );
	$html = make_attr_value( "disabled", $html );
	$html = make_attr_value( "ismap", $html );
	$html = make_attr_value( "multiple", $html );
	$html = make_attr_value( "nohref", $html );
	$html = make_attr_value( "noresize", $html );
	$html = make_attr_value( "noshade", $html );
	$html = make_attr_value( "nowrap", $html );
	$html = make_attr_value( "readonly", $html );
	$html = make_attr_value( "selected", $html );

	$html = process_html( $html );
	$html = process_body( $html );

	$html = process_head( $html );
	$html = process_p( $html );

	$html = escape_amp( $html );
	$html = escape_lt( $html );
	$html = escape_gt( $html );

	$html = escape_textarea_content( $html );

	process_tables( $html, 0 );

	process_lists( $html, 0 );
	process_deflists( $html, 0 );
	process_selects( $html, 0 );

	$html = fix_tags( $html );
	$html = fix_attrs( $html );

	$html = insert_styles( $html, $styles );

	return $html;
}

function escape_textarea_content( $html ) {
	preg_match_all( '#<textarea(.*)>(.*)<\s*/\s*textarea\s*>#Uis', $html, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );

	// Why cycle from the last to first match?
	// It will keep unprocessed matches offsets valid,
	// as escaped content may differ from original content in length,
	for ( $i = count( $matches ) - 1; $i >= 0; $i -- ) {
		$match           = $matches[ $i ];
		$match_offset    = $match[2][1];
		$match_content   = $match[2][0];
		$match_length    = strlen( $match_content );
		$escaped_content = preg_replace( '/&([^#])/', '&#38;\1',
			str_replace( '>', '&#62;',
				str_replace( '<', '&#60;', $match_content ) ) );
		$html            = substr_replace( $html, $escaped_content, $match_offset, $match_length );
	};

	return $html;
}

function lowercase_tags( $html ) {
	$result = "";

	while ( preg_match( "#^(.*?)(</?)([a-zA-z0-9]+)([\s>])#is", $html, $matches ) ) {
		// Drop extracted part
		$html = substr( $html, strlen( $matches[0] ) );
		// Move extracted part to the result
		$result .= $matches[1] . $matches[2] . strtolower( $matches[3] ) . $matches[4];
	};

	return $result . $html;
}

;

function lowercase_closing_tags( $html ) {
	$result = "";

	while ( preg_match( "#^(.*?)(<)([a-zA-z0-9]+)(\s*/\s*>)#is", $html, $matches ) ) {
		// Drop extracted part
		$html = substr( $html, strlen( $matches[0] ) );
		// Move extracted part to the result
		$result .= $matches[1] . $matches[2] . strtolower( $matches[3] ) . $matches[4];
	};

	return $result . $html;
}

;


// $Header: /cvsroot/html2ps/xhtml.tables.inc.php,v 1.9 2006/10/28 12:24:16 Konstantin Exp $

function process_cell( &$sample_html, $offset ) {
	$r = autoclose_tag( $sample_html, $offset,
		"(table|td|th|tr|thead|tbody|tfoot|/td|/th|/table|/thead|/tbody|/tfoot|/tr)",
		array( "table" => "process_table" ),
		"/td" );

	return $r;
}

;

function process_header_cell( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset,
		"(table|td|th|tr|thead|tbody|tfoot|/td|/th|/table|/thead|/tbody|/tfoot|/tr)",
		array( "table" => "process_table" ),
		"/th" );
}

;

function process_cell_without_row( &$html, $offset ) {
	// Insert missing <tr> tag and fall to the 'process_row'

	// get the LAST tag before offset point; it should be the TD tag outside the row
	preg_match( "#<[^>]+>$#", substr( $html, 0, $offset ), $matches );

	// Now 'matches' contains the bad TD tag (opening)

	// Insert the TR tag before the TD found
	$html = substr_replace( $html, "<tr>" . $matches[0], $offset - strlen( $matches[0] ), strlen( $matches[0] ) );

	// Restart row processing from the beginning of inserted TR (not inclusing the TR tag itself!, as it will cause the closing
	// tag to be inserted automatically)
	//
	$r = process_row( $html, $offset - strlen( $matches[0] ) + strlen( "<tr>" ) );

	return $r;
}

;

function process_row( &$sample_html, $offset ) {
	return autoclose_tag_cleanup( $sample_html, $offset,
		"(td|th|thead|tbody|tfoot|tr|/table|/thead|/tbody|/tfoot|/tr)",
		array(
			"td" => "process_cell",
			"th" => "process_header_cell"
		),
		"/tr" );
}

;


function process_rowgroup( $group, &$sample_html, $offset ) {
	return autoclose_tag_cleanup( $sample_html, $offset,
		"(thead|tbody|tfoot|td|th|tr|/table|/{$group})",
		array(
			"tr" => "process_row",
			"td" => "process_cell",
			"th" => "process_header_cell"
		),
		"/{$group}" );
}

function process_thead( &$html, $offset ) {
	return process_rowgroup( 'thead', $html, $offset );
}

function process_tbody( &$html, $offset ) {
	return process_rowgroup( 'tbody', $html, $offset );
}

function process_tfoot( &$html, $offset ) {
	return process_rowgroup( 'tfoot', $html, $offset );
}

function process_col( &$html, $offset ) {
	// As COL is self-closing tag, we just continue processing
	return $offset;
}

function process_col_without_colgroup( &$html, $offset ) {
	// Insert missing <colgroup> tag and fall to the 'process_colgroup'

	// get the LAST tag before offset point; it should be the COL tag outside the COLGROUP
	preg_match( "#<[^>]+>$#", substr( $html, 0, $offset ), $matches );

	// Now 'matches' contains this COL tag (self-closing)

	// Insert the COLGROUP tag before the COL found
	$sample_html = substr_replace( $html, "<colgroup>" . $matches[0], $offset - strlen( $matches[0] ), strlen( $matches[0] ) );

	// Restart colgroup processing from the beginning of inserted COLGROUP
	return process_colgroup( $html, $offset - strlen( $matches[0] ) );
}

function process_colgroup( &$html, $offset ) {
	return autoclose_tag_cleanup( $html, $offset,
		"(col|colgroup|thead|tbody|tfoot|tr|td|th|/colgroup)",
		array( "col" => "process_col" ),
		"/colgroup" );
}

function process_table( &$html, $offset ) {
	return autoclose_tag_cleanup( $html, $offset,
		"(col|colgroup|thead|tbody|tfoot|tr|td|th|/table)",
		array(
			"col"      => "process_col_without_colgroup",
			"colgroup" => "process_colgroup",
			"thead"    => "process_thead",
			"tbody"    => "process_tbody",
			"tfoot"    => "process_tfoot",
			"tr"       => "process_row",
			"td"       => "process_cell_without_row",
			"th"       => "process_cell_without_row"
		),
		"/table" );
}

;

function process_tables( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset,
		"(table)",
		array( "table" => "process_table" ),
		"" );
}

;


// $Header: /cvsroot/html2ps/xhtml.p.inc.php,v 1.6 2005/09/04 08:03:21 Konstantin Exp $

function process_p( $sample_html ) {
	$open_regexp  = implode( "|",
		array(
			"p",
			"dl",
			"div",
			"noscript",
			"blockquote",
			"form",
			"hr",
			"table",
			"fieldset",
			"address",
			"ul",
			"ol",
			"li",
			"h1",
			"h2",
			"h3",
			"h4",
			"h5",
			"h6",
			"pre",
			"frameset",
			"noframes"
		)
	);
	$close_regexp = implode( "|",
		array(
			"dl",
			"div",
			"noscript",
			"blockquote",
			"form",
			"hr",
			"table",
			"fieldset",
			"address",
			"ul",
			"ol",
			"li",
			"h1",
			"h2",
			"h3",
			"h4",
			"h5",
			"h6",
			"pre",
			"frameset",
			"noframes",
			"body"
		)
	);
	$open         = mk_open_tag_regexp( "(" . $open_regexp . ")" );
	$close        = mk_close_tag_regexp( "(" . $close_regexp . ")" );

	$offset = 0;
	while ( preg_match( "#^(.*?)(<\s*p(\s+[^>]*?)?>)(.*?)($open|$close)#is", substr( $sample_html, $offset ), $matches ) ) {
		if ( ! preg_match( "#<\s*/\s*p\s*>#is", $matches[3] ) ) {
			$cutpos      = $offset + strlen( $matches[1] ) + strlen( $matches[2] ) + strlen( $matches[4] );
			$sample_html = substr_replace( $sample_html, "</p>", $cutpos, 0 );
			$offset      = $cutpos + 4;
		} else {
			$offset += strlen( $matches[1] ) + 1;
		};
	};

	return $sample_html;
}

;


// $Header: /cvsroot/html2ps/xhtml.lists.inc.php,v 1.3 2005/04/27 16:27:46 Konstantin Exp $

function process_li( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(ul|ol|li|/li|/ul|/ol)",
		array(
			"ul" => "process_ul",
			"ol" => "process_ol"
		),
		"/li" );
}

;

function process_ol( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(li|/ol)",
		array( "li" => "process_li" ),
		"/ol" );
}

;

function process_ul( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(li|/ul)",
		array( "li" => "process_li" ),
		"/ul" );
}

;

function process_lists( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(ul|ol)",
		array(
			"ul" => "process_ul",
			"ol" => "process_ol"
		),
		"" );
}

;


// $Header: /cvsroot/html2ps/xhtml.deflist.inc.php,v 1.3 2005/04/27 16:27:46 Konstantin Exp $

function process_dd( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(dt|dd|dl|/dl|/dd)", array( "dl" => "process_dl" ), "/dd" );
}

function process_dt( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(dt|dd|dl|/dl|/dd)", array( "dl" => "process_dl" ), "/dt" );
}

function process_dl( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(dt|dd|/dl)",
		array(
			"dt" => "process_dt",
			"dd" => "process_dd"
		),
		"/dl" );
}

;

function process_deflists( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(dl)",
		array( "dl" => "process_dl" ),
		"" );
}

;


// $Header: /cvsroot/html2ps/xhtml.script.inc.php,v 1.2 2005/04/27 16:27:46 Konstantin Exp $

function process_script( $sample_html ) {
	return preg_replace( "#<script.*?</script>#is", "", $sample_html );
}


// $Header: /cvsroot/html2ps/xhtml.entities.inc.php,v 1.11 2006/12/24 14:42:44 Konstantin Exp $

function process_character_references( &$html ) {
	// Process symbolic character references
	global $g_html_entities;
	foreach ( $g_html_entities as $entity => $code ) {
		$html = str_replace( "&{$entity};", "&#{$code};", $html );

		// Some ill-brained webmasters write HTML symbolic references without
		// terminating semicolor (especially at www.whitehouse.gov. The following
		// replacemenet is required to fix these damaged inteties, converting them
		// to the numerical character reference.
		//
		// We use [\s<] as entity name terminator to avoid breaking up longer entity
		// names by filtering in only space or HTML-tag terminated ones.
		//
		$html = preg_replace( "/&{$entity}([\s<])/", "&#{$code};\\1", $html );
	};

	// Process hecadecimal character references
	while ( preg_match( "/&#x([[:xdigit:]]{2,4});/i", $html, $matches ) ) {
		// We cannot use plain str_replace, because 'x' symbol can be in both cases;
		// str_ireplace have appeared in PHP 5 only, so we cannot use it due the
		// compatibility problems

		$html = preg_replace( "/&#x" . $matches[1] . ";/i", "&#" . hexdec( $matches[1] ) . ";", $html );
	};
}

function escape_amp( $html ) {
	// Escape all ampersants not followed by a # sharp sign
	// Note that symbolic references were replaced by numeric before this!
	$html = preg_replace( "/&(?!#)/si", "&#38;\\1", $html );

	// Complete all numeric character references unterminated with ';'
	$html = preg_replace( "/&#(\d+)(?![\d;])/si", "&#\\1;", $html );

	// Escape all ampersants followed by # sharp and NON-DIGIT symbol
	// They we're not covered by above conversions and are not a
	// symbol reference.
	// Also, don't forget that we've used &amp;! They should not be converted too...
	//
	$html = preg_replace( "/&(?!#\d)/si", "&#38;\\1", $html );

	return $html;
}

;

function escape_lt( $html ) {
	// Why this loop is needed here?
	// The cause is that, for example, <<<a> sequence will not be replaced by
	// &lt;&lt<a>, as it should be. The regular expression matches TWO symbols
	// << (actually, first < symbold, and one following it, so, the second <
	// will not be matched when script attempt to find and replace next occurrence using 'g' regexp
	// modifier. So, we will need to check for such situations agint and, possibly, restart the
	// search and replace process.
	//
	while ( preg_match( "#<(\s*[^!/a-zA-Z])#", $html ) ) {
		$html = preg_replace( "#<(\s*[^!/a-zA-Z])#si", "&#60;\\1", $html );
	};

	while ( preg_match( "#(<[^>]*?)<#si", $html ) ) {
		$html = preg_replace( "#(<[^>]*?)<#si", "\\1&#60;", $html );
	};

	return $html;
}

;

function escape_gt( $html ) {
	$html = preg_replace( "#([^\s\da-zA-Z'\"/=-])\s*>#si", "\\1&#62;", $html );

	while ( preg_match( "#(>[^<]*?)>#si", $html ) ) {
		$html = preg_replace( "#(>[^<]*?)>#si", "\\1&#62;", $html );
	};

	return $html;
}

;


// $Header: /cvsroot/html2ps/xhtml.comments.inc.php,v 1.2 2005/04/27 16:27:46 Konstantin Exp $

function remove_comments( &$html ) {
	$html = preg_replace( "#<!--.*?-->#is", "", $html );
	$html = preg_replace( "#<!.*?>#is", "", $html );
}


// $Header: /cvsroot/html2ps/xhtml.style.inc.php,v 1.7 2007/03/15 18:37:36 Konstantin Exp $

function process_style( &$html ) {
	$styles = array();

	if ( preg_match( '#^(.*)(<style[^>]*>)(.*?)(</style>)(.*)$#is', $html, $matches ) ) {
		$styles = array_merge( array( $matches[2] . process_style_content( $matches[3] ) . $matches[4] ),
			process_style( $matches[5] ) );
		$html   = $matches[1] . $matches[5];
	};

	return $styles;
}

function process_style_content( $html ) {
	// Remove CDATA comment bounds inside the <style>...</style>
	$html = preg_replace( "#<!\[CDATA\[#", "", $html );
	$html = preg_replace( "#\]\]>#is", "", $html );

	// Remove HTML comment bounds inside the <style>...</style>
	$html = preg_replace( "#<!--#is", "", $html );
	$html = preg_replace( "#-->#is", "", $html );

	// Remove CSS comments
	$html = preg_replace( "#/\*.*?\*/#is", "", $html );

	// Force CDATA comment
	$html = '<![CDATA[' . $html . ']]>';

	return $html;
}

function insert_styles( $html, $styles ) {
	// This function is called after HTML code has been fixed; thus,
	// HEAD closing tag should be present

	$html = preg_replace( '#</head>#', join( "\n", $styles ) . "\n</head>", $html );

	return $html;
}


// $Header: /cvsroot/html2ps/xhtml.selects.inc.php,v 1.3 2005/04/27 16:27:46 Konstantin Exp $

function process_option( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(option|/select|/option)",
		array(),
		"/option" );
}

;

function process_select( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(option|/select)",
		array( "option" => "process_option" ),
		"/select" );
}

;

function process_selects( &$sample_html, $offset ) {
	return autoclose_tag( $sample_html, $offset, "(select)",
		array( "select" => "process_select" ),
		"" );
}

;


/**
 * @package HTML2PS
 * @subpackage Document
 * Contains information about the background image to be rendered.
 *
 * If box does not have any background image it will still contain the
 * BackgroundImage object having $_url member set to NULL.
 *
 * @see GenericFormattedBox
 * @see CSSBackgroundImage
 * @link http://www.w3.org/TR/CSS21/colors.html#q2 CSS 2.1 "The background"
 */
class BackgroundImage {
	/**
	 * @var string URL of the background image file (may be NULL in case no background image specified).
	 * @access private
	 */
	var $_url;

	/**
	 * @var Resource image to be displayed
	 * @access private
	 */
	var $_image;

	/**
	 * Constructs new BackgroundImage object
	 *
	 * @param string   $url URL of the image file (or NULL of no image should be rendered at all)
	 * @param resource $image image object to be displayed
	 */
	function BackgroundImage( $url, $image ) {
		$this->_url   = $url;
		$this->_image = $image;
	}

	/**
	 * "Deep copy" routine; it is required for compatibility with PHP 5
	 *
	 * @return BackgroundImage A copy of current object
	 */
	function &copy() {
		$value =& new BackgroundImage( $this->_url, $this->_image );

		return $value;
	}

	/**
	 * Checks if this value is equivalent to default value. According to CSS2, default value
	 * if the 'background-image' is 'none' - no image at all; in this case $_url member should
	 * contain NULL value.
	 *
	 * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-image CSS 2 'background-image' description
	 *
	 * @return boolean flag indicating whether this background image value is equivalent to default value
	 *
	 * @see CSSProperty::is_default()
	 * @see CSSBackgroundImage::default_value()
	 */
	function is_default() {
		return is_null( $this->_url );
	}

	/**
	 * Renders the backgroung image using the specified output driver.
	 *
	 * @param OutputDriver        $driver an output driver object
	 * @param GenericFormattedBox $box an box owning this background image
	 * @param int                 $repeat the 'background-repeat' value
	 * @param BackgroundPosition  $position the 'background-position' value
	 *
	 * @uses BackgroundPosition
	 * @uses OutputDriver
	 */
	function show( &$driver, $box, $repeat, $position, $attachment ) {
		/**
		 * If no image should be rendered, just return
		 * @see BackgroundImage::$_url
		 */
		if ( is_null( $this->_url ) ) {
			return;
		};

		if ( is_null( $this->_image ) ) {
			return;
		};

		if ( $attachment == BACKGROUND_ATTACHMENT_FIXED &&
		     $box->get_css_property( CSS_DISPLAY ) == '-body' ) {
			$media  =& $driver->get_media();
			$left   = $box->get_left_background();
			$right  = $box->get_right_background();
			$top    = $driver->offset + mm2pt( $media->margins['bottom'] ) + mm2pt( $media->real_height() );
			$bottom = $driver->offset + mm2pt( $media->margins['bottom'] );
		} else {
			$left   = $box->get_left_background();
			$right  = $box->get_right_background();
			$top    = $box->get_top_background();
			$bottom = $box->get_bottom_background();
		};

		$driver->save();

		if ( ! $GLOBALS['g_config']['debugnoclip'] ) {
			/**
			 * Setup clipping region for padding area. Note that background image is drawn in the padding
			 * area which in generic case is greater than content area.
			 *
			 * @see OutputDriver::clip()
			 *
			 * @link http://www.w3.org/TR/CSS21/box.html#box-padding-area CSS 2.1 definition of padding area
			 */
			$driver->moveto( $left, $top );
			$driver->lineto( $right, $top );
			$driver->lineto( $right, $bottom );
			$driver->lineto( $left, $bottom );
			$driver->closepath();
			$driver->clip();
		};

		/**
		 * get real image size in device points
		 *
		 * @see pt2pt()
		 * @see px2pt()
		 */
		$image_height = px2pt( $this->_image->sy() );
		$image_width  = px2pt( $this->_image->sx() );

		/**
		 * Get dimensions of the rectangle to be filled with the background image
		 */
		$padding_width  = $right - $left;
		$padding_height = $top - $bottom;

		/**
		 * Calculate the vertical offset from the top padding edge to the background image top edge using current
		 * 'background-position' value.
		 *
		 * @link file:///C:/docs/css/colors.html#propdef-background-position CSS 2 'background-position' description
		 */
		if ( $position->x_percentage ) {
			$x_offset = ( $padding_width - $image_width ) * $position->x / 100;
		} else {
			$x_offset = $position->x;
		}

		/**
		 * Calculate the horizontal offset from the left padding edge to the background image left edge using current
		 * 'background-position' value
		 *
		 * @link file:///C:/docs/css/colors.html#propdef-background-position CSS 2 'background-position' description
		 */
		if ( $position->y_percentage ) {
			$y_offset = ( $padding_height - $image_height ) * $position->y / 100;
		} else {
			$y_offset = $position->y;
		};

		/**
		 * Output the image (probably tiling it; depends on current value of 'background-repeat') using
		 * current output driver's tiled image output functions. Note that px2pt(1) is an image scaling factor; as all
		 * page element are scaled to fit the media, background images should be scaled too!
		 *
		 * @see OutputDriver::image()
		 * @see OutputDriver::image_rx()
		 * @see OutputDriver::image_ry()
		 * @see OutputDriver::image_rxry()
		 *
		 * @link file:///C:/docs/css/colors.html#propdef-background-repeat CSS 2.1 'background-repeat' property description
		 */
		switch ( $repeat ) {
			case BR_NO_REPEAT:
				/**
				 * 'background-repeat: no-repeat' case; no tiling at all
				 */
				$driver->image( $this->_image,
					$left + $x_offset,
					$top - $image_height - $y_offset,
					px2pt( 1 ) );
				break;
			case BR_REPEAT_X:
				/**
				 * 'background-repeat: repeat-x' case; horizontal tiling
				 */
				$driver->image_rx( $this->_image,
					$left + $x_offset,
					$top - $image_height - $y_offset,
					$image_width,
					$right,
					$x_offset,
					$y_offset,
					px2pt( 1 ) );
				break;
			case BR_REPEAT_Y:
				/**
				 * 'background-repeat: repeat-y' case; vertical tiling
				 */
				$driver->image_ry( $this->_image,
					$left + $x_offset,
					$top - $image_height - $y_offset,
					$image_height,
					$bottom,
					$x_offset,
					$y_offset,
					px2pt( 1 ) );
				break;
			case BR_REPEAT:
				/**
				 * 'background-repeat: repeat' case; full tiling
				 */
				$driver->image_rx_ry( $this->_image,
					$left + $x_offset,
					$top - $image_height + $y_offset,
					$image_width,
					$image_height,
					$right,
					$bottom,
					$x_offset,
					$y_offset,
					px2pt( 1 ) );
				break;
		};

		/**
		 * Restore the previous clipping area
		 *
		 * @see OutputDriver::clip()
		 * @see OutputDriver::restore()
		 */
		$driver->restore();
	}
}


/**
 * @package HTML2PS
 * @subpackage Document
 * Represents the 'background-postitions' CSS property value
 *
 * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-position CSS 2.1 'background-position' property description
 */
class BackgroundPosition {
	/**
	 * @var string X-offset value
	 * @access public
	 */
	var $x;

	/**
	 * @var string Y-offset value
	 * @access public
	 */
	var $y;

	/**
	 * @var boolean Indicates whether $x value contains absolute (false) or percentage (true) value
	 * @access public
	 */
	var $x_percentage;

	/**
	 * @var boolean Indicates whether $y value contains absolute (false) or percentage (true) value
	 * @access public
	 */
	var $y_percentage;

	/**
	 * Constructs new 'background-position' value object
	 *
	 * @param float   $x X-offset value
	 * @param boolean $x_percentage A flag indicating that $x value should be treated as percentage
	 * @param float   $y Y-offset value
	 * @param boolean $y_percentage A flag indicating that $y value should be treated as percentage
	 */
	function BackgroundPosition( $x, $x_percentage, $y, $y_percentage ) {
		$this->x            = $x;
		$this->x_percentage = $x_percentage;
		$this->y            = $y;
		$this->y_percentage = $y_percentage;
	}

	/**
	 * A "deep copy" routine; it is required for compatibility with PHP 5
	 *
	 * @return BackgroundPosition A copy of current object
	 */
	function &copy() {
		$value =& new BackgroundPosition( $this->x, $this->x_percentage,
			$this->y, $this->y_percentage );

		return $value;
	}

	/**
	 * Test is current value is equal to default 'background-position' CSS property value
	 */
	function is_default() {
		return
			$this->x == 0 &&
			$this->x_percentage &&
			$this->y == 0 &&
			$this->y_percentage;
	}

	/**
	 * Converts the absolute lengths to the device points
	 *
	 * @param float $font_size Font size to use during conversion of 'ex' and 'em' units
	 */
	function units2pt( $font_size ) {
		if ( ! $this->x_percentage ) {
			$this->x = units2pt( $this->x, $font_size );
		};

		if ( ! $this->y_percentage ) {
			$this->y = units2pt( $this->y, $font_size );
		};
	}
}


class ListStyleImage {
	var $_url;
	var $_image;

	function ListStyleImage( $url, $image ) {
		$this->_url   = $url;
		$this->_image = $image;
	}

	function &copy() {
		$value =& new ListStyleImage( $this->_url, $this->_image );

		return $value;
	}

	function is_default() {
		return is_null( $this->_url );
	}
}


// Height constraint "merging" function.
//
// Constraints have the following precedece:
// 1. constant constraint
// 2. diapason constraint
// 3. no constraint
//
// If both constraints are constant, the first one is choosen;
//
// If both constraints are diapason constraints the first one is choosen
//
function merge_height_constraint( $hc1, $hc2 ) {
	// First constraint is constant; return this, as second constraint
	// will never override it
	if ( ! is_null( $hc1->constant ) ) {
		return $hc1;
	};

	// Second constraint is constant; first is not constant;
	// return second, as it is more important
	if ( ! is_null( $hc2->constant ) ) {
		return $hc2;
	};

	// Ok, both constraints are not constant. Check if there's any diapason
	// constraints

	// Second constraint is free constraint, return first one, as
	// if it is a non-free it should have precedence, otherwise
	// it will be free constraint too
	if ( is_null( $hc2->min ) && is_null( $hc2->max ) ) {
		return $hc1;
	};

	// The same rule applied if the first constraint is free constraint
	if ( is_null( $hc1->min ) && is_null( $hc1->max ) ) {
		return $hc2;
	};

	// If we got here it means both constraints are diapason constraints.
	return $hc1;
}

// Height constraint class
//
// Height could be constrained as a percentage of the parent height OR
// as a constant value. Note that in most cases percentage constraint
// REQUIRE parent height to be constrained.
//
// Note that constraint can be given as a diapason from min to max height
// It is applied only of no strict height constraint is given
//
class HCConstraint {
	var $constant;
	var $min;
	var $max;

	function applicable( &$box ) {
		if ( ! is_null( $this->constant ) ) {
			return $this->applicable_value( $this->constant, $box );
		}

		$applicable_min = false;
		if ( ! is_null( $this->min ) ) {
			$applicable_min = $this->applicable_value( $this->min, $box );
		};

		$applicable_max = false;
		if ( ! is_null( $this->max ) ) {
			$applicable_max = $this->applicable_value( $this->max, $box );
		};

		return $applicable_min || $applicable_max;
	}

	/**
	 * Since we decided to calculate percentage constraints of the top-level boxes using
	 * the page height as the basis, all height constraint values will be applicable.
	 *
	 * In older version, percentage height constraints on top-level boxes were silently ignored and
	 * height was determined by box content
	 */
	function applicable_value( $value, &$box ) {
		return true;

		// Constant constraints always applicable
		//     if (!$value[1]) { return true; };

		//     if (!$box->parent) { return false; };
		//     return $box->parent->_height_constraint->applicable($box->parent);
	}

	function _fix_value( $value, &$box, $default, $no_table_recursion ) {
		// A percentage or immediate value?
		if ( $value[1] ) {
			// CSS 2.1: The percentage  is calculated with respect to the height of the generated box's containing block.
			// If the height of the containing  block is not specified explicitly (i.e., it  depends on  content height),
			// and this  element is  not absolutely positioned, the value is interpreted like 'auto'.

			/**
			 * Check if parent exists. If there's no parent, calculate percentage relatively to the page
			 * height (excluding top/bottom margins, of course)
			 */
			if ( ! isset( $box->parent ) || ! $box->parent ) {
				global $g_media;

				return mm2pt( $g_media->real_height() ) * $value[0] / 100;
			}

			if ( ! isset( $box->parent->parent ) || ! $box->parent->parent ) {
				global $g_media;

				return mm2pt( $g_media->real_height() ) * $value[0] / 100;
			}

			//       if (!isset($box->parent)) { return null; }
			//       if (!$box->parent) { return null; }

			// if parent does not have constrained height, return null - no height constraint can be applied
			// Table cells should be processed separately
			if ( ! $box->parent->isCell() &&
			     is_null( $box->parent->_height_constraint->constant ) &&
			     is_null( $box->parent->_height_constraint->min ) &&
			     is_null( $box->parent->_height_constraint->max ) ) {
				return $default;
			};

			if ( $box->parent->isCell() ) {
				if ( ! $no_table_recursion ) {
					$rhc = $box->parent->parent->get_rhc( $box->parent->row );
					if ( $rhc->is_null() ) {
						return $default;
					};

					return $rhc->apply( $box->parent->get_height(), $box, true ) * $value[0] / 100;
				} else {
					return $box->parent->parent->get_height() * $value[0] / 100;
				};
			};

			return $box->parent->get_height() * $value[0] / 100;
		} else {
			// Immediate
			return $value[0];
		}
	}

	function &create( &$box ) {
		// Determine if there's constant restriction
		$value = $box->get_css_property( CSS_HEIGHT );

		if ( $value->isAuto( $value ) ) {
			$constant = null;
		} elseif ( $value->isPercentage() ) {
			$constant = array( $value->getPercentage(), true );
		} else {
			$constant = array( $value->getPoints(), false );
		};

		// Determine if there's min restriction
		$value = $box->get_css_property( CSS_MIN_HEIGHT );
		if ( $value->isAuto( $value ) ) {
			$min = null;
		} elseif ( $value->isPercentage() ) {
			$min = array( $value->getPercentage(), true );
		} else {
			$min = array( $value->getPoints(), false );
		};

		// Determine if there's max restriction
		$value = $box->get_css_property( CSS_MAX_HEIGHT );
		if ( $value->isAuto( $value ) ) {
			$max = null;
		} elseif ( $value->isPercentage() ) {
			$max = array( $value->getPercentage(), true );
		} else {
			$max = array( $value->getPoints(), false );
		};

		$constraint =& new HCConstraint( $constant, $min, $max );

		return $constraint;
	}

	// Height constraint constructor
	//
	// @param $constant value of constant constraint or null of none
	// @param $min value of minimal box height or null if none
	// @param $max value of maximal box height or null if none
	//
	function HCConstraint( $constant, $min, $max ) {
		$this->constant = $constant;
		$this->min      = $min;
		$this->max      = $max;
	}

	function apply_min( $value, &$box, $no_table_recursion ) {
		if ( is_null( $this->min ) ) {
			return $value;
		} else {
			return max( $this->_fix_value( $this->min, $box, $value, $no_table_recursion ), $value );
		}
	}

	function apply_max( $value, &$box, $no_table_recursion ) {
		if ( is_null( $this->max ) ) {
			return $value;
		} else {
			return min( $this->_fix_value( $this->max, $box, $value, $no_table_recursion ), $value );
		}
	}

	function apply( $value, &$box, $no_table_recursion = false ) {
		if ( ! is_null( $this->constant ) ) {
			$height = $this->_fix_value( $this->constant, $box, $value, $no_table_recursion );
		} else {
			$height = $this->apply_min( $this->apply_max( $value, $box, $no_table_recursion ), $box, $no_table_recursion );
		}

		// Table cells contained in tables with border-collapse: separate
		// have padding included in the 'height' value. So, we'll need to subtract
		// vertical-extra from the current value to get the actual content height
		// TODO

		return $height;
	}

	function is_min_null() {
		if ( is_null( $this->min ) ) {
			return true;
		};

		return $this->min[0] == 0;
	}

	function is_null() {
		return
			is_null( $this->max ) &&
			$this->is_min_null() &&
			is_null( $this->constant );
	}
}


require_once( HTML2PS_DIR . 'width.constraint.php' );

function merge_width_constraint( $wc1, $wc2 ) {
	if ( $wc1->isNull() ) {
		return $wc2;
	};

	if ( $wc1->isConstant() && ! $wc2->isNull() ) {
		return $wc2;
	};

	if ( $wc1->isFraction() && $wc2->isFraction() ) {
		return $wc2;
	};

	return $wc1;
}

// the second parameter of 'apply' method may be null; it means that
// parent have 'fit' width and depends on the current constraint itself

class WCNone extends WidthConstraint {
	function WCNone() {
		$this->WidthConstraint();
	}

	function applicable( &$box ) {
		return false;
	}

	function _apply( $w, $pw ) {
		return $w;
	}

	function apply_inverse( $w, $pw ) {
		return $pw;
	}

	function &_copy() {
		$copy =& new WCNone();

		return $copy;
	}

	function _units2pt( $base ) {
	}

	function isNull() {
		return true;
	}
}

class WCConstant extends WidthConstraint {
	var $width;

	function WCConstant( $width ) {
		$this->WidthConstraint();
		$this->width = $width;
	}

	function applicable( &$box ) {
		return true;
	}

	function _apply( $w, $pw ) {
		return $this->width;
	}

	function apply_inverse( $w, $pw ) {
		return $pw;
	}

	function &_copy() {
		$copy =& new WCConstant( $this->width );

		return $copy;
	}

	function _units2pt( $base ) {
		$this->width = units2pt( $this->width, $base );
	}

	function isConstant() {
		return true;
	}
}

class WCFraction extends WidthConstraint {
	var $fraction;

	function applicable( &$box ) {
		if ( is_null( $box->parent ) ) {
			return false;
		};
		$parent_wc = $box->parent->get_css_property( CSS_WIDTH );

		return $box->isCell() || $parent_wc->applicable( $box->parent );
	}

	function WCFraction( $fraction ) {
		$this->WidthConstraint();
		$this->fraction = $fraction;
	}

	function _apply( $w, $pw ) {
		if ( ! is_null( $pw ) ) {
			return $pw * $this->fraction;
		} else {
			return $w;
		};
	}

	function apply_inverse( $w, $pw ) {
		if ( $this->fraction > 0 ) {
			return $w / $this->fraction;
		} else {
			return 0;
		};
	}

	function &_copy() {
		$copy =& new WCFraction( $this->fraction );

		return $copy;
	}

	function _units2pt( $base ) {
	}

	function isFraction() {
		return true;
	}
}


class CSSCounter {
	var $_name;
	var $_value;

	function CSSCounter( $name ) {
		$this->set_name( $name );
		$this->reset();
	}

	function get() {
		return $this->_value;
	}

	function get_name() {
		return $this->_name;
	}

	function reset() {
		$this->_value = 0;
	}

	function set( $value ) {
		$this->_value = $value;
	}

	function set_name( $value ) {
		$this->_name = $value;
	}
}


class CSSCounterCollection {
	var $_counters;

	function CSSCounterCollection() {
		$this->_counters = array();
	}

	function add( &$counter ) {
		$this->_counters[ $counter->get_name() ] =& $counter;
	}

	function &get( $name ) {
		if ( ! isset( $this->_counters[ $name ] ) ) {
			$null = null;

			return $null;
		};

		return $this->_counters[ $name ];
	}
}


// $Header: /cvsroot/html2ps/css.colors.inc.php,v 1.10 2007/01/24 18:55:51 Konstantin Exp $

$GLOBALS['g_colors'] = array(
	// Standard HTML colors
	"black"                => array( 0, 0, 0 ),
	"silver"               => array( 192, 192, 192 ),
	"gray"                 => array( 128, 128, 128 ),
	"white"                => array( 255, 255, 255 ),
	"maroon"               => array( 128, 0, 0 ),
	"red"                  => array( 255, 0, 0 ),
	"purple"               => array( 128, 0, 128 ),
	"fuchsia"              => array( 255, 0, 255 ),
	"green"                => array( 0, 128, 0 ),
	"lime"                 => array( 0, 255, 0 ),
	"olive"                => array( 128, 128, 0 ),
	"yellow"               => array( 255, 255, 0 ),
	"navy"                 => array( 0, 0, 128 ),
	"blue"                 => array( 0, 0, 255 ),
	"teal"                 => array( 0, 128, 128 ),
	"aqua"                 => array( 0, 255, 255 ),

	// Widely-used non-stadard color names
	"aliceblue"            => array( 240, 248, 255 ),
	"antiquewhite"         => array( 250, 235, 215 ),
	"aquamarine"           => array( 127, 255, 212 ),
	"azure"                => array( 240, 255, 255 ),
	"beige"                => array( 245, 245, 220 ),
	"bisque"               => array( 255, 228, 196 ),
	"blanchedalmond"       => array( 255, 235, 205 ),
	"blueviolet"           => array( 138, 43, 226 ),
	"brown"                => array( 165, 42, 42 ),
	"burlywood"            => array( 222, 184, 135 ),
	"cadetblue"            => array( 95, 158, 160 ),
	"chartreuse"           => array( 127, 255, 0 ),
	"chocolate"            => array( 210, 105, 30 ),
	"coral"                => array( 255, 127, 80 ),
	"cornflowerblue"       => array( 100, 149, 237 ),
	"cornsilk"             => array( 255, 248, 220 ),
	"crimson"              => array( 220, 20, 60 ),
	"cyan"                 => array( 0, 255, 255 ),
	"darkblue"             => array( 0, 0, 139 ),
	"darkcyan"             => array( 0, 139, 139 ),
	"darkgoldenrod"        => array( 184, 134, 11 ),
	"darkgray"             => array( 169, 169, 169 ),
	"darkgreen"            => array( 0, 100, 0 ),
	"darkkhaki"            => array( 189, 183, 107 ),
	"darkmagenta"          => array( 139, 0, 139 ),
	"darkolivegreen"       => array( 85, 107, 47 ),
	"darkorange"           => array( 255, 140, 0 ),
	"darkorchid"           => array( 153, 50, 204 ),
	"darkred"              => array( 139, 0, 0 ),
	"darksalmon"           => array( 233, 150, 122 ),
	"darkseagreen"         => array( 143, 188, 143 ),
	"darkslateblue"        => array( 72, 61, 139 ),
	"darkslategray"        => array( 47, 79, 79 ),
	"darkturquoise"        => array( 0, 206, 209 ),
	"darkviolet"           => array( 148, 0, 211 ),
	"deeppink"             => array( 255, 20, 147 ),
	"deepskyblue"          => array( 0, 191, 255 ),
	"dimgray"              => array( 105, 105, 105 ),
	"dodgerblue"           => array( 30, 144, 255 ),
	"firebrick"            => array( 178, 34, 34 ),
	"floralwhite"          => array( 255, 250, 240 ),
	"forestgreen"          => array( 34, 139, 34 ),
	"gainsboro"            => array( 220, 220, 220 ),
	"ghostwhite"           => array( 248, 248, 255 ),
	"gold"                 => array( 255, 215, 0 ),
	"goldenrod"            => array( 218, 165, 32 ),
	"greenyellow"          => array( 173, 255, 47 ),
	"honeydew"             => array( 240, 255, 240 ),
	"hotpink"              => array( 255, 105, 180 ),
	"indianred"            => array( 205, 92, 92 ),
	"indigo"               => array( 75, 0, 130 ),
	"ivory"                => array( 255, 255, 240 ),
	"khaki"                => array( 240, 230, 140 ),
	"lavender"             => array( 230, 230, 250 ),
	"lavenderblush"        => array( 255, 240, 245 ),
	"lawngreen"            => array( 124, 252, 0 ),
	"lemonchiffon"         => array( 255, 250, 205 ),
	"lightblue"            => array( 173, 216, 230 ),
	"lightcoral"           => array( 240, 128, 128 ),
	"lightcyan"            => array( 224, 255, 255 ),
	"lightgoldenrodyellow" => array( 250, 250, 210 ),
	"lightgreen"           => array( 144, 238, 244 ),
	"lightgrey"            => array( 211, 211, 211 ),
	"lightpink"            => array( 255, 182, 193 ),
	"lightsalmon"          => array( 255, 160, 122 ),
	"lightseagreen"        => array( 32, 178, 170 ),
	"lightskyblue"         => array( 135, 206, 250 ),
	"lightslategray"       => array( 119, 136, 153 ),
	"lightsteelblue"       => array( 176, 196, 222 ),
	"lightyellow"          => array( 255, 255, 224 ),
	"limegreen"            => array( 50, 205, 50 ),
	"linen"                => array( 250, 240, 230 ),
	"magenta"              => array( 255, 0, 255 ),
	"mediumaquamarine"     => array( 102, 205, 170 ),
	"mediumblue"           => array( 0, 0, 205 ),
	"mediumorchid"         => array( 186, 85, 211 ),
	"mediumpurple"         => array( 147, 112, 219 ),
	"mediumseagreen"       => array( 60, 179, 113 ),
	"mediumslateblue"      => array( 123, 104, 238 ),
	"mediumspringgreen"    => array( 0, 250, 154 ),
	"mediumturquoise"      => array( 72, 209, 204 ),
	"mediumvioletred"      => array( 199, 21, 133 ),
	"midnightblue"         => array( 25, 25, 112 ),
	"mintcream"            => array( 245, 255, 250 ),
	"mistyrose"            => array( 255, 228, 225 ),
	"moccasin"             => array( 255, 228, 181 ),
	"navajowhite"          => array( 255, 222, 173 ),
	"oldlace"              => array( 253, 245, 230 ),
	"olivedrab"            => array( 107, 142, 35 ),
	"orange"               => array( 255, 165, 0 ),
	"orangered"            => array( 255, 69, 0 ),
	"orchid"               => array( 218, 112, 214 ),
	"palegoldenrod"        => array( 238, 232, 170 ),
	"palegreen"            => array( 152, 251, 152 ),
	"paleturquoise"        => array( 175, 238, 238 ),
	"palevioletred"        => array( 219, 112, 147 ),
	"papayawhip"           => array( 255, 239, 213 ),
	"peachpuff"            => array( 255, 218, 185 ),
	"peru"                 => array( 205, 133, 63 ),
	"pink"                 => array( 255, 192, 203 ),
	"plum"                 => array( 221, 160, 221 ),
	"powderblue"           => array( 176, 224, 230 ),
	"rosybrown"            => array( 188, 143, 143 ),
	"royalblue"            => array( 65, 105, 225 ),
	"saddlebrown"          => array( 139, 69, 19 ),
	"salmon"               => array( 250, 128, 114 ),
	"sandybrown"           => array( 244, 164, 96 ),
	"seagreen"             => array( 46, 139, 87 ),
	"seashell"             => array( 255, 245, 238 ),
	"sienna"               => array( 160, 82, 45 ),
	"skyblue"              => array( 135, 206, 235 ),
	"slateblue"            => array( 106, 90, 205 ),
	"slategray"            => array( 112, 128, 144 ),
	"snow"                 => array( 255, 250, 250 ),
	"springgreen"          => array( 0, 255, 127 ),
	"steelblue"            => array( 70, 130, 180 ),
	"tan"                  => array( 210, 180, 140 ),
	"thistle"              => array( 216, 191, 216 ),
	"tomato"               => array( 255, 99, 71 ),
	"turquoise"            => array( 64, 224, 208 ),
	"violet"               => array( 238, 130, 238 ),
	"wheat"                => array( 245, 222, 179 ),
	"whitesmoke"           => array( 245, 245, 245 ),
	"yellowgreen"          => array( 154, 205, 50 )
);

function &parse_color_declaration( $decl ) {
	$color     = _parse_color_declaration( $decl, $success );
	$color_obj =& new Color( $color, is_transparent( $color ) );

	return $color_obj;
}

;


function _parse_color_declaration( $decl, &$success ) {
	$success = true;

	global $g_colors;
	if ( isset( $g_colors[ strtolower( $decl ) ] ) ) {
		return $g_colors[ strtolower( $decl ) ];
	};

	// Parse color keywords
	switch ( strtolower( $decl ) ) {
		case "transparent":
			return array( - 1, - 1, - 1 );
	}

	// rgb(0,0,0) form
	if ( preg_match( "/rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/", $decl, $matches ) ) {
		$r = min( 255, max( 0, $matches[1] ) );
		$g = min( 255, max( 0, $matches[2] ) );
		$b = min( 255, max( 0, $matches[3] ) );

		return array( $r, $g, $b );
	};

	// rgb(0%,0%,0%) form
	if ( preg_match( "/rgb\(\s*(\d+)%\s*,\s*(\d+)%\s*,\s*(\d+)%\s*\)/", $decl, $matches ) ) {
		$r = min( 255, max( 0, $matches[1] * 255 / 100 ) );
		$g = min( 255, max( 0, $matches[2] * 255 / 100 ) );
		$b = min( 255, max( 0, $matches[3] * 255 / 100 ) );

		return array( $r, $g, $b );
	};

	// We've already checked every non-hexadecimal forms; now only color declarations starting
	// with # left; nevertheless, sometimes designers forget to put #-sign before the
	// color declaration. Thus, we'll add sharp sign automatically if it is missing
	//
	if ( strlen( $decl ) > 0 ) {
		if ( $decl{0} !== "#" ) {
			$decl = "#" . $decl;
		};
	};

	// #000000 form
	if ( preg_match( "/^#([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/", $decl, $matches ) ) {
		$arrr = unpack( "C", pack( "H2", $matches[1] ) );
		$arrg = unpack( "C", pack( "H2", $matches[2] ) );
		$arrb = unpack( "C", pack( "H2", $matches[3] ) );

		// Note that array indices returned by unpack differ in different versions of PHP. Unfortunately
		// we unable to directly access values - compatibility is an issue...

		$r = array_pop( $arrr );
		$g = array_pop( $arrg );
		$b = array_pop( $arrb );

		return array( $r, $g, $b );
	};

	// #000 form
	if ( preg_match( "/^#([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/", $decl, $matches ) ) {
		$arrr = unpack( "C", pack( "H2", $matches[1] . $matches[1] ) );
		$arrg = unpack( "C", pack( "H2", $matches[2] . $matches[2] ) );
		$arrb = unpack( "C", pack( "H2", $matches[3] . $matches[3] ) );

		// Note that array indices returned by unpack differ in different versions of PHP. Unfortunately
		// we unable to directly access values - compatibility is an issue...

		$r = array_pop( $arrr );
		$g = array_pop( $arrg );
		$b = array_pop( $arrb );

		return array( $r, $g, $b );
	};

	// Transparent color - by default
	$success = false;

	return array( - 1, - 1, - 1 );
}

function is_transparent( $color ) {
	return $color[0] < 0;
}


define( 'CSS_PROPERTY_LEVEL_CURRENT', 0 );
define( 'CSS_PROPERTY_LEVEL_PARENT', 1 );

define( 'CSS_PROPERTY_INHERIT', null );

define( 'CSS_BACKGROUND', 1 );
define( 'CSS_BACKGROUND_COLOR', 2 );
define( 'CSS_BACKGROUND_IMAGE', 3 );
define( 'CSS_BORDER', 4 );
define( 'CSS_BORDER_BOTTOM', 5 );
define( 'CSS_BORDER_BOTTOM_COLOR', 6 );
define( 'CSS_BORDER_BOTTOM_STYLE', 7 );
define( 'CSS_BORDER_BOTTOM_WIDTH', 8 );
define( 'CSS_BORDER_COLLAPSE', 9 );
define( 'CSS_BORDER_COLOR', 10 );
define( 'CSS_BORDER_LEFT', 11 );
define( 'CSS_BORDER_LEFT_COLOR', 12 );
define( 'CSS_BORDER_LEFT_STYLE', 13 );
define( 'CSS_BORDER_LEFT_WIDTH', 14 );
define( 'CSS_BORDER_RIGHT', 15 );
define( 'CSS_BORDER_RIGHT_COLOR', 16 );
define( 'CSS_BORDER_RIGHT_STYLE', 17 );
define( 'CSS_BORDER_RIGHT_WIDTH', 18 );
define( 'CSS_BORDER_STYLE', 19 );
define( 'CSS_BORDER_TOP', 20 );
define( 'CSS_BORDER_TOP_COLOR', 21 );
define( 'CSS_BORDER_TOP_STYLE', 22 );
define( 'CSS_BORDER_TOP_WIDTH', 23 );
define( 'CSS_BORDER_WIDTH', 24 );
define( 'CSS_BOTTOM', 25 );
define( 'CSS_CLEAR', 26 );
define( 'CSS_COLOR', 27 );
define( 'CSS_CONTENT', 28 );
define( 'CSS_DISPLAY', 29 );
define( 'CSS_FLOAT', 30 );
define( 'CSS_FONT', 31 );
define( 'CSS_FONT_FAMILY', 32 );
define( 'CSS_FONT_SIZE', 33 );
define( 'CSS_FONT_STYLE', 34 );
define( 'CSS_FONT_WEIGHT', 35 );
define( 'CSS_HEIGHT', 36 );
define( 'CSS_LEFT', 37 );
define( 'CSS_LETTER_SPACING', 38 );
define( 'CSS_LINE_HEIGHT', 39 );
define( 'CSS_LIST_STYLE', 40 );
define( 'CSS_MARGIN', 41 );
define( 'CSS_MARGIN_BOTTOM', 42 );
define( 'CSS_MARGIN_LEFT', 43 );
define( 'CSS_MARGIN_RIGHT', 44 );
define( 'CSS_MARGIN_TOP', 45 );
define( 'CSS_MIN_HEIGHT', 46 );
define( 'CSS_OVERFLOW', 47 );
define( 'CSS_PADDING', 48 );
define( 'CSS_PADDING_BOTTOM', 49 );
define( 'CSS_PADDING_LEFT', 50 );
define( 'CSS_PADDING_RIGHT', 51 );
define( 'CSS_PADDING_TOP', 52 );
define( 'CSS_PAGE_BREAK_AFTER', 53 );
define( 'CSS_POSITION', 54 );
define( 'CSS_RIGHT', 55 );
define( 'CSS_TEXT_ALIGN', 56 );
define( 'CSS_TEXT_DECORATION', 57 );
define( 'CSS_TEXT_INDENT', 58 );
define( 'CSS_TEXT_TRANSFORM', 59 );
define( 'CSS_TOP', 60 );
define( 'CSS_VERTICAL_ALIGN', 61 );
define( 'CSS_VISIBILITY', 62 );
define( 'CSS_WIDTH', 63 );
define( 'CSS_WHITE_SPACE', 64 );
define( 'CSS_Z_INDEX', 65 );

define( 'CSS_BACKGROUND_POSITION', 100 );
define( 'CSS_BACKGROUND_REPEAT', 101 );
define( 'CSS_MAX_HEIGHT', 102 );
define( 'CSS_LIST_STYLE_IMAGE', 103 );
define( 'CSS_LIST_STYLE_POSITION', 104 );
define( 'CSS_LIST_STYLE_TYPE', 105 );
define( 'CSS_WORD_SPACING', 106 );
define( 'CSS_MIN_WIDTH', 107 );
define( 'CSS_PAGE_BREAK_INSIDE', 108 );
define( 'CSS_PAGE_BREAK_BEFORE', 109 );
define( 'CSS_ORPHANS', 110 );
define( 'CSS_WIDOWS', 111 );
define( 'CSS_TABLE_LAYOUT', 112 );
define( 'CSS_DIRECTION', 113 );
define( 'CSS_PAGE', 114 );
define( 'CSS_BACKGROUND_ATTACHMENT', 115 );
define( 'CSS_SIZE', 116 );

define( 'CSS_HTML2PS_ALIGN', 900 );
define( 'CSS_HTML2PS_CELLPADDING', 901 );
define( 'CSS_HTML2PS_CELLSPACING', 902 );
define( 'CSS_HTML2PS_FORM_ACTION', 903 );
define( 'CSS_HTML2PS_FORM_RADIOGROUP', 904 );
define( 'CSS_HTML2PS_LOCALALIGN', 905 );
define( 'CSS_HTML2PS_LINK_DESTINATION', 906 );
define( 'CSS_HTML2PS_LINK_TARGET', 907 );
define( 'CSS_HTML2PS_LIST_COUNTER', 908 );
define( 'CSS_HTML2PS_NOWRAP', 909 );

define( 'CSS_HTML2PS_TABLE_BORDER', 910 );
define( 'CSS_HTML2PS_HTML_CONTENT', 911 );
define( 'CSS_HTML2PS_PSEUDOELEMENTS', 912 );
define( 'CSS_HTML2PS_COMPOSITE_WIDTH', 913 );
define( 'CSS_HTML2PS_PIXELS', 914 );

// Selectors

define( 'CSS_PAGE_SELECTOR_ALL', 0 );
define( 'CSS_PAGE_SELECTOR_FIRST', 1 );
define( 'CSS_PAGE_SELECTOR_LEFT', 2 );
define( 'CSS_PAGE_SELECTOR_RIGHT', 3 );
define( 'CSS_PAGE_SELECTOR_NAMED', 4 );

define( 'CSS_MARGIN_BOX_SELECTOR_TOP', 0 );
define( 'CSS_MARGIN_BOX_SELECTOR_TOP_LEFT_CORNER', 1 );
define( 'CSS_MARGIN_BOX_SELECTOR_TOP_LEFT', 2 );
define( 'CSS_MARGIN_BOX_SELECTOR_TOP_CENTER', 3 );
define( 'CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT', 4 );
define( 'CSS_MARGIN_BOX_SELECTOR_TOP_RIGHT_CORNER', 5 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM', 6 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT_CORNER', 7 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM_LEFT', 8 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM_CENTER', 9 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT', 10 );
define( 'CSS_MARGIN_BOX_SELECTOR_BOTTOM_RIGHT_CORNER', 11 );
define( 'CSS_MARGIN_BOX_SELECTOR_LEFT_TOP', 12 );
define( 'CSS_MARGIN_BOX_SELECTOR_LEFT_MIDDLE', 13 );
define( 'CSS_MARGIN_BOX_SELECTOR_LEFT_BOTTOM', 14 );
define( 'CSS_MARGIN_BOX_SELECTOR_RIGHT_TOP', 15 );
define( 'CSS_MARGIN_BOX_SELECTOR_RIGHT_MIDDLE', 16 );
define( 'CSS_MARGIN_BOX_SELECTOR_RIGHT_BOTTOM', 17 );

// 'border-style' values

define( 'BS_NONE', 1 );
define( 'BS_SOLID', 2 );
define( 'BS_INSET', 3 );
define( 'BS_GROOVE', 4 );
define( 'BS_RIDGE', 5 );
define( 'BS_OUTSET', 6 );
define( 'BS_DASHED', 7 );
define( 'BS_DOTTED', 8 );
define( 'BS_DOUBLE', 9 );

// Unit types

define( 'UNIT_NONE', 0 );

// relative units

define( 'UNIT_PX', 2 );
define( 'UNIT_EM', 5 );
define( 'UNIT_EX', 6 );

// absolute length units

define( 'UNIT_IN', 7 );
define( 'UNIT_CM', 4 );
define( 'UNIT_MM', 3 );
define( 'UNIT_PT', 1 );
define( 'UNIT_PC', 8 );

// Cache constants

define( 'CACHE_MIN_WIDTH', 0 );
define( 'CACHE_MAX_WIDTH', 1 );
define( 'CACHE_TYPEFACE', 2 );
define( 'CACHE_MIN_WIDTH_NATURAL', 3 );

// CSS regular expressions

define( 'CSS_NL_REGEXP', '(?:\n|\r\n|\r|\f)' );
define( 'CSS_UNICODE_REGEXP', '\\[0-9a-f]{1,6}(?:\r\n|[ \n\r\t\f])?' );
define( 'CSS_NONASCII_REGEXP', '[^\0-\177]' );
define( 'CSS_ESCAPE_REGEXP', CSS_UNICODE_REGEXP . '|\\[^\n\r\f0-9a-f]' );
define( 'CSS_NMSTART_REGEXP', '(?:[_a-z]|' . CSS_NONASCII_REGEXP . '|' . CSS_ESCAPE_REGEXP . ')' );
define( 'CSS_NMCHAR_REGEXP', '(?:[_a-z0-9-]|' . CSS_NONASCII_REGEXP . '|' . CSS_ESCAPE_REGEXP . ')' );
define( 'CSS_IDENT_REGEXP', '-?' . CSS_NMSTART_REGEXP . CSS_NMCHAR_REGEXP . '*' );
define( 'CSS_FUNCTION_REGEXP', '(?:' . CSS_IDENT_REGEXP . '\()' );
define( 'CSS_STRING1_REGEXP', '\"(?:[^\n\r\f\\"]|\\\\' . CSS_NL_REGEXP . '|' . CSS_ESCAPE_REGEXP . ')*\"' );
define( 'CSS_STRING2_REGEXP', '\\' . "'" . '(?:[^\n\r\f\\' . "'" . ']|\\\\' . CSS_NL_REGEXP . '|' . CSS_ESCAPE_REGEXP . ')*\\' . "'" );


// $Header: /cvsroot/html2ps/css.inc.php,v 1.28 2007/04/07 11:16:34 Konstantin Exp $

class CSS {
	var $_handlers;
	var $_mapping;
	var $_defaultState;
	var $_defaultStateFlags;

	function _getDefaultState() {
		if ( ! isset( $this->_defaultState ) ) {
			$this->_defaultState = array();

			$handlers = $this->getHandlers();
			foreach ( $handlers as $property => $handler ) {
				$this->_defaultState[ $property ] = $handler->default_value();
			};
		};

		return $this->_defaultState;
	}

	function _getDefaultStateFlags() {
		if ( ! isset( $this->_defaultStateFlags ) ) {
			$this->_defaultStateFlags = array();

			$handlers = $this->getHandlers();
			foreach ( $handlers as $property => $handler ) {
				$this->_defaultStateFlags[ $property ] = true;
			};
		};

		return $this->_defaultStateFlags;
	}

	function getHandlers() {
		return $this->_handlers;
	}

	function getInheritableTextHandlers() {
		if ( ! isset( $this->_handlersInheritableText ) ) {
			$this->_handlersInheritabletext = array();
			foreach ( $this->_handlers as $property => $handler ) {
				if ( $handler->isInheritableText() ) {
					$this->_handlersInheritableText[ $property ] =& $this->_handlers[ $property ];
				};
			}
		}

		return $this->_handlersInheritableText;
	}

	function getInheritableHandlers() {
		if ( ! isset( $this->_handlersInheritable ) ) {
			$this->_handlersInheritable = array();
			foreach ( $this->_handlers as $property => $handler ) {
				if ( $handler->isInheritable() ) {
					$this->_handlersInheritable[ $property ] =& $this->_handlers[ $property ];
				};
			}
		}

		return $this->_handlersInheritable;
	}

	function &get() {
		global $__g_css_handler_set;

		if ( ! isset( $__g_css_handler_set ) ) {
			$__g_css_handler_set = new CSS();
		};

		return $__g_css_handler_set;
	}

	function CSS() {
		$this->_handlers = array();
		$this->_mapping  = array();
	}

	function getDefaultValue( $property ) {
		$css     =& CSS::get();
		$handler =& $css->_get_handler( $property );
		$value   = $handler->default_value();

		if ( is_object( $value ) ) {
			return $value->copy();
		} else {
			return $value;
		};
	}

	function &get_handler( $property ) {
		$css     =& CSS::get();
		$handler =& $css->_get_handler( $property );

		return $handler;
	}

	function &_get_handler( $property ) {
		if ( isset( $this->_handlers[ $property ] ) ) {
			return $this->_handlers[ $property ];
		} else {
			$dumb = null;

			return $dumb;
		};
	}

	function _name2code( $key ) {
		if ( ! isset( $this->_mapping[ $key ] ) ) {
			return null;
		};

		return $this->_mapping[ $key ];
	}

	function name2code( $key ) {
		$css =& CSS::get();

		return $css->_name2code( $key );
	}

	function register_css_property( &$handler ) {
		$property = $handler->get_property_code();
		$name     = $handler->get_property_name();

		$css                         =& CSS::get();
		$css->_handlers[ $property ] =& $handler;
		$css->_mapping[ $name ]      = $property;
	}

	/**
	 * Refer to CSS 2.1 G.2 Lexical scanner
	 * h    [0-9a-f]
	 * nonascii  [\200-\377]
	 * unicode    \\{h}{1,6}(\r\n|[ \t\r\n\f])?
	 * escape    {unicode}|\\[^\r\n\f0-9a-f]
	 * nmstart    [_a-z]|{nonascii}|{escape}
	 * nmchar    [_a-z0-9-]|{nonascii}|{escape}
	 * ident    -?{nmstart}{nmchar}*
	 */
	function get_identifier_regexp() {
		return '-?(?:[_a-z]|[\200-\377]|\\[0-9a-f]{1,6}(?:\r\n|[ \t\r\n\f])?|\\[^\r\n\f0-9a-f])(?:[_a-z0-9-]|[\200-\377]|\\[0-9a-f]{1,6}(?:\r\n|[ \t\r\n\f])?|\\[^\r\n\f0-9a-f])*';
	}

	function is_identifier( $string ) {
		return preg_match( sprintf( '/%s/',
			CSS::get_identifier_regexp() ),
			$string );
	}

	function parse_string( $string ) {
		if ( preg_match( sprintf( '/^(%s)\s*(.*)$/s', CSS_STRING1_REGEXP ), $string, $matches ) ) {
			$value = $matches[1];
			$rest  = $matches[2];

			$value = CSS::remove_backslash_at_newline( $value );

			return array( $value, $rest );
		};

		if ( preg_match( sprintf( '/^(%s)\s*(.*)$/s', CSS_STRING2_REGEXP ), $string, $matches ) ) {
			$value = $matches[1];
			$rest  = $matches[2];

			$value = CSS::remove_backslash_at_newline( $value );

			return array( $value, $rest );
		};

		return array( null, $string );
	}

	function remove_backslash_at_newline( $value ) {
		return preg_replace( "/\\\\\n/", '', $value );
	}
}


class CSSState {
	var $_state;
	var $_stateDefaultFlags;
	var $_handlerSet;
	var $_baseFontSize;

	function CSSState( &$handlerSet ) {
		$this->_handlerSet        =& $handlerSet;
		$this->_state             = array( $this->_getDefaultState() );
		$this->_stateDefaultFlags = array( $this->_getDefaultStateFlags() );

		/**
		 * Note that default state should contain font size in absolute units (e.g. 11pt),
		 * so we may pass any value as a base font size parameter of 'toPt' method call
		 */
		$this->_baseFontSize = array( $this->_state[0][ CSS_FONT ]->size->toPt( 0 ) );
	}

	function _getDefaultState() {
		return $this->_handlerSet->_getDefaultState();
	}

	function _getDefaultStateFlags() {
		return $this->_handlerSet->_getDefaultStateFlags();
	}

	function replaceParsed( $property_data, $property_list ) {
		foreach ( $property_list as $property ) {
			$this->set_property( $property, $property_data->get_css_property( $property ) );
		};
	}

	function popState() {
		array_shift( $this->_state );
		array_shift( $this->_stateDefaultFlags );
		array_shift( $this->_baseFontSize );
	}

	function getStoredState( &$base_font_size, &$state, &$state_default_flags ) {
		$base_font_size      = array_shift( $this->_baseFontSize );
		$state               = array_shift( $this->_state );
		$state_default_flags = array_shift( $this->_stateDefaultFlags );
	}

	function pushStoredState( $base_font_size, $state, $state_default_flags ) {
		array_unshift( $this->_baseFontSize, $base_font_size );
		array_unshift( $this->_state, $state );
		array_unshift( $this->_stateDefaultFlags, $state_default_flags );
	}

	function pushState() {
		$base_size = $this->getBaseFontSize();
		/**
		 * Only computed font-size values are inherited; this means that
		 * base font size value should not be recalculated if font-size was not set explicitly
		 */
		if ( $this->get_propertyDefaultFlag( CSS_FONT_SIZE ) ) {
			array_unshift( $this->_baseFontSize, $base_size );
		} else {
			$size = $this->getInheritedProperty( CSS_FONT_SIZE );
			array_unshift( $this->_baseFontSize, $size->toPt( $base_size ) );
		};

		array_unshift( $this->_state, $this->getState() );
		array_unshift( $this->_stateDefaultFlags, $this->_getDefaultStateFlags() );
	}

	function pushDefaultState() {
		$this->pushState();
		$this->_state[0] = $this->_getDefaultState();

		$handlers = $this->_handlerSet->getInheritableHandlers();

		foreach ( $handlers as $property => $handler ) {
			$handler->inherit( $this->_state[1], $this->_state[0] );
		};
	}

	function pushDefaultTextState() {
		$state = $this->getState();

		$this->pushState();
		$this->_state[0] = $this->_getDefaultState();
		$new_state       =& $this->getState();

		$handlers = $this->_handlerSet->getInheritableTextHandlers();
		foreach ( $handlers as $property => $handler ) {
			$handler->inherit_text( $state, $new_state );
		}
	}

	function &getStateDefaultFlags() {
		return $this->_stateDefaultFlags[0];
	}

	function &getState() {
		return $this->_state[0];
	}

	function &getInheritedProperty( $code ) {
		$handler =& CSS::get_handler( $code );

		$size = count( $this->_state );
		for ( $i = 0; $i < $size; $i ++ ) {
			$value =& $handler->get( $this->_state[ $i ] );
			if ( $value != CSS_PROPERTY_INHERIT ) {
				return $value;
			};

			// Prevent taking  the font-size property; as,  according to CSS
			// standard,  'inherit'  should mean  calculated  value, we  use
			// '1em' instead,  forcing the script to  take parent calculated
			// value later
			if ( $code == CSS_FONT_SIZE ) {
				$value =& Value::fromData( 1, UNIT_EM );

				return $value;
			};
		};

		$null = null;

		return $null;
	}

	function get_propertyOnLevel( $code, $level ) {
		return $this->_state[ $level ][ $code ];
	}

	/**
	 * Optimization notice: this function is called very often,
	 * so even a slight overhead for the 'getState() and CSS::get_handler
	 * accumulates in a significiant processing delay.
	 *
	 * getState was replaced with direct $this->_state[0] access,
	 * get_handler call results are cached in static var
	 */
	function &get_property( $code ) {
		static $cache = array();
		if ( ! isset( $cache[ $code ] ) ) {
			$cache[ $code ] =& CSS::get_handler( $code );
		};
		$value =& $cache[ $code ]->get( $this->_state[0] );

		return $value;
	}

	function get_propertyDefaultFlag( $code ) {
		return $this->_stateDefaultFlags[0][ $code ];
	}

	function set_property_on_level( $code, $level, $value ) {
		$this->_state[ $level ][ $code ] = $value;
	}

	function set_propertyDefault( $code, $value ) {
		$state          =& $this->getState();
		$state[ $code ] = $value;
	}

	/**
	 * see get_property for optimization description
	 */
	function set_property( $code, $value ) {
		$this->set_propertyDefault( $code, $value );

		static $cache = array();
		if ( ! isset( $cache[ $code ] ) ) {
			$cache[ $code ] =& CSS::get_handler( $code );
		};

		$cache[ $code ]->clearDefaultFlags( $this );
	}

	function set_propertyDefaultFlag( $code, $value ) {
		$state_flags          =& $this->getStateDefaultFlags();
		$state_flags[ $code ] = $value;
	}

	function getBaseFontSize() {
		return $this->_baseFontSize[0];
	}
}


/**
 * "Singleton"
 */
class CSSCache {
	function get() {
		global $__g_css_manager;

		if ( ! isset( $__g_css_manager ) ) {
			$__g_css_manager = new CSSCache();
		};

		return $__g_css_manager;
	}

	function _getCacheFilename( $url ) {
		return CACHE_DIR . md5( $url ) . '.css.compiled';
	}

	function _isCached( $url ) {
		$cache_filename = $this->_getCacheFilename( $url );

		return is_readable( $cache_filename );
	}

	function &_readCached( $url ) {
		$cache_filename = $this->_getCacheFilename( $url );
		$obj            = unserialize( file_get_contents( $cache_filename ) );

		return $obj;
	}

	function _putCached( $url, $css ) {
		file_put_contents( $this->_getCacheFilename( $url ), serialize( $css ) );
	}

	function &compile( $url, $css, &$pipeline ) {
		if ( $this->_isCached( $url ) ) {
			return $this->_readCached( $url );
		} else {
			$cssruleset = new CSSRuleset();
			$cssruleset->parse_css( $css, $pipeline );
			$this->_putCached( $url, $cssruleset );

			return $cssruleset;
		};
	}
}


class CSSPropertyHandler {
	var $_inheritable;
	var $_inheritable_text;

	function css( $value, &$pipeline ) {
		$css_state =& $pipeline->get_current_css_state();

		if ( $this->applicable( $css_state ) ) {
			$this->replace( $this->parse( $value, $pipeline ), $css_state );
		};
	}

	function applicable( $css_state ) {
		return true;
	}

	function clearDefaultFlags( &$state ) {
		$state->set_propertyDefaultFlag( $this->get_property_code(), false );
	}

	function CSSPropertyHandler( $inheritable, $inheritable_text ) {
		$this->_inheritable      = $inheritable;
		$this->_inheritable_text = $inheritable_text;
	}

	/**
	 * Optimization: this function is called very often, so
	 * we minimize the overhead by calling $this->get_property_code()
	 * once per property handler object instead of calling in every
	 * CSSPropertyHandler::get() call.
	 */
	function &get( &$state ) {
		static $property_code = null;
		if ( is_null( $property_code ) ) {
			$property_code = $this->get_property_code();
		};

		if ( ! isset( $state[ $property_code ] ) ) {
			$null = null;

			return $null;
		};

		return $state[ $property_code ];
	}

	function inherit( $old_state, &$new_state ) {
		$code               = $this->get_property_code();
		$new_state[ $code ] = ( $this->_inheritable ?
			$old_state[ $code ] :
			$this->default_value() );
	}

	function isInheritableText() {
		return $this->_inheritable_text;
	}

	function isInheritable() {
		return $this->_inheritable;
	}

	function inherit_text( $old_state, &$new_state ) {
		$code = $this->get_property_code();

		if ( $this->_inheritable_text ) {
			$new_state[ $code ] = $old_state[ $code ];
		} else {
			$new_state[ $code ] = $this->default_value();
		};
	}

	function is_default( $value ) {
		if ( is_object( $value ) ) {
			return $value->is_default();
		} else {
			return $this->default_value() === $value;
		};
	}

	function is_subproperty() {
		return false;
	}

	function replace( $value, &$state ) {
		$state->set_property( $this->get_property_code(), $value );
	}

	function replaceDefault( $value, &$state ) {
		$state->set_propertyDefault( $this->get_property_code(), $value );
	}

	function replace_array( $value, &$state ) {
		static $property_code = null;
		if ( is_null( $property_code ) ) {
			$property_code = $this->get_property_code();
		};

		$state[ $property_code ] = $value;
	}
}


class CSSPropertyStringSet extends CSSPropertyHandler {
	var $_mapping;
	var $_keys;

	function CSSPropertyStringSet( $inherit, $inherit_text, $mapping ) {
		$this->CSSPropertyHandler( $inherit, $inherit_text );

		$this->_mapping = $mapping;

		/**
		 * Unfortunately, isset($this->_mapping[$key]) will return false
		 * for $_mapping[$key] = null. As CSS_PROPERTY_INHERIT value is 'null',
		 * this should be avoided using the hack below
		 */
		$this->_keys = $mapping;
		foreach ( $this->_keys as $key => $value ) {
			$this->_keys[ $key ] = 1;
		};
	}

	function parse( $value ) {
		$value = trim( strtolower( $value ) );

		if ( isset( $this->_keys[ $value ] ) ) {
			return $this->_mapping[ $value ];
		};

		return $this->default_value();
	}
}


class CSSSubProperty extends CSSPropertyHandler {
	var $_owner;

	function CSSSubProperty( &$owner ) {
		$this->_owner =& $owner;
	}

	function &get( &$state ) {
		$owner    =& $this->owner();
		$value    =& $owner->get( $state );
		$subvalue =& $this->get_value( $value );

		return $subvalue;
	}

	function is_subproperty() {
		return true;
	}

	function &owner() {
		return $this->_owner;
	}

	function default_value() {
	}

	function inherit( $old_state, &$new_state ) {
	}

	function inherit_text( $old_state, &$new_state ) {
	}

	function replace_array( $value, &$state_array ) {
		$owner =& $this->owner();

		$owner_value = $state_array[ $owner->get_property_code() ];

		if ( is_object( $owner_value ) ) {
			$owner_value = $owner_value->copy();
		};

		if ( is_object( $value ) ) {
			$this->set_value( $owner_value, $value->copy() );
		} else {
			$this->set_value( $owner_value, $value );
		};

		$state_array[ $owner->get_property_code() ] = $owner_value;
	}

	function replace( $value, &$state ) {
		$owner       =& $this->owner();
		$owner_value = $owner->get( $state->getState() );

		if ( is_object( $owner_value ) ) {
			$owner_value =& $owner_value->copy();
		};

		if ( is_object( $value ) ) {
			$value_copy =& $value->copy();
			$this->set_value( $owner_value, $value_copy );
		} else {
			$this->set_value( $owner_value, $value );
		};

		$owner->replaceDefault( $owner_value, $state );
		$state->set_propertyDefaultFlag( $this->get_property_code(), false );
	}

	function set_value( &$owner_value, &$value ) {
		error_no_method( 'set_value', get_class( $this ) );
	}

	function &get_value( &$owner_value ) {
		error_no_method( 'get_value', get_class( $this ) );
	}
}


class CSSSubFieldProperty extends CSSSubProperty {
	var $_owner;
	var $_owner_field;

	function CSSSubFieldProperty( &$owner, $field ) {
		$this->CSSSubProperty( $owner );
		$this->_owner_field = $field;
	}

	function set_value( &$owner_value, &$value ) {
		$field               = $this->_owner_field;
		$owner_value->$field = $value;
	}

	function &get_value( &$owner_value ) {
		$field = $this->_owner_field;

		return $owner_value->$field;
	}
}


// $Header: /cvsroot/html2ps/css.utils.inc.php,v 1.30 2007/04/07 11:16:34 Konstantin Exp $

// TODO: make an OO-style selectors interface instead of switches

// Searches the CSS rule selector for pseudoelement selectors
// (assuming that there can be only one) and returns its value
//
// note that there's not sence in applying pseudoelement to any chained selector except the last
// (the deepest descendant)
//
function css_find_pseudoelement( $selector ) {
	$selector_type = selector_get_type( $selector );
	switch ( $selector_type ) {
		case SELECTOR_PSEUDOELEMENT_BEFORE:
		case SELECTOR_PSEUDOELEMENT_AFTER:
			return $selector_type;
		case SELECTOR_SEQUENCE:
			foreach ( $selector[1] as $subselector ) {
				$pe = css_find_pseudoelement( $subselector );
				if ( ! is_null( $pe ) ) {
					return $pe;
				};
			}

			return null;
		default:
			return null;
	}
}

function _fix_tag_display( $default_display, &$state, &$pipeline ) {
	// In some cases 'display' CSS property should be ignored for element-generated boxes
	// Here we will use the $default_display stored above
	// Note that "display: none" should _never_ be changed
	//
	$handler =& CSS::get_handler( CSS_DISPLAY );
	if ( $handler->get( $state->getState() ) === "none" ) {
		return;
	};

	switch ( $default_display ) {
		case 'table-cell':
			// TD will always have 'display: table-cell'
			$handler->css( 'table-cell', $pipeline );
			break;

		case '-button':
			// INPUT buttons will always have 'display: -button' (in latter case if display = 'block', we'll use a wrapper box)
			$css_state =& $pipeline->get_current_css_state();
			if ( $handler->get( $css_state->getState() ) === 'block' ) {
				$need_block_wrapper = true;
			};
			$handler->css( '-button', $pipeline );
			break;
	};
}

function is_percentage( $value ) {
	return $value{strlen( $value ) - 1} == "%";
}

/**
 * Handle escape sequences in CSS string values
 *
 * 4.3.7 Strings
 *
 * Strings can  either be  written with double  quotes or  with single
 * quotes.  Double quotes  cannot occur  inside double  quotes, unless
 * escaped (e.g., as '\"' or  as '\22'). Analogously for single quotes
 * (e.g., "\'" or "\27")...
 *
 * A string cannot directly contain a newline. To include a newline in
 * a string,  use an  escape representing the  line feed  character in
 * Unicode  (U+000A),  such  as  "\A"  or  "\00000a"...
 *
 * It is possible to break strings over several lines, for esthetic or
 * other reasons,  but in  such a  case the newline  itself has  to be
 * escaped  with a  backslash  (\).
 *
 * 4.1.3 Characters and case
 *
 * In  CSS 2.1,  a backslash  (\) character  indicates three  types of
 * character escapes.
 *
 * First,  inside a  string,  a  backslash followed  by  a newline  is
 * ignored  (i.e., the  string is  deemed  not to  contain either  the
 * backslash or the newline).
 *
 * Second,  it cancels  the  meaning of  special  CSS characters.  Any
 * character  (except  a hexadecimal  digit)  can  be  escaped with  a
 * backslash to  remove its  special meaning. For  example, "\""  is a
 * string consisting  of one  double quote. Style  sheet preprocessors
 * must not  remove these  backslashes from a  style sheet  since that
 * would change the style sheet's meaning.
 *
 * Third, backslash escapes allow  authors to refer to characters they
 * can't  easily put in  a document.  In this  case, the  backslash is
 * followed by at most  six hexadecimal digits (0..9A..F), which stand
 * for the  ISO 10646 ([ISO10646])  character with that  number, which
 * must not be  zero. If a character in  the range [0-9a-fA-F] follows
 * the  hexadecimal number, the  end of  the number  needs to  be made
 * clear. There are two ways to do that:
 *
 * 1. with a space (or other whitespace character): "\26 B" ("&B"). In
 *    this   case,   user  agents   should   treat   a  "CR/LF"   pair
 *    (U+000D/U+000A) as a single whitespace character.
 * 2. by providing exactly 6 hexadecimal digits: "\000026B" ("&B")
 *
 * In fact,  these two  methods may be  combined. Only  one whitespace
 * character  is ignored after  a hexadecimal  escape. Note  that this
 * means that  a "real"  space after the  escape sequence  must itself
 * either be escaped or doubled.
 */
function css_process_escapes( $value ) {
	$value = preg_replace_callback( '/\\\\([\da-f]{1,6})( |[^][\da-f])/i',
		'css_process_escapes_callback',
		$value );

	$value = preg_replace_callback( '/\\\\([\da-f]{6})( ?)/i',
		'css_process_escapes_callback',
		$value );

	return $value;
}

function css_process_escapes_callback( $matches ) {
	if ( $matches[2] == ' ' ) {
		return hex_to_utf8( $matches[1] );
	} else {
		return hex_to_utf8( $matches[1] ) . $matches[2];
	};
}

function css_remove_value_quotes( $value ) {
	if ( strlen( $value ) == 0 ) {
		return $value;
	};

	if ( $value{0} === "'" || $value{0} === "\"" ) {
		$value = substr( $value, 1, strlen( $value ) - 2 );
	};

	return $value;
}


// $Header: /cvsroot/html2ps/css.parse.inc.php,v 1.28 2007/03/15 18:37:31 Konstantin Exp $

require_once( HTML2PS_DIR . 'css.rules.page.inc.php' );
require_once( HTML2PS_DIR . 'css.property.collection.php' );
require_once( HTML2PS_DIR . 'css.parse.properties.php' );

define( "SELECTOR_CLASS_REGEXP", "[\w\d_-]+" );
define( "SELECTOR_ID_REGEXP", "[\w\d_-]+" );
define( "SELECTOR_ATTR_REGEXP", "[\w]+" );
define( "SELECTOR_ATTR_VALUE_REGEXP", "([\w]+)=['\"]?([\w]+)['\"]?" );
define( "SELECTOR_ATTR_VALUE_WORD_REGEXP", "([\w]+)~=['\"]?([\w]+)['\"]?" );

// Parse the 'style' attribute value of current node\
//
function parse_style_attr( $root, &$state, &$pipeline ) {
	$style = $root->get_attribute( "style" );

	// Some "designers" (obviously lacking the brain and ability to read ) use such constructs:
	//
	// <input maxLength=256 size=45 name=searchfor value="" style="{width:350px}">
	//
	// It is out of standard, as HTML 4.01 says:
	//
	// The syntax of the value of the style attribute is determined by the default style sheet language.
	// For example, for [[CSS2]] inline style, use the declaration block syntax described in section 4.1.8
	// *(without curly brace delimiters)*
	//
	// but still parsed by many browsers; let's be compatible with these idiots - remove curly braces
	//
	$style = preg_replace( "/^\s*{/", "", $style );
	$style = preg_replace( "/}\s*$/", "", $style );

	$properties = parse_css_properties( $style, $pipeline );

	$rule = new CSSRule( array(
		array( SELECTOR_ANY ),
		$properties,
		$pipeline->get_base_url(),
		$root
	),
		$pipeline );

	$rule->apply( $root, $state, $pipeline );
}

// TODO: make a real parser instead of if-then-else mess
//
// Selector grammar (according to CSS 2.1, paragraph 5.1 & 5.2):
// Note that this particular grammar is not LL1, but still can be converter to
// that form
//
// COMPOSITE_SELECTOR  ::= SELECTOR ("," SELECTOR)*
//
// SELECTOR            ::= SIMPLE_SELECTOR (COMBINATOR SIMPLE_SELECTOR)*
//
// COMBINATOR          ::= WHITESPACE* COMBINATOR_SYMBOL WHITESPACE*
// COMBINATOR_SYMBOL   ::= " " | ">" | "+"
//
// SIMPLE_SELECTOR     ::= TYPE_SELECTOR (ADDITIONAL_SELECTOR)*
// SIMPLE_SELECTOR     ::= UNIVERSAL_SELECTOR (ADDITIONAL_SELECTOR)*
// SIMPLE_SELECTOR     ::= (ADDITIONAL_SELECTOR)*
//
// CSS 2.1, p. 5.3: if the universal selector is not the only component of a simple selector, the "*" may be omitted
// SIMPLE_SELECTOR     ::= (ADDITIONAL_SELECTOR)*
//
// TYPE_SELECTOR       ::= TAG_NAME
//
// UNIVERSAL_SELECTOR  ::= "*"
//
// ADDITIONAL_SELECTOR ::= ATTRIBUTE_SELECTOR | ID_SELECTOR | PSEUDOCLASS | CLASS_SELECTOR | PSEUDOELEMENT
//
// ATTRIBUTE_SELECTOR  ::= "[" ATTRIBUTE_NAME "]"
// ATTRIBUTE_SELECTOR  ::= "[" ATTRIBUTE_NAME "="  ATTR_VALUE "]"
// ATTRIBUTE_SELECTOR  ::= "[" ATTRIBUTE_NAME "~=" ATTR_VALUE "]"
// ATTRIBUTE_SELECTOR  ::= "[" ATTRIBUTE_NAME "|=" ATTR_VALUE "]"
//
// CLASS_SELECTOR      ::= "." CLASS_NAME
//
// ID_SELECTOR         ::= "#" ID_VALUE
//
// PSEUDOCLASS         ::= ":first-child"    |
//                         ":link"           |
//                         ":visited"        | // ignored in our case
//                         ":hover"          | // dynamic - ignored in our case
//                         ":active"         | // dynamic - ignored in our case
//                         ":focus"          | // dynamic - ignored in our case
//                         ":lang(" LANG ")" | // dynamic - ignored in our case
//
// PSEUDOELEMENT       ::= ":first-line"     |
//                         ":first-letter"   |
//                         ":before"         |
//                         ":after"          |
//
// ATTR_VALUE          ::= IDENTIFIER | STRING
// CLASS_NAME          ::= INDETIFIER
// ID_VALUE            ::= IDENTIFIER
//
function parse_css_selector( $raw_selector ) {
	// Note a 'trim' call. Is is required as there could be leading/trailing spaces in $raw_selector
	//
	$raw_selector = strtolower( trim( $raw_selector ) );

	// Direct Parent/child selectors (for example 'table > tr')
	if ( preg_match( "/^(\S.*)\s*>\s*([^\s]+)$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array(
				parse_css_selector( $matches[2] ),
				array(
					SELECTOR_DIRECT_PARENT,
					parse_css_selector( $matches[1] )
				)
			)
		);
	}

	// Parent/child selectors (for example 'table td')
	if ( preg_match( "/^(\S.*)\s+([^\s]+)$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array(
				parse_css_selector( $matches[2] ),
				array(
					SELECTOR_PARENT,
					parse_css_selector( $matches[1] )
				)
			)
		);
	}

	if ( preg_match( "/^(.+)\[(" . SELECTOR_ATTR_REGEXP . ")\]$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array(
				parse_css_selector( $matches[1] ),
				array( SELECTOR_ATTR, $matches[2] )
			)
		);
	}

	if ( preg_match( "/^(.+)\[" . SELECTOR_ATTR_VALUE_REGEXP . "\]$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array(
				parse_css_selector( $matches[1] ),
				array( SELECTOR_ATTR_VALUE, $matches[2], css_remove_value_quotes( $matches[3] ) )
			)
		);
	}

	if ( preg_match( "/^(.+)\[" . SELECTOR_ATTR_VALUE_WORD_REGEXP . "\]$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array(
				parse_css_selector( $matches[1] ),
				array( SELECTOR_ATTR_VALUE_WORD, $matches[2], css_remove_value_quotes( $matches[3] ) )
			)
		);
	}

	// pseudoclasses & pseudoelements
	if ( preg_match( "/^([#\.\s\w_-]*):(\w+)$/", $raw_selector, $matches ) ) {
		if ( $matches[1] === "" ) {
			$matches[1] = "*";
		};

		switch ( $matches[2] ) {
			case "lowlink":
				return array(
					SELECTOR_SEQUENCE,
					array( parse_css_selector( $matches[1] ), array( SELECTOR_PSEUDOCLASS_LINK_LOW_PRIORITY ) )
				);
			case "link":
				return array(
					SELECTOR_SEQUENCE,
					array( parse_css_selector( $matches[1] ), array( SELECTOR_PSEUDOCLASS_LINK ) )
				);
			case "before":
				return array(
					SELECTOR_SEQUENCE,
					array( parse_css_selector( $matches[1] ), array( SELECTOR_PSEUDOELEMENT_BEFORE ) )
				);
			case "after":
				return array(
					SELECTOR_SEQUENCE,
					array( parse_css_selector( $matches[1] ), array( SELECTOR_PSEUDOELEMENT_AFTER ) )
				);
		};
	};

	// :lang() pseudoclass
	if ( preg_match( "/^([#\.\s\w_-]+):lang\((\w+)\)$/", $raw_selector, $matches ) ) {
		return array(
			SELECTOR_SEQUENCE,
			array( parse_css_selector( $matches[1] ), array( SELECTOR_LANGUAGE, $matches[2] ) )
		);
	};

	if ( preg_match( "/^(\S+)(\.\S+)$/", $raw_selector, $matches ) ) {
		return array( SELECTOR_SEQUENCE, array( parse_css_selector( $matches[1] ), parse_css_selector( $matches[2] ) ) );
	};

	switch ( $raw_selector{0} ) {
		case '#':
			return array( SELECTOR_ID, substr( $raw_selector, 1 ) );
		case '.':
			return array( SELECTOR_CLASS, substr( $raw_selector, 1 ) );
	};

	if ( preg_match( "/^(\w+)#(" . SELECTOR_ID_REGEXP . ")$/", $raw_selector, $matches ) ) {
		return array( SELECTOR_SEQUENCE, array( array( SELECTOR_ID, $matches[2] ), array( SELECTOR_TAG, $matches[1] ) ) );
	};

	if ( $raw_selector === "*" ) {
		return array( SELECTOR_ANY );
	};

	return array( SELECTOR_TAG, $raw_selector );
}

function parse_css_selectors( $raw_selectors ) {
	$offset    = 0;
	$selectors = array();

	$selector_strings = explode( ",", $raw_selectors );

	foreach ( $selector_strings as $selector_string ) {
		// See comment on SELECTOR_ANY regarding why this code is commented
		// Remove the '* html' string from the selector
		// $selector_string = preg_replace('/^\s*\*\s+html/','',$selector_string);

		$selector_string = trim( $selector_string );

		// Support for non-valid CSS similar to: "selector1,selector2, {rules}"
		// In this case we'll get three selectors; last will be empty string

		if ( ! empty( $selector_string ) ) {
			$selectors[] = parse_css_selector( $selector_string );
		};
	};

	return $selectors;
}

// function &parse_css_property($property, &$pipeline) {
//   if (preg_match("/^(.*?)\s*:\s*(.*)/",$property, $matches)) {
//     $name = strtolower(trim($matches[1]));
//     $code = CSS::name2code($name);
//     if (is_null($code)) {
//       error_log(sprintf("Unsupported CSS property: '%s'", $name));
//       $null = null;
//       return $null;
//     };

//     $collection =& new CSSPropertyCollection();
//     $collection->add_property(CSSPropertyDeclaration::create($code, trim($matches[2]), $pipeline));
//     return $collection;
//   } elseif (preg_match("/@import\s+\"(.*)\";/",$property, $matches)) {
//     // @import "<url>"
//     $collection =& css_import(trim($matches[1]), $pipeline);
//     return $collection;
//   } elseif (preg_match("/@import\s+url\((.*)\);/",$property, $matches)) {
//     // @import url()
//     $collection =& css_import(trim($matches[1]), $pipeline);
//     return $collection;
//   } elseif (preg_match("/@import\s+(.*);/",$property, $matches)) {
//     // @import <url>
//     $collection =& css_import(trim($matches[1]), $pipeline);
//     return $collection;
//   } else {
//     $collection =& new CSSPropertyCollection();
//     return $collection;
//   };
// }


function is_allowed_media( $media_list ) {
	// Now we've got the list of media this style can be applied to;
	// check if at least one of this media types is being used by the script
	//
	$allowed_media = config_get_allowed_media();
	$allowed_found = false;

	// Note that media names should be case-insensitive;
	// it is not guaranteed that $media_list contains lower-case variants,
	// as well as it is not guaranteed that configuration data contains them.
	// Thus, media name lists should be explicitly converted to lowercase
	$media_list    = array_map( 'strtolower', $media_list );
	$allowed_media = array_map( 'strtolower', $allowed_media );

	foreach ( $media_list as $media ) {
		$allowed_found |= ( array_search( $media, $allowed_media ) !== false );
	};

	return $allowed_found;
}


define( 'BACKGROUND_ATTACHMENT_SCROLL', 1 );
define( 'BACKGROUND_ATTACHMENT_FIXED', 2 );

class CSSBackgroundAttachment extends CSSSubFieldProperty {
	function get_property_code() {
		return CSS_BACKGROUND_ATTACHMENT;
	}

	function get_property_name() {
		return 'background-attachment';
	}

	function default_value() {
		return BACKGROUND_ATTACHMENT_SCROLL;
	}

	function &parse( $value_string ) {
		if ( $value_string === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		if ( preg_match( '/\bscroll\b/', $value_string ) ) {
			$value = BACKGROUND_ATTACHMENT_SCROLL;
		} elseif ( preg_match( '/\bfixed\b/', $value_string ) ) {
			$value = BACKGROUND_ATTACHMENT_FIXED;
		} else {
			$value = BACKGROUND_ATTACHMENT_SCROLL;
		};

		return $value;
	}
}

// $Header: /cvsroot/html2ps/css.background.color.inc.php,v 1.16 2007/01/24 18:55:50 Konstantin Exp $

// 'background-color' and color part of 'background' CSS properies handler

class CSSBackgroundColor extends CSSSubFieldProperty {
	function get_property_code() {
		return CSS_BACKGROUND_COLOR;
	}

	function get_property_name() {
		return 'background-color';
	}

	function default_value() {
		// Transparent color
		return new Color( array( 0, 0, 0 ), true );
	}

	// Note: we cannot use parse_color_declaration here directly, as at won't process composite 'background' values
	// containing, say, both background image url and background color; on the other side,
	// parse_color_declaration slow down if we'll put this composite-value processing there
	function parse( $value ) {
		// We should not split terms at whitespaces immediately preceeded by ( or , symbols, as
		// it would break "rgb( xxx, yyy, zzz)" notation
		//
		// As whitespace could be preceeded by another whitespace, we should prevent breaking
		// value in the middle of long whitespace too
		$terms = preg_split( '/(?<![,(\s])\s+(?![,)\s])/ ', $value );

		// Note that color declaration always will contain only one word;
		// thus, we can split out value into words and try to parse each one as color
		// if parse_color_declaration returns transparent value, it is possible not
		// a color part of background declaration
		foreach ( $terms as $term ) {
			if ( $term === 'inherit' ) {
				return CSS_PROPERTY_INHERIT;
			}

			$color =& parse_color_declaration( $term );

			if ( ! $color->isTransparent() ) {
				return $color;
			}
		}

		return CSSBackgroundColor::default_value();
	}

	function get_visible_background_color() {
		$owner =& $this->owner();

		for ( $i = 0, $size = count( $owner->_stack ); $i < $size; $i ++ ) {
			if ( $owner->_stack[ $i ][0]->color[0] >= 0 ) {
				return $owner->_stack[ $i ][0]->color;
			};
		};

		return array( 255, 255, 255 );
	}
}


// $Header: /cvsroot/html2ps/css.background.image.inc.php,v 1.16 2006/07/09 09:07:44 Konstantin Exp $

class CSSBackgroundImage extends CSSSubFieldProperty {
	function get_property_code() {
		return CSS_BACKGROUND_IMAGE;
	}

	function get_property_name() {
		return 'background-image';
	}

	function default_value() {
		return new BackgroundImage( null, null );
	}

	function parse( $value, &$pipeline ) {
		global $g_config;
		if ( ! $g_config['renderimages'] ) {
			return CSSBackgroundImage::default_value();
		};

		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		}

		// 'url' value
		if ( preg_match( "/url\((.*[^\\\\]?)\)/is", $value, $matches ) ) {
			$url = $matches[1];

			$full_url = $pipeline->guess_url( css_remove_value_quotes( $url ) );

			return new BackgroundImage( $full_url,
				ImageFactory::get( $full_url, $pipeline ) );
		}

		// 'none' and unrecognzed values
		return CSSBackgroundImage::default_value();
	}
}


// $Header: /cvsroot/html2ps/css.background.repeat.inc.php,v 1.8 2006/07/09 09:07:44 Konstantin Exp $

define( 'BR_REPEAT', 0 );
define( 'BR_REPEAT_X', 1 );
define( 'BR_REPEAT_Y', 2 );
define( 'BR_NO_REPEAT', 3 );

class CSSBackgroundRepeat extends CSSSubFieldProperty {
	function get_property_code() {
		return CSS_BACKGROUND_REPEAT;
	}

	function get_property_name() {
		return 'background-repeat';
	}

	function default_value() {
		return BR_REPEAT;
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		}

		// Note that we cannot just compare $value with these strings for equality,
		// as 'parse' can be called with composite 'background' value as a parameter,
		// say, 'black url(picture.gif) repeat', instead of just using 'repeat'

		// Also, note that
		// 1) 'repeat-x' value will match 'repeat' term
		// 2) background-image 'url' values may contain these values as substrings
		// to avoid these problems, we'll add spaced to the beginning and to the end of value,
		// and will search for space-padded values, instead of raw substrings
		$value = " " . $value . " ";
		if ( strpos( $value, ' repeat-x ' ) !== false ) {
			return BR_REPEAT_X;
		};
		if ( strpos( $value, ' repeat-y ' ) !== false ) {
			return BR_REPEAT_Y;
		};
		if ( strpos( $value, ' no-repeat ' ) !== false ) {
			return BR_NO_REPEAT;
		};
		if ( strpos( $value, ' repeat ' ) !== false ) {
			return BR_REPEAT;
		};

		return CSSBackgroundRepeat::default_value();
	}
}


// The background-position value is an array containing two array for X and Y position correspondingly
// each coordinate-position array, in its turn containes two values:
// first, the numeric value of percentage or units
// second, flag indication that this value is a percentage (true) or plain unit value (false)

define( 'LENGTH_REGEXP', "(?:-?\d*\.?\d+(?:em|ex|px|in|cm|mm|pt|pc)\b|-?\d+(?:em|ex|px|in|cm|mm|pt|pc)\b)" );
define( 'PERCENTAGE_REGEXP', "\b\d+%" );
define( 'TEXT_REGEXP', "\b(?:top|bottom|left|right|center)\b" );

define( 'BG_POSITION_SUBVALUE_TYPE_HORZ', 1 );
define( 'BG_POSITION_SUBVALUE_TYPE_VERT', 2 );

class CSSBackgroundPosition extends CSSSubFieldProperty {
	function get_property_code() {
		return CSS_BACKGROUND_POSITION;
	}

	function get_property_name() {
		return 'background-position';
	}

	function default_value() {
		return new BackgroundPosition( 0, true,
			0, true );
	}

	function build_subvalue( $value ) {
		if ( $value === "left" ||
		     $value === "top" ) {
			return array( 0, true );
		};

		if ( $value === "right" ||
		     $value === "bottom" ) {
			return array( 100, true );
		};

		if ( $value === "center" ) {
			return array( 50, true );
		};

		if ( substr( $value, strlen( $value ) - 1, 1 ) === "%" ) {
			return array( (int) $value, true );
		} else {
			return array( $value, false );
		};
	}

	function build_value( $x, $y ) {
		return array(
			CSSBackgroundPosition::build_subvalue( $x ),
			CSSBackgroundPosition::build_subvalue( $y )
		);
	}

	function detect_type( $value ) {
		if ( $value === "left" || $value === "right" ) {
			return BG_POSITION_SUBVALUE_TYPE_HORZ;
		};
		if ( $value === "top" || $value === "bottom" ) {
			return BG_POSITION_SUBVALUE_TYPE_VERT;
		};

		return null;
	}

	// See CSS 2.1 'background-position' for description of possible values
	//
	function parse_in( $value ) {
		if ( preg_match( "/(" . LENGTH_REGEXP . "|" . PERCENTAGE_REGEXP . "|" . TEXT_REGEXP . "|\b0\b)\s+(" . LENGTH_REGEXP . "|" . PERCENTAGE_REGEXP . "|" . TEXT_REGEXP . "|\b0\b)/", $value, $matches ) ) {
			$x = $matches[1];
			$y = $matches[2];

			$type_x = CSSBackgroundPosition::detect_type( $x );
			$type_y = CSSBackgroundPosition::detect_type( $y );

			if ( is_null( $type_x ) && is_null( $type_y ) ) {
				return CSSBackgroundPosition::build_value( $x, $y );
			};

			if ( $type_x == BG_POSITION_SUBVALUE_TYPE_HORZ ||
			     $type_y == BG_POSITION_SUBVALUE_TYPE_VERT ) {
				return CSSBackgroundPosition::build_value( $x, $y );
			};

			return CSSBackgroundPosition::build_value( $y, $x );
		};

		// These values should be processed separately at lastt
		if ( preg_match( "/\b(top)\b/", $value ) ) {
			return array( array( 50, true ), array( 0, true ) );
		};
		if ( preg_match( "/\b(center)\b/", $value ) ) {
			return array( array( 50, true ), array( 50, true ) );
		};
		if ( preg_match( "/\b(bottom)\b/", $value ) ) {
			return array( array( 50, true ), array( 100, true ) );
		};
		if ( preg_match( "/\b(left)\b/", $value ) ) {
			return array( array( 0, true ), array( 50, true ) );
		};
		if ( preg_match( "/\b(right)\b/", $value ) ) {
			return array( array( 100, true ), array( 50, true ) );
		};

		if ( preg_match( "/" . LENGTH_REGEXP . "|" . PERCENTAGE_REGEXP . "/", $value, $matches ) ) {
			$x = $matches[0];

			return CSSBackgroundPosition::build_value( $x, "50%" );
		};

		return null;
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$value = CSSBackgroundPosition::parse_in( $value );

		return new BackgroundPosition( $value[0][0], $value[0][1],
			$value[1][0], $value[1][1] );
	}
}

// $Header: /cvsroot/html2ps/css.background.inc.php,v 1.23 2007/03/15 18:37:30 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.background.php' );

class CSSBackground extends CSSPropertyHandler {
	var $default_value;

	function get_property_code() {
		return CSS_BACKGROUND;
	}

	function get_property_name() {
		return 'background';
	}

	function CSSBackground() {
		$this->default_value = new Background( CSSBackgroundColor::default_value(),
			CSSBackgroundImage::default_value(),
			CSSBackgroundRepeat::default_value(),
			CSSBackgroundPosition::default_value(),
			CSSBackgroundAttachment::default_value() );

		$this->CSSPropertyHandler( true, false );
	}

	function inherit( $state, &$new_state ) {
		// Determine parent 'display' value
		$parent_display = $state[ CSS_DISPLAY ];

		// If parent is a table row, inherit the background settings
		$this->replace_array( ( $parent_display == 'table-row' ) ? $state[ CSS_BACKGROUND ] : $this->default_value(),
			$new_state );
	}

	function default_value() {
		return $this->default_value->copy();
	}

	function parse( $value, &$pipeline ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		}

		$background = new Background( CSSBackgroundColor::parse( $value ),
			CSSBackgroundImage::parse( $value, $pipeline ),
			CSSBackgroundRepeat::parse( $value ),
			CSSBackgroundPosition::parse( $value ),
			CSSBackgroundAttachment::parse( $value ) );

		return $background;
	}
}

$bg = new CSSBackground;

CSS::register_css_property( $bg );
CSS::register_css_property( new CSSBackgroundColor( $bg, '_color' ) );
CSS::register_css_property( new CSSBackgroundImage( $bg, '_image' ) );
CSS::register_css_property( new CSSBackgroundRepeat( $bg, '_repeat' ) );
CSS::register_css_property( new CSSBackgroundPosition( $bg, '_position' ) );
CSS::register_css_property( new CSSBackgroundAttachment( $bg, '_attachment' ) );


// $Header: /cvsroot/html2ps/css.border.inc.php,v 1.25 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'css.border.color.inc.php' );
//require_once(HTML2PS_DIR . 'css.border.style.inc.php');
require_once( HTML2PS_DIR . 'css.border.width.inc.php' );

require_once( HTML2PS_DIR . 'css.border.top.inc.php' );
require_once( HTML2PS_DIR . 'css.border.right.inc.php' );
require_once( HTML2PS_DIR . 'css.border.left.inc.php' );
require_once( HTML2PS_DIR . 'css.border.bottom.inc.php' );

require_once( HTML2PS_DIR . 'css.border.top.color.inc.php' );
require_once( HTML2PS_DIR . 'css.border.right.color.inc.php' );
require_once( HTML2PS_DIR . 'css.border.left.color.inc.php' );
require_once( HTML2PS_DIR . 'css.border.bottom.color.inc.php' );

require_once( HTML2PS_DIR . 'css.border.top.style.inc.php' );
require_once( HTML2PS_DIR . 'css.border.right.style.inc.php' );
require_once( HTML2PS_DIR . 'css.border.left.style.inc.php' );
require_once( HTML2PS_DIR . 'css.border.bottom.style.inc.php' );

require_once( HTML2PS_DIR . 'css.border.top.width.inc.php' );
require_once( HTML2PS_DIR . 'css.border.right.width.inc.php' );
require_once( HTML2PS_DIR . 'css.border.left.width.inc.php' );
require_once( HTML2PS_DIR . 'css.border.bottom.width.inc.php' );

require_once( HTML2PS_DIR . 'value.generic.length.php' );
require_once( HTML2PS_DIR . 'value.border.class.php' );
require_once( HTML2PS_DIR . 'value.border.edge.class.php' );

define( 'BORDER_VALUE_COLOR', 1 );
define( 'BORDER_VALUE_WIDTH', 2 );
define( 'BORDER_VALUE_STYLE', 3 );

class CSSBorder extends CSSPropertyHandler {
	var $_defaultValue;

	function CSSBorder() {
		$this->CSSPropertyHandler( false, false );

		$this->_defaultValue = BorderPDF::create( array(
			'top'    => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'right'  => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'bottom' => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'left'   => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			)
		) );
	}

	function default_value() {
		return $this->_defaultValue;
	}

	function parse( $value ) {
		if ( $value == 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		// Remove spaces between color values in rgb() color definition; this will allow us to tread
		// this declaration as a single value
		$value = preg_replace( "/\s*,\s*/", ",", $value );

		// Remove spaces before and after parens in rgb color definition
		$value = preg_replace( "/rgb\s*\(\s*(.*?)\s*\)/", 'rgb(\1)', $value );

		$subvalues = explode( " ", $value );

		$border = CSS::getDefaultValue( CSS_BORDER );

		foreach ( $subvalues as $subvalue ) {
			$subvalue = trim( strtolower( $subvalue ) );

			switch ( CSSBorder::detect_border_value_type( $subvalue ) ) {
				case BORDER_VALUE_COLOR:
					$color_handler = CSS::get_handler( CSS_BORDER_COLOR );
					$border_color  = $color_handler->parse( $subvalue );
					$color_handler->set_value( $border, $border_color );
					break;
				case BORDER_VALUE_WIDTH:
					$width_handler = CSS::get_handler( CSS_BORDER_WIDTH );
					$border_width  = $width_handler->parse( $subvalue );
					$width_handler->set_value( $border, $border_width );
					break;
				case BORDER_VALUE_STYLE:
					$style_handler = CSS::get_handler( CSS_BORDER_STYLE );
					$border_style  = $style_handler->parse( $subvalue );
					$style_handler->set_value( $border, $border_style );
					break;
			};
		};

		return $border;
	}

	function get_property_code() {
		return CSS_BORDER;
	}

	function get_property_name() {
		return 'border';
	}

	function detect_border_value_type( $value ) {
		$color = _parse_color_declaration( $value, $success );
		if ( $success ) {
			return BORDER_VALUE_COLOR;
		};

		//     if (preg_match("/\b(transparent|black|silver|gray|white|maroon|red|purple|fuchsia|green|lime|olive|yellow|navy|blue|teal|aqua|rgb(.*?))\b/i",$value)) { return BORDER_VALUE_COLOR; };
		//     // We must detect hecadecimal values separately, as #-sign will not match the \b metacharacter at the beginning of previous regexp
		//     if (preg_match("/#([[:xdigit:]]{3}|[[:xdigit:]]{6})\b/i",$value)) { return BORDER_VALUE_COLOR; };

		// Note that unit name is in general not required, so that we can meet rule like "border: 0" in CSS!
		if ( preg_match( "/\b(thin|medium|thick|[+-]?\d+(.\d*)?(em|ex|px|in|cm|mm|pt|pc)?)\b/i", $value ) ) {
			return BORDER_VALUE_WIDTH;
		};
		if ( preg_match( "/\b(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)\b/", $value ) ) {
			return BORDER_VALUE_STYLE;
		};

		return;
	}
}

$border = new CSSBorder();
CSS::register_css_property( $border );

CSS::register_css_property( new CSSBorderColor( $border ) );
CSS::register_css_property( new CSSBorderWidth( $border ) );
CSS::register_css_property( new CSSBorderStyle( $border ) );

CSS::register_css_property( new CSSBorderTop( $border, 'top' ) );
CSS::register_css_property( new CSSBorderRight( $border, 'right' ) );
CSS::register_css_property( new CSSBorderBottom( $border, 'bottom' ) );
CSS::register_css_property( new CSSBorderLeft( $border, 'left' ) );

CSS::register_css_property( new CSSBorderLeftColor( $border ) );
CSS::register_css_property( new CSSBorderTopColor( $border ) );
CSS::register_css_property( new CSSBorderRightColor( $border ) );
CSS::register_css_property( new CSSBorderBottomColor( $border ) );

CSS::register_css_property( new CSSBorderLeftStyle( $border ) );
CSS::register_css_property( new CSSBorderTopStyle( $border ) );
CSS::register_css_property( new CSSBorderRightStyle( $border ) );
CSS::register_css_property( new CSSBorderBottomStyle( $border ) );

CSS::register_css_property( new CSSBorderLeftWidth( $border ) );
CSS::register_css_property( new CSSBorderTopWidth( $border ) );
CSS::register_css_property( new CSSBorderRightWidth( $border ) );
CSS::register_css_property( new CSSBorderBottomWidth( $border ) );


// $Header: /cvsroot/html2ps/css.border.style.inc.php,v 1.6 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.generic.php' );

class BorderStyle extends CSSValue {
	var $left;
	var $right;
	var $top;
	var $bottom;

	function &copy() {
		$value =& new BorderStyle( $this->top, $this->right, $this->bottom, $this->left );

		return $value;
	}

	function BorderStyle( $top, $right, $bottom, $left ) {
		$this->left   = $left;
		$this->right  = $right;
		$this->top    = $top;
		$this->bottom = $bottom;
	}
}

class CSSBorderStyle extends CSSSubProperty {
	var $_defaultValue;

	function CSSBorderStyle( &$owner ) {
		$this->CSSSubProperty( $owner );

		$this->_defaultValue = new BorderStyle( BS_NONE,
			BS_NONE,
			BS_NONE,
			BS_NONE );
	}

	function set_value( &$owner_value, &$value ) {
		if ( $value != CSS_PROPERTY_INHERIT ) {
			$owner_value->top->style    = $value->top;
			$owner_value->right->style  = $value->right;
			$owner_value->bottom->style = $value->bottom;
			$owner_value->left->style   = $value->left;
		} else {
			$owner_value->top->style    = CSS_PROPERTY_INHERIT;
			$owner_value->right->style  = CSS_PROPERTY_INHERIT;
			$owner_value->bottom->style = CSS_PROPERTY_INHERIT;
			$owner_value->left->style   = CSS_PROPERTY_INHERIT;
		};
	}

	function get_value( &$owner_value ) {
		return new BorderStyle( $owner_value->top->style,
			$owner_value->right->style,
			$owner_value->bottom->style,
			$owner_value->left->style );
	}

	function get_property_code() {
		return CSS_BORDER_STYLE;
	}

	function get_property_name() {
		return 'border-style';
	}

	function default_value() {
		return $this->_defaultValue;
	}

	function parse_style( $value ) {
		switch ( $value ) {
			case "solid":
				return BS_SOLID;
			case "dashed":
				return BS_DASHED;
			case "dotted":
				return BS_DOTTED;
			case "double":
				return BS_DOUBLE;
			case "inset":
				return BS_INSET;
			case "outset":
				return BS_OUTSET;
			case "groove":
				return BS_GROOVE;
			case "ridge":
				return BS_RIDGE;
			default:
				return BS_NONE;
		};
	}

	function parse_in( $value ) {
		$values = explode( " ", $value );

		switch ( count( $values ) ) {
			case 1:
				$v1 = $this->parse_style( $values[0] );

				return array( $v1, $v1, $v1, $v1 );
			case 2:
				$v1 = $this->parse_style( $values[0] );
				$v2 = $this->parse_style( $values[1] );

				return array( $v1, $v2, $v1, $v2 );
			case 3:
				$v1 = $this->parse_style( $values[0] );
				$v2 = $this->parse_style( $values[1] );
				$v3 = $this->parse_style( $values[2] );

				return array( $v1, $v2, $v3, $v2 );
			case 4:
				$v1 = $this->parse_style( $values[0] );
				$v2 = $this->parse_style( $values[1] );
				$v3 = $this->parse_style( $values[2] );
				$v4 = $this->parse_style( $values[3] );

				return array( $v1, $v2, $v3, $v4 );
			default:
				return $this->default_value();
		};
	}

	function parse( $value ) {
		if ( $value == 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		}

		$values = $this->parse_in( $value );

		return new BorderStyle( $values[0],
			$values[1],
			$values[2],
			$values[3] );
	}
}


// $Header: /cvsroot/html2ps/css.border.collapse.inc.php,v 1.7 2006/07/09 09:07:44 Konstantin Exp $

define( 'BORDER_COLLAPSE', 1 );
define( 'BORDER_SEPARATE', 2 );

class CSSBorderCollapse extends CSSPropertyStringSet {
	function CSSBorderCollapse() {
		$this->CSSPropertyStringSet( true,
			true,
			array(
				'inherit'  => CSS_PROPERTY_INHERIT,
				'collapse' => BORDER_COLLAPSE,
				'separate' => BORDER_SEPARATE
			) );
	}

	function default_value() {
		return BORDER_SEPARATE;
	}

	function get_property_code() {
		return CSS_BORDER_COLLAPSE;
	}

	function get_property_name() {
		return 'border-collapse';
	}
}

CSS::register_css_property( new CSSBorderCollapse );


// $Header: /cvsroot/html2ps/css.bottom.inc.php,v 1.6 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.bottom.php' );

/**
 * 'bottom'
 *  Value:       <length> | <percentage> | auto | inherit
 *  Initial:     auto
 *  Applies to:  positioned elements
 *  Inherited:   no
 *  Percentages: refer to height of containing block
 *  Media:       visual
 *  Computed  value:  for  'position:relative', see  section  Relative
 *  Positioning.   For   'position:static',   'auto'.  Otherwise:   if
 *  specified  as  a length,  the  corresponding  absolute length;  if
 *  specified as a percentage, the specified value; otherwise, 'auto'.
 *
 * Like 'top',  but specifies  how far a  box's bottom margin  edge is
 * offset  above  the  bottom  of  the  box's  containing  block.  For
 * relatively  positioned boxes,  the offset  is with  respect  to the
 * bottom  edge of  the box  itself. Note:  For  absolutely positioned
 * elements whose containing block  is based on a block-level element,
 * this property is an offset from the padding edge of that element.
 */
class CSSBottom extends CSSPropertyHandler {
	function CSSBottom() {
		$this->CSSPropertyHandler( false, false );
		$this->_autoValue = ValueBottom::fromString( 'auto' );
	}

	function _getAutoValue() {
		return $this->_autoValue->copy();
	}

	function default_value() {
		return $this->_getAutoValue();
	}

	function get_property_code() {
		return CSS_BOTTOM;
	}

	function get_property_name() {
		return 'bottom';
	}

	function parse( $value ) {
		return ValueBottom::fromString( $value );
	}
}

CSS::register_css_property( new CSSBottom );


// $Header: /cvsroot/html2ps/css.clear.inc.php,v 1.9 2006/09/07 18:38:13 Konstantin Exp $

define( 'CLEAR_NONE', 0 );
define( 'CLEAR_LEFT', 1 );
define( 'CLEAR_RIGHT', 2 );
define( 'CLEAR_BOTH', 3 );

class CSSClear extends CSSPropertyStringSet {
	function CSSClear() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'inherit' => CSS_PROPERTY_INHERIT,
				'left'    => CLEAR_LEFT,
				'right'   => CLEAR_RIGHT,
				'both'    => CLEAR_BOTH,
				'none'    => CLEAR_NONE
			) );
	}

	function default_value() {
		return CLEAR_NONE;
	}

	function get_property_code() {
		return CSS_CLEAR;
	}

	function get_property_name() {
		return 'clear';
	}
}

CSS::register_css_property( new CSSClear );


// $Header: /cvsroot/html2ps/css.color.inc.php,v 1.13 2007/01/24 18:55:51 Konstantin Exp $

class CSSColor extends CSSPropertyHandler {
	function CSSColor() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return new Color( array( 0, 0, 0 ), false );
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return parse_color_declaration( $value );
	}

	function get_property_code() {
		return CSS_COLOR;
	}

	function get_property_name() {
		return 'color';
	}
}

CSS::register_css_property( new CSSColor );


// $Header: /cvsroot/html2ps/css.direction.inc.php,v 1.7 2006/07/09 09:07:44 Konstantin Exp $

define( 'DIRECTION_LTR', 1 );
define( 'DIRECTION_RTF', 1 );

class CSSDirection extends CSSPropertyStringSet {
	function CSSDirection() {
		$this->CSSPropertyStringSet( true,
			true,
			array(
				'lrt' => DIRECTION_LTR,
				'rtl' => DIRECTION_RTF
			) );
	}

	function default_value() {
		return DIRECTION_LTR;
	}

	function get_property_code() {
		return CSS_DIRECTION;
	}

	function get_property_name() {
		return 'direction';
	}
}

CSS::register_css_property( new CSSDirection );


// $Header: /cvsroot/html2ps/css.html2ps.html.content.inc.php,v 1.3 2007/03/15 18:37:30 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.content.php' );

class CSSHTML2PSHTMLContent extends CSSPropertyHandler {
	function CSSHTML2PSHTMLContent() {
		$this->CSSPropertyHandler( false, false );
	}

	function &default_value() {
		$data =& new ValueContent();

		return $data;
	}

	// CSS 2.1 p 12.2:
	// Value: [ <string> | <uri> | <counter> | attr(X) | open-quote | close-quote | no-open-quote | no-close-quote ]+ | inherit
	//
	// TODO: process values other than <string>
	//
	function &parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$value_obj =& ValueContent::parse( $value );

		return $value_obj;
	}

	function get_property_code() {
		return CSS_HTML2PS_HTML_CONTENT;
	}

	function get_property_name() {
		return '-html2ps-html-content';
	}
}

CSS::register_css_property( new CSSHTML2PSHTMLContent );


// $Header: /cvsroot/html2ps/css.html2ps.pseudoelements.inc.php,v 1.1 2006/09/07 18:38:14 Konstantin Exp $

define( 'CSS_HTML2PS_PSEUDOELEMENTS_NONE', 0 );
define( 'CSS_HTML2PS_PSEUDOELEMENTS_BEFORE', 1 );
define( 'CSS_HTML2PS_PSEUDOELEMENTS_AFTER', 2 );

class CSSHTML2PSPseudoelements extends CSSPropertyHandler {
	function CSSHTML2PSPseudoelements() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return CSS_HTML2PS_PSEUDOELEMENTS_NONE;
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_HTML2PS_PSEUDOELEMENTS;
	}

	function get_property_name() {
		return '-html2ps-pseudoelements';
	}
}

CSS::register_css_property( new CSSHTML2PSPseudoelements );


class CSSHTML2PSPixels extends CSSPropertyHandler {
	function CSSHTML2PSPixels() {
		$this->CSSPropertyHandler( false, false );
	}

	function &default_value() {
		$value = 800;

		return $value;
	}

	function &parse( $value ) {
		$value_data = (int) $value;

		return $value_data;
	}

	function get_property_code() {
		return CSS_HTML2PS_PIXELS;
	}

	function get_property_name() {
		return '-html2ps-pixels';
	}
}

CSS::register_css_property( new CSSHTML2PSPixels );


// $Header: /cvsroot/html2ps/css.content.inc.php,v 1.8 2007/03/15 18:37:30 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.content.php' );

/**
 * Handles 'content' CSS property (
 *
 * 'content'
 *  Value:   normal   |   [   <string>   |   <uri>   |   <counter>   |
 *  attr(<identifier>)  | open-quote |  close-quote |  no-open-quote |
 *  no-close-quote ]+ | inherit
 *  Initial:    normal
 *  Applies to:    :before and :after pseudo-elements
 *  Inherited:    no
 *  Percentages:    N/A
 *  Media:    all
 *  Computed  value: for  URI  values, the  absolute  URI; for  attr()
 *  values, the resulting string; otherwise as specified
 *
 * This property  is used with the :before  and :after pseudo-elements
 * to  generate  content in  a  document.  Values  have the  following
 * meanings:
 *
 * normal
 *    The pseudo-element is not generated.
 * <string>
 *    Text content (see the section on strings).
 * <uri>
 *    The value  is a URI that  designates an external  resource. If a
 *    user  agent cannot  support the  resource because  of  the media
 *    types it supports, it must ignore the resource.
 * <counter>
 *    Counters  may   be  specified  with   two  different  functions:
 *    'counter()'   or  'counters()'.  The   former  has   two  forms:
 *    'counter(name)' or 'counter(name, style)'. The generated text is
 *    the value of  the named counter at this  point in the formatting
 *    structure; it is formatted  in the indicated style ('decimal' by
 *    default).   The   latter    function   also   has   two   forms:
 *    'counters(name, string)' or 'counters(name, string, style)'. The
 *    generated text is the value  of all counters with the given name
 *    at  this point  in the  formatting structure,  separated  by the
 *    specified  string. The  counters are  rendered in  the indicated
 *    style  ('decimal'  by default).  See  the  section on  automatic
 *    counters and numbering for more information.
 * open-quote and close-quote
 *    These  values are replaced  by the  appropriate string  from the
 *    'quotes' property.
 * no-open-quote and no-close-quote
 *    Same as 'none', but increments (decrements) the level of nesting
 *    for quotes.
 * attr(X)
 *    This function returns  as a string the value  of attribute X for
 *    the subject of the selector. The string is not parsed by the CSS
 *    processor.  If  the subject  of  the  selector  doesn't have  an
 *    attribute X,  an empty string is  returned. The case-sensitivity
 *    of attribute  names depends on  the document language.  Note. In
 *    CSS 2.1,  it is  not possible to  refer to attribute  values for
 *    other elements than the subject of the selector.
 */
class CSSContent extends CSSPropertyHandler {
	function CSSContent() {
		$this->CSSPropertyHandler( false, false );
	}

	function &default_value() {
		$data =& new ValueContent();

		return $data;
	}

	// CSS 2.1 p 12.2:
	// Value: [ <string> | <uri> | <counter> | attr(X) | open-quote | close-quote | no-open-quote | no-close-quote ]+ | inherit
	//
	// TODO: process values other than <string>
	//
	function &parse( $value ) {
		if ( $value == 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$value_obj =& ValueContent::parse( $value );

		return $value_obj;
	}

	function get_property_code() {
		return CSS_CONTENT;
	}

	function get_property_name() {
		return 'content';
	}
}

CSS::register_css_property( new CSSContent );


// $Header: /cvsroot/html2ps/css.display.inc.php,v 1.21 2006/09/07 18:38:13 Konstantin Exp $

class CSSDisplay extends CSSPropertyHandler {
	function CSSDisplay() {
		$this->CSSPropertyHandler( false, false );
	}

	function get_parent() {
		if ( isset( $this->_stack[1] ) ) {
			return $this->_stack[1][0];
		} else {
			return 'block';
		};
	}

	function default_value() {
		return "inline";
	}

	function get_property_code() {
		return CSS_DISPLAY;
	}

	function get_property_name() {
		return 'display';
	}

	function parse( $value ) {
		return trim( strtolower( $value ) );
	}
}

CSS::register_css_property( new CSSDisplay );

function is_inline_element( $display ) {
	return
		$display == "inline" ||
		$display == "inline-table" ||
		$display == "compact" ||
		$display == "run-in" ||
		$display == "-button" ||
		$display == "-checkbox" ||
		$display == "-iframe" ||
		$display == "-image" ||
		$display == "inline-block" ||
		$display == "-radio" ||
		$display == "-select";
}

// $Header: /cvsroot/html2ps/css.float.inc.php,v 1.7 2006/07/09 09:07:44 Konstantin Exp $

define( 'FLOAT_NONE', 0 );
define( 'FLOAT_LEFT', 1 );
define( 'FLOAT_RIGHT', 2 );

class CSSFloat extends CSSPropertyStringSet {
	function CSSFloat() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'left'  => FLOAT_LEFT,
				'right' => FLOAT_RIGHT,
				'none'  => FLOAT_NONE
			) );
	}

	function default_value() {
		return FLOAT_NONE;
	}

	function get_property_code() {
		return CSS_FLOAT;
	}

	function get_property_name() {
		return 'float';
	}
}

CSS::register_css_property( new CSSFloat );


// $Header: /cvsroot/html2ps/css.font.inc.php,v 1.28 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.font.class.php' );
require_once( HTML2PS_DIR . 'font.resolver.class.php' );
require_once( HTML2PS_DIR . 'font.constants.inc.php' );

require_once( HTML2PS_DIR . 'css.font-family.inc.php' );
require_once( HTML2PS_DIR . 'css.font-style.inc.php' );
require_once( HTML2PS_DIR . 'css.font-weight.inc.php' );
require_once( HTML2PS_DIR . 'css.font-size.inc.php' );
require_once( HTML2PS_DIR . 'css.line-height.inc.php' );

require_once( HTML2PS_DIR . 'value.font.class.php' );

define( 'FONT_VALUE_STYLE', 0 );
define( 'FONT_VALUE_WEIGHT', 1 );
define( 'FONT_VALUE_SIZE', 2 );
define( 'FONT_VALUE_FAMILY', 3 );

function detect_font_value_type( $value ) {
	if ( preg_match( "/^normal|italic|oblique$/", $value ) ) {
		return FONT_VALUE_STYLE;
	}
	if ( preg_match( "/^normal|bold|bolder|lighter|[1-9]00$/", $value ) ) {
		return FONT_VALUE_WEIGHT;
	}

	if ( preg_match( "#/#", $value ) ) {
		return FONT_VALUE_SIZE;
	}
	if ( preg_match( "#^\d+\.?\d*%$#", $value ) ) {
		return FONT_VALUE_SIZE;
	}
	if ( preg_match( "#^(xx-small|x-small|small|medium|large|x-large|xx-large)$#", $value ) ) {
		return FONT_VALUE_SIZE;
	}
	if ( preg_match( "#^(larger|smaller)$#", $value ) ) {
		return FONT_VALUE_SIZE;
	}
	if ( preg_match( "#^(\d*(.\d*)?(pt|pc|in|mm|cm|px|em|ex))$#", $value ) ) {
		return FONT_VALUE_SIZE;
	}

	return FONT_VALUE_FAMILY;
}

// ----

class CSSFont extends CSSPropertyHandler {
	var $_defaultValue;

	function CSSFont() {
		$this->CSSPropertyHandler( true, true );

		$this->_defaultValue = null;
	}

	function default_value() {
		if ( is_null( $this->_defaultValue ) ) {
			$this->_defaultValue = new ValueFont;

			$size_handler = CSS::get_handler( CSS_FONT_SIZE );
			$default_size = $size_handler->default_value();

			$this->_defaultValue->size        = $default_size->copy();
			$this->_defaultValue->weight      = CSSFontWeight::default_value();
			$this->_defaultValue->style       = CSSFontStyle::default_value();
			$this->_defaultValue->family      = CSSFontFamily::default_value();
			$this->_defaultValue->line_height = CSS::getDefaultValue( CSS_LINE_HEIGHT );
		};

		return $this->_defaultValue;
	}

	function parse( $value ) {
		$font = CSS::getDefaultValue( CSS_FONT );

		if ( $value === 'inherit' ) {
			$font->style       = CSS_PROPERTY_INHERIT;
			$font->weight      = CSS_PROPERTY_INHERIT;
			$font->size        = CSS_PROPERTY_INHERIT;
			$font->family      = CSS_PROPERTY_INHERIT;
			$font->line_height = CSS_PROPERTY_INHERIT;

			return $font;
		};


		// according to CSS 2.1 standard,
		// value of 'font' CSS property can be represented as follows:
		//   [ <'font-style'> || <'font-variant'> || <'font-weight'> ]? <'font-size'> [ / <'line-height'> ]? <'font-family'> ] |
		//   caption | icon | menu | message-box | small-caption | status-bar | inherit

		// Note that font-family value, unlike other values, can contain spaces (in this case it should be quoted)
		// Breaking value by spaces, we'll break such multi-word families.

		// Replace all white space sequences with only one space;
		// Remove spaces after commas; it will allow us
		// to split value correctly using look-backward expressions
		$value = preg_replace( "/\s+/", " ", $value );
		$value = preg_replace( "/,\s+/", ",", $value );
		$value = preg_replace( "#\s*/\s*#", "/", $value );

		// Split value to subvalues by all whitespaces NOT preceeded by comma;
		// thus, we'll keep all alternative font-families together instead of breaking them.
		// Still we have a problem with multi-word family names.
		$subvalues = preg_split( "/ /", $value );

		// Let's scan subvalues we've received and join values containing multiword family names
		$family_start        = 0;
		$family_running      = false;
		$family_double_quote = false;;

		for ( $i = 0, $num_subvalues = count( $subvalues ); $i < $num_subvalues; $i ++ ) {
			$current_value = $subvalues[ $i ];

			if ( $family_running ) {
				$subvalues[ $family_start ] .= " " . $subvalues[ $i ];

				// Remove this subvalues from the subvalue list at all
				array_splice( $subvalues, $i, 1 );

				$num_subvalues --;
				$i --;
			}

			// Check if current subvalue contains beginning of multi-word family name
			// We can detect it by searching for single or double quote without pair
			if ( $family_running && $family_double_quote && ! preg_match( '/^[^"]*("[^"]*")*[^"]*$/', $current_value ) ) {
				$family_running = false;
			} elseif ( $family_running && ! $family_double_quote && ! preg_match( "/^[^']*('[^']*')*[^']*$/", $current_value ) ) {
				$family_running = false;
			} elseif ( ! $family_running && ! preg_match( "/^[^']*('[^']*')*[^']*$/", $current_value ) ) {
				$family_running      = true;
				$family_start        = $i;
				$family_double_quote = false;
			} elseif ( ! $family_running && ! preg_match( '/^[^"]*("[^"]*")*[^"]*$/', $current_value ) ) {
				$family_running      = true;
				$family_start        = $i;
				$family_double_quote = true;
			}
		};

		// Now process subvalues one-by-one.
		foreach ( $subvalues as $subvalue ) {
			$subvalue      = trim( strtolower( $subvalue ) );
			$subvalue_type = detect_font_value_type( $subvalue );

			switch ( $subvalue_type ) {
				case FONT_VALUE_STYLE:
					$font->style = CSSFontStyle::parse( $subvalue );
					break;
				case FONT_VALUE_WEIGHT:
					$font->weight = CSSFontWeight::parse( $subvalue );
					break;
				case FONT_VALUE_SIZE:
					$size_subvalues = explode( '/', $subvalue );
					$font->size     = CSSFontSize::parse( $size_subvalues[0] );
					if ( isset( $size_subvalues[1] ) ) {
						$handler           =& CSS::get_handler( CSS_LINE_HEIGHT );
						$font->line_height = $handler->parse( $size_subvalues[1] );
					};
					break;
				case FONT_VALUE_FAMILY:
					$font->family = CSSFontFamily::parse( $subvalue );
					break;
			};
		};

		return $font;
	}

	function get_property_code() {
		return CSS_FONT;
	}

	function get_property_name() {
		return 'font';
	}

	function clearDefaultFlags( &$state ) {
		parent::clearDefaultFlags( $state );
		$state->set_propertyDefaultFlag( CSS_FONT_SIZE, false );
		$state->set_propertyDefaultFlag( CSS_FONT_STYLE, false );
		$state->set_propertyDefaultFlag( CSS_FONT_WEIGHT, false );
		$state->set_propertyDefaultFlag( CSS_FONT_FAMILY, false );
		$state->set_propertyDefaultFlag( CSS_LINE_HEIGHT, false );
	}
}

$font = new CSSFont;
CSS::register_css_property( $font );
CSS::register_css_property( new CSSFontSize( $font, 'size' ) );
CSS::register_css_property( new CSSFontStyle( $font, 'style' ) );
CSS::register_css_property( new CSSFontWeight( $font, 'weight' ) );
CSS::register_css_property( new CSSFontFamily( $font, 'family' ) );
CSS::register_css_property( new CSSLineHeight( $font, 'line_height' ) );


// $Header: /cvsroot/html2ps/css.height.inc.php,v 1.27 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.height.php' );

class CSSHeight extends CSSPropertyHandler {
	var $_autoValue;

	function CSSHeight() {
		$this->CSSPropertyHandler( true, false );
		$this->_autoValue = ValueHeight::fromString( 'auto' );
	}

	/**
	 * 'height' CSS property should be inherited by table cells from table rows
	 */
	function inherit( $old_state, &$new_state ) {
		$parent_display = $old_state[ CSS_DISPLAY ];
		$this->replace_array( ( $parent_display === 'table-row' ) ? $old_state[ CSS_HEIGHT ] : $this->default_value(),
			$new_state );
	}

	function _getAutoValue() {
		return $this->_autoValue->copy();
	}

	function default_value() {
		return $this->_getAutoValue();
	}

	function parse( $value ) {
		return ValueHeight::fromString( $value );
	}

	function get_property_code() {
		return CSS_HEIGHT;
	}

	function get_property_name() {
		return 'height';
	}
}

CSS::register_css_property( new CSSHeight );


// $Header: /cvsroot/html2ps/css.min-height.inc.php,v 1.3 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.min-height.php' );

class CSSMinHeight extends CSSPropertyHandler {
	var $_defaultValue;

	function CSSMinHeight() {
		$this->CSSPropertyHandler( true, false );
		$this->_defaultValue = ValueMinHeight::fromString( "0px" );
	}

	/**
	 * 'height' CSS property should be inherited by table cells from table rows
	 * (as, obviously, )
	 */
	function inherit( $old_state, &$new_state ) {
		$parent_display = $old_state[ CSS_DISPLAY ];
		if ( $parent_display === "table-row" ) {
			$new_state[ CSS_MIN_HEIGHT ] = $old_state[ CSS_MIN_HEIGHT ];

			return;
		}

		$new_state[ CSS_MIN_HEIGHT ] =
			is_inline_element( $parent_display ) ?
				$this->get( $old_state ) :
				$this->default_value();
	}

	function _getAutoValue() {
		return $this->default_value();
	}

	function default_value() {
		return $this->_defaultValue->copy();
	}

	function parse( $value ) {
		return ValueMinHeight::fromString( $value );
	}

	function get_property_code() {
		return CSS_MIN_HEIGHT;
	}

	function get_property_name() {
		return 'min-height';
	}
}

CSS::register_css_property( new CSSMinHeight );


// $Header: /cvsroot/html2ps/css.max-height.inc.php,v 1.3 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.max-height.php' );

class CSSMaxHeight extends CSSPropertyHandler {
	var $_defaultValue;

	function CSSMaxHeight() {
		$this->CSSPropertyHandler( true, false );
		$this->_defaultValue = ValueMaxHeight::fromString( "auto" );
	}

	/**
	 * 'height' CSS property should be inherited by table cells from table rows
	 * (as, obviously, )
	 */
	function inherit( $old_state, &$new_state ) {
		$parent_display = $old_state[ CSS_DISPLAY ];
		if ( $parent_display === "table-row" ) {
			$new_state[ CSS_MAX_HEIGHT ] = $old_state[ CSS_MAX_HEIGHT ];

			return;
		}

		$new_state[ CSS_MAX_HEIGHT ] =
			is_inline_element( $parent_display ) ?
				$this->get( $old_state ) :
				$this->default_value();
	}

	function _getAutoValue() {
		return $this->default_value();
	}

	function default_value() {
		return $this->_defaultValue->copy();
	}

	function parse( $value ) {
		if ( $value == 'none' ) {
			return ValueMaxHeight::fromString( 'auto' );
		};

		return ValueMaxHeight::fromString( $value );
	}

	function get_property_code() {
		return CSS_MAX_HEIGHT;
	}

	function get_property_name() {
		return 'max-height';
	}
}

CSS::register_css_property( new CSSMaxHeight );


// $Header: /cvsroot/html2ps/css.left.inc.php,v 1.9 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.left.php' );

class CSSLeft extends CSSPropertyHandler {
	function CSSLeft() {
		$this->CSSPropertyHandler( false, false );
		$this->_autoValue = ValueLeft::fromString( 'auto' );
	}

	function _getAutoValue() {
		return $this->_autoValue->copy();
	}

	function default_value() {
		return $this->_getAutoValue();
	}

	function parse( $value ) {
		return ValueLeft::fromString( $value );
	}

	function get_property_code() {
		return CSS_LEFT;
	}

	function get_property_name() {
		return 'left';
	}
}

CSS::register_css_property( new CSSLeft );


// $Header: /cvsroot/html2ps/css.letter-spacing.inc.php,v 1.3 2006/09/07 18:38:14 Konstantin Exp $

class CSSLetterSpacing extends CSSPropertyHandler {
	var $_default_value;

	function CSSLetterSpacing() {
		$this->CSSPropertyHandler( false, true );

		$this->_default_value = Value::fromString( "0" );
	}

	function default_value() {
		return $this->_default_value;
	}

	function parse( $value ) {
		$value = trim( $value );

		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		if ( $value === 'normal' ) {
			return $this->_default_value;
		};

		return Value::fromString( $value );
	}

	function get_property_code() {
		return CSS_LETTER_SPACING;
	}

	function get_property_name() {
		return 'letter-spacing';
	}
}

CSS::register_css_property( new CSSLetterSpacing );


// $Header: /cvsroot/html2ps/css.list-style-image.inc.php,v 1.6 2006/09/07 18:38:14 Konstantin Exp $

class CSSListStyleImage extends CSSSubFieldProperty {
	/**
	 * CSS 2.1: default value for list-style-image is none
	 */
	function default_value() {
		return new ListStyleImage( null, null );
	}

	function parse( $value, &$pipeline ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		global $g_config;
		if ( ! $g_config['renderimages'] ) {
			return CSSListStyleImage::default_value();
		};

		if ( preg_match( '/url\(([^)]+)\)/', $value, $matches ) ) {
			$url = $matches[1];

			$full_url = $pipeline->guess_url( css_remove_value_quotes( $url ) );

			return new ListStyleImage( $full_url,
				ImageFactory::get( $full_url, $pipeline ) );
		};

		/**
		 * 'none' value and all unrecognized values
		 */
		return CSSListStyleImage::default_value();
	}

	function get_property_code() {
		return CSS_LIST_STYLE_IMAGE;
	}

	function get_property_name() {
		return 'list-style-image';
	}
}


// $Header: /cvsroot/html2ps/css.list-style-position.inc.php,v 1.6 2006/07/09 09:07:45 Konstantin Exp $

define( 'LSP_OUTSIDE', 0 );
define( 'LSP_INSIDE', 1 );

class CSSListStylePosition extends CSSSubFieldProperty {
	// CSS 2.1: default value for list-style-position is 'outside'
	function default_value() {
		return LSP_OUTSIDE;
	}

	function parse( $value ) {
		if ( preg_match( '/\binside\b/', $value ) ) {
			return LSP_INSIDE;
		};

		if ( preg_match( '/\boutside\b/', $value ) ) {
			return LSP_OUTSIDE;
		};

		return null;
	}

	function get_property_code() {
		return CSS_LIST_STYLE_POSITION;
	}

	function get_property_name() {
		return 'list-style-position';
	}
}


// $Header: /cvsroot/html2ps/css.list-style-type.inc.php,v 1.13 2006/09/07 18:38:14 Konstantin Exp $

// FIXME: supported only partially
define( 'LST_NONE', 0 );
define( 'LST_DISC', 1 );
define( 'LST_CIRCLE', 2 );
define( 'LST_SQUARE', 3 );
define( 'LST_DECIMAL', 4 );
define( 'LST_DECIMAL_LEADING_ZERO', 5 );
define( 'LST_LOWER_ROMAN', 6 );
define( 'LST_UPPER_ROMAN', 7 );
define( 'LST_LOWER_LATIN', 8 );
define( 'LST_UPPER_LATIN', 9 );

class CSSListStyleType extends CSSSubFieldProperty {
	// CSS 2.1: default value for list-style-type is 'disc'
	function default_value() {
		return LST_DISC;
	}

	function parse( $value ) {
		if ( preg_match( '/\bnone\b/', $value ) ) {
			return LST_NONE;
		};
		if ( preg_match( '/\bdisc\b/', $value ) ) {
			return LST_DISC;
		};
		if ( preg_match( '/\bcircle\b/', $value ) ) {
			return LST_CIRCLE;
		};
		if ( preg_match( '/\bsquare\b/', $value ) ) {
			return LST_SQUARE;
		};
		if ( preg_match( '/\bdecimal-leading-zero\b/', $value ) ) {
			return LST_DECIMAL_LEADING_ZERO;
		}
		if ( preg_match( '/\bdecimal\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\blower-roman\b/', $value ) ) {
			return LST_LOWER_ROMAN;
		}
		if ( preg_match( '/\bupper-roman\b/', $value ) ) {
			return LST_UPPER_ROMAN;
		}
		if ( preg_match( '/\blower-latin\b/', $value ) ) {
			return LST_LOWER_LATIN;
		}
		if ( preg_match( '/\bupper-latin\b/', $value ) ) {
			return LST_UPPER_LATIN;
		}
		if ( preg_match( '/\blower-alpha\b/', $value ) ) {
			return LST_LOWER_LATIN;
		}
		if ( preg_match( '/\bupper-alpha\b/', $value ) ) {
			return LST_UPPER_LATIN;
		}

		// Unsupported CSS values:
		// According to CSS 2.1 specs 12.6.2, a user agent that does not recognize a numbering system should use 'decimal'.
		if ( preg_match( '/\bhebrew\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bgeorgian\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\barmenian\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bcjk-ideographic\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bhiragana\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bkarakana\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bhiragana-iroha\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\bkatakana-iroha\b/', $value ) ) {
			return LST_DECIMAL;
		};
		if ( preg_match( '/\blower-greek\b/', $value ) ) {
			return LST_DECIMAL;
		};

		return null;
	}

	function format_number( $type, $num ) {
		// NOTE: according to CSS 2.1 specs 12.6.2, "This specification does not define how alphabetic systems wrap
		// at the end of the alphabet. For instance, after 26 list items, 'lower-latin' rendering is undefined.
		// Therefore, for long lists, we recommend that authors specify true numbers.".
		// In our case we chose just to wrap over the beginning of alphabet (so, 'a' will again appear after 'z')
		//
		// Also, we're hoping that encoding we're using contains character codes in alphabetic order
		// It is true for ASCII, but there's some other crazy encodings... :-)
		//
		// Also, I really do not understand _WHY_ PHP is spewing a lot of notice messages complaining about
		// undefined constants if I'm using the equivalent 'switch' construct instead of 'if'
		switch ( $type ) {
			case LST_DECIMAL:
				return $num;
			case LST_DECIMAL_LEADING_ZERO:
				return sprintf( "%02d", $num );
			case LST_LOWER_LATIN:
				return chr( ord( 'a' ) + ( $num - 1 ) % 26 );
			case LST_UPPER_LATIN:
				return chr( ord( 'A' ) + ( $num - 1 ) % 26 );
			case LST_LOWER_ROMAN:
				return strtolower( arabic_to_roman( $num ) );
			case LST_UPPER_ROMAN:
				return arabic_to_roman( $num );
			default:
				return "";
		}
	}

	function get_property_code() {
		return CSS_LIST_STYLE_TYPE;
	}

	function get_property_name() {
		return 'list-style-type';
	}
}


// $Header: /cvsroot/html2ps/css.list-style.inc.php,v 1.8 2007/02/04 17:08:19 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.list-style.class.php' );

class CSSListStyle extends CSSPropertyHandler {
	// CSS 2.1: list-style is inherited
	function CSSListStyle() {
		$this->default_value           = new ListStyleValue;
		$this->default_value->image    = CSSListStyleImage::default_value();
		$this->default_value->position = CSSListStylePosition::default_value();
		$this->default_value->type     = CSSListStyleType::default_value();

		$this->CSSPropertyHandler( true, true );
	}

	function parse( $value, &$pipeline ) {
		$style           = new ListStyleValue;
		$style->image    = CSSListStyleImage::parse( $value, $pipeline );
		$style->position = CSSListStylePosition::parse( $value );
		$style->type     = CSSListStyleType::parse( $value );

		return $style;
	}

	function default_value() {
		return $this->default_value;
	}

	function get_property_code() {
		return CSS_LIST_STYLE;
	}

	function get_property_name() {
		return 'list-style';
	}
}

$ls = new CSSListStyle;
CSS::register_css_property( $ls );
CSS::register_css_property( new CSSListStyleImage( $ls, 'image' ) );
CSS::register_css_property( new CSSListStylePosition( $ls, 'position' ) );
CSS::register_css_property( new CSSListStyleType( $ls, 'type' ) );


require_once( HTML2PS_DIR . 'value.margin.class.php' );

class CSSMargin extends CSSPropertyHandler {
	var $default_value;

	function CSSMargin() {
		$this->default_value = $this->parse( "0" );
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return $this->default_value->copy();
	}

	function parse_in( $value ) {
		$values = preg_split( '/\s+/', trim( $value ) );

		switch ( count( $values ) ) {
			case 1:
				$v1 = $values[0];

				return array( $v1, $v1, $v1, $v1 );
			case 2:
				$v1 = $values[0];
				$v2 = $values[1];

				return array( $v1, $v2, $v1, $v2 );
			case 3:
				$v1 = $values[0];
				$v2 = $values[1];
				$v3 = $values[2];

				return array( $v1, $v2, $v3, $v2 );
			case 4:
				$v1 = $values[0];
				$v2 = $values[1];
				$v3 = $values[2];
				$v4 = $values[3];

				return array( $v1, $v2, $v3, $v4 );
			default:
				// We newer should get there, because 'margin' value can contain from 1 to 4 widths
				return array( 0, 0, 0, 0 );
		};
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$value = MarginValue::init( $this->parse_in( $value ) );

		return $value;
	}

	function get_property_code() {
		return CSS_MARGIN;
	}

	function get_property_name() {
		return 'margin';
	}
}

class CSSMarginTop extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return MarginSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_MARGIN_TOP;
	}

	function get_property_name() {
		return 'margin-top';
	}
}

class CSSMarginRight extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return MarginSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_MARGIN_RIGHT;
	}

	function get_property_name() {
		return 'margin-right';
	}
}

class CSSMarginLeft extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return MarginSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_MARGIN_LEFT;
	}

	function get_property_name() {
		return 'margin-left';
	}
}

class CSSMarginBottom extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return MarginSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_MARGIN_BOTTOM;
	}

	function get_property_name() {
		return 'margin-bottom';
	}
}

$mh = new CSSMargin;
CSS::register_css_property( $mh );
CSS::register_css_property( new CSSMarginLeft( $mh, 'left' ) );
CSS::register_css_property( new CSSMarginRight( $mh, 'right' ) );
CSS::register_css_property( new CSSMarginTop( $mh, 'top' ) );
CSS::register_css_property( new CSSMarginBottom( $mh, 'bottom' ) );


// $Header: /cvsroot/html2ps/css.overflow.inc.php,v 1.8 2006/09/07 18:38:14 Konstantin Exp $

define( 'OVERFLOW_VISIBLE', 0 );
define( 'OVERFLOW_HIDDEN', 1 );

class CSSOverflow extends CSSPropertyStringSet {
	function CSSOverflow() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'inherit' => CSS_PROPERTY_INHERIT,
				'hidden'  => OVERFLOW_HIDDEN,
				'scroll'  => OVERFLOW_HIDDEN,
				'auto'    => OVERFLOW_HIDDEN,
				'visible' => OVERFLOW_VISIBLE
			) );
	}

	function default_value() {
		return OVERFLOW_VISIBLE;
	}

	function get_property_code() {
		return CSS_OVERFLOW;
	}

	function get_property_name() {
		return 'overflow';
	}
}

CSS::register_css_property( new CSSOverflow );


require_once( HTML2PS_DIR . 'value.padding.class.php' );

class CSSPadding extends CSSPropertyHandler {
	var $default_value;

	function CSSPadding() {
		$this->default_value = $this->parse( "0" );
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return $this->default_value->copy();
	}

	function parse_in( $value ) {
		$values = preg_split( '/\s+/', trim( $value ) );
		switch ( count( $values ) ) {
			case 1:
				$v1 = $values[0];

				return array( $v1, $v1, $v1, $v1 );
			case 2:
				$v1 = $values[0];
				$v2 = $values[1];

				return array( $v1, $v2, $v1, $v2 );
			case 3:
				$v1 = $values[0];
				$v2 = $values[1];
				$v3 = $values[2];

				return array( $v1, $v2, $v3, $v2 );
			case 4:
				$v1 = $values[0];
				$v2 = $values[1];
				$v3 = $values[2];
				$v4 = $values[3];

				return array( $v1, $v2, $v3, $v4 );
			default:
				// We newer should get there, because 'padding' value can contain from 1 to 4 widths
				return array( 0, 0, 0, 0 );
		};
	}

	function parse( $string ) {
		if ( $string === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$values  = $this->parse_in( $string );
		$padding = PaddingValue::init( $values );

		return $padding;
	}

	function get_property_code() {
		return CSS_PADDING;
	}

	function get_property_name() {
		return 'padding';
	}
}

class CSSPaddingTop extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return PaddingSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_PADDING_TOP;
	}

	function get_property_name() {
		return 'padding-top';
	}
}

class CSSPaddingRight extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};
		$result = PaddingSideValue::init( $value );

		return $result;
	}

	function get_property_code() {
		return CSS_PADDING_RIGHT;
	}

	function get_property_name() {
		return 'padding-right';
	}
}

class CSSPaddingLeft extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return PaddingSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_PADDING_LEFT;
	}

	function get_property_name() {
		return 'padding-left';
	}
}

class CSSPaddingBottom extends CSSSubFieldProperty {
	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return PaddingSideValue::init( $value );
	}

	function get_property_code() {
		return CSS_PADDING_BOTTOM;
	}

	function get_property_name() {
		return 'padding-bottom';
	}
}

$ph = new CSSPadding;
CSS::register_css_property( $ph );
CSS::register_css_property( new CSSPaddingLeft( $ph, 'left' ) );
CSS::register_css_property( new CSSPaddingRight( $ph, 'right' ) );
CSS::register_css_property( new CSSPaddingTop( $ph, 'top' ) );
CSS::register_css_property( new CSSPaddingBottom( $ph, 'bottom' ) );


// $Header: /cvsroot/html2ps/css.color.inc.php,v 1.13 2007/01/24 18:55:51 Konstantin Exp $

class CSSPage extends CSSPropertyHandler {
	function CSSPage() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return 'auto';
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_PAGE;
	}

	function get_property_name() {
		return 'page';
	}
}

CSS::register_css_property( new CSSPage() );


define( 'PAGE_BREAK_AUTO', 0 );
define( 'PAGE_BREAK_ALWAYS', 1 );
define( 'PAGE_BREAK_AVOID', 2 );
define( 'PAGE_BREAK_LEFT', 3 );
define( 'PAGE_BREAK_RIGHT', 4 );

class CSSPageBreak extends CSSPropertyStringSet {
	function CSSPageBreak() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'inherit' => CSS_PROPERTY_INHERIT,
				'auto'    => PAGE_BREAK_AUTO,
				'always'  => PAGE_BREAK_ALWAYS,
				'avoid'   => PAGE_BREAK_AVOID,
				'left'    => PAGE_BREAK_LEFT,
				'right'   => PAGE_BREAK_RIGHT
			) );
	}

	function default_value() {
		return PAGE_BREAK_AUTO;
	}
}

// $Header: /cvsroot/html2ps/css.page-break-after.inc.php,v 1.3 2007/01/09 20:13:48 Konstantin Exp $

class CSSPageBreakAfter extends CSSPageBreak {
	function get_property_code() {
		return CSS_PAGE_BREAK_AFTER;
	}

	function get_property_name() {
		return 'page-break-after';
	}
}

CSS::register_css_property( new CSSPageBreakAfter );


// $Header: /cvsroot/html2ps/css.page-break-before.inc.php,v 1.1.2.1 2006/11/16 03:19:36 Konstantin Exp $

class CSSPageBreakBefore extends CSSPageBreak {
	function get_property_code() {
		return CSS_PAGE_BREAK_BEFORE;
	}

	function get_property_name() {
		return 'page-break-before';
	}
}

CSS::register_css_property( new CSSPageBreakBefore );


// $Header: /cvsroot/html2ps/css.page-break-inside.inc.php,v 1.1.2.1 2006/11/16 03:19:36 Konstantin Exp $

class CSSPageBreakInside extends CSSPageBreak {
	function get_property_code() {
		return CSS_PAGE_BREAK_INSIDE;
	}

	function get_property_name() {
		return 'page-break-inside';
	}
}

CSS::register_css_property( new CSSPageBreakInside );


class CSSOrphans extends CSSPropertyHandler {
	function CSSOrphans() {
		$this->CSSPropertyHandler( true, false );
	}

	function default_value() {
		return 2;
	}

	function parse( $value ) {
		return (int) $value;
	}

	function get_property_code() {
		return CSS_ORPHANS;
	}

	function get_property_name() {
		return 'orphans';
	}
}

CSS::register_css_property( new CSSOrphans );


class CSSSize extends CSSPropertyHandler {
	function CSSSize() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		$null = null;

		return $null;
	}

	// <length>{1,2} | auto | [ <page-size> || [ portrait | landscape] ]
	function parse( $value ) {
		if ( $value == '' ) {
			return null;
		};

		// First attempt to create media with predefined name
		if ( preg_match( '/^(\w+)(?:\s+(portrait|landscape))?$/', $value, $matches ) ) {
			$name      = $matches[1];
			$landscape = isset( $matches[2] ) && $matches[2] == 'landscape';

			$media =& Media::predefined( $name );

			if ( is_null( $media ) ) {
				return null;
			};

			return array(
				'size'      => array(
					'width'  => $media->get_width(),
					'height' => $media->get_height()
				),
				'landscape' => $landscape
			);
		};

		// Second, attempt to create media with predefined size
		$parts      = preg_split( '/\s+/', $value );
		$width_str  = $parts[0];
		$height_str = isset( $parts[1] ) ? $parts[1] : $parts[0];

		$width  = units2pt( $width_str );
		$height = units2pt( $height_str );

		if ( $width == 0 ||
		     $height == 0 ) {
			return null;
		};

		return array(
			'size'      => array(
				'width'  => $width / mm2pt( 1 ) / pt2pt( 1 ),
				'height' => $height / mm2pt( 1 ) / pt2pt( 1 )
			),
			'landscape' => false
		);
	}

	function get_property_code() {
		return CSS_SIZE;
	}

	function get_property_name() {
		return 'size';
	}
}

CSS::register_css_property( new CSSSize() );


class CSSWidows extends CSSPropertyHandler {
	function CSSWidows() {
		$this->CSSPropertyHandler( true, false );
	}

	function default_value() {
		return 2;
	}

	function parse( $value ) {
		return (int) $value;
	}

	function get_property_code() {
		return CSS_WIDOWS;
	}

	function get_property_name() {
		return 'widows';
	}
}

CSS::register_css_property( new CSSWidows );


// $Header: /cvsroot/html2ps/css.position.inc.php,v 1.12 2006/09/07 18:38:14 Konstantin Exp $

define( 'POSITION_STATIC', 0 );
define( 'POSITION_RELATIVE', 1 );
define( 'POSITION_ABSOLUTE', 2 );
define( 'POSITION_FIXED', 3 );

// CSS 3

define( 'POSITION_FOOTNOTE', 4 );

class CSSPosition extends CSSPropertyStringSet {
	function CSSPosition() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'inherit'  => CSS_PROPERTY_INHERIT,
				'absolute' => POSITION_ABSOLUTE,
				'relative' => POSITION_RELATIVE,
				'fixed'    => POSITION_FIXED,
				'static'   => POSITION_STATIC,
				'footnote' => POSITION_FOOTNOTE
			) );
	}

	function default_value() {
		return POSITION_STATIC;
	}

	function get_property_code() {
		return CSS_POSITION;
	}

	function get_property_name() {
		return 'position';
	}
}

CSS::register_css_property( new CSSPosition );


// $Header: /cvsroot/html2ps/css.right.inc.php,v 1.6 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.right.php' );

class CSSRight extends CSSPropertyHandler {
	function CSSRight() {
		$this->CSSPropertyHandler( false, false );
		$this->_autoValue = ValueRight::fromString( 'auto' );
	}

	function _getAutoValue() {
		return $this->_autoValue->copy();
	}

	function default_value() {
		return $this->_getAutoValue();
	}

	function parse( $value ) {
		return ValueRight::fromString( $value );
	}

	function get_property_code() {
		return CSS_RIGHT;
	}

	function get_property_name() {
		return 'right';
	}
}

CSS::register_css_property( new CSSRight );


class CSSPropertyDeclaration {
	var $_code;
	var $_value;
	var $_important;

	function CSSPropertyDeclaration() {
		$this->_code      = 0;
		$this->_value     = null;
		$this->_important = false;
	}

	function &get_value() {
		return $this->_value;
	}

	function set_code( $code ) {
		$this->_code = $code;
	}

	function set_important( $value ) {
		$this->_important = $value;
	}

	function set_value( &$value ) {
		$this->_value =& $value;
	}

	function &create( $code, $value, $pipeline ) {
		$handler =& CSS::get_handler( $code );
		if ( is_null( $handler ) ) {
			$null = null;

			return $null;
		};

		$declaration        =& new CSSPropertyDeclaration();
		$declaration->_code = $code;

		if ( preg_match( "/^(.*)!\s*important\s*$/", $value, $matches ) ) {
			$value                   = $matches[1];
			$declaration->_important = true;
		} else {
			$declaration->_important = false;
		};

		$declaration->_value = $handler->parse( $value, $pipeline );

		return $declaration;
	}

	function get_code() {
		return $this->_code;
	}

	function &copy() {
		$declaration        =& new CSSPropertyDeclaration();
		$declaration->_code = $this->_code;

		if ( is_object( $this->_value ) ) {
			$declaration->_value =& $this->_value->copy();
		} else {
			$declaration->_value =& $this->_value;
		};

		$declaration->_important = $this->_important;

		return $declaration;
	}

	function is_important() {
		return $this->_important;
	}
}


// $Header: /cvsroot/html2ps/css.rules.inc.php,v 1.10 2007/03/23 18:33:34 Konstantin Exp $

class CSSRule {
	var $selector;
	var $body;
	var $baseurl;
	var $order;

	var $specificity;
	var $pseudoelement;

	function apply( &$root, &$state, &$pipeline ) {
		$pipeline->push_base_url( $this->baseurl );
		$this->body->apply( $state );
		$pipeline->pop_base_url();
	}

	function add_property( $property ) {
		$this->body->add_property( $property );
	}

	function CSSRule( $rule, &$pipeline ) {
		$this->selector = $rule[0];
		$this->body     = $rule[1]->copy();
		$this->baseurl  = $rule[2];
		$this->order    = $rule[3];

		$this->specificity   = css_selector_specificity( $this->selector );
		$this->pseudoelement = css_find_pseudoelement( $this->selector );
	}

	function set_property( $key, $value, &$pipeline ) {
		$this->body->set_property_value( $key, $value );
	}

	function &get_property( $key ) {
		return $this->body->get_property_value( $key );
	}

	function get_order() {
		return $this->order;
	}

	function get_pseudoelement() {
		return $this->pseudoelement;
	}

	function get_selector() {
		return $this->selector;
	}

	function get_specificity() {
		return $this->specificity;
	}

	function match( $root ) {
		return match_selector( $this->selector, $root );
	}
}

function rule_get_selector( &$rule ) {
	return $rule[0];
}

;

function cmp_rules( $r1, $r2 ) {
	$a = css_selector_specificity( $r1[0] );
	$b = css_selector_specificity( $r2[0] );

	for ( $i = 0; $i <= 2; $i ++ ) {
		if ( $a[ $i ] != $b[ $i ] ) {
			return ( $a[ $i ] < $b[ $i ] ) ? - 1 : 1;
		};
	};

	// If specificity of selectors is equal, use rules natural order in stylesheet

	return $r1[3] < $r2[3] ? - 1 : 1;
}

function cmp_rule_objs( $r1, $r2 ) {
	$a = $r1->get_specificity();
	$b = $r2->get_specificity();

	for ( $i = 0; $i <= 2; $i ++ ) {
		if ( $a[ $i ] != $b[ $i ] ) {
			return ( $a[ $i ] < $b[ $i ] ) ? - 1 : 1;
		};
	};

	// If specificity of selectors is equal, use rules natural order in stylesheet

	return $r1->get_order() < $r2->get_order() ? - 1 : 1;
}


class CSSRuleset {
	var $rules;
	var $tag_filtered;
	var $_lastId;

	function CSSRuleset() {
		$this->rules        = array();
		$this->tag_filtered = array();
		$this->_lastId      = 0;
	}

	function parse_style_node( $root, &$pipeline ) {
		// Check if this style node have 'media' attribute
		// and if we're using this media;
		//
		// Note that, according to the HTML 4.01 p.14.2.3
		// This attribute specifies the intended destination medium for style information.
		// It may be a single media descriptor or a comma-separated list.
		// The default value for this attribute is "screen".
		//
		$media_list = array( "screen" );
		if ( $root->has_attribute( "media" ) ) {
			// Note that there may be whitespace symbols around commas, so we should not just use 'explode' function
			$media_list = preg_split( "/\s*,\s*/", trim( $root->get_attribute( "media" ) ) );
		};

		if ( ! is_allowed_media( $media_list ) ) {
			if ( defined( 'DEBUG_MODE' ) ) {
				error_log( sprintf( 'No allowed (%s) media types found in CSS stylesheet media types (%s). Stylesheet ignored.',
					join( ',', config_get_allowed_media() ),
					join( ',', $media_list ) ) );
			};

			return;
		};

		if ( ! isset( $GLOBALS['g_stylesheet_title'] ) ||
		     $GLOBALS['g_stylesheet_title'] === "" ) {
			$GLOBALS['g_stylesheet_title'] = $root->get_attribute( "title" );
		};

		if ( ! $root->has_attribute( "title" ) || $root->get_attribute( "title" ) === $GLOBALS['g_stylesheet_title'] ) {
			/**
			 * Check if current node is empty (then, we don't need to parse its contents)
			 */
			$content = trim( $root->get_content() );
			if ( $content != "" ) {
				$this->parse_css( $content, $pipeline );
			};
		};
	}

	function scan_styles( $root, &$pipeline ) {
		switch ( $root->node_type() ) {
			case XML_ELEMENT_NODE:
				$tagname = strtolower( $root->tagname() );

				if ( $tagname === 'style' ) {
					// Parse <style ...> ... </style> nodes
					//
					$this->parse_style_node( $root, $pipeline );

				} elseif ( $tagname === 'link' ) {
					// Parse <link rel="stylesheet" ...> nodes
					//
					$rel = strtolower( $root->get_attribute( "rel" ) );

					$type = strtolower( $root->get_attribute( "type" ) );
					if ( $root->has_attribute( "media" ) ) {
						$media = explode( ",", $root->get_attribute( "media" ) );
					} else {
						$media = array();
					};

					if ( $rel == "stylesheet" &&
					     ( $type == "text/css" || $type == "" ) &&
					     ( count( $media ) == 0 || is_allowed_media( $media ) ) ) {
						// Attempt to escape URL automaticaly
						$url_autofix = new AutofixUrl();
						$src         = $url_autofix->apply( trim( $root->get_attribute( 'href' ) ) );

						if ( $src ) {
							$this->css_import( $src, $pipeline );
						};
					};
				};

			// Note that we continue processing here!
			case XML_DOCUMENT_NODE:

				// Scan all child nodes
				$child = $root->first_child();
				while ( $child ) {
					$this->scan_styles( $child, $pipeline );
					$child = $child->next_sibling();
				};
				break;
		};
	}

	function parse_css( $css, &$pipeline, $baseindex = 0 ) {
		$allowed_media = implode( "|", config_get_allowed_media() );

		// remove the UTF8 byte-order mark from the beginning of the file (several high-order symbols at the beginning)
		$pos = 0;
		$len = strlen( $css );
		while ( ord( $css{$pos} ) > 127 && $pos < $len ) {
			$pos ++;
		};
		$css = substr( $css, $pos );

		// Process @media rules;
		// basic syntax is:
		// @media <media>(,<media>)* { <rules> }
		//

		while ( preg_match( "/^(.*?)@media([^{]+){(.*)$/s", $css, $matches ) ) {
			$head  = $matches[1];
			$media = $matches[2];
			$rest  = $matches[3];

			// Process CSS rules placed before the first @media declaration - they should be applied to
			// all media types
			//
			$this->parse_css_media( $head, $pipeline, $baseindex );

			// Extract the media content
			if ( ! preg_match( "/^((?:[^{}]*{[^{}]*})*)[^{}]*\s*}(.*)$/s", $rest, $matches ) ) {
				die( "CSS media syntax error\n" );
			} else {
				$content = $matches[1];
				$tail    = $matches[2];
			};

			// Check if this media is to be processed
			if ( preg_match( "/" . $allowed_media . "/i", $media ) ) {
				$this->parse_css_media( $content, $pipeline, $baseindex );
			};

			// Process the rest of CSS file
			$css = $tail;
		};

		// The rest of CSS file belogs to common media, process it too
		$this->parse_css_media( $css, $pipeline, $baseindex );
	}

	function css_import( $src, &$pipeline ) {
		// Update the base url;
		// all urls will be resolved relatively to the current stylesheet url
		$url  = $pipeline->guess_url( $src );
		$data = $pipeline->fetch( $url );

		/**
		 * If referred file could not be fetched return immediately
		 */
		if ( is_null( $data ) ) {
			return;
		};

		$css = $data->get_content();
		if ( ! empty( $css ) ) {
			/**
			 * Sometimes, external stylesheets contain <!-- and --> at the beginning and
			 * at the end; we should remove these characters, as they may break parsing of
			 * first and last rules
			 */
			$css = preg_replace( '/^\s*<!--/', '', $css );
			$css = preg_replace( '/-->\s*$/', '', $css );

			$this->parse_css( $css, $pipeline );
		};

		$pipeline->pop_base_url();
	}

	function parse_css_import( $import, &$pipeline ) {
		if ( preg_match( "/@import\s+[\"'](.*)[\"'];/", $import, $matches ) ) {
			// @import "<url>"
			$this->css_import( trim( $matches[1] ), $pipeline );
		} elseif ( preg_match( "/@import\s+url\((.*)\);/", $import, $matches ) ) {
			// @import url()
			$this->css_import( trim( css_remove_value_quotes( $matches[1] ) ), $pipeline );
		} elseif ( preg_match( "/@import\s+(.*);/", $import, $matches ) ) {
			// @import <url>
			$this->css_import( trim( css_remove_value_quotes( $matches[1] ) ), $pipeline );
		};
	}

	function parse_css_media( $css, &$pipeline, $baseindex = 0 ) {
		// Remove comments
		$css = preg_replace( "#/\*.*?\*/#is", "", $css );

		// Extract @page rules
		$css = parse_css_atpage_rules( $css, $pipeline );

		// Extract @import rules
		if ( $num = preg_match_all( "/@import[^;]+;/", $css, $matches, PREG_PATTERN_ORDER ) ) {
			for ( $i = 0; $i < $num; $i ++ ) {
				$this->parse_css_import( $matches[0][ $i ], $pipeline );
			}
		};

		// Remove @import rules so they will not break further processing
		$css = preg_replace( "/@import[^;]+;/", "", $css );

		while ( preg_match( "/([^{}]*){(.*?)}(.*)/is", $css, $matches ) ) {
			// Drop extracted part
			$css = $matches[3];

			// Save extracted part
			$raw_selectors  = $matches[1];
			$raw_properties = $matches[2];

			$selectors = parse_css_selectors( $raw_selectors );

			$properties = parse_css_properties( $raw_properties, $pipeline );

			foreach ( $selectors as $selector ) {
				$this->_lastId ++;
				$rule = array(
					$selector,
					$properties,
					$pipeline->get_base_url(),
					$this->_lastId + $baseindex
				);
				$this->add_rule( $rule,
					$pipeline );
			};
		};
	}

	function add_rule( &$rule, &$pipeline ) {
		$rule_obj      = new CSSRule( $rule, $pipeline );
		$this->rules[] = $rule_obj;

		$tag = $this->detect_applicable_tag( $rule_obj->get_selector() );
		if ( is_null( $tag ) ) {
			$tag = "*";
		}
		$this->tag_filtered[ $tag ][] = $rule_obj;
	}

	function apply( &$root, &$state, &$pipeline ) {
		$local_css = array();

		if ( isset( $this->tag_filtered[ strtolower( $root->tagname() ) ] ) ) {
			$local_css = $this->tag_filtered[ strtolower( $root->tagname() ) ];
		};

		if ( isset( $this->tag_filtered["*"] ) ) {
			$local_css = array_merge( $local_css, $this->tag_filtered["*"] );
		};

		$applicable = array();

		foreach ( $local_css as $rule ) {
			if ( $rule->match( $root ) ) {
				$applicable[] = $rule;
			};
		};

		usort( $applicable, "cmp_rule_objs" );

		foreach ( $applicable as $rule ) {
			switch ( $rule->get_pseudoelement() ) {
				case SELECTOR_PSEUDOELEMENT_BEFORE:
					$handler =& CSS::get_handler( CSS_HTML2PS_PSEUDOELEMENTS );
					$handler->replace( $handler->get( $state->getState() ) | CSS_HTML2PS_PSEUDOELEMENTS_BEFORE, $state );
					break;
				case SELECTOR_PSEUDOELEMENT_AFTER:
					$handler =& CSS::get_handler( CSS_HTML2PS_PSEUDOELEMENTS );
					$handler->replace( $handler->get( $state->getState() ) | CSS_HTML2PS_PSEUDOELEMENTS_AFTER, $state );
					break;
				default:
					$rule->apply( $root, $state, $pipeline );
					break;
			};
		};
	}

	function apply_pseudoelement( $element_type, &$root, &$state, &$pipeline ) {
		$local_css = array();

		if ( isset( $this->tag_filtered[ strtolower( $root->tagname() ) ] ) ) {
			$local_css = $this->tag_filtered[ strtolower( $root->tagname() ) ];
		};

		if ( isset( $this->tag_filtered["*"] ) ) {
			$local_css = array_merge( $local_css, $this->tag_filtered["*"] );
		};

		$applicable = array();

		for ( $i = 0; $i < count( $local_css ); $i ++ ) {
			$rule =& $local_css[ $i ];
			if ( $rule->get_pseudoelement() == $element_type ) {
				if ( $rule->match( $root ) ) {
					$applicable[] =& $rule;
				};
			};
		};

		usort( $applicable, "cmp_rule_objs" );

		// Note that filtered rules already have pseudoelement mathing (see condition above)

		foreach ( $applicable as $rule ) {
			$rule->apply( $root, $state, $pipeline );
		};
	}

	// Check if only tag with a specific name can match this selector
	//
	function detect_applicable_tag( $selector ) {
		switch ( selector_get_type( $selector ) ) {
			case SELECTOR_TAG:
				return $selector[1];
			case SELECTOR_TAG_CLASS:
				return $selector[1];
			case SELECTOR_SEQUENCE:
				foreach ( $selector[1] as $subselector ) {
					$tag = $this->detect_applicable_tag( $subselector );
					if ( $tag ) {
						return $tag;
					};
				};

				return null;
			default:
				return null;
		}
	}
}


// $Header: /cvsroot/html2ps/css.selectors.inc.php,v 1.12 2006/01/07 19:38:06 Konstantin Exp $

define( 'SELECTOR_ID', 1 );
define( 'SELECTOR_CLASS', 2 );
define( 'SELECTOR_TAG', 3 );
define( 'SELECTOR_TAG_CLASS', 4 );
define( 'SELECTOR_SEQUENCE', 5 );
define( 'SELECTOR_PARENT', 6 );         // TAG1 TAG2
define( 'SELECTOR_ATTR_VALUE', 7 );
define( 'SELECTOR_PSEUDOCLASS_LINK', 8 );
define( 'SELECTOR_ATTR', 9 );
define( 'SELECTOR_DIRECT_PARENT', 10 ); // TAG1 > TAG2
define( 'SELECTOR_LANGUAGE', 11 );      // SELECTOR:lang(..)

// Used for handling the body 'link' atttribute; this selector have no specificity at all
// we need to introduce this selector type as some ill-brained designers use constructs like:
//
// <html>
// <head><style type="text/css">a { color: red; }</style></head>
// <body link="#000000"><a href="test">test</a>
//
// in this case the CSS rule should have the higher priority; nevertheless, using the default selector rules
// we'd get find that 'link'-generated CSS rule is more important
//
define( 'SELECTOR_PSEUDOCLASS_LINK_LOW_PRIORITY', 12 );

// Used for hanling the following case:
//
// <head>
// <style>img { border: 0; }</style>
// </head>
// <body><a href=""><img height="10" width="10" src=""></a>
//
define( 'SELECTOR_PARENT_LOW_PRIORITY', 13 );

define( 'SELECTOR_PSEUDOELEMENT_BEFORE', 14 );
define( 'SELECTOR_PSEUDOELEMENT_AFTER', 15 );

// Note on SELECTOR_ANY:
// normally we should not process rules like
// * html <some other selector> as they're IE specific and (according to CSS standard)
// should be never matched
define( 'SELECTOR_ANY', 16 );

define( 'SELECTOR_ATTR_VALUE_WORD', 17 );

// CSS 2.1:
// In CSS2, identifiers  (including element names, classes, and IDs in selectors) can contain only the characters [A-Za-z0-9] and
// ISO 10646 characters 161 and higher, plus the hyphen (-); they cannot start with a hyphen or a digit.
// They can also contain escaped characters and any ISO 10646 character as a numeric code (see next item). For instance,
// the identifier "B&W?" may be written as "B\&W\?" or "B\26 W\3F".
//
// Any node can be marked by several space separated class names
//
function node_have_class( $root, $target_class ) {
	if ( ! $root->has_attribute( 'class' ) ) {
		return false;
	};

	$classes = preg_split( "/\s+/", strtolower( $root->get_attribute( 'class' ) ) );

	foreach ( $classes as $class ) {
		if ( $class == $target_class ) {
			return true;
		};
	};

	return false;
}

;

function match_selector( $selector, $root ) {
	switch ( $selector[0] ) {
		case SELECTOR_TAG:
			if ( $selector[1] == strtolower( $root->tagname() ) ) {
				return true;
			};
			break;
		case SELECTOR_ID:
			if ( $selector[1] == strtolower( $root->get_attribute( 'id' ) ) ) {
				return true;
			};
			break;
		case SELECTOR_CLASS:
			if ( node_have_class( $root, $selector[1] ) ) {
				return true;
			}
			if ( $selector[1] == strtolower( $root->get_attribute( 'class' ) ) ) {
				return true;
			};
			break;
		case SELECTOR_TAG_CLASS:
			if ( ( node_have_class( $root, $selector[2] ) ) &&
			     ( $selector[1] == strtolower( $root->tagname() ) ) ) {
				return true;
			};
			break;
		case SELECTOR_SEQUENCE:
			foreach ( $selector[1] as $subselector ) {
				if ( ! match_selector( $subselector, $root ) ) {
					return false;
				};
			};

			return true;
		case SELECTOR_PARENT:
		case SELECTOR_PARENT_LOW_PRIORITY:
			$node = $root->parent();

			while ( $node && $node->node_type() == XML_ELEMENT_NODE ) {
				if ( match_selector( $selector[1], $node ) ) {
					return true;
				};
				$node = $node->parent();
			};

			return false;
		case SELECTOR_DIRECT_PARENT:
			$node = $root->parent();
			if ( $node && $node->node_type() == XML_ELEMENT_NODE ) {
				if ( match_selector( $selector[1], $node ) ) {
					return true;
				};
			};

			return false;
		case SELECTOR_ATTR:
			$attr_name = $selector[1];

			return $root->has_attribute( $attr_name );
		case SELECTOR_ATTR_VALUE:
			// Note that CSS 2.1 standard does not says strictly if attribute case
			// is significiant:
			// """
			// Attribute values must be identifiers or strings. The case-sensitivity of attribute names and
			// values in selectors depends on the document language.
			// """
			// As we've met several problems with pages having INPUT type attributes in upper (or ewen worse - mixed!)
			// case, the following decision have been accepted: attribute values should not be case-sensitive

			$attr_name  = $selector[1];
			$attr_value = $selector[2];

			if ( ! $root->has_attribute( $attr_name ) ) {
				return false;
			};

			return strtolower( $root->get_attribute( $attr_name ) ) == strtolower( $attr_value );
		case SELECTOR_ATTR_VALUE_WORD:
			// Note that CSS 2.1 standard does not says strictly if attribute case
			// is significiant:
			// """
			// Attribute values must be identifiers or strings. The case-sensitivity of attribute names and
			// values in selectors depends on the document language.
			// """
			// As we've met several problems with pages having INPUT type attributes in upper (or ewen worse - mixed!)
			// case, the following decision have been accepted: attribute values should not be case-sensitive

			$attr_name  = $selector[1];
			$attr_value = $selector[2];

			if ( ! $root->has_attribute( $attr_name ) ) {
				return false;
			};

			$words = preg_split( "/\s+/", $root->get_attribute( $attr_name ) );
			foreach ( $words as $word ) {
				if ( strtolower( $word ) == strtolower( $attr_value ) ) {
					return true;
				};
			};

			return false;
		case SELECTOR_PSEUDOCLASS_LINK:
			return $root->tagname() == "a" && $root->has_attribute( 'href' );
		case SELECTOR_PSEUDOCLASS_LINK_LOW_PRIORITY:
			return $root->tagname() == "a" && $root->has_attribute( 'href' );

		// Note that :before and :after always match
		case SELECTOR_PSEUDOELEMENT_BEFORE:
			return true;
		case SELECTOR_PSEUDOELEMENT_AFTER:
			return true;

		case SELECTOR_LANGUAGE:
			// FIXME: determine the document language
			return true;

		case SELECTOR_ANY:
			return true;
	};

	return false;
}

function css_selector_specificity( $selector ) {
	switch ( $selector[0] ) {
		case SELECTOR_ID:
			return array( 1, 0, 0 );
		case SELECTOR_CLASS:
			return array( 0, 1, 0 );
		case SELECTOR_TAG:
			return array( 0, 0, 1 );
		case SELECTOR_TAG_CLASS:
			return array( 0, 1, 1 );
		case SELECTOR_SEQUENCE:
			$specificity = array( 0, 0, 0 );
			foreach ( $selector[1] as $subselector ) {
				$s           = css_selector_specificity( $subselector );
				$specificity = array(
					$specificity[0] + $s[0],
					$specificity[1] + $s[1],
					$specificity[2] + $s[2]
				);
			}

			return $specificity;
		case SELECTOR_PARENT:
			return css_selector_specificity( $selector[1] );
		case SELECTOR_PARENT_LOW_PRIORITY:
			return array( - 1, - 1, - 1 );
		case SELECTOR_DIRECT_PARENT:
			return css_selector_specificity( $selector[1] );
		case SELECTOR_ATTR:
			return array( 0, 1, 0 );
		case SELECTOR_ATTR_VALUE:
			return array( 0, 1, 0 );
		case SELECTOR_ATTR_VALUE_WORD:
			return array( 0, 1, 0 );
		case SELECTOR_PSEUDOCLASS_LINK:
			return array( 0, 1, 0 );
		case SELECTOR_PSEUDOCLASS_LINK_LOW_PRIORITY:
			return array( 0, 0, 0 );
		case SELECTOR_PSEUDOELEMENT_BEFORE:
			return array( 0, 0, 0 );
		case SELECTOR_PSEUDOELEMENT_AFTER:
			return array( 0, 0, 0 );
		case SELECTOR_LANGUAGE:
			return array( 0, 1, 0 );
		case SELECTOR_ANY:
			return array( 0, 1, 0 );
		default:
			die( "Bad selector while calculating selector specificity:" . $selector[0] );
	}
}

// Just an abstraction wrapper for determining the selector type
// from the selector-describing structure
//
function selector_get_type( $selector ) {
	return $selector[0];
}

;


// $Header: /cvsroot/html2ps/css.white-space.inc.php,v 1.8 2006/12/24 14:42:44 Konstantin Exp $

define( 'TABLE_LAYOUT_AUTO', 1 );
define( 'TABLE_LAYOUT_FIXED', 2 );

class CSSTableLayout extends CSSPropertyStringSet {
	function CSSTableLayout() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'auto'  => TABLE_LAYOUT_AUTO,
				'fixed' => TABLE_LAYOUT_FIXED
			) );
	}

	function default_value() {
		return TABLE_LAYOUT_AUTO;
	}

	function get_property_code() {
		return CSS_TABLE_LAYOUT;
	}

	function get_property_name() {
		return 'table-layout';
	}
}

CSS::register_css_property( new CSSTableLayout() );


// $Header: /cvsroot/html2ps/css.text-align.inc.php,v 1.10 2006/09/07 18:38:14 Konstantin Exp $

define( 'TA_LEFT', 0 );
define( 'TA_RIGHT', 1 );
define( 'TA_CENTER', 2 );
define( 'TA_JUSTIFY', 3 );

class CSSTextAlign extends CSSPropertyStringSet {
	function CSSTextAlign() {
		$this->CSSPropertyStringSet( true,
			true,
			array(
				'inherit' => CSS_PROPERTY_INHERIT,
				'left'    => TA_LEFT,
				'right'   => TA_RIGHT,
				'center'  => TA_CENTER,
				'middle'  => TA_CENTER,
				'justify' => TA_JUSTIFY
			) );
	}

	function default_value() {
		return TA_LEFT;
	}

	function value2pdf( $value ) {
		switch ( $value ) {
			case TA_LEFT:
				return "ta_left";
			case TA_RIGHT:
				return "ta_right";
			case TA_CENTER:
				return "ta_center";
			case TA_JUSTIFY:
				return "ta_justify";
			default:
				return "ta_left";
		}
	}

	function get_property_code() {
		return CSS_TEXT_ALIGN;
	}

	function get_property_name() {
		return 'text-align';
	}
}

CSS::register_css_property( new CSSTextAlign );


// $Header: /cvsroot/html2ps/css.text-decoration.inc.php,v 1.10 2006/09/07 18:38:14 Konstantin Exp $

/**
 * TODO: correct inheritance
 *
 * This property describes decorations that are added to the text of
 * an element using the element's color. When specified on an inline
 * element, it affects all the  boxes generated by that element; for
 * all  other  elements,  the   decorations  are  propagated  to  an
 * anonymous inline  box that wraps all the  in-flow inline children
 * of the element, and to any block-level in-flow descendants. It is
 * not,  however,  further  propagated  to floating  and  absolutely
 * positioned descendants, nor to the contents of 'inline-table' and
 * 'inline-block' descendants.
 */
class CSSTextDecoration extends CSSPropertyHandler {
	function CSSTextDecoration() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return array(
			"U" => false,
			"O" => false,
			"T" => false
		);
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		$parsed = $this->default_value();
		if ( strstr( $value, "overline" ) !== false ) {
			$parsed['O'] = true;
		};
		if ( strstr( $value, "underline" ) !== false ) {
			$parsed['U'] = true;
		};
		if ( strstr( $value, "line-through" ) !== false ) {
			$parsed['T'] = true;
		};

		return $parsed;
	}

	function get_property_code() {
		return CSS_TEXT_DECORATION;
	}

	function get_property_name() {
		return 'text-decoration';
	}
}

CSS::register_css_property( new CSSTextDecoration );


// $Header: /cvsroot/html2ps/css.text-transform.inc.php,v 1.2 2006/07/09 09:07:46 Konstantin Exp $

define( 'CSS_TEXT_TRANSFORM_NONE', 0 );
define( 'CSS_TEXT_TRANSFORM_CAPITALIZE', 1 );
define( 'CSS_TEXT_TRANSFORM_UPPERCASE', 2 );
define( 'CSS_TEXT_TRANSFORM_LOWERCASE', 3 );

class CSSTextTransform extends CSSPropertyStringSet {
	function CSSTextTransform() {
		$this->CSSPropertyStringSet( false,
			true,
			array(
				'inherit'    => CSS_PROPERTY_INHERIT,
				'none'       => CSS_TEXT_TRANSFORM_NONE,
				'capitalize' => CSS_TEXT_TRANSFORM_CAPITALIZE,
				'uppercase'  => CSS_TEXT_TRANSFORM_UPPERCASE,
				'lowercase'  => CSS_TEXT_TRANSFORM_LOWERCASE
			) );
	}

	function default_value() {
		return CSS_TEXT_TRANSFORM_NONE;
	}

	function get_property_code() {
		return CSS_TEXT_TRANSFORM;
	}

	function get_property_name() {
		return 'text-transform';
	}
}

CSS::register_css_property( new CSSTextTransform );


// $Header: /cvsroot/html2ps/css.text-indent.inc.php,v 1.13 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.text-indent.class.php' );

class CSSTextIndent extends CSSPropertyHandler {
	function CSSTextIndent() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return new TextIndentValuePDF( array( 0, false ) );
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		if ( is_percentage( $value ) ) {
			return new TextIndentValuePDF( array( (int) $value, true ) );
		} else {
			return new TextIndentValuePDF( array( $value, false ) );
		};
	}

	function get_property_code() {
		return CSS_TEXT_INDENT;
	}

	function get_property_name() {
		return 'text-indent';
	}
}

CSS::register_css_property( new CSSTextIndent() );


// $Header: /cvsroot/html2ps/css.top.inc.php,v 1.14 2006/11/11 13:43:52 Konstantin Exp $

require_once( HTML2PS_DIR . 'value.top.php' );

class CSSTop extends CSSPropertyHandler {
	function CSSTop() {
		$this->CSSPropertyHandler( false, false );
		$this->_autoValue = ValueTop::fromString( 'auto' );
	}

	function _getAutoValue() {
		return $this->_autoValue->copy();
	}

	function default_value() {
		return $this->_getAutoValue();
	}

	function get_property_code() {
		return CSS_TOP;
	}

	function get_property_name() {
		return 'top';
	}

	function parse( $value ) {
		return ValueTop::fromString( $value );
	}
}

CSS::register_css_property( new CSSTop );


// $Header: /cvsroot/html2ps/css.vertical-align.inc.php,v 1.23 2006/09/07 18:38:14 Konstantin Exp $

define( 'VA_SUPER', 0 );
define( 'VA_SUB', 1 );
define( 'VA_TOP', 2 );
define( 'VA_MIDDLE', 3 );
define( 'VA_BOTTOM', 4 );
define( 'VA_BASELINE', 5 );
define( 'VA_TEXT_TOP', 6 );
define( 'VA_TEXT_BOTTOM', 7 );

class VerticalAlignSuper {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		return; // Do nothing
	}
}

class VerticalAlignSub {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		return; // Do nothing
	}
}

class VerticalAlignTop {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		return; // Do nothing
	}
}

class VerticalAlignMiddle {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		$delta = max( 0, ( $row_height - $cell->get_real_full_height() ) / 2 );

		$old_top = $cell->get_top();
		$cell->offset( 0, - $delta );
		$cell->put_top( $old_top );
	}
}

class VerticalAlignBottom {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		$delta = ( $row_height - $cell->get_real_full_height() );

		$old_top = $cell->get_top();
		$cell->offset( 0, - $delta );
		$cell->put_top( $old_top );
	}
}

class VerticalAlignBaseline {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		$delta = ( $row_baseline - $cell->get_cell_baseline() );

		$old_top = $cell->get_top();
		$cell->offset( 0, - $delta );
		$cell->put_top( $old_top );
	}
}

class VerticalAlignTextTop {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		return; // Do nothing
	}
}

class VerticalAlignTextBottom {
	function apply_cell( &$cell, $row_height, $row_baseline ) {
		$delta = ( $row_baseline - $cell->get_cell_baseline() );

		$old_top = $cell->get_top();
		$cell->offset( 0, - $delta );
		$cell->put_top( $old_top );
	}
}

class CSSVerticalAlign extends CSSPropertyHandler {
	function CSSVerticalAlign() {
		// Note that in general, parameters 'true' and 'false' are non meaningful in out case,
		// as we anyway override 'inherit' and 'inherit_text' in this class.
		$this->CSSPropertyHandler( true, true );
	}

	function inherit( $old_state, &$new_state ) {
		// Determine parent 'display' value
		$parent_display = $old_state[ CSS_DISPLAY ];

		// Inherit vertical-align from table-rows
		if ( $parent_display === "table-row" ) {
			$this->replace_array( $this->get( $old_state ),
				$new_state );

			return;
		}

		if ( is_inline_element( $parent_display ) ) {
			$this->replace_array( $this->get( $old_state ), $new_state );

			return;
		};

		$this->replace_array( $this->default_value(), $new_state );

		return;
	}

	function inherit_text( $old_state, &$new_state ) {
		// Determine parent 'display' value
		$parent_display = $old_state[ CSS_DISPLAY ];

		$this->replace_array( is_inline_element( $parent_display ) ? $this->get( $old_state ) : $this->default_value(),
			$new_state );
	}

	function default_value() {
		return VA_BASELINE;
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		// Convert value to lower case, as html allows values
		// in both cases to be entered
		$value = strtolower( $value );

		if ( $value === 'baseline' ) {
			return VA_BASELINE;
		};
		if ( $value === 'sub' ) {
			return VA_SUB;
		};
		if ( $value === 'super' ) {
			return VA_SUPER;
		};
		if ( $value === 'top' ) {
			return VA_TOP;
		};
		if ( $value === 'middle' ) {
			return VA_MIDDLE;
		};

		// As some brainless designers sometimes use 'center' instead of 'middle',
		// we'll add support for it
		if ( $value === 'center' ) {
			return VA_MIDDLE;
		}

		if ( $value === 'bottom' ) {
			return VA_BOTTOM;
		};
		if ( $value === 'text-top' ) {
			return VA_TEXT_TOP;
		};
		if ( $value === 'text-bottom' ) {
			return VA_TEXT_BOTTOM;
		};

		return $this->default_value();
	}

	function value2pdf( $value ) {
		if ( $value === VA_SUPER ) {
			return new VerticalAlignSuper;
		}
		if ( $value === VA_SUB ) {
			return new VerticalAlignSub;
		}
		if ( $value === VA_TOP ) {
			return new VerticalAlignTop;
		}
		if ( $value === VA_MIDDLE ) {
			return new VerticalAlignMiddle;
		}
		if ( $value === VA_BOTTOM ) {
			return new VerticalAlignBottom;
		}
		if ( $value === VA_BASELINE ) {
			return new VerticalAlignBaseline;
		}
		if ( $value === VA_TEXT_TOP ) {
			return new VerticalAlignTextTop;
		}
		if ( $value === VA_TEXT_BOTTOM ) {
			return new VerticalAlignTextBottom;
		}

		return new VerticalAlignBaseline;
	}

	function applicable( $css_state ) {
		$handler =& CSS::get_handler( CSS_DISPLAY );
		$display = $handler->get( $css_state->getState() );

		return
			$display === 'table-cell' ||
			$display === 'table-row' ||
			is_inline_element( $display );
	}

	function get_property_code() {
		return CSS_VERTICAL_ALIGN;
	}

	function get_property_name() {
		return 'vertical-align';
	}
}

CSS::register_css_property( new CSSVerticalAlign );


// $Header: /cvsroot/html2ps/css.visibility.inc.php,v 1.6 2007/04/07 11:16:34 Konstantin Exp $

define( 'VISIBILITY_VISIBLE', 0 );
define( 'VISIBILITY_HIDDEN', 1 );
define( 'VISIBILITY_COLLAPSE', 2 ); // TODO: currently treated is hidden

class CSSVisibility extends CSSPropertyStringSet {
	function CSSVisibility() {
		$this->CSSPropertyStringSet( false,
			false,
			array(
				'inherit'  => CSS_PROPERTY_INHERIT,
				'visible'  => VISIBILITY_VISIBLE,
				'hidden'   => VISIBILITY_HIDDEN,
				'collapse' => VISIBILITY_COLLAPSE
			) );
	}

	function default_value() {
		return VISIBILITY_VISIBLE;
	}

	function get_property_code() {
		return CSS_VISIBILITY;
	}

	function get_property_name() {
		return 'visibility';
	}
}

CSS::register_css_property( new CSSVisibility );


// $Header: /cvsroot/html2ps/css.white-space.inc.php,v 1.9 2007/01/24 18:55:52 Konstantin Exp $

define( 'WHITESPACE_NORMAL', 0 );
define( 'WHITESPACE_PRE', 1 );
define( 'WHITESPACE_NOWRAP', 2 );
define( 'WHITESPACE_PRE_WRAP', 3 );
define( 'WHITESPACE_PRE_LINE', 4 );

class CSSWhiteSpace extends CSSPropertyStringSet {
	function CSSWhiteSpace() {
		$this->CSSPropertyStringSet( true,
			true,
			array(
				'normal'   => WHITESPACE_NORMAL,
				'pre'      => WHITESPACE_PRE,
				'pre-wrap' => WHITESPACE_PRE_WRAP,
				'nowrap'   => WHITESPACE_NOWRAP,
				'pre-line' => WHITESPACE_PRE_LINE
			) );
	}

	function default_value() {
		return WHITESPACE_NORMAL;
	}

	function get_property_code() {
		return CSS_WHITE_SPACE;
	}

	function get_property_name() {
		return 'white-space';
	}
}

CSS::register_css_property( new CSSWhiteSpace );


// $Header: /cvsroot/html2ps/css.width.inc.php,v 1.19 2007/01/24 18:55:53 Konstantin Exp $

require_once( HTML2PS_DIR . 'css.min-width.inc.php' );

//require_once(HTML2PS_DIR . 'css.property.sub.class.php');

class CSSCompositeWidth extends CSSPropertyHandler {
	function CSSCompositeWidth() {
		$this->CSSPropertyHandler( false, false );
	}

	function get_property_code() {
		return CSS_HTML2PS_COMPOSITE_WIDTH;
	}

	function get_property_name() {
		return '-html2ps-composite-width';
	}

	function default_value() {
		return new WCNone();
	}
}

class CSSWidth extends CSSSubProperty {
	function CSSWidth( $owner ) {
		$this->CSSSubProperty( $owner );
	}

	function set_value( &$owner_value, &$value ) {
		$min                     = $owner_value->_min_width;
		$owner_value             = $value->copy();
		$owner_value->_min_width = $min;
	}

	function &get_value( &$owner_value ) {
		return $owner_value;
	}

	function default_value() {
		return new WCNone;
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		// Check if user specified empty value
		if ( $value === '' ) {
			return new WCNone;
		};

		// Check if this value is 'auto' - default value of this property
		if ( $value === 'auto' ) {
			return new WCNone;
		};

		if ( substr( $value, strlen( $value ) - 1, 1 ) == '%' ) {
			// Percentage
			return new WCFraction( ( (float) $value ) / 100 );
		} else {
			// Constant
			return new WCConstant( trim( $value ) );
		}
	}

	function get_property_code() {
		return CSS_WIDTH;
	}

	function get_property_name() {
		return 'width';
	}
}

$width = new CSSCompositeWidth;
CSS::register_css_property( $width );
CSS::register_css_property( new CSSWidth( $width ) );
CSS::register_css_property( new CSSMinWidth( $width, '_min_width' ) );


// $Header: /cvsroot/html2ps/css.word-spacing.inc.php,v 1.2 2006/09/07 18:38:15 Konstantin Exp $

class CSSWordSpacing extends CSSPropertyHandler {
	var $_default_value;

	function CSSWordSpacing() {
		$this->CSSPropertyHandler( false, true );

		$this->_default_value = Value::fromString( "0" );
	}

	function default_value() {
		return $this->_default_value;
	}

	function parse( $value ) {
		$value = trim( $value );

		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		if ( $value === 'normal' ) {
			return $this->_default_value;
		};

		return Value::fromString( $value );
	}

	function get_property_code() {
		return CSS_WORD_SPACING;
	}

	function get_property_name() {
		return 'word-spacing';
	}
}

CSS::register_css_property( new CSSWordSpacing );


class CSSZIndex extends CSSPropertyHandler {
	function CSSZIndex() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return 0;
	}

	function parse( $value ) {
		if ( $value === 'inherit' ) {
			return CSS_PROPERTY_INHERIT;
		};

		return (int) $value;
	}

	function get_property_code() {
		return CSS_Z_INDEX;
	}

	function get_property_name() {
		return 'z-index';
	}
}

CSS::register_css_property( new CSSZIndex );


// $Header: /cvsroot/html2ps/css.pseudo.align.inc.php,v 1.13 2006/09/07 18:38:14 Konstantin Exp $

define( 'PA_LEFT', 0 );
define( 'PA_CENTER', 1 );
define( 'PA_RIGHT', 2 );

// This is a pseudo CSS property for

class CSSPseudoAlign extends CSSPropertyHandler {
	function CSSPseudoAlign() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return PA_LEFT;
	}

	function inherit( $old_state, &$new_state ) {
		// This pseudo-property is not inherited by tables
		// As current box display value may not be know at the moment of inheriting,
		// we'll use parent display value, stopping inheritance on the table-row/table-group level

		// Determine parent 'display' value
		$parent_display = $old_state[ CSS_DISPLAY ];

		$this->replace_array( ( $parent_display === 'table' ) ? $this->default_value() : $this->get( $old_state ),
			$new_state );
	}

	function parse( $value ) {
		// Convert value to lower case, as html allows values
		// in both cases to be entered
		//
		$value = strtolower( $value );

		if ( $value === 'left' ) {
			return PA_LEFT;
		}
		if ( $value === 'right' ) {
			return PA_RIGHT;
		}
		if ( $value === 'center' ) {
			return PA_CENTER;
		}

		// For compatibility with non-valid HTML
		//
		if ( $value === 'middle' ) {
			return PA_CENTER;
		}

		return $this->default_value();
	}

	function value2pdf( $value ) {
		switch ( $value ) {
			case PA_LEFT:
				return "ta_left";
			case PA_RIGHT:
				return "ta_right";
			case PA_CENTER:
				return "ta_center";
			default:
				return "ta_left";
		}
	}

	function get_property_code() {
		return CSS_HTML2PS_ALIGN;
	}

	function get_property_name() {
		return '-html2ps-align';
	}
}

CSS::register_css_property( new CSSPseudoAlign );


// $Header: /cvsroot/html2ps/css.pseudo.cellspacing.inc.php,v 1.6 2006/09/07 18:38:14 Konstantin Exp $

class CSSCellSpacing extends CSSPropertyHandler {
	function CSSCellSpacing() {
		$this->CSSPropertyHandler( true, false );
	}

	function default_value() {
		return Value::fromData( 1, UNIT_PX );
	}

	function parse( $value ) {
		return Value::fromString( $value );
	}

	function get_property_code() {
		return CSS_HTML2PS_CELLSPACING;
	}

	function get_property_name() {
		return '-html2ps-cellspacing';
	}
}

CSS::register_css_property( new CSSCellSpacing );


// $Header: /cvsroot/html2ps/css.pseudo.cellpadding.inc.php,v 1.6 2006/09/07 18:38:14 Konstantin Exp $

class CSSCellPadding extends CSSPropertyHandler {
	function CSSCellPadding() {
		$this->CSSPropertyHandler( true, false );
	}

	function default_value() {
		return Value::fromData( 1, UNIT_PX );
	}

	function parse( $value ) {
		return Value::fromString( $value );
	}

	function get_property_code() {
		return CSS_HTML2PS_CELLPADDING;
	}

	function get_property_name() {
		return '-html2ps-cellpadding';
	}
}

CSS::register_css_property( new CSSCellPadding );


class CSSPseudoFormAction extends CSSPropertyHandler {
	function CSSPseudoFormAction() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return null;
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_HTML2PS_FORM_ACTION;
	}

	function get_property_name() {
		return '-html2ps-form-action';
	}
}

CSS::register_css_property( new CSSPseudoFormAction );


class CSSPseudoFormRadioGroup extends CSSPropertyHandler {
	function CSSPseudoFormRadioGroup() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return null;
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_HTML2PS_FORM_RADIOGROUP;
	}

	function get_property_name() {
		return '-html2ps-form-radiogroup';
	}
}

CSS::register_css_property( new CSSPseudoFormRadioGroup );


class CSSPseudoLinkDestination extends CSSPropertyHandler {
	function CSSPseudoLinkDestination() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return null;
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_HTML2PS_LINK_DESTINATION;
	}

	function get_property_name() {
		return '-html2ps-link-destination';
	}
}

CSS::register_css_property( new CSSPseudoLinkDestination );


class CSSPseudoLinkTarget extends CSSPropertyHandler {
	function CSSPseudoLinkTarget() {
		$this->CSSPropertyHandler( true, true );
	}

	function default_value() {
		return "";
	}

	function is_external_link( $value ) {
		return ( strlen( $value ) > 0 && $value{0} != "#" );
	}

	function is_local_link( $value ) {
		return ( strlen( $value ) > 0 && $value{0} == "#" );
	}

	function parse( $value, &$pipeline ) {
		// Keep local links (starting with sharp sign) as-is
		if ( CSSPseudoLinkTarget::is_local_link( $value ) ) {
			return $value;
		}

		$data = @parse_url( $value );
		if ( ! isset( $data['scheme'] ) || $data['scheme'] == "" || $data['scheme'] == "http" ) {
			return $pipeline->guess_url( $value );
		} else {
			return $value;
		};
	}

	function get_property_code() {
		return CSS_HTML2PS_LINK_TARGET;
	}

	function get_property_name() {
		return '-html2ps-link-target';
	}
}

CSS::register_css_property( new CSSPseudoLinkTarget );


// $Header: /cvsroot/html2ps/css.pseudo.listcounter.inc.php,v 1.4 2006/09/07 18:38:14 Konstantin Exp $

class CSSPseudoListCounter extends CSSPropertyHandler {
	function CSSPseudoListCounter() {
		$this->CSSPropertyHandler( true, false );
	}

	function default_value() {
		return 0;
	}

	function get_property_code() {
		return CSS_HTML2PS_LIST_COUNTER;
	}

	function get_property_name() {
		return '-html2ps-list-counter';
	}

	function parse( $value ) {
		return (int) $value;
	}
}

CSS::register_css_property( new CSSPseudoListCounter );


// $Header: /cvsroot/html2ps/css.pseudo.localalign.inc.php,v 1.4 2006/09/07 18:38:14 Konstantin Exp $

define( 'LA_LEFT', 0 );
define( 'LA_CENTER', 1 );
define( 'LA_RIGHT', 2 );

class CSSLocalAlign extends CSSPropertyHandler {
	function CSSLocalAlign() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return LA_LEFT;
	}

	function parse( $value ) {
		return $value;
	}

	function get_property_code() {
		return CSS_HTML2PS_LOCALALIGN;
	}

	function get_property_name() {
		return '-html2ps-localalign';
	}
}

CSS::register_css_property( new CSSLocalAlign );


// $Header: /cvsroot/html2ps/css.pseudo.nowrap.inc.php,v 1.6 2006/09/07 18:38:14 Konstantin Exp $

define( 'NOWRAP_NORMAL', 0 );
define( 'NOWRAP_NOWRAP', 1 );

class CSSPseudoNoWrap extends CSSPropertyHandler {
	function CSSPseudoNoWrap() {
		$this->CSSPropertyHandler( false, false );
	}

	function default_value() {
		return NOWRAP_NORMAL;
	}

	function get_property_code() {
		return CSS_HTML2PS_NOWRAP;
	}

	function get_property_name() {
		return '-html2ps-nowrap';
	}
}

CSS::register_css_property( new CSSPseudoNoWrap );


require_once( HTML2PS_DIR . 'value.border.class.php' );
require_once( HTML2PS_DIR . 'value.border.edge.class.php' );

class CSSPseudoTableBorder extends CSSPropertyHandler {
	var $_defaultValue;

	function CSSPseudoTableBorder() {
		$this->CSSPropertyHandler( true, false );

		$this->_defaultValue = BorderPDF::create( array(
			'top'    => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'right'  => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'bottom' => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			),
			'left'   => array(
				'width' => Value::fromString( '2px' ),
				'color' => array( 0, 0, 0 ),
				'style' => BS_NONE
			)
		) );
	}

	function default_value() {
		return $this->_defaultValue->copy();
	}

	function get_property_code() {
		return CSS_HTML2PS_TABLE_BORDER;
	}

	function get_property_name() {
		return '-html2ps-table-border';
	}

	function inherit( $old_state, &$new_state ) {
		// Determine parent 'display' value
		$parent_display = $old_state[ CSS_DISPLAY ];

		// Inherit from table rows and tables
		$inherit_from = array( 'table-row', 'table', 'table-row-group', 'table-header-group', 'table-footer-group' );
		if ( array_search( $parent_display, $inherit_from ) !== false ) {
			$this->replace_array( $this->get( $old_state ),
				$new_state );

			return;
		}

		$this->replace_array( $this->default_value(), $new_state );

		return;
	}
}

CSS::register_css_property( new CSSPseudoTableBorder() );


// $Header: /cvsroot/html2ps/converter.class.php,v 1.6 2006/06/25 13:55:35 Konstantin Exp $

class Converter {
	function create() {
		//     if (function_exists('iconv')) {
		//       return new IconvConverter;
		//     } else {
		return new PurePHPConverter;
		//     }
	}
}

class IconvConverter {
	function to_utf8( $string, $encoding ) {
		return iconv( strtoupper( $encoding ), "UTF-8", $string );
	}
}

class PurePHPConverter {
	function apply_aliases( $encoding ) {
		global $g_encoding_aliases;

		if ( isset( $g_encoding_aliases[ $encoding ] ) ) {
			return $g_encoding_aliases[ $encoding ];
		}

		return $encoding;
	}

	function to_utf8( $html, $encoding ) {
		global $g_utf8_converters;

		$encoding = $this->apply_aliases( $encoding );

		if ( $encoding === 'iso-8859-1' ) {
			return utf8_encode( $html );
		} elseif ( $encoding === 'utf-8' ) {
			return $html;
		} elseif ( isset( $g_utf8_converters[ $encoding ] ) ) {
			return $this->something_to_utf8( $html, $g_utf8_converters[ $encoding ][0] );
		} else {
			die( "Unsupported encoding detected: '$encoding'" );
		};
	}

	function something_to_utf8( $html, &$mapping ) {
		for ( $i = 0; $i < strlen( $html ); $i ++ ) {
			$replacement = code_to_utf8( $mapping[ $html{$i} ] );
			if ( $replacement != $html{$i} ) {
				$html = substr_replace( $html, $replacement, $i, 1 );
				$i    += strlen( $replacement ) - 1;
			};
		};

		return $html;
	}
}

// $Header: /cvsroot/html2ps/treebuilder.class.php,v 1.17 2007/05/06 18:49:29 Konstantin Exp $

if ( ! defined( 'XML_ELEMENT_NODE' ) ) {
	define( 'XML_ELEMENT_NODE', 1 );
};
if ( ! defined( 'XML_TEXT_NODE' ) ) {
	define( 'XML_TEXT_NODE', 2 );
};
if ( ! defined( 'XML_DOCUMENT_NODE' ) ) {
	define( 'XML_DOCUMENT_NODE', 3 );
};

class TreeBuilder {
	function build( $xmlstring ) {
		if ( empty( $xmlstring ) ) {
			trigger_error( "Can not buid tree with empty xml", E_USER_ERROR );
		}
		// Detect if we're using PHP 4 (DOM XML extension)
		// or PHP 5 (DOM extension)
		// First uses a set of domxml_* functions,
		// Second - object-oriented interface
		// Third - pure PHP XML parser
		if ( function_exists( 'domxml_open_mem' ) ) {
			require_once( HTML2PS_DIR . 'dom.php4.inc.php' );

			return PHP4DOMTree::from_DOMDocument( domxml_open_mem( $xmlstring ) );
		};

		if ( class_exists( 'DOMDocument' ) ) {
			require_once( HTML2PS_DIR . 'dom.php5.inc.php' );

			return DOMTree::from_DOMDocument( DOMDocument::loadXML( $xmlstring ) );
		};

		require_once( HTML2PS_DIR . 'dom.activelink.inc.php' );
		if ( file_exists( HTML2PS_DIR . 'classes/include.php' ) ) {
			require_once( HTML2PS_DIR . 'classes/include.php' );
			import( 'org.active-link.xml.XML' );
			import( 'org.active-link.xml.XMLDocument' );

			// preprocess character references
			// literal references (actually, parser SHOULD do it itself; nevertheless, Activelink just ignores these entities)
			$xmlstring = preg_replace( "/&amp;/", "&", $xmlstring );
			$xmlstring = preg_replace( "/&quot;/", "\"", $xmlstring );
			$xmlstring = preg_replace( "/&lt;/", "<", $xmlstring );
			$xmlstring = preg_replace( "/&gt;/", ">", $xmlstring );

			// in decimal form
			while ( preg_match( "@&#(\d+);@", $xmlstring, $matches ) ) {
				$xmlstring = preg_replace( "@&#" . $matches[1] . ";@", code_to_utf8( $matches[1] ), $xmlstring );
			};
			// in hexadecimal form
			while ( preg_match( "@&#x(\d+);@i", $xmlstring, $matches ) ) {
				$xmlstring = preg_replace( "@&#x" . $matches[1] . ";@i", code_to_utf8( hexdec( $matches[1] ) ), $xmlstring );
			};

			$tree = ActiveLinkDOMTree::from_XML( new XML( $xmlstring ) );

			return $tree;
		}
		die( "None of DOM/XML, DOM or ActiveLink DOM extension found. Check your PHP configuration." );
	}
}

;


// Note that REAL cache should check for Last-Modified HTTP header at least;
// As I don't like idea of implementing the full-scaled HTTP protocol library
// and curl extension is very rare, this implementation of cache is very simple.
// Cache is cleared after the script finishes it work!

// Also, it can have problems with simultaneous access to the images.

// The class responsible for downloading and caching images
// as PHP does not support the static variables, we'll use a global variable
// containing all cached objects; note that cache consumes memory!
//
$GLOBALS['g_image_cache'] = array();

class Image {
	var $_handle;
	var $_filename;
	var $_type;

	function Image( $handle, $filename, $type ) {
		$this->_handle   = $handle;
		$this->_filename = $filename;
		$this->_type     = $type;
	}

	function get_filename() {
		return $this->_filename;
	}

	function get_handle() {
		return $this->_handle;
	}

	function get_type() {
		return $this->_type;
	}

	function sx() {
		if ( ! $this->_handle ) {
			return 0;
		};

		return imagesx( $this->_handle );
	}

	function sy() {
		if ( ! $this->_handle ) {
			return 0;
		};

		return imagesy( $this->_handle );
	}
}

class ImageFactory {
	// Static funcion; checks if given URL is already cached and either returns
	// cached object or downloads the requested image
	//
	function get( $url, &$pipeline ) {
		global $g_config, $g_image_cache;
		if ( ! $g_config['renderimages'] ) {
			return null;
		};

		// Check if this URL have been cached
		//
		if ( isset( $g_image_cache[ $url ] ) ) {
			//      return do_image_open($g_image_cache[$url]);
			return $g_image_cache[ $url ];
		};

		// Download image; we should do it before we call do_image_open,
		// as it tries to open image file twice: first to determine image type
		// and second to actually create the image - PHP url wrappers do no caching
		// at all
		//
		$filename = ImageFactory::make_cache_filename( $url );

		// REQUIRES: PHP 4.3.0+
		// we suppress warning messages, as missing image files will cause 'copy' to print
		// several warnings
		//
		// @TODO: change to fetcher class call
		//

		// simplify our url by fetcher simlify functionality
		$url  = FetcherUrl::_simplify_path( $url );
		$data = $pipeline->fetch( $url );

		if ( is_null( $data ) ) {
			trigger_error( "Cannot fetch image: " . $url, E_USER_WARNING );

			return null;
		};

		$file = fopen( $filename, 'wb' );
		fwrite( $file, $data->content );
		fclose( $file );
		$pipeline->pop_base_url();

		// register it in the cached objects array
		//
		$handle = do_image_open( $filename, $type );
		if ( $handle ) {
			$g_image_cache[ $url ] =& new Image( $handle,
				$filename,
				$type );
		} else {
			$g_image_cache[ $url ] = null;
		};
		// return image
		//
		// return do_image_open($filename);
		return $g_image_cache[ $url ];
	}

	// Makes the filename to contain the cached version of URL
	//
	function make_cache_filename( $url ) {
		// We cannot use the $url as an cache image name as it could be longer than
		// allowed file name length (especially after escaping specialy symbols)
		// thus, we generate long almost random 32-character name using the md5 hash function
		//
		return CACHE_DIR . md5( time() + $url + rand() );
	}

	// Checks if cache directory is available
	//
	function check_cache_dir() {
		// TODO: some cool easily understandable error message for the case
		// image cache directory cannot be created or accessed

		// Check if CACHE_DIR exists
		//
		if ( ! is_dir( CACHE_DIR ) ) {
			// Cache directory does not exists; try to create it (with read/write rightss for the owner only)
			//
			if ( ! mkdir( CACHE_DIR, 0700 ) ) {
				die( "Cache directory cannot be created" );
			}
		};

		// Check if we can read and write to the CACHE_DIR
		//
		// Note that directory should have 'rwx' (7) permission, so the script will
		// be able to list directory contents; under Windows is_executable always returns false
		// for directories, so we need to drop this check in this case.
		//
		// A user's note for 'is_executable' function on PHP5:
		// "The change doesn't appear to be documented, so I thought I would mention it.
		// In php5, as opposed to php4, you can no longer rely on is_executable to check the executable bit
		// on a directory in 'nix. You can still use the first note's method to check if a directory is traversable:
		// @file_exists("adirectory/.");"
		//
		if ( ! is_readable( CACHE_DIR ) ||
		     ! is_writeable( CACHE_DIR ) ||
		     ( ! @file_exists( CACHE_DIR . '.' ) ) ) {
			// omg. Cache directory exists, but useless
			//
			die( "Check cache directory permissions; cannot either read or write to directory cache" );
		};

		return;
	}

	// Clears the image cache (as we're neither implemented checking of Last-Modified HTTP header nor
	// provided the means of limiting the cache size
	//
	// TODO: Will cause problems with simultaneous access to the same images
	//
	function clear_cache() {
		foreach ( $GLOBALS['g_image_cache'] as $key => $value ) {
			if ( ! is_null( $value ) && is_file( $value->get_filename() ) ) {
				unlink( $value->get_filename() );
			};
		};
		$g_image_cache = array();
	}
}


class FetchedData {
	function get_additional_data() {
		die( "Unoverridden 'get_additional_data' called in " . get_class( $this ) );
	}

	function get_content() {
		die( "Unoverridden 'get_content' called in " . get_class( $this ) );
	}

	function get_uri() {
		return "";
	}
}

class FetchedDataHTML extends FetchedData {
	function detect_encoding() {
		die( "Unoverridden 'detect_encoding' called in " . get_class( $this ) );
	}

	function _detect_encoding_using_meta() {
		if ( preg_match( "#<\s*meta[^>]+content=(['\"])?text/html;\s*charset=([\w\d-]+)#is", $this->get_content(), $matches ) ) {
			return strtolower( $matches[2] );
		} else {
			return null;
		};
	}
}

class FetchedDataURL extends FetchedDataHTML {
	var $content;
	var $headers;
	var $url;

	function detect_encoding() {
		// First, try to get encoding from META http-equiv tag
		//
		$encoding = $this->_detect_encoding_using_meta( $this->content );

		// If no META encoding specified, try to use encoding from HTTP response
		//
		if ( is_null( $encoding ) ) {
			foreach ( $this->headers as $header ) {
				if ( preg_match( "/Content-Type: .*charset=\s*([^\s;]+)/i", $header, $matches ) ) {
					$encoding = strtolower( $matches[1] );
				};
			};
		}

		// At last, fall back to default encoding
		//
		if ( is_null( $encoding ) ) {
			$encoding = "iso-8859-1";
		}

		return $encoding;
	}

	function FetchedDataURL( $content, $headers, $url ) {
		$this->content = $content;
		$this->headers = $headers;
		$this->url     = $url;
	}

	function get_additional_data( $key ) {
		switch ( $key ) {
			case 'Content-Type':
				foreach ( $this->headers as $header ) {
					if ( preg_match( "/Content-Type: (.*)/", $header, $matches ) ) {
						return $matches[1];
					};
				};

				return null;
		};
	}

	function get_uri() {
		return $this->url;
	}

	function get_content() {
		return $this->content;
	}

	function set_content( $data ) {
		$this->content = $data;
	}
}


class FetchedDataFile extends FetchedDataHTML {
	var $content;
	var $path;

	function FetchedDataFile( $content, $path ) {
		$this->content = $content;
		$this->path    = $path;
	}

	function detect_encoding() {
		// First, try to get encoding from META http-equiv tag
		//
		$encoding = $this->_detect_encoding_using_meta( $this->content );

		// At last, fall back to default encoding
		//
		if ( is_null( $encoding ) ) {
			$encoding = "iso-8859-1";
		}

		return $encoding;
	}

	function get_additional_data( $key ) {
		return null;
	}

	function get_content() {
		return $this->content;
	}

	function set_content( $data ) {
		$this->content = $data;
	}
}

class DataFilter {
	function process( &$tree ) {
		die( "Oops. Inoverridden 'process' method called in " . get_class( $this ) );
	}
}

class DataFilterDoctype extends DataFilter {
	function DataFilterDoctype() {
	}

	function process( &$data ) {
		$html = $data->get_content();

		$xml_declaration = "<\?.*?\?>";
		$doctype         = "<!DOCTYPE.*?>";

		/**
		 * DOCTYPE declaration should be at the very beginning of the document
		 * (with the only exception of XML declaration).
		 *
		 * XML declaration is optional; XML declaration may be surrounded with whitespace
		 */

		if ( preg_match( "#^(?:\s*$xml_declaration\s*)?($doctype)#", $html, $matches ) ) {
			$doctype_match = $matches[1];

			/**
			 * remove extra spaces from doctype text; also, DOCTYPE may contain
			 * \n and \r character in its whitespace parts. Here, we replace them
			 * with one single space, converting it to the "normalized" form.
			 */
			$doctype_match = preg_replace( "/\s+/", " ", $doctype_match );


			/**
			 * Match doctype agaist standard doctypes
			 */
			switch ( $doctype_match ) {
				case '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">':
				case '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">':
				case '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">':
					$GLOBALS['g_config']['mode'] = 'html';

					return $data;
				case '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">':
				case '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">':
				case '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">':
					$GLOBALS['g_config']['mode'] = 'xhtml';

					return $data;
			};

		};

		/**
		 * No DOCTYPE found; fall back to quirks mode
		 */

		$GLOBALS['g_config']['mode'] = 'quirks';

		return $data;
	}
}


require_once( HTML2PS_DIR . 'filter.data.encoding.class.php' );

class DataFilterUTF8 extends DataFilterEncoding {
	function _convert( &$data, $encoding ) {
		$converter = Converter::create();
		$data->set_content( $converter->to_utf8( $data->get_content(), $encoding ) );
	}
}


require_once( HTML2PS_DIR . 'filter.data.encoding.class.php' );

class DataFilterUCS2 extends DataFilterEncoding {
	function _convert( &$data, $encoding ) {
		$converter = Converter::create();
		$data->set_content( $converter->to_ucs2( $data->get_content(), $encoding ) );
	}
}


/**
 * Converts tags to lower case
 */
class DataFilterHTML2XHTML extends DataFilter {
	function process( &$data ) {
		$data->set_content( html2xhtml( $data->get_content() ) );

		return $data;
	}
}


class DataFilterXHTML2XHTML extends DataFilter {
	function process( &$data ) {
		$data->set_content( xhtml2xhtml( $data->get_content() ) );

		return $data;
	}
}

class Parser {
	function process( &$data ) {
		die( "Oops! Unoverridden 'process' method called in " . get_class( $this ) );
	}
}

class ParserXHTML extends Parser {
	function &process( $html, &$pipeline, &$media ) {
		// Run the XML parser on the XHTML we've prepared
		$dom_tree = TreeBuilder::build( $html );

		// Check if parser returned valid document
		if ( is_null( $dom_tree ) ) {
			readfile( HTML2PS_DIR . 'templates/cannot_parse.html' );
			error_log( sprintf( "Cannot parse document: %s", $pipeline->get_base_url() ) );
			die( "HTML2PS Error" );
		}

		/**
		 * Detect the base URI for this document.
		 *
		 * According to the HTML 4.01 p. 12.4.1:
		 * User agents must calculate the base URI according to the following precedences (highest priority to lowest):
		 *
		 * 1. The base URI is set by the BASE element.
		 * 2. The base URI is given by meta data discovered during a protocol interaction, such as an HTTP header (see [RFC2616]).
		 * 3. By default, the base URI is that of the current document. Not all HTML documents have a base URI (e.g., a valid HTML document may appear in an email and may not be designated by a URI). Such HTML documents are considered erroneous if they contain relative URIs and rely on a default base URI.
		 */

		/**
		 * Check if BASE element present; use its first occurrence
		 */
		$this->_scan_base( $dom_tree, $pipeline );

		/**
		 * @todo fall back to the protocol metadata
		 */

		/**
		 * Parse STYLE / LINK nodes containing CSS references and definitions
		 * This should be done here, as the document body may include STYLE node
		 * (this violates HTML standard, but is rather often appears in Web)
		 */
		$css =& $pipeline->get_current_css();
		$css->scan_styles( $dom_tree, $pipeline );

		if ( ! is_null( $media ) ) {
			// Setup media size and margins
			$pipeline->get_page_media( 1, $media );
			$pipeline->output_driver->update_media( $media );
			$pipeline->_setupScales( $media );
		};

		$body =& traverse_dom_tree_pdf( $dom_tree );
		$box  =& create_pdf_box( $body, $pipeline );

		return $box;
	}

	function _scan_base( &$root, &$pipeline ) {
		switch ( $root->node_type() ) {
			case XML_ELEMENT_NODE:
				if ( $root->tagname() === 'base' ) {
					/**
					 * See HTML 4.01 p 12.4
					 * href - this attribute specifies an absolute URI that acts as the base URI for resolving relative URIs.
					 *
					 * At this moment pipeline object have current document URI on the top of the stack;
					 * we should replace it with the value of 'href' attribute of the BASE tag
					 *
					 * To handle (possibly) incorrect values, we use 'guess_url' function; in this case
					 * if 'href' attribute contains absolute value (is it SHOULD be), it will be used;
					 * if it is missing or is relative, we'll get more of less usable value base on current
					 * document URI.
					 */
					$new_url = $pipeline->guess_url( $root->get_attribute( 'href' ) );
					$pipeline->pop_base_url();
					$pipeline->push_base_url( $new_url );

					return true;
				};

			// We continue processing here!
			case XML_DOCUMENT_NODE:
				$child = $root->first_child();
				while ( $child ) {
					if ( $this->_scan_base( $child, $pipeline ) ) {
						return;
					};
					$child = $child->next_sibling();
				};

				return false;
		};

		return false;
	}
}


class PreTreeFilter {
	function process( &$tree, $data, &$pipeline ) {
		die( "Oops. Inoverridden 'process' method called in " . get_class( $this ) );
	}
}

class PreTreeFilterHTML2PSFields extends PreTreeFilter {
	var $filename;
	var $filesize;
	var $_timestamp;

	function PreTreeFilterHTML2PSFields( $filename = null, $filesize = null, $timestamp = null ) {
		$this->filename = $filename;
		$this->filesize = $filesize;

		if ( is_null( $timestamp ) ) {
			$this->_timestamp = date( "Y-m-d H:s" );
		} else {
			$this->_timestamp = $timestamp;
		};
	}

	function process( &$tree, $data, &$pipeline ) {
		if ( is_a( $tree, 'TextBox' ) ) {
			// Ignore completely empty text boxes
			if ( count( $tree->words ) == 0 ) {
				return;
			};

			switch ( $tree->words[0] ) {
				case '##PAGE##':
					$parent =& $tree->parent;
					$field  = BoxTextFieldPageNo::from_box( $tree );

					$parent->insert_before( $field, $tree );

					$parent->remove( $tree );
					break;
				case '##PAGES##':
					$parent =& $tree->parent;
					$field  = BoxTextFieldPages::from_box( $tree );
					$parent->insert_before( $field, $tree );
					$parent->remove( $tree );
					break;
				case '##FILENAME##':
					if ( is_null( $this->filename ) ) {
						$tree->words[0] = $data->get_uri();
					} else {
						$tree->words[0] = $this->filename;
					};
					break;
				case '##FILESIZE##':
					if ( is_null( $this->filesize ) ) {
						$tree->words[0] = strlen( $data->get_content() );
					} else {
						$tree->words[0] = $this->filesize;
					};
					break;
				case '##TIMESTAMP##':
					$tree->words[0] = $this->_timestamp;
					break;
			};
		} elseif ( is_a( $tree, 'GenericContainerBox' ) ) {
			for ( $i = 0; $i < count( $tree->content ); $i ++ ) {
				$this->process( $tree->content[ $i ], $data, $pipeline );
			};
		};
	}
}

class PreTreeFilterHeaderFooter extends PreTreeFilter {
	var $header_html;
	var $footer_html;

	function PreTreeFilterHeaderFooter( $header_html, $footer_html ) {
		$this->header_html = null;
		$this->footer_html = null;

		if ( trim( $header_html ) != "" ) {
			$this->header_html = "<body style=\"position: fixed; margin: 0; padding: 1px; width: 100%; left: 0; right: 0; bottom: 100%; text-align: center;\">" . trim( $header_html ) . "</body>";
		};

		if ( trim( $footer_html ) != "" ) {
			$this->footer_html = "<body style=\"position: fixed; margin: 0; padding: 1px; width: 100%; left: 0; right: 0; top: 100%; text-align: center;\">" . trim( $footer_html ) . "</body>";
		};
	}

	function process( &$tree, $data, &$pipeline ) {
		$parser = new ParserXHTML();

		$null = null;

		if ( $this->header_html ) {
			$box =& $parser->process( $this->header_html, $pipeline, $null );
			$tree->add_child( $box );
		};

		if ( $this->footer_html ) {
			$box =& $parser->process( $this->footer_html, $pipeline, $null );
			$tree->add_child( $box );
		};
	}
}


require_once( HTML2PS_DIR . 'box.note-call.class.php' );

/**
 * Support for CSS 3 position: footnote.
 *
 * Scans for elements having position: footnote and replaces them with
 * BoxNoteCall object (which contains reference to original data and
 * handles footnote rendering)
 */
class PreTreeFilterFootnotes extends PreTreeFilter {
	function process( &$tree, $data, &$pipeline ) {
		if ( is_a( $tree, 'GenericContainerBox' ) ) {
			for ( $i = 0; $i < count( $tree->content ); $i ++ ) {
				/**
				 * No need to check this conition for text boxes, as they do not correspond to
				 * HTML elements
				 */
				if ( ! is_a( $tree->content[ $i ], 'TextBox' ) ) {
					if ( $tree->content[ $i ]->get_css_property( CSS_POSITION ) == POSITION_FOOTNOTE ) {
						$tree->content[ $i ]->setCSSProperty( CSS_POSITION, POSITION_STATIC );

						$note_call           =& BoxNoteCall::create( $tree->content[ $i ], $pipeline );
						$tree->content[ $i ] =& $note_call;

						$pipeline->_addFootnote( $note_call );
					} else {
						$this->process( $tree->content[ $i ], $data, $pipeline );
					};
				};
			};
		};

		return true;
	}
}


/**
 * This is an internal HTML2PS filter; you never need to use it.
 */
class PreTreeFilterHeightConstraint extends PreTreeFilter {
	function process( &$tree, $data, &$pipeline ) {
		if ( ! is_a( $tree, 'GenericFormattedBox' ) ) {
			return;
		};

		/**
		 * In non-quirks mode, percentage height should be ignored for children of boxes having
		 * non-constrained height
		 */
		global $g_config;
		if ( $g_config['mode'] != 'quirks' ) {
			if ( ! is_null( $tree->parent ) ) {
				$parent_hc = $tree->parent->get_height_constraint();
				$hc        = $tree->get_height_constraint();

				if ( is_null( $parent_hc->constant ) &&
				     $hc->constant[1] ) {
					$hc->constant = null;
					$tree->put_height_constraint( $hc );
				};
			};
		};

		/**
		 * Set box height to constrained value
		 */
		$hc     = $tree->get_height_constraint();
		$height = $tree->get_height();

		$tree->height = $hc->apply( $height, $tree );

		/**
		 * Proceed to this box children
		 */
		if ( is_a( $tree, 'GenericContainerBox' ) ) {
			for ( $i = 0, $size = count( $tree->content ); $i < $size; $i ++ ) {
				$this->process( $tree->content[ $i ], $data, $pipeline );
			};
		};
	}
}

class LayoutEngine {
	function process( &$tree, &$media ) {
		die( "Oops. Inoverridden 'process' method called in " . get_class( $this ) );
	}
}


//require_once(HTML2PS_DIR . 'filter.post.positioned.class.php');

class LayoutEngineDefault extends LayoutEngine {
	function process( &$box, &$media, &$driver, &$context ) {
		// Calculate the size of text boxes
		if ( is_null( $box->reflow_text( $driver ) ) ) {
			error_log( "LayoutEngineDefault::process: reflow_text call failed" );

			return null;
		};

		// Explicitly remove any height declarations from the BODY-generated box;
		// BODY should always fill last page completely. Percentage height of the BODY is meaningless
		// on the paged media.
		$box->_height_constraint = new HCConstraint( null, null, null );

		$margin = $box->get_css_property( CSS_MARGIN );
		$margin->calcPercentages( mm2pt( $media->width() - $media->margins['left'] - $media->margins['right'] ) );
		$box->setCSSProperty( CSS_MARGIN, $margin );

		$box->width = mm2pt( $media->width() - $media->margins['left'] - $media->margins['right'] ) -
		              $box->_get_hor_extra();
		$box->setCSSProperty( CSS_WIDTH, new WCConstant( $box->width ) );

		$box->height = mm2pt( $media->real_height() ) - $box->_get_vert_extra();

		$box->put_top( mm2pt( $media->height() -
		                      $media->margins['top'] ) -
		               $box->get_extra_top() );

		$box->put_left( mm2pt( $media->margins['left'] ) +
		                $box->get_extra_left() );


		$flag            = false;
		$whitespace_flag = false;
		$box->reflow_whitespace( $flag, $whitespace_flag );

		$box->pre_reflow_images();

		$viewport         = new FlowViewport();
		$viewport->left   = mm2pt( $media->margins['left'] );
		$viewport->top    = mm2pt( $media->height() - $media->margins['top'] );
		$viewport->width  = mm2pt( $media->width() - $media->margins['left'] - $media->margins['right'] );
		$viewport->height = mm2pt( $media->height() - $media->margins['top'] - $media->margins['bottom'] );

		$fake_parent = null;
		$context->push_viewport( $viewport );

		$box->reflow( $fake_parent, $context );

		// Make the top-level box competely fill the last page
		$page_real_height = mm2pt( $media->real_height() );

		// Note we cannot have less than 1 page in our doc; max() call
		// is required as we, in general, CAN have the content height strictly equal to 0.
		// In this case wi still render the very first page
		$pages = max( 1, ceil( $box->get_full_height() / $page_real_height ) );

		/**
		 * Set body box height so it will fit the page exactly
		 */
		$box->height = $pages * $page_real_height - $box->_get_vert_extra();

		$driver->set_expected_pages( $pages );

		/**
		 * Flow absolute-positioned boxes;
		 * note that we should know the number of expected pages at this moment, unless
		 * we will not be able to calculate positions for elements using 'bottom: ...' CSS property
		 */
		for ( $i = 0, $num_positioned = count( $context->absolute_positioned ); $i < $num_positioned; $i ++ ) {
			$context->push();
			$context->absolute_positioned[ $i ]->reflow_absolute( $context );
			$context->pop();
		};

		// Flow fixed-positioned box
		for ( $i = 0, $num_positioned = count( $context->fixed_positioned ); $i < $num_positioned; $i ++ ) {
			$context->push();
			$context->fixed_positioned[ $i ]->reflow_fixed( $context );
			$context->pop();
		};

		$box->reflow_inline();

		return true;
	}
}


//require_once(HTML2PS_DIR . 'utils_units.php');

function cmp_footnote_locations( $a, $b ) {
	if ( $a->get_location() == $b->get_location() ) {
		return 0;
	};

	return ( $a->get_location() > $b->get_location() ) ? - 1 : 1;
}

class FootnoteLocation {
	var $_location;
	var $_content_height;

	function FootnoteLocation( $location, $content_height ) {
		$this->_location       = $location;
		$this->_content_height = $content_height;
	}

	function get_location() {
		return $this->_location;
	}

	function get_content_height() {
		return $this->_content_height;
	}
}

function cmp_page_break_locations( $a, $b ) {
	if ( $a->location == $b->location ) {
		return 0;
	};

	return ( $a->location > $b->location ) ? - 1 : 1;
}

class PageBreakLocation {
	var $location;
	var $penalty;

	function PageBreakLocation( $location, $penalty ) {
		$this->location = round( $location, 2 );
		$this->penalty  = $penalty;
	}

	function get_footnotes_height( $footnotes, $page_start, $location ) {
		$i    = 0;
		$size = count( $footnotes );

		$height = 0;

		while ( $i < $size && $footnotes[ $i ]->get_location() > $page_start ) {
			$i ++;
		};

		$footnotes_count = 0;
		while ( $i < $size && $footnotes[ $i ]->get_location() > $location ) {
			$height += $footnotes[ $i ]->get_content_height();
			$footnotes_count ++;
			$i ++;
		};

		if ( $footnotes_count > 0 ) {
			return
				$height +
				FOOTNOTE_LINE_TOP_GAP +
				FOOTNOTE_LINE_BOTTOM_GAP +
				FOOTNOTE_GAP * ( $footnotes_count - 1 );
		} else {
			return 0;
		};
	}

	function get_penalty( $page_start, $max_page_height, $footnotes ) {
		$height_penalty = $this->get_page_break_height_penalty( $page_start,
			$max_page_height - $this->get_footnotes_height( $footnotes,
				$page_start,
				$this->location ) );

		return $this->penalty + $height_penalty;
	}

	/**
	 * We should avoid page breaks  resulting in too much white space at
	 * the  page  bottom.  This  function  calculates  a  'penalty'  for
	 * breaking page at its current height.
	 */
	function get_page_break_height_penalty( $page_start, $max_page_height ) {
		$current_height = $page_start - $this->location;

		if ( $current_height > $max_page_height ) {
			return MAX_PAGE_BREAK_PENALTY;
		};

		$free_space          = $max_page_height - $current_height;
		$free_space_fraction = $free_space / $max_page_height;

		if ( $free_space_fraction < MAX_UNPENALIZED_FREE_FRACTION ) {
			return 0;
		};

		if ( $free_space_fraction > MAX_FREE_FRACTION ) {
			return MAX_PAGE_BREAK_PENALTY;
		};

		return
			( $free_space_fraction - MAX_UNPENALIZED_FREE_FRACTION ) /
			( MAX_FREE_FRACTION - MAX_UNPENALIZED_FREE_FRACTION ) *
			MAX_PAGE_BREAK_HEIGHT_PENALTY;
	}
}

/**
 * Note that, according to CSS 2.1:
 *
 * A potential page break  location is typically under the influence
 * of  the   parent  element's  'page-break-inside'   property,  the
 * 'page-break-after'  property of  the preceding  element,  and the
 * 'page-break-before' property of the following element. When these
 * properties have  values other  than 'auto', the  values 'always',
 * 'left', and 'right' take precedence over 'avoid'.
 *
 * AND
 *
 * A conforming user agent may interpret the values 'left' and 'right'
 * as 'always'.
 *
 * AND
 *
 * In the normal flow, page breaks can occur at the following places:
 *
 * 1. In the vertical margin between block boxes. When a page break occurs here, the used values of the relevant 'margin-top' and 'margin-bottom' properties are set to '0'.
 * 2. Between line boxes inside a block box.
 */
class PageBreakLocator {
	function get_break_locations( &$dom_tree ) {
		$locations_ungrouped = PageBreakLocator::get_pages_traverse( $dom_tree, 0 );

		/**
		 * If there's no page break locations (e.g. document is empty)
		 * generate one full-size page
		 */
		if ( count( $locations_ungrouped ) == 0 ) {
			return array();
		};

		return PageBreakLocator::sort_locations( $locations_ungrouped );
	}

	function get_footnotes_traverse( &$box ) {
		$footnotes = array();

		if ( is_a( $box, 'BoxNoteCall' ) ) {
			$footnotes[] = new FootnoteLocation( $box->get_top_margin(), $box->_note_content->get_full_height() );
		} elseif ( is_a( $box, 'GenericContainerBox' ) ) {
			foreach ( $box->content as $child ) {
				$footnotes = array_merge( $footnotes, PageBreakLocator::get_footnotes_traverse( $child ) );
			};
		};

		return $footnotes;
	}

	function get_pages( &$dom_tree, $max_page_height, $first_page_top ) {
		$current_page_top = $first_page_top;
		$heights          = array();

		/**
		 * Get list of footnotes and heights of footnote content blocks
		 */
		$footnotes = PageBreakLocator::get_footnotes_traverse( $dom_tree );
		usort( $footnotes, 'cmp_footnote_locations' );

		$locations = PageBreakLocator::get_break_locations( $dom_tree );

		if ( count( $locations ) == 0 ) {
			return array( $max_page_height );
		};

		$best_location = null;
		foreach ( $locations as $location ) {
			if ( $location->location < $current_page_top ) {
				if ( is_null( $best_location ) ) {
					$best_location = $location;
				};

				$current_pos           = round_units( $current_page_top - $location->location );
				$available_page_height = round_units( $max_page_height - $location->get_footnotes_height( $footnotes, $current_page_top, $location->location ) );

				if ( $current_pos > $available_page_height ) {
					/**
					 * No more locations found on current page
					 */

					$best_location_penalty = $best_location->get_penalty( $current_page_top, $max_page_height, $footnotes );
					if ( $best_location_penalty >= MAX_PAGE_BREAK_PENALTY ) {
						error_log( 'Could not find good page break location' );
						$heights[]        = $max_page_height;
						$current_page_top -= $max_page_height;
						$best_location    = null;
					} else {
						$heights[]        = $current_page_top - $best_location->location;
						$current_page_top = $best_location->location;
						$best_location    = null;
					};

				} else {
					$location_penalty = $location->get_penalty( $current_page_top, $max_page_height, $footnotes );
					$best_penalty     = $best_location->get_penalty( $current_page_top, $max_page_height, $footnotes );

					if ( $location_penalty <= $best_penalty ) {
						/**
						 * Better page break location found on current page
						 */
						$best_location = $location;
					};
				};

				if ( $location->penalty < 0 ) { // Forced page break
					$heights[]        = $current_page_top - $location->location;
					$current_page_top = $location->location;
					$best_location    = null;
				};
			};
		};

		// Last page always will have maximal height
		$heights[] = $max_page_height;

		return $heights;
	}

	function is_forced_page_break( $value ) {
		return
			$value == PAGE_BREAK_ALWAYS ||
			$value == PAGE_BREAK_LEFT ||
			$value == PAGE_BREAK_RIGHT;
	}

	function has_forced_page_break_before( &$box ) {
		return PageBreakLocator::is_forced_page_break( $box->get_css_property( CSS_PAGE_BREAK_BEFORE ) );
	}

	function has_forced_page_break_after( &$box ) {
		return PageBreakLocator::is_forced_page_break( $box->get_css_property( CSS_PAGE_BREAK_AFTER ) );
	}

	function get_pages_traverse_block( &$box, &$next, &$previous, $penalty ) {
		$locations = array();

		// Absolute/fixed positioned blocks do not cause page breaks
		// (CSS 2.1. 13.2.3 Content outside the page box)
		$position = $box->get_css_property( CSS_POSITION );
		if ( $position == POSITION_FIXED || $position == POSITION_ABSOLUTE ) {
			return $locations;
		};

		// Fake cell boxes do not generate page break locations
		if ( is_a( $box, 'FakeTableCellBox' ) ) {
			return $locations;
		}

		/**
		 * Check for breaks in block box vertical margin
		 */

		/**
		 * Check for pre-breaks
		 */
		if ( PageBreakLocator::has_forced_page_break_before( $box ) ) {
			$location = new PageBreakLocation( $box->get_top_margin(), FORCED_PAGE_BREAK_BONUS );
		} elseif ( ! is_null( $previous ) && $previous->get_css_property( CSS_PAGE_BREAK_AFTER ) == PAGE_BREAK_AVOID ) {
			$location = new PageBreakLocation( $box->get_top_margin(), $penalty + PAGE_BREAK_AFTER_AVOID_PENALTY );
		} elseif ( $box->get_css_property( CSS_PAGE_BREAK_BEFORE ) == PAGE_BREAK_AVOID ) {
			$location = new PageBreakLocation( $box->get_top_margin(), $penalty + PAGE_BREAK_BEFORE_AVOID_PENALTY );
		} else {
			$location = new PageBreakLocation( $box->get_top_margin(), $penalty );
		};
		$locations[] = $location;

		/**
		 * Check for post-breaks
		 */
		if ( PageBreakLocator::has_forced_page_break_after( $box ) ) {
			$location = new PageBreakLocation( $box->get_bottom_margin(), FORCED_PAGE_BREAK_BONUS );
		} elseif ( ! is_null( $next ) && $next->get_css_property( CSS_PAGE_BREAK_BEFORE ) == PAGE_BREAK_AVOID ) {
			$location = new PageBreakLocation( $box->get_bottom_margin(), $penalty + PAGE_BREAK_AFTER_AVOID_PENALTY );
		} elseif ( $box->get_css_property( CSS_PAGE_BREAK_AFTER ) == PAGE_BREAK_AVOID ) {
			$location = new PageBreakLocation( $box->get_bottom_margin(), $penalty + PAGE_BREAK_AFTER_AVOID_PENALTY );
		} else {
			$location = new PageBreakLocation( $box->get_bottom_margin(), $penalty );
		}
		$locations[] = $location;

		/**
		 * Check for breaks inside this box
		 * Note that this check should be done after page-break-before/after checks,
		 * as 'penalty' value may be modified here
		 */
		if ( $box->get_css_property( CSS_PAGE_BREAK_INSIDE ) == PAGE_BREAK_AVOID ) {
			$penalty += PAGE_BREAK_INSIDE_AVOID_PENALTY;
		};

		/**
		 * According to CSS 2.1, 13.3.5 'Best' page breaks,
		 * User agent shoud /Avoid breaking inside a block that has a border/
		 *
		 * From my point of view, top and bottom borders should not affect page
		 * breaks (as they're not broken by page break), while left and right ones - should.
		 */
		$border_left  =& $box->get_css_property( CSS_BORDER_LEFT );
		$border_right =& $box->get_css_property( CSS_BORDER_RIGHT );

		$has_left_border  = $border_left->style != BS_NONE && $border_left->width->getPoints() > 0;
		$has_right_border = $border_left->style != BS_NONE && $border_left->width->getPoints() > 0;

		if ( $has_left_border || $has_right_border ) {
			$penalty += PAGE_BREAK_BORDER_PENALTY;
		};

		/**
		 * Process box content
		 */
		$locations = array_merge( $locations, PageBreakLocator::get_pages_traverse( $box, $penalty ) );

		return $locations;
	}

	function get_more_before( $base, $content, $size ) {
		$i           = $base;
		$more_before = 0;

		while ( $i > 0 ) {
			$i --;
			if ( is_a( $content[ $i ], 'InlineBox' ) ) {
				$more_before += $content[ $i ]->get_line_box_count();
			} elseif ( is_a( $content[ $i ], 'BRBox' ) ||
			           is_a( $content[ $i ], 'GenericInlineBox' ) ) {
				// Do nothing
			} else {
				return $more_before;
			};
		};

		return $more_before;
	}

	function get_more_after( $base, $content, $size ) {
		$i    = $base;
		$more = 0;

		while ( $i < $size - 1 ) {
			$i ++;
			if ( is_a( $content[ $i ], 'InlineBox' ) ) {
				$more += $content[ $i ]->getLineBoxCount();
			} elseif ( is_a( $content[ $i ], 'BRBox' ) ||
			           is_a( $content[ $i ], 'GenericInlineBox' ) ) {
				// Do nothing
			} else {
				return $more;
			};
		};

		return $more;
	}

	function get_pages_traverse_table_row( &$box, $penalty ) {
		$locations = array();

		$cells = $box->getChildNodes();

		// Find first non-fake (not covered by a table row or cell span) cell
		$i    = 0;
		$size = count( $cells );
		while ( $i < $size &&
		        $cells[ $i ]->is_fake() ) {
			$i ++;
		};
		// Now $i contains the index of the first content cell or $size of there was no one
		if ( $i < $size ) {
			$locations[] = new PageBreakLocation( $cells[ $i ]->get_top_margin(), $penalty );
			$locations[] = new PageBreakLocation( $cells[ $i ]->get_bottom_margin(), $penalty );
		};

		$content_watermark = $cells[0]->get_top_margin() - $cells[0]->get_real_full_height();

		/**
		 * Process row content
		 */
		$inside_penalty = $penalty;
		if ( $box->get_css_property( CSS_PAGE_BREAK_INSIDE ) == PAGE_BREAK_AVOID ) {
			$inside_penalty += PAGE_BREAK_INSIDE_AVOID_PENALTY;
		};

		$cells                   = $box->getChildNodes();
		$null                    = null;
		$ungrouped_row_locations = PageBreakLocator::get_pages_traverse_block( $cells[0],
			$null,
			$null,
			$inside_penalty );
		$row_locations           = PageBreakLocator::sort_locations( $ungrouped_row_locations );

		for ( $i = 1, $size = count( $cells ); $i < $size; $i ++ ) {
			$ungrouped_child_locations = PageBreakLocator::get_pages_traverse_block( $cells[ $i ],
				$null,
				$null,
				$inside_penalty );
			$child_locations           = PageBreakLocator::sort_locations( $ungrouped_child_locations );

			$current_cell_content_watermark = $cells[ $i ]->get_top_margin() - $cells[ $i ]->get_real_full_height();

			$new_row_locations = array();

			// Keep only locations available in all cells

			$current_row_location_index = 0;
			while ( $current_row_location_index < count( $row_locations ) ) {
				$current_row_location = $row_locations[ $current_row_location_index ];

				// Check if current row-wide location is below the current cell content;
				// in this case, accept it immediately
				if ( $current_row_location->location < $current_cell_content_watermark ) {
					$new_row_locations[] = $current_row_location;
				} else {
					// Match all row locations agains the current cell's
					for (
						$current_child_location_index = 0, $child_locations_total = count( $child_locations );
						$current_child_location_index < $child_locations_total;
						$current_child_location_index ++
					) {
						$current_child_location = $child_locations[ $current_child_location_index ];
						if ( $current_child_location->location == $current_row_location->location ) {
							$new_row_locations[] = new PageBreakLocation( $current_child_location->location,
								max( $current_child_location->penalty,
									$current_row_location->penalty ) );
						};
					};
				};

				$current_row_location_index ++;
			};

			// Add locations available below content in previous cells

			for (
				$current_child_location_index = 0, $child_locations_total = count( $child_locations );
				$current_child_location_index < $child_locations_total;
				$current_child_location_index ++
			) {
				$current_child_location = $child_locations[ $current_child_location_index ];
				if ( $current_child_location->location < $content_watermark ) {
					$new_row_locations[] = new PageBreakLocation( $current_child_location->location,
						$current_child_location->penalty );
				};
			};

			$content_watermark = min( $content_watermark, $cells[ $i ]->get_top_margin() - $cells[ $i ]->get_real_full_height() );

			$row_locations = $new_row_locations;
		};

		$locations = array_merge( $locations, $row_locations );

		return $locations;
	}

	function get_pages_traverse_inline( &$box, $penalty, $more_before, $more_after ) {
		$locations = array();

		/**
		 * Check for breaks between line boxes
		 */

		$size = $box->get_line_box_count();

		if ( $size == 0 ) {
			return $locations;
		};

		// If there was  a BR box before current  inline box (indicated by
		// $more_before parameter > 0), we  may break page on the top edge
		// of the first line box
		if ( $more_before > 0 ) {
			if ( $more_before < $box->parent->get_css_property( CSS_ORPHANS ) ) {
				$orphans_penalty = PAGE_BREAK_ORPHANS_PENALTY;
			} else {
				$orphans_penalty = 0;
			};

			if ( $box->parent->get_css_property( CSS_WIDOWS ) > $size + $more_after ) {
				$widows_penalty = PAGE_BREAK_WIDOWS_PENALTY;
			} else {
				$widows_penalty = 0;
			};

			$line_box    = $box->get_line_box( 0 );
			$locations[] = new PageBreakLocation( $line_box->top,
				$penalty + PAGE_BREAK_LINE_PENALTY + $orphans_penalty + $widows_penalty );
		};

		// If there  was a BR box  after current inline  box (indicated by
		// $more_after parameter >  0), we may break page  on the top edge
		// of the first line box
		if ( $more_after > 0 ) {
			if ( $size + 1 + $more_before < $box->parent->get_css_property( CSS_ORPHANS ) ) {
				$orphans_penalty = PAGE_BREAK_ORPHANS_PENALTY;
			} else {
				$orphans_penalty = 0;
			};

			if ( $size + 1 + $box->parent->get_css_property( CSS_WIDOWS ) > $size + $more_after ) {
				$widows_penalty = PAGE_BREAK_WIDOWS_PENALTY;
			} else {
				$widows_penalty = 0;
			};

			$line_box    = $box->getLineBox( $size - 1 );
			$locations[] = new PageBreakLocation( $line_box->bottom,
				$penalty + PAGE_BREAK_LINE_PENALTY + $orphans_penalty + $widows_penalty );
		};

		// Note that we're  ignoring the last line box  inside this inline
		// box; it is required, as bottom of the last line box will be the
		// same as  the bottom of  the container block box.  Break penalty
		// should be calculated using block-box level data
		for ( $i = 0; $i < $size - 1; $i ++ ) {
			$line_box = $box->get_line_box( $i );

			if ( $i + 1 + $more_before < $box->parent->get_css_property( CSS_ORPHANS ) ) {
				$orphans_penalty = PAGE_BREAK_ORPHANS_PENALTY;
			} else {
				$orphans_penalty = 0;
			};

			if ( $i + 1 + $box->parent->get_css_property( CSS_WIDOWS ) > $size + $more_after ) {
				$widows_penalty = PAGE_BREAK_WIDOWS_PENALTY;
			} else {
				$widows_penalty = 0;
			};

			$locations[] = new PageBreakLocation( $line_box->bottom,
				$penalty + PAGE_BREAK_LINE_PENALTY + $orphans_penalty + $widows_penalty );
		};

		return $locations;
	}

	function &get_previous( $index, $content, $size ) {
		for ( $i = $index - 1; $i >= 0; $i -- ) {
			$child = $content[ $i ];
			if ( ! $child->is_null() ) {
				return $child;
			};
		};

		$dummy = null;

		return $dummy;
	}

	function &get_next( $index, &$content, $size ) {
		for ( $i = $index + 1; $i < $size; $i ++ ) {
			$child =& $content[ $i ];
			if ( ! $child->is_null() ) {
				return $child;
			};
		};

		$dummy = null;

		return $dummy;
	}

	function get_pages_traverse( &$box, $penalty ) {
		if ( ! is_a( $box, 'GenericContainerBox' ) ) {
			return array();
		};

		$locations = array();

		for ( $i = 0, $content_size = count( $box->content ); $i < $content_size; $i ++ ) {
			$previous_child =& PageBreakLocator::get_previous( $i, $box->content, $content_size );
			$next_child     =& PageBreakLocator::get_next( $i, $box->content, $content_size );
			$child          =& $box->content[ $i ];

			/**
			 * Note that page-break-xxx properties apply to block-level elements only
			 */
			if ( is_a( $child, 'BRBox' ) ) {
				// Do nothing
			} elseif ( $child->isBlockLevel() ) {
				$locations = array_merge( $locations, PageBreakLocator::get_pages_traverse_block( $child,
					$next_child,
					$previous_child,
					$penalty ) );

			} elseif ( is_a( $child, 'TableCellBox' ) ) {
				$null            = null;
				$child_locations = PageBreakLocator::get_pages_traverse_block( $child, $null, $null, $penalty );
				$locations       = array_merge( $locations, $child_locations );
			} elseif ( is_a( $child, 'InlineBox' ) ) {
				$more_before = 0;
				$more_after  = 0;

				if ( is_a( $previous_child, 'BRBox' ) ) {
					$more_before = PageBreakLocator::get_more_before( $i, $box->content, $content_size );
				};

				if ( is_a( $next_child, 'BRBox' ) ) {
					$more_after = PageBreakLocator::get_more_after( $i, $box->content, $content_size );
				};

				$locations = array_merge( $locations, PageBreakLocator::get_pages_traverse_inline( $child, $penalty, $more_before, $more_after ) );
			} elseif ( is_a( $child, 'TableRowBox' ) ) {
				$locations = array_merge( $locations, PageBreakLocator::get_pages_traverse_table_row( $child, $penalty ) );
			};
		};

		return $locations;
	}

	function sort_locations( $locations_ungrouped ) {
		if ( count( $locations_ungrouped ) == 0 ) {
			return array();
		};

		usort( $locations_ungrouped, 'cmp_page_break_locations' );

		$last_location = $locations_ungrouped[0];
		$locations     = array();
		foreach ( $locations_ungrouped as $location ) {
			if ( $last_location->location != $location->location ) {
				$locations[]   = $last_location;
				$last_location = $location;
			} else {
				if ( $last_location->penalty >= 0 && $location->penalty >= 0 ) {
					$last_location->penalty = max( $last_location->penalty, $location->penalty );
				} else {
					$last_location->penalty = min( $last_location->penalty, $location->penalty );
				};
			};
		};
		$locations[] = $last_location;

		return $locations;
	}
}

class PostTreeFilter {
	function process( &$tree ) {
		die( "Oops. Inoverridden 'process' method called in " . get_class( $this ) );
	}
}


class PostTreeFilterPositioned extends PreTreeFilter {
	var $_context;

	function PostTreeFilterPositioned( &$context ) {
		$this->_context =& $context;
	}

	function process( &$tree, $data, &$pipeline ) {
		if ( is_a( $tree, 'GenericContainerBox' ) ) {
			for ( $i = 0; $i < count( $tree->content ); $i ++ ) {
				$position = $tree->content[ $i ]->get_css_property( CSS_POSITION );
				$float    = $tree->content[ $i ]->get_css_property( CSS_FLOAT );

				if ( $position == POSITION_ABSOLUTE ) {
					$this->_context->add_absolute_positioned( $tree->content[ $i ] );
				} elseif ( $position == POSITION_FIXED ) {
					$this->_context->add_fixed_positioned( $tree->content[ $i ] );
				};

				$this->process( $tree->content[ $i ], $data, $pipeline );
			};
		};

		return true;
	}
}


class PostTreeFilterPostponed extends PreTreeFilter {
	var $_driver;

	function PostTreeFilterPostponed( &$driver ) {
		$this->_driver =& $driver;
	}

	function process( &$tree, $data, &$pipeline ) {
		if ( is_a( $tree, 'GenericContainerBox' ) ) {
			for ( $i = 0; $i < count( $tree->content ); $i ++ ) {
				$position = $tree->content[ $i ]->get_css_property( CSS_POSITION );
				$float    = $tree->content[ $i ]->get_css_property( CSS_FLOAT );

				if ( $position == POSITION_RELATIVE ) {
					$this->_driver->postpone( $tree->content[ $i ] );
				} elseif ( $float != FLOAT_NONE ) {
					$this->_driver->postpone( $tree->content[ $i ] );
				};

				$this->process( $tree->content[ $i ], $data, $pipeline );
			};
		};

		return true;
	}
}

class OutputFilter {
	function content_type() {
		die( "Unoverridden 'content_type' method called in " . get_class( $this ) );
	}

	function process( $tmp_filename ) {
		die( "Unoverridden 'process' method called in " . get_class( $this ) );
	}
}


function safe_exec( $cmd, &$output ) {
	exec( $cmd, $output, $result );

	if ( $result ) {
		$message = "";

		if ( count( $output ) > 0 ) {
			$message .= "Error executing '{$cmd}'<br/>\n";
			error_log( "Error executing '{$cmd}'." );
			$message .= "Command produced the following output:<br/>\n";
			error_log( "Command produced the following output:" );

			foreach ( $output as $line ) {
				$message .= "{$line}<br/>\n";
				error_log( $line );
			};
		} else {
			$_cmd = $cmd;
			include( HTML2PS_DIR . 'templates/error_exec.tpl' );
			error_log( "Error executing '{$cmd}'. Command produced no output." );
			die( "HTML2PS Error" );
		};
		die( $message );
	};
}

class OutputFilterPS2PDF extends OutputFilter {
	var $pdf_version;

	function content_type() {
		return ContentType::pdf();
	}

	function _mk_cmd( $filename ) {
		return GS_PATH . " -dNOPAUSE -dBATCH -dEmbedAllFonts=true -dCompatibilityLevel=" . $this->pdf_version . " -sDEVICE=pdfwrite -sOutputFile=" . $filename . ".pdf " . $filename;
	}

	function OutputFilterPS2PDF( $pdf_version ) {
		$this->pdf_version = $pdf_version;
	}

	function process( $tmp_filename ) {
		$pdf_file = $tmp_filename . '.pdf';
		safe_exec( $this->_mk_cmd( $tmp_filename ), $output );
		unlink( $tmp_filename );

		return $pdf_file;
	}
}


class OutputFilterGZip extends OutputFilter {
	function content_type() {
		return null;
		//    return ContentType::gz();
	}

	function process( $tmp_filename ) {
		$output_file = $tmp_filename . '.gz';

		$file = gzopen( $output_file, "wb" );
		gzwrite( $file, file_get_contents( $tmp_filename ) );
		gzclose( $file );

		unlink( $tmp_filename );

		return $output_file;
	}
}

class Destination {
	var $filename;

	function Destination( $filename ) {
		$this->set_filename( $filename );
	}

	function filename_escape( $filename ) {
		return preg_replace( "/[^a-z0-9-]/i", "_", $filename );
	}

	function get_filename() {
		return empty( $this->filename ) ? OUTPUT_DEFAULT_NAME : $this->filename;
	}

	function process( $filename, $content_type ) {
		die( "Oops. Inoverridden 'process' method called in " . get_class( $this ) );
	}

	function set_filename( $filename ) {
		$this->filename = $filename;
	}
}

class DestinationHTTP extends Destination {
	function DestinationHTTP( $filename ) {
		$this->Destination( $filename );
	}

	function headers( $content_type ) {
		die( "Unoverridden 'header' method called in " . get_class( $this ) );
	}

	function process( $tmp_filename, $content_type ) {
		header( "Content-Type: " . $content_type->mime_type );

		$headers = $this->headers( $content_type );
		foreach ( $headers as $header ) {
			header( $header );
		};

		// NOTE: readfile does not work well with some Windows machines
		// echo(file_get_contents($tmp_filename));
		readfile( $tmp_filename );
	}
}

class DestinationBrowser extends DestinationHTTP {
	function headers( $content_type ) {
		return array(
			"Content-Disposition: inline; filename=" . $this->filename_escape( $this->get_filename() ) . "." . $content_type->default_extension,
			"Content-Transfer-Encoding: binary",
			"Cache-Control: private"
		);
	}
}

class DestinationDownload extends DestinationHTTP {
	function DestinationDownload( $filename ) {
		$this->DestinationHTTP( $filename );
	}

	function headers( $content_type ) {
		return array(
			"Content-Disposition: attachment; filename=" . $this->filename_escape( $this->get_filename() ) . "." . $content_type->default_extension,
			"Content-Transfer-Encoding: binary",
			"Cache-Control: must-revalidate, post-check=0, pre-check=0",
			"Pragma: public"
		);
	}
}

class DestinationFile extends Destination {
	var $_link_text;

	function DestinationFile( $filename, $link_text = null ) {
		$this->Destination( $filename );

		$this->_link_text = $link_text;
	}

	function process( $tmp_filename, $content_type ) {
		$dest_filename = OUTPUT_FILE_DIRECTORY . $this->filename_escape( $this->get_filename() ) . "." . $content_type->default_extension;

		copy( $tmp_filename, $dest_filename );

		$text = $this->_link_text;
		$text = preg_replace( '/%link%/', 'file://' . $dest_filename, $text );
		$text = preg_replace( '/%name%/', $this->get_filename(), $text );
		print $text;
	}
}

class HTML2PS_XMLUtils {
	function valid_attribute_name( $name ) {
		// Note that, technically, it is not correct, as XML standard treats as letters
		// characters other than a-z too.. Nevertheless, this simple variant
		// will do for XHTML/HTML

		return preg_match( "/[a-z_:][a-z0-9._:.]*/i", $name );
	}
}

class ContentType {
	var $default_extension;
	var $mime_type;

	function ContentType( $extension, $mime ) {
		$this->default_extension = $extension;
		$this->mime_type         = $mime;
	}

	function png() {
		return new ContentType( 'png', 'image/png' );
	}

	function gz() {
		return new ContentType( 'gz', 'application/gzip' );
	}

	function pdf() {
		return new ContentType( 'pdf', 'application/pdf' );
	}

	function ps() {
		return new ContentType( 'ps', 'application/postscript' );
	}
}


class Dispatcher {
	var $_callbacks;

	function Dispatcher() {
		$this->_callbacks = array();
	}

	/**
	 * @param String $type name of the event to dispatch
	 */
	function add_event( $type ) {
		$this->_callbacks[ $type ] = array();
	}

	function add_observer( $type, $callback ) {
		$this->_check_event_type( $type );
		$this->_callbacks[ $type ][] = $callback;
	}

	function fire( $type, $params ) {
		$this->_check_event_type( $type );

		foreach ( $this->_callbacks[ $type ] as $callback ) {
			call_user_func( $callback, $params );
		};
	}

	function _check_event_type( $type ) {
		if ( ! isset( $this->_callbacks[ $type ] ) ) {
			die( sprintf( "Invalid event type: %s", $type ) );
		};
	}
}


class Observer {
	function run( $params ) {
		// By default, do nothing
	}
}


class StrategyPageBreakSimple {
	function StrategyPageBreakSimple() {
	}

	function run( &$pipeline, &$media, &$box ) {
		$num_pages    = ceil( $box->get_height() / mm2pt( $media->real_height() ) );
		$page_heights = array();
		for ( $i = 0; $i < $num_pages; $i ++ ) {
			$page_heights[] = mm2pt( $media->real_height() );
		};

		return $page_heights;
	}
}


class StrategyPageBreakSmart {
	function StrategyPageBreakSmart() {
	}

	function run( &$pipeline, &$media, &$box ) {
		$page_heights = PageBreakLocator::get_pages( $box,
			mm2pt( $media->real_height() ),
			mm2pt( $media->height() - $media->margins['top'] ) );

		return $page_heights;
	}
}


class StrategyLinkRenderingNormal {
	function StrategyLinkRenderingNormal() {
	}

	function apply( &$box, &$driver ) {
		$link_target = $box->get_css_property( CSS_HTML2PS_LINK_TARGET );

		if ( CSSPseudoLinkTarget::is_external_link( $link_target ) ) {
			$driver->add_link( $box->get_left(),
				$box->get_top(),
				$box->get_width(),
				$box->get_height(),
				$link_target );
		} elseif ( CSSPseudoLinkTarget::is_local_link( $link_target ) ) {
			if ( isset( $driver->anchors[ substr( $link_target, 1 ) ] ) ) {
				$anchor = $driver->anchors[ substr( $link_target, 1 ) ];
				$driver->add_local_link( $box->get_left(),
					$box->get_top(),
					$box->get_width(),
					$box->get_height(),
					$anchor );
			};
		};
	}
}


class StrategyPositionAbsolute {
	function StrategyPositionAbsolute() {
	}

	function apply( &$box ) {
		/**
		 * Box having 'position: absolute' are positioned relatively to their "containing blocks".
		 *
		 * @link http://www.w3.org/TR/CSS21/visudet.html#x0 CSS 2.1 Definition of "containing block"
		 */
		$containing_block =& $box->_get_containing_block();

		$this->_positionAbsoluteVertically( $box, $containing_block );
		$this->_positionAbsoluteHorizontally( $box, $containing_block );
	}

	/**
	 * Note that if both top and bottom are 'auto', box will use vertical coordinate
	 * calculated using guess_corder in 'reflow' method which could be used if this
	 * box had 'position: static'
	 */
	function _positionAbsoluteVertically( &$box, &$containing_block ) {
		$bottom = $box->get_css_property( CSS_BOTTOM );
		$top    = $box->get_css_property( CSS_TOP );

		if ( ! $top->isAuto() ) {
			if ( $top->isPercentage() ) {
				$top_value = ( $containing_block['top'] - $containing_block['bottom'] ) / 100 * $top->getPercentage();
			} else {
				$top_value = $top->getPoints();
			};
			$box->put_top( $containing_block['top'] - $top_value - $box->get_extra_top() );
		} elseif ( ! $bottom->isAuto() ) {
			if ( $bottom->isPercentage() ) {
				$bottom_value = ( $containing_block['top'] - $containing_block['bottom'] ) / 100 * $bottom->getPercentage();
			} else {
				$bottom_value = $bottom->getPoints();
			};
			$box->put_top( $containing_block['bottom'] + $bottom_value + $box->get_extra_bottom() + $box->get_height() );
		};

		//     $bottom = $box->get_css_property(CSS_BOTTOM);
		//     $top    = $box->get_css_property(CSS_TOP);
		//     if ($top->isAuto() && !$bottom->isAuto()) {
		//       $box->offset(0, $box->get_height());
		//     };
	}

	/**
	 * Note that  if both  'left' and 'right'  are 'auto', box  will use
	 * horizontal coordinate  calculated using guess_corder  in 'reflow'
	 * method which could be used if this box had 'position: static'
	 */
	function _positionAbsoluteHorizontally( &$box, &$containing_block ) {
		$left  = $box->get_css_property( CSS_LEFT );
		$right = $box->get_css_property( CSS_RIGHT );

		if ( ! $left->isAuto() ) {
			if ( $left->isPercentage() ) {
				$left_value = ( $containing_block['right'] - $containing_block['left'] ) / 100 * $left->getPercentage();
			} else {
				$left_value = $left->getPoints();
			};
			$box->put_left( $containing_block['left'] + $left_value + $box->get_extra_left() );
		} elseif ( ! $right->isAuto() ) {
			if ( $right->isPercentage() ) {
				$right_value = ( $containing_block['right'] - $containing_block['left'] ) / 100 * $right->getPercentage();
			} else {
				$right_value = $right->getPoints();
			};

			$left = $containing_block['right'] - $right_value - $box->get_extra_right() - $box->get_width();
			$box->put_left( $left );
		};

		//     $right = $box->get_css_property(CSS_RIGHT);
		//     $left  = $box->get_css_property(CSS_LEFT);
		//     if ($left->isAuto() && !$right->isAuto()) {
		//       $box->offset(-$box->get_width(), 0);
		//     };
	}
}


class StrategyWidthAbsolutePositioned {
	function StrategyWidthAbsolutePositioned() {
	}

	/**
	 * See also  CSS 2.1,  p 10.3.7 Absolutely  positioned, non-replaced
	 * elements
	 */
	function apply( &$box, &$context ) {
		$containing_block       =& $box->_get_containing_block();
		$containing_block_width = $containing_block['right'] - $containing_block['left'];

		$right =& $box->get_css_property( CSS_RIGHT );
		$left  =& $box->get_css_property( CSS_LEFT );
		$wc    =& $box->get_css_property( CSS_WIDTH );

		// For the purposes of this section and the next, the term "static
		// position" (of  an element) refers, roughly, to  the position an
		// element would have had in the normal flow. More precisely:
		//
		// * The static position for 'left'  is the distance from the left
		//   edge of  the containing  block to the  left margin edge  of a
		//   hypothetical box  that would have  been the first box  of the
		//   element  if its  'position'  property had  been 'static'  and
		//   'float'  had  been  'none'.  The  value is  negative  if  the
		//   hypothetical box is to the left of the containing block.
		//
		// * The  static position  for 'right'  is the  distance  from the
		//   right edge of  the containing block to the  right margin edge
		//   of the same hypothetical box  as above. The value is positive
		//   if  the hypothetical  box is  to the  left of  the containing
		//   block's edge.
		//
		// For  the  purposes  of  calculating the  static  position,  the
		// containing block  of fixed  positioned elements is  the initial
		// containing block  instead of  the viewport, and  all scrollable
		// boxes should be assumed to be scrolled to their origin.
		//
		// @todo: implement
		$static_left  = 0;
		$static_right = 0;

		// Calculation   of  the   shrink-to-fit  width   is   similar  to
		// calculating the width of a table cell using the automatic table
		// layout  algorithm. Roughly:  calculate the  preferred  width by
		// formatting the content without  breaking lines other than where
		// explicit line  breaks occur,  and also calculate  the preferred
		// minimum width,  e.g., by trying  all possible line  breaks. CSS
		// 2.1 does not define the exact algorithm. Thirdly, calculate the
		// available  width: this is  found by  solving for  'width' after
		// setting 'left' (in case 1) or 'right' (in case 3) to 0.
		//
		// Then  the  shrink-to-fit  width is:  min(max(preferred  minimum
		// width, available width), preferred width).
		$preferred_minimum_width = $box->get_preferred_minimum_width( $context );
		$available_width         = $containing_block_width -
		                           ( $left->isAuto() ? 0 : $left->getPoints( $containing_block_width ) ) -
		                           ( $right->isAuto() ? 0 : $right->getPoints( $containing_block_width ) ) -
		                           $box->_get_hor_extra();
		$preferred_width         = $box->get_preferred_width( $context );

		$shrink_to_fit_width = min( max( $preferred_minimum_width,
			$available_width ),
			$preferred_width );

		// The  constraint  that  determines  the used  values  for  these
		// elements is:
		//
		// 'left' + 'margin-left' + 'border-left-width' + 'padding-left' +
		// 'width'    +   'padding-right'    +    'border-right-width'   +
		// 'margin-right' + 'right' + scrollbar  width (if any) = width of
		// containing block

		// If all three of 'left',  'width', and 'right' are 'auto': First
		// set any  'auto' values for 'margin-left'  and 'margin-right' to
		// 0. Then, if the 'direction' property of the containing block is
		// 'ltr' set 'left'  to the static position and  apply rule number
		// three below; otherwise, set  'right' to the static position and
		// apply rule number one below.
		if ( $left->isAuto() && $right->isAuto() && $wc->isNull() ) {
			// @todo: support 'direction' property for the containing block
			$box->setCSSProperty( CSS_LEFT, ValueLeft::fromString( '0' ) );
		};

		// If  none of  the three  is  'auto': If  both 'margin-left'  and
		// 'margin-right' are  'auto', solve the equation  under the extra
		// constraint that  the two margins get equal  values, unless this
		// would make them  negative, in which case when  direction of the
		// containing   block   is   'ltr'  ('rtl'),   set   'margin-left'
		// ('margin-right')   to  zero   and   solve  for   'margin-right'
		// ('margin-left'). If  one of 'margin-left'  or 'margin-right' is
		// 'auto', solve  the equation for  that value. If the  values are
		// over-constrained,  ignore the  value  for 'left'  (in case  the
		// 'direction'  property  of the  containing  block  is 'rtl')  or
		// 'right'  (in case  'direction'  is 'ltr')  and  solve for  that
		// value.
		if ( ! $left->isAuto() && ! $right->isAuto() && ! $wc->isNull() ) {
			// @todo: implement
			$box->put_width( $wc->apply( $box->get_width(),
				$containing_block_width ) );
		};

		// Otherwise,   set   'auto'    values   for   'margin-left'   and
		// 'margin-right'  to 0,  and pick  the one  of the  following six
		// rules that applies.

		// Case  1 ('left'  and  'width'  are 'auto'  and  'right' is  not
		// 'auto', then the width is shrink-to-fit. Then solve for 'left')
		if ( $left->isAuto() && ! $right->isAuto() && $wc->isNull() ) {
			$box->put_width( $shrink_to_fit_width );
		};

		// Case  2 ('left'  and  'right'  are 'auto'  and  'width' is  not
		// 'auto',  then if  the  'direction' property  of the  containing
		// block is 'ltr' set 'left' to the static position, otherwise set
		// 'right'  to the  static  position. Then  solve  for 'left'  (if
		// 'direction is 'rtl') or 'right' (if 'direction' is 'ltr').)
		if ( $left->isAuto() && $right->isAuto() && ! $wc->isNull() ) {
			// @todo: implement 'direction' support
			$box->put_width( $wc->apply( $box->get_width(),
				$containing_block_width ) );
		};

		// Case  3 ('width'  and  'right'  are 'auto'  and  'left' is  not
		// 'auto',  then  the width  is  shrink-to-fit  .  Then solve  for
		// 'right')
		if ( ! $left->isAuto() && $right->isAuto() && $wc->isNull() ) {
			$box->put_width( $shrink_to_fit_width );
		};

		// Case 4 ('left'  is 'auto', 'width' and 'right'  are not 'auto',
		// then solve for 'left')
		if ( $left->isAuto() && ! $right->isAuto() && ! $wc->isNull() ) {
			$box->put_width( $wc->apply( $box->get_width(),
				$containing_block_width ) );
		};

		// Case 5 ('width'  is 'auto', 'left' and 'right'  are not 'auto',
		// then solve for 'width')
		if ( ! $left->isAuto() && ! $right->isAuto() && $wc->isNull() ) {
			$box->put_width( $containing_block_width -
			                 $left->getPoints( $containing_block_width ) -
			                 $right->getPoints( $containing_block_width ) );
		};

		// Case 6 ('right'  is 'auto', 'left' and 'width'  are not 'auto',
		// then solve for 'right')
		if ( ! $left->isAuto() && $right->isAuto() && ! $wc->isNull() ) {
			$box->put_width( $wc->apply( $box->get_width(),
				$containing_block_width ) );
		};

		/**
		 * After this we should remove width constraints or we may encounter problem
		 * in future when we'll try to call get_..._width functions for this box
		 *
		 * @todo Update the family of get_..._width function so that they would apply constraint
		 * using the containing block width, not "real" parent width
		 */
		$box->setCSSProperty( CSS_WIDTH, new WCConstant( $box->get_width() ) );
	}
}


class AutofixUrl {
	function AutofixUrl() {
	}

	function apply( $url ) {
		$parts = @parse_url( $url );
		if ( $parts === false ) {
			return null;
		};

		$path = isset( $parts['path'] ) ? $parts['path'] : '/';

		/*
     * Check if path contains only RFC1738 compliant symbols and fix it
     * No graphic: 00-1F, 7F, 80-FF
     * Unsafe: 'space',<>"#%{}|\^~[]`
     * Reserved: ;/?:@=&
     *
     * Normally, slash is allowed in path part, and % may be a part of encoded character
     */
		$no_graphic_found     = preg_match( '/[\x00-\x1F\x7F\x80-\xFF]/', $path );
		$unsafe_found         = preg_match( '/[ <>\"#{}\|\^~\[\]`]/', $path );
		$unsafe_percent_found = preg_match( '/%[^\dA-F]|%\d[^\dA-F]/i', $path );
		$reserved_found       = preg_match( '/;\?:@=&/', $path );

		if ( $no_graphic_found ||
		     $unsafe_found ||
		     $unsafe_percent_found ||
		     $reserved_found ) {
			$path = join( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );
		};

		// Build updated URL
		$url_fixed = '';

		if ( isset( $parts['scheme'] ) ) {
			$url_fixed .= $parts['scheme'];
			$url_fixed .= '://';

			if ( isset( $parts['user'] ) ) {
				$url_fixed .= $parts['user'];
				if ( isset( $parts['pass'] ) ) {
					$url_fixed .= ':';
					$url_fixed .= $parts['pass'];
				};
				$url_fixed .= '@';
			};

			if ( isset( $parts['host'] ) ) {
				$url_fixed .= $parts['host'];
			};

			if ( isset( $parts['port'] ) ) {
				$url_fixed .= ':';
				$url_fixed .= $parts['port'];
			};
		};

		$url_fixed .= $path;

		if ( isset( $parts['query'] ) ) {
			$url_fixed .= '?';
			$url_fixed .= $parts['query'];
		};

		if ( isset( $parts['fragment'] ) ) {
			$url_fixed .= '#';
			$url_fixed .= $parts['fragment'];
		};

		return $url_fixed;
	}
}


class Fetcher {
	/**
	 * Fetches the data identified by $data_id, wraps it into FetchedData object together with
	 * any auxiliary information (like HTTP response headers, number of redirect, fetched file information
	 * or something else) and returns this object.
	 *
	 * @param String $data_id unique identifier of the data to be fetched (URI, file path, primary key of the database record or something else)
	 *
	 * @return FetchedData object containing the fetched file contents and auxiliary information, if exists.
	 */
	function get_data( $data_id ) {
		die( "Oops. Inoverridden 'get_data' method called in " . get_class( $this ) );
	}

	/**
	 * @return String value of base URL to use for resolving relative links inside the document
	 */
	function get_base_url() {
		die( "Oops. Inoverridden 'get_base_url' method called in " . get_class( $this ) );
	}

	function error_message() {
		die( "Oops. Inoverridden 'error_message' method called in " . get_class( $this ) );
	}
}


class FeatureFactory {
	var $_features;

	function FeatureFactory() {
		$this->_features = array();
	}

	function &get( $name ) {
		$instance =& FeatureFactory::get_instance();

		return $instance->_get( $name );
	}

	function &_get( $name ) {
		if ( ! isset( $this->__features[ $name ] ) ) {
			$this->_features[ $name ] =& $this->_load( $name );
		};

		return $this->_features[ $name ];
	}

	function &_load( $name ) {
		$normalized_name = strtolower( preg_replace( '/[^\w\d\.]/i', '_', $name ) );
		$file_name       = HTML2PS_DIR . 'features/' . $normalized_name . '.php';
		$class_name      = 'Feature' . join( '', array_map( 'ucfirst', explode( '.', $normalized_name ) ) );

		if ( ! file_exists( $file_name ) ) {
			$null = null;

			return $null;
		};

		require_once( $file_name );
		$feature_object =& new $class_name;

		return $feature_object;
	}

	function &get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance =& new FeatureFactory();
		};

		return $instance;
	}
}


<?php


class module_core extends module_base {

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->module_name     = "core";
		$this->module_position = 0;

		$this->version = 2.153;
		//2.153 - 2018-04-01 - core fixes
		//2.152 - 2017-07-26 - bug fixes
		//2.151 - 2017-07-24 - default field improvements
		//2.150 - 2017-05-30 - thousands fix
		//2.149 - 2017-05-25 - invoice prefix for customer fix
		//2.148 - 2017-05-18 - invoice inc defaults
		//2.147 - 2017-05-05 - php5 fix
		//2.146 - 2017-05-02 - file path configuration
		//2.145 - 2017-05-02 - big changes
		//2.144 - 2017-01-15 - invoice inc per year
		//2.143 - 2017-01-12 - invoice_name_match_job fix
		//2.142 - 2017-01-06 - invoice, quote, job inc number
		//2.141 - 2017-01-02 - invoice, quote, job improvements
		//2.140 - 2016-11-29 - core db improvements
		//2.139 - 2016-11-21 - save timer improvements
		//2.138 - 2016-11-06 - fuzzy date calculations
		//2.137 - 2016-09-30 - new core classes
		//2.136 - 2016-08-12 - task wysiwyg fix
		//2.135 - 2016-07-10 - big update to mysqli
		//2.134 - 2015-04-20 - fix for number format
		//2.133 - 2015-03-14 - html check improvement
		//2.132 - 2015-01-07 - rounding bug fix
		//2.131 - 2014-12-22 - decimal rounding improvement
		//2.13 - 2014-10-13 - hook_filter_var core feature
		//2.129 - 2014-08-24 - hours:minutes fix
		//2.128 - 2014-08-20 - tax_decimal_places and tax_trim_decimal
		//2.127 - 2014-07-31 - php 5.3 closure check
		//2.126 - 2014-07-22 - hours:minutes task formatting fix
		//2.125 - 2014-07-16 - translation fixes for success/error messages
		//2.124 - 2014-06-09 - hours:minutes task formatting
		//2.123 - 2014-04-10 - currency cache fix
		//2.122 - 2014-02-12 - number_trim_decimals fix
		//2.121 - 2014-02-06 - number_trim_decimals advanced settings
		//2.12 - 2013-05-08 - fix for static error on some php versions
		//2.11 - 2013-04-27 - fix for number rounding with international currency formats
		//2.1 - 2013-04-21 - initial release

	}

}

/* placeholder module to contain various functions used through out the system */
@include_once 'includes/functions.php'; // so we don't re-create old functions.
include_once 'includes/plugin_core/class.base-multi.php';
include_once 'includes/plugin_core/class.base-single.php';
include_once 'includes/plugin_core/class.base-document.php';


if ( ! function_exists( 'set_message' ) ) {
	function set_message( $message ) {
		if ( ! isset( $_SESSION['_message'] ) ) {
			$_SESSION['_message'] = array();
		}
		$_SESSION['_message'][] = _l( $message );
	}
}
if ( ! function_exists( 'set_error' ) ) {
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
}
if ( ! function_exists( 'number_in' ) ) {
	function number_in( $value, $dec_positions = false ) {
		// convert a number in this format (eg: 1.234,56) to a system compatible format (eg: 1234.56)
		// only modify this number if it isn't already in db friendly format:
		$decimal_separator = module_config::c( 'currency_decimal_separator', '.' );
		$thounds_separator = module_config::c( 'currency_thousand_separator', ',' );
		$dec_positions     = ( $dec_positions === false || $dec_positions == - 1 ) ? (int) module_config::c( 'currency_decimal_places', 2 ) : $dec_positions;
		// fix for comparing floats.
		if ( $thounds_separator && $thounds_separator == '.' && preg_match( '#\d\.\d\d\d#', $value ) ) {
			$value = str_replace( $thounds_separator, '', $value );
		}
		if ( ! is_numeric( $value ) || ! ( abs( (float) $value - (float) @number_format( $value, $dec_positions, '.', '' ) ) < 0.000001 ) ) {
			//            echo "Converting $value into ";
			$value = str_replace( $thounds_separator, '', $value );
			if ( $decimal_separator != '.' ) {
				$value = str_replace( $decimal_separator, '.', $value );
			}
			//            echo "$value <br>\n";
		} else {
			//            echo "Not converting $value <br>\n";
		}

		return $value;
	}
}

if ( ! function_exists( 'number_out' ) ) {
	// converts a real number ( 12.34 ) into a lang specific number ( 12,34 ) for display on the UI
	function number_out( $value, $trim = false, $dec_positions = false ) {
		$decimal_separator = module_config::c( 'currency_decimal_separator', '.' );
		$thounds_separator = module_config::c( 'currency_thousand_separator', ',' );
		$dec_positions     = ( $dec_positions === false || $dec_positions == - 1 ) ? module_config::c( 'currency_decimal_places', 2 ) : $dec_positions;
		//echo "Number format $value ";
		$num = number_format( (float) $value, $dec_positions, $decimal_separator, $thounds_separator );
		if ( $trim && module_config::c( 'number_trim_decimals', 1 ) ) {
			$num = preg_replace( '#(' . preg_quote( $decimal_separator, '#' ) . '[1-9])0+#', '$1', $num );
			$num = preg_replace( '#' . preg_quote( $decimal_separator, '#' ) . '0+$#', '', $num );
		}

		return $num;
	}
}

if ( ! function_exists( 'decimal_time_in' ) ) {
	// we're saving the time from the user. convert it into a decimal for database storage if needed.
	function decimal_time_in( $value ) {
		// are times treated in base 60 or 100
		if ( module_config::c( 'task_time_as_hours_minutes', 1 ) && strpos( $value, ':' ) !== false ) {
			// if the time is 1:40 it means 1 hour and 40 minutes.
			// if the time is 1:80 then we round it to 2 hours and 20 minutes
			$bits    = explode( ':', $value );
			$hours   = (int) $bits[0];
			$minutes = isset( $bits[1] ) ? preg_replace( '#[^0-9]#', '', $bits[1] ) : 0;
			if ( $minutes >= 60 ) {
				$hours ++;
				$minutes = $minutes - 60;
			}
			$value = number_out( $hours + ( $minutes / 60 ) );
		}

		return $value;
	}
}

if ( ! function_exists( 'decimal_time_out' ) ) {
	function decimal_time_out( $value ) {
		if ( module_config::c( 'task_time_as_hours_minutes', 1 ) ) {
			$bits    = explode( '.', $value );
			$hours   = (int) $bits[0];
			$minutes = isset( $bits[1] ) ? preg_replace( '#[^0-9]#', '', $bits[1] ) : 0;
			$minutes = round( ( "." . $minutes ) * 60 );
			if ( $minutes <= 0 ) {
				$minutes = '00';
			} else if ( $minutes < 10 ) {
				$minutes = '0' . $minutes;
			} else if ( $minutes >= 60 ) {
				$minutes = '00';
			} else {
				$minutes = str_pad( $minutes, 2, '0', STR_PAD_RIGHT );
			}
			$value = $hours . ':' . $minutes;
		}

		return $value;
	}
}

if ( ! function_exists( 'dollar' ) ) {
	function dollar( $number, $show_currency = true, $currency_id = false, $trim_decimals = false, $decimal_places = false ) {
		return currency( number_out( $number, $trim_decimals, $decimal_places ), $show_currency, $currency_id );
	}
}
if ( ! function_exists( 'currency' ) ) {
	function currency( $data, $show_currency = true, $currency_id = false ) {
		static $currency_cache = array();
		// find the default currency.
		if ( ! defined( '_DEFAULT_CURRENCY_ID' ) ) {
			$default_currency_id = module_config::c( 'default_currency_id', 1 );
			foreach ( get_multiple( 'currency', '', 'currency_id' ) as $currency ) {
				if ( $currency['currency_id'] == $default_currency_id ) {
					define( '_DEFAULT_CURRENCY_ID', $default_currency_id );
					define( '_DEFAULT_CURRENCY_SYMBOL', $currency['symbol'] );
					define( '_DEFAULT_CURRENCY_LOCATION', $currency['location'] );
					define( '_DEFAULT_CURRENCY_CODE', $currency['code'] );
				}
			}
		}
		$currency_symbol   = defined( '_DEFAULT_CURRENCY_SYMBOL' ) ? _DEFAULT_CURRENCY_SYMBOL : '$';
		$currency_location = defined( '_DEFAULT_CURRENCY_LOCATION' ) ? _DEFAULT_CURRENCY_LOCATION : 1;
		$currency_code     = defined( '_DEFAULT_CURRENCY_CODE' ) ? _DEFAULT_CURRENCY_CODE : 'USD';
		$show_name         = false;

		if ( $currency_id && defined( '_DEFAULT_CURRENCY_ID' ) && $currency_id != _DEFAULT_CURRENCY_ID ) {
			if ( $show_currency ) {
				$show_name = true;
			}
			$currency                       = isset( $currency_cache[ $currency_id ] ) ? $currency_cache[ $currency_id ] : get_single( 'currency', 'currency_id', $currency_id );
			$currency_cache[ $currency_id ] = $currency;
			if ( $currency ) {
				$currency_symbol   = $currency['symbol'];
				$currency_location = $currency['location'];
				$currency_code     = $currency['code'];
			}
			/*
			foreach(get_multiple('currency','','currency_id') as $currency){
					if($currency['currency_id']==$currency_id){
							$currency_symbol = $currency['symbol'];
							$currency_location = $currency['location'];
							$currency_code = $currency['code'];
					}
			}*/
		}
		/*$currency_location = module_config::c('currency_location','before');
		$currency_code = module_config::c('currency','$');
		$currency_name = module_config::c('currency_name','USD');*/

		switch ( strtolower( $currency_symbol ) ) {
			case "yen":
				$currency_symbol = '&yen;';
				break;
			case "eur":
				$currency_symbol = '&euro;';
				break;
			case "gbp":
				$currency_symbol = '&pound;';
				break;
			default:
				break;
		}

		if ( ! $show_currency ) {
			$currency_symbol = '';
		}
		if ( module_config::c( 'currency_show_code_always', 0 ) ) {
			$data .= ' ' . $currency_code;
		} else if ( $show_name && module_config::c( 'currency_show_non_default', 1 ) ) {
			$data .= ' ' . $currency_code;
		}

		switch ( $currency_location ) {
			case 'after':
			case 0:
				return $data . $currency_symbol;
				break;
			case 1:
			default:
				return $currency_symbol . $data;
		}
	}
}
if ( ! function_exists( 'is_closure' ) ) {
	function is_closure( $t ) {
		return is_object( $t ) && ( $t instanceof Closure );
	}
}
if ( ! function_exists( 'is_text_html' ) ) {
	function is_text_html( $text ) {
		return stripos( $text, '<br' ) !== false || stripos( $text, '<li' ) !== false || stripos( $text, '<p' ) !== false || stripos( $text, '<div' ) !== false;
	}
}

function hook_filter_var( $hook ) {
	global $hooks;
	$argv = array();
	$tmp  = func_get_args();
	// $argv[1] is the var we want to filter and return.
	foreach ( $tmp as $key => $value ) {
		$argv[ $key ] = &$tmp[ $key ];
	} // hack for php5.3.2
	if ( is_array( $hooks ) && isset( $hooks[ $hook ] ) && is_array( $hooks[ $hook ] ) ) {
		foreach ( $hooks[ $hook ] as $hook_callback ) {
			module_debug::log( array(
				'title' => 'calling hook filter var: ' . $hook,
				'data'  => 'callback: ' . $hook_callback . ' args: ' . var_export( $argv, true ),
			) );
			$this_return = call_user_func_array( $hook_callback, $argv );
			if ( $this_return !== false && $this_return !== null ) {
				module_debug::log( array(
					'title' => 'calling hook filter var: ' . $hook . ' completed!',
					'data'  => 'got results! ',
				) );
				$argv[1] = $this_return;
			}
		}
	}

	return $argv[1];
}

function fuzzy_date( $time, $reference = false ) {
	if ( ! $reference ) {
		$reference = time();
	}
	$ago = $reference - $time;
	if ( $ago >= 0 && $ago < 60 ) {
		$when = round( $ago );
		$s    = ( $when == 1 ) ? "second" : "seconds";

		return "$when $s ago";
	} elseif ( $ago >= 0 && $ago < 3600 ) {
		$when = round( $ago / 60 );
		$m    = ( $when == 1 ) ? "minute" : "minutes";

		return "$when $m ago";
	} elseif ( $ago >= 3600 && $ago < 86400 ) {
		$when = round( $ago / 60 / 60 );
		$h    = ( $when == 1 ) ? "hour" : "hours";

		return "$when $h ago";
	} elseif ( $ago >= 86400 && $ago < 2629743.83 ) {
		$when = round( $ago / 60 / 60 / 24 );
		$d    = ( $when == 1 ) ? "day" : "days";

		return "$when $d ago";
	} elseif ( $ago >= 2629743.83 && $ago < 31556926 ) {
		$when = round( $ago / 60 / 60 / 24 / 30.4375 );
		$m    = ( $when == 1 ) ? "month" : "months";

		return "$when $m ago";
	} elseif ( $ago > 31556926 ) {
		$when = round( $ago / 60 / 60 / 24 / 365 );
		$y    = ( $when == 1 ) ? "year" : "years";

		return "$when $y ago";
	} elseif ( $ago < - 31556926 ) {
		$when = abs( round( $ago / 60 / 60 / 24 / 365 ) );
		$y    = ( $when == 1 ) ? "year" : "years";

		return "in $when $y";
	} elseif ( $ago >= - 31556926 && $ago < - 2629743.83 ) { //-2678400
		$when = abs( round( $ago / 60 / 60 / 24 / 30.4375 ) );
		$m    = ( $when == 1 ) ? "month" : "months";

		return "in $when $m";
	} elseif ( $ago >= - 2629743.83 && $ago < - 86400 ) {
		$when = abs( round( $ago / 60 / 60 / 24 ) );
		$d    = ( $when == 1 ) ? "day" : "days";

		return "in $when $d";
	} elseif ( $ago >= - 86400 && $ago < - 3600 ) {
		$when = abs( round( $ago / 60 / 60 ) );
		$h    = ( $when == 1 ) ? "hour" : "hours";

		return "in $when $h";
	} elseif ( $ago >= - 3600 ) {
		$when = abs( round( $ago / 60 ) );
		$m    = ( $when == 1 ) ? "minute" : "minutes";

		return "in $when $m";
	} else {
		$when = abs( round( $ago ) );
		$s    = ( $when == 1 ) ? "second" : "seconds";

		return "in $when $s";
	}
}

if ( ! function_exists( 'message_box' ) ) {
	function message_box( $message, $title = false, $type = false ) {
		?>
		<div class="message-box<?php echo $type ? ' type-' . $type : ''; ?>">
			<p>
				<?php if ( $title ) { ?>
					<strong><?php echo $title; ?></strong>
				<?php } ?>
				<?php echo $message; ?>
			</p>
		</div>
		<?php
	}
}
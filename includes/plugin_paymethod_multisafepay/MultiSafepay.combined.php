<?php
# @File	: MultiSafepay.combined.php
# @Date	: 03-09-2012
class MultiSafepay {
	var $plugin_name = 'Connect';
	var $version = '2.0';

	// test or live api
	var $test = false;

	// merchant data
	var $merchant = array(
		'account_id'       => '', // required
		'site_id'          => '', // required
		'site_code'        => '', // required
		'notification_url' => '',
		'cancel_url'       => '',
		'redirect_url'     => '',
		'close_window'     => '',
	);

	// customer data
	var $customer = array(
		'locale'      => '', // advised
		'ipaddress'   => '',
		'forwardedip' => '',
		'firstname'   => '',
		'lastname'    => '',
		'address1'    => '',
		'address2'    => '',
		'housenumber' => '',
		'zipcode'     => '',
		'city'        => '',
		'state'       => '',
		'country'     => '',
		'phone'       => '',
		'email'       => '', // advised
	);

	// customer-delivery data
	var $delivery = array(
		'firstname'   => '',
		'lastname'    => '',
		'address1'    => '',
		'address2'    => '',
		'housenumber' => '',
		'zipcode'     => '',
		'city'        => '',
		'state'       => '',
		'country'     => '',
		'phone'       => '',
		'email'       => '',
	);

	// transaction data
	var $transaction = array(
		'id'          => '', // required
		'currency'    => '', // required
		'amount'      => '', // required
		'description' => '', // required
		'var1'        => '',
		'var2'        => '',
		'var3'        => '',
		'items'       => '',
		'manual'      => 'false',
		'gateway'     => '',
		'daysactive'  => '',
	);

	var $cart;
	var $fields;


	// signature
	var $cart_xml;
	var $fields_xml;
	var $signature;

	// return vars
	var $api_url;
	var $request_xml;
	var $reply_xml;
	var $payment_url;
	var $status;
	var $error_code;
	var $error;

	var $details;

	var $parsed_xml;
	var $parsed_root;


	function MultiSafepay() {
		$this->cart   = new MspCart();
		$this->fields = new MspCustomFields();
	}


	// Check Merchant credentials
	function checkSettings() {
		$this->merchant['account_id'] = trim( $this->merchant['account_id'] );
		$this->merchant['site_id']    = trim( $this->merchant['site_id'] );
		$this->merchant['site_code']  = trim( $this->merchant['site_code'] );
	}

	// Start Transaction / Return reply XML
	function startTransaction() {
		$this->checkSettings();
		$this->setIp();
		$this->createSignature();

		// create request
		$this->request_xml = $this->createTransactionRequest();

		// post request and get reply
		$this->api_url   = $this->getApiUrl();
		$this->reply_xml = $this->xmlPost( $this->api_url, $this->request_xml );

		// communication error
		if ( ! $this->reply_xml ) {
			return false;
		}

		// parse xml
		$rootNode = $this->parseXmlResponse( $this->reply_xml );
		if ( ! $rootNode ) {
			return false;
		}

		// return payment url
		$this->payment_url = $this->xmlUnescape( $rootNode['transaction']['payment_url']['VALUE'] );

		return $this->payment_url;
	}


	// Start Fast Checkout Transaction
	function startCheckout() {
		$this->checkSettings();
		$this->setIp();
		$this->createSignature();

		// create request
		$this->request_xml = $this->createCheckoutRequest();

		// post request and get reply
		$this->api_url   = $this->getApiUrl();
		$this->reply_xml = $this->xmlPost( $this->api_url, $this->request_xml );

		// communication error
		if ( ! $this->reply_xml ) {
			return false;
		}

		// parse xml
		$rootNode = $this->parseXmlResponse( $this->reply_xml );
		if ( ! $rootNode ) {
			return false;
		}

		// return payment url
		$this->payment_url = $this->xmlUnescape( $rootNode['transaction']['payment_url']['VALUE'] );

		return $this->payment_url;
	}


	// Status request for specific Transaction
	function getStatus() {
		$this->checkSettings();

		// generate request
		$this->request_xml = $this->createStatusRequest();

		// post request and get reply
		$this->api_url   = $this->getApiUrl();
		$this->reply_xml = $this->xmlPost( $this->api_url, $this->request_xml );

		// communication error
		if ( ! $this->reply_xml ) {
			return false;
		}

		// parse xml
		$rootNode = $this->parseXmlResponse( $this->reply_xml );
		if ( ! $rootNode ) {
			return false;
		}

		// parse all the order details
		$details       = $this->processStatusReply( $rootNode );
		$this->details = $details;

		// return status
		$this->status = $rootNode['ewallet']['status']['VALUE'];

		return $this->status;
	}


	function processStatusReply( $rootNode ) {
		$xml    = $rootNode;
		$result = array();

		$copy = array( 'ewallet', 'customer', 'customer-delivery', 'transaction', 'paymentdetails' );

		foreach ( $copy as $section ) {
			foreach ( $xml[ $section ] as $k => $v ) {
				$result[ $section ][ $k ] = $this->xmlUnescape( $v['VALUE'] );
			}
		}

		if ( isset( $xml['checkoutdata']['shopping-cart']['items']['item'] ) ) {
			$returnCart = array();

			if ( ! isset( $xml['checkoutdata']['shopping-cart']['items']['item'][0] ) ) {
				$xml['checkoutdata']['shopping-cart']['items']['item'] = array( $xml['checkoutdata']['shopping-cart']['items']['item'] );
			}

			foreach ( $xml['checkoutdata']['shopping-cart']['items']['item'] as $item ) {
				$returnItem = array();

				foreach ( $item as $k => $v ) {
					if ( $k == 'merchant-private-item-data' ) {
						$returnItem[ $k ] = $v;
						continue;
					}

					if ( $k == 'unit-price' ) {
						$returnItem['currency'] = $v['currency'];
					}
					$returnItem[ $k ] = $v['VALUE'];
				}

				$returnCart[] = $returnItem;
			}

			$result['shopping-cart'] = $returnCart;
		}

		if ( ! empty( $xml['checkoutdata']['order-adjustment']['shipping'] ) ) {
			$returnShipping = array();
			foreach ( $xml['checkoutdata']['order-adjustment']['shipping'] as $type => $shipping ) {
				$returnShipping['type']     = $type;
				$returnShipping['name']     = $shipping['shipping-name']['VALUE'];
				$returnShipping['cost']     = $shipping['shipping-cost']['VALUE'];
				$returnShipping['currency'] = $shipping['shipping-cost']['currency'];
			}

			$result['shipping'] = $returnShipping;
		}

		if ( ! empty( $xml['checkoutdata']['order-adjustment']['total-tax'] ) ) {
			$returnAddjustment = array();

			$returnAddjustment['total']    = $xml['checkoutdata']['order-adjustment']['total-tax']['VALUE'];
			$returnAddjustment['currency'] = $xml['checkoutdata']['order-adjustment']['total-tax']['currency'];

			$result['total-tax'] = $returnAddjustment;
		}

		if ( ! empty( $xml['checkoutdata']['order-adjustment']['adjustment-total'] ) ) {
			$returnAddjustment = array();

			$returnAddjustment['total']    = $xml['checkoutdata']['order-adjustment']['adjustment-total']['VALUE'];
			$returnAddjustment['currency'] = $xml['checkoutdata']['order-adjustment']['adjustment-total']['currency'];

			$result['adjustment-total'] = $returnAddjustment;
		}

		if ( ! empty( $xml['checkoutdata']['order-total'] ) ) {
			$returnTotal = array();

			$returnTotal['total']    = $xml['checkoutdata']['order-total']['VALUE'];
			$returnTotal['currency'] = $xml['checkoutdata']['order-total']['currency'];

			$result['ordert-total'] = $returnTotal;
		}

		if ( ! empty( $xml['checkoutdata']['custom-fields'] ) ) {
			foreach ( $xml['checkoutdata']['custom-fields'] as $k => $v ) {
				$result['custom-fields'][ $k ] = $v['VALUE'];
			}
		}

		return $result;
	}


	// Returns an associative array with the ids and the descriptions of the available gateways
	function getGateways() {
		$this->checkSettings();

		// generate request
		$this->request_xml = $this->createGatewaysRequest();

		// post request and get reply
		$this->api_url   = $this->getApiUrl();
		$this->reply_xml = $this->xmlPost( $this->api_url, $this->request_xml );

		// communication error
		if ( ! $this->reply_xml ) {
			return false;
		}

		// parse xml
		$rootNode = $this->parseXmlResponse( $this->reply_xml );
		if ( ! $rootNode ) {
			return false;
		}

		// get gatesways
		$gateways = array();
		foreach ( $rootNode['gateways']['gateway'] as $xml_gateway ) {
			$gateway                = array();
			$gateway['id']          = $xml_gateway['id']['VALUE'];
			$gateway['description'] = $xml_gateway['description']['VALUE'];

			// issuers
			if ( isset( $xml_gateway['issuers'] ) ) {
				$issuers = array();

				foreach ( $xml_gateway['issuers']['issuer'] as $xml_issuer ) {
					$issuer                   = array();
					$issuer['id']             = $xml_issuer['id']['VALUE'];
					$issuer['description']    = $xml_issuer['description']['VALUE'];
					$issuers[ $issuer['id'] ] = $issuer;
				}

				$gateway['issuers'] = $issuers;
			}

			$gateways[ $gateway['id'] ] = $gateway;
		}

		// return
		return $gateways;
	}


	// Connect Transaction XML
	function createTransactionRequest() {
		// issuer attribute
		$issuer = "";
		if ( ! empty( $this->issuer ) ) {
			$issuer = ' issuer="' . $this->xmlEscape( $this->issuer ) . '"';
		}

		$request = '<?xml version="1.0" encoding="UTF-8"?>
			<redirecttransaction ua="' . $this->plugin_name . ' ' . $this->version . '">
				<merchant>
					<account>' . $this->xmlEscape( $this->merchant['account_id'] ) . '</account>
					<site_id>' . $this->xmlEscape( $this->merchant['site_id'] ) . '</site_id>
					<site_secure_code>' . $this->xmlEscape( $this->merchant['site_code'] ) . '</site_secure_code>
					<notification_url>' . $this->xmlEscape( $this->merchant['notification_url'] ) . '</notification_url>
					<cancel_url>' . $this->xmlEscape( $this->merchant['cancel_url'] ) . '</cancel_url>
					<redirect_url>' . $this->xmlEscape( $this->merchant['redirect_url'] ) . '</redirect_url>
					<close_window>' . $this->xmlEscape( $this->merchant['close_window'] ) . '</close_window>
				</merchant>
				<customer>
					<locale>' . $this->xmlEscape( $this->customer['locale'] ) . '</locale>
					<ipaddress>' . $this->xmlEscape( $this->customer['ipaddress'] ) . '</ipaddress>
					<forwardedip>' . $this->xmlEscape( $this->customer['forwardedip'] ) . '</forwardedip>
					<firstname>' . $this->xmlEscape( $this->customer['firstname'] ) . '</firstname>
					<lastname>' . $this->xmlEscape( $this->customer['lastname'] ) . '</lastname>
					<address1>' . $this->xmlEscape( $this->customer['address1'] ) . '</address1>
					<address2>' . $this->xmlEscape( $this->customer['address2'] ) . '</address2>
					<housenumber>' . $this->xmlEscape( $this->customer['housenumber'] ) . '</housenumber>
					<zipcode>' . $this->xmlEscape( $this->customer['zipcode'] ) . '</zipcode>
					<city>' . $this->xmlEscape( $this->customer['city'] ) . '</city>
					<state>' . $this->xmlEscape( $this->customer['state'] ) . '</state>
					<country>' . $this->xmlEscape( $this->customer['country'] ) . '</country>
					<phone>' . $this->xmlEscape( $this->customer['phone'] ) . '</phone>
					<email>' . $this->xmlEscape( $this->customer['email'] ) . '</email>
				</customer>
				<customer-delivery>
					<firstname>' . $this->xmlEscape( $this->delivery['firstname'] ) . '</firstname>
					<lastname>' . $this->xmlEscape( $this->delivery['lastname'] ) . '</lastname>
					<address1>' . $this->xmlEscape( $this->delivery['address1'] ) . '</address1>
					<address2>' . $this->xmlEscape( $this->delivery['address2'] ) . '</address2>
					<housenumber>' . $this->xmlEscape( $this->delivery['housenumber'] ) . '</housenumber>
					<zipcode>' . $this->xmlEscape( $this->delivery['zipcode'] ) . '</zipcode>
					<city>' . $this->xmlEscape( $this->delivery['city'] ) . '</city>
					<state>' . $this->xmlEscape( $this->delivery['state'] ) . '</state>
					<country>' . $this->xmlEscape( $this->delivery['country'] ) . '</country>
					<phone>' . $this->xmlEscape( $this->delivery['phone'] ) . '</phone>
					<email>' . $this->xmlEscape( $this->delivery['email'] ) . '</email>
				</customer-delivery>
				<transaction>
					<id>' . $this->xmlEscape( $this->transaction['id'] ) . '</id>
					<currency>' . $this->xmlEscape( $this->transaction['currency'] ) . '</currency>
					<amount>' . $this->xmlEscape( $this->transaction['amount'] ) . '</amount>
					<description>' . $this->xmlEscape( $this->transaction['description'] ) . '</description>
					<var1>' . $this->xmlEscape( $this->transaction['var1'] ) . '</var1>
					<var2>' . $this->xmlEscape( $this->transaction['var2'] ) . '</var2>
					<var3>' . $this->xmlEscape( $this->transaction['var3'] ) . '</var3>
					<items>' . $this->xmlEscape( $this->transaction['items'] ) . '</items>
					<manual>' . $this->xmlEscape( $this->transaction['manual'] ) . '</manual>
					<daysactive>' . $this->xmlEscape( $this->transaction['daysactive'] ) . '</daysactive>
					<gateway' . $issuer . '>' . $this->xmlEscape( $this->transaction['gateway'] ) . '</gateway>
				</transaction>
				<signature>' . $this->xmlEscape( $this->signature ) . '</signature>
			</redirecttransaction>
		';

		return $request;
	}


	// FastCheckout request XML Creation
	function createCheckoutRequest() {
		$this->cart_xml = $this->cart->GetXML();
		$this->cart_xml = str_replace( '<?xml version="1.0" encoding="utf-8"?>', '', $this->cart_xml );

		$this->fields_xml = $this->fields->GetXML();
		$this->fields_xml = str_replace( '<?xml version="1.0" encoding="utf-8"?>', '', $this->fields_xml );

		$ganalytics = "";
		if ( ! empty( $this->ganalytics ) ) {
			$ganalytics .= '<google-analytics>';
			$ganalytics .= $this->xmlEscape( $this->ganalytics );
			$ganalytics .= '</google-analytics>';
		}

		$request = '<?xml version="1.0" encoding="UTF-8"?>
			<checkouttransaction ua="' . $this->plugin_name . ' ' . $this->version . '">
				<merchant>
					<account>' . $this->xmlEscape( $this->merchant['account_id'] ) . '</account>
					<site_id>' . $this->xmlEscape( $this->merchant['site_id'] ) . '</site_id>
					<site_secure_code>' . $this->xmlEscape( $this->merchant['site_code'] ) . '</site_secure_code>
					<notification_url>' . $this->xmlEscape( $this->merchant['notification_url'] ) . '</notification_url>
					<cancel_url>' . $this->xmlEscape( $this->merchant['cancel_url'] ) . '</cancel_url>
					<redirect_url>' . $this->xmlEscape( $this->merchant['redirect_url'] ) . '</redirect_url>
					<close_window>' . $this->xmlEscape( $this->merchant['close_window'] ) . '</close_window>
				</merchant>
				<customer>
					<locale>' . $this->xmlEscape( $this->customer['locale'] ) . '</locale>
					<ipaddress>' . $this->xmlEscape( $this->customer['ipaddress'] ) . '</ipaddress>
					<forwardedip>' . $this->xmlEscape( $this->customer['forwardedip'] ) . '</forwardedip>
					<firstname>' . $this->xmlEscape( $this->customer['firstname'] ) . '</firstname>
					<lastname>' . $this->xmlEscape( $this->customer['lastname'] ) . '</lastname>
					<address1>' . $this->xmlEscape( $this->customer['address1'] ) . '</address1>
					<address2>' . $this->xmlEscape( $this->customer['address2'] ) . '</address2>
					<housenumber>' . $this->xmlEscape( $this->customer['housenumber'] ) . '</housenumber>
					<zipcode>' . $this->xmlEscape( $this->customer['zipcode'] ) . '</zipcode>
					<city>' . $this->xmlEscape( $this->customer['city'] ) . '</city>
					<state>' . $this->xmlEscape( $this->customer['state'] ) . '</state>
					<country>' . $this->xmlEscape( $this->customer['country'] ) . '</country>
					<phone>' . $this->xmlEscape( $this->customer['phone'] ) . '</phone>
					<email>' . $this->xmlEscape( $this->customer['email'] ) . '</email>
				</customer>
				<customer-delivery>
					<firstname>' . $this->xmlEscape( $this->delivery['firstname'] ) . '</firstname>
					<lastname>' . $this->xmlEscape( $this->delivery['lastname'] ) . '</lastname>
					<address1>' . $this->xmlEscape( $this->delivery['address1'] ) . '</address1>
					<address2>' . $this->xmlEscape( $this->delivery['address2'] ) . '</address2>
					<housenumber>' . $this->xmlEscape( $this->delivery['housenumber'] ) . '</housenumber>
					<zipcode>' . $this->xmlEscape( $this->delivery['zipcode'] ) . '</zipcode>
					<city>' . $this->xmlEscape( $this->delivery['city'] ) . '</city>
					<state>' . $this->xmlEscape( $this->delivery['state'] ) . '</state>
					<country>' . $this->xmlEscape( $this->delivery['country'] ) . '</country>
					<phone>' . $this->xmlEscape( $this->delivery['phone'] ) . '</phone>
					<email>' . $this->xmlEscape( $this->delivery['email'] ) . '</email>
				</customer-delivery>
				' . $this->cart_xml . '
				' . $this->fields_xml . '
				' . $ganalytics . '
				<transaction>
					<id>' . $this->xmlEscape( $this->transaction['id'] ) . '</id>
					<currency>' . $this->xmlEscape( $this->transaction['currency'] ) . '</currency>
					<amount>' . $this->xmlEscape( $this->transaction['amount'] ) . '</amount>
					<description>' . $this->xmlEscape( $this->transaction['description'] ) . '</description>
					<var1>' . $this->xmlEscape( $this->transaction['var1'] ) . '</var1>
					<var2>' . $this->xmlEscape( $this->transaction['var2'] ) . '</var2>
					<var3>' . $this->xmlEscape( $this->transaction['var3'] ) . '</var3>
					<items>' . $this->xmlEscape( $this->transaction['items'] ) . '</items>
					<manual>' . $this->xmlEscape( $this->transaction['manual'] ) . '</manual>
					<gateway' . $issuer . '>' . $this->xmlEscape( $this->transaction['gateway'] ) . '</gateway>
				</transaction>
				<signature>' . $this->xmlEscape( $this->signature ) . '</signature>
			</checkouttransaction>
		';

		return $request;
	}


	// Status XML Request Creation
	function createStatusRequest() {
		$request = '<?xml version="1.0" encoding="UTF-8"?>
			<status ua="' . $this->plugin_name . ' ' . $this->version . '">
				<merchant>
					<account>' . $this->xmlEscape( $this->merchant['account_id'] ) . '</account>
					<site_id>' . $this->xmlEscape( $this->merchant['site_id'] ) . '</site_id>
					<site_secure_code>' . $this->xmlEscape( $this->merchant['site_code'] ) . '</site_secure_code>
				</merchant>
				<transaction>
					<id>' . $this->xmlEscape( $this->transaction['id'] ) . '</id>
				</transaction>
			</status>
		';

		return $request;
	}


	// Gateway XML Creation
	function createGatewaysRequest() {
		$request = '<?xml version="1.0" encoding="UTF-8"?>
			<gateways ua="' . $this->plugin_name . ' ' . $this->version . '">
				<merchant>
					<account>' . $this->xmlEscape( $this->merchant['account_id'] ) . '</account>
					<site_id>' . $this->xmlEscape( $this->merchant['site_id'] ) . '</site_id>
					<site_secure_code>' . $this->xmlEscape( $this->merchant['site_code'] ) . '</site_secure_code>
				</merchant>
				<customer>
					<country>' . $this->xmlEscape( $this->customer['country'] ) . '</country>
				</customer>
			</gateways>
		';

		return $request;
	}


	// Generate Signature
	function createSignature() {
		$this->signature = md5(
			$this->transaction['amount'] .
			$this->transaction['currency'] .
			$this->merchant['account_id'] .
			$this->merchant['site_id'] .
			$this->transaction['id']
		);
	}


	// Grab user IP
	function setIp() {
		$this->customer['ipaddress'] = $_SERVER['REMOTE_ADDR'];

		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$this->customer['forwardedip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
	}


	// Parse Customer adress
	function parseCustomerAddress( $street_address ) {
		list( $address, $apartment ) = $this->parseAddress( $street_address );
		$this->customer['address1']    = $address;
		$this->customer['housenumber'] = $apartment;
	}


	// Parse Delivery adress
	function parseDeliveryAddress( $street_address ) {
		list( $address, $apartment ) = $this->parseAddress( $street_address );
		$this->delivery['address1']    = $address;
		$this->delivery['housenumber'] = $apartment;
	}


	// Split up adress / number
	function parseAddress( $street_address ) {
		$address   = $street_address;
		$apartment = "";

		$offset = strlen( $street_address );

		while ( ( $offset = $this->rstrpos( $street_address, ' ', $offset ) ) !== false ) {
			if ( $offset < strlen( $street_address ) - 1 && is_numeric( $street_address[ $offset + 1 ] ) ) {
				$address   = trim( substr( $street_address, 0, $offset ) );
				$apartment = trim( substr( $street_address, $offset + 1 ) );
				break;
			}
		}

		if ( empty( $apartment ) && strlen( $street_address ) > 0 && is_numeric( $street_address[0] ) ) {
			$pos = strpos( $street_address, ' ' );

			if ( $pos !== false ) {
				$apartment = trim( substr( $street_address, 0, $pos ), ", \t\n\r\0\x0B" );
				$address   = trim( substr( $street_address, $pos + 1 ) );
			}
		}

		return array( $address, $apartment );
	}

	// Set default tax rates
	function setDefaultTaxZones( $globalRate = true, $shippingTaxed = true ) {
		$shippingTaxed = ( $shippingTaxed ) ? 'true' : 'false';

		if ( $globalRate ) {
			$rule = new MspDefaultTaxRule( '0.19', $shippingTaxed );
			$this->cart->AddDefaultTaxRules( $rule );
		}

		$table = new MspAlternateTaxTable( 'BTW19', 'true' );
		$rule  = new MspAlternateTaxRule( '0.19' );
		$table->AddAlternateTaxRules( $rule );
		$this->cart->AddAlternateTaxTables( $table );

		$table = new MspAlternateTaxTable( 'BTW6', 'true' );
		$rule  = new MspAlternateTaxRule( '0.06' );
		$table->AddAlternateTaxRules( $rule );
		$this->cart->AddAlternateTaxTables( $table );

		$table = new MspAlternateTaxTable( 'BTW0', 'true' );
		$rule  = new MspAlternateTaxRule( '0.00' );
		$table->AddAlternateTaxRules( $rule );
		$this->cart->AddAlternateTaxTables( $table );
	}


	// Select live / test environment
	function getApiUrl() {
		if ( $this->test ) {
			return "https://testapi.multisafepay.com/ewx/";
		} else {
			return "https://api.multisafepay.com/ewx/";
		}
	}


	// Parse XML Response
	function parseXmlResponse( $response ) {
		// strip xml line
		$response = preg_replace( '#</\?xml[^>]*>#is', '', $response );

		// parse
		$parser            = new msp_gc_xmlparser( $response );
		$this->parsed_xml  = $parser->GetData();
		$this->parsed_root = $parser->GetRoot();
		$rootNode          = $this->parsed_xml[ $this->parsed_root ];


		// check for error
		$result = $this->parsed_xml[ $this->parsed_root ]['result'];
		if ( $result != "ok" ) {
			$this->error_code = $rootNode['error']['code']['VALUE'];
			$this->error      = $rootNode['error']['description']['VALUE'];

			return false;
		}

		return $rootNode;
	}


	// Encrypt XML Data
	function xmlEscape( $str ) {
		return htmlspecialchars( $str, ENT_COMPAT, "UTF-8" );
	}

	// Remove Encryption
	function xmlUnescape( $str ) {
		return html_entity_decode( $str, ENT_COMPAT, "UTF-8" );
	}

	// Post request XML and receive reply XML
	function xmlPost( $url, $request_xml, $verify_peer = false ) {
		$curl_available = extension_loaded( "curl" );

		// generate request
		$header = array();

		if ( ! $curl_available ) {
			$url = parse_url( $url );

			if ( empty( $url['port'] ) ) {
				$url['port'] = $url['scheme'] == "https" ? 443 : 80;
			}

			$header[] = "POST " . $url['path'] . "?" . $url['query'] . " HTTP/1.1";
			$header[] = "Host: " . $url['host'] . ":" . $url['port'];
			$header[] = "Content-Length: " . strlen( $request_xml );
		}

		$header[] = "Content-Type: text/xml";
		$header[] = "Connection: close";

		// issue request
		if ( $curl_available ) {
			$ch = curl_init( $url );

			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $request_xml );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verify_peer );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
			curl_setopt( $ch, CURLOPT_HEADER, true );

			$reply_data = curl_exec( $ch );
		} else {
			$request_data = implode( "\r\n", $header );
			$request_data .= "\r\n\r\n";
			$request_data .= $request_xml;
			$reply_data   = "";

			$errno  = 0;
			$errstr = "";

			$fp = fsockopen( ( $url['scheme'] == "https" ? "ssl://" : "" ) . $url['host'], $url['port'], $errno, $errstr, 30 );

			if ( $fp ) {
				if ( function_exists( "stream_context_set_params" ) ) {
					stream_context_set_params( $fp, array(
						'ssl' => array(
							'verify_peer'       => $verify_peer,
							'allow_self_signed' => $verify_peer
						)
					) );
				}

				fwrite( $fp, $request_data );
				fflush( $fp );

				while ( ! feof( $fp ) ) {
					$reply_data .= fread( $fp, 1024 );
				}

				fclose( $fp );
			}
		}

		// check response
		if ( $curl_available ) {
			if ( curl_errno( $ch ) ) {
				$this->error_code = - 1;
				$this->error      = "curl error: " . curl_errno( $ch );

				return false;
			}

			$reply_info = curl_getinfo( $ch );
			curl_close( $ch );
		} else {
			if ( $errno ) {
				$this->error_code = - 1;
				$this->error      = "connection error: " . $errno;

				return false;
			}

			$header_size  = strpos( $reply_data, "\r\n\r\n" );
			$header_data  = substr( $reply_data, 0, $header_size );
			$header       = explode( "\r\n", $header_data );
			$status_line  = explode( " ", $header[0] );
			$content_type = "application/octet-stream";

			foreach ( $header as $header_line ) {
				$header_parts = explode( ":", $header_line );

				if ( strtolower( $header_parts[0] ) == "content-type" ) {
					$content_type = trim( $header_parts[1] );
					break;
				}
			}

			$reply_info = array(
				'http_code'    => (int) $status_line[1],
				'content_type' => $content_type,
				'header_size'  => $header_size + 4
			);
		}

		if ( $reply_info['http_code'] != 200 ) {
			$this->error_code = - 1;
			$this->error      = "http error: " . $reply_info['http_code'];

			return false;
		}

		if ( strstr( $reply_info['content_type'], "/xml" ) === false ) {
			$this->error_code = - 1;
			$this->error      = "content type error: " . $reply_info['content_type'];

			return false;
		}

		// split header and body    
		$reply_header = substr( $reply_data, 0, $reply_info['header_size'] - 4 );
		$reply_xml    = substr( $reply_data, $reply_info['header_size'] );

		if ( empty( $reply_xml ) ) {
			$this->error_code = - 1;
			$this->error      = "received empty response";

			return false;
		}

		return $reply_xml;
	}

	// From http://www.php.net/manual/en/function.strrpos.php#78556
	function rstrpos( $haystack, $needle, $offset = null ) {
		$size = strlen( $haystack );

		if ( is_null( $offset ) ) {
			$offset = $size;
		}

		$pos = strpos( strrev( $haystack ), strrev( $needle ), $size - $offset );

		if ( $pos === false ) {
			return false;
		}

		return $size - $pos - strlen( $needle );
	}
}

// XML Parser
class msp_gc_xmlparser {
	var $params = array(); //Stores the object representation of XML data
	var $root = null;
	var $global_index = - 1;
	var $fold = false;

	// Class constructor
	function msp_gc_xmlparser( $input, $xmlParams = array( XML_OPTION_CASE_FOLDING => 0 ) ) {

		// XML PARSE BUG: http://bugs.php.net/bug.php?id=45996
		$input = str_replace( '&amp;', '[msp-amp]', $input );
		//

		$xmlp = xml_parser_create();
		foreach ( $xmlParams as $opt => $optVal ) {
			switch ( $opt ) {
				case XML_OPTION_CASE_FOLDING:
					$this->fold = $optVal;
					break;
				default:
					break;
			}
			xml_parser_set_option( $xmlp, $opt, $optVal );
		}

		if ( xml_parse_into_struct( $xmlp, $input, $vals, $index ) ) {
			$this->root   = $this->_foldCase( $vals[0]['tag'] );
			$this->params = $this->xml2ary( $vals );
		}

		xml_parser_free( $xmlp );
	}

	function _foldCase( $arg ) {
		return ( $this->fold ? strtoupper( $arg ) : $arg );
	}

	/*
	* Credits for the structure of this function
	* http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
	* 
	* Adapted by Ropu - 05/23/2007 
	*/
	function xml2ary( $vals ) {
		$mnary = array();
		$ary   =& $mnary;
		foreach ( $vals as $r ) {
			$t = $r['tag'];
			if ( $r['type'] == 'open' ) {
				if ( isset( $ary[ $t ] ) && ! empty( $ary[ $t ] ) ) {
					if ( isset( $ary[ $t ][0] ) ) {
						$ary[ $t ][] = array();
					} else {
						$ary[ $t ] = array( $ary[ $t ], array() );
					}

					$cv =& $ary[ $t ][ count( $ary[ $t ] ) - 1 ];
				} else {
					$cv =& $ary[ $t ];
				}

				$cv = array();
				if ( isset( $r['attributes'] ) ) {
					foreach ( $r['attributes'] as $k => $v ) {
						$cv[ $k ] = $v;
					}
				}

				$cv['_p'] =& $ary;
				$ary      =& $cv;

			} else if ( $r['type'] == 'complete' ) {
				if ( isset( $ary[ $t ] ) && ! empty( $ary[ $t ] ) ) { // same as open
					if ( isset( $ary[ $t ][0] ) ) {
						$ary[ $t ][] = array();
					} else {
						$ary[ $t ] = array( $ary[ $t ], array() );
					}

					$cv =& $ary[ $t ][ count( $ary[ $t ] ) - 1 ];
				} else {
					$cv =& $ary[ $t ];
				}
				if ( isset( $r['attributes'] ) ) {
					foreach ( $r['attributes'] as $k => $v ) {
						$cv[ $k ] = $v;
					}
				}

				$cv['VALUE'] = ( isset( $r['value'] ) ? $r['value'] : '' );

				// XML PARSE BUG: http://bugs.php.net/bug.php?id=45996
				$cv['VALUE'] = str_replace( '[msp-amp]', '&amp;', $cv['VALUE'] );
				//
			} elseif ( $r['type'] == 'close' ) {
				$ary =& $ary['_p'];
			}
		}

		$this->_del_p( $mnary );

		return $mnary;
	}

	// _Internal: Remove recursion in result array
	function _del_p( &$ary ) {
		foreach ( $ary as $k => $v ) {
			if ( $k === '_p' ) {
				unset( $ary[ $k ] );
			} else if ( is_array( $ary[ $k ] ) ) {
				$this->_del_p( $ary[ $k ] );
			}
		}
	}

	/* Returns the root of the XML data */
	function GetRoot() {
		return $this->root;
	}

	/* Returns the array representing the XML data */
	function GetData() {
		return $this->params;
	}
}

// XML Data generation
class msp_gc_XmlBuilder {
	var $xml;
	var $indent;
	var $stack = array();

	function msp_gc_XmlBuilder( $indent = '  ' ) {
		$this->indent = $indent;
		$this->xml    = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	}

	function _indent() {
		for ( $i = 0, $j = count( $this->stack ); $i < $j; $i ++ ) {
			$this->xml .= $this->indent;
		}
	}

	//Used when an element has sub-elements
	// This function adds an open tag to the output
	function Push( $element, $attributes = array() ) {
		$this->_indent();
		$this->xml .= '<' . $element;
		foreach ( $attributes as $key => $value ) {
			$this->xml .= ' ' . $key . '="' . htmlentities( $value ) . '"';
		}

		$this->xml     .= ">\n";
		$this->stack[] = $element;
	}

	//Used when an element has no subelements.
	//Data within the open and close tags are provided with the 
	//contents variable
	function Element( $element, $content, $attributes = array() ) {
		$this->_indent();
		$this->xml .= '<' . $element;
		foreach ( $attributes as $key => $value ) {
			$this->xml .= ' ' . $key . '="' . htmlentities( $value ) . '"';
		}

		$this->xml .= '>' . htmlentities( $content ) . '</' . $element . '>' . "\n";
	}

	function EmptyElement( $element, $attributes = array() ) {
		$this->_indent();
		$this->xml .= '<' . $element;
		foreach ( $attributes as $key => $value ) {
			$this->xml .= ' ' . $key . '="' . htmlentities( $value ) . '"';
		}

		$this->xml .= " />\n";
	}

	//Used to close an open tag
	function Pop( $pop_element ) {
		$element = array_pop( $this->stack );
		$this->_indent();
		if ( $element !== $pop_element ) {
			die( 'XML Error: Tag Mismatch when trying to close "' . $pop_element . '"' );
		} else {
			$this->xml .= "</$element>\n";
		}
	}

	function GetXML() {
		if ( count( $this->stack ) != 0 ) {
			die ( 'XML Error: No matching closing tag found for " ' . array_pop( $this->stack ) . '"' );
		} else {
			return $this->xml;
		}
	}
}

define( 'MAX_DIGITAL_DESC', 1024 );

class MspCart {
	var $merchant_id;
	var $merchant_key;
	var $variant = false;
	var $currency;
	var $server_url;
	var $schema_url;
	var $base_url;
	var $checkout_url;
	var $checkout_diagnose_url;
	var $request_url;
	var $request_diagnose_url;

	var $cart_expiration = "";
	var $merchant_private_data = "";
	var $edit_cart_url = "";
	var $continue_shopping_url = "";
	var $request_buyer_phone = "";
	var $merchant_calculated_tax = "";
	var $merchant_calculations_url = "";
	var $accept_merchant_coupons = "";
	var $accept_gift_certificates = "";
	var $rounding_mode;
	var $rounding_rule;
	var $analytics_data;

	var $item_arr;
	var $shipping_arr;
	var $default_tax_rules_arr;
	var $alternate_tax_tables_arr;
	var $xml_data;

	var $googleAnalytics_id = false;
	var $thirdPartyTackingUrl = false;
	var $thirdPartyTackingParams = array();

	var $multiple_tags = array(
		'flat-rate-shipping'           => array(),
		'merchant-calculated-shipping' => array(),
		'pickup'                       => array(),
		'parameterized-url'            => array(),
		'url-parameter'                => array(),
		'item'                         => array(),
		'us-state-area'                => array( 'tax-area' ),
		'us-zip-area'                  => array( 'tax-area' ),
		'us-country-area'              => array( 'tax-area' ),
		'postal-area'                  => array( 'tax-area' ),
		'alternate-tax-table'          => array(),
		'world-area'                   => array( 'tax-area' ),
		'default-tax-rule'             => array(),
		'alternate-tax-rule'           => array(),
		'gift-certificate-adjustment'  => array(),
		'coupon-adjustment'            => array(),
		'coupon-result'                => array(),
		'gift-certificate-result'      => array(),
		'method'                       => array(),
		'anonymous-address'            => array(),
		'result'                       => array(),
		'string'                       => array(),
	);

	var $ignore_tags = array(
		'xmlns'                      => true,
		'checkout-shopping-cart'     => true,
		'merchant-private-data'      => true,
		'merchant-private-item-data' => true,
	);


	/*
	* Has all the logic to build the cart's xml (or html) request to be 
	* posted to google's servers.
	* 
	* @param string $id the merchant id
	* @param string $key the merchant key
	* @param string $server_type the server type of the server to be used, one of 'test' or 'live'.
	* 	defaults to 'test'
	* @param string $currency the currency of the items to be added to the cart
	* 	defaults to 'EUR'
	*/
	function MspCart( $id = '', $key = '', $server_type = "sandbox", $currency = "EUR" ) {
		$this->merchant_id  = $id;
		$this->merchant_key = $key;
		$this->currency     = $currency;

		if ( strtolower( $server_type ) == "sandbox" ) {
			$this->server_url = "https://sandbox.google.com/checkout/";
		} else {
			$this->server_url = "https://checkout.google.com/";
		}


		$this->schema_url       = "";
		$this->base_url         = $this->server_url . "api/checkout/v2/";
		$this->checkout_url     = $this->base_url . "checkout/Merchant/" . $this->merchant_id;
		$this->checkoutForm_url = $this->base_url . "checkoutForm/Merchant/" . $this->merchant_id;

		//The item, shipping and tax table arrays are initialized
		$this->item_arr                 = array();
		$this->shipping_arr             = array();
		$this->alternate_tax_tables_arr = array();
	}

	/*
    * Sets the cart's expiration date
	*
    * @param string $cart_expire a string representing a date in the 
    * 	iso 8601 date and time format: {@link http://www.w3.org/TR/NOTE-datetime}
    * 
    * @return void
    */
	function SetCartExpiration( $cart_expire ) {
		$this->cart_expiration = $cart_expire;
	}

	function SetMerchantPrivateData( $data ) {
		$this->merchant_private_data = $data;
	}

	/*
    * Sets the url where the customer can edit his cart.
    * 
	* @param string $url the merchant's site edit cart url
    * @return void
    */
	function SetEditCartUrl( $url ) {
		$this->edit_cart_url = $url;
	}

	/*
    * Sets the continue shopping url, which allows the customer to return 
    * to the merchant's site after confirming an order.
    * 
    * @param string $url the merchant's site continue shopping url
    * @return void
    */
	function SetContinueShoppingUrl( $url ) {
		$this->continue_shopping_url = $url;
	}

	/*
    * Sets the information about calculations that will be performed by the 
    * merchant.
    * 
    * @param string $url the merchant calculations callback url
    * @param bool $tax_option true if the merchant has to do tax calculations.
    *                         defaults to false.
    * @param bool $coupons true if the merchant accepts discount coupons.
    *                         defaults to false.
    * @param bool $gift_cert true if the merchant accepts gift certificates.
    *                         defaults to false.
    * @return void
    */
	function SetMerchantCalculations(
		$url, $tax_option = "false",
		$coupons = "false", $gift_cert = "false"
	) {
		$this->merchant_calculations_url = $url;
		$this->merchant_calculated_tax   = $this->_GetBooleanValue( $tax_option, "false" );
		$this->accept_merchant_coupons   = $this->_GetBooleanValue( $coupons, "false" );
		$this->accept_gift_certificates  = $this->_GetBooleanValue( $gift_cert, "false" );
	}

	/*
    * Add an item to the cart.
    * 
    * @param MSP Item $msp_item an object that represents an item 
    * 
    * @return void
    */
	function AddItem( $msp_item ) {
		$this->item_arr[] = $msp_item;
	}

	/*
    * Add a shipping method to the cart.
    * 
    * @param object $ship an object that represents a shipping method, must be 
    * 	one of the methods defined in googleshipping.php
    * 
    * @return void
    */
	function AddShipping( $ship ) {
		$this->shipping_arr[] = $ship;
	}

	/*
    * Add a default tax rule to the cart.
    * 
    * @param GoogleDefaultTaxRule $rules an object that represents a default
    * 	tax rule (defined in googletax.php)
    * 
    * @return void
    */
	function AddDefaultTaxRules( $rules ) {
		$this->default_tax_table       = true;
		$this->default_tax_rules_arr[] = $rules;
	}

	/*
    * Add an alternate tax table to the cart.
    * 
    * @param AddAlternateTaxTable $tax an object that represents an 
    *	alternate tax table
    * 
    * @return void
    */
	function AddAlternateTaxTables( $tax ) {
		$this->alternate_tax_tables_arr[] = $tax;
	}

	function AddRoundingPolicy( $mode, $rule ) {
		switch ( $mode ) {
			case "UP":
			case "DOWN":
			case "CEILING":
			case "HALF_UP":
			case "HALF_DOWN":
			case "HALF_EVEN":
				$this->rounding_mode = $mode;
				break;
			default:
				break;
		}

		switch ( $rule ) {
			case "PER_LINE":
			case "TOTAL":
				$this->rounding_rule = $rule;
				break;
			default:
				break;
		}
	}

	/**
	 * Set the google analytics data.
	 *
	 * {@link http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html info on Checkout and Analytics integration}
	 *
	 * @param string $data the analytics data
	 *
	 * @return void
	 */
	function SetAnalyticsData( $data ) {
		$this->analytics_data = $data;
	}

	/**
	 * Add a google analytics tracking id.
	 *
	 * {@link http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html info on Checkout and Analytics integration}
	 *
	 * @param string $GA_id the google analytics id
	 *
	 * @return void
	 */
	function AddGoogleAnalyticsTracking( $GA_id ) {
		$this->googleAnalytics_id = $GA_id;
	}

	/**
	 * Add third-party tracking to the cart
	 *
	 * Described here:
	 * {@link http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html#googleCheckoutAnalyticsIntegrationAlternate}
	 *
	 * @param $tracking_attr_types attributes to be tracked, one of
	 *                            ('buyer-id',
	 *                             'order-id',
	 *                             'order-subtotal',
	 *                             'order-subtotal-plus-tax',
	 *                             'order-subtotal-plus-shipping',
	 *                             'order-total',
	 *                             'tax-amount',
	 *                             'shipping-amount',
	 *                             'coupon-amount',
	 *                             'coupon-amount',
	 *                             'billing-city',
	 *                             'billing-region',
	 *                             'billing-postal-code',
	 *                             'billing-country-code',
	 *                             'shipping-city',
	 *                             'shipping-region',
	 *                             'shipping-postal-code',
	 *                             'shipping-country-code')
	 * More info http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html#googleCheckout_tag_url-parameter
	 */
	function AddThirdPartyTracking( $url, $tracking_param_types = array() ) {
		$this->thirdPartyTackingUrl    = $url;
		$this->thirdPartyTackingParams = $tracking_param_types;
	}

	/*
    * Builds the cart's xml to be sent to MultiSafepay.
    * 
    * @return string the cart's xml
    */
	function GetXML() {
		$xml_data = new msp_gc_XmlBuilder();
		$xml_data->Push( 'checkout-shopping-cart',

			array( 'xmlns' => $this->schema_url ) );
		$xml_data->Push( 'shopping-cart' );

		//Add cart expiration if set
		if ( $this->cart_expiration != "" ) {
			$xml_data->Push( 'cart-expiration' );
			$xml_data->Element( 'good-until-date', $this->cart_expiration );
			$xml_data->Pop( 'cart-expiration' );
		}

		//Add XML data for each of the items
		$xml_data->Push( 'items' );
		foreach ( $this->item_arr as $item ) {
			$xml_data->Push( 'item' );
			$xml_data->Element( 'item-name', $item->item_name );
			$xml_data->Element( 'item-description', $item->item_description );
			$xml_data->Element( 'unit-price', $item->unit_price,
				array( 'currency' => $this->currency ) );
			$xml_data->Element( 'quantity', $item->quantity );
			if ( $item->merchant_private_item_data != '' ) {
				if ( is_a( $item->merchant_private_item_data, 'merchantprivate' ) ) {
					$item->merchant_private_item_data->AddMerchantPrivateToXML( $xml_data );
				} else {
					$xml_data->Element( 'merchant-private-item-data', $item->merchant_private_item_data );
				}
			}
			if ( $item->merchant_item_id != '' ) {
				$xml_data->Element( 'merchant-item-id', $item->merchant_item_id );
			}
			if ( $item->tax_table_selector != '' ) {
				$xml_data->Element( 'tax-table-selector', $item->tax_table_selector );
			}
			if ( $item->item_weight != '' && $item->numeric_weight !== '' ) {
				$xml_data->EmptyElement( 'item-weight', array(
					'unit'  => $item->item_weight,
					'value' => $item->numeric_weight
				) );
			}

			if ( $item->digital_content ) {
				$xml_data->push( 'digital-content' );
				if ( ! empty( $item->digital_url ) ) {
					$xml_data->element( 'description', substr( $item->digital_description, 0, MAX_DIGITAL_DESC ) );
					$xml_data->element( 'url', $item->digital_url );

					if ( ! empty( $item->digital_key ) ) {
						$xml_data->element( 'key', $item->digital_key );
					}
				} else {
					$xml_data->element( 'email-delivery',
						$this->_GetBooleanValue( $item->email_delivery, "true" ) );
				}
				$xml_data->pop( 'digital-content' );
			}

			$xml_data->Pop( 'item' );
		}

		$xml_data->Pop( 'items' );

		if ( $this->merchant_private_data != '' ) {
			if ( is_a( $this->merchant_private_data, 'merchantprivate' ) ) {
				$this->merchant_private_data->AddMerchantPrivateToXML( $xml_data );
			} else {
				$xml_data->Element( 'merchant-private-data', $this->merchant_private_data );
			}
		}

		$xml_data->Pop( 'shopping-cart' );
		$xml_data->Push( 'checkout-flow-support' );
		$xml_data->Push( 'merchant-checkout-flow-support' );

		if ( $this->edit_cart_url != '' ) {
			$xml_data->Element( 'edit-cart-url', $this->edit_cart_url );
		}
		if ( $this->continue_shopping_url != '' ) {
			$xml_data->Element( 'continue-shopping-url', $this->continue_shopping_url );
		}

		if ( count( $this->shipping_arr ) > 0 ) {
			$xml_data->Push( 'shipping-methods' );
		}

		//Add the shipping methods
		foreach ( $this->shipping_arr as $ship ) {
			if ( $ship->type == "flat-rate-shipping" || $ship->type == "merchant-calculated-shipping" ) {
				$xml_data->Push( $ship->type, array( 'name' => $ship->name ) );
				$xml_data->Element( 'price', $ship->price,

					array( 'currency' => $this->currency ) );

				$shipping_restrictions = $ship->shipping_restrictions;
				if ( isset( $shipping_restrictions ) ) {
					$xml_data->Push( 'shipping-restrictions' );

					if ( $shipping_restrictions->allow_us_po_box === true ) {
						$xml_data->Element( 'allow-us-po-box', "true" );
					} else {
						$xml_data->Element( 'allow-us-po-box', "false" );
					}

					//Check if allowed restrictions specified
					if ( $shipping_restrictions->allowed_restrictions ) {
						$xml_data->Push( 'allowed-areas' );
						if ( $shipping_restrictions->allowed_country_area != "" ) {
							$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $shipping_restrictions->allowed_country_area ) );
						}
						foreach ( $shipping_restrictions->allowed_state_areas_arr as $current ) {
							$xml_data->Push( 'us-state-area' );
							$xml_data->Element( 'state', $current );
							$xml_data->Pop( 'us-state-area' );
						}

						foreach ( $shipping_restrictions->allowed_zip_patterns_arr as $current ) {
							$xml_data->Push( 'us-zip-area' );
							$xml_data->Element( 'zip-pattern', $current );
							$xml_data->Pop( 'us-zip-area' );
						}

						if ( $shipping_restrictions->allowed_world_area === true ) {
							$xml_data->EmptyElement( 'world-area' );
						}

						for ( $i = 0; $i < count( $shipping_restrictions->allowed_country_codes_arr ); $i ++ ) {
							$xml_data->Push( 'postal-area' );
							$country_code   = $shipping_restrictions->allowed_country_codes_arr[ $i ];
							$postal_pattern = $shipping_restrictions->allowed_postal_patterns_arr[ $i ];
							$xml_data->Element( 'country-code', $country_code );

							if ( $postal_pattern != "" ) {
								$xml_data->Element( 'postal-code-pattern', $postal_pattern );
							}

							$xml_data->Pop( 'postal-area' );
						}

						$xml_data->Pop( 'allowed-areas' );
					}

					if ( $shipping_restrictions->excluded_restrictions ) {
						if ( ! $shipping_restrictions->allowed_restrictions ) {
							$xml_data->EmptyElement( 'allowed-areas' );
						}

						$xml_data->Push( 'excluded-areas' );
						if ( $shipping_restrictions->excluded_country_area != "" ) {
							$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $shipping_restrictions->excluded_country_area ) );
						}

						foreach ( $shipping_restrictions->excluded_state_areas_arr as $current ) {
							$xml_data->Push( 'us-state-area' );
							$xml_data->Element( 'state', $current );
							$xml_data->Pop( 'us-state-area' );
						}

						foreach ( $shipping_restrictions->excluded_zip_patterns_arr as $current ) {
							$xml_data->Push( 'us-zip-area' );
							$xml_data->Element( 'zip-pattern', $current );
							$xml_data->Pop( 'us-zip-area' );
						}

						for ( $i = 0; $i < count( $shipping_restrictions->excluded_country_codes_arr ); $i ++ ) {
							$xml_data->Push( 'postal-area' );
							$country_code   = $shipping_restrictions->excluded_country_codes_arr[ $i ];
							$postal_pattern = $shipping_restrictions->excluded_postal_patterns_arr[ $i ];
							$xml_data->Element( 'country-code', $country_code );
							if ( $postal_pattern != "" ) {
								$xml_data->Element( 'postal-code-pattern', $postal_pattern );
							}

							$xml_data->Pop( 'postal-area' );
						}

						$xml_data->Pop( 'excluded-areas' );
					}
					$xml_data->Pop( 'shipping-restrictions' );
				}

				if ( $ship->type == "merchant-calculated-shipping" ) {
					$address_filters = $ship->address_filters;
					if ( isset( $address_filters ) ) {
						$xml_data->Push( 'address-filters' );

						if ( $address_filters->allow_us_po_box === true ) {
							$xml_data->Element( 'allow-us-po-box', "true" );
						} else {
							$xml_data->Element( 'allow-us-po-box', "false" );
						}

						//Check if allowed restrictions specified
						if ( $address_filters->allowed_restrictions ) {
							$xml_data->Push( 'allowed-areas' );
							if ( $address_filters->allowed_country_area != "" ) {
								$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $address_filters->allowed_country_area ) );
							}
							foreach ( $address_filters->allowed_state_areas_arr as $current ) {
								$xml_data->Push( 'us-state-area' );
								$xml_data->Element( 'state', $current );
								$xml_data->Pop( 'us-state-area' );
							}

							foreach ( $address_filters->allowed_zip_patterns_arr as $current ) {
								$xml_data->Push( 'us-zip-area' );
								$xml_data->Element( 'zip-pattern', $current );
								$xml_data->Pop( 'us-zip-area' );
							}
							if ( $address_filters->allowed_world_area === true ) {
								$xml_data->EmptyElement( 'world-area' );
							}

							for ( $i = 0; $i < count( $address_filters->allowed_country_codes_arr ); $i ++ ) {
								$xml_data->Push( 'postal-area' );
								$country_code   = $address_filters->allowed_country_codes_arr[ $i ];
								$postal_pattern = $address_filters->allowed_postal_patterns_arr[ $i ];
								$xml_data->Element( 'country-code', $country_code );
								if ( $postal_pattern != "" ) {
									$xml_data->Element( 'postal-code-pattern', $postal_pattern );
								}

								$xml_data->Pop( 'postal-area' );
							}
							$xml_data->Pop( 'allowed-areas' );
						}

						if ( $address_filters->excluded_restrictions ) {
							if ( ! $address_filters->allowed_restrictions ) {
								$xml_data->EmptyElement( 'allowed-areas' );
							}
							$xml_data->Push( 'excluded-areas' );
							if ( $address_filters->excluded_country_area != "" ) {
								$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $address_filters->excluded_country_area ) );
							}

							foreach ( $address_filters->excluded_state_areas_arr as $current ) {
								$xml_data->Push( 'us-state-area' );
								$xml_data->Element( 'state', $current );
								$xml_data->Pop( 'us-state-area' );
							}

							foreach ( $address_filters->excluded_zip_patterns_arr as $current ) {
								$xml_data->Push( 'us-zip-area' );
								$xml_data->Element( 'zip-pattern', $current );
								$xml_data->Pop( 'us-zip-area' );
							}

							for ( $i = 0; $i < count( $address_filters->excluded_country_codes_arr ); $i ++ ) {
								$xml_data->Push( 'postal-area' );
								$country_code   = $address_filters->excluded_country_codes_arr[ $i ];
								$postal_pattern = $address_filters->excluded_postal_patterns_arr[ $i ];
								$xml_data->Element( 'country-code', $country_code );
								if ( $postal_pattern != "" ) {
									$xml_data->Element( 'postal-code-pattern', $postal_pattern );
								}
								$xml_data->Pop( 'postal-area' );
							}

							$xml_data->Pop( 'excluded-areas' );
						}
						$xml_data->Pop( 'address-filters' );
					}
				}
				$xml_data->Pop( $ship->type );
			} else if ( $ship->type == "carrier-calculated-shipping" ) {
				$xml_data->Push( $ship->type );
				$xml_data->Push( 'carrier-calculated-shipping-options' );
				$CCSoptions = $ship->CarrierCalculatedShippingOptions;
				foreach ( $CCSoptions as $CCSoption ) {
					$xml_data->Push( 'carrier-calculated-shipping-option' );
					$xml_data->Element( 'price', $CCSoption->price, array( 'currency' => $this->currency ) );
					$xml_data->Element( 'shipping-company', $CCSoption->shipping_company );
					$xml_data->Element( 'shipping-type', $CCSoption->shipping_type );
					$xml_data->Element( 'carrier-pickup', $CCSoption->carrier_pickup );
					if ( ! empty( $CCSoption->additional_fixed_charge ) ) {
						$xml_data->Element( 'additional-fixed-charge',
							$CCSoption->additional_fixed_charge, array( 'currency' => $this->currency ) );
					}

					if ( ! empty( $CCSoption->additional_variable_charge_percent ) ) {
						$xml_data->Element( 'additional-variable-charge-percent',
							$CCSoption->additional_variable_charge_percent );
					}

					$xml_data->Pop( 'carrier-calculated-shipping-option' );
				}

				$xml_data->Pop( 'carrier-calculated-shipping-options' );
				$xml_data->Push( 'shipping-packages' );
				$xml_data->Push( 'shipping-package' );
				$xml_data->Push( 'ship-from', array( 'id' => $ship->ShippingPackage->ship_from->id ) );
				$xml_data->Element( 'city', $ship->ShippingPackage->ship_from->city );
				$xml_data->Element( 'region', $ship->ShippingPackage->ship_from->region );
				$xml_data->Element( 'postal-code', $ship->ShippingPackage->ship_from->postal_code );
				$xml_data->Element( 'country-code', $ship->ShippingPackage->ship_from->country_code );
				$xml_data->Pop( 'ship-from' );
				$xml_data->EmptyElement( 'width', array(
					'unit'  => $ship->ShippingPackage->unit,
					'value' => $ship->ShippingPackage->width
				) );
				$xml_data->EmptyElement( 'length', array(
					'unit'  => $ship->ShippingPackage->unit,
					'value' => $ship->ShippingPackage->length
				) );
				$xml_data->EmptyElement( 'height', array(
					'unit'  => $ship->ShippingPackage->unit,
					'value' => $ship->ShippingPackage->height
				) );
				$xml_data->Element( 'delivery-address-category', $ship->ShippingPackage->delivery_address_category );
				$xml_data->Pop( 'shipping-package' );
				$xml_data->Pop( 'shipping-packages' );
				$xml_data->Pop( $ship->type );
			} else if ( $ship->type == "pickup" ) {
				$xml_data->Push( 'pickup', array( 'name' => $ship->name ) );
				$xml_data->Element( 'price', $ship->price, array( 'currency' => $this->currency ) );
				$xml_data->Pop( 'pickup' );
			}
		}

		if ( count( $this->shipping_arr ) > 0 ) {
			$xml_data->Pop( 'shipping-methods' );
		}
		if ( $this->request_buyer_phone != "" ) {
			$xml_data->Element( 'request-buyer-phone-number', $this->request_buyer_phone );
		}
		if ( $this->merchant_calculations_url != "" ) {
			$xml_data->Push( 'merchant-calculations' );
			$xml_data->Element( 'merchant-calculations-url',
				$this->merchant_calculations_url );
			if ( $this->accept_merchant_coupons != "" ) {
				$xml_data->Element( 'accept-merchant-coupons', $this->accept_merchant_coupons );
			}

			if ( $this->accept_gift_certificates != "" ) {
				$xml_data->Element( 'accept-gift-certificates', $this->accept_gift_certificates );
			}

			$xml_data->Pop( 'merchant-calculations' );
		}

		//Set Third party Tracking
		if ( $this->thirdPartyTackingUrl ) {
			$xml_data->push( 'parameterized-urls' );
			$xml_data->push( 'parameterized-url', array( 'url' => $this->thirdPartyTackingUrl ) );
			if ( is_array( $this->thirdPartyTackingParams ) && count( $this->thirdPartyTackingParams ) > 0 ) {
				$xml_data->push( 'parameters' );

				foreach ( $this->thirdPartyTackingParams as $tracking_param_name => $tracking_param_type ) {
					$xml_data->emptyElement( 'url-parameter', array(
						'name' => $tracking_param_name,
						'type' => $tracking_param_type
					) );
				}

				$xml_data->pop( 'parameters' );
			}
			$xml_data->pop( 'parameterized-url' );
			$xml_data->pop( 'parameterized-urls' );
		}

		//Set Default and Alternate tax tables
		if ( ( count( $this->alternate_tax_tables_arr ) != 0 ) || ( count( $this->default_tax_rules_arr ) != 0 ) ) {
			if ( $this->merchant_calculated_tax != "" ) {
				$xml_data->Push( 'tax-tables', array( 'merchant-calculated' => $this->merchant_calculated_tax ) );
			} else {
				$xml_data->Push( 'tax-tables' );
			}

			if ( count( $this->default_tax_rules_arr ) != 0 ) {
				$xml_data->Push( 'default-tax-table' );
				$xml_data->Push( 'tax-rules' );

				foreach ( $this->default_tax_rules_arr as $curr_rule ) {
					$rule_added = false;
					if ( $curr_rule->country_area != "" ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Push( 'tax-area' );
						$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $curr_rule->country_area ) );
						$xml_data->Pop( 'tax-area' );
						$xml_data->Pop( 'default-tax-rule' );
						$rule_added = true;
					}

					foreach ( $curr_rule->state_areas_arr as $current ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Push( 'tax-area' );
						$xml_data->Push( 'us-state-area' );
						$xml_data->Element( 'state', $current );
						$xml_data->Pop( 'us-state-area' );
						$xml_data->Pop( 'tax-area' );
						$xml_data->Pop( 'default-tax-rule' );
						$rule_added = true;
					}

					foreach ( $curr_rule->zip_patterns_arr as $current ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Push( 'tax-area' );
						$xml_data->Push( 'us-zip-area' );
						$xml_data->Element( 'zip-pattern', $current );
						$xml_data->Pop( 'us-zip-area' );
						$xml_data->Pop( 'tax-area' );
						$xml_data->Pop( 'default-tax-rule' );
						$rule_added = true;
					}

					for ( $i = 0; $i < count( $curr_rule->country_codes_arr ); $i ++ ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Push( 'tax-area' );
						$xml_data->Push( 'postal-area' );
						$country_code   = $curr_rule->country_codes_arr[ $i ];
						$postal_pattern = $curr_rule->postal_patterns_arr[ $i ];
						$xml_data->Element( 'country-code', $country_code );
						if ( $postal_pattern != "" ) {
							$xml_data->Element( 'postal-code-pattern', $postal_pattern );
						}
						$xml_data->Pop( 'postal-area' );
						$xml_data->Pop( 'tax-area' );
						$xml_data->Pop( 'default-tax-rule' );
						$rule_added = true;
					}

					if ( $curr_rule->world_area === true ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Push( 'tax-area' );
						$xml_data->EmptyElement( 'world-area' );
						$xml_data->Pop( 'tax-area' );
						$xml_data->Pop( 'default-tax-rule' );
						$rule_added = true;
					}

					// msp add
					if ( ! $rule_added ) {
						$xml_data->Push( 'default-tax-rule' );
						$xml_data->Element( 'shipping-taxed', $curr_rule->shipping_taxed );
						$xml_data->Element( 'rate', $curr_rule->tax_rate );
						$xml_data->Pop( 'default-tax-rule' );
					}
					// msp end
				}

				$xml_data->Pop( 'tax-rules' );
				$xml_data->Pop( 'default-tax-table' );
			}

			if ( count( $this->alternate_tax_tables_arr ) != 0 ) {
				$xml_data->Push( 'alternate-tax-tables' );
				foreach ( $this->alternate_tax_tables_arr as $curr_table ) {
					$xml_data->Push( 'alternate-tax-table', array(
						'standalone' => $curr_table->standalone,
						'name'       => $curr_table->name
					) );
					$xml_data->Push( 'alternate-tax-rules' );

					foreach ( $curr_table->tax_rules_arr as $curr_rule ) {
						if ( $curr_rule->country_area != "" ) {
							$xml_data->Push( 'alternate-tax-rule' );
							$xml_data->Element( 'rate', $curr_rule->tax_rate );
							$xml_data->Push( 'tax-area' );
							$xml_data->EmptyElement( 'us-country-area', array( 'country-area' => $curr_rule->country_area ) );
							$xml_data->Pop( 'tax-area' );
							$xml_data->Pop( 'alternate-tax-rule' );
						}

						foreach ( $curr_rule->state_areas_arr as $current ) {
							$xml_data->Push( 'alternate-tax-rule' );
							$xml_data->Element( 'rate', $curr_rule->tax_rate );
							$xml_data->Push( 'tax-area' );
							$xml_data->Push( 'us-state-area' );
							$xml_data->Element( 'state', $current );
							$xml_data->Pop( 'us-state-area' );
							$xml_data->Pop( 'tax-area' );
							$xml_data->Pop( 'alternate-tax-rule' );
						}

						foreach ( $curr_rule->zip_patterns_arr as $current ) {
							$xml_data->Push( 'alternate-tax-rule' );
							$xml_data->Element( 'rate', $curr_rule->tax_rate );
							$xml_data->Push( 'tax-area' );
							$xml_data->Push( 'us-zip-area' );
							$xml_data->Element( 'zip-pattern', $current );
							$xml_data->Pop( 'us-zip-area' );
							$xml_data->Pop( 'tax-area' );
							$xml_data->Pop( 'alternate-tax-rule' );
						}

						for ( $i = 0; $i < count( $curr_rule->country_codes_arr ); $i ++ ) {
							$xml_data->Push( 'alternate-tax-rule' );
							$xml_data->Element( 'rate', $curr_rule->tax_rate );
							$xml_data->Push( 'tax-area' );
							$xml_data->Push( 'postal-area' );
							$country_code   = $curr_rule->country_codes_arr[ $i ];
							$postal_pattern = $curr_rule->postal_patterns_arr[ $i ];
							$xml_data->Element( 'country-code', $country_code );
							if ( $postal_pattern != "" ) {
								$xml_data->Element( 'postal-code-pattern', $postal_pattern );
							}
							$xml_data->Pop( 'postal-area' );
							$xml_data->Pop( 'tax-area' );
							$xml_data->Pop( 'alternate-tax-rule' );
						}

						if ( $curr_rule->world_area === true ) {
							$xml_data->Push( 'alternate-tax-rule' );
							$xml_data->Element( 'rate', $curr_rule->tax_rate );
							$xml_data->Push( 'tax-area' );
							$xml_data->EmptyElement( 'world-area' );
							$xml_data->Pop( 'tax-area' );
							$xml_data->Pop( 'alternate-tax-rule' );
						}
					}

					$xml_data->Pop( 'alternate-tax-rules' );
					$xml_data->Pop( 'alternate-tax-table' );
				}
				$xml_data->Pop( 'alternate-tax-tables' );
			}

			$xml_data->Pop( 'tax-tables' );
		}

		if ( ( $this->rounding_mode != "" ) && ( $this->rounding_rule != "" ) ) {
			$xml_data->Push( 'rounding-policy' );
			$xml_data->Element( 'mode', $this->rounding_mode );
			$xml_data->Element( 'rule', $this->rounding_rule );
			$xml_data->Pop( 'rounding-policy' );
		}

		if ( $this->analytics_data != '' ) {
			$xml_data->Element( 'analytics-data', $this->analytics_data );
		}

		$xml_data->Pop( 'merchant-checkout-flow-support' );
		$xml_data->Pop( 'checkout-flow-support' );
		$xml_data->Pop( 'checkout-shopping-cart' );

		return $xml_data->GetXML();
	}

	function SetButtonVariant( $variant ) {
		switch ( $variant ) {
			case false:
				$this->variant = "disabled";
				break;
			case true:
			default:
				$this->variant = "text";
				break;
		}
	}

	function CheckoutButtonCode( $size = "large", $variant = true, $loc = "en_US", $showtext = true, $style = "trans" ) {
		switch ( strtolower( $size ) ) {
			case "medium":
				$width  = "168";
				$height = "44";
				break;
			case "small":
				$width  = "160";
				$height = "43";
				break;
			case "large":
			default:
				$width  = "180";
				$height = "46";
				break;
		}

		if ( $this->variant == false ) {
			switch ( $variant ) {
				case false:
					$this->variant = "disabled";
					break;
				case true:
				default:
					$this->variant = "text";
					break;
			}
		}


		$data = "<div style=\"width: " . $width . "px\">";
		if ( $this->variant == "text" ) {
			$data .= "<div align=center><form method=\"POST\" action=\"" . $this->checkout_url . "\"" . ( $this->googleAnalytics_id ? " onsubmit=\"setUrchinInputCode();\"" : "" ) . ">
					<input type=\"hidden\" name=\"cart\" value=\"" .
			         base64_encode( $this->GetXML() ) . "\">
					<input type=\"hidden\" name=\"signature\" value=\"" .
			         base64_encode( $this->CalcHmacSha1( $this->GetXML() ) ) . "\"> 
					<input type=\"image\" name=\"Checkout\" alt=\"Checkout\" 
					src=\"" . $this->server_url . "buttons/checkout.gif?merchant_id=" .
			         $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" .
			         $style . "&variant=" . $this->variant . "&loc=" . $loc . "\" 
					height=\"" . $height . "\" width=\"" . $width . "\" />";

			if ( $this->googleAnalytics_id ) {
				$data .= "<input type=\"hidden\" name=\"analyticsdata\" value=\"\">";
			}

			$data .= "</form></div>";
			if ( $this->googleAnalytics_id ) {
				$data .= "<!-- Start Google analytics -->
				<script src=\"https://ssl.google-analytics.com/urchin.js\" type=\"" .
				         "text/javascript\">
				</script>
				<script type=\"text/javascript\">
				_uacct = \"" . $this->googleAnalytics_id . "\";
				urchinTracker();
				</script>
				<script src=\"https://checkout.google.com/files/digital/urchin_po" .
				         "st.js\" type=\"text/javascript\"></script>  
				<!-- End Google analytics -->";
			}
		} else {
			$data .= "<div><img alt=\"Checkout\" src=\"" .
			         "" . $this->server_url . "buttons/checkout.gif?merchant_id=" .
			         "" . $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" . $style .
			         "&variant=" . $this->variant . "&loc=" . $loc . "\" height=\"" . $height . "\"" .
			         " width=\"" . $width . "\" /></div>";
		}

		if ( $showtext ) {
			$data .= "<div align=\"center\"><a href=\"javascript:void(window.ope" .
			         "n('http://checkout.google.com/seller/what_is_google_checkout.html'" .
			         ",'whatischeckout','scrollbars=0,resizable=1,directories=0,height=2" .
			         "50,width=400'));\" onmouseover=\"return window.status = 'What is G" .
			         "oogle Checkout?'\" onmouseout=\"return window.status = ''\"><font " .
			         "size=\"-2\">What is Google Checkout?</font></a></div>";
		}

		$data .= "</div>";

		return $data;
	}

	//Code for generating Checkout button 
	//@param $variant will be ignored if SetButtonVariant() was used before
	function CheckoutButtonNowCode( $size = "large", $variant = true, $loc = "en_US", $showtext = true, $style = "trans" ) {
		switch ( strtolower( $size ) ) {
			case "small":
				$width  = "121";
				$height = "44";
				break;
			case "large":
			default:
				$width  = "117";
				$height = "48";
				break;
		}

		if ( $this->variant == false ) {
			switch ( $variant ) {
				case false:
					$this->variant = "disabled";
					break;
				case true:
				default:
					$this->variant = "text";
					break;
			}
		}


		$data = "<div style=\"width: " . $width . "px\">";
		if ( $this->variant == "text" ) {
			$data .= "<div align=center><form method=\"POST\" action=\"" .
			         $this->checkout_url . "\"" . ( $this->googleAnalytics_id ?
					" onsubmit=\"setUrchinInputCode();\"" : "" ) . ">
                <input type=\"hidden\" name=\"buyButtonCart\" value=\"" .
			         base64_encode( $this->GetXML() ) . "//separator//" .
			         base64_encode( $this->CalcHmacSha1( $this->GetXML() ) ) . "\">
                <input type=\"image\" name=\"Checkout\" alt=\"BuyNow\" 
                src=\"" . $this->server_url . "buttons/buy.gif?merchant_id=" .
			         $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" .
			         $style . "&variant=" . $this->variant . "&loc=" . $loc . "\" 
                height=\"" . $height . "\" width=\"" . $width . "\" />";

			if ( $this->googleAnalytics_id ) {
				$data .= "<input type=\"hidden\" name=\"analyticsdata\" value=\"\">";
			}
			$data .= "</form></div>";
			if ( $this->googleAnalytics_id ) {
				$data .= "<!-- Start Google analytics -->
            <script src=\"https://ssl.google-analytics.com/urchin.js\" type=\"" .
				         "text/javascript\">
            </script>
            <script type=\"text/javascript\">
            _uacct = \"" . $this->googleAnalytics_id . "\";
            urchinTracker();
            </script>
            <script src=\"https://checkout.google.com/files/digital/urchin_po" .
				         "st.js\" type=\"text/javascript\"></script>  
            <!-- End Google analytics -->";
			}
			//        ask for link to BuyNow disable button
		} else {
			$data .= "<div><img alt=\"Checkout\" src=\"" .
			         "" . $this->server_url . "buttons/buy.gif?merchant_id=" .
			         "" . $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" . $style .
			         "&variant=" . $this->variant . "&loc=" . $loc . "\" height=\"" . $height . "\"" .
			         " width=\"" . $width . "\" /></div>";
		}
		if ( $showtext ) {
			$data .= "<div align=\"center\"><a href=\"javascript:void(window.ope" .
			         "n('http://checkout.google.com/seller/what_is_google_checkout.html'" .
			         ",'whatischeckout','scrollbars=0,resizable=1,directories=0,height=2" .
			         "50,width=400'));\" onmouseover=\"return window.status = 'What is G" .
			         "oogle Checkout?'\" onmouseout=\"return window.status = ''\"><font " .
			         "size=\"-2\">What is Google Checkout?</font></a></div>";
		}
		$data .= "</div>";

		return $data;
	}


	/**
	 * Get the Google Checkout button's html to be used with the html api.
	 *
	 * {@link http://code.google.com/apis/checkout/developer/index.html#google_checkout_buttons}
	 *
	 * @param string $size the size of the button, one of 'large', 'medium' or
	 *                     'small'.
	 *                     defaults to 'large'
	 * @param bool   $variant true for an enabled button, false for a
	 *                      disabled one. defaults to true. will be ignored if
	 *                      SetButtonVariant() was used before.
	 * @param string $loc the locale of the button's text, the only valid value
	 *                    is 'en_US' (used as default)
	 * @param bool   $showtext whether to show Google Checkout text or not,
	 *                       defaults to true.
	 * @param string $style the background style of the button, one of 'white'
	 *                      or 'trans'. defaults to "trans"
	 *
	 * @return string the button's html
	 */
	function CheckoutHTMLButtonCode(
		$size = "large", $variant = true, $loc = "en_US",
		$showtext = true, $style = "trans"
	) {

		switch ( strtolower( $size ) ) {
			case "medium":
				$width  = "168";
				$height = "44";
				break;

			case "small":
				$width  = "160";
				$height = "43";
				break;
			case "large":
			default:
				$width  = "180";
				$height = "46";
				break;
		}

		if ( $this->variant == false ) {
			switch ( $variant ) {
				case false:
					$this->variant = "disabled";
					break;
				case true:
				default:
					$this->variant = "text";
					break;
			}
		}


		$data = "<div style=\"width: " . $width . "px\">";
		if ( $this->variant == "text" ) {
			$data .= "<div align=\"center\"><form method=\"POST\" action=\"" .
			         $this->checkoutForm_url . "\"" . ( $this->googleAnalytics_id ?
					" onsubmit=\"setUrchinInputCode();\"" : "" ) . ">";

			$request = $this->GetXML();
			require_once( 'xml-processing/gc_xmlparser.php' );
			$xml_parser = new gc_xmlparser( $request );
			$root       = $xml_parser->GetRoot();
			$XMLdata    = $xml_parser->GetData();
			$this->xml2html( $XMLdata[ $root ], '', $data );
			$data .= "<input type=\"image\" name=\"Checkout\" alt=\"Checkout\" " .
			         "src=\"" . $this->server_url . "buttons/checkout.gif?merchant_id=" .
			         $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" .
			         $style . "&variant=" . $this->variant . "&loc=" . $loc . "\" 
                height=\"" . $height . "\" width=\"" . $width . "\" />";

			if ( $this->googleAnalytics_id ) {
				$data .= "<input type=\"hidden\" name=\"analyticsdata\" value=\"\">";
			}
			$data .= "</form></div>";
			if ( $this->googleAnalytics_id ) {
				$data .= "<!-- Start Google analytics -->
            <script src=\"https://ssl.google-analytics.com/urchin.js\" type=\"" .
				         "text/javascript\">
            </script>
            <script type=\"text/javascript\">
            _uacct = \"" . $this->googleAnalytics_id . "\";
            urchinTracker();
            </script>
            <script src=\"https://checkout.google.com/files/digital/urchin_po" .
				         "st.js\" type=\"text/javascript\"></script>  
            <!-- End Google analytics -->";
			}
		} else {
			$data .= "<div align=\"center\"><img alt=\"Checkout\" src=\"" .
			         "" . $this->server_url . "buttons/checkout.gif?merchant_id=" .
			         "" . $this->merchant_id . "&w=" . $width . "&h=" . $height . "&style=" . $style .
			         "&variant=" . $this->variant . "&loc=" . $loc . "\" height=\"" . $height . "\"" .
			         " width=\"" . $width . "\" /></div>";
		}
		if ( $showtext ) {
			$data .= "<div align=\"center\"><a href=\"javascript:void(window.ope" .
			         "n('http://checkout.google.com/seller/what_is_google_checkout.html'" .
			         ",'whatischeckout','scrollbars=0,resizable=1,directories=0,height=2" .
			         "50,width=400'));\" onmouseover=\"return window.status = 'What is G" .
			         "oogle Checkout?'\" onmouseout=\"return window.status = ''\"><font " .
			         "size=\"-2\">What is Google Checkout?</font></a></div>";
		}
		$data .= "</div>";


		return $data;

	}

	/**
	 * @access private
	 */
	function xml2html( $data, $path = '', &$rta ) {
		//      global $multiple_tags,$ignore_tags;
		//    $arr = gc_get_arr_result($data);  
		foreach ( $data as $tag_name => $tag ) {
			if ( isset( $this->ignore_tags[ $tag_name ] ) ) {
				continue;
			}
			if ( is_array( $tag ) ) {
				//     echo print_r($tag, true) . $tag_name . "<- tag name\n";
				if ( ! $this->is_associative_array( $data ) ) {
					$new_path = $path . '-' . ( $tag_name + 1 );
				} else {
					if ( isset( $this->multiple_tags[ $tag_name ] )
					     && $this->is_associative_array( $tag )
					     && ! $this->isChildOf( $path, $this->multiple_tags[ $tag_name ] ) ) {
						$tag_name .= '-1';
					}
					$new_path = $path . ( empty( $path ) ? '' : '.' ) . $tag_name;
				}
				$this->xml2html( $tag, $new_path, $rta );
			} else {
				$new_path = $path;
				if ( $tag_name != 'VALUE' ) {
					$new_path = $path . "." . $tag_name;
				}
				$rta .= '<input type="hidden" name="' .
				        $new_path . '" value="' . $tag . '"/>' . "\n";
			}
		}
	}

	// Returns true if a given variable represents an associative array

	/**
	 * @access private
	 */
	function is_associative_array( $var ) {
		return is_array( $var ) && ! is_numeric( implode( '', array_keys( $var ) ) );
	}

	/**
	 * @access private
	 */
	function isChildOf( $path = '', $parents = array() ) {
		$intersect = array_intersect( explode( '.', $path ), $parents );

		return ! empty( $intersect );
	}

	/**
	 * Get the Google Checkout acceptance logos html
	 *
	 * {@link http://checkout.google.com/seller/acceptance_logos.html}
	 *
	 * @param integer $type the acceptance logo type, valid values: 1, 2, 3
	 *
	 * @return string the logo's html
	 */
	function CheckoutAcceptanceLogo( $type = 1 ) {
		switch ( $type ) {
			case 2:
				return '<link rel="stylesheet" href="https://checkout.google.com/' .
				       'seller/accept/s.css" type="text/css" media="screen" /><scrip' .
				       't type="text/javascript" src="https://checkout.google.com/se' .
				       'ller/accept/j.js"></script><script type="text/javascript">sh' .
				       'owMark(1);</script><noscript><img src="https://checkout.goog' .
				       'le.com/seller/accept/images/st.gif" width="92" height="88" a' .
				       'lt="Google Checkout Acceptance Mark" /></noscript>';
				break;
			case 3:
				return '<link rel="stylesheet" href="https://checkout.google.com/' .
				       'seller/accept/s.css" type="text/css" media="screen" /><scrip' .
				       't type="text/javascript" src="https://checkout.google.com/se' .
				       'ller/accept/j.js"></script><script type="text/javascript">sh' .
				       'owMark(2);</script><noscript><img src="https://checkout.goog' .
				       'le.com/seller/accept/images/ht.gif" width="182" height="44" ' .
				       'alt="Google Checkout Acceptance Mark" /></noscript>';
				break;
			case 1:
			default:
				return '<link rel="stylesheet" href="https://checkout.google.com/' .
				       'seller/accept/s.css" type="text/css" media="screen" /><scrip' .
				       't type="text/javascript" src="https://checkout.google.com/se' .
				       'ller/accept/j.js"></script><script type="text/javascript">sh' .
				       'owMark(3);</script><noscript><img src="https://checkout.goog' .
				       'le.com/seller/accept/images/sc.gif" width="72" height="73" a' .
				       'lt="Google Checkout Acceptance Mark" /></noscript>';
				break;
		}
	}

	/**
	 * Calculates the cart's hmac-sha1 signature, this allows google to verify
	 * that the cart hasn't been tampered by a third-party.
	 *
	 * {@link http://code.google.com/apis/checkout/developer/index.html#create_signature}
	 *
	 * @param string $data the cart's xml
	 *
	 * @return string the cart's signature (in binary format)
	 */
	function CalcHmacSha1( $data ) {
		$key       = $this->merchant_key;
		$blocksize = 64;
		$hashfunc  = 'sha1';
		if ( strlen( $key ) > $blocksize ) {
			$key = pack( 'H*', $hashfunc( $key ) );
		}
		$key  = str_pad( $key, $blocksize, chr( 0x00 ) );
		$ipad = str_repeat( chr( 0x36 ), $blocksize );
		$opad = str_repeat( chr( 0x5c ), $blocksize );
		$hmac = pack(
			'H*', $hashfunc(
				( $key ^ $opad ) . pack(
					'H*', $hashfunc(
						( $key ^ $ipad ) . $data
					)
				)
			)
		);

		return $hmac;
	}

	//Method used internally to set true/false cart variables

	/**
	 * @access private
	 */
	function _GetBooleanValue( $value, $default ) {
		switch ( strtolower( $value ) ) {
			case "true":
				return "true";
				break;
			case "false":
				return "false";
				break;
			default:
				return $default;
				break;
		}
	}
	//Method used internally to set true/false cart variables
	// Deprecated, must NOT use eval, bug-prune function
	/**
	 * @access private
	 */
	function _SetBooleanValue( $string, $value, $default ) {
		$value = strtolower( $value );
		if ( $value == "true" || $value == "false" ) {
			eval( '$this->' . $string . '="' . $value . '";' );
		} else {
			eval( '$this->' . $string . '="' . $default . '";' );
		}
	}
}

/**
 * @abstract
 * Abstract class that represents the merchant-private-data.
 *
 * See {@link MerchantPrivateData} and {@link MerchantPrivateItemData}
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-data <merchant-private-data>}
 */
class MerchantPrivate {
	var $data;
	var $type = "Abstract";

	function MerchantPrivate() {
	}

	function AddMerchantPrivateToXML( &$xml_data ) {
		if ( is_array( $this->data ) ) {
			$xml_data->Push( $this->type );
			$this->_recursiveAdd( $xml_data, $this->data );
			$xml_data->Pop( $this->type );
		} else {
			$xml_data->Element( $this->type, (string) $this->data );
		}
	}

	/**
	 * @access private
	 */
	function _recursiveAdd( &$xml_data, $data ) {
		foreach ( $data as $name => $value ) {
			if ( is_array( $value ) ) {
				$xml_data->Push( $name );
				$this->_recursiveAdd( $xml_data, $name );
				$xml_data->Pop( $name );
			} else {
				$xml_data->Element( $name, (string) $value );
			}
		}
	}
}

/**
 * Class that represents the merchant-private-data.
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-data <merchant-private-data>}
 */
class MerchantPrivateData extends MerchantPrivate {
	/**
	 * @param mixed $data a string with the data that will go in the
	 *                    merchant-private-data tag or an array that will
	 *                    be mapped to xml, formatted like (e.g.):
	 *                    array('my-order-id' => 34234,
	 *                          'stuff' => array('registered' => 'yes',
	 *                                           'category' => 'hip stuff'))
	 *                    this will map to:
	 *                    <my-order-id>
	 *                      <stuff>
	 *                        <registered>yes</registered>
	 *                        <category>hip stuff</category>
	 *                      </stuff>
	 *                    </my-order-id>
	 */
	function MerchantPrivateData( $data = array() ) {
		$this->data = $data;
		$this->type = 'merchant-private-data';
	}
}

/**
 * Class that represents a merchant-private-item-data.
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-item-data <merchant-private-data>}
 */
class MerchantPrivateItemData extends MerchantPrivate {
	/**
	 * @param mixed $data a string with the data that will go in the
	 *                    merchant-private-item-data tag or an array that will
	 *                    be mapped to xml, formatted like:
	 *                    array('my-item-id' => 34234,
	 *                          'stuff' => array('label' => 'cool',
	 *                                           'category' => 'hip stuff'))
	 *                    this will map to:
	 *                    <my-item-id>
	 *                      <stuff>
	 *                        <label>cool</label>
	 *                        <category>hip stuff</category>
	 *                      </stuff>
	 *                    </my-item-id>
	 */
	function MerchantPrivateItemData( $data = array() ) {
		$this->data = $data;
		$this->type = 'merchant-private-item-data';
	}
}


/*
 * Copyright (C) 2007 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Classes used to represent an item to be used for Google Checkout
 * @version $Id: googleitem.php 1234 2007-09-25 14:58:57Z ropu $
 */

/**
 * Creates an item to be added to the shopping cart.
 * A new instance of the class must be created for each item to be added.
 *
 * Required fields are the item name, description, quantity and price
 * The private-data and tax-selector for each item can be set in the
 * constructor call or using individual Set functions
 */
class MspItem {

	var $item_name;
	var $item_description;
	var $unit_price;
	var $quantity;
	var $merchant_private_item_data;
	var $merchant_item_id;
	var $tax_table_selector;
	var $email_delivery;
	var $digital_content = false;
	var $digital_description;
	var $digital_key;
	var $digital_url;

	var $item_weight;
	var $numeric_weight;

	/**
	 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_item <item>}
	 *
	 * @param string  $name the name of the item -- required
	 * @param string  $desc the description of the item -- required
	 * @param integer $qty the number of units of this item the customer has
	 *                    in its shopping cart -- required
	 * @param double  $price the unit price of the item -- required
	 * @param string  $item_weight the weight unit used to specify the item's
	 *                            weight,
	 *                            one of 'LB' (pounds) or 'KG' (kilograms)
	 * @param double  $numeric_weight the weight of the item
	 *
	 */
	function MspItem( $name, $desc, $qty, $price, $item_weight = '', $numeric_weight = '' ) {
		$this->item_name        = $name;
		$this->item_description = $desc;
		$this->unit_price       = $price;
		$this->quantity         = $qty;

		if ( $item_weight != '' && $numeric_weight !== '' ) {
			switch ( strtoupper( $item_weight ) ) {
				case 'KG':
					$this->item_weight = strtoupper( $item_weight );
					break;
				case 'LB':
				default:
					$this->item_weight = 'LB';
			}
			$this->numeric_weight = (double) $numeric_weight;
		}
	}

	function SetMerchantPrivateItemData( $private_data ) {
		$this->merchant_private_item_data = $private_data;
	}

	/**
	 * Set the merchant item id that the merchant uses to uniquely identify an
	 * item. Google Checkout will include this value in the
	 * merchant calculation callbacks
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-item-id <merchant-item-id>}
	 *
	 * @param mixed $item_id the value that identifies this item on the
	 *                                 merchant's side
	 *
	 * @return void
	 */
	function SetMerchantItemId( $item_id ) {
		$this->merchant_item_id = $item_id;
	}

	/**
	 * Sets the tax table selector which identifies an alternate tax table that
	 * should be used to calculate tax for a particular item.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_tax-table-selector <tax-table-selector>}
	 *
	 * @param string $tax_selector this value should correspond to the name
	 *                             of an alternate-tax-table.
	 *
	 * @return void
	 */
	function SetTaxTableSelector( $tax_selector ) {
		$this->tax_table_selector = $tax_selector;
	}

	/**
	 * Used when the item's content is digital, sets whether the merchant will
	 * send an email to the buyer explaining how to access the digital content.
	 * Email delivery allows the merchant to charge the buyer for an order
	 * before allowing the buyer to access the digital content.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_email-delivery <email-delivery>}
	 *
	 * @param bool $email_delivery true if email_delivery applies, defaults to
	 *                             false
	 *
	 * @return void
	 */
	function SetEmailDigitalDelivery( $email_delivery = 'false' ) {
		$this->digital_url         = '';
		$this->digital_key         = '';
		$this->digital_description = '';
		$this->email_delivery      = $email_delivery;
		$this->digital_content     = true;
	}

	/**
	 * Sets the information related to the digital delivery of the item.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_digital-content <digital-content>}
	 *
	 * @param string $digital_url the url the customer must go to download the
	 *                            item. --optional
	 * @param string $digital_key the key which allows to download or unlock the
	 *                            digital content item -- optional
	 * @param string $digital_description instructions for downloading adigital
	 *                                    content item, 1024 characters max, can
	 *                                    contain xml-escaped HTML -- optional
	 *
	 * @return void
	 */
	function SetURLDigitalContent( $digital_url, $digital_key, $digital_description ) {
		$this->digital_url         = $digital_url;
		$this->digital_key         = $digital_key;
		$this->digital_description = $digital_description;
		$this->email_delivery      = 'false';
		$this->digital_content     = true;
	}
}


/*
 * Copyright (C) 2007 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
/**
 * Classes used to represent shipping types
 * @version $Id: googleshipping.php 1234 2007-09-25 14:58:57Z ropu $
 */

/**
 * Class that represents flat rate shipping
 *
 * info:
 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_flat-rate-shipping}
 * {@link http://code.google.com/apis/checkout/developer/index.html#shipping_xsd}
 *
 */
class MspFlatRateShipping {

	var $price;
	var $name;
	var $type = "flat-rate-shipping";
	var $shipping_restrictions;

	/**
	 * @param string $name a name for the shipping
	 * @param double $price the price for this shipping
	 */
	function MspFlatRateShipping( $name, $price ) {
		$this->name  = $name;
		$this->price = $price;
	}

	/**
	 * Adds a restriction to this shipping.
	 *
	 * @param GoogleShippingFilters $restrictions the shipping restrictions
	 */
	function AddShippingRestrictions( $restrictions ) {
		$this->shipping_restrictions = $restrictions;
	}
}

/**
 *
 * Shipping restrictions contain information about particular areas where
 * items can (or cannot) be shipped.
 *
 * More info:
 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_shipping-restrictions}
 *
 * Address filters identify areas where a particular merchant-calculated
 * shipping method is available or unavailable. Address filters are applied
 * before Google Checkout sends a <merchant-calculation-callback> to the
 * merchant. Google Checkout will not ask you to calculate the cost of a
 * particular shipping method for an address if the address filters in the
 * Checkout API request indicate that the method is not available for the
 * address.
 *
 * More info:
 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_address-filters}
 */
class MspShippingFilters {

	var $allow_us_po_box = true;

	var $allowed_restrictions = false;
	var $excluded_restrictions = false;

	var $allowed_world_area = false;
	var $allowed_country_codes_arr;
	var $allowed_postal_patterns_arr;
	var $allowed_country_area;
	var $allowed_state_areas_arr;
	var $allowed_zip_patterns_arr;

	var $excluded_country_codes_arr;
	var $excluded_postal_patterns_arr;
	var $excluded_country_area;
	var $excluded_state_areas_arr;
	var $excluded_zip_patterns_arr;

	function MspShippingFilters() {
		$this->allowed_country_codes_arr   = array();
		$this->allowed_postal_patterns_arr = array();
		$this->allowed_state_areas_arr     = array();
		$this->allowed_zip_patterns_arr    = array();

		$this->excluded_country_codes_arr   = array();
		$this->excluded_postal_patterns_arr = array();
		$this->excluded_state_areas_arr     = array();
		$this->excluded_zip_patterns_arr    = array();
	}

	/**
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_allow-us-po-box <allow-us-po-box>}
	 *
	 * @param bool $allow_us_po_box whether to allow delivery to PO boxes in US,
	 * defaults to true
	 */
	function SetAllowUsPoBox( $allow_us_po_box = true ) {
		$this->allow_us_po_box = $allow_us_po_box;
	}

	/**
	 * Set the world as allowed delivery area.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_world-area <world-area>}
	 *
	 * @param bool $world_area Set worldwide allowed shipping, defaults to true
	 */
	function SetAllowedWorldArea( $world_area = true ) {
		$this->allowed_restrictions = true;
		$this->allowed_world_area   = $world_area;
	}

	// Allows

	/**
	 * Add a postal area to be allowed for delivery.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_postal-area <postal-area>}
	 *
	 * @param string $country_code 2-letter iso country code
	 * @param string $postal_pattern Pattern that matches the postal areas to
	 * be allowed, as defined in {@link http://code.google.com/apis/checkout/developer/index.html#tag_postal-code-pattern}
	 */
	function AddAllowedPostalArea( $country_code, $postal_pattern = "" ) {
		$this->allowed_restrictions          = true;
		$this->allowed_country_codes_arr[]   = $country_code;
		$this->allowed_postal_patterns_arr[] = $postal_pattern;
	}

	/**
	 * Add a us country area to be allowed for delivery.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-country-area <us-country-area>}
	 *
	 * @param string $country_area the area to allow, one of "CONTINENTAL",
	 * "FULL_50_STATES" or "ALL"
	 *
	 */
	function SetAllowedCountryArea( $country_area ) {
		switch ( $country_area ) {
			case "CONTINENTAL_48":
			case "FULL_50_STATES":
			case "ALL":
				$this->allowed_country_area = $country_area;
				$this->allowed_restrictions = true;
				break;
			default:
				$this->allowed_country_area = "";
				break;
		}
	}

	/**
	 * Allow shipping to areas specified by state.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-state-area <us-state-area>}
	 *
	 * @param array $areas Areas to be allowed
	 */
	function SetAllowedStateAreas( $areas ) {
		$this->allowed_restrictions    = true;
		$this->allowed_state_areas_arr = $areas;
	}

	/**
	 * Allow shipping to areas specified by state.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-state-area <us-state-area>}
	 *
	 * @param string $area Area to be allowed
	 */
	function AddAllowedStateArea( $area ) {
		$this->allowed_restrictions      = true;
		$this->allowed_state_areas_arr[] = $area;
	}

	/**
	 * Allow shipping to areas specified by zip patterns.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-zip-area <us-zip-area>}
	 *
	 * @param array $zips
	 */
	function SetAllowedZipPatterns( $zips ) {
		$this->allowed_restrictions     = true;
		$this->allowed_zip_patterns_arr = $zips;
	}

	/**
	 * Allow shipping to area specified by zip pattern.
	 *
	 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-zip-area <us-zip-area>}
	 *
	 * @param string
	 */
	function AddAllowedZipPattern( $zip ) {
		$this->allowed_restrictions       = true;
		$this->allowed_zip_patterns_arr[] = $zip;
	}

	/**
	 * Exclude postal areas from shipping.
	 *
	 * @see AddAllowedPostalArea
	 */
	function AddExcludedPostalArea( $country_code, $postal_pattern = "" ) {
		$this->excluded_restrictions          = true;
		$this->excluded_country_codes_arr[]   = $country_code;
		$this->excluded_postal_patterns_arr[] = $postal_pattern;
	}

	/**
	 * Exclude state areas from shipping.
	 *
	 * @see SetAllowedStateAreas
	 */
	function SetExcludedStateAreas( $areas ) {
		$this->excluded_restrictions    = true;
		$this->excluded_state_areas_arr = $areas;
	}

	/**
	 * Exclude state area from shipping.
	 *
	 * @see AddAllowedStateArea
	 */
	function AddExcludedStateArea( $area ) {
		$this->excluded_restrictions      = true;
		$this->excluded_state_areas_arr[] = $area;
	}

	/**
	 * Exclude shipping to area specified by zip pattern.
	 *
	 * @see SetAllowedZipPatterns
	 */
	function SetExcludedZipPatternsStateAreas( $zips ) {
		$this->excluded_restrictions     = true;
		$this->excluded_zip_patterns_arr = $zips;
	}

	/**
	 * Exclude shipping to area specified by zip pattern.
	 *
	 * @see AddExcludedZipPattern
	 */
	function SetAllowedZipPatternsStateArea( $zip ) {
		$this->excluded_restrictions       = true;
		$this->excluded_zip_patterns_arr[] = $zip;
	}

	/**
	 * Exclude shipping to country area
	 *
	 * @see SetAllowedCountryArea
	 */
	function SetExcludedCountryArea( $country_area ) {
		switch ( $country_area ) {
			case "CONTINENTAL_48":
			case "FULL_50_STATES":
			case "ALL":
				$this->excluded_country_area = $country_area;
				$this->excluded_restrictions = true;
				break;

			default:
				$this->excluded_country_area = "";
				break;
		}
	}
}

/**
 * Used as a shipping option in which neither a carrier nor a ship-to
 * address is specified
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_pickup} <pickup>
 */
class MspPickUp {

	var $price;
	var $name;
	var $type = "pickup";

	/**
	 * @param string $name the name of this shipping option
	 * @param double $price the handling cost (if there is one)
	 */
	function MspPickUp( $name, $price ) {
		$this->price = $price;
		$this->name  = $name;
	}
}


/*
 * Copyright (C) 2006 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Classes used to handle tax rules and tables
 */

/**
 * Represents a tax rule
 *
 * @see GoogleDefaultTaxRule
 * @see GoogleAlternateTaxRule
 *
 * @abstract
 */
class MspTaxRule {

	var $tax_rate;

	var $world_area = false;
	var $country_codes_arr;
	var $postal_patterns_arr;
	var $state_areas_arr;
	var $zip_patterns_arr;
	var $country_area;

	function MspTaxRule() {
	}

	function SetWorldArea( $world_area = true ) {
		$this->world_area = $world_area;
	}

	function AddPostalArea( $country_code, $postal_pattern = "" ) {
		$this->country_codes_arr[]   = $country_code;
		$this->postal_patterns_arr[] = $postal_pattern;
	}

	function SetStateAreas( $areas ) {
		if ( is_array( $areas ) ) {
			$this->state_areas_arr = $areas;
		} else {
			$this->state_areas_arr = array( $areas );
		}
	}

	function SetZipPatterns( $zips ) {
		if ( is_array( $zips ) ) {
			$this->zip_patterns_arr = $zips;
		} else {
			$this->zip_patterns_arr = array( $zips );
		}
	}

	function SetCountryArea( $country_area ) {
		switch ( $country_area ) {
			case "CONTINENTAL_48":
			case "FULL_50_STATES":
			case "ALL":
				$this->country_area = $country_area;
				break;
			default:
				$this->country_area = "";
				break;
		}
	}
}

/**
 * Represents a default tax rule
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_default-tax-rule <default-tax-rule>}
 */
class MspDefaultTaxRule extends MspTaxRule {

	var $shipping_taxed = false;

	function MspDefaultTaxRule( $tax_rate, $shipping_taxed = "false" ) {
		$this->tax_rate       = $tax_rate;
		$this->shipping_taxed = $shipping_taxed;

		$this->country_codes_arr   = array();
		$this->postal_patterns_arr = array();
		$this->state_areas_arr     = array();
		$this->zip_patterns_arr    = array();
	}
}

/**
 * Represents an alternate tax rule
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_alternate-tax-rule <alternate-tax-rule>}
 */
class MspAlternateTaxRule extends MspTaxRule {

	function MspAlternateTaxRule( $tax_rate ) {
		$this->tax_rate = $tax_rate;

		$this->country_codes_arr   = array();
		$this->postal_patterns_arr = array();
		$this->state_areas_arr     = array();
		$this->zip_patterns_arr    = array();
	}

}


/**
 * Represents an alternate tax table
 *
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_alternate-tax-table <alternate-tax-table>}
 */
class MspAlternateTaxTable {

	var $name;
	var $tax_rules_arr;
	var $standalone;

	function MspAlternateTaxTable( $name = "", $standalone = "false" ) {
		if ( $name != "" ) {
			$this->name          = $name;
			$this->tax_rules_arr = array();
			$this->standalone    = $standalone;
		}
	}

	function AddAlternateTaxRules( $rules ) {
		$this->tax_rules_arr[] = $rules;
	}
}


class MspCustomFields {
	var $fields = array();

	function AddField( $field ) {
		$this->fields[] = $field;
	}

	function GetXml() {
		$xml_data = new msp_gc_XmlBuilder();

		$xml_data->Push( 'custom-fields' );
		foreach ( $this->fields as $field ) {
			$xml_data->Push( 'field' );

			if ( $field->standardField ) {
				$xml_data->Element( 'standardtype', $field->standardField );
			}

			if ( $field->name ) {
				$xml_data->Element( 'name', $field->name );
			}
			if ( $field->type ) {
				$xml_data->Element( 'type', $field->type );
			}
			if ( $field->default ) {
				$xml_data->Element( 'default', $field->default );
			}
			if ( $field->savevalue ) {
				$xml_data->Element( 'savevalue', $field->savevalue );
			}
			if ( $field->label ) {
				$this->_GetXmlLocalized( $xml_data, 'label', $field->label );
			}

			if ( ! empty( $field->options ) ) {
				$xml_data->Push( 'options' );
				foreach ( $field->options as $option ) {
					$xml_data->Push( 'option' );
					$xml_data->Element( 'value', $option->value );
					$this->_GetXmlLocalized( $xml_data, 'label', $option->label );
					$xml_data->Pop( 'option' );
				}
				$xml_data->Pop( 'options' );
			}

			if ( ! empty( $field->validation ) ) {
				foreach ( $field->validation as $validation ) {
					$xml_data->Push( 'validation' );
					$xml_data->Element( $validation->type, $validation->data );
					$this->_GetXmlLocalized( $xml_data, 'error', $validation->error );
					$xml_data->Pop( 'validation' );
				}
			}

			if ( $field->filter ) {
				$xml_data->Push( 'field-restrictions' );

				if ( ! empty( $field->filter->allowed_country_codes_arr ) ) {
					$xml_data->Push( 'allowed-areas' );
					foreach ( $field->filter->allowed_country_codes_arr as $country_code ) {
						$xml_data->Push( 'postal-area' );
						$xml_data->Element( 'country-code', $country_code );
						$xml_data->Pop( 'postal-area' );
					}
					$xml_data->Pop( 'allowed-areas' );
				}

				if ( ! empty( $field->filter->excluded_country_codes_arr ) ) {
					$xml_data->Push( 'excluded-areas' );
					foreach ( $field->filter->excluded_country_codes_arr as $country_code ) {
						$xml_data->Push( 'postal-area' );
						$xml_data->Element( 'country-code', $country_code );
						$xml_data->Pop( 'postal-area' );
					}
					$xml_data->Pop( 'excluded-areas' );
				}

				$xml_data->Pop( 'field-restrictions' );
			}

			$xml_data->Pop( 'field' );
		}
		$xml_data->Pop( 'custom-fields' );

		return $xml_data->GetXML();
	}

	function _GetXmlLocalized( &$xml_data, $field, $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $lang => $text ) {
				$xml_data->Element( $field, $text, array( 'xml:lang' => $lang ) );
			}
		} else {
			$xml_data->Element( $field, $value );
		}
	}
}


class MspCustomField {
	var $standardField = null;

	var $name = null;
	var $type = null;
	var $label = null;
	var $default = null;
	var $savevalue = null;
	var $options = array();
	var $validation = array();
	var $filter = null;

	function MspCustomField( $name = null, $type = null, $label = null ) {
		$this->name  = $name;
		$this->type  = $type;
		$this->label = $label;
	}

	function AddOption( $value, $label ) {
		$this->options[] = new MspCustomFieldOption( $value, $label );
	}

	function AddValidation( $validation ) {
		$this->validation[] = $validation;
	}

	function AddRestrictions( $filter ) {
		$this->filter = $filter;
	}
}

class MspCustomFieldOption {
	var $value;
	var $label;

	function MspCustomFieldOption( $value, $label ) {
		$this->value = $value;
		$this->label = $label;
	}
}

class MspCustomFieldValidation {
	var $type;
	var $data;
	var $error;

	function MspCustomFieldValidation( $type, $data, $error ) {
		$this->type  = $type;
		$this->data  = $data;
		$this->error = $error;
	}
}

class MspCustomFieldFilter {
	var $allowed_country_codes_arr;
	var $excluded_country_codes_arr;

	function MspCustomFieldFilter() {
		$this->allowed_country_codes_arr  = array();
		$this->excluded_country_codes_arr = array();
	}

	function AddAllowedPostalArea( $country_code ) {
		$this->allowed_country_codes_arr[] = $country_code;
	}

	function AddExcludedPostalArea( $country_code ) {
		$this->excluded_country_codes_arr[] = $country_code;
	}
}


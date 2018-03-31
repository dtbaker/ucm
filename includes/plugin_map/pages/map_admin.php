<?php
$search = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
?>


<style>
	#map-canvas {
		width: 100%;
		height: 700px;
	}

	#map-canvas img {
		max-width: none !important;
		width: auto !important;
		height: auto !important;
		display: inline;
	}

	#panel {
		position: absolute;
		top: 5px;
		left: 50%;
		margin-left: -180px;
		z-index: 5;
		background-color: #fff;
		padding: 5px;
		border: 1px solid #999;
	}
</style>

<script
	src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars( module_config::c( 'google_maps_api_key', 'AIzaSyDFYt1ozmTn34lp96W0AakC-tSJVzEdXjk' ) ); ?>&callback=initializeMaps"
	async defer></script>
<script>
    var geocoder;
    var map;
    var infowindow = false;

    function createInfoWindow(item, content) {
        if (!infowindow) {
            infowindow = new google.maps.InfoWindow({});
        }
        google.maps.event.addListener(item, 'click', function (event) {
            infowindow.close();
            infowindow = new google.maps.InfoWindow({
                content: content,
                position: event.latLng
            });
            infowindow.open(map, item);
        });
    }

    var customer_address = [];
		<?php
		$customer_addresses = array();
		$customers = module_customer::get_customers( array(
			'customer_id' => isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ? (int) $_REQUEST['customer_id'] : false
		), array( 'columns' => 'c.customer_id, c.customer_name' ) );
		foreach ( $customers as $customer ) {
			$address = module_address::get_address( $customer['customer_id'], 'customer', 'physical' );
			if ( ! empty( $address ) ) {
				$address_count            = 0;
				$customer['full_address'] = '';
				foreach ( array( 'line_1', 'line_2', 'suburb', 'state', 'region', 'country', 'post_code' ) as $key ) {
					if ( ! empty( $address[ $key ] ) ) {
						$address_count ++;
						$customer['full_address'] .= $address[ $key ] . ', ';
					}
					$customer[ $key ] = $address[ $key ];
				}
				if ( $address_count > 1 ) {
					$customer['address_id']   = $address['address_id'];
					$customer['full_address'] = rtrim( $customer['full_address'], ', ' );
					$customer['address_hash'] = md5( serialize( $address ) );
					$geocode                  = get_single( 'map', 'address_id', $address['address_id'] );
					if ( $geocode ) {
						// check hash matches - ie address hasn't changed.
						if ( $geocode['address_hash'] == $customer['address_hash'] ) {
							$customer = array_merge( $customer, $geocode );
						}
					}
					$customer_addresses[] = $customer;
				}
			}
		}
		foreach($customer_addresses as $customer_address){ ?>
    customer_address.push(<?php echo json_encode( $customer_address );?>);
		<?php }
		?>


    function initializeMaps() {
        geocoder = new google.maps.Geocoder();
			<?php if(! empty( $search['location'] )){ ?>
        geocoder.geocode({'address': '<?php echo addcslashes( preg_replace( "#\s+#", " ", $search['location'] ), "'" );?>'}, function (results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                var mapOptions = {
                    zoom: 10,
                    center: results[0].geometry.location
                };
                map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
                var marker = new google.maps.Marker({
                    map: map,
                    icon: 'https://maps.gstatic.com/mapfiles/ridefinder-images/mm_20_green.png',
                    position: results[0].geometry.location
                });
            } else {
                alert('Address not found: ' + status);
            }
        });
			<?php }else{ ?>
        var latlng = new google.maps.LatLng(-34.397, 150.644);
        var mapOptions = {
            zoom: 8,
            center: latlng
        };
        map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
			<?php } ?>

        var bounds = new google.maps.LatLngBounds();
        var timeout_index = 0;
        for (var i = 0; i < customer_address.length; i++) {
            if (typeof customer_address[i].lat != 'undefined') {
                // already have it in db
                var pos = new google.maps.LatLng(customer_address[i].lat, customer_address[i].lng);
                customer_address[i].marker = new google.maps.Marker({
                    map: map,
                    position: pos
                });
                createInfoWindow(customer_address[i].marker, 'Customer: <a href="<?php $link = module_customer::link_open( 1 ); echo $link; echo strpos( $link, '?' ) ? '&' : '?';?>customer_id=' + customer_address[i].customer_id + '">' + customer_address[i].customer_name + '</a><br/>' + customer_address[i].full_address);
                bounds.extend(pos);
            } else {
                // have to geocode this one.
                timeout_index++;
                (function () {
                    var index = i;
                    setTimeout(function () {
                        var address = customer_address[index].full_address;
                        var cust = customer_address[index];
                        geocoder.geocode({'address': address}, function (results, status) {
                            if (status == google.maps.GeocoderStatus.OK) {
                                $.ajax({
                                    type: "POST",
                                    url: '<?php echo full_link( '?m=map&p=map_admin&_process=ajax_save_map_coords' );?>',
                                    data: {
                                        address_id: cust.address_id,
                                        address_hash: cust.address_hash,
                                        lat: results[0].geometry.location.lat(),
                                        lng: results[0].geometry.location.lng()
                                    },
                                    dataType: "json",
                                    success: function (d) {
                                    }
                                });
                                customer_address[index].marker = new google.maps.Marker({
                                    map: map,
                                    position: results[0].geometry.location
                                });
                                createInfoWindow(customer_address[index].marker, 'Customer: <a href="<?php $link = module_customer::link_open( 1 ); echo $link; echo strpos( $link, '?' ) ? '&' : '?';?>customer_id=' + cust.customer_id + '">' + cust.customer_name + '</a><br/>' + address);
                                bounds.extend(results[0].geometry.location);
															<?php if(empty( $search['location'] )){ ?>
                                map.fitBounds(bounds);
															<?php } ?>
                            } else {
                                console.log('Address ' + address + ' not found: ' + status);
                            }
                        });
                    }, 300 * timeout_index);
                })();
            }
        }
			<?php if(empty( $search['location'] )){ ?>
        map.fitBounds(bounds);
			<?php } ?>
    }

</script>

<form action="" method="post">

	<?php module_form::print_form_auth(); ?>

	<?php $search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Location:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[location]',
					'value' => isset( $search['location'] ) ? $search['location'] : '',
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );

	?>
</form>
<div id="map-canvas"></div>
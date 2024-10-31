<?php
/*
** Helper Functions
*/


/* 
**========== Direct access not allowed =========== 
*/
if( ! defined('ABSPATH' ) ){ exit; }


/* 
**========== Print Array =========== 
*/
function postis_pa($arr){
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/* 
**========== Get Order ID =========== 
*/
function postis_get_order_id( $order ) {
		
	$class_name = get_class ($order);
	if( $class_name != 'WC_Order' ) 
		return $order -> ID;
	
	if ( version_compare( WC_VERSION, '2.7', '<' ) ) {  
		// vesion less then 2.7
		return $order -> id;
	} else {
		return $order -> get_id();
	}
}

/* 
**========== Get Product Weight =========== 
*/
function postis_product_weight( $product ) {
	
	if ( version_compare( WC_VERSION, '2.7', '<' ) ) {  
		// vesion less then 2.7
		return (float)$product -> weight;
	} else {
		return (float)$product -> get_weight();
	}
}

/* 
**========== Get Product Width =========== 
*/
function postis_product_width( $product ) {
	if ( version_compare( WC_VERSION, '2.7', '<' ) ) {  
		// vesion less then 2.7
		return (int)$product -> width;
	} else {
		return (int)$product -> get_width();
	}
}

/* 
**========== Get Product Height =========== 
*/
function postis_product_height( $product ) {
	if ( version_compare( WC_VERSION, '2.7', '<' ) ) {  
		// vesion less then 2.7
		return (int)$product -> height;
	} else {
		return (int)$product -> get_height();
	}
}

/* 
**========== Get Product Length =========== 
*/
function postis_product_length( $product ) {
	if ( version_compare( WC_VERSION, '2.7', '<' ) ) {  
		// vesion less then 2.7
		return (int)$product -> length;
	} else {
		return (int)$product -> get_length();
	}
}

/* 
**========== Load Templates =========== 
*/
function postis_load_template( $template_name, $vars = null) {

    if( $vars != null && is_array($vars) ){
        extract( $vars );
    };

    $template_path =  POSTIS_PATH . "/templates_hpos/{$template_name}";
    if( file_exists( $template_path ) ){
        require ( $template_path );
    } else {
        die( "Error while loading file {$template_path}" );
    }
}

/* 
**========== Postis Dir Setup =========== 
*/
function postis_files_setup_get_directory( $sub_dir=false ) {
    
    $upload_dir = wp_upload_dir ();
		
	$parent_dir = $upload_dir ['basedir'] . '/' . POSTIS_PDF_DIR . '/';
	$thumb_dir  = $parent_dir . 'thumbs/';
	
	if($sub_dir){
		$sub_dir = $parent_dir . $sub_dir . '/';
		if(wp_mkdir_p($sub_dir)){
			return $sub_dir;
		}
	}elseif(wp_mkdir_p($parent_dir)){
		if(wp_mkdir_p($thumb_dir)){
			return $parent_dir;
		}
	}
}

/* 
**========== Return API Key from settings =========== 
*/
function postis_get_api_key() {

	$api_key = postis_get_settings( 'apikey' );
	
	return $api_key;
}

/* 
**========== Return Plugin Mode =========== 
*/
function postis_get_plugin_mode() {
	
	$mode = postis_get_settings( 'shipping_mode' );
	
	return $mode;
}

/* 
**========== Postis Endpoints =========== 
*/
function postis_get_endpoints( $type ) {
	
	$postis_endpoint = '';
	switch( $type ) {
		
		case 'tracking':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mobiz.posturinn.is/api/v1/shipments';
			} else {
				$postis_endpoint = 'https://api.mobiz.posturinn.is/api/v1/shipments';
			}
		break;
		
		case 'pdf':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/shipments/';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/shipments/';
			}
		break;
		
		case 'create_shipment':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/shipments/create';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/shipments/create';
			}
		break;
		
		case 'calculate_shipping':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/deliveryservicesandprices';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/deliveryservicesandprices';
			}
		break;
		
		case 'billing_kennitala_validation':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mobiz.posturinn.is/api/v1/identification-number-validation?identificationNumber=';
			} else {
				$postis_endpoint = 'https://api.mobiz.posturinn.is/api/v1/identification-number-validation?identificationNumber=';
			}
		break;
		
		case 'postboxes':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/postboxes';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/postboxes';
			}
		break;
		
		case 'parcelpoints':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/parcelpoints';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/parcelpoints';
			}
		break;
		
		case 'print':
			if( postis_get_plugin_mode() == 'live' ) {
				$postis_endpoint = 'https://api.mappan.is/wscm/v1/print';
			} else {
				$postis_endpoint = 'https://apitest.mappan.is/wscm/v1/print';
			}
		break;
	}
	
	return apply_filters('postis_endpoints', $postis_endpoint, $type);
}

/* 
**========== Get Saved Postis Shipping Settings =========== 
*/
function postis_get_settings($id){
	
	$shipping_settings = get_option( 'woocommerce_postis_settings' ); 

	$value       = isset($shipping_settings[$id]) ? $shipping_settings[$id] : '';
	return $value;
}

/* 
**========== Check Shipment is ready =========== 
*/
function is_shipment_ready($order_id){

	$order = wc_get_order( $order_id );
  
	$return_key = false;
	
	$ship_methods  = $order->get_shipping_methods();
	$order_status  = $order->get_status();
	
    foreach( $ship_methods as $item_id => $shipping_item_obj ){
	    
	    $method_id = $shipping_item_obj->get_method_id();
	    
		if ( ($order_status == 'processing' || $order_status == 'completed') && $method_id == 'postis') {
			$return_key = true;
		}
	}
	
	return $return_key;
}


/* 
**========== Add description to DPT and DPO =========== 
*/
add_action('woocommerce_before_order_itemmeta', 'append_description_to_shipping_method', 10, 3);
function append_description_to_shipping_method($item_id, $item, $product) {
    if ($item->is_type('shipping')) {
        $order = $item->get_order();
        $dpo_postbox = $order->get_meta('postis_dpo_postbox');
        $shipping_method = $order->get_meta('postis_shipping_method');

        // If postis_shipping_method is DPO and postis_dpo_postbox exists and is not empty
        if ($shipping_method === 'DPO' && !empty($dpo_postbox)) {
            $endpoint_url   = postis_get_endpoints( 'postboxes' );
            $postis_apikey  = postis_get_api_key();
            $domain = $_SERVER['HTTP_HOST'];
            $req_headers    = array(
            	"headers" => array(
            		'x-api-key' => $postis_apikey, 
            		'domain_name' => $domain,
					'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
            		'WooCommerce-Version' => postis_get_woocommerce_version(),
            		'Plugin-Version' => postis_get_plugin_version()
            	)
            );

            $response = wp_remote_get($endpoint_url, $req_headers);
            if (!is_wp_error($response) && $response['response']['code'] == 200) {
                $postboxes = json_decode(wp_remote_retrieve_body($response), true);

                if (isset($postboxes['postboxes'])) {
                    foreach ($postboxes['postboxes'] as $postbox) {
                        if ($postbox['postboxId'] == $dpo_postbox) {
                            $dpo_postbox_name = $postbox['name'];
                            break;
                        }
                    }
                }
            }

            if (isset($dpo_postbox_name)) {
                echo '<small>' . esc_html($dpo_postbox_name) . '</small>';
            }
        }
        // If postis_shipping_method is DPT, echo the contents of postis_dpt_parcelpoints
        elseif ($shipping_method === 'DPT') {
            $dpt_parcelpoints = $order->get_meta('postis_dpt_parcelpoints');
            if (!empty($dpt_parcelpoints)) {
                echo '<small>' . esc_html($dpt_parcelpoints) . '</small>';
            }
        }
    }
}

/* 
**========== Get DPO Postboxes Options =========== 
*/
function postis_get_dpo_postboxes(){
	
    $endpoint_url   = postis_get_endpoints( 'postboxes' );
    $postis_apikey  = postis_get_api_key();
    
    $domain	= $_SERVER['HTTP_HOST'];
    
    $req_headers    = array(
    	"headers" => array(
    		'x-api-key'=>$postis_apikey, 
    		'domain_name'=> $domain,
			'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
            'WooCommerce-Version' => postis_get_woocommerce_version(),
            'Plugin-Version' => postis_get_plugin_version()
    	)
    );
    
    // Run the query
    $response = wp_remote_get( $endpoint_url, $req_headers );
    
    if( ! is_wp_error($response) && $response['response']['code'] == 200 ) {
        
        $postboxes = json_decode( wp_remote_retrieve_body($response), true);
        
        //postis_pa($postboxes);
        
        if( ! isset($postboxes['postboxes']) ) return '';
        
		uasort($postboxes['postboxes'], function($a, $b){
			$shipping_postcode = WC()->customer->get_shipping_postcode();
		    return abs(intval($shipping_postcode)-intval($a['postcode'])) - abs(intval($shipping_postcode)-intval($b['postcode']));
		});
		
        // Generating key => val array for postboxes
        $postboxes_array = array(''=>__( '--- Choose postbox ----', 'postis' ));
        foreach( $postboxes['postboxes'] as $postbox ) {
            
            $postbox_id     = $postbox['postboxId'];
            $postbox_name   = $postbox['name'];
            $postboxes_array[$postbox_id] = $postbox_name;
        }
        
        // Create select fields for show postboxes options
        woocommerce_form_field( 'postis_dpo_postbox', array(
                                'type'          => 'select',
                                'class'         => array('postis_dpo_postbox form-row-wide'),
                                'label'         => __('Select PostBox', 'postis'),
                                'required'    => true,
                                // 'return'    => true,
                                'options'     => $postboxes_array,
                                'default' => '')
                            );
        
        // Create select number for show mobile number                        
    	woocommerce_form_field( 'postis_dpo_phonenumber', array(
                                'type'          => 'tel',
                                'class'         => array('postis_dpo_phonenumber form-row-wide'),
                                'label'         => __('Phone Number', 'postis'),
                                'placeholder' 	=> __('Phone Number', 'postis'),
                                'required'    => true,
                                'default' => '')
                            );
    }
}


/* 
**========== Get DPO Postboxes Options =========== 
*/
function postis_get_dpt_parcelpoints(){
	
	$API = new POSTIS_API();
    
    $response = $API->parcelpoints_api_request();
    
    if( ! is_wp_error($response) && $response['response']['code'] == 200 ) {
        
        $parcelpoints = json_decode( wp_remote_retrieve_body($response), true);
        
        // postis_pa($parcelpoints);
        
        if( ! isset($parcelpoints['parcelPoints']) ) return '';
        
		uasort($parcelpoints['parcelPoints'], function($a, $b){
			$shipping_postcode = WC()->customer->get_shipping_postcode();
		    return abs($shipping_postcode-$a['postcode']) - abs($shipping_postcode-$b['postcode']);
		});
		
        $parcelpoints_array = array(''=>__( '--- Choose parcel ----', 'postis' ));
        foreach( $parcelpoints['parcelPoints'] as $parcel_meta ) {
            
            $parcel_id   = isset($parcel_meta['parcelPointId']) ? $parcel_meta['parcelPointId'] : '';
            $parcel_name = isset($parcel_meta['name']) ? $parcel_meta['name'] : '';

            $parcelpoints_array[$parcel_name] = $parcel_name;
        }

        // Create select fields for show parce options
        woocommerce_form_field(
        	'postis_dpt_parcelpoints', 
        	array(
                'type'          => 'select',
                'class'         => array('postis_dpt_parcelpoints form-row-wide'),
                'label'         => __('Select Parcel', 'postis'),
                'required'    => true,
                'options'     => $parcelpoints_array,
                'default' => ''
        	)
    	);
    	
    	// Create select number for show mobile number                        
    	woocommerce_form_field( 
    		'postis_dpt_phonenumber', 
    		array(
                'type'          => 'tel',
                'class'         => array('postis_dpo_phonenumber form-row-wide'),
                'label'         => __('Phone Number', 'postis'),
                'required'    => true,
                'default' => ''
            )
        );
    }
}


function postis_get_dpo_postboxes_by_list(){
	
    $endpoint_url   = postis_get_endpoints( 'postboxes' );
    $postis_apikey  = postis_get_api_key();
    
    $domain	= $_SERVER['HTTP_HOST'];
    
    $req_headers    = array(
    	"headers" => array(
    		'x-api-key'=>$postis_apikey, 
    		'domain_name'=> $domain,
			'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
            'WooCommerce-Version' => postis_get_woocommerce_version(),
            'Plugin-Version' => postis_get_plugin_version()
    	)
    );
    
    // Run the query
    $response = wp_remote_get( $endpoint_url, $req_headers );
    
    if( ! is_wp_error($response) && $response['response']['code'] == 200 ) {
        
        $postboxes = json_decode( wp_remote_retrieve_body($response), true);
        
        // postis_pa($postboxes);
        
        if( ! isset($postboxes['postboxes']) ) return '';
        
		uasort($postboxes['postboxes'], function($a, $b){
			$shipping_postcode = WC()->customer->get_shipping_postcode();
			if (empty($shipping_postcode)) {
				$shipping_postcode = '0';
			}
		    return abs($shipping_postcode-$a['postcode']) - abs($shipping_postcode-$b['postcode']);
		});
    }
    
    return $postboxes;
}

function postis_get_dpt_parcelpoints_by_list(){
	
    $endpoint_url   = postis_get_endpoints( 'parcelpoints' );
    $postis_apikey  = postis_get_api_key();
    
    $domain	= $_SERVER['HTTP_HOST'];
    
    $req_headers    = array(
    	"headers" => array(
    		'x-api-key'=>$postis_apikey, 
    		'domain_name'=> $domain,
			'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
            'WooCommerce-Version' => postis_get_woocommerce_version(),
            'Plugin-Version' => postis_get_plugin_version()
    	)
    );
    
    // Run the query
    $response = wp_remote_get( $endpoint_url, $req_headers );
    
    if( ! is_wp_error($response) && $response['response']['code'] == 200 ) {
        
        $postboxes = json_decode( wp_remote_retrieve_body($response), true);
        
        // postis_pa($postboxes);
        
        if( ! isset($postboxes['postboxes']) ) return '';
        
		uasort($postboxes['postboxes'], function($a, $b){
			$shipping_postcode = WC()->customer->get_shipping_postcode();
			if (empty($shipping_postcode)) {
				$shipping_postcode = '0';
			}
		    return abs($shipping_postcode-$a['postcode']) - abs($shipping_postcode-$b['postcode']);
		});
    }
    
    return $postboxes;
}

/* 
**========== Check Shipment is International or Domestic =========== 
*/
function postis_is_shipment_international($country_code){
	
	$international_shipment = true;
	if ($country_code === "IS") {
		$international_shipment = false;
	}
	
	return $international_shipment;
}

/* 
**========== Get shipping Fail Options =========== 
*/
function postis_package_deliver_fail_options(){
    $packages = array(    
        'A'  => __('Resend', 'postis'),
        'X'  => __('Destroy', 'postis')
        );

   return apply_filters('postis_is_package_deliver_fails_options',$packages);
}

/* 
**========== Get Content Delivery Option =========== 
*/
function postis_content_delivery_options(){
	$options = array(    
        'G'  => __('Gift', 'postis'),
        'D'  => __('Document', 'postis'),
        'S'  => __('Sample', 'postis'),
        'O'  => __('Sales Product', 'postis'),
        'R'  => __('Resent', 'postis'),
        'O'  => __('Other', 'postis')
        );

   return apply_filters('postis_content_delivery_options',$options);
}

/* 
**========== Get Contents Fields For international Shipment =========== 
*/
function postis_international_contents_fields(){
	$options = array(    
        'descriptionOfContents'    => array('label'     => __('Description', 'postis'),
        									'id'        => 'descriptionOfContents',
        									'display'   => 'yes',
        									'maxlength' => 49,
        									'required'  => 'yes',
        									'readonly'  => 'no',
        									'default'   => ''
        							),
		'goodsQuantity'    => array('label'     => __('QTY', 'postis'),
									'id'        => 'goodsQuantity',
									'display'   => 'yes',
									'maxlength' => '',
									'required'  => 'yes',
									'readonly'  => 'yes',
									'default'   => ''
        							),
		'valueForCustoms'    => array(  'label'     => __('Value', 'postis'),
										'id'        => 'valueForCustoms',
										'display'   => 'yes',
										'maxlength' => '',
										'required'  => 'yes',
										'readonly'  => 'yes',
										'default'   => ''
        							),
		'valueForCustomsCurrency'    => array('label'   => __('Currency', 'postis'),
											'id'        => 'valueForCustomsCurrency',
											'display'   => 'yes',
											'maxlength' => '',
											'required'  => 'yes',
											'readonly'  => 'yes',
											'default'   => ''
        							),
		'hsTariffNumber'    => array('label'    => __('TariffNumber', 'postis'),
									'id'        => 'hsTariffNumber',
									'display'   => 'yes',
									'maxlength' => '',
									'required'  => 'yes',
									'readonly'  => 'no',
									'default'   => ''
        							),
		'countryOfOrigin'    => array('label'   => __('Country', 'postis'),
									'id'        => 'countryOfOrigin',
									'display'   => 'yes',
									'maxlength' => '',
									'required'  => 'yes',
									'readonly'  => 'no',
									'default'   => 'IS'
        							),
        );

   return apply_filters('postis_international_contents_fields',$options);
}

/* 
**========== Get Saved Shipment Data =========== 
*/
function postis_get_shipment_data($order_id, $shipmet_key){
    
    $wc_order = wc_get_order($order_id);
    $shipment_meta = $wc_order->get_meta( 'postis_shipment_meta' );
    //$shipment_meta = get_post_meta($order_id, 'postis_shipment_meta', true );
     
    $postval       = isset($shipment_meta[$shipmet_key]) ? $shipment_meta[$shipmet_key] : '';
    
    return $postval;
}


<?php
/**
 * Postis API Manager Class
**/

/*
**========== Direct access not allowed =========== 
*/ 
if( ! defined('ABSPATH') ) die('Not Allowed');

class POSTIS_API {
    
    private static $ins;
    
    /*
     * this var use to get domain name
    */
    var $domain;
    
    /*
     * this var use to get domain name
    */
    var $apikey;
    
    /*
     * this var use to get tracking shipment endpoint
    */
    var $tracking_ep;
    
    /*
     * this var use to get create shipment endpoint
    */
    var $create_shipment_ep;
    
    /*
     * this var use to get postbox endpoint
    */
    var $postboxes_ep;
    
    /*
     * this var use to get pdf endpoint
    */
    var $pdf_ep;
    
    /*
     * this var use to get print shipment endpoint
    */
    var $print_shipment_ep;
    
    /*
     * this var use to get calculate shipping endpoint
    */
    var $calculate_shipping_ep;
    
    
    function __construct() {
        
        $this ->domain = $_SERVER['HTTP_HOST'];
        
        $this ->apikey = postis_get_settings( 'apikey' );
        
        $this ->tracking_ep = postis_get_endpoints( 'tracking' );
        
        $this ->create_shipment_ep = postis_get_endpoints( 'create_shipment' );
        
        $this ->postboxes_ep = postis_get_endpoints( 'postboxes' );
        
        $this ->pdf_ep = postis_get_endpoints( 'pdf' );
        
        $this ->print_shipment_ep = postis_get_endpoints( 'print' );
        
        $this ->calculate_shipping_ep = postis_get_endpoints( 'calculate_shipping' );
        
        $this ->parcelpoints = postis_get_endpoints( 'parcelpoints' );
    }
    
    
    /*
    **======== Create shipment API Request ========= 
    */
    function create_shipment_api_request($order_id, $bulk_shipment, $postmeta = array()){
        
        $order = wc_get_order( $order_id );
        
        // get all order details
        $ship_methods          = $order->get_shipping_methods(); 
        $shipping_customerName = substr($order->get_formatted_shipping_full_name(), 0, 44);
        $shipping_address1     = substr($order->get_shipping_address_1(), 0, 35);
        $shipping_address2     = substr($order->get_shipping_address_2(), 0, 35);
        $shipping_postcode     = $order->get_shipping_postcode();
        $country_code          = $order->get_shipping_country();
        $city                  = substr($order->get_shipping_city(), 0, 35);
        $customer_email        = $order->get_billing_email();
        $customer_phone        = $order->get_billing_phone();

        if ($country_code === 'US') {
            $shipping_state = $order->get_shipping_state();
            $shipping_postcode = $shipping_state.' '.$shipping_postcode;
        }
        
        $international_shipment = postis_is_shipment_international($country_code);
        
        $numberofitems   = isset($postmeta['numberofitems']) ? intval($postmeta['numberofitems']) : '';
        $shipmnet_desc   = isset($postmeta['description']) ? sanitize_text_field($postmeta['description']) : '';
        $shipmnet_desc   = sprintf(__("W: %s", "postis"), $shipmnet_desc);
        
        // Get Dynamic Options
        $dynamic_opt_control        = array();
        $dynamic_shipment_options   = array();
        
        if(isset($postmeta['optional_services']) && is_array($postmeta['optional_services'])){
            $dynamic_shipment_options = array_map('sanitize_text_field', $postmeta['optional_services']);
        }
        
        foreach($dynamic_shipment_options as $opt_name => $opt_val){
            if ( isset($dynamic_shipment_options[$opt_name]) && $dynamic_shipment_options[$opt_name] == 'on' ) {
                $dynamic_opt_control[$opt_name] = true;       
            }else{
                $dynamic_opt_control[$opt_name] = $opt_val;       
            }
        }
        
        // international shipment content meta
        $new_contents_meta = array();
        if(isset($postmeta['contents']) && is_array($postmeta['contents'])){
            foreach ($postmeta['contents'] as $item_id => $contents_meta) {
                $new_contents_meta[$item_id] = array_map('sanitize_text_field', $contents_meta);
            }
        }
        
        $valid_phonenumber     = isset($postmeta['phonenumber']) && $postmeta['phonenumber'] != '' ? wc_sanitize_phone_number( $postmeta['phonenumber'] ): $customer_phone;
        $valid_phonenumber = str_replace('+354','',$valid_phonenumber);
        if ( isset($dynamic_opt_control['cod']) && $dynamic_opt_control['cod'] ) {
            $order_total = $order->get_total();
            $dynamic_opt_control['codAmount'] = $order_total;
        }
        
        $shipping_service      = get_post_meta( $order_id, 'postis_shipping_method', true );
        
        if ($shipping_service == 'DPO') {
            
            // Get all postboxes options from API
            $postbox_resp = $this->postboxes_api_request();
            
            $postbox_list         = json_decode( wp_remote_retrieve_body($postbox_resp), true);
            
            $postbox_id          = get_post_meta( $order_id, 'postis_dpo_postbox', true );
            $postbox_phonenumber = get_post_meta( $order_id, 'postis_dpo_phonenumber', true );
            
            $postbox_phonenumber   = isset($postmeta['phonenumber']) && $postmeta['phonenumber'] != '' ? wc_sanitize_phone_number( $postmeta['phonenumber'] ): $postbox_phonenumber;
            
            if( ! is_wp_error($postbox_resp) && $postbox_resp['response']['code'] == 200 ) {
                foreach ($postbox_list['postboxes'] as $index => $postboxes) {
                    
                    if ($postboxes['postboxId'] == $postbox_id) {
                        
                        $postboxes_address = sprintf(__("Póstbox %s", "postis"), $postboxes['address']);
                        
                        $recipeint = array( 'name'         => $shipping_customerName,
                                            'addressLine1' => $postboxes_address,
                                            "postcode"     => $postboxes['postcode'],
                                            "countryCode"  => $country_code,
                                            "mobilePhone"  => $postbox_phonenumber,
                                            "town"         => $postboxes['town'],
                            );          
                    }
                }
            }
            
        }else if ($shipping_service == 'DPT') {
            
            // Get all postboxes options from API
            $postbox_resp = $this->parcelpoints_api_request();
            
            $postbox_list         = json_decode( wp_remote_retrieve_body($postbox_resp), true);
            
            $parcelpoints_name          = get_post_meta( $order_id, 'postis_dpt_parcelpoints', true );
            $postbox_phonenumber = get_post_meta( $order_id, 'postis_dpt_phonenumber', true );
            
            $postbox_phonenumber   = isset($postmeta['phonenumber']) && $postmeta['phonenumber'] != '' ? wc_sanitize_phone_number( $postmeta['phonenumber'] ): $postbox_phonenumber;
            
            if( ! is_wp_error($postbox_resp) && $postbox_resp['response']['code'] == 200 ) {
                foreach ($postbox_list['parcelPoints'] as $index => $postboxes) {
                    
                    if ($postboxes['name'] == $parcelpoints_name) {
                        
                        // $postboxes_address = sprintf(__("Póstbox %s", "postis"), $postboxes['address']);
                        $parcelpoints_address1 = isset($postboxes['name']) ? $postboxes['name']: '';
                        $parcelpoints_address2 = isset($postboxes['address']) ? $postboxes['address']: '';
                        
                        $recipeint = array( 'name'         => $shipping_customerName,
                                            'addressLine1' => $parcelpoints_address1,
                                            'addressLine2' => $parcelpoints_address2,
                                            "postcode"     => $postboxes['postcode'],
                                            "countryCode"  => $country_code,
                                            "mobilePhone"  => $postbox_phonenumber,
                                            "town"         => $postboxes['town'],
                            );          
                    }
                }
            }
            
        }else{
            if ($shipping_service == 'DPP' OR $shipping_service == 'DPH') {
                if (substr($valid_phonenumber, 0, 1) === '6' || substr($valid_phonenumber, 0, 1) === '7' || substr($valid_phonenumber, 0, 1) === '8') {
                    // phoneNumber is valid
                } else {
                    $valid_phonenumber = '';
                }
            }

            $recipeint = array( 'name'         => $shipping_customerName,
                                'addressLine1' => $shipping_address1,
                                'addressLine2' => $shipping_address2,
                                "postcode"     => $shipping_postcode,
                                "town"         => $city,
                                "countryCode"  => $country_code,
                                "mobilePhone"  => $valid_phonenumber,
                            );
        }
        /*
        if ($international_shipment) {
            $recipeint['town'] = $city;
        }
        */
        $recipeint['email'] =  $customer_email;
        
        if ($bulk_shipment == false) {
            
            $options = array(  'deliveryServiceId' => $shipping_service,
                                'numberOfItems'    => floatval($numberofitems),
                                'description'      => $shipmnet_desc
                            );
            $option_array = array_merge($options, $dynamic_opt_control);
            
            // Request params.
            if ($international_shipment) {
                $params = array('recipient' => $recipeint,
                                'options'   => $option_array,
                                'contents'  => $new_contents_meta,
                            );
            }else{
                $params = array('recipient' => $recipeint,
                                'options'   => $option_array,
                            );
            }
        }else{
            
            $options = array('deliveryServiceId' => $shipping_service);
            
            $params = array(
                        'recipient' => $recipeint,
                        'options'   => $options,
                    );
        }
        
        $params  = array_filter( $params );
        
        // postis_pa($params); exit;
    
        $headers = array(
                    'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8', 
                                            'x-api-key'    => $this ->apikey,
                                            'domain_name'  => $this ->domain,
                                            'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                                            'WooCommerce-Version' => postis_get_woocommerce_version(),
                                            'Plugin-Version' => postis_get_plugin_version()
                                        ),
                    'body'        => json_encode($params),
                    'method'      => 'POST',
                    'data_format' => 'body',
                );
        
        return wp_remote_post( $this ->create_shipment_ep, $headers );
    }
    
    
    /*
    **======== API Request For Tracking Shipment ========= 
    */
    function pdf_shipment_api_request($order_id, $allow_printing = true){
        
        $customerId         = postis_get_shipment_data($order_id, 'customerId');
        $registrationKey    = postis_get_shipment_data($order_id, 'registrationKey');
        $shipmentId         = postis_get_shipment_data($order_id, 'shipmentId');
        
        // PDF Endpoint
        $pdf_endpoint = $this ->pdf_ep.$shipmentId.'/pdf';
                
        $automatic_printing = postis_get_settings( 'pdf_print' );
        if ($automatic_printing == 'yes' && $allow_printing) {
            
            $print_resp = $this->print_shipment_api_request($shipmentId);

            $print_resp_code = wp_remote_retrieve_response_code( $print_resp );
        }

        $labelSize = postis_get_settings('postis_labelSize');
        if (empty($labelSize)) {
            $labelSize = 'A4Size'; // Set a default value if postis_labelSize is not found
        }

        $params = array(
            'registrationKey' => $registrationKey,
            'customerId'      => $customerId,
            'inline'          => 'true',
            'labelSize'       => $labelSize
        );
        $params = array_filter( $params );
        
        // Query format parameters.
        $query = add_query_arg( $params, $pdf_endpoint );
    
        $pdf_resp = wp_remote_get( $query ,
                            array( 
                                'timeout' => 10,
                                'headers' => array( 
                                    'accept' => 'application/pdf',
                                    'content-type' => 'application/pdf',
                                    'x-api-key' => $this ->apikey,
                                    'domain_name' => $this ->domain,
                                    'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                                    'WooCommerce-Version' => postis_get_woocommerce_version(),
                                    'Plugin-Version' => postis_get_plugin_version()
                                ) 
                            )
                        );
                        
        return $pdf_resp;
    }
    
    
    /*
    **======== API Request For Tracking Shipment ========= 
    */
    function tracking_shipment_api_request($shipmentId){
        error_log( 'Initiated API request for tracking shipment' );
        
        // Construct the full URL by appending the shipment ID directly to the endpoint
        $endpoint_url = trailingslashit($this->tracking_ep) . $shipmentId;

        // Define the headers
        $req_headers = array(
            "headers" => array(
                'domain_name' => $this->domain,
                'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                'WooCommerce-Version' => postis_get_woocommerce_version(),
                'Plugin-Version' => postis_get_plugin_version()
            )
        );
        
        // Make the GET request to the API
        $response = wp_remote_get($endpoint_url, $req_headers);

        // Check if the response is an error or WP_Error
        if ( is_wp_error( $response ) ) {
            // Log the error message
            error_log( 'Tracking shipment API request failed: ' . $response->get_error_message() );
            
            // Return an appropriate error response or null
            return null;
        }

        // If the request is successful, return the response
        return $response;
    }
    
    
    /*
    **======== API Request For Print Shipment PDF ========= 
    */
    function print_shipment_api_request($shipmentId){
        
        $params = array(
            'shipmentId'   => $shipmentId,
            'outputFormat' => 'poststod'
        );
        
        $params = array_filter( $params );
        
        $query = add_query_arg( $params, $this ->print_shipment_ep );
        
        $header = array(
                'headers' => array(
                    'x-api-key' => $this ->apikey, 
                    'domain_name' => $this ->domain,
                    'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                    'WooCommerce-Version' => postis_get_woocommerce_version(),
                    'Plugin-Version' => postis_get_plugin_version()
                ),
                'method'  => 'POST',
            );
            
        return wp_remote_post( $query, $header );
    }
    
    
    /*
    **======== API Request For Postboxes ========= 
    */
    function postboxes_api_request(){
        
        $req_headers = array(
            "headers" => array(
                'x-api-key' => $this->apikey, 
                'domain_name'=> $this->domain,
                'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                'WooCommerce-Version' => postis_get_woocommerce_version(),
                'Plugin-Version' => postis_get_plugin_version()
            )
        );
        
        return wp_remote_get( $this->postboxes_ep, $req_headers );
    }
    
    
    /*
    **======== Calculate Postis Shipping & Get All Options ========= 
    */
    function calculate_shipping_api_request($params){
        
        $params      = array_filter( $params );
        $query       = add_query_arg( $params, $this->calculate_shipping_ep );
        
        $req_headers = array("headers" => array(
                                'x-api-key'   => $this->apikey, 
                                'domain_name' => $this->domain,
                                'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                                'WooCommerce-Version' => postis_get_woocommerce_version(),
                                'Plugin-Version' => postis_get_plugin_version()
                                )
                            );
        
        return wp_remote_get( $query, $req_headers );
    }
    
    
    /*
    **======== Parcelpoints API Request to Get all parcel lockers ========= 
    */
    function parcelpoints_api_request(){
        
        $req_headers = array(
            "headers" => array(
                'x-api-key'   => $this->apikey, 
                'domain_name' => $this->domain,
                'WooCommerce-Version' => postis_get_woocommerce_version(),
                'Plugin-Version' => postis_get_plugin_version()
            )
        );
        
        return wp_remote_get( $this ->parcelpoints, $req_headers );
    }
    
    
    public static function get_instance() {
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }
    
}

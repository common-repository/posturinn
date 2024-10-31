<?php 
/**
 * Checkout Form Fields
* */
 
class POSTIS_Checkout_Fields {
	
	private static $ins = null;

    var $billing_kennitala_label;
    var $billing_kennitala_req;
    
	function __construct( ){
	    
	    $this-> billing_kennitala_enable = postis_get_settings( 'billing_kennitala_enable' );
	    $this-> billing_kennitala_label  = postis_get_settings( 'billing_kennitala_label' );
	    $this-> billing_kennitala_req    = postis_get_settings( 'billing_kennitala_req' ) == 'yes' ? true : false;
	    
    	   // Add extra billing fields
	    if ($this-> billing_kennitala_enable == 'yes') {
            add_filter( 'woocommerce_billing_fields', array($this, 'billing_section_fields') );
	    }
        
        // Process Checkout fields
        add_action('woocommerce_checkout_process', array($this, 'custom_checkout_field_process') );
        
        // add_action('woocommerce_checkout_fields', array($this, 'handle_checkout_fields') );
        
        // Saved Custom Fields
        add_action( 'woocommerce_checkout_update_order_meta', array($this, 'save_custom_field_on_order_meta') );
	}
	
	
	/**
     * Add Extra Fields On Billing Section
    */
    function billing_section_fields( $fields ) {
        
        // postis_pa($fields);
        
        $billing_kennitala = array(
            'label'       => $this->billing_kennitala_label,            
            'required'    => $this->billing_kennitala_req,             
            'clear'       => false,             
            'type'        => 'text',              
            'class'       => array('postis_kennitala_class')    
        );
        
        $temp_fields = array();
        
        if( $fields ) {
            foreach($fields as $key => $f){
                $temp_fields[$key] = $f;
                
                if( $key == 'billing_last_name' ){
                    $temp_fields['billing_kennitala'] = $billing_kennitala;
                }
            }
        }
    
        $fields = $temp_fields;
        return $temp_fields;
    }
    
    
    /**
     * Process the checkout
     */
    function custom_checkout_field_process() {
        
        // Custom Billing Kennitala Field Validation
        if ( isset( $_POST['billing_kennitala'] ) ) {
            
            $domain = $_SERVER['HTTP_HOST'];
            $billing_kennitala = sanitize_text_field( $_POST['billing_kennitala'] );
            
            // Get Number Length
            $numlength = strlen((string)$billing_kennitala);

            // Check SSN via API
            $endpoint_url = postis_get_endpoints( 'billing_kennitala_validation' ).$billing_kennitala;
                
            $req_headers = array(
                "headers" => array(
                    'domain_name'=> $domain,
                    'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
                    'WooCommerce-Version' => postis_get_woocommerce_version(),
                    'Plugin-Version' => postis_get_plugin_version()
                )
            );
            
            $response = wp_remote_get( $endpoint_url, $req_headers );

            // Check if the response is an error or WP_Error
            if ( is_wp_error( $response ) ) {
                error_log( 'Kennitala validation API request FAILED: ' . $response->get_error_message() );
                $kennitala_responce = null;
            } else {
                error_log( 'Kennitala validation API request SUCCESS' );
                $kennitala_responce = json_decode( wp_remote_retrieve_body( $response ), true );
            }
            
            // Check if set, if it's not set, add an error.
            if ( $this->billing_kennitala_req && $billing_kennitala ) {
                
                if ( !is_numeric($billing_kennitala) || $numlength !== 10 ) {
                    wc_add_notice( __( 'kennitala field only allows 10 numbers.', 'postis' ), 'error' );
                } else if ( $kennitala_responce && isset( $kennitala_responce['error'] ) && $kennitala_responce['error'] ) {
                    wc_add_notice( __( 'Invalid kennitala field', 'postis' ), 'error' );
                }
            }
        }
        
        // PostBox DPO service required phone number
        if ( isset( $_POST['postis_dpo_phonenumber'] ) && !$_POST['postis_dpo_phonenumber'] ) {
            $postis_dpo_phonenumber = sanitize_text_field( $_POST['postis_dpo_phonenumber'] );
            wc_add_notice( __( 'PostBox Phone Number Required For DPO Service', 'postis' ), 'error' );
        }
        
        // PostBox DPO service required PostBoxes Address
        if ( isset( $_POST['postis_dpo_postbox'] ) && !$_POST['postis_dpo_postbox'] ) {
            $postis_dpo_postbox = sanitize_text_field( $_POST['postis_dpo_postbox'] );
            wc_add_notice( __( 'PostBox Address Required For DPO Service', 'postis' ), 'error' );
        }
        
        // PostBox DPO service required phone number
        if ( isset( $_POST['postis_dpt_phonenumber'] ) && !$_POST['postis_dpt_phonenumber'] ) {
            $postis_dpt_phonenumber = sanitize_text_field( $_POST['postis_dpt_phonenumber'] );
            wc_add_notice( __( 'Parcelpoint Phone Number Required For DPT Service', 'postis' ), 'error' );
        }
        
        // PostBox DPO service required PostBoxes Address
        if ( isset( $_POST['postis_dpt_parcelpoints'] ) && !$_POST['postis_dpt_parcelpoints'] ) {
            $postis_dpt_parcelpoints = sanitize_text_field( $_POST['postis_dpt_parcelpoints'] );
            wc_add_notice( __( 'Parcelpoints Address Required For DPT Service', 'postis' ), 'error' );
        }
    }
    
    
    /**
     * Update the order meta with field value
     */
    function save_custom_field_on_order_meta( $order_id ) {
        
        //postis_pa($_POST); exit;
        
        if ( ! empty( $_POST['billing_kennitala'] ) ) {
            update_post_meta( $order_id, 'billing_kennitala', sanitize_text_field( $_POST['billing_kennitala'] ) );
        }
        
        if ( ! empty( $_POST['postis_dpo_postbox'] ) ) {
            update_post_meta( $order_id, 'postis_dpo_postbox', sanitize_text_field( $_POST['postis_dpo_postbox'] ) );
        }
        
        if ( ! empty( $_POST['postis_dpo_phonenumber'] ) ) {
            update_post_meta( $order_id, 'postis_dpo_phonenumber', sanitize_text_field( $_POST['postis_dpo_phonenumber'] ) );
        }
        
        if ( ! empty( $_POST['postis_dpt_parcelpoints'] ) ) {
            update_post_meta( $order_id, 'postis_dpt_parcelpoints', sanitize_text_field( $_POST['postis_dpt_parcelpoints'] ) );
        }
        
        if ( ! empty( $_POST['postis_dpt_phonenumber'] ) ) {
            update_post_meta( $order_id, 'postis_dpt_phonenumber', sanitize_text_field( $_POST['postis_dpt_phonenumber'] ) );
        }
        
        if ( isset($_POST['shipping_method']) && !empty( $_POST['shipping_method'] ) ) {
            foreach ($_POST['shipping_method'] as $shippment_index => $shipping_service) {
                update_post_meta( $order_id, 'postis_shipping_method', sanitize_text_field( $shipping_service ) );
            }
        }
    }
    
    
    function handle_checkout_fields($fields){
        
        $shipping_method ='DPO'; // Set the desired shipping method to hide the checkout field(s).
        global $woocommerce;
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
        $chosen_shipping = $chosen_methods[0];
        var_dump($chosen_shipping);
        $hide_fields = array( 'billing_address_1', 'billing_address_2', 'billing_phone' );
        
        foreach($hide_fields as $field ) {
            
            if ($chosen_shipping == $shipping_method) {
                $fields['billing'][$field]['required'] = false;
                $fields['billing'][$field]['class'][] = 'hide';
            }
            $fields['billing'][$field]['class'][] = 'postis-billing-controller';
        }
        
        // if ($chosen_shipping == $shipping_method) {
            
        //     unset($fields['billing']['billing_phone']); // Add/change filed name to be hide
        //     // unset($fields['billing']['billing_address_2']);
        // }
        return $fields;
    }

    
    /**
    * Create Class Instance
    */
	public static function get_instance() {
	    
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }
}

postis_checkoutfield_start();
function postis_checkoutfield_start() {
    return POSTIS_Checkout_Fields::get_instance();
}
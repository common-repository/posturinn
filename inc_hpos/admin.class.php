<?php
/**
 * Postis Admin Manager Class
**/

/*
**========== Direct access not allowed =========== 
*/ 
if( ! defined('ABSPATH') ) die('Not Allowed');
    
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class POSTIS_Admin {
    
	private static $ins;
	
	/*
	 * this var use to get API requests
	*/
	var $api;
	
	function __construct() {
	    
	    $this->api = new POSTIS_API();
	    
	    add_filter( 'manage_woocommerce_page_wc-orders_columns', array($this, 'create_order_column'), 20);
        add_filter( 'manage_woocommerce_page_wc-orders_custom_column', array($this, 'create_order_column_data') , 20, 2 );
        
        add_action( 'admin_enqueue_scripts', array($this, 'load_scripts') );
        
        add_action('wp_ajax_postis_create_shipment_action', array($this, 'create_shipment') );
        
        add_action('wp_ajax_postis_show_shipment', array($this, 'display_shipment') );
        
        add_action( 'admin_post_postis_delete_shipment', array($this, 'delete_shipment') );
        
        add_action('wp_ajax_postis_shipment_options', array($this, 'shipment_options') );
        
        add_action( 'admin_post_postis_pdf_action', array($this, 'get_shipment_pdf') );
        
        add_action( 'admin_init', array($this, 'create_metabox') );
        
        add_filter( 'bulk_actions-woocommerce_page_wc-orders', array($this, 'order_bulk_action_option'), 20, 1 );
        add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'order_bulk_action_process'), 10, 3 );
        
        add_action( 'woocommerce_product_options_shipping', array($this, 'create_product_fields') );
        
        add_action('woocommerce_process_product_meta', array($this, 'save_product_fields') );
        
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {

    	    add_action( 'restrict_manage_posts', array($this, 'create_order_filter_dropdown') );
        
            add_filter( 'request', array($this, 'order_filter_query') );	
        }
        
        // Add admin notice
        add_action( 'admin_notices', array($this, 'admin_notice_bar') );

        // automatically create the pdf on status.
        add_action('woocommerce_order_status_changed', array($this, 'generate_shipment_pdf_on_status_change'), 10, 4);
	}
	
	function admin_notice_bar() {
	    
	    $action = isset($_GET['bulk_action']) ? $_GET['bulk_action'] : '';
	    
	    $message    = '';
	    $class      = 'notice-success';
	    switch($action){
	        
	        case 'mark_printed':
	            $printed_marked = isset($_GET['printed_marked']) ? $_GET['printed_marked'] : '';
	            $message = "{$printed_marked} Order(s) marked as printed successfully";
	        break;
	        
	        case 'mark_nonprinted':
	            $nonprinted_marked = isset($_GET['nonprinted_marked']) ? $_GET['nonprinted_marked'] : '';
	            $message = "{$nonprinted_marked} Order(s) marked as non-printed successfully";
	        break;
	    }
	    
	    if( $message ) {
            echo '<div class="notice '.$class.' is-dismissible">';
            echo '<p>'.sprintf(__( '%s', 'postis' ), $message).'</p>';
            echo '</div>';
	    }
    }
	
	
	/*
    **========== WC Order Columns =========== 
    */
	function create_order_column($columns) {
	
        $columns['shipment_column'] = __( 'Shipment', 'postis' );
        $columns['pdf_column']      = __( 'PDF', 'postis' );
        // $columns['printed_column']  = __( 'Printed', 'postis' );
    
        return $columns;
    }
    
    
    /*
    **========== Add WC Order Columns Data  =========== 
    */
    function create_order_column_data( $column, $order_id ) {

        $order_id = $order_id->get_id();
    	$admin_url = admin_url('admin-post.php');
    	$pdf_url   = add_query_arg( array('action'   => 'postis_pdf_action',
    	                                  'order_id' => $order_id
    	                                ),$admin_url );
    	
    	$shipmentId  = postis_get_shipment_data($order_id, 'shipmentId');
        $sender_meta = postis_get_shipment_data($order_id, 'recipient');
    	$shipment_status   = is_shipment_ready($order_id);
    	
        //postis_pa($sender_meta);
        $country_code = isset($sender_meta['countryCode']) ? $sender_meta['countryCode'] : '';
        
        // check if shipment is international
        $is_shipment_international = postis_is_shipment_international($country_code);
        
        // PDF Settings
        $open_pdf_newtab         = postis_get_settings( 'open_pdf_newtab' );
        $open_pdf_status = '_self';
        if ($open_pdf_newtab == 'yes') {
            $open_pdf_status = '_blank';
        }
        
        switch ( $column ) {
    
            case 'shipment_column' :
                
                if ($shipment_status == true) {
                    
                    if ($shipmentId != '') {
                        echo '<a class="postis-view-shipment-js button button-primary" href="#" data-order-id="'.esc_attr($order_id).'">'.__("View Shipment", "postis"). '</a>';
                    }else{
                        echo '<a class="button button-primary postis-shipment-options-js" href="#" data-order-id="'.esc_attr($order_id).'">'. __("Create Shipment", "postis").'</a>';
                    }
                }else{
                    echo '--';
                }
            break;
    
            case 'pdf_column' :
                
                if ($shipment_status == true && $shipmentId != '') {

                    $order = wc_get_order( $order_id );
                    $print_status = $order->get_meta( 'postis_shipment_pdf_printed' );

                    //$print_status = get_post_meta( $order_id, 'postis_shipment_pdf_printed', true );
                    
                    if ($print_status == 'printed') {
                        $tooltip = __('Shipment Printed', 'postis');
                        $icon    = '<span data-tip="'.esc_attr($tooltip).'" class="tips dashicons dashicons-yes" style="color:#00ff00;margin-top: 4px;"></span>';
                    }else{
                        $icon = '';
                    }
                    
                    echo '<a class="button button-primary" href="'.esc_url($pdf_url).'" target="'.esc_attr($open_pdf_status).'">'. __("Get PDF", "postis"). $icon .'</a>';
                }else{
                    echo '--';
                }
            break;
            
        
        }
    }
    
    
    /*
    **========== Load Admin Scripts  =========== 
    */
    function load_scripts($hook) {
    
        $screen = get_current_screen();
        
        if ( isset($screen->post_type) && $screen->post_type == 'shop_order' ) {
            $localize_vars = array( 'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http') ), 
                                    'loader'  => POSTIS_URL.'/images/loader.gif', 
                            );
            add_thickbox();
            
            wp_enqueue_style('postis-admin-css', POSTIS_URL."/css/postis-admin-order.css");
            wp_enqueue_script('postis-admin-js', POSTIS_URL."/js/postis-admin-order.js", array('jquery'), 1.2, true);
            
            wp_localize_script( 'postis-admin-js', 'postis_order_vars', $localize_vars);
        }
        
        if (isset($_GET['page']) && $_GET['page'] == "wc-settings") {
            wp_enqueue_script( 'postis-wc-sortable', POSTIS_URL.'/js/postis-admin-wc.js', array('jquery','jquery-ui-core', 'jquery-ui-sortable'),  1.2, true);
            $css = '
                <style>
                    .postis-hidden-option {
                        display: none;
                    }
                </style>
            ';
            echo $css;
        }
    }
    
    
    /*
    **========== Create Shipment =========== 
    */
    function create_shipment() {

        $order_id   = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $selected_delivery_service_id = isset($_REQUEST['selected_delivery_service_id']) ? sanitize_text_field($_REQUEST['selected_delivery_service_id']) : '';
        $selected_postbox = isset($_REQUEST['postis_dpo_postbox']) ? sanitize_text_field($_REQUEST['postis_dpo_postbox']) : '';
        $selected_phonenr = isset($_REQUEST['phonenumber']) ? sanitize_text_field($_REQUEST['phonenumber']) : '';

        $order = wc_get_order($order_id);

        if (!empty($selected_delivery_service_id)) {
            $order->update_meta_data('postis_shipping_method', $selected_delivery_service_id);
        }

        if (!empty($selected_postbox)) {
            $order->update_meta_data('postis_dpo_postbox', $selected_postbox);
        }

        if (!empty($selected_phonenr)) {
            $order->update_meta_data('postis_dpo_phonenumber', $selected_phonenr);
        }

        $order->save();

        $response = $this->api->create_shipment_api_request($order_id, false, $_REQUEST);
        
        // quick fix for cURL timeouts.
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $ajax_response = array('status' => 'error', 'type' => 'other', 'message' => $error_message);
            wp_send_json($ajax_response);
            return;
        }
        
        $get_shipment_meta = json_decode($response['body'], true);
        
        // postis_pa($get_shipment_meta);

        if ($response['response']['code'] != 400 && $response['response']['code'] != 401) {
            
            $order->update_meta_data('postis_shipment_meta', $get_shipment_meta);
            $order->save();
            
            $ajax_response = array('status' => 'success', 'message' => __('Shipment Created Successfully', 'postis'));
            
        } else {
            
            $resp_message  = sprintf(__("Shipment Not Created.\nServer Response: %s", "postis"), $get_shipment_meta['message']);
            
            if (strpos($get_shipment_meta['message'], 'Ekki var hægt að skrá sendingu (Addressee.Gsm') !== false) {
                $ajax_response = array('status' => 'error', 'type' => 'phonenumber', 'message' => $resp_message);
            } else {
                $ajax_response = array('status' => 'error', 'type' => 'other', 'message' => $resp_message);
            }
        }
        
        wp_send_json($ajax_response);
    }
    
    
    /*
    **========== Display Shipments  =========== 
    */
    function display_shipment(){
	
    	$shipmentId =  postis_get_shipment_data(intval( $_REQUEST['order_id'] ), 'shipmentId');
        
        $resp = $this ->api->tracking_shipment_api_request($shipmentId);
        
        $tracking_info = json_decode( $resp['body'], true );

    	$template_vars = array( 'order_id' => intval( $_REQUEST['order_id'] ),
    	                        'tracking' => $tracking_info 
    	                    );
    
    	postis_load_template( 'shipment-view.php', $template_vars);
    	
    	die(0);
    }
    
    
    /*
    **========== Delete Shipment  =========== 
    */
    function delete_shipment(){
	
    	$order_id = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
            
        $order = wc_get_order( $order_id );
        $order->update_meta_data( 'postis_shipment_meta', array() );
        $order->update_meta_data( 'postis_shipment_pdf_printed', 'not_printed' );
        $order->save();

    	//update_post_meta( $order_id, 'postis_shipment_meta', array() );
    	//update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'not_printed' );
    	
    	$shop_order_url = admin_url('edit.php?post_type=shop_order');
    	wp_redirect($shop_order_url);
    	
    	die(0);
    }
    
    
    /*
    **========== Display Shipment Options =========== 
    */
    function shipment_options(){
        
        $order_id   = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        $selected_delivery_service_id = isset($_REQUEST['deliveryServiceId']) ? sanitize_text_field($_REQUEST['deliveryServiceId']) : '';
        
        $wc_order = wc_get_order($order_id);

        $shipping_country     = $wc_order->get_shipping_country();
        $shipping_postcode    = $wc_order->get_shipping_postcode();
        
        $params = array(
            'postCode'      => $shipping_postcode,
            'countryCode'   => $shipping_country
        );
        
        $resp = $this ->api->calculate_shipping_api_request($params);
        
        $shipping_options     = json_decode( $resp['body'], true );
        
        $template_vars = array(
            'order_id' => $order_id,
            'shipping_options' => $shipping_options,
            'selected_delivery_service_id' => $selected_delivery_service_id
        );

        postis_load_template('shipment-options.php', $template_vars);
        wp_die();
    }

    
    
    /*
    **========== Create Shipment PDF =========== 
    */
    function get_shipment_pdf() {

        $order_id   = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
        
        $this->pdf_shipment_init($order_id, $allow_printing = true);
    }
    
    
    /*
    **========== Create Metabox =========== 
    */
    function create_metabox() {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';
        add_meta_box( 'postis_order_shipment', 
                    __('Postis Shipment', 'postis'),
                    array($this, 'display_order_shipment_metabox'),
                    $screen, 
                    'side', 
                    'default'
                );
    }
    
    
    /*
    **========== Display Content On Shipment Metabox =========== 
    */
    function display_order_shipment_metabox($order){
        
        // Ensure $order is a WC_Order object
        if ( ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order );
        }

        // Use the getter method to get the order ID
        $order_id = $order->get_id();
        
        $admin_url = admin_url('admin-post.php');
    	$pdf_url   = add_query_arg(array('action'=>'postis_pdf_action','order_id'=> $order_id), $admin_url);
    	
        // Getting Shipment Detail
    	$get_shipment_meta = postis_get_shipment_data($order_id, 'shipmentId');
    	$shipment_status   = is_shipment_ready($order_id);
        $sender_meta       = postis_get_shipment_data($order_id, 'recipient');
        $country_code      = isset($sender_meta['countryCode']) ? $sender_meta['countryCode'] : '';
        
        // Check if shipment is international
        $is_shipment_international = postis_is_shipment_international($country_code);
        
        $open_pdf_newtab = postis_get_settings( 'open_pdf_newtab' );
        $open_pdf_status = '_self';
        if ($open_pdf_newtab == 'yes') {
            $open_pdf_status = '_blank';
        }
    
        // Check Shipment Ready and Create or View
        if ($shipment_status == true) {
            
            if ($get_shipment_meta != '') {
                echo '<a class="postis-view-shipment-js button button-primary" href="#" data-order-id="'.esc_attr($order_id).'">'.__("View Shipment", "postis"). '</a>';
            }else{
                echo '<a class="button button-primary postis-shipment-options-js" href="#" data-order-id="'.esc_attr($order_id).'">'. __("Create Shipment", "postis").'</a>';
            }
        }else{
            echo __("Shipment Available After Order Completed", "postis");
        }
                
        // Get Created Shipment PDF
        if ($shipment_status == true && $get_shipment_meta != '') {

            $print_status = $order->get_meta( 'postis_shipment_pdf_printed' );

            //$print_status = get_post_meta( $order_id, 'postis_shipment_pdf_printed', true );
                    
            if ($print_status == 'printed') {
                $tooltip = __('Shipment Printed', 'postis');
                $icon    = '<span data-tip="'.esc_attr($tooltip).'" class="tips dashicons dashicons-yes" style="color:#00ff00;margin-top: 4px;"></span>';
            }else{
                $icon = '';
            }
            
            echo '<br><br><a class="button button-primary" href="'.esc_url($pdf_url).'" target="'.esc_attr($open_pdf_status).'">'. __("Get PDF", "postis"). $icon .'</a>';
        }
    }
    
    
    /*
    **========== Display Bulk Action Option =========== 
    */
    function order_bulk_action_option( $actions ) {
        $actions['create_shipment']     = __( 'Pósturinn - Create Shipments', 'postis' );
        $actions['print_pdf_shipment']  = __( 'Pósturinn - Print Shipment PDF', 'postis' );
        $actions['mark_as_printed']     = __( 'Pósturinn - Mark as printed', 'postis' );
        $actions['mark_as_not_printed'] = __( 'Pósturinn - Mark as not printed', 'postis' );
        
        return $actions;
    }
    
    
    /*
    **========== Process Order Bulk Action =========== 
    */
    function order_bulk_action_process( $redirect_to, $action, $order_ids ) {
    
        $processed_ids     = array();
        $processed_success = array();
        $processed_failed  = array();
        $pdf_files         = array();
        $recipeint         = array();
        $postis_changed = 0;
        
        switch( $action) {
            
            case 'create_shipment':
                
                foreach ( $order_ids as $order_id ) {
                    
                    $shipment_ready = is_shipment_ready($order_id);
                    
                    if ($shipment_ready) {
                        
                        $response = $this->api->create_shipment_api_request($order_id, true, $postmeta = array());
                
                        $get_shipment_meta = json_decode( $response['body'], true );
                        
                        if(  $response['response']['code'] != 400 && $response['response']['code'] != 401 ) {
                            $order = wc_get_order( $order_id );
                            $order->update_meta_data( 'postis_shipment_meta', $get_shipment_meta );
                            $order->save();
                            //update_post_meta( $order_id, 'postis_shipment_meta', $get_shipment_meta );
                            
                            $processed_success[] = $order_id;
                        }else{
                            $processed_failed[] = $order_id;
                        }
                        
                        $processed_ids[] = $order_id;
                    }else{
                        $processed_failed[] = $order_id;
                    }
                    
                    $postis_changed++;
                }
                
                $bulk_resp = array( 'bulk_action' => 'create_shipment',
                                    'changed'     => count( $processed_ids ),
                                    'success'     => implode('+', $processed_success),
                                    'failed'      => implode('+', $processed_failed),
                                );
            break;
            
            case 'print_pdf_shipment':
            
                foreach ( $order_ids as $order_id ) {
                    
                    $pdf_files[] = $this->bulk_pdf_shipment_init($order_id, $allow_printing = false);
                    
                    $processed_ids[] = $order_id;
                    $postis_changed++;
                }
                
                if ($postis_changed) {
                    $pdf_path = postis_files_setup_get_directory().'/postis_pdf.pdf';
                    
                    postis_merge_pdf_files( $pdf_files, $pdf_path);
                    
                    $fp = fopen($pdf_path, "r") ;

                    header("Cache-Control: maxage=1");
                    header("Pragma: public");
                    header("Content-type: application/pdf");
                    header("Content-Disposition: inline; filename=postis_pdf.pdf");
                    header("Content-Description: PHP Generated Data");
                    header("Content-Transfer-Encoding: binary");
                    header('Content-Length:' . filesize($pdf_path));
                    ob_clean();
                    flush();
                    while (!feof($fp)) {
                       $buff = fread($fp, 1024);
                       print $buff;
                    }
                    exit;
                }
                
                $bulk_resp = array( 'bulk_action' => 'print_pdf_shipment',
                                    'changed'     => count( $processed_ids ),
                                    'success'     => implode('+', $processed_ids),
                                );
            break;
            
            case 'mark_as_printed':
            
                foreach ( $order_ids as $order_id ) {
                    $order = wc_get_order( $order_id );
                    $order->update_meta_data( 'postis_shipment_pdf_printed', 'printed' );
                    $order->save();
                    //update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'printed' );
                    
                    $processed_ids[] = $order_id;
                    $postis_changed++;
                }
                
                $bulk_resp = array( 'bulk_action' => 'mark_as_printed',
                                    'changed'     => count( $processed_ids ),
                                    'success'     => implode('+', $processed_ids),
                                );
            break;
            
            case 'mark_as_not_printed':
            
                foreach ( $order_ids as $order_id ) {
                    $order = wc_get_order( $order_id );
                    $order->update_meta_data( 'postis_shipment_pdf_printed', 'not_printed' );
                    $order->save();
                    //update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'not_printed' );
                    
                    $processed_ids[] = $order_id;
                    $postis_changed++;
                }
                
                $bulk_resp = array( 'bulk_action' => 'mark_as_not_printed',
                                    'changed'     => count( $processed_ids ),
                                    'success'     => implode('+', $processed_ids),
                                );
            break;
        }
        
        if ($postis_changed) {
            
            $redirect_to = add_query_arg( $bulk_resp , $redirect_to );
        }
        
        return esc_url_raw($redirect_to);
    }
    
    
    /*
    **======== Create Custom Product Fields On Shipping Tab ========= 
    */
    function create_product_fields() {
        
        global $woocommerce, $post;
        echo '<div class="options_group">';
            woocommerce_wp_text_input( 
                array( 
                    'id'          => 'hsTariffNumber', 
                    'label'       => __( 'Tarriff Number', 'postis' ), 
                    'placeholder' => '',
                    'desc_tip'    => 'true',
                    'description' => __( 'Tarriff Number. Needs to be 6 or 8 numbers', 'postis' ) 
                )
            );
            woocommerce_wp_text_input( 
                array( 
                    'id'          => 'descriptionOfContents', 
                    'label'       => __( 'Description in english', 'postis' ), 
                    'placeholder' => '',
                    'desc_tip'    => 'true',
                    'description' => __( 'This needs to be written in english!', 'postis' ) 
                )
            );
        echo '</div>';
    }
    
    
    /*
    **======== Save Admin Product Meta ========= 
    */
    function save_product_fields($post_id) {

        $hsnumber     = isset($_POST['hsTariffNumber']) ? $_POST['hsTariffNumber'] : '';
        $descContents = isset($_POST['descriptionOfContents']) ? $_POST['descriptionOfContents'] : '';
    
        update_post_meta($post_id, 'hsTariffNumber', sanitize_text_field($hsnumber));
        update_post_meta($post_id, 'descriptionOfContents', sanitize_text_field($descContents));
    }
    
    
    /*
    **======== Create Order Filter Dropdown Options ========= 
    */
    function create_order_filter_dropdown() {
        
    	global $typenow;
    
    	if ( 'shop_order' === $typenow ) {
    
    		?>
    		<select name="postis_filter_by_shipment" id="postis_filter_by_shipment">
    			<option value=""><?php esc_html_e( 'Shipment Print Status', 'postis' ); ?></option>
    			<option value="printed"><?php esc_html_e( 'Printed', 'postis' ); ?></option>
    			<option value="not_printed"><?php esc_html_e( 'Not printed', 'postis' ); ?></option>
    		</select>
    		<?php
    	}
    }
    
    
    /*
    **======== WC Order Query ========= 
    */
    function order_filter_query( $vars ) {
        
    	global $typenow;
    
    	if ( 'shop_order' === $typenow && isset( $_GET['postis_filter_by_shipment'] ) && ! empty( $_GET['postis_filter_by_shipment'] ) ) {
    
    		$vars['meta_key']   = 'postis_shipment_pdf_printed';
    		$vars['meta_value'] = wc_clean( $_GET['postis_filter_by_shipment'] );
    	}
    
    	return $vars;
    }
    
    
    /*
    **======== Single PDF Generator ========= 
    */
    function pdf_shipment_init($order_id, $allow_printing = true){
        
        $shipmentId   = postis_get_shipment_data($order_id, 'shipmentId');
        $sender_meta  = postis_get_shipment_data($order_id, 'recipient');
        $options_meta = postis_get_shipment_data($order_id, 'options');
        
        $numberofitems = !empty($options_meta['numberOfItems']) ? $options_meta['numberOfItems'] : 1;
        $country_code = isset($sender_meta['countryCode']) ? $sender_meta['countryCode'] : '';
        
        $is_shipment_international = postis_is_shipment_international($country_code);
        
        // Get pdf settings
        $pdf_width         = postis_get_settings( 'pdf_width' );
        $pdf_height        = postis_get_settings( 'pdf_height' );
        $pdf_rotate        = postis_get_settings( 'pdf_rotate' );
        $pdf_orientation   = postis_get_settings( 'pdf_orientation' );
        $pdf_moveright     = postis_get_settings( 'pdf_moveright' );
        $pdf_movedown      = postis_get_settings( 'pdf_movedown' );
        $pdf_biggerbarcode = postis_get_settings( 'pdf_biggerbarcode' );
        
        $pdf_request = $this->api->pdf_shipment_api_request($order_id, $allow_printing = true);
    
        if ( is_wp_error( $pdf_request ) || wp_remote_retrieve_response_code( $pdf_request ) != 200 ) {
            echo wp_remote_retrieve_body( $pdf_request );

            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'postis_shipment_pdf_printed', 'not_printed' );
            $order->save();

            //update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'not_printed' );
        } else {
    
            $response = wp_remote_retrieve_body( $pdf_request );
            
            // save PDF
            $this->postis_generate_pdf( $response, $shipmentId );
            
            $pdf_name = "{$shipmentId}.pdf";
            
            $pdf_args = array(  'pdf_width'         => $pdf_width,
                                'pdf_height'        => $pdf_height,
                                'pdf_rotate'        => $pdf_rotate,
                                'pdf_orientation'   => $pdf_orientation,
                                'pdf_moveright'     => $pdf_moveright,
                                'pdf_movedown'      => $pdf_movedown,
                                'pdf_biggerbarcode' => $pdf_biggerbarcode,
                                'numberOfItems'     => $numberofitems,
                                );

            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'postis_shipment_pdf_printed', 'printed' );
            $order->save();
            
            postis_process_pdf( $pdf_name, $pdf_args, $is_shipment_international, $order_id);

            // Check if automatic printing is enabled and allow_printing is true
            $automatic_printing = postis_get_settings('pdf_print');
            if ($automatic_printing == 'yes' && $allow_printing) {
                $print_resp = $this->api->print_shipment_api_request($shipmentId);
                $print_resp_code = wp_remote_retrieve_response_code($print_resp);
            }

            //if ($allow_printing) {
            //    header("Content-type: application/pdf");
            //    header("Content-disposition: inline;filename=" . $pdf_name);
            //    exit;
            //}
        }
    }
    
    
    /*
    **======== multiple PDF Generator ========= 
    */
    function bulk_pdf_shipment_init($order_id, $allow_printing = true){
        
        $shipmentId   = postis_get_shipment_data($order_id, 'shipmentId');
        $sender_meta  = postis_get_shipment_data($order_id, 'recipient');
        $options_meta = postis_get_shipment_data($order_id, 'options');
        
        $numberofitems = !empty($options_meta['numberOfItems']) ? $options_meta['numberOfItems'] : 1;
        $country_code = isset($sender_meta['countryCode']) ? $sender_meta['countryCode'] : '';
        
        $is_shipment_international = postis_is_shipment_international($country_code);
        
        // Get pdf settings
        $pdf_width         = postis_get_settings( 'pdf_width' );
        $pdf_height        = postis_get_settings( 'pdf_height' );
        $pdf_rotate        = postis_get_settings( 'pdf_rotate' );
        $pdf_orientation   = postis_get_settings( 'pdf_orientation' );
        $pdf_moveright     = postis_get_settings( 'pdf_moveright' );
        $pdf_movedown      = postis_get_settings( 'pdf_movedown' );
        $pdf_biggerbarcode = postis_get_settings( 'pdf_biggerbarcode' );
        
        $path_pdf   = postis_files_setup_get_directory('pdf')."/{$shipmentId}.pdf";
        
        $pdf_request = $this->api->pdf_shipment_api_request($order_id, $allow_printing = true);
    
        if ( is_wp_error( $pdf_request ) || wp_remote_retrieve_response_code( $pdf_request ) != 200 ) {
            echo wp_remote_retrieve_body( $pdf_request );

            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'postis_shipment_pdf_printed', 'not_printed' );
            $order->save();

            //update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'not_printed' );
        } else {
    
            $response = wp_remote_retrieve_body( $pdf_request );
            
            // save PDF
            $this->postis_generate_pdf( $response, $shipmentId );
            
            $pdf_name = "{$shipmentId}.pdf";
            
            $pdf_args = array(  'pdf_width'         => $pdf_width,
                                'pdf_height'        => $pdf_height,
                                'pdf_rotate'        => $pdf_rotate,
                                'pdf_orientation'   => $pdf_orientation,
                                'pdf_moveright'     => $pdf_moveright,
                                'pdf_movedown'      => $pdf_movedown,
                                'pdf_biggerbarcode' => $pdf_biggerbarcode,
                                'numberOfItems'     => $numberofitems,
                                );

            $order = wc_get_order( $order_id );
            $order->update_meta_data( 'postis_shipment_pdf_printed', 'printed' );
            $order->save();

            //update_post_meta( $order_id, 'postis_shipment_pdf_printed', 'printed' );
            
            return postis_files_setup_get_directory('pdf').$pdf_name;
        }
    }
    
    
    /*
    **======== Save PDF In Directory ========= 
    */
    function postis_generate_pdf( $response, $shipment_id ) {
        
        error_log('Generating PDF for shipment: '.$shipment_id);

        $path_pdf   = postis_files_setup_get_directory('pdf')."/{$shipment_id}.pdf";
        $pdf_fp     = fopen($path_pdf, 'w');
        
        fwrite($pdf_fp, $response);
        fclose($pdf_fp);
    }
    
	
	public static function get_instance() {
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }


    /*
    **======= Automatically create the pdf on status =======
    */
    function generate_shipment_pdf_on_status_change($order_id, $old_status, $new_status, $order) {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'mark_completed') {
            return; // Quick fix. Skip PDF generation for bulk actions
        }
        #error_log("generate_shipment_pdf_on_status_change triggered for order ID: " . $order_id);

        $auto_generate_status = postis_get_settings('pdf_auto_generate_status');
        #error_log("Auto-generate status: " . $auto_generate_status);

        if (empty($auto_generate_status)) {
            $auto_generate_status = 'completed';
            #error_log("Using default auto-generate status: " . $auto_generate_status);
        }

        $auto_generate_status = str_replace('wc-','',$auto_generate_status);

        if ($order->has_shipping_method('postis') && $new_status === $auto_generate_status) {
            #error_log("New status matches auto-generate status");

            $shipmentId = postis_get_shipment_data($order_id, 'shipmentId');
            #error_log("Shipment ID: " . $shipmentId);

            if (empty($shipmentId)) {
                #error_log("No shipment ID found for order ID: " . $order_id);
                #error_log("Creating shipment for order ID: " . $order_id);

                $response = $this->api->create_shipment_api_request($order_id, false, array());
                $get_shipment_meta = json_decode($response['body'], true);

                if ($response['response']['code'] != 400 && $response['response']['code'] != 401) {
                    $order->update_meta_data('postis_shipment_meta', $get_shipment_meta);
                    $order->save();
                    #error_log("Shipment created successfully for order ID: " . $order_id);

                    $shipmentId = postis_get_shipment_data($order_id, 'shipmentId');
                    #error_log("New shipment ID: " . $shipmentId);

                    if (!empty($shipmentId)) {
                        #error_log("Generating PDF for order ID: " . $order_id);
                        //$this->pdf_shipment_init($order_id, $allow_printing = false);
                        $add_tracking_to_email = postis_get_settings('add_tracking_to_email');
                        if ($add_tracking_to_email === 'yes') {
                            $trackingNumber = postis_get_shipment_data($order_id, 'shipmentId');
                            if (!empty($trackingNumber)) {
                                $tracking_url = 'https://posturinn.is/einstaklingar/mottaka/finna-sendingu/?q=' . $trackingNumber;
                                
                                // Load the email template file
                                ob_start();
                                include plugin_dir_path(__FILE__) . 'tracking-email-template.php';
                                $message = ob_get_clean();
                                
                                // Get the WooCommerce email settings
                                $email_from = get_option('woocommerce_email_from_address');
                                $email_from_name = get_option('woocommerce_email_from_name');
                                
                                // Set the "From" email address and name
                                add_filter('wp_mail_from', function() use ($email_from) {
                                    return $email_from;
                                });
                                add_filter('wp_mail_from_name', function() use ($email_from_name) {
                                    return $email_from_name;
                                });
                                
                                // Send the tracking details email
                                $subject = __('Þú átt von á sendingu', 'postis');
                                $headers = array('Content-Type: text/html; charset=UTF-8');
                                $recipient = $order->get_billing_email();
                                wp_mail($recipient, $subject, $message, $headers);
                                
                                // Remove the filters after sending the email
                                remove_filter('wp_mail_from', 'wp_mail_from');
                                remove_filter('wp_mail_from_name', 'wp_mail_from_name');
                            }
                        }
                    }
                } else {
                    error_log("Failed to create shipment for order ID: " . $order_id);
                    error_log("Server response: " . $get_shipment_meta['message']);
                }
            } else {
                error_log("Shipment already exists for order ID: " . $order_id);
                error_log("Generating PDF for order ID: " . $order_id);
                $this->pdf_shipment_init($order_id, $allow_printing = false);
            }
        } else {
            error_log("Order does not have the 'postis' shipping method or new status does not match auto-generate status");
        }
    }


    
}



postis_admin_init();
function postis_admin_init() {
	return POSTIS_Admin::get_instance();
}
<?php
/*
 * this is main plugin class
 * Posturinn
*/


if ( class_exists( 'WC_Shipping_Method' ) ) {

	class POSTIS_Shipping extends WC_Shipping_Method {
		
		var $services;

		function __construct(){
	
			$this->id                 	  = 'postis';
			$this->title				  = "Pósturinn's Shipping";
			$this->postis_services_option = 'woocommerce_postis_services';
			// $this->method_title 		  = sprintf(__( "%s"), $this->get_option( 'title' ) );
			$this->method_title 		  = __( 'Shipping with Posturinn', 'postis' );
			$this->method_description 	  = __( 'Posturinn Shipping allow you delivery in Iceland', 'postis' ); //
			// $this->enabled            	  = "yes"; // This can be added as an setting but for this example its forced enabled
			$this->enabled            	  = $this->get_option( 'enabled' );
			$this -> quote_method 	      =  $this->get_option( 'quote_method' );
			$this -> base_postcode 	      = $this->get_option( 'postcode' );
			$this -> error_message	      = $this->get_option('error_message');
			$this -> custom_shipping_label_cart     = $this -> get_option('custom_shipping_label_cart');
			$this -> custom_shipping_label_checkout = $this -> get_option('custom_shipping_label_checkout');
			$this -> debug				  = $this -> get_option('debug');
			$this->log                    = new WC_Logger();
			$this->weight_unit            = get_option( 'woocommerce_weight_unit' );
    		$this->dimens_unit            = get_option( 'woocommerce_dimension_unit' );
    		$this->apikey                 = $this->get_option( 'apikey' );
    		$this->default_products_weight= 0;
			
			// Hardcode Services List
			
			$this -> services = array(
						'DPH' => 'Pakki Heim',
						'DPP' => 'Pakki Pósthús',
						'DPO' => 'Pakki Póstbox',
						'DPL' => 'Pakki Landspóstur',
						'OLP' => 'Smápakki til útlanda',
						'OIJ' => 'Pakki til Útlanda',
						'DPT' => 'Pakki Pakkaport',
					);
			$this->init();
			
			add_filter('gettext', array($this, 'translate_strings'), 20, 3);
			
			add_filter( 'woocommerce_package_rates', array($this, 'hide_shipping_method'), 10, 2 );
			
			add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'add_shipping_method_description'), 10, 2);

		}
		
		
		/**
		 * Init your settings
		 *
		 * @access public
		 * @return void
		 */
		function init() {
			
			// Load the settings API
			$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
			$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
		
			// Save settings in admin if you have any defined
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_postis_rates' ) );
			
			$this->get_postis_services();
		}
		
		// Todo: Can we do this in a better way?
		public function add_shipping_method_description($label, $method) {
			if ($method->method_id === $this->id) {
				$meta_data = $method->get_meta_data();
				$description = isset($meta_data['description']) ? $meta_data['description'] : '';
				if (!empty($description)) {
					if (strpos($label, $description) === false) {
						$label .= '<br><small>' . esc_html($description) . '</small>';
					}
				}
			}
			return $label;
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 */
		function init_form_fields() {

		    $order_statuses = wc_get_order_statuses();
		    $order_status_options = array();
		    foreach ($order_statuses as $status_slug => $status_name) {
		        $order_status_options[$status_slug] = $status_name;
		    }

			$this->form_fields = array(
					
					'enabled' => array(
							'title' => __( 'Enable/Disable', 'postis' ),
							'type' => 'checkbox',
							'label' => __( 'Enable', 'postis' ),
							'default' => 'no'
					),
					'title' => array(
							'title' => __( 'Method Title', 'postis' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'postis' ),
							'default' => __( 'Shipping with Posturinn', 'postis' ),
							'desc_tip'		=> true,
					),
					'postcode' => array(
							'title' => __( 'Base Postcode', 'postis' ),
							'type' => 'text',
							'description' => __( 'Provide base post code', 'postis' ),
							'default' => '',
							'desc_tip'		=> true,
					),
					'apikey' => array(
							'title' => __( 'API Key', 'postis' ),
							'type' => 'text',
							'description' => __( 'Provide API Key received from Posturinn', 'postis' ),
							'default' => '',
							'desc_tip'		=> true,
					),
					'error_message' => array(
							'title' => __( 'Error message', 'postis' ),
							'type' => 'text',
							'description' => __( 'Custom error message when contry is not Iceland', 'postis' ),
							'desc_tip'		=> true,
					),
					'debug' => array(
                        'title'       => __( 'Debug Log', 'postis' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable logging', 'postis' ),
                        'default'     => 'yes',
                        'description' => __( 'Log Shipping API. See Log status->logs', 'postis' ),
                    ),
                    'shipping_price_method'=>array(
		    			'title'     => __( 'Use Shipping Price', 'postis' ),
		    			'description' => __( 'Choose shipping price calculation method.', 'postis' ),
		    			'type'     => 'select',
		    			'default'     => 'bruttoPrice',
		    			'options' => array( 'bruttoPrice' => 'bruttoPrice','nettoPrice' => 'nettoPrice'),
		    			'desc_tip'		=> true,
		    		),
		    		'include_wc_vat' => array(
                        'title'       => __( 'Include Woocommerce VAT', 'postis' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable', 'postis' ),
                        'default'     => 'no',
                        'description' => __( 'Calculate shipping prices with woocommerce VAT.', 'postis' ),
                    ),
                    'shipping_top' => array(
                        'title'       => __( 'Shipping method on top', 'postis' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable', 'postis' ),
                        'default'     => 'no',
                        'description' => __( 'Enable our shipping method on top.', 'postis' ),
                    ),
                    'pdf_print' => array(
                        'title'       => __( 'Automatic Printing', 'postis' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable', 'postis' ),
                        'default'     => 'no',
                        'description' => __( 'Automatically print shipment slip.', 'postis' ),
                    ),
					'add_tracking_to_email' => array(
					    'title'       => __( 'Send shipment details to customer', 'postis' ),
					    'type'        => 'checkbox',
					    'label'       => __( 'Enable', 'postis' ),
					    'default'     => 'no',
					    'description' => __( 'Customer will receive an email with the tracking number, and a link to track the package.', 'postis' ),
					),
                    'pdf_auto_generate_status' => array(
		                'title'    => __('Automatically generate Shipping slip', 'postis'),
		                'description'     => __('Select the order status that will trigger automatic shipping slip generation. Note: Does not trigger with bulk action.', 'postis'),
		                'type'     => 'select',
		                'options'  => $order_status_options,
		                'default'  => 'wc-completed',
		                'desc_tip' => true,
		            ),
                    'shipping_mode' => array(
                        'title'       => __( 'Shipping Mode', 'postis' ),
                        'type'        => 'select',
                        'options' => array( 'demo' => 'Demo',
                                    		'live' => 'Live'
                                		),
                        'default'     => 'live',
                        'description' =>  __( 'Select shipping mode.', 'postis' ),
                        'desc_tip'		=> true,
                    ),
                    'lang_switcher' => array(
                        'title'       => __( 'Shipping method language', 'postis' ),
                        'type'        => 'select',
                        'options' => array( 'default' => 'System default',
                        					'is' => 'Icelandic',
                                    		'en' => 'English'
                                		),
                        'default'     => 'default',
                        'description' =>  __( 'Select language for shipping method titles in cart and checkout. If system default is chosen then it will show user locale.', 'postis' ),
                        'desc_tip'		=> true,
                    ),
                    'show_shipping_description' => array(
                        'title'       => __( 'Shipping rate description', 'postis' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable', 'postis' ),
                        'default'     => 'yes',
                        'description' => __( 'Show shipping method description in cart', 'postis' ),
                    ),
					'postis_services_table' => array(
						'type'				=> 'postis_services_table'
					),
					
					// Google Maps Integration With Postbox
					'googlemap_section' => array(
                        'title'       => __( 'Google Map Settings', 'postis' ),
                        'type'        => 'title',
                        'description' =>  __( 'The following options are used to integrate Postboxes with Google Map on checkout page.', 'postis' ), 
                    ),
                    'googlemap_enable' => array(
		    			'title' => __( 'Enable Google Map', 'postis' ),
						'type' => 'checkbox',
						'label' => __( 'Enable', 'postis' ),
						'default' => 'no'
		    		),
		    		'googlemap_script_disable' => array(
		    			'title' => __( 'Disable Google Map Scripts', 'postis' ),
		    			'description' => __( 'You can disable our Google maps script html tag incase of conflicts.', 'postis' ),
		    			'desc_tip'		=> true,
						'type' => 'checkbox',
						'label' => __( 'Enable', 'postis' ),
						'default' => 'no'
		    		),
		    		'googlemap_api'=>array(
		    			'title'     => __( 'Google Map API', 'postis' ),
		    			'description' => __( 'Provide the google map API Key.', 'postis' ),
		    			'type'     => 'text',
		    			'desc_tip'		=> true,
		    		),
						
					// PDF Sections
					'pdf_section' => array(
                        'title'       => __( 'PDF Settings', 'postis' ),
                        'type'        => 'title',
                        'description' =>  __( 'The following options are used to configure PDF only for domestic shipment', 'postis' ), 
                    ),
                    'open_pdf_newtab' => array(
		    			'title' => __( 'Open PDF in new tab', 'postis' ),
						'type' => 'checkbox',
						'label' => __( 'Enable', 'postis' ),
						'default' => 'no'
		    		),
		    		/*
		    		'pdf_size'=>array(
		    			'title'     => __( 'Choose PDF Size', 'postis' ),
		    			'description' => __( 'Control the pdf sizes.', 'postis' ),
		    			'type'     => 'select',
		    			'default'     => 'custom',
		    			'options' => array( 'A4' => 'A4','A5' => 'A5', '9x15'=> '9x15', '9x5'=> '9x5', 'custom'=> 'Custom Sizes'),
		    			'desc_tip'		=> true,
		    			'hidden' => true,
		    		),
		    		*/
		    		'postis_labelSize'=>array(
		    			'title'     => __( 'Choose PDF Size', 'postis' ),
		    			'description' => __( 'Control the pdf sizes.', 'postis' ),
		    			'type'     => 'select',
		    			'default'     => 'A4Size',
		    			'options' => array( 'A4Size' => 'A4','LabelSize9x15' => '9x15', 'LabelSize10x7' => '10x7', 'LabelSize10x12' => '10x12', 'LabelSize6x10'=> '6x10'),
		    			'desc_tip'		=> true,
		    		),
					'pdf_width'=>array(
		    			'title'     => __( 'PDF Width', 'postis' ),
		    			'description' => __( 'This will add a PDF width', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => 100,
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_height'=>array(
		    			'title'     => __( 'PDF Height', 'postis' ),
		    			'description' => __( 'This will add a PDF height', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => 175,
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_rotate'=>array(
		    			'title'     => __( 'PDF Rotate', 'postis' ),
		    			'description' => __( 'This will add a PDF rotate', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => '0',
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_orientation'=>array(
		    			'title'     => __( 'PDF Orientation', 'postis' ),
		    			'description' => __( 'This will add a PDF orientation.', 'postis' ),
		    			'type'     => 'select',
		    			'default'     => 'L',
		    			'options' => array( 'L' => 'L','P' => 'P'),
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_moveright'=>array(
		    			'title'     => __( 'PDF Move Right', 'postis' ),
		    			'description' => __( 'This will add a PDF move right.', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => '4',
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_movedown'=>array(
		    			'title'     => __( 'PDF Move Down', 'postis' ),
		    			'description' => __( 'This will add a PDF move down.', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => '6',
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		'pdf_biggerbarcode'=>array(
		    			'title'     => __( 'PDF Bigger Barcode', 'postis' ),
		    			'description' => __( 'This will add a PDF biggerbarcode.', 'postis' ),
		    			'type'     => 'text',
		    			'default'     => '150',
		    			'desc_tip'		=> true,
		    			'class'       => 'postis-hidden-option',
		    		),
		    		
		    		// Kennitala Field Section
					'kennitala_section' => array(
                        'title'       => __( 'Kennitala Field Settings', 'postis' ),
                        'type'        => 'title',
                        'description' =>  __( 'Add extra text billing field in checkout page.', 'postis' ), 
                    ),
					'billing_kennitala_enable' => array(
		    			'title' => __( 'Enable/Disable', 'postis' ),
						'type' => 'checkbox',
						'label' => __( 'Enable', 'postis' ),
						'default' => 'no'
		    		),
		    		'billing_kennitala_label'=>array(
		    			'title'     => __( 'Field Label', 'postis' ),
		    			'description' => __( 'Enter Field Label.', 'postis' ),
		    			'type'     => 'text',
		    			'desc_tip'		=> true,
		    		),
		    		'billing_kennitala_req'=>array(
		    			'title'     => __( 'Required Field', 'postis' ),
						'label' => __( 'Enable to required field.', 'postis' ),
		    			'type' => 'checkbox',
		    			'default'     => 'no',
		    		),
			);
					
		}
	
	
		/**
		 * calculate_shipping function.
		 *
		 * @access public
		 * @param mixed $package
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			
			$calculate_shipping_endpoint = postis_get_endpoints('calculate_shipping');
			
			$domain	= $_SERVER['HTTP_HOST'];
			
			global $woocommerce;

		    if ( empty( $woocommerce->customer->get_shipping_postcode() ) ) {
		        return;
		    }
		    
			$rates = array();
			
			// Use LAFFPack for packaging.
    		include_once( POSTIS_PATH . '/lib/laff/vendor/autoload.php' );
		   
			$titles       = array();
	    	$total_weight = 0;
		
	    	// Initialize LAFFPack
	    	$packer = new \Cloudstek\PhpLaff\Packer();
		
	    	// Boxes
	    	$products_dimensions = array();
		
		    foreach ( $woocommerce->cart->get_cart() as $values ) {
		      
		    	$_product = $values['data'];
				
		    	// Check if the product has shipping enabled.
		    	if ( ! $_product->needs_shipping() ) { continue; }
		      	
		    	$quantity = $values['quantity'];
		      
		    	// Does the product have dimensions?
		    	if ( $_product->has_dimensions() ) {
		
		        	// Calculate and add to weight.
		        	$total_weight += postis_product_weight( $_product ) * $quantity;
		
		        	// Add product dimensions to the products_dimensions array.
		        	for ( $i = 0; $i < $quantity; $i++ ) {
		
			        	// Workaround weird LAFFPack issue where the dimensions are expected in reverse order.
				    	$dims = array(
				            postis_product_length( $_product ),
				            postis_product_width( $_product ),
				            postis_product_height( $_product )
				    	);
		          
		        		rsort( $dims );
				
				    	$products_dimensions[] = array(
				        	'length' => $dims[0],
				            'width'  => $dims[1],
				            'height' => $dims[2]
				    	);
		        	}
		
		        	if ( $this->debug != 'no' ) {
		        		$titles[] = $_product->get_title();
		        	}
		    	} else {
		    		
		        	if ( $this->debug != 'no' ) {
		        		$this->log->add( $this->id, 'Cannot calculate. product added is missing dimensions. Product: ' . $_product->get_title() );
		        	}
		        
			        // Calculate and add to weight.
		        	if ( postis_product_weight( $_product ) ) {
		        		
			        	$total_weight += postis_product_weight( $_product ) * $quantity;
		        	}else{
		        		
			        	$total_weight += $this->default_products_weight * $quantity;
		        	}
		        	
		        	// Set default demensions
		        	$products_dimensions[] = array(
			        	'length' => 0,
			            'width'  => 0,
			            'height' => 0
			    	);
		    	}
		    }
		    
		    if ( $this->debug != 'no' ) {
		    	$this->log->add( $this->id, 'Dimensions before packaging: ' . print_r($products_dimensions, 1) );
		    }
		
		    // Pack the boxes.
		    $packer->pack( $products_dimensions );
		
		    // Get the estimated container size from LAFFPack.
		    $container_size = $packer->get_container_dimensions();
		    
		    $is_shipment_international = postis_is_shipment_international($woocommerce->customer->get_shipping_country());
		    
		    // get site locale
		    $postis_locale = get_user_locale();
		    if ($postis_locale == 'is_IS') {
		    	$locale_api_lang = 'is';
		    } else {
		    	$locale_api_lang = 'en';
		    }
		    $lang_switcher = postis_get_settings( 'lang_switcher' );
		    if (isset($lang_switcher) && $lang_switcher !== '' && $lang_switcher !== 'default') {
		    	$locale_api_lang = $lang_switcher;
		    }

		    // Request params.
		    $params = array(
		        'countryCode'   => $woocommerce->customer->get_shipping_country(),
		        'length'        => $this->get_dimension( $container_size['length'] ),
		        'width'         => $this->get_dimension( $container_size['width'] ),
		        'height'        => $this->get_dimension( $container_size['height'] ),
		        'weight'    	=> $this->get_weight( $total_weight ),
		        'lang'			=> $locale_api_lang
		    );
		    
		    if (!$is_shipment_international) {
		    	$params['postCode'] = $woocommerce->customer->get_shipping_postcode();
		    }
		    

		    // Remove empty parameters (eg.: to and from).
		    $params = array_filter( $params );
		    
		    // Query format parameters.
		    $query = add_query_arg( $params, $calculate_shipping_endpoint );
		    
		    $req_headers = array(
		    	"headers" => array(
		    		'x-api-key'=>$this->apikey, 
		    		'domain_name'=> $domain,
					'user-agent'   => 'WooCommerce/'.postis_get_woocommerce_version().' | Postis/'.postis_get_plugin_version(),
            		'WooCommerce-Version' => postis_get_woocommerce_version(),
            		'Plugin-Version' => postis_get_plugin_version()
		    	)
		    );
		    
		    // Run the query
		    $response = wp_remote_get( $query, $req_headers );
		    
		    if (!is_wp_error($response)) {
		    	$decoded = json_decode( $response['body'], true );
		    	$rates   = $this->get_services_rates( $decoded );
		    	$this->log->add( $this->id, 'API response: ' . print_r($response, true) );
		    }else{
		    	$err_msg = __('Sorry, there is something issue to get shipping options, please try again');
		    	$this->log->add( $this->id, 'API request error: ' . $response->get_error_message() );
		    	if (!wc_has_notice($err_msg, 'error')) {
                    wc_add_notice($err_msg, 'error');
                }
		    }
		    
		    // Calculate rate.
		    if ( !empty($rates) ) {
			    foreach ($rates as $rate) {
			        $this->add_rate($rate);
			    }
		    }
		}
		
		
		// Get Services Rate
		function get_services_rates( $services ){
			
			$price_method = postis_get_settings( 'shipping_price_method' );
			$include_wc_vat = postis_get_settings( 'include_wc_vat' );
			$has_tax = false;
			if ($include_wc_vat != '' && $include_wc_vat == 'yes') {
				$has_tax = 	true;
			}
			
			$postis_services = isset($services['deliveryServicesAndPrices']) && !empty($services['deliveryServicesAndPrices']) ? $services['deliveryServicesAndPrices'] : null;
		
			if( ! $postis_services ) return false;
		    
		    $rates = array();
		
		    // Fix for when only one product exists. It's not returned in an array :/
		    if ( isset($services['Product']) ) {
			    if ( empty( $services['Product'][0] ) ) {
			      $cache = $services['Product'];
			      unset( $services['Product'] );
			      $services['Product'][] = $cache;
			    }
		    }

    		// Check if the option to show meta description is enabled
    		$show_shipping_description = postis_get_settings( 'show_shipping_description' );

			foreach ( $postis_services as $service ) {
			
				$service_id		= $service['deliveryServiceId'];
				$service_name	= $service['nameLong'];
				$service_description = isset($service['description']) ? $service['description'] : '';
				$without_vat	= $service['priceRelated']['nettoPrice'];
				$with_vat		= isset($service['priceRelated'][$price_method]) ? $service['priceRelated'][$price_method] : $service['priceRelated']['bruttoPrice'];
				$rate 			= $with_vat;
	
				// Get Service Label
				$custom_label	= $this->get_service_custom_label($service_id, $service_name);
				
				$shipping_cost = floatval($rate);
				$shipping_cost = $this->get_service_fee($service_id, $shipping_cost);
				// postis_pa($shipping_cost);
				
		        $rate = array(
		            'id'    => $service_id,
		            'cost'  => round( $shipping_cost ),
		            'label' => 'Pósturinn - '.$service_name,
		            'taxes' => $has_tax,
		        );
		        
		        // Add meta description if the option is enabled or doesn't exist
		        if ( $show_shipping_description !== 'no' ) {
		            $rate['meta_data'] = array(
		                'description' => $service_description
		            );
		        }
				
				array_push( $rates, $rate );
			}
			
			return $rates;
		}
	
	
		// Getting service custom label
		function get_service_custom_label($service_id, $service_name) {
			
			$custom_label = "{$service_id}-{$service_name}";;
			
			if( isset($this->postis_services[$service_id]) && $this->postis_services[$service_id]['name'] != '' ) {
				$custom_label = $this->postis_services[$service_id]['name'];
			}
			
			return $custom_label;
		}
	
	
		// Getting service extra fee
		function get_service_fee($service_id, $shipping_cost) {
			
			$total_fee = $shipping_cost;
			
			if( isset($this->postis_services[$service_id]) && $this->postis_services[$service_id]['enabled'] == 'on' ) {
				
				$provided_cost = $this->postis_services[$service_id]['cost'];
				
				$service_fee = floatval( $this->get_fee($provided_cost, $shipping_cost) );
				
				$total_fee = $service_fee + $shipping_cost;
				
			}
			//postis_pa($service_fee);
			
			return $total_fee;
		}
		
	
		// ================================ SOME HELPER FUNCTIONS =========================================
	
		/**
		* Return volume in dm.
		*
		* @param $dimension
		* @return float
		*/
		public function get_volume( $dimension ) {
			
			switch ( $this->dimens_unit ) {
			
			  case 'mm' :
			    return $dimension / 100;
			
			  case 'in' :
			    return $dimension * 0.254;
			
			  case 'yd' :
			    return $dimension * 9.144;
			
			  case 'cm' :
			    return $dimension / 1000;
			
			  case 'm' :
			    return $dimension / 10;
			
			  /* Unknown dimension unit */
			  default :
			    if ( $this->debug != 'no' ) {
			      $this->log->add( $this->id, sprintf( 'Could not calculate dimension unit for %s', $this->dimens_unit ) );
			    }
			    return false;
			}
		}


		/**
		* Return weight in grams.
		*
		* @param float $weight
		* @return float
		*/
		public function get_weight( $weight ) {
			
			switch ( $this->weight_unit ) {
			
			  case 'kg' :
			    return $weight;
			
			  case 'g' :
			    return $weight * 0.0010000;
			
			  case 'lbs' :
			    return $weight * 0.45359237;
			
			  case 'oz' :
			    return $weight * 0.02834952;
			
			  /* Unknown weight unit */
			  default :
			    if ( $this->debug != 'no' ) {
			      $this->log->add( $this->id, sprintf( 'Could not calculate weight unit for %s', $this->weight_unit ) );
			    }
			    return false;
			}
		}


		/**
		* Return dimension in centimeters.
		*
		* @param float $dimension
		* @return float
		*/
		public function get_dimension( $dimension ) {
		
			switch ( $this->dimens_unit ) {
			
			  case 'mm' :
			    return $dimension / 10.000;
			
			  case 'in' :
			    return $dimension / 0.39370;
			
			  case 'yd' :
			    return $dimension / 0.010936;
			
			  case 'cm' :
			    return $dimension;
			
			  case 'm' :
			    return $dimension / 0.010000;
			
			  /* Unknown dimension unit */
			  default :
			    if ( $this->debug != 'no' ) {
			      $this->log->add( $this->id, sprintf( 'Could not calculate dimension unit for %s', $this->dimens_unit ) );
			    }
			    return false;
			}
		}
		
		
		/**
		 * validate_additional_costs_field function.
		 *
		 * @access public
		 * @param mixed $key
		 * @return bool
		 */
		function validate_postis_services_table_field( $key ) {
			return false;
		}


		/**
		 * generate_additional_costs_html function.
		 *
		 * @access public
		 * @return string
		 */
		function generate_postis_services_table_html() {
			
			$saved_shippment_settings = array_filter( (array) get_option( $this->postis_services_option ) );

			$shippment_order_changed = array();
			if (!empty($saved_shippment_settings)) {
				
				foreach ($saved_shippment_settings as $shipment_id => $service_meta) {

					// skipping old shipment types
					if ($shipment_id === 'OPA' || $shipment_id === 'OPB' || $shipment_id === 'OPG') {
						continue;
					}
					$shippment_order_changed[$shipment_id] = $this->services[$shipment_id];
				}
				
				foreach ($this->services as $service_id => $service_data) {
					if (!array_key_exists($service_id, $shippment_order_changed)) {
						$shippment_order_changed[$service_id] = $this->services[$service_id];
					}
				}

				$this->services = $shippment_order_changed;
			}

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc"><?php _e( 'Costs', 'postis' ); ?>:</th>
				<td class="forminp" id="<?php echo $this->id; ?>postis_shipping">
					<table class="shippingrows widefat" cellspacing="0">
						<thead>
							<tr>
								<th></th>
								<th class="shipping_checked" style="display: none;"><?php _e( 'Enabled', 'postis' ); ?></th>
								<th class="shipping_code"><?php _e( 'Service Code', 'postis' ); ?></th>
								<th class="shipping_name"><?php _e( 'Service Name', 'postis' ); ?></th>
								<th><?php _e( 'Added Cost', 'postis' ); ?> <a class="tips" data-tip="<?php _e( 'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%', 'postis' ); ?>">[?]</a></th>
								<th><?php _e( 'Free Shipping', 'postis' ); ?></th>
							</tr>
						</thead>
						
						<tbody class="postis_shippings postis-shipping-sortable">
							
							<?php
							$i = -1;
							if ( $this->services ) {
								
								foreach ( $this->services as $code => $name ) {
									$i++;
									
									$service_name = $name;
									$service_cost = 0;
									$checked		= '';
									$service_id    = $code;
									if(isset($this->postis_services[$code])){
										$service_detail = $this->postis_services[$code];
										$service_id 	= isset($service_detail['service_id']) ? $service_detail['service_id'] : $code;
										$service_name 	= $service_detail['name'];
										$service_desc	= $service_detail['desc'];
										$service_cost 	= $service_detail['cost'];
										$checked		= ($service_detail['enabled'] == 'on' ? 'checked="checked"' : '');
										$freeShipping 	= isset($service_detail['free_shipping']) ? $service_detail['free_shipping'] : '' ;
									}
									
	
									echo '<tr class="postis_shipping">';
									echo '<th class="postis-move-icon"><span class="dashicons dashicons-move"></span></th>';
									echo '<th class="check-column" style="display: none;"><input type="checkbox" checked="checked" name="' . esc_attr( $this->id .'_enabled[' . $i . ']' ).'"></th>';
									echo '<td class="postis_shipping_class" style="padding-left:0;"><input type="hidden" name="' . esc_attr( $this->id .'_code[' . $i . ']' ) . '" value="' . esc_attr( $service_id ) . '" />'.$service_id.'</td>';
									echo '<td class="postis_shipping_class" style="display: none;"><input type="hidden" name="' . esc_attr( $this->id .'_name[' . $i . ']' ) . '" value="' . esc_attr( $service_name ) . '" placeholder="' . esc_attr( $name ) . '" class="wc_input_text" /></td>';
									echo '<td class="postis_shipping_class" style="padding-left:0;">' . esc_attr( $name ) . '</td>';
									echo '<td class="postis_shipping_class" style="display: none;"><input type="hidden" name="' . esc_attr( $this->id .'_desc[' . $i . ']' ) . '" value="' . esc_attr( $service_desc ) . '" placeholder="' . esc_attr( $name ) . '" class="wc_input_text" /></td>';
									echo '<td style="padding-left:0;"><input type="text" value="' . esc_attr( $service_cost ) . '" name="' . esc_attr( $this->id .'_cost[' . $i . ']' ) . '" placeholder="' . wc_format_localized_price( 0 ) . '" size="4" class="wc_input_price" /></td>';
									echo '<td style="padding-left:0;"><input type="text" value="' . esc_attr( $freeShipping ) . '" name="' . esc_attr( $this->id .'_free_shipping[' . $i . ']' ) . '" class="wc_input_text" /></td>';
									echo '</tr>';
								}
							}
							?>
						</tbody>
					</table>
				   	
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}
	
	
		/**
		 * process_postis_services function.
		 *
		 * @access public
		 * @return void
		 */
		function process_postis_rates() {
			
		
			$postis_service_name = array();
			$extra_cost = array();
			$postis_services = array();
	
			if ( isset( $_POST[ $this->id . '_enabled'] ) ) $postis_service_enabled = array_map( 'wc_clean', $_POST[ $this->id . '_enabled'] );
			if ( isset( $_POST[ $this->id . '_code'] ) ) $postis_service_code = array_map( 'wc_clean', $_POST[ $this->id . '_code'] );
			if ( isset( $_POST[ $this->id . '_name'] ) ) $postis_service_name = array_map( 'wc_clean', $_POST[ $this->id . '_name'] );
			if ( isset( $_POST[ $this->id . '_desc'] ) ) $postis_service_desc = array_map( 'wc_clean', $_POST[ $this->id . '_desc'] );
			if ( isset( $_POST[ $this->id . '_cost'] ) )  $extra_cost  = array_map( 'wc_clean', $_POST[ $this->id . '_cost'] );
			if ( isset( $_POST[ $this->id . '_free_shipping'] ) ) $postis_free_shipping = array_map( 'wc_clean', $_POST[ $this->id . '_free_shipping'] );
			// Get max key
			$values = $postis_service_name;
			ksort( $values );
			$value = end( $values );
			$key = key( $values );
			
			$saved_index = array();
			foreach ($postis_service_code as $index => $value) {
				
				if ( ! empty( $postis_service_name[ $index ] ) && isset( $extra_cost[ $index ] ) ) {
		
						// $extra_cost[ $index ] = wc_format_decimal( $extra_cost[$index] );
						// var_dump($extra_cost[ $index ]);
						if ( ! strstr( $extra_cost[$index], '%' ) ) {
							$extra_cost[ $index ] = wc_format_decimal( $extra_cost[$index] );
						} else {
							$extra_cost[ $index ] = wc_clean( $extra_cost[$index] );
						}
		
						// Add to flat rates array
						$postis_services[ $postis_service_code[ $index ] ] = array(
							'service_id' => $postis_service_code[ $index ],
							'name' => $postis_service_name[ $index ],
							'desc' => $postis_service_desc[ $index ],
							'free_shipping' => $postis_free_shipping[ $index ],
							'cost' => $extra_cost[ $index ],
							'enabled'	=> (isset($postis_service_enabled[ $index ]) ? $postis_service_enabled[ $index ] : ''),
						);
				}
			}
			// postis_pa($postis_service_code);
			// postis_pa($postis_services); exit;
	
			// for ( $i = 0; $i <= $key; $i++ ) {
			// 	if ( ! empty( $postis_service_name[ $i ] ) && isset( $extra_cost[ $i ] ) ) {
	
			// 		// $extra_cost[ $i ] = wc_format_decimal( $extra_cost[$i] );
			// 		// var_dump($extra_cost[ $i ]);
			// 		if ( ! strstr( $extra_cost[$i], '%' ) ) {
			// 			$extra_cost[ $i ] = wc_format_decimal( $extra_cost[$i] );
			// 		} else {
			// 			$extra_cost[ $i ] = wc_clean( $extra_cost[$i] );
			// 		}
	
			// 		// Add to flat rates array
			// 		$postis_services[ $postis_service_code[ $i ] ] = array(
			// 			'name' => $postis_service_name[ $i ],
			// 			'desc' => $postis_service_desc[ $i ],
			// 			'free_shipping' => $postis_free_shipping[ $i ],
			// 			'cost' => $extra_cost[ $i ],
			// 			'enabled'	=> (isset($postis_service_enabled[ $i ]) ? $postis_service_enabled[ $i ] : ''),
			// 		);
			// 	}
			// }
	
			update_option( $this->postis_services_option, $postis_services );
			
			// postis_pa($postis_services); exit;
	
			$this->get_postis_services();
		}


		/**
		 * get_postis_services function.
		 *
		 * @access public
		 * @return void
		 */
		function get_postis_services() {
			$this->postis_services = array_filter( (array) get_option( $this->postis_services_option ) );
		}
		
		function translate_strings($translated, $text, $domain){
			
			/*switch ($text){

				case 'Shipping and Handling':
					$translated = sprintf(__("%s", 'postis'), $this->method_title);
				break;
				
				case 'Please continue to the checkout and enter your full address to see if there are any available shipping methods.':
					$translated = sprintf(__("%s", 'postis'), $this->custom_shipping_label_cart);
				break;
				
				case 'Vennligst oppgi fullstendig adresse for å se fraktpriser og tilgjengelige leveringsmetoder.':
					$translated = sprintf(__("%s", 'postis'), $this->custom_shipping_label_checkout);
					break;
					
			}*/

		if (strpos($text,'Shipping and Handling') !== false) {
			
			$translated = $this->method_title;
		}

		if (strpos($translated,'Please continue to the checkout and') !== false) {
				
			$translated = $this->custom_shipping_label_cart;
		}
			//$translated = str_ireplace('Please continue to the checkout and enter your full address to see if there are any available shipping methods.', $this->custom_shipping_label_cart, $translated);
			
			
			
			return $translated;
		}
		
		
		// Hide Shipping Method When Cart total is greater than free shipping option
		function hide_shipping_method( $rates, $package ){
			
			$shipping_top = postis_get_settings( 'shipping_top' );
		    $include_wc_vat = postis_get_settings('include_wc_vat');

			$shipping_order = array();
			$total = WC()->cart->cart_contents_total;
		    if ($include_wc_vat === 'yes' && wc_tax_enabled() && WC()->cart->get_subtotal_tax() > 0) {
		        $total += WC()->cart->get_subtotal_tax();
		    }
		    
			// postis_pa($this->postis_services);
			// postis_pa($rates);
			
			foreach($this->postis_services as $code => $shipment_meta){
				
				if ( isset($shipment_meta['free_shipping']) && $shipment_meta['free_shipping'] != '' && $shipment_meta['free_shipping'] > 0 && isset($shipment_meta['enabled']) && $shipment_meta['enabled'] == 'on') {
					
					if( $total > $shipment_meta['free_shipping'] ) {
						if ( isset($rates[$code]) ) {
							$rates[$code]->cost = 0;
							$rates[$code]->taxes = array();
						}
				    }
				}
				
				if (isset($rates[$code])) {
					$shipping_order[] = $code;
				}
			}
			
			// ksort($shipping_order);
			if ($shipping_top == 'yes') {
				$rates = array_merge(array_flip($shipping_order), $rates);
			}
			
			// postis_pa($shipping_top);
			
	        return $rates;
		}
		
		
		
	
	} //ending class POSTIS
	
}

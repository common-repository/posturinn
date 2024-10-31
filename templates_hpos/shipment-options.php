<?php 
/*
** Store Owner Shipment Options Template
*/

/* 
**========== Direct access not allowed =========== 
*/
if( ! defined('ABSPATH' ) ){ exit; }

$service_generator = array();
$wc_order = wc_get_order($order_id);

$shipping_method_custom_meta = $wc_order->get_meta('postis_shipping_method');

if ($selected_delivery_service_id) {
    $shipping_service = $selected_delivery_service_id;
} else {
    $shipping_service = $shipping_method_custom_meta;
}

$is_dpo_selected = ($shipping_service === 'DPO');
$is_dpt_selected = ($shipping_service === 'DPT');

$postbox_id = $wc_order->get_meta('postis_dpo_postbox');
$parcelpoint_name = $wc_order->get_meta('postis_dpt_parcelpoints');
$phone_number = $wc_order->get_meta('postis_dpo_phonenumber');

$postboxes = array();
$parcelpoints = array();

if ($is_dpo_selected) {
    $postboxes = postis_get_dpo_postboxes_by_list();
} elseif ($is_dpt_selected) {
    $parcelpoints = postis_get_dpt_parcelpoints_by_list();
}

$is_postis_as_shipping = $wc_order->has_shipping_method('postis');
$shipping_country = $wc_order->get_shipping_country();
$shipping_state = $wc_order->get_shipping_state();
$shipping_postal_code = $wc_order->get_shipping_postcode();

// getting Packages Options
$all_packages = postis_package_deliver_fail_options();

// Getting Content Delivery Options
$content_delivery = postis_content_delivery_options();

// Check if shipment is international
$is_shipment_international = postis_is_shipment_international($shipping_country);

$get_all_services = isset($shipping_options['deliveryServicesAndPrices']) && !empty($shipping_options['deliveryServicesAndPrices']) ? $shipping_options['deliveryServicesAndPrices'] : array();

foreach ($get_all_services as $index => $service_meta) {
    if ( isset($service_meta['deliveryServiceId']) && $service_meta['deliveryServiceId'] != '' && $service_meta['deliveryServiceId'] == $shipping_service ) {
        $optional_services = isset($service_meta['optionalServices']) ? $service_meta['optionalServices'] : array();
        
        foreach ($optional_services as $option_name => $val) {
            if ($val) {
                switch ($option_name) {
                    case 'insurance':
                       $service_generator['insurance']       = array('type'=>'checkbox', 'attach_with'=>'insuranceAmount','show'=> true,'required'=> false);
                       $service_generator['insuranceAmount'] = array('type'=>'text', 'attach_with'=>'no', 'show'=> false, 'required'=> true);
                        break;
                    case 'cod':
                    case 'reference':
                       $service_generator['cod']       = array('type'=>'checkbox', 'attach_with'=>'reference','show'=> true, 'required'=> false);
                       $service_generator['reference'] = array('type'=>'text', 'attach_with'=>'no','show'=> false,'required'=> false);
                        break;
                    default:
                        $service_generator[$option_name] = array('type'=>'checkbox', 'attach_with'=>'no' ,'show'=> true, 'required'=> false);
                        break;
                }
            }
        }    }
}

?>

<div class="postis-admin-shipment-options-wrapper">
    <?php
    if (empty($shipping_method_custom_meta) && empty($shipping_service)) {
        echo '<div class="notice error" style="margin-left:unset;margin-top:1em;">Upplýsingar vantar til að búa til sendingu. Vinsamlegast veldu sendingarmáta úr fellilistanum.</div>';
    }
    ?>
    <!--Generic Error Message-->
    <div class="modern-row postis-generic-error-section notice error" style="margin-left:unset;margin-top:1em;">
        <span id="postis-resp-msg"></span>
    </div>
    <!--End of Generic error message-->
    <table class="table">
        <tbody>
            <!--Invalid PhoneNumber Input-->
            <tr class="postis-phonenumber-section" style="display:none;">
                <th></th>
                <td>
                     <span style="border-left: 4px solid #ffba00; padding: 3px 7px; margin: 1em 0;display: block;">
                        <?php _e('The phone number in the order is not correct or missing. You can write new phone number in the field below and click "Create shipment" button.', "postis"); ?>
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    <form class="postis-submit-shipment-options" style="margin-top:1em;">
        <div class="select-container" style="width:100%;">
            <div class="flex-field">
                <div><label for="select_delivery_service_id">Afhendingarleið</label></div>
                <select id="postis-delivery-service-id" name="selected_delivery_service_id">
                    <option value="" disabled selected>Veldu sendingarmáta</option>
                    <?php foreach ( $get_all_services as $service ) : ?>
                        <option value="<?php echo esc_attr( $service['deliveryServiceId'] ); ?>" <?php selected( $shipping_service, $service['deliveryServiceId'] ); ?>><?php echo esc_html( $service['nameLong'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="postis-phonenumber-section flex-field">
                <div><label for="phonenumber"><?php _e('Enter Valid PhoneNumber', "postis"); ?>:</label></div>
                <div>
                    <?php
                    $customer_phone = $wc_order->get_billing_phone();
                    $suggested_phone = '';

                    if (!empty($phone_number)) {
                        $suggested_phone = $phone_number;
                    } elseif (!empty($customer_phone)) {
                        $suggested_phone = $customer_phone;
                    }

                    // Check if the suggested phone number is a valid Icelandic mobile number
                    $is_valid_mobile = preg_match('/^(6|7|8)\d{6}$/', $suggested_phone);
                    ?>
                    <input type="tel" id="postis-phonenumber-valid" name="phonenumber" style="border: 1px solid <?php echo $is_valid_mobile ? '#ccc' : 'rgb(241, 5, 5)'; ?>;" placeholder="Farsímanúmer" value="<?php echo esc_attr($suggested_phone); ?>" />
                    <?php if (!$is_valid_mobile && !empty($suggested_phone)) : ?>
                        <span style="color: rgb(241, 5, 5);"><?php _e('Only Icelandic mobile phone numbers are allowed', "postis"); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_dpo_selected && !empty($postboxes['postboxes'])) : ?>
                <div class="postis-dpo-fields flex-field">
                    <div><label for="postis_dpo_postbox">Póstbox*</label></div>
                    <div>
                        <select id="postis-dpo-postbox" name="postis_dpo_postbox" required>
                            <option value=""><?php _e('--- Choose postbox ----', 'postis'); ?></option>
                            <?php foreach ($postboxes['postboxes'] as $postbox) : ?>
                                <option value="<?php echo esc_attr($postbox['postboxId']); ?>" <?php selected($postbox_id, $postbox['postboxId']); ?>><?php echo esc_html($postbox['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <input type="hidden" name="action" value="postis_create_shipment_action"/>
        <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>"/>
        <hr>
            <div class="modern-row">
                <label class="modern-label"><?php _e('Number Of Parcels', "postis"); ?></label> 
                <input type="number" min="1" id="postis-numberofitems-id" name="numberofitems" value="1" class="modern-input">
            </div>
            <div class="modern-row">
                <label class="modern-label"><?php _e('Description', "postis"); ?></label> 
                <input type="text" id="postis-desc-id" name="description" class="modern-input">
            </div>

        <div class="postis-international-options-section">
            <h1 class="accordion-title" onclick="toggleAccordion()">Viðbótarþjónusta</h1>
            
            <span class="postis-cod-alert-msg" style="display:none;"><?php _e('To use this service you must provide bank details to Posturinn', "postis"); ?></span>



            <div class="form-container" style="columns:2;">
                <?php 
                foreach ($service_generator as $option_name => $option_meta) {
                ?>
                    <div class="modern-row option-line-<?php echo esc_attr($option_name); ?>" data-attach_with="<?php echo esc_attr($option_meta['attach_with']); ?>" style="display:<?php if(!$option_meta['show']) echo 'none;'; ?>">
                        <label class="modern-label" for="<?php echo esc_attr($option_name); ?>"><?php echo __($option_name, "postis"); ?></label>
                        <input 
                            id="<?php echo esc_attr($option_name); ?>" 
                            type="<?php echo esc_attr($option_meta['type']); ?>" 
                            class="modern-checkbox postis-attach-field-js postis-shipment-<?php echo esc_attr($option_name); ?>" 
                            name="optional_services[<?php echo esc_attr($option_name); ?>]" 
                            <?php if(!$option_meta['show']) echo 'disabled'; ?> 
                            <?php if($option_meta['required']) echo 'required="true"'; ?> 
                        >
                    </div>
                <?php
                }
                ?>
            </div>




            <?php
            if ($is_shipment_international) {
                $contents = postis_international_contents_fields();
            ?>
            <div class="postis-inter-contents-options">
                
                <h1><?php _e('Contents', "postis"); ?></h1>
                <?php 
                foreach ( $wc_order->get_items() as $item_id => $item ) {
                    $product_name = $item->get_name();
                    $product_id = $item->get_product_id();
                    $product_price_vat = wc_get_price_including_tax( $item->get_product() );
                    $quantity = $item->get_quantity();
                    $currency = get_woocommerce_currency();

                    $item_tnumber      = $wc_order->get_meta('_hsTariffNumber');
                    $item_desc_content = $wc_order->get_meta('_descriptionOfContents');
                ?>
                <div class="tariff-container">
                    <h3><?php echo $item->get_name(); ?></h3>
                    <table class="table postis-international-contents">
                        <thead>
                            <tr>
                                <th><?php _e('Description', "postis"); ?></th>
                                <th><?php _e('QTY', "postis"); ?></th>
                                <th><?php _e('Value', "postis"); ?></th>
                                <th><?php _e('Currency', "postis"); ?></th>
                                <th><?php _e('TariffNumber', "postis"); ?></th>
                                <th><?php _e('Country', "postis"); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        
                        <tr>
                        <?php
                            foreach ($contents as $contents_id => $contents_val) {
                                
                                $label   = isset($contents_val['label']) ? $contents_val['label'] : '';
                                $default = isset($contents_val['default']) ? $contents_val['default'] : '';
                                $req     = (isset($contents_val['required']) && $contents_val['required'] =='yes' ) ? 'required' : '' ;
                                $readonly   = (isset($contents_val['readonly']) && $contents_val['readonly'] =='yes' ) ? 'readonly' : '' ;
                                $maxlength  = (isset($contents_val['maxlength']) && $contents_val['maxlength'] != '' ) ? 'maxlength="'.$contents_val['maxlength'].'"' : '' ;
                                
                                if ('valueForCustoms' == $contents_id) {
                                   $default = $product_price_vat;
                                }else if('goodsQuantity' == $contents_id){
                                    $default = $quantity;
                                }else if('valueForCustomsCurrency' == $contents_id){
                                    $default = $currency;
                                }else if('hsTariffNumber' == $contents_id){
                                    $default = $item_tnumber;
                                }else if('descriptionOfContents' == $contents_id){
                                    $default = $item_desc_content;
                                }
                                
                                if (isset($contents_val['display']) && $contents_val['display'] == 'yes' ) {
                                    ?>
                                    <td>
                                        <input 
                                            type="text" 
                                            id="<?php echo esc_attr($contents_id); ?>" 
                                            name="contents[<?php echo esc_attr($item_id); ?>][<?php echo esc_attr($contents_id); ?>]" 
                                            <?php echo esc_attr($req); ?> 
                                            <?php echo esc_attr($readonly); ?> 
                                            <?php echo esc_attr($maxlength); ?> 
                                            value="<?php echo esc_attr($default); ?>"
                                        />
                                    </td>
                                    <?php
                                }
                            }
                            ?>
                            <td><input type="button" value="x" class="posturinn_tariff_deleteRow"></td>
                        </tr>
                    </table>
                </div>
                <?php
                }
                ?>
                </div>
                <?php
            }
            ?>
            
        </div>
    
        <div class="postis-create-shipment-btn-wrap">
            <span class="postis-shipment-create-loader"></span>
            <input type="submit" class="button button-primary" value="<?php _e('Create Shipment', "postis"); ?>"/>
        </div>    
    
    </form>
    <div id="loading-indicator" style="display:none;"></div>
    <style>
        div#loading-indicator {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: #ffffffba;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            function bindChangeEvent() {
                $('#postis-delivery-service-id').off('change').on('change', function() {
                    var selectedDeliveryServiceId = $(this).val();
                    var orderId = $('input[name="order_id"]').val();
                    $('#loading-indicator').show(); // Show loading indicator
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'postis_shipment_options',
                            order_id: orderId,
                            deliveryServiceId: selectedDeliveryServiceId
                        },
                        success: function(response) {
                            console.log('AJAX success', response);
                            $('.postis-admin-shipment-options-wrapper').html(response);
                            $('#postis-delivery-service-id').val(selectedDeliveryServiceId);
                            $('#loading-indicator').hide(); // Hide loading indicator
                            bindChangeEvent(); // Re-bind event after replacing content
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error', error);
                            $('#loading-indicator').hide(); // Hide loading indicator on error
                        }
                    });
                });
            }

            bindChangeEvent(); // Initial bind
        });

        function toggleAccordion() {
            var container = document.querySelector('.form-container');
            var title = document.querySelector('.accordion-title');
            
            if (container.style.display === "block") {
                container.style.display = "none";
                title.classList.remove('accordion-active');
            } else {
                container.style.display = "block";
                title.classList.add('accordion-active');
            }
        }

    </script>

</div>
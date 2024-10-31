<?php
/**
 * handling Hooks Callbacks
**/


/* ======= Direct Access Not Allowed =========== */
if( ! defined('ABSPATH' ) ){ exit; }


/**
* Show Extra Service Options On Checkout Page
**/
function postis_hooks_rendering_extra_shipping_services() {
    
    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    $gm_enable = postis_get_settings( 'googlemap_enable' );

    if( in_array('DPO', $chosen_methods) && $gm_enable != 'yes' ) {
        
        echo '<tr>';
            echo '<td>'.__("Postboxes", "postis"). '</td>';
            echo '<td>';
                echo postis_get_dpo_postboxes();
            echo '</td>';
        echo '</tr>';
    }else if(in_array('DPO', $chosen_methods) && $gm_enable == 'yes' ){
        echo '<tr>';
            echo '<td>'.__("Postbox Address", "postis"). '</td>';
            echo '<td>';
                echo postis_get_dpo_postboxes();
                echo '<span class="postis-googlemap-choosen-address">' .__("Selected Postbox: ", "postis"). '<span></span></span>';
                echo '<button class="button postis-googlemap-modal-btn">' .__("Choose Postbox", "postis"). '</button>';
            echo '</td>';
        echo '</tr>';
    }
    
    if( in_array('DPT', $chosen_methods) ) {
        
        echo '<tr>';
            echo '<td>'.__("Parcel Points", "postis"). '</td>';
            echo '<td>';
                echo postis_get_dpt_parcelpoints();
            echo '</td>';
        echo '</tr>';
    }
}


/**
* Load Frontend Scripts
**/
function postis_hooks_load_scripts(){
		
	if ( is_checkout() ) {
		
		$gm_enable = postis_get_settings( 'googlemap_enable' );
		$googlemap_api = postis_get_settings( 'googlemap_api' );
        
		if( $gm_enable == 'yes') {
		    
		    wp_enqueue_script('postis-google-map', 'https://maps.googleapis.com/maps/api/js?key='.$googlemap_api, array('jquery'), '1.0.9', true);
		    
			wp_enqueue_style('postis-remodal', POSTIS_URL."/css/remodal.css");
			wp_enqueue_style('postis-remodal-theme', POSTIS_URL."/css/remodal-default-theme.css");
        	wp_enqueue_script('postis-remodal', POSTIS_URL."/js/remodal.js", array('jquery'), '1.0.9', true);
        	
        	wp_enqueue_script('postis-gm-js', POSTIS_URL."/js/postis-googlemap.js", array('jquery'), '1.0.9', true);
		}
	}
}


/**
* Render Extra Content After Checkout Form (Google Map)
**/
function postis_hooks_after_checkout_form(){
        
    $postboxes = postis_get_dpo_postboxes_by_list();
    if (isset($postboxes['postboxes']) && !empty($postboxes['postboxes'])) {
        
        // postis_pa($postboxes); exit;
    ?>
    <div data-remodal-id="postis_googlemap_modal" role="dialog">
        <div class="postis-popup-body">
            <div class="postis-popup-outer-content">
                <h3><?php echo __("Choose Postbox", "postis"); ?></h3>
            </div>
            <div class="postis-col-wrapper">
                <div class="postis-postboxes-section">
                    <ul>
                        <?php 
                        foreach ($postboxes['postboxes'] as $index => $meta) {
                            $postboxId = isset($meta['postboxId']) ? $meta['postboxId'] : '';
                            $postcode = isset($meta['postcode']) ? $meta['postcode'] : '';
                            $name      = isset($meta['name']) ? $meta['name'] : '';
                            $latitude  = isset($meta['latitude']) ? $meta['latitude'] : '';
                            $longitude = isset($meta['longitude']) ? $meta['longitude'] : '';
                        ?>
                              <li class="postis-googlemap-location" data-postboxes="<?php echo esc_attr(json_encode($meta, JSON_FORCE_OBJECT)); ?>" data-lat="<?php echo esc_attr($latitude); ?>" data-long="<?php echo esc_attr($longitude); ?>">
                                  <span><?php echo esc_html($name); ?></span>
                                  <span>(<?php echo esc_html($postcode); ?>)</span>
                              </li>
                        <?php
                        }
                        ?>
                    </ul>
                    <button data-remodal-action="confirm" class="postis-postbox-js remodal-confirm"><?php echo __("Choose Postbox", "postis"); ?></button>
                </div>
                <div class="postis-googlemap-section">
                     <div id="postis-map"></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
}
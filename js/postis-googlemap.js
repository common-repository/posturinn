"use strict";
jQuery(function($) {

    /**
        Load Google Map Modal
    **/
    var inst = $('[data-remodal-id=postis_googlemap_modal]').remodal();

    /**
        WC Checkout Update Trigger
    **/
    $(document.body).on('updated_checkout', function() {

        const shipping_wrapper = $('.woocommerce-shipping-methods');
        const get_shipping_method = shipping_wrapper.find('.shipping_method:checked').val();

        if (get_shipping_method == 'DPO') {
            $('#postis_dpo_postbox_field').hide();
            inst.open();
        }
    });

    /**
        GoogleMap Confirm Postbox selection Event
    **/
    $(document).on('confirmation', '.remodal', function() {

        var postboxes_json = $('li.postis-googlemap-active-loc').attr('data-postboxes');
        postboxes_json = JSON.parse(postboxes_json);
        var postboxId = postboxes_json.postboxId;
        var name = postboxes_json.name;
        $('select[name="postis_dpo_postbox"]').val(postboxId);
        $('.postis-googlemap-choosen-address span').html(name);
        console.log(postboxes_json);
        console.log('Confirmation button is clicked');
    });

    /**
        Open GoogleMap Modal Event
    **/
    $(document).on('click', '.postis-googlemap-modal-btn', function(e) {

        e.preventDefault();
        inst.open();
    });
});


/**
    Load GoogleMap
**/
var map;
var marker;

function initMap() {

    // The location of Iceland
    const Iceland = { lat: 64.9995555, lng: -18.9839165 };
    // The map, centered at Iceland
    map = new google.maps.Map(document.getElementById("postis-map"), {
        zoom: 6,
        center: Iceland,
    });
    // The marker, positioned at Iceland
    marker = new google.maps.Marker({
        position: Iceland,
        map: map,
    });
}

google.maps.event.addDomListener(window, 'load', initMap);

jQuery(document).on('click', '.postis-googlemap-location', function() {
    const lati = jQuery(this).attr('data-lat');
    const long = jQuery(this).attr('data-long');

    jQuery('.postis-googlemap-location').removeClass('postis-googlemap-active-loc');
    jQuery(this).addClass('postis-googlemap-active-loc');

    var panPoint = new google.maps.LatLng(lati, long);
    map.panTo(panPoint)
    new google.maps.Marker({
        position: panPoint,
        map: map,
    });
});

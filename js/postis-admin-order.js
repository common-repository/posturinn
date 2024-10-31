"use strict";
jQuery(function($) {

    // internal cropping editor
    $(".postis-view-shipment-js").click(function() {

        var order_id = $(this).attr('data-order-id');

        var uri_string = encodeURI('action=postis_show_shipment&order_id=' + order_id);
        var url = postis_order_vars.ajaxurl + '?' + uri_string;
        console.log(url);
        tb_show('Shipment', url);

        return false;
    });


    $(document).on("click", ".postis-shipment-options-js", function(e) {

        e.preventDefault();

        var order_id = $(this).attr('data-order-id');

        //console.log(order_id);

        var uri_string = encodeURI('action=postis_shipment_options&order_id=' + order_id);

        var url = postis_order_vars.ajaxurl + '?' + uri_string;
        tb_show('Extra', url);

        return false;
    });


    $(document).on("submit", ".postis-submit-shipment-options", function(e) {

        e.preventDefault();


        jQuery(".postis-shipment-create-loader").html('<img src="' + postis_order_vars.loader + '">').show();

        var data = $(this).serialize();
        var the_order_id = this.order_id.value;

        $.post(postis_order_vars.ajaxurl, data, function(resp) {
            console.log(resp)

            jQuery(".postis-shipment-create-loader").hide();

            if (resp.status == 'error') {
                if (resp.type == 'phonenumber') {
                    $('.postis-phonenumber-section').show();
                    $('.postis-phonenumber-section input[type="tel"]').attr('disabled', false);
                } else if (resp.type == 'other') {
                    $('.postis-generic-error-section').show();
                    $('#postis-resp-msg').html(resp.message);
                } else {
                    $('.postis-generic-error-section').show();
                    $('#postis-resp-msg').html(resp.message);
                }
                
                // var uri_string = encodeURI('action=postis_phonenumber_invalid&order_id=405');
                // var url = postis_order_vars.ajaxurl + '?' + uri_string;
                // console.log(url);
                // tb_show('Phone Number Invalid', url);
            }
            else {
                //alert(resp.message);
                var base_url = window.location.href.split('/wp-admin')[0];
                var get_the_pdf = base_url+'/wp-admin/admin-post.php?action=postis_pdf_action&order_id='+the_order_id;
                window.open(get_the_pdf, '_blank').focus();
                window.location.reload(true);
            }
            // if (resp.status == "success") {

            // }

        }, 'json');

    });

    $(document).on('change', '.postis-attach-field-js', function(e) {
        var tr = $(this).closest('.modern-row');
        var attach_with = tr.attr('data-attach_with');
        if (attach_with != 'no') {
            if ($(this).is(':checked')) {
                $('.option-line-' + attach_with).show();
                $('.option-line-' + attach_with).find('input').prop('disabled', false);
            } else {
                $('.option-line-' + attach_with).hide();
                $('.option-line-' + attach_with).find('input').prop('disabled', true);
            }
        }
    });

    $(document).on('change', '.postis-shipment-cod', function(e) {

        if ($(this).is(':checked')) {
            $('.postis-cod-alert-msg').show();
        }
        else {
            $('.postis-cod-alert-msg').hide();
        }
    });
    
    $(document).on('click', '.postis-accordion', function(e) {
        console.log('yes');
        $(this).next(".postis-accordion-panel").slideToggle();
    });

    $(document).on('click', '.posturinn_tariff_deleteRow', function(e) {
        $(this).closest(".tariff-container").remove();
    });


});

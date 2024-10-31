<?php 
/*
** Shipment Informations Template
*/

/* 
**========== Direct access not allowed =========== 
*/
if( ! defined('ABSPATH' ) ){ exit; }

$shipmentId      = postis_get_shipment_data($order_id, 'shipmentId');
$customerId      = postis_get_shipment_data($order_id, 'customerId');
$registrationKey = postis_get_shipment_data($order_id, 'registrationKey');
$statusText      = postis_get_shipment_data($order_id, 'statusText');

$sender_meta = postis_get_shipment_data($order_id, 'sender');

$recipient_meta = postis_get_shipment_data($order_id, 'recipient');

$options_meta = postis_get_shipment_data($order_id, 'options');


// get tracking meta
$trackingError    = isset($tracking['error']) ? $tracking['error'] : '';
$trackingErrormsg = isset($tracking['errorDetails']['errorMessage']) ? $tracking['errorDetails']['errorMessage'] : '';
$trackingNumber   = isset($tracking['trackingNumber']) ? $tracking['trackingNumber'] : '';
$deliveryLocation = isset($tracking['deliveryLocation']) ? $tracking['deliveryLocation'] : '';
$mailType         = isset($tracking['mailType']) ? $tracking['mailType'] : '';
$trackingStatus   = isset($tracking['status']) ? $tracking['status'] : array();
$trackingEvents   = isset($tracking['trackingEvents']) ? $tracking['trackingEvents'] : array();
// $statusmessage    = isset($tracking['Status']['StatusText']) ? $tracking['Status']['StatusText'] : '';
// postis_pa($trackingEvents);


// Create admin post URL for shipmet delete and create again
$admin_url = admin_url('admin-post.php');
$delete_shipment_url   = add_query_arg(array('action'=>'postis_delete_shipment','order_id'=> $order_id), $admin_url);

?>

<div class="postis-admin-created-shipment-wrapper">
    
    <div class="postis-create-shipment-again-btn">
        <a class="button postis-delete-shipment" href="<?php echo esc_url($delete_shipment_url); ?>"><?php _e('Create Shipment Again', "postis"); ?></a>
    </div>
    
    <h3><?php _e('Shipment Detail', "postis"); ?></h3>
    <table class="postis-order-table">
        <thead class="thead-light">
            <tr>
                <th><?php _e('Shipment ID', "postis"); ?></th>
                <th><?php _e('Customer ID', "postis"); ?></th>
                <th><?php _e('Registration Key', "postis"); ?></th>
            </tr>
        </thead>
        <tbody class="postis-order-tbody">
            <tr>
                <td><?php echo $shipmentId; ?></td>
                <td><?php echo $customerId; ?></td>
                <td><?php echo $registrationKey; ?></td>
            </tr>
        </tbody>
    </table>
    
    <h3><?php _e('Sender Detail', "postis"); ?></h3>
    <table class="postis-order-table">
        <thead class="thead-light">
            <tr>
                <th><?php _e('Name', "postis"); ?></th>
                <th><?php _e('User ID', "postis"); ?></th>
                <th><?php _e('Store ID', "postis"); ?></th>
                <th><?php _e('Address', "postis"); ?></th>
                <th><?php _e('Postcode', "postis"); ?></th>
                <th><?php _e('CountryCode', "postis"); ?></th>
            </tr>
        </thead>
        <tbody class="postis-order-tbody">
            <tr>
                <td><?php echo $sender_meta['name']; ?></td>
                <td><?php echo $sender_meta['userId']; ?></td>
                <td><?php echo $sender_meta['storeId']; ?></td>
                <td><?php echo $sender_meta['addressLine1']; ?></td>
                <td><?php echo $sender_meta['postcode']; ?></td>
                <td><?php echo $sender_meta['countryCode']; ?></td>
            </tr>
        </tbody>
    </table>
    
    <h3><?php _e('Recipient Detail', "postis"); ?></h3>
    <table class="postis-order-table">
        <thead class="thead-light">
            <tr>
                <th><?php _e('Name', "postis"); ?></th>
                <th><?php _e('Address', "postis"); ?></th>
                <th><?php _e('Postcode', "postis"); ?></th>
                <th><?php _e('CountryCode', "postis"); ?></th>
            </tr>
        </thead>
        <tbody class="postis-order-tbody">
            <tr>
                <td><?php echo $recipient_meta['name']; ?></td>
                <td><?php echo $recipient_meta['addressLine1']; ?></td>
                <td><?php echo $recipient_meta['postcode']; ?></td>
                <td><?php echo $recipient_meta['countryCode']; ?></td>
            </tr>
        </tbody>
    </table>
    
    
    <h3><?php _e('Shipment Method', "postis"); ?></h3>
    <table class="postis-order-table">
        <thead class="thead-light">
            <tr>
                <th><?php _e('Delivery Service ID', "postis"); ?></th>
                <th><?php _e('Delivery Service Name', "postis"); ?></th>
                <th><?php _e('Number Of Parcels', "postis"); ?></th>
            </tr>
        </thead>
        <tbody class="postis-order-tbody">
            <tr>
                <td><?php echo $options_meta['deliveryServiceId']; ?></td>
                <td><?php echo $options_meta['deliveryServiceName']; ?></td>
                <td><?php echo $options_meta['numberOfItems']; ?></td>
            </tr>
        </tbody>
    </table>
    
    <h3><?php _e('Tracking', "postis"); ?></h3>
    <?php 
    if (!$trackingError) {
    ?>
    <table class="postis-order-table">
        <thead class="thead-light">
            <tr>
                <th><?php _e('Tracking Number', "postis"); ?></th>
                <th><?php _e('Delivery Location', "postis"); ?></th>
                <th><?php _e('Mail Type', "postis"); ?></th>
            </tr>
        </thead>
        <tbody class="postis-order-tbody">
            <tr>
                <td><?php echo $trackingNumber; ?></td>
                <td><?php echo $deliveryLocation; ?></td>
                <td><?php echo $mailType; ?></td>
            </tr>
        </tbody>
    </table>
    
    <button class="postis-accordion"><?php _e('Click Here To See Tracking Status', "postis"); ?></button>
    <div class="postis-accordion-panel">
        <table class="postis-order-table">
            <tbody class="postis-order-tbody">
            <?php 
            foreach ($trackingStatus as $status_key => $status_val) {
                ?>
                    <tr>
                        <th><?php echo $status_key ?></th>
                        <td><?php echo $status_val ?></td>
                    </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    
    <button class="postis-accordion"><?php _e('Click Here To See Tracking Events', "postis"); ?></button>
    <div class="postis-accordion-panel">
        <?php
        if (!empty($trackingEvents)) {
            $event_counter = 1;
            foreach ($trackingEvents as $event_index => $event_meta) {
                ?>
                <div class="postis-tracking-event" style="background: #eee;width: 100%;display: block;clear: both;padding: 15px;margin-bottom: 5px;">
                    <div><strong><?php echo sprintf(__("Event %s: %s", "postis"), $event_counter, date("F j, Y, g:i a", strtotime($event_meta['timestamp']))); ?></strong></div>
                    <div><strong><?php _e('Description:', 'postis'); ?></strong> <?php echo $event_meta['description']; ?></div>
                    <div><strong><?php _e('Location:', 'postis'); ?></strong> <?php echo $event_meta['location']['name']; ?></div>
                </div>
                <?php
                $event_counter++;
            }
        } else {
            ?>
            <p><?php _e('No tracking events available.', 'postis'); ?></p>
            <?php
        }
        ?>
    </div>
    <?php }else{ ?>
        
    <?php echo sprintf(__("Server Response Error: <strong>%s</strong>", "postis"), $trackingErrormsg); ?>
        
    <?php } ?>
    
</div>
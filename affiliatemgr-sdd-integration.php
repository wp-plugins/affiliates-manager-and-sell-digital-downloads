<?php
/*
Plugin Name: Affiliates Manager WP iSell Integration
Plugin URI: https://wpaffiliatemanager.com
Description: Process an affiliate commission via Affiliates Manager after a WP iSell checkout.
Version: 1.0
Author: wp.insider
Author URI: https://wpaffiliatemanager.com
*/

function wpam_isell_payment_completed($ipn_data, $order_id)
{
    $custom_data = $ipn_data['custom'];
    WPAM_Logger::log_debug('WP iSell Integration - payment completed hook fired. Custom field value: '.$custom_data);
    $custom_values = array();
    parse_str($custom_data, $custom_values);
    if(isset($custom_values['wpam_tracking']) && !empty($custom_values['wpam_tracking']))
    {
        $tracking_value = $custom_values['wpam_tracking'];
        WPAM_Logger::log_debug('WP iSell Integration - Tracking data present. Need to track affiliate commission. Tracking value: '.$tracking_value);
        $purchaseLogId = $ipn_data['txn_id'];
        $purchaseAmount = $ipn_data['mc_gross']; //TODO - later calculate sub-total only
        $strRefKey = $tracking_value;
        $requestTracker = new WPAM_Tracking_RequestTracker();
        $requestTracker->handleCheckoutWithRefKey( $purchaseLogId, $purchaseAmount, $strRefKey);
        WPAM_Logger::log_debug('WP iSell Integration - Commission tracked for transaction ID: '.$purchaseLogId.'. Purchase amt: '.$purchaseAmount);
    }
}
add_action("isell_payment_completed", "wpam_isell_payment_completed", 10, 2);

function wpam_isell_add_custom_parameters($parameters)
{
    if(isset($_COOKIE[WPAM_PluginConfig::$RefKey]))
    {
        $name = 'wpam_tracking';
        $value = $_COOKIE[WPAM_PluginConfig::$RefKey];
        $new_val = $name.'='.$value;
        $current_val = $parameters['custom'];
        if(empty($current_val)){
            $parameters['custom'] = $new_val;
        }
        else{
            $parameters['custom'] = $current_val.'&'.$new_val;
        }
        WPAM_Logger::log_debug('WP iSell Integration - Adding custom field value. New value: '.$parameters['custom']);
    }
    return $parameters;
}

add_filter("isell_payment_gateway_parameters", "wpam_isell_add_custom_parameters");
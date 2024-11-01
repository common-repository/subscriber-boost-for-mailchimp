<?php
/*
 * Plugin Name: Subscriber Boost for MailChimp
 * Version: 0.1
 * Plugin URI: https://wordpress.org/plugins/subscriber-boost-for-mailchimp
 * Description: Get a boost to your newsletter subscribers.
 * Author: ConvertWise <hello@convertwise.io>
 * Requires at least: 4.0
 * Tested up to: 4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once('SubscriberBoostBackend.php');
require_once('SubscriberBoostFrontend.php');

if (is_admin()) {
    new SubscriberBoostBackend();
} else {
    new SubscriberBoostFrontend();
}

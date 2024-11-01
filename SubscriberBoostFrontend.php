<?php

class SubscriberBoostFrontend {
    private $apiNamespace = 'subscriber-boost/v1';

    public function __construct()
    {
        // Load plugin settings
        $this->options = get_option('subscriber_boost_mailchimp');

        // register api routes
        add_action('rest_api_init', array($this, 'register_api_routes'));

        // queue js scripts
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));

        // load widget
        add_action('wp_footer', array($this, 'load_widget'));

        // load mailchimp modal
        add_action('wp_footer', array($this, 'load_mailchimp_modal'), $priority = 100);
    }

    public function register_api_routes()
    {
        register_rest_route($this->apiNamespace, '/increment-widget-load-count', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'increment_widget_load_count'),
        ));

        register_rest_route($this->apiNamespace, '/subscribe-member-to-mailchimp-list', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'subscribe_member_to_mailchimp_list'),
        ));
    }

    public function increment_widget_load_count()
    {
        $today = current_time('Y-m-d');
        $stats = get_option('subscriber_boost_statistics') ?: [];

        $stats[$today] = isset($stats[$today]) ? $stats[$today] + 1 : 1;
        update_option('subscriber_boost_statistics', $stats);

        return new WP_REST_Response(array('response' => 'all good!'), 200);
    }

    public function subscribe_member_to_mailchimp_list(/* WP_REST_Request */ $request)
    {
        $token   = $this->options['mailchimp_api_key'];
        $payload = json_decode($request->get_body(), $array = true);

        if (is_null($payload)) {
            return new WP_Error('empty payload', array('status' => 500));
        }

        $region   = $payload['region'];
        $listId   = $payload['listId'];
        $email    = $payload['email'];
        $url      = 'https://' . $region . '.api.mailchimp.com/3.0/lists/' . $listId . '/members';
        $headers  = array('Content-Type' => 'application/json; charset=utf-8', 'Authorization' => 'apikey ' . $token);
        $body     = array('email_address' => $email, 'status' => 'subscribed');
        $response = wp_remote_post($url, array('headers' => $headers, 'body' => json_encode($body)));

        return new WP_REST_Response(array('response' => 'all good!'), 200);
    }

    public function load_assets()
    {
        $assets_url = esc_url(trailingslashit(plugins_url('assets', $file = __FILE__)));

        wp_register_style('subscriber-boost-widget-css', $assets_url . 'css/widget.css', array(), '1.0.1');
        wp_register_script('subscriber-boost-axios-js', $assets_url . 'js/axios-0.18.0.js', array(), '1.0.1');
        wp_register_script('subscriber-boost-anime-js', $assets_url . 'js/anime-2.2.0.js', array(), '1.0.1');
        wp_register_script('subscriber-boost-vue-js', $assets_url . 'js/vue-2.5.16.js', array(), '1.0.1');
        wp_register_script('subscriber-boost-widget-js', $assets_url . 'js/widget.js', array('subscriber-boost-anime-js', 'subscriber-boost-vue-js'), '1.0.1', true);

        wp_enqueue_style('subscriber-boost-widget-css');
        wp_enqueue_script('subscriber-boost-axios-js');
        wp_enqueue_script('subscriber-boost-anime-js');
        wp_enqueue_script('subscriber-boost-vue-js');
        wp_enqueue_script('subscriber-boost-widget-js');
    }

    public function load_widget()
    {
        $logoId      = isset($this->options['logo_id']) ? $this->options['logo_id'] : '';
        $logoUrl     = $logoId !== '' ? wp_get_attachment_thumb_url($logoId) : '';
        $brandColor  = isset($this->options['brand_color']) ? esc_attr($this->options['brand_color']) : '#3670CA';
        $gdprEnabled = isset($this->options['gdpr_enabled']) ? esc_attr($this->options['gdpr_enabled']) : 'yes';
        $mcValues    = explode('|', isset($this->options['selected_mailing_list']) ? $this->options['selected_mailing_list'] : '||');
        $collapsedWidgetTitle = isset($this->options['collapsed_widget_title']) ? esc_attr($this->options['collapsed_widget_title']) : 'Subscribe to our newsletter';
        $collapsedWidgetSubtitle = isset($this->options['collapsed_widget_subtitle']) ? esc_attr($this->options['collapsed_widget_subtitle']) : 'Sign up for our newsletter >';
        $expandedWidgetTitle = isset($this->options['expanded_widget_title']) ? esc_attr($this->options['expanded_widget_title']) : 'Sign up for our newsletter for brand new content';
        $expandedWidgetSubtitleInput = isset($this->options['widget_subtitle_input']) ? esc_attr($this->options['widget_subtitle_input']) : 'Join our subscribers for the lastest news and updates delivered directly to your inbox.';
        $expandedWidgetLabelEmailInput = isset($this->options['widget_label_email_input']) ? esc_attr($this->options['widget_label_email_input']) : 'Your Email';
        $expandedWidgetEncouragementText = isset($this->options['expanded_widget_encouragement_text']) ? esc_attr($this->options['expanded_widget_encouragement_text']) : 'Give it a try! - it only takes a click to unsubscribe.';
        $expandedWidgetCallToActionButton = isset($this->options['expanded_widget_call_to_action_button']) ? esc_attr($this->options['expanded_widget_call_to_action_button']) : 'Subscribe';
        $mcRegion    = $mcValues[0];
        $mcUuid      = $mcValues[1];
        $mcListId    = $mcValues[2];

        print ''
            . '<div id="app">'
                . '<widget
                        v-on:collapse-widget="toggleWidget"
                        v-on:newsletter-signup-modal="showSignupModal"
                        v-on:newsletter-signup-request="sendSignupRequest"
                        brand-color="' . htmlentities($brandColor) . '"
                        gdpr-enabled="' . htmlentities($gdprEnabled) . '"
                        collapsed-widget-title="' . htmlentities($collapsedWidgetTitle) . '"
                        expanded-widget-title="' . htmlentities($expandedWidgetTitle) . '"
                        expanded-widget-subtitle-input="' . htmlentities($expandedWidgetSubtitleInput) . '"
                        expanded-widget-call-to-action-button="' . htmlentities($expandedWidgetCallToActionButton) . '"
                        mc-uuid="' . htmlentities($mcUuid) . '"
                        mc-region="' . htmlentities($mcRegion) . '"
                        mc-list-id="' . htmlentities($mcListId) . '"
                        :show="show"
                        :collapsed="collapsed"
                        :subscribed="subscribed"
                        :subscribe-button-clicked="subscribeButtonClicked"></widget>'
            . '</div>';
    }

    public function load_mailchimp_modal()
    {
        print ''
            . '<script type="text/javascript" src="//downloads.mailchimp.com/js/signup-forms/popup/embed.js" data-dojo-config="usePlainJson: true, isDebug: false"></script>'
            . '<script type="text/javascript">require(["mojo/signup-forms/Loader"], function(L) { window.mailChimpModal = L })</script>';
    }
}

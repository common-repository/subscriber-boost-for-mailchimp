<?php

class SubscriberBoostBackend {
    private $region;
    private $options;
    private $apiConnected = false;
    private $apiLists = array();
    private $apiErrorMessage = '';

    // statistics
    private $notificationsSent = 0;
    private $totalSubscribers = 0;
    private $subscribersLast30Days = 0;
    private $conversionRate = 0;

    public function __construct()
    {
        // Load plugin settings
        $this->options = get_option('subscriber_boost_mailchimp');
        $this->statistics = get_option('subscriber_boost_statistics');

        // Total notifications sent
        $earliestDate = strtotime('-30 days', strtotime(current_time('Y-m-d')));
        foreach ($this->statistics as $date => $count) {
            if (strtotime($date) > $earliestDate) {
                $this->notificationsSent = $this->notificationsSent + $count;
            }
        }

        // This action is used to add extra submenus and menu options to the admin panel's menu structure.
        // It runs after the basic admin panel menu structure is in place.
        // This action mustn't be placed in an admin_init action function, because the admin_init action is called after admin_menu.
        add_action('admin_menu', array($this, 'add_plugin_page'));

        // This is triggered before any other hook when a user accesses the admin area.
        // This hook doesn't provide any parameters, so it can only be used to callback a specified function.
        add_action('admin_init', array($this, 'page_init'));

        // load on settings page
        add_action('toplevel_page_subscriber-boost-for-mailchimp', array($this, 'check_mailchimp_api_key'));
        add_action('toplevel_page_subscriber-boost-for-mailchimp', array($this, 'load_assets'));
    }

    public function check_mailchimp_api_key()
    {
        if ( ! isset($this->options['mailchimp_api_key'])) {
            return;
        }

        if ($this->options['mailchimp_api_key'] === '') {
            return;
        }

        $token = $this->options['mailchimp_api_key'];
        $tokenParts = explode('-', $token);

        if (count($tokenParts) === 1) {
            return;
        }

        $this->region = $tokenParts[1];
        $headers  = array('Authorization' => 'apikey ' . $token);
        $listsUrl = 'https://' . $this->region . '.api.mailchimp.com/3.0/lists';
        $response = wp_remote_get($listsUrl, $options = array('headers' => $headers));

        if ($response instanceof WP_Error) {
            return;
        }
        $responseBody = json_decode($response['body']);

        if ($response['response']['code'] !== 200) {
            $this->apiErrorMessage = $responseBody->title;
            return;
        }
        $this->apiConnected = true;
        $this->apiLists = json_decode($response['body'])->lists;

        // get total subscribers
        foreach ($this->apiLists as $list) {
            $this->totalSubscribers = $this->totalSubscribers + $list->stats->member_count;

            // get signups last 30 days
            $activityUrl = $listsUrl . '/' . $list->id . '/activity?count=30&fields=activity.day,activity.subs';
            $response    = wp_remote_get($activityUrl, $options = array('headers' => $headers));
            foreach (json_decode($response['body'])->activity as $activity) {
                $this->subscribersLast30Days = $this->subscribersLast30Days + $activity->subs;
            }
        }

        // calculate conversion
        $this->conversionRate = round(($this->subscribersLast30Days / $this->notificationsSent) * 100);
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_menu_page('Settings', 'Subscriber Boost', 'manage_options', 'subscriber-boost-for-mailchimp', array($this, 'create_admin_page'), 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDggOSIgZmlsbD0iYmxhY2siPjxwYXRoIGQ9Ik04LDEuNDU5NjMzMDggTDYuODM4MjIxMSwyLjYxNjg1MTU0IEM2LjI5NzY0NTM2LDIuMDEwODMxNTcgNS41MDE4NzY5LDEuNjI3ODU4NjMgNC42MTQ1NzMxOSwxLjYyNzg1ODYzIEMyLjk4NjAzMzgsMS42Mjc4NTg2MyAxLjY2NTg0MTc4LDIuOTE3OTQ4NzMgMS42NjU4NDE3OCw0LjUwOTM1NTUxIEMxLjY2NTg0MTc4LDYuMTAwNzYyMjkgMi45ODYwMzM4LDcuMzkwODUyMzkgNC42MTQ1NzMxOSw3LjM5MDg1MjM5IEM1LjQ3OTgwMDM2LDcuMzkwODUyMzkgNi4yNTc5OTA1LDcuMDI2Njk5NDcgNi43OTc0MDAzNSw2LjQ0NjY3Njk5IEw2Ljc5NzQwMDM1LDYuNTA0NzA2NDYgTDcuOTg0Nzc5MTQsNy41NTY1MDk0NSBDNy4xNDM2MjUwOCw4LjQ0NDI2OTkyIDUuOTQwNzI1MzksOSA0LjYwNDk5OTM5LDkgQzIuMDYxNzI4NDUsOSAwLDYuOTg1MjgxMzcgMCw0LjUgQzAsMi4wMTQ3MTg2MyAyLjA2MTcyODQ1LDAgNC42MDQ5OTkzOSwwIEM1Ljk0ODgwMzE4LDAgNy4xNTgxNjkyNiwwLjU2MjQ3MTk0NSA4LDEuNDU5NjMzMDggWiI+PC9wYXRoPjxlbGxpcHNlIGN4PSI0LjYxNTM4NDYyIiBjeT0iNC41IiByeD0iMS44NDYxNTM4NSIgcnk9IjEuOCI+PC9lbGxpcHNlPjwvc3ZnPg==');
    }

    public function load_assets() {
        $assets_url = esc_url(trailingslashit(plugins_url('assets', $file = __FILE__)));

        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');
        wp_enqueue_media();

        wp_register_script('subscriber-boost-settings-js', $assets_url . 'js/settings.js', array('farbtastic', 'jquery'), '1.0.0');
        wp_enqueue_script('subscriber-boost-settings-js');
        wp_register_style('subscriber-boost-settings-css', $assets_url . 'css/settings.css', array(), '1.0.0');
        wp_enqueue_style('subscriber-boost-settings-css');
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Subscriber Boost <span style="font-weight:200">for MailChimp</span></h1>
            <div class="statistics">
                <div class="statistics__block">
                    <div class="statistics__block-figures"><?php echo $this->notificationsSent; ?></div>
                    <div class="statistics__block-title">Notifications Sent</div>
                </div>
                <div class="statistics__block">
                    <div class="statistics__block-figures"><?php echo $this->totalSubscribers; ?></div>
                    <div class="statistics__block-title">Total Subscribers</div>
                </div>
                <div class="statistics__block">
                    <div class="statistics__block-figures"><?php echo $this->subscribersLast30Days; ?></div>
                    <div class="statistics__block-title">Subscribers</div>
                    <div class="statistics__block-subtitle">Last 30 days</div>
                </div>
                <div class="statistics__block">
                    <div class="statistics__block-figures"><?php echo $this->conversionRate; ?>%</div>
                    <div class="statistics__block-title">Conversion Rate</div>
                    <div class="statistics__block-subtitle">Last 30 days</div>
                </div>
            </div>
            <form method="post" action="options.php">
                <?php
                settings_fields('subscriber_boost_mailchimp_group');
                do_settings_sections('api_key_section');
                submit_button();
                do_settings_sections('account_section');
                ?>
                <table class="widefat fixed striped" style="margin: 20px 0">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>List ID</th>
                        <th>Subscribers</th>
                        <th>Edit Subscriber Form</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->apiLists as $list) {?>
                        <tr>
                            <td><?php echo $list->name;?></td>
                            <td><?php echo $list->id;?></td>
                            <td><?php echo $list->stats->member_count;?></td>
                            <td><a href="https://admin.mailchimp.com/lists/signup-forms/popup/editor?id=<?php echo $list->web_id;?>" target="_blank">Edit</a></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php
                do_settings_sections('customize_section');
                submit_button();
                ?>
                <div>
                    If you enjoyed using Subscriber Boost for MailChimp please leave a 5* review here. A massive thank you for the support!<br />
                    Want to give us feedback? Send your thoughts <a href="mailto:hello@pivotbot.com">hello@pivotbot.com</a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting('subscriber_boost_mailchimp_group', 'subscriber_boost_mailchimp', array($this, 'sanitize'));

        add_settings_section('api_key_section_id', 'MailChimp API Key', array($this, 'api_key_section_header'), 'api_key_section');
        add_settings_field('mailchimp_api_key', 'API Key', array($this, 'mailchimp_api_key_input'), 'api_key_section', 'api_key_section_id');
        add_settings_field('mailchimp_api_status', 'Status', array($this, 'mailchimp_api_status_label'), 'api_key_section', 'api_key_section_id');

        add_settings_section('api_key_section_id', 'Your MailChimp Account', array($this, 'account_section_header'), 'account_section');

        add_settings_section('customize_section_id', 'Customize Plugin', array($this, 'customize_section_header'), 'customize_section');
        add_settings_field('mailchimp_mailing_list', 'Selected Mailing List', array($this, 'mailchimp_mailing_list_input'), 'customize_section', 'customize_section_id');
        add_settings_field('enable_gdpr', 'Enable GDPR', array($this, 'enable_gdpr_input'), 'customize_section', 'customize_section_id');
        add_settings_field('collapsed_widget_title', 'Collapsed Widget Title', array($this, 'collapsed_widget_title_input'), 'customize_section', 'customize_section_id');
        add_settings_field('expanded_widget_title', 'Expanded Widget Title', array($this, 'expanded_widget_title_input'), 'customize_section', 'customize_section_id');
        add_settings_field('expanded_widget_subtitle', 'Expanded Widget Subtitle', array($this, 'expanded_widget_subtitle_input'), 'customize_section', 'customize_section_id');
        add_settings_field('expanded_widget_call_to_action_button', 'Expanded Widget Button', array($this, 'expanded_widget_call_to_action_button_input'), 'customize_section', 'customize_section_id');
        add_settings_field('brand_color', 'Brand Color', array($this, 'brand_color_input'), 'customize_section', 'customize_section_id');
    }

    public function api_key_section_header()
    {
        print 'Follow this <a href="https://admin.mailchimp.com/account/api" target="_blank">link</a> to create an API Key in your MailChimp account';
    }

    public function mailchimp_api_key_input()
    {
        printf(
            '<input type="text" class="api_key_text_input" name="subscriber_boost_mailchimp[mailchimp_api_key]" value="%s" />',
            isset( $this->options['mailchimp_api_key'] ) ? esc_attr($this->options['mailchimp_api_key']) : ''
        );
    }

    public function mailchimp_api_status_label()
    {
        print $this->apiConnected
            ? '<span style="color:green">CONNECTED!</span>'
            : '<span style="color:gray">NOT CONNECTED!' . ($this->apiErrorMessage !== '' ? ' [' . $this->apiErrorMessage . ']' : '') . '</span><span style="margin-left:0.2rem">Get an API Key from MailChimp <a href="https://admin.mailchimp.com/account/api" target="_blank">here</a></span>';
    }

    public function account_section_header()
    {
        print 'These are the lists that were found in your MailChimp account. Select the one you want to use with Subscriber Boost in the dropdown below.';
    }

    public function customize_section_header()
    {
        print '';
    }

    public function enable_gdpr_input()
    {
        $options  = array('yes' => 'Yes', 'no' => 'No');
        $selected = isset($this->options['gdpr_enabled']) ? $this->options['gdpr_enabled'] : '';

        print '<select name="subscriber_boost_mailchimp[gdpr_enabled]">';
        foreach ($options as $key => $name) {
            print '<option value="' . $key . '"' . ($key === $selected ? ' selected="selected"' : '') . '>' . $name . '</option>';
        }
        print '</select>';
        print '<div style="margin:20px 0 0">When <strong>GDPR is enabled</strong>, a signup modal window is shown where the user can opt-in for your newsletter.</div>';
        print '<div>When <strong>GDPR is disabled</strong>, users will be subscribed to your newsletter list directly without a modal being shown.</div>';
        print '<div style="margin:20px 0 0">If your MailChimp list is <strong>not a GDPR enabled mailing list</strong> you have to add support for it. Instructions on how to add GDPR support to your mailing list can be found <a href="https://kb.mailchimp.com/accounts/management/collect-consent-with-gdpr-forms#Set-Up-Your-GDPR-Friendly-Signup-Form" target="_blank">here</a>.</div>';
        print '<div style="margin:20px 0 0">If you <strong>do not see your GDPR fields in the modal</strong> after you have added them to your subscription form, click on the "Edit Subscriber Form" link in the table above for your list and click the "Generate Code" button to the bottom right in the MailChimp Popup form editor.</div>';
    }

    public function collapsed_widget_title_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[collapsed_widget_title]" value="%s" />',
            isset( $this->options['collapsed_widget_title'] ) ? esc_attr($this->options['collapsed_widget_title']) : 'Subscribe to our newsletter'
        );
    }

    public function collapsed_widget_subtitle_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[collapsed_widget_subtitle]" value="%s" />',
            isset( $this->options['collapsed_widget_subtitle'] ) ? esc_attr($this->options['collapsed_widget_subtitle']) : 'Sign up for our newsletter >'
        );
    }

    public function expanded_widget_title_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[expanded_widget_title]" value="%s" />',
            isset( $this->options['expanded_widget_title'] ) ? esc_attr($this->options['expanded_widget_title']) : 'Sign up for our newsletter for brand new content'
        );
    }

    public function expanded_widget_subtitle_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[widget_subtitle_input]" value="%s" />',
            isset( $this->options['widget_subtitle_input'] ) ? esc_attr($this->options['widget_subtitle_input']) : 'Join our subscribers for the lastest news and updates delivered directly to your inbox.'
        );
    }

    public function expanded_widget_label_email_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[widget_label_email_input]" value="%s" />',
            isset( $this->options['widget_label_email_input'] ) ? esc_attr($this->options['widget_label_email_input']) : 'Your Email'
        );
    }

    public function expanded_widget_encouragement_text_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[expanded_widget_encouragement_text]" value="%s" />',
            isset( $this->options['expanded_widget_encouragement_text'] ) ? esc_attr($this->options['expanded_widget_encouragement_text']) : 'Give it a try! - it only takes a click to unsubscribe.'
        );
    }

    public function expanded_widget_call_to_action_button_input()
    {
        printf(
            '<input type="text" class="settings_text_input" name="subscriber_boost_mailchimp[expanded_widget_call_to_action_button]" value="%s" />',
            isset( $this->options['expanded_widget_call_to_action_button'] ) ? esc_attr($this->options['expanded_widget_call_to_action_button']) : 'Subscribe'
        );
    }

    public function mailchimp_mailing_list_input()
    {
        $selected = isset($this->options['selected_mailing_list']) ? $this->options['selected_mailing_list'] : '';
        print '<select name="subscriber_boost_mailchimp[selected_mailing_list]">';
        foreach ($this->apiLists as $list) {
            // "https://<user>.<region>.list-manage.com/subscribe?u=<uuid>&id=<id>"
            parse_str(parse_url($list->subscribe_url_long)['query'], $query);
            $uuid = $query['u'];
            $key  = $this->region . '|' . $uuid . '|' . $list->id;
            print '<option value="' . $key . '"' . ($key === $selected ? ' selected="selected"' : '') . '>' . $list->name . '</option>';
        }
        print '</select>';
    }

    public function brand_color_input()
    {
        $brandColor = isset($this->options['brand_color']) ? esc_attr($this->options['brand_color']) : '#3670CA';

        print ''
            . '<div class="color-picker" style="position:relative;">'
            .     '<input id="brand-color-color-picker" type="text" name="subscriber_boost_mailchimp[brand_color]" class="color" value="' . $brandColor . '" />'
            .     '<button type="button" class="open-color-picker button button-secondary">Open Color Picker</button>'
            .     '<div style="position:absolute; background:#fff; z-index:99; border-radius:100%;" class="colorpicker"></div>'
            . '</div>'
            . '<label for="brand-color-color-picker">'
            .     '<span class="description">This is the color that will be displayed in the widget.</span>'
            . '</label>';
    }

    public function add_logo_input()
    {
        $logoId  = isset($this->options['logo_id']) ? $this->options['logo_id'] : '';
        $logoUrl = $logoId !== '' ? wp_get_attachment_thumb_url($logoId) : '';

        print ''
            . '<img id="logo_preview" class="image_preview" src="' . $logoUrl . '" />'
            . '<br />'
            . '<input id="logo_button" type="button" class="image_upload_button button" value="Upload new image" data-uploader_title="Upload an image" data-uploader_button_text="Use image" />'
            . '<input id="logo_delete" type="button" class="image_delete_button button" value="Remove image" />'
            . '<input id="logo" type="hidden" class="image_data_field" name="subscriber_boost_mailchimp[logo_id]" value="' . $logoId . '" />'
            . '<br />'
            . '<label for="logo_button">'
            .     '<span class="description">This is the image that will be displayed in the widget.</span>'
            . '</label>';
    }

    public function sanitize($input)
    {
        $input['mailchimp_api_key'] = sanitize_text_field($input['mailchimp_api_key']);

        return $input;
    }
}

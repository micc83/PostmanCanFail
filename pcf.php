<?php
/*
Plugin Name: PostmanCanFail
Plugin URI:  http://www.comodolab.it
Description: Notice via mail() or Rollbar in case of Postman errors. Postman logging must be enabled.
Version:     1.0.0
Author:      Comodolab.it
Author URI:  http://www.comodolab.it
License:     MIT
License URI: https://opensource.org/licenses/MIT
*/
defined('ABSPATH') or die('No script kiddies please!');

include 'models/PostmanError.php';

/**
 * Class PostmanCanFail.
 */
class PostmanCanFail
{
    const ROLLBAR_ENDPOINT = 'https://api.rollbar.com/api/1/item/';

    /**
     * Log types:
     */
    const LOG_VIA_MAIL = 1;
    const LOG_VIA_ROLLBAR = 2;
    const LOG_VIA_BOTH = 99;

    /**
     * @var string
     */
    private $notificationEmail;

    /**
     * @var string
     */
    private $rollbarToken;

    /**
     * @var int
     */
    private $loggingType;

    /**
     * PostmanNotifier constructor.
     */
    public function __construct()
    {
        $this->notificationEmail = get_option('pcf_notification_email');
        $this->rollbarToken      = get_option('pcf_rollbar_token');
        $this->loggingType       = (int)get_option('pcf_enable_type');

        add_action('admin_menu', [$this, 'registerOptionsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_filter('wp_insert_post_data', [$this, 'listenForNewPostmanLog']);
        add_action('wp_ajax_test_pcf', [$this, 'testConfig']);

        register_deactivation_hook(__FILE__, [$this, 'onDeactivate']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'addActionLinks']);
    }

    /**
     * Postman log each sent message as a post and the
     * post excerpt is used for error messages so...
     *
     * @param $data
     *
     * @return mixed
     */
    public function listenForNewPostmanLog($data)
    {
        if ('postman_sent_mail' === $data['post_type'] && trim($data['post_excerpt'])) {
            $error = new PostmanError($data);
            try {
                $this->send($error);
            } catch (Exception $e) {
                // Do nothing...
            }
        }

        return $data;
    }

    /**
     * @param PostmanError $error
     *
     * @return array
     */
    public function send(PostmanError $error)
    {
        $results = [];
        if ($this->loggingViaMailIsEnabled()) {
            $results['mail'] = $this->sendViaMail($error);
        }
        if ($this->loggingViaRollbarIsEnabled()) {
            $results['rollbar'] = $this->sendViaRollbar($error);
        }

        return $results;
    }

    /**
     * @return bool
     */
    private function loggingViaMailIsEnabled()
    {
        return in_array($this->loggingType, [static::LOG_VIA_MAIL, static::LOG_VIA_BOTH]);
    }

    /**
     * @return bool
     */
    private function loggingViaRollbarIsEnabled()
    {
        return in_array($this->loggingType, [static::LOG_VIA_ROLLBAR, static::LOG_VIA_BOTH]);
    }

    /**
     * Send error via mail().
     *
     * @param PostmanError $error
     *
     * @throws Exception
     */
    private function sendViaMail(PostmanError $error)
    {
        if ( ! is_email($this->notificationEmail)) {
            throw new Exception('Wrong or empty email address');
        }

        $result = mail(
            $this->notificationEmail,
            'Postman error',
            'Postman error on ' . site_url() . "\n\n> {$error->message}"
        );

        if ( ! $result) {
            throw new Exception('There was an error sending the notice through mail');
        }
    }

    /**
     * Send error via Rollbar.
     *
     * @param PostmanError $error
     *
     * @throws Exception
     */
    private function sendViaRollbar(PostmanError $error)
    {
        if (empty($this->rollbarToken)) {
            throw new Exception('You must provide a Rollbar token');
        }

        $call = wp_remote_post(static::ROLLBAR_ENDPOINT, [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'    => json_encode([
                "access_token" => $this->rollbarToken,
                "data"         => [
                    "environment" => "production",
                    "body"        => [
                        "message" => [
                            "body"            => $error->message,
                            "message_subject" => $error->subject,
                            "message_body"    => $error->content,
                        ]
                    ],
                    "request"     => [
                        "url" => site_url()
                    ]
                ]
            ]),
        ]);

        if ($call instanceof WP_Error) {
            throw new Exception($call->get_error_message());
        }

        if ($call['response']['code'] !== 200) {
            $body = json_decode($call['body']);
            throw new Exception($body->message);
        }
    }

    /**
     * Register the options page.
     */
    public function registerOptionsPage()
    {
        add_submenu_page(
            'tools.php',
            'PostmanCanFail Settings',
            'PostmanCanFail',
            'administrator',
            __FILE__,
            [$this, 'settingsPage']
        );
    }

    /**
     * Register settings.
     */
    public function registerSettings()
    {
        register_setting('postmanCanFail', 'pcf_enable_type');
        register_setting('postmanCanFail', 'pcf_notification_email');
        register_setting('postmanCanFail', 'pcf_rollbar_token');
    }

    /**
     * Test the provided config.
     */
    public function testConfig()
    {
        if (
            empty($_POST['pcf_enable_type']) ||
            ! isset($_POST['pcf_rollbar_token']) ||
            ! isset($_POST['pcf_notification_email'])
        ) {
            wp_send_json_error('Something wrong with your configuration...');
        }

        $this->notificationEmail = $_POST['pcf_notification_email'];
        $this->rollbarToken      = $_POST['pcf_rollbar_token'];
        $this->loggingType       = (int)$_POST['pcf_enable_type'];

        try {
            $this->send(new PostmanError([
                'post_excerpt' => 'Test PostmanCanFail error',
                'post_title'   => 'Test PostmanCanFail message',
                'post_content' => 'Test PostmanCanFail message content'
            ]));
        } catch (Exception $e) {
            wp_send_json_error(ucfirst($e->getMessage()) . '!');
        }

        wp_send_json_success('The test was run successfully!');
    }

    /**
     * Load the settings page.
     */
    public function settingsPage()
    {
        include 'views/admin.php';
    }

    /**
     * @param array $links
     *
     * @return array
     */
    public function addActionLinks($links)
    {
        return array_merge([
            '<a href="' . admin_url('tools.php?page=pcf%2Fpcf.php') . '">' . __('Settings') . '</a>',
        ], $links);
    }

    /**
     * On deactivate.
     */
    public function onDeactivate()
    {
        delete_option('pcf_notification_email');
        delete_option('pcf_rollbar_token');
        delete_option('pcf_enable_type');
    }
}

/**
 * Initialize plugin.
 */
new PostmanCanFail;
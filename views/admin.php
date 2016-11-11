<?php defined('ABSPATH') or die('No script kiddies please!') ?>
<style>
    .postman-notifier--panel {
        background: #fff;
        padding: 15px;
        border: 1px solid #ddd;
        max-width: 768px;
        overflow: hidden;
    }

    .wrap .postman-notifier--title {
        font-size: 20px;
        font-weight: 200;
        background: #eee;
        padding: 10px;
        display: block;
    }

    .postman-notifier--button {
        float: right;
    }

    .wrap .postman-notifier--button .button {
        height: 40px;
        line-height: 39px;
        display: inline-block;
    }

    .wrap .button-success, .wrap .button-success:focus {
        background: #4a8618;
        border-color: #3d6f14 !important;
        text-shadow: 0 -1px 1px #3d6f14, 1px 0 1px #3d6f14, 0 1px 1px #3d6f14, -1px 0 1px #3d6f14;
    }

    .wrap .button-success:hover {
        background: #54981b;
    }

    .wrap .button-success:active {
        background: #54981b;
        box-shadow: inset 0 2px 0 #3d6f14;
    }

    .wrap .button-success[disabled] {
        background: #8ac35a !important;
        color: #fff !important;
    }

    .postman-notifier--table label {
        display: block;
        margin: 5px 0;
    }

    .pcf-notification {
        padding: 10px;
        margin: 5px 0;
        color: #fff;
    }

    .pcf-notification-error {
        background: #ef5454;
        border: 1px solid #f00;
    }

    .pcf-notification-success {
        background: #44b344;
        border: 1px solid #089a00;
    }
</style>

<div class="wrap">

    <div class="postman-notifier--panel">

        <h1 class="postman-notifier--title">PostmanCanFail Settings</h1>

        <div class="js-pcf-notifications"></div>

        <form method="post" action="options.php" class="js-pcf-form">
            <?php settings_fields('postmanCanFail'); ?>
            <?php do_settings_sections('postmanCanFail'); ?>
            <table class="form-table postman-notifier--table">
                <tr valign="top">
                    <th scope="row">Enable error notifications</th>
                    <td>
                        <label>
                            <input type="radio"
                                   name="pcf_enable_type"
                                   value="0"
                                <?php checked(get_option('pcf_enable_type'), 0) ?>
                            >
                            Disabled
                        </label>
                        <label>
                            <input type="radio" name="pcf_enable_type"
                                   value="1" <?php checked(get_option('pcf_enable_type'),
                                1) ?>>
                            Notice via mail()
                        </label>
                        <label>
                            <input type="radio" name="pcf_enable_type"
                                   value="2" <?php checked(get_option('pcf_enable_type'),
                                2) ?>>
                            Notice via Rollbar
                        </label>
                        <label>
                            <input type="radio" name="pcf_enable_type"
                                   value="99" <?php checked(get_option('pcf_enable_type'),
                                99) ?>>
                            Notice via both Rollbar and mail()
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="notification_email">Recipient Email Address</label></th>
                    <td>
                        <input type="text" name="pcf_notification_email"
                               value="<?= get_option('pcf_notification_email') ?>"
                               id="notification_email">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="rollbar_token">Rollbar Token</label></th>
                    <td>
                        <input type="text" name="pcf_rollbar_token" value="<?= get_option('pcf_rollbar_token') ?>"
                               id="rollbar_token">
                    </td>
                </tr>
            </table>

            <div class="postman-notifier--button">
                <a href="#" class="button button-primary button-success js-test-pcf">Test</a>
                <input type="submit" name="submit" id="submit" class="button button-primary"
                       value="<?= __('Save Changes') ?>">
            </div>

        </form>
    </div>
</div>

<script>
    (function ($) {
        $('.js-test-pcf').click(function (e) {
            e.preventDefault();

            var $button = $(this);

            if ($button.is("[disabled]")) {
                return;
            }

            function notify(message, type) {
                type = type || 'success';
                $('.js-pcf-notification').slideUp();
                $('.js-pcf-notifications').append('<div class="js-pcf-notification pcf-notification pcf-notification-' + type + '">' + message + '</div>');
            }

            $button.attr('disabled', 'disabled');

            $.post(ajaxurl, $('.js-pcf-form').serialize() + '&action=test_pcf', function (r) {
                $button.removeAttr('disabled');
                if (!r.success) {
                    notify(r.data || 'Generic error...', 'error');
                } else {
                    notify(r.data);
                }
            });

        });
    })(jQuery);

</script>

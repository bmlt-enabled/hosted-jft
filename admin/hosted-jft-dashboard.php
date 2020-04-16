<?php
 
/* Admin dashboard of the Hosted JFT plugin */

function hosted_jft_plugin_settings()
{
    //register our settings
    register_setting('hosted-jft-plugin-settings-group', 'jft_timezone');
    register_setting('hosted-jft-plugin-settings-group', 'jft_custom_field');
    register_setting('hosted-jft-plugin-settings-group', 'jft_page_url');
    register_setting('hosted-jft-plugin-settings-group', 'jft_more_text');
}

add_action('admin_init', 'hosted_jft_plugin_settings');

function hosted_jft_plugin_page()
{
    ?>
    <div class="wrap">
        <h1>Hosted JFT Plugin Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('hosted-jft-plugin-settings-group');
            do_settings_sections('hosted-jft-plugin-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Custom Field Name</th>
                    <td>
                        <input type="text" id="jft_custom_field" name="jft_custom_field" value="<?php echo get_option('jft_custom_field'); ?>">
                        <p class="description">Choose the Custom Field Name for the JFT Posts.<br> insert [hosted_jft] shortcode on your page or post.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Time Zone</th>
                    <td>
                        <select id="jft_timezone" name="jft_timezone">
                            <option value=""></option>
                            <?php
                            $timezones_array = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                            $tz_setting = esc_attr(get_option('jft_timezone'));
                            foreach ($timezones_array as $tzItem) {
                                if ($tzItem == $tz_setting) { ?>
                                    <option selected="selected" value="<?php echo $tzItem; ?>"><?php echo $tzItem; ?></option>
                                <?php } else { ?>
                                    <option value="<?php echo $tzItem; ?>"><?php echo $tzItem; ?></option>
                                <?php }
                            } ?>
                        </select>
                        <p class="description">This should be set to your local time zone.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">JFT Page URL</th>
                    <td>
                        <input type="text" id="jft_page_url" name="jft_page_url" value="<?php echo get_option('jft_page_url'); ?>">
                        <p class="description">The URL to the page that displays the JFT, this is used for the widget.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">More Text</th>
                    <td>
                        <input type="text" id="jft_more_text" name="jft_more_text" value="<?php echo get_option('jft_more_text'); ?>">
                        <p class="description">The txt to be displayed to view full JFT, this is used for the widget.</p>
                    </td>
                </tr>
            </table>
            <?php  submit_button(); ?>
        </form>
   </div>
<?php }

// End Hosted JFT Settings Page Function
?>

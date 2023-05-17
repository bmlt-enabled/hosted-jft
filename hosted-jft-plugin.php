<?php
/*
Plugin Name: Hosted JFT
Plugin URI: https://wordpress.org/plugins/hosted-jft/
Description: Hosted JFT is a plugin that allows an NA Community to host their own translated version of the JFT.
Version: 1.0.3
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

require_once('admin/hosted-jft-dashboard.php');

// create admin menu settings page
add_action('admin_menu', 'hosted_jft_options_menu');
function hosted_jft_options_menu()
{
    add_options_page('Hosted JFT Plugin Settings', 'Hosted JFT', 'manage_options', 'hosted-jft-plugin', 'hosted_jft_plugin_page');
}

// add settings link to plugins page
function hosted_jft_add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=hosted-jft-plugin">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'hosted_jft_add_settings_link');

function hosted_jft_func($atts = [])
{
    $args = shortcode_atts(
        array(
            'timezone'    =>  ''
        ),
        $atts
    );

    // Set custom_field and timezone - shortcode parameter overrides admin setting
    $jft_custom_field = get_option('jft_custom_field');
    $jft_timezone = (!empty($args['timezone']) ? sanitize_text_field(strtolower($args['timezone'])) : get_option('jft_timezone'));

    date_default_timezone_set($jft_timezone);
    $today = date("m-d");
    $jft_post = get_posts(array(
        'numberposts'   => -1,
        'post_type'     => 'post',
        'meta_key'      => $jft_custom_field,
        'meta_value'    => $today
    ));
    $get_title =  get_post_field('post_title', $jft_post[0]->ID ?? '');
    $todays_jft_content =  get_post_field('post_content', $jft_post[0]->ID ?? '');
    if ($todays_jft_content && $todays_jft_content != '[hosted_jft]') {
        $todays_jft_title = '<div class="spo-title"><h2 class="spo-title">' . $get_title . '</h2></div>';
        $ret = $todays_jft_title . $todays_jft_content;
    } else {
        $ret = "No JFT Found for today, there could be a problem with settings or missing post for today.";
    }
    return $ret;
}

function hosted_jft_widget_func($atts = [])
{
    $args = shortcode_atts(
        array(
            'timezone'    =>  ''
        ),
        $atts
    );

    // Set custom_field and timezone - shortcode parameter overrides admin setting
    $jft_custom_field = get_option('jft_custom_field');
    $jft_timezone = (!empty($args['timezone']) ? sanitize_text_field(strtolower($args['timezone'])) : get_option('jft_timezone'));


    date_default_timezone_set($jft_timezone);
    $today = date("m-d");
    $jft_post = get_posts(array(
        'numberposts'   => -1,
        'post_type'     => 'post',
        'meta_key'      => $jft_custom_field,
        'meta_value'    => $today
    ));
    $todays_jft_array = [];
    $todays_jft_array['excerpt'] = get_post_field('post_excerpt', $jft_post[0]->ID);
    $todays_jft_array['url'] = get_post_field('guid', $jft_post[0]->ID);
    $todays_jft_array['title'] = get_post_field('post_title', $jft_post[0]->ID);

    return $todays_jft_array;
}

add_action('pre_get_posts', function ($query) {
    global $wp;
    $jft_custom_field = get_option('jft_custom_field');
    $jft_timezone = get_option('jft_timezone');

    if (!is_admin() && $query->is_main_query()) {
        if ($wp->request == 'get-jft') {
            if ($_GET['tz']) {
                date_default_timezone_set($_GET['tz']);
            } else {
                date_default_timezone_set($jft_timezone);
            }
            $today = date("m-d");
            $jft_post = get_posts(array(
                'numberposts'   => -1,
                'post_type'     => 'post',
                'meta_key'      => $jft_custom_field,
                'meta_value'    => $today
            ));
            $todays_jft_array =  array();
            $todays_jft_array[] = array(
                'title'         => get_post_field('post_title', $jft_post[0]->ID),
                'content'       => get_post_field('post_content', $jft_post[0]->ID),
                'excerpt'       => get_post_field('post_excerpt', $jft_post[0]->ID),
                'url'           => get_post_field('guid', $jft_post[0]->ID),
            );
            header('Content-Type: application/json');
            echo json_encode($todays_jft_array);
            exit;
        }
    }
});

// create [hosted_jft] shortcode
add_shortcode('hosted_jft', 'hosted_jft_func');

/** START Hosted JFT Widget **/
// register Hosted_JFT_Widget
add_action('widgets_init', function () {
    register_widget('Hosted_JFT_Widget');
});

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Hosted_JFT_Widget extends WP_Widget
{
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps
    /**
     * Sets up a new Hosted JFT widget instance.
     *
     */
    public function __construct()
    {
        $widget_ops = array(
            'classname' => 'Hosted_JFT_widget',
            'description' => 'Displays the Just For Today',
        );
        parent::__construct('Hosted_JFT_widget', 'Hosted JFT', $widget_ops);
    }

    /**
    * Outputs the content for the current Hosted JFT widget instance.
    *
    *
    * @hosted_jft_widget_func gets and parses the jft
    *
    * @param array $args     Display arguments including 'before_title', 'after_title',
    *                        'before_widget', and 'after_widget'.
    * @param array $instance Settings for the current Area Meetings Dropdown widget instance.
    */

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        if (! empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        $jft_page_url = get_option('jft_page_url');
        $jft_more_text = get_option('jft_more_text');
        $get_jft = hosted_jft_widget_func($atts);
        echo '<div class="jft-widget-title">' . $get_jft['title'] . '</div><br/>';
        echo '<div class="jft-widget-excerpt">' . $get_jft['excerpt'] . '</div>';
        echo '&nbsp;&nbsp;<a href="'.$jft_page_url.'" class="jft-widget-link">'.$jft_more_text.'</a><br/><br/>';
        echo $args['after_widget'];
    }

    /**
     * Outputs the settings form for the Hosted JFT widget.
     * @param $instance
     */
    public function form($instance)
    {
        $title = ! empty($instance['title']) ? $instance['title'] : esc_html__('Title', 'text_domain');
        ?>
        <p>
        <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
        <?php esc_attr_e('Title:', 'text_domain'); ?>
        </label>

        <input
            class="widefat"
            id="<?php echo esc_attr($this->get_field_id('title')); ?>"
            name="<?php echo esc_attr($this->get_field_name('title')); ?>"
            type="text"
            value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
    * Handles updating settings for the current Hosted JFT widget instance.
    *
    * @param array $new_instance New settings for this instance as input by the user via
    *                            WP_Widget::form().
    * @param array $old_instance Old settings for this instance.
    * @return array Updated settings to save.
    */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = ( ! empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}
/** END Hosted JFT Widget **/
?>

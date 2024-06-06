<?php

/**
 * Plugin Name: Hosted JFT
 * Plugin URI: https://wordpress.org/plugins/hosted-jft/
 * Author: bmlt-enabled
 * Description: Hosted JFT is a plugin that allows an NA Community to host their own translated version of the JFT.
 * Version: 1.1.0
 * Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
 * Disallow direct access to the plugin file
 */

namespace HostedJft;

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die('Sorry, but you cannot access this page directly.');
}

spl_autoload_register(function (string $class) {
    if (strpos($class, 'HostedJft\\') === 0) {
        $class = str_replace('HostedJft\\', '', $class);
        require __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
    }
});

class HostedJftPlugin
{
    private static $instance = null;

    public function __construct()
    {
        add_action('init', [$this, 'pluginSetup']);
    }

    public function pluginSetup()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'optionsMenu']);
        } else {
            add_shortcode('hosted_jft', [$this, 'reading']);
            add_action('widgets_init', function () {
                register_widget(Widget::class);
            });
            add_action('pre_get_posts', [$this, 'customPostContent']);
        }
    }

    public function optionsMenu()
    {
        $dashboard = new Dashboard();
        $dashboard->createMenu(plugin_basename(__FILE__));
    }

    public function reading($atts)
    {
        $reading = new Reading();
        return $reading->renderReading($atts);
    }

    public function customPostContent($query)
    {
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
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

HostedJftPlugin::getInstance();

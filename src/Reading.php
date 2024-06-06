<?php

namespace HostedJft;

class Reading
{
    public function renderReading($atts = []): string
    {
        $args = shortcode_atts(['timezone' => ''], $atts);
        $timezone = $this->sanitizeTimezone($args);

        // Set custom_field - shortcode parameter overrides admin setting
        $jftCustomField = get_option('jft_custom_field');
        $jftPostId = $this->getTodaysJftPostId($jftCustomField, $timezone);

        if ($jftPostId) {
            $getTitle =  get_post_field('post_title', $jftPostId);
            $todaysJftContent =  get_post_field('post_content', $jftPostId);
            if ($todaysJftContent && $todaysJftContent != '[hosted_jft]') {
                $todaysJftTitle = '<div class="spo-title"><h2 class="spo-title">' . $getTitle . '</h2></div>';
                $ret = $todaysJftTitle . $todaysJftContent;
            } else {
                $ret = "No JFT Found for today, there could be a problem with settings or missing post for today.";
            }
        } else {
            $ret = "No JFT Found for today, there could be a problem with settings or missing post for today.";
        }

        return $ret;
    }

    protected function sanitizeTimezone(array $args): string
    {
        return !empty($args['timezone']) ? sanitize_text_field(strtolower($args['timezone'])) : get_option('jft_timezone');
    }

    function getTodaysJftPostId($custom_field, $timezone = '')
    {
        $jft_timezone = (!empty($timezone) ? sanitize_text_field(strtolower($timezone)) : get_option('jft_timezone'));
        date_default_timezone_set($jft_timezone ?? 'Europe/Rome');
        $today = date("m-d");

        $jft_post = get_posts([
            'numberposts'   => -1,
            'post_type'     => 'post',
            'meta_key'      => $custom_field,
            'meta_value'    => $today
        ]);

        return !empty($jft_post) ? $jft_post[0]->ID : null;
    }

    function widgetFunc($atts = [])
    {
        $args = shortcode_atts(['timezone'  =>  ''], $atts);

        // Set custom_field - shortcode parameter overrides admin setting
        $jftCustomField = get_option('jft_custom_field');
        $jftPostId = get_todays_jftPostId($jftCustomField, $args['timezone']);

        if ($jftPostId) {
            $todaysJft = [];
            $todaysJft['excerpt'] = get_post_field('post_excerpt', $jftPostId);
            $todaysJft['url'] = get_post_field('guid', $jftPostId);
            $todaysJft['title'] = get_post_field('post_title', $jftPostId);
        } else {
            $todaysJft = [
                'excerpt' => '',
                'url' => '',
                'title' => ''
            ];
        }

        return $todaysJft;
    }
}

<?php
/**
 * Shortcode functionality for Code Display Box
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register shortcode
 */
function code_display_box_register_shortcode() {
    add_shortcode('code_display_box', 'code_display_box_shortcode');
}
add_action('init', 'code_display_box_register_shortcode');

/**
 * Shortcode callback
 */
function code_display_box_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'id' => 0,
        ),
        $atts,
        'code_display_box'
    );
    
    $id = intval($atts['id']);
    
    if ($id <= 0) {
        return '<p>Invalid code display box ID.</p>';
    }
    
    // Get code display box data
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    $code_box = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    
    if (!$code_box) {
        return '<p>Code display box not found.</p>';
    }
    
    // Parse code data
    $code_data = json_decode($code_box->code_data, true);
    
    if (empty($code_data)) {
        return '<p>No code data found.</p>';
    }
    
    // Build output
    $output = '<div class="code-display-box" id="code-display-box-' . esc_attr($id) . '">';
    
    // Tabs
    $output .= '<ul class="code-display-tabs">';
    $first = true;
    foreach ($code_data as $language => $code) {
        $active_class = $first ? ' active' : '';
        $output .= '<li class="code-tab' . $active_class . '" data-language="' . esc_attr(sanitize_title($language)) . '">' . esc_html($language) . '</li>';
        $first = false;
    }
    $output .= '</ul>';
    
    // Content
    $output .= '<div class="code-display-content">';
    $first = true;
    foreach ($code_data as $language => $code) {
        $active_class = $first ? ' active' : '';
        $lang_class = 'language-' . esc_attr(sanitize_title($language));
        $output .= '<pre class="code-block' . $active_class . '" data-language="' . esc_attr(sanitize_title($language)) . '"><code class="' . $lang_class . '">' . esc_html($code) . '</code></pre>';
        $first = false;
    }
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}

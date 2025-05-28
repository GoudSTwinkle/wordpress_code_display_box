<?php
/**
 * Plugin Name: Code Display Box
 * Description: Creates tabbed code display boxes for different programming languages
 * Version: 1.0
 * Author: Lovable
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CODE_DISPLAY_BOX_PATH', plugin_dir_path(__FILE__));
define('CODE_DISPLAY_BOX_URL', plugin_dir_url(__FILE__));
define('CODE_DISPLAY_BOX_VERSION', '1.0.0');

// Include required files
require_once CODE_DISPLAY_BOX_PATH . 'includes/admin/admin-page.php';
require_once CODE_DISPLAY_BOX_PATH . 'includes/shortcodes.php';
require_once CODE_DISPLAY_BOX_PATH . 'includes/enqueue-scripts.php';

// Register activation hook
register_activation_hook(__FILE__, 'code_display_box_activate');

/**
 * Plugin activation function
 */
function code_display_box_activate() {
    // Create custom table for storing code display boxes
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'code_display_boxes';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        category_id mediumint(9) NOT NULL,
        post_id mediumint(9) NOT NULL,
        languages_count mediumint(9) NOT NULL,
        code_data longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Enqueue scripts and styles
 */
function code_display_box_enqueue_scripts() {
    // Only load on frontend when shortcode is used
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'code_display_box')) {
        wp_enqueue_style('code-display-box', CODE_DISPLAY_BOX_URL . 'assets/css/front-end.css', array(), CODE_DISPLAY_BOX_VERSION);
        wp_enqueue_script('code-display-box', CODE_DISPLAY_BOX_URL . 'assets/js/front-end.js', array('jquery'), CODE_DISPLAY_BOX_VERSION, true);

    }
}
add_action('wp_enqueue_scripts', 'code_display_box_enqueue_scripts');

/**
 * Enqueue admin scripts and styles
 */
function code_display_box_admin_enqueue_scripts($hook) {
    if ('toplevel_page_code-display-box' !== $hook) {
        return;
    }
    
    wp_enqueue_style('code-display-box-admin', CODE_DISPLAY_BOX_URL . 'assets/css/admin.css', array(), CODE_DISPLAY_BOX_VERSION);
    wp_enqueue_script('code-display-box-admin', CODE_DISPLAY_BOX_URL . 'assets/js/admin.js', array('jquery'), CODE_DISPLAY_BOX_VERSION, true);
    
    wp_localize_script('code-display-box-admin', 'codeDisplayBox', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('code_display_box_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'code_display_box_admin_enqueue_scripts');

/**
 * Ajax handler for getting posts by category
 */
function code_display_box_get_posts_by_category() {
    check_ajax_referer('code_display_box_nonce', 'nonce');
    
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    $posts = get_posts(array(
        'category' => $category_id,
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_type' => 'post',
        'post_status' => 'publish'
    ));
    
    $response = array();
    
    foreach ($posts as $post) {
        $response[] = array(
            'id' => $post->ID,
            'title' => $post->post_title
        );
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_code_display_box_get_posts_by_category', 'code_display_box_get_posts_by_category');

/**
 * Ajax handler for saving code display box
 */
function code_display_box_save() {
    check_ajax_referer('code_display_box_nonce', 'nonce');
    
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $languages_count = isset($_POST['languages_count']) ? intval($_POST['languages_count']) : 0;
    $code_data = isset($_POST['code_data']) ? wp_unslash($_POST['code_data']) : array(); // FIXED
    
    // Validate data
    if (empty($title) || $category_id <= 0 || $post_id <= 0 || $languages_count <= 0 || empty($code_data)) {
        wp_send_json_error(array('message' => 'Invalid data provided.'));
        return;
    }
    
    // Sanitize code data
    $sanitized_code_data = array();
    foreach ($code_data as $language => $code) {
        $sanitized_code_data[sanitize_text_field($language)] = wp_kses_post($code);
    }
    
    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'title' => $title,
            'category_id' => $category_id,
            'post_id' => $post_id,
            'languages_count' => $languages_count,
            'code_data' => json_encode($sanitized_code_data),
        ),
        array('%s', '%d', '%d', '%d', '%s')
    );
    
    if ($result) {
        $id = $wpdb->insert_id;
        wp_send_json_success(array(
            'message' => 'Code display box created successfully.',
            'id' => $id,
            'shortcode' => '[code_display_box id="' . $id . '"]'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to save code display box.'));
    }
}
add_action('wp_ajax_code_display_box_save', 'code_display_box_save');

/**
 * Ajax handler for getting code display box data
 */
function code_display_box_get() {
    check_ajax_referer('code_display_box_nonce', 'nonce');
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        wp_send_json_error(array('message' => 'Invalid ID provided.'));
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    
    $code_box = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    
    if (!$code_box) {
        wp_send_json_error(array('message' => 'Code display box not found.'));
        return;
    }
    
    // Parse code data
    $code_box['code_data'] = json_decode($code_box['code_data'], true);
    
    wp_send_json_success($code_box);
}
add_action('wp_ajax_code_display_box_get', 'code_display_box_get');

/**
 * Ajax handler for updating code display box
 */
function code_display_box_update() {
    check_ajax_referer('code_display_box_nonce', 'nonce');
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $languages_count = isset($_POST['languages_count']) ? intval($_POST['languages_count']) : 0;
    $code_data = isset($_POST['code_data']) ? wp_unslash($_POST['code_data']) : array(); // FIXED
    
    // Validate data
    if ($id <= 0 || empty($title) || $category_id <= 0 || $post_id <= 0 || $languages_count <= 0 || empty($code_data)) {
        wp_send_json_error(array('message' => 'Invalid data provided.'));
        return;
    }
    
    // Sanitize code data
    $sanitized_code_data = array();
    foreach ($code_data as $language => $code) {
        $sanitized_code_data[sanitize_text_field($language)] = wp_kses_post($code);
    }
    
    // Update in database
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    
    $result = $wpdb->update(
        $table_name,
        array(
            'title' => $title,
            'category_id' => $category_id,
            'post_id' => $post_id,
            'languages_count' => $languages_count,
            'code_data' => json_encode($sanitized_code_data),
        ),
        array('id' => $id),
        array('%s', '%d', '%d', '%d', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success(array(
            'message' => 'Code display box updated successfully.',
            'id' => $id
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to update code display box.'));
    }
}
add_action('wp_ajax_code_display_box_update', 'code_display_box_update');

/**
 * Ajax handler for deleting code display box
 */
function code_display_box_delete() {
    check_ajax_referer('code_display_box_nonce', 'nonce');
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        wp_send_json_error(array('message' => 'Invalid ID provided.'));
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    
    $result = $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );
    
    if ($result) {
        wp_send_json_success(array('message' => 'Code display box deleted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete code display box.'));
    }
}
add_action('wp_ajax_code_display_box_delete', 'code_display_box_delete');
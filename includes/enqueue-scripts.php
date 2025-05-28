<?php
/**
 * Enqueue scripts and styles for Code Display Box
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue front-end scripts and styles
 */
function code_display_box_enqueue_front_end() {
    wp_enqueue_style('code-display-box-front-end', CODE_DISPLAY_BOX_URL . 'assets/css/front-end.css', array(), CODE_DISPLAY_BOX_VERSION);
    wp_enqueue_script('code-display-box-front-end', CODE_DISPLAY_BOX_URL . 'assets/js/front-end.js', array('jquery'), CODE_DISPLAY_BOX_VERSION, true);

    // Prism.js CSS & core JS
    wp_enqueue_style('prismjs', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css', array(), '1.29.0');
    wp_enqueue_script('prismjs', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js', array(), '1.29.0', true);

    // Prism Autoloader plugin (loads languages as needed)
    wp_enqueue_script('prismjs-autoloader', 'https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js', array('prismjs'), '1.29.0', true);
}
add_action('wp_enqueue_scripts', 'code_display_box_enqueue_front_end');

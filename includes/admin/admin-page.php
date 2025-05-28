<?php
/**
 * Admin page for Code Display Box
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu
 */
function code_display_box_add_admin_menu() {
    add_menu_page(
        'Code Display Box',
        'Code Display Box',
        'manage_options',
        'code-display-box',
        'code_display_box_admin_page',
        'dashicons-editor-code',
        30
    );
}
add_action('admin_menu', 'code_display_box_add_admin_menu');

/**
 * Admin page content
 */
function code_display_box_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get all categories
    $categories = get_categories(array(
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => false
    ));
    
    // Get existing code display boxes
    global $wpdb;
    $table_name = $wpdb->prefix . 'code_display_boxes';
    $code_boxes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
    ?>
    <div class="wrap code-display-box-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="code-display-box-tabs">
            <ul class="code-display-box-tab-nav">
                <li class="active"><a href="#create-new">Create New</a></li>
                <li><a href="#manage-existing">Manage Existing</a></li>
                <li class="edit-tab hidden"><a href="#edit-box">Edit Box</a></li>
            </ul>
            
            <div class="code-display-box-tab-content">
                <div id="create-new" class="code-display-box-tab-pane active">
                    <h2>Create New Code Display Box</h2>
                    <form id="code-display-box-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="code-display-box-title">Title</label></th>
                                <td>
                                    <input type="text" id="code-display-box-title" name="code-display-box-title" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="code-display-box-category">Category</label></th>
                                <td>
                                    <select id="code-display-box-category" name="code-display-box-category" required>
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="code-display-box-post">Post</label></th>
                                <td>
                                    <select id="code-display-box-post" name="code-display-box-post" required disabled>
                                        <option value="">Select a post</option>
                                    </select>
                                    <p class="description">Select a category first</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="code-display-box-languages-count">Number of Coding Languages</label></th>
                                <td>
                                    <input type="number" id="code-display-box-languages-count" name="code-display-box-languages-count" min="1" max="10" value="1" required>
                                    <p class="description">How many language tabs do you want to create?</p>
                                </td>
                            </tr>
                        </table>
                        
                        <div id="code-display-box-languages-container">
                            <div class="code-language-item" data-index="0">
                                <h3>Language Tab 1</h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><label for="code-language-name-0">Language Name</label></th>
                                        <td>
                                            <input type="text" id="code-language-name-0" name="code-language-name-0" class="regular-text" placeholder="e.g. JavaScript, PHP, Python" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="code-content-0">Code Content</label></th>
                                        <td>
                                            <textarea id="code-content-0" name="code-content-0" rows="10" class="large-text code" placeholder="Enter your code here..." required></textarea>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Create Code Display Box</button>
                        </p>
                    </form>
                    
                    <div id="code-display-box-shortcode-result" class="hidden">
                        <h3>Code Display Box Created!</h3>
                        <p>Use this shortcode to display your code box:</p>
                        <div class="shortcode-container">
                            <code id="generated-shortcode"></code>
                            <button class="button copy-shortcode">Copy Shortcode</button>
                        </div>
                    </div>
                </div>
                
                <div id="manage-existing" class="code-display-box-tab-pane">
                    <h2>Manage Existing Code Display Boxes</h2>
                    
                    <?php if (empty($code_boxes)) : ?>
                        <p>No code display boxes have been created yet.</p>
                    <?php else : ?>
                        <div class="code-display-box-filters">
                            <select id="filter-by-category">
                                <option value="">Filter by Category</option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filter-by-post" disabled>
                                <option value="">Filter by Post</option>
                            </select>
                        </div>
                        
                        <table class="wp-list-table widefat fixed striped code-display-boxes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Post</th>
                                    <th>Languages</th>
                                    <th>Shortcode</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($code_boxes as $box) : 
                                    $category_name = get_cat_name($box->category_id);
                                    $post_title = get_the_title($box->post_id);
                                    $shortcode = '[code_display_box id="' . $box->id . '"]';
                                    $code_data = json_decode($box->code_data, true);
                                    $languages = implode(', ', array_keys($code_data));
                                ?>
                                <tr data-category="<?php echo esc_attr($box->category_id); ?>" data-post="<?php echo esc_attr($box->post_id); ?>" data-id="<?php echo esc_attr($box->id); ?>">
                                    <td><?php echo esc_html($box->id); ?></td>
                                    <td><?php echo esc_html($box->title); ?></td>
                                    <td><?php echo esc_html($category_name); ?></td>
                                    <td><?php echo esc_html($post_title); ?></td>
                                    <td><?php echo esc_html($languages); ?></td>
                                    <td><code><?php echo esc_html($shortcode); ?></code> <button class="button button-small copy-shortcode" data-shortcode="<?php echo esc_attr($shortcode); ?>">Copy</button></td>
                                    <td>
                                        <button class="button button-small edit-code-box" data-id="<?php echo esc_attr($box->id); ?>">Edit</button>
                                        <button class="button button-small delete-code-box" data-id="<?php echo esc_attr($box->id); ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div id="edit-box" class="code-display-box-tab-pane">
                    <h2>Edit Code Display Box</h2>
                    <form id="code-display-box-edit-form">
                        <input type="hidden" id="edit-box-id" name="edit-box-id">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit-code-display-box-title">Title</label></th>
                                <td>
                                    <input type="text" id="edit-code-display-box-title" name="edit-code-display-box-title" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-code-display-box-category">Category</label></th>
                                <td>
                                    <select id="edit-code-display-box-category" name="edit-code-display-box-category" required>
                                        <option value="">Select a category</option>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-code-display-box-post">Post</label></th>
                                <td>
                                    <select id="edit-code-display-box-post" name="edit-code-display-box-post" required>
                                        <option value="">Select a post</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit-code-display-box-languages-count">Number of Coding Languages</label></th>
                                <td>
                                    <input type="number" id="edit-code-display-box-languages-count" name="edit-code-display-box-languages-count" min="1" max="10" value="1" required>
                                    <p class="description">How many language tabs do you want to create?</p>
                                </td>
                            </tr>
                        </table>
                        
                        <div id="edit-code-display-box-languages-container">
                            <!-- Language tabs will be dynamically added here -->
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Update Code Display Box</button>
                            <button type="button" class="button cancel-edit">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}
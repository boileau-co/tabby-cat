<?php
/**
 * Plugin Name: Tabby Cat
 * Description: A two-tier master-detail display component with customizable content type and categories.
 * Version: 1.4.0
 * Author: Cozy Cat
 * Text Domain: tabby-cat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('TABBY_CAT_VERSION', '1.4.0');
define('TABBY_CAT_PATH', plugin_dir_path(__FILE__));
define('TABBY_CAT_URL', plugin_dir_url(__FILE__));

/**
 * Initialize Plugin Update Checker for GitHub updates
 */
require TABBY_CAT_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$tabby_cat_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/Boileau-co/tabby-cat/',
    __FILE__,
    'tabby-cat'
);

// Set the branch to check for updates (defaults to 'main')
$tabby_cat_update_checker->setBranch('main');

/**
 * Get plugin settings with defaults
 */
function tabby_cat_get_settings() {
    $defaults = array(
        'cpt_singular'      => 'Tabby Cat Item',
        'cpt_plural'        => 'Tabby Cat Items',
        'cpt_menu_name'     => 'Tabby Cat',
        'cpt_icon'          => 'dashicons-category',
        'tax_singular'      => 'Category',
        'tax_plural'        => 'Categories',
        'field_title'       => 'Title',
        'field_description' => 'Description',
        'field_link'        => 'Link',
        'field_visual'      => 'Visual',
    );
    
    $settings = get_option('tabby_cat_settings', array());
    
    return wp_parse_args($settings, $defaults);
}

/**
 * Register Custom Post Type
 */
function tabby_cat_register_cpt() {
    $settings = tabby_cat_get_settings();
    
    $labels = array(
        'name'                  => $settings['cpt_plural'],
        'singular_name'         => $settings['cpt_singular'],
        'menu_name'             => $settings['cpt_menu_name'],
        'name_admin_bar'        => $settings['cpt_singular'],
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New ' . $settings['cpt_singular'],
        'new_item'              => 'New ' . $settings['cpt_singular'],
        'edit_item'             => 'Edit ' . $settings['cpt_singular'],
        'view_item'             => 'View ' . $settings['cpt_singular'],
        'all_items'             => 'All ' . $settings['cpt_plural'],
        'search_items'          => 'Search ' . $settings['cpt_plural'],
        'not_found'             => 'No ' . strtolower($settings['cpt_plural']) . ' found.',
        'not_found_in_trash'    => 'No ' . strtolower($settings['cpt_plural']) . ' found in Trash.',
        'archives'              => $settings['cpt_singular'] . ' Archives',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25,
        'menu_icon'          => $settings['cpt_icon'],
        'supports'           => array('title'),
        'show_in_rest'       => true,
    );

    register_post_type('tabby_cat_item', $args);
}
add_action('init', 'tabby_cat_register_cpt');

/**
 * Register Taxonomy
 */
function tabby_cat_register_taxonomy() {
    $settings = tabby_cat_get_settings();
    
    $labels = array(
        'name'                       => $settings['tax_plural'],
        'singular_name'              => $settings['tax_singular'],
        'menu_name'                  => $settings['tax_plural'],
        'all_items'                  => 'All ' . $settings['tax_plural'],
        'parent_item'                => 'Parent ' . $settings['tax_singular'],
        'parent_item_colon'          => 'Parent ' . $settings['tax_singular'] . ':',
        'new_item_name'              => 'New ' . $settings['tax_singular'] . ' Name',
        'add_new_item'               => 'Add New ' . $settings['tax_singular'],
        'edit_item'                  => 'Edit ' . $settings['tax_singular'],
        'update_item'                => 'Update ' . $settings['tax_singular'],
        'view_item'                  => 'View ' . $settings['tax_singular'],
        'search_items'               => 'Search ' . $settings['tax_plural'],
        'not_found'                  => 'Not Found',
        'no_terms'                   => 'No ' . strtolower($settings['tax_plural']),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud'     => false,
        'show_in_rest'      => true,
        'rewrite'           => false,
    );

    register_taxonomy('tabby_cat_category', array('tabby_cat_item'), $args);
}
add_action('init', 'tabby_cat_register_taxonomy');

/**
 * Category Tag Meta (for shortcode filtering)
 */
function tabby_cat_get_all_tags() {
    $all_tags = array();
    $terms = get_terms(array(
        'taxonomy'   => 'tabby_cat_category',
        'hide_empty' => false,
    ));
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $tags_string = get_term_meta($term->term_id, 'tabby_cat_tags', true);
            if (!empty($tags_string)) {
                $tags = array_map('trim', explode(',', $tags_string));
                foreach ($tags as $tag) {
                    if (!empty($tag)) {
                        $all_tags[] = $tag;
                    }
                }
            }
        }
    }
    $all_tags = array_unique($all_tags);
    sort($all_tags);
    return $all_tags;
}

function tabby_cat_render_tag_checkboxes($selected_tags = array()) {
    $all_tags = tabby_cat_get_all_tags();
    $selected_lower = array_map('strtolower', $selected_tags);

    if (!empty($all_tags)) : ?>
        <fieldset style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; margin-bottom: 8px;">
            <?php foreach ($all_tags as $tag) : ?>
                <label style="font-weight: normal;">
                    <input type="checkbox" name="tabby_cat_tags_checked[]" value="<?php echo esc_attr($tag); ?>"
                        <?php checked(in_array(strtolower($tag), $selected_lower, true)); ?>>
                    <?php echo esc_html($tag); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <label style="font-weight: normal; display: flex; align-items: center; gap: 6px;">
        <span><?php echo empty($all_tags) ? 'Tag name:' : 'Add new:'; ?></span>
        <input type="text" name="tabby_cat_tags_new" value="" style="width: 200px;">
    </label>
    <p class="description">Select tags for shortcode filtering, or type a new one.</p>
    <?php
}

function tabby_cat_category_add_tag_field() {
    ?>
    <div class="form-field">
        <label>Tags</label>
        <?php tabby_cat_render_tag_checkboxes(); ?>
    </div>
    <?php
}
add_action('tabby_cat_category_add_form_fields', 'tabby_cat_category_add_tag_field');

function tabby_cat_category_edit_tag_field($term) {
    $tags_string = get_term_meta($term->term_id, 'tabby_cat_tags', true);
    $selected = !empty($tags_string) ? array_map('trim', explode(',', $tags_string)) : array();
    ?>
    <tr class="form-field">
        <th scope="row"><label>Tags</label></th>
        <td>
            <?php tabby_cat_render_tag_checkboxes($selected); ?>
        </td>
    </tr>
    <?php
}
add_action('tabby_cat_category_edit_form_fields', 'tabby_cat_category_edit_tag_field');

function tabby_cat_save_tag_meta($term_id) {
    $tags = array();

    if (!empty($_POST['tabby_cat_tags_checked'])) {
        $tags = array_map('sanitize_text_field', $_POST['tabby_cat_tags_checked']);
    }

    if (!empty($_POST['tabby_cat_tags_new'])) {
        $new_tags = array_map('trim', explode(',', sanitize_text_field($_POST['tabby_cat_tags_new'])));
        foreach ($new_tags as $new_tag) {
            if (!empty($new_tag) && !in_array(strtolower($new_tag), array_map('strtolower', $tags), true)) {
                $tags[] = $new_tag;
            }
        }
    }

    update_term_meta($term_id, 'tabby_cat_tags', implode(', ', $tags));
}
add_action('created_tabby_cat_category', 'tabby_cat_save_tag_meta');
add_action('edited_tabby_cat_category', 'tabby_cat_save_tag_meta');

/**
 * Add Settings Page
 */
function tabby_cat_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=tabby_cat_item',
        'Tabby Cat Settings',
        'Settings',
        'manage_options',
        'tabby-cat-settings',
        'tabby_cat_render_settings_page'
    );
}
add_action('admin_menu', 'tabby_cat_add_settings_page');

/**
 * Enqueue admin scripts and styles for settings page
 */
function tabby_cat_admin_scripts($hook) {
    // Only load on our settings page
    if ($hook !== 'tabby_cat_item_page_tabby-cat-settings') {
        return;
    }
    
    // WordPress color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Initialize color pickers
    wp_add_inline_script('wp-color-picker', '
        jQuery(document).ready(function($) {
            $(".tabby-cat-color-picker").wpColorPicker();
        });
    ');
}
add_action('admin_enqueue_scripts', 'tabby_cat_admin_scripts');

/**
 * Register Settings
 */
function tabby_cat_register_settings() {
    register_setting('tabby_cat_settings_group', 'tabby_cat_settings', 'tabby_cat_sanitize_settings');
    
    // Content Type Section
    add_settings_section(
        'tabby_cat_cpt_section',
        'Content Type Labels',
        function() {
            echo '<p>Customize how the content type appears in the WordPress admin.</p>';
        },
        'tabby-cat-settings'
    );
    
    add_settings_field(
        'cpt_singular',
        'Singular Name',
        'tabby_cat_text_field',
        'tabby-cat-settings',
        'tabby_cat_cpt_section',
        array('field' => 'cpt_singular', 'placeholder' => 'e.g., Additional Work, Team Member')
    );
    
    add_settings_field(
        'cpt_plural',
        'Plural Name',
        'tabby_cat_text_field',
        'tabby-cat-settings',
        'tabby_cat_cpt_section',
        array('field' => 'cpt_plural', 'placeholder' => 'e.g., Additional Work, Team Members')
    );
    
    add_settings_field(
        'cpt_menu_name',
        'Menu Name',
        'tabby_cat_text_field',
        'tabby-cat-settings',
        'tabby_cat_cpt_section',
        array('field' => 'cpt_menu_name', 'placeholder' => 'e.g., Additional Work, Team')
    );
    
    add_settings_field(
        'cpt_icon',
        'Menu Icon',
        'tabby_cat_icon_field',
        'tabby-cat-settings',
        'tabby_cat_cpt_section',
        array('field' => 'cpt_icon')
    );
    
    // Taxonomy Section
    add_settings_section(
        'tabby_cat_tax_section',
        'Category Labels',
        function() {
            echo '<p>Customize how the category taxonomy appears in the WordPress admin.</p>';
        },
        'tabby-cat-settings'
    );
    
    add_settings_field(
        'tax_singular',
        'Singular Name',
        'tabby_cat_text_field',
        'tabby-cat-settings',
        'tabby_cat_tax_section',
        array('field' => 'tax_singular', 'placeholder' => 'e.g., Service, Department')
    );
    
    add_settings_field(
        'tax_plural',
        'Plural Name',
        'tabby_cat_text_field',
        'tabby-cat-settings',
        'tabby_cat_tax_section',
        array('field' => 'tax_plural', 'placeholder' => 'e.g., Services, Departments')
    );
    
    // Style Options Section
    register_setting('tabby_cat_settings_group', 'tabby_cat_style_settings', 'tabby_cat_sanitize_style_settings');
    
    add_settings_section(
        'tabby_cat_style_section',
        'Style Options',
        function() {
            echo '<p>Customize the front-end appearance. Leave blank to use defaults.</p>';
        },
        'tabby-cat-settings'
    );
    
    add_settings_field(
        'bg_color',
        'Background Color',
        'tabby_cat_color_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'bg_color', 'default' => '#333132')
    );
    
    add_settings_field(
        'text_color',
        'Text Color',
        'tabby_cat_color_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'text_color', 'default' => '#ffffff')
    );
    
    add_settings_field(
        'accent_color',
        'Accent Color',
        'tabby_cat_color_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'accent_color', 'default' => '#D8EE78')
    );
    
    add_settings_field(
        'border_radius',
        'Visual Border Radius (px)',
        'tabby_cat_number_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'border_radius', 'default' => '20', 'min' => '0', 'max' => '100')
    );
    
    add_settings_field(
        'gallery_prev_icon',
        'Gallery Previous Icon',
        'tabby_cat_icon_select_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'gallery_prev_icon', 'default' => 'fa-chevron-left')
    );
    
    add_settings_field(
        'gallery_next_icon',
        'Gallery Next Icon',
        'tabby_cat_icon_select_field',
        'tabby-cat-settings',
        'tabby_cat_style_section',
        array('field' => 'gallery_next_icon', 'default' => 'fa-chevron-right')
    );
    
    // Display Options Section
    add_settings_section(
        'tabby_cat_display_section',
        'Display Options',
        function() {
            echo '<p>Control what elements are shown on the front-end.</p>';
        },
        'tabby-cat-settings'
    );
    
    add_settings_field(
        'show_counter',
        'Show Category Counter',
        'tabby_cat_checkbox_field',
        'tabby-cat-settings',
        'tabby_cat_display_section',
        array('field' => 'show_counter', 'label' => 'Display item count badges next to category names')
    );
}
add_action('admin_init', 'tabby_cat_register_settings');

/**
 * Text Field Callback
 */
function tabby_cat_text_field($args) {
    $settings = tabby_cat_get_settings();
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
    $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
    
    printf(
        '<input type="text" name="tabby_cat_settings[%s]" value="%s" placeholder="%s" class="regular-text">',
        esc_attr($args['field']),
        esc_attr($value),
        esc_attr($placeholder)
    );
}

/**
 * Icon Field Callback
 */
function tabby_cat_icon_field($args) {
    $settings = tabby_cat_get_settings();
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 'dashicons-category';
    
    $icons = array(
        'dashicons-category'        => 'Category',
        'dashicons-portfolio'       => 'Portfolio',
        'dashicons-images-alt2'     => 'Images',
        'dashicons-format-gallery'  => 'Gallery',
        'dashicons-grid-view'       => 'Grid',
        'dashicons-screenoptions'   => 'Squares',
        'dashicons-star-filled'     => 'Star',
        'dashicons-heart'           => 'Heart',
        'dashicons-awards'          => 'Award',
        'dashicons-groups'          => 'Groups',
        'dashicons-businessman'     => 'Person',
        'dashicons-building'        => 'Building',
        'dashicons-store'           => 'Store',
        'dashicons-cart'            => 'Cart',
        'dashicons-megaphone'       => 'Megaphone',
        'dashicons-lightbulb'       => 'Lightbulb',
        'dashicons-hammer'          => 'Hammer',
        'dashicons-art'             => 'Art',
        'dashicons-palmtree'        => 'Palm Tree',
        'dashicons-pets'            => 'Pets',
    );
    
    echo '<select name="tabby_cat_settings[' . esc_attr($args['field']) . ']" class="tabby-cat-icon-select">';
    foreach ($icons as $icon => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($icon),
            selected($value, $icon, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<span class="dashicons ' . esc_attr($value) . '" style="margin-left: 10px; vertical-align: middle;"></span>';
    
    // JavaScript to update icon preview
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.tabby-cat-icon-select').on('change', function() {
            $(this).next('.dashicons').attr('class', 'dashicons ' + $(this).val());
        });
    });
    </script>
    <?php
}

/**
 * Color Field Callback
 */
function tabby_cat_color_field($args) {
    $settings = get_option('tabby_cat_style_settings', array());
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
    $default = isset($args['default']) ? $args['default'] : '#000000';
    
    printf(
        '<input type="text" name="tabby_cat_style_settings[%s]" value="%s" class="tabby-cat-color-picker" data-default-color="%s">',
        esc_attr($args['field']),
        esc_attr($value ? $value : $default),
        esc_attr($default)
    );
}

/**
 * Checkbox Field Callback
 */
function tabby_cat_checkbox_field($args) {
    $settings = get_option('tabby_cat_style_settings', array());
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '1'; // Default to checked
    $label = isset($args['label']) ? $args['label'] : '';
    
    printf(
        '<label><input type="checkbox" name="tabby_cat_style_settings[%s]" value="1" %s> %s</label>',
        esc_attr($args['field']),
        checked($value, '1', false),
        esc_html($label)
    );
}

/**
 * Number Field Callback
 */
function tabby_cat_number_field($args) {
    $settings = get_option('tabby_cat_style_settings', array());
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
    $default = isset($args['default']) ? $args['default'] : '0';
    $min = isset($args['min']) ? $args['min'] : '0';
    $max = isset($args['max']) ? $args['max'] : '999';
    
    printf(
        '<input type="number" name="tabby_cat_style_settings[%s]" value="%s" placeholder="%s" min="%s" max="%s" style="width: 80px;">',
        esc_attr($args['field']),
        esc_attr($value),
        esc_attr($default),
        esc_attr($min),
        esc_attr($max)
    );
    echo '<span class="description" style="margin-left: 10px;">Default: ' . esc_html($default) . 'px</span>';
}

/**
 * Icon Select Field Callback (Font Awesome)
 */
function tabby_cat_icon_select_field($args) {
    $settings = get_option('tabby_cat_style_settings', array());
    $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
    $default = isset($args['default']) ? $args['default'] : 'fa-chevron-left';
    
    $icons = array(
        'fa-chevron-left'   => 'Chevron Left',
        'fa-chevron-right'  => 'Chevron Right',
        'fa-arrow-left'     => 'Arrow Left',
        'fa-arrow-right'    => 'Arrow Right',
        'fa-angle-left'     => 'Angle Left',
        'fa-angle-right'    => 'Angle Right',
        'fa-caret-left'     => 'Caret Left',
        'fa-caret-right'    => 'Caret Right',
        'fa-circle-arrow-left'  => 'Circle Arrow Left',
        'fa-circle-arrow-right' => 'Circle Arrow Right',
        'fa-circle-chevron-left'  => 'Circle Chevron Left',
        'fa-circle-chevron-right' => 'Circle Chevron Right',
    );
    
    // Enqueue Font Awesome for admin preview
    wp_enqueue_style('font-awesome-admin', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
    
    echo '<select name="tabby_cat_style_settings[' . esc_attr($args['field']) . ']" class="tabby-cat-fa-icon-select" data-preview="' . esc_attr($args['field']) . '-preview">';
    foreach ($icons as $icon => $label) {
        $is_selected = ($value === $icon) || (empty($value) && $icon === $default);
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($icon),
            selected($is_selected, true, false),
            esc_html($label)
        );
    }
    echo '</select>';
    echo '<i class="fa-solid ' . esc_attr($value ? $value : $default) . '" id="' . esc_attr($args['field']) . '-preview" style="margin-left: 10px; font-size: 18px;"></i>';
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('select[data-preview="<?php echo esc_js($args['field']); ?>-preview"]').on('change', function() {
            $('#<?php echo esc_js($args['field']); ?>-preview').attr('class', 'fa-solid ' + $(this).val());
        });
    });
    </script>
    <?php
}

/**
 * Sanitize Style Settings
 */
function tabby_cat_sanitize_style_settings($input) {
    $sanitized = array();
    
    // Color fields
    $color_fields = array('bg_color', 'text_color', 'accent_color');
    foreach ($color_fields as $field) {
        if (isset($input[$field]) && !empty($input[$field])) {
            $sanitized[$field] = sanitize_hex_color($input[$field]);
        }
    }
    
    // Number fields
    if (isset($input['border_radius'])) {
        $sanitized['border_radius'] = absint($input['border_radius']);
    }
    
    // Icon fields
    $icon_fields = array('gallery_prev_icon', 'gallery_next_icon');
    foreach ($icon_fields as $field) {
        if (isset($input[$field]) && !empty($input[$field])) {
            $sanitized[$field] = sanitize_text_field($input[$field]);
        }
    }
    
    // Checkbox fields - if not set, it means unchecked
    $sanitized['show_counter'] = isset($input['show_counter']) ? '1' : '0';
    
    return $sanitized;
}

/**
 * Sanitize Settings
 */
function tabby_cat_sanitize_settings($input) {
    $sanitized = array();
    
    $text_fields = array('cpt_singular', 'cpt_plural', 'cpt_menu_name', 'tax_singular', 'tax_plural');
    
    foreach ($text_fields as $field) {
        $sanitized[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
    }
    
    $sanitized['cpt_icon'] = isset($input['cpt_icon']) ? sanitize_text_field($input['cpt_icon']) : 'dashicons-category';
    
    return $sanitized;
}

/**
 * Render Settings Page
 */
function tabby_cat_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Show save message
    if (isset($_GET['settings-updated'])) {
        add_settings_error('tabby_cat_messages', 'tabby_cat_message', 'Settings saved. You may need to refresh the page to see updated menu labels.', 'updated');
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('tabby_cat_messages'); ?>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('tabby_cat_settings_group');
            do_settings_sections('tabby-cat-settings');
            submit_button('Save Settings');
            ?>
        </form>
        
        <hr style="margin: 30px 0;">
        
        <h2>Shortcode Usage</h2>
        <p>Use this shortcode to display the Tabby Cat component on any page:</p>
        <code style="display: block; padding: 15px; background: #f0f0f0; margin: 10px 0;">[tabby_cat]</code>
        
        <p>Optional parameters:</p>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><code>category</code> - Display only items from specific category slugs (comma-separated)</li>
            <li><code>exclude_category</code> - Exclude specific category slugs (comma-separated)</li>
            <li><code>tag</code> - Show only categories with this tag (assigned in category settings)</li>
            <li><code>orderby</code> - Order items by: title, date, menu_order (default: title)</li>
            <li><code>order</code> - Order direction: ASC or DESC (default: ASC)</li>
        </ul>

        <p>Examples:</p>
        <code style="display: block; padding: 15px; background: #f0f0f0; margin: 10px 0;">[tabby_cat category="branding,web-design" orderby="date" order="DESC"]</code>
        <code style="display: block; padding: 15px; background: #f0f0f0; margin: 10px 0;">[tabby_cat tag="homepage"]</code>
    </div>
    <?php
}

/**
 * Add custom columns to admin list
 */
function tabby_cat_admin_columns($columns) {
    $settings = tabby_cat_get_settings();
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['item_title'] = $settings['field_title'];
            $new_columns['visual_type'] = 'Visual Type';
        }
    }
    
    return $new_columns;
}
add_filter('manage_tabby_cat_item_posts_columns', 'tabby_cat_admin_columns');

/**
 * Populate custom columns
 */
function tabby_cat_admin_column_content($column, $post_id) {
    switch ($column) {
        case 'item_title':
            $title = get_field('tabby_item_title', $post_id);
            echo $title ? esc_html($title) : '—';
            break;
            
        case 'visual_type':
            $visual_type = get_field('tabby_visual_type', $post_id);
            if ($visual_type) {
                $types = array(
                    'image'   => '🖼️ Image',
                    'gallery' => '🎞️ Gallery',
                    'video'   => '🎬 Video',
                );
                echo isset($types[$visual_type]) ? $types[$visual_type] : esc_html($visual_type);
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_tabby_cat_item_posts_custom_column', 'tabby_cat_admin_column_content', 10, 2);

/**
 * Add Tags column to category list table
 */
function tabby_cat_category_columns($columns) {
    $columns['tabby_cat_tags'] = 'Tags';
    return $columns;
}
add_filter('manage_edit-tabby_cat_category_columns', 'tabby_cat_category_columns');

function tabby_cat_category_column_content($content, $column_name, $term_id) {
    if ($column_name === 'tabby_cat_tags') {
        $tags = get_term_meta($term_id, 'tabby_cat_tags', true);
        return $tags ? esc_html($tags) : '&mdash;';
    }
    return $content;
}
add_filter('manage_tabby_cat_category_custom_column', 'tabby_cat_category_column_content', 10, 3);

/**
 * Add description text to category list page
 */
function tabby_cat_category_description() {
    $screen = get_current_screen();
    if ($screen && $screen->taxonomy === 'tabby_cat_category') {
        echo '<p style="margin-bottom: 1em; color: #646970;">These are the top-tier categories displayed as the horizontal tabs at the top of the Tabby Cat component.</p>';
    }
}
add_action('tabby_cat_category_pre_add_form', 'tabby_cat_category_description');

/**
 * Add category filter dropdown to CPT list page
 */
function tabby_cat_admin_filter_dropdown() {
    global $typenow;
    if ($typenow !== 'tabby_cat_item') {
        return;
    }
    $settings = tabby_cat_get_settings();
    $selected = isset($_GET['tabby_cat_category']) ? sanitize_text_field($_GET['tabby_cat_category']) : '';
    $terms = get_terms(array(
        'taxonomy'   => 'tabby_cat_category',
        'hide_empty' => true,
    ));
    if (!empty($terms) && !is_wp_error($terms)) {
        echo '<select name="tabby_cat_category">';
        echo '<option value="">' . esc_html('All ' . $settings['tax_plural']) . '</option>';
        foreach ($terms as $term) {
            printf(
                '<option value="%s" %s>%s (%d)</option>',
                esc_attr($term->slug),
                selected($selected, $term->slug, false),
                esc_html($term->name),
                $term->count
            );
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'tabby_cat_admin_filter_dropdown');

/**
 * Hide Re-Order submenu page (added by reorder plugins)
 */
function tabby_cat_hide_reorder_submenu() {
    global $submenu;
    $parent = 'edit.php?post_type=tabby_cat_item';
    if (!empty($submenu[$parent])) {
        foreach ($submenu[$parent] as $index => $item) {
            if (stripos($item[0], 're-order') !== false || stripos($item[0], 'reorder') !== false) {
                unset($submenu[$parent][$index]);
            }
        }
    }
}
add_action('admin_menu', 'tabby_cat_hide_reorder_submenu', 999);

/**
 * Sync post title with item title field
 */
function tabby_cat_sync_title($post_id) {
    remove_action('acf/save_post', 'tabby_cat_sync_title', 20);
    
    if (get_post_type($post_id) !== 'tabby_cat_item') {
        return;
    }
    
    $item_title = get_field('tabby_item_title', $post_id);
    
    if ($item_title) {
        wp_update_post(array(
            'ID'         => $post_id,
            'post_title' => $item_title,
            'post_name'  => sanitize_title($item_title),
        ));
    }
    
    add_action('acf/save_post', 'tabby_cat_sync_title', 20);
}
add_action('acf/save_post', 'tabby_cat_sync_title', 20);

/**
 * Register ACF fields via PHP
 */
function tabby_cat_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_tabby_cat_item',
        'title' => 'Item Details',
        'fields' => array(
            array(
                'key' => 'field_tabby_item_title',
                'label' => 'Title',
                'name' => 'tabby_item_title',
                'type' => 'text',
                'instructions' => 'The display title for this item',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_tabby_item_description',
                'label' => 'Description',
                'name' => 'tabby_item_description',
                'type' => 'textarea',
                'instructions' => 'Brief description of this item',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'rows' => 4,
                'new_lines' => 'br',
            ),
            array(
                'key' => 'field_tabby_link_text',
                'label' => 'Link Button Text',
                'name' => 'tabby_link_text',
                'type' => 'text',
                'instructions' => 'Text to display on the button',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 'View',
                'placeholder' => 'e.g., View Website, Learn More, See Project',
            ),
            array(
                'key' => 'field_tabby_link_url',
                'label' => 'Link URL',
                'name' => 'tabby_link_url',
                'type' => 'url',
                'instructions' => 'URL the button links to',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://',
            ),
            array(
                'key' => 'field_tabby_visual_type',
                'label' => 'Visual Type',
                'name' => 'tabby_visual_type',
                'type' => 'select',
                'instructions' => 'Select the type of visual to display',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'image' => 'Image',
                    'gallery' => 'Gallery',
                    'video' => 'Video',
                ),
                'default_value' => 'image',
                'return_format' => 'value',
                'ui' => 1,
            ),
            array(
                'key' => 'field_tabby_image',
                'label' => 'Image',
                'name' => 'tabby_image',
                'type' => 'image',
                'instructions' => 'Upload a single image',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_tabby_visual_type',
                            'operator' => '==',
                            'value' => 'image',
                        ),
                    ),
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'mime_types' => 'jpg, jpeg, png, webp, gif',
            ),
            array(
                'key' => 'field_tabby_gallery',
                'label' => 'Gallery',
                'name' => 'tabby_gallery',
                'type' => 'gallery',
                'instructions' => 'Upload multiple images for a slider/gallery',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_tabby_visual_type',
                            'operator' => '==',
                            'value' => 'gallery',
                        ),
                    ),
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'min' => 2,
                'mime_types' => 'jpg, jpeg, png, webp, gif',
            ),
            array(
                'key' => 'field_tabby_video',
                'label' => 'Video',
                'name' => 'tabby_video',
                'type' => 'oembed',
                'instructions' => 'Paste a video URL (YouTube, Vimeo, etc.)',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_tabby_visual_type',
                            'operator' => '==',
                            'value' => 'video',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_tabby_video_thumbnail',
                'label' => 'Video Thumbnail',
                'name' => 'tabby_video_thumbnail',
                'type' => 'image',
                'instructions' => 'Optional thumbnail image to display before the video loads. If not set, the video embed will display directly.',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_tabby_visual_type',
                            'operator' => '==',
                            'value' => 'video',
                        ),
                    ),
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
                'mime_types' => 'jpg, jpeg, png, webp, gif',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'tabby_cat_item',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => array(
            'the_content',
            'excerpt',
            'discussion',
            'comments',
            'featured_image',
        ),
        'active' => true,
    ));
}
add_action('acf/init', 'tabby_cat_register_acf_fields');

/**
 * Check for ACF dependency
 */
function tabby_cat_check_acf() {
    if (!class_exists('ACF')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><strong>Tabby Cat</strong> requires Advanced Custom Fields (ACF) to be installed and activated.</p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'tabby_cat_check_acf');

/**
 * Provide plugin information for the details modal
 */
function tabby_cat_plugin_info($result, $action, $args) {
    // Only respond to our plugin
    if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'tabby-cat') {
        return $result;
    }

    $plugin_info = new stdClass();
    
    // Basic info
    $plugin_info->name           = 'Tabby Cat';
    $plugin_info->slug           = 'tabby-cat';
    $plugin_info->version        = TABBY_CAT_VERSION;
    $plugin_info->author         = '<a href="/team/becca-barth">Cozy Cat</a>';
    $plugin_info->requires       = '5.0';
    $plugin_info->tested         = '6.4';
    $plugin_info->requires_php   = '7.4';
    $plugin_info->downloaded     = 0;
    $plugin_info->last_updated   = date('Y-m-d');
    $plugin_info->download_link  = '';
    
    // Sections that appear as tabs in the modal
    $plugin_info->sections = array(
        'description'  => tabby_cat_get_plugin_description(),
        'installation' => tabby_cat_get_plugin_installation(),
        'changelog'    => tabby_cat_get_plugin_changelog(),
    );
    
    // Banners (optional - would need actual image URLs)
    // $plugin_info->banners = array(
    //     'low'  => 'https://cozycat.dev/assets/tabby-cat-banner-772x250.png',
    //     'high' => 'https://cozycat.dev/assets/tabby-cat-banner-1544x500.png',
    // );
    
    return $plugin_info;
}
add_filter('plugins_api', 'tabby_cat_plugin_info', 20, 3);

/**
 * Plugin description content
 */
function tabby_cat_get_plugin_description() {
    return '
        <h3>A Two-Tier Master-Detail Display Component</h3>
        <p>Tabby Cat creates an elegant, interactive interface for showcasing categorized content. Perfect for:</p>
        <ul>
            <li><strong>Portfolio items</strong> - Display work samples organized by service type</li>
            <li><strong>Team members</strong> - Show staff organized by department</li>
            <li><strong>Products</strong> - Feature items organized by category</li>
            <li><strong>FAQs</strong> - Organize questions by topic</li>
            <li><strong>Any categorized content</strong> - Fully customizable labels</li>
        </ul>
        
        <h3>How It Works</h3>
        <p>Users first select a <strong>category</strong> (top level), then browse <strong>items</strong> within that category (left column), and view the <strong>detail content</strong> for each item (right column). It\'s a nested tabbed interface - or as we like to call it, a "two-tabby" system.</p>
        
        <h3>Features</h3>
        <ul>
            <li>Customizable admin labels via Settings page</li>
            <li>Support for images, galleries, and videos</li>
            <li>Simple shortcode placement</li>
            <li>ACF-powered custom fields (auto-loaded)</li>
            <li>Clean, accessible markup</li>
            <li>Part of the Cozy Cat plugin family</li>
        </ul>
        
        <h3>Requirements</h3>
        <ul>
            <li>WordPress 5.0 or higher</li>
            <li>Advanced Custom Fields (ACF) - Free or Pro</li>
            <li>PHP 7.4 or higher</li>
        </ul>
    ';
}

/**
 * Plugin installation content
 */
function tabby_cat_get_plugin_installation() {
    return '
        <h3>Installation</h3>
        <ol>
            <li>Upload the <code>tabby-cat</code> folder to <code>/wp-content/plugins/</code></li>
            <li>Activate the plugin through the Plugins menu</li>
            <li>Ensure Advanced Custom Fields (ACF) is installed and active</li>
        </ol>
        
        <h3>Configuration</h3>
        <ol>
            <li>Go to <strong>Tabby Cat → Settings</strong></li>
            <li>Customize the content type and category labels for your use case</li>
            <li>Choose a menu icon</li>
            <li>Save settings</li>
        </ol>
        
        <h3>Adding Content</h3>
        <ol>
            <li>Go to <strong>Tabby Cat → Categories</strong> and create your top-level categories</li>
            <li>Go to <strong>Tabby Cat → Add New</strong> to create items</li>
            <li>Fill in: Title, Description, Link (optional), and Visual (image/gallery/video)</li>
            <li>Assign each item to one or more categories</li>
            <li>Publish!</li>
        </ol>
        
        <h3>Displaying on Your Site</h3>
        <p>Add the shortcode to any page, post, or Divi module:</p>
        <pre>[tabby_cat]</pre>
        
        <h4>Shortcode Parameters</h4>
        <ul>
            <li><code>category="slug1,slug2"</code> - Only show specific categories</li>
            <li><code>exclude_category="slug"</code> - Exclude specific categories</li>
            <li><code>tag="tag-name"</code> - Show only categories with this tag</li>
            <li><code>orderby="title|date|menu_order"</code> - Sort items (default: title)</li>
            <li><code>order="ASC|DESC"</code> - Sort direction (default: ASC)</li>
        </ul>

        <h4>Examples</h4>
        <pre>[tabby_cat category="branding,web-design" orderby="date" order="DESC"]</pre>
        <pre>[tabby_cat tag="homepage"]</pre>
    ';
}

/**
 * Plugin changelog content
 */
function tabby_cat_get_plugin_changelog() {
    return '
        <h3>Version 1.4.0</h3>
        <p><em>Released: ' . date('F j, Y') . '</em></p>
        <ul>
            <li>Added tag filtering for category tabs via shortcode <code>tag</code> attribute</li>
            <li>Tags field on category add/edit screens for assigning comma-separated tags</li>
            <li>Tags column in category admin list table</li>
        </ul>

        <h3>Version 1.3.0</h3>
        <p><em>Released: February 7, 2026</em></p>
        <ul>
            <li>Mobile breakpoint raised from 599px to 769px for tablet support</li>
            <li>Desktop detail area stacks visual above text below 1280px</li>
            <li>Added image lightbox with gallery navigation</li>
            <li>Video autoplay on thumbnail click</li>
            <li>Mobile: first item content shown by default</li>
            <li>Mobile: item title displayed in content area</li>
            <li>Mobile: horizontal and vertical dividers for layout clarity</li>
        </ul>

        <h3>Version 1.2.0</h3>
        <p><em>Released: February 6, 2026</em></p>
        <ul>
            <li>Removed container padding for flush-left alignment</li>
            <li>First category tab now flush left (no left padding)</li>
            <li>Category counts now superscript-style (raised position, smaller font)</li>
            <li>Desktop layout holds until 600px with tighter gaps</li>
            <li>New mobile layout (599px and below):
                <ul>
                    <li>Counters hidden on mobile regardless of setting</li>
                    <li>Two-column navigation: categories left, items right</li>
                    <li>Content hidden by default, appears above nav when item selected</li>
                    <li>Visual stacked on top of text/button</li>
                    <li>Content persists when switching categories until new item selected</li>
                </ul>
            </li>
        </ul>
        
        <h3>Version 1.1.1</h3>
        <ul>
            <li>Added Display Options section with category counter toggle</li>
            <li>Added video thumbnail field with play button overlay</li>
            <li>Updated hover style to accent underline instead of background color</li>
            <li>Reduced default border radius to 20px</li>
            <li>Reduced gallery nav button size to 2rem</li>
            <li>Set container background to transparent</li>
            <li>Button font weight reduced to 300</li>
        </ul>
        
        <h3>Version 1.1.0</h3>
        <ul>
            <li>Added Style Options settings section for customizing colors, border radius, and gallery icons</li>
            <li>WordPress color picker integration for reliable color selection</li>
            <li>Updated color scheme: new background (#333132) and accent (#D8EE78) colors</li>
            <li>Added hover states with semi-transparent accent color</li>
            <li>Removed redundant title from detail panel</li>
            <li>Improved button styling: white background, uppercase text, accent hover</li>
            <li>Fixed three-column layout (item list, text, visual)</li>
            <li>16:9 aspect ratio on all visual types</li>
            <li>40px border radius on visuals</li>
            <li>Font Awesome icons for gallery navigation</li>
            <li>Improved spacing between sections</li>
        </ul>
        
        <h3>Version 1.0.2</h3>
        <ul>
            <li>Added front-end shortcode display</li>
            <li>Added CSS with custom properties for easy theming</li>
            <li>Added JavaScript for tab switching and gallery navigation</li>
            <li>Full keyboard navigation support</li>
            <li>Responsive design for mobile devices</li>
        </ul>
        
        <h3>Version 1.0.1</h3>
        <ul>
            <li>Switched from ACF JSON to PHP field registration for reliability</li>
            <li>Added plugin details modal</li>
        </ul>
        
        <h3>Version 1.0.0</h3>
        <ul>
            <li>Initial release</li>
            <li>Customizable CPT and taxonomy labels via Settings page</li>
            <li>Support for image, gallery, and video visuals</li>
            <li>Admin columns with visual type indicators</li>
        </ul>
    ';
}

/**
 * =================================================================
 * FRONT-END SHORTCODE & DISPLAY
 * =================================================================
 */

/**
 * Register the shortcode
 */
function tabby_cat_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'category'         => '',
        'exclude_category' => '',
        'tag'              => '',
        'orderby'          => 'title',
        'order'            => 'ASC',
    ), $atts, 'tabby_cat');

    // Enqueue Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        array(),
        '6.5.1'
    );

    // Enqueue assets when shortcode is used
    wp_enqueue_style(
        'tabby-cat-styles',
        TABBY_CAT_URL . 'assets/css/tabby-cat.css',
        array('font-awesome'),
        TABBY_CAT_VERSION
    );
    
    // Add custom style overrides
    $style_settings = get_option('tabby_cat_style_settings', array());
    $custom_css = ':root {';
    
    if (!empty($style_settings['bg_color'])) {
        $custom_css .= '--tabby-bg: ' . sanitize_hex_color($style_settings['bg_color']) . ';';
    }
    if (!empty($style_settings['text_color'])) {
        $custom_css .= '--tabby-text: ' . sanitize_hex_color($style_settings['text_color']) . ';';
    }
    if (!empty($style_settings['accent_color'])) {
        $accent = sanitize_hex_color($style_settings['accent_color']);
        $custom_css .= '--tabby-accent: ' . $accent . ';';
        // Convert hex to rgba for hover
        $rgb = sscanf($accent, "#%02x%02x%02x");
        if ($rgb) {
            $custom_css .= '--tabby-hover: rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', 0.15);';
        }
    }
    if (!empty($style_settings['border_radius'])) {
        $custom_css .= '--tabby-visual-border-radius: ' . intval($style_settings['border_radius']) . 'px;';
    }
    
    $custom_css .= '}';
    
    if ($custom_css !== ':root {}') {
        wp_add_inline_style('tabby-cat-styles', $custom_css);
    }
    
    wp_enqueue_script(
        'tabby-cat-scripts',
        TABBY_CAT_URL . 'assets/js/tabby-cat.js',
        array(),
        TABBY_CAT_VERSION,
        true
    );

    // Get categories
    $tax_args = array(
        'taxonomy'   => 'tabby_cat_category',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    );

    // Filter by specific categories if provided
    if (!empty($atts['category'])) {
        $tax_args['slug'] = array_map('trim', explode(',', $atts['category']));
    }

    // Exclude categories if provided
    if (!empty($atts['exclude_category'])) {
        $exclude_slugs = array_map('trim', explode(',', $atts['exclude_category']));
        $exclude_terms = get_terms(array(
            'taxonomy' => 'tabby_cat_category',
            'slug'     => $exclude_slugs,
            'fields'   => 'ids',
        ));
        if (!empty($exclude_terms) && !is_wp_error($exclude_terms)) {
            $tax_args['exclude'] = $exclude_terms;
        }
    }

    $categories = get_terms($tax_args);

    // Filter by tag if provided
    if (!empty($atts['tag'])) {
        $tag_filter = strtolower(trim($atts['tag']));
        $categories = array_filter($categories, function($category) use ($tag_filter) {
            $tags_string = get_term_meta($category->term_id, 'tabby_cat_tags', true);
            if (empty($tags_string)) {
                return false;
            }
            $tags_array = array_map('trim', array_map('strtolower', explode(',', $tags_string)));
            return in_array($tag_filter, $tags_array, true);
        });
        $categories = array_values($categories);
    }

    if (empty($categories) || is_wp_error($categories)) {
        return '<p class="tabby-cat-empty">No items to display.</p>';
    }

    // Get all items grouped by category
    $items_by_category = array();
    
    foreach ($categories as $category) {
        $query_args = array(
            'post_type'      => 'tabby_cat_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'tax_query'      => array(
                array(
                    'taxonomy' => 'tabby_cat_category',
                    'field'    => 'term_id',
                    'terms'    => $category->term_id,
                ),
            ),
        );

        $items = get_posts($query_args);
        
        if (!empty($items)) {
            $items_by_category[$category->term_id] = array(
                'category' => $category,
                'items'    => $items,
            );
        }
    }

    if (empty($items_by_category)) {
        return '<p class="tabby-cat-empty">No items to display.</p>';
    }

    // Build output
    ob_start();
    ?>
    <div class="tabby-cat" role="tablist" aria-label="Content categories">
        
        <!-- Category Tabs -->
        <div class="tabby-cat__categories">
            <?php 
            $first_cat = true;
            $show_counter = !isset($style_settings['show_counter']) || $style_settings['show_counter'] === '1';
            foreach ($items_by_category as $cat_id => $data) : 
                $category = $data['category'];
                $count = count($data['items']);
            ?>
                <button 
                    class="tabby-cat__category-tab<?php echo $first_cat ? ' is-active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $first_cat ? 'true' : 'false'; ?>"
                    aria-controls="tabby-panel-<?php echo esc_attr($cat_id); ?>"
                    data-category="<?php echo esc_attr($cat_id); ?>"
                    id="tabby-tab-<?php echo esc_attr($cat_id); ?>"
                >
                    <?php echo esc_html($category->name); ?>
                    <?php if ($show_counter) : ?>
                        <span class="tabby-cat__count"><?php echo esc_html($count); ?></span>
                    <?php endif; ?>
                </button>
            <?php 
                $first_cat = false;
            endforeach; 
            ?>
        </div>

        <hr class="tabby-cat__divider">

        <!-- Category Panels -->
        <?php 
        $first_cat = true;
        foreach ($items_by_category as $cat_id => $data) : 
            $category = $data['category'];
            $items = $data['items'];
        ?>
            <div 
                class="tabby-cat__panel<?php echo $first_cat ? ' is-active' : ''; ?>"
                role="tabpanel"
                id="tabby-panel-<?php echo esc_attr($cat_id); ?>"
                aria-labelledby="tabby-tab-<?php echo esc_attr($cat_id); ?>"
                <?php echo !$first_cat ? 'hidden' : ''; ?>
            >
                <div class="tabby-cat__content">
                    
                    <!-- Item List (Left Column) -->
                    <div class="tabby-cat__item-list" role="tablist" aria-orientation="vertical" aria-label="<?php echo esc_attr($category->name); ?> items">
                        <?php 
                        $first_item = true;
                        foreach ($items as $item) : 
                            $title = get_field('tabby_item_title', $item->ID);
                            if (empty($title)) {
                                $title = $item->post_title;
                            }
                        ?>
                            <button 
                                class="tabby-cat__item-tab<?php echo $first_item ? ' is-active' : ''; ?>"
                                role="tab"
                                aria-selected="<?php echo $first_item ? 'true' : 'false'; ?>"
                                aria-controls="tabby-item-<?php echo esc_attr($item->ID); ?>"
                                data-item="<?php echo esc_attr($item->ID); ?>"
                                id="tabby-item-tab-<?php echo esc_attr($item->ID); ?>"
                            >
                                <?php echo esc_html($title); ?>
                            </button>
                        <?php 
                            $first_item = false;
                        endforeach; 
                        ?>
                    </div>

                    <!-- Item Details (Right Column) -->
                    <div class="tabby-cat__item-details">
                        <?php 
                        $first_item = true;
                        foreach ($items as $item) : 
                            $title = get_field('tabby_item_title', $item->ID);
                            $description = get_field('tabby_item_description', $item->ID);
                            $link_text = get_field('tabby_link_text', $item->ID);
                            $link_url = get_field('tabby_link_url', $item->ID);
                            $visual_type = get_field('tabby_visual_type', $item->ID);
                            
                            if (empty($title)) {
                                $title = $item->post_title;
                            }
                            if (empty($link_text)) {
                                $link_text = 'View';
                            }
                        ?>
                            <div 
                                class="tabby-cat__item-detail<?php echo $first_item ? ' is-active' : ''; ?>"
                                role="tabpanel"
                                id="tabby-item-<?php echo esc_attr($item->ID); ?>"
                                aria-labelledby="tabby-item-tab-<?php echo esc_attr($item->ID); ?>"
                                <?php echo !$first_item ? 'hidden' : ''; ?>
                            >
                                <div class="tabby-cat__detail-content">
                                    <h3 class="tabby-cat__detail-title"><?php echo esc_html($title); ?></h3>
                                    <div class="tabby-cat__detail-text">
                                        <div class="tabby-cat__detail-description">
                                            <?php echo wp_kses_post($description); ?>
                                        </div>
                                        <?php if (!empty($link_url)) : ?>
                                            <a href="<?php echo esc_url($link_url); ?>" class="tabby-cat__detail-link" target="_blank" rel="noopener noreferrer">
                                                <?php echo esc_html($link_text); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="tabby-cat__detail-visual">
                                        <?php 
                                        switch ($visual_type) {
                                            case 'image':
                                                $image = get_field('tabby_image', $item->ID);
                                                if ($image && is_array($image)) {
                                                    $img_url = isset($image['sizes']['large']) ? $image['sizes']['large'] : $image['url'];
                                                    $img_full = $image['url'];
                                                    $img_alt = isset($image['alt']) && !empty($image['alt']) ? $image['alt'] : $title;
                                                    echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" class="tabby-cat__image" loading="lazy" data-full-src="' . esc_url($img_full) . '">';
                                                }
                                                break;
                                                
                                            case 'gallery':
                                                $gallery = get_field('tabby_gallery', $item->ID);
                                                if ($gallery && is_array($gallery)) {
                                                    $style_settings = get_option('tabby_cat_style_settings', array());
                                                    $prev_icon = isset($style_settings['gallery_prev_icon']) && !empty($style_settings['gallery_prev_icon']) ? $style_settings['gallery_prev_icon'] : 'fa-chevron-left';
                                                    $next_icon = isset($style_settings['gallery_next_icon']) && !empty($style_settings['gallery_next_icon']) ? $style_settings['gallery_next_icon'] : 'fa-chevron-right';
                                                    
                                                    echo '<div class="tabby-cat__gallery">';
                                                    echo '<div class="tabby-cat__gallery-track">';
                                                    foreach ($gallery as $img) {
                                                        $img_url = isset($img['sizes']['large']) ? $img['sizes']['large'] : $img['url'];
                                                        $img_full = $img['url'];
                                                        $img_alt = isset($img['alt']) && !empty($img['alt']) ? $img['alt'] : $title;
                                                        echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" class="tabby-cat__gallery-image" loading="lazy" data-full-src="' . esc_url($img_full) . '">';
                                                    }
                                                    echo '</div>';
                                                    echo '<div class="tabby-cat__gallery-nav">';
                                                    echo '<button class="tabby-cat__gallery-prev" aria-label="Previous image"><i class="fa-solid ' . esc_attr($prev_icon) . '"></i></button>';
                                                    echo '<span class="tabby-cat__gallery-counter"><span class="current">1</span> / <span class="total">' . count($gallery) . '</span></span>';
                                                    echo '<button class="tabby-cat__gallery-next" aria-label="Next image"><i class="fa-solid ' . esc_attr($next_icon) . '"></i></button>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                                break;
                                                
                                            case 'video':
                                                $video = get_field('tabby_video', $item->ID);
                                                $video_thumbnail = get_field('tabby_video_thumbnail', $item->ID);
                                                if ($video) {
                                                    echo '<div class="tabby-cat__video">';
                                                    if ($video_thumbnail && is_array($video_thumbnail)) {
                                                        $thumb_url = isset($video_thumbnail['sizes']['large']) ? $video_thumbnail['sizes']['large'] : $video_thumbnail['url'];
                                                        $thumb_alt = isset($video_thumbnail['alt']) && !empty($video_thumbnail['alt']) ? $video_thumbnail['alt'] : $title . ' video thumbnail';
                                                        echo '<div class="tabby-cat__video-thumbnail" data-video="' . esc_attr(base64_encode($video)) . '">';
                                                        echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($thumb_alt) . '" loading="lazy">';
                                                        echo '<button class="tabby-cat__video-play" aria-label="Play video"><i class="fa-solid fa-play"></i></button>';
                                                        echo '</div>';
                                                    } else {
                                                        echo $video;
                                                    }
                                                    echo '</div>';
                                                }
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            $first_item = false;
                        endforeach; 
                        ?>
                    </div>

                </div>
            </div>
        <?php 
            $first_cat = false;
        endforeach; 
        ?>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('tabby_cat', 'tabby_cat_shortcode');

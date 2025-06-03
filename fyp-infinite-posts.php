<?php
/**
 * Plugin Name: FYP Infinite Posts
 * Description: A plugin to display infinite posts with various pagination options.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Stable tag: 1.0.0
 * Author: François Yerg
 * Author URI: https://www.francoisyerg.net
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fyp-infinite-posts
 * Domain Path: /languages
 */

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for the plugin
define('FYPINPO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FYPINPO_PLUGIN_DIR', plugin_dir_path(__FILE__));

/*
 * Shortcode To display infinite posts
 * Usage: [fyplugins_infinite_posts pagination="scroll" post_type="post" categories="" posts_per_page="10" offset="0" orderby="date" order="DESC" btn_text="Load more" end_message="No more posts to load."]
 */
add_shortcode('fyplugins_infinite_posts', function($atts) {
    $atts = shortcode_atts([
        'pagination' => 'scroll',
        'post_type' => 'post',
        'category' => true,
        'posts_per_page' => 10,
        'offset' => 0,
        'orderby' => 'date',
        'order' => 'DESC',
        'btn_text' => __('Load more', 'fyp-infinite-posts'),
        'end_message' => __('No more posts to load.', 'fyp-infinite-posts'),
    ], $atts, 'fyp_infinite_posts');

    // Check for categories
    $category = 0;
    if ($atts['category'] === true) {
        if (is_category()) {
            $category = get_queried_object();
            if ($category && !is_wp_error($category)) {
                $category = $category->term_id;
            }
        } elseif (is_singular($atts['post_type'])) {
            $terms = get_the_terms(get_the_ID(), 'category');
            if (!is_wp_error($terms) && !empty($terms)) {
                $category = $terms[0]->term_id;
            }
        }
    } elseif (!empty($atts['category'])) {
        $term = get_term_by('slug', $atts['category'], 'category');
        if ($term && !is_wp_error($term)) {
            $category = $term->term_id;
        }
    }

    // Validate attributes
    $pagination = in_array($atts['pagination'], ['scroll', 'button', 'none']) ? $atts['pagination'] : 'scroll';
    $post_type = (post_type_exists(sanitize_text_field($atts['post_type'])) ? sanitize_text_field($atts['post_type']) : 'post');
    $posts_per_page = intval($atts['posts_per_page']);
    $offset = intval($atts['offset']);
    $orderby = in_array($atts['orderby'], ['date', 'title', 'rand']) ? $atts['orderby'] : 'date';
    $order = in_array($atts['order'], ['ASC', 'DESC']) ? $atts['order'] : 'DESC';
    $end_message = sanitize_text_field($atts['end_message']);
    
    // Generate a unique ID for the wrapper
    $id = uniqid();

    // Enqueue the necessary stylesheet if not already enqueued
    if (!wp_style_is('fyp-infinite-posts', 'enqueued')) {
        wp_enqueue_style('fyp-infinite-posts', FYPINPO_PLUGIN_URL . 'assets/css/infinite-posts.css' , [], 1.0);
    }

    // Enqueue the necessary script if not already enqueued and needed
    if (!wp_script_is('fypinpo-infinite-posts', 'endqueued') && $atts['pagination'] !== 'none') {
        wp_enqueue_script('fypinpo-infinite-posts', FYPINPO_PLUGIN_URL . 'assets/js/infinite-posts.js', ['jquery'], 1.0, true);
        wp_localize_script('fypinpo-infinite-posts', 'fypinpo_infinite_posts', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fypinpo_load_more_nonce')
        ]);
    }

    ob_start();

    echo '<div id="fypip_' . esc_attr($id) . '_wrapper" class="fypip_wrapper" data-id="' . esc_attr($id) . '" data-pagination="' . esc_attr($pagination) . '" data-post_type="'.esc_attr($post_type).'" data-category="'.esc_attr($category).'" data-page="2" data-posts_per_page="'.esc_attr($posts_per_page).'" data-offset="'.esc_attr($offset).'" data-orderby="'.esc_attr($orderby).'" data-order="'.esc_attr($order).'">';
    echo '<div id="fypip_' . esc_attr($id) . '_posts" class="fypip_posts">';
    echo wp_kses_post(fypinpo_posts_list($post_type, $category, $posts_per_page, $offset, $orderby, $order, 1));
    echo '</div>';

    if ($atts['pagination'] !== 'none') {
        // Loader CSS
        echo '<div id="fypip_' . esc_attr($id) . '_loader" class="fypip_loader"><div class="fypip_spinner"></div></div>';

        // load more button
        if ($atts['pagination'] === 'button') {
            echo '<button id="fypip_' . esc_attr($id) . '_load-more-btn" class="fypip_load-more-btn">' . esc_html($atts['btn_text']) . '</button>';
        }
        else if ($atts['pagination'] === 'scroll') {
            // Scroll to load more
            echo '<div id="fypip_' . esc_attr($id) . '_scroll-marker" class="fypip_scroll-marker"></div>';
        }
        
        // End message
        if (!empty($atts['end_message'])) {
            echo '<div id="fypip_' . esc_attr($id) . '_end-message" class="fypip_end-message">' . esc_html($atts['end_message']) . '</div>';
        }
    }

    echo "</div>";

    return ob_get_clean();
});

/*
 * Ajax handler for loading more posts
 * This function will be called via AJAX when the user scrolls to the bottom of the page or clicks the load more button.
 */
add_action('wp_ajax_fypinpo_load_more', 'fypinpo_ajax_load_more');
add_action('wp_ajax_nopriv_fypinpo_load_more', 'fypinpo_ajax_load_more');
function fypinpo_ajax_load_more() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fypinpo_load_more_nonce')) {
        wp_send_json_error(__('Invalid nonce', 'fyp-infinite-posts'));
        wp_die();
    }

    $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : 'post';
    $category = isset($_POST['category']) ? intval($_POST['category']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $orderby = isset($_POST['orderby']) ? sanitize_text_field(wp_unslash($_POST['orderby'])) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'DESC';

    $html =  fypinpo_posts_list($post_type, $category, $posts_per_page, $offset, $orderby, $order, $page);
    
    wp_send_json_success([
        'html' => $html
    ]);

    wp_die();
}

/*
 * Function to fetch and display posts based on the provided parameters
 * This function is used in both the shortcode and the AJAX handler.
 * 
 * @param string $post_type The post type to fetch.
 * @param string $category The category slug to filter by.
 * @param int $posts_per_page The number of posts to fetch per page.
 * @param int $offset The offset for pagination.
 * @param string $orderby The field to order by.
 * @param string $order The order direction (ASC or DESC).
 * @param int $page The current page number.
 * @return string The HTML output of the posts.
 */
function fypinpo_posts_list($post_type, $category, $posts_per_page, $offset, $orderby, $order, $page) {
    // Validate data
    if (!post_type_exists($post_type)) return false;
    if (!is_string($category) && !is_int($category)) return false;
    if (!is_int($posts_per_page) || $posts_per_page < 1) return false;
    if (!is_int($offset) || $offset < 0) return false;
    if (!in_array($orderby, ['date', 'title', 'rand'])) return false;
    if (!in_array($order, ['ASC', 'DESC'])) return false;
    if (!is_int($page) || $page < 1) return false;

    // Calculate the offset
    $offset = $offset + ($posts_per_page * ($page - 1));

    // Construct the query arguments
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => $posts_per_page,
        'offset' => $offset,
        'orderby' => $orderby,
        'order' => $order,
        'ignore_custom_sort' => true,
        'post_status' => 'publish',
        'cat' => $category,
    ];
    
    // Create a new WP_Query instance
    $query = new WP_Query($args);

    ob_start();

    if (file_exists(get_template_directory() . '/fyp-infinite-posts/' . $post_type . '-item.php')) {
        // Use the theme's template if it exists
        $template = get_template_directory() . '/fyp-infinite-posts/' . $post_type . '-item.php';
    }
    else if (file_exists(FYPINPO_PLUGIN_DIR . 'templates/' . $post_type . '-item.php')) {
        // Use the plugin's template if it exists
        $template = FYPINPO_PLUGIN_DIR . 'templates/' . $post_type . '-item.php';
    }
    else {
        // Fallback to a default message if no template is found
        $template = FYPINPO_PLUGIN_DIR . 'templates/default-item.php';
    }

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            include($template);
        }
    }
    
    wp_reset_postdata();

    return ob_get_clean();
}
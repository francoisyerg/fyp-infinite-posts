<?php

/**
 * Plugin Name: FYP Infinite Posts
 * Description: A plugin to display infinite posts with various pagination options.
 * Version: 1.0.2
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Stable tag: 1.0.2
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
 * Usage: [fyplugins_infinite_posts pagination="scroll" post_type="post" categories="" posts_per_page="10" offset="0" orderby="date" order="DESC" btn_text="Load more" end_message="No more posts to load." class="]
 */
add_shortcode('fyplugins_infinite_posts', function ($atts): string {
    $atts = shortcode_atts([
        'pagination' => 'scroll',
        'post_type' => 'post',
        'category' => true,
        'taxonomy' => true,
        'posts_per_page' => 10,
        'offset' => 0,
        'orderby' => 'date',
        'order' => 'DESC',
        'btn_text' => __('Load more', 'fyp-infinite-posts'),
        'end_message' => __('No more posts to load.', 'fyp-infinite-posts'),
        'class' => '',
    ], $atts, 'fyplugins_infinite_posts');

    // Check for categories
    $category = 0;
    if ($atts['category'] == true) {
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

    // Check for taxonomy
    $taxonomy = '';
    $term = '';
    if ($atts['taxonomy'] == true) {
        if (is_tax()) {
            $term_obj = get_queried_object();
            if ($term_obj && !is_wp_error($term_obj)) {
                $taxonomy = $term_obj->taxonomy;
                $term = $term_obj->term_id;
            }
        } elseif (is_tag()) {
            $term_obj = get_queried_object();
            if ($term_obj && !is_wp_error($term_obj)) {
                $taxonomy = 'post_tag';
                $term = $term_obj->term_id;
            }
        } elseif (is_singular($atts['post_type'])) {
            // Get all taxonomies for this post type
            $taxonomies = get_object_taxonomies($atts['post_type']);
            foreach ($taxonomies as $tax) {
                if ($tax !== 'category') { // Skip category as it's handled separately
                    $terms = get_the_terms(get_the_ID(), $tax);
                    if (!is_wp_error($terms) && !empty($terms)) {
                        $taxonomy = $tax;
                        $term = $terms[0]->term_id;
                        break;
                    }
                }
            }
        }
    } elseif (!empty($atts['taxonomy'])) {
        $taxonomy = sanitize_text_field($atts['taxonomy']);
        if (!empty($atts['term'])) {
            $term_obj = get_term_by('slug', $atts['term'], $taxonomy);
            if ($term_obj && !is_wp_error($term_obj)) {
                $term = $term_obj->term_id;
            }
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
    $class = sanitize_html_class($atts['class']);

    // Generate a unique ID for the wrapper
    $id = uniqid();

    // Enqueue the necessary stylesheet if not already enqueued
    if (!wp_style_is('fyp-infinite-posts', 'enqueued')) {
        wp_enqueue_style('fyp-infinite-posts', FYPINPO_PLUGIN_URL . 'assets/css/infinite-posts.css', [], 1.0);
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

    echo '<div id="fypinpo_' . esc_attr($id) . '_wrapper" class="fypinpo_wrapper' . (!empty($class) ? ' '.esc_html($class) : "") . '" data-id="' . esc_attr($id) . '" data-pagination="' . esc_attr($pagination) . '" data-post_type="'.esc_attr($post_type).'" data-category="'.esc_attr($category).'" data-taxonomy="'.esc_attr($taxonomy).'" data-term="'.esc_attr($term).'" data-page="2" data-posts_per_page="'.esc_attr($posts_per_page).'" data-offset="'.esc_attr($offset).'" data-orderby="'.esc_attr($orderby).'" data-order="'.esc_attr($order).'">';
    echo '<div id="fypinpo_' . esc_attr($id) . '_posts" class="fypinpo_posts">';
    echo wp_kses_post(fypinpo_posts_list($post_type, $category, $taxonomy, $term, $posts_per_page, $offset, $orderby, $order, 1));
    echo '</div>';

    if ($atts['pagination'] !== 'none') {
        // Loader CSS
        echo '<div id="fypinpo_' . esc_attr($id) . '_loader" class="fypinpo_loader"><div class="fypinpo_spinner"></div></div>';

        // load more button
        if ($atts['pagination'] === 'button') {
            echo '<button id="fypinpo_' . esc_attr($id) . '_load-more-btn" class="fypinpo_load-more-btn">' . esc_html($atts['btn_text']) . '</button>';
        } elseif ($atts['pagination'] === 'scroll') {
            // Scroll to load more
            echo '<div id="fypinpo_' . esc_attr($id) . '_scroll-marker" class="fypinpo_scroll-marker"></div>';
        }

        // End message
        if (!empty($atts['end_message'])) {
            echo '<div id="fypinpo_' . esc_attr($id) . '_end-message" class="fypinpo_end-message">' . esc_html($atts['end_message']) . '</div>';
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
function fypinpo_ajax_load_more(): void
{
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'fypinpo_load_more_nonce')) {
        wp_send_json_error(__('Invalid nonce', 'fyp-infinite-posts'));
        wp_die();
    }

    $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : 'post';
    $category = isset($_POST['category']) ? intval($_POST['category']) : '';
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field(wp_unslash($_POST['taxonomy'])) : '';
    $term = isset($_POST['term']) ? intval($_POST['term']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $orderby = isset($_POST['orderby']) ? sanitize_text_field(wp_unslash($_POST['orderby'])) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field(wp_unslash($_POST['order'])) : 'DESC';

    $html =  fypinpo_posts_list($post_type, $category, $taxonomy, $term, $posts_per_page, $offset, $orderby, $order, $page);

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
 * @param string $taxonomy The taxonomy slug to filter by.
 * @param int $posts_per_page The number of posts to fetch per page.
 * @param int $offset The offset for pagination.
 * @param string $orderby The field to order by.
 * @param string $order The order direction (ASC or DESC).
 * @param int $page The current page number.
 * @return string The HTML output of the posts.
 */
function fypinpo_posts_list($post_type, $category, $taxonomy, $term, $posts_per_page, $offset, $orderby, $order, $page): string
{
    // Validate data
    if (!post_type_exists($post_type)) {
        return false;
    }
    if (!is_string($category) && !is_int($category)) {
        return false;
    }
    if (!is_string($taxonomy) && !is_int($taxonomy)) {
        return false;
    }
    if (!is_string($term) && !is_int($term)) {
        return false;
    }
    if (!is_int($posts_per_page) || $posts_per_page < 1) {
        return false;
    }
    if (!is_int($offset) || $offset < 0) {
        return false;
    }
    if (!in_array($orderby, ['date', 'title', 'rand'])) {
        return false;
    }
    if (!in_array($order, ['ASC', 'DESC'])) {
        return false;
    }
    if (!is_int($page) || $page < 1) {
        return false;
    }

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
    ];

    // Add category filter if specified
    if (!empty($category)) {
        $args['cat'] = $category;
    }

    // Add taxonomy filter if specified
    if (!empty($taxonomy) && !empty($term)) {
        $args['tax_query'] = [
            [
                'taxonomy' => $taxonomy,
                'field' => 'term_taxonomy_id',
                'terms' => $term,
            ],
        ];
    }

    $cache_key = 'fyp_infinite_posts_query_' . md5(serialize($args));
    $query = wp_cache_get($cache_key);

    if (false === $query) {
        // Create a new WP_Query instance
        $query = new WP_Query($args);
        wp_cache_set($cache_key, $query, '', HOUR_IN_SECONDS);
    }

    ob_start();

    if ($query->have_posts()) {
        if (file_exists(get_stylesheet_directory() . '/fyplugins/infinite-posts/' . $post_type . '-item.php')) {
            // Use the child theme's template if it exists
            $template = get_stylesheet_directory() . '/fyplugins/infinite-posts/' . $post_type . '-item.php';
        } elseif (file_exists(get_template_directory() . '/fyplugins/infinite-posts/' . $post_type . '-item.php')) {
            // Use the parent theme's template if it exists
            $template = get_template_directory() . '/fyplugins/infinite-posts/' . $post_type . '-item.php';
        } elseif (file_exists(FYPINPO_PLUGIN_DIR . 'templates/' . $post_type . '-item.php')) {
            // Use the plugin's template if it exists
            $template = FYPINPO_PLUGIN_DIR . 'templates/' . $post_type . '-item.php';
        } else {
            // Fallback to a default template
            $template = FYPINPO_PLUGIN_DIR . 'templates/default-item.php';
        }

        while ($query->have_posts()) {
            $query->the_post();
            include($template);
        }
    }

    wp_reset_postdata();

    return ob_get_clean();
}

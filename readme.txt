=== FYP Infinite Posts ===
Contributors: francoisyerg
Donate link: https://buymeacoffee.com/francoisyerg
Tags: infinite scroll, ajax, posts, load more, pagination
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds infinite scroll to your posts with AJAX. Seamless, lightweight, and easy to use. Enhance user experience on any theme.

== Description ==

FYP Infinite Posts is a lightweight and flexible WordPress plugin designed to enhance your website's user experience by enabling infinite scrolling for your posts. With this plugin, new posts are automatically loaded as users scroll down the page, eliminating the need for traditional pagination and keeping visitors engaged for longer periods.

This plugin is easy to set up and integrates seamlessly with any WordPress theme. It uses AJAX to fetch and display additional posts without reloading the page, ensuring fast and smooth content delivery. FYP Infinite Posts is ideal for blogs, news sites, portfolios, or any website that displays a list of posts and wants to offer a modern, uninterrupted browsing experience.

**Key Features:**

- Infinite scroll for posts: Automatically loads more posts as users reach the bottom of the page.
- AJAX-powered loading: Fetches new content without page reloads for a seamless experience.
- Easy integration: Works out-of-the-box with most WordPress themes.
- Customizable settings: Configure how and when new posts are loaded.
- Lightweight and optimized: Minimal impact on site performance.
- Mobile-friendly: Fully responsive and works on all devices.
- Easy theming: Allows you to create custom templates for each post type to match your site's design.

== Usage ==

To enable infinite scroll on your site, simply add the `[fyplugins_infinite_posts]` shortcode to any page or post where you want infinite scrolling functionality.

== Shortcode Parameters ==

The `[fyplugins_infinite_posts]` shortcode supports the following parameters to customize its behavior:

- `pagination` (string): Type of pagination to use Accept 'scroll', 'button' or 'none'. Default is `scroll`.
- `posts_per_page` (integer): Number of posts to load per batch. Default is `10`.
- `offset` (integer): Number of posts to skip for the first batch. Default is `0`.
- `post_type` (string): The post type to display (e.g., `post`, `page`, or any custom post type). Default is `post`.
- `category` (string or bool): If string, limit posts to a specific category slug. If true, the shortcode will detect the current category page. If false, categories will be ignored. Default is true.
- `taxonomy` (string or bool): If string, limit posts to a specific taxonomy slug. If true, the shortcode will detect the current taxonomy page. If false, taxonomies will be ignored. Default is true.
- `order` (string): Sort order of posts. Accepts `ASC` or `DESC`. Default is `DESC`.
- `orderby` (string): Field to sort posts by (e.g., `date`, `title`, `rand`). Default is `date`.
- `btn_text` (string): Text for the "Load More" button (if enabled). Default is `Load More`.
- `end_message` (string): Text to display when all posts are already loaded. Default is `No more posts to load.`.
- `class` (string): Additional CSS class for the wrapper. Default is empty.

`[fyplugins_infinite_posts pagination='scroll' posts_per_page='10' offset='0' post_type='post' category='true' order='date' orderby='DESC' btn_text='Load More' end_message='No more posts to load.' class='']`

== Customizing Post Templates ==

FYP Infinite Posts allows you to customize how each type of post is displayed by overriding the default template. This gives you full control over the appearance of posts loaded via infinite scroll.

**How to override the post template:**

1. In your theme or child theme, create a folder '/fyplugins/infinite-posts/'.
2. Copy/paste the file 'wp-content/plugins/fyp-infinite-posts/templates/post-item.php' or create a file named {post type}-item.php (e.g., `post-item.php` for blog posts) in 'wp-content/{your-theme}/fyplugins/infinite-posts/'.
3. Edit this file to match your desired item layout and design.

The plugin will automatically use your custom template if it exists in your theme. This allows you to seamlessly integrate infinite scroll posts with your website's unique style.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/fyp-infinite-posts` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Include the shortcode [fyplugins_infinite_posts] in your pages / posts

== Frequently Asked Questions ==

= Does this plugin work with any theme? =
Yes, FYP Infinite Posts is designed to work with most WordPress themes out of the box. If you encounter layout issues, you may need to adjust your theme's CSS or use the plugin's template customization options.

= Can I customize how posts are displayed? =
Absolutely! You can override the default post template by copying the plugin's template file into your theme and modifying it to match your site's design.

= Is AJAX loading supported? =
Yes, the plugin uses AJAX to load additional posts without reloading the page, providing a smooth and seamless user experience.

= Will this plugin affect my site's performance? =
FYP Infinite Posts is lightweight and optimized for performance. It only loads additional posts when needed, minimizing the impact on your site's speed.

== Screenshots ==

1. The plugin's infinite scroll in action on a blog page, automatically loading more posts as the user scrolls down.
2. Example of integration using custom template file and CSS.

== Changelog ==

= 1.0.2 =
* Added: Request caching for better performances.
* Added: Taxonomies support.
* Addes: `taxonomy` parameter to the shortcode.
* Fixed: Category not always working.

= 1.0.1 =
* Added: `class` parameter to the shortcode.

= 1.0.0 =
* Initial release.

== Upcoming Improvements (TODO) ==

- Add customizable spinners.
- Create Gutenberg block.
- Implement admin page
<?php
/**
 * Plugin Name: Hello World Alert
 * Description: Displays a browser alert saying "hello world" when the homepage is loaded.
 * Version: 1.0
 * Author: Codex
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Output JavaScript alert on the front page.
 */
function hwa_display_alert() {
    if ( is_front_page() ) {
        echo "<script type='text/javascript'>alert('hello world');</script>";
    }
}

add_action( 'wp_footer', 'hwa_display_alert' );

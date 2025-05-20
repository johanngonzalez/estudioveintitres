<?php
/**
 * Plugin Name: PagueloFacil Payment Gateway
 * Description: Integrates PagueloFacil payments via API. Provides a shortcode to create payment buttons.
 * Version: 1.0.0
 * Author: Codex
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register plugin settings
 */
function pf_register_settings() {
    register_setting( 'paguelofacil_options', 'paguelofacil_user' );
    register_setting( 'paguelofacil_options', 'paguelofacil_token' );
    register_setting( 'paguelofacil_options', 'paguelofacil_success_url' );
    register_setting( 'paguelofacil_options', 'paguelofacil_error_url' );
    register_setting( 'paguelofacil_options', 'paguelofacil_environment' );
}
add_action( 'admin_init', 'pf_register_settings' );

/**
 * Add settings page
 */
function pf_add_settings_page() {
    add_options_page(
        'PagueloFacil Settings',
        'PagueloFacil',
        'manage_options',
        'paguelofacil',
        'pf_render_settings_page'
    );
}
add_action( 'admin_menu', 'pf_add_settings_page' );

/**
 * Render settings page
 */
function pf_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'PagueloFacil Settings', 'paguelofacil' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'paguelofacil_options' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="paguelofacil_user"><?php esc_html_e( 'User', 'paguelofacil' ); ?></label></th>
                    <td><input name="paguelofacil_user" type="text" id="paguelofacil_user" value="<?php echo esc_attr( get_option( 'paguelofacil_user' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="paguelofacil_token"><?php esc_html_e( 'Token', 'paguelofacil' ); ?></label></th>
                    <td><input name="paguelofacil_token" type="text" id="paguelofacil_token" value="<?php echo esc_attr( get_option( 'paguelofacil_token' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="paguelofacil_environment"><?php esc_html_e( 'Environment', 'paguelofacil' ); ?></label></th>
                    <td>
                        <select name="paguelofacil_environment" id="paguelofacil_environment">
                            <option value="production" <?php selected( get_option( 'paguelofacil_environment', 'production' ), 'production' ); ?>>Production</option>
                            <option value="sandbox" <?php selected( get_option( 'paguelofacil_environment', 'production' ), 'sandbox' ); ?>>Sandbox</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="paguelofacil_success_url"><?php esc_html_e( 'Success URL', 'paguelofacil' ); ?></label></th>
                    <td><input name="paguelofacil_success_url" type="url" id="paguelofacil_success_url" value="<?php echo esc_attr( get_option( 'paguelofacil_success_url' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="paguelofacil_error_url"><?php esc_html_e( 'Error URL', 'paguelofacil' ); ?></label></th>
                    <td><input name="paguelofacil_error_url" type="url" id="paguelofacil_error_url" value="<?php echo esc_attr( get_option( 'paguelofacil_error_url' ) ); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Generate payment button shortcode
 * Usage: [paguelofacil amount="10.00" description="Test" invoice="INV001"]
 */
function pf_payment_button_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'amount'      => '',
        'description' => '',
        'invoice'     => '',
        'label'       => __( 'Pay with PagueloFacil', 'paguelofacil' ),
    ), $atts, 'paguelofacil' );

    if ( empty( $atts['amount'] ) || empty( $atts['description'] ) ) {
        return '';
    }

    $data_attrs = array(
        'data-amount'      => esc_attr( $atts['amount'] ),
        'data-description' => esc_attr( $atts['description'] ),
        'data-invoice'     => esc_attr( $atts['invoice'] ),
    );
    $attr_string = '';
    foreach ( $data_attrs as $key => $value ) {
        if ( $value ) {
            $attr_string .= $key . '="' . $value . '" ';
        }
    }

    return '<button class="pf-payment-button" ' . trim( $attr_string ) . '>' . esc_html( $atts['label'] ) . '</button>';
}
add_shortcode( 'paguelofacil', 'pf_payment_button_shortcode' );

/**
 * Enqueue script to handle button click
 */
function pf_enqueue_scripts() {
    if ( ! is_singular() ) {
        return;
    }
    wp_enqueue_script( 'pf-script', plugin_dir_url( __FILE__ ) . 'pf-script.js', array( 'jquery' ), '1.0', true );
    wp_localize_script( 'pf-script', 'pfParams', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pf_create_payment' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'pf_enqueue_scripts' );

/**
 * AJAX handler to create payment link via API
 */
}
function pf_create_payment_link() {
    check_ajax_referer( 'pf_create_payment', 'nonce' );
    if ( ! isset( $_POST['amount'], $_POST['description'] ) ) {
        wp_send_json_error( 'Missing parameters' );
    }

    $user         = get_option( 'paguelofacil_user' );
    $token        = get_option( 'paguelofacil_token' );
    $success_url  = get_option( 'paguelofacil_success_url' );
    $error_url    = get_option( 'paguelofacil_error_url' );
    $environment  = get_option( 'paguelofacil_environment', 'production' );
    $api_base     = ( 'sandbox' === $environment ) ? 'https://sandbox.paguelofacil.com' : 'https://api.paguelofacil.com';
    if ( empty( $user ) || empty( $token ) ) {
        wp_send_json_error( 'Gateway not configured' );
    }

    $request_body = array(
        'user'        => $user,
        'token'       => $token,
        'amount'      => sanitize_text_field( wp_unslash( $_POST['amount'] ) ),
        'description' => sanitize_text_field( wp_unslash( $_POST['description'] ) ),
        'invoice'     => isset( $_POST['invoice'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice'] ) ) : '',
        'url_ok'      => $success_url,
        'url_error'   => $error_url,
    );

    $response = wp_remote_post( $api_base . '/payment/link', array(
        'body'    => $request_body,
        'timeout' => 20,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( empty( $data['url'] ) ) {
        wp_send_json_error( 'Invalid response' );
    }

    wp_send_json_success( array( 'url' => $data['url'] ) );
}
add_action( 'wp_ajax_pf_create_payment_link', 'pf_create_payment_link' );
add_action( 'wp_ajax_nopriv_pf_create_payment_link', 'pf_create_payment_link' );

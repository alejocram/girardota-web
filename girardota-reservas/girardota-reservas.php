<?php
/**
 * Plugin Name: Girardota Reservas
 * Description: Reservas de alojamientos y tours con WooCommerce, agenda de eventos y guía de restaurantes y cafés para Girardota.com.co.
 * Version: 1.0.0
 * Author: Girardota.com.co
 * Text Domain: girardota-reservas
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GT_RES_FILE', __FILE__ );
define( 'GT_RES_DIR', plugin_dir_path( __FILE__ ) );
define( 'GT_RES_URL', plugin_dir_url( __FILE__ ) );
define( 'GT_RES_SETUP_VERSION', '1' );

require_once GT_RES_DIR . 'includes/helpers.php';
require_once GT_RES_DIR . 'includes/post-types.php';
require_once GT_RES_DIR . 'includes/booking.php';
require_once GT_RES_DIR . 'includes/shortcodes.php';
require_once GT_RES_DIR . 'includes/setup.php';

register_activation_hook( __FILE__, 'gt_res_activate' );

function gt_res_activate() {
	gt_register_post_types();
	update_option( 'gt_res_needs_setup', 1 );
	if ( class_exists( 'WooCommerce' ) ) {
		gt_res_run_setup();
	}
	flush_rewrite_rules();
}

/* Fallback: si la activación no pudo completar la configuración, se termina en la siguiente carga. */
add_action( 'init', function () {
	if ( get_option( 'gt_res_needs_setup' ) && class_exists( 'WooCommerce' ) && get_option( 'gt_res_setup_done' ) !== GT_RES_SETUP_VERSION ) {
		gt_res_run_setup();
	}
}, 99 );

/* Ajustes posteriores a la instalación (se aplican una vez por versión). */
add_action( 'init', function () {
	if ( (int) get_option( 'gt_res_patch', 0 ) >= 2 ) {
		return;
	}
	update_option( 'woocommerce_checkout_phone_field', 'required' );
	update_option( 'woocommerce_checkout_company_field', 'hidden' );
	update_option( 'woocommerce_checkout_address_2_field', 'hidden' );
	update_option( 'woocommerce_checkout_privacy_policy_text', 'Tus datos personales se usarán para gestionar tu reserva, acompañarte durante tu experiencia en este sitio y para los fines descritos en nuestra [privacy_policy].' );
	update_option( 'woocommerce_registration_privacy_policy_text', 'Tus datos personales se usarán para gestionar tu cuenta y tus reservas, y para los fines descritos en nuestra [privacy_policy].' );
	update_option( 'woocommerce_checkout_terms_and_conditions_checkbox_text', 'He leído y acepto las [terms]' );
	update_option( 'gt_res_patch', 2 );
	do_action( 'litespeed_purge_all' );
}, 100 );

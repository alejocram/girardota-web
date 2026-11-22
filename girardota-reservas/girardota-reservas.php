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

/* v3: la sección de contacto pasa al final de "Nosotros" y se elimina la página "Contacto". */
add_action( 'init', function () {
	if ( (int) get_option( 'gt_res_patch', 0 ) >= 3 || ! function_exists( 'gt_contact_section_html' ) ) {
		return;
	}
	kses_remove_filters(); // Conserva el HTML de las secciones al guardar desde una visita anónima.
	$nos = get_page_by_path( 'nosotros' );
	if ( $nos ) {
		$c = $nos->post_content;
		$c = str_replace( 'href="/contacto/"', 'href="#contacto"', $c );
		if ( false === strpos( $c, 'id="contacto"' ) ) {
			$c .= gt_blocks( array( gt_contact_section_html() ) );
		}
		wp_update_post( array( 'ID' => $nos->ID, 'post_content' => $c ) );
	}
	// Enlaces a la página antigua en las demás páginas.
	foreach ( array( 'terminos-y-condiciones', 'politica-de-privacidad', 'politicas-de-reserva' ) as $slug ) {
		$pg = get_page_by_path( $slug );
		if ( $pg && false !== strpos( $pg->post_content, '/contacto/' ) ) {
			wp_update_post( array( 'ID' => $pg->ID, 'post_content' => str_replace( 'href="/contacto/"', 'href="/nosotros/#contacto"', $pg->post_content ) ) );
		}
	}
	$con = get_page_by_path( 'contacto' );
	if ( $con ) {
		// Menú principal: el enlace "Contacto" pasa a ser "Nosotros".
		foreach ( wp_get_nav_menus() as $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				if ( (int) $item->object_id === (int) $con->ID && 'page' === $item->object ) {
					wp_update_nav_menu_item( $menu->term_id, $item->ID, array(
						'menu-item-title'     => 'Nosotros',
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $nos ? $nos->ID : 0,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $item->menu_order,
					) );
				}
			}
		}
		wp_trash_post( $con->ID );
	}
	update_option( 'gt_res_patch', 3 );
	do_action( 'litespeed_purge_all' );
}, 101 );

/* La dirección antigua /contacto/ lleva a la sección de contacto en Nosotros. */
add_action( 'template_redirect', function () {
	if ( is_404() && 'contacto' === trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ) ) {
		wp_safe_redirect( home_url( '/nosotros/#contacto' ), 301 );
		exit;
	}
} );

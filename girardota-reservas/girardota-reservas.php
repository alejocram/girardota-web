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
require_once GT_RES_DIR . 'includes/phone-codes.php';
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

/* v4: reemplaza girardota@gmail.com por info@girardota.com.co en opciones, contenido, metadatos y usuarios. */
function gt_replace_deep( $value, $from, $to ) {
	if ( is_string( $value ) ) {
		return str_ireplace( $from, $to, $value );
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = gt_replace_deep( $v, $from, $to );
		}
	} elseif ( is_object( $value ) && ! ( $value instanceof __PHP_Incomplete_Class ) ) {
		foreach ( get_object_vars( $value ) as $k => $v ) {
			$value->$k = gt_replace_deep( $v, $from, $to );
		}
	}
	return $value;
}

add_action( 'init', function () {
	if ( (int) get_option( 'gt_res_patch', 0 ) >= 4 ) {
		return;
	}
	global $wpdb;
	$from = 'girardota@gmail.com';
	$to   = 'info@girardota.com.co';
	$like = '%' . $wpdb->esc_like( $from ) . '%';
	$log  = array();
	kses_remove_filters();

	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s AND option_name NOT LIKE '\\_transient%%' AND option_name NOT LIKE '\\_site\\_transient%%'", $like ) ) as $name ) {
		update_option( $name, gt_replace_deep( get_option( $name ), $from, $to ) );
		$log[] = "opción: $name";
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_type FROM {$wpdb->posts} WHERE post_content LIKE %s OR post_excerpt LIKE %s OR post_title LIKE %s", $like, $like, $like ) ) as $p ) {
		$post = get_post( $p->ID );
		$wpdb->update( $wpdb->posts, array(
			'post_content' => str_ireplace( $from, $to, $post->post_content ),
			'post_excerpt' => str_ireplace( $from, $to, $post->post_excerpt ),
			'post_title'   => str_ireplace( $from, $to, $post->post_title ),
		), array( 'ID' => $p->ID ) );
		clean_post_cache( $p->ID );
		$log[] = "contenido: {$p->post_type} #{$p->ID} ({$post->post_title})";
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_value LIKE %s", $like ) ) as $m ) {
		foreach ( get_post_meta( $m->post_id, $m->meta_key ) as $old ) {
			update_post_meta( $m->post_id, $m->meta_key, gt_replace_deep( $old, $from, $to ), $old );
		}
		$log[] = "metadato: #{$m->post_id} {$m->meta_key}";
	}
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s", $like ) ) as $uid ) {
		if ( ! email_exists( $to ) ) {
			wp_update_user( array( 'ID' => $uid, 'user_email' => $to ) );
			$log[] = "usuario: #$uid (correo de la cuenta)";
		} else {
			$log[] = "usuario: #$uid NO cambiado (ya existe otra cuenta con $to)";
		}
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_value LIKE %s", $like ) ) as $m ) {
		update_user_meta( $m->user_id, $m->meta_key, gt_replace_deep( get_user_meta( $m->user_id, $m->meta_key, true ), $from, $to ) );
		$log[] = "metadato de usuario: #{$m->user_id} {$m->meta_key}";
	}
	// Tablas propias de WooCommerce (pedidos HPOS), si existen.
	foreach ( array( 'wc_orders' => array( 'billing_email' ), 'wc_order_addresses' => array( 'email' ) ) as $t => $cols ) {
		$table = $wpdb->prefix . $t;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			continue;
		}
		foreach ( $cols as $c ) {
			$n = $wpdb->query( $wpdb->prepare( "UPDATE $table SET $c = REPLACE($c, %s, %s) WHERE $c LIKE %s", $from, $to, $like ) ); // phpcs:ignore
			if ( $n ) {
				$log[] = "pedidos: $table.$c ($n)";
			}
		}
	}
	$log[] = 'correo del administrador ahora: ' . get_option( 'admin_email' );
	$log[] = 'remitente WooCommerce ahora: ' . get_option( 'woocommerce_email_from_address' );
	$no = get_option( 'woocommerce_new_order_settings' );
	$log[] = 'destinatario "nuevo pedido" ahora: ' . ( is_array( $no ) && ! empty( $no['recipient'] ) ? $no['recipient'] : '(correo del administrador)' );
	@file_put_contents( WP_CONTENT_DIR . '/uploads/gt-email-log.txt', gmdate( 'c' ) . "\n" . implode( "\n", $log ) . "\n" ); // phpcs:ignore
	update_option( 'gt_res_patch', 4 );
	wp_cache_flush();
	do_action( 'litespeed_purge_all' );
}, 102 );

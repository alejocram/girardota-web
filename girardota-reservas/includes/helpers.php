<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Número de WhatsApp de reservas (formato internacional sin +). Editable en Ajustes > Generales. */
function gt_whatsapp_number() {
	$n = get_option( 'gt_whatsapp', '573007863624' );
	return preg_replace( '/\D/', '', $n );
}

function gt_whatsapp_display() {
	return get_option( 'gt_whatsapp_display', '+57 300 786 3624' );
}

function gt_wa_url( $message = '' ) {
	$url = 'https://wa.me/' . gt_whatsapp_number();
	if ( $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

function gt_cop( $amount ) {
	return '$' . number_format( (float) $amount, 0, ',', '.' ) . ' COP';
}

/** Campo extra en Ajustes > Generales para cambiar el WhatsApp sin tocar código. */
add_action( 'admin_init', function () {
	register_setting( 'general', 'gt_whatsapp', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'general', 'gt_whatsapp_display', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	add_settings_field( 'gt_whatsapp', 'WhatsApp de reservas (solo números, ej. 573007863624)', function () {
		echo '<input type="text" class="regular-text" name="gt_whatsapp" value="' . esc_attr( get_option( 'gt_whatsapp', '573007863624' ) ) . '">';
	}, 'general' );
	add_settings_field( 'gt_whatsapp_display', 'WhatsApp de reservas (como se muestra)', function () {
		echo '<input type="text" class="regular-text" name="gt_whatsapp_display" value="' . esc_attr( get_option( 'gt_whatsapp_display', '+57 300 786 3624' ) ) . '">';
	}, 'general' );
} );

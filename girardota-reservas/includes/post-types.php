<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gt_register_post_types' );

function gt_register_post_types() {
	register_post_type( 'evento', array(
		'labels'        => array(
			'name'          => 'Eventos',
			'singular_name' => 'Evento',
			'add_new'       => 'Añadir evento',
			'add_new_item'  => 'Añadir nuevo evento',
			'edit_item'     => 'Editar evento',
			'all_items'     => 'Todos los eventos',
			'menu_name'     => 'Agenda de eventos',
		),
		'public'        => true,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'evento' ),
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest'  => true,
	) );

	register_post_type( 'lugar', array(
		'labels'        => array(
			'name'          => 'Restaurantes y cafés',
			'singular_name' => 'Lugar',
			'add_new'       => 'Añadir lugar',
			'add_new_item'  => 'Añadir restaurante o café',
			'edit_item'     => 'Editar lugar',
			'all_items'     => 'Todos los lugares',
			'menu_name'     => 'Restaurantes y cafés',
		),
		'public'        => true,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'lugar' ),
		'menu_icon'     => 'dashicons-coffee',
		'menu_position' => 22,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		'show_in_rest'  => true,
	) );

	register_taxonomy( 'tipo_lugar', 'lugar', array(
		'labels'            => array( 'name' => 'Tipos de lugar', 'singular_name' => 'Tipo de lugar' ),
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'tipo-lugar' ),
		'show_in_rest'      => true,
	) );
}

/* ---------- Campos personalizados (metabox simple, sin dependencias) ---------- */

function gt_meta_fields() {
	return array(
		'evento' => array(
			'_gt_fecha_texto' => 'Fecha (texto visible, ej. "Enero 2027")',
			'_gt_fecha_orden' => 'Fecha para ordenar (AAAA-MM-DD)',
			'_gt_lugar'       => 'Lugar',
			'_gt_en_curso'    => 'En curso (escribe "si" para destacarlo)',
		),
		'lugar'  => array(
			'_gt_etiquetas'  => 'Especialidad (ej. "Trucha · Plan campestre")',
			'_gt_direccion'  => 'Dirección',
			'_gt_whatsapp'   => 'WhatsApp (solo números, ej. 573001234567)',
			'_gt_instagram'  => 'URL de Instagram',
		),
		'product' => array(
			'_gt_tipo'        => 'Tipo de reserva: "alojamiento" o "tour"',
			'_gt_subtipo'     => 'Subtipo visible (Glamping, Cabaña, Ecohotel, Finca, Café, Aventura, Miel…)',
			'_gt_capacidad'   => 'Capacidad máxima (personas)',
			'_gt_incluye'     => 'Incluye',
			'_gt_horario'     => 'Horario / check-in y check-out',
			'_gt_horarios'    => 'Horarios de salida (tours, separados por coma, ej. "8:00 a.m., 1:00 p.m.")',
			'_gt_dias'        => 'Días de salida (tours; números 0=dom … 6=sáb separados por coma; vacío = todos)',
			'_gt_duracion'    => 'Duración (tours)',
			'_gt_dificultad'  => 'Dificultad (tours)',
			'_gt_cancelacion' => 'Política de cancelación (una línea por punto)',
		),
	);
}

add_action( 'add_meta_boxes', function () {
	foreach ( array_keys( gt_meta_fields() ) as $pt ) {
		add_meta_box( 'gt_detalles', 'Detalles Girardota.com.co', 'gt_render_meta_box', $pt, 'normal', 'high' );
	}
} );

function gt_render_meta_box( $post ) {
	$fields = gt_meta_fields()[ $post->post_type ];
	wp_nonce_field( 'gt_meta', 'gt_meta_nonce' );
	echo '<table class="form-table">';
	foreach ( $fields as $key => $label ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( '_gt_cancelacion' === $key || '_gt_incluye' === $key ) {
			echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input class="regular-text" type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['gt_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gt_meta_nonce'] ) ), 'gt_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = gt_meta_fields();
	if ( empty( $fields[ $post->post_type ] ) ) {
		return;
	}
	foreach ( array_keys( $fields[ $post->post_type ] ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}, 10, 2 );

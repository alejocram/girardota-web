<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tarjeta de alojamiento o tour. */
function gt_card_product( $product ) {
	$id      = $product->get_id();
	$tipo    = gt_product_tipo( $id );
	$subtipo = get_post_meta( $id, '_gt_subtipo', true );
	$cap     = (int) get_post_meta( $id, '_gt_capacidad', true );
	$terms   = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
	$meta    = array();
	if ( 'tour' === $tipo ) {
		if ( $d = get_post_meta( $id, '_gt_duracion', true ) ) {
			$meta[] = $d;
		}
		if ( $d = get_post_meta( $id, '_gt_dificultad', true ) ) {
			$meta[] = $d;
		}
	} else {
		if ( $subtipo ) {
			$meta[] = $subtipo;
		}
		if ( $cap ) {
			$meta[] = 'Hasta ' . $cap . ' personas';
		}
	}
	ob_start();
	?>
	<article class="gt-card" data-cats="<?php echo esc_attr( implode( ' ', (array) $terms ) ); ?>" data-nombre="<?php echo esc_attr( strtolower( remove_accents( $product->get_name() ) ) ); ?>">
		<a class="gt-card__img" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore ?>
			<?php if ( $subtipo ) : ?><span class="gt-chip"><?php echo esc_html( $subtipo ); ?></span><?php endif; ?>
		</a>
		<div class="gt-card__body">
			<?php if ( $meta ) : ?><p class="gt-card__meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></p><?php endif; ?>
			<h3 class="gt-card__title"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
			<p class="gt-card__text"><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ) ); ?></p>
			<div class="gt-card__foot">
				<span class="gt-card__price"><?php echo $product->get_price_html(); // phpcs:ignore ?></span>
				<a class="gt-btn gt-btn--sm" href="<?php echo esc_url( $product->get_permalink() ); ?>">Reservar</a>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function gt_query_products( $tipo, $limit = -1 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'meta_key'       => '_gt_tipo', // phpcs:ignore
		'meta_value'     => $tipo, // phpcs:ignore
		'fields'         => 'ids',
	) );
	return array_filter( array_map( 'wc_get_product', $ids ) );
}

/* [gt_alojamientos filtros="si" limite="-1"] */
add_shortcode( 'gt_alojamientos', function ( $atts ) {
	$a     = shortcode_atts( array( 'filtros' => 'si', 'limite' => -1 ), $atts );
	$items = gt_query_products( 'alojamiento', (int) $a['limite'] );
	return gt_render_grid( $items, 'si' === $a['filtros'] ? array(
		''          => 'Todos',
		'glamping'  => 'Glamping',
		'cabanas'   => 'Cabañas',
		'ecohoteles' => 'Ecohoteles',
		'fincas'    => 'Fincas',
	) : array(), 'alojamiento' );
} );

/* [gt_tours limite="-1"] */
add_shortcode( 'gt_tours', function ( $atts ) {
	$a     = shortcode_atts( array( 'filtros' => 'no', 'limite' => -1 ), $atts );
	$items = gt_query_products( 'tour', (int) $a['limite'] );
	return gt_render_grid( $items, 'si' === $a['filtros'] ? array(
		''               => 'Todos',
		'tour-cafe'      => 'Café',
		'aventura'       => 'Aventura',
		'miel'           => 'Miel',
	) : array(), 'tour' );
} );

function gt_render_grid( $items, $filtros, $tipo ) {
	if ( ! $items ) {
		return '<p class="gt-empty">Pronto publicaremos nuevas opciones. Escríbenos por <a href="' . esc_url( gt_wa_url() ) . '">WhatsApp</a>.</p>';
	}
	ob_start();
	echo '<div class="gt-grid-wrap" data-gt-filter>';
	if ( $filtros ) {
		echo '<div class="gt-toolbar"><input type="search" class="gt-search" placeholder="Buscar ' . ( 'tour' === $tipo ? 'experiencia' : 'alojamiento' ) . '…" aria-label="Buscar"><div class="gt-filters" role="group" aria-label="Filtrar por tipo">';
		$first = true;
		foreach ( $filtros as $slug => $label ) {
			echo '<button type="button" class="gt-filter' . ( $first ? ' is-active' : '' ) . '" data-cat="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</button>';
			$first = false;
		}
		echo '</div></div>';
	}
	echo '<div class="gt-grid">';
	foreach ( $items as $p ) {
		echo gt_card_product( $p ); // phpcs:ignore
	}
	echo '</div><p class="gt-empty gt-empty--filter" hidden>No encontramos resultados con ese filtro.</p></div>';
	return ob_get_clean();
}

/* [gt_eventos limite="-1" estilo="carteles|lista"] */
add_shortcode( 'gt_eventos', function ( $atts ) {
	$a = shortcode_atts( array( 'limite' => -1, 'estilo' => 'carteles' ), $atts );
	$q = new WP_Query( array(
		'post_type'      => 'evento',
		'posts_per_page' => (int) $a['limite'],
		'meta_key'       => '_gt_fecha_orden', // phpcs:ignore
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );
	if ( ! $q->have_posts() ) {
		return '<p class="gt-empty">Pronto publicaremos la agenda.</p>';
	}
	ob_start();
	echo 'lista' === $a['estilo'] ? '<ul class="gt-event-list">' : '<div class="gt-posters">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$id      = get_the_ID();
		$fecha   = get_post_meta( $id, '_gt_fecha_texto', true );
		$lugar   = get_post_meta( $id, '_gt_lugar', true );
		$encurso = 'si' === strtolower( trim( (string) get_post_meta( $id, '_gt_en_curso', true ) ) );
		if ( 'lista' === $a['estilo'] ) {
			echo '<li class="gt-event' . ( $encurso ? ' is-live' : '' ) . '"><a href="' . esc_url( get_permalink() ) . '"><span class="gt-event__date">' . esc_html( $fecha ) . '</span><span class="gt-event__title">' . ( $encurso ? '<em class="gt-live">● En curso</em> ' : '' ) . esc_html( get_the_title() ) . '</span><span class="gt-event__arrow" aria-hidden="true">→</span></a></li>';
		} else {
			echo '<a class="gt-poster' . ( $encurso ? ' is-live' : '' ) . '" href="' . esc_url( get_permalink() ) . '">';
			echo get_the_post_thumbnail( $id, 'large', array( 'loading' => 'lazy' ) ); // phpcs:ignore
			echo '<span class="gt-poster__info">' . ( $encurso ? '<em class="gt-live">● En curso</em>' : '' ) . '<strong>' . esc_html( get_the_title() ) . '</strong><span>' . esc_html( $fecha . ( $lugar ? ' · ' . $lugar : '' ) ) . '</span></span></a>';
		}
	}
	wp_reset_postdata();
	echo 'lista' === $a['estilo'] ? '</ul>' : '</div>';
	return ob_get_clean();
} );

/* [gt_lugares tipo="restaurantes|cafes"] */
add_shortcode( 'gt_lugares', function ( $atts ) {
	$a = shortcode_atts( array( 'tipo' => 'restaurantes' ), $atts );
	$q = new WP_Query( array(
		'post_type'      => 'lugar',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => array( array( 'taxonomy' => 'tipo_lugar', 'field' => 'slug', 'terms' => sanitize_title( $a['tipo'] ) ) ), // phpcs:ignore
		'no_found_rows'  => true,
	) );
	if ( ! $q->have_posts() ) {
		return '';
	}
	ob_start();
	echo '<div class="gt-places">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$id  = get_the_ID();
		$wa  = preg_replace( '/\D/', '', (string) get_post_meta( $id, '_gt_whatsapp', true ) );
		$ig  = get_post_meta( $id, '_gt_instagram', true );
		$dir = get_post_meta( $id, '_gt_direccion', true );
		echo '<article class="gt-place">';
		echo '<p class="gt-place__tags">' . esc_html( get_post_meta( $id, '_gt_etiquetas', true ) ) . '</p>';
		echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
		echo '<p>' . esc_html( wp_strip_all_tags( get_the_excerpt() ) ) . '</p>';
		if ( $dir ) {
			echo '<p class="gt-place__addr"><span aria-hidden="true">📍</span> ' . esc_html( $dir ) . '</p>';
		}
		$links = array();
		if ( $wa ) {
			$links[] = '<a href="https://wa.me/' . esc_attr( $wa ) . '" target="_blank" rel="noopener">WhatsApp →</a>';
		}
		if ( $ig ) {
			$links[] = '<a href="' . esc_url( $ig ) . '" target="_blank" rel="noopener">Instagram →</a>';
		}
		if ( $links ) {
			echo '<p class="gt-place__links">' . implode( ' · ', $links ) . '</p>'; // phpcs:ignore
		}
		echo '</article>';
	}
	wp_reset_postdata();
	echo '</div>';
	return ob_get_clean();
} );

/* [gt_whatsapp texto="Reservar por WhatsApp" mensaje="Hola…" clase=""] */
add_shortcode( 'gt_whatsapp', function ( $atts ) {
	$a = shortcode_atts( array( 'texto' => 'Escríbenos por WhatsApp', 'mensaje' => 'Hola, vengo de Girardota.com.co', 'clase' => 'gt-btn gt-btn--wa' ), $atts );
	return '<a class="' . esc_attr( $a['clase'] ) . '" href="' . esc_url( gt_wa_url( $a['mensaje'] ) ) . '" target="_blank" rel="noopener">' . esc_html( $a['texto'] ) . '</a>';
} );

/* [gt_whatsapp_numero] */
add_shortcode( 'gt_whatsapp_numero', function () {
	return esc_html( gt_whatsapp_display() );
} );

/* [gt_contacto] Formulario de contacto que envía un correo al administrador. */
add_shortcode( 'gt_contacto', function () {
	$estado = isset( $_GET['contacto'] ) ? sanitize_key( $_GET['contacto'] ) : ''; // phpcs:ignore
	ob_start();
	if ( 'ok' === $estado ) {
		echo '<div class="gt-alert gt-alert--ok">¡Gracias! Recibimos tu mensaje y te responderemos pronto.</div>';
	} elseif ( 'error' === $estado ) {
		echo '<div class="gt-alert gt-alert--error">No pudimos enviar tu mensaje. Revisa los campos o escríbenos por WhatsApp.</div>';
	} elseif ( 'telefono' === $estado ) {
		echo '<div class="gt-alert gt-alert--error">Revisa el celular: elige el indicativo del país y escribe el número local con 7 a 12 dígitos, sin espacios ni indicativo.</div>';
	}
	?>
	<form class="gt-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="gt_contacto">
		<?php wp_nonce_field( 'gt_contacto', 'gt_nonce' ); ?>
		<p class="gt-hp" aria-hidden="true"><label>No llenar<input type="text" name="gt_web" tabindex="-1" autocomplete="off"></label></p>
		<div class="gt-form__row">
			<label>Nombre<input type="text" name="gt_nombre" required maxlength="100"></label>
			<label>Correo electrónico<input type="email" name="gt_email" required maxlength="150"></label>
		</div>
		<fieldset class="gt-phone">
			<legend>Celular / WhatsApp</legend>
			<div class="gt-phone__fields">
				<label><span class="screen-reader-text">Indicativo del país</span>
					<select name="gt_tel_pais" autocomplete="tel-country-code">
						<?php foreach ( gt_country_codes() as $iso => $c ) : ?>
							<option value="<?php echo esc_attr( $iso ); ?>" <?php selected( $iso, 'CO' ); ?>>+<?php echo esc_html( $c[1] . ' ' . $c[0] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><span class="screen-reader-text">Número de celular sin indicativo</span>
					<input type="tel" name="gt_tel" inputmode="numeric" autocomplete="tel-national" pattern="[0-9]{7,12}" minlength="7" placeholder="3001234567" title="Escribe de 7 a 12 dígitos, sin espacios ni indicativo." oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)">
				</label>
			</div>
			<small class="gt-phone__hint">Número local de 7 a 12 dígitos, sin espacios ni indicativo.</small>
		</fieldset>
		<label>Motivo
			<select name="gt_motivo">
				<option>Quiero ser aliado (alojamiento, tour o restaurante)</option>
				<option>Publicar un evento</option>
				<option>Otro</option>
			</select>
		</label>
		<label>Mensaje<textarea name="gt_mensaje" rows="5" required maxlength="3000"></textarea></label>
		<label class="gt-check"><input type="checkbox" name="gt_datos" value="1" required> <span>Autorizo el tratamiento de mis datos personales según la <a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>">política de privacidad</a>.</span></label>
		<button type="submit" class="gt-btn">Enviar mensaje</button>
	</form>
	<?php
	return ob_get_clean();
} );

/**
 * Arma el número en formato E.164 (+[indicativo][número], máximo 15 dígitos).
 * Devuelve '' si no se escribió número y false si no es válido.
 */
function gt_phone_e164( $iso, $local ) {
	$local = preg_replace( '/\D/', '', (string) $local );
	if ( '' === $local ) {
		return '';
	}
	$codes = gt_country_codes();
	$iso   = strtoupper( sanitize_key( $iso ) );
	if ( ! isset( $codes[ $iso ] ) ) {
		return false;
	}
	$len = strlen( $local );
	if ( $len < 7 || $len > 12 ) {
		return false;
	}
	$e164 = $codes[ $iso ][1] . $local;
	return strlen( $e164 ) <= 15 ? '+' . $e164 : false;
}

function gt_handle_contacto() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/nosotros/' );
	$back = remove_query_arg( 'contacto', strtok( $back, '#' ) );
	if ( ! isset( $_POST['gt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gt_nonce'] ) ), 'gt_contacto' ) || ! empty( $_POST['gt_web'] ) ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'error', $back ) . '#contacto' );
		exit;
	}
	$nombre  = sanitize_text_field( wp_unslash( $_POST['gt_nombre'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['gt_email'] ?? '' ) );
	$tel     = gt_phone_e164( wp_unslash( $_POST['gt_tel_pais'] ?? 'CO' ), wp_unslash( $_POST['gt_tel'] ?? '' ) );
	$motivo  = sanitize_text_field( wp_unslash( $_POST['gt_motivo'] ?? '' ) );
	$mensaje = sanitize_textarea_field( wp_unslash( $_POST['gt_mensaje'] ?? '' ) );
	if ( ! $nombre || ! is_email( $email ) || ! $mensaje || empty( $_POST['gt_datos'] ) ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'error', $back ) . '#contacto' );
		exit;
	}
	if ( false === $tel ) {
		wp_safe_redirect( add_query_arg( 'contacto', 'telefono', $back ) . '#contacto' );
		exit;
	}
	$cel  = $tel ? $tel . ' (WhatsApp: https://wa.me/' . ltrim( $tel, '+' ) . ')' : 'No indicado';
	$body = "Nombre: $nombre\nCorreo: $email\nCelular: $cel\nMotivo: $motivo\n\n$mensaje";
	$ok   = wp_mail( get_option( 'admin_email' ), '[Girardota.com.co] ' . $motivo . ' — ' . $nombre, $body, array( 'Reply-To: ' . $nombre . ' <' . $email . '>' ) );
	wp_safe_redirect( add_query_arg( 'contacto', $ok ? 'ok' : 'error', $back ) . '#contacto' );
	exit;
}
add_action( 'admin_post_nopriv_gt_contacto', 'gt_handle_contacto' );
add_action( 'admin_post_gt_contacto', 'gt_handle_contacto' );

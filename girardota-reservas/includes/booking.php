<?php
/**
 * Reservas con WooCommerce: alojamientos (por noche) y tours (por persona).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gt_product_tipo( $product_id ) {
	return get_post_meta( $product_id, '_gt_tipo', true );
}

function gt_fecha_larga( $ymd ) {
	$ts = strtotime( $ymd . ' 12:00:00' );
	return $ts ? date_i18n( 'l j \d\e F \d\e Y', $ts ) : $ymd;
}

function gt_hoy() {
	return current_time( 'Y-m-d' );
}

function gt_valid_date( $d ) {
	$dt = DateTime::createFromFormat( 'Y-m-d', $d );
	return $dt && $dt->format( 'Y-m-d' ) === $d;
}

function gt_dias_nombres() {
	return array( 'domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado' );
}

/* ---------- Formulario en la página del producto ---------- */

add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$id   = $product->get_id();
	$tipo = gt_product_tipo( $id );
	$hoy  = gt_hoy();
	$cap  = max( 1, (int) get_post_meta( $id, '_gt_capacidad', true ) );

	if ( 'alojamiento' === $tipo ) {
		$man = gmdate( 'Y-m-d', strtotime( $hoy . ' +1 day' ) );
		?>
		<div class="gt-booking" data-tipo="alojamiento">
			<div class="gt-booking__row">
				<label>Llegada<input type="date" name="gt_llegada" min="<?php echo esc_attr( $hoy ); ?>" required></label>
				<label>Salida<input type="date" name="gt_salida" min="<?php echo esc_attr( $man ); ?>" required></label>
			</div>
			<label>Huéspedes
				<select name="gt_huespedes">
					<?php for ( $i = 1; $i <= $cap; $i++ ) : ?>
						<option value="<?php echo (int) $i; ?>" <?php selected( $i, min( 2, $cap ) ); ?>><?php echo (int) $i . ( 1 === $i ? ' persona' : ' personas' ); ?></option>
					<?php endfor; ?>
				</select>
			</label>
			<p class="gt-booking__total" data-precio="<?php echo esc_attr( $product->get_price() ); ?>" aria-live="polite"></p>
		</div>
		<?php
	} elseif ( 'tour' === $tipo ) {
		$horarios = array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, '_gt_horarios', true ) ) ) );
		$dias     = trim( (string) get_post_meta( $id, '_gt_dias', true ) );
		?>
		<div class="gt-booking" data-tipo="tour" data-dias="<?php echo esc_attr( $dias ); ?>">
			<div class="gt-booking__row">
				<label>Fecha<input type="date" name="gt_fecha" min="<?php echo esc_attr( $hoy ); ?>" required></label>
				<?php if ( $horarios ) : ?>
					<label>Horario
						<select name="gt_hora">
							<?php foreach ( $horarios as $h ) : ?>
								<option value="<?php echo esc_attr( $h ); ?>"><?php echo esc_html( $h ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $dias ) : ?>
				<p class="gt-booking__hint">Salidas solo los <?php echo esc_html( gt_dias_texto( $dias ) ); ?>.</p>
			<?php endif; ?>
			<p class="gt-booking__hint">Máximo <?php echo (int) $cap; ?> personas por reserva.</p>
			<p class="gt-booking__total" data-precio="<?php echo esc_attr( $product->get_price() ); ?>" aria-live="polite"></p>
		</div>
		<?php
	}
} );

function gt_dias_texto( $dias ) {
	$n   = gt_dias_nombres();
	$out = array();
	foreach ( array_map( 'intval', explode( ',', $dias ) ) as $d ) {
		if ( isset( $n[ $d ] ) ) {
			$out[] = 's' === substr( $n[ $d ], -1 ) ? $n[ $d ] : $n[ $d ] . 's';
		}
	}
	if ( count( $out ) > 1 ) {
		$last = array_pop( $out );
		return implode( ', ', $out ) . ' y ' . $last;
	}
	return implode( '', $out );
}

/* Etiqueta "Personas" junto a la cantidad en los tours */
add_action( 'woocommerce_before_add_to_cart_quantity', function () {
	global $product;
	if ( $product && 'tour' === gt_product_tipo( $product->get_id() ) ) {
		echo '<label class="gt-qty-label" for="quantity_' . esc_attr( $product->get_id() ) . '">Personas</label>';
	}
} );

add_filter( 'woocommerce_quantity_input_args', function ( $args, $product ) {
	if ( $product && 'tour' === gt_product_tipo( $product->get_id() ) ) {
		$cap = (int) get_post_meta( $product->get_id(), '_gt_capacidad', true );
		if ( $cap > 0 ) {
			$args['max_value'] = $cap;
		}
		$args['input_id'] = 'quantity_' . $product->get_id();
	}
	return $args;
}, 10, 2 );

/* ---------- Validación ---------- */

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $qty ) {
	$tipo = gt_product_tipo( $product_id );
	$hoy  = gt_hoy();

	if ( 'alojamiento' === $tipo ) {
		$in  = isset( $_POST['gt_llegada'] ) ? sanitize_text_field( wp_unslash( $_POST['gt_llegada'] ) ) : '';
		$out = isset( $_POST['gt_salida'] ) ? sanitize_text_field( wp_unslash( $_POST['gt_salida'] ) ) : '';
		if ( ! gt_valid_date( $in ) || ! gt_valid_date( $out ) ) {
			wc_add_notice( 'Elige la fecha de llegada y la de salida para reservar.', 'error' );
			return false;
		}
		if ( $in < $hoy ) {
			wc_add_notice( 'La fecha de llegada no puede ser anterior a hoy.', 'error' );
			return false;
		}
		if ( $out <= $in ) {
			wc_add_notice( 'La fecha de salida debe ser posterior a la de llegada.', 'error' );
			return false;
		}
		$noches = gt_noches( $in, $out );
		if ( $noches > 30 ) {
			wc_add_notice( 'Para estadías de más de 30 noches escríbenos por WhatsApp.', 'error' );
			return false;
		}
		$cap = max( 1, (int) get_post_meta( $product_id, '_gt_capacidad', true ) );
		$hue = isset( $_POST['gt_huespedes'] ) ? absint( $_POST['gt_huespedes'] ) : 1;
		if ( $hue < 1 || $hue > $cap ) {
			wc_add_notice( 'Este alojamiento admite hasta ' . $cap . ' personas.', 'error' );
			return false;
		}
	} elseif ( 'tour' === $tipo ) {
		$f = isset( $_POST['gt_fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['gt_fecha'] ) ) : '';
		if ( ! gt_valid_date( $f ) ) {
			wc_add_notice( 'Elige la fecha del tour para reservar.', 'error' );
			return false;
		}
		if ( $f < $hoy ) {
			wc_add_notice( 'La fecha del tour no puede ser anterior a hoy.', 'error' );
			return false;
		}
		$dias = trim( (string) get_post_meta( $product_id, '_gt_dias', true ) );
		if ( '' !== $dias ) {
			$dow = (int) gmdate( 'w', strtotime( $f . ' 12:00:00' ) );
			if ( ! in_array( $dow, array_map( 'intval', explode( ',', $dias ) ), true ) ) {
				wc_add_notice( 'Este tour sale solo los ' . gt_dias_texto( $dias ) . '. Elige otra fecha.', 'error' );
				return false;
			}
		}
		$cap = (int) get_post_meta( $product_id, '_gt_capacidad', true );
		if ( $cap > 0 && $qty > $cap ) {
			wc_add_notice( 'El grupo máximo de este tour es de ' . $cap . ' personas. Para grupos más grandes escríbenos por WhatsApp.', 'error' );
			return false;
		}
	}
	return $passed;
}, 10, 3 );

function gt_noches( $in, $out ) {
	return (int) round( ( strtotime( $out . ' 12:00:00' ) - strtotime( $in . ' 12:00:00' ) ) / DAY_IN_SECONDS );
}

/* ---------- Datos de la reserva en el carrito ---------- */

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	$tipo = gt_product_tipo( $product_id );
	if ( 'alojamiento' === $tipo && isset( $_POST['gt_llegada'], $_POST['gt_salida'] ) ) {
		$in                 = sanitize_text_field( wp_unslash( $_POST['gt_llegada'] ) );
		$out                = sanitize_text_field( wp_unslash( $_POST['gt_salida'] ) );
		$data['gt_reserva'] = array(
			'tipo'      => 'alojamiento',
			'llegada'   => $in,
			'salida'    => $out,
			'noches'    => gt_noches( $in, $out ),
			'huespedes' => isset( $_POST['gt_huespedes'] ) ? absint( $_POST['gt_huespedes'] ) : 1,
		);
	} elseif ( 'tour' === $tipo && isset( $_POST['gt_fecha'] ) ) {
		$data['gt_reserva'] = array(
			'tipo'  => 'tour',
			'fecha' => sanitize_text_field( wp_unslash( $_POST['gt_fecha'] ) ),
			'hora'  => isset( $_POST['gt_hora'] ) ? sanitize_text_field( wp_unslash( $_POST['gt_hora'] ) ) : '',
		);
	}
	return $data;
}, 10, 2 );

function gt_apply_cart_price( $item ) {
	if ( ! empty( $item['gt_reserva'] ) && 'alojamiento' === $item['gt_reserva']['tipo'] && ! empty( $item['data'] ) ) {
		$base = (float) get_post_meta( $item['product_id'], '_price', true );
		$item['data']->set_price( $base * max( 1, (int) $item['gt_reserva']['noches'] ) );
	}
	return $item;
}

add_filter( 'woocommerce_get_cart_item_from_session', function ( $item, $values ) {
	if ( isset( $values['gt_reserva'] ) ) {
		$item['gt_reserva'] = $values['gt_reserva'];
	}
	return gt_apply_cart_price( $item );
}, 10, 2 );

add_filter( 'woocommerce_add_cart_item', 'gt_apply_cart_price', 10, 1 );

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	foreach ( $cart->get_cart() as $key => $item ) {
		gt_apply_cart_price( $item );
	}
}, 20 );

function gt_reserva_lineas( $r ) {
	$l = array();
	if ( 'alojamiento' === $r['tipo'] ) {
		$l['Llegada']   = gt_fecha_larga( $r['llegada'] );
		$l['Salida']    = gt_fecha_larga( $r['salida'] );
		$l['Noches']    = (int) $r['noches'];
		$l['Huéspedes'] = (int) $r['huespedes'];
	} else {
		$l['Fecha del tour'] = gt_fecha_larga( $r['fecha'] );
		if ( ! empty( $r['hora'] ) ) {
			$l['Horario'] = $r['hora'];
		}
	}
	return $l;
}

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	if ( ! empty( $item['gt_reserva'] ) ) {
		foreach ( gt_reserva_lineas( $item['gt_reserva'] ) as $k => $v ) {
			$data[] = array( 'key' => $k, 'value' => esc_html( (string) $v ) );
		}
	}
	return $data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( ! empty( $values['gt_reserva'] ) ) {
		foreach ( gt_reserva_lineas( $values['gt_reserva'] ) as $k => $v ) {
			$line->add_meta_data( $k, $v, true );
		}
	}
}, 10, 3 );

/* ---------- Textos y precios ---------- */

add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	$tipo = gt_product_tipo( $product->get_id() );
	if ( 'alojamiento' === $tipo ) {
		return '<span class="gt-desde">Desde</span> ' . $html . ' <span class="gt-unidad">/ noche</span>';
	}
	if ( 'tour' === $tipo ) {
		return $html . ' <span class="gt-unidad">/ persona</span>';
	}
	return $html;
}, 10, 2 );

add_filter( 'woocommerce_product_single_add_to_cart_text', function ( $text, $product ) {
	return gt_product_tipo( $product->get_id() ) ? 'Reservar ahora' : $text;
}, 10, 2 );

/* En los listados, el botón lleva a la ficha para elegir fechas. */
add_filter( 'woocommerce_loop_add_to_cart_link', function ( $html, $product ) {
	if ( gt_product_tipo( $product->get_id() ) ) {
		return '<a class="button gt-btn-ver" href="' . esc_url( $product->get_permalink() ) . '">Ver disponibilidad</a>';
	}
	return $html;
}, 10, 2 );

/* Ficha: detalles, política de cancelación y WhatsApp. */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$id = $product->get_id();
	if ( ! gt_product_tipo( $id ) ) {
		return;
	}
	$campos = array(
		'_gt_subtipo'    => 'Tipo',
		'_gt_duracion'   => 'Duración',
		'_gt_dificultad' => 'Dificultad',
		'_gt_capacidad'  => 'Capacidad',
		'_gt_incluye'    => 'Incluye',
		'_gt_horario'    => 'Horario',
	);
	echo '<ul class="gt-detalles">';
	foreach ( $campos as $k => $label ) {
		$v = get_post_meta( $id, $k, true );
		if ( '' === $v ) {
			continue;
		}
		if ( '_gt_capacidad' === $k ) {
			$v = ( 'tour' === gt_product_tipo( $id ) ? 'Grupos de máximo ' : 'Hasta ' ) . (int) $v . ' personas';
		}
		echo '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $v ) . '</li>';
	}
	echo '</ul>';
}, 15 );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$id = $product->get_id();
	if ( ! gt_product_tipo( $id ) ) {
		return;
	}
	$pol = trim( (string) get_post_meta( $id, '_gt_cancelacion', true ) );
	if ( $pol ) {
		echo '<div class="gt-politica"><h3>Política de cancelación</h3><ul>';
		foreach ( preg_split( '/\r\n|\r|\n/', $pol ) as $linea ) {
			if ( trim( $linea ) ) {
				echo '<li>' . esc_html( trim( $linea ) ) . '</li>';
			}
		}
		$pp = get_page_by_path( 'politicas-de-reserva' );
		echo '</ul>';
		if ( $pp ) {
			echo '<p class="gt-politica__link">Consulta también nuestras <a href="' . esc_url( get_permalink( $pp ) ) . '">políticas de reserva y cancelación</a>.</p>';
		}
		echo '</div>';
	}
	$msg = 'Hola, quiero información sobre ' . $product->get_name() . ' en Girardota.com.co';
	echo '<a class="gt-wa-inline" href="' . esc_url( gt_wa_url( $msg ) ) . '" target="_blank" rel="noopener">¿Tienes dudas? Escríbenos por WhatsApp</a>';
}, 45 );

/* ---------- Checkout simplificado para servicios turísticos ---------- */

add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	foreach ( array( 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $f ) {
		unset( $fields['billing'][ $f ] );
	}
	$fields['billing']['billing_documento'] = array(
		'label'    => 'Documento de identidad (cédula o pasaporte)',
		'required' => true,
		'class'    => array( 'form-row-wide' ),
		'priority' => 25,
	);
	$fields['billing']['billing_phone']['label']    = 'Celular / WhatsApp';
	$fields['billing']['billing_phone']['required'] = true;
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'Comentarios para el anfitrión';
		$fields['order']['order_comments']['placeholder'] = 'Hora estimada de llegada, alergias, celebraciones especiales…';
	}
	return $fields;
} );

add_filter( 'woocommerce_billing_fields', function ( $fields ) {
	foreach ( array( 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $f ) {
		unset( $fields[ $f ] );
	}
	return $fields;
}, 20 );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	if ( isset( $_POST['billing_documento'] ) ) {
		$order->update_meta_data( '_billing_documento', sanitize_text_field( wp_unslash( $_POST['billing_documento'] ) ) );
	}
} );

add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$doc = $order->get_meta( '_billing_documento' );
	if ( $doc ) {
		echo '<p><strong>Documento:</strong> ' . esc_html( $doc ) . '</p>';
	}
} );

add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$msg = 'Hola, acabo de hacer la reserva #' . $order->get_order_number() . ' en Girardota.com.co a nombre de ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '.';
	echo '<div class="gt-gracias"><h2>¡Gracias por reservar en Girardota!</h2><p>Te confirmaremos la reserva y te enviaremos las indicaciones de llegada por WhatsApp. Si quieres agilizar la confirmación, escríbenos ahora:</p><a class="button gt-wa-inline" href="' . esc_url( gt_wa_url( $msg ) ) . '" target="_blank" rel="noopener">Confirmar por WhatsApp</a></div>';
}, 5 );

/* Script del formulario: calcula noches y total, y valida días de salida. */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_enqueue_script( 'gt-booking', GT_RES_URL . 'assets/booking.js', array(), '1.0.0', true );
	}
} );

/* ---------- Textos de WooCommerce adaptados a reservas (en español aunque falte la traducción) ---------- */

function gt_wc_textos() {
	return array(
		'Add to cart'                       => 'Reservar ahora',
		'Proceed to checkout'               => 'Continuar con la reserva',
		'Place order'                       => 'Confirmar reserva',
		'Billing details'                   => 'Datos de quien reserva',
		'Your order'                        => 'Tu reserva',
		'Cart totals'                       => 'Resumen de la reserva',
		'Order received'                    => 'Reserva recibida',
		'Thank you. Your order has been received.' => '¡Gracias! Recibimos tu reserva.',
		'Your cart is currently empty.'     => 'Aún no tienes reservas en el carrito.',
		'Return to shop'                    => 'Ver alojamientos y tours',
		'Product'                           => 'Servicio',
		'Products'                          => 'Servicios',
		'Quantity'                          => 'Cantidad',
		'Subtotal'                          => 'Subtotal',
		'Total'                             => 'Total',
		'Description'                       => 'Descripción',
		'Reviews'                           => 'Opiniones',
		'Additional information'            => 'Información adicional',
		'Related products'                  => 'También te puede gustar',
		'Update cart'                       => 'Actualizar',
		'Apply coupon'                      => 'Aplicar cupón',
		'Coupon code'                       => 'Código de cupón',
		'Have a coupon?'                    => '¿Tienes un cupón?',
		'Click here to enter your code'     => 'Haz clic aquí para ingresarlo',
		'First name'                        => 'Nombre',
		'Last name'                         => 'Apellidos',
		'Email address'                     => 'Correo electrónico',
		'Country / Region'                  => 'País',
		'Order notes'                       => 'Comentarios',
		'Additional information'            => 'Información adicional',
		'Remove this item'                  => 'Quitar',
		'My account'                        => 'Mi cuenta',
		'Orders'                            => 'Mis reservas',
		'Dashboard'                         => 'Escritorio',
		'Account details'                   => 'Datos de la cuenta',
		'Log out'                           => 'Cerrar sesión',
		'Login'                             => 'Ingresar',
		'Log in'                            => 'Ingresar',
		'Register'                          => 'Crear cuenta',
		'Username or email address'         => 'Usuario o correo electrónico',
		'Password'                          => 'Contraseña',
		'Remember me'                       => 'Recordarme',
		'Lost your password?'               => '¿Olvidaste tu contraseña?',
		'Order number:'                     => 'Número de reserva:',
		'Date:'                             => 'Fecha:',
		'Email:'                            => 'Correo:',
		'Payment method:'                   => 'Método de pago:',
		'Order details'                     => 'Detalles de la reserva',
		'Billing address'                   => 'Datos de facturación',
		'Default sorting'                   => 'Orden predeterminado',
		'Sort by popularity'                => 'Más populares',
		'Sort by average rating'            => 'Mejor calificados',
		'Sort by latest'                    => 'Más recientes',
		'Sort by price: low to high'        => 'Precio: menor a mayor',
		'Sort by price: high to low'        => 'Precio: mayor a menor',
		'Showing the single result'         => 'Mostrando el único resultado',
		'Sale!'                             => '¡Oferta!',
		'Phone'                             => 'Celular',
		'optional'                          => 'opcional',
		'Shop'                              => 'Reservar',
		'Home'                              => 'Inicio',
		'Search results: &ldquo;%s&rdquo;'  => 'Resultados de búsqueda: “%s”',
		'View cart'                         => 'Ver carrito',
		'Checkout'                          => 'Finalizar reserva',
		'Category:'                         => 'Categoría:',
		'Categories:'                       => 'Categorías:',
		'There are no reviews yet.'         => 'Aún no hay opiniones.',
		'Your review'                       => 'Tu opinión',
		'Your rating'                       => 'Tu calificación',
		'Submit'                            => 'Enviar',
		'Only logged in customers who have purchased this product may leave a review.' => 'Solo los viajeros que han reservado esta experiencia pueden dejar una opinión.',
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.' => 'Tus datos personales se usarán para procesar tu reserva, mejorar tu experiencia en este sitio y para los fines descritos en nuestra %s.',
		'privacy policy'                    => 'política de privacidad',
		'I have read and agree to the website %s' => 'He leído y acepto las %s',
		'terms and conditions'              => 'políticas de reserva y cancelación',
	);
}

add_filter( 'gettext', function ( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain || ( is_admin() && ! wp_doing_ajax() ) ) {
		return $translated;
	}
	static $map = null;
	if ( null === $map ) {
		$map = gt_wc_textos();
	}
	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}, 20, 3 );

add_filter( 'woocommerce_order_button_text', function () {
	return 'Confirmar reserva';
} );

add_filter( 'woocommerce_add_to_cart_message_html', function ( $message ) {
	return preg_replace( '/has been added to your cart\.?|ha sido añadido a tu carrito\.?|se ha añadido a tu carrito\.?/u', 'quedó en tu carrito de reservas.', $message );
} );

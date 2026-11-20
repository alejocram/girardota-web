<?php
/**
 * Configuración inicial (se ejecuta una sola vez): tienda en COP, contenido, páginas, menús e idioma.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gt_res_run_setup() {
	if ( get_transient( 'gt_res_setup_lock' ) ) {
		return;
	}
	set_transient( 'gt_res_setup_lock', 1, 5 * MINUTE_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore
	}

	try {
		gt_setup_options();
		gt_setup_woocommerce();
		$cats = gt_setup_product_cats();
		gt_setup_products( $cats );
		gt_setup_eventos();
		gt_setup_lugares();
		$pages = gt_setup_pages();
		gt_setup_posts();
		if ( wp_get_theme( 'girardota' )->exists() && get_stylesheet() !== 'girardota' ) {
			switch_theme( 'girardota' );
		}
		gt_setup_menus( $pages );
		update_option( 'gt_res_setup_done', GT_RES_SETUP_VERSION );
		delete_option( 'gt_res_needs_setup' );
		flush_rewrite_rules();
		do_action( 'litespeed_purge_all' );
	} catch ( Throwable $e ) {
		update_option( 'gt_res_setup_error', $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
	}

	// El idioma va al final: si la descarga falla, el resto ya quedó listo.
	try {
		gt_setup_language();
	} catch ( Throwable $e ) {
		update_option( 'gt_res_lang_error', $e->getMessage() );
	}
	delete_transient( 'gt_res_setup_lock' );
}

/* ---------- Utilidades ---------- */

function gt_blocks( array $sections ) {
	$out = '';
	foreach ( $sections as $html ) {
		$out .= "<!-- wp:html -->\n" . trim( $html ) . "\n<!-- /wp:html -->\n\n";
	}
	return $out;
}

function gt_import_image( $file, $title ) {
	$map = get_option( 'gt_res_images', array() );
	if ( ! empty( $map[ $file ] ) && get_post( $map[ $file ] ) ) {
		return (int) $map[ $file ];
	}
	$path = GT_RES_DIR . 'assets/img/' . $file;
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$up = wp_upload_bits( $file, null, file_get_contents( $path ) ); // phpcs:ignore
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment( array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => $title,
		'post_status'    => 'inherit',
	), $up['file'] );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	$map[ $file ] = $id;
	update_option( 'gt_res_images', $map, false );
	return (int) $id;
}

function gt_upsert_post( $slug, $type, $args, $meta = array() ) {
	$existing = get_posts( array(
		'name'           => $slug,
		'post_type'      => $type,
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	$args = array_merge( array(
		'post_name'   => $slug,
		'post_type'   => $type,
		'post_status' => 'publish',
	), $args );
	if ( $existing ) {
		$args['ID'] = $existing[0];
		$id         = wp_update_post( $args );
	} else {
		$id = wp_insert_post( $args );
	}
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return (int) $id;
}

function gt_term( $name, $slug, $tax, $parent = 0, $desc = '' ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( $t ) {
		return (int) $t->term_id;
	}
	$r = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'parent' => $parent, 'description' => $desc ) );
	return is_wp_error( $r ) ? 0 : (int) $r['term_id'];
}

/* ---------- Opciones generales ---------- */

function gt_setup_options() {
	update_option( 'blogname', 'Girardota.com.co' );
	update_option( 'blogdescription', 'Glamping, tours de café, aventura y sabores locales en Girardota, Antioquia' );
	update_option( 'timezone_string', 'America/Bogota' );
	update_option( 'date_format', 'j \d\e F \d\e Y' );
	update_option( 'time_format', 'g:i a' );
	update_option( 'start_of_week', 1 );
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'default_comment_status', 'closed' );
	if ( ! get_option( 'gt_whatsapp' ) ) {
		update_option( 'gt_whatsapp', '573007863624' );
		update_option( 'gt_whatsapp_display', '+57 300 786 3624' );
	}
	update_option( 'gt_social', array(
		'instagram' => 'https://www.instagram.com/girardota/',
		'facebook'  => 'https://www.facebook.com/girardota.com.co',
	) );

	// Borra el contenido de ejemplo de WordPress.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $d ) {
		$p = get_page_by_path( $d[0], OBJECT, $d[1] );
		if ( $p ) {
			wp_delete_post( $p->ID, true );
		}
	}
}

/* ---------- WooCommerce ---------- */

function gt_setup_woocommerce() {
	$opts = array(
		'woocommerce_currency'                     => 'COP',
		'woocommerce_currency_pos'                 => 'left',
		'woocommerce_price_thousand_sep'           => '.',
		'woocommerce_price_decimal_sep'            => ',',
		'woocommerce_price_num_decimals'           => 0,
		'woocommerce_default_country'              => 'CO:ANT',
		'woocommerce_store_address'                => 'Parque principal',
		'woocommerce_store_city'                   => 'Girardota',
		'woocommerce_store_postcode'               => '051030',
		'woocommerce_allowed_countries'            => 'all',
		'woocommerce_ship_to_countries'            => 'disabled',
		'woocommerce_calc_taxes'                   => 'no',
		'woocommerce_enable_guest_checkout'        => 'yes',
		'woocommerce_enable_checkout_login_reminder' => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_enable_myaccount_registration' => 'yes',
		'woocommerce_enable_reviews'               => 'yes',
		'woocommerce_review_rating_verification_required' => 'yes',
		'woocommerce_cart_redirect_after_add'      => 'yes',
		'woocommerce_enable_ajax_add_to_cart'      => 'no',
		'woocommerce_manage_stock'                 => 'no',
		'woocommerce_coming_soon'                  => 'no',
		'woocommerce_store_pages_only'             => 'no',
		'woocommerce_task_list_hidden'             => 'yes',
		'woocommerce_task_list_complete'           => 'yes',
		'woocommerce_show_marketplace_suggestions' => 'no',
		'woocommerce_allow_tracking'               => 'no',
		'woocommerce_email_from_name'              => 'Girardota.com.co',
		'woocommerce_email_footer_text'            => 'Girardota.com.co · Turismo de naturaleza en Girardota, Antioquia',
		'woocommerce_checkout_order_received_endpoint' => 'reserva-confirmada',
		'woocommerce_checkout_pay_endpoint'        => 'pagar-reserva',
		'woocommerce_myaccount_orders_endpoint'    => 'mis-reservas',
		'woocommerce_myaccount_view_order_endpoint' => 'ver-reserva',
		'woocommerce_myaccount_edit_account_endpoint' => 'editar-cuenta',
		'woocommerce_myaccount_edit_address_endpoint' => 'mis-datos',
		'woocommerce_myaccount_lost_password_endpoint' => 'recuperar-clave',
		'woocommerce_logout_endpoint'              => 'cerrar-sesion',
	);
	foreach ( $opts as $k => $v ) {
		update_option( $k, $v );
	}
	update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true, 'completed' => true ) );
	update_option( 'woocommerce_permalinks', array(
		'product_base'           => '/reserva',
		'category_base'          => 'categoria',
		'tag_base'               => 'etiqueta',
		'attribute_base'         => '',
		'use_verbose_page_rules' => false,
	) );

	update_option( 'woocommerce_bacs_settings', array_merge( (array) get_option( 'woocommerce_bacs_settings', array() ), array(
		'enabled'      => 'yes',
		'title'        => 'Transferencia (Bancolombia, Nequi o Daviplata)',
		'description'  => 'Al confirmar te enviamos por correo y WhatsApp los datos para pagar. Tu reserva queda apartada y se confirma al recibir el pago o el anticipo acordado.',
		'instructions' => 'Envía el comprobante de pago por WhatsApp al ' . gt_whatsapp_display() . ' indicando tu número de reserva. Te confirmaremos la reserva y las indicaciones de llegada.',
	) ) );
	update_option( 'woocommerce_cod_settings', array_merge( (array) get_option( 'woocommerce_cod_settings', array() ), array(
		'enabled'            => 'yes',
		'title'              => 'Reservar ahora y coordinar el pago por WhatsApp',
		'description'        => 'Aparta tu cupo sin pagar en línea. Un asesor te escribe por WhatsApp para confirmar disponibilidad y el anticipo.',
		'instructions'       => 'Te escribiremos por WhatsApp para confirmar disponibilidad y coordinar el pago.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	) ) );
}

/* ---------- Categorías de productos ---------- */

function gt_setup_product_cats() {
	$c                   = array();
	$c['alojamientos']   = gt_term( 'Alojamientos', 'alojamientos', 'product_cat', 0, 'Glamping, cabañas, ecohoteles y fincas tradicionales en Girardota.' );
	$c['glamping']       = gt_term( 'Glamping', 'glamping', 'product_cat', $c['alojamientos'] );
	$c['cabanas']        = gt_term( 'Cabañas', 'cabanas', 'product_cat', $c['alojamientos'] );
	$c['ecohoteles']     = gt_term( 'Ecohoteles', 'ecohoteles', 'product_cat', $c['alojamientos'] );
	$c['fincas']         = gt_term( 'Fincas', 'fincas', 'product_cat', $c['alojamientos'] );
	$c['tours']          = gt_term( 'Tours y experiencias', 'tours-y-experiencias', 'product_cat', 0, 'Tours de café, aventura y miel con guías locales.' );
	$c['tour-cafe']      = gt_term( 'Café', 'tour-cafe', 'product_cat', $c['tours'] );
	$c['aventura']       = gt_term( 'Aventura', 'aventura', 'product_cat', $c['tours'] );
	$c['miel']           = gt_term( 'Miel', 'miel', 'product_cat', $c['tours'] );
	$uncat = get_term_by( 'slug', 'uncategorized', 'product_cat' );
	if ( $uncat ) {
		wp_update_term( $uncat->term_id, 'product_cat', array( 'name' => 'Otros', 'slug' => 'otros' ) );
	}
	return $c;
}

/* ---------- Alojamientos y tours ---------- */

function gt_setup_products( $c ) {
	$nota = 'Política sujeta a confirmación del proveedor al momento de reservar.';
	$items = array(
		array(
			'slug' => 'domo-estrellas', 'name' => 'Domo Estrellas', 'price' => 380000, 'img' => 'hero-glamping-girardota.jpg', 'cats' => array( 'alojamientos', 'glamping' ), 'order' => 1,
			'short' => 'Domo geodésico con vista a las montañas, jacuzzi privado, malla catamarán y desayuno campesino incluido.',
			'desc' => '<p>Duerme bajo las estrellas en un domo geodésico con vista a las montañas de Girardota. Despierta con el valle cubierto de niebla, disfruta del jacuzzi privado al atardecer y descansa en la malla catamarán mirando el cielo.</p><p>Incluye desayuno campesino preparado con productos de la región. Ideal para parejas, aniversarios y escapadas de fin de semana a solo 30 minutos de Medellín.</p>',
			'meta' => array( '_gt_tipo' => 'alojamiento', '_gt_subtipo' => 'Glamping', '_gt_capacidad' => 2, '_gt_incluye' => 'Jacuzzi privado, malla catamarán, desayuno campesino', '_gt_horario' => 'Check-in 3:00 p.m. · Check-out 12:00 m.', '_gt_cancelacion' => "Cancelación gratuita hasta 7 días antes de la llegada.\nEntre 7 y 3 días antes: se retiene el 50% del valor.\nMenos de 72 horas o no presentarse: sin reembolso.\n$nota" ),
		),
		array(
			'slug' => 'cabana-del-bosque', 'name' => 'Cabaña del Bosque', 'price' => 450000, 'img' => 'cabana-del-bosque.jpg', 'cats' => array( 'alojamientos', 'cabanas' ), 'order' => 2,
			'short' => 'Cabaña de madera con chimenea, terraza con hamacas, fogata nocturna y cocina equipada.',
			'desc' => '<p>Una cabaña de madera rodeada de bosque, ideal para desconectarte en familia o con amigos alrededor de la chimenea y la fogata.</p><p>Cuenta con terraza con hamacas, cocina equipada para preparar tus propias comidas y fogata nocturna para cerrar el día bajo las estrellas.</p>',
			'meta' => array( '_gt_tipo' => 'alojamiento', '_gt_subtipo' => 'Cabaña', '_gt_capacidad' => 4, '_gt_incluye' => 'Chimenea, terraza con hamacas, fogata nocturna, cocina equipada', '_gt_horario' => 'Check-in 3:00 p.m. · Check-out 12:00 m.', '_gt_cancelacion' => "Cancelación gratuita hasta 15 días antes de la llegada.\nMenos de 15 días: se retiene el 50% del valor.\nTemporada alta (diciembre, Semana Santa): anticipo no reembolsable.\n$nota" ),
		),
		array(
			'slug' => 'ecohotel-reserva-natural', 'name' => 'Ecohotel Reserva Natural', 'price' => 290000, 'img' => 'ecohotel-reserva-natural.jpg', 'cats' => array( 'alojamientos', 'ecohoteles' ), 'order' => 3,
			'short' => 'Habitaciones con energía solar, huerta orgánica, senderos propios y avistamiento de aves.',
			'desc' => '<p>Un refugio sostenible entre montañas: habitaciones con energía solar, huerta orgánica propia y senderos para el avistamiento de aves.</p><p>El desayuno se prepara con productos de la huerta y la jornada se puede completar con caminatas guiadas por el bosque. Perfecto para viajeros que buscan descanso con bajo impacto.</p>',
			'meta' => array( '_gt_tipo' => 'alojamiento', '_gt_subtipo' => 'Ecohotel', '_gt_capacidad' => 3, '_gt_incluye' => 'Desayuno con productos de la huerta, senderos propios, avistamiento de aves', '_gt_horario' => 'Check-in 3:00 p.m. · Check-out 12:00 m.', '_gt_cancelacion' => "Cancelación gratuita hasta 5 días antes de la llegada.\nUna reprogramación sin costo, sujeta a disponibilidad.\nMenos de 5 días o no presentarse: se cobra la primera noche.\n$nota" ),
		),
		array(
			'slug' => 'finca-tradicional-paisa', 'name' => 'Finca Tradicional Paisa', 'price' => 220000, 'img' => 'finca-tradicional-paisa.jpg', 'cats' => array( 'alojamientos', 'fincas' ), 'order' => 4,
			'short' => 'Casa de campo antioqueña con corredores llenos de flores, piscina, zona de asados y desayuno típico.',
			'desc' => '<p>Vive la tradición antioqueña en una casa de campo con corredores llenos de flores, piscina y desayuno típico con arepa y chocolate.</p><p>Espacio ideal para grupos y familias de hasta 8 personas, con zona de asados y amplias zonas verdes.</p>',
			'meta' => array( '_gt_tipo' => 'alojamiento', '_gt_subtipo' => 'Finca', '_gt_capacidad' => 8, '_gt_incluye' => 'Piscina, zona de asados, desayuno típico', '_gt_horario' => 'Check-in 3:00 p.m. · Check-out 12:00 m.', '_gt_cancelacion' => "Anticipo del 30% para confirmar, no reembolsable.\nEl saldo es reembolsable si cancelas hasta 10 días antes.\nMenos de 10 días: sin reembolso del saldo.\n$nota" ),
		),
		array(
			'slug' => 'ruta-del-cafe', 'name' => 'Ruta del Café', 'price' => 120000, 'img' => 'tour-cafe.jpg', 'cats' => array( 'tours', 'tour-cafe' ), 'order' => 5,
			'short' => 'Recolecta, tuesta y cata café de altura en una finca familiar. Incluye transporte y almuerzo campesino.',
			'desc' => '<p>Recolecta, tuesta y cata café de altura en una finca familiar de Girardota. Conoce el proceso completo del grano, desde el cafetal hasta la taza, de la mano de los caficultores que lo cultivan.</p><p>Incluye transporte desde el parque principal y almuerzo campesino. Apto para familias.</p>',
			'meta' => array( '_gt_tipo' => 'tour', '_gt_subtipo' => 'Café', '_gt_capacidad' => 10, '_gt_duracion' => '5 horas', '_gt_dificultad' => 'Fácil', '_gt_incluye' => 'Transporte desde el parque principal, recorrido por el cafetal, tueste y cata, almuerzo campesino', '_gt_horario' => 'Diario · 8:00 a.m. · Salida desde el parque principal', '_gt_horarios' => '8:00 a.m.', '_gt_dias' => '', '_gt_cancelacion' => "Gratis hasta 48 horas antes.\nSi llueve fuerte, se reprograma sin costo.\nMenos de 48 horas: sin reembolso.\n$nota" ),
		),
		array(
			'slug' => 'aventura-extrema', 'name' => 'Aventura Extrema', 'price' => 150000, 'img' => 'tour-aventura.jpg', 'cats' => array( 'tours', 'aventura' ), 'order' => 6,
			'short' => 'Canopy sobre el cañón, caminata al mirador y senderismo con equipo certificado. Edad mínima 12 años.',
			'desc' => '<p>Canopy sobre el cañón, caminata al mirador y senderismo por las montañas de Girardota. Una dosis de adrenalina con las mejores vistas del norte del Valle de Aburrá.</p><p>Equipo certificado, seguro e hidratación incluidos. Edad mínima: 12 años.</p>',
			'meta' => array( '_gt_tipo' => 'tour', '_gt_subtipo' => 'Aventura', '_gt_capacidad' => 8, '_gt_duracion' => '4 horas', '_gt_dificultad' => 'Medio', '_gt_incluye' => 'Canopy, caminata al mirador, equipo certificado, seguro e hidratación', '_gt_horario' => 'Diario · 8:00 a.m. y 1:00 p.m.', '_gt_horarios' => '8:00 a.m., 1:00 p.m.', '_gt_dias' => '', '_gt_cancelacion' => "Gratis hasta 24 horas antes.\nSi el operador cancela por clima o seguridad, puedes reprogramar o recibir reembolso total.\n$nota" ),
		),
		array(
			'slug' => 'cata-de-miel', 'name' => 'Cata de Miel', 'price' => 80000, 'img' => 'tour-miel.jpg', 'cats' => array( 'tours', 'miel' ), 'order' => 7,
			'short' => 'Ponte el traje de apicultor, visita un apiario local y prueba cinco mieles de flores nativas.',
			'desc' => '<p>Ponte el traje de apicultor, visita un apiario local y prueba cinco mieles de flores nativas. Aprende cómo trabajan las abejas y por qué son claves para los bosques de Girardota.</p><p>Incluye frasco de miel de recuerdo. No apto para personas alérgicas a picaduras.</p>',
			'meta' => array( '_gt_tipo' => 'tour', '_gt_subtipo' => 'Miel', '_gt_capacidad' => 12, '_gt_duracion' => '3 horas', '_gt_dificultad' => 'Fácil', '_gt_incluye' => 'Traje de apicultor, visita al apiario, cata de cinco mieles, frasco de recuerdo', '_gt_horario' => 'Sábados y domingos · 9:00 a.m.', '_gt_horarios' => '9:00 a.m.', '_gt_dias' => '6,0', '_gt_cancelacion' => "Gratis hasta 72 horas antes.\nMenos de 72 horas o no presentarse: sin reembolso.\n$nota" ),
		),
	);

	foreach ( $items as $it ) {
		$existing = get_page_by_path( $it['slug'], OBJECT, 'product' );
		$product  = $existing ? wc_get_product( $existing->ID ) : new WC_Product_Simple();
		$product->set_name( $it['name'] );
		$product->set_slug( $it['slug'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( (string) $it['price'] );
		$product->set_virtual( true );
		$product->set_sold_individually( 'alojamiento' === $it['meta']['_gt_tipo'] );
		$product->set_short_description( $it['short'] );
		$product->set_description( $it['desc'] );
		$product->set_menu_order( $it['order'] );
		$product->set_featured( in_array( $it['slug'], array( 'domo-estrellas', 'ruta-del-cafe', 'aventura-extrema', 'cata-de-miel' ), true ) );
		$product->set_category_ids( array_values( array_filter( array_map( function ( $k ) use ( $c ) {
			return $c[ $k ] ?? 0;
		}, $it['cats'] ) ) ) );
		$img = gt_import_image( $it['img'], $it['name'] );
		if ( $img ) {
			$product->set_image_id( $img );
		}
		foreach ( $it['meta'] as $k => $v ) {
			$product->update_meta_data( $k, $v );
		}
		$product->save();
	}
}

/* ---------- Eventos ---------- */

function gt_setup_eventos() {
	$ev = array(
		array( 'fiestas-de-la-danza-y-el-sainete', 'Fiestas de la Danza y el Sainete', 'Noviembre 2026', '2026-11-15', 'Parque principal y veredas', 'cartel-danza-sainete.jpg', 'si', 'Homenaje al folclor local con danzas, comparsas, música tradicional y sainetes, el teatro popular lleno de humor que hace parte de la identidad de Girardota. Tradicionalmente la fiesta abre con el Carnaval de la Guagua y el Chagualo.' ),
		array( 'fiestas-del-senor-caido', 'Fiestas del Señor Caído', 'Enero 2027', '2027-01-15', 'Catedral de Girardota', 'cartel-senor-caido.jpg', '', 'La gran celebración religiosa del municipio y una de las más importantes de Antioquia. Miles de peregrinos llegan para honrar la imagen del Señor Caído con eucaristías, procesiones y encuentros comunitarios.' ),
		array( 'semana-santa', 'Semana Santa', 'Marzo o abril 2027', '2027-03-25', 'Catedral y centro', 'cartel-semana-santa.jpg', '', 'Procesiones solemnes, eucaristías y representaciones de la pasión que reúnen a locales y visitantes. Una de las mejores épocas para conocer el patrimonio religioso de Girardota.' ),
		array( 'que-delicia-girardota', 'Qué Delicia Girardota', 'Junio – julio 2027', '2027-06-25', 'Restaurantes participantes', 'cartel-que-delicia.jpg', '', 'Festival gastronómico de 10 días en el que restaurantes y emprendedores locales presentan platos especiales para recorrer los sabores del municipio.' ),
		array( 'festival-del-aire', 'Festival del Aire', 'Agosto 2027', '2027-08-05', 'Lugar por confirmar', 'cartel-festival-aire.jpg', '', 'El cielo se llena de cometas y color. Un plan familiar al aire libre con actividades recreativas y mucha convivencia.' ),
		array( 'feria-del-chicharron', 'Feria del Chicharrón', 'Agosto 2027', '2027-08-15', 'Lugar por confirmar', 'evento-feria-chicharron.jpg', '', 'Uno de los eventos más esperados: propuestas gastronómicas con el chicharrón como protagonista, concurso a la mejor receta, música en vivo y actividades para toda la familia.' ),
		array( 'gira-la-lectura', 'Gira La Lectura', 'Agosto 2027', '2027-08-25', 'Lugar por confirmar', 'evento-gira-la-lectura.jpg', '', 'Fiesta cultural dedicada a la lectura y la escritura, con encuentros con autores, talleres, exposiciones y actividades artísticas para todas las edades.' ),
	);
	foreach ( $ev as $i => $e ) {
		$content = '<p>' . $e[7] . '</p><p><strong>Fecha:</strong> ' . $e[2] . '<br><strong>Lugar:</strong> ' . $e[4] . '</p><p>Las fechas exactas se confirman con la programación oficial de la Alcaldía de Girardota. También puedes preguntar en el Punto de Información Turística del parque principal o llamar al (604) 405 3636.</p>';
		$id      = gt_upsert_post( $e[0], 'evento', array( 'post_title' => $e[1], 'post_content' => $content, 'post_excerpt' => $e[7], 'menu_order' => $i ), array(
			'_gt_fecha_texto' => $e[2],
			'_gt_fecha_orden' => $e[3],
			'_gt_lugar'       => $e[4],
			'_gt_en_curso'    => $e[6],
		) );
		$img = gt_import_image( $e[5], $e[1] );
		if ( $id && $img ) {
			set_post_thumbnail( $id, $img );
		}
	}
}

/* ---------- Restaurantes y cafés ---------- */

function gt_setup_lugares() {
	$rest = gt_term( 'Restaurantes', 'restaurantes', 'tipo_lugar' );
	$cafe = gt_term( 'Cafés de especialidad', 'cafes', 'tipo_lugar' );
	$l    = array(
		array( 'truchera-piedra-lisa', 'Truchera Piedra Lisa', $rest, 'Trucha · Plan campestre', 'Pesca tu propia trucha y disfrútala preparada en medio de las montañas y el aire puro. Ideal para desconectarse en familia o con amigos.', 'Vereda El Barro, km 3', '573007850160', 'https://www.instagram.com/trucherapiedralisa/' ),
		array( 'los-meandros', 'Los Meandros', $rest, 'Chicharrón · Parrilla · Mirador', 'Restaurante campestre con mirador, famoso por sus chicharrones en más de 12 presentaciones. También parrilla, hamburguesas, picadas y coctelería con recetas tradicionales antioqueñas.', 'Vereda La Matica parte baja, a medio km de la Autopista Norte', '573219498060', 'https://www.instagram.com/los_meandros/' ),
		array( 'campanario-encanto-cultural', 'Campanario Encanto Cultural', $rest, 'Cocina nacional e internacional · Cultura', 'Casa colonial y republicana de más de 250 años convertida en espacio cultural, artístico y gastronómico, con platos de cocina nacional e internacional.', 'Calle 7 # 14-35, frente a la Puerta del Perdón de la Catedral', '573122265485', 'https://www.instagram.com/campanarioencantocultural/' ),
		array( 'q-chingon', 'Q’Chingón', $rest, 'Comida mexicana', 'Un rincón con alma mexicana en el centro de Girardota: platos preparados con pasión y una atención cercana, como en casa.', 'Calle 10 # 16-10, frente al Colegio Emiliano García', '573192832793', 'https://www.instagram.com/q.chingon_/' ),
		array( 'q-fresas', 'Q’Fresas', $rest, 'Fresas con crema · Postres', 'Arma tu combinación perfecta de fresas con crema y toppings, y prueba su famoso Taco Fresa, una creación nacida en Girardota.', 'Carrera 16 # 9-48, frente al Colegio Emiliano García', '573042880849', 'https://www.instagram.com/q.fresas_/' ),
		array( 'toros-cafe', 'Toros Café', $cafe, 'Especialidad · Rutas cafeteras', 'Tienda y barra de café de especialidad con comida y bebidas únicas. Organizan rutas cafeteras y tienen coffee truck para eventos.', 'Parque de la Poesía, barrio Santa Ana', '573236415645', 'https://www.instagram.com/toros.cafe/' ),
		array( 'mokka-tienda-de-cafe', 'Mökka Tienda de Café', $cafe, 'Métodos manuales · Brunch · Repostería', 'Café de especialidad en métodos manuales, brunch y repostería artesanal, con espacios para leer, jugar y conversar sin prisa.', 'Mall Santana, Calle 10BB # 10-09, local 103', '573165545754', 'https://www.instagram.com/mokka_tiendadecafe/' ),
		array( 'cafe-gratitud', 'Café Gratitud', $cafe, 'Especialidad · Junto a la Catedral', 'Un punto de encuentro acogedor para residentes y visitantes, donde el café de especialidad se sirve con calidez y hospitalidad girardotana.', 'Calle 7 # 14-13, al costado de la Catedral', '573162880654', 'https://www.instagram.com/nuestrocafegratitud/' ),
		array( 'abigails-coffee', 'Abigail’s Coffee', $cafe, 'Café · Brunch · Coworking', 'Buen café, brunch y un espacio de coworking pensado para trabajar, crear proyectos y quedarse sin afán.', 'Carrera 14 # 7-90, a una cuadra del parque principal', '573127619095', 'https://www.instagram.com/abigailscoffe/' ),
	);
	foreach ( $l as $i => $x ) {
		$id = gt_upsert_post( $x[0], 'lugar', array( 'post_title' => $x[1], 'post_content' => '<p>' . $x[4] . '</p>', 'post_excerpt' => $x[4], 'menu_order' => $i ), array(
			'_gt_etiquetas' => $x[3],
			'_gt_direccion' => $x[5],
			'_gt_whatsapp'  => $x[6],
			'_gt_instagram' => $x[7],
		) );
		if ( $id && $x[2] ) {
			wp_set_object_terms( $id, array( (int) $x[2] ), 'tipo_lugar' );
		}
	}
}

/* ---------- Páginas ---------- */

function gt_setup_pages() {
	$wa      = 'https://wa.me/' . gt_whatsapp_number();
	$waNum   = gt_whatsapp_display();
	$p       = array();
	$img     = function ( $f, $t ) {
		return gt_import_image( $f, $t );
	};

	$p['inicio'] = gt_upsert_post( 'inicio', 'page', array( 'post_title' => 'Inicio', 'post_content' => '' ) );

	$p['alojamientos'] = gt_upsert_post( 'alojamientos', 'page', array(
		'post_title'   => 'Alojamientos',
		'post_excerpt' => 'Desde domos de glamping con vista al Valle de Aburrá hasta fincas paisas con desayuno típico. Elige tus fechas y reserva en línea.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container"><p class="gt-eyebrow">Dónde dormir</p><h2 class="gt-h2">Glamping, cabañas, ecohoteles y fincas</h2><p class="gt-lead">Busca, filtra por tipo y reserva directo con los anfitriones locales. Paga en línea o coordina tu pago por WhatsApp.</p>[gt_alojamientos]</div></section>',
			'<section class="gt-section gt-section--alt"><div class="gt-container gt-split"><div><p class="gt-eyebrow">Reserva tranquilo</p><h2 class="gt-h2">Cada alojamiento tiene su propia política de cancelación</h2><p>Consúltala en la página de cada alojamiento antes de reservar. Antes de cobrar, te confirmamos por WhatsApp la política vigente de tu reserva.</p></div><div class="gt-split__actions"><a class="gt-btn gt-btn--ghost" href="/politicas-de-reserva/">Ver políticas de reserva</a>[gt_whatsapp texto="Reservar por WhatsApp" mensaje="Hola, quiero hacer una reserva de alojamiento en Girardota.com.co"]</div></div></section>',
		) ),
	) );
	set_post_thumbnail( $p['alojamientos'], $img( 'hero-glamping-girardota.jpg', 'Glamping en Girardota' ) );

	$p['tours'] = gt_upsert_post( 'tours', 'page', array(
		'post_title'   => 'Tours y experiencias',
		'post_excerpt' => 'Experiencias diseñadas con campesinos, apicultores y guías de aventura de Girardota. Grupos pequeños y salidas diarias.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container"><p class="gt-eyebrow">Qué hacer</p><h2 class="gt-h2">Café, aventura y miel con guías locales</h2><p class="gt-lead">Elige la fecha, el horario y el número de personas, y reserva en línea en menos de dos minutos.</p>[gt_tours]</div></section>',
			'<section class="gt-section gt-section--alt"><div class="gt-container"><p class="gt-eyebrow">Resumen</p><h2 class="gt-h2">Salidas</h2><div class="gt-table-wrap"><table class="gt-table"><thead><tr><th>Experiencia</th><th>Horario</th><th>Grupo</th><th>Precio</th></tr></thead><tbody><tr><td><a href="/reserva/ruta-del-cafe/">Ruta del Café</a></td><td>Diario · 8:00 a.m.</td><td>Máx. 10 personas</td><td>$120.000 COP</td></tr><tr><td><a href="/reserva/aventura-extrema/">Aventura Extrema</a></td><td>Diario · 8:00 a.m. y 1:00 p.m.</td><td>Máx. 8 personas</td><td>$150.000 COP</td></tr><tr><td><a href="/reserva/cata-de-miel/">Cata de Miel</a></td><td>Sáb. y dom. · 9:00 a.m.</td><td>Máx. 12 personas</td><td>$80.000 COP</td></tr></tbody></table></div><p class="gt-note">Cada operador tiene su propia política de cancelación. Consúltala en la página de cada tour y en nuestras <a href="/politicas-de-reserva/">políticas de reserva</a>.</p></div></section>',
			'<section class="gt-cta"><div class="gt-container gt-cta__inner"><div><p class="gt-eyebrow">Grupos y empresas</p><h2 class="gt-h2">¿Más de 8 personas?</h2><p>Armamos planes a la medida para colegios, empresas y celebraciones.</p></div>[gt_whatsapp texto="Pedir cotización por WhatsApp" mensaje="Hola, quiero cotizar un plan para un grupo en Girardota."]</div></section>',
		) ),
	) );
	set_post_thumbnail( $p['tours'], $img( 'tour-cafe.jpg', 'Ruta del café en Girardota' ) );

	$p['restaurantes'] = gt_upsert_post( 'restaurantes', 'page', array(
		'post_title'   => 'Restaurantes y cafés',
		'post_excerpt' => 'Girardota sabe a café recién tostado, a sancocho de leña y a dulces de la tradición antioqueña.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container"><p class="gt-eyebrow">Dónde comer</p><h2 class="gt-h2">Restaurantes</h2><p class="gt-lead">Desde trucha pescada por ti mismo en la montaña hasta cocina mexicana en el centro del pueblo.</p>[gt_lugares tipo="restaurantes"]</div></section>',
			'<section class="gt-section gt-section--alt" id="cafes"><div class="gt-container"><p class="gt-eyebrow">Café de origen</p><h2 class="gt-h2">Cafés de especialidad</h2><p class="gt-lead">Métodos manuales, brunch y espacios para trabajar o conversar, casi todos a pocos pasos del parque principal y la Catedral.</p>[gt_lugares tipo="cafes"]</div></section>',
			'<section class="gt-section"><div class="gt-container gt-split"><div><p class="gt-eyebrow">Cultura y patrimonio</p><h2 class="gt-h2">Más allá de la mesa</h2><ul class="gt-list"><li>Visita la Catedral Nuestra Señora del Rosario y el Señor Caído, destino de una de las peregrinaciones más importantes de Antioquia.</li><li>Recorre el parque principal y conoce las artesanías y dulces típicos del municipio.</li></ul></div><div class="gt-cta-card"><p class="gt-eyebrow">Negocios aliados</p><h3>¿Tienes un restaurante?</h3><p>Aparece en nuestra guía y llega a miles de visitantes que buscan dónde comer en Girardota.</p>[gt_whatsapp texto="Quiero aparecer" mensaje="Hola, tengo un restaurante o café en Girardota y quiero aparecer en la guía de Girardota.com.co"]</div></div></section>',
		) ),
	) );
	set_post_thumbnail( $p['restaurantes'], $img( 'gastronomia-local.jpg', 'Gastronomía local de Girardota' ) );

	$p['eventos'] = gt_upsert_post( 'eventos', 'page', array(
		'post_title'   => 'Agenda de eventos',
		'post_excerpt' => 'Lo que viene en Girardota: fiestas religiosas, culturales y gastronómicas durante todo el año.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container"><p class="gt-eyebrow">Próximos eventos</p><h2 class="gt-h2">Lo que viene en Girardota</h2><p class="gt-lead">Toca un cartel para ver los detalles. Las fechas exactas se confirman con la programación oficial de la Alcaldía de Girardota.</p>[gt_eventos]</div></section>',
			'<section class="gt-cta"><div class="gt-container gt-cta__inner"><div><p class="gt-eyebrow">Organizadores</p><h2 class="gt-h2">Publica tu evento</h2><p>¿Organizas una feria, concierto o actividad en Girardota? Envíanos el cartel y lo incluimos en la agenda.</p></div>[gt_whatsapp texto="Enviar cartel por WhatsApp" mensaje="Hola, quiero publicar un evento en la agenda de Girardota.com.co"]</div></section>',
		) ),
	) );
	set_post_thumbnail( $p['eventos'], $img( 'fiestas-danza-sainete.jpg', 'Fiestas de la Danza y el Sainete' ) );

	$p['girardota'] = gt_upsert_post( 'girardota', 'page', array(
		'post_title'   => 'Conoce Girardota',
		'post_excerpt' => 'Montañas verdes, veredas campesinas, fe, café y fiestas a poco más de media hora de Medellín.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-prose"><p class="gt-eyebrow">Nuestro municipio</p><h2 class="gt-h2">Un lugar para visitar sin prisa</h2><p>Girardota está ubicada en el norte del Valle de Aburrá, en Antioquia, a poco más de media hora de Medellín. Es un municipio rodeado de montañas verdes y veredas campesinas donde todavía se respira la vida tranquila del campo antioqueño: fincas cafeteras, apiarios, quebradas, caminos de herradura y miradores desde los que se domina todo el valle. Su casco urbano conserva casas de estilo colonial y republicano alrededor del parque principal y de su imponente Catedral, y su gente es reconocida por la calidez con la que recibe a cada visitante.</p><p>La fe es parte esencial de su identidad: la imagen del Señor Caído convierte a Girardota en uno de los destinos de turismo religioso más importantes de Antioquia, y cada año miles de peregrinos llegan a la Catedral para rendirle homenaje. A esa tradición se suman una cocina antioqueña auténtica, cafés de especialidad, expresiones culturales como el sainete y la danza, y un calendario lleno de fiestas que celebran la comida, la lectura, el arte y la juventud. Girardota es un lugar para visitar sin prisa, conversar con su gente y volver.</p></div></section>',
			'<section class="gt-section gt-section--alt" id="festividades"><div class="gt-container"><p class="gt-eyebrow">Cultura y tradición</p><h2 class="gt-h2">Festividades</h2><p class="gt-lead">Durante todo el año, fiestas religiosas, culturales, deportivas y gastronómicas llenan de vida el municipio.</p><ol class="gt-timeline"><li><span>Enero</span><h3>Fiestas del Señor Caído</h3><p>La gran celebración religiosa del municipio y una de las más importantes de Antioquia, con eucaristías, procesiones y encuentros comunitarios.</p></li><li><span>Marzo o abril</span><h3>Semana Santa</h3><p>Procesiones solemnes, eucaristías y representaciones de la pasión que reúnen a locales y visitantes.</p></li><li><span>Finales de junio – inicios de julio</span><h3>Qué Delicia Girardota</h3><p>Festival gastronómico de 10 días en el que restaurantes y emprendedores locales presentan platos especiales.</p></li><li><span>Agosto</span><h3>Gira La Lectura</h3><p>Fiesta cultural dedicada a la lectura y la escritura, con encuentros con autores, talleres y exposiciones.</p></li><li><span>Agosto</span><h3>Festival del Aire</h3><p>El cielo se llena de cometas y color. Un plan familiar al aire libre.</p></li><li><span>Agosto</span><h3>Feria del Chicharrón</h3><p>Propuestas gastronómicas con el chicharrón como protagonista, concurso a la mejor receta y música en vivo.</p></li><li><span>Fecha por confirmar</span><h3>Semana de la Juventud</h3><p>Actividades deportivas, culturales, artísticas y académicas que celebran el talento de los jóvenes girardotanos.</p></li><li><span>Fecha por confirmar</span><h3>Fiestas de la Danza y el Sainete</h3><p>Homenaje al folclor local con danzas, comparsas, música tradicional y sainetes.</p></li></ol></div></section>',
			'<section class="gt-section"><div class="gt-container gt-split"><div><p class="gt-eyebrow">Agéndate</p><h2 class="gt-h2">Punto de Información Turística</h2><p>Las fechas exactas cambian cada año. Confírmalas en el Punto de Información Turística o en los canales oficiales del municipio.</p></div><div class="gt-info-card"><p><strong>Parque principal de Girardota</strong></p><p>Martes a viernes: 10:00 a.m. – 4:00 p.m.<br>Sábados y domingos: 10:00 a.m. – 3:00 p.m.</p><p>Tel. <a href="tel:+576044053636">(604) 405 3636</a><br><a href="mailto:turismo@girardota.gov.co">turismo@girardota.gov.co</a></p></div></div></section>',
			'<section class="gt-cta"><div class="gt-container gt-cta__inner"><div><p class="gt-eyebrow">Planea tu visita</p><h2 class="gt-h2">Vive las fiestas como un local</h2><p>Reserva tu alojamiento con anticipación: en temporada de fiestas los cupos se agotan rápido.</p></div><div class="gt-cta__actions"><a class="gt-btn gt-btn--light" href="/alojamientos/">Ver alojamientos</a><a class="gt-btn gt-btn--ghost-light" href="/tours/">Ver tours</a></div></div></section>',
		) ),
	) );
	set_post_thumbnail( $p['girardota'], $img( 'pueblo-girardota.jpg', 'Girardota, Antioquia' ) );

	$p['noticias'] = gt_upsert_post( 'noticias', 'page', array( 'post_title' => 'Noticias', 'post_content' => '', 'post_excerpt' => 'Noticias, guías y planes para vivir Girardota como un local.' ) );

	$p['nosotros'] = gt_upsert_post( 'nosotros', 'page', array(
		'post_title'   => 'Nosotros',
		'post_excerpt' => 'Girardota.com.co nace de la pasión por nuestro municipio.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-prose"><p class="gt-eyebrow">Quiénes somos</p><h2 class="gt-h2">Turismo que deja huella en la comunidad</h2><p>Girardota.com.co nace de la pasión por nuestro municipio. Queremos que más viajeros descubran las montañas, el café, la miel, la gastronomía y la gente de Girardota, y que cada visita deje beneficios reales en la comunidad.</p><p>Somos el punto de encuentro entre quienes buscan escapadas auténticas cerca de Medellín y los anfitriones, guías, campesinos y emprendedores locales que hacen posible cada experiencia. Aquí puedes encontrar dónde dormir, qué hacer, dónde comer y qué está pasando en el municipio, y reservar directamente en un solo lugar.</p></div></section>',
			'<section class="gt-section gt-section--alt"><div class="gt-container"><p class="gt-eyebrow">Lo que nos mueve</p><h2 class="gt-h2">Nuestros valores</h2><div class="gt-values"><div class="gt-value"><span class="gt-value__icon" aria-hidden="true">🤝</span><h3>Comunidad</h3><p>Trabajamos con anfitriones, guías y productores de Girardota para que el turismo genere ingresos para las familias locales.</p></div><div class="gt-value"><span class="gt-value__icon" aria-hidden="true">🌿</span><h3>Naturaleza</h3><p>Promovemos experiencias de bajo impacto que cuidan los bosques, las fuentes de agua y las abejas de la región.</p></div><div class="gt-value"><span class="gt-value__icon" aria-hidden="true">☕</span><h3>Autenticidad</h3><p>Mostramos la Girardota real: su café, su cocina, su fe y sus tradiciones, contadas por su propia gente.</p></div></div></div></section>',
			'<section class="gt-cta"><div class="gt-container gt-cta__inner"><div><p class="gt-eyebrow">Aliados</p><h2 class="gt-h2">Crece con nosotros</h2><p>¿Tienes un alojamiento, tour, restaurante o emprendimiento en Girardota? Súmate a la plataforma y llega a más viajeros.</p></div><div class="gt-cta__actions"><a class="gt-btn gt-btn--light" href="/contacto/">Quiero ser aliado</a></div></div></section>',
		) ),
	) );

	$p['contacto'] = gt_upsert_post( 'contacto', 'page', array(
		'post_title'   => 'Contacto',
		'post_excerpt' => 'Escríbenos para reservas, grupos, alianzas o para publicar tu evento.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-split gt-split--top"><div><p class="gt-eyebrow">Hablemos</p><h2 class="gt-h2">¿En qué te ayudamos?</h2><p>La forma más rápida de reservar o resolver dudas es por WhatsApp. También puedes dejarnos un mensaje y te respondemos por correo.</p><ul class="gt-contact-list"><li><strong>WhatsApp:</strong> <a href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">' . esc_html( $waNum ) . '</a></li><li><strong>Instagram:</strong> <a href="https://www.instagram.com/girardota/" target="_blank" rel="noopener">@girardota</a></li><li><strong>Facebook:</strong> <a href="https://www.facebook.com/girardota.com.co" target="_blank" rel="noopener">girardota.com.co</a></li><li><strong>Ubicación:</strong> Girardota, Antioquia, Colombia</li></ul>[gt_whatsapp texto="Escribir por WhatsApp"]</div><div class="gt-form-card">[gt_contacto]</div></div></section>',
		) ),
	) );

	$p['politicas'] = gt_upsert_post( 'politicas-de-reserva', 'page', array(
		'post_title'   => 'Políticas de reserva y cancelación',
		'post_excerpt' => 'Cómo funcionan las reservas, los pagos y las cancelaciones en Girardota.com.co.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-prose"><h2>Reservas y pagos</h2><ul><li>Puedes reservar en línea desde la página de cada alojamiento o tour, o por WhatsApp al <a href="' . esc_url( $wa ) . '">' . esc_html( $waNum ) . '</a>.</li><li>La reserva se confirma al recibir el pago total o el anticipo acordado con cada proveedor.</li><li>Precios en pesos colombianos (COP), con impuestos incluidos salvo que se indique lo contrario.</li><li>Recibirás la confirmación por correo electrónico y las indicaciones de llegada por WhatsApp.</li></ul><h2>Políticas de cancelación</h2><p>Girardota.com.co reúne alojamientos y tours de distintos proveedores locales. <strong>Cada alojamiento y cada operador de tours tiene su propia política de cancelación</strong>, y es la que aplica a tu reserva.</p><ul><li>Encuentras la política de cada proveedor en la página de cada alojamiento y de cada tour.</li><li>Antes de cobrar, te confirmamos por WhatsApp la política vigente de tu reserva.</li><li>Si un tour se cancela por clima o seguridad por decisión del operador, te ofreceremos reprogramar o el reembolso que establezca su política.</li></ul><h2>Derecho de retracto</h2><p>Conforme al Estatuto del Consumidor (Ley 1480 de 2011), aplican las condiciones de retracto y reversión del pago para ventas a distancia en lo que corresponda a servicios turísticos.</p><h2>Cambios y cancelaciones</h2><p>Todos los cambios y cancelaciones se gestionan por WhatsApp al ' . esc_html( $waNum ) . '. Ten a mano el número de reserva, el nombre de la reserva, el alojamiento o tour y la fecha reservada.</p><p>[gt_whatsapp texto="Solicitar cambio o cancelación" mensaje="Hola, necesito hacer un cambio o cancelación de mi reserva. Número de reserva: "]</p></div></section>',
		) ),
	) );

	$p['terminos'] = gt_upsert_post( 'terminos-y-condiciones', 'page', array(
		'post_title'   => 'Términos y condiciones',
		'post_excerpt' => 'Condiciones de uso del sitio y de las reservas realizadas en Girardota.com.co.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-prose"><h2>1. Quiénes somos</h2><p>Girardota.com.co es una plataforma que reúne y comercializa alojamientos, tours y experiencias ofrecidos por proveedores locales del municipio de Girardota, Antioquia.</p><h2>2. Reservas</h2><p>Al reservar, aceptas las condiciones del servicio publicadas en su página (capacidad, horarios, qué incluye y política de cancelación del proveedor) y nuestras <a href="/politicas-de-reserva/">políticas de reserva y cancelación</a>. La reserva queda confirmada cuando recibes nuestra confirmación tras el pago total o el anticipo acordado.</p><h2>3. Precios</h2><p>Los precios se expresan en pesos colombianos (COP). Los alojamientos se cobran por noche y los tours por persona, salvo que se indique otra cosa.</p><h2>4. Responsabilidad de los proveedores</h2><p>Cada servicio es prestado por su proveedor, quien es responsable de su calidad, seguridad y cumplimiento de la normativa turística vigente, incluido el Registro Nacional de Turismo cuando aplique. Girardota.com.co actúa como intermediario y acompaña al viajero en todo el proceso.</p><h2>5. Actividades de aventura</h2><p>Algunas experiencias implican esfuerzo físico o riesgos propios de la actividad. Debes seguir las indicaciones de los guías y respetar las restricciones de edad y salud indicadas en cada tour.</p><h2>6. Protección de menores</h2><p>Girardota.com.co rechaza la explotación y el abuso sexual de niños, niñas y adolescentes, conforme a la Ley 679 de 2001 y la Ley 1336 de 2009, y lo denuncia ante las autoridades.</p><h2>7. Contacto</h2><p>Para cualquier inquietud escríbenos por <a href="' . esc_url( $wa ) . '">WhatsApp</a> o desde la página de <a href="/contacto/">contacto</a>.</p></div></section>',
		) ),
	) );

	$p['privacidad'] = gt_upsert_post( 'politica-de-privacidad', 'page', array(
		'post_title'   => 'Política de privacidad',
		'post_excerpt' => 'Tratamiento de datos personales conforme a la Ley 1581 de 2012.',
		'post_content' => gt_blocks( array(
			'<section class="gt-section"><div class="gt-container gt-prose"><p>En cumplimiento de la Ley 1581 de 2012 y el Decreto 1377 de 2013, Girardota.com.co informa cómo trata los datos personales de sus usuarios.</p><h2>Datos que recolectamos</h2><p>Nombre, documento de identidad, correo electrónico, número de celular y la información necesaria para gestionar tus reservas y responder tus mensajes.</p><h2>Finalidad</h2><ul><li>Gestionar reservas, pagos y comunicaciones con los proveedores de cada servicio.</li><li>Enviarte la confirmación y las indicaciones de tu viaje.</li><li>Responder solicitudes, quejas y reclamos.</li><li>Enviarte información de interés, solo si lo autorizas.</li></ul><h2>Tus derechos</h2><p>Puedes conocer, actualizar, rectificar y suprimir tus datos, y revocar la autorización en cualquier momento escribiéndonos por WhatsApp al ' . esc_html( $waNum ) . ' o desde la página de <a href="/contacto/">contacto</a>.</p><h2>Seguridad</h2><p>Aplicamos medidas técnicas razonables para proteger tu información. Los pagos en línea son procesados por pasarelas de pago certificadas; no almacenamos datos de tarjetas.</p></div></section>',
		) ),
	) );
	update_option( 'wp_page_for_privacy_policy', $p['privacidad'] );

	// Páginas de WooCommerce en español (versión clásica para usar los campos de reserva).
	$wc = array(
		'shop'      => array( 'reservar', 'Reservar', '' ),
		'cart'      => array( 'carrito', 'Carrito', '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->' ),
		'checkout'  => array( 'finalizar-reserva', 'Finalizar reserva', '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->' ),
		'myaccount' => array( 'mi-cuenta', 'Mi cuenta', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' ),
	);
	foreach ( $wc as $key => $d ) {
		$pid  = (int) get_option( 'woocommerce_' . $key . '_page_id' );
		$args = array( 'post_title' => $d[1], 'post_name' => $d[0], 'post_content' => $d[2], 'post_type' => 'page', 'post_status' => 'publish' );
		if ( $pid && get_post( $pid ) ) {
			$args['ID'] = $pid;
			wp_update_post( $args );
		} else {
			$pid = gt_upsert_post( $d[0], 'page', $args );
		}
		update_option( 'woocommerce_' . $key . '_page_id', $pid );
		$p[ $key ] = $pid;
	}
	update_option( 'woocommerce_terms_page_id', $p['politicas'] );
	// Las páginas de "Rastreo de pedidos" y "Reembolsos" que crea WooCommerce no se usan.
	foreach ( array( 'refund_returns' ) as $slug ) {
		$pg = get_page_by_path( $slug );
		if ( $pg ) {
			wp_update_post( array( 'ID' => $pg->ID, 'post_status' => 'draft' ) );
		}
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $p['inicio'] );
	update_option( 'page_for_posts', $p['noticias'] );
	return $p;
}

/* ---------- Noticias y guías ---------- */

function gt_setup_posts() {
	$noticias = gt_term( 'Noticias', 'noticias', 'category' );
	$guias    = gt_term( 'Guías', 'guias', 'category' );
	$def      = get_term_by( 'slug', 'uncategorized', 'category' );
	if ( $def ) {
		wp_update_term( $def->term_id, 'category', array( 'name' => 'General', 'slug' => 'general' ) );
	}

	$id = gt_upsert_post( 'fiestas-de-la-danza-y-el-sainete-2026', 'post', array(
		'post_title'    => 'Girardota vive las Fiestas de la Danza y el Sainete 2026',
		'post_excerpt'  => 'Comparsas, danza, música en vivo y el humor del sainete llenan las calles y veredas del municipio.',
		'post_date'     => '2026-11-12 09:00:00',
		'post_category' => array( $noticias ),
		'post_content'  => '<!-- wp:paragraph --><p>Girardota está de fiesta. En estos días el municipio celebra las <strong>Fiestas de la Danza y el Sainete 2026</strong>, una de las celebraciones culturales más queridas por los girardotanos y una gran oportunidad para conocer el pueblo en su versión más alegre.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Una fiesta con raíces</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Estas fiestas rinden homenaje a las expresiones artísticas y folclóricas que hacen parte de la identidad de Girardota. Su gran protagonista es el <strong>sainete</strong>, una forma de teatro popular corta, llena de humor y crítica social, que por generaciones ha hecho reír a las familias del municipio. A su lado brillan la danza, las comparsas y la música tradicional, interpretadas por grupos y artistas locales.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Qué puedes vivir</h2><!-- /wp:heading -->
<!-- wp:list --><ul><li><strong>Comparsas y desfiles</strong> por las calles del casco urbano. Tradicionalmente la fiesta abre con el Carnaval de la Guagua y el Chagualo.</li><li><strong>Presentaciones de danza y sainete</strong> a cargo de grupos artísticos del municipio.</li><li><strong>Música en vivo</strong> y encuentros en el parque principal.</li><li><strong>Jornadas en los barrios y veredas</strong>, porque la celebración recorre todo el territorio y no solo el centro.</li><li>El tradicional <strong>reinado</strong> que lleva el nombre de las fiestas.</li></ul><!-- /wp:list -->
<!-- wp:paragraph --><p>Los horarios, lugares e invitados de cada día se publican en los canales oficiales de la Alcaldía de Girardota. También puedes preguntar en el Punto de Información Turística del parque principal (martes a viernes de 10:00 a.m. a 4:00 p.m., sábados y domingos de 10:00 a.m. a 3:00 p.m.) o llamar al (604) 405 3636.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Arma tu plan de fiestas</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Aprovecha la visita para quedarte una noche en las montañas, probar un buen chicharrón o tomarte un café de especialidad a pasos de la Catedral. En temporada de fiestas los cupos se llenan rápido, así que te recomendamos <a href="/alojamientos/">reservar tu alojamiento</a> con tiempo.</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size"><em>Fuente: Alcaldía de Girardota – Fiestas y Celebraciones y Girardota Travel.</em></p><!-- /wp:paragraph -->',
	) );
	if ( $id ) {
		set_post_thumbnail( $id, gt_import_image( 'fiestas-danza-sainete.jpg', 'Fiestas de la Danza y el Sainete' ) );
	}

	$id = gt_upsert_post( 'que-hacer-en-girardota-fin-de-semana', 'post', array(
		'post_title'    => 'Qué hacer en Girardota: plan de fin de semana',
		'post_excerpt'  => 'Un itinerario de dos días con glamping, ruta del café, cata de miel y los mejores sabores del pueblo.',
		'post_date'     => '2026-11-14 09:00:00',
		'post_category' => array( $guias ),
		'post_content'  => '<!-- wp:paragraph --><p>Girardota queda a poco más de media hora de Medellín, así que es perfecta para una escapada de fin de semana. Esta es nuestra propuesta para aprovecharla al máximo.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Sábado: café, pueblo y atardecer en la montaña</h2><!-- /wp:heading -->
<!-- wp:list --><ul><li><strong>8:00 a.m.</strong> Sal desde el parque principal en la <a href="/reserva/ruta-del-cafe/">Ruta del Café</a>: recolecta, tuesta y cata café de altura en una finca familiar, con almuerzo campesino incluido.</li><li><strong>2:00 p.m.</strong> Recorre el centro: la Catedral Nuestra Señora del Rosario, la imagen del Señor Caído y las casas coloniales y republicanas alrededor del parque.</li><li><strong>4:00 p.m.</strong> Un café de especialidad junto a la Catedral o unas fresas con crema antes de subir a la montaña.</li><li><strong>Noche.</strong> Check-in en el <a href="/reserva/domo-estrellas/">Domo Estrellas</a> o en la <a href="/reserva/cabana-del-bosque/">Cabaña del Bosque</a>, con jacuzzi o fogata bajo las estrellas.</li></ul><!-- /wp:list -->
<!-- wp:heading --><h2>Domingo: abejas, chicharrón y vista al valle</h2><!-- /wp:heading -->
<!-- wp:list --><ul><li><strong>9:00 a.m.</strong> <a href="/reserva/cata-de-miel/">Cata de Miel</a>: ponte el traje de apicultor y prueba cinco mieles de flores nativas (solo sábados y domingos).</li><li><strong>12:30 p.m.</strong> Almuerzo campestre: trucha pescada por ti mismo o chicharrón con vista al valle. Mira nuestra <a href="/restaurantes/">guía de restaurantes</a>.</li><li><strong>Tarde.</strong> Si te queda energía, el tour de <a href="/reserva/aventura-extrema/">Aventura Extrema</a> de la 1:00 p.m. tiene canopy sobre el cañón.</li></ul><!-- /wp:list -->
<!-- wp:paragraph --><p>¿Viajas en temporada de fiestas? Revisa la <a href="/eventos/">agenda de eventos</a> y reserva con anticipación.</p><!-- /wp:paragraph -->',
	) );
	if ( $id ) {
		set_post_thumbnail( $id, gt_import_image( 'pueblo-girardota.jpg', 'Girardota, Antioquia' ) );
	}

	$id = gt_upsert_post( 'como-llegar-a-girardota-desde-medellin', 'post', array(
		'post_title'    => 'Cómo llegar a Girardota desde Medellín',
		'post_excerpt'  => 'En carro, en bus o combinando con el Metro: opciones para llegar al norte del Valle de Aburrá.',
		'post_date'     => '2026-11-15 09:00:00',
		'post_category' => array( $guias ),
		'post_content'  => '<!-- wp:paragraph --><p>Girardota está en el norte del Valle de Aburrá, a poco más de media hora de Medellín. Estas son las formas más comunes de llegar.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>En carro</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Toma la Autopista Norte en dirección a Bello y Copacabana y continúa hasta Girardota. Varios restaurantes campestres y alojamientos quedan a pocos minutos de la autopista; para las veredas más altas te recomendamos un vehículo con buena tracción y confirmar la ruta con tu anfitrión.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>En transporte público</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Puedes tomar el Metro hasta la estación del norte del sistema y continuar en bus intermunicipal hacia Girardota, o tomar un bus directo desde la Terminal del Norte de Medellín. Confirma horarios y tarifas vigentes con las empresas de transporte antes de viajar.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Ya en Girardota</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>El parque principal es el punto de encuentro de la mayoría de nuestros tours. Si reservas un alojamiento, te enviamos por WhatsApp la ubicación exacta y las indicaciones de llegada.</p><!-- /wp:paragraph -->',
	) );
	if ( $id ) {
		set_post_thumbnail( $id, gt_import_image( 'hero-glamping-girardota.jpg', 'Montañas de Girardota' ) );
	}
}

/* ---------- Menús ---------- */

function gt_setup_menus( $p ) {
	$build = function ( $name, $items ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $it ) {
				wp_delete_post( $it->ID, true );
			}
			$mid = $menu->term_id;
		} else {
			$mid = wp_create_nav_menu( $name );
		}
		if ( is_wp_error( $mid ) ) {
			return 0;
		}
		foreach ( $items as $it ) {
			wp_update_nav_menu_item( $mid, 0, array(
				'menu-item-title'     => $it[0],
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $it[1],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			) );
		}
		return $mid;
	};
	$principal = $build( 'Menú principal', array(
		array( 'Alojamientos', $p['alojamientos'] ),
		array( 'Tours', $p['tours'] ),
		array( 'Restaurantes y cafés', $p['restaurantes'] ),
		array( 'Eventos', $p['eventos'] ),
		array( 'Conoce Girardota', $p['girardota'] ),
		array( 'Noticias', $p['noticias'] ),
		array( 'Contacto', $p['contacto'] ),
	) );
	$legal = $build( 'Menú legal', array(
		array( 'Políticas de reserva', $p['politicas'] ),
		array( 'Términos y condiciones', $p['terminos'] ),
		array( 'Política de privacidad', $p['privacidad'] ),
		array( 'Nosotros', $p['nosotros'] ),
		array( 'Mi cuenta', $p['myaccount'] ),
	) );
	$locs            = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locs['primary'] = $principal;
	$locs['legal']   = $legal;
	set_theme_mod( 'nav_menu_locations', $locs );
	update_option( 'gt_menu_ids', array( 'primary' => $principal, 'legal' => $legal ) );
}

/* ---------- Idioma: español de Colombia ---------- */

function gt_setup_language() {
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$locale = 'es_CO';
	$got    = wp_download_language_pack( $locale );
	if ( $got ) {
		update_option( 'WPLANG', $got );
		load_default_textdomain( $got );
	}
	// Traducciones de plugins (WooCommerce) y temas.
	wp_clean_update_cache();
	wp_update_plugins();
	wp_update_themes();
	$updates = wp_get_translation_updates();
	if ( $updates ) {
		$up = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
		$up->bulk_upgrade( $updates );
	}
	update_option( 'gt_res_lang_done', $got ? $got : 'fallo' );
}

<?php
/**
 * Tema Girardota.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'girardota' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'woocommerce', array(
		'thumbnail_image_width' => 600,
		'single_image_width'    => 900,
	) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	register_nav_menus( array(
		'primary' => 'Menú principal',
		'legal'   => 'Menú legal (pie de página)',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'girardota-fonts', 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap', array(), null );
	wp_enqueue_style( 'girardota', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_script( 'girardota', get_template_directory_uri() . '/assets/main.js', array(), wp_get_theme()->get( 'Version' ), true );
} );

add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/* WooCommerce: usamos nuestro propio contenedor. */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_filter( 'woocommerce_show_page_title', '__return_false' );
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 3;
	$args['columns']        = 3;
	return $args;
} );
add_filter( 'woocommerce_breadcrumb_defaults', function ( $d ) {
	$d['home']      = 'Inicio';
	$d['delimiter'] = ' / ';
	return $d;
} );

/* Contador del carrito actualizado por AJAX. */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.gt-cart__count'] = gt_cart_count_html();
	return $fragments;
} );

function gt_cart_count_html() {
	$n = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="gt-cart__count"' . ( $n ? '' : ' hidden' ) . '>' . (int) $n . '</span>';
}

/** URL de una imagen del sitio (de la biblioteca de medios o del plugin). */
function gt_img_url( $file, $size = 'large' ) {
	$map = get_option( 'gt_res_images', array() );
	if ( ! empty( $map[ $file ] ) ) {
		$u = wp_get_attachment_image_url( $map[ $file ], $size );
		if ( $u ) {
			return $u;
		}
	}
	return content_url( 'plugins/girardota-reservas/assets/img/' . $file );
}

function gt_img( $file, $alt = '', $class = '', $size = 'large', $eager = false ) {
	$map = get_option( 'gt_res_images', array() );
	$att = array( 'class' => $class, 'alt' => $alt );
	if ( $eager ) {
		$att['loading']       = 'eager';
		$att['fetchpriority'] = 'high';
	} else {
		$att['loading'] = 'lazy';
	}
	if ( ! empty( $map[ $file ] ) && wp_get_attachment_image_url( $map[ $file ], $size ) ) {
		return wp_get_attachment_image( $map[ $file ], $size, false, $att );
	}
	return '<img src="' . esc_url( gt_img_url( $file ) ) . '" alt="' . esc_attr( $alt ) . '" class="' . esc_attr( $class ) . '" loading="' . ( $eager ? 'eager' : 'lazy' ) . '">';
}

function gt_wa( $msg = '' ) {
	if ( function_exists( 'gt_wa_url' ) ) {
		return gt_wa_url( $msg );
	}
	return 'https://wa.me/573007863624' . ( $msg ? '?text=' . rawurlencode( $msg ) : '' );
}

function gt_wa_display() {
	return function_exists( 'gt_whatsapp_display' ) ? gt_whatsapp_display() : '+57 300 786 3624';
}

function gt_social() {
	return wp_parse_args( (array) get_option( 'gt_social', array() ), array(
		'instagram' => 'https://www.instagram.com/girardota/',
		'facebook'  => 'https://www.facebook.com/girardota.com.co',
	) );
}

function gt_logo_svg() {
	return '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="24" fill="#1e4d3b"/><circle cx="31" cy="17" r="5.5" fill="#e2a93b"/><path d="M4 36l11-14 7 8 6-6 16 12a24 24 0 0 1-40 0z" fill="#8fc19f"/><path d="M8 40l10-11 9 9 5-4 10 7a24 24 0 0 1-34-1z" fill="#2c6a4f"/></svg>';
}

function gt_icon( $name ) {
	$icons = array(
		'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2c3.2 0 3.6 0 4.8.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8C2.4 3.9 3.9 2.4 7.2 2.3 8.4 2.2 8.8 2.2 12 2.2zM12 0C8.7 0 8.3 0 7.1.1 2.7.3.3 2.7.1 7.1 0 8.3 0 8.7 0 12s0 3.7.1 4.9c.2 4.4 2.6 6.8 7 7 1.2.1 1.6.1 4.9.1s3.7 0 4.9-.1c4.4-.2 6.8-2.6 7-7 .1-1.2.1-1.6.1-4.9s0-3.7-.1-4.9c-.2-4.4-2.6-6.8-7-7C15.7 0 15.3 0 12 0zm0 5.8a6.2 6.2 0 1 0 0 12.4 6.2 6.2 0 0 0 0-12.4zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.8a1.4 1.4 0 1 0 0 2.9 1.4 1.4 0 0 0 0-2.9z"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 8H6v4h3v12h5V12h3.6L18 8h-4V6.3C14 5.4 14.2 5 15.1 5H18V0h-3.8C10.6 0 9 1.6 9 4.6V8z"/></svg>',
		'cart'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
	);
	return $icons[ $name ] ?? '';
}

/** Encabezado de página interior. */
function gt_page_hero( $title, $subtitle = '', $img_id = 0, $small = false ) {
	$cls = 'gt-page-hero' . ( $img_id ? ' has-img' : '' ) . ( $small ? ' gt-page-hero--sm' : '' );
	echo '<header class="' . esc_attr( $cls ) . '">';
	if ( $img_id ) {
		echo wp_get_attachment_image( $img_id, 'full', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '' ) );
	}
	echo '<div class="gt-container">';
	if ( ! $small ) {
		echo '<nav class="gt-crumbs" aria-label="Ruta"><a href="' . esc_url( home_url( '/' ) ) . '">Inicio</a> / ' . esc_html( $title ) . '</nav>';
	}
	echo '<h1>' . esc_html( $title ) . '</h1>';
	if ( $subtitle ) {
		echo '<p>' . esc_html( $subtitle ) . '</p>';
	}
	echo '</div></header>';
}

/** Tarjeta de entrada del blog. */
function gt_post_card( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$cat     = get_the_category( $post_id );
	?>
	<article class="gt-post">
		<a class="gt-post__img" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'loading' => 'lazy' ) ); ?>
		</a>
		<div class="gt-post__body">
			<p class="gt-post__meta"><?php if ( $cat ) : ?><a href="<?php echo esc_url( get_category_link( $cat[0] ) ); ?>"><?php echo esc_html( $cat[0]->name ); ?></a> · <?php endif; ?><?php echo esc_html( get_the_date( '', $post_id ) ); ?></p>
			<h3><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 24 ) ); ?></p>
		</div>
	</article>
	<?php
}

/* Menú de respaldo si aún no hay menú asignado. */
function gt_fallback_menu() {
	$links = array(
		'alojamientos' => 'Alojamientos',
		'tours'        => 'Tours',
		'restaurantes' => 'Restaurantes y cafés',
		'eventos'      => 'Eventos',
		'girardota'    => 'Conoce Girardota',
		'noticias'     => 'Noticias',
		'nosotros'     => 'Nosotros',
	);
	echo '<ul>';
	foreach ( $links as $slug => $label ) {
		echo '<li><a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

add_filter( 'excerpt_more', function () {
	return '…';
} );

/* Metadatos básicos para buscadores y redes. */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$desc = is_singular() && has_excerpt() ? get_the_excerpt() : get_bloginfo( 'description' );
	$img  = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'large' ) : gt_img_url( 'hero-glamping-girardota.jpg' );
	echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
	echo '<meta property="og:site_name" content="Girardota.com.co">' . "\n";
	echo '<meta property="og:locale" content="es_CO">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
	echo '<meta name="theme-color" content="#1e4d3b">' . "\n";
}, 5 );

add_filter( 'document_title_separator', function () {
	return '·';
} );

/*
 * Marca fija "Girardota.com.co".
 * Los puntos de la marca se escriben como entidad HTML (&#46;) para que ningún proxy o
 * dominio de vista previa la reemplace al servir la página. El navegador la muestra igual.
 * Aplica a textos, al enlace de Facebook y a los mensajes de WhatsApp. Las URLs del sitio
 * (http://girardota.com.co/...) no se tocan.
 */
function gt_brand_protect( $html ) {
	$parts = preg_split( '#(<script\b[^>]*>.*?</script>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	foreach ( $parts as $i => $part ) {
		if ( 1 === $i % 2 ) {
			continue; // Bloques <script>: sin cambios.
		}
		$part        = str_replace(
			array( 'facebook.com/girardota.com.co', '>girardota.com.co<' ),
			array( 'facebook.com/girardota&#46;com&#46;co', '>girardota&#46;com&#46;co<' ),
			$part
		);
		$parts[ $i ] = str_replace( 'Girardota.com.co', 'Girardota&#46;com&#46;co', $part );
	}
	return implode( '', $parts );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'gt_brand_protect' );
}, 0 );

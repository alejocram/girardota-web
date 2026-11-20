<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode( gt_logo_svg() ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#contenido">Saltar al contenido</a>

<div class="gt-topbar">
	<div class="gt-container">
		<span>Reservas y dudas por WhatsApp: <a href="<?php echo esc_url( gt_wa( 'Hola, quiero información sobre Girardota.com.co' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( gt_wa_display() ); ?></a></span>
		<span class="gt-topbar__social">
			<a href="<?php echo esc_url( gt_social()['instagram'] ); ?>" target="_blank" rel="noopener">Instagram</a>
			<a href="<?php echo esc_url( gt_social()['facebook'] ); ?>" target="_blank" rel="noopener">Facebook</a>
		</span>
	</div>
</div>

<header class="gt-header" id="gt-header">
	<div class="gt-container gt-header__inner">
		<a class="gt-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php echo gt_logo_svg(); // phpcs:ignore ?>
			<span><span class="gt-logo__name">Girardota<span>.com.co</span></span><span class="gt-logo__tag">Turismo de naturaleza · Antioquia</span></span>
		</a>

		<nav class="gt-nav" id="gt-nav" aria-label="Menú principal">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'gt_fallback_menu',
				'depth'          => 1,
			) );
			?>
		</nav>

		<div class="gt-header__actions">
			<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
				<a class="gt-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Ver carrito de reservas">
					<?php echo gt_icon( 'cart' ); // phpcs:ignore ?>
					<?php echo gt_cart_count_html(); // phpcs:ignore ?>
				</a>
			<?php endif; ?>
			<a class="gt-btn gt-btn--miel gt-btn--sm gt-header__cta" href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Reservar</a>
			<button class="gt-burger" type="button" aria-controls="gt-nav" aria-expanded="false" aria-label="Abrir menú"><span></span></button>
		</div>
	</div>
</header>

<main id="contenido">

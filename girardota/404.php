<?php
get_header();
gt_page_hero( 'Página no encontrada', 'Parece que este sendero no lleva a ningún lado.', 0, true );
?>
<section class="gt-section">
	<div class="gt-container">
		<p class="gt-lead">Vuelve al inicio o explora lo mejor de Girardota:</p>
		<p style="display:flex;gap:12px;flex-wrap:wrap">
			<a class="gt-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir al inicio</a>
			<a class="gt-btn gt-btn--ghost" href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Alojamientos</a>
			<a class="gt-btn gt-btn--ghost" href="<?php echo esc_url( home_url( '/tours/' ) ); ?>">Tours</a>
		</p>
	</div>
</section>
<?php
get_footer();

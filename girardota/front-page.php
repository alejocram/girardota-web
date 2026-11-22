<?php
get_header();
$sc = function ( $code ) {
	return shortcode_exists( strtok( trim( $code, '[]' ), ' ' ) ) ? do_shortcode( $code ) : '';
};
?>

<section class="gt-hero">
	<?php echo gt_img( 'hero-glamping-girardota.jpg', 'Domo de glamping en las montañas de Girardota al atardecer', 'gt-hero__bg', 'full', true ); // phpcs:ignore ?>
	<div class="gt-container">
		<p class="gt-eyebrow">Girardota, Antioquia</p>
		<h1>Tu aventura empieza en <em>Girardota</em></h1>
		<p class="gt-hero__lead">Montañas, café de altura, miel de flores nativas y la calidez de la gente paisa, a solo 30 minutos de Medellín.</p>
		<div class="gt-hero__actions">
			<a class="gt-btn gt-btn--miel" href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Ver alojamientos</a>
			<a class="gt-btn gt-btn--ghost-light" href="<?php echo esc_url( home_url( '/tours/' ) ); ?>">Ver experiencias</a>
		</div>
		<div class="gt-quick">
			<span class="gt-quick__label">¿Qué quieres vivir?</span>
			<div class="gt-quick__chips">
				<a href="<?php echo esc_url( home_url( '/reserva/domo-estrellas/' ) ); ?>">⛺ Glamping</a>
				<a href="<?php echo esc_url( home_url( '/reserva/cabana-del-bosque/' ) ); ?>">🌲 Cabañas</a>
				<a href="<?php echo esc_url( home_url( '/reserva/finca-tradicional-paisa/' ) ); ?>">🏡 Fincas</a>
				<a href="<?php echo esc_url( home_url( '/reserva/ruta-del-cafe/' ) ); ?>">☕ Ruta del café</a>
				<a href="<?php echo esc_url( home_url( '/reserva/aventura-extrema/' ) ); ?>">🧗 Aventura</a>
				<a href="<?php echo esc_url( home_url( '/reserva/cata-de-miel/' ) ); ?>">🍯 Cata de miel</a>
			</div>
		</div>
	</div>
</section>

<section class="gt-trust" aria-label="Por qué reservar con nosotros">
	<div class="gt-container">
		<ul>
			<li><span class="gt-trust__icon" aria-hidden="true">📍</span><span><strong>A 30 min de Medellín</strong>Norte del Valle de Aburrá</span></li>
			<li><span class="gt-trust__icon" aria-hidden="true">🤝</span><span><strong>Anfitriones locales</strong>Campesinos, guías y apicultores</span></li>
			<li><span class="gt-trust__icon" aria-hidden="true">🔒</span><span><strong>Reserva en línea</strong>Precios en COP, sin sorpresas</span></li>
			<li><span class="gt-trust__icon" aria-hidden="true">💬</span><span><strong>Confirmación por WhatsApp</strong>Te acompañamos en todo</span></li>
		</ul>
	</div>
</section>

<section class="gt-section">
	<div class="gt-container">
		<div class="gt-head-row">
			<div>
				<p class="gt-eyebrow">Experiencias destacadas</p>
				<h2 class="gt-h2">Tours en grupos pequeños con guías de la región</h2>
				<p class="gt-lead">Diseñados con campesinos, apicultores y guías de aventura de Girardota.</p>
			</div>
			<a class="gt-link-arrow" href="<?php echo esc_url( home_url( '/tours/' ) ); ?>">Todos los tours</a>
		</div>
		<?php echo $sc( '[gt_tours]' ); // phpcs:ignore ?>
	</div>
</section>

<section class="gt-section gt-section--alt">
	<div class="gt-container">
		<div class="gt-head-row">
			<div>
				<p class="gt-eyebrow">Dónde dormir</p>
				<h2 class="gt-h2">Glamping, cabañas, ecohoteles y fincas paisas</h2>
				<p class="gt-lead">Despierta entre montañas y niebla, con desayuno campesino y el valle a tus pies.</p>
			</div>
			<a class="gt-link-arrow" href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Todos los alojamientos</a>
		</div>
		<?php echo $sc( '[gt_alojamientos filtros="no"]' ); // phpcs:ignore ?>
	</div>
</section>

<section class="gt-promise">
	<?php echo gt_img( 'pueblo-girardota.jpg', '', '', 'full' ); // phpcs:ignore ?>
	<div class="gt-container">
		<p class="gt-eyebrow">Nuestra promesa</p>
		<blockquote>“Cada reserva apoya directamente a las familias, guías y productores de Girardota, y protege los bosques, el agua y las abejas que hacen único este lugar.”</blockquote>
		<cite>— Equipo Girardota.com.co</cite>
	</div>
</section>

<section class="gt-section">
	<div class="gt-container">
		<div class="gt-head-row">
			<div>
				<p class="gt-eyebrow">Agenda</p>
				<h2 class="gt-h2">Próximos eventos</h2>
				<p class="gt-lead">Fiestas religiosas, culturales y gastronómicas para planear tu visita.</p>
			</div>
			<a class="gt-link-arrow" href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Ver todos los eventos</a>
		</div>
		<?php echo $sc( '[gt_eventos limite="4"]' ); // phpcs:ignore ?>
	</div>
</section>

<section class="gt-section gt-section--alt">
	<div class="gt-container">
		<p class="gt-eyebrow">Sabores y cultura</p>
		<h2 class="gt-h2">Descubre Girardota</h2>
		<p class="gt-lead">Restaurantes tradicionales, cafés de especialidad, fiestas y las últimas noticias para vivir el municipio como un local.</p>
		<div class="gt-discover">
			<a href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>">
				<?php echo gt_img( 'gastronomia-local.jpg', '' ); // phpcs:ignore ?>
				<span><strong>Restaurantes y cafés</strong><small>Trucha, chicharrón y café de origen</small></span>
			</a>
			<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">
				<?php echo gt_img( 'fiestas-danza-sainete.jpg', '' ); // phpcs:ignore ?>
				<span><strong>Agenda de eventos</strong><small>Fiestas y tradiciones todo el año</small></span>
			</a>
			<a href="<?php echo esc_url( home_url( '/girardota/' ) ); ?>">
				<?php echo gt_img( 'pueblo-girardota.jpg', '' ); // phpcs:ignore ?>
				<span><strong>Conoce Girardota</strong><small>Historia, fe y vida de pueblo</small></span>
			</a>
		</div>
	</div>
</section>

<?php
$news = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
if ( $news->have_posts() ) :
	?>
	<section class="gt-section">
		<div class="gt-container">
			<div class="gt-head-row">
				<div>
					<p class="gt-eyebrow">Noticias y guías</p>
					<h2 class="gt-h2">Lo que pasa en Girardota</h2>
				</div>
				<a class="gt-link-arrow" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/noticias/' ) ); ?>">Ver todas</a>
			</div>
			<div class="gt-posts">
				<?php
				while ( $news->have_posts() ) {
					$news->the_post();
					gt_post_card();
				}
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="gt-cta">
	<div class="gt-container gt-cta__inner">
		<div>
			<p class="gt-eyebrow">Aliados</p>
			<h2 class="gt-h2">¿Tienes un alojamiento, tour o restaurante en Girardota?</h2>
			<p>Súmate a la plataforma y llega a más viajeros que buscan escapadas auténticas cerca de Medellín.</p>
		</div>
		<div class="gt-cta__actions">
			<a class="gt-btn gt-btn--light" href="<?php echo esc_url( home_url( '/nosotros/#contacto' ) ); ?>">Quiero ser aliado</a>
			<a class="gt-btn gt-btn--ghost-light" href="<?php echo esc_url( gt_wa( 'Hola, quiero ser aliado de Girardota.com.co' ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
		</div>
	</div>
</section>

<?php
get_footer();

<?php
get_header();
while ( have_posts() ) :
	the_post();
	$type = get_post_type();

	if ( 'evento' === $type ) :
		$fecha = get_post_meta( get_the_ID(), '_gt_fecha_texto', true );
		$lugar = get_post_meta( get_the_ID(), '_gt_lugar', true );
		gt_page_hero( get_the_title(), $fecha . ( $lugar ? ' · ' . $lugar : '' ), 0, true );
		?>
		<section class="gt-section">
			<div class="gt-container gt-event-box">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="gt-event-box__poster"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>
				<div>
					<ul class="gt-facts">
						<?php if ( $fecha ) : ?><li><strong>Fecha:</strong> <?php echo esc_html( $fecha ); ?></li><?php endif; ?>
						<?php if ( $lugar ) : ?><li><strong>Lugar:</strong> <?php echo esc_html( $lugar ); ?></li><?php endif; ?>
					</ul>
					<div class="entry-content"><?php the_content(); ?></div>
					<div class="gt-share">
						<a class="gt-btn" href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">Reservar alojamiento</a>
						<a class="gt-btn gt-btn--ghost" href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Ver toda la agenda</a>
					</div>
				</div>
			</div>
		</section>
		<?php

	elseif ( 'lugar' === $type ) :
		$tags = get_post_meta( get_the_ID(), '_gt_etiquetas', true );
		$dir  = get_post_meta( get_the_ID(), '_gt_direccion', true );
		$wa   = preg_replace( '/\D/', '', (string) get_post_meta( get_the_ID(), '_gt_whatsapp', true ) );
		$ig   = get_post_meta( get_the_ID(), '_gt_instagram', true );
		gt_page_hero( get_the_title(), $tags, 0, true );
		?>
		<article class="gt-article">
			<?php if ( has_post_thumbnail() ) : ?><div class="gt-article__img"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<ul class="gt-facts">
				<?php if ( $dir ) : ?><li><strong>Dirección:</strong> <?php echo esc_html( $dir ); ?>, Girardota</li><?php endif; ?>
			</ul>
			<div class="gt-share">
				<?php if ( $wa ) : ?><a class="gt-btn gt-btn--wa" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
				<?php if ( $ig ) : ?><a class="gt-btn gt-btn--ghost" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
				<a class="gt-link-arrow" href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>">Más restaurantes y cafés</a>
			</div>
		</article>
		<?php

	else :
		$cat = get_the_category();
		gt_page_hero( get_the_title(), '', 0, true );
		?>
		<article class="gt-article">
			<p class="gt-article__meta"><?php echo $cat ? esc_html( $cat[0]->name ) . ' · ' : ''; ?><?php echo esc_html( get_the_date() ); ?></p>
			<?php if ( has_post_thumbnail() ) : ?><div class="gt-article__img"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></div><?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<div class="gt-share">
				<strong>Compartir:</strong>
				<?php
				// Enlace público fijo en https://girardota.com.co (los puntos van como %2E para que no se reescriban).
				$gt_share = str_replace( '.', '%2E', rawurlencode( 'https://girardota.com.co' . wp_make_link_relative( get_permalink() ) ) );
				?>
				<a href="https://wa.me/?text=<?php echo esc_attr( rawurlencode( get_the_title() . ' ' ) . $gt_share ); ?>" target="_blank" rel="noopener">WhatsApp</a>
				<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $gt_share ); ?>" target="_blank" rel="noopener">Facebook</a>
			</div>
		</article>
		<?php
		$gt_more = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ), 'no_found_rows' => true ) );
		if ( $gt_more->have_posts() ) :
			?>
			<section class="gt-section gt-section--alt">
				<div class="gt-container">
					<h2 class="gt-h2">Sigue leyendo</h2>
					<div class="gt-posts">
						<?php
						while ( $gt_more->have_posts() ) {
							$gt_more->the_post();
							gt_post_card();
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;
endwhile;
get_footer();

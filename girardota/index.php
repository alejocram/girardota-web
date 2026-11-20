<?php
get_header();

if ( is_home() ) {
	$pid = (int) get_option( 'page_for_posts' );
	gt_page_hero( 'Noticias y guías', 'Noticias, guías y planes para vivir Girardota como un local.', $pid ? get_post_thumbnail_id( $pid ) : 0 );
} elseif ( is_search() ) {
	gt_page_hero( 'Resultados para “' . get_search_query() . '”', '', 0, true );
} elseif ( is_archive() ) {
	gt_page_hero( wp_strip_all_tags( get_the_archive_title() ), wp_strip_all_tags( get_the_archive_description() ), 0, true );
} else {
	gt_page_hero( get_bloginfo( 'name' ), '', 0, true );
}
?>
<section class="gt-section">
	<div class="gt-container">
		<?php if ( have_posts() ) : ?>
			<div class="gt-posts">
				<?php
				while ( have_posts() ) {
					the_post();
					gt_post_card();
				}
				?>
			</div>
			<nav class="gt-pagination" aria-label="Paginación">
				<?php echo paginate_links( array( 'prev_text' => '← Anteriores', 'next_text' => 'Siguientes →' ) ); // phpcs:ignore ?>
			</nav>
		<?php else : ?>
			<p class="gt-lead">No encontramos contenido. Vuelve al <a href="<?php echo esc_url( home_url( '/' ) ); ?>">inicio</a> o mira nuestros <a href="<?php echo esc_url( home_url( '/alojamientos/' ) ); ?>">alojamientos</a>.</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();

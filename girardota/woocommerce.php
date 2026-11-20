<?php
get_header();

if ( is_product() ) {
	echo '<div class="gt-page-body"><div class="gt-container">';
	woocommerce_content();
	echo '</div></div>';
} else {
	$title = 'Reservar';
	$sub   = 'Alojamientos y experiencias en Girardota con anfitriones locales.';
	if ( is_product_taxonomy() ) {
		$term  = get_queried_object();
		$title = $term->name;
		$sub   = $term->description ? wp_strip_all_tags( $term->description ) : '';
	} elseif ( is_search() ) {
		$title = 'Resultados para “' . get_search_query() . '”';
		$sub   = '';
	}
	gt_page_hero( $title, $sub, 0, true );
	echo '<div class="gt-page-body"><div class="gt-container">';
	woocommerce_content();
	echo '</div></div>';
}

get_footer();

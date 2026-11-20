<?php
get_header();
while ( have_posts() ) :
	the_post();
	$is_wc   = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
	$content = get_the_content();
	$full    = false !== strpos( $content, 'gt-section' ) || false !== strpos( $content, 'gt-cta' );
	gt_page_hero( get_the_title(), $is_wc ? '' : ( has_excerpt() ? get_the_excerpt() : '' ), $is_wc ? 0 : get_post_thumbnail_id(), $is_wc );
	if ( $full ) {
		echo '<div class="gt-page-full">';
		the_content();
		echo '</div>';
	} else {
		echo '<div class="gt-page-body"><div class="gt-container"><div class="entry-content">';
		the_content();
		echo '</div></div></div>';
	}
endwhile;
get_footer();

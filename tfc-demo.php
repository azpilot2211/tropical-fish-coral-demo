<?php
/*
Plugin Name: TFC Demo images
Description: Public-demo helper. Shows a product's photo from the link stored in its `_tfc_img_url` field, so the demo needs no image uploads.
*/
// Some supplier sites refuse images requested from other sites; sending no referrer avoids that.
add_action( 'wp_head', function () { echo '<meta name="referrer" content="no-referrer">' . "\n"; }, 1 );

add_filter( 'woocommerce_placeholder_img_src', function ( $src ) {
	global $product;
	if ( $product instanceof WC_Product ) {
		$url = get_post_meta( $product->get_parent_id() ?: $product->get_id(), '_tfc_img_url', true );
		if ( $url ) return esc_url( $url );
	}
	return $src;
}, 20 );

<?php
// Runs once when the public demo starts: opens the store, creates the demo products, adds sample shipping rates.
require '/wordpress/wp-load.php';

update_option( 'blogname', 'Tropical Fish & Coral' );
update_option( 'blogdescription', 'Live goods, dry goods and reef supplies' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option( 'woocommerce_default_country', 'US:CA' );
update_option( 'permalink_structure', '/%postname%/' );

$data = json_decode( file_get_contents( '/wordpress/demo-products.json' ), true ) ?: [];
foreach ( $data as $row ) {
	$term = get_term_by( 'slug', $row['cat'], 'product_cat' );
	if ( ! $term ) continue;
	$sizes = ! empty( $row['sizes'] );
	$p     = $sizes ? new WC_Product_Variable() : new WC_Product_Simple();
	$p->set_name( $row['name'] );
	$p->set_status( 'publish' );
	$p->set_description( $row['desc'] );
	$p->set_category_ids( [ $term->term_id ] );
	if ( $row['weight'] !== '' ) $p->set_weight( $row['weight'] );
	if ( $sizes ) {
		$attr = new WC_Product_Attribute();
		$attr->set_name( 'Size' );
		$attr->set_options( array_column( $row['sizes'], 'size' ) );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$p->set_attributes( [ $attr ] );
	} else {
		$p->set_regular_price( $row['price'] );
		$p->set_stock_status( 'instock' );
	}
	$pid = $p->save();
	if ( ! empty( $row['image'] ) ) update_post_meta( $pid, '_tfc_img_url', $row['image'] );
	if ( $sizes ) {
		foreach ( $row['sizes'] as $s ) {
			$v = new WC_Product_Variation();
			$v->set_parent_id( $pid );
			$v->set_attributes( [ 'size' => $s['size'] ] );
			$v->set_regular_price( $s['price'] );
			$v->set_stock_status( $s['stock'] ? 'instock' : 'outofstock' );
			$v->save();
		}
		WC_Product_Variable::sync( $pid );
	}
}

// Sample state rates so the cart and checkout show shipping (edit under WooCommerce > Shipping by State).
$rates = [];
foreach ( array_keys( WC()->countries->get_states( 'US' ) ) as $st ) {
	$rates[ $st ] = [ 'live_base' => 35, 'live_lb' => 2, 'dry_base' => 9, 'dry_lb' => 0.5, 'live' => ! in_array( $st, [ 'AK', 'HI' ], true ) ];
}
update_option( 'tfc_sr_rates', $rates );
if ( function_exists( 'tfc_sr_ensure_zone' ) ) tfc_sr_ensure_zone();

// Category counts feed the home-page tiles.
$terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids' ] );
if ( function_exists( '_wc_term_recount' ) ) _wc_term_recount( $terms, get_taxonomy( 'product_cat' ), true, false );
wc_delete_product_transients();
flush_rewrite_rules();

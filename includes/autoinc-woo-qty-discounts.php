<?php
add_action( 'woocommerce_before_calculate_totals', 'epim_quantity_based_pricing', 9999 );

function epim_quantity_based_pricing( $cart ) {

    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

    // Define discount rules and thresholds
    $threshold1 = 101; // Change price if items > 100
    $discount1 = 0.05; // Reduce unit price by 5%
    $threshold2 = 1001; // Change price if items > 1000
    $discount2 = 0.1; // Reduce unit price by 10%

    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $id = $cart_item['data']->get_id();

        $epim_threshold1 = isInteger(get_post_meta($id,'epim_Qty_Break_1',true));
        $epim_threshold2 = isInteger(get_post_meta($id,'epim_Qty_Break_2',true));
        $epim_threshold3 = isInteger(get_post_meta($id,'epim_Qty_Break_3',true));

        $epim_price1 = get_post_meta($id,'epim_Qty_Price_1',true);
        $epim_price2 = get_post_meta($id,'epim_Qty_Price_2',true);
        $epim_price3 = get_post_meta($id,'epim_Qty_Price_3',true);

        $price = $cart_item['data']->get_price();

        if(($epim_price1 != '')||($epim_price2 != '')||($epim_price3 != '')) {
            if(($epim_threshold1)&&($epim_price1 != '')) {
                if($cart_item['quantity'] >= $epim_threshold1) {
                    $price = $epim_price1;
                }
            }
            if(($epim_threshold2)&&($epim_price2 != '')) {
                if($cart_item['quantity'] >= $epim_threshold2) {
                    $price = $epim_price2;
                }
            }
            if(($epim_threshold3)&&($epim_price3 != '')) {
                if($cart_item['quantity'] >= $epim_threshold3) {
                    $price = $epim_price3;
                }
            }
        }

        $cart_item['data']->set_price( $price );

    }

}

function isInteger($value) {
    if(!$value) {
        return false;
    }
    if($value=='') {
        return false;
    }
    if(!ctype_digit($value)) {
        return false;
    }
    return (int)$value;
}
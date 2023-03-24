<?php
/*
Plugin Name: Bulk Price Update
Plugin URI: https://github.com/Matthewpco/WP-Plugin-Bulk-Price-Update
Description: A plugin that updates the price of all WooCommerce products and variations by a percentage.
Author: Gary Matthew Payne
Version: 1.1.0
Author URI: https://wpwebdevelopment.com
*/

// Create the new dashboard menu
function add_custom_menu() {
    add_menu_page(
        'Bulk Price Update',
        'Bulk Price Update',
        'manage_options',
        'update-product-price',
        'price_increase_callback'
    );
}

// Add custom menu to dashboard
add_action( 'admin_menu', 'add_custom_menu' );

// When form info is submitted, amount of increase is sent, and function is triggered.
function price_increase_callback() {

    if ( isset( $_POST['price_increase'] ) ) {
        increase_product_price();
    }

    ?>

    <h2>Enter percentage amount to update all product and variation prices:</h2>
    <form method="post">
        <label for="price_increase">Price Increase:</label>
        <input type="number" name="price_increase" id="price_increase" value="10" min="0" max="100">
        <input type="submit" value="Update Prices">
    </form>

    <?php
}

// Check for products and variations then loop through all and increase price by defined amount
function increase_product_price() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'offset' => 0,
    );
    $products = new WP_Query( $args );
    if ( $products->have_posts() ) {
        while ( $products->have_posts() ) {
            $products->the_post();
            $product = wc_get_product( get_the_ID() );
            if ( ! $product ) {
                continue;
            }
            if ( $product->is_type( 'variable' ) ) {
                foreach ( $product->get_children() as $child_id ) {
                    $variation = wc_get_product( $child_id );
                    if ( ! $variation ) {
                        continue;
                    }
                    $price = $variation->get_price();
                    if ( ! is_numeric( $price ) ) {
                        echo '<script>console.error("Price is not numeric for variation ID ' . esc_js( $child_id ) . '");</script>';
                        continue;
                    }
                    $new_price = $price * ( 1 + ( $_POST['price_increase'] / 100 ) );
                    update_post_meta( $child_id, '_price', $new_price );
                    $variation->set_regular_price( $new_price );
                    $variation->set_sale_price( $new_price );
                    $variation->set_price( $new_price );
                    if ( ! $variation->save() ) {
                        echo '<script>console.error("Error saving variation ID ' . esc_js( $child_id ) . ': ' . esc_js( wc_get_notices_error_messages() ) . '");</script>';
                    }
                }
            } else {
                $price = $product->get_price();
                if ( ! is_numeric( $price ) ) {
                    echo '<script>console.error("Price is not numeric for product ID ' . esc_js( get_the_ID() ) . '");</script>';
                    continue;
                }
                $new_price = $price * ( 1 + ( $_POST['price_increase'] / 100 ) );
                update_post_meta( get_the_ID(), '_price', $new_price );
                $product->set_regular_price( $new_price );
                $product->set_sale_price( $new_price );
                $product->set_price( $new_price );
                if ( ! $product->save() ) {
                    echo '<script>console.error("Error saving product ID ' . esc_js( get_the_ID() ) . ': ' . esc_js( wc_get_notices_error_messages() ) . '");</script>';
                }
            }
        }
    }

    echo '<div class="notice notice-success is-dismissible"><p>Product prices have been updated successfully.</p></div>';
}
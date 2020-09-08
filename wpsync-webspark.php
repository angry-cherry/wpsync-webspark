<?php

/**
 * Plugin Name: Wpsync Webspark
 */

define( 'PRODUCT_MAX_COUNT', 10000 );

if ( !wp_next_scheduled( 'cron_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'cron_hook' );
}

add_action( 'cron_hook', refresh_data() );

add_filter( 'set_screen_option_'.'lisense_table_per_page', function( $status, $option, $value ) {
    return (int) $value;
}, 10, 3 );

add_filter( 'set-screen-option', function( $status, $option, $value ) {
    return ( $option == 'lisense_table_per_page' ) ? (int) $value : $status;
}, 10, 3 );

add_action( 'admin_menu', function() {
    $hook = add_menu_page( 'Products', 'Products', 'manage_options', 'page-slug', 'example_table_page', 'dashicons-products', 100 );
    add_action( "load-$hook", 'example_table_page_load' );
} );

function example_table_page_load() {
    require_once __DIR__ . '/class-Example_List_Table.php';

    $GLOBALS['Example_List_Table'] = new Example_List_Table();
}

function example_table_page() {
    ?>
    <div class="wrap">
        <h2><?php echo get_admin_page_title() ?></h2>

        <?php
        echo '<form action="" method="POST">';
        $GLOBALS['Example_List_Table']->display();
        echo '</form>';
        ?>

    </div>

    <?php
}

function get_data() {
    $url = 'https://my.api.mockaroo.com/products.json?key=89b23a40';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = curl_exec($ch);

    return $data;
}

function refresh_data() {
    global $wpdb;

    $data = json_decode( get_data(), true );
    $count_products = $wpdb->get_results("SELECT COUNT(sku) FROM products" );

    foreach ( $data as $item ) {
        $sku = $item['sku'];
        $data_db = $wpdb->get_row( "SELECT * FROM products WHERE sku = '$sku'" );

        if ( empty( $data_db ) && $count_products < PRODUCT_MAX_COUNT) {

            $wpdb->insert(
            'products',
                [
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'picture' => $item['picture'],
                    'in_stock' => $item['in_stock'],
                ], [
                    '%s',
                    '%s',
                    '%s',
                    '%f',
                    '%s',
                    '%d',
                ]
            );
        } elseif ( ! empty( $data_db ) ) {
            $wpdb->update(
                'products',
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'picture' => $item['picture'],
                    'in_stock' => $item['in_stock'],
                ],
                [
                    'sku' => $sku,
                ],
                [
                    '%s',
                    '%s',
                    '%f',
                    '%s',
                    '%d',
                ],
                [
                    '%s',
                ]
            );
        }

    }

    return true;
}

function order_create( $order ) {
    $url = 'https://my.api.mockaroo.com/products.json?key=89b23a40';
    $ch = curl_init();
    $test = [
        (object)[
            "sku" => "566fe0cb-9261-41f8-95d2-479cb41497ac",
            "items" => 1
        ],
        (object)[
            "sku" => "83878239-bd0f-419c-9ed0-cbbffb288cb6",
            "items" => 3,
        ]
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    curl_close ($ch);

    return $server_output;
}
<?php

/*

Plugin Name: RunByIT ItaliaStyle export for Meble.pl

Plugin URI: https://runbyit.com/

Description: Plugin for export data to Meble.pl.

Version: 1.0.1

Author: Oleksii Yurchenko

Author URI: https://runbyit.com/

Text Domain: rbit-meble-pl-export

*/

//$this->tableSklepXmlSklepmebleAsortyment = DB_PREFIX . 'sklep_xml_sklepmeble_asortyment';
//$this->tableSklepXmlSklepmebleKategorieCech = DB_PREFIX . 'sklep_xml_sklepmeble_kategorie_cech';
//$this->tableSklepXmlSklepmebleCechy = DB_PREFIX . 'sklep_xml_sklepmeble_cechy';

require_once 'meble-pl-functions.php';


function rbitExportProduktyXMLNewMethod() {
    //$this->conf->vars['server_addr'] = 'https://www.italiastyle.pl';
    $courier_shipping_methods = [];

    if (class_exists('RBIT_Courier_Shipping_Method')) {
        $This_Shipping_Method = new RBIT_Courier_Shipping_Method();

        $courier_shipping_method_id = (int)$This_Shipping_Method->settings['rbit_shipping_method_id'];
        $courier_shipping_method_title = (string)$This_Shipping_Method->settings['title'];
        $courier_shipping_methods[$courier_shipping_method_id]['title'] = $courier_shipping_method_title;
    }

    $upload_dir = wp_upload_dir();
    $upload_subfolder = 'meble-pl';
    $meble_pl_filename = 'meble-pl.xml';
    $meble_pl_file = $upload_dir['basedir'] . '/'.$upload_subfolder.'/' . $meble_pl_filename;

    $f = fopen($meble_pl_file, 'w');


    $gateways = WC()->payment_gateways->payment_gateways();
    $payment_methods = [];
    foreach ( $gateways as $id => $gateway ) {
        $payment_methods[$id] = $gateway->get_method_title();
    }

    $server_addr = 'https://www.italiastyle.pl';

    $no_image_url = plugin_dir_url(__FILE__) . 'img/no-image2.png';

    # poczatek xml
    $xml_content = (string) '';
    $xml_content .= '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml_content .= '<oferta>'. PHP_EOL;
    $xml_content .= '<data_utworzenia>' . date('Y-m-d H:i:s') . '</data_utworzenia>'. PHP_EOL;
    $xml_content .= '<produkty>'. PHP_EOL;

    fwrite($f, $xml_content);
    // echo "string";
    # lista produktow, na poczatek potrzebne id, by w petli wczytac wszystko co potrzebne
    //$produkty_by_cat = $this->loadAllProduktyGroupedByCategory(null,"AND p.xml_sklepy=1");
    $products = loadAllProductsIDs(2000);

    $tekst_description_opcje = '</br><p><b>Prosimy o dokonanie wyboru opcji produktu podczas zlożenia zamówienia!</b></p>';

    $xml_content = '';

    foreach ($products as $product_id) {
        $product_obj = wc_get_product( $product_id );
        $opis_temp = $product_obj->get_description();
        //$prod_temp = $this->LoadProductById($pbc['id']);
        //$cat_temp = $this->kategorie->loadCategoryById($prod_temp['category_id']);
        //$opis_temp = $this->LoadOpisById($pbc['id'])[1];
        $kolekcja_temp = getProductCollectionName($product_id); // bierze tylko jedna
        $producent = get_field('product_manufacturer', $product_id);
        $producent_title = $producent->post_title ? $producent->post_title : 'Italia Style';


        # zawarosc xml - produkty
        $xml_content .= '<produkt outlet="' . (( $product_obj->get_stock_status() == 1) ? 'tak' : 'nie') . '">'. PHP_EOL;
        $xml_content .= '<id>' . $product_id . '</id>'. PHP_EOL;
        $xml_content .= '<nazwa>' . rbitWrapToCData($product_obj->get_title()) . '</nazwa>'. PHP_EOL;

        $xml_content .= '<opis>' . rbitWrapToCData($product_obj->get_description()) . '</opis>'. PHP_EOL;

        if (!empty($product_obj->get_image_id())) {
            $product_image_src = wp_get_attachment_image_src( $product_obj->get_image_id(), 'woocommerce_thumbnail' );
            $xml_content .= '<zdjecia>'. PHP_EOL;
            $xml_content .= '<zdjecie>'. PHP_EOL;
            $xml_content .= $product_image_src[0];
            $xml_content .= '</zdjecie>'. PHP_EOL;
            foreach ($product_obj->get_gallery_image_ids() as $prod_img_id) {
                $product_gallery_src = wp_get_attachment_image_src( $prod_img_id, 'woocommerce_thumbnail' );
                $xml_content .= '<zdjecie>'. PHP_EOL;
                $xml_content .= $product_gallery_src[0];
                $xml_content .= '</zdjecie>'. PHP_EOL;
            }
            $xml_content .= '</zdjecia>'. PHP_EOL;
        } else {
            $xml_content .= '<zdjecia><zdjecie>'.$no_image_url.'</zdjecie></zdjecia>'. PHP_EOL;
        }
        $link = $product_obj->get_permalink();
        $xml_content .= '<link>' . (string) $link . '</link>'. PHP_EOL;
        $xml_content .= '<cena_brutto>' . ceil($product_obj->get_price()) . '</cena_brutto>'. PHP_EOL;
        $xml_content .= '<dostepnosc>' . get_field('dostepnosc', $product_id) . '</dostepnosc>'. PHP_EOL;
        $xml_content .= '<producent>'.str_replace('&', ' i ', $producent_title).'</producent>'. PHP_EOL;

        // if (!empty($prod_temp['t2p'])) {
        //     $xml_content .= $this->exportCreateTransport($prod_temp['t2p']);
        // }

        # cechy
        $xml_content .= rbitExportCreateCechy($product_id);

        $product_stan = get_field('stan', $product_id);
        if (!empty($product_stan)) {
            $xml_content .= '<stan>' . $product_stan . '</stan>'. PHP_EOL;
        }
        if (!empty($kolekcja_temp)) {
            $xml_content .= '<kolekcja>' . str_replace("&", "&amp;", $kolekcja_temp) . '</kolekcja>'. PHP_EOL;
        }
        # opcje
        // TODO options export. in old system options commented
        if(function_exists('rbit_get_json_for_front_v2')) {
            $options_array = rbit_options_for_meble_pl($product_id);
            if(count($options_array)) {
                $xml_content .= '<opcje>'. PHP_EOL;
                foreach ($options_array as $group_meble_pl_name => $groups_array) {
                    $xml_content .= '<typ_opcji nazwa="'.$group_meble_pl_name.'">'. PHP_EOL;
                    foreach ($groups_array as $group_id => $group_array) {
                        foreach ($group_array['options'] as $option_d => $option_array) {
                            $xml_content .= '<grupa_opcji nazwa="'.$option_array['title'].'">'. PHP_EOL;
                            foreach ($option_array['values'] as $value_id => $value_array) {
                                $value_price = is_numeric($value_array['cost']) ? ceil($value_array['cost']) : 0;
                                $xml_content .= '<opcja nazwa="'.$value_array['title'].'">'. PHP_EOL;
                                $xml_content .= '<cena>'.$value_price.'</cena>'.  PHP_EOL;
                                if(!empty($value_array['img'])) $xml_content .= '<ikona>'.$value_array['img'].'</ikona>'.  PHP_EOL;
                                $xml_content .= '</opcja>'. PHP_EOL;
                            }
                            $xml_content .= '</grupa_opcji>'. PHP_EOL;
                        }
                    }
                    $xml_content .= '</typ_opcji>'. PHP_EOL;
                }
                $xml_content .= '</opcje>'. PHP_EOL;

            }
        }

        $szerokosc = !empty($product_obj->get_width()) ? $product_obj->get_width() : get_field('szerokosc', $product_id);
        $wysokosc = !empty($product_obj->get_height()) ? $product_obj->get_height() : get_field('wysokosc', $product_id);
        $glebokosc = !empty($product_obj->get_length()) ? $product_obj->get_length() : get_field('glebokosc', $product_id);
        $waga = $product_obj->get_weight();
        $multi_sell = get_field('multi_sell', $product_id);
        $min_quantity = get_field('min_quantity', $product_id);
        if (!empty($szerokosc) || !empty($wysokosc) || !empty($glebokosc)) {
            $xml_content .= '<rozmiary>'. PHP_EOL;
            if (!empty($szerokosc)) {
                if (strpos($szerokosc, '-') !== false ) $szerokosc = explode('-', $szerokosc)[0];
                $xml_content .= '<szerokosc>' . $szerokosc . '</szerokosc>'. PHP_EOL;
            }
            if (!empty($wysokosc)) {
                if (strpos($wysokosc, '-') !== false ) $wysokosc = explode('-', $wysokosc)[0];
                $xml_content .= '<wysokosc>' . $wysokosc . '</wysokosc>'. PHP_EOL;
            }
            if (!empty($glebokosc)) {
                if (strpos($glebokosc, '-') !== false ) $glebokosc = explode('-', $glebokosc)[0];
                $xml_content .= '<glebokosc>' . $glebokosc . '</glebokosc>'. PHP_EOL;
            }
            $xml_content .= '</rozmiary>'. PHP_EOL;
        }

        if (!empty($waga)) {
            $xml_content .= '<waga jm="kg">' . $waga . '</waga>'. PHP_EOL;
        }

        // TODO shipping price and payment methods
        // using rbit_is_shipping_get_product_shipping_cost($product_id, $method_id)  from shipping cost calculation plugin

        if(function_exists('rbit_is_shipping_get_product_shipping_cost')) {
            if (count($courier_shipping_methods)) {
                $xml_content .= '<koszty_transportu>' . PHP_EOL;
                foreach ($courier_shipping_methods as $method_id => $method_data) {
                    $courier_shipping_cost = rbit_is_shipping_get_product_shipping_cost($product_id, $method_id);

                    foreach ($payment_methods as $id => $gateway) {
                        //$options[$id] = $gateway->get_method_title();
                        $xml_content .= '<koszt wysylka="' . $method_data['title'] . '" platnosc="' . $gateway . '">' . $courier_shipping_cost . '</koszt>' . PHP_EOL;//title
                    }

                }
                $xml_content .= '</koszty_transportu>' . PHP_EOL;
            }
        }

        if (!empty($multi_sell) || !empty($min_quantity)) {

            $xml_content .= '<atrybuty>'. PHP_EOL;

            if (!empty($multi_sell)) {
                $xml_content .= '<atrybut nazwa="multi_sell">' . $multi_sell . '</atrybut>'. PHP_EOL;
            }
            if (!empty($min_quantity)) {
                $xml_content .= '<atrybut nazwa="min_quantity">' . $min_quantity . '</atrybut>'. PHP_EOL;
            }

            $xml_content .= '</atrybuty>'. PHP_EOL;
        }

        $xml_content .= '</produkt>'. PHP_EOL;

        //unset($prod_temp);
        //unset($cat_temp);
        //unset($opis_temp);
        //unset($kolekcja_temp);

        unset($product_obj);

        //
        if (strlen($xml_content) > 2000000) {
            fwrite($f, $xml_content);
            $xml_content = '';
        }

    }

//    if (strlen($xml_content) > 0) {
//        fwrite($f, $xml_content);
//    }

    # zakonczenie xml
    $xml_content .= '</produkty>'. PHP_EOL;
    $xml_content .= '</oferta>'. PHP_EOL;

    fwrite($f, $xml_content);


    fclose($f);
    die();
}

function rbit_cli_register_commands_meble_pl_export() {
    WP_CLI::add_command( 'rbit_meble_pl_export', 'rbitExportProduktyXMLNewMethod' );
}

add_action( 'cli_init', 'rbit_cli_register_commands_meble_pl_export' );

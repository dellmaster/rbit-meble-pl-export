<?php

/**
 * @param $string string Polish string
 * @return string slug string
 */
function parseSTS($string) {
    return trim(str_replace(array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', ' '), array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', '_'), mb_strtolower($string, 'UTF-8')));
}

/**
 * @param $limit int products quantity limit
 * @return array returns array of product IDs
 */
function loadAllProductsIDs($limit = 100000) {
    $args = array(
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => 'publish',
        'return' => 'ids',
        //'offset' => 110,
        'limit' => $limit,

    );
    $product_ids = wc_get_products( $args );

    return $product_ids;
}



function loadSpisCech() {

}

/**
 * @param $cat_ids array of categories IDs
 * @return string mapped category name
 */
function getAsortymentByCategoryId($cat_ids) {
    return '';
}

/**
 * @param $str string string to wrap
 * @return string wrapped string
 */
function rbitWrapToCData($str) {
    $str_out = '<![CDATA[' . (string) $str . ']]>';
    return $str_out;
}

/**
 * @param $product_id int product ID
 * @return false|string returns Collection name or false if product dont have Collection or error
 */
function getProductCollectionName($product_id) {
    $term_ids = wc_get_product_term_ids( $product_id, 'is_collection_tag' );
    if (isset($term_ids[0])) {
        $term_obj = get_term($term_ids[0], 'is_collection_tag');
        if (!is_wp_error($term_obj))
        {
            return $term_obj->name;
        }
    }
    return false;

}


function getProductProducentName($product_id) {
    $term_ids = wc_get_product_term_ids( $product_id, 'is_collection_tag' );
    if (isset($term_ids[0])) {
        $term_obj = get_term($term_ids[0], 'is_collection_tag');
        if (!is_wp_error($term_obj))
        {
            return $term_obj->name;
        }
    }
    return false;

}

/**
 * @param $product WC_Product Woo product object
 * @return string|bool
 */
function rbitExportCreateCechy($product_id) {
    $product = wc_get_product( $product_id );
    $attributes = $product->get_attributes();
    //WP_CLI::log('product - '.$product_id.'; cechy : '.count($attributes));
    if(count($attributes)){
        $xml='<cechy>';
    }else{
        return '';
    }
    foreach ($attributes as $attribute) {

        $name = $attribute->get_name();
        if ( $attribute->is_taxonomy() ) {
            $attribute_tax = $attribute->get_taxonomy_object();
            $xml.='<cecha nazwa="'.$attribute_tax->attribute_label.'">';
            //
            $ile=0;
            $attr_terms = $attribute->get_terms();
            foreach ($attr_terms as $attr_term) {
                if ($ile < 7) {
                    $xml.='<definicja>'.$attr_term->name.'</definicja>';
                }
                $ile++;

            }
            $xml.='</cecha>';
        }

    }
    $xml.='</cechy>';

    return $xml;

}


function rbit_prod_attributes_test_command() {
    $product_id = 15008;
    $product_obj = wc_get_product( $product_id );
    $attributes = $product_obj->get_attributes();
    foreach ($attributes as $attribute) {
        $attribute_tax = $attribute->get_taxonomy_object();
        //WP_CLI::log('attribute obj - '.print_r($attribute_tax, true));
        WP_CLI::log('attribute name - '.$attribute_tax->attribute_label);
        //WP_CLI::log('options : ');
        //WP_CLI::log(print_r($attribute->get_options(), true));
        WP_CLI::log('terms : ');
        //WP_CLI::log(print_r($attribute->get_terms(), true));
        $attr_terms = $attribute->get_terms();
        foreach ($attr_terms as $attr_term) {
            WP_CLI::log('term : ');
            WP_CLI::log($attr_term->name);
        }

    }
    //WP_CLI::log('attributes for product '.$product_id.' : ');
    //WP_CLI::log(print_r($attributes, true));
}
function rbit_cli_register_commands_prod_attributes_test() {
    WP_CLI::add_command( 'prod_attributes_test', 'rbit_prod_attributes_test_command' );
}

add_action( 'cli_init', 'rbit_cli_register_commands_prod_attributes_test' );


function rbit_options_for_meble_pl($product_id) {
    $groups_array = [];
    $product_meta_array = get_post_meta($product_id, 'rbit_product_values');
    $groups_array = json_decode($product_meta_array[0], true);

    //get groups, options and values ids for WP_Query
    $group_ids = [];
    $option_ids = [];
    $value_ids = [];
    foreach ($groups_array as $group_id => $options) {
        $group_ids[] = $group_id;
        foreach ($options as $option_id => $values) {
            $option_ids[] = $option_id;
            foreach ($values as $value_id => $costs) {
                $value_ids[] = $value_id;
            }
        }

    }

    $all_ids = array_merge($group_ids, $option_ids, $value_ids);

    $args = array(
        'post_type'=>['is_options_group', 'is_option', 'is_option_value'], //'is_options_group',
        'post__in'=>$all_ids,
        'status'=>'publish',
        'order'=>'ASC',
        'posts_per_page'=>-1
    );
    //query_posts($args);
    $news_query = new WP_Query( $args );
    $conf_posts_result = $news_query->posts;

    foreach ($conf_posts_result as $conf_post) {
        $conf_posts[$conf_post->ID] = $conf_post;
    }


    $output_array = [];
    $step = 1;
    foreach ($groups_array as $group_id => $options) {
        $group_meble_pl_name = get_field('typ_opcji_for_meblepl', $group_id) ? get_field('typ_opcji_for_meblepl', $group_id) : 'Kolory wykończenia';
        $current_group = $conf_posts[$group_id];
        $output_array[$group_meble_pl_name][$group_id]['id'] = $group_id;
        $output_array[$group_meble_pl_name][$group_id]['title'] = $current_group->post_title;
        //$output_array[$group_id]['meble_pl_title'] =
        $output_array[$group_meble_pl_name][$group_id]['step'] = $step;

        foreach ($options as $option_id => $values) {
            $current_option = $conf_posts[$option_id];
            $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['id'] = $option_id;
            $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['title'] = $current_option->post_title;

            foreach ($values as $value_id => $cost) {
                $current_value = $conf_posts[$value_id];
                $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['values'][$value_id]['id'] = $value_id;
                $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['values'][$value_id]['title'] = $current_value->post_title;

                if( has_post_thumbnail( $value_id) ) {
                    //$output_array[$group_id]['options'][$option_id]['values'][$value_id]['img'] = get_the_post_thumbnail_url($current_value, 'post-thumbnail');
                    //$output_array[$group_id]['options'][$option_id]['values'][$value_id]['img'] = get_the_post_thumbnail_url($current_value, 'values-tumb');
                    $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['values'][$value_id]['img'] = get_the_post_thumbnail_url($current_value, 'medium');
                    //$output_array[$group_id]['options'][$option_id]['values'][$value_id]['img'] = wp_get_attachment_image_url($current_value, 'values-tumb');
                }
                else {
                    $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['values'][$value_id]['img'] = '';
                }

                $output_array[$group_meble_pl_name][$group_id]['options'][$option_id]['values'][$value_id]['cost'] = $cost;

            }
        }
        $step++;
    }

    return $output_array;

}

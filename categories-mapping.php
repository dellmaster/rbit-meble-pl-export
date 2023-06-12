<?php

function rbit_category_dd_import() {
    //delete_option('rbit_dd_categories');
    // START save mapping options
    if ('save_mapping' == $_POST['meble_action']) {
        update_option('rbit_meble_categories', $_POST['rbit_meble_categories']);
        update_option('rbit_meble_categories_for_export', $_POST['rbit_meble_categories_for_export']);
    }
    // END save mapping options

    $meble_cats_option = get_option('rbit_meble_categories');
    $meble_cats_for_export_option = get_option('rbit_meble_categories_for_export');

    // get woo categories
    $orderby = 'name';
    $order = 'asc';
    $hide_empty = false ;
    $cat_args = array(
        'orderby'    => $orderby,
        'order'      => $order,
        'hide_empty' => $hide_empty,
    );

    $product_categories = get_terms( 'product_cat', $cat_args );

    ?>

    <div style="" class="rbit-settings-block">
        <h2>Meble.pl categories mapping</h2>
        <br>
        <br>
        <form action="" method="post">
            <span class="rbit-action-text">Save categories mapping: </span><input name="submit" class="button button-primary" type="submit" value="<?php echo "Save"; ?>" />
            <table class="rbit_table">
                <tr>
                    <th>
                        Import
                    </th>
                    <th>
                        Kategoria w italiastyle
                    </th>
                    <th>
                        Kategoria name for Meble.pl
                    </th>
                </tr>

                <?php
                $cat_num = 0;
                foreach($product_categories as $product_category)
                {
                    $product_category->term_id;
                    $product_category->name;

                    $export_checked = '';
                    if(isset($meble_cats_for_export_option[$product_category->term_id])
                        && !empty($meble_cats_for_export_option[$product_category->term_id]))
                    {
                        $export_checked = 'checked';
                    }

                    ?>
                    <tr class="">
                        <td>
                            <input type="checkbox" id="rbit_meble_categories_for_export" value='1' name="rbit_meble_categories_for_export[<?php echo $product_category->term_id; ?>]" <?php echo $export_checked; ?> >
                        </td>
                        <td>
                            <?php echo $product_category->name; ?>
                        </td>
                        <td>
                            <input type="text" id="rbit_meble_categories" name="rbit_meble_categories[<?php echo $product_category->term_id; ?>]" value="<? echo $meble_cats_option[$product_category->term_id]; ?>">
                        </td>
                    </tr>
                    <?php


                }
                ?>

            </table>
            <span class="rbit-action-text">Save categories mapping: </span><input type="hidden" name="meble_action" value="save_mapping">
            <input name="submit" class="button button-primary" type="submit" value="<?php echo "Save"; ?>" />
        </form>
    </div>

    <?php

}

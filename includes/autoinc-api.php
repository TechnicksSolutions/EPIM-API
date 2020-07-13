<?php
/*function displayJSON($apiCall)
{
    $allAttributes = new RecursiveIteratorIterator(new RecursiveArrayIterator(json_decode($apiCall, true)), RecursiveIteratorIterator::SELF_FIRST);
    echo '<div style="padding-bottom: 10px; padding-top: 10px; border-bottom: 1px solid black">';
    echo '<p>';
    //$indent = 1;
    foreach ($allAttributes as $key => $val) {
        if (is_array($val)) {
            echo "</p><p>$key:<br>";
        } else {
            echo "<span style='padding-left: 1em;'> $key => $val</span><br>";
        }
    }
    echo '</p>';
    echo '</div>';
}*/

/**
 *
 *
 ************************************ API Calls*****************************************
 *
 */

function make_api_call($url)
{
    $method = get_option('epim_api_retrieval_method');
    $epim_url = get_option('epim_url');
    if (substr($epim_url, -1 != '/')) {
        $epim_url .= '/';
    }
    $epim_url .= 'api/';
    if ($method == 'curl') {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $epim_url . $url);

        $headers = array();
        $headers[] = "Ocp-Apim-Subscription-Key: " . get_option('epim_key');

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $apiCall = curl_exec($ch);

        curl_close($ch);

        return $apiCall;
    } else {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Ocp-Apim-Subscription-Key: " . get_option('epim_key')
            )
        );
        $context = stream_context_create($opts);
        $apiCall = file_get_contents($epim_url . $url, false, $context);

        return $apiCall;
    }

}

function get_api_all_categories()
{
    return make_api_call('Categories');
}

function get_api_picture($id)
{
    $res = make_api_call('Pictures/' . $id);
    if ($id == '64746') {
        //error_log($res);
    }
    return $res;
}

function get_api_all_products()
{
    $apiCall = make_api_call('Products/');
    $allProducts = json_decode($apiCall);
    $TotalResults = $allProducts->TotalResults;

    return make_api_call('Products/?limit=' . $TotalResults);
}

function get_api_variation($id)
{
    return make_api_call('Variations/' . $id);
}

function get_api_all_attributes()
{
    return make_api_call('Attributes');
}

function get_api_product($id)
{
	return make_api_call('Products/' . $id);
}

function get_api_all_changed_products_since($datetime = '2002-10-02T10:00:00-00:00')
{
    $xdatetime = substr($datetime, 0, 10).'T10:00:00-00:00';
    $r = make_api_call('ProductsUpdatedSince?ChangedSinceUTC=' . $xdatetime);
    //$r = make_api_call('https://epim.azure-api.net/Grahams/api/ProductsUpdatedSince?ChangedSinceUTC=' . '2020-01-06T10:00:00-00:00');
    return $r;
}

/**
 *
 * *****************************Helpers*********************************************
 *
 */

function getAPIIDFromCode($code)
{
	$res = false;

	$productID = wc_get_product_id_by_sku($code);

	if($productID) {
		$APIID = get_post_meta($productID,'epim_API_ID',true);
		if($APIID) return $APIID;
	}

	return $res;
}

function getCategoryImages($id)
{
    $term = getCategoryFromId($id);
    $res = array();
    if ($term) {
        $term_id = $term->term_id;
        $api_picture_ids = get_term_meta($term_id, 'epim_api_picture_ids', true);
        $res = str_getcsv($api_picture_ids);
        //error_log($term->name.': picture IDS - '.print_r($res,true));
    } else {
        //error_log('Term not found for ID: '.$id);
    }
    return json_encode($res);
}

function getCategoryFromId($id)
{
    $res = false;
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    foreach ($terms as $term) {
        $term_id = $term->term_id;
        $api_id = get_term_meta($term_id, 'epim_api_id', true);
        if ($api_id == $id) {
            return $term;
        }
    }
    return $res;
}

function getTermFromID($id, $terms)
{
    $res = false;
    foreach ($terms as $term) {
        $apiID = get_term_meta($term->term_id, 'epim_api_id', true);
        //$apiID = get_field('api_id', $term);
        if ($apiID == $id) {
            $res = $term;
            break;
        }
    }

    return $res;
}

function getAttributeNameFromID($id, $attributes)
{
    $res = 'Name Not Found';
    foreach ($attributes as $attribute) {
        if ($attribute->Id == $id) {
            $res = $attribute->Name;
            break;
        }
    }

    return $res;
}

function imageIDfromAPIID($id)
{
    $res = false;

    if ($id != ''):
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'orderby' => 'post_date',
            'order' => 'desc',
            'posts_per_page' => '-1',
            'post_status' => 'inherit',
            'meta_key' => 'epim_api_id',
            'meta_value' => $id
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
            while ($loop->have_posts()) : $loop->the_post();
                $res = get_the_ID();
                break;
            endwhile;
        endif;

        wp_reset_postdata();
    endif;
    return $res;
}

function imageImported($id)
{
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'orderby' => 'post_date',
        'order' => 'desc',
        'posts_per_page' => '-1',
        'post_status' => 'inherit',
        'meta_key' => 'epim_api_id',
        'meta_value' => $id
    );
    $loop = new WP_Query($args);

    return $loop->have_posts();

}

function getProductFromID($productID, $variationID)
{
    $res = false;

    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'product',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'epim_API_ID',
                'value' => $productID
            ),
            array(
                'key' => 'epim_variation_ID',
                'value' => $variationID
            )
        )
    );

    $loop = new WP_Query($args);
    if ($loop->have_posts()):
        while ($loop->have_posts()) : $loop->the_post();
            $res = get_the_ID();
            //error_log('post ID for '.$productID. ' / '.$variationID. ' is '.$res);
            break;
        endwhile;
    endif;

    wp_reset_postdata();

    return $res;
}

/**
 *
 *
 *==============================Core Functions=================================
 *
 */

/**
 * @param $id
 * @param $name
 * @param $ParentID
 * @param $picture_webpath
 * @param $picture_ids
 *
 * @return string
 *
 * Create a Category
 *
 */
function create_category($id, $name, $ParentID, $picture_webpath, $picture_ids)
{
    $response = '';
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);
    $term = getTermFromID($id, $terms);
    if ($term) {
        wp_update_term($term->term_id, 'product_cat', array('name' => $name));
        update_term_meta($term->term_id, 'epim_api_id', $id);
        update_term_meta($term->term_id, 'epim_api_picture_link', $picture_webpath);
        update_term_meta($term->term_id, 'epim_api_parent_id', $ParentID);
        $pSuffix = '';
        $pField = '';
        if ($picture_ids) {
            foreach ($picture_ids as $picture_id) {
                $pField .= $pSuffix;
                $pSuffix = ',';
                $pField .= $picture_id;

            }
            if ($picture_ids[0]) {
                $jsonPicture = get_api_picture($picture_ids[0]);
                $picture = json_decode($jsonPicture);
                $response .= importPicture($picture->Id, $picture->WebPath) . '<br>';
                $attachmentID = imageIDfromAPIID($picture->Id);
                if ($attachmentID) {
                    update_term_meta($term->term_id, 'thumbnail_id', absint($attachmentID));
                }
            }
        }
        update_term_meta($term->term_id, 'epim_api_picture_ids', $pField);
        $response .= $name . ' Category Updated ';
    } else {
        $newTerm = wp_insert_term($name, 'product_cat');
        if (is_wp_error($newTerm)) {
            $response = $newTerm->get_error_message() . ' Creating API_ID=' . $id . ' Name=' . $name;
        } else {
            update_term_meta($newTerm['term_id'], 'epim_api_id', $id);
            update_term_meta($newTerm['term_id'], 'epim_api_parent_id', $ParentID);
            $pSuffix = '';
            $pField = '';
            if ($picture_ids) {
                foreach ($picture_ids as $picture_id) {
                    $pField .= $pSuffix;
                    $pSuffix = ',';
                    $pField .= $picture_id;
                }
                if ($picture_ids[0]) {
                    $jsonPicture = get_api_picture($picture_ids[0]);
                    $picture = json_decode($jsonPicture);
                    $response .= importPicture($picture->Id, $picture->WebPath) . '<br>';
                    $attachmentID = imageIDfromAPIID($picture->Id);
                    if ($attachmentID) {
                        update_term_meta($newTerm['term_id'], 'thumbnail_id', absint($attachmentID));
                    }
                }
            }
            update_term_meta($newTerm['term_id'], 'epim_api_picture_ids', $pField);
            $response .= $name . ' Category Created';
        }
    }
    return $response;
}

/**
 *
 * Sort Categories
 *
 */
function sort_categories()
{
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);
    foreach ($terms as $term) {
        $api_parents = get_term_meta($term->term_id, 'epim_api_parent_id', true);
        if ($api_parents != '') {
            $parent = getTermFromID($api_parents, $terms);
            if ($parent) {
                $term_id = $term->term_id;

                $epim_api_id = get_term_meta($term_id, 'epim_api_id', true);
                $epim_api_parent_id = get_term_meta($term_id, 'epim_api_parent_id', true);
                $epim_api_picture_ids = get_term_meta($term_id, 'epim_api_picture_ids', true);
                $epim_api_picture_link = get_term_meta($term_id, 'epim_api_picture_link', true);

                wp_update_term($term_id, 'product_cat', array('parent' => $parent->term_id));

                update_term_meta($term_id, 'epim_api_id', $epim_api_id);
                update_term_meta($term_id, 'epim_api_parent_id', $epim_api_parent_id);
                update_term_meta($term_id, 'epim_api_picture_ids', $epim_api_picture_ids);
                update_term_meta($term_id, 'epim_api_picture_link', $epim_api_picture_link);
            }
        }
    }
}

/**
 *
 * Link Categories to Images
 *
 */

function linkCategoryImages()
{
    //error_log('Link Category Images Started');
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    foreach ($terms as $term) {
        $term_id = $term->term_id;
        $api_id = get_term_meta($term_id, 'epim_api_picture_ids', true);
        $attachmentID = imageIDfromAPIID($api_id);
        if ($attachmentID) {
            //error_log('linking image to '.$term->name);
            update_term_meta($term_id, 'thumbnail_id', absint($attachmentID));
            //update_field('image', $attachmentID, $term);
        }
    }
    //error_log('Link Category Images Ended');
}

/**
 * @param $id
 * @param $webpath
 *
 * @return string
 *
 * Import a picture
 *
 */
function importPicture($id, $webpath)
{

    $res = 'Image: ' . $id . ' - Import Error';
    if (!imageImported($id)) {
        $attachment_ID = uploadMedia($webpath);
        if ($attachment_ID) {
            //error_log('$attachment_ID: ' . $attachment_ID);
            update_post_meta($attachment_ID, 'epim_api_id', $id);
            $res = 'Image: ' . $id . ' - Imported Successfully';
        }
    } else {
        $res = 'Image: ' . $id . ' - Already Imported';
    }
    return $res;
}


/**
 * @param $productID
 * @param $variationID
 * @param $productBulletText
 * @param $productName
 * @param $categoryIds
 * @param $pictureIds
 *
 * @return string
 *
 * Create a Product
 */

function delete_attributes()
{
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $attribute_taxonomy) {
        $taxID = wc_attribute_taxonomy_id_by_name($attribute_taxonomy->attribute_name);
        wc_delete_attribute($taxID);
    }
}

function delete_variation($variationID) {

}

function create_product($productID, $variationID, $productBulletText, $productName, $categoryIds, $pictureIds)
{
    $res = '';
    /*
     * Get Variation Details
     */
    $productArray = array();
    $jsonVariation = get_api_variation($variationID);
    $variation = json_decode($jsonVariation);
    $jsonAttributes = get_api_all_attributes();
    $attributes = json_decode($jsonAttributes);

    $IsArchived = $variation->IsArchived;

    if($IsArchived) {
        $res = $variationID.' '.$productName.' is achived removing product from WooCommerce';
        delete_variation($variationID);
        return $res;
    }

    /*
     *
     *Check Attributes
     *
     */

    $attribute_taxonomies = wc_get_attribute_taxonomies();


    $currentAttributes = array();

    foreach ($attribute_taxonomies as $attribute_taxonomy) {

        $atName = $attribute_taxonomy->attribute_label;
        if (!in_array($atName, $currentAttributes)) {
            //error_log($atName);
            $currentAttributes[] = $atName;
        }
    }

    foreach ($attributes as $attribute) {
        $atName = $attribute->Name;
        $slugName = substr($atName, 0, 27);

        if (!wc_check_if_attribute_name_is_reserved($atName)) {
            if (!in_array($atName, $currentAttributes)) {
                $attribute_id = wc_attribute_taxonomy_id_by_name($atName);
                if (!$attribute_id) {
                    if ($atName != '') {
                        $attribute_id = wc_create_attribute(
                            array(
                                'name' => $atName,
                                'slug' => $slugName,
                            )
                        );

                        if (is_wp_error($attribute_id)) {
                            /*$error_string = $attribute_id->get_error_message();
                            error_log($error_string);
                            error_log('name = ' . $atName);
                            error_log('slug = ' . substr($atName, 0, 27));*/
                        }
                    }
                }

            }
        }
    }


    /*
     * Get Woo Categories
     */
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);
    $catIds = array();
    foreach ($categoryIds as $category_id) {
        $realCatID = getTermFromID($category_id, $terms);
        if ($realCatID) {
            $catIds[] = $realCatID->term_id;
        }
    }
    $productArray['categoryIDS'] = $catIds;

    /*
     * Other product Fields
     */
    $productArray['productTitle'] = $variation->Name;
    $productArray['productSKU'] = $variation->SKU;
    $productArray['price'] = $variation->Price;
	$Qty_Price_1 = $variation->Qty_Price_1;
	$Qty_Price_2 = $variation->Qty_Price_2;
	$Qty_Price_3 = $variation->Qty_Price_3;
	$Qty_Break_1 = $variation->Qty_Break_1;
	$Qty_Break_2 = $variation->Qty_Break_2;
	$Qty_Break_3 = $variation->Qty_Break_3;
    $productArray['productDescription'] = $variation->Table_Heading;
    $productArray['productShortDescription'] = $productBulletText;

    if(($productArray['price'] == '')||($productArray['price']==0)) {
	    $productArray['price'] = $Qty_Price_1;
    }

	if(($productArray['price'] == '')||($productArray['price']==0)) {
		$productArray['price'] = $Qty_Price_2;
	}

	if(($productArray['price'] == '')||($productArray['price']==0)) {
		$productArray['price'] = $Qty_Price_3;
	}

	/*
	* Set the product Meta Data
	*/
	$epim_API_ID = array("meta_key" => "epim_API_ID", "meta_data" => $productID);
	$epim_product_group_name = array("meta_key" => "epim_product_group_name", "meta_data" => $productName);
	$epim_variation_ID = array("meta_key" => "epim_variation_ID", "meta_data" => $variationID);
	$epim_Qty_Break_1 = array("meta_key" => "epim_Qty_Break_1", "meta_data" => $Qty_Break_1);
	$epim_Qty_Break_2 = array("meta_key" => "epim_Qty_Break_2", "meta_data" => $Qty_Break_2);
	$epim_Qty_Break_3 = array("meta_key" => "epim_Qty_Break_3", "meta_data" => $Qty_Break_3);
	$epim_Qty_Price_1 = array("meta_key" => "epim_Qty_Price_1", "meta_data" => $Qty_Price_1);
	$epim_Qty_Price_2 = array("meta_key" => "epim_Qty_Price_2", "meta_data" => $Qty_Price_2);
	$epim_Qty_Price_3 = array("meta_key" => "epim_Qty_Price_3", "meta_data" => $Qty_Price_3);

	$productArray['metaData'] = array($epim_API_ID, $epim_product_group_name, $epim_variation_ID, $epim_Qty_Break_1, $epim_Qty_Break_2, $epim_Qty_Break_3, $epim_Qty_Price_1, $epim_Qty_Price_2, $epim_Qty_Price_3);


    /*
     * Attributes
     */
    $aCounter = 1;
    $productAttributes = array();
    foreach ($variation->AttributeValues as $attribute_value) {
        $aName = getAttributeNameFromID($attribute_value->AttributeId, $attributes);
        if ($aName != '0 ( )') {
            $productAttributes[] = array("name" => $aName, "options" => array($attribute_value->Value), "position" => $aCounter, "visible" => 1, "variation" => 1);
            $aCounter++;
        }
    }
    $productArray['attributes'] = $productAttributes;

    /*
     * Image processing
     */

    $imageAttachmentIDS = array();

    if ($variation->PictureIds) {
        foreach ($variation->PictureIds as $pictureId) {
            $jsonPicture = get_api_picture($pictureId);
            $picture = json_decode($jsonPicture);
            $res .= importPicture($picture->Id, $picture->WebPath) . '<br>';
            $attachmentID = imageIDfromAPIID($picture->Id);
            if ($attachmentID) {
                if (!in_array($attachmentID, $imageAttachmentIDS)) $imageAttachmentIDS[] = $attachmentID;
            }
        }
    }

    if ($pictureIds) {
        foreach ($pictureIds as $pictureId) {
            $jsonPicture = get_api_picture($pictureId);
            $picture = json_decode($jsonPicture);
            $res .= importPicture($picture->Id, $picture->WebPath) . '<br>';
            $attachmentID = imageIDfromAPIID($picture->Id);
            if ($attachmentID) {
                if (!in_array($attachmentID, $imageAttachmentIDS)) $imageAttachmentIDS[] = $attachmentID;
            }
        }
    }

    $productArray['imageAttachmentIDS'] = $imageAttachmentIDS;

    $id = getProductFromID($productID, $variation->Id);

    //error_log('$id = '.$id);

    if (!$id) {

        if (ep_wooCreateProduct($productArray)) {
            $res .= $variation->Name . ' (' . $variation->SKU . ') Created<br>';
        } else {
            $res .= 'There was a problem creating productID: ' . $productID . ' variationID: ' . $variationID . '<br>';
        }
    } else {
        if (ep_wooUpdateProduct($id, $productArray)) {
            $res .= $variation->Name . ' (' . $variation->SKU . ') Created<br>';
        } else {
            $res .= 'There was a problem updating productID: ' . $productID . ' variationID: ' . $variationID . '<br>';
        }
    }

    return $res;

}
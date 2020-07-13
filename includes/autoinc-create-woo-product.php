<?php

// Takes an image url as an argument and upload image to wordpress and returns the media id, later we will use this id to assign the image to product.
function uploadMedia($image_url){
	require_once(ABSPATH.'wp-admin/includes/image.php');
	require_once(ABSPATH.'wp-admin/includes/file.php');
	require_once(ABSPATH.'wp-admin/includes/media.php');
	//error_log('Downloading - '.$image_url);
	try {
		$tmp  = download_url( $image_url );
		$file = array(
			'name'     => basename( $image_url ),
			'tmp_name' => $tmp
		);

		if ( is_wp_error( $tmp ) ) {
			$error_string = $tmp->get_error_message();
			@unlink( $file['tmp_name'] );
			//error_log( $image_url . ' | ' . $error_string );
			return false;
		} else {
			$media = media_handle_sideload( $file, 0 );
			if ( is_wp_error( $media ) ) {
				$error_string = $media->get_error_message();
				@unlink($file['tmp_name']);
				//error_log( $image_url . ' | ' . $error_string );
			} else {
				return $media;
			}
		}
	} catch (Exception $exception) {
		error_log($exception->getMessage());
	}
	return false;
}

function ep_wooUpdateProduct($pid, $productArray){
    return ep_wooCreateProduct_ex($pid,$productArray);
}

function ep_wooCreateProduct($productArray){
	return ep_wooCreateProduct_ex(0,$productArray);
}

function ep_wooCreateProduct_ex($pid, $productArray) {
    $product_id = false;
    try {
        if (array_key_exists('productTitle', $productArray)) {
            $productTitle = $productArray['productTitle'];
            if ($productTitle == '') {
                error_log('ep_wooCreatProduct - blank product title');
                return $product_id;
            }
        } else {
            error_log('ep_wooCreatProduct - blank product title');
            return $product_id;
        }

        if (array_key_exists('status', $productArray)) {
            $status = $productArray['status'];
        } else {
            $status = 'publish';
        }
        if (array_key_exists('catalogueVisibility', $productArray)) {
            $catalogueVisibility = $productArray['catalogueVisibility'];
        } else {
            $catalogueVisibility = 'visible';
        }
        if (array_key_exists('isVariable', $productArray)) {
            $isVariable = $productArray['isVariable'];
        } else {
            $isVariable = false;
        }
        if (array_key_exists('productDescription', $productArray)) {
            $productDescription = $productArray['productDescription'];
        } else {
            $productDescription = '';
        }
        if (array_key_exists('productShortDescription', $productArray)) {
            $productShortDescription = $productArray['productShortDescription'];
        } else {
            $productShortDescription = '';
        }
        if (array_key_exists('productSKU', $productArray)) {
            $productSKU = $productArray['productSKU'];
        } else {
            $productSKU = '';
        }
        if (array_key_exists('price', $productArray)) {
            $price = $productArray['price'];
        } else {
            $price = 1;
        }
        if (array_key_exists('regularPrice', $productArray)) {
            $regularPrice = $productArray['regularPrice'];
        } else {
            $regularPrice = $price;
        }
        if (array_key_exists('manageStock', $productArray)) {
            $manageStock = $productArray['manageStock'];
        } else {
            $manageStock = false;
        }
        if (array_key_exists('stockQuantity', $productArray)) {
            $stockQuantity = $productArray['stockQuantity'];
        } else {
            $stockQuantity = 1;
        }
        if (array_key_exists('stockStatus', $productArray)) {
            $stockStatus = $productArray['stockStatus'];
        } else {
            $stockStatus = 'instock';
        }
        if (array_key_exists('backorders', $productArray)) {
            $backorders = $productArray['backorders'];
        } else {
            $backorders = 'no';
        }
        if (array_key_exists('reviewsAllowed', $productArray)) {
            $reviewsAllowed = $productArray['reviewsAllowed'];
        } else {
            $reviewsAllowed = true;
        }
        if (array_key_exists('soldIndividually', $productArray)) {
            $soldIndividually = $productArray['soldIndividually'];
        } else {
            $soldIndividually = false;
        }
        if (array_key_exists('images', $productArray)) {
            $images = $productArray['images'];
        } else {
            $images = array();
        }
        if (array_key_exists('imageAttachmentIDS', $productArray)) {
            $imageAttachmentIDS = $productArray['imageAttachmentIDS'];
        } else {
            $imageAttachmentIDS = array();
        }
        if (array_key_exists('attributes', $productArray)) {
            $attributes = $productArray['attributes'];
        } else {
            $attributes = array();
        }
        if (array_key_exists('variations', $productArray)) {
            $variations = $productArray['variations'];
        } else {
            $variations = array();
        }
        if (array_key_exists('metaData', $productArray)) {
            $metaData = $productArray['metaData'];
        } else {
            $metaData = array();
        }


        /**
         *
         * Attributes array looks like this:
         *
         * $attributes = array(
         * array("name"=>"Size","options"=>array("S","L","XL","XXL"),"position"=>1,"visible"=>1,"variation"=>1),
         * array("name"=>"Color","options"=>array("Red","Blue","Black","White"),"position"=>2,"visible"=>1,"variation"=>1)
         * );*/


        /**
         *
         * Variations array looks like this:
         *
         * $variations = array(
         * array("regular_price"=>10.11,"price"=>10.11,"sku"=>"ABC1","attributes"=>array(array("name"=>"Size","option"=>"L"),array("name"=>"Color","option"=>"Red")),"manage_stock"=>1,"stock_quantity"=>10),
         * array("regular_price"=>10.11,"price"=>10.11,"sku"=>"ABC2","attributes"=>array(array("name"=>"Size","option"=>"XL"),array("name"=>"Color","option"=>"Red")),"manage_stock"=>1,"stock_quantity"=>10)
         *
         * );*/

        /**
         *
         * metaData array looks like this:
         *
         * $metaData = array(array("meta_key"=>"epim_API_ID","meta_data"=>"12345"),array("meta_key"=>"epim_product_group_name","meta_data"=>"Product Group Name"),array("meta_key"=>"epim_variation_ID","meta_data"=>"12345"))
         */

        if (array_key_exists('categoryIDS', $productArray)) {
            $categoryIDS = $productArray['categoryIDS'];
            if (!is_array($categoryIDS)) {
                error_log('ep_wooCreatProduct - Category IDS in unexpected format for '.$pid);
                return $product_id;
            }
            if (count($categoryIDS) == 0) {
                error_log('ep_wooCreatProduct - No Category IDS for '.$pid);
                return $product_id;
            }
        } else {
            error_log('ep_wooCreatProduct - No Category IDS supplied for '.$pid);
            return $product_id;
        }


        /*if ($isVariable) {
            $objProduct = new WC_Product_Variable();
        } else {
            $objProduct = new WC_Product();
        }*/

        if($pid>0) {

            $objProduct = wc_get_product($pid);
        } else {
            if ($isVariable) {
                $objProduct = new WC_Product_Variable();
            } else {
                $objProduct = new WC_Product();
            }
        }

        if(!$objProduct) {
            if ($isVariable) {
                $objProduct = new WC_Product_Variable();
            } else {
                $objProduct = new WC_Product();
            }
        }

        $objProduct->set_name($productTitle);
        $objProduct->set_status($status);  // can be publish,draft or any wordpress post status
        $objProduct->set_catalog_visibility($catalogueVisibility); // add the product visibility status
        $objProduct->set_description($productDescription);
        $objProduct->set_short_description($productShortDescription);
        $objProduct->set_sku($productSKU); //can be blank in case you don't have sku, but You can't add duplicate sku's
        $objProduct->set_price($price); // set product price
        $objProduct->set_regular_price($regularPrice); // set product regular price
        $objProduct->set_manage_stock($manageStock); // true or false
        $objProduct->set_stock_quantity($stockQuantity);
        $objProduct->set_stock_status($status); // in stock or out of stock value
        $objProduct->set_backorders($backorders);
        $objProduct->set_reviews_allowed($reviewsAllowed);
        $objProduct->set_sold_individually($soldIndividually);
        $objProduct->set_category_ids($categoryIDS); // array of category ids, You can get category id from WooCommerce Product Category Section of Wordpress Admin

        $productImagesIDs = array(); // define an array to store the media ids.
        //$images = array("image1 url","image2 url","image3 url"); // images url array of product
        foreach ($imageAttachmentIDS as $image_attachment_ID) {
            $productImagesIDs[] = $image_attachment_ID;
        }
        foreach ($images as $image) {
            $mediaID = uploadMedia($image); // calling the uploadMedia function and passing image url to get the uploaded media id
            if ($mediaID) $productImagesIDs[] = $mediaID; // storing media ids in a array.
        }

        if ($productImagesIDs) {
            $objProduct->set_image_id($productImagesIDs[0]); // set the first image as primary image of the product

            //in case we have more than 1 image, then add them to product gallery.
            if (count($productImagesIDs) > 1) {
                $objProduct->set_gallery_image_ids($productImagesIDs);
            }
        }

        $product_id = $objProduct->save(); // it will save the product and return the generated product id

        if ($attributes) {
            //error_log('Setting Attributes for '.$productTitle);
            $productAttributes = array();
            foreach ($attributes as $attribute) {
                $attr = wc_sanitize_taxonomy_name(stripslashes($attribute["name"])); // remove any unwanted chars and return the valid string for taxonomy name
                $attr = 'pa_' . $attr; // woocommerce prepend pa_ to each attribute name
                if ($attribute["options"]) {
                    foreach ($attribute["options"] as $option) {
                        //error_log('Attribute = '.$attr.' | Option = '.$option);
                        wp_set_object_terms($product_id, $option, $attr, true); // save the possible option value for the attribute which will be used for variation later
                    }
                }
                $productAttributes[sanitize_title($attr)] = array(
                    'name' => sanitize_title($attr),
                    'value' => $attribute["options"],
                    'position' => $attribute["position"],
                    'is_visible' => $attribute["visible"],
                    'is_variation' => $attribute["variation"],
                    'is_taxonomy' => '1'
                );
            }
            update_post_meta($product_id, '_product_attributes', $productAttributes); // save the meta entry for product attributes
        }

        if ($variations) {
            try {
                foreach ($variations as $variation) {
                    $objVariation = new WC_Product_Variation();
                    $objVariation->set_price($variation["price"]);
                    $objVariation->set_regular_price($variation["regular_price"]);
                    $objVariation->set_parent_id($product_id);
                    if (isset($variation["sku"]) && $variation["sku"]) {
                        $objVariation->set_sku($variation["sku"]);
                    }
                    $objVariation->set_manage_stock($variation["manage_stock"]);
                    $objVariation->set_stock_quantity($variation["stock_quantity"]);
                    $objVariation->set_stock_status('instock'); // in stock or out of stock value
                    $var_attributes = array();
                    foreach ($variation["attributes"] as $vattribute) {
                        $taxonomy = "pa_" . wc_sanitize_taxonomy_name(stripslashes($vattribute["name"])); // name of variant attribute should be same as the name used for creating product attributes
                        $attr_val_slug = wc_sanitize_taxonomy_name(stripslashes($vattribute["option"]));
                        $var_attributes[$taxonomy] = $attr_val_slug;
                    }
                    $objVariation->set_attributes($var_attributes);
                    $objVariation->save();
                }
            } catch (Exception $e) {
                // handle exception here
            }
        }

        if ($metaData) {
            foreach ($metaData as $data) {
                if (is_array($data)) {
                    if (array_key_exists('meta_key', $data)) {
                        if (array_key_exists('meta_data', $data)) {
                            update_post_meta($product_id, $data['meta_key'], $data['meta_data']);
                        }
                    }
                }

            }
        }
    } catch (Exception $e) {
        error_log($e->getMessage().' for '.$pid);
    }

    return $product_id;

}


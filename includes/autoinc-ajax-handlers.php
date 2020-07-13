<?php

function checkSecure() {
    if ( ! check_ajax_referer( 'epim-security-nonce', 'security' ) ) {
        wp_send_json_error( 'Invalid security token sent.' );
        wp_die();
    }
}

$log = true;


/**
 * ========================== Actions ==============================
 */


add_action( 'wp_ajax_get_all_categories', 'ajax_get_api_all_categories' );
add_action( 'wp_ajax_get_all_attributes', 'ajax_get_api_all_attributes' );
add_action( 'wp_ajax_get_all_products', 'ajax_get_api_all_products' );
add_action( 'wp_ajax_get_all_changed_products_since', 'ajax_get_api_all_changed_products_since' );
add_action( 'wp_ajax_get_product', 'ajax_get_api_product' );
add_action( 'wp_ajax_get_category', 'ajax_get_api_category' );
add_action( 'wp_ajax_get_picture', 'ajax_get_api_picture' );
add_action( 'wp_ajax_get_variation', 'ajax_get_api_variation' );
add_action( 'wp_ajax_create_category', 'ajax_create_category' );
add_action( 'wp_ajax_get_category_images', 'ajax_get_category_images' );
add_action( 'wp_ajax_get_picture_web_link', 'ajax_get_picture_web_link' );
add_action( 'wp_ajax_import_picture', 'ajax_import_picture' );
add_action( 'wp_ajax_sort_categories', 'ajax_sort_categories' );
add_action( 'wp_ajax_cat_image_link', 'ajax_cat_image_link' );
add_action( 'wp_ajax_product_image_link', 'ajax_product_image_link' );
add_action( 'wp_ajax_product_group_image_link', 'ajax_product_group_image_link' );
add_action( 'wp_ajax_create_product', 'ajax_create_product' );
add_action( 'wp_ajax_get_product_images', 'ajax_get_product_images' );
add_action( 'wp_ajax_product_ID_code', 'ajax_product_ID_from_code' );
add_action( 'wp_ajax_get_single_product_images', 'ajax_get_single_product_images' );
add_action( 'wp_ajax_import_single_product_images', 'ajax_import_single_product_images' );
add_action( 'wp_ajax_image_imported', 'ajax_image_imported' );
add_action( 'wp_ajax_delete_attributes', 'ajax_delete_attributes' );

function ajax_delete_attributes() {
    checkSecure();
    delete_attributes();
    echo 'All attributes removed';
    exit;
}


function ajax_image_imported() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        if(imageImported($_POST['ID'])) {
            echo 'Image Imported';
        } else {
            echo 'Image not Imported';
        }
    }
    exit;
}

function ajax_import_single_product_images() {
    checkSecure();
    if ( ! empty( $_POST['productID'] ) ) {
        if ( ! empty( $_POST['variationID'] ) ) {
            $response = importSingleProductImages($_POST['productID'], $_POST['variationID']);
            echo $response;
        } else {
            echo 'error no variationID supplied';
        }
    } else {
        echo 'error no productID supplied';
    }
    exit;
}


function ajax_get_single_product_images() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        $response = getSingleProductImages($_POST['ID']);
        header( "Content-Type: application/json charset=utf-8" );
        echo json_encode($response);
    } else {
        echo 'error no ID supplied';
    }
    exit;
}

function ajax_product_ID_from_code() {
    checkSecure();
    $response = 'Not Found';
    if ( ! empty( $_POST['CODE'] ) ) {
        $response = getAPIIDFromCode($_POST['CODE']);
        //error_log('Code = '.$_POST['CODE'].' | API = '.$response);
    }
    echo $response;
    exit;
}

function ajax_get_api_product() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        $jsonResponse = get_api_product( $_POST['ID'] );
        $response     = $jsonResponse;
        header( "Content-Type: application/json" );
        echo json_encode( $response );
    } else {
        echo 'error no ID supplied';
    }
    exit;
}

function ajax_get_category_images() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        header( "Content-Type: application/json" );
        echo getCategoryImages( $_POST['ID'] );
    }
    exit;
}

function ajax_get_product_images() {
    checkSecure();
    $response = getProductImages();
    //error_log(json_encode($response));
    header( "Content-Type: application/json charset=utf-8" );
    echo json_encode($response);
    exit;
}

function ajax_create_product() {
    checkSecure();
    if ( ! empty( $_POST['productID'] ) ) {
        if ( ! empty( $_POST['variationID'] ) ) {
            if ( ! empty( $_POST['productName'] ) ) {
                $pictureIDS = '';
                if(isset($_POST['pictureIDs'])) {
                    $pictureIDS = $_POST['pictureIDs'];
                }
                echo create_product( $_POST['productID'], $_POST['variationID'], $_POST['bulletText'], $_POST['productName'], $_POST['categoryIDs'], $pictureIDS );
                exit;
            } else {
                echo 'Product Creation Failed - no Product Name supplied';
                exit;
            }
        } else {
            echo 'Product Creation Failed - no Variation ID supplied';
            exit;
        }
    } else {
        echo 'Product Creation Failed - no Product ID';
        exit;
    }

}

function ajax_cat_image_link() {
    checkSecure();
    linkCategoryImages();
    echo 'Category Images Linked';
    exit;
}

function ajax_product_image_link() {
    checkSecure();
    echo linkProductImages();
    //linkVariationImages();
    //echo 'Product Images Linked';
    exit;
}

function ajax_product_group_image_link() {
    checkSecure();
    if ( ! empty( $_POST['productID'] ) ) {
        echo linkProductGroupImages($_POST['productID']);
    }

    exit;
}

function ajax_sort_categories() {
    checkSecure();
    sort_categories();
    echo 'Categories Sorted';
    exit;
}

function ajax_import_picture() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        if ( ! empty( $_POST['weblink'] ) ) {
            echo importPicture( $_POST['ID'], $_POST['weblink'] );
        }
    }
    exit;
}

function ajax_get_picture_web_link() {
    checkSecure();
    $response = '';
    if ( ! empty( $_POST['ID'] ) ) {
        $response = get_api_picture( $_POST['ID'] );
    }
    header( "Content-Type: application/json" );
    echo json_encode( $response );
    exit;
}

function ajax_get_api_all_categories() {
    checkSecure();
    $jsonResponse = get_api_all_categories();
    $response     = $jsonResponse;
    header( "Content-Type: application/json" );
    echo json_encode( $response );
    exit;
}

function ajax_get_api_all_attributes() {
    checkSecure();
    $jsonResponse = get_api_all_attributes();
    $response     = $jsonResponse;
    header( "Content-Type: application/json" );
    echo json_encode( $response );
    exit;
}

function ajax_get_api_all_products() {
    checkSecure();
    $jsonResponse = get_api_all_products();
    $response     = json_decode( $jsonResponse );
    //header( "Content-Type: application/json" );
    echo json_encode( $response->Results );
    exit;
}

function ajax_get_api_all_changed_products_since() {
    checkSecure();
    if ( ! empty( $_POST['timeCode'] ) ) {
        $jsonResponse = get_api_all_changed_products_since($_POST['timeCode'] );
        $response = json_decode($jsonResponse);
        //header( "Content-Type: application/json" );
        echo json_encode($response);
    }
    exit;
}



function ajax_get_api_category() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {

        $jsonResponse = get_api_category( $_POST['ID'] );
        $response     = $jsonResponse;
        header( "Content-Type: application/json" );
        echo json_encode( $response );
    } else {
        echo 'error no ID supplied';
    }
    exit;
}

function ajax_get_api_picture() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        //error_log('Getting Picture: '.$_POST['ID']);
        $jsonResponse = get_api_picture( $_POST['ID'] );
        $response     = $jsonResponse;
        header( "Content-Type: application/json" );
        echo json_encode( $response );
    } else {
        //error_log('error no ID supplied in ajax_get_api_picture');
    }
    exit;
}

function ajax_get_api_variation() {
    checkSecure();
    if ( ! empty( $_POST['ID'] ) ) {
        $jsonResponse = get_api_variation( $_POST['ID'] );
        $response     = $jsonResponse;
        header( "Content-Type: application/json" );
        echo json_encode( $response );
    } else {
        echo 'error no ID supplied';
    }
    exit;
}

function ajax_create_category() {
    checkSecure();
    $response = 'Nothing Happened!!';
    if ( ! empty( $_POST['ID'] ) ) {
        if ( ! empty( $_POST['name'] ) ) {
            $WebPath = '';
            $Picture_ids = '';
            if(isset($_POST['WebPath'])) {
                $WebPath = $_POST['WebPath'];
            }
            if(isset($_POST['picture_ids'])) {
                $Picture_ids= $_POST['picture_ids'];
            }
            $response = create_category( $_POST['ID'], $_POST['name'], $_POST['ParentID'], $WebPath, $Picture_ids );
        }
    }
    echo $response;
    exit;
}

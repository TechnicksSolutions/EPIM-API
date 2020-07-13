<?php
add_filter("attachment_fields_to_edit", "epim_add_image_attachment_fields_to_edit", null, 2);
add_filter("attachment_fields_to_save", "epim_add_image_attachment_fields_to_save", null , 2);

function epim_add_image_attachment_fields_to_edit( $form_fields, $post ) {

    // Add a Credit field
    $form_fields["epim_api_id"] = array(
        "label" => __("API ID"),
        "input" => "text", // this is default if "input" is omitted
        "value" => esc_attr( get_post_meta($post->ID, "epim_api_id", true) ),
        "helps" => __("ePim API ID for this image"),
    );


    return $form_fields;
}

function epim_add_image_attachment_fields_to_save( $post, $attachment ) {
    if ( isset( $attachment['epim_api_id'] ) )
        update_post_meta( $post['ID'], 'epim_api_id', esc_attr($attachment['epim_api_id']) );
    return $post;
}
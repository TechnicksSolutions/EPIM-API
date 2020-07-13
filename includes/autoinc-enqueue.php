<?php
add_action('admin_enqueue_scripts', 'epim_admin_enqueue');
function epim_admin_enqueue($hook) {
    if ('toplevel_page_epim' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery-ui-datepicker');
    wp_register_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style('jquery-ui');
    wp_enqueue_script('epim_process_queue_script', plugins_url('assets/scripts/processQueue.js',__DIR__));
    wp_enqueue_script('epim_admin_scripts', plugins_url('assets/scripts/admin.js',__DIR__),'epim_process_queue_script');
    wp_localize_script(
        'epim_process_queue_script',
        'epim_ajax_object',
        [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'security'  => wp_create_nonce( 'epim-security-nonce' ),
        ]
    );
}
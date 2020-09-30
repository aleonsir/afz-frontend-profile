<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://glowlogix.com/
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete all pages and settings when plugin in uninstalled.
 */
function afzfp_delete_options()
{
    $afzfp_uninstall = get_option('afzfp_general');
    if ('on' == $afzfp_uninstall['afzfp_remove_data_on_uninstall']) {
        // Delete Pages.
        $afzfp_options = get_option('afzfp_profile');
        wp_delete_post($afzfp_options['login_page'], true);
        wp_delete_post($afzfp_options['register_page'], true);
        wp_delete_post($afzfp_options['edit_page'], true);
        wp_delete_post($afzfp_options['profile_page'], true);

        // Delete Options.
        delete_option('_afzfp_page_created');
        delete_option('afzfp_general');
        delete_option('afzfp_profile');
        delete_option('afzfp_pages');
        delete_option('afzfp_uninstall');
        delete_option('afzfp_Install_Time');
        delete_option('afzfp_Ask_Review_Date');
    }
}
afzfp_delete_options();

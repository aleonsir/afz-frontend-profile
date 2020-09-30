<?php
/**
 * Plugin Name: Frontend Profile
 * Description: Frontend profiles for users.
 * Version:     1.2.5
 * Author:      Álvaro Franz
 * GitHub Plugin URI: https://github.com/alvarofranz/afz-frontend-profile
 * Text Domain: afzfp
*/

// Include the common functions
require_once plugin_dir_path(__FILE__).'inc/functions-afzfp.php';

// Include main class and set up actions and filters
require_once plugin_dir_path(__FILE__).'inc/class-afzfp-main.php';
AFZFP_Main::setup_actions_and_filters();

// Include the User class but don't instantiate until needed
require_once plugin_dir_path(__FILE__).'inc/class-afzfp-user.php';

// Include login class and set up actions and filters
require_once plugin_dir_path(__FILE__).'inc/class-afzfp-login.php';
AFZFP_Login::setup_actions_and_filters();

// Include registration class and set up actions and filters
require_once plugin_dir_path(__FILE__).'inc/class-afzfp-registration.php';
AFZFP_Registration::setup_actions_and_filters();

// Include profile class and set up actions and filters
require_once plugin_dir_path(__FILE__).'inc/class-afzfp-profile.php';
AFZFP_Profile::setup_actions_and_filters();

// Future: require_once plugin_dir_path(__FILE__).'inc/class-afzfp-captcha-recaptcha.php';

// Admin classes
if(is_admin()){
    require_once plugin_dir_path(__FILE__).'inc/class-afzfp-admin-installer.php';
    AFZFP_Admin_Installer::setup_actions_and_filters();

    require_once plugin_dir_path(__FILE__).'inc/class-afzfp-admin-settings.php';
    require_once plugin_dir_path(__FILE__).'inc/class-afzfp-admin-settings-api.php';
    $settings_API = new AFZFP_Settings_API();
    $todochange = new AFZFP_Admin_Settings($settings_API);
}
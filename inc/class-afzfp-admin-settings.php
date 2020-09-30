<?php

defined('ABSPATH') || exit;

if(!class_exists('AFZFP_Admin_Settings')){

class AFZFP_Admin_Settings{

private $settings_api;

//The menu page hooks. Used for checking if any page is under afzfp menu
private $menu_pages = [];

public function __construct($settings_api){
    $this->settings_api = $settings_api;
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('admin_init', [$this, 'admin_init']);
    add_action('admin_init', [$this, 'clear_settings']);
}

// Fields on setting page
public function afzfp_settings_fields(){
    $user_roles = [];
    $pages = afzfp_get_pages();
    $all_roles = get_editable_roles();
    foreach ($all_roles as $key => $value) {
        $user_roles[$key] = $value['name'];
    }
    $activation_options = [
        'activation_mail' => 'Send account activation email',
        'adimin_approval' => 'Wait for approval by admin'
    ];
    $settings_fields = [
        'afzfp_profile'             => apply_filters(
            'afzfp_options_profile',
            [
                [
                    'name'    => 'user_activation_method',
                    'label'   => __('User activation method', 'afzfp'),
                    'type'    => 'select',
                    'options' => $activation_options,
                ],
                [
                    'name'    => 'redirect_after_login_page',
                    'label'   => __('Redirect After Login', 'afzfp'),
                    'desc'    => __('After successful login, where the page will redirect to', 'afzfp'),
                    'type'    => 'select',
                    'options' => $pages,
                ],
                [
                    'name'    => 'redirect_after_registration',
                    'label'   => __('Redirect After Registration', 'afzfp'),
                    'desc'    => __('After successful registration, where the page will redirect to, Make sure you have checked auto login after registration.', 'afzfp'),
                    'type'    => 'select',
                    'options' => $pages,
                ],
            ]
        ),
        'afzfp_general'             => apply_filters(
            'afzfp_options_others',
            [
                [
                    'name'    => 'strong_password',
                    'label'   => __('Enable Strong Password', 'afzfp'),
                    'desc'    => __('Check to enable strong password.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'name'  => 'recaptcha_public',
                    'label' => __('reCAPTCHA Site Key', 'afzfp'),
                ],
                [
                    'name'  => 'recaptcha_private',
                    'label' => __('reCAPTCHA Secret Key', 'afzfp'),
                    'desc'  => __('<a target="_blank" href="https://www.google.com/recaptcha/">Register here</a> to get reCaptcha Site and Secret keys.', 'afzfp'),
                ],
                [
                    'name'    => 'enable_captcha_login',
                    'label'   => __('reCAPTCHA Login Form', 'afzfp'),
                    'desc'    => __('Check to enable reCAPTCHA in login form.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ],
                [
                    'name'    => 'enable_captcha_registration',
                    'label'   => __('reCAPTCHA Registration Form', 'afzfp'),
                    'desc'    => __('Check to enable reCAPTCHA in registration form', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'off',
                ],
                [
                    'name'    => 'afzfp_remove_data_on_uninstall',
                    'label'   => __('Remove Data on Uninstall?', 'afzfp'),
                    'desc'    => __('Check this box if you would like to completely remove all of the data when the plugin is deleted.', 'afzfp'),
                    'type'    => 'checkbox',
                    'options' => 'off',
                ],
            ]
        ),
        'afzfp_pages'               => apply_filters(
            'afzfp_options_pages',
            [
                [
                    'name'    => 'register_page',
                    'label'   => __('Registration Page', 'afzfp'),
                    'desc'    => __('Select the page which contains [afzfp_register] shortcode', 'afzfp'),
                    'type'    => 'select_page',
                    'options' => $pages,
                ],
                [
                    'name'    => 'login_page',
                    'label'   => __('Login Page', 'afzfp'),
                    'desc'    => __('Select the page which contains [afzfp_login] shortcode', 'afzfp'),
                    'type'    => 'select_page',
                    'options' => $pages,
                ],
                [
                    'name'    => 'profile_edit_page',
                    'label'   => __('Profile Edit Page', 'afzfp'),
                    'desc'    => __('Select the page which contains [afzfp] shortcode', 'afzfp'),
                    'type'    => 'select_page',
                    'options' => $pages,
                ],
                [
                    'name'    => 'profile_page',
                    'label'   => __('Profile Page', 'afzfp'),
                    'desc'    => __('Select the page which contains [afzfp_profile] shortcode', 'afzfp'),
                    'type'    => 'select_page',
                    'options' => $pages,
                ],
            ]
        ),
        'afzfp_emails_notification' => apply_filters(
            'afzfp_options_emails_notification',
            [
                [
                    'name'    => 'password_change_mail',
                    'label'   => __('Change password email', 'afzfp'),
                    'desc'    => __(' Send an email to user for change password.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'name'    => 'reset_password_mail',
                    'label'   => __('Reset password email', 'afzfp'),
                    'desc'    => __('Send an email to user for reset password.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'name'    => 'new_account_admin_mail',
                    'label'   => __('New account registration admin mail', 'afzfp'),
                    'desc'    => __('Send an email to admin when user has created account on site.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
                [
                    'name'    => 'change_password_admin_mail',
                    'label'   => __('Change user password admin mail', 'afzfp'),
                    'desc'    => __('Send an email to admin when user has changed account password.', 'afzfp'),
                    'type'    => 'checkbox',
                    'default' => 'on',
                ],
            ]
        ),
    ];

    return apply_filters('afzfp_settings_fields', $settings_fields);
}

// Tabs on setting page
public function afzfp_settings_sections()
{
    $sections = [
        [
            'id'    => 'afzfp_profile',
            'title' => __('Login / Registration', 'afzfp'),
            'icon'  => 'dashicons-admin-users',
        ],
        [
            'id'    => 'afzfp_pages',
            'title' => __('Pages', 'afzfp'),
            'icon'  => 'dashicons-admin-page',
        ],
        [
            'id'    => 'afzfp_emails_notification',
            'title' => __('Emails', 'afzfp'),
            'icon'  => 'dashicons-email',
        ],
        [
            'id'    => 'afzfp_general',
            'title' => __('Settings', 'afzfp'),
            'icon'  => 'dashicons-admin-generic',
        ],
    ];

    return apply_filters('afzfp_settings_sections', $sections);
}

// Initialize settings
public function admin_init(){
    // Set the settings.
    $this->settings_api->set_sections($this->get_settings_sections());
    $this->settings_api->set_fields($this->get_settings_fields());
    $this->settings_api->admin_init();
}

// Register the admin menu
public function admin_menu(){
    global $_registered_pages;
    // Translation issue: Hook name change due to translate menu title.
    $this->menu_pages[] = add_menu_page(__('Front-Profile', 'afzfp'), __('Front-Profile', 'afzfp'), 'manage_options', 'afzfp-settings_dashboard', [$this, 'plugin_page'], 'dashicons-admin-users', 55);
    $this->menu_pages[] = add_submenu_page('afzfp-settings_dashboard', __('Settings', 'afzfp'), __('Settings', 'afzfp'), 'manage_options', 'afzfp-settings', [$this, 'plugin_page']);
    $this->menu_pages[] = add_submenu_page('afzfp-settings_dashboard', __('Tools', 'afzfp'), __('Tools', 'afzfp'), 'manage_options', 'afzfp-tools', [$this, 'tool_page']);
    remove_submenu_page('afzfp-settings_dashboard', 'afzfp-settings_dashboard');
}

// Settings sections
public function get_settings_sections(){
    return $this->afzfp_settings_sections();
}

// Returns all the settings fields
public function get_settings_fields(){
    return $this->afzfp_settings_fields();
}

// Display all setting fields on setting page
public function plugin_page(){
    ?>
    <div class="wrap">
        <h2><?php esc_html_e('Settings', 'afzfp'); ?></h2>
        <div class="afzfp-settings-wrap">
            <div class="metabox-holder">
                <form method="post" action="options.php">
                    <?php
                    settings_errors();
                    $this->settings_api->show_navigation();
                    $this->settings_api->show_forms();
                    ?>
                </form>
            </div>
        </div>
    </div>
<?php
}

// Display all tools on tool page
public function tool_page(){

    $confirmation_message = __('Are you Sure?', 'afzfp');

    if(wp_verify_nonce(isset($_GET['afzfp_delete_settings']) && 1 === $_GET['afzfp_delete_settings'])){
        ?>
        <div class="updated updated_afzfp">
            <p>
                <?php esc_html_e('Settings has been cleared!', 'afzfp'); ?>
            </p>
        </div>

    <?php
    }
    ?>

    <div class="wrap">
        <h2>Tools</h2>
        <div class="metabox-holder">
            <div class="postbox">
                <h3><?php esc_html_e('Page Installation', 'afzfp'); ?></h3>

                <div class="inside">
                    <p><?php esc_html_e('Clicking this button will create required pages for the plugin. Note: It\'ll not delete/replace existing pages.', 'afzfp'); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url(add_query_arg(['install_afzfp_pages' => true])); ?>"><?php esc_html_e('Create Pages', 'afzfp'); ?></a>
                </div>
            </div>

            <div class="postbox">
                <h3><?php esc_html_e('Reset Settings', 'afzfp'); ?></h3>

                <div class="inside">
                    <p>
                        <strong><?php esc_html_e('Caution:', 'afzfp'); ?></strong>
                        <?php esc_html_e('This tool will delete all the plugin settings', 'afzfp'); ?>
                    </p>
                    <a class="button button-primary" href="<?php echo esc_url(add_query_arg(['afzfp_delete_settings' => 1])); ?>" onclick="return confirm('Are you sure?');"><?php esc_html_e('Reset Settings', 'afzfp'); ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php
}



// Clear all plugin settings
public function clear_settings(){
    if(isset($_GET['afzfp_delete_settings']) && '1' === $_GET['afzfp_delete_settings']) {
        // Delete Pages.
        $afzfp_options = get_option('afzfp_profile');
        wp_delete_post($afzfp_options['login_page'], false);
        wp_delete_post($afzfp_options['register_page'], false);
        wp_delete_post($afzfp_options['edit_page'], false);
        wp_delete_post($afzfp_options['profile_page'], false);
        // Delete Options.
        delete_option('_afzfp_page_created');
        delete_option('afzfp_general');
        delete_option('afzfp_profile');
        delete_option('afzfp_pages');
        delete_option('afzfp_uninstall');
    }
}
}
}
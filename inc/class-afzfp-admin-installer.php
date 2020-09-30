<?php

defined('ABSPATH') || exit;

if(!class_exists('AFZFP_Admin_Installer')){

class AFZFP_Admin_Installer{

public function __construct(){}

// Set up actions and filters
public static function setup_actions_and_filters(){

    $this_class = new self();

    add_action('admin_notices', [$this_class, 'admin_notice']);
    add_action('admin_init', [$this_class, 'handle_request']);
    add_filter('display_post_states', [$this_class, 'add_post_states'], 10, 2);

}

// Print admin notices
public function admin_notice(){

    $page_created = get_option('_afzfp_page_created'); ?>
    
    <?php
    if(false === $page_created){
        ?>
        <div class="updated error updated_afzfp">
            <p>
                <?php esc_attr_e('You need to create some pages (User Profile, Registration, Login, Profile Edit).', 'afzfp'); ?>
            </p>
            <p class="submit">
                <a class="button button-primary" href="<?php echo esc_url(add_query_arg(['install_afzfp_pages' => true], admin_url('admin.php?page=afzfp-settings'))); ?>"><?php esc_attr_e('Automatic Creation', 'afzfp'); ?></a>
                <?php esc_attr_e('or', 'afzfp'); ?>
                <a class="button" href="<?php echo esc_url(add_query_arg(['afzfp_hide_page_nag' => true])); ?>"><?php esc_attr_e('Manual creation', 'afzfp'); ?></a>
            </p>
        </div>
        <?php
    }

    if(true === isset($_GET['afzfp_page_installed']) && sanitize_text_field(wp_unslash($_GET['afzfp_page_installed']))) {
        ?>
        <div class="updated afzfp_updated">
            <p>
                <strong><?php esc_attr_e('Congratulations!', 'afzfp'); ?></strong> 
                <?php
                $page_success = 'The required pages have been successfully installed and saved.';

                echo wp_kses(
                    $page_success,
                    [
                        'p'      => [],
                        'strong' => [],
                    ]
                ); ?>
            </p>
        </div>
        <?php
    }
}

// Handle the page creation button requests
public function handle_request(){

    if(true === isset($_GET['install_afzfp_pages']) && sanitize_text_field(wp_unslash($_GET['install_afzfp_pages']))){
        $this->init_pages();
    }

    if(true === isset($_POST['install_afzfp_pages']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['install_afzfp_pages'])))){
        $this->init_pages();
    }

    if(true === isset($_GET['afzfp_hide_page_nag']) && sanitize_text_field(wp_unslash($_GET['afzfp_hide_page_nag']))){
        update_option('_afzfp_page_created', '1');
    }

}

// Initialize the plugin with some default page/settings
public function init_pages(){

    // Create pages
    $register_page = $this->create_page(__('Register', 'afzfp'), '[afzfp_register]');
    $edit_page = $this->create_page(__('Profile Edit', 'afzfp'), '[afzfp_profile_edit]');
    $login_page = $this->create_page(__('Login', 'afzfp'), '[afzfp_login]');
    $profile_page = $this->create_page(__('Profile', 'afzfp'), '[afzfp_profile]');

    $profile_options = [];
    $reg_page = false;

    if($login_page){
        $profile_options['login_page'] = $login_page;
    }
    if($register_page) {
        $profile_options['register_page'] = $register_page;
    }
    if ($edit_page) {
        $profile_options['edit_page'] = $edit_page;
    }
    if ($profile_page) {
        $profile_options['profile_page'] = $profile_page;
    }

    $data = apply_filters('afzfp_pro_page_install', $profile_options);

    if(is_array($data)){
        if(isset($data['profile_options'])){
            $profile_options = $data['profile_options'];
        }
        if(isset($data['reg_page'])){
            $reg_page = $data['reg_page'];
        }
    }

    update_option('afzfp_profile', $profile_options);

    update_option(
        'afzfp_pages',
        [
            'login_page'        => $login_page,
            'register_page'     => $register_page,
            'profile_edit_page' => $edit_page,
            'profile_page'      => $profile_page,
        ]
    );

    update_option('_afzfp_page_created', '1');

    $location = 'admin.php?page=afzfp-settings&afzfp_page_installed=true';
    $status = 302;
    $x_redirect_by = 'WordPress';

    wp_safe_redirect($location, $status, $x_redirect_by);
    exit;
}

// Create a page with title and content
public function create_page($page_title, $post_content = '', $post_type = 'page'){

    $page_id = wp_insert_post(
        [
            'post_title'     => $page_title,
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'comment_status' => 'closed',
            'post_content'   => $post_content,
        ]
    );

    if($page_id && !is_wp_error($page_id)){
        return $page_id;
    }

    return false;
}

// Add a post display state for Frontend Profile pages in the page list table
public function add_post_states($post_states, $post){

    $afzfp_options = get_option('afzfp_pages');

    if (!empty($afzfp_options['login_page']) && $afzfp_options['login_page'] === $post->ID) {
        $post_states[] = __('Login', 'afzfp');
    }

    if (!empty($afzfp_options['register_page']) && $afzfp_options['register_page'] === $post->ID) {
        $post_states[] = __('Register', 'afzfp');
    }

    if (!empty($afzfp_options['profile_edit_page']) && $afzfp_options['profile_edit_page'] === $post->ID) {
        $post_states[] = __('Edit Profile', 'afzfp');
    }

    if (!empty($afzfp_options['profile_page']) && $afzfp_options['profile_page'] === $post->ID) {
        $post_states[] = __('My Profile', 'afzfp');
    }

    return $post_states;
}
}
}
<?php
if(!class_exists('AFZFP_Registration')){
    
class AFZFP_Registration{

public function __construct(){}

// Set up actions and filters
public static function setup_actions_and_filters(){

    $this_class = new self();

    add_shortcode('afzfp_register', [$this_class, 'registration_form']);

    // Attach actions to the 'init' hook

    add_action('init', [$this_class, 'process_registration']);

}

// Used by the [afzfp_register] shortcode to show the registration form
public function registration_form($atts){

    $atts = shortcode_atts(
        [
            'role_fixed' => '',    // Force fixed role
            'role_selector' => ''  // Allow user to select role from multiple choices
        ],
        $atts
    );

    ob_start();

    if(is_user_logged_in()){
        afzfp_load_template(
            'logged-in.php',
            [
                'user' => wp_get_current_user(),
            ]
        );
    }else{
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'register';

        $args = [
            'action_url'    => afzfp_get_option('register_page', 'afzfp_pages', false),
            'role_fixed'    => $atts['role_fixed'],
            'role_selector' => $atts['role_selector']
        ];
        afzfp_load_template('registration.php', $args);
    }

    return ob_get_clean();
}

// Process the actual registration
public function process_registration(){

    if(isset($_POST['nonce_field_register'])){
        
        // Array to store userdata (will be used to insert the user into WP)
        $userdata = [];

        // Verify nonce
        if(!wp_verify_nonce($_POST['nonce_field_register'], 'afzfp_registration_action')){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Nonce value could not be verified, please try again.', 'afzfp') );
            return;
        }

        // Verify email
        if(!filter_var($_POST['afzfp_reg_email'], FILTER_VALIDATE_EMAIL)){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('A valid email is required.', 'afzfp') );
            return;
        }else{

            // Check if this email is already registered
            $clean_email = sanitize_email(wp_unslash($_POST['afzfp_reg_email']));
            if(email_exists($clean_email)){
                wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('An account with this email already exists.', 'afzfp') );
                return;
            }else{
                $userdata['user_email'] = $clean_email;
            }

        }

        // Verify username
        if(empty($_POST['afzfp_reg_uname'])){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Username is required.', 'afzfp') );
            return;
        }else{

            // Check if this username is already registered
            $clean_username = sanitize_user(wp_unslash($_POST['afzfp_reg_uname']));
            if(username_exists($clean_username)){
                wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('This username is already taken.', 'afzfp') );
                return;
            }else{
                $userdata['user_login'] = $clean_username;
            }

        }

        // Verify that password 1 is not empty
        if(empty($_POST['pwd1'])){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Password is required.', 'afzfp') );
            return;
        }

        // Verify that password 2 is not empty
        if(empty($_POST['pwd2'])){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Confirm Password is required.', 'afzfp') );
            return;
        }

        // Verify that the 2 passwords match
        if($_POST['pwd1'] != $_POST['pwd2']){
            wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Passwords are not same.', 'afzfp') );
            return;
        }else{
            $userdata['user_pass'] = $_POST['pwd1']; // Don't sanitize this, a hash will be applied
        }

        // Check if the password is secure (only if strong password feature is enabled)
        $enable_strong_pwd = afzfp_get_option('strong_password', 'afzfp_general');
        if('off' != $enable_strong_pwd){
            if(false == afzfp_check_if_password_secure($_POST['pwd1'])){
                // Add message indicating complexity issue
                wp_cache_set( 'afzfp_registration_message', '<p class="error">'.__('Your password must contain 12 characters including at least 1 uppercase, 1 lowercase letter and 1 number.', 'afzfp').'.</p>' );
            }
        }

        // Verify user role (if any)
        if(isset($_POST['afzfp_reg_role'])){
            $user_role_clean = sanitize_text_field(wp_unslash($_POST['afzfp_reg_role']));

            // Don't allow administrator role
            if('administrator' == $user_role_clean){
                wp_cache_set( 'afzfp_registration_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Administrators are not allowed to register from this plugin to avoid security issues.', 'afzfp') );
                return;
            }else{

                // Check if the role exists
                if(get_role($user_role_clean)) {
                    $userdata['role'] = $user_role_clean;
                }

            }

        }

        // Verify reCaptcha
        if(isset($_POST['g-recaptcha-response'])){
            if(empty($_POST['g-recaptcha-response'])){
                wp_cache_set( 'afzfp_registration_message', __('reCaptcha is required', 'afzfp') );
                return;
            }else{
                $no_captcha = 1;
                $invisible_captcha = 0;

                AFZFP_Captcha_Recaptcha::captcha_verification();
            }
        }   
        
        // Sanitize first name
        if(isset($_POST['afzfp_reg_fname'])){
            $userdata['first_name'] = sanitize_text_field(wp_unslash($_POST['afzfp_reg_fname']));
        }

        // Sanitize last name
        if(isset($_POST['afzfp_reg_lname'])){
            $userdata['last_name'] = sanitize_text_field(wp_unslash($_POST['afzfp_reg_lname']));
        }

        // Sanitize user description
        if(isset($_POST['afzfp-description'])){
            $userdata['description'] = sanitize_text_field(wp_unslash($_POST['afzfp-description']));
        }

        // Insert user
        $user = wp_insert_user($userdata);
        
        if(is_wp_error($user)){
            wp_cache_set( 'afzfp_registration_message', $user->get_error_message() );
            return;
        }else{

            // Set as pending approval
            add_user_meta($user, 'afzfp_user_status', 'pending');

            // Send email to admin if enabled
            if('on' == afzfp_get_option('new_account_admin_mail', 'afzfp_emails_notification', 'on')){

                $subject = esc_html__('New User Registration', 'afzfp');
                $subject = apply_filters('afzfp_default_reg_admin_mail_subject', $subject);

                $message = sprintf(esc_html__('New user registration on your site %s:', 'afzfp'), get_option('blogname'))."\r\n\r\n";
                $message .= sprintf(esc_html__('Username: %s', 'afzfp'), $userdata['user_login'])."\r\n\r\n";
                $message .= sprintf(esc_html__('E-mail: %s', 'afzfp'), $userdata['user_email'])."\r\n";
                $message = apply_filters('afzfp_default_reg_admin_mail_body', $message);

                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                wp_mail(get_option('admin_email'), sprintf(esc_html__('[%1$s] %2$s', 'afzfp'), $blogname, $subject), $message);
            }

            // Get user activation method option
            $user_activation_method = afzfp_get_option('user_activation_method', 'afzfp_profile');
            $register_page = get_permalink(afzfp_get_option('register_page', 'afzfp_pages'));

            $afzfp_user = new AFZFP_User($user);
                
            $afzfp_user->send_confirmation_email($user);

            // Action hook on register success
            do_action('afzfp_register_success', $user);
            
            wp_safe_redirect(add_query_arg(['success' => 'notactivated'], $register_page));


        }
    }
}


// Return POST value for given array key
public function get_post_value($key){

    if(isset($_POST[$key])){
        return wp_unslash($_POST[$key]);
    }

    return '';
}

// Show errors on the form
public function show_message(){

    $registration_message = wp_cache_get( 'afzfp_registration_message' );

    if($registration_message){    
        echo '<div class="afzfp-message">'.$registration_message.'</div>';
    }

}

}
}
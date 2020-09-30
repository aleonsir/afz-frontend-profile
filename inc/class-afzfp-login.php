<?php
if(!class_exists('AFZFP_Login')){

class AFZFP_Login{

public function __construct(){}

// Set up actions and filters
public static function setup_actions_and_filters(){

    $this_class = new self();

    add_shortcode('afzfp_login', [$this_class, 'login_form']);

    // Attach actions to the 'init' hook
    add_action('init', [$this_class, 'process_login']);
    add_action('init', [$this_class, 'process_logout']);
    add_action('init', [$this_class, 'process_reset_password']);
    add_action('init', [$this_class, 'wp_login_page_redirect']);
    add_action('init', [$this_class, 'activation_user_registration']);

    // Add custom fields to WP default login
    add_action('login_form', [$this_class, 'add_custom_fields']);

    // Filter the native WP URLs with the plugin custom ones
    add_filter('login_url', [$this_class, 'filter_login_url'], 10, 2);
    add_filter('logout_url', [$this_class, 'filter_logout_url'], 10, 2);
    add_filter('lostpassword_url', [$this_class, 'filter_lostpassword_url'], 10, 2);
    add_filter('authenticate', [$this_class, 'successfully_authenticate'], 30, 3);

}

// Add Captcha as custom fields to WordPress default login form
public function add_custom_fields(){

    $recaptcha = afzfp_get_option('enable_captcha_login', 'afzfp_profile');

    if('on' == $recaptcha){
        AFZFP_Captcha_Recaptcha::display_captcha();
    }
}

// Get action url based on action type
public function get_action_url($action = 'login', $redirect_to = ''){

    $root_url = $this->get_login_url();

    switch($action){
        case 'resetpass':
            return add_query_arg(['action' => 'resetpass'], $root_url);
        break;

        case 'lostpassword':
            return add_query_arg(['action' => 'lostpassword'], $root_url);
        break;

        case 'logout':
            return wp_nonce_url(add_query_arg(['action' => 'logout'], $root_url), 'log-out');
        break;

        default:
            if(empty($redirect_to)){
                return $root_url;
            }
        return add_query_arg(['redirect_to' => urlencode($redirect_to)], $root_url);
    }
}

// Get login page url
public function get_login_url(){

    $page_id = afzfp_get_option('login_page', 'afzfp_pages', false);

    if(!$page_id){
        return false;
    }

    $url = get_permalink($page_id);

    return apply_filters('afzfp_login_url', $url, $page_id);
}

// Filter the login url with ours
public function filter_login_url($url, $redirect){
    return $this->get_action_url('login', $redirect);
}

// Filter the logout url with ours
public function filter_logout_url($url, $redirect){
    return $this->get_action_url('logout', $redirect);
}

// Filter the lost password url with ours
public function filter_lostpassword_url($url, $redirect){
    return $this->get_action_url('lostpassword', $redirect);
}

// Get actions links for displaying in forms
public function lost_password_links(){
    $links = sprintf('<a href="%s">%s</a>', $this->get_action_url('lostpassword'), __('Lost Password', 'afzfp'));

    return $links;
}

// Shows the login form for the [afzfp_login] shortcode
public function login_form(){

    $login_page = $this->get_login_url();

    ob_start();

    // If the user is already logged in, display the logged-in template
    if(is_user_logged_in()){
        afzfp_load_template(
            'logged-in.php',
            [
                'user' => wp_get_current_user(),
            ]
        );

    // Else, display the login form with the corresponding action if specified
    }else{

        $action = isset($_GET['action']) ? $_GET['action'] : 'login';
        $args = [
            'action_url' => $login_page,
        ];

        switch($action){

        case 'lostpassword':
            wp_cache_set( 'afzfp_login_message', __('Please enter your username or email address. You will receive a link to create a new password via email.', 'afzfp') );

            afzfp_load_template('lost-pass.php', $args);
        break;

        case 'rp':
        case 'resetpass':
            if(isset($_GET['reset'])){
                printf('<div class="afzfp-message">'.esc_html__('Your password has been reset.', 'afzfp').'</div>');
                afzfp_load_template('login.php', $args);

                return;
            }else{
                wp_cache_set( 'afzfp_login_message', __('Enter your new password below...', 'afzfp') );
                afzfp_load_template('reset-pass.php', $args);
            }
        break;

        default:
            if(isset($_GET['checkemail'])){
                wp_cache_set( 'afzfp_login_message', __('Check your e-mail for the confirmation link.', 'afzfp') );
            }

            if(isset($_GET['loggedout'])){
                wp_cache_set( 'afzfp_login_message', __('You are now logged out.', 'afzfp') );
            }

            afzfp_load_template('login.php', $args);
        }
    }

    return ob_get_clean();
}

// Process login form
public function process_login(){

    if(!isset($_POST['nonce_field_login'])){
        return;
    }
        
    // Credentials that will be used with wp_signon()
    $creds = [];

    // Verify nonce
    if(!wp_verify_nonce($_POST['nonce_field_login'], 'afzfp_login_action')){
        wp_cache_set( 'afzfp_login_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Nonce value could not be verified, please try again.', 'afzfp') );
        return;
    }

    // Verify username or email is set
    if(empty($_POST['mailoruser'])){
        wp_cache_set( 'afzfp_login_message',  __('Username or email is required.', 'afzfp') );
        return;
    }else{

        // Get user by mail or by username (depending on user input)
        $unslashed_mailoruser = wp_unslash($_POST['mailoruser']);
        if(is_email($unslashed_mailoruser)){
            $user = get_user_by('email', sanitize_email($unslashed_mailoruser));
        }else{
            $user = get_user_by('login', sanitize_user($unslashed_mailoruser));
        }

        // Check it actuay exists
        if($user){
            $creds['user_login'] = $user->user_login;
        }else{
            wp_cache_set( 'afzfp_login_message',  __('The provided login information is incorrect', 'afzfp') );
            return;
        }

    }

    // Verify password is set
    if(empty($_POST['pwd'])){
        wp_cache_set( 'afzfp_login_message', __('Password is required.', 'afzfp') );
        return;
    }else{
        $creds['user_password'] = $_POST['pwd'];
    }

    // Verify reCaptcha
    if(isset($_POST['g-recaptcha-response'])){
        if(empty($_POST['g-recaptcha-response'])){
            wp_cache_set( 'afzfp_login_message',  __('reCaptcha is required', 'afzfp') );
            return;
        }else{
            $no_captcha = 1;
            $invisible_captcha = 0;

            AFZFP_Captcha_Recaptcha::captcha_verification();
        }
    }

    // Check if user is already verified
    $user_activation_status = get_user_meta($user->ID, 'afzfp_user_status', true);

    if($user_activation_status == 'pending'){

        $user_activation_method = afzfp_get_option('user_activation_method', 'afzfp_profile');

        if($user_activation_method == 'activation_mail'){
            wp_cache_set( 'afzfp_login_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Please verify your account.', 'afzfp') );
            return;
        }else{
            wp_cache_set( 'afzfp_login_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Your account hasn\'t been approved by the administrator yet.', 'afzfp') );
            return;
        }

    }elseif($user_activation_status == 'rejected'){
        wp_cache_set( 'afzfp_login_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Your account was rejected by the administrator.', 'afzfp') );
        return;
    }

    // Set up remember flag
    $creds['remember'] = isset($_POST['rememberme']);

    // Try to sign on and set user cookie
    $user = wp_signon($creds, is_ssl() );

    if(is_wp_error($user)){
        wp_cache_set( 'afzfp_login_message', __('The provided login information is incorrect', 'afzfp') );
        return;
    }else{
        $redirect = $this->login_redirect();

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID );

        wp_safe_redirect(apply_filters('afzfp_login_redirect', $redirect, $user));
        exit;
    }

}

// Redirect user to a specific page after login
public function login_redirect(){
    $redirect_to = afzfp_get_option('redirect_after_login_page', 'afzfp_profile', false);

    if('previous_page' == $redirect_to && !empty($_POST['redirect_to'])){
        return esc_url(wp_unslash($_POST['redirect_to']));
    }

    $redirect = get_permalink($redirect_to);

    if(!empty($redirect)){
        return $redirect;
    }

    return home_url();
}

// Logout the user
public function process_logout(){
    if(isset($_GET['action']) && 'logout' == $_GET['action']){
        check_admin_referer('log-out');
        wp_logout();

        $redirect_to = !empty($_REQUEST['redirect_to']) ? sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])) : add_query_arg(['loggedout' => 'true'], $this->get_login_url());
        wp_safe_redirect($redirect_to);
        exit();
    }
}

// Handle reset password form
public function process_reset_password(){

    if(!isset($_POST['afzfp_reset_password_step'])){
        return;
    }

    // Verify nonce
    if(!isset($_POST['afzfp_reset_nonce_field']) 
    || !wp_verify_nonce($_POST['afzfp_reset_nonce_field'], 'afzfp_reset_action')){
        wp_cache_set( 'afzfp_login_message', '<strong>'.__('Error', 'afzfp').':</strong> '.__('Nonce value could not be verified, please try again.', 'afzfp') );
        return;
    }

    // First, process the part of sending a recovery email
    if('send-recovery-email' == $_POST['afzfp_reset_password_step']){

        if($this->send_password_recovery_email()){
            wp_redirect(add_query_arg(['checkemail' => 'confirm'], $this->get_login_url()));
            exit;
        }

    // Then, in the second step, do the actual update
    }elseif('update-user-password' == $_POST['afzfp_reset_password_step']){


        // Process reset password form
        if(isset($_POST['pass1']) 
        && isset($_POST['pass2']) 
        && isset($_POST['key']) 
        && isset($_POST['login'])){

        // Check the reset key
        $user = check_password_reset_key($_POST['key'], sanitize_user($_POST['login']));

            if(is_object($user)){

                // Save these values into the form again in case of errors
                $args['key'] = $_POST['key'];
                $args['login'] = sanitize_user($_POST['login']);

                if(empty($_POST['pass1']) || empty($_POST['pass2'])){
                    wp_cache_set( 'afzfp_login_message', __('Please enter your password.', 'afzfp') );
                    return;
                }

                if($_POST['pass1'] !== $_POST['pass2']){
                    wp_cache_set( 'afzfp_login_message', __('Passwords do not match.', 'afzfp') );
                    return;
                }

                $enable_strong_pwd = afzfp_get_option('strong_password', 'afzfp_general');

                if('off' != $enable_strong_pwd){
                    if(false == afzfp_check_if_password_secure($_POST['pass1'])){
                        // Add message indicating complexity issue
                        $messages['password_complexity'] = '<p class="error">'.__('Your password must contain 12 characters including at least 1 uppercase, 1 lowercase letter and 1 number.', 'afzfp').'.</p>';
                    }
                }

                if(!wp_cache_get('afzfp_login_error')){
                    $this->reset_password($user, $_POST['pass1']);
                    wp_redirect(add_query_arg('reset', 'true', remove_query_arg(['key', 'login'])));
                    exit;
                }
                
            }
        }

    }

}

// Handles sending password retrieval email to customer
public function send_password_recovery_email(){

    if(is_email($_POST['login'])){
        $user = get_user_by('email', sanitize_email(wp_unslash($_POST['login'])));
    }else{
        $user = get_user_by('login', sanitize_user(wp_unslash($_POST['login'])));
    }

    if(!$user){
        wp_cache_set( 'afzfp_login_message', __('Invalid username or e-mail.', 'afzfp') );
        return false;
    }

    $key = get_password_reset_key($user);

    $reset_url = add_query_arg(
        [
            'action' => 'rp',
            'key'    => $key,
            'login'  => urlencode($user->user_login),
        ],
        $this->get_login_url()
    );

    $message = __('Someone requested that the password be reset for the following account:', 'afzfp')."\r\n\r\n";
    $message .= network_home_url('/')."\r\n\r\n";
    $message .= sprintf(esc_html__('Username: %s', 'afzfp'), $user->user_login)."\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'afzfp')."\r\n\r\n";
    $message .= esc_html_e('To reset your password, visit the following address:', 'afzfp')."\r\n\r\n";
    $message .= ' '.$reset_url." \r\n";

    $message = apply_filters('reset_password_email_message', $message, $key, $user->user_login);

    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    if(is_multisite()){
        $blogname = $GLOBALS['current_site']->site_name;
    }

    $subject = sprintf(esc_html__('[%s] Password Reset', 'afzfp'), $blogname);
    $subject = apply_filters('reset_password_email_subject', $subject);
   
    if(!wp_mail($user->user_email, wp_specialchars_decode($subject), $message)){
        wp_die(esc_html_e('The e-mail could not be sent.', 'afzfp')."<br />\n".esc_html_e('Possible reason: your host may have disabled the mail() function.', 'afzfp'));
    }

    return true;
}

// Successful authenticate when enable email verification in registration
public function successfully_authenticate($user, $username, $password){

    if(!is_wp_error($user)){
        if($user->ID){
            $resend_link = add_query_arg('resend_activation', $user->ID, $this->get_login_url());
            $error = new WP_Error();
            $afzfp_user = new afzfp_User($user->ID);
            if(!$afzfp_user->is_verified()){

                $error->add('acitve_user', sprintf(__('<strong>Your account is not active.</strong><br>Please check your email for activation link. <br><a href="%s">Click here</a> to resend the activation link', 'afzfp'), $resend_link));

                return $error;
            }
        }
    }

    return $user;
}

// Check in activation of user registration
public function activation_user_registration(){

    if(!isset($_GET['afzfp_registration_activation']) && empty($_GET['afzfp_registration_activation'])){
        return;
    }

    if(!isset($_GET['id']) && empty($_GET['id'])){
        wp_cache_set( 'afzfp_login_message', __('Activation URL is not valid', 'afzfp'));
        return;
    }

    $user_id = intval($_GET['id']);
    $user = new AFZFP_User($user_id);

    if(!$user){
        wp_cache_set( 'afzfp_login_message', __('Invalid User activation url', 'afzfp'));
        return;
    }

    $afzfp_user_status = get_user_meta($user_id, 'afzfp_user_status', true);

    if($user->is_verified()){
        wp_cache_set( 'afzfp_login_message', __('User already verified', 'afzfp'));
        return;
    }

    $activation_key = sanitize_text_field(wp_unslash($_GET['afzfp_registration_activation']));

    if($user->get_activation_key() != $activation_key){
        wp_cache_set( 'afzfp_login_message', __('Activation URL is not valid', 'afzfp'));
        return;
    }

    $user->mark_verified();
    $user->remove_activation_key();

    wp_cache_set( 'afzfp_login_message', __('Your account has been verified', 'afzfp') );

    // show activation message.
    add_filter('wp_login_errors', [$this, 'user_activation_message']);
    
    add_filter('redirect_canonical', '__return_false');
    do_action('afzfp_user_activated', $user_id);
}

// Shows activation message on success to wp-login.php
public function user_activation_message(){
    return new WP_Error('user-activated', __('Your account has been verified', 'afzfp'), 'message');
}

// Redirect user to page after log in
public function wp_login_page_redirect(){
    global $pagenow;

    if (!is_admin() && 'wp-login.php' == $pagenow && isset($_GET['action']) && 'register' == $_GET['action']) {
        $reg_page = get_permalink(afzfp_get_option('register_page', 'afzfp_pages'));
        wp_redirect($reg_page);
        exit;
    }
}

// Handles resetting the user's password
public function reset_password($user, $new_pass){

    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    
    wp_set_password($new_pass, $user->ID);
    
    // Email password change notification to Admin
    $change_password_admin_mail = afzfp_get_option('change_password_admin_mail', 'afzfp_emails_notification', 'on');
    if('on' == $change_password_admin_mail){
        wp_password_change_notification($user);
    }

    // Email password change notification to the user
    $message = 'Your password has been changed.';
    $subject = '['.$blogname.'] Password changed';
    $password_change_mail = afzfp_get_option('password_change_mail', 'afzfp_emails_notification', 'on');
    if('on' == $password_change_mail){
        wp_mail($user->user_email, $subject, $message);
    }
}

// Show errors on the form
public function show_message(){

    $login_message = wp_cache_get( 'afzfp_login_message' );

    if($login_message){    
        echo '<div class="afzfp-message">'.$login_message.'</div>';
    }

}

// Return POST value for given array key
public function get_post_value($key){

    if(isset($_POST[$key])){
        return wp_unslash($_POST[$key]);
    }

    return '';
}

}
}
<?php
if(!class_exists('AFZFP_User')){

class AFZFP_User{

// User id
public $id;

// User Object
public $user;

public function __construct($user){
    if(is_numeric($user)){
        $the_user = get_user_by('id', $user);
        if($the_user){
            $this->id = $the_user->ID;
            $this->user = $the_user;
        }
    }elseif(is_a($user, 'WP_User')) {
        $this->id = $user->ID;
        $this->user = $user;
    }
}

// Get user meta status
public function get_status(){
    return get_user_meta($this->id, 'afzfp_user_status', true);
}

// Check if user is verified
public function is_verified(){

    $user_status = $this->get_status();

    if($user_status == 'pending' || $user_status == 'rejected'){
        return false;
    }

    return true;
}

// Mark user as verified
public function mark_verified(){
    update_user_meta($this->id, 'afzfp_user_status', 'verified');
}

// Mark user as unverified
public function mark_unverified(){
    update_user_meta($this->id, 'afzfp_user_status', 'pending');
}

// Set user activation key
public function set_activation_key($key){
    update_user_meta($this->id, 'afzfp_activation_key', $key);
}

// Get user activation key
public function get_activation_key(){
    return get_user_meta($this->id, 'afzfp_activation_key', true);
}

// Remove user activation key
public function remove_activation_key(){
    delete_user_meta($this->id, 'afzfp_activation_key');
}

// Send the user a confirmation E-mail
public function send_confirmation_email($user){

    $userdata = get_userdata($user);

    if($user && !is_wp_error($user)){
        
        // Generate activation code
        $code = sha1($user.time());

        // Save activation code
        update_user_meta($user, 'afzfp_user_activation_code', $code, true);

        $login_page = afzfp_get_option('login_page', 'afzfp_pages');
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $activation_link = add_query_arg(
            [
                'key'  => $code,
                'user' => $user,
            ],
            get_permalink($login_page)
        );

        $message = __('Click the link to activate your account').'<br><br>';
        $message .= '<a href="'.$activation_link.'">'.$activation_link.'</a>';
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($userdata->user_email, __('Activate yor account'), $message, $headers);

    }
}

}
}
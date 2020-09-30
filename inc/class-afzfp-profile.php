<?php
if(!class_exists('AFZFP_Profile')){

// User Profile handler class
class AFZFP_Profile{

// Error array
private $login_errors = [];

// Message array
private $messages = [];

// Constructor
public function __construct(){}

// Set up actions and filters
public static function setup_actions_and_filters(){

    $this_class = new self();

    add_shortcode('afzfp_profile', [$this_class, 'user_profile']);
    add_shortcode('afzfp_profile_edit', [$this_class, 'user_profile_edit']);

    
    add_action('afzfp_before_tabs', [$this_class, 'save_fields'], 5, 2);
    add_action('afzfp_before_tabs', [$this_class, 'save_password'], 10, 2);

    add_filter('afzfp_tabs', [$this_class, 'add_profile_tab'], 10);
    add_filter('afzfp_tabs', [$this_class, 'add_password_tab'], 20);
    add_filter('afzfp_fields_profile', [$this_class, 'default_profile_tab_fields'], 10);
    add_filter('afzfp_fields_password', [$this_class, 'default_password_tab_fields'], 10);
    

}

// Add the profile tab to the profile output
public function add_profile_tab($tabs){

    // Add our tab to the tabs array
    $tabs[] = [
        'id'            => 'profile',
        'label'         => __('Profile', 'afzfp'),
        'tab_class'     => 'profile-tab',
        'content_class' => 'profile-content',
        'callback'      => 'afzfp_profile_tab_content',
    ];

    return $tabs;
}

// Adds the password tab to the profile output
public function add_password_tab($tabs){

    // Add our tab to the tabs array
    $tabs[] = [
        'id'            => 'password',
        'label'         => __('Password', 'afzfp'),
        'tab_class'     => 'password-tab',
        'content_class' => 'password-content',
    ];

    return $tabs;
}

// Default profile tab fields
function default_profile_tab_fields($fields){
    $fields[] = [
        'id'      => 'user_email',
        'label'   => __('Email Address', 'afzfp'),
        'desc'    => __('Edit your email address - used for resetting your password etc.', 'afzfp'),
        'type'    => 'email',
        'classes' => 'user_email',
    ];

    $fields[] = [
        'id'      => 'first_name',
        'label'   => __('First Name', 'afzfp'),
        'desc'    => __('Edit your first name.', 'afzfp'),
        'type'    => 'text',
        'classes' => 'first_name',
    ];

    $fields[] = [
        'id'      => 'last_name',
        'label'   => __('Last Name', 'afzfp'),
        'desc'    => __('Edit your last name.', 'afzfp'),
        'type'    => 'text',
        'classes' => 'last_name',
    ];

    $fields[] = [
        'id'      => 'user_url',
        'label'   => __('URL', 'afzfp'),
        'desc'    => __('Edit your profile associated URL.', 'afzfp'),
        'type'    => 'text',
        'classes' => 'user_url',
    ];

    $fields[] = [
        'id'      => 'description',
        'label'   => __('Description/Bio', 'afzfp'),
        'desc'    => __('Edit your description/bio.', 'afzfp'),
        'type'    => 'wysiwyg',
        'classes' => 'description',
    ];

    return $fields;
}

// Default password tab fields
function default_password_tab_fields($fields){
    $fields[] = [
        'id'      => 'user_pass',
        'label'   => __('Password', 'afzfp'),
        'desc'    => __('New Password', 'afzfp'),
        'type'    => 'password',
        'classes' => 'user_pass',
    ];

    return $fields;
}


// Display the User Profile Page
public function user_profile(){

    $profile_page = afzfp_get_option('profile_page', 'afzfp_pages', false);

    ob_start();

    afzfp_load_template('profile.php', $profile_page);

    return ob_get_clean();
}

// Display the User Edit Profile Page
public function user_profile_edit(){

    $edit_page = afzfp_get_option('profile_edit_page', 'afzfp_pages', false);

    ob_start();

    afzfp_load_template('profile-edit.php', $edit_page);

    return ob_get_clean();
}

// Add Error message
public function add_error($message){
    $this->login_errors[] = $message;
}

// Add info message
public function add_message($message){
    $this->messages[] = $message;
}

// Show errors on the form.
public function show_errors(){
    if ($this->login_errors){
        foreach ($this->login_errors as $error){
            echo '<div class="afzfp-error">';
            echo esc_html($error);
            echo '</div>';
        }
    }
}

// Show messages on the form.
public function show_messages(){
    if ($this->messages) {
        foreach ($this->messages as $message) {
            printf('<div class="afzfp-message">%s</div>', esc_html($message));
        }
    }
}

// Get User Profile page url.
public function get_profile_url(){
    $page_id = afzfp_get_option('profile_page', 'afzfp_pages', false);

    if(!$page_id){
        return false;
    }

    $url = get_permalink($page_id);

    return apply_filters('afzfp_profile_url', $url, $page_id);
}

// Save fields
public function save_fields($tabs, $user_id){

    // Check the nonce
    if(!isset($_POST['afzfp_nonce_name']) || !wp_verify_nonce($_POST['afzfp_nonce_name'], 'afzfp_nonce_action')){
        return;
    }

    // Array to store messages
    $messages = [];

    // The POST data
    $tabs_data = $_POST;

    /**
    * Remove the following array elements from the data
    * password
    * nonce name
    * wp refer - sent with nonce.
    */
    unset($tabs_data['password']);
    unset($tabs_data['afzfp_nonce_name']);
    unset($tabs_data['_wp_http_referer']);
    unset($tabs_data['description']);

    // Lets check we have some data to save
    if(empty($tabs_data)){
        return;
    }

    /**
    * Setup an array of reserved meta keys
    * to process in a different way
    * they are not meta data in WordPress
    * reserved names are user_url and user_email as they are stored in the users table not user meta.
    */
    $reserved_ids = apply_filters(
        'afzfp_reserved_ids',
        [
            'user_email',
            'user_url',
        ]
    );

    // Array of registered fields
    $registered_fields = [];
    foreach ($tabs as $tab) {
        $tab_fields = apply_filters(
            'afzfp_fields_'.$tab['id'],
            [],
            $user_id
        );
        $registered_fields = array_merge($registered_fields, $tab_fields);
    }

    // Set an array of registered keys
    $registered_keys = wp_list_pluck($registered_fields, 'id');

    // Loop through the data array - each element of this will be a tabs data
    foreach($tabs_data as $tab_data){
        
        /**
        * Loop through this tabs array
        * the ket here is the meta key to save to
        * the value is the value we want to actually save.
        */
        foreach($tab_data as $key => $value){

            // If the key is the save submit - move to next in array
            if('afzfp_save' == $key || 'afzfp_nonce_action' == $key){
                continue;
            }

            // If the key is not in our list of registered keys - move to next in array
            if(!in_array($key, $registered_keys)){
                continue;
            }

            // Check whether the key is reserved - handled with wp_update_user
            if(in_array($key, $reserved_ids)){
                
                $user_id = wp_update_user(
                    [
                        'ID' => $user_id,
                        $key => $value,
                    ]
                );

                // Check for errors
                if(is_wp_error($user_id)){

                    // Update failed
                    $messages['update_failed'] = '<p class="error">There was a problem with updating your profile.</p>';
                }

            // Standard user meta - handle with update_user_meta
            }else{

                // Lookup field options by key
                $registered_field_key = array_search($key, array_column($registered_fields, 'id'));
                
                // Sanitize user input based on field type
                switch($registered_fields[$registered_field_key]['type']){
                    case 'select':
                    case 'radio':
                        $value = sanitize_text_field($value);
                    break;
                    case 'wysiwyg':
                        $value = wp_filter_post_kses($value);
                    break;
                    case 'textarea':
                        $value = wp_filter_nohtml_kses($value);
                    break;
                    case 'checkbox':
                        $value = isset($value) && '1' === $value ? true : false;
                    break;
                    case 'checkboxes':
                    case 'select multiple':
                        $oldvalue = $value;
                        $value = [];
                        foreach($oldvalue as $v){
                            if($v === '-'){
                                continue;
                            }
                            $value[] = sanitize_text_field($v);
                        }
                    break;
                    case 'email':
                        $value = sanitize_email($value);
                    break;
                    default:
                        $value = sanitize_text_field($value);
                }

                // Update the user meta data
                if(isset($registered_fields[$registered_field_key]['taxonomy'])){
                    $meta = wp_set_object_terms($user_id, $value, $registered_fields[$registered_field_key]['taxonomy'], false);
                }else{
                    $meta = update_user_meta($user_id, $key, $value);
                }

                // Check the update was successful
                if (false == $meta) {

                    // Update failed
                    $messages['update_failed'] = '<p class="error">There was a problem with updating your profile.</p>';
                }
            }
        } // End tab loop
    } // End data loop

    // Update user bio
    if(isset($_POST['description'])){
        wp_update_user(
            [
                'ID'          => $user_id,
                'description' => sanitize_text_field(wp_unslash($_POST['description'])),
            ]
        );
    }

    // Check if we have an messages to output
    if(empty($messages)){
    ?>
		<div class="afzfp-notice error">
		<?php
        // Lets loop through the messages stored
        foreach($messages as $message){
            // Output the message
            echo wp_kses(
                $message,
                [
                    'p' => [
                        'class' => [],
                    ],
                ]
            );
        }
        ?>
		</div><!-- // messages -->
	<?php
    }else{
    ?>
		<div class="afzfp-notice"><p class="updated"><?php esc_html_e('Your profile was updated successfully!', 'afzfp'); ?></p></div>
	<?php
    }
    ?>
	<?php
}

// Save password
function save_password($tabs, $user_id){

    // Array to store messages
    $messages = [];

    // Check the nonce
    if(!isset($_POST['afzfp_nonce_name']) || !wp_verify_nonce($_POST['afzfp_nonce_name'], 'afzfp_nonce_action')){
        return;
    }

    $data = (isset($_POST['password'])) ? $_POST['password'] : '';

    // Make sure the password is not empty
    if(empty($data)){
        return;
    }

    // Check that the password match
    if($data['user_pass'] != $data['user_pass_check']){
        $messages['password_mismatch'] = '<p class="error">'.sprintf(__('Please make sure the passwords match', 'afzfp')).'.</p>';
    }

    $enable_strong_pwd = afzfp_get_option('strong_password', 'afzfp_general');

    if('off' != $enable_strong_pwd){
        if(false == afzfp_check_if_password_secure($data['user_pass'])){
            // Add message indicating complexity issue
            $messages['password_complexity'] = '<p class="error">'.__('Your password must contain 12 characters including at least 1 uppercase, 1 lowercase letter and 1 number.', 'afzfp').'.</p>';
        }
    }

    // Check we have any messages in the messages array - if we have password failed at some point
    if(empty($messages)){
        
        // The password can now be updated and redirect the user to the login page
        wp_set_password($data['user_pass'], $user_id);
        
        // Translators: %s: login link
        $successfully_msg = '<div class="messages"><p class="updated">'.sprintf(__('You\'re password was successfully changed and you have been logged out. Please <a href="%s">login again here</a>.', 'afzfp'), esc_url(wp_login_url())).'</p></div>';
        echo wp_kses(
            $successfully_msg,
            [
                'div' => [
                    'class' => [],
                ],
                'p'   => [
                    'class' => [],
                ],
                'a'   => [
                    'href' => [],
                ],
            ]
        );

        // User password change email to admin
        $user = wp_get_current_user();
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $change_password_admin_mail = afzfp_get_option('change_password_admin_mail', 'afzfp_emails_notification', 'on');
        if('off' != $change_password_admin_mail){
            wp_password_change_notification($user);
        }

        // User password change email to admin.
        $message = $user->user_login.' Your password has been changed.';
        $subject = '['.$blogname.'] Password changed';
        $password_change_mail = afzfp_get_option('password_change_mail', 'afzfp_emails_notification', 'on');
        if('off' != $password_change_mail){
            wp_mail($user->user_email, $subject, $message);
        }
        
    // Messages not empty therefore password failed
    }else{
    ?>
		<div class="afzfp-notice error">
		<?php
        foreach ($messages as $message) {
            echo wp_kses(
                $message,
                [

                    'p' => [
                        'class' => [],
                    ],
                ]
            );
        }
        ?>
		</div>
	<?php
    }

}

}
}
<?php
// Main class for WP Frontend Profile
if(!class_exists('AFZFP_Main')){

class AFZFP_Main{

public function __construct(){}

// Set up actions and filters
public static function setup_actions_and_filters(){

    $this_class = new self();

    // Actions
    add_action('wp_enqueue_scripts', [$this_class, 'enqueue_scripts_and_styles']);

    // Filters
    add_filter('show_admin_bar', [$this_class, 'show_admin_bar']);

    // Specific for WordPress backend
    if(is_admin()){

        // Redirect non admins
        add_action( 'admin_init', [$this_class, 'redirect_non_admin_user']);

        // Add custom fields near the bottom of the WP Admin profile page
        add_action('show_user_profile', [$this_class, 'user_profile_fields']); // Current user
        add_action('edit_user_profile', [$this_class, 'user_profile_fields']); // Not current user

        // Save custom fields of the WP Admin profile page 
        add_action('personal_options_update', [$this_class, 'update_profile_fields']); // Current user
        add_action('edit_user_profile_update', [$this_class, 'update_profile_fields']); // Not current user

        // Add status column to the WP Admin users page
        add_filter('manage_users_columns', [$this_class, 'add_column']);
        add_filter('manage_users_custom_column', [$this_class, 'status_column'], 10, 3);

        // Run when plugin is activated
        register_activation_hook(__FILE__, [$this_class, 'plugin_activation']);

        // Add the links and handles to manually update user status
        $manually_approve_user = afzfp_get_option('admin_manually_approve', 'afzfp_profile', 'on');
        if('on' == $manually_approve_user){

            add_filter('user_row_actions', [$this_class, 'user_table_actions'], 10, 2);
            add_action('load-users.php', [$this_class, 'update_action']);

        }

    }
}

// Show/hide admin bar to the permitted user level.
public function show_admin_bar($show){
    
    if(!is_user_logged_in()){
        return false;
    }

    $roles = afzfp_get_option('show_admin_bar_to_roles', 'afzfp_general', ['administrator', 'editor', 'author', 'contributor', 'subscriber']);
    $roles = $roles ? $roles : [];
    $current_user = wp_get_current_user();

    if(isset($current_user->roles[0])) {
        if(!in_array($current_user->roles[0], $roles)) {
            return false;
        }
    }

    return $show;
}

// Update plugin install time if not set
public function plugin_activation(){
    if (false === get_option('afzfp_install_time')) {
        update_option('afzfp_install_time', time());
    }
}

// Enqueue scripts/styles
public function enqueue_scripts_and_styles(){

    // Include afzfp CSS
    wp_enqueue_style('style-afzfp', plugins_url('/assets/css/afzfp.css', dirname(__FILE__)));

}

// User status metabox in user admin interface
public function user_profile_fields($user){
?>
<table class="form-table">
    <tr>
        <th>
            <label for="afzfp_user_status"><?php _e('User status', 'afzfp'); ?></label>
        </th>
        <td>
            <?php
            $current_user_status = esc_attr(get_the_author_meta( 'afzfp_user_status', $user->ID));
            ?>
            <select name="afzfp_user_status">
                <option value="pending" <?php echo ($current_user_status=='pending')?'selected':'';?>><?php _e('Pending approval', 'afzfp'); ?></option>
                <option value="verified" <?php echo ($current_user_status=='verified')?'selected':'';?>><?php _e('Verified', 'afzfp'); ?></option>
            </select>
        </td>
    </tr>

    <?php
    // Allow adding extra custom meta
    do_action('afzfp_user_profile_fields', $user->ID);
    ?>
    
</table>
<?php
}

// Update user admin interface fields
public function update_profile_fields($user_id){
    if(current_user_can('edit_user', $user_id)){
        
        update_user_meta($user_id, 'afzfp_user_status', sanitize_text_field($_POST['afzfp_user_status']));

        // Allow handling the update for extra custom meta
        do_action('afzfp_update_profile_fields', $user_id);
        
    }
}

// Add the status column to the user list in the WP Admin
public function add_column($columns){
    $columns['afzfp_user_status'] = __('Status', 'afzfp');
    return $columns;
}

// Add the status column value
function status_column($val_column, $column_name, $user){
    $status='';
    switch($column_name){
        case 'afzfp_user_status':
            $user_status = get_user_meta($user, 'afzfp_user_status', true);
            if('verified' == $user_status) {
                $status = __('Verified', 'afzfp');
            }elseif('pending' == $user_status) {
                $status = __('Pending', 'afzfp');
            }elseif('rejected' == $user_status) {
                $status = __('Rejected', 'afzfp');
            }else{
                $status = __('No status found', 'afzfp');
            }
            return $status;
        break;
    }

    return $val_column;
}

// Add the approve or deny link where appropriate
function user_table_actions($actions, $user){
    if(get_current_user_id() == $user->ID){
        return $actions;
    }
    if(is_super_admin($user->ID)){
        return $actions;
    }
    $user_status = get_user_meta($user->ID, 'afzfp_user_status', true);
    $approve_link = add_query_arg(
        [
            'action' => 'approve',
            'user'   => $user->ID,
        ]
    );
    $approve_link = remove_query_arg(['new_role'], $approve_link);
    $approve_link = wp_nonce_url($approve_link, 'new-user-approve');
    $reject_link = add_query_arg(
        [
            'action' => 'rejected',
            'user'   => $user->ID,
        ]
    );
    $reject_link = remove_query_arg(['new_role'], $reject_link);
    $reject_link = wp_nonce_url($reject_link, 'new-user-approve');
    $approve_action = '<a href="'.esc_url($approve_link).'">'.__('Approve', 'afzfp').'</a>';
    $deny_action = '<a href="'.esc_url($reject_link).'">'.__('Rejected', 'afzfp').'</a>';
    if('pending' == $user_status){
        $actions[] = $approve_action;
    }elseif('approve' == $user_status){
        $actions[] = $deny_action;
    }elseif ('rejected' == $user_status){
        $actions[] = $approve_action;
    }

    return $actions;
}

// Update action
function update_action(){
    if(!empty($_GET['action']) ? sanitize_text_field($_GET['action']) : '' && in_array(sanitize_text_field(wp_unslash($_GET['action'])), ['approve', 'rejected']) && !empty($_GET['new_role'] ? sanitize_text_field(wp_unslash($_GET['new_role'])) : '')){
        $request = sanitize_text_field(wp_unslash($_GET['action']));
        $request_id = intval($_GET['user']);
        $user_data = get_userdata($request_id);
        if('approve' == $request){
            update_user_meta($request_id, 'afzfp_user_status', $request);
            $subject = 'Approval notification';
            $message = 'Your account is approved by admin.'."\r\n\r\n";
            $message .= 'Now you can log in to your account.'."\r\n\r\n";
            $message .= 'Thank you'."\r\n\r\n";
            wp_mail($user_data->user_email, $subject, $message);
        }
        if('rejected' == $request){
            update_user_meta($request_id, 'afzfp_user_status', $request);
            $subject = 'Denied notification';
            $message = 'Your account is denied by admin.'."\r\n\r\n";
            $message .= 'Now you cannot Log In to your account.'."\r\n\r\n";
            $message .= 'Thank you'."\r\n\r\n";
            wp_mail($user_data->user_email, $subject, $message);
        }
    }
}

public function redirect_non_admin_user(){
    if(is_user_logged_in()){
        if(!defined('DOING_AJAX') && !current_user_can('administrator')){
            wp_redirect( site_url() );  exit;
        }
    }
}

}

}
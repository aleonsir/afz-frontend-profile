<?php

defined('ABSPATH') || exit;

// Generates the list item for a tab heading (the actual tab!)
function afzfp_tab_list_item($tab){

    // Build the tab class
    $tab_class = 'tab';

    // If we have a tab class to add
    if(' ' !== $tab['tab_class']){

        // Add the tab class to our variable
        $tab_class .= ' '.$tab['tab_class'];
    }
    ?>
	<li class="<?php echo esc_attr($tab_class); ?>">
		<a href="#<?php echo esc_attr($tab['id']); ?>"><?php echo esc_html($tab['label']); ?></a>
	</li>
	<?php
}

/**
 * Function afzfp_default_tab_content()
 * outputs the fields for a tab inside a tab
 * this function is only used if a specific callback is not declared when filtering afzfp_tabs.
 */
function afzfp_default_tab_content($tab){

    // Fires before the fields of the tab are outputted
    do_action('afzfp_before_tab_fields', $tab, get_current_user_id());

    // Build an array of fields to output
    $fields = apply_filters(
        'afzfp_fields_'.$tab['id'],
        [],
        get_current_user_ID()
    );

    // Check we have some fields
    if (!empty($fields)) {

        /* output a wrapper div and form opener */ ?>

			<div class="afzfp-fields">

				<?php

                    /* start a counter */
                    $counter = 1;

        /* get the total number of fields in the array */
        $total_fields = count($fields);

        /* lets loop through our fields array */
        foreach ($fields as $field) {

                        /* set a base counting class */
            $count_class = ' afzfp-'.$field['type'].'-field afzfp-field-'.$counter;

            /* build our counter class - check if the counter is 1 */
            if (1 === $counter) {

                            /* this is the first field element */
                $counting_class = $count_class.' first';

            /* is the counter equal to the total number of fields */
            } elseif ($counter === $total_fields) {

                            /* this is the last field element */
                $counting_class = $count_class.' last';

            /* if not first or last */
            } else {

                            /* set to base count class only */
                $counting_class = $count_class;
            }

            /* build a var for classes to add to the wrapper */
            $classes = (empty($field['classes'])) ? '' : ' '.$field['classes'];

            /* build ful classes array */
            $classes = $counting_class.$classes;

            /* output the field */
            afzfp_field($field, $classes, $tab['id'], get_current_user_id());

            /* increment the counter */
            $counter++;
        } // end for each field

                    /* output a closing wrapper div */
                ?>

			</div>

		<?php
    } // end if have fields.

    // Fires after the fields of the tab are outputted
    do_action('afzfp_after_tab_fields', $tab, get_current_user_id());
}

// Retrieves an array of valid options for a field
function afzfp_field_get_options($field){
    if ($field['taxonomy']) {
        $terms = get_terms($field['taxonomy'], ['hide_empty' => false]);
        $options = [];
        foreach ($terms as $term) {
            $options[] = ['value' => $term->slug, 'name' => $term->name];
        }

        return $options;
    }

    return $field['options'];
}

// Output an input field
function afzfp_field($field, $classes, $tab_id, $user_id){
    ?>

	<div class="afzfp-field<?php echo esc_attr($classes); ?>" id="afzfp-field-<?php echo esc_attr($field['id']); ?>">

    <?php

    // the reserved meta ids
    $reserved_ids = apply_filters(
        'afzfp_reserved_ids',
        [
            'user_email',
            'user_url',
        ]
    );

    // if the current field id is in the reserved list
    if(in_array($field['id'], $reserved_ids)){
        $userdata = get_userdata($user_id);
        $current_field_value = $userdata->{$field['id']};

    // not a reserved id, but is a taxonomy */
    }elseif (isset($field['taxonomy'])) {
        $terms = wp_get_object_terms($user_id, $field['taxonomy']);
        $current_field_value = [];
        foreach ($terms as $term) {
            if ($field['type'] == 'checkboxes' || $field['type'] == 'select multiple') {
                $current_field_value[] = $term->slug;
            } else {
                $current_field_value = $term->slug;
            }
        }

    // not a reserved id - treat normally
    }else{
        // get the current value
        $current_field_value = get_user_meta(get_current_user_id(), $field['id'], true);
    }

    // Output the input label
    ?>
		<label for="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]"><?php echo esc_html($field['label']); ?></label>
			<?php

            // Switch to alter the output depending on type
            switch ($field['type']) {

                /* if this is a wysiwyg setting */
                case 'wysiwyg':
                    /* set some settings args for the editor */
                    $editor_settings = [
                        'textarea_rows' => apply_filters('afzfp_wysiwyg_textarea_rows', '5', $field['id']),
                        'media_buttons' => apply_filters('afzfp_wysiwyg_media_buttons', false, $field['id']),
                    ];

                    /* build field name. */
                    $wysiwyg_name = $field['id'];

                    /* display the wysiwyg editor */
                    wp_editor(
                        $current_field_value, // default content.
                        $wysiwyg_name, // id to give the editor element.
                        $editor_settings // edit settings from above.
                    );

                    break;

                /* if this should be rendered as a select input */
                case 'select':
                    ?>
					<select name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>">

					<?php
                    $options = afzfp_field_get_options($field);

                    /* loop through each option */
                    foreach ($options as $option) {
                        ?>
						<option value="<?php echo esc_attr($option['value']); ?>" <?php selected($current_field_value, $option['value']); ?>><?php echo esc_html($option['name']); ?></option>
						<?php
                    }
                    ?>
					</select>
					<?php
                    break;

                /* if this should be rendered as a select input */
                case 'select multiple':
                    ?>
					<select multiple name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>][]" id="<?php echo esc_attr($field['id']); ?>">
					<option>-</option>
					<?php
                    $options = afzfp_field_get_options($field);

                    /* loop through each option */
                    foreach ($options as $option) {
                        ?>
						<option value="<?php echo esc_attr($option['value']); ?>" <?php selected(true, in_array($option['value'], $current_field_value)); ?>><?php echo esc_html($option['name']); ?></option>
						<?php
                    }
                    ?>
					</select>
					<?php

                    break;

                /* if this should be rendered as a set of radio buttons */
                case 'radio':
                    $options = afzfp_field_get_options($field);

                    /* loop through each option */
                    foreach ($options as $option) {
                        ?>
						<div class="radio-wrapper"><label><input type="radio" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" value="<?php echo esc_attr($option['value']); ?>"  <?php checked($current_field_value, $option['value']); ?>> <?php echo esc_html($option['name']); ?></label></div>
						<?php
                    }
                    ?>
					<?php

                    break;

                /* if the type is set to a textarea input */
                case 'textarea':
                    ?>

					<textarea name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" rows="<?php echo absint(apply_filters('afzfp_textarea_rows', '5', $field['id'])); ?>" cols="50" id="<?php echo esc_attr($field['id']); ?>" class="regular-text"><?php echo esc_textarea($current_field_value); ?></textarea>

					<?php

                    /* break out of the switch statement */
                    break;

                /* if the type is set to a checkbox */
                case 'checkbox':
                    ?>
					<input type="hidden" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>" value="0" <?php checked($current_field_value, '0'); ?> />
					<input type="checkbox" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>" value="1" <?php checked($current_field_value, '1'); ?> />
					<?php

                    /* break out of the switch statement */
                    break;

                /* if this should be rendered as a set of radio buttons */
                case 'checkboxes':
                    ?>
					<input type="hidden" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>][]" value="-" />
					<?php
                    $options = afzfp_field_get_options($field);

                    /* loop through each option */
                    foreach ($options as $option) {
                        ?>
						<div class="checkbox-wrapper"><label><input type="checkbox" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>][]" value="<?php echo esc_attr($option['value']); ?>" <?php checked(true, in_array($option['value'], $current_field_value)); ?>> <?php echo esc_html($option['name']); ?></label></div>
						<?php
                    }
                    ?>
					<?php

                    break;

                /* if the type is set to an email input */
                case 'email':
                    ?>
					<input type="email" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>" class="regular-text" value="<?php echo esc_attr($current_field_value); ?>" />
					<?php
                    /* break out of the switch statement */
                    break;

                /* if the type is set to a password input */
                case 'password':
                    ?>
					<input type="password" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>" class="regular-text" value="" placeholder="New Password" />

					<input type="password" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>_check]" id="<?php echo esc_attr($field['id']); ?>_check" class="regular-text" value="" placeholder="Repeat New Password" />

					<?php

                    /* break out of the switch statement */
                    break;
                /* any other type of input - treat as text input */
                default:
                    ?>
					<input type="text" name="<?php echo esc_attr($tab_id); ?>[<?php echo esc_attr($field['id']); ?>]" id="<?php echo esc_attr($field['id']); ?>" class="regular-text" value="<?php echo esc_attr($current_field_value); ?>" />
					<?php

            }

    /* if we have a description lets output it */
    if ($field['desc']) {
        ?>
				<p class="description"><?php echo esc_html($field['desc']); ?></p>
				<?php
    } // end if have description

            ?>
	</div>

	<?php
}


// Function afzfp_tab_content_save
function afzfp_tab_content_save($tab, $user_id){
    $profile_page = new AFZFP_Profile();
    $profile_page_obj = $profile_page->get_profile_url(); ?>
	<div class="afzfp-save">
		<label class="afzfp_save_description"><?php echo esc_html__('Save this tabs updated fields.', 'afzfp'); ?></label>
		<input type="submit" class="afzfp_save" name="<?php echo esc_attr($tab['id']); ?>[afzfp_save]" value="Update <?php echo esc_attr($tab['label']); ?>" />
		<a class="btn" href="<?php echo esc_attr($profile_page_obj); ?>"><?php echo esc_html__('View Profile', 'afzfp'); ?></a>
	</div>
	<?php
}
add_action('afzfp_after_tab_fields', 'afzfp_tab_content_save', 10, 2);


// Retrieve or display list of pages as a dropdown (select list)
function afzfp_get_pages(){
    global $wpdb;

    $array = ['' => __('-- Select --', 'afzfp')];
    $pages = get_posts(
        [
            'post_type'   => 'page',
            'numberposts' => -1,
        ]
    );
    
    if($pages){
        foreach($pages as $page){
            $array[$page->ID] = esc_attr($page->post_title);
        }
    }

    return $array;
}

//Include a template file (Looks up first on the theme directory or load default)
function afzfp_load_template($file, $args = []){
    $child_theme_dir = get_stylesheet_directory().'/afzfp/';
    $parent_theme_dir = get_template_directory().'/afzfp/';
    $afzfp_dir = plugin_dir_path(__DIR__).'views/';

    if(file_exists($child_theme_dir.$file)){
        include $child_theme_dir.$file;
    }elseif (file_exists($parent_theme_dir.$file)){
        include $parent_theme_dir.$file;
    }else{
        include $afzfp_dir.$file;
    }
}

// Get the value of a settings field
function afzfp_get_option($option, $section, $default = ''){
    $options = get_option($section);

    if(isset($options[$option])){
        return $options[$option];
    }

    return $default;
}

// Encryption function
function afzfp_encryption($id){
    $secret_key = AUTH_KEY;
    $secret_iv = AUTH_SALT;

    $encrypt_method = 'AES-256-CBC';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    $encoded_id = base64_encode(openssl_encrypt($id, $encrypt_method, $key, 0, $iv));

    return $encoded_id;
}


// Decryption function
function afzfp_decryption($id){
    $secret_key = AUTH_KEY;
    $secret_iv = AUTH_SALT;

    $encrypt_method = 'AES-256-CBC';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    $decoded_id = openssl_decrypt(base64_decode($id), $encrypt_method, $key, 0, $iv);

    return $decoded_id;
}


// Get edit profile page url
function get_edit_profile_page(){
    $page_id = afzfp_get_option('profile_edit_page', 'afzfp_pages', false);

    if (!$page_id) {
        return false;
    }

    $url = get_permalink($page_id);

    return apply_filters('afzfp_profile_edit_url', $url, $page_id);
}

// Function that checks if a password matches the security criteria
function afzfp_check_if_password_secure($password){

    $is_secure = true;

    // Length of the password entered
    $pass_length = strlen($password);

    // Check length
    if($pass_length < 12){
        $is_secure = false;
    }

    /**
    * Match the password against a regex of complexity
    * at least 1 upper, 1 lower case letter and 1 number.
    */
    $pass_complexity = preg_match(apply_filters('afzfp_password_regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\d,.;:]).+$/'), $password);

    // Check whether the password passed the regex check of complexity
    if(false == $pass_complexity){
        $is_secure = false;
    }

    return $is_secure;

}
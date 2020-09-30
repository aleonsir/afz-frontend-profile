<?php

// Make sure the user is logged in
if(!is_user_logged_in()){
    echo "<div class='afzfp-login-alert'>";
    /* translators: %s: Login link */
    printf(esc_html__('This page is restricted. Please %s to view this page.', 'afzfp'), wp_loginout('', false));
    echo '</div>';
    return;
}

$user = wp_get_current_user();


// Get the tabs that have been added
$afzfp_tabs = apply_filters(
    'afzfp_tabs',
    []
);


do_action('afzfp_before_tabs', $afzfp_tabs, get_current_user_id());

?>

<div class="container-fluid px-0">
    <div class="row no-gutters">
        <div class="col-md-5 col-lg-4 col-xl-3 afzfp-sidebar">
        <ul>
            <li><a href="#avatar">Avatar</a></li>
        <?php
        foreach($afzfp_tabs as $afzfp_tab){

            echo '<li><a href="#'.$afzfp_tab['id'].'">'.$afzfp_tab['label'].'</a></li>';

        }
        ?>
        </ul>
        </div>


        <div class="col-md-7 col-lg-8 col-xl-9 afzfp-main">
 

        <!-- Edit profile picture -->
        <div class="box" id="avatar">

        <?php
        $myAv = new Afz_local_avatars();

        if(isset($_POST['_afz_local_avatar_nonce'])){
            do_action('edit_user_profile_update', $user->ID);
        }
        ?>
        <form method="post" enctype="multipart/form-data">
            <?php
            
            $myAv->edit_user_profile($user);
            ?>
            <input type="submit" value="Update">
        </form>

        </div>

        <?php

        // Loop through each item
        foreach ($afzfp_tabs as $afzfp_tab) {

        // Build the content class
        $content_class = '';

        // If we have a class provided
        if ('' != $afzfp_tab['content_class']){
            $content_class .= ' '.$afzfp_tab['content_class'];
        }

        do_action('afzfp_before_tab_content', $afzfp_tab['id'], get_current_user_id());
        
        ?>

        <div class="box tab-content<?php echo esc_attr($content_class); ?>" id="<?php echo esc_attr($afzfp_tab['id']); ?>">

            <form method="post" action="<?php echo esc_attr(get_edit_profile_page()).'#'.esc_attr($afzfp_tab['id']); ?>" class="afzfp-form-<?php echo esc_attr($afzfp_tab['id']); ?>">
                <?php
                    /* check if callback function exists */
                if (isset($afzfp_tab['callback']) && function_exists($afzfp_tab['callback'])) {
                    /* use custom callback function */
                    $afzfp_tab['callback']($afzfp_tab);
                } else {
                    /* use default callback function */
                    afzfp_default_tab_content($afzfp_tab);
                } ?>
                                        
                <?php
                    wp_nonce_field(
                        'afzfp_nonce_action',
                        'afzfp_nonce_name'
                    ); ?>
            </form>
        </div>
        <?php
        do_action('afzfp_after_tab_content', $afzfp_tab['id'], get_current_user_id());
        } // end tabs loop
        ?>
        </div>
    </div>
</div>
<?php
if(isset($_GET['success']) && 'yes' === $_GET['success']){
    echo "<div class='afzfp-success'>".esc_html(esc_attr__('User has been successfully registered.', 'afzfp')).'</div>';
}
if(isset($_GET['success']) && 'notactivated' === $_GET['success']){
    echo "<div class='afzfp-success'>".esc_html(esc_attr__('User has been successfully registered manually. Activation email has been sent to user successfully.', 'afzfp')).'</div>';
}
if(isset($_GET['success']) && 'createdmanually' === $_GET['success']){
    echo "<div class='afzfp-success'>".esc_html(esc_attr__('User has been successfully registered manually.', 'afzfp')).'</div>';
}
if(isset($_GET['success']) && 'notapproved' === $_GET['success']){
    echo "<div class='afzfp-success'>".esc_html(esc_attr__('User has been successfully registered manually.', 'afzfp')).'</div>';
}
if(isset($_GET['success']) && 'created' === $_GET['success']){
    echo "<div class='afzfp-success'>".esc_html(esc_attr__('Registration has done successfully', 'afzfp')).'</div>';
}

if(is_user_logged_in() === true){
?>
    <div id="afzfp-user-loggedin">
	    <p class="alert">
            <?php
            printf(__("You are currently logged in. You don't need another account. %s", 'afzfp'), wp_loginout('', false));
            ?>
        </p>
	</div>
<?php
}
?>
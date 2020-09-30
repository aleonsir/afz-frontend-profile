<div id="afz-frontend-profile">
	<div class="login">

	<?php

	// Filter and display messages
    $message = apply_filters('login_message', '');
    if(!empty($message)){
        echo esc_html($message)."\n";
	}
	
	// Check verification key
    if(isset($_GET['key'])){
        $user_id = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if($user_id){
            $code = get_user_meta($user_id, 'afzfp_user_activation_code', true);
            if($code == $_GET['key']){
                echo "<div class='afzfp-success'>".esc_html(esc_attr__('Congratulations! Your account has been verified.', 'afzfp')).'</div>';
                update_user_meta($user_id, 'afzfp_user_status', 'verified');
            }
        }
	}
	
    $AFZFP_Login = new AFZFP_Login();
	$AFZFP_Login->show_message();
    ?>

	<form method="post">
		<p>
			<label for="afzfp-user-login"><?php esc_attr_e('Username or Email', 'afzfp'); ?></label>
			<input type="text" name="mailoruser" id="afzfp-user-login" class="input" value="<?php echo esc_attr($AFZFP_Login->get_post_value('mailoruser')); ?>" size="20" />
		</p>
		<p>
			<label for="afzfp-user-pass"><?php esc_attr_e('Password', 'afzfp'); ?></label>
			<input type="password" name="pwd" id="afzfp-user-pass" class="input">
		</p>
		<?php
		$recaptcha = afzfp_get_option('enable_captcha_login', 'afzfp_general');
		if('on' == $recaptcha){
			?>
			<p>
				<?php AFZFP_Captcha_Recaptcha::display_captcha(); ?>
			</p>
		<?php } ?>
		<p class="forgetmenot">
			<input name="rememberme" type="checkbox" id="afzfp-remember" value="forever" />
			<label for="afzfp-remember"><?php esc_attr_e('Remember Me', 'afzfp'); ?></label>
		</p>
		<p class="submit">
			<input type="hidden" name="redirect_to" value="<?php echo esc_html(wp_get_referer()); ?>" />
			<input type="hidden" name="afzfp_login" value="true" />
			<input type="hidden" name="action" value="login" />
			<?php wp_nonce_field('afzfp_login_action', 'nonce_field_login'); ?>
			<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Log In', 'afzfp'); ?>" />
		</p>
	</form>

	<?php
    $lostpass = $AFZFP_Login->lost_password_links();
	echo wp_kses(
		$lostpass,
		[
			'a' => [
				'href'  => [],
				'title' => [],
				'id'    => [],
				'class' => [],
			],
		]
	);
	?>

	</div>
</div>
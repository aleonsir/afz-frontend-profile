<div id="afzfp-reset-pass-form">

	<?php
	$AFZFP_Login = new AFZFP_Login();
	$AFZFP_Login->show_message();
	?>

	<form method="post" action="">
		<label for="afzfp-user_login"><?php esc_attr_e('Username or E-mail:', 'afzfp'); ?></label>
		<input type="text" name="login">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr('redirect_to'); ?>">
		<input type="hidden" name="afzfp_reset_password_step" value="send-recovery-email">
		<?php wp_nonce_field('afzfp_reset_action','afzfp_reset_nonce_field'); ?>
		<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Get New Password', 'afzfp'); ?>" />
	</form>

</div>
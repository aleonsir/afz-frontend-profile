<div id="afzfp-reset-pass-form">

<?php
	$AFZFP_Login = new AFZFP_Login();
	$AFZFP_Login->show_message();
	?>

	<form method="post">

		<input type="hidden" name="key" value="<?php echo esc_attr($_GET['key']); ?>">
		<input type="hidden" name="login" value="<?php echo isset($_GET['login']) ? sanitize_user($_GET['login']) : ''; ?>" />
		<input type="hidden" name="afzfp_reset_password_step" value="update-user-password">

		<label for="afzfp-pass1"><?php esc_attr_e('New password', 'afzfp'); ?></label>
		<input autocomplete="off" name="pass1" id="afzfp-pass1" value="" type="password">
		
		<label for="afzfp-pass2"><?php esc_attr_e('Confirm new password', 'afzfp'); ?></label>
		<input autocomplete="off" name="pass2" id="afzfp-pass2" value="" type="password">

		<?php wp_nonce_field('afzfp_reset_action','afzfp_reset_nonce_field'); ?>
		
		<input type="submit" name="wp-submit" id="wp-submit" value="<?php esc_attr_e('Reset Password', 'afzfp'); ?>" />	
		
	</form>

</div>
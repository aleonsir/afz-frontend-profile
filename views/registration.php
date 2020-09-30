<?php
defined('ABSPATH') || exit;
?>

<section id="afz-frontend-profile">
	<div class="register">
	<?php
	
	if(isset($_GET['success']) && 'yes' == $_GET['success']) {
		echo "<div class='afzfp-success'>".esc_html('Registration has been successful!', 'afzfp').'</div>';
	}
	if(isset($_GET['success']) && 'notactivated' == $_GET['success']) {
		echo "<div class='afzfp-success'>".esc_html(esc_attr__('Registration has been successful! Please activate your account from e-mail.', 'afzfp')).'</div>';
	}
	if(isset($_GET['success']) && 'notapproved' == $_GET['success']) {
		echo "<div class='afzfp-success'>".esc_html(esc_attr__('Registration has been successful!. Please wait for admin approval.', 'afzfp')).'</div>';
	}

	$register_page = afzfp_get_option('register_page', 'afzfp_pages');
	$action_url = get_permalink($register_page);
	
	$AFZFP_Registration = new AFZFP_Registration();
	$AFZFP_Registration->show_message();

	?>

		<form name="afzfp_registration_form" class="afzfp-registration-form" id="afzfp_registration_form" action="<?php echo esc_html($action_url); ?>" method="post">
			<ul>
				<li class="afzfp-form-field afzfp-default-first-name">
					<label for="afzfp_reg_fname"><?php esc_attr_e('First Name', 'afzfp'); ?>
					</label>
					<input type="text" name="afzfp_reg_fname" id="afzfp-user_fname" class="input" value="<?php echo esc_attr($AFZFP_Registration->get_post_value('afzfp_reg_fname')); ?>"  />
				</li>
				<li class="afzfp-form-field afzfp-default-last-name">
					<label for="afzfp_reg_lname"><?php esc_attr_e('Last Name', 'afzfp'); ?>
					</label>
					<input type="text" name="afzfp_reg_lname" id="afzfp-user_lname" class="input" value="<?php echo esc_attr($AFZFP_Registration->get_post_value('afzfp_reg_lname')); ?>"  />
				</li>
				<li class="afzfp-form-field afzfp-default-email">
					<label for="afzfp_reg_email"><?php esc_attr_e('Email', 'afzfp'); ?>
						<span class="afzfp-required">*</span>
					</label>
					<input type="Email" name="afzfp_reg_email" id="afzfp-user_email" class="input" value="<?php echo esc_attr($AFZFP_Registration->get_post_value('afzfp_reg_email')); ?>">
				</li>
				<li class="afzfp-form-field afzfp-default-username">
					<label for="afzfp_reg_uname"><?php esc_attr_e('Username', 'afzfp'); ?>
						<span class="afzfp-required">*</span>
					</label>
					<input type="text" name="afzfp_reg_uname" id="afzfp-user_login" class="input" value="<?php echo esc_attr($AFZFP_Registration->get_post_value('afzfp_reg_uname')); ?>" />
				</li>

				<?php
				if(strlen($args['role_fixed'])>0){
				?>
				<input type="hidden" name="afzfp_reg_role" value="<?php echo esc_attr($args['role_fixed']); ?>" />
				<?php
				}elseif(strlen($args['role_selector'])>0){
					?>
					<li class="afzfp-form-field afzfp-field-role">
						<label for="afzfp_reg_role"><?php esc_attr_e('Choose your role', 'afzfp'); ?></label>
						<select name="afzfp_reg_role">
							<?php
							$roles = explode(',', $args['role_selector']);
							foreach($roles as $role){
								echo '<option value="'.esc_attr($role).'">'.ucfirst($role).'</option>';
							}
							?>
						</select>
					</li>
				<?php
				}
				?>

				<li class="afzfp-form-field afzfp-default-password">
					<label for="pwd1"><?php esc_attr_e('Password', 'afzfp'); ?>
						<span class="afzfp-required">*</span>
					</label>
					<input type="password" name="pwd1" id="afzfp-user_pass1" class="input" value=""  />
				</li>
				<li class="afzfp-form-field afzfp-default-confirm-password">
					<label for="pwd2"><?php esc_attr_e('Confirm Password', 'afzfp'); ?>
						<span class="afzfp-required">*</span>
					</label>
						<input type="password" name="pwd2" id="afzfp-user_pass2" class="input" value=""  />
				</li>
				<li class="afzfp-form-field afzfp-default-user-website">
					<label for="afzfp-description"><?php esc_attr_e('Website', 'afzfp'); ?>
					</label>
					<input type="text" name="afzfp-website" id="afzfp-user_website" class="input" value="<?php echo esc_attr($AFZFP_Registration->get_post_value('afzfp-website')); ?>"  />
				</li>
				
				<li class="afzfp-form-field afzfp-default-user-bio">
					<label for="afzfp-description"><?php esc_attr_e('Biographical Info', 'afzfp'); ?>
					</label>
					<textarea rows="5" name="afzfp-description" maxlength="" class="default_field_description" id="description"><?php echo esc_textarea($AFZFP_Registration->get_post_value('afzfp-description')); ?></textarea>
				</li>
				<li>
					<?php $recaptcha = afzfp_get_option('enable_captcha_registration', 'afzfp_general'); ?>
					<?php if ('on' == $recaptcha) { ?>
						<div class="afzfp-fields">
							<?php AFZFP_Captcha_Recaptcha::display_captcha(); ?>
						</div>
					<?php } ?>
				</li>
				<li class="afzfp-submit">
					<?php wp_nonce_field('afzfp_registration_action', 'nonce_field_register'); ?>
					<input type="submit" value="<?php esc_attr_e('Register', 'afzfp'); ?>" />
				</li>
				<?php do_action('afzfp_reg_form_bottom'); ?>
			</ul>
		</form>

	</div>
</section>
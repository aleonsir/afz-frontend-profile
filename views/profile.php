<?php
defined('ABSPATH') || exit;

if(!is_user_logged_in()){
    wp_redirect('/login');
    exit;
}

$user = wp_get_current_user();
?>
<style>
.no-gutters{
    --bs-gutter-x: 0;
}
.afzfp-sidebar{
    background-color: #172a74;
    padding: 2rem;
}
.afzfp-sidebar ul{
    list-style: none;
}
.afzfp-sidebar ul li,
.afzfp-sidebar ul li a{
    color: #fff;
}
.afzfp-main{
    padding: 2rem;
}
</style>

<div class="container-fluid px-0">
    <div class="row no-gutters">
        
        <div class="col-md-5 col-lg-4 col-xl-3 afzfp-sidebar">
            <ul>
			<?php do_action( 'afz_my_profile_sidebar' ); ?>
            <li>Mis productos</li>
            <li>Mis pagos</li>
            <li><a href="<?php echo wp_logout_url(); ?>">LOGOUT</a></li>
            </ul>
        </div>
        

        <div class="col-md-7 col-lg-8 col-xl-9 afzfp-main">

        <?php
        echo get_avatar($user->ID);
            if ('' != $user->display_name) {
                echo '<h5>'.esc_html($user->display_name).'</h5>';
            }
            ?>
			<p><strong><?php esc_attr_e('Email', 'afzfp'); ?>: </strong><?php echo esc_html($user->user_email); ?></p>
			<?php if ('' != $user->user_url) { ?>
			<p><strong><?php esc_attr_e('Website', 'afzfp'); ?>: </strong><?php echo '<a href='.esc_html($user->user_url).'>'.esc_html($user->user_url).'</a>'; ?></p>
			<?php } if ('' != $user->description) { ?>
			<div class="afzfp_user_bio">
				<p>
					<strong><?php esc_attr_e('User Bio', 'afzfp'); ?> : </strong>
					<?php echo esc_html($user->description); ?>
				</p>

			</div>
			<?php } ?>
			<a class="btn" href="<?php echo esc_html(get_edit_profile_page()); ?>"><?php esc_attr_e('Edit Profile', 'afzfp'); ?></a>
			
        </div>

    </div>  

</div>
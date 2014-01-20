<?php
/*
 * Plugin Name: Password Protected Widgets
 * Plugin URI: trepmal.com
 * Description: Add password protection to widgets
 * Version:
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * TextDomain: ppw
 * DomainPath:
 * Network:
 */


/**
 * Whether widget requires password and correct password has been provided.
 *
 * @param array $instance Widget instance
 * @return bool false if a password is not required or the correct password cookie is present, true otherwise.
 */
function widget_password_required( $instance ) {

	if ( empty( $instance['password'] ) )
		return false;

	if ( ! isset( $_COOKIE['wp-postpass_' . COOKIEHASH] ) )
		return true;

	require_once ABSPATH . 'wp-includes/class-phpass.php';
	$hasher = new PasswordHash( 8, true );

	$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
	if ( 0 !== strpos( $hash, '$P$B' ) )
		return true;

	return ! $hasher->CheckPassword( $instance['password'], $hash );
}

/**
 * Retrieve protected widget password form content.
 *
 * @since 1.0.0
 * @uses apply_filters() Calls 'the_password_form' filter on output.
 * @param int $widget_id Widget ID
 * @return string HTML content for password form for password protected post.
 */
function ppw_get_the_password_form( $widget_id ) {
	$label = 'pwbox-' . ( empty($widget_id) ? rand() : $widget_id );
	$output = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" class="post-password-form" method="post">
	<!--<p>' . __( 'This content is password protected. To view it please enter your password below:' ) . '</p>-->
	<p><label for="' . $label . '">' . __( 'Password:' ) . ' <input name="post_password" id="' . $label . '" type="password" size="20" /></label> <input type="submit" name="Submit" value="' . esc_attr__( 'Submit' ) . '" /></p>
	</form>
	';
	return apply_filters( 'the_password_form', $output );
}


// add password field
add_action( 'in_widget_form', 'ppw_in_widget_form', 10, 3 );
function ppw_in_widget_form( $widget, $return, $instance ) {
	$instance = wp_parse_args( $instance, array(
		'password' => '',
	) );
	?><p style="clear:both;">
		<label><?php _e( 'Password', 'ppw' ); ?> <input type="text" name="<?php echo $widget->get_field_name('password'); ?>" value="<?php echo esc_attr( $instance['password'] ); ?>" /></label>
	</p><?php
	$return = null;
}

// save it
add_filter('widget_update_callback', 'ppw_widget_update_callback', 10, 4 );
function ppw_widget_update_callback( $instance, $new_instance, $old_instance, $this ) {
	$instance['password'] = strip_tags( $new_instance['password'] );
	return $instance;
}

// password check
add_filter('widget_display_callback', 'ppw_widget_display_callback', 10, 3 );
function ppw_widget_display_callback( $instance, $widget, $args ) {
	if ( widget_password_required( $instance ) ) {
		echo $args['before_widget'];
		echo ppw_get_the_password_form( $args['widget_id'] );
		echo $args['after_widget'];

		return false;
	}
	return $instance;
}

//
/*
 * Prerequisites:
 * Custom tabs snippet from this repository (extended-custom-tabs) for creating a custom tab
 * Enable Email editing on a Profile Form inside a custom tab
 */

add_action("um_submit_form_profile","um_012722_email_validation", 1 );
function um_012722_email_validation( $post_form ){
   
    if( isset( $post_form['user_email'] ) && ! empty( $post_form['user_email'] ) ){
        $user = wp_get_current_user();

        if( email_exists( $post_form['user_email'] ) && $post_form['user_email'] !== $user->user_email ){
            UM()->form()->add_error( 'user_email', __( 'Your email address is already taken', 'ultimate-member' ) );
        }
    }
}

add_filter( 'um_user_profile_restricted_edit_fields', 'um_012722_enable_email_address_editing');
function um_012722_enable_email_address_editing( $fields ){

    unset( $fields[0] );
    return $fields;
}

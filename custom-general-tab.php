/*
 * Prerequisite plugins:
 * https://github.com/ultimatemember/Extended/tree/d4613b3c5896fb75855a31bafd7f1e9dcbe89ea7/um-profile-photo
 *
 * Using this piece of code to change the 'general' tab content to show the predefined field for Profile Photo 
 */

add_filter('um_account_page_default_tabs_hook', 'profile_picture_general_tab', 100 );
function profile_picture_general_tab( $tabs ) {
	$tabs[100]['general']['title'] = 'Profile Picture';

	return $tabs;
}

// General tab is unset
add_filter( 'um_account_tab_general_fields', 'um_011921_add_profile_photo_uploader', 10, 2 );
function um_011921_add_profile_photo_uploader( $args, $shortcode_args ) {

    $args = 'register_profile_photo';
    return $args;
}

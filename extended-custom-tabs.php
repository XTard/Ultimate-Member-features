/*
 * Original custom tabs example
 * https://www.champ.ninja/2020/05/add-a-custom-account-tab-with-profile-form-fields/
 */

class UM_Custom_Account_Tabs{

	private $tabs = array();

	function __construct( ){
         
		add_filter( 'um_account_page_default_tabs_hook', array( $this, 'account_tabs' ), 999 );
        add_action( 'template_redirect', array( UM()->form(), "form_init" ) );
        add_action( 'wp_footer', function(){
            echo '<script>
                jQuery(document).on("ready",function(){
                    wp.hooks.addAction("um_account_active_tab_inited", "um", function() {

                            jQuery(document).off("um_fields_change");
                          
                            jQuery(".um-field[data-key]:visible").each(function() {
                                var $wrap_dom = jQuery(this);
                                var me = um_get_field_element($wrap_dom);
                                 if (typeof me.trigger !== "undefined") {
                                    um_apply_conditions(me, false);
                                }
                            });
                      
                    });
                });
                 </script>';
        });

      
		
	}

    /**
     *  Add tab
    */
	public function add_tab( $args ){
        $this->tabs[ ] = $args;
	
		add_filter( "um_account_content_hook_{$args['tab_id']}",  function() use( $args ) {
			
            
            $content = '';

            if( isset( $args["before_content"] ) ){
               $content .= $args["before_content"];
            }

            if( isset( $args['form_id'] ) ){
            	$content .= $this->render_form( $args['form_id'] );
            }

            if( !empty( $args['display_form_by_role'] ) ){
            	$content .= $this->render_form( $args['display_form_by_role'][UM()->user()->get_role()] );
            }

            if( isset( $args['shortcode'] ) ){
            	$content .=  do_shortcode( $args['shortcode'] );
            }

            if( isset( $args["after_content"] ) ){
               $content .= $args["after_content"];
            }
			
            if( isset( $args["privacy"] ) && !(in_array(UM()->user()->get_role(), $args["privacy"]) ||  UM()->user()->get_role() == "administrator" || in_array('everyone', $args["privacy"]) ) )
				return false;

            return $content;
            
		}, 10, 2 );
    }

    /** 
     * Register Tabs in Account form
     */
	public function account_tabs( $tabs ){
        
        //*** */ If you want to make the tab the first tab, use the following code:
        // set default current tab
		
		// Breaks Profile Picture upload (via 'general' tab) if 'if' is removed...?
        if( ! isset( $_POST ) ){
            UM()->account()->current_tab = null;
        }

        // *** If you want to disable the general tab, use the following code:
        // disable main account tab
        // unset( $tabs[100]['general'] );
    
        foreach(  $this->tabs as $tab ){
            // if( isset( $args["privacy"] ) && !(in_array(UM()->user()->get_role(), $args["privacy"]) ||  UM()->user()->get_role() == "administrator" || in_array('everyone', $args["privacy"]) ) )
				// continue;			
			$tabs[ $tab["position"] ][ $tab["tab_id"] ] = array(
					'privacy'       => $tab["privacy"],
					'icon'          => $tab["icon"],
					'anchor'        => $tab["anchor"],
					'anchor_target' => $tab["anchor_target"],
					'title'         => $tab["title"],
					'submit_title'  => $tab["button_title"],
					'custom'        => true,
					'show_button'   => $tab['show_button'],
			);
        }
        
      

		return $tabs;

    }
   
    /** 
     * Render the form/shortcode
     */
	public function render_form( $form_id = null ){
        
        if( ! $form_id ) return;
        $post_id = null;
        if( um_is_core_page("account") ){
            UM()->fields()->editing = true;
            UM()->fields()->set_mode =  "profile";
            $post_id = UM()->config()->permalinks['account'];
            unset( UM()->config()->permalinks['account'] );
        }

        ob_start();
        $args["form_id"] = $form_id; 
        $args["mode"] = "profile";
        $args["custom_fields"] = UM()->query()->get_attr( 'custom_fields', $form_id );
        $args["core"] = "profile";
        $args["user_id"] = get_current_user_id();
       
        extract( $args );
            echo "<div class='um um-profile'  style='margin:0px;padding:0px;'>";
			echo "<div class='um-profile-body' style='margin:0px;padding:0px;'>";
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_main_{$mode}_fields
			 * @description Some actions before login form fields
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Login form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_{$mode}_fields', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_{$mode}_fields', 'my_before_fields', 10, 1 );
			 * function my_before_form( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_main_{$mode}_fields", $args );

		    echo "<input type='hidden' name='mode' value='{$mode}'/>";
		    echo "<input type='hidden' name='user_id' value='{$user_id}'/>";
		    echo "<input type='hidden' name='form_id' value='{$form_id}'/>";
            echo "<input type='hidden' name='is_signup' value='true'/>";
            echo "<input type='hidden' name='profile_nonce' id='profile_nonce' value='".esc_attr( wp_create_nonce( 'um-profile-nonce' . $args["user_id"] ) )."' />";
            echo "</div>";
            echo "</div>";

        UM()->form()->form_suffix = "";
        UM()->config()->permalinks['account'] = $post_id;


        $template = ob_get_contents();
        ob_end_clean();

		return $template;
	}

}


/*
 * Fixing File uploads in custom tabs
 * Tutorial: https://www.champ.ninja/2020/05/add-a-custom-account-tab-with-profile-form-fields/
 * Segment: https://gist.github.com/champsupertramp/7e8c5b3407af8d4ed2f96577fb28f37c
 * Note: updated version of the code segment
 */

add_filter("um_user_pre_updating_files_array",
	function($files ){
        
		$user_id = get_current_user_id();
		if( empty( $user_id ) ) return $files;

		$new_files = array();
		$old_files = array();

		$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id, true );
		
		foreach ( $files as $key => $filename ) {

		
			//move temporary file from temp directory to the correct user directory
			$temp_file_path = UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . $filename;
			
			if ( file_exists( $temp_file_path ) ) {
				$extra_hash = hash( 'crc32b', current_time('timestamp') );

				if ( strpos( $filename , 'stream_photo_' ) !== false ) {
					$new_filename = str_replace("stream_photo_","stream_photo_{$extra_hash}_", $filename );
				} else {
					$new_filename = str_replace("file_","file_{$extra_hash}_", $filename );
				}

				$submitted = get_user_meta( $user_id, 'submitted', true );
				$submitted = ! empty( $submitted ) ? $submitted : array();

				$submitted[ $key ] = $new_filename;
				update_user_meta( $user_id, 'submitted', $submitted );

				if ( $move_only ) {

					$file = $user_basedir . DIRECTORY_SEPARATOR . $filename;
					if ( rename( $temp_file_path, $file ) ) {
						$new_files[ $key ] = $filename;
					}

				} else {

					$file = $user_basedir . DIRECTORY_SEPARATOR . $new_filename;

					if ( rename( $temp_file_path, $file ) ) {
						$new_files[ $key ] = $new_filename;
						$old_files[ $key ] = get_user_meta( $user_id, $key, true );

						update_user_meta( $user_id, $key, $new_filename );

						$file_info = get_transient( "um_{$filename}" );
						if ( ! $file_info ) {
							$file_info = get_user_meta( $user_id, "{$key}_metadata_temp", true );
							delete_user_meta( $user_id, "{$key}_metadata_temp" );
						}

						if ( $file_info ) {
							update_user_meta( $user_id, "{$key}_metadata", $file_info );
							delete_transient( "um_{$filename}" );
						}
					}
				}
			}
		}
});
add_action( 'wp_ajax_um_remove_file', 'um_042821_ajax_remove_file' ,1 );
add_action( 'wp_ajax_nopriv_um_remove_file', 'um_042821_ajax_remove_file', 1 );
function um_042821_ajax_remove_file(){
	UM()->check_ajax_nonce();

			if ( empty( $_POST['src'] ) ) {
				wp_send_json_error( __( 'Wrong path', 'ultimate-member' ) );
			}

			if ( empty( $_POST['mode'] ) ) {
				wp_send_json_error( __( 'Wrong mode', 'ultimate-member' ) );
			}

			$src = $_POST['src'];
			if ( strstr( $src, '?' ) ) {
				$splitted = explode( '?', $src );
				$src = $splitted[0];
			}

			$mode = sanitize_key( $_POST['mode'] );

			if ( $mode == 'profile'){
				$user_id = absint( $_POST['user_id'] );

				if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					wp_send_json_error( __( 'You have no permission to edit this user', 'ultimate-member' ) );
				}

				$is_temp = um_is_temp_upload( $src );
				if ( ! $is_temp ) {
					if ( ! empty( $_POST['filename'] ) && file_exists( UM()->uploader()->get_upload_user_base_dir( $user_id ) . DIRECTORY_SEPARATOR . $_POST['filename'] ) ) {
						wp_send_json_success();
					}
				}

			}

            $files = new um\core\Files();

            $raw_file = explode("/",$src);
            $uid = $raw_file[6];
            $field_key = $raw_file[5];
            $file = get_user_meta( $uid, $field_key, true );
            $file_path = UM()->uploader()->get_upload_base_dir() . $uid . DIRECTORY_SEPARATOR . $file;
			if( file_exists( $file_path ) ){
                unlink( $file_path );
                delete_user_meta( $uid, $field_key );
                delete_user_meta( $uid, $field_key."_metadata" );
                delete_transient( "um_{$file}" );
                wp_send_json_success();
            }

			if ($files->delete_file( $src ) ) {
				wp_send_json_success();
			} else {
                wp_send_json_error( __( 'You have no permission to delete this file. ', 'ultimate-member' ) );
   }
}

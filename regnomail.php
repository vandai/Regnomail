<?php
/*
* Plugin Name: Regnomail
* Plugin URI: http://www.vnetware.com/
* Description: Simple plugin to disable sending email confirmation during user registration and customize registration form, like display password form, first & last name, and change login / registration logo image.
* Version: 1.0
* Author: Miaz Akemapa
* Author URI: http://www.vnetware.com/
* Text Domain: regnomail
*/

Class RegNoMail {
    public $RGM_VERSION;
    public $rgm_options;

    public function __construct() {
        $this->RGM_VERSION = "1.0";
        $this->rgm_options = get_option('rgm_options');
        
        add_action('admin_menu', array($this,'add_rgm_menu'));
        add_action('register_form',array($this,"rgm_registration_form"));
        add_filter('registration_errors', array($this,'rgm_registration_error'), 10, 3);
        add_action('user_register', array($this,'rgm_user_register'));
        
        add_action( 'login_enqueue_scripts', array($this,'rgm_login_logo'));
        add_filter( 'login_headerurl', array($this,'rgm_login_logo_url'));
        add_filter( 'login_headertitle', array($this,'rgm_login_logo_url_title'));
        
        add_action('admin_init', array($this,'rgm_options_init'));
        
        //Translation
        add_action('init', array($this,'rgm_lang_init'));
    }
    
    public function get_rgm_options(){
        return $this->rgm_options;
    }
    public function get_rgm_version(){
        return $this->RGM_VERSION;
    }
    
    public function add_rgm_menu(){
        add_options_page("Regnomail Settings", "Regnomail Settings", 'manage_options', "regnomail", array($this,"rgm_settings"));
    }
    
    function rgm_lang_init(){
        load_plugin_textdomain('regnomail', false, dirname(plugin_basename(__FILE__))."/languages/");
    }
    
    function rgm_registration_form() {
        $rgm_password = isset($this->rgm_options['show_password']) ? $this->rgm_options['show_password'] : "";
        $rgm_fullname = isset($this->rgm_options['show_full_name']) ? $this->rgm_options['show_full_name'] : "";
        $rgm_logo = isset($this->rgm_options['rgm_login_logo']) ? $this->rgm_options['rgm_login_logo'] : "";
                
        $first_name = isset($_POST['first_name']) ? $_POST['first_name']: '';
        $last_name = isset($_POST['last_name']) ? $_POST['last_name']: '';
        $password = isset($_POST['password']) ? $_POST['password']: '';
        $repeat_password = isset($_POST['repeat_password']) ? $_POST['repeat_password']: '';
        //$are_you_human = isset($_POST['are_you_human']) ? $_POST['are_you_human']: '';
        
        ?>
        <?php if($rgm_password == "1"){ 
            add_filter( 'gettext', array($this,'rgm_password_email_text')); 
        ?>
        <p>
		<label for="password"><?php _e('Password','regnomail'); ?><br/>
		<input id="password" class="input" type="password" tabindex="10" size="25" value="" name="password" />
		</label>
	</p>
	<p>
		<label for="repeat_password"><?php _e('Repeat Password','regnomail'); ?><br/>
		<input id="repeat_password" class="input" type="password" tabindex="11" size="25" value="" name="repeat_password" />
		</label>
	</p>
        <?php }
        if($rgm_fullname != "") { ?>
        <p>
            <label for="first_name"><?php _e('First Name', 'regnomail')?><br />
            <input name="first_name" type="text" size="39" tabindex="12" id="first_name" value="<?php echo esc_attr(stripslashes($first_name)); ?>" /></label>
        </p>
        <p>
            <label for="last_name"><?php _e('Last Name', 'regnomail')?><br />
            <input name="last_name" type="text" size="39" tabindex="13" id="last_name" value="<?php echo esc_attr(stripslashes($last_name)); ?>" /></label>
        </p>
        <?php }
        // END FORM
    }
    
    function rgm_registration_error($errors, $sanitized_user_login, $user_email){
        $rgm_password = isset($this->rgm_options['show_password']) ? $this->rgm_options['show_password'] : "";
        $rgm_fullname = isset($this->rgm_options['show_full_name']) ? $this->rgm_options['show_full_name'] : "";
        
        if($rgm_fullname != "" AND $rgm_fullname == "1"){
            if ( empty( $_POST['first_name'] ) )
                $errors->add( 'first_name_error', __('<strong>ERROR</strong>: You must include a first name.','regnomail') );
            if ( empty( $_POST['last_name'] ) )
                $errors->add( 'last_name_error', __('<strong>ERROR</strong>: You must include a Last name.','regnomail') );
        }
        if($rgm_password != "" AND $rgm_password == "1"){
            if ( $_POST['password'] !== $_POST['repeat_password'] ) {
                    $errors->add( 'passwords_not_matched', __("<strong>ERROR</strong>: Passwords must match.",'regnomail') );
            }
            if ( strlen( $_POST['password'] ) < 8 ) {
                    $errors->add( 'password_too_short', __("<strong>ERROR</strong>: Passwords must be at least eight characters long",'regnomail') );
            }
        }
	/*if ( $_POST['are_you_human'] !== get_bloginfo( 'name' ) ) {
		$errors->add( 'not_human', __("<strong>ERROR</strong>: Sorry, that's not the correct answer!.",'vnetware') );
	}*/
        return $errors;
    }
    
    function rgm_user_register($user_id){
        $userdata = array();
        $rgm_password = isset($this->rgm_options['show_password']) ? $this->rgm_options['show_password'] : "";
        $rgm_fullname = isset($this->rgm_options['show_full_name']) ? $this->rgm_options['show_full_name'] : "";
        
	$userdata['ID'] = $user_id;
	if($rgm_password != "" AND $rgm_password == "1"){
            if ( $_POST['password'] !== '' ) {
                    $userdata['user_pass'] = $_POST['password'];
            }
            $new_user_id = wp_update_user( $userdata );
        }
        
        if($rgm_fullname != "" AND $rgm_fullname == "1"){
            if (isset($_POST['first_name']) AND isset($_POST['last_name']) ){
                update_user_meta($new_user_id, 'first_name', $_POST['first_name']);
                update_user_meta($new_user_id, 'last_name', $_POST['last_name']);
            }
        }
    }
    
    function rgm_password_email_text($text){
        if ( $text == 'A password will be e-mailed to you.' ) {
		$text = __('Password must be at least 8 characters long.','regnomail');
	}
	return $text;
    }
    
    function rgm_settings(){
        if(function_exists( 'wp_enqueue_media' )){
            wp_enqueue_media();
        }else{
            wp_enqueue_style('thickbox');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
        }
        
        echo '<div id="icon-options-general" class="icon32"><br /></div>  ';
        echo "<span><h2 style='padding-top:10px;'>" . __('Regnomail Settings','regnomail') . "</h2></span>  ";
        ?>
<script>
jQuery(document).ready(function($) {
   var custom_uploader;
   var input_target;
    $('#rgm_upload_button').unbind('click').click(function(e) {
        e.preventDefault();
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            library: { type: 'image' },
            multiple: false
        });
        custom_uploader.on('select', function() {
            var attachments = custom_uploader.state().get( 'selection' ).toJSON();
            img_thumb = attachments[0].url;
            $('#'+input_target).val(img_thumb); 
        });
        custom_uploader.on( 'close', function() {
            var attachments = custom_uploader.state().get( 'selection' ).toJSON();
            img_thumb = attachments[0].url;
            $('#rgm_login_logo').val(img_thumb); 
            $('#rgm_login_image').attr('src', img_thumb);
        } );
 
        custom_uploader.open();
    });
    
    $("#rgm_reset_default").click(function(){
        $(':input','#rgm_settings_form')
        .not(':button, :submit, :reset, :hidden')
        .val('')
        .removeAttr('checked')
        .removeAttr('selected');
        $("form#rgm_settings_form").submit();
    });
    
});
</script>
                    <form method="post" name="rgm_settings_form" id="rgm_settings_form" action="options.php">
                        <?php settings_fields('rgm_form_options'); ?>
                        <?php $options = $this->rgm_options;
                        ?>
                        <table class="form-table">
                            <tbody>
                                <tr valign="top"><th scope="row"><?php _e('Email Settings','regnomail');?></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php _e('Email Settings','regnomail');?></span></legend>
                                            <label for="disable_email_user">
                                            <input name="rgm_options[disable_email_user]" type="checkbox" id="disable_email_user" value="1" <?php if($options['disable_email_user'] == "1") echo "checked"; ?>>
                                            <?php _e('Disable the automatic welcome email sent to users','regnomail');?></label>
                                            <br />
                                            <label for="disable_email_admin">
                                            <input name="rgm_options[disable_email_admin]" type="checkbox" id="disable_email_admin" value="1" <?php if($options['disable_email_admin'] == "1") echo "checked"; ?>>
                                            <?php _e('Disable new user notification email to admin','regnomail');?></label>
                                            <br />
                                            <label for="disable_change_pwd">
                                            <input name="rgm_options[disable_change_pwd]" type="checkbox" id="disable_change_pwd" value="1" <?php if($options['disable_change_pwd'] == "1") echo "checked"; ?>>
                                            <?php _e('Disable user password change notification email to admin','regnomail');?></label>
                                            <br />
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e('Registration Form','regnomail');?></th>
                                    <td>
                                        <fieldset>
                                            <legend class="screen-reader-text"><span><?php _e('Registration Form','regnomail');?></span></legend>
                                            <label for="show_full_name">
                                            <input name="rgm_options[show_full_name]" type="checkbox" id="show_full_name" value="1" <?php if($options['show_full_name'] == "1") echo "checked"; ?>>
                                            <?php _e('Show First & Last Name form','regnomail');?></label>
                                            <br />
                                            <label for="show_password">
                                            <input name="rgm_options[show_password]" type="checkbox" id="show_password" value="1" <?php if($options['show_password'] == "1") echo "checked"; ?>>
                                            <?php _e('Show Password form','regnomail');?></label>
                                            <br />
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr valign="top"><th scope="row"><?php _e('Custom Login Logo','regnomail');?></th>
                                    <td><input type="text" id="rgm_login_logo" name="rgm_options[custom_login_logo]" value="<?php echo $options['custom_login_logo']; ?>" class="regular-text" />
                                    <input id="rgm_upload_button" type="button" class="button rgm_upload_button" value="<?php _e( 'Select Image', 'regnomail' ); ?>" />
                                    <span><img id="rgm_login_image" src="" class="floatright" /></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Save Changes','regnomail') ?>" />
                            <input type="button" id="rgm_reset_default" class="button-primary" value="<?php _e('Reset To Default','regnomail') ?>" />
                        </p>
                    </form>
<form id="form_rgm_reset" name="form_rgm_reset" action="" method="get">
    <input type="hidden" name="settings-updated" id="settings-updated" value="true" />
</form>
                
<?php
    }
    
    function rgm_login_logo(){
        $rgm_custom_logo = $this->rgm_options['custom_login_logo'];
        if($rgm_custom_logo != ""){
            $css = "
    <style type='text/css'>
    body.login div#login h1 a {
        background-image: url('".$rgm_custom_logo."');
        padding-bottom: 30px;
    }
    </style>";
            echo $css;
        }
    }
    
    function rgm_login_logo_url() {
        return get_bloginfo( 'url' );
    }
    

    function rgm_login_logo_url_title() {
        $title = get_bloginfo('description');
        return $title;
    }
    
    //S3 Rating Options Page
    function rgm_options_init(){
        register_setting( 'rgm_form_options', 'rgm_options', array($this,'rgm_options_validate') );
    }
    function rgm_options_validate($input) {
        $input['custom_login_logo'] = (sanitize_text_field($input['custom_login_logo']));
        
        return $input;
    }
}

$reg = new RegNoMail();
$rgm_options = $reg->get_rgm_options();

//Now disable all notification!
if(!function_exists('wp_new_user_notification')){
    function wp_new_user_notification($user_id, $plaintext_pass = '') {
	$user = get_userdata( $user_id );
        $rgm_options = get_option('rgm_options');
        
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:','regnomail'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s','regnomail'), $user->user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s','regnomail'), $user->user_email) . "\r\n";
        
        if(!isset($rgm_options['disable_email_admin']) OR $rgm_options['disable_email_admin'] != '1'){
            @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration','regnomail'), $blogname), $message);
        }

	if ( empty($plaintext_pass) )
		return;

	$message  = sprintf(__('Username: %s','regnomail'), $user->user_login) . "\r\n";
	$message .= sprintf(__('Password: %s','regnomail'), $plaintext_pass) . "\r\n";
	$message .= wp_login_url() . "\r\n";
        
        if(!isset($rgm_options['disable_email_user']) OR $rgm_options['disable_email_user'] != '1'){
            wp_mail($user->user_email, sprintf(__('[%s] Your username and password','regnomail'), $blogname), $message);
        }

    }
}
if(!function_exists( 'wp_password_change_notification')){
    if(isset($rgm_options['disable_change_pwd']) AND $rgm_options['disable_change_pwd'] == "1"){
        function wp_password_change_notification(){}
    }
}

?>
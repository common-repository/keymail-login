<?php
/*
Plugin Name: Keymail Login
Description: Keymail provides a secure, straightforward method for customers to log into your site without passwords. Users automatically receive a secure login link in an email they can manage with their chose of apps on the computer or mobile. 
Name: Keymail Login
Author: Keymail LLC
Version: 1.0
*/

require_once 'keymail-login_functions.php';

register_activation_hook(__FILE__,'kml_install');
register_sidebar_widget('Keymail Login', 'kml_widget');
register_widget_control('Keymail Login', 'kml_widget_control' );	

define('kml_PLUGIN_URL', plugins_url('',plugin_basename(__FILE__)).'/'); //PLUGIN DIRECTORY

/* INSTALL*/
function kml_install() {

	global $wpdb;
		$table = $wpdb->prefix.'log_via_email';
		$structure = "CREATE TABLE IF NOT EXISTS $table (
					  `id` INT(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					  `link` VARCHAR(255) NOT NULL,
					  `visit_link` int(11),
					  UNIQUE (`id`)
					  )";
		$wpdb->query($structure);
}


/* kml_email_form_submit */
function kml_email_form_submit(){
	global $wpdb;
	global $wp_rewrite;
	if ($_POST['email_form_submit']){
		$timestamp = time();
		
		if (!empty($_POST['destinition'])){
			$pos = strpos($_POST['destinition'], '?');
			$destinition = urlencode(substr($_POST['destinition'], $pos));
			unset($_POST['destinition']);
		}
		else{
			if (isset($_GET['user'])){
				$pos = strpos($_SERVER["REQUEST_URI"], '&');
				$url = substr_replace($_SERVER["REQUEST_URI"], '', $pos);
			}
			else
				$url = $_SERVER["REQUEST_URI"];
				
			$page_url = 'http://'.$_SERVER['SERVER_NAME'].$url;
			
			if (is_home()){
				$destinition = 'front';
			}
			else{
				$destinition = urlencode(str_replace(site_url().'/', '', $page_url));
			}
		}
		$site_name = get_bloginfo('name');
		$admin_email = get_bloginfo('admin_email');
		if (!empty($_POST['login_email'])){
			if (is_email($_POST['login_email']) ){
				$user_email = $_POST['login_email'];
					
				$sql = "SELECT `id` FROM `".$wpdb->prefix."users` WHERE `user_email`=%s";
				$result = $wpdb->get_results($wpdb->prepare($sql, $user_email));
				$user_id = $result[0]->id;
				if ( !$user_id ) {
					$user_password = wp_generate_password( 12, false );
					$user_name = $user_email;
					$user_id = wp_create_user( $user_name, $user_password, $user_email );
						
					$result = $wpdb->get_results( $wpdb->prepare("SELECT `user_pass` FROM `".$wpdb->prefix."users` WHERE `id`=%d", $user_id), ARRAY_A);
					$hash = md5($user_id. $timestamp. $result[0]['user_pass']);

					$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=$destinition";
					//$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=";
						
					$sql = "INSERT INTO `".$wpdb->prefix."log_via_email` (`link`, `visit_link`) VALUES (%s, %d)";
					$wpdb->query($wpdb->prepare($sql, strip_tags(stripslashes($login_url)), '0'));

					$message = 'A login link has been sent to ' . $_POST['login_email'];
						
					/* Send mail with link */
					$text_email = 'Login link to '. $site_name. '<br>' . $login_url;
					sendmail($user_email, $user_email, 'Login link', $text_email, $admin_email, $admin_email);

				} else {

					$result = $wpdb->get_results( $wpdb->prepare("SELECT `id`, `user_pass` FROM `".$wpdb->prefix."users` WHERE `user_email`=%s", $user_email), ARRAY_A);
					$user_id = $result[0]['id'];
						
					$hash = md5($user_id. $timestamp. $result[0]['user_pass']);
						
					$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=$destinition";
					//$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=";

					$sql = "INSERT INTO `".$wpdb->prefix."log_via_email` (`link`, `visit_link`) VALUES (%s, %d)";
					$wpdb->query($wpdb->prepare($sql, $login_url, '0'));


					$message = 'A login link has been sent to ' . $_POST['login_email'];
		
					/* Send mail with link */
					$text_email = 'Login link to '. $site_name. '<br>' . $login_url;
					sendmail($user_email, $user_email, 'Login link', $text_email, $admin_email, $admin_email );
					
				}
			}
			else $message = 'Invalid email address.';
				
			unset($_POST['email_form_submit']);
			unset($_POST['login_email']);
		}
		else $message = 'Email address field is required.';
	}
	else $message = '';

	return $message;
	echo $message;
}

/* Shortcode [login_via_email_form] */
function kml_login_form_shortcode() { 
	$current_user = wp_get_current_user();
	if (!is_user_logged_in()):
		echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="email_form">
					<label>Email address</label><br/>
					<p><input type="text" name="login_email"></p>';
		?>
		<ul>
		<script type="text/javascript">
			function link_submit(href){
				document.getElementById("destinition').value = href;
			}
		</script>
		<?php 
			$posts = get_posts('post_type=post');
			foreach($posts as $post ) :
		?>
			 <li><a href="<?php echo $post->guid; ?>" onclick="link_submit(this.href);return false;"><?php echo $post->post_title; ?></a></li>

			 <?php 	endforeach; 
			$pages = get_posts('post_type=page');
			foreach($pages as $page ) :
			?>
			 <li><a href="<?php echo $page->guid; ?>" onclick="link_submit(this.href);return false;"><?php echo $page->post_title; ?></a></li>
			 <?php endforeach; ?>
		</ul>
		<input type="hidden" name="destinition" id="destinition" value="">

		<?php 
		echo '<p><input type="submit" value="Send" name="email_form_submit"></p></form>';
		echo kml_email_form_submit();
	endif;
}
add_shortcode('kml_login_form', 'kml_login_form_shortcode');



/* Widget */
function kml_widget($args) {
	global $wpdb;
	$current_user = wp_get_current_user();
	if (!is_user_logged_in()):
		extract($args);                                 	 
		$title = get_option('kml_widget_title'); 	
		$menu_option = get_option('kml_widget_menu_option'); 
		echo $before_widget;                      			 
		echo $before_title;                            		 
		echo (empty($title)? 'Keymail Login' : $title);  
		echo $after_title;   	
		echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="email_form">
					<label>Email address</label><br/>
					<p><input type="text" name="login_email"></p>
					';
		if ($menu_option == 'on'):
	?>
		<ul>
		<script type="text/javascript">
			function link_submit(href){
				document.getElementById('destinition').value = href;
			}
		</script>
		<?php 
			$posts = get_posts('post_type=post');
			foreach($posts as $post ) :
		?>
			 <li><a href="<?php echo $post->guid; ?>" onclick="link_submit(this.href);return false;"><?php echo $post->post_title; ?></a></li>

		<?php endforeach; 
 
			$pages = get_posts('post_type=page');
			foreach($pages as $page ) :
		?>
			 <li><a href="<?php echo $page->guid; ?>" onclick="link_submit(this.href);return false;"><?php echo $page->post_title; ?></a></li>
			<?php endforeach; ?>
		
		</ul>
		<input type="hidden" name="destinition" id="destinition" value="">
	<?php
		endif;
		echo '<p><input type="submit" value="Send" name="email_form_submit"></p></form>';
		echo kml_email_form_submit();
		echo $after_widget;   
	endif;
}

/* Widget control */
function kml_widget_control() {
    if (!empty($_REQUEST['kml_widget_title'])) { 
        update_option('kml_widget_title', $_REQUEST['kml_widget_title']);      
    } 
	if (!empty($_REQUEST['kml_widget_menu_option'])) { 
        update_option('kml_widget_menu_option', $_REQUEST['kml_widget_menu_option']);   
		$checked = 'checked="checked"';		
    }
		
	echo 'Widget\'s title:<br>
		<input style="width:200px;" type="text" name="kml_widget_title" value="'.get_option('kml_widget_title').'" /><br />
		<p><input type="checkbox" name="kml_widget_menu_option"'. $checked .' />Display menu below Keymail Login widget</p>';
}
?>
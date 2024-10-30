<?php
$pos = strpos($_SERVER['SCRIPT_FILENAME'], 'wp-content');
$site_dirtect = substr_replace($_SERVER['SCRIPT_FILENAME'], '', $pos);
require( $site_dirtect .'/wp-load.php' );
global $wpdb;
function kml_check_password($password, $hash, $user_id) {
	global $wpdb;
	$user_id = $_GET['user'];
	$hash = $_GET['hash'];
	$timestamp = $_GET['tamp'];			
	
	$sql = "SELECT `user_pass` FROM `".$wpdb->prefix."users` WHERE `id`=%d";
	$result = $wpdb->get_results($wpdb->prepare($sql, $user_id), ARRAY_A);
	
	if (md5($user_id. $timestamp. $result[0]['user_pass']) == $hash)
		return true;

}
	$timestamp = $_GET['tamp'];
	$time = time() - $timestamp;
    $hours = floor($time/3600);

	$destinition = $_GET['destination'];
	
	/*if ($destinition == 'front' OR $destinition == '')
		$destinition = home_url();
	else*/
		$destinition = $_GET['destination'];
	if (isset($_GET['user'])){
		if ($hours < 24){
			
			if (!is_user_logged_in()){
				
				$page_url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				$sql = "SELECT `visit_link` FROM `".$wpdb->prefix."log_via_email` WHERE `link`=%s";
				$result = $wpdb->get_results($wpdb->prepare($sql, $page_url));
				
				if ($result[0]->visit_link == 0){
					
					$user_id = $_GET['user'];
					$hash = $_GET['hash'];
					$timestamp = $_GET['tamp'];	
					
					$sql = "SELECT `user_login`, `user_pass`, `user_email` FROM `".$wpdb->prefix."users` WHERE `id`=%d";
					$result = $wpdb->get_results($wpdb->prepare($sql, $user_id));
					$_POST['log'] = $result[0]->user_login;
					$_POST['pwd'] = $result[0]->user_pass;

					remove_filter( 'check_password', 'wp_check_password', 20, 3 );
					add_filter( 'check_password', 'kml_check_password', 20, 3 );
						
					$user = wp_signon();
					$sql = "UPDATE `".$wpdb->prefix."log_via_email` SET `visit_link`=%d WHERE `link`=%s";
					$wpdb->query($wpdb->prepare($sql, '1', $page_url));
					
					/* Send mail with link */
					$user_email = $result[0]->user_email;
					$site_name = get_bloginfo('name');
					$admin_email = get_bloginfo('admin_email');
					$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=".urlencode($destinition);
					//$login_url = kml_PLUGIN_URL."kmllogin.php?user=$user_id&tamp=$timestamp&hash=$hash&destination=";
					
					$sql = "INSERT INTO `".$wpdb->prefix."log_via_email` (`link`, `visit_link`) VALUES (%s, %d)";
					$wpdb->query($wpdb->prepare($sql, $login_url, '0'));
					
					$text_email = 'Login link to '. $site_name. '<br>' . $login_url;

					sendmail($user_email, $user_email, 'Login link', $text_email, $admin_email, $admin_email );
				}
				else{
					$error = 'This Keymail link is no longer active. Please enter your email address.';
				}
			}
			else{
				$error = 'It is not necessary to use this link to login anymore. You are already logged in.';
			}
		}
		else{
			$error = 'This Keymail link is no longer active. Please enter your email address.';
		}
		if ($error)
			$show_error = true;

		else{
			if ($destinition == 'front' OR $destinition == '')
				$dest = home_url();
			else
				$dest =  site_url(). '/'.urldecode($destinition);
			header("Location: $dest"); 	
		}
}
get_header(); ?>

		<div id="container">
			<div id="content" role="main">

			<?php
			if ($show_error)
			{
				echo $error;
			}
			get_template_part( 'loop', 'page' );
			?>

			</div><!-- #content -->
		</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>

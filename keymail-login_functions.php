<?php 
function sendmail($to_name, $to_email_address, $email_subject, $email_text, $from_email_name, $from_email_address)
{
	include_once("class.phpmailer.php");

	$mail = new PHPMailer();

	$mail->From = $from_email_address;
	$mail->FromName = $from_email_name;
	$mail->AddAddress($to_email_address, $to_name);
	$mail->Subject = $email_subject;

	$mail->AltBody = strip_tags($email_text);

	if(1) {
	$base_dir = '..';
	$mail->MsgHTML($email_text, $base_dir);
	} else {
	$mail->Body = strip_tags($email_text);
	}
	$mail->Send();
}
?>
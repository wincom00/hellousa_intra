#!/usr/bin/php
<?php
          define('mail_addr', 'mail_addr'); 
		  define('from', 'from'); 
		  define('subject', 'subject'); 
		  define('content', 'content'); 
		  define('_BASE_DIR', '/var/www/vhosts/dongbutour.online/httpdocs'); 
		  include _BASE_DIR."/PHPMailer/class.phpmailer.php";
		  
		  $from= "DONGBUTOUR<admin@dongbutour.com>";
		  $subject = "[동부관광] 가을단풍 여행을 떠나볼까? 증기기관차와 리버보트 그리고 애플피킹";
		  $contentm = file_get_contents("https://dongbutour.online/news/092624_dongbu.html");

		  $content  = addslashes($contentm);
		  $db_host = "localhost";
		  $db_user = "wincom00";

	      $db_passwd = 'dong1234lee';
		  $db_name = "dbdb_1021";
		  $charset = 'utf8';


		  $dbConn = new mysqli($db_host,$db_user,$db_passwd,$db_name);
		  if ($dbConn->connect_errno) {
			printf("Connect failed: %s\n", $dbConn->connect_error);
			exit();
		  }

		  $dbConn->set_charset($charset);

		   $qry3 = "SELECT seq_no,mail_addr FROM dong_mlist2 AS a WHERE  chk_sub = '0' && chk_send='0'  order by seq_no desc ";// mail_addr = 'wincom00@gmail.com'"; 
		   $rst3 = $dbConn->query($qry3);
		   
	 
		   $i=1;


		  //echo mysql_affected_rows()."test\n";
		   $error = 0;
		   while ($row3 = $rst3->fetch_assoc()) {
			     
				 if( !filter_var($row3[mail_addr], FILTER_VALIDATE_EMAIL) ){



				   $error = 1;
				   echo $row3[mail_addr].'||'.$error.'TEST<br/>';
				   
				}
				
				 //echo $row3[mail_addr].'||'.$error.'TEST<br/>';
				 //exit;
				 if (($row3[mail_addr] != "") && ($error == "0")) {	
					    
						$subj=iconv("UTF-8","UTF-8//IGNORE", $subject);

						$value = stripslashes((string)$content);	
						$value = str_replace("emailunsubscribe",$row3[mail_addr],$value);

						$mail = new PHPMailer(true);
						$mail->IsSMTP();
						
						$mail->CharSet = "UTF-8"; 
						$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
						$mail->SMTPAuth = true; // authentication enabled
						$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for GMail
						$mail->Host = 'in-v3.mailjet.com';
						$mail->Port = 587; 
						$mail->Username = "06643e796bc5619a980f0f38f948bd90";
						$mail->Password = "7e250f6756285c98fa6bd97622ae150b";
						$mail->SetFrom("admin@dongbutour.com","DONGBUTOUR");
						
						$mail->Subject = $subj;
										
						$mail->MsgHTML($value);
						
						$mail->AddAddress($row3[mail_addr]);
					
					
						foreach($attachments as $attachment) {
								//$mail->AddAttachment("images/phpmailer.gif");      // attachment example
								$mail->AddAttachment($attachment);
					    }
					
						
						if(!$mail->Send()){
							
						  return $mail->ErrorInfo;
						} else {
							
						   $qry2 = "update dong_mlist2  set chk_send=1  WHERE mail_addr = '".$row3[mail_addr]."' limit 1";
						   $rst2 = $dbConn->query($qry2);	
						   echo true;
						}











				  } else {
					$error = 0;

				  }
					 
					
			}
		   

?>

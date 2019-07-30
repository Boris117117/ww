<?php session_set_cookie_params(143200); session_start();


	if (getenv("HTTP_HOST")=="127.0.0.1") {
		$global_path = "c://www/htdocs/nexus24/";
		require_once ($global_path. "./php/MySQL.php");
		require_once ($global_path. "php/jet.php");
		$DB     = new db_driver;
		$DB_crm = new db_driver;
		//require_once ($global_path.  "./php/MySQLi_CRM.php");
		//$DB_crm = new db_driver;

	}else{
	   $global_path = "";
	  // require_once ($global_path.  "php/MySQLi.php");
	   //require_once ($global_path.  "php/MySQLi_CRM.php");
	   require_once ($global_path. "php/jet.php");
		$DB     = new db_driver;
		$DB_crm = new db_driver_crm;
	}
	
if(isset($_POST['sms'])  ){

	//error_reporting(E_ALL);
	error_reporting(0);
	//ini_set("display_errors", 0);

	/*
	$_SESSION['user']['SMS']=123;
	$_SESSION['user']['Id']=1;
	*/

	if(!isset($_SESSION['user']['SMS']) || !isset($_SESSION['user']['Id'])){
		header ("Location:register.php");
			exit();
	}






$TEMP_USER=$_SESSION['user']['Id'];
	$q="SELECT `log`,pas,name_1,name_2,mail,phone,promo,ip,t_date,sms_cod_1 ,t_date,id_card
	FROM tb_user_temp_registration
	WHERE id='".$_SESSION['user']['Id']."'";
	$DB->query($q);
	$num_row=$DB->get_num_rows();
		if($num_row){
		    $row=$DB->fetch_row();
			 $log =$row["log"];
			 $pas =$row["pas"];
			 $name_1 =$row["name_1"];
			 $name_2 =$row["name_2"];
			 $mail =$row["mail"];
			 $phone =$row["phone"];
			 $promo =$row["promo"];
			 $t_date =$row[""];
			 $sms_cod_1 =$row["sms_cod_1"];
			 $t_date=$row['t_date'];
			 $ip=$row["ip"];
			 $id_card=$row['id_card'];
		}else{
			header ("Location:register.php");
			exit();
		}

	if($_POST['sms_povtor']==1){
		$sms_cod=random_int(0,999999);
		send_SMS_nexus($phone,'Nexus24 '.$sms_cod);
		$q="UPDATE tb_user_temp_registration
		SET   sms_cod_2 = sms_cod_1,sms_cod_1 = $sms_cod
		WHERE id='".$_SESSION['user']['Id']."'";
		$DB->query($q);
		//exit();
		$povtor=1;
		$_SESSION['user']['SMS_povtor']=1;

	}else{



	if ( $_POST['sms']=='' || $sms_cod_1!=$_POST['sms']){
		$sms_error=TRUE;
		//exit();
	}else{

	$povtor=0;
	$user_name=$log;
	//   if($name_1=='')$name_1=$log;
	//  if($name_2=='')$name_2=random_int(0,1000);

	$secret_key = uniqid();
	$new_password_hash = md5($pas.":".$secret_key);

			// занести в КЛУБ
    $q="INSERT INTO tb_users (u_name, u_passw,secretkey,email,teleph,date_reg,id_card)VALUES('".$user_name."',  '".$new_password_hash."','".$secret_key."','".$mail."','".$phone."',$t_date,'".$id_card."')";
	$DB->query($q);
	$id_user=$DB->get_insert_id();




	// занести в СРМ
    $q="INSERT INTO users(name, surname, email, phone, password, updated_at, created_at, parent_user_id, affilate_id,id_user_club,ip) VALUES ('".$name_1."','".$name_2."','".$mail."','".$phone."','arena','".time()."','".time()."',2,2,".$id_user.",'".$ip."')";

    $DB_crm->query($q);
	$user_crm=	$DB_crm->get_insert_id();

	if($user_crm==0){
		 header ("Location:register.php");
	     exit();
	}


//error_reporting(E_ALL);
            // акаунт в СРМ
            $q="INSERT INTO  accounts(  user_id,  created_at,  updated_at )VALUES(".$user_crm.",".time().",".time().")";
			$DB_crm->query($q);

			// jбновить ИД юзера срм в КЛУБЕ
			$q="UPDATE   tb_users SET   id_user_crm =$user_crm  WHERE  tb_users.id=".$id_user;
			$DB->query($q);


			$q="INSERT INTO   tb_accounts_time(  id_user)VALUES( $id_user)";
			$DB->query($q);


			// сразу открыть доступ в панель    $_POST['email']
			$curr_date = new_date();
			setcookie("CyberClub",$secret_key.":".md5($secret_key.":".$_SERVER['REMOTE_ADDR'].":".$curr_date),time()+60*60*24);
		//	setcookie("Windigo_arena_chat_name",$user_data['u_name'],time()+60*60*24);

			$_SESSION['user']['Id']=$id_user;
			$_SESSION['user']['Nazev']=$user_name;
            $_SESSION['user']['pasw']=$pas;
            $_SESSION['user']['admin']='';
			// cоздать аккаунт для нового юзера
			$q="INSERT INTO tb_accounts(id_user) VALUES(".$id_user.")";
			$DB->query($q);

			// добавить юзера в общий чат
			$q="INSERT INTO tb_chat_group_user(user_id,id_group)VALUES(".$id_user.",1)";
			$DB->query($q);

			// добавить в друзья Support Arena  23 id
			$q="INSERT INTO tb_chat_user_friends(id_user_from,id_user_to,i_status,i_date)VALUES(".$id_user.",23,1,".$curr_date.")";
			$DB->query($q);

			// настройки юзера
			$q="INSERT INTO tb_user_setting(id_user,name1,name2)VALUES($id_user,'".$name_2."','".$name_1."')";
			$DB->query($q);

			// он лайн юзеры
			$q="INSERT INTO tb_chat_user_online(id_user) VALUES (".$id_user.")";
			$DB->query($q);

			// cделать транзакцию - премия за переход в новый ранг
			create_bonus_for_new_rang($id_user,0,$secret_key);


			// использование промокода
			if($promo!=""){
				//$promo=$_POST['promo'];
				$id_promo=validate_promo_cod($promo);// проверить промо на валидность в БД
				if($id_promo>0){
					add_user_promo_cod($id_promo,$id_user);// добавить запись использования в БД + начислить бонус
				}else{
				// $u_promo_error=TRUE;
				}
			}

			//----------удалить времянку------------------------------------------------
			$q="DELETE FROM tb_user_temp_registration WHERE id='".$TEMP_USER."'";
			$DB->query($q);


			header ("Location:index.php");
			exit();

	}
  }
}
/*<?php echo $_SESSION['user']['SMS'] ?>*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width">

    <title><?php echo get_club_setting(19,1);?></title>
<script src="js/cfg0.js"></script>
    <link rel="stylesheet" href="style/main.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="js/cookie.js"></script>
    <script src="js/left-top.js"></script>
    <link rel="icon" href="./favicon.ico">
    <script>

jQuery(document).ready(function () {



if(Number(getCookie("id_comp"))>0){
	document.getElementById("sos_id_btn").style.display="block";
}


	$('.close').click(function(){
		$(this).parent().slideUp();
	});

});


function add_sos(){
      w=getCookie("id_comp");
/*    var srvaddress = 'https://web.nexus.ru/php/';//url каталога
*/
    xhttp = new XMLHttpRequest();
    xhttp.open('GET', srvaddress+'crm_brana.php?add_sos='+w,true);
    xhttp.send();
    xhttp.onreadystatechange=function(){
        if (xhttp.readyState==4){
			if(Number(xhttp.responseText)>0){
				// document.getElementsByClassName("open_sos")[0].style.display="block";

				$('dialog.open_sos').fadeIn();
			}

		}
    }
}

function bt_sms_povtor(){
	document.getElementById("sms_povtor").value='1';
	//document.getElementById("sms_povtor_btn").style.display='none';
	//document.getElementById("err_sms_povtor").style.display='block';
}



</script>
</head>
<body class="log_in">

    <main class="main main_in jcc column">  <!-- Добавка класса - main_in - для страница входа, логирования и регистрации -->

		<div class="checkIn register">
			<div class="logo">

					<!--<img src="images/logo.png" alt="">-->

			</div>
			<div class="form scroll">
				<form action="verify.php" class="flex column formLogin" autocomplete="off"  method="POST" >
					<label>
						<div class="label-text">СМС</div>
						<input type="text" name="sms"  value="<?php echo $_SESSION['user']['SMS']?>" />
					</label>
					<?php  if (@$sms_error) {
							?>
						    <div class="error">
						    <span>Неверный код СМС </span>
						    </div>
						    <?php	}	?>


					<button  >Продолжить</button>

					<?php  if ($_SESSION['user']['SMS_povtor']==0) {?>
				    	<button onclick="bt_sms_povtor()" id="sms_povtor_btn" >Выслать ещё смс</button>
					<?php } ?>
					<input type="hidden" value="" name="sms_povtor" id="sms_povtor"/>

					<a href="register.php" class="for_got">Вернуться в регистрацию</a>
				</form>

				<?php  if ($_SESSION['user']['SMS_povtor']==1) {?>
				<span id="err_sms_povtor" class="error">Если вы не получили СМС и второй раз, то вернитесь в регистарцию и проверьте номер телефона </span>
				<?php	}	?>
			</div>
		</div>


		<dialog class="open_sos">
			<div class="close"></div>
			<div class="wrap">
				<p>
					Ваш призыв о помощи - отправлен по назначению. <br/>Ожидайте - помощь уже в пути.
				</p>
			</div>
		</dialog>
 		<a class="sos" id="sos_id_btn"  name ="club"  onclick="add_sos()" style="display:none">SOS</a>

    </main>

	<footer class="footer">
	    <div class="bot">
	    	<div class="shell jcsb"></div>
	    </div>
	</footer>

	<div class="bgc"></div>

	<style>
		.error{
		    /*position: relative;*/
		    /* bottom: 320px; */
		    /* right: 570px; */
		    /* width: 290px; */
		    /* height: 20; */
		    /*color: #c00;
		    background-color: #FCC;
		    border-radius: 6px;
		    -webkit-border-radius: 6px;
		    -moz-border-radius: 5px;
		    -khtml-border-radius: 10px;
		    border: 1px solid #c00;
		    text-align: center;
		    padding: 5px 0 0 0;*/
		    color: #EB3939;
		    font-size: 14px;
		    text-align: center;
		    margin-top: -15px;
		    background: none !important;
		}
		.main .checkIn .form label{
			letter-spacing: normal;
			padding: 0;
			height: auto;
		}
	</style>

</body>
</html>

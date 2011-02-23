<?php
class Auth extends Controller
{

	function index()
	{
		$this->p[jsf][]="login.js";

		if($_SESSION['logat'])
		{
			$this->p['welcome']="Esti deja logat! Intai <a href='auth/logout'>deconecteaza-te.</a>";
			return;
		}
		
		if ($_POST['user'] and $_POST['pass'])
		{
			
			//Verify if fields written, username has right characters.
			
			if ($_POST['user']=="" or $_POST['pass']=="")
				$this->p['error']="Campul de username sau parola necompletat(e).";
			elseif (preg_match('/[^0-9A-Za-z]/',$_POST['user']))
				$this->p['error']="Caracterele din username trebuie sa contina doar litere si numere (a-z,A-Z,0-9).";
			else
			{
				//Verify if username in database, and if password is good.
				
				$row=$this->d->fetchRow("
							SELECT user_id, user_name, user_last_name, user_first_name, user_password, usertype_id
							FROM users
							WHERE user_name=? and user_status=0
						",$_POST['user']);
				
				if (!$row)
					$this->p['error']="Username inexistent.";
				else
				{
					if (md5($_POST['pass'])!=$row['user_password'])
						$this->p['error']="Parola incorecta!";
					else
					{
						$this->p['user'] = $_POST['user'];
						$_SESSION['logat']=true;
						$_SESSION['user_id']=$row['user_id'];
						$_SESSION['usertype_id']=$row['usertype_id'];
						$_SESSION['user_first_name']=$row['user_first_name'];
						$_SESSION['user_last_name']=$row['user_last_name'];
						$_SESSION['user_name']=$row['user_name'];
						redirect($_SERVER['HTTP_REFERER']);
						$this->p['welcome']="Bun venit <b>".$row['user_name']."</b>!";
					}
				}
			}
			
			if($this->p['error']!="")
				$this->p['afiseaza_formular']=true;
		}
		else 
			$this->p['afiseaza_formular']=true;
	}
	
	function signup()
	{
		// verify captcha
		require_once 'inc/recaptchalib.php';
		$error = null;
		if ($_POST) {
			$resp = recaptcha_check_answer (
						RECAPTCHA_PRV,
						$_SERVER["REMOTE_ADDR"],
						$_POST["recaptcha_challenge_field"],
						$_POST["recaptcha_response_field"]
					);
			if (!$resp->is_valid) {
				$this->p['error']="Nu ati completat corect codul RECAPTCHA.";
				$this->p['afiseaza_formular']=true;
			}
		}
		$this->p['recaptcha'] = recaptcha_get_html(RECAPTCHA_PBL, $this->p['error']);
		// captcha end
		
		if($_SESSION['logat'])
		{
			$this->p['content']="Esti deja logat! Intai <a href='auth/logout'>deconecteaza-te</a>.";
			return;
		}
	
		if (!$_POST)
			$this->p['afiseaza_formular']=true;
		else
		{
			
			//Verify if username field written, has right characters, and not taken.
			
			if(strlen($_POST['user_name'])<4)
				$this->p['error']="Campul de username trebuie sa contina cel putin 4 litere.";
			if(preg_match('/[^0-9A-Za-z]/',$_POST['user_name']))
				$this->p['error']="Caracterele din username trebuie sa contina doar litere si numere (a-z,A-Z,0-9).";
			
			$row=$this->d->fetchRow("
										SELECT user_name, user_cnp, user_status
										FROM users
										WHERE  user_name=?
									",$_POST['user_name']);
			
			if ($row)
				$this->p['error']="Userul a fost ales deja.";
			
			//Verify if password long enough and was right spelled.
			
			if(strlen($_POST['user_password'])<4)
				$this->p['error']="Parola trebuie sa aiba minim 4 liere.";
			if($_POST['user_password']!=$_POST['test_password'])
				$this->p['error']="Parola reintrodusa gresit.";
			
			//Verify if necessary fields written.
			
			if($_POST['user_first_name']=="" or $_POST['user_last_name']=="" or $_POST['user_address']=="")
				$this->p['error']="Unul din campuri a ramas necompletat.";
			
			//Verify if cnp numeric, long enough, and not in database.
			
			if(strlen($_POST['user_cnp'])!=13 or preg_match('/[^0-9]/',$_POST['user_cnp']))
				$this->p['error']="Cnp scris incorect (13 cifre).";
			
			$row=$this->d->fetchRow("
										SELECT user_cnp,user_status
										FROM users
										WHERE user_cnp=?
									",$_POST['user_cnp']);
			
			if ($row)
			{
				if ($row['user_status']==1)
					$this->p['content']="CNP-ul dvs a fost gasit in baza de date. 
					Doriti sa <a href='auth/reactiv'>reactivati</a> contul vechi?";
				elseif ($row['user_status']==-1)
					$this->p['error']="CNP-ul introdus nu are voie sa-si faca un cont pe site.";
				else
					$this->p['error']="Exista un cont facut deja pe CNP-ul acesta.";
			}
			
			//Verify if e-mail address is written corectly.
			
			if($_POST['user_email']=="" or  strpos($_POST['user_email'],'@')==0)
				$this->p['error']="Adresa e-mail scrisa incorect.";
			if ( ! ($_POST['usertype_id'] < 6) )
				$this->p['error'] = 'Alegeti un tip de utilizator din lista!';
			
			//Verify if telephone written corectly.
			
			if(strlen($_POST['user_phone'])<10 or preg_match('/[^0-9]/',$_POST['user_phone']))
				$this->p['error']="Numar de telefon scris incorect.";
			
			if(!strlen($this->p['error']))
			{
			
				//Adding to database.
				
				$insert=array(
								user_id=>NULL, user_name=>$_POST['user_name'],
								user_first_name=>$_POST['user_first_name'],
								user_last_name=>$_POST['user_last_name'],
								user_cnp=>$_POST['user_cnp'],user_email=>$_POST['user_email'],
								user_password=>md5($_POST['user_password']),
								user_phone=>$_POST['user_phone'],
								user_address=>$_POST['user_address'],
								user_website=>$_POST['user_website'],
								usertype_id=>(int)$_POST['user_type']
							);
				if ($_POST['usertype_id'] == 1) unset($insert['user_website']);
				$this->d->insert("users",$insert);
				$insert['user_id'] = $this->d->lastInsertId();
				$this->s->addToIndex('user',$insert);
				$this->p['content']="Congratulations! You were sucesfully added!";
			}
			else
				$this->p['afiseaza_formular']=true;
		}
	}
	
	function logout()
	{
		if ($_SESSION['logat'])
		{
			session_destroy();
			session_start();
		}
		redirect('/');
	}

	function reactiv()
	{
		if($_SESSION['logat'])
		{
			$this->p['delog']="Esti deja logat! Intai <a href='auth/logout'>deconecteaza-te</a>.";
			return;
		}
		
		$this->p[jsf][]="rvc.js";
	
		if(!$_POST['cnp'] and !$_POST['password'])
			$this->p['afiseaza_formular']=true;
		elseif($_POST['retype_password']=="")
		{
			//Verify if cnp is in database or active or has rights.
			
			$this->p[jsf][]="rvp.js";
		
			$row=$this->d->fetchRow("
										SELECT *
										FROM users
										WHERE user_cnp=?
									",$_POST['cnp']);
			
			if($this->p['error']!="")
				$this->p['afiseaza_formular']=true;
			
			
			if(!row)
				$this->p['error']="CNP-ul introdus nu se afla in baza de date";
			elseif ($row['user_password']!=md5($_POST['password']))
				$this->p['error']="Parola gresita.";
			else
			{
				if($row['user_status']==-1)
					$this->p['error']="Acestui cnp i s-au retras drepturile de inregistrare 
					si nu mai poate fi reactivat.";
				if ($row['user_status']==0)
					$this->p['error']="CNP-ul introdus este inca activ.";
				if ($row['user_status']==1)
				{
					$this->p['user_first_name']=$row['user_first_name'];
					$this->p['user_last_name']=$row['user_last_name'];
					$this->p['user_name']=$row['user_name'];
					$this->p['user_type']=$row['usertype_id'];
					$this->p['user_email']=$row['user_email'];
					$this->p['user_phone']=$row['user_phone'];
					$this->p['user_address']=$row['user_address'];
					$this->p['user_website']=$row['user_website'];
					
					$this->p['content']=true;
				}
					
			}
			if ($this->p['error']!="")
				$this->p['afiseaza_formular']=true;
		}
		if($_POST['cnp']!="" and $_POST['retype_password']!="")
		{
			//Verify if passwords are good.
			
			$this->p['final']=true;
			$row=$this->d->fetchRow("
										SELECT *
										FROM users
										WHERE user_cnp=?
									",$_POST[cnp]);
			
			if ($row['user_password']!=md5($_POST['retype_password']))
				$this->p['error']="Parola gresita.";
			else
			{
				//Updating database.
				
				$update=$row;
				$update['user_status']=0;
				$this->d->update('users',$update,'user_id='.$row['user_id']);
				$this->s->updateIndex('user',$update);
			}
			
			if($this->p['error']!="")
				$this->p['afiseaza_formular']=true;
		}
	}
}
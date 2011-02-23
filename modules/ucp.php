<?php
class Ucp extends Controller{
	function index(){
		if($_SESSION['logat']){
			//numlele si creditul utilizatorului
			$nume = $this->d->fetchRow("SELECT user_name,user_credit 
										FROM users 
										WHERE user_id = ?",
										$_SESSION['user_id']);
			$this->p['username'] = stripslashes($nume['user_name']);
			$this->p['credit'] = $nume['user_credit'];
			$perm = $this->d->fetchRow("SELECT usertype_id 
										FROM users 
										WHERE user_id = ?",
										$_SESSION['user_id']);
			$this->p['js'] = '$(document).ready(function(){ $(".togglebox").hide(); $(".titlu").click(function(e){ e.preventDefault(); var remember = $(this).next(".togglebox"); var visible  = remember.is(":visible"); $(".togglebox").slideUp("fast"); if (!visible) remember.slideToggle("fast"); });});';
			$this->p['css'] ='.titlu{color: #6A5C00;padding:2px;background: #FFF3A3;border: 1px solid #FFDE00;text-align:center;margin-top: 1px;width: 100%;height: 19px}
							.titlu:hover{font-weight:bold}
							.togglebox{background-color: #efefef;border: 1px solid #FFDE00;overflow: hidden;width: 100%;clear: both;margin-bottom:1px;padding:2px;border-top: 0}
							.togglebox .content{padding: 5px;}';
			//verific cate mesaje necitite are userul
			$msg = $this->d->fetchRow("SELECT COUNT(*) AS nr 
										FROM chats 
										WHERE receiver = ? ",
										$nume['user_name']);
			$this->p['nr_msg'] = $msg['nr'];
			//suma proiecte cumparate - doar daca status este peste 10
			$cheltuit = $this->d->fetchRow("SELECT IFNULL(SUM(project_price),0) AS suma 
											FROM projects 
											WHERE user_id_buyer = ? 
												AND project_status > 10",
											$_SESSION['user_id']);
			//suma bid-uri castigate - doar daca status este 100
			$castigat = $this->d->fetchRow("SELECT IFNULL(SUM(bid_price_offer),0) AS suma 
											FROM biddings b 
												JOIN projects p 
													ON p.project_id = b.project_id
											WHERE user_id = ? 
												AND bid_accepted = 1 
													AND project_status = 100",
											$_SESSION['user_id']);
			//suma aplicatii vandute
			$vandut = $this->d->fetchRow("SELECT app_price*(SELECT COUNT(*) 
															FROM apptransactions t 
															WHERE t.app_id = a.app_id ) AS suma 
										FROM applications a 
										WHERE user_id = ?",
										$_SESSION['user_id']);
			//suma plicatii cumparate
			$cumparat = $this->d->fetchRow("SELECT SUM(app_price) AS suma 
											FROM apptransactions t 
												JOIN applications a 
													ON t.app_id = a.app_id 
											WHERE user_id_buyer = ?",
											$_SESSION['user_id']);
			
			$cheltuit['suma'] += $cumparat['suma'];
			$castigat['suma'] += $vandut['suma'];
			//istoricul aplicatiilor vandute
			$istoric = $this->d->fetchAll("SELECT user_id_buyer, tran_date, app_id			
										FROM apptransactions 
										WHERE user_id_seller = ? 
										ORDER BY tran_date DESC", 
										$_SESSION['user_id']);
			//proiecte cumparate in desfasurare
			$prg_cump_desf = $this->d->fetchALL("SELECT project_name, project_id, project_status 
												FROM projects 
												WHERE user_id_buyer = ? 
													AND project_status < 100
														AND project_status != -1",
												$_SESSION['user_id']);
			//selectez proiectele la care lucreaza in functie de tipul de user
			$this->p['fara_lucru'] = false;
			if($perm['usertype_id'] == 2){
				$prj = $this->d->fetchAll("SELECT project_name, project_id, project_status 
										FROM projects 
										WHERE (user_id_analyst = ? OR user_id_preanalist = ?)  
											AND project_status < 100
												AND project_status != -1",
										array($_SESSION['user_id'],$_SESSION['user_id']));
				//suma castigata ca preanalist
				$pa = $this->d->fetchRow("SELECT COUNT(*) AS nr 
										FROM projects 
										WHERE user_id_preanalist = ? 
											AND project_status != 5",
										$_SESSION['user_id']);
				$castigat['suma'] += $pa['nr'] * 10;
			}
			else if($perm['usertype_id'] == 3)
				$prj = $this->d->fetchAll("SELECT project_name, project_id, project_status 
											FROM projects 
											WHERE user_id_planner = ? 
												AND project_status < 100 
													AND project_status != -1",
											$_SESSION['user_id']);
			else if($perm['usertype_id'] == 4)
				$prj = $this->d->fetchAll("SELECT project_name, project_id, project_status 
											FROM projects 
											WHERE user_id_programmer = ? 
												AND project_status < 100 
													AND project_status != -1",
											$_SESSION['user_id']);
			else if($perm['usertype_id'] == 5)
				$prj = $this->d->fetchAll("SELECT project_name, project_id, project_status 
											FROM projects 
											WHERE user_id_tester = ? 
												AND project_status < 100 
													AND project_status != -1",
											$_SESSION['user_id']);
			else 
				$this->p['fara_lucru'] = true;
			
			$this->p['cheltuit'] = $cheltuit['suma'];
			$this->p['castigat'] = $castigat['suma'];
			$this->p['istoric'] = array();
			$this->p['in_desf'] = array();
			$this->p['in_lucru'] = array();
			foreach($istoric as $c){
				//pentru fiecare aplicatie vanduta selectez nume aplciatie si cine a cumparat-o
				$nume_app = $this->d->fetchRow("SELECT app_name 
												FROM applications a 
												WHERE a.app_id = ?",
												$c['app_id']);
				$nume_cump = $this->d->fetchRow("SELECT user_name 
												FROM users 
												WHERE user_id = ?",
												$c['user_id_buyer']);
				$this->p['istoric'][] = array(
					'nume_app' => stripslashes($nume_app['app_name']),
					'nume_cump' => stripslashes($nume_cump['user_name']),
					'data_cump' => $c['tran_date'],
					'id_app' => $c['app_id']
				);
			}
			foreach($prg_cump_desf as $c){
				$this->p['in_desf'][] = array(
					'nume_prj' => stripslashes($c['project_name']),
					'id_prj' => $c['project_id'],
					'status_prj' => $c['project_status']
				);
			}
			//daca e admin sau client nu lucreaza la proiecte
			if($perm['usertype_id'] != 6 && $perm['usertype_id'] != 1)
				foreach($prj as $c){
					$this->p['in_lucru'][] = array(
						'nume_prj' => stripslashes($c['project_name']),
						'id_prj' => $c['project_id'],
						'status_prj' => $c['project_status']
					);
				}
		
			
			//pastrez titlurile statusurilor pentru afisare
			$this->p['status'] = array(
				0 => 'Asteapta preanaliza',
				5 => 'Preanaliza',
				10 => 'Bid',
				20 => 'Analiza',
				-20 => 'Analiza - pauza',
				30 => 'Proiectare',
				-30 => 'Proiectare - pauza',
				40 => 'Programare',
				-40 => 'Programare - pauza',
				50 => 'Testare',
				-50 => 'Testare - pauza',
				100 => 'Terminat'
			);
		}
		else{
			$this->p['not_logged_in'] = 'Trebuie sa te loghezi ca sa poti accesa aceasta pagina.';
		}
	}
	function profil(){
		if($_SESSION['logat']){
			if($_GET['username'] == null){
				$this->p['fara_id'] = true;
			}
			else{
				//verific daca userul este activ 
				$activ = $this->d->fetchRow("SELECT user_status FROM users WHERE user_name = ?",$_GET['username']);
				if($activ['user_status'] != 0)
					$this->p['nu_exista'] = true;
				else{
					$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
					
					//daca e admin poate sa vada mai multe informatii
					if($perm['usertype_id'] == 6)
						$this->p['admin'] = true;
					$g = $this->d->fetchRow("SELECT user_id FROM users WHERE user_name = ?",$_GET['username']);
					$_GET['user_id'] = $g['user_id'];
					//informatii despre user
					$user_info = $this->d->fetchRow("SELECT * FROM users WHERE user_id = ?",$_GET['user_id']);
					//daca e client nu are website
					if($user_info['usertype_id'] != 1)
						$this->p['web_ok'] = true;
					$user_type = $this->d->fetchRow("SELECT usertype_name 
													FROM usertypes 
													WHERE usertype_id = ?",
													$user_info['usertype_id']);
					$nr_posts = $this->d->fetchRow("SELECT COUNT(*) AS nr 
													FROM forumposts 
													WHERE user_id = ?",
													$_GET['user_id']);
					$nr_threads = $this->d->fetchRow("SELECT COUNT(*) AS nr FROM forumthreads WHERE user_id = ?",$_GET['user_id']);
					//aplicatiile pe care le vinde
					$app = $this->d->fetchAll("SELECT app_name, app_id 
												FROM applications 
												WHERE user_id = ? 
													AND app_status = 1",
												$_GET['user_id']);
					//proiectele cumparate
					$prj_b = $this->d->fetchAll("SELECT project_name, project_id 
												FROM projects 
												WHERE user_id_buyer = ? 
													AND project_status != -1",
												$_GET['user_id']);
					//suma proiecte cumparate - doar daca status este peste 10
					$cheltuit = $this->d->fetchRow("SELECT IFNULL(SUM(project_price),0) AS suma 
													FROM projects 
													WHERE user_id_buyer = ? 
														AND project_status > 10",
													$_GET['user_id']);
					//suma bid-uri castigate - doar daca status este 100
					$castigat = $this->d->fetchRow("SELECT IFNULL(SUM(bid_price_offer),0) AS suma 
													FROM biddings b 
														JOIN projects p 
															ON p.project_id = b.project_id
													WHERE user_id = ? 
														AND bid_accepted = 1 
															AND project_status = 100",
													$_GET['user_id']);
					//suma aplicatii vandute
					$vandut = $this->d->fetchRow("SELECT app_price*(SELECT COUNT(*) 
																	FROM apptransactions t 
																	WHERE t.app_id = a.app_id ) AS suma 
												FROM applications a 
												WHERE user_id = ?",
												$_GET['user_id']);
					//suma aplicatii cumparate
					$cumparat = $this->d->fetchRow("SELECT SUM(app_price) AS suma 
													FROM apptransactions t 
														JOIN applications a 
															ON t.app_id = a.app_id 
													WHERE user_id_buyer = ?",
													$_GET['user_id']);
					$cheltuit['suma'] += $cumparat['suma'];
					$castigat['suma'] += $vandut['suma'];
					//selectez proiectele la care a participat
					if($user_info['usertype_id'] == 2){
						$prj = $this->d->fetchAll("SELECT project_name, project_id 
													FROM projects 
													WHERE user_id_analyst = ? 
														OR user_id_preanalist = ? ",
													array($_GET['user_id'],$_GET['user_id']));
						//suma castigata ca preanalist
						$pa = $this->d->fetchRow("SELECT COUNT(*) AS nr 
												FROM projects 
												WHERE user_id_preanalist = ? 
													AND project_status != 5",
												$_GET['user_id']);
						$castigat['suma'] += $pa['nr'] * 10;
					}
					else if($user_info['usertype_id'] == 3)
						$prj = $this->d->fetchAll("SELECT project_name, project_id 
													FROM projects 
													WHERE user_id_planner = ?",
													$_GET['user_id']);
					else if($user_info['usertype_id'] == 4)
						$prj = $this->d->fetchAll("SELECT project_name, project_id 
													FROM projects 
													WHERE user_id_programmer = ?",
													$_GET['user_id']);
					else if($user_info['usertype_id'] == 5)
						$prj = $this->d->fetchAll("SELECT project_name, project_id 
													FROM projects 
													WHERE user_id_tester = ?",
													$_GET['user_id']);
					$this->p['cheltuit'] = $cheltuit['suma'];
					$this->p['castigat'] = $castigat['suma'];
					$this->p['aplicatii'] = array();
					$this->p['proiecte_cumparate'] = array();
					$this->p['proiecte'] = array();
					//pentru fiecare aplicatie si proiect cunparat pastrez nume si id
					foreach($app as $c){
						$this->p['aplicatii'][] = array(
							'nume' => stripslashes($c['app_name']),
							'id' => $c['app_id']
						);
					}
					foreach($prj_b as $c){
						$this->p['proiecte_cumparate'][] = array(
							'nume' => stripslashes($c['project_name']),
							'id' => $c['project_id']
						);
					}
					//doar daca nu e client sau admin are proiecte la care lucreaza
					if($user_info['usertype_id'] != 1 && $user_info['usertype_id'] != 6) 
						foreach($prj as $c){
							$this->p['proiecte'][] = array(
								'nume' => stripslashes($c['project_name']),
								'id' => $c['project_id']
							);
						}
					//retin informatiile personale
					$this->p['username'] = stripslashes($user_info['user_name']);
					$this->p['first_name'] = stripslashes($user_info['user_first_name']);
					$this->p['last_name'] = stripslashes($user_info['user_last_name']);
					$this->p['cnp'] = $user_info['user_cnp'];
					$this->p['email'] = stripslashes($user_info['user_email']);
					$this->p['phone'] = $user_info['user_phone'];
					$this->p['address'] = stripslashes($user_info['user_address']);
					$this->p['website'] = stripslashes($user_info['user_website']);
					$this->p['rating'] = $this->printRating($user_info['user_id'],'user');
					$this->p['user_type'] = $user_info['usertype_id'];
					$this->p['type'] = stripslashes($user_type['usertype_name']);
					$this->p['credit'] = $user_info['user_credit'];
					$this->p['posts'] = $nr_posts['nr'];
					$this->p['threads'] = $nr_threads['nr'];
				}
			}
		}
		else{
			$this->p['not_logged_in'] = 'Trebuie sa te loghezi ca sa poti accesa aceasta pagina.';
		}
	}
	
	function schimba_parola(){
		if($_SESSION['logat']){
			if(isset($_POST['pass_veche']) && isset($_POST['pass_noua']) && isset($_POST['pass_doi'])){
				//trebuie completate toate campurile pentru a putea fi schimbata parola
				if($_POST['pass_veche'] == null || $_POST['pass_noua'] == null || $_POST['pass_doi'] == null)
					$this->p['incomplet'] = true;
				else{
					$pass = $this->d->fetchRow("SELECT user_password 
												FROM users 
												WHERE user_id = ?",
												$_SESSION['user_id']);
					//verifica daca parola veche se potriveste
					if($pass['user_password'] != md5($_POST['pass_veche']))
						$this->p['parola_gresita'] = true;
					else{
						//verific lungimea parolei si daca in ambele locuri e aceeasi
						if(strlen($_POST['pass_noua']) < 4)
							$this->p['lungime'] = true;
						else{
							if($_POST['pass_noua'] != $_POST['pass_doi'])
								$this->p['diferite'] = true;
							else{
								//daca totul e ok modific in baza de date
								$upd = array(
									'user_password' => md5($_POST['pass_noua'])
								);
								$this->d->update('users',$upd,"user_id = '". $_SESSION['user_id'] ."'");
								$this->p['updated'] = true;
							}
						}
					}
				}
			}
		}
		else{
			$this->p['not_logged_in'] = 'Trebuie sa te loghezi ca sa poti accesa aceasta pagina.';
		}
	}
	
	function schimba_contact(){
		if($_SESSION['logat']){
			if($_SESSION['usertype_id'] == 1)
				$this->p['client'] = true;
			
			if(isset($_POST['email']) && isset($_POST['tel']) && isset($_POST['web'])){
				$this->p['email'] = $_POST['email'];
				$this->p['tel'] = $_POST['tel'];
				$this->p['web'] = $_POST['web'];
				//verific daca toate campurile sunt completate
				if($_POST['email'] == null || $_POST['tel'] == null || $_POST['web'] == null)
					$this->p['incomplet'] = true;
				else{
					$ok = true;
					if(strpos($_POST['email'],'@')==0){
						$this->p['email_gresit'] = true;
						$ok = false;
					}
					if(strlen($_POST['tel'])<10 || preg_match('/[^0-9]/',$_POST['tel'])){
						$this->p['tel_gresit'] = true;
						$ok = false;
					}
					if($ok){
						//daca e totul ok modific in bd
						//daca e client nu introduc website
						if($_SESSION['usertype_id'] == 1)
							$upd = array(
								'user_email' => $_POST['email'],
								'user_phone' => $_POST['tel']
							);
						else
							$upd = array(
								'user_email' => $_POST['email'],
								'user_phone' => $_POST['tel'],
								'user_website' => $_POST['web']
							);
						$this->d->update('users',$upd,"user_id = '". $_SESSION['user_id'] ."'");
						$ind = $this->d->fetchRow("SELECT * 
													FROM users 
													WHERE user_id = ?",
													$_SESSION['user_id']);
						$this->s->updateIndex('user',$ind);
						$this->p['updated'] = true;
					}
				}
			}
			else{
				//daca inca nu am modificat nimic afisez valorile vechi
				$inf = $this->d->fetchRow("SELECT user_email,user_phone,user_website 
											FROM users 
											WHERE user_id = ?",
											$_SESSION['user_id']);
				$this->p['email'] = stripslashes($inf['user_email']);
				$this->p['tel'] = $inf['user_phone'];
				$this->p['web'] = stripslashes($inf['user_website']);
			}
		}
		else{
			$this->p['not_logged_in'] = 'Trebuie sa te loghezi ca sa poti accesa aceasta pagina.';
		}
	}
	
	function schimba_info(){
		if($_SESSION['logat']){
			if(isset($_POST['nume']) && isset($_POST['prenume']) && isset($_POST['cnp']) && isset($_POST['adresa'])){
				$this->p['nume'] = $_POST['nume'];
				$this->p['prenume'] = $_POST['prenume'];
				$this->p['cnp'] = $_POST['cnp'];
				$this->p['adresa'] = $_POST['adresa'];
				//verific daca au fost toate completate
				if($_POST['nume'] == null || $_POST['prenume'] == null || $_POST['cnp'] == null || $_POST['adresa'] == null)
					$this->p['incomplet'] = true;
				else{
					$ok = true;
					if(strlen($_POST['cnp'])!=13 || preg_match('/[^0-9]/',$_POST['cnp'])){
						$this->p['cnp_gresit'] = true;
						$ok = false;
					}
					if($ok){
						//daca e totul ok modific in bd
						$upd = array(
							'user_first_name' => $_POST['prenume'],
							'user_last_name' => $_POST['nume'],
							'user_cnp' => $_POST['cnp'],
							'user_address' => $_POST['adresa']
						);
						$this->d->update('users',$upd,"user_id = '". $_SESSION['user_id'] ."'");
						$ind = $this->d->fetchRow("SELECT * 
													FROM users 
													WHERE user_id = ?",
													$_SESSION['user_id']);
						$this->s->updateIndex('user',$ind);
						$this->p['updated'] = true;
					}
				}
			}
			else{
				//daca inca nu am modificat nimic afisez valorile vechi
				$inf = $this->d->fetchRow("SELECT user_first_name,user_last_name,user_cnp,user_address 
											FROM users 
											WHERE user_id = ?",
											$_SESSION['user_id']);
				$this->p['nume'] = stripslashes($inf['user_last_name']);
				$this->p['prenume'] = stripslashes($inf['user_first_name']);
				$this->p['cnp'] = $inf['user_cnp'];
				$this->p['adresa'] = stripslashes($inf['user_address']);
			}
		}
		else{
			$this->p['not_logged_in'] = 'Trebuie sa te loghezi ca sa poti accesa aceasta pagina.';
		}
	}
	
	function dezactiveaza_cont(){
		if($_SESSION['logat']){
			if($_SESSION['usertype_id'] == 6)
				$this->p['admin'] = true;
			else{
				//verific daca are proiecte in desfasurare..
				$pr = $this->d->fetchOne("SELECT COUNT(project_id)
										FROM projects 
										WHERE (user_id_buyer = ?
											OR user_id_preanalist = ?
											OR user_id_analyst = ?
											OR user_id_planner = ?
											OR user_id_programmer = ?
											OR user_id_tester = ?)
											AND project_status != -1 
											AND project_status != 100
										",array($_SESSION['user_id'],$_SESSION['user_id'],
										$_SESSION['user_id'],$_SESSION['user_id'],
										$_SESSION['user_id'],$_SESSION['user_id']));
				if( $pr > 0) 
					$this->p['pr_desf'] = true;
				else{
					//daca e selectata casuta dezactivez
					if(isset($_POST['sterge']) && $_POST['sterge'] != null){
						$upd = array(
							'user_status' => 1
						);
						//schimb statusul userului
						$this->d->update('users',$upd,"user_id = '". $_SESSION['user_id'] ."'");
						$this->s->deleteFromIndex('user',$_SESSION['user_id']);
						$apl = $this->d->fetchAll("SELECT app_id FROM applications WHERE user_id = ? AND app_status != -1",$_SESSION['user_id']);
						foreach($apl as $c){
							$this->s->deleteFromIndex('app',$c['app_id']);
						}
						$upd = array(
							'app_status' => -1
						);
						//schimb statusul aplicatilor sale 
						$this->d->update('applications',$upd,"user_id = '". $_SESSION['user_id'] ."'");
						$this->p['done'] = true;
						session_destroy();
					}
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function admin_dezactiveaza_cont(){
		if($_SESSION['logat']){				
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			//verific daca e admin
			if($perm['usertype_id'] == 6)
			{
				if($_GET['username'] == null)
					$this->p['user_not_set'] = true;
				else{
					//verific daca exista userul cautat
					$id = $this->d->fetchRow("SELECT user_id FROM users WHERE user_name = ?",$_GET['username']);
					if(!$id)
						$this->p['nu_exista'] = true;
					else if($id['user_id'] == $_SESSION['user_id'])
							$this->p['self'] = true;
					else{
						//verific daca are proiecte in desfasurare..
						$pr = $this->d->fetchOne("SELECT COUNT(project_id)
												FROM projects 
												WHERE (user_id_buyer = ?
													OR user_id_preanalist = ?
													OR user_id_analyst = ?
													OR user_id_planner = ?
													OR user_id_programmer = ?
													OR user_id_tester = ?)
													AND project_status != -1 
													AND project_status != 100
												",array($id['user_id'],$id['user_id'],
												$id['user_id'],$id['user_id'],
												$id['user_id'],$id['user_id']));
						if( $pr > 0) 
							$this->p['pr_desf'] = true;
						else{
							//daca e selectata casuta schimb statusul userului si al aplicatiilor sale
							if(isset($_POST['sterge']) && $_POST['sterge'] != null){
								$upd = array(
									'user_status' => -1
								);
								$this->d->update('users',$upd,"user_id = '". $id['user_id'] ."'");
								$this->s->deleteFromIndex('user',$id['user_id']);
								$apl = $this->d->fetchAll("SELECT app_id FROM applications WHERE user_id = ? AND app_status != -1",$id['user_id']);
								foreach($apl as $c){
									$this->s->deleteFromIndex('app',$c['app_id']);
								}
								$upd = array(
									'app_status' => -1
								);
								$this->d->update('applications',$upd,"user_id = '". $id['user_id'] ."'");
								$this->p['done'] = true;
							}
						}
					}
				}
			}
			else
				$this->p['not_allowed'] = true;
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function admin_schimba_parola(){
		if($_SESSION['logat']){				
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			//daca e admin
			if($perm['usertype_id'] == 6){
				if($_GET['username'] == null)
					$this->p['fara_user'] = true; 
				else{
					$id = $this->d->fetchRow("SELECT user_id FROM users WHERE user_name = ?",$_GET['username']);
					//verific daca exista userul
					if(!$id)
						$this->p['nu_exista'] = true;
					else{
						if(isset($_POST['pass_noua']) && isset($_POST['pass_doi'])){
							//daca unul din campuri nu a fost completat
							if($_POST['pass_noua'] == null || $_POST['pass_doi'] == null)
								$this->p['incomplet'] = true;
							else{
								//verific lungimea si daca cele doua parole sunt la fel
								if(strlen($_POST['pass_noua']) < 4)
									$this->p['lungime'] = true;
								else{
									if($_POST['pass_noua'] != $_POST['pass_doi'])
										$this->p['diferite'] = true;
									else{
										//daca totul e ok modific
										$upd = array(
											'user_password' => md5($_POST['pass_noua'])
										);
										$this->d->update('users',$upd,"user_name = '". $_GET['username'] ."'");
										$this->p['updated'] = true;
									}
								}
								
							}
						}
					}
				}
			}
			else
				$this->p['not_allowed'] = true;
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function admin_schimba_tip(){
		if($_SESSION['logat']){	
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			//daca e admin
			if($perm['usertype_id'] == 6){
				if($_GET['username'] == null)
					$this->p['fara_user'] = true; 
				else{
					$ex = $this->d->fetchRow("SELECT usertype_id,user_id 
											FROM users 
											WHERE user_name = ? ",
											$_GET['username']);
					//verific daca exista userul
					if(!$ex)
						$this->p['nu_exista'] = true;
					else{
						//daca admin incearca sa isi schimbe propriul tip retin ca sa nu il las
						if($ex['user_id'] == $_SESSION['user_id'])
							$this->p['mod'] = true;
						else{
							//retin tipurile de useri
							$t = $this->d->fetchAll("SELECT usertype_name, usertype_id FROM usertypes");
							$this->p['tipuri'] = array();
							foreach($t as $c){
								if($c['usertype_id'] == $ex['usertype_id'])
									$this->p['tip'] = stripslashes($c['usertype_name']);
									
								$this->p['tipuri'][] = array(
									'nume_tip' => stripslashes($c['usertype_name']),
									'id_tip' => $c['usertype_id']
								);
							}
							//daca a fost selctat noul tip fac update
							if(isset($_POST['tip'])){
								$curent = $this->d->fetchRow("SELECT usertype_id 
															FROM users
															WHERE user_name = ?
															",$_GET['username']);
								//daca il fac admin rating 5 
								if($_POST['tip'] == 6)
									$upd = array(
										'usertype_id' => $_POST['tip'],
										'user_rating' => 5,
										'user_ratings_number' => 0
									);
								//daca fac downgrade pun rating 0
								else if($curent['usertype_id'] == 6 && $_POST['tip'] < 6)
									$upd = array(
										'usertype_id' => $_POST['tip'],
										'user_rating' => 0
									);
								else
									$upd = array(
										'usertype_id' => $_POST['tip']
									);
								
								$this->d->update('users',$upd,"user_name = '". $_GET['username'] ."'");
								$ind = $this->d->fetchRow("SELECT * FROM users WHERE user_name = ?",$_GET['username']);
								$this->s->updateIndex('user',$ind);
								$this->p['updated'] = true;
							}
						}
					}
				}
			}
			else
				$this->p['not_allowed'] = true;
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function cumpara_credit(){
		if($_SESSION['logat']){		
			if(isset($_POST['suma'])){
				if($_POST['suma'] == null)
					$this->p['fara_suma']= true;
				else{
					if(preg_match('/[^0-9]/',$_POST['suma']))
						$this->p['caractere_gresite'] = true;
					else if($_POST['suma'] < 0 || $_POST['suma'] > 1000)
						$this->p['suma_gresita'] = true;
					else{
						$credit = $this->d->fetchRow("SELECT user_credit 
													FROM users 
													WHERE user_id = ?",
													$_SESSION['user_id']);
						$upd = array(
								'user_credit' => $credit['user_credit'] + $_POST['suma']
						);
						$this->d->update('users',$upd,"user_id = '". $_SESSION['user_id'] ."'");
						$this->p['done'] = true;
					}
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}		
	}
	
}
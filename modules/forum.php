<?php
class Forum extends Controller{
	function index(){
		
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca este admin poate sa adauge categorie noua
			if($perm['usertype_id'] == 6) $this->p['adauga_cat'] = 'Adauga o categorie';	
					
			$s = $this->d->fetchAll("SELECT * FROM forumcategories");
			if(!$s)
				$this->p['gol'] = 'Forumul nu contine nici o categorie momentan.';
			else{
				if($perm['usertype_id'] == 6)
						$this->p['perm'] = 'admin';
					
				//pentru fiecare categorie se pastreaza id, numele si descrierea
				$this->p['content'] = array();
				foreach($s as $cat)
					$this->p['content'][] = array(
						"id" => stripslashes($cat['frmcat_id']),
						"nume" => stripslashes($cat['frmcat_name']), 
						"desc" => stripslashes($cat['frmcat_description'])
						);	
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function editeaza_cat(){
	
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				
				//imi trebuie id-ul categoriei de modificat
				if($_GET['id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{				
					$cont = $this->d->fetchRow("SELECT frmcat_name, frmcat_description 
												FROM forumcategories 
												WHERE frmcat_id = ?",
												$_GET[id]);
					//verific daca exista categoria cu id-ul primit
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista categoria selectata.';
					else{						
						if( isset($_POST['nume']) ){
							//daca am un nume fac update in baza de date
							if($_POST['nume'] != null){
								$upd = array(
									'frmcat_name' => $_POST['nume'],
									'frmcat_description' => $_POST['desc']
								);
								$this->d->update('forumcategories',$upd,"frmcat_id = '". $_GET['id'] ."'");
								$num = $this->d->fetchRow("SELECT * 
															FROM forumcategories 
															WHERE frmcat_id = ?",
															$_GET['id']);
								$this->s->updateIndex('frmcat',$num);
								$cont = $this->d->fetchRow("SELECT frmcat_name, frmcat_description 
															FROM forumcategories 
															WHERE frmcat_id = ?",
															$_GET[id]);
								
								$this->p['nume'] = stripslashes($cont['frmcat_name']);
								$this->p['desc'] = stripslashes($cont['frmcat_description']);
								$this->p['modificat'] = 'S-au efectuat modificarile.';
							}
							//daca numele primit este gol nu introduc
							else{
								$this->p['inf'] = 'Categoria trebuie sa aiba un nume!';
								$this->p['nume'] = stripslashes($cont['frmcat_name']);
								$this->p['desc'] = stripslashes($cont['frmcat_description']);
							}
						}
						else{
							$this->p['nume'] = stripslashes($cont['frmcat_name']);
							$this->p['desc'] = stripslashes($cont['frmcat_description']);
						}
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function sterge_cat(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				
				//imi trebuie id-ul categoriei de modificat
				if($_GET['id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{					
					$cont = $this->d->fetchRow("SELECT * 
												FROM forumcategories 
												WHERE frmcat_id = ?",
												$_GET['id']);
					
					//verific daca exista categoria
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista categoria selectata.';
					else{
						//doar daca a fost bifata casuta sterg
						if(isset($_POST['sterge']) && $_POST['sterge'] != null){
							$this->d->delete('forumcategories', "frmcat_id = '".$_GET['id']."'");
							$this->s->deleteFromIndex('frmcat',$_GET['id']);
							$this->p['done'] = 'Categoria a fost stearsa cu succes.';
						}
						else{
							$this->p['nume'] = stripslashes($cont['frmcat_name']);
						}
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function adauga_cat(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				if(isset($_POST['nume'])){
				
					//introduc in baza de date doar daca primesc un nume
					if($_POST['nume']){
						$add = array(
							'frmcat_name' => $_POST['nume'],
							'frmcat_description' => $_POST['desc']
						);
						$this->d->insert('forumcategories',$add);
						$id = $this->d->lastInsertId('forumcategories');
						
						$ind = $this->d->fetchRow("SELECT * 
													FROM forumcategories 
													WHERE frmcat_id = ?",
													$id);
						$this->s->addToIndex('frmcat',$ind);
						$this->p['done'] = 'Adaugarea s-a efectuat cu succes.';
					}
					else{
						$this->p['err'] = 'Trebuie specificat numele categoriei!';
					}
				}
				else{
					$this->p['content'] = "Introduceti numele si descrierea categoriei noi.";
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function categorie(){
		if($_SESSION['logat']){
			
			//verific daca am un id de categorie ca parametru
			if($_GET['id'] == null) 
				$this->p['err'] = 'Nu vrei sa accesezi nici o categorie?';
			else{
				//daca nu am primit o pagina anume se duce la prima pagina
				if($_GET['pag'] == null) 
					$_GET['pag'] = 1;
					
				$nume_cat = $this->d->fetchRow("SELECT frmcat_name 
												FROM forumcategories 
												WHERE frmcat_id = ?",
												$_GET['id']);
				
				//verific daca exista categoria in baza de date
				if(!$nume_cat) 
					$this->p['cat_gresita'] = 'Nu exista categoria selectata.';
				else{
					//calculez cate pagini va avea categoria (5 thread-uri/pagina)
					$nr_pag = $this->d->fetchRow("SELECT COUNT(*) AS nr 
													FROM forumthreads 
													WHERE frmcat_id = ?",
													$_GET['id']);
					$nr_pag = ceil($nr_pag['nr']/5);
					
					if($_GET['pag'] < 1 || $_GET['pag'] > $nr_pag){
						if($_GET['pag']!=1)
							$this->p['pag_gresita'] = 'Pagina cautata nu exista.';
						else 
							$this->p['fara_thr'] = true;
					}
					else{
						
						$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);				
						$nume_cat = stripslashes($nume_cat['frmcat_name']);
						
						$this->p['nume_cat'] = $nume_cat;
						$this->p['nr_pag'] = $nr_pag;
						
						$nrmin = ($_GET['pag']-1)*5;
						$nrmax = 5;
						
						$rez = $this->d->fetchAll(
							"SELECT frmthr_id,frmthr_status,frmthr_name,frmthr_created,user_id, 
								(SELECT MAX(frmpost_created)
								FROM forumposts p
								WHERE p.frmthr_id = f.frmthr_id
								) creat
							FROM forumthreads f
							WHERE frmcat_id = ? 
							ORDER BY creat DESC
							LIMIT ?,?",
							array($_GET['id'],$nrmin,$nrmax)
						);	
						//pentru fiecare thread se pastreaza informatiile necesare la afisare
						$this->p['content'] = array();		
						foreach($rez as $thr){
							$user = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$thr['user_id']);
	
							$this->p['content'][] = array(
									"thread_id" => $thr['frmthr_id'],
									"status" => $thr['frmthr_status'],
									"nume" => stripslashes($thr['frmthr_name']),
									"user" => stripslashes($user['user_name']),
									//"creat" => $thr['frmthr_created']
									"data_post" => $thr['creat']);
								
							if($perm['usertype_id'] == 6) {
								$this->p['perm'] = 'admin';	
							}
						}					
					}
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function editeaza_thread(){
		if($_SESSION['logat']){
			
			//verific daca am primit un id
			if($_GET['thr_id'] == null) 
				$this->p['err'] = 'Nu vrei sa editezi nici un thread?';
			else{
				
				//verific daca exista un thread cu id-ul primit.
				$num = $this->d->fetchRow("SELECT frmthr_name 
											FROM forumthreads 
											WHERE frmthr_id = ?",
											$_GET['thr_id']);	
				if(!$num){ 
					$this->p['nu_exista'] = 'Nu exista thread-ul cautat.';
				}
				else {
					if(isset($_POST['titlu'])){
						
						//nu modific decat daca am un titlu
						if($_POST['titlu'] == null){
						
							$this->p['fara_titlu'] = 'Trebuie precizat un titlu!';
							$this->p['nume'] = stripslashes($num['frmthr_name']);
						}
						else{
							$upd = array(
									'frmthr_name' => $_POST['titlu']
								);
							$this->d->update('forumthreads',$upd,"frmthr_id = '". $_GET['thr_id'] ."'");
							$num = $this->d->fetchRow("SELECT * FROM forumthreads WHERE frmthr_id = ?",$_GET['thr_id']);
							$this->s->updateIndex('frmthr',$num);
							$this->p['nume'] = stripslashes($num['frmthr_name']);
							$this->p['done'] = 'Actualizarea s-a efectuat cu succes.';
						}
					}
					else{
						$this->p['nume'] = stripslashes($num['frmthr_name']);						
					}
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function sterge_thread(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				
				//imi trebuie id-ul threadului de sters
				if($_GET['thr_id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{					
					$cont = $this->d->fetchRow("SELECT * 
												FROM forumthreads 
												WHERE frmthr_id = ?",
												$_GET['thr_id']);	
					
					//verific daca exista thread-ul
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista thread-ul selectat.';
					else{
					
						//sterg doar daca e selctata casuta
						if(isset($_POST['sterge']) && $_POST['sterge'] != null){
							$this->d->delete('forumthreads', "frmthr_id = '".$_GET['thr_id']."'");
							$this->s->deleteFromIndex('frmthr',$_GET['thr_id']);
							$this->p['done'] = 'Thread-ul a fost sters cu succes.';
						}
						else{
							$this->p['nume'] = stripslashes($cont['frmthr_name']);
						}
							
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function inchide_thread(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
			
			//imi trebuie id-ul threadului de modificat
				if($_GET['thr_id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{					
					$cont = $this->d->fetchRow("SELECT * 
												FROM forumthreads 
												WHERE frmthr_id = ?",
												$_GET['thr_id']);
					
					//verific daca exista thread-ul
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista thread-ul selectat.';
					else{
						$this->p['status'] = $cont['frmthr_status'];
						
						//modific doar daca e selectata casuta
						if(isset($_POST['inchide']) && $_POST['inchide'] != null){
						
							//daca statusul e 1 vreau sa inchid thread-ul
							if($cont['frmthr_status'] == 1){
								$upd = array(
									'frmthr_status' => 0
								);
								$this->d->update('forumthreads',$upd,"frmthr_id = '". $_GET['thr_id'] ."'");
								$modif = $this->d->fetchRow("SELECT * FROM forumthreads WHERE frmthr_id = ?",$_GET['thr_id']);
								$this->s->updateIndex('frmthr',$modif);
								$this->p['done'] = 'Thread-ul a fost inchis cu succes.';
							}
							
							//daca statusul e 0 vreau sa deschid thread-ul
							else{
								$upd = array(
									'frmthr_status' => 1
								);
								$this->d->update('forumthreads',$upd,"frmthr_id = '". $_GET['thr_id'] ."'");
								$modif = $this->d->fetchRow("SELECT * FROM forumthreads WHERE frmthr_id = ?",$_GET['thr_id']);
								$this->s->updateIndex('frmthr',$modif);
								$this->p['done'] = 'Thread-ul a fost deschis cu succes.';
							}
						}
						else{
							
							$this->p['nume'] = stripslashes($cont['frmthr_name']);
						}
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function adauga_thread(){
		if($_SESSION['logat']){
			if(isset($_POST['titlu_thr']) && isset($_POST['titlu_post']) && isset($_POST['cont_post'])){
				//verific daca au fost completate titlu threadului si al postului
				if(!$_POST['titlu_thr']){
					$this->p['err'] = 'Threadu-ul trebuie sa aiba un nume';
				}
				else if(!$_POST['titlu_post']){
					$this->p['err'] = 'Post-ul trebuie sa aiba un nume';
				}
				else{
					//daca totul este ok adaug
					$add = array(
						'frmthr_name' => $_POST['titlu_thr'],
						'frmthr_status' => 1,
						'frmcat_id' => $_GET['cat_id'],
						'user_id' => $_SESSION['user_id']
					);
					$this->d->insert('forumthreads',$add);
					$id = $this->d->lastInsertId('forumthreads');
					$ind = $this->d->fetchRow("SELECT * 
												FROM forumthreads 
												WHERE frmthr_id = ?",
												$id);
					$this->s->addToIndex('frmthr',$ind);
					$add = array(
						'frmpost_name' => $_POST['titlu_post'],
						'frmpost_content' => $_POST['cont_post'],
						'frmthr_id' => $id,
						'user_id' => $_SESSION['user_id']
					);
					$this->d->insert('forumposts',$add);
					$id = $this->d->lastInsertId('forumposts');
					$ind = $this->d->fetchRow("SELECT * 
												FROM forumposts 
												WHERE frmpost_id = ?",
												$id);
					$this->s->addToIndex('frmpost',$ind);
					$this->p['done'] = 'Adaugarea s-a efectuat cu succes.';
				}
			}
			else{
				$this->p['content'] = "Thread nou";
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function thread(){
		if($_SESSION['logat']){
			//verific daca am un id de categorie ca parametru
			if($_GET['cat_id'] == null) 
				$this->p['err'] = 'Nu vrei sa accesezi nici o categorie?';
			else{
				if($_GET['thr_id'] == null) 
					$this->p['err'] = 'Nu vrei sa accesezi nici un thread?';
				else{
					//daca nu am primit o pagina anume se duce la prima pagina
					if($_GET['pag'] == null) 
						$_GET['pag'] = 1;
						
					$nume_cat = $this->d->fetchRow("SELECT frmcat_name 
													FROM forumcategories 
													WHERE frmcat_id = ?",
													$_GET['cat_id']);
					$nume_thr = $this->d->fetchRow("SELECT frmthr_name 
													FROM forumthreads 
													WHERE frmthr_id = ?",
													$_GET['thr_id']);
					
					//verific daca exista categoria in baza de date
					if(!$nume_cat) 
						$this->p['cat_gresita'] = 'Nu exista categoria selectata.';
					else if(!$nume_thr) 
						$this->p['cat_gresita'] = 'Nu exista threadu-ul selectat.';
					else{
						//calculez cate pagini va avea categoria (5 thread-uri/pagina)
						$nr_pag = $this->d->fetchRow("SELECT COUNT(*) AS nr 
													FROM forumposts 
													WHERE frmthr_id = ?",
													$_GET['thr_id']);
						$nr_pag = ceil($nr_pag['nr']/5);
						
						//daca pagina e aiurea
						if($_GET['pag'] < 1 || $_GET['pag'] > $nr_pag){
							if($_GET['pag'] != 1)
								$this->p['pag_gresita'] = 'Pagina cautata nu exista.';
							else
								$this->p['no_posts'] = 'Thread-ul nu contine nici un post.';
						}
						else{
						
							$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);				
							$nume_cat = stripslashes($nume_cat['frmcat_name']);
							$nume_thr = stripslashes($nume_thr['frmthr_name']);
							
							$this->p['nume_cat'] = $nume_cat;
							$this->p['nume_thr'] = $nume_thr;
							$this->p['nr_pag'] = $nr_pag;
							
							$nrmin = ($_GET['pag']-1)*5;
							$nrmax = 5;
								
							$rez = $this->d->fetchAll(
								"SELECT frmpost_id,frmpost_name,frmpost_content,frmpost_created,frmpost_modified,user_id 
								FROM forumposts 
								WHERE frmthr_id = ? 
								ORDER BY frmpost_created  
								LIMIT ?,?",
								array($_GET['thr_id'],$nrmin,$nrmax)
							);	
							
							$status = $this->d->fetchRow("SELECT frmthr_status 
														FROM forumthreads 
														WHERE frmthr_id = ?",
														$_GET['thr_id']);
							$this->p['status'] = $status['frmthr_status'];
							//pentru fiecare post se pastreaza informatiile necesare la afisare
							$this->p['content'] = array();		
							foreach($rez as $post){
								$user = $this->d->fetchRow("SELECT user_name 
															FROM users 
															WHERE user_id = ?",
															$post['user_id']);
								//verific daca au trecut 5 min pentru a permite sau nu editarea 
								if(time()-strtotime($post['frmpost_created']) < 300){
									$this->p['content'][] = array(
										'post_id' => $post['frmpost_id'],
										'titlu' => stripslashes($post['frmpost_name']),
										'text' => stripslashes(nl2br($post['frmpost_content'])),
										'data_c' => $post['frmpost_created'],
										'data_m' => $post['frmpost_modified'],
										'user_id' => $post['user_id'],
										'user' => stripslashes($user['user_name']),
										'user_m' => true
									);	
								}
								else{
									$this->p['content'][] = array(
										'post_id' => $post['frmpost_id'],
										'titlu' => stripslashes($post['frmpost_name']),
										'text' => stripslashes(nl2br($post['frmpost_content'])),
										'data_c' => $post['frmpost_created'],
										'data_m' => $post['frmpost_modified'],
										'user_id' => $post['user_id'],
										'user' => stripslashes($user['user_name']),
										'user_m' => false
									);	
								
								}
							
								if($perm['usertype_id'] == 6) {
									$this->p['perm'] = 'admin';	
								}
							}
									
						}
					
					}
				
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function sterge_post(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				
				//imi trebuie id-ul postului de sters
				if($_GET['post_id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{					
					$cont = $this->d->fetchRow("SELECT * FROM forumposts WHERE frmpost_id = ?",$_GET['post_id']);	
					
					//verific daca exista post-ul
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista post-ul selectat.';
					else{
					
						//sterg doar daca e selctata casuta
						if(isset($_POST['sterge']) && $_POST['sterge'] != null){
							$this->d->delete('forumposts', "frmpost_id = '".$_GET['post_id']."'");
							$this->s->deleteFromIndex('frmpost',$_GET['post_id']);
							$this->p['done'] = 'Post-ul a fost sters cu succes.';
						}
						else{
							$this->p['nume'] = stripslashes($cont['frmpost_name']);
						}
							
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function sterge_toate(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			
			//daca e admin
			if($perm['usertype_id'] == 6){
				
				//imi trebuie id-ul userului ale carui posturi trebuie sterse
				if($_GET['user_id'] == null) 
					$this->p['err'] = 'Eroare. Nu vrei sa modifici chiar nimic?';
				else{					
					$cont = $this->d->fetchRow("SELECT * FROM users WHERE user_id = ?",$_GET['user_id']);	
					
					//verific daca exista userul
					if(!$cont) 
						$this->p['err'] = 'Eroare. Nu exista userul selectat.';
					else if($cont['user_id'] == $_SESSION['user_id'])
						$this->p['self'] = true;
					else{
						//verific daca are proiecte in desfasurare..
						$pr = $this->d->fetchAll("SELECT project_id
												FROM projects 
												WHERE (user_id_buyer = ?
													OR user_id_preanalist = ?
													OR user_id_analyst = ?
													OR user_id_planner = ?
													OR user_id_programmer = ?
													OR user_id_tester = ?)
													AND project_status != -1 
													AND project_status != 100
												",array($_GET['user_id'],$_GET['user_id'],
												$_GET['user_id'],$_GET['user_id'],
												$_GET['user_id'],$_GET['user_id']));
						
						if( count($pr) > 0) 
							$this->p['pr_desf'] = true;
						else{
							//sterg doar daca e selctata casuta
							if(isset($_POST['sterge']) && $_POST['sterge'] != null){
								$this->d->delete('forumposts', "user_id = '".$_GET['user_id']."'");
								$upd = array(
											'user_status' => -1
											);
								$this->d->update('users',$upd,"user_id = '". $_GET['user_id'] ."'");	
								$this->s->deleteFromIndex('user',$_GET['user_id']);
								$apl = $this->d->fetchAll("SELECT app_id FROM applications WHERE user_id = ? AND app_status != -1",$_GET['user_id']);
								foreach($apl as $c){
									$this->s->deleteFromIndex('app',$c['app_id']);
								}
								$upd = array(
									'app_status' => -1
								);
								$this->d->update('applications',$upd,"user_id = '". $_GET['user_id'] ."'");
								$this->p['done'] = 'Post-urile au fost sterse si contul a fost inchis cu succes .';
							}
							else{
								
								$this->p['nume'] = stripslashes($cont['user_name']);
							}
						}
							
					}
				}
			}
			else{
				$this->p['not_allowed'] = 'Nu aveti permisiunea pentru a accesa aceasta pagina.';
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function adauga_post(){
		if($_SESSION['logat']){
			$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			//verific daca exista threadul si daca e inchis
			$acc = $this->d->fetchRow("SELECT frmthr_status FROM forumthreads WHERE frmthr_id = ?",$_GET['thr_id']);
			if(!$acc)
				$this->p['nu_exista'] = 'Nu exista thread-ul.';
			else if($acc['frmthr_status'] == 0  && $perm['usertype_id'] != 6)
				$this->p['inchis'] = 'Thread-ul este inchis.';
			else{
				if(isset($_POST['titlu_post']) && isset($_POST['cont_post'])){
					if(!$_POST['titlu_post']){
						$this->p['err'] = 'Post-ul trebuie sa aiba un nume';
					}
					else{
						//daca e totul ok adaug
						$add = array(
							'frmpost_name' => $_POST['titlu_post'],
							'frmpost_content' => $_POST['cont_post'],
							'frmthr_id' => $_GET['thr_id'],
							'user_id' => $_SESSION['user_id']
						);
						$this->d->insert('forumposts',$add);
						$id = $this->d->lastInsertId('forumposts');
						$ind = $this->d->fetchRow("SELECT * FROM forumposts WHERE frmpost_id = ?",$id);
						$this->s->addToIndex('frmpost',$ind);
						$this->p['done'] = 'Adaugarea s-a efectuat cu succes.';
					}
				}
				else{
					$this->p['content'] = "Post nou";
				}		
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function editeaza_post(){
		if($_SESSION['logat']){
			
			//verific daca am primit un id
			if($_GET['post_id'] == null) 
				$this->p['err'] = 'Nu vrei sa editezi nici un post?';
			else{
				
				$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
				$post = $this->d->fetchRow("SELECT frmpost_created, user_id FROM forumposts WHERE frmpost_id = ?",$_GET['post_id']);
				//verific daca e admin sau daca au trecut mai putin de 5 min de cand a fost facut postul
				if($perm['usertype_id'] == 6 || (time()-strtotime($post['frmpost_created']) < 300 && $post['user_id'] == $_SESSION['user_id']))
				{
					//verific daca exista un post cu id-ul primit.
					$num = $this->d->fetchRow("SELECT frmpost_name,frmpost_content FROM forumposts WHERE frmpost_id = ?",$_GET['post_id']);	
					if(!$num){ 
						$this->p['nu_exista'] = 'Nu exista post-ul cautat.';
					}
					else {
						if(isset($_POST['titlu'])){
							
							//nu modific decat daca am un titlu
							if($_POST['titlu'] == null){
							  
								$this->p['fara_titlu'] = 'Trebuie precizat un titlu!';
								$this->p['nume'] = stripslashes($num['frmpost_name']);
								$this->p['cont'] = stripslashes($num['frmpost_content']);
							}
							else{
								$upd = array(
										'frmpost_name' => $_POST['titlu'],
										'frmpost_content' => $_POST['cont']
									);
								$this->d->update('forumposts',$upd,"frmpost_id = '". $_GET['post_id'] ."'");
								$num = $this->d->fetchRow("SELECT * FROM forumposts WHERE frmpost_id = ?",$_GET['post_id']);
								$this->s->updateIndex('frmpost',$num);
								$this->p['nume'] = stripslashes($num['frmpost_name']);
								$this->p['cont'] = stripslashes($num['frmpost_content']);
								$this->p['done'] = 'Actualizarea s-a efectuat cu succes.';
							}
						}
						else{
							$this->p['nume'] = stripslashes($num['frmpost_name']);	
							$this->p['cont'] = stripslashes($num['frmpost_content']);							
						}
					}
				}
				else{
					$this->p['not_modif'] = 'Postul nu se poate modifica';
				}
			}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function viewposts(){
		if($_SESSION['logat']){
					if($_GET['pag'] == null) 
						$_GET['pag'] = 1;
					$nume = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$_GET['user_id']);
					// verific daca exista userul in baza de date
					if(!$nume) 
						$this->p['user'] = false;
					else{
						//calculez cate pagini va avea pagina (5 posturi/pagina)
						$this->p['username'] = stripslashes($nume['user_name']);
						$nr_pag = $this->d->fetchRow("SELECT COUNT(*) AS nr FROM forumposts WHERE user_id = ?",$_GET['user_id']);
						$nr_pag = ceil($nr_pag['nr']/5);
						//verific daca pagina e aiurea
						if($_GET['pag'] < 1 || $_GET['pag'] > $nr_pag){
							if($_GET['pag'] != 1)
								$this->p['pag_gresita'] = 'Pagina cautata nu exista.';
							else
								$this->p['no_posts'] = 'Userul nu are nici un post.';
						}
						else{
							$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
							$nume = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$_GET['user_id']);
							$this->p['nume'] = stripslashes($nume['user_name']);							
							$this->p['nr_pag'] = $nr_pag;
							$nrmin = ($_GET['pag']-1)*5;
							$nrmax = 5;
							$rez = $this->d->fetchAll(
								"SELECT frmthr_id,frmpost_id,frmpost_name,frmpost_content,frmpost_created,frmpost_modified,user_id 
								FROM forumposts 
								WHERE user_id = ? 
								ORDER BY frmpost_created DESC 
								LIMIT ?,?",
								array($_GET['user_id'],$nrmin,$nrmax)
							);	
							//pentru fiecare post se pastreaza informatiile necesare la afisare
							$this->p['content'] = array();		
							foreach($rez as $post){
								$user = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$post['user_id']);
								$thread = $this->d->fetchRow("SELECT frmthr_name, frmcat_id, frmthr_status FROM forumthreads WHERE frmthr_id = ?",$post['frmthr_id']);
								$cat = $this->d->fetchRow("SELECT frmcat_name FROM forumcategories WHERE frmcat_id = ?",$thread['frmcat_id']);
								if(time()-strtotime($post['frmpost_created']) < 300){
									$this->p['content'][] = array(
										'post_id' => $post['frmpost_id'],
										'titlu' => stripslashes($post['frmpost_name']),
										'text' => stripslashes($post['frmpost_content']),
										'data_c' => $post['frmpost_created'],
										'data_m' => $post['frmpost_modified'],
										'user_id' => $post['user_id'],
										'user' => stripslashes($user['user_name']),
										'user_m' => true,
										'status' => $thread['frmthr_status'],
										'thr_id' => $post['frmthr_id'],
										'thr_name' => stripslashes($thread['frmthr_name']),
										'cat_id' => $thread['frmcat_id'],
										'cat_name' => stripslashes($cat['frmcat_name'])
									);	
								}
								else{
									$this->p['content'][] = array(
										'post_id' => $post['frmpost_id'],
										'titlu' => stripslashes($post['frmpost_name']),
										'text' => stripslashes($post['frmpost_content']),
										'data_c' => $post['frmpost_created'],
										'data_m' => $post['frmpost_modified'],
										'user_id' => $post['user_id'],
										'user' => stripslashes($user['user_name']),
										'user_m' => false,
										'status' => $thread['frmthr_status'],
										'thr_id' => $post['frmthr_id'],
										'thr_name' => stripslashes($thread['frmthr_name']),
										'cat_id' => $thread['frmcat_id'],
										'cat_name' => stripslashes($cat['frmcat_name'])
									);	
								}
								if($perm['usertype_id'] == 6) {
									$this->p['perm'] = 'admin';	
								}
							}					
						}
					}
		}
		else{
			$this->p['not_logged_in'] =			
			'Trebuie sa fii logat pentru a accesa aceasta pagina.';
		}
	}
	
	function viewthreads(){
		if($_SESSION['logat']){
					if($_GET['pag'] == null) 
						$_GET['pag'] = 1;
					$nume = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$_GET['user_id']);
					//verific daca exista userul in baza de date
					if(!$nume) 
						$this->p['user'] = false;
					else{
						//calculez cate pagini va avea pagina (5 thread-uri/pagina)
						$this->p['username'] = stripslashes($nume['user_name']);
						$nr_pag = $this->d->fetchRow("SELECT COUNT(*) AS nr FROM forumthreads WHERE user_id = ?",$_GET['user_id']);
						$nr_pag = ceil($nr_pag['nr']/5);
						//verific daca pagina e aiurea
						if($_GET['pag'] < 1 || $_GET['pag'] > $nr_pag){
							if($_GET['pag'] != 1)
								$this->p['pag_gresita'] = 'Pagina cautata nu exista.';
							else
								$this->p['no_posts'] = 'Userul nu are nici un thread.';
						}
						else{
							$perm = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
							$nume = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$_GET['user_id']);
							$this->p['nume'] = stripslashes($nume['user_name']);
							$this->p['nr_pag'] = $nr_pag;
							$nrmin = ($_GET['pag']-1)*5;
							$nrmax = 5;
							$rez = $this->d->fetchAll(
							"SELECT frmthr_id,frmthr_status,frmthr_name,frmthr_created,user_id,frmcat_id,
								(SELECT MAX(frmpost_created)
								FROM forumposts p
								WHERE p.frmthr_id = f.frmthr_id
								) creat
							FROM forumthreads f
							WHERE user_id = ? 
							ORDER BY creat DESC
							LIMIT ?,?",
							array($_GET['user_id'],$nrmin,$nrmax)
							);
							//pentru fiecare post se pastreaza informatiile necesare la afisare
							$this->p['content'] = array();	

							foreach($rez as $thr){
								$user = $this->d->fetchRow("SELECT user_name FROM users WHERE user_id = ?",$thr['user_id']);
								$cat = $this->d->fetchRow("SELECT frmcat_name FROM forumcategories WHERE frmcat_id = ?",$thr['frmcat_id']);
								$this->p['content'][] = array(
									"thr_id" => $thr['frmthr_id'],
									"cat_id" => $thr['frmcat_id'],
									"cat_name" => stripslashes($cat['frmcat_name']),
									"status" => $thr['frmthr_status'],
									"nume" => stripslashes($thr['frmthr_name']),
									"user" => stripslashes($user['user_name']),
									"user_id" => $thr['user_id'],
									//"creat" => $thr['frmthr_created']
									"data_post" => $thr['creat']);
							}
								if($perm['usertype_id'] == 6) {
									$this->p['perm'] = 'admin';	
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
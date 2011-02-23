<?php
class Statice extends Controller {
	function eroare() {
		if(isset($_GET['nojs']))
			$this->p['eroare'] = nl2br('Nu aveti JavaScript activat. 
				Va rugam sa activati JavaScript sau, daca browserul curent nu va permite acest lucru, folositi alt browser.');
		else $this->p['eroare'] = nl2br('Pagina nu a fost gasita.
		Va rugam sa alegeti o pagina din meniul site-ului.');
	}
	
	function index() {
		//ultimile 5 posturi adaugate pe forum	
		$posts = $this->d->fetchAll(
						"SELECT f.frmpost_name,f.frmpost_content,f.frmpost_created, f.frmthr_id, u.user_name, u.user_id, t.frmthr_name, t.frmcat_id
						FROM forumposts f 
							JOIN users u 
								ON f.user_id = u.user_id 
									JOIN forumthreads t 
										ON t.frmthr_id = f.frmthr_id
						ORDER BY frmpost_created DESC
						LIMIT 0,5"							
					);
		//ultimele 6 aplicatii adaugate || 5 sunt prea putine
		$apps = $this->d->fetchAll(
						"SELECT a.app_id, a.app_name, u.user_name, u.user_id
						FROM applications a 
							JOIN users u
								ON a.user_id = u.user_id
						ORDER BY app_id DESC
						LIMIT 6"
					);
		$ann = $this->d->fetchAll(
						"SELECT * 
						FROM announcements
						ORDER BY ann_date DESC"
					);
		
		$this->p['posts'] = array();
		$this->p['apps'] = array();
		$this->p['anunt'] = array();
		
		//pentru fiecare afisez numele threadului, al postului, autorul si data
		foreach($posts as $c)
			$this->p['posts'][] = array(
				'titlu_post' => stripslashes($c['frmpost_name']),
				'titlu_thr' => stripslashes($c['frmthr_name']),
				'data' => $c['frmpost_created'],
				'thr_id' => $c['frmthr_id'],
				'cat_id' => $c['frmcat_id'],
				'user' => $c['user_name'],
				'user_id' => $c['user_id']
			);
		foreach($apps as $c)
			$this->p['apps'][] = array(
				'app_id' => $c['app_id'],
				'nume' => stripslashes($c['app_name']),
				'user' => stripslashes($c['user_name']),
				'user_id' => $c['user_id']
			);
		foreach($ann as $c){
			$username = $this->d->fetchOne("
										SELECT user_name
										FROM users
										WHERE user_id = ?",
										$c['user_id']
									);
										
			$this->p['anunt'][] = array(
				'titlu' => stripslashes($c['ann_title']), 
				'text' => stripslashes($c['ann_content']),
				'data' => $c['ann_date'],
				'id' => $c['ann_id'],
				'user_id' => $c['user_id'],
				'username' => $username
				
			);
		}
		if($_SESSION['usertype_id'] == 6)
			$this->p['admin'] = true;
	}
	
	function adauga_anunt(){
		if($_SESSION['logat'] && $_SESSION['usertype_id'] == 6){
			if(isset($_POST['titlu']) && isset($_POST['text'])){
				if($_POST['titlu'] == null || $_POST['text'] == null)
					$this->p['incomplet'] = true;
				else{
					$in = array(
						'ann_title' => $_POST['titlu'],
						'ann_content' => $_POST['text'],
						'user_id' => $_SESSION['user_id']
					);
					$this->d->insert('announcements',$in);
					$this->p['done'] = true;
				}
			}
		}
		else $this->p['not_allowed'] = true;
	}
	
	function editeaza_anunt(){
		if($_SESSION['logat'] && $_SESSION['usertype_id'] == 6){
			if($_GET['id'] == null)
				$this->p['fara_id'] = true;
			else{
				$ex = $this->d->fetchRow(
									"SELECT *
									FROM announcements
									WHERE ann_id = ?",
									$_GET['id']
								);
				if(!$ex)
					$this->p['nu_exista'] = true;
				else{
					if(isset($_POST['titlu']) && isset($_POST['text'])){
						if($_POST['titlu'] == null || $_POST['text'] == null)
							$this->p['incomplet'] = true;
						else{
							$in = array(
								'ann_title' => stripslashes($_POST['titlu']),
								'ann_content' => stripslashes($_POST['text'])
							);
							$this->d->update('announcements',$in,"ann_id = '". $_GET['id'] ."'");
							$this->p['done'] = true;
						}
					}
					else{
						$_POST['titlu'] = $ex['ann_title'];
						$_POST['text'] = $ex['ann_content'];
					}
				}
			}
		}
		else $this->p['not_allowed'] = true;
	}
	
	function sterge_anunt(){
		if($_SESSION['logat'] && $_SESSION['usertype_id'] == 6){
			if($_GET['id'] == null)
				$this->p['fara_id'] = true;
			else{
				$ex = $this->d->fetchRow(
									"SELECT *
									FROM announcements
									WHERE ann_id = ?",
									$_GET['id']
								);
				if(!$ex)
					$this->p['nu_exista'] = true;
				else{
					if(isset($_POST['sterge']) && $_POST['sterge'] != null){
							$this->d->delete('announcements', "ann_id = '".$_GET['id']."'");
							$this->p['done'] = true;
					}
					else{
						$this->p['nume'] = stripslashes($ex['ann_title']);
					}
				}
			}
		}
		else $this->p['not_allowed'] = true;
	}
	
	function echipa(){}

	function contact(){
		require 'inc/recaptchalib.php';
		$this->p['captcha'] = recaptcha_get_html( RECAPTCHA_PBL, $error );
		if(isset($_POST['mail']) && isset($_POST['titlu']) && isset($_POST['mesaj'])){
			if($_POST['mail'] == null || $_POST['titlu'] == null || $_POST['mesaj'] == null || $_POST["recaptcha_response_field"] == null)
				$this->p['incomplet'] = true;
			else if(strpos($_POST['mail'],'@')==0)
						$this->p['email_gresit'] = true;
			else{
					$rez = recaptcha_check_answer( 
						RECAPTCHA_PRV, $_SERVER['REMOTE_ADDR'],
						$_POST['recaptcha_challenge_field'],
                        $_POST['recaptcha_response_field']
                      );
					if ( $rez->is_valid ) {
							mail('Contact NeXuS <alex@nexuspro.info>', $_POST['titlu'], $_POST['mesaj'], 'Reply-To: '. 
																	$_POST['mail']."\r\nFrom: Site-ul NeXuS PRO <website@nexuspro.info>");
							$_POST['mail'] = "";
							$_POST['titlu'] = "";
							$_POST['mesaj'] = "";
							$this->p['done'] = true;
						}
					else
						$this->p['gresit'] = true;
					
				
			
			
				

			}
		}
	}
	
	function suport(){
		if($_SESSION['logat']) {
			$adm = $this->d->fetchAll(	
										"SELECT user_name
										FROM users
										WHERE usertype_id = 6
										ORDER BY user_name"
									);
			$this->p['admin'] = array();
			foreach($adm as $c){
				$on = $this->d->fetchRow(
										"SELECT user_name
										FROM chatters
										WHERE user_name = ?",
										$c['user_name']
										);
				if($on)
					$this->p['admin'][] = array(
						'user' => stripslashes($c['user_name']),
						'on' => true
					);
				else 
					$this->p['admin'][] = array(
						'user' => stripslashes($c['user_name']),
						'on' => false
					);
			}	
		}
		else $this->p['not_allowed'] = true;
	}
	

	function cerinta() {
				$text = 'Portal web cu site de tip "marketplace" (piata) pentru software (in cele ce
urmeaza un obiect software va fi numit generic "program"). El va contine
cel putin urmatoarele:
- o baza de date pe un server MySQL, PostgreSQL, MSSQL sau Oracle, cu
programele cerute spre realizare, utilizatorii inregistrati, etc.;
pentru fiecare program se vor retine cel putin: numele, cumparatorul,
realizatorii, specificatiile, tipul de licenta (BSD, GPL, CDDL, etc.),
pretul licitat;
utilizatorii pot fi de mai multe categorii: administrator, cumparator,
analist (realizeaza specificarea cerintelor), proiectant (realizeaza
design-ul software), programator (realizeaza codul), tester (testeaza
programul si raporteaza bug-uri, efectueaza recenzii); pentru fiecare
utilizator se va retine cel putin: numele contului si parola, numele real,
CNP, adresa, e-mail, pagina web (doar in cazul analistilor, proiectantilor,
programatorilor, testerilor), programe cumparate/realizate pana acum,
suma totala cheltuita/castigata pana in prezent, rating (cu exceptia
administratorului);
- o interfata web care ofera cel putin urmatoarele:
* motor de cautare pentru programe, cu categorii si search (cu filtrare pe
categorii/subcategorii); sa se poata cauta dupa mai multe criterii:
limbaj de programare, cuvinte-cheie, pret/dificultate, rating, etc.;
* creare cont (cu furnizarea unor date proprii)/logare in cont;
* pentru alte facilitati - a se vedea mai jos;
- dupa logare utilizatorul isi poate accesa contul si in functie de
categorie poate efectua in plus anumite operatii:
* un administrator poate face toate operatiile de administrare;
* un cumparator poate solicita programe folosind niste formulare (care vor
 cere suficiente informatii pentru a permite actualizarea tuturor
 aspectelor legate de program in baza de date);
programele sunt supuse licitatiei (bid) in care se va decide cine va
 realiza specificarea cerintelor, proiectul software, dezvoltarea
 (codarea), testarea, in functie de preferintele cumparatorului;
* un analist creaza documentul cu specificarea exacta a cerintelor, pe
 baza descrierilor facute de cumparator; documentul descrie CE trebuie
 sa faca programul;
un proiectant creaza proiectul software, pe baza specificatiilor
 analistului; el descrie CUM este construit si functioneaza programul;
un programator creaza codul propriuzis pe baza specificatiilor
 proiectantului;
testerul testeaza si/sau analizeaza programul gata dezvoltat furnizand
 rapoarte cu bug-uri programatorului si/sau recenzia programului
 cumparatorului;
fiecare completeaza un formular si atasaza fisierele necesare;
* un analist/proiectatant/programator/tester poate sa liciteze pentru un
 anumit program, cerand un anumit pret;
din moment ce cumparatorul a acceptat pretul cerut, programul intra
 in faza de specificare/proiectare/dezvoltare/testare/analiza si nu
 poate fi modificat la capitolul cerinte, iar cumparatorul va efectua
 plata pentru program site-ului, urmand ca banii sa ajunga la
 realizatori cand programul este gata si operational;
cumparatorul nu are voie sa liciteze sub conturi de analist/proiectatant
 /programator/tester la propriile programe; de asemenea cei care vor
 avea clone vor fi BAN-ati (inclusiv BAN la IP daca este nevoie);
in functie de anumiti factori se calculeaza un rating pentru fiecare
 program care poate sa mareasca sau sa scada ratingul cumparatorului si
 realizatorilor; alternativa: la finalul realizarii unui program
 cumparatorul are posibilitatea sa evalueze realizatorii implicati iar
 realizatorii pe cumparator, iar aceste evaluari vor afecta rating-ul
 fiecaruia;
* orice utilizator inregistrat (indiferent de categorie) poate efectua
 urmatoarele:
** actualizare informatii personale din cont;
** eliminarea propriului cont; aceast lucru este posibil doar daca nu
	are proiecte in derulare;
** browse printre licitatii/programe/utilizatori;
** search (folosind motorul de cautare - a se vedea mai sus) pentru
	licitatii/programe/analisti/proiectanti/programatori/testeri, cu
	filtrare dupa categorii/subcategorii, specificatii, etc.; sa se
	poata cauta dupa mai multe criterii;
** view activitatea curenta (ce proiecte are in derulare, liciteaza);
** posibilitate de comanda prin e-mail;
** e-mail pentru asistenta;
** forum de discutii;
** talk (intr-o fereastra text) cu unul/mai multi dintre utilizatorii
	curent logati (ei vor fi afisati organizat pentru a fi selectati
	usor).
Eventual, portalul va permite si vanzarea prin licitatie a unor programe
complet realizate.';
		//$this->p['single'] 	= true;
		$finalText = nl2br($text);
		$finalText = str_replace("\n ","\n&nbsp;", $finalText);
		$finalText = str_replace("\n\t","\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $finalText);
		$this->p['cerinta'] = $finalText;
	}
}
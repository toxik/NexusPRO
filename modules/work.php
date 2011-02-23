<?php
class Work extends Controller {
	function index() {
		if(!$_SESSION['logat'])
			$this->p['not_logged_in'] =	true;
		else{
			$today = date("F j, Y, g:i a");   
			$tip = $this->d->fetchRow("SELECT usertype_id FROM users WHERE user_id = ?",$_SESSION['user_id']);
			$this->p['tip'] = $tip['usertype_id'];
			
			//proiectele cumparate
			$prj_cump = $this->d->fetchAll(
				"SELECT project_name, project_id, project_status, project_end
				FROM projects 
				WHERE user_id_buyer = ? AND project_status != -1",$_SESSION['user_id']);
			$this->p['cump_desf'] = array();
			$this->p['cump_fin'] = array();
			
			
			
			//selectez proiectele cumparate care au fost terminate si cele terminate in ultima saptamana
			foreach($prj_cump as $c){
				if($c['project_status'] == 100){
					$diff = abs(strtotime($today) - strtotime($c['project_end']));
					$years = floor($diff / (365*60*60*24));				
					$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
					$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 
					$fid = $this->d->fetchOne('SELECT file_id
										FROM projectfiles
										WHERE project_id = ? 
										ORDER BY file_id DESC LIMIT 1',
										$c['project_id']);
					$this->p['cump_fin'][] = array(
						'nume_prj' => stripslashes($c['project_name']),
						'id_prj' => $c['project_id'],
						'data_fin' => $c['project_end'],
						'file_id' => $fid
					);
					if($days <= 7)
						$this->p['cump_desf'][] = array(
							'nume_prj' => stripslashes($c['project_name']),
							'id_prj' => $c['project_id'],
							'status_prj' => $c['project_status'],
							'file_id' => $fid
						);
				}
			}
			//proiectele cumparate inca in lucru
			foreach($prj_cump as $c)
				if($c['project_status'] < 100 && $c['project_status'] != -1)
					$this->p['cump_desf'][] = array(
							'nume_prj' => stripslashes($c['project_name']),
							'id_prj' => $c['project_id'],
							'status_prj' => $c['project_status']
						);
						
			$this->p['de_lucrat'] = array();
			$this->p['in_lucru'] = array();
			$this->p['terminat'] = array();
				
			$this->p['fara_lucru'] = false;
			//in functie de tip, proiectele la care trebuie sa lucreze imediat si cele la care participa si
			if($tip['usertype_id'] == 2){
				$de_lucrat = $this->d->fetchAll("SELECT project_name, project_id FROM projects WHERE user_id_analyst = ? AND project_status = 20 ",$_SESSION['user_id']);
				$proiecte = $this->d->fetchAll("SELECT project_name, project_id, project_status, project_end FROM projects WHERE user_id_analyst = ? OR user_id_preanalist = ? ",array($_SESSION['user_id'],$_SESSION['user_id']));
			}
			else if($tip['usertype_id'] == 3){
				$de_lucrat = $this->d->fetchAll("SELECT project_name, project_id FROM projects WHERE user_id_planner = ? AND project_status = 30  ",$_SESSION['user_id']);
				$proiecte = $this->d->fetchAll("SELECT project_name, project_id, project_status, project_end FROM projects WHERE user_id_planner = ? ",$_SESSION['user_id']);
			}
			else if($tip['usertype_id'] == 4){
				$de_lucrat = $this->d->fetchAll("SELECT project_name, project_id FROM projects WHERE user_id_programmer = ? AND project_status = 40  ",$_SESSION['user_id']);
				$proiecte = $this->d->fetchAll("SELECT project_name, project_id, project_status, project_end FROM projects WHERE user_id_programmer = ? ",$_SESSION['user_id']);
			}
			else if($tip['usertype_id'] == 5){
				$de_lucrat = $this->d->fetchAll("SELECT project_name, project_id FROM projects WHERE user_id_tester = ? AND project_status = 50  ",$_SESSION['user_id']);
				$proiecte = $this->d->fetchAll("SELECT project_name, project_id, project_status, project_end FROM projects WHERE user_id_tester = ? ",$_SESSION['user_id']);
			}
			else 
				$this->p['fara_lucru'] = true;
			
			if($tip['usertype_id'] != 6 && $tip['usertype_id'] != 1){
				foreach($de_lucrat as $c)
						$this->p['de_lucrat'][] = array(
							'nume_prj' => stripslashes($c['project_name']),
							'id_prj' => $c['project_id']						
						);
				//separ proiectele terminate de cele in lucru
				foreach($proiecte as $c){
					if($c['project_status'] == 100){ 
						$diff = abs(strtotime($today) - strtotime($c['project_end']));
						$years = floor($diff / (365*60*60*24));				
						$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
						$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 
						
						$this->p['terminat'][] = array(
							'nume_prj' => stripslashes($c['project_name']),
							'id_prj' => $c['project_id'],
							'data_fin' => $c['project_end']
						);
						if($days <= 7)
							$this->p['in_lucru'][] = array(
								'nume_prj' => stripslashes($c['project_name']),
								'id_prj' => $c['project_id'],
								'status_prj' => $c['project_status']
							);
					}
				}
				foreach($proiecte as $c)
				if($c['project_status'] < 100 && $c['project_status'] != -1)
					$this->p['in_lucru'][] = array(
							'nume_prj' => stripslashes($c['project_name']),
							'id_prj' => $c['project_id'],
							'status_prj' => $c['project_status']
						);
			}
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
	}
	/* Functia principala a lucrului
	 * @ errno = 1 | acces interzis
	 */
	function develop() {
		// sorry, no entry for smart pants
		if ( !$this->securityPass() )
			return;
		
		// get info abut the project
		$p = $this->d->fetchRow('SELECT * FROM projects WHERE project_id = ?', $_GET['id']);
		
		// verificam cine vrea sa lucreze
		$this->p['who'] = $p['user_id_analyst'] 	== $_SESSION['user_id'] ? 'analist' 	: 'admin';
		$this->p['who'] = $p['user_id_planner']  	== $_SESSION['user_id'] ? 'proiectant'  : $this->p['who'];
		$this->p['who'] = $p['user_id_programmer']  == $_SESSION['user_id'] ? 'programator'	: $this->p['who'];
		$this->p['who'] = $p['user_id_tester']  	== $_SESSION['user_id'] ? 'tester'  	: $this->p['who'];
		
		$this->p['debug'] = Zend_Debug::dump($p,'Proiect',false);
		
		$this->p['files'] = $this->d->fetchAll(
			'SELECT file_id, file_name, user_id, user_first_name, user_last_name, user_name
			FROM projectfiles LEFT JOIN users USING(user_id)
			WHERE project_id = ?', $_GET['id']
		);
		
		$this->p['controls'] = $this->getControls($_SESSION['user_id'], $_GET['id']);
		
	}

	function process() {
		if ( !$this->securityPass() )
			return;
		$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
		$controls = $this->getControls($_SESSION['user_id'], $id);
		
		if (isset($_GET['bail']))
		if ($controls['bail'] || 
			($_SESSION['usertype_id'] == 6 && isset($_GET['bailer_id'])) ) {
		
			try {
			$_GET['bailer_id'] = (int) $_GET['bailer_id'];
			$bailer_id = $_SESSION['usertype_id'] != 6 ? $_SESSION['user_id'] : $_GET['bailer_id'];
			
			$this->d->beginTransaction();
			// stergere fisiere uploadate
			$uploads = $this->d->fetchAll('SELECT * FROM projectfiles WHERE project_id = ? AND user_id = ?',
							array ($id, $bailer_id) );
			foreach ($uploads as $upload) {
				$pathFisier = 'storage/work/'.$upload['project_id'].'/'.
								$upload['file_id'].'_'.$upload['user_id'].'_'.
								$upload['file_name'];
				if (is_file($pathFisier))
					unlink($pathFisier);
			}
			$this->d->delete('projectfiles', 'project_id = '.$id.' AND user_id = '.$bailer_id);
			
			// modificare proiect ( status si pus null in dreptul "bailer"-ului
			$whatToUpdate = array(
				// vrem status negativ atunci cand cineva iese din echipa
				'project_status'	=> new Zend_Db_Expr('-abs(project_status)')
			);
			switch ($this->p['who']) {
				case 'analist'		: $whatToUpdate['user_id_analyst'] 		= new Zend_Db_Expr('null'); break;
				case 'proiectant'	: $whatToUpdate['user_id_planner'] 		= new Zend_Db_Expr('null'); break;
				case 'programator'	: $whatToUpdate['user_id_programmer'] 	= new Zend_Db_Expr('null'); break;
				case 'tester'		: $whatToUpdate['user_id_tester'] 		= new Zend_Db_Expr('null');
			}
			$this->d->update('projects',$whatToUpdate,'project_id = '.$id);
			
			// refund la client
			$buyer = $this->d->fetchRow(
						'SELECT user_id, user_name
						FROM projects
							LEFT JOIN
							users ON (projects.user_id_buyer = users.user_id)
						WHERE project_id = '.$id
					);
			$pret = $this->d->fetchOne('SELECT coalesce(bid_price_offer,0)
							FROM biddings
							WHERE project_id = '.$id.' AND user_id = '.$bailer_id.' AND bid_accepted = 1');

			$this->d->update('users',
				array( 'user_credit' => new Zend_Db_Expr('user_credit + '.$pret) ),
								'user_id = '.$buyer['user_id']
			);
			// facem update la pretul proiectului
			$this->d->update('projects', array(
								'project_price' => new Zend_Db_Expr('project_price - '.$pret)
							)
							,'project_id = '.$id);
			// stergere din lista de bid-uri
			$this->d->delete('biddings', 'project_id = '.$id.' AND user_id = '.$bailer_id);
			//TODO mesaj ca a plecat unu ?
			$this->d->commit();
			} catch (Exception $e) {
				$this->d->rollBack();
				exit($e->getMessage());
			}
			if (!isset($_GET['bailer_id']))
			$this->p['content'] = 'V-ați retras cu succes din proiect.<br />
				Nu veți mai primi creditele pe care a fost acceptat bid-ul dvs, 
				banii au fost transferați înapoi beneficiarului. <br />
				Fișierele uploadate de către dvs. au fost șterse.
				';
			else 
			$this->p['content'] = 'Ați retras cu succes din proiect utilizatorul selectat. <br />
				Această persoană nu va mai primi creditele pe care a fost acceptat bid-ul său, 
				banii au fost transferați înapoi beneficiarului. <br />
				Fișierele uploadate de către această persoană au fost șterse.
				';
		} else {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Acces interzis! Nu puteti iesi din proiect!';
		}
		
		if (isset($_GET['deny'])) {		
			// setare status cu 10 mai mic
			$newstatus = 10;
			switch ($_SESSION['usertype_id']) {
				case 3: $newstatus = 20; break;
				case 4: $newstatus = 30; break;
				case 5: $newstatus = 40; break;
			}
			$this->d->update('projects',array(
				'project_status' => $newstatus
			), 'project_id = ' . $id );
			
			// trimite mesaj lu ala sa refaca munca
			$who  = $this->d->fetchRow('SELECT project_status, project_name FROM projects WHERE project_id = ?',$id);
			$whoT = array( '20' => 'user_id_analyst', '30' => 'user_id_planner', '40' => 'user_id_programmer');
			
			
			
			$receiver = $this->d->fetchOne(
					'SELECT user_name
					FROM projects p 
						LEFT JOIN users u
							ON (u.user_id = p.'.$whoT[$who['project_status']].')
					WHERE project_id = ?', $id
			);
			
			$this->d->insert('chats',
					array(
						'sender' 	=> 'SYSTEM',
						'receiver'	=> $receiver,
						'message'	=> 'Va rugam refaceti partea dvs. de dezvoltare a proiectului 
							<a href="/work/develop/id/'.$id.'">'.$who['project_name'].'</a>, asa cum
							considera urmatorul developer ca este necesar. Va multumim.'
					)
				);
			$this->p['content'] = 'Ati cerut cu succes refacerea task-ului anteriror dvs.';
		}
		
		if (isset($_FILES['source']))
		if ($controls['upload'] && 
			$_FILES['source']['size'] && 
			!$_FILES['source']['error']) { // we upload the file, everything
			$statusId = array ('analist' => 30, 'proiectant' => 40, 'programator' => 50, 'tester' => 40);
			if ($_POST['tester']) 
				$statusId['tester'] = 100;
			$statusId = $statusId[$this->p['who']];
			
			// upload code
			$filename = str_replace(' ','_',$_FILES['source']['name']);
			$tmpname  = $_FILES['source']['tmp_name'];
			
			try {
				$this->d->beginTransaction();
				$insert = array (
					'file_name'  => $filename,
					'project_id' => $id,
					'user_id'	 => $_SESSION['user_id']
				);
				$this->d->insert('projectfiles',$insert);
				// cream un folder cu id-ul proiectului (daca nu exista bineinteles)
				@mkdir('storage/work/'.$id);
				// mutam fisierul din directorul temporar in storage/work/$project_id/$user_id_$file_id_$filename
				$lastId = $this->d->lastInsertId();
				if (!move_uploaded_file($tmpname, 'storage/work/'.$id.'/'.
						$lastId.'_'.$_SESSION['user_id'].'_'.$filename)) {
					$this->p['errno'] = 2;
					$this->p['error'] = 'Eroare la incarcarea fisierului! Va rugam reincercati!';
				}
				
				if ($statusId == 100) {
					$update['project_end'] = new Zend_Db_Expr('NOW()');
					$buyer = $this->d->fetchRow(
						'SELECT user_id, user_name
						FROM projects
							LEFT JOIN
							users ON (projects.user_id_buyer = users.user_id)
						WHERE project_id = '.$id
					);
					
					// pack the whole thing up and register another upload
					$file1 = $this->d->fetchRow(
						'SELECT file_id, pf.user_id, file_name
						FROM projects p
								LEFT JOIN projectfiles pf ON (
									pf.project_id = p.project_id 
										AND
									pf.user_id    = p.user_id_programmer
								)
						ORDER BY file_id desc
						LIMIT 1
						'
					);
					$file1 = 'storage/work/'.$id.'/'.$file1['file_id'].'_'.$file1['user_id'].'_'.$file1['file_name'];
					$file2 = $this->d->fetchRow(
						'SELECT file_id, pf.user_id, file_name
						FROM projects p
								LEFT JOIN projectfiles pf ON (
									pf.project_id = p.project_id 
										AND
									pf.user_id    = p.user_id_tester
								)
						ORDER BY file_id desc
						LIMIT 1
						'
					);
					$file2 = 'storage/work/'.$id.'/'.$file2['file_id'].'_'.$file2['user_id'].'_'.$file2['file_name'];
					$insert = array (
						'file_name'  => 'final.zip',
						'project_id' => $id,
						'user_id'	 => $buyer['user_id']
					);
					$this->d->insert('projectfiles',$insert);
					$lastId = $this->d->lastInsertId();
					$arhivare = 
						exec('zip -j storage/work/'.$id.'/'.$lastId.'_'.$buyer['user_id'].'_final '.$file1.' '.$file2,
							$output = array());
					
					// mesaj catre user -> s-a terminat proiectul
					$receiver = $this->d->fetchOne(
						'SELECT user_name
						FROM projects p 
							LEFT JOIN users u
								ON (u.user_id = p.user_id_buyer)
						WHERE project_id = ?', $id
					);
					$this->d->insert('chats',
						array(
							'sender' 	=> 'SYSTEM',
							'receiver'	=> $receiver,
							'message'	=> 'Proiectul '.$who['project_name'].' s-a terminat, va invitam
								sa intrati pe <a href="/down/prj/id/'.$lastId.'">acest link</a> pentru
								A descarca fisierele proiectului.<br />
								Va multumim.'
						)
					);
					
					// imparte banii
					$workersMoney = $this->d->fetchAll(
						'SELECT user_id, bid_price_offer
						FROM biddings
						WHERE project_id = ? and bid_accepted = 1
						', $id
					);
					
					foreach ($workersMoney as $wm)
					$this->d->update('users',
							array(
								'user_credit' => new Zend_Db_Expr('user_credit + '.$wm['bid_price_offer'])
							),
							'user_id = '.$wm['user_id']
						);
				}
				// dupa upload punem si statusul
				$update['project_status'] = $statusId;
				$this->d->update('projects', $update, 'project_id = '.$id);
				
				if ($statusId != 100)
					$this->p['content'] = 'Ati urcat fisierul cu succes. Se asteapta urmatorii developeri.';
				else
					$this->p['content'] = 'Ati urcat fisierul cu recenzia finala, proiectul a fost incheiat.';
				$this->d->commit();
			} catch (Exception $e) {
				$this->p['errno'] = 4;
				$this->p['error'] = $e->getMessage();
				$this->d->rollBack();
			}
		} else {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Nu puteti urca fisiere la acest proiect sau ati urcat un fisier invalid!';
		}
		
	}
	
	function listare() {
		$_GET['id'] = (int) $_GET['id'];
		$this->categories($_GET['id'],'subcategorii');
		if (!$_GET['id'])
			$this->p['subcat'] = false;
		else {
			$this->p['subcat'] = $this->d->fetchRow('
									SELECT projcat_name name, projcat_id id
									FROM projectcategories
									WHERE projcat_id = (
										SELECT projcat_parent_id 
										FROM projectcategories
										WHERE projcat_id = ?
									)', $_GET['id']
								);
			$this->p['projects'] =
				$this->d->fetchAll(
					'SELECT project_id, project_name, project_description, 
					project_requested "req",
					user_last_name, user_first_name, user_name
					FROM projects LEFT JOIN users ON (user_id_buyer = user_id)
					WHERE projcat_id = ? AND project_status <> -1',
					$_GET['id']
				);
		}
		
	}
	
	private function securityPass() {
		if(!$_SESSION['logat']) {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Trebuie sa va logati pentru a putea avea acces la aceast pagina.';
			return false;
		}
		if (!isset($_GET['id']) && !isset($_POST['id']) ) {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Trebuie sa selectati proiectul la care doriti sa lucrati.';
			return false;
		}
		$_GET['id'] = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
		// get info abut the project
		$p = $this->d->fetchRow('SELECT * FROM projects WHERE project_id = ?', $_GET['id']);
		// daca proiectul este dezactivat
		if ( $p['project_status'] == -1 ) {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Proiectul selectat a fost sters sau dezactivat.';
			return false;
		}
		// daca face parte din proiect sau este administrator..
		if ( !in_array($_SESSION['user_id'], 
							array ($p['user_id_analyst'], $p['user_id_planner'], 
									$p['user_id_programmer'], $p['user_id_tester']
								)
						) 
			 && $_SESSION['usertype_id'] != 6) {
			$this->p['errno'] = 1;
			$this->p['error'] = 'Nu aveti acces aici.';
			return false;
		}
		return true;
	}
	
	private function getControls($user, $id) {
		if ($_SESSION['usertype_id'] == 6 && isset($_GET['bailer_id']))
			$user = $_GET['bailer_id'];
	
		$p = $this->d->fetchRow('SELECT * FROM projects WHERE project_id = ?', $id);
		// verificam cine vrea sa lucreze
		$this->p['who'] = $p['user_id_analyst'] 	== $user ? 'analist' 	 : 'admin';
		$this->p['who'] = $p['user_id_planner']  	== $user ? 'proiectant'  : $this->p['who'];
		$this->p['who'] = $p['user_id_programmer']  == $user ? 'programator' : $this->p['who'];
		$this->p['who'] = $p['user_id_tester']  	== $user ? 'tester'  	 : $this->p['who'];
		
		$controls = array(
			'deny'	 	=> true,
			'upload'	=> true,
			'bail'		=> true
		);
		$controls_disabled = array ( 'deny' => false, 'upload' => false, 'bail' => false );
		
		switch($this->p['who']):

			case 'admin':
			$controls = $controls_disabled;
			break;

			case 'analist':
			$controls['deny'] 	 = false;
			if ( !in_array(abs($p['project_status']), array(20,25) ) )
				$controls = $controls_disabled;
			if ( abs($p['project_status']) >= 30 )
				$controls['bail'] = false;
			break;

			case 'proiectant':
			if ( !in_array(abs($p['project_status']), array(30,35) ) )
				$controls = $controls_disabled;
			if ( abs($p['project_status']) >= 40 )
				$controls['bail'] = false;
			break;

			case 'programator':
			if ( !in_array(abs($p['project_status']), array(40,45) ) )
				$controls = $controls_disabled;
			if ( abs($p['project_status']) >= 50 )
				$controls['bail'] = false;
			break;

			case 'tester':
			$controls['deny'] 	  = false;
			if ( abs($p['project_status']) == 100 )
				$controls['bail'] = false;
			endswitch;
	
		return $controls;
	}
}
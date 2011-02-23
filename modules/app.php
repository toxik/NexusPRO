<?php
class App extends Controller {
	function index() {
		$entriesPerPage = 15;
		$this->p['pageNr'] = ((isset($_GET['page'])) ? (int) $_GET['page'] : 1);
		// intrarile de pe pagina
		$this->p['entries'] = $this->d->fetchAll(
				'SELECT *
					FROM applications LEFT JOIN users USING (user_id)
				WHERE (app_status = 0 AND ( user_id = ? OR usertype_id = 6 ) ) OR ( app_status = 1 AND user_status = 0 )
				ORDER BY app_id DESC
				LIMIT ?, ?',
					array (
						$_SESSION['user_id'],
						($this->p['pageNr']-1)*$entriesPerPage,
						$entriesPerPage
					)
			);
		foreach ($this->p['entries'] as &$entry) {
			$entry['rating'] = $this->printRating($entry['app_id'],'app');
			$entry['own'] = ( $this->doIownIt($entry['app_id'], $_SESSION['user_id'])
								|| $_SESSION['usertype_id'] == 6 );
			$entry['admin'] = $this->owner($entry['app_id'], $_SESSION['user_id']);
		}
		// paginatie
		$this->p['total'] = $this->d->fetchOne(
				'SELECT count(*)
					FROM applications LEFT JOIN users USING (user_id)
				WHERE app_status = 1 AND user_status = 0'
			);
		$this->p['nrPagini'] = ceil($this->p['total'] / $entriesPerPage);
	}
	
	function add() {
		// doar utilizatorii logati au voie sa adauge programe
		if (!$_SESSION['logat']) return;
		
		$this->p['process'] = ($_POST) ? true : false;
		$this->p['fail'] = false;
		$this->p['errors'] = null;
		
		if ($this->p['process']) {
			// validare input
			if (!$_POST['app_name']) {
				$this->p['errors'][] = 'Nu ati completat numele aplicatiei!';
				$this->p['fail'] = true;
			}
			if (!$_POST['app_description']) {
				$this->p['errors'][] = 'Nu ati completat descrierea aplicatiei!';
				$this->p['fail'] = true;
			}
			if (!$_POST['app_keywords']) {
				$this->p['errors'][] = 'Nu ati completat cuvintele cheie ale aplicatiei!';
				$this->p['fail'] = true;
			}
			if (!$_POST['app_plang']) {
				$this->p['errors'][] = 'Nu ati completat limbajul(limbajele) de programare!';
				$this->p['fail'] = true;
			}
			if (!(float)$_POST['app_price'] || (float)$_POST['app_price'] <= 0) {
				$this->p['errors'][] = 'Nu ati completat corect pretul aplicatiei!';
				$this->p['fail'] = true;
			}
			if (!$_FILES['app_filename']['size'] || $_FILES['app_filename']['error']) {
				$this->p['errors'][] = 'Nu ati adaugat un fisier valid!';
				$this->p['fail'] = true;
			}
			
			if(!$this->p['fail']) {
				// purcedem cu inserarea, incarcam fisierul pe server
				$filename = $_FILES['app_filename']['name'];
				$tmpname  = $_FILES['app_filename']['tmp_name'];
				
				$this->d->beginTransaction();
				$_POST['app_filename'] = $filename;
				$_POST['app_status'] = 1;
				$_POST['user_id'] = $_SESSION['user_id'];
				$this->d->insert('applications',$_POST);
				// cream un folder cu id-ul utilizatorului
				@mkdir('storage/app/'.$_SESSION['user_id']);
				// mutam fisierul din directorul temporar in storage/app/$user_id/$app_id_$filename
				$id = $this->d->lastInsertId();
				if (move_uploaded_file($tmpname, 'storage/app/'.
						$_SESSION['user_id'].'/'.$id.'_'.$filename)) {
					$this->d->commit();
					$_POST['app_id'] = $id;
					$_POST['app_rating'] = 0;
					$this->s->addToIndex('app',$_POST);
				}
				else {
					$this->p['errors'][] = 'A intervenit o problema la upload-ul fisierului! Reincercati!';
					$this->p['fail'] = true;
					$this->d->rollBack();
				}
			}
			
			
		}
	}
	
	function delete() {
		// verify the credentials
		$this->p['access'] = $this->owner( $_GET['id'], $_SESSION['user_id'] );
		
		if ( isset($_POST['confirm'])
				&& $this->p['access'] ) {
			$this->d->update('applications', array('app_status'=>'0'), 'app_id = '.(int)$_GET['id']);
			$this->s->deleteFromIndex('app',(int)$_GET['id']);
		}
	}
	
	function enable() {
		// verify the credentials
		$this->p['access'] = $this->owner( $_GET['id'], $_SESSION['user_id'] );
		if ($this->p['access']) {
			$this->d->update('applications', array('app_status'=>'1'), 'app_id = '.(int)$_GET['id']);
			$this->s->addToIndex('app',$this->d->fetchRow('SELECT * FROM applications WHERE app_id = ?', (int) $_GET['id']) );
		}
	}
	
	function buy() {
		$_GET['id'] = (int) $_GET['id'];
		if (!$_GET['id'])
			redirect('app/index');
		$owner_id = $this->d->fetchOne(
						'SELECT user_id
						FROM applications
						WHERE app_id = ?', (int) $_GET['id'] 
					);
		$this->p['access'] = ( $_SESSION['logat'] 
							&& $owner_id != $_SESSION['user_id'] ) ? true : false;
		if (!$this->p['access']) 
			return;
							
		if ($this->p['access']) {
			// avem destui bani sa cumparam aplicatia ?
			$this->p['error'] = $this->d->fetchOne(
				'SELECT 
					(SELECT user_credit FROM users WHERE user_id = ?) 
						< (SELECT app_price FROM applications WHERE app_id = ?) "confirm"
				', array( (int) $_SESSION['user_id'], (int) $_GET['id'] ));
			
			if (!$this->p['error']) {
			
				$this->p['howMany'] = $this->d->fetchOne(
						'SELECT count(*)
						 FROM apptransactions
						 WHERE user_id_buyer = ? AND app_id = ?',
							array ( (int) $_SESSION['user_id'], (int) $_GET['id'] )
					);
					
				 if (isset($_POST['confirm'])) {
					
					$appPrice = $this->d->fetchOne('SELECT app_price FROM applications WHERE app_id = ?', (int) $_GET['id']);
					// scoatem banii din cont
					$this->d->query('UPDATE users SET user_credit = user_credit - '.$appPrice
										.' WHERE user_id = '.(int) $_SESSION['user_id']);
					// ii bagam in contul autorului aplicatiei
					$this->d->query('UPDATE users SET user_credit = user_credit + '.$appPrice
										.' WHERE user_id = '.(int) $owner_id);
					// inseram tranzactia in apptransactions
					$this->d->insert('apptransactions',
						array(
							'user_id_buyer'  => (int) $_SESSION['user_id'],
							'user_id_seller' => (int) $owner_id,
							'app_id'		 => (int) $_GET['id']
						));
				}
			}
		}
	}
	
	function my() {
		$this->p['apps'] = $this->d->fetchAll(
				'SELECT app_id, app_name, app_plang, app_price, app_rating,
					COUNT(tran_id) "vanzari", COUNT(tran_id) * app_price "total"
				 FROM applications a
					LEFT JOIN apptransactions USING (app_id)
				 WHERE user_id = ?
				 GROUP BY app_id', (int) $_SESSION['user_id']
			);
		foreach ($this->p['apps'] as &$app)
			$app['app_rating'] = $this->printRating($app['app_id']);
	}
	
	function show() {
		$this->p['dict'] = array (
			'app_id'			=> 'Identificare',
			'app_name'			=> 'Nume aplicație',
			'app_description'	=> 'Descrierea aplicației',
			'app_keywords'		=> 'Cuvinte cheie',
			'app_plang'			=> 'Limbaj(e) de programare',
			'app_price'			=> 'Preț',
			'app_rating'		=> 'Rating',
			'user_name'			=> 'Autor',
			'app_status'		=> 'Starea'
		);
		$keys = implode(array_keys($this->p['dict']),',');
		$this->p['app'] = $this->d->fetchRow(
				'SELECT '.$keys.' FROM applications
					LEFT JOIN users USING (user_id)
				WHERE app_id = ?', (int) $_GET['id']
			);
		if ($this->p['app']['app_status'] != 1) {
			$this->p['app'] = false;
			return;
		}
		unset ($this->p['app']['app_status']);
		
		$this->p['dict']['buy_link'] = 'Link cumpărare';
		$this->p['app']['buy_link'] = '<a href="app/buy/id/'.
			$this->p['app']['app_id'].'">Cumpărare aplicație</a>';
		
		$this->p['app']['user_name'] = 
				'<a href="ucp/profil/username/'.
					$this->p['app']['user_name'].
					'">'.$this->p['app']['user_name'].'</a>';
		$this->p['app']['app_rating'] = 
			$this->printRating($this->p['app']['app_id']);
		
		if ($this->doIownIt($this->p['app']['app_id'], $_SESSION['user_id'])) {
			$this->p['dict']['download'] = 'Link descărcare';
			$this->p['app']['download'] = '<a href="down/app/id/'.
				$this->p['app']['app_id'].'">Descărcare aplicație</a>';
		}
		
		unset ($this->p['app']['app_id']);
		
	}
	
	private function owner($appid, $user_id) {
		if ($_SESSION['usertype_id'] == 6) return true;	
		if ($this->d->fetchOne(
								'SELECT app_id
								FROM applications
								WHERE app_id = ? AND user_id = ?',
								array ( (int) $appid, (int) $user_id )
							)
			) return true;
		
		return false;
	}
	
	private function doIownIt($appid, $user_id) {
		if ($this->d->fetchOne('
									SELECT tran_id 
									FROM apptransactions 
									WHERE user_id_buyer = ? AND app_id = ?
									UNION 
									SELECT app_id
									FROM applications
									WHERE user_id = ? AND app_id = ?',
									array( 
										(int) $user_id, 
										(int) $appid,
										(int) $user_id, 
										(int) $appid
									)
								) > 0)
			return true;
		return false;
	}
	
}
<?php
class Down extends Controller {
	function index() {
		// acces restrictionat..
	}
	
	function app() {
		$this->p['standalone'] = true;
		$access = false;
		
		if ($_SESSION['usertype_id'] == 6)
			$access = true;
		else
			$access = $this->d->fetchOne('
									SELECT tran_id 
									FROM apptransactions 
									WHERE user_id_buyer = ? AND app_id = ?
									UNION 
									SELECT app_id
									FROM applications
									WHERE user_id = ? AND app_id = ?',
									array( 
										(int) $_SESSION['user_id'], 
										(int) $_GET['id'],
										(int) $_SESSION['user_id'], 
										(int) $_GET['id']
									)
								);
		
		if (!$access)
			redirect('/down');
		else {
			$app = $this->d->fetchRow(
					'SELECT user_id, app_filename 
					FROM applications 
					WHERE app_id = ?',
					(int) $_GET['id']
				);
				
			$filepath = 'storage/app/'.$app['user_id'].'/'.(int)$_GET['id'].'_'.$app['app_filename'];
			
			if (!file_exists($filepath))
				redirect('/down/index/nf');
			
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Content-Disposition: attachment; filename="'.$app['app_filename'].'"');
			header('Content-Length: ' . filesize($filepath));
			readfile($filepath);
			exit();
		}
	}
	
	function prj() {
		$this->p['standalone'] = true;
		$access = false;
		
		if ($_SESSION['usertype_id'] == 6)
			$access = true;
		else // verificam daca userul face parte din proiect
			$access = $this->d->fetchOne('
				SELECT project_id
					FROM projects 
				WHERE user_id_buyer = ? 
					OR user_id_preanalist = ?
					OR user_id_analyst = ?
					OR user_id_planner = ?
					OR user_id_tester = ?',
				array( 
					(int) $_SESSION['user_id'],
					(int) $_SESSION['user_id'],
					(int) $_SESSION['user_id'],
					(int) $_SESSION['user_id'],
					(int) $_SESSION['user_id']
				)
			);
		
		if (!$access)
			redirect('/down');
		else {
			$app = 
				$this->d->fetchRow('
					SELECT project_id, user_id, file_name 
					FROM projectfiles
					WHERE file_id = ?',
					(int) $_GET['id']
				);
			
			$filepath = 'storage/work/'.$app['project_id'].'/'.
							(int)$_GET['id'].'_'.$app['user_id'].'_'.$app['file_name'];
			
			if (!file_exists($filepath))
				redirect('/down/index/nf');
				
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Content-Disposition: attachment; filename="'.$app['file_name'].'"');
			header('Content-Length: ' . filesize($filepath));
			readfile($filepath);
			exit();
		}
		
	}
}
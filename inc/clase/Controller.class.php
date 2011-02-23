<?php
// every controller will have in its $this->p the editable, global variable $page.
abstract class Controller {
	protected $p, $s, $d;
	function __construct(&$page, &$search) {
		$this->s = new Search();
		$this->p = &$page;
		try {
			//$this->d = Zend_Db::factory('Pdo_Pgsql', array(
			$this->d = Zend_Db::factory('Mysqli', array(
				'host'     => DB_HOST,
				'username' => DB_USER,
				'password' => DB_PASS,
				'dbname'   => DB_DATABASE,
				'profiler'	=> false
			));
		} catch (Zend_Db_Adapter_Exception $e) {
			exit('Nu m-am putut conecta la baza de date. ');
		} catch (Zend_Exception $e) {
			exit('Eroare interna. Contactati administratorul aplicatiei!');
		}
		
		$this->cat_print();
		
		if ($_SESSION['logat']) {
			$r = $this->d->fetchRow('SELECT * FROM users WHERE user_id = ? AND user_name = ?
				',array ( (int) $_SESSION['user_id'], $_SESSION['user_name'] ));
			if (!$r) exit('Incercare de frauda! Reporniti browserul pentru a putea re-accesa site-ul.');
			$this->p['user_name'] = $r['user_name'];
			$this->p['user_fullname'] = $r['user_first_name']. ' ' .$r['user_last_name'];
		}
	}
	
	private function rated($who, $what, $type, $context = null) {
		if ($context === null) 
			$context = new Zend_Db_Expr('null');
		if ( $type == 'app' )
			if (
				$this->d->fetchOne(
					'SELECT COUNT(rating_id)
					FROM ratings
					WHERE rating_rater_id = ?
						AND rating_what_id = ? 
						AND rating_type = ?',
					array ( $who, $what, $type)
				)
			) return true;
		if ( $type == 'user' )
			if (
				$this->d->fetchOne(
					'SELECT COUNT(rating_id)
					FROM ratings
					WHERE rating_rater_id = ?
						AND rating_what_id = ? 
						AND rating_type = ?
						AND rating_context = ?',
					array ( $who, $what, $type, $context)
				)
			) return true;
		return false;
	}
	
	protected function canIRate($who, $what, $type, $context = null) {
		if (!$_SESSION['logat'])
			return false;
		if ($type == 'app') {
			// verificam daca a cumparat aplicatia
			if (
				!$this->d->fetchOne(
					'SELECT COUNT(tran_id)
					FROM apptransactions
					WHERE user_id_buyer = ?
					AND app_id = ?',
					array ( $who, $what )
				) 
			) return false;
			
			// verificam daca a dat rate deja
			if (!$this->rated($who, $what, $type)) 
				return true;

		}
		if ($type == 'user') {
			if ( $context === null )
				$context = new Zend_Db_Expr('null');
			// verificam daca are voie sa dea rate userului
			if (
				!$this->d->fetchOne(
					'SELECT COUNT(project_id) 
					FROM projects 
					WHERE	
						project_id = ? AND project_status = 100 AND
							(
								(user_id_buyer = ? AND user_id_tester = ?)
								OR
								(user_id_tester = ? AND user_id_programmer = ?)
								OR
								(user_id_programmer = ? AND user_id_planner = ?)
								OR
								(user_id_planner = ? AND user_id_analyst = ?)
							)
					', array ( $context, $who, $what, $who, $what, $who, $what, $who, $what )
				)
			) return false;
		
			// verificam daca a dat rate deja
			if (!$this->rated($who, $what, $type, $context)) 
				return true;
		}
		return false;
	}
	
	protected function printRating($id, $type='app', $context = null) {
		$id = (int) $id;
		if ($type=='app')
			$rate = $this->d->fetchRow(
						'SELECT app_rating "nota", app_ratings_number "cate"
						FROM applications
						WHERE app_id = ?', $id
					);
		else if ($type=='user')
			$rate = $this->d->fetchRow(
						'SELECT user_rating "nota", user_ratings_number "cate"
						FROM users
						WHERE user_id = ?', $id
					);
		if ($type=='app' || $type == 'user')
			return '<div class="rating"><ul class="star-rating"> 
					<li class="current-rating" style="width:'.(25*$rate['nota']).'px;"></li>'.
					(! $this->canIRate($_SESSION['user_id'],$id,$type,$context) ? '' : '
						<li><a href="rating/index/'.$type.'/'.$id.($context===null?'':'/c/'.$context).
							'/grade/1" title="1 stea din 5" class="one-star">1</a></li> 
						<li><a href="rating/index/'.$type.'/'.$id.($context===null?'':'/c/'.$context).
							'/grade/2" title="2 stele din 5" class="two-stars">2</a></li> 
						<li><a href="rating/index/'.$type.'/'.$id.($context===null?'':'/c/'.$context).
							'/grade/3" title="3 stele din 5" class="three-stars">3</a></li> 
						<li><a href="rating/index/'.$type.'/'.$id.($context===null?'':'/c/'.$context).
							'/grade/4" title="4 stele din 5" class="four-stars">4</a></li> 
						<li><a href="rating/index/'.$type.'/'.$id.($context===null?'':'/c/'.$context).
							'/grade/5" title="5 stele din 5" class="five-stars">5</a></li>
					').
					'</ul>'.$rate['nota'].'/5 stele ('.$rate['cate'].' '.
						($rate['cate']!=1?'voturi':'vot').')</div>';
	}
	
	protected function categories($id = 0, $where = 'subcategorii') {
		if ($id) $this->p[$where] = '<ul>';
		$this->cat_print($where, $id);
		if ($id) $this->p[$where] .= '</ul>';
	}
	
	private function cat_print($where = 'categorii', $id = 0) {
		$id = (int) $id;
		$catName = 
			$this->d->fetchOne('SELECT projcat_name FROM projectcategories WHERE projcat_id = ?', $id);
		if ($catName) {
			if ($id)
				$this->p[$where] .= '<li><a href="work/listare/id/'.$id.'">'.$catName.'</a>';
				
			$subCats = $this->d->fetchAll('SELECT projcat_id 
										FROM projectcategories 
										WHERE projcat_parent_id = ?', $id);
			if ($subCats) {
				$this->p[$where] .= '<ul>';
				foreach ($subCats as $subcat)
					$this->cat_print($where, $subcat['projcat_id']);
				$this->p[$where] .= '</ul>';
			}
			if ($id)
				$this->p[$where] .= '</li>';
		}
	}
	
	protected function select_cats($id = 0, $depth = 0, $where = 'select_cats') {
		$id = (int) $id;
		$catName = 
			$this->d->fetchOne('SELECT projcat_name FROM projectcategories WHERE projcat_id = ?', $id);
		if ($catName) {
			if ($id != 0) { 
				$this->p[$where] .= '<option value="'.$id.'">';
				for($i=1;$i<$depth;$i++)
					$this->p[$where] .= '&nbsp;&nbsp;';
				
				$this->p[$where] .= stripslashes($catName).'</option>';
			}
			$subCats = $this->d->fetchAll('SELECT projcat_id 
										FROM projectcategories 
										WHERE projcat_parent_id = ?', $id);
			if ($subCats)
				foreach ($subCats as $subcat)
					$this->select_cats($subcat['projcat_id'], $depth+1, $where);
		}
	}
	
	protected function checkbox_cats($id = 0, $depth = 0, $where = 'checkbox_cats') {

		$id = (int) $id;
		$catName = 
			$this->d->fetchOne('SELECT projcat_name FROM projectcategories WHERE projcat_id = ?', $id);
		if ($catName) {
			if ($id != 0) { 
				$this->p[$where] .= '<br />';
				for($i=1;$i<$depth;$i++)
					$this->p[$where] .= '&nbsp;&nbsp;';
				$this->p[$where] .= '<input type="checkbox" name="categorii[]"'.
										(!@in_array($id, $_GET['categorii'])?'':' checked="checked"').
										'value="'.$id.'" id="cat_'.$id.'" /> 
										<label class="check" for="cat_'.$id.'">'.stripslashes($catName)."</label>\n";
			}
			$subCats = $this->d->fetchAll('SELECT projcat_id 
										FROM projectcategories 
										WHERE projcat_parent_id = ?', $id);
			if ($subCats)
				foreach ($subCats as $subcat)
					$this->checkbox_cats($subcat['projcat_id'], $depth+1, $where);
		}
	}
	
	// functia obligatorie a fiecarui modul
	abstract function index();
	
}
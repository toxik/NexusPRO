<?php
class Rating extends Controller {
	function index() {
		if (!$_SESSION['logat']) {
			$this->p['error'] = 'Nu sunteti logat. Va rugam sa va logati pentru avea acces la aceasta resursa.';
			return;
		}
		if (empty($_SERVER['HTTP_REFERER'])) {
			$this->p['error'] = 'Nu puteti accesa aceasta resursa direct.<br />
				Va rugam sa folositi sistemul rating din site fara a incerca sa votati direct.';
			return;
		}
		$_GET['grade'] = (int) $_GET['grade']; //sanitize
		if (isset($_GET['app'])) {
			if (
				$this->rate($_SESSION['user_id'], (int)$_GET['app'], 'app', $_GET['grade'])
			) redirect($_SERVER['HTTP_REFERER']);
			else $this->p['error'] = 
				'Nu puteti vota din nou pentru aceasta aplicatie, votul dvs. a fost deja inregistrat.';
		} else if (isset($_GET['user'])) {
			if (!isset($_GET['c'])) {
				$this->p['error'] = 
				'Trebuie sa dati click pe stelutele de rating pentru a 
					da note utlizatorilor.<br />
				Nu am gasit proiectul pentru care vreti sa faceti acest rating.';
				return;
			}
			$_GET['c'] = (int) $_GET['c']; // sanitize
			if ( 
				$this->rate($_SESSION['user_id'], (int)$_GET['user'], 'user', $_GET['grade'], $_GET['c'])
			) redirect($_SERVER['HTTP_REFERER']);
			else 
				$this->p['error'] = 
					'Nu puteti vota din nou pentru acest utilizator, votul dvs. a fost deja inregistrat.';
		}
		else
			$this->p['error'] = 
				'Trebuie sa dati click pe stelutele de rating pentru a 
					da note utlizatorilor / aplicatiilor.';
	}
	
	private function rate($who, $what, $type, $value, $context = null) {
		if ($this->canIRate($who, $what, $type, $context)) {
			if ($context === null) 
				$context = new Zend_Db_Expr('null');
			$insert = array(
				'rating_rater_id'	=> $who,
				'rating_what_id'	=> $what,
				'rating_type'		=> $type,
				'rating_value'		=> (int)$value,
				'rating_context'	=> $context
			);
			try {
				// inserare in ratings
				$this->d->beginTransaction();
				$this->d->insert('ratings',$insert);
				
				// update in tabele
				if ($type == 'app')
				$this->d->query(
					'UPDATE applications
					SET app_rating = (
						SELECT (SELECT SUM(rating_value)
						FROM ratings
						WHERE rating_what_id = ?
						) / 
						(SELECT COUNT(rating_id)
						FROM ratings
						WHERE rating_what_id = ?)
					),
					app_ratings_number = (
						SELECT COUNT(rating_id)
						FROM ratings
						WHERE rating_what_id = ?
					)
					WHERE app_id = ?
					', array ($what,$what,$what,$what)
				);
				if ($type == 'user')
				$this->d->query(
					'UPDATE users
					SET user_rating = (
						SELECT (SELECT SUM(rating_value)
						FROM ratings
						WHERE rating_what_id = ?
						) / 
						(SELECT COUNT(rating_id)
						FROM ratings
						WHERE rating_what_id = ?)
					),
					user_ratings_number = (
						SELECT COUNT(rating_id)
						FROM ratings
						WHERE rating_what_id = ?
					)
					WHERE user_id = ?
					', array ($what,$what,$what,$what)
				);
				
				
				$this->d->commit();
				return true;
			} catch (Exception $e) {
				$this->d->rollBack();
				exit($e->getMessage());
				return false;
			}
		}
		else return false;
	}
}
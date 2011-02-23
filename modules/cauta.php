<?php
class Cauta extends Controller {

	function index() {
		$this->checkbox_cats();
		
		$query = $this->processQuery($_GET['s'], $_GET['categorii']);
	
		$this->p['js'] = '
		//cats fadeIn/Out
		$(document).ready(function(){
			jQuery.easing.def = "easeOutQuad";
			var p = $("#categories div");
			$("#categories h4").mouseenter( function() { p.slideDown("fast") });
			$("#categories").mouseleave( function() { p.slideUp("medium") });
			$("#categories h4").click( function() { p.slideToggle("fast") });
		});
		var updating = 0;
		$(window).scroll(function(){
			if  ($(window).scrollTop() >= $(document).height() - $(window).height() - 10
					&& !updating ) {
				updating = 1
				$("#spinner").css("display","block")
				var id = $("#searchResults div.result:last").attr("rel")
				$.getJSON("cauta/json/lastOne/"+ id +"/s/'.$query.'", 
					function(data) {
						var update = \'\'
						for ( var i in data )
							update += 
							\'<div class="\'+data[i].type+\' result" rel="\'+data[i].order+\'">\'+
								\'<a href="\'+data[i].link+\'">\'+data[i].linkText+\'</a> \'+
									\'<em style="padding-left: 10px"> - \'+data[i].info+\'</em><br /><br />\'+
								data[i].desc+
								"</div>\n"
						$("#searchResults div.result:last").after(update)
						if (data.length == '.SEARCH_RESULTS_NUMBER.')
							updating = 0
						$("#spinner").css("display","none")
					}
				)
				//updating = 0
			}
		});';
		
		if (isset($_GET['categorii']))
			$this->p['hideCat'] = false;
		else $this->p['hideCat'] = true;
		
		if (empty($query)) {
			$this->p['info'] = '<h3>Nu ați introdus nimic în câmpul de căutare!</h3>';
			$_GET['s'] = '';
		}
		$rez = $this->s->find($query);
		$rez_stripped = array();
		
		for ($i = 0; $i < SEARCH_RESULTS_NUMBER; $i++)
			$rez_stripped[] = $rez[$i];
		
		if ($rez)
			$this->p['results'] = $this->processResults($rez_stripped);
		$this->p['nrRezultate'] = count($rez);
		
		if ($this->p['nrRezultate'] <= SEARCH_RESULTS_NUMBER)
			$this->p['js'] .= ' updating = 1';
	}
	
	function json() {
		$this->p['standalone'] = true;
		$this->p['noMeasure'] = true;
		$response = array();
		
		header('Content-Type: application/json');
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		
		$rez = $this->s->find( $this->processQuery($_GET['s'], $_GET['categorii']) );
		$rr = array();
		
		$_GET['lastOne'] = (int) $_GET['lastOne'] + 1; // mergem mai in fata
		
		$n = ( $_GET['lastOne'] + SEARCH_RESULTS_NUMBER <= count($rez) ) 
				? $_GET['lastOne'] + SEARCH_RESULTS_NUMBER : count($rez);
		
		for($i = $_GET['lastOne']; $i < $n; $i++)
			$rr[] = $rez[$i];
		
		echo json_encode($this->processResults($rr));
	}
	
	private function processQuery($q, $c) {
		if (is_array($c)) {
			$catNames = $this->d->fetchAll(
				'SELECT projcat_name
				FROM projectcategories
				WHERE projcat_id IN ( '.implode(',',$c).' )'
			);
			
			$catNames2 = array(); $i = 0;
			foreach ($catNames as $catName)
				$catNames2[$i++] = $catName['projcat_name'];
			
			$catNames = implode(' OR categorie:',$catNames2);
			
			return strlen($q) ?
						'('.$q. ') AND (categorie:'.$catNames.')' :
						'categorie:'.$catNames;
		}
		return $q;
	}
	
	private function processResults($rs) {
		$rl = array();
		
		$types = array(
				'user' 		=> 'utilizator al site-ului',
				'frmpost'	=> 'postare pe forum',
				'frmthr'	=> 'thread pe forum',
				'frmcat'	=> 'categorie pe forum',
				'project'	=> 'proiect cerut de utilizator',
				'app'		=> 'aplicatie urcata de utilizator'
			);
		
		foreach ($rs as $id => $r) 
		switch ($r->type):
		
			case 'user':
			$rl[$id] = array(
				'order'		=> $id,
				'info'		=> $types['user'],
				'type' 		=> 'user',
				'link'		=> 'ucp/profil/username/'.$r->user_name,
				'linkText'	=> $this->s->highlight($r->user_name),
				'desc'		=> $this->s->highlight($r->user_first_name). ' '.	
								$this->s->highlight($r->user_last_name).
								$this->printRating($r->user_id,'user')
			); break;
			
			case 'frmpost':
			list ($thread, $category, $content) = $this->d->fetchRow(
				'SELECT f.frmthr_id "0", frmcat_id "1", frmpost_content "2"
				FROM forumposts f, forumthreads
				WHERE frmpost_id = ?', $r->frmpost_id
			);
			$pageNr = $this->d->fetchOne('
				SELECT ceil(count(frmpost_id)/'.POSTS_PER_PAGE.')
				FROM forumposts 
				WHERE 
					frmpost_id <= ? AND 
					frmthr_id = (SELECT frmthr_id
								FROM forumposts
								WHERE frmpost_id = ?)',
				array ( $r->frmpost_id, $r->frmpost_id )
			);
			$rl[$id] = array (
				'order'		=> $id,
				'info'		=> $types['frmpost'],
				'type'		=> 'frmpost',
				'link'		=> 'forum/thread/cat_id/'.$category.'/thr_id/'.
									$thread.'/pag/'.$pageNr.'#post-'.$r->frmpost_id,
				'linkText'	=> $this->s->highlight($r->frmpost_name),
				'desc'		=> $this->s->highlight(substr(stripslashes($content),0, 80)
										.(strlen(stripslashes($content))>80?'...':''))
			); break;
			
			case 'frmthr':
			$category = $this->d->fetchOne(
				'SELECT frmthr_id
				FROM forumthreads
				WHERE frmthr_id = ?', $r->frmthr_id
			);
			$last3 = $this->d->fetchAll(
				'SELECT frmpost_name "n"
				FROM forumposts
				WHERE frmthr_id = ?
				ORDER BY frmpost_id DESC', $r->frmthr_id
			);
			$rl[$id] = array (
				'order'		=> $id,
				'info'		=> $types['frmthr'],
				'type'		=> 'frmthr',
				'link'		=> 'forum/thread/cat_id/'.$category.'/thr_id/'.$r->frmthr_id,
				'linkText'	=> $this->s->highlight($r->frmthr_name),
				'desc'		=> 'Ultimele postări: <strong>'.stripslashes($last3[0]['n']).
								($last3[1]['n']?', '.stripslashes($last3[1]['n']):'').
								($last3[2]['n']?', '.stripslashes($last3[2]['n']):'').'</strong>'
			); break;
			
			
			case 'frmcat':
			$desc = stripslashes($this->d->fetchOne(
				'SELECT frmcat_description
				FROM forumcategories
				WHERE frmcat_id = ?', $r->frmcat_id
			));
			$rl[$id] = array (
				'order'		=> $id,
				'info'		=> $types['frmcat'],
				'type'		=> 'frmcat',
				'link'		=> 'forum/categorie/id/'.$r->frmcat_id,
				'linkText'	=> $this->s->highlight($r->frmcat_name),
				'desc'		=> $this->s->highlight($desc)
			); break;
			
			case 'project':
			$desc = stripslashes($this->d->fetchOne(
				'SELECT project_description FROM projects
				WHERE project_id = ?', $r->project_id
			));
			$rl[$id] = array (
				'order'		=> $id,
				'info'		=> $types['project'],
				'type'		=> 'project',
				'link'		=> 'bid/show/project_id/'.$r->project_id,
				'linkText'	=> $this->s->highlight($r->project_name),
				'desc'		=> 'Categorie: <b>'.$this->s->highlight($r->categorie).'</b><br />'.
								($r->project_keywords != 'null'?'Keywords: '.
										$this->s->highlight($r->project_keywords).'<br />':'').
								($r->project_plang != 'null'?'Limbaj(e): '.
										$this->s->highlight($r->project_plang).'<br />':'').
								($r->project_licence != 'null'?'Licenta(e): '.
										$this->s->highlight($r->project_licence).'<br />':'').
								$this->s->highlight(substr($desc,0,80).(strlen($desc)>80?'...':''))
			); break;

			case 'app':
			$des = stripslashes($this->d->fetchOne(
				'SELECT app_description FROM applications
				WHERE app_id = ?', $r->app_id
			));
			$rl[$id] = array (
				'order'		=> $id,
				'info'		=> $types['app'],
				'type'		=> 'app',
				'link'		=> '/app/show/id/'.$r->app_id,
				'linkText'	=> $this->s->highlight($r->app_name),
				'desc'		=> ($r->app_keywords?'Keywords: '.
									$this->s->highlight($r->app_keywords).'<br />':'').
							   ($r->app_plang?'Limbaj(e): '.
									$this->s->highlight($r->app_plang).'<br />':'').
							    $this->s->highlight(substr($desc,0,80).(strlen($desc)>80?'...':''))
			);
			
		endswitch;
		return $rl;
	}
	
	function useri() {
			
			//daca nu am primit o pagina anume se duce la prima pagina
			if($_GET['pag'] == null) 
				$_GET['pag'] = 1;
			if($_GET['orderby'] == null)
				$_GET['orderby'] = 'username'; 
			
			//calculez cate pagini va avea afisarea
			$nr_pag = $this->d->fetchRow("SELECT COUNT(*) AS nr 
										FROM users 
										WHERE user_status = 0");
			
			$nr_pag = ceil($nr_pag['nr']/10);
			$this->p['nr_pag'] = $nr_pag;			
			
			//daca pagina e aiurea
			if($_GET['pag'] < 1 || $_GET['pag'] > $nr_pag){
				if($_GET['pag'] != 1)
					$this->p['pag_gresita'] = 'Pagina cautata nu exista.';
				else
					$this->p['no_users'] = 'Nu exista nici un user.';
			}
			else{
				$nrmin = ($_GET['pag']-1)*10;
				$nrmax = 10;
						
				if($_GET['orderby'] == 'nume')
					$rez = $this->d->fetchAll("SELECT user_id, user_name, user_first_name, user_last_name, user_rating
												FROM users
												WHERE user_status = 0
												ORDER BY user_last_name
												LIMIT ?,?",
												array($nrmin,$nrmax));
				else if($_GET['orderby'] == 'prenume')
					$rez = $this->d->fetchAll("SELECT user_id, user_name, user_first_name, user_last_name, user_rating
												FROM users
												WHERE user_status = 0
												ORDER BY user_first_name
												LIMIT ?,?",
												array($nrmin,$nrmax));
				else if($_GET['orderby'] == 'rating')
					$rez = $this->d->fetchAll("SELECT user_id, user_name, user_first_name, user_last_name, user_rating
												FROM users
												WHERE user_status = 0
												ORDER BY user_rating DESC
												LIMIT ?,?",
												array($nrmin,$nrmax));
				else 
					$rez = $this->d->fetchAll("SELECT user_id, user_name, user_first_name, user_last_name, user_rating
												FROM users
												WHERE user_status = 0
												ORDER BY user_name
												LIMIT ?,?",
												array($nrmin,$nrmax));
				
				$this->p['useri'] = array();
				
				foreach($rez as $c){
					$this->p['useri'][] = array(
						'username' => stripslashes($c['user_name']),
						'nume' => stripslashes($c['user_last_name']),
						'prenume' => stripslashes($c['user_first_name']),
						'rating' => $this->printRating($c['user_id'],'user') 
					);
				}
			}

	
	}
	
}
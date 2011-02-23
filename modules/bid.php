<?php
class Bid extends Controller
{
	var $selectMenu;
	
	function index()
	{
		$this->p['show_menu']=true;
	}
	
	private function catiParinti($id) { 
		$parentId = $this->d->fetchOne(
						'SELECT projcat_parent_id 
						FROM projectcategories
						WHERE projcat_id = ? ', $id
					);
		if ($parentId)
			return 1 + $this->catiParinti($parentId);
		return 0;
	} 
	
	function request()
	{		
		if(!$_SESSION['logat'])
		{
			$this->p['content']="Nu sunteti conectat. Intai <a href='auth/index'>logati-va</a>!";
			return;
		}
		$this->p[jsf][]="request.js";
		$this->select_cats();
		if(!$_POST) {
			$this->p['form']=true;
		}
		else
		{
			//Verify if project_name and project_description written corectly.
			
			$this->p['content']="Verificare...";
			
			if($_POST['project_name']=="")
			{
				$this->p['error']="N-ati completat numele proiectului!";
				$this->p['form']=true; return;
			}
			
			if($_POST['project_description']=="" or $_POST['project_description']=="Descriere proiect...")
			{
				$this->p['error']="N-ati completat descrierea proiectului!";
				$this->p['form']=true; return;
			}
			
			$row=$this->d->fetchRow("
			
				SELECT project_id
				FROM projects
				WHERE project_name=? AND project_status!=-1
			
			",$_POST['project_name']);
			
			if (!$row)
			{
				//Deal with credit:
				
				$query="
					SELECT user_credit
					FROM users
					WHERE user_id=?
				";
				$result=$this->d->fetchRow($query,$_SESSION['user_id']);
				
				if ($result['user_credit']<10)
				{
					$this->p['error']="Nu aveti destule credite in cont! Suma curenta: ".$result['user_credit'].
					" Suma necesara: 10";
					$this->p['form']=true; return;
				}
				
				$update=array("user_credit"=>$result['user_credit']-10);
				$this->d->update("users",$update,"user_id=".$_SESSION['user_id']);
				
				$insert=array(
								"project_id"=>null, "user_id_buyer"=>$_SESSION['user_id'],
								"project_name"=>$_POST['project_name'], "projcat_id" => $_POST['projcat_id'],
								"project_description"=>$_POST['project_description'],
								"user_id_preanalist"=>new Zend_Db_Expr('null'),
								"analysis_estimated_price"=>new Zend_Db_Expr('null'),
								"analysis_estimated_deadline"=>new Zend_Db_Expr('null'),
								"analysis_description"=>new Zend_Db_Expr('null'),
								"project_keywords"=>new Zend_Db_Expr('null'), "project_plang"=>new Zend_Db_Expr('null'),
								"project_licence"=>new Zend_Db_Expr('null'), "project_status"=>0, 
								"user_id_analyst"=>new Zend_Db_Expr('null'),
								"user_id_planner"=>new Zend_Db_Expr('null'), "user_id_programmer"=>new Zend_Db_Expr('null'), 
								"user_id_tester"=>new Zend_Db_Expr('null'), "project_start"=>new Zend_Db_Expr('now()'),
								"project_end"=>new Zend_Db_Expr('null'), "project_price"=>0
							);
				try{
				$this->d->insert("projects",$insert);
				}
				catch (Exception $e) {echo $e;}
				$insert['project_id'] = $this->d->lastInsertId();
				$insert['categorie'] = $this->d->fetchOne(
						'SELECT projcat_name
						FROM projectcategories
						WHERE projcat_id = ?', $_POST['projcat_id']
					);
				$this->s->addToIndex('project',$insert);
				$this->p['content']="Ati fost adaugat cu succes!";
			}
			else
			{
				$this->p['error']="Numele proiectului a fost deja folosit.";
				$this->p['form']=true; return;
			}
		}
	}
	
	function evalueaza()
	{
		if(!$_SESSION['logat'])
		{
			$this->p['show_error']=true;
			$this->p['error']="Trebuie sa fii conectat pentru a accesa lista de proiecte in decurs de pre-evaluare.";
			return;
		}
		if($_SESSION['usertype_id']!=2 and $_SESSION['usertype_id']!=6)
		{
			$this->p['show_error']=true;
			$this->p['error']="Doar analistii pot accesa lista de proiecte in decurs de pre-evaluare.";
			return;
		}
		
		//Unevaluated bid list:
		$this->p['analist']=true;
	
		$row=$this->d->fetchAll("
		
			SELECT p.project_id, p.project_name, p.project_description,
			p.project_requested, u.user_name
			FROM projects p JOIN users u ON (p.user_id_buyer=u.user_id)
			WHERE p.project_status=0
		
		");
		$this->p['bids']=$row;
	}
	
	function show()
	{
		try
		{
			//View bid:
			
			//Bind a calendar on the deadline input
			$this->p['js'] .= '
			$(document).ready( 
				function() {
					$("input[name=analysis_estimated_deadline]").datepicker({ minDate: 0 })
				})';
			
			
			$row=$this->d->fetchRow("
			
					SELECT p.project_id, p.project_name, p.project_description, p.project_requested,
					p.project_licence, p.project_plang, p.project_keywords, p.analysis_description,
					p.analysis_estimated_price, p.analysis_estimated_deadline,
					p.project_status, p.project_start, a.user_name 'preanalist', u.user_name, u.user_id
					FROM projects p LEFT JOIN users u ON (p.user_id_buyer=u.user_id)
					LEFT JOIN users a ON (p.user_id_preanalist=a.user_id)
					WHERE p.project_id=?
				
				",$_GET['project_id']);
	
			if(!$row)
			{
				$this->p['show_error']=true;
				$this->p['error']="Pagina cautata nu a putut fi gasita!";
				return;
			}
			elseif ($row['project_status']==-1)
			{
				$this->p['show_error']=true;
				$this->p['error']="Proiectul cautat a fost sters! (Contactati administratorul pentru nelamuriri.)";
				return;
			}
			else
				$this->p['bid']=$row;
			
			//Check if evaluation can be done:
			
			if ($this->p['bid']['project_status']==0)
			{
				if ($_SESSION['usertype_id']==2 or $_SESSION['usertype_id']==6)
					$this->p['eval']=true;
			}
				else
					$this->p['eval']=false;
			
			if($this->p['bid']['user_id']==$_SESSION['user_id'])
				$this->p['eval']=false;
			
			//Check if user can apply:
			
			if ($this->p['bid']['project_status']==10 
				|| $this->p['bid']['project_status'] <= -20)
			{
				$this->p['apply']=true;
				if ($_SESSION['logat']!=true)
				{
					$this->p['apply']=false;
					$this->p['content']="Nu puteti aplica la acesta licitatie pentru ca nu sunteti logat!";
				}
				if ($_SESSION['usertype_id']==1 or $_SESSION['usertype_id']==6)
				{
					$this->p['apply']=false;
					$this->p['content']="Nu puteti aplica la acesta licitatie pentru ca nu aveti drepturi";
				}
				if ($_SESSION['user_id']==$this->p['bid']['user_id'])
				{
					$this->p['apply']=false;
					$this->p['content']="Nu puteti aplica la aceasta licitatie ca proiectul este al dvs!";
				}
				
				
				$row=$this->d->fetchAll("
					SELECT bid_id
					FROM biddings
					WHERE project_id=? AND user_id=?
				",array($_GET['project_id'],$_SESSION['user_id']));
				
				if(count($row)!=0)
				{
					$this->p['apply']=false;
					$this->p['content']="Ati aplicat deja la acest proiect!";
				}
				
				//Show biddings:
				
				$this->p['view_biddings']=true;
				
				$row=$this->d->fetchAll("
					SELECT user_id, user_name, usertype_id, bid_id, bid_accepted, bid_price_offer
					FROM users JOIN biddings USING (user_id)
					WHERE project_id=?
				",$_GET['project_id']);
									
				if (count($row)==0)
				{
					$this->p['content']="Acest proiect inca n-are licitatii!";
					$this->p['view_biddings']=false;
				}
				else {
					$this->p['disables'] = 
						$this->d->fetchRow(
							'SELECT user_id_analyst "2", user_id_planner "3",
									user_id_programmer "4", user_id_tester "5"
							FROM projects
							WHERE project_id = ?', $_GET['project_id']
						);
					$this->p['biddings']=$row;
				}
				
				if ($this->p['bid']['user_id']==$_SESSION['user_id'])
				{
					$this->p['showStart']=true;
					
					for ($i=2; $i<=5; $i++)
					{
						$row=$this->d->fetchAll("
							SELECT user_id
							FROM users JOIN biddings USING (user_id)
							WHERE project_id=? AND bid_accepted=1 AND usertype_id=?
						",array($_GET['project_id'],$i));
					
						if(count($row)==0) $this->p['showStart']=false;
					}
				}
				
			}
			if(abs($this->p['bid']['project_status'])>=20)
			{
				$query="
					SELECT a.user_name analyst, a.user_id aID, 
						p.user_name planner, p.user_id pID,
						pr.user_name programmer, pr.user_id prID,
						t.user_name tester, t.user_id tID
					FROM projects prj LEFT JOIN users a ON (a.user_id = prj.user_id_analyst)
					LEFT JOIN users p ON (p.user_id = prj.user_id_planner)
					LEFT JOIN users pr ON (pr.user_id = prj.user_id_programmer)
					LEFT JOIN users t ON (t.user_id = prj.user_id_tester)
					WHERE prj.project_id = ?
				";
				
				$row=$this->d->fetchRow($query,$_GET['project_id']);
				$this->p['name']=$row;
				$this->p['rating']['analyst'] = $this->printRating($row['aID'],'user',$_GET['project_id']);
				$this->p['rating']['planner'] = $this->printRating($row['pID'],'user',$_GET['project_id']);
				$this->p['rating']['programmer'] = $this->printRating($row['prID'],'user',$_GET['project_id']);
				$this->p['rating']['tester'] = $this->printRating($row['tID'],'user',$_GET['project_id']);
				
				if ($_SESSION['usertype_id'] == 6) {
					if ($row['aID'])
					$this->p['dez']['analyst'] = '<a href="work/process/id/'.
						$_GET['project_id'].'/bailer_id/'.$row['aID'].'/bail">
						Scoate din proiect analistul</a>';
					if ($row['pID'])
					$this->p['dez']['planner'] = '<a href="work/process/id/'.
						$_GET['project_id'].'/bailer_id/'.$row['pID'].'/bail">
						Scoate din proiect proiectantul</a>';
					if ($row['prID'])
					$this->p['dez']['programmer'] = '<a href="work/process/id/'.
						$_GET['project_id'].'/bailer_id/'.$row['prID'].'/bail">
						Scoate din proiect programatorul</a>';
					if ($row['tID'])
					$this->p['dez']['tester'] = '<a href="work/process/id/'.
						$_GET['project_id'].'/bailer_id/'.$row['tID'].'/bail">
						Scoate din proiect testerul</a>';
				}
			}
						
			if ($this->p['bid']['user_id']==$_SESSION['user_id'] or $_SESSION['usertype_id']==6)
				if ($this->p['bid']['project_status']<20) $this->p['showStop']=true;
			
			//Check if post fileds written right:
			
			if ($_POST["bidSelect"]==true)
			{
				$this->p['show_page']=true;
				$this->p['eval']=false;
				$this->p['apply']=false;
				
				$update=array("bid_accepted"=>0);
				
				$disablesA = 
						$this->d->fetchRow(
							'SELECT COALESCE(user_id_analyst,0) "2", COALESCE(user_id_planner,0) "3",
									COALESCE(user_id_programmer,0) "4", COALESCE(user_id_tester,0) "5"
							FROM projects
							WHERE project_id = ?', $_GET['project_id']
						);
				$disables = implode($disablesA, ',');
				
				$this->d->update('biddings',$update,"project_id=".$_GET['project_id'].
									' AND user_id  NOT IN ('.$disables.')');
									

				for ($i=2; $i<=5; $i++)
					if ($_POST["select".$i]!="" && !$disablesA[$i])
					{
						$update=array("bid_accepted"=>1);
						$this->d->update('biddings',$update,"bid_id=".$_POST["select".$i]);
						$this->p['content']="Alegerile au fost salvate.";
					}
				redirect("bid/show/project_id/".$_GET['project_id']);
			}
			
			if (isset($_GET['admdel']))
			{
				// Admin deletes bidder from list
				if ($_SESSION['usertype_id']!=6)
					$this->p['error']="N-ai drepturile necesare sa modifici lista de licitatii.";
				//elseif ($this->p['bid']['project_status']!=10)
				//	$this->p['error']="Acest proiect nu are statutul de licitatie!";
				else
				{
					if ($this->p['bid']['project_status'] == 10) {
						// daca e licitatie
						$query='bid_id='.$_GET['admdel'];
						$this->d->delete('biddings',$query);
						if ($this->p['error']=="") 
							redirect("bid/show/project_id/".$_GET['project_id']);
					}
					else {
						// altfel facem redirect la modulul din work ce scoate developerul in curs
						// ... bineinteles daca developerul lucreaza acolo
						$bailer_id = $this->d->fetchOne(
								'SELECT user_id FROM biddings WHERE bid_id = ?', $_GET['admdel']
							);
						if (
							$this->d->fetchOne(
								'SELECT COUNT(project_id)
								FROM projects
								WHERE project_id = ? AND 
									(user_id_analyst = ?
									OR user_id_planner = ?
									OR user_id_programmer = ?
									OR user_id_tester = ?
									)', array ( $_GET['project_id'], $bailer_id, 
										$bailer_id, $bailer_id, $bailer_id ) 
							)
						) redirect('work/process/id/'.$_GET['project_id'].'/bailer_id/'.$bailer_id.'/bail');
						else 
							$this->p['error'] ="Nu puteti inlatura aceasta licitatie, proiectul deja a inceput!";
					}
				}
			}
			
			if ($_POST)
			{
				if($this->p['eval'])
				{
					//Evaluate project:
					if (strlen($_POST['project_licence'])<3)
						$this->p['error']="Licenta incorecta!";
				
					if($_POST['project_licence']=="" or $_POST['project_keywords']=="" /* or $_POST['project_rating']=="" */
						or $_POST['analysis_description']=="" or $_POST['analysis_estimated_deadline']=="" 
						or $_POST['analysis_estimated_price']=="" )
							$this->p['error']="Unul din campuri a ramas necompletat!";
					
					if(preg_match('/[^0-9]/',$_POST['analysis_estimated_price']))
						$this->p['error']="Pretul estimat trebuie scris in cifre!";
					
					/*
					if(preg_match('/[^0-9]/',$_POST['project_rating']))
						$this->p['error']="Ratingul trebuie scris in cifre!";
					
					if($_POST['project_rating']>10 or $_POST['project_rating']<1)
						$this->p['error']="Ratingul se da intre 1 si 10";*/
					
					if ($this->p['error']=="")
					{
						$update=array
						(
							"project_licence"=>$_POST[project_licence],
							"project_keywords"=>$_POST[project_keywords],
						/*	"project_rating"=>$_POST[project_rating], */
							"project_plang"=>$_POST[project_plang],
							"analysis_description"=>$_POST[analysis_description],
							"analysis_estimated_deadline"=>$_POST[analysis_estimated_deadline],
							"analysis_estimated_price"=>$_POST[analysis_estimated_price],
							"user_id_preanalist"=>$_SESSION[user_id],
							"project_status"=>10
						);
					
						$this->d->update('projects',$update,'project_id='.$_GET['project_id']);
						
						$update=$this->d->fetchRow("
							SELECT *
							FROM projects
							WHERE project_id = ?
						",$_GET['project_id']);
				
						$update['categorie'] = $this->d->fetchOne(
							'SELECT projcat_name
							FROM projectcategories
							WHERE projcat_id = ?', $update['projcat_id']
						);
						$this->s->updateIndex('project',$update);
					
						$this->p['eval']=false;
						$this->p['content']="Proiectul a fost evaluat.
						<a href='bid/show/project_id/".$_GET['project_id']."'>Refresh</a>!";
					}
					
					$query="
						SELECT user_credit
						FROM users
						WHERE user_id=?
					";
					$result=$this->d->fetchRow($query,$_SESSION['user_id']);
				
					$update=array("user_credit"=>$result['user_credit']+10);
					$this->d->update("users",$update,"user_id=".$_SESSION['user_id']);
					
					$_POST['project_status']=10;
				}
				elseif ($this->p['apply'])
				{
					//Apply to project
					if($_POST['apply']==true)
					{
						if($_POST['bid_price_offer']=="")
						{
							$this->p['error']="N-ati completat campul cu suma dorita!";
							$this->p['show_page']=true; return;
						}
						if(preg_match('/[^0-9]/',$_POST['bid_price_offer']))
						{
							$this->p['error']="Suma introdusa contine si litere!";
							$this->p['show_page']=true; return;
						}
						if($_POST['bid_price_offer']<=0)
						{
							$this->p['error']="Suma introdusa este negativa!";
							$this->p['show_page']=true; return;
						}
						
						$insert=array
						(
							"bid_price_offer"=>$_POST['bid_price_offer'],
							"project_id"=>$_GET['project_id'],
							"user_id"=>$_SESSION['user_id']
						);
						
						$this->d->insert('biddings',$insert);
						$this->p['content']="Ati fost aplicat la aceasta licitatie cu suma:".$_POST['bid_price_offer'];
						if ($this->p['error']=="") redirect("bid/show/project_id/".$_GET['project_id']);
					}
					else
						$this->p['error']="Trebuie sa bifati casuta de mai sus pentru a aplica!";
				}
				
				if ($_POST["quitBid"]==true)
				{
					$prjI = 
						$this->d->fetchRow('
							SELECT project_status, user_id_analyst "id1", 
								user_id_planner "id2", user_id_programmer "id3", user_id_tester "id4"
							FROM projects WHERE project_id = ?', 
							$_GET['project_id']);
					if ($prjStatus <= -20 && in_array($_SESSION['user_id'], $prjI)) :
					
					redirect('work/process/id/'.$_GET['project_id'].'/bail');
					
					else :
				
					//Developer quits
					$query='user_id='.$_SESSION['user_id'].' AND project_id='.$_GET['project_id'];
					$this->d->delete('biddings',$query);
					
					$this->p['content']="Ai renuntat la acest proiect si nu vei mai participa la licitatie.";
					if ($this->p['error']=="") redirect("bid/show/project_id/".$_GET['project_id']);
					
					endif;
				}
				
				if ($_POST["action"]=="stop")
				{
					//Client quits
					$update=array("project_status"=>-1);
					
					$this->d->update('projects',$update,'project_id='.$_GET['project_id']);
					$this->s->deleteFromIndex('project',$_GET['project_id']);
						
					$this->p['error']="Proiectul a fost sters!";
					$this->p['show_error']=true;
					return;
				}
				
				if ($_POST["action"]=="start")
				{
					//Client accepts
					
					for ($i=2; $i<=5; $i++)
					{
						$query="
							SELECT user_id
							FROM users JOIN biddings USING (user_id)
							WHERE usertype_id=? AND project_id=? AND bid_accepted=1
						";
						
						$row[$i-2]=$this->d->fetchRow($query,array($i,$_GET['project_id']));
					}
					
					$s = $this->d->fetchOne(
						'SELECT SUM(bid_price_offer) + 10
						FROM biddings
						WHERE project_id = ? AND bid_accepted = 1', $_GET['project_id']
					);
					
					$query="
						SELECT user_credit
						FROM users
						WHERE user_id=?
					";
					
					$result=$this->d->fetchRow($query,$_SESSION['user_id']);
					
					$oldInfo = $this->d->fetchRow(
						'SELECT project_price, project_status
						FROM projects
						WHERE project_id = ?', $_GET['project_id']
					);
					
					if($result['user_credit']<$s-$oldInfo['project_price']-10)
					{
						$this->p['error']="N-ai destui bani in cont! Mai cumpără credite! (Necesar: ".
						($s-10)." In cont: ".$result['user_credit'].")";
						$this->p['show_page']=true; return;
					}
					
					$update=array(
						'project_status' => 20, "user_id_analyst"=>$row[0]['user_id'],
						"user_id_planner"=>$row[1]['user_id'], "user_id_programmer"=>$row[2]['user_id'],
						"user_id_tester"=>$row[3]['user_id'], "project_start"=>new Zend_Db_Expr('SYSDATE()'),
						"project_price"=>$s
					);
					if ($oldInfo['project_status'] <= -20) {
						$update['project_status'] = -$oldInfo['project_status'];
						unset($update['project_start']);
					}
					
					$this->d->update('projects',$update,'project_id='.$_GET['project_id']);
					
					/*$update=$this->d->fetchRow("
							SELECT *
							FROM projects
							WHERE project_id = ?
						",$_GET['project_id']);
					
					$this->s->updateIndex('project',$update);*/
					
					$upd=array("user_credit"=>$result['user_credit']-$s+$oldInfo['project_price']);
					$this->d->update('users',$upd,'user_id='.$_SESSION['user_id']);
					
					redirect("bid/show/project_id/".$_GET['project_id']);
				}

			}
			
			$this->p['show_page']=true;
		} catch (Exception $e) {
			exit($e->getMessage());
		}
	}
	
	function myproj()
	{
		if(!$_SESSION['logat'])
		{
			$this->p['error']="Trebuie sa fii logat pentru a putea accesa lista de proiecte personale";
			return;
		}
		$row=$this->d->fetchall("
			SELECT project_id, project_name, project_requested, project_status
			FROM projects
			WHERE user_id_buyer=? AND project_status!=-1
		",$_SESSION['user_id']);
		
		if (count($row)==0) $this->p['error']="Niciun proiect gasit!";
		$this->p['project']=$row;
	}
	
	function mywork()
	{
		if(!$_SESSION['logat'])
		{
			$this->p['error']="Trebuie sa fii logat pentru a putea accesa lista de proiecte in lucru";
			return;
		}
		
		//Search for biddings
		
		$query="
			SELECT project_id, project_name, project_requested
			FROM projects JOIN biddings USING (project_id)
			WHERE user_id=? AND project_status<20
		";
		$row=$this->d->fetchAll($query,$_SESSION['user_id']);
		$this->p['onbid']=$row;
		
		//Search for projects
		
		$query="
			SELECT project_id, project_name, project_requested
			FROM projects
			WHERE user_id_analyst=? OR user_id_planner=?
			OR user_id_programmer=? OR user_id_tester=?
			AND project_status<100
		";
		
		$row=$this->d->fetchAll($query,array($_SESSION['user_id'], $_SESSION['user_id'],
		$_SESSION['user_id'], $_SESSION['user_id']));
		$this->p['ongoing']=$row;
	}
	
	function available()
	{
		$query = "
			SELECT project_id, project_name, project_requested, user_id, user_name
			FROM projects p JOIN users u ON p.user_id_buyer=u.user_id
			WHERE (project_status = 10 OR project_status <= -20 ) AND user_id != ?
			ORDER BY project_requested DESC;
		";
		
		try {$result = $this->d->fetchAll($query,$_SESSION['user_id']);}
		catch (Exception $e)
		{
			$this->p['error']="Eroare baza de date: ".$e;
			return;
		}
		
		$this->p['lista']=$result;		
		
	}
}
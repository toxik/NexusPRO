<?php
class Search {
	const 		indexPath = 'search_index';
	static		$searchTime;
	static		$highlight;
	private 	$index;
	private		$delIndex;
	public		$types;
	
	function __construct() {
		// analizorul meu custom
		require 'inc/clase/tXK-goodies.php';
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(
			new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8NumComplex_CaseInsensitive()
		);
		// scoatem limita de prefixare
		Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(1);
		Zend_Search_Lucene_Search_Query_Fuzzy::setDefaultPrefixLength(3);
		
		try {
			$this->index 	= new Zend_Search_Lucene(self::indexPath);
			$this->delIndex = new Zend_Search_Lucene(self::indexPath);
		} catch (Zend_Search_Lucene_Exception $e) {
			// daca indexul nu exista, il cream
			if ($e->getCode() == 0) {
				$this->index = new Zend_Search_Lucene(self::indexPath, true); 
				$this->delIndex = new Zend_Search_Lucene(self::indexPath);
			}
			else exit('Eroare la initializarea motorului de cautare!');
		}
		
		self::$highlight = new tXK_Highlight;
		
		// define what we index
		$this->types = array(
			'user' 			=> array(
									'type'				   => 'Keyword',
									'user_id' 			   => 'Keyword',
									'user_name' 		   => 'Text',
									'user_first_name'	   => 'Text',
									'user_last_name'	   => 'Text'
								),
			'frmpost'		=> array(
									'type'				   => 'Keyword',
									'frmpost_id'		   => 'Keyword',
									'frmpost_name'		   => 'Text',
									'frmpost_content'	   => 'UnStored'
								),
			'frmthr' 		=> array(
									'type'				   => 'Keyword',
									'frmthr_id'			   => 'Keyword',
									'frmthr_name'		   => 'Text',
									'frmthr_description'   => 'UnStored'
								),
			'frmcat' 		=> array(
									'type'				   => 'Keyword',
									'frmcat_id'		 	   => 'Keyword',
									'frmcat_name'		   => 'Text',
									'frmcat_description'   => 'UnStored'
								),
			'project' 		=> array(
									'type'				   => 'Keyword',
									'project_id'		   => 'Keyword',
									'categorie'			   => 'Text',
									'project_name'		   => 'Text',
									'project_description'  => 'UnStored',
									'analysis_description' => 'UnStored',
									'project_keywords'	   => 'Text',
									'project_plang'		   => 'Text',
									'project_licence'	   => 'Text',
									'project_price'		   => 'Keyword'
								),
			'app'			=> array(
									'type'				   => 'Keyword',
									'app_id'			   => 'Keyword',
									'app_name'			   => 'Text',
									'app_description'	   => 'UnStored',
									'app_keywords'		   => 'Text',
									'app_plang'			   => 'Text'
								)
		);
	}
	
	function __destruct() {
		$this->optimize();
	}
	
	function addToIndex($type, $data, $optimize = true) {
		// construim item-ul de introdus in search index
		$doc = new Zend_Search_Lucene_Document();
		// retinem tipul de document indexat
		$data['type'] = $type;
		
		foreach ($this->types[$type] as $id => $tip)
			$doc->addField(
				Zend_Search_Lucene_Field::$tip( $id, $data[$id] )
			);
		
		// introducem efectiv si salvam modificarile
		try {
			$this->index->addDocument($doc);
			$this->index->commit();
		if ($optimize)
			$this->optimize();
		} catch (Zend_Exception $e) {
			exit('Eroare:'.$e->getMessage());
		}
	}
	
	function updateIndex($type, $data, $optimize = true) {
		// nu avem update, facem delete apoi reinsert
		try {
			if ($this->deleteFromIndex($type,$data[$type.'_id'],false))
				$this->addToIndex($type, $data, $optimize, true);
			else return false;
		} catch (Zend_Exception $e) {
			return false;
		}
	}
	
	function deleteFromIndex($type, $id, $optimize = true, $update = false) {
		$booboo = false;
		try {
			//$index = new Zend_Search_Lucene(self::indexPath);
			$ceSterg = $this->delIndex->find(
				'type: ' . $type. ' AND '.
				$type . '_id: ' . $id
			);
			if ($ceSterg) {
				foreach ($ceSterg as $item)
				try {
					$this->delIndex->delete($item->id);
				} catch(Exception $e) {
					// hai nu`i nimic..
					$booboo = true;
				}
				if (!$update)
				$this->delIndex->commit();
				if ($optimize)
					$this->delIndex->optimize();
			}
		} catch (Exception $e) { return false; }
		return !$booboo;
	}
	
	function find($query) {
		self::$searchTime = microtime(true);
		$this->queryP = Zend_Search_Lucene_Search_QueryParser::parse($query);
		try { 
			$hits = $this->index->find($this->queryP);
			self::$searchTime = round(microtime(true) - self::$searchTime, 5);
		} catch (Zend_Exception $e) {
			// nasol moment.
			self::$searchTime = round(microtime(true) - self::$searchTime, 5);
			return null;
		}
		if (count($hits))
			return $hits;
		/*
		// daca nu avem rezultat bagam un fuzzy.. la care mai e de lucrat
		for ($i = 5; $i > 0; $i--)
		try {
			$hits = $this->index->find('('.$query.')~0.'.$i);
			
		} catch (Zend_Exception $e) {
			self::$searchTime = round(microtime(true) - self::$searchTime, 5);
			return array('eroare'=>'Nasol moment. Cautarea a facut fail :(');
		}
		*/
	}
	
	function optimize() {
		$this->index->optimize();
		$this->index->commit();
	}
	
	function highlight($s) {
		return $this->queryP->htmlFragmentHighlightMatches($s, 'UTF-8', self::$highlight);
	}
	
}
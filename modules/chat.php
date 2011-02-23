<?php
class Chat extends Controller {
	function index() {
		$this->p['single'] = true;
		
		if (!$_SESSION['logat']) exit('<center>
			<h1 style="margin-top:250px">Fuck off. Please.</h1><br />(or you could login)</center>
			<script type="text/javascript">
				setTimeout("self.close ()",3000)
			</script>');
		
		// clear the chatters that had timeout
			$this->d->delete('chatters', 'lastPing +  sec_to_time('.(CHAT_TIMEOUT).') < NOW()');
		
		// verify duplicate
		$checkUser = $this->d->fetchRow('SELECT * FROM 
			chatters WHERE user_name = ?', $this->p['user_name']);
		if ($checkUser)
			exit('<center>
			<h4 style="margin-top:250px">Măi omule.<br />
				Păi cum pana lu` corbu` vrei tu să intri de 2 ori în chat? <br />
				Ești mai cu 3 coaie ?</h4></center>
			<script type="text/javascript">
				setTimeout("self.close ()",10000)
			</script>');
		
	}
	
	function update() {
		$this->p['standalone'] = true;
		
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="update.json"');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		
		// silent fail if he's not logged in
		if (!isset($_SESSION['user_id'])) {
			//header('HTTP/1.1 403 Forbidden');
			echo json_encode(array("Logged out."));
			exit();
		}
		
		$response = array();
		
		// wanna get out ?
		if (isset($_GET['getOut'])) {
			$this->d->delete('chatters', "user_name = '".$this->p['user_name']."'");
			header('HTTP/1.1 204 No Content');
			exit();
		}
		
		// user actions
		try { // add it to the chatters's table
			$this->d->insert('chatters', array(
				'user_name' => $this->p['user_name'],
				'lastPing'	=> null
			));
		} catch (Zend_Exception $e) {
			try { // ok, era deja online. sa-i modificam timestampul
			$this->d->update('chatters', array('lastPing' => null), "user_name = '". $this->p['user_name'] ."'" );
			} catch (Zend_Exception $e) {
				echo $e->getMessage();
			}
		}
		// if the user has input..
		if ($_POST) {
			// verifica faptul ca receiverul exista
			if ($this->d->fetchOne( 'SELECT user_id FROM users WHERE user_name = ?', $_POST['receiver'] )
				 || $_POST['receiver'] == 'all')
				// daca da, adaugam replica in baza de date
				$this->d->insert('chats', array(
					'sender' 	=> $this->p['user_name'],
					'receiver'	=> $_POST['receiver'],
					'message'	=>  strip_tags($_POST['message'])
				));
			header('HTTP/1.1 204 No Content');
			exit();
		}
		
		// cleanup
			// clear the chatters that had timeout
			$this->d->delete('chatters', 'lastPing +  sec_to_time('.(CHAT_TIMEOUT).') < NOW()');
			// clear old main chat messages
			$this->d->delete('chats', "timestamp + 3600 < NOW() AND receiver = 'all'");
		
		// build the chatters array
		$response['chatters'] = $this->d->fetchAll('SELECT user_name as "0" FROM chatters ORDER BY user_name');
		
		// build the messages array and delete received ones
		$response['messages'] = $this->d->fetchAll('
			SELECT * FROM chats
			WHERE ((receiver = ? AND timestamp > ?) OR receiver = ?)
			ORDER BY timestamp, receiver, sender', array('all', $_GET['timestamp'], $this->p['user_name']) );
		$this->d->delete('chats', "receiver = '".$this->p['user_name']."'");
		foreach ($response['messages'] as &$message) {
			$message["message"] = stripslashes($message["message"]);
		}
		
		echo substr($_GET['callback'],1).'('.json_encode($response).')';
		exit();
	}
}
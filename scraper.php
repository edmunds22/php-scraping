<?php

	ini_set ( 'max_execution_time' , 600); 
	function selectQuery($db, $sql='', $args=[]){
	
		$sth = $db->prepare($sql);
		foreach($args as $arg => $val){
			$sth->bindValue($arg, $val);
		}
		
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	
	}
 


	//https://gist.github.com/SeanCannon/6585889
	function array_flatten($array = null) { $result = array(); if (!is_array($array)) { $array = func_get_args(); } foreach ($array as $key => $value) { if (is_array($value)) { $result = array_merge($result, array_flatten($value)); } else { $result = array_merge($result, array($key => $value)); } } return $result; }

 	error_reporting(E_ALL); ini_set('display_errors', 1);

	include("simple_html_dom.php");

		try {
		    $db = new PDO("mysql:host=localhost;dbname=scraping", 'root', '');
		    // set the PDO error mode to exception
		    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}catch(PDOException $e){
		    echo "Connection failed: " . $e->getMessage();
			die();
		}


	$pages_to_fetch = 45;//length
	$query = 'my+search+query';
	$matches = [];

	$idx=0;
	while($idx < $pages_to_fetch){

		$useragent = "Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.14912/870; U; id) Presto/2.4.15";
		$ch = curl_init ("");
		
		curl_setopt ($ch, CURLOPT_URL, "http://www.google.com/search?hl=en&tbo=d&site=&source=hp&start=".($idx * 10)."&q=".$query);
		curl_setopt ($ch, CURLOPT_USERAGENT, $useragent); // set user agent
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 7); //timeout in seconds
		$htmdata = curl_exec($ch);
		curl_close($ch);
	  	//echo var_dump($htmdata);die();

		$urls = [];

	  	preg_match_all("/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/", $htmdata, $urls);

	  	$urls = array_merge($urls[0], $urls[1], $urls[2]);

	  	foreach($urls as $url){
	  		if(substr($url, 0, 3) == 'www'){

	  			$existsInDb = selectQuery($db, "SELECT * FROM sites WHERE url = '".$url."'", $args=[]);

	  			if(count($existsInDb) < 1){
	  				$matches[] = $url;
	  			}
	  			
	  		}
	  	}
		$matches = array_unique($matches);	
		echo 'page '.$idx.' complete<br />';
		$idx++;
	}
	print_r($matches);

	foreach($matches as $match){
		selectQuery($db, 'INSERT INTO sites (`url`) VALUES (:match)', [
									':match' => $match
								]);
	}
	echo 'google scraping done<br />';

	//get the existing url's - try to get the business info

	$sites = selectQuery($db, 'SELECT * FROM sites where used = 0 AND title is null and email is null', [
									
								]);

	foreach($sites as $website){

		//request site
		$useragent = "Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.14912/870; U; id) Presto/2.4.15";
		$ch = curl_init ("");
		echo 'processing: '.$website['url'].'<br />';
		curl_setopt ($ch, CURLOPT_URL, $website['url']);//$website['url']
		curl_setopt ($ch, CURLOPT_USERAGENT, $useragent); // set user agent
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 7); //timeout in seconds
		$htmdata = curl_exec($ch);
		curl_close($ch);

		$dom = str_get_html($htmdata);
		if($dom){
			$title = $dom->find('title', 0);
			$title = $title->plaintext;

			//get emails
			$emails = [];

		  	preg_match_all("/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i", $htmdata, $emails);

		  	$emails = array_flatten($emails);

		  	if(count($emails) > 0){

		  		foreach($emails as $email){

					if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array(substr($email, -3), ['png', 'jpg', 'ets', 'PNG', 'JPG', 'PEG'])) {
				  		$args = [
										':title' => $title,
										':email' => $email,
										':url' => $website['url']
									];

								
						$sites = selectQuery($db, 'UPDATE sites SET title = :title, email = :email WHERE url = :url', $args);
						
					}

		  		}


		


		  	}
		  	
		  	$sites = selectQuery($db, 'UPDATE sites SET used = 1 WHERE url = :url', [':url' => $website['url']]);
		  	
		}


	}





	print_r('business details fetched');die();


  
  
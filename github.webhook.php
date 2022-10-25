<?php
	include './inputchk.php';
	include './@.setting.php';
	
	//깃허브에서 RAW파일 가져오기
	function gitcontents(String $path){
		if($path === null){
			return false;
		}
		$repositories_content_api = __GITHUB_API__ .'/repos/'. __GITHUB_OWNER__ .'/'. __GITHUB_REPO__ .'/contents/'.$path;
		$header = array(
			'Accept: application/vnd.github+json',
			'Authorization: Bearer '.__GITHUB_TOKEN__
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $repositories_content_api);
		curl_setopt($ch, CURLOPT_USERAGENT, 'dadolcorp');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($response, true);
		if($response === false){
			return false;
		}
		if(isset($response['download_url'])){
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $response['download_url']);
			curl_setopt($ch, CURLOPT_USERAGENT, 'dadolcorp');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
			
		}else{
			return false;
		}
		
	}
	
	//path의 디렉토리 경로내 디렉토리가 여부를 확인하고 디렉토리를 자동생성
	function dir_create(String $path){
		if($path === null){
			return false;
		}
		$dir_arr = explode('/', $path);
		array_pop($dir_arr);
		$dir_depth_cnt = count($dir_arr);
		for($i=0; $i<$dir_depth_cnt; $i++){
			if(!is_dir($dir_arr[$i])){
				if(!mkdir($dir_arr[$i], '777')){
					return false;
				}
			}
			if($dir_depth_cnt > $i+1){
				$dir_arr[$i+1] = $dir_arr[$i].'/'.$dir_arr[$i+1];
			}
		}
		return true;
	}
	
	//파일추가 혹은 수정
	function modify(String $path){
		if($path === null){
			return false;
		}
		$raw = gitcontents($path);
		if($raw === false){
			return false;
		}
		if(dir_create($path) === false){
			return false;
		}
		if(file_put_contents($path, $raw)){
			return true;
		}else{
			return false;
		}
	}
	
	function remove(String $path){
		if($path === null){
			return false;
		}
		$dir_arr = explode('/', $path);
		$file = array_pop($dir_arr);
		$dir_depth_cnt = count($dir_arr);
		for($i=0; $i<$dir_depth_cnt; $i++){
			if(!is_dir($dir_arr[$i])){
				continue;
			}
			if($dir_depth_cnt > $i+1){
				$dir_arr[$i+1] = $dir_arr[$i].'/'.$dir_arr[$i+1];
			}
		}
		if(!is_file($path)){
			return false;
		}
		if(!unlink($path)){
			return false;
		}
		for($i=$dir_depth_cnt-1; $i>=0; $i--){
			if(!is_dir($dir_arr[$i])){
				continue;
			}
			$thisdir = opendir($dir_arr[$i]);
			$chk_cnt = 0;
			while($anotherfile = readdir($thisdir)){
				if($anotherfile === '.'|| $anotherfile === '..') continue;
				$getExt = pathinfo($anotherfile, PATHINFO_EXTENSION);
				if(empty($getExt)){
					$chk_cnt++;
				}
			}
			if($chk_cnt === 0){
				if(!rmdir($dir_arr[$i])){
					return false;
				}
			}
		}
		return true;
	}

	//깃허브 웹훅 받기
	$_POST = file_get_contents('php://input');
	$_POST_origin = $_POST;
	$_POST = json_decode($_POST, true);
	$_HEAD = getallheaders(); //헤더받기
	
	//깃허브 webhook secret 키 겁증
	if(empty($_HEAD['X-Hub-Signature-256'])){
		exit();
	}else{
		$signature = inputchk($_HEAD['X-Hub-Signature-256']);
		$signature = explode('=',$signature);
		if(count($signature) !== 2){
			exit();
		}else{
			$signature = $signature[1];
			$signature_chk = hash_hmac('sha256', $_POST_origin, __GITHUB_WH_SECRET__);
			if($signature !== $signature_chk){
				exit();
			}
		}
	}
	
	//추가, 삭제, 수정된 파일 자료구조화
	$added = array();
	$removed = array();
	$modified = array();
	if(isset($_POST['commits'])){
		$commits = inputchk($_POST['commits']);
		$commits_cnt = count($commits);
		for($c=$commits_cnt-1; $c>=0; $c--){
			if(count($commits[$c]['added']) > 0){
				$added = array_merge($added, $commits[$c]['added']);
			}
			if(count($commits[$c]['removed']) > 0){
				$removed = array_merge($removed, $commits[$c]['removed']);
			}
			if(count($commits[$c]['modified']) > 0){
				$modified = array_merge($modified, $commits[$c]['modified']);
			}
		}
	}
	$added_cnt = count($added);
	$modified_cnt = count($modified);
	$removed_cnt = count($removed);
	
	//추가
	for($i=$added_cnt-1; $i>=0; $i--){
		modify($added[$i]);
	}
	
	//수정
	for($i=$modified_cnt-1; $i>=0; $i--){
		modify($modified[$i]);
	}
	
	//삭제
	for($i=$removed_cnt-1; $i>=0; $i--){
		remove($removed[$i]);
	}
	
	
	
?>
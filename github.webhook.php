<?php
	ini_set('memory_limit','2048M');
	ini_set('max_execution_time', 3600);
	include './inputchk.php';
	include './post_multi.php';
	include './@.setting.php';
	
	$rate_url = __GITHUB_API__ . '/rate_limit';
	$header = array(
		'Accept: application/vnd.github+json',
		'Authorization: Bearer '.__GITHUB_TOKEN__
	);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $rate_url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'dadolcorp');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	$response = curl_exec($ch);
	curl_close($ch);
	$response = json_decode($response, true);
	if(isset($response['rate']['remaining'])){
		echo substr(__GITHUB_TOKEN__,0 , 10).' 잔여호출량 : '.$response['rate']['remaining'].PHP_EOL;
	}
	
	
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
		if(isset($response['content'])){
			return base64_decode($response['content']);
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

	//파일추가
	function add(array $patharr){
		$data = array();
		$i=0;
		foreach($patharr as $path){
			if($path === null){
				return false;
			}
			$data[$i]['url'] = __GITHUB_API__ .'/repos/'. __GITHUB_OWNER__ .'/'. __GITHUB_REPO__ .'/contents/'.$path;
			$data[$i]['header'] = array(
				'Accept: application/vnd.github+json',
				'Authorization: Bearer '.__GITHUB_TOKEN__
			);
			$data[$i]['useragent'] = 'dadolcorp';
			$i++;
		}
		
		$res = post_multi($data);
		
		$data = array();
		$i=0;
		foreach($res as $response){
			$response = json_decode($response, true);
			if($response === false){
				return false;
			}
			if(isset($response['content']) && $response['path']){
				if(dir_create($response['path']) === false){
					echo '디렉토리생성실패:'.$response['path'];
				}
				if(file_put_contents($response['path'], base64_decode($response['content']))){
					echo $response['path'] . '추가성공'.PHP_EOL;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		
	}
	
	//파일수정
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
			return $path . '수정성공'.PHP_EOL;
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
		return $path . '삭제성공'.PHP_EOL;
	}
	
	
	
	////////////여기서부터 실행로직////////////

	//깃허브 웹훅 받기
	$_POST = file_get_contents('php://input');
	$_POST_origin = $_POST;
	$_POST = json_decode($_POST, true);
	$_HEAD = getallheaders(); //헤더받기
	
	//깃허브 webhook secret 키 겁증
	if(empty($_HEAD['X-Hub-Signature-256'])){
		echo "잘못된 요청입니다.(nosecret1)".PHP_EOL;
		exit();
	}else{
		$signature = inputchk($_HEAD['X-Hub-Signature-256']);
		$signature = explode('=',$signature);
		if(count($signature) !== 2){
			echo "잘못된 요청입니다.(nosecret2)".PHP_EOL;
			exit();
		}else{
			$signature = $signature[1];
			$signature_chk = hash_hmac('sha256', $_POST_origin, __GITHUB_WH_SECRET__);
			if($signature !== $signature_chk){
				echo "잘못된 요청입니다.(secreterror)".PHP_EOL;
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
	echo '신규추가:';
	var_dump($added);
	
	//추가
	if($added_cnt > 0){
		echo add($added);
	}
	
	echo PHP_EOL.PHP_EOL.'수정:';
	var_dump($modified);
	//수정
	for($i=$modified_cnt-1; $i>=0; $i--){
		echo modify($modified[$i]);
	}
	
	echo PHP_EOL.PHP_EOL.'삭제:';
	var_dump($removed);
	//삭제
	for($i=$removed_cnt-1; $i>=0; $i--){
		echo remove($removed[$i]);
	}
	
	exit();
	
?>
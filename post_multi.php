<?php
	/*
	//동시 처리시킬 URL
	$data = array();
	$data[0]['url'] = 'https://null';
	 $data[0]['fields'] = array('test'=>'1');
	 $data[0]['header'] = array('Content-Type: application/json');
	 $data[1]['url'] = 'https://www.dadolcorp.com';
	 $data[1]['fields'] = array('test'=>'1');
	 $data[1]['header'] = array('Content-Type: application/json');
	$data[2]['url'] = 'https://www.hashfactory.biz';
	 $data[2]['fields'] = array('test'=>'1');
	 $data[2]['header'] = array('Content-Type: application/json');
	$data[2]['useragent'] = 'dadolcorp';

	//실행
	var_dump($data);
	$res = post_multi($data);
   
	//결과 출력
	echo '실행 결과:<pre>';
	print_r($res);
	echo '</pre>';
	*/
	function post_multi(array $data, $timeout=null){
		
		$mh = curl_multi_init();
		foreach($data as $i => $arr){
			
			//api 호출 횟수 저장
			if(function_exists('query_select') && function_exists('query_update') && function_exists('query_insert')){
				if(preg_match('/^(https?:\/\/)?([^\/]+)/i', $arr['url'], $api_name)){
					unset($db);
					gc_collect_cycles();
					$db['and']['hostname'] = $api_name[count($api_name)-1];
					$db['and']['date'] = Date('Ymd');
					$db['output'][] = 'hits';
					$db['output'][] = 'sid';
					$api_db = query_select('apihits', $db, 1, 1);
					if($api_db['output_cnt'] === 0){
						//첫 데이터 (삽입)
						unset($db);
						$db['hostname'] = $api_name[count($api_name)-1];
						$db['hits'] = 1;
						$db['date'] = Date('Ymd');
						if(!query_insert('apihits', $db)){
							nowexit(false, '해당 api가 사용량에 반영되지 못했습니다.');
						}
					}else{
						//데이터 존재 (업데이트)
						unset($db);
						$db['hits'] = (int)$api_db[0]['hits']+1;
						if(!query_update($api_db[0]['sid'], $db)){
							nowexit(false, '해당 api가 사용량에 반영되지 못했습니다.');
						}
					}
				}
			}
			
			$conn[$i] = curl_init($arr['url']);
			curl_setopt($conn[$i],CURLOPT_RETURNTRANSFER, true);
			curl_setopt($conn[$i],CURLOPT_FAILONERROR, true);
			curl_setopt($conn[$i],CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($conn[$i],CURLOPT_MAXREDIRS, 3);
			
			if(isset($arr['fields'])){
				if($arr['fields'] !== null && $arr['fields'] !== '' && is_array($arr['fields'])){
					//array 형태의 post 전송
					$post_field_string = http_build_query($arr['fields'], '', '&');
				}else{
					if(!is_array($arr['fields']) && $arr['fields'] !== ''){
						//json형태의 post 전송
						$post_field_string = $arr['fields'];
					}else{
						//post 전송안함
						$post_field_string = null;
					}

				}
				if($post_field_string !== null){
					//post전송여부
					curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $post_field_string);
				}
			}
			if(isset($arr['header'])){
				if(is_array($arr['header']) || $arr['header'] !== ''){
					//헤더전송여부
					curl_setopt($conn[$i], CURLOPT_HTTPHEADER, $arr['header']);
				}
			}
			if(isset($arr['useragent'])){
				curl_setopt($conn[$i], CURLOPT_USERAGENT, $arr['useragent']);
			}
			//SSL증명서 무시
			curl_setopt($conn[$i],CURLOPT_SSL_VERIFYPEER,false);
			curl_setopt($conn[$i],CURLOPT_SSL_VERIFYHOST,false);

			//타임아웃
			if($timeout){
				curl_setopt($conn[$i],CURLOPT_TIMEOUT,$timeout);
			}
			curl_multi_add_handle($mh,$conn[$i]);
		}
		
		
		
		$active = null;
		do {
			$mrc = curl_multi_exec($mh,$active);
		}while($mrc === CURLM_CALL_MULTI_PERFORM);

		while($active and $mrc === CURLM_OK){
			if(curl_multi_select($mh) !== -1){
				do{
					$mrc = curl_multi_exec($mh,$active);
				} while($mrc === CURLM_CALL_MULTI_PERFORM);
			}
		}

		if($mrc !== CURLM_OK){
			return false;
		}

		//결과 취득
		$res = array();
		foreach($data as $i => $arr){
			if(($err = curl_error($conn[$i])) === '') {
				$res[$i] = curl_multi_getcontent($conn[$i]);
			}else{
				return false;
			}
			curl_multi_remove_handle($mh,$conn[$i]);
			curl_close($conn[$i]);
		}
		curl_multi_close($mh);

		return $res;
	}
?>
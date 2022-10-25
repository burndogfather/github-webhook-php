<?php
	function inputchk($input){
		if(is_array($input)){
			//배열 일 경우
			$out_tmp = array();
			foreach($input AS $key => $value){
				$out_tmp[$key] = inputchk($value);
			}
			return $out_tmp;
		}else{
			$out_tmp = '';
			//문자열 일 경우
			$anti_injection_arr = array("\x00","\x1a","\<\?", '\?\>',"\<script","\\\\", '"',"'", ";","<",">","--");
			$out_tmp = str_replace($anti_injection_arr, "", addslashes(strip_tags(htmlspecialchars($input))));
			if(is_int($input)){
				return (int)stripslashes($out_tmp);
			}else{
				return $out_tmp;
			}
		}
		
	}
?>
<?php
class Zenfolio {
	private static $url = 'api.zenfolio.com/api/1.8/zfapi.asmx';
	private static $userAgent = 'ZenfolioPress v016';
	var $token = null;
	var $loginName = null;
	var $cacheSeconds = 3600;

	function __construct () {
		/* removed caching for now */
	}

	private function call($method,$params,$try = false,$secure = false) {
		if(is_array($params)) {
			$params = json_encode($params);
		}
		$bodyString = "{\"method\": \"".$method."\",\"params\": ".$params.",\"id\": 1}";
		$bodyLength = strlen($bodyString);
		$headers = array();
		//$headers[] = 'Host: api.zenfolio.com';
		//$headers[] = 'X-Zenfolio-User-Agent: '.self::$userAgent;
		if($this->token) {
			$headers[] = 'X-Zenfolio-Token: '.$this->token;
		}
		$headers[] = 'User-Agent: '.self::$userAgent;
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Content-Length: '.$bodyLength;

		$protocol = $secure ? 'https' : 'http';

		$curl_connection = curl_init($protocol.'://'.self::$url);
		curl_setopt($curl_connection, CURLOPT_USERAGENT, self::$userAgent);
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl_connection, CURLOPT_HEADER, true);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $bodyString);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl_connection, CURLOPT_CRLF,true);
		curl_setopt($curl_connection, CURLOPT_VERBOSE, true);
		
		$result = curl_exec($curl_connection);
		
		curl_close($curl_connection);
		if ($result) {
			$json = json_decode(substr($result, strpos($result, '{') - 1));
			$result = $json->result;
			if(!$result) {
				if($try) {
					throw new ZenfolioException($json->error->message,$json->error->code);
				} else {
					return null;
				}
			}
			return $result;
		} else {
			if($try) {
				throw new Exception('curl connection failed');
			} else {
				return false;
			}
		}
	}

	public function authenticatePlain($loginName, $password) {
		$params = array($loginName,$password);
		try {
			$this->token = $this->call('AuthenticatePlain',$params,true,true);
			$this->loginName = $loginName;
		} catch (ZenfolioException $e) {
			trigger_error($e->getMessage(),E_USER_ERROR);
			throw $e;
		}
		return $this->token;
	}

	public function getPopularPhotos($offset, $max) {
		$params = array($offset,$max);
		return $this->call('GetPopularPhotos',$params);
	}

	public function loadGroup($groupId,$level='LEVEL1',$includeChildren=false) {
		$params = array($groupId,$level,$includeChildren);
		$group = $this->call('LoadGroup',$params,true);
		return $group;
	}

	public function loadGroupHierarchy($loginName) {
		$params = array($loginName);
		$groupHierarchy = $this->call('LoadGroupHierarchy',$params);
		return $groupHierarchy;
	}

	public function loadPhoto($photoId,$level='LEVEL1') {
		$params = array($photoId,$level);
		$photo = $this->call('LoadPhoto',$params);
		return $photo;
	}

	public function loadPhotoSet($photoSetId,$level='LEVEL1',$includePhotos=false) {
		$includePhotos = $includePhotos ? 'true' : 'false';
		$params = '['.$photoSetId.',"'.$level.'",'.$includePhotos.']';
		$photoSet = $this->call('LoadPhotoSet',$params);
		if($includePhotos > 1) {
			foreach ($photoSet->Photos as &$photo) {
				$photo = $this->loadPhoto($photo->Id,2);
			}
		}
		return $photoSet;
	}

	public function loadPublicProfile($loginName) {
		$params = array($loginName);
		$publicProfile = $this->call('LoadPublicProfile',$params);
		return $publicProfile;
	}

}

class ZenfolioException extends Exception {
	const E_ACCOUNTLOCKED = 1;
	const E_CONNECTIONISNOTSECURE = 2;
	const E_DUPLICATEEMAIL = 3;
	const E_DUPLICATELOGINNAME = 4;
	const E_INVALIDCREDENTIALS = 5;
	const E_INVALIDFILEFORMAT = 6;
	const E_INVALIDPARAM = 7;
	const E_FILESIZEQUOTAEXCEEDED = 8;
	const E_NOSUCHOBJECT = 9;
	const E_NOTAUTHENTICATED = 10;
	const E_NOTAUTHORIZED = 11;
	const E_STORAGEQUOTAEXCEEDED = 12;
	const E_UNSPECIFIEDERROR = 13;
	private static $codes = array(
	'E_ACCOUNTLOCKED' => self::E_ACCOUNTLOCKED,
	'E_ACCOUNTLOCKED' => self::E_ACCOUNTLOCKED,
	'E_DUPLICATEEMAIL' => self::E_DUPLICATEEMAIL,
	'E_DUPLICATELOGINNAME' => self::E_DUPLICATELOGINNAME,
	'E_INVALIDCREDENTIALS' => self::E_INVALIDCREDENTIALS,
	'E_INVALIDFILEFORMAT' => self::E_INVALIDFILEFORMAT,
	'E_INVALIDPARAM' => self::E_INVALIDPARAM,
	'E_FILESIZEQUOTAEXCEEDED' => self::E_FILESIZEQUOTAEXCEEDED,
	'E_NOSUCHOBJECT' => self::E_NOSUCHOBJECT,
	'E_NOTAUTHENTICATED' => self::E_NOTAUTHENTICATED,
	'E_NOTAUTHORIZED' => self::E_NOTAUTHORIZED,
	'E_STORAGEQUOTAEXCEEDED' => self::E_STORAGEQUOTAEXCEEDED,
	'E_UNSPECIFIEDERROR' => self::E_UNSPECIFIEDERROR);

	function __construct ($message, $code = 0, $previous = NULL) {
		$this->message = $message;
		if(key_exists($code,self::$codes)) {
			$this->code = self::$codes[$code];
		} else {
			$code = 0;
		}
	}
}
?>
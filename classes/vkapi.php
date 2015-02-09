<?
class VKapi {
	private $configVKapi;

	function __construct($configVKapi) {
		$this->config = $configVKapi;
	}
	
	public function getSocNetName() {
		return $this->config['socNetName'];
	}
	
	function preparePost($rssItem) {

		/* Подготавливаем новый пост */
		$postParams = array(
			'owner_id' => $this->config['vkPublicID'],
			'from_group' => $this->config['fromGroup'],
			'message' => $this->config['postTemplate']($rssItem),
		);

		// Если есть картинка, то скачиваем ее к себе на сервер и заливаем на сервер VK
		// (чтобы приаттачить ее по полученному в ответе id).
		if ($rssItem['postImage']) {
			$imgDownUpResponse = $this->imgDownloadUpload($rssItem['postImage'], $this->config['vkPublicID']);
			if ($imgDownUpResponse[0]->id) {
				$postParams['attachments'] = "{$imgDownUpResponse[0]->id}";
			}
		}

		return $postParams;
	}
	
	function doPost($preparatedPost) {
		$newVkPostResponse = $this->api('wall.post', $preparatedPost);
		if ($newVkPostResponse->post_id) {
			$postResult = array(
				'error' => false,
				'response' => $newVkPostResponse
			);
		} else {
			$postResult = array(
				'error' => true,
				'response' => $newVkPostResponse
			);			
		}
		return $postResult;
	}
	
	
	public function imgDownloadUpload($img, $publicID) {
		/* Download image */
		$ch = curl_init($img);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$imgDown = curl_exec($ch);
		curl_close($ch);
		$imgfn = uniqid().'.jpg';
		file_put_contents($imgfn, $imgDown);
		if (strpos(mime_content_type($imgfn), 'image') === false) {
			echo 'mime_content_type: '.mime_content_type($imgfn);
			echo "\n Не удалось получить изображение. Возможно, защита от хотлинка \n";
			unlink($imgfn);
			return false;
		}

		/* Upload image */
		$imgUpResponse = $this->api('photos.getWallUploadServer', array('group_id' => abs($publicID)));
		$uploadURL = $imgUpResponse->upload_url;
		$fullServerPathToImage = $imgfn;
		$output = array();
		exec("curl -X POST -F 'photo=@$fullServerPathToImage' '$uploadURL'", $output);
		$imgUpResponse = json_decode($output[0]);
		$imgUpResponse = $this->api('photos.saveWallPhoto', array(
			'group_id' => abs($publicID),
			'photo' => $imgUpResponse->photo,
			'server' => $imgUpResponse->server,
			'hash' => $imgUpResponse->hash,
		));

		unlink($imgfn);
		return $imgUpResponse;		
	}	
	
	public function api($method, array $query = array()) {
		/* Generate query string from array */
		$parameters = array();
		foreach ($query as $param => $value) {
			$q = $param . '=';
			if (is_array($value)) {
				$q .= urlencode(implode(',', $value));
			} else {
				$q .= urlencode($value);
			}
			$parameters[] = $q;
		}
		$q = implode('&', $parameters);
		if (count($query) > 0) {
			$q .= '&'; // Add "&" sign for access_token if query exists
		}
		$url = 'https://api.vk.com/method/' . $method . '?' . $q . 'access_token=' . $this->config['vkAccessToken'];
		$result = json_decode($this->curl($url));
		if (isset($result->response)) {
			return $result->response;
		}
		return $result;
	}

	private function curl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$result = curl_exec($ch);
		if (!$result) {
			$errno = curl_errno($ch);
			$error = curl_error($ch);
		}
		curl_close($ch);
		if (isset($errno) && isset($error)) {
			throw new \Exception($error, $errno);
		}
		return $result;
	}

}
?>
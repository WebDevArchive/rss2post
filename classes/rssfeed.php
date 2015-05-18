<?
class RssFeed {
	private $config;

	function __construct($configRss) {
		$this->config = $configRss;
	}

	public function getConfigActualDate() {
		return trim($this->config['actualDate']);
	}

	public function getRssNews() {

		if ($this->config['actualDate']) $actualDate = new DateTime($this->config['actualDate']);
		else $actualDate = false;

		// Загружаем RSS-ленту.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->config['rssFeedUrl']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);		
		$rss = simplexml_load_string($output);

		if ($rss === false) {
//			$this->Log->saveError('Error parse RSS', $this->rss2VkConfig['rssFeedUrl']);
			return 'Error parse RSS: '.$this->config['rssFeedUrl'];
		}

		// Возвращаем массив новостей.
		$rssItems = array();
		foreach($rss->xpath('//item') as $rssItem) {
			$rssItemPubDate = DateTime::createFromFormat(DateTime::RSS, (string)$rssItem->pubDate);
			if (($actualDate != false) && ($rssItemPubDate <= $actualDate)) continue;
			$newRssItem = (array) $rssItem->children();
			$newRssItem['description'] = (string) $newRssItem['description'];

//			// Обработка category (теги).
			if (is_array($newRssItem['category'])) {
				foreach($newRssItem['category'] as &$newRssItemCategory) {
					$newRssItemCategory = (string) $newRssItemCategory;
				}
			}

			// Обработка namespaces
			$rssNamespaces = $rssItem->getNameSpaces(true);
			if (is_array($rssNamespaces)) {
				foreach($rssNamespaces as $rssNamespaceKey => $rssNamespaceValue) {
					$namespaceFields = (array) $rssItem->children($rssNamespaceValue);
					foreach($namespaceFields as $namespaceFieldKey => $namespaceFieldValue) {
						if (!is_array($newRssItem[$rssNamespaceKey])) $newRssItem[$rssNamespaceKey] = array ();
						$newRssItem[$rssNamespaceKey][$namespaceFieldKey] = (string) $namespaceFieldValue;
					}
				}
			}

			// Если постим с картинками, то из текста новости берем адрес первой картинки (если она есть).
			$rssItemFieldWithImgTag = $this->config['extractImages']($newRssItem);
			if ($this->config['extractImages'] !== false) {
				preg_match('/<img[^>]+src=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/', $rssItemFieldWithImgTag, $matchesImg);
				if (isset($matchesImg[2])) {
					$imgUrl = $matchesImg[2];
					if (strpos($imgUrl, '//') === 0) {$imgUrl = substr($imgUrl, 2);}
					if (strpos($imgUrl, '://') === false ) {$imgUrl = 'http://' .$imgUrl;}
					$newRssItem['postImage'] = $imgUrl;
				} else {
					$newRssItem['postImage'] = false;
				}
			}

			array_push($rssItems, $newRssItem);
		}

		if (count($rssItems)) return $rssItems;
		else return false;
	}

}
?>
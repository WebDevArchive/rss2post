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
		$rss = simplexml_load_file($this->config['rssFeedUrl']);
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

			// Если постим с картинками, то из текста новости берем адрес первой картинки (если она есть).
			if ($this->config['extractImages']) {
				preg_match('/<img[^>]+src=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/', $newRssItem['description'], $matchesImg);
				if (isset($matchesImg[2])) {
					$newRssItem['postImage'] = $matchesImg[2];
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
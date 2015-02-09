<?
include_once __DIR__ . '/classes/rssfeed.php';
include_once __DIR__ . '/classes/vkapi.php';
include_once __DIR__ . '/classes/loger.php';
	
class Rss2Post {
	private $rss;
	private $socNets;	
	private $log;

	function __construct(RssFeed $RssFeed, $socNets, Loger $Loger) {
		$this->rss 		= $RssFeed;
		$this->socNets	= $socNets;
		$this->log		= $Loger;
	}

	public function run() {
	
		// Получаем массив новостей
		$rssNews = $this->rss->getRssNews();
		if (!$rssNews) return false;

		$rssNewsCount = count($rssNews)-1;
		for ($i=$rssNewsCount; $i>=0; $i--) {
		
			// Постим новость по социальным сетям
			foreach($this->socNets as $socNetName => &$socNet) {
				
				// Если дата новости страше последней опубликованной в текущей соц.сети, то пропускаем.
				$socNetName	= $socNet->getSocNetName();
				if (!$this->isActualNews($socNetName, $rssNews[$i])) continue;
				
				// Подготавливаем пост и постим.
				$preparedPost	= $socNet->preparePost($rssNews[$i]);
				$newPost		= $socNet->doPost($preparedPost);
				
				// Если все прошло, как надо, сохраняем дату новости в лог. 
				// Если нет — сохраняем ошибку, чтобы потом посмотреть и исправить.
				if ($newPost['error']) {
					$this->log->savePostError($socNetName, 'Post was not added', array($rssNews[$i], $newPost['response']));
				} else {
					$this->log->savePostDone($socNetName, $rssNews[$i]);
				}
			}
			
			// Пауза перед постами. 
			sleep(2);
		}
	}

	private function isActualNews($socNetName, $rssItem) {
		$actualDate = $this->log->loadLastRssDate($socNetName);
		if ($actualDate) $actualDate = DateTime::createFromFormat(DateTime::RSS, $actualDate);
		$rssItemPubDate = DateTime::createFromFormat(DateTime::RSS, (string)$rssItem['pubDate']);
		$notActual = (($actualDate != false) && ($rssItemPubDate <= $actualDate));
		if ($notActual) return false;
		return true;
	}
	
	function decodeHtmlEnt($str) {
		$ret = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
		$p2 = -1;
		for(;;) {
			$p = strpos($ret, '&#', $p2+1);
			if ($p === FALSE) break;
			$p2 = strpos($ret, ';', $p);
			if ($p2 === FALSE) break;
			if (substr($ret, $p+2, 1) == 'x') $char = hexdec(substr($ret, $p+3, $p2-$p-3));
			else $char = intval(substr($ret, $p+2, $p2-$p-2));
			$newchar = iconv(
				'UCS-4', 'UTF-8',
				chr(($char>>24)&0xFF).chr(($char>>16)&0xFF).chr(($char>>8)&0xFF).chr($char&0xFF) 
			);
			$ret = substr_replace($ret, $newchar, $p, 1+$p2-$p);
			$p2 = $p + strlen($newchar);
		}
		return $ret;
	}

}
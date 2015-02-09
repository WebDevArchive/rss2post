<?
class Loger {
	private $logFolder;
	const DELIMITER = ' | ';

	function __construct($logFolder) {
		$this->logFolder = $logFolder;
	}

	public function loadLastRssDate ($SocNetName) {
		if (file_exists($this->logFolder.$SocNetName.'.log')) {
			$lastRssDate = explode(self::DELIMITER, end(file($this->logFolder.$SocNetName.'.log')));
			return trim($lastRssDate[1]);
		}
		return false;
	}

	public function savePostDone ($SocNetName, $rssItem) {
		if (!file_exists($this->logFolder)) mkdir($this->logFolder);
		$title = str_replace("\n", '', $rssItem['title']);
		$logStr = date('d.m.Y H:i:s').self::DELIMITER.$rssItem['pubDate'].self::DELIMITER.$title."\n";
		file_put_contents($this->logFolder.$SocNetName.'.log', $logStr, FILE_APPEND | LOCK_EX);
	}
	
	public function savePostError ($SocNetName, $error_message, $error_var_dump) {
		if (!file_exists($this->logFolder)) mkdir($this->logFolder);
		$logStr = "=================\n".date('d.m.Y H:i:s')."\n=================\n".
		$error_message."\n".var_export($error_var_dump, true)."\n\n";
		file_put_contents($this->logFolder.'_'.$SocNetName.'_error.log', $logStr, FILE_APPEND | LOCK_EX);
	}

}
?>
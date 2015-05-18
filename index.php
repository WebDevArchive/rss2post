<?
	include_once 'rss2post.php';

	$configRss = array (

		// Адрес RSS-ленты.
		'rssFeedUrl' 	=> '',

		// Поле с html (узла <item>) из которого извлекать картинку для поста.
		// Берется картинка из первого тега <img>, если он присутствует.
		// Обычно это поле $rssItem['description']
		'extractImages' 	=> function ($rssItem) {
			return $rssItem['description'];
			// return $rssItem['content']['encoded'];
		},

		// RSS-новости опубликованные раньше этой даты игнорируются. Формат: '01/23/2014'.
		// Пригодится, если нужно начать постить новости, начиная с конкретной даты. Или false, если не надо.
		'actualDate'	=> false
	);

	$configSocNetVK = array (

		// Название социальной сети.
		// Используется в качестве имени файла лога в папке "logs". 
		'socNetName'	=> 'VK',

		// Токен для доступа к VK api. Получать здесь: https://vk.com/dev/auth_mobile
		// Необходимо выдать необходимые привилегии (wall и offline) для доступа к стене.
		'vkAccessToken'	=> '',

		// ID группы или пользователя, где небходимо опубликовать пост.
		// ID группы указывается со знаком минус вначале, ID пользователя без.
		'vkPublicID' 	=> -12345678,

		// Пост от имени группы (1) или от имени пользователя (0).
		'fromGroup' 	=> 1,

		// Полностью настраиваемый шаблон поста. В $rssItem передаются все поля из RSS узла <item>
		// Можно использовать функцию Rss2Post::decodeHtmlEnt для декодирования сущностей навроде &amp; => &
		'postTemplate' => function ($rssItem) { return 
			Rss2Post::decodeHtmlEnt($rssItem['title'])."\n".
			$rssItem['link']."\n--------\n".
			Rss2Post::decodeHtmlEnt($rssItem['source'])."\n";
		}
	);

	// Инициализация и запуск.
	$rssFeed	= new RssFeed($configRss);
	$loger 	= new Loger('logs/');
	$socNets	= array (
		'VK' 		=> new VKapi($configSocNetVK)
	);
	$App		= new Rss2Post($rssFeed, $socNets, $loger);
	$App->run();
?>
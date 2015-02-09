<?
	include_once 'rss2post.php';

	$configRss = array (

		// Адрес RSS-ленты.
		'rssFeedUrl' 	=> '',

		// Извлекать ли картинки из новости?
		// Берется первая картинка из текста новости, если она там есть.
		'extractImages' 	=> true,	

		// RSS-новости опубликованные раньше этой даты игнорируются. Формат: '01/23/2014'.
		// Пригодится, если нужно начать постить новости, начиная с конкретной даты. Или false, если не надо.
		'actualDate'	=> false
	);

	$configSocNetVK = array (

		// Название социальной сети.
		// Используется в качестве имени файла лога в папке "logs". 
		'socNetName'	=> 'VK',

		// Токен для доступа к VK api. Получать здесь: https://vk.com/dev/auth_mobile
		// Необходимо выдать необходимые привелегии (wall) для доступа к стене.
		'vkAccessToken'	=> 'd7fjs77d5dchf3o3p74315f279a5ffdh47fjs92d6dfd2o9rn7vd12c8gud74js9d2fd5cbc20c6bfs9ff7eo6',

		// ID группы или пользователя, где небходимо опубликовать пост.
		// ID группы указывается со знаком минус вначале, ID полльзователя без.
		'vkPublicID' 	=> -12345678,

		// Пост от имени группы (1) или от имени пользователя (0).
		'fromGroup' 	=> 1,

		// Полностью настраиваемый шаблон поста. В $rssItem передаются все поля из RSS узла <item>
		// Можно использовать функцию Rss2Vk_Utils::decodeHtmlEnt для декодирования сущностей навроде &amp; => &
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
		// TODO:	'Twitter' 	=> new TwitterApi($configTwitterApi);
		// TODO:	'Facebook' 	=> new FacebookApi($configFacebookApi);
	);
	$App		= new Rss2Post($rssFeed, $socNets, $loger);
	$App->run();
?>
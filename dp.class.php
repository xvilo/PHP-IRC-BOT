<?php
class DigitalPlace
{
	private $user;
	private $pass;
	
	function __construct ($username, $password)
	{
		$this->user = $username;
		$this->pass = $password;
	}
	
	# Private functions #
	private function afterFirstWord ($string)
	{
		$string = trim ($string);
		$pos = strpos ($string, ' ') + 1;
		return substr ($string, $pos);
	}

	private function lastWord ($string)
	{
		$string = trim ($string);
		$pos = -(strlen ($string) - strrpos ($string, ' ') - 1);
		return substr ($string, $pos);
	}

	private function firstWord ($string)
	{
		$string = trim ($string);
		$pos = strpos ($string, ' ');
		return substr ($string, 0, $pos);
	}
	
	private function trimString ($string)
	{
		$last = '';
	
		while ($last != $string)
		{
			$last = $string;
			$string = trim ($string);
		}
	
		return $string;
	}
	
	# Public functions #
	/* array (string) */
	function getForumItems ($n = 5)
	{
		$items = array ();
		
		$source = file_get_contents ('http://digitalplace.nl/includes/microBot.php?mode=replies');
		$lines = explode (PHP_EOL, $source);
	
		for ($i = 0; $i < $n; $i++)
		{
			$lines[$i] = html_entity_decode ($lines[$i]);
			preg_match ('#\[(.+)\] <(.+)> (.+): http://(.+)#', $lines[$i], $matches);
			$item['string'] = $lines[$i];
			$item['forum'] = $matches[1];
			$item['user'] = $matches[2];
			$item['title'] = $matches[3];
			$item['url'] = 'http://' . $matches[4];
			
			array_push ($items, $item);
		}
		
		return $items;
	}
	
	/* string */
	function getPage ($url, $post = NULL)
	{
		$curl = curl_init ();
		curl_setopt ($curl, CURLOPT_URL, 'http://digitalplace.nl/forum/ucp.php?mode=login');
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, 'username=' . $this->user . '&password=' . $this->pass . '&autologin=1&login=Inloggen');
		curl_setopt ($curl, CURLOPT_USERAGENT, "Robin's DigitalPlace API");
		curl_setopt ($curl, CURLOPT_HEADER, false);
		curl_setopt ($curl, CURLOPT_COOKIEJAR, 'koekje');
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt ($curl, CURLOPT_TIMEOUT, 15);
		$loginPage = curl_exec ($curl);
	
		curl_setopt ($curl, CURLOPT_URL, 'http://digitalplace.nl/' . $url);
		if ($post != NULL)
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $post);
		$page = curl_exec ($curl);
		curl_close ($curl);
		
		return $page;
	}
	
	/* array (author, title, url, string) */
	function search ($keywords)
	{
		$keywords = urlencode ($keywords);
		$url = 'forum/search.php?keywords=' . $keywords;
		
		$source = $this->getPage ($url);
		preg_match_all ('#\\<div\ class\=\"search\ post((?!\<\/ul\>).)*#ms', $source, $matches);
	
		$results = array ();
		foreach ($matches[0] as $result)
		{
			preg_match ('#\<h3\>\<a\ href\=\"([^\"]+)\"\>([^\<]+)#', $result, $match1);
			preg_match ('#\<dt\ class\=\"author\"\>door\ <a\ ([^\<]+)#', $result, $match2);
		
			$author = $this->lastWord (strip_tags ($match2[0]));
		
			$url = 'http://digitalplace.nl/forum/' . str_replace ('./', '', str_replace ('&amp;', '&', $match1[1]));
			$title = $match1[2];
			
			$item = array ();
			$item['author'] = $author;
			$item['title'] = $title;
			$item['url'] = $url;
			$item['string'] = '<' . $author . '> ' . $title . ': ' . $url;
			
			array_push ($results, $string);
		}
	
		return $results;
	}
	
	/* array (id, title, uid, from, when, string) */
	function getPmList ()
	{
		$result = array ();
		
		$source = $this->getPage ('forum/ucp.php?i=pm&folder=inbox');
		preg_match_all ('#\<dt(([^\>]+)|)>(\s+)\<a href\=\"\.\/ucp\.php\?i\=pm\&amp\;mode\=view\&amp\;f\=([^\&]+)\&amp\;p\=([^\"]+)\" class\=\"topictitle\"\>([^\<]+)\<\/a\>(\s+)\<br \/\>(\s+)door \<a href\=\"\.\/memberlist\.php\?mode\=viewprofile\&amp\;u\=([^\"]+)\"(([^\>]+)|)\>([^\<]+)\<\/a\> \&raquo\; ([^\<]+)\<\/dt\>#msi', $source, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $key => &$pm)
		{
			$pmProps = array ();
			$pmProps['id'] = $pm[5];
			$pmProps['title'] = $pm[6];
			$pmProps['uid'] = $pm[9];
			$pmProps['from'] = $pm[12];
			$pmProps['when'] = $this->trimString ($pm[13]);
			$pmProps['string'] = '[' . $pmProps['id'] . '] <' . $pmProps['from'] . '> ' . $pmProps['title'] . '(' . $pmProps['when'] . ')';
			
			array_push ($result, $pmProps);
		}
	
		return $result;
	}
	
	/* array (sender, content, string) */
	function getPm ($id)
	{
		$source = $this->getPage ('forum/ucp.php?i=pm&mode=view&f=0&p=' . (int) $id);
		preg_match ('#\<div\ class\=\"postbody\"\>(.+)\<ul\ class\=\"profile\-icons\"\>(.+)\<dl\ class\=\"postprofile#ms', $source, $postBody);
	
		preg_match ('#\<strong\>Van\:((?!\<\/a\>).)*#ms', $postBody[2], $pmSender);
		$sender = afterFirstWord (strip_tags ($pmSender[0]));
	
		preg_match ('#\<div\ class\=\"content\"\>((?!\<\/dl).)*#ms', $postBody[2], $pmContent);
		$content = html_entity_decode ($pmContent[0]);
		$content = strip_tags ($content);
		$content = $this->trimString ($content);
	
		$result['sender'] = $sender;
		$result['content'] = $content;
		$result['string'] = '<' . $sender . '> ' . $content;
		
		return $result;
	}
	
	/* string */
	function sendPm ($to, $subject, $content, $icon = 17)
	{
		$page1 = $this->getPage ('http://digitalplace.nl/forum/ucp.php?i=pm&mode=compose&action=post', 'username_list=' . $to . '&add_to=Toevoegen');
		
		preg_match ('#\<input type\=\"hidden\" name\=\"form\_token\" value\=\"([^\"]+)\" \/\>#mi', $page1, $match);
		$form_token = $match[1];
		preg_match ('#\<input type\=\"hidden\" name\=\"([^\"]+)\" value\=\"to\" \/\>#mi', $page1, $match);
		$address_list = $match[1];
		preg_match ('#\<input type="hidden" name="lastclick" value="([^\"]+)\" />#mi', $page1, $match);
		$lastclick = $match[1];
		preg_match ('#\<input type\=\"hidden\" name\=\"creation\_time\" value\=\"([^\"]+)\" \/\>#mi', $page1, $match);
		$creation_time = $match[1];
		
		sleep (2);
		
		$page2 = $this->getPage ('http://digitalplace.nl/forum/ucp.php?i=pm&mode=compose&action=post', 'subject=' . $subject . '&message=' . $content . '&icon=' . $icon . '&creation_time=' . $creation_time . '&lastclick=' . $lastclick . '&form_token=' . $form_token . '&' . $address_list . '=to&post=Bevestig');
		
		curl_close ($curl);
		
		return $page2;
	}
}
?>
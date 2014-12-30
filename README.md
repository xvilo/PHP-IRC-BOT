PHP-IRC-BOT
===========


Config
===========

Put a config.php file in the parent folder, example content:
```php
<?php
	$dbconfig['user'] = "YOUR_USER_NAME_HERE";
	$dbconfig['password'] = "YOUR_PASSWORD_HERE";
	$dbconfig['host'] = "mysql:host=localhost;dbname=YOUR_DB_HERE";
	
	$dpconfig['user'] = "YOUR_USER_NAME_HERE";
	$dpconfig['password'] = "YOUR_PASSWORD_HERE";
	
	$ircconfig['host'] = "irc.digitalplace.nl";
	$ircconfig['port'] = 6667;
	
	$ircconfig['nick'] = "nick";
	$ircconfig['ident'] = "ident";
	$ircconfig['usermode'] = 0;
	$ircconfig['alternative'] = "alternative";
	$ircconfig['nickserv'] = "YOUR_PASSWORD_HERE";
	
	$ircconfig['channels'] = array('#dpb','#xvilo', '#dpf', '#spotify');
?>
```
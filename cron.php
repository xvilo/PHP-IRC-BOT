<?php
	# DigitalPlace API #
include_once ('dp.class.php');
include_once ('../config.php');

$hostDB 	= $dbconfig['host'];
$user 		= $dbconfig['user'];
$password 	= $dbconfig['password'];
		
$dbh = new PDO($hostDB, $user, $password);
//$push = file_get_contents('http://thisisd3.com/push/push.php?title=DP+IRC+Bot&message=DP+irc+bot+started+updating');
//echo $push;

$dp = new DigitalPlace ($user, $pass);

# Forum #
$forum = $dp->getForumItems (25);
//file_put_contents ('stuff/forum', serialize ($forum));
addToDB(serialize ($forum), 'forum');

# PMs #
$pms = $dp->getPmList ();
//file_put_contents ('stuff/pms', serialize ($pms));
addToDB(serialize ($pms), 'pms');

# Karma #
$source = $dp->getPage ('forum/');
preg_match ('/\<p\ class\=\"mobiel\"\>Gebruikers\ met\ de\ hoogste\ karma(.+)\<\/p\>/', $source, $match);
$karma = substr (strip_tags ($match[0]), 33);
//file_put_contents ('stuff/karma', serialize ($karma));
addToDB(serialize ($karma), 'karma');

# Stats #
$source = $dp->getPage ('forum/');
preg_match ('/\<p\ class\=\"mobiel\"\>Totaal\ aantal\ berichten(.+)\<\/a\>\<\/strong\>\<\/p\>/', $source, $match);
preg_match ('/Het\ grootst\ aantal\ gebruikers\ online\ was\ \<strong\>([^\<]+)\<\/strong\>\ op\ ([^\<]+)/', $source, $match2);
$temp = explode ('&bull;', strip_tags ($match[0]));
$aantalBerichten = lastWord ($temp[0]);
$aantalTopics = lastWord ($temp[1]);
$aantalLeden = lastWord ($temp[2]);
$nieuwsteLid = lastWord ($temp[3]);
$stats = array
(
	'berichten' => lastWord ($temp[0]),
	'topics' => lastWord ($temp[1]),
	'leden' => lastWord ($temp[2]),
	'nieuwsteLid' => lastWord ($temp[3]),
	'string' => 'Berichten: ' . $aantalBerichten . ' :: Topics: ' . $aantalTopics . ' :: Leden: ' . $aantalLeden . ' (nieuwste: ' . $nieuwsteLid . ')'
);
//file_put_contents ('stuff/stats', serialize ($stats));
addToDB(serialize ($stats), 'stats');

# Online #
$source = $dp->getPage ('forum/');
preg_match ('/Geregistreerde\ gebruikers(.+)\<\/a\>/', $source, $match);
$online = str_replace ('Geregistreerde gebruikers: ', '', strip_tags ($match[0]));
//file_put_contents ('stuff/online', serialize ($online));
addToDB(serialize ($online), 'online');

# IP #
$ip = file_get_contents ('http://thisisd3.com/ip.php');
//file_put_contents ('stuff/ip', $ip);
addToDB(serialize ($ip), 'ip');

# last updated #
$up =  date('l d F Y - H:i');
//file_put_contents ('stuff/update', serialize($up));
addToDB(serialize ($up), 'update');

# Private functions #
function afterFirstWord ($string)
{
	$string = trim ($string);
	$pos = strpos ($string, ' ') + 1;
	return substr ($string, $pos);
}

function lastWord ($string)
{
	$string = trim ($string);
	$pos = -(strlen ($string) - strrpos ($string, ' ') - 1);
	return substr ($string, $pos);
}

function firstWord ($string)
{
	$string = trim ($string);
	$pos = strpos ($string, ' ');
	return substr ($string, 0, $pos);
}

function trimString ($string)
{
	$last = '';

	while ($last != $string)
	{
		$last = $string;
		$string = trim ($string);
	}

	return $string;
}

function addToDB($content, $type){
	global $dbh;
	$stmt = $dbh->prepare("UPDATE `info` SET content='".$content."' WHERE naam = '".$type."'");
	
	if ($stmt->execute()) { 
		echo "Stored $type in DB.".PHP_EOL;
	} else {
		var_dump($stmt->errorInfo());
		echo 'PDO::errorInfo(): '.implode(",", $stmt->errorInfo());
	}
}

?>
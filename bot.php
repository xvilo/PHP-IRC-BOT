<?php
	$_ = 'bot.php';
/**
 * $Id$
 * $Revision$
 * $Author$
 * $Date$
 *
 * Copyright (c) 2002-2003 Mirco "MEEBEY" Bauer <mail@meebey.net> <http://www.meebey.net>
 *
 * Full LGPL License: <http://www.meebey.net/lgpl.txt>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
// ---EXAMPLE OF HOW TO USE Net_SmartIRC---
// this code shows how a mini php bot which could be written
include_once('Net/SmartIRC.php');
include_once('../config.php');

class MyBot
{
    private $irc;
    private $handlerids;
    private $dbh;
    private $stmt;

    public function __construct($irc)
    {
        $this->irc = $irc;
        $this->handlerids = array(
	        //regulars
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-fk', $this, 'dp_karma'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-fm', $this, 'dp_forum'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-fs', $this, 'dp_stats'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-fo', $this, 'dp_online'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-up', $this, 'dp_update'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-help', $this, 'help'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^!help', $this, 'help'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-h', $this, 'help'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, 'tangramm', $this, 'help'),
            
            //auto functions
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '\+\+ .+$', $this, 'irc_karma'),
            $irc->registerActionHandler(SMARTIRC_TYPE_JOIN, '.*?', $this, 'irc_join'),
            
            //admin functions
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-q', $this, 'quit'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-r', $this, 'restart'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '^-uirl', $this, 'dp_update_irl'),
            $irc->registerActionHandler(SMARTIRC_TYPE_CHANNEL, '.*?', $this, 'channel_test'),
        );
		global $config;
	    $this->dbh = new PDO($config['db']['host'], $config['db']['user'], $config['db']['password']);
    }
    
    public function dp_forum($irc, $data)
    {
		$forum = $this->get_dp_data('forum');
		$count = 0;
		foreach ($forum as &$value) {
		    $irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "{$value['user']} - {$value['title']} // {$value['url']}");
		    $count++;
		    
		    if($count == 5){
			    break; 
		    }
		}
	}
	
	public function dp_online($irc, $data){
		$online = $this->get_dp_data('online');
 		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "Online: $online"); 
	}
	
	public function dp_update($irc, $data){
		$update = $this->get_dp_data('update');
		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "last updated at: $update"); 
	}
	
	public function dp_karma($irc, $data)
    {
		$karma = $this->get_dp_data('karma');
		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "Hoogste aantal Karma: $karma"); 
	}
	
	public function dp_stats($irc, $data){
		$stats = $this->get_dp_data('stats');
		
		$msg =  "Topics: {$stats['topics']}, Reacties: {$stats['berichten']}, Leden: {$stats['leden']}, Nieuwste lid: {$stats['nieuwsteLid']}";
		
		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, $msg);
	}
	
	public function help($irc, $data){
		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "DPF IRC Bot by xvilo");
		$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "Commands: -fk, -fm, -fs, -fo, -up, -help");
	}
	
	public function quit($irc, $data){
		if(($data->ident == "xvilo")&&($data->host == "internet.user")){
			$irc->quit();
			exit(1);
		}else{
			$irc->message(SMARTIRC_TYPE_QUERY, $data->nick, "Niet toegestaan! Dit is gelogd");
		}
	}
	
	public function restart($irc, $data){
		if(($data->ident == "xvilo")&&($data->host == "internet.user")){
			$irc->quit();
			exit(2);
		}else{
			$irc->message(SMARTIRC_TYPE_QUERY, $data->nick, "Niet toegestaan! Dit is gelogd");
		}
	}
	
	public function reload($irc, $data){
		if(($data->ident == "xvilo")&&($data->host == "internet.user")){
			$irc->reload();
		}else{
			$irc->message(SMARTIRC_TYPE_QUERY, $data->nick, "Niet toegestaan! Dit is gelogd");
		}
	}
	
	public function dp_update_irl($irc, $data){
		if(($data->ident == "xvilo")&&($data->host == "internet.user")){
			include('cron.php');
		}else{
			$irc->message(SMARTIRC_TYPE_QUERY, $data->nick, "Niet toegestaan! Dit is gelogd");
		}
	}

    public function channel_test($irc, $data)
    {	
		$this->stmt = $this->dbh->prepare("INSERT INTO `irclog` (`nick`, `ident`, `host`, `type`, `from`, `channel`, `message`, `time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		$this->stmt->bindParam(1, $data->nick);
		$this->stmt->bindParam(2, $data->ident);
		$this->stmt->bindParam(3, $data->host);
		$this->stmt->bindParam(4, $data->rawmessageex[2]);
		$this->stmt->bindParam(5, $data->from);
		$this->stmt->bindParam(6, $data->channel);
		$this->stmt->bindParam(7, $data->message);
		$this->stmt->bindParam(8, date("G:i"));
		
		if ($this->stmt->execute()) { 
			echo "[".date("G:i")."] <$data->nick> $data->message".PHP_EOL;
		} else {
			var_dump($this->stmt->errorInfo());
			$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, 'PDO::errorInfo(): '.implode(",", $$this->stmt->errorInfo()));
		}
    }
    
    public function irc_karma($irc, $data){
	    $nick = substr($data->message, 3);
	    $nickId = $this->get_user_id($nick);
	    $rnickId = $this->get_user_id($data->nick);
	    echo $rnickId;
	    if($nickId == ""){
		    $irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "$nick bestaat niet *sadface*");
	    }elseif($rnickId == ""){
		    $irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "{$data->nick} staat niet geregistreerd, niet je hoofd nick?");
		}elseif($rnickId == $nickId){
			$irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, "{$data->nick} je mag je zelf geen karma geven!");
		}else{
		    $irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, "sorry, {$data->nick}, karma werkt nog niet");
	    }
	}
    
    public function irc_join($irc, $data){
	    //store all data nicely
		$nick = $data->nick;
		$ident = $data->ident;
		$host = $data->host;
		$type = $data->rawmessageex[2];
		$from = $data->from;
		//get user data
		$getUsers = $this->dbh->prepare("SELECT * FROM users WHERE ident='$ident' AND host='$host'");
		if (!$getUsers->execute()){
			$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, 'PDO::errorInfo(): '.implode(",", $getUsers->errorInfo()));
		}
		$users = $getUsers->fetchAll();
		if(empty($users)){
			 echo "$nick bestaat niet!".PHP_EOL;
		     $irc->message(SMARTIRC_TYPE_CHANNEL, $data->message, "$nick is nieuw! Welkom $nick.");
		     $check = $this->registerNick($irc, $data);
		}else{
			echo "$nick bestaat YES!".PHP_EOL;
			 $irc->message(SMARTIRC_TYPE_NOTICE, $data->nick, 'Welkom Terug '.$data->nick);
		}
	}
	
    
    //overall functions
    
	public function get_dp_data($type){
		$getData = $this->dbh->prepare("SELECT content FROM info WHERE naam='".$type."'");
		$getData->execute();
		$data = $getData->fetchAll();
		$data = unserialize($data[0]['content']);
		return $data;
	}
	
	public function registerNick($irc, $data){
		$opt = 1;
		
		$this->stmt = $this->dbh->prepare("INSERT INTO `users` (`nick`, `ident`, `host`, `from`, `opt-out`) VALUES (?, ?, ?, ?, ?)");
		$this->stmt->bindParam(1, $data->nick);
		$this->stmt->bindParam(2, $data->ident);
		$this->stmt->bindParam(3, $data->host);
		$this->stmt->bindParam(4, $data->from);
		$this->stmt->bindParam(5, $opt);
		
		if ($this->stmt->execute()) { 
			echo "$nick is geregistreerd!".PHP_EOL;
			return true;
		} else {
			var_dump($this->stmt->errorInfo());
			$irc->message(SMARTIRC_TYPE_CHANNEL, $data->channel, 'PDO::errorInfo(): '.implode(",", $this->stmt->errorInfo()));
			echo "$nick is niet geregistreerd!".PHP_EOL;
			return false;
		}
	}
	
	public function get_user_id($nick){
		$getData = $this->dbh->prepare("SELECT id FROM users WHERE nick='".$nick."'");
		if ($getData->execute()){
			$data = $getData->fetchAll();
			
			return $data[0]['id'];
			
		}else{
			echo 'PDO::errorInfo(): '.implode(",", $getData->errorInfo());
		}
		
	}
		
}

$irc = new Net_SmartIRC(array(
    //'DebugLevel' => SMARTIRC_DEBUG_ALL,
));
$bot = new MyBot($irc);
$irc->connect($ircconfig['host'], $ircconfig['port']);
$irc->login($ircconfig['nick'], $ircconfig['realname'], $ircconfig['usermode'], $ircconfig['ident']);
$irc->message(SMARTIRC_TYPE_QUERY, "NickServ", "identify {$ircconfig['nickserv']}"); 
$irc->join($ircconfig['channels']);
$irc->listen();
$irc->disconnect();

pcntl_exec($_);

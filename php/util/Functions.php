<?php

namespace LastFmTube\Util;

use Exception;
use LastFmTube\Util\lfmapi\LastFm;
use LastFmTube\Util\ytapi\YoutubeSearch;
use Smarty;

class Functions {

	private static $instance;
	private $basedir = false;
	private $settings = false;
	private $settingsFile = false;
	private $replacements = false;
	private $replacementFile = false;

    /**
     * @var \Smarty $smarty
     */
	private $smarty = null;
    /**
     * @var LastFm $lfmapi
     */
	private $lfmapi = null;
    /**
     * @var YoutubeSearch $ytapi
     */
	private $ytapi = null;


	private function __construct($file=false){
		$this->settingsFile = $file;
        $this->basedir = dirname(__FILE__).'/../..';
        $this->initSettings();
        $this->initReplacements();
        $this->initInstances();
	}

	private function initInstances() {
        $this->smarty = new Smarty ();
        $this->smarty->setTemplateDir ( $this->basedir.'/themes/' . $this->settings ['general'] ['theme'] );
        $this->smarty->setCacheDir ( $this->basedir.'/tmp/smarty/cache' );
        $this->smarty->setCompileDir ( $this->basedir.'/tmp/smarty/compile' );
        $this->smarty->caching = false;

        $this->smarty->assign ( 'BASE_PATH', $this->settings ['general'] ['baseurl'] );
        $this->smarty->assign ( 'LOCALE', $this->settings ['general'] ['lang'] );
        $this->smarty->assign ( 'LANG', $this->loadLangFile () );
        $this->smarty->assign ( 'ytplayerwidth', $this->settings ['general'] ['playerwidth'] );
        $this->smarty->assign ( 'ytplayerheight', $this->settings ['general'] ['playerheight'] );
        $this->smarty->assign ( 'cmenutheme', $this->settings ['general'] ['cmenutheme'] );

        $this->lfmapi = new LastFm ();
        $this->lfmapi->setApiKey ( $this->settings ['lastfm'] ['apikey'] );

        $this->ytapi = new YoutubeSearch ();
        if (isset ( $this->settings ['youtube'] ['email'] ))
            $this->ytapi->setAPIEmail ( $this->settings ['youtube'] ['email'] );
        if (isset ( $this->settings ['youtube'] ['jsonfile'] ))
            $this->ytapi->setAPIJson ( $this->settings ['youtube'] ['jsonfile'] );
        if (isset ( $this->settings ['youtube'] ['user'] ))
            $this->ytapi->setAPIUser ( $this->settings ['youtube'] ['user'] );
        $this->ytapi->setAPIKey ( $this->settings ['youtube'] ['apikey'] );
    }

    /**
     * @return \Smarty
     */
    public function getSmarty() {
	    return $this->smarty;
    }

    /**
     * @return LastFm
     */
    public function getLfmApi() {
        return $this->lfmapi;
    }

    /**
     * @return YoutubeSearch
     */
    public function getYtApi(){
        return $this->ytapi;
    }

    /**
     * @return mixed
     */
    public function getSettings(){
        return $this->settings;
    }

	private function initSettings($force=false){
		if(!$force && is_array($this->settings)) return;
		
		if($this->settingsFile===false) $this->settingsFile=$this->basedir.'/conf/settings.ini';
		if (!$this->settings = parse_ini_file($this->settingsFile, TRUE)) throw new exception('Unable to open ' . $this->settingsFile . '.');

        if (strncmp ( $this->settings ['general'] ['baseurl'], '/', strlen ( $this->settings ['general'] ['baseurl'] ) ) === 0) {
            $this->settings ['general'] ['baseurl'] = substr ( $this->settings ['general'] ['baseurl'], 1 );
        }

        $this->loadLangFile();
	}


	private function initReplacements($force=false) {
		if(!$force && is_array($this->replacements)) return;
		
		if($this->replacementFile===false) $this->replacementFile=$this->basedir.'/conf/replace_strings.txt';
		$lines = file ( $this->replacementFile );
		if($lines === false) throw new exception('Unable to open ' . $this->replacementFile . '.');
		
		foreach ( $lines  as $line ) {
		    if (substr ( trim($line), 0, 1 ) === "#") continue;
		    $line=str_replace(array("\r", "\n", "\r\n"), "", $line);
		    $entry = explode ( "=", $line, 2 );
		    if(sizeof($entry)<2) $entry[1] = '';
		    $this->replacements [$entry [0]] = $entry [1];
		}
	}
	
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Functions();
		}
		return self::$instance;
	}

	public static function br2nl($text, $tags = "br") {
		$tags = explode(" ", $tags);

		foreach($tags as $tag) {
			$text = preg_replace("/<" . $tag . "[^>]*>/i", "\n", $text);
			$text = preg_replace("/<\/" . $tag . "[^>]*>/i", "\n", $text);
		}

		return($text);
	}

	public function startSession() {
		$started = false;
		if ( php_sapi_name() !== 'cli' ) {
			if ( version_compare(phpversion(), '5.4.0', '>=') ) $started = session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;			
			else  $started = session_id() === '' ? FALSE : TRUE;		
		}
		if(!$started) session_start();


        if (isset ( $_GET ['lastfm_user'] )) {
            $_SESSION ['music'] ['lastfm_user'] = $_GET ['lastfm_user'];
            unset ( $_GET ['lastfm_user'] );
        } else if (! isset ( $_SESSION ['music'] ['lastfm_user'] )) {
            $_SESSION ['music'] ['lastfm_user'] = self::$instance->settings ['general'] ['lastfm_defaultuser'];
        }
        $_SESSION ['music'] ['lastfm_user'] = trim ( $_SESSION ['music'] ['lastfm_user'] );

        self::$instance->lfmapi->setUser ( $_SESSION ['music'] ['lastfm_user'] );
	}


	public function logMessage($msg) {
		self::$instance->initSettings();
		$logfile = fopen(self::getInstance()->settings['general']['logpath'],'a+');

		$prefix = date('d.m.Y H:i:s');
		$msgArr = explode("\n",self::br2nl($msg));
		for($i=0;$i<sizeof($msgArr);$i++) {
			if(strlen($msgArr[$i]) > 0)
				fwrite($logfile,$prefix."\t".$msgArr[$i]."\r\n");
		}
		fclose($logfile);
	}
	
	public function prepareNeedle($needle) {
        self::getInstance()->initReplacements();
		$needle = html_entity_decode(strip_tags(trim ( $needle )), ENT_QUOTES | ENT_HTML5);	
		if(is_array($this->replacements)) {
		        foreach ( $this->replacements as $key => $value ) {
			    $needle = str_replace ( $key, $value, $needle );
			}
		}		
		return $needle;
	}
 	
	private function loadLangFile() {
		return parse_ini_file(
			$this->basedir.'/locale/locale_'.
			$this->settings['general']['lang'].'.properties'
		);
	}
	
	public function saveConfig($config=false){	
		
		$fh = fopen(dirname(__FILE__).'/../../conf/settings.ini', 'w');
		fwrite($fh, 
			"; you need to have a registered last.fm user with a developer API\n\n".
			"['general']\n".
			"baseurl = ".$config['general']['baseurl']."\n".
			"; possible values = de,en\n".
			"lang = ".$config['general']['lang']."\n".
			";path to log file\n".
			"logpath = ".$config['general']['logpath']."\n".
			";youtube player width and height (relative or absolte)\n".
			"playerwidth = ".$config['general']['playerwidth']."\n".
			"playerheight = ".$config['general']['playerheight']."\n".
			"; themes/mytheme must exist, possible values 'default','dark'\n".
			"theme = ".$config['general']['theme']."\n".
			";Conext Menu Theme name. Included themes are: 'default','xp','vista','osx','human','gloss'\n".
			";Multiple themes may be applied with a comma-separated list.\n".
			"cmenutheme = ".$config['general']['cmenutheme']."\n".
			"; the default last.fm user when initally loading the playlist\n".
			"lastfm_defaultuser = ".$config['general']['lastfm_defaultuser']."\n".
			"; the Admin Password as sha1_value (default is lfmtube)\n".
			"adminpw = ".$config['general']['adminpw'].
			"\n".
			";[database]\n".
			";dsn = mysql:host=127.0.0.1;port=3306;dbname=lasttube;charset=UTF8;\n".
			";table_prefix = music_\n".
			";username = lastuser\n".
			";password = l4stp4$$\n".
			"\n".
			"[database]\n".
			"dsn = ".$config['database']['dsn']."\n".
			"table_prefix = ".$config['database']['table_prefix']."\n".
			"username = ".$config['database']['username']."\n".
			"password = ".$config['database']['password']."\n".
			"\n".
			"[lastfm]\n".
			"; the lastfm user with the developer API Key\n".
			"user = ".$config['lastfm']['user']."\n".
			"; the lastfm user developer API Key\n".
			"apikey = ".$config['lastfm']['apikey']."\n".
			"\n".
			"[youtube]\n".
			"apikey = ".$config['youtube']['apikey']."\n".
			"; required for OAuth Login (not yet supported)\n".
			";email = 755183333407-8a5huo8gk68uenschgvg1vpmdbj9c18r@developer.gserviceaccount.com\n".
			";keyfile = /home/ravermeister/lastfm.rimkus.it/conf/youtube.p12\n".
			";user = info@rimkus.it\n"
		);
		fclose($fh);
        self::getInstance()->initSettings(true);
	}

}
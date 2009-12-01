<?php

	$language_list = array();
	
	/** The great language array:
	@param name 0. i18ned name
	@param abbrev 1. abbreviation, must match asterisk langs and will be stored in db
	@param cname 2. name in english, technical
	@param locale 3. locale
	@param enc 4. encoding
	@param sela 5. selectable for voice etc.
	@param flag 6.flag. If present, selectable for web locale */
	
	function load_Lang($name,$abbrev,$cname,$locale,$enc,$sela,$flag){
		global $language_list;
		$language_list[] = array('name' => $name ,
			'abbrev' => $abbrev,
			'cname' => $cname,
			'locale' => $locale,
			'enc' => $enc,
			'sela' => $sela,
			'flag' => $flag );
	}

	load_Lang(_("English"), "en", "english","en_US","iso88591",true,"us.png");

	// Add your own languages with load_Lang(...)


function get_sel_languages() {
	global $language_list;
	$ret = array();
	foreach($language_list as $lang)
		if ($lang['sela'])
		$ret[] = array($lang['name'],$lang['abbrev']);
	return $ret;
}

function get_locales($all = true) {
	global $language_list;
	$ret = array(array('C',_("English (C)")));
	foreach($language_list as $lang)
		if ($all || $lang['sela'])
		$ret[] = array($lang['locale'],$lang['name']);
	return $ret;
}

function negot_language($def_lang){
	global $language_list;
	if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		return $def_lang;
	
	$langs= explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
	
	$cur_lang = $def_lang;
	$cur_q = 0.0;
	
	foreach($langs as $lang){
		$langa = explode(';', $lang);
		$langq=1.0;
		if ((isset($langa[1])) && (substr($langa[1],0,2)== "q="))
			$langq=(float)substr($langa[1],2);
		//echo "Found supported lang \"".$langa[0]."\", q= ".$langq ."<br>\n";
		
		if ($cur_q >= $langq)
			continue;
		
		foreach($language_list as $langl)
		    if ($langl['flag'])
			if (($langl['abbrev'] == $langa[0]) ||
			    ($langl['locale'] == $langa[0]) ||
			    (substr($langl['locale'],0,2) == $langa[0])) {
				$cur_lang=$langl['cname'];
				$cur_q = $langq;
				break;
			}
	}
	
	return $cur_lang;
}

function SetLocalLanguage($set_lang) {
	$slectedLanguage = "en_US";
	$languageEncoding = "en_US.iso88591";
	$charEncoding = "iso88591";
	global $language_list;
	global $FG_DEBUG;
	$ret='en';
	
	foreach ($language_list as $lang)
		if ($lang['cname'] == $set_lang){
		$slectedLanguage = $lang['locale'];
		$languageEncoding = $lang['locale'] . "." . $lang['enc'];
		$charEncoding = $lang['enc'] ;
		$ret=$lang['abbrev'];
	}
	
	//Code here to set the Encoding of the Lanuages and its Envirnoment Variables

	//@setlocale(LC_TIME,$languageEncoding);
	putenv("LANG=".$slectedLanguage);
	putenv("LANGUAGE=".$slectedLanguage);
	$res= setlocale(LC_ALL,$slectedLanguage, $languageEncoding);
	if (!$res  && ($FG_DEBUG > 1))
		error_log("Could not set locale to $slectedLanguage, $languageEncoding");
//       bindtextdomain($domain,"./lib/locale/");
	bindtextdomain(MESSAGE_DOMAIN, "./locale/");
	textdomain(MESSAGE_DOMAIN);
	bind_textdomain_codeset(MESSAGE_DOMAIN,$charEncoding);
	define('CHARSET', $charEncoding);
	if ($FG_DEBUG>4)
		trigger_error("Locale: " . setlocale(LC_MESSAGES,0) ." : " . $slectedLanguage,E_USER_NOTICE);
	
	return $ret;
}

?>

<?php
/* Copyright 2006-2009 P. Christeas <p_christ@hol.gr>, LGPL 2
*/


/*
 * function sanitize_data
 */
function sanitize_data($data)
{
	$lowerdata = strtolower ($data);
	$data = str_replace('--', '', $data);	
	$data = str_replace("'", '', $data);
	$data = str_replace('=', '', $data);
	$data = str_replace(';', '', $data);
	//$lowerdata = str_replace('table', '', $lowerdata);
	//$lowerdata = str_replace(' or ', '', $data);
	if (!(strpos($lowerdata, ' or ')===FALSE)){ return false;}
	if (!(strpos($lowerdata, 'table')===FALSE)){ return false;}
	return $data;
}

/** Return a single variable from the post/get data */
function getpost_single($vname)
{
	if (isset($_POST[$vname]))
		return sanitize_data($_POST[$vname]);
	elseif (isset($_GET[$vname]))
		return sanitize_data($_GET[$vname]);
	else
		return null;
}

/** The opposite of getpost_ifset: create an array with those post vars
	@param arr Array of ("var name", ...)
	@param empty_null If true, treat empty vars as null
	@return array( var => val, ...)
BIG NOTE: It doesn't work, because GLOBALS here may not be the same..
	*/

function putpost_arr($test_vars, $empty_null = false){
	$ret = array();
	if (!is_array($test_vars)) {
		$test_vars = array($test_vars);
	}
	foreach($test_vars as $test_var) {
		if (isset($GLOBALS[$test_var]) && ($GLOBALS[$test_var] != null) &&
			((!$empty_null) || $GLOBALS[$test_var] != '') )
			$ret[$test_var] = $GLOBALS[$test_var];
	}
	return $ret;
}

/** Convert params in array to url string
   @param arr An array like (var1 => value1, ...)
   @return A url like var1=value1
   */
function arr2url ($arr) {
	if (!is_array($arr))
		return;
	$rar = array();
	foreach($arr as $key => $value) {
		if ($value === null)
			continue;
		if (is_array($value)){
			foreach($value as $arr_val)
				$rar[] = "$key" . '[]=' . rawurlencode($arr_val);
		}else
		$rar[] = "$key" . '=' . rawurlencode($value);
	}
	return implode('&',$rar);
}

/** Generate an html combo, with selected values etc. */
function gen_Combo($name, $value, $option_array,$multiple=false){
	$tmp_name=$name;
	if ($multiple){
		$tmp_name.='[]';
		$tmp_size=count($option_array);
		if ($tmp_size>20)
			$tmp_size=15;
		$opts .= ' class="form_enter" multiple="multiple" size='.$tmp_size;
	}else
		$opts .=' size=1 class="form_enter"';
	?> <select name="<?= $tmp_name?>" <?=$opts ?>>
	<?php
		if (is_array($option_array))
		foreach($option_array as $option){ ?>
		<option value="<?= $option[0] ?>"<?php 
		if (($value == $option[0]) || ($multiple && is_array($value) && in_array($option[0],$value)))
			echo ' selected'; 
		?>><?= htmlspecialchars($option[1])?></option>
	<?php	}
	?>
	</select>
	<?php
	
}


/** Calculate arguments in a string of the form "Test %1 or %4 .." 
	This function is carefully written, so that it could be used securely, for
	example, when 'eval(string_param(" echo %&0",array( $dangerous_str)))' is called.
	That is, we have some special prefixes:
		%#x means the x-th parameter as a number, 0 if nan
		%&x means the x-th parameter as a quoted string
		%% will become '%', as will %X where X not [1-9a-z]
	@param $str The input string
	@param $parm_arr An array with the parameters, so %1 will become $parm_arr[1]
	@param $noffset	The offset of the param. noffset=1 means %1 = $parm_arr[0],
			noffset=-2 means %0 = $parm_arr[2]
	@note This fn won't work for more than 10 params!	
*/

function str_params($str, $parm_arr, $noffset = 0){
	$strlen=strlen($str);
	$strp=0;
	$stro=0;
	$resstr='';
	do{
		$strp=strpos($str,"%",$stro);
		if($strp===false){
			$resstr=$resstr . substr($str,$stro);
			break;
		}
		$resstr=$resstr . substr($str,$stro,$strp-$stro);
		$strp++;
		if ($strp>=$strlen)
			break;
		$sm=0;
		if ($str[$strp] == '#'){
			$sm=1;
			$strp++;
		}
		else if ($str[$strp] =='&'){
			$sm=2;
			$strp++;
		}
		if (( $str[$strp]>='0')  && ( $str[$strp]<='9')){
			$pv=$str{$strp} - '0';
// 			echo "Var %$pv\n";
			if (isset($parm_arr[$pv - $noffset]))
				$v = $parm_arr[$pv - $noffset];
			else	$v = '';
			if ($sm==1)
				$v = (integer) $v;
			else if ($sm == 2)
				$v = addslashes($v);
			
			$resstr= $resstr . $v;
		}else
			$resstr= $resstr . $str[$strp];
		$stro=$strp+1;
	}while ($stro<$strlen);
		
	return $resstr;
}

/** Calculate arguments in a string of the form "Test %1 or %4 .." 
	This function is intended for database usage:
	eg. str_dbparams(dbh,"SELECT %1 , %2 ; ", array("me", "'DROP DATABASE sql_inject;'"));
	 will result in "SELECT 'me', '''DROP DATABASE sql_inject;''' ;" which is safe!
	 %#x means the x-th parameter as a number, 0 if nan
	Additionaly, parms in the form %!3 will result in "NULL" when parm is empty.
	
	@param $str The input string, say, the sql command
	@param $parm_arr An array with the parameters, so %1 will become $parm_arr[0]
	@param $dbh the db handle
	@note This fn won't work for more than 10 params!	
*/

function str_dbparams($dbh, $str, $parm_arr){
	$strlen=strlen($str);
	$strp=0;
	$stro=0;
	$resstr='';
	do{
		$strp=strpos($str,"%",$stro);
		if($strp===false){
			$resstr=$resstr . substr($str,$stro);
			break;
		}
		$resstr=$resstr . substr($str,$stro,$strp-$stro);
		$strp++;
		if ($strp>=$strlen)
			break;
		$sm=0;
		if ($str[$strp] == '!'){
			$sm=1;
			$strp++;
		}else
		if ($str[$strp] == '#'){
			$sm=2;
			$strp++;
		}
		if (( $str[$strp]>='0')  && ( $str[$strp]<='9')){
			$pv=$str{$strp} - '0';
// 			echo "Var %$pv\n";
			$v= null;
			if (isset($parm_arr[$pv - 1]))
				$v = $parm_arr[$pv - 1];
			if ($sm==1) {
				if ($v == '') $v = null;
				if ($v == null)
					$resstr .= 'NULL';
				else
					$resstr .= $dbh->Quote($v);
			} else if ($sm ==2) {
				if ($v == '') 
					$v = null;
				
				if ($v == null)
					$resstr .= '0';
				elseif (preg_match('/^\-?[0-9]+([,.][0-9]*)?$/',$v)>=1)
					$resstr .= $v;
				else
					$resstr .= '0';
			}
			else {
				if ($v == null) $v = '';
				$resstr .= $dbh->Quote($v);
			}
		}else
			$resstr .= $str[$strp];
		$stro=$strp+1;
	}while ($stro<$strlen);
		
	return $resstr;
}

/** Calculate arguments in a string of the form "Test %var or %id .." 
	This function is carefully written, so that it could be used securely, for
	example, when 'eval(string_param(" echo %&str",array( $dangerous_str)))' is called.
	That is, we have some special prefixes:
		%#x means the x parameter as a number, 0 if nan
		%&x means the x parameter as a quoted string
		%@x means the x parameter must be html-escaped
		%% will become '%', as will %X where X not [1-9a-z]
	@param $str The input string
	@param $parm_arr An array with the parameters, so %id will become $parm_arr['id']

	@note The param name can contain alphanumeric, '_' . The name terminates at non-alpha.
*/
function str_alparams($str, $parm_arr, $noffset = 0){
	$strlen=strlen($str);
	$strp=0;
	$stro=0;
	$resstr='';
	do{
		$strp=strpos($str,"%",$stro);
		if($strp===false){
			$resstr=$resstr . substr($str,$stro);
			break;
		}
		$resstr=$resstr . substr($str,$stro,$strp-$stro);
		$strp++;
		if ($strp>=$strlen)
			break;
		$sm=0;
		if ($str[$strp] == '#'){
			$sm=1;
			$strp++;
		}
		else if ($str[$strp] =='&'){
			$sm=2;
			$strp++;
		}
		else if ($str[$strp] =='@'){
			$sm=3;
			$strp++;
		}
		for ($stre=$strp ; ($stre<$strlen) && (($str[$stre] == '_' )|| ctype_alnum($str[$stre]));$stre++);
		
		if ($stre>$strp){
			$pv=substr($str,$strp,$stre-$strp);
			if (isset($parm_arr[$pv]))
				$v = $parm_arr[$pv];
			else	$v = '';
			if ($sm==1)
				$v = (integer) $v;
			elseif ($sm == 2)
				$v = addslashes($v);
			elseif ($sm == 3)
				$v = nl2br(htmlspecialchars($v));
			
			$resstr= $resstr . $v;
		}else {
			$resstr= $resstr . $str[$strp];
			$stre++;
		}
		$stro=$stre;
	}while ($stro<$strlen);
		
	return $resstr;
}


function str_aldbparams(&$dbh,$str, $parm_arr){
	$strlen=strlen($str);
	$strp=0;
	$stro=0;
	$resstr='';
	do{
		$strp=strpos($str,"%",$stro);
		if($strp===false){
			$resstr=$resstr . substr($str,$stro);
			break;
		}
		$resstr=$resstr . substr($str,$stro,$strp-$stro);
		$strp++;
		if ($strp>=$strlen)
			break;
		$sm=0;
		if ($str[$strp] == '!'){
			$sm=1;
			$strp++;
		}
		else if ($str[$strp] =='#'){
			$sm=2;
			$strp++;
		}
		for ($stre=$strp ; ($stre<$strlen) && (($str[$stre] == '_' )|| ctype_alnum($str[$stre]));$stre++);
		
		if ($stre>$strp){
			$pv=substr($str,$strp,$stre-$strp);
			if (isset($parm_arr[$pv]))
				$v = $parm_arr[$pv];
			else	$v = '';
	
			if ($sm==1) {
				if ($v == '') $v = null;
				if ($v == null)
					$resstr .= 'NULL';
				else
					$resstr .= $dbh->Quote($v);
			} else if ($sm ==2) {
				if ($v == '') 
					$v = null;
				
				if ($v == null)
					$resstr .= '0';
				elseif (preg_match('/^\-?[0-9]+$/',$v)>=1)
					$resstr .= $v;
				else
					$resstr .= '0';
			}
			else {
				if ($v == null) $v = '';
				$resstr .= $dbh->Quote($v);
			}

		}else {
			$resstr= $resstr . $str[$strp];
			$stre++;
		}
		$stro=$stre;
	}while ($stro<$strlen);
		
	return $resstr;
}

/** For code clarity only: it will produce the string for an &lt;acronym&gt; element
		@param acr   The acronym, the short one
		@param title the explanation (usually a hint)
*/
function acronym($acr, $title){
	$res ="<acronym title=\"";
	$res .= $title;
	$res .= "\" >";
	$res .= $acr;
	$res .= "</acronym>";
	return $res;
}

/** Format a where clause, based on the date selection table.
	The selection parameters are automatically got from the _GET/_POST
	\param col The table column to check the dates against.
	\note Postgres only!
*/
function fmt_dateclause($dbhandle, $col){
	global $Period, $frommonth, $fromstatsmonth, $tomonth, $tostatsmonth, $fromday, $fromstatsday_sday, $fromstatsmonth_sday, $today, $tostatsday_sday, $tostatsmonth_sday, $fromstatsmonth_sday, $fromstatsmonth_shour, $tostatsmonth_sday, $tostatsmonth_shour, $fromstatsmonth_smin, $tostatsmonth_smin;
	$date_clauses = array();
	if ($Period == "Month"){
		if ($frommonth && isset($fromstatsmonth))
			$date_clauses[] ="$col >=  timestamptz " .
				$dbhandle->Quote($fromstatsmonth."-01");
		if ($tomonth && isset($tostatsmonth))
			$date_clauses[] ="date_trunc('month', $col) <= timestamptz " . $dbhandle->Quote( $tostatsmonth."-01");
	
	}elseif ($Period == "Day") {
		//echo "Day!" ;
		//echo "From: $fromday $fromstatsday_sday,$fromstatsmonth_sday, $fromstatsmonth_shour, $fromstatsmonth_smin <br>\n";
		if ($fromday && isset($fromstatsday_sday) && isset($fromstatsmonth_sday) && isset($fromstatsmonth_shour) && isset($fromstatsmonth_smin) ) 
			$date_clauses[]= "$col >= timestamptz " .
				$dbhandle->Quote( $fromstatsmonth_sday. "-".$fromstatsday_sday. " " . $fromstatsmonth_shour.":". $fromstatsmonth_smin);
		if ($today&& isset($tostatsday_sday) && isset($tostatsmonth_sday) && isset($tostatsmonth_shour) && isset($tostatsmonth_smin))
			$date_clauses[] =" $col <= timestamptz ". $dbhandle->Quote(sprintf("%12s-%02d %02d:%02d",
			$tostatsmonth_sday, intval($tostatsday_sday),  intval($tostatsmonth_shour), intval($tostatsmonth_smin)));
	}
		// if other period, no date_clause!
	
	return implode(" AND ",$date_clauses);

}

/** A companion to fmt_dateclause: find the clause for items \b before the
	date clause. This is useful for the sums carried to our interval */
function fmt_dateclause_c($dbhandle, $col){
	global $Period, $frommonth, $fromstatsmonth, $fromday, $fromstatsday_sday, $fromstatsmonth_sday,$fromstatsmonth_sday, $fromstatsmonth_shour, $fromstatsmonth_smin;
	
	$date_clause = "";
	if ($Period == "Month"){
		if ($frommonth && isset($fromstatsmonth))
			$date_clause ="$col <  timestamptz " .
				$dbhandle->Quote($fromstatsmonth."-01");
	}elseif ($Period == "Day") {
		if ($fromday && isset($fromstatsday_sday) && isset($fromstatsmonth_sday) && isset($fromstatsmonth_shour) && isset($fromstatsmonth_smin) ) 
			$date_clause = "$col < timestamptz " .
				$dbhandle->Quote( $fromstatsmonth_sday. "-".$fromstatsday_sday. " " . $fromstatsmonth_shour.":". $fromstatsmonth_smin);
	}
		// if other period, no date_clause!
	
	return $date_clause;

}

function sql_encodeArray($DBHandle,$arr_data){
	$tmp_arr = array();
	foreach($arr_data as $data)
	if (is_numeric($data))
		$tmp_arr[] = (string) $data;
	else
		$tmp_arr[] = $DBHandle->Quote($data);
	if (!count($tmp_arr))
		return 'NULL';

	return 'ARRAY[' . implode(', ', $tmp_arr) . ']';
}

function sql_decodeArray($arr_str){
	if (!is_string($arr_str))
		return array();
	$len = strlen($arr_str)-1;
	if (($arr_str[0] != '{' ) || ($arr_str[$len] != '}'))
		return array();
	//$a=1;
	$b=1;
	$ret_array=array();
	while($b<=$len){
		$tmp_str='';
		for(;($b<$len) && ($arr_str[$b] == ' ');$b++);
		if ($arr_str[$b] =='"'){
			for($b=$b+1;($b<$len) && ($arr_str[$b]!='"');$b++)
				$tmp_str.=$arr_str[$b];
			$b++;
		}
		for(;($b<$len) &&($arr_str[$b]!=',');$b++)
			$tmp_str.=$arr_str[$b];
		$b++;
		$ret_array[]=$tmp_str;
	}
	return $ret_array;
}

function securitykey ($key, $data)
{
	// RFC 2104 HMAC implementation for php.
	// Creates an md5 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Hacked by Lance Rushing
	
	$b = 64; // byte length for md5
	if (strlen($key) > $b) {
		$key = pack("H*",md5($key));
	}
	$key  = str_pad($key, $b, chr(0x00));
	$ipad = str_pad('', $b, chr(0x36));
	$opad = str_pad('', $b, chr(0x5c));
	$k_ipad = $key ^ $ipad ;
	$k_opad = $key ^ $opad;
	
	return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
}

/** Arguments for cmdline php 
*/
function arguments($argv) {
    $_ARG = array();
    array_shift($argv); //skip argv[0] !
    foreach ($argv as $arg) {
      if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(ereg('--([^=]+)',$arg,$reg)){
      	$_ARG[$reg[1]] = true;
      } elseif(ereg('^-([a-zA-Z0-9])',$arg,$reg)) {
            $_ARG[$reg[1]] = true;
      } else {
            $_ARG['input'][]=$arg;
      }
    }
  return $_ARG;
}

?>
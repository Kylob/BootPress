<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 4.8.4
*/function
adminer_errors($Ec,$Gc){return!!preg_match('~^(Trying to access array offset on( value of type)? null|Undefined array key)~',$Gc);}error_reporting(5);set_error_handler('adminer_errors',E_WARNING);$cd=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($cd||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$X){$Ki=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Ki)$$X=$Ki;}}if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection(){global$f;return$f;}function
adminer(){global$b;return$b;}function
version(){global$ia;return$ia;}function
idf_unescape($t){if(!preg_match('~^[`\'"[]~',$t))return$t;$te=substr($t,-1);return
str_replace($te.$te,$te,substr($t,1,-1));}function
escape_string($X){return
substr(q($X),1,-1);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
number_type(){return'((?<!o)int(?!er)|numeric|real|float|double|decimal|money)';}function
remove_slashes($wg,$cd=false){if(function_exists("get_magic_quotes_gpc")&&get_magic_quotes_gpc()){while(list($x,$X)=each($wg)){foreach($X
as$le=>$W){unset($wg[$x][$le]);if(is_array($W)){$wg[$x][stripslashes($le)]=$W;$wg[]=&$wg[$x][stripslashes($le)];}else$wg[$x][stripslashes($le)]=($cd?$W:stripslashes($W));}}}}function
bracket_escape($t,$Na=false){static$wi=array(':'=>':1',']'=>':2','['=>':3','"'=>':4');return
strtr($t,($Na?array_flip($wi):$wi));}function
min_version($bj,$Ge="",$g=null){global$f;if(!$g)$g=$f;$ph=$g->server_info;if($Ge&&preg_match('~([\d.]+)-MariaDB~',$ph,$B)){$ph=$B[1];$bj=$Ge;}return(version_compare($ph,$bj)>=0);}function
charset($f){return(min_version("5.5.3",0,$f)?"utf8mb4":"utf8");}function
script($_h,$vi="\n"){return"<script".nonce().">$_h</script>$vi";}function
script_src($Pi){return"<script src='".h($Pi)."'".nonce()."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($P){return
str_replace("\0","&#0;",htmlspecialchars($P,ENT_QUOTES,'utf-8'));}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($C,$Y,$db,$qe="",$xf="",$hb="",$re=""){$I="<input type='checkbox' name='$C' value='".h($Y)."'".($db?" checked":"").($re?" aria-labelledby='$re'":"").">".($xf?script("qsl('input').onclick = function () { $xf };",""):"");return($qe!=""||$hb?"<label".($hb?" class='$hb'":"").">$I".h($qe)."</label>":$I);}function
optionlist($D,$ih=null,$Ti=false){$I="";foreach($D
as$le=>$W){$Df=array($le=>$W);if(is_array($W)){$I.='<optgroup label="'.h($le).'">';$Df=$W;}foreach($Df
as$x=>$X)$I.='<option'.($Ti||is_string($x)?' value="'.h($x).'"':'').(($Ti||is_string($x)?(string)$x:$X)===$ih?' selected':'').'>'.h($X);if(is_array($W))$I.='</optgroup>';}return$I;}function
html_select($C,$D,$Y="",$wf=true,$re=""){if($wf)return"<select name='".h($C)."'".($re?" aria-labelledby='$re'":"").">".optionlist($D,$Y)."</select>".(is_string($wf)?script("qsl('select').onchange = function () { $wf };",""):"");$I="";foreach($D
as$x=>$X)$I.="<label><input type='radio' name='".h($C)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>";return$I;}function
select_input($Ia,$D,$Y="",$wf="",$ig=""){$ai=($D?"select":"input");return"<$ai$Ia".($D?"><option value=''>$ig".optionlist($D,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$ig'>").($wf?script("qsl('$ai').onchange = $wf;",""):"");}function
confirm($Qe="",$jh="qsl('input')"){return
script("$jh.onclick = function () { return confirm('".($Qe?js_escape($Qe):'Are you sure?')."'); };","");}function
print_fieldset($s,$ye,$ej=false){echo"<fieldset><legend>","<a href='#fieldset-$s'>$ye</a>",script("qsl('a').onclick = partial(toggle, 'fieldset-$s');",""),"</legend>","<div id='fieldset-$s'".($ej?"":" class='hidden'").">\n";}function
generate_linksbar($_){$Be="<p class='links'>";foreach($_
as$x=>$z){if($x!==key(array_keys($_)))$Be.="<span class='separator'>|</span>";$Be.=$z;}$Be.="</p>";return$Be;}function
bold($Ua,$hb=""){return($Ua?" class='active $hb'":($hb?" class='$hb'":""));}function
odd($I=' class="odd"'){static$r=0;if(!$I)$r=-1;return($r++%2?$I:'');}function
js_escape($P){return
addcslashes($P,"\r\n'\\/");}function
json_row($x,$X=null){static$dd=true;if($dd)echo"{";if($x!=""){echo($dd?"":",")."\n\t\"".addcslashes($x,"\r\n\t\"\\/").'": '.($X!==null?'"'.addcslashes($X,"\r\n\"\\/").'"':'null');$dd=false;}else{echo"\n}\n";$dd=true;}}function
ini_bool($Yd){$X=ini_get($Yd);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
sid(){static$I;if($I===null)$I=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$I;}function
set_password($aj,$M,$V,$F){$_SESSION["pwds"][$aj][$M][$V]=($_COOKIE["adminer_key"]&&is_string($F)?array(encrypt_string($F,$_COOKIE["adminer_key"])):$F);}function
get_password(){$I=get_session("pwds");if(is_array($I))$I=($_COOKIE["adminer_key"]?decrypt_string($I[0],$_COOKIE["adminer_key"]):false);return$I;}function
q($P){global$f;return$f->quote($P);}function
get_vals($G,$d=0){global$f;$I=array();$H=$f->query($G);if(is_object($H)){while($J=$H->fetch_row())$I[]=$J[$d];}return$I;}function
get_key_vals($G,$g=null,$sh=true){global$f;if(!is_object($g))$g=$f;$I=array();$H=$g->query($G);if(is_object($H)){while($J=$H->fetch_row()){if($sh)$I[$J[0]]=$J[1];else$I[]=$J[0];}}return$I;}function
get_rows($G,$g=null,$l="<p class='error'>"){global$f;$yb=(is_object($g)?$g:$f);$I=array();$H=$yb->query($G);if(is_object($H)){while($J=$H->fetch_assoc())$I[]=$J;}elseif(!$H&&!is_object($g)&&$l&&defined("PAGE_HEADER"))echo$l.error()."\n";return$I;}function
unique_array($J,$v){foreach($v
as$u){if(preg_match("~PRIMARY|UNIQUE~",$u["type"])){$I=array();foreach($u["columns"]as$x){if(!isset($J[$x]))continue
2;$I[$x]=$J[$x];}return$I;}}}function
escape_key($x){if(preg_match('(^([\w(]+)('.str_replace("_",".*",preg_quote(idf_escape("_"))).')([ \w)]+)$)',$x,$B))return$B[1].idf_escape(idf_unescape($B[2])).$B[3];return
idf_escape($x);}function
where($Z,$n=array()){global$f,$w;$I=array();foreach((array)$Z["where"]as$x=>$X){$x=bracket_escape($x,1);$d=escape_key($x);$I[]=$d.($w=="sql"&&is_numeric($X)&&preg_match('~\.~',$X)?" LIKE ".q($X):($w=="mssql"?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($n[$x],q($X))));if($w=="sql"&&preg_match('~char|text~',$n[$x]["type"]??null)&&preg_match("~[^ -@]~",$X))$I[]="$d = ".q($X)." COLLATE ".charset($f)."_bin";}foreach((array)$Z["null"]as$x)$I[]=escape_key($x)." IS NULL";return
implode(" AND ",$I);}function
where_check($X,$n=array()){parse_str($X,$bb);remove_slashes(array(&$bb));return
where($bb,$n);}function
where_link($r,$d,$Y,$zf="="){return"&where%5B$r%5D%5Bcol%5D=".urlencode($d)."&where%5B$r%5D%5Bop%5D=".urlencode(($Y!==null?$zf:"IS NULL"))."&where%5B$r%5D%5Bval%5D=".urlencode($Y);}function
convert_fields($e,$n,$L=array()){$I="";foreach($e
as$x=>$X){if($L&&!in_array(idf_escape($x),$L))continue;$Ga=convert_field($n[$x]);if($Ga)$I.=", $Ga AS ".idf_escape($x);}return$I;}function
cookie($C,$Y,$Ae=2592000){global$ba;return
header("Set-Cookie: $C=".urlencode($Y).($Ae?"; expires=".gmdate("D, d M Y H:i:s",time()+$Ae)." GMT":"")."; path=".preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]).($ba?"; secure":"")."; HttpOnly; SameSite=lax",false);}function
restart_session(){if(!ini_bool("session.use_cookies"))session_start();}function
stop_session($jd=false){$Si=ini_bool("session.use_cookies");if(!$Si||$jd){session_write_close();if($Si&&@ini_set("session.use_cookies",false)===false)session_start();}}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($aj,$M,$V,$j=null){global$mc;preg_match('~([^?]*)\??(.*)~',remove_from_uri(implode("|",array_keys($mc))."|username|".($j!==null?"db|":"").session_name()),$B);return"$B[1]?".(sid()?SID."&":"").($aj!="server"||$M!=""?urlencode($aj)."=".urlencode($M)."&":"")."username=".urlencode($V).($j!=""?"&db=".urlencode($j):"").($B[2]?"&$B[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($A,$Qe=null){if($Qe!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($A!==null?$A:$_SERVER["REQUEST_URI"]))][]=$Qe;}if($A!==null){if($A=="")$A=".";header("Location: $A");exit;}}function
query_redirect($G,$A,$Qe,$Fg=true,$Lc=true,$Vc=false,$ii=""){global$f,$l,$b;if($Lc){$Hh=microtime(true);$Vc=!$f->query($G);$ii=format_time($Hh);}$Ch="";if($G)$Ch=$b->messageQuery($G,$ii,$Vc);if($Vc){$l=error().$Ch.script("messagesPrint();");return
false;}if($Fg)redirect($A,$Qe.$Ch);return
true;}function
queries($G){global$f;static$_g=array();static$Hh;if(!$Hh)$Hh=microtime(true);if($G===null)return
array(implode("\n",$_g),format_time($Hh));$_g[]=(preg_match('~;$~',$G)?"DELIMITER ;;\n$G;\nDELIMITER ":$G).";";return$f->query($G);}function
apply_queries($G,$S,$Hc='table'){foreach($S
as$Q){if(!queries("$G ".$Hc($Q)))return
false;}return
true;}function
queries_redirect($A,$Qe,$Fg){list($_g,$ii)=queries(null);return
query_redirect($_g,$A,$Qe,$Fg,false,!$Fg,$ii);}function
format_time($Hh){return
sprintf('%.3f s',max(0,microtime(true)-$Hh));}function
relative_uri(){return
str_replace(":","%3a",preg_replace('~^[^?]*/([^?]*)~','\1',$_SERVER["REQUEST_URI"]));}function
remove_from_uri($Tf=""){return
substr(preg_replace("~(?<=[?&])($Tf".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
pagination($E,$Ob){return" ".($E==$Ob?$E+1:'<a href="'.h(remove_from_uri("page").($E?"&page=$E".($_GET["next"]?"&next=".urlencode($_GET["next"]):""):"")).'">'.($E+1)."</a>");}function
get_file($x,$Xb=false){$bd=$_FILES[$x];if(!$bd)return
null;foreach($bd
as$x=>$X)$bd[$x]=(array)$X;$I='';foreach($bd["error"]as$x=>$l){if($l)return$l;$C=$bd["name"][$x];$qi=$bd["tmp_name"][$x];$Cb=file_get_contents($Xb&&preg_match('~\.gz$~',$C)?"compress.zlib://$qi":$qi);if($Xb){$Hh=substr($Cb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Hh,$Lg))$Cb=iconv("utf-16","utf-8",$Cb);elseif($Hh=="\xEF\xBB\xBF")$Cb=substr($Cb,3);$I.=$Cb."\n\n";}else$I.=$Cb;}return$I;}function
upload_error($l){$Ne=($l==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($l?'Unable to upload a file.'.($Ne?" ".sprintf('Maximum allowed file size is %sB.',$Ne):""):'File does not exist.');}function
repeat_pattern($fg,$ze){return
str_repeat("$fg{0,65535}",$ze/65535)."$fg{0,".($ze%65535)."}";}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
shorten_utf8($P,$ze=80,$Oh=""){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$ze).")($)?)u",$P,$B))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$ze).")($)?)",$P,$B);return
h($B[1]).$Oh.(isset($B[2])?"":"<i>â€¦</i>");}function
format_number($X){return
strtr(number_format($X,0,".",','),preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
friendly_url($X){return
preg_replace('~[^a-z0-9_]~i','-',$X);}function
hidden_fields($wg,$Nd=array(),$og=''){$I=false;foreach($wg
as$x=>$X){if(!in_array($x,$Nd)){if(is_array($X))hidden_fields($X,array(),$x);else{$I=true;echo'<input type="hidden" name="'.h($og?$og."[$x]":$x).'" value="'.h($X).'">';}}}return$I;}function
hidden_fields_get(){echo(sid()?'<input type="hidden" name="'.session_name().'" value="'.h(session_id()).'">':''),(SERVER!==null?'<input type="hidden" name="'.DRIVER.'" value="'.h(SERVER).'">':""),'<input type="hidden" name="username" value="'.h($_GET["username"]).'">';}function
table_status1($Q,$Wc=false){$I=table_status($Q,$Wc);return($I?$I:array("Name"=>$Q));}function
column_foreign_keys($Q){global$b;$I=array();foreach($b->foreignKeys($Q)as$p){foreach($p["source"]as$X)$I[$X][]=$p;}return$I;}function
enum_input($T,$Ia,$m,$Y,$Ac=null){global$b;preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$Ie);$I=($Ac!==null?"<label><input type='$T'$Ia value='$Ac'".((is_array($Y)?in_array($Ac,$Y):$Y===0)?" checked":"")."><i>".'empty'."</i></label>":"");foreach($Ie[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$db=(is_int($Y)?$Y==$r+1:(is_array($Y)?in_array($r+1,$Y):$Y===$X));$I.=" <label><input type='$T'$Ia value='".($r+1)."'".($db?' checked':'').'>'.h($b->editVal($X,$m)).'</label>';}return$I;}function
input($m,$Y,$q){global$U,$b,$w;$C=h(bracket_escape($m["field"]));echo"<td class='function'>";if(is_array($Y)&&!$q){$Ea=array($Y);if(version_compare(PHP_VERSION,5.4)>=0)$Ea[]=JSON_PRETTY_PRINT;$Y=call_user_func_array('json_encode',$Ea);$q="json";}$Pg=($w=="mssql"&&$m["auto_increment"]);if($Pg&&!$_POST["save"])$q=null;$sd=(isset($_GET["select"])||$Pg?array("orig"=>'original'):array())+$b->editFunctions($m);$Ia=" name='fields[$C]'";if($m["type"]=="enum")echo
h($sd[""])."<td>".$b->editInput($_GET["edit"],$m,$Ia,$Y);else{$Cd=(in_array($q,$sd)||isset($sd[$q]));echo(count($sd)>1?"<select name='function[$C]'>".optionlist($sd,$q===null||$Cd?$q:"")."</select>".on_help("getTarget(event).value.replace(/^SQL\$/, '')",1).script("qsl('select').onchange = functionChange;",""):h(reset($sd))).'<td>';$ae=$b->editInput($_GET["edit"],$m,$Ia,$Y);if($ae!="")echo$ae;elseif(preg_match('~bool~',$m["type"]))echo"<input type='hidden'$Ia value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked='checked'":"")."$Ia value='1'>";elseif($m["type"]=="set"){preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$Ie);foreach($Ie[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$db=(is_int($Y)?($Y>>$r)&1:in_array($X,explode(",",$Y),true));echo" <label><input type='checkbox' name='fields[$C][$r]' value='".(1<<$r)."'".($db?' checked':'').">".h($b->editVal($X,$m)).'</label>';}}elseif(preg_match('~blob|bytea|raw|file~',$m["type"])&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$C'>";elseif(($gi=preg_match('~text|lob|memo~i',$m["type"]))||preg_match("~\n~",$Y)){if($gi&&$w!="sqlite")$Ia.=" cols='50' rows='12'";else{$K=min(12,substr_count($Y,"\n")+1);$Ia.=" cols='30' rows='$K'".($K==1?" style='height: 1.2em;'":"");}echo"<textarea$Ia>".h($Y).'</textarea>';}elseif($q=="json"||preg_match('~^jsonb?$~',$m["type"]))echo"<textarea$Ia cols='50' rows='12' class='jush-js'>".h($Y).'</textarea>';else{$Pe=(!preg_match('~int~',$m["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$m["length"],$B)?((preg_match("~binary~",$m["type"])?2:1)*$B[1]+($B[3]?1:0)+($B[2]&&!$m["unsigned"]?1:0)):($U[$m["type"]]?$U[$m["type"]]+($m["unsigned"]?0:1):0));if($w=='sql'&&min_version(5.6)&&preg_match('~time~',$m["type"]))$Pe+=7;echo"<input".((!$Cd||$q==="")&&preg_match('~(?<!o)int(?!er)~',$m["type"])&&!preg_match('~\[\]~',$m["full_type"])?" type='number'":"")." value='".h($Y)."'".($Pe?" data-maxlength='$Pe'":"").(preg_match('~char|binary~',$m["type"])&&$Pe>20?" size='40'":"")."$Ia>";}echo$b->editHint($_GET["edit"],$m,$Y);$dd=0;foreach($sd
as$x=>$X){if($x===""||!$X)break;$dd++;}if($dd)echo
script("mixin(qsl('td'), {onchange: partial(skipOriginal, $dd), oninput: function () { this.onchange(); }});");}}function
process_input($m){global$b,$k;$t=bracket_escape($m["field"]);$q=$_POST["function"][$t]??null;$Y=$_POST["fields"][$t];if($m["type"]=="enum"){if($Y==-1)return
false;if($Y=="")return"NULL";return+$Y;}if($m["auto_increment"]&&$Y=="")return
null;if($q=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?idf_escape($m["field"]):false);if($q=="NULL")return"NULL";if($m["type"]=="set")return
array_sum((array)$Y);if($q=="json"){$q="";$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}if(preg_match('~blob|bytea|raw|file~',$m["type"])&&ini_bool("file_uploads")){$bd=get_file("fields-$t");if(!is_string($bd))return
false;return$k->quoteBinary($bd);}return$b->processInput($m,$Y,$q);}function
fields_from_edit(){global$k;$I=array();foreach((array)$_POST["field_keys"]as$x=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$x];$_POST["fields"][$X]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$X){$C=bracket_escape($x,1);$I[$C]=array("field"=>$C,"privileges"=>array("insert"=>1,"update"=>1),"null"=>1,"auto_increment"=>($x==$k->primary),);}return$I;}function
search_tables(){global$b,$f;$_GET["where"][0]["val"]=$_POST["query"];$lh="<ul>\n";foreach(table_status('',true)as$Q=>$R){$C=$b->tableName($R);if(isset($R["Engine"])&&$C!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$H=$f->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",$b->selectSearchProcess(fields($Q),array())),1));if(!$H||$H->fetch_row()){$sg="<a href='".h(ME."select=".urlencode($Q)."&where[0][op]=".urlencode($_GET["where"][0]["op"])."&where[0][val]=".urlencode($_GET["where"][0]["val"]))."'>$C</a>";echo"$lh<li>".($H?$sg:"<p class='error'>$sg: ".error())."\n";$lh="";}}}echo($lh?"<p class='message'>".'No tables.':"</ul>")."\n";}function
dump_headers($Ld,$Ye=false){global$b;$I=$b->dumpHeaders($Ld,$Ye);$Pf=$_POST["output"];if($Pf!="text")header("Content-Disposition: attachment; filename=".$b->dumpFilename($Ld).".$I".($Pf!="file"&&preg_match('~^[0-9a-z]+$~',$Pf)?".$Pf":""));session_write_close();ob_flush();flush();return$I;}function
dump_csv($J){foreach($J
as$x=>$X){if(preg_match('~["\n,;\t]|^0|\.\d*0$~',$X)||$X==="")$J[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($_POST["format"]=="tsv"?"\t":";")),$J)."\r\n";}function
apply_sql_function($q,$d){return($q?($q=="unixepoch"?"DATETIME($d, '$q')":($q=="count distinct"?"COUNT(DISTINCT ":strtoupper("$q("))."$d)"):$d);}function
get_temp_dir(){$I=ini_get("upload_tmp_dir");if(!$I){if(function_exists('sys_get_temp_dir'))$I=sys_get_temp_dir();else{$o=@tempnam("","");if(!$o)return
false;$I=dirname($o);unlink($o);}}return$I;}function
file_open_lock($o){$qd=@fopen($o,"r+");if(!$qd){$qd=@fopen($o,"w");if(!$qd)return;chmod($o,0660);}flock($qd,LOCK_EX);return$qd;}function
file_write_unlock($qd,$Qb){rewind($qd);fwrite($qd,$Qb);ftruncate($qd,strlen($Qb));flock($qd,LOCK_UN);fclose($qd);}function
password_file($h){$o=get_temp_dir()."/adminer.key";$I=@file_get_contents($o);if($I||!$h)return$I;$qd=@fopen($o,"w");if($qd){chmod($o,0660);$I=rand_string();fwrite($qd,$I);fclose($qd);}return$I;}function
rand_string(){return
md5(uniqid(mt_rand(),true));}function
select_value($X,$z,$m,$hi){global$b;if(is_array($X)){$I="";foreach($X
as$le=>$W)$I.="<tr>".($X!=array_values($X)?"<th>".h($le):"")."<td>".select_value($W,$z,$m,$hi);return"<table cellspacing='0'>$I</table>";}if(!$z)$z=$b->selectLink($X,$m);if($z===null){if(is_mail($X))$z="mailto:$X";if(is_url($X))$z=$X;}$I=$b->editVal($X,$m);if($I!==null){if(!is_utf8($I))$I="\0";elseif($hi!=""&&is_shortable($m))$I=shorten_utf8($I,max(0,+$hi));else$I=h($I);}return$b->selectVal($I,$z,$m,$X);}function
is_mail($yc){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$lc='[[:alnum:]](?:[-[:alnum:]]{0,61}[[:alnum:]])';$fg="$Ha+(?:\\.$Ha+)*@(?:$lc?\\.)+$lc";return
is_string($yc)&&preg_match("(^$fg(?:,\\s*$fg)*\$)i",$yc);}function
is_url($P){return(bool)preg_match('~^
			https?://                 # scheme
			(?:
				# IPv6 in square brackets
				\[(?:
					(?:[[:xdigit:]]{1,4}:){7}[[:xdigit:]]{1,4} |             # 1:2:3:4:5:6:7:8
					(?:[[:xdigit:]]{1,4}:){1,7}: |                           # 1::                             1:2:3:4:5:6:7::
					(?:[[:xdigit:]]{1,4}:){1,6}:[[:xdigit:]]{1,4} |          # 1::8            1:2:3:4:5:6::8  1:2:3:4:5:6::8
					(?:[[:xdigit:]]{1,4}:){1,5}(?::[[:xdigit:]]{1,4}){1,2} | # 1::7:8          1:2:3:4:5::7:8  1:2:3:4:5::8
					(?:[[:xdigit:]]{1,4}:){1,4}(?::[[:xdigit:]]{1,4}){1,3} | # 1::6:7:8        1:2:3:4::6:7:8  1:2:3:4::8
					(?:[[:xdigit:]]{1,4}:){1,3}(?::[[:xdigit:]]{1,4}){1,4} | # 1::5:6:7:8      1:2:3::5:6:7:8  1:2:3::8
					(?:[[:xdigit:]]{1,4}:){1,2}(?::[[:xdigit:]]{1,4}){1,5} | # 1::4:5:6:7:8    1:2::4:5:6:7:8  1:2::8
					[[:xdigit:]]{1,4}:(?::[[:xdigit:]]{1,4}){1,6} |          # 1::3:4:5:6:7:8  1::3:4:5:6:7:8  1::8
					:(?::[[:xdigit:]]{1,4}){1,7} |                           # ::2:3:4:5:6:7:8 ::2:3:4:5:6:7:8 ::8
					fe80:(?::[[:xdigit:]]{0,4}){0,4}%[[:alnum:]]+ |          # fe80::7:8%eth0  fe80::7:8%1     (link-local IPv6 addresses with zone index)
					::(?:ffff(?::0{1,4})?:)?
						(?:(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])\.){3}
						(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])
						(?<!\b0\.0\.0\.0) |                                  # ::255.255.255.255  ::ffff:255.255.255.255 ::ffff:0:255.255.255.255  (IPv4-mapped IPv6 addresses and IPv4-translated addresses)
					(?:[[:xdigit:]]{1,4}:){1,4}:
						(?:(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])\.){3}
						(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])
						(?<!\b0\.0\.0\.0)                                    # 2001:db8:3:4::192.0.2.33  64:ff9b::192.0.2.33 (IPv4-Embedded IPv6 Address)
				)\] |
				# IPv4
				(?:(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])\.){3}
					(?:25[0-5]|(?:2[0-4]|1?[0-9])?[0-9])
					(?<!\b0\.0\.0\.0) |                                      # 0.0.0.0 excluded for URLs
				# domain
				[_[:alnum:]](?:[-_[:alnum:]]{0,61}[_[:alnum:]])?
					(?:\.[_[:alnum:]](?:[-_[:alnum:]]{0,61}[_[:alnum:]])?)*
			)                         # host
			(?::(?:[1-9]\d{0,3})?\d)? # port
			(?:/[^\s?\#]*)?           # path
			(?:\?[^\s\#]*)?           # query
			(?:\#\S*)?                # fragment
			$~xi',$P);}function
is_shortable($m){return
preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~',$m["type"]??null);}function
count_rows($Q,$Z,$ge,$wd){global$w;$G=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($ge&&($w=="sql"||count($wd)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$wd).")$G":"SELECT COUNT(*)".($ge?" FROM (SELECT 1$G GROUP BY ".implode(", ",$wd).") x":$G));}function
slow_query($G){global$b,$si,$k;$j=$b->database();$ji=$b->queryTimeout();$xh=$k->slowQuery($G,$ji);if(!$xh&&support("kill")&&is_object($g=connect())&&($j==""||$g->select_db($j))){$oe=$g->result(connection_id());echo'<script',nonce(),'>
var timeout = setTimeout(function () {
	ajax(\'',js_escape(ME),'script=kill\', function () {
	}, \'kill=',$oe,'&token=',$si,'\');
}, ',1000*$ji,');
</script>
';}else$g=null;ob_flush();flush();$I=@get_key_vals(($xh?$xh:$G),$g,false);if($g){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$I;}function
get_token(){$Cg=rand(1,1e6);return($Cg^$_SESSION["token"]).":$Cg";}function
verify_token(){list($si,$Cg)=explode(":",$_POST["token"]);return($Cg^$_SESSION["token"])==$si;}function
lzw_decompress($Ra){$ic=256;$Sa=8;$jb=array();$Rg=0;$Sg=0;for($r=0;$r<strlen($Ra);$r++){$Rg=($Rg<<8)+ord($Ra[$r]);$Sg+=8;if($Sg>=$Sa){$Sg-=$Sa;$jb[]=$Rg>>$Sg;$Rg&=(1<<$Sg)-1;$ic++;if($ic>>$Sa)$Sa++;}}$hc=range("\0","\xFF");$I="";foreach($jb
as$r=>$ib){$xc=$hc[$ib];if(!isset($xc))$xc=$pj.$pj[0];$I.=$xc;if($r)$hc[]=$pj.$xc[0];$pj=$xc;}return$I;}function
on_help($rb,$uh=0){return
script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $rb, $uh) }, onmouseout: helpMouseout});","");}function
edit_form($Q,$n,$J,$Ni){global$b,$w,$si,$l;$Th=$b->tableName(table_status1($Q,true));page_header(($Ni?'Edit':'Insert'),$l,array("select"=>array($Q,$Th)),$Th);$b->editRowPrint($Q,$n,$J,$Ni);if($J===false)echo"<p class='error'>".'No rows.'."\n";echo'<form action="" method="post" enctype="multipart/form-data" id="form">
';if(!$n)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table cellspacing='0' class='layout'>".script("qsl('table').onkeydown = editingKeydown;");foreach($n
as$C=>$m){echo"<tr><th>".$b->fieldName($m);$Yb=$_GET["set"][bracket_escape($C)]??null;if($Yb===null){$Yb=$m["default"];if($m["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$Yb,$Lg))$Yb=$Lg[1];}$Y=($J!==null?($J[$C]!=""&&$w=="sql"&&preg_match("~enum|set~",$m["type"])?(is_array($J[$C])?array_sum($J[$C]):+$J[$C]):(is_bool($J[$C])?+$J[$C]:$J[$C])):(!$Ni&&$m["auto_increment"]?"":(isset($_GET["select"])?false:$Yb)));if(!$_POST["save"]&&is_string($Y))$Y=$b->editVal($Y,$m);$id=null;if(isset($_POST["function"][$C]))$id=(string)$_POST["function"][$C];$q=($_POST["save"]?$id:($Ni&&preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Ni&&$Y==$m["default"]&&preg_match('~^[\w.]+\(~',$Y))$q="SQL";if(preg_match("~time~",$m["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$q="now";}input($m,$Y,$q);echo"\n";}if(!support("table"))echo"<tr>"."<th><input name='field_keys[]'>".script("qsl('input').oninput = fieldChange;")."<td class='function'>".html_select("field_funs[]",$b->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>"."\n";echo"</table>\n";}echo"<p>\n";if($n){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])){echo"<input type='submit' name='insert' value='".($Ni?'Save and continue edit':'Save and insert next')."' title='Ctrl+Shift+Enter'>\n",($Ni?script("qsl('input').onclick = function () { return !ajaxForm(this.form, '".'Saving'."â€¦', this); };"):"");}}echo($Ni?"<input type='submit' name='delete' value='".'Delete'."'>".confirm()."\n":($_POST||!$n?"":script("focus(qsa('td', qs('#form'))[1].firstChild);")));if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo'<input type="hidden" name="referer" value="',h(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"]),'">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="',$si,'">
</form>
';}if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");if($_GET["file"]=="favicon.ico"){header("Content-Type: image/x-icon");echo
lzw_decompress("\0\0\0` €Â\0X\0Z\0\0CàĞ>‡‰ \rø9\0ò9\$–M'”JeR¹d¶]/˜LfS9¤Öm7œI[m–Ãı|:¿Ò(„ı¾İnNiTºe6+y<^õe(ÿ,E¯ôòY\$ÿL\$uŠÓıD›K¿Ş÷}>İo¸[›­¶ÓüŞ`*¿ÑãÃıÈãqJ›¦Ëüşs5¿Í…²ƒı¼ÜmÜrY<¤‘ı—¯—+wù|”9°—Ë·ûóM2}ê_ë•¢Éş\\\"ìV\nÿ+·ÜMtÏÇúB›K¥*3y»pl5š¯óI`˜ÿV)”O÷ïWs×ë¾{OôÒIÿAÍï÷sµÛ¹ò»çã‰¥ş£N&ú—ßcíO}~hH”ı@Gùìz¯ºJyG‘şCc¡şN’¤‹JÓÀĞª`ê·Šê¾FCä{Ğ²U@¤i>ŸåYJé²çôE¤åiNRä(ô9Ÿç¼A%ğ!èõCQş^´yæa”®­‡tš¼¯:î¼¹&¤¦Û‡YÔ°”¿G¶¥¨êHÜ/\ngú¢xÌêsª~Ÿä@´%ñy8­å‘ZUä±EOŠa’c“©@:µ\n§;GÉş>9şiš}™@p,®ËÒı6É&ù¼c µç½F–Å‘^–%aR“AQ¦hÓIÌZÔµ9z\\ÈÅ¡`VŸö5ZØçù¬jo›Tœš¦‘ ‡™æ“Å9!–å­^“§¤O×)1É2«Šòlµ­¤ñ.IŸæ~^ŸçQÒtê’¦uáĞs³ËI;Olº›B‘ÊÀ\$ØQÖæ9Ğüq——	ş\\eŠTsà‹Ä™Uµq2H‘—êÖ¥J'ù(E±Ìv˜ĞT!Ñ%EQFPåùv\\Óo¤™'Jt¤”ßGIşI‘D\"eŒ–‡û3-ç	ÀoŸåÑj×&8E@uTICÓTUP›y3Ú¶¹>L^‰j¤¶ÆÑÂbK‘ô#qš¦Dğ8ÍÊ’\\M’dqşlšî\\Ïy^’Ê]9Ì¤ú¥¶í¾Ü¤ñ4—hNkŸ_¥¦‘gû^ÛGQÃ1Ò)‘PQ8še¥MÙşU…sÕ¸bRhÆaÿÜ&]O5VUĞ·-dé2›ú3ÖöŸå™\\UŸü÷@kWgÿ+Ë·'•gZşÒÙ–E)KJ©³|àqP·Êš=ê•ÒŸòO`[%w´O“gşNÊMÀ—PcüS	õ¾ô ÍC!‹§âï…p©¨ˆ½—×ÒdÑğÿ¨S›˜#Ñ!”K¬L=ĞÊ}ß t¡p›4!ÄÕÇü/kÊÃ¶6ºuNãüdŒU2†8ÅXÂDX‰áœHğDòvOb4IŠN<™+ó„q*?7ÂŠWøM‘!‡£XÉã,d‹ÜÂXN(„Ğ–=çÄŒˆ0£K³v¨¥Ç˜Ñã25FæÌÚÇé #\$a&Â„L‰Sd\rÈÄáŸƒ(Ğ—ğÿFHĞ˜Œá–’ß±2:'Mô‘ğ> ¾RXÚcqàëÖ)ÒxdñCpÿ\rah'ñ\0hÿDÈ ˜‡`Ğãl&&B8Bém.ørAeI©Qş2Æ@Ç)Ã. ñn¬Ëÿ†­`œ»Ç|ËÙ‹F<ä¤³–’ZêJÉ[a(á‰KoP\"4D?T”R†»ágLğÜò‚¨JU_#¥¥Ñ8%€ÿu\$¥ğÄAı£ºSÊ†K’óJAˆ8œµf°à¸¬17ÔàM]JŸ&1=¬‹afK†<w\$µÿ	’MIòšTTº—ÄH&ä30Œ?èé6ls›\$DU™ú\$«Th¾¤IÈúâì[5*M¦dÎbDÚ‡PÎL¥sÕİF\0¿'AJp‡-\rG3å_kìí]¥|m’”%`†_,›8Àº¸ÿs…ºE<2rÁ!Ü;ÅÀ|*ÖTTäÍQ-çtïÙ‚”`<¢Gl·¦ñÿJéi9¥ü³GÆLKËaæœ˜³K{\n=Mé¾+ã1h­¹/¨…:·W\\[×Øçèq\\›¬Kaîle¾İ²ëuï)¤v-~5UL?éEá½D–ª[1Æ\\Vé­6À‰¯6gÄ½fæÎªëâ8üIo¾]ËØh[İ’Á‡Š ŠñR)‡ø‹!ìÎ™ùª¥óË•£şÓW[Âã5´’Kàø'Î?ì}‘ô;¥iÒiŠˆñÙ]à3ÆlšFcşcL‹|öœµ^«5‰;*÷»ÃÇ¸¸Q¿åšû&•<ÁYu/ ¬rÙ—\nÖ-x¬8'\r Ñş1†Á÷)#n9‡ú±Vx%·YdI\ntI€*„‡È³s~m¼7.œÇŒ—RjXÔ+^PåeRª×²ø„:J‡e9{ú#';ì£¥u¡2¤‡Rj]M©õF©ÕZ¯Vjİ]«õ†±ÖZÏZk]m­õÆ²");}elseif($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
lzw_decompress("\næCÉìÆo6ÎC£‘œÄ(\"#HìÄa1šÌç#yÔÜdÌÒ1Ù˜Şn:‡#(¼b.\rDc)ÈÈa7E„‘¤Âl¦Ã±”èi1Îsƒ˜´ç54™‡fÓ4ÒngsIèhM¦óĞ´Ìi:`òƒ,¢·]¯¬ö›YÒÚtŸL0hD*B0\rF&3îìx´™°s‘†'¤æ[É„tv4œíS%òà0XL¨èÙW5ú)ÈY–Ìf®Ò\r^·=~9g0Æ\\@·ŒÇrìQËNnÊ^…ØyNÖj{æo±p®C%ÈÊ‹GS™ Zh¡œ5ZÎ|>:«ø'·ƒé b{“J)Æ“Ñ”t1Ë*uS=^²È2*ã8t(Ú0R,…8¡j:ƒxÚûÀ@9¡\nè@‡1\nJ“­#¸Ê4ŒãBRÉJ Ø¼ËğFÆ‹ÈĞ>#æú¾ïÈJıªÊÀ`Haa„hbC0\rĞ4Ar›&°ß\nBĞÄ54ã\$>À+Q‹A2Do”MELŠ\"ÈÂ48‰©=¡œs5DñLW7§óJÒÍGˆfı*’ï*HjĞú“E£İşÈKÈé1ƒÄ6£Ú¸¯,‹ÒoõLÓtíJ¬A°\\\$Q°|°9-H@Ø0\nxtóVõË(øĞo£ì•Çãí.+ãÜ®âËĞÜ;1DÀ°8e*Áœc\rTä£¨úB2ÜÛzŸÅ\nÅ83=ŠøËK£têÀÆ»¦²u©(ÚÖÀkmİ×…€Óê.ŒÜ¨G;¯+:†2)ã ÷c…n<½sÊ5ª:’6·¢±\nÔ¡)_ğ\\¦6È¤åYL‰\"VV`c\$Lõ¢æ²Ë;Ğk&£…\$ş?ÙXAWÃUˆú4£8÷©	şBÓŞ¡ĞÚ®Œ”æz…Ôó¡©*ƒÀ[O®24<?{3Á6%;Vˆƒ±Vö:\\AÈäˆ	x¡npåxŒ–Fü<ğ[xàÖşğoW.ŠÜèôU[™ÑÃƒsd7”Ër½Ø½F—&	’ÆÉb¨Ã’«¨è]t*7ji®=«dZÖ¹¯Yyô4è:ÙiäáĞe&÷µ¦ÑPèg\\1C0Â:˜’³;°Ş;Ù¹¼5ïCñ n¼ùÃ \\Ü#¨ÒÜ³¾ºœ4{^çÁùÉgÈ7u¿6ËNJ6OÜöC#Û{©!ğ?fşxw!ä7’0öºÉJJL‰48ódpŠÂšL¹ÕÀ€èÃ y”‚pæC[€œ.‡·ˆAltPQ'ÃzŸ¡›l¥=N7¬„ó@m\n/´ñÛcTcí]t»¤×”xiaìD+pÄPÁå„ŠpÆxË×…UJ%¤¸…Á³DaÁ±ˆ`@^ØÙßO©µ¦’E£c)ÅÅ‡@@ŠA'‘Ş)”höUbü!ºAÆ¸Ú ÈT~¬ƒPä‡\\*åØ9Gi¡ä};`ÂË h\r1°éZälx‘ò9H™I)¥Aj•R²WI9!#Ì†“ú[«ysåZb—²-‰™–-ÓqØ‡h7â€LjQ«ÃÊ»\"#lU`¶¦×„^Ar›\räff±V.§Q´Ísnh‚GgÙN€Ôãüª@e´ã%?ãüÿ\$á°‚±IÆ¹É;T\0`ê]¤‚æB\rˆ8œ[ºár2¯Â`\rÒ¢\rCKeF2ÙºÉ\r'[tí˜Ğàá()’ôÙÆ,€ÉM)Ú¸Â Se=*‹±N@+Ü9ª%Piº—¨æn¥:¤Ü‹T}ÄÔM´ÌC\rÓNtˆ†g%VÃ•]‰»V6HÊ’ÀlñÍ?Õ„_Vƒhee,3©Ú¾F× n¤¹¼Íú%^kØa¯«ÅM)Éî_Ìƒa-æ¸NŠ¸B¨-ˆ¯Šv’²…°Ì)r¬J1ÌNˆ¾Uly¡\0Ü#Y´MËµ©µ†ÖY`İEíM´ÖÖ­\"®UH-_€À¸©‡Úã« #ÀÖåÜ¹ıE\"ù‹\rv42Çû¢FLå8¢wZ0]5Sw.•Ù×!…0Ê´QëÍ3E|×%}¹ú«A˜Cè0tìLk3”öPj/æÚókbşN‹`Qš‚ÀQ7Şx¸§‹‚¡_í±åƒ§¦…Bã<«A¼8ğİ@%>Â\nãx[ñ&\røfyÅ îÔ!S‡¡LšsVˆÁG(¸Â¹·	àB\$èaÅ[¹üF\\AÈ8Gï&r;÷‚“orÂ>ê‰bËçi…RZMmğã,¶ÊÅdÃ\"µ<ää;™Ñ\"ÜQÏËî?1ƒuV¯~G,\0ÛÕ£À0c’Ò`ÕĞI’ÙÏ£E¢©PR^fBhU•©ÇuÉ”ÂÒ3”ÄCH¸(& Ôi½:\rtà.äW&5}˜ÙU¤mß—pQ¦šÔNiâ`\ndi#(1Q³Kæôƒ•Ò’¶æ\nÊI,éYf`¹”çı'Ÿ(Óp7Ú`£qÇ\rt\0½ĞéŞUãŒñ\rs „†r:Ä§0Ùˆ;Aáì, Ùv0Øpÿé\r°BPcr“R¨í^m¼.‚Úo\n=¼•\\9´»%F‚¬7™âZì²¬°VØ#àÔå•¥0|Y¢ygfôà?/\"òùÈaMÜ¿˜—Œ×HníĞ¬‘D‘dçæçêjú¤°l\0ŠØnŒAxsÁœ‡€ÚP(3}d#u`Ø'¤„¤¼p@[[¢„¡T*`Z?\\ë!¬„>¤ÁË„ Ìvl¶Š»ß}ìÖz†îÆf3”;„ Ş!(6š‰/&A\08\0¸‚\0r\rp4ìİ{ÂTT/áßÖàîî!\rí¨u§ÜAŸtëH¯Ê8JU°`r°9`´ €j\rÁ -H\"Õƒ•óA¯ÑTH ƒ?¥æõÚş }àqèA˜3úßlÁ~¿aø~2I^€˜ƒ`Zı\0004ù ·ÍƒePÏî&\0p`nÿ8€pìüdŒä%àf\0sïèOäó`hD0\0ûp,ûÏÀ i\$”öÀ\\\0mOÜ[ nDˆ&¬ó`aP\\úp2°.ô0/0ù°P0\nóbVòDöìúdŒû&–ÿ¯Ê¯&€jÂdï˜Ğ\"ioB0\$”&\0a¸ûGJ& @ş &O7@i\$%ï†±\n0/Æbdüo†ı.\r ro<ù\rPå°^ü°Q\n¯Ğ	ï³o`ºó¯ú/\r‚^P}O6@nÿĞÙãóÏÈD Æód— @òğ°ú0ˆü°·	â^Ãğàs\0#\0É\$üğzIQjÔòoºûÂc¯0ÿM˜0O™±¯C [0„ó@ñQÀ@ü`p fú/¦PÏ,ANp|úPNF:ì‘ÆûÏŞñˆóoÔÀl@Úúp…PïÔo@I`c	\$óàpè/‘È%\0ñHÑš”üpuÑÀP±! hï+­\"³ĞA’\$ı\"a_ÔûÏ'/?U6û²DÙ‘Cq“ fdiP\n%ğu¿QÇ\0Àe@pùpÛ#qÕ\0Ãğò\$úo“\npÿDB° ñ\0Ï“\0ì0å‚^óP\"oBÔ0ZúOÏìüñ1	ïa*ò°%âVï¬ıàjùÒ„ù°…¸?/-!²ïBÑé,ÂW\"Ï‘P\nPíEFk/p_\râc+/\$úS/7+/4ÿ_L“qìø°'à`D2%ÏÅ³#ñå\r n×‘›7p1#p…7€`ø³O0·\$o-!2€PÑ½'Ï&02ñ	°N“v/¦ÿ1\$ÏÂioÏ5ñ\nO ipõ<²€¾23QğD1¾ó‘Õ</ù1-âW\r“CÒS®Ù“\nÿ°	\0ôìÂÎ\r€Ø„ Jü d† rìÉP#€Ö¦ÎÂ(Jì ^înêïA N¢4\$”(‹´ï”?\"CtH\rô*†\"®1ep„§÷tox\r|ï9Ó°A“ ãÜü\rADtKBå@ğñÅ\0OCorî´sG`Û/“kôƒÃÜù°ÔEÔ,ï@O(ñK”Fô¢,ïºó’’øRÁ+0şğÒAñ!/ÏP©ƒ\0æ%ëã#sâÿÑ}PPá;\rET»Eæ6\r@Ş*ôh¤UKô0ï§1uI´8ë@^õ:`Âp˜R\0Ú÷ã' ğ T@b\"UaU±´îúdhi1€SR´+èK	OütmJGTÑG…E“Ñ\0qÒùqç\n’ªúÏó/ìöjÿQ«\0R ¯AÒ3ğs.³«Nâa\\¯¯@¼ç\nóï*1/\"aî[/ŠûÏ›0N±ğÿÀÙƒ<°‰ÆñaR¡0QUa/›-P\\Hoap-%/ñ\nsçkUÃÑ¼ôó	•É›\\ö#]6M!Tøfr^\r*%PIZ‘åoKQé\"VE95ı2ğSï\$i+6ñ‚şOšHÑ¶ZïïO'Z“p2ùUµ °M0<¶—,ó“Y1¡[ôµUÛi96-˜PôRÁ8p\r3?7ŞşO'cÒ³`r;Ó``í%°31¥“ÊA4é%¢WhòÁYq¥oGÃó7oa,Å[Qµ7o›Ôû sk_²gi“i,úòrO°i‘½j1CqIÑõ\0/‹!“Ò“]Â^TPÈH¿wM!wññmQœ01µ+’§mR},°Û¶€ÔO?+Tû¯í5pNû²K^·—SfõùhssvÇk71Åf/5cgù\0Ñn@Ùò1zÒ´Ô\0koPI#\0i'E{ÑGñZ\r˜ÿVÏxR`0#!/§\nq¨üc!1³g?»2n³Oj·ƒ„§@pĞ”ÿ@j\rÇ<‘9Oq·~ÿ@e0¿!3P#Ôû¸?YqÙ„Ò¸¾5é/?\0/Úú6tIu»‚¶s c€Di7õ€Àn×şòWï\$÷UmA3kÖR?uó\0rü}WãtĞ_bct±Å2Ï3pı0™ñ×’1rù÷¹;bóVúvû6;%1ÂúãØ7/Ó)Ğ(ó]1W²ñ'Q‘U“1Ñ ² ×v0\0g……ô•tADôAôWBt½WàÑX\"aXu9Iõ<5CCÙT„´EE”KATE4!WÆ6) áRc‰˜ÔcR\$ôk˜cL3:Ô#T4Î÷ğ'€s”úO†°øcğ´çV/\0×3\"T÷4üøÀj)µPÇPÒ%r;ˆ™oLÕJTÕ\$ôr ntá6ùËN²ùe9Öó5Ù+#a2\r'ÑMPo½¯9UûUmQ”ËXÏ{Y Û›quÑñ\0gÄ&ZœöÃO¿µ¥¡õú-[¹í¦ĞÂIZ:ñ:?SÕA—\0†öÚ‚î€` D\" úãBÚã€àmÊ*àæePS€æ…Èj¤Æ`J¦Xá\"´ÉK®FYä>0Œnz¢Ãèª\r:¨ºŠ\0004è3,b,f¢(íªªõ­hŞšÈøÄL¢G‚´Ù3°I˜CG„ó.X¤.ˆ½ãî,¼å¬Â7.jæ{\$]îm²ràmÜV.†åÎéŒ\0Né£Ğ*Šû¨ªêàvuÀÌíàXîH`zONõ/Võ¯^ö ÎödÉ¨€D\0Æ˜ÛhI‹\"ÖÚË¨pi¹ERV‚ªcÅ9¸.ôO-¸.ş›kºîÀÅÏò\0Dá±Ï»™aEûª[ƒ¼¨HLô­\"îbú[ƒCûj¢;ÓEµF9‘¶§¾ôQõ\"ğÀE¿ |”£½[ê2{ƒM\0D	¥Fài…\0h	\0`[ç— E¾Ü\nôsÀ¹³Á³Â\r¤Š0nsid’CÜœIÅø†—”@eÅ¶#eÜRivıÅœKÅünú\\-Ã|;ÀÙûÁ€›7`qH`EÈÃÜ‡¶¼Šò¼‘ÉB¿ÉšCÉÓ®Ü¢œ9É|´îà|ú”zğ^'p	¯c8-	b¢Ò•IP!G*¶@†%¯ãí°:²@ÛC[›	±+Ì; ½\0Ìi²Û(æ,²LØçC!³›1³î“´[HéûPëÛV›[W`ê{dêïK¶Ä)·Xï\\ö·¯i¸…¸›k¸û›¯™¹=dç.Ğ•{©C¬›°®›·×›ºñ›¿¶»Å<»É¿-I;­¿Ô½¶»Ú7\"Q¾?ÂôÁ#‘Ù›ò9÷ÀjEÛ-À%Ûƒ‰É<„÷¼Ã<÷ÜÁï-Â\\)ÂÍ£Ãìü©YØÀ`\rı„’ıœINØ!àlHp I8!¢dı—]éÜ½íÁ¥Ù”ê Ù<½yáº‰ËüÃÌf	ÌÅ/Íèjâ€˜	gÍÉµÎ<æ-DÇ›ÏŒ>è‘±\rÏû±N]Ğ{Ñq²®d2]1ŞtèHFi³¬ÃÒ[C´céÛLêïµ\$_µ›]Ó®ñ¶{k¶àtõ]KÔûy·ÃÕ›†”û‹Öi¹{\$ûœS…iÍ é×=•»›³×û‚ñoñıˆf{ÇØÕ–[·Ûİœ’İ ¤!]©A=ÑÚù¥Û4e™=Ä#ı½F‘™ÜÀñ;×¾İÕÁÄ‰ÂİÂ¿ÚÀËÜœ·Ê+È“vöıÆœMànÀsõNH¼qõüwÄĞ\\úVô?EÈ<>	²!Š'=şøõ|Ró“w„dÛ6ïÏŠ˜°\rwùñÂI?š¼Ë_yÉ¾!Ş)fŸwË3ÌìáåŒ¥w„~ËF´#şÉ…xWtšâª1Ã(É‡ReªræWü­à!¶¾ŒIı…ò[ÿÊ¤pÁ¯›)®ÍK1@Hf‘Á•IèÀ:Q S	°r;5¥ªnbkqTÊ’ÿûè¦À; €Ì‘îª o¡·Ú*˜@r&¡±Glq<\0Œ.¡w6 ñ…÷VŒ™ÿckBh’H(æ´àjOC7y¼C(mpœV\n*1#‘’ÆnÇ<ˆ[B‚RÀÄ9ğ2ŒEÿ€ò… @&â¨³F…(è\n?JÍ¹[³ãŠARğWaÃ¸şçØ»õ°¤v8ñWÄf#Pè†ü:†îk±{Œd`´¡¬ça'³€t˜¡—Íà#™tB¨mC”„¥ÄƒXdr+sP,Adhw²b?‡`®`b[Ê¡\\+HXD90œ„ò¨Ã–]CŠÌY§\$/Xk`„/Á\"‰5½¦|Q`ÔQ²œ\rÆòj\\'CÀB?†ä¬ÎĞ»Uà™Òµ,ÎãF†ü4Š><˜aµgoA,(¹M’Nr¹HkeĞIÈ,T°Ê”*áŠÀ}Ã²p8äÚÀoPiÀà ª”ü™–ypñ\\öó'?9ğI¦ü&øJN„Ù‡œ:Í<ûÏÛ.óc0Á1Ò 3hBà*aø…¹èÇEzkh”ô¡L¶”Ôî—mSÔ8Û«:ˆõYmË©›vê—¯ÔîíÁ{	^İ^·³›UÖQI{H–[†˜ƒ¾!\"µˆÛ•€ë·^ÁîNÁ{«xŠ4·½;!ïË|³ßÛÜíø¾}ğï¡|³äßÇnÅµÜ“vë½bË·¹9İ<³»âÔá§í9q÷§aYôŸ`âcé8Å;‡o\0fÒ1\$€åo@5Œ`Ìã} ï7¡Ê@h‹³‘ Æ+‰((wq4i”à6¢x=Ñ£q;îÊ'9”Q¬d#„” Å¿µNùiyq%>åü/¤ŒÃ£bã7Ú‰\rÆ˜C‹œlúĞFTó‰qTa£’âxË€Éû/£Œã—N¦ñ¢tÈ‡_¼^eØ\"6<1ãSÇÏÆ¼<G_+AWcåÀhÔğ=»x†×¸ú”Üäˆ©½L}¢­GÌCç8è{ÎPoØ,œ†>9[Í]Ù˜h@PÃ‡=¨Ü‚4H÷~B\$áÂN&4¸şŒÑ°PD,ë ˜ö\0âïƒÀÚVÂ@¨ÒrjèÒˆ\$§>:D\$	_Z¯#‘×¼r;‚ìÇÊzâ4Ò'Œ|A ÀH‚APIŒ}äDq¹*¦JÄ8p\nò EQ„üëá8\nÊË  \$m`)Ø¤ŠW©0˜ZI&ÿjiÀ¤2J€à†Â²‘[Q\n~Çı©b·ŒŸ‰@xkñXÄÂrQAzbƒ\$’+ 	\"ªæDå\niCš>\nÒŠ•4ŒÄ)ÉîT’{èdÌæ•Qh“`{’àÉ&oBr§‘,¡B(±–JjF’°‘Äd›!\\jZQAOŠŠ+ˆ2•T©‚,„­C´Án•|Sbr'ô¸¾@6| àm((vÃº+YvK~]òë—P@ÀË.ÙsN_ï€\0àò\0_aŞ—ì¿å×03œËøòú—w‰ÿ0°äL\"aó,y«ÌU©wL_Ó˜(ÃyL(¾ƒ´„0™È8v†ÀF™`[.Y~L(} o˜DÌæjP7ÌB^ó\r˜L¿ÀÊ%¹„\nâf“<2\0¿4)„M3sG ¬ÒLˆù›Mió?‘_ÃrÙ˜Lj_óB-ÊkËZm‰ŒÌöj8ø‚«2Í˜5ÓRš4ÚYóBJk3bšBeÍ1Ù™Í–ló<˜ìİfù0©·B9®!]æÿ/ñøTG+*øÊIq\nÜS—0¦@Ï.¢½ÍB“˜(¤ØCI±ÍsÓ›^æí.ùÏÍ2tRæLég;:ÉÍÍ˜\r…—<§ßÌ²e€E›åKòÎjt™äêf£:9áÎ¾u›šDñg7<IäMè(<tğ¯9rNÜ<åÊ™tË€79ËL ëÀ_œ±cÌÉ1yç¾g“ãœüğ§­>é×O¹¶å±VgÓ:÷O6z3²,xë@ä1b‰M†…_Ÿõ\0&ç0õ\næb@sıgNéãËš\0eDñH+?ÒKPr’ï˜xnF¤UùóÍÎc³E |Ø¨#3Ğ’póª›@æ”jÍâg-¡yuhW?©»Ì(\0g¡TØhe:º~‡t=¡ÌÖæ›B°6•ìÈ”1™•è‘D¨úPúaT8¢u‡Cj)Kş„óZ˜Xch¯DÊ#¾Ç8™X€…FyŞË¼`ô-£\\Ï ¾©Q&h _£|ÓƒNiĞ–<¸sa-¼¼fÂÜ2¯NàµE¬-äÃHß>`0™–k-{›8É*Ip¿Cnt\$£„Øhæ7BêÒV\\Ô5#|Ç(yDºMK¾T ˜E…9IÙ¢\rB€æ¤9\\„ÖC€²„S\n¢%fgJŠ,Ğº‰t7 ­+DuJñ6Ër~Óaœµ.'`GêP‡zn´Ém-é™7Ú^Ó>‰ o¦5f¨‚à½•|=!PÁWÃxTâ½`) ¥ìQWÀÜ\np(«|Z”ç*?‡pÑ¦\r@kç©O!.†Ä3a¥q]\n®'\nm¸=Ah„î)¾šp„œaU½9ƒNˆ³Òsƒ§hgÚ•Ha£5x/ ");}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("f:›ŒgCI¼Ü\n8œÅ3)°Ë7œ…†81ĞÊx:\nOg#)Ğêr7\n\"†è´`ø|2ÌgSi–H)N¦S‘ä§\r‡\"0¹Ä@ä)Ÿ`(\$s6O!ÓèœV/=Œ' T4æ=„˜iS˜6IO G#ÒX·VCÆs¡ Z1.Ğhp8,³[¦Häµ~Cz§Éå2¹l¾c3šÍés£‘ÙI†bâ4\néF8Tà†I˜İ©U*fz¹är0EÆÀØy¸ñfY.:æƒIŒÊ(Øc·áÎ‹!_l™í^·^(¶šN{S–“)rËqÁY“–lÙ¦3Š3Ú\n˜+G¥Óêyºí†Ëi¶ÂîxV3w³uhã^rØÀº´aÛ”ú¹cØè\r“¨ë(.ÂˆºChÒ<\r)èÑ£¡`æ7£íò43'm5Œ£È\nPÜ:2£P»ª‹q òÿÅC“}Ä«ˆúÊÁê38‹BØ0hR‰Èr(œ0¥¡b\\0ŒHr44ŒÁB!¡pÇ\$rZZË2Ü‰.Éƒ(\\5Ã|\nC(Î\"€P…ğø.ĞNÌRTÊÎ“Àæ>HN…8HPá\\¬7Jp~„Üû2%¡ĞOC¨1ã.ƒ§C8Î‡HÈò*ˆj°…á÷S(¹/¡ì¬6KUœÊ‡¡<2‰pOI„ôÕ`Ôäâ³ˆdOH Ş5-üÆ4ŒãpX25-Ò¢òÛˆ°z7£¸\"(°P \\32:]UÚèíâß…!]¸<·AÛÛ¤’ĞßiÚ°‹l\rÔ\0v²Î#J8«ÏwmíÉ¤¨<ŠÉ æü%m;p#ã`XDŒø÷iZøN0Œ•È9ø¨å Áè`…v0Cñ9 è8aĞ^µƒMè4­£¨Å\r¡|Ğ7zF[\n¦ƒ(ì7êÙv³­êIÄˆ2ê²,9™ÃŒü#‰–È¨DWÂğªR;…ÂP¦'‰Áşõ¾Îâm©Ñ3ìƒ‚†\n‹\0u­3“œæğ»9ñCw§Ğ2™fƒp{¹Ğ¸Î/Ò	o4Íax¶/ƒ \\.…ã=NJG@º£YE«9åšöaİæ³ß‰mg[Ó¹„ş=zøùŞ{Ÿä4\0æ1CHà:¾>Ôû ×Xö>Û~]\0d‰wŸô³áßsã„!î:%ãÚ9„~òa\nåüj0÷XÑuS¡ôÊ6²ŒdIÈV3„Ì¼r`AĞD‹@’–x9'²A@\\Tƒa3((Š\nPÈ`ğo{eÔ9…¸=	`´\"åÅ‚ˆ	[B{!ˆ:¶x?Ã)'|fì8ô¨µC ?†ş†PuËÜ|é@ÂxR;¦U\0(.5Ê“joN)Ì/97LçA#8á¥eAÃêCA0&#•·«wKÕ¤RG¤îˆ­ZaİªHà	UŠêİ‘ÅpR–b`Wô’àŞ°J\nË!¬MD‚xkVñnOÙ(#üŒòyN#òh“Ğ\"@œ1Ê\0Æ‰#8L2Ú\\r–Q\0r\r±Æ`ÚÙYávb+‰ƒ1æHl™`¸ƒ%³zä€.™ó ÎM »5lšë­NÀb\$Š”2ÒTxH´¤ª`XH§SÜWŠmb)§'>'z€Pî­!ºĞ¸@?VÎÄ=ƒ8\nÔ+µda¡0Ã™7;‹¤É¹ÕçÏ*ª~êµWªwÜ•Rºß*ˆT8CÒ®]ãXn¥­¥'I2¦n\$²Áp4ŞJÉtXæº<¡¥›>àyK)qi%Å°4>àV\nâİH{•J†’âîj“¥1}‡6œ„›JS¦PôC¤¤a2¶\"“¾}³s(ÔñÚ™á^ËÉÀm*çD9Í©Œ«É¸uÔ:ŠAåy¯¥ª¦Tê İË©LÍœ:+pË^‚İVÖJG‡»ffüµ”5¦¾%©y	Ü®’†ËYŠ­[ëŒ­®[¾\$EUYZ•u¢ÓJĞ dA™‡6€[-CHl€‚©)Êƒmì=G¬fæ¥²Ûc‘lŸµ•RĞVùÌ¦xU UÕXR¢«\$i®jÀË»G	Ã2Í¶¦í[T¯'\n5s´á’FYËÍh¥½i\0íX©%_¦€!ÇÆÁÜû\r€ìEŠº¶44Ôô¦•l”tm¶ªÍû93l¾·¸S²{÷SÔêËÀâü*g8¯³\$‰\"kÊHO)åJõ•bÊµ£p¢%\\¹¤P½Òs #‰Ğ)àöà¯B:aĞi‡‹¨· Z^èş©\nk¥©»‘Òv2€°—¶Fì±ÖP“ÙJ²¬¬Ê˜nB¡Ÿ,‘¸Ü§f´·­XÀ5’pBİ`ÀooèÅ’êIô!=8ë¬)ÒcÍa¤ØHã¨¬‹ñîb(ä›ùåJG•aÊÙ«\"¤‰BéF †ùTƒá{¦á‡RËõp*Øe~2Ò^ Ş>v>…ëàñ—%¾^1ò3ğwCX=¾!±gÕT–e=9¾p>” 5;µæuÅ•Òä»+ˆÌ÷…™hMœWZA\rîÄ—µ\\M\\ù3gĞœ÷[Yµv¯qX[»bÌ)¹2­\rûÚWÊÂL+ÅŠŞ¦ez©Íø•A|eÕd9A‡eªÍĞ\\ap.‚@_†±ê¸³ˆ~cpúë¯%-›¼ZÕW[îQIİ7¾ E5£¯uşÈ”[6%PCioöŞâé[1A4ƒ KD¹\$ó«i.	?V¿{ù/äÄ_y]ÿá@£­^ğÙq&#—\0êO<†‰î/¢ÉÎæ‚Ìq\"ëæª­\\WœÁïUHœïyàêWtj¦ºøVÇÛ{¦nåVÈÕfÉ+Î¼tºâ,ÔÄŠ^g:ÅLûUiİû¯šİ»¦e*ÇW•ğ“Õ/-Ú%ï›ïå¬\"8C^Äó¥¯&(E\\6¶Ô¡ïØZN¿9yôñsÖv´é^/B¬³0œRZšû®O\0‰Ú;`N &Q Ÿî;ŠP2×Î’+ŒÙş‹=MŸ tÛZ472ğhLW œ •o†K'ø&îÿ zıH»àÜ§À j†˜?\nRDG*ìÓ‰’-°#<¢ ¦ÊIÄö€÷,P¥—,Z;h®®„\0Ğ#€ÌîĞNËM>I`œ(Eˆã¢4\r‚T]\"êĞV Í\0°XQĞj~ bR`M§ŸN-àRSN,¶\"Œ0 ê	ædéŒ~¼ÆæÒÌy\rœ«\r@‘`ÂQ-\\s\n.Pèâ)¨ÀÌ]à™\0èpÔ\rğØ]à®TÂÚĞÔÛ*}\rpÚ#°î‚Ú-àjE®¾‚mp!É¨f@Í@îW®<cmXÿ„v»ğ¨	èz|KJm\0ó@ß‘W Â‡ ß‹¾‚€‚X\0¤ĞKl<«ˆ!¨N¯ú*€äĞGåEÈÛCÄZ«”§oâÛBñzš Ğ¢åê…\rÑxŠéËçGe‘œœˆHÑNä·H(gjñ‚°¯Â\0x‚‹©oÂÀ÷K@ó11lDÃ\$Ğ ¯²`Â ô®Dv Våü bS‘Ğ³H`‚è2¨®p¨nâ·.èºq´ĞQºJ…êŒñ+«õ*fÄMp«‰ö\r Ûí‚/D*÷ï\"ÃÅw\$o2úç[QéÎF‘ó`M©|î1ÒRåhÿo'&‹5#%jà€Ø>¥ÒjáM¦Ú¯ü²t›è@&rpGdDÊbtsRí\$r!äNº2 qo©¯ö¨{*,DŞÉ¯brM ÛàË`î.Q—Êq°ø§D%Ñ˜İG	!J‚’Õ-’İ¢©+§EDE`{0’ä§©0DEÈ#Ü±¶p(7ó“#2òÜ³bŸd¢óÏ73MàÏ&åysH\"74rêLÂ9-f*Ü‹•/r°öÀÊ÷RÅ˜øRúÑBn*ijê@ònâBèŒ´è€\\FIÒX%x f\$ó‘9J}9 aÏ0qsnFÀĞÚH¦|@úùöE3};)k7ó´|H´²–óKK5©´ÜSdvR†@ñ ¤ â(„ Ú\rÀš¢£&Êî¼ĞÍ*Ja@fp¯’+J‰k­,Ìw _?\"X\rÎN²‚3'5&¥t%«;A´8Lrn–€ß?E’QÔ\0%¢NSkaóÇ8\0Ğ®Ô<¼ÊDU€GŞ¢F8VTDWe7`¬ÀíäçH‰tı²ºÁH”Â‘Ìn ÒÇGJl;HTjN`¶²@H Dsò¯Ê[t¦—ÏÎ((Hš”ex¿Óm7v«yIÒ¾#T‰=íÑ>(™)‹\$_Ô§OÑOQ¤-eğ(*GKdËK´¾„ÄàtÈÂ`DV!Cæ8’Š¾sîDàæi<)3ŒØBıÀèKU;8S é¢ZBó„(âŞ.šGó…°¸ÓDƒcè,®šEÌuTãp‚dl¬7J×ÀÈ3…8•D“KÄ\nbtNSHUt# {VluV¬ZuF\r@ê`ĞÄ]6äZÇŒ˜Ç€][•½\\\rf(0ÁS€åSÍ4É3¢D³—'³W†:S˜ c:¨âÕ Ç…‚b)ífÕò³µ´Ä¢#éÈw5–v›s´,øßrw6&¯ç«æ™\\m0Á\0Úœ\n¼JÀßv6Á6Kdã)cu&œ…—Ré37NÛS¿X€ó5>DUµZ¢:Òõ¯Z'rTµ7Sµk_G[Ä¨ê\$KU‡Ñ\\Òx«¶<m5ÿ#Ã[E¹¶\\Éí0W ¾J*.\roø]à©#€ÊC‹*œÑ5kŠÿg´‚|“«< „Ğ-uĞSŠÓO7TS“^“¦ h‘oösp fœÑÖ8ÉI\\•¬#µ°„8ğı_R{pe'2ó=¬èS@á[·:Îe8Ò•Ëd3İ5¨]r¢ÖªôÎÖˆÔ&DÔe\$ån’W[3÷^‘ö«vTÔ–w)vÜ­´…°X0cmasZ »xmÀ´×Œ§×‘Py¬yz:˜¶^2–bá—9”ê…U¶qo ïof>¦\";­K|3yVh÷ÖlçÉÍµ	0Nà¦o.¯©¶éË%²ÉÍ@OGvJôï7M(€A¤³²,¬‚\"[\0¨×/, %‚¸…ÈÄª³‚àÏƒ*¸«2x}Ë¦š‡²àšgĞßAï!…,ÒÊ,¦ÍÇ–²:&\r,«3_-ŒÔ‡lÛHøo‡8vwSj)V)ñ(ÿÄNÔøS„XHóXMƒXC…x§'/A„XZ^©€|fÒƒi,Ÿ†˜†Ê¸ŠgØ˜®[»‹âÚ­âª@QbLxJ¤€‚‚”\0Ø„j•S æZ€à	øVB§\"ÜæĞ‹c<Ğ¢çtP5uÀÏP\rÒÜ¬‘­‚m„Ò\"ày‘B¡“C“ƒ“ÓèÚMÅnŠåwô^·ù1¶ ÀËNïjî¿x.øñÒ/*nëtI’\"î2¾»‘\"ñÓ=r›–ø\$:kwba.è1>3h(Kom‚5;õ2g†|B\"iºDÔ\rgÊBàX%¤‹ëÌr¦ĞVÍ\0nà\\òF†M¦Üm\0~\\…Í€™ÀáŸ²°ã4`‚.†*\0@Ô'9ö‹ú š¡º dæ	£{¤6êç ¨\n€ Pë†¾IÈÙÜnË›ÀñYÅÓ[¥VXm^oÌÔ¢Åê?g§¥Â<¯°Pÿ@N”ˆ/KÚsàDÄ÷©eêP\0xô‰©Ç¤gÚ}[¥sGä¿¥š²¯´S5ÀÊu²%Ê%Eúø@÷«Ä/§ú¶OÂZe{¬ÅLY)¥)¨ˆ¢BäR` 	à¦\n…Šà»QCLÚüyh7Ÿ8É3\0Ë²\0‘6åƒ!ÀN\"¾\$€[ŠhÔ@Ìa‚àğ ï´àZ™àZd\"\\\"…‚ÈVg²9²[)²ÂhN`°ºmèL°î-¨ÕŸÚ·fĞ»d ÏoXºh\"ÉkÓ¸sĞJºõ¹\"m­÷2ËN2T.ºõ¹uº³ ÇœyÊp›' c)(úİ¦¯èşÏğÿ[É¶[¼`ºg;ÀSZJzK”¼&‘jZšOpé¦°	¦É§@ËªZ©Jzœ(Ó®Â@{­úµ¬8\0_®Å­½\n3¿»²ñ±¤/|u¥0K«Â:IéœßVÂü:YÅ†EmšR‹fän†í4fşo¼wÇ¾‡1¾Çr’N„÷È2sb0£Ù‚<¤dî‡±ÑÏÅ/–eœàÂGf­èôZXÉŒ<I<õ•,b\rÆÇ–®K\r¨ÚJş°\"HÁ±½+qÀ-w˜×P,Ï\$ß«%U¤²¬Ê,Õ˜@ãÓ®LiT0K2yiÄ³=|\\9wÄî2ó‰BØÀø5\nÌ\ràùef˜‹ˆJ]åíÑ³ WGÔåyb'H¥µ¼Qû\\ ª\n@’ Ús#pË8PŸµª}Ö}j\rıo–‰×`\rE R‰MMHJçW ¶nt€Ô\rä*NoÄíæCÎMCÈÀBğ\"€_ÑHëkµR¢…N#]¶d<.à¸à\\Xüe&_G–_Ü×n%—Æ9ÃÆ›Êmâ4ù\\Veg§¾1µà'aü’µÁ±šoĞ\r5âp|[\0Ğœ@Îh ¾tÀP¦bgÓ¦|)¦É–æº l€ {)×ä~ä®ø|†åÌ™×§sz1vtÌFF)àXÎA4y/g—!gğ»hNq•ßhí_jÌ’Óãk+§`¢0ÖwoééSkW~,îé~€ÎP<3‚h²¾ÂóÄ~¯~DXE‘	\"Éºƒ¨kRÉbö\$ıáBİIí¥!IYx‡§F%FTg±6â-X/×\"öRmdöûkXQ‹`ÜFS‰¹å?V¯7UĞ²èœpĞ&ğè•Ø¥›sx@oĞ›|¶˜?MWµ`êié•àÇøÆHBCÖ9ê¥šÇeî;Ú¾ô3…;HP\"Lš5	ğê#´…™^£”Sâ,›ùÍÔ5`Ø#\"NÉ¢÷³B,\$Ÿ¯÷oê&•ÁñêÉ‘n±;xZ- ]‡>¢šÃq1 h€e:¥\0\r[\"Á­*àbwÒ&»¼Š³6“Vr¤ÌôÛ\n¼>ñó¹‘­@â§S¨·Èš”ÑÜ‹ü>,¦43šYÚÜè\r*ô‚ˆ=|ºRÚ0°£9ıil Ğ!SLªõıÁ\$Á˜`dN ÜdíïÆ¨“% 2Ô5Á½2Ìœ(ŒV¯†Š¸•BA ]àE ÄÉÖx*’	dFÒ3‚€ü8–	{f(^àıö¡>õH&Mf’^MÊÀÒ™…\0]ğBšÔÀñ‡\r	ïâU¤•º°ñ*…íôn‘«ŒPtŞã ù`'?\nİ|,`:Ù×ë{€©İx=öè8ÔónHrFÆ§±ZÂpsO\n|Õ	QN¡…,ÊO\\)+\n‚¬ÀŒ Í.é)†©\nhYº0¶,_¸\\‘n/\$CÛ„X\nP(\n“`X,‰Í¹¡¨MpÒ¬§­+à+@ŒëÆŒ8‘Vë5yÈN@˜ñ–'ô#‡dHàÓ\0Xƒ¡–=	¥kÿVÓ õ 	½ªBAúPÕÑÃæ\r(‡Y‚Ödh~‡ìqÏb	›©Ò6‡ÑÆÓAR\0XğÈ‘gÄ|–¡“\r`Á\\‹8äHÉ.§Ş NJ ËÒq°2”\r¬Ô‰2±[PÕ ¹CôJø	ÄV…'¹‡é±KĞ[ÎÖÈ ±H@€KMtî áT †â	ÀVÿö=!ª%'ú \n–V@èŠÎ!Så‰~úug¼´…DWmÉ—É”ZœR-«kbrÂ †iÆ¬=éïÙ08AÕdÃLƒ»ÆàòYRSœ±!Â\"rx…YCĞ\0ÄCFŠŒ‹Ï^®{xÈ¼Å¸»•ã¤³³\r\"ìÅK”Â=;Aáã\0t ¹Làà”\"×•ÁEÑ§ŒX#\nñ˜ÕÆµ/¹|¸İ<CW¤t^® 4ëvÌ6ÅR\"–te€d)˜£^f6¾¦Ìl.›˜&àá`G5×%RJÃMIB™¾Ğ#åŞ9ñT£­ò-Æı•D\"FıXÚ1™­Œø~ßªJñ.-jb‰`ì{Êh³Xò'„ªQâHtzV§°2G´¾!³¤|K* ƒ#GÕ­±÷~cÈ÷H1\n%ß`ô‚d3©’ŒóbYxšºnIÈ•`Ç·€y¼IœLhB\0€=Àl!A™zSºœ\r®MHb\$E6FÏ~IŠÈµ^Æş¯aKùÌ¦Oj*aõ*’Ï“tÀ0-2à\r|2ş@„~©Nœ˜•F6àÄš('`\"CPE\$´ep7—œR^‡@€È&Æ/\$óRz\r\\vLÄÀé?m¶à\"6€¢ İ-Éè\n`q\$Œ™ƒ@›I©!Ò„Cì›€ó'	9jKòv“Á‘¥(Å6Ÿ´\$òŸ?[€5ƒÊ’#“D¤C%)7Å¦Ü‚rŒ5œ§N—*AøÈÌ2¸ì©˜<Bc-IğRò€8^€Â!QV]CFBS´/Õzc¨&à@yI\$øÃ35°Õˆ‚l²Š.HlK1® s–\"¨â†ğ5œ\rÒ¶”´—\$ç)¹kúQjJÜ€E¡.Ğ7Év^\"“ °Rn%™=\nĞt o‡8É^68@Ä§É¦Q °M‚˜xK8`0%†T3 Ãùø”i”qÉ Â:ÈãE„Â\\ád2Õ©I>pÉÒ\r†8øY¢Š¤iÁ„]“UEÂëd`”’Ô§^#2¹âdE—b\0cæL/Y”!ŒO˜T¿şÄ¸Ë¦Üà‚¸ ¤ÂehÅÍ\n`jÎypxB`Ã°+ô@4TØ€ig²‰Î o\0Ä^Pˆ·îSRum„Ò‡(é›gß™Ü¥¥;5yÌ´9š,ÉQ4”Jœú9qÚ'X‘İ¼P#ƒ	üˆÅq8¹?Ê%Á4&Ìæq.YÍ¤:qáœŒ·ç)v“5¬!‡ï!È…Ék8ÂÊ€œ Â{¶§\$IÊKØ®QÒ‘Ù–Ğ¶ ñ't›0~šKÊº¦Y¢æcH) |+&Z\"ê@\nB  DE{À#eHL\"V‘§ª\"ûˆŠy1 ô¤Šj)9Ø¨ÓÜ6¤i¢Yö>ù…sİ-h©ĞƒÏ±»\0^m©d´éö9iz#'EÄùA@)ùaX ãQ0:FŞ0â·ŸCÚUnĞ›ş[bÎÃÊ³Ôä+iHL9c‚vÖ-†!PÊÌF’+d(K\"¬Ó‰¡bƒÔ®dõ‘Æ„’@•Éû_¡C‚ %|Ô;PxuÖoxsŒ°wD\"GG³cÍÌıâoVè\"<ø@2} kpÈ%°‰\rò \"\0G	\"\$ ±¿Á4‘ÀÚøCê™xñŠJÔş\0ña‹yííßØ@šå€\n{C¨k±´MtØ4…Sï©# ¸ˆÈ°âU¢ph xd­¿ü±ŒŸqcéZ\nG©(ï>Ã†\0¸ƒãå° å¥ ëL®k•ä\$ƒ>`@L¬“Ç“;¼6A-ÈÀZâ\$yÒà, @Z~V5rÃB÷y° `\$¦+‡©?I[RT ?¦¸]Ş,;Rœ\r¢ü”e)Â(Ğ.5P%Té§éÊğ—S¥Ú ÓéÎğ5Š\n'í‚¦šU¡>›®[€:€ÇNÊr²•Aƒ¦sJ±èQ03õéÒ?0ÜT,%CU5DŠŞ;š)QVP‘óÅ•& 6ÑhB´\\£íHªVX0>¼XA1†[ÇI0Ø˜ÏZDWR4ÔÂkIô¨,¨Ã¦RzE°'Q;†éâ`-4ƒêx¢¹°‹k¡«Å¥bª¢tÈÕTx`\$A(Ü—M-„âSÂLôe\"ÁÌDÂa©1T\"Ä-uÏ1¥É6Ë4yQq¨f\$ÆŸ¬l|“À(	Œá/à‘İV‚ìæu‹QkñYÍXø¦&.‚°U„8´\"	j%fmªeå`syl%ù<ÚÌTARMPK¢ç‰ 3Ï„ßK1ƒ¸àè¾´`e)©ˆ¨Qá•D@7cN¤HpOVah èâ öP®JãNp¼Ä\n»3+¥0šéšgê«è@ÆúQƒL-SHìÀß×<fM'%t…Ic\\À-…ÀQ2êÅuÂRjôÂerÑsœ´Èş+D„”ÌPáı&ı{ŠÓ_ãu¤*”õWtâ'–itaWÏSÃOTş«t D[Óå¬©ÓÈ-é¯Jkõ‚\$­Ğ-°%¡ÿ•é¥~bZzØ%6 ±Cş¢ E„(Öœ“,µaw¬Ië][…VÆ‰BÒ­v7ää²\nÛÕÈ¤ŠÀ¤q±M‡È[bĞ.ÆWÒ¨N±_Y•dÛVÄ(‡,\\l±b»'ÙpL¡ÿ²›ìdNtd²ºÆ¢ø/13Ù Lùí›Fx2ˆŒÚAäp¨‹ÈÂ~Óê‘ó1*æ¡(3 ³T91ÆB\0ğ½×y\$Ça\\9?hÕÊª£…j5QØ•4lÙä˜Go:-`„p´ŞO‰Dª°bwêŞ]qRv=&ŠK@@Ô¯j\"H°AMd;zG@èKZì‘”ƒYqTòrC“Íä§™le­‰jE²Z´•à«Y1íŒŠôtP*8ô_–Çn[8]\$µ¶ˆ M8Vå?.P°EÍjCª\0¼	€IX@ø \"¶\0P®ñ\n€R»‹dŠöİví’YL3(\0¡:¦»)m¿iÕ–i‰ù	94-;PTém8¶Úvİ*¡Dı•ôªC¶`\0ğûaœ®aù°ÀøV·î\\ € tAˆËNˆp\0_	\"\0øÃ››ˆUÓ¡,IÉHŸ’ÎÜÆÓö-•lÂmÛ>Ûª\$eµ[pá“Úã*`Q)Sòk»•S`:Tb¿;n+ªÚ’v	¥wˆ<v“à\r–äO¥¹î2YÛˆ,Èªo=ÊœD«t 8QÒÖP¸Àæav«¶fí×~¸}¤WÄ#±e‹L( )'#ÁŠ€›r›\0âÓ'è	ÀBó1G,­)TJ¹:Iß`áh'õÜBd9ÕYP„*úÀÂ(\0/‰ìW\r¶À€\0W{0>]öÔöF(cK*YT5³¾F²ZŞ‡xâ0€Š=pÖü[ÂË+í+O²€.úİ€éV’å*QFo)‚¢Z¨ -£AQ¥Îs­\\>Û?–>µdà=t¯¼g1Z2¼LÍá6\\„ÒˆxşÈ2¼X+5Ë€ÎŠ2&nÿI\\¬•;ó–ı;¿]iœï\nÔÆ”BBN)µ@À=„xƒÀ)œèKpéĞn2÷]	oôÇ©¥`AKŠ^±	®p+g»÷V¡AìÆÀÛµëW‚GH¸sÂ>!àØ§tvà4Šƒ®	îÂ3\\ßë	`nK]„’Ø,!·ASì|…„B…se`1—'Dbä`bdõŒä#ä6p¿.aym\$=Á‚¦pXw¡eufVÁİŒ»U­H¡™†¢‚£Ô°%#‘Ì½\rm(‘]Ì\0L¦F8ÌrÎE]ã°áß\n2ø‘Æu7i¦fX—u&&pJ´şŒW†ËÄôQæ–¡ /'ŠÅ6ñR–ÔÑà.cx Å…rUsEâÌ¸µÀ2_ñU‹œN®ÕG\0\\¦-úQ–p<W²3OEN€¾!WÁ‡ÇÒdb2ÌM¶£,…¢{ty\0Üg\"ÂÚ®àF„(>—e¬—%@K\nš›“a¯ÑV÷rÃ¶)ÀÏ¬3´¨U&•zB­,8ï\0-àú§\$¹ó¢ÎTWrlÕRÌ¥YèQcpŠàk÷ù³Zÿ!_f!Õ_ “×(™æ2Ã°€ÜíëQ«B2Ùš²*¶‰üø5\".4'¶=²½šå¯aÇ,ø¥¾*h[]íè‡…m©ÈT]J¹€´ ;\"İ³ë²‹^€¤P*…°n>0eÄNw³Ìµr³µ_â¼ÊÒWÀZ\"8Şë+J`Éø»R•Dë8b×·ØºÓªñX´.Á:Uİ\$jdB´\0öéApRßsqP-€È./F5`UìBV‚Fš±İàsŠz‹3\"ˆ¬¸ÓÃ2õ^…é`05\0ò&E*h‘Íræ4`j`eî®û{CõÄÀ0A\r´˜M/L[Ôs2õ´»í½ÂQ›B°fÚôáI½MßsvÁA*åƒ(„!±2ÛcÈ\0ï/Åp·só˜±,'Í yÊ’O\ró‘ÙİE‹Ø›Edñ2›q\\§£âñ”§ÏPò­r½>·Jğ£®•˜ Şi>U¼É}É¾Ã´'çè_ 'Gê“ÌÃ¼®Úf‚ôøÑtÌ\n†’ÓŠJ¾JêyåèPˆÛ ­©ÑÁTKU1Ø4€2GL+êŸWvæd? -ÈìÒ^A¢Ôè]9Úº¹CI£6NÄƒ!Aª#/¬ñ¡\n¢hb,¿ĞaÛÏ]¤ş`àº: ¿‰–ª³¢ƒ®Œ­˜×kÃq³`é\"Ğd@è4c¢4¡—¾â*˜ÒÒI¿¤ı35‰¬FŠô[m*a€³F\n9VXWDX\\ØAñÍü‚ß«L7Ri¢ØŠãÓ–à.·:mBDşB‚3]íA‡L¹Ó§j²ëú?#be¿Ğ€“XSÉŒ”êYe\"ıhm4kœd£c3ÍõQãØLşÈâ3¡M<C}çé®€'¾§€ïyà’¡~FÕ¾î?±‹€;¨ÈóB\"F…¶oÀ_Ë\"Õ²Å®ïyÈ­¥•M8œ¯ˆ°L^¨ZÅöj#Bú zÛ.tˆ„N‰™ĞBiÈ¯P.Cşk\r~éáÃ¾-Ó(a\"ÛAæ%cc\0B'4Ë×ÒA¬”)‰#G&¡=Ú£î©IƒªƒA€\róMe“¶‘@3€é5‰À×x}Š\rãc‡	&Æ8‹²ScZŸEú_¤nUJºI~g9Â3”ƒ¬\\'±NÿKüZŠaƒË\nüg\$I€Èö‚À<”\0Û%fKµ‘¤­äék®rÊFgß3&mIÎ;R»ŸşY!@o†Á»N›!¦À12 &·=®ñÎ˜¦´àp*{hÛPvÙ¨°fûM.	c§B;·um¸×;p~ßEß¸:‘D‘äÙÇ‹Mêö#Ğ6€67Îà’4Jµ´4ÑÖòKÄ‘×vÁ“4bÏQ²¡ÀÈâğ‚3¨Bî·w+©2[V¬ï?&-sÉ£±¡ãw%ÒgçGU^wiuCHŒ»Zö\0±Ü;r“Õ£·À6íø#9\rÜNŞ`¹Ôî|aØ¶HM¼:òï•[ÉŞX‡^õèÖxæñ	ø)v1`1ºe6àlu<#€‘§;7àcHàg.nËJ€mâÇ¨İå^7´`/Á-ÆÁß~ME¾MÔngu`p	D	·S]Şg€ºÚşL€·À‹ÑvÅÜ¡·×qao(÷ØàÍoÀû…Æ?ß¸wÁvx7‰Šóë˜p£!0Î”J °ïtá¡Şfİ!¶½êoi¡¿On¨ç‘+ì›eåÊÜ_ñı‚yŞÙ’	­Ä¦áw£¸~\$–W‰oQâƒ\$ö>õ#un-F˜iˆ«w¡-Ü¨÷\0° .ëÀ«5÷AëKÃ5/¼ÍdCÉ8¬¡Ò×f»™hËïk¡Zë»JbØ˜—·z¸ÏQ·¡Çp…ÃV»q°\0R'	ÓM£°á2ÄïJp>(°B†å\0ít6I9³1sğ~H÷\"+g¦P¡¿Êqí„£-8]ãñ¶yı2{SÛİÔaò\rn)k{hf)İîF·¸2¡L3ïSQg|æGZéŸLÿÖ§J¡æé>³Hß>X?U\\îÖ		÷Å’„ñ~ã÷	ô%\"ÿ5›Äİ–”ëŠfëº-¶8òB,v¿0ã93€­¤(ªï\\8ä ºàgâ}P@3Nei“¦ºU”Hú§äšFğÖ‘<	ã¢ôË{¿FˆÕ'€ôA)qä#S=p7r@ÿˆ›DNº¹	ÈŞCkÁ·}™E5’z0¦¨˜”ƒ3J™+\0X^›ï6§G|ĞõPÆg¢’Ä¦ÁP8ÌË»¼\nv½|(NòGÉR>i¡–Îê§Ö ã‘Z/€/@œØp>[­°wfv^N¿^Nğäóà­XÇ4L¾­¸o«½}ëIZü‰½ÉEÁİy¡‰z{S+ìÈäìxAºÔ% ğÁ\\2TÏe@İ«Uıëåú˜Ö¾Ä  °¦Ÿ|h7/ƒÚfgDÂ&WÃfš^´€ˆ}Y@››8À4Î³§ÇH‹tç7êøÙ=†à™fÏººzŠÃyà›JßcÜEöï“º2ÀdÌtñÕû¨¶|”³:ÂXÇå‹Ğ{kÌÃâq…ˆ\\C¶0¥*Y)~Škk\\ômÇ*(÷9.{ò±½‹bÜTw©?:M˜i£†´åÅ¬9-ÀZÔüÄÁE«øÕ8Â3É¬Ñ\"Şz=¥×!Ş5ø¼.@'\\¼mÎíÔõ­¡0ÅèWjFµN“\\¦ÍŠíúi€ídAÉÚ8e>´j`0>öÂºU¤fm Ü›=*fò#dğ»,QmÚA`¡0ş:åí>¿|BWÜ&\n×Ä¶–	ÍOm3¡¦–#Å»V,ŒÑÕædwS*”®åO Ùî’êb\$ÅŞ;HbYSä¾†çGİöóûOgï21©%JJIó3äº‹\$µ%âŠó”£a–ıC¢SÈLÏò';kÿ\$—Ñú¼XåO%\$Ç	—1Áq¤Àéà°£f2½õ!è±@c«XZ‘!¦Aw”ü=åYfx¬±}Í#1µëË¨c×1PñÙ•Æ?a–¾D@ÑdŠúêååpøÑ&>´ó2rJhŠ']`_*¨\\@<\0¯ÙÜëv¸	ïc|°£z#öø„ñº‹zyq‘øí&Ş;|ÓÑâo»ÓÛY4ágHBÿ;s|»®ûmh?\$	ÀD(\0D¦†MèĞádŞQÈY_€xöJ(!!OˆûC—ñh÷7ÃSÊò;WŸâkKègÅ=AP®¼XmRÙJW»?Œ™7ï~’“Íş‹GçœÙKñ\$‚c·Ë\$¦ëä}7f»ÁÙîRg4‡gŒŞúèºQ£ñwXch\"«°¤ò§”ÑOšŠ’Ä|yÅ&RFUO.°“ı²B¯K‹qò‡CÂUÒŞG/çÔ~…ñë%öQ’¶nª€ŞtWX:ıÖ’v\"Úû*Ûp{2' 2(ã²9Ú¦H`hq„e;‰#@~	Ø.Ã!:ñÁøH¸QØà½ÚBT\$~”yà\n_é-'ø’&¡@_6Í&C‚\"ñ´©¦Ç	;4#?l3Ü_…oí'eÓ<÷ÿ»È²ÿåœ\"/şş'X\0f±Q¿#±0nní5#»6“vHS^8'`L¸›• M»®î8V/SR~œ³¸àÿp¶?áû¦DÂö{ø/ùÚ‡h-oô-ş½òàA|‰„\0k€“ª	€'I‚¯ˆdª*±À~C°_:¸áñÃ '€Œ®ô\0ãM(Yá£1€ğ`+Ğ´¸”°äVASå¡“4à– À^H–¡ÇšVY9¤CÒ‡¡>¨» p ÊîÜ9jCxê\0µÛğZ ’\0³	:îZ Zƒ¼°.Hg¢Ì„!ø¬J`êÊ‚ó:€VĞ\$pmÈƒJx\0ÚV’Ù%¿ f¤	m»@Ì®±JÄ©”(˜]#·¹ 	¸{‹¼ÂoÉH­ 2R²ªLz:Aú|y0¼Å\"4„RĞTÅ/?ø8ÉªHRq!X&A`—!MA\"l‚ÍL ›?„‡&Î\0ü¨€²&å²'h{,\\ü!ªAVR)(®‚èPY Osœ°'ÀÌyBÚÃœàˆ ·¾š!<¢oÁ>ÌÈÄ\0\\ÖÀ«^©ˆûğZ4Mh~0E5ºF\0øa€zJŠÁ—2HAbÚ À¾ÌĞd¹4àÁÎˆPËùRU©v2©Jb¹0-°bÁßˆ\r£şA„:‚ĞpÖv»h°s€[#ÂPt”¦‚“¦¢¥@Á8P€AçzQé4¥^R< İÂ#á;psá¨ğy\$[¸FS(9Èk6§p_PVA””€Bm{*0hŠ `A€6)ªĞ°4\0á!Yğ˜Á¬­d	Â@ŞYà…HB‹Ô°¥.24Plª´n'\n|l)¦ºAÈòN(9!ÊUÀ®0RD#Ít|*5po…Ò¢°J<T¦1YéôB,\n,nAAÚ;!\0ÁŞĞ\$Ğ¼Â\"V|-ÇõAÄVz2PºÃp”\0è>šÅìŒvh]0uÂ\$äğ»Ã(ZL0kdÂ‹	,1ŒŒ„!”Dÿ „VJ­'@²âÌ©`ì5dPÍ»XEëµnÕ†äûQP1@”l\rÂË‰ï\rĞ2)†,¸.0ÍÀ¶•:ÌÂp¤ÂĞX…¼›WkØ€VPM\0W2ÓÉCœ ¿‰²€aù: •û\0P`C¯zº†\r„JH-£ô<\naÃÇø( «¡u“ğga!&ã\0001/’i<%‡È¸Ú¦h´DÁÌ©XSŠ-¡<AH~µ¨ğ«i:,È°aßTkàD\nG˜b 1!.X:`R°L.\0kâr †€àH[-SÀLƒ\$ÿ0®VHÌÁ…‚\\ykîö¼OIÿnÍ\0‹£á*ªBVğêŒ.DX£Hˆ#X¾‰[BÎÄLkâëÁ0¹N0¸ˆ¨c&!J©’5G€è©°< -°¸¶´ …0¸ì	¯S¡8# !	Ôˆ3›\$‡	HH¥“3`NÌÄU´0€p ˜{€Ùxr€œ¦‚—ñ?PATOnr›*[²%ÅdKÊa€dÔJñI£V\$yşçüÄ¯Ñ[Dş¹ ÇÀˆCˆ#‚	BXTÏÔE2å©ş\0004\0ua¤?ìCÀ-Å…¹ËŒRÑN\0NÁ…LÄbÂ±,-ÄU8}CÄh‹¦f‡ãÑö@§|	jxx%ÀD|ìà|‡úR.ü[k6DÊ€€*»'\0¸deû(");}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
lzw_decompress("v0œF£©ÌĞ==˜ÎFS	ĞÊ_6MÆ³˜èèr:™E‡CI´Êo:C„”Xc‚\ræØ„J(:=ŸE†¦a28¡xğ¸?Ä'ƒi°SANN‘ùğxs…NBáÌVl0›ŒçS	œËUl(D|Ò„çÊP¦À>šE†ã©¶yHchäÂ-3Eb“å ¸b½ßpEÁpÿ9.Š˜Ì~\n?Kb±iw|È`Ç÷d.¼x8EN¦ã!”Í2™‡3©ˆá\r‡ÑYÌèy6GFmY8o7\n\r³0¤÷\0DbcÓ!¾Q7Ğ¨d8‹Áì~‘¬N)ùEĞ³`ôNsßğ`ÆS)ĞOé—·ç/º<xÆ9o»ÔåµÁì3n«®2»!r¼:;ã+Â9ˆCÈ¨®‰Ã\n<ñ`Èó¯bè\\š?`†4\r#`È<¯BeãB#¤N Üã\r.D`¬«jê4ÿpéar°øã¢º÷>ò8Ó\$Éc ¾1Écœ ¡c êİê{n7ÀÃ¡ƒAğNÊRLi\r1À¾ø!£(æjÂ´®+Âê62ÀXÊ8+Êâàä.\rÍÎôƒÎ!x¼åƒhù'ãâˆ6Sğ\0RïÔôñOÒ\n¼…1(W0…ãœÇ7qœë:NÃE:68n+äÕ´5_(®s \rã”ê‰/m6PÔ@ÃEQàÄ9\n¨V-‹Áó\"¦.:åJÏ8weÎq½|Ø‡³XĞ]µİY XÁeåzWâü 7âûZ1íhQfÙãu£jÑ4Z{p\\AUËJ<õ†káÁ@¼ÉÃà@„}&„ˆL7U°wuYhÔ2¸È@ûu  Pà7ËA†hèÌò°Ş3Ã›êçXEÍ…Zˆ]­lá@MplvÂ)æ ÁÁHW‘‘Ôy>Y-øYŸè/«›ªÁî hC [*‹ûFã­#~†!Ğ`ô\r#0PïCË—f ·¶¡îÃ\\î›¶‡É^Ã%B<\\½fˆŞ±ÅáĞİã&/¦O‚ğL\\jF¨jZ£1«\\:Æ´>N¹¯XaFÃAÀ³²ğÃØÍf…h{\"s\n×64‡ÜøÒ…¼?Ä8Ü^p\"ë°ñÈ¸\\Úe(¸PƒNµìq[g¸Árÿ&Â}PhÊà¡ÀWÙí*Şír_sËP‡hà¼àĞ\nÛËÃomõ¿¥Ãê—Ó#§¡.Á\0@épdW ²\$Òº°QÛ½Tl0† ¾ÃHdHë)š‡ÛÙÀ)PÓÜØHgàıUş„ªBèe\r†t:‡Õ\0)\"Åtô,´œ’ÛÇ[(DøO\nR8!†Æ¬ÖšğÜlAüV…¨4 hà£Sq<à@}ÃëÊgK±]®àè]â=90°'€åâøwA<‚ƒĞÑaÁ~€òWšæƒD|A´††2ÓXÙU2àéyÅŠŠ=¡p)«\0P	˜s€µn…3îr„f\0¢F…·ºvÒÌG®ÁI@é%¤”Ÿ+Àö_I`¶ÌôÅ\r.ƒ N²ºËKI…[”Ê–SJò©¾aUf›Szûƒ«M§ô„%¬·\"Q|9€¨Bc§aÁq\0©8Ÿ#Ò<a„³:z1Ufª·>îZ¹l‰‰¹ÓÀe5#U@iUGÂ‚™©n¨%Ò°s¦„Ë;gxL´pPš?BçŒÊQ\\—b„ÿé¾’Q„=7:¸¯İ¡Qº\r:ƒtì¥:y(Å ×\nÛd)¹ĞÒ\nÁX; ‹ìêCaA¬\ráİñŸP¨GHù!¡ ¢@È9\n\nAl~H úªV\nsªÉÕ«Æ¯ÕbBr£ªö„’­²ßû3ƒ\rP¿%¢Ñ„\r}b/‰Î‘\$“5§PëCä\"wÌB_çÉUÕgAtë¤ô…å¤…é^QÄåUÉÄÖj™Áí Bvhì¡„4‡)¹ã+ª)<–j^<Lóà4U* õBg ëĞæè*nÊ–è-ÿÜõÓ	9O\$´‰Ø·zyM™3„\\9Üè˜.oŠ¶šÌë¸E(iåàœÄÓ7	tßšé-&¢\nj!\rÀyœyàD1gğÒö]«ÜyRÔ7\"ğæ§·ƒˆ~ÀíàÜ)TZ0E9MåYZtXe!İf†@ç{È¬yl	8‡;¦ƒR{„ë8‡Ä®ÁeØ+ULñ'‚F²1ıøæ8PE5-	Ğ_!Ô7…ó [2‰JËÁ;‡HR²éÇ¹€8pç—²İ‡@™£0,Õ®psK0\r¿4”¢\$sJ¾Ã4ÉDZ©ÕI¢™'\$cL”R–MpY&ü½Íiçz3GÍzÒšJ%ÁÌPÜ-„[É/xç³T¾{p¶§z‹CÖvµ¥Ó:ƒV'\\–’KJa¨ÃMƒ&º°£Ó¾\"à²eo^Q+h^âĞiTğ1ªORäl«,5[İ˜\$¹·)¬ôjLÆU`£SË`Z^ğ|€‡r½=Ğ÷nç™»–˜TU	1Hyk›Çt+\0váD¿\r	<œàÆ™ìñjG”­tÆ*3%k›YÜ²T*İ|\"CŠülhE§(È\rÃ8r‡×{Üñ0å²×şÙDÜ_Œ‡.6Ğ¸è;ãü‡„rBjƒO'Ûœ¥¥Ï>\$¤Ô`^6™Ì9‘#¸¨§æ4Xş¥mh8:êûc‹ş0ø×;Ø/Ô‰·¿¹Ø;ä\\'( î„tú'+™òı¯Ì·°^]­±NÑv¹ç#Ç,ëvğ×ÃOÏiÏ–©>·Ş<SïA\\€\\îµü!Ø3*tl`÷u\0p'è7…Pà9·bsœ{Àv®{·ü7ˆ\"{ÛÆrîaÖ(¿^æ¼İE÷úÿë¹gÒÜ/¡øUÄ9g¶î÷/ÈÔ`Ä\nL\n)À†‚(Aúağ\" çØ	Á&„PøÂ@O\nå¸«0†(M&©FJ'Ú! …0Š<ïHëîÂçÆù¥*Ì|ìÆ*çOZím*n/bî/ö®Ôˆ¹.ìâ©o\0ÎÊdnÎ)ùi:RÎëP2êmµ\0/vìOX÷ğøFÊ³ÏˆîŒè®\"ñ®êöî¸÷0õ0ö‚¬©í0bËĞgjğğ\$ñné0}°	î@ø=MÆ‚0nîPŸ/pæotì€÷°¨ğ.ÌÌ½g\0Ğ)o—\n0È÷‰\rF¶é€ b¾i¶Ão}\n°Ì¯…	NQ°'ğxòFaĞJîÎôLõéğĞàÆ\rÀÍ\r€Öö‘0Åñ'ğ¬Éd	oepİ°4DĞÜÊ¦q(~ÀÌ ê\r‚E°ÛprùQVFHœl£‚Kj¦¿äN&­j!ÍH`‚_bh\r1 ºn!ÍÉ­z™°¡ğ¥Í\\«¬\rŠíŠÃ`V_kÚÃ\"\\×‚'Vˆ«\0Ê¾`ACúÀ±Ï…¦VÆ`\r%¢’ÂÅì¦\rñâƒ‚k@NÀ°üBñíš™¯ ·!È\n’\0Z™6°\$d Œ,%à%laíH×\n‹#¢S\$!\$@¶İ2±„I\$r€{!±°J‡2HàZM\\ÉÇhb,‡'||cj~gĞr…`¼Ä¼º\$ºÄÂ+êA1ğœE€ÇÀÙ <ÊL¨Ñ\$âY%-FDªŠd€Lç„³ ª\n@’bVfè¾;2_(ëôLÄĞ¿Â²<%@Úœ,\"êdÄÀN‚erô\0æƒ`Ä¤Z€¾4Å'ld9-ò#`äóÅ–…à¶Öãj6ëÆ£ãv ¶àNÕÍf Ö@Ü†“&’B\$å¶(ğZ&„ßó278I à¿àP\rk\\§—2`¶\rdLb@Eöƒ2`P( B'ã€¶€º0²& ô{Â•“§:®ªdBå1ò^Ø‰*\r\0c<K|İ5sZ¾`ºÀÀO3ê5=@å5ÀC>@ÂW*	=\0N<g¿6s67Sm7u?	{<&LÂ.3~DÄê\rÅš¯x¹í),rîinÅ/ åO\0o{0kÎ]3>m‹”1\0”I@Ô9T34+Ô™@e”GFMCÉ\rE3ËEtm!Û#1ÁD @‚H(‘Ón ÃÆ<g,V`R]@úÂÇÉ3Cr7s~ÅGIói@\0vÂÓ5\rVß'¬ ¤ Î£PÀÔ\râ\$<bĞ%(‡Ddƒ‹PWÄîĞÌbØfO æx\0è} Üâ”lb &‰vj4µLS¼¨Ö´Ô¶5&dsF Mó4ÌÓ\".HËM0ó1uL³\"ÂÂ/J`ò{Çş§€ÊxÇYu*\"U.I53Q­3Qô»J„”g ’5…sàú&jÑŒ’Õu‚Ù­ĞªGQMTmGBƒtl-cù*±ş\rŠ«Z7Ôõó*hs/RUV·ğôªBŸNËˆ¸ÃóãêÔŠài¨Lk÷.©´Ätì é¾©…rYi”Õé-Sµƒ3Í\\šTëOM^­G>‘ZQjÔ‡™\"¤¬i”ÖMsSãS\$Ib	f²âÑuæ¦´™å:êSB|i¢ YÂ¦ƒà8	vÊ#é”Dª4`‡†.€Ë^óHÅM‰_Õ¼ŠuÀ™UÊz`ZJ	eçºİ@Ceíëa‰\"mób„6Ô¯JRÂÖ‘T?Ô£XMZÜÍĞ†ÍòpèÒ¶ªQv¯jÿjV¶{¶¼ÅCœ\rµÕ7‰TÊª úí5{Pö¿]’\rÓ?QàAAÀè‹’Í2ñ¾ “V)Ji£Ü-N99f–l JmÍò;u¨@‚<FşÑ ¾e†j€ÒÄ¦I‰<+CW@ğçÀ¿Z‘lÑ1É<2ÅiFı7`KG˜~L&+NàYtWHé£‘w	Ö•ƒòl€Òs'gÉãq+Lézbiz«ÆÊÅ¢Ğ.ĞŠÇzW²Ç ùzd•W¦Û÷¹(y)vİE4,\0Ô\"d¢¤\$Bã{²!)1U†5bp#Å}m=×È@ˆwÄ	P\0ä\rì¢·‘€`O|ëÆö	œÉüÅõûYôæJÕ‚öE×ÙOu_§\n`F`È}MÂ.#1á‚¬fì*´Õ¡µ§  ¿zàucû€—³ xfÓ8kZR¯s2Ê‚-†’§Z2­+Ê·¯(åsUõcDòÑ·Êì˜İX!àÍuø&-vPĞØ±\0'LïŒX øLÃ¹Œˆo	İô>¸ÕÓ\r@ÙPõ\rxF×üE€ÌÈ­ï%Àãì®ü=5NÖœƒ¸?„7ùNËÃ…©wŠ`ØhX«98 Ìø¯q¬£zãÏd%6Ì‚tÍ/…•˜ä¬ëLúÍl¾Ê,ÜKa•N~ÏÀÛìú,ÿ'íÇ€M\rf9£w˜!x÷x[ˆÏ‘ØG’8;„xA˜ù-IÌ&5\$–D\$ö¼³%…ØxÑ¬Á”ÈÂ´ÀÂŒ]›¤õ‡&o‰-39ÖLù½zü§y6¹;u¹zZ èÑ8ÿ_•Éx\0D?šX7†™«’y±OY.#3Ÿ8 ™Ç€˜e”Q¨=Ø€*˜™GŒwm ³Ú„Y‘ù ÀÚ]YOY¨F¨íšÙ)„z#\$eŠš)†/Œz?£z;™—Ù¬^ÛúFÒZg¤ù• Ì÷¥™§ƒš`^Úe¡­¦º#§“Øñ”©ú?œ¸e£€M£Ú3uÌåƒ0¹>Ê\"?Ÿö@×—Xv•\"ç”Œ¹¬¦*Ô¢\r6v~‡ÃOV~&×¨^gü šÄ‘Ù‡'Î€f6:-Z~¹šO6;zx²;&!Û+{9M³Ù³d¬ \r,9Öí°ä·WÂÆİ­:ê\rúÙœùã@ç‚+¢·]œÌ-[g™Û‡[s¶[iÙiÈq››y›éxé+“|7Í{7Ë|w³}„¢›£E–ûW°€Wk¸|JØ¶å‰xmˆ¸q xwyjŸ»˜#³˜e¼ø(²©‰¸ÀßÃ¾™†ò³ {èßÚ y“ »M»¸´@«æÉ‚“°Y(gÍš-ÿ©º©äí¡š¡ØJ(¥ü@ó…;…yÂ#S¼‡µY„Èp@Ï%èsúoŸ9;°ê¿ôõ¤¹+¯Ú	¥;«ÁúˆZNÙ¯Âº§„š k¼V§·u‰[ñ¼x…|q’¤ON?€ÉÕ	…`uœ¡6|­|X¹¤­—Ø³|Oìx!ë:¨œÏ—Y]–¬¹™c•¬À\r¹hÍ9nÎÁ¬¬ë€Ï8'—ù‚êà Æ\rS.1¿¢USÈ¸…¼X‰É+ËÉz]ÉµÊ¤?œ©ÊÀCË\r×Ë\\º­¹ø\$Ï`ùÌ)UÌ|Ë¤|Ñ¨x'ÕœØÌäÊ<àÌ™eÎ|êÍ³ç—â’Ìé—LïÏİMÎy€(Û§ĞlĞº¤O]{Ñ¾×FD®ÕÙ}¡yu‹ÑÄ’ß,XL\\ÆxÆÈ;U×ÉWt€vŸÄ\\OxWJ9È’×R5·WiMi[‡Kˆ€f(\0æ¾dÄšÒè¿©´\rìMÄáÈÙ7¿;ÈÃÆóÒñçÓ6‰KÊ¦Iª\rÄÜÃxv\r²V3ÕÛßÉ±.ÌàRùÂşÉá|Ÿá¾^2‰^0ß¾\$ QÍä[ã¿D÷áÜ£å>1'^X~t1\"6Lş›+ş¾Aàeá“æŞåI‘ç~Ÿåâ³â³@ßÕ­õpM>Óm<´ÒSKÊç-HÉÀ¼T76ÙSMfg¨=»ÅGPÊ°›PÖ\r¸é>Íö¾¡¥2Sb\$•C[Ø×ï(Ä)Ş%Q#G`uğ°ÇGwp\rkŞKe—zhjÓ“zi(ôèrO«óÄŞÓşØT=·7³òî~ÿ4\"ef›~íd™ôíVÿZ‰š÷U•-ëb'VµJ¹Z7ÛöÂ)T‘£8.<¿RMÿ\$‰ôÛØ'ßbyï\n5øƒİõ_àwñÎ°íUğ’`eiŞ¿J”b©gğuSÍë?Íå`öáì+¾Ïï Mïgè7`ùïí\0¢_Ô-ûŸõ_÷–?õF°\0“õ¸X‚å´’[²¯Jœ8&~D#Áö{P•Øô4Ü—½ù\"›\0ÌÀ€‹ı§ı@Ò“–¥\0F ?* ^ñï¹å¯wëĞ:ğ¾uàÏ3xKÍ^ów“¼¨ß¯‰y[Ô(æ–µ#¦/zr_”g·æ?¾\0?€1wMR&M¿†ù?¬St€T]İ´Gõ:I·à¢÷ˆ)‡©Bïˆ‹ vô§’½1ç<ôtÈâ6½:W{ÀŠôx:=Èî‘ƒŒŞšóø:Â!!\0x›Õ˜£÷q&áè0}z\"]ÄŞo•z¥™ÒjÃw×ßÊÚÁ6¸ÒJ¢PÛ[\\ }ûª`S™\0à¤qHMë/7B’€P°ÂÄ]FTã•8S5±/IÑ\rŒ\n îO¯0aQ\n >Ã2­j…;=Ú¬ÛdA=­p£VL)Xõ\nÂ¦`e\$˜TÆ¦QJÎk´7ª*Oë .‰ˆ…òÄ¡\röµš\$#pİWT>!ªªv|¿¢}ë× .%˜Á,;¨ê›å…­Úf*?«ç„˜ïô„\0¸ÄpD›¸! ¶õ#:MRcúèB/06©­®	7@\0V¹vg€ ØÄhZ\nR\"@®ÈF	‘Êä¼+Êš°EŸIŞ\n8&2ÒbXşPÄ¬€Í¤=h[§¥æ+ÕÊ‰\r:ÄÍFû\0:*åŞ\r}#úˆ!\"¤c;hÅ¦/0ƒ·Ş’òEj®íÁ‚Î]ñZ’ˆ‘—\0Ú@iW_–”®h›;ŒVRb°ÚP%!­ìb]SBšƒ’õUl	åâ³érˆÜ\rÀ-\0 À\"Q=ÀIhÒÍ€´	 F‘ùşLèÎFxR‚Ñ@œ\0*Æj5Œük\0Ï0'	@El€O˜ÚÆH CxÜ@\"G41Ä`Ï¼P(G91«\0„ğ\"f:QÊ¸@¨`'>7ÑÈädÀ¨ˆíÇR41ç>ÌrIHõGt\n€RH	ÀÄbÒ€¶71»ìfãh)Dª„8 B`À†°(V<Q§8c? 2€´€E4j\0œ9¼\r‚Íÿ@‹\0'FúDš¢,Å!ÓÿH=Ò* ˆEí(×ÆÆ?Ñª&xd_H÷Ç¢E²6Ä~£uÈßG\0RXıÀZ~P'U=Çß @èÏÈl+A­\n„h£IiÆ”ü±ŸPG€Z`\$ÈP‡ş‘À¤Ù.Ş;ÀEÀ\0‚}€ §¸Q±¤“äÓ%èÑÉjA’W’Ø¥\$»!ıÉ3r1‘ {Ó‰%i=IfK”!Œe\$àé8Ê0!üh#\\¹HF|Œi8tl\$ƒğÊlÀìläi*(ïG¸ñçL	 ß\$€—xØ.èq\"Wzs{8d`&ğWô©\0&E´¯Íì15jWäb¬öÄ‡ÊŞV©R„³™¿-#{\0ŠXi¤²Äg*÷š7ÒVF3‹`å¦©p@õÅ#7°	å†0€æ[Ò®–¬¸[øÃ©hË–\\áo{ÈáŞT­ÊÒ]²ï—Œ¼Å¦á‘€8l`f@—reh·¥\nÊŞW2Å*@\0€`K(©L•Ì·\0vTƒË\0åc'L¯ŠÀ:„” 0˜¼@L1×T0b¢àhşWÌ|\\É-èïÏDN‡ó€\ns3ÀÚ\"°€¥°`Ç¢ùè‚’2ªå€&¾ˆ\rœU+™^ÌèR‰eS‹n›i0ÙuËšb	J˜’€¹2s¹Ípƒs^n<¸¥òâ™±Fl°aØ\0¸š´\0’mA2›`|ØŸ6	‡¦nrÁ›¨\0DÙ¼Íì7Ë&mÜß§-)¸ÊÚ\\©ÆäİŒ\n=â¤–à;* ‚Şb„è“ˆÄT“‚y7cú|o /–Ôßß:‹ît¡P<ÙÀY: K¸&C´ì'G/Å@ÎàQ *›8çv’/‡À&¼üòWí6p.\0ªu3«ŒñBq:(eOPáp	”é§²üÙã\rœ‹á0(ac>ºNö|£º	“t¹Ó\n6vÀ_„îeİ;yÕÎè6fügQ;yúÎ²[Sø	äëgöÇ°èO’ud¡dH€Hğ= Z\ræ'ÚÊùqC*€) œîgÂÇEêO’€ \" ğ¨!kĞ('€`Ÿ\nkhTùÄ*ösˆÄ5R¤Eöa\n#Ö!1¡œ¿‰×\0¡;ÆÇSÂiÈ¼@(àl¦Á¸I× Ìv\rœnj~ØçŠ63¿ÎˆôI:h°ÔÂƒ\n.‰«2plÄ9Btâ0\$bº†p+”Ç€*‹tJ¢ğÌ¾s†JQ8;4P(ı†Ò§Ñ¶!’€.Ppk@©)6¶5ı”!µ(ø“\n+¦Ø{`=£¸H,É\\Ñ´€4ƒ\"[²Cø»º1“´Œ-èÌluoµä¸4•[™±â…EÊ%‡\"‹ôw] Ù(ã ÊTe¢)êK´A“E={ \n·`;?İôœ-ÀGŠ5I¡í­Ò.%Á¥²şéq%EŸ—ıs¢é©gFˆ¹s	‰¦¸ŠKºGÑøn4i/,­i0·uèx)73ŒSzgŒâÁV[¢¯hãDp'ÑL<TM¤äjP*oœâ‰´‘\nHÎÚÅ\n 4¨M-W÷NÊA/î†@¤8mH¢‚Rp€tp„V”=h*0ºÁ	¥1;\0uG‘ÊT6’@s™\0)ô6À–Æ£T\\…(\"èÅU,ò•C:‹¥5iÉKšl«ì‚Û§¡E*Œ\"êrà¦ÔÎ.@jRâJ–QîŒÕ/¨½L@ÓSZ”‘¥Põ)(jjJ¨««ªİL*ª¯Ä\0§ªÛ\r¢-ˆñQ*„QÚœgª9é~P@…ÕÔH³‘¬\n-e»\0êQw%^ ETø< 2Hş@Ş´îe¥\0ğ e#;öÖI‚T’l“¤İ+A+C*’YŒ¢ªh/øD\\ğ£!é¬š8“Â»3AĞ™ÄĞEğÍE¦/}0tµJ|™Àİ1Qm«Øn%(¬p´ë!\nÈÑÂ±UË)\rsEXú‚’5u%B- ´Àw]¡*•»E¢)<+¾¦qyV¸@°mFH òÔšBN#ı]ÃYQ1¸Ö:¯ìV#ù\$“æ şô<&ˆX„€¡úÿ…x« tš@]GğíÔ¶¥j)-@—qĞˆL\nc÷I°Y?qC´\ràv(@ØËX\0Ov£<¬Rå3X©µ¬Q¾Jä–Éü9Ö9ÈlxCuÄ«d±± vT²Zkl\rÓJíÀ\\o›&?”o6EĞq °³ªÉĞ\r–÷«'3úËÉª˜J´6ë'Y@È6ÉFZ50‡VÍT²yŠ¬˜C`\0äİVS!ıš‹&Û6”6ÉÑ³rD§f`ê›¨Jvqz„¬àF¿ ÂÂò´@è¸İµ…šÒ…Z.\$kXkJÚ\\ª\"Ë\"àÖi°ê«:ÓEÿµÎ\roXÁ\0>P–¥Pğmi]\0ªöö“µaV¨¸=¿ªÈI6¨´°ÎÓjK3ÚòÔZµQ¦m‰EÄèğbÓ0:Ÿ32ºV4N6³´à‘!÷lë^Ú¦Ù@hµhUĞ>:ú	˜ĞE›>jäèĞú0g´\\|¡Shâ7yÂŞ„\$•†,5aÄ—7&¡ë°:[WX4ÊØqÖ ‹ìJ¹Æä×‚Şc8!°H¸àØVD§Ä­+íDŠ:‘¡¥°9,DUa!±X\$‘ÕĞ¯ÀÚ‹GÁÜŒŠBŠt9-+oÛt”L÷£}Ä­õqK‹‘x6&¯¯%x”ÏtR¿–éğ\"ÕÏ€èR‚IWA`c÷°È}l6€Â~Ä*¸0vkıp«Ü6Àë›8z+¡qúXöäw*·EƒªIN›¶ªå¶ê*qPKFO\0İ,(Ğ€|œ•‘”°k *YF5”åå;“<6´@ØQU—\"×ğ\rbØOAXÃvè÷v¯)H®ôo`STÈpbj1+Å‹¢e²Á™ Ê€Qx8@¡‡ĞÈç5\\Q¦,Œ‡¸Ä‰NëİŞ˜b#Y½H¥¯p1›ÖÊøkB¨8NüoûX3,#UÚ©å'Ä\"†é”€ÂeeH#z›­q^rG[¸—:¿\r¸m‹ngòÜÌ·5½¥V]«ñ-(İWğ¿0âëÑ~kh\\˜„ZŠå`ïél°êÄÜk ‚oÊjõWĞ!€.¯hFŠÔå[tÖA‡wê¿e¥Mà««¡3!¬µÍæ°nK_SF˜j©¿ş-S‚[rœÌ€wä´ø0^Áh„fü-´­ı°?‚›ıXø5—/±©Š€ëëIY ÅV7²a€d ‡8°bq·µbƒn\n1YRÇvT±õ•,ƒ+!Øıü¶NÀT£î2IÃß·ÄÄ÷„ÇòØ‡õ©K`K\"ğ½ô£÷O)\nY­Ú4!}K¢^²êÂàD@á…÷naˆ\$@¦ ƒÆ\$AŠ”jÉËÇø\\‹D[=Ë	bHpùSOAG—ho!F@l„UËİ`Xn\$\\˜Íˆ_†¢Ë˜`¶âHBÅÕ]ª2ü«¢\"z0i1‹\\”ŞÇÂÔwù.…fyŞ»K)£îíÂ‡¸ pÀ0ä¸XÂS>1	*,]’à\r\"ÿ¹<cQ±ñ\$t‹„qœ.‹ü	<ğ¬ñ™+t,©]Lò!È{€güãX¤¶\$¤6v…˜ùÇ ¡š£%GÜHõ–ÄØœÈE ÒXÃÈ*Á‚0ÛŠ)q¡nCØ)I›ûà\"µåÚÅŞíˆ³¬`„KFçÁ’@ïd»5Œê»AÈÉp€{“\\äÓÀpÉ¾Nòrì'£S(+5®ĞŠ+ \"´Ä€£U0ÆiËÜ›úæ!nMˆùbrKÀğä6Ãº¡r–ì¥â¬|aüÊÀˆ@Æx|®²kaÍ9WR4\"?5Ê¬pıÛ“•ñk„rÄ˜«¸¨ıß’ğæ¼7Â—Hp†‹5YpW®¼ØG#ÏrÊ¶AWD+`¬ä=Ê\"ø}Ï@HÑ\\p°“Ğ€©ß‹Ì)C3Í!sO:)Ùè_F/\r4éÀç<A¦…\nn /Tæ3f7P1«6ÓÄÙıOYĞ»Ï²‡¢óqì×;ìØÀæaıXtS<ã¼9Ânws²x@1ÎxsÑ?¬ï3Å@¹…×54„®oÜÈƒ0»ŞĞïpR\0Øà¦„†Îù·óâyqßÕL&S^:ÙÒQğ>\\4OInƒZ“nçòvà3¸3ô+P¨…L(÷Ä”ğ…Àà.x \$àÂ«Cå‡éCnªAkçc:LÙ6¨ÍÂr³w›ÓÌh°½ÙÈnr³Zêã=è»=jÑ’˜³‡6}MŸGıu~3ùšÄbg4Åùôs6sóQé±#:¡3g~v3¼ó€¿<¡+Ï<ô³Òa}Ï§=Îe8£'n)ÓcCÇzÑ‰4L=hıŒ{i´±Jç^~çƒÓwg‹Dà»jLÓéÏ^šœÒÁ=6Î§NÓ”êÅÁ¢\\éÛDóÆÑN”†êEı?hÃ:SÂ*>„ô+¡uúhhÒ…´W›E1j†x²Ÿôí´ŠtÖ'Îtà[ îwS²¸ê·9š¯Tö®[«,ÕjÒv“òÕît£¬A#T™¸Ôæ‚9ìèj‹K-õÒŞ ³¿¨Yèi‹Qe?®£4ÓÓÁë_WzßÎéó‹@JkWYêhÎÖpu®­çj|z4×˜õ	èi˜ğm¢	àO5à\0>ç|ß9É×–«µè½ öëgVyÒÔu´»¨=}gs_ºãÔV¹sÕ®{çk¤@r×^—õÚ(İwÏ…øH'°İaì=i»ÖNÅ4µ¨‹ë_{Ï6ÇtÏ¨ÜöÏ—e [Ğh-¢“Ul?Jîƒ0O\0^ÛHlõ\0.±„Z‚’œ¼âÚxu€æğ\"<	 /7ÁŠ¨Ú û‹ïi:Ò\nÇ ¡´à;íÇ!À3ÚÈÀ_0`\0H`€Â2\0€ŒHò#h€[¶P<í¦†‘×¢g¶Ü§m@~ï(şÕ\0ßµkâY»vÚæâ#>¥ù„\nz\n˜@ÌQñ\n(àGİ\nöüà'kóš¦èº5“n”5Û¨Ø@_`Ğ‡_l€1Üşèwp¿Pî›w›ªŞ\0…cµĞoEl{Åİ¾é7“»¼¶o0ĞÛÂôIbÏên‹zÛÊŞÎï·›¼ ‹ç{Ç8øw=ëîŸ| /yê3aíß¼#xqŸÛØò¿»@ï÷kaà!ÿ\08dîmˆäR[wvÇ‹RGp8øŸ vñ\$Zü½¸mÈûtÜŞİÀ¥·½íôºÜû·Ç½Ôîûu€oİp÷`2ğãm|;#x»mñnç~;ËáVëE£ÂíØğÄü3OŸ\r¸,~o¿w[òáNêø}ºş ›clyá¾ñ¸OÄÍŞñ;…œ?á~ì€^j\"ñWz¼:ß'xWÂŞ.ñ	Áu’(¸ÅÃäq—‹<gâçv¿hWq¿‰\\;ßŸ8¡Ã)M\\³š5vÚ·x=h¦iºb-ÀŞ|bÎğàpyDĞ•Hh\rceà˜y7·p®îxşÜG€@D=ğ Öù§1Œÿ!4Ra\r¥9”!\0'ÊYŒŸ¥@>iS>æ€Ö¦Ÿo°óoòÎfsO 9 .íşéâ\"ĞF‚…ló20åğE!Qšá¦çËD9dÑBW4ƒ›\0û‚y`RoF>FÄa„‰0‘ùÊƒó0	À2ç<‚IÏP'\\ñçÈIÌ\0\$Ÿœ\n R aUĞ.‚sĞ„«æ\"ùš1Ğ†…eºYç ¢„Zêqœñ1 |Ç÷#¯G!±P’P\0|‰HÇFnp>Wü:¢`YP%”ÄâŸ\nÈa8‰ÃP>‘ÁÁè–™`]‘‹4œ`<Ğr\0ùÃ›ç¨û¡–z–4Ù‡¥Ë8€ùÎĞ4ó`mãh:¢Îª¬HDªãÀjÏ+p>*ä‹ÃÄê8äŸÕ 08—A¸È:€À»Ñ´]wêÃºùz>9\n+¯ççÍÀñØ:—°ii“PoG0°Öö1ş¬)ìŠZ°Ú–èn¤È’ì×eRÖ–Üí‡g£M¢à”ÀŒgs‰LC½rç8Ğ€!°†À‚Œ3R)Îú0³0Œôs¨IéJˆVPpK\n|9e[á•ÖÇË‘²’D0¡Õ àz4Ï‘ªo¥Ôéáèà´,N8nåØsµ#{è“·z3ğ>¸BSı\";Àe5VD0±¬š[\$7z0¬ºøÃËã=8ş	T 3÷»¨Q÷'R’±—’ØnÈ¼LĞyÅ‹ìö'£\0oäÛ,»‰\0:[}(’¢ƒ|×ú‡X†>xvqWá“?tBÒE1wG;ó!®İ‹5Î€|Ç0¯»JI@¯¨#¢ˆŞuÅ†Iáø\\p8Û!'‚]ß®šl-€låSßBØğ,Ó—·»ò]èñ¬1‡Ô•HöÿNÂ8%%¤	Å/;FGSôòôhé\\Ù„ÓcÔt²¡á2|ùWÚ\$tøÎ<ËhİOŠ¬+#¦BêaN1ùç{ØĞyÊwòš°2\\Z&)½d°b',XxmÃ~‚Hƒç@:d	>=-Ÿ¦lK¯ŒÜşJí€\0ŸÌÌó@€rÏ¥²@\"Œ(AÁñïªıZ¼7Åh>¥÷­½\\Íæú¨#>¬õø\0­ƒXrã—YøïYxÅæq=:šÔ¹ó\rlŠoæm‡gbööÀ¿À˜ï„D_àTx·C³ß0.Šôy€†R]Ú_İëÇZñÇ»WöIàëGÔï	MÉª(®É|@\0SO¬ÈsŞ {î£”ˆø@k}äFXSÛb8àå=¾È_ŠÔ”¹l²\0å=ÈgÁÊ{ HÿÉyGüÕáÛ sœ_şJ\$hkúF¼q„àŸ÷¢Éd4Ï‰ø»æÖ'ø½>vÏ¬ !_7ùVq­Ó@1zë¤uSe…õjKdyuëÛÂS©.‚2Œ\"¯{úÌKşØË?˜s·ä¬Ë¦h’ßRíd‚é`:y—ÙåûGÚ¾\nQéı·Ùßow’„'öïhS—î>ñ©¶‰LÖX}ğˆe·§¸G¾â­@9ıãíŸˆüWİ|íøÏ¹û@•_ˆ÷uZ=©‡,¸åÌ!}¥ŞÂ\0äI@ˆä#·¶\"±'ãY`¿Ò\\?Ìßpó·ê,Gú¯µı×œ_®±'åGúÿ²Ğ	ŸT†‚#ûoŸÍH\rş‡\"Êëúoã}§ò?¬şOé¼”7ç|'ÎÁ´=8³M±ñQ”yôaÈH€?±…ß®‡ ³ÿ\0ÿ±öbUdè67şÁ¾I Oöäïû\"-¤2_ÿ0\rõ?øÿ«–ÿ hO×¿¶t\0\0002°~şÂ° 4²¢ÌK,“Öoh¼Î	Pc£ƒ·z`@ÚÀ\"îœâŒàÇH; ,=Ì 'S‚.bËÇS„¾øàCc—ƒêìšŒ¡R,~ƒñXŠ@ '…œ8Z0„&í(np<pÈ£ğ32(ü«.@R3ºĞ@^\r¸+Ğ@ , öò\$	ÏŸ¸„E’ƒèt«B,²¯¤âª€Ê°h\r£><6]#ø¥ƒ;‚íC÷.Ò€¢ËĞ8»Pğ3ş°;@æªL,+>½‰p(#Ğ-†f1Äz°Áª,8»ß ÆÆPà:9ÀŒï·RğÛ³¯ƒ¹†)e\0Ú¢R²°!µ\nr{Æîe™ÒøÎGA@*ÛÊnDöŠ6Á»ğòóíN¸\rR™Ôø8QK²0»àé¢½®À>PN°Ü©IQ=r<á;&À°fÁNGJ;ğUAõÜ¦×A–P€&şõØã`©ÁüÀ€);‰ø!Ğs\0î£Áp†p\r‹¶à‹¾n(ø•@…%&	S²dY«ŞìïuCÚ,¥º8O˜#ÏÁ„óòoªšêRè¬v,€¯#è¯|7Ù\"Cp‰ƒ¡Bô`ìj¦X3«~ïŠ„RĞ@¤ÂvÂø¨£À9B#˜¹ @\nğ0—>Tíõá‘À-€5„ˆ/¡=è€ ¾‚İE¯—Ç\nç“Âˆd\"!‚;ŞÄp*n¬¼Z²\08/ŒjX°\r¨>F	PÏe>À•OŸ¢LÄ¯¡¬O0³\0Ù)kÀÂºã¦ƒ[	ÀÈÏ³Âêœ'L€Ù	Ãåñƒ‚é›1 1\0ø¡Cë 1Tº`©„¾ìRÊz¼Äš£îÒp®¢°ÁÜ¶ìÀ< .£>î¨5İ\0ä»¹>Ÿ BnËŠ<\"he•>ĞººÃ®£çsõ!ºHı{Ü‘!\rĞ\rÀ\"¬ä| ‰>Rš1dàö÷\"U@ÈD6ĞåÁ¢3£çğŸ>o\r³çá¿vL:K„2å+Æ0ì¾€>°È\0äí ®‚·Bé{!r*Hî¹§’y;®`8\0ÈËØ¯ô½dş³ûé\rÃ0ÿÍÀ2AşÀ£î¼?°õ+û\0ÛÃ…\0A¯ƒwSû‡lÁ²¿°\r[Ô¡ª6ôcoƒ=¶ü¼ˆ0§z/J+ê†ŒøW[·¬~C0‹ùeü30HQP÷DPY“}‡4#YDö…ºp)	º|û@¥&ã-À†/F˜	á‰T˜	­«„¦aH5‘#ƒëH.ƒA>Ğğ0;.¬­şY“Ä¡	Ã*ûD2 =3·	pBnuDw\n€!ÄzûCQ \0ØÌHQ4DË*ñ7\0‡JÄñ%Ä±puD (ôO=!°>®u,7»ù1†ãTM+—3ù1:\"P¸Ä÷”RQ?¿“üP°Š¼+ù11= ŒM\$ZÄ×lT7Å,Nq%E!ÌS±2Å&öŒU*>GDS&¼ªéó›ozh8881\\:ÑØZ0hŠÁÈT •C+#Ê±A%¤¤D!\0ØïòñÁXDAÀ3\0•!\\í#h¼ªí9bÏ‚T€!dª—ˆÏÄY‘j2ôSëÈÅÊ\nA+Í½¤šHÈwD`íŠ(AB*÷ª+%ÕEï¬X.Ë Bé#ºƒÈ¿Œ¸&ÙÄXe„EoŸ\"×è|©r¼ª8ÄW€2‘@8Daï|ƒ‚ø÷‘Š”Núhô¥ÊJ8[¬Û³öÂö®WzØ{Z\"L\0¶\0€È†8ØxŒÛ¶X@”À E£Íïë‘h;¿af˜¼1Âş;nÃÎhZ3¨E™Â«†0|¼ ì˜‘­öAà’£tB,~ôŠW£8^»Ç ×ƒ‚õ<2/	º8¢+´¨Û”‚O+ %P#Î®\n?»ß‰?½şeË”ÁO\\]Ò7(#û©DÛ¾(!c) NöˆºÑMF”E£#DXîgï)¾0Aª\0€:ÜrBÆ×``  ÚèQ’³H>!\rB‡¨\0€‰V%ce¡HFH×ñ¤m2€B¨2IêµÄÙë`#ú˜ØD>¬ø³n\n:LŒıÉ9CñÊ˜0ãë\0“x(Ş©(\nş€¦ºLÀ\"GŠ\n@éø`[Ãó€Š˜\ni'\0œğ)ˆù€‚¼y)&¤Ÿ(p\0€Nˆ	À\"€®N:8±é.\r!'4|×œ~¬ç§ÜÙÊ€ê´·\"…cúÇDlt‘Ó¨Ÿ0c«Å5kQQ×¨+‹ZGkê!F€„cÍ4ˆÓRx@ƒ&>z=¹\$(?óŸïÂ(\nì€¨>à	ëÒµ‚ÔéCqÛŒ¼Œt-}ÇG,tòGW ’xqÛHf«b\0\0zÕìƒÁT9zwĞ…¢Dmn'îccb H\0z…‰ñ3¹!¼€ÑÔÅ HóÚHz×€Iy\",ƒ- \0Û\"<†2ˆî Ğ'’#H`†d-µ#cljÄ`³­i(º_¤ÈdgÈíÇ‚*Ój\rª\0ò>Â 6¶ºà6É2ókjã·<ÚCq‘Ğ9àÄ†ÉI\r\$C’AI\$x\r’H¶È7Ê8 Ü€Z²pZrR£òà‚_²U\0äl\r‚®IRXi\0<²äÄÌr…~xÃS¬é%™Ò^“%j@^ÆôT3…3É€GH±z€ñ&\$˜(…Éq\0Œšf&8+Å\rÉ—%ì–2hCüx™¥ÕI½šlbÉ€’(hòSƒY&àBªÀŒ•’`”f•òxÉv n.L+ş›/\"=I 0«d¼\$4¨7rŒæ¼A£„õ(4 2gJ(D˜á=F„¡â´Èå(«‚û-'Ä òXGô29Z=˜’Ê,ÊÀr`);x\"Éä8;²–>û&…¡„ó',—@¢¤2Ãpl²—ä:0ÃlI¡¨\rrœJDˆÀúÊ»°±’hAÈz22pÎ`O2hˆ±8H‚´Ä„wt˜BF²Œg`7ÉÂä¥2{‘,Kl£ğ›Œß°%C%úomû€¾àÀ’´ƒ‘+X£íûÊ41ò¹¸\nÈ2pŠÒ	ZB!ò=VÆÜ¨èÈ€Ø+H6²ÃÊ*èª\0ækÕà—%<² øK',3ØrÄI ;¥ 8\0Z°+EÜ­Ò`Ğˆ²½Êã+l¯ÈÏËW+¨YÒµ-t­fËb¡Qò·Ë_-Ó€Ş…§+„· 95ŠLjJ.GÊ©,\\·òÔ….\$¯2ØJè\\„- À1ÿ-c¨²‚Ë‡.l·fŒxBqK°,d·èË€â8äA¹Ko-ô¸²îÃæ²°3KÆ¯r¾¸/|¬ÊËå/\\¸r¾Ëñ,¡HÏ¤¸!ğYÀ1¹0¤@­.Â„&|˜ÿËâ+ÀéJ\0ç0P3JÍ-ZQ³	»\r&„‘Ãá\nÒLÑ*ÀËŞj‘Ä‰|—ÒåËæ#Ô¾ª\"Ëº“AÊï/ä¹òû8)1#ï7\$\"È6\n>\nô¢Ã7L1à‹òh9Î\0B€Z»d˜#©b:\0+A¹¾©22ÁÓ'Ì•\nt ’ÄÌœÉOÄç2lÊ³.L¢”HC\0™é2 ó+L¢\\¼™r´Kk+¼¹³Ë³.êŒ’êº;(DÆ€¢Êù1s€ÕÌòdÏs9Ìú•¼ P4ÊìŒœÏó@‹.ìÄáAäÅnhJß1²3óKõ0„Ñ3J\$\0ìÒ2íLk3ãˆáQÍ;3”Ñn\0\0Ä,ÔsIÍ@Œûu/VAÅ1œµ³UMâ<ÆLe4DÖ2şÍV¢% ¨Ap\nÈ¬2ÉÍ35ØòĞA-´“TÍu5š3òÛ¹1+fL~ä\nô°ƒ	„õ->£° ÖÒ¡M—4XLóS†õdÙ²ÖÍŸ*\\Ú@Í¨€˜YÓk¤Š¤ÛSDM»5 Xf° ¬ªD³s¤äÀUs%	«Ì±p+Ké6ÄŞ/ÍÔüİ’ñ8XäŞ‚=K»6pHà†’ñ%è3ƒÍ«7lØI£K0ú¤ÉLíÎD»³uƒêõ`±½P\rüÙSOÍ™&(;³L@Œ£ÏˆN>Sü¸2€Ë8(ü³Ò`J®E°€r­F	2üåSE‰”M’†MÈá\$qÎE¶Ÿ\$ÔÃ£/I\$\\“ãáIDå\" †\nä±º½w.tÏS	€æ„Ñ’Pğò#\nWÆõ-\0CÒµÎ:jœRíÍ^Süí„Å8;dì`”£ò5ÔªaÊ–ÇôE¹+(XröMë;Œì3±;´•ó¼B,Œ˜*1&î“ÃÎË2XåS¼ˆõ)<Í ­L9;òRSN¼Ş£ÁgIs+ÜëÓ°Kƒ<¬ñsµLY-Z’:A<áÓÂOO*œõ2vÏW7¹¹+|ô €Ë»<TÖóÕ9 h’“²Ïy\$<ôÎ#Ï;ÔöÓá›v±\$öOé\0­ ¬,Hkòü-äõàÏš\rÜú²ŸÏ£;„”¹O•>ìù“·Ë7>´§3@O{.4öpO½?TübÃÏË.ë.~O…4ôÏSïÏì>1SS€Ï*4¶PÈ£ó>ü·ÁÏï3í\0ÒWÏ>´ô2å><ëóßP?4€Û@Œôt\nNÀÇùAŒxpÜû%=P@ÅÒCÏ@…RÇËŸ?x°ó\n˜´Œ0NòwĞO?ÕTJC@õÎ#„	.dş“·MêÌt¯&=¹\\ä4èÄAÈå:L“¥€í\$ÜéÒNƒ­:Œ’\rÎÉI'Å²–AÕráŒ;\r /€ñCôÈåBåÓ®Œi>LèŠ7:9¡¡€ö|©C\$ÊË)Ñù¡­¹z@´tlÇ:>€úCê\n²Bi0GÚ,\0±FD%p)o\0Š°©ƒ\n>ˆú`)QZIéKGÚ%M\0#\0DĞ ¦Q.Hà'\$ÍE\n «\$Ü%4IÑD°3o¢:LÀ\$£Îm ±ƒ0¨	ÔB£\\(«¨8üÃé€š…hÌ«D½ÔCÑsDX4TK€¦Œ{ö£xì`\n€,…¼\nE£ê:Òp\nÀ'€–> ê¡o\0¬“ıtIÆ` -\0‹D½À/€®KPú`/¤êøH×\$\n=‰€†>´U÷FP0£ëÈUG}4B\$?EıÛÑ%”T€WD} *©H0ûT„\0tõ´†‚ÂØ\"!o\0Eâ7±ïR.“€útfRFu!ÔDğ\nï\0‡F-4V€QHÅ%4„Ñ0uN\0ŸDõQRuEà	)ÍI\n &Q“m€)Çš’m ‰#\\˜“ÒD½À(\$Ì“x4€€WFM&ÔœR5Hå%qåÒ[F…+ÈùÑIF \nT«R3DºLÁo°Œ¼y4TQ/E´[Ñ<­t^ÒËFü )Qˆå+4°Q—IÕ#´½‰IF'TiÑªXÿÀ!Ñ±FĞ*ÔnRÊ>ª5ÔpÑÇKm+ÔsÇÜ û£ïÒáIåôŸREı+Ô©¤ÙM\0ûÀ(R°?+HÒ€¥Jí\"TÃDˆª\$˜Œà	4wQà}Tz\0‹Gµ8|ÒxçÍ©R¢õ6ÀRæ	4XR6\nµ4yÑmNôãQ÷NMà&RÓH&É2Q/ª7#èÒ›Ü{©'ÒÒ,|”’ÇÎ\n°	.·\0˜>Ô{Áo#1D…;ÀÂĞ?Uô‘Ò•Jò9€*€š¸j”ı€¯F’N¨ÒÑ‰Jõ #Ñ~%-?CôÇßL¨3Õ@EP´{`>QÆÈ”µÔ%Oí)4ïR%IŠ@Ôô%,\"ÕÓùIÕ<‘ëÓÏå\$Ô‰TP>Ğ\nµ\0QP5DÿÓkOFÕTYµ<ÁoıQ…=T‰\0¬“x	5©D¥,Â0?ÍiÎ?xş  ºmE}>Î|¤ÀŒÀ[Èç\0€•&RL€ú”H«S9•G›I›§1ä€–…M4V­HşoT-S)QãGÇF [ÃùTQRjN±ã#x]N(ÌU8\nuU\n?5,TmÔ?Ğÿ’Ü?€ş@ÂU\nµu-€‹Rê9ãğU/S \nU3­IEStQYJu.µQÒõF´o\$&ŒÀûi	ÜKPCó6Â>å5µG\0uR€ÿu)U'R¨0”Ğ€¡DuIU…J@	Ô÷:åV8*ÕRf%&µ\\¿RÈõMU9RøüfUAU[T°UQSe[¤µ\0KeZUa‚­UhúµmS<»®À,Rès¨`&Tj@ˆçGÇ!\\xô^£0>¨ş\0&ÀpÿÎ‚Q¿Q)T˜UåPs®@%\0ŸW€	`\$Ôò(1éQ?Õ\$CïQp\nµOÔJ¹ñX#ƒıV7Xu;Ö!YBî°ÓSåcşÑ+V£ÎÃñ#MUÕW•HÍUıR²Ç…U-+ôğVmY}\\õ€ÈOK¥Mƒì\$ÉSíeToV„ŒÍHTùÑ!!<{´RÓÍZA5œRÁ!=3U™¤(’{@*Ratz\0)QƒP5HØÒ“ÎÕ°­N5+•–ÏP[Ôí9óV%\"µ²ÖØ\n°ıñäG•SL•µÔò9”ùÇÌë•lÀ£ˆ‘\rVˆØ¤Í[•ouºUIY…R_T©Y­p5OÖ§\\q`«U×[ÕBu'Uw\\mRUÇÔ­\\Es5ÓK\\úƒïVÉ\\ÅS•{×AZ%Oõ¼\$Ü¥FµÔ¬>ı5E×WVm`õ€Wd]& \$ÑÎŒÅ•ÛÓ!R¥Z}Ô…]}v5À€§ZUgôÔQ^y` Ñ!^=F•áRÁ^¥vëUÅKex@+¤Şr5À#×@?=”uÎ“s •¤×¥YšNµsS!^c5ğ\$.“u`µÜ\0«XE~1ï9Ò…JóUZ¢@²#1_[­4JÒ2à\nà\$VI²4n»\0˜?ò4aªRç!U~)&ÓòB>t’RßIÕ0ÀÔ_EkTUSØœ|µıUk_Â8€&€›E°ü(â€˜?â@õ××JÒ5Ò½JU†BQT}HVÖ‘j€¤Qx\neÖVsU=ƒÔıV‘N¢4Õ²Ø—\\xèÒÖïR34İG¿D\":	KQş>˜[Õ\rÕY_å#!ª#][j<6Ø®X	¨ìÍc‰•Ø#KL}>`'\0¨5”XÑcU[\0õ(ÔÙÑWt|tô€R]pÀ/£]H2I€QO‹­1âS©Qj•Z€¨¸´Hº´m¨ÌÙ)dµ^SXCY\rtu@Jëpüµ%ÓÿM¸ø€¨óµ“Ö?ÙUQ°\nö=Råar:Ô¿Eí‘À¥-G€\0\$ÑÇd½“ö]Òmeh*ÃìQ‰Wt„öc€¡`•˜AªY=S\r®¯«	m-´‚¤=MwÖH£]Jå\"ä´Ä õş­fõ\"´{#9Teœ‰ÙÍMÔc¹ñNêI£òÙßD¥œõÙÜçUœ6ÙñgÑ2Ù×İ¶eƒa­L´€Q&&uTåX51Y >£óûSıÖŠQ#êIµ¥Õj\0ûœ£ÅW PÑş?ub5FUóLn¶)V5R¢@ãë\$!%o¶ÔPúÉ'€‰EµUÁÔP-†¶š¤Bp\nµF\$ŸS4…t±UF|{–qÖÈ“0û•ÎUmjsÎÃü€²øı\$´Ú›j…cëÚå¦Ö«€¿aZI5X€ƒj26®¤&>vÑ\n\r)2Õ_kîG¶®TJÚÁeQ-cîZñVM­Ö½£z>õ]•a¹c£Ëcìß`t„”HÚÑjİ6¹£+kŠM–\0Œ>Œ„€##3l=à'´¥^6Í\0¨Ã¨v¦Z9Se£€\"×ÊêbÎ¡ÔB>)•/TÁ=ö9\0ù`Pà\$\0¿]í/0Úª•«äµ½k-š6İÛ{küÖá[F\r|´SÑ¿J¥õMQ¿D=õ/ÈWX¢öœV—a¬'¶¹éa¨to€©lå†¶ĞXj}C@\"ÀKPÛÎÖÚom’3\0#HV”µ…v÷Ñ~“{µÖ?gx	n|[Ø?U¶äµ[rê½h¶ŞG¸`õ3#Gk%L£ê\0¿I`CùDŞê¸	 \"\0ˆŒÅ§¶°#cN«6ßÚ¹fÂÔzÛêº;Ñ¤ÃeeF–7Ù/N\r:ôâQñGÕ9	\$ÔóIøÕ¼ºß]£®TİØWGs«ÔdWõMÚIãèÑÙf’BcêÛ¤êõÂ÷!#cnu&(ŞSã_Õw£ùSfë&TšZ:…0CóSÙLN`Ü³Yj=·¶>Å²ÃñZ!=€rV]gû	Ó£rµ ËXlŒÉ-.¹UÄ'uJuJ\0ƒs­J¶'W%·¶­\\>?òBöëV­j4µÏJ}I/-ÒrRLºSè3\0,RgqÓ­ôÇTf>İ1Õï\0¥_•”Ç\\V8õ¡ZÛt…Ácè€†ú<^\\ùll´j\0¾˜şT¥]CİÔw×Î“zI¶ÙZwN…¶¶pVW…jv»Y¶>2Ó	o\$|U‡WÃL%{toX3_õ¶òR‰J5~6\"×ãZl}´`Ôkc­ÑîÛeR=^UÔ•¥1òÑ½w7eØdµİvÙb=á\0ùf €,³må)ÕéGpûÕ-Ó¼½)9Lı“š>|Ôë \"Ì@èû¤5§`†:›ô\0é,€ñt@ºÄxº“òlÃJÈ»b¨6 à…½‰İaŞA\0Ø»ARì[A»Ã0\$qo—AàÊSÒü@Ìø¬<@ÓyÄĞ\"as.âÎä÷V^„•è®¥^õ›…—œ\0ÜÈHÁ·[H@’bK—©Ş)zÀ\r·¨¤¤=éÁ^¿zˆB\0º¿’¤äNéo<Ì‡t<xî£\0Ú¬0*R ºI{¥í®´^æEµî·¸:{KÕ§1Eˆ0²ÓYº•›à/ÕÑcêÀ\"\0„ê¸4øÉF7'€†˜\nÕ0İÉ`U£Tù¤?MPÔÀÓlµÈ4ŒÓr(	´ÁZ¿|„€&†©t\"Iµ¿ÖÛL w+Òm}…§÷€Wi\r>ÖU__uÅ÷63ßy[¢8µT-÷ÙVÏ}¤xãô_~è%ø7Ùß{jMáo_šEù÷ØÓë~]ôP\$ßJõCaXGŠ9„\0007Åƒ5óA#á\0.‹Àä\rË´_Ö¢áÀßÚ%şáÀÀ\n€\r#<MÅxØJËù±|¸Ø2ğ\0¨–;oŒ^a+F€í¸Îç¬€LkúÁ;À_Ûİê#€¾M\\“¬€¤pr@ä“ÃµÆÔøÂşOR€¿ñ–~zÇûAÁNE°YÁO	(1N×‰ˆRø¨8Ø€C¼¦ë¨Én?O)ƒ¶1AçDo\0ä\r»Ç¢?àkJâî‘“„\"â,OFÈÌa…›ùª-bà6]PSø)Æ™ 5xCâ=@j°€ÇL”ÁèÈLî˜:\"èƒ»ÎŠ¤l#¢ÀéBèk£“ˆ›€ÖË@ •Nº:ê>ï|Bé9î	«Èî”:Nıñ\$èéS¥ CB:j6î—Şé•àÎ‰Jk”†uKğ_W›Í¢Ã˜I =@TvãÒ\n0^o…\\¿Ó ?/Á‡&uê.ŞØ_˜æ\r®î¥Cæì+Úøc†~±J¸b†6ÓüØe\0ÍyóÑ¡\0wxêhÁ8j%S›À–VH@N'\\Û¯‡ÆN¥`n\r‹ÒuŞn‰KèqUÃBé+í˜f>G‡°\r¸»ˆ=@G¤Åädç‚†\nã)¬ĞFOÅ hÊ·›†ÃˆfC‡É…X|˜‡I…]æğ3auyàUi^â9yÖ\no^rt\r8ÀÍ‡#óîØâN	VÈâY†;Êc*â%Và<›‰#Øh9r \rxcâv(\raŸá¨æ(xja¡`g¸0çVÌ¼°Œ¿Q†©x(ÇëƒÀglÕ°{—Ægh`sW<Kj°'¿;)°Gnq\$¨pæ+ÎÉŒ_ŠÉdø¶^& ¯Š˜DÂxà!bèvŞ!EjPV¤' ââÁ(”=ÏbÂ\rˆ\"–b¦İL¼\0€¿Ìbtá‚\n>J¬Ôã1;üù¼ÖîÛˆ¿4^s¨QÁp`Öfr`7‚ˆ«xª»E<lÑÏã	8sş¯'PT°øÖºæËƒ¸°z_ÊT[>Ğ€:Ïó`³1.î¾°;7ó@[ÑŞ>º6!¡*\$`²•\0À„æ`,€“øÇàİÁ@°àáå?Ìm˜>ƒ>\0êLCÇ¸ñˆR¸În™°/+½`;CŠ£Õø\0ê½*€<F“„ö+ëƒâ„q MŒÁş;1ºK\nÀ:b3j1™Ôl–:c>áYøhôìŞ¾#Ô;ã´Ü3Öº”8à5Ç:ï\\Şï¨\0XH·Â…¶«aş®¸™M1ä\\æL[YC…£vN’·\0+\0Ôät#ø\$¬ÆØØà!@*©l¦„	F»dhdİıùF›‘à&˜˜Æ˜fó¹)=˜¦0¡ 4…x\0004ED6KÍòä¢£±…”\0ònN¨];qº4sj-Ê=-8½ê†\0æsÇ¨ûˆ¹D§f5p4Œàé©Jè^Öí’'Ó”[úùH^·NR F˜Kw¼z¢Ò ÜĞE”º“ágF|!Èc©ôäo•dbÁêùxß\0ì-åà6ß,Eí„_†íê3uåp ÇÂ/åwz¨( ØexRaºH¼YùceŠš5ê9d\0ó–0@2@ÒÖYùfey–YÙcM×•ºhÙÃ•Ö[¹ez\rv\\0Áeƒ•ö\\¹cÊƒ†î[Ùue“—NY`•åÛ–Î]9hå§—~^Yqe±–¦]™qe_|6!Şóuï`fÕî™Jæ{è7¸ºM{¶YÙ‡©øj‚eÆÌC»¢S6\0DuasFL}º\$È‡à(å”Mb…ÈàÆ¤,0BuÎ¯…ì¥Ñ‚2ögxFÑ™{a¸n:i\rPjıeÏñ˜rÈrØÏGıBY ˆM+qïçiY”dË™é`0À,>6®foš0ù©†o™ó æXf¢äù\0ÀVİL!“«f…†láœ6 Å/ëæ£1eƒ•\0‰>kbfé\r˜!ïufò<%ä(rË›ùa&	ı™¨àY€Ş!¡Òñ–mBg=@ƒĞ\rç; \rŞ5phI 9bm›\$BYË‹ÿšÄgxç#‰@QEOÇæm9–®Ë0\"€ºç!t¨˜ê†Ë‰¸®Ğ‡çO* Ååÿ\0Âİ>%Ö\$éoîrN&s9¿f£4çù™gŠä~jMùf›wyèg›yí\\`X1y5xÿŒù^zï_,& kÑæ¢é|¡€À¦1xçÏA‘6ğ \nîoè”»Œ&xÙïgg™{r…?ç·›ü-°½…®|tä3±šˆÈÍ}gHgK¢9¿¿¨õJÀ<C C° 1„î9ş7‡g÷š‚ïh6!0Hâí›cdy´fÿ¡DA;ƒ‚9…Tæ¢ÿ®0¬Ä\0ÆpØàù†!‡ 6^ã.øSÂ²?ÆØ¦E(P­Îˆ .æÂ 5€ÄhŠéˆEPJv‰ .‹•¢+—\$ç5Œ>P+µ?~‰¡gŒ6\r³öh¢¼p«z(è†WÙÄ`Â•¨±\"y¯ñÏ:ĞFadÅ¬6:ù¡f˜Şi\0ì˜İØàA;áe¢°àì¬ç^ÊÖwf„ >yÍŠËõ`-\rŠÚ…á\0­hr\rÎr£8i\"_Ú	££¼9¡CI¹fXËˆ2¦‰š\"ÍÅ¢‰… øh¢L~Š\"ö…š%V•:!%Šxyèizyg„vxÚ]‚Æ}qgÄÃZiŒä|Œ`Ç+ _úgèòú†™Ù£¾úªÂÀÂè­6PA€Ê€\$¶=9¢ŒùàÍh‹¢|p’ ÿ¢ˆé˜íè!¢.ø!”ş¶üiç§^œøÚiË¢8zVCÌùöŒZ\"€æäØ(Ä¥›¹°9èU)û¥!DgU\0Ãjÿã¿?`Çğ4ãLTo@•B¤§úN†aš{Ãrç:\nÌŸ“E„»8Ã¦&=êE¨*Z:\n?˜¨g¢èÌŠ£‹h¢õ.•˜’ Nş5(ˆSƒhÑôi2Ö*c„fı@•“ÑŞ7¦œz\"áƒ|ÖúrP†.Ç€ÊL8T'¿¸k¢ˆß:(¹q2&œÆED±2~ÿ¿Ø±şœŒ¬Ã9ûÒÂv£©¼8ÿƒ©– @úé^X=X`ªqZºĞQ«Ö®`9jø5^ˆ¹å@ç«¸În¼qv±á¨3±ÚÇèŠ(I6ğªjšdT±ÚÂ\\Š ‚Ÿ3¢,™Ïhék¢3ú(ë3¬‘‘PÒu•VÏ|\0ï§†Uâk;¢ÌJQ¶ã é. Ú	:J\rŠ1ŸênìBI\r\0É¬h@˜¼?ÒN±\nsh—®å\"ë’ò;¦r~7O§\$ ú(ã5¤RÅèÆ	èÊ½jÂîšØFYF šÜ”£«~‰xŞ¾©f º\"ã†vÛ“ošëË¨ººÂº#ŒÜaÒèŠõ¶®P“„Ë<ãáh£-3éº/Gx®õ²nÇi@\"’G…?ó¤,ïZpÖxX`v¦4XÆõóàû„[ƒI¶œ7Ã¥Xc	îÅ!¡bç¢}ÚjŒ_¾¥9á5qti¦6f»’°¸İÙ5ÿûç FÆ¹ãiÑ±©pX'ø2¡rƒ„®0ÆÆºé§D,#GëU2€ÌØâIè\rl(£— €ì±£¦¨=ĞA¸a€ì©³-8›dbSşˆûõ4~‚ô—H;°Â­0à6Çbé{ª„ŞºRæèÃs3zë¯ÃÀüNğŞ„`ÆË†+ò¦­ 4<ø^aƒy°¬”	}r°Âây´õãáû¸kŒ&4@ˆÁ?~ÔäÅcE´ÂÈ­@ˆLS@€Œéz^qqN¦°</H‚j^sCâ`èæsbgGy¹¤Ö^\nÈNó\n:G¶N}¼c\nîÚÕí¤ +£†ï=†pÙ1º’NµTB[dÀÿ¶–š¶Ğ‹¢¾Ü¹ñ`³nÚoj;jÄ›whØõ€c9ƒ‚pÌ¡[y4«¨¶05œÍ‹NßÁ+Î¿·Ğ`Xdaáæ/zn*öPÀ‡êÁ¸#tíèµ¸~à9Wî	šVâò~=¸#Ùùn)¨î´î	2ÜÉ;…j:õ°Ják„C¸!>xîù5š£==¦2»—‚. ã|¿'¨îä[€Ì'—;üÚv½ù«–“¸„®÷ÎëÎ;:SA	º&Ğ[£me†êãn±ëúûªî™«Ëµ¦Ä•<Ÿº6ma‘=Y.ç¥ÀÅ:g¶ÔşÉè…€ù°Ğ;«Iß»xÅ[”éI¡J\0÷~ÂzaY®íºîüwT\\`–íV\nÆ~P)ézJ¾©æ½üñğQ@İà[¶{rÊ‰µDîB„v—ï|i-¹EæøKŒ;^n»{êó½å:Nh;–—Ú2Á¨Æ€pçÑ´6“úƒ»ç½˜9§9¡¥öÖXÂhQœ~—ÛÛiAŸ@D šj‡¥î}ÑozLV÷ïçÑ³~ù•	8B?â#F}F¾Td­ë»áĞe±ÃzcîçŸFÅÀŠg‚7Î—Ûêà€ 6ı#.EÂ£¼áÀÖÂ£¥ğS£.J3¥ö5»¯KÉ¥óJ™§¸;¤—„n5¾¾:ySï‘ÀCÛvoÕ½.˜{ñğ	d\\0ë?W\0!)ğ'šû¼èEgá;à+»\0üY Ntbp+À†cŒø“ş£\0©B=\"ùc†Tñ:Bœ±Á¤úcğïˆşîÆï¸P‘IÜÈD¸ÂV0ÊÇ!ROl‰O˜N~aFş|%Éßº³¸¬…ò)Où¿	Wìo´û‡Qğw¨È:ÙŸlé0h@:ƒ«ÀÖ…8îQ£&™[Ànç¹FïÛp,Ã¦å@‡ºJTöw°9½„(ş†œ<é{ÃÆO\rñ	¥àùÚ‚\$m…/HnP\$o^®U¡Ì\"»¿ã{Ä–…<.îç¡‹n¥q8\rÕ\0;³n£ÄŞÔÛğç¡Ÿˆ+ÎŞ³3¢¼n{ÃD\$7¬,Ez7\0…“l!{˜é8÷á¶xÒ‚°.s8‡PA¹FxÛrğÄÓôQÛ®€¹†1Ì…¸p+@ØdÔŞ9OP5¼lKÂ/¾‘·¾˜\\mæú¸Äs‡q» îvºQí/§ÿÜ	„!»¶åz¼7¾oœ¿EÇ†Ò:qàV 5˜?G¡HO®âO†\$ül¾š+â,òœ\r;ãç°¾¤’~ÎAÄéŒ³é{È`7|‡ÿÄ‚Äàër'‰°Ji\rc+¢|—#+<&Ò›¹<W,Ã>¢»^òPğ&nÂJhĞe‡%d¶æìèÏÜCƒi¶zXÃAÿ'DÍ>ÉÎˆ¡Ek£Ê¬@©Bòw(€.–¾\n99Aê¯hNæcîkN¾d`£ĞÂp`Âò°%2ö¦½3H†Ëb2&¨< 9¤R(òÀ‡táTH¬	àz‘Ö'œ× oòÀ‹>4?Ô\rZÌwÊÓ‚ä×4ƒ`ºÈĞ‡é†µ³N‡ñŸéÓ€î'-IõÈì†÷0(S¨rØw,ü¹ĞåËKÊrÍÌ'-2Hlo-ÁUòáËâ_’'W#'/üÉHÖŸ¤®j6“Ì‰¡¡ÉàÈ«¶\0é„<‘„ÚúŒj1¤E’QŒTÜT­ÆrÁBcmí16ãÍˆgÙ«:w6Í¯›h@1ÅI:¤ÃÁ’Éş2ópò’L/ÎÁŸÂwÿ:òÅ‘ÓÎøK<ğÌE<‚şJ­76Ó€s×.Ì²sZóß/\$÷AsEyÏœàrÚr:w?Õ‰”!Ï?³áêÇ™ĞZ“MÍ9»Õ\0ÏÁ1?ARÍ¦%Ğ7>ÖMÇARr}sé€ñr)\\t-8=³öÍËĞUıË,WOCsÕ†„Ğ#w½5®á¯ERlM*¯D³ç1ûÑ>]ÏÀgK¤²V¹\nÜ\\èÜÓsˆÜ‡8Í¹seÍ§9­soÎ~„ ìów4xàŒ†’ñf@×ĞÜD­ö9€‡ÎÊ6¬\0	@.©î²@´9\0ŠC;Kôy+ÓJğ“ÜÙ¥ƒÏu<\\û`òc{Ó‹¤E£>ÿyÁJ=lŒüïá/…-—7˜ş”ĞZ46¨uC5™‘PçÎ©´RVĞòæ¡ÜáĞıÊ³lVøÒaNxû`Õ´?UÛ7(HP“}jVØJëzNQJ÷S–¸±s-gQ!a¥VØ_SwRıOõ3am‡ZXwZÍo‰'İwa­‰ÖOØoZµ“õ!Ù[\n<ôZ€µO¥Ò¶'ÇÅOmo÷[×Óa=Qºä>‚:õTĞ\nµ¨ç\0Š=€ım×jú–ATÃRÅbu(ÈI×´è:å×\$v¾Wõ×µÃğuÅS¿\\V8Øçvç\\õ•×g!MĞ¶¦uÅÖ_µ&Öis¿\\CÿRVM¢]tXT7\\UoT×Øo_Ô¯İ›S?aÔlÈSØ-LutZGeÇÕái`	}XZ‹i}Q•yW[i­…TŠöYo¦ (ZE\\¨}nÙi—f–‘Ú‹ÙÏW×dÑ%Tıpu3uÍTıf5)vˆÛ]ÕUR3VEY]¥X¸\n·^½§VqS½Sı}XéiGf•Úv>­Sı‚v»JMQšvÚ•Š…ÔÙ\\•g]´QYE“Îİµ#1Vÿl5UØEK]ÕÉ\0³ØİSıU?\\ºBwS•UŠ7–´ÕmZ½V5\\õ¹WfıÂÕ§[¥eUrõ{G\\µıUµÚ,„Éö‘W…[]xö›V×j5mTïV×jİ~u7Ø\0ûV¦UµØ'tı°w?msİÕÔÉÛ5VİÃvİq}Ùöáİu-UqÕ]İ—c]ÚWİØõ]Tt:ífŠM”k¶“e]î¹[-p}^ÔI[©XDãéºåY¿V—dõÀıO]	seNõ£ÜßZ¯WYÚ[Õt…ÈV?ò3ŞÇµßM“öñİ™`Ğût^w£d²:qTL•@@>]Áj\rFİqvµİ-Lv´GKwiôLwIPMo”ùÇ¹Mgv½ÿø[§Uss¦~	èõ…w:BâA‘ŸÑNEù{ä!-ÔÃdıŸo\0´’}&Ş­hXÕÎA–5µ%Ù£fzLÖHÙ5d­” Y…_%…v´Ó™!mšÒ]Öë•ØÒÌ%üñßò€şå=B©>E [#^}öhYFÛa·ßÆ>{¡gS…¶ğp[ìF÷¦ÏDaë6næ´À¶x9«¥8LêIãˆ«N–a=ˆSÊ@úbPk¦.™áNòøHù”l\0ú†:àğè–îŠº2#çÎ˜;¼í®vøO}€9ik]	&®{õ‰ ø«ÕœÙ2|a—·&óãÔÇåÿŞQ½¥ª±ÌîÎç¨)ÉñµoÙ“Ç¸:é&.\0¶5q\0JĞL½é‚64hy€3®Ş¢«¹˜a®Şƒù‚Iz†ÁO‚—–ñ„æï®ˆ\"á¶yB»Ê³{ª3Æ%˜5r(mØÈàÂáÇx.7rÒb%Á‡ü^ e†M€»¢2®\0x—½!‰b}.®âY6\$qS”Ï\"^|xE…äÈøaãş‘¼À€ëXÇ¡5‚9†'T‚R	Ãc9ÄãèW¢1ßáÑAÎ”Pí¦ŸØh6'Şoò-àÖËpµ¾T(\nn\rËÅ“å1Ô„RïRUgÛéƒÈş™“çx¨•Pe#îé*¤âkT<Ÿ<>b;‹“\0™Á˜gL½.<k©ZváÌ„ø¯óz³¶Æ8~¬ğy7€Y¸ïÈêÜ7w¨áOdnÒ>¤<€ú›Eé3ˆ¦wS”Û†œ@¾¡ë® oôWÅ1…ñúñ¾Òº¿zã‰eíŞ½è±å1İˆz÷\0f=ØùcãŠ¤g¹Ÿ{éŞ>nŒp\0±ÍèÎ‘:Hé†BnŒ6FèÆB¯rçW=öãC>M.1~@3ºGí9‡8÷q<Sô|ûY•8QPâû`L[Öqzç˜Û«PÇíèNà<{_-Ù®¥dO¸ùd-îNB7ä4İîBùNÁí.Vº·ç9Æ¨Qø3º{IcP\$§»ºhû¾<R yy…ì?ŞòGÒş:n™ã€µôgÍÁœÿ;Ah!åÔşÁ&å»+>ğË€Û;MÁËŒŞ	ÍşşÃïÿ6SâîŠ·N¸ÚŒ=#ñëëñ³±`üTü#+ìnû;•·r,‚Ç½ğ¦ÏX|#ïÄ\rü# ïÃ?\nüD>¨|VüSñ¿ÂÚeÏ—~Jãm99…á¾\nsÆ{S|r],~ÿË¹ñøé¿ µqÏI?\"|wñ¦øÿ%|Œj‘\0rEò,kSnü¡íç¿øqÆ•Èd8B.ûñ‡1«Ñü³\"™ß/|Æ´€Øƒ]òüˆ¸­€·EüÏœèN²lüÌÕÆxÖËI°÷Ï Icó¿Å¸.|\$8D¹ŸF¨İÌ“…˜PÕKÆò€3ƒô\\j¾¥xUÏC/äã³Ò—¿A{¹ÀĞûşeüÚƒ€ÿÓæ×¶éÜ¾ÿŠÕôà\rpıU\nçÕŸWloÂ­Yâ{ÿô˜ã`]'Öşıs†Õ/|¼oïÿ×à3çÀrü}‹ö;Úÿ[ÊnÎ¹ûÿº—¿OíM7¯ÛÉß£Ø¼q¾µq(ÏĞ_lâqsN÷“yòûñÄçÕ;ŒiÀg¿t—‡ÅÎ:ÿıåÈëÕ™§qk‡¿íôá{÷Ÿß?zı¿÷ÏŞûêñMÈ—ßoıì'àj˜úïá†ãcøyñß„ıãøgß‡gkŒwÉâf8¼VcÔ7fAÌY‘³å+Kxñ…=gKAkşT,95rdã+ùGåÀºíÙ¯„…ñş[Òà%…AÅwæŸµú…½å7ùßåà¬…£%· {½míú8%_”şmú—qˆàVËË¨_ ş“%«!şEƒú¼iø~‘ù²h ú~»ŸCªß­~§ù¨%†„­µ—ç_¨şÙúåÿ·rLkD«yÌúŒğ~Ô?p1O!?¿®vÌ\\ïä±Pm©\"¸Ì<ûŒ¯ïŸÅúE©6… äEŸVğ³åÎñšzkîÇú¦9³zÉªßĞ~Ê/ìäÕº¬é!Q‹>ÿ O£åNmèğ3rˆç Fú˜l‘Òúe;¤Mãß·…ŸºÏ½_a ´!~C»¼f€úå¼b}3œ K¼føÜí. 	Ùä}.©ş»ƒDX	i5¿|úŒ?ğÀ=\0õ±?ï?»ø?£Ş@ˆÿÃ•£½fu~a^’Ønûáªy±Q;ï q¹ÌàŒş)€s’S½,\"G†\nu%ÊÇU­YïAKl\nÓëBØIÊ86VCcO\0Ö`}.x©ƒî„,-Ná‡@~ºèœTÿG›çü–'üÄdÛJƒ÷‚ŸÆy1ƒzl‡á½Ã¦f÷gõ·ùAB aõ!şŒM\\<ƒgÊƒız4Æ¿ìÜ@/³ŞCÜÃ‚ì@õ	¯Qq÷)¤ûxäÁ/Ã.7inD±#=Àœ *79cÂF²ËÑd2(¶ .ÀV€À3µ¿ùÚ\$g`ˆAá§‹rl|øm˜²¶b§‚/¯qE²›ÕÃ´!bU@œ¿9iâ;ppÊdííÛ×¤=ğ1ùy–x°x	™=€v=ø®(v±ï¬s_œ³BoòÉ‚ãÖ#àK\r nñîÈ\\—# Ûf˜PXĞu-3&«	½›J&,FÊ(9¶v´0Á&@khZòy¶gîCÔ‹€z Á”Ãã¦hi=¡s9TñÂ eT>gŒÂ3ëdŞtFûö2b&:¾ğ\0ĞP¡÷€B–š-¹QËº8~ÔLSÆMàˆ™Ú·cgĞÎğTh'òf(Ñ³Ğ\$¨.EŒ«§VLÀ°·œAıI¼ãÃßŒñ†¹¼râ¦ãêgÛ\rÜÙã0§¶œ‚ëTëÎ1P`1’dÔâôÕÄ\r¦4âÁÚ=6@FüÁ¼È F±Ìñœ=¿É‚6ÏA¾Â>åN¥AVß	èÙÚ(\$ÎA/¦·ØÚõ¦;¦­çÚ?¾gŒf^	¬\nè&ğKO³Æn„{]õĞgË›Î8åc¬ÒÑ„–²Ï·Şı³ÿ‚\nÈ7LĞŒ¶‚t:ÒÑ ³hF°VO\r³èJú)bƒ(\"OBÌm°	oØß\$]T„SHÎZ^½õKŒÿ©äwğ\\[A9('ÒÙ„cÛ‘â­Üàb0‚ØÙÄ K’à£åà²srB™x\nè*BaÆz6oƒ\ry&tX1p'›^ƒM·¹<âCg¹`Ì4Ã8GHõ“zd?gX›†.@,‹7wÃïÛ:+ƒTiUX16à“L¸Üs’:\ršLè6‡Á±ƒf—r\r`ãtà67~g°xˆgH9ãJÀ¿O=-\$ğ4?rÙª4½ƒ¨¡O›ûè:z¦§{ÈşD`ó¨‹Ğ21FŒÜµ£Ğ(DòMÓÊ;¥º½ñ&–¡ÍÌ©ÔÚ­¾ƒU>ÎI˜6‹™cİÄò›ß¸@\r/œ/¸¶Ô•ıó_HÀƒ\n7zë ¶ü€“œ‰7òaî É»[9D¢'ü„¿ì}Bÿ€O›R‡ôİŸ¸B#s“¼]z!(DÀ“Å@L^„ı	û³x£İ@oá¿u„OäïÁ¥D¸ÏÜ!e`\na³k>´0`á„€Ì-*™ ˆ8E‡Z6=fÌé%¡™İ×cã›°”K=£ò¤F‡\rÊ…ÂShèyNò[v*vá\rÁää@#ß¸í‰ªAh*ãL\$°À±AÀA\\”¢‚úÓ%Á*	ÄçpŠ\r*==8ì\$Wî\rƒ [±“Jx0yñÛZÃ+&YÙHA~A\n,\\(Öìp¤!F¶êÚ<6SØ&IP`6Xzü+í£dfŞ\r¾ÏJÂ£€ŞÌië•sã+Ò&5¼å/rE…À£M^\$R(R‘QÌÒEw3‰ôlH*m\0Bq¬aŒ¯rèêLB“ª¥Q¹z6~lËùB‰\rIÂ®GøæXÙ¸XVbs¡mB·Hª×ó™ócî_Kç\$pæ-:8„•Nj:ÂÑ…Œ¡-#¢Få	\0’aiBÆs\\)Î<.!Æİ\\ß‰N‹ÒbIw8§Í¹t…øPjWä¨`¶‚y\0ìİ&0˜i?¡ˆÃÒ”:«Ia)=’C†,a&ºM˜apÆƒ\$İI€IFcæ­ç\0!„ƒ˜YÄxa)~¯C1†PÒZL3T¸jİC\0yˆÒ¤`\\ÆWÂü\\t\$¤2µ\næ+a¤\0aKbèíÎ\n„˜]àC@‚º?I\rĞHãƒ®Ks%ÏN©ğ—áË^°ÏÔ9CL/š=%Û¨õhÉÆ:?&PşìEYÒ>5¢ín[GÙ’×%Vàá»*ôw<¥ù­ÕgJ¸]º*éwd®]ŞBŸ5^óÖ¢’OQ>%­s{½Ô…ç•«;ìWö³‰ÖzÂGi®ıÀ*»ùRnìÑG9ĞE°Š¢Ş,(u*°±Õ’Ã—€ŠXÕs«àRŒ¦¦:µ5ë;”æ)°R¶¦ÍNúŠÈvKØ(œR³İM¢œÇbğîÔé©_‡{ÕF<<3ª:%ºÙHVëYS\ná%L+{”o.>Z(´Qk¢ÖÂN«!Ãì,‰:rH}nRÒNkI		ª‡[ò´Ìë’Ó§gÎÎÖ¤;mYÒ³g™%ñ9V~-J_³ñg²­•©Ë\\–É®£Q\n®–!õt«\\UY-tZn¨¡d:Bµ°Ê½Ü*í]')t“²¥wÁù–É«[BUm*Úr4†Ø–Õ*yv¢¶ÁvZÀÕ¹+GHÎåZn°PÂÜ…|\nT¥ %#\\·AX\0}5b+wr«XwÜ²1uù×%Cg=I­òv`creË0`..<·êğh‰+ŒHÌ^\\j­yFòİ%Ê]¹BÊ\0ÉrÅ+€> %Zx¹š æ%C.ªÃìÄ`Vn­1KS¾¥Îk\rƒõçX|´õ[Ì;õ6H	U@©D:Ş»Mj	Î•ÛÊ?ıª]Ú¤Øˆb“A+ÔÅG£\0thxbşÆL`”ÅÀ64MŞ›ÄôŠY#ºhfD=e€Øw=´c˜+H…ñ¡¡:„.%ü^\$òDZrAzjÿfLl›7’o¬Œı°Û\0¨-äÜ³EdäŞ‰yz'V ­“Ó¯W´	Zö§K˜+°d(AÌfyŞP?‡xRš^hõ…¸'•æàA\0ˆ¯:p\r„d(V±ŒÜ½šdöt	SîFcHÈŸ¹]r¢rÊCHY	X_º/fƒŒİÍ½ 4 7eÚ6D³{,ÑèşêØ<<Z^´İj\"	éµ\n+Æ€M…Y9…’A‚(<Pl¤lp	“,>Ğ€¤{E9Ü&àGhšh{(ı±Agg8 (@ŞjTûnËg€Zã†ÙÅ°ÁJˆÁŠ³x¦˜Œü¼@ic¶àÕ‹ô(pƒ'oJ0MnÄ€í&Ê§³\r'\0Õ‘ø„\rqÑFè4½°Š)ı½cL˜§ş_ÀoJÚ}5ïÚc–o¨àà|6„m¾}Qª£á4QëÇb„·µ[úx«m( İ&µ@ä;Â+ò˜¥®ÚÅf|IÎàõ”RĞ48… {	`øè®çk`u»r`èWã¸±`\"´)fI\n©Ô;ò8ZjÍ‡–gğ~¡šAÎˆè!j¼Ä%ÄæT ÂE\\¯\r3E“j‚jê¢FXZ	âÏAyækH ØXdğgCQ“–±´áÎ€ş0ğd”ü²¨°ïû¡†út¨	œÇzkÀ`@\0001\0n”ŒøçH¸À\0€4\0g&.€\0Àú\0O(³ÈP@\r¢èEÄ\0l\0à°X» \râæEä‹Ç8Àx»¥›@ÅÔ‹Ö\0À¤^˜»±z@Eğ‹æ\0Ş.¤^¨¸Qq\"éÅà‹æYäÂD_p&âÿ€3\0mZ.Ppà\r€EÏ‹÷sˆñv\"éÅá‹ç0´`ø¿wâñÆ,óü¼_¼`\rcÅâŒö/Ô]x¸q‚€€3\0qÎ.p˜ÂqŠâğ\0002Œ_ì³i„ˆÄÑŠ¢âEÆ\0aŞ1äbÀÑwJ \0l\0Î1,`ˆº1y\0€9#?0T^ØÇq‘£\$F6Œ/\$d¨¸‘‚€FDŒyJ0b˜»\0	ªÆWŒ¾\0æ.œc¸Â‘{c EØ\0s†3l]@\rbùFŒ\"\0Â2ô`˜Á‘’\"ñ€7‹µÎ/à\0±š¢èÅÓa	^04e¨ºQ{c<ÅÑŒÉj/_˜ÁÑc\0001Œµ*28BAàã\0000ŒxÆ”iØ¾1˜£F50ljH¸‘™\"éFŒ30\\_ˆ¾q™\0ÆfŒ¡T³l_0Ñ‚£BEÄŒ#3ì]øÒñs€Æ½‹Ó†64_XÀ1–\0Æ½‹ñà™d`ø×`\r£SÆ_JMV/f€±­€1\0005I6tf€°ã4Fª‹Á¶34fà‘ ãF-‹ß’6Œd‘±\"÷€4k½„\$h¨Â± #EÅÌŒú\0Ö6¤_01—c@F‹áª/d]X×Q£#G\n‹÷†5¬g¹q‘ãEF\nŒm\\ÂDn˜Åq½£YFv1/4`øàq½ã€4=â8b×q|À\0004‹‰3ÄmXÁ1‹£e‘ö\0Åî.¬\\èàQ—cIÆ	·.7ü\\xÖ`\"íÆ\0i^3ğ(ç±’ÀÆ\"Ev4l_ÈÈq®Œ\$Fñ‹±àœoÈ¾ \r#UEä©^9ütˆÁ‘¹¢ïÆ.\0Ş3|rÈÄ1¿\0Æöù69l^x¹Ñ¼PF-]\n0ÔvˆâQy\"íG‹³2,sxÁQq#™F+Œ\0Ù/DiÈëq}£ÀÇ8[6,jø»\0cmÇo×N5¼ehàQv£«GL€H<T_ĞQ®£?FÉ‹É..\$føÛÑyãšE÷ŒC2Ül¨Û1s#ØEéŒD³lohÙÑ²£j ‹²Â8Ôe¸Å±ÔbğF!õÆ9Ü`xÓq¨£§–CÆ7ÄhxÕÙ£ÆÅ»ú7œ^xÍñğK<Çhƒø	,uØé±‘ãG)Ú;luàÀ#îEß¹ş<ükÛÑíbşÆÜ\0sR.¬w¸Ö±#zÆ~w’2|x(Ú÷âğ\0001'†:Üv‰\0001‘ã¢GæŒ¿¦?|`øò‘£‡ÆóÛ .2¨XÜÀ#“G¨8KÆ@<z¾1–£Æ¹\"9|jˆÒÑĞã	G¤/æ6ÜqˆŞÑö€GÁsÖ7ù/\0001‹büÇßí¶:|ƒ8ÚQÚ#~F»W‚4ég˜ÌÒ#<F\rµ š2üƒXÁQÌ#ÿFvkî7´xÒ1Ú#ÎÅÆ›¦@¬rhÜÑÀãêF”íZ;¬fÈårc¿y‹‘!\r	ä_xë1¿\"üH1Ï¶0TwèÙ²c\rF1 \n8dX»rãĞÆÔŒ§Ş2Dbèı±{d4HˆŒrA<~ÈÙ1±dBHI[J?¼¸ÅÒ£qÇ~kº0ÔtØØÒ#„F\r#0\\h¨î\r¤GÈí’EttØè‘íc7ÈUŒ¿!Ö=D_ˆèòcNÇ\0‘yÖ6aÙñë¤ Fgç!v1ÌqØÈ1ØãKÇ‡»â@äeè÷Ñ³cGoó\n/¬ŒøÆ²ãˆEã‹Á\"3t`©ñö#cHµ‚<ÜcøÓqâüFî%†?Tbè¹±°d)Ç‹© r0‚øÌñqc¿Eøã>3\$tyQÒ£…ÉE’Cl`9)¤VFHMJ7”føöÄ\$HHQ ;üri’7#F³-F¤HÆQ÷#\0G·!‚1ä^Èş&4¤vG&‘û7Ôgèà±ƒ\$\0G\rr/ÄdÙR¤(Æã‘s6@¤“Ù'RAãÇ¬›È”Œù&‘¢¤–Çg\0k z=´|HÙ±Éã‡ÅàŒÉ^J´]ÀÑsd¤Ç,\$’1”¨à<cqÇ¦’ŸêJœ_øÏÁbçGˆQvJ´¸Ø±ŞãH5Œ¢FôpÜÀIc¬È[‹‹Î@ÔrÈÏ¤vHå%ã¶3D”¨Çòc<I\$M.d—Ùr1c=F÷.4„cˆÕ2béG.Œ!¦L|{X×Ñ³£{I«NFôdx÷qscŞÆİ¿#şE¼a)‘Ñ#¹G”ƒJ¬m¹.‘û\$=Gh’AN=¬s‰ÑÅ¤EÍ‘GşG\\a1ò0¤ÛH¡‘ÁF.tg8ê‘Ã¤[Èòÿ¦Idn¸şò8ãF€‹ÙÖ.T’¨ûñ·€F3‘Eº6riq¸ãsF¼Ö6ÄxºrãÚÆL=nFTÒod Ç>-ª3ô|©2\$ı0„‘= â:‘xc’HËI\"NP\$b¸ÛQñ\$Fñ ®DÄ‚˜æÑïä}FêŒ%ª?äŸ(î£êÉG”3\$‚O\$^xÂ2T¢éÆñÕ0Œ¡ğR’‹Ì#ÈDŒ:„òE¤|i/2Œ£XGˆ’”’8¬•¹-ù\$HÉv¥Ö=dš‰ è¤Ç`’ù’:laxäÑú¢ğI¦¢:ì—XâRJ¤Òñ”ÒRÌmxê’J#\nGG“9!N¨ä{cIõ’Ó&æI¬ éR=£€I\rŒù&j:ä‘8ÃÒg#¸H‹á'3„_x¸²b¤H}”£>7ƒèèñŠcÌÇÙ\"&K<xØÊ2¡ãçH†‹¥\"6@dbèë±­e;É)Œ!–.Ä]ù/ò‘d—Êm*f6,v©—ÉªÊ‹£ªLäÉ(qµ£AI8”7d„9TtcôÊ’‚UL•XÈò%H¡”I*z:Ì|IXqsá¨ó-ÂBĞÅäq^(•R¼»aq(~eÑñ¯§ 9JèU‡+-eq*nTà­Ğ>¡\$ÕÑ«er’•Î±¡p\nÅÕ¼Ë\$es+îV£IšºÇb«øeq:ß#]•cc®7r\nÙf,gYø³TC²%Œñ	Ô}Ë\0–²©\\*ìEWPæaè:ÏE¥,&WòÆp)Å¦Ëxl²MáÂÄ3\0t\0¦/IipñD'\0	k\$T¤¬F‡¤]fºÍdMòÈ€K\$”¼ıH(@îÉ”‹»(–zµnWÒ¤Ù_ŠMİ”*º\0¦eÙlF™^H	W*B––ZPe½ÅÖ˜‡ÓR/dRÂ—RÊ…\0Ku£,yH)¶\"SÊXI'®¹Zƒ=çLøRå3åÄÒ\nÀ'š[kğ­Í6@;}R”íıI²ò³ô¬_é) wê‚[óÀ û\nß´n–ª¼ŒÊ“bBr¸l,\$vÖíÍİÔ°‡ˆÀÕH©à‡…\\¢‹Ùs*È ºå–.Qt’B…ºdˆb‘½—@ï?3¼S`a@¤Kª\\.«´à~Çfª)¬«¨ï,?|&Ó¶KÀ£…Z9.İX³+S‘â|ÀœØ\0PÊ¼¢ŒE“òçe‚/Ê\0VëÖ^KÄ\0\n-	:ËÉSØ²)×ªû0j‘9TX•åBğƒ½K\"åÅ¯±•Â²,2Æ'‡2ËåÖ˜P,¡xŠôàpÀĞáKê—ª´š›õ\"ÊD¢#TV²œD¿õ1ñAo;Ø•×/9TH%V`WJ<9˜¯aeÊ° K/V^/¨Q†¤Ø\nBñZ\"9íËÆXÒ¯M~\$°5„ŠßÚ\$0dè½I€U“Í³2¼^X\n¼*ãE7I\nV3«–…+ÎaŒÃIiÒÒNËKK˜g0’aŒ°„z*“V©º#bJyMÒ¦eõâZ– …V ¢`’ĞòĞU1ËC˜Ÿ.\rF²ª-jÎ&LU˜p§9s‚é¹Š+Q&1¨âRm¥ÕÓ±gZª²–	,.XryZì²°0¨ÏÜ3¬2˜A1©Ö‚’e‰Nû©¸˜ú²(?Al ŞÌ,Nèue²Ï\$|rùá_%²ñE05E}³\$¡Ü…X2«%ÚZªe €\n\";<9a¾hã¶¥àa]úÊì™8±à*éu¯åÁªL¥¦¶±dR¿ğ0«¸Áª+ŞQm.ü,Gù–«¦M®ï_±2åedBêÍİ¸,°S…2Á²>UÕêëÔ°»4vlë~e2©ò2¤eÄµËYg2nf’=Àş\$%óÌÙ–Ffaìµ)‹ê§å”ÌfTÆ¶áG¤Í×g2ºW,[™šíÊX>)tÊA]œº™R*º&Z·Å6j2|‘¥\0 °(©p	ê9× ÌùuÒªô?ôĞ`nåœ-lZnë!H9²çæzLğš¢9VLÏ¹yÒĞİ¢ZØJhR›‰g“EfL©UŠ²~`4ÍYˆçæx)\$B±QR#Ã•Sê”¥ËËõ,6i#ÀY¦“,;C±šr¬âiÙ&ÇXªû]èÍ\nw54­K‰x\n*&©Tš£îWüÓùŠ“¦©+SĞ»qNc·yóIWä¯Û\0W5cÔÒÉ«‹ğ&+š¶ğVrå)¬êÎ£Kgšª¾Ô?‰ µŠ“¥|«gR¦¯†hR´%Kë¹œ)Z#‹5ä,Öµ–k…æ¼»`šìl:à•LsC”[M‰UB©6ldÑÑ“J¦°ªŸ•ï1nl:ºù•j¦ËLß–¢\0®hã¶ *)¥p/®šŞ§5\\”<9´óV¦…/‹šŞ«®hTÇdjµårMbx\nˆ]R¹çWªR‰ MaUµ3=×µ`0³oÈË,Z™¬³lÀÅ}Èó¦m¨ì›”í²lôÎ´ÕmLåS6ê\\’tÎ™¹òºèL—îÉ\\Ï%‘J¶”ƒKå™ñ7oÑ©Ÿ¤ef€Mš£’oC»Y¡“væ…­NVÃ4=RÑ¢sJİÉÍö¬¶*hÔÕéhnäæ-m›é4‰ß4ày¤óHñMû›|îÊis¬U=ƒİÚÍA\$Ú­òi¹Ï™¾“…öÍ>–êîÊpâ¼pûóQfø«îšÀ§ªq,ÔÕ5sŠULùš£8}İ¬ÅÙª“Œ÷#ÃXH±ÙİìßI««î§¼9Uµ8íc:³I»îíf´ªĞ±7Òklä5}Ğ÷f¹LY•ğ¬áN2Ş°ó}&½	išê®ñc,åI¹3‹ÚÄRœ©6räØ‰Ì3b¦ûÍœÇ6>lXY¿ûfıLœ)+ÙS,Ù‰Ì*ùelÍô™U\"edæº\"ZçªÚ–6’ZDßE9°á%ÈÎ‚›Y9rmtãEĞó'.M²[4¬‚^„åÉ·ë;M»wÙ5…×Í9¸Òóa¬¦v+70lÍÉÓÓd%£Ì<œù3Š_<é•lN²¦Š(€v+7YRlÎ…Óª]‡.•Õ4©I³®)¼³=ÖƒN®Tš]Û¹'U^Ó?çS«¼½7¾XC®Å©Ó¨Õ1Íu¹9©E´ß™²kçL;œ¤NhÌìÀSİqNXk;1[„ÒõÓLgpVœBî1_¤á¥ÎÅgs¬ š;­RlîÕEˆ×ßNğTÇ8öw,îéÅs¯•1ÍPxrëŠq”ê‰ß3¦¬(ª;ñZÚı	yÓ¾'{O	_´¾êrï™ÈªMg|ÎIó92eLçÊó”f¼O\rYŠnkÜåuŠ™”SNÉv9Vkâ“	Ë3Ç§.Ì›v9zydæ)á“¦ÈNĞYì&s\$ìùÍjd'6Í”œQ<ÍVÜç)èeç+Ï›§:ÑØ¬êYjt¥¡Ãp‡u<±İÊ–Éß3¢]qM°Y:9XãµS³¾gI«Ã*¿mäÆÄCëùıv GßìÜR@ÀÖ¯¬jT—=¨:e ÛÀ(\0_Vn©,?p	3Ş'Î ™¸¨‘Ø™ïÒ\r¬†•¼ö|\"ŞiğºgT’nşPçš¤°\nÓ”åq,ÛSf¸.YĞµQ A¼A‡,ZÊÚeSå›˜sEÀì\rú‘v„T‹¬QŸZ©\"pó²IósëUAÏ›\0¾ëvZ¸}®rÙ¥KŸtféPäf9ç–®¸{¼¶^J€çßÏ‚Ÿ”¿šø©•\n0%«€NGÚ«*~lüD.»¦ÎKeŸ¹6¢[,Ô%ÀˆğOÕ˜É-†~ìµ•–óú¥j®ŸRO;úŒ@	Ë¨en›b_¾%sK¿Åœë‚ÃïYÿæºÎYÑ0ü¥ÃLËWª¦jrßÕóèÏ† ë©!BšÙñ”æ„Pv´£fwÚ«Éø€çãMÃR2´2€zŒ4rúh;Ò#M@…}…\0‰|ëã¨MÃ\0…=Ú=å¡àf-!Ÿ6pÊ g[P4‚´†ÌìóCÚ[5:–‚\rµCt¨ÍÃ u@ıÛº<éŸäif„ĞNu¼n[ñ!u8j{&9Ku FQlR“iÀ(ËC ÇAä®™s4ˆë\0Y Í;fƒB<Ô{”å˜¼R_Iš~š…6ô×|MWTAí]4÷e@J­eÉP|[ú¨–r5*Áÿ—OÎ íBt½)¤ê¯%Ğ-\0Pªjm	usá§}Ğ˜Ÿ“Bi^©Ú*¦zĞ0YK.ù`[¯Yû2íÖĞ«—|°XBÑÅÁÓÁ(?Ğ—±.\$“l¼’³,æÎX¶DçÍ\nêëjæ¡OD ->_<¼¥ÕÖ‡Ù\0š£ÙÕ¬¥Ásøh\\…¡•ea\\Ó\0Êöeä‘™Yµ`¼¥´7UØ\"e¡ÇCYTìñÙzt:V9P™_š³…a‚Ğ•FÔ;İ€\0MŸ¢´†…2“eúëHCéĞóZ‘?îVò¼åœ'×¬å‡ä³}c¾Yüaõè„¬åı?Qh8	ğ´0•Q‡CM`ºŸ«ó6æø,‹Ÿ¢J‘eZ¾Z\"G—Wª¡u†–u\rÕ>49èKı—ğI%L–¹ÍİV9Ïü˜İÖ‰´øZë{VEOÄX;©áÑÏĞoàagPÂ\$\n²RX@}!-Si€òRª¾¢qzÖ	öêITH.¡Ôí\nk\nïš \ndÏ®˜Tº‰²>Ğ\nîÂ– ­?£E…`²Ì5D+f’?#z³…IZü7T[¨€Qs#ùDˆŠ\$«ÕÏPù¢ìI†	û3¾×*¼:İ9YI²ãH‹³ÔH®¬X«0åDŠ!u7J¸–m® YB}Eª°Š³¿—ç®€¢òr”8Q•ù\n}'PõSâ²	Q±Ğõáú¨‘°\$§Å`RÇ)^áõ(O€P\0®aK½µõômè3¬Š\$H.„ùX„ëñÔç)ĞV®™`”­Ú9 ¨.®Y™‘18âÚeUÁ’`Xç9‚´	Œğäç\\Lcˆj°IE Né«ª¦6€W¡D¦XBØ	Z‹:”|Ï¤:	E-P-Ú&ÎÁè¿)ú†ğ§ˆ*ÓúÔlÀ)PÂuŒy|R°³Lhÿ.p¤§é_* QA †@ ·?,Æ§êYêÖ)t‚Ñ‡œ<íÁP*êåÜj’VuQş:2\0L¸?JëçèÑ,TPHL²ÁúE%–¬\0ª¢yP(YJZ¥î©úTHÅX\r	•Q4hOÒ;\\vVõ#åÀTWw‡ï\\`õOÒ¡Å«?ÒJR2³ò’=õFóâ]»ĞŸI5TMjIë9é,(Æ¤Dv|tÉ)ŠWy-¦]z¨Úe‚Œ‰a,pQ6\$ëI-g=%‘SÔW#íTP§Ü¤É)«T&]ŞÑõX15j†”B8„„æVÏÓ¥\nìem y“”h›*è¤ü»„°dç4Ï‚·bd!0¤gR”J\\Í ÖMtƒÀ1R\n\nïâxè¡èÜÁª.ö_¾üuò+Æ¼Ç;ı‹*4ˆÎ¸)]À\\¡lÜ(m\"ñƒQ†nTˆ(*\0¬`ğ1Hì@2	6hàêYÀcH_ÌÚÈfğ?°Ğa«–7=KKdeÂt÷HàÀ2\0/\0…62@b~Ë`·\0.”€\0¼vÙ) !~º€JPÄT—Á½ô½’–…µ¥óÂ—ÚOƒ{t¾¾\0005¦¾˜/à¯€\r©ƒÁJ^ğ½0Úa!¶)€8¦%KŞ˜PP4Åé~ÓH’˜á÷ĞÅô¼Üí\r+¦Lb˜¥/24)“Ó¦GKê™e0ŠeËé€S1¦B¨	-0jfÔÄéšS¦wLÎ™Äiêd …é Ó¦Lºš\r1ºhôÈ©œS ¦—MJJÊht¾)¨Ó+?L¶še5n”Óé|FHŒÉMN—õ5êjÔÉ©™SH“ÕL–—å4É=TØé´ÓD“ÕMnš½6Zm@I@S`¦)'ª™Õ7fòz©ŸSz¦x~OU1k”¿¤õSF¦ıMOU4ªpôÙ£2\0000¦ì¾7…6ŠkÑ#xSl§'Kâ7…7\nl”ÍãxSu§LR7…7šstßãxS}§GM7…8*qtÓ#xS†§OM\"7…8ªuôë)ÆÓ\0¿’š•9úr™)ËSr¦‰2šı; ôğ)ŞÓ7§Nj›m/Šxç©ÕÓ¿¦sNÚ:jy4¿©àSª§gO:1ı=\ncTö©§SÍ§•’œ•;ê{ñ¥©îSÈ§/ORH\r=ÊtTôéŠIİ§¥O˜¤\\zx4÷©Sò§‹MşŸ•>j|TıiºS¶‘³O†™¼š~ôĞ\$lÓú¨Oöš}tüÈÙ§ßOî˜¤šzÔû*%§]PPüšvU\"úÓİ§¯Kâ í@\noõjÓH¨;P¡>š1£éÿFd¨P.5BØ¸•ª\rÔ¨3œuB¹<µL#Ô<¨QPECÊu*\nÅÛ¨yPN¡´lª‚õ\r‹6Óó¨?Kú¢mBZi•jÓH¨›O2¢}1J‰µé›ÔM¨_Mş¢mDŠˆ€ê&ÔK¨ÇQ6¡­Fzv´ğ‹6Ó¹§éQjå;jµj)Ô*¨Ş¾£mEÊŒª9Fd¨ÅQv5eGØÉµd¤Ô„¨EM\0+åDêƒ\"j)SD©QÒ¤pZfµéÆ‚§mR&¢ıHŠ’U’Û%§{Rv0m0z”¥ä§ŸLÆ¥@ú”'ÖÔ©ER¶?eJ÷>é¸Ô¨İM’¥µIú•²ªYT¦ÛRõ/¥BÊ•.êUT»©YRÎ¡L:™jNÔ…©•Rš¡İLú˜5ji&,‰Oê¦mJDß5,ã9ÔÀ©­Q¦©Íè•1êhTf©›NÈ˜ÒÑŞ¥Q€'©Î7¾§Lih¸²\rcjÔŒ‘Sz§ušŸ\0nãÔº©g¶§Ø9Õ@cÕŒ\rT§%LÅÕAªfT­MT9uQ\nŸÕ)¢çU©µSº¨uD:“±—jˆU	©­Æ¨…PÚ–q‰*‚EÚªKSb¥l\\Ú¤µFª”ÔÅªGTz§gJ¤µHªSFª	\"©½Q:˜1‘ê›Õ©;†©½Rê¦µL*~EßªoTÒ¦\\z ‘„ª¥Õ:©­âª]Sê•±Ÿª¥ÕBª“U¨^J©uR*kEõª	ªıTêœQtê¯ÕR©g2ªıUj«µV\$ÅÕ_ª¹Sˆ³mPHÆU\\ª±TüŒ[UÊ«5JhÙµ\\ªµUpªÙ¢«•Vğ7a_*€Ó«¬=R‡>\0I*¼¥ô”V«íX:hU8jÉTæKZ’¬\\:ƒÕ)jÇT·«8˜±	åWZ³Ub’òJ8«R­=Y³UVU–«R¬¤\\:™Õ-jËÔÑ«iV.¦¥[z´±ÒªÂÇ-«{T²­ÅZªuoj×U»«3 ¡Í[ª±Õ>ªØÈ«E ­%\\º±µh#bÕ…‹©WZ®-\\º¸õCêæÕ«»W>¨­]Úºg4#¶ÕÀ«KTr®íZÊ¤wjãÕ\$«›z¬-Rj½õtjĞU*«ßWš¬tp\n¾4õ€ğ'–N•Mº´²ªxUş™X32[xò•+®“Ë\$B°US*½õqê›UÍªqXZ®}SÊÂÕxêÁÕ@¬-W\n5İXZ¨Õ…ªãÕJ«›U2±=\\úª‰ëF+«ñV‚0]XXÁUŒªìÖ0«¬-VJ¹²+Ö/«É‚±ÍZÊ®5sj¹ÖD«ŸUŞ²%bØÉµªÁÇ÷«V²%Yš^u@d¤Õ¢’“WĞæ„”šÅ²Rk&œŒñYR¬\\¤Å’RkÖY©cVÆO-\\š—	kdòÓáKoX²¥KÊÍ/ë9Ö]“ËVªO-U‰<µ™@İÉå¬¥VÎ³[Ÿõ›«6U¹­—Â=eŠÏµo«4Tİ­Yâ0eHÆÕ¤ª\rÊÍ9«¢•¬6à(ó®•+7ÎybÓrI §|Ä\0—:FzğÉè\n…§|ªœs<°R½%JÓËÔ]¦õFèµ3õ­Œ‰j¢Î£¹Y®µZ“¾^<5X·IJòÅM`×nO\\£B&¶r“õsÅçQˆuz¨¢x¼å¹è	¬Tˆ®¤VwÍJ5¸g	Ï?v¨qF4ï•9³Ó·»­Õ6ªzjùèÕ‡OV•¿\rÎuÊ=Â@Ê’fTÍšœğïöy´³	€Ö«pKaXU9šm²³…­\nekMo›Ã5\nhTŞ†ê¦¦…V ®¬v€‚ı:®Ñs®\\p>ÁÒLÓ:¦‹)ñ­O=nk}j¥Sõ«&·Ö®ª~µŠ¤y©àe”¬ÜšßZÖµñ)jØ®”t×VR¢Vµ½sµrÊ:+aÍo­‹,!TılŠUÏ•Ş*n­›5¾¶\\ğU÷dv+’M\\®)]B¶|ñJë´¦l;4˜¯5öpLÖùÓµØ¦7Liı[~bmtÉæSe€\"»°›Bº½v©´d“ç@Í§SÁ4)Ø’—Zï¼»\$)®ñ5ic!™µ´¢½ÎŒ–êî\\Rù*ßSD¦’Îw\$›9ætSÁ\ná”GfòPÔ›ÆîÊ¸´ßÚã*¦	KÍô­D·Vyû¹5ÍuÈ¦J×‘š\\šµC¹•\$“ÙW,¯M\\º»ôåÊæ5¬ëÓ–®k^•VÕsŠè5®k¡Ö»¯M^êµı{Àu°§Ï¤wFQàßJéHûgWN¡k8şºÍŠôÊ‰+¸»§˜¥1brÄíùË•ØëÓVÜX]dLçjí´YT™Îv®ç6–twyË•Şkò×ë­à«vx=…5àh»²ï½ô8—]ÊÁ‘ñË·x\"c|ĞufUÿƒşØ\0˜Ò§5ŞjÈ©}”PknÌšRl¾‰fÙªà+ò“ÑÛ£‚¢>c4Æ×W+TıDo®Òï ’Ç÷qî¯É€SX’¨İb}}Åhnµ&<Ï?™/3º”-Ã¡h†°©qn‰ı§	õpƒ%)SÉyP\r…ÛÍµÿm-Ïf5°Šº[€\\–=ÌTà}øy )ıç Ydç«Ø¤46#Y>¥3ÔŒ× šm©ú\n09h;²4˜°Â0‚Ã+ßae\nÈƒÄ°È!ÊÅüÑ)‘@ôx¢x}‡\$¦ÖßıAFŒúÃ‘²0Nö Rã	º°şÓ„èiÜ¥ü¬U¬?½¡—b5í!+×­\0G˜ıØw{¶îÓ¤—ïlI £)’w-4;p8ÂÎØ¤;@\r\n\r­…ÚN5Æ…F\\Ó¹hgPE il0¦ëX¦%’)\nˆØLkÈ^‚Æ2¢İ<5FØìd‰Iƒ<ñFÆj³bM¬d'á	¶Æ²D£âîBma²ĞÒö…ıOYñXgg¼8¥çZVØ%mf¬Ô%å€F¡-¥,É\nƒ‘ıaù¤FÇwfƒôs¹ç¬Ê0Gä¹‘ØZ²\n	1†;Jí–1Á\"iPñBÈy´C¬–Ìû²t—zÓ‰ãÑÖ;l‚4âÈÒ¡‚ƒJ‡”mLX²+lá˜ªõ{Â8¬\"â\nÌVÁÀšÄÛ(Ú\$Y\0íd\\İ†6›D9B´H±d%¦Óî–1ÛÁ˜6f Ñ\"ÊTJÖÚ`/²‡>ÊC=Äc“ì¨±¼²?e!ık*±3l~ƒÃÓiÿ«,×A‚z/dà¨¦MoìÅí´Ú²nÑ\"É½„ÍÂëÆzTr}eÙŒ{MÀaCÔ7‘fiTºõ—Ë/6W¢©P²ìÖÌ8†Fa`İì¾5³ó©¹M…f2V]œ['}cn4]h·íÖe«¦‹Z€Å§\r™‹2ÉÈ½XllGa`(­™—Û(‚ŠÄò\0èÄıšĞ_ölO˜ùf&fÄ1c8ìD{¼QæÜ	S6öp\0äYÂ˜æ¹˜™î\0\röq…3m&*fÎ;Ìpò6r^cŒÏ³¨—`Éµ&z€n^Ú±ù;DÈèSã¤oj^ã=¿L'g”5œ“Ä&ƒìä‡Ef&ñŞÏ|\nK 6?bX*¬.fÏˆEƒû–~&9Ù!˜çdŒk@‰v\"F¬Gšx\\é=ıEŠ7ïXP2[:Á¶\0ƒ×à¡ X~¦½7·ÍâX6†4²œÉ(Ã\";Bì\nŞıX×Ñhy¹Ì&›DÖˆÛZ¼l\nKC–‰íšŸ†pØ’Ä`mS®	2ĞU¢;Gà•‘8¶´{’Ñ-”±WBmì¸\$F€ø\ràl&B‡Y2\r´¨mAÅ‘°wÄZØ6ØRĞ’¿Ğ%d´ŒİÂÚ_²œTô5¦``BaĞÙG´ÕcáXKö\r¶˜\0­ØgN¼ù\\‘´¾;Nà¨àÄÚs^\nŒÌu§ä¿Ÿ­Ñ²VwzÄU F\"\0T-±,^’Î\0‹Îö—è2 /æ™ óÂÏàEW/\0Â¼ò–ÒÄ¾Ë4;\"ìK-NZš½ĞMcÎ»RVNeœZ¦wj–ÂŠ6ë¯a¶÷yÌˆÙç»‹KV®lN?±Ãjt2­–¶T/[íN¤û±j|0t% #°”€âÑ\0ôÓ`£ø5F<–´ƒ X@\nÓ¢Áí•ËZF\\-m›¼³cd2Äp5Gºv'Bß'¢7{kŠ*'LÜAªZ|I±k´\n-.C¢6¼«¹Çk•-¯×©SÚú°÷kÑ]¯Ë_\$…Ú+Gò× [^‡­­z]kÑÑ8›\\ö¿F|§¢?BˆØÁ^ÏB¨‰Ì|ñ™ë@Š­Â÷B¯¥zPéW/R?[!bB–á¹kÀ‰Ñ '	(ãe:xfàr‚7\r_íâq¶Maê\0#±ä7|éQ&\0É@)µô†À1òë®†LA[PtÀ\0œ™ı`‡6Õ\\e‘Ÿ¶zxÒÚSİ€vÕˆÏ€U:Ú±¿T¼Á‡ˆÏ—>fÛ\nq‹l€Å+K(|¶\\´Ñ G›UØ‹³Æ@(ğ*ÉiS%F¨\rR\$©•C¶¶LĞİÄö;ÉdµìÄ¼gë-\$m?ölhÊŠ3?PªY\0");}else{header("Content-Type: image/gif");switch($_GET["file"]){case"plus.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0!„©ËíMñÌ*)¾oú¯) q•¡eˆµî#ÄòLË\0;";break;case"cross.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0#„©Ëí#\naÖFo~yÃ._wa”á1ç±JîGÂL×6]\0\0;";break;case"up.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0 „©ËíMQN\nï}ôa8ŠyšaÅ¶®\0Çò\0;";break;case"down.gif":echo"GIF89a\0\0\0001îîî\0\0€™™™\0\0\0!ù\0\0\0,\0\0\0\0\0\0 „©ËíMñÌ*)¾[Wş\\¢ÇL&ÙœÆ¶•\0Çò\0;";break;case"arrow.gif":echo"GIF89a\0\n\0€\0\0€€€ÿÿÿ!ù\0\0\0,\0\0\0\0\0\n\0\0‚i–±‹”ªÓ²Ş»\0\0;";break;}}exit;}if($_GET["script"]=="version"){$qd=file_open_lock(get_temp_dir()."/adminer.version");if($qd)file_write_unlock($qd,serialize(array("version"=>$_POST["version"])));exit;}global$b,$f,$k,$mc,$uc,$Dc,$l,$sd,$zd,$ba,$Zd,$w,$ca,$se,$vf,$hg,$Lh,$Dd,$si,$yi,$U,$Mi,$ia;if(!$_SERVER["REQUEST_URI"])$_SERVER["REQUEST_URI"]=$_SERVER["ORIG_PATH_INFO"];if(!strpos($_SERVER["REQUEST_URI"],'?')&&$_SERVER["QUERY_STRING"]!="")$_SERVER["REQUEST_URI"].="?$_SERVER[QUERY_STRING]";if($_SERVER["HTTP_X_FORWARDED_PREFIX"])$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];$ba=($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure");@ini_set("session.use_trans_sid",false);if(!defined("SID")){session_cache_limiter("");session_name("adminer_sid");$Uf=array(0,preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),"",$ba);if(version_compare(PHP_VERSION,'5.2.0')>=0)$Uf[]=true;call_user_func_array('session_set_cookie_params',$Uf);session_start();}remove_slashes(array(&$_GET,&$_POST,&$_COOKIE),$cd);if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);@set_time_limit(0);@ini_set("zend.ze1_compatibility_mode",false);@ini_set("precision",15);function
get_lang(){return'en';}function
lang($xi,$kf=null){if(is_array($xi)){$kg=($kf==1?0:1);$xi=$xi[$kg];}$xi=str_replace("%d","%s",$xi);$kf=format_number($kf);return
sprintf($xi,$kf);}if(extension_loaded('pdo')){class
Min_PDO{var$_result,$server_info,$affected_rows,$errno,$error,$pdo;function
__construct(){global$b;$kg=array_search("SQL",$b->operators);if($kg!==false)unset($b->operators[$kg]);}function
dsn($rc,$V,$F,$D=array()){$D[PDO::ATTR_ERRMODE]=PDO::ERRMODE_SILENT;$D[PDO::ATTR_STATEMENT_CLASS]=array('Min_PDOStatement');try{$this->pdo=new
PDO($rc,$V,$F,$D);}catch(Exception$Jc){auth_error(h($Jc->getMessage()));}$this->server_info=@$this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);}function
quote($P){return$this->pdo->quote($P);}function
query($G,$Gi=false){$H=$this->pdo->query($G);$this->error="";if(!$H){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error='Unknown error.';return
false;}$this->store_result($H);return$H;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result($H=null){if(!$H){$H=$this->_result;if(!$H)return
false;}if($H->columnCount()){$H->num_rows=$H->rowCount();return$H;}$this->affected_rows=$H->rowCount();return
true;}function
next_result(){if(!$this->_result)return
false;$this->_result->_offset=0;return@$this->_result->nextRowset();}function
result($G,$m=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch();return$J[$m];}}class
Min_PDOStatement
extends
PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch(PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch(PDO::FETCH_NUM);}function
fetch_field(){$J=(object)$this->getColumnMeta($this->_offset++);$J->orgtable=$J->table;$J->orgname=$J->name;$J->charsetnr=(in_array("blob",(array)$J->flags)?63:0);return$J;}}}$mc=array();function
add_driver($s,$C){global$mc;$mc[$s]=$C;}function
get_driver($s){global$mc;return$mc[$s];}class
Min_SQL{var$_conn;function
__construct($f){$this->_conn=$f;}function
select($Q,$L,$Z,$wd,$Ef=array(),$y=1,$E=0,$sg=false){global$b,$w;$ge=(count($wd)<count($L));$G=$b->selectQueryBuild($L,$Z,$wd,$Ef,$y,$E);if(!$G)$G="SELECT".limit(($_GET["page"]!="last"&&$y!=""&&$wd&&$ge&&$w=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$L)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($wd&&$ge?"\nGROUP BY ".implode(", ",$wd):"").($Ef?"\nORDER BY ".implode(", ",$Ef):""),($y!=""?+$y:null),($E?$y*$E:0),"\n");$Hh=microtime(true);$I=$this->_conn->query($G);if($sg)echo$b->selectQuery($G,$Hh,!$I);return$I;}function
delete($Q,$Ag,$y=0){$G="FROM ".table($Q);return
queries("DELETE".($y?limit1($Q,$G,$Ag):" $G$Ag"));}function
update($Q,$N,$Ag,$y=0,$mh="\n"){$Yi=array();foreach($N
as$x=>$X)$Yi[]="$x = $X";$G=table($Q)." SET$mh".implode(",$mh",$Yi);return
queries("UPDATE".($y?limit1($Q,$G,$Ag,$mh):" $G$Ag"));}function
insert($Q,$N){return
queries("INSERT INTO ".table($Q).($N?" (".implode(", ",array_keys($N)).")\nVALUES (".implode(", ",$N).")":" DEFAULT VALUES"));}function
insertUpdate($Q,$K,$qg){return
false;}function
begin(){return
queries("BEGIN");}function
commit(){return
queries("COMMIT");}function
rollback(){return
queries("ROLLBACK");}function
slowQuery($G,$ji){}function
convertSearch($t,$X,$m){return$t;}function
value($X,$m){return(method_exists($this->_conn,'value')?$this->_conn->value($X,$m):(is_resource($X)?stream_get_contents($X):$X));}function
quoteBinary($ch){return
q($ch);}function
warnings(){return'';}function
tableHelp($C){}}$mc["sqlite"]="SQLite 3";$mc["sqlite2"]="SQLite 2";if(isset($_GET["sqlite"])||isset($_GET["sqlite2"])){define("DRIVER",(isset($_GET["sqlite"])?"sqlite":"sqlite2"));if(class_exists(isset($_GET["sqlite"])?"SQLite3":"SQLiteDatabase")){if(isset($_GET["sqlite"])){class
Min_SQLite{var$extension="SQLite3",$server_info,$affected_rows,$errno,$error,$_link;function
__construct($o){$this->_link=new
SQLite3($o);$bj=$this->_link->version();$this->server_info=$bj["versionString"];}function
query($G){$H=@$this->_link->query($G);$this->error="";if(!$H){$this->errno=$this->_link->lastErrorCode();$this->error=$this->_link->lastErrorMsg();return
false;}elseif($H->numColumns())return
new
Min_Result($H);$this->affected_rows=$this->_link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->_link->escapeString($P)."'":"x'".reset(unpack('H*',$P))."'");}function
store_result(){return$this->_result;}function
result($G,$m=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetchArray();return$J[$m];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;}function
fetch_assoc(){return$this->_result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->_result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$d=$this->_offset++;$T=$this->_result->columnType($d);return(object)array("name"=>$this->_result->columnName($d),"type"=>$T,"charsetnr"=>($T==SQLITE3_BLOB?63:0),);}function
__desctruct(){return$this->_result->finalize();}}}else{class
Min_SQLite{var$extension="SQLite",$server_info,$affected_rows,$error,$_link;function
__construct($o){$this->server_info=sqlite_libversion();$this->_link=new
SQLiteDatabase($o);}function
query($G,$Gi=false){$Ve=($Gi?"unbufferedQuery":"query");$H=@$this->_link->$Ve($G,SQLITE_BOTH,$l);$this->error="";if(!$H){$this->error=$l;return
false;}elseif($H===true){$this->affected_rows=$this->changes();return
true;}return
new
Min_Result($H);}function
quote($P){return"'".sqlite_escape_string($P)."'";}function
store_result(){return$this->_result;}function
result($G,$m=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->_result->fetch();return$J[$m];}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;if(method_exists($H,'numRows'))$this->num_rows=$H->numRows();}function
fetch_assoc(){$J=$this->_result->fetch(SQLITE_ASSOC);if(!$J)return
false;$I=array();foreach($J
as$x=>$X)$I[idf_unescape($x)]=$X;return$I;}function
fetch_row(){return$this->_result->fetch(SQLITE_NUM);}function
fetch_field(){$C=$this->_result->fieldName($this->_offset++);$fg='(\[.*]|"(?:[^"]|"")*"|(.+))';if(preg_match("~^($fg\\.)?$fg\$~",$C,$B)){$Q=($B[3]!=""?$B[3]:idf_unescape($B[2]));$C=($B[5]!=""?$B[5]:idf_unescape($B[4]));}return(object)array("name"=>$C,"orgname"=>$C,"orgtable"=>$Q,);}}}}elseif(extension_loaded("pdo_sqlite")){class
Min_SQLite
extends
Min_PDO{var$extension="PDO_SQLite";function
__construct($o){$this->dsn(DRIVER.":$o","","");}}}if(class_exists("Min_SQLite")){class
Min_DB
extends
Min_SQLite{function
__construct(){parent::__construct(":memory:");$this->query("PRAGMA foreign_keys = 1");}function
select_db($o){if(is_readable($o)&&$this->query("ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$o)?$o:dirname($_SERVER["SCRIPT_FILENAME"])."/$o")." AS a")){parent::__construct($o);$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return
true;}return
false;}function
multi_query($G){return$this->_result=$this->query($G);}function
next_result(){return
false;}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$qg){$Yi=array();foreach($K
as$N)$Yi[]="(".implode(", ",$N).")";return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys(reset($K))).") VALUES\n".implode(",\n",$Yi));}function
tableHelp($C){if($C=="sqlite_sequence")return"fileformat2.html#seqtab";if($C=="sqlite_master")return"fileformat2.html#$C";}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){global$b;list(,,$F)=$b->credentials();if($F!="")return'Database does not support password.';return
new
Min_DB;}function
get_databases(){return
array();}function
limit($G,$Z,$y,$nf=0,$mh=" "){return" $G$Z".($y!==null?$mh."LIMIT $y".($nf?" OFFSET $nf":""):"");}function
limit1($Q,$G,$Z,$mh="\n"){global$f;return(preg_match('~^INTO~',$G)||$f->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($G,$Z,1,0,$mh):" $G WHERE rowid = (SELECT rowid FROM ".table($Q).$Z.$mh."LIMIT 1)");}function
db_collation($j,$nb){global$f;return$f->result("PRAGMA encoding");}function
engines(){return
array();}function
logged_user(){return
get_current_user();}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");}function
count_tables($i){return
array();}function
table_status($C=""){global$f;$I=array();foreach(get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){$J["Rows"]=$f->result("SELECT COUNT(*) FROM ".idf_escape($J["Name"]));$I[$J["Name"]]=$J;}foreach(get_rows("SELECT * FROM sqlite_sequence",null,"")as$J)$I[$J["name"]]["Auto_increment"]=$J["seq"];return($C!=""?$I[$C]:$I);}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){global$f;return!$f->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
fields($Q){global$f;$I=array();$qg="";foreach(get_rows("PRAGMA table_info(".table($Q).")")as$J){$C=$J["name"];$T=strtolower($J["type"]);$Yb=$J["dflt_value"];$I[$C]=array("field"=>$C,"type"=>(preg_match('~int~i',$T)?"integer":(preg_match('~char|clob|text~i',$T)?"text":(preg_match('~blob~i',$T)?"blob":(preg_match('~real|floa|doub~i',$T)?"real":"numeric")))),"full_type"=>$T,"default"=>(preg_match("~'(.*)'~",$Yb,$B)?str_replace("''","'",$B[1]):($Yb=="NULL"?null:$Yb)),"null"=>!$J["notnull"],"privileges"=>array("select"=>1,"insert"=>1,"update"=>1),"primary"=>$J["pk"],);if($J["pk"]){if($qg!="")$I[$qg]["auto_increment"]=false;elseif(preg_match('~^integer$~i',$T))$I[$C]["auto_increment"]=true;$qg=$C;}}$Ch=$f->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i',$Ch,$Ie,PREG_SET_ORDER);foreach($Ie
as$B){$C=str_replace('""','"',preg_replace('~^"|"$~','',$B[1]));if($I[$C])$I[$C]["collation"]=trim($B[3],"'");}return$I;}function
indexes($Q,$g=null){global$f;if(!is_object($g))$g=$f;$I=array();$Ch=$g->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$Ch,$B)){$I[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$B[1],$Ie,PREG_SET_ORDER);foreach($Ie
as$B){$I[""]["columns"][]=idf_unescape($B[2]).$B[4];$I[""]["descs"][]=(preg_match('~DESC~i',$B[5])?'1':null);}}if(!$I){foreach(fields($Q)as$C=>$m){if($m["primary"])$I[""]=array("type"=>"PRIMARY","columns"=>array($C),"lengths"=>array(),"descs"=>array(null));}}$Fh=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q),$g);foreach(get_rows("PRAGMA index_list(".table($Q).")",$g)as$J){$C=$J["name"];$u=array("type"=>($J["unique"]?"UNIQUE":"INDEX"));$u["lengths"]=array();$u["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($C).")",$g)as$bh){$u["columns"][]=$bh["name"];$u["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($C).' ON '.idf_escape($Q),'~').' \((.*)\)$~i',$Fh[$C],$Lg)){preg_match_all('/("[^"]*+")+( DESC)?/',$Lg[2],$Ie);foreach($Ie[2]as$x=>$X){if($X)$u["descs"][$x]='1';}}if(!$I[""]||$u["type"]!="UNIQUE"||$u["columns"]!=$I[""]["columns"]||$u["descs"]!=$I[""]["descs"]||!preg_match("~^sqlite_~",$C))$I[$C]=$u;}return$I;}function
foreign_keys($Q){$I=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$J){$p=&$I[$J["id"]];if(!$p)$p=$J;$p["source"][]=$J["from"];$p["target"][]=$J["to"];}return$I;}function
view($C){global$f;return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',$f->result("SELECT sql FROM sqlite_master WHERE name = ".q($C))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($j){return
false;}function
error(){global$f;return
h($f->error);}function
check_sqlite_name($C){global$f;$Sc="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Sc)\$~",$C)){$f->error=sprintf('Please use one of the extensions %s.',str_replace("|",", ",$Sc));return
false;}return
true;}function
create_database($j,$mb){global$f;if(file_exists($j)){$f->error='File exists.';return
false;}if(!check_sqlite_name($j))return
false;try{$z=new
Min_SQLite($j);}catch(Exception$Jc){$f->error=$Jc->getMessage();return
false;}$z->query('PRAGMA encoding = "UTF-8"');$z->query('CREATE TABLE adminer (i)');$z->query('DROP TABLE adminer');return
true;}function
drop_databases($i){global$f;$f->__construct(":memory:");foreach($i
as$j){if(!@unlink($j)){$f->error='File exists.';return
false;}}return
true;}function
rename_database($C,$mb){global$f;if(!check_sqlite_name($C))return
false;$f->__construct(":memory:");$f->error='File exists.';return@rename(DB,$C);}function
auto_increment(){return" PRIMARY KEY".(DRIVER=="sqlite"?" AUTOINCREMENT":"");}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){global$f;$Ri=($Q==""||$kd);foreach($n
as$m){if($m[0]!=""||!$m[1]||$m[2]){$Ri=true;break;}}$c=array();$Nf=array();foreach($n
as$m){if($m[1]){$c[]=($Ri?$m[1]:"ADD ".implode($m[1]));if($m[0]!="")$Nf[$m[0]]=$m[1][0];}}if(!$Ri){foreach($c
as$X){if(!queries("ALTER TABLE ".table($Q)." $X"))return
false;}if($Q!=$C&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)))return
false;}elseif(!recreate_table($Q,$C,$c,$Nf,$kd,$La))return
false;if($La){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($C));if(!$f->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($C).", $La)");queries("COMMIT");}return
true;}function
recreate_table($Q,$C,$n,$Nf,$kd,$La,$v=array()){global$f;if($Q!=""){if(!$n){foreach(fields($Q)as$x=>$m){if($v)$m["auto_increment"]=0;$n[]=process_field($m,$m);$Nf[$x]=idf_escape($x);}}$rg=false;foreach($n
as$m){if($m[6])$rg=true;}$pc=array();foreach($v
as$x=>$X){if($X[2]=="DROP"){$pc[$X[1]]=true;unset($v[$x]);}}foreach(indexes($Q)as$me=>$u){$e=array();foreach($u["columns"]as$x=>$d){if(!$Nf[$d])continue
2;$e[]=$Nf[$d].($u["descs"][$x]?" DESC":"");}if(!$pc[$me]){if($u["type"]!="PRIMARY"||!$rg)$v[]=array($u["type"],$me,$e);}}foreach($v
as$x=>$X){if($X[0]=="PRIMARY"){unset($v[$x]);$kd[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}}foreach(foreign_keys($Q)as$me=>$p){foreach($p["source"]as$x=>$d){if(!$Nf[$d])continue
2;$p["source"][$x]=idf_unescape($Nf[$d]);}if(!isset($kd[" $me"]))$kd[]=" ".format_foreign_key($p);}queries("BEGIN");}foreach($n
as$x=>$m)$n[$x]="  ".implode($m);$n=array_merge($n,array_filter($kd));$di=($Q==$C?"adminer_$C":$C);if(!queries("CREATE TABLE ".table($di)." (\n".implode(",\n",$n)."\n)"))return
false;if($Q!=""){if($Nf&&!queries("INSERT INTO ".table($di)." (".implode(", ",$Nf).") SELECT ".implode(", ",array_map('idf_escape',array_keys($Nf)))." FROM ".table($Q)))return
false;$Di=array();foreach(triggers($Q)as$Bi=>$ki){$Ai=trigger($Bi);$Di[]="CREATE TRIGGER ".idf_escape($Bi)." ".implode(" ",$ki)." ON ".table($C)."\n$Ai[Statement]";}$La=$La?0:$f->result("SELECT seq FROM sqlite_sequence WHERE name = ".q($Q));if(!queries("DROP TABLE ".table($Q))||($Q==$C&&!queries("ALTER TABLE ".table($di)." RENAME TO ".table($C)))||!alter_indexes($C,$v))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($C));foreach($Di
as$Ai){if(!queries($Ai))return
false;}queries("COMMIT");}return
true;}function
index_sql($Q,$T,$C,$e){return"CREATE $T ".($T!="INDEX"?"INDEX ":"").idf_escape($C!=""?$C:uniqid($Q."_"))." ON ".table($Q)." $e";}function
alter_indexes($Q,$c){foreach($c
as$qg){if($qg[0]=="PRIMARY")return
recreate_table($Q,$Q,array(),array(),array(),0,$c);}foreach(array_reverse($c)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($Q,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables($S){return
apply_queries("DELETE FROM",$S);}function
drop_views($dj){return
apply_queries("DROP VIEW",$dj);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
move_tables($S,$dj,$bi){return
false;}function
trigger($C){global$f;if($C=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$t='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$Ci=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$t\\s*(".implode("|",$Ci["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($t))?\\s+ON\\s*$t\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",$f->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($C)),$B);$mf=$B[3];return
array("Timing"=>strtoupper($B[1]),"Event"=>strtoupper($B[2]).($mf?" OF":""),"Of"=>idf_unescape($mf),"Trigger"=>$C,"Statement"=>$B[4],);}function
triggers($Q){$I=array();$Ci=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$J){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$Ci["Timing"]).')\s*(.*?)\s+ON\b~i',$J["sql"],$B);$I[$J["name"]]=array($B[1],$B[2]);}return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
begin(){return
queries("BEGIN");}function
last_id(){global$f;return$f->result("SELECT LAST_INSERT_ROWID()");}function
explain($f,$G){return$f->query("EXPLAIN QUERY PLAN $G");}function
found_rows($R,$Z){}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($fh){return
true;}function
create_sql($Q,$La,$Mh){global$f;$I=$f->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$C=>$u){if($C=='')continue;$I.=";\n\n".index_sql($Q,$u['type'],$C,"(".implode(", ",array_map('idf_escape',$u['columns'])).")");}return$I;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($Sb){}function
trigger_sql($Q){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){global$f;$I=array();foreach(array("auto_vacuum","cache_size","count_changes","default_cache_size","empty_result_callbacks","encoding","foreign_keys","full_column_names","fullfsync","journal_mode","journal_size_limit","legacy_file_format","locking_mode","page_size","max_page_count","read_uncommitted","recursive_triggers","reverse_unordered_selects","secure_delete","short_column_names","synchronous","temp_store","temp_store_directory","schema_version","integrity_check","quick_check")as$x)$I[$x]=$f->result("PRAGMA $x");return$I;}function
show_status(){$I=array();foreach(get_vals("PRAGMA compile_options")as$Cf){list($x,$X)=explode("=",$Cf,2);$I[$x]=$X;}return$I;}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
support($Xc){return
preg_match('~^(columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~',$Xc);}function
driver_config(){$U=array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0);return
array('possible_drivers'=>array((isset($_GET["sqlite"])?"SQLite3":"SQLite"),"PDO_SQLite"),'jush'=>"sqlite",'types'=>$U,'structured_types'=>array_keys($U),'unsigned'=>array(),'operators'=>array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL"),'functions'=>array("distinct","hex","length","lower","round","unixepoch","upper"),'grouping'=>array("avg","count","count distinct","group_concat","max","min","sum"),'edit_functions'=>array(array(),array("integer|real|numeric"=>"+/-","text"=>"||",)),);}}$mc["pgsql"]="PostgreSQL";if(isset($_GET["pgsql"])){define("DRIVER","pgsql");if(extension_loaded("pgsql")){class
Min_DB{var$extension="PgSQL",$_link,$_result,$_string,$_database=true,$server_info,$affected_rows,$error,$timeout;function
_error($Ec,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
connect($M,$V,$F){global$b;$j=$b->database();set_error_handler(array($this,'_error'));$this->_string="host='".str_replace(":","' port='",addcslashes($M,"'\\"))."' user='".addcslashes($V,"'\\")."' password='".addcslashes($F,"'\\")."'";$this->_link=@pg_connect("$this->_string dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->_link&&$j!=""){$this->_database=false;$this->_link=@pg_connect("$this->_string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->_link){$bj=pg_version($this->_link);$this->server_info=$bj["server"];pg_set_client_encoding($this->_link,"UTF8");}return(bool)$this->_link;}function
quote($P){return"'".pg_escape_string($this->_link,$P)."'";}function
value($X,$m){return($m["type"]=="bytea"&&$X!==null?pg_unescape_bytea($X):$X);}function
quoteBinary($P){return"'".pg_escape_bytea($this->_link,$P)."'";}function
select_db($Sb){global$b;if($Sb==$b->database())return$this->_database;$I=@pg_connect("$this->_string dbname='".addcslashes($Sb,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($I)$this->_link=$I;return$I;}function
close(){$this->_link=@pg_connect("$this->_string dbname='postgres'");}function
query($G,$Gi=false){$H=@pg_query($this->_link,$G);$this->error="";if(!$H){$this->error=pg_last_error($this->_link);$I=false;}elseif(!pg_num_fields($H)){$this->affected_rows=pg_affected_rows($H);$I=true;}else$I=new
Min_Result($H);if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$m=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
pg_fetch_result($H->_result,0,$m);}function
warnings(){return
h(pg_last_notice($this->_link));}}class
Min_Result{var$_result,$_offset=0,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=pg_num_rows($H);}function
fetch_assoc(){return
pg_fetch_assoc($this->_result);}function
fetch_row(){return
pg_fetch_row($this->_result);}function
fetch_field(){$d=$this->_offset++;$I=new
stdClass;if(function_exists('pg_field_table'))$I->orgtable=pg_field_table($this->_result,$d);$I->name=pg_field_name($this->_result,$d);$I->orgname=$I->name;$I->type=pg_field_type($this->_result,$d);$I->charsetnr=($I->type=="bytea"?63:0);return$I;}function
__destruct(){pg_free_result($this->_result);}}}elseif(extension_loaded("pdo_pgsql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_PgSQL",$timeout;function
connect($M,$V,$F){global$b;$j=$b->database();$this->dsn("pgsql:host='".str_replace(":","' port='",addcslashes($M,"'\\"))."' client_encoding=utf8 dbname='".($j!=""?addcslashes($j,"'\\"):"postgres")."'",$V,$F);return
true;}function
select_db($Sb){global$b;return($b->database()==$Sb);}function
quoteBinary($ch){return
q($ch);}function
query($G,$Gi=false){$I=parent::query($G,$Gi);if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$I;}function
warnings(){return'';}function
close(){}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$qg){global$f;foreach($K
as$N){$Ni=array();$Z=array();foreach($N
as$x=>$X){$Ni[]="$x = $X";if(isset($qg[idf_unescape($x)]))$Z[]="$x = $X";}if(!(($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Ni)." WHERE ".implode(" AND ",$Z))&&$f->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($N)).") VALUES (".implode(", ",$N).")")))return
false;}return
true;}function
slowQuery($G,$ji){$this->_conn->query("SET statement_timeout = ".(1000*$ji));$this->_conn->timeout=1000*$ji;return$G;}function
convertSearch($t,$X,$m){return(preg_match('~char|text'.(!preg_match('~LIKE~',$X["op"])?'|date|time(stamp)?|boolean|uuid|'.number_type():'').'~',$m["type"])?$t:"CAST($t AS text)");}function
quoteBinary($ch){return$this->_conn->quoteBinary($ch);}function
warnings(){return$this->_conn->warnings();}function
tableHelp($C){$_=array("information_schema"=>"infoschema","pg_catalog"=>"catalog",);$z=$_[$_GET["ns"]];if($z)return"$z-".str_replace("_","-",$C).".html";}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){global$b,$U,$Lh;$f=new
Min_DB;$Lb=$b->credentials();if($f->connect($Lb[0],$Lb[1],$Lb[2])){if(min_version(9,0,$f)){$f->query("SET application_name = 'Adminer'");if(min_version(9.2,0,$f)){$Lh['Strings'][]="json";$U["json"]=4294967295;if(min_version(9.4,0,$f)){$Lh['Strings'][]="jsonb";$U["jsonb"]=4294967295;}}}return$f;}return$f->error;}function
get_databases(){return
get_vals("SELECT datname FROM pg_database WHERE has_database_privilege(datname, 'CONNECT') ORDER BY datname");}function
limit($G,$Z,$y,$nf=0,$mh=" "){return" $G$Z".($y!==null?$mh."LIMIT $y".($nf?" OFFSET $nf":""):"");}function
limit1($Q,$G,$Z,$mh="\n"){return(preg_match('~^INTO~',$G)?limit($G,$Z,1,0,$mh):" $G".(is_view(table_status1($Q))?$Z:$mh."WHERE ctid = (SELECT ctid FROM ".table($Q).$Z.$mh."LIMIT 1)"));}function
db_collation($j,$nb){global$f;return$f->result("SELECT datcollate FROM pg_database WHERE datname = ".q($j));}function
engines(){return
array();}function
logged_user(){global$f;return$f->result("SELECT user");}function
tables_list(){$G="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support('materializedview'))$G.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$G.="
ORDER BY 1";return
get_key_vals($G);}function
count_tables($i){return
array();}function
table_status($C=""){$I=array();foreach(get_rows("SELECT c.relname AS \"Name\", CASE c.relkind WHEN 'r' THEN 'table' WHEN 'm' THEN 'materialized view' ELSE 'view' END AS \"Engine\", pg_relation_size(c.oid) AS \"Data_length\", pg_total_relation_size(c.oid) - pg_relation_size(c.oid) AS \"Index_length\", obj_description(c.oid, 'pg_class') AS \"Comment\", ".(min_version(12)?"''":"CASE WHEN c.relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\", c.reltuples as \"Rows\", n.nspname
FROM pg_class c
JOIN pg_namespace n ON(n.nspname = current_schema() AND n.oid = c.relnamespace)
WHERE relkind IN ('r', 'm', 'v', 'f', 'p')
".($C!=""?"AND relname = ".q($C):"ORDER BY relname"))as$J)$I[$J["Name"]]=$J;return($C!=""?$I[$C]:$I);}function
is_view($R){return
in_array($R["Engine"],array("view","materialized view"));}function
fk_support($R){return
true;}function
fields($Q){$I=array();$Ca=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz',);foreach(get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, pg_get_expr(d.adbin, d.adrelid) AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment".(min_version(10)?", a.attidentity":"")."
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = ".q($Q)."
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$J){preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$J["full_type"],$B);list(,$T,$ze,$J["length"],$xa,$Fa)=$B;$J["length"].=$Fa;$cb=$T.$xa;if(isset($Ca[$cb])){$J["type"]=$Ca[$cb];$J["full_type"]=$J["type"].$ze.$Fa;}else{$J["type"]=$T;$J["full_type"]=$J["type"].$ze.$xa.$Fa;}if(in_array($J['attidentity'],array('a','d')))$J['default']='GENERATED '.($J['attidentity']=='d'?'BY DEFAULT':'ALWAYS').' AS IDENTITY';$J["null"]=!$J["attnotnull"];$J["auto_increment"]=$J['attidentity']||preg_match('~^nextval\(~i',$J["default"]);$J["privileges"]=array("insert"=>1,"select"=>1,"update"=>1);if(preg_match('~(.+)::[^,)]+(.*)~',$J["default"],$B))$J["default"]=($B[1]=="NULL"?null:idf_unescape($B[1]).$B[2]);$I[$J["field"]]=$J;}return$I;}function
indexes($Q,$g=null){global$f;if(!is_object($g))$g=$f;$I=array();$Uh=$g->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($Q));$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Uh AND attnum > 0",$g);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption, (indpred IS NOT NULL)::int as indispartial FROM pg_index i, pg_class ci WHERE i.indrelid = $Uh AND ci.oid = i.indexrelid",$g)as$J){$Mg=$J["relname"];$I[$Mg]["type"]=($J["indispartial"]?"INDEX":($J["indisprimary"]?"PRIMARY":($J["indisunique"]?"UNIQUE":"INDEX")));$I[$Mg]["columns"]=array();foreach(explode(" ",$J["indkey"])as$Vd)$I[$Mg]["columns"][]=$e[$Vd];$I[$Mg]["descs"]=array();foreach(explode(" ",$J["indoption"])as$Wd)$I[$Mg]["descs"][]=($Wd&1?'1':null);$I[$Mg]["lengths"]=array();}return$I;}function
foreign_keys($Q){global$vf;$I=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = ".q($Q)." AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname")as$J){if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$J['definition'],$B)){$J['source']=array_map('idf_unescape',array_map('trim',explode(',',$B[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$B[2],$He)){$J['ns']=idf_unescape($He[2]);$J['table']=idf_unescape($He[4]);}$J['target']=array_map('idf_unescape',array_map('trim',explode(',',$B[3])));$J['on_delete']=(preg_match("~ON DELETE ($vf)~",$B[4],$He)?$He[1]:'NO ACTION');$J['on_update']=(preg_match("~ON UPDATE ($vf)~",$B[4],$He)?$He[1]:'NO ACTION');$I[$J['conname']]=$J;}}return$I;}function
constraints($Q){global$vf;$I=array();foreach(get_rows("SELECT conname, consrc
FROM pg_catalog.pg_constraint
INNER JOIN pg_catalog.pg_namespace ON pg_constraint.connamespace = pg_namespace.oid
INNER JOIN pg_catalog.pg_class ON pg_constraint.conrelid = pg_class.oid AND pg_constraint.connamespace = pg_class.relnamespace
WHERE pg_constraint.contype = 'c'
AND conrelid != 0 -- handle only CONSTRAINTs here, not TYPES
AND nspname = current_schema()
AND relname = ".q($Q)."
ORDER BY connamespace, conname")as$J)$I[$J['conname']]=$J['consrc'];return$I;}function
view($C){global$f;return
array("select"=>trim($f->result("SELECT pg_get_viewdef(".$f->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = ".q($C)).")")));}function
collations(){return
array();}function
information_schema($j){return($j=="information_schema");}function
error(){global$f;$I=h($f->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$I,$B))$I=$B[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($B[3]).'})(.*)~','\1<b>\2</b>',$B[2]).$B[4];return
nl_br($I);}function
create_database($j,$mb){return
queries("CREATE DATABASE ".idf_escape($j).($mb?" ENCODING ".idf_escape($mb):""));}function
drop_databases($i){global$f;$f->close();return
apply_queries("DROP DATABASE",$i,'idf_escape');}function
rename_database($C,$mb){return
queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($C));}function
auto_increment(){return"";}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){$c=array();$_g=array();if($Q!=""&&$Q!=$C)$_g[]="ALTER TABLE ".table($Q)." RENAME TO ".table($C);foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$c[]="DROP $d";else{$Xi=$X[5];unset($X[5]);if($m[0]==""){if(isset($X[6]))$X[1]=($X[1]==" bigint"?" big":($X[1]==" smallint"?" small":" "))."serial";$c[]=($Q!=""?"ADD ":"  ").implode($X);if(isset($X[6]))$c[]=($Q!=""?"ADD":" ")." PRIMARY KEY ($X[0])";}else{if($d!=$X[0])$_g[]="ALTER TABLE ".table($C)." RENAME $d TO $X[0]";$c[]="ALTER $d TYPE$X[1]";if(!$X[6]){$c[]="ALTER $d ".($X[3]?"SET$X[3]":"DROP DEFAULT");$c[]="ALTER $d ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}}if($m[0]!=""||$Xi!="")$_g[]="COMMENT ON COLUMN ".table($C).".$X[0] IS ".($Xi!=""?substr($Xi,9):"''");}}$c=array_merge($c,$kd);if($Q=="")array_unshift($_g,"CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");elseif($c)array_unshift($_g,"ALTER TABLE ".table($Q)."\n".implode(",\n",$c));if($tb!==null)$_g[]="COMMENT ON TABLE ".table($C)." IS ".q($tb);if($La!=""){}foreach($_g
as$G){if(!queries($G))return
false;}return
true;}function
alter_indexes($Q,$c){$h=array();$nc=array();$_g=array();foreach($c
as$X){if($X[0]!="INDEX")$h[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$nc[]=idf_escape($X[1]);else$_g[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$X[2]).")";}if($h)array_unshift($_g,"ALTER TABLE ".table($Q).implode(",",$h));if($nc)array_unshift($_g,"DROP INDEX ".implode(", ",$nc));foreach($_g
as$G){if(!queries($G))return
false;}return
true;}function
truncate_tables($S){return
queries("TRUNCATE ".implode(", ",array_map('table',$S)));return
true;}function
drop_views($dj){return
drop_tables($dj);}function
drop_tables($S){foreach($S
as$Q){$O=table_status($Q);if(!queries("DROP ".strtoupper($O["Engine"])." ".table($Q)))return
false;}return
true;}function
move_tables($S,$dj,$bi){foreach(array_merge($S,$dj)as$Q){$O=table_status($Q);if(!queries("ALTER ".strtoupper($O["Engine"])." ".table($Q)." SET SCHEMA ".idf_escape($bi)))return
false;}return
true;}function
trigger($C,$Q){if($C=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$e=array();$Z="WHERE trigger_schema = current_schema() AND event_object_table = ".q($Q)." AND trigger_name = ".q($C);foreach(get_rows("SELECT * FROM information_schema.triggered_update_columns $Z")as$J)$e[]=$J["event_object_column"];$I=array();foreach(get_rows('SELECT trigger_name AS "Trigger", action_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement" FROM information_schema.triggers '."$Z ORDER BY event_manipulation DESC")as$J){if($e&&$J["Event"]=="UPDATE")$J["Event"].=" OF";$J["Of"]=implode(", ",$e);if($I)$J["Event"].=" OR $I[Event]";$I=$J;}return$I;}function
triggers($Q){$I=array();foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($Q))as$J){$Ai=trigger($J["trigger_name"],$Q);$I[$Ai["Trigger"]]=array($Ai["Timing"],$Ai["Event"]);}return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF"),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($C,$T){$K=get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = '.q($C));$I=$K[0];$I["returns"]=array("type"=>$I["type_udt_name"]);$I["fields"]=get_rows('SELECT parameter_name AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = '.q($C).'
ORDER BY ordinal_position');return$I;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');}function
routine_languages(){return
get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");}function
routine_id($C,$J){$I=array();foreach($J["fields"]as$m)$I[]=$m["type"];return
idf_escape($C)."(".implode(", ",$I).")";}function
last_id(){return
0;}function
explain($f,$G){return$f->query("EXPLAIN $G");}function
found_rows($R,$Z){global$f;if(preg_match("~ rows=([0-9]+)~",$f->result("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$Lg))return$Lg[1];return
false;}function
types(){return
get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0");}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){global$f;return$f->result("SELECT current_schema()");}function
set_schema($eh,$g=null){global$f,$U,$Lh;if(!$g)$g=$f;$I=$g->query("SET search_path TO ".idf_escape($eh));foreach(types()as$T){if(!isset($U[$T])){$U[$T]=0;$Lh['User types'][]=$T;}}return$I;}function
foreign_keys_sql($Q){$I="";$O=table_status($Q);$gd=foreign_keys($Q);ksort($gd);foreach($gd
as$fd=>$ed)$I.="ALTER TABLE ONLY ".idf_escape($O['nspname']).".".idf_escape($O['Name'])." ADD CONSTRAINT ".idf_escape($fd)." $ed[definition] ".($ed['deferrable']?'DEFERRABLE':'NOT DEFERRABLE').";\n";return($I?"$I\n":$I);}function
create_sql($Q,$La,$Mh){global$f;$I='';$Ug=array();$oh=array();$O=table_status($Q);if(is_view($O)){$cj=view($Q);return
rtrim("CREATE VIEW ".idf_escape($Q)." AS $cj[select]",";");}$n=fields($Q);$v=indexes($Q);ksort($v);$Bb=constraints($Q);if(!$O||empty($n))return
false;$I="CREATE TABLE ".idf_escape($O['nspname']).".".idf_escape($O['Name'])." (\n    ";foreach($n
as$Zc=>$m){$Wf=idf_escape($m['field']).' '.$m['full_type'].default_value($m).($m['attnotnull']?" NOT NULL":"");$Ug[]=$Wf;if(preg_match('~nextval\(\'([^\']+)\'\)~',$m['default'],$Ie)){$nh=$Ie[1];$Bh=reset(get_rows(min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q($nh):"SELECT * FROM $nh"));$oh[]=($Mh=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $nh;\n":"")."CREATE SEQUENCE $nh INCREMENT $Bh[increment_by] MINVALUE $Bh[min_value] MAXVALUE $Bh[max_value]".($La&&$Bh['last_value']?" START $Bh[last_value]":"")." CACHE $Bh[cache_value];";}}if(!empty($oh))$I=implode("\n\n",$oh)."\n\n$I";foreach($v
as$Qd=>$u){switch($u['type']){case'UNIQUE':$Ug[]="CONSTRAINT ".idf_escape($Qd)." UNIQUE (".implode(', ',array_map('idf_escape',$u['columns'])).")";break;case'PRIMARY':$Ug[]="CONSTRAINT ".idf_escape($Qd)." PRIMARY KEY (".implode(', ',array_map('idf_escape',$u['columns'])).")";break;}}foreach($Bb
as$zb=>$Ab)$Ug[]="CONSTRAINT ".idf_escape($zb)." CHECK $Ab";$I.=implode(",\n    ",$Ug)."\n) WITH (oids = ".($O['Oid']?'true':'false').");";foreach($v
as$Qd=>$u){if($u['type']=='INDEX'){$e=array();foreach($u['columns']as$x=>$X)$e[]=idf_escape($X).($u['descs'][$x]?" DESC":"");$I.="\n\nCREATE INDEX ".idf_escape($Qd)." ON ".idf_escape($O['nspname']).".".idf_escape($O['Name'])." USING btree (".implode(', ',$e).");";}}if($O['Comment'])$I.="\n\nCOMMENT ON TABLE ".idf_escape($O['nspname']).".".idf_escape($O['Name'])." IS ".q($O['Comment']).";";foreach($n
as$Zc=>$m){if($m['comment'])$I.="\n\nCOMMENT ON COLUMN ".idf_escape($O['nspname']).".".idf_escape($O['Name']).".".idf_escape($Zc)." IS ".q($m['comment']).";";}return
rtrim($I,';');}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
trigger_sql($Q){$O=table_status($Q);$I="";foreach(triggers($Q)as$_i=>$zi){$Ai=trigger($_i,$O['Name']);$I.="\nCREATE TRIGGER ".idf_escape($Ai['Trigger'])." $Ai[Timing] $Ai[Event] ON ".idf_escape($O["nspname"]).".".idf_escape($O['Name'])." $Ai[Type] $Ai[Statement];;\n";}return$I;}function
use_sql($Sb){return"\connect ".idf_escape($Sb);}function
show_variables(){return
get_key_vals("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
show_status(){}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
support($Xc){return
preg_match('~^(database|table|columns|sql|indexes|descidx|comment|view|'.(min_version(9.3)?'materializedview|':'').'scheme|routine|processlist|sequence|trigger|type|variables|drop_col|kill|dump)$~',$Xc);}function
kill_process($X){return
queries("SELECT pg_terminate_backend(".number($X).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){global$f;return$f->result("SHOW max_connections");}function
driver_config(){$U=array();$Lh=array();foreach(array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>13,"time"=>17,"timestamp"=>20,"timestamptz"=>21,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),)as$x=>$X){$U+=$X;$Lh[$x]=array_keys($X);}return
array('possible_drivers'=>array("PgSQL","PDO_PgSQL"),'jush'=>"pgsql",'types'=>$U,'structured_types'=>$Lh,'unsigned'=>array(),'operators'=>array("=","<",">","<=",">=","!=","~","~*","!~","!~*","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"),'operator_regexp'=>'~*','functions'=>array("char_length","distinct","lower","round","to_hex","to_timestamp","upper"),'grouping'=>array("avg","count","count distinct","max","min","sum"),'edit_functions'=>array(array("char"=>"md5","date|time"=>"now",),array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",)),);}}$mc["oracle"]="Oracle (beta)";if(isset($_GET["oracle"])){define("DRIVER","oracle");if(extension_loaded("oci8")){class
Min_DB{var$extension="oci8",$_link,$_result,$server_info,$affected_rows,$errno,$error;var$_current_db;function
_error($Ec,$l){if(ini_bool("html_errors"))$l=html_entity_decode(strip_tags($l));$l=preg_replace('~^[^:]*: ~','',$l);$this->error=$l;}function
connect($M,$V,$F){$this->_link=@oci_new_connect($V,$F,$M,"AL32UTF8");if($this->_link){$this->server_info=oci_server_version($this->_link);return
true;}$l=oci_error();$this->error=$l["message"];return
false;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Sb){$this->_current_db=$Sb;return
true;}function
query($G,$Gi=false){$H=oci_parse($this->_link,$G);$this->error="";if(!$H){$l=oci_error($this->_link);$this->errno=$l["code"];$this->error=$l["message"];return
false;}set_error_handler(array($this,'_error'));$I=@oci_execute($H);restore_error_handler();if($I){if(oci_num_fields($H))return
new
Min_Result($H);$this->affected_rows=oci_num_rows($H);oci_free_statement($H);}return$I;}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$m=1){$H=$this->query($G);if(!is_object($H)||!oci_fetch($H->_result))return
false;return
oci_result($H->_result,$m);}}class
Min_Result{var$_result,$_offset=1,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$x=>$X){if(is_a($X,'OCI-Lob'))$J[$x]=$X->load();}return$J;}function
fetch_assoc(){return$this->_convert(oci_fetch_assoc($this->_result));}function
fetch_row(){return$this->_convert(oci_fetch_row($this->_result));}function
fetch_field(){$d=$this->_offset++;$I=new
stdClass;$I->name=oci_field_name($this->_result,$d);$I->orgname=$I->name;$I->type=oci_field_type($this->_result,$d);$I->charsetnr=(preg_match("~raw|blob|bfile~",$I->type)?63:0);return$I;}function
__destruct(){oci_free_statement($this->_result);}}}elseif(extension_loaded("pdo_oci")){class
Min_DB
extends
Min_PDO{var$extension="PDO_OCI";var$_current_db;function
connect($M,$V,$F){$this->dsn("oci:dbname=//$M;charset=AL32UTF8",$V,$F);return
true;}function
select_db($Sb){$this->_current_db=$Sb;return
true;}}}class
Min_Driver
extends
Min_SQL{function
begin(){return
true;}function
insertUpdate($Q,$K,$qg){global$f;foreach($K
as$N){$Ni=array();$Z=array();foreach($N
as$x=>$X){$Ni[]="$x = $X";if(isset($qg[idf_unescape($x)]))$Z[]="$x = $X";}if(!(($Z&&queries("UPDATE ".table($Q)." SET ".implode(", ",$Ni)." WHERE ".implode(" AND ",$Z))&&$f->affected_rows)||queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($N)).") VALUES (".implode(", ",$N).")")))return
false;}return
true;}}function
idf_escape($t){return'"'.str_replace('"','""',$t).'"';}function
table($t){return
idf_escape($t);}function
connect(){global$b;$f=new
Min_DB;$Lb=$b->credentials();if($f->connect($Lb[0],$Lb[1],$Lb[2]))return$f;return$f->error;}function
get_databases(){return
get_vals("SELECT tablespace_name FROM user_tablespaces ORDER BY 1");}function
limit($G,$Z,$y,$nf=0,$mh=" "){return($nf?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $G$Z) t WHERE rownum <= ".($y+$nf).") WHERE rnum > $nf":($y!==null?" * FROM (SELECT $G$Z) WHERE rownum <= ".($y+$nf):" $G$Z"));}function
limit1($Q,$G,$Z,$mh="\n"){return" $G$Z";}function
db_collation($j,$nb){global$f;return$f->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
engines(){return
array();}function
logged_user(){global$f;return$f->result("SELECT USER FROM DUAL");}function
get_current_db(){global$f;$j=$f->_current_db?$f->_current_db:DB;unset($f->_current_db);return$j;}function
where_owner($og,$Qf="owner"){if(!$_GET["ns"])return'';return"$og$Qf = sys_context('USERENV', 'CURRENT_SCHEMA')";}function
views_table($e){$Qf=where_owner('');return"(SELECT $e FROM all_views WHERE ".($Qf?$Qf:"rownum < 0").")";}function
tables_list(){$cj=views_table("view_name");$Qf=where_owner(" AND ");return
get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = ".q(DB)."$Qf
UNION SELECT view_name, 'view' FROM $cj
ORDER BY 1");}function
count_tables($i){global$f;$I=array();foreach($i
as$j)$I[$j]=$f->result("SELECT COUNT(*) FROM all_tables WHERE tablespace_name = ".q($j));return$I;}function
table_status($C=""){$I=array();$gh=q($C);$j=get_current_db();$cj=views_table("view_name");$Qf=where_owner(" AND ");foreach(get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = '.q($j).$Qf.($C!=""?" AND table_name = $gh":"")."
UNION SELECT view_name, 'view', 0, 0 FROM $cj".($C!=""?" WHERE view_name = $gh":"")."
ORDER BY 1")as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="view";}function
fk_support($R){return
true;}function
fields($Q){$I=array();$Qf=where_owner(" AND ");foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)."$Qf ORDER BY column_id")as$J){$T=$J["DATA_TYPE"];$ze="$J[DATA_PRECISION],$J[DATA_SCALE]";if($ze==",")$ze=$J["CHAR_COL_DECL_LENGTH"];$I[$J["COLUMN_NAME"]]=array("field"=>$J["COLUMN_NAME"],"full_type"=>$T.($ze?"($ze)":""),"type"=>strtolower($T),"length"=>$ze,"default"=>$J["DATA_DEFAULT"],"null"=>($J["NULLABLE"]=="Y"),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);}return$I;}function
indexes($Q,$g=null){$I=array();$Qf=where_owner(" AND ","aic.table_owner");foreach(get_rows("SELECT aic.*, ac.constraint_type, atc.data_default
FROM all_ind_columns aic
LEFT JOIN all_constraints ac ON aic.index_name = ac.constraint_name AND aic.table_name = ac.table_name AND aic.index_owner = ac.owner
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($Q)."$Qf
ORDER BY ac.constraint_type, aic.column_position",$g)as$J){$Qd=$J["INDEX_NAME"];$qb=$J["DATA_DEFAULT"];$qb=($qb?trim($qb,'"'):$J["COLUMN_NAME"]);$I[$Qd]["type"]=($J["CONSTRAINT_TYPE"]=="P"?"PRIMARY":($J["CONSTRAINT_TYPE"]=="U"?"UNIQUE":"INDEX"));$I[$Qd]["columns"][]=$qb;$I[$Qd]["lengths"][]=($J["CHAR_LENGTH"]&&$J["CHAR_LENGTH"]!=$J["COLUMN_LENGTH"]?$J["CHAR_LENGTH"]:null);$I[$Qd]["descs"][]=($J["DESCEND"]&&$J["DESCEND"]=="DESC"?'1':null);}return$I;}function
view($C){$cj=views_table("view_name, text");$K=get_rows('SELECT text "select" FROM '.$cj.' WHERE view_name = '.q($C));return
reset($K);}function
collations(){return
array();}function
information_schema($j){return
false;}function
error(){global$f;return
h($f->error);}function
explain($f,$G){$f->query("EXPLAIN PLAN FOR $G");return$f->query("SELECT * FROM plan_table");}function
found_rows($R,$Z){}function
auto_increment(){return"";}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){$c=$nc=array();$Kf=($Q?fields($Q):array());foreach($n
as$m){$X=$m[1];if($X&&$m[0]!=""&&idf_escape($m[0])!=$X[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($m[0])." TO $X[0]");$Jf=$Kf[$m[0]];if($X&&$Jf){$pf=process_field($Jf,$Jf);if($X[2]==$pf[2])$X[2]="";}if($X)$c[]=($Q!=""?($m[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($Q!=""?")":"");else$nc[]=idf_escape($m[0]);}if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)");return(!$c||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$c)))&&(!$nc||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$nc).")"))&&($Q==$C||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($C)));}function
alter_indexes($Q,$c){$nc=array();$_g=array();foreach($c
as$X){if($X[0]!="INDEX"){$X[2]=preg_replace('~ DESC$~','',$X[2]);$h=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");array_unshift($_g,"ALTER TABLE ".table($Q).$h);}elseif($X[2]=="DROP")$nc[]=idf_escape($X[1]);else$_g[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$X[2]).")";}if($nc)array_unshift($_g,"DROP INDEX ".implode(", ",$nc));foreach($_g
as$G){if(!queries($G))return
false;}return
true;}function
foreign_keys($Q){$I=array();$G="SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = ".q($Q);foreach(get_rows($G)as$J)$I[$J['NAME']]=array("db"=>$J['DEST_DB'],"table"=>$J['DEST_TABLE'],"source"=>array($J['SRC_COLUMN']),"target"=>array($J['DEST_COLUMN']),"on_delete"=>$J['ON_DELETE'],"on_update"=>null,);return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($dj){return
apply_queries("DROP VIEW",$dj);}function
drop_tables($S){return
apply_queries("DROP TABLE",$S);}function
last_id(){return
0;}function
schemas(){$I=get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX')) ORDER BY 1");return($I?$I:get_vals("SELECT DISTINCT owner FROM all_tables WHERE tablespace_name = ".q(DB)." ORDER BY 1"));}function
get_schema(){global$f;return$f->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");}function
set_schema($fh,$g=null){global$f;if(!$g)$g=$f;return$g->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($fh));}function
show_variables(){return
get_key_vals('SELECT name, display_value FROM v$parameter');}function
process_list(){return
get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
show_status(){$K=get_rows('SELECT * FROM v$instance');return
reset($K);}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
support($Xc){return
preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view)$~',$Xc);}function
driver_config(){$U=array();$Lh=array();foreach(array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>10,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),)as$x=>$X){$U+=$X;$Lh[$x]=array_keys($X);}return
array('possible_drivers'=>array("OCI8","PDO_OCI"),'jush'=>"oracle",'types'=>$U,'structured_types'=>$Lh,'unsigned'=>array(),'operators'=>array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL"),'functions'=>array("distinct","length","lower","round","upper"),'grouping'=>array("avg","count","count distinct","max","min","sum"),'edit_functions'=>array(array("date"=>"current_date","timestamp"=>"current_timestamp",),array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",)),);}}$mc["mssql"]="MS SQL (beta)";if(isset($_GET["mssql"])){define("DRIVER","mssql");if(extension_loaded("sqlsrv")){class
Min_DB{var$extension="sqlsrv",$_link,$_result,$server_info,$affected_rows,$errno,$error;function
_get_error(){$this->error="";foreach(sqlsrv_errors()as$l){$this->errno=$l["code"];$this->error.="$l[message]\n";}$this->error=rtrim($this->error);}function
connect($M,$V,$F){global$b;$j=$b->database();$_b=array("UID"=>$V,"PWD"=>$F,"CharacterSet"=>"UTF-8");if($j!="")$_b["Database"]=$j;$this->_link=@sqlsrv_connect(preg_replace('~:~',',',$M),$_b);if($this->_link){$Xd=sqlsrv_server_info($this->_link);$this->server_info=$Xd['SQLServerVersion'];}else$this->_get_error();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Sb){return$this->query("USE ".idf_escape($Sb));}function
query($G,$Gi=false){$H=sqlsrv_query($this->_link,$G);$this->error="";if(!$H){$this->_get_error();return
false;}return$this->store_result($H);}function
multi_query($G){$this->_result=sqlsrv_query($this->_link,$G);$this->error="";if(!$this->_result){$this->_get_error();return
false;}return
true;}function
store_result($H=null){if(!$H)$H=$this->_result;if(!$H)return
false;if(sqlsrv_field_metadata($H))return
new
Min_Result($H);$this->affected_rows=sqlsrv_rows_affected($H);return
true;}function
next_result(){return$this->_result?sqlsrv_next_result($this->_result):null;}function
result($G,$m=0){$H=$this->query($G);if(!is_object($H))return
false;$J=$H->fetch_row();return$J[$m];}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;}function
_convert($J){foreach((array)$J
as$x=>$X){if(is_a($X,'DateTime'))$J[$x]=$X->format("Y-m-d H:i:s");}return$J;}function
fetch_assoc(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_ASSOC));}function
fetch_row(){return$this->_convert(sqlsrv_fetch_array($this->_result,SQLSRV_FETCH_NUMERIC));}function
fetch_field(){if(!$this->_fields)$this->_fields=sqlsrv_field_metadata($this->_result);$m=$this->_fields[$this->_offset++];$I=new
stdClass;$I->name=$m["Name"];$I->orgname=$m["Name"];$I->type=($m["Type"]==1?254:0);return$I;}function
seek($nf){for($r=0;$r<$nf;$r++)sqlsrv_fetch($this->_result);}function
__destruct(){sqlsrv_free_stmt($this->_result);}}}elseif(extension_loaded("mssql")){class
Min_DB{var$extension="MSSQL",$_link,$_result,$server_info,$affected_rows,$error;function
connect($M,$V,$F){$this->_link=@mssql_connect($M,$V,$F);if($this->_link){$H=$this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");if($H){$J=$H->fetch_row();$this->server_info=$this->result("sp_server_info 2",2)." [$J[0]] $J[1]";}}else$this->error=mssql_get_last_message();return(bool)$this->_link;}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($Sb){return
mssql_select_db($Sb);}function
query($G,$Gi=false){$H=@mssql_query($G,$this->_link);$this->error="";if(!$H){$this->error=mssql_get_last_message();return
false;}if($H===true){$this->affected_rows=mssql_rows_affected($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
mssql_next_result($this->_result->_result);}function
result($G,$m=0){$H=$this->query($G);if(!is_object($H))return
false;return
mssql_result($H->_result,0,$m);}}class
Min_Result{var$_result,$_offset=0,$_fields,$num_rows;function
__construct($H){$this->_result=$H;$this->num_rows=mssql_num_rows($H);}function
fetch_assoc(){return
mssql_fetch_assoc($this->_result);}function
fetch_row(){return
mssql_fetch_row($this->_result);}function
num_rows(){return
mssql_num_rows($this->_result);}function
fetch_field(){$I=mssql_fetch_field($this->_result);$I->orgtable=$I->table;$I->orgname=$I->name;return$I;}function
seek($nf){mssql_data_seek($this->_result,$nf);}function
__destruct(){mssql_free_result($this->_result);}}}elseif(extension_loaded("pdo_dblib")){class
Min_DB
extends
Min_PDO{var$extension="PDO_DBLIB";function
connect($M,$V,$F){$this->dsn("dblib:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$M)),$V,$F);return
true;}function
select_db($Sb){return$this->query("USE ".idf_escape($Sb));}}}class
Min_Driver
extends
Min_SQL{function
insertUpdate($Q,$K,$qg){foreach($K
as$N){$Ni=array();$Z=array();foreach($N
as$x=>$X){$Ni[]="$x = $X";if(isset($qg[idf_unescape($x)]))$Z[]="$x = $X";}if(!queries("MERGE ".table($Q)." USING (VALUES(".implode(", ",$N).")) AS source (c".implode(", c",range(1,count($N))).") ON ".implode(" AND ",$Z)." WHEN MATCHED THEN UPDATE SET ".implode(", ",$Ni)." WHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($N)).") VALUES (".implode(", ",$N).");"))return
false;}return
true;}function
begin(){return
queries("BEGIN TRANSACTION");}}function
idf_escape($t){return"[".str_replace("]","]]",$t)."]";}function
table($t){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($t);}function
connect(){global$b;$f=new
Min_DB;$Lb=$b->credentials();if($f->connect($Lb[0],$Lb[1],$Lb[2]))return$f;return$f->error;}function
get_databases(){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($G,$Z,$y,$nf=0,$mh=" "){return($y!==null?" TOP (".($y+$nf).")":"")." $G$Z";}function
limit1($Q,$G,$Z,$mh="\n"){return
limit($G,$Z,1,0,$mh);}function
db_collation($j,$nb){global$f;return$f->result("SELECT collation_name FROM sys.databases WHERE name = ".q($j));}function
engines(){return
array();}function
logged_user(){global$f;return$f->result("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables($i){global$f;$I=array();foreach($i
as$j){$f->select_db($j);$I[$j]=$f->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$I;}function
table_status($C=""){$I=array();foreach(get_rows("SELECT ao.name AS Name, ao.type_desc AS Engine, (SELECT value FROM fn_listextendedproperty(default, 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment FROM sys.all_objects AS ao WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($C!=""?"AND name = ".q($C):"ORDER BY name"))as$J){if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]=="VIEW";}function
fk_support($R){return
true;}function
fields($Q){$vb=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($Q).", 'column', NULL)");$I=array();foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, CAST(d.definition as text) [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('S', 'U', 'V') AND o.name = ".q($Q))as$J){$T=$J["type"];$ze=(preg_match("~char|binary~",$T)?$J["max_length"]:($T=="decimal"?"$J[precision],$J[scale]":""));$I[$J["name"]]=array("field"=>$J["name"],"full_type"=>$T.($ze?"($ze)":""),"type"=>$T,"length"=>$ze,"default"=>$J["default"],"null"=>$J["is_nullable"],"auto_increment"=>$J["is_identity"],"collation"=>$J["collation_name"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),"primary"=>$J["is_identity"],"comment"=>$vb[$J["name"]],);}return$I;}function
indexes($Q,$g=null){$I=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$g)as$J){$C=$J["name"];$I[$C]["type"]=($J["is_primary_key"]?"PRIMARY":($J["is_unique"]?"UNIQUE":"INDEX"));$I[$C]["lengths"]=array();$I[$C]["columns"][$J["key_ordinal"]]=$J["column_name"];$I[$C]["descs"][$J["key_ordinal"]]=($J["is_descending_key"]?'1':null);}return$I;}function
view($C){global$f;return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',$f->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($C))));}function
collations(){$I=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$mb)$I[preg_replace('~_.*~','',$mb)][]=$mb;return$I;}function
information_schema($j){return
false;}function
error(){global$f;return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',$f->error)));}function
create_database($j,$mb){return
queries("CREATE DATABASE ".idf_escape($j).(preg_match('~^[a-z0-9_]+$~i',$mb)?" COLLATE $mb":""));}function
drop_databases($i){return
queries("DROP DATABASE ".implode(", ",array_map('idf_escape',$i)));}function
rename_database($C,$mb){if(preg_match('~^[a-z0-9_]+$~i',$mb))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $mb");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($C));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){$c=array();$vb=array();foreach($n
as$m){$d=idf_escape($m[0]);$X=$m[1];if(!$X)$c["DROP"][]=" COLUMN $d";else{$X[1]=preg_replace("~( COLLATE )'(\\w+)'~",'\1\2',$X[1]);$vb[$m[0]]=$X[5];unset($X[5]);if($m[0]=="")$c["ADD"][]="\n  ".implode("",$X).($Q==""?substr($kd[$X[0]],16+strlen($X[0])):"");else{unset($X[6]);if($d!=$X[0])queries("EXEC sp_rename ".q(table($Q).".$d").", ".q(idf_unescape($X[0])).", 'COLUMN'");$c["ALTER COLUMN ".implode("",$X)][]="";}}}if($Q=="")return
queries("CREATE TABLE ".table($C)." (".implode(",",(array)$c["ADD"])."\n)");if($Q!=$C)queries("EXEC sp_rename ".q(table($Q)).", ".q($C));if($kd)$c[""]=$kd;foreach($c
as$x=>$X){if(!queries("ALTER TABLE ".idf_escape($C)." $x".implode(",",$X)))return
false;}foreach($vb
as$x=>$X){$tb=substr($X,9);queries("EXEC sp_dropextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($C).", @level2type = N'Column', @level2name = ".q($x));queries("EXEC sp_addextendedproperty @name = N'MS_Description', @value = ".$tb.", @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($C).", @level2type = N'Column', @level2name = ".q($x));}return
true;}function
alter_indexes($Q,$c){$u=array();$nc=array();foreach($c
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY")$nc[]=idf_escape($X[1]);else$u[]=idf_escape($X[1])." ON ".table($Q);}elseif(!queries(($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")"))return
false;}return(!$u||queries("DROP INDEX ".implode(", ",$u)))&&(!$nc||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$nc)));}function
last_id(){global$f;return$f->result("SELECT SCOPE_IDENTITY()");}function
explain($f,$G){$f->query("SET SHOWPLAN_ALL ON");$I=$f->query($G);$f->query("SET SHOWPLAN_ALL OFF");return$I;}function
found_rows($R,$Z){}function
foreign_keys($Q){$I=array();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q))as$J){$p=&$I[$J["FK_NAME"]];$p["db"]=$J["PKTABLE_QUALIFIER"];$p["table"]=$J["PKTABLE_NAME"];$p["source"][]=$J["FKCOLUMN_NAME"];$p["target"][]=$J["PKCOLUMN_NAME"];}return$I;}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($dj){return
queries("DROP VIEW ".implode(", ",array_map('table',$dj)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$dj,$bi){return
apply_queries("ALTER SCHEMA ".idf_escape($bi)." TRANSFER",array_merge($S,$dj));}function
trigger($C){if($C=="")return
array();$K=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($C));$I=reset($K);if($I)$I["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$I["text"]);return$I;}function
triggers($Q){$I=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$J)$I[$J["name"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){global$f;if($_GET["ns"]!="")return$_GET["ns"];return$f->result("SELECT SCHEMA_NAME()");}function
set_schema($eh){return
true;}function
use_sql($Sb){return"USE ".idf_escape($Sb);}function
show_variables(){return
array();}function
show_status(){return
array();}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
support($Xc){return
preg_match('~^(comment|columns|database|drop_col|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~',$Xc);}function
driver_config(){$U=array();$Lh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>19,"datetime2"=>19,"time"=>8,"datetimeoffset"=>10),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),)as$x=>$X){$U+=$X;$Lh[$x]=array_keys($X);}return
array('possible_drivers'=>array("SQLSRV","MSSQL","PDO_DBLIB"),'jush'=>"mssql",'types'=>$U,'structured_types'=>$Lh,'unsigned'=>array(),'operators'=>array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"),'functions'=>array("distinct","len","lower","round","upper"),'grouping'=>array("avg","count","count distinct","max","min","sum"),'edit_functions'=>array(array("date|time"=>"getdate",),array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",)),);}}$mc["mongo"]="MongoDB (alpha)";if(isset($_GET["mongo"])){define("DRIVER","mongo");if(class_exists('MongoDB')){class
Min_DB{var$extension="Mongo",$server_info=MongoClient::VERSION,$error,$last_id,$_link,$_db;function
connect($Oi,$D){try{$this->_link=new
MongoClient($Oi,$D);if($D["password"]!=""){$D["password"]="";try{new
MongoClient($Oi,$D);$this->error='Database does not support password.';}catch(Exception$tc){}}}catch(Exception$tc){$this->error=$tc->getMessage();}}function
query($G){return
false;}function
select_db($Sb){try{$this->_db=$this->_link->selectDB($Sb);return
true;}catch(Exception$Jc){$this->error=$Jc->getMessage();return
false;}}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$je){$J=array();foreach($je
as$x=>$X){if(is_a($X,'MongoBinData'))$this->_charset[$x]=63;$J[$x]=(is_a($X,'MongoId')?"ObjectId(\"$X\")":(is_a($X,'MongoDate')?gmdate("Y-m-d H:i:s",$X->sec)." GMT":(is_a($X,'MongoBinData')?$X->bin:(is_a($X,'MongoRegex')?"$X":(is_object($X)?get_class($X):$X)))));}$this->_rows[]=$J;foreach($J
as$x=>$X){if(!isset($this->_rows[0][$x]))$this->_rows[0][$x]=null;}}$this->num_rows=count($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$x=>$X)$I[$x]=$J[$x];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$ne=array_keys($this->_rows[0]);$C=$ne[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$qg="_id";function
select($Q,$L,$Z,$wd,$Ef=array(),$y=1,$E=0,$sg=false){$L=($L==array("*")?array():array_fill_keys($L,true));$zh=array();foreach($Ef
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Hb);$zh[$X]=($Hb?-1:1);}return
new
Min_Result($this->_conn->_db->selectCollection($Q)->find(array(),$L)->sort($zh)->limit($y!=""?+$y:0)->skip($E*$y));}function
insert($Q,$N){try{$I=$this->_conn->_db->selectCollection($Q)->insert($N);$this->_conn->errno=$I['code'];$this->_conn->error=$I['err'];$this->_conn->last_id=$N['_id'];return!$I['err'];}catch(Exception$Jc){$this->_conn->error=$Jc->getMessage();return
false;}}}function
get_databases($hd){global$f;$I=array();$Wb=$f->_link->listDBs();foreach($Wb['databases']as$j)$I[]=$j['name'];return$I;}function
count_tables($i){global$f;$I=array();foreach($i
as$j)$I[$j]=count($f->_link->selectDB($j)->getCollectionNames(true));return$I;}function
tables_list(){global$f;return
array_fill_keys($f->_db->getCollectionNames(true),'table');}function
drop_databases($i){global$f;foreach($i
as$j){$Qg=$f->_link->selectDB($j)->drop();if(!$Qg['ok'])return
false;}return
true;}function
indexes($Q,$g=null){global$f;$I=array();foreach($f->_db->selectCollection($Q)->getIndexInfo()as$u){$gc=array();foreach($u["key"]as$d=>$T)$gc[]=($T==-1?'1':null);$I[$u["name"]]=array("type"=>($u["name"]=="_id_"?"PRIMARY":($u["unique"]?"UNIQUE":"INDEX")),"columns"=>array_keys($u["key"]),"lengths"=>array(),"descs"=>$gc,);}return$I;}function
fields($Q){return
fields_from_edit();}function
found_rows($R,$Z){global$f;return$f->_db->selectCollection($_GET["select"])->count($Z);}$Af=array("=");$_f=null;}elseif(class_exists('MongoDB\Driver\Manager')){class
Min_DB{var$extension="MongoDB",$server_info=MONGODB_VERSION,$affected_rows,$error,$last_id;var$_link;var$_db,$_db_name;function
connect($Oi,$D){$hb='MongoDB\Driver\Manager';$this->_link=new$hb($Oi,$D);$this->executeCommand('admin',array('ping'=>1));}function
executeCommand($j,$rb){$hb='MongoDB\Driver\Command';try{return$this->_link->executeCommand($j,new$hb($rb));}catch(Exception$tc){$this->error=$tc->getMessage();return
array();}}function
executeBulkWrite($cf,$Xa,$Ib){try{$Tg=$this->_link->executeBulkWrite($cf,$Xa);$this->affected_rows=$Tg->$Ib();return
true;}catch(Exception$tc){$this->error=$tc->getMessage();return
false;}}function
query($G){return
false;}function
select_db($Sb){$this->_db_name=$Sb;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows=array(),$_offset=0,$_charset=array();function
__construct($H){foreach($H
as$je){$J=array();foreach($je
as$x=>$X){if(is_a($X,'MongoDB\BSON\Binary'))$this->_charset[$x]=63;$J[$x]=(is_a($X,'MongoDB\BSON\ObjectID')?'MongoDB\BSON\ObjectID("'."$X\")":(is_a($X,'MongoDB\BSON\UTCDatetime')?$X->toDateTime()->format('Y-m-d H:i:s'):(is_a($X,'MongoDB\BSON\Binary')?$X->getData():(is_a($X,'MongoDB\BSON\Regex')?"$X":(is_object($X)||is_array($X)?json_encode($X,256):$X)))));}$this->_rows[]=$J;foreach($J
as$x=>$X){if(!isset($this->_rows[0][$x]))$this->_rows[0][$x]=null;}}$this->num_rows=count($this->_rows);}function
fetch_assoc(){$J=current($this->_rows);if(!$J)return$J;$I=array();foreach($this->_rows[0]as$x=>$X)$I[$x]=$J[$x];next($this->_rows);return$I;}function
fetch_row(){$I=$this->fetch_assoc();if(!$I)return$I;return
array_values($I);}function
fetch_field(){$ne=array_keys($this->_rows[0]);$C=$ne[$this->_offset++];return(object)array('name'=>$C,'charsetnr'=>$this->_charset[$C],);}}class
Min_Driver
extends
Min_SQL{public$qg="_id";function
select($Q,$L,$Z,$wd,$Ef=array(),$y=1,$E=0,$sg=false){global$f;$L=($L==array("*")?array():array_fill_keys($L,1));if(count($L)&&!isset($L['_id']))$L['_id']=0;$Z=where_to_query($Z);$zh=array();foreach($Ef
as$X){$X=preg_replace('~ DESC$~','',$X,1,$Hb);$zh[$X]=($Hb?-1:1);}if(isset($_GET['limit'])&&is_numeric($_GET['limit'])&&$_GET['limit']>0)$y=$_GET['limit'];$y=min(200,max(1,(int)$y));$wh=$E*$y;$hb='MongoDB\Driver\Query';try{return
new
Min_Result($f->_link->executeQuery("$f->_db_name.$Q",new$hb($Z,array('projection'=>$L,'limit'=>$y,'skip'=>$wh,'sort'=>$zh))));}catch(Exception$tc){$f->error=$tc->getMessage();return
false;}}function
update($Q,$N,$Ag,$y=0,$mh="\n"){global$f;$j=$f->_db_name;$Z=sql_query_where_parser($Ag);$hb='MongoDB\Driver\BulkWrite';$Xa=new$hb(array());if(isset($N['_id']))unset($N['_id']);$Ng=array();foreach($N
as$x=>$Y){if($Y=='NULL'){$Ng[$x]=1;unset($N[$x]);}}$Ni=array('$set'=>$N);if(count($Ng))$Ni['$unset']=$Ng;$Xa->update($Z,$Ni,array('upsert'=>false));return$f->executeBulkWrite("$j.$Q",$Xa,'getModifiedCount');}function
delete($Q,$Ag,$y=0){global$f;$j=$f->_db_name;$Z=sql_query_where_parser($Ag);$hb='MongoDB\Driver\BulkWrite';$Xa=new$hb(array());$Xa->delete($Z,array('limit'=>$y));return$f->executeBulkWrite("$j.$Q",$Xa,'getDeletedCount');}function
insert($Q,$N){global$f;$j=$f->_db_name;$hb='MongoDB\Driver\BulkWrite';$Xa=new$hb(array());if($N['_id']=='')unset($N['_id']);$Xa->insert($N);return$f->executeBulkWrite("$j.$Q",$Xa,'getInsertedCount');}}function
get_databases($hd){global$f;$I=array();foreach($f->executeCommand('admin',array('listDatabases'=>1))as$Wb){foreach($Wb->databases
as$j)$I[]=$j->name;}return$I;}function
count_tables($i){$I=array();return$I;}function
tables_list(){global$f;$ob=array();foreach($f->executeCommand($f->_db_name,array('listCollections'=>1))as$H)$ob[$H->name]='table';return$ob;}function
drop_databases($i){return
false;}function
indexes($Q,$g=null){global$f;$I=array();foreach($f->executeCommand($f->_db_name,array('listIndexes'=>$Q))as$u){$gc=array();$e=array();foreach(get_object_vars($u->key)as$d=>$T){$gc[]=($T==-1?'1':null);$e[]=$d;}$I[$u->name]=array("type"=>($u->name=="_id_"?"PRIMARY":(isset($u->unique)?"UNIQUE":"INDEX")),"columns"=>$e,"lengths"=>array(),"descs"=>$gc,);}return$I;}function
fields($Q){global$k;$n=fields_from_edit();if(!$n){$H=$k->select($Q,array("*"),null,null,array(),10);if($H){while($J=$H->fetch_assoc()){foreach($J
as$x=>$X){$J[$x]=null;$n[$x]=array("field"=>$x,"type"=>"string","null"=>($x!=$k->primary),"auto_increment"=>($x==$k->primary),"privileges"=>array("insert"=>1,"select"=>1,"update"=>1,),);}}}}return$n;}function
found_rows($R,$Z){global$f;$Z=where_to_query($Z);$ri=$f->executeCommand($f->_db_name,array('count'=>$R['Name'],'query'=>$Z))->toArray();return$ri[0]->n;}function
sql_query_where_parser($Ag){$Ag=preg_replace('~^\sWHERE \(?\(?(.+?)\)?\)?$~','\1',$Ag);$nj=explode(' AND ',$Ag);$oj=explode(') OR (',$Ag);$Z=array();foreach($nj
as$lj)$Z[]=trim($lj);if(count($oj)==1)$oj=array();elseif(count($oj)>1)$Z=array();return
where_to_query($Z,$oj);}function
where_to_query($jj=array(),$kj=array()){global$b;$Qb=array();foreach(array('and'=>$jj,'or'=>$kj)as$T=>$Z){if(is_array($Z)){foreach($Z
as$Pc){list($kb,$yf,$X)=explode(" ",$Pc,3);if($kb=="_id"&&preg_match('~^(MongoDB\\\\BSON\\\\ObjectID)\("(.+)"\)$~',$X,$B)){list(,$hb,$X)=$B;$X=new$hb($X);}if(!in_array($yf,$b->operators))continue;if(preg_match('~^\(f\)(.+)~',$yf,$B)){$X=(float)$X;$yf=$B[1];}elseif(preg_match('~^\(date\)(.+)~',$yf,$B)){$Tb=new
DateTime($X);$hb='MongoDB\BSON\UTCDatetime';$X=new$hb($Tb->getTimestamp()*1000);$yf=$B[1];}switch($yf){case'=':$yf='$eq';break;case'!=':$yf='$ne';break;case'>':$yf='$gt';break;case'<':$yf='$lt';break;case'>=':$yf='$gte';break;case'<=':$yf='$lte';break;case'regex':$yf='$regex';break;default:continue
2;}if($T=='and')$Qb['$and'][]=array($kb=>array($yf=>$X));elseif($T=='or')$Qb['$or'][]=array($kb=>array($yf=>$X));}}}return$Qb;}$Af=array("=","!=",">","<",">=","<=","regex","(f)=","(f)!=","(f)>","(f)<","(f)>=","(f)<=","(date)=","(date)!=","(date)>","(date)<","(date)>=","(date)<=",);$_f='regex';}function
table($t){return$t;}function
idf_escape($t){return$t;}function
table_status($C="",$Wc=false){$I=array();foreach(tables_list()as$Q=>$T){$I[$Q]=array("Name"=>$Q);if($C==$Q)return$I[$Q];}return$I;}function
create_database($j,$mb){return
true;}function
last_id(){global$f;return$f->last_id;}function
error(){global$f;return
h($f->error);}function
collations(){return
array();}function
logged_user(){global$b;$Lb=$b->credentials();return$Lb[1];}function
connect(){global$b;$f=new
Min_DB;list($M,$V,$F)=$b->credentials();$D=array();if($V.$F!=""){$D["username"]=$V;$D["password"]=$F;}$j=$b->database();if($j!="")$D["db"]=$j;if(($Ka=getenv("MONGO_AUTH_SOURCE")))$D["authSource"]=$Ka;$f->connect("mongodb://$M",$D);if($f->error)return$f->error;return$f;}function
alter_indexes($Q,$c){global$f;foreach($c
as$X){list($T,$C,$N)=$X;if($N=="DROP")$I=$f->_db->command(array("deleteIndexes"=>$Q,"index"=>$C));else{$e=array();foreach($N
as$d){$d=preg_replace('~ DESC$~','',$d,1,$Hb);$e[$d]=($Hb?-1:1);}$I=$f->_db->selectCollection($Q)->ensureIndex($e,array("unique"=>($T=="UNIQUE"),"name"=>$C,));}if($I['errmsg']){$f->error=$I['errmsg'];return
false;}}return
true;}function
support($Xc){return
preg_match("~database|indexes|descidx~",$Xc);}function
db_collation($j,$nb){}function
information_schema(){}function
is_view($R){}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
foreign_keys($Q){return
array();}function
fk_support($R){}function
engines(){return
array();}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){global$f;if($Q==""){$f->_db->createCollection($C);return
true;}}function
drop_tables($S){global$f;foreach($S
as$Q){$Qg=$f->_db->selectCollection($Q)->drop();if(!$Qg['ok'])return
false;}return
true;}function
truncate_tables($S){global$f;foreach($S
as$Q){$Qg=$f->_db->selectCollection($Q)->remove();if(!$Qg['ok'])return
false;}return
true;}function
driver_config(){global$Af,$_f;return
array('possible_drivers'=>array("mongo","mongodb"),'jush'=>"mongo",'operators'=>$Af,'operator_regexp'=>$_f,'functions'=>array(),'grouping'=>array(),'edit_functions'=>array(array("json")),);}}$mc["elastic"]="Elasticsearch (beta)";if(isset($_GET["elastic"])){define("DRIVER","elastic");if(function_exists('json_decode')&&ini_bool('allow_url_fopen')){class
Min_DB{var$extension="JSON",$server_info,$errno,$error,$_url,$_db;function
rootQuery($dg,array$Cb=null,$Ve='GET'){@ini_set('track_errors',1);$bd=@file_get_contents("$this->_url/".ltrim($dg,'/'),false,stream_context_create(array('http'=>array('method'=>$Ve,'content'=>$Cb!==null?json_encode($Cb):null,'header'=>$Cb!==null?'Content-Type: application/json':[],'ignore_errors'=>1,'follow_location'=>0,'max_redirects'=>0,))));if($bd===false){$this->error='Invalid server or credentials.';return
false;}$I=json_decode($bd,true);if($I===null){$this->error='Invalid server or credentials.';return
false;}if(!preg_match('~^HTTP/[0-9.]+ 2~i',$http_response_header[0])){if(isset($I['error']['root_cause'][0]['type']))$this->error=$I['error']['root_cause'][0]['type'].": ".$I['error']['root_cause'][0]['reason'];else$this->error='Invalid server or credentials.';return
false;}return$I;}function
query($dg,array$Cb=null,$Ve='GET'){return$this->rootQuery(($this->_db!=""?"$this->_db/":"/").ltrim($dg,'/'),$Cb,$Ve);}function
connect($M,$V,$F){$this->_url=build_http_url($M,$V,$F,"localhost",9200);$I=$this->query('');if(!$I)return
false;if(!isset($I['version']['number'])){$this->error='Invalid server or credentials.';return
false;}$this->server_info=$I['version']['number'];return
true;}function
select_db($Sb){$this->_db=$Sb;return
true;}function
quote($P){return$P;}}class
Min_Result{var$num_rows,$_rows;function
__construct($K){$this->num_rows=count($K);$this->_rows=$K;reset($this->_rows);}function
fetch_assoc(){$I=current($this->_rows);next($this->_rows);return$I;}function
fetch_row(){return
array_values($this->fetch_assoc());}}}class
Min_Driver
extends
Min_SQL{function
select($Q,$L,$Z,$wd,$Ef=array(),$y=1,$E=0,$sg=false){global$b;$Qb=array();$G="$Q/_search";if($L!=array("*"))$Qb["fields"]=$L;if($Ef){$zh=array();foreach($Ef
as$kb){$kb=preg_replace('~ DESC$~','',$kb,1,$Hb);$zh[]=($Hb?array($kb=>"desc"):$kb);}$Qb["sort"]=$zh;}if($y){$Qb["size"]=+$y;if($E)$Qb["from"]=($E*$y);}foreach($Z
as$X){list($kb,$yf,$X)=explode(" ",$X,3);if($kb=="_id")$Qb["query"]["ids"]["values"][]=$X;elseif($kb.$X!=""){$ei=array("term"=>array(($kb!=""?$kb:"_all")=>$X));if($yf=="=")$Qb["query"]["filtered"]["filter"]["and"][]=$ei;else$Qb["query"]["filtered"]["query"]["bool"]["must"][]=$ei;}}if($Qb["query"]&&!$Qb["query"]["filtered"]["query"]&&!$Qb["query"]["ids"])$Qb["query"]["filtered"]["query"]=array("match_all"=>array());$Hh=microtime(true);$gh=$this->_conn->query($G,$Qb);if($sg)echo$b->selectQuery("$G: ".json_encode($Qb),$Hh,!$gh);if(!$gh)return
false;$I=array();foreach($gh['hits']['hits']as$Id){$J=array();if($L==array("*"))$J["_id"]=$Id["_id"];$n=$Id['_source'];if($L!=array("*")){$n=array();foreach($L
as$x)$n[$x]=$Id['fields'][$x];}foreach($n
as$x=>$X){if($Qb["fields"])$X=$X[0];$J[$x]=(is_array($X)?json_encode($X):$X);}$I[]=$J;}return
new
Min_Result($I);}function
update($T,$Eg,$Ag,$y=0,$mh="\n"){$bg=preg_split('~ *= *~',$Ag);if(count($bg)==2){$s=trim($bg[1]);$G="$T/$s";return$this->_conn->query($G,$Eg,'POST');}return
false;}function
insert($T,$Eg){$s="";$G="$T/$s";$Qg=$this->_conn->query($G,$Eg,'POST');$this->_conn->last_id=$Qg['_id'];return$Qg['created'];}function
delete($T,$Ag,$y=0){$Md=array();if(is_array($_GET["where"])&&$_GET["where"]["_id"])$Md[]=$_GET["where"]["_id"];if(is_array($_POST['check'])){foreach($_POST['check']as$bb){$bg=preg_split('~ *= *~',$bb);if(count($bg)==2)$Md[]=trim($bg[1]);}}$this->_conn->affected_rows=0;foreach($Md
as$s){$G="{$T}/{$s}";$Qg=$this->_conn->query($G,'{}','DELETE');if(is_array($Qg)&&$Qg['found']==true)$this->_conn->affected_rows++;}return$this->_conn->affected_rows;}}function
connect(){global$b;$f=new
Min_DB;list($M,$V,$F)=$b->credentials();if($F!=""&&$f->connect($M,$V,""))return'Database does not support password.';if($f->connect($M,$V,$F))return$f;return$f->error;}function
support($Xc){return
preg_match("~database|table|columns~",$Xc);}function
logged_user(){global$b;$Lb=$b->credentials();return$Lb[1];}function
get_databases(){global$f;$I=$f->rootQuery('_aliases');if($I){$I=array_keys($I);sort($I,SORT_STRING);}return$I;}function
collations(){return
array();}function
db_collation($j,$nb){}function
engines(){return
array();}function
count_tables($i){global$f;$I=array();$H=$f->query('_stats');if($H&&$H['indices']){$Ud=$H['indices'];foreach($Ud
as$Td=>$Ih){$Sd=$Ih['total']['indexing'];$I[$Td]=$Sd['index_total'];}}return$I;}function
tables_list(){global$f;if(min_version(6))return
array('_doc'=>'table');$I=$f->query('_mapping');if($I)$I=array_fill_keys(array_keys($I[$f->_db]["mappings"]),'table');return$I;}function
table_status($C="",$Wc=false){global$f;$gh=$f->query("_search",array("size"=>0,"aggregations"=>array("count_by_type"=>array("terms"=>array("field"=>"_type")))),"POST");$I=array();if($gh){$S=$gh["aggregations"]["count_by_type"]["buckets"];foreach($S
as$Q){$I[$Q["key"]]=array("Name"=>$Q["key"],"Engine"=>"table","Rows"=>$Q["doc_count"],);if($C!=""&&$C==$Q["key"])return$I[$C];}}return$I;}function
error(){global$f;return
h($f->error);}function
information_schema(){}function
is_view($R){}function
indexes($Q,$g=null){return
array(array("type"=>"PRIMARY","columns"=>array("_id")),);}function
fields($Q){global$f;$Ee=array();if(min_version(6)){$H=$f->query("_mapping");if($H)$Ee=$H[$f->_db]['mappings']['properties'];}else{$H=$f->query("$Q/_mapping");if($H){$Ee=$H[$Q]['properties'];if(!$Ee)$Ee=$H[$f->_db]['mappings'][$Q]['properties'];}}$I=array();if($Ee){foreach($Ee
as$C=>$m){$I[$C]=array("field"=>$C,"full_type"=>$m["type"],"type"=>$m["type"],"privileges"=>array("insert"=>1,"select"=>1,"update"=>1),);if($m["properties"]){unset($I[$C]["privileges"]["insert"]);unset($I[$C]["privileges"]["update"]);}}}return$I;}function
foreign_keys($Q){return
array();}function
table($t){return$t;}function
idf_escape($t){return$t;}function
convert_field($m){}function
unconvert_field($m,$I){return$I;}function
fk_support($R){}function
found_rows($R,$Z){return
null;}function
create_database($j){global$f;return$f->rootQuery(urlencode($j),null,'PUT');}function
drop_databases($i){global$f;return$f->rootQuery(urlencode(implode(',',$i)),array(),'DELETE');}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){global$f;$yg=array();foreach($n
as$Uc){$Zc=trim($Uc[1][0]);$ad=trim($Uc[1][1]?$Uc[1][1]:"text");$yg[$Zc]=array('type'=>$ad);}if(!empty($yg))$yg=array('properties'=>$yg);return$f->query("_mapping/{$C}",$yg,'PUT');}function
drop_tables($S){global$f;$I=true;foreach($S
as$Q)$I=$I&&$f->query(urlencode($Q),array(),'DELETE');return$I;}function
last_id(){global$f;return$f->last_id;}function
driver_config(){$U=array();$Lh=array();foreach(array('Numbers'=>array("long"=>3,"integer"=>5,"short"=>8,"byte"=>10,"double"=>20,"float"=>66,"half_float"=>12,"scaled_float"=>21),'Date and time'=>array("date"=>10),'Strings'=>array("string"=>65535,"text"=>65535),'Binary'=>array("binary"=>255),)as$x=>$X){$U+=$X;$Lh[$x]=array_keys($X);}return
array('possible_drivers'=>array("json + allow_url_fopen"),'jush'=>"elastic",'operators'=>array("=","query"),'functions'=>array(),'grouping'=>array(),'edit_functions'=>array(array("json")),'types'=>$U,'structured_types'=>$Lh,);}}class
Adminer{var$operators;function
name(){return"<a href='https://www.adminerevo.org/'".target_blank()." id='h1'>AdminerEvo</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($h=false){return
password_file($h);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
serverName($M){return
h($M);}function
database(){return
DB;}function
databases($hd=true){return
get_databases($hd);}function
schemas(){return
schemas();}function
queryTimeout(){return
2;}function
headers(){}function
csp(){return
csp();}function
head(){return
true;}function
css(){$I=array();$o="adminer.css";if(file_exists($o))$I[]="$o?v=".crc32(file_get_contents($o));return$I;}function
loginForm(){global$mc;echo"<table cellspacing='0' class='layout'>\n",$this->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",$mc,DRIVER,"loginDriver(this);")."\n"),$this->loginFormField('server','<tr><th>'.'Server'.'<td>','<input name="auth[server]" value="'.h(SERVER).'" title="hostname[:port]" placeholder="localhost" autocapitalize="off">'."\n"),$this->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("focus(qs('#username')); qs('#username').form['auth[driver]'].onchange();")),$this->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'."\n"),$this->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'."\n"),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($C,$Fd,$Y){return$Fd.$Y;}function
login($Ce,$F){if(1==1)
sprintf('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.',target_blank());return
true;}function
tableName($Sh){return
h($Sh["Name"]);}function
fieldName($m,$Ef=0){return'<span title="'.h($m["full_type"]).'">'.h($m["field"]).'</span>';}function
selectLinks($Sh,$N=""){global$w,$k;echo'<p class="links">';$wa=array("select"=>'Select data');if(support("table")||support("indexes"))$wa["table"]='Show structure';if(support("table")){if(is_view($Sh))$wa["view"]='Alter view';else$wa["create"]='Alter table';}if($N!==null)$wa["edit"]='New item';$C=$Sh["Name"];$_=[];foreach($wa
as$x=>$X)$_[]="<a href='".h(ME)."$x=".urlencode($C).($x=="edit"?$N:"")."'".bold(isset($_GET[$x])).">$X</a>";echo
generate_linksbar($_),doc_link(array($w=>$k->tableHelp($C)),"?"),"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Rh){return
array();}function
backwardKeysPrint($Oa,$J){}function
selectQuery($G,$Hh,$Vc=false){global$w,$k;if(!$Vc&&($gj=$k->warnings())){$s="warnings";$I=", <a href='#$s'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$s');","")."<div id='$s' class='hidden'>\n$gj</div>\n";}$_=[(support("sql")?"<a href='".h(ME)."sql=".urlencode($G)."'>".'Edit'."</a>":""),"<a href='#' class='copy-to-clipboard'>".'Copy to clipboard'."</a>",];return"<code class='jush-$w copy-to-clipboard'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>(".format_time($Hh).")</span>".generate_linksbar($_);}function
sqlCommandQuery($G){return
shorten_utf8(trim($G),1000);}function
rowDescription($Q){return"";}function
rowDescriptions($K,$ld){return$K;}function
selectLink($X,$m){}function
selectVal($X,$z,$m,$Mf){$I=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$m["type"]??null)&&!preg_match("~var~",$m["type"]??null)?"<code>$X</code>":$X));if(preg_match('~blob|bytea|raw|file~',$m["type"]??null)&&!is_utf8($X))$I="<i>".lang(array('%d byte','%d bytes'),strlen($Mf))."</i>";if(preg_match('~json~',$m["type"]??null))$I="<code class='jush-js'>$I</code>";return($z?"<a href='".h($z)."'".(is_url($z)?target_blank():"").">$I</a>":$I);}function
editVal($X,$m){return$X;}function
tableStructurePrint($n){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr><th>".'Column'."<td>".'Type'.(support("comment")?"<td>".'Comment':"")."</thead>\n";foreach($n
as$m){echo"<tr".odd()."><th>".h($m["field"]),"<td><span title='".h($m["collation"])."'>".h($m["full_type"])."</span>",($m["null"]?" <i>NULL</i>":""),($m["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($m["default"])?" <span title='".'Default value'."'>[<b>".h($m["default"])."</b>]</span>":""),(support("comment")?"<td>".h($m["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint($v){echo"<table cellspacing='0'>\n";foreach($v
as$C=>$u){ksort($u["columns"]);$sg=array();foreach($u["columns"]as$x=>$X)$sg[]="<i>".h($X)."</i>".($u["lengths"][$x]?"(".$u["lengths"][$x].")":"").($u["descs"][$x]?" DESC":"");echo"<tr title='".h($C)."'><th>$u[type]<td>".implode(", ",$sg)."\n";}echo"</table>\n";}function
selectColumnsPrint($L,$e){global$sd,$zd;print_fieldset("select",'Select',$L);$r=0;$L[""]=array();foreach($L
as$x=>$X){$X=$_GET["columns"][$x]??null;$d=select_input(" name='columns[$r][col]'",$e,$X["col"]??null,($x!==""?"selectFieldChange":"selectAddRow"));echo"<div>".($sd||$zd?"<select name='columns[$r][fun]'>".optionlist(array(-1=>"")+array_filter(array('Functions'=>$sd,'Aggregation'=>$zd)),$X["fun"]??null)."</select>".on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'",1).script("qsl('select').onchange = function () { helpClose();".($x!==""?"":" qsl('select, input', this.parentNode).onchange();")." };","")."($d)":$d)." <input type='image' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' class='jsonly icon' title='",h('Remove'),"' alt='x'>".script('qsl(".icon").onclick = selectRemoveRow;',"")."</div>\n";$r++;}echo"</div></fieldset>\n";}function
selectSearchPrint($Z,$e,$v){print_fieldset("search",'Search',$Z);foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT"){echo"<div>(<i>".implode("</i>, <i>",array_map('h',$u["columns"]))."</i>) AGAINST"," <input type='search' name='fulltext[$r]' value='".h($_GET["fulltext"][$r])."'>",script("qsl('input').oninput = selectFieldChange;",""),checkbox("boolean[$r]",1,isset($_GET["boolean"][$r]),"BOOL"),"</div>\n";}}$Za="this.parentNode.firstChild.onchange();";foreach(array_merge((array)$_GET["where"],array(array()))as$r=>$X){if(!$X||("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators))){echo"<div>".select_input(" name='where[$r][col]'",$e,$X["col"],($X?"selectFieldChange":"selectAddRow"),"(".'anywhere'.")"),html_select("where[$r][op]",$this->operators,$X["op"],$Za),"<input type='search' name='where[$r][val]' value='".h($X["val"])."'>",script("mixin(qsl('input'), {oninput: function () { $Za }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});",""),"<input type='image' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' class='jsonly icon' title='",h('Remove'),"' alt='x'>",script('qsl(".icon").onclick = selectRemoveRow;',""),"</div>\n";}}echo"</div></fieldset>\n";}function
selectOrderPrint($Ef,$e,$v){print_fieldset("sort",'Sort',$Ef);$r=0;foreach((array)$_GET["order"]as$x=>$X){if($X!=""){echo"<div>".select_input(" name='order[$r]'",$e,$X,"selectFieldChange"),checkbox("desc[$r]",1,isset($_GET["desc"][$x]),'descending')," <input type='image' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' class='jsonly icon' title='",h('Remove'),"' alt='x'>",script('qsl(".icon").onclick = selectRemoveRow;',""),"</div>\n";$r++;}}echo"<div>".select_input(" name='order[$r]'",$e,"","selectAddRow"),checkbox("desc[$r]",1,false,'descending')," <input type='image' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' class='jsonly icon' title='",h('Remove'),"' alt='x'>",script('qsl(".icon").onclick = selectRemoveRow;',""),"</div>\n","</div></fieldset>\n";}function
selectLimitPrint($y){echo"<fieldset><legend>".'Limit'."</legend><div>";echo"<input type='number' name='limit' class='size' value='".h($y)."'>",script("qsl('input').oninput = selectFieldChange;",""),"</div></fieldset>\n";}function
selectLengthPrint($hi){if($hi!==null){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($hi)."'>","</div></fieldset>\n";}}function
selectActionPrint($v){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","var indexColumns = ";$e=array();foreach($v
as$u){$Pb=reset($u["columns"]);if($u["type"]!="FULLTEXT"&&$Pb)$e[$Pb]=1;}$e[""]=1;foreach($e
as$x=>$X)json_row($x);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint($zc,$e){}function
selectColumnsProcess($e,$v){global$sd,$zd;$L=array();$wd=array();foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],$sd)||in_array($X["fun"],$zd)))){$L[$x]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],$zd))$wd[]=$L[$x];}}return
array($L,$wd);}function
selectSearchProcess($n,$v){global$f,$k;$I=array();foreach($v
as$r=>$u){if($u["type"]=="FULLTEXT"&&$_GET["fulltext"][$r]!="")$I[]="MATCH (".implode(", ",array_map('idf_escape',$u["columns"])).") AGAINST (".q($_GET["fulltext"][$r]).(isset($_GET["boolean"][$r])?" IN BOOLEAN MODE":"").")";}foreach((array)$_GET["where"]as$x=>$X){if("$X[col]$X[val]"!=""&&in_array($X["op"],$this->operators)){$og="";$wb=" $X[op]";if(preg_match('~IN$~',$X["op"])){$Pd=process_length($X["val"]);$wb.=" ".($Pd!=""?$Pd:"(NULL)");}elseif($X["op"]=="SQL")$wb=" $X[val]";elseif($X["op"]=="LIKE %%")$wb=" LIKE ".$this->processInput($n[$X["col"]],"%$X[val]%");elseif($X["op"]=="ILIKE %%")$wb=" ILIKE ".$this->processInput($n[$X["col"]],"%$X[val]%");elseif($X["op"]=="FIND_IN_SET"){$og="$X[op](".q($X["val"]).", ";$wb=")";}elseif(!preg_match('~NULL$~',$X["op"]))$wb.=" ".$this->processInput($n[$X["col"]],$X["val"]);if($X["col"]!="")$I[]=$og.$k->convertSearch(idf_escape($X["col"]),$X,$n[$X["col"]]).$wb;else{$pb=array();foreach($n
as$C=>$m){if((preg_match('~^[-\d.'.(preg_match('~IN$~',$X["op"])?',':'').']+$~',$X["val"])||!preg_match('~'.number_type().'|bit~',$m["type"]))&&(!preg_match("~[\x80-\xFF]~",$X["val"])||preg_match('~char|text|enum|set~',$m["type"]))&&(!preg_match('~date|timestamp~',$m["type"])||preg_match('~^\d+-\d+-\d+~',$X["val"])))$pb[]=$og.$k->convertSearch(idf_escape($C),$X,$m).$wb;}$I[]=($pb?"(".implode(" OR ",$pb).")":"1 = 0");}}}return$I;}function
selectOrderProcess($n,$v){$I=array();foreach((array)$_GET["order"]as$x=>$X){if($X!="")$I[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$x])?" DESC":"");}return$I;}function
selectLimitProcess(){return(isset($_GET["limit"])?$_GET["limit"]:"50");}function
selectLengthProcess(){return(isset($_GET["text_length"])?$_GET["text_length"]:"100");}function
selectEmailProcess($Z,$ld){return
false;}function
selectQueryBuild($L,$Z,$wd,$Ef,$y,$E){return"";}function
messageQuery($G,$ii,$Vc=false){global$w,$k;restart_session();$Gd=&get_session("queries");if(isset($Gd[$_GET["db"]])===false)$Gd[$_GET["db"]]=array();if(strlen($G)>1e6)$G=preg_replace('~[\x80-\xFF]+$~','',substr($G,0,1e6))."\nâ€¦";$Gd[$_GET["db"]][]=array($G,time(),$ii);$Eh="sql-".count($Gd[$_GET["db"]]);$I="<a href='#$Eh' class='toggle'>".'SQL command'."</a> <a href='#' class='copy-to-clipboard icon expand' data-expand-id='$Eh'></a>\n";if(!$Vc&&($gj=$k->warnings())){$s="warnings-".count($Gd[$_GET["db"]]);$I="<a href='#$s' class='toggle'>".'Warnings'."</a>, $I<div id='$s' class='hidden'>\n$gj</div>\n";}$_=[];if(support("sql")){$_[]='<a href="'.h(str_replace("db=".urlencode(DB),"db=".urlencode($_GET["db"]),ME).'sql=&history='.(count($Gd[$_GET["db"]])-1)).'">'.'Edit'.'</a>';$_[]='<a href="#" class="copy-to-clipboard">'.'Copy to clipboard'.'</a>';}return" <span class='time'>".@date("H:i:s")."</span>"." $I<div id='$Eh' class='hidden'><pre><code class='jush-$w copy-to-clipboard'>".shorten_utf8($G,1000)."</code></pre>".($ii?" <span class='time'>($ii)</span>":'').generate_linksbar($_).'</div>';}function
editRowPrint($Q,$n,$J,$Ni){}function
editFunctions($m){global$uc;$I=($m["null"]?"NULL/":"");$Ni=isset($_GET["select"])||where($_GET);foreach($uc
as$x=>$sd){if(!$x||(!isset($_GET["call"])&&$Ni)){foreach($sd
as$fg=>$X){if(!$fg||preg_match("~$fg~",$m["type"]))$I.="/$X";}}if($x&&!preg_match('~set|blob|bytea|raw|file|bool~',$m["type"]))$I.="/SQL";}if($m["auto_increment"]&&!$Ni)$I='Auto Increment';return
explode("/",$I);}function
editInput($Q,$m,$Ia,$Y){if($m["type"]=="enum"){$D=array();$ih=$Y;if(isset($_GET["select"])){$D[-1]='original';if($ih===null)$ih=-1;}if($m["null"]){$D[""]="NULL";if($Y===null&&!isset($_GET["select"]))$ih="";}$D[0]='empty';preg_match_all("~'((?:[^']|'')*)'~",$m["length"],$Ie);foreach($Ie[1]as$r=>$X){$X=stripcslashes(str_replace("''","'",$X));$D[$r+1]=$X;if($Y===$X)$ih=$r+1;}return"<select$Ia>".optionlist($D,(string)$ih,1)."</select>";}return"";}function
editHint($Q,$m,$Y){return"";}function
processInput($m,$Y,$q=""){if($q=="SQL")return$Y;$C=$m["field"];$I=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$q))$I="$q()";elseif(preg_match('~^current_(date|timestamp)$~',$q))$I=$q;elseif(preg_match('~^([+-]|\|\|)$~',$q))$I=idf_escape($C)." $q $I";elseif(preg_match('~^[+-] interval$~',$q))$I=idf_escape($C)." $q ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)?$Y:$I);elseif(preg_match('~^(addtime|subtime|concat)$~',$q))$I="$q(".idf_escape($C).", $I)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$q))$I="$q($I)";return
unconvert_field($m,$I);}function
dumpOutput(){$I=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$I['gz']='gzip';return$I;}function
dumpFormat(){return
array('sql'=>'SQL','csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpDatabase($j){}function
dumpTable($Q,$Mh,$ie=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Mh)dump_csv(array_keys(fields($Q)));}else{if($ie==2){$n=array();foreach(fields($Q)as$C=>$m)$n[]=idf_escape($C)." $m[full_type]";$h="CREATE TABLE ".table($Q)." (".implode(", ",$n).")";}else$h=create_sql($Q,$_POST["auto_increment"],$Mh);set_utf8mb4($h);if($Mh&&$h){if($Mh=="DROP+CREATE"||$ie==1)echo"DROP ".($ie==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($ie==1)$h=remove_definer($h);echo"$h;\n\n";}}}function
dumpData($Q,$Mh,$G){global$f,$w;$Ke=($w=="sqlite"?0:1048576);if($Mh){if($_POST["format"]=="sql"){if($Mh=="TRUNCATE+INSERT")echo
truncate_sql($Q).";\n";$n=fields($Q);}$H=$f->query($G,1);if($H){$be="";$Wa="";$ne=array();$td=array();$Oh="";$Yc=($Q!=''?'fetch_assoc':'fetch_row');while($J=$H->$Yc()){if(!$ne){$Yi=array();foreach($J
as$X){$m=$H->fetch_field();if(!empty($n[$m->name]['generated'])){$td[$m->name]=true;continue;}$ne[]=$m->name;$x=idf_escape($m->name);$Yi[]="$x = VALUES($x)";}$Oh=($Mh=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Yi):"").";\n";}if($_POST["format"]!="sql"){if($Mh=="table"){dump_csv($ne);$Mh="INSERT";}dump_csv($J);}else{if(!$be)$be="INSERT INTO ".table($Q)." (".implode(", ",array_map('idf_escape',$ne)).") VALUES";foreach($J
as$x=>$X){if(isset($td[$x])){unset($J[$x]);continue;}$m=$n[$x];$J[$x]=($X!==null?unconvert_field($m,preg_match(number_type(),$m["type"])&&!preg_match('~\[~',$m["full_type"])&&is_numeric($X)?$X:q(($X===false?0:$X))):"NULL");}$ch=($Ke?"\n":" ")."(".implode(",\t",$J).")";if(!$Wa)$Wa=$be.$ch;elseif(strlen($Wa)+4+strlen($ch)+strlen($Oh)<$Ke)$Wa.=",$ch";else{echo$Wa.$Oh;$Wa=$be.$ch;}}}if($Wa)echo$Wa.$Oh;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",$f->error)."\n";}}function
dumpFilename($Ld){return
friendly_url($Ld!=""?$Ld:(SERVER!=""?SERVER:"localhost"));}function
dumpHeaders($Ld,$Ye=false){$Pf=$_POST["output"];$Qc=(preg_match('~sql~',$_POST["format"])?"sql":($Ye?"tar":"csv"));header("Content-Type: ".($Pf=="gz"?"application/x-gzip":($Qc=="tar"?"application/x-tar":($Qc=="sql"||$Pf!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Pf=="gz")ob_start('ob_gzencode',1e6);return$Qc;}function
importServerPath(){return"adminer.sql";}function
homepage(){$_=[];if($_GET["ns"]==""&&support("database"))$_[]='<a href="'.h(ME).'database=">'.'Alter database'.'</a>';if(support("scheme"))$_[]="<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>";if($_GET["ns"]!=="")$_[]='<a href="'.h(ME).'schema=">'.'Database schema'.'</a>';if(support("privileges"))$_[]="<a href='".h(ME)."privileges='>".'Privileges'."</a>";echo
generate_linksbar($_);return
true;}function
navigation($Xe){global$ia,$w,$mc,$f;echo'<h1>
',$this->name(),' <span class="version">',$ia,'</span>
<a href="https://download.adminerevo.org/latest/adminer/"',target_blank(),' id="version" title="A newer version of AdminerEvo is available, download it now!">',(version_compare($ia,$_COOKIE["adminer_version"])<0?h($_COOKIE["adminer_version"]):""),'</a>
</h1>
';if($Xe=="auth"){$Pf="";foreach((array)$_SESSION["pwds"]as$aj=>$qh){foreach($qh
as$M=>$Vi){foreach($Vi
as$V=>$F){if($F!==null){$Wb=$_SESSION["db"][$aj][$M][$V];foreach(($Wb?array_keys($Wb):array(""))as$j)$Pf.="<li><a href='".h(auth_url($aj,$M,$V,$j))."'>($mc[$aj]) ".h($V.($M!=""?"@".$this->serverName($M):"").($j!=""?" - $j":""))."</a>\n";}}}}if($Pf)echo"<ul id='logins'>\n$Pf</ul>\n".script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");}else{$S=array();if($_GET["ns"]!==""&&!$Xe&&DB!=""){$f->select_db(DB);$S=table_status('',true);}echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=4.8.4");if(support("sql")){echo'<script',nonce(),'>
';if($S){$_=array();foreach($S
as$Q=>$T)$_[]=preg_quote($Q,'/');echo"var jushLinks = { $w: [ '".js_escape(ME).(support("table")?"table=":"select=")."\$&', /\\b(".implode("|",$_).")\\b/g ] };\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$X)echo"jushLinks.$X = jushLinks.$w;\n";}$ph=$f->server_info;echo'bodyLoad(\'',(is_object($f)?preg_replace('~^(\d\.?\d).*~s','\1',$ph):""),'\'',(preg_match('~MariaDB~',$ph)?", true":""),');
</script>
';}$this->databasesPrint($Xe);$_=[];if(DB==""||!$Xe){if(support("sql")){$_[]="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$_[]="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}if(support("dump"))$_[]="<a href='".h(ME)."dump=".urlencode(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}echo
generate_linksbar($_);if($_GET["ns"]!==""&&!$Xe&&DB!=""){echo
generate_linksbar(['<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>"]);if(!$S)echo"<p class='message'>".'No tables.'."\n";else$this->tablesPrint($S);}}}function
databasesPrint($Xe){global$b,$f;$i=$this->databases();if(DB&&$i&&!in_array(DB,$i))array_unshift($i,DB);echo'<form action="">
',"<table id='dbs'><tr><td width=1>";hidden_fields_get();$Ub=script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");echo"<label title='".'database'."' for='menu_db'>".'DB'."</label>:</td><td>".($i?"<select name='db' id='menu_db'>".optionlist(array(""=>"")+$i,DB)."</select>$Ub":"<input name='db' id='menu_db' value='".h(DB)."' autocapitalize='off'>\n"),"</td></tr>";if(support("scheme")){if($Xe!="db"&&DB!=""&&$f->select_db(DB)){echo"<tr><td><label for='menu_ns'>".'Schema'.":</label></td>","<td><select name='ns' id='menu_ns'>".optionlist(array(""=>"")+$b->schemas(),$_GET["ns"])."</select>$Ub";if($_GET["ns"]!="")set_schema($_GET["ns"]);echo"</td></tr>";}}echo"<tr".($i?" class='hidden'":"")."><td colspan=2><input type='submit' value='".'Use'."'></td></tr>\n","</table>";foreach(array("import","sql","schema","dump","privileges")as$X){if(isset($_GET[$X])){echo"<input type='hidden' name='$X' value=''>";break;}}}function
tablesPrint($S){echo"<ul id='tables'>".script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");foreach($S
as$Q=>$O){$C=$this->tableName($O);if($C!=""){echo'<li><a href="'.h(ME).'select='.urlencode($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select")." title='".'Select data'."'>".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.urlencode($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["select"])),(is_view($O)?"view":"structure"))." title='".'Show structure'."'>$C</a>":"<span>$C</span>")."\n";}}echo"</ul>\n";}}$b=(function_exists('adminer_object')?adminer_object():new
Adminer);$mc=array("server"=>"MySQL")+$mc;if(!defined("DRIVER")){define("DRIVER","server");if(extension_loaded("mysqli")){class
Min_DB
extends
MySQLi{var$extension="MySQLi";function
__construct(){parent::init();}function
connect($M="",$V="",$F="",$Sb=null,$jg=null,$yh=null){global$b;mysqli_report(MYSQLI_REPORT_OFF);list($Jd,$jg)=explode(":",$M,2);$Gh=$b->connectSsl();if($Gh)$this->ssl_set($Gh['key'],$Gh['cert'],$Gh['ca'],'','');$I=@$this->real_connect(($M!=""?$Jd:ini_get("mysqli.default_host")),($M.$V!=""?$V:ini_get("mysqli.default_user")),($M.$V.$F!=""?$F:ini_get("mysqli.default_pw")),$Sb,(is_numeric($jg)?$jg:ini_get("mysqli.default_port")),(!is_numeric($jg)?$jg:$yh),($Gh?64:0));$this->options(MYSQLI_OPT_LOCAL_INFILE,false);return$I;}function
set_charset($ab){if(parent::set_charset($ab))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $ab");}function
result($G,$m=0){$H=$this->query($G);if(!$H)return
false;$J=$H->fetch_array();return$J[$m];}function
quote($P){return"'".$this->escape_string($P)."'";}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Min_DB{var$extension="MySQL",$server_info,$affected_rows,$errno,$error,$_link,$_result;function
connect($M,$V,$F){if(ini_bool("mysql.allow_local_infile")){$this->error=sprintf('Disable %s or enable %s or %s extensions.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");return
false;}$this->_link=@mysql_connect(($M!=""?$M:ini_get("mysql.default_host")),("$M$V"!=""?$V:ini_get("mysql.default_user")),("$M$V$F"!=""?$F:ini_get("mysql.default_password")),true,131072);if($this->_link)$this->server_info=mysql_get_server_info($this->_link);else$this->error=mysql_error();return(bool)$this->_link;}function
set_charset($ab){if(function_exists('mysql_set_charset')){if(mysql_set_charset($ab,$this->_link))return
true;mysql_set_charset('utf8',$this->_link);}return$this->query("SET NAMES $ab");}function
quote($P){return"'".mysql_real_escape_string($P,$this->_link)."'";}function
select_db($Sb){return
mysql_select_db($Sb,$this->_link);}function
query($G,$Gi=false){$H=@($Gi?mysql_unbuffered_query($G,$this->_link):mysql_query($G,$this->_link));$this->error="";if(!$H){$this->errno=mysql_errno($this->_link);$this->error=mysql_error($this->_link);return
false;}if($H===true){$this->affected_rows=mysql_affected_rows($this->_link);$this->info=mysql_info($this->_link);return
true;}return
new
Min_Result($H);}function
multi_query($G){return$this->_result=$this->query($G);}function
store_result(){return$this->_result;}function
next_result(){return
false;}function
result($G,$m=0){$H=$this->query($G);if(!$H||!$H->num_rows)return
false;return
mysql_result($H->_result,0,$m);}}class
Min_Result{var$num_rows,$_result,$_offset=0;function
__construct($H){$this->_result=$H;$this->num_rows=mysql_num_rows($H);}function
fetch_assoc(){return
mysql_fetch_assoc($this->_result);}function
fetch_row(){return
mysql_fetch_row($this->_result);}function
fetch_field(){$I=mysql_fetch_field($this->_result,$this->_offset++);$I->orgtable=$I->table;$I->orgname=$I->name;$I->charsetnr=($I->blob?63:0);return$I;}function
__destruct(){mysql_free_result($this->_result);}}}elseif(extension_loaded("pdo_mysql")){class
Min_DB
extends
Min_PDO{var$extension="PDO_MySQL";function
connect($M,$V,$F){global$b;$D=array(PDO::MYSQL_ATTR_LOCAL_INFILE=>false);$Gh=$b->connectSsl();if($Gh){if(!empty($Gh['key']))$D[PDO::MYSQL_ATTR_SSL_KEY]=$Gh['key'];if(!empty($Gh['cert']))$D[PDO::MYSQL_ATTR_SSL_CERT]=$Gh['cert'];if(!empty($Gh['ca']))$D[PDO::MYSQL_ATTR_SSL_CA]=$Gh['ca'];}$this->dsn("mysql:charset=utf8;host=".str_replace(":",";unix_socket=",preg_replace('~:(\d)~',';port=\1',$M)),$V,$F,$D);return
true;}function
set_charset($ab){$this->query("SET NAMES $ab");}function
select_db($Sb){return$this->query("USE ".idf_escape($Sb));}function
query($G,$Gi=false){$this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$Gi);return
parent::query($G,$Gi);}}}class
Min_Driver
extends
Min_SQL{function
insert($Q,$N){return($N?parent::insert($Q,$N):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,$K,$qg){$e=array_keys(reset($K));$og="INSERT INTO ".table($Q)." (".implode(", ",$e).") VALUES\n";$Yi=array();foreach($e
as$x)$Yi[$x]="$x = VALUES($x)";$Oh="\nON DUPLICATE KEY UPDATE ".implode(", ",$Yi);$Yi=array();$ze=0;foreach($K
as$N){$Y="(".implode(", ",$N).")";if($Yi&&(strlen($og)+$ze+strlen($Y)+strlen($Oh)>1e6)){if(!queries($og.implode(",\n",$Yi).$Oh))return
false;$Yi=array();$ze=0;}$Yi[]=$Y;$ze+=strlen($Y)+2;}return
queries($og.implode(",\n",$Yi).$Oh);}function
slowQuery($G,$ji){if(min_version('5.7.8','10.1.2')){if(preg_match('~MariaDB~',$this->_conn->server_info))return"SET STATEMENT max_statement_time=$ji FOR $G";elseif(preg_match('~^(SELECT\b)(.+)~is',$G,$B))return"$B[1] /*+ MAX_EXECUTION_TIME(".($ji*1000).") */ $B[2]";}}function
convertSearch($t,$X,$m){return(preg_match('~char|text|enum|set~',$m["type"])&&!preg_match("~^utf8~",$m["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($t USING ".charset($this->_conn).")":$t);}function
warnings(){$H=$this->_conn->query("SHOW WARNINGS");if($H&&$H->num_rows){ob_start();select($H);return
ob_get_clean();}}function
tableHelp($C){$Fe=preg_match('~MariaDB~',$this->_conn->server_info);if(information_schema(DB))return
strtolower(($Fe?"information-schema-$C-table/":str_replace("_","-",$C)."-table.html"));if(DB=="mysql")return($Fe?"mysql$C-table/":"system-database.html");}}function
idf_escape($t){return"`".str_replace("`","``",$t)."`";}function
table($t){return
idf_escape($t);}function
connect(){global$b,$U,$Lh;$f=new
Min_DB;$Lb=$b->credentials();if($f->connect($Lb[0],$Lb[1],$Lb[2])){$f->set_charset(charset($f));$f->query("SET sql_quote_show_create = 1, autocommit = 1");if(min_version('5.7.8',10.2,$f)){$Lh['Strings'][]="json";$U["json"]=4294967295;}return$f;}$I=$f->error;if(function_exists('iconv')&&!is_utf8($I)&&strlen($ch=iconv("windows-1250","utf-8",$I))>strlen($I))$I=$ch;return$I;}function
get_databases($hd){$I=get_session("dbs");if($I===null){$G=(min_version(5)?"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME":"SHOW DATABASES");$I=($hd?slow_query($G):get_vals($G));restart_session();set_session("dbs",$I);stop_session();}return$I;}function
limit($G,$Z,$y,$nf=0,$mh=" "){return" $G$Z".($y!==null?$mh."LIMIT $y".($nf?" OFFSET $nf":""):"");}function
limit1($Q,$G,$Z,$mh="\n"){return
limit($G,$Z,1,0,$mh);}function
db_collation($j,$nb){global$f;$I=null;$h=$f->result("SHOW CREATE DATABASE ".idf_escape($j),1);if(preg_match('~ COLLATE ([^ ]+)~',$h,$B))$I=$B[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$h,$B))$I=$nb[$B[1]][-1];return$I;}function
engines(){$I=array();foreach(get_rows("SHOW ENGINES")as$J){if(preg_match("~YES|DEFAULT~",$J["Support"]))$I[]=$J["Engine"];}return$I;}function
logged_user(){global$f;return$f->result("SELECT USER()");}function
tables_list(){return
get_key_vals(min_version(5)?"SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME":"SHOW TABLES");}function
count_tables($i){$I=array();foreach($i
as$j)$I[$j]=count(get_vals("SHOW TABLES IN ".idf_escape($j)));return$I;}function
table_status($C="",$Wc=false){$I=array();foreach(get_rows($Wc&&min_version(5)?"SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($C!=""?"AND TABLE_NAME = ".q($C):"ORDER BY Name"):"SHOW TABLE STATUS".($C!=""?" LIKE ".q(addcslashes($C,"%_\\")):""))as$J){if($J["Engine"]=="InnoDB")$J["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$J["Comment"]);if(!isset($J["Engine"]))$J["Comment"]="";if($C!="")return$J;$I[$J["Name"]]=$J;}return$I;}function
is_view($R){return$R["Engine"]===null;}function
fk_support($R){return
preg_match('~InnoDB|IBMDB2I~i',$R["Engine"])||(preg_match('~NDB~i',$R["Engine"])&&min_version(5.6));}function
fields($Q){$I=array();foreach(get_rows("SHOW FULL COLUMNS FROM ".table($Q))as$J){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$J["Type"],$B);$I[$J["Field"]]=array("field"=>$J["Field"],"full_type"=>$J["Type"],"type"=>$B[1],"length"=>$B[2],"unsigned"=>ltrim($B[3].$B[4]),"default"=>($J["Default"]!=""||preg_match("~char|set~",$B[1])?(preg_match('~text~',$B[1])?stripslashes(preg_replace("~^'(.*)'\$~",'\1',$J["Default"])):$J["Default"]):null),"null"=>($J["Null"]=="YES"),"auto_increment"=>($J["Extra"]=="auto_increment"),"on_update"=>(preg_match('~^on update (.+)~i',$J["Extra"],$B)?$B[1]:""),"collation"=>$J["Collation"],"privileges"=>array_flip(preg_split('~, *~',$J["Privileges"])),"comment"=>$J["Comment"],"primary"=>($J["Key"]=="PRI"),"generated"=>preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$J["Extra"]),);}return$I;}function
indexes($Q,$g=null){$I=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$g)as$J){$C=$J["Key_name"];$I[$C]["type"]=($C=="PRIMARY"?"PRIMARY":($J["Index_type"]=="FULLTEXT"?"FULLTEXT":($J["Non_unique"]?($J["Index_type"]=="SPATIAL"?"SPATIAL":"INDEX"):"UNIQUE")));$I[$C]["columns"][]=$J["Column_name"];$I[$C]["lengths"][]=($J["Index_type"]=="SPATIAL"?null:$J["Sub_part"]);$I[$C]["descs"][]=null;}return$I;}function
foreign_keys($Q){global$f,$vf;static$fg='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$I=array();$Jb=$f->result("SHOW CREATE TABLE ".table($Q),1);if($Jb){preg_match_all("~CONSTRAINT ($fg) FOREIGN KEY ?\\(((?:$fg,? ?)+)\\) REFERENCES ($fg)(?:\\.($fg))? \\(((?:$fg,? ?)+)\\)(?: ON DELETE ($vf))?(?: ON UPDATE ($vf))?~",$Jb,$Ie,PREG_SET_ORDER);foreach($Ie
as$B){preg_match_all("~$fg~",$B[2],$_h);preg_match_all("~$fg~",$B[5],$bi);$I[idf_unescape($B[1])]=array("db"=>idf_unescape($B[4]!=""?$B[3]:$B[4]),"table"=>idf_unescape($B[4]!=""?$B[4]:$B[3]),"source"=>array_map('idf_unescape',$_h[0]),"target"=>array_map('idf_unescape',$bi[0]),"on_delete"=>($B[6]?$B[6]:"RESTRICT"),"on_update"=>($B[7]?$B[7]:"RESTRICT"),);}}return$I;}function
view($C){global$f;return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',$f->result("SHOW CREATE VIEW ".table($C),1)));}function
collations(){$I=array();foreach(get_rows("SHOW COLLATION")as$J){if($J["Default"])$I[$J["Charset"]][-1]=$J["Collation"];else$I[$J["Charset"]][]=$J["Collation"];}ksort($I);foreach($I
as$x=>$X)asort($I[$x]);return$I;}function
information_schema($j){return(min_version(5)&&$j=="information_schema")||(min_version(5.5)&&$j=="performance_schema");}function
error(){global$f;return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",$f->error));}function
create_database($j,$mb){return
queries("CREATE DATABASE ".idf_escape($j).($mb?" COLLATE ".q($mb):""));}function
drop_databases($i){$I=apply_queries("DROP DATABASE",$i,'idf_escape');restart_session();set_session("dbs",null);return$I;}function
rename_database($C,$mb){$I=false;if(create_database($C,$mb)){$S=array();$dj=array();foreach(tables_list()as$Q=>$T){if($T=='VIEW')$dj[]=$Q;else$S[]=$Q;}$I=(!$S&&!$dj)||move_tables($S,$dj,$C);drop_databases($I?array(DB):array());}return$I;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$u){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$u["columns"],true)){$Ma="";break;}if($u["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($Q,$C,$n,$kd,$tb,$Bc,$mb,$La,$Zf){$c=array();foreach($n
as$m)$c[]=($m[1]?($Q!=""?($m[0]!=""?"CHANGE ".idf_escape($m[0]):"ADD"):" ")." ".implode($m[1]).($Q!=""?$m[2]:""):"DROP ".idf_escape($m[0]));$c=array_merge($c,$kd);$O=($tb!==null?" COMMENT=".q($tb):"").($Bc?" ENGINE=".q($Bc):"").($mb?" COLLATE ".q($mb):"").($La!=""?" AUTO_INCREMENT=$La":"");if($Q=="")return
queries("CREATE TABLE ".table($C)." (\n".implode(",\n",$c)."\n)$O$Zf");if($Q!=$C)$c[]="RENAME TO ".table($C);if($O)$c[]=ltrim($O);return($c||$Zf?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$c).$Zf):true);}function
alter_indexes($Q,$c){foreach($c
as$x=>$X)$c[$x]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$c));}function
truncate_tables($S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views($dj){return
queries("DROP VIEW ".implode(", ",array_map('table',$dj)));}function
drop_tables($S){return
queries("DROP TABLE ".implode(", ",array_map('table',$S)));}function
move_tables($S,$dj,$bi){global$f;$Og=array();foreach($S
as$Q)$Og[]=table($Q)." TO ".idf_escape($bi).".".table($Q);if(!$Og||queries("RENAME TABLE ".implode(", ",$Og))){$dc=array();foreach($dj
as$Q)$dc[table($Q)]=view($Q);$f->select_db($bi);$j=idf_escape(DB);foreach($dc
as$C=>$cj){if(!queries("CREATE VIEW $C AS ".str_replace(" $j."," ",$cj["select"]))||!queries("DROP VIEW $j.$C"))return
false;}return
true;}return
false;}function
copy_tables($S,$dj,$bi){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$C=($bi==DB?table("copy_$Q"):idf_escape($bi).".".table($Q));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $C"))||!queries("CREATE TABLE $C LIKE ".table($Q))||!queries("INSERT INTO $C SELECT * FROM ".table($Q)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J){$Ai=$J["Trigger"];if(!queries("CREATE TRIGGER ".($bi==DB?idf_escape("copy_$Ai"):idf_escape($bi).".".idf_escape($Ai))." $J[Timing] $J[Event] ON $C FOR EACH ROW\n$J[Statement];"))return
false;}}foreach($dj
as$Q){$C=($bi==DB?table("copy_$Q"):idf_escape($bi).".".table($Q));$cj=view($Q);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $C"))||!queries("CREATE VIEW $C AS $cj[select]"))return
false;}return
true;}function
trigger($C){if($C=="")return
array();$K=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($C));return
reset($K);}function
triggers($Q){$I=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$J)$I[$J["Trigger"]]=array($J["Timing"],$J["Event"]);return$I;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
routine($C,$T){global$f,$Dc,$Zd,$U;$Ca=array("bool","boolean","integer","double precision","real","dec","numeric","fixed","national char","national varchar");$Ah="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$Fi="((".implode("|",array_merge(array_keys($U),$Ca)).")\\b(?:\\s*\\(((?:[^'\")]|$Dc)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";$fg="$Ah*(".($T=="FUNCTION"?"":$Zd).")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$Fi";$h=$f->result("SHOW CREATE $T ".idf_escape($C),2);preg_match("~\\(((?:$fg\\s*,?)*)\\)\\s*".($T=="FUNCTION"?"RETURNS\\s+$Fi\\s+":"")."(.*)~is",$h,$B);$n=array();preg_match_all("~$fg\\s*,?~is",$B[1],$Ie,PREG_SET_ORDER);foreach($Ie
as$Tf)$n[]=array("field"=>str_replace("``","`",$Tf[2]).$Tf[3],"type"=>strtolower($Tf[5]),"length"=>preg_replace_callback("~$Dc~s",'normalize_enum',$Tf[6]),"unsigned"=>strtolower(preg_replace('~\s+~',' ',trim("$Tf[8] $Tf[7]"))),"null"=>1,"full_type"=>$Tf[4],"inout"=>strtoupper($Tf[1]),"collation"=>strtolower($Tf[9]),);if($T!="FUNCTION")return
array("fields"=>$n,"definition"=>$B[11]);return
array("fields"=>$n,"returns"=>array("type"=>$B[12],"length"=>$B[13],"unsigned"=>$B[15],"collation"=>$B[16]),"definition"=>$B[17],"language"=>"SQL",);}function
routines(){return
get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ".q(DB));}function
routine_languages(){return
array();}function
routine_id($C,$J){return
idf_escape($C);}function
last_id(){global$f;return$f->result("SELECT LAST_INSERT_ID()");}function
explain($f,$G){return$f->query("EXPLAIN ".(min_version(5.1)&&!min_version(5.7)?"PARTITIONS ":"").$G);}function
found_rows($R,$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
types(){return
array();}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($eh,$g=null){return
true;}function
create_sql($Q,$La,$Mh){global$f;$I=$f->result("SHOW CREATE TABLE ".table($Q),1);if(!$La)$I=preg_replace('~ AUTO_INCREMENT=\d+~','',$I);return$I;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($Sb){return"USE ".idf_escape($Sb);}function
trigger_sql($Q){$I="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$J)$I.="\nCREATE TRIGGER ".idf_escape($J["Trigger"])." $J[Timing] $J[Event] ON ".table($J["Table"])." FOR EACH ROW\n$J[Statement];;\n";return$I;}function
show_variables(){return
get_key_vals("SHOW VARIABLES");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
show_status(){return
get_key_vals("SHOW STATUS");}function
convert_field($m){if(preg_match("~binary~",$m["type"]))return"HEX(".idf_escape($m["field"]).")";if($m["type"]=="bit")return"BIN(".idf_escape($m["field"])." + 0)";if(preg_match("~geometry|point|linestring|polygon~",$m["type"]))return(min_version(8)?"ST_":"")."AsWKT(".idf_escape($m["field"]).")";}function
unconvert_field($m,$I){if(preg_match("~binary~",$m["type"]??null))$I="UNHEX($I)";if(isset($m["type"])&&$m["type"]=="bit")$I="CONV($I, 2, 10) + 0";if(preg_match("~geometry|point|linestring|polygon~",$m["type"]??null)){$og=(min_version(8)?"ST_":"");$I=$og."GeomFromText($I, $og"."SRID($m[field]))";}return$I;}function
support($Xc){return!preg_match("~scheme|sequence|type|view_trigger|materializedview".(min_version(8)?"":"|descidx".(min_version(5.1)?"":"|event|partitioning".(min_version(5)?"":"|routine|trigger|view")))."~",$Xc);}function
kill_process($X){return
queries("KILL ".number($X));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){global$f;return$f->result("SELECT @@max_connections");}function
driver_config(){$U=array();$Lh=array();foreach(array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),)as$x=>$X){$U+=$X;$Lh[$x]=array_keys($X);}return
array('possible_drivers'=>array("MySQLi","MySQL","PDO_MySQL"),'jush'=>"sql",'types'=>$U,'structured_types'=>$Lh,'unsigned'=>array("unsigned","zerofill","unsigned zerofill"),'operators'=>array("=","<",">","<=",">=","!=","LIKE","LIKE %%","REGEXP","IN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL"),'functions'=>array("char_length","date","distinct","from_unixtime","unix_timestamp","lower","round","floor","ceil","sec_to_time","time_to_sec","upper"),'operator_regexp'=>'REGEXP','grouping'=>array("avg","count","count distinct","group_concat","max","min","sum"),'edit_functions'=>array(array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",),array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",)),);}}$xb=driver_config();$ng=$xb['possible_drivers'];$w=$xb['jush'];$U=$xb['types'];$Lh=$xb['structured_types'];$Mi=$xb['unsigned'];$Af=$xb['operators'];$_f=isset($xb['operator_regexp'])&&in_array($xb['operator_regexp'],$Af)?$xb['operator_regexp']:null;$sd=$xb['functions'];$zd=$xb['grouping'];$uc=$xb['edit_functions'];if($b->operators===null){$b->operators=$Af;$b->operator_regexp=$_f;}define("SERVER",$_GET[DRIVER]);define("DB",$_GET["db"]);define("ME",preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').(SERVER!==null?DRIVER."=".urlencode(SERVER).'&':'').(isset($_GET["username"])?"username=".urlencode($_GET["username"]).'&':'').(DB!=""?'db='.urlencode(DB).'&'.(isset($_GET["ns"])?"ns=".urlencode($_GET["ns"])."&":""):''));$ia="4.8.4";function
page_header($li,$l="",$Va=array(),$mi=""){global$ca,$ia,$b,$mc,$w;page_headers();if(is_ajax()&&$l){page_messages($l);exit;}$ni=$li.($mi!=""?": $mi":"");$oi=strip_tags($ni.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".$b->name());echo'<!DOCTYPE html>
<html lang="en" dir="ltr">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<title>',$oi,'</title>
<link rel="stylesheet" type="text/css" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=4.8.4"),'">
',script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=4.8.4");if($b->head()){echo'<link rel="shortcut icon" type="image/x-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.8.4"),'">
<link rel="apple-touch-icon" href="',h(preg_replace("~\\?.*~","",ME)."?file=favicon.ico&version=4.8.4"),'">
';foreach($b->css()as$Nb){echo'<link rel="stylesheet" type="text/css" href="',h($Nb),'">
';}}echo'
<body class="ltr nojs adminer">
';$o=get_temp_dir()."/adminer.version";if(!$_COOKIE["adminer_version"]&&file_exists($o)&&filemtime($o)+86400>time()){$bj=unserialize(file_get_contents($o));$_COOKIE["adminer_version"]=$bj["version"];}echo'<script',nonce(),'>
mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick',(isset($_COOKIE["adminer_version"])?"":", onload: partial(verifyVersion, '$ia', '".js_escape(ME)."', '".get_token()."')");?>});
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo
js_escape('You are offline.'),'\';
var thousandsSeparator = \'',js_escape(','),'\';
</script>

<div id="help" class="jush-',$w,' jsonly hidden"></div>
',script("mixin(qs('#help'), {onmouseover: function () { helpOpen = 1; }, onmouseout: helpMouseout});"),'
<div id="content">
';if($Va!==null){$z=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($z?$z:".").'">'.$mc[DRIVER].'</a> &raquo; ';$z=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$M=$b->serverName(SERVER);$M=($M!=""?$M:'Server');if($Va===false)echo"$M\n";else{echo"<a href='".h($z)."' accesskey='1' title='Alt+Shift+1'>$M</a> &raquo; ";if($_GET["ns"]!=""||(DB!=""&&is_array($Va)))echo'<a href="'.h($z."&db=".urlencode(DB).(support("scheme")?"&ns=":"")).'">'.h(DB).'</a> &raquo; ';if(is_array($Va)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1)).'">'.h($_GET["ns"]).'</a> &raquo; ';foreach($Va
as$x=>$X){$fc=(is_array($X)?$X[1]:h($X));if($fc!="")echo"<a href='".h(ME."$x=").urlencode(is_array($X)?$X[0]:$X)."'>$fc</a> &raquo; ";}}echo"$li\n";}}echo"<h2>$ni</h2>\n","<div id='ajaxstatus' class='jsonly hidden'></div>\n";restart_session();page_messages($l);$i=&get_session("dbs");if(DB!=""&&$i&&!in_array(DB,$i,true))$i=null;stop_session();define("PAGE_HEADER",1);}function
page_headers(){global$b;header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach($b->csp()as$Mb){$Ed=array();foreach($Mb
as$x=>$X)$Ed[]="$x $X";header("Content-Security-Policy: ".implode("; ",$Ed));}$b->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://api.github.com/repos/adminerevo/adminerevo/releases/latest","frame-src"=>"'self'","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
get_nonce(){static$hf;if(!$hf)$hf=base64_encode(rand_string());return$hf;}function
page_messages($l){$Oi=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$Ue=[];if(isset($_SESSION["messages"][$Oi]))$Ue=$_SESSION["messages"][$Oi];if(count($Ue)>0){echo"<div class='message'>".implode("</div>\n<div class='message'>",$Ue)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Oi]);}if($l)echo"<div class='error'>$l</div>\n";}function
page_footer($Xe=""){global$b,$si;echo'</div>

';if($Xe!="auth"){echo'<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="Logout" id="logout">
<input type="hidden" name="token" value="',$si,'">
</p>
</form>
';}echo'<div id="menu">
';$b->navigation($Xe);echo'</div>
',script("setupSubmitHighlight(document);"),script("setupCopyToClipboard(document);"),"</body>\n</html>";}function
int32($af){while($af>=2147483648)$af-=4294967296;while($af<=-2147483649)$af+=4294967296;return(int)$af;}function
long2str($W,$fj){$ch='';foreach($W
as$X)$ch.=pack('V',$X);if($fj)return
substr($ch,0,end($W));return$ch;}function
str2long($ch,$fj){$W=array_values(unpack('V*',str_pad($ch,4*ceil(strlen($ch)/4),"\0")));if($fj)$W[]=strlen($ch);return$W;}function
xxtea_mx($rj,$qj,$Ph,$le){return
int32((($rj>>5&0x7FFFFFF)^$qj<<2)+(($qj>>3&0x1FFFFFFF)^$rj<<4))^int32(($Ph^$qj)+($le^$rj));}function
encrypt_string($Kh,$x){if($Kh=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Kh,true);$af=count($W)-1;$rj=$W[$af];$qj=$W[0];$zg=floor(6+52/($af+1));$Ph=0;while($zg-->0){$Ph=int32($Ph+0x9E3779B9);$tc=$Ph>>2&3;for($Rf=0;$Rf<$af;$Rf++){$qj=$W[$Rf+1];$Ze=xxtea_mx($rj,$qj,$Ph,$x[$Rf&3^$tc]);$rj=int32($W[$Rf]+$Ze);$W[$Rf]=$rj;}$qj=$W[0];$Ze=xxtea_mx($rj,$qj,$Ph,$x[$Rf&3^$tc]);$rj=int32($W[$af]+$Ze);$W[$af]=$rj;}return
long2str($W,false);}function
decrypt_string($Kh,$x){if($Kh=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($Kh,false);$af=count($W)-1;$rj=$W[$af];$qj=$W[0];$zg=floor(6+52/($af+1));$Ph=int32($zg*0x9E3779B9);while($Ph){$tc=$Ph>>2&3;for($Rf=$af;$Rf>0;$Rf--){$rj=$W[$Rf-1];$Ze=xxtea_mx($rj,$qj,$Ph,$x[$Rf&3^$tc]);$qj=int32($W[$Rf]-$Ze);$W[$Rf]=$qj;}$rj=$W[$af];$Ze=xxtea_mx($rj,$qj,$Ph,$x[$Rf&3^$tc]);$qj=int32($W[0]-$Ze);$W[0]=$qj;$Ph=int32($Ph-0x9E3779B9);}return
long2str($W,true);}$f='';$Dd=$_SESSION["token"];if(!$Dd)$_SESSION["token"]=rand(1,1e6);$si=get_token();$hg=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$hg[$x]=$X;}}function
validate_server_input(){if(SERVER=="")return;$bg=parse_url(SERVER);if(!$bg)auth_error('Invalid server or credentials.');if(isset($bg['user'])||isset($bg['pass'])||isset($bg['query'])||isset($bg['fragment']))auth_error('Invalid server or credentials.');if(isset($bg['scheme'])&&!preg_match('~^(https?)$~i',$bg['scheme']))auth_error('Invalid server or credentials.');$Jd=(isset($bg['host'])?$bg['host']:'').(isset($bg['path'])?$bg['path']:'');if(strpos(rtrim($Jd,'/'),'/')!==false)auth_error('Invalid server or credentials.');if(isset($bg['port'])&&($bg['port']<1024||$bg['port']>65535))auth_error('Connecting to privileged ports is not allowed.');}function
build_http_url($M,$V,$F,$ac,$Zb=null){if(!preg_match('~^(https?://)?([^:]*)(:\d+)?$~',rtrim($M,'/'),$Ie)){$this->error='Invalid server or credentials.';return
false;}return($Ie[1]?:"http://").($V!==""||$F!==""?"$V:$F@":"").($Ie[2]!==""?$Ie[2]:$ac).(isset($Ie[3])?$Ie[3]:($Zb?":$Zb":""));}function
add_invalid_login(){global$b;$qd=file_open_lock(get_temp_dir()."/adminer.invalid");if(!$qd)return;$ee=unserialize(stream_get_contents($qd));$ii=time();if($ee){foreach($ee
as$fe=>$X){if($X[0]<$ii)unset($ee[$fe]);}}$de=&$ee[$b->bruteForceKey()];if(!$de)$de=array($ii+30*60,0);$de[1]++;file_write_unlock($qd,serialize($ee));}function
check_invalid_login(){global$b;$ee=unserialize(@file_get_contents(get_temp_dir()."/adminer.invalid"));$de=($ee?$ee[$b->bruteForceKey()]:array());if($de===null)return;$gf=($de[1]>29?$de[0]-time():0);if($gf>0)auth_error(lang(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($gf/60)));}$Ja=$_POST["auth"];if($Ja){session_regenerate_id();$aj=$Ja["driver"];$M=trim($Ja["server"]);$V=$Ja["username"];$F=(string)$Ja["password"];$j=$Ja["db"];set_password($aj,$M,$V,$F);$_SESSION["db"][$aj][$M][$V][$j]=true;if($Ja["permanent"]){$x=base64_encode($aj)."-".base64_encode($M)."-".base64_encode($V)."-".base64_encode($j);$tg=$b->permanentLogin(true);$hg[$x]="$x:".base64_encode($tg?encrypt_string($F,$tg):"");cookie("adminer_permanent",implode(" ",$hg));}if(count($_POST)==1||DRIVER!=$aj||SERVER!=$M||$_GET["username"]!==$V||DB!=$j)redirect(auth_url($aj,$M,$V,$j));}elseif($_POST["logout"]&&(!$Dd||verify_token())){foreach(array("pwds","db","dbs","queries")as$x)set_session($x,null);unset_permanent();redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.');}elseif($hg&&!$_SESSION["pwds"]){session_regenerate_id();$tg=$b->permanentLogin();foreach($hg
as$x=>$X){list(,$gb)=explode(":",$X);list($aj,$M,$V,$j)=array_map('base64_decode',explode("-",$x));set_password($aj,$M,$V,decrypt_string(base64_decode($gb),$tg));$_SESSION["db"][$aj][$M][$V][$j]=true;}}function
unset_permanent(){global$hg;foreach($hg
as$x=>$X){list($aj,$M,$V,$j)=array_map('base64_decode',explode("-",$x));if($aj==DRIVER&&$M==SERVER&&$V==$_GET["username"]&&$j==DB)unset($hg[$x]);}cookie("adminer_permanent",implode(" ",$hg));}function
auth_error($l){global$b,$Dd;$rh=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$rh]||$_GET[$rh])&&!$Dd)$l='Session expired, please login again.';else{restart_session();add_invalid_login();$F=get_password();if($F!==null){if($F===false)$l.=($l?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);}unset_permanent();}}if(!$_COOKIE[$rh]&&$_GET[$rh]&&ini_bool("session.use_only_cookies"))$l='Session support must be enabled.';$Uf=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?$_COOKIE["adminer_key"]:rand_string()),$Uf["lifetime"]);page_header('Login',$l,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo"</div>\n";$b->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists("Min_DB")){unset($_SESSION["pwds"][DRIVER]);unset_permanent();page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",$ng)),false);page_footer("auth");exit;}stop_session(true);if(isset($_GET["username"])&&is_string(get_password())){validate_server_input();check_invalid_login();$f=connect();$k=new
Min_Driver($f);}$Ce=null;if(!is_object($f)||($Ce=$b->login($_GET["username"],get_password()))!==true){$l=(is_string($f)?h($f):(is_string($Ce)?$Ce:'Invalid server or credentials.'));auth_error($l.(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the input password which might be the cause.':''));}if($_POST["logout"]&&$Dd&&!verify_token()){page_header('Logout','Invalid CSRF token. Send the form again.');page_footer("db");exit;}if($Ja&&$_POST["token"])$_POST["token"]=$si;$l='';if($_POST){if(!verify_token()){$Yd="max_input_vars";$Oe=ini_get($Yd);if(extension_loaded("suhosin")){foreach(array("suhosin.request.max_vars","suhosin.post.max_vars")as$x){$X=ini_get($x);if($X&&(!$Oe||$X<$Oe)){$Yd=$x;$Oe=$X;}}}$l=(!$_POST["token"]&&$Oe?sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"'$Yd'"):'Invalid CSRF token. Send the form again.'.' '.'If you did not send this request from Adminer then close this page.');}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){$l=sprintf('Too big POST data. Reduce the data or increase the %s configuration directive.',"'post_max_size'");if(isset($_GET["sql"]))$l.=' '.'You can upload a big SQL file via FTP and import it from server.';}function
select($H,$g=null,$Hf=array(),$y=0){global$w;$_=array();$v=array();$e=array();$Ta=array();$U=array();$I=array();odd('');for($r=0;(!$y||$r<$y)&&($J=$H->fetch_row());$r++){if(!$r){echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap'>\n","<thead><tr>";for($ke=0;$ke<count($J);$ke++){$m=$H->fetch_field();$C=$m->name;$Gf=$m->orgtable;$Ff=$m->orgname;$I[$m->table]=$Gf;if($Hf&&$w=="sql")$_[$ke]=($C=="table"?"table=":($C=="possible_keys"?"indexes=":null));elseif($Gf!=""){if(!isset($v[$Gf])){$v[$Gf]=array();foreach(indexes($Gf,$g)as$u){if($u["type"]=="PRIMARY"){$v[$Gf]=array_flip($u["columns"]);break;}}$e[$Gf]=$v[$Gf];}if(isset($e[$Gf][$Ff])){unset($e[$Gf][$Ff]);$v[$Gf][$Ff]=$ke;$_[$ke]=$Gf;}}if($m->charsetnr==63)$Ta[$ke]=true;$U[$ke]=$m->type;echo"<th".($Gf!=""||$m->name!=$Ff?" title='".h(($Gf!=""?"$Gf.":"").$Ff)."'":"").">".h($C).($Hf?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($C),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}echo"</thead>\n";}echo"<tr".odd().">";foreach($J
as$x=>$X){$z="";if(isset($_[$x])&&!$e[$_[$x]]){if($Hf&&$w=="sql"){$Q=$J[array_search("table=",$_)];$z=ME.$_[$x].urlencode($Hf[$Q]!=""?$Hf[$Q]:$Q);}else{$z=ME."edit=".urlencode($_[$x]);foreach($v[$_[$x]]as$kb=>$ke)$z.="&where".urlencode("[".bracket_escape($kb)."]")."=".urlencode($J[$ke]);}}elseif(is_url($X))$z=$X;if($X===null)$X="<i>NULL</i>";elseif($Ta[$x]&&!is_utf8($X))$X="<i>".lang(array('%d byte','%d bytes'),strlen($X))."</i>";else{$X=h($X);if($U[$x]==254)$X="<code>$X</code>";}if($z)$X="<a href='".h($z)."'".(is_url($z)?target_blank():'').">$X</a>";echo"<td>$X";}}echo($r?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$I;}function
referencable_primary($kh){$I=array();foreach(table_status('',true)as$Th=>$Q){if($Th!=$kh&&fk_support($Q)){foreach(fields($Th)as$m){if($m["primary"]){if($I[$Th]){unset($I[$Th]);break;}$I[$Th]=$m;}}}}return$I;}function
adminer_settings(){parse_str($_COOKIE["adminer_settings"],$th);return$th;}function
adminer_setting($x){$th=adminer_settings();return$th[$x];}function
set_adminer_settings($th){return
cookie("adminer_settings",http_build_query($th+adminer_settings()));}function
textarea($C,$Y,$K=10,$pb=80){global$w;echo"<textarea name='".h($C)."' rows='$K' cols='$pb' class='sqlarea jush-$w' spellcheck='false' wrap='off'>";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
edit_type($x,$m,$nb,$md=array(),$Tc=array()){global$Lh,$U,$Mi,$vf;$T=$m["type"];echo'<td><select name="',h($x),'[type]" class="type" aria-labelledby="label-type">';if($T&&!isset($U[$T])&&!isset($md[$T])&&!in_array($T,$Tc))$Tc[]=$T;if($md)$Lh['Foreign keys']=$md;echo
optionlist(array_merge($Tc,$Lh),$T),'</select><td><input name="',h($x),'[length]" value="',h($m["length"]),'" size="3"',(!$m["length"]&&preg_match('~var(char|binary)$~',$T)?" class='required'":"");echo' aria-labelledby="label-length"><td class="options">',"<select name='".h($x)."[collation]'".(preg_match('~(char|text|enum|set)$~',$T)?"":" class='hidden'").'><option value="">('.'collation'.')'.optionlist($nb,$m["collation"]).'</select>',($Mi?"<select name='".h($x)."[unsigned]'".(!$T||preg_match(number_type(),$T)?"":" class='hidden'").'><option>'.optionlist($Mi,$m["unsigned"]).'</select>':''),(isset($m['on_update'])?"<select name='".h($x)."[on_update]'".(preg_match('~timestamp|datetime~',$T)?"":" class='hidden'").'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$m["on_update"])?"CURRENT_TIMESTAMP":$m["on_update"])).'</select>':''),($md?"<select name='".h($x)."[on_delete]'".(preg_match("~`~",$T)?"":" class='hidden'")."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",$vf),$m["on_delete"])."</select> ":" ");}function
process_length($ze){global$Dc;return(preg_match("~^\\s*\\(?\\s*$Dc(?:\\s*,\\s*$Dc)*+\\s*\\)?\\s*\$~",$ze)&&preg_match_all("~$Dc~",$ze,$Ie)?"(".implode(",",$Ie[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$ze)));}function
process_type($m,$lb="COLLATE"){global$Mi;if(DRIVER==='server'&&($m['unsigned']==='unsigned'||stripos((string)$m['type'],'int')!==false)&&min_version(8))$m["length"]='';return" $m[type]".process_length($m["length"]).(preg_match(number_type(),$m["type"])&&in_array($m["unsigned"],$Mi)?" $m[unsigned]":"").(preg_match('~char|text|enum|set~',$m["type"])&&$m["collation"]?" $lb ".q($m["collation"]):"");}function
process_field($m,$Ei){return
array(idf_escape(trim($m["field"])),process_type($Ei),($m["null"]?" NULL":" NOT NULL"),default_value($m),(preg_match('~timestamp|datetime~',$m["type"])&&$m["on_update"]?" ON UPDATE $m[on_update]":""),(support("comment")&&$m["comment"]!=""?" COMMENT ".q($m["comment"]):""),($m["auto_increment"]?auto_increment():null),);}function
default_value($m){$Yb=$m["default"];return($Yb===null?"":" DEFAULT ".(preg_match('~char|binary|text|enum|set~',$m["type"])||preg_match('~^(?![a-z])~i',$Yb)?q($Yb):$Yb));}function
type_class($T){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$x=>$X){if(preg_match("~$x|$X~",$T))return" class='$x'";}}function
edit_fields($n,$nb,$T="TABLE",$md=array()){global$Zd;$n=array_values($n);$bc=(($_POST?$_POST["defaults"]:adminer_setting("defaults"))?"":" class='hidden'");$ub=(($_POST?$_POST["comments"]:adminer_setting("comments"))?"":" class='hidden'");echo'<thead><tr>
';if($T=="PROCEDURE"){echo'<td>';}echo'<th id="label-name">',($T=="TABLE"?'Column name':'Parameter name'),'<td id="label-type">Type<textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;"></textarea>',script("qs('#enum-edit').onblur = editingLengthBlur;"),'<td id="label-length">Length
<td>','Options';if($T=="TABLE"){echo'<td id="label-null">NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym id="label-ai" title="Auto Increment">AI</acronym>',doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype.html#DATATYPE-SERIAL",'mssql'=>"ms186775.aspx",)),'<td id="label-default"',$bc,'>Default value
',(support("comment")?"<td id='label-comment'$ub>".'Comment':"");}echo'<td>',"<input type='image' class='icon' name='add[".(support("move_col")?0:count($n))."]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.8.4")."' alt='+' title='".'Add next'."'>".script("row_count = ".count($n).";"),'</thead>
<tbody>
',script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");foreach($n
as$r=>$m){$r++;$If=$m[($_POST?"orig":"field")];$jc=(isset($_POST["add"][$r-1])||(isset($m["field"])&&isset($_POST["drop_col"][$r])===false))&&(support("drop_col")||$If=="");echo'<tr',($jc?"":" style='display: none;'"),'>
',($T=="PROCEDURE"?"<td>".html_select("fields[$r][inout]",explode("|",$Zd),$m["inout"]):""),'<th>';if($jc){echo'<input name="fields[',$r,'][field]" value="',h($m["field"]),'" data-maxlength="64" autocapitalize="off" aria-labelledby="label-name">';}echo'<input type="hidden" name="fields[',$r,'][orig]" value="',h($If),'">';edit_type("fields[$r]",$m,$nb,$md);if($T=="TABLE"){echo'<td>',checkbox("fields[$r][null]",1,$m["null"],"","","block","label-null"),'<td><label class="block"><input type="radio" name="auto_increment_col" value="',$r,'"';if($m["auto_increment"]){echo' checked';}echo' aria-labelledby="label-ai"></label><td',$bc,'>',checkbox("fields[$r][has_default]",1,$m["has_default"],"","","","label-default"),'<input name="fields[',$r,'][default]" value="',h($m["default"]),'" aria-labelledby="label-default">',(support("comment")?"<td$ub><input name='fields[$r][comment]' value='".h($m["comment"])."' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'>":"");}echo"<td>",(support("move_col")?"<input type='image' class='icon' name='add[$r]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.8.4")."' alt='+' title='".'Add next'."'> "."<input type='image' class='icon' name='up[$r]' src='".h(preg_replace("~\\?.*~","",ME)."?file=up.gif&version=4.8.4")."' alt='â†‘' title='".'Move up'."'> "."<input type='image' class='icon' name='down[$r]' src='".h(preg_replace("~\\?.*~","",ME)."?file=down.gif&version=4.8.4")."' alt='â†“' title='".'Move down'."'> ":""),($If==""||support("drop_col")?"<input type='image' class='icon' name='drop_col[$r]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' alt='x' title='".'Remove'."'>":"");}}function
process_fields(&$n){$nf=0;if($_POST["up"]){$te=0;foreach($n
as$x=>$m){if(key($_POST["up"])==$x){unset($n[$x]);array_splice($n,$te,0,array($m));break;}if(isset($m["field"]))$te=$nf;$nf++;}}elseif($_POST["down"]){$od=false;foreach($n
as$x=>$m){if(isset($m["field"])&&$od){unset($n[key($_POST["down"])]);array_splice($n,$nf,0,array($od));break;}if(key($_POST["down"])==$x)$od=$m;$nf++;}}elseif($_POST["add"]){$n=array_values($n);array_splice($n,key($_POST["add"]),0,array(array()));}elseif(!$_POST["drop_col"])return
false;return
true;}function
normalize_enum($B){return"'".str_replace("'","''",addcslashes(stripcslashes(str_replace($B[0][0].$B[0][0],$B[0][0],substr($B[0],1,-1))),'\\'))."'";}function
grant($ud,$vg,$e,$uf){if(!$vg)return
true;if($vg==array("ALL PRIVILEGES","GRANT OPTION"))return($ud=="GRANT"?queries("$ud ALL PRIVILEGES$uf WITH GRANT OPTION"):queries("$ud ALL PRIVILEGES$uf")&&queries("$ud GRANT OPTION$uf"));return
queries("$ud ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$vg).$e).$uf);}function
drop_create($nc,$h,$oc,$fi,$qc,$A,$Te,$Re,$Se,$rf,$ef){if($_POST["drop"])query_redirect($nc,$A,$Te);elseif($rf=="")query_redirect($h,$A,$Se);elseif($rf!=$ef){$Kb=queries($h);queries_redirect($A,$Re,$Kb&&queries($nc));if($Kb)queries($oc);}else
queries_redirect($A,$Re,queries($fi)&&queries($qc)&&queries($nc)&&queries($h));}function
create_trigger($uf,$J){global$w;$ki=" $J[Timing] $J[Event]".(preg_match('~ OF~',$J["Event"])?" $J[Of]":"");return"CREATE TRIGGER ".idf_escape($J["Trigger"]).($w=="mssql"?$uf.$ki:$ki.$uf).rtrim(" $J[Type]\n$J[Statement]",";").";";}function
create_routine($Yg,$J){global$Zd,$w;$N=array();$n=(array)$J["fields"];ksort($n);foreach($n
as$m){if($m["field"]!="")$N[]=(preg_match("~^($Zd)\$~",$m["inout"])?"$m[inout] ":"").idf_escape($m["field"]).process_type($m,"CHARACTER SET");}$cc=rtrim("\n$J[definition]",";");return"CREATE $Yg ".idf_escape(trim($J["name"]))." (".implode(", ",$N).")".(isset($_GET["function"])?" RETURNS".process_type($J["returns"],"CHARACTER SET"):"").($J["language"]?" LANGUAGE $J[language]":"").($w=="pgsql"?" AS ".q($cc):"$cc;");}function
remove_definer($G){return
preg_replace('~^([A-Z =]+) DEFINER=`'.preg_replace('~@(.*)~','`@`(%|\1)',logged_user()).'`~','\1',$G);}function
format_foreign_key($p){global$vf;$j=$p["db"];$if=$p["ns"];return" FOREIGN KEY (".implode(", ",array_map('idf_escape',$p["source"])).") REFERENCES ".($j!=""&&$j!=$_GET["db"]?idf_escape($j).".":"").($if!=""&&$if!=$_GET["ns"]?idf_escape($if).".":"").table($p["table"])." (".implode(", ",array_map('idf_escape',$p["target"])).")".(preg_match("~^($vf)\$~",$p["on_delete"])?" ON DELETE $p[on_delete]":"").(preg_match("~^($vf)\$~",$p["on_update"])?" ON UPDATE $p[on_update]":"");}function
tar_file($o,$pi){$I=pack("a100a8a8a8a12a12",$o,644,0,0,decoct($pi->size),decoct(time()));$fb=8*32;for($r=0;$r<strlen($I);$r++)$fb+=ord($I[$r]);$I.=sprintf("%06o",$fb)."\0 ";echo$I,str_repeat("\0",512-strlen($I));$pi->send();echo
str_repeat("\0",511-($pi->size+511)%512);}function
ini_bytes($Yd){$X=ini_get($Yd);switch(strtolower(substr($X,-1))){case'g':$X*=1024;case'm':$X*=1024;case'k':$X*=1024;}return$X;}function
doc_link($eg,$gi="<sup>?</sup>"){global$w,$f;$ph=$f->server_info;$bj=preg_replace('~^(\d\.?\d).*~s','\1',$ph);$Qi=array('sql'=>"https://dev.mysql.com/doc/refman/$bj/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/$bj/",'mssql'=>"https://msdn.microsoft.com/library/",'oracle'=>"https://www.oracle.com/pls/topic/lookup?ctx=db".preg_replace('~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s','\1\2',$ph)."&id=",);if(preg_match('~MariaDB~',$ph)){$Qi['sql']="https://mariadb.com/kb/en/library/";$eg['sql']=(isset($eg['mariadb'])?$eg['mariadb']:str_replace(".html","/",$eg['sql']));}return($eg[$w]?"<a href='".h($Qi[$w].$eg[$w])."'".target_blank().">$gi</a>":"");}function
ob_gzencode($P){return
gzencode($P);}function
db_size($j){global$f;if(!$f->select_db($j))return"?";$I=0;foreach(table_status()as$R)$I+=$R["Data_length"]+$R["Index_length"];return
format_number($I);}function
set_utf8mb4($h){global$f;static$N=false;if(!$N&&preg_match('~\butf8mb4~i',$h)){$N=true;echo"SET NAMES ".charset($f).";\n\n";}}function
connect_error(){global$b,$f,$si,$l,$mc;if(DB!=""){header("HTTP/1.1 404 Not Found");page_header('Database'.": ".h(DB),'Invalid database.',true);}else{if($_POST["db"]&&!$l)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$l,false);$wa=['database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',];$_=[];foreach($wa
as$x=>$X){if(support($x))$_[]="<a href='".h(ME)."$x='>$X</a>";}echo
generate_linksbar($_),"<p>".sprintf('%s version: %s through PHP extension %s',$mc[DRIVER],"<b>".h($f->server_info)."</b>","<b>$f->extension</b>")."\n","<p>".sprintf('Logged as: %s',"<b>".h(logged_user())."</b>")."\n";$i=$b->databases();if($i){$fh=support("scheme");$nb=collations();echo"<form action='' method='post'>\n","<table cellspacing='0' class='checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),"<thead><tr>".(support("database")?"<td>":"")."<th>".'Database'." - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>"."<td>".'Collation'."<td>".'Tables'."<td>".'Size'." - <a href='".h(ME)."dbsize=1'>".'Compute'."</a>".script("qsl('a').onclick = partial(ajaxSetHtml, '".js_escape(ME)."script=connect');","")."</thead>\n";$i=($_GET["dbsize"]?count_tables($i):array_flip($i));foreach($i
as$j=>$S){$Xg=h(ME)."db=".urlencode($j);$s=h("Db-".$j);echo"<tr".odd().">".(support("database")?"<td>".checkbox("db[]",$j,in_array($j,(array)$_POST["db"]),"","","",$s):""),"<th><a href='$Xg' id='$s'>".h($j)."</a>";$mb=h(db_collation($j,$nb));echo"<td>".(support("database")?"<a href='$Xg".($fh?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$mb</a>":$mb),"<td align='right'><a href='$Xg&amp;schema=' id='tables-".h($j)."' title='".'Database schema'."'>".($_GET["dbsize"]?$S:"?")."</a>","<td align='right' id='size-".h($j)."'>".($_GET["dbsize"]?db_size($j):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''>".script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };")."<input type='submit' name='drop' value='".'Drop'."'>".confirm()."\n"."</div></fieldset>\n"."</div></div>\n":""),"<input type='hidden' name='token' value='$si'>\n","</form>\n",script("tableCheck();");}}page_footer("db");}if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];if(!(DB!=""?$f->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}connect_error();exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~ns=[^&]*&~','',ME)."ns=".get_schema());if(!set_schema($_GET["ns"])){header("HTTP/1.1 404 Not Found");page_header('Schema'.": ".h($_GET["ns"]),'Invalid schema.',true);page_footer("ns");exit;}}}$vf="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";class
TmpFile{var$handler;var$size;function
__construct(){$this->handler=tmpfile();}function
write($Db){$this->size+=strlen($Db);fwrite($this->handler,$Db);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}$Dc="'(?:''|[^'\\\\]|\\\\.)*'";$Zd="IN|OUT|INOUT";if(isset($_GET["select"])&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$n=fields($a);header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$_GET["where"])).".".friendly_url($_GET["field"]));$L=array(idf_escape($_GET["field"]));$H=$k->select($a,$L,array(where($_GET,$n)),$L);$J=($H?$H->fetch_row():array());echo$k->value($J[0],$n[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$n=fields($a);if(!$n)$l=error();$R=table_status1($a,true);$C=$b->tableName($R);page_header(($n&&is_view($R)?$R['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($C!=""?$C:h($a)),$l);$b->selectLinks($R);$tb=$R["Comment"];if($tb!="")echo"<p class='nowrap'>".'Comment'.": ".h($tb)."\n";if($n)$b->tableStructurePrint($n);if(!is_view($R)){if(support("indexes")){echo"<h3 id='indexes'>".'Indexes'."</h3>\n";$v=indexes($a);if($v)$b->tableIndexesPrint($v);echo'<p class="links"><a href="'.h(ME).'indexes='.urlencode($a).'">'.'Alter indexes'."</a>\n";}if(fk_support($R)){echo"<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$md=foreign_keys($a);if($md){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Source'."<td>".'Target'."<td>".'ON DELETE'."<td>".'ON UPDATE'."<td></thead>\n";foreach($md
as$C=>$p){echo"<tr title='".h($C)."'>","<th><i>".implode("</i>, <i>",array_map('h',$p["source"]))."</i>","<td><a href='".h($p["db"]!=""?preg_replace('~db=[^&]*~',"db=".urlencode($p["db"]),ME):($p["ns"]!=""?preg_replace('~ns=[^&]*~',"ns=".urlencode($p["ns"]),ME):ME))."table=".urlencode($p["table"])."'>".($p["db"]!=""?"<b>".h($p["db"])."</b>.":"").($p["ns"]!=""?"<b>".h($p["ns"])."</b>.":"").h($p["table"])."</a>","(<i>".implode("</i>, <i>",array_map('h',$p["target"]))."</i>)","<td>".h($p["on_delete"])."\n","<td>".h($p["on_update"])."\n",'<td><a href="'.h(ME.'foreign='.urlencode($a).'&name='.urlencode($C)).'">'.'Alter'.'</a>';}echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'foreign='.urlencode($a).'">'.'Add foreign key'."</a>\n";}}if(support(is_view($R)?"view_trigger":"trigger")){echo"<h3 id='triggers'>".'Triggers'."</h3>\n";$Di=triggers($a);if($Di){echo"<table cellspacing='0'>\n";foreach($Di
as$x=>$X)echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($x)."<td><a href='".h(ME.'trigger='.urlencode($a).'&name='.urlencode($x))."'>".'Alter'."</a>\n";echo"</table>\n";}echo'<p class="links"><a href="'.h(ME).'trigger='.urlencode($a).'">'.'Add trigger'."</a>\n";}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));$Vh=array();$Wh=array();$ea=($_GET["schema"]?$_GET["schema"]:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ea,$Ie,PREG_SET_ORDER);foreach($Ie
as$r=>$B){$Vh[$B[1]]=array($B[2],$B[3]);$Wh[]="\n\t'".js_escape($B[1])."': [ $B[2], $B[3] ]";}$ti=0;$Qa=-1;$eh=array();$Jg=array();$xe=array();foreach(table_status('',true)as$Q=>$R){if(is_view($R))continue;$kg=0;$eh[$Q]["fields"]=array();foreach(fields($Q)as$C=>$m){$kg+=1.25;$m["pos"]=$kg;$eh[$Q]["fields"][$C]=$m;}$eh[$Q]["pos"]=($Vh[$Q]?$Vh[$Q]:array($ti,0));foreach($b->foreignKeys($Q)as$X){if(!$X["db"]){$ve=$Qa;if($Vh[$Q][1]||$Vh[$X["table"]][1])$ve=min(floatval($Vh[$Q][1]),floatval($Vh[$X["table"]][1]))-1;else$Qa-=.1;while($xe[(string)$ve])$ve-=.0001;$eh[$Q]["references"][$X["table"]][(string)$ve]=array($X["source"],$X["target"]);$Jg[$X["table"]][$Q][(string)$ve]=$X["target"];$xe[(string)$ve]=true;}}$ti=max($ti,$eh[$Q]["pos"][0]+2.5+$kg);}echo'<div id="schema" style="height: ',$ti,'em;">
<script',nonce(),'>
qs(\'#schema\').onselectstart = function () { return false; };
var tablePos = {',implode(",",$Wh)."\n",'};
var em = qs(\'#schema\').offsetHeight / ',$ti,';
document.onmousemove = schemaMousemove;
document.onmouseup = partialArg(schemaMouseup, \'',js_escape(DB),'\');
</script>
';foreach($eh
as$C=>$Q){echo"<div class='table' style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em;'>",'<a href="'.h(ME).'table='.urlencode($C).'"><b>'.h($C)."</b></a>",script("qsl('div').onmousedown = schemaMousedown;");foreach($Q["fields"]as$m){$X='<span'.type_class($m["type"]).' title="'.h($m["full_type"].($m["null"]?" NULL":'')).'">'.h($m["field"]).'</span>';echo"<br>".($m["primary"]?"<i>$X</i>":$X);}foreach((array)$Q["references"]as$ci=>$Kg){foreach($Kg
as$ve=>$Gg){$we=$ve-$Vh[$C][1];$r=0;foreach($Gg[0]as$_h)echo"\n<div class='references' title='".h($ci)."' id='refs$ve-".($r++)."' style='left: $we"."em; top: ".$Q["fields"][$_h]["pos"]."em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: ".(-$we)."em;'></div></div>";}}foreach((array)$Jg[$C]as$ci=>$Kg){foreach($Kg
as$ve=>$e){$we=$ve-$Vh[$C][1];$r=0;foreach($e
as$bi)echo"\n<div class='references' title='".h($ci)."' id='refd$ve-".($r++)."' style='left: $we"."em; top: ".$Q["fields"][$bi]["pos"]."em; height: 1.25em; background: url(".h(preg_replace("~\\?.*~","",ME)."?file=arrow.gif) no-repeat right center;&version=4.8.4")."'><div style='height: .5em; border-bottom: 1px solid Gray; width: ".(-$we)."em;'></div></div>";}}echo"\n</div>\n";}foreach($eh
as$C=>$Q){foreach((array)$Q["references"]as$ci=>$Kg){foreach($Kg
as$ve=>$Gg){$We=$ti;$Me=-10;foreach($Gg[0]as$x=>$_h){$lg=$Q["pos"][0]+$Q["fields"][$_h]["pos"];$mg=$eh[$ci]["pos"][0]+$eh[$ci]["fields"][$Gg[1][$x]]["pos"];$We=min($We,$lg,$mg);$Me=max($Me,$lg,$mg);}echo"<div class='references' id='refl$ve' style='left: $ve"."em; top: $We"."em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: ".($Me-$We)."em;'></div></div>\n";}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".urlencode($ea)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$l){$Gb="";foreach(array("output","format","db_style","routines","events","table_style","auto_increment","triggers","data_style")as$x)$Gb.="&$x=".urlencode($_POST[$x]);cookie("adminer_export",substr($Gb,1));$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Qc=dump_headers((count($S)==1?key($S):DB),(DB==""||count($S)>1));$he=preg_match('~sql~',$_POST["format"]);if($he){echo"-- Adminer $ia ".$mc[DRIVER]." ".str_replace("\n"," ",$f->server_info)." dump\n\n";if($w=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";$f->query("SET time_zone = '+00:00'");$f->query("SET sql_mode = ''");}}$Mh=$_POST["db_style"];$i=array(DB);if(DB==""){$i=$_POST["databases"];if(is_string($i))$i=explode("\n",rtrim(str_replace("\r","",$i),"\n"));}foreach((array)$i
as$j){$b->dumpDatabase($j);if($f->select_db($j)){if($he&&preg_match('~CREATE~',$Mh)&&($h=$f->result("SHOW CREATE DATABASE ".idf_escape($j),1))){set_utf8mb4($h);if($Mh=="DROP+CREATE")echo"DROP DATABASE IF EXISTS ".idf_escape($j).";\n";echo"$h;\n";}if($he){if($Mh)echo
use_sql($j).";\n\n";$Of="";if($_POST["routines"]){foreach(array("FUNCTION","PROCEDURE")as$Yg){foreach(get_rows("SHOW $Yg STATUS WHERE Db = ".q($j),null,"-- ")as$J){$h=remove_definer($f->result("SHOW CREATE $Yg ".idf_escape($J["Name"]),2));set_utf8mb4($h);$Of.=($Mh!='DROP+CREATE'?"DROP $Yg IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$h;;\n\n";}}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$J){$h=remove_definer($f->result("SHOW CREATE EVENT ".idf_escape($J["Name"]),3));set_utf8mb4($h);$Of.=($Mh!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$h;;\n\n";}}if($Of)echo"DELIMITER ;;\n\n$Of"."DELIMITER ;\n\n";}if($_POST["table_style"]||$_POST["data_style"]){$dj=array();foreach(table_status('',true)as$C=>$R){$Q=(DB==""||in_array($C,(array)$_POST["tables"]));$Qb=(DB==""||in_array($C,(array)$_POST["data"]));if($Q||$Qb){if($Qc=="tar"){$pi=new
TmpFile;ob_start(array($pi,'write'),1e5);}$b->dumpTable($C,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$dj[]=$C;elseif($Qb){$n=fields($C);$b->dumpData($C,$_POST["data_style"],"SELECT *".convert_fields($n,$n)." FROM ".table($C));}if($he&&$_POST["triggers"]&&$Q&&($Di=trigger_sql($C)))echo"\nDELIMITER ;;\n$Di\nDELIMITER ;\n";if($Qc=="tar"){ob_end_flush();tar_file((DB!=""?"":"$j/")."$C.csv",$pi);}elseif($he)echo"\n";}}if(function_exists('foreign_keys_sql')){foreach(table_status('',true)as$C=>$R){$Q=(DB==""||in_array($C,(array)$_POST["tables"]));if($Q&&!is_view($R))echo
foreign_keys_sql($C);}}foreach($dj
as$cj)$b->dumpTable($cj,$_POST["table_style"],1);if($Qc=="tar")echo
pack("x512");}}}if($he)echo"-- ".$f->result("SELECT NOW()")."\n";exit;}page_header('Export',$l,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
';$Vb=array('','USE','DROP+CREATE','CREATE');$Xh=array('','DROP+CREATE','CREATE');$Rb=array('','TRUNCATE+INSERT','INSERT');if($w=="sql")$Rb[]='INSERT+UPDATE';parse_str($_COOKIE["adminer_export"],$J);if(!$J)$J=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"table_style"=>"DROP+CREATE","data_style"=>"INSERT");if(!isset($J["events"])){$J["routines"]=$J["events"]=($_GET["dump"]=="");$J["triggers"]=$J["table_style"];}echo"<tr><th>".'Output'."<td>".html_select("output",$b->dumpOutput(),$J["output"],0)."\n";echo"<tr><th>".'Format'."<td>".html_select("format",$b->dumpFormat(),$J["format"],0)."\n";echo($w=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$Vb,$J["db_style"]).(support("routine")?checkbox("routines",1,$J["routines"],'Routines'):"").(support("event")?checkbox("events",1,$J["events"],'Events'):"")),"<tr><th>".'Tables'."<td>".html_select('table_style',$Xh,$J["table_style"]).checkbox("auto_increment",1,$J["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$J["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$Rb,$J["data_style"]),'</table>
<p><input type="submit" value="Export">
<input type="hidden" name="token" value="',$si,'">

<table cellspacing="0">
',script("qsl('table').onclick = dumpClick;");$pg=array();if(DB!=""){$db=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$db>".'Tables'."</label>".script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);",""),"<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$db></label>".script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);",""),"</thead>\n";$dj="";$Yh=tables_list();foreach($Yh
as$C=>$T){$og=preg_replace('~_.*~','',$C);$db=($a==""||$a==(substr($a,-1)=="%"?"$og%":$C));$sg="<tr><td>".checkbox("tables[]",$C,$db,$C,"","block");if($T!==null&&!preg_match('~table~i',$T))$dj.="$sg\n";else
echo"$sg<td align='right'><label class='block'><span id='Rows-".h($C)."'></span>".checkbox("data[]",$C,$db)."</label>\n";$pg[$og]++;}echo$dj;if($Yh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-databases'".($a==""?" checked":"").">".'Database'."</label>",script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);",""),"</thead>\n";$i=$b->databases();if($i){foreach($i
as$j){if(!information_schema($j)){$og=preg_replace('~_.*~','',$j);echo"<tr><td>".checkbox("databases[]",$j,$a==""||$a=="$og%",$j,"","block")."\n";$pg[$og]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$dd=true;foreach($pg
as$x=>$X){if($x!=""&&$X>1){echo($dd?"<p>":" ")."<a href='".h(ME)."dump=".urlencode("$x%")."'>".h($x)."</a>";$dd=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$H=$f->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$ud=$H;if(!$H)$H=$f->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo"<input type='hidden' name='db' value='".h(DB)."'>\n",($ud?"":"<input type='hidden' name='grant' value=''>\n"),"<table cellspacing='0'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<th></thead>\n";while($J=$H->fetch_assoc())echo'<tr'.odd().'><td>'.h($J["User"])."<td>".h($J["Host"]).'<td><a href="'.h(ME.'user='.urlencode($J["User"]).'&host='.urlencode($J["Host"])).'">'.'Edit'."</a>\n";if(!$ud||DB!="")echo"<tr".odd()."><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$l&&$_POST["export"]){dump_headers("sql");$b->dumpTable("","");$b->dumpData("","table",$_POST["query"]);exit;}restart_session();$Hd=&get_session("queries");$Gd=&$Hd[DB];if(!$l&&$_POST["clear"]){$Gd=array();redirect(remove_from_uri("history"));}page_header((isset($_GET["import"])?'Import':'SQL command'),$l);if(!$l&&$_POST){$qd=false;if(!isset($_GET["import"]))$G=$_POST["query"];elseif($_POST["webfile"]){$Dh=$b->importServerPath();$qd=@fopen((file_exists($Dh)?$Dh:"compress.zlib://$Dh.gz"),"rb");$G=($qd?fread($qd,1e6):false);}else$G=get_file("sql_file",true);if(is_string($G)){if(function_exists('memory_get_usage'))@ini_set("memory_limit",max(ini_bytes("memory_limit"),2*strlen($G)+memory_get_usage()+8e6));if($G!=""&&strlen($G)<1e6){$zg=$G.(preg_match("~;[ \t\r\n]*\$~",$G)?"":";");if(!$Gd||reset(end($Gd))!=$zg){restart_session();$Gd[]=array($zg,time());set_session("queries",$Hd);stop_session();}}$Ah="(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";$ec=";";$nf=0;$Ac=true;$g=connect();if(is_object($g)&&DB!=""){$g->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$g);}$sb=0;$Fc=array();$Vf='[\'"'.($w=="sql"?'`#':($w=="sqlite"?'`[':($w=="mssql"?'[':''))).']|/\*|-- |$'.($w=="pgsql"?'|\$[^$]*\$':'');$ui=microtime(true);parse_str($_COOKIE["adminer_export"],$ya);$sc=$b->dumpFormat();unset($sc["sql"]);while($G!=""){if(!$nf&&preg_match("~^$Ah*+DELIMITER\\s+(\\S+)~i",$G,$B)){$ec=$B[1];$G=substr($G,strlen($B[0]));}else{preg_match('('.preg_quote($ec)."\\s*|$Vf)",$G,$B,PREG_OFFSET_CAPTURE,$nf);list($od,$kg)=$B[0];if(!$od&&$qd&&!feof($qd))$G.=fread($qd,1e5);else{if(!$od&&rtrim($G)=="")break;$nf=$kg+strlen($od);if($od&&rtrim($od)!=$ec){while(preg_match('('.($od=='/*'?'\*/':($od=='['?']':(preg_match('~^-- |^#~',$od)?"\n":preg_quote($od)."|\\\\."))).'|$)s',$G,$B,PREG_OFFSET_CAPTURE,$nf)){$ch=$B[0][0];if(!$ch&&$qd&&!feof($qd))$G.=fread($qd,1e5);else{$nf=$B[0][1]+strlen($ch);if($ch[0]!="\\")break;}}}else{$Ac=false;$zg=substr($G,0,$kg);$sb++;$sg="<pre id='sql-$sb'><code class='jush-$w copy-to-clipboard'>".$b->sqlCommandQuery($zg)."</code></pre>\n";$sg.=generate_linksbar(["<a href='#' class='copy-to-clipboard'>".'Copy to clipboard'."</a>"]);if($w=="sqlite"&&preg_match("~^$Ah*+ATTACH\\b~i",$zg,$B)){echo$sg,"<p class='error'>".'ATTACH queries are not supported.'."\n";$Fc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$sg;ob_flush();flush();}$Hh=microtime(true);if($f->multi_query($zg)&&is_object($g)&&preg_match("~^$Ah*+USE\\b~i",$zg))$g->query($zg);do{$H=$f->store_result();if($f->error){echo($_POST["only_errors"]?$sg:""),"<p class='error'>".'Error in query'.($f->errno?" ($f->errno)":"").": ".error()."\n";$Fc[]=" <a href='#sql-$sb'>$sb</a>";if($_POST["error_stops"])break
2;}else{$ii=" <span class='time'>(".format_time($Hh).")</span>".(strlen($zg)<1000?" <a href='".h(ME)."sql=".urlencode(trim($zg))."'>".'Edit'."</a>":"");$_a=$f->affected_rows;$gj=($_POST["only_errors"]?"":$k->warnings());$hj="warnings-$sb";if($gj)$ii.=", <a href='#$hj'>".'Warnings'."</a>".script("qsl('a').onclick = partial(toggle, '$hj');","");$Nc=null;$Oc="explain-$sb";if(is_object($H)){$y=$_POST["limit"];$Hf=select($H,$g,array(),$y);if(!$_POST["only_errors"]){echo"<form action='' method='post'>\n";$jf=$H->num_rows;echo"<p>".($jf?($y&&$jf>$y?sprintf('%d / ',$y):"").lang(array('%d row','%d rows'),$jf):""),$ii;if($g&&preg_match("~^($Ah|\\()*+SELECT\\b~i",$zg)&&($Nc=explain($g,$zg)))echo", <a href='#$Oc'>Explain</a>".script("qsl('a').onclick = partial(toggle, '$Oc');","");$s="export-$sb";echo", <a href='#$s'>".'Export'."</a>".script("qsl('a').onclick = partial(toggle, '$s');","")."<span id='$s' class='hidden'>: ".html_select("output",$b->dumpOutput(),$ya["output"])." ".html_select("format",$sc,$ya["format"])."<input type='hidden' name='query' value='".h($zg)."'>"." <input type='submit' name='export' value='".'Export'."'><input type='hidden' name='token' value='$si'></span>\n"."</form>\n";}}else{if(preg_match("~^$Ah*+(CREATE|DROP|ALTER)$Ah++(DATABASE|SCHEMA)\\b~i",$zg)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h($f->info)."'>".lang(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$_a)."$ii\n";}echo($gj?"<div id='$hj' class='hidden'>\n$gj</div>\n":"");if($Nc){echo"<div id='$Oc' class='hidden'>\n";select($Nc,$g,$Hf);echo"</div>\n";}}$Hh=microtime(true);}while($f->next_result());}$G=substr($G,$nf);$nf=0;}}}}if($Ac)echo"<p class='message'>".'No commands to execute.'."\n";elseif($_POST["only_errors"]){echo"<p class='message'>".lang(array('%d query executed OK.','%d queries executed OK.'),$sb-count($Fc))," <span class='time'>(".format_time($ui).")</span>\n";}elseif($Fc&&$sb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Fc)."\n";}else
echo"<p class='error'>".upload_error($G)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form">
';$Lc="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$zg=$_GET["sql"];if($_POST)$zg=$_POST["query"];elseif($_GET["history"]=="all")$zg=$Gd;elseif($_GET["history"]!="")$zg=$Gd[$_GET["history"]][0];echo"<p>";textarea("query",$zg,20);echo
script(($_POST?"":"qs('textarea').focus();\n")."qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '".js_escape(remove_from_uri("sql|limit|error_stops|only_errors|history"))."');"),"<p>$Lc\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{echo"<fieldset><legend>".'File upload'."</legend><div>";$_d=(extension_loaded("zlib")?"[.gz]":"");echo(ini_bool("file_uploads")?"SQL$_d (&lt; ".ini_get("upload_max_filesize")."B): <input type='file' name='sql_file[]' multiple>\n$Lc":'File uploads are disabled.'),"</div></fieldset>\n";$Od=$b->importServerPath();if($Od){echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($Od)."$_d</code>"),' <input type="submit" name="webfile" value="'.'Run file'.'">',"</div></fieldset>\n";}echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n","<input type='hidden' name='token' value='$si'>\n";if(!isset($_GET["import"])&&$Gd){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($Gd);$X;$X=prev($Gd)){$x=key($Gd);list($zg,$ii,$wc)=$X;echo'<a href="'.h(ME."sql=&history=$x").'" class="edit" title="'.'Edit'.'">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$ii)."'>".@date("H:i:s",$ii)."</span>"." <code class='jush-$w'>".shorten_utf8(ltrim(str_replace("\n"," ",str_replace("\r","",preg_replace('~^(#|-- ).*~m','',$zg)))),80,"</code>").($wc?" <span class='time'>($wc)</span>":"")."<br>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."' class='edit-all' title='".'Edit all'."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$n=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$n):""):where($_GET,$n));$Ni=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($n
as$C=>$m){if(!isset($m["privileges"][$Ni?"update":"insert"])||$b->fieldName($m)==""||$m["generated"])unset($n[$C]);}if($_POST&&!$l&&!isset($_GET["select"])){$A=$_POST["referer"];if($_POST["insert"])$A=($Ni?null:$_SERVER["REQUEST_URI"]);elseif(!preg_match('~^.+&select=.+$~',$A))$A=ME."select=".urlencode($a);$v=indexes($a);$Ii=unique_array($_GET["where"],$v);$Bg="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($A,'Item has been deleted.',$k->delete($a,$Bg,!$Ii));else{$N=array();foreach($n
as$C=>$m){$X=process_input($m);if($X!==false&&$X!==null)$N[idf_escape($C)]=$X;}if($Ni){if(!$N)redirect($A);queries_redirect($A,'Item has been updated.',$k->update($a,$N,$Bg,!$Ii));if(is_ajax()){page_headers();page_messages($l);exit;}}else{$H=$k->insert($a,$N);$ue=($H?last_id():0);queries_redirect($A,sprintf('Item%s has been inserted.',($ue?" $ue":"")),$H);}}}$J=null;if($_POST["save"])$J=(array)$_POST["fields"];elseif($Z){$L=array();foreach($n
as$C=>$m){if(isset($m["privileges"]["select"])){$Ga=convert_field($m);if($_POST["clone"]&&$m["auto_increment"])$Ga="''";if($w=="sql"&&preg_match("~enum|set~",$m["type"]))$Ga="1*".idf_escape($C);$L[]=($Ga?"$Ga AS ":"").idf_escape($C);}}$J=array();if(!support("table"))$L=array("*");if($L){$H=$k->select($a,$L,array($Z),$L,array(),(isset($_GET["select"])?2:1));if(!$H)$l=error();else{$J=$H->fetch_assoc();if(!$J)$J=false;}if(isset($_GET["select"])&&(!$J||$H->fetch_assoc()))$J=null;}}if(!support("table")&&!$n){if(!$Z){$H=$k->select($a,array("*"),$Z,array("*"));$J=($H?$H->fetch_assoc():false);if(!$J)$J=array($k->primary=>"");}if($J){foreach($J
as$x=>$X){if(!$Z)$J[$x]=null;$n[$x]=array("field"=>$x,"null"=>($x!=$k->primary),"auto_increment"=>($x==$k->primary));}}}edit_form($a,$n,$J,$Ni);}elseif(isset($_GET["create"])){$a=$_GET["create"];$Xf=array();foreach(array('HASH','LINEAR HASH','KEY','LINEAR KEY','RANGE','LIST')as$x)$Xf[$x]=$x;$Ig=referencable_primary($a);$md=array();foreach($Ig
as$Th=>$m)$md[str_replace("`","``",$Th)."`".str_replace("`","``",$m["field"])]=$Th;$Kf=array();$R=array();if($a!=""){$Kf=fields($a);$R=table_status($a);if(!$R)$l='No tables.';}$J=$_POST;$J["fields"]=(array)$J["fields"];if($J["auto_increment_col"])$J["fields"][$J["auto_increment_col"]]["auto_increment"]=true;if($_POST)set_adminer_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($J["fields"])&&!$l){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$n=array();$Da=array();$Ri=false;$kd=array();$Jf=reset($Kf);$Ba=" FIRST";foreach($J["fields"]as$x=>$m){$p=$md[$m["type"]];$Ei=($p!==null?$Ig[$p]:$m);if($m["field"]!=""){if(!$m["has_default"])$m["default"]=null;if($x==$J["auto_increment_col"])$m["auto_increment"]=true;$xg=process_field($m,$Ei);$Da[]=array($m["orig"],$xg,$Ba);if(!$Jf||$xg!=process_field($Jf,$Jf)){$n[]=array($m["orig"],$xg,$Ba);if($m["orig"]!=""||$Ba)$Ri=true;}if($p!==null)$kd[idf_escape($m["field"])]=($a!=""&&$w!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$md[$m["type"]],'source'=>array($m["field"]),'target'=>array($Ei["field"]),'on_delete'=>$m["on_delete"],));$Ba=" AFTER ".idf_escape($m["field"]);}elseif($m["orig"]!=""){$Ri=true;$n[]=array($m["orig"]);}if($m["orig"]!=""){$Jf=next($Kf);if(!$Jf)$Ba="";}}$Zf="";if($Xf[$J["partition_by"]]){$ag=array();if($J["partition_by"]=='RANGE'||$J["partition_by"]=='LIST'){foreach(array_filter($J["partition_names"])as$x=>$X){$Y=$J["partition_values"][$x];$ag[]="\n  PARTITION ".idf_escape($X)." VALUES ".($J["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$Zf.="\nPARTITION BY $J[partition_by]($J[partition])".($ag?" (".implode(",",$ag)."\n)":($J["partitions"]?" PARTITIONS ".(+$J["partitions"]):""));}elseif(support("partitioning")&&preg_match("~partitioned~",$R["Create_options"]))$Zf.="\nREMOVE PARTITIONING";$Qe='Table has been altered.';if($a==""){cookie("adminer_engine",$J["Engine"]);$Qe='Table has been created.';}$C=trim($J["name"]);queries_redirect(ME.(support("table")?"table=":"select=").urlencode($C),$Qe,alter_table($a,$C,($w=="sqlite"&&($Ri||$kd)?$Da:$n),$kd,($J["Comment"]!=$R["Comment"]?$J["Comment"]:null),($J["Engine"]&&$J["Engine"]!=$R["Engine"]?$J["Engine"]:""),($J["Collation"]&&$J["Collation"]!=$R["Collation"]?$J["Collation"]:""),($J["Auto_increment"]!=""?number($J["Auto_increment"]):""),$Zf));}}page_header(($a!=""?'Alter table':'Create table'),$l,array("table"=>$a),h($a));if(!$_POST){$J=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($U["int"])?"int":(isset($U["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$J=$R;$J["name"]=$a;$J["fields"]=array();if(!$_GET["auto_increment"])$J["Auto_increment"]="";foreach($Kf
as$m){$m["has_default"]=isset($m["default"]);$J["fields"][]=$m;}if(support("partitioning")){$rd="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($a);$H=$f->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $rd ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");list($J["partition_by"],$J["partitions"],$J["partition"])=$H->fetch_row();$ag=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $rd AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$ag[""]="";$J["partition_names"]=array_keys($ag);$J["partition_values"]=array_values($ag);}}}$nb=collations();$Cc=engines();foreach($Cc
as$Bc){if(!strcasecmp($Bc,$J["Engine"])){$J["Engine"]=$Bc;break;}}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name: <input name="name" data-maxlength="64" value="',h($J["name"]),'" autocapitalize="off">
';if($a==""&&!$_POST)echo
script("focus(qs('#form')['name']);");echo($Cc?"<select name='Engine'>".optionlist(array(""=>"(".'engine'.")")+$Cc,$J["Engine"])."</select>".on_help("getTarget(event).value",1).script("qsl('select').onchange = helpClose;"):""),' ',($nb&&!preg_match("~sqlite|mssql~",$w)?html_select("Collation",array(""=>"(".'collation'.")")+$nb,$J["Collation"]):""),' <input type="submit" value="Save">
';}echo'
';if(support("columns")){echo'<div class="scrollable">
<table cellspacing="0" id="edit-fields" class="nowrap">
';edit_fields($J["fields"],$nb,"TABLE",$md);echo'</table>
',script("editFields();"),'</div>
<p>
Auto Increment: <input type="number" name="Auto_increment" size="6" value="',h($J["Auto_increment"]),'">
',checkbox("defaults",1,($_POST?$_POST["defaults"]:adminer_setting("defaults")),'Default values',"columnShow(this.checked, 5)","jsonly");$vb=($_POST?$_POST["comments"]:adminer_setting("comments"));echo(support("comment")?checkbox("comments",1,$vb,'Comment',"editingCommentsClick(this, true);","jsonly").' '.(preg_match('~\n~',$J["Comment"])?"<textarea name='Comment' rows='2' cols='20'".($vb?"":" class='hidden'").">".h($J["Comment"])."</textarea>":'<input name="Comment" value="'.h($J["Comment"]).'" data-maxlength="'.(min_version(5.5)?2048:60).'"'.($vb?"":" class='hidden'").'>'):''),'<p>
<input type="submit" value="Save">
';}echo'
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}if(support("partitioning")){$Yf=preg_match('~RANGE|LIST~',$J["partition_by"]);print_fieldset("partition",'Partition by',$J["partition_by"]);echo'<p>
',"<select name='partition_by'>".optionlist(array(""=>"")+$Xf,$J["partition_by"])."</select>".on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')",1).script("qsl('select').onchange = partitionByChange;"),'(<input name="partition" value="',h($J["partition"]),'">)
Partitions: <input type="number" name="partitions" class="size',($Yf||!$J["partition_by"]?" hidden":""),'" value="',h($J["partitions"]),'">
<table cellspacing="0" id="partition-table"',($Yf?"":" class='hidden'"),'>
<thead><tr><th>Partition name<th>Values</thead>
';foreach($J["partition_names"]as$x=>$X){echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off">',($x==count($J["partition_names"])-1?script("qsl('input').oninput = partitionNameChange;"):''),'<td><input name="partition_values[]" value="'.h($J["partition_values"][$x]).'">';}echo'</table>
</div></fieldset>
';}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Rd=array("PRIMARY","UNIQUE","INDEX");$R=table_status($a,true);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$R["Engine"]))$Rd[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$R["Engine"]))$Rd[]="SPATIAL";$v=indexes($a);$qg=array();if($w=="mongo"){$qg=$v["_id_"];unset($Rd[0]);unset($v["_id_"]);}$J=$_POST;if($_POST&&!$l&&!$_POST["add"]&&!$_POST["drop_col"]){$c=array();foreach($J["indexes"]as$u){$C=$u["name"];if(in_array($u["type"],$Rd)){$e=array();$_e=array();$gc=array();$N=array();ksort($u["columns"]);foreach($u["columns"]as$x=>$d){if($d!=""){$ze=$u["lengths"][$x];$fc=$u["descs"][$x];$N[]=idf_escape($d).($ze?"(".(+$ze).")":"").($fc?" DESC":"");$e[]=$d;$_e[]=($ze?$ze:null);$gc[]=$fc;}}if($e){$Mc=$v[$C];if($Mc){ksort($Mc["columns"]);ksort($Mc["lengths"]);ksort($Mc["descs"]);if($u["type"]==$Mc["type"]&&array_values($Mc["columns"])===$e&&(!$Mc["lengths"]||array_values($Mc["lengths"])===$_e)&&array_values($Mc["descs"])===$gc){unset($v[$C]);continue;}}$c[]=array($u["type"],$C,$N);}}}foreach($v
as$C=>$Mc)$c[]=array($Mc["type"],$C,"DROP");if(!$c)redirect(ME."table=".urlencode($a));queries_redirect(ME."table=".urlencode($a),'Indexes have been altered.',alter_indexes($a,$c));}page_header('Indexes',$l,array("table"=>$a),h($a));$n=array_keys(fields($a));if($_POST["add"]){foreach($J["indexes"]as$x=>$u){if($u["columns"][count($u["columns"])]!="")$J["indexes"][$x]["columns"][]="";}$u=end($J["indexes"]);if($u["type"]||array_filter($u["columns"],'strlen'))$J["indexes"][]=array("columns"=>array(1=>""));}if(!$J){foreach($v
as$x=>$u){$v[$x]["name"]=$x;$v[$x]["columns"][]="";}$v[]=array("columns"=>array(1=>""));$J["indexes"]=$v;}echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
<thead><tr>
<th id="label-type">Index Type
<th><input type="submit" class="wayoff">Column (length)
<th id="label-name">Name
<th><noscript>',"<input type='image' class='icon' name='add[0]' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.8.4")."' alt='+' title='".'Add next'."'>",'</noscript>
</thead>
';if($qg){echo"<tr><td>PRIMARY<td>";foreach($qg["columns"]as$x=>$d){echo
select_input(" disabled",$n,$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";}echo"<td><td>\n";}$ke=1;foreach($J["indexes"]as$u){if(!$_POST["drop_col"]||$ke!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$ke][type]",array(-1=>"")+$Rd,$u["type"],($ke==count($J["indexes"])?"indexesAddRow.call(this);":1),"label-type"),"<td>";ksort($u["columns"]);$r=1;foreach($u["columns"]as$x=>$d){echo"<span>".select_input(" name='indexes[$ke][columns][$r]' title='".'Column'."'",($n?array_combine($n,$n):$n),$d,"partial(".($r==count($u["columns"])?"indexesAddColumn":"indexesChangeColumn").", '".js_escape($w=="sql"?"":$_GET["indexes"]."_")."')"),($w=="sql"||$w=="mssql"?"<input type='number' name='indexes[$ke][lengths][$r]' class='size' value='".h($u["lengths"][$x])."' title='".'Length'."'>":""),(support("descidx")?checkbox("indexes[$ke][descs][$r]",1,$u["descs"][$x],'descending'):"")," </span>";$r++;}echo"<td><input name='indexes[$ke][name]' value='".h($u["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n","<td><input type='image' class='icon' name='drop_col[$ke]' src='".h(preg_replace("~\\?.*~","",ME)."?file=cross.gif&version=4.8.4")."' alt='x' title='".'Remove'."'>".script("qsl('input').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");}$ke++;}echo'</table>
</div>
<p>
<input type="submit" value="Save">
<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["database"])){$J=$_POST;if($_POST&&!$l&&!isset($_POST["add_x"])){$C=trim($J["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif(DB!==$C){if(DB!=""){$_GET["db"]=$C;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".urlencode($C),'Database has been renamed.',rename_database($C,$J["collation"]));}else{$i=explode("\n",str_replace("\r","",$C));$Nh=true;$te="";foreach($i
as$j){if(count($i)==1||$j!=""){if(!create_database($j,$J["collation"]))$Nh=false;$te=$j;}}restart_session();set_session("dbs",null);queries_redirect(ME."db=".urlencode($te),'Database has been created.',$Nh);}}else{if(!$J["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($C).(preg_match('~^[a-z0-9_]+$~i',$J["collation"])?" COLLATE $J[collation]":""),substr(ME,0,-1),'Database has been altered.');}}page_header(DB!=""?'Alter database':'Create database',$l,array(),h(DB));$nb=collations();$C=DB;if($_POST)$C=$J["name"];elseif(DB!="")$J["collation"]=db_collation(DB,$nb);elseif($w=="sql"){foreach(get_vals("SHOW GRANTS")as$ud){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$ud,$B)&&$B[1]){$C=stripcslashes(idf_unescape("`$B[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add_x"]||strpos($C,"\n")?'<textarea id="name" name="name" rows="10" cols="40">'.h($C).'</textarea><br>':'<input name="name" id="name" value="'.h($C).'" data-maxlength="64" autocapitalize="off">')."\n".($nb?html_select("collation",array(""=>"(".'collation'.")")+$nb,$J["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"ms187963.aspx",)):""),script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',DB))."\n";elseif(!$_POST["add_x"]&&$_GET["db"]=="")echo"<input type='image' class='icon' name='add' src='".h(preg_replace("~\\?.*~","",ME)."?file=plus.gif&version=4.8.4")."' alt='+' title='".'Add next'."'>\n";echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["scheme"])){$J=$_POST;if($_POST&&!$l){$z=preg_replace('~ns=[^&]*&~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$z,'Schema has been dropped.');else{$C=trim($J["name"]);$z.=urlencode($C);if($_GET["ns"]=="")query_redirect("CREATE SCHEMA ".idf_escape($C),$z,'Schema has been created.');elseif($_GET["ns"]!=$C)query_redirect("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($C),$z,'Schema has been altered.');else
redirect($z);}}page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$l);if(!$J)$J["name"]=$_GET["ns"];echo'
<form action="" method="post">
<p><input name="name" id="name" value="',h($J["name"]),'" autocapitalize="off">
',script("focus(qs('#name'));"),'<input type="submit" value="Save">
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$_GET["ns"]))."\n";echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["call"])){$da=($_GET["name"]?$_GET["name"]:$_GET["call"]);page_header('Call'.": ".h($da),$l);$Yg=routine($_GET["call"],(isset($_GET["callf"])?"FUNCTION":"PROCEDURE"));$Pd=array();$Of=array();foreach($Yg["fields"]as$r=>$m){if(substr($m["inout"],-3)=="OUT")$Of[$r]="@".idf_escape($m["field"])." AS ".idf_escape($m["field"]);if(!$m["inout"]||substr($m["inout"],0,2)=="IN")$Pd[]=$r;}if(!$l&&$_POST){$Ya=array();foreach($Yg["fields"]as$x=>$m){if(in_array($x,$Pd)){$X=process_input($m);if($X===false)$X="''";if(isset($Of[$x]))$f->query("SET @".idf_escape($m["field"])." = $X");}$Ya[]=(isset($Of[$x])?"@".idf_escape($m["field"]):$X);}$G=(isset($_GET["callf"])?"SELECT":"CALL")." ".table($da)."(".implode(", ",$Ya).")";$Hh=microtime(true);$H=$f->multi_query($G);$_a=$f->affected_rows;echo$b->selectQuery($G,$Hh,!$H);if(!$H)echo"<p class='error'>".error()."\n";else{$g=connect();if(is_object($g))$g->select_db(DB);do{$H=$f->store_result();if(is_object($H))select($H,$g);else
echo"<p class='message'>".lang(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$_a)." <span class='time'>".@date("H:i:s")."</span>\n";}while($f->next_result());if($Of)select($f->query("SELECT ".implode(", ",$Of)));}}echo'
<form action="" method="post">
';if($Pd){echo"<table cellspacing='0' class='layout'>\n";foreach($Pd
as$x){$m=$Yg["fields"][$x];$C=$m["field"];echo"<tr><th>".$b->fieldName($m);$Y=$_POST["fields"][$C];if($Y!=""){if($m["type"]=="enum")$Y=+$Y;if($m["type"]=="set")$Y=array_sum($Y);}input($m,$Y,(string)$_POST["function"][$C]);echo"\n";}echo"</table>\n";}echo'<p>
<input type="submit" value="Call">
<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$C=$_GET["name"];$J=$_POST;if($_POST&&!$l&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){$Qe=($_POST["drop"]?'Foreign key has been dropped.':($C!=""?'Foreign key has been altered.':'Foreign key has been created.'));$A=ME."table=".urlencode($a);if(!$_POST["drop"]){$J["source"]=array_filter($J["source"],'strlen');ksort($J["source"]);$bi=array();foreach($J["source"]as$x=>$X)$bi[$x]=$J["target"][$x];$J["target"]=$bi;}if($w=="sqlite")queries_redirect($A,$Qe,recreate_table($a,$a,array(),array(),array(" $C"=>($_POST["drop"]?"":" ".format_foreign_key($J)))));else{$c="ALTER TABLE ".table($a);$nc="\nDROP ".($w=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($C);if($_POST["drop"])query_redirect($c.$nc,$A,$Qe);else{query_redirect($c.($C!=""?"$nc,":"")."\nADD".format_foreign_key($J),$A,$Qe);$l='Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.'."<br>$l";}}}page_header('Foreign key',$l,array("table"=>$a),h($a));if($_POST){ksort($J["source"]);if($_POST["add"])$J["source"][]="";elseif($_POST["change"]||$_POST["change-js"])$J["target"]=array();}elseif($C!=""){$md=foreign_keys($a);$J=$md[$C];$J["source"][]="";}else{$J["table"]=$a;$J["source"]=array("");}echo'
<form action="" method="post">
';$_h=array_keys(fields($a));if($J["db"]!="")$f->select_db($J["db"]);if($J["ns"]!="")set_schema($J["ns"]);$Hg=array_keys(array_filter(table_status('',true),'fk_support'));$bi=array_keys(fields(in_array($J["table"],$Hg)?$J["table"]:reset($Hg)));$wf="this.form['change-js'].value = '1'; this.form.submit();";echo"<p>".'Target table'.": ".html_select("table",$Hg,$J["table"],$wf)."\n";if($w=="pgsql")echo'Schema'.": ".html_select("ns",$b->schemas(),$J["ns"]!=""?$J["ns"]:$_GET["ns"],$wf);elseif($w!="sqlite"){$Wb=array();foreach($b->databases()as$j){if(!information_schema($j))$Wb[]=$j;}echo'DB'.": ".html_select("db",$Wb,$J["db"]!=""?$J["db"]:$_GET["db"],$wf);}echo'<input type="hidden" name="change-js" value="">
<noscript><p><input type="submit" name="change" value="Change"></noscript>
<table cellspacing="0">
<thead><tr><th id="label-source">Source<th id="label-target">Target</thead>
';$ke=0;foreach($J["source"]as$x=>$X){echo"<tr>","<td>".html_select("source[".(+$x)."]",array(-1=>"")+$_h,$X,($ke==count($J["source"])-1?"foreignAddRow.call(this);":1),"label-source"),"<td>".html_select("target[".(+$x)."]",$bi,isset($J["target"][$x])?$J["target"][$x]:null,1,"label-target");$ke++;}echo'</table>
<p>
ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",$vf),$J["on_delete"]),' ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",$vf),$J["on_update"]),doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-REFERENCES",'mssql'=>"ms174979.aspx",'oracle'=>"https://docs.oracle.com/cd/B19306_01/server.102/b14200/clauses002.htm#sthref2903",)),'<p>
<input type="submit" value="Save">
<noscript><p><input type="submit" name="add" value="Add column"></noscript>
';if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$J=$_POST;$Lf="VIEW";if($w=="pgsql"&&$a!=""){$O=table_status($a);$Lf=strtoupper($O["Engine"]);}if($_POST&&!$l){$C=trim($J["name"]);$Ga=" AS\n$J[select]";$A=ME."table=".urlencode($C);$Qe='View has been altered.';$T=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$C&&$w!="sqlite"&&$T=="VIEW"&&$Lf=="VIEW")query_redirect(($w=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($C).$Ga,$A,$Qe);else{$di=$C."_adminer_".uniqid();drop_create("DROP $Lf ".table($a),"CREATE $T ".table($C).$Ga,"DROP $T ".table($C),"CREATE $T ".table($di).$Ga,"DROP $T ".table($di),($_POST["drop"]?substr(ME,0,-1):$A),'View has been dropped.',$Qe,'View has been created.',$a,$C);}}if(!$_POST&&$a!=""){$J=view($a);$J["name"]=$a;$J["materialized"]=($Lf!="VIEW");if(!$l)$l=error();}page_header(($a!=""?'Alter view':'Create view'),$l,array("table"=>$a),h($a));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$J["materialized"],'Materialized view'):""),'<p>';textarea("select",$J["select"]);echo'<p>
<input type="submit" value="Save">
';if($a!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$a));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$ce=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Jh=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$J=$_POST;if($_POST&&!$l){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($J["INTERVAL_FIELD"],$ce)&&isset($Jh[$J["STATUS"]])){$dh="\nON SCHEDULE ".($J["INTERVAL_VALUE"]?"EVERY ".q($J["INTERVAL_VALUE"])." $J[INTERVAL_FIELD]".($J["STARTS"]?" STARTS ".q($J["STARTS"]):"").($J["ENDS"]?" ENDS ".q($J["ENDS"]):""):"AT ".q($J["STARTS"]))." ON COMPLETION".($J["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$dh.($aa!=$J["EVENT_NAME"]?"\nRENAME TO ".idf_escape($J["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($J["EVENT_NAME"]).$dh)."\n".$Jh[$J["STATUS"]]." COMMENT ".q($J["EVENT_COMMENT"]).rtrim(" DO\n$J[EVENT_DEFINITION]",";").";"));}}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$l);if(!$J&&$aa!=""){$K=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$J=reset($K);}echo'
<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($J["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$J[EXECUTE_AT]$J[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($J["ENDS"]),'">
<tr><th>Every<td><input type="number" name="INTERVAL_VALUE" value="',h($J["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$ce,$J["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Jh,$J["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($J["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$J["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$J["EVENT_DEFINITION"]);echo'<p>
<input type="submit" value="Save">
';if($aa!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$aa));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["procedure"])){$da=($_GET["name"]?$_GET["name"]:$_GET["procedure"]);$Yg=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$J=$_POST;$J["fields"]=(array)$J["fields"];if($_POST&&!process_fields($J["fields"])&&!$l){$If=routine($_GET["procedure"],$Yg);$di="$J[name]_adminer_".uniqid();drop_create("DROP $Yg ".routine_id($da,$If),create_routine($Yg,$J),"DROP $Yg ".routine_id($J["name"],$J),create_routine($Yg,array("name"=>$di)+$J),"DROP $Yg ".routine_id($di,$J),substr(ME,0,-1),'Routine has been dropped.','Routine has been altered.','Routine has been created.',$da,$J["name"]);}page_header(($da!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($da):(isset($_GET["function"])?'Create function':'Create procedure')),$l);if(!$_POST&&$da!=""){$J=routine($_GET["procedure"],$Yg);$J["name"]=$da;}$nb=get_vals("SHOW CHARACTER SET");sort($nb);$Zg=routine_languages();echo'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',($Zg?'Language'.": ".html_select("language",$Zg,$J["language"])."\n":""),'<input type="submit" value="Save">
<div class="scrollable">
<table cellspacing="0" class="nowrap">
';edit_fields($J["fields"],$nb,$Yg);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",$J["returns"],$nb,array(),($w=="pgsql"?array("void","trigger"):array()));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$J["definition"]);echo'<p>
<input type="submit" value="Save">
';if($da!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$da));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["sequence"])){$fa=$_GET["sequence"];$J=$_POST;if($_POST&&!$l){$z=substr(ME,0,-1);$C=trim($J["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($fa),$z,'Sequence has been dropped.');elseif($fa=="")query_redirect("CREATE SEQUENCE ".idf_escape($C),$z,'Sequence has been created.');elseif($fa!=$C)query_redirect("ALTER SEQUENCE ".idf_escape($fa)." RENAME TO ".idf_escape($C),$z,'Sequence has been altered.');else
redirect($z);}page_header($fa!=""?'Alter sequence'.": ".h($fa):'Create sequence',$l);if(!$J)$J["name"]=$fa;echo'
<form action="" method="post">
<p><input name="name" value="',h($J["name"]),'" autocapitalize="off">
<input type="submit" value="Save">
';if($fa!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$fa))."\n";echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["type"])){$ga=$_GET["type"];$J=$_POST;if($_POST&&!$l){$z=substr(ME,0,-1);if($_POST["drop"])query_redirect("DROP TYPE ".idf_escape($ga),$z,'Type has been dropped.');else
query_redirect("CREATE TYPE ".idf_escape(trim($J["name"]))." $J[as]",$z,'Type has been created.');}page_header($ga!=""?'Alter type'.": ".h($ga):'Create type',$l);if(!$J)$J["as"]="AS ";echo'
<form action="" method="post">
<p>
';if($ga!="")echo"<input type='submit' name='drop' value='".'Drop'."'>".confirm(sprintf('Drop %s?',$ga))."\n";else{echo"<input name='name' value='".h($J['name'])."' autocapitalize='off'>\n";textarea("as",$J["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$C=$_GET["name"];$Ci=trigger_options();$J=(array)trigger($C,$a)+array("Trigger"=>$a."_bi");if($_POST){if(!$l&&in_array($_POST["Timing"],$Ci["Timing"])&&in_array($_POST["Event"],$Ci["Event"])&&in_array($_POST["Type"],$Ci["Type"])){$uf=" ON ".table($a);$nc="DROP TRIGGER ".idf_escape($C).($w=="pgsql"?$uf:"");$A=ME."table=".urlencode($a);if($_POST["drop"])query_redirect($nc,$A,'Trigger has been dropped.');else{if($C!="")queries($nc);queries_redirect($A,($C!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($uf,$_POST)));if($C!="")queries(create_trigger($uf,$J+array("Type"=>reset($Ci["Type"]))));}}$J=$_POST;}page_header(($C!=""?'Alter trigger'.": ".h($C):'Create trigger'),$l,array("table"=>$a));echo'
<form action="" method="post" id="form">
<table cellspacing="0" class="layout">
<tr><th>Time<td>',html_select("Timing",$Ci["Timing"],$J["Timing"],"triggerChange(/^".preg_quote($a,"/")."_[ba][iud]$/, '".js_escape($a)."', this.form);"),'<tr><th>Event<td>',html_select("Event",$Ci["Event"],$J["Event"],"this.form['Timing'].onchange();"),(in_array("UPDATE OF",$Ci["Event"])?" <input name='Of' value='".h($J["Of"])."' class='hidden'>":""),'<tr><th>Type<td>',html_select("Type",$Ci["Type"],$J["Type"]),'</table>
<p>Name: <input name="Trigger" value="',h($J["Trigger"]),'" data-maxlength="64" autocapitalize="off">
',script("qs('#form')['Timing'].onchange();"),'<p>';textarea("Statement",$J["Statement"]);echo'<p>
<input type="submit" value="Save">
';if($C!=""){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',$C));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["user"])){$ha=$_GET["user"];$vg=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$J){foreach(explode(",",($J["Privilege"]=="Grant option"?"":$J["Context"]))as$Eb)$vg[$Eb][$J["Privilege"]]=$J["Comment"];}$vg["Server Admin"]+=$vg["File access on server"];$vg["Databases"]["Create routine"]=$vg["Procedures"]["Create routine"];unset($vg["Procedures"]["Create routine"]);$vg["Columns"]=array();foreach(array("Select","Insert","Update","References")as$X)$vg["Columns"][$X]=$vg["Tables"][$X];unset($vg["Server Admin"]["Usage"]);foreach($vg["Tables"]as$x=>$X)unset($vg["Databases"][$x]);$df=array();if($_POST){foreach($_POST["objects"]as$x=>$X)$df[$X]=(array)$df[$X]+(array)$_POST["grants"][$x];}$vd=array();$sf="";if(isset($_GET["host"])&&($H=$f->query("SHOW GRANTS FOR ".q($ha)."@".q($_GET["host"])))){while($J=$H->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$J[0],$B)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$B[1],$Ie,PREG_SET_ORDER)){foreach($Ie
as$X){if($X[1]!="USAGE")$vd["$B[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$J[0]))$vd["$B[2]$X[2]"]["GRANT OPTION"]=true;}}if(preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~",$J[0],$B))$sf=$B[1];}}if($_POST&&!$l){$tf=(isset($_GET["host"])?q($ha)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $tf",ME."privileges=",'User has been dropped.');else{$ff=q($_POST["user"])."@".q($_POST["host"]);$cg=$_POST["pass"];if($cg!=''&&!$_POST["hashed"]&&!min_version(8)){$cg=$f->result("SELECT PASSWORD(".q($cg).")");$l=!$cg;}$Kb=false;if(!$l){if($tf!=$ff){$Kb=queries((min_version(5)?"CREATE USER":"GRANT USAGE ON *.* TO")." $ff IDENTIFIED BY ".(min_version(8)?"":"PASSWORD ").q($cg));$l=!$Kb;}elseif($cg!=$sf)queries("SET PASSWORD FOR $ff = ".q($cg));}if(!$l){$Vg=array();foreach($df
as$lf=>$ud){if(isset($_GET["grant"]))$ud=array_filter($ud);$ud=array_keys($ud);if(isset($_GET["grant"]))$Vg=array_diff(array_keys(array_filter($df[$lf],'strlen')),$ud);elseif($tf==$ff){$qf=array_keys((array)$vd[$lf]);$Vg=array_diff($qf,$ud);$ud=array_diff($ud,$qf);unset($vd[$lf]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$lf,$B)&&(!grant("REVOKE",$Vg,$B[2]," ON $B[1] FROM $ff")||!grant("GRANT",$ud,$B[2]," ON $B[1] TO $ff"))){$l=true;break;}}}if(!$l&&isset($_GET["host"])){if($tf!=$ff)queries("DROP USER $tf");elseif(!isset($_GET["grant"])){foreach($vd
as$lf=>$Vg){if(preg_match('~^(.+)(\(.*\))?$~U',$lf,$B))grant("REVOKE",array_keys($Vg),$B[2]," ON $B[1] FROM $ff");}}}queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),!$l);if($Kb)$f->query("DROP USER $ff");}}page_header((isset($_GET["host"])?'Username'.": ".h("$ha@$_GET[host]"):'Create user'),$l,array("privileges"=>array('','Privileges')));if($_POST){$J=$_POST;$vd=$df;}else{$J=$_GET+array("host"=>$f->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$J["pass"]=$sf;if($sf!="")$J["hashed"]=true;$vd[(DB==""||$vd?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table cellspacing="0" class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($J["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($J["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($J["pass"]),'" autocomplete="new-password">
';if(!$J["hashed"])echo
script("typePassword(qs('#pass'));");echo(min_version(8)?"":checkbox("hashed",1,$J["hashed"],'Hashed',"typePassword(this.form['pass'], this.checked);")),'</table>

';echo"<table cellspacing='0'>\n","<thead><tr><th colspan='2'>".'Privileges'.doc_link(array('sql'=>"grant.html#priv_level"));$r=0;foreach($vd
as$lf=>$ud){echo'<th>'.($lf!="*.*"?"<input name='objects[$r]' value='".h($lf)."' size='10' autocapitalize='off'>":"<input type='hidden' name='objects[$r]' value='*.*' size='10'>*.*");$r++;}echo"</thead>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Columns"=>'Column',"Procedures"=>'Routine',)as$Eb=>$fc){foreach((array)$vg[$Eb]as$ug=>$tb){echo"<tr".odd()."><td".($fc?">$fc<td":" colspan='2'").' lang="en" title="'.h($tb).'">'.h($ug);$r=0;foreach($vd
as$lf=>$ud){$C="'grants[$r][".h(strtoupper($ug))."]'";$Y=$ud[strtoupper($ug)];if($Eb=="Server Admin"&&$lf!=(isset($vd["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$C><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else{echo"<td align='center'><label class='block'>","<input type='checkbox' name=$C value='1'".($Y?" checked":"").($ug=="All privileges"?" id='grants-$r-all'>":">".($ug=="Grant option"?"":script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$r-all'); };"))),"</label>";}$r++;}}}echo"</table>\n",'<p>
<input type="submit" value="Save">
';if(isset($_GET["host"])){echo'<input type="submit" name="drop" value="Drop">',confirm(sprintf('Drop %s?',"$ha@$_GET[host]"));}echo'<input type="hidden" name="token" value="',$si,'">
</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")){if($_POST&&!$l){$pe=0;foreach((array)$_POST["kill"]as$X){if(kill_process($X))$pe++;}queries_redirect(ME."processlist=",lang(array('%d process has been killed.','%d processes have been killed.'),$pe),$pe||!$_POST["kill"]);}}page_header('Process list',$l);echo'
<form action="" method="post">
<div class="scrollable">
<table cellspacing="0" class="nowrap checkable">
',script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");$r=-1;foreach(process_list()as$r=>$J){if(!$r){echo"<thead><tr lang='en'>".(support("kill")?"<th>":"");foreach($J
as$x=>$X)echo"<th>$x".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($x),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"REFRN30223",));echo"</thead>\n";}echo"<tr".odd().">".(support("kill")?"<td>".checkbox("kill[]",$J[$w=="sql"?"Id":"pid"],0):"");foreach($J
as$x=>$X)echo"<td>".(($w=="sql"&&$x=="Info"&&preg_match("~Query|Killed~",$J["Command"])&&$X!="")||($w=="pgsql"&&$x=="current_query"&&$X!="<IDLE>")||($w=="oracle"&&$x=="sql_text"&&$X!="")?"<code class='jush-$w'>".shorten_utf8($X,100,"</code>").' <a href="'.h(ME.($J["db"]!=""?"db=".urlencode($J["db"])."&":"")."sql=".urlencode($X)).'">'.'Clone'.'</a>':h($X));echo"\n";}echo'</table>
</div>
<p>
';if(support("kill")){echo($r+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";}echo'<input type="hidden" name="token" value="',$si,'">
</form>
',script("tableCheck();");}elseif(isset($_GET["select"])){$a=$_GET["select"];$R=table_status1($a);$v=indexes($a);$n=fields($a);$md=column_foreign_keys($a);$of=$R["Oid"];parse_str($_COOKIE["adminer_import"],$za);$Wg=array();$e=array();$hi=null;foreach($n
as$x=>$m){$C=$b->fieldName($m);if(isset($m["privileges"]["select"])&&$C!=""){$e[$x]=html_entity_decode(strip_tags($C),ENT_QUOTES);if(is_shortable($m))$hi=$b->selectLengthProcess();}$Wg+=$m["privileges"];}list($L,$wd)=$b->selectColumnsProcess($e,$v);$ge=count($wd)<count($L)||strstr($L[0],"DISTINCT");$Z=$b->selectSearchProcess($n,$v);$Ef=$b->selectOrderProcess($n,$v);$y=$b->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Ji=>$J){$Ga=convert_field($n[key($J)]);$L=array($Ga?$Ga:idf_escape(key($J)));$Z[]=where_check($Ji,$n);$I=$k->select($a,$L,$Z,$L);if($I)echo
reset($I->fetch_row());}exit;}$qg=$Li=null;foreach($v
as$u){if($u["type"]=="PRIMARY"){$qg=array_flip($u["columns"]);$Li=($L?$qg:array());foreach($Li
as$x=>$X){if(in_array(idf_escape($x),$L))unset($Li[$x]);}break;}}if($of&&!$qg){$qg=$Li=array($of=>0);$v[]=array("type"=>"PRIMARY","columns"=>array($of));}if($_POST&&!$l){$mj=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$eb=array();foreach($_POST["check"]as$bb)$eb[]=where_check($bb,$n);$mj[]="((".implode(") OR (",$eb)."))";}$mj=($mj?"\nWHERE ".implode(" AND ",$mj):"");if($_POST["export"]){cookie("adminer_import","output=".urlencode($_POST["output"])."&format=".urlencode($_POST["format"]));dump_headers($a);$b->dumpTable($a,"");$rd=($L?implode(", ",$L):"*").convert_fields($e,$n,$L)."\nFROM ".table($a);$yd=($wd&&$ge?"\nGROUP BY ".implode(", ",$wd):"").($Ef?"\nORDER BY ".implode(", ",$Ef):"");if(!is_array($_POST["check"])||$qg)$G="SELECT $rd$mj$yd";else{$Hi=array();foreach($_POST["check"]as$X)$Hi[]="(SELECT".limit($rd,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n).$yd,1).")";$G=implode(" UNION ALL ",$Hi);}$b->dumpData($a,"table",$G);exit;}if(!$b->selectEmailProcess($Z,$md)){if($_POST["save"]||$_POST["delete"]){$H=true;$_a=0;$N=array();if(!$_POST["delete"]){foreach($e
as$C=>$X){$X=process_input($n[$C]);if($X!==null&&($_POST["clone"]||$X!==false))$N[idf_escape($C)]=($X!==false?$X:idf_escape($C));}}if($_POST["delete"]||$N){if($_POST["clone"])$G="INTO ".table($a)." (".implode(", ",array_keys($N)).")\nSELECT ".implode(", ",$N)."\nFROM ".table($a);if($_POST["all"]||($qg&&is_array($_POST["check"]))||$ge){$H=($_POST["delete"]?$k->delete($a,$mj):($_POST["clone"]?queries("INSERT $G$mj"):$k->update($a,$N,$mj)));$_a=$f->affected_rows;}else{foreach((array)$_POST["check"]as$X){$ij="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$n);$H=($_POST["delete"]?$k->delete($a,$ij,1):($_POST["clone"]?queries("INSERT".limit1($a,$G,$ij)):$k->update($a,$N,$ij,1)));if(!$H)break;$_a+=$f->affected_rows;}}}$Qe=lang(array('%d item has been affected.','%d items have been affected.'),$_a);if($_POST["clone"]&&$H&&$_a==1){$ue=last_id();if($ue)$Qe=sprintf('Item%s has been inserted.'," $ue");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page":""),$Qe,$H);if(!$_POST["delete"]){edit_form($a,$n,(array)$_POST["fields"],!$_POST["clone"]);page_footer();exit;}}elseif(!$_POST["import"]){if(!$_POST["val"])$l='Ctrl+click on a value to modify it.';else{$H=true;$_a=0;foreach($_POST["val"]as$Ji=>$J){$N=array();foreach($J
as$x=>$X){$x=bracket_escape($x,1);$N[idf_escape($x)]=(preg_match('~char|text~',$n[$x]["type"])||$X!=""?$b->processInput($n[$x],$X):"NULL");}$H=$k->update($a,$N," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($Ji,$n),!$ge&&!$qg," ");if(!$H)break;$_a+=$f->affected_rows;}queries_redirect(remove_from_uri(),lang(array('%d item has been affected.','%d items have been affected.'),$_a),$H);}}elseif(!is_string($bd=get_file("csv_file",true)))$l=upload_error($bd);elseif(!preg_match('~~u',$bd))$l='File must be in UTF-8 encoding.';else{cookie("adminer_import","output=".urlencode($za["output"])."&format=".urlencode($_POST["separator"]));$H=true;$pb=array_keys($n);preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$bd,$Ie);$_a=count($Ie[0]);$k->begin();$mh=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$K=array();foreach($Ie[0]as$x=>$X){preg_match_all("~((?>\"[^\"]*\")+|[^$mh]*)$mh~",$X.$mh,$Je);if(!$x&&!array_diff($Je[1],$pb)){$pb=$Je[1];$_a--;}else{$N=array();foreach($Je[1]as$r=>$kb)$N[idf_escape($pb[$r])]=($kb==""&&$n[$pb[$r]]["null"]?"NULL":q(str_replace('""','"',preg_replace('~^"|"$~','',$kb))));$K[]=$N;}}$H=(!$K||$k->insertUpdate($a,$K,$qg));if($H)$H=$k->commit();queries_redirect(remove_from_uri("page"),lang(array('%d row has been imported.','%d rows have been imported.'),$_a),$H);$k->rollback();}}}$Th=$b->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Th",$l);$N=null;if(isset($Wg["insert"])||!support("table")){$N="";foreach((array)$_GET["where"]as$X){if($md[$X["col"]]&&count($md[$X["col"]])==1&&($X["op"]=="="||(!$X["op"]&&!preg_match('~[_%]~',$X["val"]))))$N.="&set".urlencode("[".bracket_escape($X["col"])."]")."=".urlencode($X["val"]);}}$b->selectLinks($R,$N);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table'.($n?".":": ".error())."\n";else{echo"<form action='' id='form'>\n","<div style='display: none;'>";hidden_fields_get();echo(DB!=""?'<input type="hidden" name="db" value="'.h(DB).'">'.(isset($_GET["ns"])?'<input type="hidden" name="ns" value="'.h($_GET["ns"]).'">':""):"");echo'<input type="hidden" name="select" value="'.h($a).'">','<input type="submit" value="'.h('Select').'">';echo"</div>\n";$b->selectColumnsPrint($L,$e);$b->selectSearchPrint($Z,$e,$v);$b->selectOrderPrint($Ef,$e,$v);$b->selectLimitPrint($y);$b->selectLengthPrint($hi);$b->selectActionPrint($v);echo"</form>\n";$E=$_GET["page"];if($E=="last"){$pd=$f->result(count_rows($a,$Z,$ge,$wd));$E=floor(max(0,$pd-1)/$y);}$hh=$L;$xd=$wd;if(!$hh){$hh[]="*";$Fb=convert_fields($e,$n,$L);if($Fb)$hh[]=substr($Fb,2);}foreach($L
as$x=>$X){$m=$n[idf_unescape($X)];if($m&&($Ga=convert_field($m)))$hh[$x]="$Ga AS $X";}if(!$ge&&$Li){foreach($Li
as$x=>$X){$hh[]=idf_escape($x);if($xd)$xd[]=idf_escape($x);}}$H=$k->select($a,$hh,$Z,$xd,$Ef,$y,$E,true);if(!$H)echo"<p class='error'>".error()."\n";else{if($w=="mssql"&&$E)$H->seek($y*$E);$_c=array();echo"<form action='' method='post' enctype='multipart/form-data'>\n";$K=array();while($J=$H->fetch_assoc()){if($E&&$w=="oracle")unset($J["RNUM"]);$K[]=$J;}if($_GET["page"]!="last"&&$y!=""&&$wd&&$ge&&$w=="sql")$pd=$f->result(" SELECT FOUND_ROWS()");if(!$K)echo"<p class='message'>".'No rows.'."\n";else{$Pa=$b->backwardKeys($a,$Th);echo"<div class='scrollable'>","<table id='table' cellspacing='0' class='nowrap checkable'>",script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});"),"<thead><tr>".(!$wd&&$L?"":"<td><input type='checkbox' id='all-page' class='jsonly'>".script("qs('#all-page').onclick = partial(formCheck, /check/);","")." <a href='".h($_GET["modify"]?remove_from_uri("modify"):$_SERVER["REQUEST_URI"]."&modify=1")."' title='".'Modify'."' class='edit-all'>".'Modify'."</a>");$bf=array();$sd=array();reset($L);$Dg=1;foreach($K[0]as$x=>$X){if(!isset($Li[$x])){$X=$_GET["columns"][key($L)]??null;$m=$n[$L?($X?$X["col"]:current($L)):$x];$C=($m?$b->fieldName($m,$Dg):($X["fun"]?"*":$x));if($C!=""){$Dg++;$bf[$x]=$C;$d=idf_escape($x);$Kd=remove_from_uri('(order|desc)[^=]*|page').'&order%5B0%5D='.urlencode($x);$fc="&desc%5B0%5D=1";echo"<th id='th[".h(bracket_escape($x))."]'>".script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});",""),'<a href="'.h($Kd.($Ef[0]==$d||$Ef[0]==$x||(!$Ef&&$ge&&$wd[0]==$d)?$fc:'')).'">';echo
apply_sql_function($X["fun"]??null,$C)."</a>";echo"<span class='column hidden'>","<a href='".h($Kd.$fc)."' title='".'descending'."' class='text'> â†“</a>";if(isset($X["fun"])===false){echo'<a href="#fieldset-search" title="'.'Search'.'" class="text jsonly"> =</a>',script("qsl('a').onclick = partial(selectSearch, '".js_escape($x)."');");}echo"</span>";}$sd[$x]=$X["fun"]??null;next($L);}}$_e=array();if($_GET["modify"]){foreach($K
as$J){foreach($J
as$x=>$X)$_e[$x]=max($_e[$x],min(40,strlen(utf8_decode($X))));}}echo($Pa?"<th>".'Relations':"")."</thead>\n";if(is_ajax()){if($y%2==1&&$E%2==1)odd();ob_end_clean();}foreach($b->rowDescriptions($K,$md)as$af=>$J){$Ii=unique_array($K[$af],$v);if(!$Ii){$Ii=array();foreach($K[$af]as$x=>$X){if(!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~',$x))$Ii[$x]=$X;}}$Ji="";foreach($Ii
as$x=>$X){if(($w=="sql"||$w=="pgsql")&&preg_match('~char|text|enum|set~',$n[$x]["type"])&&strlen($X)>64){$x=(strpos($x,'(')?$x:idf_escape($x));$x="MD5(".($w!='sql'||preg_match("~^utf8~",$n[$x]["collation"])?$x:"CONVERT($x USING ".charset($f).")").")";$X=md5($X);}$Ji.="&".($X!==null?urlencode("where[".bracket_escape($x)."]")."=".urlencode($X===false?"f":$X):"null%5B%5D=".urlencode($x));}echo"<tr".odd().">".(!$wd&&$L?"":"<td>".checkbox("check[]",substr($Ji,1),in_array(substr($Ji,1),(array)$_POST["check"])).($ge||information_schema(DB)?"":" <a href='".h(ME."edit=".urlencode($a).$Ji)."' class='edit' title='".'edit'."'>".'edit'."</a>"));foreach($J
as$x=>$X){if(isset($bf[$x])){$m=$n[$x];$X=$k->value($X,$m);if($X!=""&&(!isset($_c[$x])||$_c[$x]!=""))$_c[$x]=(is_mail($X)?$bf[$x]:"");$z="";if(preg_match('~blob|bytea|raw|file~',$m["type"]??null)&&$X!="")$z=ME.'download='.urlencode($a).'&field='.urlencode($x).$Ji;if(!$z&&$X!==null){foreach((array)$md[$x]as$p){if(count($md[$x])==1||end($p["source"])==$x){$z="";foreach($p["source"]as$r=>$_h)$z.=where_link($r,$p["target"][$r],$K[$af][$_h]);$z=($p["db"]!=""?preg_replace('~([?&]db=)[^&]+~','\1'.urlencode($p["db"]),ME):ME).'select='.urlencode($p["table"]).$z;if($p["ns"])$z=preg_replace('~([?&]ns=)[^&]+~','\1'.urlencode($p["ns"]),$z);if(count($p["source"])==1)break;}}}if($x=="COUNT(*)"){$z=ME."select=".urlencode($a);$r=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Ii))$z.=where_link($r++,$W["col"],$W["val"],$W["op"]);}foreach($Ii
as$le=>$W)$z.=where_link($r++,$le,$W);}$X=select_value($X,$z,$m,$hi);$s=h("val[$Ji][".bracket_escape($x)."]");$Y=null;if(isset($_POST["val"][$Ji][bracket_escape($x)]))$_POST["val"][$Ji][bracket_escape($x)];$vc=!is_array($J[$x])&&is_utf8($X)&&$K[$af][$x]==$J[$x]&&!$sd[$x];$gi=preg_match('~text|lob~',$m["type"]??null);echo"<td id='$s'";if(($_GET["modify"]&&$vc)||$Y!==null){$Ad=h($Y!==null?$Y:$J[$x]);echo">".($gi?"<textarea name='$s' cols='30' rows='".(substr_count($J[$x],"\n")+1)."'>$Ad</textarea>":"<input name='$s' value='$Ad' size='$_e[$x]'>");}else{$De=strpos($X,"<i>â€¦</i>");echo" data-text='".($De?2:($gi?1:0))."'".($vc?"":" data-warning='".h('Use edit link to modify this value.')."'").">$X</td>";}}}if($Pa)echo"<td>";$b->backwardKeysPrint($Pa,$K[$af]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){if($K||$E){$Kc=true;if($_GET["page"]!="last"){if($y==""||(count($K)<$y&&($K||!$E)))$pd=($E?$E*$y:0)+count($K);elseif($w!="sql"||!$ge){$pd=($ge?false:found_rows($R,$Z));if($pd<max(1e4,2*($E+1)*$y))$pd=reset(slow_query(count_rows($a,$Z,$ge,$wd)));else$Kc=false;}}$Sf=($y!=""&&($pd===false||$pd>$y||$E));if($Sf){echo(($pd===false?count($K)+1:$pd-$E*$y)>$y?'<p><a href="'.h(remove_from_uri("page")."&page=".($E+1)).'" class="loadmore">'.'Load more data'.'</a>'.script("qsl('a').onclick = partial(selectLoadMore, ".(+$y).", '".'Loading'."â€¦');",""):''),"\n";}}echo"<div class='footer'><div>\n";if($K||$E){if($Sf){$Le=($pd===false?$E+(count($K)>=$y?2:1):floor(($pd-1)/$y));echo"<fieldset>";if($w!="simpledb"){echo"<legend><a href='".h(remove_from_uri("page"))."'>".'Page'."</a></legend>",script("qsl('a').onclick = function () { pageClick(this.href, +prompt('".'Page'."', '".($E+1)."')); return false; };"),pagination(0,$E).($E>5?" â€¦":"");for($r=max(1,$E-4);$r<min($Le,$E+5);$r++)echo
pagination($r,$E);if($Le>0){echo($E+5<$Le?" â€¦":""),($Kc&&$pd!==false?pagination($Le,$E):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Le'>".'last'."</a>");}}else{echo"<legend>".'Page'."</legend>",pagination(0,$E).($E>1?" â€¦":""),($E?pagination($E,$E):""),($Le>$E?pagination($E+1,$E).($Le>$E+1?" â€¦":""):"");}echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$kc=($Kc?"":"~ ").$pd;echo
checkbox("all",1,0,($pd!==false?($Kc?"":"~ ").lang(array('%d row','%d rows'),$pd):""),"var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$kc' : checked); selectCount('selected2', this.checked || !checked ? '$kc' : checked);")."\n","</fieldset>\n";if($b->selectCommandPrint()){echo'<fieldset',($_GET["modify"]?'':' class="jsonly"'),'><legend>Modify</legend><div>
<input type="submit" value="Save"',($_GET["modify"]?'':' title="'.'Ctrl+click on a value to modify it.'.'"'),'>
</div></fieldset>
<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type="submit" name="edit" value="Edit">
<input type="submit" name="clone" value="Clone">
<input type="submit" name="delete" value="Delete">',confirm(),'</div></fieldset>
';}$nd=$b->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]){unset($nd['sql']);break;}}if($nd){print_fieldset("export",'Export'." <span id='selected2'></span>");$Pf=$b->dumpOutput();echo($Pf?html_select("output",$Pf,$za["output"])." ":""),html_select("format",$nd,$za["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}$b->selectEmailPrint(array_filter($_c,'strlen'),$e);}echo"</div></div>\n";if($b->selectImportPrint()){echo"<div>","<a href='#import'>".'Import'."</a>",script("qsl('a').onclick = partial(toggle, 'import');",""),"<span id='import' class='hidden'>: ","<input type='file' name='csv_file'> ",html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$za["format"],1);echo" <input type='submit' name='import' value='".'Import'."'>","</span>","</div>";}echo"<input type='hidden' name='token' value='$si'>\n","</form>\n",(!$wd&&$L?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$O=isset($_GET["status"]);page_header($O?'Status':'Variables');$Zi=($O?show_status():show_variables());if(!$Zi)echo"<p class='message'>".'No rows.'."\n";else{echo"<table cellspacing='0'>\n";foreach($Zi
as$x=>$X){echo"<tr>","<th><code class='jush-".$w.($O?"status":"set")."'>".h($x)."</code>","<td>".h($X);}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: text/javascript; charset=utf-8");if($_GET["script"]=="db"){$Qh=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$C=>$R){json_row("Comment-$C",h($R["Comment"]));if(!is_view($R)){foreach(array("Engine","Collation")as$x)json_row("$x-$C",h($R[$x]));foreach($Qh+array("Auto_increment"=>0,"Rows"=>0)as$x=>$X){if($R[$x]!=""){$X=format_number($R[$x]);json_row("$x-$C",($x=="Rows"&&$X&&$R["Engine"]==($w=="pgsql"?"table":"InnoDB")?"~ $X":$X));if(isset($Qh[$x]))$Qh[$x]+=($R["Engine"]!="InnoDB"||$x!="Data_free"?$R[$x]:0);}elseif(array_key_exists($x,$R))json_row("$x-$C");}}}foreach($Qh
as$x=>$X)json_row("sum-$x",format_number($X));json_row("");}elseif($_GET["script"]=="kill")$f->query("KILL ".number($_POST["kill"]));else{foreach(count_tables($b->databases())as$j=>$X){json_row("tables-$j",$X);json_row("size-$j",db_size($j));}json_row("");}exit;}else{$Zh=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Zh&&!$l&&!$_POST["search"]){$H=true;$Qe="";if($w=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$H=truncate_tables($_POST["tables"]);$Qe='Tables have been truncated.';}elseif($_POST["move"]){$H=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Qe='Tables have been moved.';}elseif($_POST["copy"]){$H=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$Qe='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$H=drop_views($_POST["views"]);if($H&&$_POST["tables"])$H=drop_tables($_POST["tables"]);$Qe='Tables have been dropped.';}elseif($w!="sql"){$H=($w=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?"":" ANALYZE"),$_POST["tables"]));$Qe='Tables have been optimized.';}elseif(!$_POST["tables"])$Qe='No tables.';elseif($H=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('idf_escape',$_POST["tables"])))){while($J=$H->fetch_assoc())$Qe.="<b>".h($J["Table"])."</b>: ".h($J["Msg_text"])."<br>";}queries_redirect(substr(ME,0,-1),$Qe,$H);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$l,true);if($b->homepage()){if($_GET["ns"]!==""){echo"<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Yh=tables_list();if(!$Yh)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>","<input type='search' name='query' value='".h($_POST["query"])."'>",script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');","")," <input type='submit' name='search' value='".'Search'."'>\n";if($b->operator_regexp!==null){echo"<p><label><input type='checkbox' name='regexp' value='1'".(empty($_POST['regexp'])?'':' checked').'>'.'as a regular expression'.'</label>',doc_link(array('sql'=>'regexp.html','pgsql'=>'functions-matching.html#FUNCTIONS-POSIX-REGEXP'))."</p>\n";}echo"</div></fieldset>\n";if($_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]=$b->operator_regexp===null||empty($_POST['regexp'])?"LIKE %%":$b->operator_regexp;search_tables();}}echo"<div class='scrollable'>\n","<table cellspacing='0' class='nowrap checkable'>\n",script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});"),'<thead><tr class="wrap">','<td><input id="check-all" type="checkbox" class="jsonly">'.script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);",""),'<th>'.'Table','<td>'.'Engine'.doc_link(array('sql'=>'storage-engines.html')),'<td>'.'Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')),'<td>'.'Data Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'REFRN20286')),'<td>'.'Index Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT')),'<td>'.'Data Free'.doc_link(array('sql'=>'show-table-status.html')),'<td>'.'Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),'<td>'.'Rows'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'REFRN20286')),(support("comment")?'<td>'.'Comment'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE')):''),"</thead>\n";$S=0;foreach($Yh
as$C=>$T){$cj=($T!==null&&!preg_match('~table|sequence~i',$T));$s=h("Table-".$C);echo'<tr'.odd().'><td>'.checkbox(($cj?"views[]":"tables[]"),$C,in_array($C,$Zh,true),"","","",$s),'<th>'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".urlencode($C)."' title='".'Show structure'."' id='$s'>".h($C).'</a>':h($C));if($cj){echo'<td colspan="6"><a href="'.h(ME)."view=".urlencode($C).'" title="'.'Alter view'.'">'.(preg_match('~materialized~i',$T)?'Materialized view':'View').'</a>','<td align="right"><a href="'.h(ME)."select=".urlencode($C).'" title="'.'Select data'.'">?</a>';}else{foreach(array("Engine"=>array(),"Collation"=>array(),"Data_length"=>array("create",'Alter table'),"Index_length"=>array("indexes",'Alter indexes'),"Data_free"=>array("edit",'New item'),"Auto_increment"=>array("auto_increment=1&create",'Alter table'),"Rows"=>array("select",'Select data'),)as$x=>$z){$s=" id='$x-".h($C)."'";echo($z?"<td align='right'>".(support("table")||$x=="Rows"||(support("indexes")&&$x!="Data_length")?"<a href='".h(ME."$z[0]=").urlencode($C)."'$s title='$z[1]'>?</a>":"<span$s>?</span>"):"<td id='$x-".h($C)."'>");}$S++;}echo(support("comment")?"<td id='Comment-".h($C)."'>":"");}echo"<tr><td><th>".sprintf('%d in total',count($Yh)),"<td>".h($w=="sql"?$f->result("SELECT @@default_storage_engine"):""),"<td>".h(db_collation(DB,collations()));foreach(array("Data_length","Index_length","Data_free")as$x)echo"<td align='right' id='sum-$x'>";echo"</table>\n","</div>\n";if(!information_schema(DB)){echo"<div class='footer'><div>\n";$Wi="<input type='submit' value='".'Vacuum'."'> ".on_help("'VACUUM'");$Bf="<input type='submit' name='optimize' value='".'Optimize'."'> ".on_help($w=="sql"?"'OPTIMIZE TABLE'":"'VACUUM OPTIMIZE'");echo"<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>".($w=="sqlite"?$Wi:($w=="pgsql"?$Wi.$Bf:($w=="sql"?"<input type='submit' value='".'Analyze'."'> ".on_help("'ANALYZE TABLE'").$Bf."<input type='submit' name='check' value='".'Check'."'> ".on_help("'CHECK TABLE'")."<input type='submit' name='repair' value='".'Repair'."'> ".on_help("'REPAIR TABLE'"):"")))."<input type='submit' name='truncate' value='".'Truncate'."'> ".on_help($w=="sqlite"?"'DELETE'":"'TRUNCATE".($w=="pgsql"?"'":" TABLE'")).confirm()."<input type='submit' name='drop' value='".'Drop'."'>".on_help("'DROP TABLE'").confirm()."\n";$i=(support("scheme")?$b->schemas():$b->databases());if(count($i)!=1&&$w!="sqlite"){$j=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo"<p>".'Move to other database'.": ",($i?html_select("target",$i,$j):'<input name="target" value="'.h($j).'" autocapitalize="off">')," <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"\n";}echo"<input type='hidden' name='all' value=''>";echo
script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));".(support("table")?" selectCount('selected2', formChecked(this, /^tables\[/) || $S);":"")." }"),"<input type='hidden' name='token' value='$si'>\n","</div></fieldset>\n","</div></div>\n";}echo"</form>\n",script("tableCheck();");}$_=[];$_[]="<a href='".h(ME)."create='>".'Create table'."</a>";if(support("view"))$_[]="<a href='".h(ME)."view='>".'Create view'."</a>";echo
generate_linksbar($_);if(support("routine")){echo"<h3 id='routines'>".'Routines'."</h3>\n";$ah=routines();if($ah){echo"<table cellspacing='0'>\n",'<thead><tr><th>'.'Name'.'<td>'.'Type'.'<td>'.'Return type'."<td></thead>\n";odd('');foreach($ah
as$J){$C=($J["SPECIFIC_NAME"]==$J["ROUTINE_NAME"]?"":"&name=".urlencode($J["ROUTINE_NAME"]));echo'<tr'.odd().'>','<th><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.h($J["ROUTINE_NAME"]).'</a>','<td>'.h($J["ROUTINE_TYPE"]),'<td>'.h($J["DTD_IDENTIFIER"]),'<td><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').urlencode($J["SPECIFIC_NAME"]).$C).'">'.'Alter'."</a>";}echo"</table>\n";}$_=[];if(support('procedure'))$_[]="<a href='".h(ME)."procedure='>".'Create procedure'."</a>";$_[]="<a href='".h(ME)."function='>".'Create function'."</a>";echo
generate_linksbar($_);}if(support("sequence")){echo"<h3 id='sequences'>".'Sequences'."</h3>\n";$oh=get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");if($oh){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($oh
as$X)echo"<tr".odd()."><th><a href='".h(ME)."sequence=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo
generate_linksbar(["<a href='".h(ME)."sequence='>".'Create sequence'."</a>"]);}if(support("type")){echo"<h3 id='user-types'>".'User types'."</h3>\n";$Ui=types();if($Ui){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."</thead>\n";odd('');foreach($Ui
as$X)echo"<tr".odd()."><th><a href='".h(ME)."type=".urlencode($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo
generate_linksbar(["<a href='".h(ME)."type='>".'Create type'."</a>"]);}if(support("event")){echo"<h3 id='events'>".'Events'."</h3>\n";$K=get_rows("SHOW EVENTS");if($K){echo"<table cellspacing='0'>\n","<thead><tr><th>".'Name'."<td>".'Schedule'."<td>".'Start'."<td>".'End'."<td></thead>\n";foreach($K
as$J){echo"<tr>","<th>".h($J["Name"]),"<td>".($J["Execute at"]?'At given time'."<td>".$J["Execute at"]:'Every'." ".$J["Interval value"]." ".$J["Interval field"]."<td>$J[Starts]"),"<td>$J[Ends]",'<td><a href="'.h(ME).'event='.urlencode($J["Name"]).'">'.'Alter'.'</a>';}echo"</table>\n";$Ic=$f->result("SELECT @@event_scheduler");if($Ic&&$Ic!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Ic)."\n";}echo
generate_linksbar(["<a href='".h(ME)."event='>".'Create event'."</a>"]);}if($Yh)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}}}page_footer();
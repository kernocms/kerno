<?php

/*
 * Copyright (C) 2006-2018 Kerno CMS
 *
 * Name: functions.php
 * Description: Common system functions
 *
 * @author Vitaly Ponomarev
 * @author Alexey Zinchenko
 * @author Dmitry Ryzhkov
 *
*/

// Protect against hack attempts
if (!defined('KERNO')) die ('HAL');

//
// SQL security string escape
//
function db_squote($string) {

	global $mysql;
	if (is_array($string)) {
		return false;
	}

	return "'" . $mysql->db_quote($string) . "'";
}

function db_dquote($string) {

	global $mysql;
	if (is_array($string)) {
		return false;
	}

	return '"' . $mysql->db_quote($string) . '"';
}

//
// HTML & special symbols protection
//
function secure_html($string) {

	if (is_array($string)) {
		return '[UNEXPECTED ARRAY]';
	}

	return str_replace(array("{", "<", ">"), array("&#123;", "&lt;", "&gt;"), htmlspecialchars($string, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
}

function Formatsize($file_size) {

	if ($file_size >= 1073741824) {
		$file_size = round($file_size / 1073741824 * 100) / 100 . " Gb";
	} elseif ($file_size >= 1048576) {
		$file_size = round($file_size / 1048576 * 100) / 100 . " Mb";
	} elseif ($file_size >= 1024) {
		$file_size = round($file_size / 1024 * 100) / 100 . " Kb";
	} else {
		$file_size = $file_size . " b";
	}

	return $file_size;
}

function checkIP() {

	if (getenv("REMOTE_ADDR")) {
		return getenv("REMOTE_ADDR");
	} elseif ($_SERVER["REMOTE_ADDR"]) {
		return $_SERVER['REMOTE_ADDR'];
	}

	return "unknown";
}

function initGZipHandler() {

	global $config;

	if ($config['use_gzip'] == "1" && extension_loaded('zlib') && function_exists('ob_gzhandler')) {
		@ob_start('ob_gzhandler');
	}
}

// Generate BACKUP of DB
// * $delayed - flag if call should be delayed for 30 mins (for cases of SYSCRON / normal calls)
function AutoBackup($delayed = false, $force = false) {

	global $config;

	$backupFlagFile = root . "cache/last_backup.tmp";
	$backupMarkerFile = root . "cache/last_backup_marker.tmp";

	// Load `Last Backup Date` from $backupFlagFile
	$last_backup = intval(@file_get_contents($backupFlagFile));
	$time_now = time();

	// Force backup if requested
	if ($force) {
		$last_backup = 0;
	}

	// Check if last backup was too much time ago
	if ($time_now > ($last_backup + $config['auto_backup_time'] * 3600 + ($delayed ? 30 * 60 : 0))) {
		// Yep, we need a backup.
		// ** Manage marker file
		$flagDoProcess = false;

		// -> Try to create marker
		if (($fm = fopen($backupMarkerFile, 'x')) !== false) {
			// Created, write CALL time
			fwrite($fm, $time_now);
			fclose($fm);

			$flagDoProcess = true;
		} else {
			// Marker already exists, check creation time
			$markerTime = intval(@file_get_contents($backupMarkerFile));

			// TTL for marker is 5 min
			if ($time_now > ($markerTime + 180)) {
				// Delete OLD marker, create ours
				if (unlink($backupMarkerFile) && (($fm = fopen($backupMarkerFile, 'x')) !== false)) {
					// Created, write CALL time
					fwrite($fm, $time_now);
					fclose($fm);

					$flagDoProcess = true;
				}
			}
		}

		// Do not run if another session is running
		if (!$flagDoProcess) {
			return;
		}

		// Try to open temp file for writing
		$fx = is_file($backupFlagFile) ? @fopen($backupFlagFile, "r+") : @fopen($backupFlagFile, "w+");
		if ($fx) {
			$filename = root . "backups/backup_" . date("Y_m_d_H_i", $time_now) . ".gz";

			// Load library
			require_once(root . '/includes/inc/lib_admin.php');

			// We need to create file with backup
			dbBackup($filename, 1);

			rewind($fx);
			fwrite($fx, $time_now);
			ftruncate($fx, ftell($fx));
		}

		// Delete marker
		@unlink($backupMarkerFile);
	}
}

function LangDate($format, $timestamp) {
	global $lang;

	$weekdays = explode(",", $lang['weekdays']);
	$short_weekdays = explode(",", $lang['short_weekdays']);
	$months = explode(",", $lang['months']);
	$months_s = explode(",", $lang['months_s']);
	$short_months = explode(",", $lang['short_months']);

	foreach ($weekdays as $name => $value)
		$weekdays[$name] = preg_replace("/./", "\\\\\\0", $value);

	foreach ($short_weekdays as $name => $value)
		$short_weekdays[$name] = preg_replace("/./", "\\\\\\0", $value);

	foreach ($months as $name => $value)
		$months[$name] = preg_replace("/./", "\\\\\\0", $value);

	foreach ($months_s as $name => $value)
		$months_s[$name] = preg_replace("/./", "\\\\\\0", $value);

	foreach ($short_months as $name => $value)
		$short_months[$name] = preg_replace("/./", "\\\\\\0", $value);

	$format = @preg_replace("/(?<!\\\\)D/", $short_weekdays[date("w", $timestamp)], $format);
	$format = @preg_replace("/(?<!\\\\)F/", $months[date("n", $timestamp) - 1], $format);
	$format = @preg_replace("/(?<!\\\\)Q/", $months_s[date("n", $timestamp) - 1], $format);
	$format = @preg_replace("/(?<!\\\\)l/", $weekdays[date("w", $timestamp)], $format);
	$format = @preg_replace("/(?<!\\\\)M/", $short_months[date("n", $timestamp) - 1], $format);

	return @date($format, $timestamp);
}

function LangDatetime($format, $datetime) {
    global $lang;

    $weekdays = explode(",", $lang['weekdays']);
    $short_weekdays = explode(",", $lang['short_weekdays']);
    $months = explode(",", $lang['months']);
    $months_s = explode(",", $lang['months_s']);
    $short_months = explode(",", $lang['short_months']);

    foreach ($weekdays as $name => $value)
        $weekdays[$name] = preg_replace("/./", "\\\\\\0", $value);

    foreach ($short_weekdays as $name => $value)
        $short_weekdays[$name] = preg_replace("/./", "\\\\\\0", $value);

    foreach ($months as $name => $value)
        $months[$name] = preg_replace("/./", "\\\\\\0", $value);

    foreach ($months_s as $name => $value)
        $months_s[$name] = preg_replace("/./", "\\\\\\0", $value);

    foreach ($short_months as $name => $value)
        $short_months[$name] = preg_replace("/./", "\\\\\\0", $value);

    $date = new \DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone( new \DateTimeZone(date_default_timezone_get()) );

    $format = @preg_replace("/(?<!\\\\)D/", $short_weekdays[ $date->format('w') ], $format);
    $format = @preg_replace("/(?<!\\\\)F/", $months[ $date->format('n') - 1 ], $format);
    $format = @preg_replace("/(?<!\\\\)Q/", $months_s[ $date->format('n') - 1 ], $format);
    $format = @preg_replace("/(?<!\\\\)l/", $weekdays[ $date->format('w') ], $format);
    $format = @preg_replace("/(?<!\\\\)M/", $short_months[ $date->format('n') - 1 ], $format);

    return $date->format($format);
}

//
// Generate a list of smilies to show
function InsertSmilies($insert_location, $break_location = false, $area = false) {

	global $config, $tpl;

	if ($config['use_smilies']) {
		$smilies = explode(",", $config['smilies']);

		// For smilies in comments, try to use 'smilies.tpl' from site template
		$templateDir = (($insert_location == 'comments') && is_readable(tpl_dir . $config['theme'] . '/smilies.tpl')) ? tpl_dir . $config['theme'] : tpl_actions;

		$i = 0;
		$output = '';
		foreach ($smilies as $null => $smile) {
			$i++;
			$smile = trim($smile);

			$tvars['vars'] = array(
				'area'  => $area ? $area : "''",
				'smile' => $smile
			);

			$tpl->template('smilies', $templateDir);
			$tpl->vars('smilies', $tvars);
			$output .= $tpl->show('smilies');

			if (($break_location > 0) && (!$i % $break_location)) {
				$output .= "<br />";
			} else {
				$output .= "&nbsp;";
			}
		}

		return $output;
	}
}

function phphighlight($content = '') {

	$f = array('<br>', '<br />', '<p>', '&lt;', '&gt;', '&amp;', '&#124;', '&quot;', '&#036;', '&#092;', '&#039;', '&nbsp;', '\"');
	$r = array("\n", "\n", "\n", '<', '>', '&', '\|', '"', '$', '', '\'', '', '"');
	$content = str_replace($f, $r, $content);
	$content = highlight_string($content, true);

	return $content;
}

function QuickTags($area = false, $template = false) {

	global $tpl, $PHP_SELF;

	$tvars['vars'] = array(
		'php_self' => $PHP_SELF,
		'area'     => $area ? $area : "''"
	);

	if (!in_array($template, array('pmmes', 'editcom', 'news', 'static')))
		return false;

	$tplname = 'qt_' . $template;

	$tpl->template($tplname, tpl_actions);
	$tpl->vars($tplname, $tvars);

	return $tpl->show($tplname);
}

function BBCodes($area = false) {

	global $config, $lang, $tpl, $PHP_SELF;

	if ($config['use_bbcodes'] == "1") {
		$tvars['vars'] = array(
			'php_self' => $PHP_SELF,
			'area'     => $area
		);

		$tpl->template('bbcodes', tpl_site);
		$tpl->vars('bbcodes', $tvars);

		return $tpl->show('bbcodes');
	}
}

function Padeg($n, $s) {

	$n = abs($n);
	$a = explode(",", $s);
	$l1 = $n - ((int)($n / 10)) * 10;
	$l2 = $n - ((int)($n / 100)) * 100;

	if ("11" <= $l2 && $l2 <= "14") {
		$e = $a[2];
	} else {
		if ($l1 == "1") {
			$e = $a[0];
		}

		if ("2" <= $l1 && $l1 <= "4") {
			$e = $a[1];
		}

		if (("5" <= $l1 && $l1 <= "9") || $l1 == "0") {
			$e = $a[2];
		}
	}

	if ($e == "") {
		$e = $a[0];
	}

	return ($e);
}

//
// Perform BAN check
// $ip		- IP address of user
// $act		- action type ( 'users', 'comments', 'news',... )
// $subact	- subaction type ( for comments this may be 'add' )
// $userRec	- record of user (in case of logged in)
// $name	- name entered by user (in case it was entered)
function checkBanned($ip, $act, $subact, $userRec, $name) {

	global $mysql;

	$check_ip = sprintf("%u", ip2long($ip));

	// Currently we use limited mode. Try to find row
	if ($ban_row = $mysql->record("select * from " . prefix . "_ipban where addr_start <= " . db_squote($check_ip) . " and addr_stop >= " . db_squote($check_ip) . " order by netlen limit 1")) {
		// Row is found. Let's check for event type. STATIC CONVERSION
		$mode = 0;
		if (($act == 'users') && ($subact == 'register')) {
			$mode = 1;
		} else if (($act == 'users') && ($subact == 'auth')) {
			$mode = 2;
		} else if (($act == 'comments') && ($subact == 'add')) {
			$mode = 3;
		}
		if (($locktype = intval(mb_substr($ban_row['flags'], $mode, 1))) > 0) {
			$mysql->query("update " . prefix . "_ipban set hitcount=hitcount+1 where id=" . db_squote($ban_row['id']));

			return $locktype;
		}
	}

	return 0;
}

//
// Perform FLOOD check
// $mode	- WORKING MODE ( 0 - check only, 1 - update )
// $ip		- IP address of user
// $act		- action type ( 'comments', 'news',... )
// $subact	- subaction type ( for comments this may be 'add' )
// $userRec	- record of user (in case of logged in)
// $name	- name entered by user (in case it was entered)
function checkFlood($mode, $ip, $act, $subact, $userRec, $name) {

	global $mysql, $config;

	// Return if flood protection is disabled
	if (!$config['flood_time']) {
		return 0;
	}

	$this_time = time() + ($config['date_adjust'] * 60) - $config['flood_time'];

	// If UPDATE mode is used - update data
	if ($mode) {
		$this_time = time() + ($config['date_adjust'] * 60);
		$mysql->query("insert into " . prefix . "_flood (ip, id) values (" . db_squote($ip) . ", " . db_squote($this_time) . ") on duplicate key update id=" . db_squote($this_time));

		return 0;
	}

	// Delete expired records
	$mysql->query("DELETE FROM " . prefix . "_flood WHERE id < " . db_squote($this_time));

	// Check if we have record
	if ($mysql->record("SELECT * FROM " . prefix . "_flood WHERE id > " . db_squote($this_time) . " AND ip = " . db_squote($ip) . " limit 1")) {
		// Flood found
		return 1;
	}

	return 0;
}

function zzMail($to, $subject, $message, $filename = false, $mail_from = false, $ctype = 'text/html') {

	sendEmailMessage($to, $subject, $message, $filename, $mail_from, $ctype);
}

function sendEmailMessage($to, $subject, $message, $filename = false, $mail_from = false, $ctype = 'text/html') {

	global $lang, $config;

	// Include new PHP mailer class
	@include_once root . 'includes/classes/phpmailer/PHPMailerAutoload.php';
	$mail = new phpmailer;

	$mail->CharSet = 'UTF-8';

	// Fill `sender` field
	$mail->FromName = 'Mailbot '.str_replace("www.", "", $_SERVER['SERVER_NAME']);
	if ($config['mailfrom_name']) {
		$mail->FromName = $config['mailfrom_name'];
	}
	if ($mail_from) {
		$mail->From = $mail_from;
	} else if ($config['mailfrom']) {
		$mail->From = $config['mailfrom'];
	} else {
		$mail->From = "mailbot@" . str_replace("www.", "", $_SERVER['SERVER_NAME']);
	}

	$mail->Subject = $subject;
	$mail->Body = $message;
	$mail->ContentType = $ctype;
	$mail->AddAddress($to, $to);
	if (($filename !== false) && (is_file($filename))) {
		$mail->AddAttachment($filename);
	}

	// Select delivery transport
	switch ($config['mail_mode']) {
		default:
		case 'mail':
			$mail->isMail();
			break;
		case 'sendmail':
			$mail->isSendmail();
			break;
		case 'smtp':
			if (!$config['mail']['smtp']['host'] || !$config['mail']['smtp']['port']) {
				$mail->isMail();
				break;
			}
			$mail->isSMTP();
			$mail->Host = $config['mail']['smtp']['host'];
			$mail->Port = $config['mail']['smtp']['port'];
			$mail->SMTPAuth = ($config['mail']['smtp']['auth']) ? true : false;
			$mail->Username = $config['mail']['smtp']['login'];
			$mail->Password = $config['mail']['smtp']['pass'];
			$mail->SMTPSecure = $config['mail']['smtp']['secure'];
			break;
	}

	return $mail->Send();
}

//
// Generate info / error message
// $mode - working mode
//			0 - use SITE template
//			1 - use ADMIN PANEL template
// $disp - flag [display mode]:
//		   -1 - automatic mode
//			0 - add into mainblock
//			1 - print
//			2 - return as result
function msg($params, $mode = 0, $disp = -1) {

	global $config, $tpl, $lang, $template, $PHP_SELF, $TemplateCache, $notify;

	// Set AUTO mode if $disp == -1
	if ($disp == -1)
		$mode = ($PHP_SELF == 'admin.php') ? 1 : 0;

	if (!templateLoadVariables(false, $mode)) {
		die('Internal system error: ' . var_export($params, true));
	}

	// Choose working mode
	$type = 'msg.common';
	switch (getIsSet($params['type'])) {
		case 'error':
			$type = 'msg.error' . (isset($params['info']) ? '_info' : '');
			break;
		case 'info':
			$type = 'msg.info';
			break;
		default:
			$type = 'msg.common' . (isset($params['info']) ? '_info' : '');
			break;
	}
	$tmvars = array(
		'vars' => array(
			'text' => isset($params['text']) ? $params['text'] : '',
			'info' => isset($params['info']) ? $params['info'] : '',
		)
	);
	$message = $tpl->vars($TemplateCache[$mode ? 'admin' : 'site']['#variables']['messages'][$type], $tmvars, array('inline' => true));

	switch ($disp) {
		case 0:
			$template['vars']['mainblock'] .= $message;
			break;
		case 1:
			print $message;
			break;
		case 2:
			return $message;
		default:
			if ($PHP_SELF == 'admin.php') {
				$notify = $message;
			} else {
				$template['vars']['mainblock'] .= $message;
			}
			break;
	}
}

// Generate popup sticker with information block
// $msg - Message to display
// 		* TEXT - message text will be displayed
//		* ARRAY - array with (TEXT, STYLE, [noSecureFlag]) for multiple messages
// $type - message type ['', 'error']
// $disp - flag [display mode]:
//		   -1 - automatic mode
//			0 - add into mainblock
//			1 - print
//			2 - return as result
function msgSticker($msg, $type = '', $disp = -1) {
	global $notify;
	
	$lines = array();
	if (is_array($msg)) {
		foreach ($msg as $x) {
			$txt = (isset($x[2]) && ($x[2])) ? $x[0] : htmlspecialchars($x[0], ENT_COMPAT | ENT_HTML401, "UTF-8");
			$lines [] = (isset($x[1]) && ($x[1] == 'title')) ? ('<b>' . $txt . '</b>') : $txt;
		}
	} else {
		$lines [] = htmlspecialchars($msg, ENT_COMPAT | ENT_HTML401, "UTF-8");
	}

	$output = '<script type="text/javascript" language="javascript">ngNotifySticker("' .
		join("<br/>", $lines) . '"' .
		(($type == "error") ? ', {sticked: true, className: "ngStickerClassError"}' : '') .
		');</script>';
	$notify = $output;
}

function DirSize($directory) {

	if (!is_dir($directory)) return -1;
	$size = 0;

	if ($dir = opendir($directory)) {
		while (($dirfile = readdir($dir)) !== false) {
			if (is_link($directory . '/' . $dirfile) || $dirfile == '.' || $dirfile == '..') {
				continue;
			}
			if (is_file($directory . '/' . $dirfile)) {
				$size += filesize($directory . '/' . $dirfile);
			} elseif (is_dir($directory . '/' . $dirfile)) {
				$dirSize = dirsize($directory . '/' . $dirfile);
				if ($dirSize >= 0) {
					$size += $dirSize;
				} else {
					return -1;
				}
			}
		}
		closedir($dir);
	}

	return $size;
}

// Scans directory and returns it's size and file count
// Return array with size, count
function directoryWalk($dir, $blackmask = null, $whitemask = null, $returnFiles = true, $execTimeLimit = 0) {

	$tStart = microtime(true);
	if (!is_dir($dir)) return array(-1, -1);

	$size = 0;
	$count = 0;
	$flag = 0;
	$path = array($dir);
	$wpath = array();
	$files = array();
	$od = array();
	$dfile = array();
	$od[1] = opendir($dir);

	while (count($path)) {
		if (($count % 100) == 0) {
			$tNow = microtime(true);
			if (($execTimeLimit > 0) && (($tNow - $tStart) >= $execTimeLimit)) {
				return array($size, $count, $files, true);

			}
		}

		$level = count($path);
		$sd = join("/", $path);
		$wsd = join("/", $wpath);
		while (($dfile[$level] = readdir($od[$level])) !== false) {
			if (is_link($sd . '/' . $dfile[$level]) || $dfile[$level] == '.' || $dfile[$level] == '..')
				continue;

			if (is_file($sd . '/' . $dfile[$level])) {
				// Check for black list

				$size += filesize($sd . '/' . $dfile[$level]);
				if ($returnFiles)
					$files [] = ($wsd ? $wsd . '/' : '') . $dfile[$level];
				$count++;
			} elseif (is_dir($sd . '/' . $dfile[$level])) {
				array_push($path, $dfile[$level]);
				array_push($wpath, $dfile[$level]);
				$od[$level + 1] = opendir(join("/", $path));
				$flag = 1;
				break;
			}
		}
		if ($flag) {
			$flag = 0;
			continue;
		}
		array_pop($path);
		array_pop($wpath);
	}

	return array($size, $count, $files, false);
}

function OrderList($value, $showDefault = false) {

	global $lang, $catz;

	$output = "<select name=\"orderby\">\n";
	if ($showDefault)
		$output .= '<option value="">' . $lang['order_default'];
	foreach (array('id desc', 'id asc', 'postdate desc', 'postdate asc', 'title desc', 'title asc', 'rating desc', 'rating asc') as $v) {
		$vx = str_replace(' ', '_', $v);
		$output .= '<option value="' . $v . '"' . (($value == $v) ? ' selected="selected"' : '') . '>' . $lang["order_$vx"] . "</option>\n";
	}
	$output .= "</select>\n";

	return $output;
}

function ChangeDate($time = 0, $nodiv = 0) {
	global $lang, $langShortMonths;

	if ($time <= 0) {
		$time = time();
	}

	$result = $nodiv ? '' : '<div id="cdate">';
	$result .= '<select name="c_day">';
	for ($i = 1; $i <= 31; $i++)
		$result .= '<option value="' . $i . '"' . ((date('j', $time) == $i) ? ' selected="selected"' : '') . '>' . $i . '</option>';

	$result .= '</select><select id="c_month" name="c_month">';

	foreach ($langShortMonths as $k => $v)
		$result .= '<option value="' . ($k + 1) . '"' . ((date('n', $time) == ($k + 1)) ? ' selected="selected"' : '') . '>' . $v . '</option>';

	$result .= '</select>
	<input type="text" id="c_year" name="c_year" size="4" maxlength="4" value="' . date('Y', $time) . '" />
	<input type="text" id="c_hour" name="c_hour" size="2" maxlength="2" value="' . date('H', $time) . '" /> :
	<input type="text" id="c_minute" name="c_minute" size="2" maxlength="2" value="' . date('i', $time) . '" />';
	if (!$nodiv) {
		$result .= '</div>';
	}

	return $result;
}

//
// Return a list of files
// $path		- путь по которому искать файлы
// $ext			- [scalar/array] расширение (одно или массивом) файла
// $showExt		- флаг: показывать ли расширение [0 - нет, 1 - показывать, 2 - использовать в значениях]
// $silentError		- не выводить сообщение об ошибке
// $returnNullOnError	- возвращать NULL при ошибке
function ListFiles($path, $ext, $showExt = 0, $silentError = 0, $returnNullOnError = 0) {

	$list = array();
	if (!is_array($ext))
		$ext = array($ext);

	if (!($handle = opendir($path))) {
		if (!$silentError)
			echo "<p>ListFiles($path) execution error: Can't open directory</p>";
		if ($returnNullOnError)
			return;

		return array();
	}

	while (($file = readdir($handle)) !== false) {
		// Skip reserved words
		if (($file == '.') || ($file == '..')) continue;

		// Check file against all extensions
		foreach ($ext as $e) {
			if ($e == '') {
				if (mb_strpos($file, '.') === false) {
					$list[$file] = $file;
					break;
				}
			} else {
				if (preg_match('#^(.+?)\.' . $e . '$#', $file, $m)) {
					$list[($showExt == 2) ? $file : $m[1]] = $showExt ? $file : $m[1];
					break;
				}
			}
		}

	}
	closedir($handle);

	return $list;
}

function ListDirs($folder, $category = false, $alllink = true, $elementID = '') {

	global $lang;

	switch ($folder) {
		case 'files':
			$wdir = files_dir;
			break;
		case 'images':
			$wdir = images_dir;
			break;

		default:
			return fase;
	}

	$select = '<select ' . ($elementID ? 'id="' . $elementID . '" ' : '') . 'name="category">' . ($alllink ? '<option value="">- ' . $lang['all'] . ' -</option>' : '');

	if (($dir = @opendir($wdir)) === false) {
		msg(array(
			'type' => 'error',
			'text' => str_replace('{dirname}', $wdir, $lang['error.nodir']),
			'info' => str_replace('{dirname}', $wdir, $lang['error.nodir#desc'])
		),
			1);

		return false;
	}

	$filelist = array();
	while ($file = readdir($dir)) {
		$filelist[] = $file;
	}

	natcasesort($filelist);
	reset($filelist);

	foreach ($filelist as $file) {
		if (is_dir($wdir . "/" . $file) && $file != "." && $file != "..")
			$select .= "<option value=\"" . $file . "\"" . ($category == $file ? ' selected="selected"' : '') . ">" . $file . "</option>\n";
	}
	$select .= '</select>';

	return $select;
}

function MakeDropDown($options, $name, $selected = "FALSE") {

	$output = "<select size=1 name=\"" . $name . "\">";
	foreach ($options as $k => $v)
		$output .= "<option value=\"" . $k . "\"" . (($selected == $k) ? " selected=\"selected\"" : '') . ">" . $v . "</option>";
	$output .= "</select>";

	return $output;
}

function LoadLang($what, $where = '', $area = '') {
	global $config, $lang;

	$where = ($where) ? '/' . $where : '';

	if (!file_exists($toinc = root . 'lang/' . $config['default_lang'] . $where . '/' . $what . '.ini')) {
		$toinc = root . 'lang/english/' . $where . '/' . $what . '.ini';
	}

	if (file_exists($toinc)) {
		$content = parse_ini_file($toinc, true);
		if (!is_array($lang)) {
			$lang = array();
		}
		if ($area) {
			$lang[$area] = $content;
		} else {
			$lang = array_merge($lang, $content);
		}
	}

	return $lang;
}

function LoadLangTheme() {

	global $config, $lang;

	$dir_lang = tpl_dir . $config['theme'] . '/lang/' . $config['default_lang'] . '.ini';

	if (file_exists($dir_lang))
		$lang['theme'] = parse_ini_file($dir_lang, true);

	return $lang;
}

// Return plugin dir
function GetPluginDir($name) {

	global $EXTRA_CONFIG;

	$extras = get_extras_list();
	if (!$extras[$name]) {
		return 0;
	}

	return extras_dir . '/' . $extras[$name]['dir'];
}

function GetPluginLangDir($name) {
	global $config;

	$lang_dir = GetPluginDir($name) . '/lang';

	if (!$lang_dir) {
		return 0;
	}

	if (is_dir($lang_dir . '/' . $config['default_lang'])) {
		$lang_dir = $lang_dir . '/' . $config['default_lang'];
	} elseif (is_dir($lang_dir . '/english')) {
		$lang_dir = $lang_dir . '/english';
	} elseif (is_dir($lang_dir . '/russian')) {
		$lang_dir = $lang_dir . '/russian';
	}

	return $lang_dir;
}

// Load LANG file for plugin
function LoadPluginLang($plugin, $file, $group = '', $prefix = '', $delimiter = '_') {
	global $config, $lang, $EXTRA_CONFIG;

	if (!$prefix) {
		$prefix = $plugin;
	}
	// If requested plugin is activated, we can get 'dir' information from active array
	$active = getPluginsActiveList();

	if (!$active['active'][$plugin]) {
		// No, plugin is not active. Let's load plugin list
		$extras = get_extras_list();

		// Exit if no data about this plugin is found
		if (!$extras[$plugin]) {
			return 0;
		}
		$lang_dir = extras_dir . '/' . $extras[$plugin]['dir'] . '/lang';
	} else {
		$lang_dir = extras_dir . '/' . $active['active'][$plugin] . '/lang';
	}

	// Exit if no lang dir
	if (!is_dir($lang_dir)) {
		return 0;
	}

	// find if we have 'lang' dir in plugin directory
	// Try to load langs in order: default / english / russian

	$lfn = ($group ? $group . '/' : '') . $file . '.ini';

	// * Default language
	if (is_dir($lang_dir . '/' . $config['default_lang']) && is_file($lang_dir . '/' . $config['default_lang'] . '/' . $lfn)) {
		$lang_dir = $lang_dir . '/' . $config['default_lang'];
	} else if (is_dir($lang_dir . '/english') && is_file($lang_dir . '/english/' . $lfn)) {
		//print "<b>LANG></b> No default lang file for `$plugin` (name: `$file`), using ENGLISH</br>\n";
		$lang_dir = $lang_dir . '/english';
	} else if (is_dir($lang_dir . '/russian') && is_file($lang_dir . '/russian/' . $lfn)) {
		//print "<b>LANG></b> No default lang file for `$plugin` (name: `$file`), using RUSSIAN</br>\n";
		$lang_dir = $lang_dir . '/russian';
	} else {
		//print "<b>LANG></b> No default lang file for `$plugin` (name: `$file`), using <b><u>NOthING</u></b></br>\n";
		return 0;
	}

	// load file
	$plugin_lang = parse_ini_file($lang_dir . '/' . $lfn);

	// merge values
	if (is_array($plugin_lang)) {
		// Delimiter = '#' - special delimiter, make a separate array
		if ($delimiter == '#') {
			$lang[$prefix] = $plugin_lang;
		} else if (($delimiter == '') && ($prefix == '')) {
			$lang = $lang + $plugin_lang;
		} else {
			foreach ($plugin_lang as $p => $v) {
				$lang[$prefix . $delimiter . $p] = $v;
			}
		}
	}

	return 1;
}

function getDatetimeUTC($timestamp = null, $format = 'Y-m-d H:i:s') {
    return ($timestamp === null) ? gmdate($format) : gmdate($format, $timestamp);
}

function showDatetimeFromUTC($datetime = null, $format = 'd-m-Y H:i') {
    $date = new \DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone( new \DateTimeZone(date_default_timezone_get()) );
    return $date->format($format);
}

//return ramdom length bytes
function getRandomStr($length = 10) {
    return random_bytes($length);
}

function getRandomStrLite($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $maxIndex = mb_strlen($characters) - 1;
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $maxIndex)];
    }

    return $randomString;
}

function MakeRandomPassword() {
	global $config;

	return mb_substr(md5($config['crypto_salt'] . uniqid(rand(), 1)), 0, 10);
}

function generateAdminNavigations($current, $start, $stop, $link, $navigations) {
	$result = '';
	//print "call generateAdminNavigations(current=".$current.", start=".$start.", stop=".$stop.")<br>\n";
	//print "Navigations: <pre>"; var_dump($navigations); print "</pre>";
	for ($j = $start; $j <= $stop; $j++) {
		if ($j == $current) {
			$result .= str_replace('%page%', $j, $navigations['current_page']);
		} else {
			$row['page'] = $j;
			$result .= str_replace('%page%', $j, str_replace('%link%', str_replace('%page%', $j, $link), $navigations['link_page']));
		}
	}

	return $result;
}

// Generate page list for admin panel
// * current - number of current page
// * count   - total count of pages
// * url	 - URL of page, %page% will be replaced by page number
// * maxNavigations - max number of navigation links
function generateAdminPagelist($param) {
	global $tpl, $TemplateCache;

	if ($param['count'] < 2) return '';

	templateLoadVariables(true, 1);
	$nav = $TemplateCache['admin']['#variables']['navigation'];

	$tpl->template('pages', tpl_actions);

	// Prev page link
	if ($param['current'] > 1) {
		$prev = $param['current'] - 1;
		$tvars['regx']["'\[prev-link\](.*?)\[/prev-link\]'si"] = str_replace('%page%', "$1", str_replace('%link%', str_replace('%page%', $prev, $param['url']), $nav['prevlink']));
	} else {
		$tvars['regx']["'\[prev-link\](.*?)\[/prev-link\]'si"] = "";
		$no_prev = true;
	}

	// ===[ TO PUT INTO CONFIG ]===
	$pages = '';
	if (isset($param['maxNavigations']) && ($param['maxNavigations'] > 3) && ($param['maxNavigations'] < 500)) {
		$maxNavigations = intval($param['maxNavigations']);
	} else {
		$maxNavigations = 10;
	}

	$sectionSize = floor($maxNavigations / 3);
	if ($param['count'] > $maxNavigations) {
		// We have more than 10 pages. Let's generate 3 parts
		// Situation #1: 1,2,3,4,[5],6 ... 128
		if ($param['current'] < ($sectionSize * 2)) {
			$pages .= generateAdminNavigations($param['current'], 1, $sectionSize * 2, $param['url'], $nav);
			$pages .= $nav['dots'];
			$pages .= generateAdminNavigations($param['current'], $param['count'] - $sectionSize, $param['count'], $param['url'], $nav);
		} elseif ($param['current'] > ($param['count'] - $sectionSize * 2 + 1)) {
			$pages .= generateAdminNavigations($param['current'], 1, $sectionSize, $param['url'], $nav);
			$pages .= $nav['dots'];
			$pages .= generateAdminNavigations($param['current'], $param['count'] - $sectionSize * 2 + 1, $param['count'], $param['url'], $nav);
		} else {
			$pages .= generateAdminNavigations($param['current'], 1, $sectionSize, $param['url'], $nav);
			$pages .= $nav['dots'];
			$pages .= generateAdminNavigations($param['current'], $param['current'] - 1, $param['current'] + 1, $param['url'], $nav);
			$pages .= $nav['dots'];
			$pages .= generateAdminNavigations($param['current'], $param['count'] - $sectionSize, $param['count'], $param['url'], $nav);
		}
	} else {
		// If we have less then 10 pages
		$pages .= generateAdminNavigations($param['current'], 1, $param['count'], $param['url'], $nav);
	}

	$tvars['vars']['pages'] = $pages;
	if ($prev + 2 <= $param['count']) {
		$next = $prev + 2;
		$tvars['regx']["'\[next-link\](.*?)\[/next-link\]'si"] = str_replace('%page%', "$1", str_replace('%link%', str_replace('%page%', $next, $param['url']), $nav['nextlink']));
	} else {
		$tvars['regx']["'\[next-link\](.*?)\[/next-link\]'si"] = "";
		$no_next = true;
	}
	$tpl->vars('pages', $tvars);

	return $tpl->show('pages');
}

$letters = array('%A8' => '%D0%81', '%B8' => '%D1%91', '%C0' => '%D0%90', '%C1' => '%D0%91', '%C2' => '%D0%92', '%C3' => '%D0%93', '%C4' => '%D0%94', '%C5' => '%D0%95', '%C6' => '%D0%96', '%C7' => '%D0%97', '%C8' => '%D0%98', '%C9' => '%D0%99', '%CA' => '%D0%9A', '%CB' => '%D0%9B', '%CC' => '%D0%9C', '%CD' => '%D0%9D', '%CE' => '%D0%9E', '%CF' => '%D0%9F', '%D0' => '%D0%A0', '%D1' => '%D0%A1', '%D2' => '%D0%A2', '%D3' => '%D0%A3', '%D4' => '%D0%A4', '%D5' => '%D0%A5', '%D6' => '%D0%A6', '%D7' => '%D0%A7', '%D8' => '%D0%A8', '%D9' => '%D0%A9', '%DA' => '%D0%AA', '%DB' => '%D0%AB', '%DC' => '%D0%AC', '%DD' => '%D0%AD', '%DE' => '%D0%AE', '%DF' => '%D0%AF', '%E0' => '%D0%B0', '%E1' => '%D0%B1', '%E2' => '%D0%B2', '%E3' => '%D0%B3', '%E4' => '%D0%B4', '%E5' => '%D0%B5', '%E6' => '%D0%B6', '%E7' => '%D0%B7', '%E8' => '%D0%B8', '%E9' => '%D0%B9', '%EA' => '%D0%BA', '%EB' => '%D0%BB', '%EC' => '%D0%BC', '%ED' => '%D0%BD', '%EE' => '%D0%BE', '%EF' => '%D0%BF', '%F0' => '%D1%80', '%F1' => '%D1%81', '%F2' => '%D1%82', '%F3' => '%D1%83', '%F4' => '%D1%84', '%F5' => '%D1%85', '%F6' => '%D1%86', '%F7' => '%D1%87', '%F8' => '%D1%88', '%F9' => '%D1%89', '%FA' => '%D1%8A', '%FB' => '%D1%8B', '%FC' => '%D1%8C', '%FD' => '%D1%8D', '%FE' => '%D1%8E', '%FF' => '%D1%8F');
//$chars = array('%C2%A7' => '&#167;', '%C2%A9' => '&#169;', '%C2%AB' => '&#171;', '%C2%AE' => '&#174;', '%C2%B0' => '&#176;', '%C2%B1' => '&#177;', '%C2%BB' => '&#187;', '%E2%80%93' => '&#150;', '%E2%80%94' => '&#151;', '%E2%80%9C' => '&#147;', '%E2%80%9D' => '&#148;', '%E2%80%9E' => '&#132;', '%E2%80%A6' => '&#133;', '%E2%84%96' => '&#8470;', '%E2%84%A2' => '&#153;', '%C2%A4' => '&curren;', '%C2%B6' => '&para;', '%C2%B7' => '&middot;', '%E2%80%98' => '&#145;', '%E2%80%99' => '&#146;', '%E2%80%A2' => '&#149;');
// TEMPORARY SOLUTION AGAINST '&' quoting
$chars = array('%D0%86' => '[CYR_I]', '%D1%96' => '[CYR_i]', '%D0%84' => '[CYR_E]', '%D1%94' => '[CYR_e]', '%D0%87' => '[CYR_II]', '%D1%97' => '[CYR_ii]', '%C2%A7' => chr(167), '%C2%A9' => chr(169), '%C2%AB' => chr(171), '%C2%AE' => chr(174), '%C2%B0' => chr(176), '%C2%B1' => chr(177), '%C2%BB' => chr(187), '%E2%80%93' => chr(150), '%E2%80%94' => chr(151), '%E2%80%9C' => chr(147), '%E2%80%9D' => chr(148), '%E2%80%9E' => chr(132), '%E2%80%A6' => chr(133), '%E2%84%96' => '&#8470;', '%E2%84%A2' => chr(153), '%C2%A4' => '&curren;', '%C2%B6' => '&para;', '%C2%B7' => '&middot;', '%E2%80%98' => chr(145), '%E2%80%99' => chr(146), '%E2%80%A2' => chr(149));
$byary = array_flip($letters);

function convert($content) {

	global $byary, $chars;

	$content = mb_strstr(urlencode($content), $byary);
	$content = mb_strstr($content, $chars);
	$content = urldecode($content);

	return $content;
}

function utf2cp1251($text) {

	return convert($text);
}

//
// Generate link to news
//
function newsGenerateLink($row, $flagPrint = false, $page = 0, $absoluteLink = false) {

	global $catmap, $config;

	// Prepare category listing
	$clist = 'none';
	$ilist = 0;
	if ($row['catid']) {
		$ccats = array();
		$icats = array();
		foreach (explode(',', $row['catid']) as $ccatid) {
			if ($catmap[$ccatid] != '') {
				$ccats[] = $catmap[$ccatid];
				$icats[] = $ccatid;
			}
			if ($config['news_multicat_url'])
				break;
		}
		$clist = implode("-", $ccats);
		$ilist = implode("-", $icats);
	}

	// Get full news link
	$params = array('category' => $clist, 'catid' => $ilist, 'altname' => $row['alt_name'], 'id' => $row['id'], 'zid' => sprintf('%04u', $row['id']), 'year' => date('Y', $row['postdate']), 'month' => date('m', $row['postdate']), 'day' => date('d', $row['postdate']));
	if ($page)
		$params['page'] = $page;

	return generateLink('news', $flagPrint ? 'print' : 'news', $params, array(), false, $absoluteLink);

}

// Fetch metatags rows
function GetMetatags() {

	global $config, $SYSTEM_FLAGS;

	if (!$config['meta'])
		return;

	$meta['description'] = $config['description'];
	$meta['keywords'] = $config['keywords'];

	if (isset($SYSTEM_FLAGS['meta']['description']) && ($SYSTEM_FLAGS['meta']['description'] != ''))
		$meta['description'] = $SYSTEM_FLAGS['meta']['description'];

	if (isset($SYSTEM_FLAGS['meta']['keywords']) && ($SYSTEM_FLAGS['meta']['keywords'] != ''))
		$meta['keywords'] = $SYSTEM_FLAGS['meta']['keywords'];

	$result = ($meta['description'] != '') ? "<meta name=\"description\" content=\"" . secure_html($meta['description']) . "\" />\r\n" : '';
	$result .= ($meta['keywords'] != '') ? "<meta name=\"keywords\" content=\"" . secure_html($meta['keywords']) . "\" />\r\n" : '';

	return $result;
}

// Generate pagination block
function generatePaginationBlock($current, $start, $end, $paginationParams, $navigations, $intlink = false) {

	$result = '';
	for ($j = $start; $j <= $end; $j++) {
		if ($j == $current) {
			$result .= str_replace('%page%', $j, $navigations['current_page']);
		} else {
			$result .= str_replace('%page%', $j, str_replace('%link%', generatePageLink($paginationParams, $j, $intlink), $navigations['link_page']));
		}
	}

	return $result;
}

//
// Generate navigations panel ( like: 1.2.[3].4. ... 25 )
// $current				- current page
// $start				- first page in navigations
// $end					- last page in navigations
// $maxnav				- maximum number of navigtions to show
// $paginationParams	- pagination params [ for function generatePageLink() ]
// $intlink				- generate all '&' as '&amp;' if value is set
function generatePagination($current, $start, $end, $maxnav, $paginationParams, $navigations, $intlink = false) {

	$pages_count = $end - $start + 1;
	$pages = '';

	if ($pages_count > $maxnav) {
		// We have more than 10 pages. Let's generate 3 parts
		$sectionSize = floor($maxnav / 3);

		// Section size should be not less 1 item
		if ($sectionSize < 1)
			$sectionSize = 1;

		// Situation #1: 1,2,3,4,[5],6 ... 128
		if ($current < ($sectionSize * 2)) {
			$pages .= generatePaginationBlock($current, 1, $sectionSize * 2, $paginationParams, $navigations, $intlink);
			$pages .= $navigations['dots'];
			$pages .= generatePaginationBlock($current, $pages_count - $sectionSize, $pages_count, $paginationParams, $navigations, $intlink);
		} elseif ($current > ($pages_count - $sectionSize * 2 + 1)) {
			$pages .= generatePaginationBlock($current, 1, $sectionSize, $paginationParams, $navigations, $intlink);
			$pages .= $navigations['dots'];
			$pages .= generatePaginationBlock($current, $pages_count - $sectionSize * 2 + 1, $pages_count, $paginationParams, $navigations, $intlink);
		} else {
			$pages .= generatePaginationBlock($current, 1, $sectionSize, $paginationParams, $navigations, $intlink);
			$pages .= $navigations['dots'];
			$pages .= generatePaginationBlock($current, $current - 1, $current + 1, $paginationParams, $navigations, $intlink);
			$pages .= $navigations['dots'];
			$pages .= generatePaginationBlock($current, $pages_count - $sectionSize, $pages_count, $paginationParams, $navigations, $intlink);
		}
	} else {
		// If we have less then $maxnav pages
		$pages .= generatePaginationBlock($current, 1, $pages_count, $paginationParams, $navigations, $intlink);
	}

	return $pages;
}

// Generate block with pages [ 1, 2, [3], 4, ..., 25, 26, 27 ] using default configuration of template
function ngSitePagination($currentPage, $totalPages, $paginationParams, $navigationsCount = 0, $flagIntLink = false) {

	global $config, $TemplateCache, $tpl;

	if ($totalPages < 2)
		return '';

	templateLoadVariables(true);
	$navigations = $TemplateCache['site']['#variables']['navigation'];
	$tpl->template('pages', tpl_dir . $config['theme']);

	// Prev page link
	if ($currentPage > 1) {
		$prev = $currentPage - 1;
		$tvars['regx']["'\[prev-link\](.*?)\[/prev-link\]'si"] = str_replace('%page%', "$1", str_replace('%link%', generatePageLink($paginationParams, $prev), $navigations['prevlink']));
	} else {
		$tvars['regx']["'\[prev-link\](.*?)\[/prev-link\]'si"] = "";
		$prev = 0;
		$no_prev = true;
	}

	$maxNavigations = $config['newsNavigationsCount'];
	if ($navigationsCount < 1)
		$navigationsCount = ($config['newsNavigationsCount'] > 2) ? $config['newsNavigationsCount'] : 10;

	$tvars['vars']['pages'] = generatePagination($currentPage, 1, $totalPages, $navigationsCount, $paginationParams, $navigations);

	// Next page link
	if (($prev + 2 <= $totalPages)) {
		$tvars['regx']["'\[next-link\](.*?)\[/next-link\]'si"] = str_replace('%page%', "$1", str_replace('%link%', generatePageLink($paginationParams, $prev + 2), $navigations['nextlink']));
	} else {
		$tvars['regx']["'\[next-link\](.*?)\[/next-link\]'si"] = "";
		$no_next = true;
	}

	$tpl->vars('pages', $tvars);
	$paginationOutput = $tpl->show('pages');

	return $paginationOutput;
}

//
// Return user record by login
//
function locateUser($login) {

	global $mysql;
	if ($row = $mysql->record("select * from " . uprefix . "_users where name = " . db_squote($login))) {
		return $row;
	}

	return array();
}

function locateUserById($id) {

	global $mysql;
	if ($row = $mysql->record("select * from " . uprefix . "_users where id = " . db_squote($id))) {
		return $row;
	}

	return array();
}

if (!function_exists('json_encode')) {
	function utf8_to_html($data) {

		return preg_replace("/([\\xC0-\\xF7]{1,1}[\\x80-\\xBF]+)/e", '_utf8_to_html("\\1")', $data);
	}

	function _utf8_to_html($data) {

		$ret = 0;
		foreach ((str_split(strrev(chr((ord($data{0}) % 252 % 248 % 240 % 224 % 192) + 128) . mb_substr($data, 1)))) as $k => $v)
			$ret += (ord($v) % 128) * pow(64, $k);

		// return "&#$ret;";
		return sprintf("\u%04x", $ret);
	}

	function json_encode($a = false) {

		if (is_null($a)) return 'null';
		if ($a === false) return 'false';
		if ($a === true) return 'true';
		if (is_scalar($a)) {
			if (is_float($a)) {
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			}

			if (is_string($a)) {
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));

				return '"' . utf8_to_html(str_replace($jsonReplaces[0], $jsonReplaces[1], $a)) . '"';
			} else
				return $a;
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if (key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		$result = array();
		if ($isList) {
			foreach ($a as $v) $result[] = json_encode($v);

			return '[' . join(',', $result) . ']';
		} else {
			foreach ($a as $k => $v) $result[] = json_encode($k) . ':' . json_encode($v);

			return '{' . join(',', $result) . '}';
		}
	}
}

//
// Add json_decode() support for PHP < 5.2.0
//
if (!function_exists('json_decode')) {
	function json_decode($json, $assoc = false) {

		include_once root . 'includes/classes/json.php';
		$jclass = new Services_JSON($assoc ? SERVICES_JSON_LOOSE_TYPE : 0);

		return $jclass->decode($json);
	}
}

// Parse params
function parseParams($paramLine) {

	// Start scanning
	// State:
	// 0 - waiting for name
	// 1 - scanning name
	// 2 - waiting for '='
	// 3 - waiting for value
	// 4 - scanning value
	// 5 - complete
	$state = 0;
	// 0 - no quotes activated
	// 1 - single quotes activated
	// 2 - double quotes activated
	$quotes = 0;

	$keyName = '';
	$keyValue = '';
	$errorFlag = 0;

	$keys = array();

	for ($sI = 0; $sI < mb_strlen($paramLine); $sI++) {
		// act according current state
		$x = $paramLine{$sI};

		switch ($state) {
			case 0:
				if ($x == "'") {
					$quotes = 1;
					$state = 1;
					$keyName = '';
				} else if ($x == "'") {
					$quotes = 2;
					$state = 1;
					$keyName = '';
				} else if ((($x >= 'A') && ($x <= 'Z')) || (($x >= 'a') && ($x <= 'z'))) {
					$state = 1;
					$keyName = $x;
				}
				break;
			case 1:
				if ((($quotes == 1) && ($x == "'")) || (($quotes == 2) && ($x == '"'))) {
					$quotes = 0;
					$state = 2;
				} else if ((($x >= 'A') && ($x <= 'Z')) || (($x >= 'a') && ($x <= 'z'))) {
					$keyName .= $x;
				} else if ($x == '=') {
					$state = 3;
				} else if (($x == ' ') || ($x == chr(9))) {
					$state = 2;
				} else {
					$erorFlag = 1;
				}
				break;
			case 2:
				if ($x == '=') {
					$state = 3;
				} else if (($x == ' ') || ($x == chr(9))) {
					;
				} else {
					$errorFlag = 1;
				}
				break;
			case 3:
				if ($x == "'") {
					$quotes = 1;
					$state = 4;
					$keyValue = '';
				} else if ($x == '"') {
					$quotes = 2;
					$state = 4;
					$keyValue = '';
				} else if ((($x >= 'A') && ($x <= 'Z')) || (($x >= 'a') && ($x <= 'z'))) {
					$state = 4;
					$keyValue = $x;
				}
				break;
			case 4:
				if ((($quotes == 1) && ($x == "'")) || (($quotes == 2) && ($x == '"'))) {
					$quotes = 0;
					$state = 5;
				} else if (!$quotes && (($x == ' ') || ($x == chr(9)))) {
					$state = 5;
				} else {
					$keyValue .= $x;
				}
				break;
		}

		// Action in case when scanning is complete
		if ($state == 5) {
			$keys [mb_strtolower($keyName)] = $keyValue;
			$state = 0;
		}
	}

	// If we finished and we're in stete "scanning value" - register this field
	if ($state == 4) {
		$keys [mb_strtolower($keyName)] = $keyValue;
		$state = 0;
	}

	// If we have any other state - report an error
	if ($state) {
		$errorFlag = 1; // print "EF ($state)[".$paramLine."].";
	}

	if ($errorFlag) {
		return -1;
	}

	return $keys;
}

//
// Print output HTTP headers
//
function printHTTPheaders() {
	global $SYSTEM_FLAGS;

	foreach ($SYSTEM_FLAGS['http.headers'] as $hkey => $hvalue) {
		@header($hkey . ': ' . $hvalue);
	}
}


// Generate error "PAGE NOT FOUND"
function error404() {
	global $config, $tpl, $template, $SYSTEM_FLAGS, $lang;

	@header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	switch ($config['404_mode']) {
		// HTTP error 404
		case 2:
			exit;

		// External error template
		case 1:
			$tpl->template('404.external', tpl_site);
			$tpl->vars('404.external', []);
			echo $tpl->show('404.external');
			exit;

		// Internal error template
		case 0:
		default:
			$tpl->template('404.internal', tpl_site);
			$tpl->vars('404.internal', []);
			$template['vars']['mainblock'] = $tpl->show('404.internal');

			$SYSTEM_FLAGS['info']['title']['group'] = $lang['404.title'];
	}
}

//
// Generate SecureToken for protection from CSRF attacks
//
function genUToken($identity = '') {

	global $userROW, $config;

	$line = $identity;
	if (isset($userROW))
		$line .= $userROW['id'] . $userROW['authcookie'];

	if (isset($config['UUID']))
		$line .= $config['UUID'];

	return md5($line);
}

// Converse array charset
// $direction:
//		0	- Win1251	=> UTF-8
//		1	- UTF-8		=> Win1251
//	$data
function arrayCharsetConvert($direction, $data) {

	if (!is_array($data))
		return iconv($direction ? 'UTF-8' : 'Windows-1251', $direction ? 'Windows-1251' : 'UTF-8', $data);

	$result = array();
	foreach ($data as $k => $v) {
		$result[iconv($direction ? 'UTF-8' : 'Windows-1251', $direction ? 'Windows-1251' : 'UTF-8', $k)] = is_array($v) ? arrayCharsetConvert($direction, $v) : iconv($direction ? 'UTF-8' : 'Windows-1251', $direction ? 'Windows-1251' : 'UTF-8', $v);
	}

	return $result;
}

// Check if user $user have access to identity $identity with mode $mode
// $identity - array with element characteristics
// 	* plugin	- id of plugin
//	* item		- id of item in plugin
//  * ds		- id of Date Source (if applicable)
//	* ds_id		- id of item from DS (if applicable)
// $user - user record or null if access is checked for current user
// $mode - access mode:
//		'view'
//		'details'
//		'modify'/
//		.. here can be any other modes, but view/details/modify are most commonly used
// $way	 - way for content access
//			'rpc' - via rpc
//			'' - default access via site
function checkPermission($identity, $user = null, $mode = '', $way = '') {

	global $userROW, $PERM;
	//$xDEBUG = true;
	$xDEBUG = false;

	if ($xDEBUG) {
		print "checkPermission[" . $identity['plugin'] . "," . $identity['item'] . "," . $mode . "] = ";
	}

	// Determine user's groups
	$uGroup = (isset($user) && isset($user['status'])) ? $user['status'] : $userROW['status'];

	// Check if permissions for this group exists. Break if no.
	if (!isset($PERM[$uGroup])) {
		if ($xDEBUG) {
			print " => FALSE[1]<br/>\n";
		}

		return false;
	}

	//return true;

	// Now let's check for possible access
	// - access group
	$ag = '';
	if (isset($PERM[$uGroup][$identity['plugin']])) {
		// Plugin found
		$ag = $identity['plugin'];
	} elseif (isset($PERM[$uGroup]['*'])) {
		// Perform default action
		$ag = '*';
	} else {
		// No such group [plugin] and no default action, return FALSE
		if ($xDEBUG) {
			print " => FALSE[2]<br/>\n";
		}

		return false;
	}
	if ($xDEBUG) {
		print "[AG=$ag]";
	}
	// - access item
	$ai = '';
	if (isset($PERM[$uGroup][$ag][$identity['item']]) && ($PERM[$uGroup][$ag][$identity['item']] !== null)) {
		// Plugin found
		$ai = $identity['item'];
	} elseif (isset($PERM[$uGroup][$ag]['*']) && ($PERM[$uGroup][$ag]['*'] !== null)) {
		// Perform default action
		$ai = '*';
	} else {
		// No such group [plugin] and no default action, return FALSE
		if ($xDEBUG) {
			print " => FALSE[3]<br/>\n";
		}

		return false;
	}

	if ($xDEBUG) {
		print "[AI=$ai]";
	}

	// Ok, now we located item and can return requested mode
	$mList = is_array($mode) ? $mode : array($mode);
	$mStatus = array();

	foreach ($mList as $mKey) {
		// The very default - DENY
		$iStatus = false;
		if (isset($PERM[$uGroup][$ag]) && isset($PERM[$uGroup][$ag][$ai]) && isset($PERM[$uGroup][$ag][$ai][$mKey]) && ($PERM[$uGroup][$ag][$ai][$mKey] !== null)) {
			// Check specific mode
			$iStatus = $PERM[$uGroup][$ag][$ai][$mKey];
		} else if (isset($PERM[$uGroup][$ag]) && isset($PERM[$uGroup][$ag][$ai]) && isset($PERM[$uGroup][$ag][$ai]['*']) && ($PERM[$uGroup][$ag][$ai]['*'] !== null)) {
			// Ckeck '*' under specifig Group/Item
			$iStatus = $PERM[$uGroup][$ag][$ai]['*'];
		} else if (isset($PERM[$uGroup][$ag]) && isset($PERM[$uGroup][$ag]['*']) && isset($PERM[$uGroup][$ag]['*']['*']) && ($PERM[$uGroup][$ag]['*']['*'] !== null)) {
			// Check '*' under specific Group
			$iStatus = $PERM[$uGroup][$ag]['*']['*'];
		} else if (isset($PERM[$uGroup]['*']) && isset($PERM[$uGroup]['*']['*']) && isset($PERM[$uGroup]['*']['*']['*']) && ($PERM[$uGroup]['*']['*']['*'] !== null)) {
			// Check '*' under current UserGroupID
			$iStatus = $PERM[$uGroup]['*']['*']['*'];
		}
		$mStatus[$mKey] = $iStatus;
	}

	if ($xDEBUG) {
		print " => " . var_export($mStatus, true) . "<br/>\n";
	}

	// Now check return mode and return
	return is_array($mode) ? $mStatus : $mStatus[$mode];
}

// Load user groups
function loadGroups() {

	global $UGROUP, $config;

	$UGROUP = array();
	if (is_file(confroot . 'ugroup.php')) {
		include confroot . 'ugroup.php';
		$UGROUP = $confUserGroup;
	}

	// Fill default groups if not specified
	if (!isset($UGROUP[1])) {
		$UGROUP[1] = array(
			'identity' => 'admin',
			'langName' => array(
				'russian' => 'Администратор',
				'english' => 'Administrator',
			),
		);
		$UGROUP[2] = array(
			'identity' => 'editor',
			'langName' => array(
				'russian' => 'Редактор',
				'english' => 'Editor',
			),
		);
		$UGROUP[3] = array(
			'identity' => 'journalist',
			'langName' => array(
				'russian' => 'Журналист',
				'english' => 'Journalist',
			),
		);
		$UGROUP[4] = array(
			'identity' => 'commentator',
			'langName' => array(
				'russian' => 'Комментатор',
				'english' => 'Commentator',
			),
		);
		//		$UGROUP[5] = array(
		//			'identity'	=> 'tester',
		//			'langName'	=> array(
		//				'russian'	=> 'Тестировщик',
		//				'english'	=> 'Tester',
		//			),
		//		);
	}

	// Initialize name according to current selected language
	foreach ($UGROUP as $id => $v) {
		$UGROUP[$id]['name'] = (isset($UGROUP[$id]['langName'][$config['default_lang']])) ? $UGROUP[$id]['langName'][$config['default_lang']] : $UGROUP[$id]['identity'];
	}

}

// Load permissions
function loadPermissions() {

	global $PERM, $confPerm, $confPermUser;

	// 1. Load DEFAULT permission file.
	// * if not exists - allow everything for group = 1, other's are restricted
	$PERM = array();
	if (is_file(confroot . 'perm.default.php')) {
		include confroot . 'perm.default.php';
		$PERM = $confPerm;
	} else {
		$PERM = array('1' => array('*' => array('*' => array('*' => true))));
	}

	// 2. Load user specific config file
	// If configuration file exists
	$confPermUser = array();
	if (is_file(confroot . 'perm.php')) {
		// Try to load it
		include confroot . 'perm.php';
	}

	// Scan user's permissions
	if (is_array($confPermUser))
		foreach ($confPermUser as $g => $ginfo) {
			if (is_array($ginfo))
				foreach ($ginfo as $p => $ainfo) {
					if (is_array($ainfo))
						foreach ($ainfo as $r => $rinfo) {
							if (is_array($rinfo))
								foreach ($rinfo as $i => $ivalue) {
									$PERM[$g][$p][$r][$i] = $ivalue;
								}
						}
				}
		}
}

// SAVE updated user-defined permissions
function saveUserPermissions() {

	global $confPermUser;

	$line = '<?php' . "\n// Kerno User defined permissions ()\n";
	$line .= '$confPermUser = ' . var_export($confPermUser, true) . "\n;\n?>";

	$fcHandler = @fopen(confroot . 'perm.php', 'w');
	if ($fcHandler) {
		fwrite($fcHandler, $line);
		fclose($fcHandler);

		return true;
	}

	return false;
}

// Generate record in System LOG for security audit and logging of changes
// $identity - array of params for identification if object
// 	* plugin	- id of plugin
//	* item		- id of item in plugin
// 	* ds		- id of Date Source (if applicable)
//	* ds_id		- id of item from DS (if applicable)
// $action	- array of params to identify action
//	* action	- id of action
//	* list		- list of changed fields
// $user	- user record or null if access is checked for current user
// $status	- array of params to identify resulting status
//	* [0]	- state [ 0 - fail, 1 - ok ]
//	* [1]	- text value CODE of error (if have error)
function ngSYSLOG($identity, $action, $user, $status) {

	global $ip, $mysql, $userROW, $config;

	if (!$config['syslog'])
		return false;

	$sVars = array(
		'dt'       => 'now()',
		'ip'       => db_squote($ip),
		'plugin'   => db_squote($identity['plugin']),
		'item'     => db_squote($identity['item']),
		'ds'       => intval($identity['ds']),
		'ds_id'    => intval($identity['ds_id']),
		'action'   => db_squote($action['action']),
		'alist'    => db_squote(serialize($action['list'])),
		'userid'   => is_array($user) ? intval($user['id']) : (($user === null) ? intval($userROW['id']) : 0),
		'username' => is_array($user) ? db_squote($user['name']) : (($user === null) ? db_squote($userROW['name']) : db_squote($user)),
		'status'   => intval($status[0]),
		'stext'    => db_squote($status[1]),
	);
	//print "<pre>".var_export($sVars, true)."</pre>";
	$mysql->query("insert into " . prefix . "_syslog (" . join(",", array_keys($sVars)) . ") values (" . join(",", array_values($sVars)) . ")");
	//$mysql->query("insert into ".prefix."_syslog (dt, ip, plugin, item, ds, ds_id, action, alist, userid, username, status, stext) values (now(), ".db_squote($ip).",");
	//print "<pre>ngSYSLOG: ".var_export($identity, true)."\n".var_export($action, true)."\n".var_export($user, true)."\n".var_export($status, true)."</pre>";
}

//
// HANDLER: Exceptions
function ngExceptionHandler($exception) {

	?>
	<html>
	<head>
		<title>Kerno Runtime exception: <?php echo get_class($exception); ?></title>
		<style>
			body {
				font: 1em Georgia, "Times New Roman", serif;
			}

			.dmsg {
				border: 1px #EEEEEE solid;
				padding: 10px;
				background-color: yellow;
			}

			.dtrace TBODY TD {
				padding: 3px;
				/*border: 1px #EEEEEE solid;*/
				background-color: #EEEEEE;
			}

			.dtrace THEAD TD {
				padding: 3px;
				background-color: #EEEEEE;
				font-weight: bold;
			}

		</style>
	</head>
	<body>
	<?php
	print "<h1>Kerno Runtime exception: " . get_class($exception) . "</h1>\n";
	print "<div class='dmsg'>" . $exception->getMessage() . "</div><br/>";
	print "<h2>Stack trace</h2>";
	print "<table class='dtrace'><thead><tr><td>#</td><td>Line #</td><td><i>Class</i>/Function</td><td>File name</td></tr></thead><tbody>";
	print "<tr><td>X</td><td>".$exception->getLine()."</td><td>".$exception->getCode()."</td><td>".$exception->getFile()."</td></tr>";
	foreach ($exception->getTrace() as $k => $v) {
		print "<tr><td>" . $k . "</td><td>" . $v['line'] . "</td><td>" . (isset($v['class']) ? ('<i>' . $v['class'] . '</i>') : $v['function']) . "</td><td>" . $v['file'] . "</td></tr>\n";
	}
	print "</tbody></table>";
}

//Проверяем переменную
function getIsSet(&$result) {

	if (isset($result))
		return $result;

	return null;
}

//
// HANDLER: Errors
function ngErrorHandler($code, $message, $file, $line) {
	/* if (0 == error_reporting())
	{
		return;
	}
	print "ERROR: [$code]($message)[$line]($file)<br/>\n"; */
}

//
// HANDLER: Shutdown
function ngShutdownHandler() {
	$lastError = error_get_last();

	// Activate only for fatal errors
	$flagFatal = 0;

	switch ($lastError['type']) {
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
			$flagFatal = 1;
			break;
	}
	if (!$flagFatal)
		return true;
	?>
<html>
	<head>
		<title>Kerno Runtime error: <?php echo $lastError['message']; ?></title>
		<style type="text/css">
			body {
				font: 1em Georgia, "Times New Roman", serif;
			}

			.dmsg {
				border: 1px #EEEEEE solid;
				padding: 10px;
				background-color: yellow;
			}

			.dtrace TBODY TD {
				padding: 3px;
				/*border: 1px #EEEEEE solid;*/
				background-color: #EEEEEE;
			}

			.dtrace THEAD TD {
				padding: 3px;
				background-color: #EEEEEE;
				font-weight: bold;
			}

		</style>
	</head>
<body>
<?php
print "<div id=\"ngErrorInformer\">";
print "<h1>Kerno Runtime error: " . $lastError['message'] . "</h1>\n";
print "<div class='dmsg'>[ " . $lastError['type'] . "]: " . $lastError['message'] . "</div><br/>";
print "<h2>Stack trace</h2>";
print "<table class='dtrace'><thead><td>Line #</td><td>File name</td></tr></thead><tbody>";
print "<tr><td>" . $lastError['line'] . "</td><td>" . $lastError['file'] . "</td></tr></tbody></table>";
print "</div>";
?>
	<div id="hdrSpanItem"></div>
	<script language="Javascript">
		{
			var xc = document.getElementById('ngErrorInformer').innerHTML;
			var i = 0;
			var cnt = 0;
			while (i < document.body.childNodes.length) {
				var node = document.body.childNodes[i];
				if (node.tagName == 'DIV') {
					document.body.removeChild(document.body.childNodes[i]);
					break;
				}
				if ((node.tagName == 'TITLE') || (node.tagName == 'STYLE') || (node.tagName == '')) {
					i++;
				} else {
					document.body.removeChild(document.body.childNodes[i]);
				}
			}
			document.body.innerHTML = xc;
		}
	</script>
	<?php
	return false;
}

// Software generated fatal error
function ngFatalError($title, $description = '') {

	?>
	<html>
	<head>
		<title>Kerno Runtime error: <?php echo $title; ?></title>
		<style type="text/css">
			body {
				font: 1em Georgia, "Times New Roman", serif;
			}

			.dmsg {
				border: 1px #EEEEEE solid;
				padding: 10px;
				background-color: yellow;
			}

			.dtrace TBODY TD {
				padding: 3px;
				/*border: 1px #EEEEEE solid;*/
				background-color: #EEEEEE;
			}

			.dtrace THEAD TD {
				padding: 3px;
				background-color: #EEEEEE;
				font-weight: bold;
			}

		</style>
	</head>
	<body>
	<div id="hdrSpanItem"></div>
	<script language="Javascript">
		{
			var i = 0;
			var cnt = 0;
			while (i < document.body.childNodes.length) {
				var node = document.body.childNodes[i];
				if (node.tagName == 'DIV') {
					document.body.removeChild(document.body.childNodes[i]);
					break;
				}
				if ((node.tagName == 'TITLE') || (node.tagName == 'STYLE')) {
					i++;
				} else {
					document.body.removeChild(document.body.childNodes[i]);
				}
			}
		}
	</script>
	<?php
	print "<h1>Kerno CMS software generated fatal error: " . $title . "</h1>\n";
	print "<div class='dmsg'>[ Software error ]: " . $title . "</div><br/>";
	if ($description) {
		print "<p><i>" . $description . "</i></p>";
	}
	print "<h2>Stack trace</h2>";
	print "<table class='dtrace'><thead><td>Line #</td><td>Function</td><td>File name</td></tr></thead><tbody>";

	$trace = debug_backtrace();
	$num = 0;
	foreach ($trace as $k => $v) {
		$num++;
		print "<tr><td>" . $v['line'] . "</td><td>" . $v['function'] . "<td>" . $v['file'] . "</td></tr>";
		if ($num > 3) {
			print "<tr><td colspan='3'>...</td></tr>";
			break;
		}
	}
	print "</tbody></table></body></html>";
	exit;
}

// Notify kernel about script termination, used for statistics calculation
function coreNormalTerminate($mode = 0) {
	global $mysql, $timer, $config, $userROW, $systemAccessURL;

	$exectime = $timer->stop();
	$now = localtime(time(), true);
	$now_str = sprintf("%04u-%02u-%02u %02u:%02u:00", ($now['tm_year'] + 1900), ($now['tm_mon'] + 1), $now['tm_mday'], $now['tm_hour'], (intval($now['tm_min'] / 15) * 15));

	// Common analytics
	if ($config['load_analytics']) {
		$cvar = ($mode == 0) ? "core" : (($mode == 1) ? "plugin" : "ppage");
		$mysql->query("INSERT INTO " . prefix . "_load (dt, hit_core, hit_plugin, hit_ppage, exec_core, exec_plugin, exec_ppage) VALUES (
" . db_squote($now_str) . ", " . (($mode == 0) ? 1 : 0) . ", " . (($mode == 1) ? 1 : 0) . " , " . (($mode == 2) ? 1 : 0) . ", " . (($mode == 0) ? $exectime : 0) . ", " . (($mode == 1) ? $exectime : 0) . ", " . (($mode == 2) ? $exectime : 0) . ") on duplicate key update hit_" . $cvar . " = hit_" . $cvar . " + 1, exec_" . $cvar . " = exec_" . $cvar . " + " . $exectime);
	}

	// DEBUG profiler
	if ($config['load_profiler'] > time()) {
		$trace = [
			'queries' => $mysql->query_list,
			'events'  => $timer->printEvents(1),
		];

		$mysql->query("INSERT INTO " . prefix . "_profiler (dt, userid, exectime, memusage, url, tracedata) VALUES (now(),
" . ((isset($userROW) && is_array($userROW)) ? $userROW['id'] : 0) . ", " . $exectime . ", " . sprintf("%7.3f", (memory_get_peak_usage() / 1024 / 1024)) . ", " . db_squote($systemAccessURL) . ", " . db_squote(serialize($trace)) . ")");
	}
}

// Generate user redirect call and terminate execution of CMS
function coreRedirectAndTerminate($location) {
	@header("Location: " . $location);
	coreNormalTerminate();
	exit;
}

// Update delayed news counters
function newsUpdateDelayedCounters() {

	global $mysql;

	// Lock tables
	$mysql->query("lock tables " . prefix . "_news_view write, " . prefix . "_news write");

	// Read data and update counters
	foreach ($mysql->select("select * from " . prefix . "_news_view") as $vrec) {
		$mysql->query("update " . prefix . "_news set views = views + " . intval($vrec['cnt']) . " where id = " . intval($vrec['id']));
	}

	// Truncate view table
	//$mysql->query("truncate table ".prefix."_news_view");
	// DUE TO BUG IN MYSQL - USE DELETE + OPTIMIZE
	$mysql->query("delete from " . prefix . "_news_view");
	$mysql->query("optimize table " . prefix . "_news_view");

	// Unlock tables
	$mysql->query("unlock tables");

	return true;
}

// Delete old LOAD information, SYSLOG logging
function sysloadTruncate() {

	global $mysql;

	// Store LOAD data only for 1 week
	$mysql->query("delete from " . prefix . "_load where dt < from_unixtime(unix_timestamp(now()) - 7*86400)");
	$mysql->query("optimize table " . prefix . "_load");

	// Store SYSLOG data only for 1 month
	$mysql->query("delete from " . prefix . "_syslog where dt < from_unixtime(unix_timestamp(now()) - 30*86400)");
	$mysql->query("optimize table " . prefix . "_syslog");

}

// Process CRON job calls
function core_cron($isSysCron, $handler) {

	global $config;

	// Execute DB backup if automatic backup is enabled
	if (($handler == 'db_backup') && (isset($config['auto_backup'])) && $config['auto_backup']) {
		AutoBackup($isSysCron, false);
	}

	if ($handler == 'news_views') {
		newsUpdateDelayedCounters();
	}

	if ($handler == 'load_truncate') {
		sysloadTruncate();
	}
}

function coreUserMenu() {

	global $lang, $userROW, $PFILTERS, $lang, $twigLoader, $twig, $template, $config, $SYSTEM_FLAGS, $TemplateCache;

	// Preload template configuration variables
	templateLoadVariables();

	// Use default <noavatar> file
	// - Check if noavatar is defined on template level
	$tplVars = $TemplateCache['site']['#variables'];
	$noAvatarURL = (isset($tplVars['configuration']) && is_array($tplVars['configuration']) && isset($tplVars['configuration']['noAvatarImage']) && $tplVars['configuration']['noAvatarImage']) ? (tpl_url . "/" . $tplVars['configuration']['noAvatarImage']) : (avatars_url . "/noavatar.gif");

	// Preload plugins for usermenu
	loadActionHandlers('usermenu');

	// Load language file
	$lang = LoadLang('usermenu', 'site');

	// Prepare global params for TWIG
	$tVars = array();
	$tVars['flags']['isLogged'] = is_array($userROW) ? 1 : 0;

	// Prepare REGEX conversion table
	$conversionConfigRegex = array(
		"#\[login\](.*?)\[/login\]#si"               => '{% if (not flags.isLogged) %}$1{% endif %}',
		"#\[isnt-logged\](.*?)\[/isnt-logged\]#si"   => '{% if (not flags.isLogged) %}$1{% endif %}',
		"#\[is-logged\](.*?)\[/is-logged\]#si"       => '{% if (flags.isLogged) %}$1{% endif %}',
		"#\[login-err\](.*?)\[/login-err\]#si"       => '{% if (flags.loginError) %}$1{% endif %}',
		"#\[if-have-perm\](.*?)\[/if-have-perm\]#si" => "{% if (global.flags.isLogged and (global.user['status'] <= 3)) %}$1{% endif %}",
		//		"#\{l_([0-9a-zA-Z\-\_\.\#]+)}#"					=> "{{ lang['$1'] }}",
	);

	// Prepare conversion table
	$conversionConfig = array(
		'{avatar_url}'   => '{{ avatar_url }}',
		'{profile_link}' => '{{ profile_link }}',
		'{addnews_link}' => '{{ addnews_link }}',
		'{logout_link}'  => '{{ logout_link }}',
		'{phtumb_url}'   => '{{ phtumb_url }}',
		'{name}'         => '{{ name }}',
		'{result}'       => '{{ result }}',
		'{home_url}'     => '{{ home_url }}',
		'{redirect}'     => '{{ redirect }}',
		'{reg_link}'     => '{{ reg_link }}',
		'{lost_link}'    => '{{ lost_link }}',
		'{form_action}'  => '{{ form_action }}',
	);

	// If not logged in
	if (!is_array($userROW)) {

		$tVars['flags']['loginError'] = ($SYSTEM_FLAGS['auth_fail']) ? '$1' : '';
		$tVars['redirect'] = isset($SYSTEM_FLAGS['module.usermenu']['redirect']) ? $SYSTEM_FLAGS['module.usermenu']['redirect'] : $_SERVER['REQUEST_URI'];
		$tVars['reg_link'] = generateLink('core', 'registration');
		$tVars['lost_link'] = generateLink('core', 'lostpassword');
		$tVars['form_action'] = generateLink('core', 'login');
	} else {
		// User is logged in
		$tVars['profile_link'] = generateLink('uprofile', 'edit');
		$tVars['addnews_link'] = $config['admin_url'] . '/admin.php?mod=news&amp;action=add';
		$tVars['logout_link'] = generateLink('core', 'logout');
		$tVars['name'] = $userROW['name'];
		$tVars['phtumb_url'] = photos_url . '/' . (($userROW['photo'] != "") ? 'thumb/' . $userROW['photo'] : 'nophoto.gif');
		$tVars['home_url'] = home;

		// Generate avatar link
		$userAvatar = '';

		if ($config['use_avatars']) {
			if ($userROW['avatar']) {
				$userAvatar = avatars_url . "/" . $userROW['avatar'];
			} else {
				// If gravatar integration is active, show avatar from GRAVATAR.COM
				if ($config['avatars_gravatar']) {
					$userAvatar = 'http://www.gravatar.com/avatar/' . md5(mb_strtolower($userROW['mail'])) . '.jpg?s=' . $config['avatar_wh'] . '&d=' . urlencode($noAvatarURL);
				} else {
					$userAvatar = $noAvatarURL;
				}
			}
		}
		$tVars['avatar_url'] = $userAvatar;
	}

	// Execute filters - add additional variables
	if (isset($PFILTERS['core.userMenu']) && is_array($PFILTERS['core.userMenu']))
		foreach ($PFILTERS['core.userMenu'] as $k => $v) {
			$v->showUserMenu($tVars);
		}

	$twigLoader->setConversion('usermenu.tpl', $conversionConfig, $conversionConfigRegex);
	$xt = $twig->loadTemplate('usermenu.tpl');
	$template['vars']['personal_menu'] = $xt->render($tVars);

	// Add special variables `personal_menu:logged` and `personal_menu:not.logged`
	$template['vars']['personal_menu:logged'] = is_array($userROW) ? $template['vars']['personal_menu'] : '';
	$template['vars']['personal_menu:not.logged'] = is_array($userROW) ? '' : $template['vars']['personal_menu'];
}

function coreSearchForm() {

	global $tpl, $template, $lang;

	LoadLang('search', 'site');

	$tpl->template('search.form', tpl_site);
	$tpl->vars('search.form', array('vars' => array('form_url' => generateLink('search', '', array()))));
	$template['vars']['search_form'] = $tpl->show('search.form');
}

// Return current news category
function getCurrentNewsCategory() {

	global $currentCategory, $catz, $catmap, $config, $CurrentHandler, $SYSTEM_FLAGS;

	// Return if user is not reading any news
	if (($CurrentHandler['pluginName'] != 'news') || (!isset($SYSTEM_FLAGS['news']['currentCategory.id'])))
		return false;

	// Return if user is not reading short/full news from categories
	if (($CurrentHandler['handlerName'] != 'news') && ($CurrentHandler['handlerName'] != 'print') && ($CurrentHandler['handlerName'] != 'by.category'))
		return false;

	return array(($CurrentHandler['handlerName'] == 'by.category') ? 'short' : 'full', $SYSTEM_FLAGS['news']['currentCategory.id'], $SYSTEM_FLAGS['news']['db.id']);
}

function jsonFormatter($json) {

	$result = '';
	$pos = 0;
	$strLen = mb_strlen($json);
	$indentStr = '  ';
	$newLine = "\n";
	$prevChar = '';
	$outOfQuotes = true;

	for ($i = 0; $i <= $strLen; $i++) {

		// Grab the next character in the string.
		$char = mb_substr($json, $i, 1);

		// Are we inside a quoted string?
		if ($char == '"' && $prevChar != '\\') {
			$outOfQuotes = !$outOfQuotes;

			// If this character is the end of an element,
			// output a new line and indent the next line.
		} else if (($char == '}' || $char == ']') && $outOfQuotes) {
			$result .= $newLine;
			$pos--;
			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		// Add the character to the result string.
		$result .= $char;

		// If the last character was the beginning of an element,
		// output a new line and indent the next line.
		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
			$result .= $newLine;
			if ($char == '{' || $char == '[') {
				$pos++;
			}

			for ($j = 0; $j < $pos; $j++) {
				$result .= $indentStr;
			}
		}

		$prevChar = $char;
	}

	return $result;
}

function ngLoadCategories() {

	global $mysql, $catz, $catmap;

	if (($result = cacheRetrieveFile('LoadCategories.dat', 86400)) === false) {
		$result = $mysql->select("select nc.*, ni.id as icon_id, ni.name as icon_name, ni.storage as icon_storage, ni.folder as icon_folder, ni.preview as icon_preview, ni.width as icon_width, ni.height as icon_height, ni.p_width as icon_pwidth, ni.p_height as icon_pheight from `" . prefix . "_category` as nc left join `" . prefix . "_images` ni on nc.image_id = ni.id order by nc.posorder asc", 1);
		cacheStoreFile('LoadCategories.dat', serialize($result));
	} else $result = unserialize($result);

	if (is_array($result))
		foreach ($result as $row) {
			$catz[$row['alt']] = $row;
			$catmap[$row['id']] = $row['alt'];
		}
}

// Function for detection of UTF-8 charset
function detectUTF8($string) {

	return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

// Collect backtrace for debug analysis
// $style:
//		0 - print output in <pre>..</pre>
//		1 - return array
function ngCollectTrace($style = 0) {

	$bt = debug_backtrace();
	$list = array();
	foreach ($bt as $b) {
		$list [] = array('file' => $b['file'], 'line' => $b['line'], 'function' => $b['function']);
	}

	if ($style == 1)
		return $list;

	print "<pre>ngCollectTrace() debug output:\n";
	foreach ($list as $b) {
		printf("[ %-40s ] (%5u) %s\n", $b['function'], $b['line'], $b['file']);
	}
	print "</pre>";

	return true;
}

/**
 * Быстрый дебаг
 *
 * @param mixed $obj
 *
 * @return string
 */
function dd($obj) {

	if (is_array($obj) || is_object($obj)) {
		$obj = print_r($obj, true);
	}

	echo '<pre>' . htmlentities($obj, ENT_QUOTES) . "</pre><br>\n";
}
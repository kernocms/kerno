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

function twigLocalPath($context) {
    return $context['_templatePath'];
}

function twigIsLang($lang) {

    global $config;

    return ($config['default_lang'] == $lang);
}

function twigGetLang() {
    global $config;

    return $config['default_lang'];
}

// Allow to have specific template configuration for different locations ($CurrentHandler global array)
// RULE is: <ENTRY1>[|<ENTRY2>[|<ENTRY3>...]]
// ENTRY1,2,.. is: <PLUGIN>[:<HANDLER>]
function twigIsHandler($rules) {

    global $config, $CurrentHandler;

    $ruleCatched = false;
    foreach (preg_split("#\|#", $rules) as $rule) {
        if (preg_match("#^(.+?)\:(.+?)$#", $rule, $pt)) {
            // Specified: Plugin + Handler
            if (($pt[1] == $CurrentHandler['pluginName']) && ($pt[2] == $CurrentHandler['handlerName'])) {
                $ruleCatched = true;
                break;
            }
        } else if ($rule == $CurrentHandler['pluginName']) {
            $ruleCatched = true;
            break;
        }
    }

    return $ruleCatched;
}

function twigIsCategory($list) {
    global $currentCategory, $catz, $catmap, $config, $CurrentHandler;
    //print "twigCall isCategory($list):<pre>".var_export($currentCategory, true)."</pre>";

    // Return if user is not reading any news
    if ($CurrentHandler['pluginName'] != 'news') return false;
    if (($CurrentHandler['handlerName'] == 'news') || ($CurrentHandler['handlerName'] == 'print')) return false;

    // Return false if we're not in category now
    if (!isset($currentCategory)) {
        return false;
    }

    // ****** Process modifiers ******
    if ($list == '') return true;
    if ($list == ':id') return $currentCategory['id'];
    if ($list == ':alt') return secure_html($currentCategory['alt']);
    if ($list == ':name') return secure_html($currentCategory['name']);
    if ($list == ':icon') return ($currentCategory['image_id'] && $currentCategory['icon_id']) ? 1 : 0;
    if ($list == ':icon.url') return $config['attach_url'] . '/' . $currentCategory['icon_folder'] . '/' . $currentCategory['icon_name'];
    if ($list == ':icon.width') return intval($currentCategory['icon_width']);
    if ($list == ':icon.height') return intval($currentCategory['icon_height']);
    if ($list == ':icon.preview') return ($currentCategory['image_id'] && $currentCategory['icon_id'] && $currentCategory['icon_preview']) ? 1 : 0;
    if ($list == ':icon.preview.url') return $config['attach_url'] . '/' . $currentCategory['icon_folder'] . '/thumb/' . $currentCategory['icon_name'];
    if ($list == ':icon.preview.width') return intval($currentCategory['icon_pwidth']);
    if ($list == ':icon.preview.height') return intval($currentCategory['icon_pheight']);

    foreach (preg_split("# *, *#", $list) as $key) {
        if ($key == '')
            continue;

        if (ctype_digit($key)) {
            if (isset($catmap[$key]) && is_array($currentCategory) && ($currentCategory['id'] == $key))
                return true;
        } else {
            if (isset($catz[$key]) && is_array($catz[$key]) && is_array($currentCategory) && ($currentCategory['alt'] == $key))
                return true;
        }

    }

    return false;
}

function twigIsNews($rules) {
    global $catz, $catmap, $CurrentHandler, $SYSTEM_FLAGS, $CurrentCategory;
    //print "twigCall isNews($list):<pre>".var_export($SYSTEM_FLAGS['news'], true)."</pre>";

    // Return if user is not in news
    if ($CurrentHandler['pluginName'] != 'news') return false;
    if (($CurrentHandler['handlerName'] != 'news') && ($CurrentHandler['handlerName'] != 'print')) return false;
    if (!isset($SYSTEM_FLAGS['news']['db.id'])) return false;

    $ruleList = array('news' => array(), 'cat' => array(), 'mastercat' => array());
    $ruleCatched = false;

    // Pre-scan incoming data
    foreach (preg_split("#\|#", $rules) as $rule) {
        if (preg_match("#^(.+?)\:(.+?)$#", $rule, $pt)) {
            $ruleList[$pt[1]] = $ruleList[$pt[1]] + preg_split("# *, *#", $pt[2]);
        } else {
            $ruleList['news'] = $ruleList['news'] + preg_split("# *, *#", $rule);
        }
    }
    //print "isNews debug rules: <pre>".var_export($ruleList, true)."</pre>";
    foreach ($ruleList as $rType => $rVal) {
        //print "[SCAN TYPE: '$rType' with val: (".var_export($rVal, true).")]<br/>";
        switch ($rType) {
            // -- NEWS
            case 'news':
                if (!isset($SYSTEM_FLAGS['news']['db.id']))
                    continue;

                foreach ($rVal as $key) {
                    if (ctype_digit($key)) {
                        if ($SYSTEM_FLAGS['news']['db.id'] == $key)
                            return true;
                    } else {
                        if ($SYSTEM_FLAGS['news']['db.alt'] == $key)
                            return true;
                    }
                }
                break;

            // -- CATEGORY (master or any)
            case 'mastercat':
            case 'cat':
                if ((!isset($SYSTEM_FLAGS['news']['db.categories'])) || ($SYSTEM_FLAGS['news']['db.categories'] == ''))
                    continue;

                // List of categories from news
                foreach ($rVal as $key) {
                    if (ctype_digit($key)) {
                        if (($rType == 'mastercat') && ($SYSTEM_FLAGS['news']['db.categories'][0] == $key))
                            return true;
                        if (($rType == 'cat') && (in_array($key, $SYSTEM_FLAGS['news']['db.categories'])))
                            return true;
                    } else {
                        if (($rType == 'mastercat') && (is_array($catz[$key])) && ($SYSTEM_FLAGS['news']['db.categories'][0] == $catz[$key]['id']))
                            return true;
                        if (($rType == 'cat') && (is_array($catz[$key])) && (in_array($catz[$key]['id'], $SYSTEM_FLAGS['news']['db.categories'])))
                            return true;
                    }
                }
                break;
        }
    }

    return false;
}

// Check if current user has specified permissions
// RULE is: <ENTRY1>[|<ENTRY2>[|<ENTRY3>...]]
// ENTRY1,2,.. is: <PLUGIN>[:<HANDLER>]
function twigIsPerm($rules) {
    //
}

function twigIsSet($context, $val) {
    //print "call TWIG::isSet(".var_export($context, true)." || ".var_export($val, true).");<br/>";
    //print "call TWIG::isSet(".var_export($val, true).");<br/>";
    if ((!isset($val)) || (is_array($val) && (count($val) == 0)))
        return false;

    return true;
}

function twigDebugValue($val) {
    return "<b>debugValue:</b><pre>" . var_export($val, true) . "</pre>";
}

function twigDebugContext($context) {
    return "<b>debugContext:</b><pre>" . var_export($context, true) . "</pre>";
}

function twigGetCategoryTree($masterCategory = null, $flags = array()) {
    if (!is_array($flags))
        $flags = array();

    if (!isset($flags['returnData']))
        $flags['returnData'] = true;

    return generateCategoryMenu($masterCategory, $flags);
}

function TwigEngineMSG($type, $text, $info = '') {
    $cfg = ['type' => $type];

    if ($text) $cfg['text'] = $text;
    if ($info) $cfg['info'] = $info;

    return msg($cfg, 0, 2);
}

// Load variables from template
// $die	- flag: generate die() in case when file is not found (else - return false)
// $loadMode - flag:
//			0 - use SITE template
//			1 - use ADMIN PANEL template
function templateLoadVariables($die = false, $loadMode = 0) {

    global $TemplateCache;

    if (isset($TemplateCache[$loadMode ? 'admin' : 'site']['#variables']))
        return true;

    $filename = ($loadMode ? tpl_actions : tpl_site) . 'variables.ini';
    if (!is_file($filename)) {
        if ($die) {
            die('Internal error: cannot locate Template Variables file');
        }

        return false;
    }
    $TemplateCache[$loadMode ? 'admin' : 'site']['#variables'] = parse_ini_file($filename, true);

    //print "<pre>".var_export($TemplateCache, true)."</pre>";
    return true;
}

// Call plugin execution via TWIG
function twigCallPlugin($funcName, $params) {
    global $TWIGFUNC;

    // Try to preload function if required
    if (!isset($TWIGFUNC[$funcName])) {
        if (preg_match("#^(.+?)\.(.+?)$#", $funcName, $m)) {
            loadPlugin($m[1], 'twig');
        }
    }

    if (!isset($TWIGFUNC[$funcName])) {
        print "ERROR :: callPlugin - no function [$funcName]<br/>\n";

        return;
    }

    return call_user_func($TWIGFUNC[$funcName], $params);
}

// Truncate HTML
function twigTruncateHTML($string, $len = 70, $finisher = '') {
    global $parse;

    return $parse->truncateHTML($string, $len, $finisher);
}
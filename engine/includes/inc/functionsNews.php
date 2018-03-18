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

function resolveCatNames($idList, $split = ', ') {
    global $catz, $catmap;

    $inames = array();
    foreach ($idList as $id) {
        if (isset($catmap[$id])) {
            $inames [] = $catz[$catmap[$id]]['name'];
        }
    }

    return join($split, $inames);
}

function GetCategories($catid, $plain = false, $firstOnly = false) {
    global $catz, $catmap;

    $catline = array();
    $cats = is_array($catid) ? $catid : explode(",", $catid);

    if (count($cats) && $firstOnly) {
        $cats = array($cats[0]);
    }
    foreach ($cats as $v) {
        if (isset($catmap[$v])) {
            $row = $catz[$catmap[$v]];
            $catline[] = ($plain) ? $row['name'] : "<a href=\"" . generateLink('news', 'by.category', array('category' => $row['alt'], 'catid' => $row['id'])) . "\">" . $row['name'] . "</a>";
        }
    }

    return ($catline ? implode(", ", $catline) : '');
}

function makeCategoryInfo($ctext) {

    global $catz, $catmap, $config;

    $list = array();
    $cats = is_array($ctext) ? $ctext : explode(",", $ctext);

    foreach ($cats as $v) {
        if (isset($catmap[$v])) {
            $row = $catz[$catmap[$v]];
            $url = generateLink('news', 'by.category', array('category' => $row['alt'], 'catid' => $row['id']));
            $record = array(
                'id'    => $row['id'],
                'level' => $row['poslevel'],
                'alt'   => $row['alt'],
                'name'  => $row['name'],
                'info'  => $row['info'],
                'url'   => $url,
                'text'  => '<a href="' . $url . '">' . $row['name'] . '</a>',
            );
            if ($row['icon_id'] && $row['icon_folder']) {
                $record['icon'] = array(
                    'url'        => $config['attach_url'] . '/' . $row['icon_folder'] . '/' . $row['icon_name'],
                    'purl'       => $row['icon_preview'] ? ($config['attach_url'] . '/' . $row['icon_folder'] . '/thumb/' . $row['icon_name']) : '',
                    'width'      => $row['icon_width'],
                    'height'     => $row['icon_height'],
                    'pwidth'     => $row['icon_pwidth'],
                    'pheight'    => $row['icon_pheight'],
                    'isExtended' => true,
                    'hasPreview' => $row['icon_preview'] ? true : false,
                );
            } else if ($row['icon']) {
                $record['icon'] = array(
                    'url'        => $row['icon'],
                    'isExtended' => false,
                    'hasPreview' => false,
                );
            }

            $list [] = $record;
        }
    }

    return $list;
}

//
// New category menu generator
function generateCategoryMenu($treeMasterCategory = null, $flags = array()) {

    global $mysql, $catz, $tpl, $config, $CurrentHandler, $SYSTEM_FLAGS, $TemplateCache, $twig, $twigLoader;

    // Load template variables
    templateLoadVariables(true);
    $markers = $TemplateCache['site']['#variables']['category_tree'];

    if (!isset($markers['class.active']))
        $markers['class.active'] = 'active_cat';

    if (!isset($markers['class.inactive']))
        $markers['class.inactive'] = '';

    if (!isset($markers['mark.default']))
        $markers['mark.default'] = '&#8212;';

    // Determine working mode - old or new
    // If template 'news.categories' exists - use `new way`, else - old
    if (file_exists(tpl_site . 'news.categories.tpl') || (isset($flags['returnData']) && $flags['returnData'])) {

        $tVars = array();
        $tEntries = array();
        $tIDs = array();

        $treeSelector = array(
            'defined'     => false,
            'id'          => 0,
            'skipDefined' => false,
            'started'     => false,
            'level'       => 0,
        );

        if (!is_null($treeMasterCategory) && preg_match('#^(\:){0,1}(\d+)$#', $treeMasterCategory, $m)) {
            $treeSelector['defined'] = true;
            $treeSelector['skipDefined'] = $m[1] ? true : false;
            $treeSelector['id'] = intval($m[2]);
        }

        foreach ($catz as $k => $v) {
            if (!mb_substr($v['flags'], 0, 1)) continue;

            // If tree selector is active - skip unwanted entries
            if ($treeSelector['defined']) {
                if ($treeSelector['started']) {
                    if ($v['poslevel'] <= $treeSelector['level']) {
                        break;
                    }
                } else {
                    if ($v['id'] == $treeSelector['id']) {
                        $treeSelector['started'] = true;
                        $treeSelector['level'] = $v['poslevel'];

                        if ($treeSelector['skipDefined'])
                            continue;
                    } else {
                        continue;
                    }
                }
            }

            $tEntry = array(
                'id'      => $v['id'],
                'cat'     => $v['name'],
                'link'    => ($v['alt_url'] == '') ? generateLink('news', 'by.category', array('category' => $v['alt'], 'catid' => $v['id'])) : $v['alt_url'],
                'mark'    => isset($markers['mark.level.' . $v['poslevel']]) ? $markers['mark.level.' . $v['poslevel']] : str_repeat($markers['mark.default'], $v['poslevel']),
                'level'   => $v['poslevel'],
                'info'    => $v['info'],
                'counter' => $v['posts'],
                'icon'    => $v['icon'],

                'flags' => array(
                    'active'  => (isset($SYSTEM_FLAGS['news']['currentCategory.id']) && ($v['id'] == $SYSTEM_FLAGS['news']['currentCategory.id'])) ? true : false,
                    'counter' => ($config['category_counters'] && $v['posts']) ? true : false,
                )
            );
            $tEntries [] = $tEntry;
            $tIDs [] = $v['id'];
        }

        // Update `hasChildren` and `closeLevel_X` flags for items
        for ($i = 0; $i < count($tEntries); $i++) {
            $tEntries[$i]['flags']['hasChildren'] = true;
            if (($i == (count($tEntries) - 1)) || ($tEntries[$i]['level'] >= $tEntries[$i + 1]['level'])) {
                // Mark that this is last item in this level
                $tEntries[$i]['flags']['hasChildren'] = false;

                // Mark all levels that are closed after this item
                if ($i == (count($tEntries) - 1)) {
                    for ($x = 0; $x <= $tEntries[$i]['level']; $x++) {
                        $tEntries[$i]['flags']['closeLevel_' . $x] = true;
                    }
                } else {
                    for ($x = $tEntries[$i + 1]['level']; $x <= $tEntries[$i]['level']; $x++) {
                        $tEntries[$i]['flags']['closeLevel_' . $x] = true;
                    }
                }
                if ($tEntries[$i]['level'] > $tEntries[$i + 1]['level'])
                    $tEntries[$i]['closeToLevel'] = intval($tEntries[$i + 1]['level']);
            }

        }

        if ($flags['returnData']) {
            return $flags['onlyID'] ? $tIDs : $tEntries;
        }

        // Prepare conversion maps
        $conversionConfig = array(
            '[entries]'        => '{% for entry in entries %}',
            '[/entries]'       => '{% endfor %}',
            '[flags.active]'   => '{% if (entry.flags.active) %}',
            '[/flags.active]'  => '{% endif %}',
            '[!flags.active]'  => '{% if (not entry.flags.active) %}',
            '[/!flags.active]' => '{% endif %}',
            '[flags.counter]'  => '{% if (entry.flags.counter) %}',
            '[/flags.counter]' => '{% endif %}',
        );

        $tVars['entries'] = $tEntries;
        $twigLoader->setConversion('news.categories.tpl', $conversionConfig);
        $xt = $twig->loadTemplate('news.categories.tpl');

        return $xt->render($tVars);

    }

    // OLD STYLE menu generation
    $result = '';

    $flagSkip = false;
    $skipLevel = 0;
    $tpl->template('categories', tpl_site);
    foreach ($catz as $k => $v) {
        // Skip category if it's disabled in category tree
        if ($flagSkip) {
            if ($v['poslevel'] > $skipLevel)
                continue;
            $flagSkip = false;
        }

        if (!mb_substr($v['flags'], 0, 1)) {
            $flagSkip = true;
            $skipLevel = $v['poslevel'];
            continue;
        }

        $tvars['vars'] = array(
            'if_active' => (isset($SYSTEM_FLAGS['news']['currentCategory.id']) && ($v['id'] == $SYSTEM_FLAGS['news']['currentCategory.id'])) ? $markers['class.active'] : $markers['class.inactive'],
            'link'      => ($v['alt_url'] == '') ? generateLink('news', 'by.category', array('category' => $v['alt'], 'catid' => $v['id'])) : $v['alt_url'],
            'mark'      => isset($markers['mark.level.' . $v['poslevel']]) ? $markers['mark.level.' . $v['poslevel']] : str_repeat($markers['mark.default'], $v['poslevel']),
            'level'     => $v['poslevel'],
            'cat'       => $v['name'],
            'counter'   => ($config['category_counters'] && $v['posts']) ? ('[' . $v['posts'] . ']') : '',
            'icon'      => $v['icon'],
        );
        $tvars['regx']['[\[icon\](.*)\[/icon\]]'] = trim($v['icon']) ? '$1' : '';
        switch (intval(mb_substr($v['flags'], 1, 1))) {
            case 0:
                $rmode = true;
                break;
            case 1:
                $rmode = ($v['posts']) ? true : false;
                break;
            case 2:
                $rmode = false;
                break;
        }
        $tvars['regx']['#\[if_link\](.+?)\[/if_link\]#is'] = $rmode ? '$1' : '';

        $tpl->vars('categories', $tvars);

        $result .= $tpl->show('categories');
    }

    return $result;
}
// make an array for filtering from text line like 'abc-def,dfg'
function generateCategoryArray($categories) {
    global $catz;

    $carray = array();
    foreach (explode(",", $categories) as $v) {
        $xa = array();
        foreach (explode("-", $v) as $n) {
            if (is_array($catz[trim($n)]))
                array_push($xa, $catz[trim($n)]['id']);
        }
        if (count($xa))
            array_push($carray, $xa);
    }

    return $carray;
}

// make a SQL filter for specified array
function generateCategoryFilter() {
    //
}

function GetCategoryById($id) {
    global $catz;

    foreach ($catz as $cat) {
        if ($cat['id'] == $id) {
            return $cat;
        }
    }

    return [];
}

// makeCategoryList - make <SELECT> list of categories
// Params: set via named array
// * name      		- name field of <SELECT>
// * selected  		- ID of category to be selected or array of IDs to select (in list mode)
// * skip      		- ID of category to skip or array of IDs to skip
// * skipDisabled	- skip disabled areas
// * doempty   		- add empty category to the beginning ("no category"), value = 0
// * greyempty		- show empty category as `grey`
// * doall     		- add category named "ALL" to the beginning, value is empty
// * allMarker		- marker value for `doall`
// * dowithout		- add "Without category" after "ALL", value = 0
// * nameval   		- use DB field "name" instead of ID in HTML option value
// * resync    		- flag, if set - we make additional lookup into database for new category list
// * checkarea	 	- flag, if set - generate a list of checkboxes instead of <SELECT>
// * class     		- HTML class name
// * style     		- HTML style
// * disabledarea	- mark all entries (for checkarea) as disabled [for cases when extra categories are not allowed]
// * noHeader		- Don't write header (<select>..</select>) in output
// * returnOptArray	- FLAG: if we should return OPTIONS (with values) array instead of data
function makeCategoryList($params = array()) {

    global $catz, $lang, $mysql;

    $optList = array();

    if (!isset($params['skip'])) {
        $params['skip'] = array();
    }
    if (!is_array($params['skip'])) {
        $params['skip'] = $params['skip'] ? array($params['skip']) : array();
    }
    $name = array_key_exists('name', $params) ? $params['name'] : 'category';

    $out = '';
    if (!isset($params['checkarea']) || !$params['checkarea']) {
        if (empty($params['noHeader'])) {
            $out = "<select name=\"$name\" id=\"catmenu\"" .
                ((isset($params['style']) && ($params['style'] != '')) ? ' style="' . $params['style'] . '"' : '') .
                ((isset($params['class']) && ($params['class'] != '')) ? ' class="' . $params['class'] . '"' : '') .
                ">\n";
        }
        if (isset($params['doempty']) && $params['doempty']) {
            $out .= "<option " . (((isset($params['greyempty']) && $params['greyempty'])) ? 'style="background: #c41e3a;" ' : '') . "value=\"0\">" . $lang['no_cat'] . "</option>\n";
            $optList [] = array('k' => 0, 'v' => $lang['no_cat']);
        }
        if (isset($params['doall']) && $params['doall']) {
            $out .= "<option value=\"" . (isset($params['allmarker']) ? $params['allmarker'] : '') . "\">" . $lang['sh_all'] . "</option>\n";
            $optList [] = array('k' => (isset($params['allmarker']) ? $params['allmarker'] : ''), 'v' => $lang['sh_all']);
        }
        if (isset($params['dowithout']) && $params['dowithout']) {
            $out .= "<option value=\"0\"" . (((!is_null($params['selected'])) && ($params['selected'] == 0)) ? ' selected="selected"' : '') . ">" . $lang['sh_empty'] . "</option>\n";
            $optList [] = array('k' => 0, 'v' => $lang['sh_empty']);
        }
    }
    if (isset($params['resync']) && $params['resync']) {
        $catz = array();
        foreach ($mysql->select("select * from `" . prefix . "_category` order by posorder asc", 1) as $row) {
            $catz[$row['alt']] = $row;
            $catmap[$row['id']] = $row['alt'];
        }
    }

    foreach ($catz as $k => $v) {
        if (in_array($v['id'], $params['skip'])) {
            continue;
        }
        if (isset($params['skipDisabled']) && $params['skipDisabled'] && ($v['alt_url'] != '')) {
            continue;
        }
        if (isset($params['checkarea']) && $params['checkarea']) {
            $out .= str_repeat('&#8212; ', $v['poslevel']) .
                '<label><input type="checkbox" name="' .
                $name .
                '_' .
                $v['id'] .
                '" value="1"' .
                ((isset($params['selected']) && is_array($params['selected']) && in_array($v['id'], $params['selected'])) ? ' checked="checked"' : '') .
                (((($v['alt_url'] != '') || (isset($params['disabledarea']) && $params['disabledarea']))) ? ' disabled="disabled"' : '') .
                '/> ' .
                $v['name'] .
                "</label><br/>\n";
        } else {
            $out .= "<option value=\"" . ((isset($params['nameval']) && $params['nameval']) ? $v['name'] : $v['id']) . "\"" . ((isset($params['selected']) && ($v['id'] == $params['selected'])) ? ' selected="selected"' : '') . ($v['alt_url'] != '' ? ' disabled="disabled" style="background: #c41e3a;"' : '') . ">" . str_repeat('&#8212; ', $v['poslevel']) . $v['name'] . "</option>\n";
            $optList [] = array('k' => ((isset($params['nameval']) && $params['nameval']) ? $v['name'] : $v['id']), 'v' => str_repeat('&#8212; ', $v['poslevel']) . $v['name']);
        }
    }
    if (!isset($params['checkarea']) || !$params['checkarea']) {
        if (empty($params['noHeader'])) {
            $out .= "</select>";
        }
    }

    if (isset($params['returnOptArray']) && $params['returnOptArray'])
        return $optList;

    return $out;
}

// Fill variables for news:
// * $row		- SQL row
// * $fullMode		- flag if desired mode is full
// * $page		- page No to show in full mode
// * $disablePagination	- flag if pagination should be disabled
// * $regenShortNews	- array, describe what to do with `short news`
//	mode:
//		''	- no modifications
//		'auto'	- generate short news from long news in case if short news is empty
//		'force'	- generate short news from long news in any case
//	len		- size in chars for part of long news to use
//	finisher	- chars that will be added into the end to indicate that this is truncated line ( default = '...' )
//function Prepare($row, $page) {
function newsFillVariables($row, $fullMode, $page = 0, $disablePagination = 0, $regenShortNews = array()) {
    global $config, $parse, $lang, $catz, $catmap, $CurrentHandler, $currentCategory, $TemplateCache, $mysql, $PHP_SELF;

    $tvars = array(
        'vars'  => array(
            'news'       => array('id' => $row['id']),
            'pagination' => '',

        ),
        'flags' => array()
    );

    $alink = checkLinkAvailable('uprofile', 'show') ?
        generateLink('uprofile', 'show', array('name' => $row['author'], 'id' => $row['author_id'])) :
        generateLink('core', 'plugin', array('plugin' => 'uprofile', 'handler' => 'show'), array('name' => $row['author'], 'id' => $row['author_id']));

    // [TWIG] news.author.*
    $tvars['vars']['news']['author']['name'] = $row['author'];
    $tvars['vars']['news']['author']['id'] = $row['author_id'];
    $tvars['vars']['news']['author']['url'] = $alink;

    // [TWIG] number of comments
    if (getPluginStatusActive('comments'))
        $tvars['vars']['p']['comments']['count'] = $row['com'];

    $tvars['vars']['author'] = "<a href=\"" . $alink . "\" target=\"_blank\">" . $row['author'] . "</a>";
    $tvars['vars']['author_link'] = $alink;
    $tvars['vars']['author_name'] = $row['author'];

    // [TWIG] news.flags.fullMode: if we're in full mode
    $tvars['vars']['news']['flags']['isFullMode'] = $fullMode ? true : false;

    $nlink = newsGenerateLink($row);

    // Divide into short and full content
    if ($config['extended_more']) {
        if (preg_match('#^(.*?)\<\!--more(?:\="(.+?)"){0,1}--\>(.+)$#uis', $row['content'], $pres)) {
            $short = $pres[1];
            $full = $pres[3];
            $more = $pres[2];
        } else {
            $short = $row['content'];
            $full = '';
            $more = '';
        }
    } else {
        list ($short, $full) = array_pad(explode('<!--more-->', $row['content']), 2, '');
        $more = '';
    }
    // Default page number
    $page = 1;

    // Check if long part is divided into several pages
    if ($full && (!$disablePagination) && (mb_strpos($full, "<!--nextpage-->") !== false)) {
        $page = intval(isset($CurrentHandler['params']['page']) ? $CurrentHandler['params']['page'] : (isset($_REQUEST['page']) ? $_REQUEST['page'] : 0));
        if ($page < 1) $page = 1;

        $pagination = '';
        $pages = explode("<!--nextpage-->", $full);
        $pcount = count($pages);

        // [TWIG] news.pageCount, pageNumber
        $tvars['vars']['news']['pageCount'] = count($pages);
        $tvars['vars']['news']['pageNumber'] = $page;

        $tvars['vars']['pageCount'] = count($pages);
        $tvars['vars']['page'] = $page;

        if ($pcount > 1) {
            // Prepare VARS for pagination
            $catid = intval(array_shift(explode(',', $row['catid'])));

            $cname = 'none';
            if ($catid && isset($catmap[$catid]))
                $cname = $catmap[$catid];

            // Generate pagination within news
            $paginationParams = checkLinkAvailable('news', 'news') ?
                array('pluginName' => 'news', 'pluginHandler' => 'news', 'params' => array('category' => $cname, 'catid' => $catid, 'altname' => $row['alt_name'], 'id' => $row['id']), 'xparams' => array(), 'paginator' => array('page', 0, false)) :
                array('pluginName' => 'core', 'pluginHandler' => 'plugin', 'params' => array('plugin' => 'news', 'handler' => 'news'), 'xparams' => array('category' => $cname, 'catid' => $catid, 'altname' => $row['alt_name'], 'id' => $row['id']), 'paginator' => array('page', 1, false));

            templateLoadVariables(true);
            $navigations = $TemplateCache['site']['#variables']['navigation'];

            // Show pagination bar
            $tvars['vars']['pagination'] = generatePagination($page, 1, $pcount, 10, $paginationParams, $navigations);

            // [TWIG] news.pagination
            $tvars['vars']['news']['pagination'] = $tvars['vars']['pagination'];

            if ($page > 1) {
                $tvars['vars']['short-story'] = '';
            }
            $full = $pages[$page - 1];
            $tvars['vars']['[pagination]'] = '';
            $tvars['vars']['[/pagination]'] = '';
            $tvars['vars']['news']['flags']['hasPagination'] = true;
        }
    } else {
        $tvars['regx']["'\[pagination\].*?\[/pagination\]'si"] = '';
        $tvars['vars']['news']['flags']['hasPagination'] = false;
    }

    // Conditional blocks for full-page
    if ($full) {
        $tvars['regx']['#\[page-first\](.*?)\[\/page-first\]#si'] = ($page < 2) ? '$1' : '';
        $tvars['regx']['#\[page-next\](.*?)\[\/page-next\]#si'] = ($page > 1) ? '$1' : '';
    }

    // Delete "<!--nextpage-->" if pagination is disabled
    if ($disablePagination)
        $full = str_replace("<!--nextpage-->", "\n", $full);

    // If HTML code is not permitted - LOCK it
    $title = $row['title'];

    if (!($row['flags'] & 2)) {
        $short = str_replace('<', '&lt;', $short);
        $full = str_replace('<', '&lt;', $full);
        $title = secure_html($title);
    }
    $tvars['vars']['title'] = $title;

    // [TWIG] news.title
    $tvars['vars']['news']['title'] = $row['title'];

    // Make conversion
    if ($config['blocks_for_reg']) {
        $short = $parse->userblocks($short);
        $full = $parse->userblocks($full);
    }
    if ($config['use_bbcodes']) {
        $short = $parse->bbcodes($short);
        $full = $parse->bbcodes($full);
    }
    if ($config['use_htmlformatter'] && (!($row['flags'] & 1))) {
        $short = $parse->htmlformatter($short);
        $full = $parse->htmlformatter($full);
    }
    if ($config['use_smilies']) {
        $short = $parse->smilies($short);
        $full = $parse->smilies($full);
    }
    if (1 && templateLoadVariables()) {

        $short = $parse->parseBBAttach($short, $mysql, $TemplateCache['site']['#variables']);
        $full = $parse->parseBBAttach($full, $mysql, $TemplateCache['site']['#variables']);
    }

    // Check if we need to regenerate short news
    if (isset($regenShortNews['mode']) && ($regenShortNews['mode'] != '')) {
        if ((($regenShortNews['mode'] == 'force') || (trim($short) == '')) && (trim($full) != '')) {
            // REGEN
            if (!isset($regenShortNews['len']) || (intval($regenShortNews['len']) < 0)) {
                $regenShortNews['len'] = 50;
            }
            if (!isset($regenShortNews['finisher'])) {
                $regenShortNews['finisher'] = '...';
            }
            $short = $parse->truncateHTML($full, $regenShortNews['len'], $regenShortNews['finisher']);
        }

    }

    $tvars['vars']['short-story'] = $short;
    $tvars['vars']['full-story'] = $full;

    // [TWIG] news.short, news.full
    $tvars['vars']['news']['short'] = $short;
    $tvars['vars']['news']['full'] = $full;

    // Activities for short mode
    if (!$fullMode) {
        // Make link for full news
        $tvars['vars']['[full-link]'] = "<a href=\"" . $nlink . "\">";
        $tvars['vars']['[/full-link]'] = "</a>";

        $tvars['vars']['[link]'] = "<a href=\"" . $nlink . "\">";
        $tvars['vars']['[/link]'] = "</a>";

        $tvars['vars']['full-link'] = $nlink;

        // Make blocks [fullnews] .. [/fullnews] and [nofullnews] .. [/nofullnews]
        $tvars['vars']['news']['flags']['hasFullNews'] = mb_strlen($full) ? true : false;
        if (mb_strlen($full)) {
            // we have full news
            $tvars['vars']['[fullnews]'] = '';
            $tvars['vars']['[/fullnews]'] = '';

            $tvars['regx']["'\[nofullnews\].*?\[/nofullnews\]'si"] = '';
        } else {
            // we have ONLY short news
            $tvars['vars']['[nofullnews]'] = '';
            $tvars['vars']['[/nofullnews]'] = '';

            $tvars['regx']["'\[fullnews\].*?\[/fullnews\]'si"] = '';

        }

    } else {
        $tvars['regx']["#\[full-link\].*?\[/full-link\]#si"] = '';
        $tvars['regx']["#\[link\](.*?)\[/link\]#si"] = '$1';
    }

    $tvars['vars']['pinned'] = ($row['pinned']) ? "news_pinned" : "";

    $tvars['vars']['category'] = @GetCategories($row['catid']);
    $tvars['vars']['masterCategory'] = @GetCategories($row['catid'], false, true);

    // [TWIG] news.categories.*
    $tCList = makeCategoryInfo($row['catid']);
    $tvars['vars']['news']['categories']['count'] = count($tCList);
    $tvars['vars']['news']['categories']['list'] = $tCList;
    $tvars['vars']['news']['categories']['masterText'] = count($tCList) > 0 ? $tCList[0]['text'] : '';

    $tCTextList = array();
    foreach ($tCList as $tV)
        $tCTextList [] = $tV['text'];

    $tvars['vars']['news']['categories']['text'] = join(", ", $tCTextList);

    $tvars['vars']['[print-link]'] = "<a href=\"" . newsGenerateLink($row, true, $page) . "\">";
    $tvars['vars']['print-link'] = newsGenerateLink($row, true, $page);
    $tvars['vars']['print_link'] = newsGenerateLink($row, true, $page);
    $tvars['vars']['[/print-link]'] = "</a>";
    $tvars['vars']['news_link'] = $nlink;

    // [TWIG] news.url
    $tvars['vars']['news']['url'] = array(
        'full'  => $nlink,
        'print' => newsGenerateLink($row, true, $page),
    );

    // [TWIG] news.flags.isPinned
    $tvars['vars']['news']['flags']['isPinned'] = ($row['pinned']) ? true : false;

    $tvars['vars']['news-id'] = $row['id'];
    $tvars['vars']['news_id'] = $row['id'];
    $tvars['vars']['php-self'] = $PHP_SELF;

    $tvars['vars']['date'] = LangDatetime($config['timestamp_active'], $row['postdate']);
    $tvars['vars']['views'] = $row['views'];

    // [TWIG] news.date, news.dateStamp, news.views
    $tvars['vars']['news']['date'] = LangDatetime($config['timestamp_active'], $row['postdate']);
    $tvars['vars']['news']['dateStamp'] = $row['postdate'];
    $tvars['vars']['news']['views'] = $row['views'];

    if ($row['editdate'] > $row['postdate']) {
        // [TWIG] news.flags.isUpdated, news.update, news.updateStamp
        $tvars['vars']['news']['flags']['isUpdated'] = true;
        $tvars['vars']['news']['update'] = LangDatetime($config['timestamp_updated'], $row['editdate']);
        $tvars['vars']['news']['updateStamp'] = $row['editdate'];

        $tvars['regx']['[\[update\](.*)\[/update\]]'] = '$1';
        $tvars['vars']['update'] = LangDatetime($config['timestamp_updated'], $row['editdate']);
        $tvars['vars']['updateStamp'] = $row['editdate'];
    } else {
        // [TWIG] news.flags.isUpdated, news.update, news.updateStamp
        $tvars['vars']['news']['flags']['isUpdated'] = false;

        $tvars['regx']['[\[update\](.*)\[/update\]]'] = '';
        $tvars['vars']['update'] = '';
    }

    if ($more == '') {
        // [TWIG] news.flags.hasPersonalMore
        $tvars['vars']['news']['flags']['hasPersonalMore'] = false;

        $tvars['vars']['[more]'] = '';
        $tvars['vars']['[/more]'] = '';
    } else {
        // [TWIG] news.flags.hasPersonalMore, news.personalMore
        $tvars['vars']['news']['flags']['hasPersonalMore'] = true;
        $tvars['vars']['news']['personalMore'] = $more;

        $tvars['vars']['personalMore'] = $more;
        $tvars['regx']['#\[more\](.*?)\[/more\]#is'] = $more;
    }

    return $tvars;
}
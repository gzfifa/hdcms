<?php
if (!defined("HDPHP_PATH")) exit;
//获得栏目url
function get_category_url($cid)
{
    $category = F("category", false, CATEGORY_CACHE_PATH);
    $cat = $category[$cid];
    if ($cat['cattype'] == 3) {
        return $cat['cat_redirecturl'];
    } else if ($cat['urltype'] == 1) { //静态访问
        return __ROOT__ . '/' . C("HTML_PATH") . '/' . $cat['catdir'] . '/index.html';
    } else {
        return __ROOT__ . '/index.php?a=Content&c=Index&m=category&cid=' . $cat['cid'];
    }

}

//获得栏目模板
function get_category_tpl($cid)
{
    $category = F("category", false, CATEGORY_CACHE_PATH);
    if ($category[$cid]['cattype'] == 2) {
        $tpl_file = $category[$cid]['index_tpl'];
    } else if ($category[$cid]['cattype'] == 1) {
        $tpl_file = $category[$cid]['list_tpl'];
    } else {
        //外部链接
        return false;
    }
    return str_replace('{style}', './template/' . C("WEB_STYLE"), $tpl_file);
}

//获得内容页模板
function get_content_tpl($aid)
{
    $db = K("ContentView");
    $content = $db->join("category")->find($aid);
    $tpl = empty($content['template']) ? $content['arc_tpl'] : $content['template'];
    return str_replace('{style}', './template/' . C("WEB_STYLE"), $tpl);
}

//获得内容页url地址
function get_content_url($field)
{
    $cat = F("category", false, CATEGORY_CACHE_PATH);
    $category = $cat[$field['cid']];
    if ($category['is_arc_html']) {
        return __ROOT__ . '/' . get_content_html($field);
    } else {
        return __WEB__ . "?a=Content&c=Index&m=content&cid={$field['cid']}&aid=" . $field['aid'];
    }
}

//获得内容页静态地址
function get_content_html($field)
{
    if (!empty($field['html_path'])) return $field['html_path'];
    $cat = F("category", false, CATEGORY_CACHE_PATH);
    $category = $cat[$field['cid']];
    $arc_html_url = $category['arc_html_url'];
    //栏目静态规则配置错误
    if (empty($arc_html_url)) {
        return null;
    }
    $_s = array(
        '{catdir}', '{y}', '{m}', '{d}', '{aid}'
    );
    //文章发表时间
    $time = getdate($field['addtime']);
    $_r = array(
        $category['catdir'],
        $time['year'],
        $time['mon'],
        $time['mday'],
        $field['aid']
    );
    foreach ($_s as $n => $s) {
        $arc_html_url = str_replace($s, $_r[$n], $arc_html_url);
    }
    $url = rtrim(C("HTML_PATH"), '/\\') . '/' . $arc_html_url;
    //生成静态
    return $url;
}

//获得栏目静态html
function get_category_html($field)
{
    $cat = F("category", false, CATEGORY_CACHE_PATH);
    $category = $cat[$field['cid']];
    $arc_html_url = $category['arc_html_url'];
    //栏目静态规则配置错误
    if (empty($arc_html_url)) {
        return null;
    }
    $_s = array(
        '{catdir}', '{y}', '{m}', '{d}', '{aid}'
    );
    //文章发表时间
    $time = getdate($field['addtime']);
    $_r = array(
        $category['catdir'],
        $time['year'],
        $time['mon'],
        $time['mday'],
        $field['aid']
    );
    foreach ($_s as $n => $s) {
        $arc_html_url = str_replace($s, $_r[$n], $arc_html_url);
    }
    $url = rtrim(C("HTMLDIR"), '/\\') . '/' . $arc_html_url;
    //生成静态
    return $url;
}
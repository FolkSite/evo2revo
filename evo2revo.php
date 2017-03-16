<?php
/**
 * https://quasi-art.ru/
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(1);

$config = [
    'database' => 'rp',
    'password' => '',
    'username' => 'root',
    'prefix_from' => 'modx_',
    'prefix_to' => 'rp_',
    'port_content' => true,
    'port_htmlsnippets' => true,
    'port_templates' => true,
    'port_tv' => true,
];
$truncateQueries = [
    'TRUNCATE  `'.$config['prefix_to'].'site_content`',
    'TRUNCATE  `'.$config['prefix_to'].'site_htmlsnippets`',
    'TRUNCATE  `'.$config['prefix_to'].'site_templates`',
    'TRUNCATE  `'.$config['prefix_to'].'site_tmplvars`',
    'TRUNCATE  `'.$config['prefix_to'].'site_tmplvar_contentvalues`',
    'TRUNCATE  `'.$config['prefix_to'].'site_tmplvar_templates`',
];

/**
 * Портирование тегов
 * [[sitemap
 */
function processTags($content) {
    $content = str_replace('{{', '[[$', $content);
    $content = str_replace('}}', ']]', $content);
    $content = str_replace('[(site_name)]', '[[++site_name]]', $content);
    $content = str_replace('[(site_start)]', '[[++site_start]]', $content);
    $content = str_replace('[(site_url)]', '[[++site_url]]', $content);
    $content = str_replace('[(modx_charset)]', 'utf-8', $content);
    $content = str_replace('[*pagetitle*]', '[[*pagetitle]]', $content);
    $content = str_replace('[*content*]', '[[*content]]', $content);
    $content = str_replace('[*longtitle*]', '[[*longtitle]]', $content);
    
    $content = str_replace('[+title+]', '[[+pagetitle]]', $content);
    $content = str_replace('[+introtext+]', '[[+introtext]]', $content);
    $content = str_replace('[+date+]', '[[+publishedon:date=`%j.%m.%Y`]]', $content);
    $content = str_replace('[+url+]', '[[~[[+id]]]]', $content);
    $content = str_replace('[+wf.link+]', '[[~[[+id]]]]', $content);
    $content = str_replace('[+wf.wrapper+]', '[[+wf.wrapper]]', $content);
    $content = str_replace('[+wf.title+]', '[[+wf.title]]', $content);
    $content = str_replace('[!if', '[[!If', $content);
    $content = str_replace('[[if', '[[If', $content);
    
    // Сниппеты
    $content = str_replace('[!Ditto', '[[!pdoResources', $content);
    $content = str_replace('!]', ']]', $content);
    $content = str_replace('[!AjaxSearch', '[[!SimpleSearch', $content);
    $content = str_replace('[!sitemap', '[[pdoSitemap', $content);
    $content = str_replace('[[sitemap', '[[pdoSitemap', $content);
    $content = str_replace('[[Wayfinder', '[[pdoMenu', $content);
    $content = str_replace('[!Wayfinder', '[[pdoMenu', $content);
    $content = str_replace('&startId=', '&parents=', $content);
    $content = str_replace('[!Breadcrumbs', '[[pdoCrumbs', $content);
    $content = str_replace('[[Breadcrumbs', '[[pdoCrumbs', $content);

    return $content;
}

/**
 * Обработка тегов для ссылок
 */
function processLinkTags($content, $count = 2000) {
    $count = (int)$count;
    if ($count > 1) {
        for ($i = 1; $i < $count; $i++) {
            $content = str_replace('[~'.$i.'~]', '[[~'.$i.']]', $content);
        }
    }
    return $content;

}

/**
 * Функция
 */
function pdoSet($fields, &$values, $source = array()) {
  $set = '';
  $values = array();
  foreach ($fields as $field) {
    if (isset($source[$field])) {
      $set.="`".str_replace("`","``",$field)."`". "=:$field, ";
      $values[$field] = $source[$field];
    }
  }
  return substr($set, 0, -2); 
}

try {
    $db = new PDO("mysql:dbname=".$config['database'].";charset=utf8;host=localhost", $config['username'], $config['password']);
    echo 'PDO connection object created<br><br>';
    
    foreach ($truncateQueries as $sql) {
        $db->query($sql);
    }
    
    /**
     * Resources
     */
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_content` ORDER BY `id` ';
    $contents = $db->query($sql);

    /**
     * Chunks
     */
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_htmlsnippets` ORDER BY `id` ';
    $chunks = $db->query($sql);

    /**
     * Templates
     */
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_templates` ORDER BY `id` ';
    $templates = $db->query($sql);

    /**
     * TV Values
     */ 
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_tmplvar_contentvalues` ORDER BY `id` ';
    $tvvalues = $db->query($sql);

    /**
     * TV to Templates
     */ 
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_tmplvar_templates` ORDER BY `id` ';
    $tvtemplates = $db->query($sql);
    
    /**
     * TV
     */ 
    $sql = 'SELECT * FROM `'.$config['prefix_from'].'site_tmplvars` ORDER BY `id` ';
    $tvs = $db->query($sql);

    /**
     * Перенос ресурсов
     */
    $items = array();
    foreach ($contents as $row)
    {
        $item = array(
            'id' => $row['id'],
            'type' => $row['type'],
            'contentType' => $row['contentType'],
            'pagetitle' => $row['pagetitle'],
            'longtitle' => $row['longtitle'],
            'description' => $row['description'],
            'alias' => $row['alias'],
            'published' => $row['published'],
            'pub_date' => $row['pub_date'],
            'unpub_date' => $row['unpub_date'],
            'parent' => $row['parent'],
            'isfolder' => $row['isfolder'],
            'introtext' => $row['introtext'],
            'content' => processTags($row['content']),
            'richtext' => $row['richtext'],
            'template' => $row['template'],
            'menuindex' => $row['menuindex'],
            'searchable' => $row['searchable'],
            'cacheable' => $row['cacheable'],
            'createdby' => $row['createdby'],
            'createdon' => $row['createdon'],
            'editedby' => $row['editedby'],
            'editedon' => $row['editedon'],
            'deleted' => $row['deleted'],
            'deletedon' => $row['deletedon'],
            'deletedby' => $row['deletedby'],
            'publishedon' => $row['publishedon'],
            'publishedby' => $row['publishedby'],
            'menutitle' => $row['menutitle'],
            'hidemenu' => $row['hidemenu'],
            'class_key' => 'modDocument',
            'context_key' => 'web',
        );
        $item['content'] = processLinkTags($item['content'], count($items));
        $items[] = $item;
    }
    foreach ($items as &$item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_content` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br>';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
    
    /**
     * Перенос чанков
     */
    $items = array();
    foreach ($chunks as $row)
    {
        $item = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'snippet' => processTags($row['snippet']),


        );
        $item['snippet'] = processLinkTags($item['snippet'], count($items));
        $items[] = $item;
    }
    foreach ($items as &$item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_htmlsnippets` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br>';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
    
    /**
     * Перенос шаблонов
     */
    $items = array();
    foreach ($templates as $row)
    {
        $item = array(
            'id' => $row['id'],
            'templatename' => $row['templatename'],
            'description' => $row['description'],
            'content' => processTags($row['content']),
        );
        $item['content'] = processLinkTags($item['content'], count($items));
        $items[] = $item;
    }
    foreach ($items as &$item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_templates` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br>';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
    
    /**
     * Перенос значений переменных шаблона
     */
    $items = array();
    foreach ($tvvalues as $row)
    {
        $item = array(
            'id' => $row['id'],
            'tmplvarid' => $row['tmplvarid'],
            'contentid' => $row['contentid'],
            'value' => $row['value'],
        );
        
        $items[] = $item;
    }
    foreach ($items as &$item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_tmplvar_contentvalues` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br>';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
    
    /**
     * Перенос отшений TV к шаблонам
     */
    $items = array();
    foreach ($tvtemplates as $row)
    {
        $item = array(
            'tmplvarid' => $row['tmplvarid'],
            'templateid' => $row['templateid'],
            'rank' => $row['rank'],
        );
        
        $items[] = $item;
    }
    foreach ($items as &$item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_tmplvar_templates` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br>';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
    
    /**
     * Перенос переменных шаблона
     */
    $items = array();
    foreach ($tvs as $row)
    {
        $item = array(
            'id' => $row['id'],
            'type' => $row['type'],
            'name' => $row['name'],
            'caption' => $row['caption'],
            'description' => $row['description'],
            'elements' => $row['elements'],
            'display' => 'default',
            'default_text' => $row['default_text'],
            'properties' => 'a:0:{}',
            'input_properties' => 'a:0:{}',
            'output_properties' => 'a:0:{}',
        );
        switch ($row['type']) {
            case 'dropdown':
                $item['type'] = 'listbox';
                $item['input_properties'] = 'a:7:{s:10:"allowBlank";s:4:"true";s:9:"listWidth";s:0:"";s:5:"title";s:0:"";s:9:"typeAhead";s:5:"false";s:14:"typeAheadDelay";s:3:"250";s:14:"forceSelection";s:5:"false";s:13:"listEmptyText";s:0:"";}';
                break;
            case 'text':
                $item['input_properties'] = 'a:5:{s:10:"allowBlank";s:4:"true";s:9:"maxLength";s:0:"";s:9:"minLength";s:0:"";s:5:"regex";s:0:"";s:9:"regexText";s:0:"";}';
                break;
        }
        $items[] = $item;
    }

    foreach ($items as $item) {
        $fields = array();
        $value = array();

        foreach ($item as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
        }

        $sql = "INSERT INTO `".$config['prefix_to']."site_tmplvars` SET ".pdoSet($fields, $values, $item);
        echo $sql.'<br />';
        $stm = $db->prepare($sql);
        $stm->execute($values);
    }
} catch(PDOException $e) {
    echo $e->getMessage();
}

<?php

/**
 * 页面 handler
 * Class Widget_CustomHandler
 */
define('PLUGINROOT','OneCircle/');
define('ABSPLUGINROOT',dirname(dirname(__DIR__)));
require_once ABSPLUGINROOT.'/core/utils/JKUtils.php';

class Widget_CustomHandler extends Widget_Archive
{

    public static function handleResourcesPage($archive, $archive_select)
    {
        if (JKUtils::hasPlugin("TePass")){
            $payPlugin = 'TePass';
        }else{
            $payPlugin = 'none';
        }
        $archive->setArchiveType('resources');
        // 条件 filter
        $category_mid = $archive->request->get("category","");
        $tag_mid = $archive->request->get("tag","");
        $price = $archive->request->get("price",-1);
        $order = $archive->request->get("order","");

        try {
            $db = Typecho_Db::get();
        } catch (Typecho_Db_Exception $e) {
            $db = $archive->db;
        }

        // 设置分页数据
        $archive->setPageRow([
            'payPlugin' => $payPlugin
        ]);

        if ($payPlugin == 'TePass'){
            /** 第一步：构造自定义 sql 得到初步筛选结果 */
            $select = $db->select("id","post_id","post_uid","post_see_type","post_islogin","post_price","post_sold_num",
                "table.contents.cid","title","slug","created","modified","text","authorId","template","type","status","password","commentsNum","views"
            )->from('table.tepass_posts')->join('table.contents','table.tepass_posts.post_id = table.contents.cid')
                ->where('table.contents.status = ?','publish');
            $filter_mids = [];
            if (isset($category_mid) and is_numeric($category_mid)){
                /** 如果是分类 */
                $categorySelect = $db->select()
                    ->from('table.metas')
                    ->where('type = ?', 'category')
                    ->limit(1);
                $categorySelect->where('mid = ?',$category_mid);
                $category = $db->fetchRow($categorySelect);

                if (empty($category)) {
                    throw new Typecho_Widget_Exception(_t('分类不存在'), 404);
                }
                $categoryListWidget = $archive->widget('Widget_Metas_Category_List', 'current=' . $category);
                $category = $categoryListWidget->filter($category);

                $children = $categoryListWidget->getAllChildren($category['mid']);
                $children[] = $category['mid'];
                if (isset($children) and !empty($children)){
                    $filter_mids = array_merge($filter_mids,$children);
                }
            }
            if (isset($tag_mid) and is_numeric($tag_mid)){
                $tagSelect = $db->select()->from('table.metas')
                    ->where('type = ?', 'tag')->limit(1)->where('mid = ?', $tag_mid);
                /** 如果是标签 */
                $tag = $db->fetchRow($tagSelect,
                    array($archive->widget('Widget_Abstract_Metas'), 'filter'));

                if (!$tag) {
                    throw new Typecho_Widget_Exception(_t('标签不存在'), 404);
                }
                if (isset($tag['mid'] ) and !empty($tag['mid'])){
                    $filter_mids[] = $tag['mid'];
                }
            }

            $select->join('table.relationships', 'table.tepass_posts.post_id = table.relationships.cid');

            // 条件 filter 位置不可移动
            // price -1:all / 0:free /1: login see /2:vip see /3:need pay
            if (isset($price) and is_numeric($price)){
                if ($price >= "0" and  $price < "4"){
                    $select->where('table.tepass_posts.post_see_type = ?',$price);
                }
            }

            if (isset($filter_mids) and !empty($filter_mids)){
                $select->where('table.relationships.mid IN ?', $filter_mids);
            }
            $select->group('table.tepass_posts.id');

            /** 第二步：重载计数函数 否则报错 */
            $count_sql = clone $select;
            $total = $db->fetchObject($count_sql
                ->select(array('COUNT(DISTINCT table.tepass_posts.post_id)' => 'num'))
                ->from('table.tepass_posts')
                ->cleanAttribute('group'))->num;
            $archive->setTotal($total);
            /** 重载计数函数 end */
        }else {
            /** 第一步：构造自定义 sql 得到初步筛选结果 */
            $select = $db->select("str_value as price",
                "table.contents.cid","title","slug","created","modified","text","authorId","template","table.contents.type","status","password","commentsNum","views"
            )->from('table.contents')->join('table.fields','table.fields.cid = table.contents.cid')
                ->where('table.contents.status = ? and table.fields.name = ?','publish','jifenPay');
            $filter_mids = [];
            if (isset($category_mid) and is_numeric($category_mid)){
                /** 如果是分类 */
                $categorySelect = $db->select()
                    ->from('table.metas')
                    ->where('type = ?', 'category')
                    ->limit(1);
                $categorySelect->where('mid = ?',$category_mid);
                $category = $db->fetchRow($categorySelect);

                if (empty($category)) {
                    throw new Typecho_Widget_Exception(_t('分类不存在'), 404);
                }
                $categoryListWidget = $archive->widget('Widget_Metas_Category_List', 'current=' . $category);
                $category = $categoryListWidget->filter($category);

                $children = $categoryListWidget->getAllChildren($category['mid']);
                $children[] = $category['mid'];
                if (isset($children) and !empty($children)){
                    $filter_mids = array_merge($filter_mids,$children);
                }
            }

            // price -1:all / 0:free /1: login see /2:vip see /3:need pay
            if (isset($price) and is_numeric($price)){
                if ($price >= "0" and  $price < "4"){
                    if ($price == "0"){
                        $select->where('table.fields.str_value = ?',"0");
                    }
                }
            }

            if (isset($tag_mid) and is_numeric($tag_mid)){
                $tagSelect = $db->select()->from('table.metas')
                    ->where('type = ?', 'tag')->limit(1)->where('mid = ?', $tag_mid);
                /** 如果是标签 */
                $tag = $db->fetchRow($tagSelect,
                    array($archive->widget('Widget_Abstract_Metas'), 'filter'));

                if (!$tag) {
                    throw new Typecho_Widget_Exception(_t('标签不存在'), 404);
                }
                if (isset($tag['mid'] ) and !empty($tag['mid'])){
                    $filter_mids[] = $tag['mid'];
                }
            }

            $select->join('table.relationships', 'table.contents.cid = table.relationships.cid');

            // 条件 filter 位置不可移动
            // price -1:all / 0:free /1: login see /2:vip see /3:need pay

//            if (isset($price) and is_numeric($price)){
//                if ($price >= "0" and  $price < "4"){
//                    $select->where('table.tepass_posts.post_see_type = ?',$price);
//                }
//            }

            if (isset($filter_mids) and !empty($filter_mids)){
                $select->where('table.relationships.mid IN ?', $filter_mids);
            }
            $select->group('table.contents.cid');

            /** 第二步：重载计数函数 否则报错 */
            $count_sql = clone $select;
            $total = $db->fetchObject($count_sql
                ->select(array('COUNT(DISTINCT table.contents.cid)' => 'num'))
                ->from('table.contents')
                ->cleanAttribute('group'))->num;

            $archive->setTotal($total);
            /** 重载计数函数 end */
        }

        $allow_order = ['created','modified','commentsNum','views','likes'];
        $default_order = 'created';
        if (in_array($order,$allow_order)){
            $default_order = $order;
        }
        // 修改默认分页数，否则分页对不齐
        $archive->parameter->pageSize = 8;
        $select->order($default_order, Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);
        // pageSize end
        $archive->query($select);
        $archive->setMetaTitle('资源分享');
        /** 设置关键词 */
        $archive->setKeywords("付费专区");

        /** 设置描述 */
        $archive->setDescription("资源分享,typecho 主题,typecho资源");
        /** 设置标题 */
        $archive->setArchiveTitle("资源分享-即刻学术");

        /** 模板位置设置 */
//        $archive->setThemeDir(ABSPLUGINROOT);
        $archive->setThemeFile('/page/resources.php');

    }

    public static function handleHotPostsPage($archive, $archive_select)
    {
        $archive->setArchiveType('hotposts');
        $archive->parameter->pageSize = 10;
        $archive_select->where('table.contents.type = ?', 'post')->group('table.contents.cid');
        /** 仅输出文章 */
        $archive->setCountSql(clone $archive_select);

        $archive_select->order('table.contents.views', Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);

        $archive->query($archive_select);

        /** 设置关键词 */
        $archive->setKeywords("热门文章");

        /** 设置描述 */
        $archive->setDescription("热门文章");
        /** 设置标题 */
        $archive->setArchiveTitle("热门文章");

        $archive->setThemeFile('/page/hotposts.php');

    }
}
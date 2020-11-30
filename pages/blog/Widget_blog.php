<?php

/**
 * blog 页面
 * Class Widget_blog
 */
class Widget_blog extends Widget_Archive
{
    public static function handle($archive, $select)
    {
        $archive->setArchiveType('blog');
        $select->where('table.contents.type = ?', 'post');
        $mids = Helper::options()->blogMid;
        $midsarr = explode("||", $mids);
        foreach ($midsarr as $key => $val){
            $midsarr[$key] = trim($val);
        }
        if ($mids and !empty($midsarr)){
            $select->join('table.relationships', 'table.contents.cid = table.relationships.cid',Typecho_Db::LEFT_JOIN)->where('table.relationships.mid in ?',$midsarr);
        }
        /** 仅输出文章 */
        $archive->setCountSql(clone $select);

        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);
        $archive->query($select);

        /** 设置关键词 */
        $archive->setKeywords("我的博客");

        /** 设置描述 */
        $archive->setDescription("我的博客");
        /** 设置标题 */
        $archive->setArchiveTitle("我的博客");

    }
}
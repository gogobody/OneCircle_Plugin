<?php

/**
 * blog 页面
 * Class Widget_blog
 */
class Widget_blog extends Widget_Archive
{
    public static function handle($archive, $select)
    {
        $archive->setArchiveType('myblog');
        $select->where('table.contents.type = ?', 'post');


        /** 仅输出文章 */
        $archive->setCountSql(clone $select);

        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($archive->_currentPage, $archive->parameter->pageSize);
        $archive->query($select);

        /** 设置关键词 */
        $archive->setKeywords("我的博客");

        /** 设置描述 */
        $archive->setDescription("我的博客");
        /** 设置标题 */
        $archive->setArchiveTitle("我的博客");

    }
}
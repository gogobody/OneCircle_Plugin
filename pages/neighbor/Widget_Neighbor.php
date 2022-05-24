<?php

include_once Helper::options()->themeFile('onecircle','libs/language.php');

/**
 * neighbor
 * Class Widget_Neighbor
 */
class Widget_Neighbor extends Widget_Archive
{
    public static function handle($archive, $select)
    {
        $archive->setArchiveType('neighbor');
        /*
         * 支持的过滤器列表
         *         'int'       =>  'intval',
        'integer'   =>  'intval',
        'search'    =>  array('Typecho_Common', 'filterSearchQuery'),
        'xss'       =>  array('Typecho_Common', 'removeXSS'),
        'url'       =>  array('Typecho_Common', 'safeUrl'),
        'slug'      =>  array('Typecho_Common', 'slugName')
         * */
        $district = $archive->request->filter('url', 'search')->keyword;
        global $language;
        if (empty($district) or $district == $language['defaultAddr']) {
            $archive->setPageRow(array('keyword' => urlencode($district)));
            $district = "";
        }else{
            /** 设置分页 这个属性不设置的话 url 解析不出来*/
            $archive->setPageRow(array('keyword' => urlencode($district)));
        }
        // 按照 district 迷糊匹配

        $db = Typecho_Db::get();

        if ($archive->user->hasLogin()) {
            $select = $db->select()->where('table.contents.status = ? OR
                        (table.contents.status = ? AND table.contents.authorId = ?)', 'publish', 'private', $archive->user->uid);
        } else {
            $select = $db->select()->where('table.contents.status = ?', 'publish');
        }

        $select->from($db->getPrefix().'contents')->where('table.contents.district LIKE ?', '%' . $district . '%')
            ->where('table.contents.created < ?', $archive->options->time);

        /** 仅输出文章 */
        $archive->setCountSql(clone $select);

        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);

        $archive->query($select);


        /** 设置关键词 */
        $archive->setKeywords($district ? $district : $language['defaultAddr']);

        /** 设置描述 */
        $archive->setDescription("neighboor 附近");
        /** 设置标题 */
        $archive->setArchiveTitle("附近");
    }
}

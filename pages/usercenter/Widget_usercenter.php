<?php
/**
 * usercenter 页面
 * Class Widget_usercenter
 */


class Widget_usercenter extends Widget_Archive
{
    public static function handleSetting($archive, $select)
    {
        if (!$archive->user->hasLogin()){
            $archive->response->redirect($archive->options->loginUrl);
        }
        $archive->setArchiveType('setting');
        $select->where('table.contents.type = ?', 'post');

        /** 仅输出文章 */
        $archive->setCountSql(clone $select);

        $select->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($archive->getCurrentPage(), $archive->parameter->pageSize);
        $archive->query($select);
        $archive->setThemeFile('usercenter/setting.php');

        /** 设置关键词 */
        $archive->setKeywords("个人中心");

        /** 设置描述 */
        $archive->setDescription("个人中心");
        /** 设置标题 */
        $archive->setArchiveTitle("个人中心");

    }
    public static function handleCredits($archive, $select)
    {
        if (!$archive->user->hasLogin()){
            $archive->response->redirect($archive->options->loginUrl);
        }
        $archive->setArchiveType('credits');
        $archive->setArchiveType($archive->parameter->type);
        $archive->setMetaTitle('账户积分');
        $archive->setThemeFile('usercenter/credits.php');
        $archive->credits = Typecho_Widget::widget('Widget_Credits_List');
        /** 设置关键词 */
        $archive->setKeywords("账户积分");

        /** 设置描述 */
        $archive->setDescription("账户积分");
        /** 设置标题 */
        $archive->setArchiveTitle("账户积分");
    }
}
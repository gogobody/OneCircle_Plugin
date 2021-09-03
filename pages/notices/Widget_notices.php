<?php

/**
 * 系统消息 // 不是私聊
 */
class Widget_notices extends Widget_Archive
{
    public static function noticeHandle($archive, $select){
        if (!$archive->user->hasLogin()){
            $archive->response->redirect($archive->options->loginUrl);
        }

        $archive->setArchiveType('notices');
        $archive->setArchiveType($archive->parameter->type);
        $archive->setMetaTitle('消息提醒');
        $archive->setThemeFile('usercenter/notices.php');
        $archive->notices = Typecho_Widget::widget('Widget_Notice_Archive');
        /** 设置关键词 */
        $archive->setKeywords("消息提醒");

        /** 设置描述 */
        $archive->setDescription("消息提醒");
        /** 设置标题 */
        $archive->setArchiveTitle("消息提醒");
    }
}
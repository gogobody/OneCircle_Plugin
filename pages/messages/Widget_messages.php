<?php

/**
 * 私聊消息
 */
class Widget_messages extends Widget_Archive
{
    public static function messageHandle($archive, $select){
        if (!$archive->user->hasLogin()){
            $archive->response->redirect($archive->options->loginUrl);
        }

        $archive->setArchiveType('messages');
        $archive->setArchiveType($archive->parameter->type);
        $archive->setMetaTitle('私信');
        $archive->setThemeFile('usercenter/messages.php');
        // 这种是获取所有的消息
        $archive->messages = Typecho_Widget::widget('Widget_Message_Archive',['unique'=>true]);
        /** 设置关键词 */
        $archive->setKeywords("私信");

        /** 设置描述 */
        $archive->setDescription("私信");
        /** 设置标题 */
        $archive->setArchiveTitle("私信");
    }
}
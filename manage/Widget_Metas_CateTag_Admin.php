<?php
class Widget_Metas_CateTag_Admin extends Widget_Metas_Tag_Cloud
{
    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $select = $this->select()->where('type = ?', 'catetag')->order('mid', Typecho_Db::SORT_DESC);
        $this->db->fetchAll($select, array($this, 'push'));
    }

    /**
     * 获取菜单标题
     *
     * @access public
     * @return string
     */
    public function getMenuTitle()
    {
        if (isset($this->request->mid)) {
            $tag = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'catetag', $this->request->mid));

            if (!empty($tag)) {
                return _t('编辑标签 %s', $tag['name']);
            }
        } else {
            return;
        }

        throw new Typecho_Widget_Exception(_t('标签不存在'), 404);
    }
}
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// +----------------------------------------------------------------------
// |  根据用户ID、用户名或者用户邮箱查找用户


class Widget_Users_Query extends Widget_Abstract_Users
{
    /**
     * 执行函数,初始化数据
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if($this->parameter->uid){
            $this->db->fetchRow($this->select()
                ->where('table.users.uid = ?', $this->parameter->uid)->limit(1), array($this, 'push'));
        }else if ($this->parameter->name) {
            $this->db->fetchRow($this->select()
                ->where('table.users.name = ?', $this->parameter->name)->limit(1), array($this, 'push'));
        }else if ($this->parameter->mail) {
            $this->db->fetchRow($this->select()
                ->where('table.users.mail = ?', $this->parameter->mail)->limit(1), array($this, 'push'));
        }
    }
}

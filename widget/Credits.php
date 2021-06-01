<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// +----------------------------------------------------------------------
// | 用户积分
// +----------------------------------------------------------------------
require_once 'Abstract/Credits.php';

class Widget_Users_Credits extends Widget_Abstract_Credits
{
    /**
     * @var int[]
     */
    private $levelMap;
    /**
     * @var int
     */
    private $maxLevel;

    /**
     * @return $_currentPage
     */

    public function __construct($request, $response, $params = NULL){
        parent::__construct($request, $response, $params);
        $this->levelMap = array(
            4000 => 1, // lv1
            7000 => 2,
            11000 => 3,
            16000 => 4,
            22000 => 5,
            29000 => 6,
            37000 => 7,
            46000 => 8,
            56000 => 9
        );
        $this->maxLevel = 9;
    }

    /**
     * 判断是否超出次数
     * @param unknown $uid
     * @param unknown $type
     * @return boolean
     */
    protected function isSetMaxNum($uid,$type){
        if($this->_creditType[$type]['max']==0) return true;
        $select = $this->db->select()->where('type = ? AND uid = ?', $type, $uid);
        if($this->_creditType[$type]['cycle']==1){
            $date = strtotime(date('Y-m-d'));
            $select->where('created > ?',$date);
        }

        $num = $this->size($select);
        return $num < $this->_creditType[$type]['max'] ? true :false;
    }

    public function setUserCredits($uid,$type,$srcId){
        var_dump('aaa');
        $creditsName = 'credits'.ucfirst($type);
        var_dump($creditsName);

        if(!$this->options->$creditsName){
            return;
        }
        $srcId = is_null($srcId) ? 0 : $srcId;
        if($this->isSetMaxNum($uid, $type)){
            $data = array(
                'uid'=>$uid,
                'srcId'=>$srcId,
                'type'=>$type,
                'amount'=>$this->options->$creditsName,
                'created'=>$this->options->gmtTime
            );
            $this->saveCredits($data);
        }

        if($type=='invite'){
            $user = $this->widget('Widget_Users_Query@uid_'.$uid,'uid='.$uid);
            if(empty($user->extend) || empty($user->extend['inviter'])){
                return;
            }

            $inviter = $this->widget('Widget_Users_Query@name_'.$user->extend['inviter'],'name='.$user->extend['inviter']);

            if(!$this->isSetMaxNum($inviter->uid, 'inviter')){
                return;
            }
            $data = array(
                'uid'=>$inviter->uid,
                'srcId'=>$uid,
                'type'=>'inviter',
                'amount'=>$this->options->$creditsName,
                'created'=>$this->options->gmtTime
            );
            $this->saveCredits($data);
        }
    }

    protected function saveCredits($data = array()){
        $user = $this->getUserCredits($data['uid']);
        $data['balance'] = $user['credits']+$data['amount'];
        if ($data['balance'] < 0 ) $data['balance'] = 0;
        // 修改用户等级
        $newlevel = 1;
        foreach ($this->levelMap as $credits=> $level){
            if (intval($user['credits']) > intval($credits)){
                $newlevel = $level;
            }
        }
        if ($newlevel > $this->maxLevel) $newlevel = $this->maxLevel;

        $this->insert($data);
        $this->db->query($this->db->update('table.users')->rows(array('credits'=>$data['balance'],'level' => $newlevel))->where('uid = ?',$data['uid']));
    }

    protected function getUserCredits($uid){
        $user = $this->db->fetchRow($this->db->select('table.users.uid,table.users.credits')
            ->from('table.users')
            ->where('uid = ?', $uid)
            ->limit(1));
        return $user;
    }


}

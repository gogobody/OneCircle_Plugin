<?php


function PluginUrl($str_ = null,$ret = false){
    if ($ret){
        if (!empty($str_)){
            return Typecho_Common::url(__TYPECHO_PLUGIN_DIR__ . '/OneCircle/'.$str_ , Helper::options()->siteUrl);
        }else{
            return Typecho_Common::url(__TYPECHO_PLUGIN_DIR__ . '/OneCircle' , Helper::options()->siteUrl);
        }
    }else{
        if (!empty($str_)){
            echo Typecho_Common::url(__TYPECHO_PLUGIN_DIR__ . '/OneCircle/'.$str_ , Helper::options()->siteUrl);
        }else {
            echo Typecho_Common::url(__TYPECHO_PLUGIN_DIR__ . '/OneCircle' , Helper::options()->siteUrl);
        }
    }

}

function get_plugins_info(){
    $plugin_name = 'OneCircle'; //改成你的插件名
    Typecho_Widget::widget('Widget_Plugins_List@activated', 'activated=1')->to($activatedPlugins);
    if ($activatedPlugins->have() || !empty($activatedPlugins->activatedPlugins)){
        while ($activatedPlugins->next()){
            if ($activatedPlugins->title == $plugin_name){
                return $activatedPlugins->version;
            }
        }
    }
    return false;
}

class JKUtils
{
    /**
     * 判断插件是否可用（存在且已激活）
     * @param $name
     * @return bool
     */
    public static function hasPlugin($name)
    {
        $plugins = Typecho_Plugin::export();
        $plugins = $plugins['activated'];
        return is_array($plugins) && array_key_exists($name, $plugins);
    }
    public static function genPermalink($type,$value){
        //生成静态链接 example
//        $value = [
//            "type" => 'tag',
//            "slug" => 'shihi'
//        ];
//        $type = $value['type'];
//        $tmpSlug = $value['slug'];
        $value['slug'] = urlencode($value['slug']);

        return Typecho_Router::url($type, $value, Helper::options()->index);
    }
}

//生成静态链接 example
function genPermalink($type,$value){
    //生成静态链接 example
//        $value = [
//            "type" => 'tag',
//            "slug" => 'shihi'
//        ];
//        $type = $value['type'];
//        $tmpSlug = $value['slug'];
    $value['slug'] = urlencode($value['slug']);

    return Typecho_Router::url($type, $value, Helper::options()->index);
}


/* 获取懒加载图片 */
function GetLazyLoad_()
{
    $poption = Helper::options()->plugin('OneCircle');
    if ($poption->JLazyLoad) {
        return $poption->JLazyLoad;
    } else {
        return "https://cdn.jsdelivr.net/gh/gogobody/Modify_Joe_Theme@4.7.0/assets/img/lazyload-min.gif";
//        return $options->themeUrl."/assets/img/loading.svg";
    }
}



/**
 * 时间友好化
 *
 * @access public
 * @param mixed
 * @return
 */
function formatTime_($time){
    $text = '';
    $time = intval($time);
    $ctime = time();
    $t = $ctime - $time; //时间差
    if ($t < 0) {
        return date('Y-m-d', $time);
    }
    $y = date('Y', $ctime) - date('Y', $time);//是否跨年
    switch ($t) {
        case $t == 0:
            $text = '刚刚';
            break;
        case $t < 60://一分钟内
            $text = $t . '秒前';
            break;
        case $t < 3600://一小时内
            $text = floor($t / 60) . '分钟前';
            break;
        case $t < 86400://一天内
            $text = floor($t / 3600) . '小时前'; // 一天内
            break;
        case $t < 2592000://30天内
            if($time > strtotime(date('Ymd',strtotime("-1 day")))) {
                $text = '昨天';
            } elseif($time > strtotime(date('Ymd',strtotime("-2 days")))) {
                $text = '前天';
            } else {
                $text = floor($t / 86400) . '天前';
            }
            break;
        case $t < 31536000 && $y == 0://一年内 不跨年
            $m = date('m', $ctime) - date('m', $time) -1;
            if($m == 0) {
                $text = floor($t / 86400) . '天前';
            } else {
                $text = $m . '个月前';
            }
            break;
        case $t < 31536000 && $y > 0://一年内 跨年
            $text = (11 - date('m', $time) + date('m', $ctime)) . '个月前';
            break;
        default:
            $text = (date('Y', $ctime) - date('Y', $time)) . '年前';
            break;
    }
    return $text;
}

/* 通过邮箱生成头像地址 */
function _JKhelperGetAvatarByMail($mail)
{
    $gravatarsUrl = 'https://gravatar.helingqi.com/wavatar/';
    $mailLower = strtolower($mail);
    $md5MailLower = md5($mailLower);
    $qqMail = str_replace('@qq.com', '', $mailLower);
    if (strstr($mailLower, "qq.com") && is_numeric($qqMail) && strlen($qqMail) < 11 && strlen($qqMail) > 4) {
        return 'https://thirdqq.qlogo.cn/g?b=qq&nk=' . $qqMail . '&s=100';
    } else {
        return $gravatarsUrl . $md5MailLower . '?d=mm';
    }
};


/**
 * 积分辅助函数
 * @param $credits
 * @return array|int[]
 */
function creditsConvert($credits){
    if ($credits <= 0) return array(0,0,0);
    $gold = intval($credits/10000);
    $res = $credits%10000;
    $sliver = intval($res/100);
    $copper = intval($res%100);
    return array($gold,$sliver,$copper);
}

function str_replace_first($from, $to, $content)
{
    $from = '/'.preg_quote($from, '/').'/';

    return preg_replace($from, $to, $content, 1);
}



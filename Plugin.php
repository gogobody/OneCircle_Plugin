<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * OneCircle：
 * 后台接口，增加用户个人签名设置<br>
 * 加入了权限设置：修改注册时默认用户组，贡献者可直接发布文章无需审核,前台注册支持用户输入密码<br>
 * @package OneCircle
 * @author gogobody
 * @version 4.6
 * @link https://one.ijkxs.com
 */

require(__DIR__ . DIRECTORY_SEPARATOR . "Action.php");
require(__DIR__ . DIRECTORY_SEPARATOR . 'core/utils/JKUtils.php');
require(__DIR__ . DIRECTORY_SEPARATOR . 'core/handler/handler.php');

// require abstrace
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Abstract/Credits.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Abstract/Notice.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Abstract/Message.php");

// require handler
require_once 'pages/metas/Metasmanage.php';
require_once 'pages/neighbor/Widget_Neighbor.php';
require_once 'pages/blog/Widget_blog.php';
require_once 'pages/usercenter/Widget_usercenter.php';
require_once 'pages/notices/Widget_notices.php';
require_once 'pages/messages/Widget_messages.php';

// require widget
require_once(__DIR__ . DIRECTORY_SEPARATOR . "manage/Widget_CateTag_Edit.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/upload.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Credits.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Common.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Users/Query.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Credits/List.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Notices/List.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Messages/List.php");



class OneCircle_Plugin extends Widget_Archive implements Typecho_Plugin_Interface
{
    // 默认加密首尾标签对 // 不要修改
    protected static $pluginNodeStart = '<!--jkhelper start-->';
    protected static $pluginNodeEnd = '<!--jkhelper end-->';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // plugin handle

        // 自定义页面
//        $author = array (
//            'url' => '/author/[uid:digital]/',
//            'widget' => 'Widget_Archive',
//            'action' => 'render',
//        );
//        $this->_defaultRegx = array(
//            'string' => '(.%s)',
//            'char'   => '([^/]%s)',
//            'digital'=> '([0-9]%s)',
//            'alpha'  => '([_0-9a-zA-Z-]%s)',
//            'alphaslash'  => '([_0-9a-zA-Z-/]%s)',
//            'split'  => '((?:[^/]+/)%s[^/]+)',
//        );

//        /**
//         * 支持的过滤器列表
//         *
//         * @access private
//         * @var string
//         */
//        private static $_supportFilters = array(
//        'int'       =>  'intval',
//        'integer'   =>  'intval',
//        'search'    =>  array('Typecho_Common', 'filterSearchQuery'),
//        'xss'       =>  array('Typecho_Common', 'removeXSS'),
//        'url'       =>  array('Typecho_Common', 'safeUrl'),
//        'slug'      =>  array('Typecho_Common', 'slugName')
//    );
        Helper::addRoute('metas', '/metas/', 'Widget_Archive@meta', 'render');
        /** 这里要注意区分不同的 archive 实例 */
        Helper::addRoute('neighbor_page', '/neighbor/[keyword]/[page:digital]/', 'Widget_Archive@neighbor_page', 'render');
        Helper::addRoute('neighbor', '/neighbor/[keyword]/', 'Widget_Archive@neighbor', 'render');
        Helper::addRoute('myblog', '/myblog/', 'Widget_Archive@myblog', 'render');
        Helper::addRoute('myblog_page', '/myblog/[page:digital]/', 'Widget_Archive@myblog_page', 'render');
        Helper::addRoute('setting', '/usercenter/setting', 'Widget_Archive@usercenter_settiing', 'render');
        Helper::addRoute('credits', '/usercenter/credits', 'Widget_Archive@usercenter_credits', 'render');
        Helper::addRoute('notice', '/usercenter/notice', 'Widget_Archive@notice', 'render');
        Helper::addRoute('messages', '/usercenter/messages', 'Widget_Archive@messages', 'render');
        // 路由注册

        // 资源专区
        Helper::addRoute('resources', '/resources', 'Widget_Archive@resources', 'render');
        Helper::addRoute('resources_page', '/resources/[page:digital]/', 'Widget_Archive@resources_page', 'render');
        // 热门文章
        Helper::addRoute('hotposts', '/hot/posts', 'Widget_Archive@hotposts', 'render');
        Helper::addRoute('hotposts_page', '/hot/posts/[page:digital]/', 'Widget_Archive@hotposts_page', 'render');

        // 页面注册
        Typecho_Plugin::factory('Widget_Archive')->handleInit_1000 = array('OneCircle_Plugin','handleInit');
        Typecho_Plugin::factory('Widget_Archive')->handle_1000 = array('OneCircle_Plugin','handle');
        // 修改注册用户上传权限
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle_1000 = array('OneCircle_Plugin','uploadHandle');
        // 打赏模块
        Typecho_Plugin::factory('OneCircle.Donate')->Donate = array('OneCircle_Plugin', 'Donate');

        // 注册用户权限管理
        Typecho_Plugin::factory('Widget_Register')->register_1000 = array('OneCircle_Plugin', 'zhuce');
        Typecho_Plugin::factory('Widget_Register')->finishRegister_1000 = array('OneCircle_Plugin', 'zhucewan');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write_1000 = array('OneCircle_Plugin', 'fabu');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish_1000 = array('OneCircle_Plugin', 'fabuwan');
        Typecho_Plugin::factory('admin/footer.php')->end_1000 = array('OneCircle_Plugin', 'footerjs');
        // 积分
        Typecho_Plugin::factory('Widget_Login')->loginSucceed_1000 = array('OneCircle_Plugin', 'loginSucceed');
        Typecho_Plugin::factory('Widget_Feedback')->finishComment_1000 = array('OneCircle_Plugin', 'finishComment');

        // 添加 关注圈子
        OneCircle_Plugin::userCircleFollowInstall();

        // 添加个人简介字段
        OneCircle_Plugin::userSignsqlInstall();
        OneCircle_Plugin::userTagsqlInstall();
        // 添加分类标签字段
        OneCircle_Plugin::metasTypesqlInstall();
        // 添加 用户头像
        OneCircle_Plugin::userAvatarsqlInstall();
        OneCircle_Plugin::userBackImgsqlInstall();
        // 添加 用户性别
        OneCircle_Plugin::userSexsqlInstall();
        // 添加文章额外字段
        OneCircle_Plugin::contentsSqlInstall();
        OneCircle_Plugin::typecho_TableInstall();
        OneCircle_Plugin::userExtendsqlInstall();
        // 2021/9/1 添加消息
        //

        // 积分阅读
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx_1001 = array(__CLASS__, 'contentEx');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx_1001 = array(__CLASS__, 'excerptEx');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array(__CLASS__, 'writeRender');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array(__CLASS__, 'writeRender');
        // register apis
        Helper::addRoute('jsonp_', '/jkhelper/[type]', 'OneCircle_Action');
        // action method url : /action/oneapi
//        Helper::addAction('oneapi', 'OneCircle_Action');
        // route method url : /oneaction
        // /action/whosurdaddy
        Helper::addRoute("one_action", "/oneaction", "OneCircle_Action", 'route');


        Helper::addPanel(3, 'OneCircle/manage/manage-cat-tags.php', '管理圈子分类', '圈子分类', 'administrator'); //editor //contributor

    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate()
    {
        Helper::removeRoute('one_action');
        Helper::removeRoute('metas');
        Helper::removeRoute('neighbor');
        Helper::removeRoute('neighbor_page');
        Helper::removeRoute('myblog');
        Helper::removeRoute('myblog_page');
        Helper::removeRoute('setting');
        Helper::removeRoute('credits');
        Helper::removeRoute('jsonp_');

        Helper::removePanel(3, 'OneCircle/manage/manage-cat-tags.php');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $options = Helper::options();
        $pluginV = get_plugins_info();
        ?>
        <div class="j-setting-contain">
        <link href="<?php echo $options->themeUrl('/assets/admin/css/one.setting.min.css','onecircle') ?>" rel="stylesheet" type="text/css" />
        <div>
            <div class="j-aside">
                <div class="logo">ONE <?php echo $pluginV ?><br><small style="font-size: 10px"></small><br>
                <a href="/admin/options-theme.php" target="_self" rel="noopener noreferrer">点我去主题设置</a></div>
                <ul class="j-setting-tab">
                    <li data-current="j-setting-global">公共设置</li>
                    <li data-current="j-setting-qrcode">二维码设置</li>
                    <li data-current="j-setting-resource">资源设置</li>
                </ul>
                <?php require_once('Backups.php'); ?>
            </div>
        </div>
        <span id="j-version" style="display: none;"><?php echo $pluginV; ?></span>
        <div class="j-setting-notice"><iframe src="https://www.yuque.com/docs/share/05f40cac-980f-4e53-8b92-ed9728b8dc50?# 《OneCircle 主题说明》" frameborder="no" scrolling="yes" height="100%" width="100%"></iframe></div>
        <script src="<?php echo $options->themeUrl('/assets/admin/js/one.setting.min.js','onecircle')?>"></script>
    <?php
        /** 分类名称 */
//        $name = new Typecho_Widget_Helper_Form_Element_Text('word', NULL, 'Hello World', '说点什么');
//        $form->addInput($name);
        $yonghuzu = new Typecho_Widget_Helper_Form_Element_Radio('yonghuzu', array(
            'visitor' => '访问者',
            'subscriber' => '关注者',
            'contributor' => '贡献者',
            'editor' => '编辑',
            'administrator' => '管理员'
        ), 'contributor', '注册用户默认用户组设置', '<p class="description">不同的用户组拥有不同的权限，具体的权限分配表请<a href="http://docs.typecho.org/develop/acl" target="_blank" rel="noopener noreferrer">参考这里</a>.</p>');
        $yonghuzu->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($yonghuzu);

        $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan',
            array('contributor-nb' => '勾选该选项让【贡献者】直接发布文章无需审核',
                'register-nb' => '勾选该选项后台注册功能将可以直接设置注册密码',
            ),
            array('contributor-nb','register-nb'), '拓展设置', '');
        $tuozhan->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($tuozhan->multiMode());

        //
        $db = Typecho_Db::get();
        $select = $db->select('mid')->from('table.metas');
        $row = $db->fetchRow($select);
        if (!empty($row)) $umid = $row['mid'];
        else $umid = 1;
        $registeruserMid = new Typecho_Widget_Helper_Form_Element_Text('registeruserMid', NULL, $umid, '用户注册后默认关注哪个分类（int）');
        $registeruserMid->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($registeruserMid);

        $focususerMid = new Typecho_Widget_Helper_Form_Element_Text('focususerMid', NULL, 1, '发布关注消息到哪个分类（int）');
        $focususerMid->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($focususerMid);

        $amapJsKey= new Typecho_Widget_Helper_Form_Element_Text('amapJsKey', NULL, '', '高德地图 Web端(JS API key');
        $amapJsKey->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($amapJsKey);

        $amapWebKey= new Typecho_Widget_Helper_Form_Element_Text('amapWebKey', NULL, '', '高德地图 Web服务 key');
        $amapWebKey->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($amapWebKey);

        $allowNoneAdminUpload = new Typecho_Widget_Helper_Form_Element_Radio('allowNoneAdminUpload',array(
                1 => '允许',
                0 => '不允许'
        ),0,'是否允许非管理员后台上传文件','此设置仅针对于后台文章编辑有效');
        $allowNoneAdminUpload->setAttribute('class', 'j-setting-content j-setting-global');
        $form->addInput($allowNoneAdminUpload);

        $options = Helper::options();
        $alipay = $options->themeUrl('assets/img/donate/alipay.jpg','onecircle');
        $wxpay = $options->themeUrl('assets/img/donate/wxpay.jpg','onecircle');

        //支付宝二维码
        $AlipayPic = new Typecho_Widget_Helper_Form_Element_Text('AlipayPic', NULL, $alipay, '支付宝二维码', '打赏中使用的支付宝二维码,建议尺寸小于250×250,且为正方形');
        $AlipayPic->setAttribute('class', 'j-setting-content j-setting-qrcode');
        $form->addInput($AlipayPic);
        //微信二维码
        $WechatPic = new Typecho_Widget_Helper_Form_Element_Text('WechatPic', NULL, $wxpay, '微信二维码', '打赏中使用的微信二维码,建议尺寸小于250×250,且为正方形');
        $WechatPic->setAttribute('class', 'j-setting-content j-setting-qrcode');
        $form->addInput($WechatPic);

        // 资源设置
        $enableResource = new Typecho_Widget_Helper_Form_Element_Radio('enableResource',array(
                1 => '开启',
                0 => '关闭'
        ),1,'开启资源页','是否开启资源页');
        $enableResource->setAttribute('class', 'j-setting-content j-setting-resource');
        $form->addInput($enableResource);

        $JResourceCatags = new Typecho_Widget_Helper_Form_Element_Textarea(
            'JResourceCatags',
            null,
            '','资源页面展示的分类和tag',
            '说明：这里展示分类和 tag 的对应关系，<br>格式：<br>分类1的mid||tag1的mid,tag2的mid<br>比如：1||11，13每行一个'
        );
        $JResourceCatags->setAttribute('class', 'j-setting-content j-setting-resource');
        $form->addInput($JResourceCatags);

        $JPageStatus = new Typecho_Widget_Helper_Form_Element_Select(
            'JPageStatus',
            array('default' => '按钮切换形式（默认）', 'ajax' => '点击加载形式'),
            'default',
            '选择首页的分页形式',
            '介绍：选择一款您所喜欢的分页形式'
        );
        $JPageStatus->setAttribute('class', 'j-setting-content j-setting-resource');
        $form->addInput($JPageStatus->multiMode());
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 添加个人简介字段
        $db = Typecho_Db::get();

        $user = Typecho_Widget::widget('Widget_User');
        $user->execute();
        $res = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', $user->uid));
//        try{
//            $res = $db->fetchRow($db->select('table.users.userSign')->from('table.users')->where('uid = ?', $user->uid));
//            $tags = $db->fetchRow($db->select('table.users.userTag')->from('table.users')->where('uid = ?', $user->uid));
//
//        }catch (Exception $e){
//            $res['userSign'] = '太懒了还没有个性签名';
//            $tags['userTag'] = '学生,重庆';
//        }
        $useravatar = new Typecho_Widget_Helper_Form_Element_Text('userAvatar', NULL, null, '个人头像',
        '<div id="avatar-uploader" style="width: 100%;text-align: right;flex-direction: row-reverse;display: none"><div class="row XCHRv" style="width: 100%;display: block"><div id="zz-img-show"></div><div class="zz-add-img "><input id="zz-img-file" type="file" accept="image/*" multiple="multiple"><button id="zz-img-add" type="button"><span class="chevereto-pup-button-icon"><svg class="chevereto-pup-button-icon" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M76.7 87.5c12.8 0 23.3-13.3 23.3-29.4 0-13.6-5.2-25.7-15.4-27.5 0 0-3.5-0.7-5.6 1.7 0 0 0.6 9.4-2.9 12.6 0 0 8.7-32.4-23.7-32.4 -29.3 0-22.5 34.5-22.5 34.5 -5-6.4-0.6-19.6-0.6-19.6 -2.5-2.6-6.1-2.5-6.1-2.5C10.9 25 0 39.1 0 54.6c0 15.5 9.3 32.7 29.3 32.7 2 0 6.4 0 11.7 0V68.5h-13l22-22 22 22H59v18.8C68.6 87.4 76.7 87.5 76.7 87.5z" style="fill: currentcolor;"></path></svg></span><span class="chevereto-pup-button-text">上传</span></button></div></div></div>');
        $useravatar->setAttribute("id","personal-userAvatar");
        $form->addInput($useravatar);

        $userbackimg = new Typecho_Widget_Helper_Form_Element_Text('userBackImg', NULL, null, '背景图片');
        $form->addInput($userbackimg);

        $sign = new Typecho_Widget_Helper_Form_Element_Text('userSign', NULL, null, '个性签名');
        $form->addInput($sign);

        $tag = new Typecho_Widget_Helper_Form_Element_Text('userTag', NULL, null, '设置个人TAG,用英文逗号分隔');
        $form->addInput($tag);

        $sex = new Typecho_Widget_Helper_Form_Element_Radio('userSex',array(
                1 => '男',
                0 => "女"
        ),1,"选择性别","");
        $form->addInput($sex);

        $lifeStatus = new Typecho_Widget_Helper_Form_Element_Radio('userLifeStatus',array(
            '' => '保密',
            '今日单身' => '今日单身',
            '等TA出现' => '等TA出现',
            '自由可撩' => '自由可撩',
            '心里有人' => '心里有人',
            '恋爱中' => '恋爱中',
            '一言难尽' => '一言难尽',
        ),"","情感状态");
        $form->addInput($lifeStatus);
        //
        $useravatar->value($res['userAvatar']);
        $userbackimg->value($res["userBackImg"]);
        $sign->value($res['userSign']);
        $tag->value($res['userTag']);
        $sex->value($res['userSex']);
        $lifeStatus->value($res['userLifeStatus']);

    }
    public static function personalConfigHandle($settings,$isSetup){

        $db = Typecho_Db::get();
        if($isSetup)
        {
            Typecho_Widget::widget('Widget_Abstract_Options')->insert(array(
                'name'  =>  '_plugin:OneCircle',
                'value' =>  serialize($settings),
                'user'  =>  0
            ));
        }

        $user = Typecho_Widget::widget('Widget_User');
        $user->execute();

        $db->query($db->sql()->where('uid = ?', $user->uid)->update('table.users')->rows(array(
                'userSign'=>Typecho_Common::removeXSS($settings['userSign']),
                'userTag'=>Typecho_Common::removeXSS($settings['userTag']),
                'userAvatar'=>Typecho_Common::removeXSS($settings['userAvatar']),
                'userBackImg'=>Typecho_Common::removeXSS($settings['userBackImg']),
                'userSex'=>Typecho_Common::removeXSS($settings['userSex']),
                'userLifeStatus'=>Typecho_Common::removeXSS($settings['userLifeStatus'])

        )));

//        try{
//
//        }catch (Exception $e){
//
//        }
        return true;
    }

    public static function zhuce($v)
    {
        /*获取插件设置*/
        $yonghuzu = Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->yonghuzu;
        $hasher = new PasswordHash(8, true);
        /*判断注册表单是否有密码*/
        if (isset(Typecho_Widget::widget('Widget_Register')->request->password)) {
            /*将密码设定为用户输入的密码*/
            $generatedPassword = Typecho_Widget::widget('Widget_Register')->request->password;
        } else {
            /*用户没输入密码，随机密码*/
            $generatedPassword = Typecho_Common::randString(7);
        }
        /*将密码设置为常量，方便下个函数adu()直接获取*/
        define('passd', $generatedPassword);
        /*将密码加密*/
        $wPassword = $hasher->HashPassword($generatedPassword);
        /*设置用户密码*/
        $v['password'] = $wPassword;
        /*将注册用户默认用户组改为插件设置的用户组*/
        $v['group'] = $yonghuzu;
        /*返回注册参数*/
        return $v;
    }

    public static function zhucewan($obj)
    {
        /*获取密码*/
        $wPassword = passd;
        /*登录账号*/
        $obj->user->login($obj->request->name, $wPassword);
        /*删除cookie*/
        Typecho_Cookie::delete('__typecho_first_run');
        Typecho_Cookie::delete('__typecho_remember_name');
        Typecho_Cookie::delete('__typecho_remember_mail');
        /*发出提示*/
        $obj->widget('Widget_Notice')->set('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $obj->screenName, $wPassword, 'success');
        // add default follow circle
        OneCircle_Plugin::addDefaultTag($obj->user->uid);
        //注册积分
        Widget_Common::credits('register');
        /*跳转地址(后台)*/
        if (NULL != $obj->request->referer) {
            $obj->response->redirect($obj->request->referer);
        } else if (NULL != $obj->request->tz) {
            if (Helper::options()->rewrite == 0) {
                $authorurl = Helper::options()->rootUrl . '/index.php/author/';
            } else {
                $authorurl = Helper::options()->rootUrl . '/author/';
            }
            $obj->response->redirect($authorurl . $obj->user->uid);
        } else {
            $obj->response->redirect($obj->options->adminUrl);
        }
    }


    public static function fabu($con, $obj)
    {
        /*插件用户设置是否勾选*/
        if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->tuozhan) && in_array('contributor-nb', Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->tuozhan)) {
            /*获取插件设置的分类id*/
//$tcat = Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->tcat;
            /*求插件设置的分类id数据与用户勾选的分类数据交集*/
//$result=array_intersect($tcat,$con['category']);   && count($result)==0
            /*如果用户是贡献者临时给予编辑权限，并且非特例分类*/
            if (($obj->have() && $obj->author->group == 'contributor') || $obj->user->group == 'contributor') {
                $obj->user->group = 'editor';
            }
        }
        // 给文章发布添加高德地图信息
        return $con;
    }


    public static function fabuwan($con, $obj)
    {
        // 给文章发布添加高德地图信息
        $contents = $obj->request->from('name','district','address');
        $db = Typecho_Db::get();
        $db->query($db->sql()->where('cid = ?', $obj->cid)->update('table.contents')->rows($contents));
        // 发步完文章触发积分机制
        Widget_Common::credits('publish',null,$obj->cid);
        /** 跳转验证后地址 */
        if ($obj->request->referer == 'return') {
            exit;
        } elseif (NULL != $obj->request->referer) {
            /** 发送ping */
            $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($obj->request->trackback)));
            $obj->widget('Widget_Service')->sendPing($obj->cid, $trackback);
            /** 设置提示信息 */
            $obj->widget('Widget_Notice')->set('post' == $obj->type ?
                _t('文章 "<a href="%s">%s</a>" 已经发布', $obj->permalink, $obj->title):
                _t('文章 "%s" 等待审核', $obj->title, 'success'));
            /** 设置高亮 */
            $obj->widget('Widget_Notice')->highlight($obj->theId);
            /** 获取页面偏移 */
            $pageQuery = $obj->getPageOffsetQuery($obj->cid);
            /** 页面跳转 */
            $obj->response->redirect($obj->request->referer);
            exit;
        } else {
            return $con;
        }

    }

    public static function footerjs()
    {
        if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->tuozhan) && in_array('register-nb', Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->tuozhan)) {
            ?>
            <script>
                var OneCirclehtml = '<p><label for="password" class="sr-only">密码</label><input type="password"  id="password" name="password" placeholder="输入密码" class="text-l w-100" autocomplete="off" required></p><p><label for="confirm" class="sr-only">确认密码</label><input type="password"  id="confirm" name="confirm" placeholder="再次输入密码" class="text-l w-100" autocomplete="off" required></p>';
                $("#mail").parent().after(OneCirclehtml);
            </script>
            <?php
        }
    }

    // SQL创建
    // 添加个人简介字段
    public static function userSignsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.userSign')->from('table.users');
            $db->query($select);
            return '检测到个性签名字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userSign` VARCHAR( 255 )  DEFAULT '' COMMENT '用户个性签名';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userSign` VARCHAR( 10 )  DEFAULT ''");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立个性签名字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '个性签名已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('个性签名插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，个性签名插件启用失败。错误号：'.$code);
        }
    }
    // 添加个人标签字段
    public static function userTagsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.userTag')->from('table.users');
            $db->query($select);
            return '检测到个性签名字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userTag` VARCHAR( 255 )  DEFAULT '学生' COMMENT '用户个性标签';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userTag` VARCHAR( 100 )  DEFAULT '学生'");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立个性签名字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '个性签名已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('个性签名插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，个性签名插件启用失败。错误号：'.$code);
        }
    }
    // 添加用户圈子关注
    public static function userCircleFollowInstall(){
        // create circle follow table
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if($type == "SQLite"){
            $sql ="SELECT count(*) FROM sqlite_master WHERE type='table' AND name='".$prefix."circle_follow';";
            $checkTabel = $db->query($sql);
            $row = $checkTabel->fetchAll();
            if ($row[0]["count(*)"] == '0'){
                $res = $db->query('CREATE TABLE `' . $prefix . 'circle_follow` (
                                  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                                  `uid` bigint(20) NOT NULL DEFAULT 0 ,
                                  `mid` bigint(20) NOT NULL DEFAULT 0 ,
                                  `createtime` int(10) DEFAULT 0 
                                )');
            }
        }else{
            $sql = 'SHOW TABLES LIKE "' . $prefix . 'circle_follow' . '"';
            $checkTabel = $db->query($sql);
            $row = $checkTabel->fetchAll();
            if ('1' == count($row)) {
            } else {
                $db->query('CREATE TABLE `' . $prefix . 'circle_follow` (
                              `id` bigint(20) NOT NULL AUTO_INCREMENT,
                              `uid` bigint(20) NOT NULL DEFAULT 0 COMMENT \'用户ID\',
                              `mid` bigint(20) NOT NULL DEFAULT 0 COMMENT \'关注meta/circle\',
                              `createtime` int(10) DEFAULT 0 COMMENT \'关注时间\',
                              PRIMARY KEY (`id`)
                            )');
            }
        }


    }
    // 添加分类标签
    public static function metasTypesqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.metas.tagid')->from('table.metas');
            $db->query($select);
            return '检测到meta分类字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."metas` ADD `tagid` INTEGER  DEFAULT 0 COMMENT 'metas分类';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."metas` ADD `tagid` INTEGER  DEFAULT 0");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立meta分类字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return 'meta分类已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('meta分类插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，meta分类插件启用失败。错误号：'.$code);
        }
    }
    // 添加用户头像字段
    public static function userAvatarsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.userAvatar')->from('table.users');
            $db->query($select);
            return '检测到用户头像字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userAvatar` VARCHAR( 512 )  DEFAULT '' COMMENT '用户头像';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userAvatar` VARCHAR( 512 )  DEFAULT ''");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立个性签名字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '个性签名已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('用户头像插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，用户头像插件启用失败。错误号：'.$code);
        }
    }
    // 添加用户背景字段
    public static function userBackImgsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.userBackImg')->from('table.users');
            $db->query($select);
            return '检测到用户背景字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userBackImg` VARCHAR( 512 )  DEFAULT '' COMMENT '用户背景';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userBackImg` VARCHAR( 512 )  DEFAULT ''");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立用户背景字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '用户背景已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('用户背景插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，用户背景插件启用失败。错误号：'.$code);
        }
    }
    // 添加性别字段
    public static function userSexsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.userSex','table.users.userLifeStatus')->from('table.users');
            $db->query($select);
            return '检测到用户性别字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userSex` INT( 1 )  DEFAULT 1 COMMENT '用户性别';");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userLifeStatus` VARCHAR( 20 )  DEFAULT '' COMMENT '情感状态';");

                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userSex` INT( 1 )  DEFAULT 1");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `userLifeStatus` VARCHAR( 20 )  DEFAULT ''");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立用户性别字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '用户性别已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('用户性别插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，用户性别插件启用失败。错误号：'.$code);
        }
    }
    // 添加用户额外字段
    public static function userExtendsqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('table.users.location','table.users.credits','table.users.extend','table.users.level')->from('table.users');
            $db->query($select);
            return '检测到用户，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `location` VARCHAR( 120 )  DEFAULT '' COMMENT '用户位置';");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `credits` int(10) unsigned   DEFAULT 0 COMMENT '用户信用';");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `level` int(2) unsigned   DEFAULT 1 COMMENT '用户等级';");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `extend` text COMMENT '用户邀请';");

                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."users` ADD `location` VARCHAR( 120 )  DEFAULT '';");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `credits` int(10)  DEFAULT 0 ;");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `level` int(2)  DEFAULT 1;");
                        $db->query("ALTER TABLE `".$prefix."users` ADD `extend` text ;");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，插件启用失败。错误号：'.$code);
        }
    }
    // 添加 typecho_creditslog
    public static function typecho_TableInstall(){
        // create circle follow table
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        if($type == "SQLite"){
            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."creditslog` (
                                  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                                  `uid` int(10) NOT NULL,
                                  `srcId` int(10) NOT NULL,
                                  `created` int(10) NOT NULL DEFAULT '0',
                                  `type` char(16) NOT NULL DEFAULT 'login',
                                  `amount` int(10) NOT NULL DEFAULT '0',
                                  `balance` int(10) NOT NULL DEFAULT '0',
                                  `remark` varchar(255) NOT NULL DEFAULT ''
                                );");
            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."onemessages` (
                                `id` int(10) unsigned NOT NULL auto_increment,
                                `uid` int(10) unsigned NOT NULL ,
                                `fid` int(10) unsigned NOT NULL ,
                                `type` char(16) NOT NULL DEFAULT 'message' ,
                                `text` TEXT NOT NULL ,
                                `created` int(10) unsigned NOT NULL DEFAULT '0' ,
                                `status` tinyint(1) unsigned NOT NULL DEFAULT '0' ,
                                PRIMARY KEY (`id`),
                                KEY `uid` (`uid`)
                                ) ;");
            // 删除旧的 message 表
            $db->query("DROP TABLE IF EXISTS `" . $prefix ."messages`;");

            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."notices` (
                                `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                                `uid` int(10) NOT NULL ,
                                `type` char(16) NOT NULL DEFAULT 'comment' ,
                                `srcId` int(10) NOT NULL DEFAULT '0' ,
                                `created` int(10) NOT NULL DEFAULT '0' ,
                                `text` varchar(700) NOT NULL DEFAULT '',
                                `status` tinyint(1) NOT NULL DEFAULT '0'
                                );");
            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."favorites` (
                                `fid` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL ,
                                `uid` int(10) NOT NULL,
                                `type` char(16) NOT NULL DEFAULT 'post' ,
                                `srcId` int(10) NOT NULL DEFAULT '0',
                                `created` int(10) NOT NULL DEFAULT '0'
                                );");
        }else{
            $res= $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."creditslog` (
                                  `id` int(10) unsigned NOT NULL auto_increment COMMENT '积分日志表主键',
                                  `uid` int(10) unsigned NOT NULL COMMENT '所属用户',
                                  `srcId` int(10) unsigned NOT NULL COMMENT '触发的资源ID',
                                  `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                                  `type` char(16) NOT NULL DEFAULT 'login' COMMENT '积分类型',
                                  `amount` int(10) NOT NULL DEFAULT '0' COMMENT '本次积分',
                                  `balance` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '余额',
                                  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
                                  PRIMARY KEY (`id`)
                                );");
            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."onemessages` (
                                `id` int(10) unsigned NOT NULL auto_increment COMMENT '消息表主键',
                                `uid` int(10) unsigned NOT NULL COMMENT '谁的消息',
                                `fid` int(10) unsigned NOT NULL COMMENT '谁发来的',
                                `type` char(16) NOT NULL DEFAULT 'message' COMMENT '消息类型',
                                `text` TEXT NOT NULL COMMENT '消息内容',
                                `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '触发时间',
                                `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读',
                                PRIMARY KEY (`id`),
                                KEY `uid` (`uid`)
                                ) ;");
            // 删除旧的 message 表
            $db->query("DROP TABLE IF EXISTS `" . $prefix ."messages`;");

            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."notices` (
                                `id` int(10) unsigned NOT NULL auto_increment COMMENT '提醒表主键',
                                `uid` int(10) unsigned NOT NULL COMMENT '提醒的用户',
                                `type` char(16) NOT NULL DEFAULT 'comment' COMMENT '提醒类型', 
                                `srcId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '触发的资源',
                                `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '触发时间',
                                `text` varchar(700) NOT NULL DEFAULT '' COMMENT '详情',
                                `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读',
                                PRIMARY KEY (`id`),
                                KEY `uid` (`uid`)
                                ) ;");
            $db->query("CREATE TABLE IF NOT EXISTS `" . $prefix ."favorites` (
                                `fid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏主键',
                                `uid` int(10) unsigned NOT NULL COMMENT '所属用户',
                                `type` char(16) NOT NULL DEFAULT 'post' COMMENT '收藏类型',
                                `srcId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '资源ID',
                                `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏时间',
                                PRIMARY KEY (`fid`)
                                );");
        }


    }
    // 添加文章额外字段
    public static function contentsSqlInstall()
    {
        $db = Typecho_Db::get();
        $type = explode('_', $db->getAdapterName());
        $type = array_pop($type);
        $prefix = $db->getPrefix();
        try {
            $select = $db->select('name','district ','address')->from('table.contents'); //name :  "重庆大学A区", district :  "重庆市沙坪坝区",address :  "沙坪坝正街174号",
            $db->query($select);
            return '检测到文章额外字段，插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    if ('Mysql' == $type) {
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `name` VARCHAR( 30 )  DEFAULT '' COMMENT '地点name';");
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `district` VARCHAR( 30 )  DEFAULT '' COMMENT '地点区县';");
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `address` VARCHAR( 40 )  DEFAULT '' COMMENT '街道名称';");
                    } else if ('SQLite' == $type) {
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `name` VARCHAR( 30 )  DEFAULT ''");
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `district` VARCHAR( 30 )  DEFAULT ''");
                        $db->query("ALTER TABLE `".$prefix."contents` ADD `address` VARCHAR( 40 )  DEFAULT ''");
                    } else {
                        throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
                    }
                    return '建立文章额外字段，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if(('Mysql' == $type && 1060 == $code) ) {
                        return '文章额外字段已经存在，插件启用成功';
                    }
                    throw new Typecho_Plugin_Exception('文章额外字段插件启用失败。错误号：'.$code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表检测失败，文章额外字段插件启用失败。错误号：'.$code);
        }
    }
    // add default faned tag , 用户注册的时候添加默认关注
    public static function addDefaultTag($uid){
        $db = Typecho_Db::get();
        $umid = Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->registeruserMid;
        $insert = $db->insert('table.circle_follow')
            ->rows(array('uid' => $uid, 'mid' => $umid));
        $db->query($insert);
    }
    // 添加新的 Page
    public static function handleInit($archive,$select){



//        $fp = fopen('write.txt', 'a+b'); //a+读写方式打开，将文件指针指向文件末尾。b为强制使用二进制模式. 如果文件不存在则尝试创建之。
//        fwrite($fp,print_r($archive->parameter->type."--\r\n",true));
//        fwrite($fp,print_r(Helper::options()->JIndexShowPage."--\r\n",true));
//        fclose($fp); //关闭打开的文件。
    }

    public static function handle($type,$archive,$select){

        if ($type == 'metas'){
            Widget_Metasmanage::handle($archive);
        }elseif ($type == 'neighbor' or $type == 'neighbor_page'){
            Widget_Neighbor::handle($archive,$select);
        }elseif ($type == 'myblog' or $type == 'myblog_page'){
            Widget_blog::handle($archive,$select);
        }elseif ($type == 'setting'){
            Widget_usercenter::handleSetting($archive,$select);
        }elseif ($type == 'credits'){
            Widget_usercenter::handleCredits($archive,$select);
        }elseif ($type == 'notice'){
            Widget_notices::noticeHandle($archive, $select);
        }elseif ($type == 'messages'){
            Widget_messages::messageHandle($archive, $select);
        }elseif($type == 'resources' or $type == 'resources_page'){
            Widget_CustomHandler::handleResourcesPage($archive, $select);
        }elseif($type == 'hotposts' or $type == 'hotposts_page'){
            Widget_CustomHandler::handleHotPostsPage($archive, $select);
        }

        return true; // 不输出文章 // 查看源码
    }
    // end


    // 重写 typecho 后台上传
    public static function uploadHandle($file)
    {
        $user = Typecho_Widget::widget('Widget_User');
        $user->execute();
        $allowNoneAdminUpload = Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->allowNoneAdminUpload;
        $upload = new Widget_Upload_Extend();
        if ($allowNoneAdminUpload) return $upload->uploadHandle($file);
        else{
            if ($user->pass('editor', true)){
                return $upload->uploadHandle($file);
            }else{
                return false;
            }
        }
    }

    /**
     * 输出打赏信息
     * @return string
     */
    public static function Donate()
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OneCircle');
        $loading = Helper::options()->themeUrl('assets/img/loading.svg', 'onecircle');
        $returnHtml = '
             <div class="support-author text-center">
                 <button id="support_author" data-toggle="modal" data-target="#donateModal" class="btn btn-pay btn-danger btn-rounded"><svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-wallet-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v2h6a.5.5 0 0 1 .5.5c0 .253.08.644.306.958.207.288.557.542 1.194.542.637 0 .987-.254 1.194-.542.226-.314.306-.705.306-.958a.5.5 0 0 1 .5-.5h6v-2A1.5 1.5 0 0 0 14.5 2h-13z"/><path d="M16 6.5h-5.551a2.678 2.678 0 0 1-.443 1.042C9.613 8.088 8.963 8.5 8 8.5c-.963 0-1.613-.412-2.006-.958A2.679 2.679 0 0 1 5.551 6.5H0v6A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-6z"/></svg><span>&nbsp;' . "赞赏" . '</span></button>
             </div>
             <div id="donateModal" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" data-backdrop="" aria-labelledby="mySmallModalLabel">
                 <div class="modal-dialog modal-sm  modal-dialog-centered" role="document">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h6 class="modal-title">' . "赞赏作者" . '</h6>
                             <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                         </div>
                         <div class="modal-body">
                             <p class="text-center article__reward"> <strong class="article__reward-text">' . "扫一扫支付" . '</strong> </p>
                             <div class="tab-content">';
        if ($options->AlipayPic != null) {
            $returnHtml .= '<img aria-labelledby="alipay-tab" class="lazyload pay-img tab-pane fade active show" id="alipay_author" role="tabpanel" src="' . $loading . '" data-src="' . $options->AlipayPic . '" />';
        }
        if ($options->WechatPic != null) {
            $returnHtml .= '<img aria-labelledby="wechatpay-tab" class="lazyload pay-img tab-pane fade" id="wechatpay_author" role="tabpanel" src="' . $loading . '" data-src="' . $options->WechatPic . '" />';
        }

        $returnHtml .= '</div>
                             <div class="article__reward-border mb20 mt10"></div><div class="text-center">
                             <ul class="text-center nav d-block" role="tablist">';
        if ($options->AlipayPic != null) {
            $returnHtml .= '<li class="pay-button nav-item" role="presentation" class="active"><button href="#alipay_author" id="alipay-tab" aria-controls="alipay_author" role="tab" data-toggle="tab" aria-selected="true" class="btn m-b-xs m-r-xs btn-blue"><svg t="1606310341446" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2836" width="1em" height="1em"><path d="M233.6 576c-12.8 9.6-25.6 22.4-28.8 41.6-6.4 25.6 0 54.4 22.4 80 28.8 28.8 70.4 35.2 89.6 38.4 51.2 3.2 105.6-22.4 144-51.2 16-9.6 44.8-35.2 70.4-67.2-57.6-28.8-131.2-64-208-60.8-38.4 0-67.2 6.4-89.6 19.2zM976 710.4c25.6-60.8 41.6-128 41.6-198.4C1017.6 233.6 790.4 6.4 512 6.4S6.4 233.6 6.4 512s227.2 505.6 505.6 505.6c166.4 0 316.8-83.2 409.6-208-86.4-41.6-230.4-115.2-316.8-156.8-41.6 48-102.4 96-172.8 115.2-44.8 12.8-83.2 19.2-124.8 9.6s-70.4-28.8-89.6-48c-9.6-9.6-19.2-22.4-25.6-38.4v3.2s-3.2-6.4-6.4-19.2c0-6.4-3.2-12.8-3.2-19.2v-35.2c3.2-19.2 12.8-44.8 35.2-64 48-48 112-51.2 147.2-48 48 0 137.6 22.4 208 48 19.2-41.6 32-89.6 41.6-118.4H307.2v-32H464v-64H275.2v-32H464v-64c0-16 3.2-22.4 16-22.4h73.6v83.2h204.8v32H553.6v64h163.2s-16 92.8-67.2 182.4C761.6 624 921.6 688 976 710.4z" fill="#ffffff" p-id="2837" data-spm-anchor-id="a313x.7781069.0.i3" class="selected"></path></svg><span>&nbsp;' . "支付宝支付" . '</span></button>
                                 </li>';
        }
        if ($options->WechatPic != null) {
            $returnHtml .= '<li class="pay-button nav-item" role="presentation"><button href="#wechatpay_author" id="wechatpay-tab" aria-controls="wechatpay_author" role="tab" data-toggle="tab" aria-selected="false" class="btn m-b-xs btn-always-success"><svg t="1606304793200" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4257" width="1em" height="1em"><path d="M390.4 615.5c-4.1 2.1-8.3 3.1-13.4 3.1-11.4 0-20.7-6.2-25.9-15.5L349 599l-81.7-178c-1-2.1-1-4.1-1-6.2 0-8.3 6.2-14.5 14.5-14.5 3.1 0 6.2 1 9.3 3.1l96.2 68.3c7.2 4.1 15.5 7.2 24.8 7.2 5.2 0 10.3-1 15.5-3.1l451.1-200.7C797 179.9 663.6 117.8 512.5 117.8c-246.2 0-446.9 166.6-446.9 372.4 0 111.7 60 213.1 154.1 281.4 7.2 5.2 12.4 14.5 12.4 23.8 0 3.1-1 6.2-2.1 9.3-7.2 27.9-19.7 73.5-19.7 75.5-1 3.1-2.1 7.2-2.1 11.4 0 8.3 6.2 14.5 14.5 14.5 3.1 0 6.2-1 8.3-3.1l97.2-56.9c7.2-4.1 15.5-7.2 23.8-7.2 4.1 0 9.3 1 13.4 2.1 45.5 13.4 95.2 20.7 145.9 20.7 246.2 0 446.9-166.6 446.9-372.4 0-62.1-18.6-121-50.7-172.8l-514 296.9-3.1 2.1z" fill="#ffffff" p-id="4258" data-spm-anchor-id="a313x.7781069.0.i2" class="selected"></path></svg><span>&nbsp;' . "微信支付" . '</span></button>
                                 </li>';
        }

        $returnHtml .= '</ul></div>
                         </div>
                     </div>
                 </div>
             </div>
        ';

        return $returnHtml;
    }
    /**
     * 积分
     */
    public static function loginSucceed($user, $name, $password, $remember){
        $type = 'login';
        if($user->have()){
            $srcId = null;
            Typecho_Widget::widget('Widget_User_Credits')->setUserCredits($user->uid,$type,$srcId);
        }
    }
    public static function finishComment($archive){
        Widget_Common::credits('reply',null,$archive->coid);
    }


    /**
     * 自动输出摘要
     * @access public
     * @return string
     */
    public static function excerptEx($html, $widget, $lastResult){
        $JKHRule='/<!--jkhelper start-->([\s\S]*?)<!--jkhelper end-->/i';
        preg_match_all($JKHRule, $html, $hide_words);
        if(!$hide_words[0]){
            $JKHRule='/&lt;!--jkhelper start--&gt;([\s\S]*?)&lt;!--jkhelper end--&gt;/i';
        }
        $html=trim($html);
        if (preg_match_all($JKHRule, $html, $hide_words)){
            $html = str_replace($hide_words[0], '', $html);
        }
        $html=Typecho_Common::subStr(strip_tags($html), 0, 140, "...");
        return $html;
    }

    /**
     * 自动输出内容
     * @access public
     * @return string
     */
    public static function contentEx($html, $widget, $lastResult){
        $JKHRule='/<!--jkhelper start-->([\s\S]*?)<!--jkhelper end-->/i';
        preg_match_all($JKHRule, $html, $hide_words);
        if(!$hide_words[0]){
            $JKHRule='/&lt;!--jkhelper start--&gt;([\s\S]*?)&lt;!--jkhelper end--&gt;/i';
        }
        $html = empty( $lastResult ) ? $html : $lastResult;
        $html = trim($html);
        if (preg_match_all($JKHRule, $html, $hide_content)){
            if(!empty($hide_content)){
                $db = Typecho_Db::get();

                //
                $loginUrl = Helper::options()->loginUrl;
                $jifenPay = $db->fetchObject($db->select('str_value')->from('table.fields')->where('cid = ? and name = ?',$widget->cid,'jifenPay'))->str_value;
                $jifenPay = $jifenPay? $jifenPay:0;

                // 获取插件版本号
                $pluginV = get_plugins_info();
                // 全局 已获得阅读权限输出
                $showRes = '<link rel="stylesheet" href="/usr/themes/onecircle/assets/css/jifenpay.min.css?v='.$pluginV.'" type="text/css">
<div class="jkhelperpost"><span class="jkhelper_content">'.$hide_content[1][0].'</span><span class="jkhelper_top_left"><span>您已获得阅读权限</span></span><span class="jkhelper_top_right"><img src="/usr/plugins/OneCircle/assets/img/icon.png" nogallery="nogallery" no-zoom="true" data-url="/usr/plugins/OneCircle/assets/img/icon.png" class="scrollLoading"></span></div>';
                // 积分为 0 直接可见
                if ($jifenPay==0){
                    $html = str_replace_first($hide_content[0][0]?$hide_content[0][0]:$hide_content[0], $showRes, $html);
                    return $html;
                }
                // 积分不为 0
                if ($widget->user->hasLogin()){
                    // 检查是否已经支付过积分
                    $obj = $db->fetchObject($db->select('id')->from('table.creditslog')->where('uid = ? and srcId = ? and type = ?',$widget->user->uid,$widget->cid,'jifenpay'));
                    if(!empty((array)$obj)) $id=$obj->id;
                    if (!empty($id)){
                        $html = str_replace_first($hide_content[0][0]?$hide_content[0][0]:$hide_content[0], $showRes, $html);
                        return $html;
                    }

                    $ustr = '注册用户 LV'. $widget->user->level;
                    $pstr = '<span>支付积分<b>￥'.$jifenPay.'<i></i></b>以后下载<a onclick="callJifenPay()"> 立即支付</a></span>';
                }else{
                    $ustr = '游客';
                    $pstr = '<span>请先登录<a href="'.$loginUrl.'">登录</a></span>';
                }
                /* 积分组件 */
                $repstr = '<link rel="stylesheet" href="/usr/themes/onecircle/assets/css/jifenpay.min.css?v="'.$pluginV.' type="text/css"><div id="download-box" class="download-box mg-b">
    <div class="download-list">
        <div class="download-item box not-allow-down" style="width: 100%;">
            <div class="download-thumb"
                 style="background-image: url("");"></div>
            <div class="download-rights"><h2><span id="i-8">阅读权限</span></h2><span class="mobile-show">查看</span>
                <ul>
                    <li class="">
                        <div><span>VIP用户组</span></div>
                        <div>免费下载</div></li>
                    <li class="">
                        <div><span>普通用户组</span></div>
                        <div><i class="b2font b2-jifen "></i><span>'.$jifenPay.'</span></div></li>
                </ul>
            </div>
            <div class="download-info"><h2>'.$widget->title.'</h2>
                <div class="download-current"><span>您当前的等级为</span> <span><span
                        class="lv-icon user-guest b2-guest"><i></i><b>'.$ustr.'</b></span></span> <span></span>
                    <div>'.$pstr.'</div>
                </div>
            </div>
        </div>
    </div>
</div>';
                /* 付费组件 */
                $paySrc = Helper::options()->rootUrl."/jkhelper/jifenPay?post_id=".$widget->cid;
                $payWidget = '<link rel="stylesheet" href="/usr/themes/onecircle/assets/css/jifenpay.min.css?v='.$pluginV.'" type="text/css">
<div id="ds-box" class="ds-box"><div class="modal"><div class="modal-content b2-radius"><div class="pay-box-title"><div class="pay-box-left">
                                '.$widget->title.'
</div><div class="pay-box-right"><span class="pay-close" onclick="callJifenPay()">×</span></div></div><div class="pay-box-content normal"><div class="pay-box-desc">支付积分</div> <div class="ds-price"><p class="ds-current-money"><i>￥</i><span>'.$jifenPay.'</span></p></div></div> <div class="pay-my-money"><span class="b2-radius">您当前的积分剩余￥'.$widget->user->credits.'<a style="display:none;" href="#" target="_blank" class="b2-color">充值余额</a></span> <p style="display: none;">商品价格为0元，请使用余额付款！</p></div> <div class="pay-type"><ul><li><button class="b2-radius" id="yuepay" onclick="yuePay()"><i class="ds-pay-yue">￥</i><span>余额</span></button></li></ul></div> <div class="pay-button"><div><button class="" onclick="jinfenPay(\''.$paySrc.'\')"><span id="chosePay" style="">请选择支付方式</span><span id="payBtn" style="display: none;">支付</span></button></div></div></div></div></div>
<script src="/usr/themes/onecircle/assets/js/jifenpay.min.js?v='.$pluginV.'"></script>';
                $repstr = $repstr.$payWidget;
                $html = str_replace_first($hide_content[0][0]?$hide_content[0][0]:$hide_content[0], $repstr, $html);

            }
        }
        return $html;
    }

    /**
     * 编辑页插入
     */
    public static function writeRender(){
        $pluginNodeStart = self::$pluginNodeStart;
        $pluginNodeEnd = self::$pluginNodeEnd;

    }
}


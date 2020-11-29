<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * OneCircle：
 * 后台接口，增加用户个人签名设置<br>
 * 加入了权限设置：修改注册时默认用户组，贡献者可直接发布文章无需审核,前台注册支持用户输入密码<br>
 * @package OneCircle
 * @author gogobody
 * @version 2.0.0
 * @link https://blog.gogobody.cn
 */

require(__DIR__ . DIRECTORY_SEPARATOR . "Action.php");
require_once 'pages/metas/Metasmanage.php';
require_once 'pages/neighbor/Widget_Neighbor.php';
require_once 'pages/blog/Widget_blog.php';
require_once(__DIR__ . DIRECTORY_SEPARATOR . "manage/Widget_CateTag_Edit.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/upload.php");



class OneCircle_Plugin extends Widget_Archive implements Typecho_Plugin_Interface
{
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

        // register apis
        // action method url : /action/oneapi
//        Helper::addAction('oneapi', 'OneCircle_Action');
        // route method url : /oneaction

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

        /** 分类名称 */
//        $name = new Typecho_Widget_Helper_Form_Element_Text('word', NULL, 'Hello World', _t('说点什么'));
//        $form->addInput($name);
        $yonghuzu = new Typecho_Widget_Helper_Form_Element_Radio('yonghuzu', array(
            'visitor' => _t('访问者'),
            'subscriber' => _t('关注者'),
            'contributor' => _t('贡献者'),
            'editor' => _t('编辑'),
            'administrator' => _t('管理员')
        ), 'contributor', _t('注册用户默认用户组设置'), _t('<p class="description">不同的用户组拥有不同的权限，具体的权限分配表请<a href="http://docs.typecho.org/develop/acl" target="_blank" rel="noopener noreferrer">参考这里</a>.</p>'));
        $form->addInput($yonghuzu);

        $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan',
            array('contributor-nb' => _t('勾选该选项让【贡献者】直接发布文章无需审核'),
                'register-nb' => _t('勾选该选项后台注册功能将可以直接设置注册密码'),
            ),
            array('contributor-nb','register-nb'), _t('拓展设置'), _t(''));
        $form->addInput($tuozhan->multiMode());

        //
        $db = Typecho_Db::get();
        $select = $db->select('mid')->from('table.metas');
        $row = $db->fetchRow($select);
        if (!empty($row)) $umid = $row['mid'];
        else $umid = 1;
        $registeruserMid = new Typecho_Widget_Helper_Form_Element_Text('registeruserMid', NULL, _t($umid), _t('用户注册后默认关注哪个分类（int）'));
        $form->addInput($registeruserMid);

        $focususerMid = new Typecho_Widget_Helper_Form_Element_Text('focususerMid', NULL, 1, _t('发布关注消息到哪个分类（int）'));
        $form->addInput($focususerMid);

        $amapJsKey= new Typecho_Widget_Helper_Form_Element_Text('amapJsKey', NULL, '', _t('高德地图 Web端(JS API) key'));
        $form->addInput($amapJsKey);

        $amapWebKey= new Typecho_Widget_Helper_Form_Element_Text('amapWebKey', NULL, '', _t('高德地图 Web服务 key'));
        $form->addInput($amapWebKey);

        $allowNoneAdminUpload = new Typecho_Widget_Helper_Form_Element_Radio('allowNoneAdminUpload',array(
                1 => _t('允许'),
                0 => _t('不允许')
        ),0,_t('是否允许非管理员后台上传文件'),_t('此设置仅针对于后台文章编辑有效'));
        $form->addInput($allowNoneAdminUpload);

        $options = Helper::options();
        $alipay = $options->themeUrl('assets/img/donate/alipay.jpg','onecircle');
        $wxpay = $options->themeUrl('assets/img/donate/wxpay.jpg','onecircle');

        //支付宝二维码
        $AlipayPic = new Typecho_Widget_Helper_Form_Element_Text('AlipayPic', NULL, _t($alipay), _t('支付宝二维码'), _t('打赏中使用的支付宝二维码,建议尺寸小于250×250,且为正方形'));
        $form->addInput($AlipayPic);
        //微信二维码
        $WechatPic = new Typecho_Widget_Helper_Form_Element_Text('WechatPic', NULL, _t($wxpay), _t('微信二维码'), _t('打赏中使用的微信二维码,建议尺寸小于250×250,且为正方形'));
        $form->addInput($WechatPic);

        //以下为博客设置
        $blogMid = new Typecho_Widget_Helper_Form_Element_Text('blogMid', NULL, NULL, _t('展示的博客分类mid'), _t('输入需要展示的博客分类的mid，空格分隔'));
        $form->addInput($blogMid);

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
        $useravatar = new Typecho_Widget_Helper_Form_Element_Text('userAvatar', NULL, null, _t('个人头像'));
        $form->addInput($useravatar);

        $userbackimg = new Typecho_Widget_Helper_Form_Element_Text('userBackImg', NULL, null, _t('背景图片'));
        $form->addInput($userbackimg);

        $sign = new Typecho_Widget_Helper_Form_Element_Text('userSign', NULL, null, _t('个性签名'));
        $form->addInput($sign);

        $tag = new Typecho_Widget_Helper_Form_Element_Text('userTag', NULL, null, _t('设置个人TAG,用英文逗号分隔'));
        $form->addInput($tag);

        $sex = new Typecho_Widget_Helper_Form_Element_Radio('userSex',array(
                1 => _t('男'),
                0 => _t("女")
        ),1,"选择性别","");
        $form->addInput($sex);

        $lifeStatus = new Typecho_Widget_Helper_Form_Element_Radio('userLifeStatus',array(
            '' => _t('保密'),
            '今日单身' => _t('今日单身'),
            '等TA出现' => _t('等TA出现'),
            '自由可撩' => _t('自由可撩'),
            '心里有人' => _t('心里有人'),
            '恋爱中' => _t('恋爱中'),
            '一言难尽' => _t('一言难尽'),
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
        $obj->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $obj->screenName, $wPassword), 'success');
        // add default follow circle
        OneCircle_Plugin::addDefaultTag($obj->user->uid);

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
            if ($obj->author->group == 'contributor' || $obj->user->group == 'contributor') {
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
        $obj->db->query($obj->db->sql()->where('cid = ?', $obj->cid)->update('table.contents')->rows($contents));

        /** 跳转验证后地址 */
        if ($obj->request->referer == 'return') {
            exit;
        } elseif (NULL != $obj->request->referer) {
            /** 发送ping */
            $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($obj->request->trackback)));
            $obj->widget('Widget_Service')->sendPing($obj->cid, $trackback);
            /** 设置提示信息 */
            $obj->widget('Widget_Notice')->set('post' == $obj->type ?
                _t('文章 "<a href="%s">%s</a>" 已经发布', $obj->permalink, $obj->title) :
                _t('文章 "%s" 等待审核', $obj->title), 'success');
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
//        fwrite($fp,print_r("-handleInit:".$archive->parameter."--\r\n",true));
//        fclose($fp); //关闭打开的文件。

//        $archive->setThemeFile('metamanage.php');

    }

    public static function handle($type,$archive,$select){
//        $fp = fopen('write.txt', 'a+b'); //a+读写方式打开，将文件指针指向文件末尾。b为强制使用二进制模式. 如果文件不存在则尝试创建之。
//        fwrite($fp,print_r("-handle:".$type."--".$archive->parameter."--".$archive->request->metatag."--\r\n",true));
//        fclose($fp); //关闭打开的文件。
        if ($type == 'metas'){
            Widget_Metasmanage::handle($archive);
        }elseif ($type == 'neighbor' or $type == 'neighbor_page'){
            Widget_Neighbor::handle($archive,$select);
        }elseif ($type == 'myblog' or $type == 'myblog_page'){
            Widget_blog::handle($archive,$select);
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
                 <button id="support_author" data-toggle="modal" data-target="#donateModal" class="btn btn-pay btn-danger btn-rounded"><svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-wallet-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v2h6a.5.5 0 0 1 .5.5c0 .253.08.644.306.958.207.288.557.542 1.194.542.637 0 .987-.254 1.194-.542.226-.314.306-.705.306-.958a.5.5 0 0 1 .5-.5h6v-2A1.5 1.5 0 0 0 14.5 2h-13z"/><path d="M16 6.5h-5.551a2.678 2.678 0 0 1-.443 1.042C9.613 8.088 8.963 8.5 8 8.5c-.963 0-1.613-.412-2.006-.958A2.679 2.679 0 0 1 5.551 6.5H0v6A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-6z"/></svg><span>&nbsp;' . _t("赞赏") . '</span></button>
             </div>
             <div id="donateModal" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" data-backdrop="" aria-labelledby="mySmallModalLabel">
                 <div class="modal-dialog modal-sm  modal-dialog-centered" role="document">
                     <div class="modal-content">
                         <div class="modal-header">
                             <h6 class="modal-title">' . _t("赞赏作者") . '</h6>
                             <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                         </div>
                         <div class="modal-body">
                             <p class="text-center article__reward"> <strong class="article__reward-text">' . _t("扫一扫支付") . '</strong> </p>
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
            $returnHtml .= '<li class="pay-button nav-item" role="presentation" class="active"><button href="#alipay_author" id="alipay-tab" aria-controls="alipay_author" role="tab" data-toggle="tab" aria-selected="true" class="btn m-b-xs m-r-xs btn-blue"><svg t="1606310341446" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2836" width="1em" height="1em"><path d="M233.6 576c-12.8 9.6-25.6 22.4-28.8 41.6-6.4 25.6 0 54.4 22.4 80 28.8 28.8 70.4 35.2 89.6 38.4 51.2 3.2 105.6-22.4 144-51.2 16-9.6 44.8-35.2 70.4-67.2-57.6-28.8-131.2-64-208-60.8-38.4 0-67.2 6.4-89.6 19.2zM976 710.4c25.6-60.8 41.6-128 41.6-198.4C1017.6 233.6 790.4 6.4 512 6.4S6.4 233.6 6.4 512s227.2 505.6 505.6 505.6c166.4 0 316.8-83.2 409.6-208-86.4-41.6-230.4-115.2-316.8-156.8-41.6 48-102.4 96-172.8 115.2-44.8 12.8-83.2 19.2-124.8 9.6s-70.4-28.8-89.6-48c-9.6-9.6-19.2-22.4-25.6-38.4v3.2s-3.2-6.4-6.4-19.2c0-6.4-3.2-12.8-3.2-19.2v-35.2c3.2-19.2 12.8-44.8 35.2-64 48-48 112-51.2 147.2-48 48 0 137.6 22.4 208 48 19.2-41.6 32-89.6 41.6-118.4H307.2v-32H464v-64H275.2v-32H464v-64c0-16 3.2-22.4 16-22.4h73.6v83.2h204.8v32H553.6v64h163.2s-16 92.8-67.2 182.4C761.6 624 921.6 688 976 710.4z" fill="#ffffff" p-id="2837" data-spm-anchor-id="a313x.7781069.0.i3" class="selected"></path></svg><span>&nbsp;' . _t("支付宝支付") . '</span></button>
                                 </li>';
        }
        if ($options->WechatPic != null) {
            $returnHtml .= '<li class="pay-button nav-item" role="presentation"><button href="#wechatpay_author" id="wechatpay-tab" aria-controls="wechatpay_author" role="tab" data-toggle="tab" aria-selected="false" class="btn m-b-xs btn-always-success"><svg t="1606304793200" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4257" width="1em" height="1em"><path d="M390.4 615.5c-4.1 2.1-8.3 3.1-13.4 3.1-11.4 0-20.7-6.2-25.9-15.5L349 599l-81.7-178c-1-2.1-1-4.1-1-6.2 0-8.3 6.2-14.5 14.5-14.5 3.1 0 6.2 1 9.3 3.1l96.2 68.3c7.2 4.1 15.5 7.2 24.8 7.2 5.2 0 10.3-1 15.5-3.1l451.1-200.7C797 179.9 663.6 117.8 512.5 117.8c-246.2 0-446.9 166.6-446.9 372.4 0 111.7 60 213.1 154.1 281.4 7.2 5.2 12.4 14.5 12.4 23.8 0 3.1-1 6.2-2.1 9.3-7.2 27.9-19.7 73.5-19.7 75.5-1 3.1-2.1 7.2-2.1 11.4 0 8.3 6.2 14.5 14.5 14.5 3.1 0 6.2-1 8.3-3.1l97.2-56.9c7.2-4.1 15.5-7.2 23.8-7.2 4.1 0 9.3 1 13.4 2.1 45.5 13.4 95.2 20.7 145.9 20.7 246.2 0 446.9-166.6 446.9-372.4 0-62.1-18.6-121-50.7-172.8l-514 296.9-3.1 2.1z" fill="#ffffff" p-id="4258" data-spm-anchor-id="a313x.7781069.0.i2" class="selected"></path></svg><span>&nbsp;' . _t("微信支付") . '</span></button>
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

}


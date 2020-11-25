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

        Typecho_Plugin::factory('Widget_Archive')->handleInit_1000 = array('OneCircle_Plugin','handleInit');
        Typecho_Plugin::factory('Widget_Archive')->handle_1000 = array('OneCircle_Plugin','handle');
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle_1000 = array('OneCircle_Plugin','uploadHandle');


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
        $insert = $db->insert('table.circle_follow')
            ->rows(array('uid' => $uid, 'mid' => 1));
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
        }

        return true; // 不输出文章 // 查看源码
    }
    // end

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

}


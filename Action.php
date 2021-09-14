<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Credits.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Common.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Users/Query.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Credits/List.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "widget/Abstract/Credits.php");
/**
 * OneCircle
 *
 * @package OneCircle
 * @author gogobody
 * @version 2.0.0
 * @link https://blog.gogobody.cn
 */
class OneCircle_Action extends Widget_Abstract_Contents
{
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->res = new Typecho_Response();
        $this->options = Helper::options();
//        var_dump($this->request->getRequestUrl());
        if (method_exists($this, $this->request->type)) {
            call_user_func(array(
                $this,
                $this->request->type
            ));
        } else {
            $this->defaults();
        }
    }
    public function action()
    {
        $this->on($this->request);
    }

    // do /oneaction  apis
    public function route()
    {
        $request = Typecho_Request::getInstance();
        $type = $request->get('type');


        switch ($type) {
            // 用于link 解析
            case "parsemeta":
                // check login and permission
                if (!$this->checkPermission($this->user->group)) {
                    $this->response->throwJson(array(
                        "msg" => "not login",
                        "code" => 0
                    ));
                    return false;
                }
                $url = $request->get('url');
                $html = $this->getUrlContent($url);
                $this->response->throwJson(array(
                    "msg" => "",
                    "code" => 1,
                    "data" => $this->getDescriptionFromContent($html, 120)
                ));
                break;
            // 用于主页前台发布
            case "getsecuritytoken":
                // check login and permission
                if (!$this->checkPermission($this->user->group)) {
                    $this->response->throwJson(array(
                        "msg" => "not login",
                        "code" => 0
                    ));
                    return false;
                }
                if ($request->isPost()) {

                    $security = $this->widget('Widget_Security');
                    $this->response->throwJson(array(
                        "msg" => "",
                        "code" => 1,
                        "data" => $security->getToken($this->request->getReferer())
                    ));
                    break;

                }
                echo 'error';
                break;
            // get login action   <?php $this
            // 用于登录
            case "getsecurl":
                if ($request->isPost()) {
                    $url = $request->get('url');
                    $path = Typecho_Router::url('do', array('action' => 'login', 'widget' => 'Login'),
                        Typecho_Common::url('index.php', $this->rootUrl));
                    $this->response->throwJson(array(
                        "msg" => "",
                        "code" => 1,
                        "data" => $this->getTokenUrl($path, $url)
                    ));
                    break;
                }
                echo 'error';
                break;
            case "getfocusmid":
                if ($request->isPost()) {
                    echo Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->focususerMid;
                    break;
                }
                echo "error method";
                break;
            case "amapKey":
                // check login and permission
                if (!$this->checkPermission($this->user->group)) {
                    $this->response->throwJson(array(
                        "msg" => "not login",
                        "code" => 0
                    ));
                    return false;
                }
                $this->response->throwJson(array(
                    "status" => 1,
                    "data" => array(
                        "jskey" => Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->amapJsKey,
                        "webkey" => Typecho_Widget::widget('Widget_Options')->plugin('OneCircle')->amapWebKey
                    )
                ));
                break;
            default:
                echo 'unhandled method';
                break;
        }

    }
    //组合返回值
    public function make_response($code, $msg, $data = null)
    {
        $response = [
            'code' => $code,
            'msg' => $msg,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->response->throwJson($response);
        return $response;
    }
    //组合返回值 成功
    public function make_success($data = null)
    {
        return $this->make_response(1, '操作成功！', $data);
    }

    //组合返回值 失败
    public function make_error($msg = '', $code = 0)
    {
        return $this->make_response($code, $msg);
    }
    public function jifenPay(){
        $postid = $this->request->get('post_id');
        if ($postid=='' or $postid == ' ' or empty($postid)){
            $this->make_error("参数错误!");
        }
        $user = Typecho_Widget::widget('Widget_User');
        if($user->have()){
            // 检查 post 是否存在
            $cid = $this->db->fetchObject($this->db->select('cid')->from('table.contents')->where('cid = ?',$postid))->cid;
            if (empty($cid)){
                $this->make_error("错误的cid");
            }
            $jifenPay = $this->db->fetchObject($this->db->select('str_value')->from('table.fields')->where('cid = ? and name = ?',$cid,'jifenPay'))->str_value;

            if ($user->credits < $jifenPay){
                $this->make_error("积分不足");
            }
            Widget_Common::credits('jifenpay',$user->uid,$postid);
            $this->make_success();

        }else{
            $this->make_error("没有登录!");
        }

    }
    // 解析网页内容
    function getUrlContent($url, $ecms = 0, $post = 0)
    {
        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36'
        );
        $ch = curl_init();
        $timeout = 15;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 伪造百度蜘蛛头部
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, $ecms);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, $post);
        // 执行
        $content = curl_exec($ch);
        if ($content == FALSE) {
            echo "error:" . curl_error($ch);
        }
        // 关闭
        curl_close($ch);
        //输出结果
        return $content;
    }

    // 获取描述
    function getDescriptionFromContent($content, $count)
    {
        preg_match("/<title>(.*?)<\/title>/s", $content, $title);
        if (count($title) == 0 || empty(trim($title[1]))) {
            preg_match('/<meta +name *=["\']?description["\']? *content=["\']?([^<>"]+)["\']?/i', $content, $res);

            if (count($res) == 0) { //match failed
                $content = preg_replace("@<script(.*?)</script>@is", "", $content);
                $content = preg_replace("@<iframe(.*?)</iframe>@is", "", $content);
                $content = preg_replace("@<style(.*?)</style>@is", "", $content);
                $content = preg_replace("@<(.*?)>@is", "", $content);
                $content = str_replace(PHP_EOL, '', $content);
                $space = array(" ", "　", "  ", " ", " ", "\t", "\n", "\r");
                $go_away = array("", "", "", "", "", "", "", "");
                $content = str_replace($space, $go_away, $content);
            } else { //match success
                $content = $res[1];
            }
        } else {
            $content = $title[1];
        }
        $content = trim($content);
        $res = mb_substr($content, 0, $count, 'UTF-8');
        if (mb_strlen($content, 'UTF-8') > $count) {
            $res = $res . "...";
        }
        return $res;
    }

    /**
     * 生成带token的路径
     *
     * @param $path
     * @param $url
     * @return string
     * @throws Typecho_Exception
     */
    function getTokenUrl($path, $url)
    {
        $parts = parse_url($path);
        $params = array();

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }
        $security = $this->widget('Widget_Security');
        if ($url) {
            $requrl = $url;
        } else
            $requrl = $this->request->getRequestUrl();
        $params['_'] = $security->getToken($requrl);
        $parts['query'] = http_build_query($params);

        return Typecho_Common::buildUrl($parts);
    }

    /**
     * check login and permission
     * @param $group
     * @return bool
     */
    function checkPermission($group)
    {
        if ($this->user->hasLogin() && ($group == 'administrator' or $group == 'editor' or $group == 'contributor')) {
            return true;
        }
        return false;
    }

}


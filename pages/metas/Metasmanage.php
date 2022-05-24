<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Widget_Metasmanage
 *
 * @package OneCircle
 * @author gogobody
 * @version 2.0.0
 * @link https://blog.gogobody.cn
 *
 * 数据结构如下：
//["type_categories_all"]=>
//array(2) {
//["all_"]=> // 大的tag 实际是大分类
//array(3) {
//    ["id"]=>
//string(1) "0"
//    ["name"]=>
//string(6) "所有"
//    ["categories"]=> // 包含的categories
//array(6) {
//        [0]=>
//array(5) {
//            ["mid"]=>
//  string(1) "7"
//            ["name"]=>
//  string(3) "dsd"
//            ["slug"]=>
//  string(3) "dsd"
//            ["description"]=>
//  NULL
//  ["order"]=>
//  string(1) "0"
//}
 */
class Widget_Metasmanage extends Typecho_Widget
{
    public static function handle($archive)
    {
        $archive->setArchiveType('metamanage');
//        $tagSelect = $archive->db->select()->from('table.metas')
//            ->where('type = ?', 'tag');
//        $select1 = $this->select()->where('type = ?', 'post')
//            ->join('table.relationships', 'table.contents.cid = table.relationships.cid',Typecho_Db::LEFT_JOIN)->where('table.relationships.mid in ?',$usermids_arr);

//        if (isset($archive->request->mid)) {
//            $tagSelect->where('mid = ?', $archive->request->filter('int')->mid);
//        }

//        if (isset($archive->request->slug)) {
//            $tagSelect->where('slug = ?', $archive->request->slug);
//        }

        /** 如果是标签 */
//        $tag = $archive->db->fetchRow($tagSelect,
//            array($archive->widget('Widget_Abstract_Metas'), 'filter'));

        // 默认tagid  =0 ，就是默认分类
        $db = Typecho_Db::get();
        $sql = "SELECT t.*,t1.name AS tagname,t1.slug AS tagslug,t1.`order` AS tagorder FROM typecho_metas t LEFT JOIN `".$db->getPrefix()."metas` t1 on t.`type` = 'category' and t1.`type` = 'catetag' and t.`tagid` = t1.`mid` WHERE t.`type` = 'category' ORDER BY t1.`order`,t.`order`";
        // 压入 this
        $res = @$db->fetchAll($sql, array($archive, 'push'));
        // while($this->next())
        // 选取有 tag 的那些 categories
        $details = array();
        foreach ($res as $selecres){
            if (!isset($selecres['tagname'])){
                $tag_slug = 'all_'; // 默认设置了一个防止冲突
                $details[$tag_slug]['id'] = $selecres['tagid'];
                $details[$tag_slug]['name']='默认';

            }else{
                $tag_slug = $selecres['tagslug'];
                $details[$tag_slug]['id'] = $selecres['tagid'];
                $details[$tag_slug]['name']=$selecres['tagname'];;
            }
            if (empty($details[$tag_slug]['categories'])){
                $details[$tag_slug]['categories'] = array();
            }
            array_push($details[$tag_slug]['categories'],array(
                'mid' => $selecres['mid'],
                'name' => $selecres['name'],
                'slug' => $selecres['slug'],
                'description'=>$selecres['description'],
                'order'=>$selecres['order']
            ));
        }
        // select all tags 选取所有的 tag
        $tags = $db->fetchAll($db->select()->from('table.metas')->where('type =?','catetag'));
        $tags_all = array();
        array_push($tags_all,array(
            'id' => 0,
            'mid' => 0,
            'name' => '默认',
            'slug' => '_all',
            'description'=>'',
            'order'=>0
        ));
        foreach ($tags as $tag){
            array_push($tags_all,array(
                'id' => $tag['mid'],
                'mid' => $tag['mid'],
                'name' => $tag['name'],
                'slug' => $tag['slug'],
                'description'=>$tag['description'],
                'order'=>$tag['order']
            ));
        }

        /** 设置分页 */
        $arr = array("type_categories_all"=>$details);
        $arr["tags_all"] = $tags_all;
        $archive->setPageRow($arr);

        /** 设置关键词 */
        $archive->setKeywords("分类管理");

        /** 设置描述 */
        $archive->setDescription("tags 分类管理");
        /** 设置标题 */
        $archive->setArchiveTitle("分类管理");
    }

}

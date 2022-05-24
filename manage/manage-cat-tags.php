<?php
include_once 'common.php';
include_once 'header.php';
include_once 'menu.php';
include_once 'Widget_CateTag_Edit.php';
include_once 'Widget_Metas_CateTag_Admin.php';
if (!defined("CIRCLE_MANAGEURL_TAG")) {
    define("CIRCLE_MANAGEURL_TAG","/extending.php?panel=OneCircle%2Fmanage%2Fmanage-cat-tags.php");
}
//$url = Typecho_Common::url('manage-tags.php', $this->options->adminUrl);
$db = Typecho_Db::get();
//$select = $db->select()->from('table.metas')->where('type = ?','catetag');
//$catetags = $db->fetchAll($select);
Typecho_Widget::widget('Widget_Metas_CateTag_Admin')->to($tags);
$widget = Typecho_Widget::widget('Widget_CateTag_Edit');
$widget->execute();
$widget->route();
?>

<div class="main">
    <div class="body container">
        <?php include_once 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8">
                <h6> 这里的分类是说比如你创建了一个圈子叫《今天吃什么呢》，那么在这里你可以为此创一个大分类叫 《生活》</h6>
                <h5> 创建完之后去前台更改分类 ： <a href="<?php _e(Helper::options()->index.'/metas')?>">点我</a></h5>
            </div>
            <div class="col-mb-12 col-tb-8" role="main">

                <form method="post" name="manage_tags" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a onclick="$('#leftaction').val('delete')" lang="<?php _e('你确认要删除这些标签吗?'); ?>" href="<?php $security->adminUrl(CIRCLE_MANAGEURL_TAG); ?>"><?php _e('删除'); ?></a></li>
                                    <li><a onclick="$('#leftaction').val('refresh')" lang="<?php _e('刷新标签可能需要等待较长时间, 你确认要刷新这些标签吗?'); ?>" href="<?php $security->adminUrl(CIRCLE_MANAGEURL_TAG); ?>"><?php _e('刷新'); ?></a></li>
<!--                                    <li class="multiline">-->
<!--                                        <button type="button" class="btn btn-s merge" rel="--><?php //$security->adminUrl(CIRCLE_MANAGEURL_TAG); ?><!--">--><?php //_e('合并到'); ?><!--</button>-->
<!--                                        <input type="text" name="merge" class="text-s" />-->
<!--                                    </li>-->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <ul class="typecho-list-notable tag-list clearfix">
                        <?php if($tags->have()): ?>
                            <?php while ($tags->next()): ?>
                                <li class="size-<?php $tags->split(5, 10, 20, 30); ?>" id="<?php $tags->theId(); ?>">
                                    <input type="checkbox" value="<?php $tags->mid(); ?>" name="mid[]"/>
                                    <span rel="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><?php $tags->name(); ?></span>
                                    <a class="tag-edit-link" href="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><i class="i-edit"></i></a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <h6 class="typecho-list-table-title"><?php _e('没有任何分类标签'); ?></h6>
                        <?php endif; ?>
                    </ul>
                    <input id="leftaction" type="hidden" name="do" value="delete" />
                </form>

            </div>
            <div class="col-mb-12 col-tb-4" role="form">
                <?php Typecho_Widget::widget('Widget_CateTag_Edit')->form()->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include_once 'copyright.php';
include_once 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        $(document).ready(function () {

            $('.typecho-list-notable').tableSelectable({
                checkEl     :   'input[type=checkbox]',
                rowEl       :   'li',
                selectAllEl :   '.typecho-table-select-all',
                actionEl    :   '.dropdown-menu a'
            });

            $('.btn-drop').dropdownMenu({
                btnEl       :   '.dropdown-toggle',
                menuEl      :   '.dropdown-menu'
            });

            $('.dropdown-menu button.merge').click(function () {
                var btn = $(this);
                $('#leftaction').val('merge')
                btn.parents('form').attr('action', btn.attr('rel')).submit();
            });

            <?php if (isset($request->mid)): ?>
            $('.typecho-mini-panel').effect('highlight', '#AACB36');
            <?php endif; ?>
        });
    })();
</script>
<?php include_once 'footer.php'; ?>


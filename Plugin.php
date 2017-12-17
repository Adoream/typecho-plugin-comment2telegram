<?php
require_once __DIR__ . '/Bootstrap.php';

/**
 * Telegram 推送评论通知
 * 
 * @package Comment2Telegram
 * @author Momiji.Jin
 * @version 1.1.0
 * @link https://jcl.moe
 */
class Comment2Telegram_Plugin implements Typecho_Plugin_Interface {
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('Comment2Telegram_Plugin', 'comment_send');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('Comment2Telegram_Plugin', 'comment_send');
        Helper::addAction("CommentEdit", "Comment2Telegram_Action");
        return _t('请配置此插件的 Token 和 Telergam Master ID, 以使您的 Telegram 推送生效');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {
        Helper::removeAction("CommentEdit");
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config (Typecho_Widget_Helper_Form $form) {
        $Mode = new Typecho_Widget_Helper_Form_Element_Radio('mode', array ('0' => '由插件处理', '1' => '外部处理'), 0, '回复处理。。', '建议选择 "插件处理"。。如果 Bot 还要实现其他功能请选择 "外部处理"');
        $form->addInput($Mode->addRule('enum', _t('必须选择一个模式'), array(0, 1)));
        $Token = new Typecho_Widget_Helper_Form_Element_Text('Token', NULL, NULL, _t('Token'), _t('需要输入指定Token'));
        $form->addInput($Token->addRule('required', _t('您必须填写一个正确的Token')));
        $MasterID = new Typecho_Widget_Helper_Form_Element_Text('MasterID', NULL, NULL, _t('MasterID'), _t('Telergam Master ID'));
        $form->addInput($MasterID->addRule('required', _t('您必须填写一个正确的 Telegram ID')));
        echo '<script>window.onload=function(){$("#typecho-option-item-Token-1 li").append(\'<div class="description"><button class="btn primary" id="setWebhook">设置 Bot 回调</button><p class="description">请先保存设置再设置回调</p></div>\');$("button#setWebhook").click(function(){var b=$(this),a=$(b).text();$(b).attr("disabled","disabled");if($("input#Token-0-2").val()==""){$(b).text("请填写Bot Token");setTimeout(function(){$(b).text(a);$(b).removeAttr("disabled")},2000);return}$.ajax({type:"POST",url:window.location.origin+"/index.php/action/CommentEdit?do=setWebhook",success:function(d,e,c){if(d.code=="0"){$(b).text("已 Reset Webhook")}else{$(b).text("失败："+d.msg)}setTimeout(function(){$(b).text(a);$(b).removeAttr("disabled")},2000)},dataType:"json"})})};</script>';
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * Telegram推送
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function comment_send($comment, $post) {
        // 初始化变量
        $text = $comment->author . ' 在 "' . $comment->title . '"(#' . $comment->cid . ') 中说到: 
> ' . $comment->text . ' (#' . $comment->coid . ')';
        
        $button = json_encode (array (
            'inline_keyboard' => array (
                array (array (
                    'text' => '垃圾评论',
                    'callback_data' => 'spam_' . $comment->coid
                )),
                array (array (
                    'text' => '删除评论',
                    'callback_data' => 'delete_' . $comment->coid
                ))
            )
        ));
                
        $GLOBALS['telegramModel']->sendMessage(MASTER, $text, NULL, $button);
    }
}

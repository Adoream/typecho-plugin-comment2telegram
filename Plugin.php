<?php
require_once __DIR__ . '/Bootstrap.php';

/**
 * Telegram 推送评论通知
 * 
 * @package Comment2Telegram
 * @author Sora Jin
 * @version 1.3.2
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
        if (false == self::isWritable(dirname(__FILE__))) {
            throw new Typecho_Plugin_Exception(_t('对不起，插件目录不可写，无法正常使用此功能'));
        }
        
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('Comment2Telegram_Plugin', 'commentSend');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('Comment2Telegram_Plugin', 'commentSend');
        Helper::addAction("CommentEdit", "Comment2Telegram_Action");
        
        Bootstrap::fetch ("https://api.aim.moe/Counter/Plugin", [
            'siteName' => $GLOBALS['options']->title,
            'siteUrl' => $GLOBALS['options']->siteUrl,
            'plugin' => 'Comment2Telegram',
            'version' => Plugin_Const::VERSION
        ], 'POST');
        
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

        $data = Bootstrap::fetch ("https://api.aim.moe/Counter/Plugin?siteName=" . $GLOBALS['options']->title . '&siteUrl=' . $GLOBALS['options']->siteUrl . '&plugin=Comment2Telegram');
        $data = json_decode ($data, true);
        Bootstrap::fetch ("https://api.aim.moe/Counter/Plugin/" . $data[0]->pid, 'DELETE');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config (Typecho_Widget_Helper_Form $form) {

        $lversion = json_decode(Bootstrap::fetch (Plugin_Const::GITHUB_REPO_API))->tag_name;
    	if ($lversion > Plugin_Const::VERSION){	
    		echo '<p style="font-size:18px;">你正在使用 <a>' . Plugin_Const::VERSION . '</a> 版本的 Comment2Telegram，最新版本为 <a style="color:red;">' . $lversion . '</a><a href="https://github.com/MoeLoli/Comment2Telegram"><button type="submit" class="btn btn-warn" style="margin-left:10px;">前往更新</button></a></p>';	
    	} else {	
    		echo '<p style="font-size:18px;">你正在使用最新版的 Comment2Telegram！</p>';	
    	}
        $Mode = new Typecho_Widget_Helper_Form_Element_Radio('mode', array ('0' => '由插件处理', '1' => '外部处理'), 0, '回复处理。。', '建议选择 "插件处理"。。如果 Bot 还要实现其他功能请选择 "外部处理"');
        $form->addInput($Mode->addRule('enum', _t('必须选择一个模式'), array(0, 1)));
        $Token = new Typecho_Widget_Helper_Form_Element_Text('Token', NULL, NULL, _t('Token'), _t('需要输入指定Token'));
        $form->addInput($Token->addRule('required', _t('您必须填写一个正确的Token')));
        $MasterID = new Typecho_Widget_Helper_Form_Element_Text('MasterID', NULL, NULL, _t('MasterID'), _t('Telergam Master ID'));
        $form->addInput($MasterID->addRule('required', _t('您必须填写一个正确的 Telegram ID')));
        echo '<style>.typecho-option-submit button[type="submit"]{display:none!important}</style><script>window.onload=function(){$(".typecho-option-submit li").append("<div class=\"description\"><button class=\"btn primary\" id=\"save\">保存设置</button></div>");$("input[name=mode]").change(function(){if($(this).val()==1){$(".description").append("<div class=\"outDeal\">保存后将会往你的 TG 中发送接口信息</div>")}else if($(this).val()==0){if($(".outDeal").length>0){$(".outDeal").remove()}}});$("button#save").click(function(){var b=$(this),a=$(b).text();$(b).attr("disabled","disabled");if($("input[name=Token]").val()==""){$(b).text("请填写Bot Token");setTimeout(function(){$(b).text(a);$(b).removeAttr("disabled")},2000);return}if($("input[name=MasterID]").val()==""){$(b).text("请填写Bot Token");setTimeout(function(){$(b).text(a);$(b).removeAttr("disabled")},2000);return}$.ajax({type:"POST",url:window.location.origin+"/action/CommentEdit?do=setWebhook",dataType:"json",data:{mode:$("input[name=mode]:checked").val(),token:$("input[name=Token]").val(),master:$("input[name=Master]").val()},success:function(d,e,c){if(d.code=="0"){$(b).text("已 Reset Webhook");setTimeout(function(){$(b).text("正在保存设置");$(".typecho-option-submit button[type=\"submit\"]").click()},2000)}else{$(b).text("失败："+d.msg)}}})})}</script>';
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
     * Telegram 推送
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function commentSend($comment, $post) {
        // 初始化变量
        $_cfg = $GLOBALS['options']->plugin('Comment2Telegram');

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
                
        $GLOBALS['telegramModel']->sendMessage($_cfg->MasterID, $text, NULL, $button);
    }

    /**
     * 检测 是否可写
     * @param $file
     * @return bool
     */
    public static function isWritable($file) {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/check_writable", 'w')) {
                @fclose($fp);
                @unlink("$dir/check_writable");
                $writeable = true;
            } else {
                $writeable = false;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = true;
            } else {
                $writeable = false;
            }
        }

        return $writeable;
    }
}

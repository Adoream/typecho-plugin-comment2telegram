<?php
    define('__COMMENT2TELEGRAM_PLUGIN_ROOT__', __DIR__);
    
    require_once __COMMENT2TELEGRAM_PLUGIN_ROOT__ . '/lib/Const.php';
    require_once __COMMENT2TELEGRAM_PLUGIN_ROOT__ . '/lib/TelegramModel.php';
        
    $GLOBALS['options'] = Helper::options();
    $all = Typecho_Plugin::export();

    if (array_key_exists('Comment2Telegram', $all['activated'])) {
        $_cfg = Helper::options()->plugin('Comment2Telegram');
        if (isset($_cfg->Token)) {
            $GLOBALS['telegramModel'] = new TelegramModel($_cfg->Token, $_cfg->MasterID);
            if ($_cfg->mode == 1 || $_POST['mode'] == 1) {
                $GLOBALS['route'] = [
                    'Add' => md5 ($_SERVER['HTTP_HOST'] . $_cfg->Token . $_cfg->MasterID),
                    'Del' => md5 (md5 ($_SERVER['HTTP_HOST'] . $_cfg->Token . $_cfg->MasterID)),
                    'Mark' => md5 (md5 (md5 ($_SERVER['HTTP_HOST'] . $_cfg->Token . $_cfg->MasterID)))
                ];
            }
        } else {
            $GLOBALS['telegramModel'] = NULL;
        }
    } else {
        $GLOBALS['telegramModel'] = NULL;
    }


    class Bootstrap {
        public static function fetch ($url, $postdata = null, $method = 'GET') {
            $ch = curl_init ();
            curl_setopt ($ch, CURLOPT_URL, $url);
            switch ($method) {
                case 'GET':

                    break;
                case 'POST':
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
                    break;
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                    break;
                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            }
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            $re = curl_exec ($ch);
            curl_close ($ch);
            
            return $re;
        }
    }

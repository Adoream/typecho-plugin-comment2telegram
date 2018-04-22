<?php
    define('__COMMENT2TELEGRAM_PLUGIN_ROOT__', __DIR__);
    
    require_once __COMMENT2TELEGRAM_PLUGIN_ROOT__ . '/Config.php';
    require_once __COMMENT2TELEGRAM_PLUGIN_ROOT__ . '/lib/Const.php';
    require_once __COMMENT2TELEGRAM_PLUGIN_ROOT__ . '/lib/TelegramModel.php';
    
    $GLOBALS['telegramModel'] = new TelegramModel;
    
    $GLOBALS['options'] = Helper::options();
    
    class Bootstrap {
        public static function fetch ($url, $postdata = null) {
            $ch = curl_init ();
            curl_setopt ($ch, CURLOPT_URL, $url);
            if (!is_null ($postdata)) {
                curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query ($postdata));
            }
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
            $re = curl_exec ($ch);
            curl_close ($ch);
            
            return $re;
        }   
    }
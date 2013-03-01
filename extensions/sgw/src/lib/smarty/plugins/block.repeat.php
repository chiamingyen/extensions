<?php

function smarty_block_repeat($params, $content, &$smarty)
{
    if (!empty($content)) {
        $intCount = intval($params['count']);
        if($intCount < 0) {
            $smarty->trigger_error("block: negative 'count' parameter");
            return;
        }
        
        $strRepeat = str_repeat($content, $intCount);
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'], $strRepeat);
        } else {
            echo $strRepeat;
        }
    }
}
?>
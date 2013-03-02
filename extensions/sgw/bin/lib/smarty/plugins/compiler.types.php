<?php

function smarty_compiler_types($tag_attrs, &$compiler)
{
    $_params = $compiler->_parse_attrs($tag_attrs);
    return "echo call_type(modify::htmlquote(".implode("), modify::htmlquote(", $_params)."), \$this);";
}

?>

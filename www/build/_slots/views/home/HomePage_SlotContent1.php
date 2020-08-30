<?php

use Vo\PageEngine;
use Vo\BaseComponent;

function RenderHomePage_SlotContent1(
    \HomePage $component,
    PageEngine $pageEngine,
    array $slots
    , ...$scope
) {
    $slotContents = [];
    
    $_content = '';

    $_content .= '
        <title>';
    $_content .= htmlentities($_component->count);
    $_content .= ' ';
    $_content .= htmlentities($_component->title);
    $_content .= '</title>
    ';
    return $_content;
   
}

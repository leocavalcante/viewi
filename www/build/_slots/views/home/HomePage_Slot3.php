<?php

use Vo\PageEngine;
use Vo\BaseComponent;

function RenderHomePage_Slot3(
    \HomePage $_component,
    PageEngine $pageEngine,
    array $slots
    , ...$scope
) {
    $slotContents = [];
    
    $_content = '';

    $_content .= '';
    $_content .= htmlentities($_component->title);
    return $_content;
   
}

<?php

namespace Viewi\JsFunctions\Functions\Ctype;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class CtypeXdigit extends BaseFunctionConverter
{
    public static string $name = 'ctype_xdigit';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'CtypeXdigit.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

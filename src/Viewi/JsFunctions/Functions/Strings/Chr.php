<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class Chr extends BaseFunctionConverter
{
    public static string $name = 'chr';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'Chr.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

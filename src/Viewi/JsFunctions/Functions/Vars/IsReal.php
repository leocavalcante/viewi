<?php

namespace Viewi\JsFunctions\Functions\Vars;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class IsReal extends BaseFunctionConverter
{
    public static string $name = 'is_real';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'IsReal.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

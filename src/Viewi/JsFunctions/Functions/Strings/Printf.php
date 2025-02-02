<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class Printf extends BaseFunctionConverter
{
    public static string $name = 'printf';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'Printf.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class Localeconv extends BaseFunctionConverter
{
    public static string $name = 'localeconv';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'Localeconv.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

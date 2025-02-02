<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class Strncmp extends BaseFunctionConverter
{
    public static string $name = 'strncmp';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'Strncmp.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

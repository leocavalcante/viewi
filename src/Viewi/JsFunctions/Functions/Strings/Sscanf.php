<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class Sscanf extends BaseFunctionConverter
{
    public static string $name = 'sscanf';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'Sscanf.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

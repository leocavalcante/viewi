<?php

namespace Viewi\JsFunctions\Functions\Strings;

use Viewi\JsFunctions\BaseFunctionConverter;
use Viewi\JsTranslator;

class NlLanginfo extends BaseFunctionConverter
{
    public static string $name = 'nl_langinfo';
    
    public static function convert(
        JsTranslator $translator,
        string $code,
        string $indentation
    ): string {
        $jsToInclue = __DIR__ . DIRECTORY_SEPARATOR . 'NlLanginfo.js';
        $translator->includeJsFile(self::$name, $jsToInclue);
        return $code . '(';
    }
}

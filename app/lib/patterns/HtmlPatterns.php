<?php

namespace App\Lib\Patterns;

class HtmlPatterns
{
    public const TAG_A_PATTERN = '<a href="[^"]*" title="[^"]*">|<\/a>';

    public const TAG_CODE_PATTERN = '<code>|<\/code>';

    public const TAG_I_PATTERN = '<i>|<\/i>';

    public const TAG_STRIKE_PATTERN = '<strike>|<\/strike>';

    public const TAG_STRONG_PATTERN = '<strong>|<\/strong>';
}
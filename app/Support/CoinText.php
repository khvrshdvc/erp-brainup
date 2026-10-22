<?php

namespace App\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Shows the word "BrainCoin" as the animated coin inside interface text. The text is escaped first;
 * screen readers still hear "BrainCoin". In Blade: @coins(text).
 */
class CoinText
{
    private static ?string $coin = null;

    public static function render(Htmlable|string|null $text): HtmlString
    {
        if ($text instanceof Htmlable) {
            return new HtmlString($text->toHtml());
        }

        return new HtmlString(preg_replace('/\bBrainCoin\b/u', self::coin(), e((string) $text)));
    }

    public static function coin(): string
    {
        return self::$coin ??= '<span class="coin-inline" title="BrainCoin">'
            .trim(Blade::render('<x-coin-mark />'))
            .'<span class="sr-only">BrainCoin</span></span>';
    }
}

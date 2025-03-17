<?php
namespace App\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('trans', [$this, 'trans']),
            new TwigFilter('json_encode', [$this, 'jsonEncode'], ['is_safe' => ['html']])
        ];
    }

    public function trans($message)
    {
        return gettext($message);
    }

    public function jsonEncode($data)
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
} 
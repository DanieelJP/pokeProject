<?php
namespace App\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TranslationExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('trans', [$this, 'trans'])
        ];
    }

    public function trans($message)
    {
        return gettext($message);
    }
} 
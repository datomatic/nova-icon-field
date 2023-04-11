<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField;

use Laravel\Nova\Fields\Field;

class NovaIconField extends Field
{
    public $component = 'nova-icon-field';

    public function resolve($resource, $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        $this->withMeta([
            'styles_url' => route(config('nova-icon-field.route.name').'.styles', [], false),
            'icons_url' => route(config('nova-icon-field.route.name').'.icons', ['style' => '[style]'], false),
            'icon_url' => route(config('nova-icon-field.route.name').'.icon', ['style' => '[style]', 'icon' => '[icon]'], false),
            'refresh_url' => route(config('nova-icon-field.route.name').'.refresh', [], false),
            'api_nova_headers' => config('nova-icon-field.route.nova_headers', []),
            'api_icon_params' => config('nova-icon-field.route.icon_params', []),
            'style_prefix' => config('nova-icon-field.style_prefix', ''),
            'style_suffix' => config('nova-icon-field.style_suffix', ''),
            'icon_prefix' => config('nova-icon-field.icon_prefix', ''),
            'icon_suffix' => config('nova-icon-field.icon_suffix', ''),
        ]);
    }

    public function addButtonText($text): NovaIconField
    {
        return $this->withMeta([
            'add_button_text' => $text
        ]);
    }

    public function defaultIcon($style, $icon): NovaIconField
    {
        return $this->withMeta([
            'default_icon_style' => $style,
            'default_icon' => $icon
        ]);
    }

    public function enforceDefaultIcon(): NovaIconField
    {
        return $this->withMeta([
            'enforce_default_icon' => true
        ]);
    }

    public function only(mixed $stylesOrIcons = []): NovaIconField
    {
        return $this->withMeta([
            'only' => is_array($stylesOrIcons) ? $stylesOrIcons : [$stylesOrIcons]
        ]);
    }
}

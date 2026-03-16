<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IconService
{
    protected function storage(): Filesystem
    {
        return Storage::disk(config('nova-icon-field.disk'));
    }

    public function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9-]+/', '', Str::kebab($filename)) ?? '';
    }

    /**
     * @return array<int, string>
     */
    public function getStyles(): array
    {
        return Cache::tags(['nova-icon-field', 'nova-icon-field.styles'])
            ->rememberForever(
                'nova-icon-field.styles',
                fn () => array_map(
                    fn ($directory) => $this->sanitizeFilename($directory),
                    $this->storage()->directories()
                )
            );
    }

    /**
     * @return array<int, string>
     */
    public function getIcons(string $style): array
    {
        $style = $this->sanitizeFilename($style);

        return Cache::tags(['nova-icon-field', 'nova-icon-field.icons'])
            ->rememberForever(
                'nova-icon-field.icons.'.$style,
                fn () => array_map(
                    fn ($file) => $this->sanitizeFilename(pathinfo($file, PATHINFO_FILENAME)),
                    $this->storage()->files($style)
                )
            );
    }

    /**
     * @return array<int, array{style: string, icon: string}>
     */
    public function getMasterIndex(): array
    {
        return Cache::tags(['nova-icon-field', 'nova-icon-field.icons'])
            ->rememberForever(
                'nova-icon-field.master-index',
                function () {
                    $storage = $this->storage();
                    $styles = array_map(
                        fn ($directory) => $this->sanitizeFilename($directory),
                        $storage->directories()
                    );

                    $icons = [];
                    foreach ($styles as $style) {
                        $files = array_map(
                            fn ($file) => $this->sanitizeFilename(pathinfo($file, PATHINFO_FILENAME)),
                            $storage->files($style)
                        );
                        foreach ($files as $icon) {
                            $icons[] = ['style' => $style, 'icon' => $icon];
                        }
                    }

                    usort($icons, fn ($a, $b) => strcmp($a['icon'], $b['icon']));

                    return $icons;
                }
            );
    }

    public function flush(): void
    {
        Cache::tags('nova-icon-field')->flush();
    }
}

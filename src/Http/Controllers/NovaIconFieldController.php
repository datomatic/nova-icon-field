<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TecnobitCore\Core\Http\Controllers\TecnobitController;

class NovaIconFieldController extends TecnobitController
{
    protected function getStorage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('nova-icon-field.disk'));
    }

    protected function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9-]+/', '', Str::kebab($filename)) ?? '';
    }

    public function styles(): JsonResponse
    {
        $styles = Cache::tags(['nova-icon-field', 'nova-icon-field.styles'])
            ->rememberForever(
                'nova-icon-field.styles',
                fn () => array_map(
                    fn ($directory) => $this->sanitizeFilename($directory),
                    $this->getStorage()->directories()
                )
            );

        return response()->json($styles);
    }

    public function icons(string $style): JsonResponse
    {
        $style = $this->sanitizeFilename($style);

        $icons = Cache::tags(['nova-icon-field', 'nova-icon-field.icons'])
            ->rememberForever(
                'nova-icon-field.icons.'.$style,
                fn () => array_map(
                    fn ($file) => $this->sanitizeFilename(pathinfo($file, PATHINFO_FILENAME)),
                    $this->getStorage()->files($style)
                )
            );

        return response()->json($icons);
    }

    public function icon(string $style, string $icon): StreamedResponse | JsonResponse
    {
        $style = $this->sanitizeFilename($style);
        $icon = $this->sanitizeFilename($icon);

        $path = $style.DIRECTORY_SEPARATOR.$icon;
        $pathWithExtension = $path.'.svg';

        if($this->getStorage()->exists($pathWithExtension)) {
            return $this->getStorage()->download($pathWithExtension);
        }
        if($this->getStorage()->exists($path)) {
            return $this->getStorage()->download($path);
        }
        return response()->json(['status' => 'not_found'], 404);
    }

    public function refresh(): JsonResponse
    {
        Cache::tags('nova-icon-field')->flush();

        return response()->json(['status' => 'ok']);
    }
}

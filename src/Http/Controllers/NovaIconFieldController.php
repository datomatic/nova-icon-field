<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NovaIconFieldController extends Controller
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

    /**
     * Build and cache a flat, sorted array of all icons across all styles.
     *
     * @return array<int, array{style: string, icon: string}>
     */
    protected function getMasterIndex(): array
    {
        return Cache::tags(['nova-icon-field', 'nova-icon-field.icons'])
            ->rememberForever(
                'nova-icon-field.master-index',
                function () {
                    $storage = $this->getStorage();
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

    /**
     * Check if an icon matches the "only" filter rules.
     *
     * @param array<int, mixed> $only
     */
    protected function matchesOnly(string $style, string $icon, array $only): bool
    {
        foreach ($only as $rule) {
            if (is_string($rule)) {
                if ($rule === $style || $rule === $icon) {
                    return true;
                }
                // Try parsing as "style icon" format
                $parts = explode(' ', $rule, 2);
                if (count($parts) === 2) {
                    $ruleStyle = preg_replace('/[^a-zA-Z0-9-]+/', '', Str::kebab($parts[0])) ?? '';
                    $ruleIcon = preg_replace('/[^a-zA-Z0-9-]+/', '', Str::kebab($parts[1])) ?? '';
                    if ($ruleStyle === $style && $ruleIcon === $icon) {
                        return true;
                    }
                }
                continue;
            }
            if (is_array($rule)) {
                $ruleStyle = $rule['style'] ?? null;
                $ruleIcon = $rule['icon'] ?? null;
                if ($ruleStyle === null && $ruleIcon === null) {
                    continue;
                }
                if (($ruleStyle === null || $ruleStyle === $style) && ($ruleIcon === null || $ruleIcon === $icon)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function search(Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');
        $styleFilter = (string) $request->input('style', 'all');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(500, max(1, (int) $request->input('per_page', 100)));
        $onlyRaw = $request->input('only');
        $only = is_string($onlyRaw) ? json_decode($onlyRaw, true) : null;

        $allIcons = $this->getMasterIndex();

        // Apply "only" filter
        if (is_array($only) && count($only) > 0) {
            $allIcons = array_values(array_filter(
                $allIcons,
                fn ($item) => $this->matchesOnly($item['style'], $item['icon'], $only)
            ));
        }

        // Calculate style counts (before search/style filtering)
        $styleCounts = [];
        foreach ($allIcons as $item) {
            $styleCounts[$item['style']] = ($styleCounts[$item['style']] ?? 0) + 1;
        }

        // Apply style filter
        if ($styleFilter !== '' && $styleFilter !== 'all') {
            $safeStyle = $this->sanitizeFilename($styleFilter);
            $allIcons = array_values(array_filter(
                $allIcons,
                fn ($item) => $item['style'] === $safeStyle
            ));
        }

        // Apply search filter
        if ($search !== '') {
            $keyword = mb_strtoupper(trim($search));
            $keywordAlt = str_replace(['-', ' '], [' ', '-'], $keyword);
            $allIcons = array_values(array_filter(
                $allIcons,
                function ($item) use ($keyword, $keywordAlt) {
                    $name = mb_strtoupper($item['icon']);
                    $nameAlt = str_replace('-', ' ', $name);
                    $keywordAltSpace = str_replace('-', ' ', $keyword);

                    return str_contains($name, $keyword)
                        || str_contains($nameAlt, $keywordAltSpace)
                        || str_contains($name, $keywordAlt)
                        || str_contains($nameAlt, $keywordAlt);
                }
            ));
        }

        $total = count($allIcons);
        $offset = ($page - 1) * $perPage;
        $data = array_slice($allIcons, $offset, $perPage);
        $hasMore = ($offset + $perPage) < $total;

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => $hasMore,
                'total' => $total,
            ],
            'styles' => $styleCounts,
        ]);
    }
}

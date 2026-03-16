<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField\Http\Controllers;

use Datomatic\NovaIconField\Services\IconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NovaIconFieldController extends Controller
{
    public function styles(IconService $service): JsonResponse
    {
        return response()->json($service->getStyles());
    }

    public function icons(IconService $service, string $style): JsonResponse
    {
        return response()->json($service->getIcons($style));
    }

    public function icon(IconService $service, string $style, string $icon): StreamedResponse|JsonResponse
    {
        $style = $service->sanitizeFilename($style);
        $icon = $service->sanitizeFilename($icon);

        $storage = Storage::disk(config('nova-icon-field.disk'));
        $path = $style.DIRECTORY_SEPARATOR.$icon;
        $pathWithExtension = $path.'.svg';

        if ($storage->exists($pathWithExtension)) {
            return $storage->download($pathWithExtension);
        }
        if ($storage->exists($path)) {
            return $storage->download($path);
        }

        return response()->json(['status' => 'not_found'], 404);
    }

    public function refresh(IconService $service): JsonResponse
    {
        $service->flush();

        return response()->json(['status' => 'ok']);
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

    public function search(IconService $service, Request $request): JsonResponse
    {
        $search = (string) $request->input('search', '');
        $styleFilter = (string) $request->input('style', 'all');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(500, max(1, (int) $request->input('per_page', 100)));
        $onlyRaw = $request->input('only');
        $only = is_string($onlyRaw) ? json_decode($onlyRaw, true) : null;

        $allIcons = $service->getMasterIndex();

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
            $safeStyle = $service->sanitizeFilename($styleFilter);
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

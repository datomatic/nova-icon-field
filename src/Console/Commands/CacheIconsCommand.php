<?php

declare(strict_types=1);

namespace Datomatic\NovaIconField\Console\Commands;

use Datomatic\NovaIconField\Services\IconService;
use Illuminate\Console\Command;

class CacheIconsCommand extends Command
{
    protected $signature = 'nova-icon-field:cache';

    protected $description = 'Generate all nova-icon-field caches (styles, icons, master index)';

    public function handle(IconService $service): int
    {
        $this->info('Flushing existing caches...');
        $service->flush();

        $this->info('Caching styles...');
        $styles = $service->getStyles();
        $this->info('Found '.count($styles).' styles.');

        $this->info('Caching icons per style...');
        foreach ($styles as $style) {
            $icons = $service->getIcons($style);
            $this->line("  {$style}: ".count($icons).' icons');
        }

        $this->info('Caching master index...');
        $masterIndex = $service->getMasterIndex();
        $this->info('Total: '.count($masterIndex).' icons cached.');

        return self::SUCCESS;
    }
}

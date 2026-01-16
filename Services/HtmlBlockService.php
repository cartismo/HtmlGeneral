<?php

namespace Modules\HtmlGeneral\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\HtmlGeneral\Models\HtmlBlock;

class HtmlBlockService
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected int $cacheDuration = 3600;

    /**
     * Get HTML content for a specific position
     *
     * @param string $position The position code (e.g., 'footer_left', 'header_top')
     * @param int|null $storeId The store ID (null for global blocks)
     * @param string|null $locale The locale (uses current locale if null)
     * @return string The combined HTML content for all blocks at this position
     */
    public function getContent(string $position, ?int $storeId = null, ?string $locale = null): string
    {
        $blocks = $this->getBlocks($position, $storeId);
        $locale = $locale ?? app()->getLocale();

        $content = '';
        foreach ($blocks as $block) {
            $translation = $block->translate($locale);
            if ($translation && !empty($translation->content)) {
                $content .= $translation->content;
            }
        }

        return $content;
    }

    /**
     * Get all blocks for a specific position
     *
     * @param string $position The position code
     * @param int|null $storeId The store ID (null for global blocks only)
     * @return Collection
     */
    public function getBlocks(string $position, ?int $storeId = null): Collection
    {
        $cacheKey = "html_blocks:{$position}:" . ($storeId ?? 'global');

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($position, $storeId) {
            return HtmlBlock::getForPosition($position, $storeId);
        });
    }

    /**
     * Check if there's any content at a position
     *
     * @param string $position The position code
     * @param int|null $storeId The store ID
     * @return bool
     */
    public function hasContent(string $position, ?int $storeId = null): bool
    {
        return $this->getBlocks($position, $storeId)->isNotEmpty();
    }

    /**
     * Get all available positions from config
     *
     * @return array
     */
    public function getPositions(): array
    {
        return config('htmlgeneral.positions', []);
    }

    /**
     * Clear cache for a specific position
     *
     * @param string $position
     * @param int|null $storeId
     * @return void
     */
    public function clearCache(string $position, ?int $storeId = null): void
    {
        $cacheKey = "html_blocks:{$position}:" . ($storeId ?? 'global');
        Cache::forget($cacheKey);
    }

    /**
     * Clear all HTML block caches
     *
     * @return void
     */
    public function clearAllCache(): void
    {
        $positions = array_keys($this->getPositions());

        foreach ($positions as $position) {
            Cache::forget("html_blocks:{$position}:global");
            // Note: Store-specific caches would need to be tracked separately
            // or use Cache::tags() if available
        }
    }
}
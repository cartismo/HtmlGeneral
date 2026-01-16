<?php

namespace Modules\HtmlGeneral\Models;

use App\Models\Store;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HtmlBlock extends Model implements TranslatableContract
{
    use Translatable, SoftDeletes;

    public array $translatedAttributes = ['title', 'content'];

    protected $fillable = [
        'store_id',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the store this block belongs to
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope for active blocks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by position code
     */
    public function scopeForPosition($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope by store
     */
    public function scopeForStore($query, ?int $storeId)
    {
        return $query->where(function ($q) use ($storeId) {
            $q->whereNull('store_id');
            if ($storeId) {
                $q->orWhere('store_id', $storeId);
            }
        });
    }

    /**
     * Get blocks for a specific position and store
     */
    public static function getForPosition(string $code, ?int $storeId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::withTranslation()
            ->active()
            ->forPosition($code)
            ->forStore($storeId)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get available position options from config
     */
    public static function getPositionOptions(): array
    {
        $positions = config('htmlgeneral.positions', []);
        $options = [];

        foreach ($positions as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
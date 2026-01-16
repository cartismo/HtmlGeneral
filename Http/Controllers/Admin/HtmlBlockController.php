<?php

namespace Modules\HtmlGeneral\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\HtmlGeneral\Models\HtmlBlock;

class HtmlBlockController extends Controller
{
    /**
     * List all HTML blocks
     */
    public function index(Request $request): Response
    {
        $query = HtmlBlock::withTranslation()
            ->with('store:id,code')
            ->orderBy('sort_order')
            ->orderBy('code');

        // Filter by position
        if ($request->filled('position')) {
            $query->where('code', $request->position);
        }

        // Filter by store
        if ($request->filled('store_id')) {
            if ($request->store_id === 'global') {
                $query->whereNull('store_id');
            } else {
                $query->where('store_id', $request->store_id);
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereTranslationLike('title', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $blocks = $query->paginate(20)->withQueryString();

        return Inertia::render('HtmlGeneral::Admin/Blocks/Index', [
            'blocks' => $blocks,
            'positions' => HtmlBlock::getPositionOptions(),
            'stores' => Store::orderBy('id')->get(['id', 'code']),
            'filters' => $request->only(['search', 'position', 'store_id', 'status']),
        ]);
    }

    /**
     * Show create form
     */
    public function create(): Response
    {
        $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
        $stores = Store::orderBy('id')->get(['id', 'code']);

        return Inertia::render('HtmlGeneral::Admin/Blocks/Create', [
            'languages' => $languages,
            'positions' => HtmlBlock::getPositionOptions(),
            'stores' => $stores,
        ]);
    }

    /**
     * Store new HTML block
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'code' => 'required|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'translations' => 'required|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|string',
        ]);

        $block = HtmlBlock::create([
            'store_id' => $validated['store_id'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        // Save translations
        foreach ($validated['translations'] as $locale => $translation) {
            if (!empty($translation['title']) || !empty($translation['content'])) {
                $block->translateOrNew($locale)->fill($translation);
            }
        }
        $block->save();

        return redirect()
            ->route('admin.general.html.edit', $block)
            ->with('success', 'HTML block created successfully.');
    }

    /**
     * Show edit form
     */
    public function edit(HtmlBlock $htmlBlock): Response
    {
        $htmlBlock->load('translations');

        $languages = Language::where('is_active', true)->orderBy('sort_order')->get();
        $stores = Store::orderBy('id')->get(['id', 'code']);

        // Format translations
        $translations = [];
        foreach ($languages as $language) {
            $translation = $htmlBlock->translations->where('locale', $language->code)->first();
            $translations[$language->code] = [
                'title' => $translation?->title ?? '',
                'content' => $translation?->content ?? '',
            ];
        }

        return Inertia::render('HtmlGeneral::Admin/Blocks/Edit', [
            'block' => array_merge($htmlBlock->toArray(), [
                'translations' => $translations,
            ]),
            'languages' => $languages,
            'positions' => HtmlBlock::getPositionOptions(),
            'stores' => $stores,
        ]);
    }

    /**
     * Update HTML block
     */
    public function update(Request $request, HtmlBlock $htmlBlock): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'code' => 'required|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'translations' => 'required|array',
            'translations.*.title' => 'nullable|string|max:255',
            'translations.*.content' => 'nullable|string',
        ]);

        $htmlBlock->update([
            'store_id' => $validated['store_id'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        // Update translations
        foreach ($validated['translations'] as $locale => $translation) {
            if (!empty($translation['title']) || !empty($translation['content'])) {
                $htmlBlock->translateOrNew($locale)->fill($translation);
            }
        }
        $htmlBlock->save();

        return back()->with('success', 'HTML block updated successfully.');
    }

    /**
     * Delete HTML block
     */
    public function destroy(HtmlBlock $htmlBlock): RedirectResponse
    {
        $htmlBlock->delete();

        return redirect()
            ->route('admin.general.html.index')
            ->with('success', 'HTML block deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggle(HtmlBlock $htmlBlock): RedirectResponse
    {
        $htmlBlock->update(['is_active' => !$htmlBlock->is_active]);

        return back()->with('success', 'Status updated.');
    }

    /**
     * API: Get all HTML blocks for select dropdowns
     */
    public function apiList(Request $request): JsonResponse
    {
        $query = HtmlBlock::withTranslation()
            ->orderBy('code')
            ->orderBy('sort_order');

        if ($request->has('position')) {
            $query->where('code', $request->position);
        }

        if ($request->has('store_id')) {
            $query->forStore($request->store_id);
        }

        $blocks = $query->get(['id', 'store_id', 'code', 'is_active']);

        return response()->json($blocks);
    }
}
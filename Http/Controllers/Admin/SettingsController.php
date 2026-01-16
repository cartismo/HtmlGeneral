<?php

namespace Modules\HtmlGeneral\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'html-general';
    }

    protected function getDefaultSettings(): array
    {
        return config('htmlgeneral.defaults', [
            'enabled' => true,
            'sort_order' => 0,
        ]);
    }

    public function index(): Response
    {
        $data = $this->getMultiStoreData();
        $data['config'] = config('htmlgeneral');

        return Inertia::render('HtmlGeneral::Admin/Settings', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }
}
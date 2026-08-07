<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Locales;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Editable copy blocks, declared here rather than seeded as free-form rows
     * so the settings screen always shows a known, validated set of keys.
     *
     * @return array<string, array{label:string, type:string, max:int}>
     */
    private function schema(): array
    {
        return [
            'home.hero_headline' => ['label' => 'Hero headline', 'type' => 'text', 'max' => 160],
            'home.hero_body' => ['label' => 'Hero paragraph', 'type' => 'textarea', 'max' => 400],
            'home.quality_body' => ['label' => 'Quality section body', 'type' => 'textarea', 'max' => 700],
            'cta.title' => ['label' => 'CTA band title', 'type' => 'text', 'max' => 160],
            'cta.body' => ['label' => 'CTA band body', 'type' => 'textarea', 'max' => 400],
            'contact.note' => ['label' => 'Contact response note', 'type' => 'text', 'max' => 240],
        ];
    }

    public function edit(): View
    {
        $this->authorize('manage-settings');

        return view('admin.settings', [
            'schema' => $this->schema(),
            'values' => Setting::map(),
            'locales' => Locales::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $rules = [];

        foreach ($this->schema() as $key => $definition) {
            foreach (Locales::codes() as $locale) {
                // Dots are the settings key separator *and* Laravel's nested
                // input separator, so form inputs use a `|` in the field name
                // and it is translated back on save.
                $rules[str_replace('.', '|', $key).'.'.$locale] = [
                    'nullable', 'string', 'max:'.$definition['max'],
                ];
            }
        }

        $validated = $request->validate($rules);

        foreach ($validated as $field => $translations) {
            Setting::put(str_replace('|', '.', $field), array_filter($translations, 'filled'));
        }

        Navigation::flush();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('admin.updated'));
    }
}

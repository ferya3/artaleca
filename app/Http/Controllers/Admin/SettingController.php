<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Locales;
use App\Support\Media;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Site-wide settings, declared as a schema so the screen always shows a known,
 * validated set of keys rather than a free-form key/value editor an editor can
 * typo into silence.
 */
class SettingController extends Controller
{
    /**
     * `.` is both the settings-key separator and Laravel's nested-input
     * separator, so form field names use `|` and are mapped back on save.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function schema(): array
    {
        return [
            // ── The headline numbers, editable without a deploy ──────────
            'figures' => [
                'figures.annual_capacity_m3' => ['label' => __('common.figures.capacity'), 'type' => 'number', 'max' => 100000000],
                'figures.plant_area_m2' => ['label' => __('common.figures.area'), 'type' => 'number', 'max' => 100000000],
                'figures.kiln_lines' => ['label' => __('common.figures.kilns'), 'type' => 'number', 'max' => 999],
                'figures.export_countries' => ['label' => __('common.figures.countries'), 'type' => 'number', 'max' => 999],
                'figures.employees' => ['label' => __('common.figures.employees'), 'type' => 'number', 'max' => 999999],
                'figures.since' => ['label' => __('common.figures.since'), 'type' => 'number', 'max' => 2200],
            ],

            // ── Editor-owned copy blocks ────────────────────────────────
            'copy' => [
                'home.hero_headline' => ['label' => __('admin.settings_fields.hero_headline'), 'type' => 'text', 'max' => 160],
                'home.hero_body' => ['label' => __('admin.settings_fields.hero_body'), 'type' => 'textarea', 'max' => 400],
                'home.quality_body' => ['label' => __('admin.settings_fields.quality_body'), 'type' => 'textarea', 'max' => 700],
                'cta.title' => ['label' => __('admin.settings_fields.cta_title'), 'type' => 'text', 'max' => 160],
                'cta.body' => ['label' => __('admin.settings_fields.cta_body'), 'type' => 'textarea', 'max' => 400],
                'contact.note' => ['label' => __('admin.settings_fields.contact_note'), 'type' => 'text', 'max' => 240],
            ],

            // ── SEO ─────────────────────────────────────────────────────
            'seo' => [
                'seo.default_title' => ['label' => __('admin.settings_fields.seo_title'), 'type' => 'text', 'max' => 70],
                'seo.default_description' => ['label' => __('admin.settings_fields.seo_description'), 'type' => 'textarea', 'max' => 170],
                'seo.og_image' => ['label' => __('admin.settings_fields.og_image'), 'type' => 'image', 'translatable' => false],
                'seo.google_verification' => ['label' => 'google-site-verification', 'type' => 'text', 'max' => 120, 'translatable' => false],
                'seo.twitter_handle' => ['label' => 'Twitter / X handle', 'type' => 'text', 'max' => 40, 'translatable' => false],
            ],

            // ── LocalBusiness structured data ───────────────────────────
            'business' => [
                'business.enabled' => ['label' => __('admin.settings_fields.localbusiness_enabled'), 'type' => 'checkbox', 'translatable' => false],
                'business.street' => ['label' => __('common.address'), 'type' => 'text', 'max' => 240],
                'business.locality' => ['label' => __('admin.settings_fields.locality'), 'type' => 'text', 'max' => 120],
                'business.postal_code' => ['label' => __('common.postal_code'), 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.latitude' => ['label' => 'Latitude', 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.longitude' => ['label' => 'Longitude', 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.opening_hours' => ['label' => __('common.working_hours'), 'type' => 'text', 'max' => 120, 'translatable' => false,
                    'hint' => 'schema.org format, e.g. Sa-We 08:00-17:00'],
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function flatSchema(): array
    {
        return array_merge(...array_values($this->schema()));
    }

    public function edit(): View
    {
        $this->authorize('manage-settings');

        return view('admin.settings', [
            'groups' => $this->schema(),
            'values' => Setting::map(),
            'locales' => Locales::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $rules = [];

        foreach ($this->flatSchema() as $key => $definition) {
            $field = str_replace('.', '|', $key);
            $type = $definition['type'] ?? 'text';
            $translatable = $definition['translatable'] ?? ($type !== 'number' && $type !== 'checkbox' && $type !== 'image');

            $rules[$field] = match ($type) {
                'number' => ['nullable', 'integer', 'min:0', 'max:'.($definition['max'] ?? 999999999)],
                'checkbox' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:'.implode(',', config('site.uploads.image_mimes')),
                    'max:'.config('site.uploads.max_image_kb')],
                default => $translatable ? ['nullable', 'array'] : ['nullable', 'string', 'max:'.($definition['max'] ?? 255)],
            };

            if ($translatable && ! in_array($type, ['number', 'checkbox', 'image'], true)) {
                unset($rules[$field]);

                foreach (Locales::codes() as $locale) {
                    $rules[$field.'.'.$locale] = ['nullable', 'string', 'max:'.($definition['max'] ?? 255)];
                }
            }
        }

        $validated = $request->validate($rules);

        foreach ($this->flatSchema() as $key => $definition) {
            $field = str_replace('.', '|', $key);
            $type = $definition['type'] ?? 'text';

            $value = match ($type) {
                // A replaced image only overwrites when one was actually chosen,
                // so saving the form without touching it never clears the old file.
                'image' => $request->hasFile($field)
                    ? Media::storeImage($request->file($field), 'settings')
                    : Setting::get($key),
                'checkbox' => $request->boolean($field),
                'number' => filled($validated[$field] ?? null) ? (int) $validated[$field] : null,
                default => is_array($validated[$field] ?? null)
                    ? array_filter($validated[$field], 'filled')
                    : ($validated[$field] ?? null),
            };

            Setting::put($key, $value, explode('.', $key)[0]);
        }

        Navigation::flush();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('admin.updated'));
    }
}

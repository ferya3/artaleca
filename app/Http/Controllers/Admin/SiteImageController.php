<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Locales;
use App\Support\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The site's photography and artwork, on a screen of its own.
 *
 * It was a group inside Settings, next to schema.org fields and a Twitter
 * handle. That was fine while an image was one upload; it is not now that each
 * one is a grid of language against theme, and uploading pictures is a
 * different job from editing configuration anyway.
 *
 * Each image is stored as
 *
 *     ['fa' => ['light' => '/…', 'dark' => '/…'], 'en' => [...]]
 *
 * with both axes falling back rather than failing: a language with no artwork
 * of its own uses the default language's, and night uses day.
 */
class SiteImageController extends Controller
{
    /**
     * Grouped by where the image appears, because that is how an editor looks
     * for one — never by which template happens to render it.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function schema(): array
    {
        return [
            'hero' => [
                'media.hero' => [
                    'label' => __('admin.settings_fields.media_hero'),
                ],
                'media.hero_mobile' => [
                    'label' => __('admin.settings_fields.media_hero_mobile'),
                    'hint' => __('admin.settings_fields.media_hero_mobile_hint'),
                ],
            ],

            'applications' => [
                'media.applications_infographic' => [
                    'label' => __('admin.settings_fields.media_applications_infographic'),
                    'hint' => __('admin.settings_fields.media_applications_infographic_hint'),
                ],
                'media.applications_infographic_mobile' => [
                    'label' => __('admin.settings_fields.media_applications_infographic_mobile'),
                    'hint' => __('admin.settings_fields.media_applications_infographic_mobile_hint'),
                ],
            ],

            'showcase' => [
                'media.showcase' => [
                    'label' => __('admin.settings_fields.media_showcase'),
                    'hint' => __('admin.settings_fields.media_showcase_hint'),
                ],
                'media.showcase_mobile' => [
                    'label' => __('admin.settings_fields.media_showcase_mobile'),
                ],
            ],

            'plant' => [
                'media.quality_lab' => ['label' => __('admin.settings_fields.media_quality_lab')],
                'media.plant_exterior' => ['label' => __('admin.settings_fields.media_plant_exterior')],
                'media.kiln' => ['label' => __('admin.settings_fields.media_kiln')],
                'media.screening' => ['label' => __('admin.settings_fields.media_screening')],
            ],

            'social' => [
                'seo.og_image' => [
                    'label' => __('admin.settings_fields.og_image'),
                    'hint' => __('admin.settings_fields.og_image_hint'),
                ],
            ],
        ];
    }

    /** @return list<string> */
    private function keys(): array
    {
        return array_keys(array_merge(...array_values($this->schema())));
    }

    public function edit(): View
    {
        $this->authorize('manage-settings');

        return view('admin.site-images', [
            'groups' => $this->schema(),
            'values' => Setting::map(),
            'locales' => Locales::all(),
            'defaultLocale' => Locales::default(),
            'themes' => ['light', 'dark'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $rules = [];

        foreach ($this->keys() as $key) {
            $field = str_replace('.', '|', $key);

            $rules[$field] = ['nullable', 'array'];

            foreach (Locales::codes() as $locale) {
                foreach (['light', 'dark'] as $theme) {
                    $rules["{$field}.{$locale}.{$theme}"] = [
                        'nullable', 'image',
                        'mimes:'.implode(',', config('site.uploads.image_mimes')),
                        'max:'.config('site.uploads.max_image_kb'),
                    ];
                }
            }
        }

        $request->validate($rules);

        foreach ($this->keys() as $key) {
            Setting::put($key, $this->merge($request, $key), explode('.', $key)[0]);
        }

        return redirect()
            ->route('admin.site-images.edit')
            ->with('status', __('admin.updated'));
    }

    /**
     * Fold whatever was uploaded into what is already stored.
     *
     * A slot is written only when a file was chosen for it, so saving the form
     * without touching the uploads changes nothing — the alternative is a
     * screen that silently empties itself, which is the worst possible
     * behaviour for a page whose entire job is holding files.
     *
     * @return array<string, array<string, string>>|null
     */
    private function merge(Request $request, string $key): ?array
    {
        $field = str_replace('.', '|', $key);
        $map = $this->existing($key);

        foreach (Locales::codes() as $locale) {
            foreach (['light', 'dark'] as $theme) {
                if ($request->hasFile("{$field}.{$locale}.{$theme}")) {
                    $map[$locale][$theme] = Media::storeImage(
                        $request->file("{$field}.{$locale}.{$theme}"),
                        'settings',
                    );
                }
            }
        }

        $map = array_filter(array_map(
            fn (array $themes): array => array_filter($themes, 'filled'),
            $map,
        ));

        return $map === [] ? null : $map;
    }

    /**
     * The stored value, normalised to language → theme.
     *
     * Two older shapes are promoted rather than discarded: a plain string, from
     * before images were per-language, becomes the default language's day
     * image; a language map of plain strings becomes each language's day image.
     * Anything else on a running site would mean a deploy silently blanked the
     * imagery.
     *
     * @return array<string, array<string, string>>
     */
    private function existing(string $key): array
    {
        $value = Setting::map()[$key] ?? null;

        if (is_string($value) && $value !== '') {
            return [Locales::default() => ['light' => $value]];
        }

        if (! is_array($value)) {
            return [];
        }

        $map = [];

        foreach ($value as $locale => $stored) {
            if (! in_array($locale, Locales::codes(), true)) {
                continue;
            }

            if (is_string($stored) && $stored !== '') {
                $map[$locale] = ['light' => $stored];
            } elseif (is_array($stored)) {
                $map[$locale] = array_filter([
                    'light' => is_string($stored['light'] ?? null) ? $stored['light'] : null,
                    'dark' => is_string($stored['dark'] ?? null) ? $stored['dark'] : null,
                ]);
            }
        }

        return $map;
    }
}

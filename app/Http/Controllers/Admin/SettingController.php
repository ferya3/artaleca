<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Contact;
use App\Support\DeliveryTerms;
use App\Support\Locales;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Site-wide settings, declared as a schema so the screen always shows a known,
 * validated set of keys rather than a free-form key/value editor an editor can
 * typo into silence.
 *
 * One screen per area, reached from a hub, rather than one page carrying all of
 * them: "Settings" was a single scroll of forty fields in five unlabelled
 * stretches, and the plant's own address — the thing most likely to be wrong on
 * the day the site goes live — was somewhere in the middle of it. Each area is
 * now its own page with its own name in the sidebar, so finding the address
 * means reading a list of six words rather than scrolling a form.
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
            // ── Where the company is and how to reach it ────────────────
            'contact' => [
                'contact.email' => ['label' => __('admin.settings_fields.general_email'), 'type' => 'text', 'max' => 120, 'translatable' => false],
                'contact.national_phone' => ['label' => content('common.national_phone'), 'type' => 'text',
                    'max' => 40, 'translatable' => false, 'hint' => __('admin.settings_fields.national_phone_hint')],
                'contact.sales_phone' => ['label' => __('admin.settings_fields.sales_phone'), 'type' => 'text', 'max' => 40, 'translatable' => false],
                'contact.sales_email' => ['label' => __('admin.settings_fields.sales_email'), 'type' => 'text', 'max' => 120, 'translatable' => false],
                'contact.export_email' => ['label' => __('admin.settings_fields.export_email'), 'type' => 'text', 'max' => 120, 'translatable' => false],
            ],

            /*
             * ── The messenger desks ──────────────────────────────────────
             *
             * These drive the quick-contact button on every page. They are
             * their own screen rather than three more rows on the contact
             * page, because they are the one setting somebody will come back
             * to change — the account that answers moves between people far
             * more often than the plant's address does.
             *
             * WhatsApp moved here from the contact group. Its key is unchanged
             * (`contact.whatsapp`), so an already-saved number carries over.
             */
            'support' => [
                'contact.whatsapp' => ['label' => content('common.support.channels.whatsapp'), 'type' => 'text',
                    'max' => 40, 'translatable' => false, 'hint' => __('admin.settings_fields.whatsapp_hint')],
                'contact.telegram' => ['label' => content('common.support.channels.telegram'), 'type' => 'text',
                    'max' => 60, 'translatable' => false, 'hint' => __('admin.settings_fields.telegram_hint')],
                'contact.rubika' => ['label' => content('common.support.channels.rubika'), 'type' => 'text',
                    'max' => 60, 'translatable' => false, 'hint' => __('admin.settings_fields.rubika_hint')],
                'contact.sales_phone' => ['label' => __('admin.settings_fields.sales_phone'), 'type' => 'text',
                    'max' => 40, 'translatable' => false, 'hint' => __('admin.settings_fields.support_phone_hint')],
            ],

            // ── Social profiles ─────────────────────────────────────────
            'social' => [
                'social.linkedin' => ['label' => 'LinkedIn', 'type' => 'text', 'max' => 200, 'translatable' => false],
                'social.instagram' => ['label' => 'Instagram', 'type' => 'text', 'max' => 200, 'translatable' => false],
                'social.youtube' => ['label' => 'YouTube', 'type' => 'text', 'max' => 200, 'translatable' => false],
                'social.aparat' => ['label' => 'آپارات', 'type' => 'text', 'max' => 200, 'translatable' => false],
            ],

            /*
             * ── What the quote form offers under "delivery terms" ────────
             *
             * One line per term, `CODE | explanation`. The code is stored on
             * the enquiry and read by the sales desk and the forwarder; the
             * explanation is what the buyer sees, and it is the whole reason
             * this is a setting — the select used to be five bare Incoterm
             * codes, which is an unreadable field to anybody who does not
             * work in freight. See `App\Support\DeliveryTerms`.
             */
            'delivery' => [
                'delivery.terms' => ['label' => __('form.delivery_terms'), 'type' => 'textarea', 'rows' => 7,
                    'max' => 1200, 'hint' => __('admin.settings_fields.delivery_terms_hint')],
            ],

            // ── The headline numbers, editable without a deploy ──────────
            'figures' => [
                'figures.annual_capacity_m3' => ['label' => content('common.figures.capacity'), 'type' => 'number', 'max' => 100000000],
                'figures.plant_area_m2' => ['label' => content('common.figures.area'), 'type' => 'number', 'max' => 100000000],
                'figures.kiln_lines' => ['label' => content('common.figures.kilns'), 'type' => 'number', 'max' => 999],
                'figures.export_countries' => ['label' => content('common.figures.countries'), 'type' => 'number', 'max' => 999],
                'figures.employees' => ['label' => content('common.figures.employees'), 'type' => 'number', 'max' => 999999],
                'figures.since' => ['label' => content('common.figures.since'), 'type' => 'number', 'max' => 2200],
            ],

            // ── SEO ─────────────────────────────────────────────────────
            'seo' => [
                'seo.default_title' => ['label' => __('admin.settings_fields.seo_title'), 'type' => 'text', 'max' => 70],
                'seo.default_description' => ['label' => __('admin.settings_fields.seo_description'), 'type' => 'textarea', 'max' => 170],
                'seo.google_verification' => ['label' => 'google-site-verification', 'type' => 'text', 'max' => 120, 'translatable' => false],
                'seo.twitter_handle' => ['label' => 'Twitter / X handle', 'type' => 'text', 'max' => 40, 'translatable' => false],
            ],

            // ── LocalBusiness structured data ───────────────────────────
            'business' => [
                'business.enabled' => ['label' => __('admin.settings_fields.localbusiness_enabled'), 'type' => 'checkbox', 'translatable' => false],
                'business.street' => ['label' => content('common.address'), 'type' => 'text', 'max' => 240],
                'business.locality' => ['label' => __('admin.settings_fields.locality'), 'type' => 'text', 'max' => 120],
                'business.postal_code' => ['label' => content('common.postal_code'), 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.latitude' => ['label' => __('admin.settings_fields.latitude'), 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.longitude' => ['label' => __('admin.settings_fields.longitude'), 'type' => 'text', 'max' => 20, 'translatable' => false],
                'business.opening_hours' => ['label' => content('common.working_hours'), 'type' => 'text', 'max' => 120, 'translatable' => false,
                    'hint' => 'schema.org format, e.g. Sa-We 08:00-17:00'],
            ],
        ];
    }

    public function index(): View
    {
        $this->authorize('manage-settings');

        $values = Setting::map();

        return view('admin.settings.index', [
            'groups' => collect($this->schema())->map(fn (array $fields, string $group) => [
                'group' => $group,
                'label' => __('admin.settings_groups.'.$group),
                'summary' => __('admin.settings_group_intros.'.$group),
                'total' => count($fields),
                'filled' => collect(array_keys($fields))->filter(fn (string $key) => filled($values[$key] ?? null))->count(),
            ])->values()->all(),
        ]);
    }

    public function edit(string $group): View
    {
        $this->authorize('manage-settings');

        return view('admin.settings.edit', [
            'group' => $group,
            'label' => __('admin.settings_groups.'.$group),
            'summary' => __('admin.settings_group_intros.'.$group),
            'fields' => $this->fieldsFor($group),
            'values' => Setting::map(),
            'locales' => Locales::all(),
            'placeholders' => $this->placeholdersFor($group),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $this->authorize('manage-settings');

        $fields = $this->fieldsFor($group);
        $rules = [];

        foreach ($fields as $key => $definition) {
            $field = str_replace('.', '|', $key);
            $type = $definition['type'] ?? 'text';
            $translatable = $definition['translatable'] ?? ($type !== 'number' && $type !== 'checkbox');

            if ($translatable && ! in_array($type, ['number', 'checkbox'], true)) {
                foreach (Locales::codes() as $locale) {
                    $rules[$field.'.'.$locale] = ['nullable', 'string', 'max:'.($definition['max'] ?? 255)];
                }

                continue;
            }

            $rules[$field] = match ($type) {
                'number' => ['nullable', 'integer', 'min:0', 'max:'.($definition['max'] ?? 999999999)],
                'checkbox' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:'.($definition['max'] ?? 255)],
            };
        }

        $validated = $request->validate($rules);

        foreach ($fields as $key => $definition) {
            $field = str_replace('.', '|', $key);
            $type = $definition['type'] ?? 'text';

            $value = match ($type) {
                'checkbox' => $request->boolean($field),
                'number' => filled($validated[$field] ?? null) ? (int) $validated[$field] : null,
                default => is_array($validated[$field] ?? null)
                    ? array_filter($validated[$field], 'filled')
                    : ($validated[$field] ?? null),
            };

            Setting::put($key, blank($value) && $type !== 'checkbox' ? null : $value, $group);
        }

        Navigation::flush();

        return redirect()
            ->route('admin.settings.edit', $group)
            ->with('status', __('admin.updated'));
    }

    /** @return array<string, array<string, mixed>> */
    private function fieldsFor(string $group): array
    {
        return $this->schema()[$group] ?? throw new NotFoundHttpException;
    }

    /**
     * What the site shows today for a field nobody has filled in.
     *
     * The contact details fall back to `config/site.php`, so an empty box does
     * not mean an empty line on the site — it means "still the shipped value".
     * Showing that value as the input's placeholder is the difference between a
     * form that looks unconfigured and one that says what it is doing.
     *
     * @return array<string, string>
     */
    private function placeholdersFor(string $group): array
    {
        // The delivery terms fall back to the language files the same way, so
        // an empty box there is the shipped five Incoterms rather than a form
        // with no options at all.
        if ($group === 'delivery') {
            $placeholders = [];

            foreach (Locales::codes() as $locale) {
                $placeholders[DeliveryTerms::SETTING.'.'.$locale] = DeliveryTerms::defaultText($locale);
            }

            return $placeholders;
        }

        // The support screen's fields are all `contact.*` keys, so the same
        // defaults answer for it. The messengers ship empty, which is the
        // point — an unset one has no placeholder and is simply not offered.
        if (! in_array($group, ['contact', 'support'], true)) {
            return [];
        }

        $placeholders = [];

        foreach (Contact::defaults() as $field => $default) {
            if (is_array($default)) {
                foreach ($default as $locale => $text) {
                    $placeholders['contact.'.$field.'.'.$locale] = (string) $text;
                }

                continue;
            }

            $placeholders['contact.'.$field] = $default;
        }

        return $placeholders;
    }
}

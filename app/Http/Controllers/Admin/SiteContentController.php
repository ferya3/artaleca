<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Locales;
use App\Support\Navigation;
use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page copy, on a screen of its own.
 *
 * A hub listing the areas of the site, then one form per area. Deliberately not
 * one page: there are some 370 strings across the site and three languages each,
 * and a single form would be eleven hundred inputs — unusable to edit, and
 * slower to render than anything else in the panel.
 *
 * The schema is read from the translation files rather than declared here, so a
 * string added to a page appears on this screen without anyone remembering to
 * list it.
 */
class SiteContentController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage-settings');

        return view('admin.content.index', [
            'groups' => collect(SiteContent::GROUPS)->map(fn (string $group) => [
                'group' => $group,
                'label' => __('admin.content_groups.'.$group),
                'total' => count(SiteContent::keys($group)),
                'overridden' => SiteContent::overriddenCount($group),
            ])->all(),
        ]);
    }

    public function edit(string $group): View
    {
        $this->authorize('manage-settings');

        if (! SiteContent::isGroup($group)) {
            throw new NotFoundHttpException;
        }

        return view('admin.content.edit', [
            'group' => $group,
            'label' => __('admin.content_groups.'.$group),
            'keys' => SiteContent::keys($group),
            'values' => Setting::map(),
            'locales' => Locales::all(),
            'defaultLocale' => Locales::default(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $this->authorize('manage-settings');

        if (! SiteContent::isGroup($group)) {
            throw new NotFoundHttpException;
        }

        $keys = SiteContent::keys($group);
        $rules = [];

        foreach (array_keys($keys) as $key) {
            $field = str_replace('.', '|', $key);

            foreach (Locales::codes() as $locale) {
                $rules[$field.'.'.$locale] = ['nullable', 'string', 'max:5000'];
            }
        }

        $validated = $request->validate($rules);

        foreach (array_keys($keys) as $key) {
            $field = str_replace('.', '|', $key);

            /*
             * An empty box is not an empty string, it is "no override" — the
             * translation file takes over again. Storing it as `''` would blank
             * the text on the page instead of restoring it, which is the
             * opposite of what clearing a field looks like it should do.
             */
            $value = array_filter($validated[$field] ?? [], 'filled');

            Setting::put('content.'.$group.'.'.$key, $value === [] ? null : $value, 'content');
        }

        // Navigation labels are among the strings here, and they are cached.
        Navigation::flush();

        return redirect()
            ->route('admin.content.edit', $group)
            ->with('status', __('admin.updated'));
    }
}

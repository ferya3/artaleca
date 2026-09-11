<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use App\Support\Media;
use App\Support\Navigation;
use App\Support\Search;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Schema-driven CRUD shared by every content resource.
 *
 * Each subclass declares its model, its field schema and its list columns; the
 * index/create/edit/update/destroy flow, the validation rules, the translatable
 * JSON handling, file uploads and both Blade views are shared. Adding a
 * resource is a ~40 line class rather than five controllers and five
 * templates, and every resource behaves identically for the editor.
 */
abstract class ResourceController extends Controller
{
    /**
     * The Eloquent class this resource manages.
     *
     * Public because the `record` route binding asks the controller which
     * model to resolve, which is what keeps a single set of resource routes
     * working for every subclass.
     *
     * @return class-string<Model>
     */
    abstract public function model(): string;

    /**
     * Field definitions. Recognised keys:
     *   name, label, type, translatable, rules, options, hint, width, rows, step
     *
     * Types: text | textarea | number | select | checkbox | datetime-local
     *      | list | pairs | image | gallery | document | relation
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function fields(): array;

    /**
     * Columns for the index table: attribute => label.
     *
     * @return array<string, string>
     */
    abstract protected function listColumns(): array;

    /** Route name segment, e.g. `products` for admin.products.index. */
    abstract protected function routeName(): string;

    abstract protected function title(): string;

    /** Relations eager-loaded on the index screen. */
    protected function listWith(): array
    {
        return [];
    }

    /** Attributes searched by the index search box. */
    protected function searchable(): array
    {
        return ['slug'];
    }

    // ── CRUD ────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $this->authorize('viewAny', $this->model());

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $records = $this->model()::query()
            ->with($this->listWith())
            ->when(
                filled($validated['q'] ?? null),
                fn (Builder $query) => Search::anyLike($query, $this->searchable(), $validated['q']),
            )
            ->orderBy($this->defaultSortColumn(), $this->defaultSortDirection())
            ->paginate(25)
            ->withQueryString();

        return view('admin.resource.index', [
            'records' => $records,
            'columns' => $this->listColumns(),
            'routeName' => $this->routeName(),
            'title' => $this->title(),
            'search' => $validated['q'] ?? '',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', $this->model());

        return view('admin.resource.form', $this->formData(new ($this->model())));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', $this->model());

        $model = new ($this->model());
        $this->fill($model, $request->validate($this->rules(null)), $request);
        $model->save();

        $this->syncRelations($model, $request);
        $this->afterSave($model, $request);

        return redirect()
            ->route('admin.'.$this->routeName().'.edit', $model)
            ->with('status', __('admin.created'));
    }

    public function edit(Model $record): View
    {
        $this->authorize('update', $record);

        return view('admin.resource.form', $this->formData($record));
    }

    public function update(Request $request, Model $record): RedirectResponse
    {
        $this->authorize('update', $record);

        $this->fill($record, $request->validate($this->rules($record)), $request);
        $record->save();

        $this->syncRelations($record, $request);
        $this->afterSave($record, $request);

        return redirect()
            ->route('admin.'.$this->routeName().'.edit', $record)
            ->with('status', __('admin.updated'));
    }

    public function destroy(Model $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        $record->delete();
        Navigation::flush();

        return redirect()
            ->route('admin.'.$this->routeName().'.index')
            ->with('status', __('admin.deleted'));
    }

    // ── Schema plumbing ─────────────────────────────────────────────────

    /**
     * Validation rules derived from the field schema.
     *
     * Translatable fields expand into one rule per locale; only the default
     * locale inherits a `required`, so an editor can save a record before the
     * other two translations exist.
     *
     * @return array<string, mixed>
     */
    protected function rules(?Model $record): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $base = $this->resolveRules($field, $record);

            if ($field['translatable'] ?? false) {
                // A list arrives as one textarea of lines per locale, so the
                // per-field rules (max length, and so on) do not apply to it.
                if ($type === 'list') {
                    foreach (Locales::codes() as $locale) {
                        $rules[$name.'.'.$locale] = ['nullable', 'string', 'max:4000'];
                    }

                    continue;
                }

                foreach (Locales::codes() as $locale) {
                    if ($locale === Locales::default()) {
                        $rules[$name.'.'.$locale] = $base;

                        continue;
                    }

                    /*
                     * Secondary locales are optional. `nullable` is essential
                     * rather than cosmetic: ConvertEmptyStringsToNull turns a
                     * blank input into null, which the `string` rule would
                     * otherwise reject — making it impossible to save a record
                     * translated into only one language.
                     */
                    $rules[$name.'.'.$locale] = array_values(array_unique(array_merge(
                        ['nullable'],
                        array_diff($base, ['required']),
                    )));
                }

                continue;
            }

            // An explicit `rules` key always wins, which is how a subclass makes
            // a file required on create but optional on edit.
            $explicit = array_key_exists('rules', $field) ? $base : null;

            match ($type) {
                'list', 'pairs' => $rules[$name] = ['nullable', 'string'],
                'image' => $rules[$name] = $explicit ?? $this->imageRules(),
                'gallery' => [
                    $rules[$name] = ['nullable', 'array', 'max:20'],
                    $rules[$name.'.*'] = $this->imageRules(),
                ],
                'document' => $rules[$name] = $explicit ?? $this->documentRules(),
                'relation' => $rules[$name] = ['nullable', 'array'],
                default => $rules[$name] = $base,
            };
        }

        return $rules;
    }

    /** @return list<string> */
    protected function documentRules(): array
    {
        return [
            'nullable', 'file',
            'mimes:'.implode(',', config('site.uploads.document_mimes')),
            'max:'.config('site.uploads.max_document_kb'),
        ];
    }

    /** @return list<string> */
    protected function imageRules(): array
    {
        return [
            'nullable', 'image',
            'mimes:'.implode(',', config('site.uploads.image_mimes')),
            'max:'.config('site.uploads.max_image_kb'),
        ];
    }

    /** @return list<mixed> */
    private function resolveRules(array $field, ?Model $record): array
    {
        $rules = $field['rules'] ?? ['nullable', 'string', 'max:255'];

        // Callable rules receive the record so a subclass can build a unique
        // rule that ignores the row being edited.
        return is_callable($rules) ? $rules($record) : $rules;
    }

    /** @param  array<string, mixed>  $data */
    protected function fill(Model $model, array $data, Request $request): void
    {
        foreach ($this->fields() as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            // Uploads and relations arrive outside the validated scalar payload.
            if (in_array($type, ['image', 'document', 'gallery', 'relation'], true)) {
                $this->fillUpload($model, $field, $request);

                continue;
            }

            if (! array_key_exists($name, $data)) {
                // An unchecked checkbox is simply absent from the payload.
                if ($type === 'checkbox') {
                    $model->{$name} = false;
                }

                continue;
            }

            $value = $data[$name];

            if ($field['translatable'] ?? false) {
                $value = is_array($value) ? $value : [];

                /*
                 * A translatable list is not a translatable string. It is
                 * stored as a list of per-locale entries — one per bullet, each
                 * carrying all three languages — so the three textareas are
                 * zipped back together by position. `setTranslations` expects a
                 * map of strings and would write the wrong shape.
                 */
                if ($type === 'list') {
                    $model->{$name} = $this->parseTranslatedList($value);
                } else {
                    $model->setTranslations($name, $value);
                }

                continue;
            }

            $model->{$name} = match ($type) {
                'checkbox' => (bool) $value,
                'number' => $value === null || $value === '' ? null : $value + 0,
                'list' => $this->parseList($value),
                'pairs' => $this->parsePairs($value),
                default => $value,
            };
        }

        if ($model->isFillable('slug') && blank($model->slug)) {
            $model->slug = $this->uniqueSlug($model);
        }
    }

    /** @param  array<string, mixed>  $field */
    private function fillUpload(Model $model, array $field, Request $request): void
    {
        $name = $field['name'];
        $type = $field['type'];

        if ($type === 'relation') {
            return; // handled in syncRelations, after the model has an id
        }

        if ($type === 'gallery') {
            $existing = array_values(array_filter((array) ($model->{$name} ?? [])));

            // Removals are submitted as a list of paths to drop, so reordering
            // or deleting one image never re-uploads the rest.
            foreach ((array) $request->input($name.'_remove', []) as $remove) {
                if (($index = array_search($remove, $existing, true)) !== false) {
                    Media::deleteImage($remove);
                    unset($existing[$index]);
                }
            }

            foreach ((array) $request->file($name, []) as $file) {
                $existing[] = Media::storeImage($file, $this->routeName());
            }

            $model->{$name} = array_values($existing);

            return;
        }

        if (! $request->hasFile($name)) {
            // No file chosen: keep whatever is stored. An explicit clear
            // checkbox is the only way to remove one.
            if ($request->boolean($name.'_clear')) {
                $type === 'image' ? Media::deleteImage($model->{$name}) : null;
                $model->{$name} = null;
            }

            return;
        }

        if ($type === 'image') {
            Media::deleteImage($model->{$name});
            $model->{$name} = Media::storeImage($request->file($name), $this->routeName());

            return;
        }

        $file = $request->file($name);
        $model->{$name} = Media::storeDocument($file, $this->routeName());

        // Keep the download metadata honest without asking the editor for it.
        if ($model->isFillable('file_size')) {
            $model->file_size = $file->getSize();
        }
        if ($model->isFillable('file_extension')) {
            $model->file_extension = strtolower((string) $file->extension());
        }
    }

    /** Attach many-to-many selections once the model is guaranteed an id. */
    protected function syncRelations(Model $model, Request $request): void
    {
        foreach ($this->fields() as $field) {
            if (($field['type'] ?? null) !== 'relation') {
                continue;
            }

            $relation = $field['relation'] ?? $field['name'];
            $ids = array_filter((array) $request->input($field['name'], []));

            $model->{$relation}()->sync($ids);
        }
    }

    /**
     * Newline-separated textarea → JSON list. Editors think in lines, not JSON.
     *
     * @return list<string>
     */
    /**
     * Three textareas of lines into one list of per-locale entries.
     *
     * Zipped by position: the second line of the Persian box and the second
     * line of the English box are the same bullet in two languages. The list is
     * as long as the longest box, so adding a line in one language does not
     * silently drop it for want of a translation — an entry missing a language
     * falls back through `bullets()` exactly as a missing translation does
     * anywhere else.
     *
     * @param  array<string, string|null>  $byLocale
     * @return list<array<string, string>>
     */
    protected function parseTranslatedList(array $byLocale): array
    {
        $lines = [];

        foreach (Locales::codes() as $locale) {
            $lines[$locale] = $this->parseList($byLocale[$locale] ?? null);
        }

        $length = max(array_map('count', $lines) ?: [0]);
        $entries = [];

        for ($i = 0; $i < $length; $i++) {
            $entry = [];

            foreach ($lines as $locale => $values) {
                if (filled($values[$i] ?? null)) {
                    $entry[$locale] = $values[$i];
                }
            }

            if ($entry !== []) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    protected function parseList(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * `label | value` per line → list of pairs, used for free-form spec rows.
     *
     * @return list<array{label:string,value:string}>
     */
    protected function parsePairs(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn (string $line) => array_map('trim', explode('|', $line, 2)))
            ->filter(fn (array $parts) => count($parts) === 2 && filled($parts[0]) && filled($parts[1]))
            ->map(fn (array $parts) => ['label' => $parts[0], 'value' => $parts[1]])
            ->values()
            ->all();
    }

    /**
     * Slug from the default-locale title, made unique with a numeric suffix.
     *
     * Persian and Arabic titles transliterate to nothing useful, so a record
     * with no Latin characters falls back to a short random token rather than
     * to an empty slug.
     */
    protected function uniqueSlug(Model $model): string
    {
        $source = (string) ($model->getTranslations('name')[Locales::default()]
            ?? $model->getTranslations('title')[Locales::default()]
            ?? '');

        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($model->newQuery()->where('slug', $slug)->whereKeyNot($model->getKey() ?? 0)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /** Content changed, so any cached navigation or homepage block is stale. */
    protected function afterSave(Model $model, Request $request): void
    {
        Navigation::flush();
    }

    /** @return array<string, mixed> */
    protected function formData(Model $record): array
    {
        return [
            'record' => $record,
            'fields' => $this->fields(),
            'routeName' => $this->routeName(),
            'title' => $this->title(),
            'locales' => Locales::all(),
        ];
    }

    protected function defaultSortColumn(): string
    {
        return 'position';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectController extends ResourceController
{
    public function model(): string
    {
        return Redirect::class;
    }

    protected function routeName(): string
    {
        return 'redirects';
    }

    protected function title(): string
    {
        return __('admin.redirects');
    }

    protected function searchable(): array
    {
        return ['source', 'destination'];
    }

    protected function defaultSortColumn(): string
    {
        return 'source';
    }

    protected function listColumns(): array
    {
        return [
            'source' => __('admin.redirect.source'),
            'destination' => __('admin.redirect.destination'),
            'status' => __('admin.redirect.status'),
            'hits' => __('admin.redirect.hits'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'source', 'label' => __('admin.redirect.source'),
                'hint' => __('admin.redirect.source_hint'),
                'rules' => fn (?Model $r) => ['required', 'string', 'max:255', 'starts_with:/',
                    Rule::unique('redirects', 'source')->ignore($r)]],

            ['name' => 'destination', 'label' => __('admin.redirect.destination'),
                'hint' => __('admin.redirect.destination_hint'),
                'rules' => ['required', 'string', 'max:255']],

            ['name' => 'status', 'label' => __('admin.redirect.status'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => [301 => '301 — Permanent', 302 => '302 — Temporary'],
                'rules' => ['required', Rule::in([301, 302])]],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }

    /**
     * Normalise on save so `/Old-Page/` and `old-page` cannot become two rules
     * that disagree, and reject a rule that points at itself.
     */
    protected function fill(Model $model, array $data, Request $request): void
    {
        parent::fill($model, $data, $request);

        $model->source = Redirect::normalise((string) $model->source);

        if (Redirect::normalise((string) $model->destination) === $model->source) {
            abort(422, 'A redirect cannot point at itself.');
        }
    }
}

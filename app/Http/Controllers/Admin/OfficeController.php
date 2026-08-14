<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Office;
use Illuminate\Validation\Rule;

class OfficeController extends ResourceController
{
    public function model(): string
    {
        return Office::class;
    }

    protected function routeName(): string
    {
        return 'offices';
    }

    protected function title(): string
    {
        return __('admin.offices');
    }

    protected function searchable(): array
    {
        return ['name'];
    }

    protected function listColumns(): array
    {
        return [
            'name' => __('admin.fields.name'),
            'kind_label' => __('admin.fields.office_kind'),
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => __('admin.fields.office_name'), 'translatable' => true,
                'hint' => __('admin.fields.office_name_hint'),
                'rules' => ['required', 'string', 'max:120']],

            ['name' => 'kind', 'label' => __('admin.fields.office_kind'), 'type' => 'select', 'width' => 'half',
                'hint' => __('admin.fields.office_kind_hint'),
                'options' => fn () => collect(Office::KINDS)
                    ->mapWithKeys(fn (string $k) => [$k => __('admin.office_kinds.'.$k)])->all(),
                'rules' => ['required', Rule::in(Office::KINDS)]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'hint' => __('admin.fields.office_position_hint'),
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'address', 'label' => __('admin.fields.office_address'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 3, 'rules' => ['required', 'string', 'max:300']],

            ['name' => 'postal_code', 'label' => __('common.postal_code'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:20']],

            ['name' => 'phone', 'label' => __('common.phone'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:40']],

            ['name' => 'fax', 'label' => __('common.fax'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:40']],

            ['name' => 'email', 'label' => __('common.email'), 'width' => 'half',
                'rules' => ['nullable', 'email', 'max:120']],

            ['name' => 'hours', 'label' => __('common.working_hours'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:120']],

            ['name' => 'latitude', 'label' => __('admin.settings_fields.latitude'), 'width' => 'half',
                'hint' => __('admin.fields.office_geo_hint'),
                'rules' => ['nullable', 'string', 'max:20']],

            ['name' => 'longitude', 'label' => __('admin.settings_fields.longitude'), 'width' => 'half',
                'rules' => ['nullable', 'string', 'max:20']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}

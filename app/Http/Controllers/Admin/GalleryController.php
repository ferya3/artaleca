<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class GalleryController extends ResourceController
{
    public function model(): string
    {
        return GalleryImage::class;
    }

    protected function routeName(): string
    {
        return 'gallery';
    }

    protected function title(): string
    {
        return __('admin.gallery');
    }

    protected function searchable(): array
    {
        return ['path', 'title'];
    }

    protected function listColumns(): array
    {
        return [
            'title' => __('admin.fields.name'),
            'album' => __('product.filter_by_category'),
            'position' => __('admin.position'),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'path', 'label' => __('admin.fields.image_path'), 'type' => 'image',
                // Required on create — a gallery row without an image is a
                // broken tile — but optional on edit so changing a caption
                // does not force a re-upload.
                'rules' => fn (?Model $r) => array_merge(
                    [$r?->exists ? 'nullable' : 'required'],
                    ['image', 'mimes:'.implode(',', config('site.uploads.image_mimes')),
                        'max:'.config('site.uploads.max_image_kb')],
                )],

            ['name' => 'title', 'label' => __('admin.fields.name'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:180']],

            /*
             * Alt text is required in the default locale: an image without it
             * is invisible to a screen reader and worthless to image search,
             * and "we'll add it later" never happens.
             */
            ['name' => 'alt', 'label' => 'ALT', 'translatable' => true,
                'rules' => ['required', 'string', 'max:180']],

            ['name' => 'album', 'label' => __('product.filter_by_category'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(GalleryImage::ALBUMS)
                    ->mapWithKeys(fn ($a) => [$a => __('gallery.albums.'.$a)])->all(),
                'rules' => ['required', Rule::in(GalleryImage::ALBUMS)]],

            ['name' => 'position', 'label' => __('admin.position'), 'type' => 'number', 'width' => 'half',
                'rules' => ['nullable', 'integer', 'min:0', 'max:9999']],

            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox'],
        ];
    }
}

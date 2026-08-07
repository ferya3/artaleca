<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends ResourceController
{
    public function model(): string
    {
        return Post::class;
    }

    protected function routeName(): string
    {
        return 'posts';
    }

    protected function title(): string
    {
        return __('admin.posts');
    }

    protected function searchable(): array
    {
        return ['slug', 'title'];
    }

    protected function defaultSortColumn(): string
    {
        return 'published_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
    }

    protected function listColumns(): array
    {
        return [
            'title' => __('admin.posts'),
            'type' => __('articles.types.all'),
            'published_at' => __('articles.published_on', ['date' => '']),
            'is_active' => __('admin.status'),
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => __('admin.posts'), 'translatable' => true,
                'rules' => ['required', 'string', 'max:200']],

            ['name' => 'slug', 'label' => 'Slug', 'width' => 'half',
                'rules' => fn (?Model $r) => ['nullable', 'string', 'max:200', 'alpha_dash',
                    Rule::unique('posts', 'slug')->ignore($r)]],

            ['name' => 'type', 'label' => __('articles.types.all'), 'type' => 'select', 'width' => 'half',
                'options' => fn () => collect(Post::TYPES)->mapWithKeys(fn ($t) => [$t => __('articles.types.'.$t)])->all(),
                'rules' => ['required', Rule::in(Post::TYPES)]],

            ['name' => 'published_at', 'label' => __('articles.published_on', ['date' => '']), 'type' => 'datetime-local',
                'width' => 'half', 'rules' => ['nullable', 'date']],

            ['name' => 'reading_minutes', 'label' => __('articles.reading_time', ['minutes' => '']), 'type' => 'number',
                'width' => 'half', 'rules' => ['nullable', 'integer', 'min:1', 'max:120']],

            ['name' => 'excerpt', 'label' => __('admin.fields.summary'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:600']],

            ['name' => 'body', 'label' => __('admin.fields.description'), 'type' => 'textarea', 'translatable' => true,
                'rows' => 16, 'rules' => ['nullable', 'string', 'max:40000']],

            ['name' => 'cover_image', 'label' => __('admin.fields.cover_path'), 'type' => 'image'],

            ['name' => 'meta_title', 'label' => __('admin.fields.meta_title'), 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:70']],
            ['name' => 'meta_description', 'label' => __('admin.fields.meta_description'), 'type' => 'textarea', 'translatable' => true,
                'rules' => ['nullable', 'string', 'max:170']],

            ['name' => 'is_featured', 'label' => __('admin.featured'), 'type' => 'checkbox', 'width' => 'half'],
            ['name' => 'is_active', 'label' => __('admin.active'), 'type' => 'checkbox', 'width' => 'half'],
        ];
    }

    /** Attribute the post to whoever created it, once. */
    protected function afterSave(Model $model, Request $request): void
    {
        if ($model->user_id === null) {
            $model->forceFill(['user_id' => $request->user()?->id])->saveQuietly();
        }

        parent::afterSave($model, $request);
    }
}

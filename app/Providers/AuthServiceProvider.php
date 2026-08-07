<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Application;
use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\User;
use App\Policies\ContactMessagePolicy;
use App\Policies\ContentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Content models share one policy; the two models with different rules get
     * their own. Mapping them explicitly (rather than relying on Laravel's
     * name-based discovery) is what lets eight models share one class.
     *
     * @var array<class-string, class-string>
     */
    private array $policies = [
        Product::class => ContentPolicy::class,
        ProductCategory::class => ContentPolicy::class,
        Application::class => ContentPolicy::class,
        Project::class => ContentPolicy::class,
        Post::class => ContentPolicy::class,
        Download::class => ContentPolicy::class,
        Certificate::class => ContentPolicy::class,
        Faq::class => ContentPolicy::class,

        ContactMessage::class => ContactMessagePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Site-wide copy and configuration are an administrator's job.
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());

        // A deactivated account fails every check, whatever its role says.
        Gate::before(fn (User $user) => $user->is_active ? null : false);
    }
}

<?php

declare(strict_types=1);

use App\Models\Office;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per place, replacing the fixed head-office/plant pair.
 *
 * The two addresses the site already had are carried across here rather than
 * left behind: whatever is in the settings — or, where nothing has been typed
 * yet, in `config/site.php` — becomes the first two rows, so the contact page
 * and the footer read exactly the same after this runs as before it. The
 * settings keys those addresses came from are then dropped, because two places
 * holding the same address is how they come to disagree.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('kind', 20)->default(Office::KIND_OFFICE); // office | plant
            $table->json('address');
            $table->json('hours')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('fax', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        $this->carryOverExistingAddresses();

        // The addresses now live in `offices`. What stays in settings is the
        // ways to reach the company that belong to no particular building.
        Setting::query()->whereIn('key', [
            'contact.hq_lines',
            'contact.hq_postal_code',
            'contact.plant_lines',
            'contact.plant_lat',
            'contact.plant_lng',
            'contact.hours',
            'contact.phone',
            'contact.fax',
        ])->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }

    private function carryOverExistingAddresses(): void
    {
        $settings = Setting::query()->pluck('value', 'key');

        $stored = function (string $key, mixed $default) use ($settings) {
            $value = $settings[$key] ?? null;

            return filled($value) ? $value : $default;
        };

        $config = config('site.contact');

        Office::create([
            'name' => $this->label('common.headquarters'),
            'kind' => Office::KIND_OFFICE,
            'address' => $stored('contact.hq_lines', $config['hq']['lines']),
            'postal_code' => $stored('contact.hq_postal_code', $config['hq']['postal_code']),
            'phone' => $stored('contact.phone', $config['phone']),
            'fax' => $stored('contact.fax', $config['fax']),
            'email' => $config['email'],
            'position' => 1,
            'is_active' => true,
        ]);

        Office::create([
            'name' => $this->label('common.plant'),
            'kind' => Office::KIND_PLANT,
            'address' => $stored('contact.plant_lines', $config['plant']['lines']),
            'hours' => $stored('contact.hours', $config['hours']),
            'latitude' => (string) $stored('contact.plant_lat', $config['plant']['geo']['lat']),
            'longitude' => (string) $stored('contact.plant_lng', $config['plant']['geo']['lng']),
            'position' => 2,
            'is_active' => true,
        ]);
    }

    /** @return array<string, string> */
    private function label(string $key): array
    {
        $labels = [];

        foreach (array_keys(config('site.locales')) as $locale) {
            $labels[$locale] = (string) trans($key, [], $locale);
        }

        return $labels;
    }
};

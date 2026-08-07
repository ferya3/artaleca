<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * Seeds the settings rows the admin settings screen expects to find.
 *
 * Values mirror the language files, so the site reads identically whether or
 * not an editor has overridden a block yet.
 */
class SettingSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $settings = [
            'home.hero_headline' => ['group' => 'home', 'value' => $this->t(
                'سبکدانه لیکا، با دانه‌بندی پایدار و تکرارپذیر',
                'Lightweight expanded clay aggregate, graded to repeat',
                'ركام ليكا خفيف الوزن، بتدرّج حبيبي ثابت وقابل للتكرار',
            )],

            'home.hero_body' => ['group' => 'home', 'value' => $this->t(
                'سه خط کوره دوار، کنترل کیفیت پیوسته و ظرفیت سالانه ۴۵۰٬۰۰۰ متر مکعب — برای پروژه‌هایی که به عدد ثابت نیاز دارند، نه به میانگین.',
                'Three rotary kiln lines, continuous in-house testing and 450,000 m³ of annual capacity — for projects that need a fixed number, not an average.',
                'ثلاثة خطوط أفران دوارة، واختبار مستمر في مختبر المصنع، وطاقة سنوية تبلغ ٤٥٠٬٠٠٠ متر مكعب — لمشاريع تحتاج إلى رقم ثابت لا إلى متوسط.',
            )],

            'home.quality_body' => ['group' => 'home', 'value' => $this->t(
                'هر شیفت تولید در آزمایشگاه کارخانه از نظر دانه‌بندی، وزن مخصوص انبوه، جذب آب و مقاومت فشاری دانه آزمون می‌شود. نتایج به تفکیک بچ نگهداری می‌شوند و گواهی آنالیز هر محموله در زمان بارگیری صادر می‌گردد.',
                'Every production shift is tested in the plant laboratory for grading, loose bulk density, water absorption and crushing resistance. Results are retained per batch, and a certificate of analysis is issued with each consignment at loading.',
                'تُختبر كل وردية إنتاج في مختبر المصنع من حيث التدرّج الحبيبي والكثافة الظاهرية وامتصاص الماء ومقاومة التكسير. وتُحفظ النتائج لكل دفعة، وتصدر شهادة تحليل مع كل حمولة عند التحميل.',
            )],

            'cta.title' => ['group' => 'cta', 'value' => $this->t(
                'برای پروژه خود استعلام بگیرید',
                'Get a quotation for your project',
                'اطلب عرض سعر لمشروعك',
            )],

            'cta.body' => ['group' => 'cta', 'value' => $this->t(
                'مشخصات فنی، حجم مورد نیاز و مقصد تحویل را برای ما ارسال کنید؛ کارشناسان فنی ما ظرف یک روز کاری پاسخ می‌دهند.',
                'Send us the specification, the volume you need and the delivery point. Our technical sales desk replies within one working day.',
                'أرسل لنا المواصفات الفنية والكمية المطلوبة وميناء التسليم، ويردّ قسم المبيعات الفني خلال يوم عمل واحد.',
            )],

            'contact.note' => ['group' => 'contact', 'value' => $this->t(
                'به پیام‌های دریافتی در ساعات کاری، معمولاً ظرف یک روز کاری پاسخ داده می‌شود.',
                'Messages received during working hours are normally answered within one working day.',
                'يُرد عادةً على الرسائل الواردة خلال ساعات العمل في غضون يوم عمل واحد.',
            )],
        ];

        foreach ($settings as $key => $setting) {
            Setting::put($key, $setting['value'], $setting['group']);
        }

        // Headline numbers: seeded from config so a fresh install matches the
        // brochure, then owned by the admin screen from that point on.
        foreach (config('site.figures') as $key => $value) {
            Setting::put('figures.'.$key, (int) $value, 'figures', translatable: false);
        }

        $seo = [
            'seo.default_title' => $this->t(
                'سبکدانه لیکا | تولید صنعتی با دانه‌بندی تضمین‌شده',
                'LECA lightweight aggregate | Industrial production, guaranteed grading',
                'ركام ليكا خفيف الوزن | إنتاج صناعي بتدرّج مضمون',
            ),
            'seo.default_description' => $this->t(
                __('seo.default_description', [], 'fa'),
                __('seo.default_description', [], 'en'),
                __('seo.default_description', [], 'ar'),
            ),
        ];

        foreach ($seo as $key => $value) {
            Setting::put($key, $value, 'seo');
        }

        Setting::put('seo.twitter_handle', config('site.seo.twitter_handle'), 'seo', translatable: false);

        // LocalBusiness stays switched off until an administrator confirms the
        // address is real — publishing placeholder geodata is worse than none.
        $plant = config('site.contact.plant');

        Setting::put('business.enabled', false, 'business', translatable: false);
        Setting::put('business.street', $this->t(
            $plant['lines']['fa'], $plant['lines']['en'], $plant['lines']['ar'],
        ), 'business');
        Setting::put('business.locality', $this->t('قم', 'Qom', 'قم'), 'business');
        Setting::put('business.latitude', (string) $plant['geo']['lat'], 'business', translatable: false);
        Setting::put('business.longitude', (string) $plant['geo']['lng'], 'business', translatable: false);
        Setting::put('business.opening_hours', 'Sa-We 08:00-17:00', 'business', translatable: false);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Download;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Product;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $this->pages();
        $this->partners();
        $this->productDocuments();
    }

    private function pages(): void
    {
        $pages = [
            [
                'slug' => 'careers',
                'show_in_footer' => true,
                'title' => $this->t('فرصت‌های شغلی', 'Careers', 'الوظائف'),
                'lead' => $this->t(
                    'کارخانه‌ای که سه شیفت کار می‌کند، همیشه به آدم‌های دقیق نیاز دارد.',
                    'A plant running three shifts always needs people who work precisely.',
                    'مصنع يعمل بثلاث ورديات يحتاج دائماً إلى أشخاص دقيقين.',
                ),
                'body' => $this->t(
                    "ما برای واحدهای تولید، کنترل کیفیت، نگهداری و تعمیرات و فروش صادراتی نیرو جذب می‌کنیم.\n\nرزومه خود را به آدرس پست الکترونیک درج‌شده در صفحه تماس ارسال کنید و در موضوع نامه، عنوان واحد مورد نظر را بنویسید.",
                    "We recruit for production, quality control, maintenance and export sales.\n\nSend your CV to the address on the contact page, with the department you are applying to in the subject line.",
                    "نوظّف في أقسام الإنتاج وضبط الجودة والصيانة ومبيعات التصدير.\n\nأرسل سيرتك الذاتية إلى البريد المذكور في صفحة الاتصال، مع ذكر القسم المطلوب في عنوان الرسالة.",
                ),
            ],
            [
                'slug' => 'sales-terms',
                'show_in_footer' => true,
                'title' => $this->t('شرایط عمومی فروش', 'General terms of sale', 'الشروط العامة للبيع'),
                'lead' => $this->t(
                    'شرایطی که مبنای هر پیش‌فاکتور و قرارداد تأمین قرار می‌گیرد.',
                    'The terms every proforma invoice and supply contract is written on.',
                    'الشروط التي تُبنى عليها كل فاتورة مبدئية وعقد توريد.',
                ),
                'body' => $this->t(
                    "قیمت‌ها بر مبنای متر مکعب و در شرایط تحویل توافق‌شده اعلام می‌شوند.\n\nهر محموله با گواهی آنالیز و شماره بچ تحویل می‌شود. اعتراض به کیفیت باید حداکثر ظرف هفت روز کاری از تاریخ تحویل و پیش از مصرف محموله اعلام شود؛ نمونه شاهد همان بچ در آزمایشگاه کارخانه مبنای داوری قرار می‌گیرد.",
                    "Prices are quoted per cubic metre on the agreed delivery terms.\n\nEvery consignment ships with a certificate of analysis and a batch number. A quality claim must be raised within seven working days of delivery and before the material is used; the retained sample for that batch, held in the plant laboratory, is the reference for adjudication.",
                    "تُعلَن الأسعار للمتر المكعب وفق شروط التسليم المتفق عليها.\n\nتُسلَّم كل حمولة بشهادة تحليل ورقم دفعة. ويجب تقديم أي مطالبة تتعلق بالجودة خلال سبعة أيام عمل من التسليم وقبل استخدام المادة؛ وتكون العينة الشاهدة لتلك الدفعة، المحفوظة في مختبر المصنع، هي المرجع في الفصل.",
                ),
            ],
        ];

        foreach ($pages as $index => $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [...$page, 'position' => $index + 1, 'is_active' => true],
            );
        }
    }

    private function partners(): void
    {
        $partners = [
            ['slug' => 'iran-concrete-association', 'kind' => 'association',
                'name' => $this->t('انجمن بتن ایران', 'Iranian Concrete Association', 'الجمعية الإيرانية للخرسانة')],
            ['slug' => 'building-research-centre', 'kind' => 'association',
                'name' => $this->t('مرکز تحقیقات راه، مسکن و شهرسازی', 'Road, Housing & Urban Development Research Centre', 'مركز أبحاث الطرق والإسكان والتنمية العمرانية')],
            ['slug' => 'national-standards-organization', 'kind' => 'association',
                'name' => $this->t('سازمان ملی استاندارد ایران', 'Iranian National Standards Organization', 'المنظمة الوطنية الإيرانية للمواصفات')],
            ['slug' => 'pars-bana', 'kind' => 'client',
                'name' => $this->t('گروه ساختمانی پارس بنا', 'Pars Bana Construction Group', 'مجموعة بارس بنا للإنشاءات')],
            ['slug' => 'ports-maritime-organization', 'kind' => 'client',
                'name' => $this->t('سازمان بنادر و دریانوردی', 'Ports & Maritime Organization', 'منظمة الموانئ والملاحة البحرية')],
            ['slug' => 'gulf-trading', 'kind' => 'partner',
                'name' => $this->t('نماینده فروش حاشیه خلیج فارس', 'Persian Gulf distribution partner', 'شريك التوزيع في الخليج')],

            ['slug' => 'rep-tehran', 'kind' => 'representative',
                'name' => $this->t('نمایندگی تهران', 'Tehran representative', 'وكيل طهران'),
                'summary' => $this->t(
                    'سفارش، مشاوره‌ی فنی و تحویل برای تهران و البرز.',
                    'Ordering, technical advice and delivery across Tehran and Alborz.',
                    'الطلب والاستشارة الفنية والتسليم في طهران وألبرز.')],
            ['slug' => 'rep-isfahan', 'kind' => 'representative',
                'name' => $this->t('نمایندگی اصفهان', 'Isfahan representative', 'وكيل أصفهان'),
                'summary' => $this->t(
                    'پوشش اصفهان، یزد و چهارمحال و بختیاری.',
                    'Covering Isfahan, Yazd and Chaharmahal.',
                    'تغطية أصفهان ويزد وجهارمحال.')],
            ['slug' => 'rep-mashhad', 'kind' => 'representative',
                'name' => $this->t('نمایندگی مشهد', 'Mashhad representative', 'وكيل مشهد'),
                'summary' => $this->t(
                    'پوشش خراسان رضوی، شمالی و جنوبی.',
                    'Covering the three Khorasan provinces.',
                    'تغطية محافظات خراسان الثلاث.')],
        ];

        foreach ($partners as $index => $partner) {
            Partner::updateOrCreate(
                ['slug' => $partner['slug']],
                [...$partner, 'position' => $index + 1, 'is_active' => true],
            );
        }
    }

    /** Attach the seeded datasheets to the grades they describe. */
    private function productDocuments(): void
    {
        $map = [
            'datasheet-structural-grades' => ['leca-structure-4-10', 'leca-structure-10-20'],
            'datasheet-fill-grades' => ['leca-fill-0-3', 'leca-fill-3-10'],
        ];

        foreach ($map as $downloadSlug => $productSlugs) {
            $download = Download::query()->where('slug', $downloadSlug)->first();

            if ($download === null) {
                continue;
            }

            $download->products()->sync(
                Product::query()->whereIn('slug', $productSlugs)->pluck('id'),
            );
        }
    }
}

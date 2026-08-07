<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Product;
use App\Models\Project;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        foreach ($this->projects() as $index => $data) {
            $productSlugs = $data['products'];
            $applicationSlugs = $data['applications'];
            unset($data['products'], $data['applications']);

            $project = Project::updateOrCreate(
                ['slug' => $data['slug']],
                [...$data, 'position' => $index + 1, 'is_active' => true],
            );

            $project->products()->sync(Product::query()->whereIn('slug', $productSlugs)->pluck('id'));
            $project->applications()->sync(Application::query()->whereIn('slug', $applicationSlugs)->pluck('id'));
        }
    }

    /** @return list<array<string, mixed>> */
    private function projects(): array
    {
        return [
            [
                'slug' => 'tehran-residential-tower-vanak',
                'title' => $this->t(
                    'برج مسکونی ۲۸ طبقه ونک',
                    '28-storey residential tower, Vanak',
                    'برج سكني من ٢٨ طابقاً، ونك',
                ),
                'client' => $this->t('گروه ساختمانی پارس بنا', 'Pars Bana Construction Group', 'مجموعة بارس بنا للإنشاءات'),
                'location' => $this->t('تهران، ایران', 'Tehran, Iran', 'طهران، إيران'),
                'country_code' => 'IR',
                'year' => 2024,
                'volume_m3' => 18500,
                'summary' => $this->t(
                    'بتن سبک سازه‌ای در دال تمام طبقات، برای کاهش بار لرزه‌ای و ابعاد فونداسیون.',
                    'Structural lightweight concrete throughout the floor slabs, cutting seismic demand and foundation size.',
                    'خرسانة إنشائية خفيفة الوزن في بلاطات جميع الطوابق، لخفض الطلب الزلزالي وحجم الأساسات.',
                ),
                'body' => $this->t(
                    "پروژه روی زمینی با ظرفیت باربری محدود در منطقه لرزه‌خیز طراحی شد. استفاده از بتن سبک سازه‌ای در دال تمام طبقات، جرم سازه را حدود ۲۲ درصد کاهش داد.\n\nاثر مستقیم این کاهش، کوچک‌تر شدن مقاطع ستون در طبقات پایین و حذف یک ردیف شمع از طرح فونداسیون بود. تأمین در طول ۱۴ ماه و به‌صورت پیوسته انجام شد؛ هر محموله با گواهی آنالیز جداگانه تحویل داده شد.",
                    "The site had limited bearing capacity in a seismic zone. Using structural lightweight concrete across every floor slab reduced the structural mass by roughly 22%.\n\nThat fed straight through into smaller column sections in the lower storeys and let one row of piles be taken out of the foundation design. Supply ran continuously over 14 months, each consignment delivered with its own certificate of analysis.",
                    "صُمّم المشروع على أرض محدودة قدرة التحمل في منطقة زلزالية. وأدى استخدام الخرسانة الإنشائية خفيفة الوزن في بلاطات كل الطوابق إلى خفض كتلة المنشأ بنحو ٢٢٪.\n\nوانعكس ذلك مباشرة على تصغير مقاطع الأعمدة في الطوابق السفلى وإلغاء صف من الخوازيق من تصميم الأساسات. واستمر التوريد ١٤ شهراً متواصلة، وسُلّمت كل حمولة بشهادة تحليل مستقلة.",
                ),
                'scope' => [
                    $this->t('تأمین ۱۸٬۵۰۰ متر مکعب سبکدانه سازه‌ای', 'Supply of 18,500 m³ structural aggregate', 'توريد ١٨٬٥٠٠ م³ من الركام الإنشائي'),
                    $this->t('همکاری در تنظیم طرح اختلاط با آزمایشگاه پروژه', 'Mix design support with the project laboratory', 'دعم تصميم الخلطة مع مختبر المشروع'),
                    $this->t('گواهی آنالیز به تفکیک محموله', 'Certificate of analysis per consignment', 'شهادة تحليل لكل حمولة'),
                ],
                'is_featured' => true,
                'products' => ['leca-structure-4-10'],
                'applications' => ['structural-lightweight-concrete'],
            ],

            [
                'slug' => 'bandar-abbas-port-embankment',
                'title' => $this->t(
                    'خاکریز سبک محوطه بندری بندرعباس',
                    'Lightweight embankment, Bandar Abbas port yard',
                    'ردمية خفيفة في ساحة ميناء بندر عباس',
                ),
                'client' => $this->t('سازمان بنادر و دریانوردی', 'Ports & Maritime Organization', 'منظمة الموانئ والملاحة البحرية'),
                'location' => $this->t('هرمزگان، ایران', 'Hormozgan, Iran', 'هرمزكان، إيران'),
                'country_code' => 'IR',
                'year' => 2023,
                'volume_m3' => 42000,
                'summary' => $this->t(
                    'جایگزینی خاکریز معمولی با سبکدانه روی بستر رسی نرم، برای حذف نشست بلندمدت.',
                    'Conventional fill replaced with expanded clay over soft marine clay, designing out long-term settlement.',
                    'استبدال الردم التقليدي بالطين الممدد فوق طين بحري رخو، لإلغاء الهبوط بعيد المدى.',
                ),
                'body' => $this->t(
                    "بستر محوطه از رس نرم دریایی با ضخامت بیش از ۱۲ متر تشکیل شده بود. محاسبات نشان می‌داد خاکریز متعارف بیش از ۴۰ سانتی‌متر نشست تحکیمی در ۱۰ سال ایجاد می‌کند.\n\nبا جایگزینی خاکریز، بار وارد بر بستر حدود ۷۵ درصد کاهش یافت و نشست محاسباتی به کمتر از ۵ سانتی‌متر رسید؛ بدون نیاز به پیش‌بارگذاری و بدون تأخیر در برنامه اجرا.",
                    "The yard sits on more than 12 m of soft marine clay. Calculations put consolidation settlement under a conventional embankment at over 400 mm across ten years.\n\nSubstituting the fill cut the load on the subgrade by about 75% and brought calculated settlement below 50 mm — with no preloading period and no delay to the construction programme.",
                    "تقع الساحة فوق أكثر من ١٢ متراً من الطين البحري الرخو. وأظهرت الحسابات أن هبوط التضاغط تحت ردمية تقليدية يتجاوز ٤٠٠ مم خلال عشر سنوات.\n\nوأدى استبدال الردم إلى خفض الحمل على طبقة الأساس بنحو ٧٥٪ وإنزال الهبوط المحسوب دون ٥٠ مم، دون فترة تحميل مسبق ودون تأخير في برنامج التنفيذ.",
                ),
                'scope' => [
                    $this->t('تأمین ۴۲٬۰۰۰ متر مکعب سبکدانه ژئوتکنیکی', 'Supply of 42,000 m³ geotechnical aggregate', 'توريد ٤٢٬٠٠٠ م³ من الركام الجيوتقني'),
                    $this->t('برنامه تحویل فله هماهنگ با پیشرفت اجرا', 'Bulk delivery scheduled to the placement programme', 'جدولة التوريد السائب وفق برنامج الفرش'),
                ],
                'is_featured' => true,
                'products' => ['leca-infra-10-30'],
                'applications' => ['geotechnical-fill'],
            ],

            [
                'slug' => 'doha-office-green-roof',
                'title' => $this->t(
                    'بام سبز مجتمع اداری دوحه',
                    'Green roof, Doha office complex',
                    'سطح أخضر لمجمع مكاتب في الدوحة',
                ),
                'client' => $this->t('پیمانکار محرمانه', 'Confidential contractor', 'مقاول سري'),
                'location' => $this->t('دوحه، قطر', 'Doha, Qatar', 'الدوحة، قطر'),
                'country_code' => 'QA',
                'year' => 2023,
                'volume_m3' => 3200,
                'summary' => $this->t(
                    'لایه زهکش بام سبز متمرکز روی ۹٬۰۰۰ متر مربع، در محدودیت شدید بار مرده.',
                    'Intensive green roof drainage across 9,000 m² under a tight dead-load budget.',
                    'تصريف سطح أخضر مكثّف على ٩٬٠٠٠ م² ضمن سقف صارم للحمل الميت.',
                ),
                'body' => $this->t(
                    "سازه موجود تنها ۱۸۰ کیلوگرم بر متر مربع ظرفیت اضافی داشت؛ زهکش شنی در حالت اشباع از این حد عبور می‌کرد.\n\nبا لایه ۱۰ سانتی‌متری سبکدانه ۸–۱۶، وزن اشباع لایه زهکش به حدود ۶۵ کیلوگرم بر متر مربع رسید و بودجه بار برای عمق بیشتر بستر کشت آزاد شد.",
                    "The existing structure had only 180 kg/m² of spare capacity; a gravel drainage course would have exceeded it once saturated.\n\nA 100 mm layer of 8–16 mm expanded clay brought the saturated weight of the drainage course to about 65 kg/m², freeing load budget for a deeper growing medium.",
                    "لم يكن لدى المنشأ القائم سوى ١٨٠ كجم/م² من السعة الفائضة، وكانت طبقة تصريف حصوية ستتجاوزها عند التشبّع.\n\nوأنزلت طبقة بسماكة ١٠٠ مم من الطين الممدد ٨–١٦ الوزن المشبع لطبقة التصريف إلى نحو ٦٥ كجم/م²، ما حرّر سعة حمل لعمق أكبر لوسط الزراعة.",
                ),
                'scope' => [
                    $this->t('تأمین ۳٬۲۰۰ متر مکعب در بیگ‌بگ یک متر مکعبی', 'Supply of 3,200 m³ in 1 m³ big bags', 'توريد ٣٬٢٠٠ م³ في أكياس كبيرة سعة م³'),
                    $this->t('حمل دریایی CIF بندر حمد', 'CIF sea freight to Hamad Port', 'شحن بحري CIF إلى ميناء حمد'),
                ],
                'is_featured' => true,
                'products' => ['leca-greenroof-8-16'],
                'applications' => ['green-roofs'],
            ],

            [
                'slug' => 'isfahan-hospital-roof-screed',
                'title' => $this->t(
                    'شیب‌بندی بام بیمارستان اصفهان',
                    'Roof screed, Isfahan hospital',
                    'ميول سطح مستشفى أصفهان',
                ),
                'client' => $this->t('دانشگاه علوم پزشکی اصفهان', 'Isfahan University of Medical Sciences', 'جامعة أصفهان للعلوم الطبية'),
                'location' => $this->t('اصفهان، ایران', 'Isfahan, Iran', 'أصفهان، إيران'),
                'country_code' => 'IR',
                'year' => 2022,
                'volume_m3' => 2700,
                'summary' => $this->t(
                    'شیب‌بندی و عایق‌کاری هم‌زمان بام ۱۴٬۰۰۰ متر مربعی، بدون افزودن بار به سقف موجود.',
                    'Falls and insulation in one layer across a 14,000 m² roof, adding no load to the existing slab.',
                    'ميول وعزل في طبقة واحدة على سطح ١٤٬٠٠٠ م²، دون إضافة حمل على البلاطة القائمة.',
                ),
                'body' => $this->t(
                    'بازسازی بام یک ساختمان درمانی در حال بهره‌برداری، با محدودیت بار و محدودیت زمان اجرا. شیب‌بندی خشک با سبکدانه امکان اجرای مرحله‌ای و تحویل بخش‌به‌بخش بام را فراهم کرد.',
                    'Re-roofing a working hospital, constrained on both load and programme. A dry-laid expanded clay screed allowed the roof to be done in phases and handed back section by section.',
                    'إعادة تسقيف مستشفى قيد التشغيل، بقيود على الحمل وعلى البرنامج الزمني معاً. وأتاح ميل جاف من الطين الممدد تنفيذ السطح على مراحل وتسليمه قطاعاً بعد قطاع.',
                ),
                'scope' => [
                    $this->t('تأمین ۲٬۷۰۰ متر مکعب سبکدانه شیب‌بندی', 'Supply of 2,700 m³ screed grade', 'توريد ٢٬٧٠٠ م³ من درجة الميول'),
                    $this->t('تحویل مرحله‌ای هماهنگ با فازبندی اجرا', 'Phased delivery matched to the construction sequence', 'توريد مرحلي متوافق مع تسلسل التنفيذ'),
                ],
                'products' => ['leca-fill-3-10'],
                'applications' => ['roof-screed-and-floors'],
            ],

            [
                'slug' => 'basra-precast-block-plant',
                'title' => $this->t(
                    'کارخانه بلوک سبک بصره',
                    'Lightweight block plant, Basra',
                    'مصنع بلوك خفيف الوزن، البصرة',
                ),
                'client' => $this->t('تولیدکننده مصالح ساختمانی', 'Building materials manufacturer', 'شركة مواد بناء'),
                'location' => $this->t('بصره، عراق', 'Basra, Iraq', 'البصرة، العراق'),
                'country_code' => 'IQ',
                'year' => 2024,
                'volume_m3' => 26000,
                'summary' => $this->t(
                    'تأمین سالانه ریزدانه برای خط تولید بلوک سبک غیرباربر.',
                    'Annual supply of fine aggregate to a non-loadbearing lightweight block line.',
                    'توريد سنوي لركام ناعم لخط إنتاج بلوك خفيف غير حامل.',
                ),
                'body' => $this->t(
                    'ثبات دانه‌بندی در تولید بلوک، مستقیماً روی وزن و مقاومت محصول نهایی اثر می‌گذارد. قرارداد تأمین بر پایه محدوده رواداری توافق‌شده و آزمون هر محموله بسته شد.',
                    'In block production, consistent grading feeds straight through to the weight and strength of the finished unit. The supply agreement was written around an agreed tolerance band with every consignment tested.',
                    'في إنتاج البلوك، ينعكس ثبات التدرّج مباشرة على وزن الوحدة النهائية ومقاومتها. وصيغ عقد التوريد حول نطاق تفاوت متفق عليه مع اختبار كل حمولة.',
                ),
                'scope' => [
                    $this->t('تأمین سالانه ۲۶٬۰۰۰ متر مکعب ریزدانه', 'Annual supply of 26,000 m³ fine fraction', 'توريد سنوي ٢٦٬٠٠٠ م³ من المقاس الناعم'),
                    $this->t('تحویل EXW با بارگیری فله', 'EXW delivery, bulk loaded', 'تسليم EXW بتحميل سائب'),
                ],
                'products' => ['leca-fill-0-3'],
                'applications' => ['roof-screed-and-floors', 'thermal-insulation'],
            ],

            [
                'slug' => 'qom-greenhouse-hydroponics',
                'title' => $this->t(
                    'گلخانه هیدروپونیک قم',
                    'Hydroponic greenhouse, Qom',
                    'بيت زجاجي مائي، قم',
                ),
                'client' => $this->t('مجتمع کشت و صنعت', 'Agro-industrial complex', 'مجمع زراعي صناعي'),
                'location' => $this->t('قم، ایران', 'Qom, Iran', 'قم، إيران'),
                'country_code' => 'IR',
                'year' => 2025,
                'volume_m3' => 1400,
                'summary' => $this->t(
                    'بستر کشت هیدروپونیک برای ۶ هکتار گلخانه گوجه و خیار.',
                    'Hydroponic growing medium for six hectares of tomato and cucumber glasshouse.',
                    'وسط زراعة مائية لستة هكتارات من بيوت الطماطم والخيار.',
                ),
                'body' => $this->t(
                    'بستر شسته با pH خنثی انتخاب شد تا محلول غذایی بدون تنظیم اضافی قابل کنترل بماند. بستر پس از هر دوره کشت شسته و ضدعفونی و دوباره استفاده می‌شود.',
                    'A washed, pH-neutral medium was specified so the nutrient solution stays controllable without additional correction. The medium is washed, sterilised and reused after each crop cycle.',
                    'حُدّد وسط مغسول بحموضة متعادلة ليبقى المحلول المغذي قابلاً للضبط دون تصحيح إضافي. ويُغسل الوسط ويُعقَّم ويُعاد استخدامه بعد كل دورة زراعية.',
                ),
                'scope' => [
                    $this->t('تأمین ۱٬۴۰۰ متر مکعب لیکای شسته باغبانی', 'Supply of 1,400 m³ washed horticultural grade', 'توريد ١٬٤٠٠ م³ من الدرجة البستانية المغسولة'),
                    $this->t('بسته‌بندی بیگ‌بگ برای انتقال درون گلخانه', 'Big-bag packing for in-house handling', 'تعبئة بأكياس كبيرة للمناولة الداخلية'),
                ],
                'products' => ['leca-garden-4-10'],
                'applications' => ['horticulture-hydroponics'],
            ],
        ];
    }
}

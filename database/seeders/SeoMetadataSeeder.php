<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Support\Navigation;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * The SEO title and description fields in the panel, filled in.
 *
 *     php artisan db:seed --class=SeoMetadataSeeder
 *
 * Every controller already falls back to the record's own name and summary
 * when these are empty, so nothing was broken — but a fallback title is the
 * name of the thing ("لیکا سازه‌ای ۴–۱۰") and a search result wants the phrase
 * someone would actually type. That is the whole difference these fill in.
 *
 * Only blanks are written. An editor's own wording is never overwritten, so
 * this is safe to re-run after adding records; the ones already filled in are
 * skipped.
 *
 * Persian numerals are written literally here rather than left to the digit
 * middleware: that rewrites text between tags, and a meta description lives
 * inside one, so a Latin "4–10" typed here would reach Google as "4–10".
 */
class SeoMetadataSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $filled = 0;

        foreach ($this->metadata() as $class => $records) {
            foreach ($records as $slug => [$title, $description]) {
                $model = $class::where('slug', $slug)->first();

                if (! $model) {
                    $this->command?->warn("No {$class} with slug {$slug} — skipped.");

                    continue;
                }

                $filled += (int) $this->fill($model, $title, $description);
            }
        }

        Navigation::flush();

        $this->command?->info("Filled the SEO fields on {$filled} records.");
    }

    /**
     * @param  array{fa: string, en: string, ar: string}  $title
     * @param  array{fa: string, en: string, ar: string}  $description
     */
    private function fill(Model $model, array $title, array $description): bool
    {
        $wrote = false;

        foreach (['meta_title' => $title, 'meta_description' => $description] as $field => $values) {
            foreach ($values as $locale => $value) {
                if (filled($model->getTranslation($field, $locale))) {
                    continue;
                }

                $model->setTranslation($field, $locale, $value);
                $wrote = true;
            }
        }

        if ($wrote) {
            $model->save();
        }

        return $wrote;
    }

    /** @return array<class-string, array<string, array{0: array<string,string>, 1: array<string,string>}>> */
    private function metadata(): array
    {
        return [
            Product::class => [
                'leca-structure-4-10' => [
                    $this->t(
                        'سبکدانه لیکا ۴–۱۰ میلی‌متر برای بتن سبک سازه‌ای',
                        'Expanded clay 4–10 mm for structural lightweight concrete',
                        'ركام الطين الممدد ٤–١٠ مم للخرسانة الإنشائية الخفيفة',
                    ),
                    $this->t(
                        'گرید ۴ تا ۱۰ میلی‌متر با وزن مخصوص انبوه ۳۲۰ تا ۴۰۰ کیلوگرم بر متر مکعب؛ سبکدانه بتن برای مقاطع سازه‌ای و پمپاژ در ارتفاع. استعلام قیمت و خرید.',
                        'A 4–10 mm fraction at 320–400 kg/m³ bulk density: the aggregate for structural sections and for pumping to height. Datasheet and price on request.',
                        'مقاس ٤–١٠ مم بكثافة ظاهرية ٣٢٠–٤٠٠ كغ/م³: الركام للمقاطع الإنشائية وللضخ إلى الارتفاعات. ورقة البيانات والسعر عند الطلب.',
                    ),
                ],
                'leca-structure-10-20' => [
                    $this->t(
                        'سبکدانه لیکا ۱۰–۲۰ میلی‌متر؛ سبک‌ترین گرید سازه‌ای',
                        'Expanded clay 10–20 mm: the lightest structural grade',
                        'ركام الطين الممدد ١٠–٢٠ مم: أخف الدرجات الإنشائية',
                    ),
                    $this->t(
                        'گرید ۱۰ تا ۲۰ میلی‌متر با وزن مخصوص انبوه ۲۶۰ تا ۳۲۰ کیلوگرم بر متر مکعب؛ بیشترین کاهش وزن در بتن سبک لیکا و پرکردن حجم. قیمت و دیتاشیت.',
                        'A 10–20 mm fraction at 260–320 kg/m³: the largest weight saving in lightweight concrete, and the grade for filling volume. Datasheet and price.',
                        'مقاس ١٠–٢٠ مم بكثافة ٢٦٠–٣٢٠ كغ/م³: أكبر توفير في الوزن داخل الخرسانة الخفيفة، ودرجة ملء الحجم. ورقة البيانات والسعر.',
                    ),
                ],
                'leca-fill-0-3' => [
                    $this->t(
                        'لیکا ریزدانه ۰–۳ میلی‌متر؛ جایگزین ماسه در ملات سبک',
                        'Expanded clay 0–3 mm: a sand replacement for light mortars',
                        'ركام الطين الممدد ٠–٣ مم: بديل الرمل في المونة الخفيفة',
                    ),
                    $this->t(
                        'دانه لیکا در بازه ۰ تا ۳ میلی‌متر با وزن مخصوص ۵۵۰ تا ۶۵۰ کیلوگرم بر متر مکعب؛ جای ماسه را در ملات و لایه رویه می‌گیرد، نه جای سنگدانه درشت.',
                        'The 0–3 mm fraction at 550–650 kg/m³ takes the place of sand in mortars and toppings — not the place of coarse aggregate.',
                        'المقاس ٠–٣ مم بكثافة ٥٥٠–٦٥٠ كغ/م³ يحل محل الرمل في المونة والطبقات العلوية، لا محل الركام الخشن.',
                    ),
                ],
                'leca-fill-3-10' => [
                    $this->t(
                        'سبکدانه لیکا ۳–۱۰ میلی‌متر برای شیب‌بندی و کف‌سازی',
                        'Expanded clay 3–10 mm for screeds and levelling',
                        'ركام الطين الممدد ٣–١٠ مم للميول والتسوية',
                    ),
                    $this->t(
                        'گرید ۳ تا ۱۰ میلی‌متر با وزن مخصوص ۳۸۰ تا ۴۵۰ کیلوگرم بر متر مکعب؛ سبک می‌ماند و سطحش زیر ماله بسته می‌شود. مصالح سبک ساختمانی برای بام و کف.',
                        'A 3–10 mm fraction at 380–450 kg/m³: light enough to matter, fine enough to close under a float. The grade for roof falls and floor levelling.',
                        'مقاس ٣–١٠ مم بكثافة ٣٨٠–٤٥٠ كغ/م³: خفيف بما يكفي، وناعم بما يُغلق تحت المسطرين. درجة ميول الأسطح وتسوية الأرضيات.',
                    ),
                ],
                'leca-infra-10-30' => [
                    $this->t(
                        'سبکدانه لیکا ۱۰–۳۰ میلی‌متر؛ پرکننده ژئوتکنیکی',
                        'Expanded clay 10–30 mm: geotechnical lightweight fill',
                        'ركام الطين الممدد ١٠–٣٠ مم: ردم جيوتقني خفيف',
                    ),
                    $this->t(
                        'درشت‌ترین گرید، ۱۰ تا ۳۰ میلی‌متر با وزن مخصوص ۲۵۰ تا ۳۰۰ کیلوگرم بر متر مکعب؛ خاکریز سبک روی خاک نرم و پرکننده پشت دیوار حائل.',
                        'The coarsest grade, 10–30 mm at 250–300 kg/m³: lightweight embankment over soft ground and backfill behind retaining walls.',
                        'أخشن الدرجات، ١٠–٣٠ مم بكثافة ٢٥٠–٣٠٠ كغ/م³: ردم خفيف فوق التربة الطرية وخلف الجدران الساندة.',
                    ),
                ],
                'leca-garden-4-10' => [
                    $this->t(
                        'لیکا باغبانی ۴–۱۰ میلی‌متر؛ بستر کشت و هیدروپونیک',
                        'Horticultural expanded clay 4–10 mm for growing media',
                        'ركام الطين الممدد للزراعة ٤–١٠ مم لأوساط النمو',
                    ),
                    $this->t(
                        'گرید شسته و غربال‌شده ۴ تا ۱۰ میلی‌متر با وزن مخصوص ۳۰۰ تا ۳۸۰ کیلوگرم بر متر مکعب؛ بستر خنثی، عاری از بیماری و قابل استفاده مجدد.',
                        'A washed and screened 4–10 mm fraction at 300–380 kg/m³: an inert, disease-free medium that can be reused.',
                        'مقاس مغسول ومغربل ٤–١٠ مم بكثافة ٣٠٠–٣٨٠ كغ/م³: وسط خامل خالٍ من الأمراض وقابل لإعادة الاستخدام.',
                    ),
                ],
                'leca-greenroof-8-16' => [
                    $this->t(
                        'لیکا بام سبز ۸–۱۶ میلی‌متر؛ لایه زهکش و بستر کشت',
                        'Green-roof expanded clay 8–16 mm: drainage and substrate',
                        'ركام الطين الممدد للأسطح الخضراء ٨–١٦ مم: تصريف ووسط',
                    ),
                    $this->t(
                        'گرید ۸ تا ۱۶ میلی‌متر با وزن مخصوص ۲۸۰ تا ۳۴۰ کیلوگرم بر متر مکعب؛ لایه زهکش بام سبز با کمترین وزن اشباع بر سقف.',
                        'An 8–16 mm fraction at 280–340 kg/m³: the drainage layer of a green roof, at the lowest saturated weight on the slab.',
                        'مقاس ٨–١٦ مم بكثافة ٢٨٠–٣٤٠ كغ/م³: طبقة تصريف السطح الأخضر بأدنى وزن مُشبع على البلاطة.',
                    ),
                ],
            ],

            ProductCategory::class => [
                'structural' => [
                    $this->t(
                        'سبکدانه سازه‌ای لیکا؛ گریدهای بتن سبک باربر',
                        'Structural grades for load-bearing lightweight concrete',
                        'الدرجات الإنشائية للخرسانة الخفيفة الحاملة',
                    ),
                    $this->t(
                        'گریدهای درشت‌دانه سبکدانه لیکا برای بتن سبک سازه‌ای، با تعادل میان کاهش وزن و مقاومت فشاری. دانه‌بندی، وزن مخصوص و دیتاشیت هر گرید.',
                        'The coarse fractions for structural lightweight concrete, balancing weight saving against crushing strength. Grading, density and datasheet per grade.',
                        'المقاسات الخشنة للخرسانة الإنشائية الخفيفة، بموازنة بين توفير الوزن ومقاومة التكسير. التدرّج والكثافة وورقة البيانات لكل درجة.',
                    ),
                ],
                'fill-and-screed' => [
                    $this->t(
                        'سبکدانه شیب‌بندی و پرکننده؛ گریدهای کف‌سازی سبک',
                        'Fill and screed grades for light floors and falls',
                        'درجات الردم والميول للأرضيات الخفيفة',
                    ),
                    $this->t(
                        'گریدهای ریزدانه برای شیب‌بندی بام، کف‌سازی سبک و پرکردن حفرات؛ مصالح سبک ساختمانی با عایق حرارتی پیوسته و بدون بار اضافی بر سقف.',
                        'The fine fractions for roof falls, light floor build-ups and filling voids: continuous thermal insulation without added load on the slab.',
                        'المقاسات الناعمة لميول الأسطح والأرضيات الخفيفة وملء الفراغات: عزل حراري متصل دون حمل إضافي على البلاطة.',
                    ),
                ],
                'geotechnical' => [
                    $this->t(
                        'سبکدانه ژئوتکنیک و راه؛ خاکریز سبک و پرکننده',
                        'Geotechnical grades: lightweight embankment and backfill',
                        'الدرجات الجيوتقنية: الردم الخفيف وخلف الجدران',
                    ),
                    $this->t(
                        'گرید درشت لیکا برای پرکننده سبک پشت دیوار حائل، خاکریز راه و بستر ابنیه؛ حذف نشست روی خاک نرم و کاهش فشار جانبی.',
                        'The coarse grade for lightweight backfill behind retaining walls, road embankment and sub-structures: settlement on soft ground removed, lateral pressure cut.',
                        'الدرجة الخشنة للردم الخفيف خلف الجدران الساندة وردميات الطرق وأسس المنشآت: إزالة الهبوط فوق التربة الطرية وخفض الضغط الجانبي.',
                    ),
                ],
                'horticulture' => [
                    $this->t(
                        'لیکا باغبانی و بام سبز؛ بستر کشت و زهکش',
                        'Horticulture and green-roof grades: media and drainage',
                        'درجات الزراعة والأسطح الخضراء: الأوساط والتصريف',
                    ),
                    $this->t(
                        'گریدهای شسته و غربال‌شده برای بستر کشت، زهکش بام سبز و هیدروپونیک؛ خنثی، عاری از بیماری و قابل شست‌وشو و استفاده مجدد.',
                        'Washed and screened grades for growing media, green-roof drainage and hydroponics: inert, disease-free, and reusable after rinsing.',
                        'درجات مغسولة ومغربلة لأوساط النمو وتصريف الأسطح الخضراء والزراعة المائية: خاملة وخالية من الأمراض وقابلة لإعادة الاستخدام.',
                    ),
                ],
            ],

            Application::class => [
                'structural-lightweight-concrete' => [
                    $this->t(
                        'بتن سبک سازه‌ای با سبکدانه لیکا؛ گرید و مزیت',
                        'Structural lightweight concrete with expanded clay',
                        'الخرسانة الإنشائية الخفيفة بركام الطين الممدد',
                    ),
                    $this->t(
                        'کاهش ۲۵ تا ۳۵ درصدی وزن بتن در همان رده مقاومتی، با اثر مستقیم بر ابعاد فونداسیون و بار لرزه‌ای. گرید پیشنهادی سبکدانه بتن و نکات اجرا.',
                        'A 25 to 35 per cent cut in concrete weight at the same strength class, with direct effect on foundation sizing and seismic load. Recommended grade and site notes.',
                        'خفض وزن الخرسانة ٢٥ إلى ٣٥٪ عند رتبة المقاومة نفسها، بأثر مباشر على أبعاد الأساسات والحمل الزلزالي. الدرجة الموصى بها وملاحظات التنفيذ.',
                    ),
                ],
                'roof-screed-and-floors' => [
                    $this->t(
                        'شیب‌بندی بام و کف‌سازی با سبکدانه لیکا',
                        'Roof falls and floor build-ups with expanded clay',
                        'ميول الأسطح وتسوية الأرضيات بركام الطين الممدد',
                    ),
                    $this->t(
                        'شیب‌بندی سبک با عایق حرارتی پیوسته و بدون بار اضافی بر سقف؛ گرید پیشنهادی، ضخامت لایه و محاسبه مصرف در هر متر مربع.',
                        'Light falls with continuous thermal insulation and no added load on the slab: recommended grade, layer thickness and consumption per square metre.',
                        'ميول خفيفة بعزل حراري متصل ودون حمل إضافي على البلاطة: الدرجة الموصى بها وسماكة الطبقة والاستهلاك للمتر المربع.',
                    ),
                ],
                'geotechnical-fill' => [
                    $this->t(
                        'پرکننده ژئوتکنیکی سبک؛ خاکریز روی خاک نرم',
                        'Geotechnical lightweight fill over soft ground',
                        'الردم الجيوتقني الخفيف فوق التربة الطرية',
                    ),
                    $this->t(
                        'خاکریز سبک روی خاک نرم و پرکننده پشت دیوار حائل، برای حذف نشست و کاهش فشار جانبی؛ گرید ۱۰ تا ۳۰ میلی‌متر و نکات اجرا.',
                        'Lightweight embankment over soft ground and backfill behind retaining walls, to remove settlement and cut lateral pressure: the 10–30 mm grade and how to place it.',
                        'ردم خفيف فوق التربة الطرية وخلف الجدران الساندة لإزالة الهبوط وخفض الضغط الجانبي: المقاس ١٠–٣٠ مم وطريقة التنفيذ.',
                    ),
                ],
                'green-roofs' => [
                    $this->t(
                        'بام سبز با لیکا؛ لایه زهکش و بودجه بار',
                        'Green roofs: expanded clay drainage and load budget',
                        'الأسطح الخضراء: تصريف الطين الممدد وميزانية الحمل',
                    ),
                    $this->t(
                        'لایه زهکش و بخشی از بستر کشت، با کمترین وزن اشباع؛ عددهایی که بودجه بار بام را تعیین می‌کنند و گرید پیشنهادی برای هر ضخامت.',
                        'The drainage layer and part of the substrate, at the lowest saturated weight: the numbers that set a roof load budget, and the grade for each depth.',
                        'طبقة التصريف وجزء من الوسط الزراعي بأدنى وزن مُشبع: الأرقام التي تحدد ميزانية حمل السطح، والدرجة المناسبة لكل سماكة.',
                    ),
                ],
                'horticulture-hydroponics' => [
                    $this->t(
                        'بستر کشت لیکا برای گلخانه و هیدروپونیک',
                        'Expanded clay growing media for glasshouse and hydroponics',
                        'أوساط نمو من الطين الممدد للبيوت المحمية والزراعة المائية',
                    ),
                    $this->t(
                        'بستر کشت خنثی، عاری از بیماری و قابل استفاده مجدد؛ تهویه ریشه، نگهداشت رطوبت و شست‌وشوی بین دو دوره کشت.',
                        'An inert, disease-free and reusable medium: root aeration, moisture retention, and rinsing between one crop and the next.',
                        'وسط خامل خالٍ من الأمراض وقابل لإعادة الاستخدام: تهوية الجذور واحتجاز الرطوبة والغسل بين دورتي زراعة.',
                    ),
                ],
                'thermal-insulation' => [
                    $this->t(
                        'عایق‌کاری حرارتی با سبکدانه معدنی و غیرقابل اشتعال',
                        'Thermal insulation with a mineral, non-combustible aggregate',
                        'العزل الحراري بركام معدني غير قابل للاشتعال',
                    ),
                    $this->t(
                        'عایق معدنی، غیرقابل اشتعال و بدون افت عملکرد در طول عمر ساختمان؛ ضریب هدایت حرارتی، ضخامت لایه و جزئیات اجرا در دیوار و کف.',
                        'A mineral insulant, non-combustible and with no loss of performance over the life of the building: conductivity, layer thickness and details for walls and floors.',
                        'عازل معدني غير قابل للاشتعال ولا يفقد أداءه طوال عمر المبنى: معامل التوصيل وسماكة الطبقة وتفاصيل الجدران والأرضيات.',
                    ),
                ],
                'water-filtration' => [
                    $this->t(
                        'فیلتراسیون و تصفیه؛ بستر فیلتر و حامل بیوفیلم',
                        'Filtration: filter bed and biofilm carrier',
                        'الترشيح: وسط الترشيح وحامل الغشاء الحيوي',
                    ),
                    $this->t(
                        'بستر فیلتر و حامل بیوفیلم در تصفیه فاضلاب و سیستم‌های زهکشی؛ سطح ویژه بالا، پایداری شیمیایی و مقاومت در برابر گرفتگی.',
                        'A filter bed and biofilm carrier in wastewater treatment and drainage systems: high specific surface, chemical stability and resistance to blinding.',
                        'وسط ترشيح وحامل غشاء حيوي في معالجة الصرف وأنظمة التصريف: مساحة نوعية عالية وثبات كيميائي ومقاومة للانسداد.',
                    ),
                ],
            ],

            Project::class => [
                'tehran-residential-tower-vanak' => [
                    $this->t(
                        'برج مسکونی ۲۸ طبقه ونک تهران؛ بتن سبک لیکا',
                        'A 28-storey residential tower in Vanak, Tehran',
                        'برج سكني من ٢٨ طابقاً في ونك بطهران',
                    ),
                    $this->t(
                        'تأمین ۱۸٬۵۰۰ متر مکعب سبکدانه لیکا برای بتن سبک دال تمام طبقات؛ کاهش بار لرزه‌ای و ابعاد فونداسیون. تهران، ۱۴۰۳.',
                        '18,500 m³ of expanded clay aggregate for lightweight slab concrete on every floor: seismic load and foundation sizing both reduced. Tehran, 2024.',
                        'توريد ١٨٬٥٠٠ م³ من ركام الطين الممدد لخرسانة البلاطات الخفيفة في كل الطوابق: خفض الحمل الزلزالي وأبعاد الأساسات. طهران، ٢٠٢٤.',
                    ),
                ],
                'bandar-abbas-port-embankment' => [
                    $this->t(
                        'خاکریز سبک محوطه بندری بندرعباس؛ ۴۲ هزار متر مکعب',
                        'Lightweight embankment at Bandar Abbas port',
                        'ردم خفيف في ميناء بندر عباس',
                    ),
                    $this->t(
                        'تأمین ۴۲٬۰۰۰ متر مکعب پرکننده ژئوتکنیکی برای محوطه بندری روی خاک نرم ساحلی؛ حذف نشست بلندمدت. هرمزگان، ۱۴۰۲.',
                        '42,000 m³ of geotechnical fill for a port apron on soft coastal ground, removing long-term settlement. Hormozgan, 2023.',
                        'توريد ٤٢٬٠٠٠ م³ من الردم الجيوتقني لساحة ميناء فوق تربة ساحلية طرية، لإزالة الهبوط بعيد المدى. هرمزكان، ٢٠٢٣.',
                    ),
                ],
                'doha-office-green-roof' => [
                    $this->t(
                        'بام سبز مجتمع اداری دوحه؛ لایه زهکش لیکا',
                        'Green roof on an office complex in Doha',
                        'سطح أخضر لمجمع مكاتب في الدوحة',
                    ),
                    $this->t(
                        'تأمین ۳٬۲۰۰ متر مکعب لیکا برای لایه زهکش و بستر کشت بام سبز، با کمترین وزن اشباع بر سازه. دوحه، قطر، ۱۴۰۲.',
                        '3,200 m³ of expanded clay for the drainage layer and substrate of a green roof, at the lowest saturated weight on the structure. Doha, 2023.',
                        'توريد ٣٬٢٠٠ م³ من الطين الممدد لطبقة التصريف والوسط الزراعي لسطح أخضر، بأدنى وزن مُشبع على المنشأ. الدوحة، ٢٠٢٣.',
                    ),
                ],
                'isfahan-hospital-roof-screed' => [
                    $this->t(
                        'شیب‌بندی بام بیمارستان اصفهان با سبکدانه',
                        'Roof screed on a hospital in Isfahan',
                        'ميول سطح مستشفى في أصفهان',
                    ),
                    $this->t(
                        'تأمین ۲٬۷۰۰ متر مکعب سبکدانه برای شیب‌بندی و عایق حرارتی پیوسته بام، بدون افزودن بار به سقف موجود. اصفهان، ۱۴۰۱.',
                        '2,700 m³ of lightweight aggregate for roof falls and continuous thermal insulation, with no load added to the existing slab. Isfahan, 2022.',
                        'توريد ٢٬٧٠٠ م³ من الركام الخفيف لميول السطح والعزل الحراري المتصل، دون إضافة حمل على البلاطة القائمة. أصفهان، ٢٠٢٢.',
                    ),
                ],
                'basra-precast-block-plant' => [
                    $this->t(
                        'کارخانه بلوک سبک بصره؛ تأمین مستمر سبکدانه',
                        'A lightweight block plant in Basra',
                        'مصنع بلوك خفيف في البصرة',
                    ),
                    $this->t(
                        'تأمین ۲۶٬۰۰۰ متر مکعب سبکدانه لیکا برای تولید پیوسته بلوک سبک، با دانه‌بندی تکرارپذیر در هر محموله. بصره، عراق، ۱۴۰۳.',
                        '26,000 m³ of expanded clay for continuous lightweight block production, with the same grading in every consignment. Basra, 2024.',
                        'توريد ٢٦٬٠٠٠ م³ من الطين الممدد لإنتاج البلوك الخفيف باستمرار، بالتدرّج نفسه في كل شحنة. البصرة، ٢٠٢٤.',
                    ),
                ],
                'qom-greenhouse-hydroponics' => [
                    $this->t(
                        'گلخانه هیدروپونیک قم؛ بستر کشت لیکا',
                        'A hydroponic glasshouse in Qom',
                        'بيت محمي مائي في قم',
                    ),
                    $this->t(
                        'تأمین ۱٬۴۰۰ متر مکعب لیکا باغبانی شسته برای بستر کشت هیدروپونیک، با امکان شست‌وشو و استفاده مجدد بین دوره‌ها. قم، ۱۴۰۴.',
                        '1,400 m³ of washed horticultural grade as a hydroponic growing medium, rinsed and reused between crops. Qom, 2025.',
                        'توريد ١٬٤٠٠ م³ من الدرجة الزراعية المغسولة كوسط نمو مائي، يُغسل ويُعاد استخدامه بين الدورات. قم، ٢٠٢٥.',
                    ),
                ],
            ],

            Page::class => [
                'careers' => [
                    $this->t(
                        'فرصت‌های شغلی آرتا لیکا؛ استخدام در کارخانه قم',
                        'Careers at Arta Leca',
                        'الوظائف في آرتا ليكا',
                    ),
                    $this->t(
                        'کارخانه‌ای که سه شیفت کار می‌کند همیشه به آدم‌های دقیق نیاز دارد. موقعیت‌های تولید، کنترل کیفیت، فروش و لجستیک در شهرک صنعتی محمودآباد قم.',
                        'A plant running three shifts always needs careful people. Openings in production, quality control, sales and logistics at the Mahmoudabad Industrial Zone in Qom.',
                        'مصنع يعمل بثلاث ورديات يحتاج دائماً إلى أشخاص دقيقين. شواغر في الإنتاج وضبط الجودة والمبيعات واللوجستيات في قم.',
                    ),
                ],
                'sales-terms' => [
                    $this->t(
                        'شرایط عمومی فروش سبکدانه لیکا؛ تحویل و پرداخت',
                        'General conditions of sale',
                        'الشروط العامة للبيع',
                    ),
                    $this->t(
                        'شرایطی که مبنای هر پیش‌فاکتور و قرارداد تأمین قرار می‌گیرد: واحد اندازه‌گیری، بسته‌بندی، شرایط تحویل و پرداخت، و مسئولیت کسری و خسارت.',
                        'The terms behind every proforma and supply contract: unit of measure, packaging, delivery and payment terms, and liability for shortfall and damage.',
                        'الشروط التي يقوم عليها كل عرض سعر وعقد توريد: وحدة القياس والتعبئة وشروط التسليم والدفع والمسؤولية عن النقص والضرر.',
                    ),
                ],
            ],

            Post::class => [
                'lightweight-concrete-mix-design-basics' => [
                    $this->t(
                        'طرح اختلاط بتن سبک لیکا؛ چهار اشتباه رایج',
                        'Lightweight concrete mix design: four recurring mistakes',
                        'تصميم خلطات الخرسانة الخفيفة: أربعة أخطاء',
                    ),
                    $this->t(
                        'بیشتر مشکلات بتن سبک در محل اجرا ریشه در طراحی مخلوط دارد نه در خود سبکدانه: طراحی وزنی به‌جای حجمی، جذب آب، ویبره بیش از حد و عمل‌آوری ناکافی.',
                        'Most lightweight concrete problems on site start in the mix design, not in the aggregate: proportioning by weight, absorption, over-vibration and thin curing.',
                        'تبدأ معظم مشكلات الخرسانة الخفيفة من تصميم الخلطة لا من الركام: التنسيب بالوزن والامتصاص والإفراط في الهزّ وضعف المعالجة.',
                    ),
                ],
                'choosing-the-right-grain-size' => [
                    $this->t(
                        'انتخاب دانه‌بندی سبکدانه لیکا؛ یک معاوضه ساده',
                        'Choosing a fraction: one simple trade-off',
                        'اختيار المقاس: مقايضة واحدة بسيطة',
                    ),
                    $this->t(
                        'هرچه دانه لیکا درشت‌تر، سبک‌تر و ضعیف‌تر. تمام انتخاب گرید در همین جمله خلاصه می‌شود — و در اینکه کدام سر این معاوضه به کار شما می‌خورد.',
                        'The coarser the granule, the lighter and the weaker. The whole of grade selection is in that sentence — and in which end of the trade-off your job needs.',
                        'كلما خشُنت الحبيبة خفّت وضعُفت. اختيار الدرجة كله في هذه الجملة، وفي أي طرف من المقايضة يحتاجه عملك.',
                    ),
                ],
                'third-kiln-line-commissioned' => [
                    $this->t(
                        'خط سوم کوره دوار آرتا لیکا وارد مدار شد',
                        'The third rotary kiln line comes on stream',
                        'دخول خط الفرن الدوّار الثالث الخدمة',
                    ),
                    $this->t(
                        'با راه‌اندازی خط سوم، ظرفیت تولید سبکدانه لیکا به ۴۵۰٬۰۰۰ متر مکعب در سال رسید و زمان تحویل سفارش‌های حجیم کوتاه‌تر شد.',
                        'With the third line running, annual capacity reaches 450,000 m³ and lead times on large orders come down.',
                        'بتشغيل الخط الثالث بلغت الطاقة السنوية ٤٥٠٬٠٠٠ م³ وقصُرت مهل التسليم للطلبات الكبيرة.',
                    ),
                ],
                'green-roof-load-budget' => [
                    $this->t(
                        'بودجه بار در بام سبز؛ یک نمونه واقعی با عدد',
                        'A green-roof load budget, worked through',
                        'ميزانية حمل سطح أخضر، محسوبة بالكامل',
                    ),
                    $this->t(
                        'وزن اشباع لایه زهکش، بستر کشت و پوشش گیاهی روی یک بام واقعی، لایه به لایه — و جایی که انتخاب گرید لیکا بودجه بار را نجات می‌دهد.',
                        'The saturated weight of drainage, substrate and planting on a real roof, layer by layer — and where the choice of grade saves the load budget.',
                        'الوزن المُشبع للتصريف والوسط والغطاء النباتي على سطح حقيقي، طبقة طبقة، وأين يُنقذ اختيار الدرجة ميزانية الحمل.',
                    ),
                ],
                'export-shipments-reach-fourteen-countries' => [
                    $this->t(
                        'صادرات سبکدانه لیکا به چهاردهمین کشور رسید',
                        'Exports reach a fourteenth destination',
                        'وصول الصادرات إلى البلد الرابع عشر',
                    ),
                    $this->t(
                        'با نخستین محموله به مقصد تازه، شمار کشورهای مقصد صادرات آرتا لیکا به چهارده رسید؛ تحویل فله و بیگ‌بگ در شرایط FOB و CIF.',
                        'With a first shipment to a new market, Arta Leca now exports to fourteen countries, in bulk and big bags on FOB and CIF terms.',
                        'مع أول شحنة إلى سوق جديدة، صارت آرتا ليكا تصدّر إلى أربعة عشر بلداً، سائباً وبأكياس كبيرة بشروط FOB وCIF.',
                    ),
                ],
            ],
        ];
    }
}

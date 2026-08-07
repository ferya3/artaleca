<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Post;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class EditorialSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $this->posts();
        $this->downloads();
        $this->certificates();
        $this->faqs();
    }

    private function posts(): void
    {
        $posts = [
            [
                'slug' => 'lightweight-concrete-mix-design-basics',
                'type' => 'article',
                'reading_minutes' => 7,
                'published_at' => now()->subDays(9),
                'is_featured' => true,
                'title' => $this->t(
                    'طرح اختلاط بتن سبک: چهار اشتباه رایج',
                    'Lightweight concrete mix design: four recurring mistakes',
                    'تصميم خلطات الخرسانة خفيفة الوزن: أربعة أخطاء متكررة',
                ),
                'excerpt' => $this->t(
                    'بیشتر مشکلات بتن سبک در محل اجرا، ریشه در طراحی مخلوط دارد نه در خود سبکدانه.',
                    'Most lightweight concrete problems on site start in the mix design, not in the aggregate.',
                    'تبدأ معظم مشكلات الخرسانة خفيفة الوزن في الموقع من تصميم الخلطة لا من الركام نفسه.',
                ),
                'body' => $this->t(
                    "۱. طراحی بر مبنای وزن به‌جای حجم. سبکدانه چگالی متفاوتی با سنگدانه معمولی دارد؛ نسبت‌های وزنی مرسوم، حجم واقعی سنگدانه در مخلوط را جابه‌جا می‌کند. طرح اختلاط بتن سبک باید حجمی بسته شود.\n\n۲. نادیده گرفتن جذب آب. سبکدانه خشک بخشی از آب اختلاط را جذب می‌کند و اسلامپ در حین حمل افت می‌کند. پیش‌اشباع ۳۰ دقیقه‌ای، این افت را حذف می‌کند.\n\n۳. ویبره بیش از حد. چگالی کمتر دانه یعنی تمایل به بالا آمدن. ویبره طولانی، دانه‌ها را به سطح می‌راند و مقطع را غیریکنواخت می‌کند. زمان ویبره را نسبت به بتن معمولی کوتاه‌تر بگیرید.\n\n۴. عمل‌آوری زودهنگام ناکافی. آب ذخیره‌شده در دانه به عمل‌آوری داخلی کمک می‌کند، اما جایگزین عمل‌آوری سطحی نیست — به‌ویژه در هوای گرم و خشک.",
                    "1. Designing by weight instead of by volume. Expanded clay has a different density from normal aggregate, so conventional weight ratios shift the actual aggregate volume in the mix. Lightweight mixes must be proportioned by volume.\n\n2. Ignoring absorption. Dry aggregate takes up part of the mixing water and slump falls in transit. A 30-minute pre-soak removes that drift.\n\n3. Over-vibration. Lower particle density means the aggregate wants to rise. Prolonged vibration drives it to the surface and leaves the section non-uniform. Vibrate for less time than you would normal-weight concrete.\n\n4. Skimping on early curing. Water held inside the granule assists internal curing, but it does not replace surface curing — least of all in hot, dry weather.",
                    "١. التصميم بالوزن بدل الحجم. تختلف كثافة الطين الممدد عن الركام الاعتيادي، فتزيح النسب الوزنية التقليدية حجم الركام الفعلي في الخلطة. ويجب أن تُنسَّب الخلطات خفيفة الوزن حجمياً.\n\n٢. تجاهل الامتصاص. يمتص الركام الجاف جزءاً من ماء الخلط فينخفض الهبوط أثناء النقل، والنقع المسبق ٣٠ دقيقة يزيل هذا الانحراف.\n\n٣. الإفراط في الهزّ. تعني الكثافة الأدنى للحبيبة ميلها إلى الصعود، فيدفعها الهزّ المطوّل إلى السطح ويترك المقطع غير منتظم. اهزز مدة أقصر مما تفعل مع الخرسانة الاعتيادية.\n\n٤. التقصير في المعالجة المبكرة. يساعد الماء المحتجز داخل الحبيبة على المعالجة الداخلية، لكنه لا يغني عن المعالجة السطحية، وبخاصة في الأجواء الحارة الجافة.",
                ),
            ],
            [
                'slug' => 'choosing-the-right-grain-size',
                'type' => 'article',
                'reading_minutes' => 5,
                'published_at' => now()->subDays(24),
                'title' => $this->t(
                    'انتخاب دانه‌بندی: یک معاوضه ساده',
                    'Choosing a fraction: one simple trade-off',
                    'اختيار المقاس: مقايضة واحدة بسيطة',
                ),
                'excerpt' => $this->t(
                    'هرچه دانه درشت‌تر، سبک‌تر و ضعیف‌تر. تمام انتخاب گرید در همین جمله خلاصه می‌شود.',
                    'The coarser the fraction, the lighter and the weaker. Grade selection is very nearly that one sentence.',
                    'كلما خشن المقاس، خفّ ووَهَن. ويكاد اختيار الدرجة يختصر في هذه الجملة.',
                ),
                'body' => $this->t(
                    "با افزایش اندازه دانه، نسبت تخلخل به ماده جامد بالا می‌رود: دانه سبک‌تر می‌شود و مقاومت فشاری آن پایین می‌آید.\n\nبرای بتن سازه‌ای، دانه‌بندی ۴ تا ۱۰ میلی‌متر معمولاً بهترین تعادل را می‌دهد. برای پرکردن حجم و شیب‌بندی که مقاومت تعیین‌کننده نیست، هرچه درشت‌تر بهتر. برای بلوک و ملات که سطح تمام‌شده اهمیت دارد، ریزدانه.\n\nیک استثنا هست: در کاربردهای زهکشی، اندازه دانه را نفوذپذیری تعیین می‌کند، نه وزن — و آنجا دانه‌بندی یکنواخت مهم‌تر از اندازه مطلق است.",
                    "As the particle gets larger, the ratio of pore space to solid material rises: the granule gets lighter and its crushing resistance falls.\n\nFor structural concrete, 4–10 mm usually strikes the best balance. For bulk fill and screeds, where strength is not the governing criterion, coarser is better. For blocks and mortars, where the finished surface matters, go fine.\n\nOne exception: in drainage applications the size is set by the permeability you need, not by weight — and there a uniform grading matters more than the absolute size.",
                    "كلما كبرت الحبيبة ارتفعت نسبة الفراغات إلى المادة الصلبة: فتخفّ الحبيبة وتقل مقاومتها للتكسير.\n\nللخرسانة الإنشائية، يحقق المقاس ٤–١٠ مم عادةً أفضل توازن. ولأعمال الردم والميول حيث لا تكون المقاومة هي الحاكم، فالأخشن أفضل. وللبلوك والمونة حيث يهم السطح النهائي، اختر الناعم.\n\nاستثناء واحد: في تطبيقات التصريف تحدد النفاذية المطلوبة المقاس لا الوزن، وهناك يهم انتظام التدرّج أكثر من المقاس المطلق.",
                ),
            ],
            [
                'slug' => 'third-kiln-line-commissioned',
                'type' => 'news',
                'reading_minutes' => 2,
                'published_at' => now()->subDays(46),
                'title' => $this->t(
                    'خط سوم کوره دوار وارد مدار تولید شد',
                    'Third rotary kiln line enters production',
                    'دخول خط الفرن الدوّار الثالث الإنتاج',
                ),
                'excerpt' => $this->t(
                    'ظرفیت سالانه به ۴۵۰٬۰۰۰ متر مکعب رسید و سیستم بازیابی حرارت راه‌اندازی شد.',
                    'Annual capacity reaches 450,000 m³, with heat recovery from kiln exhaust now online.',
                    'بلوغ الطاقة السنوية ٤٥٠٬٠٠٠ م³ مع تشغيل استرداد الحرارة من عادم الفرن.',
                ),
                'body' => $this->t(
                    "خط سوم تولید پس از دوره راه‌اندازی و آزمون، به‌طور کامل وارد مدار شد. با این افزایش، ظرفیت اسمی سالانه کارخانه به ۴۵۰٬۰۰۰ متر مکعب رسید.\n\nهم‌زمان، سیستم بازیابی حرارت از گاز خروجی کوره نصب شد که حرارت مورد نیاز مرحله پیش‌گرمایش را تأمین می‌کند و مصرف سوخت را به ازای هر متر مکعب محصول کاهش می‌دهد.",
                    "The third production line has entered full service after its commissioning and test period, taking the plant's nominal annual capacity to 450,000 m³.\n\nA heat recovery system on the kiln exhaust was installed at the same time. It supplies the pre-heating stage and lowers fuel consumption per cubic metre of product.",
                    "دخل خط الإنتاج الثالث الخدمة الكاملة بعد فترة التشغيل التجريبي والاختبار، ليصل بالطاقة الاسمية السنوية للمصنع إلى ٤٥٠٬٠٠٠ متر مكعب.\n\nوركّب في الوقت نفسه نظام لاسترداد الحرارة من عادم الفرن يغذّي مرحلة التسخين المسبق ويخفض استهلاك الوقود لكل متر مكعب من المنتج.",
                ),
            ],
            [
                'slug' => 'green-roof-load-budget',
                'type' => 'case-study',
                'reading_minutes' => 6,
                'published_at' => now()->subDays(68),
                'title' => $this->t(
                    'بودجه بار در بام سبز: یک نمونه واقعی',
                    'The load budget of a green roof: a worked example',
                    'ميزانية الحمل في سطح أخضر: مثال عملي',
                ),
                'excerpt' => $this->t(
                    'وقتی سقف فقط ۱۸۰ کیلوگرم بر متر مربع ظرفیت اضافی دارد، انتخاب لایه زهکش کل طرح را تعیین می‌کند.',
                    'When the slab has only 180 kg/m² to spare, the drainage layer decides the whole build-up.',
                    'حين لا تملك البلاطة سوى ١٨٠ كجم/م² فائضة، تحدد طبقة التصريف كامل التركيب.',
                ),
                'body' => $this->t(
                    "در بازسازی بام یک مجتمع اداری، تحلیل سازه نشان داد تنها ۱۸۰ کیلوگرم بر متر مربع ظرفیت اضافی وجود دارد — و این عدد باید بین لایه زهکش، بستر کشت، گیاه و آب اشباع تقسیم می‌شد.\n\nزهکش شن ۱۰ سانتی‌متری در حالت اشباع حدود ۱۹۰ کیلوگرم بر متر مربع وزن دارد؛ یعنی به‌تنهایی از کل بودجه عبور می‌کرد.\n\nهمان ضخامت با سبکدانه ۸–۱۶، حدود ۶۵ کیلوگرم بر متر مربع. باقی‌مانده بودجه اجازه داد عمق بستر کشت از ۱۲ به ۲۰ سانتی‌متر افزایش یابد و طرح کاشت از پوشش گیاهی خزنده به بوته و درختچه ارتقا پیدا کند.",
                    "In a roof refurbishment on an office complex, the structural check found only 180 kg/m² of spare capacity — to be divided between drainage layer, growing medium, planting and saturation water.\n\nA 100 mm gravel drainage course weighs around 190 kg/m² saturated. On its own it went straight past the entire budget.\n\nThe same depth in 8–16 mm expanded clay weighs about 65 kg/m². What was left over allowed the growing medium to go from 120 mm to 200 mm deep, and the planting scheme to move from ground cover up to shrubs.",
                    "في إعادة تأهيل سطح مجمع مكاتب، وجد الفحص الإنشائي ١٨٠ كجم/م² فقط من السعة الفائضة، تُقسَّم بين طبقة التصريف ووسط الزراعة والنباتات وماء التشبّع.\n\nتزن طبقة تصريف حصوية بسماكة ١٠٠ مم نحو ١٩٠ كجم/م² عند التشبّع، أي أنها وحدها تتجاوز الميزانية كاملة.\n\nأما السماكة نفسها من الطين الممدد ٨–١٦ فتزن نحو ٦٥ كجم/م². وأتاح المتبقي رفع عمق وسط الزراعة من ١٢٠ إلى ٢٠٠ مم، وترقية مخطط الزراعة من الغطاء الأرضي إلى الشجيرات.",
                ),
            ],
            [
                'slug' => 'export-shipments-reach-fourteen-countries',
                'type' => 'news',
                'reading_minutes' => 2,
                'published_at' => now()->subDays(95),
                'title' => $this->t(
                    'صادرات به چهاردهمین کشور مقصد رسید',
                    'Export reaches a fourteenth destination',
                    'التصدير يبلغ الوجهة الرابعة عشرة',
                ),
                'excerpt' => $this->t(
                    'نخستین محموله بیگ‌بگ به مقصد جدید، با تحویل CIF بارگیری شد.',
                    'The first big-bag consignment to the new destination has been loaded, delivered CIF.',
                    'شُحنت أول حمولة بأكياس كبيرة إلى الوجهة الجديدة بتسليم CIF.',
                ),
                'body' => $this->t(
                    'با بارگیری نخستین محموله به مقصد جدید، تعداد کشورهای مقصد صادرات به چهارده رسید. حمل به‌صورت بیگ‌بگ یک متر مکعبی و با شرایط CIF انجام شد. واحد صادرات آماده بررسی درخواست‌های EXW، FOB و CIF برای مقاصد خلیج فارس و آسیای میانه است.',
                    'With the first consignment loaded to a new destination, the number of export markets reaches fourteen. The shipment went out in 1 m³ big bags on CIF terms. The export desk handles EXW, FOB and CIF enquiries for Persian Gulf and Central Asian destinations.',
                    'بشحن أول حمولة إلى وجهة جديدة، بلغ عدد أسواق التصدير أربع عشرة سوقاً. وشُحنت الحمولة بأكياس كبيرة سعة متر مكعب بشروط CIF. ويتعامل قسم التصدير مع طلبات EXW وFOB وCIF لوجهات الخليج وآسيا الوسطى.',
                ),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(['slug' => $post['slug']], [...$post, 'is_active' => true]);
        }
    }

    private function downloads(): void
    {
        $downloads = [
            [
                'slug' => 'product-catalogue-fa', 'category' => 'catalogue', 'locale' => 'fa',
                'file_path' => 'catalogues/arta-leca-catalogue-fa.pdf', 'file_extension' => 'pdf', 'file_size' => 4_812_000,
                'title' => $this->t('کاتالوگ محصولات (فارسی)', 'Product catalogue (Persian)', 'كتالوج المنتجات (فارسي)'),
                'description' => $this->t(
                    'کاتالوگ کامل گریدها، مشخصات فنی و راهنمای انتخاب.',
                    'Full catalogue of grades, technical data and selection guidance.',
                    'كتالوج كامل للدرجات والبيانات الفنية ودليل الاختيار.',
                ),
            ],
            [
                'slug' => 'product-catalogue-en', 'category' => 'catalogue', 'locale' => 'en',
                'file_path' => 'catalogues/arta-leca-catalogue-en.pdf', 'file_extension' => 'pdf', 'file_size' => 4_640_000,
                'title' => $this->t('کاتالوگ محصولات (انگلیسی)', 'Product catalogue (English)', 'كتالوج المنتجات (إنجليزي)'),
                'description' => $this->t(
                    'نسخه انگلیسی کاتالوگ، مناسب ارسال به مشتریان صادراتی.',
                    'English edition of the catalogue, for export customers.',
                    'النسخة الإنجليزية من الكتالوج، لعملاء التصدير.',
                ),
            ],
            [
                'slug' => 'product-catalogue-ar', 'category' => 'catalogue', 'locale' => 'ar',
                'file_path' => 'catalogues/arta-leca-catalogue-ar.pdf', 'file_extension' => 'pdf', 'file_size' => 4_705_000,
                'title' => $this->t('کاتالوگ محصولات (عربی)', 'Product catalogue (Arabic)', 'كتالوج المنتجات (عربي)'),
                'description' => $this->t(
                    'نسخه عربی کاتالوگ برای بازارهای منطقه.',
                    'Arabic edition of the catalogue for regional markets.',
                    'النسخة العربية من الكتالوج لأسواق المنطقة.',
                ),
            ],
            [
                'slug' => 'datasheet-structural-grades', 'category' => 'datasheet', 'locale' => null,
                'file_path' => 'datasheets/structural-grades.pdf', 'file_extension' => 'pdf', 'file_size' => 612_000,
                'title' => $this->t('دیتاشیت گریدهای سازه‌ای', 'Structural grades datasheet', 'ورقة بيانات الدرجات الإنشائية'),
                'description' => $this->t(
                    'مقادیر معمول تولید برای گریدهای ۴–۱۰ و ۱۰–۲۰ میلی‌متر.',
                    'Typical production values for the 4–10 and 10–20 mm fractions.',
                    'القيم الإنتاجية النموذجية للمقاسين ٤–١٠ و١٠–٢٠ مم.',
                ),
            ],
            [
                'slug' => 'datasheet-fill-grades', 'category' => 'datasheet', 'locale' => null,
                'file_path' => 'datasheets/fill-grades.pdf', 'file_extension' => 'pdf', 'file_size' => 588_000,
                'title' => $this->t('دیتاشیت گریدهای پرکننده', 'Fill grades datasheet', 'ورقة بيانات درجات الردم'),
                'description' => $this->t(
                    'مقادیر معمول تولید برای گریدهای ۰–۳ و ۳–۱۰ میلی‌متر.',
                    'Typical production values for the 0–3 and 3–10 mm fractions.',
                    'القيم الإنتاجية النموذجية للمقاسين ٠–٣ و٣–١٠ مم.',
                ),
            ],
            [
                'slug' => 'installation-guide-roof-screed', 'category' => 'guide', 'locale' => null,
                'file_path' => 'guides/roof-screed-installation.pdf', 'file_extension' => 'pdf', 'file_size' => 1_940_000,
                'title' => $this->t('راهنمای اجرای شیب‌بندی بام', 'Roof screed installation guide', 'دليل تنفيذ ميول الأسطح'),
                'description' => $this->t(
                    'جزئیات اجرایی، ضخامت‌های پیشنهادی و نکات پرداخت سطح.',
                    'Build-up details, recommended depths and surface finishing notes.',
                    'تفاصيل التركيب والسماكات الموصى بها وملاحظات تشطيب السطح.',
                ),
            ],
            [
                'slug' => 'quality-management-certificate', 'category' => 'certificate', 'locale' => null,
                'file_path' => 'certificates/quality-management.pdf', 'file_extension' => 'pdf', 'file_size' => 320_000,
                'title' => $this->t('گواهینامه سیستم مدیریت کیفیت', 'Quality management certificate', 'شهادة نظام إدارة الجودة'),
                'description' => $this->t(
                    'گواهی معتبر سیستم مدیریت کیفیت کارخانه.',
                    'Current certification of the plant quality management system.',
                    'الشهادة السارية لنظام إدارة الجودة في المصنع.',
                ),
            ],
        ];

        foreach ($downloads as $index => $download) {
            Download::updateOrCreate(
                ['slug' => $download['slug']],
                [...$download, 'position' => $index + 1, 'is_active' => true],
            );
        }
    }

    private function certificates(): void
    {
        $certificates = [
            [
                'reference' => 'QMS-2018-4471', 'year' => 2018,
                'title' => $this->t('سیستم مدیریت کیفیت', 'Quality management system', 'نظام إدارة الجودة'),
                'issuer' => $this->t('مرجع صدور گواهی مستقل', 'Independent certification body', 'جهة اعتماد مستقلة'),
            ],
            [
                'reference' => 'OHS-2019-1182', 'year' => 2019,
                'title' => $this->t('سیستم مدیریت ایمنی و بهداشت شغلی', 'Occupational health & safety system', 'نظام الصحة والسلامة المهنية'),
                'issuer' => $this->t('مرجع صدور گواهی مستقل', 'Independent certification body', 'جهة اعتماد مستقلة'),
            ],
            [
                'reference' => 'ISIRI-7657', 'year' => 2021,
                'title' => $this->t('استاندارد ملی سبکدانه سازه‌ای', 'National standard, structural lightweight aggregate', 'المواصفة الوطنية للركام الإنشائي خفيف الوزن'),
                'issuer' => $this->t('سازمان ملی استاندارد ایران', 'Iranian National Standards Organization', 'المنظمة الوطنية الإيرانية للمواصفات'),
            ],
            [
                'reference' => 'EN-13055-1', 'year' => 2022,
                'title' => $this->t('انطباق با EN 13055-1', 'Conformity with EN 13055-1', 'المطابقة لـ EN 13055-1'),
                'issuer' => $this->t('آزمایشگاه شخص ثالث', 'Third-party laboratory', 'مختبر طرف ثالث'),
            ],
            [
                'reference' => 'ENV-2023-0907', 'year' => 2023,
                'title' => $this->t('سیستم مدیریت زیست‌محیطی', 'Environmental management system', 'نظام الإدارة البيئية'),
                'issuer' => $this->t('مرجع صدور گواهی مستقل', 'Independent certification body', 'جهة اعتماد مستقلة'),
            ],
        ];

        foreach ($certificates as $index => $certificate) {
            Certificate::updateOrCreate(
                ['reference' => $certificate['reference']],
                [...$certificate, 'position' => $index + 1, 'is_active' => true],
            );
        }
    }

    private function faqs(): void
    {
        $faqs = [
            [
                'group' => 'general',
                'question' => $this->t('لیکا دقیقاً چیست؟', 'What exactly is LECA?', 'ما هي ليكا بالضبط؟'),
                'answer' => $this->t(
                    'سبکدانه رسی منبسط‌شده: گرانول رس که در کوره دوار تا حدود ۱٬۲۰۰ درجه سانتی‌گراد پخته می‌شود. گازهای آزادشده در این دما ساختار سلولی بسته‌ای می‌سازند و دانه تا چند برابر حجم اولیه منبسط می‌شود. محصول نهایی صد در صد معدنی، سبک و متخلخل است.',
                    'Lightweight expanded clay aggregate: clay granules fired in a rotary kiln to about 1,200 °C. Gases released at that temperature create a closed cellular structure and the granule expands to several times its original volume. The result is a fully mineral, light, porous aggregate.',
                    'ركام الطين الممدد خفيف الوزن: حبيبات طين تُحرق في فرن دوّار حتى نحو ١٬٢٠٠ درجة مئوية. وتكوّن الغازات المنطلقة عند هذه الحرارة بنية خلوية مغلقة فتتمدد الحبيبة إلى أضعاف حجمها الأصلي. والناتج ركام معدني بالكامل، خفيف ومسامي.',
                ),
            ],
            [
                'group' => 'technical',
                'question' => $this->t('چه گریدی برای بتن سبک سازه‌ای مناسب است؟', 'Which grade suits structural lightweight concrete?', 'أي درجة تناسب الخرسانة الإنشائية خفيفة الوزن؟'),
                'answer' => $this->t(
                    'گرید ۴ تا ۱۰ میلی‌متر معمولاً بهترین تعادل میان کاهش وزن و مقاومت فشاری دانه را می‌دهد. برای رده‌های مقاومتی بالای ۳۰ مگاپاسکال، ترکیب آن با ریزدانه ۰–۳ توصیه می‌شود.',
                    'The 4–10 mm fraction usually gives the best balance between weight reduction and crushing resistance. Above the 30 MPa strength class we recommend blending it with the 0–3 mm fine fraction.',
                    'يعطي المقاس ٤–١٠ مم عادةً أفضل توازن بين خفض الوزن ومقاومة التكسير. وفوق فئة المقاومة ٣٠ ميجاباسكال نوصي بمزجه مع المقاس الناعم ٠–٣ مم.',
                ),
            ],
            [
                'group' => 'technical',
                'question' => $this->t('آیا سبکدانه باید پیش از اختلاط اشباع شود؟', 'Does the aggregate need pre-soaking before batching?', 'هل يحتاج الركام إلى نقع مسبق قبل الخلط؟'),
                'answer' => $this->t(
                    'بله. دانه خشک بخشی از آب اختلاط را جذب می‌کند و اسلامپ در حین حمل افت می‌کند. پیش‌اشباع حدود ۳۰ دقیقه، این نوسان را حذف می‌کند و کارایی مخلوط را در طول مسیر پمپاژ ثابت نگه می‌دارد.',
                    'Yes. Dry aggregate takes up part of the mixing water and slump falls in transit. A pre-soak of about 30 minutes removes that drift and keeps workability constant along the pump line.',
                    'نعم. يمتص الركام الجاف جزءاً من ماء الخلط فينخفض الهبوط أثناء النقل. ويزيل نقع مسبق نحو ٣٠ دقيقة هذا الانحراف ويحافظ على قابلية التشغيل على طول خط الضخ.',
                ),
            ],
            [
                'group' => 'technical',
                'question' => $this->t('رفتار لیکا در برابر آتش چگونه است؟', 'How does LECA behave in a fire?', 'كيف يتصرف الطين الممدد في الحريق؟'),
                'answer' => $this->t(
                    'کاملاً معدنی و غیرقابل اشتعال است (رده واکنش در برابر آتش A1). چون خود دانه در دمای حدود ۱٬۲۰۰ درجه پخته شده، تا حدود ۱٬۱۵۰ درجه سانتی‌گراد پایدار می‌ماند و هیچ دود یا گاز سمی آزاد نمی‌کند.',
                    'It is entirely mineral and non-combustible (Euroclass A1 reaction to fire). Because the granule was itself fired at around 1,200 °C, it stays stable to about 1,150 °C and releases no smoke or toxic gas.',
                    'معدني بالكامل وغير قابل للاشتعال (تصنيف A1 لرد الفعل تجاه الحريق). ولأن الحبيبة نفسها حُرقت عند نحو ١٬٢٠٠ درجة، تبقى ثابتة حتى نحو ١٬١٥٠ درجة مئوية ولا تطلق دخاناً ولا غازات سامة.',
                ),
            ],
            [
                'group' => 'technical',
                'question' => $this->t('آیا سبکدانه با گذر زمان افت می‌کند؟', 'Does the aggregate degrade over time?', 'هل يتدهور الركام مع الوقت؟'),
                'answer' => $this->t(
                    'خیر. ماده‌ای خنثی از نظر شیمیایی، مقاوم در برابر یخبندان، پوسیدگی، حشرات و جوندگان است. عمر مفید آن برابر با عمر سازه در نظر گرفته می‌شود.',
                    'No. It is chemically inert and resistant to frost, rot, insects and rodents. Its service life is taken as equal to that of the structure.',
                    'لا. فهو خامل كيميائياً ومقاوم للصقيع والتعفن والحشرات والقوارض. ويُعد عمره الافتراضي مساوياً لعمر المنشأ.',
                ),
            ],
            [
                'group' => 'ordering',
                'question' => $this->t('حداقل مقدار سفارش چقدر است؟', 'What is the minimum order quantity?', 'ما هي الكمية الدنيا للطلب؟'),
                'answer' => $this->t(
                    'برای تحویل فله، حداقل یک کامیون (حدود ۶۰ متر مکعب). سفارش‌های کوچک‌تر به‌صورت بیگ‌بگ یک متر مکعبی یا کیسه ۵۰ لیتری قابل تأمین است.',
                    'For bulk delivery, one truckload (about 60 m³). Smaller quantities are supplied in 1 m³ big bags or 50-litre sacks.',
                    'للتوريد السائب، حمولة شاحنة واحدة (نحو ٦٠ م³). وتُورَّد الكميات الأصغر بأكياس كبيرة سعة م³ أو أكياس ٥٠ لتراً.',
                ),
            ],
            [
                'group' => 'ordering',
                'question' => $this->t('گواهی آنالیز محموله چه زمانی صادر می‌شود؟', 'When is the certificate of analysis issued?', 'متى تصدر شهادة تحليل الحمولة؟'),
                'answer' => $this->t(
                    'هم‌زمان با بارگیری، بر اساس آزمون‌های همان بچ تولید. نمونه شاهد هر بچ به مدت شش ماه در آزمایشگاه کارخانه نگهداری می‌شود.',
                    'At loading, based on the tests for that production batch. A retained sample of every batch is kept in the plant laboratory for six months.',
                    'عند التحميل، استناداً إلى اختبارات دفعة الإنتاج نفسها. وتُحفظ عينة شاهدة من كل دفعة في مختبر المصنع لمدة ستة أشهر.',
                ),
            ],
            [
                'group' => 'ordering',
                'question' => $this->t('چگونه حجم مورد نیاز پروژه را محاسبه کنم؟', 'How do I work out the volume my project needs?', 'كيف أحسب الكمية التي يحتاجها مشروعي؟'),
                'answer' => $this->t(
                    'برای شیب‌بندی و پرکردن، حجم هندسی به‌علاوه حدود ۵ درصد افت اجرایی. برای بتن، حجم سنگدانه از طرح اختلاط استخراج می‌شود. واحد فنی ما محاسبه را همراه با طرح اختلاط پیشنهادی ارائه می‌دهد.',
                    'For screeds and fills, the geometric volume plus about 5% for placement losses. For concrete, the aggregate volume comes out of the mix design. Our technical desk will do the calculation and propose a mix design with it.',
                    'لأعمال الميول والردم، الحجم الهندسي زائد نحو ٥٪ لفواقد التنفيذ. وللخرسانة، يُستخرج حجم الركام من تصميم الخلطة. ويقوم قسمنا الفني بالحساب ويقترح تصميم الخلطة معه.',
                ),
            ],
            [
                'group' => 'export',
                'question' => $this->t('چه شرایط تحویلی برای صادرات ارائه می‌شود؟', 'What delivery terms do you offer for export?', 'ما شروط التسليم المتاحة للتصدير؟'),
                'answer' => $this->t(
                    'EXW از درب کارخانه، FOB بندرعباس، و CIF برای مقاصد خلیج فارس. برای عراق و آسیای میانه، حمل زمینی با شرایط DAP نیز قابل بررسی است.',
                    'EXW at the plant, FOB Bandar Abbas, and CIF to Persian Gulf destinations. For Iraq and Central Asia we can also quote overland delivery on DAP terms.',
                    'EXW من المصنع، وFOB بندر عباس، وCIF إلى موانئ الخليج. وللعراق وآسيا الوسطى يمكننا أيضاً تسعير النقل البري بشروط DAP.',
                ),
            ],
            [
                'group' => 'export',
                'question' => $this->t('بسته‌بندی صادراتی چگونه است؟', 'How is export cargo packed?', 'كيف تُعبَّأ بضاعة التصدير؟'),
                'answer' => $this->t(
                    'بیگ‌بگ یک متر مکعبی با آستر ضدرطوبت برای حمل کانتینری، و بارگیری فله برای حمل کشتی. کیسه ۵۰ لیتری نیز برای بازار خرده‌فروشی موجود است.',
                    '1 m³ big bags with a moisture barrier liner for container shipment, and bulk loading for vessel cargo. 50-litre sacks are available for the retail trade.',
                    'أكياس كبيرة سعة م³ ببطانة مانعة للرطوبة للشحن بالحاويات، وتحميل سائب للشحن بالسفن. وتتوفر أكياس ٥٠ لتراً لتجارة التجزئة.',
                ),
            ],
        ];

        // FAQs have no slug, so group + position is the stable identity that
        // keeps re-seeding idempotent instead of duplicating every entry.
        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['group' => $faq['group'], 'position' => $index + 1],
                [...$faq, 'is_active' => true],
            );
        }
    }
}

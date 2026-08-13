<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Download;
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
}

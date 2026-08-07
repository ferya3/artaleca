<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * Product catalogue.
 *
 * Technical values are typical published figures for expanded clay aggregate:
 * bulk density falls and crushing resistance drops as the fraction gets
 * coarser, which is exactly the trade-off a specifier is choosing between.
 */
class CatalogueSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        foreach ($this->categories() as $index => $category) {
            $products = $category['products'];
            unset($category['products']);

            $model = ProductCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [...$category, 'position' => $index + 1, 'is_active' => true],
            );

            foreach ($products as $productIndex => $product) {
                Product::updateOrCreate(
                    ['slug' => $product['slug']],
                    [
                        ...$product,
                        'product_category_id' => $model->id,
                        'position' => ($index + 1) * 10 + $productIndex,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function categories(): array
    {
        return [
            [
                'slug' => 'structural',
                'name' => $this->t('سبکدانه سازه‌ای', 'Structural grades', 'درجات إنشائية'),
                'summary' => $this->t(
                    'گریدهای درشت‌دانه برای بتن سبک سازه‌ای، با تعادل میان کاهش وزن و مقاومت فشاری دانه.',
                    'Coarse fractions for structural lightweight concrete, balancing weight reduction against crushing resistance.',
                    'مقاسات خشنة للخرسانة الإنشائية خفيفة الوزن، توازن بين خفض الوزن ومقاومة التكسير.',
                ),
                'description' => $this->t(
                    "بتن سبک سازه‌ای با سبکدانه لیکا به مقاومت فشاری ۲۵ تا ۴۰ مگاپاسکال در وزن مخصوصی حدود ۱۶۰۰ تا ۱۸۰۰ کیلوگرم بر متر مکعب می‌رسد؛ یعنی حدود ۲۵ تا ۳۵ درصد سبک‌تر از بتن معمولی با همان رده مقاومتی.\n\nاین کاهش وزن مستقیماً به ابعاد کوچک‌تر مقاطع، آرماتور کمتر و بار کمتر بر فونداسیون ترجمه می‌شود — اثری که در سازه‌های بلندمرتبه و دهانه‌بلند از خود صرفه‌جویی در مصالح مهم‌تر است.",
                    "Structural lightweight concrete made with Arta Leca reaches 25–40 MPa compressive strength at a density of roughly 1,600–1,800 kg/m³ — some 25–35% lighter than normal-weight concrete of the same strength class.\n\nThat reduction translates directly into smaller sections, less reinforcement and lower foundation loads, which in tall or long-span structures matters more than the saving on the material itself.",
                    "تبلغ مقاومة الخرسانة الإنشائية خفيفة الوزن المصنوعة بركام آرتا ليكا ٢٥–٤٠ ميجاباسكال عند كثافة تقارب ١٬٦٠٠–١٬٨٠٠ كجم/م³، أي أخف بنحو ٢٥–٣٥٪ من الخرسانة الاعتيادية بالفئة نفسها.\n\nويترجم هذا الخفض مباشرة إلى مقاطع أصغر وحديد تسليح أقل وأحمال أساسات أدنى، وهو ما يفوق في المنشآت المرتفعة وطويلة البحور قيمة التوفير في المادة ذاتها.",
                ),
                'products' => [
                    [
                        'slug' => 'leca-structure-4-10',
                        'sku' => 'ALS-0410',
                        'name' => $this->t('لیکا سازه‌ای ۴–۱۰', 'Leca Structure 4–10', 'ليكا إنشائية ٤–١٠'),
                        'tagline' => $this->t(
                            'گرید اصلی بتن سبک سازه‌ای درجا و پیش‌ساخته.',
                            'The workhorse grade for cast-in-place and precast structural lightweight concrete.',
                            'الدرجة الأساسية للخرسانة الإنشائية خفيفة الوزن المصبوبة والمسبقة الصنع.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی ۴ تا ۱۰ میلی‌متر با وزن مخصوص انبوه ۳۲۰ تا ۴۰۰ کیلوگرم بر متر مکعب و مقاومت فشاری دانه ۱٫۵ مگاپاسکال؛ سازگار با پمپاژ تا ارتفاع بلند.',
                            '4–10 mm fraction at 320–400 kg/m³ loose bulk density and 1.5 MPa crushing resistance, pumpable to full building height.',
                            'مقاس ٤–١٠ مم بكثافة ظاهرية ٣٢٠–٤٠٠ كجم/م³ ومقاومة تكسير ١٫٥ ميجاباسكال، قابل للضخ إلى كامل ارتفاع المبنى.',
                        ),
                        'description' => $this->t(
                            "پرمصرف‌ترین گرید در طرح‌های اختلاط بتن سبک سازه‌ای. دانه‌بندی پیوسته و پراکندگی کم، جداشدگی مخلوط را در پمپاژ کاهش می‌دهد.\n\nنکته اجرایی: پیش از اختلاط، دانه‌ها را به مدت ۳۰ دقیقه پیش‌اشباع کنید. سبکدانه خشک بخشی از آب اختلاط را جذب می‌کند و اسلامپ در حین حمل افت می‌کند؛ پیش‌اشباع این نوسان را حذف می‌کند و کارایی را در طول مسیر ثابت نگه می‌دارد.",
                            "The most widely used grade in structural lightweight mix designs. A continuous grading with narrow spread reduces segregation during pumping.\n\nOn site: pre-soak for 30 minutes before batching. Dry aggregate absorbs part of the mixing water and slump falls during transport; pre-soaking removes that drift and keeps workability constant along the pump line.",
                            "الدرجة الأكثر استخداماً في تصاميم خلطات الخرسانة الإنشائية خفيفة الوزن. يقلّل التدرّج المستمر وضيق التشتّت من انفصال الخلطة أثناء الضخ.\n\nملاحظة تنفيذية: انقع الركام مسبقاً ٣٠ دقيقة قبل الخلط. يمتص الركام الجاف جزءاً من ماء الخلط فينخفض الهبوط أثناء النقل، والنقع المسبق يزيل هذا التذبذب ويحافظ على قابلية التشغيل على طول خط الضخ.",
                        ),
                        'grain_min_mm' => 4, 'grain_max_mm' => 10,
                        'bulk_density_min' => 320, 'bulk_density_max' => 400,
                        'particle_density' => 650,
                        'crushing_strength' => 1.5,
                        'thermal_conductivity' => 0.098,
                        'water_absorption_24h' => 16.0,
                        'ph_value' => 7.5,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 13055-1', 'ASTM C330', 'ISIRI 7657'],
                        'packaging' => [
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                            ['type' => $this->t('بیگ‌بگ', 'Big bag', 'كيس كبير'), 'volume' => '1 m³'],
                        ],
                        'is_featured' => true,
                    ],
                    [
                        'slug' => 'leca-structure-10-20',
                        'sku' => 'ALS-1020',
                        'name' => $this->t('لیکا سازه‌ای ۱۰–۲۰', 'Leca Structure 10–20', 'ليكا إنشائية ١٠–٢٠'),
                        'tagline' => $this->t(
                            'سبک‌ترین گرید سازه‌ای، برای جایی که کاهش وزن تعیین‌کننده است.',
                            'The lightest structural fraction, for where dead load governs the design.',
                            'أخف درجة إنشائية، حين يكون الحمل الميت هو الحاكم في التصميم.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی ۱۰ تا ۲۰ میلی‌متر با وزن مخصوص انبوه ۲۶۰ تا ۳۲۰ کیلوگرم بر متر مکعب؛ کمترین بار مرده در میان گریدهای سازه‌ای.',
                            '10–20 mm fraction at 260–320 kg/m³, the lowest dead load of any structural grade.',
                            'مقاس ١٠–٢٠ مم بكثافة ٢٦٠–٣٢٠ كجم/م³، وهو أدنى حمل ميت بين الدرجات الإنشائية.',
                        ),
                        'description' => $this->t(
                            "برای بتن سبک با تأکید بر کاهش وزن، و برای پرکردن حجم‌های بزرگ در سقف‌های کامپوزیت.\n\nبا افزایش اندازه دانه، مقاومت فشاری دانه کاهش می‌یابد؛ در رده‌های مقاومتی بالای ۳۰ مگاپاسکال، ترکیب این گرید با ۴–۱۰ توصیه می‌شود.",
                            "For lightweight concrete where dead load dominates, and for filling large volumes in composite floor build-ups.\n\nCrushing resistance falls as the particle gets larger; above the 30 MPa strength class we recommend blending this fraction with 4–10.",
                            "للخرسانة خفيفة الوزن حين يهيمن الحمل الميت، ولملء الحجوم الكبيرة في الأسقف المركّبة.\n\nتقل مقاومة التكسير كلما كبرت الحبيبة؛ وفوق فئة المقاومة ٣٠ ميجاباسكال نوصي بمزج هذه الدرجة مع ٤–١٠.",
                        ),
                        'grain_min_mm' => 10, 'grain_max_mm' => 20,
                        'bulk_density_min' => 260, 'bulk_density_max' => 320,
                        'particle_density' => 520,
                        'crushing_strength' => 0.9,
                        'thermal_conductivity' => 0.091,
                        'water_absorption_24h' => 18.5,
                        'ph_value' => 7.5,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 13055-1', 'ASTM C330'],
                        'packaging' => [
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                            ['type' => $this->t('بیگ‌بگ', 'Big bag', 'كيس كبير'), 'volume' => '1 m³'],
                        ],
                        'is_featured' => true,
                    ],
                ],
            ],

            [
                'slug' => 'fill-and-screed',
                'name' => $this->t('پرکننده و شیب‌بندی', 'Fill & screed grades', 'درجات الردم والميول'),
                'summary' => $this->t(
                    'گریدهای ریزدانه برای شیب‌بندی بام، کف‌سازی سبک و پرکردن حفرات.',
                    'Fine fractions for roof screeds, lightweight floor build-ups and void filling.',
                    'مقاسات ناعمة لميول الأسطح والأرضيات خفيفة الوزن وملء الفراغات.',
                ),
                'description' => $this->t(
                    'شیب‌بندی بام با سبکدانه، در مقایسه با پوکه معدنی یا بتن سبک درجا، وزن کمتری بر سقف تحمیل می‌کند و در عین حال یک لایه عایق حرارتی پیوسته می‌سازد.',
                    'A LECA screed puts less weight on the slab than pumice or site-mixed lightweight concrete, and forms a continuous layer of thermal insulation at the same time.',
                    'يضع ميل الأسطح بركام ليكا وزناً أقل على البلاطة مقارنةً بالخفّان أو الخرسانة خفيفة الوزن المخلوطة في الموقع، ويشكّل في الوقت نفسه طبقة عزل حراري متصلة.',
                ),
                'products' => [
                    [
                        'slug' => 'leca-fill-0-3',
                        'sku' => 'ALF-0003',
                        'name' => $this->t('لیکا ریزدانه ۰–۳', 'Leca Fill 0–3', 'ليكا ناعمة ٠–٣'),
                        'tagline' => $this->t(
                            'ریزدانه برای بلوک سبک، ملات و کف‌سازی.',
                            'The fine fraction for lightweight blocks, mortars and floor screeds.',
                            'المقاس الناعم للبلوك خفيف الوزن والمونة وأرضيات الميول.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی ۰ تا ۳ میلی‌متر با وزن مخصوص انبوه ۵۵۰ تا ۶۵۰ کیلوگرم بر متر مکعب و بالاترین مقاومت فشاری دانه در میان گریدها.',
                            '0–3 mm at 550–650 kg/m³, with the highest crushing resistance in the range.',
                            'مقاس ٠–٣ مم بكثافة ٥٥٠–٦٥٠ كجم/م³ وأعلى مقاومة تكسير ضمن المجموعة.',
                        ),
                        'description' => $this->t(
                            'جایگزین ماسه در بلوک‌های سبک، ملات‌های عایق و کف‌سازی. ریزدانگی بالا، بافت سطح یکنواخت‌تری می‌دهد و پرداخت را ساده می‌کند.',
                            'Replaces sand in lightweight blocks, insulating mortars and floor screeds. The fine grading gives a more uniform surface texture and makes finishing straightforward.',
                            'يحل محل الرمل في البلوك خفيف الوزن والمونة العازلة وأرضيات الميول. ويمنح التدرّج الناعم نسيجاً سطحياً أكثر انتظاماً ويبسّط أعمال التشطيب.',
                        ),
                        'grain_min_mm' => 0, 'grain_max_mm' => 3,
                        'bulk_density_min' => 550, 'bulk_density_max' => 650,
                        'particle_density' => 1100,
                        'crushing_strength' => 3.4,
                        'thermal_conductivity' => 0.125,
                        'water_absorption_24h' => 12.0,
                        'ph_value' => 7.8,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 13055-1', 'EN 1097-3'],
                        'packaging' => [
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                            ['type' => $this->t('کیسه', 'Sack', 'كيس'), 'volume' => '50 L'],
                        ],
                    ],
                    [
                        'slug' => 'leca-fill-3-10',
                        'sku' => 'ALF-0310',
                        'name' => $this->t('لیکا شیب‌بندی ۳–۱۰', 'Leca Fill 3–10', 'ليكا للميول ٣–١٠'),
                        'tagline' => $this->t(
                            'استاندارد شیب‌بندی بام و پرکردن حفرات.',
                            'The standard grade for roof screeds and void filling.',
                            'الدرجة القياسية لميول الأسطح وملء الفراغات.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی ۳ تا ۱۰ میلی‌متر با وزن مخصوص انبوه ۳۸۰ تا ۴۵۰ کیلوگرم بر متر مکعب؛ قابل اجرا به‌صورت خشک یا با دوغاب سیمان.',
                            '3–10 mm at 380–450 kg/m³, laid dry or bound with a cement slurry.',
                            'مقاس ٣–١٠ مم بكثافة ٣٨٠–٤٥٠ كجم/م³، يُنفَّذ جافاً أو مربوطاً بمعجون أسمنتي.',
                        ),
                        'description' => $this->t(
                            'برای شیب‌بندی پشت‌بام، پرکردن فضای بین تیرچه‌ها و اجرای کف شناور. اجرای خشک آن سریع است و در همان روز قابل بارگذاری سبک است.',
                            'For roof falls, filling between joists and floating floor build-ups. Laid dry it goes down fast and takes light traffic the same day.',
                            'لميول الأسطح وملء ما بين الأعصاب وتنفيذ الأرضيات العائمة. تنفيذه جافاً سريع ويتحمّل حركة خفيفة في اليوم نفسه.',
                        ),
                        'grain_min_mm' => 3, 'grain_max_mm' => 10,
                        'bulk_density_min' => 380, 'bulk_density_max' => 450,
                        'particle_density' => 750,
                        'crushing_strength' => 1.9,
                        'thermal_conductivity' => 0.105,
                        'water_absorption_24h' => 15.0,
                        'ph_value' => 7.6,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 13055-1'],
                        'packaging' => [
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                            ['type' => $this->t('بیگ‌بگ', 'Big bag', 'كيس كبير'), 'volume' => '1 m³'],
                            ['type' => $this->t('کیسه', 'Sack', 'كيس'), 'volume' => '50 L'],
                        ],
                        'is_featured' => true,
                    ],
                ],
            ],

            [
                'slug' => 'geotechnical',
                'name' => $this->t('ژئوتکنیک و راه', 'Geotechnical & infrastructure', 'الجيوتقنية والبنية التحتية'),
                'summary' => $this->t(
                    'گرید درشت برای پرکننده سبک پشت دیوار حائل، خاکریز راه و بستر ابنیه.',
                    'Coarse grade for lightweight backfill behind retaining walls, road embankments and structure bedding.',
                    'درجة خشنة للردم خفيف الوزن خلف الجدران الاستنادية وردميات الطرق وفرشات المنشآت.',
                ),
                'description' => $this->t(
                    'در خاک‌های نرم، وزن خاکریز خودش عامل اصلی نشست است. جایگزینی خاکریز معمولی با سبکدانه، بار وارد بر بستر را تا ۸۰ درصد کاهش می‌دهد و نشست بلندمدت را حذف می‌کند.',
                    'On soft ground the weight of the embankment is itself the main cause of settlement. Replacing conventional fill with expanded clay cuts the load on the subgrade by up to 80% and designs the long-term settlement out.',
                    'في الأتربة الرخوة يكون وزن الردمية نفسه السبب الرئيس للهبوط. ويقلل استبدال الردم التقليدي بالطين الممدد الحمل على طبقة الأساس بنسبة تصل إلى ٨٠٪ ويلغي الهبوط بعيد المدى.',
                ),
                'products' => [
                    [
                        'slug' => 'leca-infra-10-30',
                        'sku' => 'ALI-1030',
                        'name' => $this->t('لیکا ژئوتکنیک ۱۰–۳۰', 'Leca Infra 10–30', 'ليكا جيوتقنية ١٠–٣٠'),
                        'tagline' => $this->t(
                            'خاکریز سبک با یک‌پنجم وزن خاک معمولی.',
                            'Lightweight embankment fill at a fifth of the weight of soil.',
                            'ردم خفيف بوزن يعادل خُمس وزن التربة.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی درشت ۱۰ تا ۳۰ میلی‌متر با وزن مخصوص انبوه ۲۵۰ تا ۳۰۰ کیلوگرم بر متر مکعب و زاویه اصطکاک داخلی حدود ۳۵ درجه.',
                            '10–30 mm at 250–300 kg/m³ with an internal friction angle of about 35°.',
                            'مقاس ١٠–٣٠ مم بكثافة ٢٥٠–٣٠٠ كجم/م³ وزاوية احتكاك داخلي نحو ٣٥ درجة.',
                        ),
                        'description' => $this->t(
                            "برای خاکریز راه روی خاک نرم، پرکننده پشت دیوار حائل و کاهش فشار جانبی خاک.\n\nزهکشی آزاد این گرید، فشار آب منفذی را پشت سازه نگه‌دار حذف می‌کند — همان عاملی که در بسیاری از خرابی‌های دیوار حائل نقش اصلی را دارد.",
                            "For road embankments over soft ground, backfill behind retaining walls, and reducing lateral earth pressure.\n\nIts free-draining structure removes pore water pressure behind the retaining structure — the factor behind a large share of retaining wall failures.",
                            "لردميات الطرق فوق الأتربة الرخوة، والردم خلف الجدران الاستنادية، وخفض ضغط التربة الجانبي.\n\nويلغي تصريفه الحر ضغط الماء المسامي خلف المنشأ الساند، وهو العامل الكامن وراء نسبة كبيرة من انهيارات الجدران الاستنادية.",
                        ),
                        'grain_min_mm' => 10, 'grain_max_mm' => 30,
                        'bulk_density_min' => 250, 'bulk_density_max' => 300,
                        'particle_density' => 480,
                        'crushing_strength' => 0.8,
                        'thermal_conductivity' => 0.090,
                        'water_absorption_24h' => 20.0,
                        'ph_value' => 7.4,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 15732', 'EN 13055-2'],
                        'packaging' => [
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'horticulture',
                'name' => $this->t('باغبانی و بام سبز', 'Horticulture & green roof', 'البستنة والأسطح الخضراء'),
                'summary' => $this->t(
                    'گریدهای شسته و غربال‌شده برای بستر کشت، زهکش بام سبز و هیدروپونیک.',
                    'Washed, screened grades for growing media, green roof drainage and hydroponics.',
                    'درجات مغسولة ومنخولة لأوساط الزراعة وتصريف الأسطح الخضراء والزراعة المائية.',
                ),
                'description' => $this->t(
                    'سبکدانه در بستر کشت هم‌زمان زهکشی و ذخیره رطوبت را فراهم می‌کند: آب اضافی از میان دانه‌ها عبور می‌کند و بخشی از رطوبت در تخلخل خود دانه می‌ماند و به‌تدریج آزاد می‌شود.',
                    'In a growing medium expanded clay does two jobs at once: surplus water drains through the voids between granules, while part of the moisture is held inside the granule itself and released gradually.',
                    'يؤدي الطين الممدد في وسط الزراعة وظيفتين معاً: يُصرَّف الماء الفائض عبر الفراغات بين الحبيبات، بينما يُحتجَز جزء من الرطوبة داخل الحبيبة نفسها ويُطلَق تدريجياً.',
                ),
                'products' => [
                    [
                        'slug' => 'leca-garden-4-10',
                        'sku' => 'ALG-0410',
                        'name' => $this->t('لیکا باغبانی ۴–۱۰', 'Leca Garden 4–10', 'ليكا بستنة ٤–١٠'),
                        'tagline' => $this->t(
                            'شسته و بدون غبار، برای بستر کشت و هیدروپونیک.',
                            'Washed and dust-free, for growing media and hydroponics.',
                            'مغسولة وخالية من الغبار، لأوساط الزراعة والزراعة المائية.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی ۴ تا ۱۰ میلی‌متر شسته، با pH خنثی و بدون املاح محلول؛ قابل استفاده مجدد پس از ضدعفونی.',
                            'Washed 4–10 mm with a neutral pH and no soluble salts; reusable after sterilisation.',
                            'مقاس ٤–١٠ مم مغسول برقم هيدروجيني متعادل وخالٍ من الأملاح الذائبة؛ قابل لإعادة الاستخدام بعد التعقيم.',
                        ),
                        'description' => $this->t(
                            'برای کشت هیدروپونیک، بستر گلدان، مالچ سطحی و لایه زهکش زیر خاک گلدان. خنثی بودن شیمیایی یعنی EC محلول غذایی را تغییر نمی‌دهد.',
                            'For hydroponic culture, potting media, surface mulch and the drainage layer beneath potting soil. Being chemically inert, it does not shift the EC of the nutrient solution.',
                            'للزراعة المائية وأوساط الأصص والتغطية السطحية وطبقة التصريف أسفل تربة الأصص. وكونها خاملة كيميائياً فإنها لا تغيّر التوصيلية الكهربائية للمحلول المغذي.',
                        ),
                        'grain_min_mm' => 4, 'grain_max_mm' => 10,
                        'bulk_density_min' => 300, 'bulk_density_max' => 380,
                        'particle_density' => 620,
                        'crushing_strength' => 1.4,
                        'thermal_conductivity' => 0.096,
                        'water_absorption_24h' => 17.0,
                        'ph_value' => 7.0,
                        'fire_resistance_c' => 1150,
                        'standards' => ['EN 12580'],
                        'packaging' => [
                            ['type' => $this->t('کیسه', 'Sack', 'كيس'), 'volume' => '50 L'],
                            ['type' => $this->t('بیگ‌بگ', 'Big bag', 'كيس كبير'), 'volume' => '1 m³'],
                        ],
                        'is_featured' => true,
                    ],
                    [
                        'slug' => 'leca-greenroof-8-16',
                        'sku' => 'ALG-0816',
                        'name' => $this->t('لیکا بام سبز ۸–۱۶', 'Leca Green Roof 8–16', 'ليكا أسطح خضراء ٨–١٦'),
                        'tagline' => $this->t(
                            'لایه زهکش بام سبز، سبک و پایدار در برابر یخبندان.',
                            'The green roof drainage layer: light and frost-stable.',
                            'طبقة تصريف الأسطح الخضراء: خفيفة وثابتة أمام التجمّد.',
                        ),
                        'summary' => $this->t(
                            'دانه‌بندی یکنواخت ۸ تا ۱۶ میلی‌متر با تخلخل بالا؛ زهکشی سریع در بارش شدید همراه با ذخیره رطوبت برای دوره خشکی.',
                            'A uniform 8–16 mm fraction with high porosity: rapid drainage in heavy rain, moisture storage for the dry spell after it.',
                            'مقاس منتظم ٨–١٦ مم بمسامية عالية: تصريف سريع في الأمطار الغزيرة واحتجاز للرطوبة للفترة الجافة بعدها.',
                        ),
                        'description' => $this->t(
                            'لایه زهکش در بام سبز گسترده و متمرکز. وزن اشباع پایین آن، بار مرده سقف را در مقایسه با زهکش شنی به‌شدت کاهش می‌دهد.',
                            'The drainage layer in both extensive and intensive green roofs. Its low saturated weight cuts the roof dead load sharply compared with a gravel drainage course.',
                            'طبقة التصريف في الأسطح الخضراء الممتدة والمكثّفة. ويخفض وزنها المشبع المنخفض الحمل الميت للسطح بدرجة كبيرة مقارنةً بطبقة تصريف من الحصى.',
                        ),
                        'grain_min_mm' => 8, 'grain_max_mm' => 16,
                        'bulk_density_min' => 280, 'bulk_density_max' => 340,
                        'particle_density' => 560,
                        'crushing_strength' => 1.1,
                        'thermal_conductivity' => 0.093,
                        'water_absorption_24h' => 18.0,
                        'ph_value' => 7.2,
                        'fire_resistance_c' => 1150,
                        'standards' => ['FLL', 'EN 13055-1'],
                        'packaging' => [
                            ['type' => $this->t('بیگ‌بگ', 'Big bag', 'كيس كبير'), 'volume' => '1 m³'],
                            ['type' => $this->t('فله', 'Bulk tipper', 'سائب'), 'volume' => '60–90 m³'],
                        ],
                    ],
                ],
            ],
        ];
    }
}

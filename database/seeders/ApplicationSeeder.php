<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Product;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        foreach ($this->applications() as $index => $data) {
            $productSlugs = $data['products'];
            unset($data['products']);

            $application = Application::updateOrCreate(
                ['slug' => $data['slug']],
                [...$data, 'position' => $index + 1, 'is_active' => true],
            );

            $application->products()->sync(
                Product::query()->whereIn('slug', $productSlugs)->pluck('id'),
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function applications(): array
    {
        return [
            [
                'slug' => 'structural-lightweight-concrete',
                'name' => $this->t('بتن سبک سازه‌ای', 'Structural lightweight concrete', 'الخرسانة الإنشائية خفيفة الوزن'),
                'summary' => $this->t(
                    'کاهش ۲۵ تا ۳۵ درصدی وزن بتن در همان رده مقاومتی، با اثر مستقیم بر ابعاد مقاطع و بار فونداسیون.',
                    'A 25–35% reduction in concrete weight at the same strength class, with a direct effect on section sizes and foundation loads.',
                    'خفض وزن الخرسانة بنسبة ٢٥–٣٥٪ ضمن فئة المقاومة نفسها، بأثر مباشر على أبعاد المقاطع وأحمال الأساسات.',
                ),
                'description' => $this->t(
                    "در ساختمان‌های بلندمرتبه، وزن مرده خودِ سازه بخش عمده بار وارد بر فونداسیون است. جایگزینی سنگدانه معمولی با سبکدانه، این بار را در تمام طبقات کاهش می‌دهد و اثر آن به‌صورت تجمعی به پی می‌رسد.\n\nنتیجه عملی: مقاطع کوچک‌تر تیر و ستون، آرماتور کمتر، و در بازسازی‌ها امکان افزودن طبقه بدون تقویت فونداسیون موجود.\n\nطرح اختلاط باید بر پایه حجم و نه وزن تنظیم شود، و سبکدانه پیش از اختلاط پیش‌اشباع گردد.",
                    "In tall buildings the structure's own dead weight is the greater part of what reaches the foundation. Replacing normal-weight aggregate reduces that load on every floor, and the effect accumulates all the way down.\n\nIn practice: smaller beam and column sections, less reinforcement, and — in refurbishment — the option of adding a storey without strengthening the existing foundation.\n\nMix design must be set out by volume rather than by weight, and the aggregate pre-soaked before batching.",
                    "في المباني المرتفعة يشكّل الوزن الميت للمنشأ نفسه الجزء الأكبر مما يصل إلى الأساسات. ويقلل استبدال الركام الاعتيادي هذا الحمل في كل طابق، ويتراكم الأثر حتى القاعدة.\n\nعملياً: مقاطع أصغر للجسور والأعمدة، وحديد تسليح أقل، وفي أعمال التأهيل إمكانية إضافة طابق دون تقوية الأساسات القائمة.\n\nويجب ضبط تصميم الخلطة على أساس الحجم لا الوزن، مع نقع الركام مسبقاً قبل الخلط.",
                ),
                'benefits' => [
                    $this->t('کاهش ۲۵ تا ۳۵ درصدی وزن مخصوص بتن', '25–35% lower concrete density', 'خفض كثافة الخرسانة بنسبة ٢٥–٣٥٪'),
                    $this->t('کاهش بار لرزه‌ای متناسب با کاهش جرم سازه', 'Seismic demand falls with the reduced mass', 'انخفاض الطلب الزلزالي بانخفاض الكتلة'),
                    $this->t('امکان کاهش ابعاد مقاطع و مصرف آرماتور', 'Smaller sections and less reinforcement', 'مقاطع أصغر وحديد تسليح أقل'),
                    $this->t('عملکرد حرارتی بهتر پوسته بتنی', 'Better thermal performance of the concrete envelope', 'أداء حراري أفضل للغلاف الخرساني'),
                ],
                'products' => ['leca-structure-4-10', 'leca-structure-10-20'],
            ],

            [
                'slug' => 'roof-screed-and-floors',
                'name' => $this->t('شیب‌بندی بام و کف‌سازی', 'Roof screeds & floor build-ups', 'ميول الأسطح والأرضيات'),
                'summary' => $this->t(
                    'شیب‌بندی سبک با عایق حرارتی پیوسته، بدون بار اضافی بر سقف.',
                    'Lightweight falls that double as a continuous insulation layer, without loading the slab.',
                    'ميول خفيفة تعمل كطبقة عزل متصلة، دون تحميل البلاطة.',
                ),
                'description' => $this->t(
                    "شیب‌بندی مرسوم با پوکه یا بتن سبک درجا، بین ۸۰۰ تا ۱۲۰۰ کیلوگرم بر متر مکعب وزن دارد. همان کار با سبکدانه، حدود ۴۰۰ کیلوگرم بر متر مکعب.\n\nاین اختلاف در بام‌های بزرگ به ده‌ها تن بار مرده حذف‌شده می‌رسد و هم‌زمان یک لایه عایق حرارتی پیوسته — بدون درز و پل حرارتی — روی سقف ایجاد می‌کند.",
                    "A conventional pumice or site-mixed lightweight screed weighs 800–1,200 kg/m³. The same build-up in expanded clay weighs around 400.\n\nOver a large roof that difference amounts to tens of tonnes of dead load removed, while producing a continuous layer of thermal insulation — no joints, no thermal bridges — across the slab.",
                    "يزن ميل الأسطح التقليدي بالخفّان أو الخرسانة المخلوطة في الموقع ٨٠٠–١٬٢٠٠ كجم/م³، بينما يزن التنفيذ نفسه بالطين الممدد نحو ٤٠٠.\n\nوفي الأسطح الكبيرة يبلغ هذا الفارق عشرات الأطنان من الحمل الميت المُزال، مع تكوين طبقة عزل حراري متصلة بلا فواصل ولا جسور حرارية فوق البلاطة.",
                ),
                'benefits' => [
                    $this->t('حدود یک‌سوم وزن شیب‌بندی متعارف', 'About a third of the weight of a conventional screed', 'نحو ثلث وزن الميل التقليدي'),
                    $this->t('لایه عایق حرارتی پیوسته و بدون پل حرارتی', 'Continuous insulation with no thermal bridging', 'عزل متصل بلا جسور حرارية'),
                    $this->t('اجرای خشک و سریع، بدون زمان عمل‌آوری', 'Dry laid and fast, with no curing time', 'تنفيذ جاف وسريع بلا زمن معالجة'),
                    $this->t('غیرقابل اشتعال و پایدار در برابر رطوبت', 'Non-combustible and moisture-stable', 'غير قابل للاشتعال وثابت أمام الرطوبة'),
                ],
                'products' => ['leca-fill-3-10', 'leca-fill-0-3'],
            ],

            [
                'slug' => 'geotechnical-fill',
                'name' => $this->t('پرکننده ژئوتکنیکی', 'Geotechnical fill', 'الردم الجيوتقني'),
                'summary' => $this->t(
                    'خاکریز سبک روی خاک نرم و پرکننده پشت دیوار حائل، برای حذف نشست و کاهش فشار جانبی.',
                    'Lightweight embankment over soft ground and backfill behind retaining walls, designing out settlement and lateral pressure.',
                    'ردم خفيف فوق الأتربة الرخوة وخلف الجدران الاستنادية، لإلغاء الهبوط وخفض الضغط الجانبي.',
                ),
                'description' => $this->t(
                    "روی بستر نرم، افزودن خاکریز به معنی افزودن نشست است. سبکدانه با یک‌پنجم وزن خاک، همان ارتفاع را با یک‌پنجم بار می‌سازد.\n\nپشت دیوار حائل دو اثر هم‌زمان دارد: فشار جانبی کمتر به دلیل وزن کمتر، و حذف فشار آب منفذی به دلیل زهکشی آزاد. هر دو مستقیماً روی ضخامت و آرماتور دیوار اثر می‌گذارند.",
                    "On soft ground, adding embankment means adding settlement. At a fifth of the weight of soil, expanded clay builds the same height for a fifth of the load.\n\nBehind a retaining wall it does two things at once: less lateral pressure because it weighs less, and no pore water pressure because it drains freely. Both feed straight back into wall thickness and reinforcement.",
                    "فوق الأرض الرخوة، تعني إضافة الردمية إضافة الهبوط. وبخُمس وزن التربة، يبني الطين الممدد الارتفاع نفسه بخُمس الحمل.\n\nوخلف الجدار الاستنادي يؤدي دورين معاً: ضغط جانبي أقل لخفة وزنه، وإلغاء ضغط الماء المسامي لتصريفه الحر. وكلاهما ينعكس مباشرة على سماكة الجدار وتسليحه.",
                ),
                'benefits' => [
                    $this->t('کاهش تا ۸۰ درصدی بار وارد بر بستر', 'Up to 80% less load on the subgrade', 'خفض الحمل على طبقة الأساس حتى ٨٠٪'),
                    $this->t('زهکشی آزاد و حذف فشار آب منفذی', 'Free-draining: no pore water pressure', 'تصريف حر يلغي ضغط الماء المسامي'),
                    $this->t('عایق حرارتی و جلوگیری از نفوذ یخبندان', 'Insulating: limits frost penetration', 'عازل يحدّ من تغلغل الصقيع'),
                    $this->t('پایدار در بلندمدت و بدون تجزیه', 'Long-term stable, does not decompose', 'ثابت بعيد المدى ولا يتحلل'),
                ],
                'products' => ['leca-infra-10-30'],
            ],

            [
                'slug' => 'green-roofs',
                'name' => $this->t('بام سبز', 'Green roofs', 'الأسطح الخضراء'),
                'summary' => $this->t(
                    'لایه زهکش و بخشی از بستر کشت، با کمترین وزن اشباع.',
                    'The drainage course and part of the growing medium, at the lowest saturated weight available.',
                    'طبقة التصريف وجزء من وسط الزراعة، بأدنى وزن مشبع متاح.',
                ),
                'description' => $this->t(
                    "محدودکننده اصلی هر بام سبز، ظرفیت باربری سقف در حالت اشباع است. زهکش شنی در حالت اشباع سنگین است؛ سبکدانه در همان ضخامت، کسری از آن وزن دارد.\n\nهم‌زمان، تخلخل دانه بخشی از آب بارش را نگه می‌دارد و در فاصله میان دو آبیاری آزاد می‌کند — که هم مصرف آب را کم می‌کند و هم بار رواناب شهری را.",
                    "What limits any green roof is the slab's capacity when everything is saturated. A gravel drainage course is heavy wet; expanded clay at the same depth weighs a fraction of it.\n\nAt the same time the granule's porosity retains part of the rainfall and releases it between waterings — which cuts both irrigation demand and the peak load on urban stormwater drainage.",
                    "ما يحدّ أي سطح أخضر هو قدرة البلاطة عند التشبّع الكامل. فطبقة التصريف الحصوية ثقيلة عند الابتلال، بينما يزن الطين الممدد بالعمق نفسه جزءاً يسيراً منها.\n\nوفي الوقت ذاته تحتجز مسامية الحبيبة جزءاً من مياه الأمطار وتطلقه بين الريّات، ما يقلل حاجة الري وذروة تصريف مياه الأمطار الحضرية معاً.",
                ),
                'benefits' => [
                    $this->t('کمترین وزن اشباع در میان مصالح زهکش', 'The lowest saturated weight among drainage materials', 'أدنى وزن مشبع بين مواد التصريف'),
                    $this->t('ذخیره و آزادسازی تدریجی رطوبت', 'Stores moisture and releases it gradually', 'يخزّن الرطوبة ويطلقها تدريجياً'),
                    $this->t('مقاوم در برابر یخبندان و بدون تجزیه', 'Frost-resistant and non-degrading', 'مقاوم للصقيع ولا يتحلل'),
                    $this->t('کاهش ذره‌ای رواناب سطحی', 'Reduces peak stormwater runoff', 'يقلل ذروة الجريان السطحي'),
                ],
                'products' => ['leca-greenroof-8-16', 'leca-garden-4-10'],
            ],

            [
                'slug' => 'horticulture-hydroponics',
                'name' => $this->t('کشاورزی و هیدروپونیک', 'Horticulture & hydroponics', 'البستنة والزراعة المائية'),
                'summary' => $this->t(
                    'بستر کشت خنثی، عاری از بیماری و قابل استفاده مجدد.',
                    'An inert, disease-free and reusable growing medium.',
                    'وسط زراعة خامل وخالٍ من الأمراض وقابل لإعادة الاستخدام.',
                ),
                'description' => $this->t(
                    "دانه‌ها در دمای بالای ۱٬۱۰۰ درجه پخته می‌شوند؛ نتیجه بستری است کاملاً عاری از بذر علف هرز، قارچ و نماتد، بدون هیچ ضدعفونی اولیه.\n\nخنثی بودن شیمیایی به این معناست که EC و pH محلول غذایی را جابه‌جا نمی‌کند — مزیتی که در هیدروپونیک تعیین‌کننده است. پس از پایان دوره کشت، بستر شسته و ضدعفونی و دوباره استفاده می‌شود.",
                    "The granules are fired above 1,100 °C, which leaves a medium entirely free of weed seed, fungi and nematodes with no initial sterilisation.\n\nBeing chemically inert, it does not shift the EC or pH of the nutrient solution — decisive in hydroponics. At the end of a crop cycle the medium is washed, sterilised and used again.",
                    "تُحرق الحبيبات فوق ١٬١٠٠ درجة مئوية، ما يترك وسطاً خالياً تماماً من بذور الأعشاب والفطريات والنيماتودا دون أي تعقيم أولي.\n\nوكونه خاملاً كيميائياً فإنه لا يزيح التوصيلية الكهربائية أو الرقم الهيدروجيني للمحلول المغذي، وهو أمر حاسم في الزراعة المائية. وفي نهاية الدورة يُغسل الوسط ويُعقَّم ويُستخدم من جديد.",
                ),
                'benefits' => [
                    $this->t('عاری از بذر علف هرز و عوامل بیماری‌زا', 'Free of weed seed and pathogens', 'خالٍ من بذور الأعشاب ومسببات الأمراض'),
                    $this->t('خنثی از نظر شیمیایی؛ بدون تغییر EC و pH', 'Chemically inert: does not shift EC or pH', 'خامل كيميائياً ولا يغيّر التوصيلية أو الحموضة'),
                    $this->t('تهویه ریشه همراه با ذخیره رطوبت', 'Root aeration together with moisture storage', 'تهوية للجذور مع احتجاز الرطوبة'),
                    $this->t('قابل استفاده مجدد پس از شست‌وشو', 'Reusable after washing', 'قابل لإعادة الاستخدام بعد الغسل'),
                ],
                'products' => ['leca-garden-4-10'],
            ],

            [
                'slug' => 'thermal-insulation',
                'name' => $this->t('عایق‌کاری حرارتی', 'Thermal insulation', 'العزل الحراري'),
                'summary' => $this->t(
                    'عایق معدنی، غیرقابل اشتعال و بدون افت عملکرد در طول عمر ساختمان.',
                    'A mineral, non-combustible insulant with no performance loss over the building\'s life.',
                    'عازل معدني غير قابل للاشتعال ولا يفقد أداءه طوال عمر المبنى.',
                ),
                'description' => $this->t(
                    "عایق‌های آلی با زمان و رطوبت افت می‌کنند و در حریق بار آتش اضافه می‌کنند. سبکدانه هیچ‌کدام را ندارد: صد درصد معدنی، رده واکنش در برابر آتش A1، و پایدار تا حدود ۱٬۱۵۰ درجه سانتی‌گراد.\n\nکاربردهای رایج: پرکردن حفره دیوار دوجداره، عایق‌کاری کف روی خاک، پرکردن اطراف لوله‌های حرارتی مدفون، و عایق‌کاری کوره و دودکش صنعتی.",
                    "Organic insulants lose performance with time and moisture, and add fire load in a blaze. Expanded clay does neither: fully mineral, Euroclass A1 reaction to fire, stable to about 1,150 °C.\n\nTypical uses: filling cavity walls, insulating ground-bearing floors, packing around buried heating pipes, and lining industrial kilns and flues.",
                    "تفقد العوازل العضوية أداءها مع الوقت والرطوبة، وتضيف حملاً حرارياً عند الحريق. أما الطين الممدد فلا يفعل أياً منهما: معدني بالكامل، وتصنيف A1 لرد الفعل تجاه الحريق، وثابت حتى نحو ١٬١٥٠ درجة مئوية.\n\nالاستخدامات المعتادة: ملء الجدران المزدوجة، وعزل الأرضيات المستندة على التربة، والحشو حول أنابيب التدفئة المدفونة، وتبطين الأفران والمداخن الصناعية.",
                ),
                'benefits' => [
                    $this->t('ضریب هدایت حرارتی حدود ۰٫۰۹ تا ۰٫۱۱ وات بر متر کلوین', 'Thermal conductivity around 0.09–0.11 W/m·K', 'موصلية حرارية نحو ٠٫٠٩–٠٫١١ واط/م·ك'),
                    $this->t('غیرقابل اشتعال، رده A1', 'Non-combustible, Euroclass A1', 'غير قابل للاشتعال، تصنيف A1'),
                    $this->t('بدون افت عملکرد در اثر رطوبت یا گذر زمان', 'No performance loss from moisture or age', 'لا يفقد أداءه بالرطوبة أو بمرور الزمن'),
                    $this->t('مقاوم در برابر جوندگان و حشرات', 'Resistant to rodents and insects', 'مقاوم للقوارض والحشرات'),
                ],
                'products' => ['leca-fill-3-10', 'leca-structure-10-20'],
            ],

            [
                'slug' => 'water-filtration',
                'name' => $this->t('فیلتراسیون و تصفیه', 'Filtration & water treatment', 'الترشيح ومعالجة المياه'),
                'summary' => $this->t(
                    'بستر فیلتر و حامل بیوفیلم در تصفیه فاضلاب و سیستم‌های زهکشی.',
                    'Filter bed and biofilm carrier in wastewater treatment and drainage systems.',
                    'وسط ترشيح وحامل للأغشية الحيوية في معالجة الصرف وأنظمة التصريف.',
                ),
                'description' => $this->t(
                    "سطح داخلی متخلخل دانه، بستر مناسبی برای تشکیل بیوفیلم فراهم می‌کند؛ به همین دلیل در فیلترهای بیولوژیک و سیستم‌های تصفیه گیاهی به کار می‌رود.\n\nخنثی بودن شیمیایی و مقاومت در برابر خردشدگی، عمر بستر را بدون نیاز به تعویض دوره‌ای طولانی می‌کند.",
                    "The granule's porous internal surface gives biofilm somewhere to establish, which is why it is used in trickling filters and constructed wetlands.\n\nBeing chemically inert and resistant to attrition, the bed lasts without the periodic replacement other media need.",
                    "يمنح السطح الداخلي المسامي للحبيبة موضعاً تتكوّن فيه الأغشية الحيوية، ولذلك يُستخدم في المرشّحات البيولوجية والأراضي الرطبة المُنشأة.\n\nوكونه خاملاً كيميائياً ومقاوماً للتآكل، يطول عمر الوسط دون الاستبدال الدوري الذي تحتاجه الأوساط الأخرى.",
                ),
                'benefits' => [
                    $this->t('سطح ویژه بالا برای تشکیل بیوفیلم', 'High specific surface for biofilm growth', 'مساحة نوعية عالية لنمو الأغشية الحيوية'),
                    $this->t('نفوذپذیری بالا و گرفتگی کمتر', 'High permeability, slower clogging', 'نفاذية عالية وانسداد أبطأ'),
                    $this->t('خنثی و بدون آزادسازی مواد به آب', 'Inert: releases nothing into the water', 'خامل ولا يطلق مواد في الماء'),
                    $this->t('عمر بلند بدون تعویض دوره‌ای', 'Long service life without periodic replacement', 'عمر خدمة طويل دون استبدال دوري'),
                ],
                'products' => ['leca-greenroof-8-16', 'leca-infra-10-30'],
            ],
        ];
    }
}

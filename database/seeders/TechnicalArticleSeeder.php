<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use App\Support\Navigation;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * Four technical articles, each answering a question people actually type.
 *
 *     php artisan db:seed --class=TechnicalArticleSeeder
 *
 * `firstOrCreate`, not `updateOrCreate`: these are a starting draft that an
 * editor is expected to rewrite, and a second run must not silently replace
 * their work with the shipped text. Delete a post from the panel and re-run to
 * get the original back.
 *
 * On the writing: each piece answers one question with numbers a reader can
 * check, and none of them repeats the two articles already in EditorialSeeder.
 * That is the whole SEO strategy — a page that answers the question keeps the
 * visitor, and pages that keep visitors are what rank. The search terms are in
 * the titles because they are what the subject is called, not as a garnish.
 */
class TechnicalArticleSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        foreach ($this->posts() as $post) {
            Post::firstOrCreate(['slug' => $post['slug']], [...$post, 'is_active' => true]);
        }

        // The sitemap is cached for six hours. Saving from the panel clears it;
        // a seeder does not, so without this the new articles would be absent
        // from the sitemap until long after the run that published them.
        Navigation::flush();
    }

    /** @return list<array<string, mixed>> */
    private function posts(): array
    {
        return [
            [
                'slug' => 'leca-vs-natural-pumice',
                'type' => 'article',
                'reading_minutes' => 6,
                'published_at' => now()->subDays(3),
                'title' => $this->t(
                    'پوکه لیکا یا پوکه معدنی؟ تفاوت در سه عدد',
                    'Expanded clay or natural pumice? The difference in three numbers',
                    'ليكا أم البوزولان الطبيعي؟ الفرق في ثلاثة أرقام',
                ),
                'meta_title' => $this->t(
                    'تفاوت پوکه لیکا و پوکه معدنی؛ انتخاب سبکدانه صنعتی',
                    'Expanded clay aggregate versus natural pumice',
                    'الفرق بين ركام الطين الممدد والبوزولان الطبيعي',
                ),
                'meta_description' => $this->t(
                    'پوکه لیکا از کوره بیرون می‌آید و پوکه معدنی از معدن. تفاوت در یکنواختی دانه‌بندی، جذب آب و مقاومت است — و در اینکه کدام را می‌توان در طرح اختلاط تکرار کرد.',
                    'One comes out of a kiln, the other out of a quarry. The difference shows in grading, absorption and strength — and in which a mix design can rely on.',
                    'أحدهما يخرج من فرن والآخر من محجر. يظهر الفرق في انتظام التدرّج والامتصاص والمقاومة، وفي أيّهما يمكن لتصميم الخلطة الاعتماد عليه.',
                ),
                'excerpt' => $this->t(
                    'هر دو سبک‌اند و هر دو را پوکه می‌نامند، اما یکی از کوره بیرون می‌آید و دیگری از معدن. تفاوت اصلی، تکرارپذیری است.',
                    'Both are light and both get called pumice, but one comes out of a kiln and the other out of a quarry. The real difference is repeatability.',
                    'كلاهما خفيف ويُسمّى بوزولاناً، لكن أحدهما يخرج من فرن والآخر من محجر. والفارق الحقيقي هو قابلية التكرار.',
                ),
                'body' => $this->t(
                    "در بازار ایران هر دو با نام «پوکه» خرید و فروش می‌شوند و همین باعث بیشترین اشتباه در سفارش است. پوکه معدنی سنگ آتشفشانی متخلخل است که استخراج، سرند و بارگیری می‌شود. پوکه صنعتی لیکا از پخت رس در کوره دوار در دمای حدود ۱٬۲۰۰ درجه به دست می‌آید؛ دانه در کوره منبسط می‌شود و پوسته‌ای نیمه‌شیشه‌ای روی آن می‌نشیند. این یک تفاوت در قیمت نیست، تفاوت در رفتار مصالح است.\n\nعدد اول: پراکندگی دانه‌بندی. پوکه معدنی محصول یک معدن است و ترکیب آن از جبهه‌کاری به جبهه‌کاری و از محموله به محموله فرق می‌کند. سبکدانه صنعتی روی یک بازهٔ مشخص — مثلاً ۴ تا ۱۰ میلی‌متر — تولید و سرند می‌شود و همان بازه در محمولهٔ بعدی هم تحویل داده می‌شود. اگر طرح اختلاط دارید، این تنها عددی است که اهمیت دارد: طرحی که روی یک محموله جواب داده، روی محمولهٔ بعدی هم باید جواب بدهد.\n\nعدد دوم: جذب آب. تخلخل پوکه معدنی باز و نامنظم است و آب را بیشتر و پیش‌بینی‌ناپذیرتر می‌گیرد. پوستهٔ بستهٔ دانهٔ لیکا جذب را محدود و قابل اندازه‌گیری می‌کند، و همین است که اجازه می‌دهد پیش‌اشباع را حساب کنید به‌جای آنکه اسلامپ را در راه از دست بدهید.\n\nعدد سوم: مقاومت فشاری دانه به ازای وزن مخصوص. اینجا مقایسهٔ منصفانه، مقاومت در یک چگالی برابر است نه مقاومت مطلق. دانهٔ صنعتی به‌خاطر ساختار یکنواخت و پوستهٔ سخت، در چگالی برابر عدد بالاتری می‌دهد — و مهم‌تر، عددش انحراف کمتری دارد.\n\nپس پوکه معدنی کجا کافی است؟ وقتی کار پرکردن است نه باربری: پرکردن فضای مرده، شیب‌بندی‌های غیرحساس، و پروژه‌هایی که معدن نزدیک است و هزینهٔ حمل حرف اول را می‌زند. در این کارها یکنواختی ارزش پولی که بابتش می‌دهید را ندارد.\n\nو کجا نه؟ هر جا که عدد باید تکرار شود: بتن سبک سازه‌ای، کارهایی که دفترچهٔ مشخصات و گواهی آنالیز می‌خواهند، صادرات، و هر پروژه‌ای که مصرفش آن‌قدر بزرگ است که چند محموله طول بکشد. در این موارد، هزینهٔ یک محمولهٔ خارج از مشخصات از تفاوت قیمت بیشتر است.\n\nقاعدهٔ عملی: اگر کسی از شما دیتاشیت می‌خواهد، سبکدانه صنعتی بخرید. اگر کسی نمی‌پرسد و کار باربر نیست، پوکه معدنی محلی احتمالاً ارزان‌تر تمام می‌شود.",
                    "Both trade under the same word in the local market, and that is where most ordering mistakes begin. Natural pumice is a porous volcanic rock: quarried, screened, loaded. Expanded clay aggregate comes out of a rotary kiln at around 1,200 °C, where the granule bloats and takes on a semi-vitrified shell. This is not a difference in price. It is a difference in how the material behaves.\n\nThe first number: grading spread. Natural pumice is the product of a quarry, and its composition shifts from face to face and load to load. Industrial aggregate is produced and screened to a stated fraction — 4 to 10 mm, say — and the next consignment arrives in that same fraction. If you are working to a mix design, this is the only number that matters: a design that worked on one load has to work on the next.\n\nThe second number: water absorption. The porosity of natural pumice is open and irregular, so it takes up more water and less predictably. The closed shell on an expanded clay granule keeps absorption bounded and measurable, which is what lets you calculate a pre-soak instead of losing slump in transit.\n\nThe third number: crushing strength against bulk density. The fair comparison here is strength at equal density, not strength outright. The industrial granule, with its uniform structure and hard shell, returns a higher figure at the same density — and, more usefully, one that varies less.\n\nSo where is natural pumice enough? Where the job is filling rather than carrying: dead space, uncritical falls, and projects close enough to a quarry that haulage decides the cost. On that work, consistency is not worth what you pay for it.\n\nAnd where is it not? Anywhere the number has to repeat: structural lightweight concrete, work that comes with a specification and wants a certificate of analysis, export, and any project large enough to run across several consignments. There, one out-of-spec load costs more than the price difference.\n\nA working rule: if somebody is asking you for a datasheet, buy industrial aggregate. If nobody is asking and the material is not carrying load, local pumice will probably come out cheaper.",
                    "يُتداول كلاهما بالاسم نفسه في السوق المحلية، ومن هنا تبدأ معظم أخطاء الطلب. البوزولان الطبيعي صخر بركاني مسامي يُستخرج ويُغربل ويُحمَّل. أما ركام الطين الممدد فيخرج من فرن دوّار عند نحو ١٬٢٠٠ درجة، حيث تنتفخ الحبيبة وتكتسب قشرة شبه زجاجية. وهذا ليس فرقاً في السعر بل في سلوك المادة.\n\nالرقم الأول: تشتّت التدرّج. البوزولان الطبيعي نتاج محجر، وتركيبه يتغيّر من جبهة إلى أخرى ومن حمولة إلى أخرى. أما الركام الصناعي فيُنتج ويُغربل على مقاس معلن — من ٤ إلى ١٠ مم مثلاً — وتصل الشحنة التالية بالمقاس نفسه. وإن كنت تعمل بتصميم خلطة فهذا هو الرقم الوحيد المهم: ما نجح على حمولة يجب أن ينجح على التالية.\n\nالرقم الثاني: امتصاص الماء. مسامية البوزولان الطبيعي مفتوحة وغير منتظمة، فيمتص ماءً أكثر وبصورة أقل قابلية للتنبؤ. أما القشرة المغلقة لحبيبة الطين الممدد فتُبقي الامتصاص محدوداً وقابلاً للقياس، وهو ما يتيح حساب النقع المسبق بدل فقدان الهبوط أثناء النقل.\n\nالرقم الثالث: مقاومة التكسير مقابل الكثافة الظاهرية. والمقارنة المنصفة هنا هي المقاومة عند كثافة متساوية لا المقاومة المطلقة. الحبيبة الصناعية، ببنيتها المنتظمة وقشرتها الصلبة، تعطي رقماً أعلى عند الكثافة نفسها، والأهم أنه رقم أقل تبايناً.\n\nفأين يكفي البوزولان الطبيعي؟ حيث العمل ردم لا حمل: الفراغات الميتة، والميول غير الحسّاسة، والمشاريع القريبة من محجر بحيث يحسم النقل الكلفة. في هذه الأعمال لا يساوي الانتظام ثمنه.\n\nوأين لا يكفي؟ حيثما وجب أن يتكرّر الرقم: الخرسانة الإنشائية خفيفة الوزن، والأعمال التي تأتي بمواصفة وتطلب شهادة تحليل، والتصدير، وأي مشروع يكبر حتى يمتدّ على عدة شحنات. هناك تكلّف حمولة واحدة خارج المواصفة أكثر من فرق السعر.\n\nقاعدة عملية: إن كان أحد يطلب منك ورقة بيانات فاشترِ ركاماً صناعياً. وإن لم يسأل أحد ولم تكن المادة حاملة لحمل، فالبوزولان المحلي أرخص على الأرجح.",
                ),
            ],

            [
                'slug' => 'screed-volume-per-square-metre',
                'type' => 'article',
                'reading_minutes' => 5,
                'published_at' => now()->subDays(8),
                'title' => $this->t(
                    'شیب‌بندی با سبکدانه: چند متر مکعب برای صد متر مربع؟',
                    'Sloped screeds: how many cubic metres per hundred square metres?',
                    'الميول بالركام الخفيف: كم متراً مكعباً لكل مئة متر مربع؟',
                ),
                'meta_title' => $this->t(
                    'محاسبه مصرف سبکدانه در شیب‌بندی و کف‌سازی',
                    'Calculating lightweight aggregate for screeds and levelling',
                    'حساب كمية الركام الخفيف للميول والتسوية',
                ),
                'meta_description' => $this->t(
                    'حجم سبکدانه برای شیب‌بندی از ضخامت میانگین به دست می‌آید نه از ضخامت کمینه. یک مثال عددی کامل برای صد متر مربع، به‌همراه ضریبی که باید به سفارش اضافه کنید.',
                    'Screed volume comes from average thickness, not minimum thickness. A worked example over a hundred square metres, and the allowance to add to the order.',
                    'يُحسب حجم الميول من متوسط السماكة لا من أدناها. مثال عددي كامل لمئة متر مربع، مع النسبة التي يجب إضافتها إلى الطلب.',
                ),
                'excerpt' => $this->t(
                    'رایج‌ترین اشتباه در سفارش، ضرب مساحت در ضخامت کمینه است. شیب‌بندی یک گُوِه است، نه یک ورق هم‌ضخامت.',
                    'The commonest ordering mistake is multiplying area by the minimum thickness. A fall is a wedge, not a slab of even depth.',
                    'أشيع خطأ في الطلب هو ضرب المساحة في أدنى سماكة. فالميل إسفين لا بلاطة متساوية العمق.',
                ),
                'body' => $this->t(
                    "سفارش کم و سفارش زیاد هر دو هزینه دارند: یکی کار را وسط زمین متوقف می‌کند و دیگری متر مکعبی روی دست شما می‌گذارد که جایی برای انبار کردنش نیست. حساب درست دو خط بیشتر نیست.\n\nشیب‌بندی یک گُوِه است. ضخامت از کمترین نقطه شروع می‌شود و تا آبرو یا لبه بالا می‌رود، پس حجم را باید با ضخامت میانگین حساب کرد: میانگین برابر است با نصفِ مجموع کمترین و بیشترین ضخامت.\n\nیک مثال کامل. سطح ۱۰۰ متر مربع، طول مسیر شیب ۱۲ متر، شیب ۱٫۵ درصد، و ضخامت کمینه ۵ سانتی‌متر روی نقطهٔ تخلیه.\n\nافزایش ارتفاع در طول مسیر: ۱۲ متر × ۱٫۵٪ = ۱۸ سانتی‌متر. پس بیشترین ضخامت می‌شود ۵ + ۱۸ = ۲۳ سانتی‌متر. ضخامت میانگین: (۵ + ۲۳) ÷ ۲ = ۱۴ سانتی‌متر، یعنی ۰٫۱۴ متر. حجم درجا: ۱۰۰ × ۰٫۱۴ = ۱۴ متر مکعب.\n\nحالا ضریب. بین حجم درجا و حجمی که باید سفارش بدهید سه چیز فاصله می‌اندازد: تراکم زیر ماله و ویبره، نشست در کنج‌ها و اطراف کرسی‌چینی‌ها، و افت حمل و تخلیه. ۸ تا ۱۲ درصد اضافه کنید. با ۱۰ درصد، سفارش می‌شود حدود ۱۵٫۵ متر مکعب برای هر ۱۰۰ متر مربع.\n\nیک نکته که فاکتور را جابه‌جا می‌کند: سبکدانه فله بر اساس حجم انباشتهٔ شل فروخته می‌شود، نه حجم متراکم درجا. عددی که بالا حساب کردیم حجم درجاست؛ وقتی استعلام می‌گیرید بگویید کدام را می‌گویید، وگرنه دو طرف دربارهٔ دو عدد متفاوت حرف می‌زنند.\n\nدانه‌بندی هم روی همین حساب اثر دارد. برای شیب‌بندی معمولاً گرید ۴ تا ۱۰ میلی‌متر انتخاب می‌شود: به‌اندازهٔ کافی درشت که سبک بماند و به‌اندازهٔ کافی ریز که سطح با ماله بسته شود. اگر روی این لایه کف‌پوش نازک اجرا می‌شود، یک لایهٔ رویهٔ ۲ سانتی‌متری با گرید ۰ تا ۳ حساب کنید و آن را جداگانه به حجم اضافه کنید — این همان دو سانتی‌متری است که معمولاً از قلم می‌افتد.",
                    "Ordering short and ordering long both cost money: one stops the job mid-floor, the other leaves you with cubic metres and nowhere to put them. The arithmetic is two lines.\n\nA fall is a wedge. Thickness starts at the low point and rises to the outlet or the edge, so volume has to be worked from the average: average equals minimum plus maximum, halved.\n\nA worked example. 100 m² of floor, 12 m of run, a fall of 1.5%, and 5 cm of cover at the low point.\n\nRise over the run: 12 m × 1.5% = 18 cm. Maximum thickness is therefore 5 + 18 = 23 cm. Average thickness: (5 + 23) ÷ 2 = 14 cm, or 0.14 m. In-place volume: 100 × 0.14 = 14 m³.\n\nNow the allowance. Three things sit between in-place volume and what you should order: compaction under float and vibration, settlement in corners and around upstands, and losses in transport and discharge. Add 8 to 12%. At 10%, the order comes to roughly 15.5 m³ per 100 m².\n\nOne detail that moves the invoice: bulk aggregate is sold by loose volume, not by compacted in-place volume. The figure above is in-place. Say which one you mean when you ask for a quote, or the two sides are discussing two different numbers.\n\nGrading bears on the same sum. A 4–10 mm fraction is the usual choice for falls: coarse enough to stay light, fine enough to close under a float. If a thin floor finish goes over it, allow a 2 cm topping in 0–3 mm and add that to the volume separately — it is the two centimetres most often left out.",
                    "الطلب الناقص والطلب الزائد كلاهما يكلّف: أحدهما يوقف العمل في منتصف الأرضية، والآخر يترك لديك أمتاراً مكعبة بلا مكان. والحساب سطران لا أكثر.\n\nالميل إسفين. تبدأ السماكة من أخفض نقطة وترتفع حتى المصرف أو الحافة، لذا يُحسب الحجم من المتوسط: المتوسط يساوي أدنى سماكة زائد أقصاها، مقسومَين على اثنين.\n\nمثال كامل. ١٠٠ م² من الأرضية، ١٢ م طول المسار، ميل ١٫٥٪، وتغطية ٥ سم عند أخفض نقطة.\n\nالارتفاع على طول المسار: ١٢ م × ١٫٥٪ = ١٨ سم. فتكون أقصى سماكة ٥ + ١٨ = ٢٣ سم. ومتوسط السماكة: (٥ + ٢٣) ÷ ٢ = ١٤ سم، أي ٠٫١٤ م. والحجم في الموقع: ١٠٠ × ٠٫١٤ = ١٤ م³.\n\nثم النسبة الإضافية. ثلاثة أمور تفصل بين الحجم في الموقع وما ينبغي طلبه: الدمك تحت المسطرين والهزّ، والهبوط في الزوايا وحول القواعد، وفواقد النقل والتفريغ. أضف من ٨ إلى ١٢٪. وعند ١٠٪ يصير الطلب نحو ١٥٫٥ م³ لكل ١٠٠ م².\n\nتفصيل واحد يحرّك الفاتورة: يُباع الركام السائب بالحجم السائب لا بالحجم المدموك في الموقع. والرقم أعلاه هو حجم الموقع. فبيّن أيّهما تقصد حين تطلب عرض سعر، وإلا تحدّث الطرفان عن رقمين مختلفين.\n\nوللتدرّج أثر في الحساب نفسه. المقاس ٤–١٠ مم هو الخيار المعتاد للميول: خشن بما يكفي ليبقى خفيفاً، وناعم بما يكفي ليُغلَق تحت المسطرين. وإن نُفّذ فوقه تشطيب أرضي رقيق، فاحسب طبقة علوية ٢ سم بمقاس ٠–٣ مم وأضفها إلى الحجم على حدة — فهي السنتيمتران الأكثر إغفالاً.",
                ),
            ],

            [
                'slug' => 'what-drives-the-price-of-leca',
                'type' => 'article',
                'reading_minutes' => 5,
                'published_at' => now()->subDays(14),
                'title' => $this->t(
                    'قیمت لیکا چطور تعیین می‌شود؟ پنج عاملی که فاکتور را جابه‌جا می‌کند',
                    'What sets the price of expanded clay aggregate? Five factors',
                    'ما الذي يحدّد سعر ركام الطين الممدد؟ خمسة عوامل',
                ),
                'meta_title' => $this->t(
                    'قیمت سبکدانه لیکا؛ پنج عامل تعیین‌کنندهٔ فاکتور',
                    'The price of expanded clay aggregate: five factors',
                    'سعر ركام الطين الممدد: خمسة عوامل',
                ),
                'meta_description' => $this->t(
                    'قیمت لیکا نرخ ثابتی ندارد و به گرید، حجم، بسته‌بندی، شرایط تحویل و فاصله بستگی دارد. این پنج عامل را بشناسید تا استعلامی بفرستید که یک بار جواب بگیرد.',
                    'No flat rate exists: grade, volume, packaging, delivery terms and distance each move the figure. Know the five and your enquiry gets answered once.',
                    'لا يوجد سعر ثابت: المقاس والكمية والتعبئة وشروط التسليم والمسافة، كلها تحرّك الرقم. ومعرفتها تتيح إرسال طلب يُجاب عنه مرة واحدة.',
                ),
                'excerpt' => $this->t(
                    'هیچ تولیدکننده‌ای نمی‌تواند یک عدد ثابت بدهد، و کسی که می‌دهد احتمالاً چیزی را در فاکتور پنهان کرده است.',
                    'No producer can give you one flat number, and anyone who does has probably buried something in the invoice.',
                    'لا يستطيع أي منتج أن يعطيك رقماً ثابتاً واحداً، ومن يفعل فقد أخفى شيئاً في الفاتورة على الأرجح.',
                ),
                'body' => $this->t(
                    "«قیمت لیکا چند است؟» پرسشی است که بدون پنج عدد دیگر جواب ندارد. این پنج عدد را در استعلام بنویسید تا یک بار جواب بگیرید، نه سه بار.\n\nیک. گرید. گریدهای ریزتر متر مکعب گران‌تری دارند و دلیلش بازدهی است: هر تن رس پخته‌شده مقدار مشخصی دانه در هر بازه می‌دهد، و بازه‌های ریز سهم کمتری از خروجی کوره‌اند. فاصلهٔ قیمت میان ۰ تا ۳ و ۱۰ تا ۲۰ میلی‌متر واقعی است و نه قابل چانه‌زنی.\n\nدو. حجم سفارش. پلهٔ اصلی قیمت جایی است که سفارش به ظرفیت یک تریلی برسد. بارگیری فله در هر تریلی حدود ۶۰ تا ۷۰ متر مکعب است؛ سفارش ۳۰ متر مکعبی همان کرایه را می‌پردازد که سفارش ۶۵ متر مکعبی، پس هزینهٔ حمل هر متر مکعبش تقریباً دو برابر می‌شود. اگر پروژه اجازه می‌دهد، سفارش‌ها را جمع کنید.\n\nسه. بسته‌بندی. فله ارزان‌ترین است و به سیلو یا محل تخلیهٔ آماده نیاز دارد. بیگ‌بگ یک متر مکعبی برای کارگاه‌هایی است که جرثقیل دارند و می‌خواهند مصرف را کنترل کنند. کیسهٔ ۵۰ لیتری گران‌ترین حالت به ازای حجم است و برای کارهای کوچک و خرده‌فروشی معنا دارد. تفاوت میان فله و کیسه در پروژه‌های بزرگ چشمگیر است.\n\nچهار. شرایط تحویل. قیمت درب کارخانه با قیمت تحویل در محل دو عدد متفاوت‌اند، و برای صادرات هم FOB و CIF. بگویید کدام را می‌خواهید؛ مقایسهٔ قیمت درب کارخانهٔ یک تأمین‌کننده با قیمت تحویل دیگری، مقایسهٔ دو چیز نامربوط است.\n\nپنج. فاصله. سبکدانه سبک است ولی حجیم، و کرایهٔ حمل بر اساس حجم بسته می‌شود نه وزن. یعنی سهم حمل از قیمت نهایی بیشتر از چیزی است که برای مصالح سنگین انتظار دارید. در فاصله‌های دور، حمل می‌تواند از خود کالا بیشتر شود.\n\nدو عامل دیگر هم هست که کمتر پیش می‌آید: فصل — تقاضا در ماه‌های ساخت‌وساز بالاتر است — و زمان تحویل، چون سفارش فوری جای سفارش برنامه‌ریزی‌شده را در نوبت بارگیری می‌گیرد.\n\nپس برای گرفتن یک قیمت درست، این‌ها را بنویسید: گرید یا کاربرد، حجم به متر مکعب، نوع بسته‌بندی، مقصد تحویل، و تاریخ نیاز. با همین پنج خط، پیشنهاد قیمتی می‌گیرید که قابل اتکا باشد — و اگر گرید را نمی‌دانید، کاربرد را بنویسید؛ انتخاب گرید کار فروشنده است نه شما.",
                    "\"What does it cost?\" is a question with no answer until five other numbers are on the table. Put them in the enquiry and you get a reply once rather than three times.\n\nOne. Grade. Finer fractions carry a higher rate per cubic metre, and the reason is yield: a tonne of fired clay gives a fixed share of each fraction, and the fine ones are a smaller part of what leaves the kiln. The gap between 0–3 and 10–20 mm is real and not negotiable.\n\nTwo. Order size. The main step in the price is where an order reaches a trailer load. Bulk loading runs about 60 to 70 m³ per trailer; a 30 m³ order pays the same haulage as a 65 m³ one, so its transport cost per cubic metre roughly doubles. Consolidate orders where the programme allows it.\n\nThree. Packaging. Bulk is cheapest and needs a silo or a prepared discharge point. One-cubic-metre bulk bags suit sites with a crane that want to meter consumption. Fifty-litre sacks are the dearest by volume and make sense for small works and retail. On a large project the difference between bulk and sacks is substantial.\n\nFour. Delivery terms. Ex-works and delivered are two different numbers, as are FOB and CIF for export. Say which you want: comparing one supplier's ex-works against another's delivered is comparing two unrelated things.\n\nFive. Distance. The material is light but bulky, and haulage is priced by volume rather than weight. Transport therefore takes a larger share of the final figure than you would expect from heavy aggregate. Over long distances it can exceed the goods.\n\nTwo lesser factors: season, since demand rises through the building months, and lead time, because an urgent order takes a planned one's place in the loading queue.\n\nSo, to get a firm price, state these: grade or application, volume in cubic metres, packaging, delivery destination, and the date you need it. Five lines will get you a quote you can rely on — and if you do not know the grade, give the application instead. Choosing the grade is the seller's job, not yours.",
                    "«كم السعر؟» سؤال بلا جواب حتى تُطرح خمسة أرقام أخرى. اذكرها في طلبك لتحصل على ردّ مرة واحدة لا ثلاثاً.\n\nأولاً: المقاس. المقاسات الأنعم أغلى للمتر المكعب، والسبب هو المردود: يعطي طنّ الطين المحروق حصة ثابتة من كل مقاس، والمقاسات الناعمة جزء أصغر مما يخرج من الفرن. والفارق بين ٠–٣ و١٠–٢٠ مم حقيقي وغير قابل للمساومة.\n\nثانياً: حجم الطلب. الدرجة الأساسية في السعر عند بلوغ الطلب حمولة مقطورة. يبلغ التحميل السائب نحو ٦٠ إلى ٧٠ م³ للمقطورة؛ وطلب ٣٠ م³ يدفع أجرة النقل نفسها التي يدفعها طلب ٦٥ م³، فتتضاعف كلفة نقل متره المكعب تقريباً. فاجمع الطلبات متى سمح البرنامج.\n\nثالثاً: التعبئة. السائب أرخصها ويحتاج صومعة أو نقطة تفريغ مهيأة. والأكياس الكبيرة سعة متر مكعب تناسب المواقع التي لديها رافعة وتريد ضبط الاستهلاك. وأكياس الخمسين لتراً أغلاها بالحجم وتناسب الأعمال الصغيرة والتجزئة. وفي المشاريع الكبيرة يكون الفارق بين السائب والأكياس كبيراً.\n\nرابعاً: شروط التسليم. تسليم المصنع والتسليم في الموقع رقمان مختلفان، وكذلك FOB وCIF للتصدير. فبيّن أيّهما تريد: مقارنة سعر تسليم مصنعٍ لمورّد بسعر تسليمٍ في موقع لمورّد آخر مقارنة بين شيئين لا صلة بينهما.\n\nخامساً: المسافة. المادة خفيفة لكنها ضخمة، وأجرة النقل تُحسب بالحجم لا بالوزن. لذا يأخذ النقل حصة من الرقم النهائي أكبر مما تتوقعه من ركام ثقيل، وقد يتجاوز قيمة البضاعة على المسافات البعيدة.\n\nوثمة عاملان أقل شيوعاً: الموسم، إذ يرتفع الطلب في أشهر البناء، ومهلة التسليم، لأن الطلب العاجل يأخذ مكان المخطّط في دور التحميل.\n\nإذن، للحصول على سعر ثابت اذكر: المقاس أو التطبيق، والحجم بالمتر المكعب، والتعبئة، وجهة التسليم، وتاريخ الحاجة. خمسة أسطر تكفي للحصول على عرض يُعتمد عليه — وإن لم تعرف المقاس فاذكر التطبيق، فاختيار المقاس عمل البائع لا عملك.",
                ),
            ],

            [
                'slug' => 'leca-infill-concrete-grades',
                'type' => 'article',
                'reading_minutes' => 6,
                'published_at' => now()->subDays(20),
                'title' => $this->t(
                    'بتن سبک لیکا برای پرکردن سقف و دیوار: چه گریدی، چه مقاومتی',
                    'Infill lightweight concrete: which grade, which strength',
                    'الخرسانة الخفيفة للردم: أي مقاس وأي مقاومة',
                ),
                'meta_title' => $this->t(
                    'بتن سبک لیکا و سبکدانه بتن؛ انتخاب گرید برای پرکننده',
                    'Lightweight infill concrete: choosing the aggregate grade',
                    'خرسانة الردم خفيفة الوزن: اختيار مقاس الركام',
                ),
                'meta_description' => $this->t(
                    'پرکنندهٔ سبک مقاومت سازه‌ای لازم ندارد و سیمان اضافه فقط وزن و ترک می‌آورد. انتخاب گرید و نسبت اختلاط برای پرکردن سقف، دیوار و کرسی‌چینی.',
                    'A lightweight infill needs no structural strength, and extra cement buys only weight and cracking. Grade and mix selection for slabs, walls and voids.',
                    'لا يحتاج الردم الخفيف مقاومة إنشائية، والإسمنت الزائد لا يشتري إلا الوزن والتشقق. اختيار المقاس والخلطة لردم البلاطات والجدران والفراغات.',
                ),
                'excerpt' => $this->t(
                    'پرکنندهٔ سبک، بتن سازه‌ای نیست. بیشترین اشتباه این است که با آن مثل بتن سازه‌ای رفتار می‌شود — و نتیجه‌اش وزن بیشتر است، نه مقاومت بیشتر.',
                    'An infill is not structural concrete. The commonest mistake is treating it as if it were, and the result is more weight rather than more strength.',
                    'الردم ليس خرسانة إنشائية. وأشيع خطأ معاملته كأنه كذلك، والنتيجة وزن أكبر لا مقاومة أكبر.',
                ),
                'body' => $this->t(
                    "پرکردن با بتن سبک لیکا سه کار می‌کند: وزن مرده را کم می‌کند، فضای مرده را پر می‌کند، و عایق حرارتی و صوتی می‌دهد. هیچ‌کدام از این سه، مقاومت سازه‌ای نیست — و همین است که در اجرا فراموش می‌شود.\n\nاول تکلیف را روشن کنید: این لایه بار می‌برد یا نه؟ اگر روی آن فقط کف‌پوش و تردد معمولی است، پرکنندهٔ غیرسازه‌ای کافی است و هدف، کمترین چگالی ممکن است. اگر روی آن بار متمرکز می‌نشیند یا خودش جزئی از مقطع باربر است، دیگر پرکننده نیست و باید با طرح اختلاط بتن سبک سازه‌ای پیش بروید — که موضوع دیگری است.\n\nگرید برای پرکنندهٔ خالص: ۱۰ تا ۲۰ میلی‌متر. درشت‌ترین دانه، سبک‌ترین نتیجه و کمترین مصرف سیمان را می‌دهد، چون سطح ویژهٔ کمتری دارد که باید پوشانده شود. برای پرکردن فضای بین تیرچه‌ها، بالای سقف‌های قدیمی و پشت دیوارهای حائل، همین انتخاب درست است.\n\nگرید برای لایه‌ای که ماله می‌خورد: ۴ تا ۱۰ میلی‌متر. کمی سنگین‌تر، ولی سطح با ماله بسته می‌شود و برای شیب‌بندی و کف‌سازی مناسب است.\n\nگرید ۰ تا ۳ میلی‌متر جای ماسه را می‌گیرد، نه جای سنگدانهٔ درشت را. آن را برای پرکردن حجم به کار نبرید؛ چگالی را بالا می‌برد و مصرف سیمان را با خودش بالا می‌کشد.\n\nدو اشتباهی که مدام تکرار می‌شود:\n\nسیمان اضافه. حس عمومی این است که سیمان بیشتر یعنی کار محکم‌تر. در پرکنندهٔ سبک، سیمان بیشتر یعنی خمیر بیشتر میان دانه‌ها، یعنی چگالی بالاتر — دقیقاً همان چیزی که می‌خواستید کم کنید — به‌علاوهٔ جمع‌شدگی و ترک بیشتر. برای پرکنندهٔ غیرسازه‌ای، کمترین مقدار سیمانی که دانه‌ها را به هم بچسباند کافی است.\n\nآب اضافه. دانهٔ خشک آب اختلاط را می‌گیرد و مخلوط سفت به نظر می‌رسد، پس آب اضافه می‌شود. سی دقیقه پیش‌اشباع کردن دانه قبل از اختلاط، این وسوسه را از بین می‌برد و از جدا شدن خمیر جلوگیری می‌کند.\n\nو یک نکتهٔ اجرایی: دانهٔ سبک تمایل دارد در مخلوط بالا بیاید. ویبرهٔ طولانی آن را به سطح می‌راند و مقطع را دولایه می‌کند — بالا پر از دانه، پایین پر از خمیر. ویبره را کوتاه‌تر از بتن معمولی بگیرید و به پخش شدن اکتفا کنید.",
                    "Filling with lightweight concrete does three things: it cuts dead load, it fills dead space, and it insulates against heat and sound. None of the three is structural strength — which is precisely what gets forgotten on site.\n\nSettle the question first: does this layer carry load? If all that sits on it is a floor finish and ordinary traffic, a non-structural infill will do and the aim is the lowest density you can get. If concentrated loads land on it, or it forms part of a load-bearing section, it is no longer an infill and belongs in a structural lightweight mix design — a different subject.\n\nGrade for pure infill: 10–20 mm. The coarsest fraction gives the lightest result and the lowest cement demand, because there is less surface area to coat. For filling between joists, over old slabs and behind retaining walls, this is the right choice.\n\nGrade for a layer that gets floated: 4–10 mm. Slightly heavier, but the surface closes under a float, which suits falls and levelling.\n\nThe 0–3 mm fraction replaces sand, not coarse aggregate. Do not use it to fill volume: it raises density and pulls cement consumption up with it.\n\nTwo mistakes that keep recurring:\n\nToo much cement. The instinct is that more cement means a sounder job. In a lightweight infill, more cement means more paste between granules, which means higher density — the very thing you were trying to reduce — along with more shrinkage and cracking. For a non-structural fill, the least cement that binds the granules together is enough.\n\nToo much water. Dry granules take up mixing water, the mix looks stiff, and water gets added. Pre-soaking the aggregate for thirty minutes before mixing removes the temptation and stops the paste separating.\n\nOne point of execution: light granules want to rise in the mix. Prolonged vibration drives them to the surface and leaves the section in two layers — aggregate above, paste below. Vibrate for less time than you would normal-weight concrete, and settle for placing it evenly.",
                    "الردم بالخرسانة خفيفة الوزن يؤدي ثلاثة أعمال: يخفّض الحمل الميت، ويملأ الفراغ الميت، ويعزل حرارياً وصوتياً. وليس أيّ من الثلاثة مقاومةً إنشائية — وهذا بالضبط ما يُنسى في الموقع.\n\nاحسم السؤال أولاً: هل تحمل هذه الطبقة حملاً؟ إن كان فوقها تشطيب أرضي وحركة اعتيادية فحسب، فيكفي ردم غير إنشائي والهدف أدنى كثافة ممكنة. أما إن نزلت عليها أحمال مركّزة أو كانت جزءاً من مقطع حامل، فلم تعد ردماً وموضعها تصميم خلطة خفيفة إنشائية، وذلك موضوع آخر.\n\nالمقاس للردم الخالص: ١٠–٢٠ مم. أخشن مقاس يعطي أخف نتيجة وأقل طلب على الإسمنت، لقلة المساحة السطحية التي يجب تغليفها. وللردم بين الأعصاب وفوق البلاطات القديمة وخلف الجدران الساندة، هذا هو الخيار الصحيح.\n\nالمقاس لطبقة تُمسَّح: ٤–١٠ مم. أثقل قليلاً، لكن السطح يُغلق تحت المسطرين، وهو ما يناسب الميول والتسوية.\n\nأما المقاس ٠–٣ مم فيحلّ محلّ الرمل لا محلّ الركام الخشن. لا تستخدمه لملء الحجم: فهو يرفع الكثافة ويجرّ استهلاك الإسمنت معه.\n\nخطآن يتكرّران باستمرار:\n\nإسمنت زائد. الانطباع السائد أن زيادة الإسمنت تعني عملاً أمتن. وفي الردم الخفيف تعني زيادة الإسمنت عجينة أكثر بين الحبيبات، أي كثافة أعلى — وهو عين ما أردت خفضه — مع مزيد من الانكماش والتشقق. وللردم غير الإنشائي يكفي أقل قدر من الإسمنت يربط الحبيبات.\n\nماء زائد. تمتص الحبيبات الجافة ماء الخلط فتبدو الخلطة جافة فيُضاف الماء. والنقع المسبق ثلاثين دقيقة قبل الخلط يزيل هذا الإغراء ويمنع انفصال العجينة.\n\nوملاحظة تنفيذية: تميل الحبيبة الخفيفة إلى الصعود في الخلطة. والهزّ المطوّل يدفعها إلى السطح ويترك المقطع طبقتين — ركام أعلى وعجينة أسفل. اهزز مدة أقصر مما تفعل مع الخرسانة الاعتيادية، واكتفِ بتوزيعها بانتظام.",
                ),
            ],
        ];
    }
}

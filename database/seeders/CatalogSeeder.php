<?php

namespace Database\Seeders;

use App\Enums\BannerLinkType;
use App\Enums\ProductRelationType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\DisplaySection;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\Slug;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $groceries = $this->category('المقاضي', 'groceries', '#E64A19', 'https://cdn-icons-png.flaticon.com/512/3724/3724788.png', 1);
        $beverages = $this->category('المشروبات والمفرحات', 'beverages', '#0288D1', 'https://cdn-icons-png.flaticon.com/512/2405/2405479.png', 2);
        $homeCare = $this->category('العناية بالمنزل', 'home-care', '#5D4037', 'https://cdn-icons-png.flaticon.com/512/2917/2917995.png', 3);
        $electronics = $this->category('إلكترونيات وأجهزة', 'electronics', '#7B1FA2', 'https://cdn-icons-png.flaticon.com/512/3659/3659899.png', 4);

        $groceriesSection = $this->display('المقاضي', 'groceries', '🍞', 1);
        $beveragesSection = $this->display('المشروبات والمفرحات', 'beverages', '🥤', 2);
        $homeCareSection = $this->display('العناية بالمنزل', 'home-care', '🏠', 3);

        $groceryCats = $this->attachChildren($groceriesSection, $groceries, [
            ['الأطعمة المجمدة', 'https://images.unsplash.com/photo-1623065422902-30a2d299bbe4?w=400&q=80'],
            ['الأرز والحبوب', 'https://images.unsplash.com/photo-1516684732362-966a6d62b9d9?w=400&q=80'],
            ['المعكرونة والمعلبات', 'https://images.unsplash.com/photo-1551183053-bf91b1d5856d?w=400&q=80'],
            ['الزيوت والصلصات', 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&q=80'],
            ['الحليب والألبان', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=400&q=80'],
            ['المشروبات', 'https://images.unsplash.com/photo-1437418543717-4559fdaf7d4b?w=400&q=80'],
            ['الخبز والمخبوزات', 'https://images.unsplash.com/photo-1509440159591-9d5d4a0e7e32?w=400&q=80'],
            ['الخضروات والفواكه', 'https://images.unsplash.com/photo-1610348725531-843dff563e2c?w=400&q=80'],
        ]);

        $beverageCats = $this->attachChildren($beveragesSection, $beverages, [
            ['عصائر طبيعية', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400&q=80'],
            ['مشروبات غازية', 'https://images.unsplash.com/photo-1629203851122-3726ecdf080e?w=400&q=80'],
            ['شاي وقهوة', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&q=80'],
            ['مياه ومعدنية', 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=400&q=80'],
            ['مشروبات الطاقة', 'https://images.unsplash.com/photo-1622543928473-62858afe70ea?w=400&q=80'],
            ['الحليب النباتي', 'https://images.unsplash.com/photo-1563636619-e9143da7973b?w=400&q=80'],
            ['مشروبات رياضية', 'https://images.unsplash.com/photo-1594385208972-0b82951e1c1e?w=400&q=80'],
            ['مركزات وعصائر', 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=400&q=80'],
        ]);

        $homeCats = $this->attachChildren($homeCareSection, $homeCare, [
            ['منظفات المطبخ', 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400&q=80'],
            ['منظفات الحمام', 'https://images.unsplash.com/photo-1584622650111-993a426fbf0c?w=400&q=80'],
            ['معطرات الجو', 'https://images.unsplash.com/photo-1615486511484-92e172cc4fe0?w=400&q=80'],
            ['أدوات المسح', 'https://images.unsplash.com/photo-1517677208171-0bc8725fda30?w=400&q=80'],
            ['غسيل الملابس', 'https://images.unsplash.com/photo-1610557892470-55d9e80c0bce?w=400&q=80'],
            ['ورق ومناديل', 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?w=400&q=80'],
            ['أكياس وقفازات', 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?w=400&q=80'],
            ['مبيدات حشرية', 'https://images.unsplash.com/photo-1581578731548-c64695a6958c?w=400&q=80'],
        ]);

        $frozenKids = $this->addChildren($groceryCats['الأطعمة المجمدة'], [
            ['دجاج مجمد', 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=400&q=80'],
            ['لحوم مجمدة', 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=400&q=80'],
            ['بطاطس مجمدة', 'https://images.unsplash.com/photo-1576107232684-1279f390859f?w=400&q=80'],
            ['خضروات مجمدة', 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=400&q=80'],
            ['أسماك مجمدة', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=400&q=80'],
            ['معجنات مجمدة', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=400&q=80'],
        ]);
        $this->addChildren($groceryCats['الأرز والحبوب'], [
            ['أرز بسمتي', 'https://images.unsplash.com/photo-1516684732362-966a6d62b9d9?w=400&q=80'],
            ['أرز مصري', 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=400&q=80'],
            ['برغل وكسكس', 'https://images.unsplash.com/photo-1509440159591-9d5d4a0e7e32?w=400&q=80'],
            ['عدس وحبوب', 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400&q=80'],
        ]);
        $this->addChildren($groceryCats['المعكرونة والمعلبات'], [
            ['معكرونة', 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec884?w=400&q=80'],
            ['معلبات خضار', 'https://images.unsplash.com/photo-1534483509719-3feaee7c30da?w=400&q=80'],
            ['تونة وسردين', 'https://images.unsplash.com/photo-1553909489-cd47e0907980?w=400&q=80'],
        ]);
        $this->addChildren($groceryCats['الحليب والألبان'], [
            ['حليب طازج', 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=400&q=80'],
            ['أجبان', 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=400&q=80'],
            ['زبادي', 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&q=80'],
            ['لبنة وقشطة', 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?w=400&q=80'],
        ]);
        $this->addChildren($groceryCats['الخضروات والفواكه'], [
            ['فواكه', 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400&q=80'],
            ['خضروات', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&q=80'],
            ['أعشاب', 'https://images.unsplash.com/photo-1466637574441-749b8f2cd37b?w=400&q=80'],
        ]);
        $this->addChildren($beverageCats['عصائر طبيعية'], [
            ['عصير برتقال', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400&q=80'],
            ['عصير تفاح', 'https://images.unsplash.com/photo-1576673442511-7e39b8d4cce4?w=400&q=80'],
            ['كوكتيل فواكه', 'https://images.unsplash.com/photo-1505252585461-04db1eb84625?w=400&q=80'],
        ]);
        $this->addChildren($beverageCats['شاي وقهوة'], [
            ['شاي', 'https://images.unsplash.com/photo-1564890369478-c85903b19e23?w=400&q=80'],
            ['قهوة', 'https://images.unsplash.com/photo-1495474473417-4ef422ad6b86?w=400&q=80'],
            ['نسكافيه', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=400&q=80'],
        ]);
        $this->addChildren($homeCats['منظفات المطبخ'], [
            ['سائل جلي', 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400&q=80'],
            ['منظف أسطح', 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=400&q=80'],
            ['إسفنج ومماسح', 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400&q=80'],
        ]);
        $this->addChildren($homeCats['منظفات الحمام'], [
            ['جل المرحاض', 'https://images.unsplash.com/photo-1584622650111-993a426fbf0c?w=400&q=80'],
            ['منظف البلاط', 'https://images.unsplash.com/photo-1584622781867-1c5fe70b0d73?w=400&q=80'],
        ]);

        $ids = [
            'electronics' => $electronics->id,
            'frozen' => $groceryCats['الأطعمة المجمدة']->id,
            'frozen_chicken' => $frozenKids['دجاج مجمد']->id,
            'frozen_meat' => $frozenKids['لحوم مجمدة']->id,
            'frozen_veg' => $frozenKids['خضروات مجمدة']->id,
            'rice' => $groceryCats['الأرز والحبوب']->id,
            'pasta' => $groceryCats['المعكرونة والمعلبات']->id,
            'oil' => $groceryCats['الزيوت والصلصات']->id,
            'dairy' => $groceryCats['الحليب والألبان']->id,
            'drinks' => $groceryCats['المشروبات']->id,
            'bread' => $groceryCats['الخبز والمخبوزات']->id,
            'produce' => $groceryCats['الخضروات والفواكه']->id,
            'juice' => $beverageCats['عصائر طبيعية']->id,
            'soda' => $beverageCats['مشروبات غازية']->id,
            'tea' => $beverageCats['شاي وقهوة']->id,
            'water' => $beverageCats['مياه ومعدنية']->id,
            'kitchen' => $homeCats['منظفات المطبخ']->id,
            'bathroom' => $homeCats['منظفات الحمام']->id,
            'air' => $homeCats['معطرات الجو']->id,
            'mop' => $homeCats['أدوات المسح']->id,
        ];

        $products = [];
        foreach ($this->productPayloads($ids) as $index => $payload) {
            $products[] = $this->product($payload, $index);
        }

        $bySku = collect($products)->keyBy('sku');
        $discounted = collect($products)->filter(fn (Product $p) => $p->discount_price !== null)->values();

        foreach ($discounted->take(4) as $i => $product) {
            Banner::query()->updateOrCreate(
                ['title' => $product->name],
                [
                    'subtitle' => 'عرض السوبر — وفر الآن',
                    'image_url' => $product->images()->value('url') ?: '',
                    'link_type' => BannerLinkType::Product,
                    'link_id' => $product->id,
                    'sort_order' => $i,
                    'is_active' => true,
                ],
            );
        }

        $this->section('most_requested', 'الأكثر طلباً', null, 1, collect($products)->sortByDesc('review_count')->take(12)->pluck('id'));
        $this->section('fresh_groceries', 'خضروات وفواكه', 'طازجة يومياً من أجود المزارع', 2, collect($products)->whereIn('category_id', [$ids['produce'], $ids['frozen']])->pluck('id'));
        $this->section('best_prices', 'أسعار ولا في الأحلام', 'إلا في روعة الخمسة! 😉', 3, collect($products)->pluck('id'));

        $this->relate($bySku, 'prod_001', 'prod_002', ProductRelationType::Complementary);
        $this->relate($bySku, 'prod_007', 'prod_015', ProductRelationType::Complementary);
        $this->relate($bySku, 'prod_008', 'prod_009', ProductRelationType::Complementary);
        $this->relate($bySku, 'prod_003', 'prod_004', ProductRelationType::Upsell);
        $this->relate($bySku, 'prod_005', 'prod_006', ProductRelationType::Complementary);

        Category::query()->whereIn('slug', ['food', 'cleaning'])->update(['is_active' => false]);

        $this->call(ProductDetailsSeeder::class);
    }

    private function category(string $name, string $slug, string $color, string $icon, int $order): Category
    {
        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'icon_url' => $icon,
                'color' => $color,
                'sort_order' => $order,
                'is_active' => true,
            ],
        );
    }

    private function display(string $name, string $slug, string $emoji, int $order): DisplaySection
    {
        return DisplaySection::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'emoji' => $emoji,
                'sort_order' => $order,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items
     * @return array<string, Category>
     */
    private function attachChildren(DisplaySection $section, Category $parent, array $items): array
    {
        $sync = [];
        $children = [];
        foreach ($items as $i => [$name, $image]) {
            $child = Category::query()->updateOrCreate(
                ['slug' => Slug::from($parent->slug.'-'.$name)],
                [
                    'parent_id' => $parent->id,
                    'name' => $name,
                    'image_url' => $image,
                    'icon_url' => $image,
                    'color' => $parent->color,
                    'sort_order' => $i,
                    'is_active' => true,
                ],
            );
            $sync[$child->id] = ['sort_order' => $i];
            $children[$name] = $child;
        }
        $section->categories()->sync($sync);

        return $children;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items
     * @return array<string, Category>
     */
    private function addChildren(Category $parent, array $items): array
    {
        $children = [];
        foreach ($items as $i => [$name, $image]) {
            $child = Category::query()->updateOrCreate(
                ['slug' => Slug::from($parent->slug.'-'.$name)],
                [
                    'parent_id' => $parent->id,
                    'name' => $name,
                    'image_url' => $image,
                    'icon_url' => $image,
                    'color' => $parent->color,
                    'sort_order' => $i,
                    'is_active' => true,
                ],
            );
            $children[$name] = $child;
        }

        return $children;
    }

    private function section(string $key, string $title, ?string $subtitle, int $order, $productIds): void
    {
        $section = HomeSection::query()->updateOrCreate(
            ['key' => $key],
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'sort_order' => $order,
                'is_active' => true,
            ],
        );

        $sync = [];
        foreach ($productIds->values() as $i => $id) {
            $sync[$id] = ['sort_order' => $i];
        }
        $section->products()->sync($sync);
    }

    private function relate($bySku, string $sku, string $relatedSku, ProductRelationType $type): void
    {
        $product = $bySku->get($sku);
        $related = $bySku->get($relatedSku);
        if (! $product || ! $related) {
            return;
        }

        $product->productRelations()->updateOrCreate(
            [
                'related_product_id' => $related->id,
                'type' => $type->value,
            ],
            ['sort_order' => 0],
        );
    }

    private function product(array $payload, int $index): Product
    {
        $image = $payload['image_url'];
        unset($payload['image_url']);
        $existing = Product::query()->where('sku', $payload['sku'])->first();
        $payload['slug'] = Slug::unique($payload['name'], 'products', 'slug', $existing?->id);
        $payload['sort_order'] = $index;
        $payload['is_active'] = true;

        $product = Product::withTrashed()->where('sku', $payload['sku'])->first();
        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }
            $product->fill($payload)->save();
        } else {
            $product = Product::query()->create($payload);
        }

        $product->images()->delete();
        $product->images()->create([
            'url' => $image,
            'alt' => $product->name,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        return $product->fresh(['images']);
    }

    /**
     * @param  array<string, int>  $ids
     * @return list<array<string, mixed>>
     */
    private function productPayloads(array $ids): array
    {
        return [
            [
                'sku' => 'prod_001',
                'category_id' => $ids['kitchen'],
                'name' => 'سائل غسيل الأطباق النقاء الفائق',
                'description' => 'سائل تنظيف قوي للأطباق والأواني، يزيل الدهون العنيدة بلمسة واحدة.',
                'price' => 12.50,
                'discount_price' => 9.99,
                'image_url' => 'https://images.unsplash.com/photo-1563453392212-326f5e854473?w=400',
                'stock' => 150,
                'rating' => 4.7,
                'review_count' => 243,
                'is_featured' => true,
                'benefits' => ['يزيل الدهون بفاعلية', 'رائحة ليمون منعشة', 'لطيف على البشرة'],
                'keywords' => ['سائل جلي', 'غسيل أطباق', 'منظف مطبخ', 'ليمون'],
                'usage_instructions' => 'ضع بضع قطرات على سفنجة مبللة واغسل الأطباق ثم اشطف.',
            ],
            [
                'sku' => 'prod_002',
                'category_id' => $ids['kitchen'],
                'name' => 'بخاخ تنظيف الأسطح المتعددة كلين برو',
                'description' => 'بخاخ يعقّم ويلمّع الرخام والبلاط والزجاج ويقضي على الجراثيم.',
                'price' => 18.75,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=400',
                'stock' => 89,
                'rating' => 4.5,
                'review_count' => 178,
                'is_featured' => false,
                'benefits' => ['يقضي على 99٪ من البكتيريا', 'لا يترك آثاراً'],
                'keywords' => ['بخاخ تنظيف', 'معقم أسطح', 'منظف منزلي'],
                'usage_instructions' => 'رش من مسافة 20 سم ثم امسح بقطعة قماش نظيفة.',
            ],
            [
                'sku' => 'prod_003',
                'category_id' => $ids['electronics'],
                'name' => 'سماعات لاسلكية NovaBuds Pro',
                'description' => 'سماعات بلوتوث 5.3 مع إلغاء ضوضاء وبطارية 36 ساعة.',
                'price' => 249.00,
                'discount_price' => 199.00,
                'image_url' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=400',
                'stock' => 42,
                'rating' => 4.8,
                'review_count' => 512,
                'is_featured' => true,
                'benefits' => ['إلغاء ضوضاء نشط', 'بطارية طويلة'],
                'keywords' => ['سماعات', 'بلوتوث', 'ANC', 'إيربودز'],
                'usage_instructions' => 'افتح العلبة للإقران ثم اربطها من إعدادات البلوتوث.',
            ],
            [
                'sku' => 'prod_004',
                'category_id' => $ids['electronics'],
                'name' => 'شاحن لاسلكي سريع MagCharge 15W',
                'description' => 'شاحن لاسلكي 15 واط متوافق مع MagSafe والأندرويد.',
                'price' => 85.00,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=400',
                'stock' => 67,
                'rating' => 4.4,
                'review_count' => 89,
                'is_featured' => false,
                'benefits' => ['شحن سريع 15 واط', 'حماية من الحرارة'],
                'keywords' => ['شاحن لاسلكي', 'MagSafe'],
                'usage_instructions' => 'ضع الهاتف في منتصف القاعدة حتى يبدأ الشحن.',
            ],
            [
                'sku' => 'prod_005',
                'category_id' => $ids['produce'],
                'name' => 'تفاح أحمر فاخر 1 كجم',
                'description' => 'تفاح طازج مقرمش من أجود المزارع، حلو ومتوازن الحموضة.',
                'price' => 14.00,
                'discount_price' => 11.50,
                'image_url' => 'https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?w=400',
                'stock' => 200,
                'rating' => 4.6,
                'review_count' => 320,
                'is_featured' => true,
                'benefits' => ['طازج يومياً', 'غني بالألياف'],
                'keywords' => ['تفاح', 'فواكه', 'خضروات وفواكه'],
                'usage_instructions' => 'يُغسل جيداً قبل الأكل ويُحفظ في الثلاجة.',
            ],
            [
                'sku' => 'prod_006',
                'category_id' => $ids['produce'],
                'name' => 'موز كافينديش طازج 1 كجم',
                'description' => 'موز ناضج جاهز للأكل أو للسموذي.',
                'price' => 9.75,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=400',
                'stock' => 180,
                'rating' => 4.5,
                'review_count' => 210,
                'is_featured' => false,
                'benefits' => ['مصدر طاقة سريع', 'غني بالبوتاسيوم'],
                'keywords' => ['موز', 'فواكه'],
                'usage_instructions' => 'يُحفظ في درجة حرارة الغرفة حتى ينضج.',
            ],
            [
                'sku' => 'prod_007',
                'category_id' => $ids['dairy'],
                'name' => 'حليب طازج كامل الدسم 1 لتر',
                'description' => 'حليب يومي مبستر مناسب للشرب والطبخ.',
                'price' => 7.50,
                'discount_price' => 6.25,
                'image_url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=400',
                'stock' => 140,
                'rating' => 4.7,
                'review_count' => 188,
                'is_featured' => true,
                'benefits' => ['طازج يومياً', 'غني بالكالسيوم'],
                'keywords' => ['حليب', 'ألبان'],
                'usage_instructions' => 'يُحفظ مبرداً ويُستهلك خلال أيام من الفتح.',
            ],
            [
                'sku' => 'prod_008',
                'category_id' => $ids['rice'],
                'name' => 'أرز بسمتي فاخر 5 كجم',
                'description' => 'أرز طويل الحبة بنكهة عطرية للولائم اليومية.',
                'price' => 42.00,
                'discount_price' => 36.90,
                'image_url' => 'https://images.unsplash.com/photo-1516684732362-966a6d62b9d9?w=400',
                'stock' => 95,
                'rating' => 4.8,
                'review_count' => 401,
                'is_featured' => true,
                'benefits' => ['حبوب طويلة', 'لا يلتصق عند الطبخ الصحيح'],
                'keywords' => ['أرز', 'حبوب', 'بسمتي'],
                'usage_instructions' => 'يُشطف ثم يُنقع 20 دقيقة قبل الطبخ.',
            ],
            [
                'sku' => 'prod_009',
                'category_id' => $ids['oil'],
                'name' => 'زيت زيتون بكر ممتاز 500 مل',
                'description' => 'زيت زيتون للسلطات والقلي الخفيف بنكهة فاكهية.',
                'price' => 29.00,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400',
                'stock' => 76,
                'rating' => 4.6,
                'review_count' => 134,
                'is_featured' => false,
                'benefits' => ['بكر ممتاز', 'مناسب للسلطات'],
                'keywords' => ['زيت', 'زيتون', 'صلصات'],
                'usage_instructions' => 'يُحفظ بعيداً عن الضوء والحرارة.',
            ],
            [
                'sku' => 'prod_010',
                'category_id' => $ids['juice'],
                'name' => 'عصير برتقال طبيعي 1 لتر',
                'description' => 'عصير برتقال بلا مواد حافظة صناعية ظاهرة على العبوة.',
                'price' => 8.90,
                'discount_price' => 7.50,
                'image_url' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400',
                'stock' => 120,
                'rating' => 4.4,
                'review_count' => 97,
                'is_featured' => false,
                'benefits' => ['طعم طبيعي', 'منعش'],
                'keywords' => ['عصير', 'برتقال', 'مشروبات'],
                'usage_instructions' => 'يُرج جيداً قبل الشرب ويُحفظ مبرداً بعد الفتح.',
            ],
            [
                'sku' => 'prod_011',
                'category_id' => $ids['air'],
                'name' => 'معطر جو برائحة اللافندر',
                'description' => 'رذاذ معطّر للمنسوجات والغرف بثبات مقبول.',
                'price' => 16.00,
                'discount_price' => 12.90,
                'image_url' => 'https://images.unsplash.com/photo-1615486511484-92e172cc4fe0?w=400',
                'stock' => 110,
                'rating' => 4.5,
                'review_count' => 201,
                'is_featured' => false,
                'benefits' => ['رائحة ثابتة', 'سهل الاستخدام'],
                'keywords' => ['معطر', 'روائح', 'لافندر'],
                'usage_instructions' => 'رش من مسافة آمنة على المنسوجات والهواء.',
            ],
            [
                'sku' => 'prod_012',
                'category_id' => $ids['bathroom'],
                'name' => 'جل تنظيف الحمّام والمراحيض المركّز',
                'description' => 'يزيل الأملاح والطبقات مع الالتزام بتعليمات السلامة.',
                'price' => 14.60,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0c?w=400',
                'stock' => 72,
                'rating' => 4.6,
                'review_count' => 112,
                'is_featured' => false,
                'benefits' => ['تركيز عالٍ', 'لمعان واضح'],
                'keywords' => ['مرحاض', 'حمام', 'جل تنظيف'],
                'usage_instructions' => 'اتركه دقائق قصيرة ثم اشطف بالماء جيداً.',
            ],
            [
                'sku' => 'prod_013',
                'category_id' => $ids['frozen_veg'],
                'name' => 'خضروات مجمدة مشكلة 950 جرام',
                'description' => 'مزيج مختار من قطع الخضروات المجمدة سريعة التحضير.',
                'price' => 24.50,
                'discount_price' => 19.90,
                'image_url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=400',
                'stock' => 80,
                'rating' => 4.3,
                'review_count' => 112,
                'is_featured' => false,
                'benefits' => ['تحضير سريع', 'يحفظ الفيتامينات'],
                'keywords' => ['مجمد', 'خضروات', 'مجمدات'],
                'usage_instructions' => 'اطهِ حسب الحاجة في قدر أو ميكروويف.',
            ],
            [
                'sku' => 'prod_014',
                'category_id' => $ids['pasta'],
                'name' => 'معكرونة سباغيتي رقم 7 — 500 جم',
                'description' => 'معكرونة القمح الصلب لمختلف الوصفات اليومية.',
                'price' => 8.95,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1612929633738-8fe44f7ec884?w=400',
                'stock' => 200,
                'rating' => 4.4,
                'review_count' => 88,
                'is_featured' => false,
                'benefits' => ['طبخ سريع', 'قوام متماسك'],
                'keywords' => ['معكرونة', 'سباغيتي', 'باستا'],
                'usage_instructions' => 'سلق في ماء مغلي مع ملح ثم تصفية.',
            ],
            [
                'sku' => 'prod_015',
                'category_id' => $ids['bread'],
                'name' => 'خبز توست أبيض مقطّع 400 جم',
                'description' => 'خبز طري للفطور والساندويش.',
                'price' => 11.50,
                'discount_price' => 9.75,
                'image_url' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=400',
                'stock' => 45,
                'rating' => 4.1,
                'review_count' => 54,
                'is_featured' => false,
                'benefits' => ['طازج يومياً', 'سهل التخزين'],
                'keywords' => ['خبز', 'توست'],
                'usage_instructions' => 'يُخزَّن في مكان جاف وبارد، ويمكن تجميده.',
            ],
            [
                'sku' => 'prod_016',
                'category_id' => $ids['soda'],
                'name' => 'مشروب غازي لايت 320 مل',
                'description' => 'منعش ومثالي بارداً مع الوجبات.',
                'price' => 3.50,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f668f914b99?w=400',
                'stock' => 320,
                'rating' => 4.2,
                'review_count' => 55,
                'is_featured' => false,
                'benefits' => ['منعش', 'يُقدَّم بارداً'],
                'keywords' => ['غازية', 'مشروب'],
                'usage_instructions' => 'يُخدم بارداً.',
            ],
            [
                'sku' => 'prod_017',
                'category_id' => $ids['tea'],
                'name' => 'شاي أحمر فاخر ظروف × 100',
                'description' => 'مذاق اعتيادي يفضّله كثير من الأسر.',
                'price' => 15.75,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1564890369478-c85903b19e23?w=400',
                'stock' => 140,
                'rating' => 4.4,
                'review_count' => 210,
                'is_featured' => false,
                'benefits' => ['نكهة متوازنة', 'سهل التحضير'],
                'keywords' => ['شاي', 'أعشاب'],
                'usage_instructions' => 'انقع حسب الغليان المفضل لديك.',
            ],
            [
                'sku' => 'prod_018',
                'category_id' => $ids['tea'],
                'name' => 'قهوة سريعة الذوبان عبوة كبيرة',
                'description' => 'نكهة غنية ومريحة لمختلف الأوقات.',
                'price' => 42.90,
                'discount_price' => 36.50,
                'image_url' => 'https://images.unsplash.com/photo-1495474473417-4ef422ad6b86?w=400',
                'stock' => 60,
                'rating' => 4.6,
                'review_count' => 89,
                'is_featured' => true,
                'benefits' => ['تحضير فوري', 'نكهة غنية'],
                'keywords' => ['قهوة', 'نسكافيه'],
                'usage_instructions' => 'أضف مع ماء ساخن وحرّك جيداً.',
            ],
            [
                'sku' => 'prod_019',
                'category_id' => $ids['water'],
                'name' => 'مياه معدنية طبيعية 600 مل × 24',
                'description' => 'مياه نقية ومتوازنة للشرب اليومي.',
                'price' => 18.00,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1548839140-50a08942709d?w=400',
                'stock' => 200,
                'rating' => 4.5,
                'review_count' => 120,
                'is_featured' => false,
                'benefits' => ['نقاء عالٍ', 'مناسبة يومياً'],
                'keywords' => ['مياه', 'معدنية'],
                'usage_instructions' => 'تُخزَّن في مكان جاف بعيداً عن الشمس.',
            ],
            [
                'sku' => 'prod_020',
                'category_id' => $ids['drinks'],
                'name' => 'شراب شعير بالليمون 330 مل',
                'description' => 'مشروب منعش للعائلة في الأيام الحارة.',
                'price' => 4.25,
                'discount_price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1437418543717-4559fdaf7d4b?w=400',
                'stock' => 160,
                'rating' => 4.3,
                'review_count' => 73,
                'is_featured' => false,
                'benefits' => ['منعش', 'طعم ليمون'],
                'keywords' => ['مشروبات', 'شعير', 'ليمون'],
                'usage_instructions' => 'يُقدَّم بارداً بعد الرج.',
            ],
            [
                'sku' => 'prod_021',
                'category_id' => $ids['mop'],
                'name' => 'ممسحة أرضيات بألياف مايكروفايبر',
                'description' => 'تساعد على نشر السائل وماصة جيداً.',
                'price' => 35.00,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1517677208171-0bc8725fda30?w=400',
                'stock' => 48,
                'rating' => 4.2,
                'review_count' => 41,
                'is_featured' => false,
                'benefits' => ['امتصاص عالٍ', 'سهلة الغسل'],
                'keywords' => ['ممسحة', 'أدوات', 'مسح'],
                'usage_instructions' => 'اغسل وجفّف قبل التخزين.',
            ],
            [
                'sku' => 'prod_022',
                'category_id' => $ids['frozen_chicken'],
                'name' => 'دجاج مجمد كامل 10 × 700 جم',
                'description' => 'دجاج كامل مجمد جاهز للطبخ، عبوة عائلية.',
                'price' => 123.80,
                'discount_price' => 109.90,
                'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=400',
                'stock' => 36,
                'rating' => 4.5,
                'review_count' => 88,
                'is_featured' => true,
                'benefits' => ['عبوة عائلية', 'مجمد طازج'],
                'keywords' => ['دجاج', 'مجمد', 'دواجن'],
                'usage_instructions' => 'يُذاب في الثلاجة ثم يُطهى جيداً.',
            ],
            [
                'sku' => 'prod_023',
                'category_id' => $ids['frozen_meat'],
                'name' => 'لحم بقري مجمد مقطع 1 كجم',
                'description' => 'قطع لحم بقري مجمدة مناسبة للطبخ اليومي.',
                'price' => 54.00,
                'discount_price' => null,
                'image_url' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=400',
                'stock' => 40,
                'rating' => 4.4,
                'review_count' => 61,
                'is_featured' => false,
                'benefits' => ['تقطيع جاهز', 'يحفظ في الفريزر'],
                'keywords' => ['لحم', 'مجمد', 'بقري'],
                'usage_instructions' => 'يُذاب بالكامل قبل الطبخ.',
            ],
        ];
    }
}

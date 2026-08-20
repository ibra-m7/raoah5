<x-layouts.marketing :title="$title">
    <div class="mkt-orbs" aria-hidden="true">
        <span class="orb orb-a"></span>
        <span class="orb orb-b"></span>
        <span class="orb orb-c"></span>
    </div>

    <header class="mkt-nav">
        <a class="mkt-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $strings::APP_NAME }}">
            <span>{{ $strings::APP_NAME }}</span>
        </a>
        <a class="mkt-nav-cta" href="{{ route('admin.login') }}">{{ $strings::LANDING_CTA }}</a>
    </header>

    <main>
        <section class="mkt-hero">
            <p class="mkt-kicker reveal">{{ $strings::LANDING_KICKER }}</p>
            <h1 class="reveal delay-1">{{ $strings::LANDING_HERO_TITLE }}</h1>
            <p class="mkt-lead reveal delay-2">{{ $strings::LANDING_HERO_BODY }}</p>
            <div class="mkt-actions reveal delay-3">
                <a class="mkt-btn" href="{{ route('admin.login') }}">{{ $strings::LANDING_CTA }}</a>
                <a class="mkt-btn ghost" href="#features">{{ $strings::LANDING_CTA_SECONDARY }}</a>
            </div>
            <div class="mkt-hero-visual reveal delay-4">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $strings::APP_NAME }}">
                <div class="float-card card-a"><i class="bi bi-bag-check"></i> تتبع الطلبات</div>
                <div class="float-card card-b"><i class="bi bi-percent"></i> عروض لحظية</div>
                <div class="float-card card-c"><i class="bi bi-mic"></i> مساعد ذكي</div>
            </div>
        </section>

        <section class="mkt-stats">
            <article class="reveal"><strong data-count="1200">0</strong><span>منتج جاهز للعرض</span></article>
            <article class="reveal delay-1"><strong data-count="48">0</strong><span>دقيقة متوسط التوصيل</span></article>
            <article class="reveal delay-2"><strong data-count="24">0</strong><span>ساعة إدارة مستمرة</span></article>
        </section>

        <section class="mkt-features" id="features">
            <h2 class="reveal">كل ما يحتاجه متجرك في مكان واحد</h2>
            <div class="mkt-grid">
                <article class="reveal">
                    <i class="bi bi-box-seam"></i>
                    <h3>كتالوج احترافي</h3>
                    <p>أدر المنتجات والأقسام والعروض والكوبونات بواجهة واضحة وسريعة.</p>
                </article>
                <article class="reveal delay-1">
                    <i class="bi bi-graph-up-arrow"></i>
                    <h3>مبيعات لحظية</h3>
                    <p>تابع الطلبات والعملاء والتقييمات من لوحة واحدة دون تشتيت.</p>
                </article>
                <article class="reveal delay-2">
                    <i class="bi bi-phone"></i>
                    <h3>يعمل على كل جهاز</h3>
                    <p>تجربة متجاوبة للجوال والتابلت واللابتوب بنفس الفخامة.</p>
                </article>
                <article class="reveal delay-3">
                    <i class="bi bi-shield-check"></i>
                    <h3>دخول آمن للمدراء</h3>
                    <p>صلاحيات الإدارة محمية، والتصفح العام يبقى مفتوحاً للجميع.</p>
                </article>
            </div>
        </section>

        <section class="mkt-cta-band reveal">
            <div>
                <h2>جاهز لإدارة روعة الخمسة؟</h2>
                <p>ادخل لوحة التحكم وابدأ التحديثات التي تظهر في التطبيق فوراً.</p>
            </div>
            <a class="mkt-btn" href="{{ route('admin.login') }}">{{ $strings::LANDING_CTA }}</a>
        </section>
    </main>

    <footer class="mkt-footer">
        <span>{{ $strings::APP_NAME }}</span>
        <small>{{ $strings::APP_TAGLINE }}</small>
    </footer>
</x-layouts.marketing>

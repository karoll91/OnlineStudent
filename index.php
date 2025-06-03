<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
redirect_if_logged_in();

$page_title = 'Home';
?>

<?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section bg-light py-5 mb-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold text-primary mb-4">
                    <i class="fas fa-graduation-cap me-3"></i>
                    Onlayn Ta'lim Tizimi
                </h1>
                <p class="lead mb-4 text-muted">
                    Zamonaviy onlayn talaba ro'yxatdan o'tish va test tizimi.
                    Kurslar, testlar va natijalarni boshqaring.
                    Ta'limda yangi imkoniyatlarni kashf eting!
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Ro'yxatdan O'tish
                    </a>
                    <a href="login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Kirish
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image">
                    <i class="fas fa-laptop-code display-1 text-primary opacity-75"></i>
                    <div class="mt-3">
                        <i class="fas fa-users text-success me-3" style="font-size: 2rem;"></i>
                        <i class="fas fa-book-open text-info me-3" style="font-size: 2rem;"></i>
                        <i class="fas fa-chart-line text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="row text-center mb-5">
        <div class="col">
            <h2 class="h2 fw-bold mb-3">Tizim Imkoniyatlari</h2>
            <p class="text-muted mb-5">Zamonaviy ta'lim tizimining barcha kerakli funksiyalari bir joyda</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                    <h5 class="card-title">Talaba Boshqaruvi</h5>
                    <p class="card-text text-muted">
                        Oson ro'yxatdan o'tish, profil boshqaruvi va shaxsiy ma'lumotlarni yangilash.
                        Barcha ma'lumotlar xavfsiz saqlanadi.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <h5 class="card-title">Interaktiv Testlar</h5>
                    <p class="card-text text-muted">
                        Real-time testlar, avtomatik baholash va natijalar tahlili.
                        Har bir savolga vaqt cheklovi va ball tizimi.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-chart-bar fa-2x"></i>
                    </div>
                    <h5 class="card-title">Natijalar Tahlili</h5>
                    <p class="card-text text-muted">
                        Batafsil hisobotlar, progress tracking va statistik ma'lumotlar.
                        O'z natijalaringizni kuzatib boring.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                    <h5 class="card-title">Kurs Boshqaruvi</h5>
                    <p class="card-text text-muted">
                        Turli kurslar, materiallar va resurslar.
                        Har bir kurs uchun alohida testlar va vazifalar.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h5 class="card-title">Xavfsizlik</h5>
                    <p class="card-text text-muted">
                        Ma'lumotlaringiz xavfsizligi bizning birinchi ustuvorligimiz.
                        Zamonaviy shifrlash texnologiyalari.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-mobile-alt fa-2x"></i>
                    </div>
                    <h5 class="card-title">Mobil Qo'llab-quvvatlash</h5>
                    <p class="card-text text-muted">
                        Barcha qurilmalarda ishlaydigan responsive dizayn.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="bg-primary text-white py-5 rounded mb-5">
        <div class="row text-center">
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">
                        <i class="fas fa-users me-2"></i>500+
                    </h3>
                    <p class="mb-0 fs-5">Faol Talabalar</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">
                        <i class="fas fa-book me-2"></i>50+
                    </h3>
                    <p class="mb-0 fs-5">Mavjud Kurslar</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">
                        <i class="fas fa-clipboard-list me-2"></i>200+
                    </h3>
                    <p class="mb-0 fs-5">Onlayn Testlar</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stat-item">
                    <h3 class="display-4 fw-bold">
                        <i class="fas fa-trophy me-2"></i>95%
                    </h3>
                    <p class="mb-0 fs-5">Muvaffaqiyat Darajasi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How it Works Section -->
    <div class="row text-center mb-5">
        <div class="col">
            <h2 class="h2 fw-bold mb-3">Qanday Ishlaydi?</h2>
            <p class="text-muted mb-5">Tizimdan foydalanish uchun oddiy 4 qadam</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="text-center">
                <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <span class="fw-bold fs-4">1</span>
                </div>
                <h5>Ro'yxatdan O'ting</h5>
                <p class="text-muted">Oddiy forma to'ldiring va elektron pochtangizni tasdiqlang</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="text-center">
                <div class="step-number bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <span class="fw-bold fs-4">2</span>
                </div>
                <h5>Kurslarni Tanlang</h5>
                <p class="text-muted">Qiziqtirgan kurslaringizga yoziling va o'qishni boshlang</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="text-center">
                <div class="step-number bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <span class="fw-bold fs-4">3</span>
                </div>
                <h5>Testlar Bajaring</h5>
                <p class="text-muted">Bilimlaringizni sinab ko'ring va natijalarni oling</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="text-center">
                <div class="step-number bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <span class="fw-bold fs-4">4</span>
                </div>
                <h5>Rivojlanishni Kuzating</h5>
                <p class="text-muted">O'z natijalaringizni tahlil qiling va yanada yaxshilang</p>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-light p-5 rounded text-center">
        <h3 class="fw-bold mb-3">Bugun boshlang!</h3>
        <p class="text-muted mb-4">
            Ta'limdagi yangi imkoniyatlarni kashf eting.
            Bepul ro'yxatdan o'ting va o'z bilimlaringizni sinovdan o'tkazing.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="register.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket me-2"></i>Hoziroq Boshlash
            </a>
            <a href="#features" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-info-circle me-2"></i>Batafsil Ma'lumot
            </a>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
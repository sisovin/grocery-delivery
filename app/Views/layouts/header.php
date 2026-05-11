<?php

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isHome = rtrim($currentPath, '/') === '' || rtrim($currentPath, '/') === '/';
$dashboardPath = $dashboardPath ?? '/login';
$csrfToken = $csrfToken ?? '';
$authUser = $authUser ?? null;
?>

<header>
  <div class="nav-container">
    <a class="logo" href="/">
      <span class="logo-icon">🌿</span>
      Nourish
    </a>

    <div class="nav-links">
      <a href="/">ផ្ទះ</a>
      <a href="#products">ផលិតផល</a>
      <a href="/supplier">កសិដ្ឋាន</a>
      <a href="/customer">ទំនាក់ទំនង</a>
      <?php if (!empty($authUser)): ?>
        <a href="<?= e($dashboardPath) ?>">Dashboard</a>
        <form action="/logout" method="post" class="inline-auth-form">
          <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
          <button type="submit" class="auth-link-btn">Logout</button>
        </form>
      <?php else: ?>
        <a href="/login">Login</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isHome): ?>
    <div class="hero-content">
      <h1>Earth's finest ដឹកជញ្ជូនដល់ផ្ទះអ្នក</h1>
      <p>វេទិកាដឹកជញ្ជូនម្ហូបស្រស់ និងសរីរាង្គដែលមានគុណភាពខ្ពស់ ផ្គត់ផ្គង់ដល់ 24 ខេត្ត/រាជធានីនៅកម្ពុជា។ Nourish your home
        ជាមួយផលិតផលពី Local farms តាមរដូវកាល។</p>
      <div class="hero-badges">
        <div class="badge">🚚 ដឹកជញ្ជូនឥតគិតថ្លៃលើការបញ្ជាទិញលើស $20</div>
        <div class="badge">⚡ Same-Day Delivery</div>
        <div class="badge">🔒 ការទូទាត់មានសុវត្ថិភាព 100%</div>
      </div>
      <div class="hero-cta">
        <a href="#products" class="btn btn-primary">ស្វែងរកផលិតផល</a>
        <a href="/customer" class="btn btn-secondary">មើលគម្រោង</a>
      </div>
    </div>
  <?php else: ?>
    <div class="hero-content hero-content-compact">
      <h1>Nourish Workspace</h1>
      <p>
        <?php if (!empty($authUser)): ?>
          Signed in as <?= e($authUser['name']) ?> (<?= e($authUser['role']) ?>)
        <?php else: ?>
          Welcome to Nourish. Please sign in to continue.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</header>

<?php if ($isHome): ?>
  <div class="provinces-marquee">
    <div class="marquee-content">
      <span>ភ្នំពេញ</span><span>កណ្ដាល</span><span>កំពង់ធំ</span><span>កំពង់ឆ្នាំង</span><span>កំពង់ស្ពឺ</span><span>កំពង់ចាម</span><span>កំពត</span><span>កែប</span><span>កោះកុង</span><span>ក្រចេះ</span><span>មណ្ឌលគីរី</span><span>បាត់ដំបង</span><span>បន្ទាយមានជ័យ</span><span>ប៉ៃលិន</span><span>ព្រះវិហារ</span><span>ព្រៃវែង</span><span>ពោធិ៍សាត់</span><span>រតនគីរី</span><span>សៀមរាប</span><span>ស្ទឹងត្រែង</span><span>ស្វាយរៀង</span><span>តាកែវ</span><span>ត្បូងឃ្មុំ</span><span>ឧត្តរមានជ័យ</span>
      <span>ភ្នំពេញ</span><span>កណ្ដាល</span><span>កំពង់ធំ</span><span>កំពង់ឆ្នាំង</span><span>កំពង់ស្ពឺ</span><span>កំពង់ចាម</span><span>កំពត</span><span>កែប</span><span>កោះកុង</span><span>ក្រចេះ</span><span>មណ្ឌលគីរី</span><span>បាត់ដំបង</span><span>បន្ទាយមានជ័យ</span><span>ប៉ៃលិន</span><span>ព្រះវិហារ</span><span>ព្រៃវែង</span><span>ពោធិ៍សាត់</span><span>រតនគីរី</span><span>សៀមរាប</span><span>ស្ទឹងត្រែង</span><span>ស្វាយរៀង</span><span>តាកែវ</span><span>ត្បូងឃ្មុំ</span><span>ឧត្តរមានជ័យ</span>
    </div>
  </div>
<?php endif; ?>
<?php
/**
 * Medical Videos Library
 * Educational video content management
 */

$page_title = 'مكتبة الفيديوهات التعليمية';
require_once __DIR__ . '/../includes/header.php';

// Require authentication
requireAuth();
$current_user = getCurrentUser();

// Initialize Video class
$video = new Video();

// Get videos based on user role
$user_role = $current_user['role'];

// فلترة الفيديوهات حسب العيادة إذا تم تمرير clinic_id
$clinic_id = isset($_GET['clinic_id']) ? (int)$_GET['clinic_id'] : null;
if ($clinic_id) {
    $videos = $video->getByClinic($clinic_id, 50, $user_role);
} else {
    $videos = $video->getRecent(50, $user_role);
}

// عرض اسم العيادة أعلى الصفحة إذا تم تمرير clinic_id
$clinic_name = '';
if ($clinic_id) {
    require_once __DIR__ . '/../classes/Clinic.php';
    $clinicObj = new Clinic();
    $clinic = $clinicObj->getById($clinic_id);
    $clinic_name = $clinic['name'] ?? '';
}

// Get all categories
$categories = $video->getCategories();

// Count videos by category
$category_counts = [];
foreach ($categories as $cat) {
    $category_counts[$cat['id']] = 0;
}

foreach ($videos as $vid) {
    if ($vid['category']) {
        $category_counts[$vid['category']]++;
    }
}

// Helper functions
function getYouTubeId($url) {
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    return isset($match[1]) ? $match[1] : '';
}

function getCreatorName($user_id) {
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? $user['first_name'] . ' ' . $user['last_name'] : 'غير محدد';
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    $time = ($time < 1) ? 1 : $time;
    $tokens = array (
        31536000 => 'سنة',
        2592000 => 'شهر',
        604800 => 'أسبوع',
        86400 => 'يوم',
        3600 => 'ساعة',
        60 => 'دقيقة',
        1 => 'ثانية'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?' ':'').' مضت';
    }
}
?>

<div class="container-fluid p-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-video me-2"></i>
            <?php if ($clinic_id && $clinic_name): ?>
                فيديوهات عيادة: <?= htmlspecialchars($clinic_name) ?> 
            <?php else: ?>
                مكتبة الفيديوهات التعليمية
            <?php endif; ?>
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                <?php if ($clinic_id && $clinic_name): ?>
                    <li class="breadcrumb-item"><a href="/clinics/doc.php">العيادات</a></li>
                    <li class="breadcrumb-item active">فيديوهات <?= htmlspecialchars($clinic_name) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item active">الفيديوهات</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-video text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= count($videos) ?></h3>
                    <p class="text-muted mb-0">إجمالي الفيديوهات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-play text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= count(array_filter($videos, function($v) { return $v['status'] === 'active'; })) ?></h3>
                    <p class="text-muted mb-0">الفيديوهات النشطة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-folder text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= count($categories) ?></h3>
                    <p class="text-muted mb-0">الفئات المتاحة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-eye text-primary fa-lg"></i>
                    </div>
                    <h3 class="mb-1"><?= array_sum(array_column($videos, 'view_count')) ?></h3>
                    <p class="text-muted mb-0">إجمالي المشاهدات</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <!-- Videos List -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>الفيديوهات المتاحة</h5>
                    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                        <i class="fas fa-plus me-2"></i>إضافة فيديو
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <!-- Search and Filter -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="البحث في الفيديوهات..." id="videoSearch">
                                <button class="btn btn-outline-primary" type="button" onclick="searchVideos()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">جميع الفئات</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="levelFilter">
                                <option value="">جميع المستويات</option>
                                <option value="beginner">مبتدئ</option>
                                <option value="intermediate">متوسط</option>
                                <option value="advanced">متقدم</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter me-2"></i>فلترة
                            </button>
                        </div>
                    </div>

                    <div class="row" id="videosContainer">
                    <?php if (count($videos) == 0): ?>
                        <div class="col-12 text-center">
                            <div class="card h-100 shadow-sm p-4 mb-4" style="max-width:400px;margin:auto;cursor:pointer;" <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>data-bs-toggle="modal" data-bs-target="#addVideoModal"<?php endif; ?>>
                                <div class="card-body">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-video text-primary fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-2">لا توجد فيديوهات متاحة</h5>
                                    <p class="card-text text-muted">
                                        <?php if ($clinic_id): ?>
                                            لم يتم إضافة فيديوهات لهذه العيادة بعد
                                        <?php else: ?>
                                            ابدأ بإضافة فيديوهات تعليمية جديدة
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>
                                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                                        <i class="fas fa-plus me-2"></i>إضافة أول فيديو
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($videos as $video_item): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3 mb-4 video-item" data-category="<?= $video_item['category'] ?>" data-target-audience="<?= $video_item['target_audience'] ?>">
                            <div class="card h-100 shadow-sm video-card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="card-title mb-0"><i class="fas fa-video me-2"></i><?= htmlspecialchars($video_item['title']) ?></h6>
                                </div>
                                <div class="position-relative">
                                    <?php if ($video_item['video_url']): ?>
                                        <!-- YouTube thumbnail -->
                                        <div class="video-thumbnail bg-dark d-flex align-items-center justify-content-center" 
                                             style="height: 200px; background-image: url('https://img.youtube.com/vi/<?= getYouTubeId($video_item['video_url']) ?>/hqdefault.jpg'); background-size: cover; cursor: pointer;"
                                             onclick="playVideo('<?= $video_item['id'] ?>', '<?= $video_item['video_url'] ?>')">
                                            <i class="fas fa-play-circle text-white fa-3x"></i>
                                        </div>
                                    <?php else: ?>
                                        <!-- Local video thumbnail placeholder -->
                                        <div class="video-thumbnail bg-dark d-flex align-items-center justify-content-center" 
                                             style="height: 200px; cursor: pointer;"
                                             onclick="playVideo('<?= $video_item['id'] ?>', '<?= $video_item['video_path'] ?>')">
                                            <i class="fas fa-play-circle text-white fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-4 d-flex flex-column">
                                    <p class="card-text mb-3 flex-grow-1"><?= htmlspecialchars(substr($video_item['description'], 0, 100)) . (strlen($video_item['description']) > 100 ? '...' : '') ?></p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-eye me-1"></i><?= $video_item['view_count'] ?? 0 ?> مشاهدة
                                        </small>
                                        <span class="badge bg-<?= ($video_item['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                            <?= $video_item['status'] === 'active' ? 'نشط' : 'غير نشط' ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 d-flex justify-content-center gap-2 p-3">
                                    <button class="btn btn-sm btn-primary" onclick="playVideo('<?= $video_item['id'] ?>', '<?= $video_item['video_url'] ?: $video_item['video_path'] ?>')">
                                        <i class="fas fa-play me-1"></i> تشغيل
                                    </button>
                                    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>
                                    <button class="btn btn-sm btn-outline-primary editVideoBtn" data-id="<?= $video_item['id'] ?>">
                                        <i class="fas fa-edit me-1"></i> تعديل
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger deleteVideoBtn" data-id="<?= $video_item['id'] ?>">
                                        <i class="fas fa-trash me-1"></i> حذف
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions - Full Width -->
    <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <button class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                                <i class="fas fa-plus me-2"></i>إضافة فيديو جديد
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="/clinics/doc.php" class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3">
                                <i class="fas fa-clinic-medical me-2"></i>إدارة العيادات
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="/dashboard.php" class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3">
                                <i class="fas fa-home me-2"></i>العودة للوحة التحكم
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3" onclick="applyFilters()">
                                <i class="fas fa-filter me-2"></i>فلترة الفيديوهات
                            </button>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <button class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3" onclick="clearAllFilters()">
                                <i class="fas fa-times me-2"></i>مسح الفلاتر
                            </button>
                        </div>
                        <?php if ($clinic_id): ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="/videos/index.php" class="btn btn-outline-primary w-100 h-100 d-flex align-items-center justify-content-center p-3">
                                <i class="fas fa-video me-2"></i>جميع الفيديوهات
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'doctor'): ?>
<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة فيديو تعليمي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data" action="/handlers/video_handler.php">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="redirect" value="/videos/index.php<?= $clinic_id ? '?clinic_id='.$clinic_id : '' ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <?php if ($clinic_id): ?>
                    <input type="hidden" name="clinic_id" value="<?= $clinic_id ?>">
                <?php endif; ?>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="title" class="form-label">عنوان الفيديو</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">وصف الفيديو</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">الفئة</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">اختر الفئة</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">الجمهور المستهدف</label>
                                <select class="form-select" id="target_audience" name="target_audience">
                                    <option value="all">الجميع</option>
                                    <option value="doctor">الأطباء</option>
                                    <option value="patient">المرضى</option>
                                    <option value="secretary">السكرتارية</option>
                                    <option value="pharmacy">الصيدلية</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">مصدر الفيديو</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="video_source" id="youtube_source" value="youtube" checked>
                                    <label class="form-check-label" for="youtube_source">
                                        رابط يوتيوب
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="video_source" id="upload_source" value="upload">
                                    <label class="form-check-label" for="upload_source">
                                        رفع ملف
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" id="youtube_url_field">
                            <div class="mb-3">
                                <label for="video_url" class="form-label">رابط يوتيوب</label>
                                <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                        <div class="col-md-12 d-none" id="video_file_field">
                            <div class="mb-3">
                                <label for="video_file" class="form-label">ملف الفيديو</label>
                                <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*">
                                <div class="form-text">الحد الأقصى: 500 ميجابايت. الصيغ المدعومة: MP4, WebM, OGG, MOV</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ الفيديو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Video Modal -->
<div class="modal fade" id="editVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل الفيديو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/handlers/video_handler.php" id="editVideoForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="video_id" id="edit_video_id">
                <input type="hidden" name="redirect" value="/videos/index.php<?= $clinic_id ? '?clinic_id='.$clinic_id : '' ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">عنوان الفيديو</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_description" class="form-label">وصف الفيديو</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category" class="form-label">الفئة</label>
                                <select class="form-select" id="edit_category" name="category">
                                    <option value="">اختر الفئة</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_target_audience" class="form-label">الجمهور المستهدف</label>
                                <select class="form-select" id="edit_target_audience" name="target_audience">
                                    <option value="all">الجميع</option>
                                    <option value="doctor">الأطباء</option>
                                    <option value="patient">المرضى</option>
                                    <option value="secretary">السكرتارية</option>
                                    <option value="pharmacy">الصيدلية</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_video_url" class="form-label">رابط الفيديو</label>
                                <input type="url" class="form-control" id="edit_video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">الحالة</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div class="modal fade" id="videoPlayerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoPlayerTitle">تشغيل الفيديو</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopVideo()"></button>
            </div>
            <div class="modal-body p-0">
                <div id="videoPlayerContainer" class="ratio ratio-16x9">
                    <!-- Video content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Video source toggle
document.querySelectorAll('input[name="video_source"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const youtubeField = document.getElementById('youtube_url_field');
        const uploadField = document.getElementById('video_file_field');
        
        if (this.value === 'youtube') {
            youtubeField.classList.remove('d-none');
            uploadField.classList.add('d-none');
            document.getElementById('video_file').required = false;
            document.getElementById('video_url').required = true;
        } else {
            youtubeField.classList.add('d-none');
            uploadField.classList.remove('d-none');
            document.getElementById('video_url').required = false;
            document.getElementById('video_file').required = true;
        }
    });
});

// Search videos
function searchVideos() {
    const searchTerm = document.getElementById('videoSearch').value.toLowerCase();
    const videoItems = document.querySelectorAll('.video-item');
    
    videoItems.forEach(item => {
        const title = item.querySelector('.card-title').textContent.toLowerCase();
        const description = item.querySelector('.card-text').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Apply filters
function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const videoItems = document.querySelectorAll('.video-item');
    
    videoItems.forEach(item => {
        let show = true;
        
        if (categoryFilter && item.dataset.category !== categoryFilter) {
            show = false;
        }
        
        // Add level filtering logic here if needed
        
        item.style.display = show ? 'block' : 'none';
    });
}

// Clear all filters
function clearAllFilters() {
    document.getElementById('videoSearch').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('levelFilter').value = '';
    
    document.querySelectorAll('.video-item').forEach(item => {
        item.style.display = 'block';
    });
}

// Play video
function playVideo(videoId, videoUrl) {
    const modal = new bootstrap.Modal(document.getElementById('videoPlayerModal'));
    const container = document.getElementById('videoPlayerContainer');
    const title = document.getElementById('videoPlayerTitle');
    
    // Clear previous content
    container.innerHTML = '';
    
    if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
        // YouTube video
        const videoCode = getYouTubeVideoId(videoUrl);
        container.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoCode}?autoplay=1" frameborder="0" allowfullscreen></iframe>`;
    } else {
        // Local video
        container.innerHTML = `<video controls autoplay class="w-100 h-100"><source src="${videoUrl}" type="video/mp4">Your browser does not support the video tag.</video>`;
    }
    
    modal.show();
}

// Stop video
function stopVideo() {
    document.getElementById('videoPlayerContainer').innerHTML = '';
}

// Get YouTube video ID from URL
function getYouTubeVideoId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

// Event listeners for edit and delete buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.editVideoBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const videoId = this.dataset.id;
            const videoCard = this.closest('.video-card');
            
            // Extract video data from the card
            const title = videoCard.querySelector('.card-title').textContent.replace('🎬', '').trim();
            const description = videoCard.querySelector('.card-text').textContent.trim();
            const targetAudience = videoCard.dataset.targetAudience || 'all';
            const category = videoCard.dataset.category || '';
            const status = videoCard.querySelector('.badge').textContent.includes('نشط') ? 'active' : 'inactive';
            
            // Populate edit form
            document.getElementById('edit_video_id').value = videoId;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_target_audience').value = targetAudience;
            document.getElementById('edit_status').value = status;
            
            // Show edit modal
            const modal = new bootstrap.Modal(document.getElementById('editVideoModal'));
            modal.show();
        });
    });
    
    document.querySelectorAll('.deleteVideoBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const videoId = this.dataset.id;
            const videoTitle = this.closest('.video-card').querySelector('.card-title').textContent.replace('🎬', '').trim();
            
            if (confirm(`هل أنت متأكد من حذف الفيديو "${videoTitle}"؟`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/handlers/video_handler.php';
                
                // Add hidden fields
                const fields = {
                    'action': 'delete',
                    'video_id': videoId,
                    'redirect': window.location.href,
                    'csrf_token': '<?php echo generateCSRFToken(); ?>'
                };
                
                for (const [name, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
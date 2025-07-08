<?php
session_start();
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/classes/Auth.php';

// Get current user
$auth = new Auth();
$current_user = $auth->getCurrentUser();

if (!$current_user) {
    header('Location: login.php');
    exit;
}

// Get category from URL
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
if (empty($category)) {
    header('Location: keys.php');
    exit;
}

// Define category names for display
$categoryNames = [
    'anatomy' => 'تشريح',
    'symptoms' => 'أعراض',
    'procedures' => 'إجراءات',
    'diseases' => 'أمراض',
    'treatments' => 'علاجات',
    'medications' => 'أدوية',
    'family' => 'الأسرة',
    'verbs' => 'أفعال',
    'religion' => 'دين',
    'times' => 'مواقيت',
    'colors' => 'ألوان',
    'places' => 'أماكن',
    'weekdays' => 'أيام الأسبوع',
    'intro' => 'تعارف',
    'countries' => 'الدول',
    'education' => 'تعليم',
    'adjectives' => 'صفات',
    'egypt_govs' => 'محافظات مصر',
    'clothes' => 'ملابس',
    'jobs' => 'مهن',
    'transport' => 'وسائل مواصلات',
    'communication' => 'وسائل التواصل'
];

$categoryDisplayName = isset($categoryNames[$category]) ? $categoryNames[$category] : $category;

// Load existing terms from file
$termsFile = __DIR__ . '/terms.json';
$terms = file_exists($termsFile) ? json_decode(file_get_contents($termsFile), true) : [];

// Filter terms by category
$categoryTerms = array_filter($terms, function($term) use ($category) {
    return isset($term['category']) && $term['category'] === $category;
});

// Initialize messages
$success = '';
$error = '';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($current_user['role']) && $current_user['role'] === 'admin') {
                    $newTerm = [
                        'term' => trim($_POST['term'] ?? ''),
                        'definition' => trim($_POST['definition'] ?? ''),
                        'category' => $category,
                        'video' => ''
                    ];
                    
                    // Handle video upload
                    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/uploads/term_videos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $filename = 'term_' . time() . '_' . rand(1000, 9999) . '.mp4';
                        $uploadPath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadPath)) {
                            $newTerm['video'] = 'uploads/term_videos/' . $filename;
                        }
                    }
                    
                    if (!empty($newTerm['term']) && !empty($newTerm['definition'])) {
                        $terms[] = $newTerm;
                        if (file_put_contents($termsFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                            $success = 'تم إضافة المصطلح بنجاح';
                        } else {
                            $error = 'فشل في حفظ المصطلح';
                        }
                    } else {
                        $error = 'يرجى ملء جميع الحقول المطلوبة';
                    }
                } else {
                    $error = 'ليس لديك صلاحية لإضافة مصطلحات';
                }
                break;
                
            case 'edit':
                if (isset($current_user['role']) && $current_user['role'] === 'admin') {
                    $termIndex = (int)($_POST['term_index'] ?? -1);
                    if ($termIndex >= 0 && isset($terms[$termIndex])) {
                        $terms[$termIndex]['term'] = trim($_POST['term'] ?? '');
                        $terms[$termIndex]['definition'] = trim($_POST['definition'] ?? '');
                        
                        // Handle video upload for edit
                        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                            // Delete old video if exists
                            if (!empty($terms[$termIndex]['video']) && file_exists(__DIR__ . '/' . $terms[$termIndex]['video'])) {
                                unlink(__DIR__ . '/' . $terms[$termIndex]['video']);
                            }
                            
                            $uploadDir = __DIR__ . '/uploads/term_videos/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            
                            $filename = 'term_' . time() . '_' . rand(1000, 9999) . '.mp4';
                            $uploadPath = $uploadDir . $filename;
                            
                            if (move_uploaded_file($_FILES['video']['tmp_name'], $uploadPath)) {
                                $terms[$termIndex]['video'] = 'uploads/term_videos/' . $filename;
                            }
                        }
                        
                        if (file_put_contents($termsFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                            $success = 'تم تحديث المصطلح بنجاح';
                        } else {
                            $error = 'فشل في تحديث المصطلح';
                        }
                    }
                } else {
                    $error = 'ليس لديك صلاحية لتعديل المصطلحات';
                }
                break;
                
            case 'delete':
                if (isset($current_user['role']) && $current_user['role'] === 'admin') {
                    $termIndex = (int)($_POST['term_index'] ?? -1);
                    if ($termIndex >= 0 && isset($terms[$termIndex])) {
                        // Delete video file if exists
                        if (!empty($terms[$termIndex]['video']) && file_exists(__DIR__ . '/' . $terms[$termIndex]['video'])) {
                            unlink(__DIR__ . '/' . $terms[$termIndex]['video']);
                        }
                        
                        array_splice($terms, $termIndex, 1);
                        
                        if (file_put_contents($termsFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                            $success = 'تم حذف المصطلح بنجاح';
                        } else {
                            $error = 'فشل في حذف المصطلح';
                        }
                    }
                } else {
                    $error = 'ليس لديك صلاحية لحذف المصطلحات';
                }
                break;
        }
    }
    
    // Redirect to prevent form resubmission (Post-Redirect-Get pattern)
    $redirect_url = "keys_category.php?category=" . urlencode($category);
    if (!empty($success)) {
        $redirect_url .= "&success=" . urlencode($success);
    }
    if (!empty($error)) {
        $redirect_url .= "&error=" . urlencode($error);
    }
    header("Location: $redirect_url");
    exit;
}

// Get messages from URL parameters (after redirect)
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Re-filter terms after potential modifications
$categoryTerms = array_filter($terms, function($term) use ($category) {
    return isset($term['category']) && $term['category'] === $category;
});

$page_title = "مصطلحات " . $categoryDisplayName;
require_once __DIR__ . '/includes/header.php';
?>

<style>
.term-card .card {
    transition: all 0.3s ease;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.term-card .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
}

.btn-outline-primary {
    border-color: #0d6efd;
    color: #0d6efd;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.video-thumbnail {
    cursor: pointer;
    transition: transform 0.2s;
    border-radius: 8px;
    overflow: hidden;
}

.video-thumbnail:hover {
    transform: scale(1.02);
}

.back-button {
    transition: all 0.3s ease;
}

.back-button:hover {
    transform: translateX(-2px);
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="keys.php" class="btn btn-outline-secondary me-3 back-button">
                <i class="fas fa-arrow-right me-2"></i>العودة للقائمة الرئيسية
            </a>
            <div>
                <h2 class="mb-0 mt-3">
                    <i class="fas fa-hand-paper me-2 text-primary"></i>
                    مصطلحات <?php echo htmlspecialchars($categoryDisplayName); ?>
                </h2>
                <p class="text-muted mb-0">إدارة وعرض مصطلحات لغة الإشارة</p>
            </div>
        </div>
        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTermModal">
            <i class="fas fa-plus me-2"></i>إضافة مصطلح جديد
        </button>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchTerm" class="form-control" placeholder="البحث في المصطلحات...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="alphabetFilter" class="form-select">
                        <option value="">جميع الحروف</option>
                        <option value="أ">أ</option>
                        <option value="ب">ب</option>
                        <option value="ت">ت</option>
                        <option value="ث">ث</option>
                        <option value="ج">ج</option>
                        <option value="ح">ح</option>
                        <option value="خ">خ</option>
                        <option value="د">د</option>
                        <option value="ذ">ذ</option>
                        <option value="ر">ر</option>
                        <option value="ز">ز</option>
                        <option value="س">س</option>
                        <option value="ش">ش</option>
                        <option value="ص">ص</option>
                        <option value="ض">ض</option>
                        <option value="ط">ط</option>
                        <option value="ظ">ظ</option>
                        <option value="ع">ع</option>
                        <option value="غ">غ</option>
                        <option value="ف">ف</option>
                        <option value="ق">ق</option>
                        <option value="ك">ك</option>
                        <option value="ل">ل</option>
                        <option value="م">م</option>
                        <option value="ن">ن</option>
                        <option value="ه">ه</option>
                        <option value="و">و</option>
                        <option value="ي">ي</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        إجمالي المصطلحات: <span id="termCount"><?php echo count($categoryTerms); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Grid -->
    <div class="row" id="termsContainer">
        <?php if (empty($categoryTerms)): ?>
            <!-- Empty State -->
            <div class="col-12 text-center">
                <div class="card h-100 shadow-sm p-4 mb-4" style="max-width:400px;margin:auto;">
                    <div class="card-body">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-hand-paper text-primary fa-2x"></i>
                        </div>
                        <h5 class="card-title mb-2">لا توجد مصطلحات في فئة <?php echo htmlspecialchars($categoryDisplayName); ?></h5>
                        <p class="card-text text-muted">ابدأ بإضافة مصطلح جديد لهذه الفئة</p>
                        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
                            <i class="fas fa-plus me-2"></i>إضافة أول مصطلح
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else:
            $termIndex = 0;
            foreach ($terms as $globalIndex => $term):
                if ($term['category'] !== $category) continue;
        ?>
        <div class="col-lg-6 col-xl-4 mb-4 term-card" data-term="<?php echo htmlspecialchars($term['term']); ?>">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-hand-paper me-2"></i>
                            <?php echo htmlspecialchars($term['term']); ?>
                        </h5>
                        <span class="badge bg-light text-primary"><?php echo htmlspecialchars($categoryDisplayName); ?></span>
                    </div>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <?php if (!empty($term['video'])): ?>
                    <div class="position-relative mb-3">
                        <div class="video-thumbnail bg-light rounded d-flex align-items-center justify-content-center" 
                             style="height: 180px; cursor: pointer;" 
                             onclick="playVideo('<?php echo htmlspecialchars($term['video']); ?>')">
                            <div class="text-center">
                                <i class="fas fa-play-circle fa-3x text-primary mb-2"></i>
                                <h6 class="text-muted">مشاهدة فيديو لغة الإشارة</h6>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex-grow-1">
                        <p class="card-text text-muted mb-3"><?php echo nl2br(htmlspecialchars($term['definition'])); ?></p>
                    </div>
                    
                    <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                    <div class="mt-auto">
                        <div class="btn-group w-100" role="group">
                            <?php if (!empty($term['video'])): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="playVideo('<?php echo htmlspecialchars($term['video']); ?>')">
                                <i class="fas fa-play me-1"></i>فيديو
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="editTerm(<?php echo $globalIndex; ?>, '<?php echo htmlspecialchars($term['term']); ?>', '<?php echo htmlspecialchars($term['definition']); ?>')">
                                <i class="fas fa-edit me-1"></i>تعديل
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTerm(<?php echo $globalIndex; ?>, '<?php echo htmlspecialchars($term['term']); ?>')">
                                <i class="fas fa-trash me-1"></i>حذف
                            </button>
                        </div>
                    </div>
                    <?php elseif (!empty($term['video'])): ?>
                    <div class="mt-auto">
                        <button type="button" class="btn btn-primary w-100 btn-sm" onclick="playVideo('<?php echo htmlspecialchars($term['video']); ?>')">
                            <i class="fas fa-play me-2"></i>مشاهدة فيديو لغة الإشارة
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
            $termIndex++;
            endforeach; 
        endif; ?>
    </div>
</div>

<!-- Add Term Modal -->
<?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
<div class="modal fade" id="addTermModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة مصطلح جديد - <?php echo htmlspecialchars($categoryDisplayName); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="termForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="termName" class="form-label">المصطلح <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="termName" name="term" required>
                    </div>
                    <div class="mb-3">
                        <label for="termDefinition" class="form-label">التعريف <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="termDefinition" name="definition" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="termVideo" class="form-label">فيديو لغة الإشارة (اختياري)</label>
                        <input type="file" class="form-control" id="termVideo" name="video" accept="video/*">
                        <div class="form-text">يُفضل رفع ملفات MP4 بجودة عالية</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ المصطلح
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Term Modal -->
<?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
<div class="modal fade" id="editTermModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل المصطلح</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editTermForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="term_index" id="editTermIndex">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTermName" class="form-label">المصطلح <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTermName" name="term" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTermDefinition" class="form-label">التعريف <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editTermDefinition" name="definition" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editTermVideo" class="form-label">فيديو لغة الإشارة (اختياري)</label>
                        <input type="file" class="form-control" id="editTermVideo" name="video" accept="video/*">
                        <div class="form-text">اترك فارغاً للاحتفاظ بالفيديو الحالي</div>
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
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
<div class="modal fade" id="deleteTermModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف المصطلح "<span id="deleteTermName"></span>"؟</p>
                <p class="text-muted">لا يمكن التراجع عن هذا الإجراء.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="term_index" id="deleteTermIndex">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>حذف نهائياً
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalTitle">فيديو لغة الإشارة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="videoModalBody">
                <!-- Video content will be inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
function searchTerms() {
    const searchTerm = document.getElementById('searchTerm').value.toLowerCase();
    const alphabetFilter = document.getElementById('alphabetFilter').value;
    const termCards = document.querySelectorAll('.term-card');
    let visibleCount = 0;
    
    termCards.forEach(card => {
        const termText = card.dataset.term.toLowerCase();
        const firstChar = card.dataset.term.charAt(0);
        
        const matchesSearch = searchTerm === '' || termText.includes(searchTerm);
        const matchesAlphabet = alphabetFilter === '' || firstChar === alphabetFilter;
        
        if (matchesSearch && matchesAlphabet) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    document.getElementById('termCount').textContent = visibleCount;
}

// Edit term function
function editTerm(index, term, definition) {
    document.getElementById('editTermIndex').value = index;
    document.getElementById('editTermName').value = term;
    document.getElementById('editTermDefinition').value = definition;
    
    const editModal = new bootstrap.Modal(document.getElementById('editTermModal'));
    editModal.show();
}

// Delete term function
function deleteTerm(index, termName) {
    document.getElementById('deleteTermIndex').value = index;
    document.getElementById('deleteTermName').textContent = termName;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteTermModal'));
    deleteModal.show();
}

// Play video function
function playVideo(videoPath) {
    const modal = new bootstrap.Modal(document.getElementById('videoModal'));
    const videoBody = document.getElementById('videoModalBody');
    const videoTitle = document.getElementById('videoModalTitle');
    
    videoTitle.textContent = `فيديو لغة الإشارة`;
    
    if (videoPath) {
        // Clear previous content
        videoBody.innerHTML = '';
        
        // Create video element with proper styling
        const videoContainer = document.createElement('div');
        videoContainer.className = 'ratio ratio-16x9';
        
        const videoElement = document.createElement('video');
        videoElement.controls = true;
        videoElement.autoplay = true;
        videoElement.className = 'w-100 h-100';
        videoElement.style.objectFit = 'cover';
        
        const sourceElement = document.createElement('source');
        sourceElement.src = videoPath;
        sourceElement.type = 'video/mp4';
        
        videoElement.appendChild(sourceElement);
        videoContainer.appendChild(videoElement);
        videoBody.appendChild(videoContainer);
    } else {
        videoBody.innerHTML = `
            <div class='bg-light p-5 rounded'>
                <i class='fas fa-video fa-3x text-muted mb-3'></i>
                <h5 class='text-muted'>فيديو لغة الإشارة غير متوفر</h5>
                <p class='text-muted'>سيتم إضافة الفيديو قريباً</p>
            </div>`;
    }
    
    modal.show();
}

// Initialize functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for real-time search
    document.getElementById('searchTerm').addEventListener('input', searchTerms);
    document.getElementById('alphabetFilter').addEventListener('change', searchTerms);
    
    // Handle add term form submission
    const termForm = document.getElementById('termForm');
    if (termForm) {
        termForm.addEventListener('submit', function(e) {
            const submitBtn = termForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Handle edit term form submission
    const editTermForm = document.getElementById('editTermForm');
    if (editTermForm) {
        editTermForm.addEventListener('submit', function(e) {
            const submitBtn = editTermForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Close modal automatically on successful submission
    <?php if (!empty($success)): ?>
    const addTermModal = document.getElementById('addTermModal');
    if (addTermModal) {
        const modal = bootstrap.Modal.getInstance(addTermModal);
        if (modal) {
            modal.hide();
        }
    }
    const editTermModal = document.getElementById('editTermModal');
    if (editTermModal) {
        const modal = bootstrap.Modal.getInstance(editTermModal);
        if (modal) {
            modal.hide();
        }
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

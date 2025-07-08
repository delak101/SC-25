<?php
/**
 * Medical Terms and Sign Language Keys Page
 * Dictionary of medical terms with sign language translations
 */

session_start(); // Start session at the beginning

// Require authentication and functions first (before any processing)
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';
requireAuth();
$current_user = getCurrentUser();

$termsFile = __DIR__ . '/terms.json';

// Handle new term submission or deletion BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_term'])) {
        $term = trim($_POST['term'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $definition = trim($_POST['definition'] ?? '');
        $errors = [];
        $videoPath = '';

        // Validate
        if ($term === '' || $category === '' || $definition === '') {
            $errors[] = 'جميع الحقول مطلوبة.';
        }

        // Handle video upload
        if (isset($_FILES['sign_video']) && $_FILES['sign_video']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['sign_video']['name'], PATHINFO_EXTENSION));
            $allowed = ['mp4', 'webm', 'ogg'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'صيغة الفيديو غير مدعومة.';
            } else {
                $filename = 'term_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $target = __DIR__ . '/uploads/term_videos/' . $filename;
                if (move_uploaded_file($_FILES['sign_video']['tmp_name'], $target)) {
                    $videoPath = 'uploads/term_videos/' . $filename;
                } else {
                    $errors[] = 'فشل رفع الفيديو.';
                }
            }
        }

        // Save new term (append to JSON file)
        if (empty($errors)) {
            // Ensure uploads directory exists
            if (!is_dir(__DIR__ . '/uploads/term_videos/')) {
                mkdir(__DIR__ . '/uploads/term_videos/', 0755, true);
            }
            
            $terms = file_exists($termsFile) ? json_decode(file_get_contents($termsFile), true) : [];
            $terms[] = [
                'term' => $term,
                'category' => $category,
                'definition' => $definition,
                'video' => $videoPath
            ];
            file_put_contents($termsFile, json_encode($terms, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            
            // Store success message in session and redirect to prevent duplicate submissions
            $_SESSION['success_message'] = 'تمت إضافة المصطلح بنجاح!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['delete_term']) && isset($_POST['term_index'])) {
        // Handle term deletion
        $termIndex = (int)$_POST['term_index'];
        $terms = file_exists($termsFile) ? json_decode(file_get_contents($termsFile), true) : [];
        
        if (isset($terms[$termIndex])) {
            // Delete associated video file if exists
            if (!empty($terms[$termIndex]['video']) && file_exists(__DIR__ . '/' . $terms[$termIndex]['video'])) {
                unlink(__DIR__ . '/' . $terms[$termIndex]['video']);
            }
            
            // Remove term from array
            array_splice($terms, $termIndex, 1);
            
            // Save updated terms
            file_put_contents($termsFile, json_encode($terms, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
            
            // Store success message in session and redirect
            $_SESSION['success_message'] = 'تم حذف المصطلح بنجاح!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Check for success message from session
$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Remove message after displaying
}

// NOW include header after all processing is done
$page_title = 'قاموس المصطلحات اللغوية';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid p-4">
    <!-- Success/Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo implode('<br>', $errors); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book-medical me-2"></i>قاموس المصطلحات الطبية</h2>
        <div class="d-flex align-items-center gap-3">
            <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTermModal">
                <i class="fas fa-plus me-2"></i>إضافة مصطلح
            </button>
            <?php endif; ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard.php">لوحة التحكم</a></li>
                    <li class="breadcrumb-item active">المصطلحات الطبية</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchTerm" placeholder="ابحث عن مصطلح طبي...">
                        <button class="btn btn-primary" type="button" onclick="searchTerms()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">جميع الفئات</option>
                        <option value="family">الاسرة</option>
                        <option value="verbs">افعال</option>
                        <option value="religion">دين</option>
                        <option value="times">مواقيت</option>
                        <option value="colors">الوان</option>
                        <option value="places">اماكن</option>
                        <option value="weekdays">ايام الاسبوع</option>
                        <option value="intro">تعارف</option>
                        <option value="countries">الدول</option>
                        <option value="education">تعليم</option>
                        <option value="adjectives">صفات</option>
                        <option value="egypt_govs">مجافظات مصر</option>
                        <option value="clothes">ملابس</option>
                        <option value="jobs">مهن</option>
                        <option value="transport">وسائل مواصلات</option>
                        <option value="communication">وسائل التواصل</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="alphabetFilter">
                        <option value="">الترتيب الأبجدي</option>
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
            </div>
        </div>
    </div>

    <!-- Medical Terms Grid -->
    <div class="row" id="termsContainer">
        <?php
        // Load terms from file
        $terms = file_exists($termsFile) ? json_decode(file_get_contents($termsFile), true) : [];
        
        // Define category cards (instead of sample terms)
        $categoryCards = [
            [
                'term' => 'الأسرة',
                'category' => 'family',
                'definition' => 'مصطلحات متعلقة بأفراد الأسرة والعلاقات العائلية',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'أفعال',
                'category' => 'verbs',
                'definition' => 'الأفعال والحركات المستخدمة في التواصل اليومي',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'دين',
                'category' => 'religion',
                'definition' => 'مصطلحات دينية وعبادات',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'مواقيت',
                'category' => 'times',
                'definition' => 'الأوقات والمواعيد والزمن',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'ألوان',
                'category' => 'colors',
                'definition' => 'الألوان المختلفة وتدرجاتها',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'أماكن',
                'category' => 'places',
                'definition' => 'الأماكن والمواقع الجغرافية',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'أيام الأسبوع',
                'category' => 'weekdays',
                'definition' => 'أيام الأسبوع السبعة',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'تعارف',
                'category' => 'intro',
                'definition' => 'كلمات وجمل التعارف والترحيب',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'الدول',
                'category' => 'countries',
                'definition' => 'أسماء الدول والجنسيات',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'تعليم',
                'category' => 'education',
                'definition' => 'مصطلحات تعليمية ومدرسية',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'صفات',
                'category' => 'adjectives',
                'definition' => 'الصفات ووصف الأشياء والأشخاص',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'محافظات مصر',
                'category' => 'egypt_govs',
                'definition' => 'محافظات جمهورية مصر العربية',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'ملابس',
                'category' => 'clothes',
                'definition' => 'أنواع الملابس والأزياء',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'مهن',
                'category' => 'jobs',
                'definition' => 'المهن والوظائف المختلفة',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'وسائل مواصلات',
                'category' => 'transport',
                'definition' => 'وسائل النقل والمواصلات',
                'video' => '',
                'is_category' => true
            ],
            [
                'term' => 'وسائل التواصل',
                'category' => 'communication',
                'definition' => 'وسائل التواصل والاتصال',
                'video' => '',
                'is_category' => true
            ]
        ];
        
        $allTerms = array_merge($categoryCards, $terms);
        
        if (empty($allTerms)): ?>
            <!-- Empty State -->
            <div class="col-12 text-center">
                <div class="card h-100 shadow-sm p-4 mb-4" style="max-width:400px;margin:auto;">
                    <div class="card-body">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 mx-auto mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-book-medical text-primary fa-2x"></i>
                        </div>
                        <h5 class="card-title mb-2">لا توجد مصطلحات مسجلة</h5>
                        <p class="card-text text-muted">ابدأ بإضافة مصطلح طبي جديد لبناء قاموسك</p>
                        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
                            <i class="fas fa-plus me-2"></i>إضافة أول مصطلح
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else:
            foreach ($allTerms as $idx => $t):
        ?>
        <div class="col-lg-6 col-xl-4 mb-4 term-card" data-category="<?php echo htmlspecialchars($t['category']); ?>" data-term="<?php echo htmlspecialchars($t['term']); ?>">
            <div class="card h-100 shadow-sm<?php echo isset($t['is_category']) ? ' category-card' : ''; ?>" 
                 <?php if (isset($t['is_category'])): ?>
                 style="cursor:pointer;" 
                 onclick="navigateToCategory('<?php echo htmlspecialchars($t['category']); ?>')"
                 data-term-id="<?php echo $idx; ?>"
                 data-term-name="<?php echo htmlspecialchars($t['term']); ?>"
                 data-term-category="<?php echo htmlspecialchars($t['category']); ?>"
                 data-term-definition="<?php echo htmlspecialchars($t['definition']); ?>"
                 <?php endif; ?>>
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-hand-paper me-2"></i>
                            <?php echo htmlspecialchars($t['term']); ?>
                        </h5>
                        <span class="badge bg-light text-primary"><?php
                            switch($t['category']) {
                                case 'anatomy': echo 'تشريح'; break;
                                case 'symptoms': echo 'أعراض'; break;
                                case 'procedures': echo 'إجراءات'; break;
                                case 'diseases': echo 'أمراض'; break;
                                case 'treatments': echo 'علاجات'; break;
                                case 'medications': echo 'أدوية'; break;
                                case 'family': echo 'الأسرة'; break;
                                case 'verbs': echo 'أفعال'; break;
                                case 'religion': echo 'دين'; break;
                                case 'times': echo 'مواقيت'; break;
                                case 'colors': echo 'ألوان'; break;
                                case 'places': echo 'أماكن'; break;
                                case 'weekdays': echo 'أيام الأسبوع'; break;
                                case 'intro': echo 'تعارف'; break;
                                case 'countries': echo 'الدول'; break;
                                case 'education': echo 'تعليم'; break;
                                case 'adjectives': echo 'صفات'; break;
                                case 'egypt_govs': echo 'محافظات مصر'; break;
                                case 'clothes': echo 'ملابس'; break;
                                case 'jobs': echo 'مهن'; break;
                                case 'transport': echo 'وسائل مواصلات'; break;
                                case 'communication': echo 'وسائل التواصل'; break;
                                default: echo $t['category'];
                            }
                        ?></span>
                    </div>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <?php if ($t['video']): ?>
                    <div class="position-relative mb-3">
                        <div class="video-thumbnail bg-dark d-flex align-items-center justify-content-center rounded" 
                             style="height: 150px; cursor: pointer;"
                             onclick="<?php echo isset($t['is_category']) ? 'event.stopPropagation();' : ''; ?>playSignVideo('<?php echo 'term_' . $idx; ?>')">
                            <i class="fas fa-play-circle text-white fa-3x"></i>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-<?php echo isset($t['is_category']) ? 'folder-open' : 'hand-paper'; ?> text-primary fa-2x"></i>
                        </div>
                        <small class="text-muted"><?php echo isset($t['is_category']) ? 'فئة المصطلحات' : 'فيديو لغة الإشارة'; ?></small>
                    </div>
                    <?php endif; ?>
                    <p class="text-muted mb-3 flex-grow-1"><?php echo htmlspecialchars($t['definition']); ?></p>
                    <div class="d-flex gap-2 mt-auto">
                        <?php if (isset($t['is_category'])): ?>
                        <button class="btn btn-primary btn-sm flex-fill" onclick="event.stopPropagation();playSignVideo('<?php echo 'term_' . $idx; ?>')">
                            <i class="fas fa-play me-1"></i>فيديو الإشارة
                        </button>
                        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
                        <button class="btn btn-outline-primary btn-sm editTermBtn" data-id="<?php echo $idx; ?>" onclick="event.stopPropagation();">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm deleteTermBtn" data-id="<?php echo $idx; ?>" onclick="event.stopPropagation();">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php else: ?>
                        <button class="btn btn-primary btn-sm flex-fill" onclick="playSignVideo('<?php echo 'term_' . $idx; ?>')">
                            <i class="fas fa-play me-1"></i>فيديو الإشارة
                        </button>
                        <?php if (isset($current_user['role']) && $current_user['role'] === 'admin' && $idx >= 16): ?>
                        <button class="btn btn-outline-primary btn-sm" onclick="editTerm('<?php echo 'term_' . $idx; ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteTerm('<?php echo 'term_' . $idx; ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; 
        
        // Add "Add Term" card if there are existing terms
        if (!empty($allTerms) && isset($current_user['role']) && $current_user['role'] === 'admin'): ?>
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100 shadow-sm border-dashed" style="min-height: 250px; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#addTermModal">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-plus text-primary fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-2">إضافة مصطلح جديد</h5>
                    <p class="card-text text-muted">أضف مصطلح طبي جديد مع فيديو لغة الإشارة</p>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addTermModal">
                        <i class="fas fa-plus me-2"></i>إضافة مصطلح
                    </button>
                </div>
            </div>
        </div>
        <?php endif; endif; ?>
        <!-- No Results Message -->
        <div class="col-12" id="noResults" style="display: none;">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لم يتم العثور على نتائج</h5>
                    <p class="text-muted">جرب تغيير مصطلحات البحث أو الفلاتر</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Add Term Modal -->
<div class="modal fade" id="addTermModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة مصطلح جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="termForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_term" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">المصطلح</label>
                        <input type="text" class="form-control" name="term" placeholder="أدخل المصطلح الطبي" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <select class="form-select" name="category" required>
                            <option value="">اختر الفئة</option>
                            <option value="anatomy">تشريح</option>
                            <option value="symptoms">أعراض</option>
                            <option value="procedures">إجراءات</option>
                            <option value="diseases">أمراض</option>
                            <option value="treatments">علاجات</option>
                            <option value="medications">أدوية</option>
                            <option value="family">الأسرة</option>
                            <option value="verbs">أفعال</option>
                            <option value="religion">دين</option>
                            <option value="times">مواقيت</option>
                            <option value="colors">ألوان</option>
                            <option value="places">أماكن</option>
                            <option value="weekdays">أيام الأسبوع</option>
                            <option value="intro">تعارف</option>
                            <option value="countries">الدول</option>
                            <option value="education">تعليم</option>
                            <option value="adjectives">صفات</option>
                            <option value="egypt_govs">محافظات مصر</option>
                            <option value="clothes">ملابس</option>
                            <option value="jobs">مهن</option>
                            <option value="transport">وسائل مواصلات</option>
                            <option value="communication">وسائل التواصل</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التعريف</label>
                        <textarea class="form-control" name="definition" rows="3" placeholder="أدخل تعريف المصطلح" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">فيديو لغة الإشارة (اختياري)</label>
                        <input type="file" class="form-control" name="sign_video" accept="video/*">
                        <div class="form-text">صيغ مدعومة: MP4, WebM, OGG</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المصطلح</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Term Details Modal -->
<div class="modal fade" id="termDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termTitle">تفاصيل المصطلح</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="termDetails">
                <!-- Term details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="playSignVideo()">
                    <i class="fas fa-play me-2"></i>تشغيل فيديو الإشارة
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sign Video Modal -->
<div class="modal fade" id="signVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoTitle">فيديو لغة الإشارة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="signVideoBody">
                <div class="bg-light p-5 rounded">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">فيديو لغة الإشارة</h5>
                    <p class="text-muted">سيتم إضافة فيديوهات لغة الإشارة قريباً</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
// Load all terms (PHP to JS)
const allTerms = <?php echo json_encode($allTerms, JSON_UNESCAPED_UNICODE); ?>;

// Add CSS for dashed border cards and category cards
const style = document.createElement('style');
style.textContent = `
    .border-dashed {
        border: 2px dashed #dee2e6 !important;
        transition: all 0.3s ease;
    }
    .border-dashed:hover {
        border-color: #0d6efd !important;
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .category-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #0d6efd;
    }
    .category-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .category-card .card-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
    }
`;
document.head.appendChild(style);

// Edit term functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.editTermBtn')) {
        e.preventDefault();
        e.stopPropagation();
        const btn = e.target.closest('.editTermBtn');
        const termId = btn.dataset.id;
        const idx = parseInt(termId);
        const term = allTerms[idx];
        
        if (!term) return;
        
        // Check if this is a category term (first 16 terms) - don't allow editing name/category
        if (idx < 16) {
            // For category cards, only allow editing definition and video
            const modal = new bootstrap.Modal(document.getElementById('addTermModal'));
            const form = document.getElementById('termForm');
            
            // Update modal title
            document.querySelector('#addTermModal .modal-title').textContent = 'تعديل فئة المصطلحات';
            
            // Populate form fields (disable term and category for category cards)
            form.querySelector('input[name="term"]').value = term.term || '';
            form.querySelector('input[name="term"]').disabled = true;
            form.querySelector('select[name="category"]').value = term.category || '';
            form.querySelector('select[name="category"]').disabled = true;
            form.querySelector('textarea[name="definition"]').value = term.definition || '';
            
            // Update submit button
            form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save me-2"></i>حفظ التعديلات';
            
            // Show modal
            modal.show();
            
            // Reset form when modal is closed
            document.getElementById('addTermModal').addEventListener('hidden.bs.modal', function() {
                document.querySelector('#addTermModal .modal-title').textContent = 'إضافة مصطلح جديد';
                form.querySelector('button[type="submit"]').innerHTML = 'حفظ المصطلح';
                form.querySelector('input[name="term"]').disabled = false;
                form.querySelector('select[name="category"]').disabled = false;
                form.reset();
            }, { once: true });
        } else {
            // For regular terms, allow full editing
            editTerm('term_' + termId);
        }
    }
    
    if (e.target.closest('.deleteTermBtn')) {
        e.preventDefault();
        e.stopPropagation();
        const btn = e.target.closest('.deleteTermBtn');
        const termId = btn.dataset.id;
        const idx = parseInt(termId);
        const term = allTerms[idx];
        
        if (!term) return;
        
        // Check if this is a category term (first 16 terms) - don't allow deletion
        if (idx < 16) {
            alert('لا يمكن حذف فئات المصطلحات الأساسية');
            return;
        }
        
        // For regular terms, allow deletion
        deleteTerm('term_' + termId);
    }
});

function searchTerms() {
    const searchTerm = document.getElementById('searchTerm').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const alphabetFilter = document.getElementById('alphabetFilter').value;
    
    const termCards = document.querySelectorAll('.term-card');
    let visibleCount = 0;
    
    termCards.forEach(card => {
        const term = card.getAttribute('data-term').toLowerCase();
        const category = card.getAttribute('data-category');
        
        let showCard = true;
        
        // Search filter
        if (searchTerm && !term.includes(searchTerm)) {
            showCard = false;
        }
        
        // Category filter
        if (categoryFilter && category !== categoryFilter) {
            showCard = false;
        }
        
        // Alphabet filter
        if (alphabetFilter && !term.startsWith(alphabetFilter)) {
            showCard = false;
        }
        
        if (showCard) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noResults = document.getElementById('noResults');
    if (visibleCount === 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}

function filterByCategory(category) {
    document.getElementById('categoryFilter').value = category;
    searchTerms();
}

function clearFilters() {
    document.getElementById('searchTerm').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('alphabetFilter').value = '';
    searchTerms();
}

function showTermDetails(termId) {
    const modal = new bootstrap.Modal(document.getElementById('termDetailsModal'));
    const idx = parseInt(termId.replace('term_', ''));
    const details = allTerms[idx] || { title: 'مصطلح طبي', definition: 'تفاصيل هذا المصطلح ستكون متاحة قريباً' };
    document.getElementById('termTitle').textContent = details.term || details.title;
    let detailsHTML = `
        <div class="mb-3">
            <h6>التعريف:</h6>
            <p>${details.definition || ''}</p>
        </div>
    `;
    document.getElementById('termDetails').innerHTML = detailsHTML;
    // Store video path for modal
    document.getElementById('termDetailsModal').setAttribute('data-video', details.video || '');
    modal.show();
}

function editTerm(termId) {
    const idx = parseInt(termId.replace('term_', ''));
    const term = allTerms[idx];
    
    if (!term) return;
    
    // Check if this is a category term (first 16 terms) - handle differently
    if (idx < 16) {
        alert('لا يمكن تعديل فئات المصطلحات الأساسية من خلال هذه الوظيفة');
        return;
    }
    
    // Populate the add form with existing data
    const modal = new bootstrap.Modal(document.getElementById('addTermModal'));
    const form = document.getElementById('termForm');
    
    // Update modal title
    document.querySelector('#addTermModal .modal-title').textContent = 'تعديل المصطلح';
    
    // Populate form fields
    form.querySelector('input[name="term"]').value = term.term || '';
    form.querySelector('select[name="category"]').value = term.category || '';
    form.querySelector('textarea[name="definition"]').value = term.definition || '';
    
    // Update submit button
    form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save me-2"></i>حفظ التعديلات';
    
    // Show modal
    modal.show();
    
    // Reset form when modal is closed
    document.getElementById('addTermModal').addEventListener('hidden.bs.modal', function() {
        document.querySelector('#addTermModal .modal-title').textContent = 'إضافة مصطلح جديد';
        form.querySelector('button[type="submit"]').innerHTML = 'حفظ المصطلح';
        form.reset();
    }, { once: true });
}

function deleteTerm(termId) {
    const idx = parseInt(termId.replace('term_', ''));
    const term = allTerms[idx];
    
    if (!term) return;
    
    // Check if this is a category term (first 16 terms) - don't allow deletion
    if (idx < 16) {
        alert('لا يمكن حذف فئات المصطلحات الأساسية');
        return;
    }
    
    // Calculate the actual index in the loaded terms array (subtract category terms)
    const actualIndex = idx - 16;
    
    if (confirm(`هل أنت متأكد من حذف المصطلح "${term.term}"؟\nسيتم حذف الفيديو المرتبط به أيضاً.`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        // Add hidden fields
        const fields = {
            'delete_term': '1',
            'term_index': actualIndex
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
}

function playSignVideo(termId) {
    let videoPath = '';
    if (termId) {
        const idx = parseInt(termId.replace('term_', ''));
        videoPath = allTerms[idx] && allTerms[idx].video ? allTerms[idx].video : '';
    } else {
        // From details modal
        videoPath = document.getElementById('termDetailsModal').getAttribute('data-video') || '';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('signVideoModal'));
    const videoTitle = document.getElementById('videoTitle');
    const videoBody = document.getElementById('signVideoBody');
    
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

// Navigate to category-specific page
function navigateToCategory(category) {
    window.location.href = `keys_category.php?category=${encodeURIComponent(category)}`;
}

// Initialize filters
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for real-time search
    document.getElementById('searchTerm').addEventListener('input', searchTerms);
    document.getElementById('categoryFilter').addEventListener('change', searchTerms);
    document.getElementById('alphabetFilter').addEventListener('change', searchTerms);
    
    // Handle add term form submission
    const termForm = document.getElementById('termForm');
    if (termForm) {
        termForm.addEventListener('submit', function(e) {
            // Let the form submit normally to PHP
            // This will reload the page with success/error messages
            
            // Add loading state to submit button
            const submitBtn = termForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Close modal automatically on successful submission (if success message exists)
    <?php if (!empty($success)): ?>
    const addTermModal = document.getElementById('addTermModal');
    if (addTermModal) {
        const modal = bootstrap.Modal.getInstance(addTermModal);
        if (modal) {
            modal.hide();
        }
    }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
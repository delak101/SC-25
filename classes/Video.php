<?php
require_once __DIR__ . '/Database.php';

/**
 * Video Class
 * Handles video management operations
 */
class Video {
    private $db;
    private $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    private $maxSize = 500 * 1024 * 1024; // 500MB

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Upload video file
     */
    public function uploadFile($file, $title) {
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type. Only MP4, WebM, OGG, and MOV are allowed.');
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds maximum limit of 500MB.');
        }

        // Create uploads directory if not exists
        $uploadDir = __DIR__ . '/../uploads/videos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate safe filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeTitle = preg_replace('/[^a-zA-Z0-9-_]/', '', $title);
        $filename = $safeTitle . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file.');
        }

        return [
            'path' => '/uploads/videos/' . $filename,
            'filename' => $filename,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    /**
     * Update video record
     */
    public function update($id, $data) {
        // Always update the updated_at timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $query = "UPDATE videos SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete video record
     */
    public function delete($id) {
        $query = "DELETE FROM videos WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Create a new video record
     */
    public function create($data) {
        // Set default values
        $data['status'] = $data['status'] ?? 'active';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $query = "INSERT INTO videos 
                 (title, description, video_url, video_path, category, target_audience, status, created_by, created_at, updated_at) 
                 VALUES 
                 (:title, :description, :video_url, :video_path, :category, :target_audience, :status, :created_by, :created_at, :updated_at)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':video_url' => $data['video_url'] ?? null,
            ':video_path' => $data['video_path'] ?? null,
            ':category' => $data['category'],
            ':target_audience' => $data['target_audience'],
            ':status' => $data['status'],
            ':created_by' => $data['created_by'],
            ':created_at' => $data['created_at'],
            ':updated_at' => $data['updated_at']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Get video by ID
     */
    public function getById($id) {
        $query = "SELECT v.*, vc.name as category_name, vc.icon as category_icon, vc.color as category_color 
                  FROM videos v
                  LEFT JOIN video_categories vc ON v.category = vc.id
                  WHERE v.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get video statistics
     */
    public function getStatistics($user_id = null) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(status = 'active') as active,
                    SUM(status = 'inactive') as inactive
                FROM videos";
        
        if ($user_id) {
            $query .= " WHERE created_by = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $user_id]);
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent videos
     */
    public function getRecent($limit = 6, $user_role = 'patient') {
        $query = "SELECT v.*, vc.name as category_name, vc.icon as category_icon, vc.color as category_color 
                  FROM videos v
                  LEFT JOIN video_categories vc ON v.category = vc.id
                  WHERE (v.target_audience = :role OR v.target_audience = 'all')
                  AND v.status = 'active'
                  ORDER BY v.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':role', $user_role, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a video file
     */
    public function deleteFile($path) {
        $fullPath = __DIR__ . '/..' . $path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
            return true;
        }
        return false;
    }

    /**
     * Get all video categories
     */
    public function getCategories() {
        $query = "SELECT * FROM video_categories ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get videos by category
     */
    public function getByCategory($category_id, $limit = 20) {
        $query = "SELECT v.*, vc.name as category_name, vc.icon as category_icon, vc.color as category_color 
                  FROM videos v
                  LEFT JOIN video_categories vc ON v.category = vc.id
                  WHERE v.category = :category_id
                  AND v.status = 'active'
                  ORDER BY v.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new category
     */
    public function createCategory($data) {
        $query = "INSERT INTO video_categories 
                 (name, slug, description, icon, color, created_by) 
                 VALUES 
                 (:name, :slug, :description, :icon, :color, :created_by)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? 'video',
            ':color' => $data['color'] ?? 'primary',
            ':created_by' => $data['created_by']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update a category
     */
    public function updateCategory($id, $data) {
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $query = "UPDATE video_categories SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id) {
        // First, update videos in this category to NULL
        $updateQuery = "UPDATE videos SET category = NULL WHERE category = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute([':id' => $id]);
        
        // Then delete the category
        $deleteQuery = "DELETE FROM video_categories WHERE id = :id";
        $deleteStmt = $this->db->prepare($deleteQuery);
        return $deleteStmt->execute([':id' => $id]);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($id) {
        $query = "SELECT * FROM video_categories WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Search videos
     */
    public function search($query, $limit = 10) {
        $searchTerm = "%$query%";
        $query = "SELECT v.*, vc.name as category_name, vc.icon as category_icon, vc.color as category_color 
                  FROM videos v
                  LEFT JOIN video_categories vc ON v.category = vc.id
                  WHERE (v.title LIKE :search OR v.description LIKE :search OR vc.name LIKE :search)
                  AND v.status = 'active'
                  ORDER BY v.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all videos for a specific clinic
     */
    public function getByClinic($clinic_id, $limit = 50, $user_role = 'patient') {
        $query = "SELECT v.*, vc.name as category_name, vc.icon as category_icon, vc.color as category_color 
                  FROM videos v
                  LEFT JOIN video_categories vc ON v.category = vc.id
                  WHERE v.clinic_id = :clinic_id
                  AND (v.target_audience = :role OR v.target_audience = 'all')
                  AND v.status = 'active'
                  ORDER BY v.created_at DESC 
                  LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':clinic_id', $clinic_id, PDO::PARAM_INT);
        $stmt->bindValue(':role', $user_role, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
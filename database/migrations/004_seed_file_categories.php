<?php
/**
 * Migration: Seed file categories
 * Description: Populates file_categories table with default categories
 */

require_once __DIR__ . '/../../app/src/Migration.php';

class SeedFileCategories extends Migration
{
    public function up()
    {
        $this->log("Seeding file categories...");

        $categories = [
            [
                'name' => 'Documents',
                'slug' => 'documents',
                'description' => 'Document files including PDF, Word, Text files',
                'allowed_extensions' => 'pdf,doc,docx,txt,rtf,odt',
                'max_file_size' => 52428800, // 50MB
                'icon' => 'fa-file-text',
                'color' => '#3498db'
            ],
            [
                'name' => 'Images',
                'slug' => 'images',
                'description' => 'Image files including PNG, JPG, GIF, SVG',
                'allowed_extensions' => 'jpg,jpeg,png,gif,bmp,svg,webp,ico',
                'max_file_size' => 10485760, // 10MB
                'icon' => 'fa-image',
                'color' => '#e74c3c'
            ],
            [
                'name' => 'Videos',
                'slug' => 'videos',
                'description' => 'Video files including MP4, AVI, MOV, MKV',
                'allowed_extensions' => 'mp4,avi,mov,mkv,wmv,flv,webm,m4v',
                'max_file_size' => 524288000, // 500MB
                'icon' => 'fa-video',
                'color' => '#9b59b6'
            ],
            [
                'name' => 'Audio',
                'slug' => 'audio',
                'description' => 'Audio files including MP3, WAV, OGG',
                'allowed_extensions' => 'mp3,wav,ogg,m4a,flac,aac,wma',
                'max_file_size' => 52428800, // 50MB
                'icon' => 'fa-music',
                'color' => '#1abc9c'
            ],
            [
                'name' => 'Spreadsheets',
                'slug' => 'spreadsheets',
                'description' => 'Spreadsheet files including Excel, CSV',
                'allowed_extensions' => 'xlsx,xls,csv,ods',
                'max_file_size' => 20971520, // 20MB
                'icon' => 'fa-table',
                'color' => '#27ae60'
            ],
            [
                'name' => 'Presentations',
                'slug' => 'presentations',
                'description' => 'Presentation files including PowerPoint',
                'allowed_extensions' => 'ppt,pptx,odp,key',
                'max_file_size' => 52428800, // 50MB
                'icon' => 'fa-presentation',
                'color' => '#f39c12'
            ],
            [
                'name' => 'Archives',
                'slug' => 'archives',
                'description' => 'Compressed archive files',
                'allowed_extensions' => 'zip,rar,7z,tar,gz,bz2',
                'max_file_size' => 104857600, // 100MB
                'icon' => 'fa-archive',
                'color' => '#95a5a6'
            ],
            [
                'name' => 'Code',
                'slug' => 'code',
                'description' => 'Source code and programming files',
                'allowed_extensions' => 'php,js,html,css,json,xml,sql,py,java,cpp,c,h,sh',
                'max_file_size' => 5242880, // 5MB
                'icon' => 'fa-code',
                'color' => '#34495e'
            ],
            [
                'name' => 'Others',
                'slug' => 'others',
                'description' => 'Other file types not categorized',
                'allowed_extensions' => '*',
                'max_file_size' => 104857600, // 100MB
                'icon' => 'fa-file',
                'color' => '#7f8c8d'
            ]
        ];

        $stmt = $this->pdo->prepare("
            INSERT INTO file_categories 
            (name, slug, description, allowed_extensions, max_file_size, icon, color) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $insertedCount = 0;
        foreach ($categories as $category) {
            // Check if category already exists
            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM file_categories WHERE slug = ?");
            $checkStmt->execute([$category['slug']]);
            
            if ($checkStmt->fetchColumn() == 0) {
                $stmt->execute([
                    $category['name'],
                    $category['slug'],
                    $category['description'],
                    $category['allowed_extensions'],
                    $category['max_file_size'],
                    $category['icon'],
                    $category['color']
                ]);
                $insertedCount++;
                $this->log("  - Added category: {$category['name']}");
            } else {
                $this->log("  - Category already exists: {$category['name']}");
            }
        }

        $this->log("Seeded {$insertedCount} file categories successfully");
        return true;
    }

    public function down()
    {
        $this->log("Removing seeded file categories...");
        
        $this->execute("DELETE FROM file_categories WHERE slug IN (
            'documents', 'images', 'videos', 'audio', 'spreadsheets', 
            'presentations', 'archives', 'code', 'others'
        )");

        $this->log("File categories removed successfully");
        return true;
    }
}

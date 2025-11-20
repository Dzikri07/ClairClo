<?php
/**
 * upload.php - File upload handler with StorageManager
 * 
 * Features:
 * - Saves files to /uploads/<user_id>/ directory
 * - Validates file type and size against category rules
 * - Checks storage quota
 * - Generates thumbnails for images
 * - Stores metadata in database
 * - Tracks download stats
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../src/StorageManager.php';

// Check if user is logged in
session_start();
$userId = $_SESSION['user_id'] ?? null;

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Validate user is logged in
	if (!$userId) {
		http_response_code(401);
		$error = 'Please login to upload files';
		// Redirect or show error
		if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header('Content-Type: application/json');
			die(json_encode(['success' => false, 'message' => $error]));
		}
		header('Location: login.php?error=' . urlencode($error));
		exit;
	}

	// Check if file was uploaded
	// Helper: convert php size strings like "8M" to bytes
	function parsePhpSizeToBytes($size) {
		$size = trim($size);
		$unit = strtolower(substr($size, -1));
		$number = (int) $size;
		switch ($unit) {
			case 'g':
				return $number * 1024 * 1024 * 1024;
			case 'm':
				return $number * 1024 * 1024;
			case 'k':
				return $number * 1024;
			default:
				return (int) $size;
		}
	}

	// If POST body was dropped due to post_max_size, PHP will have empty $_FILES/$_POST.
	// Detect by comparing CONTENT_LENGTH to post_max_size and return a clear JSON error.
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
		$postMax = parsePhpSizeToBytes(ini_get('post_max_size'));
			if ($contentLength > 0 && $contentLength > $postMax && !isset($_FILES['upload_file'])) {
				$error = 'Uploaded data exceeds server post_max_size (' . ini_get('post_max_size') . ')';
				if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
					header('Content-Type: application/json');
					http_response_code(413);
					die(json_encode(['success' => false, 'message' => $error]));
				}
				header('Location: semuafile.php?upload=error&msg=' . urlencode($error));
				exit;
			}
	}

	// Check if file was uploaded
	if (!isset($_FILES['upload_file'])) {
		$error = 'No file selected';
		if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header('Content-Type: application/json');
			http_response_code(400);
			die(json_encode(['success' => false, 'message' => $error]));
		}
		header('Location: semuafile.php?upload=error&msg=' . urlencode($error));
		exit;
	}

	// Check upload errors
	$file = $_FILES['upload_file'];
	if ($file['error'] !== UPLOAD_ERR_OK) {
		$uploadErrors = [
			UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
			UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
			UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
			UPLOAD_ERR_NO_FILE => 'No file was uploaded',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
			UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
		];
		$error = $uploadErrors[$file['error']] ?? 'Unknown upload error';
		
		if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header('Content-Type: application/json');
			http_response_code(400);
			die(json_encode(['success' => false, 'message' => $error]));
		}
		header('Location: semuafile.php?upload=error&msg=' . urlencode($error));
		exit;
	}

	// Get optional description
	$description = $_POST['description'] ?? null;
	if ($description) {
		$description = trim(substr($description, 0, 500)); // Limit to 500 chars
	}

	try {
		// Use StorageManager to upload file
		$storage = new StorageManager();
		$result = $storage->uploadFile($file, $userId, $description);

		// Check if JSON response is expected
		if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header('Content-Type: application/json');
			if ($result['success']) {
				http_response_code(200);
			} else {
				http_response_code(400);
			}
			die(json_encode($result));
		}

		// Redirect based on result
		if ($result['success']) {
			header('Location: semuafile.php?upload=success&file_id=' . $result['file_id']);
			exit;
		} else {
			header('Location: semuafile.php?upload=error&msg=' . urlencode($result['message']));
			exit;
		}

	} catch (Exception $e) {
		$error = 'Upload failed: ' . $e->getMessage();
		
		if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
			header('Content-Type: application/json');
			http_response_code(500);
			die(json_encode(['success' => false, 'message' => $error]));
		}
		header('Location: semuafile.php?upload=error&msg=' . urlencode($error));
		exit;
	}
}

// Show upload form if GET request and user is logged in
if (!$userId) {
	header('Location: login.php');
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Upload File</title>
	<style>
		body { font-family: Arial, sans-serif; padding: 20px; }
		.container { max-width: 600px; margin: 0 auto; }
		.form-group { margin-bottom: 15px; }
		label { display: block; margin-bottom: 5px; font-weight: bold; }
		input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
		button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
		button:hover { background-color: #45a049; }
		.info { background-color: #f0f0f0; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
	</style>
</head>
<body>
	<div class="container">
		<h1>Upload File</h1>
		<div class="info">
			<p>Upload files to your cloud storage. Files will be saved in a private directory specific to your account.</p>
		</div>
		
		<form action="upload.php" method="post" enctype="multipart/form-data">
			<div class="form-group">
				<label for="upload_file">Select File:</label>
				<input type="file" id="upload_file" name="upload_file" required>
			</div>
			
			<div class="form-group">
				<label for="description">Description (optional):</label>
				<textarea id="description" name="description" rows="3" placeholder="Enter a description for this file..."></textarea>
			</div>
			
			<div class="form-group">
				<button type="submit">Upload</button>
				<a href="semuafile.php" style="margin-left: 10px; text-decoration: none;">
					<button type="button">Back to Files</button>
				</a>
			</div>
		</form>
	</div>
</body>
</html>
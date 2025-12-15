<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

// Set PDO error mode
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header('Location: Index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_success = false;
$upload_error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['action'])){
        if($_POST['action'] == 'submit'){
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image_data = $_POST['image_data'] ?? '';
            
            if(empty($title) || empty($description) || empty($image_data)){
                $upload_error = 'Please fill in all required fields.';
            } else {
                if (strpos($image_data, 'data:') === 0) {
                    $parts = explode(',', $image_data, 2);
                    if (count($parts) === 2) {
                        $type_part = $parts[0];
                        $encoded_data = $parts[1];
                        
                        $image_type = 'image/jpeg';
                        if (preg_match('/data:([^;]+)/', $type_part, $matches)) {
                            $image_type = $matches[1];
                        }
                        
                        if ($image_type === 'image/heic' || $image_type === 'image/heif') {
                            $image_type = 'image/jpeg';
                        }
                        
                        $encoded_data = str_replace(' ', '+', $encoded_data);
                        $decoded_image = base64_decode($encoded_data);
                        
                        if ($decoded_image !== false && strlen($decoded_image) > 0) {
                            try {
                     
                                $stmt = $pdo->prepare('INSERT INTO artworks (user_id, title, story, image, image_type) VALUES (?, ?, ?, ?, ?)');
                                
                                if($stmt->execute([$user_id, $title, $description, $decoded_image, $image_type])){
                                    $upload_success = true;
                                    
                                    echo '<script>
                                        document.getElementById("uploadForm").reset();
                                        document.getElementById("previewContainer").classList.remove("show");
                                        document.getElementById("emptyState").style.display = "block";
                                        draftData = { title: "", description: "", imageData: "", hasDraft: false };
                                    </script>';
                                } else {
                                    $upload_error = 'Failed to save. Please try again.';
                                }
                            } catch(PDOException $e) {
                                $upload_error = 'Database error: ' . $e->getMessage();
                                error_log('Upload error: ' . $e->getMessage());
                            }
                        } else {
                            $upload_error = 'Invalid image data. Please try again.';
                        }
                    } else {
                        $upload_error = 'Invalid image format.';
                    }
                } else {
                    $upload_error = 'Please upload a valid image file.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Art - African Art Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.7.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', Arial, sans-serif;
            color: #fff;
            position: relative;
            overflow-x: hidden;
            background: #000;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .video-background iframe {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 177.77vh;
            height: 100vh;
            min-width: 100%;
            min-height: 100%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        @media (min-aspect-ratio: 16/9) {
            .video-background iframe {
                width: 100vw;
                height: 56.25vw;
            }
        }

        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: -1;
        }

        .nav-links {
            position: fixed;
            top: 30px;
            right: 40px;
            display: flex;
            gap: 20px;
            z-index: 100;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            padding: 10px 25px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-links a:hover {
            background: rgba(211, 84, 0, 0.9);
            transform: translateY(-2px);
            border-color: rgba(211, 84, 0, 0.9);
        }

        .upload-wrapper {
            max-width: 1200px;
            margin: 80px auto 30px;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .upload-box {
            flex: 1;
            padding: 35px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .preview-container {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            display: none;
        }

        .preview-container.show {
            opacity: 1;
            transform: translateY(0);
            display: block;
        }

        .box-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .box-header h2 {
            font-size: 2rem;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 700;
        }

        .box-header p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-weight: 600;
            color: #fff;
            font-size: 1rem;
        }

        .form-group input[type="text"],
        .form-group textarea {
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d35400;
            box-shadow: 0 0 0 3px rgba(211, 84, 0, 0.2);
            background: #fff;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            font-size: 0.95rem;
            color: #555;
            transition: all 0.3s ease;
        }

        .form-group input[type="file"]:hover {
            border-color: #d35400;
            background: #fff;
        }

        .form-group input[type="file"]::file-selector-button {
            padding: 10px 20px;
            background: #d35400;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-group input[type="file"]::file-selector-button:hover {
            background: #e67e22;
        }

        .preview-section {
            text-align: center;
        }

        .preview-content {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 25px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .preview-content.has-draft {
            border: 2px solid rgba(211, 84, 0, 0.5);
            background: rgba(211, 84, 0, 0.05);
        }

        .preview-image {
            max-width: 100%;
            max-height: 280px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: none;
        }

        .preview-image.show {
            display: block;
        }

        .preview-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            text-align: center;
        }

        .preview-details {
            width: 100%;
            text-align: left;
            margin-top: 18px;
            padding: 18px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .preview-details.show {
            opacity: 1;
            transform: translateY(0);
            display: block;
        }

        .preview-details h4 {
            color: #fff;
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .preview-details p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 6px;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 22px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .action-buttons.show {
            opacity: 1;
            transform: translateY(0);
            display: flex;
        }

        .btn {
            flex: 1;
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #fff;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #3498db, #5dade2);
            color: #fff;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c, #ec7063);
            color: #fff;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
        }

        .btn-save-draft {
            background: linear-gradient(135deg, #f39c12, #f1c40f);
            color: #fff;
            width: 100%;
            margin-top: 8px;
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-save-draft:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(243, 156, 18, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 500;
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(212, 237, 218, 0.95);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: rgba(248, 215, 218, 0.95);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: 50px 30px;
            color: rgba(255, 255, 255, 0.7);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 0;
        }

        @media (max-width: 992px) {
            .upload-wrapper {
                flex-direction: column;
                margin: 70px auto 30px;
            }

            .upload-box {
                padding: 30px 25px;
            }

            .box-header h2 {
                font-size: 1.6rem;
            }

            .nav-links {
                top: 20px;
                right: 20px;
                gap: 10px;
            }

            .nav-links a {
                font-size: 0.9rem;
                padding: 8px 18px;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                position: relative;
                top: 0;
                right: 0;
                justify-content: center;
                margin: 15px 0;
            }
        }

        
        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- YouTube Video -->
    <div class="video-background">
        <iframe 
            src="https://www.youtube.com/embed/hOgVAYpHPCc?autoplay=1&mute=1&loop=1&playlist=hOgVAYpHPCc&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1"
            allow="autoplay; encrypted-media"
            allowfullscreen>
        </iframe>
    </div>
    <div class="video-overlay"></div>

    <!-- Navigation links-->
    <div class="nav-links">
        <a href="ArtPage.php">Home</a>
        <a href="discover.php">Discover</a>
    </div>


    <div class="upload-wrapper">
        <div class="upload-box">
            <div class="box-header">
                <h2>Create Your Post</h2>
                <p>Fill in the details below</p>
            </div>

            <?php if($upload_success): ?>
            <div class="alert alert-success">
                <i class="ri-check-line"></i> Your artwork has been uploaded successfully!
            </div>
            <?php endif; ?>

            <?php if($upload_error): ?>
            <div class="alert alert-error">
                <i class="ri-close-line"></i> <?php echo htmlspecialchars($upload_error); ?>
            </div>
            <?php endif; ?>

            <form id="uploadForm" class="upload-form">
                <div class="form-group">
                    <label for="title">Artwork Title</label>
                    <input type="text" id="title" name="title" required placeholder="Enter your artwork title">
                </div>

                <div class="form-group">
                    <label for="description">Story</label>
                    <textarea id="description" name="description" required placeholder="Tell the story behind your artwork..."></textarea>
                </div>

                <div class="form-group">
                    <label for="artwork">Upload Image</label>
                    <input type="file" id="artwork" name="artwork" accept="image/*,image/heic,image/heif" capture="environment" required>
                    <small style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-top: 4px; display: block;">
                        Supports all image formats including phone camera photos
                    </small>
                </div>

                <button type="button" class="btn-save-draft" onclick="saveDraft()">
                    <i class="ri-save-line"></i> Save Draft
                </button>
            </form>
        </div>

      
        <div class="upload-box">
            <div class="box-header">
                <h2>Preview & Publish</h2>
                <p>Review your artwork before posting</p>
            </div>

     
            <div class="empty-state" id="emptyState">
                <h3>No Draft Saved</h3>
                <p>Click "Save Draft" on the left to preview your artwork here</p>
            </div>

            <!-- important part, the preview part hidded until draft is saved by user -->
            <div class="preview-container" id="previewContainer">
                <div class="preview-section">
                    <div class="preview-content" id="previewContent">
                        <img id="previewImage" class="preview-image" alt="Preview">
                        <p class="preview-text" id="previewPlaceholder">
                            Image preview will appear here
                        </p>
                    </div>

                   <!--  preview to show title and description of saved draft -->
<div class="preview-details" id="previewDetails">
    <h4 id="previewTitle">Untitled Artwork</h4> 
    <p id="previewDescription">No story provided</p> 
</div>


<form method="POST" id="submitForm">

    <input type="hidden" name="title" id="hiddenTitle">
    <input type="hidden" name="description" id="hiddenDescription">
    <input type="hidden" name="image_data" id="hiddenImageData">
    <input type="hidden" name="action" value="submit">
    
    <!-- Buttons for editing, deleting, and submitting draft -->
    <div class="action-buttons" id="actionButtons">
        <button type="button" class="btn btn-edit" onclick="editDraft()">
            <i class="ri-edit-line"></i> Edit 
        </button>
        <button type="button" class="btn btn-delete" onclick="deleteDraft()">
            <i class="ri-delete-bin-line"></i> Clear 
        </button>
        <button type="submit" class="btn btn-submit">
            <i class="ri-send-plane-fill"></i> Publish 
        </button>
    </div>
</form>

<script>
    // here is to hold draft data in memory
    let draftData = {
        title: '',
        description: '',
        imageData: '',
        hasDraft: false
    };

    // Save draft function when user saves artwork
    function saveDraft() {
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const file = document.getElementById('artwork').files[0];
        
        // Checking if all fields are filled
        if (!title || !description || !file) {
            alert('Please enter a title, story, and select an image before saving.');
            return;
        }

        // for file size
        const maxSize = 10 * 1024 * 1024; 
        if (file.size > maxSize) {
            alert('Image is too large. Maximum size is 10MB.');
            return;
        }

        draftData.title = title;
        draftData.description = description;
        
        // Read image file as Base64
        const reader = new FileReader();
        reader.onload = function(e) {
            draftData.imageData = e.target.result;
            draftData.hasDraft = true;
            
            // Show preview container
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('previewContainer').classList.add('show');
            
            updatePreview(); 
            
            // Also store data in hidden form inputs
            document.getElementById('hiddenTitle').value = draftData.title;
            document.getElementById('hiddenDescription').value = draftData.description;
            document.getElementById('hiddenImageData').value = draftData.imageData;
            
            showMessage('Draft saved! Preview is now available.', 'success');
        };
        reader.readAsDataURL(file);
    }

    // Update the preview section with current draft data
    function updatePreview() {
        if (!draftData.hasDraft) return; 

        const previewImage = document.getElementById('previewImage');
        const previewPlaceholder = document.getElementById('previewPlaceholder');
        const previewContent = document.getElementById('previewContent');
        const previewDetails = document.getElementById('previewDetails');
        const actionButtons = document.getElementById('actionButtons');

        if (draftData.imageData) {
            previewImage.src = draftData.imageData;
            previewImage.classList.add('show');
            previewPlaceholder.style.display = 'none';
            previewContent.classList.add('has-draft');
        }

        // Update title and description in preview
        document.getElementById('previewTitle').textContent = draftData.title || 'Untitled Artwork';
        document.getElementById('previewDescription').textContent = draftData.description || 'No story provided';
        
        // Show the preview details and action buttons
        setTimeout(() => {
            previewDetails.classList.add('show');
            actionButtons.classList.add('show');
        }, 300);
    }

    // Show successful or failed messageds to user
    function showMessage(text, type) {
        const message = document.createElement('div');
        message.className = `alert alert-${type}`;
        message.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 1000; padding: 10px 15px; max-width: 280px; font-size: 0.85rem; animation: fadeIn 0.3s ease;';
        message.innerHTML = `<i class="ri-${type === 'success' ? 'check' : 'close'}-line"></i> ${text}`;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                if (message.parentNode) document.body.removeChild(message);
            }, 500);
        }, 3000);
    }

    function editDraft() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        document.getElementById('title').focus();
    }

    // Clear draft completely
    function deleteDraft() {
        if (confirm('Are you sure you want to clear this draft?')) {
            document.getElementById('uploadForm').reset();
            draftData = { title: '', description: '', imageData: '', hasDraft: false };
            document.getElementById('previewContainer').classList.remove('show');
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('hiddenTitle').value = '';
            document.getElementById('hiddenDescription').value = '';
            document.getElementById('hiddenImageData').value = '';
            showMessage('Draft cleared', 'success');
        }
    }

    // Handle form submit
    document.getElementById('submitForm').addEventListener('submit', function(e) {
        if (!draftData.hasDraft) {
            e.preventDefault();
            alert('Please save a draft first before publishing.');
            return;
        }
        
        if (!draftData.title || !draftData.description || !draftData.imageData) {
            e.preventDefault();
            alert('Please fill in all required fields before publishing.');
            return;
        }
        
        const submitBtn = this.querySelector('.btn-submit');
        submitBtn.innerHTML = '<i class="ri-loader-4-line spin"></i> Publishing...';
        submitBtn.disabled = true;
    });

    // Update preview as the user types
    document.getElementById('title').addEventListener('input', function() {
        if (draftData.hasDraft) {
            draftData.title = this.value;
            document.getElementById('previewTitle').textContent = this.value || 'Untitled Artwork';
            document.getElementById('hiddenTitle').value = this.value;
        }
    });

    document.getElementById('description').addEventListener('input', function() {
        if (draftData.hasDraft) {
            draftData.description = this.value;
            document.getElementById('previewDescription').textContent = this.value || 'No story provided';
            document.getElementById('hiddenDescription').value = this.value;
        }
    });

    // Update preview image if user selects a new file after draft is saved
    document.getElementById('artwork').addEventListener('change', function() {
        if (this.files[0] && draftData.hasDraft) {
            const reader = new FileReader();
            reader.onload = function(e) {
                draftData.imageData = e.target.result;
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('hiddenImageData').value = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>

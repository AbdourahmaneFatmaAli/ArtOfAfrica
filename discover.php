<?php

session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header('Location: Index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// if user wants to delete, support CRUD opperatiions
if (isset($_POST['delete_art']) && isset($_POST['art_id'])) {
    $art_id_to_delete = $_POST['art_id'];
    
    // verify if the user owns that artwork before they delete
    $check_stmt = $pdo->prepare('SELECT user_id FROM artworks WHERE art_id = ?');
    $check_stmt->execute([$art_id_to_delete]);
    $artwork_owner = $check_stmt->fetch();
    
    if ($artwork_owner && $artwork_owner['user_id'] == $user_id) {
        $delete_stmt = $pdo->prepare('DELETE FROM artworks WHERE art_id = ?');
        if ($delete_stmt->execute([$art_id_to_delete])) {
            $_SESSION['success'] = 'Artwork deleted successfully!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// AJAX liking a post or unliking it
if (isset($_POST['ajax']) && $_POST['ajax'] == 'toggle_like' && isset($_POST['art_id'])) {
    $art_id = (int)$_POST['art_id'];
    
    try {
        // Check if the like already exists
        $check_stmt = $pdo->prepare('SELECT like_id FROM likes WHERE user_id = ? AND art_id = ?');
        $check_stmt->execute([$user_id, $art_id]);
        $existing_like = $check_stmt->fetch();
        
        if ($existing_like) {
            // unlike by removing from database
            $delete_stmt = $pdo->prepare('DELETE FROM likes WHERE like_id = ?');
            $delete_stmt->execute([$existing_like['like_id']]);
            
            $new_like_count = countLikes($pdo, $art_id);
            echo json_encode(['status' => 'unliked', 'likes' => $new_like_count]);
        } else {
            // like add to database
            $insert_stmt = $pdo->prepare('INSERT INTO likes (user_id, art_id, created_at) VALUES (?, ?, NOW())');
            $insert_stmt->execute([$user_id, $art_id]);
            
            $new_like_count = countLikes($pdo, $art_id);
            echo json_encode(['status' => 'liked', 'likes' => $new_like_count]);
        }
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// AJAX for comments
if (isset($_POST['ajax']) && $_POST['ajax'] == 'add_comment' && isset($_POST['art_id']) && isset($_POST['comment'])) {
    $art_id = (int)$_POST['art_id'];
    $comment_text = trim($_POST['comment']);
    
    if (!empty($comment_text)) {
        try {
            // Insert comment into comments table
            $stmt = $pdo->prepare('INSERT INTO comments (user_id, art_id, content, author, email, created_at) 
                                   VALUES (?, ?, ?, ?, ?, NOW())');
            
            $result = $stmt->execute([
                $user_id,
                $art_id,
                $comment_text,
                $_SESSION['username'] ?? 'Anonymous',
                $_SESSION['email'] ?? ''
            ]);
            
            if ($result) {
                $comment_id = $pdo->lastInsertId();
                $comment_html = '<div class="comment" id="comment-' . $comment_id . '">
                    <div class="comment-header">
                        <span class="comment-author">' . htmlspecialchars($_SESSION['username'] ?? 'Anonymous') . '</span>
                        <span class="comment-time">Just now</span>
                    </div>
                    <div class="comment-text">' . htmlspecialchars($comment_text) . '</div>
                </div>';
                
                echo json_encode(['success' => true, 'comment' => $comment_html]);
            } else {
                echo json_encode(['error' => 'Failed to save comment']);
            }
        } catch(Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Comment cannot be empty']);
    }
    exit();
}

// Using AJAX for loading comments
if (isset($_GET['ajax']) && $_GET['ajax'] == 'load_comments' && isset($_GET['art_id'])) {
    $art_id = (int)$_GET['art_id'];
    
    try {
        // Get comments for the selected artwork
        $stmt = $pdo->prepare('SELECT c.*, u.username 
                               FROM comments c 
                               LEFT JOIN users u ON c.user_id = u.user_id 
                               WHERE c.art_id = ? 
                               ORDER BY c.created_at DESC 
                               LIMIT 10');
        $stmt->execute([$art_id]);
        $comments = $stmt->fetchAll();
        
        $comments_html = '';
        if (empty($comments)) {
            $comments_html = '<div class="no-comments">No comments yet. Be the first to comment!</div>';
        } else {
            foreach($comments as $comment) {
                $comment_date = new DateTime($comment['created_at']);
                $comment_time = $comment_date->format('M d, H:i');
                
                $comments_html .= '<div class="comment" id="comment-' . $comment['comment_id'] . '">
                    <div class="comment-header">
                        <span class="comment-author">' . htmlspecialchars($comment['username'] ?? $comment['author'] ?? 'Anonymous') . '</span>
                        <span class="comment-time">' . $comment_time . '</span>
                    </div>
                    <div class="comment-text">' . htmlspecialchars($comment['content']) . '</div>
                </div>';
            }
        }
        
        echo json_encode(['success' => true, 'comments' => $comments_html]);
    } catch(Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// image request handling
if (isset($_GET['get_image']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $art_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare('SELECT image, image_type FROM artworks WHERE art_id = ?');
        $stmt->execute([$art_id]);
        $artwork = $stmt->fetch();
        
        if ($artwork && !empty($artwork['image'])) {
            header('Content-Type: ' . ($artwork['image_type'] ?: 'image/jpeg'));
            header('Content-Length: ' . strlen($artwork['image']));
            echo $artwork['image'];
            exit();
        }
    } catch(Exception $e) {
        
    }
    
    // Show placeholder if there is no image
    header('Content-Type: image/svg+xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#2c3e50"/>
        <text x="50%" y="50%" font-family="Arial" font-size="18" fill="#ecf0f1" 
              text-anchor="middle" dy=".3em">Image Not Found</text>
    </svg>';
    exit();
}

// using database and count function to count likes 
function countLikes($pdo, $art_id) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM likes WHERE art_id = ?');
        $stmt->execute([$art_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

//function to check if user liked an artwork
function userLiked($pdo, $user_id, $art_id) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND art_id = ?');
        $stmt->execute([$user_id, $art_id]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    } catch(Exception $e) {
        return false;
    }
}

// function to count comments
function countComments($pdo, $art_id) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM comments WHERE art_id = ?');
        $stmt->execute([$art_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch(Exception $e) {
        return 0;
    }
}

// Get artworks without loading data
try {
    // Get recent artworks 3 most recent in this case
    $recent_stmt = $pdo->prepare('
        SELECT a.art_id, a.title, a.story, a.created_at, u.username, u.email, u.user_id
        FROM artworks a 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.created_at DESC 
        LIMIT 3
    ');
    $recent_stmt->execute();
    $recent_artworks = $recent_stmt->fetchAll();
    
    // Get other artworks all except the 3 recent ones
    $other_stmt = $pdo->prepare('
        SELECT a.art_id, a.title, a.story, a.created_at, u.username, u.email, u.user_id
        FROM artworks a 
        JOIN users u ON a.user_id = u.user_id 
        LEFT JOIN (
            SELECT art_id FROM artworks ORDER BY created_at DESC LIMIT 3
        ) AS recent ON a.art_id = recent.art_id
        WHERE recent.art_id IS NULL
        ORDER BY a.created_at DESC
    ');
    $other_stmt->execute();
    $other_artworks = $other_stmt->fetchAll();
    
} catch(Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover - African Art Gallery</title>
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

        .discover-wrapper {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 0 20px;
        }

        .section-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .section-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }

      =
        .recent-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .other-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

 =
        .artwork-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
        }

        .artwork-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            border-color: rgba(211, 84, 0, 0.5);
        }

        .artwork-image-container {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .artwork-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .artwork-card:hover .artwork-image {
            transform: scale(1.05);
        }

        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 71, 87, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .artwork-card:hover .delete-btn {
            opacity: 1;
        }

        .delete-btn:hover {
            background: rgba(255, 71, 87, 1);
            transform: scale(1.1);
        }

        
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .delete-modal-content {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .delete-modal h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #fff;
        }

        .delete-modal p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 25px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .cancel-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .confirm-delete-btn {
            background: linear-gradient(135deg, #ff4757, #ff3838);
            color: white;
        }

        .confirm-delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.4);
        }

        .artwork-content {
            padding: 25px;
        }

        .artwork-header {
            margin-bottom: 15px;
        }

        .artwork-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .artist-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .artist-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d35400, #e67e22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 1.2rem;
        }

        .artist-name {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .artwork-story {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
            min-height: 60px;
        }

       
        .action-buttons {
            display: flex;
            gap: 10px; 
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-wrap: wrap;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px; 
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem; 
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            flex: 1; 
            min-width: 0; 
            min-height: 40px;
            white-space: nowrap;
        }

        .action-btn span:not(.like-count):not(.comment-count) {
            font-size: 0.85rem; 
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .like-btn {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.1), rgba(255, 71, 87, 0.2));
            border-color: rgba(255, 71, 87, 0.3);
        }

        .like-btn.liked {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.3), rgba(255, 71, 87, 0.4));
            color: #ff4757;
            border-color: rgba(255, 71, 87, 0.5);
        }

        .like-btn.liked:hover {
            background: linear-gradient(135deg, rgba(255, 71, 87, 0.4), rgba(255, 71, 87, 0.5));
        }

        .comment-btn {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(39, 174, 96, 0.2));
            border-color: rgba(39, 174, 96, 0.3);
        }

        .comment-btn:hover {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.2), rgba(39, 174, 96, 0.3));
        }

        /* Immersive View Button */
        .immersive-btn {
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.1), rgba(155, 89, 182, 0.2));
            border-color: rgba(155, 89, 182, 0.3);
        }

        .immersive-btn:hover {
            background: linear-gradient(135deg, rgba(155, 89, 182, 0.2), rgba(155, 89, 182, 0.3));
        }

        .like-count, .comment-count {
            font-size: 0.8rem; 
            margin-left: 2px; 
            font-weight: 600;
            min-width: 16px; 
            text-align: center;
        }

        .like-btn.liked .like-count {
            color: #ff4757;
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .immersive-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .immersive-modal-content {
            max-width: 1000px;
            width: 100%;
            max-height: 90vh;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .immersive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .immersive-title {
            font-size: 1.8rem;
            color: #fff;
            font-weight: 700;
        }

        .close-immersive {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-immersive:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .immersive-body {
            display: flex;
            height: calc(90vh - 120px);
        }

        .immersive-image-container {
            flex: 2;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .immersive-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .immersive-details {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.2);
        }

        .immersive-artist-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .immersive-artist-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d35400, #e67e22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            font-size: 1.8rem;
        }

        .immersive-artist-name {
            font-size: 1.3rem;
            color: #fff;
            font-weight: 600;
        }

        .immersive-artist-email {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .immersive-story {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #d35400;
        }

        .immersive-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

       
        .contact-section {
            margin-top: 15px;
        }

        .contact-toggle {
            width: 100%;
            padding: 12px 15px;
            background: rgba(211, 84, 0, 0.1);
            border: 1px solid rgba(211, 84, 0, 0.3);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .contact-toggle:hover {
            background: rgba(211, 84, 0, 0.2);
            transform: translateY(-2px);
        }

        .contact-info {
            margin-top: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: none;
        }

        .contact-info.show {
            display: block;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .contact-item i {
            color: #d35400;
            width: 20px;
            font-size: 1.1rem;
        }

      
        .comments-section {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
            padding-right: 10px;
        }

        .comments-section::-webkit-scrollbar {
            width: 6px;
        }

        .comments-section::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .comments-section::-webkit-scrollbar-thumb {
            background: rgba(211, 84, 0, 0.5);
            border-radius: 3px;
        }

        .comment {
            margin-bottom: 12px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 3px solid #d35400;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .comment-author {
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
        }

        .comment-time {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
        }

        .comment-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .add-comment-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .comment-input {
            flex: 1;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .comment-input:focus {
            outline: none;
            border-color: #d35400;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(211, 84, 0, 0.1);
        }

        .submit-comment {
            padding: 12px 24px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .submit-comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .submit-comment:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .success-message {
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

      
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .upload-cta {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .upload-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.4);
        }

        .no-comments {
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        @media (max-width: 992px) {
            .recent-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .other-grid {
                grid-template-columns: 1fr;
            }
            
            .immersive-body {
                flex-direction: column;
                height: auto;
            }
            
            .immersive-image-container {
                flex: none;
                height: 50vh;
            }
            
            .immersive-details {
                flex: none;
                max-height: 40vh;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                top: 20px;
                right: 20px;
                gap: 10px;
            }

            .nav-links a {
                font-size: 0.9rem;
                padding: 8px 18px;
            }

            .discover-wrapper {
                margin: 80px auto 30px;
            }

            .section-header h2 {
                font-size: 2rem;
            }
            
            .delete-btn {
                opacity: 1;
            }
            
            .action-buttons {
                flex-direction: row; 
                gap: 8px;
            }
            
            .action-btn {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            
            .action-btn span:not(.like-count):not(.comment-count) {
                font-size: 0.8rem;
            }
            
            .immersive-modal-content {
                border-radius: 10px;
            }
            
            .immersive-header {
                padding: 15px 20px;
            }
            
            .immersive-title {
                font-size: 1.4rem;
            }
        }

 
        @media (max-width: 480px) {
            .action-btn span:not(.like-count):not(.comment-count) {
                display: none;
            }
            
            .action-btn {
                padding: 8px;
                width: 40px; 
                justify-content: center;
                flex: none;
            }
            
            .action-btn i {
                font-size: 1.1rem;
                margin: 0;
            }
            
            .like-count, .comment-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background: rgba(211, 84, 0, 0.9);
                color: white;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                font-size: 0.7rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .action-btn {
                position: relative;
            }
            
            .like-btn .like-count,
            .comment-btn .comment-count {
                display: flex; 
            }
        }

        @media (max-width: 400px) {
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: flex-start;
                padding: 10px 15px;
            }
            
            .action-btn span:not(.like-count):not(.comment-count) {
                display: inline; 
                font-size: 0.9rem;
            }
            
            .action-btn i {
                margin-right: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- YouTube Video for the background -->
    <div class="video-background">
        <iframe 
            src="https://www.youtube.com/embed/hOgVAYpHPCc?autoplay=1&mute=1&loop=1&playlist=hOgVAYpHPCc&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1"
            allow="autoplay; encrypted-media"
            allowfullscreen>
        </iframe>
    </div>
    <div class="video-overlay"></div>

    <!-- Navigations -->
    <div class="nav-links">
        <a href="ArtPage.php">Home</a>
        <a href="upload_art.php">Upload</a>
    </div>

    <!-- Delete confirmaton -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <h3>Delete Artwork</h3>
            <p>Are you sure you want to delete this artwork? This action cannot be undone.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="art_id" id="deleteArtId">
                <input type="hidden" name="delete_art" value="1">
                <div class="modal-buttons">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="modal-btn confirm-delete-btn">Delete</button>
                </div>
            </form>
        </div>
    </div>

    

    <!-- Main Content -->
    <div class="discover-wrapper">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="success-message">
                <i class="ri-check-line"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Recent Artworks Section -->
        <div class="section-header">
            <h2>Recent Artworks</h2>
            <p>Latest 3 creations from our community</p>
        </div>

        <?php if(count($recent_artworks) > 0): ?>
            <div class="recent-grid">
                <?php foreach($recent_artworks as $artwork): 
                    $date = new DateTime($artwork['created_at']);
                    $formattedDate = $date->format('M d, Y');
                    $avatarLetter = strtoupper(substr($artwork['username'], 0, 1));
                    $isOwner = ($artwork['user_id'] == $user_id);
                    $likeCount = countLikes($pdo, $artwork['art_id']);
                    $userHasLiked = userLiked($pdo, $user_id, $artwork['art_id']);
                    $commentCount = countComments($pdo, $artwork['art_id']);
                ?>
                <div class="artwork-card" data-art-id="<?php echo $artwork['art_id']; ?>">
                    <div class="artwork-image-container">
                        <img 
                            src="<?php echo $_SERVER['PHP_SELF']; ?>?get_image=1&id=<?php echo $artwork['art_id']; ?>" 
                            alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                            class="artwork-image"
                            loading="lazy"
                            onerror="this.onerror=null; this.src='data:image/svg+xml;base64,<?php echo base64_encode('<?xml version="1.0" encoding="UTF-8"?>
                            <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
                                <rect width="100%" height="100%" fill="#2c3e50"/>
                                <text x="50%" y="50%" font-family="Arial" font-size="16" fill="#ecf0f1" 
                                      text-anchor="middle" dy=".3em">'.htmlspecialchars($artwork['title']).'</text>
                            </svg>'); ?>'"
                        >
                        <?php if($isOwner): ?>
                            <button class="delete-btn" onclick="showDeleteModal(<?php echo $artwork['art_id']; ?>)">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artwork-content">
                        <div class="artwork-header">
                            <h3 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                        </div>
                        
                        <div class="artist-info">
                            <div class="artist-avatar">
                                <?php echo $avatarLetter; ?>
                            </div>
                            <span class="artist-name"><?php echo htmlspecialchars($artwork['username']); ?></span>
                        </div>
                        
                        <p class="artwork-story"><?php echo htmlspecialchars($artwork['story']); ?></p>
                        
                        <!-- button for immerssive view -->
                        <div class="action-buttons">
                            <button class="action-btn like-btn <?php echo $userHasLiked ? 'liked' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $artwork['art_id']; ?>, this)"
                                    data-liked="<?php echo $userHasLiked ? '1' : '0'; ?>"
                                    data-art-id="<?php echo $artwork['art_id']; ?>">
                                <i class="ri-heart-<?php echo $userHasLiked ? 'fill' : 'line'; ?>"></i>
                                <span>Like</span>
                                <span class="like-count"><?php echo $likeCount; ?></span>
                            </button>
                            
                            <a href="vr_gallery.php?art_id=<?php echo $artwork['art_id']; ?>" class="action-btn immersive-btn">
                                <i class="ri-vr-line"></i>
                                <span>VR View</span>
                            </a>
                        
                        <!-- Comments Section -->
                        <div id="comments-<?php echo $artwork['art_id']; ?>" class="comments-section" style="display: none;">
                            <!-- Comments will be loaded using AJAX -->
                            <div class="no-comments">Loading comments...</div>
                        </div>
                        
                        <!-- Add Comment form -->
                        <form id="comment-form-<?php echo $artwork['art_id']; ?>" class="add-comment-form" style="display: none;" 
                              onsubmit="addComment(<?php echo $artwork['art_id']; ?>); return false;">
                            <input type="text" class="comment-input" placeholder="Add a comment..." required
                                   id="comment-input-<?php echo $artwork['art_id']; ?>">
                            <button type="submit" class="submit-comment" 
                                    id="submit-comment-<?php echo $artwork['art_id']; ?>">
                                Post
                            </button>
                        </form>
                        
                        <!-- Contact Section -->
                        <div class="contact-section">
                            <button class="contact-toggle" onclick="toggleContact(<?php echo $artwork['art_id']; ?>)">
                                <span>contact the Artist</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <div id="contact-<?php echo $artwork['art_id']; ?>" class="contact-info">
                                <div class="contact-item">
                                    <i class="ri-user-line"></i>
                                    <span><?php echo htmlspecialchars($artwork['username']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="ri-mail-line"></i>
                                    <span><?php echo htmlspecialchars($artwork['email']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="ri-calendar-line"></i>
                                    <span>Posted: <?php echo $formattedDate; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="ri-image-line"></i>
                <h3>No Recent Artworks</h3>
                <p>Be the first to upload an artwork!</p>
                <a href="upload_art.php" class="upload-cta">Upload Artwork</a>
            </div>
        <?php endif; ?>

        <!-- Other Artworks Section -->
        <div class="section-header" style="margin-top: 60px;">
            <h2>More Artworks</h2>
            <p>Explore more creations from our community</p>
        </div>

        <?php if(count($other_artworks) > 0): ?>
            <div class="other-grid">
                <?php foreach($other_artworks as $artwork): 
                    $date = new DateTime($artwork['created_at']);
                    $formattedDate = $date->format('M d, Y');
                    $avatarLetter = strtoupper(substr($artwork['username'], 0, 1));
                    $isOwner = ($artwork['user_id'] == $user_id);
                    $likeCount = countLikes($pdo, $artwork['art_id']);
                    $userHasLiked = userLiked($pdo, $user_id, $artwork['art_id']);
                    $commentCount = countComments($pdo, $artwork['art_id']);
                ?>
                <div class="artwork-card" data-art-id="<?php echo $artwork['art_id']; ?>">
                    <div class="artwork-image-container">
                        <img 
                            src="<?php echo $_SERVER['PHP_SELF']; ?>?get_image=1&id=<?php echo $artwork['art_id']; ?>" 
                            alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                            class="artwork-image"
                            loading="lazy"
                            onerror="this.onerror=null; this.src='data:image/svg+xml;base64,<?php echo base64_encode('<?xml version="1.0" encoding="UTF-8"?>
                            <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
                                <rect width="100%" height="100%" fill="#2c3e50"/>
                                <text x="50%" y="50%" font-family="Arial" font-size="16" fill="#ecf0f1" 
                                      text-anchor="middle" dy=".3em">'.htmlspecialchars($artwork['title']).'</text>
                            </svg>'); ?>'"
                        >
                        <?php if($isOwner): ?>
                            <button class="delete-btn" onclick="showDeleteModal(<?php echo $artwork['art_id']; ?>)">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artwork-content">
                        <div class="artwork-header">
                            <h3 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                        </div>
                        
                        <div class="artist-info">
                            <div class="artist-avatar">
                                <?php echo $avatarLetter; ?>
                            </div>
                            <span class="artist-name"><?php echo htmlspecialchars($artwork['username']); ?></span>
                        </div>
                        
                        <p class="artwork-story"><?php echo htmlspecialchars($artwork['story']); ?></p>
                        
                        <!-- Action Buttons-->
                        <div class="action-buttons">
                            <button class="action-btn like-btn <?php echo $userHasLiked ? 'liked' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $artwork['art_id']; ?>, this)"
                                    data-liked="<?php echo $userHasLiked ? '1' : '0'; ?>"
                                    data-art-id="<?php echo $artwork['art_id']; ?>">
                                <i class="ri-heart-<?php echo $userHasLiked ? 'fill' : 'line'; ?>"></i>
                                <span>Like</span>
                                <span class="like-count"><?php echo $likeCount; ?></span>
                            </button>
                            
                            <button class="action-btn comment-btn" 
                                    onclick="toggleComments(<?php echo $artwork['art_id']; ?>, this)"
                                    data-art-id="<?php echo $artwork['art_id']; ?>">
                                <i class="ri-chat-3-line"></i>
                                <span>Comment</span>
                                <span class="comment-count"><?php echo $commentCount; ?></span>
                            </button>
                            
                            <button class="action-btn immersive-btn" 
                                    onclick="openImmersiveView(<?php echo $artwork['art_id']; ?>, '<?php echo htmlspecialchars($artwork['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($artwork['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($artwork['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($artwork['story'], ENT_QUOTES); ?>', '<?php echo $avatarLetter; ?>', '<?php echo $formattedDate; ?>')">
                                <i class="ri-fullscreen-line"></i>
                                <span>Immersive View</span>
                            </button>
                        </div>
                        
                        <!-- Comments Section -->
                        <div id="comments-<?php echo $artwork['art_id']; ?>" class="comments-section" style="display: none;">
                            <!-- Comments will be loaded via AJAX -->
                            <div class="no-comments">Loading comments...</div>
                        </div>
                        
                        <!-- Addig Comment form -->
                        <form id="comment-form-<?php echo $artwork['art_id']; ?>" class="add-comment-form" style="display: none;"
                              onsubmit="addComment(<?php echo $artwork['art_id']; ?>); return false;">
                            <input type="text" class="comment-input" placeholder="Add a comment..." required
                                   id="comment-input-<?php echo $artwork['art_id']; ?>">
                            <button type="submit" class="submit-comment" 
                                    id="submit-comment-<?php echo $artwork['art_id']; ?>">
                                Post
                            </button>
                        </form>
                        
                        <!-- Contact Section -->
                        <div class="contact-section">
                            <button class="contact-toggle" onclick="toggleContact(<?php echo $artwork['art_id']; ?>)">
                                <span>Contact Artist</span>
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                            <div id="contact-<?php echo $artwork['art_id']; ?>" class="contact-info">
                                <div class="contact-item">
                                    <i class="ri-user-line"></i>
                                    <span><?php echo htmlspecialchars($artwork['username']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="ri-mail-line"></i>
                                    <span><?php echo htmlspecialchars($artwork['email']); ?></span>
                                </div>
                                <div class="contact-item">
                                    <i class="ri-calendar-line"></i>
                                    <span>Posted: <?php echo $formattedDate; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php elseif(count($recent_artworks) == 0): ?>
            <div class="empty-state">
                <i class="ri-image-line"></i>
                <h3>No Artworks Yet</h3>
                <p>Be the first to share your art with the community!</p>
                <a href="upload_art.php" class="upload-cta">Upload Artwork</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Deletefunctions
        function showDeleteModal(artId) {
            document.getElementById('deleteArtId').value = artId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // VR Functions
        function openImmersiveView(artId, title, artist, email, story, avatarLetter, date) {
            // Set the content
            document.getElementById('immersiveTitle').textContent = title;
            document.getElementById('immersiveArtist').textContent = artist;
            document.getElementById('immersiveEmail').textContent = email;
            document.getElementById('immersiveStory').textContent = story;
            document.getElementById('immersiveAvatar').textContent = avatarLetter;
            document.getElementById('immersiveDate').textContent = 'Posted: ' + date;
            
            // Set the image
            const imageUrl = '<?php echo $_SERVER['PHP_SELF']; ?>?get_image=1&id=' + artId;
            document.getElementById('immersiveImage').src = imageUrl;
            
            // Show the modal
            document.getElementById('immersiveModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImmersiveView() {
            document.getElementById('immersiveModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close immersive modal when clicking outside
        document.getElementById('immersiveModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImmersiveView();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('immersiveModal').style.display === 'flex') {
                closeImmersiveView();
            }
        });

        // contact info
        function toggleContact(artId) {
            const contactInfo = document.getElementById('contact-' + artId);
            const toggleBtn = event.currentTarget;
            const icon = toggleBtn.querySelector('i');
            
            if (contactInfo.classList.contains('show')) {
                contactInfo.classList.remove('show');
                icon.classList.remove('ri-arrow-up-s-line');
                icon.classList.add('ri-arrow-down-s-line');
            } else {
                contactInfo.classList.add('show');
                icon.classList.remove('ri-arrow-down-s-line');
                icon.classList.add('ri-arrow-up-s-line');
            }
        }

        // Toggle comments
        async function toggleComments(artId, button) {
            const commentsSection = document.getElementById('comments-' + artId);
            const commentForm = document.getElementById('comment-form-' + artId);
            
            if (commentsSection.style.display === 'none' || commentsSection.style.display === '') {
                // Show and load comments
                commentsSection.style.display = 'block';
                commentForm.style.display = 'flex';
                
                // Load comments
                try {
                    const response = await fetch('?ajax=load_comments&art_id=' + artId);
                    const data = await response.json();
                    
                    if (data.success) {
                        commentsSection.innerHTML = data.comments;
                    } else {
                        commentsSection.innerHTML = '<div class="no-comments">Error loading comments</div>';
                    }
                } catch (error) {
                    commentsSection.innerHTML = '<div class="no-comments">Error loading comments</div>';
                }
                
            
                document.getElementById('comment-input-' + artId).focus();
            } else {
                commentsSection.style.display = 'none';
                commentForm.style.display = 'none';
            }
        }

        // Toggle likes
        async function toggleLike(artId, button) {
            const likeIcon = button.querySelector('i');
            const likeCountSpan = button.querySelector('.like-count');
            const isLiked = button.getAttribute('data-liked') === '1';
            
          
            button.style.opacity = '0.7';
            button.style.cursor = 'wait';
            
            try {
                const formData = new FormData();
                formData.append('ajax', 'toggle_like');
                formData.append('art_id', artId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                // Update UI based on responses
                if (data.status === 'liked') {
                    likeIcon.classList.remove('ri-heart-line');
                    likeIcon.classList.add('ri-heart-fill');
                    button.classList.add('liked');
                    button.setAttribute('data-liked', '1');
                    
                    // Heart animation for likes
                    likeIcon.style.animation = 'none';
                    setTimeout(() => {
                        likeIcon.style.animation = 'heartBeat 0.5s ease';
                    }, 10);
                } else {
                    likeIcon.classList.remove('ri-heart-fill');
                    likeIcon.classList.add('ri-heart-line');
                    button.classList.remove('liked');
                    button.setAttribute('data-liked', '0');
                }
                
                // for like count
                likeCountSpan.textContent = data.likes;
                
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                button.style.opacity = '1';
                button.style.cursor = 'pointer';
            }
        }

        // Add comment
        async function addComment(artId) {
            const commentInput = document.getElementById('comment-input-' + artId);
            const submitBtn = document.getElementById('submit-comment-' + artId);
            const commentText = commentInput.value.trim();
            
            if (!commentText) {
                alert('Please enter a comment');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';
            
            try {
                const formData = new FormData();
                formData.append('ajax', 'add_comment');
                formData.append('art_id', artId);
                formData.append('comment', commentText);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                if (data.success) {
                    // Clear input
                    commentInput.value = '';
                    
                    // Add new comment to the top
                    const commentsSection = document.getElementById('comments-' + artId);
                    const noComments = commentsSection.querySelector('.no-comments');
                    
                    if (noComments) {
                        noComments.remove();
                    }
                    
                  
                }

            }
                
          
        }

        // Show message successful or failed
        function showMessage(text, type) {
            const message = document.createElement('div');
            message.className = 'success-message';
            message.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 1000; padding: 15px 25px; max-width: 300px;';
            message.innerHTML = `<i class="ri-${type === 'success' ? 'check' : 'close'}-line"></i> ${text}`;
            
            document.body.appendChild(message);
            
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (message.parentNode) {
                        document.body.removeChild(message);
                    }
                }, 500);
            }, 3000);
        }

        // handling the error of image
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.artwork-image');
            
            images.forEach(img => {
                img.addEventListener('error', function() {
                    const colors = ['#2c3e50', '#34495e', '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#d35400'];
                    const randomColor = colors[Math.floor(Math.random() * colors.length)];
                    
                    const svg = `<svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
                        <rect width="100%" height="100%" fill="${randomColor}"/>
                        <text x="50%" y="50%" font-family="Arial" font-size="16" fill="#ecf0f1" 
                              text-anchor="middle" dy=".3em">Image Loading Error</text>
                    </svg>`;
                    
                    this.src = 'data:image/svg+xml;base64,' + btoa(svg);
                });
            });
        });

        // submit comment
        document.addEventListener('keypress', function(e) {
            if (e.target.classList.contains('comment-input') && e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const artId = e.target.id.split('-')[2];
                addComment(artId);
            }
        });
    </script>
</body>
</html>
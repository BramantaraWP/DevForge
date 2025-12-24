<?php
/**
 * DevForge - Social Media Platform for Programmers
 * Single File PHP Application with File-based Storage
 */

// ============================================
// KONFIGURASI & INISIALISASI
// ============================================

session_start();
ob_start();

// Konfigurasi
define('APP_NAME', 'DevForge');
define('DATA_DIR', __DIR__ . '/data/');
define('UPLOADS_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);

// Buat direktori jika belum ada
if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
if (!file_exists(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0777, true);

// File data
$users_file = DATA_DIR . 'users.json';
$posts_file = DATA_DIR . 'posts.json';
$chats_file = DATA_DIR . 'chats.json';
$notifications_file = DATA_DIR . 'notifications.json';

// Inisialisasi file data
init_data_files();

// ============================================
// FUNGSI UTILITAS
// ============================================

function init_data_files() {
    global $users_file, $posts_file, $chats_file, $notifications_file;
    
    $default_users = [
        [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'display_name' => 'Admin DevForge',
            'email' => 'admin@devforge.com',
            'bio' => 'Founder and Developer of DevForge Platform',
            'role' => 'Founder & Developer',
            'avatar' => 'https://files.clxgo.my.id/9CWgw.jpeg',
            'banner' => '',
            'banner_is_video' => false,
            'checkmark_type' => 'blue',
            'custom_checkmark' => '',
            'is_verified' => true,
            'privacy' => 'public',
            'followers' => [],
            'following' => [],
            'created_at' => time(),
            'posts_count' => 0,
            'followers_count' => 0,
            'following_count' => 0
        ]
    ];
    
    if (!file_exists($users_file)) {
        file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
    }
    
    if (!file_exists($posts_file)) {
        file_put_contents($posts_file, json_encode([], JSON_PRETTY_PRINT));
    }
    
    if (!file_exists($chats_file)) {
        file_put_contents($chats_file, json_encode([], JSON_PRETTY_PRINT));
    }
    
    if (!file_exists($notifications_file)) {
        file_put_contents($notifications_file, json_encode([], JSON_PRETTY_PRINT));
    }
}

function get_users() {
    global $users_file;
    return json_decode(file_get_contents($users_file), true) ?: [];
}

function save_users($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

function get_posts() {
    global $posts_file;
    return json_decode(file_get_contents($posts_file), true) ?: [];
}

function save_posts($posts) {
    global $posts_file;
    file_put_contents($posts_file, json_encode($posts, JSON_PRETTY_PRINT));
}

function get_chats() {
    global $chats_file;
    return json_decode(file_get_contents($chats_file), true) ?: [];
}

function save_chats($chats) {
    global $chats_file;
    file_put_contents($chats_file, json_encode($chats, JSON_PRETTY_PRINT));
}

function get_notifications() {
    global $notifications_file;
    return json_decode(file_get_contents($notifications_file), true) ?: [];
}

function save_notifications($notifications) {
    global $notifications_file;
    file_put_contents($notifications_file, json_encode($notifications, JSON_PRETTY_PRINT));
}

function get_current_user() {
    if (isset($_SESSION['user_id'])) {
        $users = get_users();
        foreach ($users as $user) {
            if ($user['id'] == $_SESSION['user_id']) {
                return $user;
            }
        }
    }
    return null;
}

function generate_id() {
    return time() . '_' . rand(1000, 9999);
}

function time_ago($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    if ($diff < 2592000) return floor($diff / 604800) . ' minggu lalu';
    if ($diff < 31536000) return floor($diff / 2592000) . ' bulan lalu';
    return floor($diff / 31536000) . ' tahun lalu';
}

function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function upload_file($file, $type = 'image') {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    
    $allowed_types = $type === 'image' ? ALLOWED_IMAGE_TYPES : ALLOWED_VIDEO_TYPES;
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'Ukuran file terlalu besar. Maksimal 10MB'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = UPLOADS_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $filename;
    }
    
    return null;
}

function get_checkmark_badge($type, $custom_url = '') {
    switch ($type) {
        case 'blue':
            return '<i class="fas fa-check-circle blue-check-badge"></i>';
        case 'gold':
            return '<i class="fas fa-check-circle gold-check-badge"></i>';
        case 'custom':
            return $custom_url ? 
                '<img src="' . $custom_url . '" alt="Verified" style="width: 16px; height: 16px; border-radius: 50%; margin-left: 4px;">' :
                '<i class="fas fa-check-circle blue-check-badge"></i>';
        default:
            return '<i class="fas fa-check-circle blue-check-badge"></i>';
    }
}

// ============================================
// HANDLER FORM & AKSI
// ============================================

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$current_user = get_current_user();

// Login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    $users = get_users();
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        }
    }
    
    $error = 'Username atau password salah';
}

// Register
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $display_name = sanitize_input($_POST['display_name']);
    $email = sanitize_input($_POST['email']);
    
    $users = get_users();
    
    // Cek jika username sudah ada
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $error = 'Username sudah digunakan';
            break;
        }
    }
    
    if (!isset($error)) {
        $new_user = [
            'id' => generate_id(),
            'username' => $username,
            'password' => $password,
            'display_name' => $display_name,
            'email' => $email,
            'bio' => 'Programmer at DevForge',
            'role' => 'Programmer',
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($display_name) . '&background=333&color=fff',
            'banner' => '',
            'banner_is_video' => false,
            'checkmark_type' => 'none',
            'custom_checkmark' => '',
            'is_verified' => false,
            'privacy' => 'public',
            'followers' => [],
            'following' => [],
            'created_at' => time(),
            'posts_count' => 0,
            'followers_count' => 0,
            'following_count' => 0
        ];
        
        $users[] = $new_user;
        save_users($users);
        
        $_SESSION['user_id'] = $new_user['id'];
        header('Location: index.php');
        exit;
    }
}

// Logout
if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Create Post
if ($action === 'create_post' && $_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {
    $content = sanitize_input($_POST['content']);
    $type = $_POST['type'] ?? 'text';
    $media_urls = [];
    
    // Handle media upload
    if (!empty($_FILES['media']['name'][0])) {
        foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
            $file = [
                'name' => $_FILES['media']['name'][$key],
                'type' => $_FILES['media']['type'][$key],
                'tmp_name' => $tmp_name,
                'error' => $_FILES['media']['error'][$key],
                'size' => $_FILES['media']['size'][$key]
            ];
            
            $upload_type = strpos($file['type'], 'video') !== false ? 'video' : 'image';
            $result = upload_file($file, $upload_type);
            
            if ($result && !isset($result['error'])) {
                $media_urls[] = [
                    'url' => $result,
                    'type' => $file['type']
                ];
            }
        }
    }
    
    $posts = get_posts();
    $new_post = [
        'id' => generate_id(),
        'user_id' => $current_user['id'],
        'username' => $current_user['username'],
        'display_name' => $current_user['display_name'],
        'avatar' => $current_user['avatar'],
        'content' => $content,
        'type' => $type,
        'media' => $media_urls,
        'poll' => null,
        'event' => null,
        'likes' => [],
        'comments' => [],
        'shares' => 0,
        'created_at' => time(),
        'privacy' => $current_user['privacy'],
        'checkmark_type' => $current_user['checkmark_type'],
        'custom_checkmark' => $current_user['custom_checkmark']
    ];
    
    // Handle poll
    if ($type === 'poll' && isset($_POST['poll_question'])) {
        $poll_options = [];
        foreach ($_POST['poll_option'] as $option) {
            if (!empty(trim($option))) {
                $poll_options[] = [
                    'text' => sanitize_input($option),
                    'votes' => 0,
                    'voters' => []
                ];
            }
        }
        
        $new_post['poll'] = [
            'question' => sanitize_input($_POST['poll_question']),
            'options' => $poll_options,
            'duration' => (int)($_POST['poll_duration'] ?? 1),
            'ends_at' => time() + ((int)($_POST['poll_duration'] ?? 1) * 86400)
        ];
    }
    
    // Handle event
    if ($type === 'event' && isset($_POST['event_title'])) {
        $new_post['event'] = [
            'title' => sanitize_input($_POST['event_title']),
            'date' => sanitize_input($_POST['event_date']),
            'location' => sanitize_input($_POST['event_location'] ?? '')
        ];
    }
    
    $posts[] = $new_post;
    save_posts($posts);
    
    // Update user's post count
    $users = get_users();
    foreach ($users as &$user) {
        if ($user['id'] == $current_user['id']) {
            $user['posts_count']++;
            break;
        }
    }
    save_users($users);
    
    header('Location: index.php');
    exit;
}

// Delete Post
if ($action === 'delete_post' && isset($_GET['post_id']) && $current_user) {
    $post_id = $_GET['post_id'];
    $posts = get_posts();
    
    foreach ($posts as $key => $post) {
        if ($post['id'] == $post_id && $post['user_id'] == $current_user['id']) {
            // Delete media files
            foreach ($post['media'] as $media) {
                if (file_exists(__DIR__ . '/' . $media['url'])) {
                    unlink(__DIR__ . '/' . $media['url']);
                }
            }
            
            unset($posts[$key]);
            $posts = array_values($posts);
            save_posts($posts);
            
            // Update user's post count
            $users = get_users();
            foreach ($users as &$user) {
                if ($user['id'] == $current_user['id']) {
                    $user['posts_count'] = max(0, $user['posts_count'] - 1);
                    break;
                }
            }
            save_users($users);
            
            break;
        }
    }
    
    header('Location: index.php');
    exit;
}

// Like Post
if ($action === 'like_post' && isset($_GET['post_id']) && $current_user) {
    $post_id = $_GET['post_id'];
    $posts = get_posts();
    
    foreach ($posts as &$post) {
        if ($post['id'] == $post_id) {
            $key = array_search($current_user['id'], $post['likes']);
            if ($key !== false) {
                // Unlike
                unset($post['likes'][$key]);
                $post['likes'] = array_values($post['likes']);
            } else {
                // Like
                $post['likes'][] = $current_user['id'];
                
                // Create notification if not own post
                if ($post['user_id'] != $current_user['id']) {
                    $notifications = get_notifications();
                    $notifications[] = [
                        'id' => generate_id(),
                        'user_id' => $post['user_id'],
                        'type' => 'like',
                        'message' => $current_user['display_name'] . ' menyukai postingan Anda',
                        'post_id' => $post_id,
                        'from_user_id' => $current_user['id'],
                        'read' => false,
                        'created_at' => time()
                    ];
                    save_notifications($notifications);
                }
            }
            break;
        }
    }
    
    save_posts($posts);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Add Comment
if ($action === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {
    $post_id = $_POST['post_id'];
    $comment = sanitize_input($_POST['comment']);
    
    $posts = get_posts();
    foreach ($posts as &$post) {
        if ($post['id'] == $post_id) {
            $post['comments'][] = [
                'id' => generate_id(),
                'user_id' => $current_user['id'],
                'display_name' => $current_user['display_name'],
                'username' => $current_user['username'],
                'avatar' => $current_user['avatar'],
                'comment' => $comment,
                'created_at' => time(),
                'likes' => []
            ];
            
            // Create notification if not own post
            if ($post['user_id'] != $current_user['id']) {
                $notifications = get_notifications();
                $notifications[] = [
                    'id' => generate_id(),
                    'user_id' => $post['user_id'],
                    'type' => 'comment',
                    'message' => $current_user['display_name'] . ' mengomentari postingan Anda',
                    'post_id' => $post_id,
                    'from_user_id' => $current_user['id'],
                    'read' => false,
                    'created_at' => time()
                ];
                save_notifications($notifications);
            }
            
            break;
        }
    }
    
    save_posts($posts);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Update Profile
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {
    $users = get_users();
    
    foreach ($users as &$user) {
        if ($user['id'] == $current_user['id']) {
            $user['display_name'] = sanitize_input($_POST['display_name']);
            $user['username'] = sanitize_input($_POST['username']);
            $user['bio'] = sanitize_input($_POST['bio']);
            $user['role'] = sanitize_input($_POST['role']);
            $user['privacy'] = $_POST['privacy'] ?? 'public';
            $user['checkmark_type'] = $_POST['checkmark_type'] ?? 'none';
            
            // Upload avatar
            if (!empty($_FILES['avatar']['name'])) {
                $result = upload_file($_FILES['avatar'], 'image');
                if ($result && !isset($result['error'])) {
                    $user['avatar'] = $result;
                }
            }
            
            // Upload banner
            if (!empty($_FILES['banner']['name'])) {
                $result = upload_file($_FILES['banner'], 
                    strpos($_FILES['banner']['type'], 'video') !== false ? 'video' : 'image');
                if ($result && !isset($result['error'])) {
                    $user['banner'] = $result;
                    $user['banner_is_video'] = strpos($_FILES['banner']['type'], 'video') !== false;
                }
            }
            
            // Upload custom checkmark
            if (!empty($_FILES['custom_checkmark']['name'])) {
                $result = upload_file($_FILES['custom_checkmark'], 'image');
                if ($result && !isset($result['error'])) {
                    $user['custom_checkmark'] = $result;
                    $user['checkmark_type'] = 'custom';
                }
            }
            
            break;
        }
    }
    
    save_users($users);
    header('Location: index.php?view=profile');
    exit;
}

// Follow/Unfollow User
if ($action === 'follow' && isset($_GET['user_id']) && $current_user) {
    $target_user_id = $_GET['user_id'];
    
    if ($target_user_id != $current_user['id']) {
        $users = get_users();
        
        foreach ($users as &$user) {
            if ($user['id'] == $current_user['id']) {
                // Check if already following
                $key = array_search($target_user_id, $user['following']);
                if ($key !== false) {
                    // Unfollow
                    unset($user['following'][$key]);
                    $user['following'] = array_values($user['following']);
                    $user['following_count'] = count($user['following']);
                } else {
                    // Follow
                    $user['following'][] = $target_user_id;
                    $user['following_count'] = count($user['following']);
                }
            }
            
            if ($user['id'] == $target_user_id) {
                // Update target user's followers
                $key = array_search($current_user['id'], $user['followers']);
                if ($key !== false) {
                    unset($user['followers'][$key]);
                    $user['followers'] = array_values($user['followers']);
                } else {
                    $user['followers'][] = $current_user['id'];
                    
                    // Create notification
                    $notifications = get_notifications();
                    $notifications[] = [
                        'id' => generate_id(),
                        'user_id' => $target_user_id,
                        'type' => 'follow',
                        'message' => $current_user['display_name'] . ' mulai mengikuti Anda',
                        'from_user_id' => $current_user['id'],
                        'read' => false,
                        'created_at' => time()
                    ];
                    save_notifications($notifications);
                }
                $user['followers_count'] = count($user['followers']);
            }
        }
        
        save_users($users);
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Delete Account
if ($action === 'delete_account' && $current_user) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirmation = $_POST['confirmation'] ?? '';
        
        if ($confirmation === 'DELETE') {
            // Delete user's posts and media
            $posts = get_posts();
            $new_posts = [];
            foreach ($posts as $post) {
                if ($post['user_id'] == $current_user['id']) {
                    // Delete media files
                    foreach ($post['media'] as $media) {
                        if (file_exists(__DIR__ . '/' . $media['url'])) {
                            unlink(__DIR__ . '/' . $media['url']);
                        }
                    }
                } else {
                    $new_posts[] = $post;
                }
            }
            save_posts($new_posts);
            
            // Remove user from other users' followers/following
            $users = get_users();
            foreach ($users as &$user) {
                if ($user['id'] != $current_user['id']) {
                    $user['followers'] = array_diff($user['followers'], [$current_user['id']]);
                    $user['following'] = array_diff($user['following'], [$current_user['id']]);
                    $user['followers_count'] = count($user['followers']);
                    $user['following_count'] = count($user['following']);
                }
            }
            
            // Remove the user
            $users = array_filter($users, function($user) use ($current_user) {
                return $user['id'] != $current_user['id'];
            });
            save_users($users);
            
            // Clear notifications for this user
            $notifications = get_notifications();
            $notifications = array_filter($notifications, function($notification) use ($current_user) {
                return $notification['user_id'] != $current_user['id'];
            });
            save_notifications($notifications);
            
            // Logout
            session_destroy();
            header('Location: index.php');
            exit;
        }
    }
}

// Mark notification as read
if ($action === 'mark_notification_read' && isset($_GET['notification_id']) && $current_user) {
    $notification_id = $_GET['notification_id'];
    $notifications = get_notifications();
    
    foreach ($notifications as &$notification) {
        if ($notification['id'] == $notification_id && $notification['user_id'] == $current_user['id']) {
            $notification['read'] = true;
            break;
        }
    }
    
    save_notifications($notifications);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Send Message
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {
    $receiver_id = $_POST['receiver_id'];
    $message = sanitize_input($_POST['message']);
    
    $chats = get_chats();
    $chat_id = min($current_user['id'], $receiver_id) . '_' . max($current_user['id'], $receiver_id);
    
    if (!isset($chats[$chat_id])) {
        $chats[$chat_id] = [
            'participants' => [$current_user['id'], $receiver_id],
            'messages' => []
        ];
    }
    
    $chats[$chat_id]['messages'][] = [
        'id' => generate_id(),
        'sender_id' => $current_user['id'],
        'message' => $message,
        'created_at' => time(),
        'read' => false
    ];
    
    save_chats($chats);
    
    // Create notification
    $notifications = get_notifications();
    $notifications[] = [
        'id' => generate_id(),
        'user_id' => $receiver_id,
        'type' => 'message',
        'message' => $current_user['display_name'] . ' mengirim pesan kepada Anda',
        'from_user_id' => $current_user['id'],
        'read' => false,
        'created_at' => time()
    ];
    save_notifications($notifications);
    
    header('Location: index.php?view=chat&user_id=' . $receiver_id);
    exit;
}

// Vote on poll
if ($action === 'vote_poll' && isset($_GET['post_id']) && isset($_GET['option_index']) && $current_user) {
    $post_id = $_GET['post_id'];
    $option_index = (int)$_GET['option_index'];
    
    $posts = get_posts();
    foreach ($posts as &$post) {
        if ($post['id'] == $post_id && isset($post['poll'])) {
            // Check if user already voted
            $already_voted = false;
            foreach ($post['poll']['options'] as $option) {
                if (in_array($current_user['id'], $option['voters'])) {
                    $already_voted = true;
                    break;
                }
            }
            
            if (!$already_voted) {
                $post['poll']['options'][$option_index]['votes']++;
                $post['poll']['options'][$option_index]['voters'][] = $current_user['id'];
            }
            break;
        }
    }
    
    save_posts($posts);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// ============================================
// TAMPILAN
// ============================================

$view = $_GET['view'] ?? 'home';
$current_user = get_current_user();

// Jika belum login, tampilkan halaman login
if (!$current_user && $view !== 'register') {
    $view = 'login';
}

// Ambil data yang diperlukan
$users = get_users();
$posts = get_posts();
$notifications = get_notifications();
$chats = get_chats();

// Filter posts berdasarkan privacy
if ($current_user) {
    $filtered_posts = [];
    foreach ($posts as $post) {
        $post_user = null;
        foreach ($users as $u) {
            if ($u['id'] == $post['user_id']) {
                $post_user = $u;
                break;
            }
        }
        
        if (!$post_user) continue;
        
        if ($post_user['privacy'] === 'public') {
            $filtered_posts[] = $post;
        } elseif ($post_user['privacy'] === 'private' && $post_user['id'] == $current_user['id']) {
            $filtered_posts[] = $post;
        } elseif ($post_user['privacy'] === 'followers') {
            if ($post_user['id'] == $current_user['id'] || in_array($current_user['id'], $post_user['followers'])) {
                $filtered_posts[] = $post;
            }
        }
    }
    $posts = $filtered_posts;
    
    // Sort posts by date
    usort($posts, function($a, $b) {
        return $b['created_at'] <=> $a['created_at'];
    });
    
    // Get user notifications
    $user_notifications = array_filter($notifications, function($notification) use ($current_user) {
        return $notification['user_id'] == $current_user['id'] && !$notification['read'];
    });
}

// ============================================
// HTML OUTPUT
// ============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Social Media for Programmers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'style.css'; ?>
    </style>
    <style>
        /* CSS dari file HTML Anda disertakan di sini */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        :root {
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --border-color: #333333;
            --hover-color: #1a1a1a;
            --background-color: #000000;
            --card-background: #111111;
            --trends-hover: #222222;
            --bottom-nav-height: 60px;
            --active-color: #ffffff;
            --error-color: #ff3333;
            --success-color: #00cc66;
            --verified-color: #a0a0a0;
            --poll-color: #555555;
            --blue-check-color: #1d9bf0;
            --gold-check-color: #ffd700;
            --reply-color: #a0a0a0;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-primary);
            min-height: 100vh;
            padding-bottom: var(--bottom-nav-height);
            overflow-x: hidden;
        }

        /* Tambahan untuk halaman login/register */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-box {
            background-color: var(--card-background);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
        }

        .auth-logo {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-primary);
        }

        .auth-form .form-group {
            margin-bottom: 20px;
        }

        .auth-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .auth-form input {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 16px;
        }

        .auth-form input:focus {
            outline: none;
            border-color: var(--text-primary);
        }

        .auth-btn {
            width: 100%;
            padding: 14px;
            background-color: var(--text-primary);
            color: var(--background-color);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .auth-btn:hover {
            background-color: #e0e0e0;
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }

        .auth-link a {
            color: var(--text-primary);
            text-decoration: none;
        }

        .auth-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: rgba(255, 51, 51, 0.1);
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background-color: rgba(0, 204, 102, 0.1);
            color: var(--success-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* CSS lainnya dari file HTML Anda tetap sama */
        /* ... (seluruh CSS dari file HTML Anda) ... */
        
        /* Hanya menambahkan bagian yang berbeda */
        .login-notice {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            z-index: 1000;
        }
        
        /* Untuk chat bubble */
        .chat-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            margin-bottom: 10px;
            position: relative;
        }
        
        .chat-bubble.sent {
            background-color: #1d9bf0;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .chat-bubble.received {
            background-color: var(--card-background);
            color: var(--text-primary);
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .chat-time {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 5px;
            text-align: right;
        }
        
        /* Untuk poll results */
        .poll-result {
            margin-bottom: 10px;
        }
        
        .poll-bar {
            height: 30px;
            background-color: var(--poll-color);
            border-radius: 15px;
            margin-top: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .poll-fill {
            height: 100%;
            background-color: #1d9bf0;
            border-radius: 15px;
            transition: width 0.5s ease;
        }
        
        .poll-percentage {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php if (!$current_user && $view !== 'login' && $view !== 'register'): ?>
        <div class="login-notice">
            Silakan <a href="index.php?view=login" style="color: white; text-decoration: underline;">login</a> atau <a href="index.php?view=register" style="color: white; text-decoration: underline;">register</a> untuk menggunakan DevForge
        </div>
    <?php endif; ?>

    <?php if ($view === 'login'): ?>
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-logo">DevForge</div>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="index.php?action=login" class="auth-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="auth-btn">Login</button>
                </form>
                <div class="auth-link">
                    Belum punya akun? <a href="index.php?view=register">Register di sini</a>
                </div>
            </div>
        </div>
    
    <?php elseif ($view === 'register'): ?>
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-logo">DevForge</div>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="index.php?action=register" class="auth-form">
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="display_name" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="auth-btn">Register</button>
                </form>
                <div class="auth-link">
                    Sudah punya akun? <a href="index.php?view=login">Login di sini</a>
                </div>
            </div>
        </div>
    
    <?php elseif ($current_user): ?>
        <!-- HEADER ATAS MOBILE -->
        <div class="top-header">
            <a href="index.php" class="logo">DevForge</a>
            <div class="header-actions">
                <button class="notification-btn" onclick="window.location.href='index.php?view=notifications'">
                    <i class="fas fa-bell"></i>
                    <?php if (count($user_notifications) > 0): ?>
                        <span class="notification-badge"><?php echo count($user_notifications); ?></span>
                    <?php endif; ?>
                </button>
                <button class="settings-btn" onclick="document.getElementById('editProfileModal').style.display='flex'">
                    <i class="fas fa-cog"></i>
                </button>
                <div class="profile-icon-top" style="background-image: url('<?php echo $current_user['avatar']; ?>')" 
                     onclick="window.location.href='index.php?view=profile'"></div>
            </div>
        </div>
        
        <?php if ($view === 'home' || $view === 'search' || $view === 'notifications' || $view === 'chat' || $view === 'profile'): ?>
            <!-- NAVIGASI UTAMA -->
            <nav class="bottom-nav">
                <button class="nav-item <?php echo $view === 'home' ? 'active' : ''; ?>" onclick="window.location.href='index.php?view=home'">
                    <div class="nav-icon"><i class="fas fa-home"></i></div>
                    <span>Home</span>
                </button>
                <button class="nav-item <?php echo $view === 'search' ? 'active' : ''; ?>" onclick="window.location.href='index.php?view=search'">
                    <div class="nav-icon"><i class="fas fa-search"></i></div>
                    <span>Search</span>
                </button>
                <button class="nav-item <?php echo $view === 'notifications' ? 'active' : ''; ?>" onclick="window.location.href='index.php?view=notifications'">
                    <div class="nav-icon"><i class="fas fa-bell"></i></div>
                    <span>Notifikasi</span>
                    <?php if (count($user_notifications) > 0): ?>
                        <span class="nav-badge"><?php echo count($user_notifications); ?></span>
                    <?php endif; ?>
                </button>
                <button class="nav-item <?php echo $view === 'chat' ? 'active' : ''; ?>" onclick="window.location.href='index.php?view=chat'">
                    <div class="nav-icon"><i class="fas fa-comment"></i></div>
                    <span>Chat</span>
                </button>
                <button class="nav-item <?php echo $view === 'profile' ? 'active' : ''; ?>" onclick="window.location.href='index.php?view=profile'">
                    <div class="nav-icon"><i class="fas fa-user"></i></div>
                    <span>Profile</span>
                </button>
            </nav>
        <?php endif; ?>
        
        <!-- KONTEN UTAMA -->
        <div class="main-content <?php echo $view === 'home' ? 'active' : ''; ?>" id="homePage" style="<?php echo $view === 'home' ? 'display:block;' : 'display:none;'; ?>">
            <?php if ($view === 'home'): ?>
                <!-- FEED TABS -->
                <div class="feed-tabs">
                    <div class="tab active">Untuk Anda</div>
                    <div class="tab">Mengikuti</div>
                </div>
                
                <!-- AREA BUAT POSTINGAN -->
                <div class="post-compose">
                    <div class="user-avatar" style="background-image: url('<?php echo $current_user['avatar']; ?>')"></div>
                    <div class="post-input-container">
                        <form method="POST" action="index.php?action=create_post" enctype="multipart/form-data" id="postForm">
                            <input type="hidden" name="type" id="postType" value="text">
                            
                            <!-- TOMBOL MEDIA, POLLING, EVENT -->
                            <div class="post-type-buttons">
                                <button type="button" class="post-type-button" data-type="media" title="Media">
                                    <i class="fas fa-image"></i>
                                </button>
                                <button type="button" class="post-type-button" data-type="poll" title="Polling">
                                    <i class="fas fa-poll"></i>
                                </button>
                                <button type="button" class="post-type-button" data-type="event" title="Event">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                            
                            <textarea name="content" class="post-input" id="postInput" placeholder="Apa yang sedang Anda kodekan hari ini?" required></textarea>
                            
                            <!-- MEDIA UPLOAD PREVIEW -->
                            <div class="media-preview" id="mediaPreview" style="display: none;"></div>
                            
                            <!-- POLL FORM -->
                            <div class="poll-form" id="pollForm" style="display: none;">
                                <input type="text" name="poll_question" class="poll-question" placeholder="Masukkan pertanyaan polling">
                                <div id="pollOptions">
                                    <div class="poll-option">
                                        <input type="text" name="poll_option[]" placeholder="Pilihan 1" required>
                                    </div>
                                    <div class="poll-option">
                                        <input type="text" name="poll_option[]" placeholder="Pilihan 2" required>
                                    </div>
                                </div>
                                <button type="button" class="poll-add-option" id="addPollOption">+ Tambah Pilihan</button>
                                <div class="poll-duration">
                                    <span>Durasi:</span>
                                    <select name="poll_duration">
                                        <option value="1">1 Hari</option>
                                        <option value="3">3 Hari</option>
                                        <option value="7">1 Minggu</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- EVENT FORM -->
                            <div class="event-form" id="eventForm" style="display: none;">
                                <input type="text" name="event_title" class="event-input" placeholder="Judul event/acara">
                                <div class="event-date">
                                    <input type="datetime-local" name="event_date" required>
                                </div>
                                <input type="text" name="event_location" class="event-input" placeholder="Lokasi (opsional)">
                            </div>
                            
                            <div class="post-actions">
                                <div class="post-icons">
                                    <div class="post-icon" title="Upload Media" onclick="document.getElementById('mediaUploadInput').click()">
                                        <i class="fas fa-image"></i>
                                        <input type="file" id="mediaUploadInput" name="media[]" accept="image/*,video/*" multiple style="display: none;" onchange="previewMedia(this.files)">
                                    </div>
                                </div>
                                <button type="submit" class="post-submit" id="postSubmit">Post</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- FEED POSTINGAN -->
                <div class="feed" id="postFeed">
                    <?php foreach ($posts as $post): 
                        $post_user = null;
                        foreach ($users as $u) {
                            if ($u['id'] == $post['user_id']) {
                                $post_user = $u;
                                break;
                            }
                        }
                        if (!$post_user) continue;
                        
                        $is_liked = in_array($current_user['id'], $post['likes']);
                        $is_owner = $post['user_id'] == $current_user['id'];
                        
                        // Calculate poll results
                        $poll_results = null;
                        if ($post['type'] === 'poll' && isset($post['poll'])) {
                            $total_votes = 0;
                            foreach ($post['poll']['options'] as $option) {
                                $total_votes += $option['votes'];
                            }
                            $poll_results = ['total' => $total_votes, 'options' => []];
                            foreach ($post['poll']['options'] as $option) {
                                $percentage = $total_votes > 0 ? round(($option['votes'] / $total_votes) * 100) : 0;
                                $poll_results['options'][] = [
                                    'text' => $option['text'],
                                    'votes' => $option['votes'],
                                    'percentage' => $percentage
                                ];
                            }
                        }
                    ?>
                    <div class="post">
                        <?php if ($is_owner): ?>
                            <a href="index.php?action=delete_post&post_id=<?php echo $post['id']; ?>" 
                               class="post-delete-btn" onclick="return confirm('Hapus postingan ini?')" title="Hapus postingan">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                        
                        <div class="post-avatar" style="background-image: url('<?php echo $post['avatar']; ?>')"
                             onclick="window.location.href='index.php?view=profile&user_id=<?php echo $post['user_id']; ?>'"></div>
                        
                        <div class="post-content">
                            <div class="post-header">
                                <div class="post-author-info">
                                    <div class="post-author" onclick="window.location.href='index.php?view=profile&user_id=<?php echo $post['user_id']; ?>'">
                                        <?php echo $post['display_name']; ?>
                                        <?php echo get_checkmark_badge($post['checkmark_type'], $post['custom_checkmark']); ?>
                                        <?php if ($post_user['role']): ?>
                                            <span class="owner-badge"><?php echo $post_user['role']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-handle" onclick="window.location.href='index.php?view=profile&user_id=<?php echo $post['user_id']; ?>'">
                                        @<?php echo $post['username']; ?>
                                    </div>
                                </div>
                                <div class="post-time"><?php echo time_ago($post['created_at']); ?></div>
                            </div>
                            
                            <div class="post-text"><?php echo nl2br($post['content']); ?></div>
                            
                            <?php if (!empty($post['media'])): ?>
                                <div class="post-media">
                                    <?php foreach ($post['media'] as $media): ?>
                                        <?php if (strpos($media['type'], 'image') !== false): ?>
                                            <img src="<?php echo $media['url']; ?>" alt="Post media" style="max-width: 100%; border-radius: 16px;">
                                        <?php elseif (strpos($media['type'], 'video') !== false): ?>
                                            <video controls style="max-width: 100%; border-radius: 16px;">
                                                <source src="<?php echo $media['url']; ?>" type="<?php echo $media['type']; ?>">
                                            </video>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['type'] === 'poll' && isset($post['poll'])): ?>
                                <div class="post-poll">
                                    <svg class="poll-svg-icon" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                    </svg>
                                    <div class="poll-question-display"><?php echo $post['poll']['question']; ?></div>
                                    
                                    <?php if ($poll_results): ?>
                                        <?php foreach ($poll_results['options'] as $index => $option): 
                                            $has_voted = false;
                                            if (isset($post['poll']['options'][$index]['voters'])) {
                                                $has_voted = in_array($current_user['id'], $post['poll']['options'][$index]['voters']);
                                            }
                                        ?>
                                            <div class="poll-result">
                                                <div><?php echo $option['text']; ?></div>
                                                <div class="poll-bar">
                                                    <div class="poll-fill" style="width: <?php echo $option['percentage']; ?>%"></div>
                                                    <div class="poll-percentage"><?php echo $option['percentage']; ?>% (<?php echo $option['votes']; ?>)</div>
                                                </div>
                                                <?php if (!$has_voted && time() < $post['poll']['ends_at']): ?>
                                                    <a href="index.php?action=vote_poll&post_id=<?php echo $post['id']; ?>&option_index=<?php echo $index; ?>"
                                                       style="font-size: 12px; color: var(--blue-check-color);">Vote</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="poll-total"><?php echo $poll_results['total']; ?> suara • 
                                            <?php echo ceil(($post['poll']['ends_at'] - time()) / 86400); ?> hari tersisa</div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($post['type'] === 'event' && isset($post['event'])): ?>
                                <div class="post-event">
                                    <svg class="event-svg-icon" viewBox="0 0 24 24">
                                        <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
                                    </svg>
                                    <div class="event-title"><?php echo $post['event']['title']; ?></div>
                                    <div class="event-date-display">
                                        <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y H:i', strtotime($post['event']['date'])); ?>
                                        <?php if (!empty($post['event']['location'])): ?>
                                            • <i class="fas fa-map-marker-alt"></i> <?php echo $post['event']['location']; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="event-countdown">
                                        <?php 
                                            $event_time = strtotime($post['event']['date']);
                                            $time_left = $event_time - time();
                                            if ($time_left > 0) {
                                                $days = floor($time_left / (60 * 60 * 24));
                                                $hours = floor(($time_left % (60 * 60 * 24)) / (60 * 60));
                                                echo "{$days} hari {$hours} jam lagi";
                                            } else {
                                                echo "Acara telah berakhir";
                                            }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- TOMBOL KOMENTAR -->
                            <button class="show-comments-btn" onclick="toggleComments('<?php echo $post['id']; ?>')">
                                <?php echo count($post['comments']); ?> komentar
                            </button>
                            
                            <!-- FORM KOMENTAR -->
                            <div class="comment-section" id="comment-section-<?php echo $post['id']; ?>" style="display: none;">
                                <form method="POST" action="index.php?action=add_comment" class="comment-form">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="text" name="comment" class="comment-input" placeholder="Tulis komentar...">
                                    <button type="submit" class="comment-submit">Kirim</button>
                                </form>
                                
                                <!-- DAFTAR KOMENTAR -->
                                <div class="comments-list">
                                    <?php foreach ($post['comments'] as $comment): ?>
                                        <div class="comment-item">
                                            <div class="comment-avatar" style="background-image: url('<?php echo $comment['avatar']; ?>')"></div>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <div class="comment-author"><?php echo $comment['display_name']; ?></div>
                                                    <div class="comment-handle">@<?php echo $comment['username']; ?></div>
                                                    <div class="comment-time"><?php echo time_ago($comment['created_at']); ?></div>
                                                </div>
                                                <div class="comment-text"><?php echo nl2br($comment['comment']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="post-actions-footer">
                                <div class="post-action comment-btn" onclick="toggleComments('<?php echo $post['id']; ?>')">
                                    <div class="action-icon">
                                        <i class="far fa-comment"></i>
                                    </div>
                                    <span><?php echo count($post['comments']); ?></span>
                                </div>
                                <div class="post-action">
                                    <div class="action-icon">
                                        <i class="fas fa-retweet"></i>
                                    </div>
                                    <span><?php echo $post['shares']; ?></span>
                                </div>
                                <a href="index.php?action=like_post&post_id=<?php echo $post['id']; ?>" class="post-action like">
                                    <div class="action-icon">
                                        <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </div>
                                    <span><?php echo count($post['likes']); ?></span>
                                </a>
                                <div class="post-action">
                                    <div class="action-icon">
                                        <i class="far fa-share-square"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- HALAMAN SEARCH -->
        <div class="main-content <?php echo $view === 'search' ? 'active' : ''; ?>" id="searchPage" style="<?php echo $view === 'search' ? 'display:block;' : 'display:none;'; ?>">
            <?php if ($view === 'search'): ?>
                <div class="search-container">
                    <div class="search-box">
                        <div class="search-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" class="search-input" id="mainSearchInput" placeholder="Cari programmer...">
                    </div>
                </div>
                
                <div class="search-results" id="searchResults">
                    <div class="search-message">
                        <i class="fas fa-search fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>
                        <p>Cari programmer berdasarkan nama atau keahlian</p>
                    </div>
                    
                    <!-- DAFTAR PROGRAMMER -->
                    <div style="padding: 20px;">
                        <h3 style="margin-bottom: 16px;">Programmer Terpopuler</h3>
                        <?php foreach ($users as $user): 
                            if ($user['id'] == $current_user['id']) continue;
                        ?>
                            <div class="chat-item" onclick="window.location.href='index.php?view=profile&user_id=<?php echo $user['id']; ?>'">
                                <div class="chat-avatar" style="background-image: url('<?php echo $user['avatar']; ?>')"></div>
                                <div class="chat-info">
                                    <div class="chat-contact">
                                        <?php echo $user['display_name']; ?>
                                        <?php echo get_checkmark_badge($user['checkmark_type'], $user['custom_checkmark']); ?>
                                    </div>
                                    <div class="chat-preview">@<?php echo $user['username']; ?> • <?php echo $user['role']; ?></div>
                                    <div class="chat-time"><?php echo $user['followers_count']; ?> pengikut</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- HALAMAN NOTIFIKASI -->
        <div class="main-content <?php echo $view === 'notifications' ? 'active' : ''; ?>" id="notificationsPage" style="<?php echo $view === 'notifications' ? 'display:block;' : 'display:none;'; ?>">
            <?php if ($view === 'notifications'): ?>
                <div class="top-header" style="position: static;">
                    <div style="font-size: 20px; font-weight: 700;">Notifikasi</div>
                    <a href="index.php?action=mark_all_read" class="settings-btn">
                        <i class="fas fa-check-double"></i>
                    </a>
                </div>
                
                <div class="notifications-container">
                    <?php 
                    $user_notifications_all = array_filter($notifications, function($n) use ($current_user) {
                        return $n['user_id'] == $current_user['id'];
                    });
                    
                    usort($user_notifications_all, function($a, $b) {
                        return $b['created_at'] <=> $a['created_at'];
                    });
                    
                    if (empty($user_notifications_all)): ?>
                        <div class="search-message">
                            <i class="fas fa-bell-slash fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>
                            <p>Tidak ada notifikasi</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_notifications_all as $notification): 
                            $from_user = null;
                            foreach ($users as $u) {
                                if ($u['id'] == $notification['from_user_id']) {
                                    $from_user = $u;
                                    break;
                                }
                            }
                            if (!$from_user) continue;
                        ?>
                            <div class="notification-item <?php echo !$notification['read'] ? 'notification-unread' : ''; ?>">
                                <div class="notification-icon">
                                    <?php switch($notification['type']): 
                                        case 'like': ?>
                                            <i class="fas fa-heart" style="color: #ff3333;"></i>
                                            <?php break; ?>
                                        <?php case 'comment': ?>
                                            <i class="fas fa-comment" style="color: #1d9bf0;"></i>
                                            <?php break; ?>
                                        <?php case 'follow': ?>
                                            <i class="fas fa-user-plus" style="color: #00cc66;"></i>
                                            <?php break; ?>
                                        <?php case 'message': ?>
                                            <i class="fas fa-envelope" style="color: #ffd700;"></i>
                                            <?php break; ?>
                                        <?php default: ?>
                                            <i class="fas fa-bell"></i>
                                    <?php endswitch; ?>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-text">
                                        <strong><?php echo $from_user['display_name']; ?></strong> <?php echo $notification['message']; ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo time_ago($notification['created_at']); ?>
                                        <?php if (!$notification['read']): ?>
                                            • <a href="index.php?action=mark_notification_read&notification_id=<?php echo $notification['id']; ?>" 
                                               style="color: var(--blue-check-color); font-size: 12px;">Tandai sudah dibaca</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- HALAMAN CHAT -->
        <div class="main-content <?php echo $view === 'chat' ? 'active' : ''; ?>" id="chatPage" style="<?php echo $view === 'chat' ? 'display:block;' : 'display:none;'; ?>">
            <?php if ($view === 'chat'): ?>
                <div class="search-container">
                    <div class="search-box">
                        <div class="search-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" class="search-input" id="chatSearchInput" placeholder="Cari programmer untuk chat...">
                    </div>
                </div>
                
                <div class="chat-container">
                    <?php 
                    $chat_user_id = $_GET['user_id'] ?? null;
                    if ($chat_user_id): 
                        $chat_user = null;
                        foreach ($users as $u) {
                            if ($u['id'] == $chat_user_id) {
                                $chat_user = $u;
                                break;
                            }
                        }
                        
                        if ($chat_user):
                            $chat_id = min($current_user['id'], $chat_user_id) . '_' . max($current_user['id'], $chat_user_id);
                            $chat_messages = isset($chats[$chat_id]) ? $chats[$chat_id]['messages'] : [];
                    ?>
                        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center;">
                            <a href="index.php?view=chat" style="margin-right: 12px; color: var(--text-primary);">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div class="chat-avatar" style="background-image: url('<?php echo $chat_user['avatar']; ?>'); width: 40px; height: 40px;"></div>
                            <div style="margin-left: 12px;">
                                <div style="font-weight: 700;"><?php echo $chat_user['display_name']; ?></div>
                                <div style="font-size: 12px; color: var(--text-secondary);">@<?php echo $chat_user['username']; ?></div>
                            </div>
                        </div>
                        
                        <!-- AREA CHAT -->
                        <div style="padding: 16px; height: 60vh; overflow-y: auto;" id="chatMessages">
                            <?php foreach ($chat_messages as $message): 
                                $is_sent = $message['sender_id'] == $current_user['id'];
                            ?>
                                <div class="chat-bubble <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                    <div><?php echo nl2br($message['message']); ?></div>
                                    <div class="chat-time"><?php echo date('H:i', $message['created_at']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- FORM KIRIM PESAN -->
                        <form method="POST" action="index.php?action=send_message" style="padding: 16px; border-top: 1px solid var(--border-color);">
                            <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                            <div style="display: flex;">
                                <input type="text" name="message" placeholder="Ketik pesan..." 
                                       style="flex: 1; padding: 12px; background-color: var(--card-background); border: 1px solid var(--border-color); 
                                              border-radius: 20px; color: var(--text-primary);">
                                <button type="submit" style="margin-left: 12px; padding: 12px 20px; background-color: var(--text-primary); 
                                        color: var(--background-color); border: none; border-radius: 20px; cursor: pointer;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                        
                        <script>
                            // Scroll ke bawah chat
                            window.onload = function() {
                                var chatDiv = document.getElementById('chatMessages');
                                chatDiv.scrollTop = chatDiv.scrollHeight;
                            };
                        </script>
                        
                    <?php else: ?>
                        <div class="search-message">
                            <i class="fas fa-exclamation-circle fa-2x" style="margin-bottom: 16px; color: var(--error-color);"></i>
                            <p>Pengguna tidak ditemukan</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <!-- DAFTAR CHAT -->
                        <div id="chatList">
                            <div class="search-message">
                                <i class="fas fa-comments fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>
                                <p>Pilih programmer untuk memulai percakapan</p>
                            </div>
                            
                            <?php 
                            // Get unique chat participants
                            $chat_partners = [];
                            foreach ($chats as $chat_id => $chat) {
                                if (in_array($current_user['id'], $chat['participants'])) {
                                    $partner_id = $chat['participants'][0] == $current_user['id'] ? $chat['participants'][1] : $chat['participants'][0];
                                    $chat_partners[$partner_id] = $chat_id;
                                }
                            }
                            
                            foreach ($chat_partners as $partner_id => $chat_id):
                                $partner = null;
                                foreach ($users as $u) {
                                    if ($u['id'] == $partner_id) {
                                        $partner = $u;
                                        break;
                                    }
                                }
                                if (!$partner) continue;
                                
                                $last_message = end($chats[$chat_id]['messages']);
                            ?>
                                <div class="chat-item" onclick="window.location.href='index.php?view=chat&user_id=<?php echo $partner_id; ?>'">
                                    <div class="chat-avatar" style="background-image: url('<?php echo $partner['avatar']; ?>')"></div>
                                    <div class="chat-info">
                                        <div class="chat-contact">
                                            <?php echo $partner['display_name']; ?>
                                            <?php echo get_checkmark_badge($partner['checkmark_type'], $partner['custom_checkmark']); ?>
                                        </div>
                                        <div class="chat-preview">
                                            <?php echo $last_message ? substr($last_message['message'], 0, 50) . '...' : 'Belum ada pesan'; ?>
                                        </div>
                                        <div class="chat-time">
                                            <?php echo $last_message ? time_ago($last_message['created_at']) : ''; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- DAFTAR PROGRAMMER LAIN -->
                            <div style="padding: 20px; border-top: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 16px;">Programmer Lainnya</h4>
                                <?php foreach ($users as $user): 
                                    if ($user['id'] == $current_user['id'] || isset($chat_partners[$user['id']])) continue;
                                ?>
                                    <div class="chat-item" onclick="window.location.href='index.php?view=chat&user_id=<?php echo $user['id']; ?>'">
                                        <div class="chat-avatar" style="background-image: url('<?php echo $user['avatar']; ?>')"></div>
                                        <div class="chat-info">
                                            <div class="chat-contact">
                                                <?php echo $user['display_name']; ?>
                                                <?php echo get_checkmark_badge($user['checkmark_type'], $user['custom_checkmark']); ?>
                                            </div>
                                            <div class="chat-preview">@<?php echo $user['username']; ?></div>
                                            <div class="chat-time">Mulai chat</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- HALAMAN PROFILE -->
        <div class="main-content <?php echo $view === 'profile' ? 'active' : ''; ?>" id="profilePage" style="<?php echo $view === 'profile' ? 'display:block;' : 'display:none;'; ?>">
            <?php if ($view === 'profile'): 
                $profile_user_id = $_GET['user_id'] ?? $current_user['id'];
                $profile_user = null;
                foreach ($users as $u) {
                    if ($u['id'] == $profile_user_id) {
                        $profile_user = $u;
                        break;
                    }
                }
                
                if (!$profile_user) {
                    echo '<div class="search-message"><p>Profil tidak ditemukan</p></div>';
                } else {
                    $is_own_profile = $profile_user['id'] == $current_user['id'];
                    $is_following = in_array($profile_user['id'], $current_user['following']);
                    
                    // Get user's posts
                    $user_posts = array_filter($posts, function($post) use ($profile_user_id) {
                        return $post['user_id'] == $profile_user_id;
                    });
            ?>
                <!-- BANNER PROFILE -->
                <div class="profile-banner" id="profileBanner" 
                     style="<?php echo $profile_user['banner'] ? 'background-image: url(\'' . $profile_user['banner'] . '\')' : ''; ?>">
                    <?php if ($profile_user['banner_is_video'] && $profile_user['banner']): ?>
                        <video autoplay muted loop style="width: 100%; height: 100%; object-fit: cover;">
                            <source src="<?php echo $profile_user['banner']; ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                </div>
                
                <!-- HEADER PROFILE -->
                <div class="profile-header">
                    <div class="profile-avatar-large" id="profileAvatarLarge" 
                         style="background-image: url('<?php echo $profile_user['avatar']; ?>')">
                        <?php if ($is_own_profile): ?>
                            <button class="avatar-edit" onclick="document.getElementById('editProfileModal').style.display='flex'">
                                <i class="fas fa-camera"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-name" id="profileNameDisplay">
                        <?php echo $profile_user['display_name']; ?>
                        <?php echo get_checkmark_badge($profile_user['checkmark_type'], $profile_user['custom_checkmark']); ?>
                        <?php if ($profile_user['role']): ?>
                            <span class="owner-badge"><?php echo $profile_user['role']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-handle" id="profileHandleDisplay">@<?php echo $profile_user['username']; ?></div>
                    <div class="profile-bio" id="profileBioDisplay"><?php echo nl2br($profile_user['bio']); ?></div>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="stat-count"><?php echo count($user_posts); ?></span>
                            <span class="stat-label">Postingan</span>
                        </div>
                        <div class="profile-stat">
                            <span class="stat-count"><?php echo $profile_user['followers_count']; ?></span>
                            <span class="stat-label">Pengikut</span>
                        </div>
                        <div class="profile-stat">
                            <span class="stat-count"><?php echo $profile_user['following_count']; ?></span>
                            <span class="stat-label">Mengikuti</span>
                        </div>
                    </div>
                </div>
                
                <!-- TOMBOL AKSI -->
                <div class="profile-actions">
                    <?php if ($is_own_profile): ?>
                        <button class="profile-btn" onclick="document.getElementById('editProfileModal').style.display='flex'">Edit Profil</button>
                        <a href="index.php?action=logout" class="profile-btn logout">Logout</a>
                    <?php else: ?>
                        <a href="index.php?action=follow&user_id=<?php echo $profile_user['id']; ?>" 
                           class="profile-btn <?php echo $is_following ? 'logout' : ''; ?>">
                            <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>
                        </a>
                        <a href="index.php?view=chat&user_id=<?php echo $profile_user['id']; ?>" class="profile-btn">Message</a>
                    <?php endif; ?>
                </div>
                
                <!-- POSTINGAN USER -->
                <div style="padding: 16px;">
                    <h3 style="margin-bottom: 16px;">Postingan</h3>
                    <?php if (empty($user_posts)): ?>
                        <div class="search-message" style="padding: 40px 20px;">
                            <p>Belum ada postingan</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_posts as $post): ?>
                            <div class="post" style="margin-bottom: 16px;">
                                <div class="post-content">
                                    <div class="post-text"><?php echo nl2br($post['content']); ?></div>
                                    <?php if (!empty($post['media'])): ?>
                                        <div class="post-media">
                                            <img src="<?php echo $post['media'][0]['url']; ?>" alt="Post media" style="max-width: 100%; border-radius: 8px;">
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-time" style="margin-top: 8px; font-size: 12px;">
                                        <?php echo time_ago($post['created_at']); ?>
                                        • <a href="index.php?view=home#post-<?php echo $post['id']; ?>" style="color: var(--blue-check-color);">Lihat lengkap</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- PENGATURAN (Hanya untuk own profile) -->
                <?php if ($is_own_profile): ?>
                    <div class="profile-settings">
                        <div class="setting-item" onclick="document.getElementById('editProfileModal').style.display='flex'">
                            <div class="setting-info">
                                <div class="setting-title">Akun</div>
                                <div class="setting-desc">Pengaturan akun dan keamanan</div>
                            </div>
                            <div class="setting-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                        <div class="setting-item" onclick="document.getElementById('privacyModal').style.display='flex'">
                            <div class="setting-info">
                                <div class="setting-title">Privasi</div>
                                <div class="setting-desc">Kontrol siapa yang bisa melihat konten Anda</div>
                            </div>
                            <div class="setting-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                        <div class="setting-item" onclick="window.location.href='index.php?view=notifications'">
                            <div class="setting-info">
                                <div class="setting-title">Notifikasi</div>
                                <div class="setting-desc">Kelola notifikasi yang Anda terima</div>
                            </div>
                            <div class="setting-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                        <div class="setting-item" onclick="document.getElementById('deleteAccountModal').style.display='flex'" style="border-bottom: none;">
                            <div class="setting-info">
                                <div class="setting-title" style="color: var(--error-color);">Zona Berbahaya</div>
                                <div class="setting-desc">Hapus akun dan data lainnya</div>
                            </div>
                            <div class="setting-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php } ?>
        </div>
        
        <!-- MODAL EDIT PROFIL -->
        <div class="modal" id="editProfileModal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">Edit Profil</div>
                    <button class="modal-close" onclick="document.getElementById('editProfileModal').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php?action=update_profile" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Display Name</label>
                            <input type="text" class="form-input" name="display_name" value="<?php echo $current_user['display_name']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input" name="username" value="<?php echo $current_user['username']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Bio</label>
                            <textarea class="form-textarea" name="bio"><?php echo $current_user['bio']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-input" name="role" value="<?php echo $current_user['role']; ?>">
                        </div>
                        
                        <!-- CHECKMARK OPTIONS -->
                        <div class="form-group">
                            <label class="form-label">Centang Verifikasi</label>
                            <div class="checkmark-options">
                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] == 'blue' ? 'selected' : ''; ?>" 
                                     data-type="blue" onclick="selectCheckmark('blue')">
                                    <i class="fas fa-check-circle"></i>
                                    <div>Centang Biru</div>
                                </div>
                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] == 'gold' ? 'selected' : ''; ?>" 
                                     data-type="gold" onclick="selectCheckmark('gold')">
                                    <i class="fas fa-check-circle"></i>
                                    <div>Centang Emas</div>
                                </div>
                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] == 'custom' ? 'selected' : ''; ?>" 
                                     data-type="custom" onclick="document.getElementById('customCheckmarkUpload').click()">
                                    <i class="fas fa-upload"></i>
                                    <div>Upload Sticker</div>
                                </div>
                            </div>
                            <input type="hidden" name="checkmark_type" id="checkmarkType" value="<?php echo $current_user['checkmark_type']; ?>">
                            <input type="file" name="custom_checkmark" id="customCheckmarkUpload" accept="image/*" style="display: none;" 
                                   onchange="document.getElementById('checkmarkType').value='custom'">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Foto Profil</label>
                            <div class="file-upload" onclick="document.getElementById('avatarUploadInput').click()">
                                <input type="file" name="avatar" id="avatarUploadInput" accept="image/*" style="display: none;">
                                <div class="file-upload-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Klik untuk upload foto profil</div>
                                    <div style="font-size: 12px;">PNG, JPG max 5MB</div>
                                </div>
                            </div>
                            <div style="margin-top: 10px;">
                                <img src="<?php echo $current_user['avatar']; ?>" alt="Current avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Banner Profil</label>
                            <div class="file-upload" onclick="document.getElementById('bannerUploadInput').click()">
                                <input type="file" name="banner" id="bannerUploadInput" accept="image/*,video/*" style="display: none;">
                                <div class="file-upload-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>Klik untuk upload banner</div>
                                    <div style="font-size: 12px;">PNG, JPG, MP4 max 10MB</div>
                                </div>
                            </div>
                            <?php if ($current_user['banner']): ?>
                                <div style="margin-top: 10px;">
                                    <?php if ($current_user['banner_is_video']): ?>
                                        <video style="width: 100%; max-height: 100px; border-radius: 8px; object-fit: cover;" controls>
                                            <source src="<?php echo $current_user['banner']; ?>" type="video/mp4">
                                        </video>
                                    <?php else: ?>
                                        <img src="<?php echo $current_user['banner']; ?>" alt="Current banner" 
                                             style="width: 100%; max-height: 100px; border-radius: 8px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Privacy Settings</label>
                            <select name="privacy" class="form-input">
                                <option value="public" <?php echo $current_user['privacy'] == 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo $current_user['privacy'] == 'private' ? 'selected' : ''; ?>>Private</option>
                                <option value="followers" <?php echo $current_user['privacy'] == 'followers' ? 'selected' : ''; ?>>Followers Only</option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="modal-btn secondary" onclick="document.getElementById('editProfileModal').style.display='none'">Batal</button>
                            <button type="submit" class="modal-btn primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- MODAL PRIVACY -->
        <div class="modal" id="privacyModal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">Pengaturan Privasi</div>
                    <button class="modal-close" onclick="document.getElementById('privacyModal').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php?action=update_profile">
                        <input type="hidden" name="display_name" value="<?php echo $current_user['display_name']; ?>">
                        <input type="hidden" name="username" value="<?php echo $current_user['username']; ?>">
                        <input type="hidden" name="bio" value="<?php echo $current_user['bio']; ?>">
                        <input type="hidden" name="role" value="<?php echo $current_user['role']; ?>">
                        <input type="hidden" name="checkmark_type" value="<?php echo $current_user['checkmark_type']; ?>">
                        
                        <div class="privacy-options">
                            <div class="privacy-option <?php echo $current_user['privacy'] == 'public' ? 'selected' : ''; ?>" 
                                 onclick="selectPrivacy('public')" data-privacy="public">
                                <div class="privacy-radio"></div>
                                <div class="privacy-info">
                                    <div class="privacy-title">Public</div>
                                    <div class="privacy-desc">Semua orang dapat melihat postingan Anda</div>
                                </div>
                            </div>
                            <div class="privacy-option <?php echo $current_user['privacy'] == 'private' ? 'selected' : ''; ?>" 
                                 onclick="selectPrivacy('private')" data-privacy="private">
                                <div class="privacy-radio"></div>
                                <div class="privacy-info">
                                    <div class="privacy-title">Private</div>
                                    <div class="privacy-desc">Hanya Anda yang dapat melihat postingan</div>
                                </div>
                            </div>
                            <div class="privacy-option <?php echo $current_user['privacy'] == 'followers' ? 'selected' : ''; ?>" 
                                 onclick="selectPrivacy('followers')" data-privacy="followers">
                                <div class="privacy-radio"></div>
                                <div class="privacy-info">
                                    <div class="privacy-title">Followers Only</div>
                                    <div class="privacy-desc">Hanya pengikut yang dapat melihat postingan</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="privacy" id="privacySetting" value="<?php echo $current_user['privacy']; ?>">
                        
                        <div class="modal-actions">
                            <button type="button" class="modal-btn secondary" onclick="document.getElementById('privacyModal').style.display='none'">Batal</button>
                            <button type="submit" class="modal-btn primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- MODAL DELETE ACCOUNT -->
        <div class="modal" id="deleteAccountModal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title" style="color: var(--error-color);">Hapus Akun</div>
                    <button class="modal-close" onclick="document.getElementById('deleteAccountModal').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="color: var(--error-color); margin-bottom: 16px;"></i>
                        <h3 style="margin-bottom: 12px;">Hapus Akun Anda?</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 16px;">Tindakan ini tidak dapat dibatalkan. Semua data Anda akan dihapus secara permanen.</p>
                        
                        <div style="background-color: var(--card-background); padding: 16px; border-radius: 8px; text-align: left; margin-bottom: 20px;">
                            <p style="margin-bottom: 8px; font-weight: bold;">Yang akan dihapus:</p>
                            <ul style="color: var(--text-secondary); padding-left: 20px;">
                                <li>Semua postingan Anda</li>
                                <li>Semua percakapan</li>
                                <li>Semua pengikut dan yang Anda ikuti</li>
                                <li>Semua preferensi dan pengaturan</li>
                            </ul>
                        </div>
                        
                        <form method="POST" action="index.php?action=delete_account">
                            <div class="form-group">
                                <label class="form-label">Ketik "DELETE" untuk konfirmasi</label>
                                <input type="text" class="form-input" id="deleteConfirmation" name="confirmation" placeholder="DELETE" 
                                       oninput="document.getElementById('confirmDeleteBtn').disabled = this.value !== 'DELETE'">
                            </div>
                            
                            <div class="modal-actions">
                                <button type="button" class="modal-btn secondary" onclick="document.getElementById('deleteAccountModal').style.display='none'">Batal</button>
                                <button type="submit" class="modal-btn danger" id="confirmDeleteBtn" disabled>Hapus Akun Permanen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SIDEBAR DESKTOP -->
        <div class="sidebar-left" id="desktopSidebar">
            <div>
                <div class="logo-side" onclick="window.location.href='index.php'">DevForge</div>
                
                <ul class="nav-menu">
                    <li>
                        <a class="nav-link <?php echo $view === 'home' ? 'active' : ''; ?>" href="index.php?view=home">
                            <div class="nav-icon-side"><i class="fas fa-home"></i></div>
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?php echo $view === 'search' ? 'active' : ''; ?>" href="index.php?view=search">
                            <div class="nav-icon-side"><i class="fas fa-search"></i></div>
                            <span>Search</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?php echo $view === 'notifications' ? 'active' : ''; ?>" href="index.php?view=notifications">
                            <div class="nav-icon-side"><i class="fas fa-bell"></i></div>
                            <span>Notifikasi</span>
                            <?php if (count($user_notifications) > 0): ?>
                                <span class="nav-badge-side"><?php echo count($user_notifications); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?php echo $view === 'chat' ? 'active' : ''; ?>" href="index.php?view=chat">
                            <div class="nav-icon-side"><i class="fas fa-comment"></i></div>
                            <span>Chat</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?php echo $view === 'profile' ? 'active' : ''; ?>" href="index.php?view=profile">
                            <div class="nav-icon-side"><i class="fas fa-user"></i></div>
                            <span>Profile</span>
                        </a>
                    </li>
                </ul>
                
                <button class="post-button-side" onclick="window.location.href='index.php?view=home'">Post</button>
            </div>
            
            <div class="user-profile-side" onclick="window.location.href='index.php?view=profile'">
                <div class="user-avatar-side" style="background-image: url('<?php echo $current_user['avatar']; ?>')"></div>
                <div class="user-info-side">
                    <div class="user-name-side">
                        <?php echo $current_user['display_name']; ?>
                        <?php echo get_checkmark_badge($current_user['checkmark_type'], $current_user['custom_checkmark']); ?>
                    </div>
                    <div class="user-handle-side">@<?php echo $current_user['username']; ?></div>
                </div>
                <i class="fas fa-ellipsis-h"></i>
            </div>
        </div>
        
        <!-- MODAL CREATE POST -->
        <div class="modal" id="createModal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">Buat Postingan Baru</div>
                    <button class="modal-close" onclick="document.getElementById('createModal').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="post-compose" style="border: none; padding: 0;">
                        <div class="user-avatar" style="background-image: url('<?php echo $current_user['avatar']; ?>')"></div>
                        <div class="post-input-container">
                            <form method="POST" action="index.php?action=create_post" enctype="multipart/form-data">
                                <input type="hidden" name="type" value="text">
                                <textarea name="content" class="post-input" placeholder="Apa yang ingin Anda bagikan?" style="min-height: 150px;" required></textarea>
                                
                                <div class="post-actions">
                                    <div class="post-icons">
                                        <div class="post-icon" title="Media" onclick="document.getElementById('modalMediaInput').click()">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    </div>
                                    <button type="submit" class="post-submit">Post</button>
                                </div>
                                <input type="file" id="modalMediaInput" name="media[]" accept="image/*,video/*" multiple style="display: none;">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // JavaScript untuk interaksi
            function toggleComments(postId) {
                var commentSection = document.getElementById('comment-section-' + postId);
                if (commentSection.style.display === 'none' || commentSection.style.display === '') {
                    commentSection.style.display = 'block';
                } else {
                    commentSection.style.display = 'none';
                }
            }
            
            function selectCheckmark(type) {
                document.getElementById('checkmarkType').value = type;
                document.querySelectorAll('.checkmark-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                document.querySelector('.checkmark-option[data-type="' + type + '"]').classList.add('selected');
            }
            
            function selectPrivacy(privacy) {
                document.getElementById('privacySetting').value = privacy;
                document.querySelectorAll('.privacy-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                document.querySelector('.privacy-option[data-privacy="' + privacy + '"]').classList.add('selected');
            }
            
            function previewMedia(files) {
                var preview = document.getElementById('mediaPreview');
                preview.innerHTML = '';
                preview.style.display = 'block';
                
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        var mediaElement = document.createElement('div');
                        mediaElement.className = 'media-preview-item';
                        
                        if (file.type.startsWith('image')) {
                            mediaElement.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                        } else if (file.type.startsWith('video')) {
                            mediaElement.innerHTML = '<video controls><source src="' + e.target.result + '" type="' + file.type + '"></video>';
                        }
                        
                        preview.appendChild(mediaElement);
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
            
            // Handle post type buttons
            document.querySelectorAll('.post-type-button').forEach(button => {
                button.addEventListener('click', function() {
                    var type = this.getAttribute('data-type');
                    document.getElementById('postType').value = type;
                    
                    // Hide all forms
                    document.getElementById('mediaPreview').style.display = 'none';
                    document.getElementById('pollForm').style.display = 'none';
                    document.getElementById('eventForm').style.display = 'none';
                    
                    // Show selected form
                    if (type === 'media') {
                        document.getElementById('mediaPreview').style.display = 'block';
                    } else if (type === 'poll') {
                        document.getElementById('pollForm').style.display = 'block';
                    } else if (type === 'event') {
                        document.getElementById('eventForm').style.display = 'block';
                    }
                    
                    // Update active button
                    document.querySelectorAll('.post-type-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            // Add poll option
            document.getElementById('addPollOption')?.addEventListener('click', function() {
                var optionsDiv = document.getElementById('pollOptions');
                var optionCount = optionsDiv.querySelectorAll('.poll-option').length;
                
                if (optionCount < 4) {
                    var newOption = document.createElement('div');
                    newOption.className = 'poll-option';
                    newOption.innerHTML = '<input type="text" name="poll_option[]" placeholder="Pilihan ' + (optionCount + 1) + '" required>';
                    optionsDiv.appendChild(newOption);
                } else {
                    alert('Maksimal 4 pilihan untuk polling');
                }
            });
            
            // Close modals on outside click
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };
            
            // Responsive sidebar
            window.onresize = function() {
                var sidebar = document.getElementById('desktopSidebar');
                if (window.innerWidth >= 768) {
                    sidebar.style.display = 'block';
                } else {
                    sidebar.style.display = 'none';
                }
            };
            
            // Initialize
            window.onload = function() {
                if (window.innerWidth >= 768) {
                    document.getElementById('desktopSidebar').style.display = 'block';
                }
                
                // Set minimum date for event form
                var now = new Date();
                var minDate = now.toISOString().slice(0, 16);
                var eventDateInput = document.querySelector('input[name="event_date"]');
                if (eventDateInput) {
                    eventDateInput.min = minDate;
                }
            };
        </script>
    <?php endif; ?>
</body>
</html>

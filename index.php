<?php

session_start();

// ============================================

// KONFIGURASI

// ============================================

define('APP_NAME', 'DevForge');

define('DATA_DIR', __DIR__ . '/data/');

define('UPLOADS_DIR', __DIR__ . '/uploads/');

// Buat direktori jika belum ada

if (!file_exists(DATA_DIR)) mkdir(DATA_DIR, 0777, true);

if (!file_exists(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0777, true);

// File data

$users_file = DATA_DIR . 'users.json';

$posts_file = DATA_DIR . 'posts.json';

$notifications_file = DATA_DIR . 'notifications.json';

// Inisialisasi file data jika belum ada

if (!file_exists($users_file)) {

    file_put_contents($users_file, json_encode([

        [

            'id' => 1,

            'username' => 'admin',

            'password' => password_hash('admin123', PASSWORD_DEFAULT),

            'display_name' => 'Admin DevForge',

            'email' => 'admin@devforge.com',

            'bio' => 'Founder and Developer of DevForge Platform',

            'role' => 'Founder & Developer',

            'avatar' => 'https://ui-avatars.com/api/?name=Admin&background=333&color=fff',

            'banner' => '',

            'checkmark_type' => 'blue',

            'privacy' => 'public',

            'followers' => [],

            'following' => [],

            'created_at' => time(),

            'posts_count' => 0,

            'followers_count' => 0,

            'following_count' => 0

        ]

    ], JSON_PRETTY_PRINT));

}

if (!file_exists($posts_file)) {

    file_put_contents($posts_file, json_encode([], JSON_PRETTY_PRINT));

}

if (!file_exists($notifications_file)) {

    file_put_contents($notifications_file, json_encode([], JSON_PRETTY_PRINT));

}

// ============================================

// FUNGSI UTILITAS

// ============================================

function get_users() {

    global $users_file;

    $data = file_get_contents($users_file);

    return json_decode($data, true) ?: [];

}

function save_users($users) {

    global $users_file;

    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

}

function get_posts() {

    global $posts_file;

    $data = file_get_contents($posts_file);

    return json_decode($data, true) ?: [];

}

function save_posts($posts) {

    global $posts_file;

    file_put_contents($posts_file, json_encode($posts, JSON_PRETTY_PRINT));

}

function get_notifications() {

    global $notifications_file;

    $data = file_get_contents($notifications_file);

    return json_decode($data, true) ?: [];

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

    return floor($diff / 604800) . ' minggu lalu';

}

function sanitize($input) {

    return htmlspecialchars(strip_tags(trim($input)));

}

// ============================================

// HANDLER REQUEST

// ============================================

$current_user = get_current_user();

$action = $_GET['action'] ?? '';

$view = $_GET['view'] ?? 'home';

// Jika belum login dan bukan halaman login/register

if (!$current_user && !in_array($view, ['login', 'register'])) {

    $view = 'login';

}

// Handle actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'login') {

        $username = sanitize($_POST['username']);

        $password = $_POST['password'];

        

        $users = get_users();

        foreach ($users as $user) {

            if ($user['username'] === $username && password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];

                header('Location: index.php');

                exit;

            }

        }

        $login_error = 'Username atau password salah';

        $view = 'login';

    }

    

    elseif ($action === 'register') {

        $username = sanitize($_POST['username']);

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $display_name = sanitize($_POST['display_name']);

        $email = sanitize($_POST['email']);

        

        $users = get_users();

        

        // Cek username sudah ada

        foreach ($users as $user) {

            if ($user['username'] === $username) {

                $register_error = 'Username sudah digunakan';

                $view = 'register';

                break;

            }

        }

        

        if (!isset($register_error)) {

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

                'checkmark_type' => 'none',

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

    

    elseif ($action === 'create_post' && $current_user) {

        $content = sanitize($_POST['content']);

        

        $posts = get_posts();

        $new_post = [

            'id' => generate_id(),

            'user_id' => $current_user['id'],

            'username' => $current_user['username'],

            'display_name' => $current_user['display_name'],

            'avatar' => $current_user['avatar'],

            'content' => $content,

            'likes' => [],

            'comments' => [],

            'shares' => 0,

            'created_at' => time()

        ];

        

        $posts[] = $new_post;

        save_posts($posts);

        

        // Update post count

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

    

    elseif ($action === 'add_comment' && $current_user) {

        $post_id = $_POST['post_id'];

        $comment = sanitize($_POST['comment']);

        

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

                    'created_at' => time()

                ];

                break;

            }

        }

        save_posts($posts);

        header('Location: index.php');

        exit;

    }

    

    elseif ($action === 'update_profile' && $current_user) {

        $users = get_users();

        

        foreach ($users as &$user) {

            if ($user['id'] == $current_user['id']) {

                $user['display_name'] = sanitize($_POST['display_name']);

                $user['username'] = sanitize($_POST['username']);

                $user['bio'] = sanitize($_POST['bio']);

                $user['role'] = sanitize($_POST['role']);

                $user['checkmark_type'] = $_POST['checkmark_type'] ?? 'none';

                $user['privacy'] = $_POST['privacy'] ?? 'public';

                break;

            }

        }

        

        save_users($users);

        header('Location: index.php?view=profile');

        exit;

    }

}

// Handle GET actions

if ($action === 'logout') {

    session_destroy();

    header('Location: index.php');

    exit;

}

elseif ($action === 'delete_post' && isset($_GET['post_id']) && $current_user) {

    $post_id = $_GET['post_id'];

    $posts = get_posts();

    

    foreach ($posts as $key => $post) {

        if ($post['id'] == $post_id && $post['user_id'] == $current_user['id']) {

            unset($posts[$key]);

            $posts = array_values($posts);

            save_posts($posts);

            

            // Update post count

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

elseif ($action === 'like_post' && isset($_GET['post_id']) && $current_user) {

    $post_id = $_GET['post_id'];

    $posts = get_posts();

    

    foreach ($posts as &$post) {

        if ($post['id'] == $post_id) {

            $key = array_search($current_user['id'], $post['likes']);

            if ($key !== false) {

                unset($post['likes'][$key]);

                $post['likes'] = array_values($post['likes']);

            } else {

                $post['likes'][] = $current_user['id'];

            }

            break;

        }

    }

    

    save_posts($posts);

    header('Location: index.php');

    exit;

}

elseif ($action === 'follow' && isset($_GET['user_id']) && $current_user) {

    $target_user_id = $_GET['user_id'];

    

    if ($target_user_id != $current_user['id']) {

        $users = get_users();

        

        foreach ($users as &$user) {

            if ($user['id'] == $current_user['id']) {

                $key = array_search($target_user_id, $user['following']);

                if ($key !== false) {

                    unset($user['following'][$key]);

                    $user['following'] = array_values($user['following']);

                    $user['following_count'] = count($user['following']);

                } else {

                    $user['following'][] = $target_user_id;

                    $user['following_count'] = count($user['following']);

                }

            }

            

            if ($user['id'] == $target_user_id) {

                $key = array_search($current_user['id'], $user['followers']);

                if ($key !== false) {

                    unset($user['followers'][$key]);

                    $user['followers'] = array_values($user['followers']);

                } else {

                    $user['followers'][] = $current_user['id'];

                }

                $user['followers_count'] = count($user['followers']);

            }

        }

        

        save_users($users);

    }

    

    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');

    exit;

}

// Ambil data untuk tampilan

$users = get_users();

$posts = get_posts();

$notifications = get_notifications();

// Sort posts by date

usort($posts, function($a, $b) {

    return $b['created_at'] <=> $a['created_at'];

});

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

        }

    }

    $posts = $filtered_posts;

    

    // Get user notifications

    $user_notifications = array_filter($notifications, function($n) use ($current_user) {

        return $n['user_id'] == $current_user['id'] && !$n['read'];

    });

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DevForge - Social Media for Programmers</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        /* CSS dari kode HTML Anda */

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

        /* Login/Register styles */

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

        /* Header */

        .top-header {

            position: sticky;

            top: 0;

            background-color: rgba(0, 0, 0, 0.95);

            backdrop-filter: blur(12px);

            z-index: 100;

            border-bottom: 1px solid var(--border-color);

            padding: 12px 16px;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }

        .logo {

            font-size: 24px;

            font-weight: 700;

            color: var(--text-primary);

            text-decoration: none;

        }

        .header-actions {

            display: flex;

            gap: 16px;

            align-items: center;

        }

        .notification-btn, .settings-btn {

            background: none;

            border: none;

            color: var(--text-primary);

            font-size: 20px;

            cursor: pointer;

            width: 36px;

            height: 36px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            transition: background-color 0.2s;

        }

        .notification-btn:hover, .settings-btn:hover {

            background-color: var(--hover-color);

        }

        .profile-icon-top {

            width: 36px;

            height: 36px;

            border-radius: 50%;

            background-size: cover;

            background-position: center;

            background-color: #333;

            cursor: pointer;

            border: 2px solid var(--text-primary);

        }

        /* Main Content */

        .main-content {

            max-width: 600px;

            margin: 0 auto;

            min-height: calc(100vh - var(--bottom-nav-height));

            display: none;

        }

        .main-content.active {

            display: block;

        }

        /* Feed Tabs */

        .feed-tabs {

            display: flex;

            border-bottom: 1px solid var(--border-color);

        }

        .tab {

            flex: 1;

            text-align: center;

            padding: 16px 0;

            font-weight: 700;

            font-size: 15px;

            cursor: pointer;

            color: var(--text-secondary);

        }

        .tab.active {

            color: var(--text-primary);

            position: relative;

        }

        .tab.active::after {

            content: '';

            position: absolute;

            bottom: 0;

            left: 50%;

            transform: translateX(-50%);

            width: 56px;

            height: 4px;

            background-color: var(--text-primary);

            border-radius: 9999px;

        }

        /* Post Compose */

        .post-compose {

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

            display: flex;

        }

        .user-avatar {

            width: 48px;

            height: 48px;

            border-radius: 50%;

            margin-right: 12px;

            background-size: cover;

            background-position: center;

            background-color: #333;

            flex-shrink: 0;

        }

        .post-input-container {

            flex: 1;

        }

        .post-input {

            background-color: transparent;

            border: none;

            color: var(--text-primary);

            font-size: 16px;

            width: 100%;

            resize: none;

            outline: none;

            margin-bottom: 16px;

            padding: 8px 0;

            min-height: 80px;

            font-family: inherit;

        }

        .post-input::placeholder {

            color: var(--text-secondary);

        }

        .post-submit {

            background-color: var(--text-primary);

            color: var(--background-color);

            border: none;

            border-radius: 9999px;

            padding: 8px 16px;

            font-weight: 700;

            font-size: 15px;

            cursor: pointer;

            transition: all 0.2s;

        }

        .post-submit:hover {

            background-color: #e0e0e0;

        }

        /* Posts */

        .feed {

            border-bottom: 1px solid var(--border-color);

        }

        .post {

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

            display: flex;

            position: relative;

        }

        .post:hover {

            background-color: var(--hover-color);

        }

        .post-avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            margin-right: 12px;

            background-size: cover;

            background-position: center;

            background-color: #333;

            flex-shrink: 0;

        }

        .post-content {

            flex: 1;

        }

        .post-header {

            display: flex;

            align-items: flex-start;

            margin-bottom: 4px;

        }

        .post-author-info {

            flex: 1;

        }

        .post-author {

            font-weight: 700;

            margin-right: 4px;

        }

        .post-handle {

            color: var(--text-secondary);

            font-size: 14px;

        }

        .post-time {

            color: var(--text-secondary);

            font-size: 14px;

            margin-left: 8px;

        }

        .post-text {

            margin-bottom: 12px;

            line-height: 1.5;

            font-size: 15px;

            word-break: break-word;

        }

        .post-actions-footer {

            display: flex;

            justify-content: space-between;

            max-width: 425px;

            margin-top: 12px;

        }

        .post-action {

            display: flex;

            align-items: center;

            color: var(--text-secondary);

            font-size: 13px;

            transition: color 0.2s;

            cursor: pointer;

            text-decoration: none;

        }

        .post-action:hover {

            color: var(--text-primary);

        }

        .action-icon {

            margin-right: 8px;

            width: 34px;

            height: 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            transition: background-color 0.2s;

        }

        .post-action:hover .action-icon {

            background-color: var(--hover-color);

        }

        /* Comments */

        .comment-section {

            margin-top: 12px;

            border-top: 1px solid var(--border-color);

            padding-top: 12px;

        }

        .comment-form {

            display: flex;

            margin-bottom: 12px;

        }

        .comment-input {

            flex: 1;

            background-color: var(--card-background);

            border: 1px solid var(--border-color);

            border-radius: 9999px;

            padding: 8px 16px;

            color: var(--text-primary);

            font-size: 14px;

            outline: none;

        }

        .comment-submit {

            background-color: var(--text-primary);

            color: var(--background-color);

            border: none;

            border-radius: 9999px;

            padding: 8px 16px;

            margin-left: 8px;

            font-weight: 700;

            font-size: 14px;

            cursor: pointer;

        }

        .comment-item {

            display: flex;

            padding: 8px 0;

            border-bottom: 1px solid rgba(255, 255, 255, 0.05);

        }

        .comment-avatar {

            width: 32px;

            height: 32px;

            border-radius: 50%;

            margin-right: 8px;

            background-size: cover;

            background-position: center;

            background-color: #333;

            flex-shrink: 0;

        }

        .comment-author {

            font-weight: 700;

            font-size: 14px;

            margin-right: 4px;

        }

        .comment-time {

            color: var(--text-secondary);

            font-size: 12px;

        }

        .comment-text {

            font-size: 14px;

            line-height: 1.4;

            margin-bottom: 4px;

        }

        /* Profile Page */

        .profile-banner {

            height: 150px;

            background: linear-gradient(45deg, #111111, #222222);

            position: relative;

        }

        .profile-header {

            padding: 20px 16px;

            position: relative;

        }

        .profile-avatar-large {

            width: 100px;

            height: 100px;

            border-radius: 50%;

            margin-bottom: 16px;

            background-size: cover;

            background-position: center;

            border: 4px solid var(--background-color);

            position: relative;

            margin-top: -50px;

            background-color: #333333;

        }

        .profile-name {

            font-size: 24px;

            font-weight: 700;

            margin-bottom: 4px;

        }

        .profile-handle {

            color: var(--text-secondary);

            font-size: 16px;

            margin-bottom: 12px;

        }

        .profile-bio {

            margin-bottom: 16px;

            line-height: 1.5;

        }

        .profile-stats {

            display: flex;

            gap: 20px;

            margin-bottom: 20px;

        }

        .profile-stat {

            display: flex;

            flex-direction: column;

        }

        .stat-count {

            font-weight: 700;

            font-size: 18px;

        }

        .stat-label {

            color: var(--text-secondary);

            font-size: 14px;

        }

        .profile-actions {

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

            display: flex;

            gap: 12px;

        }

        .profile-btn {

            flex: 1;

            padding: 12px;

            border-radius: 9999px;

            border: 1px solid var(--border-color);

            background-color: transparent;

            color: var(--text-primary);

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            text-align: center;

        }

        .profile-btn:hover {

            background-color: var(--hover-color);

        }

        /* Bottom Navigation */

        .bottom-nav {

            position: fixed;

            bottom: 0;

            left: 0;

            width: 100%;

            height: var(--bottom-nav-height);

            background-color: rgba(0, 0, 0, 0.95);

            backdrop-filter: blur(20px);

            border-top: 1px solid var(--border-color);

            display: flex;

            justify-content: space-around;

            align-items: center;

            z-index: 100;

        }

        .nav-item {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            color: var(--text-secondary);

            text-decoration: none;

            font-size: 10px;

            padding: 8px;

            border-radius: 8px;

            transition: all 0.2s;

            flex: 1;

            height: 100%;

            background: none;

            border: none;

            cursor: pointer;

        }

        .nav-item.active {

            color: var(--text-primary);

        }

        .nav-icon {

            font-size: 20px;

            margin-bottom: 4px;

        }

        /* Modal */

        .modal {

            display: none;

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            background-color: rgba(0, 0, 0, 0.9);

            z-index: 1000;

            align-items: center;

            justify-content: center;

        }

        .modal-content {

            background-color: var(--background-color);

            border-radius: 16px;

            width: 90%;

            max-width: 500px;

            max-height: 90vh;

            overflow-y: auto;

            border: 1px solid var(--border-color);

        }

        .modal-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

        }

        .modal-title {

            font-size: 20px;

            font-weight: 700;

        }

        .modal-close {

            background: none;

            border: none;

            color: var(--text-primary);

            font-size: 24px;

            cursor: pointer;

            width: 36px;

            height: 36px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

        }

        .modal-body {

            padding: 16px;

        }

        .form-group {

            margin-bottom: 20px;

        }

        .form-label {

            display: block;

            margin-bottom: 8px;

            font-weight: 600;

        }

        .form-input {

            width: 100%;

            padding: 12px;

            background-color: var(--card-background);

            border: 1px solid var(--border-color);

            border-radius: 8px;

            color: var(--text-primary);

            font-size: 16px;

        }

        .form-textarea {

            width: 100%;

            padding: 12px;

            background-color: var(--card-background);

            border: 1px solid var(--border-color);

            border-radius: 8px;

            color: var(--text-primary);

            font-size: 16px;

            resize: vertical;

            min-height: 100px;

        }

        .modal-actions {

            padding: 16px;

            border-top: 1px solid var(--border-color);

            display: flex;

            gap: 12px;

            justify-content: flex-end;

        }

        .modal-btn {

            padding: 10px 20px;

            border-radius: 9999px;

            border: none;

            font-weight: 700;

            cursor: pointer;

        }

        .modal-btn.primary {

            background-color: var(--text-primary);

            color: var(--background-color);

        }

        .modal-btn.secondary {

            background-color: transparent;

            color: var(--text-primary);

            border: 1px solid var(--border-color);

        }

        /* Delete button */

        .post-delete-btn {

            position: absolute;

            top: 16px;

            right: 16px;

            background: transparent;

            border: none;

            color: var(--text-secondary);

            cursor: pointer;

            width: 30px;

            height: 30px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

        }

        .post-delete-btn:hover {

            background-color: rgba(255, 51, 51, 0.2);

            color: var(--error-color);

        }

        /* Badges */

        .blue-check-badge {

            color: var(--blue-check-color);

            margin-left: 4px;

            font-size: 14px;

        }

        .gold-check-badge {

            color: var(--gold-check-color);

            margin-left: 4px;

            font-size: 14px;

        }

        .owner-badge {

            background-color: var(--verified-color);

            color: var(--background-color);

            font-size: 10px;

            padding: 2px 6px;

            border-radius: 4px;

            margin-left: 8px;

            font-weight: bold;

        }

        /* Search Page */

        .search-container {

            padding: 12px 16px;

            border-bottom: 1px solid var(--border-color);

        }

        .search-box {

            background-color: var(--card-background);

            border-radius: 9999px;

            padding: 12px 20px;

            display: flex;

            align-items: center;

        }

        .search-icon {

            color: var(--text-secondary);

            margin-right: 12px;

            font-size: 18px;

        }

        .search-input {

            background-color: transparent;

            border: none;

            color: var(--text-primary);

            font-size: 16px;

            width: 100%;

            outline: none;

        }

        .search-results {

            padding: 20px 16px;

        }

        .search-message {

            text-align: center;

            padding: 40px 20px;

            color: var(--text-secondary);

            font-size: 16px;

        }

        .chat-item {

            display: flex;

            align-items: center;

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

            cursor: pointer;

        }

        .chat-avatar {

            width: 48px;

            height: 48px;

            border-radius: 50%;

            background-color: #333333;

            margin-right: 12px;

            background-size: cover;

            background-position: center;

        }

        .chat-contact {

            font-weight: 700;

            margin-bottom: 4px;

        }

        .chat-preview {

            color: var(--text-secondary);

            font-size: 14px;

        }

        .chat-time {

            color: var(--text-secondary);

            font-size: 12px;

        }

        /* Notifications */

        .notifications-container {

            padding: 0;

        }

        .notification-item {

            display: flex;

            align-items: flex-start;

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

        }

        .notification-icon {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            background-color: #333333;

            margin-right: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-size: 18px;

        }

        .notification-content {

            flex: 1;

        }

        .notification-text {

            margin-bottom: 4px;

            line-height: 1.4;

        }

        .notification-time {

            color: var(--text-secondary);

            font-size: 12px;

        }

        .notification-unread {

            background-color: rgba(255, 255, 255, 0.05);

        }

        /* Settings */

        .profile-settings {

            padding: 16px 0;

        }

        .setting-item {

            padding: 16px;

            border-bottom: 1px solid var(--border-color);

            display: flex;

            align-items: center;

            justify-content: space-between;

            cursor: pointer;

        }

        .setting-info {

            display: flex;

            flex-direction: column;

        }

        .setting-title {

            font-weight: 700;

            margin-bottom: 4px;

        }

        .setting-desc {

            color: var(--text-secondary);

            font-size: 14px;

        }

        .setting-arrow {

            color: var(--text-secondary);

        }

        /* Checkmark options */

        .checkmark-options {

            display: flex;

            gap: 12px;

            margin-bottom: 16px;

        }

        .checkmark-option {

            flex: 1;

            padding: 16px;

            border: 2px solid var(--border-color);

            border-radius: 8px;

            text-align: center;

            cursor: pointer;

        }

        .checkmark-option.selected {

            border-color: var(--blue-check-color);

        }

        .checkmark-option i {

            font-size: 24px;

            margin-bottom: 8px;

            display: block;

        }

        /* Privacy options */

        .privacy-options {

            display: flex;

            flex-direction: column;

            gap: 8px;

            margin-bottom: 16px;

        }

        .privacy-option {

            display: flex;

            align-items: center;

            padding: 12px;

            border: 1px solid var(--border-color);

            border-radius: 8px;

            cursor: pointer;

        }

        .privacy-option.selected {

            border-color: var(--text-primary);

        }

        .privacy-radio {

            margin-right: 12px;

            width: 20px;

            height: 20px;

            border-radius: 50%;

            border: 2px solid var(--text-secondary);

            display: flex;

            align-items: center;

            justify-content: center;

        }

        .privacy-option.selected .privacy-radio {

            border-color: var(--text-primary);

        }

        .privacy-option.selected .privacy-radio::after {

            content: '';

            width: 10px;

            height: 10px;

            border-radius: 50%;

            background-color: var(--text-primary);

        }

    </style>

</head>

<body>

    <?php if ($view === 'login'): ?>

        <!-- LOGIN PAGE -->

        <div class="auth-container">

            <div class="auth-box">

                <div class="auth-logo">DevForge</div>

                <?php if (isset($login_error)): ?>

                    <div class="error-message"><?php echo $login_error; ?></div>

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

        <!-- REGISTER PAGE -->

        <div class="auth-container">

            <div class="auth-box">

                <div class="auth-logo">DevForge</div>

                <?php if (isset($register_error)): ?>

                    <div class="error-message"><?php echo $register_error; ?></div>

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

        <!-- MAIN APP -->

        <div class="top-header">

            <a href="index.php" class="logo">DevForge</a>

            <div class="header-actions">

                <button class="notification-btn" onclick="showPage('notifications')">

                    <i class="fas fa-bell"></i>

                </button>

                <button class="settings-btn" onclick="showModal('editProfileModal')">

                    <i class="fas fa-cog"></i>

                </button>

                <div class="profile-icon-top" style="background-image: url('<?php echo $current_user['avatar']; ?>')" 

                     onclick="showPage('profile')"></div>

            </div>

        </div>

        <!-- Navigation -->

        <nav class="bottom-nav">

            <button class="nav-item <?php echo $view === 'home' ? 'active' : ''; ?>" onclick="showPage('home')">

                <div class="nav-icon"><i class="fas fa-home"></i></div>

                <span>Home</span>

            </button>

            <button class="nav-item <?php echo $view === 'search' ? 'active' : ''; ?>" onclick="showPage('search')">

                <div class="nav-icon"><i class="fas fa-search"></i></div>

                <span>Search</span>

            </button>

            <button class="nav-item <?php echo $view === 'notifications' ? 'active' : ''; ?>" onclick="showPage('notifications')">

                <div class="nav-icon"><i class="fas fa-bell"></i></div>

                <span>Notifikasi</span>

            </button>

            <button class="nav-item <?php echo $view === 'chat' ? 'active' : ''; ?>" onclick="showPage('chat')">

                <div class="nav-icon"><i class="fas fa-comment"></i></div>

                <span>Chat</span>

            </button>

            <button class="nav-item <?php echo $view === 'profile' ? 'active' : ''; ?>" onclick="showPage('profile')">

                <div class="nav-icon"><i class="fas fa-user"></i></div>

                <span>Profile</span>

            </button>

        </nav>

        <!-- Home Page -->

        <div class="main-content <?php echo $view === 'home' ? 'active' : ''; ?>" id="homePage">

            <div class="feed-tabs">

                <div class="tab active">Untuk Anda</div>

                <div class="tab">Mengikuti</div>

            </div>

            

            <!-- Create Post -->

            <div class="post-compose">

                <div class="user-avatar" style="background-image: url('<?php echo $current_user['avatar']; ?>')"></div>

                <div class="post-input-container">

                    <form method="POST" action="index.php?action=create_post">

                        <textarea name="content" class="post-input" placeholder="Apa yang sedang Anda kodekan hari ini?" required></textarea>

                        <button type="submit" class="post-submit">Post</button>

                    </form>

                </div>

            </div>

            

            <!-- Posts Feed -->

            <div class="feed">

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

                ?>

                <div class="post">

                    <?php if ($is_owner): ?>

                        <a href="index.php?action=delete_post&post_id=<?php echo $post['id']; ?>" 

                           class="post-delete-btn" onclick="return confirm('Hapus postingan ini?')">

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

                                    <?php if ($post_user['checkmark_type'] === 'blue'): ?>

                                        <i class="fas fa-check-circle blue-check-badge"></i>

                                    <?php elseif ($post_user['checkmark_type'] === 'gold'): ?>

                                        <i class="fas fa-check-circle gold-check-badge"></i>

                                    <?php endif; ?>

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

                        

                        <div class="post-actions-footer">

                            <button class="post-action comment-btn" onclick="toggleComments('<?php echo $post['id']; ?>')">

                                <div class="action-icon">

                                    <i class="far fa-comment"></i>

                                </div>

                                <span><?php echo count($post['comments']); ?></span>

                            </button>

                            <a href="index.php?action=like_post&post_id=<?php echo $post['id']; ?>" class="post-action like">

                                <div class="action-icon">

                                    <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>

                                </div>

                                <span><?php echo count($post['likes']); ?></span>

                            </a>

                        </div>

                        

                        <!-- Comments Section -->

                        <div class="comment-section" id="comment-section-<?php echo $post['id']; ?>" style="display: none;">

                            <form method="POST" action="index.php?action=add_comment" class="comment-form">

                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">

                                <input type="text" name="comment" class="comment-input" placeholder="Tulis komentar...">

                                <button type="submit" class="comment-submit">Kirim</button>

                            </form>

                            

                            <!-- Comments List -->

                            <?php foreach ($post['comments'] as $comment): ?>

                                <div class="comment-item">

                                    <div class="comment-avatar" style="background-image: url('<?php echo $comment['avatar']; ?>')"></div>

                                    <div class="comment-content">

                                        <div style="display: flex; align-items: center; margin-bottom: 4px;">

                                            <div class="comment-author"><?php echo $comment['display_name']; ?></div>

                                            <div class="comment-time"><?php echo time_ago($comment['created_at']); ?></div>

                                        </div>

                                        <div class="comment-text"><?php echo nl2br($comment['comment']); ?></div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- Search Page -->

        <div class="main-content <?php echo $view === 'search' ? 'active' : ''; ?>" id="searchPage">

            <div class="search-container">

                <div class="search-box">

                    <div class="search-icon"><i class="fas fa-search"></i></div>

                    <input type="text" class="search-input" placeholder="Cari programmer...">

                </div>

            </div>

            

            <div class="search-results">

                <div class="search-message">

                    <i class="fas fa-search fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>

                    <p>Cari programmer berdasarkan nama atau keahlian</p>

                </div>

                

                <!-- Programmer List -->

                <?php foreach ($users as $user): 

                    if ($user['id'] == $current_user['id']) continue;

                    $is_following = in_array($user['id'], $current_user['following']);

                ?>

                    <div class="chat-item" onclick="window.location.href='index.php?view=profile&user_id=<?php echo $user['id']; ?>'">

                        <div class="chat-avatar" style="background-image: url('<?php echo $user['avatar']; ?>')"></div>

                        <div class="chat-info">

                            <div class="chat-contact">

                                <?php echo $user['display_name']; ?>

                                <?php if ($user['checkmark_type'] === 'blue'): ?>

                                    <i class="fas fa-check-circle blue-check-badge"></i>

                                <?php elseif ($user['checkmark_type'] === 'gold'): ?>

                                    <i class="fas fa-check-circle gold-check-badge"></i>

                                <?php endif; ?>

                            </div>

                            <div class="chat-preview">@<?php echo $user['username']; ?> • <?php echo $user['role']; ?></div>

                            <div class="chat-time"><?php echo $user['followers_count']; ?> pengikut</div>

                        </div>

                        <a href="index.php?action=follow&user_id=<?php echo $user['id']; ?>" 

                           style="padding: 6px 12px; background-color: var(--text-primary); color: var(--background-color); 

                                  border-radius: 20px; text-decoration: none; font-size: 12px;">

                            <?php echo $is_following ? 'Following' : 'Follow'; ?>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- Notifications Page -->

        <div class="main-content <?php echo $view === 'notifications' ? 'active' : ''; ?>" id="notificationsPage">

            <div class="top-header" style="position: static;">

                <div style="font-size: 20px; font-weight: 700;">Notifikasi</div>

            </div>

            

            <div class="notifications-container">

                <?php if (empty($user_notifications)): ?>

                    <div class="search-message">

                        <i class="fas fa-bell-slash fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>

                        <p>Tidak ada notifikasi</p>

                    </div>

                <?php else: ?>

                    <?php foreach ($user_notifications as $notification): ?>

                        <div class="notification-item notification-unread">

                            <div class="notification-icon">

                                <i class="fas fa-bell"></i>

                            </div>

                            <div class="notification-content">

                                <div class="notification-text"><?php echo $notification['message']; ?></div>

                                <div class="notification-time"><?php echo time_ago($notification['created_at']); ?></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

        <!-- Chat Page -->

        <div class="main-content <?php echo $view === 'chat' ? 'active' : ''; ?>" id="chatPage">

            <div class="search-container">

                <div class="search-box">

                    <div class="search-icon"><i class="fas fa-search"></i></div>

                    <input type="text" class="search-input" placeholder="Cari programmer untuk chat...">

                </div>

            </div>

            

            <div class="search-results">

                <div class="search-message">

                    <i class="fas fa-comments fa-2x" style="margin-bottom: 16px; color: var(--text-secondary);"></i>

                    <p>Pilih programmer untuk memulai percakapan</p>

                </div>

                

                <!-- Programmer List -->

                <?php foreach ($users as $user): 

                    if ($user['id'] == $current_user['id']) continue;

                ?>

                    <div class="chat-item" onclick="alert('Fitur chat akan segera hadir!')">

                        <div class="chat-avatar" style="background-image: url('<?php echo $user['avatar']; ?>')"></div>

                        <div class="chat-info">

                            <div class="chat-contact">

                                <?php echo $user['display_name']; ?>

                                <?php if ($user['checkmark_type'] === 'blue'): ?>

                                    <i class="fas fa-check-circle blue-check-badge"></i>

                                <?php elseif ($user['checkmark_type'] === 'gold'): ?>

                                    <i class="fas fa-check-circle gold-check-badge"></i>

                                <?php endif; ?>

                            </div>

                            <div class="chat-preview">@<?php echo $user['username']; ?></div>

                            <div class="chat-time">Klik untuk chat</div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- Profile Page -->

        <div class="main-content <?php echo $view === 'profile' ? 'active' : ''; ?>" id="profilePage">

            <?php

            $profile_user_id = $_GET['user_id'] ?? $current_user['id'];

            $profile_user = null;

            foreach ($users as $u) {

                if ($u['id'] == $profile_user_id) {

                    $profile_user = $u;

                    break;

                }

            }

            

            if (!$profile_user) {

                $profile_user = $current_user;

            }

            

            $is_own_profile = $profile_user['id'] == $current_user['id'];

            $is_following = in_array($profile_user['id'], $current_user['following']);

            

            // Get user's posts

            $user_posts = array_filter($posts, function($post) use ($profile_user_id) {

                return $post['user_id'] == $profile_user_id;

            });

            ?>

            

            <div class="profile-banner"></div>

            

            <div class="profile-header">

                <div class="profile-avatar-large" style="background-image: url('<?php echo $profile_user['avatar']; ?>')"></div>

                

                <div class="profile-name">

                    <?php echo $profile_user['display_name']; ?>

                    <?php if ($profile_user['checkmark_type'] === 'blue'): ?>

                        <i class="fas fa-check-circle blue-check-badge"></i>

                    <?php elseif ($profile_user['checkmark_type'] === 'gold'): ?>

                        <i class="fas fa-check-circle gold-check-badge"></i>

                    <?php endif; ?>

                </div>

                

                <div class="profile-handle">@<?php echo $profile_user['username']; ?></div>

                <div class="profile-bio"><?php echo nl2br($profile_user['bio']); ?></div>

                

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

            

            <div class="profile-actions">

                <?php if ($is_own_profile): ?>

                    <button class="profile-btn" onclick="showModal('editProfileModal')">Edit Profil</button>

                    <a href="index.php?action=logout" class="profile-btn logout">Logout</a>

                <?php else: ?>

                    <a href="index.php?action=follow&user_id=<?php echo $profile_user['id']; ?>" 

                       class="profile-btn <?php echo $is_following ? 'logout' : ''; ?>">

                        <?php echo $is_following ? 'Unfollow' : 'Follow'; ?>

                    </a>

                <?php endif; ?>

            </div>

            

            <!-- User's Posts -->

            <div style="padding: 16px;">

                <h3 style="margin-bottom: 16px;">Postingan</h3>

                <?php if (empty($user_posts)): ?>

                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">

                        <p>Belum ada postingan</p>

                    </div>

                <?php else: ?>

                    <?php foreach ($user_posts as $post): ?>

                        <div class="post" style="margin-bottom: 16px; border: 1px solid var(--border-color); border-radius: 8px;">

                            <div class="post-content">

                                <div class="post-text"><?php echo nl2br($post['content']); ?></div>

                                <div class="post-time" style="margin-top: 8px; font-size: 12px;">

                                    <?php echo time_ago($post['created_at']); ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            

            <!-- Settings -->

            <?php if ($is_own_profile): ?>

                <div class="profile-settings">

                    <div class="setting-item" onclick="showModal('editProfileModal')">

                        <div class="setting-info">

                            <div class="setting-title">Akun</div>

                            <div class="setting-desc">Pengaturan akun dan keamanan</div>

                        </div>

                        <div class="setting-arrow">

                            <i class="fas fa-chevron-right"></i>

                        </div>

                    </div>

                    <div class="setting-item" onclick="showModal('privacyModal')">

                        <div class="setting-info">

                            <div class="setting-title">Privasi</div>

                            <div class="setting-desc">Kontrol siapa yang bisa melihat konten Anda</div>

                        </div>

                        <div class="setting-arrow">

                            <i class="fas fa-chevron-right"></i>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>

        <!-- Edit Profile Modal -->

        <div class="modal" id="editProfileModal">

            <div class="modal-content">

                <div class="modal-header">

                    <div class="modal-title">Edit Profil</div>

                    <button class="modal-close" onclick="hideModal('editProfileModal')">&times;</button>

                </div>

                <div class="modal-body">

                    <form method="POST" action="index.php?action=update_profile">

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

                        

                        <div class="form-group">

                            <label class="form-label">Centang Verifikasi</label>

                            <div class="checkmark-options">

                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] === 'none' ? 'selected' : ''; ?>" 

                                     onclick="selectCheckmark('none')">

                                    <i class="fas fa-times-circle"></i>

                                    <div>Tidak ada</div>

                                </div>

                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] === 'blue' ? 'selected' : ''; ?>" 

                                     onclick="selectCheckmark('blue')">

                                    <i class="fas fa-check-circle"></i>

                                    <div>Centang Biru</div>

                                </div>

                                <div class="checkmark-option <?php echo $current_user['checkmark_type'] === 'gold' ? 'selected' : ''; ?>" 

                                     onclick="selectCheckmark('gold')">

                                    <i class="fas fa-check-circle"></i>

                                    <div>Centang Emas</div>

                                </div>

                            </div>

                            <input type="hidden" name="checkmark_type" id="checkmarkType" value="<?php echo $current_user['checkmark_type']; ?>">

                        </div>

                        

                        <div class="form-group">

                            <label class="form-label">Privacy Settings</label>

                            <select name="privacy" class="form-input">

                                <option value="public" <?php echo $current_user['privacy'] == 'public' ? 'selected' : ''; ?>>Public</option>

                                <option value="private" <?php echo $current_user['privacy'] == 'private' ? 'selected' : ''; ?>>Private</option>

                            </select>

                        </div>

                        

                        <div class="modal-actions">

                            <button type="button" class="modal-btn secondary" onclick="hideModal('editProfileModal')">Batal</button>

                            <button type="submit" class="modal-btn primary">Simpan Perubahan</button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <!-- Privacy Modal -->

        <div class="modal" id="privacyModal">

            <div class="modal-content">

                <div class="modal-header">

                    <div class="modal-title">Pengaturan Privasi</div>

                    <button class="modal-close" onclick="hideModal('privacyModal')">&times;</button>

                </div>

                <div class="modal-body">

                    <div class="privacy-options">

                        <div class="privacy-option <?php echo $current_user['privacy'] == 'public' ? 'selected' : ''; ?>" 

                             onclick="selectPrivacy('public')">

                            <div class="privacy-radio"></div>

                            <div class="privacy-info">

                                <div class="privacy-title">Public</div>

                                <div class="privacy-desc">Semua orang dapat melihat postingan Anda</div>

                            </div>

                        </div>

                        <div class="privacy-option <?php echo $current_user['privacy'] == 'private' ? 'selected' : ''; ?>" 

                             onclick="selectPrivacy('private')">

                            <div class="privacy-radio"></div>

                            <div class="privacy-info">

                                <div class="privacy-title">Private</div>

                                <div class="privacy-desc">Hanya Anda yang dapat melihat postingan</div>

                            </div>

                        </div>

                    </div>

                    <form method="POST" action="index.php?action=update_profile" id="privacyForm">

                        <input type="hidden" name="display_name" value="<?php echo $current_user['display_name']; ?>">

                        <input type="hidden" name="username" value="<?php echo $current_user['username']; ?>">

                        <input type="hidden" name="bio" value="<?php echo $current_user['bio']; ?>">

                        <input type="hidden" name="role" value="<?php echo $current_user['role']; ?>">

                        <input type="hidden" name="checkmark_type" value="<?php echo $current_user['checkmark_type']; ?>">

                        <input type="hidden" name="privacy" id="privacyValue" value="<?php echo $current_user['privacy']; ?>">

                        <div class="modal-actions">

                            <button type="button" class="modal-btn secondary" onclick="hideModal('privacyModal')">Batal</button>

                            <button type="submit" class="modal-btn primary">Simpan</button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <script>

            // Fungsi untuk toggle comments

            function toggleComments(postId) {

                var commentSection = document.getElementById('comment-section-' + postId);

                if (commentSection.style.display === 'none' || commentSection.style.display === '') {

                    commentSection.style.display = 'block';

                } else {

                    commentSection.style.display = 'none';

                }

            }

            // Fungsi untuk navigasi halaman

            function showPage(page) {

                window.location.href = 'index.php?view=' + page;

            }

            // Fungsi untuk show/hide modal

            function showModal(modalId) {

                document.getElementById(modalId).style.display = 'flex';

            }

            function hideModal(modalId) {

                document.getElementById(modalId).style.display = 'none';

            }

            // Fungsi untuk select checkmark

            function selectCheckmark(type) {

                document.getElementById('checkmarkType').value = type;

                document.querySelectorAll('.checkmark-option').forEach(opt => {

                    opt.classList.remove('selected');

                });

                document.querySelectorAll('.checkmark-option').forEach(opt => {

                    if (opt.onclick.toString().includes("'" + type + "'")) {

                        opt.classList.add('selected');

                    }

                });

            }

            // Fungsi untuk select privacy

            function selectPrivacy(privacy) {

                document.getElementById('privacyValue').value = privacy;

                document.querySelectorAll('.privacy-option').forEach(opt => {

                    opt.classList.remove('selected');

                });

                document.querySelectorAll('.privacy-option').forEach(opt => {

                    if (opt.onclick.toString().includes("'" + privacy + "'")) {

                        opt.classList.add('selected');

                    }

                });

            }

            // Close modal ketika klik di luar

            window.onclick = function(event) {

                if (event.target.classList.contains('modal')) {

                    event.target.style.display = 'none';

                }

            };

        </script>

    <?php endif; ?>

</body>

</html>

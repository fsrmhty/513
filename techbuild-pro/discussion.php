<?php
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/json_functions.php'; // 如果需要使用JSON存储

// 必须登录才能访问
if (!isset($_SESSION['user_id']) && !is_subscriber()) {
    header("Location: /techbuild-pro/auth/login.php");
    exit;
}

// JSON文件路径
define('DISCUSSION_JSON_PATH', dirname(__FILE__) . '/data/discussion.json');

/**
 * 确保讨论区JSON文件存在
 */
function ensure_discussion_json() {
    $data_dir = dirname(DISCUSSION_JSON_PATH);
    
    // 创建数据目录
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // 创建JSON文件如果不存在
    if (!file_exists(DISCUSSION_JSON_PATH)) {
        $initial_data = [
            'posts' => [],
            'last_post_id' => 0
        ];
        
        // 添加示例数据
        $sample_posts = generate_sample_posts();
        $initial_data['posts'] = $sample_posts;
        $initial_data['last_post_id'] = count($sample_posts);
        
        file_put_contents(DISCUSSION_JSON_PATH, json_encode($initial_data, JSON_PRETTY_PRINT));
    }
}

/**
 * 生成20条示例帖子
 */
function generate_sample_posts() {
    $sample_users = [
        ['id' => 101, 'name' => 'Alex Johnson', 'role' => 'customer'],
        ['id' => 102, 'name' => 'Sarah Miller', 'role' => 'customer'],
        ['id' => 103, 'name' => 'Mike Davis', 'role' => 'customer'],
        ['id' => 104, 'name' => 'TechSupport_Admin', 'role' => 'admin'],
        ['id' => 105, 'name' => 'Emma Wilson', 'role' => 'customer'],
        ['id' => 106, 'name' => 'PCBuilder_Pro', 'role' => 'technician'],
        ['id' => 107, 'name' => 'Robert Chen', 'role' => 'customer'],
        ['id' => 108, 'name' => 'Lisa Wang', 'role' => 'customer'],
        ['id' => 109, 'name' => 'David Smith', 'role' => 'customer'],
        ['id' => 110, 'name' => 'Maria Garcia', 'role' => 'customer']
    ];
    
    $sample_topics = [
        'General Feedback',
        'Product Reviews',
        'Technical Support',
        'Service Experience',
        'Feature Requests',
        'Bug Reports',
        'Success Stories',
        'Troubleshooting Help'
    ];
    
    $sample_titles = [
        'Great experience with repair service!',
        'Need help with GPU compatibility',
        'Custom build recommendation?',
        'How long does repair usually take?',
        'Best motherboard for gaming?',
        'Service improvement suggestions',
        'PC keeps overheating - help needed',
        'Excellent customer service!',
        'RAM compatibility issue',
        'Upgrade advice needed',
        'Fast and professional service',
        'PC won\'t boot after upgrade',
        'Monitor recommendation for programming',
        'Thermal paste replacement service',
        'Gaming PC build review',
        'Data recovery experience',
        'Power supply noise problem',
        'Windows installation service',
        'RGB lighting setup help',
        'Overall satisfaction with services'
    ];
    
    $sample_contents = [
        'I recently had my laptop repaired at TechBuild Pro and the service was excellent. The technician was knowledgeable and fixed the issue quickly.',
        'I\'m building a new gaming PC and need advice on GPU compatibility with my motherboard. Any recommendations?',
        'Looking for recommendations for a custom PC build for video editing. Budget around $1500.',
        'How long does a typical repair service take? I need my computer for work.',
        'What\'s the best motherboard for gaming in the mid-range price category?',
        'I think the website could use a better search function. It\'s hard to find specific components.',
        'My PC keeps overheating during gaming sessions. I\'ve already cleaned the fans but it\'s still happening.',
        'The customer service team was very helpful when I had questions about my order. Great experience!',
        'I bought 32GB RAM but my system only shows 16GB. Could this be a compatibility issue?',
        'I want to upgrade my 5-year-old PC. Should I upgrade components or build a new one?',
        'I used the repair service last week and my computer is working perfectly now. Very satisfied!',
        'After upgrading my CPU, my PC won\'t boot. The fans spin but no display.',
        'Looking for a good monitor for programming work. Any recommendations under $300?',
        'Does TechBuild Pro offer thermal paste replacement service? My CPU temperatures are high.',
        'Just completed my first gaming PC build with components from here. Everything works perfectly!',
        'I needed data recovery from a failed hard drive. The service was professional and successful.',
        'My power supply makes a buzzing noise. Is this normal or should I be concerned?',
        'Do you offer Windows installation service? I\'m not comfortable doing it myself.',
        'I need help setting up RGB lighting on my new build. Any tutorials or guides?',
        'Overall, I\'m very satisfied with TechBuild Pro services. Keep up the good work!'
    ];
    
    $posts = [];
    $post_id = 1;
    
    for ($i = 0; $i < 20; $i++) {
        $user = $sample_users[$i % count($sample_users)];
        $days_ago = rand(1, 30);
        $hours_ago = rand(0, 23);
        $minutes_ago = rand(0, 59);
        
        $posts[] = [
            'id' => $post_id++,
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_role' => $user['role'],
            'title' => $sample_titles[$i % count($sample_titles)],
            'content' => $sample_contents[$i % count($sample_contents)],
            'category' => $sample_topics[array_rand($sample_topics)],
            'created_at' => date('Y-m-d H:i:s', strtotime("-$days_ago days -$hours_ago hours -$minutes_ago minutes")),
            'replies' => rand(0, 15),
            'views' => rand(50, 500),
            'likes' => rand(0, 50),
            'status' => 'active',
            'tags' => $i < 5 ? ['featured'] : []
        ];
    }
    
    return $posts;
}

/**
 * 获取所有帖子
 */
function get_all_posts($filter_category = '') {
    ensure_discussion_json();
    
    $json_data = file_get_contents(DISCUSSION_JSON_PATH);
    $data = json_decode($json_data, true);
    
    $posts = $data['posts'] ?? [];
    
    // 按类别过滤
    if (!empty($filter_category)) {
        $posts = array_filter($posts, function($post) use ($filter_category) {
            return $post['category'] === $filter_category;
        });
    }
    
    // 按时间排序（最新的在前）
    usort($posts, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $posts;
}

/**
 * 添加新帖子
 */
function add_new_post($post_data) {
    ensure_discussion_json();
    
    $json_data = file_get_contents(DISCUSSION_JSON_PATH);
    $data = json_decode($json_data, true);
    
    // 获取新ID
    $new_id = ($data['last_post_id'] ?? 0) + 1;
    
    // 准备当前用户信息
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_name = $_SESSION['user_name'] ?? 'Anonymous';
    $user_role = $_SESSION['user_role'] ?? 'customer';
    
    // 如果是订阅者登录
    if (is_subscriber()) {
        $subscriber = get_subscriber_info();
        if ($subscriber) {
            $user_id = $subscriber['id'] + 1000; // 添加偏移量
            $user_name = $subscriber['name'];
            $user_role = 'customer';
        }
    }
    
    $new_post = [
        'id' => $new_id,
        'user_id' => $user_id,
        'user_name' => $user_name,
        'user_role' => $user_role,
        'title' => $post_data['title'],
        'content' => $post_data['content'],
        'category' => $post_data['category'],
        'created_at' => date('Y-m-d H:i:s'),
        'replies' => 0,
        'views' => 0,
        'likes' => 0,
        'status' => 'active',
        'tags' => []
    ];
    
    // 添加到帖子数组
    $data['posts'][] = $new_post;
    $data['last_post_id'] = $new_id;
    
    // 保存到JSON文件
    if (file_put_contents(DISCUSSION_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT))) {
        return $new_id;
    }
    
    return false;
}

/**
 * 获取帖子类别列表
 */
function get_post_categories() {
    $posts = get_all_posts();
    $categories = array_unique(array_column($posts, 'category'));
    sort($categories);
    return $categories;
}

// 处理表单提交
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'new_post') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? 'General Feedback');
        
        if (empty($title) || empty($content)) {
            $error = "Please enter both title and content";
        } else {
            $post_data = [
                'title' => $title,
                'content' => $content,
                'category' => $category
            ];
            
            $new_post_id = add_new_post($post_data);
            if ($new_post_id) {
                $success = "Your post has been published successfully!";
                // 清空表单
                $_POST = [];
            } else {
                $error = "Failed to publish post. Please try again.";
            }
        }
    }
}

// 获取过滤参数
$filter_category = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';

// 获取帖子数据
$all_posts = get_all_posts($filter_category);

// 搜索过滤
if (!empty($search_query)) {
    $search_query_lower = strtolower($search_query);
    $all_posts = array_filter($all_posts, function($post) use ($search_query_lower) {
        return strpos(strtolower($post['title']), $search_query_lower) !== false ||
               strpos(strtolower($post['content']), $search_query_lower) !== false ||
               strpos(strtolower($post['user_name']), $search_query_lower) !== false;
    });
}

// 获取类别列表
$categories = get_post_categories();

// 获取当前用户信息
$current_user_name = $_SESSION['user_name'] ?? (is_subscriber() ? get_subscriber_info()['name'] : 'Guest');
$current_user_role = $_SESSION['user_role'] ?? (is_subscriber() ? 'customer' : 'guest');
?>

<?php include 'includes/header.php'; ?>

<div class="page-header">
    <h1 class="page-title">Community Discussion Forum</h1>
    <p class="page-subtitle">Share feedback, ask questions, and discuss with other TechBuild Pro users</p>
</div>

<div class="admin-container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- 用户信息栏 -->
    <div class="card-hover" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; color: white;">Welcome, <?= htmlspecialchars($current_user_name) ?>!</h3>
                <p style="margin: 0.5rem 0 0; opacity: 0.9;">Share your thoughts with the community</p>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: var(--radius);">
                <span style="font-weight: 600;">Posts:</span> <?= count($all_posts) ?> • 
                <span style="font-weight: 600;">Role:</span> <?= ucfirst($current_user_role) ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 1.5rem;">
        <!-- 左侧栏 -->
        <div>
            <!-- 新建帖子卡片 -->
            <div class="card-hover" style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">Create New Post</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="new_post">
                    
                    <div class="form-group">
                        <label class="form-label">Title:</label>
                        <input type="text" name="title" class="form-control" 
                               placeholder="Enter post title" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category:</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Content:</label>
                        <textarea name="content" class="form-control" rows="4" 
                                  placeholder="Share your thoughts, questions, or feedback..." 
                                  required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">Publish Post</button>
                </form>
            </div>

            <!-- 类别筛选 -->
            <div class="card-hover" style="background: white; padding: 1.5rem; border-radius: var(--radius-lg);">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">Categories</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="discussion.php" 
                       class="btn <?= empty($filter_category) ? 'btn-primary' : 'btn-outline' ?>"
                       style="text-align: left; justify-content: flex-start;">
                        All Categories (<?= count(get_all_posts()) ?>)
                    </a>
                    <?php foreach ($categories as $cat): 
                        $cat_posts = get_all_posts($cat);
                        $cat_count = count($cat_posts);
                    ?>
                        <a href="discussion.php?category=<?= urlencode($cat) ?>" 
                           class="btn <?= $filter_category === $cat ? 'btn-primary' : 'btn-outline' ?>"
                           style="text-align: left; justify-content: space-between;">
                            <span><?= htmlspecialchars($cat) ?></span>
                            <span style="background: var(--gray-100); padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem;">
                                <?= $cat_count ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 主内容区 -->
        <div>
            <!-- 搜索栏 -->
            <div class="card-hover" style="background: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 1rem;">
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search discussions..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                    <?php if (!empty($filter_category)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($filter_category) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search_query) || !empty($filter_category)): ?>
                        <a href="discussion.php" class="btn btn-outline">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 帖子列表 -->
            <div class="card-hover" style="background: white; padding: 0; border-radius: var(--radius-lg);">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                    <h3 style="color: var(--primary); margin: 0;">
                        <?php if (!empty($filter_category)): ?>
                            <?= htmlspecialchars($filter_category) ?> Discussions
                        <?php elseif (!empty($search_query)): ?>
                            Search Results for "<?= htmlspecialchars($search_query) ?>"
                        <?php else: ?>
                            Recent Discussions
                        <?php endif; ?>
                        <span style="color: var(--gray-600); font-size: 0.875rem; margin-left: 0.5rem;">
                            (<?= count($all_posts) ?> posts)
                        </span>
                    </h3>
                </div>
                
                <?php if (empty($all_posts)): ?>
                    <div style="text-align: center; padding: 3rem;">
                        <p style="color: var(--gray-500); margin-bottom: 1rem;">No discussions found.</p>
                        <?php if (!empty($search_query)): ?>
                            <p>Try different search terms or <a href="discussion.php">view all discussions</a>.</p>
                        <?php else: ?>
                            <a href="discussion.php" class="btn btn-primary">View All Discussions</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($all_posts as $post): 
                            $time_ago = time_elapsed_string($post['created_at']);
                            $is_featured = in_array('featured', $post['tags']);
                        ?>
                            <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-100); transition: var(--transition);"
                                 class="post-item"
                                 onmouseover="this.style.backgroundColor='var(--gray-50)'"
                                 onmouseout="this.style.backgroundColor='white'">
                                
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <div>
                                        <h4 style="margin: 0; color: var(--gray-900);">
                                            <?php if ($is_featured): ?>
                                                <span style="color: var(--warning); margin-right: 0.5rem;">⭐</span>
                                            <?php endif; ?>
                                            <a href="discussion_view.php?id=<?= $post['id'] ?>" 
                                               style="color: inherit; text-decoration: none;">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                        </h4>
                                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-600);">
                                            <span>
                                                <span style="font-weight: 600;">By:</span> 
                                                <span style="color: var(--primary);"><?= htmlspecialchars($post['user_name']) ?></span>
                                                <?php if ($post['user_role'] === 'admin'): ?>
                                                    <span style="background: var(--primary); color: white; padding: 0.1rem 0.4rem; border-radius: var(--radius-sm); font-size: 0.7rem; margin-left: 0.25rem;">ADMIN</span>
                                                <?php elseif ($post['user_role'] === 'technician'): ?>
                                                    <span style="background: var(--success); color: white; padding: 0.1rem 0.4rem; border-radius: var(--radius-sm); font-size: 0.7rem; margin-left: 0.25rem;">TECH</span>
                                                <?php endif; ?>
                                            </span>
                                            <span>•</span>
                                            <span><?= $time_ago ?></span>
                                            <span>•</span>
                                            <span><?= htmlspecialchars($post['category']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div style="text-align: right; min-width: 100px;">
                                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                            <div style="text-align: center;">
                                                <div style="font-size: 0.75rem; color: var(--gray-500);">Replies</div>
                                                <div style="font-weight: 600; color: var(--primary);"><?= $post['replies'] ?></div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-size: 0.75rem; color: var(--gray-500);">Likes</div>
                                                <div style="font-weight: 600; color: var(--success);"><?= $post['likes'] ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <p style="margin: 0.75rem 0; color: var(--gray-700); line-height: 1.5;">
                                    <?= htmlspecialchars(substr($post['content'], 0, 200)) ?>
                                    <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                                </p>
                                
                                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                    <a href="discussion_view.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline">Read More</a>
                                    <button class="btn btn-sm btn-outline" onclick="likePost(<?= $post['id'] ?>)">
                                        <span style="color: var(--success);">👍</span> Like
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 辅助函数：计算时间差
function time_elapsed_string(datetime) {
    var time = new Date(datetime).getTime();
    var now = new Date().getTime();
    var diff = now - time;
    
    var seconds = Math.floor(diff / 1000);
    var minutes = Math.floor(seconds / 60);
    var hours = Math.floor(minutes / 60);
    var days = Math.floor(hours / 24);
    var weeks = Math.floor(days / 7);
    var months = Math.floor(days / 30);
    var years = Math.floor(days / 365);
    
    if (years > 0) return years + " year" + (years > 1 ? "s" : "") + " ago";
    if (months > 0) return months + " month" + (months > 1 ? "s" : "") + " ago";
    if (weeks > 0) return weeks + " week" + (weeks > 1 ? "s" : "") + " ago";
    if (days > 0) return days + " day" + (days > 1 ? "s" : "") + " ago";
    if (hours > 0) return hours + " hour" + (hours > 1 ? "s" : "") + " ago";
    if (minutes > 0) return minutes + " minute" + (minutes > 1 ? "s" : "") + " ago";
    return "just now";
}

// 点赞功能
function likePost(postId) {
    // 在实际应用中，这里应该发送AJAX请求到服务器
    alert('Liked post #' + postId + '\nIn a live system, this would update the like count.');
}

// 自动更新所有时间显示
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.post-item').forEach(function(item) {
        var timeElement = item.querySelector('span:nth-child(3)');
        if (timeElement) {
            var timeText = timeElement.textContent;
            if (timeText.includes('ago')) {
                // 时间已经是相对格式，不需要更新
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
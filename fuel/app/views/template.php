<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | 課題管理アプリ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <?php echo \Asset::css('style.css'); ?>
</head>
<body class="bg-light">
    <header class="global-header">
        <div class="header-inner">
            <div class="brand">
                <h1>Task<span>Flow</span></h1> </div>
            
            <div class="user-controls">
                <div class="user-badge">
                    <span class="user-greeting">Welcome,</span>
                    <span class="user-name"><?php echo $current_user; ?></span>
                </div>
                <nav class="global-nav">
                    <!-- ページ遷移を今後実装した時のためのヘッダーリンク -->
                    <a href="/home/index" class="nav-link active">ダッシュボード</a>
                    <a href="/auth/logout" class="btn-logout-modern">ログアウト</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container main-wrapper">
        <?php echo (string) $content; ?>
    </div>

    <footer class="global-footer">
        <div class="footer-inner">
            <p>&copy; 2026 TaskFlow. Built for Efficiency.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-latest.js"></script>
    <?php echo \Asset::render('js_footer'); ?>
</body>
</html>
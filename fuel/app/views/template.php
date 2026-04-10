<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | 課題管理アプリ</title>
    <?php echo \Asset::css('style.css'); ?>
</head>
<body>
    <header class="global-header">
        <div class="header-inner">
            <h1>課題管理アプリ</h1>
            <div class="user-info">
                <span>ようこそ、<strong><?php echo $current_user; ?></strong>さん</span>
                <nav>
                    <a href="/home/index">ダッシュボード</a>
                    <a href="/auth/logout" class="btn-logout">ログアウト</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <?php echo $content; ?>
    </div>

    <footer class="global-footer">
        <p>&copy; 2026 課題管理システム</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-latest.js"></script>
    <?php echo \Asset::render('js_footer'); ?>
</body>
</html>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - 課題管理アプリ</title>
    <?php echo \Asset::css('auth.css'); ?>
</head>
<body>
    <div class="auth-container">
        <h2>ログイン</h2>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="/auth/login" method="post">
            <?php echo \Form::csrf(); ?>

            <div class="form-group">
                <label for="username">ユーザー名</label>
                <input type="text" name="username" id="username" required placeholder="ユーザー名を入力">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" name="password" id="password" required placeholder="パスワードを入力">
            </div>

            <button type="submit" class="btn-submit">ログイン</button>
        </form>

        <div class="auth-footer">
            <p>アカウントをお持ちでないですか？</p>
            <a href="/auth/register">新規ユーザー登録はこちら</a>
        </div>
    </div>
</body>
</html>
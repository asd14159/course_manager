<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>新規登録 - 履修管理</title>
    <?php echo \Asset::css('auth.css'); ?>
</head>
<body>
    <div class="auth-container">
        <h1>新規ユーザー登録</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="/auth/register" method="post">
            <div class="form-group">
                <label>ユーザー名</label>
                <input type="text" name="username" required placeholder="例: Kumi Tanaka">
            </div>
            
            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" name="email" required placeholder="example@univ.ac.jp">
            </div>

            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required minlength="8">
            </div>

            <div class="form-group">
                <label>パスワード（確認）</label>
                <input type="password" name="password_confirm" required>
            </div>

            <button type="submit" class="btn-submit">アカウント作成</button>
        </form>

        <div class="auth-footer">
            <a href="/auth/login">既にアカウントをお持ちの方はこちら</a>
        </div>
    </div>
</body>
</html>
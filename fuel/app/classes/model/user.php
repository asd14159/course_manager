<?php

class Model_User extends \Model
{
    /**
     * 新しいユーザーを登録する（クエリビルダー使用）
     * * @param array $data 登録データ ['username', 'password', 'email']
     * @return int 挿入されたレコードのID
     * @throws \Exception DB操作失敗時
     */
    public static function create_user(array $data): int
    {
        // 指摘事項：try-catchのtryブロックは最小限（DB操作のみ）にする
        try {
            // 指摘事項：DBクラスのinsertメソッドを使用（queryメソッドは避ける）
            $result = \DB::insert('users')
                ->set([
                    'username'   => $data['username'],
                    'password'   => \Auth::instance()->hash_password($data['password']), // ※後のステップでAuthクラスを使ってハッシュ化します
                    'email'      => $data['email'],
                    'group'      => 1,
                    'last_login' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])
                ->execute();

            // execute() は [挿入されたID, 影響を受けた行数] を返すので IDを返す
            return (int) $result[0];
            
        } catch (\Database_Exception $e) {
            // エラーをログに記録したり、上位（Seederなど）へ投げ直す
            throw new \Exception("ユーザー登録に失敗しました: " . $e->getMessage());
        }
    }
}
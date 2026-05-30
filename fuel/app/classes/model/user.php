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
        try {
            $insert = [
                'username'       => $data['username'],
                'password'       => \Auth::instance()->hash_password($data['password']),
                'email'          => $data['email'],

                'group'          => 1,
                'last_login'     => 0,
                'login_hash'     => md5(uniqid()),   // ← 仮値でOK
                'profile_fields' => '',

                // 時間
                'created_at'     => time(),
                'updated_at'     => time(),
            ];

            $result = \DB::insert('users')
                ->set($insert)
                ->execute();

            return (int) $result[0];
            
        } catch (\Database_Exception $e) {
            throw new \Exception('ユーザー登録に失敗しました');
        }
    }
}

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
            $result = \DB::insert('users')
                ->set([
                    'username'   => $data['username'],
                    'password'   => \Auth::instance()->hash_password($data['password']),
                    'email'      => $data['email'],
                    'group'      => 1,
                    'last_login' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ])
                ->execute();

            return (int) $result[0];
            
        } catch (\Database_Exception $e) {
            throw new \Exception("ユーザー登録に失敗しました: " . $e->getMessage());
        }
    }
}
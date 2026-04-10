<?php

namespace Fuel\Tasks;

class Seeder
{
    public function run()
    {
        echo "Starting seeder...\n";

        try {
            // 1. テストユーザーの作成
            // すでにユーザーが存在する場合のエラーを避けるため、try-catchの内側で制御
            $user_id = \Model_User::create_user([
                'username' => 'test_student4', // 重複を避けるためタイムスタンプを付与
                'password' => 'password123',
                'email'    => 'student_' . time() . '@example.com',
            ]);

            if ($user_id <= 0) {
                throw new \Exception("Failed to create User.");
            }
            echo "Successfully created User ID: {$user_id}\n";

            // 2. 授業 (Course) の作成
            // Model_Crud を継承しているので forge()->save() が使えます
            $course = \Model_Course::forge([
                'user_id'     => $user_id, // 作成したユーザーに紐付け
                'name'        => 'Webプログラミング演習',
                'day_of_week' => 1, // 月曜日
                'period'      => 3, // 3限
            ]);
            $course->save();
            $course_id = $course->id; // 自動採番されたIDを取得
            echo "Successfully created Course ID: {$course_id}\n";

            // 3. 課題 (Assignment) の作成（複数作ることでソートの確認ができる）
            // 課題A: 優先度High
            \Model_Assignment::forge([
                'course_id'    => $course_id, // 作成した授業に紐付け
                'title'        => 'ポートフォリオ中間報告',
                'description'  => 'GitHubのURLを提出すること',
                'deadline'     => '2026-04-15 23:59:59',
                'priority'     => 1, // High
                'is_completed' => 0,
            ])->save();

            // 課題B: 優先度Middle
            \Model_Assignment::forge([
                'course_id'    => $course_id,
                'title'        => '小テスト復習',
                'description'  => '第1回から第3回までの内容',
                'deadline'     => '2026-04-20 18:00:00',
                'priority'     => 2, // Middle
                'is_completed' => 0,
            ])->save();

            echo "Successfully created Assignments for Course ID: {$course_id}\n";
            echo "--- Seeder Finished Successfully ---\n";

        } catch (\Exception $e) {
            echo "Seeder Error: " . $e->getMessage() . "\n";
        }
    }
}
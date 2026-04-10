document.addEventListener('DOMContentLoaded', function() {
    function HomeViewModel() {
        const self = this; 
        
        // --- 1. 状態管理（Observable） ---
        self.assignments = ko.observableArray([]); 
        self.selectedCourse = ko.observable(null); 
        self.currentSort = ko.observable('deadline'); 
        self.isUpdating = ko.observable(false); 
        self.isModalVisible = ko.observable(false);
        // 1. 変数の定義（self = this; のすぐ下あたり）
        self.editingCourseId = ko.observable(null);

        // 追加フォーム専用のObservable（ここだけにまとめます）
        self.selectedCourseForAdd = ko.observable(""); // ここで1回だけ定義
        self.newTitle = ko.observable('');
        self.newDescription = ko.observable('');
        self.newDeadline = ko.observable('');
        self.newPriority = ko.observable('2');
        self.newCourseName = ko.observable('');
        self.newCourseDay = ko.observable('1');
        self.newCoursePeriod = ko.observable('1');

        // --- 2. 表示用プロパティ（Computed） ---
        self.headerTitle = ko.computed(() => {
            const course = self.selectedCourse();
            return course ? course.name + ' の課題' : 'すべての課題';
        });

        // 2. 編集開始
        self.startEdit = function(id, event) {
            if (event && event.stopPropagation) event.stopPropagation();
                self.editingCourseId(id);
        };

        // 3. 編集保存
        self.saveEdit = function(id, event) {
            if (event && event.stopPropagation) event.stopPropagation();

            // DOMから値を取得
            const name = document.getElementById(`edit-name-${id}`).value;
            const day = document.getElementById(`edit-day-${id}`).value;
            const period = document.getElementById(`edit-period-${id}`).value;

            if (!name) { alert("授業名を入力してください"); return; }

            const params = new URLSearchParams();
            params.append('id', id);
            params.append('name', name);
            params.append('day_of_week', day);
            params.append('period', period);

            fetch('/api/course/update.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    self.editingCourseId(null); // 編集モード終了
                    window.location.reload();   // 再読み込みして反映
                } else {
                    alert("エラー: " + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        };

        // 【修正ポイント】ここで定義！
        self.isNewCourseMode = ko.computed(() => {
            return self.selectedCourseForAdd() === "new";
        });

        // --- 3. 授業選択メソッド ---
        self.selectAllCourses = function() {
            self.loadAssignments(null);
        };
        self.selectCourse = function(id, name) {
            self.loadAssignments(id, name);
        };

        // --- 4. モーダル操作 ---
        self.openModal = function() {
            self.isModalVisible(true);
        };

        self.closeModal = function() {
            self.isModalVisible(false);
            // Observableをリセット
            self.selectedCourseForAdd("");
            self.newTitle('');
            self.newDescription('');
            self.newDeadline('');
            self.newPriority('2');
            self.newCourseName('');
        };

        // --- 5. 並び替えロジック ---
        self.sortAssignments = function() {
            const type = self.currentSort();
            self.assignments.sort((a, b) => {
                const statusA = Number(a.is_completed);
                const statusB = Number(b.is_completed);
                if (statusA !== statusB) return statusA - statusB;

                if (type === 'deadline') {
                    const dateA = a.deadline_formatted || "";
                    const dateB = b.deadline_formatted || "";
                    return dateA.localeCompare(dateB);
                } else if (type === 'priority') {
                    return b.priority - a.priority;
                }
                return 0;
            });
        };
        self.currentSort.subscribe(() => self.sortAssignments());

        // --- 6. API通信 ---

        // 【取得】
        self.loadAssignments = function(courseId, courseName) {
            self.selectedCourse(courseId ? { id: courseId, name: courseName } : null);
            const url = courseId ? `/api/assignment/list/${courseId}.json` : '/api/assignment/all.json';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const formattedData = (Array.isArray(data) ? data : []).map(item => {
                        
                        // ① まず先に Observable（センサー）を作る！
                        const isCompleted = Number(item.is_completed) === 1;
                        item.is_completed_bool = ko.observable(isCompleted);

                        // ② その後で subscribe（監視）を設定する
                        item.is_completed_bool.subscribe((newValue) => {
                            const status = newValue ? 1 : 0;
                            item.is_completed = status; // 数値側も更新
                            self.sortAssignments();     // 即座に並び替え
                            self.toggleComplete(item, status); // 裏で通信
                        });

                        return item;
                    });

                    self.assignments(formattedData);
                    self.sortAssignments(); 
                })
                .catch(error => console.error("Load Error:", error));
            
        };

        self.deleteCourse = function(id, name, event) {
            if (event && event.stopPropagation) event.stopPropagation();

            if (!confirm(`授業「${name}」を削除しますか？`)) return;

            console.log("削除リクエスト送信開始: ID =", id); // デバッグ用

            const params = new URLSearchParams();
            params.append('id', id);

            fetch('/api/course/delete.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(async response => {
                // デバッグポイント：レスポンスの状態を確認
                console.log("ステータスコード:", response.status);
                
                if (!response.ok) {
                    // サーバーがエラー（500など）を返した場合、その中身（PHPのエラー文）を読み出す
                    const errorText = await response.text();
                    throw new Error(`サーバーエラー(${response.status}): ${errorText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("受信データ:", data);
                if (data.status === 'success') {
                    alert("削除完了！");
                    window.location.reload();
                } else {
                    alert("サーバーからの拒否: " + data.message);
                }
            })
            .catch(error => {
                // デバッグポイント：エラーの詳細をアラートで出す
                console.error("詳細エラー:", error);
                alert("デバッグ情報:\n" + error.message);
            });
        };

        // 【追加】
        self.addAssignment = function() {
            if (self.isUpdating()) return;
            
            const courseId = self.selectedCourseForAdd();
            if (!courseId) return alert("授業を選択してください");

            // デバッグ用：何が選ばれているか確認（確認できたら消してOK）
            console.log("選択されているID:", courseId);

            // 修正ポイント：
            // 「空文字」「null」「数字でない文字列（"授業"など）」をすべてブロックする
            if (!courseId || courseId === "" || (courseId !== "new" && isNaN(courseId))) {
                return alert("授業リストから正しい授業を選択してください。");
            }

            const isNewCourse = (courseId === 'new');
            
            if (!self.newTitle() || !self.newDeadline()) return alert("タイトルと期限を入力してください");
            if (isNewCourse && !self.newCourseName()) return alert("新しい授業名を入力してください");

            self.isUpdating(true);

            const params = new URLSearchParams({
                course_id: courseId,
                title: self.newTitle(),
                description: self.newDescription(),
                deadline: self.newDeadline(),
                priority: self.newPriority(),
                new_course_name: isNewCourse ? self.newCourseName() : '',
                new_course_day: isNewCourse ? self.newCourseDay() : '',
                new_course_period: isNewCourse ? self.newCoursePeriod() : ''
            });

            fetch('/api/assignment/create.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            // .then(res => res.json())
            // .then(() => {
            //     window.location.reload(); 
            // })
            // .catch(err => alert("保存に失敗しました。"))

            .then(res => {
                // サーバーから「200 OK」以外が返ってきたらここで止める
                if (!res.ok) {
                    return res.text().then(text => { throw new Error(text) });
                }
                return res.json();
            })
            .then(data => {
                console.log("保存成功:", data);
                alert("保存に成功しました！"); // 確認できたらリロードするように戻す
                window.location.reload(); 
            })
            .catch(err => {
                // ここで何が起きているかアラートに出す
                console.error("保存失敗のログ:", err);
                alert("エラー発生: " + err.message); // これでPHPのエラーメッセージが見えるかも
            // .finally(() => self.isUpdating(false)
            });
        };

        // 【更新】
        // self.toggleComplete = function(assignment, newStatus) {
        //     if (self.isUpdating()) return;
        //     self.isUpdating(true);

        //     assignment.is_completed = newStatus;

        //     fetch('/api/assignment/update_status.json', {
        //         method: 'POST',
        //         headers: { 'Content-Type': 'application/json' },
        //         body: JSON.stringify({ id: assignment.id, is_completed: newStatus })
        //     })
        //     .then(() => {
        //         // 完了したものを下に送るために再ソート
        //         self.sortAssignments();
        //     })
        //     .catch(error => {
        //         alert("通信エラーが発生しました。");
        //         // エラー時は、見た目（チェックボックス）と内部データの両方を元に戻す
        //         const originalStatus = newStatus === 1 ? 0 : 1;
        //         assignment.is_completed = originalStatus;
        //         assignment.is_completed_bool(originalStatus === 1);
        //     })
        //     .finally(() => self.isUpdating(false));
        // };

        self.toggleComplete = function(assignment, newStatus) {
            // UIを止めないために isUpdating(true) は外すか、
            // もしくはチェックボックスの disable 専用にするのがおすすめ
            
            fetch('/api/assignment/update_status.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: assignment.id, is_completed: newStatus })
            })
            .then(response => {
                if (!response.ok) throw new Error();
                // 成功時はすでにソート済みなので何もしなくてOK！
            })
            .catch(error => {
                alert("保存に失敗しました。元の位置に戻します。");
                // 失敗した時だけ「巻き戻し」を行う
                const originalStatus = newStatus === 1 ? 0 : 1;
                assignment.is_completed = originalStatus;
                assignment.is_completed_bool(originalStatus === 1); // ここで自動的に再ソートが走る仕組みにするか、下で呼ぶ
                self.sortAssignments();
            });
        };

        // home.js の中で、他の self.xxx = function... の並びに追加
        self.deleteAssignment = function(assignment) {
            // 1. 誤操作防止の確認
            if (!confirm("「" + assignment.title + "」を削除してもよろしいですか？")) {
                return;
            }

            // 2. 送信データの準備
            const params = new URLSearchParams();
            params.append('id', assignment.id); // assignment.id はDBの主キー

            // 3. サーバーへリクエストを飛ばす
            fetch('/api/assignment/delete.json', { // URLは環境に合わせて調整してください
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
            .then(response => {
                if (!response.ok) throw new Error('削除に失敗しました');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert("削除が完了しました");
                    // 画面をリロードして最新状態にする
                    window.location.reload();
                } else {
                    alert("エラー: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("通信エラーが発生しました");
            });
        };

        // --- 初期化 ---
        self.loadAssignments(null);
    }

    self.deleteCourse = function(id, name) {
        if (!confirm(`授業「${name}」を削除しますか？\n※この授業内の課題もすべて削除されます。`)) {
            return;
        }

        const params = new URLSearchParams();
        params.append('id', id);

        // 送信先を新しく作った course.php の方に変える！
        fetch('/api/course/delete.json', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        // ...あとの処理は課題削除の時と同じ
        .then(response => {
            if (!response.ok) throw new Error('削除に失敗しました');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert("削除が完了しました");
                // 画面をリロードして最新状態にする
                window.location.reload();
            } else {
                alert("エラー: " + data.message);
            }
        })
        .catch(error => {       
            console.error('Error:', error);
            alert("通信エラーが発生しました");
        });
    };

    ko.applyBindings(new HomeViewModel());
});
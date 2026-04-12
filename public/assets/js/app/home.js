document.addEventListener('DOMContentLoaded', function() {
    function HomeViewModel() {
        const self = this; 
        
        // --- 1. 状態管理（Observable） ---
        self.assignments = ko.observableArray([]); 
        self.selectedCourse = ko.observable(null); 
        self.currentSort = ko.observable('deadline'); 
        self.isUpdating = ko.observable(false); 
        self.isModalVisible = ko.observable(false);
        self.editingCourseId = ko.observable(null);
        self.editingAssignment = ko.observable(null);

        // 追加フォームに入力された値を一時的に保管しておく場所
        self.selectedCourseForAdd = ko.observable("");
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

        self.isNewCourseMode = ko.computed(() => {
            return self.selectedCourseForAdd() === "new";
        });

        // --- 3. 内部ロジック（Sorting/Helper) ---
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

        // --- 4. モーダル操作 ---
        self.openModal = function() {
            self.editingAssignment(null);// 編集データをクリア
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

        self.toggleDetail = function(item) {
            item.isVisible(!item.isVisible()); // クリックで開閉を切り替え
        };

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

        // --- 3. 授業選択メソッド ---
        self.selectAllCourses = function() {
            self.loadAssignments(null);
        };
        self.selectCourse = function(id, name) {
            self.loadAssignments(id, name);
        };

        // --- 4. モーダル操作 ---
        self.openModal = function() {
            self.editingAssignment(null);// 編集データをクリア
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


        // --- 6. API通信 ---

        // 【取得】
        self.loadAssignments = function(courseId, courseName) {
            self.assignments([]);
            self.selectedCourse(courseId ? { id: courseId, name: courseName } : null);
            const url = courseId ? `/api/assignment/list/${courseId}.json` : '/api/assignment/all.json';
            
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error("HTTP error");
                    return response.text();
                })

                .then(text => {
                    // もし中身が「空っぽ」なら、JSON.parseせずに空の構造を返す
                    if (!text || text.trim() === "") {
                        console.warn("サーバーからの返却値が空でした");
                        return { status: "success", assignments: [] }; 
                    }
                    
                    // 中身がある時だけ JSON に変換する
                    return JSON.parse(text);
                })

                .then(data => {
                    const targetData = (data && data.assignments) ? data.assignments : [];

                    const formattedData = targetData.map(item => {

                        // 詳細の表示状態を管理するスイッチを初期値 false (閉じている) で作成
                        item.isVisible = ko.observable(false);

                        const now = new Date();
                        const deadline = new Date(item.deadline);
                        const diffHours = (deadline - now) / (1000 * 60 * 60);

                        // 24時間以内かつ期限を過ぎていない
                        item.isUrgent = ko.observable(diffHours > 0 && diffHours <= 24);

                        // 期限を過ぎている（マイナス）
                        item.isOverdue = ko.observable(diffHours <= 0);
                        
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

//         self.loadAssignments = function(courseId, courseName) {
//     console.log("--- デバッグ開始 ---");
//     console.log("選択された授業:", { id: courseId, name: courseName });

//     self.assignments([]);
//     self.selectedCourse(courseId ? { id: courseId, name: courseName } : null);
    
//     const url = courseId ? `/api/assignment/list/${courseId}.json` : '/api/assignment/all.json';
//     console.log("リクエスト先URL:", url);

//     fetch(url)
//         .then(response => {
//             // ステップ1: レスポンスの状態を確認
//             console.log("【1. 通信状態】", {
//                 status: response.status,
//                 statusText: response.statusText,
//                 ok: response.ok,
//                 headers: [...response.headers.entries()] // 全ヘッダーを表示
//             });

//             if (!response.ok) {
//                 throw new Error(`HTTPエラー発生: ${response.status}`);
//             }
//             // 重要：ここで一度「テキスト」として受け取る
//             return response.text(); 
//         })
//         .then(text => {
//             // ステップ2: 届いた生データの中身を確認
//             console.log("【2. 受信データ(生)】", text === "" ? "(空っぽです！)" : text);

//             if (!text || text.trim() === "") {
//                 console.warn("警告: サーバーから中身が返ってきていません。");
//                 return []; 
//             }

//             // ステップ3: JSONパースを試みる
//             try {
//                 const data = JSON.parse(text);
//                 console.log("【3. JSON変換後】", data);
//                 return data;
//             } catch (e) {
//                 console.error("【3. JSON変換エラー】パースに失敗しました。データが壊れている可能性があります。");
//                 throw e;
//             }
//         })
//         .then(data => {
//             // ステップ4: Knockout.js用のデータ整形
//             const targetData = Array.isArray(data) ? data : (data.assignments || []);
//             console.log(`【4. 最終処理】 ${targetData.length} 件の課題を処理します。`);

//             const formattedData = targetData.map(item => {
//                 const isCompleted = Number(item.is_completed) === 1;
//                 item.is_completed_bool = ko.observable(isCompleted);
//                 item.is_completed_bool.subscribe((newValue) => {
//                     const status = newValue ? 1 : 0;
//                     item.is_completed = status;
//                     self.sortAssignments();
//                     self.toggleComplete(item, status);
//                 });
//                 return item;
//             });

//             self.assignments(formattedData);
//             self.sortAssignments(); 
//         })
//         .catch(error => {
//             // すべてのエラーをここで詳細に表示
//             console.error("--- 致命的エラー発生 ---");
//             console.error("エラーの種類:", error.name);
//             console.error("メッセージ:", error.message);
//             console.error("スタックトレース:", error.stack);
//         });
// };

        // --- 課題の編集用メソッド ---

        // 1. 編集開始
        self.editAssignment = function(item) {
            self.isModalVisible(false); // ★重要：新規追加モーダルを強制的に閉じる
            // ko.toJS(item) で「今の値」のコピーを作成してセット
            self.editingAssignment(ko.toJS(item));
            // 必要ならここでモーダルを表示するフラグをtrueにする
        };

        // 2. 編集キャンセル
        self.cancelEditAssignment = function() {
            self.editingAssignment(null);
            self.isModalVisible(false);
        };

        // 3. 編集保存
        self.saveAssignmentUpdate = function() {
            const data = self.editingAssignment(); // 下書きデータ取得

            // デバッグ用：ここで title が本当に入っているか確認！
            console.log("送信直前のデータ:", data);

            if (!data || !data.title) {
                alert("タイトルを入力してください");
                return;
            }
            
            const params = new URLSearchParams();
            params.append('id', data.id);
            params.append('title', data.title);
            params.append('description', data.description);
            params.append('deadline', data.deadline);
            params.append('priority', data.priority);

            fetch('/api/assignment/update.json', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    alert("課題を更新しました");
                    self.editingAssignment(null); // モード終了
                    self.loadAssignments(self.selectedCourse()?.id); // 画面をリフレッシュ
                } else {
                    alert("エラー: " + res.message);
                }
            })
            .catch(err => alert("通信エラーが発生しました"));
        };


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

    ko.applyBindings(new HomeViewModel());
});
document.addEventListener('DOMContentLoaded', function() {
    function HomeViewModel() {
        const self = this; 
        
        // --- 1. 状態管理（Observable） ---
        self.assignments = ko.observableArray([]);
        self.courses = ko.observableArray([]); // 授業一覧を格納する場所 
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

        // --- 2. API通信(GET) ---
        self.loadCourses = async function() {
            try {
                const response = await fetch('/api/course/list.json');
                if (!response.ok) throw new Error("Course Load Error");
                const data = await response.json();

                const mappedCourses = data.courses.map(course => {
                    return {
                        id: course.id,
                        name: ko.observable(course.name || ""),
                        day_of_week: ko.observable(course.day_of_week || "1"),
                        period: ko.observable(course.period || "1")
                    };
                });
                self.courses(mappedCourses);
            } catch (error) {
                console.error("授業の読み込みに失敗:", error);
            }
        };

        self.loadAssignments = function(courseId, courseName) {
            self.assignments([]);
            self.selectedCourse(courseId ? { 
                id: courseId, 
                name: ko.observable(courseName) 
            } : null);
            const url = courseId ? `/api/assignment/list/${courseId}` : '/api/assignment/all.json';
            
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error("HTTP error");
                    return response.text();
                })

                //textに変換したのは授業が課題を持たない状況で[]が返ってくるため
                .then(text => {
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

                        //課題通信中フラグ
                        item.isSending = false;
                        
                        //チェックボックスの判定・サーバーへの通信
                        const isCompleted = Number(item.is_completed) === 1;
                        item.is_completed_bool = ko.observable(isCompleted);
                        item.is_completed_bool.subscribe((newValue) => {
                            const status = newValue ? 1 : 0;
                            item.is_completed = status;
                            self.sortAssignments();
                            self.toggleComplete(item, status); 
                        });
                        return item;
                    });

                    self.assignments(formattedData);
                    self.sortAssignments(); 
                })
                .catch(error => console.error("Load Error:", error));
        };

        //すべての授業を表示を選択
        self.selectAllCourses = function() {
            self.loadAssignments(null);
        };

        //特定の授業を選択
        self.selectCourse = function(id, name) {
            self.loadAssignments(id, name);
        };

        // --- 3.API通信(POST/DELETE) ---
        //課題の操作
        self.addAssignment = async function() {
            // 二重送信の防止
            if (self.isUpdating()) return;

            const courseId = self.selectedCourseForAdd();
            const isNewCourse = (courseId === 'new');
            const isValidId = courseId && !isNaN(parseFloat(courseId)) && isFinite(courseId);

            // バリデーション
            if (!isNewCourse && !isValidId) return alert("授業を選択してください");
            if (!self.newTitle() || !self.newDeadline()) return alert("タイトルと期限を入力してください");
            if (isNewCourse && !self.newCourseName()) return alert("新しい授業名を入力してください");

            try {
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

                const response = await fetch('/api/assignment/create.json', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(errorText);
                }

                const data = await response.json();

                alert("保存に成功しました！");
                window.location.reload();

            } catch (err) {
                console.error("保存失敗のログ:", err);
                alert("エラー発生: " + err.message);

            } finally {
                self.isUpdating(false);
            }
        };

        self.saveAssignmentUpdate = async function() {
            if (self.isUpdating()) return;

            const data = self.editingAssignment(); 

            if (!data || !data.title) {
                return alert("タイトルを入力してください");
            }

            try {
                self.isUpdating(true);

                const params = new URLSearchParams({
                    id: data.id,
                    title: data.title,
                    description: data.description || '',
                    deadline: data.deadline,
                    priority: data.priority
                });

                const response = await fetch('/api/assignment/update.json', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(errorText || "サーバーエラーが発生しました");
                }

                const result = await response.json();

                if (result.status === 'success') {
                    alert("課題を更新しました");
                    self.editingAssignment(null);
                    const currentCourseId = self.selectedCourse() ? self.selectedCourse().id : null;
                    self.loadAssignments(currentCourseId);
                } else {
                    throw new Error(result.message || "更新に失敗しました");
                }

            } catch (err) {
                console.error("更新失敗:", err);
                alert("エラー: " + err.message);

            } finally {
                self.isUpdating(false);
            }
        };

        self.toggleComplete = async function(assignment, newStatus) {
            if (assignment.isSending) return; 
            
            const originalStatus = newStatus === 1 ? 0 : 1; // 失敗した時のためのバックアップ
            
            self.sortAssignments(); 

            try {
                assignment.isSending = true;

                const response = await fetch('/api/assignment/update_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: assignment.id, is_completed: newStatus })
                });

                if (!response.ok) throw new Error("Status Update Failed");

            } catch (error) {
                alert("通信エラーのため、変更を保存できませんでした。");
                
                // ロールバック処理
                assignment.is_completed = originalStatus;
                assignment.is_completed_bool(originalStatus === 1); 
                self.sortAssignments();
            } finally {
                assignment.isSending = false;
            }
        };

        self.deleteAssignment = async function(assignment) {
            if (!confirm(`「${assignment.title}」を削除してもよろしいですか？`)) return;

            if (self.isUpdating()) return;

            try {
                self.isUpdating(true);

                const params = new URLSearchParams({ id: assignment.id });

                const response = await fetch('/api/assignment/delete.json', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                if (!response.ok) throw new Error("サーバー通信に失敗しました");

                const result = await response.json();

                if (result.status === 'success') {
                    alert("削除しました");
                    self.assignments.remove(assignment); 
                } else {
                    throw new Error(result.message || "削除に失敗しました");
                }
            } catch (err) {
                console.error("削除エラー:", err);
                alert("エラー: " + err.message);
            } finally {
                self.isUpdating(false);
            }
        };


        self.saveEdit = async function(course, event) {
            if (event && event.stopPropagation) event.stopPropagation();
            if (self.isUpdating()) return;

            if (!course.name()) return alert("授業名を入力してください");

            try {
                self.isUpdating(true);

                const params = new URLSearchParams({
                    id: course.id,
                    name: course.name(),
                    day_of_week: course.day_of_week(),
                    period: course.period()
                });

                const response = await fetch('/api/course/update.json', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });

                if (!response.ok) throw new Error("通信に失敗しました");
                const data = await response.json();

                if (data.status === 'success') {
                    //ここ未だ未修正
                    course.name(newName);
                    const selected = self.selectedCourse();
                    if (selected && selected.id == course.id) {
                        if (typeof selected.name === 'function') {
                            selected.name(newName);
                        }
                    }
                    alert("更新しました");
                    self.editingCourseId(null);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                alert("エラー: " + error.message);
            } finally {
                self.isUpdating(false);
            }
        };

        self.deleteCourse = async function(course) {
                const courseName = ko.unwrap(course.name);
                if (!confirm(`授業「${courseName}」を削除しますか？`)) return;

                try {
                    const params = new URLSearchParams();
                    params.append('id', course.id);

                    const response = await fetch('/api/course/delete.json', { 
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: params
                    });

                    if (!response.ok) throw new Error('削除に失敗しました');
                    const data = await response.json();

                    if (data.status === 'success') {
                        await self.loadCourses();
                        self.loadAssignments(null); 
                        alert("削除が完了しました");
                    } else {
                        alert("エラー: " + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert("通信エラーが発生しました");
                }
        };

        // --- 4. 表示用プロパティ（Computed） ---
        self.headerTitle = ko.computed(() => {
            const course = self.selectedCourse();
            if (!course) return 'すべての課題';

            // name が Observable かどうかを判定して値を取り出す
            const name = (typeof course.name === 'function') ? course.name() : course.name;
            return name + ' の課題';
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

        // 曜日IDを日本語名に変換するヘルパー
        self.getDayName = function(dayId) {
            // 引数 dayId が Observable の場合は自動で値を取り出す
            const id = typeof dayId === 'function' ? dayId() : dayId;
            
            const dayNames = {
                "1": "月",
                "2": "火",
                "3": "水",
                "4": "木",
                "5": "金",
                "6": "土",
                "0": "日"
            };
            return dayNames[id] || "？";
        };

        //監視設定
        self.currentSort.subscribe(() => self.sortAssignments());

        // --- 5. UI操作 ---
        self.openModal = function() {
            self.editingAssignment(null);// 編集データをクリア
            self.isModalVisible(true);
        };

        self.closeModal = function() {
            self.isModalVisible(false);
            self.selectedCourseForAdd("");
            self.newTitle('');
            self.newDescription('');
            self.newDeadline('');
            self.newPriority('2');
            self.newCourseName('');
        };

        //詳細のトグル
        self.toggleDetail = function(item) {
            item.isVisible(!item.isVisible()); // クリックで開閉を切り替え
        };

        //授業の編集開始
        self.startEdit = function(id, event) {
            if (event && event.stopPropagation) event.stopPropagation();
                self.editingCourseId(id);
        };

        //課題の編集開始
        self.editAssignment = function(item) {
            self.isModalVisible(false); //新規追加モーダルを強制的に閉じる
            self.editingAssignment(ko.toJS(item));
        };

        // 課題の編集キャンセル
        self.cancelEditAssignment = function() {
            self.editingAssignment(null);
            self.isModalVisible(false);
        };
        
        // --- 6.初期化 ---
        self.init = async function() {
            try {
                await self.loadCourses();
                self.loadAssignments(null);
            } catch (error) {
                console.error("初期化中にエラーが発生しました:", error);
            }
        };

        self.init();
    }

    ko.applyBindings(new HomeViewModel());
});
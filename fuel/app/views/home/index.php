<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>授業一覧</h3>
        </div>
        <?php 
            $day_names = ['日', '月', '火', '水', '木', '金', '土'];
        ?>

        <nav class="course-nav">
             <ul data-bind="foreach: courses">
                <li class="course-item" data-bind="css: { active: $root.selectedCourse() && $root.selectedCourse().id == id }">
                    <div class="course-item-inner">
                        <div class="course-view-mode" data-bind="visible: $root.editingCourseId() != id">
                            <a href="#" class="course-info" data-bind="click: function() { $root.selectCourse(id, name()) }">
                                <div class="course-name" data-bind="text: name"></div>
                                <div class="course-meta">
                                    <span class="course-day" data-bind="text: $root.getDayName(day_of_week()) + '曜日'"></span>
                                    <span class="course-period" data-bind="text: period() + '限'"></span>
                                </div>
                            </a>
                            <button class="course-edit-btn" data-bind="click: function(d, e) { $root.startEdit(id, e) }">✏️</button>
                            <button class="course-delete-btn" data-bind="click: function() { $root.deleteCourse($data) }">🗑️</button>
                        </div> <div class="course-edit-form" data-bind="visible: $root.editingCourseId() == id">
                            <input type="text" class="form-control" data-bind="value: name"> 
                            <div class="select-group">
                                <select data-bind="value: day_of_week">
                                    <option value="1">月曜日</option>
                                    <option value="2">火曜日</option>
                                    <option value="3">水曜日</option>
                                    <option value="4">木曜日</option>
                                    <option value="5">金曜日</option>
                                    <option value="6">土曜日</option>
                                </select>
                                <select data-bind="value: period">
                                    <option value="1">1限</option>
                                    </select>
                            </div>
                            <div class="edit-actions">
                                <button class="save-btn" data-bind="click: $root.saveEdit">保存</button>
                                <button class="cancel-btn" data-bind="click: function() { $root.editingCourseId(null) }">中止</button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <h2><span data-bind="text: headerTitle"></span></h2>
            </div>
            <div class="header-right">
                <div class="filter-group">
                    <span class="filter-icon">🔃</span>
                    <select class="modern-select" data-bind="value: currentSort">
                        <option value="deadline">期限が近い順</option>
                        <option value="priority">優先度が高い順</option>
                    </select>
                </div>
                
                <button class="btn-modern-primary" data-bind="click: openModal">
                    <span class="plus-icon">+</span> 課題を追加
                </button>
            </div>
        </header>

        <div class="assignment-container">
            <p class="empty-state" data-bind="visible: assignments().length === 0">表示する課題はありません。</p>
            <table class="assignment-table" data-bind="visible: assignments().length > 0">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>課題名</th>
                        <th data-bind="visible: !selectedCourse()">授業名</th>
                        <th>期限</th>
                        <th>優先度</th>
                    </tr>
                </thead>
                <tbody data-bind="foreach: assignments">
                    <tr class="assignment-row" data-bind="
                        click: $parent.toggleDetail, 
                        css: { 
                            'is-completed': is_completed_bool, 
                            'is-expanded': isVisible,
                            'is-urgent': ($data.isUrgent && isUrgent()) && !is_completed_bool(),
                            'is-overdue': isOverdue() && !is_completed_bool()
                        }
                    ">
                        <td class="col-status" data-bind="click: function(d, e) { e.stopPropagation(); return true; }">
                            <input type="checkbox" data-bind="checked: is_completed_bool, disable: isSending">
                        </td>
                        <td class="col-title" data-bind="text: title"></td>
                        <td data-bind="visible: !$parent.selectedCourse(), text: course_name || '不明'"></td>
                        <td class="col-deadline" data-bind="text: deadline_formatted"></td>
                        <td class="col-priority" data-bind="text: 'Lv.' + priority"></td>
                        <td class="col-action">
                            <div class="action-wrapper">
                                <button class="icon-btn edit-btn" title="編集" data-bind="click: $parent.editAssignment">
                                    <span>✏️</span>
                                </button>
                                
                                <button class="icon-btn delete-btn" title="削除" data-bind="click: $parent.deleteAssignment">
                                    <span>🗑️</span>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr class="assignment-detail-row" data-bind="
                        visible: isVisible, 
                        css: { 'is-completed': is_completed_bool } 
                    ">
                        <td colspan="6">
                            <div class="detail-container">
                                <h4 class="detail-label">詳細内容</h4>
                                <div class="detail-content" data-bind="text: description"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

    
            <div class="edit-modal-overlay" data-bind="visible: editingAssignment" style="display: none;">
                <div class="edit-modal-content" data-bind="with: editingAssignment">
                    <h3>課題の編集</h3>
                    
                    <div class="form-group">
                        <label>課題名</label>
                        <input type="text" data-bind="value: title" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>詳細内容（メモ）</label>
                        <textarea data-bind="value: description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>期限</label>
                        <input type="datetime-local" data-bind="value: deadline" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>優先度</label>
                        <select data-bind="value: priority" class="form-control">
                            <option value="1">Lv.1 (低)</option>
                            <option value="2">Lv.2 (中)</option>
                            <option value="3">Lv.3 (高)</option>
                        </select>
                    </div>

                    <div class="button-group">
                        <button class="btn btn-primary" data-bind="click: $root.saveAssignmentUpdate">保存する</button>
                        <button class="btn btn-secondary" data-bind="click: function() { $root.editingAssignment(null); }">キャンセル</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="add-modal" class="modal-overlay" data-bind="visible: isModalVisible" style="display: none;">
    <div class="modal-content">
        <header class="modal-header">
            <h3>新しい課題を追加</h3>
        </header>
        
        <form data-bind="submit: addAssignment">
            <div class="modal-body">
                <div class="form-group">
                    <label for="reg-course-id">授業名</label>
                    <select id="reg-course-id" name="course_id" required class="form-control" 
                            data-bind="value: selectedCourseForAdd">
                        <option value="" data-bind="value: ''">-- 授業を選択 --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course->id; ?>">
                                <?php echo htmlspecialchars($course->name, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new">+ 新しい授業を追加</option>
                    </select>
                </div>

                <div class="new-course-fields" data-bind="visible: isNewCourseMode">
                    <div class="form-group">
                        <label for="reg-new-course-name">新しい授業の名前</label>
                        <input type="text" id="reg-new-course-name" name="new_course_name" class="form-control" 
                               placeholder="例: システム設計" 
                               data-bind="value: newCourseName, attr: { required: isNewCourseMode }">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reg-new-course-day">開講曜日</label>
                            <select id="reg-new-course-day" name="new_course_day" class="form-control" data-bind="value: newCourseDay">
                                <option value="1">月曜日</option>
                                <option value="2">火曜日</option>
                                <option value="3">水曜日</option>
                                <option value="4">木曜日</option>
                                <option value="5">金曜日</option>
                                <option value="6">土曜日</option>
                                <option value="0">日曜日</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reg-new-course-period">時限 (1-7)</label>
                            <select id="reg-new-course-period" name="new_course_period" class="form-control" data-bind="value: newCoursePeriod">
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?>限</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reg-new-title">課題タイトル</label>
                    <input type="text" id="reg-new-title" name="title" class="form-control" 
                           placeholder="例: レポート提出" required maxlength="50" data-bind="value: newTitle">
                </div>

                <div class="form-group">
                    <label for="reg-new-description">詳細説明（任意）</label>
                    <textarea id="reg-new-description" name="description" class="form-control" rows="3" 
                              placeholder="課題のメモや詳細" data-bind="value: newDescription"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="reg-new-deadline">期限</label>
                        <input type="datetime-local" id="reg-new-deadline" name="deadline" class="form-control" 
                               required data-bind="value: newDeadline">
                    </div>

                    <div class="form-group">
                        <label for="reg-new-priority">優先度</label>
                        <select id="reg-new-priority" name="priority" class="form-control" data-bind="value: newPriority">
                            <option value="1">Lv.1 (低)</option>
                            <option value="2">Lv.2 (中)</option>
                            <option value="3">Lv.3 (高)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-bind="click: closeModal">キャンセル</button>
                <button type="submit" class="btn-save" data-bind="disable: isUpdating">
                    <span data-bind="text: isUpdating() ? '保存中...' : '保存する'"></span>
                </button>
            </div>
        </form> 
        </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.1/knockout-latest.js"></script>

<?php echo \Asset::js('app/home.js'); ?>
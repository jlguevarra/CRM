<?php
session_start();
include 'config.php';
include 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch user role and name
$user_id = $_SESSION['user_id'];
$user = getUserDetails($user_id);
$role = $user['role'];
$user_name = $user['name'];

// Handle ALL form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $is_error = false;
    if (isset($_POST['create_task'])) {
        $task_data = [
            'title' => $_POST['title'], 'description' => $_POST['description'],
            'due_date' => $_POST['due_date'], 'priority' => $_POST['priority'],
            'status' => 'pending', 'assigned_to' => $_POST['assigned_to'],
            'created_by' => $user_id
        ];
        if (createTask($task_data)) {
            $message = "Task created and assigned successfully!";
        } else {
            $message = "Failed to create task."; $is_error = true;
        }
    }
    if (isset($_POST['update_task'])) {
        $task_data = [
            'task_id' => $_POST['task_id'], 'title' => $_POST['title'],
            'description' => $_POST['description'], 'due_date' => $_POST['due_date'],
            'priority' => $_POST['priority'], 'assigned_to' => $_POST['assigned_to']
        ];
        if (updateTask($task_data, true)) { // Pass true for admin edit
            $message = "Task updated successfully!";
        } else {
            $message = "Failed to update task."; $is_error = true;
        }
    }
    if (isset($_POST['delete_task'])) {
        if (deleteTask($_POST['task_id'])) {
            $message = "Task deleted successfully!";
        } else {
            $message = "Failed to delete task."; $is_error = true;
        }
    }
    if (isset($_POST['update_task_status'])) {
        if (updateTaskStatus($_POST['task_id'] ?? 0, $_POST['status'] ?? 'pending')) {
            $message = "Task status updated!";
        } else {
            $message = "Failed to update status."; $is_error = true;
        }
    }
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $is_error ? 'error' : 'success'];
    header("Location: task.php");
    exit();
}

if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$tasks = getTasks();
$assignable_users = [];
$user_result = $conn->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY name ASC");
if ($user_result) {
    while ($row = $user_result->fetch_assoc()) {
        $assignable_users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - CRM System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header { background: white; padding: 18px 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 24px; color: #333; }
        .header-actions { display: flex; align-items: center; gap: 15px; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            transition: opacity 0.5s ease;
        }
        .alert.hiding {
            opacity: 0;
        }
        .alert-success { background-color: #e6f4e6; color: #27ae60; }
        .alert-error { background-color: #ffecec; color: #dc2626; }
        .card { background: white; padding: 25px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { margin: 0; font-size: 20px; }
        .btn { padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
        .btn-primary { background-color: var(--primary); color: white; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-item { display: flex; flex-direction: column; }
        .filter-item label { margin-bottom: 5px; font-size: 12px; color: #555; }
        .filter-item select { padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
        .task-list { list-style: none; padding: 0; }
        .task-item { display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-bottom: 1px solid #eee; }
        .task-item:last-child { border-bottom: none; }
        .task-content { flex-grow: 1; }
        .task-title { font-weight: 600; color: #333; }
        .task-description { font-size: 14px; color: #777; margin: 4px 0; }
        .task-meta { display: flex; align-items: center; gap: 15px; font-size: 12px; color: #777; margin-top: 8px; flex-wrap: wrap; }
        .task-meta > div { display: flex; align-items: center; gap: 5px; }
        .task-priority, .task-status { padding: 3px 8px; border-radius: 12px; font-weight: 500; }
        .priority-high { background: #ffecec; color: var(--danger); }
        .priority-medium { background: #fff4e6; color: var(--warning); }
        .priority-low { background: #e6f4ff; color: var(--primary); }
        .status-pending { background-color: #f1f5f9; color: #555; }
        .status-in-progress { background-color: #e0f2fe; color: #0284c7; }
        .status-completed { background-color: #dcfce7; color: #16a34a; }
        .task-actions { display: flex; gap: 10px; align-items: center; }
        .task-actions button, .task-actions .btn { background: none; border: none; color: #999; cursor: pointer; font-size: 14px; padding: 5px; }
        .task-actions .btn { background: #f1f5f9; color: #333; border-radius: 6px; font-size: 12px; padding: 6px 10px; }
        .empty-state { text-align: center; padding: 50px; color: #999; }
        .empty-state i { font-size: 40px; margin-bottom: 15px; }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fefefe; margin: auto; padding: 25px; border-radius: var(--border-radius); width: 90%; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <h2>Task Management</h2>
            <div class="header-actions">
                 <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user_name); ?></div>
                        <div style="font-size: 12px; color: var(--secondary);"><?php echo ucfirst($role); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($flash_message)): ?>
        <div class="alert alert-<?php echo $flash_message['type']; ?>" id="flashMessage">
            <i class="fas fa-check-circle"></i> <?php echo $flash_message['text']; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo ($role === 'admin') ? 'All Tasks' : 'My Tasks'; ?></h2>
                <?php if ($role === 'admin') : ?>
                    <button class="btn btn-primary" id="addTaskBtn"><i class="fas fa-plus"></i> Add New Task</button>
                <?php endif; ?>
            </div>
            
            <div class="filters">
                </div>
            
            <ul class="task-list" id="taskList">
                <?php if (!empty($tasks)): foreach ($tasks as $task): ?>
                <li class="task-item" data-id="<?php echo $task['id']; ?>">
                    <div class="task-content">
                        <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                        <div class="task-description"><?php echo htmlspecialchars($task['description'] ?? ''); ?></div>
                        <div class="task-meta">
                            <div><i class="fas fa-calendar-alt"></i> <span><?php echo date("M j, Y", strtotime($task['due_date'])); ?></span></div>
                            <div class="task-priority priority-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?></div>
                            <div class="task-status status-<?php echo $task['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?></div>
                            <?php if ($role === 'admin'): ?>
                            <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($task['assigned_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="task-actions">
                         <?php if ($role === 'user' && $task['status'] !== 'completed'): ?>
                            <?php if ($task['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="update_task_status" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="status" value="in-progress">
                                <button type="submit" class="btn">Start Task</button>
                            </form>
                            <?php endif; ?>
                             <form method="POST" style="display:inline;">
                                <input type="hidden" name="update_task_status" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn">Complete</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($role === 'admin' && $task['status'] !== 'completed') : ?>
                            <button title="Edit" onclick="editTask(this)" data-task='<?= htmlspecialchars(json_encode($task), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-edit"></i></button>
                            <form method="POST" onsubmit="return confirm('Delete this task?');" style="display:inline;">
                                <input type="hidden" name="delete_task" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-square"></i>
                    <p>No tasks found.</p>
                </div>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <div class="modal" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Task</h2>
                <button class="close">&times;</button>
            </div>
            <form method="POST" id="taskForm">
                <input type="hidden" name="create_task" value="1" id="formAction">
                <input type="hidden" name="task_id" id="taskId">
                <div class="form-group">
                    <label for="taskTitle">Title *</label>
                    <input type="text" id="taskTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="taskDescription">Description</label>
                    <textarea id="taskDescription" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="taskDueDate">Due Date *</label>
                    <input type="date" id="taskDueDate" name="due_date" required>
                </div>
                <div class="form-group">
                    <label for="taskPriority">Priority *</label>
                    <select id="taskPriority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group" id="status-group" style="display: none;">
                    <label for="taskStatus">Status</label>
                    <input type="hidden" id="taskStatusHidden" name="status">
                    <select id="taskStatus" disabled>
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskAssignedTo">Assign To (User Only) *</label>
                    <select id="taskAssignedTo" name="assigned_to" required>
                        <option value="">Select a user</option>
                        <?php foreach ($assignable_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" id="cancelTask">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-dismiss flash message after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessage = document.getElementById('flashMessage');
            
            if (flashMessage) {
                setTimeout(function() {
                    flashMessage.classList.add('hiding');
                    setTimeout(function() {
                        flashMessage.remove();
                    }, 500); // Wait for fade-out animation to complete
                }, 4000); // 4 seconds
            }
        });

        const addTaskBtn = document.getElementById('addTaskBtn');
        const taskModal = document.getElementById('taskModal');
        const modalTitle = document.getElementById('modalTitle');
        const taskForm = document.getElementById('taskForm');
        const formAction = document.getElementById('formAction');
        const cancelBtn = document.getElementById('cancelTask');
        const closeModalBtn = taskModal.querySelector('.close');
        const statusGroup = document.getElementById('status-group');
        const taskDueDateInput = document.getElementById('taskDueDate');
        
        const today = new Date().toISOString().split('T')[0];
        
        function openModal() { taskModal.style.display = 'flex'; }
        function closeModal() { taskModal.style.display = 'none'; }

        if (addTaskBtn) {
            addTaskBtn.addEventListener('click', () => {
                modalTitle.textContent = 'Add New Task';
                taskForm.reset();
                formAction.name = 'create_task';
                document.getElementById('taskId').value = '';
                statusGroup.style.display = 'none';
                taskDueDateInput.setAttribute('min', today); // Set min date for new tasks
                openModal();
            });
        }
        
        function editTask(button) {
            const task = JSON.parse(button.getAttribute('data-task'));
            modalTitle.textContent = 'Edit Task';
            taskForm.reset();
            formAction.name = 'update_task';
            statusGroup.style.display = 'block';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description;
            document.getElementById('taskDueDate').value = task.due_date;
            taskDueDateInput.setAttribute('min', today); // MODIFIED: Set min date for edit form as well
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskAssignedTo').value = task.assigned_to;
            document.getElementById('taskStatus').value = task.status;
            document.getElementById('taskStatusHidden').value = task.status;
            openModal();
        }

        cancelBtn.addEventListener('click', closeModal);
        closeModalBtn.addEventListener('click', closeModal);
        window.addEventListener('click', (e) => { if (e.target === taskModal) closeModal(); });
    </script>
</body>
</html>
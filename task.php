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

// Get tasks based on role
$tasks = getTasks(); // Already filters by role inside the function

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Create Task
    if (isset($_POST['create_task'])) {
        $task_data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'due_date' => $_POST['due_date'],
            'priority' => $_POST['priority'],
            'status' => $_POST['status'],
            'assigned_to' => $_POST['assigned_to'],
            'created_by' => $user_id
        ];

        if (createTask($task_data)) {
            $task_id = $conn->insert_id;

            // 🔔 Insert notification for assigned user
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
            $title = "New Task Assigned";
            $message = "You've been assigned a new task: " . $task_data['title'];
            $type = "task";
            $related_type = "task";
            $stmt->bind_param("issssi", $task_data['assigned_to'], $title, $message, $type, $related_type, $task_id);
            $stmt->execute();

            $success_message = "Task created successfully!";
            $tasks = getTasks(); // Refresh
        } else {
            $error_message = "Failed to create task. Please try again.";
        }
    }

    // ✅ Update Task
    if (isset($_POST['update_task'])) {
        $task_data = [
            'task_id' => $_POST['task_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'due_date' => $_POST['due_date'],
            'priority' => $_POST['priority'],
            'status' => $_POST['status'],
            'assigned_to' => $_POST['assigned_to']
        ];

        if (updateTask($task_data)) {
            $success_message = "Task updated successfully!";
            $tasks = getTasks();
        } else {
            $error_message = "Failed to update task. Please try again.";
        }
    }

    // ✅ Delete Task
    if (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];

        if (deleteTask($task_id)) {
            $success_message = "Task deleted successfully!";
            $tasks = getTasks();
        } else {
            $error_message = "Failed to delete task. Please try again.";
        }
    }

    // ✅ Update Task Status
    if (isset($_POST['update_task_status'])) {
        $task_id = $_POST['task_id'];
        $status = $_POST['status'];

        if (updateTaskStatus($task_id, $status)) {
            $tasks = getTasks();
        }
    }
}

// ✅ Get users for assignment dropdown
$users = [];
$user_result = $conn->query("SELECT id, name FROM users");
if ($user_result->num_rows > 0) {
    while ($row = $user_result->fetch_assoc()) {
        $users[] = $row;
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
    <link rel="stylesheet" href="task/task.css">
   
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>CRM</h2>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="customers.php"><i class="fas fa-users"></i> <span>Customers</span></a>
        <?php if ($role === 'admin') : ?>
            <a href="users.php"><i class="fas fa-user-cog"></i> <span>Users</span></a>
            <a href="reports.php"><i class="fas fa-chart-pie"></i> <span>Reports</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <?php endif; ?>
        <a href="tasks.php" class="active"><i class="fas fa-tasks"></i> <span>Tasks</span></a>
       
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <button class ="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header">
            <h2>Task Management</h2>
            <div class="header-actions">
                <div class="notification" id="notificationBell">
                <i class="fas fa-bell"></i>
                <span class="badge" id="notificationCount">0</span>
            <div class="notification-dropdown" id="notificationDropdown"></div>
</div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user_name); ?></div>
                        <div style="font-size: 12px; color: var(--secondary);"><?php echo ucfirst($role); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
                            
                            <div class="card">
                            <div class="card-header">
                        <h2>
                            <?php 
                            if ($role === 'admin') {
                                echo 'All Tasks';
                            } else {
                                echo 'My Tasks';
                            }
                            ?>
                        </h2>
                        <?php if ($role === 'admin') : ?>
                            <button class="btn btn-primary" id="addTaskBtn"><i class="fas fa-plus"></i> Add New Task</button>
                        <?php endif; ?>
                    </div>
            
            <div class="filters">
                <div class="filter-item">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="priorityFilter">Priority</label>
                    <select id="priorityFilter">
                        <option value="all">All Priorities</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="dueDateFilter">Due Date</label>
                    <input type="date" id="dueDateFilter">
                </div>
            </div>
            
            <ul class="task-list" id="taskList">
                <?php if (!empty($tasks)): ?>
                    <?php foreach ($tasks as $task): ?>
                    <li class="task-item" data-id="<?php echo $task['id']; ?>">
                        <form method="POST" class="task-status-form">
                            <input type="hidden" name="update_task_status" value="1">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <input type="checkbox" class="task-checkbox" name="status" value="completed" <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?> onchange="this.form.submit()">
                        </form>
                        <div class="task-content">
                            <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div class="task-description"><?php echo htmlspecialchars($task['description'] ?? ''); ?></div>
                            <div class="task-meta">
                                <div>Due: <span class="due-date"><?php echo $task['due_date']; ?></span></div>
                                <div class="task-priority priority-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?> Priority</div>
                                <div class="task-status status-<?php echo $task['status']; ?>"><?php echo ucfirst(str_replace('-', ' ', $task['status'])); ?></div>
                                <div>Assigned to: <?php echo htmlspecialchars($task['assigned_name']); ?></div>
                            </div>
                        </div>
                       <div class="task-actions">
                        <?php if ($role === 'admin' || $task['created_by'] == $user_id) : ?>
                            <button title="Edit" onclick="editTask(<?php echo $task['id']; ?>)"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_task" value="1">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <button type="submit" title="Delete" onclick="return confirm('Are you sure you want to delete this task?')"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php else : ?>
                            <span class="view-only">View Only</span>
                        <?php endif; ?>
                    </div>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <p>No tasks found. Create your first task to get started.</p>
                    </div>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <!-- Add/Edit Task Modal -->
    <div class="modal" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Task</h2>
                <button class="close">&times;</button>
            </div>
            <form method="POST" id="taskForm">
                <input type="hidden" name="create_task" value="1" id="formType">
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
                
                <div class="form-group">
                    <label for="taskStatus">Status *</label>
                    <select id="taskStatus" name="status" required>
                        <option value="pending" selected>Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="taskAssignedTo">Assign To *</label>
                    <select id="taskAssignedTo" name="assigned_to" required>
                        <option value="">Select a user</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
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
        // DOM Elements
        const addTaskBtn = document.getElementById('addTaskBtn');
        const taskModal = document.getElementById('taskModal');
        const modalTitle = document.getElementById('modalTitle');
        const taskForm = document.getElementById('taskForm');
        const formType = document.getElementById('formType');
        const taskId = document.getElementById('taskId');
        const cancelTask = document.getElementById('cancelTask');
        const closeModal = document.querySelector('.close');
        const taskList = document.getElementById('taskList');
        
        // Open modal for adding new task
        addTaskBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Add New Task';
            taskForm.reset();
            formType.value = 'create_task';
            formType.name = 'create_task';
            taskModal.style.display = 'flex';
        });
        
        // Close modal
        const closeModalFunc = () => {
            taskModal.style.display = 'none';
        };
        
        closeModal.addEventListener('click', closeModalFunc);
        cancelTask.addEventListener('click', closeModalFunc);
        
        // Edit task function
        function editTask(taskId) {
            // In a real application, you would fetch task details from the server
            // For this example, we'll use the tasks data from PHP
            const tasks = <?php echo json_encode($tasks); ?>;
            const task = tasks.find(t => t.id == taskId);
            
            if (task) {
                modalTitle.textContent = 'Edit Task';
                document.getElementById('taskTitle').value = task.title;
                document.getElementById('taskDescription').value = task.description || '';
                document.getElementById('taskDueDate').value = task.due_date;
                document.getElementById('taskPriority').value = task.priority;
                document.getElementById('taskStatus').value = task.status;
                document.getElementById('taskAssignedTo').value = task.assigned_to;
                document.getElementById('taskId').value = task.id;
                
                // Change form to update mode
                formType.value = 'update_task';
                formType.name = 'update_task';
                
                taskModal.style.display = 'flex';
            }
        }
        
        // Filter functionality
        const statusFilter = document.getElementById('statusFilter');
        const priorityFilter = document.getElementById('priorityFilter');
        const dueDateFilter = document.getElementById('dueDateFilter');
        
        const filterTasks = () => {
            const statusValue = statusFilter.value;
            const priorityValue = priorityFilter.value;
            const dueDateValue = dueDateFilter.value;
            
            const tasks = document.querySelectorAll('.task-item');
            
            tasks.forEach(task => {
                let show = true;
                const taskStatus = task.querySelector('.task-status').textContent.toLowerCase().replace(' ', '-');
                const taskPriority = task.querySelector('.task-priority').textContent.toLowerCase().replace(' priority', '');
                const taskDueDate = task.querySelector('.due-date').textContent;
                
                if (statusValue !== 'all' && taskStatus !== statusValue) {
                    show = false;
                }
                
                if (priorityValue !== 'all' && taskPriority !== priorityValue) {
                    show = false;
                }
                
                if (dueDateValue && taskDueDate !== dueDateValue) {
                    show = false;
                }
                
                task.style.display = show ? 'flex' : 'none';
            });
        };
        
        statusFilter.addEventListener('change', filterTasks);
        priorityFilter.addEventListener('change', filterTasks);
        dueDateFilter.addEventListener('change', filterTasks);
        
        // Close modal if clicked outside
        window.addEventListener('click', (e) => {
            if (e.target === taskModal) {
                closeModalFunc();
            }
        });
        
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

    // Toggle notification dropdown
const bell = document.getElementById('notificationBell');
const dropdown = document.getElementById('notificationDropdown');

bell.addEventListener('click', () => {
    dropdown.classList.toggle('active');

    // kapag binuksan, mark as read
    if (dropdown.classList.contains('active')) {
        fetch('mark_read.php')
            .then(() => {
                document.getElementById('notificationCount').style.display = 'none';
            });
    }
});

// Load notifications list
function loadNotifications() {
    fetch('notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notificationCount');
            const dropdown = document.getElementById('notificationDropdown');

            // Update badge
            const unread = data.filter(n => n.is_read == 0).length;
            badge.textContent = unread;
            badge.style.display = unread > 0 ? 'inline-block' : 'none';

            // Populate dropdown
            dropdown.innerHTML = "";
            if (data.length === 0) {
                dropdown.innerHTML = "<div class='no-notif'>No notifications</div>";
            } else {
                data.forEach(n => {
                    const item = document.createElement('div');
                    item.className = "notif-item " + (n.is_read == 0 ? "unread" : "");
                    item.innerHTML = `
                        <strong>${n.task_title ?? 'Task'}</strong> - ${n.message} 
                        <br><small>${n.created_at}</small>
                    `;
                    dropdown.appendChild(item);
                });
            }
        });
}

// auto-refresh notifications every 10s
setInterval(loadNotifications, 10000);
window.onload = loadNotifications;



    </script>
</body>
</html>
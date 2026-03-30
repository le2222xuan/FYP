<?php
session_start();
require_once __DIR__ . '/includes/paths.php';

// Verify login
if (!isset($_SESSION['user_id']) || (($_SESSION['usertype'] ?? '') !== 'user')) {
    header('Location: ' . base_url('login.php')); 
    exit; 
}

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Session Error: user_id not found. Please re-login.");
}
$current_user_id = $_SESSION['user_id'];

// Handle AJAX fetch events
if (isset($_GET['action']) && $_GET['action'] == 'fetch_events') {
    $events = [];
    
    // Fetch user-specific wedding orders
    $query_orders = "SELECT order_id, full_name, wedding_date, status FROM orders WHERE user_id = ? AND is_deleted = 0 AND wedding_date IS NOT NULL";
    $stmt_orders = $conn->prepare($query_orders);
    $stmt_orders->bind_param("i", $current_user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    
    while($row = mysqli_fetch_assoc($result_orders)) {
        $events[] = [
            'id' => 'wedding_' . $row['order_id'],
            'title' => "Wedding: " . $row['full_name'],
            'start' => $row['wedding_date'],
            'backgroundColor' => '#f8f4f0',
            'borderColor' => '#b38b5b',
            'textColor' => '#1a1816',
            'allDay' => true,
            'type' => 'wedding'
        ];
    }
    
    // Fetch user-specific checklist items
    $task_query = "SELECT checklist_id, task_text, task_date, task_time, status FROM checklist WHERE user_id = ? AND task_date IS NOT NULL";
    $stmt_task = $conn->prepare($task_query);
    $stmt_task->bind_param("i", $current_user_id);
    $stmt_task->execute();
    $task_result = $stmt_task->get_result();
    
    while($row = mysqli_fetch_assoc($task_result)) {
        $start_datetime = $row['task_date'];
        if (!empty($row['task_time'])) {
            $start_datetime .= ' ' . $row['task_time'];
        }
        
        $events[] = [
            'id' => 'task_' . $row['checklist_id'],
            'title' => $row['task_text'],
            'start' => $start_datetime,
            'backgroundColor' => $row['status'] ? '#e0e0e0' : '#b38b5b',
            'borderColor' => 'transparent',
            'textColor' => $row['status'] ? '#999' : '#fff',
            'allDay' => empty($row['task_time']),
            'type' => 'task'
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}

// Handle add task
if (isset($_POST['add_task'])) {
    $task_text = trim($_POST['task']);
    $task_date = !empty($_POST['task_date']) ? $_POST['task_date'] : NULL;
    $task_time = !empty($_POST['task_time']) ? $_POST['task_time'] : NULL;
    
    if (!empty($task_text)) {
        $stmt = $conn->prepare("INSERT INTO checklist (user_id, task_text, task_date, task_time) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $current_user_id, $task_text, $task_date, $task_time);
        $stmt->execute();
        header("Location: mySchedule.php");
        exit;
    }
}

// Handle toggle task status
if (isset($_GET['toggle'])) {
    $task_id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE checklist SET status = 1 - status WHERE checklist_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $current_user_id);
    $stmt->execute();
    header("Location: mySchedule.php");
    exit;
}

// Handle delete task
if (isset($_GET['delete_task'])) {
    $id = (int)$_GET['delete_task'];
    $stmt = $conn->prepare("DELETE FROM checklist WHERE checklist_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $current_user_id);
    $stmt->execute();
    header('Location: mySchedule.php');
    exit;
}

// Get all tasks
$stmt_list = $conn->prepare("SELECT * FROM checklist WHERE user_id = ? ORDER BY status ASC, task_date ASC, task_time ASC, created_at DESC");
$stmt_list->bind_param("i", $current_user_id);
$stmt_list->execute();
$todos = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);

// Template variables
$page_title = 'My Schedule';
$active_page = 'checklist';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | ChapterTwo</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php require_once __DIR__ . '/includes/paths.php'; echo base_url('assets/css/main-page.css'); ?>">
        <link rel="stylesheet" href="<?php echo base_url('assets/css/mySidebar.css'); ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --gold: #b38b5b;
            --gold-light: #dac29c;
            --dark: #1e1b18;
            --charcoal: #4f4a45;
            --border: #e3ddd5;
            --bg: #fdfaf7;
            --white: #ffffff;
            --shadow: 0 30px 60px -40px rgba(0,0,0,0.2);
            --serif: 'Cormorant Garamond', Georgia, serif;
            --sans: 'Inter', 'Helvetica Neue', -apple-system, sans-serif;
        }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--dark);
            line-height: 1.5;
            font-weight: 400;
            font-size: 15px;
        }

        /* dashboard container - same layout */
        /* layout handled by mySidebar.css app-wrapper grid */

        /* admin header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .admin-header::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100px;
            height: 1px;
            background: var(--gold);
        }

        .admin-header h1 {
            font-family: var(--serif);
            font-size: 2.2rem;
            font-weight: 500;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--dark);
            margin: 0;
        }

        .export-btn {
            color: var(--charcoal);
            text-decoration: none;
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: color 0.3s;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 400;
        }

        .export-btn:hover {
            opacity: 1;
            color: var(--gold);
        }

        /* schedule container - exactly same grid layout */
        .schedule-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
            margin-top: 5px;
        }

        /* cards */
        .calendar-card, .todo-card {
            background: var(--white);
            padding: 25px;
            box-shadow: var(--shadow);
            border: none;
        }

        #calendar { 
            min-height: 600px;
            font-family: var(--serif);
        }

        /* Todo List  */
        .todo-card h3 {
            font-family: var(--serif);
            font-size: 1.4rem;
            font-weight: 500;
            letter-spacing: 1.5px;
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .todo-card h3 i {
            color: var(--gold);
            opacity: 0.8;
            font-size: 1.3rem;
        }

        .todo-item {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
            transition: 0.3s;
        }

        .todo-item:hover { 
            background: rgba(179, 139, 91, 0.02); 
        }

        .todo-item.done span { 
            text-decoration: line-through; 
            color: #aaa; 
        }

        .todo-item .task-text {
            font-family: var(--serif);
            font-size: 1rem;
            font-weight: 400;
            color: var(--dark);
            flex: 1;
        }

        .todo-item .task-date {
            font-size: 0.8rem;
            color: var(--charcoal);
            margin-left: 15px;
            font-family: var(--sans);
            letter-spacing: 0.3px;
            background: rgba(179, 139, 91, 0.05);
            padding: 4px 10px;
            border-radius: 20px;
            white-space: nowrap;
            font-weight: 400;
        }

        .todo-item .task-date i {
            margin-right: 5px;
            color: var(--gold);
            opacity: 0.7;
            font-size: 0.75rem;
        }

        .todo-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 18px;
            cursor: pointer;
            accent-color: var(--gold);
            border: 1px solid var(--border);
        }

        .todo-item .delete-btn {
            margin-left: 15px;
            color: #ddd;
            transition: 0.3s;
            font-size: 1rem;
        }

        .todo-item:hover .delete-btn { 
            color: #d9534f; 
        }

        /* Add task form */
        .add-task-box {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 25px;
        }

        .add-task-box input[type="text"] {
            width: 100%;
            padding: 14px 0;
            border: none;
            border-bottom: 1px solid var(--border);
            outline: none;
            font-size: 1rem;
            font-family: var(--serif);
            background: transparent;
            transition: 0.3s;
            font-weight: 400;
        }

        .add-task-box input[type="text"]:focus {
            border-bottom-color: var(--gold);
        }

        .add-task-box input[type="text"]::placeholder {
            color: #bbb;
            font-style: italic;
            font-weight: 300;
        }

        .add-task-box .datetime-inputs {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .add-task-box .datetime-inputs .date-group,
        .add-task-box .datetime-inputs .time-group {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding: 8px 0;
        }

        .add-task-box .datetime-inputs label {
            font-size: 0.85rem;
            color: var(--charcoal);
            white-space: nowrap;
            font-family: var(--sans);
            letter-spacing: 0.5px;
            font-weight: 400;
        }

        .add-task-box .datetime-inputs label i {
            color: var(--gold);
            opacity: 0.7;
            margin-right: 5px;
        }

        .add-task-box .datetime-inputs input {
            border: none;
            outline: none;
            font-family: var(--sans);
            font-size: 0.9rem;
            background: transparent;
            color: var(--dark);
            font-weight: 400;
        }

        .add-task-box button {
            background: transparent;
            color: var(--dark);
            border: 1px solid var(--border);
            padding: 14px 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-family: var(--sans);
        }

        .add-task-box button:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        .add-task-box button i {
            margin-right: 8px;
        }

        /* Quick date buttons  */
        .quick-date-buttons {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .quick-date-btn {
            padding: 8px 14px;
            background: transparent;
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            font-family: var(--sans);
            letter-spacing: 0.5px;
            color: var(--charcoal);
            opacity: 0.8;
            text-transform: uppercase;
            font-weight: 400;
            border-radius: 30px;
        }
        
        .quick-date-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            opacity: 1;
            background: rgba(179, 139, 91, 0.02);
        }
        
        /* Upcoming tasks section heading */
        .upcoming-tasks-title {
            font-family: var(--serif);
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 1px;
            color: var(--charcoal);
            margin: 25px 0 15px 0;
            padding-top: 15px;
            border-top: 1px dashed var(--border);
            text-transform: uppercase;
            display: flex;
            align-items: center;
        }

        .upcoming-tasks-title i {
            color: var(--gold);
            margin-right: 12px;
            opacity: 0.8;
            font-size: 1rem;
        }

        /* Calendar customization  */
        .fc {
            --fc-border-color: var(--border);
            --fc-button-text-color: var(--dark);
            --fc-button-bg-color: transparent;
            --fc-button-border-color: transparent;
            --fc-button-hover-bg-color: transparent;
            --fc-button-hover-border-color: transparent;
            --fc-button-active-bg-color: transparent;
            --fc-button-active-border-color: transparent;
            --fc-today-bg-color: rgba(179, 139, 91, 0.03);
            --fc-page-bg-color: transparent;
            font-family: var(--serif);
        }

        .fc .fc-button {
            padding: 0.5rem 1.2rem;
            font-family: var(--sans);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
            border-radius: 0;
            transition: opacity 0.3s;
            opacity: 0.7;
        }

        .fc .fc-button:hover {
            opacity: 1;
            background: transparent;
        }

        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background: transparent;
            opacity: 1;
            color: var(--gold);
        }

        .fc .fc-toolbar-title {
            font-family: var(--serif);
            font-size: 1.6rem !important;
            font-weight: 500;
            letter-spacing: 2px;
            color: var(--dark);
            text-transform: uppercase;
        }

        .fc-theme-standard td, 
        .fc-theme-standard th {
            border: 1px solid var(--border) !important;
        }

        .fc-col-header-cell {
            background: transparent;
            padding: 0.8rem 0 !important;
            font-family: var(--serif);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            color: var(--charcoal);
        }

        .fc-daygrid-day-number {
            font-family: var(--serif);
            font-size: 0.95rem;
            color: var(--charcoal);
            padding: 0.6rem 0.6rem 0 0;
            font-weight: 400;
        }

        .fc-day-today .fc-daygrid-day-number {
            color: var(--gold);
            font-weight: 600;
        }

        .fc-event {
            border: none !important;
            background: transparent !important;
            padding: 0.2rem 0.5rem !important;
            margin: 0.15rem 0 !important;
            font-size: 0.85rem;
            cursor: pointer;
            border-radius: 0;
            border-left: 3px solid transparent !important;
            font-family: var(--serif);
            font-weight: 400;
        }

        .fc-event-title {
            font-weight: 400;
            font-size: 0.85rem;
        }

        .wedding-event {
            border-left: 3px solid var(--gold) !important;
        }

        .wedding-event .fc-event-title {
            color: var(--dark);
            font-weight: 500;
        }

        .task-event {
            border-left: 3px solid #ccc !important;
        }

        .task-event .fc-event-title {
            color: var(--charcoal);
        }

        .fc-daygrid-event-dot {
            display: none;
        }

        .fc-daygrid-event {
            padding: 3px 6px !important;
        }

        /* Print styles - keep same */
        @media print {
            .sidebar, .admin-header, .add-task-box, .delete-btn {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .schedule-container {
                grid-template-columns: 1fr;
            }
            .calendar-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .todo-card {
                display: none;
            }
        }

        @media (max-width: 1100px) {
            .schedule-container { 
                grid-template-columns: 1fr; 
            }
            #calendar {
                min-height: 450px;
            }
            .add-task-box .datetime-inputs {
                flex-direction: column;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .app-sidebar {
                display: none;
            }
        }

        .fc-daygrid-event {
            padding: 3px 6px !important;
        }
        .fc-event-time {
            font-size: 0.8rem;
            font-weight: normal;
            margin-right: 3px;
        }
        .fc-event-title {
            font-size: 0.85rem;
        }

        /* Added task specific styling */
        .todo-item .task-text {
            line-height: 1.4;
        }
        
        .todo-item .task-date {
            display: inline-flex;
            align-items: center;
        }
    </style>

</head>
<body>

<?php include __DIR__ . '/includes/header_user.php'; ?>

<div class="app-wrapper">
    <aside class="app-sidebar">
        <?php include __DIR__ . '/includes/mySidebar.php'; ?>
    </aside>

    <main class="app-main">
        <div class="content-card">
            <div class="admin-header" style="padding-bottom:20px; margin-bottom:25px;">
                <h1>Checklist</h1>
            </div>

        <div class="schedule-container">
            <div class="calendar-card">
                <div id="calendar"></div>
            </div>

            <div class="todo-card">
                <h3><i class="fas fa-list-ul"></i> Planning Tasks</h3>
                
                <div class="quick-date-buttons">
                    <button type="button" class="quick-date-btn" data-days="0">Today</button>
                    <button type="button" class="quick-date-btn" data-days="1">Tomorrow</button>
                    <button type="button" class="quick-date-btn" data-days="7">In 1 Week</button>
                    <button type="button" class="quick-date-btn" data-days="30">In 1 Month</button>
                </div>
                
                <form class="add-task-box" method="POST" id="taskForm">
                    <input type="text" name="task" placeholder="Add a new task..." required id="taskInput">
                    
                    <div class="datetime-inputs">
                        <div class="date-group">
                            <label for="task_date"><i class="far fa-calendar-alt"></i> Date:</label>
                            <input type="date" name="task_date" id="task_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="time-group">
                            <label for="task_time"><i class="far fa-clock"></i> Time:</label>
                            <input type="time" name="task_time" id="task_time" value="09:00">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_task"><i class="fas fa-plus"></i> Add Task</button>
                </form>

                <!-- All Upcoming Tasks -->
                <div class="upcoming-tasks-title">
                    <i class="far fa-calendar-alt"></i> All Upcoming Tasks
                </div>

                <div class="todo-list">
                    <?php foreach($todos as $todo): ?>
                        <div class="todo-item <?php echo $todo['status'] ? 'done' : ''; ?>">
                            <input type="checkbox" <?php echo $todo['status'] ? 'checked' : ''; ?> 
                                onclick="window.location.href='mySchedule.php?toggle=<?php echo (int) $todo['checklist_id']; ?>'">
                            
                            <span class="task-text"><?php echo htmlspecialchars($todo['task_text']); ?></span>
                            
                            <?php if($todo['task_date']): ?>
                                <?php 
                                $formattedDate = date('M d, Y', strtotime($todo['task_date']));
                                $displayText = $formattedDate;
                                if (!empty($todo['task_time'])) {
                                    $displayText .= ' · ' . date('g:i A', strtotime($todo['task_time']));
                                }
                                ?>
                                <span class="task-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo htmlspecialchars($displayText); ?>
                                </span>
                            <?php endif; ?>
                            
                            <a href="mySchedule.php?delete_task=<?php echo (int) $todo['checklist_id']; ?>" 
                            class="delete-btn">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($todos)): ?>
                        <p style="text-align:center; color:#aaa; margin-top:30px; font-style:italic;">No pending tasks.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.schedule-container -->

        </div><!-- /.content-card -->
    </main>

</div><!-- /.app-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: new Date(),
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        themeSystem: 'standard',
        events: 'mySchedule.php?action=fetch_events',
        
        eventClick: function(info) {
            var eventType = info.event.extendedProps.type;
            var message = '';
            
            if (eventType === 'wedding') {
                message = 'Wedding for: ' + info.event.title + 
                          '\nDate: ' + info.event.start.toLocaleDateString();
            } else if (eventType === 'task') {
                message = 'Task: ' + info.event.title + '\nDate: ' + 
                          info.event.start.toLocaleDateString();
                
                if (!info.event.allDay && info.event.start) {
                    var timeStr = info.event.start.toLocaleTimeString([], {
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: true
                    });
                    message += ' at ' + timeStr;
                }
            }
            
            alert(message);
        },
        
        height: 'auto',
        navLinks: true,
        eventClassNames: function(arg) {
            return arg.event.extendedProps.type === 'wedding' ? 'wedding-event' : 'task-event';
        },
        allDaySlot: true,
        nowIndicator: true,
        
        displayEventTime: false, 

        eventContent: function(arg) {
            return {
                html: '<div class="fc-event-title">' + arg.event.title + '</div>'
            };
        }
    });
    
    calendar.render();
    
    document.querySelectorAll('.quick-date-btn').forEach(button => {
        button.addEventListener('click', function() {
            const days = parseInt(this.getAttribute('data-days'));
            const targetDate = new Date();
            targetDate.setDate(targetDate.getDate() + days);
            
            const dateStr = targetDate.toISOString().split('T')[0];
            document.getElementById('task_date').value = dateStr;
            document.getElementById('task_time').value = '09:00';
            document.getElementById('taskInput').focus();
            
            document.querySelectorAll('.quick-date-btn').forEach(btn => {
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
            });
            this.style.borderColor = 'var(--gold)';
            this.style.color = 'var(--gold)';
        });
    });
    
    //Print
    document.getElementById('print-schedule').addEventListener('click', function(e) {
        e.preventDefault();
        calendar.refetchEvents();
        setTimeout(function() {
            window.print();
        }, 500); 
    });
    
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    var tomorrowStr = tomorrow.toISOString().split('T')[0];
    if(document.getElementById('task_date')) {
        document.getElementById('task_date').value = tomorrowStr;
    }
    
    calendar.on('dateClick', function(info) {
        document.getElementById('task_date').value = info.dateStr;
        document.getElementById('task_time').value = '09:00';
        document.getElementById('taskInput').focus();
    });
});
</script>
<?php include 'includes/footer.php'; ?>

</body>
</html>
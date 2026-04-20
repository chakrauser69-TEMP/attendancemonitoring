<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}
include 'connection.php';

$students = []; // list of students in this session (in PHP, not JS)
$summary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];

// --- Handle new student scan via form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_student'])) {
    $student_id = trim($_POST['student_id']);
    $class      = $_POST['class'] ?? 'bsit1a';
    $date       = $_POST['date'] ?? date('Y-m-d');
    $time       = $_POST['time'] ?? date('H:i:s');

    if (!empty($student_id)) {
        // OPTIONAL: fetch name from DB (if you have a students table)
        $name = $student_id; // fallback: just use ID
        $stmt_name = $pdo->prepare("SELECT name FROM students WHERE student_id = ?");
        if ($stmt_name->execute([$student_id]) && $row = $stmt_name->fetch()) {
            $name = $row['name'];
        }

        // Simple check: avoid duplicate rows in this session
        $found = false;
        foreach ($_SESSION['marks'][$class] ?? [] as $student) {
            if ($student['id'] === $student_id) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $new_student = [
                'id'      => $student_id,
                'name'    => $name,
                'status'  => 'present',
                'time'    => date('H:i:s'),
                'remarks' => '',
            ];

            // Store in SESSION for this CLASS + DATE (so you can still modify)
            if (!isset($_SESSION['marks'][$class])) {
                $_SESSION['marks'][$class] = [];
            }
            $_SESSION['marks'][$class][] = $new_student;

            // Optional: direct DB insert (mark now instead of later)
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, class, date, time, status, remarks)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $class, $date, $time, 'present', '']);
        }
    }

    // Redirect to self to reflect updated table
    header("Location: mark_attendance.php");
    exit;
}

// --- Load current session data (from SESSION or DB) ---
$class = $_POST['class'] ?? ($_SESSION['marks']['last_class'] ?? 'bsit1a');
$date  = $_POST['date'] ?? date('Y-m-d');

$students = $_SESSION['marks'][$class] ?? [];

// --- Summary ---
$total = count($students);
$present = count(array_filter($students, fn($s) => $s['status'] === 'present'));
$absent  = $total - $present;
$late    = count(array_filter($students, fn($s) => $s['status'] === 'late'));

// --- Save to database (Submit Attendance button) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $class = $_POST['class'] ?? 'bsit1a';
    $date  = $_POST['date'] ?? date('Y-m-d');
    $time  = $_POST['time'] ?? date('H:i:s');

    // Clear old attendance for this class & date first (optional)
    // $pdo->prepare("DELETE FROM attendance WHERE class = ? AND date = ?")->execute([$class, $date]);

    // Insert all students in the session
    $stmt = $pdo->prepare("
        INSERT INTO attendance (student_id, class, date, time, status, remarks)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($_SESSION['marks'][$class] ?? [] as $student) {
        $stmt->execute([
            $student['id'],
            $class,
            $date,
            $time,
            $student['status'],
            $student['remarks'],
        ]);
    }

    // Optionally clear session for this class after saving
    // unset($_SESSION['marks'][$class]);

    header("Location: teacher_dashboard.php?msg=Attendance+saved");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mark Attendance (Teacher)</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f7fa;
      margin: 0;
      padding: 0;
      padding-top: 40px;
      padding-bottom: 40px;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }
    h1 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 10px;
    }

    /* Session header */
    section.session {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 20px 24px;
      margin-bottom: 20px;
    }
    section.session h2 {
      margin-top: 0;
      color: #34495e;
      font-size: 1.1rem;
      margin-bottom: 12px;
    }
    section.session label {
      display: inline-block;
      margin-right: 8px;
      margin-bottom: 4px;
      color: #34495e;
      font-weight: 500;
    }
    section.session select,
    section.session input[type="date"],
    section.session input[type="time"] {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      margin-right: 16px;
    }
    section.session button.qr {
      margin-top: 12px;
      padding: 8px 14px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
    }
    section.session button.qr:hover {
      background-color: #2980b9;
    }
    section.session button.qr:disabled {
      background-color: #bdc3c7;
      cursor: not-allowed;
    }

    /* Manual "scanner" box */
    .qr-camera {
      margin-top: 12px;
      padding: 12px 16px;
      background: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 6px;
    }
    .qr-camera.active {
      border-color: #27ae60;
      background: #f0f7ff;
    }
    .qr-status {
      padding: 8px;
      margin-top: 6px;
      border-radius: 4px;
      font-weight: 500;
    }
    .qr-status.scanning {
      background: rgba(39, 174, 96, 0.1);
      color: #27ae60;
      border: 1px solid #27ae60;
    }
    .qr-status.success {
      background: rgba(39, 174, 96, 0.2);
      color: #27ae60;
    }
    .qr-status.error {
      background: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
      border: 1px solid #e74c3c;
    }

    /* Student table */
    section.attendance {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 20px 24px;
      margin-bottom: 20px;
    }
    section.attendance h2 {
      margin-top: 0;
      color: #2c3e50;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    th {
      background-color: #3498db;
      color: white;
      text-align: left;
      padding: 10px 12px;
      font-weight: normal;
    }
    td {
      padding: 8px 12px;
      border-bottom: 1px solid #eee;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    tr:last-child td {
      border-bottom: none;
    }

    select {
      padding: 6px 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      min-width: 100px;
    }
    input[type="text"] {
      padding: 6px 8px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 13px;
      width: 100%;
    }
    button[type="submit"] {
      padding: 10px 16px;
      background-color: #27ae60;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
    }
    button[type="submit"]:hover {
      background-color: #219653;
    }

    /* Summary section */
    section.summary {
      background-color: #f0f7ff;
      border-radius: 10px;
      padding: 16px 20px;
      border-left: 4px solid #3498db;
    }
    section.summary h2 {
      margin-top: 0;
      color: #2c3e50;
      font-size: 1rem;
      margin-bottom: 8px;
    }
    section.summary p {
      margin: 4px 0;
      color: #34495e;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Mark Attendance</h1>

    <!-- Session header -->
    <section class="session">
      <h2>Session Details</h2>
      <form method="post">
        <label for="class">Class:</label>
        <select id="class" name="class">
          <option value="bsit1a" <?= $class === 'bsit1a' ? 'selected' : '' ?>>BSIT 1A</option>
          <option value="bsit1b" <?= $class === 'bsit1b' ? 'selected' : '' ?>>BSIT 1B</option>
          <option value="bsce1a" <?= $class === 'bsce1a' ? 'selected' : '' ?>>BSCE 1A</option>
        </select>

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" />

        <label for="time">Time:</label>
        <input type="time" id="time" name="time" value="<?= date('H:i') ?>" />

        <!-- "Start camera" / "Start scan" -->
        <button type="submit" class="qr" name="start_scan">Start scan</button>
      </form>

      <!-- Manual "QR scanner" (instead of jsQR) -->
      <div class="qr-camera" id="qr-camera-container" class="active">
        <h3 style="margin:4px 0">Scan / Add Student</h3>
        <form method="post" style="display: flex; gap: 8px; align-items: end;">
          <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
          <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
          <input type="hidden" name="time" value="<?= date('H:i:s') ?>">

          <input type="text" name="student_id" id="student_id"
                 placeholder="Enter student ID (or QR value)" required style="flex:1;">

          <button type="submit" name="scan_student" style="margin:0">🔍 Scan / Add</button>
        </form>
        <div id="qr-status" class="qr-status scanning">
          Enter student ID manually or scan their QR, then click "Scan / Add".
        </div>
      </div>
    </section>

    <!-- Student attendance table -->
    <section class="attendance">
      <h2 id="classHeader">Class: <?= strtoupper($class) ?></h2>
      <table id="attendanceTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Time</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $index => $student): ?>
          <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($student['id']) ?></td>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td>
              <select onchange="updateStatus(this, <?= json_encode($student['id']) ?>)" style="width:100%;">
                <option value="present" <?= $student['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                <option value="late"    <?= $student['status'] === 'late'    ? 'selected' : '' ?>>Late</option>
                <option value="absent"  <?= $student['status'] === 'absent'  ? 'selected' : '' ?>>Absent</option>
                <option value="excused" <?= $student['status'] === 'excused' ? 'selected' : '' ?>>Excused</option>
              </select>
            </td>
            <td><?= htmlspecialchars($student['time']) ?></td>
            <td>
              <input type="text" value="<?= htmlspecialchars($student['remarks']) ?>"
                     onchange="updateRemarks(this, <?= json_encode($student['id']) ?>)"
                     placeholder="Optional remarks" style="width:100%;">
            </td>
          </tr>
          <?php endforeach; ?>

          <?php if (empty($students)): ?>
          <tr>
            <td colspan="6" style="text-align:center; color:#888;">
              No students checked in yet.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
      <button type="submit" id="submitAttendance" name="submit_attendance">Submit Attendance</button>
    </section>

    <!-- Summary section -->
    <section class="summary">
      <h2>Attendance Summary</h2>
      <p id="totalStudents"><strong>Total Students:</strong> <?= $total ?></p>
      <p id="presentCount"><strong>Present:</strong> <?= $present ?></p>
      <p id="absentCount"><strong>Absent:</strong> <?= $absent ?></p>
      <p id="lateCount"><strong>Late:</strong> <?= $late ?></p>
    </section>
  </div>

  <!-- Small JS for status / remarks UI (optional, can be removed later) -->
  <script>
    function updateStatus(select, id) {
      fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'student_id=' + encodeURIComponent(id) +
              '&status=' + encodeURIComponent(select.value) +
              '&class=<?= urlencode($class) ?>'
      });
    }

    function updateRemarks(input, id) {
      fetch('update_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'student_id=' + encodeURIComponent(id) +
              '&remarks=' + encodeURIComponent(input.value) +
              '&class=<?= urlencode($class) ?>'
      });
    }
  </script>
</body>
</html>
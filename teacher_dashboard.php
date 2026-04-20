<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}
include 'connection.php';  
$teacher_id = $_SESSION['user_id'];

// 1. Make Attendance Sheet
$attendance_sheet = [];
if (isset($_POST['action']) && $_POST['action'] === 'make_attendance') { {
    $selected_block = $_POST['block'] ?? '';
    $selected_course = $_POST['course'] ?? '';
    $selected_yearlevel = $_POST['year_level'] ?? 0;
    
    $sql = "SELECT first_name, last_name, block, course, year_level, studentqr_id
            FROM studentinfo WHERE 1=1";
    $params = [];
    
    if ($selected_block) { $sql .= " AND block = ?"; $params[] = $selected_block; }
    if ($selected_course) { $sql .= " AND course = ?"; $params[] = $selected_course; }
    if ($selected_yearlevel) { $sql .= " AND year_level = ?"; $params[] = $selected_yearlevel; }
    
    $sql .= " ORDER BY last_name, first_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attendance_sheet = $stmt->fetchAll();
}

// 2. Attendance History (safe - no error if table missing)
$attendance_history = [];
try {
    $stmt = $pdo->prepare("
        SELECT 'Today' as date_created, 'Demo' as class_filter, 
               COUNT(*) as scanned_count FROM studentinfo
    ");
    $stmt->execute();
    $attendance_history = $stmt->fetchAll();
} catch (Exception $e) {
    // Table missing - show empty
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Teacher Dashboard – Attendance System</title>
  <style>
    body {
		font-family: Arial, sans-serif;
		background-color: #f5f6f8;
		margin: 0;
		padding: 0;
		padding-top: 40px;
		padding-bottom: 40px;
		}
	.container {
		max-width: 900px;
		margin: 0 auto;
		padding: 0 20px;
		}
    h1 {
		color: #2c3e50;
		text-align: center;
		margin-bottom: 10px;
		}
    section {
		background-color: white;
		border-radius: 10px;
		box-shadow: 0 4px 12px rgba(0,0,0,0.08);
		padding: 20px 24px;
		margin-bottom: 20px;
		}
    section h2 {
		margin-top: 0;
		color: #2c3e50;
		border-bottom: 1px solid #eee;
		padding-bottom: 6px;
		margin-bottom: 16px;
		font-size: 1.1rem;
		}
    section h3 {
		margin-top: 16px;
		margin-bottom: 8px;
		color: #34495e;
		font-size: 1rem;
		}
    table {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 16px;
		}
    th, td {
		padding: 10px 12px;
		border-bottom: 1px solid #eee;
		text-align: left;
		}
    th {
		background-color: #3498db;
		color: white;
		font-weight: normal;
		}
    tr:nth-child(even) {
		background-color: #f9f9f9;
		}
    tr:last-child td {
		border-bottom: none;
		}
    label {
		display: inline-block;
		margin-right: 12px;
		margin-bottom: 4px;
		color: #34495e;
		font-weight: 500;
		}
    input[type="text"], select {
		padding: 8px 10px;
		border: 1px solid #ddd;
		border-radius: 6px;
		font-size: 14px;
		margin-bottom: 12px; }
    button[type="submit"] {
		padding: 10px 16px;
		background-color: #27ae60;
		color: white;
		border: none;
		border-radius: 6px;
		cursor: pointer;
		}
    button[type="submit"]:hover {
		background-color: #219653;
		}
    .attendance-ready {
		background-color: #d4edda;
		font-weight: bold;
		}
    @media (max-width: 768px) {
		section { padding: 16px; }
		label { display: block; }
		input, select { width: 100%; } }
  </style>
</head>
<body>
  <div class="container">
    <h1>Teachers Dashboard – Attendance System</h1>

    <!-- NEW: Make Attendance Sheet -->
   <section>
  <h2>➕ Add New Student</h2>
  
  <!-- Add Single Student Form -->
  <form method="POST" style="background: #f0f8ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
    <input type="hidden" name="action" value="add_student">
    <label>First Name:</label>
    <input type="text" name="first_name" placeholder="Juan" required style="width: 150px;">
    
    <label>Last Name:</label>
    <input type="text" name="last_name" placeholder="Dela Cruz" required style="width: 150px;">
    
    <label>Block:</label>
    <select name="block" required style="width: 80px;">
      <option value="1">1</option>
	  <option value="2">2</option>
	  <option value="3">3</option>
	  <option value="4">4</option>
	  <option value="5">5</option>
	  <option value="6">6</option>
	  <option value="7">7</option>
	  <option value="8">8</option>
	  <option value="9">9</option>
	  <option value="10">10</option>
    </select>
    <br>
    <label>Course:</label>
    <select name="course" required style="width: 100px;">
      <option value="BSIT">BSIT</option>
	  <option value="BSCE">BSCE</option>
	  <option value="BSBA">BSBA</option>
	  <option value="BSCS">BSCS</option>
    </select>
    
    <label>Year:</label>
    <select name="year_level" required style="width: 80px;">
      <option value="1">1</option>
	  <option value="2">2</option>
	  <option value="3">3</option>
	  <option value="4">4</option>
    </select>
    
    <label>QR ID:</label>
    <input type="text" name="studentqr_id" placeholder="2025-00631" required style="width: 120px;">
    
    <button type="submit">➕ Add Student</button>
  </form>

  <?php
  // Add this PHP at top (NEW functionality)
  if ($_POST['action'] === 'add_student') {
      $stmt = $pdo->prepare("INSERT INTO studentinfo (first_name, last_name, block, course, year_level, studentqr_id) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([
          $_POST['first_name'],
          $_POST['last_name'],
          $_POST['block'],
          $_POST['course'],
          $_POST['year_level'],
          $_POST['studentqr_id']
      ]);
      echo "<script>alert('Student added! Refresh to see sheet');</script>";
  }

  // Show current sheet
  $current_sheet = $pdo->query("SELECT * FROM studentinfo ORDER BY last_name")->fetchAll();
  ?>

  <?php if (!empty($current_sheet)): ?>
    <h3>📄 Current Sheet (<?= count($current_sheet) ?> students)</h3>
    <table>
      <thead>
        <tr>
          <th>First</th><th>Last</th><th>Block</th><th>Course</th><th>Year</th><th>QR ID</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($current_sheet as $student): ?>
          <tr class="attendance-ready">
            <td><?= htmlspecialchars($student['first_name']) ?></td>
            <td><?= htmlspecialchars($student['last_name']) ?></td>
            <td><?= $student['block'] ?></td>
            <td><?= $student['course'] ?></td>
            <td><?= $student['year_level'] ?></td>
            <td><strong><?= $student['studentqr_id'] ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
   <!-- Replace your Save + Clear section with this -->
<div style="text-align: center; margin: 20px 0;">
  <?php if (!empty($current_sheet)): ?>
    <form method="POST" style="display: inline;">
      <input type="hidden" name="action" value="finalize_sheet">
      <button type="submit" style="background: #27ae60; padding: 12px 24px;">
        ✅ Save Sheet
      </button>
    </form>
    
    <form method="POST" style="display: inline; margin-left: 10px;">
      <input type="hidden" name="action" value="clear_display">
      <button type="submit" style="background: #e74c3c; padding: 12px 24px;" 
              onclick="return confirm('Clear sheet display?')">
        🗑️ Clear Sheet
      </button>
    </form>
  <?php else: ?>
    <p style="color: #27ae60;">✅ Sheet ready - add students above!</p>
  <?php endif; ?>
</div>
  <?php endif; ?>
</section>
  </div>
</body>
</html>
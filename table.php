<?php
include "connection.php";

// GET DATA TO BE DISPLAYED IN THE TABLE
$data = $pdo->query("SELECT * FROM users"); //ang data variable is may hawak sa mga data na nasa DATABASE.


//QUERY FOR UPDATE
/*ANG "isset()" function ay default na sa PHP, ang ibig sabihin niyan is kapag naka set sya, or yung button
na yung ang gi click, sa pag gamit ng isset() ay dapat meron "name" ang button ninyo tulad jan "$_POST['update']"*/
if (isset($_POST['update'])) {

//SA update query na ito, mag i-execute ito kahit walang data, kasi nga wala pang validation.
    $id = $_POST['user_id']; //para i collect ang data ng id at i-store sa variable
    $username = $_POST['username']; //para i collect ang data ng username at i-store sa variable
    $password = $_POST['password']; //para i collect ang password ng username at i-store sa variable
	$role = $_POST['role'];  //para i collect ang role ng username at i-store sa variable
    $sql = "UPDATE users SET username=?, password=?, role=? WHERE user_id=?"; //Query
    $stmt = $pdo->prepare($sql); 

    $stmt->execute([$username, $password, $role, $id]); //UPDATE account SET username=$username, password=$password WHERE ID=$id

//                     ALERT MESSAGE                RELOADS THE PAGE
    echo "<script>alert('Updated successfully'); window.location.href='';</script>";
}

//QUERY FOR DELETE
/*ANG "isset()" function ay default na sa PHP, ang ibig sabihin niyan is kapag naka set sya, or yung button
na yung ang gi click, sa pag gamit ng isset() ay dapat meron "name" ang button ninyo tulad jan "$_POST['delete']"*/
if (isset($_POST['delete'])) {

//SA delete query na ito, mag i-execute ito kahit walang data, kasi nga wala pang validation.
    $id = $_POST['id']; //para i collect ang data ng id at i-store sa variable

    $sql = "DELETE FROM account WHERE user_id=?"; //Query
    $stmt = $pdo->prepare($sql);

    $stmt->execute([$id]);  // Also read as DELETE FROM account WHERE ID= $id

//                     ALERT MESSAGE                RELOADS THE PAGE
    echo "<script>alert('Deleted successfully'); window.location.href='';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Table</title>
<style>
            tr:hover {
            background: lightgray;
            cursor: pointer;
        }
</style>
</head>

<body>

<h2>Account Table</h2>



<!-- TABLE -->
<table border="1">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Password</th>
    </tr>

    <!-- Converting the $data variable as $row para i-display sa mga table row ang Selected Data-->
    <?php foreach($data as $row): ?>

    <!-- 
        Ang onclick="" is HTML attribute yan na ibig sabihin is kapag gi-click.
        Ang script naman means para i-display sa row ang lahat ng mga ID, USERNAME at PASSWORD na nasa DATABASE mo. 
        Ang getData() function naman is JavaScript yan, ang code ay nasa baba, ang function na yan is para pag nag
        click kayo ng row, mag display ang data sa mga input field na gi-click ninyo.
      -->
    <tr onclick="getData('<?php echo $row['id']; ?>','<?php echo $row['username']; ?>','<?php echo $row['password']; ?>')">
        <td><?php echo $row['id']; ?></td>      <!-- Displayss all ID -->
        <td><?php echo $row['username']; ?></td>  <!-- Displayss all username -->
        <td><?php echo $row['password']; ?></td>   <!-- Displayss all password -->
		<td><?php echo $row['role']; ?></td>
    </tr>
    <?php endforeach; ?> <!-- closing ng foreach -->

</table>

<br>
<h2> Information </h2>
<form method="POST"> <!-- Okay lang, walang action since nag gamit tayo ng isset() function -->


<input type="text" id="id" name="id" placeholder="ID" readonly><br><br>

<input type="text" id="username" name="username" placeholder="Username"><br><br>

<input type="text" id="password" name="password" placeholder="Password"><br><br>

<input type="text" id="role" name="role" placeholder="Role"><br><br>

<input type="submit" name="update" value="Update">
<input type="submit" name="delete" value="Delete">
</form>

    
<!-- JAVASCRIPT -->
<!-- getData() fucntion 
ang document.getElementById() ibig sabihin niyan is i get yung element na may 
id="id"
id="username"
id="password" na kung saan naman makikita natin yan doon sa mga <input> natin

NOTE: MAGKAIBA ANG id at name! ANG id IS UNIQUE IDENTIFICATION NG TAG/ELEMENT
ANG name NAMAN IS KEY NG INPUT PARA MA ACCESS ANG DATA NYA THROUGH PHP.
-->
<script>
function getData(id, username, password, role) {
    document.getElementById("id").value = id;
    document.getElementById("username").value = username;
    document.getElementById("password").value = password;
	document.getElementById("role").value = role;
}
</script>

</body>
</html>
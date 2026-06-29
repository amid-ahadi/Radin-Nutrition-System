<?php
/**
 * -------------------------In the name of ALLAH-----------------------------
 * --------------------------------------------------------------------------
 * Programmer:  Amid Ahadi
 * Email:       Amid-ahadi@gmail.com
 * Website:     amid-ahadi.ir
 * Copyright:   All rights reserved for Amid Ahadi
 * --------------------------------------------------------------------------
 * Coded for Karaj Emam Hospital with love ❤️
 * Created:     2026-06-20
 */
include("../layout/header.php");
include("../config/database.php");
include("../config/logger.php");

logAction($conn,$_SESSION["user_id"],"ثبت درخواست غذا");
allowRoles(["ward_secretary", "admin"]);
$foods = $conn->query("SELECT * FROM foods");

if($_POST){

$ward_id = $_SESSION["ward_id"] ?? 1;
$date = $_POST["meal_date"];
$meal = $_POST["meal_type"];
$food = $_POST["food_id"];
$count = $_POST["patient_count"];
$user = $_SESSION["user_id"];

$stmt = $conn->prepare("
INSERT INTO meal_requests 
(ward_id,meal_date,meal_type,food_id,patient_count,created_by,status)
VALUES (?,?,?,?,?,?,0)
");

$stmt->bind_param("issiii",$ward_id,$date,$meal,$food,$count,$user);
$stmt->execute();

echo "<div class='alert alert-success'>درخواست ثبت شد</div>";
}
?>

<h4>ثبت درخواست غذا</h4>

<form method="post">

<input type="date" class="form-control mb-3" name="meal_date">

<select class="form-control mb-3" name="meal_type">
<option value="breakfast">صبحانه</option>
<option value="lunch">ناهار</option>
<option value="dinner">شام</option>
</select>

<select class="form-control mb-3" name="food_id">
<?php while($f=$foods->fetch_assoc()): ?>
<option value="<?=$f["id"]?>"><?=$f["food_name"]?></option>
<?php endwhile; ?>
</select>

<input type="number" class="form-control mb-3" name="patient_count" placeholder="تعداد بیمار">

<button class="btn btn-primary">ثبت درخواست</button>

</form>

<?php include("../layout/footer.php"); ?>

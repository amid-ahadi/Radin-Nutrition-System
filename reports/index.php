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

$from=$_GET["from"] ?? date("Y-m-01");
$to=$_GET["to"] ?? date("Y-m-d");

$stmt=$conn->prepare("
SELECT foods.food_name,
SUM(meal_requests.patient_count) as total_count,
food_prices.price,
SUM(meal_requests.patient_count * food_prices.price) as total_price
FROM meal_requests
JOIN foods ON foods.id = meal_requests.food_id
JOIN food_prices ON food_prices.food_id = foods.id
WHERE meal_requests.status=2
AND meal_requests.meal_date BETWEEN ? AND ?
GROUP BY foods.food_name
");

$stmt->bind_param("ss",$from,$to);
$stmt->execute();
$result=$stmt->get_result();
?>

<h4>گزارش مالی</h4>

<form method="get" class="row mb-3">
<div class="col-md-3">
<input type="date" name="from" class="form-control" value="<?=$from?>">
</div>
<div class="col-md-3">
<input type="date" name="to" class="form-control" value="<?=$to?>">
</div>
<div class="col-md-3">
<button class="btn btn-primary">نمایش</button>
</div>
</form>

<table class="table table-bordered">

<tr>
<th>غذا</th>
<th>تعداد</th>
<th>قیمت واحد</th>
<th>جمع</th>
</tr>

<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?=$row["food_name"]?></td>
<td><?=$row["total_count"]?></td>
<td><?=$row["price"]?></td>
<td><?=$row["total_price"]?></td>
</tr>
<?php endwhile; ?>

</table>

<?php include("../layout/footer.php"); ?>

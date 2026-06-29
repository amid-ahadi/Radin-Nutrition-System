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
include("../auth/role_check.php");

allowRoles(["nutrition_manager","admin"]);
$result = $conn->query("SELECT * FROM foods ORDER BY id DESC");
?>

<h4>مدیریت غذاها</h4>

<a href="add.php" class="btn btn-primary mb-3">افزودن غذا</a>

<table class="table table-bordered">

<tr>
<th>#</th>
<th>نام غذا</th>
<th>وعده</th>
</tr>

<?php while($row=$result->fetch_assoc()): ?>

<tr>
<td><?=$row["id"]?></td>
<td><?=$row["food_name"]?></td>
<td><?=$row["meal_type"]?></td>
</tr>

<?php endwhile; ?>

</table>

<?php include("../layout/footer.php"); ?>

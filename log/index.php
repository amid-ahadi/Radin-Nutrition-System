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

$sql="
SELECT activity_logs.*, users.name
FROM activity_logs
JOIN users ON users.id = activity_logs.user_id
ORDER BY activity_logs.id DESC
";

$res=$conn->query($sql);

?>

<h4>لاگ فعالیت کاربران</h4>

<table class="table table-bordered">

<tr>
<th>کاربر</th>
<th>عملیات</th>
<th>زمان</th>
</tr>

<?php while($row=$res->fetch_assoc()): ?>

<tr>
<td><?=$row["name"]?></td>
<td><?=$row["action"]?></td>
<td><?=$row["created_at"]?></td>
</tr>

<?php endwhile; ?>

</table>

<?php include("../layout/footer.php"); ?>

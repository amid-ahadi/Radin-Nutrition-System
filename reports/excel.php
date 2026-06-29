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

include("../config/database.php");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=financial_report.xls");

echo "Food\tCount\tPrice\tTotal\n";

$sql="
SELECT foods.food_name,
SUM(meal_requests.patient_count) as count,
food_prices.price,
SUM(meal_requests.patient_count * food_prices.price) as total
FROM meal_requests
JOIN foods ON foods.id = meal_requests.food_id
JOIN food_prices ON food_prices.food_id = foods.id
WHERE meal_requests.status=2
GROUP BY foods.food_name
";

$res=$conn->query($sql);

while($row=$res->fetch_assoc()){

echo $row["food_name"]."\t".
$row["count"]."\t".
$row["price"]."\t".
$row["total"]."\n";

}

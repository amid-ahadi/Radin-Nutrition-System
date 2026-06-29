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

$sql = "
SELECT 
MONTH(meal_date) as month,
SUM(meal_requests.patient_count * food_prices.price) as total
FROM meal_requests
JOIN food_prices ON food_prices.food_id = meal_requests.food_id
WHERE meal_requests.status = 2
GROUP BY MONTH(meal_date)
";

$res = $conn->query($sql);

$months=[];
$totals=[];

while($row=$res->fetch_assoc()){

$months[]=$row["month"];
$totals[]=$row["total"];

}
?>

<h4>نمودار هزینه ماهانه غذا</h4>

<canvas id="foodChart"></canvas>

<script>

var ctx=document.getElementById('foodChart');

new Chart(ctx,{

type:'bar',

data:{
labels:<?=json_encode($months)?>,
datasets:[{
label:'هزینه غذا',
data:<?=json_encode($totals)?>,
backgroundColor:'#0d6efd'
}]
}

});

</script>

<?php include("../layout/footer.php"); ?>

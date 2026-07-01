<?php
include("../config/database.php");
$id = (int)$_GET['id'];

$sql = "SELECT i.name, mi.quantity, u.name as unit 
        FROM meal_analysis_items mi 
        JOIN ingredients i ON mi.ingredient_id = i.id 
        LEFT JOIN units u ON i.unit_id = u.id 
        WHERE mi.analysis_id = $id";
$res = $conn->query($sql);
?>
<div class="modal-header bg-info text-white">
    <h5 class="modal-title">جزئیات مواد اولیه</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <table class="table table-bordered text-center">
        <thead><tr><th>ماده</th><th>مقدار</th><th>واحد</th></tr></thead>
        <tbody>
            <?php while($item = $res->fetch_assoc()): ?>
                <tr>
                    <td><?=$item['name']?></td>
                    <td><?=$item['quantity']?></td>
                    <td><?=$item['unit']?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

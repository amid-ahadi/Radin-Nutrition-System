<?php
include("../config/database.php");
include("../auth/role_check.php");
include_once("../config/jdf.php");

// تعیین سطح دسترسی
allowRoles(['admin', 'nutrition_manager', 'staff']);

$msg = "";
$error = "";

// ثبت ماده اولیه جدید
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ingredient'])) {
    $name = trim($_POST['name']);
    $unit_id = intval($_POST['unit_id']);

    if (!empty($name) && $unit_id > 0) {
        $stmt = $conn->prepare("INSERT INTO ingredients (name, unit_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $unit_id);
        if ($stmt->execute()) {
            $msg = "ماده اولیه با موفقیت ثبت شد.";
        } else {
            $error = "خطا در ثبت اطلاعات: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "لطفاً تمامی فیلدها را به درستی پر کنید.";
    }
}

include("../layout/header.php");
?>

<div class="container-fluid mt-4">
    <!-- نمایش پیام‌ها -->
    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show text-right" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show text-right" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- کارت فرم ثبت -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> مدیریت و تعریف مواد اولیه</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3 align-items-end">
                <div class="col-md-5 text-right">
                    <label class="form-label font-weight-bold">نام ماده اولیه</label>
                    <input type="text" name="name" class="form-control" placeholder="مثال: برنج طارم، لپه، روغن مایع" required>
                </div>
                <div class="col-md-4 text-right">
                    <label class="form-label font-weight-bold">واحد اندازه‌گیری</label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">-- انتخاب واحد --</option>
                        <?php
                        $units = $conn->query("SELECT * FROM units");
                        while ($u = $units->fetch_assoc()):
                        ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_ingredient" class="btn btn-success w-100 py-2">
                        <i class="fas fa-plus-circle me-1"></i> افزودن به انبار
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول نمایش مواد اولیه -->
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <h6 class="mb-0 text-secondary font-weight-bold"><i class="fas fa-list me-2"></i> لیست مواد اولیه تعریف‌شده</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 50%;">نام ماده اولیه</th>
                        <th style="width: 40%;">واحد استاندارد مصرف</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $list = $conn->query("
                    SELECT ingredients.id, ingredients.name, units.name AS unit_name 
                    FROM ingredients 
                    JOIN units ON ingredients.unit_id = units.id 
                    ORDER BY ingredients.id DESC
                ");
                $counter = 1;
                if ($list->num_rows > 0):
                    while ($row = $list->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($row['name']) ?></td>
                        <td><span class="badge bg-info text-dark px-3 py-2"><?= htmlspecialchars($row['unit_name']) ?></span></td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr>
                        <td colspan="3" class="text-muted py-4">هنوز هیچ ماده اولیه‌ای تعریف نشده است.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("../layout/footer.php"); ?>

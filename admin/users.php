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

allowRoles(["admin"]);

$msg = "";

// ۱. منطق حذف کاربر
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // جلوگیری از حذف ادمین اصلی (فرض بر این است که یوزر ادمین آیدی ۱ یا یوزرنیم مشخصی دارد)
    $check_admin = $conn->query("SELECT username FROM users WHERE id = $delete_id")->fetch_assoc();
    
    if ($check_admin['username'] === 'admin') {
        $msg = "<div class='alert alert-danger'>خطا: مدیر اصلی سیستم قابل حذف نیست!</div>";
    } else {
        $conn->query("DELETE FROM users WHERE id = $delete_id");
        $msg = "<div class='alert alert-success'>کاربر با موفقیت حذف شد.</div>";
    }
}

// ۲. منطق تغییر رمز عبور
if (isset($_POST['change_pass'])) {
    $user_id = intval($_POST['user_id']);
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_pass, $user_id);
    if($stmt->execute()) {
        $msg = "<div class='alert alert-success'>رمز عبور با موفقیت تغییر کرد.</div>";
    }
}

// ۳. ایجاد کاربر جدید
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $ward_id = ($role == 'ward_secretary') ? $_POST['ward_id'] : NULL;

    // چک کردن تکراری نبودن نام کاربری
    $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check_user->num_rows > 0) {
        $msg = "<div class='alert alert-warning'>این نام کاربری قبلاً ثبت شده است.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, ward_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $username, $password, $role, $ward_id);
        if($stmt->execute()) $msg = "<div class='alert alert-success'>کاربر جدید ساخته شد.</div>";
    }
}

$wards = $conn->query("SELECT * FROM wards");
$users = $conn->query("SELECT users.*, wards.ward_name FROM users LEFT JOIN wards ON users.ward_id = wards.id ORDER BY users.id DESC");
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-users-cog me-2"></i>مدیریت کاربران و دسترسی‌ها</h4>
    </div>

    <?php echo $msg; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">ایجاد کاربر جدید</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-3">
                    <label class="small fw-bold">نام و خانوادگی</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">نام کاربری (انگلیسی)</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">رمز عبور</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">نقش کاربری</label>
                    <select name="role" id="role_select" class="form-select" onchange="toggleWard()">
                        <option value="ward_secretary">منشی بخش</option>
                        <option value="matron">مترون</option>
                        <option value="nutrition_manager">مدیر تغذیه</option>
                        <option value="finance">امور مالی</option>
                        <option value="other_staff">متفرقه (پرسنل)</option> <!-- نقش جدید -->
                        <option value="admin">ادمین کل</option>
                    </select>
                </div>
                <div class="col-md-2" id="ward_div">
                    <label class="small fw-bold">انتخاب بخش</label>
                    <select name="ward_id" class="form-select">
                        <option value="">انتخاب...</option>
                        <?php 
                        $wards->data_seek(0);
                        while($w = $wards->fetch_assoc()): ?>
                            <option value="<?=$w['id']?>"><?=$w['ward_name']?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" name="add_user" class="btn btn-success w-100">ثبت</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">نام</th>
                        <th>نام کاربری</th>
                        <th>نقش</th>
                        <th>بخش</th>
                        <th class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?=$u['name']?></td>
                        <td><span class="badge bg-light text-dark"><?=$u['username']?></span></td>
                        <td>
                            <?php
                            $roles = [
                                'admin' => ['bg-danger', 'ادمین'],
                                'ward_secretary' => ['bg-primary', 'منشی بخش'],
                                'nutrition_manager' => ['bg-success', 'مدیر تغذیه'],
                                'matron' => ['bg-info', 'مترون'],
                                'finance' => ['bg-warning', 'امور مالی'],
                                'other_staff' => ['bg-secondary', 'متفرقه']
                            ];
                            $r = $roles[$u['role']];
                            echo "<span class='badge {$r[0]}'>{$r[1]}</span>";
                            ?>
                        </td>
                        <td><?=$u['ward_name'] ?? '<small class="text-muted">ندارد</small>'?></td>
                        <td class="text-center">
                            <!-- دکمه تغییر رمز -->
                            <button class="btn btn-sm btn-outline-primary" onclick="openPassModal(<?=$u['id']?>, '<?=$u['name']?>')">
                                <i class="fas fa-key"></i>
                            </button>
                            
                            <!-- دکمه حذف -->
                            <?php if($u['username'] !== 'admin'): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?=$u['id']?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal تغییر رمز -->
<div class="modal fade" id="passModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تغییر رمز عبور: <span id="modal_user_name"></span></h5>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modal_user_id">
                <div class="mb-3">
                    <label class="form-label">رمز عبور جدید</label>
                    <input type="password" name="new_password" class="form-control" required minlength="4">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" name="change_pass" class="btn btn-primary">ذخیره رمز جدید</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/sweetalert2@11.js"></script>
<script>
function toggleWard() {
    var role = document.getElementById("role_select").value;
    var wardDiv = document.getElementById("ward_div");
    wardDiv.style.display = (role === "ward_secretary") ? "block" : "none";
}

function openPassModal(id, name) {
    document.getElementById('modal_user_id').value = id;
    document.getElementById('modal_user_name').innerText = name;
    new bootstrap.Modal(document.getElementById('passModal')).show();
}

function confirmDelete(id) {
    Swal.fire({
        title: 'آیا مطمئن هستید؟',
        text: "این کاربر به طور کامل از سیستم حذف خواهد شد!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'لغو',
        confirmButtonText: 'بله، حذف کن'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'manage_users.php?delete_id=' + id;
        }
    })
}

// مقداردهی اولیه وضعیت بخش
toggleWard();
</script>

<?php include("../layout/footer.php"); ?>

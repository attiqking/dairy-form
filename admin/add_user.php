<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'Admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$user = [
    'id' => '',
    'username' => '',
    'password' => '',
    'full_name' => '',
    'role' => 'Worker'
];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user['username'] = $_POST['username'] ?? '';
    $user['password'] = $_POST['password'] ?? '';
    $user['full_name'] = $_POST['full_name'] ?? '';
    $user['role'] = $_POST['role'] ?? 'Worker';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($user['username'])) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($user['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($user['password'])) {
        $errors['password'] = 'Password is required';
    } elseif ($user['password'] !== $confirm_password) {
        $errors['password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", 
            $user['username'],
            $password_hash,
            $user['full_name'],
            $user['role']
        );
        
        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "/admin/users.php");
            exit();
        } else {
            $errors['database'] = 'Error saving user: ' . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New User</h1>
    </div>

    <?php if (!empty($errors['database'])): ?>
    <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                       id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <?php if (isset($errors['username'])): ?>
                <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                       id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                       id="password" name="password" required>
                <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="Manager" <?php echo $user['role'] === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="Worker" <?php echo $user['role'] === 'Worker' ? 'selected' : ''; ?>>Worker</option>
                </select>
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary me-md-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
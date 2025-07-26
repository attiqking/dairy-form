<?php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'Admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Don't allow deleting yourself
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: " . BASE_URL . "/admin/users.php");
    exit();
}

// Fetch all users
$users = [];
$query = "SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">User Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo BASE_URL; ?>/admin/add_user.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus-circle"></i> Add User
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            switch($user['role']) {
                                case 'Admin': echo 'danger'; break;
                                case 'Manager': echo 'primary'; break;
                                case 'Worker': echo 'success'; break;
                                default: echo 'secondary';
                            }
                        ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/admin/edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
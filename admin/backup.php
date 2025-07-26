<?php
// admin/backup.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'Admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$backup_dir = __DIR__ . '/../../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$message = '';
$error = '';

// Handle backup creation
if (isset($_POST['create_backup'])) {
    $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Database credentials
    require __DIR__ . '/../../config/database.php';
    
    // Create backup command
    $command = "mysqldump --user=" . DB_USERNAME . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file;
    
    system($command, $output);
    
    if ($output === 0) {
        $message = 'Backup created successfully: ' . basename($backup_file);
    } else {
        $error = 'Error creating backup';
    }
}

// Handle backup restore
if (isset($_POST['restore_backup'])) {
    $backup_file = $backup_dir . $_POST['backup_file'];
    
    if (file_exists($backup_file)) {
        // Database credentials
        require __DIR__ . '/../../config/database.php';
        
        // Restore command
        $command = "mysql --user=" . DB_USERNAME . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . DB_NAME . " < " . $backup_file;
        
        system($command, $output);
        
        if ($output === 0) {
            $message = 'Backup restored successfully: ' . $_POST['backup_file'];
        } else {
            $error = 'Error restoring backup';
        }
    } else {
        $error = 'Backup file not found';
    }
}

// Get list of backup files
$backup_files = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = $file;
        }
    }
    rsort($backup_files);
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Database Backup & Restore</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Create Backup</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <p>Create a complete backup of the database.</p>
                        <button type="submit" name="create_backup" class="btn btn-primary">Create Backup Now</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Restore Backup</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($backup_files)): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="backup_file" class="form-label">Select Backup File</label>
                            <select class="form-select" id="backup_file" name="backup_file" required>
                                <?php foreach ($backup_files as $file): ?>
                                <option value="<?php echo htmlspecialchars($file); ?>"><?php echo htmlspecialchars($file); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="restore_backup" class="btn btn-warning">Restore Selected Backup</button>
                    </form>
                    <?php else: ?>
                    <p>No backup files found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Available Backups</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($backup_files)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backup_files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td><?php echo round(filesize($backup_dir . $file) / 1024, 2); ?> KB</td>
                            <td><?php echo date('Y-m-d H:i:s', filemtime($backup_dir . $file)); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/download_backup.php?file=<?php echo urlencode($file); ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                    <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($file); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p>No backup files available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
// admin/settings.php
require_once __DIR__ . '/../includes/bootstrap.php';



if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'Admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}


$conn = $database->getConnection();

$settings = [];
$query = "SELECT * FROM settings";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farm_name = $_POST['farm_name'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $milk_price = $_POST['milk_price'] ?? '';
    $notification_email = $_POST['notification_email'] ?? '';
    
    // Validate inputs
    if (empty($farm_name)) {
        $errors['farm_name'] = 'Farm name is required';
    }
    
    if (!is_numeric($milk_price) || $milk_price < 0) {
        $errors['milk_price'] = 'Invalid milk price';
    }
    
    if (empty($errors)) {
        // Update settings in transaction
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            $settings_to_update = [
                'farm_name' => $farm_name,
                'currency' => $currency,
                'milk_price' => $milk_price,
                'notification_email' => $notification_email
            ];
            
            foreach ($settings_to_update as $key => $value) {
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = 'Settings updated successfully';
            
            // Update current settings
            $settings = array_merge($settings, $settings_to_update);
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = 'Error updating settings: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">System Settings</h1>
    </div>

    <?php if (!empty($errors['database'])): ?>
    <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="farm_name" class="form-label">Farm Name</label>
                <input type="text" class="form-control <?php echo isset($errors['farm_name']) ? 'is-invalid' : ''; ?>" 
                       id="farm_name" name="farm_name" value="<?php echo htmlspecialchars($settings['farm_name'] ?? ''); ?>" required>
                <?php if (isset($errors['farm_name'])): ?>
                <div class="invalid-feedback"><?php echo $errors['farm_name']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="currency" class="form-label">Currency</label>
                <select class="form-select" id="currency" name="currency">
                    <option value="$" <?php echo ($settings['currency'] ?? '$') === '$' ? 'selected' : ''; ?>>USD ($)</option>
                    <option value="€" <?php echo ($settings['currency'] ?? '$') === '€' ? 'selected' : ''; ?>>Euro (€)</option>
                    <option value="£" <?php echo ($settings['currency'] ?? '$') === '£' ? 'selected' : ''; ?>>GBP (£)</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="milk_price" class="form-label">Milk Price (per liter)</label>
                <input type="number" step="0.01" class="form-control <?php echo isset($errors['milk_price']) ? 'is-invalid' : ''; ?>" 
                       id="milk_price" name="milk_price" value="<?php echo htmlspecialchars($settings['milk_price'] ?? '0.50'); ?>" required>
                <?php if (isset($errors['milk_price'])): ?>
                <div class="invalid-feedback"><?php echo $errors['milk_price']; ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="notification_email" class="form-label">Notification Email</label>
                <input type="email" class="form-control" id="notification_email" name="notification_email" 
                       value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>">
            </div>
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
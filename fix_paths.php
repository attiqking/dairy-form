<?php
// fix_paths.php - Script to fix all hardcoded paths in the project

$projectRoot = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));

$replacements = [
    // Fix hardcoded URLs
    '"/deepseek_dairy/' => '"<?php echo BASE_URL; ?>/',
    "'\/deepseek_dairy\/" => "'<?php echo BASE_URL; ?>/",
    
    // Fix header redirects
    'header("Location: /deepseek_dairy/' => 'header("Location: " . BASE_URL . "/',
    "header('Location: /deepseek_dairy/" => "header('Location: ' . BASE_URL . '/",
    
    // Fix incorrect auth includes
    'require_once __DIR__ . \'/../../includes/auth.php\';' => 'require_once __DIR__ . \'/../includes/bootstrap.php\';',
    'require_once __DIR__ . \'/../../includes/header.php\';' => '',
    'require_once __DIR__ . \'/../../includes/footer.php\';' => 'require_once __DIR__ . \'/../includes/footer.php\';',
    
    // Fix auth instantiation
    '$auth = new Auth();' => '',
    '$auth->requireLogin();' => 'redirectIfNotLoggedIn();',
    '$auth->isLoggedIn()' => '$auth->isLoggedIn()',
    
    // Fix database instantiation
    '$db = new Database();' => '',
    '$db->getConnection()' => '$database->getConnection()',
];

$fixedFiles = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'fix_paths.php') {
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "Fixed: " . $file->getFilename() . "\n";
            $fixedFiles++;
        }
    }
}

echo "\nCompleted! Fixed $fixedFiles files.\n";

// Now fix specific file patterns that need manual attention
$specificFixes = [
    // Files that need bootstrap include
    '/user/profile.php' => [
        'require_once __DIR__ . \'/../../includes/auth.php\';' => 'require_once __DIR__ . \'/../includes/bootstrap.php\';',
        'if (!isset($_SESSION[\'user_id\'])) {' => 'redirectIfNotLoggedIn();'
    ],
    '/reports/production.php' => [
        'require_once __DIR__ . \'/../../includes/auth.php\';' => 'require_once __DIR__ . \'/../includes/bootstrap.php\';',
        'if (!isset($_SESSION[\'user_id\'])) {' => 'redirectIfNotLoggedIn();'
    ],
    '/reports/financial.php' => [
        'require_once __DIR__ . \'/../../includes/auth.php\';' => 'require_once __DIR__ . \'/../includes/bootstrap.php\';',
        'if (!isset($_SESSION[\'user_id\'])) {' => 'redirectIfNotLoggedIn();'
    ]
];

foreach ($specificFixes as $filePath => $fixes) {
    $fullPath = $projectRoot . $filePath;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        foreach ($fixes as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        file_put_contents($fullPath, $content);
        echo "Applied specific fixes to: $filePath\n";
    }
}

echo "All path fixes completed!\n";
?>

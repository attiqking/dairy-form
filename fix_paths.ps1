# Fix Paths Script for Dairy Farm Project

# Replace hardcoded /deepseek_dairy/ URLs with BASE_URL constant
$files = Get-ChildItem -Path "d:\wordpress\xampp\htdocs\deepseek_dairy" -Recurse -Filter "*.php" -Exclude "*.git*"

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    if ($content) {
        # Replace hardcoded URLs
        $content = $content -replace '"/deepseek_dairy/', '"<?php echo BASE_URL; ?>/'
        $content = $content -replace "'\/deepseek_dairy\/", "'<?php echo BASE_URL; ?>/"
        $content = $content -replace 'Location: /deepseek_dairy/', 'Location: " . BASE_URL . "/'
        
        # Fix include/require paths
        $content = $content -replace '__DIR__ \. \'\/\.\./\.\./includes\/', '__DIR__ . \'/../includes/'
        $content = $content -replace '__DIR__ \. \"\/\.\./\.\./includes\/', '__DIR__ . "/../includes/'
        
        # Replace session header redirects
        $content = $content -replace 'header\("Location: /deepseek_dairy/([^"]+)"\)', 'header("Location: " . BASE_URL . "/$1")'
        
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($file.Name)"
    }
}

Write-Host "Path fixes completed!"

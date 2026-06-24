# Zip plugin for distribution
$pluginName = "cps-bloom-mailer"
$currentDir = Get-Location
$distDir = Join-Path $currentDir "dist"
$tempDir = Join-Path $distDir $pluginName
$zipFile = Join-Path $distDir "$pluginName.zip"

$excludeDirs = @(
    "node_modules", "src", ".git", "dist", ".vscode", ".idea", ".gemini"
)

$excludeFiles = @(
    "composer.json", "composer.lock", "package.json", "package-lock.json",
    "webpack.config.js", "zip-plugin.ps1", "admin.js", "optin.js", 
    "simpleselect.js", "popup.js", "editor.js", "restrict.js",
    ".gitignore", ".DS_Store", "Thumbs.db", ".antigravityignore"
)

$excludeExtensions = @(".log", ".md", ".zip")

# Create dist and temp directory
if (Test-Path $distDir) { Remove-Item -Recurse -Force $distDir }
New-Item -ItemType Directory -Path $tempDir | Out-Null

Write-Host "Copying files..." -ForegroundColor Cyan

Get-ChildItem -Path $currentDir -Recurse | ForEach-Object {
    $item = $_

    # Skip excluded directories (and anything inside them)
    foreach ($dir in $excludeDirs) {
        if ($item.FullName -like "*\$dir\*" -or $item.FullName -like "*\$dir") {
            return
        }
    }

    # Skip excluded file names
    if ($excludeFiles -contains $item.Name) { return }

    # Skip excluded extensions
    if ($excludeExtensions -contains $item.Extension) { return }

    # Build destination path
    $relativePath = $item.FullName.Substring($currentDir.Path.Length + 1)
    $destPath = Join-Path $tempDir $relativePath

    if ($item.PSIsContainer) {
        New-Item -ItemType Directory -Path $destPath -Force | Out-Null
    } else {
        Copy-Item -Path $item.FullName -Destination $destPath -Force
    }
}

Write-Host "Zipping..." -ForegroundColor Cyan
tar -a -c -f $zipFile -C $distDir $pluginName

Write-Host "Cleaning up..." -ForegroundColor Cyan
Remove-Item -Recurse -Force $tempDir

Write-Host "Done! Zip file created at: $zipFile" -ForegroundColor Green
# ABOUTME: PowerShell script for creating comprehensive pre-migration backups on Windows
# ABOUTME: Provides database, configuration, and state backups before schema migration

param(
    [switch]$Verify,
    [switch]$Compress,
    [string]$BackupPath = ""
)

# Configuration
$ErrorActionPreference = "Stop"
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$defaultBackupPath = "./storage/backups/pre-migration-$timestamp"
$backupDir = if ($BackupPath) { $BackupPath } else { $defaultBackupPath }

# Colors for output
$colors = @{
    Info = "Cyan"
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Type = "Info"
    )
    Write-Host $Message -ForegroundColor $colors[$Type]
}

function Get-EnvVariable {
    param([string]$Name, [string]$Default = "")
    
    # Try to read from .env file
    if (Test-Path ".env") {
        $envContent = Get-Content ".env"
        $envVar = $envContent | Where-Object { $_ -match "^$Name=" }
        if ($envVar) {
            return ($envVar -split "=", 2)[1].Trim('"')
        }
    }
    
    # Fallback to environment variable
    $value = [Environment]::GetEnvironmentVariable($Name)
    return if ($value) { $value } else { $Default }
}

function Test-PostgreSQLConnection {
    param(
        [string]$Host,
        [string]$Port,
        [string]$Database,
        [string]$Username,
        [string]$Password
    )
    
    try {
        $env:PGPASSWORD = $Password
        $result = & psql -h $Host -p $Port -U $Username -d $Database -c "SELECT 1;" 2>&1
        Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
        return $LASTEXITCODE -eq 0
    }
    catch {
        Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
        return $false
    }
}

function New-DatabaseBackup {
    param(
        [string]$BackupPath,
        [hashtable]$DbConfig
    )
    
    Write-ColorOutput "💾 Creating database backup..." "Info"
    
    # Test connection first
    if (-not (Test-PostgreSQLConnection -Host $DbConfig.Host -Port $DbConfig.Port -Database $DbConfig.Database -Username $DbConfig.Username -Password $DbConfig.Password)) {
        throw "Cannot connect to PostgreSQL database. Please check your connection settings."
    }
    
    $env:PGPASSWORD = $DbConfig.Password
    
    try {
        # Full backup in custom format
        $customBackupFile = Join-Path $BackupPath "full_database.backup"
        Write-ColorOutput "Creating custom format backup: $customBackupFile" "Info"
        
        $pgDumpArgs = @(
            "-h", $DbConfig.Host,
            "-p", $DbConfig.Port,
            "-U", $DbConfig.Username,
            "-d", $DbConfig.Database,
            "--verbose",
            "--clean",
            "--if-exists",
            "--create",
            "--format=custom",
            "--file=$customBackupFile"
        )
        
        $result = & pg_dump @pgDumpArgs 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw "pg_dump failed: $result"
        }
        
        # SQL format backup (human readable)
        $sqlBackupFile = Join-Path $BackupPath "full_database.sql"
        Write-ColorOutput "Creating SQL format backup: $sqlBackupFile" "Info"
        
        $pgDumpArgs = @(
            "-h", $DbConfig.Host,
            "-p", $DbConfig.Port,
            "-U", $DbConfig.Username,
            "-d", $DbConfig.Database,
            "--verbose",
            "--clean",
            "--if-exists",
            "--create",
            "--format=plain",
            "--file=$sqlBackupFile"
        )
        
        $result = & pg_dump @pgDumpArgs 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-ColorOutput "SQL format backup failed, but custom format succeeded" "Warning"
        }
        
        # Schema-only backup
        $schemaBackupFile = Join-Path $BackupPath "schema_only.sql"
        Write-ColorOutput "Creating schema-only backup: $schemaBackupFile" "Info"
        
        $pgDumpArgs = @(
            "-h", $DbConfig.Host,
            "-p", $DbConfig.Port,
            "-U", $DbConfig.Username,
            "-d", $DbConfig.Database,
            "--verbose",
            "--schema-only",
            "--clean",
            "--if-exists",
            "--create",
            "--file=$schemaBackupFile"
        )
        
        $result = & pg_dump @pgDumpArgs 2>&1
        
        # Individual table backups for critical tables
        $criticalTables = @("tenants", "users", "domains", "graduates", "courses")
        $tablesDir = Join-Path $BackupPath "tables"
        New-Item -ItemType Directory -Path $tablesDir -Force | Out-Null
        
        foreach ($table in $criticalTables) {
            Write-ColorOutput "Backing up table: $table" "Info"
            $tableBackupFile = Join-Path $tablesDir "table_$table.sql"
            
            $pgDumpArgs = @(
                "-h", $DbConfig.Host,
                "-p", $DbConfig.Port,
                "-U", $DbConfig.Username,
                "-d", $DbConfig.Database,
                "--table=$table",
                "--data-only",
                "--disable-triggers",
                "--file=$tableBackupFile"
            )
            
            $result = & pg_dump @pgDumpArgs 2>&1
        }
        
        Write-ColorOutput "✓ Database backup created" "Success"
    }
    finally {
        Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
    }
}

function New-ConfigBackup {
    param([string]$BackupPath)
    
    Write-ColorOutput "⚙️ Creating configuration backup..." "Info"
    
    $configPath = Join-Path $BackupPath "config"
    New-Item -ItemType Directory -Path $configPath -Force | Out-Null
    
    # Copy config directory
    if (Test-Path "config") {
        Copy-Item -Path "config" -Destination (Join-Path $configPath "config") -Recurse -Force
    }
    
    # Copy environment files
    if (Test-Path ".env") {
        Copy-Item -Path ".env" -Destination (Join-Path $configPath ".env.backup") -Force
    }
    
    if (Test-Path ".env.example") {
        Copy-Item -Path ".env.example" -Destination (Join-Path $configPath ".env.example") -Force
    }
    
    # Copy composer files
    if (Test-Path "composer.json") {
        Copy-Item -Path "composer.json" -Destination (Join-Path $configPath "composer.json") -Force
    }
    
    if (Test-Path "composer.lock") {
        Copy-Item -Path "composer.lock" -Destination (Join-Path $configPath "composer.lock") -Force
    }
    
    Write-ColorOutput "✓ Configuration backup created" "Success"
}

function New-MigrationBackup {
    param([string]$BackupPath)
    
    Write-ColorOutput "🔄 Creating migration state backup..." "Info"
    
    $migrationPath = Join-Path $BackupPath "migrations"
    New-Item -ItemType Directory -Path $migrationPath -Force | Out-Null
    
    # Copy migration files
    if (Test-Path "database\migrations") {
        Copy-Item -Path "database\migrations" -Destination $migrationPath -Recurse -Force
    }
    
    Write-ColorOutput "✓ Migration state backup created" "Success"
}

function New-GitStateBackup {
    param([string]$BackupPath)
    
    Write-ColorOutput "📝 Creating Git state backup..." "Info"
    
    $gitPath = Join-Path $BackupPath "git"
    New-Item -ItemType Directory -Path $gitPath -Force | Out-Null
    
    try {
        # Git information
        $gitInfo = @{
            current_commit = (git rev-parse HEAD 2>$null) -replace "`n", ""
            current_branch = (git branch --show-current 2>$null) -replace "`n", ""
            recent_commits = (git log --oneline -10 2>$null) -split "`n"
            git_status = (git status --porcelain 2>$null) -split "`n"
            branches = (git branch -a 2>$null) -split "`n"
            remotes = (git remote -v 2>$null) -split "`n"
        }
        
        $gitInfo | ConvertTo-Json -Depth 3 | Out-File -FilePath (Join-Path $gitPath "git_state.json") -Encoding UTF8
        
        # Save uncommitted changes
        $diff = git diff 2>$null
        if ($diff) {
            $diff | Out-File -FilePath (Join-Path $gitPath "uncommitted_changes.diff") -Encoding UTF8
        }
        
        Write-ColorOutput "✓ Git state backup created" "Success"
    }
    catch {
        Write-ColorOutput "⚠️ Git state backup failed: $($_.Exception.Message)" "Warning"
    }
}

function New-TenantAnalysis {
    param(
        [string]$BackupPath,
        [hashtable]$DbConfig
    )
    
    Write-ColorOutput "🔍 Analyzing tenant data..." "Info"
    
    $env:PGPASSWORD = $DbConfig.Password
    
    try {
        # Get tenant count
        $tenantCountQuery = "SELECT COUNT(*) FROM tenants;"
        $tenantCount = & psql -h $DbConfig.Host -p $DbConfig.Port -U $DbConfig.Username -d $DbConfig.Database -t -c $tenantCountQuery 2>$null
        
        # Get table counts
        $tableCountQuery = "SELECT tablename FROM pg_tables WHERE schemaname = 'public';"
        $tables = & psql -h $DbConfig.Host -p $DbConfig.Port -U $DbConfig.Username -d $DbConfig.Database -t -c $tableCountQuery 2>$null
        
        $tableCounts = @{}
        if ($tables) {
            foreach ($table in $tables) {
                $table = $table.Trim()
                if ($table) {
                    $countQuery = "SELECT COUNT(*) FROM $table;"
                    $count = & psql -h $DbConfig.Host -p $DbConfig.Port -U $DbConfig.Username -d $DbConfig.Database -t -c $countQuery 2>$null
                    $tableCounts[$table] = $count.Trim()
                }
            }
        }
        
        # Get database size
        $sizeQuery = "SELECT pg_size_pretty(pg_database_size(current_database()));"
        $dbSize = & psql -h $DbConfig.Host -p $DbConfig.Port -U $DbConfig.Username -d $DbConfig.Database -t -c $sizeQuery 2>$null
        
        # Scan for models with tenant_id
        $modelsWithTenantId = @()
        if (Test-Path "app\Models") {
            $modelFiles = Get-ChildItem -Path "app\Models" -Filter "*.php" -Recurse
            foreach ($file in $modelFiles) {
                $content = Get-Content $file.FullName -Raw
                if ($content -match "tenant_id") {
                    $modelsWithTenantId += @{
                        file = $file.Name
                        path = $file.FullName
                        has_fillable_tenant_id = $content -match "'tenant_id'"
                        has_tenant_relationship = $content -match "belongsTo\(Tenant::class\)"
                        has_global_scope = $content -match "addGlobalScope"
                    }
                }
            }
        }
        
        $analysis = @{
            tenant_count = $tenantCount.Trim()
            database_size = $dbSize.Trim()
            table_counts = $tableCounts
            models_with_tenant_id = $modelsWithTenantId
            analyzed_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        }
        
        $analysis | ConvertTo-Json -Depth 4 | Out-File -FilePath (Join-Path $BackupPath "tenant_analysis.json") -Encoding UTF8
        
        Write-ColorOutput "✓ Tenant data analysis completed ($($tenantCount.Trim()) tenants found)" "Success"
    }
    catch {
        Write-ColorOutput "⚠️ Tenant analysis failed: $($_.Exception.Message)" "Warning"
    }
    finally {
        Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
    }
}

function New-BackupManifest {
    param([string]$BackupPath)
    
    Write-ColorOutput "📋 Creating backup manifest..." "Info"
    
    $manifest = @{
        created_at = Get-Date -Format "yyyy-MM-ddTHH:mm:ss.fffZ"
        backup_type = "pre-migration"
        php_version = (php --version 2>$null | Select-Object -First 1)
        laravel_version = (php artisan --version 2>$null)
        git_commit = (git rev-parse HEAD 2>$null)
        git_branch = (git branch --show-current 2>$null)
        backup_size = (Get-ChildItem -Path $BackupPath -Recurse | Measure-Object -Property Length -Sum).Sum
        files = @()
    }
    
    # Get file listing
    $files = Get-ChildItem -Path $BackupPath -Recurse -File
    foreach ($file in $files) {
        $relativePath = $file.FullName.Replace($BackupPath + "\", "")
        $manifest.files += @{
            path = $relativePath
            size = $file.Length
            modified = $file.LastWriteTime.ToString("yyyy-MM-dd HH:mm:ss")
        }
    }
    
    $manifest | ConvertTo-Json -Depth 4 | Out-File -FilePath (Join-Path $BackupPath "manifest.json") -Encoding UTF8
    
    Write-ColorOutput "✓ Backup manifest created" "Success"
}

function Test-BackupIntegrity {
    param([string]$BackupPath)
    
    Write-ColorOutput "🔍 Verifying backup integrity..." "Info"
    
    # Verify database backup
    $backupFile = Join-Path $BackupPath "full_database.backup"
    if (Test-Path $backupFile) {
        $result = & pg_restore --list $backupFile 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✓ Database backup verification passed" "Success"
        } else {
            throw "Database backup verification failed: $result"
        }
    }
    
    # Verify essential files exist
    $essentialFiles = @(
        "manifest.json",
        "tenant_analysis.json",
        "config"
    )
    
    foreach ($file in $essentialFiles) {
        $filePath = Join-Path $BackupPath $file
        if (-not (Test-Path $filePath)) {
            throw "Essential backup file missing: $file"
        }
    }
    
    Write-ColorOutput "✓ Backup verification completed" "Success"
}

function Compress-Backup {
    param([string]$BackupPath)
    
    Write-ColorOutput "🗜️ Compressing backup..." "Info"
    
    $archivePath = "$BackupPath.zip"
    
    try {
        Compress-Archive -Path $BackupPath -DestinationPath $archivePath -Force
        $archiveSize = (Get-Item $archivePath).Length
        $formattedSize = Format-FileSize $archiveSize
        Write-ColorOutput "✓ Backup compressed to: $archivePath" "Success"
        Write-ColorOutput "📦 Compressed size: $formattedSize" "Info"
    }
    catch {
        Write-ColorOutput "⚠️ Compression failed: $($_.Exception.Message)" "Warning"
    }
}

function Format-FileSize {
    param([long]$Size)
    
    $units = @("B", "KB", "MB", "GB", "TB")
    $index = 0
    
    while ($Size -gt 1024 -and $index -lt ($units.Length - 1)) {
        $Size = $Size / 1024
        $index++
    }
    
    return "{0:N2} {1}" -f $Size, $units[$index]
}

function Show-BackupSummary {
    param([string]$BackupPath)
    
    Write-ColorOutput "" "Info"
    Write-ColorOutput "📊 Backup Summary:" "Info"
    Write-ColorOutput "================" "Info"
    
    $analysisFile = Join-Path $BackupPath "tenant_analysis.json"
    if (Test-Path $analysisFile) {
        $analysis = Get-Content $analysisFile | ConvertFrom-Json
        Write-ColorOutput "🏢 Tenants: $($analysis.tenant_count)" "Info"
        Write-ColorOutput "📊 Database size: $($analysis.database_size)" "Info"
        Write-ColorOutput "📁 Models with tenant_id: $($analysis.models_with_tenant_id.Count)" "Info"
    }
    
    $backupSize = (Get-ChildItem -Path $BackupPath -Recurse | Measure-Object -Property Length -Sum).Sum
    $formattedSize = Format-FileSize $backupSize
    Write-ColorOutput "💾 Backup size: $formattedSize" "Info"
    
    Write-ColorOutput "" "Info"
    Write-ColorOutput "🔒 Please store this backup securely before proceeding with migration!" "Warning"
}

# Main execution
try {
    Write-ColorOutput "🚀 Starting pre-migration backup process..." "Info"
    
    # Create backup directory
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    Write-ColorOutput "📁 Backup directory: $backupDir" "Info"
    
    # Get database configuration
    $dbConfig = @{
        Host = Get-EnvVariable "DB_HOST" "localhost"
        Port = Get-EnvVariable "DB_PORT" "5432"
        Database = Get-EnvVariable "DB_DATABASE"
        Username = Get-EnvVariable "DB_USERNAME"
        Password = Get-EnvVariable "DB_PASSWORD"
    }
    
    if (-not $dbConfig.Database -or -not $dbConfig.Username) {
        throw "Database configuration is incomplete. Please check your .env file."
    }
    
    # Create backups
    New-DatabaseBackup -BackupPath $backupDir -DbConfig $dbConfig
    New-ConfigBackup -BackupPath $backupDir
    New-MigrationBackup -BackupPath $backupDir
    New-GitStateBackup -BackupPath $backupDir
    New-TenantAnalysis -BackupPath $backupDir -DbConfig $dbConfig
    New-BackupManifest -BackupPath $backupDir
    
    if ($Verify) {
        Test-BackupIntegrity -BackupPath $backupDir
    }
    
    if ($Compress) {
        Compress-Backup -BackupPath $backupDir
    }
    
    Write-ColorOutput "✅ Pre-migration backup completed successfully!" "Success"
    Write-ColorOutput "📍 Backup location: $backupDir" "Info"
    
    Show-BackupSummary -BackupPath $backupDir
}
catch {
    Write-ColorOutput "❌ Backup failed: $($_.Exception.Message)" "Error"
    exit 1
}

Write-ColorOutput "" "Info"
Write-ColorOutput "Next steps:" "Info"
Write-ColorOutput "1. Verify the backup is complete and accessible" "Info"
Write-ColorOutput "2. Store the backup in a secure location" "Info"
Write-ColorOutput "3. Proceed with the schema migration" "Info"
Write-ColorOutput "4. Test the migration thoroughly" "Info"
Write-ColorOutput "5. Keep this backup until migration is confirmed successful" "Info"
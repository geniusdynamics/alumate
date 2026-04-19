# Simple syntax test script
try {
    $content = Get-Content 'start-dev.ps1' -Raw
    $tokens = [System.Management.Automation.PSParser]::Tokenize($content, [ref]$null)
    Write-Host "SUCCESS: Script has valid syntax" -ForegroundColor Green
    Write-Host "Total tokens: $($tokens.Count)" -ForegroundColor Cyan
}
catch {
    Write-Host "SYNTAX ERROR: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Error at line: $($_.InvocationInfo.ScriptLineNumber)" -ForegroundColor Yellow
    exit 1
}
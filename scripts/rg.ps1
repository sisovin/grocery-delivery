$ErrorActionPreference = 'Stop'

$rgPath = 'C:\Users\sisov\.vscode\extensions\github.copilot-chat-0.44.1\node_modules\@github\copilot\sdk\ripgrep\bin\win32-x64\rg.exe'

if (-not (Test-Path $rgPath)) {
  Write-Error "Pinned ripgrep binary not found at $rgPath"
  exit 1
}

& $rgPath @args
exit $LASTEXITCODE

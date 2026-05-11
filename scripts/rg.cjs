const { spawnSync } = require('node:child_process');
const fs = require('node:fs');

const rgPath = 'C:\\Users\\sisov\\.vscode\\extensions\\github.copilot-chat-0.44.1\\node_modules\\@github\\copilot\\sdk\\ripgrep\\bin\\win32-x64\\rg.exe';

if (!fs.existsSync(rgPath)) {
  console.error(`Pinned ripgrep binary not found at ${rgPath}`);
  process.exit(1);
}

const result = spawnSync(rgPath, process.argv.slice(2), { stdio: 'inherit' });

if (result.error) {
  console.error(result.error.message);
  process.exit(1);
}

process.exit(result.status ?? 1);

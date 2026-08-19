import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(process.cwd(), 'dist', 'frontend', 'browser');

if (!fs.existsSync(root)) {
  console.error(`Build folder not found: ${root}`);
  process.exit(1);
}

const forbiddenPatterns = [
  /localhost/i,
  /127\.0\.0\.1/i,
  /:4200\b/i,
  /:8080\b/i,
  /:8081\b/i,
  /\b172\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/i,
];

const ignoredFiles = new Set(['3rdpartylicenses.txt', 'favicon.svg']);
const textExtensions = new Set(['.html', '.js', '.css', '.json', '.txt']);

const failures = [];
const files = [];

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(fullPath);
      continue;
    }

    files.push(fullPath);
  }
}

walk(root);

for (const file of files) {
  const base = path.basename(file);
  const ext = path.extname(file).toLowerCase();

  if (ignoredFiles.has(base) || !textExtensions.has(ext)) {
    continue;
  }

  const content = fs.readFileSync(file, 'utf8');

  for (const pattern of forbiddenPatterns) {
    if (pattern.test(content)) {
      failures.push({
        file: path.relative(root, file),
        pattern: pattern.toString(),
      });
    }
  }
}

const ngswFiles = files.filter((file) => /ngsw|service-worker/i.test(path.basename(file)));

if (ngswFiles.length > 0) {
  failures.push({
    file: 'dist/frontend/browser',
    pattern: 'unexpected service worker artifact',
  });
}

if (failures.length > 0) {
  console.error('Production bundle verification failed:');
  for (const failure of failures) {
    console.error(`- ${failure.file}: ${failure.pattern}`);
  }
  process.exit(1);
}

console.log('Production bundle verification passed.');

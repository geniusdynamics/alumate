import { promises as fs } from 'node:fs'
import path from 'node:path'
import { execSync } from 'node:child_process'

const root = process.cwd()
const sourceRoot = path.join(root, 'resources', 'js')
const exts = new Set(['.ts', '.tsx', '.js', '.jsx', '.vue'])
const importRegex = /(?:import|export)\s+(?:[^'"]*from\s+)?['"]([^'"]+)['"]/g

const failures = []
const scopedPrefixes = [
  'resources/js/app.ts',
  'resources/js/Components/ComponentLibrary/',
  'resources/js/Components/PageBuilder/',
  'resources/js/Components/index.ts',
  'resources/js/Composables/useInsights.ts',
]
const trackedFiles = execSync('git ls-files "resources/js/**"', { cwd: root, encoding: 'utf8' })
  .split('\n')
  .map((entry) => entry.trim())
  .filter(Boolean)
const trackedByLower = new Map(trackedFiles.map((entry) => [entry.toLowerCase(), entry]))

async function listFiles(dir) {
  const entries = await fs.readdir(dir, { withFileTypes: true })
  const files = await Promise.all(entries.map(async (entry) => {
    const fullPath = path.join(dir, entry.name)
    if (entry.isDirectory()) {
      return listFiles(fullPath)
    }
    return exts.has(path.extname(entry.name)) ? [fullPath] : []
  }))
  return files.flat()
}

async function pathExists(filePath) {
  try {
    const stats = await fs.stat(filePath)
    return stats.isFile()
  } catch {
    return false
  }
}

async function resolveImportTarget(fromFile, importPath) {
  if (!importPath.startsWith('.') && !importPath.startsWith('/')) {
    return null
  }

  const basePath = importPath.startsWith('/')
    ? path.join(root, importPath)
    : path.resolve(path.dirname(fromFile), importPath)

  const candidates = [
    basePath,
    ...[...exts].map((ext) => `${basePath}${ext}`),
    ...[...exts].map((ext) => path.join(basePath, `index${ext}`)),
  ]

  for (const candidate of candidates) {
    if (await pathExists(candidate)) {
      return candidate
    }
  }

  return null
}

async function checkFile(file) {
  const relativeFile = path.relative(root, file).split(path.sep).join('/')
  if (!scopedPrefixes.some((prefix) => relativeFile.startsWith(prefix))) {
    return
  }

  const content = await fs.readFile(file, 'utf8')
  const imports = [...content.matchAll(importRegex)].map((match) => match[1])

  for (const importPath of imports) {
    const target = await resolveImportTarget(file, importPath)
    if (!target) {
      continue
    }

    const relativeTarget = path.relative(root, target).split(path.sep).join('/')
    if (!relativeTarget.startsWith('resources/js/')) {
      continue
    }
    const canonicalTarget = trackedByLower.get(relativeTarget.toLowerCase())

    if (!canonicalTarget) {
      failures.push(`${path.relative(root, file)} -> ${importPath}`)
      continue
    }

    if (canonicalTarget !== relativeTarget) {
      failures.push(`${path.relative(root, file)} -> ${importPath}`)
    }
  }
}

const files = await listFiles(sourceRoot)
for (const file of files) {
  await checkFile(file)
}

if (failures.length > 0) {
  console.error('Import path casing check failed:')
  failures.forEach((failure) => console.error(`- ${failure}`))
  process.exit(1)
}

console.log(`Import path casing check passed for ${files.length} files.`)

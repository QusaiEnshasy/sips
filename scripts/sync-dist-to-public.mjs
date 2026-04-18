import fs from 'node:fs'
import path from 'node:path'

const root = process.cwd()
const sourceDir = path.join(root, 'dist', 'assets')
const targetDir = path.join(root, 'public', 'assets')
const manifestSourceDir = path.join(root, 'dist', '.vite')
const manifestTargetDir = path.join(root, 'public', '.vite')

if (!fs.existsSync(sourceDir)) {
  console.error(`Missing source directory: ${sourceDir}`)
  process.exit(1)
}

const copyDirectory = (fromDir, toDir) => {
  if (!fs.existsSync(fromDir)) {
    return
  }

  fs.mkdirSync(toDir, { recursive: true })

  for (const entry of fs.readdirSync(fromDir, { withFileTypes: true })) {
    const sourcePath = path.join(fromDir, entry.name)
    const targetPath = path.join(toDir, entry.name)

    if (entry.isDirectory()) {
      copyDirectory(sourcePath, targetPath)
      continue
    }

    if (entry.isFile()) {
      fs.copyFileSync(sourcePath, targetPath)
    }
  }
}

copyDirectory(sourceDir, targetDir)
copyDirectory(manifestSourceDir, manifestTargetDir)

console.log(`Synced built assets from ${sourceDir} to ${targetDir}`)

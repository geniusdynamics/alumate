const fs = require('fs');
const path = require('path');

// Function to recursively find all .vue files
function findVueFiles(dir, fileList = []) {
    const files = readdirSync(dir);
    
    files.forEach(file => {
        const filePath = join(dir, file);
        const stat = statSync(filePath);
        
        if (stat.isDirectory()) {
            findVueFiles(filePath, fileList);
        } else if (file.endsWith('.vue')) {
            fileList.push(filePath);
        }
    });
    
    return fileList;
}

// Function to fix script setup lang attributes
function fixLangAttributes() {
    const vueFiles = findVueFiles('resources/js/Pages');
    let fixedCount = 0;
    
    vueFiles.forEach(file => {
        try {
            let content = readFileSync(file, 'utf8');
            
            // Check if file has <script setup> without lang="ts"
            if (content.includes('<script setup>') && !content.includes('<script setup lang="ts">')) {
                content = content.replace('<script setup>', '<script setup lang="ts">');
                writeFileSync(file, content, 'utf8');
                console.log(`✓ Fixed: ${file}`);
                fixedCount++;
            }
        } catch (error) {
            console.error(`Error processing ${file}:`, error.message);
        }
    });
    
    console.log(`\nFixed ${fixedCount} files with missing lang="ts" attributes`);
}

// Run the fix
fixLangAttributes();
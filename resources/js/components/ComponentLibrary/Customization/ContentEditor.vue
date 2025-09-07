<template>
  <div class="content-editor" role="region" aria-labelledby="content-editor-label">
    <!-- Header -->
    <div id="content-editor-label" class="sr-only">Content Editor</div>

    <!-- Content Blocks List -->
    <div class="content-blocks-list">
      <div class="blocks-header">
        <h3 class="blocks-title">Content Blocks</h3>
        <button
          @click="addNewBlock('text')"
          class="btn-add-block"
          aria-label="Add new text block"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Add Block
        </button>
      </div>

      <div class="blocks-container" ref="blocksContainer">
        <!-- Content Blocks -->
        <div
          v-for="(block, index) in contentBlocks"
          :key="block.id"
          class="content-block"
          :class="{
            selected: selectedBlockId === block.id,
            dragging: isDraggingBlock === block.id
          }"
          @click="selectBlock(block.id)"
          role="button"
          tabindex="0"
          :aria-label="`Edit ${block.type} block`"
          @keydown.enter="selectBlock(block.id)"
          @keydown.space.prevent="selectBlock(block.id)"
        >
          <!-- Block Header -->
          <div class="block-header">
            <div class="block-info">
              <div class="block-type-icon">
                <component :is="getBlockIcon(block.type)" class="w-4 h-4" />
              </div>
              <span class="block-type-label">{{ getBlockTypeLabel(block.type) }}</span>
              <span class="block-id">#{{ index + 1 }}</span>
            </div>

            <div class="block-actions">
              <button
                @click.stop="duplicateBlock(block.id)"
                class="block-action-btn"
                :aria-label="`Duplicate ${block.type} block`"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                  <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" stroke="currentColor" stroke-width="2"/>
                </svg>
              </button>

              <button
                @click.stop="deleteBlock(block.id)"
                class="block-action-btn delete"
                :aria-label="`Delete ${block.type} block`"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                  <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3" stroke="currentColor" stroke-width="2"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Block Preview -->
          <div class="block-preview">
            <template v-if="block.type === 'text'">
              <div class="text-preview" v-html="getTextPreview(block)"></div>
            </template>

            <template v-else-if="block.type === 'image'">
              <div class="image-preview">
                <img
                  v-if="block.data.url"
                  :src="block.data.url"
                  :alt="block.data.alt || 'Image preview'"
                  class="image-thumb"
                />
                <div v-else class="image-placeholder">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </div>
                <div class="image-info">
                  <span class="image-filename">{{ block.data.filename || 'No image selected' }}</span>
                  <span v-if="block.data.size" class="image-size">{{ formatFileSize(block.data.size) }}</span>
                </div>
              </div>
            </template>

            <template v-else-if="block.type === 'button'">
              <div class="button-preview">
                <button class="btn-preview" :style="getButtonStyles(block)">{{ block.data.text || 'Button Text' }}</button>
              </div>
            </template>

            <template v-else-if="block.type === 'video'">
              <div class="video-preview">
                <div class="video-placeholder">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                    <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" stroke="currentColor" stroke-width="2"/>
                  </svg>
                  <span>{{ block.data.transcript ? 'Video with captions' : 'Video' }}</span>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="generic-preview">
                <span>{{ getBlockTypeLabel(block.type) }} Block</span>
              </div>
            </template>
          </div>

          <!-- Drag Handle -->
          <div class="block-drag-handle" @mousedown="startDrag(block.id)" @touchstart="startDrag(block.id)">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M4 8h16M4 16h16" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
        </div>

        <!-- Add Block Placeholder -->
        <div class="add-block-placeholder" @click="showBlockTypesModal = true">
          <div class="placeholder-content">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Add Content Block</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Content Editor Panel -->
    <div v-if="selectedBlock" class="content-editor-panel">
      <div class="panel-header">
        <h4 class="panel-title">Edit {{ getBlockTypeLabel(selectedBlock.type) }}</h4>
        <button @click="closeEditor" class="panel-close" aria-label="Close editor">&times;</button>
      </div>

      <div class="panel-content">
        <!-- Text Block Editor -->
        <template v-if="selectedBlock.type === 'text'">
          <div class="text-editor">
            <div class="editor-toolbar">
              <button
                v-for="format in textFormats"
                :key="format.id"
                @click="applyTextFormat(format.id)"
                :class="{ active: activeFormats.includes(format.id) }"
                class="format-btn"
                :aria-label="format.label"
                :title="format.label"
              >
                <component :is="format.icon" class="w-4 h-4" />
              </button>
            </div>

            <div
              class="text-input"
              contenteditable="true"
              :contenteditable="true"
              @input="updateTextContent"
              @keyup="handleKeyUp"
              @keydown="handleKeyDown"
              v-html="selectedBlock.data.html || selectedBlock.data.text"
              ref="textEditor"
              aria-label="Text content editor"
            ></div>

            <div class="text-settings">
              <div class="setting-group">
                <label class="setting-label">Text Alignment</label>
                <div class="alignment-options">
                  <button
                    v-for="align in ['left', 'center', 'right']"
                    :key="align"
                    @click="updateTextAlignment(align)"
                    :class="{ active: selectedBlock.data.alignment === align }"
                    class="align-btn"
                    :aria-label="`Align text ${align}`"
                  >
                    <svg v-if="align === 'left'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M4 6h16M4 12h8M4 18h12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <svg v-else-if="align === 'center'" width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M4 6h16M8 12h8M6 18h12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none">
                      <path d="M4 6h16M12 12h8M8 18h12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Image Block Editor -->
        <template v-else-if="selectedBlock.type === 'image'">
          <div class="image-editor">
            <div class="image-upload-area" @click="openImageSelector" @keydown.enter="openImageSelector" @keydown.space.prevent="openImageSelector" tabindex="0" role="button">
              <div v-if="!selectedBlock.data.url" class="upload-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                  <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>Click to select image</p>
                <p class="upload-hint">Or drag and drop an image here</p>
              </div>

              <div v-else class="image-display">
                <img :src="selectedBlock.data.url" :alt="selectedBlock.data.alt || ''" class="selected-image" />
                <div class="image-overlay">
                  <button @click.stop="replaceImage" class="replace-btn">Replace</button>
                </div>
              </div>
            </div>

            <div class="image-settings">
              <div class="setting-group">
                <label for="image-alt-input" class="setting-label">Alt Text</label>
                <input
                  id="image-alt-input"
                  v-model="selectedBlock.data.alt"
                  type="text"
                  class="setting-input"
                  placeholder="Describe the image for accessibility"
                  aria-describedby="alt-description"
                />
                <div id="alt-description" class="sr-only">Alternative text that describes the image for screen readers</div>
              </div>

              <div class="setting-group">
                <label for="image-caption-input" class="setting-label">Caption (optional)</label>
                <input
                  id="image-caption-input"
                  v-model="selectedBlock.data.caption"
                  type="text"
                  class="setting-input"
                  placeholder="Caption text below the image"
                />
              </div>

              <div class="setting-group">
                <label for="image-link-input" class="setting-label">Link URL (optional)</label>
                <input
                  id="image-link-input"
                  v-model="selectedBlock.data.link"
                  type="url"
                  class="setting-input"
                  placeholder="https://example.com"
                />
              </div>

              <div class="setting-group">
                <label class="setting-label">Image Fit</label>
                <select v-model="selectedBlock.data.fit" class="setting-select">
                  <option value="cover">Cover</option>
                  <option value="contain">Contain</option>
                  <option value="fill">Fill</option>
                  <option value="scale">Scale</option>
                </select>
              </div>
            </div>
          </div>
        </template>

        <!-- Button Block Editor -->
        <template v-else-if="selectedBlock.type === 'button'">
          <div class="button-editor">
            <div class="setting-group">
              <label for="button-text-input" class="setting-label">Button Text</label>
              <input
                id="button-text-input"
                v-model="selectedBlock.data.text"
                type="text"
                class="setting-input"
                placeholder="Enter button text"
              />
            </div>

            <div class="setting-group">
              <label for="button-url-input" class="setting-label">URL</label>
              <input
                id="button-url-input"
                v-model="selectedBlock.data.url"
                type="url"
                class="setting-input"
                placeholder="https://example.com"
              />
            </div>

            <div class="setting-group">
              <label class="setting-label">Button Style</label>
              <select v-model="selectedBlock.data.style" class="setting-select">
                <option value="primary">Primary</option>
                <option value="secondary">Secondary</option>
                <option value="outline">Outline</option>
                <option value="ghost">Ghost</option>
              </select>
            </div>

            <div class="setting-group">
              <label class="setting-label">Button Size</label>
              <select v-model="selectedBlock.data.size" class="setting-select">
                <option value="sm">Small</option>
                <option value="md">Medium</option>
                <option value="lg">Large</option>
                <option value="xl">Extra Large</option>
              </select>
            </div>

            <div class="setting-group">
              <label class="setting-label">Button Preview</label>
              <div class="button-styles-preview">
                <button :class="`btn-${selectedBlock.data.style} btn-${selectedBlock.data.size}`">
                  {{ selectedBlock.data.text || 'Button Text' }}
                </button>
              </div>
            </div>
          </div>
        </template>

        <!-- Video Block Editor -->
        <template v-else-if="selectedBlock.type === 'video'">
          <div class="video-editor">
            <div class="video-upload-area" @click="openVideoSelector">
              <div v-if="!selectedBlock.data.asset" class="upload-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                  <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>Click to select video</p>
                <p class="upload-hint">Or drag and drop a video file</p>
              </div>
            </div>

            <div v-if="selectedBlock.data.asset" class="video-settings">
              <div class="setting-group">
                <label class="setting-label">Video Settings</label>
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input v-model="selectedBlock.data.autoplay" type="checkbox" />
                    Autoplay
                  </label>
                  <label class="checkbox-label">
                    <input v-model="selectedBlock.data.muted" type="checkbox" />
                    Muted by default
                  </label>
                  <label class="checkbox-label">
                    <input v-model="selectedBlock.data.showControls" type="checkbox" />
                    Show controls
                  </label>
                  <label class="checkbox-label">
                    <input v-model="selectedBlock.data.showCaptions" type="checkbox" />
                    Show captions
                  </label>
                </div>
              </div>

              <div v-if="selectedBlock.data.transcript" class="setting-group">
                <label class="setting-label">Transcript</label>
                <div class="transcript-preview">
                  <p>{{ selectedBlock.data.transcript.substring(0, 150) }}...</p>
                  <button @click="editTranscript" class="edit-transcript">Edit Transcript</button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Block Types Modal -->
    <div v-if="showBlockTypesModal" class="modal-overlay" @click="showBlockTypesModal = false">
      <div class="block-types-modal" @click.stop>
        <div class="modal-header">
          <h3>Add New Block</h3>
          <button @click="showBlockTypesModal = false" class="modal-close">&times;</button>
        </div>

        <div class="block-types-grid">
          <button
            v-for="type in availableBlockTypes"
            :key="type.id"
            @click="addNewBlock(type.id)"
            class="block-type-btn"
            :aria-label="`Add ${type.label} block`"
          >
            <component :is="type.icon" class="w-8 h-8 mb-2" />
            <span class="block-type-name">{{ type.label }}</span>
            <span class="block-type-desc">{{ type.description }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Hidden file input -->
    <input
      ref="fileInput"
      type="file"
      class="hidden-input"
      @change="handleFileSelection"
      accept="image/*"
      aria-label="Image file selection"
    />

    <!-- Accessibility announcements -->
    <div class="sr-only" aria-live="polite" aria-atomic="true">
      {{ accessibilityMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { ContentBlock, TextBlock, ImageBlock, ButtonBlock } from '@/types/components'

// Template refs
const blocksContainer = ref<HTMLElement>()
const textEditor = ref<HTMLElement>()
const fileInput = ref<HTMLInputElement>()

// Component state
const contentBlocks = ref<ContentBlock[]>([])
const selectedBlockId = ref<string | null>(null)
const showBlockTypesModal = ref(false)
const isDraggingBlock = ref<string | null>(null)
const accessibilityMessage = ref('')

// Text editor state
const activeFormats = ref<string[]>([])

// Available block types
const availableBlockTypes = [
  {
    id: 'text',
    label: 'Text',
    description: 'Add paragraphs and headings',
    icon: 'text-icon'
  },
  {
    id: 'image',
    label: 'Image',
    description: 'Upload and display images',
    icon: 'image-icon'
  },
  {
    id: 'button',
    label: 'Button',
    description: 'Call-to-action buttons',
    icon: 'button-icon'
  },
  {
    id: 'video',
    label: 'Video',
    description: 'Embed video content',
    icon: 'video-icon'
  },
  {
    id: 'divider',
    label: 'Divider',
    description: 'Visual separation line',
    icon: 'divider-icon'
  },
  {
    id: 'spacer',
    label: 'Spacer',
    description: 'Add vertical space',
    icon: 'spacer-icon'
  }
]

// Text formatting options
const textFormats = [
  { id: 'bold', label: 'Bold', icon: 'bold-icon', command: 'bold' },
  { id: 'italic', label: 'Italic', icon: 'italic-icon', command: 'italic' },
  { id: 'underline', label: 'Underline', icon: 'underline-icon', command: 'underline' },
  { id: 'h1', label: 'Heading 1', icon: 'h1-icon', command: 'formatBlock', arg: 'h1' },
  { id: 'h2', label: 'Heading 2', icon: 'h2-icon', command: 'formatBlock', arg: 'h2' },
  { id: 'h3', label: 'Heading 3', icon: 'h3-icon', command: 'formatBlock', arg: 'h3' },
  { id: 'ul', label: 'Bullet List', icon: 'ul-icon', command: 'insertUnorderedList' },
  { id: 'ol', label: 'Numbered List', icon: 'ol-icon', command: 'insertOrderedList' }
]

// Computed properties
const selectedBlock = computed(() => {
  return contentBlocks.value.find(block => block.id === selectedBlockId.value) || null
})

// Methods
const addNewBlock = (type: string) => {
  const newBlock: ContentBlock = {
    id: generateBlockId(),
    type: type as ContentBlock['type'],
    data: getDefaultBlockData(type),
    position: contentBlocks.value.length,
    isVisible: true,
    animationSettings: {
      enabled: false,
      type: 'fade',
      delay: 0,
      duration: 300
    }
  }

  contentBlocks.value.push(newBlock)
  selectBlock(newBlock.id)
  showBlockTypesModal.value = false
  accessibilityMessage.value = `New ${type} block added`
}

const getDefaultBlockData = (type: string) => {
  switch (type) {
    case 'text':
      return {
        html: '<p>Enter your text here...</p>',
        text: 'Enter your text here...',
        format: 'paragraph',
        alignment: 'left',
        color: '#000000',
        size: 16
      }
    case 'image':
      return {
        url: '',
        alt: '',
        caption: '',
        filename: '',
        size: 0,
        fit: 'cover'
      }
    case 'button':
      return {
        text: 'Button Text',
        url: '',
        style: 'primary',
        size: 'md',
        openInNewTab: false
      }
    case 'video':
      return {
        asset: null,
        autoplay: false,
        muted: true,
        showControls: true,
        showCaptions: false,
        transcript: ''
      }
    default:
      return {}
  }
}

const generateBlockId = (): string => {
  return `block_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

const selectBlock = (blockId: string) => {
  selectedBlockId.value = blockId
  accessibilityMessage.value = `${getBlockTypeLabel(selectedBlock.value?.type || 'text')} block selected`
}

const closeEditor = () => {
  selectedBlockId.value = null
  accessibilityMessage.value = 'Editor closed'
}

const duplicateBlock = (blockId: string) => {
  const block = contentBlocks.value.find(b => b.id === blockId)
  if (!block) return

  const duplicatedBlock: ContentBlock = {
    ...block,
    id: generateBlockId(),
    position: contentBlocks.value.length,
    data: { ...block.data } // Deep copy
  }

  contentBlocks.value.push(duplicatedBlock)
  selectBlock(duplicatedBlock.id)
  accessibilityMessage.value = `${getBlockTypeLabel(block.type)} block duplicated`
}

const deleteBlock = (blockId: string) => {
  const block = contentBlocks.value.find(b => b.id === blockId)
  if (!block) return

  const index = contentBlocks.value.findIndex(b => b.id === blockId)
  contentBlocks.value.splice(index, 1)

  if (selectedBlockId.value === blockId) {
    selectedBlockId.value = null
  }

  accessibilityMessage.value = `${getBlockTypeLabel(block.type)} block deleted`
}

const getBlockTypeLabel = (type: string): string => {
  return availableBlockTypes.find(t => t.id === type)?.label || type
}

const getBlockIcon = (type: string) => {
  const icons = {
    text: 'TypeIcon',
    image: 'PhotographIcon',
    button: 'CursorClickIcon',
    video: 'VideoCameraIcon',
    divider: 'MinusIcon',
    spacer: 'ArrowsExpandIcon'
  }
  return icons[type as keyof typeof icons] || 'CubeIcon'
}

// Text editor methods
const applyTextFormat = (formatId: string) => {
  if (!textEditor.value) return

  const format = textFormats.find(f => f.id === formatId)
  if (!format) return

  document.execCommand(format.command, false, format.arg)
  updateActiveFormats()
}

const updateTextContent = () => {
  if (!textEditor.value || !selectedBlock.value) return

  const html = textEditor.value.innerHTML
  const text = textEditor.value.textContent || ''

  selectedBlock.value.data.html = html
  selectedBlock.value.data.text = text

  // Emit content change event
  emit('content-changed', {
    blockId: selectedBlock.value.id,
    content: { html, text }
  })
}

const updateTextAlignment = (alignment: string) => {
  if (!selectedBlock.value) return

  selectedBlock.value.data.alignment = alignment
  selectedBlock.value.data.html = `<div style="text-align: ${alignment};">${selectedBlock.value.data.html}</div>`

  emit('content-changed', {
    blockId: selectedBlock.value.id,
    alignment
  })
}

const handleKeyUp = (event: KeyboardEvent) => {
  updateActiveFormats()
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key.toLowerCase()) {
      case 'b':
        event.preventDefault()
        applyTextFormat('bold')
        break
      case 'i':
        event.preventDefault()
        applyTextFormat('italic')
        break
      case 'u':
        event.preventDefault()
        applyTextFormat('underline')
        break
    }
  }
}

const updateActiveFormats = () => {
  activeFormats.value = textFormats
    .filter(format => document.queryCommandState(format.command))
    .map(format => format.id)
}

// Image methods
const openImageSelector = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const handleFileSelection = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file || !selectedBlock.value) return

  // Handle file upload (mock implementation)
  const reader = new FileReader()
  reader.onload = () => {
    selectedBlock.value!.data.url = reader.result as string
    selectedBlock.value!.data.filename = file.name
    selectedBlock.value!.data.size = file.size

    emit('file-uploaded', {
      blockId: selectedBlock.value!.id,
      file,
      url: reader.result
    })

    accessibilityMessage.value = `Image ${file.name} uploaded successfully`
  }
  reader.readAsDataURL(file)
}

const replaceImage = () => {
  openImageSelector()
}

const getTextPreview = (block: ContentBlock): string => {
  const text = block.data.text || block.data.html?.replace(/<[^>]*>/g, '') || ''
  return text.length > 100 ? text.substring(0, 100) + '...' : text
}

const getButtonStyles = (block: ContentBlock) => {
  const styles = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    secondary: 'bg-gray-600 text-white hover:bg-gray-700',
    outline: 'border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white',
    ghost: 'text-blue-600 hover:bg-blue-100'
  }
  const sizeStyles = {
    sm: 'px-3 py-1 text-sm',
    md: 'px-4 py-2',
    lg: 'px-6 py-3 text-lg',
    xl: 'px-8 py-4 text-xl'
  }

  return `${styles[block.data.style as keyof typeof styles] || styles.primary} ${sizeStyles[block.data.size as keyof typeof sizeStyles] || sizeStyles.md} rounded-md transition-colors`
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Video methods
const openVideoSelector = () => {
  // Mock implementation - in real app would open file picker or URL input
  console.log('Open video selector')
}

// Drag functionality
const startDrag = (blockId: string) => {
  isDraggingBlock.value = blockId

  const startPos = { x: 0, y: 0 }
  const handleMouseMove = (e: MouseEvent) => {
    // Handle drag logic
  }

  const handleMouseUp = () => {
    isDraggingBlock.value = null
    document.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseup', handleMouseMove)
  }

  document.addEventListener('mousemove', handleMouseMove)
  document.addEventListener('mouseup', handleMouseUp)
}

// Props & Emits
interface Props {
  initialBlocks?: ContentBlock[]
}

interface Emits {
  contentChanged: [data: { blockId: string; content: any }]
  fileUploaded: [data: { blockId: string; file: File; url: string }]
  blockOrderChanged: [blocks: ContentBlock[]]
  blockDeleted: [blockId: string]
  blockDuplicated: [originalBlockId: string; newBlockId: string]
}

const props = withDefaults(defineProps<Props>(), {
  initialBlocks: () => []
})

const emit = defineEmits<Emits>()

// Lifecycle
onMounted(() => {
  if (props.initialBlocks.length > 0) {
    contentBlocks.value = [...props.initialBlocks]
  }
})

onBeforeUnmount(() => {
  // Clean up event listeners if needed
})
</script>

<style scoped>
.content-editor {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm min-h-screen;
}

.content-blocks-list {
  @apply p-4;
}

.blocks-header {
  @apply flex items-center justify-between mb-4;
}

.blocks-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.btn-add-block {
  @apply flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors;
}

.blocks-container {
  @apply space-y-4;
}

.content-block {
  @apply relative bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-transparent transition-all duration-200 cursor-pointer;
}

.content-block:hover {
  @apply border-gray-200 dark:border-gray-600;
}

.content-block.selected {
  @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.content-block.dragging {
  @apply opacity-50 transform scale-105;
}

.block-header {
  @apply flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-600 rounded-t-lg;
}

.block-info {
  @apply flex items-center gap-3;
}

.block-type-icon {
  @apply text-gray-600 dark:text-gray-400;
}

.block-type-label {
  @apply font-medium text-gray-900 dark:text-white;
}

.block-id {
  @apply text-xs text-gray-500 dark:text-gray-400 font-mono;
}

.block-actions {
  @apply flex gap-1;
}

.block-action-btn {
  @apply p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100;
}

.block-action-btn.delete {
  @apply text-red-600 hover:text-red-700;
}

.block-preview {
  @apply p-4 min-h-16;
}

.text-preview {
  @apply text-sm text-gray-700 dark:text-gray-300 line-clamp-3;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.image-preview {
  @apply flex items-center gap-3;
}

.image-thumb {
  @apply w-16 h-16 object-cover rounded border;
}

.image-placeholder {
  @apply w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded border border-dashed border-gray-400 dark:border-gray-500 flex items-center justify-center text-gray-400;
}

.image-info {
  @apply flex flex-col;
}

.image-filename {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.image-size {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.button-preview {
  @apply flex justify-center;
}

.btn-preview {
  @apply px-4 py-2 bg-blue-600 text-white rounded-md text-sm;
}

.video-preview,
.generic-preview {
  @apply flex items-center gap-3 text-gray-600 dark:text-gray-400;
}

.add-block-placeholder {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 hover:border-gray-400 dark:hover:border-gray-500 transition-colors cursor-pointer;
}

.placeholder-content {
  @apply flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500;
}

.block-drag-handle {
  @apply absolute top-2 right-2 p-1 bg-gray-200 dark:bg-gray-700 rounded cursor-move opacity-0 hover:opacity-100 transition-opacity;
}

.content-block:hover .block-drag-handle {
  @apply opacity-100;
}

/* Content Editor Panel */
.content-editor-panel {
  @apply border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800;
}

.panel-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.panel-title {
  @apply font-semibold text-gray-900 dark:text-white;
}

.panel-close {
  @apply text-xl text-gray-400 hover:text-gray-600 transition-colors;
}

.panel-content {
  @apply p-4 max-h-96 overflow-y-auto;
}

/* Text Editor */
.text-editor {
  @apply space-y-4;
}

.editor-toolbar {
  @apply flex gap-1 p-2 bg-gray-50 dark:bg-gray-700 rounded border;
}

.format-btn {
  @apply p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-gray-600 dark:text-gray-400;
}

.format-btn.active {
  @apply bg-blue-600 text-white;
}

.text-input {
  @apply w-full min-h-32 p-3 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-y;
}

.text-settings {
  @apply space-y-3;
}

.alignment-options {
  @apply flex gap-1;
}

.align-btn {
  @apply p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors;
}

.align-btn.active {
  @apply bg-blue-600 text-white;
}

/* Image Editor */
.image-editor {
  @apply space-y-4;
}

.image-upload-area {
  @apply border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 hover:border-gray-400 dark:hover:border-gray-500 transition-colors cursor-pointer text-center;
}

.upload-placeholder {
  @apply space-y-2;
}

.upload-hint {
  @apply text-sm;
}

.image-display {
  @apply relative;
}

.selected-image {
  @apply w-full h-48 object-cover rounded border;
}

.image-overlay {
  @apply absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity rounded;
}

.replace-btn {
  @apply px-4 py-2 bg-white text-black rounded font-medium hover:bg-gray-100 transition-colors;
}

/* Button Editor */
.button-editor {
  @apply space-y-4;
}

.button-styles-preview {
  @apply p-4 bg-gray-100 dark:bg-gray-700 rounded flex justify-center;
}

/* Video Editor */
.video-editor {
  @apply space-y-4;
}

.video-settings {
  @apply space-y-4;
}

.checkbox-group {
  @apply space-y-2;
}

.checkbox-label {
  @apply flex items-center gap-2;
}

.transcript-preview {
  @apply p-3 bg-gray-50 dark:bg-gray-700 rounded border;
}

.edit-transcript {
  @apply mt-2 px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition-colors;
}

/* Settings */
.setting-group {
  @apply space-y-2;
}

.setting-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.setting-input,
.setting-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500;
}

/* Modal */
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50;
}

.block-types-modal,
.font-preview-modal {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md;
}

.modal-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.modal-close {
  @apply text-xl text-gray-400 hover:text-gray-600;
}

.block-types-grid {
  @apply grid grid-cols-2 gap-4 p-4;
}

.block-type-btn {
  @apply p-4 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-center;
}

.block-type-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.block-type-desc {
  @apply text-sm text-gray-600 dark:text-gray-400 mt-1;
}

/* Responsive */
@media (max-width: 640px) {
  .content-editor {
    @apply rounded-none;
  }

  .blocks-header {
    @apply flex-col gap-3;
  }

  .panel-content {
    @apply max-h-64;
  }

  .editor-toolbar {
    @apply flex-wrap justify-center;
  }

  .block-actions {
    @apply flex-col gap-2;
  }
}

/* Utilities */
.hidden-input {
  @apply hidden;
}

.line-clamp-3 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

/* Animation */
.content-block {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .text-preview {
    @apply text-gray-300;
  }

  .image-thumb {
    @apply border-gray-600;
  }

  .image-placeholder {
    @apply bg-gray-600 border-gray-500;
  }

  .add-block-placeholder {
    @apply border-gray-600 hover:border-gray-500;
  }

  .placeholder-content {
    @apply text-gray-500;
  }

  .block-preview {
    @apply bg-gray-800;
  }

  .block-header {
    @apply bg-gray-700;
  }

  .content-editor-panel {
    @apply bg-gray-800;
  }

  .text-input {
    @apply bg-gray-700 text-gray-300 border-gray-600;
  }

  .editor-toolbar {
    @apply bg-gray-700 border-gray-600;
  }

  .format-btn {
    @apply text-gray-400;
  }

  .setting-input,
  .setting-select {
    @apply bg-gray-700 text-gray-300 border-gray-600;
  }

  .modal-overlay {
    @apply bg-opacity-75;
  }

  .block-type-btn {
    @apply border-gray-600 hover:border-blue-500;
  }

  .image-overlay {
    @apply bg-opacity-60;
  }

  .video-preview,
  .generic-preview {
    @apply text-gray-400;
  }
}

/* High contrast */
@media (prefers-contrast: high) {
  .content-block.selected {
    @apply border-blue-600;
  }

  .format-btn.active {
    @apply bg-blue-700;
  }

  .block-action-btn {
    @apply border;
  }

  .setting-input:focus,
  .setting-select:focus {
    @apply border-blue-700 ring-blue-700;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .content-block {
    animation: none;
  }

  .format-btn,
  .block-action-btn,
  .panel-close {
    transition: none;
  }
}

/* Screen reader only */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
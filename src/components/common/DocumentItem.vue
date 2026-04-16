<template>
  <div
    class="document-item"
    :class="{ 'verified': verified, 'clickable': clickable }"
    @click="clickable ? $emit('click') : null"
  >
    <div class="d-flex align-items-center gap-3">
      <!-- Document icon based on type -->
      <div class="document-icon" :class="iconClass">
        <i :class="getDocumentIcon"></i>
      </div>

      <!-- Document info -->
      <div class="document-info">
        <p class="mb-0 fw-bold small">{{ fileName }}</p>
        <div class="document-meta">
          <small class="text-muted">{{ fileSize }}</small>
          <span class="meta-separator">•</span>
          <small class="text-muted">{{ fileType }}</small>
        </div>
      </div>
    </div>

    <!-- Status and actions -->
    <div class="document-actions">
      <!-- Verified badge -->
      <div v-if="verified" class="verified-badge">
        <i class="bi bi-check2-circle me-1"></i>
        <span>{{ t('verified') }}</span>
      </div>

      <!-- Action buttons -->
      <div class="action-buttons">
        <button
          v-if="showDownload"
          class="btn-icon"
          @click.stop="$emit('download')"
          :title="t('download')"
        >
          <i class="bi bi-download"></i>
        </button>
        <button
          v-if="showPreview"
          class="btn-icon"
          @click.stop="$emit('preview')"
          :title="t('preview')"
        >
          <i class="bi bi-eye"></i>
        </button>
        <button
          v-if="showDelete"
          class="btn-icon text-danger"
          @click.stop="$emit('delete')"
          :title="t('delete')"
        >
          <i class="bi bi-trash3"></i>
        </button>
      </div>

      <!-- Custom actions slot -->
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()

const props = defineProps({
  // Document info
  fileName: { type: String, required: true },
  fileSize: { type: String, default: '' },
  fileType: { type: String, default: '' },

  // Document type for icon
  type: {
    type: String,
    default: 'file',
    validator: (value) => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'image', 'file'].includes(value)
  },

  // Status
  verified: { type: Boolean, default: false },

  // Actions visibility
  showDownload: { type: Boolean, default: true },
  showPreview: { type: Boolean, default: false },
  showDelete: { type: Boolean, default: false },

  // Interactive
  clickable: { type: Boolean, default: false }
})

defineEmits(['click', 'download', 'preview', 'delete'])

// Get document icon based on type
const getDocumentIcon = computed(() => {
  const icons = {
    pdf: 'bi bi-file-earmark-pdf',
    doc: 'bi bi-file-earmark-word',
    docx: 'bi bi-file-earmark-word',
    xls: 'bi bi-file-earmark-excel',
    xlsx: 'bi bi-file-earmark-excel',
    zip: 'bi bi-file-earmark-zip',
    image: 'bi bi-file-earmark-image',
    file: 'bi bi-file-earmark'
  }
  return icons[props.type] || icons.file
})

// Icon color class
const iconClass = computed(() => {
  const classes = {
    pdf: 'pdf-color',
    doc: 'word-color',
    docx: 'word-color',
    xls: 'excel-color',
    xlsx: 'excel-color',
    zip: 'zip-color',
    image: 'image-color'
  }
  return classes[props.type] || 'default-color'
})
</script>

<style scoped>
.document-item {
  background: var(--main-bg);
  border-radius: 18px;
  padding: 15px 20px;
  margin-bottom: 12px;
  border: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
  flex-wrap: wrap;
  gap: 15px;
}

.document-item:hover {
  background: var(--card-bg);
  border-color: var(--accent);
  transform: scale(1.01);
  box-shadow: var(--hover-shadow);
}

.document-item.verified {
  border-left: 4px solid #22c55e;
}

.document-item.clickable {
  cursor: pointer;
}

.document-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.pdf-color {
  color: #dc2626;
  background: rgba(220, 38, 38, 0.1);
}

.word-color {
  color: #2563eb;
  background: rgba(37, 99, 235, 0.1);
}

.excel-color {
  color: #16a34a;
  background: rgba(22, 163, 74, 0.1);
}

.zip-color {
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.image-color {
  color: #9333ea;
  background: rgba(147, 51, 234, 0.1);
}

.default-color {
  color: var(--text-muted);
  background: var(--border-color);
}

.document-info {
  flex: 1;
}

.document-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 2px;
}

.meta-separator {
  color: var(--text-muted);
  font-size: 12px;
}

.document-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.verified-badge {
  background: #f0fdf4;
  color: #166534;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
}

[data-theme="dark"] .verified-badge {
  background: rgba(34, 197, 94, 0.2);
  color: #86efac;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.btn-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: var(--card-bg);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}

.btn-icon.text-danger:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}

/* Responsive */
@media (max-width: 576px) {
  .document-item {
    flex-direction: column;
    align-items: flex-start;
    padding: 15px;
  }

  .document-actions {
    width: 100%;
    justify-content: space-between;
  }

  .verified-badge {
    order: -1;
  }
}

@media (max-width: 400px) {
  .document-actions {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .action-buttons {
    width: 100%;
    justify-content: space-between;
  }

  .btn-icon {
    flex: 1;
  }
}
</style>

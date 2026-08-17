<template>
  <div>
    <div
      class="uploader"
      :class="{ 'uploader-active': dragging }"
      tabindex="0"
      @click="$refs.input.click()"
      @keydown.enter.prevent="$refs.input.click()"
      @paste="onPaste"
      @dragover.prevent="dragging = true"
      @dragenter.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <p v-if="dragging" class="font-medium text-indigo-600">Upuść pliki tutaj...</p>
      <p v-else class="text-gray-500">
        Wklej print screen (Ctrl+V), przeciągnij plik albo kliknij, żeby wybrać
        <span class="block text-xs text-gray-400">jpg, png, gif, webp, pdf — max 10 MB</span>
      </p>
    </div>

    <input ref="input" type="file" multiple :accept="accept" class="hidden" @change="onSelect" />

    <div v-if="previews.length" class="grid grid-cols-2 gap-3 mt-3 sm:grid-cols-4">
      <div v-for="preview in previews" :key="preview.key" class="relative group">
        <img v-if="preview.src" :src="preview.src" class="w-full h-24 object-cover rounded border border-gray-200" :alt="preview.name" />
        <div v-else class="flex items-center justify-center w-full h-24 text-xs text-gray-500 bg-gray-50 rounded border border-gray-200 px-2 text-center">
          {{ preview.name }}
        </div>
        <button
          type="button"
          class="absolute top-1 right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full opacity-90 hover:bg-red-600"
          title="Usuń"
          @click.stop="remove(preview.key)"
        >
          ×
        </button>
        <div class="mt-1 text-[10px] text-gray-500 truncate">{{ preview.name }} ({{ preview.sizeLabel }})</div>
      </div>
    </div>
  </div>
</template>

<script>
const ACCEPT = 'image/png,image/jpeg,image/gif,image/webp,application/pdf'

export default {
  name: 'ScreenshotUploader',
  props: {
    modelValue: { type: Array, default: () => [] },
    accept: { type: String, default: ACCEPT },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      dragging: false,
      previews: [],
      nextKey: 1,
    }
  },
  watch: {
    // Rodzic czyści listę po zapisie — wtedy sprzątamy podglądy.
    modelValue(value) {
      if (!value || value.length === 0) {
        this.revokeAll()
        this.previews = []
      }
    },
  },
  beforeUnmount() {
    this.revokeAll()
  },
  methods: {
    /** Publiczne — formularz przekazuje tu pliki wklejone w polu opisu. */
    addFiles(files) {
      const accepted = Array.from(files || []).filter((file) => file instanceof File)

      if (accepted.length === 0) {
        return
      }

      accepted.forEach((original) => {
        const name = this.fileName(original)
        // Screeny z clipboardu trafiają tu jako "image.png" — przezywamy plik,
        // żeby na serwerze zapisała się rozpoznawalna nazwa.
        const file = name === original.name ? original : new File([original], name, { type: original.type })

        this.previews.push({
          key: this.nextKey++,
          file,
          name,
          sizeLabel: this.sizeLabel(file.size),
          src: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
        })
      })

      this.emitFiles()
    },
    onPaste(event) {
      const files = Array.from(event.clipboardData?.files || [])

      if (files.length) {
        event.preventDefault()
        this.addFiles(files)
      }
    },
    onDrop(event) {
      this.dragging = false
      this.addFiles(event.dataTransfer?.files)
    },
    onSelect(event) {
      this.addFiles(event.target.files)
      event.target.value = ''
    },
    remove(key) {
      const index = this.previews.findIndex((preview) => preview.key === key)

      if (index === -1) {
        return
      }

      if (this.previews[index].src) {
        URL.revokeObjectURL(this.previews[index].src)
      }

      this.previews.splice(index, 1)
      this.emitFiles()
    },
    emitFiles() {
      this.$emit('update:modelValue', this.previews.map((preview) => preview.file))
    },
    revokeAll() {
      this.previews.forEach((preview) => preview.src && URL.revokeObjectURL(preview.src))
    },
    /** Screeny z clipboardu nazywają się "image.png" — dokładamy godzinę, żeby je rozróżnić. */
    fileName(file) {
      if (file.name && file.name !== 'image.png') {
        return file.name
      }

      const now = new Date()
      const stamp = [now.getHours(), now.getMinutes(), now.getSeconds()]
        .map((part) => String(part).padStart(2, '0'))
        .join('-')

      return `screen-${stamp}.${(file.type.split('/')[1] || 'png')}`
    },
    sizeLabel(size) {
      if (!size) {
        return '?'
      }

      return size > 1024 * 1024 ? `${(size / 1024 / 1024).toFixed(1)} MB` : `${Math.round(size / 1024)} kB`
    },
  },
}
</script>

<style scoped>
.uploader {
  width: 100%;
  padding: 14px 10px;
  border: 2px dashed #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  text-align: center;
  font-size: 13px;
  transition: all 0.2s ease;
}

.uploader:hover,
.uploader:focus,
.uploader-active {
  border-color: #6366f1;
  background: #f1f5f9;
  outline: none;
}
</style>

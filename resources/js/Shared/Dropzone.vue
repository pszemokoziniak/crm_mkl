<template>
  <div>
    <div v-if="state.files.length > 0" class="files">
      <div v-for="(file, index) in state.files" :key="index">
        <div class="file-item">
          <div class="flex items-center overflow-hidden">
            <div v-if="file.display" class="mr-4 flex-shrink-0">
              <a :href="file.path" target="_blank" title="Kliknij, aby powiększyć">
                <img :src="file.path + '?w=100&h=100&fit=crop'" class="w-16 h-16 object-cover rounded border border-gray-200 hover:opacity-75 transition-opacity" alt="tool_image">
              </a>
            </div>
            <span class="truncate font-medium text-gray-700">{{ file.name }}</span>
          </div>
          <div class="flex items-center ml-4">
            <a v-if="file.preview !== false" :href="file.path" target="_blank" class="download-file text-xs">Pobierz</a>
            <span class="delete-file text-xs" @click="handleClickDeleteFile(index)">Usuń</span>
          </div>
        </div>
      </div>
    </div>
    <div class="dropzone mt-2" v-bind="getRootProps()">
      <input v-bind="getInputProps()" />
      <p v-if="isDragActive" class="text-indigo-600 font-medium">Upuść tutaj ...</p>
      <p v-else class="text-gray-500">Kliknij lub przeciągnij pliki tutaj... <span class="text-xs block text-gray-400">{{ extensions?.join(', ') }}</span></p>
    </div>
  </div>
</template>

<script>
import {useDropzone} from 'vue3-dropzone'
import {ref, toRefs} from 'vue'

export default {
  name: 'UseDropzoneDemo',
  props: {
    modelValue: [Array, FileList],
    extensions: Array,
  },
  emits: ['update:modelValue'],
  setup(props, {emit}) {
    const state = ref({files: []})
    const toDelete = []
    const {modelValue} = toRefs(props)

    if (modelValue.value && Array.isArray(modelValue.value)) {
      state.value.files = modelValue.value.map(file => {
        let newFile = new File(['*'], file.name)
        newFile.path = file.path
        newFile.display = file.display
        return newFile
      })
    }

    function onDrop(acceptFiles) {
      acceptFiles = acceptFiles.map(file => {
        file.preview = false
        return file
      })
      state.value.files.push(...acceptFiles)
      emit('update:modelValue', [...state.value.files])
    }

    function handleClickDeleteFile(index) {
      if (confirm('Czy na pewno chcesz usunąć ten plik?')) {
        let fileToDelete = state.value.files[index]
        fileToDelete.deleted = true
        toDelete.push(fileToDelete)

        state.value.files.splice(index, 1)
        emit('update:modelValue', [...state.value.files, ...toDelete])
      }
    }

    const {getRootProps, getInputProps, ...rest} = useDropzone({
      onDrop,
      maxFiles: 10,
    })

    return {
      getRootProps,
      getInputProps,
      ...rest,
      state,
      handleClickDeleteFile,
    }
  },
  watch: {
    modelValue(value) {
      if (!value || (Array.isArray(value) && value.length === 0)) {
        this.state.files = []
      }
    },
  },
}
</script>

<style scoped>
.dropzone,
.files {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  box-shadow: rgba(60, 64, 67, 0.1) 0 1px 2px 0;
  font-size: 13px;
}

.dropzone {
  border: 2px dashed #e2e8f0;
  background: #f8fafc;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s ease;
}

.dropzone:hover {
  border-color: #6366f1;
  background: #f1f5f9;
}

.file-item {
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 8px 12px;
  margin-bottom: 8px;
}

.file-item:last-child {
  margin-bottom: 0;
}

.delete-file {
  background: #fee2e2;
  color: #b91c1c;
  padding: 4px 8px;
  margin-left: 8px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.delete-file:hover {
  background: #fecaca;
}

.download-file {
  background: #f1f5f9;
  color: #475569;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: 500;
}

.download-file:hover {
  background: #e2e8f0;
}
</style>

<template>
  <div>
    <div class="files">
      <div v-for="(file) in state.files" :key="file.id" v-bind="state" class="file-item">
        <span>{{ file.name }}</span>
      </div>
    </div>
    <div class="dropzone" v-bind="getRootProps()">
      <input v-bind="getInputProps()" />
      <p v-if="isDragActive">Drop the files here ...</p>
      <p v-else>Kliknij aby dodać pliki...</p>
    </div>
  </div>
</template>

<script>
import { useDropzone } from 'vue3-dropzone'
import {ref} from 'vue'

const state = ref({ files: [] })

export default {
  name: 'UseDropzoneDemo',
  props: {
    modelValue: FileList,
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {


    function onDrop(acceptFiles) {
      state.value.files = acceptFiles
      emit('update:modelValue', acceptFiles)
    }

    const { getRootProps, getInputProps, ...rest } = useDropzone({ onDrop });

    return {
      getRootProps,
      getInputProps,
      ...rest,
      state,
    }
  },
  watch: {
    modelValue(value) {
      if (!value) {
        state.value.files = []
      }
    },
  },
  mounted() {
    state.value.files = []
  },
}
</script>

<style>
.dropzone,
.files {
  width: 100%;
  max-width: 300px;
  margin: 0 auto;
  padding: 10px;
  border-radius: 8px;
  box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px,
  rgba(60, 64, 67, 0.15) 0px 1px 3px 1px;
  font-size: 12px;
  line-height: 1.5;
}

.border {
  border: 2px dashed #ccc;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  transition: all 0.3s ease;
  background: #fff;
}

.border .isDragActive {
  border: 2px dashed #ffb300;
  background: rgb(255 167 18 / 20%);
}

.file-item {
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgb(255 167 18 / 20%);
  padding: 7px;
  padding-left: 15px;
  margin-top: 10px;
}

.file-item .first-child {
  margin-top: 0;
}

.file-item .delete-file {
  background: red;
  color: #fff;
  padding: 5px 10px;
  border-radius: 8px;
  cursor: pointer;
}
</style>
<template>
  <div>
    <div v-if="state.files.length > 0" class="files">
      <div v-for="(file, index) in state.files" :key="file.id" v-bind="state" class="file-item">
        <span>{{ file.name }}</span>
        <span class="delete-file" @click="handleClickDeleteFile(index)">Delete</span>
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

export default {
  name: 'UseDropzoneDemo',
  props: {
    modelValue: FileList,
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {

    const state = ref({ files: [] })

    function onDrop(acceptFiles) {
      state.value.files = acceptFiles
      emit('update:modelValue', acceptFiles)
    }

    function handleClickDeleteFile(index) {
      state.value.files.splice(index, 1)
    }

    const { getRootProps, getInputProps, ...rest } = useDropzone({ onDrop });

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
      if (!value) {
        this.state.value.files = []
      }
    },
  },
}
</script>

<style>
.dropzone,
.files {
  width: 100%;
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
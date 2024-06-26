<template>
  <div>
    <Head title="Narzedzia" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedzia">Sprzęt</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer seryjny" />
          <date-input v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" class="pb-8 pr-6 w-full lg:w-1/2" label="Ważność badań" />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa" />
          <text-input v-model="form.ilosc_all" type="number" :error="form.errors.ilosc_all" class="pb-8 pr-6 w-full lg:w-1/4" label="Ilość" />
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Zdjęcia</div>
            <dropzone v-model="form.photos" :extensions="['jpg', 'jpeg', 'png', 'tiff']" />
          </div>
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Dokumenty</div>
            <dropzone v-model="form.documents" :extensions="['pdf', 'xls', 'xlsx', 'doc', 'docx', '']" />
          </div>
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj Sprzęt</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import DateInput from '@/Shared/DateInput.vue'
import Dropzone from '@/Shared/Dropzone.vue'
import axios from 'axios'

export default {
  components: {
    DateInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    Dropzone,
  },
  layout: Layout,
  props: {
    organizations: Array,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        numer_seryjny: '',
        waznosc_badan: new Date().toISOString().substr(0, 10),
        name: '',
        ilosc_all: 0,
        photos: null,
        documents: null,
      }),
    }
  },
  computed() {
    this.form.ilosc_magazyn = this.form.ilosc_all ? this.form.ilosc_all : 0
  },
  methods: {
    store() {
      this.form
        .transform((data) => ({
          ...data,
          photos: data.photos.filter(file => file.deleted !== true),
          documents: data.documents.filter(file => file.deleted !== true),
        }))
        .post('/narzedzia', {
          onBefore: () => {
            /**
               * Send files to delete because is impossible to add property `deleted`
               * to File object. Backend also doesn't get it.
               */
            const photosToDelete = this
              .form
              .photos
              .filter(file => file.deleted === true)
              .map(file => file.name)

            const documentsToDelete = this
              .form
              .documents
              .filter(file => file.deleted === true)
              .map(file => file.name)

            const filesToDelete = [...photosToDelete, ...documentsToDelete]

            if (filesToDelete.length > 0) {
              axios.delete(`/narzedzia/${this.narzedzia.id}/file`, {
                data: {
                  files: filesToDelete,
                },
              })
            }
          },
        })
    },
  },
}
</script>

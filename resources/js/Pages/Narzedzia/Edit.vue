<template>
  <div>
    <Head :title="`${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedzia">Sprzęt</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
    <trashed-message v-if="narzedzia.deleted_at" class="mb-6" @restore="restore">Usunąć?</trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input
            v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" class="pb-8 pr-6 w-full lg:w-1/2"
            label="Numer seryjny"
          />
          <date-input
            v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" class="pb-8 pr-6 w-full lg:w-1/2"
            label="Ważność badań"
          />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa" />
          <text-input
            v-model="form.ilosc_all" :error="form.errors.ilosc_all" class="pb-8 pr-6 w-full lg:w-1/4"
            label="Ilość"
          />
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Zdjęcia</div>
            <dropzone v-model="form.photos" :extensions="['jpg', 'jpeg', 'png', 'tiff']" />
          </div>
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Dokumenty</div>
            <dropzone v-model="form.documents" :extensions="['pdf', 'xls', 'xlsx', 'doc', 'docx', '']" />
          </div>
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <delete-button
            v-if="!narzedzia.deleted_at"
            :href="`/narzedzia/${narzedzia.id}`"
            confirm="Czy na pewno chcesz usunąć ten sprzęt?"
          >
            Usuń sprzęt
          </delete-button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import {Head, Link} from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import DateInput from '@/Shared/DateInput.vue'
import Dropzone from '@/Shared/Dropzone.vue'
import DeleteButton from '@/Shared/DeleteButton.vue'
import axios from 'axios'

export default {
  components: {
    DateInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
    Dropzone,
    DeleteButton,
  },
  layout: Layout,
  props: {
    narzedzia: Object,
    photos: Array,
    documents: Array,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        id: this.narzedzia.id,
        numer_seryjny: this.narzedzia.numer_seryjny,
        waznosc_badan: this.narzedzia.waznosc_badan,
        name: this.narzedzia.name,
        ilosc_all: this.narzedzia.ilosc_all,
        photos: this.photos,
        documents: this.documents,
      }),
    }
  },
  methods: {
    update() {
      this.form
        .transform((data) => ({
          ...data,
          photos: data.photos.filter(file => file.deleted !== true),
          documents: data.documents.filter(file => file.deleted !== true),
        }))
        .post(`/narzedzia/${this.narzedzia.id}`, {
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
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/narzedzia/${this.narzedzia.id}/restore`)
      }
    },
  },
}
</script>

<template>
  <div>
    <Head :title="`${form.name}`" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/narzedzia">Sprzęt</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.name }}
    </h1>
    <trashed-message v-if="narzedzia.deleted_at" class="mb-6" @restore="restore">Usuniąć?</trashed-message>

    <div class="grid col-span-1">
      <img v-if="narzedzia.photo_path" class="" :src="narzedzia.photo_path"/>
      <img v-if="narzedzia.photo_path == null" class="" src="/img/contacts/emptyPhoto.png?w=260&h=260&fit=fill"/>
    </div>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">

        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer seryjny" />
          <date-input v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" class="pb-8 pr-6 w-full lg:w-1/2" label="Ważność badań" />
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa" />
          <text-input v-model="form.ilosc_all" :error="form.errors.ilosc_all" class="pb-8 pr-6 w-full lg:w-1/4" label="Ilość" />
          <file-input v-model="form.photo" :error="form.errors.photo" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Zdjęcia" />
          <file-input v-model="form.document" :errors="form.errors.document" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Dokument" />
        </div>

        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!narzedzia.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Popraw</loading-button>
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
import TrashedMessage from '@/Shared/TrashedMessage'
import FileInput from '@/Shared/FileInput.vue'
import DateInput from '@/Shared/DateInput.vue'

export default {
  components: {
    DateInput, FileInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    TrashedMessage,
  },
  layout: Layout,
  props: {
    narzedzia: Object,
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
        photo: this.narzedzia.photo,
        document: this.narzedzia.document,
      }),
    }
  },
  methods: {
    update() {
      this.form.post(`/narzedzia/${this.narzedzia.id}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/narzedzia/${this.narzedzia.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/narzedzia/${this.narzedzia.id}/restore`)
      }
    },
  },
}
</script>

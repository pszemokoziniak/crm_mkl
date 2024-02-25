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
          <text-input v-model="form.ilosc_all" :error="form.errors.ilosc_all" class="pb-8 pr-6 w-full lg:w-1/4" label="Ilość" />
          <file-input v-model="form.photo" :error="form.errors.photo" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Zdjęcia" />
          <file-input v-model="form.document" :errors="form.errors.document" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Dokument" />
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
import FileInput from '@/Shared/FileInput.vue'
import DateInput from '@/Shared/DateInput.vue'

export default {
  components: {
    DateInput,
    FileInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  // props: {
  //   organizations: Array,
  //   accounts: Array,
  // },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        numer_seryjny: '',
        waznosc_badan: '',
        name: '',
        ilosc_all: '',
        photo: null,
        document: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/narzedzia')
    },
  },
}
</script>

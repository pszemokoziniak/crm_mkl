<template>
  <div>
    <Head title="Create Contact" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownicy</Link>
      <span class="text-indigo-400 font-medium">/</span> Dodaj
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa" />
          <select-input v-model="form.typ" :error="form.errors.typ" class="pb-8 pr-6 w-full lg:w-1/2" label="Typ dokumentu">
            <option value="Lekarskie">Badania Lekarskie</option>
            <option value="BHP">Szkolenia BHP</option>
            <option value="Uprawnienia">Uprawnienia</option>
            <option value="A1">A1</option>
          </select-input>
          <file-input v-model="form.document" :errors="form.errors.document" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Dokument" />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj dokument</loading-button>
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
import FileInput from "@/Shared/FileInput";
import SelectInput from '@/Shared/SelectInput'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    TextInput,
    FileInput,
    SelectInput,
  },
  layout: Layout,
  props: {
    contactId: Number,
    errors: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        name: "",
        typ: "",
        document: null,
      }),
    }
  },
  methods: {
    store() {
      this.form.post(`/contacts/${this.contactId}/documents/store`)
    },
  },
}
</script>

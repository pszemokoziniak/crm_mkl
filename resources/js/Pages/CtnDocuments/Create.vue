<template>
  <div>
    <Head title="Create Contact" />
    <worker-menu :contact-id="contact_id" />
    <pracownik-naglowek :contact-id="pracownik ? pracownik.id : contact_id" :nazwa="pracownik ? pracownik.nazwa : ''" tytul="Dokumenty — dodaj" />
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa" />
          <select-input v-model="form.typ" :error="form.errors.typ" class="pb-8 pr-6 w-full lg:w-1/2" label="Typ dokumentu">
            <option v-for="item in dokumentyTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Dokumenty</div>
            <dropzone v-model="form.documents"></dropzone>
          </div>
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
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import WorkerMenu from '@/Shared/WorkerMenu'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import LoadingButton from '@/Shared/LoadingButton'
import SelectInput from '@/Shared/SelectInput'
import Dropzone from '@/Shared/Dropzone.vue'

export default {
  components: {
    PracownikNaglowek,
    WorkerMenu,
    Head,
    Link,
    LoadingButton,
    TextInput,
    SelectInput,
    Dropzone,
  },
  layout: Layout,
  props: {
    pracownik: { type: Object, default: null },
    contactId: Number,
    errors: Object,
    dokumentyTyps: Object,
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        typ: '',
        documents: null,
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

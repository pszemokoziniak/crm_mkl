<template>
  <div>
    <Head title="Dodaj A1" />
    <worker-menu :contact-id="contact_id" />
    <pracownik-naglowek :contact-id="pracownik ? pracownik.id : contact_id" :nazwa="pracownik ? pracownik.nazwa : ''" tytul="A1 — dodaj" />
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store(contact_id)">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.start" type="date" :error="form.errors.start" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek A1" />
          <text-input v-model="form.end" type="date" :error="form.errors.end" :min="minDate" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec A1" />
          <select-input v-model="form.kraj_typs_id" :error="form.errors.kraj_typs_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kraj">
            <option v-for="item in countries" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <text-input v-model="form.contact_id" type="hidden" value="@{{contact_id}}" :error="form.errors.contact_id" />
          <!-- Skan przy wpisie, żeby nie trzeba było wracać do zakładki
               Dokumenty i wgrywać go osobno. Dokument zostaje powiązany
               z tym wpisem. -->
          <file-input
            v-model="form.skan"
            :errors="form.errors.skan ? [form.errors.skan] : []"
            class="pb-8 pr-6 w-full"
            type="file"
            accept=".pdf,image/*"
            label="Skan (opcjonalnie)"
          />
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Dodaj A1</loading-button>
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
import FileInput from '@/Shared/FileInput'
import LoadingButton from '@/Shared/LoadingButton'
import SelectInput from '@/Shared/SelectInput.vue'

export default {
  components: {
    PracownikNaglowek,
    WorkerMenu,
    SelectInput,
    Head,
    Link,
    FileInput,
    LoadingButton,
    TextInput,
  },
  layout: Layout,
  props: {
    pracownik: { type: Object, default: null },
    contact_id: Number,
    countries: Object,
  },
  remember: 'form',
  data() {
    const date = new Date()
    const minDate = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0')
    return {
      form: this.$inertia.form({
        skan: null,
        start: '',
        end: '',
        kraj_typs_id: '',
        contact_id: '',
      }),
      minDate: minDate,
    }
  },
  methods: {
    store(contact_id) {
      // console.log(contact_id)
      this.form.post('/a1/' + contact_id)
    },
  },
}
</script>

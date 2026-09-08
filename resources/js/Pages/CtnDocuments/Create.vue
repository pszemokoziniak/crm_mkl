<template>
  <div>
    <Head title="Create Contact" />
    <worker-menu :contact-id="contactId" />
    <pracownik-naglowek :pracownik="pracownik" tytul="Dokumenty — dodaj" />
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.name" :error="form.errors.name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwa" />
          <select-input v-model="form.typ" :error="form.errors.typ" class="pb-8 pr-6 w-full lg:w-1/2" label="Typ dokumentu">
            <option v-for="item in dokumentyTyps" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select-input>
          <!-- Przypisanie do konkretnego wpisu: bez tego dokument trafia tylko
               do worka "dokumenty tego pracownika w tym typie".
               Pole jest widoczne od razu, tylko nieczynne do czasu wybrania
               typu — schowane nie dawało znać, że taka możliwość istnieje. -->
          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <label class="form-label" for="zrodlo">Przypisz do wpisu (opcjonalnie):</label>
            <select
              id="zrodlo"
              v-model="form.zrodlo_id"
              class="form-select"
              :class="{ 'bg-gray-100 text-gray-400': !form.typ || !wpisyTypu.length }"
              :disabled="!form.typ || !wpisyTypu.length"
            >
              <option :value="null">— nie przypisuj —</option>
              <option v-for="w in wpisyTypu" :key="w.id" :value="w.id">{{ w.etykieta }}</option>
            </select>
            <p class="mt-1 text-sm text-gray-500">
              <template v-if="!form.typ">Najpierw wybierz typ dokumentu.</template>
              <template v-else-if="!wpisyTypu.length">
                Ten pracownik nie ma jeszcze wpisów rodzaju „{{ nazwaTypu }}" — dokument zostanie dodany luzem.
              </template>
              <template v-else>Dzięki temu przy wpisie pojawi się odnośnik do tego dokumentu.</template>
            </p>
            <div v-if="form.errors.zrodlo_id" class="form-error">{{ form.errors.zrodlo_id }}</div>
          </div>

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
    wpisy: { type: Object, default: () => ({}) },
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        typ: '',
        zrodlo_id: null,
        documents: null,
      }),
    }
  },
  computed: {
    wpisyTypu() {
      return this.wpisy[this.form.typ] || []
    },
    nazwaTypu() {
      const t = (this.dokumentyTyps || []).find((x) => String(x.id) === String(this.form.typ))

      return t ? t.name : ''
    },
  },
  watch: {
    'form.typ': function () {
      this.form.zrodlo_id = null
    },
  },
  methods: {
    store() {
      this.form.post(`/contacts/${this.contactId}/documents/store`)
    },
  },
}
</script>

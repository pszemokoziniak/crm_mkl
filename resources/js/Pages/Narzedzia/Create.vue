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
          <select-input v-model="form.narzedzia_typ_id" :error="form.errors.narzedzia_typ_id" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa sprzętu (typ)">
            <option value="">— wybierz —</option>
            <option v-for="t in typy" :key="t.id" :value="t.id">{{ t.name }}</option>
            <option value="__new__">+ Nowy typ…</option>
          </select-input>
          <text-input v-if="form.narzedzia_typ_id === '__new__'" v-model="form.new_typ_name" :error="form.errors.new_typ_name" class="pb-8 pr-6 w-full lg:w-3/4" label="Nazwa nowego typu" />
          <!-- Lista już używanych kategorii + możliwość wpisania nowej,
               tak samo jak przy wyborze typu sprzętu. -->
          <select-input
            v-if="form.narzedzia_typ_id === '__new__'"
            v-model="form.new_typ_kategoria_wybor"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Kategoria (grupuje modele w magazynie)"
          >
            <option value="">— bez kategorii —</option>
            <option v-for="k in kategorie" :key="k" :value="k">{{ k }}</option>
            <option value="__new__">+ Nowa kategoria…</option>
          </select-input>
          <text-input
            v-if="form.narzedzia_typ_id === '__new__' && form.new_typ_kategoria_wybor === '__new__'"
            v-model="form.new_typ_kategoria_nowa"
            :error="form.errors.new_typ_kategoria"
            class="pb-8 pr-6 w-full lg:w-1/1"
            label="Nazwa nowej kategorii"
            placeholder="np. Żuraw"
          />
          <number-input v-model="form.ilosc_all" :error="form.errors.ilosc_all" class="pb-8 pr-6 w-full lg:w-1/4" label="Ilość" />
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
import NumberInput from '@/Shared/NumberInput'
import LoadingButton from '@/Shared/LoadingButton'
import DateInput from '@/Shared/DateInput.vue'
import SelectInput from '@/Shared/SelectInput'
import Dropzone from '@/Shared/Dropzone.vue'

export default {
  components: {
    DateInput,
    SelectInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    NumberInput,
    Dropzone,
  },
  layout: Layout,
  props: {
    kategorie: { type: Array, default: () => [] },
    organizations: Array,
    typy: { type: Array, default: () => [] },
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        numer_seryjny: '',
        waznosc_badan: new Date().toISOString().substr(0, 10),
        narzedzia_typ_id: '',
        new_typ_name: '',
        new_typ_kategoria: '',
        new_typ_kategoria_wybor: '',
        new_typ_kategoria_nowa: '',
        ilosc_all: 0,
        photos: [],
        documents: [],
      }),
    }
  },
  watch: {
    'form.new_typ_kategoria_wybor': function (wybor) {
      this.form.new_typ_kategoria = wybor === '__new__' ? this.form.new_typ_kategoria_nowa : wybor
    },
    'form.new_typ_kategoria_nowa': function (nazwa) {
      if (this.form.new_typ_kategoria_wybor === '__new__') {
        this.form.new_typ_kategoria = nazwa
      }
    },
  },
  methods: {
    store() {
      this.form
        .transform((data) => ({
          ...data,
          narzedzia_typ_id: data.narzedzia_typ_id === '__new__' ? null : data.narzedzia_typ_id,
          photos: data.photos ? data.photos.filter((file) => file.deleted !== true) : [],
          documents: data.documents ? data.documents.filter((file) => file.deleted !== true) : [],
        }))
        .post('/narzedzia')
    },
  },
}
</script>

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
          <!-- Najpierw czym sprzęt jest, potem którą sztuką — tak się go opisuje. -->
          <div class="pb-8 pr-6 w-full">
            <wybor-typu-sprzetu
              v-model="form.narzedzia_typ_id"
              v-model:nowy-typ="form.new_typ_name"
              v-model:nowa-grupa="form.new_typ_grupa"
              :typy="typy"
              :grupy="grupy"
              :bledy="form.errors"
            />
          </div>
          <text-input v-model="form.numer_seryjny" :error="form.errors.numer_seryjny" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer seryjny" />
          <text-input v-model="form.numer_udt" :error="form.errors.numer_udt" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer ewidencyjny UDT" placeholder="np. N3412000123" />
          <date-input v-model="form.waznosc_badan" :error="form.errors.waznosc_badan" class="pb-8 pr-6 w-full lg:w-1/2" label="Ważność badań" />
          <div class="pb-8 pr-6 w-full lg:w-1/2">
            <number-input v-model="form.ilosc_all" :error="form.errors.ilosc_all" label="Ilość" />
            <p class="mt-1 text-sm text-gray-500">Jeden wpis to jedna maszyna — magazyn liczy sprzęt sztukami.</p>
          </div>
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Zdjęcia</div>
            <dropzone v-model="form.photos" :extensions="['jpg', 'jpeg', 'png', 'tiff']" />
          </div>
          <div class="pb-8 pr-6 w-full">
            <div class="form-label">Dokumenty</div>
            <dropzone v-model="form.documents" :extensions="['pdf', 'xls', 'xlsx', 'doc', 'docx', '']" />
          </div>
        </div>
        <!-- Serwer odrzuca zbyt duże pliki, zanim dojdzie do zapisu — mówimy
             o tym wprost, zamiast pozwolić, żeby przycisk nic nie robił. -->
        <div v-if="zaDuzePliki.length" class="px-8 pb-4 text-sm text-red-600">
          Za duże pliki (limit {{ limitPlikuMb }} MB na plik):
          <span v-for="(p, i) in zaDuzePliki" :key="i">{{ i ? ', ' : '' }}{{ p }}</span>.
          Usuń je albo zmniejsz, inaczej zapis się nie powiedzie.
        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" :disabled="zaDuzePliki.length > 0" type="submit">Dodaj Sprzęt</loading-button>
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
import Dropzone from '@/Shared/Dropzone.vue'
import WyborTypuSprzetu from '@/Shared/WyborTypuSprzetu'

export default {
  components: {
    DateInput,
    Head,
    Link,
    LoadingButton,
    TextInput,
    NumberInput,
    Dropzone,
    WyborTypuSprzetu,
  },
  layout: Layout,
  props: {
    limitPlikuMb: { type: Number, default: 2 },
    grupy: { type: Array, default: () => [] },
    organizations: Array,
    typy: { type: Array, default: () => [] },
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        numer_seryjny: '',
        numer_udt: '',
        waznosc_badan: '',
        narzedzia_typ_id: '',
        new_typ_name: '',
        new_typ_grupa: '',
        ilosc_all: 1,
        photos: [],
        documents: [],
      }),
    }
  },
  computed: {
    // Pliki większe niż limit serwera — sprawdzamy w przeglądarce, bo taki
    // formularz nie dociera nawet do walidacji i zapis kończy się ciszą.
    zaDuzePliki() {
      const limit = this.limitPlikuMb * 1024 * 1024
      const wszystkie = [...(this.form.photos || []), ...(this.form.documents || [])]

      return wszystkie
        .filter((p) => p && !p.deleted && p.size > limit)
        .map((p) => `${p.name} (${(p.size / 1024 / 1024).toFixed(1)} MB)`)
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

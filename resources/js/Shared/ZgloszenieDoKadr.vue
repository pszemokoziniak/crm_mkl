<template>
  <!-- Zgłoszenie do kadr: kierownik wie pierwszy o zjeździe czy urlopie,
       ale zmianę pobytu i nieobecność wstawiają kadry. Jeden formularz
       dla zakładki Pracownicy i dla KCP (tam z wymaganym skanem wniosku). -->
  <teleport to="body">
    <div v-if="otwarte" class="fixed inset-0 z-[10001] flex items-center justify-center bg-gray-900 bg-opacity-50 p-4" @click.self="$emit('zamknij')">
      <form class="w-full max-w-lg bg-white rounded-lg shadow-xl" @submit.prevent="wyslij">
        <div class="px-6 py-4 border-b border-gray-100">
          <h3 class="text-lg font-bold text-gray-900">{{ tytul }}</h3>
          <p class="text-sm text-gray-500">{{ pracownik.nazwa }} · {{ budowa }}</p>
        </div>
        <div class="px-6 py-4 space-y-4">
          <div>
            <label class="form-label" for="zgl-rodzaj">Czego dotyczy:</label>
            <select id="zgl-rodzaj" v-model="form.rodzaj" class="form-select w-full" :class="{ error: form.errors.rodzaj }" :disabled="tylkoUrlop">
              <option v-for="(nazwa, klucz) in rodzajeDoWyboru" :key="klucz" :value="klucz">{{ nazwa }}</option>
            </select>
            <div v-if="form.errors.rodzaj" class="form-error">{{ form.errors.rodzaj }}</div>
          </div>
          <div v-if="form.rodzaj === 'dokument'">
            <label class="form-label" for="zgl-dokument">Jakiego dokumentu brakuje:</label>
            <select id="zgl-dokument" v-model="form.dokument" class="form-select w-full" :class="{ error: form.errors.dokument }">
              <option v-for="(nazwa, klucz) in dokumentyDoWyboru" :key="klucz" :value="klucz">{{ nazwa }}</option>
            </select>
            <div v-if="form.errors.dokument" class="form-error">{{ form.errors.dokument }}</div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <date-input v-model="form.od" :error="form.errors.od" label="Od" />
            <date-input v-model="form.do" :error="form.errors.do" label="Do" />
          </div>
          <div>
            <label class="form-label" for="zgl-uwaga">Uwaga dla kadr:</label>
            <textarea id="zgl-uwaga" v-model="form.uwaga" rows="3" class="form-input w-full" :placeholder="form.rodzaj === 'dokument' ? 'np. badania skończyły się w sierpniu, w poniedziałek wjeżdża na budowę' : 'np. wraca 28.09, chce urlop na wesele brata'"></textarea>
            <div v-if="form.errors.uwaga" class="form-error">{{ form.errors.uwaga }}</div>
          </div>
          <div>
            <label class="form-label" for="zgl-plik">
              Skan{{ wymagajPliku ? ' wniosku urlopowego' : ' (wniosek urlopowy, zdjęcie albo PDF)' }}:
              <span v-if="wymagajPliku" class="text-red-600">*</span>
            </label>
            <input id="zgl-plik" type="file" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-gray-600" :required="wymagajPliku" @change="form.plik = $event.target.files[0] || null" />
            <div v-if="form.errors.plik" class="form-error">{{ form.errors.plik }}</div>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-lg">
          <button type="button" class="text-sm text-gray-600 hover:text-gray-900" @click="$emit('zamknij')">Anuluj</button>
          <button type="submit" class="btn-indigo text-sm" :disabled="form.processing">Wyślij do kadr</button>
        </div>
      </form>
    </div>
  </teleport>
</template>

<script>
import { useForm } from '@inertiajs/inertia-vue3'
import DateInput from '@/Shared/DateInput.vue'

export default {
  components: { DateInput },
  props: {
    otwarte: Boolean,
    organizationId: { type: Number, required: true },
    budowa: { type: String, default: '' },
    // { id, nazwa }
    pracownik: { type: Object, default: () => ({ id: null, nazwa: '' }) },
    rodzaje: { type: Object, default: () => ({}) },
    // Wartości startowe: { rodzaj, od, do, uwaga }
    start: { type: Object, default: () => ({}) },
    // KCP: skan wniosku urlopowego jest po to, żeby go dołączyć.
    wymagajPliku: Boolean,
    tylkoUrlop: Boolean,
    tytul: { type: String, default: 'Zgłoś do kadr' },
  },
  emits: ['zamknij', 'wyslane'],
  data() {
    return {
      form: useForm({ contact_id: null, rodzaj: 'zjazd', dokument: 'a1', od: '', do: '', uwaga: '', plik: null }),
    }
  },
  computed: {
    // Słowniki idą z propsa, a gdy strona ich nie podaje — ze wspólnych props Inertii.
    rodzajeDoWyboru() {
      return Object.keys(this.rodzaje).length ? this.rodzaje : (this.$page.props.zgloszenia?.rodzaje || {})
    },
    dokumentyDoWyboru() {
      return this.$page.props.zgloszenia?.dokumenty || {}
    },
  },
  watch: {
    otwarte(teraz) {
      if (!teraz) return
      this.form.reset()
      this.form.clearErrors()
      this.form.contact_id = this.pracownik.id
      this.form.rodzaj = this.start.rodzaj || (this.tylkoUrlop ? 'urlop' : 'zjazd')
      this.form.dokument = this.start.dokument || 'a1'
      this.form.od = this.start.od || ''
      this.form.do = this.start.do || ''
      this.form.uwaga = this.start.uwaga || ''
    },
  },
  methods: {
    wyslij() {
      this.form.post(`/budowy/${this.organizationId}/zgloszenia`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          this.$emit('wyslane')
          this.$emit('zamknij')
        },
      })
    },
  },
}
</script>

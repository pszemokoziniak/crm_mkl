<template>
  <!-- Jeden formularz dla budowy i pracownika: różni je tylko to, czy
       wybiera się osobę (na budowie), czy budowę (w karcie osoby). -->
  <form class="bg-white rounded-md shadow overflow-hidden" @submit.prevent="zapisz">
    <div class="px-4 py-3 sm:px-6 border-b border-gray-100 font-semibold text-gray-800">
      {{ koszt ? 'Popraw koszt' : 'Nowy koszt' }}
    </div>
    <div class="grid grid-cols-1 gap-4 p-4 sm:px-6 sm:grid-cols-2 lg:grid-cols-4">
      <div>
        <label class="form-label">Typ kosztu</label>
        <select v-model="form.typ_kosztu_id" class="form-select mt-1 w-full" :class="{ error: form.errors.typ_kosztu_id }" @change="zmienionoTyp">
          <option value="">— wybierz —</option>
          <option v-for="t in typy" :key="t.id" :value="t.id">{{ t.nazwa }}</option>
        </select>
        <div v-if="form.errors.typ_kosztu_id" class="form-error">{{ form.errors.typ_kosztu_id }}</div>
      </div>
      <div>
        <label class="form-label">Data</label>
        <input v-model="form.dzien" type="date" class="form-input mt-1 w-full" :class="{ error: form.errors.data }" />
        <div v-if="form.errors.data" class="form-error">{{ form.errors.data }}</div>
      </div>
      <div>
        <label class="form-label">Kwota</label>
        <div class="mt-1 flex gap-2">
          <input v-model="form.kwota" type="number" step="0.01" min="0" class="form-input w-full" :class="{ error: form.errors.kwota }" />
          <select v-model="form.waluta" class="form-select w-28">
            <option v-for="w in waluty" :key="w" :value="w">{{ w }}</option>
          </select>
        </div>
        <div v-if="form.errors.kwota" class="form-error">{{ form.errors.kwota }}</div>
      </div>
      <div v-if="form.waluta !== 'PLN'">
        <label class="form-label">Kurs do PLN</label>
        <label class="mt-1 flex items-center gap-2 text-sm text-gray-600">
          <input v-model="form.kurs_reczny" type="checkbox" class="form-checkbox" />
          <span>wpiszę ręcznie</span>
        </label>
        <input v-if="form.kurs_reczny" v-model="form.kurs" type="number" step="0.000001" min="0" class="form-input mt-1 w-full" placeholder="np. 4.3120" />
        <p v-else class="mt-1 text-xs text-gray-500">Kurs średni NBP z dnia kosztu (weekend: ostatni dzień roboczy).</p>
        <div v-if="form.errors.kurs" class="form-error">{{ form.errors.kurs }}</div>
      </div>

      <div v-if="osoby && !nocleg">
        <label class="form-label">Pracownik</label>
        <select v-model="form.contact_id" class="form-select mt-1 w-full" :class="{ error: form.errors.contact_id }">
          <option value="">— koszt całej budowy —</option>
          <option v-for="o in osoby" :key="o.id" :value="o.id">{{ o.nazwa }}</option>
        </select>
        <div v-if="form.errors.contact_id" class="form-error">{{ form.errors.contact_id }}</div>
      </div>
      <div v-if="budowy">
        <label class="form-label">Budowa</label>
        <select v-model="form.organization_id" class="form-select mt-1 w-full" :class="{ error: form.errors.organization_id }">
          <option value="">— bez budowy —</option>
          <option v-for="b in budowy" :key="b.id" :value="b.id">{{ b.nazwa }}</option>
        </select>
        <div v-if="form.errors.organization_id" class="form-error">{{ form.errors.organization_id }}</div>
      </div>

      <template v-if="nocleg">
        <div>
          <label class="form-label">Pokój od</label>
          <input v-model="form.od" type="date" class="form-input mt-1 w-full" :class="{ error: form.errors.od }" />
          <div v-if="form.errors.od" class="form-error">{{ form.errors.od }}</div>
        </div>
        <div>
          <label class="form-label">Pokój do</label>
          <input v-model="form.do" type="date" class="form-input mt-1 w-full" :class="{ error: form.errors.do }" />
          <div v-if="form.errors.do" class="form-error">{{ form.errors.do }}</div>
        </div>
        <div>
          <label class="form-label">Liczba miejsc</label>
          <input v-model="form.miejsc" type="number" min="1" max="200" class="form-input mt-1 w-full" :class="{ error: form.errors.miejsc }" />
          <div v-if="form.errors.miejsc" class="form-error">{{ form.errors.miejsc }}</div>
        </div>
      </template>
      <div v-else-if="osoby && !form.contact_id" class="sm:col-span-2">
        <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
          <input v-model="form.dzielony" type="checkbox" class="form-checkbox" />
          <span>Dziel na pracowników (po dniach pobytu, chyba że wskażesz osoby)</span>
        </label>
      </div>

      <div class="sm:col-span-2 lg:col-span-3">
        <label class="form-label">Opis</label>
        <input v-model="form.opis" type="text" class="form-input mt-1 w-full" placeholder="np. lot Berlin–Warszawa, rachunek za wrzesień" />
        <div v-if="form.errors.opis" class="form-error">{{ form.errors.opis }}</div>
      </div>
      <div>
        <label class="form-label">Skan / faktura</label>
        <input type="file" accept="image/*,.pdf" class="mt-1 block w-full text-sm" @change="form.plik = $event.target.files[0] || null" />
        <p v-if="koszt && koszt.plik_nazwa" class="mt-1 text-xs text-gray-500">obecny: {{ koszt.plik_nazwa }}</p>
        <div v-if="form.errors.plik" class="form-error">{{ form.errors.plik }}</div>
      </div>
    </div>
    <p v-if="ukryteBledy.length" class="px-4 pb-3 sm:px-6 text-sm text-red-700">{{ ukryteBledy.join(' ') }}</p>
    <div class="flex flex-col gap-2 px-4 py-3 sm:px-6 bg-gray-50 border-t border-gray-100 sm:flex-row sm:items-center sm:justify-end">
      <button type="button" class="text-sm text-gray-600 hover:text-gray-900 sm:mr-4" @click="$emit('zamknij')">Anuluj</button>
      <loading-button :loading="form.processing" class="btn-indigo" type="submit">{{ koszt ? 'Zapisz zmiany' : 'Dodaj koszt' }}</loading-button>
    </div>
  </form>
</template>

<script>
import LoadingButton from '@/Shared/LoadingButton'

export default {
  components: { LoadingButton },
  props: {
    typy: { type: Array, required: true },
    waluty: { type: Array, required: true },
    // Lista osób = formularz na budowie; lista budów = formularz w karcie osoby.
    osoby: { type: Array, default: null },
    budowy: { type: Array, default: null },
    koszt: { type: Object, default: null },
    adres: { type: String, required: true },
  },
  emits: ['zamknij'],
  data() {
    const k = this.koszt

    return {
      form: this.$inertia.form({
        typ_kosztu_id: k ? k.typ_kosztu_id : '',
        // Nie "data": helper formularza Inertii ma metodę data() i pole o tej
        // nazwie ją nadpisywało — zapis kończył się cichym błędem w przeglądarce.
        dzien: k ? k.data : new Date().toISOString().slice(0, 10),
        kwota: k ? k.kwota : '',
        waluta: k ? k.waluta : 'PLN',
        kurs_reczny: k ? k.kurs_reczny : false,
        kurs: k && k.kurs_reczny ? k.kurs : '',
        contact_id: k && k.contact_id ? k.contact_id : '',
        organization_id: k && k.organization_id ? k.organization_id : '',
        dzielony: k ? k.dzielony : false,
        od: k ? k.od || '' : '',
        do: k ? k.do || '' : '',
        miejsc: k ? k.miejsc || '' : '',
        opis: k ? k.opis || '' : '',
        plik: null,
      }),
    }
  },
  computed: {
    typ() {
      return this.typy.find((t) => t.id === Number(this.form.typ_kosztu_id)) || null
    },
    nocleg() {
      return !!(this.typ && this.typ.nocleg)
    },
    // Pole może być schowane (pracownik przy pokoju, kurs przy PLN, budowa na
    // budowie) — jego błąd i tak musi być widoczny.
    ukryteBledy() {
      const widoczne = ['typ_kosztu_id', 'data', 'kwota', 'opis', 'plik']
      if (this.form.waluta !== 'PLN') widoczne.push('kurs')
      if (this.osoby && !this.nocleg) widoczne.push('contact_id')
      if (this.budowy) widoczne.push('organization_id')
      if (this.nocleg) widoczne.push('od', 'do', 'miejsc')

      return Object.entries(this.form.errors).filter(([k]) => !widoczne.includes(k)).map(([, v]) => v)
    },
  },
  methods: {
    zmienionoTyp() {
      if (!this.koszt && this.typ) this.form.dzielony = !!this.typ.dzielony
    },
    zapisz() {
      const opcje = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          this.form.reset()
          this.$emit('zamknij')
        },
      }

      const doWyslania = (d) => ({ ...d, data: d.dzien })

      if (this.koszt) {
        // Plik idzie multipartem, więc PUT udajemy przez _method.
        this.form.transform((d) => ({ ...doWyslania(d), _method: 'put' })).post(`/koszty/${this.koszt.id}`, opcje)
      } else {
        this.form.transform(doWyslania).post(this.adres, opcje)
      }
    },
  },
}
</script>

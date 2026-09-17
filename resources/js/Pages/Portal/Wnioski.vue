<template>
  <div class="min-h-screen bg-gray-100">
    <Head title="Moje wnioski" />
    <!-- Strona na telefon: jedna kolumna, duże przyciski, tylko własne sprawy. -->
    <!-- Na komputerze nagłówek trzyma się tej samej kolumny, co formularz. -->
    <header class="bg-white shadow-sm px-4 py-3 flex items-center justify-between max-w-md mx-auto md:mt-4 md:rounded-xl">
      <div>
        <div class="font-bold text-gray-900">{{ pracownik.imie }} {{ pracownik.nazwisko }}</div>
        <div class="text-xs text-gray-500">
          <template v-if="pracownik.budowa">
            {{ pracownik.budowa }}
            <span class="text-gray-400">· {{ opisPobytu }}</span>
          </template>
          <template v-else>bez przypisanej budowy</template>
        </div>
      </div>
      <button type="button" class="text-xs text-gray-500 hover:text-gray-800" @click="wyloguj">Wyjdź</button>
    </header>

    <main class="p-4 space-y-4 max-w-md mx-auto">
      <div v-if="$page.props.flash && $page.props.flash.success" class="p-3 rounded-lg bg-green-50 text-sm text-green-800">{{ $page.props.flash.success }}</div>

      <!-- Kontakt z kierownictwem: jedno dotknięcie i dzwoni. -->
      <section v-if="kierownicy.length" class="bg-white rounded-xl shadow p-4">
        <h2 class="font-bold text-gray-900 mb-2">{{ kierownicy.length === 1 ? 'Mój kierownik' : 'Moi kierownicy' }}</h2>
        <div v-for="k in kierownicy" :key="k.id" class="flex items-center justify-between gap-3 py-1.5">
          <div>
            <div class="text-gray-900">{{ k.nazwa }}</div>
            <div v-if="k.stanowisko" class="text-xs text-gray-500">{{ k.stanowisko }}</div>
          </div>
          <a v-if="k.telefon" :href="`tel:${k.telefon.replace(/\s+/g, '')}`" class="btn-indigo text-sm whitespace-nowrap">Zadzwoń</a>
          <span v-else class="text-xs text-gray-400">bez telefonu</span>
        </div>
      </section>

      <section class="bg-white rounded-xl shadow p-4">
        <h2 class="font-bold text-gray-900 mb-3">Złóż wniosek urlopowy</h2>
        <form class="space-y-3" @submit.prevent="wyslij">
          <div>
            <label class="form-label" for="rodzaj">Rodzaj:</label>
            <select id="rodzaj" v-model="form.rodzaj" class="form-select w-full text-base">
              <option v-for="(nazwa, kod) in rodzaje" :key="kod" :value="kod">{{ nazwa }}</option>
            </select>
            <div v-if="form.errors.rodzaj" class="form-error">{{ form.errors.rodzaj }}</div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label" for="od">Od:</label>
              <input id="od" v-model="form.od" type="date" class="form-input w-full text-base" :class="{ error: form.errors.od }" />
              <div v-if="form.errors.od" class="form-error">{{ form.errors.od }}</div>
            </div>
            <div>
              <label class="form-label" for="do">Do:</label>
              <input id="do" v-model="form.do" type="date" class="form-input w-full text-base" :class="{ error: form.errors.do }" />
              <div v-if="form.errors.do" class="form-error">{{ form.errors.do }}</div>
            </div>
          </div>
          <!-- Zanim wyśle: ile dni wybrał i czy nie wychodzi poza pobyt. -->
          <p v-if="wybraneDni" class="text-sm text-gray-700">
            Wybrano <span class="font-semibold">{{ wybraneDni }} {{ odmianaDni(wybraneDni) }}</span>: {{ zakres(form.od, form.do) }}
          </p>
          <p v-if="pozaPobytem" class="p-2 rounded bg-orange-50 text-sm text-orange-800">
            Urlop wykracza poza koniec Twojego pobytu na budowie ({{ data(pracownik.pobyt_do) }}). Możesz wysłać, ale kierownik może odrzucić.
          </p>
          <div>
            <label class="form-label" for="uwaga">Uwaga dla kierownika (nieobowiązkowo):</label>
            <textarea id="uwaga" v-model="form.uwaga" rows="2" class="form-input w-full text-base" placeholder="np. wesele brata"></textarea>
          </div>
          <button type="submit" class="btn-indigo w-full py-3 text-base" :disabled="form.processing">Wyślij do kierownika</button>
        </form>
      </section>

      <section class="bg-white rounded-xl shadow">
        <h2 class="font-bold text-gray-900 px-4 pt-4 pb-2">Moje wnioski</h2>
        <p v-if="wnioski.length === 0" class="px-4 pb-4 text-sm text-gray-400">Jeszcze nic nie składałeś.</p>
        <template v-else>
          <p v-if="nadchodzace.length === 0" class="px-4 pb-3 text-sm text-gray-400">Brak nadchodzących urlopów.</p>
          <div v-for="w in nadchodzace" :key="w.id" class="px-4 py-3 border-t border-gray-100">
            <!-- Zakres dat nie łamie się w środku; gdy brakuje miejsca, plakietka schodzi pod spód. -->
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
              <div class="font-medium text-gray-900 whitespace-nowrap">{{ zakres(w.od, w.do) }} <span class="text-gray-500 font-normal">({{ w.dni }}&nbsp;{{ odmianaDni(w.dni) }})</span></div>
              <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full border whitespace-nowrap" :class="klasa(w.status)">{{ w.status_label }}</span>
            </div>
            <div class="text-sm text-gray-600">{{ w.rodzaj }} · złożony {{ w.zlozony }}</div>
            <div v-if="w.odpowiedz" class="mt-1 text-sm italic text-gray-700">„{{ w.odpowiedz }}”</div>
            <watek-wniosku :komentarze="w.komentarze" :adres="`/u/${token}/wniosek/${w.id}/komentarz`" ja-jestem-pracownikiem />
          </div>
          <!-- Minione zwinięte: pracownik szuka tego, co przed nim, nie historii. -->
          <button v-if="minione.length" type="button" class="w-full px-4 py-3 border-t border-gray-100 text-sm text-left text-gray-500 hover:text-gray-800" @click="pokazMinione = !pokazMinione">
            {{ pokazMinione ? 'Ukryj minione' : `Minione (${minione.length})` }}
          </button>
          <template v-if="pokazMinione">
            <div v-for="w in minione" :key="w.id" class="px-4 py-3 border-t border-gray-100 bg-gray-50">
              <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <div class="text-gray-700 whitespace-nowrap">{{ zakres(w.od, w.do) }} <span class="text-gray-500">({{ w.dni }}&nbsp;{{ odmianaDni(w.dni) }})</span></div>
                <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full border whitespace-nowrap opacity-70" :class="klasa(w.status)">{{ w.status_label }}</span>
              </div>
              <div class="text-xs text-gray-500">{{ w.rodzaj }} · złożony {{ w.zlozony }}</div>
              <div v-if="w.odpowiedz" class="mt-1 text-xs italic text-gray-600">„{{ w.odpowiedz }}”</div>
              <watek-wniosku :komentarze="w.komentarze" :adres="`/u/${token}/wniosek/${w.id}/komentarz`" :mozna-pisac="false" ja-jestem-pracownikiem />
            </div>
          </template>
        </template>
      </section>

      <p class="text-xs text-gray-400 text-center">Zatwierdzony urlop trafia do kadr i do KCP. Dodaj tę stronę do ekranu głównego telefonu, żeby mieć ją pod ręką.</p>
    </main>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'
import WatekWniosku from '@/Shared/WatekWniosku.vue'

export default {
  components: { Head, WatekWniosku },
  props: {
    token: String,
    pracownik: Object,
    rodzaje: Object,
    wnioski: { type: Array, default: () => [] },
    kierownicy: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: useForm({ rodzaj: 'UW', od: '', do: '', uwaga: '' }),
      pokazMinione: false,
    }
  },
  computed: {
    dzis() {
      return new Date().toISOString().slice(0, 10)
    },
    opisPobytu() {
      if (!this.pracownik.pobyt_do) return 'pobyt bezterminowy'
      const dni = this.roznicaDni(this.dzis, this.pracownik.pobyt_do)
      if (dni < 0) return `pobyt zakończył się ${this.data(this.pracownik.pobyt_do)}`
      if (dni === 0) return 'ostatni dzień pobytu'
      return `do ${this.data(this.pracownik.pobyt_do)} · jeszcze ${dni} ${this.odmianaDni(dni)}`
    },
    wybraneDni() {
      if (!this.form.od || !this.form.do || this.form.do < this.form.od) return 0
      return this.roznicaDni(this.form.od, this.form.do) + 1
    },
    pozaPobytem() {
      return !!this.pracownik.pobyt_do && !!this.form.do && this.form.do > this.pracownik.pobyt_do
    },
    nadchodzace() {
      return this.wnioski.filter((w) => w.do >= this.dzis)
    },
    minione() {
      return this.wnioski.filter((w) => w.do < this.dzis)
    },
  },
  methods: {
    roznicaDni(od, doDaty) {
      return Math.round((new Date(doDaty) - new Date(od)) / 86400000)
    },
    // "20 września" zamiast "2026-09-20" — pracownik czyta datę, nie parsuje.
    data(iso) {
      if (!iso) return ''
      const d = new Date(iso)
      const tenRok = d.getFullYear() === new Date().getFullYear()
      return d.toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', ...(tenRok ? {} : { year: 'numeric' }) })
    },
    // "5–9 października" zamiast "5 października – 9 października":
    // krócej i nie łamie się w środku zakresu na wąskim ekranie.
    zakres(od, doDaty) {
      if (!od || !doDaty) return ''
      if (od === doDaty) return this.data(od)
      const a = new Date(od)
      const b = new Date(doDaty)
      if (a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth()) {
        return `${a.getDate()}–${this.data(doDaty)}`
      }
      return `${this.data(od)} – ${this.data(doDaty)}`
    },
    odmianaDni(n) {
      if (n === 1) return 'dzień'
      return 'dni'
    },
    wyslij() {
      this.form.post(`/u/${this.token}/wniosek`, { onSuccess: () => this.form.reset() })
    },
    wyloguj() {
      Inertia.post(`/u/${this.token}/wyloguj`)
    },
    klasa(status) {
      return {
        zlozony: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        zatwierdzony: 'bg-green-100 text-green-800 border-green-200',
        odrzucony: 'bg-red-100 text-red-800 border-red-200',
      }[status] || 'bg-gray-100 text-gray-700 border-gray-200'
    },
  },
}
</script>

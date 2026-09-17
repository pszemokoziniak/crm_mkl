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
            <span class="text-gray-400">· pobyt {{ pracownik.pobyt_od }} – {{ pracownik.pobyt_do || 'bezterminowo' }}</span>
          </template>
          <template v-else>bez przypisanej budowy</template>
        </div>
      </div>
      <button type="button" class="text-xs text-gray-500 hover:text-gray-800" @click="wyloguj">Wyjdź</button>
    </header>

    <main class="p-4 space-y-4 max-w-md mx-auto">
      <div v-if="$page.props.flash && $page.props.flash.success" class="p-3 rounded-lg bg-green-50 text-sm text-green-800">{{ $page.props.flash.success }}</div>

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
        <div v-for="w in wnioski" :key="w.id" class="px-4 py-3 border-t border-gray-100">
          <div class="flex items-center justify-between gap-2">
            <div class="font-medium text-gray-900">{{ w.od }} – {{ w.do }} <span class="text-gray-500 font-normal">({{ w.dni }} {{ w.dni === 1 ? 'dzień' : 'dni' }})</span></div>
            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full border whitespace-nowrap" :class="klasa(w.status)">{{ w.status_label }}</span>
          </div>
          <div class="text-sm text-gray-600">{{ w.rodzaj }} · złożony {{ w.zlozony }}</div>
          <div v-if="w.odpowiedz" class="mt-1 text-sm italic text-gray-700">„{{ w.odpowiedz }}”</div>
        </div>
      </section>

      <p class="text-xs text-gray-400 text-center">Zatwierdzony urlop trafia do kadr i do KCP. Dodaj tę stronę do ekranu głównego telefonu, żeby mieć ją pod ręką.</p>
    </main>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/inertia-vue3'
import { Inertia } from '@inertiajs/inertia'

export default {
  components: { Head },
  props: {
    token: String,
    pracownik: Object,
    rodzaje: Object,
    wnioski: { type: Array, default: () => [] },
  },
  data() {
    return { form: useForm({ rodzaj: 'UW', od: '', do: '', uwaga: '' }) }
  },
  methods: {
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

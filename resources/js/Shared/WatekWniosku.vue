<template>
  <!-- Rozmowa przypięta do wniosku: pracownik z telefonu, kierownik/kadry z HRM.
       Ten sam wątek na stronie pracownika, na pulpicie kierownika i u kadr. -->
  <div class="mt-2">
    <button type="button" class="text-xs text-indigo-600 hover:underline" @click="otwarty = !otwarty">
      {{ otwarty ? 'Ukryj rozmowę' : (komentarze.length ? `Rozmowa (${komentarze.length})` : 'Napisz') }}
    </button>
    <div v-if="otwarty" class="mt-2 space-y-2">
      <div
        v-for="k in komentarze"
        :key="k.id"
        class="max-w-[85%] px-3 py-2 rounded-lg text-sm"
        :class="k.od_pracownika === jaJestemPracownikiem ? 'ml-auto bg-indigo-50 text-indigo-900' : 'bg-gray-100 text-gray-800'"
      >
        <div class="whitespace-pre-line">{{ k.tresc }}</div>
        <div class="mt-0.5 text-[11px] text-gray-500">{{ k.autor }} · {{ k.kiedy }}</div>
      </div>
      <!-- Na wąskim ekranie pole i przycisk jedno pod drugim; obok siebie dopiero, gdy jest miejsce. -->
      <form v-if="moznaPisac" class="flex flex-col sm:flex-row sm:items-end gap-2" @submit.prevent="wyslij">
        <textarea v-model="form.tresc" rows="2" class="form-input w-full sm:flex-1 text-sm" :class="{ error: form.errors.tresc }" placeholder="Napisz wiadomość…" maxlength="1000"></textarea>
        <button type="submit" class="btn-indigo text-sm self-end" :disabled="form.processing || !form.tresc.trim()">Wyślij</button>
      </form>
      <div v-if="form.errors.tresc" class="form-error">{{ form.errors.tresc }}</div>
    </div>
  </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3'

export default {
  props: {
    komentarze: { type: Array, default: () => [] },
    // Dokąd idzie nowa wiadomość.
    adres: { type: String, required: true },
    moznaPisac: { type: Boolean, default: true },
    // Na stronie pracownika jego wpisy są "moje" (po prawej), w HRM — kierownika.
    jaJestemPracownikiem: Boolean,
    // Wątek z wpisami startuje rozwinięty — żeby nikt nie przegapił odpowiedzi.
    rozwiniety: Boolean,
  },
  data() {
    return {
      otwarty: this.rozwiniety || this.komentarze.length > 0,
      form: useForm({ tresc: '' }),
    }
  },
  methods: {
    wyslij() {
      this.form.post(this.adres, { preserveScroll: true, onSuccess: () => this.form.reset() })
    },
  },
}
</script>

<template>
  <div>
    <Head title="Dostęp z telefonu" />
    <pracownik-naglowek :pracownik="pracownik" tytul="Dostęp z telefonu" />
    <worker-menu :contact-id="pracownik.id" />

    <div class="max-w-2xl space-y-6">
      <!-- Link widać raz, zaraz po wydaniu — w bazie jest tylko jego skrót. -->
      <div v-if="nowy_link" class="p-4 rounded-md bg-green-50 border border-green-200">
        <div class="text-sm font-semibold text-green-900">Nowy link (widoczny tylko teraz):</div>
        <div class="mt-1 flex items-center gap-2">
          <input type="text" readonly :value="nowy_link" class="form-input flex-1 text-sm font-mono" @focus="$event.target.select()" />
          <button type="button" class="btn-indigo text-sm" @click="kopiuj">Kopiuj</button>
        </div>
        <p class="mt-2 text-xs text-green-800">Przekaż go pracownikowi tylko osobiście. Przy pierwszym wejściu ustawi PIN.</p>
      </div>

      <div class="bg-white rounded-md shadow-sm p-6">
        <h2 class="font-bold text-gray-900 mb-2">Strona pracownika na telefon</h2>
        <p class="text-sm text-gray-600 mb-4">
          Pracownik wchodzi osobistym linkiem i PIN-em, składa wnioski urlopowe i widzi, czy kierownik je zatwierdził.
          Link jest jak hasło: każde wydanie albo wysłanie unieważnia poprzedni.
        </p>

        <dl v-if="dostep" class="text-sm grid grid-cols-2 gap-y-2 mb-4">
          <dt class="text-gray-500">Link wydany</dt><dd>{{ dostep.wydany }}<span v-if="dostep.wydal" class="text-gray-500"> ({{ dostep.wydal }})</span></dd>
          <dt class="text-gray-500">PIN</dt><dd>{{ dostep.ma_pin ? 'ustawiony' : 'jeszcze nieustawiony' }}</dd>
          <dt class="text-gray-500">Ostatnie wejście</dt><dd>{{ dostep.ostatnie_wejscie || '—' }}</dd>
          <dt class="text-gray-500">Wysłany</dt><dd>{{ [dostep.wyslany_mail && `mailem ${dostep.wyslany_mail}`, dostep.wyslany_sms && `SMS-em ${dostep.wyslany_sms}`].filter(Boolean).join(', ') || '—' }}</dd>
        </dl>
        <p v-else class="text-sm text-gray-500 mb-4">Ten pracownik nie ma jeszcze dostępu.</p>

        <div class="flex flex-wrap gap-3">
          <button type="button" class="btn-indigo text-sm" :disabled="trwa" @click="wyslij('')">{{ dostep ? 'Wydaj nowy link' : 'Wydaj link' }}</button>
          <button type="button" class="px-4 py-2 rounded-sm border text-sm" :class="kontakt.email ? 'border-indigo-300 text-indigo-700 hover:bg-indigo-50' : 'border-gray-200 text-gray-400 cursor-not-allowed'" :disabled="!kontakt.email || trwa" :title="kontakt.email ? `Wyślij na ${kontakt.email}` : 'Brak e-maila w kartotece'" @click="wyslij('/mail')">Wyślij mailem</button>
          <button type="button" class="px-4 py-2 rounded-sm border text-sm" :class="kontakt.phone && sms_dostepny ? 'border-indigo-300 text-indigo-700 hover:bg-indigo-50' : 'border-gray-200 text-gray-400 cursor-not-allowed'" :disabled="!kontakt.phone || !sms_dostepny || trwa" :title="!sms_dostepny ? 'Wysyłka SMS nie jest skonfigurowana' : (kontakt.phone ? `Wyślij na ${kontakt.phone}` : 'Brak telefonu w kartotece')" @click="wyslij('/sms')">Wyślij SMS-em</button>
          <button v-if="dostep" type="button" class="ml-auto text-sm text-red-600 hover:underline" :disabled="trwa" @click="uniewaznij">Unieważnij dostęp</button>
        </div>
        <!-- Bez adresu albo numeru przycisk jest wyłączony — powód ma być
             widoczny obok, nie w dymku po najechaniu. -->
        <ul v-if="braki.length" class="mt-3 space-y-1 text-sm">
          <li v-for="b in braki" :key="b" class="text-orange-700">
            {{ b }} —
            <Link class="underline hover:text-orange-900" :href="`/contacts/${pracownik.id}/edit`">uzupełnij w Danych osobowych</Link>.
          </li>
        </ul>
        <p v-if="!sms_dostepny" class="mt-3 text-xs text-gray-400">SMS: brak konfiguracji bramki (SMSAPI_TOKEN na serwerze).</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'
import PracownikNaglowek from '@/Shared/PracownikNaglowek'
import WorkerMenu from '@/Shared/WorkerMenu'

export default {
  components: { Head, Link, PracownikNaglowek, WorkerMenu },
  layout: Layout,
  props: {
    pracownik: Object,
    kontakt: Object,
    dostep: { type: Object, default: null },
    sms_dostepny: Boolean,
    nowy_link: { type: String, default: null },
  },
  data() {
    return { trwa: false }
  },
  computed: {
    braki() {
      const lista = []
      if (!this.kontakt.email) lista.push('Pracownik nie ma adresu e-mail w kartotece')
      if (!this.kontakt.phone) lista.push('Pracownik nie ma numeru telefonu w kartotece')
      return lista
    },
  },
  methods: {
    wyslij(sufiks) {
      const pytania = { '': 'Wydać nowy link? Poprzedni przestanie działać.', '/mail': `Wysłać nowy link mailem na ${this.kontakt.email}? Poprzedni przestanie działać.`, '/sms': `Wysłać nowy link SMS-em na ${this.kontakt.phone}? Poprzedni przestanie działać.` }
      if (this.dostep && !confirm(pytania[sufiks])) return
      this.trwa = true
      router.post(`/contacts/${this.pracownik.id}/dostep${sufiks}`, {}, { preserveScroll: true, onFinish: () => { this.trwa = false } })
    },
    uniewaznij() {
      if (!confirm('Unieważnić dostęp? Link i PIN przestaną działać.')) return
      this.trwa = true
      router.delete(`/contacts/${this.pracownik.id}/dostep`, { preserveScroll: true, onFinish: () => { this.trwa = false } })
    },
    kopiuj() {
      navigator.clipboard?.writeText(this.nowy_link)
    },
  },
}
</script>

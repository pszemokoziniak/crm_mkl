<template>
  <!-- Telefon: kafelki zawijane w wiersze (pasek przewijany w bok był
       niewygodny kciukiem); szeroki ekran: jeden pasek zakładek jak dotąd. -->
  <nav class="mt-2 mb-6 md:-mx-1 md:border-b md:border-gray-200">
    <div class="flex flex-wrap gap-2 md:hidden">
      <Link
        v-for="zakladka in widoczne"
        :key="`k-${zakladka.adres}`"
        :href="`/contacts/${contactId}/${zakladka.adres}`"
        class="px-3 py-1.5 rounded-full border text-sm transition-colors"
        :class="isUrl(zakladka.adres)
          ? 'bg-green-600 border-green-600 text-white font-semibold'
          : 'bg-white border-gray-300 text-gray-700 hover:border-green-400'"
      >{{ zakladka.nazwa }}</Link>
    </div>
    <div class="hidden md:flex gap-4 whitespace-nowrap px-1 pb-2">
      <Link
        v-for="zakladka in widoczne"
        :key="zakladka.adres"
        :href="`/contacts/${contactId}/${zakladka.adres}`"
        class="flex-shrink-0 pb-1 border-b-2 transition-colors"
        :class="isUrl(zakladka.adres)
          ? 'border-green-500 text-green-600 font-medium'
          : 'border-transparent text-indigo-300 hover:text-green-500'"
      >{{ zakladka.nazwa }}</Link>
    </div>
  </nav>
</template>

<script>
import { prowadziBudowy } from '@/role'
import { Link } from '@inertiajs/inertia-vue3'

export default {
  components: { Link },
  props: {
    contactId: [Number, String],
    userOwner: Number,
  },
  data() {
    return {
      zakladki: [
        { adres: 'edit', nazwa: 'Dane osobowe' },
        { adres: 'badania', nazwa: 'Badania lekarskie' },
        { adres: 'bhp', nazwa: 'Szkolenia BHP' },
        { adres: 'pbioz', nazwa: 'CertKJ' },
        { adres: 'uprawnienia', nazwa: 'Uprawnienia' },
        { adres: 'documents', nazwa: 'Dokumenty' },
        { adres: 'jezyk', nazwa: 'Języki' },
        { adres: 'a1', nazwa: 'A1' },
        { adres: 'holiday', nazwa: 'Nieobecności' },
        { adres: 'history', nazwa: 'Historia' },
        { adres: 'umowa', nazwa: 'Umowa', tylkoBiuro: true },
        { adres: 'dostep', nazwa: 'Dostęp z telefonu', tylkoBiuro: true },
      ],
    }
  },
  computed: {
    // Rola bywa podana wprost, a bywa, że strona jej nie przekazuje —
    // wtedy bierzemy ją z danych zalogowanego użytkownika, żeby kierownik
    // nie zobaczył pozycji, do których i tak nie ma dostępu.
    rola() {
      return this.userOwner ?? this.$page.props.auth?.user?.owner
    },
    widoczne() {
      // Zaszyte "rola !== 3" gubiło kierownika projektu: widział zakładkę
      // Umowa, a serwer odmawiał jej otwarcia. Pytamy o to samo, co reszta
      // systemu — czy ta osoba prowadzi budowy, czy siedzi w biurze.
      return this.zakladki.filter((z) => !z.tylkoBiuro || !prowadziBudowy(this.rola))
    },
  },
  methods: {
    isUrl(adres) {
      return this.$page.url.replace(/\?.*$/, '').replace(/\/$/, '').endsWith(`/${adres}`)
    },
  },
}
</script>

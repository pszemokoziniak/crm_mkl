<template>
  <!-- Na telefonie dziesięć zakładek układało się w pionową listę i spychało
       treść pod ekran. Teraz to jeden pasek, który się przewija w bok;
       na szerokim ekranie mieści się w całości jak dotąd. -->
  <nav class="mt-2 mb-6 -mx-1 border-b border-gray-200">
    <div class="flex gap-4 overflow-x-auto whitespace-nowrap px-1 pb-2">
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
        { adres: 'pbioz', nazwa: 'PBiOZ' },
        { adres: 'uprawnienia', nazwa: 'Uprawnienia' },
        { adres: 'documents', nazwa: 'Dokumenty' },
        { adres: 'jezyk', nazwa: 'Języki' },
        { adres: 'a1', nazwa: 'A1' },
        { adres: 'holiday', nazwa: 'Nieobecności' },
        { adres: 'history', nazwa: 'Historia' },
        { adres: 'umowa', nazwa: 'Umowa', tylkoBiuro: true },
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
      return this.zakladki.filter((z) => !z.tylkoBiuro || this.rola !== 3)
    },
  },
  methods: {
    isUrl(adres) {
      return this.$page.url.replace(/\?.*$/, '').replace(/\/$/, '').endsWith(`/${adres}`)
    },
  },
}
</script>

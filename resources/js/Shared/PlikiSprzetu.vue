<template>
  <div v-if="pliki.length" class="mb-4 bg-white rounded-md shadow overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="naglowek-tabeli">
          <th v-if="zdjecia" class="w-px">Podgląd</th>
          <th>Nazwa</th>
          <th v-if="zdjecia" class="w-px whitespace-nowrap">Na karcie</th>
          <th class="text-right">Akcje</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="plik in pliki" :key="plik.id">
          <td v-if="zdjecia" class="px-6 py-3">
            <a :href="plik.path" target="_blank" title="Kliknij, aby powiększyć">
              <img
                :src="plik.path + '?w=100&h=100&fit=crop'"
                class="w-14 h-14 object-cover rounded border border-gray-200 hover:opacity-75"
                :alt="plik.etykieta"
              />
            </a>
          </td>
          <td class="px-6 py-3">
            <!-- Puste pole znaczy "bez własnego podpisu" — wtedy w szarej
                 podpowiedzi stoi nazwa pliku, czyli to, co i tak widać niżej. -->
            <input
              v-model="podpisy[plik.id]"
              type="text"
              class="form-input w-full"
              :placeholder="plik.name"
              @keyup.enter="zapisz(plik)"
            />
            <div class="mt-1 text-xs text-gray-400 truncate">plik: {{ plik.name }}</div>
          </td>
          <td v-if="zdjecia" class="px-6 py-3 whitespace-nowrap">
            <span v-if="plik.glowne" class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800">
              główne
            </span>
            <button
              v-else
              type="button"
              class="text-xs text-indigo-600 hover:underline"
              @click="ustawGlowne(plik)"
            >
              Ustaw jako główne
            </button>
          </td>
          <td class="px-6 py-3 text-right whitespace-nowrap">
            <!-- Wygaszenie przez :class, nie przez disabled:… — tej odmiany
                 Tailwind w tym projekcie nie generuje i klasa zniknęłaby po cichu. -->
            <button
              type="button"
              :class="zmieniony(plik) ? 'text-indigo-600 hover:underline' : 'text-gray-300 cursor-default'"
              :disabled="!zmieniony(plik)"
              @click="zapisz(plik)"
            >
              Zapisz nazwę
            </button>
            <a :href="plik.path" target="_blank" class="ml-3 text-gray-600 hover:underline">Pobierz</a>
            <button type="button" class="ml-3 text-red-600 hover:underline" @click="usun(plik)">Usuń</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <p v-else class="mb-4 text-sm text-gray-500">{{ pustoTekst }}</p>
</template>

<script>
/**
 * Lista plików już zapisanych przy sprzęcie: podpis do poprawienia, wskazanie
 * zdjęcia na kartę i usuwanie. Wgrywanie nowych zostaje w polu poniżej —
 * tamto pracuje na plikach z dysku, te wiersze na wpisach z bazy.
 */
export default {
  name: 'PlikiSprzetu',
  props: {
    narzedziaId: { type: Number, required: true },
    pliki: { type: Array, default: () => [] },
    zdjecia: { type: Boolean, default: false },
    pustoTekst: { type: String, default: 'Brak plików.' },
  },
  data() {
    return {
      podpisy: this.poczatkowePodpisy(),
    }
  },
  watch: {
    // Po zapisie wracają nowe dane z serwera — pola muszą za nimi nadążyć.
    pliki() {
      this.podpisy = this.poczatkowePodpisy()
    },
  },
  methods: {
    poczatkowePodpisy() {
      return this.pliki.reduce((zebrane, plik) => {
        zebrane[plik.id] = plik.nazwa || ''
        return zebrane
      }, {})
    },
    zmieniony(plik) {
      return (this.podpisy[plik.id] || '') !== (plik.nazwa || '')
    },
    zapisz(plik) {
      if (!this.zmieniony(plik)) return

      this.$inertia.put(
        `/narzedzia/${this.narzedziaId}/pliki/${plik.id}`,
        { nazwa: this.podpisy[plik.id] },
        { preserveScroll: true }
      )
    },
    ustawGlowne(plik) {
      this.$inertia.put(
        `/narzedzia/${this.narzedziaId}/pliki/${plik.id}`,
        { nazwa: this.podpisy[plik.id], glowne: true },
        { preserveScroll: true }
      )
    },
    usun(plik) {
      if (!confirm(`Usunąć „${plik.etykieta}" z karty sprzętu?`)) return

      this.$inertia.delete(`/narzedzia/${this.narzedziaId}/pliki/${plik.id}`, { preserveScroll: true })
    },
  },
}
</script>

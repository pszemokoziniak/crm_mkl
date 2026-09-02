<template>
  <span v-if="status.typ === 'nieobecnosc'" class="inline-flex flex-wrap items-center gap-1">
    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-full uppercase">
      {{ status.label }}
      <span v-if="status.do" class="ml-1 normal-case font-normal">do {{ status.do }}</span>
    </span>
    <!-- Nieobecność nie zdejmuje pracownika z budowy — pokazujemy obie rzeczy. -->
    <span v-if="status.budowa" class="text-xs text-gray-400">{{ status.budowa }}</span>
  </span>

  <span v-else-if="status.typ === 'budowa'" class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-green-800 bg-green-100 border border-green-200 rounded-full">
    {{ status.label }}
  </span>

  <span v-else class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 border border-gray-200 rounded-full">
    Nie pracuje
  </span>
</template>

<script>
export default {
  name: 'StatusPracownika',
  props: {
    // { typ: 'budowa'|'nieobecnosc'|'brak', label, kod, do, budowa }
    status: {
      type: Object,
      default: () => ({ typ: 'brak', label: 'Nie pracuje' }),
    },
  },
}
</script>

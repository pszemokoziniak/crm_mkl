<template>
  <div>
    <Head title="Baza wiedzy" />

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-3xl font-bold">Baza wiedzy</h1>
      <Link v-if="admin" class="btn-indigo" href="/baza-wiedzy/create">
        <span>Dodaj artykuł</span>
      </Link>
    </div>

    <p class="mb-6 text-sm text-gray-500">
      Instrukcje i procedury w jednym miejscu — żeby nie szukać ich po mailach, kiedy są akurat potrzebne.
    </p>

    <input
      v-model="form.szukaj"
      type="text"
      class="form-input mb-6 w-full max-w-md"
      placeholder="Szukaj w tytułach i treści…"
    />

    <div v-for="grupa in pogrupowane" :key="grupa.kategoria || 'bez'" class="mb-8">
      <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500">
        {{ grupa.kategoria || 'Bez kategorii' }}
      </h2>
      <div class="bg-white rounded-md shadow overflow-hidden">
        <Link
          v-for="a in grupa.artykuly"
          :key="a.id"
          :href="`/baza-wiedzy/${a.id}`"
          class="block px-6 py-4 border-t border-gray-50 first:border-t-0 hover:bg-gray-50"
        >
          <div class="flex flex-wrap items-center gap-2">
            <span class="font-medium text-gray-800">{{ a.tytul }}</span>
            <!-- Widoczna plakietka, żeby admin wiedział, czego reszta firmy nie widzi. -->
            <span v-if="a.tylko_admin" class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-200 text-gray-700">
              tylko admin
            </span>
            <span class="ml-auto text-xs text-gray-400">{{ a.zmieniony }}</span>
          </div>
          <p v-if="a.zajawka" class="mt-1 text-sm text-gray-500">{{ a.zajawka }}</p>
        </Link>
      </div>
    </div>

    <p v-if="!artykuly.length" class="bg-white rounded-md shadow px-6 py-8 text-sm text-gray-400">
      {{ filters.szukaj ? 'Nic nie pasuje do tego wyszukiwania.' : 'Nie ma jeszcze żadnego artykułu.' }}
    </p>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'

export default {
  components: { Head, Link },
  layout: Layout,
  props: {
    filters: { type: Object, default: () => ({}) },
    artykuly: { type: Array, default: () => [] },
  },
  data() {
    return {
      form: { szukaj: this.filters.szukaj },
    }
  },
  computed: {
    admin() {
      return !!this.$page.props.permissions.admin
    },
    // Kolejność kategorii bierze się z kolejności artykułów, a tę ustala baza
    // (kolejnosc, potem tytuł) — dzięki temu lista nie skacze między wejściami.
    pogrupowane() {
      const grupy = []

      this.artykuly.forEach((a) => {
        const klucz = a.kategoria || ''
        let grupa = grupy.find((g) => (g.kategoria || '') === klucz)

        if (!grupa) {
          grupa = { kategoria: a.kategoria, artykuly: [] }
          grupy.push(grupa)
        }

        grupa.artykuly.push(a)
      })

      return grupy
    },
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/baza-wiedzy', pickBy(this.form), { preserveState: true, replace: true })
      }, 300),
    },
  },
}
</script>

<template>
  <div>
    <Head title="Uprawnienia ról" />
    <h1 class="mb-2 text-3xl font-bold text-gray-900">Uprawnienia ról</h1>
    <p class="mb-6 text-sm text-gray-500 max-w-3xl">
      Zaznaczone = rola wchodzi w dany obszar. Zakres „tylko swoje budowy i ludzie” kierownika budowy
      i kierownika projektu działa niezależnie od tej tabeli. Zaznaczenie edycji dokłada podgląd samo,
      odznaczenie podglądu zabiera wszystko, co go wymaga. Administrator ma zawsze wszystko.
    </p>

    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="naglowek-tabeli">
          <tr>
            <th class="px-4 py-3 text-left" style="min-width: 260px">Obszar / uprawnienie</th>
            <th v-for="rola in stan" :key="rola.id" class="px-3 py-3 text-center align-top" style="min-width: 128px">
              <div class="font-bold">{{ rola.nazwa }}</div>
              <div class="mt-1 text-xs font-normal">
                <span v-if="!rola.edytowalna" class="text-gray-500">zawsze wszystko</span>
                <span v-else-if="zmieniona(rola)" class="text-yellow-700">niezapisane zmiany</span>
                <span v-else-if="rola.nadpisana" class="text-indigo-700">ustawione ręcznie</span>
                <span v-else class="text-gray-500">domyślne</span>
              </div>
              <div v-if="rola.edytowalna" class="mt-2 flex flex-col items-center gap-1">
                <button
                  type="button"
                  class="px-3 py-1 rounded text-xs font-medium"
                  :class="zmieniona(rola) ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-100 text-gray-400 cursor-default'"
                  :disabled="!zmieniona(rola) || zapisywanie"
                  @click="zapisz(rola)"
                >
                  Zapisz
                </button>
                <button
                  v-if="rola.nadpisana || zmieniona(rola)"
                  type="button"
                  class="text-xs text-gray-500 hover:text-gray-800 hover:underline"
                  :disabled="zapisywanie"
                  @click="przywroc(rola)"
                >
                  {{ rola.nadpisana ? 'Przywróć domyślne' : 'Cofnij zmiany' }}
                </button>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <template v-for="obszar in obszary" :key="obszar.nazwa">
            <tr class="bg-gray-50">
              <td :colspan="stan.length + 1" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-600">
                {{ obszar.nazwa }}
              </td>
            </tr>
            <tr v-for="u in obszar.uprawnienia" :key="u.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-800">
                {{ u.etykieta }}
                <span v-if="u.tylkoAdmin" class="ml-1 text-xs text-gray-400">(tylko administrator)</span>
              </td>
              <td v-for="rola in stan" :key="rola.id" class="px-3 py-2 text-center">
                <input
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-40"
                  :checked="rola.zaznaczone.includes(u.id)"
                  :disabled="!rola.edytowalna || u.tylkoAdmin"
                  :title="tytul(rola, u)"
                  @change="przelacz(rola, u, $event.target.checked)"
                />
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <h2 class="mt-10 mb-3 text-xl font-bold text-gray-900">Ostatnie zmiany</h2>
    <p v-if="historia.length === 0" class="text-sm text-gray-400 italic">Nikt jeszcze niczego nie zmieniał — wszystkie role mają domyślne uprawnienia.</p>
    <div v-else class="bg-white rounded-md shadow divide-y divide-gray-100">
      <div v-for="(z, i) in historia" :key="i" class="px-6 py-3 text-sm">
        <div class="text-gray-800">
          <span class="font-semibold">{{ z.rola }}</span>
          <span v-if="z.przywrocenie"> — przywrócono domyślne</span>
          <span class="text-gray-500"> · {{ z.kto }}, {{ z.kiedy }}</span>
        </div>
        <div v-if="z.dodane.length" class="text-green-700 text-xs mt-1">+ {{ z.dodane.join('; ') }}</div>
        <div v-if="z.odebrane.length" class="text-red-700 text-xs mt-1">− {{ z.odebrane.join('; ') }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout'

export default {
  components: { Head },
  layout: Layout,
  props: {
    obszary: Array,
    role: Array,
    historia: Array,
  },
  data() {
    return {
      zapisywanie: false,
      // Kopia robocza: co jest zaznaczone na ekranie, zanim admin kliknie Zapisz.
      stan: this.role.map((r) => ({ ...r, zaznaczone: [...r.uprawnienia] })),
    }
  },
  computed: {
    wszystkie() {
      return this.obszary.flatMap((o) => o.uprawnienia)
    },
  },
  watch: {
    role(nowe) {
      this.stan = nowe.map((r) => ({ ...r, zaznaczone: [...r.uprawnienia] }))
    },
  },
  methods: {
    zmieniona(rola) {
      const a = [...rola.zaznaczone].sort().join(',')
      const b = [...rola.uprawnienia].sort().join(',')
      return a !== b
    },
    tytul(rola, u) {
      if (!rola.edytowalna) return 'Administrator ma zawsze wszystkie uprawnienia'
      if (u.tylkoAdmin) return 'Tego uprawnienia nie da się nadać nikomu poza administratorem'
      return u.wymaga.length ? 'Wymaga: ' + u.wymaga.map((id) => this.nazwa(id)).join(', ') : ''
    },
    nazwa(id) {
      const u = this.wszystkie.find((x) => x.id === id)
      return u ? u.etykieta : id
    },
    // Zaznaczenie dokłada wszystko, czego uprawnienie wymaga (przechodnio);
    // odznaczenie zabiera wszystko, co wymagało odznaczonego.
    przelacz(rola, u, zaznacz) {
      const zbior = new Set(rola.zaznaczone)
      if (zaznacz) {
        const kolejka = [u.id]
        while (kolejka.length) {
          const id = kolejka.shift()
          if (zbior.has(id)) continue
          zbior.add(id)
          const def = this.wszystkie.find((x) => x.id === id)
          if (def) kolejka.push(...def.wymaga)
        }
      } else {
        const kolejka = [u.id]
        while (kolejka.length) {
          const id = kolejka.shift()
          if (!zbior.has(id)) continue
          zbior.delete(id)
          this.wszystkie.filter((x) => x.wymaga.includes(id)).forEach((x) => kolejka.push(x.id))
        }
      }
      rola.zaznaczone = this.wszystkie.map((x) => x.id).filter((id) => zbior.has(id))
    },
    zapisz(rola) {
      this.zapisywanie = true
      router.put(`/uprawnienia-rol/${rola.id}`, { uprawnienia: rola.zaznaczone }, {
        preserveScroll: true,
        onFinish: () => { this.zapisywanie = false },
      })
    },
    przywroc(rola) {
      if (!rola.nadpisana) {
        rola.zaznaczone = [...rola.uprawnienia]
        return
      }
      if (!confirm(`Przywrócić roli ${rola.nazwa} domyślne uprawnienia?`)) return
      this.zapisywanie = true
      router.delete(`/uprawnienia-rol/${rola.id}`, {
        preserveScroll: true,
        onFinish: () => { this.zapisywanie = false },
      })
    },
  },
}
</script>

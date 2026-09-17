<template>
  <div>
    <Head title="Kadry" />
    <h1 class="mb-2 text-3xl font-bold text-gray-900">Kadry</h1>
    <p class="mb-6 text-sm text-gray-500">
      Zgłoszenia od kierowników i zmiany pobytów na budowach do przygotowania aneksów.
      Do obsłużenia: zgłoszeń <span class="font-bold text-gray-700">{{ zgloszenia_licznik }}</span>,
      zmian pobytów <span class="font-bold text-gray-700">{{ licznik }}</span>.
    </p>

    <div class="flex items-center gap-3 mb-6">
      <div class="flex bg-white rounded shadow overflow-hidden">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium"
          :class="filters.pokaz !== 'wszystkie' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
          @click="pokaz('nieobsluzone')"
        >
          Do obsłużenia
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium"
          :class="filters.pokaz === 'wszystkie' ? 'bg-indigo-500 text-white' : 'text-gray-600 hover:bg-gray-50'"
          @click="pokaz('wszystkie')"
        >
          Wszystkie
        </button>
      </div>
      <!-- Historia bez zakresu ucina się na 300 najnowszych wpisach. -->
      <div v-if="filters.pokaz === 'wszystkie'" class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
        <label for="zk-od">od</label>
        <input id="zk-od" v-model="zakres.od" type="date" class="form-input py-1.5 text-sm" @change="pokaz('wszystkie')" />
        <label for="zk-do">do</label>
        <input id="zk-do" v-model="zakres.do" type="date" class="form-input py-1.5 text-sm" @change="pokaz('wszystkie')" />
        <button v-if="zakres.od || zakres.do" type="button" class="text-indigo-600 hover:underline" @click="zakres.od = ''; zakres.do = ''; pokaz('wszystkie')">Wyczyść</button>
      </div>
    </div>

    <!-- Zgłoszenia od kierowników: kierownik wie pierwszy o zjeździe czy urlopie,
         ale zmianę pobytu i nieobecność wstawiają kadry, po czym zamykają zgłoszenie. -->
    <h2 class="mb-3 text-xl font-bold text-gray-900">Zgłoszenia od kierowników</h2>
    <p v-if="zgloszenia.length === 0" class="mb-8 p-6 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Brak zgłoszeń{{ filters.pokaz === 'wszystkie' ? '' : ' do obsłużenia' }}.
    </p>
    <div v-else class="mb-8 bg-white rounded-md shadow divide-y divide-gray-100">
      <div v-for="z in zgloszenia" :key="z.id" class="px-6 py-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <Link class="font-semibold text-gray-900 hover:text-indigo-600" :href="`/contacts/${z.contact_id}/edit`">{{ z.pracownik }}</Link>
              <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded-full" :class="klasaZgloszenia(z.status)">{{ z.status_label }}</span>
            </div>
            <div class="mt-1 text-sm text-gray-800">
              <span class="font-medium">{{ z.rodzaj_label }}</span>
              <span v-if="z.od || z.do" class="ml-1 tabular-nums">{{ z.od || '…' }} – {{ z.do || '…' }}</span>
              <span class="ml-1 text-gray-500">·</span>
              <Link class="ml-1 text-indigo-600 hover:underline" :href="`/pracownicy/${z.organization_id}`">{{ z.budowa }}</Link>
            </div>
            <p v-if="z.uwaga" class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ z.uwaga }}</p>
            <div class="mt-1 text-xs text-gray-500">
              zgłosił {{ z.autor }}, {{ z.kiedy }}
              <a v-if="z.plik" class="ml-2 text-indigo-600 hover:underline" :href="z.plik" target="_blank" rel="noopener">skan: {{ z.plik_nazwa }}</a>
              <span v-if="z.obsluzyl" class="ml-2">· {{ z.status_label }} {{ z.obsluzone_kiedy }} ({{ z.obsluzyl }})</span>
              <span v-if="z.odpowiedz" class="ml-2 italic">„{{ z.odpowiedz }}”</span>
            </div>
          </div>
          <div v-if="z.status === 'nowe'" class="flex flex-wrap items-center gap-2 text-sm">
            <Link v-if="z.dodaj_dokument_url" class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" :href="z.dodaj_dokument_url">Dodaj dokument</Link>
            <template v-else>
              <Link v-if="z.pobyt_id" class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" :href="`/pracownicy/${z.organization_id}/edit/${z.pobyt_id}`">Popraw daty pobytu</Link>
              <Link class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" :href="`/contacts/${z.contact_id}/holiday/create`">Wstaw nieobecność</Link>
            </template>
            <button type="button" class="btn-indigo text-sm" @click="obsluzZgloszenie(z, 'obsluzone')">Obsłużone</button>
            <button type="button" class="text-red-600 hover:underline" @click="obsluzZgloszenie(z, 'odrzucone')">Odrzuć</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Wnioski urlopowe z telefonu: tu tylko podgląd, decyzję podejmuje
         kierownik; zatwierdzone przychodzą wyżej jako zgłoszenia urlopu. -->
    <h2 class="mb-3 text-xl font-bold text-gray-900">
      Wnioski urlopowe z telefonu
      <span class="ml-1 text-sm font-normal text-gray-500">— czekają na kierownika; bez kierownika decydują kadry</span>
    </h2>
    <p v-if="wnioski_z_telefonu.length === 0" class="mb-8 p-6 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Brak wniosków{{ filters.pokaz === 'wszystkie' ? '' : ' czekających na kierownika' }}.
    </p>
    <div v-else class="mb-8 bg-white rounded-md shadow divide-y divide-gray-100">
      <div v-for="w in wnioski_z_telefonu" :key="w.id" class="px-6 py-3 text-sm flex flex-wrap items-start justify-between gap-2">
        <div>
          <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${w.contact_id}/edit`">{{ w.pracownik }}</Link>
          <span class="ml-2 text-gray-700">{{ w.rodzaj }} · {{ w.od }} – {{ w.do }} ({{ w.dni }} {{ w.dni === 1 ? 'dzień' : 'dni' }})</span>
          <span v-if="w.uwaga" class="ml-2 text-gray-500 italic">„{{ w.uwaga }}”</span>
          <div class="text-xs text-gray-500">
            złożony {{ w.zlozony }}
            <span v-if="w.rozpatrzyl"> · {{ w.status_label }} {{ w.rozpatrzony }} ({{ w.rozpatrzyl }})</span>
            <span v-if="w.odpowiedz" class="italic"> „{{ w.odpowiedz }}”</span>
          </div>
          <watek-wniosku :komentarze="w.komentarze" :adres="`/wnioski-urlopowe/${w.id}/komentarze`" />
        </div>
        <div class="flex items-center gap-2">
          <!-- Pracownik bez kierownika (między budowami): wniosek zawisłby, więc decydują kadry. -->
          <template v-if="w.bez_kierownika">
            <span class="text-xs text-orange-700">bez kierownika</span>
            <button type="button" class="btn-indigo text-xs" @click="rozpatrzWniosek(w, 'zatwierdzony')">Zatwierdź</button>
            <button type="button" class="text-xs text-red-600 hover:underline" @click="rozpatrzWniosek(w, 'odrzucony')">Odrzuć</button>
          </template>
          <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded-full" :class="klasaWniosku(w.status)">{{ w.status_label }}</span>
        </div>
      </div>
    </div>

    <!-- Nowo wprowadzeni pracownicy bez badań albo BHP: upominamy się, dopóki
         obowiązkowe dokumenty nie są wpisane i ważne; potem pilnuje ich raport terminów. -->
    <h2 class="mb-3 text-xl font-bold text-gray-900">
      Nowi pracownicy: dokumenty na start
      <span class="ml-1 text-sm font-bold px-2 py-0.5 rounded-full" :class="nowi_pracownicy.length ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">{{ nowi_pracownicy.length }}</span>
    </h2>
    <p v-if="nowi_pracownicy.length === 0" class="mb-8 p-6 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Każdy pracownik wprowadzony w ostatnich 90 dniach ma ważne badania i szkolenie BHP.
    </p>
    <div v-else class="mb-8 bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Pracownik</th>
            <th>Wprowadzony</th>
            <th>Badania lekarskie</th>
            <th>Szkolenie BHP</th>
            <th>Uprawnienia <span class="font-normal text-gray-400">(opcjonalnie)</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="n in nowi_pracownicy" :key="n.id" class="hover:bg-gray-50">
            <td class="px-6 py-3"><Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${n.id}/edit`">{{ n.pracownik }}</Link></td>
            <td class="px-6 py-3 text-gray-600 tabular-nums">{{ n.wprowadzony }} <span class="text-xs text-gray-400">({{ ileDniTemu(n.dni_temu) }})</span></td>
            <td class="px-6 py-3"><komplet-dokumentu :jest="n.badania" :href="`/contacts/${n.id}/badania/create`" /></td>
            <td class="px-6 py-3"><komplet-dokumentu :jest="n.bhp" :href="`/contacts/${n.id}/bhp/create`" /></td>
            <td class="px-6 py-3"><komplet-dokumentu :jest="n.uprawnienia" :href="`/contacts/${n.id}/uprawnienia/create`" opcjonalne /></td>
          </tr>
        </tbody>
      </table>
      <p class="px-6 py-3 text-xs text-gray-500 border-t border-gray-100">
        Wiersz znika, gdy badania i BHP są wpisane i ważne. Dalej pilnuje ich raport „Termin uprawnień”.
      </p>
    </div>

    <!-- Urlopy wpisane w KCP bez skanu wniosku — ten i poprzedni miesiąc. -->
    <h2 class="mb-3 text-xl font-bold text-gray-900">
      Urlopy w KCP bez wniosku
      <span class="ml-1 text-sm font-bold px-2 py-0.5 rounded-full" :class="urlopy_bez_wniosku.length ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">{{ urlopy_bez_wniosku.length }}</span>
    </h2>
    <p v-if="urlopy_bez_wniosku.length === 0" class="mb-8 p-6 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Każdy urlop wpisany w KCP w tym i poprzednim miesiącu ma wniosek albo nieobecność w kartotece.
    </p>
    <div v-else class="mb-8 bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="naglowek-tabeli">
            <th>Pracownik</th>
            <th>Budowa</th>
            <th>Urlop w KCP</th>
            <th class="text-right">KCP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in urlopy_bez_wniosku" :key="`${u.organization_id}-${u.contact_id}-${u.od}`" class="hover:bg-gray-50">
            <td class="px-6 py-3"><Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${u.contact_id}/edit`">{{ u.pracownik }}</Link></td>
            <td class="px-6 py-3 text-gray-700">{{ u.budowa }}</td>
            <td class="px-6 py-3 tabular-nums text-gray-700">{{ u.kod }} {{ u.od }} – {{ u.do }} ({{ u.dni }} {{ u.dni === 1 ? 'dzień' : 'dni' }})</td>
            <td class="px-6 py-3 text-right"><Link class="text-indigo-600 hover:underline" :href="`/building/${u.organization_id}/time-sheet?date=${u.od}`">otwórz</Link></td>
          </tr>
        </tbody>
      </table>
      <p class="px-6 py-3 text-xs text-gray-500 border-t border-gray-100">
        Kierownik dołącza skan przyciskiem „Dodaj wniosek” w KCP; po wstawieniu nieobecności w kartotece pozycja znika sama.
      </p>
    </div>

    <h2 class="mb-3 text-xl font-bold text-gray-900">Zmiany pobytów</h2>
    <p v-if="paczki.length === 0" class="p-8 text-center text-sm text-gray-400 italic bg-white rounded-md shadow">
      Nic do obsłużenia.
    </p>

    <div v-for="paczka in paczki" :key="paczka.paczka" class="mb-4 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-gray-50 border-b border-gray-100">
        <div>
          <!-- Tytuł paczki streszcza kilka zmian; przy jednej powtarzałby
               wiersz pod spodem, więc zostaje samo "kto, kiedy". -->
          <div v-if="paczka.zmiany.length > 1" class="font-semibold text-gray-800">{{ paczka.naglowek }}</div>
          <div class="text-xs text-gray-500" :class="{ 'text-sm': paczka.zmiany.length === 1 }">
            zgłosił {{ paczka.autor }}, {{ paczka.kiedy }}
            <span v-if="paczka.nieobsluzonych > 0" class="ml-2 text-yellow-700">
              — {{ paczka.nieobsluzonych }} do obsłużenia
            </span>
            <span v-else class="ml-2 text-green-700">— obsłużone</span>
          </div>
        </div>
        <button
          v-if="paczka.nieobsluzonych > 0 && paczka.zmiany.length > 1"
          type="button"
          class="btn-indigo text-sm"
          @click="zamknij({ paczka: paczka.paczka }, `Oznaczyć całą paczkę (${paczka.osob}) jako obsłużoną?`)"
        >
          Umowy gotowe — cała paczka
        </button>
      </div>

      <!-- Każda paczka to osobna tabela; bez stałych szerokości kolumny
           w kolejnych paczkach rozjeżdżały się względem siebie. -->
      <table class="w-full text-sm table-fixed">
        <colgroup>
          <col style="width: 20%" />
          <col style="width: 22%" />
          <col style="width: 21%" />
          <col style="width: 13%" />
          <col style="width: 24%" />
        </colgroup>
        <thead>
          <tr class="naglowek-tabeli">
            <th>Pracownik</th>
            <th>Zmiana</th>
            <th>Termin</th>
            <th>Status</th>
            <th />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="zmiana in paczka.zmiany" :key="zmiana.id" class="hover:bg-gray-50">
            <td class="px-6 py-3">
              <Link class="font-medium text-gray-900 hover:text-indigo-600" :href="`/contacts/${zmiana.contact_id}/edit`">
                {{ zmiana.pracownik }}
              </Link>
              <!-- Zwolniony trafia do archiwum — nazwisko zostaje, żeby wiadomo
                   było, kogo wpis dotyczył. -->
              <span v-if="zmiana.pracownik_w_archiwum" class="block text-[10px] text-gray-400">
                w archiwum
              </span>
            </td>
            <td class="px-6 py-3 text-gray-700">
              {{ zmiana.typ_label }}
              <span v-if="zmiana.budowa_z && zmiana.budowa_do && zmiana.budowa_z !== zmiana.budowa_do" class="block text-xs text-gray-500">
                {{ zmiana.budowa_z }} → {{ zmiana.budowa_do }}
              </span>
              <span v-else-if="zmiana.budowa_do" class="block text-xs text-gray-500">{{ zmiana.budowa_do }}</span>
              <span v-else-if="zmiana.budowa_z" class="block text-xs text-gray-500">{{ zmiana.budowa_z }}</span>
            </td>
            <td class="px-6 py-3 text-gray-700">
              <span v-if="zmiana.stary_termin" class="block text-xs text-gray-400 line-through">{{ zmiana.stary_termin }}</span>
              <span v-if="zmiana.nowy_termin">{{ zmiana.nowy_termin }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-6 py-3">
              <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full border" :class="statusClass(zmiana.status)">
                {{ zmiana.status_label }}
              </span>
              <span v-if="zmiana.obsluzyl" class="block mt-1 text-[10px] text-gray-400">
                {{ zmiana.obsluzyl }}, {{ zmiana.obsluzono }}
              </span>
            </td>
            <!-- Cztery przyciski nie mieszczą się w jednej linii przy stałej
                 szerokości kolumny — niech zawijają, zamiast wychodzić poza komórkę. -->
            <td class="px-6 py-3 text-right">
              <!-- Aneks z wypełnioną budową i terminem tej zmiany. -->
              <a
                v-if="zmiana.link_aneks"
                :href="zmiana.link_aneks"
                class="text-xs text-gray-700 hover:underline mr-3"
              >
                Generuj aneks
              </a>
              <button
                v-if="zmiana.status === 'nowa'"
                type="button"
                class="text-xs text-indigo-600 hover:underline mr-3"
                @click="zmienStatus({ id: zmiana.id, status: 'w_przygotowaniu' })"
              >
                Biorę
              </button>
              <button
                v-if="!zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-green-700 hover:underline mr-3"
                @click="zmienStatus({ id: zmiana.id, status: 'gotowa' })"
              >
                Umowa gotowa
              </button>
              <!-- Zjazd z budowy bez kolejnej roboty: nie ma czego aneksować,
                   a sprawa i tak musi zejść z listy kadr. -->
              <button
                v-if="!zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-gray-600 hover:underline"
                @click="zamknijBezAneksu(zmiana)"
              >
                Bez aneksu
              </button>
              <button
                v-if="zamknieta(zmiana.status)"
                type="button"
                class="text-xs text-gray-400 hover:underline"
                @click="zmienStatus({ id: zmiana.id, status: 'nowa' })"
              >
                Cofnij
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import KompletDokumentu from '@/Shared/KompletDokumentu.vue'
import WatekWniosku from '@/Shared/WatekWniosku.vue'
import Layout from '@/Shared/Layout'

export default {
  components: {
    Head,
    KompletDokumentu,
    Link,
    WatekWniosku,
  },
  layout: Layout,
  props: {
    paczki: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    licznik: { type: Number, default: 0 },
    zgloszenia: { type: Array, default: () => [] },
    zgloszenia_licznik: { type: Number, default: 0 },
    urlopy_bez_wniosku: { type: Array, default: () => [] },
    nowi_pracownicy: { type: Array, default: () => [] },
    wnioski_z_telefonu: { type: Array, default: () => [] },
  },
  data() {
    return {
      zakres: { od: this.filters.od || '', do: this.filters.do || '' },
    }
  },
  methods: {
    ileDniTemu(dni) {
      if (dni === 0) return 'dziś'
      if (dni === 1) return 'wczoraj'
      return `${dni} dni temu`
    },
    obsluzZgloszenie(z, status) {
      const pytanie = status === 'obsluzone'
        ? `Oznaczyć zgłoszenie (${z.pracownik} — ${z.rodzaj_label}) jako obsłużone?\n\nOdpowiedź dla kierownika (opcjonalnie):`
        : `Odrzucić zgłoszenie (${z.pracownik} — ${z.rodzaj_label})?\n\nNapisz kierownikowi dlaczego:`
      const odpowiedz = prompt(pytanie, '')
      if (odpowiedz === null) return
      this.$inertia.put(`/zgloszenia/${z.id}`, { status, odpowiedz: odpowiedz || null }, { preserveScroll: true })
    },
    rozpatrzWniosek(w, status) {
      const pytanie = status === 'zatwierdzony'
        ? `Zatwierdzić urlop: ${w.pracownik}, ${w.od} – ${w.do}?\n\nOdpowiedź dla pracownika (opcjonalnie):`
        : `Odrzucić wniosek: ${w.pracownik}, ${w.od} – ${w.do}?\n\nNapisz pracownikowi dlaczego:`
      const odpowiedz = prompt(pytanie, '')
      if (odpowiedz === null) return
      this.$inertia.put(`/wnioski-urlopowe/${w.id}`, { status, odpowiedz: odpowiedz || null }, { preserveScroll: true })
    },
    klasaWniosku(status) {
      return {
        zlozony: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        zatwierdzony: 'bg-green-100 text-green-800 border-green-200',
        odrzucony: 'bg-red-100 text-red-800 border-red-200',
      }[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    },
    klasaZgloszenia(status) {
      return {
        nowe: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        obsluzone: 'bg-green-100 text-green-800 border-green-200',
        odrzucone: 'bg-red-100 text-red-800 border-red-200',
      }[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    },
    pokaz(co) {
      const dane = { pokaz: co }
      if (co === 'wszystkie') {
        if (this.zakres.od) dane.od = this.zakres.od
        if (this.zakres.do) dane.do = this.zakres.do
      }
      this.$inertia.get('/zmiany-kadrowe', dane, { preserveState: true, replace: true })
    },
    zmienStatus(dane) {
      this.$inertia.put('/zmiany-kadrowe', dane, { preserveScroll: true })
    },
    zamknieta(status) {
      return status === 'gotowa' || status === 'bez_aneksu'
    },
    zamknijBezAneksu(zmiana) {
      const pytanie = `Zamknąć bez aneksu: ${zmiana.pracownik} — ${zmiana.typ_label}?\n\n`
        + 'Użyj tego, gdy do zmiany nie trzeba żadnego dokumentu (np. pracownik zjechał z budowy). '
        + 'Wpis zostaje w rejestrze, tylko przestaje czekać na kadry.'

      if (confirm(pytanie)) {
        this.zmienStatus({ id: zmiana.id, status: 'bez_aneksu' })
      }
    },
    zamknij(dane, pytanie) {
      if (confirm(pytanie)) {
        this.zmienStatus({ ...dane, status: 'gotowa' })
      }
    },
    statusClass(status) {
      return {
        nowa: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        w_przygotowaniu: 'bg-blue-100 text-blue-800 border-blue-200',
        gotowa: 'bg-green-100 text-green-800 border-green-200',
        bez_aneksu: 'bg-gray-100 text-gray-700 border-gray-300',
      }[status] || 'bg-gray-100 text-gray-800 border-gray-200'
    },
  },
}
</script>

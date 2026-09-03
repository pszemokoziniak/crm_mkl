<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />

    <div class="grid grid-cols-3 bg-white rounded-md shadow overflow-hidden">
      <div class="grid col-span-1 relative group bg-gray-200">
        <!-- Podgląd nowo wybranego zdjęcia -->
        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" alt="Podgląd" />
        <!-- Istniejące zdjęcie -->
        <img v-else-if="contact.photo_path" :src="contact.photo_path" class="w-full h-full object-cover" alt="image" />
        <!-- Placeholder -->
        <img v-else src="/img/contacts/emptyPhoto.png?w=260&h=260&fit=fill" class="w-full h-full object-cover opacity-50" alt="Brak zdjęcia" />

        <!-- Stanowisko nakładka na zdjęcie - GÓRNY LEWY RÓG -->
        <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 via-black/50 to-transparent p-5 pt-6">
          <p class="text-[10px] font-bold text-black-300 uppercase tracking-[0.2em] mb-1 drop-shadow-sm">Stanowisko</p>
          <p class="text-2xl text-black font-black tracking-tight leading-tight drop-shadow-md uppercase">
            {{ currentFunkcjaName }}
          </p>
        </div>
      </div>
      <div class="col-span-1 p-2">
        <div class="p-2 space-y-1">
          <span class="p-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Języki:</span>
          <div v-for="item in jezyks.data" :key="item.id" class="text-sm">
            <span v-if="item.jezyk" class="font-medium text-gray-900">{{ item.jezyk.name }}</span>
            <span v-if="item.poziom" class="text-gray-500 ml-1">- {{ item.poziom }}</span>
          </div>
        </div>
        <div class="p-2 space-y-1 mt-3">
          <span class="p-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Kontakt:</span>
          <div v-if="form.phone" class="text-sm">📞 <a :href="`tel:${form.phone}`" class="text-indigo-600 hover:underline">{{ form.phone }}</a></div>
          <div v-if="form.email" class="text-sm break-all">✉️ <a :href="`mailto:${form.email}`" class="text-indigo-600 hover:underline">{{ form.email }}</a></div>
          <div v-if="!form.phone && !form.email" class="text-sm text-gray-400">brak</div>
        </div>
        <div class="p-2 space-y-1 mt-3">
          <span class="p-4 font-bold text-gray-700 uppercase text-xs tracking-wider">Statystyki:</span>
          <div class="text-sm text-gray-700">Godziny: <span class="font-semibold">{{ stats.total_hours ?? 0 }}</span></div>
          <div class="text-sm text-gray-700">Budów: <span class="font-semibold">{{ stats.builds_count ?? 0 }}</span></div>
        </div>
      </div>
      <div class="col-span-1 p-4 bg-gray-50 border-l">
        <h2 class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3 border-b pb-2">Ważne terminy:</h2>
        <div class="space-y-4">
          <div v-if="latestTermin(bhp)">
            <p class="text-[10px] text-gray-400 font-bold uppercase">BHP</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(latestTermin(bhp).end)">
              {{ latestTermin(bhp).end }}
              <span v-if="isExpiringSoon(latestTermin(bhp).end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(latestTermin(bhp).end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="latestTermin(lekarskie)">
            <p class="text-[10px] text-gray-400 font-bold uppercase">Badania lekarskie</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(latestTermin(lekarskie).end)">
              {{ latestTermin(lekarskie).end }}
              <span v-if="isExpiringSoon(latestTermin(lekarskie).end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(latestTermin(lekarskie).end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="latestTermin(a1)">
            <p class="text-[10px] text-gray-400 font-bold uppercase">A1</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(latestTermin(a1).end)">
              {{ latestTermin(a1).end }}
              <span v-if="isExpiringSoon(latestTermin(a1).end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(latestTermin(a1).end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="latestTermin(uprawnienia)">
            <p class="text-[10px] text-gray-400 font-bold uppercase">Uprawnienia</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(latestTermin(uprawnienia).end)">
              {{ latestTermin(uprawnienia).end }}
              <span v-if="isExpiringSoon(latestTermin(uprawnienia).end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(latestTermin(uprawnienia).end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="latestTermin(pbioz)">
            <p class="text-[10px] text-gray-400 font-bold uppercase">PBIOZ</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(latestTermin(pbioz).end)">
              {{ latestTermin(pbioz).end }}
              <span v-if="isExpiringSoon(latestTermin(pbioz).end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(latestTermin(pbioz).end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="form.work_end">
            <p class="text-[10px] text-gray-400 font-bold uppercase">Umowa (koniec)</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(form.work_end)">
              {{ form.work_end }}
              <span v-if="isExpiringSoon(form.work_end)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(form.work_end)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="form.idCard_date">
            <p class="text-[10px] text-gray-400 font-bold uppercase">Dowód osobisty</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(form.idCard_date)">
              {{ form.idCard_date }}
              <span v-if="isExpiringSoon(form.idCard_date)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(form.idCard_date)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="form.ekuz">
            <p class="text-[10px] text-gray-400 font-bold uppercase">EKUZ</p>
            <p class="text-sm font-semibold flex flex-wrap items-center gap-1" :class="terminClass(form.ekuz)">
              {{ form.ekuz }}
              <span v-if="isExpiringSoon(form.ekuz)" title="Zbliża się termin — do 30 dni">🔔</span>
              <span v-if="isExpired(form.ekuz)" class="text-[9px] font-bold uppercase bg-red-100 text-red-700 px-1.5 py-0.5 rounded">po terminie</span>
            </p>
          </div>

          <div v-if="!hasAnyTermin" class="text-xs text-gray-400 italic">Brak terminów</div>
        </div>
      </div>
    </div>
    <div>
      <WorkerMenu :contact-id="contactId" :uprawnienia="uprawnienia" :user-owner="user_owner" />
    </div>
    <h1 class="mb-4 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownicy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.first_name }} {{ form.last_name }}
    </h1>
    <div class="mb-6 bg-white rounded-md shadow overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
        <span class="font-semibold text-gray-700">Budowy pracownika</span>
        <!-- Co pracownik robi dzisiaj: budowa, urlop/zwolnienie albo nic. -->
        <span class="flex items-center gap-2">
          <span class="text-xs text-gray-400 uppercase tracking-wider">Obecnie:</span>
          <status-pracownika :status="status" />
        </span>
      </div>
      <div class="px-6 py-4">
        <div v-if="przypisania.length" class="space-y-1">
          <div v-for="p in przypisania" :key="p.id" class="flex flex-wrap items-center gap-x-3 text-sm">
            <Link :href="`/budowy/${p.organization_id}/edit`" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
              {{ p.nazwaBud || 'budowa usunięta' }}
            </Link>
            <span class="text-gray-500">{{ p.start }} → {{ p.end || 'bez końca' }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">Nie jest przypisany do żadnej budowy.</p>
        <p class="mt-3 text-xs text-gray-400">
          Aby przenieść pracownika na inną budowę lub go usunąć — wejdź w zakładkę danej budowy i popraw daty pobytu.
        </p>
      </div>

      <div v-if="!flag" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
        <div class="mb-3 text-sm font-medium text-gray-700">Przypisz do budowy</div>
        <form class="flex flex-wrap items-end gap-4" @submit.prevent="openAssignConfirm">
          <div class="w-full sm:w-64">
            <label class="form-label">Budowa:</label>
            <select v-model="assignForm.organization_id" class="form-select mt-1 w-full">
              <option value="">— wybierz —</option>
              <option v-for="o in organizations" :key="o.id" :value="o.id">{{ o.nazwaBud || o.name }}</option>
            </select>
            <div v-if="assignForm.errors.organization_id" class="form-error">{{ assignForm.errors.organization_id }}</div>
          </div>
          <div class="w-full sm:w-40">
            <label class="form-label">Od:</label>
            <input v-model="assignForm.start" type="date" class="form-input mt-1 w-full" />
            <div v-if="assignForm.errors.start" class="form-error">{{ assignForm.errors.start }}</div>
          </div>
          <div class="w-full sm:w-40">
            <label class="form-label">Do:</label>
            <input v-model="assignForm.end" type="date" class="form-input mt-1 w-full" />
            <div v-if="assignForm.errors.end" class="form-error">{{ assignForm.errors.end }}</div>
          </div>
          <button type="submit" class="btn-indigo">Przypisz</button>
        </form>
      </div>
    </div>

    <teleport to="body">
      <div v-if="showAssignConfirm" class="fixed inset-0 z-[10000] flex items-center justify-center p-4" @keydown.esc.window="showAssignConfirm = false">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showAssignConfirm = false" />
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Potwierdź przypisanie do budowy</h3>
          </div>
          <div class="px-6 py-4 text-sm text-gray-700 space-y-1">
            <p><span class="text-gray-500">Pracownik:</span> {{ form.last_name }} {{ form.first_name }}</p>
            <p><span class="text-gray-500">Budowa:</span> <span class="font-medium">{{ selectedBudowaName }}</span></p>
            <p><span class="text-gray-500">Termin:</span> {{ assignForm.start }} → {{ assignForm.end }}</p>

            <!-- Ten sam termin na innej budowie: kierownictwo może, reszta nie. -->
            <div v-if="kolidujacePobyty.length" class="mt-3 p-3 text-red-700 bg-red-50 border border-red-200 rounded space-y-1">
              <p class="font-semibold">Uwaga — w tym samym czasie pracownik jest już na budowie:</p>
              <p v-for="(pobyt, i) in kolidujacePobyty" :key="i">
                <span class="font-semibold">{{ pobyt.nazwaBud || 'budowa usunięta' }}</span>
                — od {{ pobyt.start }} do {{ pobyt.end || 'bez końca' }}
              </p>
              <p v-if="czyKierownictwo" class="text-xs">
                Stanowisko kierownicze może obsługiwać kilka budów naraz — jeśli to celowe, potwierdź.
              </p>
              <p v-else class="text-xs font-semibold">
                Tego przypisania nie da się zapisać. Najpierw popraw daty pobytu w zakładce tamtej budowy.
              </p>
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50" @click="showAssignConfirm = false">Anuluj</button>
            <button type="button" class="btn-indigo disabled:opacity-50 disabled:cursor-not-allowed" :disabled="assignForm.processing || zablokowane" @click="confirmAssign">Potwierdź</button>
          </div>
        </div>
      </div>
    </teleport>
    <trashed-message v-if="contact.deleted_at" class="mb-6" @restore="restore"> Ten pracownik został usunięty</trashed-message>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <fieldset :disabled="disabled === 0">
        <form @submit.prevent="submitUpdate">
          <div class="flex flex-wrap -mb-8 -mr-6 p-8">
            <text-input v-model="form.first_name" :error="form.errors.first_name" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Imię" />
            <text-input v-model="form.last_name" :error="form.errors.last_name" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwisko" />

            <text-input v-model="form.birth_date" :error="form.errors.birth_date" :disabled="flag" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Data Urodzenia" />
            <text-input v-model="form.pesel" :error="form.errors.pesel" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="PESEL" />

            <text-input v-model="form.address" :error="form.errors.address" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/1" label="Miejsce zamieszkania" />
            <text-input v-model="form.miejsce_urodzenia" :error="form.errors.miejsce_urodzenia" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/1" label="Miejsce urodzenia" />

            <text-input v-model="form.idCard_number" :error="form.errors.idCard_number" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Numer Dowodu" />
            <text-input v-model="form.idCard_date" :error="form.errors.idCard_date" :disabled="flag" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Data ważności dowodu" />

            <text-input v-model="form.email" :error="form.errors.email" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" type="email" label="Email" />
            <text-input v-model="form.phone" :error="form.errors.phone" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" type="tel" label="Telefon" />

            <select-input v-model="form.funkcja_id" :error="form.errors.funkcja_id" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Stanowisko">
              <option v-for="funkcja in funkcjas" :key="funkcja.id" :value="funkcja.id">{{ funkcja.name }}</option>
            </select-input>

            <select-input v-model="form.status_zatrudnienia" :error="form.errors.status_zatrudnienia" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Status zatrudnienia">
              <option value="Aktywny">Aktywny</option>
              <option value="Zwolniony">Zwolniony</option>
            </select-input>

            <file-input v-model="form.photo_path" :error="form.errors.photo_path" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Zdjęcie" />

            <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Umowa o pracę</label>
            <text-input v-model="form.work_start" :error="form.errors.work_start" :disabled="flag" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Początek umowy" />
            <text-input v-model="form.work_end" :error="form.errors.work_end" :disabled="flag" type="date" class="pb-8 pr-6 w-full lg:w-1/2" label="Koniec umowy" />

            <label class="text-indigo-600 font-medium pb-8 pr-6 w-full">Ekuz</label>
            <text-input v-model="form.ekuz" type="date" :error="form.errors.ekuz" :disabled="flag" class="pb-8 pr-6 w-full lg:w-1/2" label="Ważne do" />
          </div>
          <div v-if="flag === false" class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
            <delete-button v-if="!contact.deleted_at" :href="`/contacts/${contact.id}`" confirm="Chcesz usunąć pracownika?">Usuń</delete-button>
            <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz</loading-button>
          </div>
        </form>
      </fieldset>
    </div>

    <teleport to="body">
      <div v-if="showZwolnionyConfirm" class="fixed inset-0 z-[10000] flex items-center justify-center p-4" @keydown.esc.window="showZwolnionyConfirm = false">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showZwolnionyConfirm = false" />
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Pracownik zwolniony</h3>
          </div>
          <div class="px-6 py-4 text-sm text-gray-700">
            Oznaczasz <span class="font-medium">{{ form.last_name }} {{ form.first_name }}</span> jako zwolnionego.
            Czy przenieść go też do <span class="font-medium">archiwum</span>? Zniknie z aktywnej listy pracowników (dane i historia zostają).
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50" :disabled="form.processing" @click="saveWithoutArchive">Nie, zostaw w aktywnych</button>
            <button type="button" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700" :disabled="form.processing" @click="confirmArchive">Tak, do archiwum</button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import StatusPracownika from '@/Shared/StatusPracownika'
import WorkerMenu from '@/Shared/WorkerMenu'
import FileInput from '@/Shared/FileInput'
import DeleteButton from '@/Shared/DeleteButton'
import moment from 'moment'


export default {
  components: {
    StatusPracownika,
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    WorkerMenu,
    FileInput,
    DeleteButton,
  },
  layout: Layout,
  props: {
    contact: Object,
    organizations: Array,
    funkcjas: Object,
    accounts: Object,
    jezyks: Object,
    errors: Object,
    bhp: Object,
    lekarskie: Object,
    a1: Object,
    pbioz: Object,
    uprawnienia: Object,
    przypisania: { type: Array, default: () => [] },
    status: { type: Object, default: () => ({ typ: 'brak', label: 'Nie pracuje' }) },
    wszystkiePobyty: { type: Array, default: () => [] },
    czyKierownictwo: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
    flag: Boolean,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      disabled: 1,
      photoPreview: null,
      showAssignConfirm: false,
      showZwolnionyConfirm: false,
      assignForm: this.$inertia.form({
        organization_id: '',
        start: '',
        end: '',
      }),
      form: this.$inertia.form({
        first_name: this.contact.first_name,
        last_name: this.contact.last_name,
        organization_id: this.contact.organization_id,
        email: this.contact.email,
        phone: this.contact.phone,
        address: this.contact.address,
        miejsce_urodzenia: this.contact.miejsce_urodzenia,
        contactId: this.contact.id,
        birth_date: this.contact.birth_date,
        pesel: this.contact.pesel,
        idCard_number: this.contact.idCard_number,
        idCard_date: this.contact.idCard_date,
        funkcja_id: this.contact.funkcja_id,
        work_start: this.contact.work_start,
        work_end: this.contact.work_end,
        ekuz: this.contact.ekuz,
        photo_path: null,
        status_zatrudnienia: this.contact.status_zatrudnienia,
        archive: false,
      }),
    }
  },
  computed: {
    /** Pobyty na innych budowach zachodzące na wybrany termin. */
    kolidujacePobyty() {
      if (!this.assignForm.start || !this.assignForm.end) {
        return []
      }

      return this.wszystkiePobyty.filter(
        (p) => p.start <= this.assignForm.end && (!p.end || p.end >= this.assignForm.start)
      )
    },
    /** Niekierownicze stanowisko z kolizją — serwer i tak odmówi. */
    zablokowane() {
      return this.kolidujacePobyty.length > 0 && !this.czyKierownictwo
    },
    selectedBudowaName() {
      const o = (this.organizations || []).find((x) => x.id === this.assignForm.organization_id)
      return o ? (o.nazwaBud || o.name) : '—'
    },
    currentFunkcjaName() {
      const funkcja = Object.values(this.funkcjas).find(f => f.id === this.form.funkcja_id);
      return funkcja ? funkcja.name : 'Nie określono';
    },
    hasAnyTermin() {
      return (
        !!this.latestTermin(this.bhp) ||
        !!this.latestTermin(this.lekarskie) ||
        !!this.latestTermin(this.a1) ||
        !!this.latestTermin(this.uprawnienia) ||
        !!this.latestTermin(this.pbioz) ||
        !!this.form.work_end || !!this.form.idCard_date || !!this.form.ekuz
      )
    },
  },
  watch: {
    'form.photo_path': function (value) {
      if (value instanceof File) {
        const reader = new FileReader()
        reader.onload = (e) => {
          this.photoPreview = e.target.result
        }
        reader.readAsDataURL(value)
      } else {
        this.photoPreview = null
      }
    },
  },
  methods: {
    openAssignConfirm() {
      // Popup pokazujemy tylko z kompletem danych; walidację reszty robi serwer.
      if (!this.assignForm.organization_id || !this.assignForm.start || !this.assignForm.end) {
        this.assignForm.post(`/contacts/${this.contactId}/przypisz-budowe`, { preserveScroll: true })
        return
      }
      this.showAssignConfirm = true
    },
    confirmAssign() {
      this.showAssignConfirm = false
      this.assignForm.post(`/contacts/${this.contactId}/przypisz-budowe`, {
        preserveScroll: true,
        onSuccess: () => this.assignForm.reset(),
      })
    },
    filterActive(items) {
      if (!items) return []
      const today = moment().startOf('day')
      const itemsArray = Array.isArray(items) ? items : Object.values(items)
      return itemsArray.filter((item) => item.end && moment(item.end).isSameOrAfter(today))
    },
    // Najdalszy (najświeższy) termin ważności danego dokumentu — z niego wynika,
    // czy dziś jest ważny, czy już po terminie.
    latestTermin(items) {
      if (!items) return null
      const itemsArray = Array.isArray(items) ? items : Object.values(items)
      const withEnd = itemsArray.filter((item) => item.end)
      if (!withEnd.length) return null
      return withEnd.reduce((best, item) => (moment(item.end).isAfter(moment(best.end)) ? item : best))
    },
    isExpired(date) {
      if (!date) return false
      return moment(date).isBefore(moment().startOf('day'))
    },
    // Termin ważny, ale zostało nie więcej niż 30 dni — czas na odnowienie.
    isExpiringSoon(date) {
      if (!date) return false
      const today = moment().startOf('day')
      const d = moment(date).startOf('day')
      return d.isSameOrAfter(today) && d.diff(today, 'days') <= 30
    },
    terminClass(date) {
      if (this.isExpired(date)) return 'text-red-600'
      if (this.isExpiringSoon(date)) return 'text-orange-600'
      return 'text-indigo-600'
    },
    submitUpdate() {
      // Monit tylko gdy status WŁAŚNIE zmieniono na "Zwolniony".
      if (this.form.status_zatrudnienia === 'Zwolniony' && this.contact.status_zatrudnienia !== 'Zwolniony') {
        this.showZwolnionyConfirm = true
        return
      }
      this.form.archive = false
      this.doUpdate()
    },
    doUpdate() {
      this.form.post(`/contacts/${this.contact.id}`, {
        onSuccess: () => {
          this.form.reset('photo_path')
          this.photoPreview = null
        },
      })
    },
    confirmArchive() {
      this.form.archive = true
      this.showZwolnionyConfirm = false
      this.doUpdate()
    },
    saveWithoutArchive() {
      this.form.archive = false
      this.showZwolnionyConfirm = false
      this.doUpdate()
    },
    destroy() {
      if (confirm('Chcesz usunąć?')) {
        this.$inertia.delete(`/contacts/${this.contact.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić?')) {
        this.$inertia.put(`/contacts/${this.contact.id}/restore`)
      }
    },
  },
}
</script>

<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />

    <div class="grid grid-cols-3 bg-white rounded-md shadow overflow-hidden">
      <div class="grid col-span-1">
        <!-- Podgląd nowo wybranego zdjęcia -->
        <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" alt="Podgląd" />
        <!-- Istniejące zdjęcie -->
        <img v-else-if="contact.photo_path" :src="contact.photo_path" class="w-full h-full object-cover" alt="image" />
        <!-- Placeholder -->
        <img v-else src="/img/contacts/emptyPhoto.png?w=260&h=260&fit=fill" class="w-full h-full object-cover" alt="Brak zdjęcia" />
      </div>
      <div class="grid col-span-1 p-2">
        <h2 class="hover:bg-gray-100 focus-within:bg-gray-100 border-b m-1 font-medium">
          <span class="p-4">Języki:</span>
        </h2>
        <span v-for="item in jezyks.data" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <h3 v-if="item.jezyk" class="m-1">
            {{ item.jezyk.name }} - {{ item.poziom }}
          </h3>
        </span>
      </div>
      <div class="grid col-span-1 p-2">
        <h2 class="hover:bg-gray-100 focus-within:bg-gray-100 border-b m-1 font-medium">
          <span>Terminy:</span>
        </h2>
        <h3 class="m-3">
          BHP:
          <span v-for="item in bhp" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <span v-if="item.end" class="m-1">
              {{ item.end }}
            </span>
          </span>
        </h3>
        <h3 class="m-3">
          Badania lekarskie:
          <span v-for="item in lekarskie" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <span v-if="item.end" class="m-1">
              {{ item.end }}
            </span>
          </span>
        </h3>
        <h3 class="m-3">
          A1:
          <span v-for="item in a1" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <span v-if="item.end" class="m-1">
              {{ item.end }}
            </span>
          </span>
        </h3>
        <h3 class="m-3">
          Uprawnienia:
          <span v-for="item in uprawnienia" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <span v-if="item.end" class="m-1">
              {{ item.end }}
            </span>
          </span>
        </h3>
        <h3 class="m-3">
          PBIOZ:
          <span v-for="item in pbioz" :key="item.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
            <span v-if="item.end" class="m-1">
              {{ item.end }}
            </span>
          </span>
        </h3>
      </div>
    </div>
    <div>
      <WorkerMenu :contact-id="contactId" :uprawnienia="uprawnienia" />
    </div>
    <h1 class="mb-4 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/contacts">Pracownicy</Link>
      <span class="text-indigo-400 font-medium">/</span>
      {{ form.first_name }} {{ form.last_name }}
    </h1>
    <h2 class="mb-4 font-medium">
      <span class="text-indigo-400">Obecna budowa: </span>
      <span v-if="obecna_budowa !== 'Nie pracuje'" class="text-lg">{{ obecna_budowa.organization.nazwaBud }}</span>
      <span v-if="obecna_budowa === 'Nie pracuje'" class="text-lg">Nie pracuje</span>
    </h2>
    <trashed-message v-if="contact.deleted_at" :user_owner="user_owner" class="mb-6" @restore="restore"> Ten pracownik został usunięty</trashed-message>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <fieldset :disabled="disabled === 0">
        <form @submit.prevent="update">
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
            <button v-if="!contact.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
            <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz</loading-button>
          </div>
        </form>
      </fieldset>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import TrashedMessage from '@/Shared/TrashedMessage'
import WorkerMenu from '@/Shared/WorkerMenu'
import FileInput from '@/Shared/FileInput'


export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    WorkerMenu,
    FileInput,
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
    obecna_budowa: {
      type: [String, Object],
      required: false,
      default: null,
    },
    flag: Boolean,
    user_owner: Number,
  },
  remember: 'form',
  data() {
    return {
      contactId: this.contact.id,
      disabled: 1,
      photoPreview: null,
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
      }),
    }
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
    update() {
      this.form.post(`/contacts/${this.contact.id}`, {
        onSuccess: () => {
          this.form.reset('photo_path')
          this.photoPreview = null
        },
      })
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

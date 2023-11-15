<template>
  <div>
    <Head :title="`${form.first_name} ${form.last_name}`" />
    <div class="flex justify-start mb-8 max-w-3xl">
      <h1 class="text-3xl font-bold">
        <Link class="text-indigo-400 hover:text-indigo-600" href="/users">Użytkownicy</Link>
        <span class="text-indigo-400 font-medium">/</span>
        {{ form.first_name }} {{ form.last_name }}
      </h1>
      <img v-if="user.photo" class="block ml-4 w-8 h-8 rounded-full" :src="user.photo" />
    </div>
    <trashed-message v-if="user.deleted_at" class="mb-6" @restore="restore"> Usunięte. </trashed-message>
    <div class="bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.first_name" :error="form.errors.first_name" class="pb-8 pr-6 w-full lg:w-1/2" label="Imię" />
          <text-input v-model="form.last_name" :error="form.errors.last_name" class="pb-8 pr-6 w-full lg:w-1/2" label="Nazwisko" />
          <text-input v-model="form.email" :error="form.errors.email" class="pb-8 pr-6 w-full lg:w-1/2" label="Email" />
          <text-input v-model="form.password" :error="form.errors.password" class="pb-8 pr-6 w-full lg:w-1/2" type="password" autocomplete="new-password" label="Hasło" />
          <select-input v-show="userLoged === 1" v-model="form.owner" :error="form.errors.owner" class="pb-8 pr-6 w-full lg:w-1/2" label="Uprawnienia">
            <option value="1">Administrator</option>
            <option value="2">Biuro</option>
            <option value="3">Kierownik budowy</option>
          </select-input>
          <select-input v-if="userLoged === 1" v-model="form.user_id" :error="form.errors.user_id" class="pb-8 pr-6 w-full lg:w-1/2" label="Kierownik Budowy">
            <option v-for="item in contacts" :key="item.id" :value="item.id">{{item.first_name}} {{item.last_name}}</option>
          </select-input>
          <file-input v-model="form.photo" :error="form.errors.photo" class="pb-8 pr-6 w-full lg:w-1/2" type="file" accept="image/*" label="Zdjęcie" />
        </div>
        <div v-if="userLoged === 1" class="px-8 py-4 bg-gray-50 border-t border-gray-100">
          <icon name="zablokuj" class="mr-2 w-4 h-4 inline"/>
          <button v-if="user.active===1" class="text-indigo-600 hover:underline ml-auto" tabindex="-1" type="button" @click="blockActive">Zablokuj konto </button>
          <button v-if="user.active===0" class="text-indigo-600 hover:underline ml-auto" tabindex="-1" type="button" @click="unblockActive">Odblokuj konto</button>
        </div>
        <div v-if="userLoged === 1" class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!user.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń</button>
          <loading-button :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import FileInput from '@/Shared/FileInput'
import SelectInput from '@/Shared/SelectInput'
import LoadingButton from '@/Shared/LoadingButton'
import Icon from '@/Shared/Icon'
import TrashedMessage from '@/Shared/TrashedMessage.vue'

export default {
  components: {
    TrashedMessage,
    FileInput,
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    Icon,
  },
  layout: Layout,
  props: {
    user: Object,
    contacts: Array,
    userLoged: Number,
  },
  remember: 'form',
  data() {
    return {
      // user_id: this.user_id,

      form: this.$inertia.form({
        _method: 'put',
        first_name: this.user.first_name,
        last_name: this.user.last_name,
        email: this.user.email,
        password: '',
        owner: this.user.owner,
        photo: null,
        user_id: this.user_id,
        // contact_id: this.user.contact_id,
      }),
    }
  },
  methods: {
    update() {
      this.form.post(`/users/${this.user.id}`, {
        onSuccess: () => this.form.reset('password', 'photo'),
      })
    },
    destroy() {
      if (confirm('Czy chcesz usunąć użytkownika ?')) {
        this.$inertia.delete(`/users/${this.user.id}`)
      }
    },
    restore() {
      if (confirm('Chcesz usunąć użytkownika?')) {
        this.$inertia.put(`/users/${this.user.id}/restore`)
      }
    },
    blockActive() {
      this.$inertia.post(`/users/${this.user.id}/block`)
    },
    unblockActive() {
      this.$inertia.post(`/users/${this.user.id}/unblock`)
    },
  },
}
</script>

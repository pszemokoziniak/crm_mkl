<template>
  <div class="bg-white rounded-lg shadow overflow-auto grid flex py-2 px-6">
    <div>
      <span class="text-lg font-bold text-gray-800">{{ month }}</span>
      <span class="ml-1 text-lg text-gray-600 font-normal">{{ date.getFullYear() }}</span>
    </div>
    <div v-for="timeSheet in timeSheets" :key="timeSheet.id" class="flex border-t border-l">
      <div v-for="shift in timeSheet" @click="showModal(shift)" class="px-4 pt-2 border-r border-b relative cursor-pointer hover:border-green-600 hover:text-green-600 text-gray-500" style="width: 127px; height: 89px;">
        <div class="inline-flex items-center justify-center cursor-pointer text-center leading-none rounded-full text-gray-700 hover:bg-blue-200 text-gray-700 hover:bg-blue-200 text-sm">{{ (new Date(shift.day)).getDate() }}</div>
        <div class="text-sm">{{ shift.name }}</div>
        <div class="overflow-y-auto mt-1" style="height: 80px;">
          {{ shift.from }} - {{ shift.to }} <br>
          Czas: {{ shift.work }}
        </div>
      </div>
    </div>
  </div>

  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="open = false">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900"> Wprowadź dane dla dnia: </DialogTitle>
                    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
                      <fieldset :disabled="disabled == 0">
                        <form @submit.prevent="update">
                          <div class="flex flex-wrap -mb-8 -mr-6 p-8">
                            <text-input type="time" v-model="form.from" class="pb-8 pr-6 w-full lg:w-1/2" label="Od" />
                            <text-input type="time" v-model="form.to"  class="pb-8 pr-6 w-full lg:w-1/2" label="Do" />
                            <text-input type="time" v-model="form.workTime" class="pb-8 pr-6 w-full lg:w-1/2" label="Efektywny czas pracy" />
                          </div>
                        </form>
                      </fieldset>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm" @click="saveHours()">Zapisz</button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="open = false" ref="cancelButtonRef">Anuluj</button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

</template>

<script>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Layout from '@/Shared/Layout'
import TextInput from '@/Shared/TextInput'
import axios from 'axios'

export default {
  components: {
    TextInput,
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
  },
  layout: Layout,
  props: {
    build: Number,
    timeSheets: Array,
    date: Date,
    month: String,
  },
  data() {
    return {
      date: new Date(this.date),
      open: false, // default value for modal
      form: this.$inertia.form({
        id: null,
        day: null,
        from: null,
        to: null,
        workTime: null,
      }),
    }
  },
  methods: {
    showModal(shift) {
      this.open = true

      this.form = this.$inertia.form = ({
        build: shift.build,
        id: shift.id ?? null,
        day: shift.day ?? '11-08-2022',
        from: shift.from ?? '07:00',
        to: shift.to ?? '15:00',
        workTime: shift.workTime ?? '08:00',
      })
    },
    saveHours() {

      try {
        this.timeSheets[this.form.id][this.form.day] = {
          build: this.form.build,
          id: this.form.id ?? null,
          day: this.form.day,
          from: this.form.from,
          to: this.form.to,
          work: this.form.workTime,
        }

        /**
         * How to work with callback functions on $inertia
         * @see resources/js/Pages/Users/Edit.vue:73
         */
        axios.post(`/building/${this.build}/time-sheet`,this.form)


      } catch (e) {
        console.error('Something happen while saving data.')
        throw e
      }
      this.form = this.$inertia.form = ({
        id: null,
        day: '11-08-2022',
        from: '07:00',
        to: '15:00',
        workTime: '08:00',
      })
      // display notification
      this.open = false
    }
  }
}
</script>

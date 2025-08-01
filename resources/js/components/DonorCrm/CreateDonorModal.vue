<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="$emit('close')"></div>
      </div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="createDonor">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Add New Donor
                </h3>
                
                <div class="space-y-4">
                  <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">
                      Select User
                    </label>
                    <select
                      id="user_id"
                      v-model="form.user_id"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="">Select a user...</option>
                      <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }} ({{ user.email }})
                      </option>
                    </select>
                  </div>

                  <div>
                    <label for="donor_status" class="block text-sm font-medium text-gray-700">
                      Donor Status
                    </label>
                    <select
                      id="donor_status"
                      v-model="form.donor_status"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="prospect">Prospect</option>
                      <option value="active">Active</option>
                      <option value="lapsed">Lapsed</option>
                      <option value="major_gift">Major Gift</option>
                    </select>
                  </div>

                  <div>
                    <label for="preferred_contact_method" class="block text-sm font-medium text-gray-700">
                      Preferred Contact Method
                    </label>
                    <select
                      id="preferred_contact_method"
                      v-model="form.preferred_contact_method"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="email">Email</option>
                      <option value="phone">Phone</option>
                      <option value="mail">Mail</option>
                      <option value="in_person">In Person</option>
                    </select>
                  </div>

                  <div>
                    <label for="giving_capacity" class="block text-sm font-medium text-gray-700">
                      Estimated Giving Capacity
                    </label>
                    <input
                      id="giving_capacity"
                      v-model.number="form.giving_capacity"
                      type="number"
                      step="0.01"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                      Notes
                    </label>
                    <textarea
                      id="notes"
                      v-model="form.notes"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Additional notes about the donor..."
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="loading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ loading ? 'Creating...' : 'Create Donor' }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Emits
const emit = defineEmits<{
  close: []
  created: [donor: any]
}>()

// Types
interface User {
  id: number
  name: string
  email: string
}

// Reactive state
const loading = ref(false)
const users = ref<User[]>([])

const form = ref({
  user_id: '',
  donor_status: 'prospect',
  preferred_contact_method: 'email',
  giving_capacity: null as number | null,
  notes: ''
})

// Methods
const loadUsers = async () => {
  try {
    const response = await axios.get('/api/users', {
      params: { without_donor_profile: true }
    })
    users.value = response.data.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}

const createDonor = async () => {
  loading.value = true
  try {
    const response = await axios.post('/api/donor-profiles', form.value)
    emit('created', response.data)
  } catch (error) {
    console.error('Error creating donor:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadUsers()
})
</script>
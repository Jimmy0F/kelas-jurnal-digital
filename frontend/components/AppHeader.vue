<template>
  <header class="h-20 bg-transparent flex items-center justify-between px-8 select-none">
    <div>
      <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
      <p v-if="subtitle" class="text-xs text-gray-500 mt-0.5">{{ subtitle }}</p>
    </div>

    <div class="flex items-center gap-4">
      <!-- Notification Bell -->
      <NuxtLink
        to="/notifikasi"
        class="relative w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-gray-900 hover:border-gray-300 transition-colors shadow-2xs"
        title="Pemberitahuan"
      >
        <Bell class="w-5 h-5" />
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 w-5 h-5 bg-primary text-white text-[11px] font-bold rounded-full flex items-center justify-center ring-2 ring-white"
        >
          {{ unreadCount }}
        </span>
      </NuxtLink>

      <!-- Logout Button -->
      <button
        @click="handleLogout"
        class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:border-red-200 hover:text-primary rounded-xl text-xs font-semibold text-gray-700 transition-colors shadow-2xs"
      >
        <LogOut class="w-4 h-4" />
        <span>Keluar</span>
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Bell, LogOut } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'

defineProps<{
  title?: string
  subtitle?: string
}>()

const authStore = useAuthStore()
const unreadCount = ref(2)

onMounted(async () => {
  if (authStore.token) {
    try {
      const res = await $fetch<{ unread_count: number }>(`${authStore.apiBase}/notifications`, {
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      unreadCount.value = res.unread_count
    } catch (e) {
      // keep fallback
    }
  }
})

async function handleLogout() {
  await authStore.logout()
}
</script>

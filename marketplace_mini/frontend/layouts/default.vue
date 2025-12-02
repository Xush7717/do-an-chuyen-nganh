<script setup lang="ts">
// Default layout for the application
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50">
    <!-- Header -->
    <AppHeader />

    <!-- Main Content Area (grows to fill available space) -->
    <main class="flex-1 pt-8">
      <!-- pt-16 adds padding-top to account for fixed header -->
      <slot />
    </main>

    <!-- Footer -->
    <AppFooter />
  </div>
</template>

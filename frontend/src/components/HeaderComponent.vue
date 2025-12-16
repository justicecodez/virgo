<script setup>
defineProps({
    profile: {
        type: Object,
        default: null
    }
})

const formatCurrency = (value) => {
    if (value == null) return "—"

    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD"
    }).format(value)
}
</script>


<template>
    <header v-if="profile" class="bg-white shadow p-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-semibold">
                Welcome,
                <span class="text-blue-600">{{ profile.name }}</span>
            </h1>
            <p class="text-sm text-gray-500">Wallet Balance</p>
        </div>

        <div class="text-right">
            <p class="text-2xl font-bold">
                {{ formatCurrency(profile.balance) }}
            </p>
            <p class="text-sm text-green-600">Available</p>
        </div>
    </header>

    <!-- Optional loading state -->
    <div v-else class="p-4 text-gray-400">
        Loading profile...
    </div>
</template>

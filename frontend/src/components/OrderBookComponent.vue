<script setup>
defineProps({
    orderBook: {
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
    <section class="bg-white rounded-lg shadow p-4 lg:col-span-2">
        <h2 class="text-lg font-semibold mb-4">Order Book</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <ul class="space-y-1">
                    <li class="flex text-green-600 justify-between" v-for="order in orders">
                        <span>{{ formatCurrency(order.price) }}</span>
                        <span> {{ order.amount + " " + order.symbol }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
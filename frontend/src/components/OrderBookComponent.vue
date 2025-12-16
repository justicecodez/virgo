<script setup>
defineProps({
    orderBooksBuy: {
        type: Object,
        default: null
    },
    orderBooksSell: {
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

const formatCrypto = (value, decimals = 6) => {
  if (value == null) return '—'

  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: decimals
  }).format(value)
}

</script>

<template>
    <section class="bg-white rounded-lg shadow p-4 lg:col-span-2">
        <h2 class="text-lg font-semibold mb-4">Order Book</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <h3 class="font-medium capitalize text-green-600 mb-2">Buy Orders</h3>
                <ul class="space-y-1">
                    <li class="flex  justify-between" v-for="orderBookBuy in orderBooksBuy">
                        <span>{{ formatCurrency(orderBooksBuy.price) }}</span>
                        <span> {{ formatCrypto(orderBooksBuy.amount) + " " + orderBooksBuy.symbol }}</span>
                    </li>
                </ul>
            </div>

            <div>
                <h3 class="font-medium capitalize text-red-600 mb-2">sell Orders</h3>
                <ul class="space-y-1">
                    <li class="flex  justify-between" v-for="orderBookSell in orderBooksSell">
                        <span>{{ formatCurrency(orderBookSell.price) }}</span>
                        <span> {{ formatCrypto(orderBookSell.amount) + " " + orderBookSell.symbol }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

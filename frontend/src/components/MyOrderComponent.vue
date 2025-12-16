<script setup>
defineProps({
    myOrders: {
        type: Object,
        default: null
    }
})

const statusMap = {
    1: { label: 'Open', class: 'text-green-500' },
    2: { label: 'Filled', class: 'text-gray-500' },
    3: { label: 'Cancelled', class: 'text-red-500' }
}

const formatCurrency = (value) => {
    if (value == null) return "—"

    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD"
    }).format(value)
}

</script>

<template>
    <section class="bg-white rounded-lg shadow p-4">
        <h2 class="text-lg font-semibold mb-4">My Orders</h2>

        <div class="space-y-3 text-sm">
            <div v-for="myOrder in myOrders" class="border rounded p-3 flex justify-between">
                <div>
                    <p class="font-medium capitalize"> {{ myOrder.side + " " + myOrder.symbol }}</p>
                    <p class="text-gray-500">{{ formatCurrency(myOrder.price) }}</p>
                </div>
                <span class="font-medium" :class="statusMap[myOrder.status]?.class || 'text-gray-400'">
                    {{ statusMap[myOrder.status]?.label || 'Unknown' }}
                </span>


            </div>
        </div>
    </section>

</template>
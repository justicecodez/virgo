<script setup>
import { ref } from "vue";
import { placeOrderService } from '../utils/services/apiService'
const symbol = ref("BTC");
const side = ref("buy");
const price = ref("");
const amount = ref("");
const loading = ref(false);
const error = ref(null);
const success = ref(null);

const submit = async () => {
    error.value = null;
    success.value = null;
    loading.value = true;

    try {
        const order = await placeOrderService({
            symbol: symbol.value,
            side: side.value,
            price: price.value,
            amount: amount.value,
        });
        if (order.status) {
            success.value = "Order placed successfully";
            price.value = "";
            amount.value = "";
        }

    } catch (e) {
        error.value = e.response?.data?.message || "Order failed";
    } finally {
        loading.value = false;
    }
};
</script>
<template>
    <div class="max-w-md mx-auto mt-10 space-y-4">
        <h1 class="text-xl font-bold">Place Order</h1>

        <select v-model="symbol" class="border p-2 w-full">
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
        </select>

        <select v-model="side" class="border p-2 w-full">
            <option value="buy">Buy</option>
            <option value="sell">Sell</option>
        </select>

        <input v-model="price" type="number" step="0.00000001" placeholder="Price" class="border p-2 w-full" />

        <input v-model="amount" type="number" step="0.00000001" placeholder="Amount" class="border p-2 w-full" />

        <button @click="submit" :disabled="loading" class="bg-black text-white px-4 py-2 w-full">
            {{ loading ? "Placing..." : "Place Order" }}
        </button>

        <p v-if="error" class="text-red-500">{{ error }}</p>
        <p v-if="success" class="text-green-600">{{ success }}</p>
    </div>
</template>

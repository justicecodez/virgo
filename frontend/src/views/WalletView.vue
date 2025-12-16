<script setup>
import { ref, onMounted, watch, onUnmounted } from "vue";
import { placeOrderService, orderBookService, profileService, myOrderService } from "../utils/services/apiService";
import echo from "../utils/services/echo";

const profile = ref(null)
const orderbook = ref([])
const myOrders = ref([])
const selectedSymbol = ref('BTC')



onMounted(async () => {
  await fetchProfile()
  await fetchOrderbook()
  await fetchMyOrders()
})

onMounted(async () => {
  const res = await profileService();
  userId.value = res.user.id;

  echo.private(`user.${userId.value}`)
    .listen(".order.matched", (e) => {
      console.log("ORDER MATCHED", e);

      // reload balances + orders
      loadOrderbook();
      loadProfile();
    });
});

onUnmounted(() => {
  if (userId.value) {
    echo.leave(`private-user.${userId.value}`);
  }
});




const loadOrderbook = async () => {
  const res = await orderBookService(symbol.value);
  orderbook.value = res.data;
};

onMounted(loadOrderbook);

watch(symbol, loadOrderbook);
</script>

<template>
  
  <div class="mt-8">
    <h2 class="font-semibold mb-2">Orderbook</h2>

    <ul class="border divide-y">
      <li v-for="order in orderbook" :key="order.id" class="flex justify-between p-2 text-sm">
        <span>{{ order.side.toUpperCase() }}</span>
        <span>{{ order.price }}</span>
        <span>{{ order.amount }}</span>
      </li>
    </ul>
  </div>

</template>

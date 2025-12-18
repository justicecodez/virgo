<script setup>
import HeaderComponent from "../components/HeaderComponent.vue";
import OrderBookComponent from "../components/OrderBookComponent.vue";
import MyOrderComponent from "../components/MyOrderComponent.vue";
import PlaceOrderComponent from "../components/PlaceOrderComponent.vue";
import { ref, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { orderBookService, profileService, myOrderService, logoutService } from "../utils/services/apiService";
import echo from "../utils/services/echo";
import { clearUser } from "../utils/store/auth";

const profile = ref(null)
const orderBooks = ref([])
const myOrders = ref([])
const selectedSymbol = ref('BTC')
const router = useRouter();


const fetchProfile = async () => {
    const response = await profileService()
    profile.value = response.user;
}

const fetchOrderbook = async () => {
    const response = await orderBookService(selectedSymbol.value);
    orderBooks.value = response.data
}

const fetchMyOrders = async () => {
    const response = await myOrderService()
    myOrders.value = response.data
}

async function logout() {
    try {
        const response = await logoutService();
        if (response.status) {
            clearUser()
            router.replace("/login");
        }
    } catch (error) {
        console.error("Logout failed:", error);
    }

}

onMounted(async () => {
    await fetchProfile();
    await fetchOrderbook()
    await fetchMyOrders()


    const userId = profile.value.id
    echo.private(`user.${userId}`).listen('.order.matched', async () => {
        await Promise.all([
            fetchProfile(),
            fetchMyOrders(),
            fetchOrderbook()
        ]);
    })
})

watch(selectedSymbol, fetchOrderbook);



</script>

<template>
    <HeaderComponent :profile="profile" />
    <main class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="">
            <button @click="logout()" type="button"
                class="capitalize rounded bg-red-600 text-white p-3 m-1">logout</button>
        </div>
        <OrderBookComponent :orderBooksBuy="orderBooks.buy" :orderBooksSell="orderBooks.sell" />
        <PlaceOrderComponent />
        <MyOrderComponent :myOrders="myOrders" />
    </main>

</template>

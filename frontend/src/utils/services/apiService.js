import AxiosClient from "../api/axiosClient";

export const loginService=async(data)=>{
    const response=await AxiosClient.post('/api/guest/login', data);
    return response.data
}

export const logoutService=async()=>{
    const response=await AxiosClient.post('/api/guest/logout');
    return response.data;
}

export const profileService= async()=>{
    const response=await AxiosClient.get('/api/user')
    return response.data;
}

export const placeOrderService=async(data)=>{
    const response=await AxiosClient.post('/api/orders', data);
    return response.data;
}

export const orderBookService=async(symbol)=>{
    const response=await AxiosClient.get(`/api/orders/symbol/${symbol}`);
    return response.data;
}

export const myOrderService=async()=>{
    const response=await AxiosClient.get('/api/my/orders');
    return response.data;
}
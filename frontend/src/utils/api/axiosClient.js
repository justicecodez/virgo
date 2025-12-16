import axios from "axios";

const endpoint="http://localhost:8000"

const AxiosClient=axios.create({
    baseURL:endpoint,
    withCredentials: true,
});

AxiosClient.interceptors.request.use((config) => {
  const token = document.cookie
    .split("; ")
    .find((row) => row.startsWith("XSRF-TOKEN="))
    ?.split("=")[1];

  if (token) {
    config.headers["X-XSRF-TOKEN"] = decodeURIComponent(token);
  }
  return config;
});

AxiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const normalized = {
      status: error.response?.status,
      message: error.response?.data?.message || "Unexpected error",
      errors: error.response?.data?.errors || null,
    };
    return Promise.reject(normalized);
  }
);

// AxiosClient.interceptors.response.use(null, async error => {
//   if (error.response?.status === 419) {
//     await ensureCsrf()
//     return AxiosClient(error.config)
//   }
//   return Promise.reject(error)
// })


export default AxiosClient;
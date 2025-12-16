import AxiosClient from "../api/axiosClient"

let csrfLoaded = false

export async function ensureCsrf() {
  if (csrfLoaded) return

  await AxiosClient.get('/sanctum/csrf-cookie')
  csrfLoaded = true
}
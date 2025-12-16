import Echo from "laravel-echo";
import Pusher from "pusher-js";
import AxiosClient from "../api/axiosClient";

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: "pusher",
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  withCredentials: true,
  authorizer: (channel) => {
    return {
      authorize: (socketId, callback) => {
        AxiosClient.post("/broadcasting/auth", {
          socket_id: socketId,
          channel_name: channel.name,
        })
          .then((res) => callback(false, res.data))
          .catch((err) => callback(true, err));
      },
    };
  },
});

export default echo;

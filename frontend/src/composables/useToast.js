import { useToast } from 'vue-toastification'

export function useAppToast() {
  const toast = useToast()

  const success = (message) => {
    toast.success(message, {
      position: 'bottom-right',
      timeout: 3000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      newestOnTop: true,
      rtl: false
    })
  }

  const error = (message) => {
    toast.error(message, {
      position: 'bottom-right',
      timeout: 5000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      newestOnTop: true,
      rtl: false
    })
  }

  const info = (message) => {
    toast.info(message, {
      position: 'bottom-right',
      timeout: 3000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      newestOnTop: true,
      rtl: false
    })
  }

  const warning = (message) => {
    toast.warning(message, {
      position: 'bottom-right',
      timeout: 4000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      newestOnTop: true,
      rtl: false
    })
  }

  return {
    success,
    error,
    info,
    warning
  }
}

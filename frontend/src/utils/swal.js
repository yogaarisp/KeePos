import Swal from 'sweetalert2';

// Small centered alert
const SmallAlert = Swal.mixin({
  customClass: {
    popup: 'swal-small',
    title: 'swal-small-title',
    htmlContainer: 'swal-small-text',
    confirmButton: 'swal-btn-confirm',
    cancelButton: 'swal-btn-cancel'
  },
  buttonsStyling: false,
  width: '400px',
  padding: '20px'
});

// Success alert (small dialog)
export const showSuccess = (options) => {
  // Support both string and object
  if (typeof options === 'string') {
    return SmallAlert.fire({
      title: 'Berhasil!',
      text: options,
      icon: 'success',
      confirmButtonText: 'OK',
      timer: 2000,
      timerProgressBar: true
    });
  }
  
  return SmallAlert.fire({
    title: options.title || 'Berhasil!',
    text: options.text || '',
    icon: 'success',
    confirmButtonText: options.confirmText || 'OK',
    timer: options.timer || 2000,
    timerProgressBar: true
  });
};

// Error alert (small dialog)
export const showError = (options) => {
  // Support both string and object
  if (typeof options === 'string') {
    return SmallAlert.fire({
      title: 'Gagal!',
      text: options,
      icon: 'error',
      confirmButtonText: 'OK'
    });
  }
  
  return SmallAlert.fire({
    title: options.title || 'Gagal!',
    text: options.text || '',
    icon: 'error',
    confirmButtonText: options.confirmText || 'OK'
  });
};

// Warning alert (small dialog)
export const showWarning = (options) => {
  // Support both string and object
  if (typeof options === 'string') {
    return SmallAlert.fire({
      title: 'Peringatan!',
      text: options,
      icon: 'warning',
      confirmButtonText: 'OK'
    });
  }
  
  return SmallAlert.fire({
    title: options.title || 'Peringatan!',
    text: options.text || '',
    icon: 'warning',
    confirmButtonText: options.confirmText || 'OK'
  });
};

// Info alert (small dialog)
export const showInfo = (message) => {
  return SmallAlert.fire({
    title: 'Informasi',
    text: message,
    icon: 'info',
    confirmButtonText: 'OK'
  });
};

// Confirm dialog (small)
export const showConfirm = (options) => {
  return SmallAlert.fire({
    title: options.title || 'Konfirmasi',
    text: options.text || 'Apakah Anda yakin?',
    icon: options.icon || 'question',
    showCancelButton: true,
    confirmButtonText: options.confirmText || 'Ya',
    cancelButtonText: options.cancelText || 'Batal',
    reverseButtons: true
  });
};

// Alert dialog (small)
export const showAlert = (options) => {
  return SmallAlert.fire({
    title: options.title || 'Perhatian',
    text: options.text || '',
    icon: options.icon || 'info',
    confirmButtonText: options.confirmText || 'OK'
  });
};

// Toast alert (small, top-right, auto-hide)
export const showToast = (message, icon = 'success') => {
  return Swal.fire({
    text: message,
    icon: icon,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });
};

export default {
  SmallAlert,
  showSuccess,
  showError,
  showWarning,
  showInfo,
  showConfirm,
  showAlert,
  showToast
};

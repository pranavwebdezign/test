import { Snackbar, Alert } from '@mui/material';

/**
 * Drop-in MUI Snackbar notification renderer.
 * Wire up with `useNotification` hook.
 */
export default function AppSnackbar({ open, message, severity = 'info', onClose }) {
  return (
    <Snackbar
      open={open}
      autoHideDuration={3000}
      onClose={onClose}
      anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
    >
      <Alert onClose={onClose} severity={severity} variant="filled" sx={{ minWidth: 240 }}>
        {message}
      </Alert>
    </Snackbar>
  );
}

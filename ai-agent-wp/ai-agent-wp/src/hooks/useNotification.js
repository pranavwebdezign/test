import { useState, useCallback } from 'react';

/**
 * Simple notification hook — replaces Ant Design `message` API.
 * Returns { notify, close, snackbarProps } for wiring into a MUI Snackbar + Alert.
 */
export function useNotification() {
  const [state, setState] = useState({
    open: false,
    message: '',
    severity: 'info', // 'success' | 'error' | 'warning' | 'info'
  });

  const notify = useCallback((message, severity = 'info') => {
    setState({ open: true, message, severity });
  }, []);

  const close = useCallback(() => {
    setState((prev) => ({ ...prev, open: false }));
  }, []);

  return {
    notify,
    close,
    snackbarProps: {
      open: state.open,
      message: state.message,
      severity: state.severity,
      onClose: close,
    },
  };
}

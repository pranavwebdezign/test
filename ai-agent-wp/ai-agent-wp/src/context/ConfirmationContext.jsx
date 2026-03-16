import { createContext, useContext, useState, useCallback, useRef } from 'react';

const ConfirmationContext = createContext(null);

/**
 * useConfirmation — await-able confirmation dialog.
 *
 * Usage:
 *   const { confirm } = useConfirmation();
 *   const ok = await confirm({ title, message, confirmText, cancelText, severity });
 *   if (ok) { ... }
 */
export function useConfirmation() {
  const ctx = useContext(ConfirmationContext);
  if (!ctx) throw new Error('useConfirmation must be used inside <ConfirmationProvider>');
  return ctx;
}

export function ConfirmationProvider({ children }) {
  const [dialog, setDialog] = useState({ open: false, title: '', message: '', confirmText: 'Confirm', cancelText: 'Cancel', severity: 'warning' });
  const resolverRef = useRef(null);

  const confirm = useCallback(({ title = 'Are you sure?', message = '', confirmText = 'Confirm', cancelText = 'Cancel', severity = 'warning' } = {}) => {
    return new Promise((resolve) => {
      resolverRef.current = resolve;
      setDialog({ open: true, title, message, confirmText, cancelText, severity });
    });
  }, []);

  const handleConfirm = () => {
    setDialog((d) => ({ ...d, open: false }));
    resolverRef.current?.(true);
  };

  const handleCancel = () => {
    setDialog((d) => ({ ...d, open: false }));
    resolverRef.current?.(false);
  };

  return (
    <ConfirmationContext.Provider value={{ confirm }}>
      {children}
      {/* Lazy-import the dialog to keep context lean */}
      <ConfirmationDialogRenderer {...dialog} onConfirm={handleConfirm} onCancel={handleCancel} />
    </ConfirmationContext.Provider>
  );
}

// ── Inline renderer so we don't need a circular import ──────────────
import ConfirmationDialog from '../components/common/ConfirmationDialog';

function ConfirmationDialogRenderer(props) {
  return <ConfirmationDialog {...props} />;
}

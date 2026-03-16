import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogContentText,
  DialogActions,
  Button,
} from '@mui/material';

/**
 * Reusable confirmation dialog — replaces `Modal.confirm()` from Ant Design.
 * Usage:
 *   const [confirm, setConfirm] = useState({ open: false, ... });
 *   <ConfirmDialog {...confirm} onClose={() => setConfirm(p => ({...p, open: false}))} />
 */
export default function ConfirmDialog({
  open,
  title,
  content,
  okText = 'Confirm',
  cancelText = 'Cancel',
  danger = false,
  onOk,
  onClose,
}) {
  const handleOk = () => {
    onOk?.();
    onClose();
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{title}</DialogTitle>
      <DialogContent>
        <DialogContentText>{content}</DialogContentText>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>{cancelText}</Button>
        <Button
          onClick={handleOk}
          variant="contained"
          color={danger ? 'error' : 'primary'}
          autoFocus
        >
          {okText}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

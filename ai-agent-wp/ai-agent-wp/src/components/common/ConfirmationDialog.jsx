import {
  Dialog, DialogTitle, DialogContent, DialogContentText, DialogActions,
  Button, Box, useMediaQuery, useTheme, Slide,
} from '@mui/material';
import {
  DeleteOutline, WarningAmber, InfoOutlined, CheckCircleOutline,
} from '@mui/icons-material';
import { forwardRef } from 'react';

const Transition = forwardRef(function Transition(props, ref) {
  return <Slide direction="up" ref={ref} {...props} />;
});

const severityConfig = {
  error:   { icon: DeleteOutline,         color: '#DC2626', bg: '#FFF1F2', border: '#FECDD3', btnColor: 'error'   },
  warning: { icon: WarningAmber,          color: '#D97706', bg: '#FFFBEB', border: '#FDE68A', btnColor: 'warning' },
  info:    { icon: InfoOutlined,          color: '#8E43F0', bg: '#EDE8FC', border: '#D4B8FA', btnColor: 'primary' },
  success: { icon: CheckCircleOutline,    color: '#16A34A', bg: '#F0FDF4', border: '#BBF7D0', btnColor: 'success' },
};

export default function ConfirmationDialog({
  open, title, message, confirmText = 'Confirm', cancelText = 'Cancel',
  severity = 'warning', onConfirm, onCancel,
}) {
  const theme = useTheme();
  const fullWidth = useMediaQuery(theme.breakpoints.down('sm'));
  const cfg = severityConfig[severity] ?? severityConfig.warning;
  const Icon = cfg.icon;

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') onConfirm?.();
  };

  return (
    <Dialog
      open={open}
      onClose={onCancel}
      TransitionComponent={Transition}
      aria-labelledby="confirm-dialog-title"
      aria-describedby="confirm-dialog-description"
      onKeyDown={handleKeyDown}
      PaperProps={{
        sx: {
          borderRadius: 3,
          maxWidth: 440,
          width: '100%',
          boxShadow: '0 20px 60px rgba(142,67,240,0.18)',
          overflow: 'hidden',
        },
      }}
    >
      {/* Severity banner */}
      <Box sx={{ bgcolor: cfg.bg, borderBottom: `1px solid ${cfg.border}`, px: 3, pt: 3, pb: 2.5, display: 'flex', alignItems: 'flex-start', gap: 2 }}>
        <Box sx={{ width: 44, height: 44, borderRadius: 2, bgcolor: '#fff', border: `1px solid ${cfg.border}`, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
          <Icon sx={{ color: cfg.color, fontSize: 22 }} />
        </Box>
        <Box>
          <DialogTitle
            id="confirm-dialog-title"
            sx={{ p: 0, color: cfg.color, fontWeight: 700, fontSize: '1.05rem', lineHeight: 1.3 }}
          >
            {title}
          </DialogTitle>
        </Box>
      </Box>

      <DialogContent sx={{ px: 3, pt: 2.5, pb: 1 }}>
        <DialogContentText id="confirm-dialog-description" sx={{ color: '#5C4A8A', fontSize: '0.92rem', lineHeight: 1.6 }}>
          {message}
        </DialogContentText>
      </DialogContent>

      <DialogActions
        sx={{
          px: 3, pb: 3, pt: 1, gap: 1.5,
          flexDirection: fullWidth ? 'column-reverse' : 'row',
        }}
      >
        <Button
          onClick={onCancel}
          variant="outlined"
          fullWidth={fullWidth}
          sx={{ borderRadius: 2, fontWeight: 600, color: '#5C4A8A', borderColor: '#D4B8FA', '&:hover': { borderColor: '#9B89C4', bgcolor: '#F7F5FF' } }}
        >
          {cancelText}
        </Button>
        <Button
          onClick={onConfirm}
          variant="contained"
          color={cfg.btnColor}
          fullWidth={fullWidth}
          sx={{ borderRadius: 2, fontWeight: 600, boxShadow: 'none' }}
          autoFocus
        >
          {confirmText}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

import { Box, Typography, Button } from '@mui/material';
import { FolderOff, PersonOff, WebAssetOff, AssignmentLate, SearchOff } from '@mui/icons-material';

const iconMap = {
  site:     WebAssetOff,
  project:  FolderOff,
  client:   PersonOff,
  task:     AssignmentLate,
  default:  SearchOff,
};

/**
 * EmptyState — centered illustration + message for not-found scenarios.
 * Props: type ('site'|'project'|'client'|'task'), message, actionLabel, onAction
 */
export default function EmptyState({ type = 'default', message = 'Not found', actionLabel, onAction, icon: CustomIcon }) {
  const Icon = CustomIcon ?? iconMap[type] ?? iconMap.default;
  return (
    <Box
      sx={{
        display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
        minHeight: 340, gap: 2, px: 3, textAlign: 'center',
      }}
    >
      <Box sx={{ width: 72, height: 72, borderRadius: '50%', bgcolor: '#EDE8FC', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <Icon sx={{ fontSize: 36, color: '#9B89C4' }} />
      </Box>
      <Typography variant="h6" fontWeight={600} color="text.secondary">{message}</Typography>
      {actionLabel && onAction && (
        <Button variant="contained" onClick={onAction} sx={{ mt: 0.5, borderRadius: 2 }}>
          {actionLabel}
        </Button>
      )}
    </Box>
  );
}

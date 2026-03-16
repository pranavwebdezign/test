import { Box, Chip } from '@mui/material';

const config = {
  // Project / task status
  active: { label: 'Active', color: '#16A34A', bg: '#F0FDF4', border: '#BBF7D0' },
  inactive: { label: 'Inactive', color: '#5C4A8A', bg: '#EDE8FC', border: '#D4B8FA' },
  completed: { label: 'Completed', color: '#16A34A', bg: '#F0FDF4', border: '#BBF7D0' },
  review: { label: 'In Review', color: '#D97706', bg: '#FFFBEB', border: '#FDE68A' },
  in_progress: { label: 'In Progress', color: '#8E43F0', bg: '#EDE8FC', border: '#D4B8FA' },
  on_hold: { label: 'On Hold', color: '#D97706', bg: '#FFFBEB', border: '#FDE68A' },
  cancelled: { label: 'Cancelled', color: '#DC2626', bg: '#FFF1F2', border: '#FECDD3' },
  open: { label: 'Open', color: '#8E43F0', bg: '#EDE8FC', border: '#D4B8FA' },
  done: { label: 'Done', color: '#16A34A', bg: '#F0FDF4', border: '#BBF7D0' },
  paused: { label: 'Paused', color: '#5C4A8A', bg: '#EDE8FC', border: '#D4B8FA' },
  // Priority
  Critical: { label: 'Critical', color: '#DC2626', bg: '#FFF1F2', border: '#FECDD3' },
  High: { label: 'High', color: '#D97706', bg: '#FFFBEB', border: '#FDE68A' },
  Medium: { label: 'Medium', color: '#8E43F0', bg: '#EDE8FC', border: '#D4B8FA' },
  Low: { label: 'Low', color: '#5C4A8A', bg: '#EDE8FC', border: '#D4B8FA' },
  // Roles
  SuperAdmin: { label: 'Super Admin', color: '#8E43F0', bg: '#EDE8FC', border: '#D4B8FA' },
  Developer: { label: 'Developer', color: '#6A1FCC', bg: '#EDE8FC', border: '#D4B8FA' },
  Client: { label: 'Client', color: '#059669', bg: '#ECFDF5', border: '#A7F3D0' },
};

export default function StatusBadge({ value }) {
  const c = config[value] ?? { label: value ?? '—', color: '#5C4A8A', bg: '#EDE8FC', border: '#D4B8FA' };

  return (
    <Chip
      size="small"
      label={
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.6 }}>
          {/* Dot indicator */}
          <Box
            component="span"
            sx={{
              display: 'inline-block',
              width: 6, height: 6,
              borderRadius: '50%',
              bgcolor: c.color,
              flexShrink: 0,
            }}
          />
          {c.label}
        </Box>
      }
      sx={{
        fontWeight: 600,
        fontSize: '0.72rem',
        color: c.color,
        bgcolor: c.bg,
        border: `1px solid ${c.border}`,
        height: 22,
        px: 0.25,
        borderRadius: 2,
        '& .MuiChip-label': { px: 1 },
        transition: 'all 0.15s',
      }}
    />
  );
}

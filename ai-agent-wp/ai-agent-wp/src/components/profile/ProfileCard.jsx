import { Box, Card, CardContent, Avatar, Typography, Stack, Chip, Divider } from '@mui/material';
import { Email, Phone, Business, LocationOn, Circle } from '@mui/icons-material';

const roleMeta = {
  SuperAdmin: { color: '#8E43F0', bg: '#EDE8FC' },
  Developer:  { color: '#8E43F0', bg: '#EDE8FC' },
  Client:     { color: '#059669', bg: '#D1FAE5' },
};

/**
 * ProfileCard — read-only summary card for the sidebar/top.
 */
export default function ProfileCard({ profile }) {
  if (!profile) return null;
  const rm = roleMeta[profile.role] ?? roleMeta.Client;
  const initials = profile.fullName?.split(' ').map((n) => n[0]).join('').slice(0, 2) ?? '??';

  return (
    <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
      <CardContent sx={{ p: 3 }}>
        <Stack alignItems="center" spacing={1.5} sx={{ mb: 2.5 }}>
          <Avatar
            src={profile.avatar || undefined}
            sx={{ width: 80, height: 80, bgcolor: rm.color, fontSize: '1.5rem', fontWeight: 700, border: '3px solid #DDD4F8' }}
          >
            {!profile.avatar && initials}
          </Avatar>
          <Box sx={{ textAlign: 'center' }}>
            <Typography variant="h6" fontWeight={700}>{profile.fullName || '—'}</Typography>
            <Chip label={profile.role || 'No Role'} size="small"
              sx={{ mt: 0.5, fontWeight: 600, fontSize: '0.72rem', bgcolor: rm.bg, color: rm.color }} />
          </Box>
        </Stack>

        <Divider sx={{ mb: 2 }} />

        <Stack spacing={1.2}>
          {[
            { icon: Email,       value: profile.email,   label: 'Email'   },
            { icon: Phone,       value: profile.phone,   label: 'Phone'   },
            { icon: Business,    value: profile.company, label: 'Company' },
            { icon: LocationOn,  value: [profile.city, profile.country].filter(Boolean).join(', ') || null, label: 'Location' },
          ].filter((r) => r.value).map((row) => (
            <Box key={row.label} sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <row.icon sx={{ fontSize: 16, color: 'text.secondary', flexShrink: 0 }} />
              <Typography variant="body2" color="text.secondary" noWrap>{row.value}</Typography>
            </Box>
          ))}
        </Stack>

        {profile.bio && (
          <Box sx={{ mt: 2, pt: 2, borderTop: '1px solid #DDD4F8' }}>
            <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.6, display: 'block' }}>
              {profile.bio}
            </Typography>
          </Box>
        )}
      </CardContent>
    </Card>
  );
}

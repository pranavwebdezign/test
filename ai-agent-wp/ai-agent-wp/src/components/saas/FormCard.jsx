import { isValidElement } from 'react';
import { Card, CardContent, Box, Typography, Divider } from '@mui/material';

export default function FormCard({ title, subtitle, icon, children, sx, accentColor }) {
  const accent = accentColor ?? '#8E43F0';
  // Accept both <Icon /> element and Icon component reference
  const IconEl = icon
    ? isValidElement(icon)
      ? icon
      : (() => { const I = icon; return <I sx={{ fontSize: 18, color: accent }} />; })()
    : null;

  return (
    <Card
      elevation={0}
      sx={{
        borderRadius: 3,
        border: '1px solid #DDD4F8',
        overflow: 'hidden',
        transition: 'box-shadow 0.2s',
        '&:hover': { boxShadow: '0 4px 20px rgba(142,67,240,0.08)' },
        ...sx,
      }}
    >
      {(title || subtitle) && (
        <>
          <Box
            sx={{
              px: 3, pt: 2.5, pb: 2,
              display: 'flex', alignItems: 'center', gap: 1.5,
              borderLeft: `3px solid ${accent}`,
              background: 'linear-gradient(90deg, rgba(142,67,240,0.04) 0%, transparent 100%)',
            }}
          >
            {IconEl && (
              <Box
                sx={{
                  width: 36, height: 36, borderRadius: 2,
                  background: `linear-gradient(135deg, ${accent}22, ${accent}0A)`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  flexShrink: 0,
                }}
              >
                {IconEl}
              </Box>
            )}
            <Box>
              {title && (
                <Typography variant="h6" fontWeight={700} sx={{ color: '#1A0A3C', fontSize: '1rem' }}>
                  {title}
                </Typography>
              )}
              {subtitle && (
                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.2, fontSize: '0.82rem' }}>
                  {subtitle}
                </Typography>
              )}
            </Box>
          </Box>
          <Divider sx={{ borderColor: '#EDE8FC' }} />
        </>
      )}
      <CardContent sx={{ p: 3 }}>{children}</CardContent>
    </Card>
  );
}

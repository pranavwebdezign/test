import { Box, Typography, Button, Stack } from '@mui/material';
import { Home, ArrowBack, SearchOff } from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';

export default function NotFound() {
  const navigate = useNavigate();

  return (
    <Box sx={{
      minHeight: '100vh',
      display: 'flex', flexDirection: 'column',
      alignItems: 'center', justifyContent: 'center',
      bgcolor: '#F7F5FF', px: 3, textAlign: 'center',
      position: 'relative', overflow: 'hidden',
    }}>

      {/* Background decorative orbs */}
      {[
        { size: 340, top: -100, right: -100, op: 0.06, color: '#8E43F0' },
        { size: 220, bottom: -80, left: -80, op: 0.05, color: '#0099C2' },
        { size: 140, top: '40%', left: '8%', op: 0.04, color: '#8E43F0' },
      ].map((c, i) => (
        <Box key={i} sx={{
          position: 'absolute', width: c.size, height: c.size,
          borderRadius: '50%', bgcolor: c.color, opacity: c.op,
          top: c.top, bottom: c.bottom, left: c.left, right: c.right,
          animation: `pulse ${6 + i * 3}s ease-in-out infinite alternate`,
          '@keyframes pulse': {
            '0%': { transform: 'scale(1)' },
            '100%': { transform: 'scale(1.1)' },
          },
        }} />
      ))}

      {/* Content */}
      <Box sx={{
        position: 'relative', zIndex: 1,
        animation: 'fadeUp 0.6s ease both',
        '@keyframes fadeUp': {
          from: { opacity: 0, transform: 'translateY(32px)' },
          to: { opacity: 1, transform: 'translateY(0)' },
        },
      }}>

        {/* Icon box */}
        <Box sx={{
          width: 96, height: 96, borderRadius: 4, mx: 'auto', mb: 3,
          background: 'linear-gradient(135deg,#6A1FCC,#8E43F0)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          boxShadow: '0 8px 32px rgba(142,67,240,0.35)',
        }}>
          <SearchOff sx={{ fontSize: 48, color: '#fff' }} />
        </Box>

        {/* Giant gradient "404" */}
        <Typography
          sx={{
            fontSize: { xs: '6rem', sm: '9rem' },
            fontWeight: 800, lineHeight: 1,
            background: 'linear-gradient(135deg,#6A1FCC 0%,#8E43F0 45%,#0099C2 100%)',
            WebkitBackgroundClip: 'text',
            WebkitTextFillColor: 'transparent',
            backgroundClip: 'text',
            mb: 1,
            userSelect: 'none',
          }}
        >
          404
        </Typography>

        <Typography variant="h5" fontWeight={700} color="#1A0A3C" sx={{ mb: 1 }}>
          Page not found
        </Typography>

        <Typography variant="body1" color="text.secondary" sx={{ maxWidth: 400, mx: 'auto', mb: 4, lineHeight: 1.7 }}>
          The page you're looking for doesn't exist, has been moved, or you may not have permission to view it.
        </Typography>

        {/* CTA buttons */}
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} justifyContent="center">
          <Button
            variant="contained"
            size="large"
            startIcon={<Home />}
            onClick={() => navigate('/dashboard')}
            sx={{
              px: 4, py: 1.4, borderRadius: 2.5, fontWeight: 700,
              background: 'linear-gradient(135deg,#6A1FCC,#8E43F0)',
              boxShadow: '0 4px 18px rgba(142,67,240,0.35)',
              transition: 'all 0.2s',
              '&:hover': { boxShadow: '0 6px 24px rgba(142,67,240,0.45)', transform: 'translateY(-1px)' },
              '&:active': { transform: 'translateY(0)' },
            }}
          >
            Go to Dashboard
          </Button>
          <Button
            variant="outlined"
            size="large"
            startIcon={<ArrowBack />}
            onClick={() => navigate(-1)}
            sx={{
              px: 4, py: 1.4, borderRadius: 2.5, fontWeight: 700,
              borderColor: '#DDD4F8', color: '#8E43F0',
              transition: 'all 0.2s',
              '&:hover': { borderColor: '#8E43F0', bgcolor: '#F5F0FF', transform: 'translateY(-1px)' },
            }}
          >
            Go Back
          </Button>
        </Stack>

        {/* Subtle footer hint */}
        <Typography variant="caption" color="text.disabled" sx={{ display: 'block', mt: 5 }}>
          If you believe this is a mistake, contact your administrator.
        </Typography>
      </Box>
    </Box>
  );
}

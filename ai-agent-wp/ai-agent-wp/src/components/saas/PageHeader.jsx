import { Box, Typography, Breadcrumbs, Link } from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import { NavigateNext, FiberManualRecord } from '@mui/icons-material';

export default function PageHeader({ title, subtitle, action, breadcrumbs }) {
  return (
    <Box sx={{ mb: 3, position: 'relative', overflow: 'hidden' }}>
      {/* Gradient accent strip */}
      <Box
        sx={{
          position: 'absolute',
          top: 0, left: 0,
          height: 3, width: 48,
          borderRadius: 8,
          background: 'linear-gradient(90deg, #8E43F0 0%, #D4006A 100%)',
          mb: 1,
        }}
      />

      <Box sx={{ pt: 1.5 }}>
        {/* Breadcrumbs */}
        {breadcrumbs?.length > 0 && (
          <Breadcrumbs
            separator={<NavigateNext sx={{ fontSize: 13, color: 'text.disabled' }} />}
            sx={{ mb: 0.75 }}
          >
            {breadcrumbs.map((crumb, i) =>
              i < breadcrumbs.length - 1 ? (
                <Link
                  key={crumb.label}
                  component={RouterLink}
                  to={crumb.path}
                  underline="hover"
                  sx={{
                    fontSize: '0.78rem',
                    color: 'text.secondary',
                    fontWeight: 500,
                    display: 'flex', alignItems: 'center', gap: 0.5,
                    '&:hover': { color: 'primary.main' },
                    transition: 'color 0.15s',
                  }}
                >
                  {crumb.icon && <crumb.icon sx={{ fontSize: 13 }} />}
                  {crumb.label}
                </Link>
              ) : (
                <Box key={crumb.label} sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                  <FiberManualRecord sx={{ fontSize: 6, color: 'primary.main' }} />
                  <Typography
                    sx={{ fontSize: '0.78rem', color: 'text.primary', fontWeight: 600 }}
                  >
                    {crumb.label}
                  </Typography>
                </Box>
              )
            )}
          </Breadcrumbs>
        )}

        {/* Title row */}
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: { xs: 'flex-start', sm: 'center' },
            flexWrap: 'wrap',
            gap: 2,
          }}
        >
          <Box sx={{ minWidth: 0 }}>
            <Typography
              variant="h4"
              fontWeight={800}
              sx={{
                color: '#1A0A3C', mb: 0.3, letterSpacing: '-0.02em', lineHeight: 1.2,
                fontSize: { xs: '1.4rem', sm: '1.75rem', md: '2.125rem' },
              }}
            >
              {title}
            </Typography>
            {subtitle && (
              <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
                {subtitle}
              </Typography>
            )}
          </Box>

          {/* Action area */}
          {action && (
            <Box sx={{ display: 'flex', gap: 1, flexShrink: 0, flexWrap: 'wrap', width: { xs: '100%', sm: 'auto' }, justifyContent: { xs: 'flex-start', sm: 'flex-end' } }}>
              {action}
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  );
}

import { Box, Card, CardContent, Typography, Stack } from '@mui/material';
import { TrendingUp, TrendingDown } from '@mui/icons-material';

const colorMap = {
  primary: { bg: '#EDE8FC', icon: '#8E43F0', border: '#D4B8FA', grad: 'linear-gradient(135deg, rgba(142,67,240,0.14) 0%, rgba(142,67,240,0.05) 100%)' },
  success: { bg: '#F0FDF4', icon: '#16A34A', border: '#BBF7D0', grad: 'linear-gradient(135deg, rgba(22,163,74,0.14) 0%, rgba(22,163,74,0.05) 100%)' },
  warning: { bg: '#FFFBEB', icon: '#D97706', border: '#FDE68A', grad: 'linear-gradient(135deg, rgba(217,119,6,0.14) 0%, rgba(217,119,6,0.05) 100%)' },
  error: { bg: '#FFF1F2', icon: '#DC2626', border: '#FECDD3', grad: 'linear-gradient(135deg, rgba(220,38,38,0.14) 0%, rgba(220,38,38,0.05) 100%)' },
  info: { bg: '#F0FAFF', icon: '#0099C2', border: '#BAE6FD', grad: 'linear-gradient(135deg, rgba(0,153,194,0.14) 0%, rgba(0,153,194,0.05) 100%)' },
  purple: { bg: '#EDE8FC', icon: '#8E43F0', border: '#D4B8FA', grad: 'linear-gradient(135deg, rgba(142,67,240,0.14) 0%, rgba(142,67,240,0.05) 100%)' },
};

export default function StatsCard({ title, value, subtitle, icon, color = 'primary', trend, trendLabel, sx }) {
  const c = colorMap[color] ?? colorMap.primary;
  const isUp = trend > 0;

  return (
    <Card
      elevation={0}
      sx={{
        height: '100%',
        border: '1px solid #DDD4F8',
        borderRadius: 3,
        borderBottom: `3px solid ${c.icon}`,
        transition: 'all 0.22s ease',
        cursor: 'default',
        overflow: 'hidden',
        '&:hover': {
          transform: 'translateY(-3px) scale(1.015)',
          boxShadow: `0 10px 30px ${c.icon}28`,
          borderColor: c.border,
        },
        ...sx,
      }}
    >
      <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
        {/* Icon + Trend row */}
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 2 }}>

          {/* Gradient icon box */}
          <Box
            sx={{
              width: { xs: 40, sm: 46 }, height: { xs: 40, sm: 46 }, borderRadius: 2.5,
              background: c.grad,
              border: `1px solid ${c.border}`,
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              transition: 'transform 0.2s',
              '&:hover': { transform: 'scale(1.08)' },
            }}
          >
            <Box sx={{ color: c.icon, display: 'flex', '& svg': { fontSize: { xs: 19, sm: 22 } } }}>{icon}</Box>
          </Box>

          {/* Trend chip */}
          {trend !== undefined && (
            <Stack
              direction="row" alignItems="center" spacing={0.3}
              sx={{
                bgcolor: isUp ? '#F0FDF4' : '#FFF1F2',
                border: `1px solid ${isUp ? '#BBF7D0' : '#FECDD3'}`,
                px: 1, py: 0.4, borderRadius: 2,
              }}
            >
              {isUp
                ? <TrendingUp sx={{ fontSize: 13, color: '#16A34A' }} />
                : <TrendingDown sx={{ fontSize: 13, color: '#DC2626' }} />}
              <Typography variant="caption" sx={{ color: isUp ? '#16A34A' : '#DC2626', fontWeight: 700, fontSize: '0.7rem' }}>
                {isUp ? '+' : ''}{trend}%
              </Typography>
            </Stack>
          )}
        </Box>

        {/* Value */}
        <Typography
          variant="h4" fontWeight={800}
          sx={{ color: '#1A0A3C', mb: 0.4, letterSpacing: '-0.02em', lineHeight: 1.1,
            fontSize: { xs: '1.5rem', sm: '2.125rem' }
          }}
        >
          {value}
        </Typography>

        {/* Title */}
        <Typography variant="body2" color="text.secondary" fontWeight={500}>
          {title}
        </Typography>

        {/* Trend label */}
        {trendLabel && (
          <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>
            {trendLabel}
          </Typography>
        )}

        {/* Subtitle */}
        {subtitle && (
          <Typography variant="caption" color="text.secondary" sx={{ mt: 0.25, display: 'block' }}>
            {subtitle}
          </Typography>
        )}
      </CardContent>
    </Card>
  );
}

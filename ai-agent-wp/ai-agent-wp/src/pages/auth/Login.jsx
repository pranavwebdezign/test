import { useState } from 'react';
import { Link as RouterLink, useNavigate, useLocation } from 'react-router-dom';
import {
  Box, Grid, Typography, TextField, Button, Checkbox, FormControlLabel,
  Alert, CircularProgress, Divider, Link, IconButton, InputAdornment, Stack,
} from '@mui/material';
import { Email, Lock, Visibility, VisibilityOff, ArrowForward } from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';

export default function Login() {
  const { login, loading, error } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname ?? '/dashboard';

  const [form, setForm] = useState({ email: '', password: '', remember: false });
  const [showPwd, setShowPwd] = useState(false);
  const [errors, setErrors] = useState({});

  const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.email) e.email = 'Email is required';
    else if (!/\S+@\S+\.\S+/.test(form.email)) e.email = 'Enter a valid email';
    if (!form.password) e.password = 'Password is required';
    else if (form.password.length < 4) e.password = 'Minimum 4 characters';
    setErrors(e);
    return !Object.keys(e).length;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validate()) return;
    const ok = await login(form);
    if (ok) navigate(from, { replace: true });
  };

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#F7F5FF', display: 'flex' }}>
      <Grid container sx={{ flex: 1 }}>

        {/* ── Left brand panel ── */}
        <Grid
          item xs={false} md={6}
          sx={{
            display: { xs: 'none', md: 'flex' },
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            background: 'linear-gradient(135deg, #6A1FCC 0%, #8E43F0 40%, #0099C2 100%)',
            p: 6,
            position: 'relative',
            overflow: 'hidden',
          }}
        >
          {/* Animated floating dots */}
          {[{ size: 300, top: -80, right: -80, op: 0.08, anim: '8s' }, { size: 200, bottom: -60, left: -60, op: 0.06, anim: '12s' }, { size: 120, top: '45%', left: '10%', op: 0.05, anim: '6s' }].map((c, i) => (
            <Box key={i} sx={{
              position: 'absolute', width: c.size, height: c.size, borderRadius: '50%',
              bgcolor: '#fff', opacity: c.op, top: c.top, bottom: c.bottom, left: c.left, right: c.right,
              animation: `pulse ${c.anim} ease-in-out infinite alternate`,
              '@keyframes pulse': { '0%': { transform: 'scale(1)', opacity: c.op }, '100%': { transform: 'scale(1.08)', opacity: c.op * 1.5 } },
            }} />
          ))}

          <Box sx={{
            position: 'relative', zIndex: 1, textAlign: 'center', maxWidth: 440,
            animation: 'fadeSlideUp 0.7s ease both',
            '@keyframes fadeSlideUp': { from: { opacity: 0, transform: 'translateY(24px)' }, to: { opacity: 1, transform: 'translateY(0)' } },
          }}>
            {/* Logo mark */}
            <Box sx={{ width: 64, height: 64, borderRadius: 3.5, bgcolor: 'rgba(255,255,255,0.18)', border: '2px solid rgba(255,255,255,0.35)', backdropFilter: 'blur(8px)', display: 'flex', alignItems: 'center', justifyContent: 'center', mx: 'auto', mb: 4, boxShadow: '0 8px 32px rgba(0,0,0,0.12)' }}>
              <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1.6rem' }}>W</Typography>
            </Box>
            <Typography variant="h3" fontWeight={800} sx={{ color: '#fff', mb: 2, lineHeight: 1.2 }}>
              Manage your<br />digital projects
            </Typography>
            <Typography sx={{ color: 'rgba(255,255,255,0.85)', fontSize: '1.05rem', lineHeight: 1.7 }}>
              The all-in-one platform for agencies to manage clients, developers and project delivery — beautifully.
            </Typography>

            {/* Feature pills */}
            <Stack direction="row" spacing={1} justifyContent="center" sx={{ mt: 4, flexWrap: 'wrap', gap: 1 }}>
              {['Role-based access', 'Real-time tracking', 'Client portal', 'Smart reports'].map((f) => (
                <Box key={f} sx={{ px: 1.5, py: 0.6, borderRadius: 10, bgcolor: 'rgba(255,255,255,0.15)', border: '1px solid rgba(255,255,255,0.3)', backdropFilter: 'blur(4px)', transition: 'all 0.2s', '&:hover': { bgcolor: 'rgba(255,255,255,0.25)' } }}>
                  <Typography sx={{ color: '#fff', fontSize: '0.78rem', fontWeight: 500 }}>{f}</Typography>
                </Box>
              ))}
            </Stack>
          </Box>
        </Grid>

        {/* ── Right login form ── */}
        <Grid item xs={12} md={6} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', p: { xs: 3, sm: 6 } }}>
          <Box sx={{ width: '100%', maxWidth: 440 }}>
            {/* Mobile logo */}
            <Box sx={{ display: { md: 'none' }, mb: 3, textAlign: 'center' }}>
              <Box sx={{ width: 44, height: 44, borderRadius: 2, bgcolor: 'primary.main', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', mb: 1 }}>
                <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1.2rem' }}>W</Typography>
              </Box>
              <Typography variant="h6" fontWeight={700}>Webdezign</Typography>
            </Box>

            <Typography variant="h4" fontWeight={700} sx={{ color: '#1A0A3C', mb: 0.5 }}>Welcome back</Typography>
            <Typography color="text.secondary" sx={{ mb: 3 }}>Sign in to your account to continue</Typography>

            {/* Dev quick-fill — 3 buttons */}
            <Box sx={{ mb: 3, p: 2, bgcolor: 'rgba(142,67,240,0.06)', borderRadius: 2.5, border: '1px solid #D4B8FA' }}>
              <Typography variant="caption" fontWeight={700} color="primary.main" sx={{ display: 'block', mb: 1 }}>
                🔑 Quick Login
              </Typography>
              <Stack direction="row" spacing={1} flexWrap="wrap" gap={0.5}>
                {[
                  { label: 'Super Admin', email: 'superadmin@demo.com', color: '#1A0A3C', bg: '#EDE8FC' },
                  { label: 'Admin', email: 'admin@demo.com', color: '#8E43F0', bg: '#EDE8FC' },
                  { label: 'Developer', email: 'dev@demo.com', color: '#6A1FCC', bg: '#EDE8FC' },
                  { label: 'Client', email: 'client@demo.com', color: '#059669', bg: '#D1FAE5' },
                ].map(({ label, email, color, bg }) => (
                  <Button
                    key={label}
                    size="small"
                    onClick={() => setForm({ email, password: 'password', remember: false })}
                    sx={{
                      fontSize: '0.72rem', fontWeight: 700, px: 1.5, py: 0.5,
                      bgcolor: bg, color, border: `1px solid ${color}33`,
                      borderRadius: 2, textTransform: 'none', minWidth: 0,
                      '&:hover': { bgcolor: color, color: '#fff' },
                      transition: 'all 0.15s',
                    }}
                  >
                    {label}
                  </Button>
                ))}
              </Stack>
            </Box>

            {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>{error}</Alert>}

            <Box component="form" onSubmit={handleSubmit}>
              <Stack spacing={2.5}>
                <TextField
                  label="Email Address" type="email" fullWidth
                  value={form.email} onChange={(e) => set('email', e.target.value)}
                  error={!!errors.email} helperText={errors.email}
                  InputProps={{ startAdornment: <InputAdornment position="start"><Email fontSize="small" sx={{ color: 'text.secondary' }} /></InputAdornment> }}
                />
                <TextField
                  label="Password" type={showPwd ? 'text' : 'password'} fullWidth
                  value={form.password} onChange={(e) => set('password', e.target.value)}
                  error={!!errors.password} helperText={errors.password}
                  InputProps={{
                    startAdornment: <InputAdornment position="start"><Lock fontSize="small" sx={{ color: 'text.secondary' }} /></InputAdornment>,
                    endAdornment: (
                      <InputAdornment position="end">
                        <IconButton size="small" onClick={() => setShowPwd((v) => !v)} edge="end">
                          {showPwd ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                        </IconButton>
                      </InputAdornment>
                    ),
                  }}
                />
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <FormControlLabel
                    control={<Checkbox size="small" checked={form.remember} onChange={(e) => set('remember', e.target.checked)} />}
                    label={<Typography variant="body2">Remember me</Typography>}
                  />
                  <Link component={RouterLink} to="/forgot-password" variant="body2" color="primary.main" fontWeight={600} underline="none">
                    Forgot password?
                  </Link>
                </Box>
                <Button
                  type="submit" variant="contained" fullWidth size="large"
                  disabled={loading}
                  endIcon={loading ? <CircularProgress size={16} color="inherit" /> : <ArrowForward fontSize="small" />}
                  sx={{
                    py: 1.4, fontSize: '1rem', fontWeight: 700,
                    background: 'linear-gradient(135deg,#6A1FCC,#8E43F0)',
                    boxShadow: '0 4px 18px rgba(142,67,240,0.35)',
                    transition: 'all 0.2s',
                    '&:hover': { boxShadow: '0 6px 24px rgba(142,67,240,0.45)', transform: 'translateY(-1px)' },
                    '&:active': { transform: 'translateY(0)' },
                  }}
                >
                  {loading ? 'Signing in…' : 'Sign In'}
                </Button>
              </Stack>
            </Box>

            <Divider sx={{ my: 3 }}><Typography variant="caption" color="text.secondary">New to Webdezign?</Typography></Divider>
            <Button component={RouterLink} to="/signup" variant="outlined" fullWidth size="large" sx={{ py: 1.4 }}>
              Create an account
            </Button>
          </Box>
        </Grid>
      </Grid>
    </Box>
  );
}

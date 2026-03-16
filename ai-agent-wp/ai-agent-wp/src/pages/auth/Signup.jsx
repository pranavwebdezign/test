import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import {
  Box, Grid, Typography, TextField, Button, Alert, CircularProgress,
  Link, IconButton, InputAdornment, Stack,
} from '@mui/material';
import { Person, Email, Lock, Business, Visibility, VisibilityOff, ArrowForward } from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';

export default function Signup() {
  const { signup, loading, error } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ name: '', email: '', company: '', password: '', confirm: '' });
  const [showPwd, setShowPwd] = useState(false);
  const [errors, setErrors] = useState({});
  const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.name.trim())    e.name    = 'Full name is required';
    if (!form.email)          e.email   = 'Email is required';
    else if (!/\S+@\S+\.\S+/.test(form.email)) e.email = 'Enter a valid email';
    if (!form.company.trim()) e.company = 'Company name is required';
    if (!form.password)       e.password = 'Password is required';
    else if (form.password.length < 6) e.password = 'Minimum 6 characters';
    if (form.password !== form.confirm) e.confirm = 'Passwords do not match';
    setErrors(e);
    return !Object.keys(e).length;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validate()) return;
    const ok = await signup(form);
    if (ok) navigate('/dashboard');
  };

  const Field = ({ name, label, type = 'text', icon, end }) => (
    <TextField
      label={label} type={type} fullWidth
      value={form[name]} onChange={(ev) => set(name, ev.target.value)}
      error={!!errors[name]} helperText={errors[name]}
      InputProps={{
        startAdornment: <InputAdornment position="start">{icon}</InputAdornment>,
        ...(end ? { endAdornment: end } : {}),
      }}
    />
  );

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#F7F5FF', display: 'flex' }}>
      <Grid container sx={{ flex: 1 }}>
        {/* Left brand panel */}
        <Grid item xs={false} md={5} sx={{
          display: { xs: 'none', md: 'flex' }, flexDirection: 'column',
          justifyContent: 'center', alignItems: 'center',
          background: 'linear-gradient(150deg, #1A0A3C 0%, #6A1FCC 100%)', p: 6,
        }}>
          <Box sx={{ textAlign: 'center', maxWidth: 360 }}>
            <Box sx={{ width: 60, height: 60, borderRadius: 3, bgcolor: 'rgba(255,255,255,0.12)', border: '2px solid rgba(255,255,255,0.2)', display: 'flex', alignItems: 'center', justifyContent: 'center', mx: 'auto', mb: 4 }}>
              <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1.6rem' }}>W</Typography>
            </Box>
            <Typography variant="h3" fontWeight={800} sx={{ color: '#fff', mb: 2, lineHeight: 1.2 }}>Start your<br />free account</Typography>
            <Typography sx={{ color: 'rgba(255,255,255,0.7)', lineHeight: 1.7 }}>
              Join hundreds of digital agencies using Webdezign to deliver world-class projects.
            </Typography>
            {['No credit card required', 'Unlimited projects', 'Role-based team access'].map((f) => (
              <Box key={f} sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 2, justifyContent: 'center' }}>
                <Box sx={{ width: 6, height: 6, borderRadius: '50%', bgcolor: '#0099C2' }} />
                <Typography sx={{ color: 'rgba(255,255,255,0.8)', fontSize: '0.875rem' }}>{f}</Typography>
              </Box>
            ))}
          </Box>
        </Grid>

        {/* Right form */}
        <Grid item xs={12} md={7} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', p: { xs: 3, sm: 5 } }}>
          <Box sx={{ width: '100%', maxWidth: 480 }}>
            <Typography variant="h4" fontWeight={700} sx={{ mb: 0.5, color: '#1A0A3C' }}>Create account</Typography>
            <Typography color="text.secondary" sx={{ mb: 3 }}>Fill in your details below to get started</Typography>

            {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>{error}</Alert>}

            <Box component="form" onSubmit={handleSubmit}>
              <Stack spacing={2.5}>
                <Field name="name"    label="Full Name"     icon={<Person fontSize="small" sx={{ color: 'text.secondary' }} />} />
                <Field name="email"   label="Email Address" type="email" icon={<Email fontSize="small" sx={{ color: 'text.secondary' }} />} />
                <Field name="company" label="Company Name"  icon={<Business fontSize="small" sx={{ color: 'text.secondary' }} />} />
                <Field name="password" label="Password" type={showPwd ? 'text' : 'password'}
                  icon={<Lock fontSize="small" sx={{ color: 'text.secondary' }} />}
                  end={
                    <InputAdornment position="end">
                      <IconButton size="small" onClick={() => setShowPwd((v) => !v)} edge="end">
                        {showPwd ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                      </IconButton>
                    </InputAdornment>
                  }
                />
                <Field name="confirm" label="Confirm Password" type={showPwd ? 'text' : 'password'}
                  icon={<Lock fontSize="small" sx={{ color: 'text.secondary' }} />}
                />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={loading}
                  endIcon={loading ? <CircularProgress size={16} color="inherit" /> : <ArrowForward fontSize="small" />}
                  sx={{ py: 1.4, fontSize: '1rem' }}>
                  {loading ? 'Creating account…' : 'Create Account'}
                </Button>
              </Stack>
            </Box>

            <Box sx={{ mt: 3, textAlign: 'center' }}>
              <Typography variant="body2" color="text.secondary">
                Already have an account?{' '}
                <Link component={RouterLink} to="/login" color="primary.main" fontWeight={600} underline="none">Sign in</Link>
              </Typography>
            </Box>
          </Box>
        </Grid>
      </Grid>
    </Box>
  );
}

import { useState } from 'react';
import { Link as RouterLink, useNavigate, useSearchParams } from 'react-router-dom';
import {
  Box, Typography, TextField, Button, Alert, CircularProgress,
  Link, Stack, InputAdornment, IconButton,
} from '@mui/material';
import { Lock, Visibility, VisibilityOff, CheckCircle, Email } from '@mui/icons-material';
import { useMutation } from '@apollo/client';
import { RESET_PASSWORD } from '../../graphql/mutations';

export default function ResetPassword() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  // Laravel sends ?token=...&email=... in the reset link
  const tokenFromUrl = searchParams.get('token') ?? '';
  const emailFromUrl = searchParams.get('email') ?? '';

  const [form, setForm] = useState({
    email: emailFromUrl,
    token: tokenFromUrl,
    password: '',
    confirm: '',
  });
  const [showPwd, setShowPwd] = useState(false);
  const [errors, setErrors] = useState({});
  const [done, setDone] = useState(false);
  const [apiError, setApiError] = useState('');
  const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

  const validate = () => {
    const e = {};
    if (!form.email) e.email = 'Email is required';
    if (!form.token) e.token = 'Reset token is missing — use the link from your email';
    if (!form.password) e.password = 'Password is required';
    else if (form.password.length < 6) e.password = 'Minimum 6 characters';
    if (form.password !== form.confirm) e.confirm = 'Passwords do not match';
    setErrors(e);
    return !Object.keys(e).length;
  };

  const [resetPassword, { loading }] = useMutation(RESET_PASSWORD, {
    onCompleted: () => {
      setDone(true);
      setTimeout(() => navigate('/login'), 2500);
    },
    onError: (e) => setApiError(e.message || 'Failed to reset password. The link may have expired.'),
  });

  const handleSubmit = (ev) => {
    ev.preventDefault();
    setApiError('');
    if (!validate()) return;
    resetPassword({
      variables: {
        email: form.email,
        token: form.token,
        password: form.password,
        password_confirmation: form.confirm,
      },
    });
  };

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#F7F5FF', display: 'flex', alignItems: 'center', justifyContent: 'center', p: 3 }}>
      <Box sx={{ width: '100%', maxWidth: 440 }}>
        <Box sx={{ textAlign: 'center', mb: 4 }}>
          <Box sx={{ width: 56, height: 56, borderRadius: 3, bgcolor: 'primary.main', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', mb: 2 }}>
            <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1.4rem' }}>W</Typography>
          </Box>
          <Typography variant="h4" fontWeight={700} sx={{ color: '#1A0A3C' }}>Reset password</Typography>
          <Typography color="text.secondary" sx={{ mt: 0.5 }}>Choose a strong new password</Typography>
        </Box>

        {done ? (
          <Box sx={{ textAlign: 'center', p: 3, bgcolor: '#F0FDF4', borderRadius: 3, border: '1px solid #BBF7D0' }}>
            <CheckCircle sx={{ fontSize: 48, color: '#16A34A', mb: 2 }} />
            <Typography fontWeight={600} color="#16A34A">Password reset successful!</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>Redirecting you to sign in…</Typography>
          </Box>
        ) : (
          <Box sx={{ bgcolor: '#fff', p: 4, borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: '0 4px 16px rgba(142,67,240,0.06)' }}>
            {apiError && <Alert severity="error" sx={{ mb: 2.5, borderRadius: 2 }}>{apiError}</Alert>}
            <Box component="form" onSubmit={handleSubmit}>
              <Stack spacing={2.5}>
                {/* Email — pre-filled from URL, editable if needed */}
                <TextField
                  label="Email Address" type="email" fullWidth
                  value={form.email} onChange={(e) => set('email', e.target.value)}
                  error={!!errors.email} helperText={errors.email}
                  InputProps={{ startAdornment: <InputAdornment position="start"><Email fontSize="small" sx={{ color: 'text.secondary' }} /></InputAdornment> }}
                />
                {/* Token — hidden field, error shown if missing */}
                {errors.token && <Alert severity="error" sx={{ borderRadius: 2 }}>{errors.token}</Alert>}

                <TextField
                  label="New Password" type={showPwd ? 'text' : 'password'} fullWidth
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
                <TextField
                  label="Confirm Password" type={showPwd ? 'text' : 'password'} fullWidth
                  value={form.confirm} onChange={(e) => set('confirm', e.target.value)}
                  error={!!errors.confirm} helperText={errors.confirm}
                  InputProps={{ startAdornment: <InputAdornment position="start"><Lock fontSize="small" sx={{ color: 'text.secondary' }} /></InputAdornment> }}
                />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={loading}
                  endIcon={loading && <CircularProgress size={16} color="inherit" />}
                  sx={{ py: 1.4, fontSize: '1rem' }}>
                  {loading ? 'Resetting…' : 'Reset Password'}
                </Button>
              </Stack>
            </Box>
          </Box>
        )}

        <Box sx={{ mt: 3, textAlign: 'center' }}>
          <Link component={RouterLink} to="/login" variant="body2" color="text.secondary" underline="hover">
            Back to Sign In
          </Link>
        </Box>
      </Box>
    </Box>
  );
}

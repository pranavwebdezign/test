import { useState } from 'react';
import { Link as RouterLink } from 'react-router-dom';
import {
  Box, Typography, TextField, Button, Alert, CircularProgress,
  Link, Stack, InputAdornment,
} from '@mui/material';
import { Email, ArrowBack, SendOutlined } from '@mui/icons-material';
import { useMutation } from '@apollo/client';
import { FORGOT_PASSWORD } from '../../graphql/mutations';

export default function ForgotPassword() {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [sent, setSent] = useState(false);

  const [sendReset, { loading }] = useMutation(FORGOT_PASSWORD, {
    onCompleted: () => setSent(true),
    onError: (e) => setError(e.message || 'Something went wrong. Please try again.'),
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!/\S+@\S+\.\S+/.test(email)) { setError('Enter a valid email address'); return; }
    setError('');
    sendReset({ variables: { email } });
  };

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#F7F5FF', display: 'flex', alignItems: 'center', justifyContent: 'center', p: 3 }}>
      <Box sx={{ width: '100%', maxWidth: 440 }}>
        {/* Logo */}
        <Box sx={{ textAlign: 'center', mb: 4 }}>
          <Box sx={{ width: 56, height: 56, borderRadius: 3, bgcolor: 'primary.main', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', mb: 2 }}>
            <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1.4rem' }}>W</Typography>
          </Box>
          <Typography variant="h4" fontWeight={700} sx={{ color: '#1A0A3C' }}>Forgot password?</Typography>
          <Typography color="text.secondary" sx={{ mt: 0.5 }}>
            Enter your email and we'll send you a reset link
          </Typography>
        </Box>

        {sent ? (
          <Box sx={{ textAlign: 'center', p: 3, bgcolor: '#F0FDF4', borderRadius: 3, border: '1px solid #BBF7D0' }}>
            <SendOutlined sx={{ fontSize: 48, color: '#16A34A', mb: 2 }} />
            <Typography fontWeight={600} color="#16A34A" gutterBottom>Check your email</Typography>
            <Typography variant="body2" color="text.secondary">
              If an account with <strong>{email}</strong> exists, we've sent a password reset link.
            </Typography>
            <Button component={RouterLink} to="/login" variant="contained" sx={{ mt: 3 }}>Back to Sign In</Button>
          </Box>
        ) : (
          <Box sx={{ bgcolor: '#fff', p: 4, borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: '0 4px 16px rgba(142,67,240,0.06)' }}>
            {error && <Alert severity="error" sx={{ mb: 2.5, borderRadius: 2 }}>{error}</Alert>}
            <Box component="form" onSubmit={handleSubmit}>
              <Stack spacing={3}>
                <TextField
                  label="Email Address" type="email" fullWidth
                  value={email} onChange={(e) => setEmail(e.target.value)}
                  InputProps={{ startAdornment: <InputAdornment position="start"><Email fontSize="small" sx={{ color: 'text.secondary' }} /></InputAdornment> }}
                />
                <Button type="submit" variant="contained" fullWidth size="large" disabled={loading}
                  endIcon={loading ? <CircularProgress size={16} color="inherit" /> : <SendOutlined fontSize="small" />}
                  sx={{ py: 1.4, fontSize: '1rem' }}>
                  {loading ? 'Sending…' : 'Send Reset Link'}
                </Button>
              </Stack>
            </Box>
          </Box>
        )}

        <Box sx={{ mt: 3, textAlign: 'center' }}>
          <Link component={RouterLink} to="/login" variant="body2" color="text.secondary" underline="hover"
            sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5 }}>
            <ArrowBack fontSize="small" /> Back to Sign In
          </Link>
        </Box>
      </Box>
    </Box>
  );
}

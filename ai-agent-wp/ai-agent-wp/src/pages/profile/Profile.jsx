import { useState, useEffect, useRef } from 'react';
import {
  Box, Grid, Button, Stack, Divider, Typography, TextField,
  Card, CardContent, CircularProgress, InputAdornment, IconButton,
} from '@mui/material';
import { Save, LockOutlined, Visibility, VisibilityOff } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import ProfileImageUpload from '../../components/profile/ProfileImageUpload';
import ProfileForm from '../../components/profile/ProfileForm';
import ProfileCard from '../../components/profile/ProfileCard';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_ME } from '../../graphql/queries';
import { UPDATE_PROFILE, UPDATE_PASSWORD } from '../../graphql/mutations';

const buildProfile = (user) => ({
  fullName: user?.name ?? '',
  email: user?.email ?? '',
  phone: user?.phone ?? '',
  company: user?.company ?? '',
  role: user?.role ?? 'Client',
  bio: user?.bio ?? '',
  address: '',
  city: '',
  country: '',
  zip: '',
  avatar: user?.avatar_url ?? null,
});

function validate(values) {
  const errors = {};
  if (!values.fullName?.trim()) errors.fullName = 'Full name is required';
  if (!values.email?.trim()) errors.email = 'Email is required';
  else if (!/\S+@\S+\.\S+/.test(values.email)) errors.email = 'Enter a valid email';
  return errors;
}

export default function Profile() {
  const { enqueueSnackbar } = useSnackbar();
  const { confirm } = useConfirmation();

  // ── Profile data ──────────────────────────────────────────
  const { data, loading: meLoading, refetch } = useQuery(GET_ME, { fetchPolicy: 'cache-and-network' });
  const [profile, setProfile] = useState(() => buildProfile(null));
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (data?.me) setProfile(buildProfile(data.me));
  }, [data]);

  const [updateProfile, { loading: savingProfile }] = useMutation(UPDATE_PROFILE, {
    onCompleted: (d) => {
      refetch();
      enqueueSnackbar('Profile saved successfully', { variant: 'success' });
    },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to save profile', { variant: 'error' }),
  });

  // ── Password ──────────────────────────────────────────────
  const [pwForm, setPwForm] = useState({ current: '', next: '', confirm: '' });
  const [showPw, setShowPw] = useState({ current: false, next: false, confirm: false });
  const togglePw = (k) => setShowPw((p) => ({ ...p, [k]: !p[k] }));
  const setPw = (k, v) => setPwForm((p) => ({ ...p, [k]: v }));

  const [updatePassword, { loading: savingPw }] = useMutation(UPDATE_PASSWORD, {
    onCompleted: () => {
      enqueueSnackbar('Password changed successfully', { variant: 'success' });
      setPwForm({ current: '', next: '', confirm: '' });
    },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to change password', { variant: 'error' }),
  });

  // ── Handlers ──────────────────────────────────────────────
  const handleChange = (field, value) => {
    setProfile((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
  };

  const handleSave = () => {
    const errs = validate(profile);
    if (Object.keys(errs).length) { setErrors(errs); return; }
    updateProfile({ variables: { name: profile.fullName, email: profile.email, phone: profile.phone || null } });
  };

  // ── Avatar upload via REST ────────────────────────────────
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const avatarFileRef = useRef(null); // store raw File from ProfileImageUpload

  // Called when ProfileImageUpload fires onChange(dataURL) from a new File
  // We intercept by patching the input's onChange via a wrapper
  const handleAvatarFile = async (file) => {
    if (!file) return;
    const formData = new FormData();
    formData.append('avatar', file);
    setUploadingAvatar(true);
    try {
      const token = localStorage.getItem('auth_token');
      const res = await fetch('/api/avatar', {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: formData,
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || 'Upload failed');
      setProfile((p) => ({ ...p, avatar: json.avatar_url }));
      refetch();
      enqueueSnackbar('Profile photo updated', { variant: 'success' });
    } catch (err) {
      enqueueSnackbar(err.message || 'Failed to upload avatar', { variant: 'error' });
    } finally {
      setUploadingAvatar(false);
    }
  };

  // Override ProfileImageUpload's internal processFile to also upload
  const handleAvatarChange = (dataUrl) => {
    // dataUrl is the preview; find the raw file from the last input event via a ref trick
    // We use a wrapping input change to get the File object
    setProfile((p) => ({ ...p, avatar: dataUrl })); // instant preview
  };


  const handleReset = async () => {
    const ok = await confirm({ title: 'Reset Profile', message: 'Unsaved changes will be lost.', confirmText: 'Reset', severity: 'warning' });
    if (ok && data?.me) { setProfile(buildProfile(data.me)); setErrors({}); }
  };

  const handleChangePassword = () => {
    if (!pwForm.current) return enqueueSnackbar('Enter your current password', { variant: 'warning' });
    if (pwForm.next.length < 8) return enqueueSnackbar('New password must be at least 8 characters', { variant: 'warning' });
    if (pwForm.next !== pwForm.confirm) return enqueueSnackbar('New passwords do not match', { variant: 'warning' });
    updatePassword({ variables: { current_password: pwForm.current, new_password: pwForm.next } });
  };

  const PwField = ({ field, label }) => (
    <TextField
      label={label} fullWidth size="small"
      type={showPw[field] ? 'text' : 'password'}
      value={pwForm[field]}
      onChange={(e) => setPw(field, e.target.value)}
      InputProps={{
        endAdornment: (
          <InputAdornment position="end">
            <IconButton onClick={() => togglePw(field)} edge="end" size="small">
              {showPw[field] ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
            </IconButton>
          </InputAdornment>
        )
      }}
    />
  );

  return (
    <Box>
      <PageHeader
        title="My Profile"
        subtitle="Manage your personal information and preferences"
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Profile', path: '/profile' }]}
        action={
          <Stack direction="row" spacing={1.5}>
            <Button variant="outlined" onClick={handleReset} sx={{ borderRadius: 2 }} disabled={meLoading}>Reset</Button>
            <Button
              variant="contained" startIcon={savingProfile ? <CircularProgress size={16} /> : <Save />}
              onClick={handleSave} sx={{ borderRadius: 2 }} disabled={savingProfile || meLoading}
            >
              Save Profile
            </Button>
          </Stack>
        }
      />

      <Grid container spacing={3}>
        {/* Left — avatar + card + danger */}
        <Grid item xs={12} md={4} lg={3}>
          <Stack spacing={3}>
            <FormCard title="Profile Photo" subtitle="Click or drag to change">
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 1 }}>
                <Box sx={{ position: 'relative' }}>
                  {/* Gradient ring */}
                  <Box sx={{
                    p: '3px', borderRadius: '50%',
                    background: 'linear-gradient(135deg,#6A1FCC,#8E43F0,#0099C2)',
                    display: 'inline-block',
                  }}>
                    <Box sx={{ borderRadius: '50%', bgcolor: '#fff', p: '2px' }}>
                      <ProfileImageUpload
                        value={profile.avatar}
                        onChange={handleAvatarChange}
                        onFileSelected={handleAvatarFile}
                        initials={profile.fullName?.split(' ').map((n) => n[0]).join('').slice(0, 2) || '??'}
                        size={118}
                      />
                    </Box>
                  </Box>
                  {uploadingAvatar && (
                    <Box sx={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: '50%', bgcolor: 'rgba(255,255,255,0.7)' }}>
                      <CircularProgress size={32} />
                    </Box>
                  )}
                </Box>
              </Box>
              <Box sx={{ textAlign: 'center', mt: 1 }}>
                <Typography variant="body2" fontWeight={700}>{profile.fullName || 'Your Name'}</Typography>
                <Typography variant="caption" color="text.secondary">{profile.role}</Typography>
              </Box>
            </FormCard>

            <ProfileCard profile={profile} />
          </Stack>
        </Grid>

        {/* Right — profile form + password */}
        <Grid item xs={12} md={8} lg={9}>
          <Stack spacing={3}>
            {/* Profile info */}
            <FormCard title="Profile Information" subtitle="Update your personal details — name, email and phone sync to the server">
              <ProfileForm values={profile} onChange={handleChange} errors={errors} />
              <Box sx={{ mt: 2, display: { md: 'none' } }}>
                <Button variant="contained" fullWidth startIcon={<Save />} onClick={handleSave} sx={{ borderRadius: 2, py: 1.3 }} disabled={savingProfile}>
                  Save Profile
                </Button>
              </Box>
            </FormCard>

            {/* Change password */}
            <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none' }}>
              <CardContent sx={{ p: 3 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
                  <LockOutlined sx={{ fontSize: 18, color: 'text.secondary' }} />
                  <Typography variant="h6" fontWeight={700}>Change Password</Typography>
                </Box>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 2.5 }}>
                  Enter your current password, then choose a new one (min. 8 characters).
                </Typography>
                <Grid container spacing={2}>
                  <Grid item xs={12} sm={4}><PwField field="current" label="Current Password" /></Grid>
                  <Grid item xs={12} sm={4}><PwField field="next" label="New Password" /></Grid>
                  <Grid item xs={12} sm={4}><PwField field="confirm" label="Confirm New Password" /></Grid>
                </Grid>
                <Box sx={{ mt: 2, display: 'flex', justifyContent: 'flex-end' }}>
                  <Button
                    variant="outlined"
                    startIcon={savingPw ? <CircularProgress size={16} /> : <LockOutlined />}
                    onClick={handleChangePassword}
                    disabled={!pwForm.current || !pwForm.next || !pwForm.confirm || savingPw}
                    sx={{ borderRadius: 2 }}
                  >
                    Update Password
                  </Button>
                </Box>
              </CardContent>
            </Card>
          </Stack>
        </Grid>
      </Grid>
    </Box>
  );
}

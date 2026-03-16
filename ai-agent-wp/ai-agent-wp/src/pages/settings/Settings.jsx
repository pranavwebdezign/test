import { useState, useEffect } from 'react';
import {
  Box, Grid, Button, TextField, Divider, Stack, Switch, FormControlLabel,
  Avatar, Typography, Alert, Select, MenuItem, FormControl, InputLabel,
  CircularProgress, Tabs, Tab, Paper, ToggleButtonGroup, ToggleButton,
} from '@mui/material';
import {
  Save, LockOutlined, Visibility, VisibilityOff,
  PersonOutlined, SecurityOutlined, NotificationsOutlined, BookmarkOutlined,
} from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import SavedSitesPanel from '../../components/saas/SavedSitesPanel';
import ApiIntegrationsSettings from '../../components/settings/ApiIntegrationsSettings';
import { useAuth } from '../../contexts/AuthContext';
import { useLayout } from '../../contexts/LayoutContext';
import { GET_ME } from '../../graphql/queries';
import { UPDATE_PROFILE, UPDATE_PASSWORD, UPDATE_PREFERENCES } from '../../graphql/mutations';

const TABS = [
  { label: 'Profile', icon: <PersonOutlined fontSize="small" /> },
  { label: 'Security', icon: <SecurityOutlined fontSize="small" /> },
  { label: 'Notifications', icon: <NotificationsOutlined fontSize="small" /> },
  { label: 'Saved Sites', icon: <BookmarkOutlined fontSize="small" /> },
  { label: 'API Integrations', icon: <SecurityOutlined fontSize="small" /> },
];

export default function Settings() {
  const { user } = useAuth();
  const { navLayout, toggleNav, collapsed, toggleSidebar } = useLayout();
  const { enqueueSnackbar } = useSnackbar();
  const [tab, setTab] = useState(0);

  const { data, loading: meLoading, refetch } = useQuery(GET_ME, { fetchPolicy: 'cache-and-network' });

  const [profile, setProfile] = useState({
    name: user?.name ?? '', email: user?.email ?? '',
    company: user?.company ?? '', phone: user?.phone ?? '', bio: user?.bio ?? '',
  });
  const [notif, setNotif] = useState({ email: true, push: false, taskUpdates: true, projectUpdates: true, weeklyReport: false });
  const [prefs, setPrefs] = useState({ timezone: 'UTC', language: 'English', theme: 'light' });
  const [pwForm, setPwForm] = useState({ current: '', next: '', confirm: '' });
  const [showPw, setShowPw] = useState({ current: false, next: false, confirm: false });

  useEffect(() => {
    if (data?.me) {
      const me = data.me;
      setProfile({ name: me.name ?? '', email: me.email ?? '', company: me.company ?? '', phone: me.phone ?? '', bio: me.bio ?? '' });
      if (me.user_prefs) {
        try {
          const saved = typeof me.user_prefs === 'string' ? JSON.parse(me.user_prefs) : me.user_prefs;
          setPrefs((p) => ({ ...p, timezone: saved.timezone ?? p.timezone, language: saved.language ?? p.language, theme: saved.theme ?? p.theme }));
          setNotif((n) => ({
            email: saved.email_notif ?? n.email, push: saved.push_notif ?? n.push,
            taskUpdates: saved.task_updates ?? n.taskUpdates,
            projectUpdates: saved.project_updates ?? n.projectUpdates,
            weeklyReport: saved.weekly_report ?? n.weeklyReport,
          }));
        } catch (_) { /* malformed JSON */ }
      }
    }
  }, [data]);

  const setP = (k, v) => setProfile((p) => ({ ...p, [k]: v }));
  const setN = (k, v) => setNotif((p) => ({ ...p, [k]: v }));
  const setPr = (k, v) => setPrefs((p) => ({ ...p, [k]: v }));
  const setPw = (k, v) => setPwForm((p) => ({ ...p, [k]: v }));
  const togglePw = (k) => setShowPw((p) => ({ ...p, [k]: !p[k] }));

  const [updateProfile, { loading: savingProfile }] = useMutation(UPDATE_PROFILE, {
    onCompleted: () => { refetch(); enqueueSnackbar('Profile saved', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to save', { variant: 'error' }),
  });
  const [updatePreferences, { loading: savingPrefs }] = useMutation(UPDATE_PREFERENCES, {
    onCompleted: () => enqueueSnackbar('Preferences saved', { variant: 'success' }),
    onError: (e) => enqueueSnackbar(e.message || 'Failed', { variant: 'error' }),
  });
  const [updatePassword, { loading: savingPw }] = useMutation(UPDATE_PASSWORD, {
    onCompleted: () => { enqueueSnackbar('Password updated', { variant: 'success' }); setPwForm({ current: '', next: '', confirm: '' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update password', { variant: 'error' }),
  });

  const handleSave = () => {
    if (!profile.name.trim()) { enqueueSnackbar('Name is required', { variant: 'warning' }); return; }
    updateProfile({ variables: { name: profile.name, email: profile.email, phone: profile.phone || null } });
    updatePreferences({ variables: { timezone: prefs.timezone, language: prefs.language, theme: prefs.theme, email_notif: notif.email, push_notif: notif.push, task_updates: notif.taskUpdates, project_updates: notif.projectUpdates, weekly_report: notif.weeklyReport } });
  };

  const handleChangePassword = () => {
    if (!pwForm.current) { enqueueSnackbar('Enter your current password', { variant: 'warning' }); return; }
    if (pwForm.next.length < 6) { enqueueSnackbar('New password must be at least 6 characters', { variant: 'warning' }); return; }
    if (pwForm.next !== pwForm.confirm) { enqueueSnackbar('Passwords do not match', { variant: 'warning' }); return; }
    updatePassword({ variables: { current_password: pwForm.current, new_password: pwForm.next } });
  };

  const PwField = ({ field, label }) => (
    <TextField
      label={label} type={showPw[field] ? 'text' : 'password'} fullWidth size="small"
      value={pwForm[field]} onChange={(e) => setPw(field, e.target.value)}
      sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
      InputProps={{
        endAdornment: (
          <Box component="span" sx={{ display: 'flex', alignItems: 'center', cursor: 'pointer', mr: 0.5 }} onClick={() => togglePw(field)}>
            {showPw[field] ? <VisibilityOff fontSize="small" color="disabled" /> : <Visibility fontSize="small" color="disabled" />}
          </Box>
        ),
      }}
    />
  );

  const avatarInitials = profile.name.split(' ').map((n) => n[0]).join('').slice(0, 2) || '??';

  return (
    <Box sx={{ pb: 12 }}>
      <PageHeader
        title="Settings"
        subtitle="Manage your account preferences"
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Settings', path: '/settings' }]}
      />

      {/* ── Tabs navigation ─────────────────────────────────── */}
      <Paper sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', mb: 3, overflow: 'hidden' }}>
        <Tabs
          value={tab} onChange={(_, v) => setTab(v)}
          variant="scrollable" scrollButtons="auto"
          sx={{
            borderBottom: '1px solid #EDE8FC',
            '& .MuiTab-root': { textTransform: 'none', fontWeight: 600, minHeight: 52, fontSize: '0.9rem', gap: 0.5 },
            '& .MuiTabs-indicator': { height: 3, borderRadius: '3px 3px 0 0', background: 'linear-gradient(90deg,#6A1FCC,#8E43F0)' },
            '& .Mui-selected': { color: 'primary.main' },
          }}
        >
          {TABS.map((t, i) => (
            <Tab key={i} label={t.label} icon={t.icon} iconPosition="start" />
          ))}
        </Tabs>
      </Paper>

      {/* ── Tab 0: Profile ───────────────────────────────────── */}
      {tab === 0 && (
        <Grid container spacing={3}>
          <Grid item xs={12} lg={8}>
            <FormCard title="Profile Information" subtitle="Update your personal details">
              <Grid container spacing={2.5}>
                {/* Avatar preview */}
                <Grid item xs={12} sx={{ display: 'flex', alignItems: 'center', gap: 2.5, pb: 1 }}>
                  <Box sx={{ p: '3px', borderRadius: '50%', background: 'linear-gradient(135deg,#6A1FCC,#8E43F0,#0099C2)', flexShrink: 0 }}>
                    <Avatar sx={{ width: 64, height: 64, bgcolor: '#8E43F0', fontSize: '1.2rem', fontWeight: 700, border: '3px solid #fff' }}>
                      {avatarInitials}
                    </Avatar>
                  </Box>
                  <Box>
                    <Typography variant="body1" fontWeight={700}>{profile.name || 'Your Name'}</Typography>
                    <Typography variant="caption" color="text.secondary">{user?.role} · {user?.email}</Typography>
                  </Box>
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField label="Full Name" fullWidth autoFocus value={profile.name} onChange={(e) => setP('name', e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField label="Email Address" type="email" fullWidth value={profile.email} onChange={(e) => setP('email', e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField label="Company" fullWidth value={profile.company} onChange={(e) => setP('company', e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField label="Phone Number" fullWidth value={profile.phone} onChange={(e) => setP('phone', e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                </Grid>
                <Grid item xs={12}>
                  <TextField label="Bio" fullWidth multiline rows={3} value={profile.bio} onChange={(e) => setP('bio', e.target.value)} sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }} />
                </Grid>
              </Grid>
            </FormCard>
          </Grid>

          <Grid item xs={12} lg={4}>
            <FormCard title="Preferences" subtitle="Localisation and appearance">
              <Stack spacing={2.5}>
                <FormControl fullWidth size="small">
                  <InputLabel>Timezone</InputLabel>
                  <Select value={prefs.timezone} label="Timezone" onChange={(e) => setPr('timezone', e.target.value)} sx={{ borderRadius: 2 }}>
                    {['UTC', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Asia/Tokyo'].map((tz) => (
                      <MenuItem key={tz} value={tz}>{tz}</MenuItem>
                    ))}
                  </Select>
                </FormControl>
                <FormControl fullWidth size="small">
                  <InputLabel>Language</InputLabel>
                  <Select value={prefs.language} label="Language" onChange={(e) => setPr('language', e.target.value)} sx={{ borderRadius: 2 }}>
                    {['English', 'French', 'German', 'Spanish'].map((l) => <MenuItem key={l} value={l}>{l}</MenuItem>)}
                  </Select>
                </FormControl>
              </Stack>

              {/* Navigation Layout preference */}
              <Divider sx={{ my: 2 }} />
              <Typography variant="body2" fontWeight={700} sx={{ mb: 1.5 }}>Navigation Layout</Typography>
              <Stack spacing={2}>
                <Box>
                  <Typography variant="caption" color="text.secondary" sx={{ mb: 1, display: 'block' }}>
                    Choose where the main navigation appears
                  </Typography>
                  <ToggleButtonGroup
                    value={navLayout} exclusive
                    onChange={(_, v) => v && toggleNav(v)}
                    size="small"
                    sx={{
                      '& .MuiToggleButton-root': { textTransform: 'none', fontWeight: 600, px: 2, borderColor: '#DDD4F8', fontSize: '0.82rem' },
                      '& .Mui-selected': { bgcolor: '#EDE8FC !important', color: '#8E43F0 !important', borderColor: '#C9B6F8 !important' },
                    }}
                  >
                    <ToggleButton value="left">Left Sidebar</ToggleButton>
                    <ToggleButton value="top">Top Bar</ToggleButton>
                  </ToggleButtonGroup>
                </Box>
                {navLayout === 'left' && (
                  <FormControlLabel
                    control={
                      <Switch
                        checked={collapsed}
                        onChange={toggleSidebar}
                        color="primary"
                        size="small"
                      />
                    }
                    label={
                      <Box>
                        <Typography variant="body2" fontWeight={600}>Compact sidebar</Typography>
                        <Typography variant="caption" color="text.secondary">Icon-only rail — saves horizontal space</Typography>
                      </Box>
                    }
                  />
                )}
                <Typography variant="caption" color="text.disabled" sx={{ fontStyle: 'italic' }}>
                  Preferences saved locally and take effect immediately.
                </Typography>
              </Stack>
            </FormCard>
          </Grid>
        </Grid>
      )}

      {/* ── Tab 1: Security ──────────────────────────────────── */}
      {tab === 1 && (
        <FormCard title="Change Password" subtitle="Choose a strong new password (minimum 6 characters)" icon={<SecurityOutlined />}>
          <Grid container spacing={2.5}>
            <Grid item xs={12} sm={4}><PwField field="current" label="Current Password" /></Grid>
            <Grid item xs={12} sm={4}><PwField field="next" label="New Password" /></Grid>
            <Grid item xs={12} sm={4}><PwField field="confirm" label="Confirm New Password" /></Grid>
          </Grid>
          <Box sx={{ mt: 2 }}>
            <Button
              variant="outlined" size="small"
              startIcon={savingPw ? <CircularProgress size={14} /> : <LockOutlined />}
              onClick={handleChangePassword}
              disabled={savingPw || !pwForm.current || !pwForm.next || !pwForm.confirm}
              sx={{ borderRadius: 2 }}
            >
              Update Password
            </Button>
          </Box>
        </FormCard>
      )}

      {/* ── Tab 2: Notifications ─────────────────────────────── */}
      {tab === 2 && (
        <FormCard title="Notification Preferences" subtitle="Choose what alerts you receive" icon={<NotificationsOutlined />} sx={{ maxWidth: 600 }}>
          <Stack spacing={0.5}>
            {[
              ['email', 'Email notifications', 'Receive updates by email'],
              ['push', 'Push notifications', 'Browser push alerts'],
              ['taskUpdates', 'Task updates', 'When tasks are assigned or updated'],
              ['projectUpdates', 'Project updates', 'When project status changes'],
              ['weeklyReport', 'Weekly summary report', 'A weekly digest every Monday'],
            ].map(([key, label, hint]) => (
              <Box key={key} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', py: 1.5, borderBottom: '1px solid #F5F0FF' }}>
                <Box>
                  <Typography variant="body2" fontWeight={600}>{label}</Typography>
                  <Typography variant="caption" color="text.secondary">{hint}</Typography>
                </Box>
                <Switch checked={notif[key]} onChange={(e) => setN(key, e.target.checked)} color="primary" size="small" />
              </Box>
            ))}
          </Stack>
        </FormCard>
      )}

      {/* ── Tab 3: Saved Sites ───────────────────────────────── */}
      {tab === 3 && (
        <FormCard title="Saved Sites" subtitle="Sites you've bookmarked for quick access" icon={<BookmarkOutlined />}>
          <SavedSitesPanel />
        </FormCard>
      )}

      {/* ── Tab 4: API Integrations ──────────────────────────── */}
      {tab === 4 && (
        <Box sx={{ maxWidth: 800 }}>
          <ApiIntegrationsSettings />
        </Box>
      )}

      {/* Sticky action bar — only for profile / notifications tabs */}
      {(tab === 0 || tab === 2) && (
        <Paper elevation={4} sx={{
          position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 1200,
          px: 3, py: 2, borderTop: '1px solid #DDD4F8',
          display: 'flex', justifyContent: 'flex-end', gap: 2,
          bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(8px)',
        }}>
          <Button
            variant="contained"
            startIcon={(savingProfile || savingPrefs) ? <CircularProgress size={16} color="inherit" /> : <Save />}
            onClick={handleSave}
            disabled={savingProfile || savingPrefs || meLoading}
            sx={{ borderRadius: 2, px: 3 }}
          >
            {(savingProfile || savingPrefs) ? 'Saving…' : 'Save Changes'}
          </Button>
        </Paper>
      )}
    </Box>
  );
}

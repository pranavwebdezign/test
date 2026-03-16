import { useState, useEffect } from 'react';
import {
  Box, Typography, Button, TextField, Switch,
  Select, MenuItem, FormControl, InputLabel, FormControlLabel,
  Card, CardContent, Divider, Stack, CircularProgress,
} from '@mui/material';
import { Save } from '@mui/icons-material';
import AppLayout from '../components/layout/AppLayout';
import AppSnackbar from '../components/AppSnackbar';
import { useNotification } from '../hooks/useNotification';
import ApiIntegrationsSettings from '../components/settings/ApiIntegrationsSettings';
import { useQuery, useMutation } from '@apollo/client';
import {
  PORTAL_SETTINGS_QUERY,
  UPDATE_PORTAL_SETTING,
  GET_WP_SITES,
} from '../graphql/queries';

// Helper: find a setting value from the portal settings array
const getSetting = (settings, key, fallback = '') =>
  settings.find((s) => s.key === key)?.value ?? fallback;

export default function Settings() {
  const { notify, snackbarProps } = useNotification();

  // ── Load portal settings from backend ──
  const { data: settingsData, loading: settingsLoading, refetch } = useQuery(PORTAL_SETTINGS_QUERY, {
    fetchPolicy: 'cache-and-network',
  });
  const portalSettings = settingsData?.portalSettings ?? [];

  // ── Load sites for AA stats ──
  const { data: sitesData } = useQuery(GET_WP_SITES, { fetchPolicy: 'cache-and-network' });
  const allSites = sitesData?.wpSites ?? [];
  const aaStats = {
    total: allSites.length,
    active: allSites.filter((s) => s.aa_plugin_active).length,
  };

  // ── Local form state ──
  const [settings, setSettings] = useState({
    default_check_schedule: 'daily',
    alert_p0_email: '',
    alert_p1_email: '',
    slack_webhook_url: '',
    checkHistoryDays: 90,
    findingsRetentionDays: 180,
    emailNotifications: true,
    slackNotifications: false,
    criticalAlerts: true,
    warningAlerts: true,
    timezone: 'UTC',
    checkInterval: 60,
    retryAttempts: 3,
  });

  // ── Pre-fill from loaded settings ──
  useEffect(() => {
    if (!portalSettings.length) return;
    setSettings((prev) => ({
      ...prev,
      default_check_schedule: getSetting(portalSettings, 'default_check_schedule', 'daily'),
      alert_p0_email: getSetting(portalSettings, 'alert_p0_email', ''),
      alert_p1_email: getSetting(portalSettings, 'alert_p1_email', ''),
      slack_webhook_url: getSetting(portalSettings, 'slack_webhook_url', ''),
    }));
  }, [portalSettings.length]); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Mutation ──
  const [updateSetting] = useMutation(UPDATE_PORTAL_SETTING, {
    onError: (e) => notify(e.message, 'error'),
  });

  const set = (key, value) => setSettings((prev) => ({ ...prev, [key]: value }));

  // Save all wired portal settings
  const handleSave = async () => {
    const keysToSave = [
      { key: 'default_check_schedule', value: settings.default_check_schedule },
      ...(settings.alert_p0_email ? [{ key: 'alert_p0_email', value: settings.alert_p0_email }] : []),
      ...(settings.alert_p1_email ? [{ key: 'alert_p1_email', value: settings.alert_p1_email }] : []),
      ...(settings.slack_webhook_url ? [{ key: 'slack_webhook_url', value: settings.slack_webhook_url }] : []),
    ];

    try {
      await Promise.all(keysToSave.map(({ key, value }) =>
        updateSetting({ variables: { key, value } })
      ));
      refetch();
      notify('Settings saved successfully', 'success');
    } catch {
      // individual errors reported by onError above
    }
  };

  const [saving, setSaving] = useState(false);
  const handleSaveWithLoading = async () => {
    setSaving(true);
    await handleSave();
    setSaving(false);
  };

  const sectionCard = (title, children) => (
    <Card variant="outlined" sx={{ mb: 3 }}>
      <CardContent>
        <Typography variant="subtitle1" fontWeight={600} gutterBottom>{title}</Typography>
        <Divider sx={{ mb: 2 }} />
        {children}
      </CardContent>
    </Card>
  );

  return (
    <AppLayout>
      <AppSnackbar {...snackbarProps} />
      <Box sx={{ p: { xs: 2, md: 3 }, maxWidth: 800 }}>
        <Box sx={{ mb: 3 }}>
          <Typography variant="h4" fontWeight={600}>Settings</Typography>
          <Typography variant="body2" color="text.secondary">Configure your WP Ops dashboard</Typography>
        </Box>

        {sectionCard('Check Schedule', (
          <Stack spacing={3}>
            <FormControl sx={{ maxWidth: 280 }} size="small">
              <InputLabel>Default Check Schedule</InputLabel>
              <Select
                value={settings.default_check_schedule}
                label="Default Check Schedule"
                onChange={(e) => set('default_check_schedule', e.target.value)}
              >
                <MenuItem value="hourly">Every Hour</MenuItem>
                <MenuItem value="every_6h">Every 6 Hours</MenuItem>
                <MenuItem value="daily">Daily</MenuItem>
                <MenuItem value="weekly">Weekly</MenuItem>
              </Select>
            </FormControl>
            <TextField
              label="Default Check Interval (minutes)"
              type="number"
              value={settings.checkInterval}
              onChange={(e) => set('checkInterval', Number(e.target.value))}
              helperText="How often to automatically run health checks"
              inputProps={{ min: 15, max: 1440 }}
              sx={{ maxWidth: 280 }}
              size="small"
            />
            <TextField
              label="Retry Attempts"
              type="number"
              value={settings.retryAttempts}
              onChange={(e) => set('retryAttempts', Number(e.target.value))}
              helperText="Number of retries before marking a check as failed"
              inputProps={{ min: 1, max: 10 }}
              sx={{ maxWidth: 280 }}
              size="small"
            />
            <FormControl sx={{ maxWidth: 320 }} size="small">
              <InputLabel>Timezone</InputLabel>
              <Select value={settings.timezone} label="Timezone" onChange={(e) => set('timezone', e.target.value)}>
                <MenuItem value="UTC">UTC</MenuItem>
                <MenuItem value="America/New_York">Eastern Time (US)</MenuItem>
                <MenuItem value="America/Los_Angeles">Pacific Time (US)</MenuItem>
                <MenuItem value="Europe/London">London</MenuItem>
                <MenuItem value="Europe/Paris">Paris</MenuItem>
                <MenuItem value="Asia/Tokyo">Tokyo</MenuItem>
              </Select>
            </FormControl>
          </Stack>
        ))}

        {sectionCard('Notifications', (
          <Stack spacing={3}>
            <FormControlLabel
              control={<Switch checked={settings.emailNotifications} onChange={(e) => set('emailNotifications', e.target.checked)} />}
              label="Email Notifications"
            />
            <TextField
              label="Critical Alert Email (P0)"
              placeholder="alerts@example.com"
              value={settings.alert_p0_email}
              onChange={(e) => set('alert_p0_email', e.target.value)}
              helperText="Receives notifications for critical (P0) findings"
              sx={{ maxWidth: 360 }}
              size="small"
            />
            <TextField
              label="Warning Alert Email (P1-P2)"
              placeholder="team@example.com"
              value={settings.alert_p1_email}
              onChange={(e) => set('alert_p1_email', e.target.value)}
              helperText="Receives notifications for warning findings"
              sx={{ maxWidth: 360 }}
              size="small"
            />
            <Divider />
            <FormControlLabel
              control={<Switch checked={settings.slackNotifications} onChange={(e) => set('slackNotifications', e.target.checked)} />}
              label="Slack Notifications"
            />
            <TextField
              label="Slack Webhook URL"
              placeholder="https://hooks.slack.com/services/..."
              value={settings.slack_webhook_url}
              onChange={(e) => set('slack_webhook_url', e.target.value)}
              helperText="Slack incoming webhook URL for alert delivery"
              fullWidth
              size="small"
            />
            <Divider />
            <Typography variant="body2" fontWeight={600}>Alert Levels</Typography>
            <FormControlLabel
              control={<Switch checked={settings.criticalAlerts} onChange={(e) => set('criticalAlerts', e.target.checked)} />}
              label="Critical Alerts (P0)"
            />
            <FormControlLabel
              control={<Switch checked={settings.warningAlerts} onChange={(e) => set('warningAlerts', e.target.checked)} />}
              label="Warning Alerts (P1-P2)"
            />
          </Stack>
        ))}

        {sectionCard('API Integrations', (
          <ApiIntegrationsSettings notify={notify} aaStats={aaStats} />
        ))}

        {sectionCard('Data Retention', (
          <Stack spacing={3}>
            <TextField
              label="Check History Retention (days)"
              type="number"
              value={settings.checkHistoryDays}
              onChange={(e) => set('checkHistoryDays', Number(e.target.value))}
              helperText="How long to keep check run history"
              inputProps={{ min: 7, max: 365 }}
              sx={{ maxWidth: 280 }}
              size="small"
            />
            <TextField
              label="Findings Retention (days)"
              type="number"
              value={settings.findingsRetentionDays}
              onChange={(e) => set('findingsRetentionDays', Number(e.target.value))}
              helperText="How long to keep resolved findings"
              inputProps={{ min: 30, max: 730 }}
              sx={{ maxWidth: 280 }}
              size="small"
            />
          </Stack>
        ))}

        <Stack direction="row" spacing={2}>
          <Button
            variant="contained"
            startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />}
            onClick={handleSaveWithLoading}
            disabled={saving || settingsLoading}
            sx={{ bgcolor: '#8E43F0' }}
          >
            {saving ? 'Saving…' : 'Save Settings'}
          </Button>
          <Button variant="outlined" onClick={() => refetch()}>Reset to Loaded</Button>
        </Stack>
      </Box>
    </AppLayout>
  );
}

import React, { useState } from 'react';
import {
  Box, Card, CardContent, Typography, TextField, Button,
  InputAdornment, IconButton, Chip, Stack, Alert, AlertTitle,
  CircularProgress, Divider, Fade, Link, Tooltip,
  LinearProgress,
} from '@mui/material';
import {
  Visibility, VisibilityOff, Check, Close, Key,
  Shield, Speed, Extension, Info, Launch, Refresh,
} from '@mui/icons-material';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation, useQuery } from '@apollo/client';
import { wordfenceKeySchema, lighthouseKeySchema } from '../../lib/validationSchemas';
import {
  PORTAL_SETTINGS_QUERY,
  UPDATE_PORTAL_SETTING,
  TEST_WORDFENCE_KEY,
  TEST_LIGHTHOUSE_KEY,
} from '../../graphql/queries';
import { useAuth } from '../../contexts/AuthContext';

// ─────────────────────────────────────────────────────────────────────────────
// API Key Section Card
// ─────────────────────────────────────────────────────────────────────────────

function ApiKeySection({
  title,
  description,
  settingKey,
  icon: Icon,
  iconColor,
  docsUrl,
  docsLabel,
  infoMessage,
  infoSeverity = 'info',
  placeholder,
  helperText,
  schema,
  fieldName,
  onTest,
  onSave,
  currentHint,
  isConfigured,
  updatedAt,
  testResult,
  testLoading,
  saveLoading,
}) {
  const [showKey, setShowKey] = useState(false);

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm({ resolver: zodResolver(schema) });

  const value = watch(fieldName, '');

  return (
    <Card
      sx={{
        mb: 3,
        border: '1px solid',
        borderColor: 'divider',
        borderRadius: 2,
        overflow: 'visible',
      }}
    >
      <CardContent sx={{ p: 3 }}>
        {/* Header */}
        <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 2, mb: 2 }}>
          <Box
            sx={{
              width: 44, height: 44, borderRadius: 2,
              background: `linear-gradient(135deg, ${iconColor}22, ${iconColor}44)`,
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              flexShrink: 0,
            }}
          >
            <Icon sx={{ color: iconColor, fontSize: 22 }} />
          </Box>
          <Box sx={{ flexGrow: 1 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, flexWrap: 'wrap' }}>
              <Typography variant="h6" fontWeight={600} fontSize={16}>
                {title}
              </Typography>
              {isConfigured ? (
                <Chip
                  icon={<Check sx={{ fontSize: '14px !important' }} />}
                  label="Configured"
                  color="success"
                  size="small"
                  sx={{ height: 22, fontSize: 11 }}
                />
              ) : (
                <Chip label="Not configured" variant="outlined" size="small" sx={{ height: 22, fontSize: 11 }} />
              )}
              {isConfigured && currentHint && (
                <Typography variant="body2" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: 12 }}>
                  ••••••{currentHint}
                </Typography>
              )}
            </Box>
            {isConfigured && updatedAt && (
              <Typography variant="caption" color="text.disabled">
                Last updated: {new Date(updatedAt).toLocaleDateString()}
              </Typography>
            )}
          </Box>
        </Box>

        <Typography variant="body2" color="text.secondary" sx={{ mb: 2.5 }}>
          {description}
        </Typography>

        {/* Info / Warning Alert */}
        {(!isConfigured || infoSeverity !== 'warning') && (
          <Alert severity={infoSeverity} sx={{ mb: 2.5, fontSize: 13 }}>
            {infoMessage}
            {docsUrl && (
              <Box sx={{ mt: 0.5 }}>
                <Link href={docsUrl} target="_blank" rel="noopener" sx={{ fontSize: 12 }}>
                  {docsLabel} <Launch sx={{ fontSize: 11, verticalAlign: 'middle' }} />
                </Link>
              </Box>
            )}
          </Alert>
        )}

        {/* Key Input + Actions */}
        <Box component="form" onSubmit={handleSubmit(onSave)}>
          <TextField
            fullWidth
            label={`${title} Key`}
            placeholder={placeholder}
            type={showKey ? 'text' : 'password'}
            size="small"
            helperText={errors[fieldName]?.message || helperText}
            error={!!errors[fieldName]}
            sx={{ mb: 2 }}
            InputProps={{
              endAdornment: (
                <InputAdornment position="end">
                  <IconButton size="small" onClick={() => setShowKey(!showKey)} edge="end">
                    {showKey ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                  </IconButton>
                </InputAdornment>
              ),
            }}
            {...register(fieldName)}
          />

          {/* Test Result */}
          {testResult && (
            <Fade in>
              <Alert
                severity={testResult.success ? 'success' : 'error'}
                iconMapping={{ success: <Check fontSize="inherit" />, error: <Close fontSize="inherit" /> }}
                sx={{ mb: 2, py: 0.5 }}
              >
                {testResult.message}
              </Alert>
            </Fade>
          )}

          <Stack direction="row" spacing={1.5} flexWrap="wrap" useFlexGap>
            <Button
              variant="outlined"
              size="small"
              startIcon={testLoading ? <CircularProgress size={14} /> : <Refresh />}
              disabled={testLoading || !value}
              onClick={() => onTest(value)}
            >
              {testLoading ? 'Testing…' : 'Test Connection'}
            </Button>
            <Button
              type="submit"
              variant="contained"
              size="small"
              startIcon={saveLoading ? <CircularProgress size={14} color="inherit" /> : <Key />}
              disabled={saveLoading || !value}
            >
              {saveLoading ? 'Saving…' : 'Save Key'}
            </Button>
            {isConfigured && (
              <Button
                variant="text"
                color="error"
                size="small"
                onClick={() => onSave({ [fieldName]: '' })}
              >
                Remove Key
              </Button>
            )}
          </Stack>
        </Box>
      </CardContent>
    </Card>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Active Auditor Info Section
// ─────────────────────────────────────────────────────────────────────────────

function ActiveAuditorSection({ aaStats }) {
  return (
    <Card sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 2 }}>
      <CardContent sx={{ p: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mb: 2 }}>
          <Box
            sx={{
              width: 44, height: 44, borderRadius: 2,
              background: 'linear-gradient(135deg, #7c3aed22, #7c3aed44)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}
          >
            <Extension sx={{ color: '#7c3aed', fontSize: 22 }} />
          </Box>
          <Typography variant="h6" fontWeight={600} fontSize={16}>
            Active Auditor WordPress Plugin
          </Typography>
        </Box>

        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          The Active Auditor plugin must be installed on each WordPress site to enable detailed health
          monitoring — updates, security audits, SEO analysis, Lighthouse scores, and Google Services detection.
        </Typography>

        {/* Stats */}
        {aaStats && (
          <Box sx={{ mb: 2.5 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
              <Typography variant="body2" color="text.secondary">
                Sites with plugin installed
              </Typography>
              <Typography variant="body2" fontWeight={600}>
                {aaStats.active} / {aaStats.total}
              </Typography>
            </Box>
            <LinearProgress
              variant="determinate"
              value={aaStats.total > 0 ? (aaStats.active / aaStats.total) * 100 : 0}
              sx={{ borderRadius: 1, height: 6 }}
              color={aaStats.active === aaStats.total ? 'success' : 'warning'}
            />
          </Box>
        )}

        <Alert severity="info" sx={{ mb: 2 }}>
          <AlertTitle sx={{ fontSize: 13, fontWeight: 600 }}>Installation steps</AlertTitle>
          <Box component="ol" sx={{ m: 0, pl: 2, fontSize: 13, lineHeight: 1.8 }}>
            <li>Download the Active Auditor plugin ZIP from Webdezign Portal</li>
            <li>In WP Admin → Plugins → Add New → Upload Plugin</li>
            <li>Activate the plugin, then go to Active Auditor → Settings to copy your API token</li>
            <li>Paste the token in the &quot;Plugin Token&quot; field on each site&apos;s detail page</li>
          </Box>
        </Alert>

        <Typography variant="caption" color="text.secondary">
          Plugin slug: <code>active-auditor</code> · Namespace: <code>active-auditor/v1</code>
        </Typography>
      </CardContent>
    </Card>
  );
}

// ─────────────────────────────────────────────────────────────────────────────
// Main Component
// ─────────────────────────────────────────────────────────────────────────────

export default function ApiIntegrationsSettings({ notify, aaStats = null }) {
  const { user } = useAuth();
  const [wfTestResult, setWfTestResult] = useState(null);
  const [lhTestResult, setLhTestResult] = useState(null);
  const [wfTestLoading, setWfTestLoading] = useState(false);
  const [lhTestLoading, setLhTestLoading] = useState(false);
  const [wfSaveLoading, setWfSaveLoading] = useState(false);
  const [lhSaveLoading, setLhSaveLoading] = useState(false);

  const { data: settingsData, refetch } = useQuery(PORTAL_SETTINGS_QUERY, {
    skip: user?.role !== 'SuperAdmin',
  });

  const [updateSetting] = useMutation(UPDATE_PORTAL_SETTING);
  const [testWordfence] = useMutation(TEST_WORDFENCE_KEY);
  const [testLighthouse] = useMutation(TEST_LIGHTHOUSE_KEY);

  if (user?.role !== 'SuperAdmin') {
    return (
      <Alert severity="warning">
        Only SuperAdmin users can manage API integrations.
      </Alert>
    );
  }

  const settings = settingsData?.portalSettings ?? [];
  const wfSetting = settings.find((s) => s.key === 'wordfence_api_key');
  const lhSetting = settings.find((s) => s.key === 'lighthouse_api_key');

  const handleSave = async (settingKey, value, setLoading) => {
    setLoading(true);
    try {
      await updateSetting({ variables: { key: settingKey, value } });
      notify('API key saved successfully.', 'success');
      refetch();
    } catch (err) {
      notify(err.message || 'Failed to save. Please try again.', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleTestWordfence = async (apiKey) => {
    setWfTestLoading(true);
    setWfTestResult(null);
    try {
      const { data } = await testWordfence({ variables: { api_key: apiKey } });
      setWfTestResult(JSON.parse(data.testWordfenceKey));
    } catch (err) {
      setWfTestResult({ success: false, message: err.message || 'Test failed.' });
    } finally {
      setWfTestLoading(false);
    }
  };

  const handleTestLighthouse = async (apiKey) => {
    setLhTestLoading(true);
    setLhTestResult(null);
    try {
      const { data } = await testLighthouse({ variables: { api_key: apiKey } });
      setLhTestResult(JSON.parse(data.testLighthouseKey));
    } catch (err) {
      setLhTestResult({ success: false, message: err.message || 'Test failed.' });
    } finally {
      setLhTestLoading(false);
    }
  };

  return (
    <Box>
      <Typography variant="h6" fontWeight={600} sx={{ mb: 0.5 }}>
        API Integrations
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Portal-wide API keys — one key covers all client WordPress sites.
      </Typography>

      {/* Wordfence Section */}
      <ApiKeySection
        title="Wordfence Intelligence API"
        description="Real-time CVE vulnerability scanning across all WordPress sites using the Wordfence database. One key covers all client sites."
        settingKey="wordfence_api_key"
        icon={Shield}
        iconColor="#ef4444"
        placeholder="Enter your Wordfence Intelligence API key"
        helperText='Get a free API key at wordfence.com/intelligence/api'
        docsUrl="https://www.wordfence.com/intelligence/api"
        docsLabel="View Wordfence API docs"
        infoMessage="Without this key, vulnerability detection uses Wordfence's public database only. With an API key you get real-time CVE data for all installed plugins and themes."
        infoSeverity={wfSetting?.is_configured ? 'success' : 'info'}
        schema={wordfenceKeySchema}
        fieldName="api_key"
        onTest={handleTestWordfence}
        onSave={(values) => handleSave('wordfence_api_key', values.api_key, setWfSaveLoading)}
        currentHint={wfSetting?.hint}
        isConfigured={wfSetting?.is_configured}
        updatedAt={wfSetting?.updated_at}
        testResult={wfTestResult}
        testLoading={wfTestLoading}
        saveLoading={wfSaveLoading}
      />

      {/* Lighthouse / PageSpeed Section */}
      <ApiKeySection
        title="Google PageSpeed Insights API"
        description="Powers Lighthouse performance, accessibility, SEO, and best practices scoring for all monitored WordPress sites."
        settingKey="lighthouse_api_key"
        icon={Speed}
        iconColor="#f59e0b"
        placeholder="Enter your Google PageSpeed Insights API key"
        helperText="Enable PageSpeed Insights API in Google Cloud Console"
        docsUrl="https://console.cloud.google.com/apis/library/pagespeedonline.googleapis.com"
        docsLabel="Enable PageSpeed Insights API in Google Cloud Console"
        infoMessage="Without this key, Lighthouse scores show cached sample data only. Add your Google API key to get real-time performance scores."
        infoSeverity={lhSetting?.is_configured ? 'success' : 'warning'}
        schema={lighthouseKeySchema}
        fieldName="api_key"
        onTest={handleTestLighthouse}
        onSave={(values) => handleSave('lighthouse_api_key', values.api_key, setLhSaveLoading)}
        currentHint={lhSetting?.hint}
        isConfigured={lhSetting?.is_configured}
        updatedAt={lhSetting?.updated_at}
        testResult={lhTestResult}
        testLoading={lhTestLoading}
        saveLoading={lhSaveLoading}
      />

      <Divider sx={{ my: 3 }} />

      {/* Active Auditor Section */}
      <ActiveAuditorSection aaStats={aaStats} />
    </Box>
  );
}

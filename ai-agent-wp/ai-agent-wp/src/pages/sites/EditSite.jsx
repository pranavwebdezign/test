import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import {
    Box, Grid, Typography, Button, Stack, TextField,
    MenuItem, Select, FormControl, InputLabel, InputAdornment, IconButton,
    Skeleton, Alert, Divider, CircularProgress, Accordion, AccordionSummary,
    AccordionDetails, Chip,
} from '@mui/material';
import {
    ArrowBack, Save, Visibility, VisibilityOff, Language, Security as SecurityIcon,
    Settings as SettingsIcon, Description, Key, VerifiedUser, LinkOff, Extension,
    ExpandMore,
} from '@mui/icons-material';
import AppLayout from '../../components/layout/AppLayout';
import { useNotification } from '../../hooks/useNotification';
import AppSnackbar from '../../components/AppSnackbar';
import {
    GET_WP_SITE, GET_WP_CLIENTS, UPDATE_WP_SITE,
    UPDATE_WP_SITE_AUTH, VERIFY_WP_SITE_TOKEN, REMOVE_WP_SITE_AUTH,
    UPDATE_WP_SITE_AA_TOKEN,
} from '../../graphql/queries';

// ── Constants ──────────────────────────────────────────────────────────────────
const HOSTING_OPTIONS = [
    { value: 'shared', label: 'Shared Hosting' },
    { value: 'vps', label: 'VPS' },
    { value: 'cloud', label: 'Cloud' },
    { value: 'dedicated', label: 'Dedicated Server' },
    { value: 'managed-wp', label: 'Managed WordPress' },
    { value: 'other', label: 'Other' },
];

const PRIORITY_OPTIONS = [
    { value: 'low', label: 'Low' },
    { value: 'normal', label: 'Normal' },
    { value: 'high', label: 'High' },
    { value: 'critical', label: 'Critical' },
];

const SCHEDULE_OPTIONS = [
    { value: 'hourly', label: 'Every Hour' },
    { value: '6h', label: 'Every 6 Hours' },
    { value: '12h', label: 'Every 12 Hours' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'manual', label: 'Manual Only' },
];

const AUTH_METHOD_OPTIONS = [
    { value: 'none', label: 'None' },
    { value: 'app_password', label: 'Application Password' },
    { value: 'jwt', label: 'JWT' },
    { value: 'basic', label: 'HTTP Basic' },
];

import SectionCard from '../../components/sites/SectionCard';
import SelectField from '../../components/sites/SelectField';

// ── URL sanitizer: strips trailing slash + collapses doubled origins ───────────
// e.g. "https://example.com/https://example.com" → "https://example.com"
// e.g. "https://example.com/" → "https://example.com"
const sanitizeUrl = (raw = '') => {
    const trimmed = raw.trim().replace(/\/$/, '');
    // Detect doubled origin: https://host/https://host → https://host
    const doubled = trimmed.match(/^(https?:\/\/[^/]+)\/\1(.*)$/);
    if (doubled) return doubled[1] + (doubled[2] || '');
    return trimmed;
};

// ── Main Page ──────────────────────────────────────────────────────────────────
export default function EditSite() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { notify, snackbar, closeSnackbar } = useNotification();

    // ── Load existing site data ──
    const { data: siteData, loading: siteLoading } = useQuery(GET_WP_SITE, {
        variables: { id },
        skip: !id,
    });

    // ── Load WP clients for dropdown ──
    const { data: clientsData } = useQuery(GET_WP_CLIENTS);
    const wpClients = clientsData?.wpClients ?? [];

    // ── Form state ──
    const [form, setForm] = useState({
        name: '',
        url: '',
        notes: '',
        hosting_environment: 'shared',
        priority: 'normal',
        check_schedule: 'daily',
        wp_client_id: '',
    });
    const [showToken, setShowToken] = useState(false);
    const [isDirty, setIsDirty] = useState(false);

    // ── Separate auth state (not in main form) ──
    const [authMethod, setAuthMethod]   = useState('app_password');
    const [authUsername, setAuthUsername] = useState('');
    const [authToken, setAuthToken]     = useState('');
    const [showAuthToken, setShowAuthToken] = useState(false);
    const [aaToken, setAaToken]         = useState('');
    const [showAaToken, setShowAaToken] = useState(false);

    // ── Pre-fill from loaded site ──
    useEffect(() => {
        const s = siteData?.wpSite;
        if (!s) return;
        setForm({
            name: s.name ?? '',
            url: sanitizeUrl(s.url ?? ''),   // defensive: strip trailing slash + de-duplicate
            notes: s.notes ?? '',
            hosting_environment: s.hosting_environment ?? 'shared',
            priority: s.priority ?? 'normal',
            check_schedule: s.check_schedule ?? 'daily',
            wp_client_id: s.wp_client?.id ?? '',
        });
        setAuthMethod(s.auth_method ?? 'app_password');
        setAuthUsername(s.auth_username ?? '');
        // Never pre-fill tokens
    }, [siteData]);

    const update = (field) => (value) => {
        setForm((prev) => ({ ...prev, [field]: value }));
        setIsDirty(true);
    };

    const handleTextField = (field) => (e) => update(field)(e.target.value);

    // ── Separate auth mutations ──
    const [saveAuth, { loading: savingAuth }] = useMutation(UPDATE_WP_SITE_AUTH, {
        onCompleted: () => { setAuthToken(''); notify('Auth credentials saved.', 'success'); },
        onError: (e) => notify(e.message, 'error'),
        refetchQueries: [{ query: GET_WP_SITE, variables: { id } }],
    });

    const [verifyAuth, { loading: verifyingAuth }] = useMutation(VERIFY_WP_SITE_TOKEN, {
        onCompleted: () => notify('Credentials verified — administrator confirmed.', 'success'),
        onError: (e) => notify(e.message, 'error'),
        refetchQueries: [{ query: GET_WP_SITE, variables: { id } }],
    });

    const [removeAuth, { loading: removingAuth }] = useMutation(REMOVE_WP_SITE_AUTH, {
        onCompleted: () => { setAuthToken(''); setAuthUsername(''); notify('Auth credentials removed.', 'info'); },
        onError: (e) => notify(e.message, 'error'),
        refetchQueries: [{ query: GET_WP_SITE, variables: { id } }],
    });

    const [saveAaToken, { loading: savingAa }] = useMutation(UPDATE_WP_SITE_AA_TOKEN, {
        onCompleted: () => { setAaToken(''); notify('AA plugin token saved and verified.', 'success'); },
        onError: (e) => notify(e.message, 'error'),
        refetchQueries: [{ query: GET_WP_SITE, variables: { id } }],
    });

    // ── Main mutation (site info only — no auth fields) ──
    const [updateSite, { loading: saving }] = useMutation(UPDATE_WP_SITE, {
        onCompleted: () => {
            notify('Site updated successfully.', 'success');
            setTimeout(() => navigate(`/sites/${id}`), 800);
        },
        onError: (e) => notify(e.message, 'error'),
        refetchQueries: [{ query: GET_WP_SITE, variables: { id } }],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        // Sanitize URL on save: strip trailing slash + de-duplicate doubled origins
        const cleanForm = { ...form, url: sanitizeUrl(form.url) };
        // Auth fields excluded — handled by separate mutation
        updateSite({ variables: { id, ...cleanForm } });
    };

    const site = siteData?.wpSite;

    return (
        <AppLayout>
            <AppSnackbar {...snackbar} onClose={closeSnackbar} />
            <Box
                component="form"
                onSubmit={handleSubmit}
                sx={{ maxWidth: 900, mx: 'auto', px: { xs: 2, md: 3 }, py: 3 }}
            >
                {/* ── Header ── */}
                <Box sx={{ mb: 3 }}>
                    <Button
                        startIcon={<ArrowBack />}
                        onClick={() => navigate(-1)}
                        size="small"
                        sx={{ color: 'text.secondary', mb: 1.5, '&:hover': { bgcolor: '#EDE8FC' } }}
                    >
                        {site ? site.name : 'Back'}
                    </Button>
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2 }}>
                        <Box>
                            <Typography variant="h5" fontWeight={800} color="#1A0A3C">Edit Site</Typography>
                            {site && (
                                <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
                                    {site.url}
                                </Typography>
                            )}
                        </Box>
                        <Stack direction="row" spacing={1.5}>
                            <Button
                                variant="outlined"
                                onClick={() => navigate(-1)}
                                sx={{ borderRadius: 2, fontWeight: 600 }}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                disabled={saving || !isDirty}
                                startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />}
                                sx={{ borderRadius: 2, fontWeight: 600, bgcolor: '#8E43F0' }}
                            >
                                {saving ? 'Saving…' : 'Save Changes'}
                            </Button>
                        </Stack>
                    </Box>
                </Box>

                {siteLoading ? (
                    <Stack spacing={2.5}>
                        <Skeleton variant="rounded" height={220} />
                        <Skeleton variant="rounded" height={180} />
                        <Skeleton variant="rounded" height={200} />
                    </Stack>
                ) : (
                    <Stack spacing={2.5}>
                        {/* ── Basic Info ── */}
                        <SectionCard icon={Language} title="Site Information">
                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Site Name"
                                        required
                                        fullWidth
                                        size="small"
                                        value={form.name}
                                        onChange={handleTextField('name')}
                                        helperText="Display name shown in the portal"
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Site URL"
                                        required
                                        fullWidth
                                        size="small"
                                        value={form.url}
                                        onChange={handleTextField('url')}
                                        placeholder="https://example.com"
                                        helperText="Must include https://"
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <SelectField
                                        label="WP Client"
                                        value={form.wp_client_id}
                                        onChange={update('wp_client_id')}
                                        options={[
                                            { value: '', label: '— No client —' },
                                            ...wpClients.map((c) => ({ value: c.id, label: c.name })),
                                        ]}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <SelectField
                                        label="Hosting Environment"
                                        value={form.hosting_environment}
                                        onChange={update('hosting_environment')}
                                        options={HOSTING_OPTIONS}
                                    />
                                </Grid>
                                <Grid item xs={12}>
                                    <TextField
                                        label="Notes"
                                        fullWidth
                                        multiline
                                        rows={3}
                                        size="small"
                                        value={form.notes}
                                        onChange={handleTextField('notes')}
                                        placeholder="Internal notes about this site…"
                                    />
                                </Grid>
                            </Grid>
                        </SectionCard>

                        {/* ── Monitoring Settings ── */}
                        <SectionCard icon={SettingsIcon} title="Monitoring Settings">
                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <SelectField
                                        label="Check Schedule"
                                        value={form.check_schedule}
                                        onChange={update('check_schedule')}
                                        options={SCHEDULE_OPTIONS}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <SelectField
                                        label="Priority"
                                        value={form.priority}
                                        onChange={update('priority')}
                                        options={PRIORITY_OPTIONS}
                                    />
                                </Grid>
                            </Grid>
                        </SectionCard>

                        {/* ── Authentication ── */}
                        <SectionCard icon={SecurityIcon} title="Site Authentication">
                            <Alert severity="info" sx={{ mb: 2, fontSize: '0.82rem' }}>
                                Connected credentials allow the portal to fetch live data from your WordPress site.
                                The Active Auditor plugin provides <strong>full access</strong>; WP Admin credentials provide basic access.
                            </Alert>

                            {/* WP Admin Credentials */}
                            <Typography variant="body2" fontWeight={700} sx={{ mb: 1.5, color: '#1A0A3C' }}>WP Admin Credentials</Typography>

                            {site?.is_authenticated && (
                                <Box sx={{ p: 1.5, mb: 2, bgcolor: site?.token_verified_at ? '#F0FDF4' : '#FFF7ED', borderRadius: 2, border: `1px solid ${site?.token_verified_at ? '#BBF7D0' : '#FED7AA'}`, display: 'flex', alignItems: 'center', gap: 1 }}>
                                    {site?.token_verified_at
                                        ? <VerifiedUser sx={{ fontSize: 16, color: '#16A34A', flexShrink: 0 }} />
                                        : <SecurityIcon sx={{ fontSize: 16, color: '#D97706', flexShrink: 0 }} />
                                    }
                                    <Box>
                                        <Typography variant="caption" fontWeight={700} color={site?.token_verified_at ? 'success.main' : 'warning.main'} display="block">
                                            {site?.token_verified_at ? 'Verified as administrator' : 'Unverified'}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', fontSize: '0.72rem' }}>
                                            {site?.auth_username} · ••••{site?.auth_token_hint}
                                        </Typography>
                                    </Box>
                                </Box>
                            )}

                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={4}>
                                    <FormControl fullWidth size="small">
                                        <InputLabel>Auth Method</InputLabel>
                                        <Select value={authMethod} label="Auth Method" onChange={(e) => setAuthMethod(e.target.value)}>
                                            <MenuItem value="app_password">App Password</MenuItem>
                                            <MenuItem value="jwt">JWT</MenuItem>
                                            <MenuItem value="basic">HTTP Basic</MenuItem>
                                        </Select>
                                    </FormControl>
                                </Grid>
                                <Grid item xs={12} sm={4}>
                                    <TextField
                                        label="WP Username"
                                        fullWidth size="small"
                                        value={authUsername}
                                        onChange={(e) => setAuthUsername(e.target.value)}
                                        placeholder="admin"
                                    />
                                </Grid>
                                <Grid item xs={12} sm={4}>
                                    <TextField
                                        label={authMethod === 'app_password' ? 'App Password' : 'Token'}
                                        fullWidth size="small"
                                        type={showAuthToken ? 'text' : 'password'}
                                        value={authToken}
                                        onChange={(e) => setAuthToken(e.target.value)}
                                        placeholder={site?.is_authenticated ? 'Leave blank to keep' : 'xxxx xxxx xxxx xxxx'}
                                        InputProps={{
                                            endAdornment: (
                                                <InputAdornment position="end">
                                                    <IconButton size="small" onClick={() => setShowAuthToken(!showAuthToken)} edge="end">
                                                        {showAuthToken ? <VisibilityOff sx={{ fontSize: 16 }} /> : <Visibility sx={{ fontSize: 16 }} />}
                                                    </IconButton>
                                                </InputAdornment>
                                            ),
                                        }}
                                    />
                                </Grid>
                            </Grid>

                            <Stack direction="row" spacing={1.5} sx={{ mt: 2, flexWrap: 'wrap', gap: 1 }}>
                                <Button
                                    variant="contained" size="small"
                                    disabled={!authUsername.trim() || !authToken.trim() || savingAuth}
                                    startIcon={savingAuth ? <CircularProgress size={13} color="inherit" /> : <Key sx={{ fontSize: 14 }} />}
                                    onClick={() => saveAuth({ variables: { id, auth_method: authMethod, auth_username: authUsername, auth_token: authToken } })}
                                    sx={{ borderRadius: 2, fontWeight: 600 }}
                                >
                                    {savingAuth ? 'Saving…' : 'Save Auth'}
                                </Button>
                                {site?.is_authenticated && (
                                    <Button
                                        variant="outlined" size="small"
                                        disabled={verifyingAuth}
                                        startIcon={verifyingAuth ? <CircularProgress size={13} color="inherit" /> : <VerifiedUser sx={{ fontSize: 14 }} />}
                                        onClick={() => verifyAuth({ variables: { id } })}
                                        sx={{ borderRadius: 2, fontWeight: 600 }}
                                    >
                                        {verifyingAuth ? 'Verifying…' : 'Verify'}
                                    </Button>
                                )}
                                {site?.is_authenticated && (
                                    <Button
                                        variant="outlined" color="error" size="small"
                                        disabled={removingAuth}
                                        startIcon={<LinkOff sx={{ fontSize: 14 }} />}
                                        onClick={() => removeAuth({ variables: { id } })}
                                        sx={{ borderRadius: 2, fontWeight: 600 }}
                                    >
                                        Remove
                                    </Button>
                                )}
                            </Stack>

                            {/* AA Token Accordion */}
                            <Accordion elevation={0} sx={{ mt: 2.5, border: '1px solid #DDD4F8', borderRadius: '8px !important', '&:before': { display: 'none' } }}>
                                <AccordionSummary expandIcon={<ExpandMore />} sx={{ minHeight: 44, '& .MuiAccordionSummary-content': { my: 0.5 } }}>
                                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                        <Extension sx={{ fontSize: 16, color: site?.aa_plugin_active ? '#16A34A' : '#9B89C4' }} />
                                        <Typography variant="body2" fontWeight={700}>Active Auditor Plugin Token</Typography>
                                        <Chip
                                            label={site?.aa_plugin_active ? 'Connected' : site?.aa_token_hint ? 'Saved' : 'Not Set'}
                                            size="small"
                                            sx={{
                                                height: 18, fontSize: '0.62rem', fontWeight: 700,
                                                bgcolor: site?.aa_plugin_active ? '#DCFCE7' : site?.aa_token_hint ? '#FEF9C3' : '#EDE8FC',
                                                color: site?.aa_plugin_active ? '#15803D' : site?.aa_token_hint ? '#92400E' : '#5C4A8A',
                                            }}
                                        />
                                    </Box>
                                </AccordionSummary>
                                <AccordionDetails sx={{ pt: 0 }}>
                                    <Alert severity="info" sx={{ mb: 1.5, fontSize: '0.8rem' }}>
                                        Full data access including updates, security, Lighthouse & SEO. Get the token from
                                        WP Admin → Active Auditor → Settings.
                                    </Alert>
                                    {site?.aa_token_hint && (
                                        <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace', display: 'block', mb: 1 }}>
                                            Current: ••••••{site.aa_token_hint}
                                            {site?.aa_plugin_active && ` · v${site.aa_plugin_version}`}
                                        </Typography>
                                    )}
                                    <Stack direction="row" spacing={1.5} alignItems="flex-start">
                                        <TextField
                                            label={site?.aa_token_hint ? 'Replace Token' : 'Plugin API Token'}
                                            size="small"
                                            value={aaToken}
                                            onChange={(e) => setAaToken(e.target.value)}
                                            type={showAaToken ? 'text' : 'password'}
                                            placeholder="Paste from WP Admin → Active Auditor → Settings"
                                            sx={{ flex: 1 }}
                                            InputProps={{
                                                endAdornment: (
                                                    <InputAdornment position="end">
                                                        <IconButton size="small" onClick={() => setShowAaToken(!showAaToken)} edge="end">
                                                            {showAaToken ? <VisibilityOff sx={{ fontSize: 16 }} /> : <Visibility sx={{ fontSize: 16 }} />}
                                                        </IconButton>
                                                    </InputAdornment>
                                                ),
                                            }}
                                        />
                                        <Button
                                            variant="contained" size="small"
                                            disabled={!aaToken.trim() || savingAa}
                                            startIcon={savingAa ? <CircularProgress size={13} color="inherit" /> : <Save sx={{ fontSize: 14 }} />}
                                            onClick={() => saveAaToken({ variables: { id, aa_token: aaToken.trim() } })}
                                            sx={{ borderRadius: 2, fontWeight: 600, mt: 0.3, bgcolor: '#8E43F0', whiteSpace: 'nowrap' }}
                                        >
                                            {savingAa ? 'Saving…' : 'Verify & Save'}
                                        </Button>
                                    </Stack>
                                </AccordionDetails>
                            </Accordion>
                        </SectionCard>

                        {/* ── Bottom action bar ── */}
                        <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1.5, pt: 1 }}>
                            <Button
                                variant="outlined"
                                onClick={() => navigate(-1)}
                                sx={{ borderRadius: 2, fontWeight: 600 }}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                disabled={saving || !isDirty}
                                startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />}
                                sx={{ borderRadius: 2, fontWeight: 600, bgcolor: '#8E43F0' }}
                            >
                                {saving ? 'Saving…' : 'Save Changes'}
                            </Button>
                        </Box>
                    </Stack>
                )}
            </Box>
        </AppLayout>
    );
}

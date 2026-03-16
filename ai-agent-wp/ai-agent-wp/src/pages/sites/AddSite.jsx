import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import {
    Box, Grid, Typography, Button, Stack, TextField,
    InputAdornment, IconButton, Alert, CircularProgress,
    Accordion, AccordionSummary, AccordionDetails, Chip,
    FormControl, InputLabel, Select, MenuItem,
} from '@mui/material';
import {
    ArrowBack, Add, Visibility, VisibilityOff, Language,
    Security as SecurityIcon, Settings as SettingsIcon,
    Key, Extension, ExpandMore,
} from '@mui/icons-material';
import AppLayout from '../../components/layout/AppLayout';
import SectionCard from '../../components/sites/SectionCard';
import SelectField from '../../components/sites/SelectField';
import { useNotification } from '../../hooks/useNotification';
import AppSnackbar from '../../components/AppSnackbar';
import { GET_WP_CLIENTS, GET_WP_SITES } from '../../graphql/queries';
import { CREATE_WP_SITE } from '../../graphql/mutations';

// ── Constants (same as EditSite) ───────────────────────────────────────────────
const HOSTING_OPTIONS = [
    { value: 'shared', label: 'Shared Hosting' },
    { value: 'vps', label: 'VPS' },
    { value: 'cloud', label: 'Cloud' },
    { value: 'dedicated', label: 'Dedicated Server' },
    { value: 'managed-wp', label: 'Managed WordPress' },
    { value: 'cloudways', label: 'Cloudways' },
    { value: 'wpengine', label: 'WP Engine' },
    { value: 'kinsta', label: 'Kinsta' },
    { value: 'siteground', label: 'SiteGround' },
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

// ── URL sanitizer (same as EditSite) ──────────────────────────────────────────
const sanitizeUrl = (raw = '') => {
    const trimmed = raw.trim().replace(/\/$/, '');
    const doubled = trimmed.match(/^(https?:\/\/[^/]+)\/\1(.*)$/);
    if (doubled) return doubled[1] + (doubled[2] || '');
    return trimmed;
};

// ── Main Component ─────────────────────────────────────────────────────────────
export default function AddSite() {
    const navigate = useNavigate();
    const { notify, snackbar, closeSnackbar } = useNotification();

    // ── Load clients dropdown ──
    const { data: clientsData } = useQuery(GET_WP_CLIENTS);
    const wpClients = clientsData?.wpClients ?? [];

    // ── Main form state ──
    const [form, setForm] = useState({
        name: '',
        url: '',
        wp_admin_url: '',
        notes: '',
        hosting_environment: 'shared',
        priority: 'normal',
        check_schedule: 'daily',
        wp_client_id: '',
    });

    // ── Auth state ──
    const [authMethod, setAuthMethod] = useState('app_password');
    const [authUsername, setAuthUsername] = useState('');
    const [authToken, setAuthToken] = useState('');
    const [showAuthToken, setShowAuthToken] = useState(false);

    // ── AA Token state ──
    const [aaToken, setAaToken] = useState('');
    const [showAaToken, setShowAaToken] = useState(false);

    // ── Validation errors ──
    const [errors, setErrors] = useState({});

    const update = (field) => (value) => setForm((prev) => ({ ...prev, [field]: value }));
    const handleTextField = (field) => (e) => update(field)(e.target.value);

    // ── Mutation ──
    const [createWpSite, { loading: creating }] = useMutation(CREATE_WP_SITE, {
        refetchQueries: [{ query: GET_WP_SITES }],
        onCompleted: (data) => {
            notify('Site created successfully!', 'success');
            const newId = data?.createWpSite?.id;
            setTimeout(() => navigate(newId ? `/sites/${newId}` : '/sites'), 600);
        },
        onError: (e) => notify(e.message, 'error'),
    });

    // ── Validation ──
    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = 'Site name is required';
        if (!form.url.trim()) e.url = 'Site URL is required';
        else if (!/^https?:\/\//.test(form.url.trim())) e.url = 'Must start with https://';
        if (!form.wp_client_id) e.wp_client_id = 'Client is required';
        if (!form.hosting_environment) e.hosting_environment = 'Hosting is required';
        setErrors(e);
        return Object.keys(e).length === 0;
    };

    // ── Submit ──
    const handleSubmit = (e) => {
        e.preventDefault();
        if (!validate()) return;

        const cleanUrl = sanitizeUrl(form.url);
        const variables = {
            wp_client_id: form.wp_client_id,
            name: form.name,
            url: cleanUrl,
            wp_admin_url: form.wp_admin_url ? sanitizeUrl(form.wp_admin_url) : undefined,
            hosting_environment: form.hosting_environment,
            notes: form.notes || undefined,
            check_schedule: form.check_schedule,
            priority: form.priority,
        };

        // Optional auth
        if (authToken.trim() && authUsername.trim()) {
            variables.auth_method = authMethod;
            variables.auth_username = authUsername;
            variables.auth_token = authToken;
        }

        // Optional AA token
        if (aaToken.trim()) {
            variables.aa_token = aaToken.trim();
        }

        createWpSite({ variables });
    };

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
                        onClick={() => navigate('/sites')}
                        size="small"
                        sx={{ color: 'text.secondary', mb: 1.5, '&:hover': { bgcolor: '#EDE8FC' } }}
                    >
                        Sites
                    </Button>
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 2 }}>
                        <Box>
                            <Typography variant="h5" fontWeight={800} color="#1A0A3C">Add New Site</Typography>
                            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.25 }}>
                                Connect and start monitoring a new WordPress site
                            </Typography>
                        </Box>
                        <Stack direction="row" spacing={1.5}>
                            <Button
                                variant="outlined"
                                onClick={() => navigate('/sites')}
                                sx={{ borderRadius: 2, fontWeight: 600 }}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="contained"
                                disabled={creating}
                                startIcon={creating ? <CircularProgress size={16} color="inherit" /> : <Add />}
                                sx={{ borderRadius: 2, fontWeight: 600, bgcolor: '#8E43F0' }}
                            >
                                {creating ? 'Creating…' : 'Add Site'}
                            </Button>
                        </Stack>
                    </Box>
                </Box>

                <Stack spacing={2.5}>
                    {/* ── Section 1: Site Information ── */}
                    <SectionCard icon={Language} title="Site Information">
                        <Grid container spacing={2}>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Site Name"
                                    required fullWidth size="small"
                                    value={form.name}
                                    onChange={handleTextField('name')}
                                    error={!!errors.name} helperText={errors.name || 'Display name shown in the portal'}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <SelectField
                                    label="WP Client"
                                    value={form.wp_client_id}
                                    onChange={update('wp_client_id')}
                                    error={!!errors.wp_client_id}
                                    options={[
                                        { value: '', label: '— Select client —' },
                                        ...wpClients.map((c) => ({ value: c.id, label: c.name })),
                                    ]}
                                />
                                {errors.wp_client_id && (
                                    <Typography variant="caption" color="error" sx={{ ml: 1.5 }}>
                                        {errors.wp_client_id}
                                    </Typography>
                                )}
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Site URL"
                                    required fullWidth size="small"
                                    value={form.url}
                                    onChange={handleTextField('url')}
                                    placeholder="https://example.com"
                                    error={!!errors.url} helperText={errors.url || 'Must include https://'}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="WP Admin URL"
                                    fullWidth size="small"
                                    value={form.wp_admin_url}
                                    onChange={handleTextField('wp_admin_url')}
                                    placeholder="https://example.com/wp-admin"
                                    helperText="Leave blank to auto-set from Site URL"
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
                                    fullWidth multiline rows={3} size="small"
                                    value={form.notes}
                                    onChange={handleTextField('notes')}
                                    placeholder="Internal notes about this site…"
                                />
                            </Grid>
                        </Grid>
                    </SectionCard>

                    {/* ── Section 2: Monitoring Settings ── */}
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

                    {/* ── Section 3: Authentication ── */}
                    <SectionCard icon={SecurityIcon} title="Site Authentication">
                        <Alert severity="info" sx={{ mb: 2.5, fontSize: '0.82rem' }}>
                            Optionally connect credentials now — or skip and set them up later from <strong>Site Settings</strong>.
                            The Active Auditor plugin provides <strong>full access</strong>; WP Admin credentials provide basic access.
                        </Alert>

                        <Typography variant="body2" fontWeight={700} sx={{ mb: 1.5, color: '#1A0A3C' }}>
                            WP Admin Credentials
                        </Typography>

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
                                    placeholder="xxxx xxxx xxxx xxxx"
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

                        {/* AA Token Accordion */}
                        <Accordion elevation={0} sx={{ mt: 2.5, border: '1px solid #DDD4F8', borderRadius: '8px !important', '&:before': { display: 'none' } }}>
                            <AccordionSummary expandIcon={<ExpandMore />} sx={{ minHeight: 44, '& .MuiAccordionSummary-content': { my: 0.5 } }}>
                                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                    <Extension sx={{ fontSize: 16, color: '#9B89C4' }} />
                                    <Typography variant="body2" fontWeight={700}>Active Auditor Plugin Token</Typography>
                                    <Chip
                                        label="Not Set"
                                        size="small"
                                        sx={{ height: 18, fontSize: '0.62rem', fontWeight: 700, bgcolor: '#EDE8FC', color: '#5C4A8A' }}
                                    />
                                </Box>
                            </AccordionSummary>
                            <AccordionDetails sx={{ pt: 0 }}>
                                <Alert severity="info" sx={{ mb: 1.5, fontSize: '0.8rem' }}>
                                    Full data access including updates, security, Lighthouse & SEO. Get the token from
                                    WP Admin → Active Auditor → Settings.
                                </Alert>
                                <Stack direction="row" spacing={1.5} alignItems="flex-start">
                                    <TextField
                                        label="Plugin API Token"
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
                                    <Typography variant="caption" color="text.secondary" sx={{ mt: 1.5, whiteSpace: 'nowrap' }}>
                                        Verified on create
                                    </Typography>
                                </Stack>
                            </AccordionDetails>
                        </Accordion>
                    </SectionCard>

                    {/* ── Bottom action bar ── */}
                    <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1.5, pt: 1 }}>
                        <Button
                            variant="outlined"
                            onClick={() => navigate('/sites')}
                            sx={{ borderRadius: 2, fontWeight: 600 }}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="contained"
                            disabled={creating}
                            startIcon={creating ? <CircularProgress size={16} color="inherit" /> : <Add />}
                            sx={{ borderRadius: 2, fontWeight: 600, bgcolor: '#8E43F0' }}
                        >
                            {creating ? 'Creating…' : 'Add Site'}
                        </Button>
                    </Box>
                </Stack>
            </Box>
        </AppLayout>
    );
}

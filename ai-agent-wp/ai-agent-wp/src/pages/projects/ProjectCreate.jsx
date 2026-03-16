import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, Typography, Stack, Chip, CircularProgress, Paper,
    Switch, FormControlLabel,
} from '@mui/material';
import { Save, Close, Language } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_CLIENTS, GET_DEVELOPERS } from '../../graphql/queries';
import { CREATE_PROJECT } from '../../graphql/mutations';

const STATUSES = ['active', 'review', 'completed', 'paused'];
const EMPTY = { name: '', description: '', client_id: '', developer_ids: [], status: 'active', budget: '', due_date: '', is_wordpress: false, wp_site_url: '' };

export default function ProjectCreate() {
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const { data: clientsData } = useQuery(GET_CLIENTS, { fetchPolicy: 'cache-and-network' });
    const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-and-network' });
    const clients = clientsData?.clients ?? [];
    const developers = devsData?.developers ?? [];

    const [createProject, { loading }] = useMutation(CREATE_PROJECT, {
        onCompleted: (d) => {
            enqueueSnackbar('Project created successfully', { variant: 'success' });
            navigate(`/projects/${d.createProject.id}`);
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to create project', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = 'Project name is required';
        if (!form.client_id) e.client_id = 'Client is required';
        if (form.is_wordpress && !form.wp_site_url.trim()) e.wp_site_url = 'WordPress site URL is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        createProject({
            variables: {
                name: form.name,
                description: form.description || null,
                client_id: form.client_id,
                developer_ids: form.developer_ids.length ? form.developer_ids : null,
                status: form.status,
                budget: form.budget ? parseFloat(form.budget) : null,
                due_date: form.due_date || null,
                is_wordpress: form.is_wordpress,
                wp_site_url: form.is_wordpress ? form.wp_site_url.trim() : null,
            },
        });
    };

    const toggleDev = (id) =>
        set('developer_ids', form.developer_ids.includes(id)
            ? form.developer_ids.filter((d) => d !== id)
            : [...form.developer_ids, id]);

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="New Project"
                subtitle="Fill in the details to create a new project"
                breadcrumbs={[
                    { label: 'Dashboard', path: '/dashboard' },
                    { label: 'Projects', path: '/projects' },
                    { label: 'Create' },
                ]}
            />

            <Grid container spacing={3}>
                {/* Main details */}
                <Grid item xs={12} lg={8}>
                    <FormCard title="Project Details" subtitle="Core information about the project">
                        <Stack spacing={2.5}>
                            <TextField
                                label="Project Name *" fullWidth
                                value={form.name} onChange={(e) => set('name', e.target.value)}
                                error={!!errors.name} helperText={errors.name}
                            />
                            <TextField
                                label="Description" fullWidth multiline rows={3}
                                value={form.description} onChange={(e) => set('description', e.target.value)}
                            />
                            <Grid container spacing={2} alignItems="flex-start">
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Budget (£)" fullWidth type="number"
                                        inputProps={{ min: 0, step: 100 }}
                                        value={form.budget} onChange={(e) => set('budget', e.target.value)}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Due Date" fullWidth type="date"
                                        value={form.due_date} onChange={(e) => set('due_date', e.target.value)}
                                        InputLabelProps={{ shrink: true }}
                                        inputProps={{ placeholder: 'DD/MM/YYYY' }}
                                        helperText="DD / MM / YYYY"
                                    />
                                </Grid>
                            </Grid>
                        </Stack>
                    </FormCard>
                </Grid>

                {/* Sidebar */}
                <Grid item xs={12} lg={4}>
                    <Stack spacing={3}>
                        <FormCard title="Client & Status" subtitle="Assign a client and set status">
                            <Stack spacing={2.5}>
                                <FormControl fullWidth error={!!errors.client_id}>
                                    <InputLabel>Client *</InputLabel>
                                    <Select value={form.client_id} label="Client *" onChange={(e) => set('client_id', e.target.value)}>
                                        {clients.map((c) => <MenuItem key={c.id} value={c.id}>{c.name}</MenuItem>)}
                                    </Select>
                                    {errors.client_id && <Typography variant="caption" color="error" sx={{ mt: 0.5, ml: 1.5 }}>{errors.client_id}</Typography>}
                                </FormControl>
                                <FormControl fullWidth>
                                    <InputLabel>Status</InputLabel>
                                    <Select value={form.status} label="Status" onChange={(e) => set('status', e.target.value)}>
                                        {STATUSES.map((s) => <MenuItem key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</MenuItem>)}
                                    </Select>
                                </FormControl>
                            </Stack>
                        </FormCard>

                        <FormCard title="Developers" subtitle="Click to add team members">
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                                {developers.map((d) => {
                                    const selected = form.developer_ids.includes(d.id);
                                    return (
                                        <Chip
                                            key={d.id} label={d.name} clickable onClick={() => toggleDev(d.id)}
                                            color={selected ? 'primary' : 'default'}
                                            variant={selected ? 'filled' : 'outlined'}
                                            sx={{ fontWeight: selected ? 700 : 400 }}
                                        />
                                    );
                                })}
                                {developers.length === 0 && <Typography variant="caption" color="text.secondary">No developers found</Typography>}
                            </Box>
                        </FormCard>

                        {/* WordPress toggle */}
                        <FormCard title="WordPress Site" subtitle="Auto-add to WP Operations Sites" icon={Language}>
                            <Stack spacing={2}>
                                <FormControlLabel
                                    control={
                                        <Switch
                                            checked={form.is_wordpress}
                                            onChange={(e) => { set('is_wordpress', e.target.checked); if (!e.target.checked) setErrors((p) => ({ ...p, wp_site_url: '' })); }}
                                            color="primary"
                                        />
                                    }
                                    label={
                                        <Typography variant="body2" fontWeight={600}>
                                            This project is a WordPress website
                                        </Typography>
                                    }
                                />
                                {form.is_wordpress && (
                                    <TextField
                                        label="WordPress Site URL *"
                                        fullWidth
                                        value={form.wp_site_url}
                                        onChange={(e) => set('wp_site_url', e.target.value)}
                                        placeholder="https://example.com"
                                        error={!!errors.wp_site_url}
                                        helperText={errors.wp_site_url || 'Site will appear in WP Operations → Sites'}
                                    />
                                )}
                            </Stack>
                        </FormCard>
                    </Stack>
                </Grid>
            </Grid>

            {/* Sticky bottom action bar */}
            <Paper elevation={4} sx={{
                position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 1200,
                px: { xs: 2, sm: 3 }, py: { xs: 2.5, sm: 2 }, borderTop: '1px solid #DDD4F8',
                display: 'flex', justifyContent: 'flex-end', gap: 2,
                bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(8px)',
            }}>
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate('/projects')} disabled={loading} sx={{ borderRadius: 2 }}>Cancel</Button>
                <Button variant="contained" startIcon={loading ? <CircularProgress size={16} color="inherit" /> : <Save />} onClick={handleSave} disabled={loading} sx={{ borderRadius: 2, px: 3 }}>
                    {loading ? 'Creating…' : 'Create Project'}
                </Button>
            </Paper>
        </Box>
    );
}


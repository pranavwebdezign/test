import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, Typography, Stack, Chip, CircularProgress, Alert, Skeleton, Paper,
    Switch, FormControlLabel,
} from '@mui/material';
import { Save, Close, Language } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_PROJECT, GET_CLIENTS, GET_DEVELOPERS } from '../../graphql/queries';
import { UPDATE_PROJECT } from '../../graphql/mutations';

const STATUSES = ['active', 'review', 'completed', 'paused'];

export default function ProjectEdit() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState({ name: '', description: '', status: 'active', budget: '', due_date: '', progress: 0, is_wordpress: false, wp_site_url: '' });
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const { data, loading: loadingProject, error } = useQuery(GET_PROJECT, {
        variables: { id }, fetchPolicy: 'cache-and-network', skip: !id,
    });
    const { data: clientsData } = useQuery(GET_CLIENTS, { fetchPolicy: 'cache-first' });
    const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-first' });
    const clients = clientsData?.clients ?? [];
    const developers = devsData?.developers ?? [];

    // Pre-fill form when project loads
    useEffect(() => {
        const p = data?.project;
        if (!p) return;
        setForm({
            name: p.name ?? '',
            description: p.description ?? '',
            status: p.status ?? 'active',
            budget: p.budget ?? '',
            due_date: p.due_date ?? '',
            progress: p.progress ?? 0,
            is_wordpress: p.is_wordpress ?? false,
            wp_site_url: p.wp_site_url ?? '',
        });
    }, [data]);

    const [updateProject, { loading: saving }] = useMutation(UPDATE_PROJECT, {
        onCompleted: () => {
            enqueueSnackbar('Project updated successfully', { variant: 'success' });
            navigate(`/projects/${id}`);
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to update project', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = 'Project name is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        updateProject({
            variables: {
                id,
                name: form.name,
                description: form.description || null,
                status: form.status,
                budget: form.budget ? parseFloat(form.budget) : null,
                due_date: form.due_date || null,
                progress: parseInt(form.progress, 10) || 0,
                is_wordpress: form.is_wordpress,
                wp_site_url: form.is_wordpress ? form.wp_site_url.trim() : null,
            },
        });
    };

    if (loadingProject && !data) return (
        <Box>
            <Skeleton variant="text" width={200} height={44} sx={{ mb: 3 }} />
            <Grid container spacing={3}>
                <Grid item xs={12} lg={8}><Skeleton variant="rounded" height={280} sx={{ borderRadius: 3 }} /></Grid>
                <Grid item xs={12} lg={4}><Skeleton variant="rounded" height={200} sx={{ borderRadius: 3 }} /></Grid>
            </Grid>
        </Box>
    );

    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load project. {error.message}</Alert>;

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="Edit Project"
                subtitle={`Editing: ${data?.project?.name ?? '…'}`}
                breadcrumbs={[
                    { label: 'Dashboard', path: '/dashboard' },
                    { label: 'Projects', path: '/projects' },
                    { label: data?.project?.name ?? '…', path: `/projects/${id}` },
                    { label: 'Edit' },
                ]}
            />

            <Grid container spacing={3}>
                <Grid item xs={12} lg={8}>
                    <FormCard title="Project Details" subtitle="Update the core project information">
                        <Stack spacing={2.5}>
                            <TextField
                                label="Project Name *" fullWidth
                                value={form.name} onChange={(e) => set('name', e.target.value)}
                                error={!!errors.name} helperText={errors.name}
                            />
                            <TextField
                                label="Description" fullWidth multiline rows={4}
                                value={form.description} onChange={(e) => set('description', e.target.value)}
                            />
                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Budget (£)" fullWidth type="number" inputProps={{ min: 0, step: 100 }}
                                        value={form.budget} onChange={(e) => set('budget', e.target.value)}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Due Date" fullWidth type="date"
                                        value={form.due_date} onChange={(e) => set('due_date', e.target.value)}
                                        InputLabelProps={{ shrink: true }}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6}>
                                    <TextField
                                        label="Progress (%)" fullWidth type="number" inputProps={{ min: 0, max: 100, step: 5 }}
                                        value={form.progress} onChange={(e) => set('progress', e.target.value)}
                                    />
                                </Grid>
                            </Grid>
                        </Stack>
                    </FormCard>
                </Grid>

                <Grid item xs={12} lg={4}>
                    <FormCard title="Status" subtitle="Current project status">
                        <FormControl fullWidth>
                            <InputLabel>Status</InputLabel>
                            <Select value={form.status} label="Status" onChange={(e) => set('status', e.target.value)}>
                                {STATUSES.map((s) => <MenuItem key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</MenuItem>)}
                            </Select>
                        </FormControl>
                        <Typography variant="caption" color="text.secondary" sx={{ mt: 2, display: 'block' }}>
                            To reassign developers or change the client, use the backend admin panel.
                        </Typography>
                    </FormCard>

                    {/* WordPress toggle */}
                    <FormCard title="WordPress Site" subtitle="Auto-add to WP Operations Sites" icon={Language}>
                        <Stack spacing={2}>
                            <FormControlLabel
                                control={
                                    <Switch
                                        checked={form.is_wordpress}
                                        onChange={(e) => { set('is_wordpress', e.target.checked); }}
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
                                    label="WordPress Site URL"
                                    fullWidth
                                    value={form.wp_site_url}
                                    onChange={(e) => set('wp_site_url', e.target.value)}
                                    placeholder="https://example.com"
                                    helperText="Site will appear in WP Operations → Sites"
                                />
                            )}
                        </Stack>
                    </FormCard>
                </Grid>
            </Grid>

            {/* Sticky bottom action bar */}
            <Paper elevation={4} sx={{
                position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 1200,
                px: { xs: 2, sm: 3 }, py: { xs: 2.5, sm: 2 }, borderTop: '1px solid #DDD4F8',
                display: 'flex', justifyContent: 'flex-end', gap: 2,
                bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(8px)',
            }}>
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate(`/projects/${id}`)} disabled={saving} sx={{ borderRadius: 2 }}>Cancel</Button>
                <Button variant="contained" startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />} onClick={handleSave} disabled={saving} sx={{ borderRadius: 2, px: 3 }}>
                    {saving ? 'Saving…' : 'Save Changes'}
                </Button>
            </Paper>
        </Box>
    );
}


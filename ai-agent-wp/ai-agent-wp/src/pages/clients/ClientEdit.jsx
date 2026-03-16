import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, CircularProgress, Paper, Skeleton, Alert,
} from '@mui/material';
import { Save, Close, PersonOutlined, InfoOutlined } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_CLIENT } from '../../graphql/queries';
import { UPDATE_CLIENT } from '../../graphql/mutations';

export default function ClientEdit() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();

    const { data, loading: fetching, error } = useQuery(GET_CLIENT, { variables: { id }, fetchPolicy: 'cache-and-network' });
    const [form, setForm] = useState({ name: '', email: '', company: '', phone: '', address: '', status: 'active' });
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    useEffect(() => {
        if (data?.client) {
            const c = data.client;
            setForm({ name: c.name, email: c.email, company: c.company ?? '', phone: c.phone ?? '', address: c.address ?? '', status: c.status });
        }
    }, [data]);

    const [updateClient, { loading: saving }] = useMutation(UPDATE_CLIENT, {
        onCompleted: () => {
            enqueueSnackbar('Client updated successfully', { variant: 'success' });
            navigate(`/clients/${id}`);
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to update client', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = 'Full name is required';
        if (!form.email.trim()) e.email = 'Email address is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        updateClient({ variables: { id, name: form.name, email: form.email, company: form.company || null, status: form.status } });
    };

    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load client.</Alert>;

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="Edit Client"
                subtitle={`Editing: ${data?.client?.name ?? '…'}`}
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Clients', path: '/clients' }, { label: data?.client?.name ?? '…', path: `/clients/${id}` }, { label: 'Edit' }]}
            />

            {fetching ? (
                <Box sx={{ maxWidth: { xs: '100%', sm: 780 }, mx: 'auto' }}>
                    {[1, 2].map((i) => <Skeleton key={i} variant="rounded" height={180} sx={{ mb: 2.5, borderRadius: 3 }} />)}
                </Box>
            ) : (
                <Box sx={{ maxWidth: { xs: '100%', sm: 780 }, mx: 'auto' }}>
                    {/* Basic Information */}
                    <FormCard title="Basic Information" icon={<PersonOutlined />} sx={{ mb: 3 }}>
                        <Grid container spacing={2.5}>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Full Name *" fullWidth autoFocus
                                    value={form.name} onChange={(e) => set('name', e.target.value)}
                                    error={!!errors.name} helperText={errors.name}
                                    sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Email Address *" type="email" fullWidth
                                    value={form.email} onChange={(e) => set('email', e.target.value)}
                                    error={!!errors.email} helperText={errors.email}
                                    sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Company" fullWidth
                                    value={form.company} onChange={(e) => set('company', e.target.value)}
                                    sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <TextField
                                    label="Phone" fullWidth
                                    value={form.phone} onChange={(e) => set('phone', e.target.value)}
                                    sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                />
                            </Grid>
                        </Grid>
                    </FormCard>

                    {/* Additional Details */}
                    <FormCard title="Additional Details" icon={<InfoOutlined />}>
                        <Grid container spacing={2.5}>
                            <Grid item xs={12}>
                                <TextField
                                    label="Address" fullWidth multiline rows={2}
                                    value={form.address} onChange={(e) => set('address', e.target.value)}
                                    sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                />
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth>
                                    <InputLabel>Status</InputLabel>
                                    <Select value={form.status} label="Status" onChange={(e) => set('status', e.target.value)} sx={{ borderRadius: 2 }}>
                                        <MenuItem value="active">Active</MenuItem>
                                        <MenuItem value="inactive">Inactive</MenuItem>
                                    </Select>
                                </FormControl>
                            </Grid>
                        </Grid>
                    </FormCard>
                </Box>
            )}

            {/* Sticky bottom action bar */}
            <Paper elevation={4} sx={{
                position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 1200,
                px: { xs: 2, sm: 3 }, py: { xs: 2.5, sm: 2 }, borderTop: '1px solid #DDD4F8',
                display: 'flex', justifyContent: 'flex-end', gap: 2,
                bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(8px)',
            }}>
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate(`/clients/${id}`)} disabled={saving} sx={{ borderRadius: 2 }}>
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />}
                    onClick={handleSave} disabled={saving || fetching}
                    sx={{ borderRadius: 2, px: 3 }}
                >
                    {saving ? 'Saving…' : 'Save Changes'}
                </Button>
            </Paper>
        </Box>
    );
}


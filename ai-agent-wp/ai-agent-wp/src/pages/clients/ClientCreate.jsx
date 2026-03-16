import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, CircularProgress, Paper,
} from '@mui/material';
import { Save, Close, PersonOutlined, InfoOutlined } from '@mui/icons-material';
import { useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { CREATE_CLIENT } from '../../graphql/mutations';

const EMPTY = { name: '', email: '', company: '', phone: '', address: '', status: 'active' };

export default function ClientCreate() {
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const [createClient, { loading }] = useMutation(CREATE_CLIENT, {
        onCompleted: () => {
            enqueueSnackbar('Client created successfully', { variant: 'success' });
            navigate('/clients');
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to create client', { variant: 'error' }),
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
        createClient({ variables: { name: form.name, email: form.email, company: form.company || null, phone: form.phone || null, address: form.address || null } });
    };

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="New Client"
                subtitle="Fill in the details to register a new client"
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Clients', path: '/clients' }, { label: 'New Client' }]}
            />

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

            {/* Sticky bottom action bar */}
            <Paper elevation={4} sx={{
                position: 'fixed', bottom: 0, left: 0, right: 0, zIndex: 1200,
                px: { xs: 2, sm: 3 }, py: { xs: 2.5, sm: 2 }, borderTop: '1px solid #DDD4F8',
                display: 'flex', justifyContent: 'flex-end', gap: 2,
                bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(8px)',
            }}>
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate('/clients')} disabled={loading} sx={{ borderRadius: 2 }}>
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    startIcon={loading ? <CircularProgress size={16} color="inherit" /> : <Save />}
                    onClick={handleSave} disabled={loading}
                    sx={{ borderRadius: 2, px: 3 }}
                >
                    {loading ? 'Creating…' : 'Create Client'}
                </Button>
            </Paper>
        </Box>
    );
}


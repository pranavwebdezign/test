import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, FormControl, InputLabel, Select,
    MenuItem, CircularProgress, Paper, Skeleton, Alert,
} from '@mui/material';
import { Save, Close, PersonOutlined, ManageAccountsOutlined } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_USERS } from '../../graphql/queries';
import { UPDATE_USER } from '../../graphql/mutations';

const ROLES = ['SuperAdmin', 'Developer', 'Client'];
const STATUSES = ['active', 'inactive'];

export default function UserEdit() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();

    const { data, loading: fetching, error } = useQuery(GET_USERS, { fetchPolicy: 'cache-and-network' });
    const userObj = data?.users?.find((u) => u.id === id);

    const [form, setForm] = useState({ name: '', email: '', role: 'Client', status: 'active', company: '', phone: '' });
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    useEffect(() => {
        if (userObj) setForm({
            name: userObj.name, email: userObj.email, role: userObj.role,
            status: userObj.status, company: userObj.company ?? '', phone: userObj.phone ?? '',
        });
    }, [userObj]);

    const [updateUser, { loading: saving }] = useMutation(UPDATE_USER, {
        onCompleted: () => {
            enqueueSnackbar('User updated successfully', { variant: 'success' });
            navigate('/users');
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to update user', { variant: 'error' }),
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
        updateUser({ variables: { id, name: form.name, email: form.email, role: form.role, status: form.status, company: form.company || null, phone: form.phone || null } });
    };

    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load user data.</Alert>;

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="Edit User"
                subtitle={`Editing: ${userObj?.name ?? '…'}`}
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Users', path: '/users' }, { label: userObj?.name ?? '…' }, { label: 'Edit' }]}
            />

            {fetching ? (
                <Box sx={{ maxWidth: { xs: '100%', sm: 780 }, mx: 'auto' }}>
                    {[1, 2].map((i) => <Skeleton key={i} variant="rounded" height={180} sx={{ mb: 2.5, borderRadius: 3 }} />)}
                </Box>
            ) : (
                <Box sx={{ maxWidth: { xs: '100%', sm: 780 }, mx: 'auto' }}>
                    {/* Account Information */}
                    <FormCard title="Account Information" icon={<PersonOutlined />} sx={{ mb: 3 }}>
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

                    {/* Access Control */}
                    <FormCard title="Access Control" icon={<ManageAccountsOutlined />}>
                        <Grid container spacing={2.5}>
                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth>
                                    <InputLabel>Role</InputLabel>
                                    <Select value={form.role} label="Role" onChange={(e) => set('role', e.target.value)} sx={{ borderRadius: 2 }}>
                                        {ROLES.map((r) => <MenuItem key={r} value={r}>{r}</MenuItem>)}
                                    </Select>
                                </FormControl>
                            </Grid>
                            <Grid item xs={12} sm={6}>
                                <FormControl fullWidth>
                                    <InputLabel>Status</InputLabel>
                                    <Select value={form.status} label="Status" onChange={(e) => set('status', e.target.value)} sx={{ borderRadius: 2 }}>
                                        {STATUSES.map((s) => <MenuItem key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</MenuItem>)}
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
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate('/users')} disabled={saving} sx={{ borderRadius: 2 }}>
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


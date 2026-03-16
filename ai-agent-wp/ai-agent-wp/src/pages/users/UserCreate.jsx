import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Box, Button, Card, CardContent, Grid, TextField, Typography,
    FormControl, InputLabel, Select, MenuItem, Stack, CircularProgress,
    InputAdornment, IconButton,
} from '@mui/material';
import { ArrowBack, Save, Visibility, VisibilityOff } from '@mui/icons-material';
import { useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import { CREATE_USER } from '../../graphql/mutations';

const ROLES = ['SuperAdmin', 'Developer', 'Client'];
const EMPTY = { name: '', email: '', password: '', role: 'Client', company: '', phone: '' };

export default function UserCreate() {
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState(EMPTY);
    const [showPw, setShowPw] = useState(false);
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const [createUser, { loading }] = useMutation(CREATE_USER, {
        onCompleted: () => {
            enqueueSnackbar('User created successfully', { variant: 'success' });
            navigate('/users');
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to create user', { variant: 'error' }),
    });

    const handleSave = () => {
        if (!form.name || !form.email || !form.password) return;
        createUser({
            variables: {
                name: form.name, email: form.email, password: form.password,
                role: form.role, company: form.company || null, phone: form.phone || null,
            }
        });
    };

    return (
        <Box>
            <PageHeader
                title="Add User"
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Users', path: '/users' }, { label: 'Add User' }]}
            />

            <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', mb: 3 }}>
                <CardContent sx={{ p: 3 }}>
                    <Typography variant="h6" fontWeight={700} sx={{ mb: 2.5 }}>Account Details</Typography>
                    <Grid container spacing={2.5}>
                        <Grid item xs={12} sm={6}><TextField label="Full Name" fullWidth required value={form.name} onChange={(e) => set('name', e.target.value)} /></Grid>
                        <Grid item xs={12} sm={6}><TextField label="Email Address" type="email" fullWidth required value={form.email} onChange={(e) => set('email', e.target.value)} /></Grid>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                label="Password" fullWidth required
                                type={showPw ? 'text' : 'password'}
                                value={form.password}
                                onChange={(e) => set('password', e.target.value)}
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <IconButton onClick={() => setShowPw((p) => !p)} edge="end" size="small">
                                                {showPw ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                                            </IconButton>
                                        </InputAdornment>
                                    )
                                }}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6}>
                            <FormControl fullWidth required>
                                <InputLabel>Role</InputLabel>
                                <Select value={form.role} label="Role" onChange={(e) => set('role', e.target.value)}>
                                    {ROLES.map((r) => <MenuItem key={r} value={r}>{r}</MenuItem>)}
                                </Select>
                            </FormControl>
                        </Grid>
                        <Grid item xs={12} sm={6}><TextField label="Company" fullWidth value={form.company} onChange={(e) => set('company', e.target.value)} /></Grid>
                        <Grid item xs={12} sm={6}><TextField label="Phone" fullWidth value={form.phone} onChange={(e) => set('phone', e.target.value)} /></Grid>
                    </Grid>
                </CardContent>
            </Card>

            <Stack direction="row" spacing={2} justifyContent="flex-end">
                <Button variant="outlined" startIcon={<ArrowBack />} onClick={() => navigate('/users')} disabled={loading}>Back</Button>
                <Button variant="text" onClick={() => navigate('/users')} disabled={loading}>Cancel</Button>
                <Button
                    variant="contained"
                    startIcon={loading ? <CircularProgress size={16} /> : <Save />}
                    onClick={handleSave}
                    disabled={!form.name || !form.email || !form.password || loading}
                >
                    Create User
                </Button>
            </Stack>
        </Box>
    );
}

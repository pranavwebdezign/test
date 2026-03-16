import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, Stack, CircularProgress, Paper, InputAdornment, IconButton,
} from '@mui/material';
import { Save, Close, PersonOutlined, LockOutlined, CodeOutlined, Visibility, VisibilityOff } from '@mui/icons-material';
import { useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { CREATE_DEVELOPER } from '../../graphql/mutations';

const EMPTY = { name: '', email: '', password: '', company: '', bio: '' };

export default function DeveloperCreate() {
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const [showPw, setShowPw] = useState(false);
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const [createDeveloper, { loading }] = useMutation(CREATE_DEVELOPER, {
        onCompleted: () => {
            enqueueSnackbar('Developer added successfully', { variant: 'success' });
            navigate('/developers');
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to create developer', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.name.trim()) e.name = 'Full name is required';
        if (!form.email.trim()) e.email = 'Email address is required';
        if (!form.password.trim()) e.password = 'Password is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        createDeveloper({
            variables: {
                name: form.name,
                email: form.email,
                password: form.password,
                role: 'Developer',
                company: form.company || null,
                bio: form.bio || null,
            }
        });
    };

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="Add Developer"
                subtitle="Fill in the details to add a new developer"
                breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Developers', path: '/developers' }, { label: 'Add Developer' }]}
            />

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
                                label="Password *" fullWidth
                                type={showPw ? 'text' : 'password'}
                                value={form.password}
                                onChange={(e) => set('password', e.target.value)}
                                error={!!errors.password} helperText={errors.password}
                                sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                                InputProps={{
                                    endAdornment: (
                                        <InputAdornment position="end">
                                            <IconButton onClick={() => setShowPw((p) => !p)} edge="end" size="small">
                                                {showPw ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                                            </IconButton>
                                        </InputAdornment>
                                    ),
                                }}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6}>
                            <TextField
                                label="Specialization / Role" fullWidth
                                value={form.company} onChange={(e) => set('company', e.target.value)}
                                helperText="e.g. Full Stack, Frontend, DevOps"
                                sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                            />
                        </Grid>
                    </Grid>
                </FormCard>

                {/* Skills */}
                <FormCard title="Skills" icon={<CodeOutlined />}>
                    <Grid container spacing={2.5}>
                        <Grid item xs={12}>
                            <TextField
                                label="Skills (comma-separated)" fullWidth multiline rows={3}
                                value={form.bio} onChange={(e) => set('bio', e.target.value)}
                                helperText="e.g. React, Node.js, AWS — stored in bio field"
                                sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2 } }}
                            />
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
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate('/developers')} disabled={loading} sx={{ borderRadius: 2 }}>
                    Cancel
                </Button>
                <Button
                    variant="contained"
                    startIcon={loading ? <CircularProgress size={16} color="inherit" /> : <Save />}
                    onClick={handleSave} disabled={loading}
                    sx={{ borderRadius: 2, px: 3 }}
                >
                    {loading ? 'Creating…' : 'Add Developer'}
                </Button>
            </Paper>
        </Box>
    );
}


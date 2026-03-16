import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, Typography, Stack, CircularProgress, Paper,
} from '@mui/material';
import { Save, Close } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_PROJECTS, GET_DEVELOPERS } from '../../graphql/queries';
import { CREATE_TASK } from '../../graphql/mutations';

const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];
const STATUSES = ['open', 'in_progress', 'review', 'done'];
const EMPTY = { title: '', description: '', project_id: '', assignee_id: '', priority: 'Medium', status: 'open', due_date: '' };

export default function TaskCreate() {
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState(EMPTY);
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    const { data: projectsData } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-and-network' });
    const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-and-network' });
    const projects = projectsData?.projects ?? [];
    const developers = devsData?.developers ?? [];

    const [createTask, { loading }] = useMutation(CREATE_TASK, {
        onCompleted: (d) => {
            enqueueSnackbar('Task created successfully', { variant: 'success' });
            navigate(`/tasks/${d.createTask.id}`);
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to create task', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.title.trim()) e.title = 'Task title is required';
        if (!form.project_id) e.project_id = 'Project is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        createTask({
            variables: {
                title: form.title,
                description: form.description || null,
                project_id: form.project_id,
                assignee_id: form.assignee_id || null,
                priority: form.priority,
                status: form.status,
                due_date: form.due_date || null,
            },
        });
    };

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="New Task"
                subtitle="Fill in the details to create a new task"
                breadcrumbs={[
                    { label: 'Dashboard', path: '/dashboard' },
                    { label: 'Tasks', path: '/tasks' },
                    { label: 'Create' },
                ]}
            />

            <Grid container spacing={3}>
                {/* Main */}
                <Grid item xs={12} lg={8}>
                    <FormCard title="Task Details" subtitle="What needs to be done?">
                        <Stack spacing={2.5}>
                            <TextField
                                label="Task Title *" fullWidth
                                value={form.title} onChange={(e) => set('title', e.target.value)}
                                error={!!errors.title} helperText={errors.title}
                            />
                            <TextField
                                label="Description" fullWidth multiline rows={5}
                                value={form.description} onChange={(e) => set('description', e.target.value)}
                                placeholder="Detailed description of the task…"
                            />
                        </Stack>
                    </FormCard>
                </Grid>

                {/* Sidebar */}
                <Grid item xs={12} lg={4}>
                    <Stack spacing={3}>
                        <FormCard title="Assignment" subtitle="Project, assignee, and dates">
                            <Stack spacing={2.5}>
                                <FormControl fullWidth error={!!errors.project_id}>
                                    <InputLabel>Project *</InputLabel>
                                    <Select value={form.project_id} label="Project *" onChange={(e) => set('project_id', e.target.value)}>
                                        {projects.map((p) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
                                    </Select>
                                    {errors.project_id && <Typography variant="caption" color="error" sx={{ mt: 0.5, ml: 1.5 }}>{errors.project_id}</Typography>}
                                </FormControl>
                                <FormControl fullWidth>
                                    <InputLabel>Assign To</InputLabel>
                                    <Select value={form.assignee_id} label="Assign To" onChange={(e) => set('assignee_id', e.target.value)}>
                                        <MenuItem value=""><em>Unassigned</em></MenuItem>
                                        {developers.map((d) => <MenuItem key={d.id} value={d.id}>{d.name}</MenuItem>)}
                                    </Select>
                                </FormControl>
                                <TextField
                                    label="Due Date" fullWidth type="date"
                                    value={form.due_date} onChange={(e) => set('due_date', e.target.value)}
                                    InputLabelProps={{ shrink: true }}
                                />
                            </Stack>
                        </FormCard>

                        <FormCard title="Priority & Status" subtitle="">
                            <Stack spacing={2.5}>
                                <FormControl fullWidth>
                                    <InputLabel>Priority</InputLabel>
                                    <Select value={form.priority} label="Priority" onChange={(e) => set('priority', e.target.value)}>
                                        {PRIORITIES.map((p) => <MenuItem key={p} value={p}>{p}</MenuItem>)}
                                    </Select>
                                </FormControl>
                                <FormControl fullWidth>
                                    <InputLabel>Status</InputLabel>
                                    <Select value={form.status} label="Status" onChange={(e) => set('status', e.target.value)}>
                                        {STATUSES.map((s) => <MenuItem key={s} value={s}>{s.replace('_', ' ')}</MenuItem>)}
                                    </Select>
                                </FormControl>
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
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate('/tasks')} disabled={loading} sx={{ borderRadius: 2 }}>Cancel</Button>
                <Button variant="contained" startIcon={loading ? <CircularProgress size={16} color="inherit" /> : <Save />} onClick={handleSave} disabled={loading} sx={{ borderRadius: 2, px: 3 }}>
                    {loading ? 'Creating…' : 'Create Task'}
                </Button>
            </Paper>
        </Box>
    );
}


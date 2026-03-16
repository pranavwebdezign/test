import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
    Box, Grid, Button, TextField, MenuItem, Select, FormControl,
    InputLabel, Typography, Stack, CircularProgress, Alert, Skeleton, Paper,
} from '@mui/material';
import { Save, Close } from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import FormCard from '../../components/saas/FormCard';
import { GET_TASKS, GET_PROJECTS, GET_DEVELOPERS } from '../../graphql/queries';
import { UPDATE_TASK } from '../../graphql/mutations';

const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];
const STATUSES = ['open', 'in_progress', 'review', 'done'];

export default function TaskEdit() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const [form, setForm] = useState({ title: '', description: '', project_id: '', assignee_id: '', priority: 'Medium', status: 'open', due_date: '' });
    const [errors, setErrors] = useState({});
    const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

    // Load the task — we get it from the tasks list query (should be cached)
    const { data: tasksData, loading: loadingTasks, error } = useQuery(GET_TASKS, { fetchPolicy: 'cache-and-network' });
    const { data: projectsData } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-first' });
    const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-first' });
    const projects = projectsData?.projects ?? [];
    const developers = devsData?.developers ?? [];

    const task = tasksData?.tasks?.find((t) => t.id === id);

    useEffect(() => {
        if (!task) return;
        setForm({
            title: task.title ?? '',
            description: task.description ?? '',
            project_id: task.project?.id ?? '',
            assignee_id: task.assignee?.id ?? '',
            priority: task.priority ?? 'Medium',
            status: task.status ?? 'open',
            due_date: task.due_date ?? '',
        });
    }, [task]);

    const [updateTask, { loading: saving }] = useMutation(UPDATE_TASK, {
        onCompleted: () => {
            enqueueSnackbar('Task updated successfully', { variant: 'success' });
            navigate(`/tasks/${id}`);
        },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to update task', { variant: 'error' }),
    });

    const validate = () => {
        const e = {};
        if (!form.title.trim()) e.title = 'Task title is required';
        setErrors(e);
        return !Object.keys(e).length;
    };

    const handleSave = () => {
        if (!validate()) return;
        updateTask({
            variables: {
                id,
                title: form.title,
                description: form.description || null,
                assignee_id: form.assignee_id || null,
                priority: form.priority,
                status: form.status,
                due_date: form.due_date || null,
            },
        });
    };

    if (loadingTasks && !tasksData) return (
        <Box>
            <Skeleton variant="text" width={200} height={44} sx={{ mb: 3 }} />
            <Grid container spacing={3}>
                <Grid item xs={12} lg={8}><Skeleton variant="rounded" height={280} sx={{ borderRadius: 3 }} /></Grid>
                <Grid item xs={12} lg={4}><Skeleton variant="rounded" height={220} sx={{ borderRadius: 3 }} /></Grid>
            </Grid>
        </Box>
    );

    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load task. {error.message}</Alert>;
    if (!task) return <Alert severity="warning" sx={{ m: 3 }}>Task not found.</Alert>;

    return (
        <Box sx={{ pb: 12 }}>
            <PageHeader
                title="Edit Task"
                subtitle={`Editing: ${task.title}`}
                breadcrumbs={[
                    { label: 'Dashboard', path: '/dashboard' },
                    { label: 'Tasks', path: '/tasks' },
                    { label: task.title, path: `/tasks/${id}` },
                    { label: 'Edit' },
                ]}
            />

            <Grid container spacing={3}>
                <Grid item xs={12} lg={8}>
                    <FormCard title="Task Details" subtitle="Update the task information">
                        <Stack spacing={2.5}>
                            <TextField
                                label="Task Title *" fullWidth
                                value={form.title} onChange={(e) => set('title', e.target.value)}
                                error={!!errors.title} helperText={errors.title}
                            />
                            <TextField
                                label="Description" fullWidth multiline rows={5}
                                value={form.description} onChange={(e) => set('description', e.target.value)}
                            />
                        </Stack>
                    </FormCard>
                </Grid>

                <Grid item xs={12} lg={4}>
                    <Stack spacing={3}>
                        <FormCard title="Assignment" subtitle="">
                            <Stack spacing={2.5}>
                                <FormControl fullWidth>
                                    <InputLabel>Project</InputLabel>
                                    <Select value={form.project_id} label="Project" disabled>
                                        {projects.map((p) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
                                    </Select>
                                </FormControl>
                                <FormControl fullWidth>
                                    <InputLabel>Assignee</InputLabel>
                                    <Select value={form.assignee_id} label="Assignee" onChange={(e) => set('assignee_id', e.target.value)}>
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
                <Button variant="outlined" startIcon={<Close />} onClick={() => navigate(`/tasks/${id}`)} disabled={saving} sx={{ borderRadius: 2 }}>Cancel</Button>
                <Button variant="contained" startIcon={saving ? <CircularProgress size={16} color="inherit" /> : <Save />} onClick={handleSave} disabled={saving} sx={{ borderRadius: 2, px: 3 }}>
                    {saving ? 'Saving…' : 'Save Changes'}
                </Button>
            </Paper>
        </Box>
    );
}


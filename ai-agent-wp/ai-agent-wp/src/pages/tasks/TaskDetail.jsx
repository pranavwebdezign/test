import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
    Box, Grid, Card, CardContent, Typography, Button, Chip, Stack,
    TextField, Skeleton, Alert, Divider, Avatar, CircularProgress,
    Stepper, Step, StepLabel, StepConnector, stepConnectorClasses,
} from '@mui/material';
import { styled } from '@mui/material/styles';
import {
    ArrowBack, Edit, Assignment, CalendarToday, FlagOutlined,
    PersonOutlined, SendOutlined, FolderOpen, CheckCircle,
    RadioButtonUnchecked, FiberManualRecord,
} from '@mui/icons-material';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import { GET_TASK } from '../../graphql/queries';
import { UPDATE_TASK, ADD_TASK_COMMENT } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';

const priorityColor = { Critical: '#DC2626', High: '#D97706', Medium: '#8E43F0', Low: '#16A34A' };
const priorityBg = { Critical: '#FFF1F2', High: '#FFFBEB', Medium: '#EDE8FC', Low: '#F0FDF4' };
const STATUSES = ['open', 'in_progress', 'review', 'done'];
const STATUS_LABELS = { open: 'Open', in_progress: 'In Progress', review: 'Review', done: 'Done' };

// ── Custom Stepper connector ──────────────────────────────────────
const PurpleConnector = styled(StepConnector)(() => ({
    [`&.${stepConnectorClasses.alternativeLabel}`]: { top: 12 },
    [`&.${stepConnectorClasses.active} .${stepConnectorClasses.line}`]: { backgroundImage: 'linear-gradient(90deg,#8E43F0,#D4006A)' },
    [`&.${stepConnectorClasses.completed} .${stepConnectorClasses.line}`]: { backgroundImage: 'linear-gradient(90deg,#8E43F0,#D4006A)' },
    [`& .${stepConnectorClasses.line}`]: { height: 2, border: 0, borderRadius: 4, bgcolor: 'transparent', background: '#DDD4F8' },
}));

function PurpleStepIcon({ active, completed, icon }) {
    return (
        <Box sx={{
            width: 28, height: 28, borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center',
            background: completed ? 'linear-gradient(135deg,#8E43F0,#D4006A)' : active ? '#EDE8FC' : '#F7F5FF',
            border: active ? '2px solid #8E43F0' : completed ? 'none' : '1.5px solid #DDD4F8',
            transition: 'all 0.2s',
        }}>
            {completed
                ? <CheckCircle sx={{ fontSize: 16, color: '#fff' }} />
                : <Typography sx={{ fontSize: '0.68rem', fontWeight: 800, color: active ? '#8E43F0' : 'text.disabled' }}>{icon}</Typography>}
        </Box>
    );
}

function fmtCommentDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function TaskDetailSkeleton() {
    return (
        <Box>
            <Skeleton variant="text" width={200} height={44} sx={{ mb: 3 }} />
            <Skeleton variant="rounded" height={140} sx={{ borderRadius: 3, mb: 3 }} />
            <Grid container spacing={3}>
                <Grid item xs={12} md={8}><Skeleton variant="rounded" height={300} sx={{ borderRadius: 3 }} /></Grid>
                <Grid item xs={12} md={4}><Skeleton variant="rounded" height={250} sx={{ borderRadius: 3 }} /></Grid>
            </Grid>
        </Box>
    );
}

export default function TaskDetail() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { enqueueSnackbar } = useSnackbar();
    const { user } = useAuth();
    const [comment, setComment] = useState('');

    const { data, loading, error } = useQuery(GET_TASK, { variables: { id }, skip: !id, fetchPolicy: 'cache-and-network' });
    const task = data?.task;

    const [updateTask, { loading: saving }] = useMutation(UPDATE_TASK, {
        onCompleted: () => enqueueSnackbar('Status updated', { variant: 'success' }),
        onError: (e) => enqueueSnackbar(e.message || 'Update failed', { variant: 'error' }),
    });

    const [addComment, { loading: posting }] = useMutation(ADD_TASK_COMMENT, {
        onCompleted: () => { setComment(''); enqueueSnackbar('Comment posted', { variant: 'success' }); },
        onError: (e) => enqueueSnackbar(e.message || 'Failed to post comment', { variant: 'error' }),
        refetchQueries: [{ query: GET_TASKS }],
    });

    const handleStatusChange = (newStatus) => updateTask({ variables: { id, status: newStatus } });
    const handlePostComment = () => { if (comment.trim()) addComment({ variables: { task_id: id, comment: comment.trim() } }); };

    if (loading && !data) return <TaskDetailSkeleton />;
    if (error) return <Alert severity="error" sx={{ m: 3 }}>Failed to load task. {error.message}</Alert>;
    if (!task) return <Alert severity="warning" sx={{ m: 3 }}>Task not found. <Button size="small" onClick={() => navigate('/tasks')}>Back to Tasks</Button></Alert>;

    const comments = [...(task.comments ?? [])].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    const activeStep = STATUSES.indexOf(task.status);
    const pColor = priorityColor[task.priority] ?? '#8E43F0';
    const pBg = priorityBg[task.priority] ?? '#EDE8FC';
    const isOverdue = task.due_date && new Date(task.due_date) < new Date();
    const initials = (name) => (name ?? '?').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();

    return (
        <Box sx={{ pb: { xs: 20, md: 0 } }}>
            {/* Back */}
            <Button startIcon={<ArrowBack />} onClick={() => navigate('/tasks')} size="small" sx={{ color: 'text.secondary', mb: 2 }}>
                All Tasks
            </Button>

            {/* ── Hero header ──────────────────────────────── */}
            <Card elevation={0} sx={{
                borderRadius: 3, mb: 3, overflow: 'hidden',
                borderLeft: `4px solid ${pColor}`,
                border: `1px solid ${pColor}33`, borderLeftWidth: 4,
            }}>
                <CardContent sx={{ p: { xs: 2.5, sm: 3 } }}>
                    <Box sx={{
                        display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start',
                        flexDirection: { xs: 'column', sm: 'row' }, flexWrap: 'wrap', gap: 2
                    }}>
                        <Box sx={{ flex: 1, minWidth: 0 }}>
                            <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 0.75 }}>
                                {/* Priority pill */}
                                <Chip
                                    size="small" label={task.priority}
                                    sx={{ bgcolor: pBg, color: pColor, fontWeight: 700, fontSize: '0.7rem', height: 20, borderRadius: 1.5 }}
                                />
                                <StatusBadge value={task.status} />
                                {isOverdue && <Chip size="small" label="Overdue" sx={{ bgcolor: '#FFF1F2', color: '#DC2626', fontWeight: 700, fontSize: '0.7rem', height: 20 }} />}
                            </Stack>
                            <Typography variant="h5" fontWeight={800} sx={{ color: '#1A0A3C', mb: 0.5, letterSpacing: '-0.015em' }}>
                                {task.title}
                            </Typography>
                            <Typography variant="body2" color="text.secondary">
                                {task.project?.name ?? '—'} · Assigned to {task.assignee?.name ?? 'Unassigned'}
                            </Typography>
                        </Box>
                        <Button
                            variant="outlined" startIcon={<Edit />} size="small"
                            onClick={() => navigate(`/tasks/${id}/edit`)}
                            sx={{ borderRadius: 2, flexShrink: 0 }}
                        >
                            Edit
                        </Button>
                    </Box>
                </CardContent>
            </Card>

            <Grid container spacing={3}>
                {/* ── Left column ─────────────────────────────── */}
                <Grid item xs={12} md={8}>
                    <Stack spacing={3}>
                        {/* Description */}
                        <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="h6" fontWeight={700} sx={{ mb: 1.5 }}>Description</Typography>
                                {task.description
                                    ? <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'pre-wrap', lineHeight: 1.85 }}>{task.description}</Typography>
                                    : <Typography variant="body2" color="text.disabled" fontStyle="italic">No description provided.</Typography>}
                            </CardContent>
                        </Card>

                        {/* ── Status Stepper ─────────────────────── */}
                        <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
                            <CardContent sx={{ p: 3 }}>
                                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2.5 }}>
                                    <Typography variant="h6" fontWeight={700}>Status Pipeline</Typography>
                                    {saving && <CircularProgress size={18} />}
                                </Box>
                                <Stepper
                                    activeStep={activeStep}
                                    alternativeLabel
                                    connector={<PurpleConnector />}
                                    sx={{ mb: 0 }}
                                >
                                    {STATUSES.map((s, i) => (
                                        <Step key={s} completed={i < activeStep} sx={{ cursor: 'pointer' }} onClick={() => !saving && handleStatusChange(s)}>
                                            <StepLabel StepIconComponent={PurpleStepIcon}>
                                                <Typography
                                                    variant="caption"
                                                    fontWeight={task.status === s ? 700 : 500}
                                                    sx={{
                                                        color: task.status === s ? 'primary.main' : i < activeStep ? '#8E43F0' : 'text.secondary',
                                                        fontSize: '0.72rem',
                                                    }}
                                                >
                                                    {STATUS_LABELS[s]}
                                                </Typography>
                                            </StepLabel>
                                        </Step>
                                    ))}
                                </Stepper>
                                <Typography variant="caption" color="text.disabled" sx={{ display: 'block', textAlign: 'center', mt: 1.5 }}>
                                    Click a step to move the task forward or backward
                                </Typography>
                            </CardContent>
                        </Card>

                        {/* ── Related Project card ───────────────── */}
                        {task.project && (
                            <Card
                                elevation={0}
                                sx={{
                                    borderRadius: 3, border: '1px solid #DDD4F8', cursor: 'pointer',
                                    transition: 'all 0.15s',
                                    '&:hover': { borderColor: 'primary.main', boxShadow: '0 4px 14px rgba(142,67,240,0.10)' },
                                }}
                                onClick={() => navigate(`/projects/${task.project.id}`)}
                            >
                                <CardContent sx={{ p: 2.5, display: 'flex', alignItems: 'center', gap: 2 }}>
                                    <Box sx={{ width: 40, height: 40, borderRadius: 2, bgcolor: '#EDE8FC', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                        <FolderOpen sx={{ color: '#8E43F0', fontSize: 20 }} />
                                    </Box>
                                    <Box sx={{ flex: 1, minWidth: 0 }}>
                                        <Typography variant="caption" color="text.secondary" fontWeight={600}>Related Project</Typography>
                                        <Typography variant="body2" fontWeight={700} noWrap color="primary.main">{task.project.name}</Typography>
                                        {task.project.status && <StatusBadge value={task.project.status} />}
                                    </Box>
                                    <Typography variant="caption" color="primary.main" fontWeight={600} sx={{ flexShrink: 0 }}>View →</Typography>
                                </CardContent>
                            </Card>
                        )}

                        {/* ── Comment thread ─────────────────────── */}
                        <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
                            <CardContent sx={{ p: 3 }}>
                                <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>
                                    Comments
                                    {comments.length > 0 && (
                                        <Typography component="span" variant="caption" color="text.secondary" sx={{ ml: 1 }}>
                                            ({comments.length})
                                        </Typography>
                                    )}
                                </Typography>

                                {/* Bubbles */}
                                {comments.length === 0 ? (
                                    <Box sx={{ py: 3, textAlign: 'center' }}>
                                        <Typography variant="body2" color="text.secondary">No comments yet. Be the first to comment.</Typography>
                                    </Box>
                                ) : (
                                    <Stack spacing={2} sx={{ mb: 2.5 }}>
                                        {comments.map((c, i) => {
                                            const isMe = c.user?.id === user?.id;
                                            return (
                                                <Box key={c.id} sx={{ display: 'flex', gap: 1.5, alignItems: 'flex-start', flexDirection: isMe ? 'row-reverse' : 'row' }}>
                                                    <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
                                                        <Avatar src={c.user?.avatar_url} sx={{ width: 30, height: 30, bgcolor: 'primary.dark', fontSize: '0.68rem', fontWeight: 700, border: '2px solid #fff' }}>
                                                            {initials(c.user?.name)}
                                                        </Avatar>
                                                    </Box>
                                                    <Box sx={{ maxWidth: '75%' }}>
                                                        <Box sx={{
                                                            px: 2, py: 1.25, borderRadius: isMe ? '16px 4px 16px 16px' : '4px 16px 16px 16px',
                                                            bgcolor: isMe ? '#EDE8FC' : '#F7F5FF',
                                                            border: `1px solid ${isMe ? '#D4B8FA' : '#DDD4F8'}`,
                                                        }}>
                                                            <Box sx={{ display: 'flex', justifyContent: 'space-between', gap: 2, mb: 0.5 }}>
                                                                <Typography variant="caption" fontWeight={700} color={isMe ? 'primary.main' : 'text.primary'}>{c.user?.name ?? 'Unknown'}</Typography>
                                                                <Typography variant="caption" color="text.disabled" sx={{ whiteSpace: 'nowrap' }}>{fmtCommentDate(c.created_at)}</Typography>
                                                            </Box>
                                                            <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap', lineHeight: 1.65 }}>{c.content}</Typography>
                                                        </Box>
                                                    </Box>
                                                </Box>
                                            );
                                        })}
                                    </Stack>
                                )}

                                <Divider sx={{ mb: 2, borderColor: '#EDE8FC' }} />

                                {/* Post comment */}
                                <Stack direction="row" spacing={1.5} alignItems="flex-end">
                                    <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
                                        <Avatar sx={{ width: 30, height: 30, bgcolor: 'primary.main', fontSize: '0.68rem', fontWeight: 700, border: '2px solid #fff' }}>
                                            {initials(user?.name)}
                                        </Avatar>
                                    </Box>
                                    <TextField fullWidth multiline rows={2} size="small"
                                        placeholder="Write a comment… (Ctrl+Enter to post)"
                                        value={comment}
                                        onChange={(e) => setComment(e.target.value)}
                                        onKeyDown={(e) => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) handlePostComment(); }}
                                        sx={{ '& .MuiOutlinedInput-root': { borderRadius: 2.5, bgcolor: '#F7F5FF', '& fieldset': { borderColor: '#DDD4F8' } } }}
                                    />
                                    <Button
                                        variant="contained" size="small"
                                        disabled={!comment.trim() || posting}
                                        endIcon={posting ? <CircularProgress size={14} color="inherit" /> : <SendOutlined sx={{ fontSize: 16 }} />}
                                        onClick={handlePostComment}
                                        sx={{ borderRadius: 2.5, py: 1.25, minWidth: 80, flexShrink: 0 }}
                                    >
                                        Post
                                    </Button>
                                </Stack>
                            </CardContent>
                        </Card>
                    </Stack>
                </Grid>

                {/* ── Sidebar ──────────────────────────────────── */}
                <Grid item xs={12} md={4}>
                    <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', position: { md: 'sticky' }, top: { md: 80 } }}>
                        <CardContent sx={{ p: 3 }}>
                            <Typography variant="h6" fontWeight={700} sx={{ mb: 2 }}>Details</Typography>
                            <Stack spacing={2}>
                                {[
                                    {
                                        icon: FlagOutlined, label: 'Priority',
                                        value: (
                                            <Box sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5, px: 1.25, py: 0.3, borderRadius: 2, bgcolor: pBg }}>
                                                <FiberManualRecord sx={{ fontSize: 8, color: pColor }} />
                                                <Typography sx={{ fontSize: '0.72rem', fontWeight: 700, color: pColor }}>{task.priority}</Typography>
                                            </Box>
                                        ),
                                    },
                                    { icon: Assignment, label: 'Status', value: <StatusBadge value={task.status} /> },
                                    { icon: PersonOutlined, label: 'Assignee', value: task.assignee?.name ?? 'Unassigned' },
                                    {
                                        icon: CalendarToday, label: 'Due Date',
                                        value: (
                                            <Typography variant="body2" fontWeight={600} sx={{ color: isOverdue ? 'error.main' : 'text.primary' }}>
                                                {fmtDate(task.due_date)} {isOverdue && '⚠'}
                                            </Typography>
                                        ),
                                    },
                                    { icon: CalendarToday, label: 'Completed', value: task.completed_at ? fmtDate(task.completed_at) : '—' },
                                ].map(({ icon: Icon, label, value }) => (
                                    <Box key={label}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.75, mb: 0.5 }}>
                                            <Icon sx={{ fontSize: 14, color: 'text.secondary' }} />
                                            <Typography variant="caption" color="text.secondary" fontWeight={600}>{label}</Typography>
                                        </Box>
                                        {typeof value === 'string'
                                            ? <Typography variant="body2" fontWeight={500} sx={{ pl: 2.5 }}>{value}</Typography>
                                            : <Box sx={{ pl: 2.5 }}>{value}</Box>}
                                    </Box>
                                ))}
                            </Stack>
                        </CardContent>
                    </Card>
                </Grid>
            </Grid>
            {/* Mobile sticky action bar — xs only */}
            <Box sx={{
                display: { xs: 'flex', sm: 'none' },
                position: 'fixed', bottom: 64, left: 0, right: 0, zIndex: 1200,
                px: 2, py: 1.5,
                bgcolor: 'rgba(255,255,255,0.95)', backdropFilter: 'blur(10px)',
                borderTop: '1px solid #DDD4F8',
            }}>
                <Button
                    fullWidth variant="contained" startIcon={<Edit />}
                    onClick={() => navigate(/tasks/ / edit)}
                    sx={{ borderRadius: 2, py: 1.2, background: 'linear-gradient(135deg,#6A1FCC,#8E43F0)', fontWeight: 700 }}
                >
                    Edit Task
                </Button>
            </Box>
        </Box>
    );
}

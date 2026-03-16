import { useState, useMemo } from 'react';
import {
  Box, Button, Typography, Stack, Select, MenuItem, IconButton, Tooltip,
  Skeleton, Alert, Chip, Fab, Avatar,
} from '@mui/material';

function ActionBtn({ title, icon: Icon, color, hoverBg, onClick }) {
  return (
    <Tooltip title={title}>
      <IconButton onClick={onClick} size="small" sx={{
        width: 30, height: 30, borderRadius: 1.5, color,
        transition: 'all 0.15s',
        '&:hover': { bgcolor: hoverBg, transform: 'scale(1.12)' },
      }}>
        <Icon sx={{ fontSize: 17 }} />
      </IconButton>
    </Tooltip>
  );
}
import { Add, Edit, Delete, Visibility } from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import DataTable from '../../components/saas/DataTable';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_TASKS, GET_PROJECTS, GET_DEVELOPERS } from '../../graphql/queries';
import { CREATE_TASK, UPDATE_TASK, DELETE_TASK } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';

const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];
const STATUSES = ['open', 'in_progress', 'review', 'done'];
const EMPTY = { title: '', project_id: '', assignee_id: '', priority: 'Medium', status: 'open', due_date: '' };

const priorityColor = { Critical: '#DC2626', High: '#D97706', Medium: '#8E43F0', Low: '#16A34A' };
const priorityBg = { Critical: '#FFF1F2', High: '#FFFBEB', Medium: '#EDE8FC', Low: '#F0FDF4' };
const priorityBorder = { Critical: '#FECDD3', High: '#FDE68A', Medium: '#D4B8FA', Low: '#BBF7D0' };

// ── Due date colour helper ─────────────────────────────────────────
function dueDateColor(iso) {
  if (!iso) return 'text.secondary';
  const due = new Date(iso);
  const now = new Date();
  const diff = (due - now) / 86400000;          // days remaining
  if (diff < 0) return 'error.main';           // overdue  — red
  if (diff <= 3) return 'warning.main';         // ≤ 3 days — amber
  return 'text.secondary';
}

export default function Tasks() {
  const { hasRole } = useAuth();
  const navigate = useNavigate();
  const { confirm } = useConfirmation();
  const { enqueueSnackbar } = useSnackbar();

  // ── UI state ────────────────────────────────────────────────────
  const [priorityFilter, setPriorityFilter] = useState('All');
  const [assigneeFilter, setAssigneeFilter] = useState('all');
  const [form, setForm] = useState(EMPTY);
  const [editing, setEditing] = useState(null);

  // ── Queries ──────────────────────────────────────────────────────
  const { data, loading, error, refetch } = useQuery(GET_TASKS, { fetchPolicy: 'cache-and-network' });
  const { data: projectsData } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-and-network' });
  const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-and-network' });

  const tasks = data?.tasks ?? [];
  const projects = projectsData?.projects ?? [];
  const developers = devsData?.developers ?? [];

  // ── Mutations ────────────────────────────────────────────────────
  const [createTask, { loading: creating }] = useMutation(CREATE_TASK, {
    onCompleted: () => { refetch(); enqueueSnackbar('Task created', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to create task', { variant: 'error' }),
  });
  const [updateTask, { loading: updating }] = useMutation(UPDATE_TASK, {
    onCompleted: () => { refetch(); enqueueSnackbar('Task updated', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update task', { variant: 'error' }),
  });
  const [deleteTask] = useMutation(DELETE_TASK, {
    onCompleted: () => { refetch(); enqueueSnackbar('Task deleted', { variant: 'info' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to delete task', { variant: 'error' }),
  });

  const handleStatusChange = (id, status) => updateTask({ variables: { id, status } });

  const handleSave = () => {
    const vars = { title: form.title, project_id: form.project_id, assignee_id: form.assignee_id || null, priority: form.priority, status: form.status, due_date: form.due_date || null };
    if (editing) updateTask({ variables: { id: editing.id, ...vars } });
    else createTask({ variables: vars });
  };

  const handleEdit = (row) => {
    setEditing(row);
    setForm({ title: row.title, project_id: row.project?.id ?? '', assignee_id: row.assignee?.id ?? '', priority: row.priority, status: row.status, due_date: row.due_date ?? '' });
  };

  const handleDelete = async (taskId, title) => {
    const ok = await confirm({ title: 'Delete Task', message: `Delete "${title}"? This cannot be undone.`, confirmText: 'Delete', severity: 'error' });
    if (ok) deleteTask({ variables: { id: taskId } });
  };

  // ── Filtered tasks ───────────────────────────────────────────────
  const filtered = useMemo(() => {
    let list = tasks;
    if (priorityFilter !== 'All') list = list.filter((t) => t.priority === priorityFilter);
    if (assigneeFilter !== 'all') list = list.filter((t) => (t.assignee?.id ?? 'unassigned') === assigneeFilter);
    return list;
  }, [tasks, priorityFilter, assigneeFilter]);

  const rows = filtered.map((t) => ({
    ...t,
    project: t.project?.name ?? '—',
    assignee: t.assignee?.name ?? 'Unassigned',
    due: fmtDate(t.due_date),
  }));

  const columns = [
    {
      key: 'priority', label: 'Priority',
      render: (v) => (
        <Box sx={{
          display: 'inline-flex', alignItems: 'center', gap: 0.5,
          px: 1.25, py: 0.3, borderRadius: 2,
          bgcolor: priorityBg[v] ?? '#EDE8FC',
          border: `1px solid ${priorityBorder[v] ?? '#D4B8FA'}`,
        }}>
          {/* Dot */}
          <Box sx={{ width: 6, height: 6, borderRadius: '50%', bgcolor: priorityColor[v] ?? '#8E43F0', flexShrink: 0 }} />
          <Typography sx={{ fontSize: '0.72rem', fontWeight: 700, color: priorityColor[v] ?? '#8E43F0' }}>{v}</Typography>
        </Box>
      ),
    },
    {
      key: 'title', label: 'Task',
      render: (v, row) => (
        <Box sx={{ cursor: 'pointer' }} onClick={() => navigate(`/tasks/${row.id}`)}>
          <Typography variant="body2" fontWeight={600} color="primary.main" sx={{ '&:hover': { textDecoration: 'underline' } }}>{v}</Typography>
          <Typography variant="caption" sx={{ color: dueDateColor(row.due_date) }}>
            {row.project} · Due {row.due}
            {new Date(row.due_date) < new Date() ? ' ⚠' : ''}
          </Typography>
        </Box>
      ),
    },
    {
      key: 'assignee', label: 'Assignee',
      render: (v) => (
        <Stack direction="row" alignItems="center" spacing={1}>
          <Avatar sx={{ width: 26, height: 26, fontSize: '0.62rem', fontWeight: 700, bgcolor: '#8E43F0', flexShrink: 0 }}>
            {v.split(' ').map((n) => n[0]).join('').slice(0, 2)}
          </Avatar>
          <Typography variant="body2">{v}</Typography>
        </Stack>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: (v, row) =>
        hasRole('SuperAdmin', 'Developer') ? (
          <Select value={v} onChange={(e) => handleStatusChange(row.id, e.target.value)} size="small"
            sx={{ fontSize: '0.8rem', minWidth: 130, borderRadius: 2, '& .MuiOutlinedInput-notchedOutline': { borderColor: '#DDD4F8' } }}>
            {STATUSES.map((s) => <MenuItem key={s} value={s}>{s.replace('_', ' ')}</MenuItem>)}
          </Select>
        ) : <StatusBadge value={v} />,
    },
    {
      key: 'actions', label: '', sortable: false,
      render: (_, row) => (
        <Stack direction="row" spacing={0.25}>
          <ActionBtn title="View" icon={Visibility} color="#8E43F0" hoverBg="#EDE8FC" onClick={() => navigate(`/tasks/${row.id}`)} />
          {hasRole('SuperAdmin') && <>
            <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/tasks/${row.id}/edit`)} />
            <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(row.id, row.title)} />
          </>}
        </Stack>
      ),
    },
  ];

  return (
    <Box sx={{ pb: { xs: 12, sm: 0 } }}>
      <PageHeader
        title="Tasks"
        subtitle={loading ? 'Loading…' : `${filtered.length} of ${tasks.length} tasks`}
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Tasks', path: '/tasks' }]}
        action={hasRole('SuperAdmin') && (
          <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/tasks/create')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
            Add Task
          </Button>
        )}
      />

      {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>Failed to load tasks. Please refresh.</Alert>}

      {/* ── Filter bar ─── */}
      <Box sx={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: 1.5, mb: 2 }}>
        {/* Priority chips — scrollable on mobile */}
        <Box sx={{ display: 'flex', gap: 1, overflowX: 'auto', pb: 0.5, flexWrap: 'nowrap', flexShrink: 1, minWidth: 0 }}>
          {['All', ...PRIORITIES].map((p) => {
            const active = priorityFilter === p;
            const col = priorityColor[p];
            return (
              <Chip
                key={p}
                label={p}
                size="small"
                onClick={() => setPriorityFilter(p)}
                sx={{
                  fontWeight: 700, fontSize: '0.76rem', cursor: 'pointer', flexShrink: 0,
                  ...(active && p !== 'All'
                    ? { bgcolor: priorityBg[p], color: col, border: `1.5px solid ${col}`, boxShadow: `0 0 0 2px ${col}22` }
                    : active
                      ? { bgcolor: 'primary.main', color: '#fff' }
                      : { bgcolor: '#F7F5FF', color: 'text.secondary', border: '1px solid #DDD4F8', '&:hover': { bgcolor: '#EDE8FC' } }
                  ),
                }}
              />
            );
          })}
        </Box>

        {/* Assignee filter — hidden on xs to save space */}
        {developers.length > 0 && (
          <Select
            value={assigneeFilter}
            onChange={(e) => setAssigneeFilter(e.target.value)}
            size="small"
            sx={{ minWidth: 160, borderRadius: 2, fontSize: '0.82rem', display: { xs: 'none', sm: 'flex' }, '& .MuiOutlinedInput-notchedOutline': { borderColor: '#DDD4F8' } }}
          >
            <MenuItem value="all">All Assignees</MenuItem>
            <MenuItem value="unassigned">Unassigned</MenuItem>
            {developers.map((d) => <MenuItem key={d.id} value={d.id}>{d.name}</MenuItem>)}
          </Select>
        )}
      </Box>

      {/* ── Table ─── */}
      {loading && !data ? (
        <Stack spacing={1.5}>
          {[1, 2, 3, 4, 5, 6].map((i) => <Skeleton key={i} variant="rounded" height={56} sx={{ borderRadius: 2 }} />)}
        </Stack>
      ) : (
        <DataTable columns={columns} rows={rows} searchKeys={['title', 'project', 'assignee']} defaultSort={{ key: 'priority', dir: 'asc' }} />
      )}

      {/* FAB for mobile */}
      {hasRole('SuperAdmin') && (
        <Fab color="primary" onClick={() => navigate('/tasks/create')} sx={{ position: 'fixed', bottom: 80, right: 16, display: { sm: 'none' } }}>
          <Add />
        </Fab>
      )}
    </Box>
  );
}

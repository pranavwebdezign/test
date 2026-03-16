import { useState, useMemo } from 'react';
import {
  Box, Button, LinearProgress, Typography, Stack, Avatar, AvatarGroup,
  IconButton, Tooltip, Skeleton, Alert, Grid, Card, CardContent,
  Chip, ToggleButtonGroup, ToggleButton, CircularProgress, Fab,
} from '@mui/material';

// Shared interactive action button
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
import {
  Add, Edit, Delete, TableRows, GridView, Visibility,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import DataTable from '../../components/saas/DataTable';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_PROJECTS, GET_CLIENTS, GET_DEVELOPERS } from '../../graphql/queries';
import { CREATE_PROJECT, UPDATE_PROJECT, DELETE_PROJECT } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';

const EMPTY = {};

const STATUS_FILTERS = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Cancelled', value: 'cancelled' },
];

// ── Progress ring ─────────────────────────────────────────────────
function ProgressRing({ value }) {
  return (
    <Box sx={{ position: 'relative', display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>
      <CircularProgress
        variant="determinate" value={100}
        size={52} thickness={4}
        sx={{ color: '#DDD4F8', position: 'absolute' }}
      />
      <CircularProgress
        variant="determinate" value={value ?? 0}
        size={52} thickness={4}
        sx={{ color: value >= 100 ? '#16A34A' : '#8E43F0' }}
      />
      <Typography
        variant="caption" fontWeight={700}
        sx={{ position: 'absolute', fontSize: '0.65rem', color: value >= 100 ? '#16A34A' : '#8E43F0' }}
      >
        {value ?? 0}%
      </Typography>
    </Box>
  );
}

export default function Projects() {
  const { hasRole } = useAuth();
  const navigate = useNavigate();
  const { confirm } = useConfirmation();
  const { enqueueSnackbar } = useSnackbar();

  const [viewMode, setViewMode] = useState('table');
  const [statusFilter, setStatusFilter] = useState('all');

  const [form, setForm] = useState(EMPTY);
  const [editing, setEditing] = useState(null);
  const set = (k, v) => setForm((p) => ({ ...p, [k]: v }));

  // ── Queries ──────────────────────────────────────────────────────
  const { data, loading, error, refetch } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-and-network' });
  const { data: clientsData } = useQuery(GET_CLIENTS, { fetchPolicy: 'cache-and-network' });
  const { data: devsData } = useQuery(GET_DEVELOPERS, { fetchPolicy: 'cache-and-network' });

  const projects = data?.projects ?? [];

  // ── Mutations ─────────────────────────────────────────────────────
  const [createProject, { loading: creating }] = useMutation(CREATE_PROJECT, {
    onCompleted: () => { setEditing(null); setForm(EMPTY); refetch(); enqueueSnackbar('Project created successfully', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to create project', { variant: 'error' }),
  });

  const [updateProject, { loading: updating }] = useMutation(UPDATE_PROJECT, {
    onCompleted: () => { setEditing(null); setForm(EMPTY); refetch(); enqueueSnackbar('Project updated', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update project', { variant: 'error' }),
  });

  const [deleteProject] = useMutation(DELETE_PROJECT, {
    onCompleted: () => { refetch(); enqueueSnackbar('Project deleted', { variant: 'info' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to delete project', { variant: 'error' }),
  });

  const handleSave = () => {
    const vars = { name: form.name, client_id: form.client_id, developer_ids: form.developer_ids, status: form.status, budget: form.budget ? parseFloat(form.budget) : null, due_date: form.due_date || null };
    if (editing) updateProject({ variables: { id: editing.id, ...vars } });
    else createProject({ variables: vars });
  };

  const handleDelete = async (projectId, name) => {
    const ok = await confirm({ title: 'Delete Project', message: `Delete "${name}"? This cannot be undone.`, confirmText: 'Delete', severity: 'error' });
    if (ok) deleteProject({ variables: { id: projectId } });
  };

  // ── Filter ───────────────────────────────────────────────────────
  const filtered = useMemo(() =>
    statusFilter === 'all' ? projects : projects.filter((p) => p.status === statusFilter),
    [projects, statusFilter]
  );
  const today = new Date();

  // ── Table rows ───────────────────────────────────────────────────
  const rows = filtered.map((p) => ({
    ...p,
    client: p.client?.name ?? '—',
    developer: p.developers?.[0]?.name ?? '—',
    due: fmtDate(p.due_date),
    budget: p.budget ?? 0,
  }));

  const columns = [
    {
      key: 'name', label: 'Project',
      render: (v, row) => (
        <Box sx={{ cursor: 'pointer' }} onClick={() => navigate(`/projects/${row.id}`)}>
          <Typography variant="body2" fontWeight={600} color="primary.main" sx={{ '&:hover': { textDecoration: 'underline' } }}>{v}</Typography>
          <Typography variant="caption" color="text.secondary">Due {row.due}</Typography>
        </Box>
      ),
    },
    { key: 'client', label: 'Client' },
    {
      key: 'developer', label: 'Developer',
      render: (v) => (
        <Stack direction="row" alignItems="center" spacing={1}>
          <Avatar sx={{ width: 24, height: 24, fontSize: '0.62rem', bgcolor: '#8E43F0' }}>{v.split(' ').map((n) => n[0]).join('')}</Avatar>
          <Typography variant="body2">{v}</Typography>
        </Stack>
      ),
    },
    {
      key: 'progress', label: 'Progress',
      render: (v) => (
        <Box sx={{ minWidth: 100 }}>
          <Typography variant="caption" color="text.secondary">{v ?? 0}%</Typography>
          <LinearProgress variant="determinate" value={v ?? 0} sx={{ borderRadius: 4, height: 6, bgcolor: '#DDD4F8', '& .MuiLinearProgress-bar': { bgcolor: v === 100 ? '#16A34A' : '#8E43F0', borderRadius: 4 } }} />
        </Box>
      ),
    },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge value={v} /> },
    { key: 'budget', label: 'Budget', render: (v) => <Typography variant="body2" fontWeight={600} color="primary.main">£{Number(v).toLocaleString()}</Typography> },
    {
      key: 'actions', label: '', sortable: false,
      render: (_, row) => (
        <Stack direction="row" spacing={0.25}>
          <ActionBtn title="View" icon={Visibility} color="#8E43F0" hoverBg="#EDE8FC" onClick={() => navigate(`/projects/${row.id}`)} />
          {hasRole('SuperAdmin') && <>
            <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/projects/${row.id}/edit`)} />
            <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(row.id, row.name)} />
          </>}
        </Stack>
      ),
    },
  ];

  return (
    <Box sx={{ pb: { xs: 12, sm: 0 } }}>
      <PageHeader
        title="Projects"
        subtitle={loading ? 'Loading…' : `${filtered.length} of ${projects.length} projects`}
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Projects', path: '/projects' }]}
        action={hasRole('SuperAdmin') && (
          <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/projects/create')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
            New Project
          </Button>
        )}
      />

      {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>Failed to load projects. Please refresh.</Alert>}

      {/* ── Filter row + view toggle ─── */}
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1.5 }}>
        {/* Status filter chips — horizontally scrollable on mobile */}
        <Box sx={{ display: 'flex', gap: 1, overflowX: 'auto', pb: 0.5, flexWrap: 'nowrap', flexShrink: 1, minWidth: 0 }}>
          {STATUS_FILTERS.map(({ label, value }) => (
            <Chip
              key={value}
              label={label}
              size="small"
              onClick={() => setStatusFilter(value)}
              sx={{
                fontWeight: 600, fontSize: '0.78rem', cursor: 'pointer',
                flexShrink: 0,
                ...(statusFilter === value
                  ? { bgcolor: 'primary.main', color: '#fff', '&:hover': { bgcolor: 'primary.dark' } }
                  : { bgcolor: '#F7F5FF', color: 'text.secondary', border: '1px solid #DDD4F8', '&:hover': { bgcolor: '#EDE8FC' } }
                ),
              }}
            />
          ))}
        </Box>

        {/* View toggle */}
        <ToggleButtonGroup
          value={viewMode}
          exclusive
          onChange={(_, v) => v && setViewMode(v)}
          size="small"
          sx={{ flexShrink: 0 }}
        >
          <ToggleButton value="table" sx={{ px: 1.5 }}>
            <Tooltip title="Table view"><TableRows fontSize="small" /></Tooltip>
          </ToggleButton>
          <ToggleButton value="grid" sx={{ px: 1.5 }}>
            <Tooltip title="Card view"><GridView fontSize="small" /></Tooltip>
          </ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {/* ── Loading skeleton ─── */}
      {loading && !data ? (
        <Grid container spacing={2}>
          {[1, 2, 3, 4, 5, 6].map((i) => (
            <Grid item xs={12} sm={6} lg={4} key={i}>
              <Skeleton variant="rounded" height={200} sx={{ borderRadius: 3 }} />
            </Grid>
          ))}
        </Grid>
      ) : viewMode === 'table' ? (
        /* ── Table view ─── */
        <DataTable columns={columns} rows={rows} searchKeys={['name', 'client', 'developer']} defaultSort={{ key: 'name', dir: 'asc' }} />
      ) : (
        /* ── Card / Grid view ─── */
        <Grid container spacing={2.5}>
          {filtered.length === 0 ? (
            <Grid item xs={12}>
              <Box sx={{ py: 8, textAlign: 'center' }}>
                <Typography color="text.secondary">No projects match the selected filter.</Typography>
              </Box>
            </Grid>
          ) : filtered.map((p) => {
            const isOverdue = p.due_date && new Date(p.due_date) < today;
            return (
              <Grid item xs={12} sm={6} lg={4} key={p.id}>
                <Card elevation={0} sx={{
                  borderRadius: 3, border: '1px solid #DDD4F8',
                  height: '100%', display: 'flex', flexDirection: 'column',
                  transition: 'all 0.2s',
                  '&:hover': { boxShadow: '0 8px 28px rgba(142,67,240,0.12)', transform: 'translateY(-2px)', borderColor: '#C4B0F0' },
                }}>
                  <CardContent sx={{ p: 2.5, flex: 1, display: 'flex', flexDirection: 'column' }}>
                    {/* Header */}
                    <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 1.5 }}>
                      <Box sx={{ flex: 1, minWidth: 0, mr: 1 }}>
                        <Typography
                          variant="h6" fontWeight={700} noWrap
                          sx={{ fontSize: '0.95rem', color: '#1A0A3C', cursor: 'pointer', '&:hover': { color: 'primary.main' } }}
                          onClick={() => navigate(`/projects/${p.id}`)}
                        >
                          {p.name}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">{p.client?.name ?? '—'}</Typography>
                      </Box>
                      <StatusBadge value={p.status} />
                    </Box>

                    {/* Progress ring — centered */}
                    <Box sx={{ display: 'flex', justifyContent: 'center', my: 2 }}>
                      <ProgressRing value={p.progress} />
                    </Box>

                    {/* Budget + Due */}
                    <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.5 }}>
                      <Typography variant="caption" fontWeight={600} color="primary.main">
                        £{Number(p.budget ?? 0).toLocaleString()}
                      </Typography>
                      <Typography
                        variant="caption"
                        sx={{ color: isOverdue ? 'error.main' : 'text.secondary' }}
                      >
                        Due {fmtDate(p.due_date)} {isOverdue && '⚠'}
                      </Typography>
                    </Stack>

                    {/* Developer avatars */}
                    {p.developers?.length > 0 && (
                      <Box sx={{ mb: 2 }}>
                        <AvatarGroup max={4} sx={{ justifyContent: 'flex-start', '& .MuiAvatar-root': { width: 26, height: 26, fontSize: '0.62rem', border: '2px solid #fff' } }}>
                          {p.developers.map((d) => (
                            <Tooltip key={d.id} title={d.name}>
                              <Avatar sx={{ bgcolor: 'primary.main' }}>
                                {d.name.split(' ').map((n) => n[0]).join('').slice(0, 2)}
                              </Avatar>
                            </Tooltip>
                          ))}
                        </AvatarGroup>
                      </Box>
                    )}

                    {/* Card actions */}
                    <Stack direction="row" spacing={0.5} alignItems="center" sx={{ mt: 'auto', pt: 1.5, borderTop: '1px solid #EDE8FC' }}>
                      <Button size="small" variant="outlined" startIcon={<Visibility />} onClick={() => navigate(`/projects/${p.id}`)} sx={{ borderRadius: 2, fontSize: '0.76rem', flex: 1 }}>
                        View
                      </Button>
                      {hasRole('SuperAdmin') && (
                        <>
                          <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/projects/${p.id}/edit`)} />
                          <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(p.id, p.name)} />
                        </>
                      )}
                    </Stack>
                  </CardContent>
                </Card>
              </Grid>
            );
          })}
        </Grid>
      )}

      {/* FAB for mobile */}
      {hasRole('SuperAdmin') && (
        <Fab
          color="primary"
          onClick={() => navigate('/projects/create')}
          sx={{ position: 'fixed', bottom: 80, right: 16, display: { sm: 'none' } }}
        >
          <Add />
        </Fab>
      )}
    </Box>
  );
}

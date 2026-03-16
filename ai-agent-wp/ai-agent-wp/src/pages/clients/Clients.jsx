import { useState, useMemo } from 'react';
import {
  Box, Button, Typography, Stack, Avatar, IconButton, Tooltip,
  Skeleton, Alert, Chip, Grid, Card, CardContent, Fab,
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
import { Add, Edit, Delete, Visibility, Business, Email } from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import DataTable from '../../components/saas/DataTable';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_CLIENTS } from '../../graphql/queries';
import { UPDATE_CLIENT, DELETE_CLIENT } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';

const STATUS_FILTERS = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

// Palette for auto-coloured avatars
const avatarColors = ['#8E43F0', '#0099C2', '#D97706', '#16A34A', '#DC2626', '#6A1FCC'];
const avatarColor = (name) => avatarColors[name.charCodeAt(0) % avatarColors.length];

export default function SaasClients() {
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const { confirm } = useConfirmation();
  const { enqueueSnackbar } = useSnackbar();

  const [statusFilter, setStatusFilter] = useState('all');

  const { data, loading, error, refetch } = useQuery(GET_CLIENTS, { fetchPolicy: 'cache-and-network' });
  const clients = data?.clients ?? [];

  const [updateClient] = useMutation(UPDATE_CLIENT, {
    onCompleted: () => { refetch(); enqueueSnackbar('Client updated', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update client', { variant: 'error' }),
  });

  const [deleteClient] = useMutation(DELETE_CLIENT, {
    onCompleted: () => { refetch(); enqueueSnackbar('Client deleted', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to delete client', { variant: 'error' }),
  });

  const handleToggleStatus = (row) => {
    const newStatus = row.status === 'active' ? 'inactive' : 'active';
    updateClient({ variables: { id: row.id, status: newStatus } });
  };

  const handleDelete = async (id, name) => {
    const ok = await confirm({ title: 'Delete Client', message: `Delete "${name}"? This cannot be undone.`, confirmText: 'Delete', severity: 'error' });
    if (ok) deleteClient({ variables: { id } });
  };

  const filtered = useMemo(() =>
    statusFilter === 'all' ? clients : clients.filter((c) => c.status === statusFilter),
    [clients, statusFilter]
  );

  const rows = filtered.map((c) => ({
    ...c,
    revenue: c.revenue ?? 0,
    joined: fmtDate(c.joined_at),
  }));

  const columns = [
    {
      key: 'name', label: 'Client',
      render: (v, row) => (
        <Stack direction="row" alignItems="center" spacing={1.5}>
          {/* Gradient-ring avatar */}
          <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
            <Avatar sx={{ width: 32, height: 32, bgcolor: avatarColor(v), fontSize: '0.72rem', fontWeight: 700, border: '2px solid #fff' }}>
              {v.split(' ').map((n) => n[0]).join('').slice(0, 2)}
            </Avatar>
          </Box>
          <Box>
            <Typography variant="body2" fontWeight={700}>{v}</Typography>
            <Typography variant="caption" color="text.secondary">{row.email}</Typography>
          </Box>
        </Stack>
      ),
    },
    {
      key: 'company', label: 'Company',
      render: (v) => (
        <Stack direction="row" alignItems="center" spacing={0.75}>
          <Business sx={{ fontSize: 14, color: 'text.disabled' }} />
          <Typography variant="body2">{v ?? '—'}</Typography>
        </Stack>
      ),
    },
    {
      key: 'revenue', label: 'Revenue',
      render: (v) => <Typography variant="body2" fontWeight={700} color="primary.main">£{Number(v).toLocaleString()}</Typography>,
    },
    { key: 'joined', label: 'Joined' },
    { key: 'status', label: 'Status', render: (v) => <StatusBadge value={v} /> },
    {
      key: 'actions', label: '', sortable: false,
      render: (_, row) => (
        <Stack direction="row" spacing={0.25}>
          <ActionBtn title="View" icon={Visibility} color="#8E43F0" hoverBg="#EDE8FC" onClick={() => navigate(`/clients/${row.id}`)} />
          {hasRole('SuperAdmin') && <>
            <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/clients/${row.id}/edit`)} />
            <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(row.id, row.name)} />
          </>}
        </Stack>
      ),
    },
  ];

  return (
    <Box sx={{ pb: { xs: 12, sm: 0 } }}>
      <PageHeader
        title="Clients"
        subtitle={loading ? 'Loading…' : `${filtered.length} of ${clients.length} clients`}
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Clients', path: '/clients' }]}
        action={hasRole('SuperAdmin') && (
          <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/clients/create')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
            Add Client
          </Button>
        )}
      />

      {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>Failed to load clients. Please refresh.</Alert>}

      {/* Status filter chips — horizontally scrollable on mobile */}
      <Box sx={{ display: 'flex', gap: 1, mb: 2, overflowX: 'auto', pb: 0.5, flexWrap: 'nowrap' }}>
        {STATUS_FILTERS.map(({ label, value }) => (
          <Chip
            key={value}
            label={label}
            size="small"
            onClick={() => setStatusFilter(value)}
            sx={{
              fontWeight: 600, fontSize: '0.78rem', cursor: 'pointer', flexShrink: 0,
              ...(statusFilter === value
                ? { bgcolor: 'primary.main', color: '#fff' }
                : { bgcolor: '#F7F5FF', color: 'text.secondary', border: '1px solid #DDD4F8' }
              ),
            }}
          />
        ))}
      </Box>

      {loading && !data
        ? <Stack spacing={1.5}>{[1, 2, 3, 4, 5].map((i) => <Skeleton key={i} variant="rounded" height={56} sx={{ borderRadius: 2 }} />)}</Stack>
        : <DataTable columns={columns} rows={rows} searchKeys={['name', 'email', 'company']} defaultSort={{ key: 'name', dir: 'asc' }} />
      }

      {hasRole('SuperAdmin') && (
        <Fab color="primary" onClick={() => navigate('/clients/create')} sx={{ position: 'fixed', bottom: 80, right: 16, display: { sm: 'none' } }}>
          <Add />
        </Fab>
      )}
    </Box>
  );
}

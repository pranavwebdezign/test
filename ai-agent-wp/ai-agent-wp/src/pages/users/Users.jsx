import {
  Box, Button, Typography, Stack, Avatar, Chip, Switch, Skeleton, Alert, Fab,
} from '@mui/material';
import { Add, Edit, Visibility, Delete } from '@mui/icons-material';
import { Tooltip, IconButton } from '@mui/material';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useSnackbar } from 'notistack';
import PageHeader from '../../components/saas/PageHeader';
import DataTable from '../../components/saas/DataTable';
import StatusBadge from '../../components/saas/StatusBadge';
import { useAuth } from '../../contexts/AuthContext';
import { useConfirmation } from '../../context/ConfirmationContext';
import { GET_USERS } from '../../graphql/queries';
import { UPDATE_USER, DELETE_USER } from '../../graphql/mutations';
import { fmtDate } from '../../utils/dates';

// Interactive action button — consistent with other list pages
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

// Role → gradient mapping
const roleGradient = {
  SuperAdmin: 'linear-gradient(135deg,#8E43F0,#D4006A)',
  Developer: 'linear-gradient(135deg,#0099C2,#8E43F0)',
  Client: 'linear-gradient(135deg,#16A34A,#0099C2)',
};
const roleBg = { SuperAdmin: '#8E43F0', Developer: '#0099C2', Client: '#16A34A' };
const avatarColors = ['#8E43F0', '#0099C2', '#D97706', '#16A34A', '#DC2626', '#6A1FCC'];
const avatarColor = (name) => avatarColors[(name ?? '').charCodeAt(0) % avatarColors.length];

export default function Users() {
  const navigate = useNavigate();
  const { hasRole } = useAuth();
  const { enqueueSnackbar } = useSnackbar();
  const { confirm } = useConfirmation();

  const { data, loading, error, refetch } = useQuery(GET_USERS, { fetchPolicy: 'cache-and-network' });
  const users = data?.users ?? [];

  const [updateUser] = useMutation(UPDATE_USER, {
    onCompleted: () => { refetch(); enqueueSnackbar('User updated', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to update user', { variant: 'error' }),
  });

  const [deleteUser] = useMutation(DELETE_USER, {
    onCompleted: () => { refetch(); enqueueSnackbar('User deleted', { variant: 'success' }); },
    onError: (e) => enqueueSnackbar(e.message || 'Failed to delete user', { variant: 'error' }),
  });

  const toggleStatus = (row) => {
    const newStatus = row.status === 'active' ? 'inactive' : 'active';
    updateUser({ variables: { id: row.id, status: newStatus } });
  };

  const handleDelete = async (id, name) => {
    const ok = await confirm({ title: 'Delete User', message: `Delete "${name}"? This cannot be undone.`, confirmText: 'Delete', severity: 'error' });
    if (ok) deleteUser({ variables: { id } });
  };

  const rows = users.map((u) => ({
    ...u,
    joined: fmtDate(u.last_login_at ?? u.created_at),
  }));

  const columns = [
    {
      key: 'name', label: 'User',
      render: (v, row) => (
        <Stack direction="row" alignItems="center" spacing={1.5}>
          {/* Gradient-ring avatar */}
          <Box sx={{ p: '2px', borderRadius: '50%', background: roleGradient[row.role] ?? 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
            <Avatar sx={{ width: 32, height: 32, bgcolor: roleBg[row.role] ?? avatarColor(v), fontSize: '0.72rem', fontWeight: 700, border: '2px solid #fff' }}>
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
      key: 'role', label: 'Role',
      render: (v) => (
        <Chip label={v} size="small" sx={{ bgcolor: roleBg[v] ? `${roleBg[v]}22` : '#EDE8FC', color: roleBg[v] ?? '#8E43F0', fontWeight: 700, fontSize: '0.72rem' }} />
      ),
    },
    { key: 'company', label: 'Company', render: (v) => <Typography variant="body2">{v ?? '—'}</Typography> },
    { key: 'joined', label: 'Last Login', render: (v) => <Typography variant="body2" color="text.secondary">{v}</Typography> },
    {
      key: 'status', label: 'Status',
      render: (v, row) => (
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <StatusBadge value={v} />
          <Switch size="small" checked={v === 'active'} onChange={() => toggleStatus(row)} color="success" />
        </Box>
      ),
    },
    {
      key: 'actions', label: '', sortable: false,
      render: (_, row) => (
        <Stack direction="row" spacing={0.25}>
          <ActionBtn title="Edit" icon={Edit} color="#D97706" hoverBg="#FFFBEB" onClick={() => navigate(`/users/${row.id}/edit`)} />
          {hasRole('SuperAdmin') && (
            <ActionBtn title="Delete" icon={Delete} color="#DC2626" hoverBg="#FFF1F2" onClick={() => handleDelete(row.id, row.name)} />
          )}
        </Stack>
      ),
    },
  ];

  return (
    <Box sx={{ pb: { xs: 12, sm: 0 } }}>
      <PageHeader
        title="User Management"
        subtitle={loading ? 'Loading…' : `${users.length} users · manage access and roles`}
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }, { label: 'Users', path: '/users' }]}
        action={hasRole('SuperAdmin') && (
          <Button variant="contained" startIcon={<Add />} onClick={() => navigate('/users/create')} sx={{ display: { xs: 'none', sm: 'inline-flex' } }}>
            Add User
          </Button>
        )}
      />

      {error && <Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>Failed to load users. Please refresh.</Alert>}

      {loading && !data
        ? <Stack spacing={1.5}>{[1, 2, 3, 4, 5].map((i) => <Skeleton key={i} variant="rounded" height={56} sx={{ borderRadius: 2 }} />)}</Stack>
        : <DataTable columns={columns} rows={rows} searchKeys={['name', 'email', 'role', 'company']} />
      }

      {hasRole('SuperAdmin') && (
        <Fab color="primary" onClick={() => navigate('/users/create')} sx={{ position: 'fixed', bottom: 80, right: 16, display: { sm: 'none' } }}>
          <Add />
        </Fab>
      )}
    </Box>
  );
}
